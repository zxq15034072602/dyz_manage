<?php
/**
 * 独一张管理app销售操作
 * @date: 2017年6月22日 上午9:30:37
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$store_id = $_REQUEST["store_id"]; // 所属门店Id
$uid = $_REQUEST['uid']; // 登陆用户id
$user_roleid = $_REQUEST['roleid']; // 用户权限id
if ($do == "index") { // 销售录入主页面
    $type=$_REQUEST[type]??0;//0独一张/1食维健
    $good_type = $db->select(0, 0, "rv_type","*","and type=$type");
    $store_goods = array();
    if ($good_type) { // 获取商品品牌，并获取所属门店的商品
        foreach ($good_type as $key => $type) {
            $sql = "select *,g.id as gid from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and g.fatherid=?";
            $db->p_e($sql, array(
                $store_id,
                $type[id]
            ));
            $goods = $db->fetchAll();
            if ($goods) {
                $store_goods[$key][typename] = $type[name];
                $store_goods[$key][goods] = $goods;
            }
        }
    }
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_goods", $store_goods);
    $smt->display("sales_index.html");
    exit();
} elseif ($do == "add_buy_cart"){ //添加销售商品到录入单
    $add_goods_list=json_decode($_REQUEST['goods_list']);//要入单的商品 （要求商品id,商品名称,商品单价）
    $number=$_REQUEST['number']??1;//添加的商品数量
    if(empty($store_id)&&empty($add_goods_list)&&!is_array($add_goods_list)&&empty($uid)){
       echo '{"code":"500","msg":"关键数据获取失败！"}';
       exit();
    }
    $add_type=$_REQUEST['add_type']??0;//添加类型:0 添加普通商品/1 添加赠品
    foreach($add_goods_list as $good){
        $sql="select * from rv_buy_cart where buyer_id=? and store_id=? and goods_id =? and bl_id=?";
        $db->p_e($sql, array($uid,$store_id,$good[id],$add_type));
        $row=$db->fetchRow();
        if($row){//如果已存在，则更新数据
            
        }else{
          
        }
    }

}
elseif ($do == "sales_view") { // 获取销售录入信息
    $type=$_REQUEST[type]??0;//0独一张/1食维健
    $add_goods_list=json_decode($_REQUEST['goods_list']);//要入单的商品 （要求商品id）
    if(empty($store_id)||empty($add_goods_list)||!is_array($add_goods_list)){
        echo '{"code":"500","msg":"关键数据获取失败！"}';
        exit();
    }
    $item_list_tmp = '';
    foreach ($add_goods_list as $good){
        $item_list_tmp .= $item_list_tmp ? ",$good[0]" : "$good[0]";
    }
    $sql = "select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and k.gid in ($item_list_tmp)";
    $db->p_e($sql, array($store_id));
    $goods_list= $db->fetchAll();
    if ($goods_list) {
        $store_goods = array();
        $sql = "select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? ";// 获取商品品牌，并获取所属门店的商品 (赠品时用)
        $db->p_e($sql, array(
            $store_id
        ));
        $store_goods = $db->fetchAll();
        echo '{"code":"200","goodslist":' . json_encode($goods_list) . ',"store_goods":'.json_encode($store_goods).'}';
        exit();
    }
    echo '{"code":"500","msg":"程序异常，请稍后重试"}';
    exit();
} elseif ($do == "sales_add") { // 提交销售录入
    $add_goods_list=json_decode($_REQUEST['goods_list'],true);//要入单的商品 （要求商品id,数量,商品类型0是正常商品/1 赠品）
    $total_price = $_REQUEST['total_price']; // 销售总价
    $sale_price = $_REQUEST['sale_price']??$total_price;//实际自定义的活动价格
    $sex = $_REQUEST['sex'] ?? 1;
    $address = $_REQUEST['address'] ?? "传国医精粹,布健康功德";
    $addtime = date('Y-m-d h:i:s');
    $status = ($user_roleid == 3 || $user_roleid == 1) ? 1 : 0; // 录入状态
    if(empty($add_goods_list)&&!is_array($add_goods_list)){
        echo '{"code":"500","msg":"商品信息有误"}';
        exit();
    }
    if (empty($_REQUEST['username'])) {
        echo '{"code":"500","msg":"顾客姓名不能为空"}';
        exit();
    }
    if (empty($_REQUEST['age'])) {
        echo '{"code":"500","msg":"顾客年龄不能为空"}';
        exit();
    }
    if (empty($_REQUEST['mobile'])) {
        echo '{"code":"500","msg":"顾客手机不能为空"}';
        exit();
    }
    if (! ismobile($_REQUEST['mobile'])) {
        echo '{"code":"500","msg":"手机号码不正确"}';
        exit();
    }
    foreach ($add_goods_list as $good){//循环全部商品 判断库存
        $sql = "select * from rv_kucun  where 1=1 and gid=? and mid=? ";
        $db->p_e($sql, array(
            $good[0],
            $store_id
        ));
        $sales_kucun = $db->fetchRow();
        
        if ($sales_kucun['kucun'] < $good[1]) {
            echo '{"code":"500","msg":"对不起，商品库存不足","kucun":"'.$store_id.'"}';
            exit();
        }
    }  
   
    $insert_buy = $db->insert(0, 2, "rv_buy", array(
        "uid=$uid",
        "mid=$store_id",
        "username='$_REQUEST[username]'",
        "sex=$sex",
        "age=$_REQUEST[age]",
        "tel=$_REQUEST[mobile]",
        "addtime='$addtime'",
        "address='$address'",
        "sale_price=$sale_price",
        "total_price=$total_price",
        "status=$status"
    ));
    
    if ($insert_buy) { // 销售录入插入成功后更新商品库存
        $sql="INSERT INTO rv_buy_goods (goods_id,buy_id,count,goods_type) VALUES";
        $item_list_tmp = '';
        $params = array();
        foreach($add_goods_list as $good){
            $item_list_tmp .= $item_list_tmp ? ",(?,?,?,?)" : "(?,?,?,?)";
            array_push($params,$good[0],$insert_buy,$good[1],$good[2]);
        }
        $sql .= $item_list_tmp;
        $db->p_e($sql, $params);
        if ($user_roleid == 3) { // 如果是店长
            foreach($add_goods_list as $good){
                $new_kuncun = $sales_kucun['kucun'] - $good[1];
                $db->update(0, 1, "rv_kucun", array("kucun=$new_kuncun"), array( "mid=$store_id","gid=$good[0]"));
                  
            }
            echo '{"code":"200","msg":"录入成功"}';
            exit();
            
        } else {
            echo '{"code":"200","msg":"录入成功,请到我的审查中查看"}';
            exit();
        }
    }
    echo '{"code":"500","msg":"录入失败,请重试"}';
    exit();
} elseif ($do == "sales_history1") { // 经销商加盟商销售记录
    $uid=$_REQUEST['uid'];
    //获取经销商加盟商门店
    $sql="select mid from rv_user_jingxiao_jiameng where 1=1 and uid=?";
    $db->p_e($sql, array($_REQUEST['uid']));
    $stroe=$db->fetchRow();
    $arr=explode(",", $stroe['mid']);
    $list=array();
    foreach($arr as $k=>$v){
        $sql="select id,name from rv_mendian where 1=1 and id=?";
        $db->p_e($sql, array($v));
        $arr2=$db->fetchRow();
        $list[$k]=$arr2;
    }
    //获取下拉框的值进行搜索
    $search='';
    $arr=array();   
    if($_REQUEST['mendian']){
        $search .= "and d.id like ? ";
        $arr['mid']=$_REQUEST['mendian'];
    }
    //产品销售记录按天排行(从高到低)
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$todaystart,$todayend));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($todaystart,$todayend));
    }         
    $day_list=$db->fetchAll();
    //产品销售记录按天排行(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$todaystart,$todayend));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($todaystart,$todayend));
    }   
    $day1_list=$db->fetchAll();
    $todaystart=date("Y年m月d日",$todaystart);
    
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    //产品销售排行按周排行(从高到低)
    if($_REQUEST['mendian']){
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($week_start,$week_end));
    }   
    $week_list=$db->fetchAll();
    //产品销售排行按周排行(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($week_start,$week_end));
    }  
    $week1_list=$db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("Y年m月d日",$week_end);
 
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    //产品销售排行按月排行(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$beginThismonth,$beginThismonth));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($beginThismonth,$beginThismonth));
    }   
    $month_list=$db->fetchAll();
    //产品销售排行按月排行(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$beginThismonth,$beginThismonth));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($beginThismonth,$beginThismonth));
    }  
    $month1_list=$db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);

    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    //产品销售排行按年排行(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$year_start,$year_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($year_start,$year_end));
    }  
    $year_list=$db->fetchAll();    
    //产品销售排行按年排行(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$year_start,$year_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id in ($stroe[mid])  and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($year_start,$year_end));
    }   
    $year1_list=$db->fetchAll();
    $year_start=date("Y年",$year_start);
    
    //模板
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("list", $list);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('endThismonth',$endThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_history1.html");
    exit();
}elseif($do=='sales_history2'){//店员店长产品销售记录
    
    $store_id=$_REQUEST['store_id'];
    //根据门店id查询门店名称
    $sql = "select name from rv_mendian where id=?";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_name = $db->fetch_count();
    
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    //店员店长所属门店销售排行按天排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$todaystart,$todayend));   
    $day_list = $db->fetchAll();
    //店员店长所属门店销售排行按天排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$todaystart,$todayend));   
    $day1_list = $db->fetchAll();  
    $todaystart=date("Y年m月d日",$todaystart);

    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    //店员店长所属门店销售排行按周排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$week_start,$week_end));
    $week_list = $db->fetchAll();
    //店员店长所属门店销售排行按周排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$week_start,$week_end));
    $week1_list = $db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("d日",$week_end);
    
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    //店员店长所属门店销售排行按月排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$beginThismonth,$endThismonth));
    $month_list = $db->fetchAll();
    //店员店长所属门店销售排行按月排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$beginThismonth,$endThismonth));
    $month1_list = $db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);
    
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    //店员店长所属门店销售排行按年排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$year_start,$year_end));
    $year_list = $db->fetchAll();
    
    //店员店长所属门店销售排行按年排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$year_start,$year_end));
    $year1_list = $db->fetchAll();
    $year_start=date("Y年",$year_start);
    //模板
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_history2.html");
    exit();
}elseif($do=='sales_history3'){//董事长、总经理、总部人员销售记录页面
    //查询所有门店生成下拉列表
    $sql="select id,name from rv_mendian";
    $db->p_e($sql, array());
    $list=$db->fetchAll();
    
    $search='';
    $arr=array();
    if($_REQUEST['mendian']){
        $search .= "and d.id like ? ";
        $arr['mid']=$_REQUEST['mendian'];
    }
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    //产品销售按天排序(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$todaystart,$todayend));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc";
        $db->p_e($sql, array($todaystart,$todayend));
    } 
    $day_list=$db->fetchAll();
    //产品销售按天排序(从低高高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$todaystart,$todayend));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($todaystart,$todayend));
    }   
    $day1_list=$db->fetchAll();
    $todaystart=date("Y年m月d日",$todaystart);
    
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    //产品销售按周排序(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc";
        $db->p_e($sql, array($week_start,$week_end));
    }     
    $week_list=$db->fetchAll();
    //产品销售按周排序(从低到高)
    if($_REQUEST['mendian']){
               $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($week_start,$week_end));
    }
    $week1_list=$db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("Y年m月d日",$week_end);
    
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    //产品销售按月排序(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$beginThismonth,$endThismonth));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc";
        $db->p_e($sql, array($beginThismonth,$endThismonth));
    } 
    $month_list=$db->fetchAll();
    //产品销售按月排序(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$beginThismonth,$endThismonth));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($beginThismonth,$endThismonth));
    }
    $month1_list=$db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);
    
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    //产品销售按年排序(从高到低)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
        $db->p_e($sql, array($arr['mid'],$year_start,$year_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc";
        $db->p_e($sql, array($year_start,$year_end));
    } 
    $year_list=$db->fetchAll();
    //产品销售按年排序(从低到高)
    if($_REQUEST['mendian']){
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where 1=1 ".$search." and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($arr['mid'],$year_start,$year_end));
    }else{
        $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,b.money,(sum(a.count)*b.money) as total  from (rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id where UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
        $db->p_e($sql, array($year_start,$year_end));
    }
    $year1_list=$db->fetchAll();
    $year_start=date("Y年",$year_start);
    //模板
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("list", $list);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('endThismonth',$endThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_history3.html");
    
    exit();   
}elseif ($do == "stock") { // 查看库存
    
    if (empty($store_id)) {
        echo '{"code":"500","msg":"门店ID不能为空！"}';
        exit();
    }
    
    $sql = "select g.id,g.name,g.good_img,g.dw,g.money,k.kucun from rv_goods as g,rv_kucun as k where g.id = k.gid and k.mid=? and k.kucun < 100";
    $db->p_e($sql, array(
        $store_id
    ));
    $stock_goods = $db->fetchAll();
    $sql = "select name from rv_mendian where 1=1 and id=?";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_name = $db->fetch_count(); // 获取门店名字
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign("stock_goods", $stock_goods);
    $smt->display("check_stock.html");
    exit();
}
/**
 * 销售排行模块
 */
if($do=='sales_ranking1'){//店员店长所属门店销售排行
    $store_id=$_REQUEST['store_id'];
    //获取店员店长所属门店
    $sql = "select name from rv_mendian where id=?";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_name = $db->fetch_count();
    //获取产品信息生成下拉框
    $sql="select id,name from rv_goods";
    $db->p_e($sql, array());
    $list=$db->fetchAll();
    
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    //门店按天排名数字(从高到低)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($todaystart, $todayend));
    $day_list=$db->fetchAll();
    $arr=array();
    foreach($day_list as $k=>$v){
        $arr[$k+1]=$v['mdname'];
    }
    $day_key=array_search($store_name, $arr);
           
    //门店产品销售排行按天排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$todaystart,$todayend));
    $day_list = $db->fetchAll();
    //门店产品销售排行按天排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$todaystart,$todayend));
    $day1_list = $db->fetchAll();
    $todaystart=date("Y年m月d日",$todaystart);
    
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($week_start,$week_end));
    $week_list=$db->fetchAll();
    $arr=array();
    foreach($week_list as $k=>$v){
        $arr[$k+1]=$v['mdname'];
    }
    $week_key=array_search($store_name, $arr);
    
    //门店产品销售排行按周排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$week_start,$week_end));
    $week_list = $db->fetchAll();
    //门店产品销售排行按周排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$week_start,$week_end));
    $week1_list = $db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("d日",$week_end);
    
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($beginThismonth, $endThismonth));
    $month_list=$db->fetchAll();
    $arr=array();
    foreach($month_list as $k=>$v){
        $arr[$k+1]=$v['mdname'];
    }
    $month_key=array_search($store_name, $arr);
    
    //门店产品销售排行按月排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$beginThismonth,$endThismonth));
    $month_list = $db->fetchAll();
    //门店产品销售排行按月排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$beginThismonth,$endThismonth));
    $month1_list = $db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);
    
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($year_start, $year_end));
    $year_list=$db->fetchAll();
    $arr=array();
    foreach($year_list as $k=>$v){
        $arr[$k+1]=$v['mdname'];
    }
    $year_key=array_search($store_name, $arr);
    
    //门店产品销售排行按年排序(从高到低)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($store_id,$year_start,$year_end));
    $year_list = $db->fetchAll();
    //门店产品销售排行按年排序(从低到高)
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id)left join rv_mendian as d on c.mid=d.id where d.id=? and UNIX_TIMESTAMP(c.addtime) BETWEEN ? AND ? group by a.goods_id order by total asc ";
    $db->p_e($sql, array($store_id,$year_start,$year_end));
    $year1_list = $db->fetchAll();
    $year_start=date("Y年",$year_start);
    //模板
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign('list',$list);
    $smt->assign('day_key',$day_key);
    $smt->assign('week_key',$week_key);
    $smt->assign('month_key',$month_key);
    $smt->assign('year_key',$year_key);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_rangking1.html");
    exit();
}elseif($do=='sales_ranking2'){//全国门店销量排行
    
    $sql="select id,name from rv_mendian";
    $db->p_e($sql, array());
    $list=$db->fetchAll();
    
    $search='';
    $arr=array();
    if($_REQUEST['mendian']){
        $search .= "and b.id like ? ";
        $arr['mid']=$_REQUEST['mendian'];
    }
    
    //门店按天排行(销售额从高到低)
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($arr['mid'],$todaystart, $todayend));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($todaystart, $todayend));
    }
    $day_list=$db->fetchAll();
    
    //门店按天排行(销售额从低到高)
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($arr['mid'],$todaystart, $todayend));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($todaystart, $todayend));
    }
    $day1_list=$db->fetchAll(); 
    $todaystart=date("Y年m月d日",$todaystart);
  
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    //门店按周排行(销售额从高到低)
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($week_start,$week_end));
    }
    $week_list=$db->fetchAll();
    //门店按周排行(销售额从低到高)
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($arr['mid'],$week_start,$week_end));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($week_start,$week_end));
    }
    $week1_list=$db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("d日",$week_end);
    
    //门店按月排行(销售额从高到低)
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($arr['mid'],$beginThismonth, $endThismonth));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($beginThismonth, $endThismonth));
    }
    $month_list=$db->fetchAll();
    //门店按月排行(销售额从低到高)
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($arr['mid'],$beginThismonth, $endThismonth));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($beginThismonth, $endThismonth));
    }
    $month1_list=$db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);
    
    //门店按年排行(销售额从高到低)
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($arr['mid'],$year_start, $year_end));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
        $db->p_e($sql, array($year_start, $year_end));
    }
    $year_list=$db->fetchAll();
    //门店按年排行(销售额从低到高)
    if($_REQUEST['mendian']){
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 ".$search." and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($arr['mid'],$year_start, $year_end));
    }else{
        $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
        $db->p_e($sql, array($year_start, $year_end));
    }
    $year1_list=$db->fetchAll();   
    $year_start=date("Y年",$year_start);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign('list',$list);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_rangking2.html");
    exit();
}elseif($do=='sales_ranking3'){//经销商加盟商销售排行
    $uid=$_REQUEST['uid'];
    $sql="select * from rv_user_jingxiao_jiameng where 1=1 and uid=?";
    $db->p_e($sql, array($uid));
    $stroe=$db->fetchRow();
    $arr=explode(",", $stroe['mid']);
    $list=array();
    foreach($arr as $k=>$v){
        $sql="select id,name from rv_mendian where 1=1 and id=?";
        $db->p_e($sql, array($v));
        $arr2=$db->fetchRow();
        $list[$k]=$arr2;
    }   
    //门店按天排行(销售额从高到低)
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($todaystart, $todayend));  
    $day_list=$db->fetchAll();    
    //门店按天排行(销售额从低到高)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
    $db->p_e($sql, array($todaystart, $todayend));  
    $day1_list=$db->fetchAll();
    $todaystart=date("Y年m月d日",$todaystart);
    
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    //门店按周排行(销售额从高到低)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($week_start,$week_end));
    $week_list=$db->fetchAll();
    //门店按周排行(销售额从低到高)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
    $db->p_e($sql, array($week_start,$week_end));
    $week1_list=$db->fetchAll();
    $week_start=date("Y年m月d日",$week_start);
    $week_end=date("d日",$week_end);
    
    //门店按月排行(销售额从高到低)
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($beginThismonth, $endThismonth));
    $month_list=$db->fetchAll();
    //门店按月排行(销售额从低到高)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
    $db->p_e($sql, array($beginThismonth, $endThismonth));
    $month1_list=$db->fetchAll();
    $beginThismonth=date("Y年m月",$beginThismonth);
    $endThismonth=date("Y年m月",$endThismonth);
    
    //门店按年排行(销售额从高到低)
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum desc";
    $db->p_e($sql, array($year_start, $year_end));
    $year_list=$db->fetchAll();
    //门店按年排行(销售额从低到高)
    $sql="select sum(a.total_price) as sum,b.name as mdname,b.id as mid from rv_buy as a left join rv_mendian as b on a.mid=b.id where 1=1 and b.id in ($stroe[mid]) and UNIX_TIMESTAMP(a.addtime) BETWEEN ? AND ? group by a.mid order by sum asc";
    $db->p_e($sql, array($year_start, $year_end));
    $year1_list=$db->fetchAll();
    $year_start=date("Y年",$year_start);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign('list',$list);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->assign("day1_list", $day1_list);
    $smt->assign("week1_list", $week1_list);
    $smt->assign("month1_list", $month1_list);
    $smt->assign("year1_list", $year1_list);
    $smt->assign('todaystart',$todaystart);
    $smt->assign('week_start',$week_start);
    $smt->assign('week_end',$week_end);
    $smt->assign('beginThismonth',$beginThismonth);
    $smt->assign('year_start',$year_start);
    $smt->display("sales_rangking3.html");
    exit();
}
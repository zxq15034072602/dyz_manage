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
} elseif ($do == "sales_history") { // 销售记录
    $sql = "select name from rv_mendian where id=?";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_name = $db->fetch_count();
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    $day_list = get_time_buy($store_id, $todaystart, $todayend);
    
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    $week_list = get_time_buy($store_id, $week_start, $week_end);
    
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    $month_list = get_time_buy($store_id, $beginThismonth, $endThismonth);
    
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    $year_list = get_time_buy($store_id, $year_start, $year_end);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("year_list", $year_list);
    $smt->display("sales_history.html");
    exit();
} elseif ($do == "stock") { // 查看库存
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
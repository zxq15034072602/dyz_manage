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
    $search = '';
    $arr = array();
    if ($_REQUEST['keywords']) { // 如果有搜索
        $search .= "and g.name like ? ";
        $arr[] = "%" . $_REQUEST['keywords'] . "%";
    }
    $sql = "select * from rv_buy as b,rv_goods as g where b.gid=g.id " . $search . " and b.mid=? ORDER BY shuliang desc";
    
    $arr[] = $store_id;
    $db->p_e($sql, $arr);
    $sales_goods = $db->fetchAll();
    
    $sql = "select * from rv_type where 1=1";
    $good_type = $db->select(0, 0, "rv_type");
    $store_goods = array();
    if ($good_type) { // 获取商品品牌，并获取所属门店的商品
        foreach ($good_type as $key => $type) {
            $sql = "select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and g.fatherid=?";
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
    $smt->assign("sales_goods", $sales_goods);
    $smt->assign("store_goods", $store_goods);
    $smt->display("sales_index.html");
    exit();
} elseif ($do == "sales_view") { // 获取销售录入信息
    $gid = $_REQUEST['gid']; // 商品id
    $sql = "select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and k.gid=?";
    $db->p_e($sql, array(
        $store_id,
        $gid
    ));
    $good = $db->fetchRow();
    if ($good) {
        echo '{"code":"200","goodinfo":' . json_encode($good) . '}';
        exit();
    }
    echo '{"code":"500","msg":"程序异常，请稍后重试"}';
    exit();
} elseif ($do == "sales_add") { // 提交销售录入
    $gid = $_REQUEST['gid']; // 商品id
    $count = $_REQUEST['count']; // 已选数量
    $total_price = $_REQUEST['total_price']; // 销售总价
    $sex = $_REQUEST['sex'] ?? 1;
    $address = $_REQUEST['address'] ?? "123";
    $addtime = date('Y-m-d h:i:s');
    
    $status = ($user_roleid == 3 || $user_roleid == 1) ? 1 : 0; // 录入状态
    if (empty($count)) {
        echo '{"code":"500","msg":"录入数量不能为空！"}';
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
    $sql = "select * from rv_kucun  where 1=1 and gid=? and mid=? ";
    $db->p_e($sql, array(
        $gid,
        $store_id
    ));
    $sales_kucun = $db->fetchRow();
    
    if ($sales_kucun['kucun'] < $count) {
        echo '{"code":"500","msg":"对不起，此商品库存不足"}';
        exit();
    }
    
    $insert_buy = $db->insert(0, 2, "rv_buy", array(
        "uid=$uid",
        "mid=$store_id",
        "gid=$gid",
        "username='$_REQUEST[username]'",
        "sex=$sex",
        "age=$_REQUEST[age]",
        "tel=$_REQUEST[mobile]",
        "shuliang=$count",
        "addtime='$addtime'",
        "address='$address'",
        "total_price=$total_price",
        "status=$status"
    ));
    if ($insert_buy) { // 销售录入插入成功后更新商品库存
        $new_kuncun = $sales_kucun['kucun'] - $count;
        if ($db->update(0, 1, "rv_kucun", array(
            "kucun=$new_kuncun"
        ), array(
            "mid=$store_id",
            "gid=$gid"
        ))) {
            if($user_roleid==3){//如果是店长
                echo '{"code":"200","msg":"录入成功"}';
            }else{
                echo '{"code":"200","msg":"录入成功,请到我的审查中查看"}';
            }
           
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
    $yaer_list = get_time_buy($store_id, $year_start, $year_end);
    
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign("day_list", $day_list);
    $smt->assign("week_list", $week_list);
    $smt->assign("month_list", $month_list);
    $smt->assign("yaer_list", $yaer_list);
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
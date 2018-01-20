<?php
/**
 * 独一张管理app审核管理
 * @date: 2017年6月26日 下午12:05:33
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$store_id = $_REQUEST["store_id"]; // 所属门店Id
$user_roleid = $_REQUEST['roleid']; // 用户权限id
$uid = $_REQUEST['uid']; // 登陆用户id
$time=time();

if ($do == "input_verify_list") // 销售录入列表页面
{
    if (empty($store_id) || empty($user_roleid)) { // 销售录入需要用户有了所属门店后才能使用
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    
    $sql="select b.id,b.uid,b.mid,b.addtime1,b.status,u.name,u.roleid,b.total_price,b.sale_price ,(select sum(count) from rv_buy_goods where buy_id=b.id) as num from rv_buy as b ,rv_user as u WHERE  b.uid=u.id and  b.mid=? order by b.id desc";
    $db->p_e($sql, array($store_id));
    $verify_list = $db->fetchAll();
    
    foreach ($verify_list as &$values){
        $values['addtime']=date('Y-m-d H:i:s',$values['addtime1']);
        $sql="select g.id,b.count from rv_buy_goods as b,rv_goods as g where b.goods_id=g.id and buy_id=?";
        $db->p_e($sql, array($values[id]));
        $values['goods_list']=$db->fetchAll();
        $values['js_goods_list']=json_encode($values['goods_list']);
    }
    
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("user_roleid", $user_roleid); // 登陆用户的角色id
    $smt->assign("flag", "list_i_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_i_verify") { // 同意销售录入审核
    $add_goods_list=json_decode($_REQUEST['goods_list']);//要入单的商品 （要求商品id,数量）
    if (empty($_REQUEST['bid']) || empty($user_roleid) || empty($add_goods_list) || !is_array($add_goods_list)|| empty($store_id)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] != $store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
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
            echo '{"code":"500","msg":"对不起，商品库存不足"}';
            exit();
        }
    }  
    $sql = "update rv_buy set status=1,endtime1=? where id=?";
    if ($db->p_e($sql, array($time,$_REQUEST['bid']))) { // 如果同意成功则，sokect推送数据
        
        foreach($add_goods_list as $good){
            $new_kuncun = $sales_kucun['kucun'] - $good[1];
            $db->update(0, 1, "rv_kucun", array("kucun=$new_kuncun"), array( "mid=$store_id","gid=$good[0]"));
        
        }
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => "你好，你的录入申请已经通过审核"
        );
        $cont = json_encode($cont);
        to_msg(array(
            'type' => 'verify_to_user',
            'cont' => $cont,
            'to' => $_REQUEST[touid]
        ));
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
} elseif ($do == "refuse_i_verify") { // 拒绝销售录入审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] != $store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_buy set status=2,endtime1=? where id=?";
    if ($db->p_e($sql, array(
        $time,
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => "你好，你的录入申请已被拒绝"
        );
        $cont = json_encode($cont);
        to_msg(array(
            'type' => 'verify_to_user',
            'cont' => $cont,
            'to' => $_REQUEST[touid]
        ));
        echo '{"code":"200","msg":"拒绝成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"拒绝失败！"}';
    exit();
} elseif ($do == "show_i_verify") { // 查看录入审核信息
    $vid = $_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql ="select b.id,b.uid,b.mid,b.addtime1,b.endtime1,u.name,b.status,b.total_price,b.sale_price,GROUP_CONCAT(bg.goods_id) as goods_id from rv_buy as b,rv_buy_goods as bg,rv_user as u where b.id=bg.buy_id and b.uid=u.id and b.id=?";
    $db->p_e($sql, array(
        $vid
    ));
    $verify_info = $db->fetchRow();
    if($verify_info){
        $verify_info['addtime']=date('Y-m-d H:i:s',$verify_info['addtime1']);
        $verify_info['endtime']=date('Y-m-d H:i:s',$verify_info['endtime1']);
        $sql="select * from rv_buy_goods as bg, rv_goods as g where bg.goods_id=g.id and bg.goods_id and buy_id=?";
        $db->p_e($sql, array($verify_info[id]));
        $verify_info['goods']=$db->fetchAll();
    }
    
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_info", $verify_info);
    $smt->assign("flag", "show_i_verify");
    $smt->display('verify_show.html');
    exit();
} elseif ($do == "people_verify_list") { // 人员审核列表
    if (empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select v.id,u.name,m.name as mdname,v.addtime1,v.updatetime1,u.roleid,v.type,v.status,v.uid,v.mid from rv_verify as v,rv_user as u,rv_mendian as m where v.uid=u.id and v.mid=m.id and v.mid =? and v.type=0 order by v.addtime1 DESC";
    $db->p_e($sql, array(
        $store_id
    ));
    $verify_list = $db->fetchAll();
    foreach($verify_list as &$v){
        $v['addtime']=date('Y-m-d H:i:s',$v['addtime1']);
    }
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("user_roleid", $user_roleid); // 登陆用户的角色id
    $smt->assign("flag", "list_p_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_p_verify") { // 同意人员审核
    if (empty($_REQUEST['vid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] != $store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_verify set status=1,updatetime1=? where id=?";
    if ($db->p_e($sql, array(
        $time,
        $_REQUEST[vid]
    ))) { // 如果同意成功则，sokect推送数据
        $sql = "update rv_user set zz=? where id=?";
        $db->p_e($sql, array(
            $_REQUEST[mid],
            $_REQUEST[touid]
        ));
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => "你好，你的加入申请已通过",
            "store_id" => $_REQUEST[mid],
            "roleid" =>5
        );
        $cont = json_encode($cont);
        to_msg(array(
            'type' => 'verify_to_user',
            'cont' => $cont,
            'to' => $_REQUEST[touid]
        ));
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
} elseif ($do == "refuse_p_verify") { // 拒绝人员审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] != $store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_verify set status=2,updatetime1=? where id=?";
    if ($db->p_e($sql, array(
        $time,
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => "你好，你的加入申请已被拒绝"
        );
        $cont = json_encode($cont);
        to_msg(array(
            'type' => 'verify_to_user',
            'cont' => $cont,
            'to' => $_REQUEST[touid]
        ));
        echo '{"code":"200","msg":"拒绝成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"拒绝失败！"}';
    exit();
} elseif ($do == "show_p_verify") { // 查看人员审核信息
    $vid = $_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select v.id,u.name,m.name as mdname,v.addtime1,v.updatetime1,v.status,v.type from rv_verify as v,rv_mendian as m,rv_user as u where v.mid=m.id and v.uid=u.id and v.id=?";
    $db->p_e($sql, array(
        $vid
    ));
    $verify_info = $db->fetchRow();
    $verify_info['addtime']=date('Y-m-d H:i:s',$verify_info['addtime1']);
    $verify_info['updatetime']=date('Y-m-d H:i:s',$verify_info['updatetime1']);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_info", $verify_info);
    $smt->assign("flag", "show_p_verify");
    $smt->display('verify_show.html');
    exit();
}elseif($do == "wdstatus"){//未读审核状态
    
    if(empty($store_id)){
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
   $sql="select count(*) from rv_verify where 1=1 and mid=? and status=0";      
   $db->p_e($sql, array(
        $store_id,
    ));
   $verify=$db->fetch_count();
   
   $sql1="select count(*) from rv_buy where 1=1 and mid=? and status=0";
   $db->p_e($sql1, array(
       $store_id
   ));
   $buy=$db->fetch_count();
   echo '{"verify":'.$verify.',"buy":'.$buy.'}';
   exit;
}
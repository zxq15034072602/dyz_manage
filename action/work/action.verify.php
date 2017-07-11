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
if ($do == "input_verify_list") // 销售录入列表页面
{
    if (empty($store_id)||empty($user_roleid)) { // 销售录入需要用户有了所属门店后才能使用
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select b.id,b.uid,b.mid,u.name,b.addtime,b.status,g.dw,g.name as goodname,b.shuliang,u.roleid from rv_buy as b,rv_user as u,rv_goods as g where 1=1  and g.id=b.gid and b.uid=u.id and b.mid=?  ";
    $db->p_e($sql, array(
        $store_id,
        
    ));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("user_roleid",$user_roleid);//登陆用户的角色id
    $smt->assign("flag", "list_i_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_i_verify") { // 同意销售录入审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] !=$store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    
    $sql = "update rv_buy set status=1,endtime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        $cont=array("time"=>date('m月d日 H:i'),"msg"=>"你好，你的录入申请已经通过审核");
        $cont=json_encode($cont);
        to_msg(array('type'=>'verify_to_user','cont'=>$cont,'to'=>$_REQUEST[touid]));
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
}elseif ($do == "refuse_i_verify"){// 拒绝销售录入审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] != $store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_buy set status=2,endtime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        $cont=array("time"=>date('m月d日 H:i'),"msg"=>"你好，你的录入申请已被拒绝");
        $cont=json_encode($cont);
        to_msg(array('type'=>'verify_to_user','cont'=>$cont,'to'=>$_REQUEST[touid]));
        echo '{"code":"200","msg":"拒绝成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"拒绝失败！"}';
    exit();
    
}elseif ($do == "show_i_verify") { // 查看录入审核信息
    $vid = $_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select b.id,b.uid,b.mid,u.name,b.addtime,b.endtime,b.status,g.name as goodname,b.shuliang,m.name as mdname,g.dw from rv_buy as b,rv_user as u,rv_goods as g,rv_mendian as m where 1=1   and b.mid =m.id and g.id=b.gid and b.uid=u.id and b.id=?
";
    $db->p_e($sql, array(
        $vid
    ));
    $verify_info = $db->fetchRow();
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
    $sql = "select v.id,u.name,m.name as mdname,v.addtime,u.roleid,v.type,v.status,v.uid,v.mid from rv_verify as v,rv_user as u,rv_mendian as m where v.uid=u.id and v.mid=m.id  v.mid =?";
    $db->p_e($sql, array($store_isd));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("user_roleid",$user_roleid);//登陆用户的角色id
    $smt->assign("flag", "list_p_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_p_verify") { // 同意人员审核
    if (empty($_REQUEST['vid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] !=$store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_verify set status=1,updatetime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST[vid]
    ))) { // 如果同意成功则，sokect推送数据
        $sql="update rv_user set zz=? where id=?";
        $db->p_e($sql, array($_REQUEST[mid],$_REQUEST[touid]));
        $cont=array("time"=>date('m月d日 H:i'),"msg"=>"你好，你的加入申请已通过","store_id"=>$_REQUEST[mid]);
        $cont=json_encode($cont);
        to_msg(array('type'=>'verify_to_user','cont'=>$cont,'to'=>$_REQUEST[touid]));
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
}elseif ($do == "refuse_p_verify"){// 拒绝人员审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3 && $_REQUEST[mid] !=$store_id) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_verify set status=2,updatetime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        $cont=array("time"=>date('m月d日 H:i'),"msg"=>"你好，你的加入申请已被拒绝");
        $cont=json_encode($cont);
        to_msg(array('type'=>'verify_to_user','cont'=>$cont,'to'=>$_REQUEST[touid]));
        echo '{"code":"200","msg":"拒绝成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"拒绝失败！"}';
    exit();
    
}elseif ($do =="show_p_verify"){ //查看人员审核信息
    $vid = $_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql="select v.id,u.name,m.name as mdname,v.addtime,v.updatetime,v.status,v.type from rv_verify as v,rv_mendian as m,rv_user as u where v.mid=m.id and v.uid=u.id and v.id=?";
    $db->p_e($sql, array($vid));
    $verify_info=$db->fetchRow();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_info", $verify_info);
    $smt->assign("flag", "show_p_verify");
    $smt->display('verify_show.html');
    exit();
}
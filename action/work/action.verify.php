<?php
/**
 * 独一张管理app审核管理
 * @date: 2017年6月26日 下午12:05:33
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$store_id = $_REQUEST["store_id"]; // 所属门店Id
$uid = $_REQUEST['uid']; // 登陆用户id
$user_roleid = $_REQUEST['roleid']; // 用户权限id
if ($do == "input_verify_list") // 销售录入列表页面
{
    if (empty($store_id) || empty($uid)) { // 销售录入需要用户有了所属门店后才能使用
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select b.id,b.uid,b.mid,u.name,b.addtime,b.status,g.dw,g.name as goodname,b.shuliang,u.roleid from rv_buy as b,rv_user as u,rv_goods as g where 1=1  and g.id=b.gid and b.uid=u.id and b.mid=? and b.uid=? and b.status=0";
    $db->p_e($sql, array(
        $store_id,
        $uid
    ));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag", "list_i_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_i_verify") { // 同意销售录入审核
    if (empty($_REQUEST['bid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_by set status=1,endtime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST['bid']
    ))) { // 如果同意成功则，sokect推送数据
        
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
} elseif ($do == "show_i_verify") { // 查看录入审核信息
    $vid = $_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select b.id,b.uid,b.mid,u.name,b.addtime,b.endtime,b.status,g.name as goodname,b.shuliang,m.name as mdname from rv_buy as b,rv_user as u,rv_goods as g,rv_mendian as m where 1=1   and b.mid =m.id and g.id=b.gid and b.uid=u.id and b.id=?
";
    $db->p_e($sql, array(
        $vid
    ));
    $verify_info = $db->fetchRow();
    var_dump($verify_info);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag", "show_i_verify");
    $smt->display('verify_show.html');
    exit();
} elseif ($do == "people_verify_list") { // 人员审核列表
    if (empty($uid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select v.id,u.name,m.name as mdname,v.addtime,u.roleid,v.type,v.status from rv_verify as v,rv_user as u,rv_mendian as m where v.uid=u.id and v.mid=m.id and v.uid=?";
    $db->p_e($sql, array(
        $uid
    ));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag", "list_p_verify");
    $smt->display('verify_list.html');
    exit();
} elseif ($do == "agree_p_verify") { // 同意人员审核
    if (empty($_REQUEST['vid']) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_verify set status=1,updatetime=now() where id=?";
    if ($db->p_e($sql, array(
        $_REQUEST[vid]
    ))) { // 如果同意成功则，sokect推送数据
        echo '{"code":"200","msg":"审核成功，已通知' . $_REQUEST['name'] . '"}';
        exit();
    }
    echo '{"code":"500","msg":"审核失败！"}';
    exit();
}
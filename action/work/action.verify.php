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
if ($do == "input_verify_list") // 我的审核列表页面
{
    if (empty($store_id) || empty($uid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select b.id,b.uid,b.mid,u.name,b.addtime,b.status,g.dw,g.name as goodname,b.shuliang from rv_buy as b,rv_user as u,rv_goods as g where 1=1  and g.id=b.gid and b.uid=u.id and b.mid=? and b.uid=? and b.status=0";
    $db->p_e($sql, array(
        $store_id,
        $uid
    ));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag","list_i_verify");
    $smt->display('verify_list.html');
    exit();
}elseif($do == "show_i_verify"){//查看录入审核信息
    $vid=$_REQUEST['vid'];
    if (empty($vid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql="select b.id,b.uid,b.mid,u.name,b.addtime,b.endtime,b.status,g.name as goodname,b.shuliang,m.name as mdname from rv_buy as b,rv_user as u,rv_goods as g,rv_mendian as m where 1=1   and b.mid =m.id and g.id=b.gid and b.uid=u.id and b.id=?
";
    $db->p_e($sql, array($vid));
    $verify_info=$db->fetchRow();
    var_dump($verify_info);
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag","show_i_verify");
    $smt->display('verify_show.html');     
    exit(); 
}elseif($do =="people_verify_list"){//人员审核列表
    
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
    $smt->assign("flag","list_i_verify");
    $smt->display('verify_list.html');
    exit();
}
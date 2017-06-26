<?php
/**
* 独一张管理app人员管理
* @date: 2017年6月26日 上午11:21:31
* @author: fx
*/
if(!defined("CORE")) exit("error");
$store_id = $_REQUEST["store_id"];//所属门店Id
$uid = $_REQUEST['uid'];//登陆用户id
$user_roleid = $_REQUEST['roleid'];//用户权限id
if($do == "index"){//人员管理页面
    if(empty($store_id)||empty($uid)||empty($user_roleid)) {echo '{"code":"500","msg":"关键数据缺失！"}';exit();}
    $sql="select m.name as mname,u.id as uid,m.id as mid ,u.username,u.name,u.head_img, u.roleid from rv_mendian as m,rv_user as u where u.zz=m.id and m.id=? ORDER BY u.roleid ";
    $db->p_e($sql, array($store_id));
    $store_people_list=$db->fetchAll();
    if($store_people_list){
        $store_people_list[mname]=$store_people_list[0][mname];//获取门店名称
    }
    $smt=new Smarty();smarty_cfg($smt);
    $smt->assign("store_people_list",$store_people_list);
    $smt->display("people_list.html");
    exit(); 
}
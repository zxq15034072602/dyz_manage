<?php
/**
 * 独一张管理app人员管理
 * @date: 2017年6月26日 上午11:21:31
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$store_id = $_REQUEST["store_id"]; // 所属门店Id
$uid = $_REQUEST['uid']; // 登陆用户id
$user_roleid = $_REQUEST['roleid']; // 用户权限id
if ($do == "index") { // 人员管理页面
    if (empty($store_id) || empty($uid) || empty($user_roleid)) {
        echo '{"code":"500","msg":"关键数据缺失！"}';
        exit();
    }
    $sql = "select  u.id as uid,m.id as mid ,u.username,u.name,u.head_img, u.roleid from rv_mendian as m,rv_user as u where u.zz=m.id and m.id=? and u.roleid>5 and u.roleid<8 ORDER BY u.roleid ";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_people = $db->fetchAll();
    foreach($store_people as &$vv){
        if(stripos($vv['head_img'],"http://")===false && $vv['head_img']!=null){
            $vv['head_img']="../../image/header_picture/".$vv['head_img'];
        }
    }
    $sql = "select  u.id as uid,m.id as mid ,u.username,u.name,u.head_img, u.roleid from rv_mendian as m,rv_user as u where u.zz=m.id and m.id=? and u.roleid<6 ORDER BY u.roleid ";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_people_list = $db->fetchAll();
    foreach($store_people_list as &$v){
        if(stripos($v['head_img'],"http://")===false && $v['head_img']!=null){
            $v['head_img']="../../image/header_picture/".$v['head_img'];
        }
    }
    $sql = "select name from rv_mendian where 1=1 and id=?";
    $db->p_e($sql, array(
        $store_id
    ));
    $store_name = $db->fetch_count(); // 获取门店名字    
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("store_name", $store_name);
    $smt->assign("roleid", $user_roleid);
    $smt->assign("store_people_list", $store_people_list);
    $smt->assign("store_people", $store_people);
    $smt->display("people_list.html");
    exit();
} elseif ($do == 'delete_people') { // 删除店员
    if (empty($user_roleid) || empty($store_id) || empty($uid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    if ($user_roleid != 3) {
        echo '{"code":"500","msg":"对不起，你不是店长"}';
        exit();
    }
    $sql = "update rv_user set zz=0 where id=?";
    if ($db->p_e($sql, array(
        $uid
    ))) { // 如果删除成功则，sokect推送数据
        
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => "你好，你已经被店长请离团队"
        );
        $cont = json_encode($cont);
        to_msg(array(
            'type' => 'people_to_user',
            'cont' => $cont,
            'to' => $uid
        ));
        echo '{"code":"200","msg":"删除店员成功"}';
        exit();
    }
    echo '{"code":"500","msg":"删除店员成功"}';
    exit();
}
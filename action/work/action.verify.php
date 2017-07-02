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
if ($do == "input_verify_list") // 我的审核列表页面
{
    if (empty($store_id) || empty($uid)) {
        echo '{"code":"500","msg":"关键数据获取失败"}';
        exit();
    }
    $sql = "select * from rv_buy where 1=1 and mid=? and uid=? and status=0";
    $db->p_e($sql, array(
        $store_id,
        $uid
    ));
    $verify_list = $db->fetchAll();
    $smt = new Smarty();
    smarty_cfg($smt);
    $smt->assign("verify_list", $verify_list);
}
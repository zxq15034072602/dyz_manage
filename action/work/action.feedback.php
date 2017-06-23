<?php
/**
* 独一张管理app反馈
* @date: 2017年6月23日 上午10:01:46
* @author: fx
*/
if(!defined("CORE")) exit("error");
if($do == "index"){
    $uid= $_REQUEST['uid'];//用户id
    $content=$_REQUEST['content'];//用户反馈的内容
    if(empty($content)){echo '{"code":"500","msg":"反馈内容不能为空"}';exit;}
    $sql="select addtime from rv_feedback where 1=1 and uid=? order by id desc";
    $db->p_e($sql, array($uid));
    $last_time=strtotime($db->fetchRow()[addtime]);
    if($last_time){
        $cil=time()-$last_time;
        if($cil<10){echo '{"code":"500","msg":"对不起，不要频繁提交"}';exit;}
    }
    $addtime=date("Y-m-d H:i:s");
    if($db->insert(0, 2, "rv_feedback", array("uid=$uid","content='$content'","addtime='$addtime'"))){
        echo '{"code":"200","msg":"提交成功 ,我们会根据您的意见整改"}';exit;
    }
    echo '{"code":"500","msg":"提交失败"}';exit;
}
<?php
/**
* 独一张管理app用户操作
* @date: 2017年6月19日 上午11:31:55
* @author: fx
*/
if(!defined("CORE")) exit("error");
if($do == "login"){//用户登陆
    $user_type=$_REQUEST['type']??0;//所屬用戶 （0独一张，1食维健）
    $sql="select * from rv_user where 1=1 and username=? and password=? and type=? and status=1";
    $db->p_e($sql,array($_POST['user_name'],md5($_POST['password']),$user_type));
    $user=$db->fetchRow();
    if($user['id']>0){
        $sql="select action from rv_role where 1=1 and  id=?";
        $db->p_e($sql,array($user['roleid']));
        $user_role=explode(",", $db->fetchRow()[action]);//获取用户权限
        echo '{"code":"200","uid":"'.$user['id'].'","user_role":"'.json_encode($user_role).'","roleid":"'.$user['roleid'].'"}';//登陆成功返回code：200 用户id 与角色权限id
    }else{
        echo '{"code":"500","msg":"登陆信息有误"}';
    }
    exit;
}elseif($do == "register"){//用户注册
    
}

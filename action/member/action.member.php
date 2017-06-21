<?php
/**
* 独一张管理app用户操作
* @date: 2017年6月19日 上午11:31:55
* @author: fx
*/
if(!defined("CORE")) exit("error");
$user_type=$_REQUEST['type']??0;//所屬用戶 （0独一张，1食维健）
if($do == "login"){//用户登陆
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
    $mobile=$_POST['mobile'];//手机号
    $password=md5($_POST['password']);//密码
    $confirmpass=md5($_POST['confirmpass']);//确认密码
    $code=$_POST['code'];//验证码
    $verifycode=$_POST['verifycode'];//短信验证码
    if(empty($mobile)){echo '{"code":"500","msg":"手机不能为空"}'; exit;}
    if(empty($password)){echo '{"code":"500","msg":"密码不能为空"}'; exit;}
    if(empty($confirmpass)){echo '{"code":"500","msg":"确认密码不能为空"}'; exit;}
    if(empty($code)){echo '{"code":"500","msg":"验证码不能为空"}'; exit;}
    if($password !=$confirmpass){echo '{"code":"500","msg":"两次密码不一致"}'; exit;}
    if($code!=$verifycode){echo '{"code":"500","msg":"验证码不正确"}'; exit;}
    $sql="SELECT * FROM rv_user where username =? LIMIT 1";//判断用户是否存在
    $db->p_e($sql,array($mobile));
    if($db->fetchRow()){echo '{"code":"500","msg":"用户已存在"}'; exit;}
    $reg_uid=$db->insert(0, 2, "rv_user", array("username=$mobile","password=$password","roleid=5","mobile=$mobile","created_at=$date('Y-m-d h:i:s')","type=$user_type"));
    if($reg_uid){ 
        echo '{"code":"200","msg":"注册成功","uid":"'.$reg_uid.'"}';
    }else{
        echo '{"code":"500","msg":"注册失败"}';
    }
    exit;
}

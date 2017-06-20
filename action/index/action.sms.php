<?php
/**
* 独一张管理app短信验证码类
* @date: 2017年6月19日 下午3:35:02
* @author: fx
*/
if(!defined("CORE")) exit("error");
if($do == "getverifycode"){ //获取短信验证码
    $mobile = $_REQUEST['mobile'];//手机号
    $type=$_REQUEST['type']??0;
    if(empty($mobile)) {echo '{"code":"500","msg":"手机不能为空"}'; exit;}
    if(!ismobile($mobile)) {echo '{"code":"500","msg":"手机号码不正确"}';exit;}
    $verifycode = getverifycode();
    $content="【".($type == 0?'独一张' : '食维健')."】验证码为".$verifycode."（客服绝不会以任何理由索取此验证码，切勿告知他人），请在页面中输入以完成验证。";
    if(send_sms($mobile, $content)){
        echo '{"code":"200","msg":"短信已发送","verifycode":"'.$verifycode.'"}';
    }else{
        echo '{"code":"500","msg":"短信发送失败"}';
    }
    exit;
}

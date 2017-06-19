<?php
/**
* 独一张管理app用户操作
* @date: 2017年6月19日 上午11:31:55
* @author: fx
*/
if(!defined("CORE")) exit("error");
if($do == "login"){//用户登陆
    $user_type=$_REQUEST['type']??0;//所屬用戶 （0独一张，1食维健）
}

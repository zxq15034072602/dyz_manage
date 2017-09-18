<?php
if(!defined('CORE'))exit("error!");

if($do=='banner'){//广告图片
    $type=$_REQUEST['type'];
    $sql="select * from rv_advinfo where 1=1 and type=?";
    $db->p_e($sql, array(
        $type
    ));
    $banner=$db->fetchRow();
    $banner['img']=explode(",", $banner['img']);
    echo '{"code":"200","banner":'.json_encode($banner).'}';
    exit();
}












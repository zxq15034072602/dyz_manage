<?php
if (! defined('CORE')) exit("error!");

if($do=='index'){//下载页面
    $uid=$_REQUEST['uid'];
    $sql="select * from rv_file";
    $db->p_e($sql, array());
    $fileArr=$db->fetchAll();
 /*    foreach($fileArr as &$value){
        $value['status']=$value['status']??0;
        $sql="select status from rv_file_status where uid=? and fid=?";
        $db->p_e($sql, array(
            $uid,
            $value[id]
        ));
        $value['status']=$db->fetchRow();
    } */
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('fileArr',$fileArr);
    $smt->display('file_index.htm');
    exit();
}/* elseif($do=='xzstatus'){//下载or未下载
    $uid=$_REQUEST['uid'];
    $fid=$_REQUEST['fid'];
    $status=$_REQUEST['status'];
    if(!empty($uid) && !empty($fid)){
        if($status==0){
            if($db->update(0, 1, "rv_file_status", array(
                "status=0"
            ),array(
                "uid='$uid'",
                "fid='$fid'"
            ))){               
                echo '{"code":"500","msg":"您未下载","status":"0"}';
                exit();
            }
        }else{
        
            $sql="select status from rv_file_status where uid=? and fid=?";
            $db->p_e($sql, array(
                $uid,
                $fid
            ));
            $status=$db->fetchRow();
            if($status===false){
                if($db->insert(0, 2, "rv_file_status", array(
                    "uid='$uid'",
                    "fid='$fid'",
                    "status=1"
                ))){
                    echo '{"code":"200","msg":"您已下载","status":"1"}';
                    exit();
                }
            }else{
                if( $db->update(0, 1, "rv_file_status", array(
                    "status=1"
                ),array(
                    "uid='$uid'",
                    "fid='$fid'"
                ))){
                    echo '{"code":"200","msg":"您已下载","status":"1"}';
                    exit();                  
                }
            }
        }
    }
} */

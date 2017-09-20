<?php
if (! defined('CORE')) exit("error!");

if($do=='glist'){//产品列表页
    $sql="select g.* from rv_goods as g left join rv_type as t on g.fatherid=t.id where t.type=0";
    $db->p_e($sql, array());
    $gArr=$db->fetchAll();
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('gArr',$gArr);
    $smt->display('product_list.htm');
    exit();
}elseif($do=='gdetail'){//产品详情
    $gid=$_REQUEST['gid'];
    $sql="select * from rv_goods where id=?";
    $db->p_e($sql, array(
        $gid
    ));
    $pdetail=$db->fetchRow();
    $content=htmlspecialchars_decode($pdetail['content']);
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('pdetail',$pdetail);
    $smt->assign('content',$content);
    $smt->display('product_detail.htm');
    exit();
}































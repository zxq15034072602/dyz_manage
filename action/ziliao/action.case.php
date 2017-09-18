<?php
if (! defined('CORE')) exit("error!");

if($do=='clist'){//案例列表页
    $sql="select a.*,b.name as gname,c.name as mname from (rv_case as a left join rv_goods as b on a.gid=b.id)left join rv_mendian as c on a.mid=c.id ";
    $db->p_e($sql, array());
    $cArr=$db->fetchAll();
    foreach($cArr as $k=>&$v){     
        $cArr[$k]['img']=explode(",", $v['case_img']);    
    }
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('cArr',$cArr);
    $smt->display('case_list.htm');
    exit();
}elseif($do=='cdetail'){//案例详情
    $cid=$_REQUEST['cid'];
    $sql="select a.*,b.name as mname from rv_case as a left join rv_mendian as b on a.mid=b.id where a.id=?";
    $db->p_e($sql, array(
        $cid
    ));
    $cdetail=$db->fetchRow();   
    $cdetail['img']=explode(",", $cdetail['case_img']);
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('cdetail',$cdetail);
    $smt->display('case_detail.htm');
    exit();
}































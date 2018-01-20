<?php
if (! defined('CORE')) exit("error!");

if($do=='son_class'){//案例二级分类
    $class_id=$_REQUEST['parent_id'];
    $son_id=$_REQUEST['son_class_id'];
    if(empty($class_id)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    $sql="select * from rv_case_disease_class where fatherid=? order by id desc";
    $db->p_e($sql, array($class_id));
    $cArr=$db->fetchAll();
    echo '{"code":"200","son_class":'.json_encode($cArr).'}';
    exit();
}elseif($do=='cdetail'){//案例详情
    $cid=$_REQUEST['cid'];
    $sql="select * from rv_case where id=?";
    $db->p_e($sql, array(
        $cid
    ));
    $cdetail=$db->fetchRow();  
    $cdetail['mname']=$cdetail['mid'];
    $cdetail['img']=explode(",", $cdetail['case_img']);
    $cdetail['content']=htmlspecialchars_decode($cdetail['content']);
    $cdetail['process']=htmlspecialchars_decode($cdetail['process']);
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('cdetail',$cdetail);
    $smt->display('case_detail.htm');
    exit();
}

if($do=='case_class'){//全部分类页
    $sql="select * from rv_case_class ";
    $db->p_e($sql, array());
    $case_class=$db->fetchAll();
    foreach($case_class as &$v){
        $sql="select * from rv_case_disease_class where fatherid=?";
        $db->p_e($sql, array($v['id']));
        $v['son_class']=$db->fetchAll();
    }
    echo '{"code":"200","case_class":'.json_encode($case_class).'}';
}

if($do=='clist'){//列表页
    $id=$_REQUEST['parent_id'];
    if(empty($id)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    $sql1="select a.id,a.mid,a.case_img,a.content,a.yongyao,a.addtime,b.name as gname from rv_case as a left join rv_goods as b on a.gid=b.id where a.fatherid=? order by a.id desc";
    $db->p_e($sql1, array($id));
    $case=$db->fetchAll();
    foreach($case as $k=>&$vv){
        $vv['mname']=$vv['mid'];
        $vv['img']=explode(",", $vv['case_img']);
        $vv['content']=strip_tags(html_entity_decode($vv['content']));
        $vv['content']=strip_tags(html_entity_decode($vv['content']));
    }
    echo '{"code":"200","case":'.json_encode($case).'}';
    exit();
}


























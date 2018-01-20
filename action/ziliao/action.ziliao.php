<?php
if (! defined('CORE')) exit("error!");

if($do=='index1'){//资料模块首页
    //获取产品资料
    $sql="select a.*,b.gid  from rv_goods as a left join rv_product_detail as b on a.id=b.gid where a.id in(13,17,2,12)";
    $db->p_e($sql, array());
    $pArr=$db->fetchAll();   
    $pArr=array_slice($pArr,0,4);
    //获取营销秘籍
    $sql="select * from rv_video_type order by id desc";
    $db->p_e($sql, array());
    $mArr=$db->fetchAll();

    foreach($mArr as &$value){
        $value['kcnum']=$value['kcnum']??0;
        $sql="select count(*) as kcnum from rv_video_list where vid=?";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['kcnum']=$db->fetchRow();
        
        //点击数
        $value['learnnum']=$value['learnnum']??0;
        $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vid=? and status=1";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['learnnum']=$db->fetchRow();
    }
    $mArr=array_slice($mArr,0,3);

    //获取康复案例分类
    $sql="select * from rv_case_disease_class where status=1 order by id desc";
    $db->p_e($sql, array());
    $case_class=$db->fetchAll();
    
    echo '{"pArr":' . json_encode($pArr) . ',"mArr":' . json_encode($mArr) . ',"case_class":'.json_encode($case_class).'}';
    exit();
}












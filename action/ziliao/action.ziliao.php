<?php
if (! defined('CORE')) exit("error!");

if($do=='index'){//资料模块首页
    //获取产品资料
    $sql="select a.*,b.gid  from rv_goods as a left join rv_product_detail as b on a.id=b.gid";
    $db->p_e($sql, array());
    $pArr=$db->fetchAll();   
    $pArr=array_slice($pArr,0,4);
   // var_dump($pArr);
    //获取营销秘籍
    $sql="select * from rv_video_type";
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
    //var_dump($mArr);
    //获取康复案例

    $sql="select a.*,b.name as gname,c.name as mname from (rv_case as a left join rv_goods as b on a.gid=b.id)left join rv_mendian as c on a.mid=c.id ";
    $db->p_e($sql, array());
    $cArr=$db->fetchAll(); 
     foreach($cArr as $k=>&$v){
        $imgArr=explode(",", $v['case_img']);
        $cArr[$k]['img']=$imgArr['0'];
    }
    $cArr=array_slice($cArr,0,4);
    //模板
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('pArr',$pArr);
    $smt->assign('mArr',$mArr);
    $smt->assign('cArr',$cArr);
    $smt->display('ziliao_index.htm');
    exit();
}
















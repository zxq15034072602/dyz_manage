<?php
if (! defined('CORE')) exit("error!");

if($do=='video_type'){//视频分类页
    $sql="select * from rv_video_type";
    $db->p_e($sql, array());
    $mArr=$db->fetchAll();
    foreach($mArr as &$value){
        //课程数量
        $value['kcnum']=$value['kcnum']??0;
        $sql="select count(*) as kcnum from rv_video_list where vid=?";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['kcnum']=$db->fetchRow();
        //图文数量
        $value['twnum']=$value['twnum']??0;
        $sql="select count(*) as twnum from rv_article_list where vid=?";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['twnum']=$db->fetchRow();
        
        $sql="select id from rv_video_list where vid=?";
        $db->p_e($sql, array(
            $value[id]
        ));
        $vidArr=$db->fetchAll();
        foreach($vidArr as &$vv){
            
        }
        //点击数
        $value['learnnum']=$value['learnnum']??0;
        $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vid=? and status=1";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['learnnum']=$db->fetchRow();
    }
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('mArr',$mArr);
    $smt->display('video_type.htm');
    exit();
}elseif($do=='video_list'){//视频列表页
    $vid=$_REQUEST['vid'];
    $sql="select a.*,b.name,b.content,b.video_img,b.type from rv_video_list as a left join rv_video_type as b on a.vid=b.id where vid=?";
    $db->p_e($sql, array(
        $vid
    ));
    $videolist=$db->fetchAll();
    foreach($videolist as &$value){
        //点击数
        $value['learnnum']=$value['learnnum']??0;
        $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vid=? and status=1";
        $db->p_e($sql, array(
            $value[vid]
        ));
        $value['learnnum']=$db->fetchRow();
    }
    $sql="select count(*) as count from rv_video_list where vid=?";
    $db->p_e($sql, array(
        $vid
    ));
    $kcnum=$db->fetch_count();

    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('videolist',$videolist);
    $smt->assign('kcnum',$kcnum);
    $smt->display('video_list.htm');
    exit();
}elseif($do=='videodetail1'){//秘籍详情页
    $vid=$_REQUEST['vid'];
    if($vid){
        $sql="select a.title,a.url,a.vid,b.content,b.type from rv_video_list as a left join rv_video_type as b on a.vid=b.id where a.id=?";
        $db->p_e($sql, array(
            $vid
        ));
        $videoArr=$db->fetchRow();
        
        //相关视频
        $sql="select a.* from rv_video_list as a left join rv_video_type as b on a.vid=b.id where 1=1 and vid=?";
        $db->p_e($sql, array(
            $videoArr['vid']
        ));
        $xgvideo=$db->fetchAll();
        foreach($xgvideo as &$value){
            $value['learnnum']=$value['learnnum']??0;
            $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vvid=? and status=1";
            $db->p_e($sql, array(
                $value[id]
            ));
            $value['learnnum']=$db->fetchRow();
        }
        echo '{"code":"200","videoArr":' . json_encode($videoArr) . ',"xgvideo":' . json_encode($xgvideo) . '}';
        exit();
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    
}elseif($do=='videodetail'){//秘籍详情页
    $vid=$_REQUEST['vid'];
    $sql="select a.title,a.url,a.vid,b.content,b.type from rv_video_list as a left join rv_video_type as b on a.vid=b.id where a.id=?";
    $db->p_e($sql, array(
        $vid
    ));
    $videoArr=$db->fetchRow();
    
    //相关视频
    $sql="select a.* from rv_video_list as a left join rv_video_type as b on a.vid=b.id where 1=1 and vid=?";
    $db->p_e($sql, array(
        $videoArr['vid']
    ));
    $xgvideo=$db->fetchAll();
    foreach($xgvideo as &$value){
        $value['learnnum']=$value['learnnum']??0;
        $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vvid=? and status=1";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['learnnum']=$db->fetchRow();
    }
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('videoArr',$videoArr);
    $smt->assign('xgvideo',$xgvideo);
    $smt->display('video_detail.htm');
    exit();
}elseif($do=='article_list'){//图文列表
    $vid=$_REQUEST['vid'];
    $sql="select a.*,b.name,b.content,b.video_img,b.type from rv_article_list as a left join rv_video_type as b on a.vid=b.id where vid=?";
    $db->p_e($sql, array(
        $vid
    ));
    $articlelist=$db->fetchAll();
    foreach($articlelist as &$value){
        //点击数
        $value['learnnum']=$value['learnnum']??0;
        $sql="select count(*) as learnnum from rv_video_learn where 1=1 and vvid=? and status=1";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['learnnum']=$db->fetchRow();
        //课程数量
        $value['kcnum']=$value['kcnum']??0;
        $sql="select count(*) as kcnum from rv_article_list where vid=?";
        $db->p_e($sql, array(
            $value[id]
        ));
        $value['kcnum']=$db->fetchRow();
        
    }
    $sql="select count(*) as count from rv_article_list where vid=?";
    $db->p_e($sql, array(
        $vid
    ));
    $kcnum=$db->fetch_count();
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('articlelist',$articlelist);
    $smt->assign('kcnum',$kcnum);
    $smt->display('article_list.htm');
    exit();
}elseif($do=='article_detail'){//图文详情页
    $vid=$_REQUEST['aid'];
    $sql="select * from rv_article_list where id=?";
    $db->p_e($sql, array(
        $vid
    ));
    $aArr=$db->fetchRow();
    $content=htmlspecialchars_decode($aArr['content']);
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('aArr',$aArr);
    $smt->assign('content',$content);
    $smt->display('article_detail.htm');
}elseif($do=='learnNum'){
    $uid=$_REQUEST['uid'];
    $vid=$_REQUEST['vid'];//视频分类id
    $vvid=$_REQUEST['vvid'];//视频id
    $sql="select uid from rv_video_learn where uid=? and vvid=? and vid=?";
    $db->p_e($sql, array(
        $uid,
        $vvid,
        $vid
    ));
    //查询该用户是否学习
    $userid=$db->fetchRow()[uid];
    if($userid){
        echo '{"code":"500","msg":"此用户已经学习过该视频"}';
        exit();
    }else{
        $last_id=$db->insert(0, 2, "rv_video_learn", array(
            "vid='$vid'",
            "vvid='$vvid'",
            "uid='$uid'",
            "status=1"
        ));
        if($last_id){
            echo '{"code":"200","msg":"加入学习成功"}';
            exit();
        }else{
            echo '{"code":"500","msg":"失败"}';
            exit();
        }
    }
}

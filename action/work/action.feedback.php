<?php
/**
 * 独一张管理app反馈
 * @date: 2017年6月23日 上午10:01:46
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
if ($do == "question") {//提问
    $uid = $_REQUEST['uid']; // 用户id
    $content = $_REQUEST['content']; // 用户反馈的内容
    $type=$_REQUEST['type'];//用户反馈的类型   
    if (empty($content)) {
        echo '{"code":"500","msg":"反馈内容不能为空"}';
        exit();
    }
    
    $sql = "select addtime from rv_feedback where 1=1 and uid=? order by id desc";
    $db->p_e($sql, array(
        $uid
    ));
    $last_time = strtotime($db->fetchRow()[addtime]);
   
    if ($last_time) {
        $cil = time() - $last_time;
        if ($cil < 10) {
            echo '{"code":"500","msg":"对不起，不要频繁提交"}';
            exit();
        }
    }
   
    $addtime = date("Y-m-d H:i:s");
    if ($db->insert(0, 2, "rv_feedback", array(
        "uid=$uid",
        "content='$content'",
        "addtime='$addtime'",
        "type=$type"
    ))) {
        echo '{"code":"200","msg":"问题发布成功 "}';
        exit();
    }
    echo '{"code":"500","msg":"发布失败"}';
    exit();
}elseif($do == "index"){//问答专区首页
    $uid=$_REQUEST['uid'];
    $pagenum = 5;
    $page = $_REQUEST['page'] ?? 1;
    $page = ($page - 1) * $pagenum;
    if($uid){       
        $sql="select f.id,f.type,f.uid,f.content,f.addtime,GROUP_CONCAT(an.id) as answerid,u.name from (rv_feedback as f left join rv_answer as an on f.id=an.qid )left join rv_user as u on f.uid=u.id GROUP BY f.id ORDER BY f.addtime desc limit " . $page . "," . $pagenum;
        $db->p_e($sql, array());
        //问题答案详情
        $question=$db->fetchAll();  
        $total = $db->fetch_count();
        $total = ceil($total / $pagenum);
        foreach ($question as &$value){
            $value['answerid']=$value['answerid']??0;
            $sql="select a.id,a.uid,a.qid,a.content,a.addtime,count(g.id) as count,u.name from (rv_answer as a LEFT JOIN rv_answer_greet as g on a.id=g.aid)left join rv_user as u on a.uid=u.id where a.id in ($value[answerid]) GROUP BY id ORDER BY count desc LIMIT 1";
            $db->p_e($sql,array());
            $value['answer']=$db->fetchRow();
            
            $value['guanzhu']=$value['guanzhu']??0;
            $sql="select status from rv_guanzhu where uid=$uid and qid=$value[id] group by id";
            $db->p_e($sql, array());
            $value['guanzhu']=$db->fetchRow();
            
            $value['guanzhunum']=$value['guanzhu']??0;
            $sql="select count(id) as count from rv_guanzhu where qid=$value[id] and status=1 order by id";
            $db->p_e($sql, array());
            $value['guanzhunum']=$db->fetchRow();
           
            $value['answernum']=$value['answernum']??0;
            $sql="select count(*) as hdnum from rv_answer where qid=$value[id]";
            $db->p_e($sql, array());
            $value['answernum']=$db->fetchRow();
        }
        $smt=new Smarty();
        smarty_cfg($smt);
        $smt->assign('question',$question);
        $smt->assign('total', $total);
        $smt->display('feedback_show.htm');
        exit;
  }
}elseif($do=='qlist'){//问题详情页
    $uid=$_REQUEST['uid'];
    $qid=$_REQUEST['qid'];
    $sql="select a.*,b.name from rv_feedback as a left join rv_user as b on a.uid=b.id where a.id=?";
    $db->p_e($sql, array(
        $qid
    ));
    //问题
    $qArr=$db->fetchAll();

    $sql1="select a.id as aid,a.uid,a.qid,a.content,a.addtime,b.name from rv_answer as a left join rv_user as b on a.uid=b.id where qid=?";
    $db->p_e($sql1, array(
        $qid
    ));
    //答案
    $aArr=$db->fetchAll();
    
    foreach($aArr as &$value){
       //回复内容
       $sql="select a.*,b.name from rv_answer_reply as a left join rv_user as b on a.uid=b.id where aid=$value[aid]";    
       $db->p_e($sql, array());
       $value['reply']=$db->fetchAll();
       //赞同状态
       $value['greet']=$value['greet']??0;
       $sql="select status from rv_answer_greet where 1=1 and  uid=$uid and aid=$value[aid]";
       $db->p_e($sql, array());
       $value['greet']=$db->fetchRow();
       //赞同数
       $value['agreenum']=$value['agreenum']??0;
       $sql="select count(*) as count from rv_answer_greet where aid=$value[aid] and status=1";
       $db->p_e($sql, array());
       $value['ztnum']=$db->fetchRow();
       //回复的数量
       $value['replynum']=$value['replynum']??0;
       $sql="select count(aid) as count from rv_answer_reply where aid=$value[aid]";
       $db->p_e($sql, array());
       $value['replynum']=$db->fetchRow();
    }
    //模板
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('qArr',$qArr);
    $smt->assign('aArr',$aArr);
    $smt->display('feedback_list.htm');
    exit;
  
}elseif($do=='rlist'){//回复列表页
    $uid=$_REQUEST['uid'];
    $aid=$_REQUEST['aid'];
    $sql="select r.*,u.name,u.head_img from rv_answer_reply as r left join rv_user as u on r.uid=u.id where 1=1 and aid=?";
    $db->p_e($sql, array(
        $aid
    ));
    //回复列表
    $rArr=$db->fetchAll();
    //模板
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('rArr',$rArr);
    $smt->display('feedback_reply_list.htm');
    exit();
}elseif($do=='reply'){//回答
    $uid=$_REQUEST['uid'];
    $qid=$_REQUEST['qid'];
    $q_uid=$_REQUEST['q_uid'];
    $content=$_REQUEST['content'];    
    //获取用户名
    $sql="select name from rv_user where 1=1 and id=?";
    $db->p_e($sql, array(
        $uid
        
    ));
    $name=$db->fetchRow();
    $uname=$name['name'];
    
    if(empty($content)){
        echo '{"code":"500","msg":"回答内容不能为空"}';
        exit();
    }
    /*$sql="select addtime from rv_answer where 1=1 and uid=? order by id desc";
    $db->p_e($sql, array(
        $uid
    ));
    $last_time = strtotime($db->fetchRow()[addtime]);
    if ($last_time) {
        $cil = time() - $last_time;
        if ($cil < 10) {
            echo '{"code":"500","msg":"对不起，不要频繁提交"}';
            exit();
        }
    }*/
    $addtime = date("Y-m-d H:i:s");
    $last_id=$db->insert(0, 2, "rv_answer", array(
        "uid=$uid",
        "content='$content'",
        "addtime='$addtime'",
        "qid='$qid'"
    )); 

    if ($last_id) {
        $cont = array(
            "time" => date('m月d日 H:i'),
            "msg" => $uname."回答了您的问题"
        );
        $cont = json_encode($cont);
        to_msg(array('type'=>"reply_to_msg","cont"=>$cont,"to"=>$q_uid));
        echo '{"code":"200","msg":"回答成功","uname":"'.$uname.'","aid":"'.$last_id.'"}';//添加推送消息
        exit();
    }
    echo '{"code":"500","msg":"提交失败"}';
    exit();    
}elseif($do=='review'){//回复
    $uid=$_REQUEST['uid'];
    $aid=$_REQUEST['aid'];
    $content=$_REQUEST['content'];
 
    if(empty($content)){
        echo '{"code":"500","msg":"回复内容不能为空"}';
        exit();
    }
   /* $sql="select addtime from rv_answer_reply where 1=1 and uid=? order by id desc";
    $db->p_e($sql, array(
        $uid
    ));
    $last_time = strtotime($db->fetchRow()[addtime]);
    if ($last_time) {
        $cil = time() - $last_time;
        if ($cil < 10) {
            echo '{"code":"500","msg":"对不起，不要频繁提交"}';
            exit();
        }
    }
    */
    $addtime = date("Y-m-d H:i:s");
    if($db->insert(0, 2, "rv_answer_reply", array(
        "uid=$uid",
        "content='$content'",
        "addtime='$addtime'",
        "aid='$aid'"
    ))){
        echo '{"code":"200","msg":"回复成功"}';
        exit();
    }else{
        echo '{"code":"500","msg":"提交失败"}';
        exit();
    }
}elseif($do=='guanzhu'){//关注or取消关注
    $uid=$_REQUEST['uid'];
    $qid=$_REQUEST['qid'];
    $status=$_REQUEST['status'];
    if(!empty($uid) && !empty($qid)){
        if($status==0){
            if($db->update(0, 1, "rv_guanzhu", array(
                "status=0"
            ),array(
                "uid='$uid'",
                "qid='$qid'"
            ))){               
                echo '{"code":"500","msg":"您已取消关注","status":"0"}';//推送消息
                exit();
            }
        }else{
        
            $sql="select status from rv_guanzhu where uid=? and qid=?";
            $db->p_e($sql, array(
                $uid,
                $qid
            ));
            $status=$db->fetchRow();
            if($status===false){
                if($db->insert(0, 2, "rv_guanzhu", array(
                    "uid='$uid'",
                    "qid='$qid'",
                    "status=1"
                ))){
                    echo '{"code":"200","msg":"您已关注","status":"1"}';//推送消息
                    exit();
                }
            }else{
                if( $db->update(0, 1, "rv_guanzhu", array(
                    "status=1"
                ),array(
                    "uid='$uid'",
                    "qid='$qid'"
                ))){
                    echo '{"code":"200","msg":"您已关注","status":"1"}';//推送消息
                    exit();                  
                }
            }
        }
    }
}elseif($do=='agree'){//赞同or不赞同
    $uid=$_REQUEST['uid'];
    $aid=$_REQUEST['aid'];
    $status=$_REQUEST['status'];
    if(!empty($uid) && !empty($aid)){
        if($status==0){
            if($db->update(0, 1, "rv_answer_greet", array(
                "status=0"
            ),array(
                "uid='$uid'",
                "aid='$aid'"
            ))){
                
                echo '{"code":"500","msg":"您已取消赞同","status":"0"}';
                exit();
            }
        }else{
            $sql="select status from rv_answer_greet where uid=? and aid=?";
            $db->p_e($sql, array(
                $uid,
                $aid
            ));
            $status=$db->fetchRow();
            
            if($status===false){
                if($db->insert(0, 2, "rv_answer_greet", array(
                    "uid='$uid'",
                    "aid='$aid'",
                    "status=1"
                ))){
                    echo '{"code":"200","msg":"您已赞同","status":"1"}';
                    exit();
                }
            }else{
                if( $db->update(0, 1, "rv_answer_greet", array(
                    "status=1"
                ),array(
                    "uid='$uid'",
                    "aid='$aid'"
                ))){
                    echo '{"code":"200","msg":"您已赞同","status":"1"}';
                    exit();
                }
            }
        }
    }
}elseif($do=='delquestion'){//删除问题
    $uid=$_REQUEST['uid'];
    $qid=$_REQUEST['qid'];
   if(!empty($uid) && !empty($qid)){
       $sql="select uid from rv_feedback where id=?";
       $db->p_e($sql, array(
           $qid
       ));
       $uidArr=$db->fetchRow();
       if($uid==$uidArr['uid']){
           if($db->delete(0, 1, "rv_feedback",array(
               "id='$qid'"
           ))){
              echo '{"code":"200","msg":"删除成功"}';
              exit();
           }
       }else{
           echo '{"code":"500","msg":"不好意思,您没有权限删除"}';
           exit();
       }
   } 
}elseif($do=='delanswer'){//删除答案
    $uid=$_REQUEST['uid'];
    $aid=$_REQUEST['aid'];
    if(!empty($uid) && !empty($aid)){
        $sql="select uid from rv_answer where id=?";
        $db->p_e($sql, array(
            $aid
        ));
        $uidArr=$db->fetchRow();
        if($uid==$uidArr['uid']){
            if($db->delete(0, 1, "rv_answer",array(
                "id='$aid'"
            ))){
                echo '{"code":"200","msg":"删除成功"}';
                exit();
            }
        }else{
            echo '{"code":"500","msg":"不好意思,您没有权限删除"}';
            exit();
        }
    }
}elseif($do=='deletereply'){//删除回复
    $uid=$_REQUEST['uid'];
    $rid=$_REQUEST['rid'];
    if(!empty($uid) && !empty($rid)){
        $sql="select uid from rv_answer_reply where id=?";
        $db->p_e($sql, array(
            $rid
        ));
        $uidArr=$db->fetchRow();
        if($uid==$uidArr['uid']){
            if($db->delete(0, 1, "rv_answer_reply",array(
                "id='$rid'"
            ))){
                echo '{"code":"200","msg":"删除回复成功"}';
                exit();
            }
        }else{
            echo '{"code":"500","msg":"不好意思,您没有权限删除"}';
            exit();
        }
    }
}

<?php 
if (! defined('CORE'))
    exit("error!"); // 检查某常量是否存在。
    $save_url = "http://static.duyiwang.cn/amr/";
    $dir_name = "E:/apptupian/amr/";
    //$save_url = "http://192.168.1.143/amr/";
    //$dir_name = "G:/wamp64/www/amr/";
    $file_url="";
    if($_FILES['file']['size']>5242880){
        echo '{"code":"404","msg":"文件过大"}';
        exit();
    }
    if(is_uploaded_file($_FILES['file']['tmp_name'])){
        // 新文件名
        $new_file_name = date("YmdHis") . '_' . mt_rand(10000, 99999) . '.amr';
        $file_url = $save_url . $new_file_name;
        $file_path = $dir_name . $new_file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }
   
if($do == "send_voice"){//发送语音（单聊）
    $uid = $_POST['uid'];
    $toid = $_POST['toid'];
    $amr = $file_url;
    $nowtime = date('m月d日 H:i');
    $local_url=$_REQUEST["local_url"];
    $time_length=$_REQUEST['time_length'];
    $send_length=$_REQUEST['send_length'];
    $cont = array('lx' =>2,'nr' => $file_url, 'time' => date('m月d日 H:i'), 'time_length'=>$time_length,'send_length'=>$send_length);
    $cont = json_encode($cont);
    $sql = "insert into rv_xiaoxi(uid,toid,content,local_url,time_length,send_length,content_type,type,status,is_du) values(?,?,?,?,?,?,2,1,1,1)";
    if ($db->p_e($sql, array( $uid,$toid,$amr,$local_url,$time_length,$send_length))) {
        $sql = "insert into rv_xiaoxi(uid,toid,content,local_url,time_length,send_length,content_type,type,status,is_du) values(?,?,?,?,?,?,2,2,1,0)";
        if ($db->p_e($sql, array($toid,$uid,$amr,$local_url,$time_length,$send_length))) {
            to_msg(array('type' => 'sixin_to','cont' => $cont,'to' => $toid));
            echo '{"code":"200","url":"' . $file_url . '","time":"' . $nowtime . '"}';
        } else {
            echo '{"code":"404"}';
        }
    } else {
        echo '{"code":"404"}';
    }
    exit();
}elseif ($do == "send_voice_groups"){//发送语音群聊
    $uid = $_REQUEST['uid'];
    $gid = $_REQUEST['gid'];
    $groups_room = $_REQUEST['groups_room'];
    $amr = $file_url;
    $nowtime = date('m月d日 H:i');
    $local_url=$_REQUEST["local_url"];
    $time_length=$_REQUEST['time_length'];
    $send_length=$_REQUEST['send_length'];
    $send_name = $db->select(0, 1, "rv_user", "name", array(
        "id=$uid"
       
    ), "gu_id desc");
    $cont = array(
        'lx' =>2,
        'nr' => $file_url,
        'time' => date('m月d日 H:i'),
        "from_id" => $uid,
        "send_name" =>$send_name[gu_group_nick],
        'time_length'=>$time_length,
        'send_length'=>$send_length
    );
    $cont = json_encode($cont);
    $sql = "insert into rv_groups_xiaoxi (from_uid,togid,content,local_url,time_length,send_length,content_type) values(?,?,?,?,?,?,2)";
    if ($db->p_e($sql, array($uid,$gid, $amr,$local_url,$time_length,$send_length))) { // 成功后像socket 服务端推送数据
        to_msg(array('type' => 'sixin_to_groups','cont' => $cont,'to' => $groups_room)); // 推送消息
        echo '{"code":"200","url":"' . $file_url . '","time":"' . $nowtime . '","send_name":"' . $send_name[name] . '"}';
        exit();
    }
    echo '{"code":"500"}';
    exit();
}

?>
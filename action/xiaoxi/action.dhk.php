<?php
if (! defined('CORE'))
    exit("error!");
// 对话框
if ($do == "dhk") {
    $uid = $_REQUEST['uid'];
    $id = $_REQUEST['id'];
    // 变已读
    $sql = "update rv_xiaoxi set is_du=1 where 1=1 and uid=? and toid=?";
    $db->p_e($sql, array(
        $uid,
        $id
    ));
    // 获取消息
    $sql = "select *,date_format(addtime,'%m月%d日 %H:%i') as addtime1 from rv_xiaoxi where 1=1 and uid=? and toid=? and status=1";
    $db->p_e($sql, array(
        $uid,
        $id
    ));
    $dh = $db->fetchAll();
    foreach ($dh as &$k) {
        $k['wo'] = user($k['uid']);
        if(stripos($k['wo']['head_img'],"http://")===false && $k['wo']['head_img']!=null){
            $k['wo']['head_img']="../../image/header_picture/".$k['wo']['head_img'];
        }
        $k['to'] = user($k['toid']);
        if(stripos($k['to']['head_img'],"http://")===false && $k['to']['head_img']!=null){
            $k['to']['head_img']="../../image/header_picture/".$k['to']['head_img'];
        }
    }
    // 模版
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign('dh', $dh);
    $smt->display('dhk.html');
    exit();
}
// 对话人
if ($do == "dhr") {
    $id = $_REQUEST['id'];
    $uid = $_REQUEST['uid'];
    $sql = "select name from rv_user where 1=1 and id=?";
    $db->p_e($sql, array(
        $id
    ));
    $to_name = $db->fetch_count();
    $sql = "select name from rv_user where 1=1 and id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $wo_name = $db->fetch_count();
    $sql = "select is_openwin from rv_user where 1=1 and id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $is_openwin = $db->fetch_count();
    if (! $is_openwin) {
        if ($db->update(0, 1, "rv_user", array(
            "is_openwin=1"
        ), array(
            "id=$uid"
        ))) {
            $is_openwin = 1;
        }
    }
    echo '{"code":"200","to_name":"' . $to_name . '","wo_name":"' . $wo_name . '"}';
    exit();
}

// 发私信
if ($do == "fasixin") {
    $uid = $_POST['uid'];
    $toid = $_POST['toid'];
    $txt = $_POST['txt'];
    $nowtime = date('m月d日 H:i');
    $sql="select * from rv_user where id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $head=$db->fetchRow();
    $head_img=$head['head_img'];
    $send_name=$head['name'];
    $cont = array(
        'lx' => 0,
        'nr' => $txt,
        'time' => date('m月d日 H:i'),
        "toid" => $uid,
        "head_img"=>$head_img
    );
    $cont = json_encode($cont);
    $sql = "insert into rv_xiaoxi(uid,toid,content,content_type,type,status,is_du) values(?,?,?,0,1,1,1)";
    if ($db->p_e($sql, array(
        $uid,
        $toid,
        $txt
    ))) {
        $sql = "insert into rv_xiaoxi(uid,toid,content,content_type,type,status,is_du) values(?,?,?,0,2,1,0)";
        if ($db->p_e($sql, array(
            $toid,
            $uid,
            $txt
        ))) {
            to_msg(array(
                'type' => 'sixin_to',
                'cont' => $cont,
                'to' => $toid
            ));
            echo '{"code":"200","time":"' . $nowtime . '"}';
        } else {
            echo '{"code":"404"}';
        }
    } else {
        echo '{"code":"404"}';
    }
    exit();
    
} else if ($do == "get_user_openwin") { // 获取用户窗口是否打开
    $uid = $_REQUEST[uid];
    $sql = "select is_openwin from rv_user where id=?";
    $db->p_e($sql, array(
        $uid
    ));
    $is_openwin = $db->fetch_count();
    echo '{"is_openwin":"' . $is_openwin . '"}';
    exit();
}else if ($do == "update_openwin") { // 更新用户窗口状态
    $uid = $_POST['uid'];
    $db->update(0, 1, "rv_user", array(
        "is_openwin=0"
    ), array(
        "id=$uid"
    ));
}
<?php
/**
 * 独一张管理app用户操作
 * @date: 2017年6月19日 上午11:31:55
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$user_type = $_REQUEST['type'] ?? 0; // 所屬用戶 （0独一张，1食维健）
if ($do == "uerinfo") { // 用户中心个人信息
    $uid = $_REQUEST['uid']; // 用户id
    $user = user($uid); // 获取用户相关信息
    if ($_REQUEST['dosubmit']) { // 如果是提交修改用户资料
        if (empty($_REQUEST['name'])) {
            echo '{"code":"500","msg":"姓名不能为空"}';
            exit();
        }
        if (empty($_REQUEST['age'])) {
            echo '{"code":"500","msg":"年龄不能为空"}';
            exit();
        }
        if (empty($_REQUEST['roleid'])) {
            echo '{"code":"500","msg":"程序错误"}';
            exit();
        }
        
        if ($user['stroe_id'] != $_REQUEST['stroe_id']) { // 如果用户修改了所属门店，则插入未审核人员记录
            $sql = "select * from rv_verify where 1=1 and uid=?  and type=0 and status=0";
            $db->p_e($sql, array(
                $uid
            ));
            if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交门店变更申请
                echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                exit();
            }
            $sql = "insert into rv_verify (uid,mid,type,addtime,status) VAULES (?,?,?,?,?)";
            $arr = array(
                $uid,
                $_REQUEST['stroe_id'],
                0,
                date("Y-m-d H:i:s"),
                0
            );
            if ($db->p_e($sql, $arr)) {
                $db->update(0, 1, "rv_user", array(
                    "name=$_REQUEST[name]",
                    "sex=$_REQUEST[sex]",
                    "age=$_REQUEST[age]"
                ), "id=$uid");
                echo '{"code":"200","msg":"更换门店提交成功！请等待店长审核！"}';
                exit();
            }
            echo '{"code":"500","msg":"更换门店失败！"}';
            exit();
        }
        if ($user['roleid'] != $_REQUEST['roleid']) { // 如果用户修改了职位，则插入未审核店长记录
            if (empty($_REQUEST[stroe_id])) { // 如果未选择门店，则不能申请店长
                echo '{"code":"500","msg":"请先选择所属门店"}';
                exit();
            }
            $sql = "select * from rv_verify where 1=1 and uid=?  and type=1 and status=0";
            $db->p_e($sql, array(
                $uid
            ));
            if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                exit();
            }
            $sql = "insert into rv_verify (uid,mid,type,addtime,status) VAULES (?,?,?,?,?)";
            $arr = array(
                $uid,
                $_REQUEST['stroe_id'],
                1,
                date("Y-m-d H:i:s"),
                0
            );
            if ($db->p_e($sql, $arr)) {
                $db->update(0, 1, "rv_user", array(
                    "name=$_REQUEST[name]",
                    "sex=$_REQUEST[sex]",
                    "age=$_REQUEST[age]"
                ), "id=$uid");
                echo '{"code":"200","msg":"申请成为店长提交成功！请等待审核！"}';
                exit();
            }
            echo '{"code":"500","msg":"申请店长失败！"}';
            exit();
        }
        if ($db->update(0, 1, "rv_user", array(
            "name=$_REQUEST[name]",
            "sex=$_REQUEST[sex]",
            "age=$_REQUEST[age]"
        ), "id=$uid")) {
            echo '{"code":"200","msg":"修改成功"}';
            exit();
        }
        echo '{"code":"500","msg":"修改失败"}';
        exit();
    }
    $stroe_list = $db->select(0, 0, "rv_mendian"); // 获取所有门店
    echo '{"userinfo":' . json_encode($user) . ',"stroe_list":' . json_encode($stroe_list) . '}';
    exit();
} elseif ($do == "login") { // 用户登陆
    $sql = "select * from rv_user where 1=1 and username=? and password=? and type=? and status=1";
    $db->p_e($sql, array(
        $_REQUEST['user_name'],
        md5($_REQUEST['password']),
        $user_type
    ));
    $user = $db->fetchRow();
    if ($user['id'] > 0) {
        $sql = "select action from rv_role where 1=1 and  id=?";
        $db->p_e($sql, array(
            $user['roleid']
        ));
        $roles = $db->fetchRow();
        $user_role = explode(",", $roles[action]); // 获取用户权限
        echo '{"code":"200","uid":"' . $user['id'] . '","user_role":' . json_encode($user_role) . ',"roleid":"' . $user['roleid'] . '","name":"' . $user['name'] . '","mobile":"' . $user['mobile'] . '"}'; // 登陆成功返回code：200 用户id 与角色权限id
        exit();
    }
    echo '{"code":"500","msg":"登陆信息有误"}';
    exit();
} elseif ($do == "register") { // 用户注册
    $mobile = $_POST['mobile']; // 手机号
    $password = md5($_POST['password']); // 密码
    $confirmpass = md5($_POST['confirmpass']); // 确认密码
    $code = $_POST['code']; // 验证码
    $verifycode = $_POST['verifycode']; // 短信验证码
    $addtime = date('Y-m-d h:i:s');
    if (empty($mobile)) {
        echo '{"code":"500","msg":"手机不能为空"}';
        exit();
    }
    if (empty($password)) {
        echo '{"code":"500","msg":"密码不能为空"}';
        exit();
    }
    if (empty($confirmpass)) {
        echo '{"code":"500","msg":"确认密码不能为空"}';
        exit();
    }
    if (empty($code)) {
        echo '{"code":"500","msg":"验证码不能为空"}';
        exit();
    }
    if ($password != $confirmpass) {
        echo '{"code":"500","msg":"两次密码不一致"}';
        exit();
    }
    if ($code != $verifycode) {
        echo '{"code":"500","msg":"验证码不正确"}';
        exit();
    }
    $sql = "SELECT * FROM rv_user where username =?  LIMIT 1"; // 判断用户是否存在
    $db->p_e($sql, array(
        $mobile
    ));
    $already_user = $db->fetchRow();
    if ($already_user) {
        if ($$already_user[type]) {
            echo '{"code":"500","msg":"此手机已在食维健注册过，请勿换另一个手机注册"}';
            exit();
        }
        echo '{"code":"500","msg":"此手机已在独一张注册过，请勿换另一个手机注册"}';
        exit();
    }
    $reg_uid = $db->insert(0, 2, "rv_user", array(
        "username='$mobile'",
        "password='$password'",
        "roleid=5",
        "mobile=$mobile",
        "created_at='$addtime'",
        "type=$user_type"
    ));
    if ($reg_uid) {
        echo '{"code":"200","msg":"注册成功","uid":"' . $reg_uid . '"}';
        exit();
    }
    echo '{"code":"500","msg":"注册失败"}';
    exit();
}

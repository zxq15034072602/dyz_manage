<?php
/**
 * 独一张管理app用户操作
 * @date: 2017年6月19日 上午11:31:55
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$user_type = $_REQUEST['type'] ?? 0; // 所屬用戶 （0独一张，1食维健）
if ($do == "userinfo") { // 用户中心个人信息
    $uid = $_REQUEST['uid']; // 用户id
    $user = user($uid); // 获取用户相关信息s
    
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
        if($user['roleid']!=$_REQUEST['roleid']){
            if($user['roleid']==2 && $_REQUEST['roleid']==3){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==2 && $_REQUEST['roleid']==5){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==4 && $_REQUEST['roleid']==5){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==4 && $_REQUEST['roleid']==3){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==2 && $_REQUEST['roleid']==4){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==3 && $_REQUEST['roleid']==5){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==1 && $_REQUEST['roleid']==2){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==1 && $_REQUEST['roleid']==3){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==1 && $_REQUEST['roleid']==4){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }elseif($user['roleid']==1 && $_REQUEST['roleid']==5){
                echo '{"code":"500","msg":"不能申请降级职位"}';
                exit();
            }
        }

        if ($user['stroe_id'] != $_REQUEST['stroe_id']  || $user['roleid'] != $_REQUEST['roleid']) { // 如果用户修改了所属门店，则插入未审核人员记录,或者如果用户修改了职位，则插入未审核店长记录s
            if ($_REQUEST[roleid] == 5 && $user['roleid'] != 3) { // 如果是店员身份修改所属门店
                $sql = "select * from rv_verify where 1=1 and uid=?  and type=0 and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交门店变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime,status) VALUES (?,?,?,now(),?)";
                $arr = array(
                    $uid,
                    $_REQUEST['stroe_id'],
                    0,
                    0
                );
                if ($db->p_e($sql, $arr)) {
                    $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                    $db->p_e($sql, array(
                        $_REQUEST[name],
                        $_REQUEST[sex],
                        $_REQUEST[age],
                        $_REQUEST[head_img],
                        $uid
                    ));
                    echo '{"code":"200","msg":"更换门店提交成功！请等待店长审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"更换门店失败！"}';
                exit();
            } else if ($_REQUEST[roleid] == 3) {
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
                $sql = "insert into rv_verify (uid,mid,type,addtime,status) VALUES (?,?,?,now(),?)";
                $arr = array(
                    $uid,
                    $_REQUEST['stroe_id'],
                    1,
                    0
                );
                if ($db->p_e($sql, $arr)) {
                    $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                    $db->p_e($sql, array(
                        $_REQUEST[name],
                        $_REQUEST[sex],
                        $_REQUEST[age],
                        $_REQUEST[head_img],
                        $uid
                    )); 
                    echo '{"code":"200","msg":"申请成为店长提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请店长失败！"}';
                exit();
            }
        }
        
        if(!empty(json_decode($_REQUEST['stroe_id'])) || $user['roleid'] != $_REQUEST['roleid']){
            if($_REQUEST[roleid]==1){//总部人员审核
                $sql = "select * from rv_verify where 1=1 and uid=?  and type=4 and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime,status) VALUES (?,?,?,now(),?)";
                $arr = array(
                    $uid,
                    '370',
                    4,
                    0
                );
                if ($db->p_e($sql, $arr)) {
                    $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                    $db->p_e($sql, array(
                        $_REQUEST[name],
                        $_REQUEST[sex],
                        $_REQUEST[age],
                        $_REQUEST[head_img],
                        $uid
                    ));
                    echo '{"code":"200","msg":"申请成为总部人员提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请成为总部人员失败！"}';
                exit();
            }elseif($_REQUEST[roleid]==2){//经销商审核
                if (empty(json_decode($_REQUEST[stroe_id]))) { // 如果未选择门店，则不能申请经销商
                    echo '{"code":"500","msg":"请先选择所属门店"}';
                    exit();
                }
                $sql = "select * from rv_verify where 1=1 and uid=?  and type=2 and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                //处理接收的门店id
                $mid=$_REQUEST['stroe_id'];
                $mid=rtrim($mid,']');
                $mid=ltrim($mid,'[');
                $mid=str_replace('"', "", $mid);
                $sql = "insert into rv_verify (uid,mid,type,addtime,status) VALUES (?,?,?,now(),?)";
                $arr = array(
                    $uid,
                    $mid,
                    2,
                    0
                );
                if ($db->p_e($sql, $arr)) {
                    $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                    $db->p_e($sql, array(
                        $_REQUEST[name],
                        $_REQUEST[sex],
                        $_REQUEST[age],
                        $_REQUEST[head_img],
                        $uid
                    ));
                    echo '{"code":"200","msg":"申请成为经销商提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请经销商失败！"}';
                exit();
            }elseif($_REQUEST[roleid]==4){//加盟商审核
                if (empty(json_decode($_REQUEST[stroe_id]))) { // 如果未选择门店，则不能申请加盟商
                    echo '{"code":"500","msg":"请先选择所属门店"}';
                    exit();
                }
                $sql = "select * from rv_verify where 1=1 and uid=?  and type=3 and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime,status) VALUES (?,?,?,now(),?)";
                 
                //处理接收的门店id
                $mid=$_REQUEST['stroe_id'];
                $mid=rtrim($mid,']');
                $mid=ltrim($mid,'[');
                $mid=str_replace('"', "", $mid);
                
                $arr = array(
                    $uid,
                    $mid,
                    3,
                    0
                );
                if ($db->p_e($sql, $arr)) {
                    $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                    $db->p_e($sql, array(
                        $_REQUEST[name],
                        $_REQUEST[sex],
                        $_REQUEST[age],
                        $_REQUEST[head_img],
                        $uid
                    ));
                    echo '{"code":"200","msg":"申请成为加盟商提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请加盟商失败！"}';
                exit();
            }
        }
        

        $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
        if ($db->p_e($sql, array(
            $_REQUEST[name],
            $_REQUEST[sex],
            $_REQUEST[age],
            $_REQUEST[head_img],
            $uid
        ))) {
            echo '{"code":"200","msg":"修改成功"}';
            exit();
        }
        echo '{"code":"500","msg":"修改失败"}';
        exit();
    }
    //处理接收的门店id
    $sql="select mid from rv_user_jingxiao_jiameng where 1=1 and uid=?";
    $db->p_e($sql, array($_REQUEST['uid']));
    $stroe=$db->fetchRow();
    if($stroe){
        $stroe['mid']=explode(",", $stroe['mid']);
        foreach($stroe['mid'] as $k=>$v){
            $sql="select id,name from rv_mendian where 1=1 and id=?";
            $db->p_e($sql, array($v));
            $name=$db->fetchRow();
            $stroe['name'].=$name['name'].'&nbsp;&nbsp;';
        } 
    }else{
        $sql = "select id,name from rv_mendian where 1=1 and type=? and id=?"; // 获取指定门店
        $db->p_e($sql, array(
            $user_type,$_REQUEST['stroe_id']
        ));
        $stroe = $db->fetchRow();
    }
   
    echo '{"userinfo":' . json_encode($user) . ',"stroe":' . json_encode($stroe) . '}';
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
        echo '{"code":"200","uid":"' . $user['id'] . '","user_role":' . json_encode($user_role) . ',"roleid":"' . $user['roleid'] . '","name":"' . $user['name'] . '","mobile":"' . $user['mobile'] . '","store_id":"' . $user['zz'] . '","type":"' . $user['type'] . '"}'; // 登陆成功返回code：200 用户id 与角色权限id
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
} elseif ($do == "area") { // 门店省级联动页面
    $sql = "select  GET_SZM(province) as szm from rv_province group by szm";
    $db->p_e($sql, array());
    $szm = $db->fetchAll();
    foreach ($szm as $key=>&$k) {
        $sql = "select * from (select *,provinceid as pid,(select count(id) from rv_mendian where  provinceid =pid ) as count from rv_province where 1=1 and GET_SZM(province) = ?) as a where a.count >0";
        $db->p_e($sql, array(
            $k['szm']
        ));
        $k['province'] = $db->fetchAll();
        if(empty($k['province'])){
          $k['szm']="";
        }
        foreach ($k[province] as &$value) {
            $sql = "select * from (select city,cityid ,cityid as t,(select count(id) from rv_mendian where cityid=t) as count from rv_city where 1=1  and fatherid=? ) as a  where a.count>0";
            $db->p_e($sql, array(
                $value['provinceid']
            ));
            $value['area'] = $db->fetchAll();
            
        }
    }
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign('province', $szm);
    $smt->display('area.html');
} else if ($do == "find_store_list") { // 获得 指定市级门店
    $type=$_REQUEST[type]??0;
    $cityid=$_REQUEST['cityid'];//城市id
    $sql="select * from rv_mendian where 1=1 and cityid=? and type=? and status=1";
    $db->p_e($sql, array($cityid,$type));
    $store_list=$db->fetchAll();
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign('store_list', $store_list);
    $smt->display('store_list.html');
}else if ($do == "find_store_list1") { // 获得 指定市级门店
    $type=$_REQUEST[type]??0;
    $cityid=$_REQUEST['cityid'];//城市id
    $sql="select * from rv_mendian where 1=1 and cityid=? and type=?";
    $db->p_e($sql, array($cityid,$type));
    $store_list=$db->fetchAll();
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign('store_list', $store_list);
    $smt->display('store_list1.html');
}

elseif($do=='info'){//获取用户个人信息
    $uid=$_REQUEST['uid'];
    if(empty($uid)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    $sql="select a.mid,b.id,b.name,b.age,b.sex,b.mobile,b.head_img,b.roleid from rv_user_jingxiao_jiameng as a left join rv_user as b on a.uid=b.id where 1=1 and a.uid=?";
    $db->p_e($sql, array($uid));
    $info=$db->fetchRow();      
    if($info){
        $info['mid']=explode(",", $info['mid']);
        foreach($info['mid'] as $k=>$v){
            $sql="select id,name from rv_mendian where 1=1 and id=?";
            $db->p_e($sql, array($v));
            $name=$db->fetchRow();
            $info['mdname'].=$name['name'].'&nbsp;&nbsp;';
        } 
    }else{
        $sql="select u.id,u.name,u.age,u.sex,u.mobile,u.head_img,u.roleid,m.name as mdname FROM rv_user as u LEFT JOIN rv_mendian as m on u.zz=m.id where u.id=?";
        $db->p_e($sql, array(
            $uid
        ));
        $info=$db->fetchRow();
    }
    $sql = "select status from rv_verify where 1=1 and uid=? order by addtime desc limit 1";
    $db->p_e($sql, array($uid));
    $verify_status=$db->fetchRow();
    if(!empty($info)){
        echo '{"code":"200","info":'.json_encode($info).',"status":'.json_encode($verify_status).'}';
        exit();
    }else{
        echo '{"code":"500"}';
        exit();
    }
}elseif($do=='mendian'){//经销商,加盟商门店显示
    $uid=$_REQUEST['uid'];
    $sql="select mid from rv_user_jingxiao_jiameng where 1=1 and uid=?";
    $db->p_e($sql, array($_REQUEST['uid']));
    $stroe=$db->fetchRow();
    $arr=explode(",", $stroe['mid']);
    $list=array();
    foreach($arr as $k=>$v){
        $sql="select id,name from rv_mendian where 1=1 and id=?";
        $db->p_e($sql, array($v));
        $arr2=$db->fetchRow();
        $list[$k]=$arr2;
    }
    if($list[0]){
        $smt=new Smarty();
        smarty_cfg($smt);
        $smt->assign('list',$list);
        $smt->display('mendian.html');
    }   
    exit();
}

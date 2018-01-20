<?php
/**
 * 独一张管理app用户操作
 * @date: 2017年6月19日 上午11:31:55
 * @author: fx
 */
if (! defined("CORE"))
    exit("error");
$time=time();
$user_type = $_REQUEST['type'] ?? 0; // 所屬用戶 （0独一张，1食维健）
if ($do == "userinfo") { // 用户中心个人信息
    $uid = $_REQUEST['uid']; // 用户id
    $user = user($uid); // 获取用户相关信息s
    //获取经销商加盟商区域
    if($user['roleid']==2 || $user['roleid']==4){
        $sql="select * from rv_user_jingxiao_jiameng where uid=?";
        $db->p_e($sql, array($uid));
        $city=$db->fetchRow();
        //获取城市
        $sql="select * from rv_city where cityid=?";
        $db->p_e($sql, array($city['cityid']));
        $city_name=$db->fetchRow();
        //获取省份
        $sql="select * from rv_province where provinceid=?";
        $db->p_e($sql, array($city_name['fatherid']));
        $province=$db->fetchRow();
        //连接省份和城市字符串
        $user['region']=$province['province'].$city_name['city'];
    }
    
    if ($_REQUEST['dosubmit']) { // 如果是提交修改用户资料
        if($_POST['head_img']){
            if(stripos($_POST['head_img'],"http://")===false){
                $base64 = $_POST['head_img'];
                $IMG = base64_decode($base64);
                $save_url = "http://static.duyiwang.cn/image/";
                $dir_name = "E:/apptupian/image/";
                /* $save_url = "http://192.168.1.138/apptupian/headimg/";
                 $dir_name = "F:/wamp/www/apptupian/headimg/"; */
        
                $ymd = date("Ymd");
                $dir_name .= $ymd . "/";
                $save_url .= $ymd . "/";
                if (! file_exists($dir_name)) {
                    mkdir($dir_name);
                }
                //缩略图文件名
                $new_file_names = date("YmdHis") . '_' . mt_rand(10000, 99999) . '.jpg';
                // 移动缩略图文件
                $file_path_s = $dir_name . $new_file_names;
                $file_url_s = $save_url . $new_file_names;
               // file_put_contents($file_path_s, $IMG);
                $fhead=fopen($file_path_s, "w");
                fwrite($fhead, $IMG);
                fclose($fhead);
                $head_img=$file_url_s;
            }else{
                $head_img=$_POST['head_img'];
            }
        }
        
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

        if ($user['stroe_id'] != $_REQUEST['stroe_id']  || $user['roleid'] != $_REQUEST['roleid']) { // 如果用户修改了所属门店，则插入未审核人员记录,或者如果用户修改了职位，则插入未审核店长记录s

            if ($_REQUEST[roleid] == 5) { // 如果是店员身份修改所属门店
                $sql = "select * from rv_verify where 1=1 and uid=? and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交门店变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime1,status) VALUES (?,?,?,$time,?)";
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
                        $head_img,
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
                $sql = "select * from rv_verify where 1=1 and uid=? and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime1,status) VALUES (?,?,?,$time,?)";
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
                        $head_img,
                        $uid
                    )); 
                    echo '{"code":"200","msg":"申请成为店长提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请店长失败！"}';
                exit();
            }elseif($_REQUEST[roleid]==1){//总部人员审核
                $sql = "select * from rv_verify where 1=1 and uid=? and status=0";
                $db->p_e($sql, array(
                    $uid
                ));
                if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                    echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                    exit();
                }
                $sql = "insert into rv_verify (uid,mid,type,addtime1,status) VALUES (?,?,?,$time,?)";
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
                        $head_img,
                        $uid
                    ));
                    echo '{"code":"200","msg":"申请成为总部人员提交成功！请等待审核！"}';
                    exit();
                }
                echo '{"code":"500","msg":"申请成为总部人员失败！"}';
                exit();
            }
        }
        
        if(!empty(json_decode($_REQUEST['stroe_id'])) || $user['roleid'] != $_REQUEST['roleid'] || !empty($_REQUEST['cityid'])){
            //处理接收的门店id
            $mid=$_REQUEST['stroe_id'];
            $mid=rtrim($mid,']');
            $mid=ltrim($mid,'[');
            $mid=str_replace('"', "", $mid);
            $sql="select * from rv_user_jingxiao_jiameng where uid=?";
            $db->p_e($sql, array($uid));
            $md=$db->fetchRow()['mid'];
            //处理接收的区域
            if(strpos($_REQUEST['cityid'], ",")){
                $area=$_REQUEST['cityid'];
                $area=rtrim($area,']');
                $area=ltrim($area,'[');
                $area=str_replace('"', "", $area);
                $areaArr=explode(",", $area);
                $sql="select a.* from rv_city as a left join rv_area as b on a.cityid=b.fatherid where b.areaid=?";
                $db->p_e($sql, array($areaArr['0']));
                $cities=$db->fetchRow()['cityid'];                
            }else{
                $cities=$_REQUEST['cityid'];
                $area='';
            }

            if($user['roleid'] != $_REQUEST['roleid'] || $mid != $md && !empty($_REQUEST['cityid'])){
                if($_REQUEST[roleid]==2){//经销商审核
                    if (empty(json_decode($_REQUEST[stroe_id]))) { // 如果未选择门店，则不能申请经销商
                        echo '{"code":"500","msg":"请先选择所属门店"}';
                        exit();
                    }
                    $sql = "select * from rv_verify where 1=1 and uid=? and status=0";
                    $db->p_e($sql, array(
                        $uid
                    ));
                    if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                        echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                        exit();
                    }
                
                    $sql = "insert into rv_verify (uid,mid,type,addtime1,status,cityid,areaid) VALUES (?,?,?,$time,?,?,?)";
                    $arr = array(
                        $uid,
                        $mid,
                        2,
                        0,
                        $cities,
                        $area
                    );
                    if ($db->p_e($sql, $arr)) {
                        $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                        $db->p_e($sql, array(
                            $_REQUEST[name],
                            $_REQUEST[sex],
                            $_REQUEST[age],
                            $head_img,
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
                    $sql = "select * from rv_verify where 1=1 and uid=? and status=0";
                    $db->p_e($sql, array(
                        $uid
                    ));
                    if ($db->fetchRow()) { // 如果还有未处理的审核则不能提交职位变更申请
                        echo '{"code":"500","msg":"您有未处理的申请，请耐心等待"}';
                        exit();
                    }
                    $sql = "insert into rv_verify (uid,mid,type,addtime1,status,cityid,areaid) VALUES (?,?,?,$time,?,?,?)";
                
                    $arr = array(
                        $uid,
                        $mid,
                        3,
                        0,
                        $cities,
                        $area
                    );
                    if ($db->p_e($sql, $arr)) {
                        $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
                        $db->p_e($sql, array(
                            $_REQUEST[name],
                            $_REQUEST[sex],
                            $_REQUEST[age],
                            $head_img,
                            $uid
                        ));
                        echo '{"code":"200","msg":"申请成为加盟商提交成功！请等待审核！"}';
                        exit();
                    }
                    echo '{"code":"500","msg":"申请加盟商失败！"}';
                    exit();
                } 
            }
                       
        }
        
        $sql = "update rv_user set name=?,sex=?,age=?,head_img=? where id=?";
        if ($db->p_e($sql, array(
            $_REQUEST[name],
            $_REQUEST[sex],
            $_REQUEST[age],
            $head_img,
            $uid
        ))) {
            echo '{"code":"200","msg":"修改成功"}';
            exit();
        }
        echo '{"code":"500","msg":"修改失败"}';
        exit();
    }
    //处理接收的门店id
    $sql="select * from rv_user_jingxiao_jiameng where 1=1 and uid=?";
    $db->p_e($sql, array($_REQUEST['uid']));
    $stroe=$db->fetchRow();
    if($stroe){
        $stroe['mid']=explode(",", $stroe['mid']);
        foreach($stroe['mid'] as $k=>$v){
            $sql="select id,name from rv_mendian where 1=1 and id=?";
            $db->p_e($sql, array($v));
            $name=$db->fetchRow();
            $stroe['name'].=$name['name'].'&nbsp;&nbsp;';
            $stroe['store'][]=array('id'=>$v,'name'=>$name['name']);
        } 
        //处理接收的区域
        if($stroe['areaid']){
            $sql="select * from rv_city where cityid=?";
            $db->p_e($sql, array($stroe['cityid']));
            $cityname=$db->fetchRow();
            $stroe['position'].=$cityname['city'];
            $stroe['areaid']=explode(",", $stroe['areaid']);
            foreach($stroe['areaid'] as $kk=>$vv){
                $sql="select * from rv_area where areaid=?";
                $db->p_e($sql, array($vv));
                $areaname=$db->fetchRow();
                $stroe['position'].=$areaname['area'].'&nbsp;&nbsp;';
            }
        }else{
            $sql="select a.city,b.province from rv_city as a left join rv_province as b on a.fatherid=b.provinceid where a.cityid=?";
            $db->p_e($sql, array($stroe['cityid']));
            $cityname=$db->fetchRow();
            $stroe['position']=$cityname['province'].$cityname['city'];
        }
    }else{
        $sql = "select id,name from rv_mendian where 1=1 and type=? and id=?"; // 获取指定门店
        $db->p_e($sql, array(
            $user_type,$_REQUEST['stroe_id']
        ));
        $stroe = $db->fetchRow();
    }
   
    echo '{"userinfo":' . json_encode($user) . ',"stroe":' . json_encode($stroe) . ',"area":'.json_encode($area).'}';
    exit();
} elseif ($do == "login") { // 用户登陆
    //判断是否微信登录
    if($_REQUEST['weixin_id']){
        $sql="select * from rv_user where 1=1 and weixin_id=?";
        $db->p_e($sql, array(
            $_REQUEST['weixin_id']
        ));
        $user=$db->fetchRow();
        
        if($user){
            $sql = "select action from rv_role where 1=1 and  id=?";
            $db->p_e($sql, array(
                $user['roleid']
            ));
            $roles = $db->fetchRow();
            $user_role = explode(",", $roles[action]); // 获取用户权限
            echo '{"code":"200","uid":"' . $user['id'] . '","user_role":' . json_encode($user_role) . ',"roleid":"' . $user['roleid'] . '","name":"' . $user['name'] . '","mobile":"' . $user['mobile'] . '","store_id":"' . $user['zz'] . '","type":"' . $user['type'] . '"}'; // 登陆成功返回code：200 用户id 与角色权限id
            exit();
        }
    }else{
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
    }
      
    echo '{"code":"500","msg":"您输入的账号或密码有误"}';
    exit();
} elseif ($do == "register") { // 用户注册
    $mobile = $_POST['mobile']; // 手机号
    $password = md5($_POST['password']); // 密码
    $weixinid=$_REQUEST['weixin_id']??0;
    if (empty($mobile)) {
        echo '{"code":"500","msg":"手机不能为空"}';
        exit();
    }
    if (empty($password)) {
        echo '{"code":"500","msg":"密码不能为空"}';
        exit();
    }
    
    $sql = "SELECT * FROM rv_user where username =?  LIMIT 1"; // 判断用户是否存在
    $db->p_e($sql, array(
        $mobile
    ));
    $already_user = $db->fetchRow();
    if ($already_user) {
        if ($$already_user[type]) {
            echo '{"code":"500","msg":"此手机已在食维健注册过，请换另一个手机注册"}';
            exit();
        }
        echo '{"code":"500","msg":"此手机已在独一张注册过，请换另一个手机注册"}';
        exit();
    }
    $reg_uid = $db->insert(0, 2, "rv_user", array(
        "username='$mobile'",
        "password='$password'",
        "roleid=5",
        "mobile=$mobile",
        "created_at1='$time'",
        "type=$user_type",
        "weixin_id='$weixinid'"
    ));
    if ($reg_uid) {
        echo '{"code":"200","msg":"注册成功","uid":"' . $reg_uid . '"}';
        exit();
    }
    echo '{"code":"500","msg":"注册失败"}';
    exit();
}elseif($do=='verification'){//验证用户是否注册
    if($_REQUEST['mobile']){
        $sql = "SELECT * FROM rv_user where username =?  LIMIT 1"; // 判断用户是否存在
        $db->p_e($sql, array(
            $_REQUEST['mobile']
        ));
        $already_user = $db->fetchRow();
        if ($already_user) {
            if ($$already_user[type]) {
                echo '{"code":"500","msg":"此手机已在食维健注册过，请换另一个手机注册"}';
                exit();
            }
            echo '{"code":"500","msg":"此手机已在独一张注册过，请换另一个手机注册"}';
            exit();
        }else{
            echo '{"code":"200","msg":"此手机号还没注册"}';
            exit();
        }
    }else{
        echo '{"code":"500","msg":"手机号错误"}';
        exit();
    }
    
}elseif($do=='bind'){//已注册过用户绑定
    $mobile=$_REQUEST['mobile'];
    $password=md5($_REQUEST['password']);
    if($mobile){
        $sql = "SELECT * FROM rv_user where username =? and password=? LIMIT 1"; // 判断用户是否存在
        $db->p_e($sql, array(
            $mobile,
            $password
        ));
        $user = $db->fetchRow();
        if($user){
            $sql = "select action from rv_role where 1=1 and  id=?";
            $db->p_e($sql, array(
                $user['roleid']
            ));
            $roles = $db->fetchRow();
            $user_role = explode(",", $roles[action]); // 获取用户权限
            if($db->update(0, 1, "rv_user", array(
                "weixin_id='$_REQUEST[weixin_id]'"
            ),array(
                "username='$mobile'",
                "password='$password'"
            ))){
                 echo '{"code":"200","uid":"' . $user['id'] . '","user_role":' . json_encode($user_role) . ',"roleid":"' . $user['roleid'] . '","name":"' . $user['name'] . '","mobile":"' . $user['mobile'] . '","store_id":"' . $user['zz'] . '","type":"' . $user['type'] . '"}'; // 登陆成功返回code：200 用户id 与角色权限id
                 exit();
            }else{
                echo '{"code":"500","msg":"您输入的账号或密码有误"}';
                exit();
            }
        }else{
            echo '{"code":"404","msg":"该用户未注册"}';
            exit();
        }
    }else{
        echo '{"code":"500","msg":"手机号错误"}';
        exit();
    }
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
    $sql="select * from rv_mendian where 1=1 and cityid=? and status=1 and type=?";
    $db->p_e($sql, array($cityid,$type));
    $store_list=$db->fetchAll();
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign('store_list', $store_list);
    $smt->display('store_list.html');
}else if ($do == "find_store_list1") { // 获得 指定市级门店
    $type=$_REQUEST[type]??0;
    $cityid=$_REQUEST['cityid'];//城市id
    $sql="select * from rv_mendian where 1=1 and cityid=? and status=1 and type=?";
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
    $sql = "select status from rv_verify where 1=1 and uid=? order by addtime1 desc limit 1";
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
}elseif($do=='forget'){//忘记密码
    $mobile = $_POST['mobile']; // 手机号
    if (empty($mobile)) {
        echo '{"code":"500","msg":"手机不能为空"}';
        exit();
    }
    $sql="select * from rv_user where mobile=?";
    $db->p_e($sql,array($mobile));
    $row=$db->fetchRow();
    if($mobile==$row['mobile']){
        $password = md5($_POST['password']); // 密码
        if($db->update(0, 1, "rv_user", array(
            "password='$password'"
        ),array(
            "id='$row[id]'" 
        ))){
            echo '{"code":"200","msg":"密码修改成功"}';
        }else{
            echo '{"code":"500","msg":"密码修改失败,请重试！"}';
        }
    }
}elseif($do=='city'){//经销商加盟商所属区域
    $roleid=$_REQUEST['roleid'];
    //获取已经选择过得城市
    if($roleid==2){
        $sql="select a.cityid,a.areaid from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=2";
        $db->p_e($sql, array());
        $cities=$db->fetchAll();
        foreach($cities as $val){
            //$str.=$val['cityid'].",".$val['areaid'].",";
            $str.=$val['cityid'].",";
        }
        $cityids=rtrim($str, ",");
        $cids=explode(",", $cityids);
    }else{
        $cids=array();
    }
    
    //获取省份与城市
    $sql="select * from rv_province";
    $db->p_e($sql, array());
    $province=$db->fetchAll();
    foreach($province as &$v){
        if($v['provinceid']==110000 || $v['provinceid']==120000 ||$v['provinceid']==500000 ||$v['provinceid']==310000){
            $sql="select a.areaid as cityid,a.area as city from rv_area as a left join rv_city as b on a.fatherid=b.cityid where b.fatherid=?";
            $db->p_e($sql, array($v['provinceid']));
            $v['city']=$db->fetchAll();
            $v['type']=1;
        }else{
            $sql="select cityid,city from rv_city where fatherid=?";
            $db->p_e($sql, array($v['provinceid']));
            $v['city']=$db->fetchAll();
        }
    }
    $smt=new Smarty();
    smarty_cfg($smt);
    $smt->assign('province', $province);
    $smt->assign('cids', $cids);
    $smt->display('city.html');
    
}








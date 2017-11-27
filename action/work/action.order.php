<?php
if (! defined("CORE")) exit("error");

$uid=$_REQUEST['uid'];//用户id
$role=$_REQUEST['roleid'];//权限id

//经销商 加盟商门店列表 以及产品
if($do=='index'){
    if(!empty($uid) && !empty($role) && ($role==2 || $role==4)){
        $type=$_REQUEST[type]??0;//0独一张/1食维健
        $good_type = $db->select(0, 0, "rv_type","*","and type=$type");
        
        //查询登录用户门店
        $sql="select * from rv_user_jingxiao_jiameng where uid=?";
        $db->p_e($sql, array($uid));
        $userinfo=$db->fetchRow();
        $userinfo['mid']=explode(",", $userinfo['mid']);
        foreach($userinfo['mid'] as $v){
            $sql="select name from rv_mendian where id=?";
            $db->p_e($sql, array($v));
            $name=$db->fetchRow();
            $store[]=array('id'=>$v,'mbname'=>$name['name']);
        }
        $good=array();
        foreach($store as &$val){
            foreach ($good_type as $key => $type) {
                $sql = "select * from rv_goods where fatherid=?";
                $db->p_e($sql, array(
                    $type[id]
                ));
                $goods = $db->fetchAll();
                if ($goods) {
                    $good[$key][typename] = $type[name];
                    $good[$key][goods] = $goods;
                }
            }
        }
        echo '{"code":"200","store":'.json_encode($store).',"good":'.json_encode($good).'}';
        exit();
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='order_add'){//接收订单详情
    $str='';
    $add_list=json_decode($_REQUEST['add_list']);
//     $arr=array('370'=>array(0=>array('goods_id'=>'1','count'=>'10','price'=>'1000'),1=>array('goods_id'=>'2','count'=>'10','price'=>'1000')),'400'=>array(0=>array('goods_id'=>'2','count'=>'2','price'=>'2000')));
//     var_dump($arr);
//     exit();

    foreach($add_list as $key=>$value){
        foreach($value as $k=>&$vv){
            file_put_contents('error4.txt', $k);
            $order_storeid=$db->insert(0,2,"rv_order_stores",array(
                "fid='$uid'",
                "mid='$k'",
                "status=0"
            ));
            if($order_storeid){
                foreach($vv as $val){
                    $order_goodsid=$db->insert(0, 2,"rv_order_goods",array(
                        "fid='$order_storeid'",
                        "goods_id='$val[0]'",
                        "count='$val[1]'",
                        "goods_price='$val[2]'"
                    ));
                    if($order_goodsid){
                        echo '{"code":"200","msg":"提交成功","str":"'.$str.'"}';
                        $str+=$val['2'];
                    }else{
                        echo '{"code":"500","msg":"提交失败"}';
                        exit();
                    }
                }
            }else{
                echo '{"code":"500","msg":"提交失败"}';
                exit();
            }
        }              
    }
    //echo '{"code":"200","msg":"提交成功","str":"'.$str.'"}';
    exit();
}elseif($do=='order_person_info'){//获取接受的订单信息
    $time=time();   
    if($_REQUEST['dosubmit']){
        if (empty($_REQUEST['name'])) {
            echo '{"code":"500","msg":"姓名不能为空"}';
            exit();
        }
        if (empty($_REQUEST['sex'])) {
            echo '{"code":"500","msg":"请选择性别"}';
            exit();
        }
        if (empty($_REQUEST['mobile'])) {
            echo '{"code":"500","msg":"手机号不能为空"}';
            exit();
        }
        if (empty($_REQUEST['address'])) {
            echo '{"code":"500","msg":"收货地址不能为空"}';
            exit();
        }
        if (empty($_REQUEST['price'])) {
            echo '{"code":"500","msg":"关键数据缺失"}';
            exit();
        }
        if(!empty($uid) && !empty($role) && ($role==2 || $role==4)){
            $orderid=$db->insert(0, 2, "rv_order", array(
                "uid='$uid'",
                "name='$_REQUEST[name]'",
                "sex='$_REQUEST[sex]'",
                "roleid='$role'",
                "mobile='$_REQUEST[mobile]'",
                "address='$_REQUEST[address]'",
                "status=0",
                "starttime='$time'",
                "price='$_REQUEST[price]'"
            ));
            if($orderid){
                if($db->update(0, 1, "rv_order_stores",array(
                    "fid='$orderid'",
                    "status=1"
                ),array(
                    "fid='$uid'",
                    "status=0"
                ))){
                    echo '{"code":"200","msg":"申请订单成功！"}';
                    exit();
                }
            }
        }else{
            echo '{"code":"500","msg":"关键数据缺失"}';
            exit();
        }
    }else{//如果不是提交操作，删除刚刚创建的表数据
        $order_storeid=json_decode($_REQUEST['order_storeid']);
        foreach($order_storeid as $v){
            $db->delete(0, 1, "rv_order_stores",array("mid=$v","fid=$uid"));
        }     
    } 
}elseif($do=='record_index'){//进货记录
    if(!empty($uid)){
        $pagenum = 5;
        $page = $_REQUEST['page'] ?? 1;
        $page = ($page - 1) * $pagenum;
        //未完成订单
        $sql="select * from rv_order where uid=? and status in(0,2) order by id desc limit " . $page . "," . $pagenum;
        $db->p_e($sql, array($uid));
        $unfinished_info=$db->fetchAll();  
        $total = $db->fetch_count();
        $total = ceil($total / $pagenum);
        foreach($unfinished_info as &$v){
            $v['starttime']=date('Y/m/d',$v['starttime']);
            if(empty($v['voucher_image'])){
                $v['order_status']='请上传转账凭证';
            }elseif(!empty($v['voucher_image']) && $v['status']==0){
                $v['order_status']='未完成';
            }elseif(!empty($v['voucher_image']) && $v['status']==2){
                $v['order_status']='已发货';
            }
        }
        //已完成订单
        $sql1="select * from rv_order where uid=? and status=1 order by id desc limit " . $page . "," . $pagenum;
        $db->p_e($sql1, array($uid));
        $finish_info=$db->fetchAll();
        $total1 = $db->fetch_count();
        $total1 = ceil($total / $pagenum);  
        foreach($finish_info as &$v){
            $v['starttime']=date('Y/m/d',$v['starttime']);
            if($v['status']==1){
                $v['order_status']='已完成';
            }
        }
        $smt=new Smarty();
        smarty_cfg($smt);
        $smt->assign('total',$total);
        $smt->assign('unfinished_info',$unfinished_info);
        $smt->assign('total1',$total1);
        $smt->assign('finish_info',$finish_info);
        $smt->display('order_list.html');
        exit();    
    }
}elseif($do=='order_detail'){//订单详情
    $orderid=$_REQUEST['orderid'];
    if(!empty($uid) && !empty($orderid)){
        //查询订单表详情
        $sql="select * from rv_order where 1=1 and id=? and uid=?";
        $db->p_e($sql, array($orderid,$uid));
        $order_info=$db->fetchRow();
        
        if(empty($order_info['voucher_image'])){
            $order_info['order_status']='请上传转账凭证';
        }elseif(!empty($order_info['voucher_image']) && $order_info['status']==0){
            $order_info['order_status']='未完成';
        }elseif(!empty($order_info['voucher_image']) && $order_info['status']==2){
            $order_info['order_status']='已发货';
        }elseif($order_info['status']==1){
            $order_info['order_status']='已完成';
        }
        
        //查询门店信息以及产品信息
        $sql="select a.id,a.mid,b.name from rv_order_stores as a left join rv_mendian as b on a.mid=b.id where a.fid=?";
        $db->p_e($sql, array($order_info['id']));
        $order_info['store']=$db->fetchAll();
        foreach($order_info['store'] as &$v){
            $sql="select a.*,b.name,b.money from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id  where a.fid=?";
            $db->p_e($sql, array($v['id']));
            $v['goods_info']=$db->fetchAll();
        }
        echo '{"code":"200","order_info":'.json_encode($order_info).'}';
        exit();
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='upload_credentials'){//上传凭证
    $orderid=$_REQUEST['orderid'];
    if(!empty($uid) && !empty($orderid)){
        $base64 = $_POST['voucher_image'];
        $IMG = base64_decode($base64);
//      $save_url = "http://static.duyiwang.cn/credentials_image/";
//      $dir_name = "E:/apptupian/credentials_image/";
        $save_url = "http://192.168.1.106/apptupian/credentials_image/";
        $dir_name = "E:wamp/wamp/www/apptupian/credentials_image/"; 
        
        $ymd = date("Ymd");
        $dir_name .= $ymd . "/";
        $save_url .= $ymd . "/";
        if (! file_exists($dir_name)) {
            mkdir($dir_name);
        }
        //缩略图文件名
        $new_file_names = $uid.'_'.$orderid.'_'.date("YmdHis") . '_' . mt_rand(100, 999) . '.jpg';
        // 移动缩略图文件
        $file_path_s = $dir_name . $new_file_names;
        $file_url_s = $save_url . $new_file_names;
        file_put_contents($file_path_s, $IMG);
        
        if($db->update(0, 1, "rv_order", array(
            "voucher_image='$file_url_s'"
        ),array(
            "id='$orderid'"
        ))){
            echo '{"code":"200","url":"'.$file_url_s.'"}';
            exit();
        }else{
            echo '{"code":"500","msg":"操作失败"}';
            exit();
        }
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='confirm_order'){//确认收货
    $orderid=$_REQUEST['orderid'];
    $time=time();
    if(!empty($uid) && !empty($orderid)){
        if($_REQUEST['dosubmit']){
            if($db->update(0, 1, "rv_order", array(
                "status=1",
                "endtime='$time'"
            ),array(
                "id='$orderid'",
                "uid='$uid'"
            ))){
                echo '{"code":"200","msg":"确认成功,订单完成"}';
                exit();
            }
        }
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}

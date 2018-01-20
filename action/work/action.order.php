<?php
if (! defined("CORE")) exit("error");

$uid=$_REQUEST['uid'];//用户id
$role=$_REQUEST['roleid'];//权限id

//经销商 加盟商门店列表 以及产品
if($do=='index'){
       if(!empty($uid) && !empty($role) && ($role==2 || $role==4 || $role==1 || $role==6 || $role==7)){

        $type=$_REQUEST[type]??0;//0独一张/1食维健
        $good_type = $db->select(0, 0, "rv_type","*","and type=$type and id<5","id asc");
        if($role==2 || $role==4){
            //查询登录用户门店
            $sql="select * from rv_user_jingxiao_jiameng where uid=?";
            $db->p_e($sql, array($uid));
            $userinfo=$db->fetchRow();
            if($role==2){
                if($userinfo['areaid']){
                    $sql="select id,name as mbname from rv_mendian where areaid=?";
                    $db->p_e($sql, array($userinfo['areaid']));
                    $store=$db->fetchAll();  
                }else{
                    $sql="select id,name as mbname from rv_mendian where cityid=?";
                    $db->p_e($sql, array($userinfo['cityid']));
                    $store=$db->fetchAll();
                }         
            }else{
                $userinfo['mid']=explode(",", $userinfo['mid']);
                foreach($userinfo['mid'] as $v){
                    $sql="select name from rv_mendian where id=?";
                    $db->p_e($sql, array($v));
                    $name=$db->fetchRow();
                    $store[]=array('id'=>$v,'mbname'=>$name['name']);
                }
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
        }elseif($role==1 ||$role==6 ||$role==7){
            $sql="select b.id,b.name from rv_user as a left join rv_mendian as b on a.zz=b.id where a.id=?";
            $db->p_e($sql, array($uid));
            $name=$db->fetchRow();
            $store[]=array('id'=>$name['id'],'mbname'=>$name['name']);
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
        }
       
        echo '{"code":"200","store":'.json_encode($store).',"good":'.json_encode($good).'}';
        exit();
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='order_add'){//接收订单详情
    $add_list=json_decode($_REQUEST['add_list']);
    foreach($add_list as $key=>$value){
        foreach($value as $k=>&$vv){
            $order_storeid=$db->insert(0,2,"rv_order_stores",array(
                "fid='$uid'",
                "mid='$k'",
                "status=0"
            ));
            if($order_storeid){
                foreach($vv as $val){
                     //产品数量
                    if($val[0]==1){$num=$val[1];}elseif($val[0]==2){$num=$val[1]*40;
                    }elseif($val[0]==3){$num=$val[1];}elseif($val[0]==4){ $num=$val[1];
                    }elseif($val[0]==6){$num=$val[1];
                    }elseif($val[0]==7){$num=$val[1]*300;
                    }elseif($val[0]==8){$num=$val[1]*20;
                    }elseif($val[0]==9){$num=$val[1]*50;
                    }elseif($val[0]==10){$num=$val[1]*20;
                    }elseif($val[0]==11){$num=$val[1]*20;
                    }elseif($val[0]==12){$num=$val[1]*50;
                    }elseif($val[0]==13){$num=$val[1]*5;
                    }elseif($val[0]==14){$num=$val[1]*5;
                    }elseif($val[0]==15){$num=$val[1]*40;
                    }elseif($val[0]==16){$num=$val[1]*40;
                    }elseif($val[0]==17){$num=$val[1]*40;
                    }elseif($val[0]==18){$num=$val[1];
                    }elseif($val[0]==19){$num=$val[1];
                    }elseif($val[0]==20){$num=$val[1];
                    }
                    $order_goodsid=$db->insert(0, 2,"rv_order_goods",array(
                        "fid='$order_storeid'",
                        "goods_id='$val[0]'",
                        "count='$val[1]'",
                        "goods_price='$val[2]'",
                        "number='$num'"
                    ));
                    if($order_goodsid){
                        
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
    echo '{"code":"200","msg":"提交成功"}';
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
        if(!empty($uid) && !empty($role) && ($role==2 || $role==4 || $role==1 || $role==6 || $role==7)){
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
                $v['order_status']='已发货，请确认收货';
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

         //物流单号
        $order_info['order_number']=explode("，", $order_info['order_number']);
        
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
//         $save_url = "http://static.duyiwang.cn/credentials_image/";
//         $dir_name = "E:/apptupian/credentials_image/";
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
             //确认收货后添加库存
            $sql="select * from rv_order_stores where fid=?";
            $db->p_e($sql, array($orderid));
            $stores=$db->fetchAll();
            foreach($stores as &$v){
                $sql='select * from rv_order_goods where fid=?';
                $db->p_e($sql, array($v['id']));
                $v['goods']=$db->fetchAll();
                foreach($v['goods'] as $vv){
                    $sql="select kucun from rv_kucun where mid=? and gid=?";
                    $db->p_e($sql, array($v['mid'],$vv['goods_id']));
                    $count=$db->fetchRow()['kucun'];
                    $total=$count+$vv['number'];
                    $db->update(0, 1, "rv_kucun", array(
                        "kucun='$total'"
                    ),array(
                        "mid='$v[mid]'",
                        "gid='$vv[goods_id]'",
                    ));
                } 
            } 
                echo '{"code":"200","msg":"确认收货成功,库存添加成功,订单完成"}';
                exit();
            }
        }
    }else{
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
}elseif($do=='store_ranking'){//经销商门店的进货排行
    $uid=$_REQUEST['uid'];
    $roleid=$_REQUEST['roleid'];
    $pagenum = 10;
    $page = $_REQUEST['page'] ?? 1;
    $page = ($page - 1) * $pagenum;
    if($roleid==2 || $roleid==1 || $roleid==6 || $roleid==7){
        $md=array();
        if($roleid==2){
            $sql="select mid from rv_user_jingxiao_jiameng where uid=? ";
            $db->p_e($sql, array($uid));
            $store=$db->fetch_count();
            $store=explode(",", $store);
            $total = count($store);
            $total = ceil($total / $pagenum);
            foreach($store as $v){
                $sql="select a.name,b.head_img from rv_mendian as a left join rv_user as b on a.person_id=b.id where a.id=?";
                $db->p_e($sql, array($v));
                $info=$db->fetchRow();
                $info['head_img']=$info['head_img']??'http://static.duyiwang.cn/tc_log.jpg';
                $sql="select c.name,sum(b.price) as sum from (rv_order_stores as a left join rv_order as b on a.fid=b.id) left join rv_mendian as c on a.mid=c.id where b.status=1 and a.mid=$v";
                $db->p_e($sql, array());
                $mname=$db->fetchRow();
                $mname['sum']=$mname['sum']??0;
                $md[]=array('id'=>$v,'name'=>$info['name'],'sum'=>$mname['sum'],'head_img'=>$info['head_img']);
            }
        }else{
            //总页数
            $sql="select b.mid from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 group by b.mid ";
            $db->p_e($sql, array());
            $store=$db->fetchAll();
            $total = count($store);
            $total = ceil($total / $pagenum);
            //门店分页
            $sql="select b.mid from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 group by b.mid limit ". $page . "," . $pagenum;
            $db->p_e($sql, array());
            $store=$db->fetchAll();

            foreach($store as $v){
                $sql="select sum(b.price) as sum,c.name,c.person_id from (rv_order_stores as a left join rv_order as b on a.fid=b.id) left join rv_mendian as c on a.mid=c.id where  a.mid=$v[mid]";
                $db->p_e($sql, array());
                $mname=$db->fetchRow();
                $sql="select head_img from rv_user where id=?";
                $db->p_e($sql, array($mname['person_id']));
                $head_img=$db->fetch_count();
                $head_img=$head_img??'http://static.duyiwang.cn/tc_log.jpg';
                $md[]=array('id'=>$v['mid'],'name'=>$mname['name'],'sum'=>$mname['sum'],"head_img"=>$head_img);
            }
        }
        $sort = array(
                     'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                     'field'     => 'sum',       //排序字段
             );
         $arrSort = array();
         foreach($md AS $uniqid => $row){
             foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
             }
         }
         if($sort['direction']){
                  array_multisort($arrSort[$sort['field']], constant($sort['direction']), $md);
         }
         
         echo '{"code":"200","md":'.json_encode($md).',"total":'.json_encode($total).'}';
         exit();        
    }else{
        echo '{"code":"500","msg":"身份信息有误"}';
        exit();
    }
}elseif($do=='search'){//搜索门店查询
    $name=$_REQUEST['name'];
    $search .= "and a.name like ? ";
    $arr[]="%".$_REQUEST['name']."%";

    $sql="select a.id,a.name,b.head_img from rv_mendian as a left join rv_user as b on a.person_id=b.id where a.status=1 and a.type=0 ".$search;
    
    $db->p_e($sql, $arr);
    $store=$db->fetchAll();
    foreach($store as &$v){
        $v['head_img']=$v['head_img']??'http://static.duyiwang.cn/tc_log.jpg';
    }
    echo '{"code":"200","store":'.json_encode($store).'}';
    exit();
}elseif($do=='store_sales'){//单店销售记录
    $mid=$_REQUEST['mid'];
    if(empty($mid)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    //今天门店销售记录
    $todaystart = strtotime(date('Y-m-d' . '00:00:00', time())); // 获取今天00:00
    $todayend = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24)); // 今日结束时间
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id) where 1=1 and a.goods_type=0 and c.mid=? and c.addtime1 BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($mid,$todaystart,$todayend));
    $day_list=$db->fetchAll();
    
    //门店本周销售记录
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id) where 1=1 and a.goods_type=0 and c.mid=? and c.addtime1 BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($mid,$week_start,$week_start));
    $week_list=$db->fetchAll();
    
    //门店本月销售记录
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id) where 1=1 and a.goods_type=0 and c.mid=? and c.addtime1 BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($mid,$beginThismonth,$endThismonth));
    $month_list=$db->fetchAll();
    
    //门店本年销售记录
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id) where 1=1 and a.goods_type=0 and c.mid=? and c.addtime1 BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($mid,$year_start,$year_end));
    $year_list=$db->fetchAll();
    
    echo '{"code":"200","day":'.json_encode($day_list).',"week":'.json_encode($week_list).',"month":'.json_encode($month_list).',"year":'.json_encode($year_list).'}';
    exit();
}elseif($do=='store_order'){//单店进货记录
    $mid=$_REQUEST['mid'];
    if(empty($mid)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    //门店最近一次的进货记录
    $sql="select starttime from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? order by a.starttime desc";
    $db->p_e($sql, array($mid));
    $times=$db->fetch_count();
    if($times){
        $times=date('Y-m-d',$times);
        $daystart=strtotime($times);
        $dayend=($daystart+3600*24);
        $sql="select a.price,b.id from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? and a.starttime between ? and ?";
        $db->p_e($sql, array($mid,$daystart,$dayend));
        $day_order=$db->fetchAll();
        foreach($day_order as &$d){
            $day_fid[]=$d['id'];
        }
        $day_fid=implode(",", $day_fid);
        $sql="select sum(a.goods_price) as price,sum(a.number) as num,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid in ($day_fid) group by a.goods_id";
        $db->p_e($sql, array());
        $day=$db->fetchAll();       
    }else{
        $day=array();
    }
    
    //门店本周的进货记录
    $sdefaultDate = date("Y-m-d"); // 当前日期
    $first = 1; // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($sdefaultDate)); // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $week_s = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days')); // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
    $week_end = strtotime("$week_s +6 days"); // 本周结束日期
    $sql="select a.price,b.id from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? and a.starttime between ? and ?";
    $db->p_e($sql, array($mid,$week_start,$week_end));
    $week_order=$db->fetchAll();
    foreach($week_order as $w){
        $week_fid[]=$w['id'];
    }
    if($week_fid){
        $week_fid=implode(",", $week_fid);
    }else{
        $week_fid=0;
    }    
    $sql="select sum(a.goods_price) as price,sum(a.number) as num,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid in ($week_fid) group by a.goods_id";
    $db->p_e($sql, array());
    $week=$db->fetchAll();
    //门店本月的进货记录
    $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y')); // 本月开始时间
    $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y')); // 本月结束时间
    $sql="select a.price,b.id from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? and a.starttime between ? and ?";
    $db->p_e($sql, array($mid,$beginThismonth,$endThismonth));
    $month_order=$db->fetchAll();
    foreach($month_order as $m){
        $month_fid[]=$m['id'];
    }
    if($month_fid){
        $month_fid=implode(",", $month_fid);
    }else{
        $month_fid=0;
    }
    $sql="select sum(a.goods_price) as price,sum(a.number) as num,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid in ($month_fid) group by a.goods_id";
    $db->p_e($sql, array());
    $month=$db->fetchAll();
    
    //门店本年的进货记录
    $year_start = strtotime(date("Y", time()) . "-1" . "-1"); // 本年开始
    $year_end = strtotime(date("Y", time()) . "-12" . "-31"); // 本年结束
    $sql="select a.price,b.id from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? and a.starttime between ? and ?";
    $db->p_e($sql, array($mid,$year_start,$year_end));
    $year_order=$db->fetchAll();
    foreach($year_order as $y){
        $year_fid[]=$y['id'];
    }
    if($year_fid){
        $year_fid=implode(",", $year_fid);
    }else{
        $year_fid=0;
    }
    $sql="select sum(a.goods_price) as price,sum(a.number) as num,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid in($year_fid) group by a.goods_id";
    //$sql="select a.goods_price,a.number,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid=?";
    $db->p_e($sql, array());
    $year=$db->fetchAll();
    
    echo '{"code":"200","day":'.json_encode($day).',"week":'.json_encode($week).',"month":'.json_encode($month).',"year":'.json_encode($year).',"times":'.json_encode($times).'}';
    exit();
}elseif($do=='store_date'){//门店进货日期
    $mid=$_REQUEST['mid'];
    if(empty($mid)){
        echo '{"code":"500"}';
    }
    $sql="select a.starttime from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=?";
    $db->p_e($sql, array($mid));
    $datetime=$db->fetchAll();
    foreach($datetime as &$v){
        $v['starttime']=date('Y-m-d',$v['starttime']);
    }
    echo '{"code":"200","datetime":'.json_encode($datetime).'}';
    exit();
}elseif($do=='sale_time'){//按照销售日期进行查询
    $starttime=strtotime($_REQUEST['search_time']);
    $endtime=($starttime+3600*24);
    $mid=$_REQUEST['mid'];
    if(empty($mid) || empty($starttime)){
        echo '{"code":"500"}';
        exit();
    }
    $sql="select a.goods_id,sum(a.count) as sum,b.name as gname,(sum(a.count)*b.money) as total from ((rv_buy_goods as a left join rv_goods as b on a.goods_id=b.id)left join rv_buy as c on a.buy_id=c.id) where 1=1 and a.goods_type=0 and c.mid=? and c.addtime1 BETWEEN ? AND ? group by a.goods_id order by total desc ";
    $db->p_e($sql, array($mid,$starttime,$endtime));
    $info=$db->fetchAll();
    echo '{"code":"200","info":'.json_encode($info).'}';
    exit();
}elseif($do=='order_time'){//进货记录按日期查询
    $starttime=strtotime($_REQUEST['search_time']);
    $endtime=($starttime+3600*24);
    $mid=$_REQUEST['mid'];
    if(empty($mid) || empty($starttime)){
        echo '{"code":"500"}';
        exit();
    }
    $sql="select a.price,b.id from rv_order as a left join rv_order_stores as b on a.id=b.fid where a.status=1 and b.mid=? and a.starttime between ? and ?";
    $db->p_e($sql, array($mid,$starttime,$endtime));
    $info_order=$db->fetchAll();
    foreach($info_order as $v){
        $fidA[]=$v['id'];
    }
    $fidA=implode(",", $fidA);
    $sql="select sum(a.goods_price) as price,sum(a.number) as num,b.name from rv_order_goods as a left join rv_goods as b on a.goods_id=b.id where a.fid in ($fidA) group by a.goods_id";
    $db->p_e($sql, array());
    $info=$db->fetchAll();  
    echo '{"code":"200","info":'.json_encode($info).'}';
}elseif($do=='salestime'){//销售记录日期
    $mid=$_REQUEST['mid'];
    if(empty($mid)){
        echo '{"code":"500","msg":"关键数据缺失"}';
        exit();
    }
    $end=time();
    $start=($end-3600*24*60);
    $sql="select addtime1 from rv_buy where mid=? and addtime1 BETWEEN ? AND ?";
    $db->p_e($sql, array($mid,$start,$end));
    $datetime=$db->fetchAll();
    foreach($datetime as &$v){
        $v['addtime1']=date('Y-m-d',$v['addtime1']);
    }
    echo '{"code":"200","datetime":'.json_encode($datetime).'}';
    exit();
}

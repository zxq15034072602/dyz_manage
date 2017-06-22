<?php
/**
* 独一张管理app销售操作
* @date: 2017年6月22日 上午9:30:37
* @author: fx
*/
if(!defined("CORE")) exit("error");
$store_id = $_REQUEST["store_id"];//所属门店Id
$uid = $_REQUEST['uid'];//登陆用户id
$user_roleid = $_REQUEST['roleid'];//用户权限id
if($do == "index"){//销售录入主页面
    $search='';
    $arr=array();
    if($_POST['keywords']){//如果有搜索
        $search .= "and g.name like ? ";
        $arr[]="%".$_POST['keywords']."%";
    }
    $sql = "select * from rv_buy as b,rv_goods as g where b.gid=g.id ".$search." and b.store_id=? ORDER BY shuliang desc";
    $arr[]=$store_id;
    $db->p_e($sql,$arr);
    $sales_goods=$db->fetchAll();
    $sql = "select * from rv_type where 1=1";
    $good_type=$db->select(0, 0, "rv_type");
    $store_goods=array();
    if($good_type){//获取商品品牌，并获取所属门店的商品
        foreach ($good_type as $key=>$type){
            $sql="select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and g.fatherid=?";
            $db->p_e($sql, array($store_id,$type[id]));
            $goods=$db->fetchAll();
            if($goods){
                $store_goods[$key][typename]=$type[name];
                $store_goods[$key][goods]=$goods;
            }
            
        }
    }
    $smt=new Smarty();smarty_cfg($smt);
    $smt->assign("sales_goods",$sales_goods);
    $smt->assign("store_goods",$store_goods);
    $smt->display("sales_index.html");
}elseif ($do == "sales_view"){//获取销售录入信息
    $gid=$_REQUEST['gid'];//商品id
    $sql="select * from rv_goods as g,rv_kucun as k where k.gid=g.id and k.mid=? and k.gid=?";
    $db->p_e($sql,array($store_id,$gid));
    $good=$db->fetchRow();
    if($good){
        echo '{"code":"200","goodinfo":"'.json_encode($good).'"}';
        exit;
    }
    echo '{"code":"500","msg":"程序异常，请稍后重试"}';
    exit;
}elseif($do == "sales_add"){//提交销售录入
    $gid=$_REQUEST['gid'];//商品id
    $count=$_REQUEST['count'];//已选数量
    $total_price =$_REQUEST['total_price'];//销售总价
    $sex=$_REQUEST[sex]??1;
    $address=$_REQUEST['address']??"123";
    $addtime=date('Y-m-d h:i:s');
    $status=($user_roleid ==3 ||$user_roleid==1)?1:0;//录入状态
    if(empty($count)){echo '{"code":"500","msg":"录入数量不能为空！"}';exit;}
    if(empty($_REQUEST['username'])){echo '{"code":"500","msg":"顾客姓名不能为空"}';exit;}
    if(empty($_REQUEST['age'])){echo '{"code":"500","msg":"顾客年龄不能为空"}';exit;}
    if(empty($_REQUEST['mobile'])){echo '{"code":"500","msg":"顾客手机不能为空"}';exit;}
    if(!ismobile($_REQUEST['mobile'])) {echo '{"code":"500","msg":"手机号码不正确"}';exit;}
    $sql="select * from rv_kucun  where 1=1 and gid=? and mid=? ";
    $db->p_e($sql, array($gid,$store_id));
    $sales_kucun=$db->fetchRow();
    if($sales_kucun['kucun']<$count){echo '{"code":"500","msg":"对不起，此商品库存不足"}';exit;}
    $insert_buy=$db->insert(0, 2, "rv_buy", array("uid=$uid","mid=$store_id","gid=$gid","username='$_REQUEST[username]'","sex=$sex","age=$_REQUEST[age]","tel=$_REQUEST[mobile]","shuliang=$count","addtime='$addtime'","address=$address","total_price=$total_price","status=$status"));
    if($insert_buy){//销售录入插入成功后更新商品库存
        $new_kuncun=$sales_kucun['kucun']-$count;
        if($db->update(0, 1, "rv_kucun", array("kucun=$new_kuncun"),array("mid=$store_id","gid=$gid"))){
            echo '{"code":"200","msg":"录入成功，请到我的审查中查看"}';
            exit;
        }
    }
    echo '{"code":"500","msg":"录入失败,请重试"}';
    exit;
}
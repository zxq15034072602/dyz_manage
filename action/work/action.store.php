<?php
if (! defined('CORE'))
    exit("error!");
if($do=='store_num'){//店面分布获取省份下的经销商数量、加盟商数量、门店数量
    $provinceid=$_REQUEST['provinceid'];
    if(empty($provinceid)){
        echo '{"code":"500","msg":"关键性数据缺失"}';
        exit();
    }
    //省下经销商数量
    $sql="select count(*) from (rv_user_jingxiao_jiameng as a left join rv_city as b on a.cityid=b.cityid)left join rv_user as c on a.id=c.zz where c.roleid=2 and b.fatherid=?";
    $db->p_e($sql, array($provinceid));
    $dealerConut =$db->fetch_count();

    //省下加盟商数量
    $sql="select count(*) from (rv_user_jingxiao_jiameng as a left join rv_city as b on a.cityid=b.cityid)left join rv_user as c on a.id=c.zz where c.roleid=4 and b.fatherid=?";
    $db->p_e($sql, array($provinceid));
    $franchiseeConut =$db->fetch_count();
    //省下门店数量
    $sql="select count(*) from rv_mendian where status=1 and provinceid=?";
    $db->p_e($sql, array($provinceid));
    $storeCount=$db->fetch_count();    
    echo '{"dealerConut":"'.$dealerConut.'","franchiseeConut":"'.$franchiseeConut.'","storeCount":"'.$storeCount.'"}';
    exit();
}elseif($do=='dealer_franchisee_info'){//城市下面经销商、门店加盟商详细信息
    $cityid=$_REQUEST['cityid'];
    if(empty($cityid)){
        echo '{"code":"500","msg":"关键性数据缺失"}';
        exit();
    }
    //经销商个人信息
    $sql="select a.mid,a.cityid,a.areaid,b.* from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=2 and a.cityid=?";
    $db->p_e($sql, array($cityid));
    $dealer_info=$db->fetchAll();
    //加盟商信息
    foreach($dealer_info as &$v){
        //获取经销商门店
        $v['mid']=explode(",", $v['mid']);
        foreach($v['mid'] as &$value){
            $sql="select name from rv_mendian where status=1 and id=?";
            $db->p_e($sql, array($value));
            $md=$db->fetchRow()['name'];
            $v['mdname'][]=$md;
        }

        //获取经销商城市
        $sql="select city from rv_city where cityid=?";
        $db->p_e($sql, array($v['cityid']));
        $city=$db->fetchRow()['city'];
        if($v['roleid']==2){
            $position='经销商';
        }
        $v['position']=$city.$position;   
    }
    //获取城市门店
    $sql="select * from rv_mendian where cityid=? and status=1 and type=0";
    $db->p_e($sql, array($cityid));
    $store=$db->fetchAll();
 
    foreach($store as &$val){
        $sql="select a.*,b.name from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=4";
        $db->p_e($sql, array());
        $arr=$db->fetchAll();
        foreach($arr as $vvv){
            $vvv['mid']=explode(",", $vvv['mid']);
            if(in_array($val['id'], $vvv['mid'])){
                $val['username'].=$vvv['name'].'&nbsp;';
            }
        }
    }    
    echo '{"code":"200","dealer_info":'.json_encode($dealer_info).',"store":'.json_encode($store).'}';
    exit();
}elseif($do=='city_storenum'){//城市下经销商数、加盟商数、门店数量
    $id=$_REQUEST['provinceid'];
    if(empty($id)){
        echo '{"code":"500","msg":"关键性数据缺失"}';
        exit();
    }
    $cities = $db->select(0, 0, "rv_city", "*", "and fatherid=$id", "id asc");
    foreach($cities as $k=>&$v){
        //经销商数量
        $sql="select count(*) from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=2 and a.cityid=?";
        $db->p_e($sql, array($v['cityid']));
        $v['dealer_num']=$db->fetch_count();
        //加盟商数量
        $sql="select count(*) from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=4 and a.cityid=?";
        $db->p_e($sql, array($v['cityid']));
        $v['franchisee_num']=$db->fetch_count();
        //门店数量
        $sql="select count(*) from rv_mendian where cityid=? and status=1 and type=0";
        $db->p_e($sql, array($v['cityid']));
        $v['store_num']=$db->fetch_count();
    }
    echo '{"code":"200","cities":' . json_encode($cities) . '}';
    exit();
}




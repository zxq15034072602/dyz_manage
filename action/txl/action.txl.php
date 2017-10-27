<?php
if (! defined('CORE'))
    exit("error!");
// 通讯录
$type=$_REQUEST["type"];
if ($do == "txl") {
    // 门店
    if($_REQUEST['name']){
        $search .= "and gu_group_nick like ? ";
        $arr1[]="%".$_REQUEST['name']."%";
    }
   
    $sql = "select GET_SZM(a.name) as szm from rv_mendian as a left join rv_fengongsi as b on a.fid=b.id   where 1=1 and a.status=1  group by szm";
    $db->p_e($sql, array());
    $szm = $db->fetchAll();
    if (count($szm) > 0) {
        $sql = "select a.*,b.name as fgsname,GET_SZM(a.name) as szm from rv_mendian as a left join rv_fengongsi as b on a.fid=b.id where 1=1 and a.status=1 and a.type=? and b.status=1 and a.id!=370";
        $db->p_e($sql, array($type));
        $txl = $db->fetchAll();
        foreach ($txl as &$k) {
            $k['admin'] = user($k['adminid']);
            $sql = "select id from rv_user where 1=1 and zz=? and status=1 and roleid in (1,3,5,6,7) and zz!=370";
            $db->p_e($sql, array(
                $k['id']
            ));
            $k['yh_user'] = $db->fetchAll();
            foreach ($k['yh_user'] as &$v) {
                $v['user'] = user($v['id']);
                if(stripos($v['user']['head_img'],"http://")===false && $v['user']['head_img']!=null){
                    $v['user']['head_img']="../../image/header_picture/".$v['user']['head_img'];
                }
            }
        }
    } else {
        $txl = array();
    }
    //太常集团
        $sql="select a.* from rv_user as a left join rv_mendian as b on a.zz=b.id where a.zz=370 and a.status=1";
        $db->p_e($sql, array());
        $zongbu=$db->fetchAll();
        foreach ($zongbu as &$vvvv){
            if(stripos($vvvv['head_img'],"http://")===false && $vvvv['head_img']!=null){
                $vvvv['head_img']="../../image/header_picture/".$vvvv['head_img'];
            }
        }
    //经销商
        $sql="select b.* from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=2 and b.status=1";
        $db->p_e($sql, array());
        $jingxiao=$db->fetchAll();
        foreach ($jingxiao as &$vv){
            if(stripos($vv['head_img'],"http://")===false && $vv['head_img']!=null){
                 $vv['head_img']="../../image/header_picture/".$vv['head_img'];
            }
        }
    //加盟商
        $sql="select b.* from rv_user_jingxiao_jiameng as a left join rv_user as b on a.id=b.zz where b.roleid=4 and b.status=1";
        $db->p_e($sql, array());
        $jiameng=$db->fetchAll();
        foreach($jiameng as $vvv){
            if(stripos($vvv['head_img'],"http://")===false && $vvv['head_img']!=null){
                $vvv['head_img']="../../image/header_picture/".$vvv['head_img'];
            }
        }
    // 模版
    $flag = $_REQUEST['flag'] ?? '0'; // 0未默认通信录 1群聊通讯录
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign("flag", $flag);
    $smt->assign('szm', $szm);
    $smt->assign('txl', $txl);
    $smt->assign('jingxiao',$jingxiao);
    $smt->assign('jiameng',$jiameng);
    $smt->assign('zongbu',$zongbu);
    $smt->display('txl.html');
    exit();
} elseif ($do == "get_store") { // 获取省份对应门店
    $cityid = $_REQUEST['cityid'] ?? 0;
    $sql = "select id,name from rv_mendian where cityid=? and status=1 and type=? ";
    $db->p_e($sql, array(
        $cityid,$type
    ));
    $stores = $db->fetchAll();
    echo '{"code":"200","stores":' . json_encode($stores) . '}';
    exit();
} elseif ($do == "single_txl") { // 单聊通讯录
    $storeid = $_REQUEST['store_id'];
    $sql = "select * from rv_user where 1=1 and zz=? and status=1 and roleid in (1,3,5)";
    $db->p_e($sql, array(
        $storeid
    ));
    $txl = $db->fetchAll();
    foreach($txl as &$v){
        if(stripos($v['head_img'],"http://")===false && $v['head_img']!=null){
            $v['head_img']="../../image/header_picture/".$v['head_img'];
        }
    }
    // 模版
    $flag = 0; // 0未默认通信录 1群聊通讯录
    $smt = new smarty();
    smarty_cfg($smt);
    $smt->assign("flag", $flag);
    $smt->assign('txl', $txl);
    $smt->display('txl.html');
    exit();
}elseif($do=='search'){//群聊通讯录搜索
    $name=$_REQUEST['name'];
    $search .= "and name like ? ";
    $arr[]="%".$_REQUEST['name']."%";
    
    $sql="select * from rv_user where 1=1 and status=1 ".$search;
    $db->p_e($sql, $arr);
    $list=$db->fetchAll();
    foreach($list as &$v){
        if($v['roleid']==2){
            $sql="select b.city as cityname from rv_user_jingxiao_jiameng as a left join rv_city as b on a.cityid=b.cityid where a.id=?";
            $db->p_e($sql, array($v['zz']));
            $cityname=$db->fetchRow()['cityname'];
            $v['position']=$cityname.'经销商';
        }elseif($v['roleid']==4){
            $sql="select b.city as cityname from rv_user_jingxiao_jiameng as a left join rv_city as b on a.cityid=b.cityid where a.id=?";
            $db->p_e($sql, array($v['zz']));
            $cityname=$db->fetchRow()['cityname'];
            $v['position']=$cityname.'加盟商';
        }else{
            if($v['zz']){
                $sql="select name as mdname from rv_mendian where id=?";
                $db->p_e($sql, array($v['zz']));
                $v['position']=$db->fetchRow()['mdname'];
            }else{
                $v['position']='该用户还未加入门店';
            }
            
        }
    }
    echo '{"list":'.json_encode($list).'}';
    exit();
}


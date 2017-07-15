<?php
if (! defined('CORE'))
    exit("error!");
if ($do == "ajax_region") {
    $type = $_REQUEST['type'] ?? 2;
    $id = $_REQUEST['id'] ?? 0;
    if($type == 1){//获取省份
        $province = $db->select(0, 0, "rv_province", "*", "", "id asc");
        if ($province) {
            echo '{"code":"200","province":' . json_encode($province) . '}';exit();
        } else {
            echo '{"code":500}';exit();
        }
    } else if ($type == 2) { // 获取省份对应的市区
        $cities = $db->select(0, 0, "rv_city", "*", "and fatherid=$id", "id asc");
        if ($cities) {
            echo '{"code":"200","cities":' . json_encode($cities) . '}';exit();
        } else {
            echo '{"code":500}';exit();
        }
    } elseif ($type == 3) { // 获取市区对应的城区
        $areas = $db->select(0, 0, "rv_area", "*", "and fatherid=$id and status=0", "id asc");
        if ($areas) {
            echo '{"code":"200","areas":' . json_encode($areas) . '}';
        } else {
            echo '{"code":500}';
        }
    }
}
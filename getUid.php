<?php
    //当前在线人数
    $arrName = 'onlineUserId';
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $onlineUserId = $redis -> sMembers($arrName);
    $userInfo = [];
    foreach ($onlineUserId as $value){
        $arr = $redis->HGETALL('swuser_'.$value);
        if($arr) $userInfo[] = $arr;
    }
    echo json_encode($userInfo);



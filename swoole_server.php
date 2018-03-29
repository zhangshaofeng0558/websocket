<?php
    //创建websocket服务器对象，监听0.0.0.0:9502端口
    $ws = new swoole_websocket_server("0.0.0.0", 8008);
    //$ws->set(['daemonize' => 1]);
    //监听WebSocket连接打开事件
    $ws->on('open', function ($ws, $request) {
        echo "client-{$request->fd} is opening\n";
    });
    //监听WebSocket消息事件
    $ws->on('message', function ($ws, $frame) {
        echo "Message: {$frame->data}\n";
        //解码客户端传来的数据
        $data = json_decode($frame->data);
        //var_dump($data);
        $fd = $frame->fd;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        //判断用户进入还是发送消息
        if(isset($data->uid)&& !empty($data->uid)){
            //发送信息用户
            $name = $data->uid;
            $uid = md5($name);
            $userInfoKey = 'swuser_'.$uid;
            $arrName = 'onlineUserId';
            $result = $redis->sAdd($arrName,$uid);
            if($result){
                //当前为新用户绑定自己的uid/fd
                $userInfoArr = ['uid'=>$uid,'name'=>$name,'fd'=>$fd];
                $redis->HMSET($userInfoKey,$userInfoArr);
                $redis->SET($fd,$uid);
            }else{
                $redis->HSET($userInfoKey,'fd',$fd);
            };
            $arr = ['class'=>0];
        }else if(isset($data->toId)&& !empty($data->toId)){
            //发送信息
            //获取被发送用户fd
            $toId=$data->toId;
            $toFd = $redis->HGET('swuser_'.$toId,'fd');
            //var_dump($toFd);
            //查询用户是否在线
            $connections = [];
            foreach ($ws->connections as $value){
                $connections[] = $value;
            }
            if(in_array($toFd,$connections)){
                //获取发送用户id
                $fromId = $redis->get($frame->fd);
                $fromName = $redis->HGET('swuser_'.$fromId,'name');
                $toArr = ['class'=>1,'text'=>$data->text,'fromName'=>$fromName,'fromId'=>$fromId];
                $toMsg = json_encode($toArr);
                $ws->push($toFd,$toMsg);
                $arr = ['class'=>2,'text'=>'消息已发送'];
            }else{
                $arr = ['class'=>3,'text'=>'用户已离线'];
            }
        }else{
            $arr = ['class'=>4,'text'=>'心跳检测'];
        }
        //发送回执给用户
        $msg = json_encode($arr);
        $ws->push($fd,$msg);
    });
    //监听WebSocket连接关闭事件
    $ws->on('close', function ($ws, $fd) {
        echo "client-{$fd} is closed\n";
        //用户下线，删除对应的fd号和集合中用户名
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $uid = $redis->get($fd);
        $arrName = 'onlineUserId';
        $redis -> sRem($arrName,$uid);
        $redis->del('swuser_'.$uid);//删除hash
        $redis->del($fd);
    });
    $ws->start();

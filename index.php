<?php
    //当前在线人数
    $arrName = 'onlineUserId';
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $onlineUserId = $redis -> sMembers($arrName);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>websocket</title>
    <style>
        div{margin-bottom: 10px}
        .to_user{cursor: pointer;margin-right: 10px}
        .to_user:hover{color:red}
        .chat{margin-top:30px;border: 1px solid rgb(80,80,80);width: 220px;height:300px}
        .chat_title{margin: 5px 0}
        .chat_room{margin-bottom:20px;height: 220px;border:1px dashed rgb(80,80,80);border-left:none;border-right:none;overflow-y: auto}
    </style>
</head>
<body>
<div class="welcome"></div>
<div>当前用户:<span class="currentUser"></span></div>
<div>当前在线用户:
    <div class="users"></div>
</div>
<div class="chats"></div>
</body>
<script src="jquery.min.js"></script>
<script>
    var name = prompt('输入用户名','');
    console.log(name);
    if(name === 'null' || name === '')location.reload(true);
    //var wsUrl = 'wss://zhangshaofeng.top/wss';
    var wsUrl = 'ws://127.0.0.1:8008';
    var ws = new WebSocket(wsUrl);
    ws.onopen = function () {
        console.log('连接成功');
        var uid = name;
        var msg = JSON.stringify({'uid':uid})
        ws.send(msg);
        //心跳检测重置
        heartCheck.reset().start();
    };
    ws.onmessage = function (event) {
        var msg = JSON.parse(event.data);
        console.log(msg);
        //根据消息不同类型判断
        var type = msg.class;
        //首次上线
        if(type === 0){
            $.post('getUid.php',function(msg){
                console.log(msg);
                var data = JSON.parse(msg);
                $.each(data,function (index,arr) {
                    if(arr.name === name)$('.currentUser').attr('title',arr.uid).html(arr.name);
                    var item ="<span class='to_user' title='"+arr.uid+"'>"+arr.name+"</span>";
                    $('.users').append(item);
                })
            })
            console.log('用户登录');
        }else if(type ===1){
            var $selector = $('.chats');
            var newListItem ="<div style=\"text-align:left\">"+ msg.text+"</div>";
            var $self = $('.chat .chat_title[title='+msg.fromId+']');
            var len = $self.length;
            //console.log(len);
            if(len ===0){
                var _word ='<div class="chat">'
                    +'<div class="chat_title" title="'+msg.fromId+'">与'+msg.fromName+'交谈中</div>'
                    +'<div class="chat_room">'+newListItem+'</div>'
                    +'<div class="chat_send">'
                    +'<input type="text" class="text">'
                    +'<button class="send"> 发送</button>'
                    +'</div>'
                    +'</div>';
                $selector.append(_word);
            }else{
                var $parent = $self.parents('.chat');
                $parent.find('.chat_room').append(newListItem);
            }
        }else{
            //console.log(msg.text);
        }
        //获取到消息，心跳检测重置
        heartCheck.reset().start();
    }
    ws.onclose = function () {}
    ws.onerror = function () {}

    //心跳检测
    var heartCheck = {
        timeout: 60000,//60s
        timeoutObj: null,
        serverTimeoutObj: null,
        reset: function(){
            console.log('heart reset');
            clearTimeout(this.timeoutObj);
            clearTimeout(this.serverTimeoutObj);
            return this;
        },
        start: function(){
            var self = this;
            this.timeoutObj = setTimeout(function(){
                //这里发送一个心跳，后端收到后，返回一个心跳消息，
                //onmessage拿到返回的心跳就说明连接正常
                ws.send("HeartTest");
                //如果超过一定时间还没重置(onmessage没收到消息)，说明后端主动断开了
                self.serverTimeoutObj = setTimeout(function(){
                    ws.close();
                }, self.timeout)
            }, this.timeout)
        }
    }

    $(function () {
        $('.chats').on('click','.send',function () {
            var $selector = $(this).parents('.chat');
            //选择联系人
            var toId = $selector.find('.chat_title').attr('title');
            if(toId  === '') {
                console.log('用户未选择')
                return false;
            }
            //发送内容为空
            var text = $selector.find('.text').val();
            //console.log(text);
            if(text === ''){
                console.log('消息内容为空')
                return false;
            }
            var msg = JSON.stringify({'text':text,'toId':toId });
            console.log(msg);
            ws.send(msg);
            $selector.find('.text').val('');
            var newListItem ="<div style=\"text-align:right\">"+ text+"</div>";
            $selector.find('.chat_room').append(newListItem);
        })

        $('.users').on('click','.to_user',function () {
            var $selector = $('.chats');
            var uid = $('.currentUser').attr('title');
            var toId = $(this).attr('title');
            if(uid === toId)return false;
            var $self = $('.chat .chat_title[title='+toId+']');
            var len = $self.length;
            if(len !== 0)return false;
            var toName = $(this).text();
            var _word ='<div class="chat">'
                +'<div class="chat_title" title="'+toId+'">与'+toName+'交谈中</div>'
                +'<div class="chat_room"></div>'
                +'<div class="chat_send">'
                +'<input type="text" class="text">'
                +'<button class="send"> 发送</button>'
                +'</div>'
                +'</div>';
            $selector.append(_word);
        })

        $('body').keyup(function () {
            var $self = $(this);
            if (event.which === 13){
                $self.find('.send').trigger('click');
            }
        });
    })
</script>
</html>
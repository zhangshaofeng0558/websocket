# websocket
一个基于websocket协议实现的即时聊天；
客户端使用了浏览器支持的websocket接口；
服务器端使用了swoole搭建了websocket服务器；
客户端服务端一段时间无数据传送，会自动断开连接，
借鉴代码 https://github.com/zimv/WebSocketHeartBeat
加了个心跳包，一段时间发送一个数据包，以免掉线

使用
1.linux 环境安装swoole扩展。可参考swoole官网安装说明
2.服务器端php命令行启动 php swoole_srver.php
3.打开控制台查看信息进行调试

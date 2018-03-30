# websocket
一个基于websocket协议实现的即时聊天；
客户端使用了浏览器支持的websocket接口；
服务器端使用了swoole搭建了websocket服务器；
客户端服务端一段时间无数据传送，会自动断开连接，
借鉴代码 https://github.com/zimv/WebSocketHeartBeat
加了个心跳包，一段时间发送一个数据包，以免掉线

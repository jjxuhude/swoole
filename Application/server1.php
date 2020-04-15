<?php
$serv = new swoole_server("127.0.0.1", 9501);
$serv->set(array(
    'worker_num' => 2,    //开启两个worker进程
    'max_request' => 3,   //每个worker进程max request设置为3次
    'dispatch_mode'=>3,
));
//监听数据接收事件
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, "Server: ".$data);
});
//启动服务器
$serv->start();
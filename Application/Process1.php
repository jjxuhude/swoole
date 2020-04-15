<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;

use Swoole;

class Process1
{

    function __construct()
    {
        $this->run();
    }

    function run(){
        $server = new Swoole\Server('127.0.0.1', 9501);
        $server->set(array('task_worker_num' => 4));
        /**
         * 用户进程实现了广播功能，循环接收管道消息，并发给服务器的所有连接
         */
        $process = new Swoole\Process(function($process) use ($server) {
            while (true) {
                $msg = $process->read();
                foreach($server->connections as $conn) {
                    $server->send($conn, $msg);
                }
            }
        });

        $server->addProcess($process);

        $server->on('receive', function ($serv, $fd, $reactor_id, $data) use ($process) {
            //群发收到的消息
            $process->write("我是通過進程回复的：".$data);
            $task_id = $serv->task($data);
            echo "异步任务：Dispath AsyncTask: id=$task_id\n";
        });

        $server->on('task', function ($serv, $task_id, $from_id, $data) {
            echo "New AsyncTask[id=$task_id]".PHP_EOL;
            //返回任务执行的结果
            $serv->finish("$data -> OK");
        });

        $server->on('finish', function ($serv, $task_id, $data) {
            echo "任务完成：AsyncTask[$task_id] Finish: $data".PHP_EOL;
        });



        $server->start();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;
use Swoole;
class Tcp{

    function __construct()
    {
        $this->run();
        $this->wait();
    }

    function run(){
        $server = new Swoole\Server('127.0.0.1', 9500);
        $server->set(array('task_worker_num' => 1));
        /**
         * 创建用户进程实现了广播功能，循环接收管道消息，并发给服务器的所有连接
         */
        $process = new Swoole\Process(function($process) use ($server) {
            while (true) {
                $msg = $process->read();
                foreach($server->connections as $conn) {
                    $server->send($conn, "服务器开始执行任务：".$msg);
                }
            }
        });

        $server->addProcess($process);

        $server->on('receive', function ($serv, $fd, $reactor_id, $data) use ($process) {
            //用户进程接收到客户端的消息后，向管道发送消息：自己的进程id
            $process->write(getmypid()."任务：".$data);
            //发给正在连接的客户端$fd的信息
            $serv->send($fd,"----------------");
            //开始执行任务
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

        $server->on('WorkerStart', function ($serv, $worker_id){
            echo 'worker_id:'.$worker_id.PHP_EOL;
        });


        $server->start();
    }

    function wait()
    {
        while ($ret = Swoole\Process::wait()) {
            echo "PID={$ret['pid']}\n";
        }
    }
}

new Tcp();
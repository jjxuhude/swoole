<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;


use Swoole;

class Process7
{

    function __construct()
    {
        $this->run();
    }

    function run(){

        $proc1 = new Swoole\Process(function (Swoole\Process $proc) {
            $socket = $proc->exportSocket();
            echo $socket->recv();
            $socket->send("hello master\n");
            echo "proc1 stop\n";
        }, false, 1, true);
        //重定向子进程的标准输入和输出。
        //SOCK_STREAM：1
        //启用协程

        $proc1->start();


        //父进程创建一个协程容器
        \Co\run(function() use ($proc1) {
            $socket = $proc1->exportSocket();
            $socket->send("hello pro1\n");
            echo ($socket->recv());
        });



        Swoole\Process::signal(SIGCHLD, function ($sig) {
            //必须为false，非阻塞模式
            while ($ret = Swoole\Process::wait(false)) {
                echo "PID={$ret['pid']}\n";
            }
        });


    }

}

require '../vendor/autoload.php';
new Process7();
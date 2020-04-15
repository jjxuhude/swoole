<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;

use Swoole;

class Process2
{
    protected $manager_num = 2;
    protected $worker_num = 5;
    protected $queue;

    function __construct()
    {
        $this->queue();
        $this->worker();
        $this->manager();
        $this->wait();
    }

    function queue(){
        $key =\ftok(__DIR__,1);
        $queue = \msg_get_queue($key);
        $this->queue=$queue;
    }
    function manager()
    {
        for ($i = 0; $i < $this->manager_num; $i++) {
            $process = new Swoole\Process(function () use ($i) {
                msg_send($this->queue,1,'我给你派发任务了：'.$i,false,false);
                echo getmypid() . ':正在執行任务' . PHP_EOL;
            });
            $process->name('manager_'.$i);

            $process->start();
        }
    }

    function worker()
    {
        for ($i = 0; $i < $this->worker_num; $i++) {
            $process = new Swoole\Process(function () {
                while(true){
                    msg_receive($this->queue,0,$msgtype,1024,$message,false);
                    echo getmypid() . ':正在執行任务:'.$message . PHP_EOL;
                }
            });
            $process->name('worker_'.$i);
            $process->start();
        }
    }


    function wait()
    {
        while ($ret = Swoole\Process::wait()) {
            //echo "PID={$ret['pid']}\n";
        }
    }

}

require '../vendor/autoload.php';
new Process2();
<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;


use Swoole;
use PDO;
use Swoole\Process;
use Swoole\Event;
use Co;
use Swoole\Table;

class pool_data
{

    protected $worker = 5;
    protected $pool=[];
    protected $table;
    protected $redis;
    function __construct()
    {
        $this->run();
    }


    function redis(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        return $redis;
    }


    function run(){
        for ($i = 0; $i < $this->worker; $i++) {
            $worker = new Process(function ($worker) {
                //在子进程中给管道添加事件监听
                //底层会自动将该管道设置为非阻塞模式
                //参数二，是可读事件回调函数，表示管道可以读了
                Event::add($worker->pipe, function ($pipe) use ($worker) {
                    //子进程把计算的结果，写入管道
                    $redis = $this->redis();
                    foreach(range(1,100) as $item){
                        sleep(1);
                        $redis->rpush('name',"woker:".$worker->pid.":".$item."_item");
                    }
                    $status = $worker->pid.':完成';
                    $worker->write($status);
                    //注意，swoole_event_add与swoole_event_del要成对使用
                    Event::del($worker->pipe);
                    //退出子进程
                    $worker->exit(0);
                });
            },false,1,false);

            $worker_process[$i] = $worker;

            //启动子进程
            $worker->start();
        }

        $atomic = new Swoole\Atomic();
        for ($i = 0; $i < $this->worker; $i++) {
            $worker->write($i);
            $worker = $worker_process[$i];
            //主进程中，监听子进程管道事件
            Event::add($worker->pipe, function ($pipe) use ($worker,$atomic) {
                $status = $worker->read();
                //var_dump($status);
                if($atomic->add()==$this->worker){
                    var_dump("完成");
                }
                Event::del($worker->pipe);
            });
        }

        //父进程监听子进程退出信号，回收子进程，防止出现僵尸进程
        Process::signal(SIGCHLD, function ($sig) {
            //必须为false，非阻塞模式
            while ($ret = Process::wait(false)) {
                //print_r($ret);
            }

        });






    }





}

require '../vendor/autoload.php';
new pool_data();
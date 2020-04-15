<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 23:03
 */

namespace App;
use Co\Redis;
use Swoole;


/**
 * Class Pool1
 * @package App
 * 1.redis开启一个有序列表
 * 2.开启10个woker的线程池
 * 每个woker监听有序列表如果有数据，就取出来处理，否则就等待
 */
class Pool1
{
    protected  $redis;


    function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->run();
    }



    function run(){
        $worker_num = 5;
        //设置swoole进程名称
        swoole_set_process_name('swoole_pool');
        foreach(range(1,3) as $i){
            $users[]='user_'.$i;
        }
        $this->redis->lPush('name',...$users);

        $pool =new Swoole\Process\Pool($worker_num);

        $pool->on('WorkerStart',[$this,'workerStart']);
        $pool->on('WorkerStop',[$this,'workerStop']);

        $pool->start();
    }

    function workerStart($pool,$worker_id){
        $running = true;
        //安装信号 SIGTERM：终止进程
        pcntl_signal(SIGTERM, function () use (&$running) {
            $running = false;
        });
        echo "Worker#{$worker_id} : ".getmypid()." is started,master pid:$pool->master_pid\n";
        $key = "name";
        while ($running) {
            try{
                $msgs = $this->redis->brpop($key, 1);
                //调用信号回调
                pcntl_signal_dispatch();
                if(empty($msgs)){
                    break;
                }else{
                    var_dump($msgs);
                }
            }catch (\Exception $e){
                break;
            }

        }
    }

    function workerStop(Swoole\Process\Pool $pool,$worker_id){
//        echo "Master pid:".$pool->master_pid."\n";
//        echo "Worker#{$worker_id} is stopped\n";
    }




}

require '../vendor/autoload.php';
new Pool1();
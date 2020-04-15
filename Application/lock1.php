<?php
declare(strict_types=1);
namespace App;
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 23:03
 */

use Swoole;
use Co;
use PDO;
use Chan;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Runtime;
use Swoole\Process;


//需要启动TCP.PHP

class lock1
{
    protected $pool=[];
    protected $worker=5;
    protected $result=[];
    function __construct()
    {

        $this->run();
    }

    function redis(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        return $redis;
    }
    function run(){
        $this->redis()->set('start',0);
        $this->redis()->del('demo');
        for($n=$this->worker;$n--;){
            $process = new Process(function (Process $proc)use($n){
                $socket=$proc->exportSocket();
                $redis = $this->redis();
                $done=$redis->incr('start');
                $redis->rPush('demo',$done);
                $socket->send('ok');
            },false,1,true);
            $process->start();
            $this->pool[$process->pid]=$process;
        }

        $atomic = new Swoole\Atomic();
        foreach($this->pool as $pid=>$proc){
            Co\run(function()use($proc,$atomic){
                $socket=$proc->exportSocket();
                if($socket->recv()=='ok' && $atomic->add()==$this->worker){
                    var_dump($this->redis()->lRange('demo',0,-1));
                }
            });
        }
    }




}

require '../vendor/autoload.php';
new lock1();
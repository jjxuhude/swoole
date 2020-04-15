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


//需要启动TCP.PHP

class lock
{
    protected $pool;
    protected $worker = 20;
    protected $pagesize= 10000;
    protected  $redis;
    protected $orders=[];

    function __construct()
    {
        $this->run();
    }
    function run(){
        $lock = new Swoole\Lock(SWOOLE_MUTEX);
        echo "[Master]create lock\n";
        $lock->lock();
        if (pcntl_fork() > 0)
        {
            echo "child ing...\n";
            sleep(3);
            $lock->unlock();
        }
        else
        {
            echo "[Child] Wait Lock\n";
            $lock->lock();
            echo "[Child] Get Lock\n";
            $lock->unlock();
            exit("[Child] exit\n");
        }
        echo "[Master]release lock\n";
        unset($lock);
        sleep(1);
        echo "[Master]exit\n";
    }




}

require '../vendor/autoload.php';
new lock();
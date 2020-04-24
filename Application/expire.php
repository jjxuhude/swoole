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

class expire
{
    protected $pool=[];
    protected $worker=5;
    protected $result=[];
    function __construct()
    {

        $this->run();
    }


    function run(){
        var_dump(set_time_limit(10) );
        var_dump(ini_set('max_execution_time','5'));
        var_dump("我开始睡觉");
        sleep(60);
        var_dump('我睡了60秒');
    }




}

require '../vendor/autoload.php';
new expire();
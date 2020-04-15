<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;

use Swoole;

class Process3
{

    function __construct()
    {
        $this->run();
    }

    function run(){
        for($i=0;$i<10;$i++){
            $process = new Swoole\Process(function (){
                echo getmypid().':正在執行任务'.PHP_EOL;
                sleep(10);
            });

            $process->start();
        }

        while($ret =  Swoole\Process::wait()) {
            echo "PID={$ret['pid']}\n";
        }

    }

}

require '../vendor/autoload.php';
new Process2();
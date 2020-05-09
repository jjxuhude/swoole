<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 22:26
 */



namespace App;

use Swoole;
use Co;
use Chan;

class co2
{
    function __construct()
    {
        $this->run();
    }

    function run()
    {
        Swoole\Runtime::enableCoroutine();
        $chan = new Chan();
        go(function()use($chan){
            for($i=0;$i<5;$i++){
                $chan->push($i);
                echo "顺序插入{$i}".PHP_EOL;
            }
        });
        echo "顺序执行".PHP_EOL;
        go(function()use($chan){
            while(!$chan->isEmpty()){
                $res = $chan->pop();
                echo "顺序消费{$res}".PHP_EOL;
            }
        });
    }




}

new co2();
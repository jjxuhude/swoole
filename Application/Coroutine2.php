<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 22:26
 */

namespace App;

use Swoole;


class Coroutine2
{
    function __construct()
    {
        $this->run();
    }

    function run()
    {
        $server=new Swoole\Http\Server('0.0.0.0',9501);

        $server->set([
            'worker_num' => 1
        ]);

        $server -> on('request',function ($request,$response){
            $data=$this->client();
            $response->end("<h1>$data</h1>");
        });

        $server -> start();
    }

    protected function client(){
        Co\run(function(){
            //使用Channel进行协程间通讯
            $chan = new Swoole\Coroutine\Channel(1);
            Swoole\Coroutine::create(function () use ($chan) {
                for($i = 0; $i < 5; $i++) {
                    co::sleep(1.0);
                    $chan->push(['rand' => rand(1000, 9999), 'index' => $i]);
                    echo "$i\n";
                }
            });
            Swoole\Coroutine::create(function () use ($chan) {
                while(1) {
                    $data = $chan->pop();
                    var_dump($data);
                }
            });
        });
    }
}

new Coroutine2();
<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 22:26
 */

namespace App;

use Swoole;


class Coroutine1
{
    function __construct()
    {
        $this->run();
    }

    function run()
    {
        $server = Swoole\Http\Server('0,0,0,0', 9501);

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
        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $client ->connect('127.0.0.1',9501);
        $client ->send('Swoole\Coroutine\Client');
        $data=$client->recv();
        $client->close();
        return $data;
    }
}

new Coroutine1();
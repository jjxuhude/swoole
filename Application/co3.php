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

class co3
{
    function __construct()
    {
        $this->run();
    }

    function run()
    {
        Swoole\Runtime::enableCoroutine();
        $chan = new Chan();
        go(function () use ($chan){
            $result =[];
            for($i=0;$i<2;$i++){
                $result[]=$chan->pop();
            }
            var_dump($result);
        });


        # 协程2
        go(function () use ($chan) {
            $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
            $cli->set(['timeout' => 10]);
            $cli->setHeaders([
                'Host' => "www.qq.com",
                "User-Agent" => 'Chrome/49.0.2587.3',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Encoding' => 'gzip',
            ]);
            $ret = $cli->get('/');
            // $cli->body 响应内容过大，这里用 Http 状态码作为测试
            $chan->push(['www.qq.com' => $cli->statusCode]);
        });

        # 协程3
        go(function () use ($chan) {
            $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
            $cli->set(['timeout' => 10]);
            $cli->setHeaders([
                'Host' => "www.163.com",
                "User-Agent" => 'Chrome/49.0.2587.3',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Encoding' => 'gzip',
            ]);
            $ret = $cli->get('/');
            // $cli->body 响应内容过大，这里用 Http 状态码作为测试
            $chan->push(['www.163.com' => $cli->statusCode]);
        });

    }




}

new co3();
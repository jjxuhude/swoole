<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 23:03
 */

namespace App;
use Swoole;

//需要启动TCP.PHP

class Web
{
    function __construct()
    {
        $this->run();
    }

    function run(){
        $server=new Swoole\Http\Server('0.0.0.0',9501);
        $server->set([
            'worker_num'=>1
        ]);
        $server->on('request',function ($request,$response){
            $response->header("Content-Type", "text/html; charset=utf-8");
//            $data=$this->client();
//            $mysqlData=$this->mysql();
//            $name=$this->redis();
         //   $api=$this->curl();
//            $html="
//                    <h1>client:$data</h1>
//                    $mysqlData
//                    <h1>redis:$name</h1>
//                    <div>$api</div>
//                ";
            $server=print_r($request->server,true);
            $html="<h1>{$server}</h1>";
            $response->end($html);
        });
        $server->start();
    }

    protected function client(){
        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $client ->connect('127.0.0.1',9500);
        $client->send('Swoole\Coroutine\Client');
        $data=$client->recv();
        $client->close();
        return $data;
    }

    protected function mysql(){
        $mysql = new Swoole\Coroutine\MySQL();
        $mysql ->connect([
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => 'Jj!111111',
            'database' => 'promotion_css',
            'port'    => '3306',
            'charset' => 'utf8mb4',
            'strict_type' => false, //开启严格模式，query方法返回的数据也将转为强类型
            'fetch_mode' => false, //开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        ]);
        $mysql->setDefer();
        $mysql->query('select * from rule_type');
        $items=$mysql->recv();
        return dump($items);
    }

    protected function redis(){
        $redis = new Swoole\Coroutine\Redis();
        $redis->connect('127.0.0.1',6379);
        $redis->set('name','徐华德',3600);
        $name=$redis->get('name');
        return $name;
    }

    protected function curl(){
        $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', 80);
//        $cli->setHeaders([
//            'Host' => "localhost",
//            "User-Agent" => 'Chrome/49.0.2587.3',
//            'Accept' => 'text/html,application/xhtml+xml,application/xml',
//            'Accept-Encoding' => 'gzip',
//        ]);
//        $cli->set([ 'timeout' => 1]);
        $cli->get('/');
        $cli->close();
        return  $cli->body;
    }



}

require '../vendor/autoload.php';
new Web();
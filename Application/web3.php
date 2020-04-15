<?php
declare(strict_types=1);
namespace App;
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 23:03
 */

/**
 * http://192.168.59.100:9502/?start=2019-12-16&end=2019-12-18
 * web3.php
 * tpc3.php
 */

use Swoole;
use Co;
use PDO;
use Chan;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Runtime;



class web3
{

    function __construct()
    {
        $this->run();
    }
    function run(){
        $server=new Swoole\Http\Server('0.0.0.0',9502);
        $server->set([
            'worker_num'=>1,
            'buffer_output_size' => 32 * 1024 *1024 //必须为数字
        ]);
        $server->on('request',function ($request,$response){
            $response->header("Content-Type", "application/json; charset=utf-8");
            if(!isset($request->get['start']) || !isset($request->get['end'])){
                $result=['status'=>'error','message'=>"开始时间和结束时间必须"];
                $result= json_encode($result);
                $response->end($result);
            }
            $message = $this->done($request);
            $result=['status'=>'success','message'=>$message];
            $result= json_encode($result);
            $response->end($result);
        });
        $server->start();
    }

    protected function done($request){
        $client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC );
        if (!$client->connect('127.0.0.1', 9501, 0.5)) {
            $message="connect failed. Error: {$client->errCode}";
        }else{

            $startTime=$request->get['start'];
            $endTime=$request->get['end'];
            $data=['start'=>$startTime,'end'=>$endTime];
            $data=json_encode($data);
            $client->send($data);
            $message= $client->recv();
        }
        $client->close();
        return $message;
    }

}

require '../vendor/autoload.php';
new web3();
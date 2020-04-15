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

class web2
{
    protected $pool;
    protected $worker = 10;
    protected $pagesize= 10000;
    protected  $redis;
    protected $orders=[];

    function __construct()
    {
        $this->run();
    }
    function run(){
        $server=new Swoole\Http\Server('0.0.0.0',9501);
        $server->set([
            'worker_num'=>1,
            'buffer_output_size' => 32 * 1024 *1024 //必须为数字
        ]);
        $server->on('request',function ($request,$response){
            $response->header("Content-Type", "application/json; charset=utf-8");
            $csvFileName = "CSV数据.csv";
            $response->header("Content-Type","application/vnd.ms-excel; charset=utf-8");
            $response->header("Pragma","public");
            $response->header("Expires","0");
            $response->header("Cache-Control","must-revalidate, post-check=0, pre-check=0");
            $response->header("Content-Type","application/force-download");
            $response->header("Content-Type","application/octet-stream");
            $response->header("Content-Type","application/download");
            $response->header("Content-Disposition","attachment;filename=$csvFileName");
            $response->header("Content-Transfer-Encoding","binary");
            $orders=$this->exportOrders();
            $this->orders=[];

            $response->end(join(PHP_EOL,$orders));
        });
        $server->start();
    }



    function exportOrders(){

        Runtime::enableCoroutine();
        $s = microtime(true);
        $chan = new Chan();
        $wg = new Swoole\Coroutine\WaitGroup();
        for($i=$this->worker;$i--;){
            $wg->add();
            go(function()use ($i,$wg){
                $pageSize=$this->pagesize;
                /** @var $pdo \PDO **/
                $pdo = new PDO('mysql:host=192.168.111.1;dbname=css_ii_order;charset=utf8mb4', 'magento2', '');
                /* @var $statment Coroutine\Mysql\Statement*/
                $statment = $pdo->prepare("select * from orders limit ?,?");
                $statment->bindValue(1,$i * $pageSize, PDO::PARAM_INT);
                $statment->bindValue(2,$pageSize,PDO::PARAM_INT);
                /* @var $result Coroutine\Mysql\Statement */
                $result = $statment->execute();
                $list=$statment->fetchAll(PDO::FETCH_ASSOC);

                $ids =[];
                foreach($list as $item){
                    $ids[]=$item['id'];
                }

                $statment=$pdo->prepare('select id,order_id,pdt_id,sku_id from `order_goods` where `order_goods`.`order_id` in (:ids)');
                $statment->bindValue('ids',join(',',$ids));
                $result = $statment->execute();
                $items=$statment->fetchAll(PDO::FETCH_ASSOC);

                $orderItems=[];
                foreach($items as $item){
                    $orderItems[$item['order_id']][]=$item;
                }
                foreach($list as $order){
                    $order['goods']='[]';
                    if(isset($orderItems[$order['id']])){
                        $order['goods']=json_encode($orderItems[$order['id']]);
                    }
                    $order = array_map(function($field){
                        if($field && !is_numeric($field)){
                            return '"'.strtr($field,['"'=>'""']).'"';
                        }else{
                            return (int)$field;
                        }
                    },$order);
                    $line=iconv("UTF-8", "GB2312//IGNORE", join(',',$order));
                    $this->orders[]=$line;
                }
                $wg->done();
            });

        }
        $wg->wait();
        $s = microtime(true) - $s;
        ksort($this->orders,SORT_NUMERIC);
        var_dump($s,count($this->orders));
        return $this->orders;
    }
}

require '../vendor/autoload.php';
new web2();
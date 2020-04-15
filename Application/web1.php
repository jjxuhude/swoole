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

class web1
{
    protected $pool;
    protected $worker = 2;
    protected $pagesize= 10000;

    function __construct()
    {
        $this->initPool();
        $this->run();
    }
    function initPool(){
        if(!$this->pool){
            $pool = new PDOPool((new PDOConfig)
                ->withHost('192.168.111.1')
                ->withPort(3306)
                // ->withUnixSocket('/tmp/mysql.sock')
                ->withDbName('css_ii_order')
                ->withCharset('utf8mb4')
                ->withUsername('magento2')
                ->withPassword('')
            );
            $this->pool= $pool;
        }
    }
    function run(){
        $server=new Swoole\Http\Server('0.0.0.0',9501);
        $server->set([
            'worker_num'=>1
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

            $response->end(join(PHP_EOL,$orders));
        });
        $server->start();
    }



    function exportOrders(){

        Runtime::enableCoroutine();
        $chan = new Chan();
        $s = microtime(true);
        for($i=$this->worker;$i--;){
            go(function()use ($chan,$i){
                $pageSize=$this->pagesize;
                /** @var $pdo \PDO **/
                $pdo=$this->pool->get();
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

                foreach($list as &$order){
                    $order['goods']='[]';
                    if(isset($orderItems[$order['id']])){
                        $order['goods']=json_encode($orderItems[$order['id']]);
                    }

                }

                $chan->push($list);
                $this->pool->put($pdo);
            });

        }

        $lines=[];
        $wg = new Swoole\Coroutine\WaitGroup();
        for($i=0;$i<$this->worker;$i++){
            $wg->add();
            Swoole\Coroutine::create(function()use($wg,$chan,&$lines){
                $orders = $chan->pop();
                array_map(function ($order)use(&$lines){
                    $order = array_map(function($field){
                        if($field && !is_numeric($field)){
                            return '"'.strtr($field,['"'=>'""']).'"';
                        }else{
                            return (int)$field;
                        }
                    },$order);
                    $line=iconv("UTF-8", "GB2312//IGNORE", join(',',$order));
                    $lines[$order['id']]=$line;
                },$orders);
                $wg->done();
            });
        }
        $wg->wait();
        ksort($lines,SORT_NUMERIC);
        $s = microtime(true) - $s;
        var_dump([$s,count($lines)]);
        return $lines;
    }
}

require '../vendor/autoload.php';
new web1();
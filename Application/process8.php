<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;


use Swoole;
use PDO;

class process8
{

    protected $worker = 10;
    protected $pagesize= 10000;
    protected $pool=[];
    protected $orders=[];
    protected $table;

    function __construct()
    {
        $this->run();
    }

    function run(){

        $s = microtime(true);
        $this->table();

        for($n=$this->worker;$n--;){
            $proc = new Swoole\Process(function (Swoole\Process $proc) use ($n) {
                $socket = $proc->exportSocket();
                $this->exportOrders($n);
                $socket->send("ok");
            }, false, 1, true);
            //重定向子进程的标准输入和输出。
            //SOCK_STREAM：1
            //启用协程
            $proc->start();
            $this->pool[$proc->pid]=$proc;
        }


        $atomic = new Swoole\Atomic();
        foreach($this->pool as $pid=>$proc){
            //父进程创建一个协程容器
            \Co\run(function () use ($proc,$atomic,$s) {
                $socket = $proc->exportSocket();
                if($socket->recv()=='ok' && $atomic->add()==$this->worker){


                    $content='';
                    foreach($this->table as $row)
                    {
                        $content.=$row['line'].PHP_EOL;
                    }
                    //error_log($content,3,'order.csv');
                    $s = microtime(true) - $s;
                    var_dump($this->table->count());
                    var_dump($s);
                }
            });
        }
    }

    function exportOrders($i){
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
            $this->table->set($order['id'],['line'=>$line]);
        }
    }

    function table(){
        $table = new Swoole\Table(1002400);
        $table->column('line', Swoole\Table::TYPE_STRING,1024);
        $table->create();
        $this->table=$table;
    }

}

require '../vendor/autoload.php';
new process8();
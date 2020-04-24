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
use Swoole\Process;
use Swoole\Event;
use Co;
use Swoole\Table;

class merge
{

    protected $worker = 24;
    protected $pagesize= 10000;
    protected $pool=[];
    protected $table;
    protected $redis;
    function __construct()
    {
        $this->run();
    }

    function redis(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        return $redis;
    }

    function table(){
        $table = new Swoole\Table(100240);
        $table->column('line', Swoole\Table::TYPE_STRING,9002);
        $table->create();
        $this->table=$table;
    }

    function run(){
        $startTime='2019-12-16 00:00:00';
        $endTime='2019-12-17 00:00:00';
        @unlink('export/order.csv');
//        $lock = new Swoole\Lock();
        for ($i = 0; $i < $this->worker; $i++) {
            $worker = new Process(function ($worker)use($startTime,$endTime) {
                //在子进程中给管道添加事件监听
                //底层会自动将该管道设置为非阻塞模式
                //参数二，是可读事件回调函数，表示管道可以读了
                Event::add($worker->pipe, function ($pipe) use ($worker,$startTime,$endTime) {
                    //子进程把计算的结果，写入管道
                    $i = $worker->read();
                    $orders=$this->exportOrders($i,$startTime,$endTime);
                   // var_dump(end($orders));
//                    $lock->lock();
//                    error_log(join(PHP_EOL,$orders).PHP_EOL,3,'export/'.$i.'.tmp');
                      file_put_contents('export/'.$i.'.tmp',join(PHP_EOL,$orders).PHP_EOL);

//                    $lock->unlock();
                    $status = $worker->pid.":".count($orders);
                    $worker->write($status);
                    //注意，swoole_event_add与swoole_event_del要成对使用
                    Event::del($worker->pipe);
                    //退出子进程
                    $worker->exit(0);
                });
            },false,1,false);

            $worker->name('order_export');
            $worker_process[$i] = $worker;

            //启动子进程
            $worker->start();
        }
        unset($lock);

        $atomic = new Swoole\Atomic();
        for ($i = 0; $i < $this->worker; $i++) {
            $worker->write($i);
            $worker = $worker_process[$i];
                //主进程中，监听子进程管道事件
                Event::add($worker->pipe, function ($pipe) use ($worker,$atomic) {
                    $status = $worker->read();
                    var_dump($status);
                    if($atomic->add()==$this->worker){
                        $dir =__DIR__."/export/";
                        exec("find ".$dir." -name '*.tmp' | sort -t / -n -k 2 |xargs cat >> ".$dir."order.csv");
                        exec("rm -rf ".$dir."*.tmp");
                        var_dump("完成");
                    }
                    Event::del($worker->pipe);
                });
        }

        //父进程监听子进程退出信号，回收子进程，防止出现僵尸进程
        Process::signal(SIGCHLD, function ($sig) {
            //必须为false，非阻塞模式
            while ($ret = Process::wait(false)) {
                //print_r($ret);
            }

        });






    }

    function exportOrders($i,$startTime,$endTime){
        $pageSize=$this->pagesize;
        /** @var $pdo \PDO **/
        $pdo = new PDO('mysql:host=192.168.111.1;dbname=css_ii_order;charset=utf8mb4', 'magento2', '');
        /* @var $statment Coroutine\Mysql\Statement*/
        $statment = $pdo->prepare("select * from orders where created_at >=? and  created_at<? limit ?,?");
        $statment->bindValue(1,$startTime, PDO::PARAM_STR );
        $statment->bindValue(2,$endTime, PDO::PARAM_STR);
        $statment->bindValue(3,$i * $pageSize, PDO::PARAM_INT);
        $statment->bindValue(4,$pageSize,PDO::PARAM_INT);
        /* @var $result Coroutine\Mysql\Statement */
        $result = $statment->execute();
        $list=$statment->fetchAll(PDO::FETCH_ASSOC);
        if(!$list){
            return [];
        }

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
        $lines=[];
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
            $content=iconv("UTF-8", "GB2312//IGNORE", join(',',$order));
            $lines[]= strtr($content,[PHP_EOL=>""]);
        }
        return $lines;
    }



}

require '../vendor/autoload.php';
new merge();
<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/7/25
 * Time: 23:53
 */

namespace App;
use Swoole;
use Co;
class tcp3{

    function __construct()
    {
        $this->run();
        $this->wait();
    }

    function run(){
        $server = new Swoole\Server('127.0.0.1', 9501);
        $server->set(array('task_worker_num' => 1));

        $process = new Swoole\Process(function(Swoole\Process $process) use ($server) {
            $data = $process->read();//接收参数，执行任务
            $data=json_decode($data,true);
            $data['process']=$process;
            new process14($data);
        });

        $server->addProcess($process);

        $server->on('receive', function ($serv, $fd, $reactor_id, $data) use ( $process){
              $serv->send($fd,'收到数据：'.$data.",正在处理中..."); //发生到客服端
              $data= json_decode($data,true);
              $data['export_id']=123;
              $data = json_encode($data);
              $serv->task($data);
        });

        $server->on('task', function ($serv, $task_id, $from_id, $data) use ($process) {
            var_dump($data);
            $process->write($data);//接收从web服务器传过来的参数，通过进程通道，发送给订单导出进程
            $serv->finish($data);
        });
        $server->on('finish', function ($serv, $task_id, $data) use ($process) {
            while(true){
                $result=$process->read(); //执行完成
                var_dump($result);
            }

        });
        $server->start();
    }



    function wait()
    {
        while ($ret = Swoole\Process::wait()) {
            echo "PID={$ret['pid']}\n";
        }
    }
}
require '../vendor/autoload.php';
new tcp3();
<?php
$worker_process_nums = 5;
$worker_process = [];

for ($i = 0; $i < $worker_process_nums; $i++) {
    $worker = new swoole_process(function ($worker) {
        //在子进程中给管道添加事件监听
        //底层会自动将该管道设置为非阻塞模式
        //参数二，是可读事件回调函数，表示管道可以读了
        swoole_event_add($worker->pipe, function ($pipe) use ($worker) {
            $task = json_decode($worker->read(), true);

            $tmp = 0;
            for ($i = $task['start']; $i < $task['end']; $i++) {
                $tmp += $i;
            }
            echo "子进程 : {$worker->pid} 计算 {$task['start']} - {$task['end']} \n";
            //子进程把计算的结果，写入管道
            $worker->write($tmp);
            //注意，swoole_event_add与swoole_event_del要成对使用
            swoole_event_del($worker->pipe);
            //退出子进程
        });
    });

    $worker_process[$i] = $worker;

    //启动子进程
    $worker->start();
}

for ($i = 0; $i < $worker_process_nums; $i++) {
    $worker = $worker_process[$i];

    $worker->write(json_encode([
        'start' => mt_rand(1, 10),
        'end' => mt_rand(50, 100),
    ]));

    //主进程中，监听子进程管道事件
    swoole_event_add($worker->pipe, function ($pipe) use ($worker) {
        $result = $worker->read();
        echo "子进程 : {$worker->pid} 计算结果 {$result} \n";
        swoole_event_del($worker->pipe);
    });
}


//父进程监听子进程退出信号，回收子进程，防止出现僵尸进程
swoole_process::signal(SIGCHLD, function ($sig) {
    //必须为false，非阻塞模式
    while ($ret = swoole_process::wait(false)) {
        print_r($ret);
    }
});


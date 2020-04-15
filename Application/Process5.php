<?php
use Swoole\Process;
$process = new Process(function (Process $worker) {
   // $worker->exec('/usr/bin/date', ['+%Y-%m-%d %H:%M:%S']);
    $worker->write('hello');
},true, 1, true); // 需要启用标准输入输出重定向

$process->start();

Co\run(function() use($process) {
    echo "read:".$process->read()."\n";
   // $socket = $process->exportSocket();
   // echo "使用Co exec: " . $socket->recv() . "\n";
});









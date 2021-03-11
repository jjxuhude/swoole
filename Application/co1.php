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

class co1
{
    function __construct()
    {
        $this->run3();
        //dev/bug/1111
    }

    function run()
    {
        Co\run(function () {
            $server = new Co\Http\Server("0.0.0.0", 9502, false);
            $server->handle('/', function ($request, $response) {
                $response->end("<h1>Index</h1>");
            });
            $server->handle('/test', function ($request, $response) {
                $response->end("<h1>Test</h1>");
            });
            $server->handle('/stop', function ($request, $response) use ($server) {
                $response->end("<h1>Stop</h1>");
                $server->shutdown();
            });
            $server->start();
        });
        echo 1;//得不到执行
    }

    function run2(){
        Co\run(function () {
            go(function() {
                var_dump(file_get_contents("http://www.xinhuanet.com/"));
            });

            go(function() {
                Co::sleep(1);
                echo "done\n";
            });
        });
    }

    function run3(){
        $scheduler = new Swoole\Coroutine\Scheduler;
        $scheduler->add(function ($a, $b) {
            Co::sleep(1);
            echo assert($a == 'hello') . PHP_EOL;
            echo assert($b == 12345) . PHP_EOL;
            echo "Done.\n";
        }, "hello", 12345);

        $scheduler->start();
    }


}

new co1();
//11111111111111
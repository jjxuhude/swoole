<?php
declare(strict_types=1);
namespace App;

use Swoole;
use Co;
use Chan;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Runtime;

class Download
{
    const N = 1024;
    protected $pool;
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

    function run()
    {
        Runtime::enableCoroutine();
        $s = microtime(true);
        Co\run(function () {
            for ($n = self::N; $n--;) {
                go(function () use ($n) {
                    $pdo = $this->pool->get();
                    $statement = $pdo->prepare('select * from orders limit 1');
                    if (!$statement) {
                        throw new RuntimeException('Prepare failed');
                    }
                    $result = $statement->execute();
                    if (!$result) {
                        throw new RuntimeException('Execute failed');
                    }
                    $result = $statement->fetchObject();
                    var_dump($result);
                    $this->pool->put($pdo);
                });
            }
        });

    }




}

new Download();
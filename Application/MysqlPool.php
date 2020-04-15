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

class MysqlPool
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
                ->withDbName('css_ii_admin')
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
                    $statement = $pdo->prepare('select * from users limit 1');
                    if (!$statement) {
                        throw new RuntimeException('Prepare failed');
                    }
                    $a = mt_rand(1, 100);
                    $b = mt_rand(1, 100);
                    $result = $statement->execute([$a, $b]);
                    if (!$result) {
                        throw new RuntimeException('Execute failed');
                    }

                    $result = $statement->fetchObject();
                    var_dump($result);
                    $this->pool->put($pdo);
                });
            }
        });

        $s = microtime(true) - $s;
        echo 'Use ' . $s . 's for ' . self::N . ' queries' . PHP_EOL;
    }




}

new MysqlPool();
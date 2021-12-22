<?php

namespace Uccu\SwKoaHttp;

use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;
use Uccu\SwKoaPlugin\PluginLoader;
use Uccu\SwKoaPlugin\IPoolStartBeforePlugin;

class HttpServer implements IPoolStartBeforePlugin
{

    /**
     * @var PluginLoader
     */
    public $pluginLoader;

    /**
     * @var Pool
     */
    public $pool;

    /**
     * @var int
     */
    public $workerId;

    /**
     * @var \Uccu\SwKoaLog\Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    public $config;

    public function pluginLoaderInit(PluginLoader $pluginLoader)
    {
        $this->pluginLoader = $pluginLoader;
    }

    public function poolStartBefore($manager)
    {

        $appName = "Uccu\\SwKoaServer\\App";
        if (class_exists($appName)) {
            $this->config = $appName::$config;
            $this->logger = $appName::$logger;
        }

        if (is_null($this->config)) {
            throw new HttpServerException("config is not been imported");
        }

        $host = $this->config::get('app.HOST');
        if (!$host) {
            $host = "0.0.0.0";
        }

        $port = $this->config::get('app.PORT');
        if (!$port) {
            $port = 9501;
        }

        $port = intval($port);

        $workerNum = $this->config::get('app.WORKER_NUM');
        if (!$workerNum) {
            $workerNum = swoole_cpu_num();
        }

        $workerNum = intval($workerNum);


        $manager->addBatch($workerNum, function (Pool $pool, int $workerId) use ($host, $port) {

            $this->pool = $pool;
            $this->workerId = $workerId;

            $this->pluginLoader->httpServerStartBefore($this);

            $server = new Server($host, $port, false, true);

            $server->handle('/', function (Request $request, Response $response) use ($pool, $workerId) {
                $ctx = new Context($request, $response, $pool, $workerId);
                $this->pluginLoader->httpServerHandleBefore($ctx);
            });
            
            if (!is_null($this->logger)) {
                $this->logger->info("worker start: {host}:{port}", ['host' => $host, 'port' => $port]);
            } else {
                echo "worker start: " . $host . ":" . $port;
            }

            $server->start();
        });
    }
}

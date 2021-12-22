<?php

namespace Uccu\SwKoaHttp;

use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Swoole\Process\Manager;
use Uccu\SwKoaPlugin\PluginLoader;
use Uccu\SwKoaPlugin\PoolStartBeforePlugin;

class HttpServer implements LoggerAwareInterface, PoolStartBeforePlugin
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
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @var Config $config
     */
    public static $config;

    /**
     * @var Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function __construct(PluginLoader $pluginLoader)
    {
        $this->pluginLoader = $pluginLoader;
    }

    public function poolStartBefore(Manager $manager)
    {

        $host = $this->config->get('app.HOST');
        if (!$host) {
            $host = "0.0.0.0";
        }

        $port = $this->config->get('app.PORT');
        if (!$port) {
            $port = 9501;
        }

        $port = intval($port);

        $workerNum = $this->config->get('app.WORKER_NUM');
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

            $this->logger->info("worker start: {host}:{port}", ['host' => $host, 'port' => $port]);
            $server->start();
        });
    }
}

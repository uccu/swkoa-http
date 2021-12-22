<?php

namespace Uccu\SwKoaHttp;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;

class Context
{

    public  $request;
    public  $response;
    public  $workerId;
    public  $pool;

    function __construct(Request $request, Response $response, Pool $pool, int $workerId)
    {
        $this->request =  $request;
        $this->response =  $response;
        $this->pool =  $pool;
        $this->workerId =  $workerId;
    }
}

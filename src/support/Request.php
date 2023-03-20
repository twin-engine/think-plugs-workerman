<?php

declare (strict_types=1);

namespace plugin\worker\support;

use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Worker;

/**
 * 定制请求管理类
 * @class Request
 * @package plugin\worker\support
 */
class Request extends \think\Request
{
    public function withWorkerRequest(WorkerRequest $request): Request
    {
        $this->get = $request->get();
        $this->post = $request->post();
        $this->host = $request->host();
        $this->file = $request->file() ?? [];
        $this->header = $request->header();
        $this->cookie = $request->cookie();
        $this->method = $request->method();
        $this->request = $this->post + $this->get;
        $this->pathinfo = ltrim($request->path(), '\\/');
        return $this->withInput($request->rawBody())->withServer(array_filter([
            'PATH_INFO'             => $this->pathinfo,
            'HTTP_HOST'             => $request->host(),
            'REQUEST_URI'           => $request->uri(),
            'SERVER_NAME'           => $request->host(true),
            'QUERY_STRING'          => $request->queryString(),
            'REQUEST_METHOD'        => $request->method(),
            'HTTP_X_PJAX'           => $this->header['x-pjax'] ?? null,
            'HTTP_X_REQUESTED_WITH' => $this->header['x-requested-with'] ?? null,
            'HTTP_ACCEPT'           => $this->header['accept'] ?? null,
            'HTTP_ACCEPT_ENCODING'  => $this->header['accept-encoding'] ?? null,
            'HTTP_ACCEPT_LANGUAGE'  => $this->header['accept-language'] ?? null,
            'HTTP_USER_AGENT'       => $this->header['user-agent'] ?? null,
            'HTTP_COOKIE'           => $this->header['cookie'] ?? null,
            'HTTP_CACHE_CONTROL'    => $this->header['cache-control'] ?? null,
            'HTTP_PRAGMA'           => $this->header['pragma'] ?? null,
            'SERVER_SOFTWARE'       => 'Server/' . Worker::VERSION,
            'REQUEST_TIME'          => time(),
            'REQUEST_TIME_FLOAT'    => microtime(true),
        ]));
    }
}
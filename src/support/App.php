<?php

declare (strict_types=1);

namespace plugin\worker\support;

use think\exception\Handle;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;

/**
 * 自定义基础类
 * @class App
 * @package plugin\worker\support
 * @property ThinkCookie $cookie
 * @property ThinkRequest $request
 */
class App extends \think\App
{

    /**
     * 访问请求处理
     * @param TcpConnection $connection
     * @param WorkerRequest $request
     */
    public function worker(TcpConnection $connection, WorkerRequest $request)
    {
        try {
            // 初始化请求
            $this->delete('view');
            $this->db->clearQueryTimes();
            $this->beginTime = microtime(true);
            $this->beginMem = memory_get_usage();
            while (ob_get_level() > 1) ob_end_clean();

            // 切换进程数据
            $this->session->clear();
            $this->session->setId($request->sessionId());
            $this->request->withWorkerRequest($connection, $request);
            $response = $this->cookie->withWorkerResponse();

            ob_start();
            // 执行处理请求
            $thinkres = $this->http->run($this->request);
            $response->withBody(ob_get_clean() . $thinkres->getContent());
            $response->withStatus($thinkres->getCode()) && $this->cookie->save();
            $response->withHeaders($thinkres->getHeader() + ['Server' => 'x-server']);
            if (strtolower($request->header('connection')) === 'keep-alive') {
                $connection->send($response);
            } else {
                $connection->close($response);
            }

            // 结束当前请求
            $this->http->end($thinkres);

        } catch (\RuntimeException|\Exception|\Throwable|\Error $exception) {
            // 其他异常处理
            $this->showException($connection, $exception);
        }
    }

    /**
     * 是否运行在命令行下
     * @return boolean
     */
    public function runningInConsole(): bool
    {
        return false;
    }

    /**
     * 输出异常信息
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \RuntimeException|\Exception|\Throwable $exception
     */
    private function showException(TcpConnection $connection, $exception)
    {
        if ($exception instanceof \Exception) {
            ($handler = $this->make(Handle::class))->report($exception);
            $resp = $handler->render($this->request, $exception);
            $connection->send(new WorkerResponse($resp->getCode(), ['Server' => 'x-server'], $resp->getContent()));
        } else {
            $connection->send(new WorkerResponse(500, ['Server' => 'x-server'], $exception->getMessage()));
        }
    }
}
<?php

declare (strict_types=1);

namespace plugin\worker\support;

use plugin\worker\Monitor;
use plugin\worker\Server;
use think\admin\install\Support;
use think\admin\service\RuntimeService;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Timer;
use Workerman\Worker;

class Http extends Server
{
    /** @var App */
    protected $app;

    /** @var string */
    protected $root;

    /** @var array */
    protected $monitor;

    public function __construct(string $host, int $port, array $context = [])
    {
        $this->port = $port;
        $this->host = $host;
        $this->context = $context;
        $this->protocol = 'http';
        parent::__construct();
    }

    protected function init()
    {
    }

    /**
     * onWorkerStart
     * @param \Workerman\Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {

        // 初始化应用
        $this->app = new App($this->root);
        RuntimeService::init($this->app)->bind('request', Request::class)->initialize();

        // 定时发起数据库请求，防止失效而锁死
        Timer::add(60, function () {
            $this->app->db->query(sprintf('select %d as stime', time()));
        });

        // 设置文件变化及内存超限监控管理
        if (!Support::isWin() && 0 == $worker->id && $this->monitor) {
            Monitor::listen($this->monitor['path'] ?? []);
            Monitor::enableFilesMonitor($this->monitor['files_interval'] ?? 0);
            Monitor::enableMemoryMonitor($this->monitor['memory_interval'] ?? 0, $this->monitor['memory_limit'] ?? null);
        }
    }

    /**
     * onMessage
     * @param TcpConnection $connection
     * @param WorkerRequest $request
     */
    public function onMessage(TcpConnection $connection, WorkerRequest $request)
    {
        if (is_file($file = syspath("public{$request->path()}"))) {
            // 检查 if-modified-since 头判断文件是否修改过
            if (!empty($ifModifiedSince = $request->header('if-modified-since'))) {
                $modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
                // 文件未修改则返回 304
                if ($modifiedTime === $ifModifiedSince) {
                    $connection->send(new WorkerResponse(304, ['Server' => 'x-server']));
                    return;
                }
            }
            // 文件修改过或者没有 if-modified-since 头则发送文件
            $response = (new WorkerResponse())->withFile($file);
            $connection->send($response->header('Server', 'x-server'));
        } else {
            $this->app->worker($connection, $request);
        }
    }

    /**
     * 设置系统根路径
     * @param string $path
     * @return void
     */
    public function setRoot(string $path)
    {
        $this->root = $path;
    }

    /**
     * 设置进程属性配置
     * @param array $option
     * @return void
     */
    public function setOption(array $option)
    {
        if (count($option) > 0) foreach ($option as $key => $val) {
            $this->worker->$key = $val;
        }
    }

    /**
     * 设置运行属性配置
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setStaticOption(string $name, $value)
    {
        Worker::${$name} = $value;
    }

    /**
     * 设置文件监控配置
     * @param integer $interval
     * @param array $path
     * @return void
     */
    public function setMonitorFiles(int $interval = 2, array $path = [])
    {
        $this->monitor['path'] = $path;
        $this->monitor['files_interval'] = $interval;
    }

    /**
     * 设置内存监控配置
     * @param integer $interval
     * @param string|null $limit
     * @return void
     */
    public function setMonitorMemory(int $interval = 60, ?string $limit = null)
    {
        $this->monitor['memory_limit'] = $limit;
        $this->monitor['memory_interval'] = $interval;
    }
}
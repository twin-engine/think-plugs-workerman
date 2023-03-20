<?php

return [
    // 服务监听地址
    'host'    => '127.0.0.1',
    // 服务监听端口
    'port'    => 2346,
    // 套接字上下文选项
    'context' => [],
    // 工作进程参数配置
    'worker'  => [
        'name'  => 'DeAdmin',
        'count' => 4,
    ],
    // 监控文件变更重载
    'files'   => [
        // 监控检测间隔（单位秒，零不监控）
        'time' => 3,
        // 文件监控目录（默认监控 app 目录）
        'path' => [],
    ],
    // 监控内存超限重载
    'memory'  => [
        // 监控检测间隔（单位秒，零不监控）
        'time'  => 60,
        // 限制内存大小（可选单位有 G M K ）
        'limit' => '1G'
    ],
];
<?php

declare (strict_types=1);

namespace plugin\worker;

use plugin\worker\command\Worker;
use think\admin\Plugin;

class Service extends Plugin
{
    protected $package = 'rotoos/think-workerman';

    public function register()
    {
        $this->commands(['xadmin:worker' => Worker::class]);
    }

    public static function menu(): array
    {
        return [];
    }
}
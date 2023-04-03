<?php

declare (strict_types=1);

namespace plugin\worker;

/**
 * 插件安装器事件处理
 * @class Event
 * @package plugin\worker
 */
abstract class Event
{
    public static function onRemove()
    {
        @unlink('config/worker.php');
    }
}
<?php
namespace CjsConsole\Scheduling;

/**
 * 计划,时间表
 */
class Schedule {
    //所有计划表事件
    protected $events = [];

    public function call($callback, array $parameters = array())
    {
        $this->events[] = $event = new CallbackEvent($callback, $parameters);
        return $event;
    }

    public function command($command)
    {//调用php的artisan文件 参数是命令
        $artisan = \CjsConsole\ConsoleConfig::getInstance()->getPrefixArtisan()?:' artisan ';
        return $this->exec(sprintf('%s %s %s' ,PHP_BINARY, trim($artisan), $command));
    }

    public function exec($command)
    {
        $this->events[] = $event = new Event($command);
        return $event;
    }

    //所有事件
    public function events()
    {
        return $this->events;
    }

    //到期,执行所有事件, 在ScheduleRunCommand类的fire()中有调用
    public function dueEvents($app = null)
    {
        return array_filter($this->events, function($event) use ($app)
        {
            return $event->isDue($app);
        });
    }


}
<?php
require_once __DIR__ . '/common.php';

chdir(__DIR__ . '/');
echo getcwd() . PHP_EOL;
$command = PHP_BINARY.' test.php ';
//$event = new \CjsConsole\Scheduling\Event($command);
//$event = $event->hourly(); //设置每小时执行一次，返回Event对象

$event = (new \CjsConsole\Scheduling\Event(PHP_BINARY . ' ./test.php'))->cron("* * * * * *");
if(\CjsConsole\isWin()) {
    $event->sendOutputTo(__DIR__ . '/devnull');
}
$event->run();

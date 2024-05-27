#!/usr/bin/env php
<?php
require_once __DIR__ . '/common.php';


\CjsConsole\ConsoleConfig::getInstance()->setEnvironments('dev')->setDebug(true);


$input = new \CjsConsole\input\ArgvInput();
$ConsoleObj = \CjsConsole\ConsoleApp::getInstance("cjs console", '1.0.0');
$kernel = new \ConsoleDemo\Kernel($ConsoleObj);

//添加命令 -- 新建工程命令:  artisan new 项目名
$ConsoleObj->add(new \CjsConsole\Command\NewCommand());

$status = $kernel->handle($input, new \CjsConsole\Output\ConsoleOutput());
$kernel->terminate($input, $status);

exit($status);


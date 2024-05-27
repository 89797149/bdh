<?php
require_once dirname(__DIR__) . '/common.php';

use \CjsConsole\Input\InputArgument;
$argObj = new InputArgument('command', InputArgument::REQUIRED, 'The command to execute');
echo $argObj->getName() . PHP_EOL;

var_dump($argObj->isRequired());//判断参数是否必须
echo PHP_EOL;

$argObj = new InputArgument('参数名1', 2, '参数描述,在cli中,参数名1可选');
echo $argObj->getName() . PHP_EOL;

var_dump($argObj->isRequired());//判断参数是否必须
echo PHP_EOL;

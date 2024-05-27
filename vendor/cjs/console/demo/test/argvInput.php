<?php
require_once dirname(__DIR__) . '/common.php';

use \CjsConsole\Input\ArgvInput;

//cli参数分析:  php test/argvInput.php  a b c --hi=123
$arginput = new ArgvInput($_SERVER['argv']);

echo $arginput->getFirstArgument() . PHP_EOL; //获取第1个参数

var_dump($arginput->hasParameterOption('--hi')); //判断参数和选项是否存在
var_dump($arginput->hasParameterOption('b')); //判断参数和选项是否存在
var_dump($arginput->hasParameterOption('foo')); //判断参数和选项是否存在

echo $arginput->getParameterOption('--hi') . PHP_EOL; //123



echo $arginput . PHP_EOL;//a b c --hi=123

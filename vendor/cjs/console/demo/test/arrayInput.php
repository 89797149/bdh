<?php
require_once dirname(__DIR__) . '/common.php';

use \CjsConsole\Input\ArrayInput;

$input = new ArrayInput(array('name' => 'foo', '--bar' => 'foobar', 'xyz'=>'hi123'));

echo $input->getFirstArgument() . PHP_EOL; //foo 获取第一个参数值


echo $input . PHP_EOL;//foo --bar=foobar hi123



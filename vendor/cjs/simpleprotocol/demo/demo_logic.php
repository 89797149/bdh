<?php
require_once __DIR__ . '/common.php';

$logicRes = \CjsProtocol\LogicResponse::getInstance()->setCode(0);

echo $logicRes->toJson() . PHP_EOL;
echo $logicRes->toJson4EmptyDataObj() . PHP_EOL;

$logicRes->setMsg("fail")->setData(['is_ok'=>false]);
var_export($logicRes->toArray());
echo PHP_EOL;
echo $logicRes->toJson4EmptyDataObj() . PHP_EOL;


<?php
require_once __DIR__ . '/common.php';

\CjsProtocol\ApiResponse::getInstance()->setTraceId("abc-xyz-123-win8");

echo \CjsProtocol\ApiResponse::getInstance()->toJson() . PHP_EOL;

$apiObj = \CjsProtocol\ApiResponse::getInstance();
$apiObj->setCode(100)->setMsg("fail")->setData(['is_ok'=>false]);
var_export($apiObj->toArray());
echo PHP_EOL;
echo $apiObj->toJson() . PHP_EOL;

echo $apiObj->setCode(0)->toJson() . PHP_EOL;
echo $apiObj->resetCMD()->setMsg("ok")->setTraceId('traceid')->toJson() . PHP_EOL;

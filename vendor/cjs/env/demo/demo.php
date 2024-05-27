<?php
require_once __DIR__ . '/common.php';

\CjsEnv\EnvLoader::load(__DIR__, '.env.example');

echo \CjsEnv\env("APP_LOG_LEVEL") . PHP_EOL;

echo \CjsEnv\env('APP_ENV') . PHP_EOL;

echo \CjsEnv\env('USER_NAME', 'root') . PHP_EOL;

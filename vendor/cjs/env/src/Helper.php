<?php
require_once __DIR__ . '/Util.php';

if (!function_exists('env')) {

    function env($key, $default = null)
    {
        return \CjsEnv\env($key, $default);
    }

}

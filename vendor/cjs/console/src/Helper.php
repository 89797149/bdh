<?php
namespace CjsConsole;

use Closure;
use CjsConsole\ConsoleConfig;
function base_path(){

    $path = ConsoleConfig::getInstance()->getCrontabEntryPath();
    return $path;

}


/**
 * 是否维护状态
 */
function isDownForMaintenance()
{
    return ConsoleConfig::getInstance()->isDownForMaintenance();
}

function share(Closure $closure)
{
    return function($container) use ($closure)
    {
        static $object;
        if (is_null($object))
        {
            $object = $closure($container);
        }
        return $object;
    };
}


function isWin() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return true;
    } else {
        return false;
    }
}

function debug($str) {
    if(is_array($str)) {
        $str = var_export($str, true);
    }
    if(isWin()) {
        $str = mb_convert_encoding($str, 'gbk', 'utf-8');    
    }
    echo $str;
}

function str_contains($haystack, $needles)
{
    foreach ((array) $needles as $needle)
    {
        if ($needle != '' && strpos($haystack, $needle) !== false) return true;
    }
    return false;
}


function starts_with($haystack, $needles)
{
    foreach ((array) $needles as $needle)
    {
        if ($needle != '' && strpos($haystack, $needle) === 0) return true;
    }
    return false;
}

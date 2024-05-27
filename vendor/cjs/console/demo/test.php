<?php

//file_put_contents(__DIR__ . '/tmp.log', mt_rand(0, 9999), FILE_APPEND);
/**
    array (
        0 => 'make',
        1 => 'make:controller',
        2 => 'make:controller:hi',
    )
 */
$a = '';
var_dump(array_shift($a));
//var_export(extractAllNamespaces('make:controller:hi:xyz'));
echo php_uname('s') . PHP_EOL; //操作系统名称: Darwin

function extractAllNamespaces($name)
{
    //则返回除了最后一个元素外的所有元素
    $parts = explode(':', $name, -1);
    $namespaces = array();
    foreach ($parts as $part) {
        if (count($namespaces)) {
            $namespaces[] = end($namespaces).':'.$part;
        } else {
            $namespaces[] = $part;
        }
    }
    return $namespaces;
}



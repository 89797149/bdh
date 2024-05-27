<?php
/**
 *
/usr/bin/php argv.php a 1 b=2 --c=3 --d 4
array (
    0 => 'argv.php',
    1 => 'a',
    2 => '1',
    3 => 'b=2',
    4 => '--c=3',
    5 => '--dd',
    6 => '4',
)
说明以空格分割命令行
 */
var_export($_SERVER['argv']);


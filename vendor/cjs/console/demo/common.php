<?php
$vendorFile = dirname(__DIR__)  .  '/vendor/autoload.php';
if(file_exists($vendorFile)) {
    require $vendorFile;
} else {
    require_once dirname(__DIR__) . '/src/Helper.php';
    spl_autoload_register(function ($class) {
        $ns = 'CjsConsole';
        $base_dir = dirname(__DIR__) . '/src';
        $prefix_len = strlen($ns);
        if (substr($class, 0, $prefix_len) !== $ns) {
            return;
        }
        // strip the prefix off the class
        $class = substr($class, $prefix_len);
        // a partial filename
        $file = $base_dir .str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        if (is_readable($file)) {
            require $file;
        }

    });

    $_cronTmpDir = dirname(dirname(__DIR__)) . '/cjsCron/';
    if(is_dir($_cronTmpDir)) {
        spl_autoload_register(function ($class) use ($_cronTmpDir) {
            $ns = 'CjsCron';
            $base_dir = $_cronTmpDir . '/src';
            $prefix_len = strlen($ns);
            if (substr($class, 0, $prefix_len) !== $ns) {
                return;
            }
            // strip the prefix off the class
            $class = substr($class, $prefix_len);
            // a partial filename
            $file = $base_dir .str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            if (is_readable($file)) {
                require $file;
            }

        });
    }

}

spl_autoload_register(function ($class) {
    $ns = 'ConsoleDemo';
    $base_dir = __DIR__ . '/';
    $prefix_len = strlen($ns);
    if (substr($class, 0, $prefix_len) !== $ns) {
        return;
    }
    // strip the prefix off the class
    $class = substr($class, $prefix_len);
    // a partial filename
    $file = $base_dir .str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_readable($file)) {
        require $file;
    }

});

if ( ! function_exists('env'))
{
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default instanceof \Closure ? $default() : $default;
        }
        switch (strtolower($value))
        {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'null':
            case '(null)':
                return null;

            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}
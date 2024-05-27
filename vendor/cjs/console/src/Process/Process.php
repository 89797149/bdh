<?php
namespace CjsConsole\Process;

//只是写的一个process执行demo
class Process{

    private $cmd;
    private $cwd;

    public function __construct()
    {

    }


    public function setCmd($cmd) {
        $this->cmd = $cmd;
        return $this;
    }

    public function setCwd($cwd = null) {
        $this->cwd = $cwd;
        if (null === $this->cwd && (defined('ZEND_THREAD_SAFE') || '\\' === DIRECTORY_SEPARATOR)) {
            $this->cwd = getcwd();
        }
        return $this;
    }

    public function __destruct()
    {

    }

    public function run()
    {
        $content = '';
        $cmd = $this->cmd;
        $handle = popen($cmd, 'r');
        while (!feof($handle)) {
            $content .= fread($handle, 2096);
        }
        pclose($handle);
        return $content;

    }


}
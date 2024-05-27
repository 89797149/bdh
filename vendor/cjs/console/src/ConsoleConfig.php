<?php
namespace CjsConsole;

class ConsoleConfig {

    protected static $instance = null;

    protected $_crontabEntryPath = null;

    protected $_isDownForMaintenance=false; //是否维护状态

    protected $environments = 'production'; //当前运行环境
    
    protected $debug = false;   //是否开启debug

    protected $prefixArtisan = "artisan"; //artisan命令前缀  如 artisan user_service

    protected function __construct()
    {

    }

    public static function getInstance() {
        if(static::$instance) {
            return static::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param boolean $isDebug
     */
    public function setDebug($debug = true)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefixArtisan()
    {
        return $this->prefixArtisan?:'artisan';
    }

    /**
     * @param string $prefixArtisan
     */
    public function setPrefixArtisan($prefixArtisan)
    {
        $this->prefixArtisan = $prefixArtisan;
        return $this;
    }

    
    public function getCrontabEntryPath() {
        return $this->_crontabEntryPath;
    }


    public function setCrontabEntryPath($path)
    {
        $this->_crontabEntryPath = $path;
        return $this;

    }

    /**
     * @param boolean $isDownForMaintenance
     */
    public function setIsDownForMaintenance($isDownForMaintenance)
    {
        $this->_isDownForMaintenance = $isDownForMaintenance;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDownForMaintenance()
    {
        return $this->_isDownForMaintenance;
    }

    /**
     * @return string
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * @param string $environments
     */
    public function setEnvironments($environments)
    {
        $this->environments = $environments;
        return $this;
    }




}
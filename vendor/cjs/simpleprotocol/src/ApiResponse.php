<?php
namespace CjsProtocol;

class ApiResponse extends CmdProtocol
{
    protected $trace_id;
    //单例
    public static function getInstance() {
        static $instance = null;
        if(is_null($instance)) {
            $instance = new static();
        }
        return $instance;
    }

    public function getTraceId()
    {
        return $this->trace_id;
    }

    public function setTraceId($trace_id)
    {
        $this->trace_id = $trace_id;
        return $this;
    }

    public function toArray() {
        $data = $this->getData();
        if(empty($data)) {
            $data = new \stdClass();
        }
        $ary = [
            'code'=>$this->getCode(),
            'msg'=>$this->getMsg(),
            'data'=>$data,
            'trace_id'=>$this->getTraceId(),
        ];
        return $ary;
    }

    public function toJson() {
        $ary = $this->toArray();
        return json_encode($ary, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

}

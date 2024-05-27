<?php
namespace CjsProtocol;

//业务逻辑层返回code、msg、data协议格式
class LogicResponse extends CmdProtocol
{
    //非单例
    public static function getInstance() {
        return new static();
    }

    public function toArray() {
        $ary = [
            'code'=>$this->getCode(),
            'msg'=>$this->getMsg(),
            'data'=>$this->getData(),
        ];
        return $ary;
    }

    public function toJson() {
        $ary = $this->toArray();
        return json_encode($ary, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    public function toJson4EmptyDataObj() {
        $ary = $this->toArray();
        if(empty($ary['data'])) {
            $ary['data'] = new \stdClass();
        }
        return json_encode($ary, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
}

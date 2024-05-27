<?php
namespace CjsProtocol;

class CmdProtocol
{
    protected $code = 0;
    protected $msg = 'success';
    protected $data = '';

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function setMsg($msg)
    {
        $this->msg = $msg;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function resetCMD() {
        $this->code = 0;
        $this->msg = 'success';
        $this->data = '';
        return $this;
    }

}
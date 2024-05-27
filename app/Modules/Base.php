<?php
namespace App\Modules;

abstract class Base
{
    //每次实例化新的对象
    public static function getInstance() {
        return new static();
    }

}

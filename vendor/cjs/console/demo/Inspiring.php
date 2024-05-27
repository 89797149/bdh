<?php
namespace ConsoleDemo;


class Inspiring {


    public static function quote()
    {
        //随机取一个单元
        $ret = self::random([
            '1.hello world',
            '2.hi jelly',
            '3.hi tom',
            '4.what,why,who',
            '5.fine,nice',

        ]);

        return $ret;
    }


    protected static function random($ary)
    {
        $keys = array_rand($ary, 1);
        return $ary[$keys];
    }
}

<?php
namespace V3\Action;

// 调试
class FooAction extends BaseAction{

    public function foo1(){
        // 所有 身份
        $cost = 2;
        $con = [
            'isDelete' => 0,
            'rankCost' => ['elt',$cost]
        ];
        $res = M('rank')
            ->where($con)
            ->find();
        dump($res);
    }

}
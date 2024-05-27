<?php

// 所有API接口参数统一配置校验
/*

开启 validate 校验API参数

在config.php中增加以下配置

'api_validate' => true,

 */
return array(
    'User.addUser' => array(
        'rule' => array(
//            'name' => 'require|length:1,30',
            'pass' => 'require|length:1,30',
            'username' => 'require|length:1,30',
            'phone' => array('require','regex'=>'/1[345789]\d{9}$/'),
//            'email' => array('require','regex'=>'/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/'),
        ),
        'field' => array(
//            'name' => '账号',
            'pass' => '密码',
            'username' => '用户姓名',
            'phone' => '手机号',
//            'email' => '邮箱',
        ),
    ),

    'User.edit' => array(
        'rule' => array(
            'username' => 'require|length:1,30',
            'pass' => 'length:1,30',
            'phone' => array('require','regex'=>'/1[345789]\d{9}$/'),
//            'email' => array('require','regex'=>'/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/'),
        ),
        'field' => array(
            'username' => '用户姓名',
            'phone' => '手机号',
            'pass' => '密码',
//            'email' => '邮箱',
        ),
    ),
    //店铺报表
    'Orders.getOrderSumFields' => array(
        'rule' => array(
            'defPreSale' => 'number|in:0,1',
        ),
        'field' => array(
            'defPreSale' => '是否预售订单',
        ),
    ),

    //pos
//    'Pos.getShopCatsList' => array(
//        'rule' => array(
//            'shopId' => 'require|number',
//        ),
//        'field' => array(
//            'shopId' => '店铺id',
//        ),
//    ),
    'Pos.getShopGoodsList' => array(
        'rule' => array(
            'SuppPriceDiff' => 'in:1,-1',
        ),
        'field' => array(
            'SuppPriceDiff' => '是否称重补差价',
        ),
    ),
    'ShopsCats.addCats' => array(
        'rule' => array(
            'parentId' => 'number',
            'catName' => 'require',
            'catSort' => 'require|number',
            'isShow' => 'number|in:0,1',
        ),
        'field' => array(
            'parentId' => '上级id',
            'catName' => '分类名称',
            'catSort' => '排序',
            'isShow' => '是否显示',
        ),
    ),

);
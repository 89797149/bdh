<?php
return array(
    //'DEFAULT_TIMEZONE'      =>  'PRC',  // 默认时区
    //'URL_MODEL' => '2',
    'URL_CASE_INSENSITIVE' => true,//URL不区分大小写为了兼容liunx服务器
    'VAR_PAGE' => 'p',
    'PAGE_SIZE' => 15,

    'DB_TYPE' => 'mysqli',//如server支持pdo可采用pdo

    // 本地的
    /*'DB_HOST'=>'127.0.0.1',
    'DB_NAME'=>'niaocmsdmd',
    'DB_USER'=>'root',
    'DB_PWD'=>'',*/


    // --- 线上的 --- start ---
    'DB_HOST' => env('DB_HOST', '127.0.0.1'),
    'DB_NAME' => env('DB_NAME', ''),
    'DB_USER' => env('DB_USER', ''),
    'DB_PWD' => env('DB_PWD', ''),
    'DB_PORT' => env('DB_PORT', '3366'),
    // --- 线上的 --- end ---

    'DB_PREFIX' => 'wst_',

    'DEFAULT_C_LAYER' => 'Action',
    'DEFAULT_CITY' => '310115',//行政区划代码
    'DATA_CACHE_SUBDIR' => true,
    'DATA_PATH_LEVEL' => 2,
    'SESSION_PREFIX' => 'NIAOMALL',
    'COOKIE_PREFIX' => 'NIAOMALL',
    'LOAD_EXT_CONFIG' => 'wst_config',

    'DB_BIND_PARAM' => false,//是否自动绑定参数  我挺喜欢手动的

    //设置允许的模块和默认模块配置
    'MODULE_ALLOW_LIST' => array('Home', 'Merchantapi', 'V3', 'Adminapi', 'Made'),
    'DEFAULT_MODULE' => 'Home',


    'api_md5' => '4b373e32f250ce370dd1bf8aeca266b2',//api接口加密


    define('WEB_HOST', WSTDomain()),
    //密钥 给支付接口加密
    'data_key' => 'C30B2E4382443E2C',
    //七牛上传配置
    'UPLOAD_SITEIMG_QINIU' => array(
        'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
        'exts' => array('jpg', 'png', 'gif', 'jpeg'), //允许上传的文件后缀
        'rootPath' => './Upload/', //保存根路径
        'saveName' => array('uniqid', ''),
        'driver' => '',
        /*'driver' => 'Qiniu',
        'driverConfig' => array (
            'accessKey' => '-jdFmlrdhC92WCc6AlOMNLLNjNqkvPCF4RQiduN2',
            'secrectKey' => '6ChDoSLUsT_34IWmWN4mjMIUJQQOh3MjtwwT3L4Z',
            'domain' => 'pwzvhtz9z.bkt.clouddn.com',
            'bucket' => 'test',
        ),*/
        'driverConfig' => array(
            'accessKey' => '',
            'secrectKey' => '',
            'domain' => '',
            'bucket' => '',
        )

    ),

    'redis_host1' => '127.0.0.1',
    'redis_port1' => '6379',
    /*
         //'配置项'=>'配置值' redis配置
         'DATA_CACHE_PREFIX' => 'Redis_',//缓存前缀
         'DATA_CACHE_TYPE'=>'Redis',//默认动态缓存为Redis
         'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
         'REDIS_HOST'=>'127.0.0.1', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
         'REDIS_PORT'=>'6379',//端口号
         'REDIS_TIMEOUT'=>'300',//超时时间
         'REDIS_PERSISTENT'=>false,//是否长连接 false=短连接
         'REDIS_AUTH'=>'',//AUTH认证密码
         'DATA_CACHE_TIME'=> 10800,      // 数据缓存有效期 0表示永久缓存
    */
    /*
         //redis配置
        'REDIS_HOST'=>'ip地址',
        'REDIS_PORT'=>'6379', //默认6379
        'REDIS_AUTH'=>'',
        'DATA_CACHE_TIMEOUT' => 300,
         */
    //支付宝支付配置
    /*"alipayConfig" => array (
        //签名方式,默认为RSA2(RSA2048)
        'sign_type' => "RSA2",

        //支付宝公钥
        'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlXcw9fqkIgenqOaIHgOSo42aCLPdoVJBnZN+vKAngiBMdavac88XtOStXuqGHemNFi6HUY/DRhnyypdCeGnKvLQYToKn1gvAPttQ++kRfnkS0UyLsdfB8KYA4KqjC+eBMkccMOcR2Z4aXrczsWm6SInCEgvdDpmW0DngEliMK2xyc4ZRe7f2SsWcptqnW1S9Qcaj+9hMvHtZJw+jhivqjp6zgKlwbSC+oHQEHWkdCtNW75uE9d+UhFy67T+iNFKoVbhR+TN/nsLGVibEJo3GYEsF2ZMUAqlaoVJU8at94VztEHRCTnO0dbmnaWirqwGny/nHk9PS0zBweuFNg6rLFwIDAQAB",

        //商户私钥
        'merchant_private_key' => "MIIEpAIBAAKCAQEA9zUSgb7VPcoyc+VbKBgw7svYFu0+lHrlxHzZa8NSZrbw6nK1i6TDAdPx5Q/GrRfaGYDnPnNwuUxfypzt4uDXHzx1iRKfwbDffQ375MMa8c2+zUXcf24crq7qV5oID7Q0Rc8vf6NFHRVhx6zjiMFE9VrRfAQyq9xvPBysL3lzCNY9ygH9ab65yRD0Lu1pBZCWHCbOlfoD14dI+WMgHLZTvKxhexxcFNboVGudEvFcga3I3iGAYSedDS6mGLRa+HRKewLxZ6KakzX1UGI+vfnxHzckk53+JEhW4mF4YSRf2DlUS6t14cqKIFf25ukNePUPuEMm5UzPLy19eWZRNvRyewIDAQABAoIBAQCQa2n/cIxFh+3HtXN2zgMwTthUNI+90LqQ+ttOUJLAPRor7Y3QIhZm5/pGdtv73ZZmFd+CpZByQIMp7FoxlGc48Wo9BStdzcYS2euR7sW8BBX2+Fxj+eE50ChJu0wAWKe9LNgz+h9zaT1xmLho+Xge2srNZ+puat51QM77ATnKK7GH6GE8XD508xuznw5YQh7mSLjTlV0//Ze0baSTWJ9LivQpibacy/ARKnYgWOoAvMTPx4cylyGIj7rybNHOtSk7y6UglfW80Nk1Lep7AOk1Q3sQ0tI/ESc9fSQMBsXu3oA58YIedrFzk3zxQ6+wRxAT+3betO62mitIc+6xU52BAoGBAPuVOa/HQR+s9AKug0bTuBNPSXqN3jaihPGwo9C2YDmlxByX0NpBTGGYE9HVf2IPVwX8spdOwe+YLWp0RVJrPWXiWC6n/GVKgAXZz5/CbyX4GEK4R/ryZRbbhxcy4CnoKa/K9Mej/T4mamUY0eYFvzb1IZCjfrtZSNZOZ4fDuSG7AoGBAPuMLiKi+AYBi2CY1bC3++oAUYlAv1E02z3DI3OUYkRK5g0ba8LT2gjWfxL/xt2jNEhlgZy/GLXS32PW7jgufT2IHMpGoe7zruhsGeGehObHlOFwsIgev6uGhrW3DNdjeJ4WbeSHLCSjg51CqaHS354FvKaheXjt8CQSRImxsYZBAoGBAKDIseFhGoG/6wJ7vXJahN3yYids24NXQlekaE1PARhWlIshi1yxNrt4kdIc/BgTba5p3UlOECurufq67ELMPqUKjwjiWy/w+PjERyj2/knp0LdzRq1elLSTADcXUKP3uAydTOr6JzK9ImoR/rNfIOFisFzb4HajjSVJXmkp8PtZAoGADL8ww06Q1PUVFAMKqRbZrCTx/MuMJlhQV9PgowW86QsGl1lxX4EOxm3gKJ1PfYG8r4J9S+0fGm+iJFQK5EvuysRv+QwVPp+YmGyJ7zXyNCOe9sGYIH22ZsG9Z83r16pRvWkTjoYPBZhHnht3rxyZek9+HM+H8UnVZm6KjJO1lEECgYAvZCPX2BnzqCQMgfJ0FMTNlGfuhKAeXWg/YvAlIVgd2Oh5QCiycOGeVgcqKzhoIRrIrcJUWOVkbOW5dJVpZaySjPGduQ0or2qU120wDRve48G+ameVLi1eq5U1W5MkQ8Qllu3zNxH9c8gJ6Z6h7vimzunyoNZhJ0FxsZTZ/e3MlA==",

        //编码格式
        'charset' => "UTF-8",

        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

        //应用ID
        'app_id' => "2016122404571752",

        //异步通知地址,只有扫码支付预下单可用
        'notify_url' => "http://www.baidu.com",

        //最大查询重试次数
        'MaxQueryRetry' => "10",

        //查询间隔
        'QueryDuration' => "3"
    ),*/
    //需要被sku属性替换的字段
    'replaceSkuField' => array(
        'skuShopPrice' => 'shopPrice',
        'skuMemberPrice' => 'memberPrice',
        'skuGoodsStock' => 'goodsStock',
        'skuGoodsImg' => 'goodsImg',
        'skuBarcode' => 'goodsSn',
        //'skuMarketPrice' => 'marketPrice', //暂时先去掉吧，不知道为什么前端没有这个字段了
        'skuGoodsImg' => 'goodsThums',
        'minBuyNum' => 'minBuyNum',
        'weigetG' => 'weigetG',
        'UnitPrice' => 'UnitPrice',
        'unit' => 'unit',
    ),

    //订制数据库
    //'DB_CONFIG2' => 'mysql://root:123@192.168.1.10:3306/webpage#utf8');
    'made_db' => 'mysql://made:TtRrj2zDXpAEXLH2@47.114.136.2:3306/made#utf8',
    //ERP数据库信息
    'sqlserver_db' => [
        'host' => 'hz1.db.gjpcloud.com',
        'port' => '1131',
        'username' => 'vvwzh10818',
        'pw' => '!885ff8s9g99jhth',
        'dbname' => 'FZYUN092428',
    ]
);

?>

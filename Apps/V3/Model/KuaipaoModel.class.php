<?php
namespace V3\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 自建物流服务类
 */
class KuaipaoModel extends BaseModel {

    // 开发密钥dev_key
    private $dev_key;
    // 签名密钥dev_secret
    private $dev_secret;

    public function __construct(){
        $this->dev_key = $GLOBALS['CONFIG']['dev_key'];
        $this->dev_secret = $GLOBALS['CONFIG']['dev_secret'];
    }

    /**
     * 数组 转 对象
     *
     * @param array $arr 数组
     * @return object
     */
    public function array_to_object($arr) {
        if (gettype($arr) != 'array') {
            return;
        }
        foreach ($arr as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object') {
                $arr[$k] = (object)array_to_object($v);
            }
        }

        return (object)$arr;
    }

    /**
     * 对象 转 数组
     *
     * @param object $obj 对象
     * @return array
     */
    public function object_to_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        } if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = $this->object_to_array($value);
            }
        }
        return $array;
    }

    

    /*
    *获取配送员位置
    */
    public function getOrderInfo($orderNo,&$res=null,&$info=null,&$error=null){
        $para = array(
            'trade_no' => $orderNo
        );
        $sdk = new \Org\Util\KeloopCnSdk3($this->dev_key, $this->dev_secret);
        $result = $sdk->getCourierTag($para);
        // 业务逻辑处理
        if (is_null($result)) {
            exit('获取订单信息接口异常');
        } else if (is_array($result)) {
            if ($result['code'] == 200) {
                // $data = $result['data'];
                // var_dump($data);
                // exit('success');
                $res = $result['data'];
            } else {
                $error = $result['message'];
                // exit('错误信息：' . $result['message']);
            }
        } else {
            // exit('接口调用异常');
            $info = '接口调用异常';
        }
    }

    /*
        *验证签名
        */
    public function checkSign($para){
        $KeloopCnSdk3 = new \Org\Util\KeloopCnSdk3($GLOBALS['CONFIG']['dev_key'], $GLOBALS['CONFIG']['dev_secret']);
        // 调用 getOrderInfo 方法
        return $KeloopCnSdk3->checkSign($para);

    }


}

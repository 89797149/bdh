<?php
namespace Home\Model;
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

    /**
     * 创建订单
     */
    public function createOrder($data,&$res=null,&$info=null,&$error=null){

        $KeloopCnSdk3 = new \Org\Util\KeloopCnSdk3($this->dev_key, $this->dev_secret);

        // 封装参数
        $para = array(

            // 团队Token
            'team_token' => $data['team_token'],

            // 第三方系统中的商户 ID
            'shop_id' => $data['shop_id'],
            // 第三方系统中的商户名称
            'shop_name' => $data['shop_name'],
            // 第三方系统中的商户电话
            'shop_tel' => $data['shop_tel'],
            // 第三方系统中的商户地址
            'shop_address' => $data['shop_address'],
            // 第三方系统中的商户坐标火星坐标 '104.181909,30.679741'
            'shop_tag' => $data['shop_tag'],

            // 'note' => 'great',
            // 'order_content' => '2份烧白开(100x1),2份拉面(18x1)',
            // 'order_note' => '不要太辣了',
            // 'order_mark' => '12',
            // 'order_from' => '美团外卖',
            // 'order_send' => '下午六点钟之前送达',
            // 'order_no' => '12283245',
            // 'order_time' => '2016-12-31 23:59:59',
            // 'order_photo' => 'http://a4.att.hudong.com/38/47/19300001391844134804474917734_950.png',
            // 'order_price' => 99.99,

            // 订单客户对应配送订单的收单人 姓名
            'customer_name' => $data['customer_name'],
            // 订单客户电话
            'customer_tel' => $data['customer_tel'],
            // 订单客户地址 '成都市金牛区蓝海天地1栋421'
            'customer_address' => $data['customer_address'],
            // 订单客户坐标火星坐标 '104.081909,30.779741'
             'customer_tag' => $data['customer_tag'],

            // 订单单号，请使用string类型，否则长数字将会自动转换成科学计数法
            'order_no' => $data['order_no'],


            // 订单支付方式：0 表示已支付、1 表示货到付款
            'pay_status' => $data['pay_status'],
            // 'pay_type' => 2,
            // 'pay_fee' => 6.66
        );
        // 创建 SDK 实例

        // 调用 createOrder 方法
        $result = $KeloopCnSdk3->createOrder($para);
        // 业务逻辑处理
        if (is_null($result)) {
            exit('创建订单接口异常');
        } else if (is_array($result)) {
            if ($result['code'] == 200) {
                // $tradeNo = $result['data']['trade_no'];
                // TODO: => 将 $tradeNo 保存到数据库中，以待调用其他接口时使用
                // var_dump($tradeNo);
                // exit('success');
                $res = $result['data'];
            } else {
                $error=$result['message'];
                // exit('错误信息：' . $result['message']);
            }
        } else {
            $info='接口调用异常';
            // exit('接口调用异常');
        }




    }

    /*
    *获取订单详情
    */
    public function getOrderInfo($orderNo,&$res=null,&$info=null,&$error=null){
        $para = array(
            'trade_no' => $orderNo
        );
        // 创建 SDK 实例
        $sdk = new \Org\Util\KeloopCnSdk3($this->dev_key, $this->dev_secret);
        // 调用 getOrderInfo 方法
        $result = $sdk->getOrderInfo($para);
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

    /**
     * @param $orderNo
     * @return array
     * 撤销配送订单接口
     */
    public function cancelOrder($orderNo){
        $para = array(
            'trade_no' => $orderNo
        );
        // 创建 SDK 实例
        $sdk = new \Org\Util\KeloopCnSdk3($this->dev_key, $this->dev_secret);
        // 调用 cancelOrder 方法
        $result = $sdk->cancelOrder($para);
        $data = [];
        // 业务逻辑处理
        if (is_null($result)) {
            $info = '撤销配送订单接口异常';
            $data['message'] = $info;
            $data['code'] = 0;
            $data['data'] = [];
        } else if (is_array($result)) {
            $data = $result;
        } else {
            $info = '接口调用异常';
            $data['message'] = $info;
            $data['code'] = 0;
            $data['data'] = [];
        }
        return (array)$data;
    }

}

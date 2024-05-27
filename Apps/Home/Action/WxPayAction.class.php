<?php

namespace Home\Action;

use App\Modules\Pay\PayModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;
use Think\Controller;

/**
 * 微信支付 PS:该文件在服务存在一个修改之前的副本
 */
class WxPayAction extends BaseAction
{
    /**
     * 初始化
     */
    private $wxpayConfig;
    private $wxpay;

    public function _initialize()
    {
        header("Content-type: text/html; charset=utf-8");
        vendor('WxPay.WxPayConf');
        vendor('WxPay.WxQrcodePay');

        $this->wxpayConfig = C('WxPayConf');
        $m = D('Home/Payments');
        $this->wxpay = $m->getPayment("weixin");
        $this->wxpayConfig ['appid'] = $this->wxpay ['appId']; // 微信公众号身份的唯一标识
        $this->wxpayConfig ['appsecret'] = $this->wxpay ['appsecret']; // JSAPI接口中获取openid
        $this->wxpayConfig ['mchid'] = $this->wxpay ['mchId']; // 受理商ID
        $this->wxpayConfig ['key'] = $this->wxpay ['apiKey']; // 商户支付密钥Key
        $this->wxpayConfig ['notifyurl'] = $this->wxpayConfig ['NOTIFY_URL'];
        $this->wxpayConfig ['returnurl'] = "";
        // 初始化WxPayConf_pub
        $wxpaypubconfig = new \WxPayConf ($this->wxpayConfig);
    }

    public function createQrcode()
    {
        $pkey = base64_decode(I("pkey"));

        $pkeys = explode("@", $pkey);
        $pflag = true;
        if (count($pkeys) != 3) {
            $this->assign('out_trade_no', "");
        } else {
            $morders = D('Home/Orders');
            $obj ["uniqueId"] = $pkeys [1];
            $obj ["orderType"] = $pkeys [2];
            $data = $morders->getPayOrders($obj);

            $orders = $data ["orders"];
            $needPay = $data ["needPay"];
            if ($needPay > 0) {

                $this->assign("orders", $orders);
                $this->assign("needPay", $needPay);
                $this->assign("orderCnt", count($orders));

                // 使用统一支付接口
                $wxQrcodePay = new \WxQrcodePay ();
                $wxQrcodePay->setParameter("body", "支付订单费用"); // 商品描述
                $timeStamp = md5((string)uniqid() . (string)microtime() . (string)rand(9999, 999999) . (string)time());
                $out_trade_no = "$timeStamp";
                //$out_trade_no = "1000001|1000002";
                $wxQrcodePay->setParameter("out_trade_no", "$out_trade_no"); // 商户订单号
                $wxQrcodePay->setParameter("total_fee", $needPay * 100); // 总金额
                $wxQrcodePay->setParameter("notify_url", C('WxPayConf.NOTIFY_URL')); // 通知地址
                $wxQrcodePay->setParameter("trade_type", "NATIVE"); // 交易类型
                $wxQrcodePay->setParameter("attach", "$pkey"); // 附加数据
                //$wxQrcodePay->setParameter ( "detail", "" );//附加数据
                $wxQrcodePay->SetParameter("input_charset", "UTF-8");
                // 获取统一支付接口结果
                $wxQrcodePayResult = $wxQrcodePay->getResult();

                // 商户根据实际情况设置相应的处理流程
                if ($wxQrcodePayResult ["return_code"] == "FAIL") {
                    // 商户自行增加处理流程
                    echo "通信出错：" . $wxQrcodePayResult ['return_msg'] . "<br>";
                } elseif ($wxQrcodePayResult ["result_code"] == "FAIL") {
                    // 商户自行增加处理流程
                    echo "错误代码：" . $wxQrcodePayResult ['err_code'] . "<br>";
                    echo "错误代码描述：" . $wxQrcodePayResult ['err_code_des'] . "<br>";
                } elseif ($wxQrcodePayResult ["code_url"] != NULL) {
                    // 从统一支付接口获取到code_url
                    $code_url = $wxQrcodePayResult ["code_url"];
                    // 商户自行增加处理流程
                }
                $this->assign('out_trade_no', $obj ["uniqueId"]);
                $this->assign('code_url', $code_url);
                $this->assign('wxQrcodePayResult', $wxQrcodePayResult);
            } else {
                $pflag = false;
            }
        }
        if ($pflag) {
            $this->display("default/payment/wxpay/qrcode");
        } else {
            $this->display("default/payment/pay_success");
        }

    }

    /*
     * 微信下单回调
     * @param array $order PS:微信返回数据
     * */
    public function notify($order = [])
    {
        $config = $GLOBALS['CONFIG'];
        if (empty($order)) {
            $returnData = returnData(null, -1, 'error', '参数异常');
            return $returnData;
        }
        //过滤一些无用的回调吧
        $notifyInfo = $this->notifyInfo($order['out_trade_no']);
        if (!empty($notifyInfo) && $notifyInfo['notifyStatus'] == 1) {
            return returnData();
        }
        $trade_no = $order["transaction_id"];
        $total_fee = $order["total_fee"];
        $attach = $order["attach"];
        $userId = $attach['userId'];

        $mergeTab = M('order_merge');
        $orderToken = $attach['orderToken'];
        $mergeInfo = $mergeTab->where(['orderToken' => $orderToken])->find();
        $orderArr = explode('A', $mergeInfo['value']);
        $pm = D('Home/Payments');
        $orderTab = M('orders');
        //后加,统一运费,如果订单金额未达到免减运费且多单,则运费只体现在一个订单上即可
        foreach ($orderArr as $ak => $av) {
            $where = "orderNo='{$av}' ";
            $orderInfo = $orderTab->where($where)->find();
            if ($orderInfo['orderStatus'] == -1) {
                //后加,主要针对的场景:用户支付了,订单却被系统自动取消,(常见:用户处于正在支付的状态,定时任务直接把超时未支付的订单给取消掉了......)
                $orderTab->where(['orderId' => $orderInfo['orderId']])->save(['orderStatus' => -2, 'userDelete' => 1]);
            }
            if ($orderInfo['isPay'] == 1 || ($orderInfo['orderStatus'] >= 0 && $orderInfo['orderStatus'] != 15)) {
                continue;
            }
            // 商户订单号
            $obj = array();
            $obj ["order_id"] = $orderInfo['orderId'];
            $obj ["trade_no"] = $trade_no;
            $obj ["out_trade_no"] = $orderInfo['orderId'];
            $obj ["order_type"] = 1;
            $obj ["total_fee"] = $total_fee;
            $obj ["userId"] = $userId;
            $obj["payFrom"] = 2;
            // 支付成功业务逻辑
            $payments = $pm->complatePay($obj);
        }
        $totalTealTotalMoney = M('orders')->where(['tradeNo' => $trade_no])->sum('realTotalMoney');//这里的realTotalMoney在未支付前是不包含运费的
        if ($totalTealTotalMoney > 0) {
            if ($config['setDeliveryMoney'] == 2) {//废弃
                foreach ($orderArr as $ak => $av) {
                    $where = "orderNo='{$av}' ";
                    $orderInfo = $orderTab->where($where)->find();
                    if ($orderInfo['isPay'] == 1) {
                        if ($totalTealTotalMoney < $config['deliveryFreeMoney']) {
                            if ($ak == 0) {
                                $saveData = [];
                                $saveData['deliverMoney'] = $orderInfo['setDeliveryMoney'];
                                $saveData['realTotalMoney'] = bc_math($orderInfo['realTotalMoney'], $orderInfo['setDeliveryMoney'], 'bcadd', 2);
                                $res = M('orders')->where(['orderId' => $orderInfo['orderId']])->save($saveData);
                            } else {
                                $res = M('orders')->where(['orderId' => $orderInfo['orderId']])->save(['deliverMoney' => 0]);
                            }
                        }
                    }
                }
                if ($totalTealTotalMoney >= $config['deliveryFreeMoney']) {
                    $res = M('orders')->where(['tradeNo' => $trade_no])->save(['deliverMoney' => 0]);
                }
            } else {//处理常规非常规订单运费问题
                if (count($orderArr) > 1) {//修复常规非常规商品拆单运费问题
                    $shop_module = new ShopsModule();
                    foreach ($orderArr as $ak => $av) {
                        $where = "orderNo='{$av}' ";
                        $orderInfo = $orderTab->where($where)->find();
                        $shop_detail = $shop_module->getShopsInfoById($orderInfo['shopId'], 'shopId,deliveryFreeMoney', 2);
                        if ($orderInfo['isPay'] == 1) {
                            if ($totalTealTotalMoney < $shop_detail['deliveryFreeMoney']) {
                                if ($ak == 0) {
                                    $saveData = [];
                                    $saveData['deliverMoney'] = $orderInfo['setDeliveryMoney'];
                                    $saveData['realTotalMoney'] = bc_math($orderInfo['realTotalMoney'], $orderInfo['setDeliveryMoney'], 'bcadd', 2);
                                    $res = M('orders')->where(['orderId' => $orderInfo['orderId']])->save($saveData);
                                } else {
                                    $res = M('orders')->where(['orderId' => $orderInfo['orderId']])->save(['deliverMoney' => 0]);
                                }
                            }
                        }
                    }
                }
//                if($totalTealTotalMoney >= $config['deliveryFreeMoney']){
//                    $res = M('orders')->where(['tradeNo'=>$trade_no])->save(['deliverMoney'=>0]);
//                }
            }
        }
        //记录变量 测试代码 记得删除
        //errorLog(print_r(get_defined_vars(),true));
        return returnData();
    }

    /*
     * 余额充值
     * @param array $order PS:微信回调数据
     * */
    public function notifyUser($order = [])
    {
        if (empty($order)) {
            $returnData = returnData(null, -1, 'error', '参数异常');
            return $returnData;
        }
        $trade_no = $order["transaction_id"];
        $total_fee = $order["total_fee"];
        $attach = $order["attach"];
        $userId = $attach['userId'];
        $pm = D('Home/Payments');
        // 用户充值数组组织
        $obj = array();
        $obj["trade_no"] = $trade_no;
        $obj["userId"] = $userId;
        $obj["balance"] = $total_fee / 100;
        $obj["openid"] = $order['openid'];
        $obj["out_trade_no"] = $order['out_trade_no'];
        $res = $pm->wxNotifyUser($obj);
        if ($res) {
            return returnData();
        }
    }

    /**
     * 购买会员套餐
     * @param array $order PS:微信回调数据
     */
    public function notifyBuySetmeal($order)
    {
        $m = M('transaction');
        $transactionInfo = $m->where(array('transaction_id' => $order['transaction_id']))->find();
        if (!empty($transactionInfo)) {
            return returnData();
        } else {
            $m->add(array(
                'transaction_id' => $order['transaction_id'],
                'createTime' => date('Y-m-d H:i:s')
            ));
        }
        $attach = $order["attach"];
        $smId = $attach['smId'];
        $userId = $attach['userId'];
        $m = D('V3/Api');
        $res = $m->notifyBuySetmeal($userId, $smId);
        if ($res) {
            return returnData();
        }
    }

    /*
     * 用户还款
     * @param array $order PS:微信回调数据
     * @return array
     * */
    public function notifyUserRepayment(array $order)
    {
        $attach = $order["attach"];
        $money = (float)$attach["money"];
        $userId = (int)$attach["userId"];
        $userModule = new UsersModule();
        $res = $userModule->decQuotaArrears($userId, $money);
        if ($res) {
            return returnData();
        }
    }

    /**
     * 购买抢购加量包
     * @param array $order
     */
    public function notifyBuyCouponset($order = [])
    {
        $m = M('transaction');
        $transactionInfo = $m->where(array('transaction_id' => $order['transaction_id']))->find();
        if (!empty($transactionInfo)) {
            return returnData();
        } else {
            $m->add(array(
                'transaction_id' => $order['transaction_id'],
                'createTime' => date('Y-m-d H:i:s')
            ));
        }
        $attach = $order["attach"];
        $userId = $attach['userId'];
        $csId = $attach['csId'];
        $m = D('V3/Api');
        $res = $m->notifyBuyCouponset($userId, $csId);
        if ($res) {
            returnData();
        }
    }

    /**
     * 检查支付结果
     */
    public function getPayStatus()
    {
        $trade_no = I('trade_no');
        $total_fee = S($trade_no);
        $data = array("status" => -1);
        if (empty ($total_fee)) {
            $data["status"] = -1;
        } else {// 检查缓存是否存在，存在说明支付成功
            S($trade_no, null);
            $data["status"] = 1;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 检查支付结果
     */
    public function paySuccess()
    {
        $this->display("default/payment/pay_success");
    }


    public function notifyUnifiedOrder()
    {
        // 使用通用通知接口
        $wxQrcodePay = new \WxQrcodePay ();
        // 存储微信的回调
        $xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
        $wxQrcodePay->saveData($xml);
        // 验证签名，并回应微信。
        if ($wxQrcodePay->checkSign() == FALSE) {
            $wxQrcodePay->setReturnParameter("return_code", "FAIL"); // 返回状态码
            $wxQrcodePay->setReturnParameter("return_msg", "签名失败"); // 返回信息
        } else {
            $wxQrcodePay->setReturnParameter("return_code", "SUCCESS"); // 设置返回码
        }
        $returnXml = $wxQrcodePay->returnXml();
        // ==商户根据实际情况设置相应的处理流程，此处仅作举例=======
        $res = false;
        if ($wxQrcodePay->checkSign() == TRUE) {
            if ($wxQrcodePay->data ["return_code"] == "FAIL") {
                // 此处应该更新一下订单状态，商户自行增删操作
            } elseif ($wxQrcodePay->data ["result_code"] == "FAIL") {
                // 此处应该更新一下订单状态，商户自行增删操作
            } else {
                // 此处应该更新一下订单状态，商户自行增删操作
                $order = $wxQrcodePay->getData();
                $sign = $order['out_trade_no'];
                $notifyField = [];
                $notifyField['responseJson'] = json_encode($order);
                $notifyField['responseTime'] = date('Y-m-d H:i:s', time());
                $this->notifySave($sign, $notifyField);
                $notifyInfo = $this->notifyInfo($sign);
                if ($notifyInfo) {
                    //已完成的回调不再处理 start
                    if ($notifyInfo['notifyStatus'] == 1) {
                        header("Content-type:text/xml");
                        $resxml['return_code'] = 'SUCCESS';
                        exit(arrayToXml($resxml));
                    }
                    //已完成的回调不再处理 end
                    $attach = json_decode($notifyInfo['requestJson'], true);
                    $order['attach'] = $attach;
                    //(1:微信下单支付|2:微信重新支付|3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包)
                    $dataType = $notifyInfo['type'];
                    $payModule = new PayModule();
                    switch ($dataType) {
                        case 1:
//                            $response = $this->notify($order);
                            $response = $payModule->orderNotify($order);
                            break;
                        case 2:
//                            $response = $this->notify($order);
                            $response = $payModule->orderNotify($order);
                            break;
                        case 3:
//                            $response = $this->notifyUser($order);
                            $response = $payModule->notifyUser($order);
                            break;
                        case 4:
//                            $response = $this->notifyBuySetmeal($order);
                            $response = $payModule->notifyBuySetmeal($order);
                            break;
                        case 5:
//                            $response = $this->notifyBuyCouponset($order);
                            $response = $payModule->notifyBuyCouponset($order);
                            break;
                        case 6:
//                            $response = $this->notifyUserRepayment($order);//用户还款
                            $response = $payModule->notifyUserRepayment($order);//用户还款
                            break;
                    }
                    if ($response['code'] == 0) {
                        $res = true;
                    }
                }
            }
        }
        if ($res) {
            //更新回调处理状态
            $notifyField = [];
            $notifyField['notifyStatus'] = 1;
            $this->notifySave($sign, $notifyField);
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        }
    }

    /*
     * 获取notify信息
     * @param string key
     * */
    public function notifyInfo($key = '')
    {
        $info = '';
        if (!empty($key)) {
            $logInfo = M('notify_log')->where(['key' => $key])->find();
            if ($logInfo) {
                $info = $logInfo;
            }
        }
        return $info;
    }

    /*
     * 保存notify信息
     * @param string $key
     * @param array $save
     * */
    public function notifySave($key = '', $save)
    {
        $res = false;
        if (!empty($key) && !empty($save)) {
            $res = M('notify_log')->where(['key' => $key])->save($save);
        }
        return $res;
    }
}

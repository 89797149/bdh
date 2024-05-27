<?php
/**
 * 支付相关
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2022-08-03
 * Time: 10:47
 */

namespace App\Modules\Pay;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\Orders\OrdersModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;

class PayModule extends BaseModel
{
    /**
     * 微信支付
     * @param string $sign 支付请求标识
     * @return array
     * */
    public function wxPay(string $sign)
    {
        //测试参数
        //$root = WSTRootPath() . '/ThinkPHP/Library/Vendor/WxPay/sdk3';
        $root = $_SERVER['DOCUMENT_ROOT'] . '/ThinkPHP/Library/Vendor/WxPay/sdk3';
        require_once $root . "/lib/WxPay.Api.php";
        require_once $root . "/WxPay.JsApiPay.php";
        require_once $root . "/WxPay.Config.php";
        require_once $root . "/lib/WxPay.Data.php";
        require_once $root . '/log.php';
        //初始化日志
        $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
        \Log::Init($logHandler, 15);
        if (isset($param['attach']) && !empty($param['attach'])) {
            $attach = $param['attach'];
        }
        $orderModule = new OrdersModule();
        $notifyLogRow = $orderModule->getNotifyLogDetail($sign);
        if (empty($notifyLogRow)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败', '支付信息有误');
        }
        $requestData = json_decode($notifyLogRow['requestJson'], true);
        $amount = $requestData['amount'] * 100;
        try {
            $config = new \WxPayConfig();
            getWxPayConfig($requestData['payType'], $requestData['dataFrom'], $config);//重置支付配置
            //$tools = new JsApiPay();
            $openId = $requestData['openId'];
            //②、统一下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("订单支付");
            $input->SetAttach($attach);
            $input->SetOut_trade_no($sign);
            $input->SetTotal_fee($amount);
            $input->SetNotify_url(WSTDomain() . '/Home/WxPay/notifyUnifiedOrder');
            //2:手机版 3:app 4：小程序
            if ($requestData['dataFrom'] == 2) {
                $input->SetTrade_type("MWEB");
            } elseif ($requestData['dataFrom'] == 3) {
                $input->SetTrade_type("APP");
            } else {
                $input->SetTrade_type("JSAPI");
            }
            $input->SetSignType('MD5');
            $input->SetProduct_id(time());
            if ($requestData['dataFrom'] == 4 || $requestData['dataFrom'] == 5) {
                $input->SetOpenid($openId);
            }
            $result = \WxPayApi::unifiedOrder($config, $input);
            $returnData = $result;
            if ($result['return_code'] != 'SUCCESS') {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败-' . $result['return_msg'], $result['return_msg']);
            }
            if ($result['result_code'] != 'SUCCESS') {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败' . $result['err_code_des'], $result['err_code_des']);
            }
            $returnData['apikey'] = $config->GetKey();
            return returnData($returnData);
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
        }
    }

    public function orderUnifiedOrder($param)
    {
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        $userId = $param['userId'];
        $mergeTab = M('order_merge');
        $mergeInfo = $mergeTab->where(['orderToken' => $dataValue['orderToken']])->find();
        $orderNoStr = $mergeInfo['value'];
        $orderNoArr = explode('A', $orderNoStr);
        $where = [];
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $where['orderNo'] = ['IN', $orderNoArr];
        $orderList = M('orders')->where($where)->select();
        if (count($orderList) < count($orderNoArr)) {
            $apiRet = returnData(null, -1, 'error', '支付失败，非法数据请求', '非法数据请求，订单合并表数据和实际订单不符');
            return $apiRet;
        }
        $orderToken = $dataValue['orderToken'];
        $payAmount = 0;
        foreach ($orderList as $key => $value) {
            $payAmount += $value['realTotalMoney'];
            if ($value['isPay'] == 1) {
                $apiRet = returnData(null, -1, 'error', '订单已支付，不能重复提交支付');
                return $apiRet;
            }
        }
        if (count($orderList) > 1) {
            $payAmount = $mergeInfo['realTotalMoney'];
        }
        $payAmount = $mergeInfo['realTotalMoney'];
        M()->startTrans();
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $userId;
        $attach['orderToken'] = $orderToken;
        $param['requestJson'] = json_encode($attach);
        $param['orderToken'] = $orderToken;
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '支付失败，数据异常');
            return $apiRet;
        }
        //构建参数
        $payParam['openId'] = $param['openId'];
        $payParam['orderNo'] = $sign;
        $payParam['amount'] = $payAmount;
        $payParam['attach'] = '';
        $payParam['dataFrom'] = $param['dataFrom'];
        $payParam['payType'] = $param['payType'];
        $payRes = unifiedOrder($payParam);//统一下单支付
        if ($payRes['result_code'] !== 'SUCCESS') {
            M()->rollback();
            $msg = $payRes['err_code_des'];
            if (!isset($payRes['err_code_des'])) {
                $msg = $payRes['return_msg'];
            }
            return returnData(null, -1, 'error', '支付失败，' . $msg);
        }
        $payRes['realTotalMoney'] = $payAmount;
        $payRes['orderToken'] = $orderToken;
        $payRes['notifySign'] = $sign;
        M()->commit();
        return returnData($payRes, 0, 'success', '生成成功');
    }

    /**
     * 付款-支付宝
     * @param string $sign 支付请求标识
     * @return array
     * */
    public function aliPay(string $sign)
    {
        $orderModule = new OrdersModule();
        $notifyLogRow = $orderModule->getNotifyLogDetail($sign);
        if (empty($notifyLogRow)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败', '支付信息有误');
        }
        $requestData = json_decode($notifyLogRow['requestJson'], true);
        $amount = $requestData['amount'];
        try {
            $root = dirname(dirname(dirname(dirname(__FILE__)))) . '/ThinkPHP/Library/Vendor/Alipay_2/';
            require_once $root . 'pc_pay/pagepay/service/AlipayTradeService.php';
            require_once $root . 'pc_pay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';
            require_once $root . 'pc_pay/aop/AopClient.php';
            require_once $root . 'pc_pay/aop/request/AlipayTradeAppPayRequest.php';
            require_once $root . 'pc_pay/config.php';
            //商户订单号，商户网站订单系统中唯一订单号，必填
            $out_trade_no = $sign;
            //付款金额，必填
            $total_amount = $amount;
            $config = getWxPayConfig(1);
            $aop = new \AopClient;
            $aop->gatewayUrl = $config['gatewayUrl'];
            $aop->appId = $config['app_id'];
            $aop->rsaPrivateKey = $config['merchant_private_key'];
            $aop->format = "json";
            $aop->postCharset = "UTF-8";
            $aop->signType = $config['sign_type'];
            $aop->alipayrsaPublicKey = $config['alipay_public_key'];
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
            $request = new \AlipayTradeAppPayRequest();
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
            $bizcontent = "{\"body\":\"订单支付\","
                . "\"subject\": \"订单支付\","
                . "\"out_trade_no\": \"{$out_trade_no}\","
                . "\"timeout_express\": \"30m\","
                . "\"total_amount\": \"{$total_amount}\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
            $request->setNotifyUrl("{$config['notify_url']}");
            $request->setBizContent($bizcontent);
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);
            //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
            //$order_string = htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
            $order_string = str_replace('amp;', '', htmlspecialchars($response));
            $return_data = array(
                'order_string' => $order_string,
            );
            return returnData($return_data);
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
        }
    }

    /**
     * 支付宝退款
     * @param string $trade_no 交易号
     * @param float $refund_amount 退款金额
     * */
    public function aliPayRefund($trade_no, $refund_amount)
    {
        $config = getWxPayConfig(1);
        $root = dirname(dirname(dirname(dirname(__FILE__)))) . '/ThinkPHP/Library/Vendor/Alipay_2/';
        require_once $root . 'pc_pay/aop/AopClient.php';
        require_once $root . 'pc_pay/aop/request/AlipayTradeRefundRequest.php';
        try {
            $aop = new \AopClient();
            $aop->gatewayUrl = $config['gatewayUrl'];
            $aop->appId = $config['app_id'];
            $aop->rsaPrivateKey = $config['merchant_private_key'];
            $aop->alipayrsaPublicKey = $config['alipay_public_key'];
            $aop->apiVersion = '1.0';
            $aop->signType = $config['sign_type'];
            $aop->postCharset = 'UTF-8';
            $aop->format = 'json';
            $object = new \stdClass();
            $object->trade_no = $trade_no;
            $object->refund_amount = $refund_amount;
            $out_request_no = M("psd_orderids")->add(array('rnd' => microtime(true))); //该表已经无用了,拿来做单号生成
            $object->out_request_no = "{$out_request_no}";
            $json = json_encode($object);
            $request = new \AlipayTradeRefundRequest();
            $request->setBizContent($json);
            $result = $aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if (!empty($resultCode) && $resultCode == 10000) {
                //暂时请求成功就算成功,因为目前站点的配置是错误的,无法进行真实环境测试得到正确的返回示例
                return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', "退款请求成功");
            } else {
                $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "支付宝退款请求失败：$result ");
                fclose($myfile);
                $mgs = "退款请求失败";
                if (!empty($result->$responseNode->sub_msg)) {
                    $mgs = $result->$responseNode->sub_msg;
                }
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $mgs);
            }
        } catch (\Exception $e) {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "支付宝退款异常：$e->getMessage() ");
            fclose($myfile);
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $e->getMessage());
        }
    }


    /*
     * 下单回调
     * @param array $order PS:微信返回数据
     * */
    public function orderNotify($order = [])
    {
        //原有逻辑直接复制过来的，只是位置换了下
        $config = $GLOBALS['CONFIG'];
        if (empty($order)) {
            $returnData = returnData(null, -1, 'error', '参数异常');
            return $returnData;
        }
        //过滤一些无用的回调吧
        $orderModule = new OrdersModule();
        $notifyInfo = $orderModule->getNotifyLogDetail($order['out_trade_no']);
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
        $order_module = new OrdersModule();
        $shop_module = new ShopsModule();
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
            $obj["payFrom"] = $orderInfo['payFrom'];
            // 支付成功业务逻辑
            $payments = $pm->complatePay($obj);

            //临时修复，订单自动受理
            $order_row = $order_module->getOrderInfoById($orderInfo['orderId'], "*", 2);
            if ($order_row['isPay'] != 1) {
                continue;
            }
            $getShopsList = $shop_module->getShopsList();
            $printsInfo = [];
            if (!empty($getShopsList)) {//处理在营业时间内的店铺自动接单
                foreach ($getShopsList as $shopIdKey => $shopIdRow) {
                    if ($shopIdRow['shopId'] != $order_row['shopId']) {
                        continue;
                    }
                    $getPrintsInfo = $shop_module->getPrintsList($shopIdRow['shopId']);
                    if (!empty($getPrintsInfo)) {
                        foreach ($getPrintsInfo as $printInfo) {
                            if ($printInfo['isDefault'] == 1) {//是否默认【0:否|1:默认】
                                $printsInfo = $printInfo;
                            }
                        }
                    }
                }
            }
            if (!empty($printsInfo)) {
                $order_row["printsInfo"] = $printsInfo;
                $order_module->shopOrderAccept($order_row);
            }
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
     * 余额充值回调
     * @param array $order PS:微信回调数据
     * */
    public function notifyUser($order = [])
    {
        //原有逻辑直接复制过来的
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
     * 购买会员套餐回调
     * @param array $order PS:微信回调数据
     */
    public function notifyBuySetmeal($order)
    {
        //原有逻辑直接复制过来的
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
     * 用户还款回调
     * @param array $order PS:微信回调数据
     * @return array
     * */
    public function notifyUserRepayment(array $order)
    {
        //原有逻辑直接复制过来的
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
     * 购买抢购加量包回调
     * @param array $order
     */
    public function notifyBuyCouponset($order = [])
    {
        //原有逻辑直接复制过来的
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
}
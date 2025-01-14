<?php
namespace Home\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 支付类
 */

use Think\Model;

class PaymentsModel extends BaseModel
{
    /**
     * 获取支付列表
     */
    public function getList()
    {
        $m = M('payments');
        $payments = $m->where('enabled=1')->order('payOrder asc')->select();
        $paylist = array();
        foreach ($payments as $key => $payment) {
            $payConfig = json_decode($payment["payConfig"]);
            foreach ($payConfig as $key2 => $value) {
                $payment[$key2] = $value;
            }
            //$payments[$key] = $payment;
            if ($payment["isOnline"]) {
                $paylist["onlines"][] = $payment;
            } else {
                $paylist["unlines"][] = $payment;
            }
        }
        return $paylist;
    }

    /**
     * 获取支付信息
     * @return unknown
     */
    public function getPayment($payCode = "")
    {
        $m = M('payments');
        $payCode = $payCode ? $payCode : WSTAddslashes(I("payCode"));
        $payment = $m->where("enabled=1 AND payCode='$payCode' AND isOnline=1")->find();
        $payConfig = json_decode($payment["payConfig"]);
        foreach ($payConfig as $key => $value) {
            $payment[$key] = $value;
        }
        return $payment;
    }

    /**
     * 生成支付代码
     * @param array $order 订单信息
     * @param array $payment 支付方式信息
     */
    function getAlipayUrl()
    {
        $payment = self::getPayment();
        $real_method = 2;

        switch ($real_method) {
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
        }

        $extend_param = '';
        $orderunique = WSTAddslashes(I("orderunique"));

        $USER = session('WST_USER');
        $userId = (int)$USER['userId'];
        $obj["userId"] = $userId;
        $orderId = (int)I("orderId");

        if ($orderId > 0) {
            $obj["orderType"] = 1;
            $obj["uniqueId"] = $orderId;
        } else {
            $obj["orderType"] = 2;
            $obj["uniqueId"] = session("WST_ORDER_UNIQUE");
        }
        $order = self::getPayOrders($obj);
        $orderAmount = $order["needPay"];

        $return_url = WSTDomain() . '/Wstapi/payment/return_alipay.php';
        $notify_url = WSTDomain() . '/Wstapi/payment/notify_alipay.php';
        $parameter = array(
            'extra_common_param' => $userId . "@" . $obj["orderType"],
            'service' => $service,
            'partner' => $payment['parterID'],
            '_input_charset' => "utf-8",
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            /* 业务参数 */
            'subject' => '支付购买商品费' . $orderAmount . '元',
            'body' => '支付订单费用',
            'out_trade_no' => $obj["uniqueId"],
            'total_fee' => $orderAmount,
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* 买卖双方信息 */
            'seller_email' => $payment['payAccount']
        );
        ksort($parameter);
        reset($parameter);
        $param = '';
        $sign = '';
        foreach ($parameter as $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }
        $param = substr($param, 0, -1);
        $sign = substr($sign, 0, -1) . $payment['parterKey'];
        return 'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5';
    }


    /**
     * 获取支付订单信息
     */
    public function getPayOrders($obj)
    {
        $userId = (int)$obj["userId"];
        $orderType = (int)$obj["orderType"];
        if ($orderType == 1) {
            $orderId = (int)$obj["uniqueId"];
            $sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE userId = $userId AND orderId = $orderId AND orderFlag = 1 AND needPay>0 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        } else {
            $orderunique = WSTAddslashes($obj["uniqueId"]);
            $sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE userId = $userId AND orderunique = '$orderunique' AND orderFlag = 1 AND needPay>0 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        }
        $data = self::queryRow($sql);
        return $data;
    }

    /**
     * 完成支付订单
     */
    public function complatePay($obj)
    {

        $trade_no = WSTAddslashes($obj["trade_no"]);
        $orderType = (int)$obj["order_type"];
        if ($orderType == 1) {
            $orderId = (int)$obj["out_trade_no"];
        } else {
            $orderunique = WSTAddslashes($obj["out_trade_no"]);
        }
        $userId = (int)$obj["userId"];
        $payFrom = (int)$obj["payFrom"];
        if ($orderType == 1) {
            $sql = "select og.orderId,og.goodsId,og.goodsNums,og.goodsAttrId from __PREFIX__order_goods og, __PREFIX__orders o where o.userId=$userId and og.orderId = o.orderId AND o.orderId = $orderId and o.payType = 1 and o.needPay > 0 and o.orderFlag=1 and (o.orderStatus=-2 or o.orderStatus=15) ";
        } else {
            $sql = "select og.orderId,og.goodsId,og.goodsNums,og.goodsAttrId from __PREFIX__order_goods og, __PREFIX__orders o where o.userId=$userId and og.orderId = o.orderId AND o.orderunique = '$orderunique' and o.payType = 1 and o.needPay > 0 and o.orderFlag=1 and (o.orderStatus=-2 or o.orderStatus=15) ";
        }
        $goodslist = $this->query($sql);
        $data = array();
//		$data["needPay"] = 0;
        $data["isPay"] = 1;
        $data["pay_time"] = date('Y-m-d H:i:s');
        $data['isReceivables'] = 2;//(psd)是否收款(0:待收款|1:预收款|2:已收款(全款))
        $data["orderStatus"] = 0;
        $data["tradeNo"] = $trade_no;
        $data["payFrom"] = $payFrom;
        $rd = array('status' => -1);
        $om = M('orders');
        $order_detail = $om
            ->where(array('orderId' => $obj['order_id']))
            ->field('orderId,orderType')
            ->find();
        if ($order_detail['orderType'] == 2) {//主要针对拼团
            M('user_activity_relation')->where(array('orderId' => $obj['order_id']))->save(array('is_pay' => 1));
        }
        if ($orderType == 1) {
            $rs = $om->where("orderId = $orderId and payType = 1 and needPay > 0 and orderFlag=1 and (orderStatus=-2 or orderStatus=15)")->save($data);
        } else {
            $rs = $om->where("orderunique = '$orderunique' and payType = 1 and needPay > 0 and orderFlag=1 and (orderStatus=-2 or orderStatus=15)")->save($data);
        }
        if (false !== $rs) {
            $rd['status'] = 1;
            //修改库存
//            foreach ($goodslist as $key => $sgoods) {
//                $goodsId = $sgoods['goodsId'];
//                $goodsNums = $sgoods['goodsNums'];
//                $goodsAttrId = $sgoods['goodsAttrId'];
////                $goods_goodsNums = gChangeKg($goodsId, $goodsNums, 1);
////                $sql = "update __PREFIX__goods set goodsStock=goodsStock-" . $goods_goodsNums . " where goodsId=" . $goodsId;
////                $this->execute($sql);
//
//                //更新进销存系统商品的库存
//                //updateJXCGoodsStock($goodsId,$goodsNums,1);
//
////                if ((int)$goodsAttrId > 0) {
////                    $sql = "update __PREFIX__goods_attributes set attrStock=attrStock-" . $goods_goodsNums . " where id=" . $goodsAttrId;
////                    $this->execute($sql);
////                }
//            }
            if ($orderType == 1) {
                $sql = "select orderId,orderNo from __PREFIX__orders where userId=$userId and orderId=$orderId";
            } else {
                $sql = "select orderId,orderNo from __PREFIX__orders where userId=$userId and orderunique='$orderunique'";
            }

            $list = $this->query($sql);
            for ($i = 0; $i < count($list); $i++) {
                $orderId = $list[$i]["orderId"];
                //添加报表-start
                addReportForms($orderId, 1, array());
                //添加报表-end
                //如果是拼团订单，则处理拼团订单的状态
                D('V3/Assemble')->completeAssemble($orderId);

//                $data = array();
//                $lm = M('log_orders');
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "订单已支付,下单成功";
//                $data["logUserId"] = $userId;
//                $data["logType"] = 0;
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $ra = $lm->add($data);
                $content = '订单已支付,下单成功';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => $userId,
                    'logUserName' => '用户',
                    'orderStatus' => 0,
                    'payStatus' => 1,
                    'logType' => 0,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);

                $push = D('Adminapi/Push');
                $push->postMessage(7, $userId, $list[$i]['orderNo']);
            }
        }

        return $rd;
    }


    /**
     * 支付回调接口
     * @param unknown $request
     * @return multitype:string boolean
     */
    function notify($request)
    {
        $return_res = array(
            'info' => '',
            'status' => false,
        );
        $request = $this->argSort($request);
        /* 检查数字签名是否正确 */
        $isSign = $this->getSignVeryfy($request);
        if (!$isSign) {//签名验证失败
            $return_res['info'] = '签名验证失败';
            return $return_res;
        }
        if ($request['trade_status'] == 'TRADE_SUCCESS' || $request['trade_status'] == 'TRADE_FINISHED' || $request['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $request['trade_status'] == 'WAIT_BUYER_CONFIRM_GOODS') {
            $return_res['status'] = true;
        }
        return $return_res;
    }

    /**
     * 获取返回时的签名验证结果
     * @param unknown $para_temp
     * @return boolean
     */
    function getSignVeryfy($para_temp)
    {
        $payment = self::getPayment("alipay");
        $parterKey = $payment["parterKey"];
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        $isSgin = $this->md5Verify($prestr, $para_temp['sign'], $parterKey);
        return $isSgin;
    }

    /**
     * 验证签名
     * @param unknown $prestr
     * @param unknown $sign
     * @param unknown $key
     * @return boolean
     */
    function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     */
    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     */
    function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param unknown $para
     * @return unknown
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 完成支付订单
     */
    public function wxNotifyUser($obj = array(), &$msg = '')
    {
        $um = M('users');
        $mod_pay_tmp = M('pay_tmp');
        //判断当前订单号是否在支付缓存表里 如果在就是处理过的直接返回true即可
        if ($mod_pay_tmp->where("orderno = '{$obj["out_trade_no"]}'")->count() > 0) {
            return true;
        }
        //官方bug setInc函数 组合最终结果 字符串类型丢失 导致sql最终出错
        // $res = $um->where("openId = '{$obj['openid']}'")->setInc('balance', abs((float)$obj['balance']));//有误

        $savesc['balance'] = array('exp', 'balance+' . abs((float)$obj['balance']));
//        $res = $um->where("openId = '{$obj['openid']}'")->save($savesc);
        $res = $um->where("userId = '{$obj['userId']}'")->save($savesc);

        if (!$res) {
            $msg = '充值失败';
            return false;
        }

        //充值成功 进行记录
        $add_data['orderno'] = $obj["out_trade_no"];
        $mod_pay_tmp->add($add_data);

        //获取用户id
        //$userId = $um->where("openId = ".$obj['openid'])->find()['userId'];

        //写入余额流水
        $mod_user_balance = M('user_balance');
        $add_user_balance['userId'] = $obj['userId'];
        $add_user_balance['balance'] = $obj['balance'];
        $add_user_balance['dataSrc'] = 1;
        $add_user_balance['orderNo'] = $obj['trade_no'];
        $add_user_balance['dataRemarks'] = '余额充值';
        $add_user_balance['balanceType'] = 1;
        $add_user_balance['createTime'] = date('Y-m-d H:i:s');
        $add_user_balance['shopId'] = '';

        $resm = $mod_user_balance->add($add_user_balance);

        if (!$resm) {
            return false;
        }
        //充值成功,处理返现逻辑 后加 start
        $amount = $obj['balance'];
        $rechargeConfigList = M('recharge_config')->where(['dataFlag' => 1])->select();
        $returnAmount = 0;
        foreach ($rechargeConfigList as $value) {
            if ($amount >= $value['minAmount'] && $amount <= $value['maxAmount']) {
                $returnAmount = $value['returnAmount'];
            }
        }
        if ($returnAmount > 0) {
            /*$savesc['balance'] = array('exp','balance+'.abs((float)$returnAmount['returnAmount']));
            $updateBalanceRes = $um->where("userId = '{$obj['userId']}'")->save($savesc);*/
            $userInfo = $um->where(['userId' => $obj['userId']])->find();
            $updateBalance = [];
            $updateBalance['balance'] = $userInfo['balance'] + $returnAmount;
            $updateBalanceRes = $um->where("userId = '{$obj['userId']}'")->save($updateBalance);
            if (!$updateBalanceRes) {
                return false;
            }
            //写入余额流水
            $add_user_balance['userId'] = $obj['userId'];
            $add_user_balance['balance'] = $returnAmount;
            $add_user_balance['dataSrc'] = 1;
            $add_user_balance['orderNo'] = $obj['trade_no'];
            $add_user_balance['dataRemarks'] = '余额充值返现';
            $add_user_balance['balanceType'] = 1;
            $add_user_balance['createTime'] = date('Y-m-d H:i:s');
            $add_user_balance['shopId'] = '';
            $mod_user_balance->add($add_user_balance);
        }
        //充值成功,处理返现逻辑 后加 end
        return true;
    }

}

?>

<?php
/**
 * 支付宝回调
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-04-17
 * Time: 17:36
 */

namespace Home\Action;

use App\Modules\Orders\OrdersModule;
use App\Modules\Pay\PayModule;
use App\Modules\Shops\ShopsModule;
use Think\Model;

class AliPayAction extends BaseAction
{
    public function notify()
    {
        $root = dirname(dirname(dirname(dirname(__FILE__)))) . '/ThinkPHP/Library/Vendor/Alipay_2/';
        require_once $root . 'pc_pay/config.php';
        require_once $root . 'pc_pay/pagepay/service/AlipayTradeService.php';
        $config = getWxPayConfig(1);
        $arr = $_POST;
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if ($result) {//验证成功
            $notify_key = $_POST['out_trade_no'];//商户平台交易号
            $trade_status = $_POST['trade_status'];//交易状态
            $trade_no = $_POST['trade_no']; //支付宝交易号
            if ($trade_status == 'TRADE_SUCCESS') {
                $order_module = new OrdersModule();
                $notify_log_detail = $order_module->getNotifyLogDetail($notify_key);
                if (!empty($notify_log_detail) && $notify_log_detail['notifyStatus'] != 1) {
                    $request_params = json_decode($notify_log_detail['requestJson'], true);
                    $order["transaction_id"] = $trade_no;
                    $order["out_trade_no"] = $notify_key;
                    $order["total_fee"] = bc_math($request_params["amount"], 100, 'bcmul', 2); //以前的方法都是按着微信的规则来的,所以这里的金额也转成分来兼容
                    $order['attach'] = $request_params;
                    $dataType = $notify_log_detail['type'];
                    $pay_module = new PayModule();
                    switch ($dataType) {
                        case 1:
                            $response = $pay_module->orderNotify($order);
                            break;
                        case 2:
                            $response = $pay_module->orderNotify($order);
                            break;
                        case 3:
                            $response = $pay_module->notifyUser($order);
                            break;
                        case 4:
                            $response = $pay_module->notifyBuySetmeal($order);
                            break;
                        case 5:
                            $response = $pay_module->notifyBuyCouponset($order);
                            break;
                        case 6:
                            $response = $pay_module->notifyUserRepayment($order);//用户还款
                            break;
                    }
                    $res = false;
                    if ($response['code'] == 0) {
                        $res = true;
                    }
                    if ($res) {
                        $order_module->updateNotifyLog($notify_log_detail['id'], json_encode($_POST), 1);
                        echo "success";    //请不要修改或删除
                    }
                }
            }
        } else {
            //验证失败
            echo "fail";

        }
    }

}
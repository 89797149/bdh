<?php
/**
 * 还款
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-23
 * Time: 14:17
 */

namespace App\Modules\Repayment;


use App\Enum\ExceptionCodeEnum;
use App\Modules\Orders\OrdersModule;
use App\Modules\Users\UsersModule;
use Think\Model;

class RepaymentModule
{
    /**
     * 还款-用户还款-余额还款
     * @param string $sign 支付请求标识
     * @param int $total_fee 金额,单位元
     * @return array
     * */
    public function userRepaymentBalance(string $sign)
    {
        $order_module = new OrdersModule();
        $notify_detial = $order_module->getNotifyLogDetail($sign);
        if (empty($notify_detial)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '支付信息有误');
        }
        $notify_id = $notify_detial['id'];
        $request_detail = json_decode($notify_detial['requestJson'], true);
        $user_id = $request_detail['userId'];
        $money = (float)$request_detail['money'];
        $users_module = new UsersModule();
        $user_field = 'userId,userName,quota_arrears,balance';
        $user_detail = $users_module->getUsersDetailById($user_id, $user_field, 2);
        $predeposit = (float)$user_detail['balance'];
        if ($money > $predeposit) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '余额不足');
        }
        $db_trans = new Model();
        $db_trans->startTrans();
        $dec_res = $users_module->deductionUsersBalance($user_id, $money);
        if (!$dec_res) {
            $db_trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '用户余额扣除失败');
        }
        $dec_res = $users_module->decQuotaArrears($user_id, $money);
        if (!$dec_res) {
            $db_trans->rollback();
            return returnData(false, -1, 'error', '还款失败', '欠款金额处理失败');
        }
        $update_res = $order_module->updateNotifyLog($notify_id, '');
        if (!$update_res) {
            $db_trans->rollback();
            return returnData(false, -1, 'error', '还款失败', '支付状态修改失败');
        }
        $return_data = array(
            'money' => sprintfNumber($money),
            'notifySign' => $sign,//还款标识
        );
        return returnData($return_data, ExceptionCodeEnum::SUCCESS, 'success', '还款成功');
    }

    /**
     * 还款-用户还款-微信还款
     * @param string $sign 支付请求标识
     * @return array
     * */
    public function userRepaymentWxpay(string $sign)
    {
        $orderModule = new OrdersModule();
        $notifyInfo = $orderModule->getNotifyLogDetail($sign);
        $requestArr = json_decode($notifyInfo['requestJson'], true);
        //构建参数
        $payParam = array();
        $payParam['openId'] = $requestArr['openId'];
        $payParam['orderNo'] = $sign;
        $payParam['amount'] = $requestArr['money'];
        $payParam['attach'] = '';
        $payParam['dataFrom'] = $requestArr['dataFrom'];
        $payParam['payType'] = $requestArr['payType'];
        $payRes = unifiedOrder($payParam);//统一下单支付
        if ($payRes['result_code'] !== 'SUCCESS') {
            M()->rollback();
            $msg = $payRes['err_code_des'];
            if (!isset($payRes['err_code_des'])) {
                $msg = $payRes['return_msg'];
            }
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败，' . $msg);
        }
        $payRes['notifySign'] = $sign;
        $payRes['money'] = sprintfNumber($requestArr['money']);
        return returnData($payRes);
    }

}
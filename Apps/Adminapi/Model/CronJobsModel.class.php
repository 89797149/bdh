<?php

namespace Adminapi\Model;

use App\Modules\Pay\PayModule;
use App\Modules\Shops\ShopsModule;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;
use App\Modules\Orders\OrdersModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 定时任务服务类
 */
class CronJobsModel extends BaseModel
{

    /**
     * 自动取消未付款订单 5分钟
     */
    public function autoReOrder()
    {
        //获取所有超过十分钟未付款的订单
        $mod_order = M('orders');
        $this->autoCheckOrderDeliverMoney();
        $this->autoHandleGoodsDiffMoney();
        $config = $GLOBALS['CONFIG'];
        $repay_time = $config['rePayTime'];
        if (empty($repay_time)) {
            $repay_time = 5;
        }
        $over_time = $repay_time * 60;//订单超时时间
        $setTime = date("Y-m-d H:i:s", time() - $over_time);
        $where['createTime'] = array('elt', $setTime);

//        $where['orderStatus'] = -2;
        $where['orderFlag'] = 1;
        $where['isPay'] = 0;
        $where['orderStatus'] = array('neq', -1);
        $resdata = $mod_order->where($where)->select();
        $num = count($resdata);
        $numTime = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoReNopayOrder.log", "未付款订单数：{$num} # $numTime  \r\n", true);

        foreach ($resdata as $key => $value) {
            $userId = $value['userId'];
            $orderId = $value['orderId'];
            //取消订单
            $saveData = [];
            $saveData['orderStatus'] = -1;
            if ($value['orderType'] == 2) {
                $saveData['userDelete'] = -1;
            }
            $where = [];
            $where['orderId'] = $orderId;
            $updateRes = $mod_order->where($where)->save($saveData);//把订单状态改为用户取消(未受理前)
            $time = date('Y-m-d H:i:s');
            WSTLog("Apps/Runtime/Logs/autoReNopayOrder.log", "订单id:{$orderId} #$time\r\n", true);
            //返还优惠券
            cancelUserCoupon($orderId);
            //返还积分
            returnIntegral($orderId, $userId);

            //撤销库存
            //returnGoodsNum($resdata[$i]['orderId']);
            //返回秒杀库存
            //returnKillStock($resdata[$i]['orderId']);

            //返还属性库存
            //returnAttrStock($resdata[$i]['orderId']);

            //更新商品限制购买记录表
            updateGoodsOrderNumLimit($orderId);

            $where = [];
            $where["og.orderId"] = $orderId;
            $field = "goods.goodsId,goods.goodsName,goods.goodsStock,goods.isShopSecKill,goods.SuppPriceDiff,goods.weightG,";
            $field .= "og.id,og.goodsNums,og.goodsAttrId,og.skuId";
            $orderGoods = M('order_goods og')
                ->join('left join wst_goods goods on goods.goodsId=og.goodsId')
                ->where($where)
                ->field($field)
                ->select();
            foreach ($orderGoods as $ogKey => $ogVal) {
                $returnOrderGoodsStock = returnOrderGoodsStock($ogVal['id']);//取消订单归还商品库存
            }

            //添加系统自动取消日志
            //订单日志
//            $log_orders = M("log_orders");
//            $log_orders_data["orderId"] = $orderId;
//            $log_orders_data["logContent"] = "订单支付超时-已被系统自动取消";
//            $log_orders_data["logUserId"] = $userId;
//            $log_orders_data["logType"] = 2;
//            $log_orders_data["logTime"] = date("Y-m-d H:i:s");
//            $log_orders->add($log_orders_data);
            $content = "订单支付超时-已被系统自动取消";
            $logParams = [
                'orderId' => $orderId,
                'logContent' => $content,
                'logUserId' => 0,
                'logUserName' => '系统',
                'orderStatus' => -1,
                'payStatus' => 0,
                'logType' => 2,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);
        }
    }

    /**
     * 处理个别用户已收货补差价未到的问题
     * */
    public function autoHandleGoodsDiffMoney()
    {
        $where = [];
        $where['orders.isPay'] = 1;
        $where['orders.orderStatus'] = 4;
        $where['orders.orderFlag'] = 1;
        $where['orders.createTime'] = ['EGT', '2020-07-01'];
        $where['diff.money'] = ['GT', 0];
        $where['diff.isPay'] = 0;
        $field = 'orders.orderId,orders.shopId,orders.payFrom,orders.orderNo,orders.userId,orders.tradeNo,orders.realTotalMoney,orders.orderStatus';
        $field .= ',diff.id,diff.goodsId,diff.skuId,diff.money,diff.addTime,diff.isPay';
        $data = M('goods_pricediffe diff')
            ->join('left join wst_orders orders on orders.orderId=diff.orderId')
            ->where($where)
            ->field($field)
            ->group('diff.id')
            ->limit(10)
            ->select();
        if (empty($data)) {
            return false;
        }
        $payModule = new PayModule();
        foreach ($data as $key => $value) {
            $pay_transaction_id = $value['tradeNo'];
            $pay_total_fee = $value['realTotalMoney'] * 100;
            $pay_refund_fee = $value['money'] * 100;
            $orderId = $value['orderId'];
            $goodsId = $value['goodsId'];
            $skuId = $value['skuId'];
            $userId = $value['userId'];
            if ($value['payFrom'] == 2) {
                //微信补差价退款
                $repay = wxRefundGoods($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $orderId, $goodsId, $skuId, 2, []);
            } elseif ($value['payFrom'] == 3) {
                //余额补差价退款
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                $userBalance = M('users')->where(['userId' => $userId])->getField('balance');
                $saveData = [];
                $saveData['balance'] = $userBalance + $refundFee;

                $refundRes = M('users')->where(['userId' => $userId])->save($saveData);
                if ($refundRes == false) {
                    return false;
                }
                //写入订单日志
//                $log_orders = M("log_orders");
//                $data = [];
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "补差价退款：" . $refundFee . '元';
//                $data["logUserId"] = $userId;
//                $data["logType"] = "0";
//                $data["logTime"] = date("Y-m-d H:i:s");
//                $log_orders->add($data);
                $content = "补差价退款：" . $refundFee . '元';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => $value['orderStatus'],
                    'payStatus' => 0,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);

                //补差价余额日志
                $userBalanceTable = M('user_balance');
                $userBalanceData = [];
                $userBalanceData['userId'] = $userId;
                $userBalanceData['balance'] = $refundFee;
                $userBalanceData['dataSrc'] = 1;
                $userBalanceData['orderNo'] = $value['orderNo'];
                $userBalanceData['dataRemarks'] = "补差价退款：" . $refundFee . '元';
                $userBalanceData['balanceType'] = 1;
                $userBalanceData['createTime'] = date("Y-m-d H:i:s");
                $userBalanceData['shopId'] = $value['shopId'];
                $userBalanceTable->add($userBalanceData);

                //更改退款记录
                $save_data = [];
                $save_data['isPay'] = 1;
                $save_data['payTime'] = date('Y-m-d H:i:s');
                $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId} and skuId={$skuId}")->save($save_data);
                if (!$diffRes) {
                    return false;
                }
            } elseif ($value['payFrom'] == 1) {
                //支付宝
                //临时修复,原有逻辑直接复制过来
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                //系统打款
                $aliPayRefundRes = $payModule->aliPayRefund($value['tradeNo'], $refundFee);
                if ($aliPayRefundRes['code'] != 0) {
                    continue;
                }
                $content = "发起支付宝退款：" . $refundFee . '元';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => $value['orderStatus'],
                    'payStatus' => 0,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
        }
    }


    /**
     * 自动收货
     */
    public function autoReceivie()
    {
        $m = M('orders');
        $autoReceiveDays = (int)$GLOBALS['CONFIG']['autoReceiveDays'];
        $autoReceiveDays = ($autoReceiveDays > 0) ? $autoReceiveDays : 10;//避免有些客户没有设置值
//        $lastDay = date("Y-m-d 00:00:00", strtotime("-" . $autoReceiveDays . " days"));
        $lastDay = date("Y-m-d H:i:s", strtotime("-" . $autoReceiveDays . " days"));

        $orderDeliveryWhere = [];
        $orderDeliveryWhere['orderFlag'] = 1;
        $orderDeliveryWhere['orderStatus'] = 3;
        $orderDeliveryWhere['deliveryTime'] = array('lt', $lastDay);

        $rs1 = $m->where($orderDeliveryWhere)->select();
        $rs1 = empty($rs1) ? [] : $rs1;

        //数据筛选 - 检索 所有的数据
        $result = $m->where("orderFlag=1 and orderStatus=3 and deliveryTime is null")->order('orderId asc')->limit(15)->select();
        //筛选 - 快跑者已配送不为空
        //$rs = [];
        $rs2 = [];
        if (!empty($result)) {
            /*
             *   新加逻辑针对 自提和门店配送的有发货时间
             *   快跑者和非自提的 没有发货时间
             *   而定时任务又依赖发货时间 针对于原先异常数据做服务降级根据订单创建时间
             */
            $orderCreateWhere = [];
            $orderCreateWhere['orderFlag'] = 1;
            //$orderCreateWhere['isSelf'] = 0;
            $orderCreateWhere['orderStatus'] = 3;
            //$orderCreateWhere['deliverType'] = 4;
            $orderCreateWhere['createTime'] = array('lt', $lastDay);
            $rs2 = $m->where($orderCreateWhere)->select();
            $rs2 = empty($rs2) ? [] : $rs2;
            if (empty($rs1)) {
                $rs = $rs2;
            }
        }
        $rs = array_merge($rs1, $rs2);
        $goods_module = new GoodsModule();
        $order_module = new OrdersModule();
        if (!empty($rs)) {
            foreach ($rs as $detail) {
                $content = "系统自动确认收货";
                $logParams = [
                    'orderId' => $detail['orderId'],
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 4,
                    'payStatus' => $detail['isPay'],//支付状态【0：未支付|1：已支付|2：已退款】
                    'logType' => 2,
                ];
                $order_module->confirmReceipt($detail['orderId'], 1, '', $logParams);
            }
        }
//        if (!empty($rs)) {
//            $mlogo = M('log_orders');
//            $msm = M('log_sys_moneys');
//            $mse = M('user_score');
//            foreach ($rs as $key => $v) {
//                //结束订单状态
//                $data = array();
//                $data['receiveTime'] = date('Y-m-d 00:00:00');
//                $data['orderStatus'] = 4;
//                if ($v['payFrom'] == 4) {
//                    $data['pay_time'] = date('Y-m-d H:i:s');
//                }
//
//                //更新订单状态
//                $orderSaveWhere = [];
//                $orderSaveWhere['orderStatus'] = 3;
//                $orderSaveWhere['orderFlag'] = 1;
//                $orderSaveWhere['orderId'] = $v['orderId'];
////                'orderId=' . $v['orderId'] . " and orderStatus=3 and orderFlag=1"
//                $rsStatus = $m->where($orderSaveWhere)->save($data);
//
//                if (false !== $rsStatus) {
//                    //修改商品销量
//                    $sql = "UPDATE __PREFIX__goods g, __PREFIX__order_goods og, __PREFIX__orders o SET g.saleCount=g.saleCount+og.goodsNums WHERE g.goodsId= og.goodsId AND og.orderId = o.orderId AND o.orderId=" . $v['orderId'] . " AND o.userId=" . $v['userId'];
//                    $this->execute($sql);
//                    //增加积分
//                    //$sql = "UPDATE __PREFIX__users set userScore=userScore+".$v["orderScore"].",userTotalScore=userTotalScore+".$v["orderScore"]." WHERE userId=".$v['userId'];
//                    //$this->execute($sql);
//                    //插入日志
////                    $data = array();
////                    $data["orderId"] = $v['orderId'];
////                    $data["logContent"] = "系统自动确认收货";
////                    $data["logUserId"] = $v['userId'];
////                    $data["logType"] = 2;
////                    $data["logTime"] = date('Y-m-d H:i:s');
////                    $mlogo->add($data);
//                    $content = "系统自动确认收货";
//                    $logParams = [
//                        'orderId' => $v['orderId'],
//                        'logContent' => $content,
//                        'logUserId' => 0,
//                        'logUserName' => '系统',
//                        'orderStatus' => 4,
//                        'payStatus' => $v['isPay'],//支付状态【0：未支付|1：已支付|2：已退款】
//                        'logType' => 2,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                    //修改积分
//                    if ((int)$GLOBALS['CONFIG']['isOrderScore'] == 1) {
//                        $v['orderScore'] = (int)$v['orderScore'];
//                        if ($v['orderScore'] <= 0) {
//                            $v['orderScore'] = 0;
//                        }
//                        $sql = "UPDATE __PREFIX__users set userScore=userScore+" . $v["orderScore"] . ",userTotalScore=userTotalScore+" . $v["orderScore"] . " WHERE userId=" . $v['userId'];
//                        $this->execute($sql);
//                        $data = array();
//                        $data["userId"] = $v['userId'];
//                        $data["score"] = $v["orderScore"];
//                        $data["dataSrc"] = 1;
//                        $data["dataId"] = $v["orderId"];
//                        $data["dataRemarks"] = "交易获得";
//                        $data["scoreType"] = 1;
//                        $data["createTime"] = date('Y-m-d H:i:s');
//                        $mse->add($data);
//                    }
//                    //平台积分支付支出
//                    if ($v["scoreMoney"] > 0) {
//                        $data = array();
//                        $data["targetType"] = 0;
//                        $data["targetId"] = $v['userId'];
//                        $data["dataSrc"] = 2;
//                        $data["dataId"] = $v['orderId'];
//                        $data["moneyRemark"] = "订单【" . $v["orderNo"] . "】支付 " . $v["useScore"] . " 个积分，支出 ￥" . $v["scoreMoney"];
//                        $data["moneyType"] = 2;
//                        $data["money"] = $v["scoreMoney"];
//                        $data["createTime"] = date('Y-m-d H:i:s');
//                        $data["dataFlag"] = 1;
//                        $msm->add($data);
//                    }
//                    //平台收取订单佣金
//                    if ($v["poundageMoney"] > 0) {
//                        $data = array();
//                        $data["targetType"] = 1;
//                        $data["targetId"] = $v["shopId"];
//                        $data["dataSrc"] = 1;
//                        $data["dataId"] = $v['orderId'];
//                        $data["moneyRemark"] = "收取订单【" . $v["orderNo"] . "】" . $v["poundageRate"] . "%的佣金 ￥" . $v["poundageMoney"];
//                        $data["moneyType"] = 1;
//                        $data["money"] = $v["poundageMoney"];
//                        $data["createTime"] = date('Y-m-d H:i:s');
//                        $data["dataFlag"] = 1;
//                        $msm->add($data);
//                    }
//
//                    //判断当前订单是否有差价(列表)需要退
//                    $mod_goods_pricediffe = M('goods_pricediffe');
//                    $finwhere['orderId'] = $v["orderId"];
//                    $finwhere['userId'] = $v['userId'];
//                    $finwhere['isPay'] = 0;
//                    $data_goods_pricediffe = $mod_goods_pricediffe->where($finwhere)->select();
//                    $mod_orders = M('orders');
//                    $mw['orderId'] = $v['orderId'];
//                    $mw['userId'] = $v['userId'];
//                    $data_mod_orders = $mod_orders->where($mw)->find();
//                    $userId = $data_mod_orders['userId'];
//                    $orderId = $data_mod_orders['orderId'];
//                    if (count($data_goods_pricediffe) > 0) {//如果有需要退的 进行退款操作
//                        //退款备参
//                        $pay_transaction_id = $data_mod_orders['tradeNo'];
//                        $pay_total_fee = $data_mod_orders['realTotalMoney'] * 100;
//                        for ($i = 0; $i < count($data_goods_pricediffe); $i++) {
//                            //返还商品库存-start
//                            $goods_id = (int)$data_goods_pricediffe[$i]['goodsId'];
//                            $sku_id = (int)$data_goods_pricediffe[$i]['skuId'];
//                            $goods_num = (int)$data_goods_pricediffe[$i]['goosNum'];
//                            $real_weight = (float)$data_goods_pricediffe[$i]['weightG'];//实际称重
////                            $goods_field = 'goodsId,SuppPriceDiff,weightG';
////                            $goods_data = $goods_module->getGoodsInfoById($goods_id, $goods_field, 2);
////                            $goods_weightG = (float)$goods_data['weightG'];//包装系数
////                            if ($sku_id > 0) {
////                                $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
////                                $goods_weightG = (float)$sku_detail['weigetG'];
////                            }
////                            $buy_weight = $goods_num * $goods_weightG;//购买的重量数量
////                            //返还商品库存-start
////                            $return_stock = $buy_weight - $real_weight;
////                            if ($return_stock > 0) {//返库存
////                                $order_goods_result = $order_module->getOrderGoodsInfoByParams(array(
////                                    'orderId' => $data_goods_pricediffe[$i]['orderId'],
////                                    'goodsId' => $data_goods_pricediffe[$i]['goodsId'],
////                                    'skuId' => $data_goods_pricediffe[$i]['skuId']
////                                ));
////                                if ($order_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
////                                    if ($goods_data['SuppPriceDiff'] == 1) {
////                                        $return_stock = $return_stock / 1000;
////                                    }
////                                    $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $return_stock, 1, 2);
////                                }
////                            }
////                            //返还商品库存-end
//                            if ($data_goods_pricediffe[$i]['money'] > 0) {
//                                $pay_refund_fee = $data_goods_pricediffe[$i]['money'] * 100;
//                                if ($data_mod_orders['payFrom'] == 2) {
//                                    $repay = wxRefundGoods($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $mw['orderId'], $data_goods_pricediffe[$i]['goodsId'],$data_goods_pricediffe[$i]['skuId'], 2, []);
//                                } elseif ($data_mod_orders['payFrom'] == 3) {
//                                    //后加余额补差价退款
//                                    $refundFee = $pay_refund_fee / 100;//需要退款的金额
//                                    $userBalance = M('users')->where(['userId' => $userId])->getField('balance');
//                                    $saveData = [];
//                                    $saveData['balance'] = $userBalance + $refundFee;
//                                    M('users')->where(['userId' => $userId])->save($saveData);
//                                    //写入订单日志
////                                    $log_orders = M("log_orders");
////                                    $data = [];
////                                    $data["orderId"] = $orderId;
////                                    $data["logContent"] = "补差价退款：" . $refundFee . '元';
////                                    $data["logUserId"] = $userId;
////                                    $data["logType"] = "0";
////                                    $data["logTime"] = date("Y-m-d H:i:s");
////                                    $log_orders->add($data);
//                                    $content = "补差价退款：" . $refundFee . '元';
//                                    $logParams = [
//                                        'orderId' => $v['orderId'],
//                                        'logContent' => $content,
//                                        'logUserId' => 0,
//                                        'logUserName' => '系统',
//                                        'orderStatus' => 4,
//                                        'payStatus' => $v['isPay'],//支付状态【0：未支付|1：已支付|2：已退款】
//                                        'logType' => 2,
//                                        'logTime' => date('Y-m-d H:i:s'),
//                                    ];
//                                    M('log_orders')->add($logParams);
//
//                                    //补差价余额日志
//                                    $userBalanceTable = M('user_balance');
//                                    $userBalanceData = [];
//                                    $userBalanceData['userId'] = $userId;
//                                    $userBalanceData['balance'] = $refundFee;
//                                    $userBalanceData['dataSrc'] = 1;
//                                    $userBalanceData['orderNo'] = $data_mod_orders['orderNo'];
//                                    $userBalanceData['dataRemarks'] = "补差价退款：" . $refundFee . '元';
//                                    $userBalanceData['balanceType'] = 1;
//                                    $userBalanceData['createTime'] = date("Y-m-d H:i:s");
//                                    $userBalanceData['shopId'] = $data_mod_orders['shopId'];
//                                    $userBalanceTable->add($userBalanceData);
//
//                                    //更改退款记录
//                                    $save_data = [];
//                                    $save_data['isPay'] = 1;
//                                    $save_data['payTime'] = date('Y-m-d H:i:s');
//                                    $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$data_goods_pricediffe[$i]['goodsId']} and userId= {$userId} and skuId={$data_goods_pricediffe[$i]['skuId']}")->save($save_data);
//                                    if ($diffRes) {
//                                        $repay = true;//退款申请成功
//                                    } else {
//                                        $repay = false;
//                                    }
//                                }
////                                if ($repay !== true) {
////                                    $statusCode["statusCode"] = "000063";
////                                    $statusCode["info"] = "差价退款失败";
////                                    $statusCode["data"] = $repay;
////                                    return $statusCode;
////                                }
//                            }
//                        }
//                    }
//                    //判断是否是首次下单
//                    //是否奖励邀请券 判断是否是第一次下单(第一笔订单 之前一定是0笔) 且是否拥有邀请人 并邀请人有优惠券待恢复使用
//                    //判断用户是否存在有效订单不得大于1
//                    //if(M('orders')->limit(1)->where("orderStatus=4 and userId = '{$userId}'")->count() == 0){
//                    //if (M('orders')->limit(1)->where("orderStatus=4 and userId = '" . $v['userId'] . "'")->count() == 1) {
//                    //改写首单判断逻辑 根据users表-firstOrder字段判断是否为首单 -1非首单 1首单
//                    $userInfo = M("users")->where(['userId' => $v['userId']])->field('firstOrder')->find();
//                    if ($userInfo['firstOrder'] == 1) {
////                    if (M('orders')->limit(2)->where("orderStatus=4 and userId = '" . $v['userId'] . "'")->count() == 1) {
//                        //本次订单是否满足十元
//                        $find_user_invitation = M('user_invitation')->where("UserToId = '" . $v['userId'] . "'")->find();
//                        //判断被邀请人ID是否还存在
//                        $userInfo = M('users')->where("userId = '{$find_user_invitation['userId']}' and userFlag = 1")->find();
//                        if ($userInfo) {
//                            $saveData['invitationStatus'] = 1;
//                            $saveData['updateTime'] = date('Y-m-d H:i:s');
//                            M('user_invitation')->where("UserToId = '{$userId}'")->save($saveData);
//                        }
//                        if ($data_mod_orders['realTotalMoney'] >= $GLOBALS["CONFIG"]["InvitationOrderMoney"]) {
//                            //查询是否存在邀请人
//                            if ($find_user_invitation) {
//                                //是否存在待恢复使用的优惠券
//                                $mod_coupons_users = M('coupons_users');
//                                $coupons_save['dataFlag'] = 1;
//                                $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->save($coupons_save);
//                                // $res_mod_coupons_users = $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->select();
//                                // if($res_mod_coupons_users){
//                                //   //恢复冻结的优惠券
//                                //
//                                //   for($i=0;$i<count($res_mod_coupons_users);$i++){
//                                //     $coupons_users_save['dataFlag'] = 1;
//                                //     $mod_coupons_users->where("id ='{$res_mod_coupons_users[$i]['id']}'")->save($coupons_users_save);
//                                //   }
//                                //
//                                //
//                                // }
//                            }
////                            //领过权益以后 首单状态更新
////                            $userSave['firstOrder'] = -1;
////                            $userId = $v['userId'];
////                            M('users')->where("userId = '$userId' and userFlag = 1")->save($userSave);
//                        }
//                        //领过权益以后 首单状态更新
//                        $userSave['firstOrder'] = -1;
//                        $userId = $v['userId'];
//                        M('users')->where("userId = '$userId' and userFlag = 1")->save($userSave);
//                    }
//                    //判断商品是否属于分销商品
//                    checkGoodsDistribution($v['orderId']);
//                    //发放地推奖励
//                    grantPullNewAmount($v['orderId']);
//                }
//            }
//        }
    }

    /**
     * 自动好评
     */
    public function autoGoodAppraise()
    {
        $m = M('orders');
        $autoAppraiseDays = (int)$GLOBALS['CONFIG']['autoAppraiseDays'];
        $autoAppraiseDays = ($autoAppraiseDays > 0) ? $autoAppraiseDays : 7;//避免有些客户没有设置值
//        $lastDay = date("Y-m-d 00:00:00", strtotime("-" . $autoAppraiseDays . " days"));
        $lastDay = date("Y-m-d H:i:s", strtotime("-" . $autoAppraiseDays . " days"));
        $rs = $m->where('receiveTime<"' . $lastDay . '" and orderStatus=4 and orderFlag=1 and isAppraises=0')->getField("orderId,userId,orderScore,shopId");
        if (!empty($rs)) {
            $mog = M('order_goods');
            $mga = M('goods_appraises');
            $gm = M('goods_scores');
            $ms = M('user_score');
            foreach ($rs as $key => $v) {
                //标记订单已评价
                $sql = "update __PREFIX__orders set isAppraises=1 where orderId=" . $v['orderId'];
                $this->execute($sql);
                //获取该订单下的商品
                $ogRs = $mog->where('orderId=' . $v['orderId'])->select();
                foreach ($ogRs as $vg) {
                    //自动评价
                    $data = array();
                    $data["goodsId"] = $vg['goodsId'];
                    $data["shopId"] = $v['shopId'];
                    $data["userId"] = $v['userId'];
                    $data["goodsScore"] = 5;
                    $data["timeScore"] = 5;
                    $data["serviceScore"] = 5;
                    $data["content"] = "系统自动好评";
                    $data['goodsAttrId'] = $vg['goodsAttrId'];
                    $data["isShow"] = 1;
                    $data["createTime"] = date('Y-m-d H:i:s');
                    $data["orderId"] = $v['orderId'];
                    $mga->add($data);
                    //增加商品评分
                    $sql = "SELECT * FROM __PREFIX__goods_scores WHERE goodsId=" . $vg['goodsId'];
                    $goodsScores = $this->queryRow($sql);
                    if (empty($goodsScores)) {
                        $data = array();
                        $data["goodsId"] = $vg['goodsId'];
                        $data["shopId"] = $v['shopId'];
                        $data["goodsScore"] = 5;
                        $data["goodsUsers"] = 1;
                        $data["timeScore"] = 5;
                        $data["timeUsers"] = 1;
                        $data["serviceScore"] = 5;
                        $data["serviceUsers"] = 1;
                        $data["totalScore"] = 15;
                        $data["totalUsers"] = 1;
                        $gm->add($data);
                    } else {
                        $sql = "UPDATE __PREFIX__goods_scores set  totalUsers = totalUsers +1 , totalScore = totalScore + 15
						,goodsUsers = goodsUsers +1 , goodsScore = goodsScore +5 ,timeUsers = timeUsers +1 , timeScore = timeScore +5
						,serviceUsers = serviceUsers +1 , serviceScore = serviceScore +5 WHERE goodsId = " . $vg['goodsId'];
                        $this->execute($sql);
                    }
                    //增加店铺评分
                    $sql = "UPDATE __PREFIX__shop_scores set totalUsers = totalUsers +1 , totalScore = totalScore + 15
					    ,goodsUsers = goodsUsers +1 , goodsScore = goodsScore +5,timeUsers = timeUsers +1 , timeScore = timeScore +5
						,serviceUsers = serviceUsers +1 , serviceScore = serviceScore +5 WHERE shopId = " . $v['shopId'];
                    $this->execute($sql);
                    //如果有评价积分的话设置评价积分
                    if ((int)$GLOBALS['CONFIG']['isAppraisesScore'] == 1) {
                        $appraisesScore = (int)$GLOBALS['CONFIG']['appraisesScore'];
                        if ($appraisesScore <= 0) {
                            $appraisesScore = 0;
                        }
                        $sql = "UPDATE __PREFIX__users set userScore=userScore+" . $appraisesScore . ",userTotalScore=userTotalScore+" . $appraisesScore . " WHERE userId=" . $v['userId'];
                        $this->execute($sql);
                        //增加积分记录
                        $data = array();
                        $data["userId"] = $v['userId'];
                        $data["score"] = $appraisesScore;
                        $data["dataSrc"] = 2;
                        $data["dataId"] = $v['orderId'];
                        $data["dataRemarks"] = "订单评价获得";
                        $data["scoreType"] = 1;
                        $data["createTime"] = date('Y-m-d H:i:s');
                        $ms->add($data);
                    }
                }
            }
        }
    }

    /**
     * 自动结算
     */
    public function autoSettlement()
    {
        //获取上一月没有计结算的订单
        $lastMonth = WSTMonth(-1);
        $sql = "select distinct shopId from __PREFIX__orders where left(receiveTime,7)='" . $lastMonth . "' and orderStatus=4
	 	     and orderFlag=1 and o.payType=1 and ";
        $rs = $this->query($sql);
        if (!empty($rs)) {
            $m = M('order_settlements');
            foreach ($rs as $v) {
                //获取商家结算账户
                $sql = "select bankName,bankNo,bankUserName from __PREFIX__shops s
		        left join __PREFIX__banks b on b.bankId=s.bankId where s.shopId=" . $v['shopId'];
                $accRs = $this->queryRow($sql);
                if (empty($accRs)) continue;
                //按商家进行结算
                $sql = "select sum(totalMoney+deliverMoney) settlementMoney,sum(poundageMoney) poundageMoney
	 			     from __PREFIX__orders where left(receiveTime,7)='" . $lastMonth . "' and orderStatus=4
	 			     and settlementId=0 and orderFlag=1 and shopId=" . $v['shopId'];
                $totalRs = $this->queryRow($sql);
                if ((float)$totalRs['settlementMoney'] == 0) continue;
                $data = array();
                $data['settlementType'] = 0;
                $data['shopId'] = $v['shopId'];
                $data['accName'] = $accRs['bankName'];
                $data['accNo'] = $accRs['bankNo'];
                $data['accUser'] = $accRs['bankUserName'];
                $data['createTime'] = date('Y-m-d H:i:s');
                $data['orderMoney'] = $totalRs['realTotalMoney'];
                $data['settlementMoney'] = $totalRs['settlementMoney'] - $totalRs['poundageMoney'];
                $data['poundageMoney'] = $totalRs['poundageMoney'];
                $data['isFinish'] = 0;
                $settlementId = $m->add($data);
                if (false !== $settlementId) {
                    //修改结算单号
                    $sql = "update __PREFIX__order_settlements set settlementNo='" . date('y') . sprintf("%08d", $settlementId) . "'
					where  settlementId=" . $settlementId;
                    $this->execute($sql);
                    //修改订单ID的结算ID
                    $sql = "update __PREFIX__orders set settlementId=" . $settlementId . " where left(receiveTime,7)='" . $lastMonth . "'
			              and orderStatus=4 and settlementId=0 and orderFlag=1 and shopId=" . $v['shopId'];
                    $this->execute($sql);
                }
            }
        }
    }

    /**
     * 拼团失败，自动退费用、退库存
     * 弃用
     */
    /*public function autoReAssembleOrder(){
        $curTime = date('Y-m-d H:i:s');
        //取出拼团失败的活动
        $assembleActivity = M('user_activity_relation as uar')->join("wst_assemble as a on uar.pid = a.pid")->where("a.endTime <= '" . $curTime . "' and a.state = -1 and a.isRefund = 0")->field('uar.*')->select();
        if (!empty($assembleActivity)) {
            $m = D('Adminapi/Orders');
            $am = M('assemble');
            $uarm = M('user_activity_relation');
            $pid_arr = array();
            foreach ($assembleActivity as $v){
                $pid_arr[] = $v['pid'];
            }
            $pid_arr = array_unique($pid_arr);
            $am->where(array('pid'=>array('in',$pid_arr)))->save(array('isRefund'=>1));
            foreach ($assembleActivity as $v) {
                $m->assembleOrderCancel(array('userId'=>$v['uid'], 'orderId'=>$v['orderId']));
            }
        }
    }*/

    /**
     * 自动更新拼团状态
     */
    public function autoUpdateAssembleState()
    {
        $curTime = date('Y-m-d H:i:s');
        $am = M('assemble');
        //取出无状态的拼团活动
        $assembleList = $am->where('state = 0 and isRefund = 0')->select();
        if (!empty($assembleList)) {
            $m = D('Adminapi/Orders');
            $uarm = M('user_activity_relation');
            $uar_arr = array();
            $uar_data = $uarm->select();
            if (!empty($uar_data)) {
                foreach ($uar_data as $v) {
                    $uar_arr[$v['pid']] .= $v['orderId'] . ",";
                }
            }
            $orderModule = new OrdersModule();
            foreach ($assembleList as $v) {
//                $myfile = fopen("debug.txt", "a+") or die("Unable to open file!");
//                $txt = "走了吗";
//                fwrite($myfile, "拼团：我来了：$txt \n");
//                fclose($myfile);
                $orderIdArr = explode(',', rtrim($uar_arr[$v['pid']], ','));
                $orderList = $orderModule->getOrderListById($orderIdArr);
                if (empty($orderList)) {
                    continue;
                }
                $buyPeopleNum = 0;//已支付人数
                $paiedOrderIdArr = array();//已付款订单
                foreach ($orderList as $orderRow) {
                    if ($orderRow['isPay'] == 1) {
                        $buyPeopleNum += 1;
                        $paiedOrderIdArr[] = $orderRow['orderId'];
                    }
                }
                $state = 0;
                $v['buyPeopleNum'] = count($paiedOrderIdArr);
                if ($v['buyPeopleNum'] == $v['groupPeopleNum']) {
                    $state = 1;
//                    $orderids = rtrim($uar_arr[$v['pid']], ',');
                    $orderids = implode(',', $paiedOrderIdArr);
                    $updateRes = M('orders')->where("orderId in (" . $orderids . ") and isPay=1")->save(array('orderStatus' => 0));
                    if ($updateRes === false) {
                        continue;
                    }
                }
                if ($v['endTime'] <= $curTime && $v['buyPeopleNum'] != $v['groupPeopleNum']) {
                    $state = -1;
                }
                if ($state != 0) {
                    $data = array('state' => $state);
                    $awhere = array('pid' => $v['pid'], 'aid' => $v['aid']);
                    $am->where($awhere)->save($data);
                    $uarm->where($awhere)->save($data);
                    //拼团失败，自动退费用、退库存
                    if ($state == -1) {
                        $am->where($awhere)->save(array('isRefund' => 1));
                        $uar_data_list = $uarm->where($awhere)->select();
                        if (!empty($uar_data_list)) {
                            foreach ($uar_data_list as $v) {
                                //$m->assembleOrderCancel(array('userId' => $v['uid'], 'orderId' => $v['orderId']));
                                $m->assembleOrderCancel($v['uid'], $v['orderId']);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * (每月)自动发放优惠券
     */
    public function autoSendCoupon()
    {
        //商城/通用优惠券
        $couponList = M('coupons')->where(array('shopId' => 0, 'couponType' => 5, 'validStartTime' => array('ELT', date('Y-m-d')), 'validEndTime' => array('EGT', date('Y-m-d')), 'dataFlag' => 1, 'type' => 1))->select();
        if (empty($couponList)) return false;

        $userList = M('users')->where(array('userFlag' => 1, 'expireTime' => array('GT', date('Y-m-d H:i:s'))))->select();
        if (empty($userList)) return false;

        foreach ($userList as $v) {

            $buyUserRecord = M('buy_user_record')->where(array('userId' => $v['userId']))->order('buyTime desc')->find();
            if (empty($buyUserRecord) || $buyUserRecord['buyTime'] == date('Y-m-d H:i:s')) continue;

            if (date('d H:i:s') == date('d H:i:s', strtotime($v['expireTime'])) && date('Y-m-d H:i:s') != $v['expireTime']) {
                $data = array();
                foreach ($couponList as $vc) {
                    $data[] = array(
                        'couponId' => $vc['couponId'],
                        'userId' => $v['userId'],
                        'receiveTime' => date('Y-m-d H:i:s'),
                        'couponStatus' => 1,
                        'dataFlag' => 1,
                        'orderNo' => '',
                        'ucouponId' => 0
                    );
                }
                M('coupons_users')->addAll($data);
            }
        }
    }

    //(每分钟)(盘点端)自动更改盘点任务状态(弃用)
    //比如：盘点时间到了，自动更改盘点状态，将待盘点改为盘点中
    public function autoUpdateInventoryState()
    {
        $where['startTime'] = array('ELT', date('Y-m-d H:i:s'));
        $where['endTime'] = array('GT', date('Y-m-d H:i:s'));
        $where['state'] = 0;
        $where['iFlag'] = 1;
        $im = M('inventory');
        $inventoryList = $im->where($where)->select();
        if (empty($inventoryList)) exit();
        $result = $im->where($where)->save(array('state' => 1));
        if ($result) {
            $cim = M('child_inventory');
            foreach ($inventoryList as $v) {
                $where_n = array('iid' => $v['iid'], 'state' => 0, 'ciFlag' => 1);
                $cim->where($where_n)->save(array('state' => 1));
            }
        }
    }

    //(每分钟)(盘点端)自动更改入库任务状态（弃用）
    //比如：入库时间到了，自动更改入库状态，将待入库改为入库中
    public function autoUpdateInWarehouseState()
    {
        $where['startTime'] = array('ELT', date('Y-m-d H:i:s'));
        $where['endTime'] = array('GT', date('Y-m-d H:i:s'));
        $where['state'] = 0;
        $where['iwFlag'] = 1;
        M('in_warehouse')->where($where)->save(array('state' => 1));
    }

    /**
     * 5分钟未支付，自动取消Pos订单
     * 每分钟执行一次
     */
    public function autoCancelPosOrder()
    {
        $time = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $gm = M('goods');
        $pogm = M('pos_orders_goods');
        $pom = M('pos_orders');
        $where = array('addtime' => array('LT', $time), 'state' => 1);
        $order_id_arr = $pom->where($where)->getField('id', true);
        if (!empty($order_id_arr)) {
            $pom->where($where)->save(array('state' => 2));
//            $goodsList = $pogm->where(array('orderid' => array('in', $order_id_arr)))->select();
//            if (!empty($goodsList)) {
//                foreach ($goodsList as $v) {
//                    $SuppPriceDiff = $gm->where(array('goodsId' => $v['goodsId']))->find()['SuppPriceDiff'];
//                    $v_number = ($SuppPriceDiff == 1) ? number_format($v['weight'] / 1000, 3) : $v['number'];
//                    $gm->where(array('goodsId' => $v['goodsId']))->setInc('goodsStock', $v_number);
//
//                    //更新进销存系统商品的库存
//                    //updateJXCGoodsStock($v['goodsId'], $v['number'], 0);
//                }
//            }
        }
    }

    //(每分钟)精准营销数据入redis
    //每分钟执行一次
    public function precisionMarketingDataToRedis()
    {
        $precision_marketing_list = M('precision_marketing')->where(array('state' => 0))->order('createTime asc')->select();
        if (!empty($precision_marketing_list)) {
            $redis = new \Redis();
            $redis->connect(C('redis_host1'), C('redis_port1'));
            foreach ($precision_marketing_list as $k => $v) {
                $where = array();
                if ($v['bigType'] == 1) {//大分类，1：用户唤醒功能
                    if ($v['startCreateTime'] > '0000-00-00 00:00:00') {//开始创建时间
                        $where['createTime'][] = array('EGT', $v['startCreateTime']);
                    }
                    if ($v['endCreateTime'] > '0000-00-00 00:00:00') {//结束创建时间
                        $where['createTime'][] = array('ELT', $v['endCreateTime']);
                    }
                    if ($v['startUserExpireTime'] > '0000-00-00 00:00:00') {//开始过期时间
                        $where['expireTime'][] = array('EGT', $v['startUserExpireTime']);
                    }
                    if ($v['endUserExpireTime'] > '0000-00-00 00:00:00') {//结束过期时间
                        $where['expireTime'][] = array('ELT', $v['endUserExpireTime']);
                    }
                    $where['balance'][] = array('EGT', $v['startBalance']);//开始余额
                    if ($v['endBalance'] > 0) {//结束余额
                        $where['balance'][] = array('ELT', $v['endBalance']);
                    }
                    $where['userScore'][] = array('EGT', $v['startIntegral']);//开始积分
                    if ($v['endIntegral'] > 0) {//结束积分
                        $where['userScore'][] = array('ELT', $v['endIntegral']);
                    }
                    if (!empty($v['isSignIn'])) {//今日是否已签到 ，0:否，1：是
                        $where['lastSignInTime'] = array('like', date('Y-m-d') . '%');
                    }
                    if ($v['startLastTime'] > '0000-00-00 00:00:00') {//开始登录时间
                        $where['lastTime'][] = array('EGT', $v['startLastTime']);
                    }
                    if ($v['endLastTime'] > '0000-00-00 00:00:00') {//结束登录时间
                        $where['lastTime'][] = array('ELT', $v['endLastTime']);
                    }

                    $user_id_arr = array();
                    $condition = "";
                    if ($v['endConsumeNum'] > 0) {//结束消费次数
                        $condition = "order_counts >= " . $v['startConsumeNum'];//开始消费次数
                        $condition .= " and order_counts <= " . $v['endConsumeNum'];
                    }
                    if (!empty($condition)) {
                        $order_list = M('orders')->field('userId,count(*) as order_counts')->where(array('orderStatus' => 4, 'orderFlag' => 1))->group('userId')->having($condition)->select();
                        if (!empty($order_list)) {
                            foreach ($order_list as $v) {
                                $user_id_arr[] = $v['userId'];
                            }
                        }
                    }
                    if (!empty($user_id_arr)) {
                        $where['userId'] = array('in', $user_id_arr);
                    }
                    $user_list = M('users')->where($where)->limit($v['sendPeopleNum'])->select();
                    if (!empty($user_list)) {
                        foreach ($user_list as $uv) {
                            $redis->lpush('precision_marketing_' . $v['id'], $uv);
                        }
                    }
                }
            }
        }
    }

    //(每分钟)redis中精准营销数据发送到用户
    //每分钟执行一次
    public function sendMsgToUserFromPrecisionMarketingData()
    {
        $precision_marketing_m = M('precision_marketing');
        $precision_marketing_list = $precision_marketing_m->where(array('state' => 0))->order('createTime asc')->select();
        if (!empty($precision_marketing_list)) {
            $coupons_m = M('coupons');
            $redis = new \Redis();
            $redis->connect(C('redis_host1'), C('redis_port1'));
            foreach ($precision_marketing_list as $k => $v) {
                $user_list = $redis->get('precision_marketing_' . $v['id']);
                $user_list = json_decode($user_list, true);
                if (!empty($user_list)) {
                    if ($v['bigType'] == 1) {//大分类，1：用户唤醒功能
                        $noticeMode = explode(',', $v['noticeMode']);//通知方式，1：短信通知、2：极光推送、3：公众号
                        foreach ($user_list as $uv) {
                            //执行方式，1：积分+文案、2：优惠券+文案、3：余额+文案、4：会员体验+文案、5：纯文案通知
                            if ($v['implementType'] == 1) {//积分+文案
                                if ($v['integral'] > 0) {
                                    $data = array(
                                        'userId' => $uv['userId'],
                                        'score' => $v['integral'],
                                        'dataSrc' => 13,
                                        'dataId' => 0,
                                        'dataRemarks' => '赠送',
                                        'scoreType' => 1,
                                        'createTime' => date('Y-m-d H:i:s')
                                    );
                                    M('user_score')->add($data);
                                }
                            } else if ($v['implementType'] == 2) {//优惠券+文案
                                if (!empty($v['couponId'])) {
                                    $couponId_arr = explode(',', $v['couponId']);
                                    $data = array();
                                    foreach ($couponId_arr as $v) {
                                        $coupons_info = $coupons_m->where(array('couponId' => $v))->find();
                                        $expireDays = $coupons_info['expireDays'];
                                        $data[] = array(
                                            'couponId' => $v,
                                            'userId' => $uv['userId'],
                                            'receiveTime' => date('Y-m-d H:i:s'),
                                            'couponStatus' => 1,
                                            'dataFlag' => 1,
                                            'orderNo' => '',
                                            'ucouponId' => 0,
                                            'couponExpireTime' => date("Y-m-d H:i:s", strtotime("+$expireDays days")),
                                            'userToId' => 0
                                        );
                                    }
                                    M('coupons_users')->addAll($data);
                                }
                            } else if ($v['implementType'] == 3) {//余额+文案
                                if ($v['balance'] > 0) {
                                    $data = array(
                                        'userId' => $uv['userId'],
                                        'balance' => $v['balance'],
                                        'dataSrc' => 2,
                                        'orderNo' => '',
                                        'dataRemarks' => '赠送',
                                        'balanceType' => 1,
                                        'createTime' => date('Y-m-d H:i:s'),
                                        'shopId' => 0
                                    );
                                    M('user_balance')->add($data);
                                }
                            } else if ($v['implementType'] == 4) {//会员体验+文案
                                if ($v['day'] > 0) {
                                    $curTime = date("Y-m-d H:i:s");
                                    $expireTime = ($uv['expireTime'] > $curTime) ? date("Y-m-d H:i:s", strtotime("+" . $v['day'] . " days", strtotime($uv['expireTime']))) : date('Y-m-d H:i:s', strtotime("+" . $v['day'] . " days"));
                                    M('users')->where(array('userId' => $uv['userId']))->save(array('expireTime' => $expireTime));
                                }
                            }
                            if (in_array(1, $noticeMode)) {//短信通知
                                WSTSendSMS($uv['userPhone'], $v['copywriting']);
                            }
                            if (in_array(2, $noticeMode)) {//极光推送

                            }
                            if (in_array(3, $noticeMode)) {//公众号

                            }
                        }
                    }
                }
                $precision_marketing_m->where(array('id' => $v['id']))->save(array('state' => 1));
            }
        }
    }

    /**
     * PS:配送费没有彻底查到原因前,不要变动该代码
     * 检查配送费 PS;只针对统一运费模式
     * */
    public function autoCheckOrderDeliverMoney()
    {
        $config = $GLOBALS['CONFIG'];
        //1:叠加运费||2:统一运费
        if ($config['setDeliveryMoney'] == 2) {
            //只处理统一运费
            $orderTab = M('orders');
            $date = date('Y-m-d');
            $startDate = date("Y-m-d ", strtotime("-1 day")) . ' 00:00:00';
            //$startDate = $date.' 00:00:00';
            $endDate = $date . ' 23:59:59';
            //$startDate = "2020-03-15 00:00:00";
            //$endDate = "2020-03-27 23:59:59";

            //个别退款失败的,进行二次处理start
            $cancelWhere = [];
            $cancelWhere['isPay'] = 1;
            $cancelWhere['isRefund'] = 0;
            $cancelWhere['payFrom'] = 2;
            $cancelWhere['orderStatus'] = -6;
            $cancelWhere['createTime'] = ['between', [$startDate, $endDate]];
            $cancelOrderOKList = $orderTab->where($cancelWhere)->select();

            if (!empty($cancelOrderOKList)) {
                foreach ($cancelOrderOKList as $value) {
                    order_WxPayRefund($value['tradeNo'], $value['orderId'], 0, 2);//可整单退款
                }
            }
            //个别退款失败的,进行二次处理end

            $where = [];
            $where['isPay'] = 1;
            $where['isSelf'] = 0;
            $where['orderStatus'] = ['EGT', 0];
            $where['createTime'] = ['between', [$startDate, $endDate]];
            $orderList = $orderTab->where($where)->field('orderId,orderNo,orderToken,deliverMoney,setDeliveryMoney')->select();
            $mergeTab = M('order_merge');
            foreach ($orderList as $key => $value) {
                $mergeInfo = $mergeTab->where(['orderToken' => $value['orderToken']])->find();
                if (!empty($mergeInfo['value'])) {
                    $orderNoArr = explode('A', $mergeInfo['value']);
                    if ($mergeInfo['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $where = [];
                        $where['orderNo'] = ["IN", $orderNoArr];
                        $singleOrders = $orderTab->where($where)->field('orderId,orderNo,orderToken,deliverMoney,setDeliveryMoney')->select();
                        $singleDelivery = 0;
                        foreach ($singleOrders as $singleValue) {
                            $singleDelivery += $singleValue['deliverMoney'];
                        }
                        if ($singleDelivery < $config['deliveryMoney']) {
                            $orderTab->where(['orderToken' => $value['orderToken']])->save(['deliverMoney' => 0]);
                            $orderTab->where(['orderId' => $singleOrders[0]['orderId']])->save(['deliverMoney' => $value['setDeliveryMoney']]);
                            //var_dump($value['orderNo']);
                        }
                        unset($singleDelivery);
                    }
                }
            }
        }
    }

    /**
     * 定时更新直播相关的参数
     * */
    public function autoUpdateLivePlay()
    {
        $livePlayModel = new \Home\Model\LivePlayModel();
        $livePlayModel->getComponentAccessToken();//获取component_access_token令牌
        $livePlayModel->getAuthorizerRefreshToken();//获取刷新令牌authorizer_refresh_token
        $livePlayModel->getAccessToken();//获取授权码
        $livePlayModel->getWxGoodsStatus();//更新商品状态
        $livePlayModel->getWxLiveplayStatus();//更新直播状态
    }

    /**
     * 定时上下架商品
     * */
    public function autoSaleGoods()
    {
        $goods_model = new \Home\Model\GoodsModel();
        $goods_model->autoSaleGoods();
    }

    /**
     * @return bool
     * 定时受理订单、打印小票
     */
    public function autoAcceptanceOrder()
    {
        //获取【开启自动受理状态】【在营业时间】的店铺
        $shopsModule = new ShopsModule();
        $getShopsList = $shopsModule->getShopsList();
        if (empty($getShopsList)) {
            return false;
        }
        //获取店铺打印机配置
        foreach ($getShopsList as $k => $v) {
            $getPrintsInfo = $shopsModule->getPrintsList($v['shopId']);
            $printsInfo = [];
            if (!empty($getPrintsInfo)) {
                foreach ($getPrintsInfo as $key => $value) {
                    if ($value['isDefault'] == 1) {//是否默认【0:否|1:默认】
                        $printsInfo = $value;
                    }
                }
            }
            $getShopsList[$k]['printsInfo'] = $printsInfo;
        }
        $shopIds = array_get_column($getShopsList, 'shopId');
        //获取店铺未受理的订单
        $ordersModule = new OrdersModule();
        $getToBeAcceptedOrdersList = $ordersModule->getToBeAcceptedOrdersList($shopIds);
        foreach ($getToBeAcceptedOrdersList as $k => $v) {
            foreach ($getShopsList as $key => $val) {
                if ((int)$val['shopId'] == (int)$v['shopId']) {
                    $getToBeAcceptedOrdersList[$k]['printsInfo'] = $val['printsInfo'];
                }
            }
            $ordersModule->shopOrderAccept($getToBeAcceptedOrdersList[$k]);
        }
    }
}
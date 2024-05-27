<?php

namespace Home\Model;

use App\Enum\ExceptionCodeEnum;
use App\Enum\Orders\OrderEnum;
use App\Enum\Orders\OrderGoodsEnum;
use App\Modules\Chain\ChainServiceModule;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Orders\OrdersServiceModule;
use App\Modules\Pay\PayModule;
use App\Modules\PSD\DriverModule;
use App\Modules\PSD\LineModule;
use App\Modules\PSD\RegionModule;
use App\Modules\PurchaseBill\PurchaseBillModule;
use App\Modules\Settlement\CustomerSettlementBillModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\ShopStaffMember\ShopStaffMemberModule;
use App\Modules\Sorting\SortingModule;
use App\Modules\Supplier\SupplierModule;
use App\Modules\Users\UsersModule;
use App\Modules\Users\UsersServiceModule;
use App\Modules\Users\GoodsServiceModule;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单服务类
 */
class OrdersModel extends BaseModel
{
    /**
     * 获以订单列表
     */
    public function getOrdersList($obj)
    {
        $userId = $obj["userId"];
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders WHERE userId = $userId AND orderStatus <>-1 order by createTime desc";
        return $m->pageQuery($sql);
    }

    /**
     * 取消订单记录
     */
    public function getcancelOrderList($obj)
    {
        $userId = $obj["userId"];
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders WHERE userId = $userId AND orderStatus =-1 order by createTime desc";
        return $m->pageQuery($sql);

    }

    /**
     * 获取订单详情
     */
    public function getOrdersDetails($obj)
    {
        $orderId = $obj["orderId"];
        $sql = "SELECT od.*,sp.shopName
				FROM __PREFIX__orders od, __PREFIX__shops sp
				WHERE od.shopId = sp.shopId And orderId = $orderId ";
        $rs = $this->query($sql);;
        return $rs;

    }

    /**
     * 获取订单商品信息
     */
    public function getOrdersGoods($obj)
    {

        $orderId = $obj["orderId"];
        $sql = "SELECT g.*,og.goodsNums as ogoodsNums,og.goodsPrice as ogoodsPrice
				FROM __PREFIX__order_goods og, __PREFIX__goods g
				WHERE og.orderId = $orderId AND og.goodsId = g.goodsId ";
        $rs = $this->query($sql);
        return $rs;

    }

    /**
     *
     * 获取订单商品详情
     */
    public function getOrdersGoodsDetails($obj)
    {

        $orderId = $obj["orderId"];
        $sql = "SELECT g.*,og.goodsNums as ogoodsNums,og.goodsPrice as ogoodsPrice ,ga.id as gaId
				FROM __PREFIX__order_goods og, __PREFIX__goods g
				LEFT JOIN __PREFIX__goods_appraises ga ON g.goodsId = ga.goodsId AND ga.orderId = $orderId
				WHERE og.orderId = $orderId AND og.goodsId = g.goodsId";
        $rs = $this->query($sql);
        return $rs;

    }

    /**
     *
     * 获取订单商品详情
     */
    public function getPayOrders($obj)
    {
        $orderType = (int)$obj["orderType"];
        $orderId = 0;
        $orderunique = 0;
        if ($orderType > 0) {//来在线支付接口
            $uniqueId = $obj["uniqueId"];
            if ($orderType == 1) {
                $orderId = (int)$uniqueId;
            } else {
                $orderunique = WSTAddslashes($uniqueId);
            }
        } else {
            $orderId = (int)$obj["orderId"];
            $orderunique = session("WST_ORDER_UNIQUE");
        }

        if ($orderId > 0) {
            $sql = "SELECT o.orderId, o.orderNo, g.goodsId, g.goodsName ,og.goodsAttrName , og.goodsNums ,og.goodsPrice
				FROM __PREFIX__order_goods og, __PREFIX__goods g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.goodsId = g.goodsId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.needPay>0 AND o.orderStatus = -2 AND o.orderId =$orderId";
        } else {
            $sql = "SELECT o.orderId, o.orderNo, g.goodsId, g.goodsName ,og.goodsAttrName , og.goodsNums ,og.goodsPrice
				FROM __PREFIX__order_goods og, __PREFIX__goods g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.goodsId = g.goodsId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.needPay>0 AND o.orderStatus = -2 AND o.orderunique ='$orderunique'";
        }

        $rslist = $this->query($sql);

        $orders = array();
        foreach ($rslist as $key => $order) {
            $orders[$order["orderNo"]][] = $order;
        }
        if ($orderId > 0) {
            $sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE orderId = $orderId AND isPay=0 AND payType=1 AND needPay>0 AND orderStatus = -2 AND orderFlag =1";
        } else {
            $sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE orderunique = '$orderunique' AND isPay=0 AND payType=1 AND needPay>0 AND orderStatus = -2 AND orderFlag =1";
        }
        $payInfo = self::queryRow($sql);
        $data["orders"] = $orders;
        $data["needPay"] = $payInfo["needPay"];
        return $data;

    }

    /**
     * 下单
     */
    public function submitOrder()
    {
        $rd = array('status' => -1);
        $USER = session('WST_USER');
        $goodsmodel = D('Home/Goods');
        $morders = D('Home/Orders');
        $totalMoney = 0;
        $totalCnt = 0;
        $userId = (int)session('WST_USER.userId');

        $consigneeId = (int)I("consigneeId");
        $payway = (int)I("payway");
        $isself = (int)I("isself");
        $needreceipt = (int)I("needreceipt");
        $orderunique = WSTGetMillisecond() . $userId;

        $sql = "select * from __PREFIX__cart where userId = $userId and isCheck=1 and goodsCnt>0";
        $shopcart = $this->query($sql);

        $catgoods = array();
        $order = array();
        if (empty($shopcart)) {
            $rd['msg'] = '购物车为空!';
            return $rd;
        } else {
            //整理及核对购物车数据
            $paygoods = session('WST_PAY_GOODS');
            $cartIds = array();
            for ($i = 0; $i < count($shopcart); $i++) {
                $cgoods = $shopcart[$i];
                $goodsId = (int)$cgoods["goodsId"];
                $goodsAttrId = (int)$cgoods["goodsAttrId"];

                if (in_array($goodsId, $paygoods)) {
                    $goods = $goodsmodel->getGoodsSimpInfo($goodsId, $goodsAttrId);
                    //核对商品是否符合购买要求
                    if (empty($goods)) {
                        $rd['msg'] = '找不到指定的商品!';
                        return $rd;
                    }
                    if ($goods['goodsStock'] <= 0) {
                        $rd['msg'] = '对不起，商品' . $goods['goodsName'] . '库存不足!';
                        return $rd;
                    }
                    if ($goods['isSale'] != 1) {
                        $rd['msg'] = '对不起，商品库' . $goods['goodsName'] . '已下架!';
                        return $rd;
                    }
                    $goods["cnt"] = $cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["shopgoods"][] = $goods;
                    $catgoods[$goods["shopId"]]["deliveryFreeMoney"] = $goods["deliveryFreeMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["deliveryMoney"] = $goods["deliveryMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["totalCnt"] = $catgoods[$goods["shopId"]]["totalCnt"] + $cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["totalMoney"] = $catgoods[$goods["shopId"]]["totalMoney"] + ($goods["cnt"] * $goods["shopPrice"]);
                    $cartIds[] = $cgoods["cartId"];
                }
            }
            $morders->startTrans();
            try {
                $ordersInfo = $morders->addOrders($userId, $consigneeId, $payway, $needreceipt, $catgoods, $orderunique, $isself);
                $morders->commit();
                if (!empty($cartIds)) {
                    $sql = "delete from __PREFIX__cart where userId = $userId and cartId in (" . implode(",", $cartIds) . ")";
                    $this->execute($sql);
                }
                $rd['orderIds'] = implode(",", $ordersInfo["orderIds"]);
                $rd['status'] = 1;
                session("WST_ORDER_UNIQUE", $orderunique);
            } catch (Exception $e) {
                $morders->rollback();
                $rd['msg'] = '下单出错，请联系管理员!';
            }
            return $rd;
        }
    }

    /**
     * 废弃
     * 生成订单
     */
//    public function addOrders($userId, $consigneeId, $payway, $needreceipt, $catgoods, $orderunique, $isself)
//    {
//
//        $orderInfos = array();
//        $orderIds = array();
//        $orderNos = array();
//        $remarks = I("remarks");
//
//        $addressInfo = UserAddressModel::getAddressDetails($consigneeId);
//        $m = M('orderids');
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);
//
//        foreach ($catgoods as $key => $shopgoods) {
//            //生成订单ID
//            $orderSrcNo = $m->add(array('rnd' => microtime(true)));
//            $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));
//            //创建订单信息
//            $data = array();
//            $pshopgoods = $shopgoods["shopgoods"];
//            $shopId = $pshopgoods[0]["shopId"];
//            $data["orderNo"] = $orderNo;
//            $data["shopId"] = $shopId;
//            $deliverType = intval($pshopgoods[0]["deliveryType"]);
//            $data["userId"] = $userId;
//
//            $data["orderFlag"] = 1;
//            $data["totalMoney"] = $shopgoods["totalMoney"];
//            if ($isself == 1) {//自提
//                $deliverMoney = 0;
//            } else {
//                $deliverMoney = ($shopgoods["totalMoney"] < $shopgoods["deliveryFreeMoney"]) ? $shopgoods["deliveryMoney"] : 0;
//            }
//            $data["deliverMoney"] = $deliverMoney;
//            $data["payType"] = $payway;
//            $data["deliverType"] = $deliverType;
//            $data["userName"] = $addressInfo["userName"];
//            $data["areaId1"] = $addressInfo["areaId1"];
//            $data["areaId2"] = $addressInfo["areaId2"];
//            $data["areaId3"] = $addressInfo["areaId3"];
//            $data["communityId"] = $addressInfo["communityId"];
//            $data["userAddress"] = $addressInfo["paddress"] . " " . $addressInfo["address"];
//            $data["userTel"] = $addressInfo["userTel"];
//            $data["userPhone"] = $addressInfo["userPhone"];
//
//            //$data['orderScore'] = floor($data["totalMoney"])*$rewardScoreMultiple;
//            $data['orderScore'] = getOrderScoreByOrderScoreRate($data["totalMoney"]);
//            $data["isInvoice"] = $needreceipt;
//            $data["orderRemarks"] = $remarks;
//            $data["requireTime"] = I("requireTime");
//            $data["invoiceClient"] = I("invoiceClient");
//            $data["isAppraises"] = 0;
//            $data["isSelf"] = $isself;
//
//            $isScorePay = (int)I("isScorePay", 0);
//            $scoreMoney = 0;
//            $useScore = 0;
//
//            $shop_info = D('Home/Shops')->getShopInfo($shopId);
//            if (!empty($shop_info) && $shop_info['commissionRate'] > 0) {
//                $data["poundageRate"] = (float)$shop_info['commissionRate'];
//                $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);
//            } else if ($GLOBALS['CONFIG']['poundageRate'] > 0) {
//                $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
//                $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);
//            } else {
//                $data["poundageRate"] = 0;
//                $data["poundageMoney"] = 0;
//            }
//            if ($GLOBALS['CONFIG']['isOpenScorePay'] == 1 && $isScorePay == 1) {//积分支付
//                $baseScore = WSTOrderScore();
//                $baseMoney = WSTScoreMoney();
//                $sql = "select userId,userScore from __PREFIX__users where userId=$userId";
//                $user = $this->queryRow($sql);
//                $useScore = $baseScore * floor($user["userScore"] / $baseScore);
//                $scoreMoney = $baseMoney * floor($user["userScore"] / $baseScore);
//                $orderTotalMoney = $shopgoods["totalMoney"] + $deliverMoney;
//                if ($orderTotalMoney < $scoreMoney) {//订单金额小于积分金额
//                    $useScore = $baseScore * floor($orderTotalMoney / $baseMoney);
//                    $scoreMoney = $baseMoney * floor($orderTotalMoney / $baseMoney);
//                }
//                $data["useScore"] = $useScore;
//                $data["scoreMoney"] = $scoreMoney;
//            }
//            $data["realTotalMoney"] = $shopgoods["totalMoney"] + $deliverMoney - $scoreMoney;
//            $data["needPay"] = $shopgoods["totalMoney"] + $deliverMoney - $scoreMoney;
//
//            $data["createTime"] = date("Y-m-d H:i:s");
//            if ($payway == 1) {
//                $data["orderStatus"] = -2;
//            } else {
//                $data["orderStatus"] = 0;
//            }
//
//            $data["orderunique"] = $orderunique;
//            $data["isPay"] = 0;
//            if ($data["needPay"] == 0) {
//                $data["isPay"] = 1;
//            }
//
//            $morders = M('orders');
//            $orderId = $morders->add($data);
//
//            //订单创建成功则建立相关记录
//            if ($orderId > 0) {
//                $orderInfo = $morders->where(['orderId' => $orderId])->find();
//                $push = D('Adminapi/Push');
//                $push->postMessage(6, $userId, $orderNo, $shopId);
//
//                if ($GLOBALS['CONFIG']['isOpenScorePay'] == 1 && $isScorePay == 1 && $useScore > 0) {//积分支付
////                    $sql = "UPDATE __PREFIX__users set userScore=userScore-" . $useScore . " WHERE userId=" . $userId;
////                    $rs = $this->execute($sql);
//                    //积分处理-start
//                    $users_service_module = new UsersServiceModule();
//                    $score = (int)$useScore;
//                    $users_id = $userId;
//                    $users_service_module->deduction_users_score($users_id, $score);
////积分处理-end
//
//                    $data = array();
//                    $m = M('user_score');
//                    $data["userId"] = $userId;
//                    $data["score"] = $useScore;
//                    $data["dataSrc"] = 1;
//                    $data["dataId"] = $orderId;
//                    $data["dataRemarks"] = "订单支付-扣积分";
//                    $data["scoreType"] = 2;
//                    $data["createTime"] = date('Y-m-d H:i:s');
//                    $m->add($data);
//                }
//
//                $orderIds[] = $orderId;
//                //建立订单商品记录表
//                $mog = M('order_goods');
//                foreach ($pshopgoods as $key => $sgoods) {
//                    $data = array();
//                    $data["orderId"] = $orderId;
//                    $data["goodsId"] = $sgoods["goodsId"];
//                    $data["goodsAttrId"] = (int)$sgoods["goodsAttrId"];
//                    if ($sgoods["attrVal"] != '') $data["goodsAttrName"] = $sgoods["attrName"] . ":" . $sgoods["attrVal"];
//                    $data["goodsNums"] = $sgoods["cnt"];
//                    $data["goodsPrice"] = $sgoods["shopPrice"];
//                    $data["goodsName"] = $sgoods["goodsName"];
//                    $data["goodsThums"] = $sgoods["goodsThums"];
//                    $mog->add($data);
//                }
//
//                if ($payway == 0) {
//                    //建立订单记录
////                    $data = array();
////                    $data["orderId"] = $orderId;
////                    $data["logContent"] = ($pshopgoods[0]["deliverType"] == 0) ? "下单成功" : "下单成功等待审核";
////                    $data["logUserId"] = $userId;
////                    $data["logType"] = 0;
////                    $data["logTime"] = date('Y-m-d H:i:s');
////                    $mlogo = M('log_orders');
////                    $mlogo->add($data);
//
//                    $content = ($pshopgoods[0]["deliverType"] == 0) ? "下单成功" : "下单成功等待审核";;
//                    $logParams = [
//                        'orderId' => $orderId,
//                        'logContent' => $content,
//                        'logUserId' => $userId,
//                        'logUserName' => '用户',
//                        'orderStatus' => $orderInfo['orderStatus'],
//                        'payStatus' => $orderInfo['isPay'],
//                        'logType' => 0,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                    //建立订单提醒
//                    $sql = "SELECT userId,shopId,shopName FROM __PREFIX__shops WHERE shopId=$shopId AND shopFlag=1  ";
//                    $users = $this->query($sql);
//                    $morm = M('order_reminds');
//                    for ($i = 0; $i < count($users); $i++) {
//                        $data = array();
//                        $data["orderId"] = $orderId;
//                        $data["shopId"] = $shopId;
//                        $data["userId"] = $users[$i]["userId"];
//                        $data["userType"] = 0;
//                        $data["remindType"] = 0;
//                        $data["createTime"] = date("Y-m-d H:i:s");
//                        $morm->add($data);
//                    }
//
//                    //修改库存
//                    foreach ($pshopgoods as $key => $sgoods) {
//                        $sgoods_cnt = gChangeKg($sgoods["goodsId"], $sgoods['cnt'], 1);
//                        $sql = "update __PREFIX__goods set goodsStock=goodsStock-" . $sgoods_cnt . " where goodsId=" . $sgoods["goodsId"];
//                        $this->execute($sql);
//
//                        //更新进销存系统商品的库存
//                        //updateJXCGoodsStock($sgoods["goodsId"], $sgoods['cnt'], 1);
//
//                        if ((int)$sgoods["goodsAttrId"] > 0) {
//                            $sql = "update __PREFIX__goods_attributes set attrStock=attrStock-" . $sgoods_cnt . " where id=" . $sgoods["goodsAttrId"];
//                            $this->execute($sql);
//                        }
//                    }
//                } else {
////                    $data = array();
////                    $data["orderId"] = $orderId;
////                    $data["logContent"] = "订单已提交，等待支付";
////                    $data["logUserId"] = $userId;
////                    $data["logType"] = 0;
////                    $data["logTime"] = date('Y-m-d H:i:s');
////                    $mlogo = M('log_orders');
////                    $mlogo->add($data);
//                    $content = '订单已提交，等待支付';
//                    $logParams = [
//                        'orderId' => $orderId,
//                        'logContent' => $content,
//                        'logUserId' => $userId,
//                        'logUserName' => '用户',
//                        'orderStatus' => -2,
//                        'payStatus' => 0,
//                        'logType' => 0,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                }
//            }
//        }
//
//        return array("orderIds" => $orderIds);
//
//    }

    /**
     * 获取订单参数
     */
    public function getOrderListByIds()
    {
        $orderunique = session("WST_ORDER_UNIQUE");
        $orderInfos = array('totalMoney' => 0, 'isMoreOrder' => 0, 'list' => array());
        $sql = "select orderId,orderNo,totalMoney,deliverMoney,realTotalMoney
		         from __PREFIX__orders where userId=" . (int)session('WST_USER.userId') . "
		         and orderunique='" . $orderunique . "' and orderFlag=1 ";
        $rs = $this->query($sql);
        if (!empty($rs)) {
            $totalMoney = 0;
            $realTotalMoney = 0;
            foreach ($rs as $key => $v) {
                $orderInfos['list'][] = array('orderId' => $v['orderId'], 'orderNo' => $v['orderNo']);
                $totalMoney += $v['totalMoney'] + $v['deliverMoney'];
                $realTotalMoney += $v['realTotalMoney'];
            }
            $orderInfos['totalMoney'] = $totalMoney;
            $orderInfos['realTotalMoney'] = $realTotalMoney;
            $orderInfos['isMoreOrder'] = (count($rs) > 0) ? 1 : 0;
        }
        return $orderInfos;
    }

    /**
     * 获取待付款订单
     */
    public function queryByPage($obj)
    {
        $userId = $obj["userId"];
        $pcurr = (int)I("pcurr", 0);
        $sql = "SELECT o.* FROM __PREFIX__orders o
				WHERE userId = $userId AND orderFlag=1 order by orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 获取待付款订单
     */
    public function queryPayByPage($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus", 0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr", 0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus =-2 AND o.isPay = 0 AND needPay >0 AND o.payType = 1 AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }


    /**
     * 获取待确认收货
     */
    public function queryReceiveByPage($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus", 0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr", 0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp WHERE o.userId = $userId AND o.orderStatus =3 AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 获取待发货订单
     */
    public function queryDeliveryByPage($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus", 0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr", 0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus in ( 0,1,2 ) AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);

        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 获取退款
     */
    public function queryRefundByPage($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus", 0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $sdate = WSTAddslashes(I("sdate"));
        $edate = WSTAddslashes(I("edate"));
        $pcurr = (int)I("pcurr", 0);
        //必须是在线支付的才允许退款

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp
		        WHERE o.userId = $userId AND (o.orderStatus in (-3,-4,-5) or (o.orderStatus in (-1,-4,-6,-7) and payType =1 AND o.isPay =1)) AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 获取取消的订单
     */
    public function queryCancelOrders($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus", 0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr", 0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus in (-1,-6,-7) AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 获取待评价交易
     */
    public function queryAppraiseByPage($obj)
    {
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr", 0);
        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp WHERE o.userId = $userId AND o.shopId=sp.shopId ";
        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND o.userName like '%$userName%'";
        }
        if ($shopName != "") {
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " AND o.orderStatus = 4";
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql, $pcurr);
        $orderList = $pages["root"];
        if (count($orderList) > 0) {
            $orderIds = array();
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (" . implode(',', $orderIds) . ")";
            $glist = $this->query($sql);
            $goodslist = array();
            for ($i = 0; $i < count($glist); $i++) {
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for ($i = 0; $i < count($orderList); $i++) {
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 废弃
     * 取消订单
     */
//    public function orderCancel($obj)
//    {
//        $userId = (int)$obj["userId"];
//        $orderId = (int)$obj["orderId"];
//        $rsdata = array('status' => -1);
//        //判断订单状态，只有符合状态的订单才允许改变
//        $sql = "SELECT orderId,orderNo,orderStatus,useScore FROM __PREFIX__orders WHERE orderId = $orderId and orderFlag = 1 and userId=" . $userId;
//        $rsv = $this->queryRow($sql);
//        $cancelStatus = array(0, 1, 2, -2);//未受理,已受理,打包中,待付款订单
//        if (!in_array($rsv["orderStatus"], $cancelStatus)) return $rsdata;
//        //如果是未受理和待付款的订单直接改为"用户取消【受理前】"，已受理和打包中的则要改成"用户取消【受理后-商家未知】"，后者要给商家知道有这么一回事，然后再改成"用户取消【受理后-商家已知】"的状态
//        $orderStatus = -6;//取对商家影响最小的状态
//        if ($rsv["orderStatus"] == 0 || $rsv["orderStatus"] == -2) $orderStatus = -1;
//        if ($orderStatus == -6 && I('rejectionRemarks') == '') return $rsdata;//如果是受理后取消需要有原因
//        $sql = "UPDATE __PREFIX__orders set orderStatus = " . $orderStatus . " WHERE orderId = $orderId and userId=" . $userId;
//        $rs = $this->execute($sql);
//
//        $sql = "select ord.deliverType, ord.orderId, og.goodsId ,og.goodsId, og.goodsNums
//				from __PREFIX__orders ord , __PREFIX__order_goods og
//				WHERE ord.orderId = og.orderId AND ord.orderId = $orderId";
//        $ogoodsList = $this->query($sql);
//        //获取商品库存
//        for ($i = 0; $i < count($ogoodsList); $i++) {
//            $sgoods = $ogoodsList[$i];
//            $sgoods_goodsNums = gChangeKg($sgoods["goodsId"], $sgoods['goodsNums'], 1);
//            $sql = "update __PREFIX__goods set goodsStock=goodsStock+" . $sgoods_goodsNums . " where goodsId=" . $sgoods["goodsId"];
//            $this->execute($sql);
//
//            //更新进销存系统商品的库存
//            //updateJXCGoodsStock($sgoods["goodsId"], $sgoods['goodsNums'], 0);
//        }
//        $sql = "Delete From __PREFIX__order_reminds where orderId=" . $orderId . " AND remindType=0";
//        $this->execute($sql);
//
//        //返还用户已使用的优惠券
//        cancelUserCoupon($orderId);
//
//        if ($rsv["useScore"] > 0) {
//            $sql = "UPDATE __PREFIX__users set userScore=userScore+" . $rsv["useScore"] . " WHERE userId=" . $userId;
//            $this->execute($sql);
//
//            $data = array();
//            $m = M('user_score');
//            $data["userId"] = $userId;
//            $data["score"] = $rsv["useScore"];
//            $data["dataSrc"] = 3;
//            $data["dataId"] = $orderId;
//            $data["dataRemarks"] = "取消订单返还";
//            $data["scoreType"] = 1;
//            $data["createTime"] = date('Y-m-d H:i:s');
//            $m->add($data);
//        }
//        $data = array();
//        $m = M('log_orders');
//        $data["orderId"] = $orderId;
//        $data["logContent"] = "用户已取消订单" . (($orderStatus == -6) ? "：" . I('rejectionRemarks') : "");
//        $data["logUserId"] = $userId;
//        $data["logType"] = 0;
//        $data["logTime"] = date('Y-m-d H:i:s');
//        $ra = $m->add($data);
//        $rsdata["status"] = $ra;
//        return $rsdata;
//
//    }

    /**
     * 废弃
     * 用户确认收货
     */
    public function orderConfirm($obj)
    {
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $type = (int)$obj["type"];
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderScore,orderStatus,poundageRate,poundageMoney,shopId,useScore,scoreMoney FROM __PREFIX__orders WHERE orderId = $orderId and userId=" . $userId;
        $rsv = $this->queryRow($sql);
        if ($rsv["orderStatus"] != 3) {
            $rsdata["status"] = -1;
            return $rsdata;
        }
        //收货则给用户增加积分
        if ($type == 1) {
            $sql = "UPDATE __PREFIX__orders set orderStatus = 4,receiveTime='" . date("Y-m-d H:i:s") . "'  WHERE orderId = $orderId and userId=" . $userId;
            $rs = $this->execute($sql);

            //修改商品销量
            $sql = "UPDATE __PREFIX__goods g, __PREFIX__order_goods og, __PREFIX__orders o SET g.saleCount=g.saleCount+og.goodsNums WHERE g.goodsId= og.goodsId AND og.orderId = o.orderId AND o.orderId=$orderId AND o.userId=" . $userId;
            $rs = $this->execute($sql);

            //修改积分
            if ($GLOBALS['CONFIG']['isOrderScore'] == 1 && $rsv["orderScore"] > 0) {
                $rsv['orderScore'] = (int)$rsv['orderScore'];
                if ($rsv['orderScore'] <= 0) {
                    $rsv['orderScore'] = 0;
                }
                $sql = "UPDATE __PREFIX__users set userScore=userScore+" . $rsv["orderScore"] . ",userTotalScore=userTotalScore+" . $rsv["orderScore"] . " WHERE userId=" . $userId;
                $rs = $this->execute($sql);

                $data = array();
                $m = M('user_score');
                $data["userId"] = $userId;
                $data["score"] = $rsv["orderScore"];
                $data["dataSrc"] = 1;
                $data["dataId"] = $orderId;
                $data["dataRemarks"] = "交易获得";
                $data["scoreType"] = 1;
                $data["createTime"] = date('Y-m-d H:i:s');
                $m->add($data);
            }
            //积分支付支出
            if ($rsv["scoreMoney"] > 0) {
                $data = array();
                $m = M('log_sys_moneys');
                $data["targetType"] = 0;
                $data["targetId"] = $userId;
                $data["dataSrc"] = 2;
                $data["dataId"] = $orderId;
                $data["moneyRemark"] = "订单【" . $rsv["orderNo"] . "】支付 " . $rsv["useScore"] . " 个积分，支出 ￥" . $rsv["scoreMoney"];
                $data["moneyType"] = 2;
                $data["money"] = $rsv["scoreMoney"];
                $data["createTime"] = date('Y-m-d H:i:s');
                $data["dataFlag"] = 1;
                $m->add($data);
            }
            //收取订单佣金
            if ($rsv["poundageMoney"] > 0) {
                $data = array();
                $m = M('log_sys_moneys');
                $data["targetType"] = 1;
                $data["targetId"] = $rsv["shopId"];
                $data["dataSrc"] = 1;
                $data["dataId"] = $orderId;
                $data["moneyRemark"] = "收取订单【" . $rsv["orderNo"] . "】" . $rsv["poundageRate"] . "%的佣金 ￥" . $rsv["poundageMoney"];
                $data["moneyType"] = 1;
                $data["money"] = $rsv["poundageMoney"];
                $data["createTime"] = date('Y-m-d H:i:s');
                $data["dataFlag"] = 1;
                $m->add($data);
            }

        } else {
            if (I('rejectionRemarks') == '') return $rsdata;//如果是拒收的话需要填写原因
            $sql = "UPDATE __PREFIX__orders set orderStatus = -3 WHERE orderId = $orderId and userId=" . $userId;
            $rs = $this->execute($sql);
        }
        //增加记录
        $data = array();
        $m = M('log_orders');
        $data["orderId"] = $orderId;
        $data["logContent"] = ($type == 1) ? "用户已收货" : "用户拒收：" . I('rejectionRemarks');
        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;
        return $rsdata;
    }

    /**
     * 获取订单详情
     * @param $where
     * @return mixed
     */
    public function getOrderDetail($where)
    {
        return (array)M('orders')->where($where)->find();
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetails($obj)
    {
        $config = $GLOBALS['CONFIG'];
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=" . $userId . " or shopId=" . $shopId . ")";
        $order = $this->queryRow($sql);
        if (empty($order)) return $data;
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] != 1) {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] += $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] += $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        $data["order"] = $order;
        $sql = "select og.orderId, og.goodsId ,g.goodsSn,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,og.skuId,og.skuSpecAttr
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        $data["goodsList"] = $goods;

        $sql = "SELECT * FROM __PREFIX__log_orders WHERE orderId = $orderId ";
        $logs = $this->query($sql);
        $data["logs"] = $logs;
        //发票详情
        $data['invoiceInfo'] = [];
        if (isset($order['isInvoice']) && $order['isInvoice'] == 1) {
            $data['invoiceInfo'] = M('invoice')->where("id='" . $order['invoiceClient'] . "'")->find();
        }
        //买家信息
        $order['buyer'] = M('users')->where("userId='" . $order['userId'] . "'")->find();
        return $data;

    }

    /**
     * 获取订单过秤数据
     */
    public function getOrderElectronicScaleList($obj)
    {
        $config = $GLOBALS['CONFIG'];
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=" . $userId . " or shopId=" . $shopId . ")";
        $order = $this->queryRow($sql);
        if (empty($order)) return $data;
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] != 1) {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] += $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {//处理常规非常规订单拆分后的运费问题
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] += $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        $data["order"] = $order;
        $sql = "select og.orderId, og.goodsId ,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId AND g.SuppPriceDiff = 1";
        $goods = $this->query($sql);
        $data["goodsList"] = $goods;

        return $data;

    }

    /**
     * 获取用户指定状态的订单数目
     */
    public function getUserOrderStatusCount($obj)
    {
        $userId = (int)$obj["userId"];
        $data = array();
        $sql = "select orderStatus,COUNT(*) cnt from __PREFIX__orders WHERE orderStatus in (0,1,2,3) and orderFlag=1 and userId = $userId GROUP BY orderStatus";
        $olist = $this->query($sql);
        $data = array('-3' => 0, '-2' => 0, '2' => 0, '3' => 0, '4' => 0);
        for ($i = 0; $i < count($olist); $i++) {
            $row = $olist[$i];
            if ($row["orderStatus"] == 0 || $row["orderStatus"] == 1 || $row["orderStatus"] == 2) {
                $row["orderStatus"] = 2;
            }
            $data[$row["orderStatus"]] = $data[$row["orderStatus"]] + $row["cnt"];
        }
        //获取未支付订单
        $sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus = -2 and isRefund=0 and payType=1 and orderFlag=1 and isPay = 0 and needPay >0 and userId = $userId";
        $olist = $this->query($sql);
        $data[-2] = $olist[0]['cnt'];

        //获取退款订单
        $sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus in (-3,-4,-6,-7) and isRefund=0 and payType=1 and orderFlag=1 and userId = $userId";
        $olist = $this->query($sql);
        $data[-3] = $olist[0]['cnt'];
        //获取待评价订单
        $sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus =4 and isAppraises=0 and orderFlag=1 and userId = $userId";
        $olist = $this->query($sql);
        $data[4] = $olist[0]['cnt'];

        //获取商城信息
        $sql = "select count(*) cnt from __PREFIX__messages WHERE  receiveUserId=" . $userId . " and msgStatus=0 and msgFlag=1 ";
        $olist = $this->query($sql);
        $data[100000] = empty($olist) ? 0 : $olist[0]['cnt'];

        return $data;

    }

    /**
     * 获取用户指定状态的订单数目
     */
    public function getShopOrderStatusCount($obj)
    {
        $shopId = (int)$obj["shopId"];
        $rsdata = array();
        //待受理订单
        $sql = "SELECT COUNT(*) cnt FROM __PREFIX__orders WHERE shopId = $shopId AND orderStatus = 0 ";
        $olist = $this->queryRow($sql);
        $rsdata[0] = $olist['cnt'];

        //取消-商家未知的 / 拒收订单
        $sql = "SELECT COUNT(*) cnt FROM __PREFIX__orders WHERE shopId = $shopId AND orderStatus in (-3,-6)";
        $olist = $this->queryRow($sql);
        $rsdata[5] = $olist['cnt'];
        $rsdata[100] = $rsdata[0] + $rsdata[5];

        //获取商城信息
        $sql = "select count(*) cnt from __PREFIX__messages WHERE  receiveUserId=" . (int)$obj["userId"] . " and msgStatus=0 and msgFlag=1 ";
        $olist = $this->query($sql);
        $rsdata[100000] = empty($olist) ? 0 : $olist[0]['cnt'];

        return $rsdata;

    }


    /**
     * 获取商家订单列表
     */
//    public function queryShopOrders($obj)
//    {
//        $userId = (int)$obj["userId"];
//        $shopId = (int)$obj["shopId"];
//        $pcurr = (int)I("pcurr", 0);
//        $orderStatus = (int)I("statusMark");
//        $export = I('export', 0);//导出(0:否|1:是)
//
//        $orderNo = WSTAddslashes(I("orderNo"));
//        $userName = WSTAddslashes(I("userName"));
//        $userPhone = WSTAddslashes(I("userPhone"));
//        $userAddress = WSTAddslashes(I("userAddress"));
//        $rsdata = array();
//        //$sql = "SELECT orderNo,isSelf,orderId,userId,userName,userAddress,totalMoney,realTotalMoney,orderStatus,createTime FROM __PREFIX__orders WHERE shopId = $shopId ";
//        //$sql = "select o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone from ".__PREFIX__orders." o left join ".__PREFIX__users." u on u.userId=o.userId where o.shopId='".$shopId."' ";
//        $sql = "select o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " where o.shopId='" . $shopId . "' ";
//        //以下sql，主要用来统计相关条件下的总金额
//        $sql1 = "select sum(o.realTotalMoney) as total_order_money from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " where o.shopId='" . $shopId . "' ";
//        if ($orderStatus == 8) {
//            $sql = "select o.*,g.goodsId,g.goodsName,g.ShopGoodPreSaleEndTime,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone from " . __PREFIX__orders . " o left join __PREFIX__order_goods og on og.orderId=o.orderId left join __PREFIX__goods g on g.goodsId=og.goodsId left join " . __PREFIX__users . " u on u.userId=o.userId " . " where o.shopId='" . $shopId . "' ";
//            //以下sql，主要用来统计相关条件下的总金额
//            $sql1 = "select sum(o.realTotalMoney) as total_order_money from " . __PREFIX__orders . " o left join __PREFIX__order_goods og on og.orderId=o.orderId left join __PREFIX__goods g on g.goodsId=og.goodsId left join " . __PREFIX__users . " u on u.userId=o.userId " . " where o.shopId='" . $shopId . "' ";
//        }
//        if (!empty(I('areaId1'))) {
//            $sql .= " AND o.areaId1 = " . I('areaId1') . " ";
//            $sql1 .= " AND o.areaId1 = " . I('areaId1') . " ";
//        }
//        if (!empty(I('areaId2'))) {
//            $sql .= " AND o.areaId2 = " . I('areaId2') . " ";
//            $sql1 .= " AND o.areaId2 = " . I('areaId2') . " ";
//        }
//        if (!empty(I('areaId3'))) {
//            $sql .= " AND o.areaId3 = " . I('areaId3') . " ";
//            $sql1 .= " AND o.areaId3 = " . I('areaId3') . " ";
//        }
//        if ($orderStatus != 20) {
//            if ($orderStatus == 5) {
//                $sql .= " AND o.orderStatus in (-3,-4,-5,-6,-7)";
//                $sql1 .= " AND o.orderStatus in (-3,-4,-5,-6,-7)";
//            } elseif ($orderStatus == 7) {
//                $sql .= " AND o.orderStatus in (7,8,10,16,17)";
//                $sql1 .= " AND o.orderStatus in (7,8,10,16,17)";
//            } elseif ($orderStatus == 8) {
//                $sql .= " AND o.orderStatus in (13,14)";
//                $sql1 .= " AND o.orderStatus in (13,14)";
//            } else {
//                $sql .= " AND o.orderStatus = $orderStatus ";
//                $sql1 .= " AND o.orderStatus = $orderStatus ";
//            }
//        }
//
//        if ($orderNo != "") {
//            $sql .= " AND o.orderNo like '%$orderNo%'";
//            $sql1 .= " AND o.orderNo like '%$orderNo%'";
//        }
//        if ($userName != "") {
//            $sql .= " AND u.userName like '%$userName%'";
//            $sql1 .= " AND u.userName like '%$userName%'";
//        }
//        if ($userPhone != "") {
//            $sql .= " AND u.userPhone like '%$userPhone%'";
//            $sql1 .= " AND u.userPhone like '%$userPhone%'";
//        }
//        if ($userAddress != "") {
//            $sql .= " AND o.userAddress like '%$userAddress%'";
//            $sql1 .= " AND o.userAddress like '%$userAddress%'";
//        }
//
//        $startDate = I('startDate');
//        $endDate = I('endDate');
//        if (!empty($startDate)) {
//            $sql .= " AND o.createTime>='" . $startDate . "'";
//            $sql1 .= " AND o.createTime>='" . $startDate . "'";
//        }
//        if (!empty($endDate)) {
//            $sql .= " AND o.createTime<='" . $endDate . "'";
//            $sql1 .= " AND o.createTime<='" . $endDate . "'";
//        }
//        $sql .= " AND o.orderFlag=1";
//        $sql1 .= " AND o.orderFlag=1";
//        if ($orderStatus == 8) {
//            $sql .= " order by ShopGoodPreSaleEndTime asc ";
//        } else {
//            $sql .= " order by o.orderId desc ";
//        }
//        if ($export != 1) {
//            $data = $this->pageQuery($sql, $pcurr);
//        } else {
//            $data['root'] = $this->query($sql);
//        }
//        $total_order_money = $this->query($sql1);
//        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
//        $data['total_order_money'] = $total_order_money[0]['total_order_money'];
//        //获取取消/拒收原因
//        $orderIds = array();
//        $noReadrderIds = array();
//        $config = $GLOBALS['CONFIG'];
//        foreach ($data['root'] as $key => $v) {
//            $nowtime = time();
//            $shopGoodPreSaleEndTimeInt = strtotime($v['ShopGoodPreSaleEndTime']);
//            $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 0; //未过期
//            if ($nowtime >= $shopGoodPreSaleEndTimeInt) {
//                $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 1; //已过期
//                $data['root'][$key]['shopGoodPreSaleDelivery'] = 0;
//            } else {
//                $data['root'][$key]['shopGoodPreSaleDelivery'] = timeDiff($shopGoodPreSaleEndTimeInt, $nowtime);
//            }
//            $data['root'][$key]['weightG'] = 0;
//            $orderGoods = M('order_goods')->where(['orderId' => $v['orderId']])->select();
//            $data['root'][$key]['goodslist'] = $orderGoods;
//            foreach ($orderGoods as $gv) {
//                $goodsId[] = $gv['goodsId'];
//            }
//            $goodsWhere['goodsId'] = ['IN', $goodsId];
//            $goods = M('goods')->where($goodsWhere)->field('SuppPriceDiff')->select();
//            foreach ($goods as $ggv) {
//                if ($ggv['SuppPriceDiff'] == 1) {
//                    $data['root'][$key]['weightG'] = 1;
//                }
//            }
//            if ($v['orderStatus'] == -6) $noReadrderIds[] = $v['orderId'];
//            $sql = "select logContent from __PREFIX__log_orders where orderId =" . $v['orderId'] . " and logType=0 and logUserId=" . $v['userId'] . " order by logId desc limit 1";
//            $ors = $this->query($sql);
//            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
//            if ($config['setDeliveryMoney'] == 2) {
//                if ($data['root'][$key]['isPay'] == 1) {
//                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
//                } else {
//                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
//                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
//                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
//                    }
//                }
//            }
//        }
//
//        //要对用户取消【-6】的状态进行处理,表示这一条取消信息商家已经知道了
//        // if($orderStatus==5 && count($noReadrderIds)>0){
//        // 	$sql = "UPDATE __PREFIX__orders set orderStatus=-7 WHERE shopId = $shopId AND orderId in (".implode(',',$noReadrderIds).")AND orderStatus = -6 ";
//        // 	$this->execute($sql);
//        // }//2019 手动读取
//        if ($export == 1) {
//            $this->exportOrderList($data['root'], I());
//        }
//        return $data;
//    }

    /**
     * 获取商家订单列表
     * @param array $params <p>
     *  string orderNo 订单号
     *  string userName 收货人姓名
     *  string receivePeple 收货人姓名/手机号码
     *  string userPhone 收货人手机号
     *  string buyer_userName 买家姓名
     *  string buyer_userPhone 买家手机号
     *  int onStart 自提状态(0:未提货 1：已提货)
     *  int isSelf 自提订单【0：否|1：是】
     *  string source 自提码
     *  int payFrom 支付来源[1:支付宝，2：微信,3:余额,4:货到付款]
     *  int orderType 订单类型或订单来源(1.普通订单|2.拼团订单|3.预售订单|5.非常规订单)
     *  int deliverType 配送方式[0:商城配送 | 1:门店配送 | 2：达达配送 | 3.蜂鸟配送 | 4:快跑者 | 5:自建跑腿 | 6:自建司机 |22:自提]
     *  int areaId1 省id
     *  int areaId2 市id
     *  int areaId3 区id
     *  datetime startDate 下单时间-开始时间
     *  datetime endDate 下单时间-结束时间
     *  int statusMark 订单状态【all:全部|toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】
     *  int export 导出【0：否|1：是】
     *  int page 页码
     *  int pageSize 分页条数
     * </p>
     */
    public function queryShopOrders(array $params)
    {
        $shopId = $params["shopId"];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $export = (int)$params['export'];
        $sort_field = 'o.' . $params['sort_field'];
        $sort_value = $params['sort_value'];
        $where = " o.shopId={$shopId} and o.orderFlag=1 ";
        $whereFind = [];
        $whereFind['o.orderNo'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['o.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['o.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        $whereFind['self.source'] = function () use ($params) {
            if (empty($params['source'])) {
                return null;
            }
            return ['like', "%{$params['source']}%", 'and'];
        };
        $whereFind['self.onStart'] = function () use ($params) {
            if (!is_numeric($params['onStart']) || !in_array($params['onStart'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['onStart']}", 'and'];
        };
        $whereFind['o.payFrom'] = function () use ($params) {
            if (empty($params['payFrom'])) {
                return null;
            }
            return ['=', "{$params['payFrom']}", 'and'];
        };
        $whereFind['o.orderType'] = function () use ($params) {
            if (!is_numeric($params['orderType']) || !in_array($params['orderType'], [1, 2, 3, 5])) {
                return null;
            }
            return ['=', "{$params['orderType']}", 'and'];
        };
        $whereFind['o.deliverType'] = function () use ($params) {
            if (!is_numeric($params['deliverType']) || !in_array($params['deliverType'], [0, 1, 2, 3, 4, 5, 6])) {
                return null;
            }
            return ['=', "{$params['deliverType']}", 'and'];
        };
        $whereFind['o.isSelf'] = function () use ($params) {
            if (!is_numeric($params['isSelf']) || !in_array($params['isSelf'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['isSelf']}", 'and'];
        };
        if (is_numeric($params['deliverType']) && in_array($params['deliverType'], [22])) {
            unset($whereFind['o.isSelf']);
            $whereFind['o.isSelf'] = function () use ($params) {
                return ['=', "1", 'and'];
            };
        }
        $whereFind['o.areaId1'] = function () use ($params) {
            if (empty($params['areaId1'])) {
                return null;
            }
            return ['=', "{$params['areaId1']}", 'and'];
        };
        $whereFind['o.areaId2'] = function () use ($params) {
            if (empty($params['areaId2'])) {
                return null;
            }
            return ['=', "{$params['areaId2']}", 'and'];
        };
        $whereFind['o.areaId3'] = function () use ($params) {
            if (empty($params['areaId3'])) {
                return null;
            }
            return ['=', "{$params['areaId3']}", 'and'];
        };
        $whereFind['o.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            $params['endDate'] = date("Y-m-d", strtotime($params['endDate'])) . " 23:59:59";
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['u.userName'] = function () use ($params) {
            if (empty($params['buyer_userName'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userName']}%", 'and'];
        };
        $whereFind['u.userPhone'] = function () use ($params) {
            if (empty($params['buyer_userPhone'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userPhone']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        if (!empty($params['receivePeple'])) {
            $whereInfo .= " and (o.userName like '%{$params['receivePeple']}%' or o.userPhone like '%{$params['receivePeple']}%')";
        }
        $statusMark = [];
        $statusMark['statusMark'] = null;
        parm_filter($statusMark, $params);
        $field = 'o.orderId,o.orderNo,o.shopId,o.orderStatus,o.totalMoney,o.deliverMoney,o.payType,o.payFrom,o.isSelf,o.isPay,o.deliverType,o.userId,o.userName,o.userAddress,o.userPhone,o.orderRemarks,o.needPay,o.realTotalMoney,o.useScore,o.orderType,o.createTime,o.userTel,o.areaId1,o.areaId2,o.areaId3,o.pay_time,o.requireTime,o.setDeliveryMoney,o.printNum';
        $field .= ',u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,self.source,self.onStart';
        $sql = "select {$field} from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " left join __PREFIX__user_self_goods self on self.orderId=o.orderId " . " where {$whereInfo} ";
        //以下sql，主要用来统计相关条件下的总金额
        $sql1 = "select sum(o.realTotalMoney) as total_order_money from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " left join __PREFIX__user_self_goods self on self.orderId=o.orderId " . " where {$whereInfo} ";
        if (!empty($statusMark['statusMark'])) {
            $statusMark = $statusMark['statusMark'];
            switch ($statusMark) {
                case 'toBePaid'://待付款
                    $sql .= " AND o.orderStatus = -2 ";
                    $sql1 .= " AND o.orderStatus = -2 ";
                    break;
                case 'toBeAccepted'://待接单
                    $sql .= " AND o.orderStatus = 0 ";
                    $sql1 .= " AND o.orderStatus = 0 ";
                    break;
                case 'toBeDelivered'://待发货
                    $sql .= " AND o.orderStatus IN(1,2) ";
                    $sql1 .= " AND o.orderStatus IN(1,2) ";
                    break;
                case 'toBeReceived'://待收货
                    $sql .= " AND o.orderStatus IN(3) and isSelf=0 ";
                    $sql1 .= " AND o.orderStatus IN(3) and isSelf=0 ";
                    break;
                case 'confirmReceipt'://已完成
                    $sql .= " AND o.orderStatus = 4 ";
                    $sql1 .= "AND o.orderStatus = 4 ";
                    break;
                case 'toBePickedUp'://待取货,自提订单,商家发货后
                    $sql .= " AND (o.orderStatus in(3,16) and o.isSelf=1) ";
                    $sql1 .= "AND (o.orderStatus in(3,16) and o.isSelf=1) ";
                    break;
                case 'takeOutDelivery'://外卖配送
                    $sql .= " AND o.orderStatus IN(7,8,9,10,11,16,17) and o.isSelf!=1 ";
//                    $sql .= " AND o.orderStatus IN(-6,7,8,9,10,11,16,17) and o.isSelf!=1 ";
//                    $sql1 .= " AND o.orderStatus IN(-6,7,8,9,10,11,16,17) and o.isSelf!=1 ";
                    $sql1 .= " AND o.orderStatus IN(7,8,9,10,11,16,17) and o.isSelf!=1 ";
                    break;
                case 'invalid'://无效订单(用户取消或商家拒收)
                    $sql .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    $sql1 .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    break;
            }
        }
        //排序 主要针对待接单
        $sql .= " group by o.orderId order by {$sort_field} {$sort_value} ";
        if ($export != 1) {
            $data = $this->pageQuery($sql, $page, $pageSize);
        } else {
            $data['root'] = $this->query($sql);
        }
        $total_order_money = $this->query($sql1);
        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
        $data['total_order_money'] = $total_order_money[0]['total_order_money'];
        //获取取消/拒收原因
        $noReadrderIds = array();
        $config = $GLOBALS['CONFIG'];

        $shop_config_tab = M('shop_configs');
        $sorting_tab = M('sorting');
        $sorting_packaging_tab = M('sorting_packaging');
        $array = [];
        foreach ($data['root'] as $key => $v) {
            if ($v['isSelf'] == 1) {
                if ($v['orderStatus'] == 16 && $v['deliverType'] == 6) {
                    $data['root'][$key]['orderStatus'] = 3;//待收货,这里临时修改下,就不让前端改了
                }
                $data['root'][$key]['deliverType'] = 22;
            }
            $data['root'][$key]['deliverType'] = (string)$data['root'][$key]['deliverType'];//避免前端因为类型报错
            $data['root'][$key]['source'] = (string)$v['source']; //自提码
            $data['root'][$key]['onStart'] = (int)$v['onStart']; //自提状态(0:未提货 1：已提货)
            $nowtime = time();
            $shopGoodPreSaleEndTimeInt = strtotime($v['ShopGoodPreSaleEndTime']);
            $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 0; //未过期
            if ($nowtime >= $shopGoodPreSaleEndTimeInt) {
                $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 1; //已过期
                $data['root'][$key]['shopGoodPreSaleDelivery'] = 0;
            } else {
                $data['root'][$key]['shopGoodPreSaleDelivery'] = timeDiff($shopGoodPreSaleEndTimeInt, $nowtime);
            }
            $data['root'][$key]['weightG'] = 0;
            $orderGoods = M('order_goods')->where(['orderId' => $v['orderId']])->select();
            foreach ($orderGoods as $gk => $gv) {
                $orderGoods[$gk]['goodsNums'] = (float)$gv['goodsNums'];
                $goodsId[] = $gv['goodsId'];
            }
            $data['root'][$key]['goodslist'] = $orderGoods;
            $goodsWhere['goodsId'] = ['IN', $goodsId];
            $goods = M('goods')->where($goodsWhere)->field('SuppPriceDiff')->select();
            foreach ($goods as $ggv) {
                if ($ggv['SuppPriceDiff'] == 1) {
                    $data['root'][$key]['weightG'] = 1;
                }
            }
            if ($v['orderStatus'] == -6) $noReadrderIds[] = $v['orderId'];
            $sql = "select logContent from __PREFIX__log_orders where orderId =" . $v['orderId'] . " and logType=0 and logUserId=" . $v['userId'] . " order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data['root'][$key]['isPay'] == 1) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                } else {
                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            } else {//常规非常规拆单后运费修复
                if ($data['root'][$key]['isPay'] != 1 && $data['root'][$key]['setDeliveryMoney'] > 0) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                    $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                }
            }
            //增加可发货状态-start
            $data['root'][$key]['can_delivery_state'] = -1;//可发货状态(0:不可发货|1:可发货)
            if (empty($params['statusMark']) || $params['statusMark'] == 'toBeDelivered') {
                if ($v['orderStatus'] == 2) {
                    $data['root'][$key]['can_delivery_state'] = 1;
                }
                $shop_config_info = $shop_config_tab->where(
                    array(
                        'shopId' => $v['shopId']
                    ))->field('isSorting')->find();//门店配置【是否开启分拣】1：是 -1：否
                $sort_order_info = $sorting_tab->where(array(
                    'orderId' => $v['orderId'],
                    'sortingFlag' => 1
                ))->select();//分拣信息
                $sort_pack_info = $sorting_packaging_tab->where(array(
                    'orderId' => $v['orderId']
                ))->find();//打包信息
                //存在分拣任务
                if (!empty($sort_order_info)) {
                    //packType:1:待打包|2:已打包
                    if ((int)$sort_pack_info['packType'] != 2) {
                        $data['root'][$key]['can_delivery_state'] = 0;
                    }
                }
                //开启分拣
                if ($shop_config_info['isSorting'] == 1) {
                    if (!empty($sort_order_info) && (int)$sort_pack_info['packType'] != 2) {
                        $data['root'][$key]['can_delivery_state'] = 0;
                    }
                }
                //增加可发货状态-end
            }
            //判断未达到受理时间的订单
//            if ($params['statusMark'] == "toBeAccepted") {
//                //防止格式异常导致计算错误
//                $dateTime = date('Y-m-d H:i:s', strtotime($v['requireTime']));
//                $unconventionality = 1 * 60 * 60;//订单配送时长
//                $requireTime = date("Y-m-d H:i:s", strtotime($dateTime) - $unconventionality);
//                if (strtotime($requireTime) <= time()) {
//                    $array[] = $data['root'][$key];
//                }
//            }
        }
        //判断未达到受理时间的订单
//        if ($params['statusMark'] == 'toBeAccepted') {
//            $data['root'] = $array;
//        }
        if ($params['statusMark'] == 'toBeDelivered' && !empty($data['root'])) {
            $list = $data['root'];
            $sort_arr = array();
            foreach ($list as $key => $value) {
                $sort_arr[] = $value['can_delivery_state'];
            }
            array_multisort($sort_arr, SORT_DESC, $list);
            $data['root'] = $list;
        }
        if ($export == 1) {
            $this->exportOrderList($data['root'], I());
        }
        return $data;
    }

    /**
     * @param $res
     * @return array
     * 获取导出采购列表
     */
    public function getExportPurchaseList($res)
    {
        $goodsModel = M('goods g');
        $field = "wgc.catName as catName1,gc.catName as catName2,c.catName as catName3,g.goodsCatId3";
        foreach ($res as $k => $v) {
            $goodsDetail = $goodsModel
                ->join('left join wst_goods_cats wgc on wgc.catId = g.goodsCatId1')
                ->join('left join wst_goods_cats gc on gc.catId = g.goodsCatId2')
                ->join('left join wst_goods_cats c on c.catId = g.goodsCatId3')
                ->where(['goodsId' => $v['goodsId']])
                ->field($field)
                ->find();
            $res[$k]['catName1'] = $goodsDetail['catName1'];
            $res[$k]['catName2'] = $goodsDetail['catName2'];
            $res[$k]['catName3'] = $goodsDetail['catName3'];
            $res[$k]['goodsCatId3'] = $goodsDetail['goodsCatId3'];
        }
        $getPurchaseList = [];
        foreach ($res as $k => $v) {
            $getPurchaseList[$v['goodsCatId3']][] = $v;
        }
        $getPurchaseList = array_values($getPurchaseList);
        $catList = [];
        foreach ($getPurchaseList as $key => $value) {
            $catInfo = [];
            $catInfo['catName1'] = $value[0]['catName1'];
            $catInfo['catName2'] = $value[0]['catName2'];
            $catInfo['catName3'] = $value[0]['catName3'];
            $catInfo['goodsCatId3'] = $value[0]['goodsCatId3'];
            $catInfo['goodsList'] = [];
            $catList[] = $catInfo;
        }
        foreach ($catList as &$item) {
            foreach ($getPurchaseList as $value) {
                foreach ($value as $vv) {
                    if ($item['goodsCatId3'] == $vv['goodsCatId3']) {
                        $item['goodsList'][] = $vv;
                    }
                }
            }
        }
        unset($item);
        return $catList;
    }

    /**
     * 导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportPurchaseClassList(array $orderList, array $params)
    {
        //拼接表格信息
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        } else {
            $nowDate = date('Y-m-d H:i:s');
            $date = $nowDate . ' - ' . $nowDate;
        }

        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:150px;'>分类</th>
                <th style='width:150px;'>商品名称</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:400px;'>备注</th>
            </tr>";
        $num = 0;
        $rowspan = 0;//下面的导出表格会用到
        foreach ($orderList as $key => $value) {
            foreach ($value as $v) {
                if (empty($v['skuId'])) {
                    $rowspan += count($v['sku']);
                    //无sku
                } else {
                    //有sku
                    $rowspan += 1;
                }
            }
            $orderList[$key]['rowspan'] = $rowspan;
        }
        foreach ($orderList as $okey => $ovalue) {
            $rowspan = $ovalue['rowspan'];
            $orderSku = $ovalue['goodsList'];
            $key = $okey + 1;
            $goodsSpan = count($ovalue['goodsList']);
            //打个补丁 start
            $rowspan = count($ovalue['goodsList']);
            foreach ($orderSku as $gVal) {
                if (!empty($gVal['sku'])) {
                    $rowspan += count($gVal['sku']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderSku as $gkey => $gVal) {
                $skuCount = $gVal['sku'];
                $countOrderGoods = count($orderSku);
                if ($countOrderGoods > $rowspan) {
                    $rowspan = $countOrderGoods;
                }
                $num++;
                $specName = '无';
                $goodsRowspan = 1;
                $goodsSkuCount = 1;
                if (!empty($gVal['sku'])) {
                    $goodsRowspan = count($gVal['sku']);
                    $goodsSkuCount = $goodsRowspan;
                }
                $goodsRowspan = $rowspan;
                if (!empty($gVal['catName3'])) {
                    $catName = "{$gVal['catName1']}/{$gVal['catName2']}/{$gVal['catName3']}";
                } else {
                    $catName = "";
                }
                if ($gkey == 0) {
                    if (empty($skuCount)) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$goodsRowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$goodsRowspan}'>" . $catName . "</td>" .//分类
                            "<td style='width:80px;' >" . $gVal['goodsInfo']['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsInfo']['buyPirce'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            "<td style='width:100px;'>" . $gVal['remark'] . "</td>" .//备注
                            "</tr>";
                    } else {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$goodsRowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$goodsRowspan}'>" . $catName . "</td>" .//分类
                            "<td style='width:80px;' rowspan='{$goodsSpan}'>" . $gVal['goodsInfo']['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsInfo']['buyPirce'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $gVal['sku'][0]['skuSpecAttrInfo'] . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['sku'][0]['totalNum'] . "</td>" .//商品数量
                            "<td style='width:100px;' >" . $gVal['remark'] . "</td>" .//备注rowspan='{$goodsSkuCount}'
                            "</tr>";
                    }
                    if (!empty($skuCount)) {
                        foreach ($skuCount as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['buyPirce'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['skuSpecAttrInfo'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $skuVal['totalNum'] . "</td>" .//商品数量
                                    "<td style='width:100px;' >" . $gVal['remark'] . "</td>" .//备注rowspan='{$goodsSkuCount}'
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    if (empty($skuCount)) {
                        $body .=
                            "<tr align='center'>" .
//                            "<td style='width:80px;' rowspan='{$goodsSkuCount}'>4444444" . $gVal['goodsInfo']['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsInfo']['buyPirce'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            "<td style='width:100px;'>" . $gVal['remark'] . "</td>" .//备注rowspan='{$goodsSkuCount}'
                            "</tr>";
                    } else {
                        $goodsRowspan = count($skuCount);//$goodsSkuCount
                        foreach ($skuCount as $skuKey => $skuVal) {
//                            $goodsName = "<td style='width:80px;' rowspan='{$goodsSkuCount}'>555555" . $value['goodsInfo']['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" . $goodsName .
                                "<td style='width:50px;' >" . $skuVal['buyPirce'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['skuSpecAttrInfo'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $skuVal['totalNum'] . "</td>" .//商品数量
                                "<td style='width:100px;' >" . $skuVal['remarks'] . "</td>" .//备注rowspan='{$goodsSkuCount}'
                                "</tr>";
                        }
                    }
                }
            }
        }
        $headTitle = "非常规订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 采购按品类导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrderClassList(array $orderList, array $params)
    {
        //拼接表格信息
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        } else {
            $nowDate = date('Y-m-d H:i:s');
            $date = $nowDate . ' - ' . $nowDate;
        }

        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:400px;'>备注</th>
            </tr>";
        $num = 0;
        $rowspan = 0;//下面的导出表格会用到
        foreach ($orderList as $key => $value) {
            if (empty($value['skuId'])) {
                $rowspan += 1;
                //无sku
            } else {
                //有sku
                $rowspan += 1;
            }
            $orderList[$key]['rowspan'] = $rowspan;
        }
        foreach ($orderList as $key => $value) {
            $num++;
            $orderListCount = count($orderList);
            $rowspan = $value['rowspan'];
            $skuCount = $value['sku'];
            $keyCount = $key + 1;
            //打个补丁 start
            $rowspan = count($skuCount);
            foreach ($skuCount as $gVal) {
                $rowspan += count($gVal) - 1;
            }
            unset($gVal);
            if ($orderListCount > $rowspan) {
                $rowspan = $orderListCount;
            }
            $goodsRowspan = 1;
            if (!empty($skuCount)) {
                $goodsRowspan = count($skuCount);
            }
            $specName = "";
            if ($key == 0) {
                if (empty($skuCount)) {
                    $body .=
                        "<tr align='center'>" .
                        "<td style='width:40px;' rowspan='{$goodsRowspan}'>" . $keyCount . "</td>" .//序号
                        "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $value['goodsInfo']['goodsName'] . "</td>" .//商品名称
                        "<td style='width:50px;' >" . $value['goodsInfo']['buyPirce'] . "</td>" .//商品价格
                        "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                        "<td style='width:50px;' >" . $value['totalNum'] . "</td>" .//商品数量
                        "<td style='width:400px;' rowspan='{$goodsRowspan}'>" . $value['remark'] . "</td>" .//备注
                        "</tr>";
                } else {
                    $body .=
                        "<tr align='center'>" .
                        "<td style='width:40px;' rowspan='{$goodsRowspan}'>" . $keyCount . "</td>" .//序号
                        "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $value['goodsInfo']['goodsName'] . "</td>" .//商品名称
                        "<td style='width:50px;' >" . $value['sku'][0]['buyPirce'] . "</td>" .//商品价格
                        "<td style='width:100px;' >" . $value['sku'][0]['skuSpecAttrInfo'] . "</td>" .//商品规格
                        "<td style='width:50px;' >" . $value['sku'][0]['totalNum'] . "</td>" .//商品数量
                        "<td style='width:400px;' rowspan='{$goodsRowspan}'>" . $value['sku'][0]['remark'] . "</td>" .//备注
                        "</tr>";
                }
                if (!empty($skuCount)) {
                    foreach ($skuCount as $skuKey => $skuVal) {
                        if ($skuKey != 0) {
                            $body .=
                                "<tr>" .
                                "<td style='width:50px;' >" . $skuVal['buyPirce'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['skuSpecAttrInfo'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $skuVal['totalNum'] . "</td>" .//商品数量
                                "</tr>";
                        }
                    }
                }
            } else {
                if (empty($skuCount)) {
                    $body .=
                        "<tr align='center'>" .
                        "<td style='width:40px;' rowspan='{$goodsRowspan}'>" . $keyCount . "</td>" .//序号
                        "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $value['goodsInfo']['goodsName'] . "</td>" .//商品名称
                        "<td style='width:50px;' >" . $value['goodsInfo']['buyPirce'] . "</td>" .//商品价格
                        "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                        "<td style='width:50px;' >" . $value['totalNum'] . "</td>" .//商品数量
                        "<td style='width:400px;' rowspan='{$goodsRowspan}'>" . $value['remark'] . "</td>" .//备注
                        "</tr>";
                } else {
                    $goodsRowspan = count($skuCount);
                    foreach ($skuCount as $skuKey => $skuVal) {
                        $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $value['goodsInfo']['goodsName'] . "</td>";//商品名称;
                        if ($skuKey != 0) {
                            $goodsName = '';
                        }
                        $body .=
                            "<tr>" .
                            $goodsName .
                            "<td style='width:50px;' >" . $skuVal['buyPirce'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $skuVal['skuSpecAttrInfo'] . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $skuVal['totalNum'] . "</td>" .//商品数量
                            "</tr>";
                    }
                }
            }
        }
        $headTitle = "非常规订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * @param array $params
     * @return array
     * 获取商家非常规订单列表
     */
    public function getShopOrders(array $params)
    {
        $shopId = $params["shopId"];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $export = (int)$params['export'];
        $where = "shopId = {$shopId}";
        $sql = "select * from __PREFIX__purchase_goods where {$where}";
        $data = $this->pageQuery($sql, $page, $pageSize);
        foreach ($data['root'] as $key => &$item) {
            $item['id'] = $key + 1;
        }
        unset($item);
        if ($export == 1) {
            $param = [];
            $shopId = $params['shopId'];
            $res = $this->getExportPurchaseOrderList($shopId);
            $getPurchaseList = $this->getExportPurchaseList($res);
            $this->exportPurchaseClassList($getPurchaseList, $param);
        }
        return $data;
    }

    /**
     * @param array $params
     * @return mixed
     * 删除由非常规订单生成的采购商品
     */
    public function delPurchaseGoods(array $params)
    {
        $purchaseGoodsModel = M('purchase_goods');
        $where = [];
        $where['shopId'] = $params["shopId"];
        $where['goodsId'] = $params["goodsId"];
        $where['skuId'] = $params["skuId"];

        $orderInfo = $purchaseGoodsModel->where($where)->delete();
        if ($orderInfo == 0) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

//    /**
//     * @param $params
//     * @return array
//     * 生成采购单-------由订单生成
//     */
//    public function getPurchaseList($params)
//    {
//        $where = [];
//        $where['orderStatus'] = ["IN", "1,2"];
//        $where['orderFlag'] = 1;
//        $where['shopId'] = $params["shopId"];
//        $where['orderType'] = 5;
//        if (!empty($params['startDate']) && !empty($params['endDate'])) {
//            $where['createTime'] = ['between', "{$params['startDate']},{$params['endDate']}"];
//        }
//
//        $orderModel = M('orders');
//        $goodsModel = M('goods');
//        $orderGoodsModel = M('order_goods');
//        $skuGoodsModel = M('sku_goods_system');
//        $skuGoodsSelfModel = M('sku_goods_self sgs');
//
//        $orderList = $orderModel->where($where)->select();
//
//        $goodsList = [];
//        $orderIds = [];
//        foreach ($orderList as $key => $value) {
//            $orderIds[] = $value['orderId'];
//            $orderGoodsList = $orderGoodsModel->where(['orderId' => $value['orderId']])->field('goodsId,goodsName,goodsThums,skuId,goodsNums,goodsPrice,remarks,skuSpecAttr')->select();
//            foreach ($orderGoodsList as $k => $v) {
//                $goodsInfo = [];
//                $goodsDate = $goodsModel->where(['goodsId' => $v['goodsId']])->find();
//                $goodsInfo['goodsName'] = $v['goodsName'];
//                $goodsInfo['goodsImg'] = $v['goodsThums'];
//                $goodsInfo['buyPirce'] = (string)$goodsDate['shopPrice'];
//                $skuInfo = $skuGoodsModel->where(['skuId' => $v['skuId'], 'goodsId' => $v['goodsId']])->field('skuId')->find();
//                if (!empty($skuInfo)) {
//                    $skuInfo['totalNum'] = (int)$v['goodsNums'];
//                    $skuInfo['remarks'] = $v['remarks'];
//                    $skuInfo['skuSpecAttrInfo'] = $v['skuSpecAttr'];
//                    $skuInfo['buyPirce'] = $goodsDate['shopPrice'];
//                    $where = [];
//                    $where['sgs.skuId'] = $skuInfo['skuId'];
//                    $skuInfo['skuSpecAttr'] = $skuGoodsSelfModel
//                        ->join('left join wst_sku_spec wss on wss.specId = sgs.specId')
//                        ->join('left join wst_sku_spec_attr wsa on wsa.attrId = sgs.attrId')
//                        ->where($where)
//                        ->field('wss.specId,wss.specName,wsa.attrId,wsa.attrName,sgs.skuId')
//                        ->select();
//                }
//                $v['goodsPrice'] = $goodsDate['shopPrice'];
//                $v['sku'] = $skuInfo;
//                $v['goodsInfo'] = $goodsInfo;
//                $goodsList[] = $v;
//            }
//        }
//        //将所有相同商品进行合并
//        $item = [];
//        $itemSku = [];
//        foreach ($goodsList as $k => $v) {
//            if (!isset($item[$v['goodsId']])) {
//                $item[$v['goodsId']]['goodsId'] = $v['goodsId'];
//                if (!empty($params['startDate'])) {
//                    $item[$v['goodsId']]['remark'] = "在({$params['startDate']})——({$params['endDate']})时间区间内的订单,生成采购单";
//                } else {
//                    $item[$v['goodsId']]['remark'] = "";
//                }
//            } else {
//                $item[$v['goodsId']]['totalNum'] = 0;
//            }
//            if (!empty($v['skuId'])) {
//                if (!isset($itemSku[$v['skuId']])) {
//                    $itemSku[$v['skuId']] = $v['sku'];
//                } else {
//                    $itemSku[$v['skuId']]['skuId'] = $v['sku']['skuId'];
//                    $itemSku[$v['skuId']]['remarks'] = $v['sku']['remarks'];
//                    $itemSku[$v['skuId']]['skuSpecAttrInfo'] = $v['sku']['skuSpecAttrInfo'];
//                    $itemSku[$v['skuId']]['buyPirce'] = $v['goodsPrice'];
//                    $itemSku[$v['skuId']]['totalNum'] += $v['goodsNums'];
//                    $itemSku[$v['skuId']]['skuSpecAttr'] = $v['sku']['skuSpecAttr'];
//                }
//            }
//            //用于判断是否多余的数据【0:不是|1:是】
//            $item[$v['goodsId']]['type'] = 0;
//            if ($v['skuId'] == 0) {
//                $item[$v['goodsId']]['type'] = 1;
//                $item[$v['goodsId']]['totalNum'] += $v['goodsNums'];
//            }
//            $item[$v['goodsId']]['sku'] = array_values($itemSku);
//            $item[$v['goodsId']]['goodsInfo'] = $v['goodsInfo'];
//        }
//        $item = array_values($item);
//        //用于多余数据去除
//        foreach ($item as $k => $v) {
//            if ($item[$k]['type'] == 1) {
//                $item[$k]['sku'] = [];
//            }
//        }
//        $res = [];
//        $res['goodsList'] = $item;
//        $res['orderIds'] = implode(',',$orderIds);
//        return (array)$res;
//    }
    /**
     * @param $shopId
     * @return array
     * 导出
     * 生成采购单-------由商品采购表生成
     */
    public function getExportPurchaseOrderList($shopId)
    {
        $skuGoodsModel = M('sku_goods_system');
        $purchaseGoodsModel = M('purchase_goods');
        $skuGoodsSelfModel = M('sku_goods_self sgs');

        $where = [];
        $where['shopId'] = $shopId;
        $purchaseGoodsList = $purchaseGoodsModel->where($where)->select();
        $goodsIds = [];
        foreach ($purchaseGoodsList as $v) {
            $goods['goodsId'] = $v['goodsId'];
            $goods['skuId'] = $v['skuId'];
            $goodsIds[] = $goods;
        }
//        $goodsIds = array_unique($goodsIds);
        $res = [];
        foreach ($goodsIds as $key => $value) {
            //重新排序value
            ksort($value);
            //获取key ，判断是否存在的依据
            $key = implode("_", $value);   //name1_10
            //md5 为了防止字段内容过长特殊字符等
            $res[md5($key)] = $value;
        }
//重置索引
        $goodsIds = array_values($res);
        $rest = [];
        foreach ($goodsIds as $value) {
            $goodsList = [];
            $sku = [];
            $remark = "";
            foreach ($purchaseGoodsList as $v) {
                if ($value['goodsId'] == $v['goodsId'] && $value['skuId'] == $v['skuId']) {
                    $goodsName = $v['goodsName'];
                    $goodsImg = $v['goodsThums'];
                    $buyPirce = $v['goodsPrice'];
                    $totalNum = $v['goodsNums'];
                    $remark = (string)$v['remarks'];
                    $skuInfo = $skuGoodsModel->where(['skuId' => $v['skuId'], 'goodsId' => $v['goodsId'], 'dataFlag' => 1])->field('skuId')->find();
                    if (!empty($skuInfo)) {
                        $totalNum = 0;
                        $skuInfo['skuId'] = $v['skuId'];
                        $skuInfo['totalNum'] = (int)$v['goodsNums'];
                        $skuInfo['remarks'] = $v['remarks'];
                        $skuInfo['skuSpecAttrInfo'] = $v['skuSpecAttr'];
                        $skuInfo['buyPirce'] = $v['goodsPrice'];
                        $where = [];
                        $where['sgs.skuId'] = $skuInfo['skuId'];
                        $where['sgs.dataFlag'] = 1;
                        $skuInfo['skuSpecAttr'] = $skuGoodsSelfModel
                            ->join('left join wst_sku_spec wss on wss.specId = sgs.specId')
                            ->join('left join wst_sku_spec_attr wsa on wsa.attrId = sgs.attrId')
                            ->where($where)
                            ->field('wss.specId,wss.specName,wsa.attrId,wsa.attrName,sgs.skuId')
                            ->select();
                        $sku[] = $skuInfo;
                    }
                }
            }
            $goodsInfo = [];
            $goodsInfo['goodsName'] = $goodsName;
            $goodsInfo['goodsImg'] = $goodsImg;
            $goodsInfo['buyPirce'] = $buyPirce;
            $goodsList['isOrder'] = 1;//用于判断是否是由订单生成的采购单
            $goodsList['goodsId'] = $value['goodsId'];
            $goodsList['totalNum'] = $totalNum;
            $goodsList['remark'] = $remark;
            $goodsList['sku'] = $sku;
            $goodsList['goodsInfo'] = $goodsInfo;
            $rest[] = $goodsList;
        }
        return (array)$rest;
    }

    /**
     * @param $shopId
     * @return array
     * 生成采购单-------由商品采购表生成
     */
    public function getPurchaseList($shopId)
    {
        $skuGoodsModel = M('sku_goods_system');
        $jxcSkuGoodsModel = M('jxc_sku_goods_system');
        $purchaseGoodsModel = M('purchase_goods');
//        $skuGoodsSelfModel = M('sku_goods_self sgs');
        $jxcSkuGoodsSelfModel = M('jxc_sku_goods_self jsgs');
        $goodsModel = M('goods');
        $jxcGoodsModel = M('jxc_goods');

        $where = [];
        $where['shopId'] = $shopId;
        $purchaseGoodsList = $purchaseGoodsModel->where($where)->select();
        $goodsIds = [];
        foreach ($purchaseGoodsList as $v) {
            $goodsIds[] = $v['goodsId'];
        }
        $goodsIds = array_unique($goodsIds);
        $rest = [];
        foreach ($goodsIds as $value) {
            $goodsList = [];
            $sku = [];
            $remark = "";
            foreach ($purchaseGoodsList as $v) {
                if ($value == $v['goodsId']) {
                    $goodsDate = $goodsModel->where(['goodsId' => $v['goodsId']])->find();
                    $jxcGoodsDate = $jxcGoodsModel->where(['goodsSn' => $goodsDate['goodsSn']])->find();
                    $goodsName = $v['goodsName'];
                    $goodsImg = $v['goodsThums'];
                    $buyPirce = $v['goodsPrice'];
                    $totalNum = $v['goodsNums'];
                    $remark = (string)$v['remark'];
                    $skuInfo = $skuGoodsModel->where(['skuId' => $v['skuId'], 'goodsId' => $v['goodsId'], 'dataFlag' => 1])->field('skuId as wstSkuId,skuBarcode')->find();
                    if (!empty($skuInfo)) {
                        $jxcSkuInfo = $jxcSkuGoodsModel->where(['skuBarcode' => $skuInfo['skuBarcode'], 'dataFlag' => 1])->find();
                        $totalNum = 0;
                        $skuInfo['skuId'] = $jxcSkuInfo['skuId'];
                        $skuInfo['totalNum'] = (int)$v['goodsNums'];
                        $skuInfo['remarks'] = $v['remarks'];
                        $skuInfo['skuSpecAttrInfo'] = $v['skuSpecAttr'];
                        $skuInfo['buyPirce'] = $jxcSkuInfo['sellPrice'];
                        $where = [];
//                        $where['sgs.skuId'] = $skuInfo['skuId'];
//                        $where['sgs.dataFlag'] = 1;
//                        $skuInfo['skuSpecAttr'] = $skuGoodsSelfModel
//                            ->join('left join wst_sku_spec wss on wss.specId = sgs.specId')
//                            ->join('left join wst_sku_spec_attr wsa on wsa.attrId = sgs.attrId')
//                            ->where($where)
//                            ->field('wss.specId,wss.specName,wsa.attrId,wsa.attrName,sgs.skuId')
//                            ->select();
                        //替换成总仓商品数据
                        $where['jsgs.skuId'] = $jxcSkuInfo['skuId'];
                        $where['jsgs.dataFlag'] = 1;
                        $skuInfo['skuSpecAttr'] = $jxcSkuGoodsSelfModel
                            ->join('left join wst_jxc_sku_spec wss on wss.specId = jsgs.specId')
                            ->join('left join wst_jxc_sku_spec_attr wsa on wsa.attrId = jsgs.attrId')
                            ->where($where)
                            ->field('wss.specId,wss.specName,wsa.attrId,wsa.attrName,jsgs.skuId')
                            ->select();
                        $sku[] = $skuInfo;
                    }
                }
            }
            //跳过总仓没有的商品
            if (empty($jxcGoodsDate['goodsId'])) {
                continue;
            }
            $goodsInfo = [];
            $goodsInfo['goodsSn'] = $goodsDate['goodsSn'];
            $goodsInfo['goodsName'] = $goodsName;
            $goodsInfo['goodsImg'] = $goodsImg;
            $goodsInfo['buyPirce'] = $buyPirce;
            $goodsList['isOrder'] = 1;//用于判断是否是由订单生成的采购单
            $goodsList['wstGoodsId'] = $value;
            $goodsList['goodsId'] = $jxcGoodsDate['goodsId'];
            $goodsList['totalNum'] = $totalNum;
            $goodsList['remark'] = $remark;
            $goodsList['sku'] = $sku;
            $goodsList['goodsInfo'] = $goodsInfo;
            $rest[] = $goodsList;
        }
        return (array)$rest;
    }

    /**
     * 统计订单数量
     * @param array $params
     * */
    public function getOrderStatusNum(array $params)
    {
        $shopId = $params['shopId'];
        $where = " o.shopId={$shopId} and o.orderFlag=1 ";
        $whereFind = [];
        $whereFind['o.orderNo'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['o.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['o.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        $whereFind['o.payFrom'] = function () use ($params) {
            if (empty($params['payFrom'])) {
                return null;
            }
            return ['=', "{$params['payFrom']}", 'and'];
        };
        $whereFind['o.deliverType'] = function () use ($params) {
            if (empty($params['deliverType'])) {
                return null;
            }
            return ['=', "{$params['deliverType']}", 'and'];
        };
        $whereFind['o.orderType'] = function () use ($params) {
            if (empty($params['orderType'])) {
                return null;
            }
            return ['=', "{$params['orderType']}", 'and'];
        };
        $whereFind['o.isSelf'] = function () use ($params) {
            if (!is_numeric($params['isSelf']) || !in_array($params['isSelf'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['isSelf']}", 'and'];
        };
        $whereFind['self.onStart'] = function () use ($params) {
            if (!is_numeric($params['onStart']) || !in_array($params['onStart'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['onStart']}", 'and'];
        };
        $whereFind['self.source'] = function () use ($params) {
            if (empty($params['source'])) {
                return null;
            }
            return ['=', "{$params['source']}", 'and'];
        };
        $whereFind['o.areaId1'] = function () use ($params) {
            if (empty($params['areaId1'])) {
                return null;
            }
            return ['=', "{$params['areaId1']}", 'and'];
        };
        $whereFind['o.areaId2'] = function () use ($params) {
            if (empty($params['areaId2'])) {
                return null;
            }
            return ['=', "{$params['areaId2']}", 'and'];
        };
        $whereFind['o.areaId3'] = function () use ($params) {
            if (empty($params['areaId3'])) {
                return null;
            }
            return ['=', "{$params['areaId3']}", 'and'];
        };
        $whereFind['o.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['u.userName'] = function () use ($params) {
            if (empty($params['buyer_userName'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userName']}%", 'and'];
        };
        $whereFind['u.userPhone'] = function () use ($params) {
            if (empty($params['buyer_userPhone'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userPhone']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        //以下sql，主要用来统计相关条件下的总金额
        $orderTab = M('orders o ');
//        $all = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo)->count();//全部
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $all = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus=-2 ';
//        $toBePaid = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//待付款
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $toBePaid = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus=0 ';
//        $toBeAccepted = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//待接单/待受理
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $toBeAccepted = (int)$this->queryRow($total_sql)['counts'];
//        $array = [];
//        date_default_timezone_set("Asia/Shanghai");
//        foreach ($toBeAcceptedList as $v) {
//            //防止格式异常导致计算错误
//            $dateTime = date('Y-m-d H:i:s', strtotime($v['requireTime']));
//            $unconventionality = 1 * 60 * 60;//非常规订单配送时长
//            $requireTime = date("Y-m-d H:i:s", strtotime($dateTime) - $unconventionality);
//            if (strtotime($requireTime) <= time()) {
//                $array[] = $v;
//            }
//        }
//        $toBeAccepted = count($array);
        $andWhere = ' and orderStatus IN(1,2) ';
//        $toBeDelivered = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//待发货
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $toBeDelivered = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus IN(3) and isSelf=0 ';
//        $toBeReceived = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//待收货
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $toBeReceived = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus IN(3,16) and isSelf=1 ';
//        $toBePickedUp = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//待取货 PS:自提订单,商家发货后
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $toBePickedUp = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus=4 ';
//        $confirmReceipt = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//已完成
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $confirmReceipt = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ';
//        $invalid = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//已失效(用户取消或门店拒收)
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $invalid = (int)$this->queryRow($total_sql)['counts'];
        $andWhere = ' and orderStatus IN(7,8,10,16,17) ';
//        $takeOutDelivery = $orderTab
//            ->join('left join wst_users u on u.userId=o.userId')
//            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
//            ->where($whereInfo . $andWhere)->count();//外卖配送
        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $takeOutDelivery = (int)$this->queryRow($total_sql)['counts'];


//        已取消订单总数
        $andWhere = ' and o.orderStatus IN(-7,-6,-1,-8,-2,9,10,11) ';


        $sql = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_user_self_goods self on self.orderId=o.orderId')
            ->field('o.orderId')
            ->where($whereInfo . $andWhere)->group('o.orderId')->buildSql();//全部
        $total_sql = "select count(1) counts from (" . $sql . ") as count ";
        $CancelledCount = (int)$this->queryRow($total_sql)['counts'];
        // var_dump($total_sql);


        $data = [];
        $data['all'] = $all;
        $data['toBePaid'] = $toBePaid;
        $data['toBeAccepted'] = $toBeAccepted;
        $data['toBeDelivered'] = $toBeDelivered;
        $data['toBeReceived'] = $toBeReceived;
        $data['confirmReceipt'] = $confirmReceipt;
        $data['toBePickedUp'] = $toBePickedUp;
        $data['invalid'] = $invalid;
        $data['takeOutDelivery'] = $takeOutDelivery;
        $data['cancelled_count'] = $CancelledCount; //已取消订单总数
        return $data;
    }

    /**
     * 导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrderList(array $orderList, array $params)
    {
        //处理订单商品信息
        foreach ($orderList as $key => $value) {
            $rowspan = 0;//下面的导出表格会用到
            $orderGoods = $value['goodslist'];
            $goodsList = [];
            foreach ($value['goodslist'] as $gval) {
                $goodsInfo = [];
                $goodsInfo['goodsId'] = $gval['goodsId'];
                $goodsInfo['goodsName'] = $gval['goodsName'];
                $goodsInfo['goodsPrice'] = $gval['goodsPrice'];
                $goodsInfo['goodsNums'] = 0;
                $goodsInfo['remarks'] = $gval['remarks'];
                $goodsList[] = $goodsInfo;
            }
            $unquieGoods = arrayUnset($goodsList, 'goodsId');
            foreach ($unquieGoods as $uKey => $uVal) {
                $skulist = [];
                foreach ($orderGoods as $oVal) {
                    if ($oVal['goodsId'] == $uVal['goodsId']) {
                        $rowspan += 1;
                        $unquieGoods[$uKey]['goodsNums'] += $oVal['goodsNums'];
                        if (!empty($oVal['skuId'])) {
                            $skuInfo = [];
                            $skuInfo['skuId'] = $oVal['skuId'];
                            $skuInfo['goodsNums'] = $oVal['goodsNums'];
                            $skuInfo['goodsPrice'] = $oVal['goodsPrice'];
                            $skuInfo['goodsAttrName'] = $oVal['goodsAttrName'];
                            $skuInfo['skuSpecAttr'] = $oVal['skuSpecAttr'];
                            $skuInfo['remarks'] = $oVal['remarks'];
                            $skulist[] = $skuInfo;
                        }
                    }
                }
                $unquieGoods[$uKey]['skulist'] = $skulist;
            }
            $orderList[$key]['goodslist'] = $unquieGoods;
            $orderList[$key]['rowspan'] = count($unquieGoods);
        }

        //拼接表格信息
        $date = '';
        $startDate = '';
        $endDate = '';
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        }
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:100px;'>订单号</th>
                <th style='width:200px;'>收货人信息</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:50px;'>小计</th>
                <th style='width:50px;'>运费</th>
                <th style='width:80px;'>实付金额</th>
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>下单时间</th>
                <th style='width:150px;'>支付方式</th>
            </tr>";
        $num = 0;
        $orderModule = new OrdersModule();
        foreach ($orderList as $okey => $ovalue) {
            $pay_name = $orderModule->getPayName($ovalue['payFrom']);
            $orderGoods = $ovalue['goodslist'];
            $rowspan = $ovalue['rowspan'];
            $key = $okey + 1;
            $userDetailAddress = '';
            $userDetailAddress .= '用户名：' . $ovalue['userName'] . '<br>';
            $userDetailAddress .= '电话：' . $ovalue['userPhone'] . '<br>';
            $userDetailAddress .= '收货地址：' . $ovalue['userAddress'] . '<br>';
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                /*if(!empty($gVal['skulist'])){
                     $rowspan = count($gVal['skulist']);
                 }*/
                $num++;
                $goodsNums = "<td style='width:30px;'>" . $gVal['goodsNums'] . "</td>";//数量;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }

                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $pay_name . "</td>" .//支付方式
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['skuSpecAttr'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['skulist'][0]['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['skulist'][0]['goodsPrice'] * $gVal['skulist'][0]['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $pay_name . "</td>" .//支付方式
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['skuSpecAttr'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $skuVal['goodsNums'] . "</td>" .//商品数量
                                    "<td style='width:50px;'>" . $skuVal['goodsPrice'] * $skuVal['goodsNums'] . "</td>" .//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    /*$headTitle = "订单数据";
                    $filename = $headTitle . ".xls";
                    usePublicExport($body,$headTitle,$filename,$date);*/
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['skuSpecAttr'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $skuVal['goodsNums'] . "</td>" .//商品数量
                                "<td style='width:50px;'>" . $skuVal['goodsPrice'] * $skuVal['goodsNums'] . "</td>" .//小计
                                "</tr>";
                        }
                    }
                }
            }

        }
        $headTitle = "订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }


    /**
     * 商家受理订单-只能受理【未受理】的订单
     * @param array $loginUserInfo 当前登陆者信息
     * @param int $orderId 订单id
     * @param int $deliverType 配送方式【0：商城配送 | 1：门店配送 | 2：达达配送 | 3：蜂鸟配送 | 4：快跑者 | 5：自建跑腿 | 6：自建司机 |22：自提】
     * @return bool $data
     */
    public function shopOrderAccept(array $loginUserInfo, int $orderId, int $deliverType)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');
        $where = [];
        $where['orderId'] = $orderId;
        $where['shopId'] = $shopId;
        $orderInfo = $orderTab->where($where)->lock(true)->find();
        if (!in_array($orderInfo['orderStatus'], array(0, 14))) {
            $from = (int)I('from');
            if ($from == 1) {
                //针对前段轮询请求,不给错误提示只刷新
                return returnData(false, -2, 'error', '非受理订单');
            }
            return returnData(false, -1, 'error', '非待受理订单');
        }
        //受理订单-start
        M()->startTrans();
        $where = [];
        $where['orderId'] = $orderId;
        $saveData = [];
        $saveData['orderStatus'] = 1;
        if (is_numeric($deliverType)) {
            $saveData['deliverType'] = $deliverType;
        }
        //更改配送方式-start
        $updateParams = $saveData;
        $updateParams['receive_deliverType'] = $deliverType;
        $updateDelivetyRes = $this->updateOrderDeliveryType($loginUserInfo, $orderId, $updateParams);
        if ($updateDelivetyRes['code'] != 0) {
            M()->rollback();
            return $updateDelivetyRes;
        }
        $saveData = $updateDelivetyRes['data'];
        $shop_service_module = new ShopsServiceModule();
        $shop_config_result = $shop_service_module->getShopConfig($shopId);
        $shop_config_data = $shop_config_result['data'];
        if ($shop_config_data['open_suspension_chain'] == 1) {
            //如果开启了悬挂链将上报状态改为待上报
            $saveData['is_reporting'] = 0;
        }
        //更改配送方式-end
        $updateRes = $orderTab->where($where)->save($saveData);
        if ($updateRes === false) {
            M()->rollback();
            return returnData(false, -1, 'error', '订单受理失败');
        }
        $logId = [];
        $content = "商家已受理订单";
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 1;
        $logParams['orderStatus'] = 1;
        $logParams['payStatus'] = 1;
        $logId[] = $this->addOrderLog($loginUserInfo, $logParams);
        //受理订单-start
        //打包订单-start
        $produceRes = $this->shopOrderProduce($loginUserInfo, $orderId, M());
        if ($produceRes['code'] != 0) {
            $this->delOrderLog($logId);
            M()->rollback();
            return $produceRes;
        }
        $content = "订单打包中";
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 1;
        $logParams['orderStatus'] = 2;
        $logParams['payStatus'] = 1;
        $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
        //打包订单-end
        //发布订单-start
        $where = [];
        $where['orderId'] = $orderInfo['orderId'];
        $orderInfo = $orderTab->where($where)->find();
        if ($orderInfo['isSelf'] == 1) {
            //自提
//            $where = [];
//            $where['orderId'] = $orderId;
//            $save = [];
//            $save['orderStatus'] = 3;
//            $updateOrderRes = $orderTab->where($where)->save($save);
//            if ($updateOrderRes === false) {
//                $this->delOrderLog($logId);
//                M()->rollback();
//            } else {
//                //写入订单日志
//                $content = "待取货";
//                $logParams = [];
//                $logParams['orderId'] = $orderId;
//                $logParams['content'] = $content;
//                $logParams['logType'] = 1;
//                $logParams['orderStatus'] = 3;
//                $logParams['payStatus'] = 1;
//                $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//                M()->commit();
//                return returnData(true);
//            }
        }
        //todo:通知骑手接单在受理时已取消，改为在分拣待打包时通知2020-11-1 16:31:34
        //非常规订单受理时不呼叫骑手，在分拣员入框商品后呼叫
//        if ($orderInfo["deliverType"] == 2 and $orderInfo["isSelf"] == 0 and $orderInfo["orderType"] != 5) {
//            //预发布 并提交达达订单
//            $funResData = self::DaqueryDeliverFee($loginUserInfo, $orderId);
//            if ($funResData['code'] != 0) {
//                $this->delOrderLog($logId);
//                M()->rollback();
//            } else {
//                //写入订单日志
//                $content = "商家已通知达达取货";
//                $logParams = [];
//                $logParams['orderId'] = $orderId;
//                $logParams['content'] = $content;
//                $logParams['logType'] = 1;
//                $logParams['orderStatus'] = 7;
//                $logParams['payStatus'] = 1;
//                $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//                M()->commit();
//            }
//            return $funResData;
//        }
        //自建司机配送
        if ($orderInfo['deliverType'] == 6 and $orderInfo["isSelf"] == 0) {
            $funResData = self::dirverQueryDeliverFee($loginUserInfo, $orderId);
            if ($funResData['code'] != 0) {
                $this->delOrderLog($logId);
                M()->rollback();
            } //else {
//                M()->commit();
//            }
//            return $funResData;
        }
        //自建物流配送  非常规订单受理时不呼叫骑手，在分拣员入框商品后呼叫
//        if ($orderInfo["deliverType"] == 4 and $orderInfo["isSelf"] == 0 and $orderInfo["orderType"] != 5) {
//            if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
//                $this->delOrderLog($logId);
//                M()->rollback();
//                return returnData(false, -1, 'error', '快跑者未配置');
//            }
////                $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");;
////                fwrite($myfile, "已进入自建物流 \n");
////                fclose($myfile);
//            $funResData = self::KuaiqueryDeliverFee($loginUserInfo, $orderId);
//            if ($funResData['code'] != 0) {
//                $this->delOrderLog($logId);
//                M()->rollback();
//            } else {
//                $content = "商家已通知骑手取货";
//                $logParams = [];
//                $logParams['orderId'] = $orderId;
//                $logParams['content'] = $content;
//                $logParams['logType'] = 1;
//                $logParams['orderStatus'] = 7;
//                $logParams['payStatus'] = 1;
//                $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//                M()->commit();
//            }
////                $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////                $txt = json_encode($funResData);
////                fwrite($myfile, "自建物流调用结果：$txt \n");
////                fclose($myfile);
//            return $funResData;
//        }
//            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
//            fwrite($myfile, "自建物流没走： \n");
//            fclose($myfile);
        //发布订单-end
        M()->commit();
        //非常订单添加到商品采购单
        if (!empty($orderId)) {
            $orderDate = implode(',', [$orderId]);
            $this->addPurchaseGoods($orderDate);
        }
        return returnData(true);
    }

    /**
     * 商家批量受理订单-只能受理【未受理】的订单 #已废弃
     * @param array $shopInfo
     */
    public function batchShopOrderAccept(array $shopInfo)
    {
        $userId = (int)$shopInfo["userId"];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$shopInfo['shopId'];
        if (empty($orderIds)) return array('status' => -2);
        $orderIds = explode(',', $orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        $orderTab = M('orders');
        $orderIdInfo = [];
        $shop_service_module = new ShopsServiceModule();
        $shop_config_result = $shop_service_module->getShopConfig($shopId);
        $shop_config_data = $shop_config_result['data'];
        foreach ($orderIds as $orderId) {
            if ($orderId == '') continue;//订单号为空则跳过
            $whereCheck = [];
            $whereCheck['orderId'] = $orderId;
            $whereCheck['shopId'] = $shopId;
            $res = $orderTab->where($whereCheck)->lock(true)->find();
            //订单状态不符合则跳过 未受理或预售订单-已付款
            if (!in_array($res['orderStatus'], [0, 14])) {
                continue;
            }
            try {
                //事务处理订单日志
                $logOrderDB = M('log_orders');
                M()->startTrans();
                $where = [];
                $where['orderId'] = $orderId;
                $saveData = [];
                $saveData['orderStatus'] = 1;
                if ($shop_config_data['open_suspension_chain'] == 1) {
                    //如果开启了悬挂链将上报状态改为待上报
                    $saveData['is_reporting'] = 0;
                }
                $orderTab->where($where)->save($saveData);
//                $data = array();
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "商家已受理订单";
//                $data["logUserId"] = $userId;
//                $data["logType"] = 0;
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $ra = $logOrderDB->add($data);
                $content = '商家已受理订单';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => $shopInfo['user_id'],
                    'logUserName' => $shopInfo['user_username'],
                    'orderStatus' => 1,
                    'payStatus' => 1,
                    'logType' => 1,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);

                //打包订单
                $shopOrderProduceParams = [];
                $shopOrderProduceParams['userId'] = $userId;
                $shopOrderProduceParams['shopId'] = $shopId;
                $shopOrderProduceParams['orderId'] = $orderId;
                $shopOrder = $this->shopOrderProduce($shopOrderProduceParams, $orderId);
                if ($shopOrder['status'] == -1) {
                    $saveData['orderStatus'] = 0;
                    $orderTab->where($where)->save($saveData);
                    M()->rollback();
                    return $shopOrder;
                }
                M()->commit();
                //通知骑手取货
                // if($rsv["deliverType"]==2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0){
                if ($res["deliverType"] == 2 and $res["isSelf"] == 0) {
                    //预发布 并提交达达订单
                    $funResData = self::DaqueryDeliverFee($shopOrderProduceParams);
                    return $funResData;
                }
                //自建司机配送
                if ($res['deliverType'] == 6 and $res["isSelf"] == 0) {
                    $funResData = self::dirverQueryDeliverFee($orderId);
                    return $funResData;
                }
                //自建物流配送
                if ($res["deliverType"] == 4 and $res["isSelf"] == 0) {
                    if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                        $rsdata["status"] = -1;
                        $rsdata["info"] = '请联系管理员配置快跑者信息';
                        return $rsdata;
                    };
//                $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");;
//                fwrite($myfile, "已进入自建物流 \n");
//                fclose($myfile);
                    $funResData = self::KuaiqueryDeliverFee($shopOrderProduceParams);
//                $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
//                $txt = json_encode($funResData);
//                fwrite($myfile, "自建物流调用结果：$txt \n");
//                fclose($myfile);
                    return $funResData;
                }
//            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
//            fwrite($myfile, "自建物流没走： \n");
//            fclose($myfile);
            } catch (\Exception $e) {
                //回滚事务
                M()->rollback();
            }
            $editOrderNum++;
            if ($res['orderType'] == 5) {
                $orderIdInfo[] = $orderId;
            }
        }
        if ($editOrderNum == 0) return array('status' => -1);//没有符合条件的执行操作
        if ($editOrderNum < $orderNum) return array('status' => -2);//只有部分订单符合操作
        //非常订单添加到商品采购单
        if (!empty($orderIdInfo)) {
            $orderDate = implode(',', $orderIdInfo);
            $this->addPurchaseGoods($orderDate);
        }
        return array('status' => 1);
    }

    /**
     * @param $orderIds
     * 非常订单添加到商品采购单
     */
    public function addPurchaseGoods($orderIds)
    {
        $where = [];
        $where['wog.orderId'] = ["IN", $orderIds];

        $goodsModel = M('goods');
        $orderModel = M('orders wo');
        $purchaseGoodsModel = M('purchase_goods');

        $orderList = $orderModel
            ->join('left join wst_order_goods wog on wog.orderId = wo.orderId')
            ->where($where)
            ->field('wog.*,wo.shopId')
            ->group('wog.id')
            ->select();
        $goodsModule = new GoodsModule();
        foreach ($orderList as $value) {
            $goodsInfo = $goodsModel->where(['goodsId' => $value['goodsId']])->find();
            $goods = [];
            $goods['orderId'] = $value['orderId'];
            $goods['goodsId'] = $value['goodsId'];
            $goods['goodsNums'] = $value['goodsNums'];
//            $goods['goodsPrice'] = $goodsInfo['shopPrice'];
            $goods['goodsPrice'] = $goodsInfo['goodsUnit'];
            $goods['skuId'] = $value['skuId'];
            $goods['skuSpecAttr'] = $value['skuSpecAttr'];
            $goods['goodsName'] = $value['goodsName'];
            $goods['goodsThums'] = $value['goodsThums'];
            $goods['remarks'] = $value['remarks'];
            $goods['shopId'] = $value['shopId'];
            if (!empty($goods['skuId'])) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($goods['skuId'], 2);
                if (!empty($skuDetail)) {
                    $goods['goodsPrice'] = $skuDetail['purchase_price'];
                    $goods['skuSpecAttr'] = $skuDetail['skuSpecAttrTwo'];
                }
            }

//            $where = [];
//            $where['goodsId'] = $value['goodsId'];
//            $where['skuId'] = $value['skuId'];
//            $where['shopId'] = $value['shopId'];
//            $goodsDate = $purchaseGoodsModel->where($where)->find();
//            if (empty($value['skuId'])) {
//                if (empty($goodsDate)) {
//                    $purchaseGoodsModel->add($goods);
//                } else {
//                    $purchaseGoodsModel->where($where)->setInc('goodsNums', $value['goodsNums']);
//                }
//            }
//            if (!empty($value['skuId'])) {
//                if (empty($goodsDate)) {
//                    $purchaseGoodsModel->add($goods);
//                } else {
//                    $purchaseGoodsModel->where($where)->setInc('goodsNums', $value['goodsNums']);
//                }
//            }
            $purchaseGoodsModel->add($goods);
        }
    }

    /**
     * 商家打包订单-只能处理[受理]的订单
     * @param array $loginUserInfo 当前登陆者信息
     * @param int $orderId 订单id
     * @param object $trans
     */
    public function shopOrderProduce(array $loginUserInfo, int $orderId, $trans = null)
    {
        //M()->startTrans(); PS:订单逻辑更改后,这里开启事务会出现嵌套事务影响受理订单流程
        $shopId = (int)$loginUserInfo["shopId"];
        $orderId = (int)$orderId;
        $shopInfo = M('shop_configs')->where(array('shopId' => $shopId))->find();
        if (empty($shopInfo)) {
            return returnData(false, -1, 'error', '门店信息有误');
        }
        $orderTab = M('orders');
        $where = [];
        $where['orderFlag'] = 1;
        $where['orderId'] = $orderId;
        $where['shopId'] = $shopId;
        $orderInfo = $orderTab->where($where)->find();
        if ($orderInfo["orderStatus"] != 1) {
            //M()->rollback();
            return returnData(false, -1, 'error', '非受理订单不能打包');
        }
        if ($orderInfo["orderStatus"] == 2) {
            //M()->rollback();
            return returnData(false, -1, 'error', '已打包订单不能重复打包');
        }
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $where = [];
        $where['orderId'] = $orderInfo['orderId'];
        $save = [];
        $save['orderStatus'] = 2;
        $updateRes = $orderTab->where($where)->save($save);
        if (!$updateRes) {
            //M()->rollback();
            $model->rollback();
            return returnData(false, -1, 'error', '订单打包失败');
        }

        //目前受理时只要开启分拣功能那么就要分配框位
        if ($shopInfo['isSorting'] == 1) {//是否开启分拣功能  1：是 -1：否
            if ($orderInfo['deliverType'] == 6 && $orderInfo['isSelf'] == 1) {
                if (empty($trans)) {
                    $model->commit();
                }
                return returnData(true);
            }
            //分配框位
            $basketInfo = autoDistributionBasket($shopId);
            if ($basketInfo['apiCode'] !== 0) {
                //M()->rollback();
                $model->rollback();
                return returnData(false, -1, 'error', $basketInfo['apiInfo']);
            }
            $where = [];
            $where['orderId'] = $orderInfo['orderId'];
            $save = [];
            $save['basketId'] = $basketInfo['apiData']['basketId'];
            $updateRes = $orderTab->where($where)->save($save);
            if (!$updateRes) {
                //M()->rollback();
                $model->rollback();
                return returnData(false, -1, 'error', '订单分配框位失败');
            }
            $orderInfo = $orderTab->where(['orderId' => $orderId])->find();
            if ($orderInfo['basketId'] > 0) {
                //店铺是否分配分拣员 ,如果是，则为该订单分配分拣员
                if ($shopInfo['isSorting'] == 1) {
                    $result = autoDistributionSorting($shopId, $orderId);
                    if ($result['status'] !== 1) {
                        //M()->rollback();
                        $model->rollback();
                        return returnData(false, -1, 'error', $result['msg']);
                    }
                }
                //分配下链口
                if ($shopInfo['open_suspension_chain'] == 1) {
                    $chain_service_module = new ChainServiceModule();
                    $params = array(
                        'shop_id' => $shopId,
                        'status' => 1,
                    );
                    $sort = array(
                        'field' => 'current_order_num',
                        'value' => 'asc',
                    );
                    $chain_result = $chain_service_module->getChainListByParams($params, $sort);
                    if ($chain_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        $model->rollback();
                        return returnData(false, -1, 'error', '分配下链口失败');
                    }
                    $chain_data = $chain_result['data'];
                    $where = array(
                        'orderId' => $orderId
                    );
                    $save = array(
                        'chain_id' => $chain_data[0]['chain_id']
                    );
                    $bind_chain_res = $orderTab->where($where)->save($save);//为订单分配当前订单量最少的下链口
                    if (!$bind_chain_res) {
                        $model->rollback();
                        return returnData(false, -1, 'error', '订单分配下链口失败');
                    }
                    //增加订下链口当前订单量
                    $setinc_res = $chain_service_module->setIncChainOrderNum($save['chain_id'], 1, M());
                    if ($setinc_res['code'] != ExceptionCodeEnum::SUCCESS) {
                        $model->rollback();
                        return returnData(false, -1, 'error', '订单分配下链口失败');
                    }
                }
            }
        }

        //店铺是否分配筐位 ,如果是，则为该订单分配筐位
//        if ($shopInfo['isDistributionBasket'] > 0) {
//
//            $basketInfo = autoDistributionBasket($shopId);
//            if ($basketInfo['apiCode'] !== 0) {
//                //M()->rollback();
//                return returnData(false, -1, 'error', $basketInfo['apiInfo']);
//            }
//            $where = [];
//            $where['orderId'] = $orderInfo['orderId'];
//            $save = [];
//            $save['basketId'] = $basketInfo['apiData']['basketId'];
//            $updateRes = $orderTab->where($where)->save($save);
//            if (!$updateRes) {
//                //M()->rollback();
//                return returnData(false, -1, 'error', '订单分配框位失败');
//            }
//        }

//        $orderInfo = $orderTab->where(['orderId' => $orderId])->find();
//        if ($orderInfo['basketId'] > 0) {
//            //店铺是否分配分拣员 ,如果是，则为该订单分配分拣员
//            if ($shopInfo['isSorting'] == 1) {
//                $result = autoDistributionSorting($shopId, $orderId);
//                if ($result['status'] !== 1) {
//                    //M()->rollback();
//                    return returnData(false, -1, 'error', $result['msg']);
//                }
//            }
//        }
        //M()->commit();
        if (empty($trans)) {
            $model->commit();
        }
        return returnData(true);
    }

    /**
     * 商家批量打包订单-只能处理[受理]的订单
     */
    public function batchShopOrderProduce($parameter = array())
    {
        M()->startTrans();
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId ? $userId : $parameter['userId'];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId ? $shopId : $parameter['shopId'];
        $shopInfo = M('shop_configs')->where(array('shopId' => $shopId))->find();
        if (empty($shopInfo)) {
            M()->rollback();
            return array('status' => -1);
        }

        if ($orderIds == '') {
            M()->rollback();
            return array('status' => -2);
        }
        $orderIds = explode(',', $orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        $om = M('orders');
        foreach ($orderIds as $orderId) {
            if ($orderId == '') continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=" . $shopId;
            $rsv = $this->queryRow($sql);
            if ($rsv["orderStatus"] != 1) continue;//订单状态不符合则跳过

            $sql = "UPDATE __PREFIX__orders set orderStatus = 2 WHERE orderId = $orderId and shopId=" . $shopId;
            $rs = $this->execute($sql);
            if (!$rs) {
                M()->rollback();
                return array('status' => -2);
            }

            //店铺是否分配筐位 ,如果是，则为该订单分配筐位
            if ($shopInfo['isDistributionBasket'] > 0) {
                $basketInfo = autoDistributionBasket($shopId);
                if ($basketInfo['apiCode'] !== 0) {
                    M()->rollback();
                    return $basketInfo;
                }
                $om->where(array('orderId' => $orderId, 'shopId' => $shopId))->save(array('basketId' => $basketInfo['apiData']['basketId']));
            }

            $orderInfo = $om->where(array('orderId' => $orderId, 'shopId' => $shopId))->find();
            if ($orderInfo['basketId'] > 0) {
                //店铺是否分配分拣员 ,如果是，则分配分拣员，分拣方式，（0：按整笔订单分 1：按商品分）
                if ($shopInfo['isSorting'] == 1) {
                    $result = autoDistributionSorting($shopId, $orderId);
                    if ($result['status'] !== 1) {
                        M()->rollback();
                        return $result;
                    }
                }
            }

//            $data = array();
//            $m = M('log_orders');
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "订单打包中";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $ra = $m->add($data);

            $logOrderDB = M('log_orders');
            $logOrderDB->startTrans();
            try {
                // 提交事务
//                $data = array();
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "订单打包中";
//                $data["logUserId"] = $userId;
//                $data["logType"] = 0;
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $ra = $logOrderDB->add($data);
                $content = '订单打包中';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => $parameter['user_id'],
                    'logUserName' => $parameter['user_username'],
                    'orderStatus' => 2,
                    'payStatus' => 1,
                    'logType' => 1,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
                M()->commit();
            } catch (\Exception $e) {
                //回滚事务
                M()->rollback();
            }

            $editOrderNum++;
        }
        M()->commit();
        if ($editOrderNum == 0) return array('status' => -1);//没有符合条件的执行操作
        if ($editOrderNum < $orderNum) return array('status' => -2);//只有部分订单符合操作
        return array('status' => 1);
    }


    /**
     * 使用达达预发布并提交订单
     * @param array $loginUserInfo 当前登陆者信息
     * @param int $orderId 订单id
     */
    static function DaqueryDeliverFee(array $loginUserInfo, int $orderId)
    {
        $orderTab = M('orders');
        $areaTab = M('areas');
        //orders表里的字段 updateTime设置为空
        $save = [];
        $save['updateTime'] = null;
        $save['deliverType'] = 2;//达达配送
        $where = [];
        $where['orderId'] = $orderId;
        $orderTab->where($where)->save($save);
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = $orderTab->where($where)->find();//当前订单数据
        //判断当前订单是否在达达覆盖范围城市内
        $shopTab = M('shops');
        $where = [];
        $where['shopId'] = $orderInfo['shopId'];
        $shopInfo = $shopTab->where($where)->find();
        $dadaShopId = $shopInfo['dadaShopId'];
        $dadaOriginShopId = $shopInfo['dadaOriginShopId'];
        $dadam = D("Home/dada");
        $dadamod = $dadam->cityCodeList(null, $dadaShopId);//线上环境
// 		$dadamod = $dadam->cityCodeList(null,73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
        if (!empty($dadamod['niaocmsstatic'])) {
            $msg = '获取城市出错#' . $dadamod['info'];
            return returnData(false, -1, 'error', $msg);
        }
        $where = [];
        $where['areaId'] = $orderInfo['areaId2'];
        $areaInfo = $areaTab->where($where)->find();
        $cityNameisWx = str_replace(array('省', '市'), '', $areaInfo['areaName']);
        //判断当前是否在达达覆盖范围内
        for ($i = 0; $i <= count($dadamod) - 1; $i++) {
            if ($cityNameisWx == $dadamod[$i]['cityName']) {//如果在配送范围
                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
                $txt = '在达达覆盖范围';
                fwrite($myfile, $txt . '\n');
                fclose($myfile);
                //进行订单预发布
                //备参
                $DaDaData = array(
                    'shop_no' => $dadaOriginShopId,//	门店编号，门店创建后可在门店列表和单页查看
                    // 	'shop_no'=> '11047059',//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
                    'origin_id' => $orderInfo["orderNo"],//第三方订单ID
                    'city_code' => $dadamod[$i]['cityCode'],//	订单所在城市的code（查看各城市对应的code值）
                    'cargo_price' => $orderInfo["totalMoney"],//	订单金额 不加运费
                    'is_prepay' => 0,//	是否需要垫付 1:是 0:否 (垫付订单金额，非运费)
                    'receiver_name' => $orderInfo["userName"],//收货人姓名
                    'cargo_weight' => 1,
                    'origin_mark_no' => $orderInfo["orderNo"],//订单来源编号，最大长度为30，该字段可以显示在骑士APP订单详情页面
                    'receiver_address' => $orderInfo["userAddress"],//	收货人地址
                    'receiver_phone' => $orderInfo["userPhone"],//	收货人手机号
                    // 'callback' => WSTDomain() . '/wstapi/logistics/notify_dada.php' //	回调URL（查看回调说明）
                    'callback' => WSTDomain() . '/Home/dada/dadaOrderCall' //	回调URL（查看回调说明）
                );
                $dada_res_data = $dadam->queryDeliverFee($DaDaData, $dadaShopId);
                // $dada_res_data = $dadam->queryDeliverFee($DaDaData,73753);///测试环境 测试完成 此段删除 开启上行代码-------------------------------------
                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
                $txt = json_encode($dada_res_data);
                fwrite($myfile, "我是发布前的请求#" . $txt . '\n');
                fclose($myfile);
                //更改订单某些字段
                $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
                $data['deliveryNo'] = $dada_res_data['deliveryNo'];//来自达达返回的平台订单号
                //$data["deliverMoney"] = $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
                //注释的原因:用户下完单,订单金额和配送费都算好了,这里又把运费改掉了,造成金额不符
                $data["orderStatus"] = 7;//	订单状态
                $where = [];
                $where['orderId'] = $orderId;
                $orderTab->where($where)->save($data);
                //发布订单
                $dadam = D("Home/dada");
                $dadamod = $dadam->addAfterQuery(array('deliveryNo' => $data['deliveryNo']), $dadaShopId);
                // $dadamod = $dadam->addAfterQuery(array('deliveryNo'=>$data['deliveryNo']),73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
                if (!empty($dadamod['niaocmsstatic'])) {
                    $msg = '发布订单#' . $dadamod['info'];
                    return returnData(false, -1, 'error', $msg);
                }
                return returnData(true);
            }
        }
        $msg = '达达发布订单失败';
        return returnData(false, -1, 'error', $msg);
    }

    /**
     *快跑者 发布订单
     * @param array $loginUserInfo 当前操作者信息
     * @param int $orderId 订单id
     * @return array $data
     */
    static function KuaiqueryDeliverFee(array $loginUserInfo, int $orderId)
    {
        $orderTab = M('orders');
        //orders表里的字段 updateTime设置为空
        $save['updateTime'] = null;
        $save['deliverType'] = 4;//快跑者
        $where = [];
        $where['orderId'] = $orderId;
        $orderTab->where($where)->save($save);
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = $orderTab->where($where)->find();//当前订单数据
        // 获取店铺详情数据
        $shopTab = M('shops');
        $where = [];
        $where['shopId'] = $orderInfo['shopId'];
        $shopInfo = $shopTab->where($where)->find();
        //创建订单 并获取订单详情
        $M_Kuaipao = D("Home/Kuaipao");
        $M_data_Kuaipao = array(
            'team_token' => $shopInfo['team_token'],
            'shop_id' => $shopInfo['shopId'],
            'shop_name' => $shopInfo['shopName'],
            'shop_tel' => $shopInfo['shopTel'],
            'shop_address' => $shopInfo['shopAddress'],
            'shop_tag' => "{$shopInfo['longitude']},{$shopInfo['latitude']}",
            'customer_name' => $orderInfo['userName'],
            'customer_tel' => $orderInfo['userPhone'],
            'customer_tag' => "{$orderInfo['lng']},{$orderInfo['lat']}",
            'customer_address' => $orderInfo['userAddress'],
            'order_no' => $orderInfo['orderNo'],
            'pay_status' => 0,
        );
        $M_res_Kuaipao = null;
        $M_info_Kuaipao = null;
        $M_error_Kuaipao = null;
        $M_Kuaipao->createOrder($M_data_Kuaipao, $M_res_Kuaipao, $M_info_Kuaipao, $M_error_Kuaipao);
        $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($M_data_Kuaipao);
        fwrite($myfile, "自建物流调用结果详情： $txt # $M_res_Kuaipao #  $M_info_Kuaipao # $M_error_Kuaipao \n");
        fclose($myfile);
        if ($M_res_Kuaipao) {//如果成功
            $dada_res_data['deliveryNo'] = $M_res_Kuaipao['trade_no'];
            //查询订单详细信息
            $getOrderInfo_res = null;
            $getOrderInfo_info = null;
            $getOrderInfo_error = null;
            $M_Kuaipao->getOrderInfo($M_res_Kuaipao['trade_no'], $getOrderInfo_res, $getOrderInfo_info, $getOrderInfo_error);
            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($getOrderInfo_res);
            fwrite($myfile, "查询订单结果详情： $txt \n");
            fclose($myfile);
            if ($getOrderInfo_res) {
                $dada_res_data['distance'] = (float)$getOrderInfo_res['distance'] * 1000;
                $dada_res_data['fee'] = $getOrderInfo_res['pay_fee'];
            }
        }
        //更改订单某些字段
        $data = [];
        $data['distance'] = $getOrderInfo_res['distance'];//配送距离(单位：米)
        $data['deliveryNo'] = $getOrderInfo_res['trade_no'];//来自跑腿平台返回的平台订单号

        //$data["deliverMoney"] = $getOrderInfo_res['fee'];//	实际运费(单位：元)，运费减去优惠券费用
        //注释掉快跑者运费的原因:用户下完单,订单金额和配送费都算好了,这里又把运费改掉了,造成金额不符
        $data["orderStatus"] = 7;//	订单状态

        //这时候没有骑手信息的
// 			$data["dmName"] =  $dada_res_data['courier_name'];//	骑手姓名
// 			$data["dmMobile"] =  $dada_res_data['courier_tel'];//	骑手电话

        // $whereCheck = [];
        // $whereCheck['orderStatus'] = 7;
        // $whereCheck['orderId'] = $orderId;
        // $whereCheck['shopId'] = $orderInfo['shopId'];
        // $whereCheck['deliveryNo'] = array('exp', 'is null');
        // $res = M('orders')->where($whereCheck)->lock(true)->find();
        // if ($res) {
        //     $msg = '请勿重复通知骑手取货';
        //     return returnData(false, -1, 'error', $msg);
        // }
        $where = [];
        $where['orderId'] = $orderId;
        $updateRes = $orderTab->where($where)->save($data);
        if ($updateRes === false) {
            $msg = '通知骑手取货失败';
            return returnData(false, -1, 'error', $msg);
        }
        return returnData(true);
    }

    /**
     * 自建司机配送
     * @param array $loginUserInfo 当前登陆者信息
     * @param int $orderId 订单id
     * */
    static function dirverQueryDeliverFee(array $logUserInfo, int $orderId)
    {
        $orderTab = M('orders');
        $where = [];
        $where['orderId'] = $orderId;
        $where['deliverType'] = 6;
        $where['orderFlag'] = 1;
        $orderInfo = $orderTab->where($where)->find();
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '订单数据有误');
        }
        $userId = $orderInfo['userId'];
        $shopId = $orderInfo['shopId'];
        $regionModule = new RegionModule();
        if ($regionModule->isExistMemberRegion($userId, $shopId)) {
            $regionId = $regionModule->getUserRegionId($userId, $shopId);
            if ($regionId > 0) {
                $saveOrderParams = array(
                    'orderId' => $orderId,
                    'orderStatus' => 16,
                    'regionId' => $regionId,
                );
                $saveOrderRes = (new OrdersModule())->saveOrdersDetail($saveOrderParams);
                if (empty($saveOrderRes)) {
                    return returnData(false, -1, 'error', '分配配送区域失败');
                }
                return returnData(true);
            }
        }
        //为订单分配配送区域id
        $lat = $orderInfo['lat'];
        $lng = $orderInfo['lng'];
        //百度转高德
//        $arr = bd_decrypt($lng, $lat);
//        $lng = $arr['gg_lon'];
//        $lat = $arr['gg_lat'];
        $point = [
            'lng' => $lng,
            'lat' => $lat,
        ];
        $regionTab = M('psd_region_range');
        $where = [];
        $where['dataFlag'] = 1;
        $regionRangeList = $regionTab->where($where)->select();
        if (empty($regionRangeList)) {
            return returnData(false, -1, 'error', '请到配送端配置区域信息');
        }
        $saveData = [];
        $saveData['orderStatus'] = 16;//司机待配送
        $saveData['regionId'] = 0;//所属配送区域id
        foreach ($regionRangeList as $key => $value) {
            $deliveryLatLng = htmlspecialchars_decode($value['deliveryLatLng']);
            $pts = json_decode($deliveryLatLng, true);
            foreach ($pts as $data) {
                if (!empty($data['M'])) {
                    $lng_M = $data['M'];
                } else {
                    $lng_M = $data['lng'];
                }
                if (!empty($data['O'])) {
                    $lat_O = $data['O'];
                } else {
                    $lat_O = $data['lat'];
                }
                $arrlnglat[] = array('lng' => $lng_M, 'lat' => $lat_O);
                //$arrlnglat[] = array('lng' => $data['M'], 'lat' => $data['O']);
            }
            $check = is_point_in_polygon($point, $arrlnglat);//检测是否在配送范围
            if ($check) {
                $saveData['regionId'] = $value['regionId'];
                break;
            }
        }
        if (empty($saveData['regionId'])) {
            //未分配到任何区域的,为其匹配一个最近的区域
            foreach ($regionRangeList as $key => $value) {
                $deliveryLatLng = htmlspecialchars_decode($value['deliveryLatLng']);
                $pts = json_decode($deliveryLatLng, true);
                foreach ($pts as $data) {
                    if (!empty($data['M'])) {
                        $lng_M = $data['M'];
                    } else {
                        $lng_M = $data['lng'];
                    }
                    if (!empty($data['O'])) {
                        $lat_O = $data['O'];
                    } else {
                        $lat_O = $data['lat'];
                    }
                    $arrlnglat[] = array('lng' => $lng_M, 'lat' => $lat_O);
                    //$arrlnglat[] = array('lng' => $data['M'], 'lat' => $data['O']);
                }
                //获取坐标到每条边的最短距离
                foreach ($arrlnglat as $arrkey => $arrval) {
                    $arrlnglat[$arrkey]['distance'] = 0;
                    $nextLat = $arrlnglat[$arrkey + 1]['lat'];
                    $nextLng = $arrlnglat[$arrkey + 1]['lng'];
                    if ($arrkey > count($pts)) {
                        unset($arrlnglat[$arrkey]);
                    } else {
                        $arrlnglat[$arrkey]['distance'] = getNearestDistance($arrval['lng'], $arrval['lat'], $nextLng, $nextLat, $lng, $lat);
                    }
                }
                $distance = array_column($arrlnglat, 'distance');
                array_multisort($distance, SORT_ASC, $arrlnglat);
                $regionRangeList[$key]['distance'] = $arrlnglat[0]['distance'];//取出一个最短距离
                unset($arrlnglat);
            }
            $distanceArr = array_column($regionRangeList, 'distance');
            array_multisort($distanceArr, SORT_ASC, $regionRangeList);
            $saveData['regionId'] = $regionRangeList[0]['regionId'];//没有匹配到区域范围则为其分配一个最近的区域
            unset($regionRangeList);
        }
        if (empty($saveData['regionId'])) {
            return returnData(false, -1, 'error', '未分配到区域');
        }
        $where = [];
        $where['orderId'] = $orderId;
        $updateRes = $orderTab->where($where)->save($saveData);
        if (!$updateRes) {
            return returnData(false, -1, 'error', '司机配送失败');
        }
        return returnData(true);
    }

    /**
     * 商家发货配送订单
     * @param array $loginUserInfo 当前登陆者信息
     */
//    public function shopOrderDelivery($loginUserInfo, $obj)
//    {
//        $userId = (int)$obj["userId"];
//        $orderId = (int)$obj["orderId"];
//        $shopId = (int)$obj["shopId"];
//        $weightGJson = $obj["weightGJson"];
//
//        $source = I('source');
//        //$deliverType = (int)$obj["deliverType"];
//        $data = array();
//        $rsdata = array();
//        $sql = "SELECT orderId,orderNo,orderStatus,deliverType,isSelf,realTotalMoney,userId,orderType,shopId FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=" . $shopId;
//        $rsv = $this->queryRow($sql);
//        if ($rsv['orderStatus'] != 2) {
//            return returnData(false, -1, 'error', '已发货订单不能重复发货');
//        }
//
//        //分拣后发货配送---------start--------后加【请勿删除，测试环境有点麻烦，如果正式环境可以将注释打开】
//        $shopConfigInfo = M('shop_configs')->where(['shopId' => $rsv['shopId']])->field('isSorting')->find();//门店配置【是否开启分拣】1：是 -1：否
//        $sortOrder = M('sorting')->where(['orderId' => $orderId, 'sortingFlag' => 1])->select();//分拣信息
//        $sortPack = M('sorting_packaging')->where(['orderId' => $orderId])->find();//打包信息
//
//        //存在分拣任务
//        $orderSortType = 0;//用于判断是否有分拣任务或开启分拣【0:没有|1:有】
//        if (!empty($sortOrder)) {
//            foreach ($sortOrder as $v) {
//                if ($v['status'] != 3) {
//                    return returnData(false, -1, 'error', '请分拣后发货配送');
//                }
//            }
//            if (empty($sortPack)) {
//                return returnData(false, -1, 'error', '请打包后发货配送');
//            }
//            //packType:1:待打包|2:已打包
//            if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
//                return returnData(false, -1, 'error', '请打包后发货配送');
//            }
//            $orderSortType = 1;
//        }
//        //开启分拣
//        if ($shopConfigInfo['isSorting'] == 1) {
//            if (!empty($sortOrder)) {
//                foreach ($sortOrder as $v) {
//                    if ($v['status'] != 3) {
//                        return returnData(false, -1, 'error', '请分拣后发货配送');
//                    }
//                }
//                if (empty($sortPack)) {
//                    return returnData(false, -1, 'error', '请打包后发货配送');
//                }
//                if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
//                    return returnData(false, -1, 'error', '请打包后发货配送');
//                }
//            } else {
//                return returnData(false, -1, 'error', '请分拣后发货配送');
//            }
//            $orderSortType = 1;
//        }
//
//        //-----------------------end-----------------------------------------
//
//        //记录需要退款的商品 在确认收货的时候自动退款  在有分拣任务或开启分拣的情况下此处不进行补差价
//        if (count($weightGJson) > 0 && $orderSortType != 1) {
//            $order_goods = M('order_goods');
//            $goods_pricediffe = M('goods_pricediffe');
//            $orders = M('orders');
//            $mod_goods = M('goods');
//            //验证此笔订单是否包含这些商品
//            for ($i = 0; $i < count($weightGJson); $i++) {
//                $data_order_goods = $order_goods->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find();
//                if (!$data_order_goods) {
////                    $rsdata["status"] = -1;
////                    $rsdata["info"] = '当前订单和商品不匹配';
////                    return $rsdata;
//                    return returnData(false, -1, 'error', '当前订单和商品不匹配');
//                }
//                //判断本订单的商品是否已经记录过了
//                if ($goods_pricediffe->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find()) {
//                    continue;
////                    $rsdata["status"] = -1;
////                    $rsdata["info"] = '本订单的商品已经处理过了';
////                    return $rsdata;
//                    return returnData(false, -1, 'error', '本订单的商品已经处理过了');
//                }
//                $orders_data = $orders->where("orderId = '{$orderId}'")->find();
//                $goods_this_data = $mod_goods->where("SuppPriceDiff=1 and goodsId = '{$weightGJson[$i]['goodsId']}'")->find();
//                $totalMoney_order = $data_order_goods['goodsPrice'] * (int)$data_order_goods['goodsNums'];//当前商品总价 原来
//                $totalMoney_order1 = $data_order_goods['goodsPrice'] / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价
//                //如果重量不够 补差价
//                if ($totalMoney_order > $totalMoney_order1 && $totalMoney_order1 > 0) {
//                    $add_data['orderId'] = $orderId;
//                    $add_data['tradeNo'] = $orders_data['tradeNo'] ? $orders_data['tradeNo'] : 0;
//                    $add_data['goodsId'] = $weightGJson[$i]['goodsId'];
//                    $add_data['money'] = $totalMoney_order - $totalMoney_order1;//修复差价
//                    //后加,退款金额减去使用的优惠券金额和积分金额
//                    $add_data['money'] = bc_math($add_data['money'], bc_math($data_order_goods['couponMoney'], $data_order_goods['scoreMoney'], 'bcadd', 2), 'bcsub', 2);
//                    $add_data['addTime'] = date('Y-m-d H:i:s');
//                    $add_data['userId'] = $orders_data['userId'];
//                    $add_data['weightG'] = $weightGJson[$i]['goodWeight'];
//                    $add_data['goosNum'] = $data_order_goods['goodsNums'];
//                    $add_data['unitPrice'] = $data_order_goods['goodsPrice'];
//                    if ($add_data['money'] <= 0) {
//                        continue;
//                    }
//                    //写入退款记录
//                    if (!$goods_pricediffe->add($add_data)) {
////                        $rsdata["status"] = -1;
////                        $rsdata["info"] = '退款记录写入失败';
////                        return $rsdata;
//                        return returnData(false, -1, 'error', '退款记录写入失败');
//                    }
//                    //信息推送
//                    $push = D('Adminapi/Push');
//                    $push->postMessage(8, $orders_data['userId'], $orders_data['orderNo'], $shopId);
//
////                    $log_orders = M("log_orders");
////                    $data["orderId"] = $orderId;
////                    //$data["logContent"] =  $data_order_goods['goodsName'] . '#补差价：' . sprintf("%.2f",substr(sprintf("%.3f",$add_data['money']), 0, -1)) .'元。确认收货后返款！';
////                    $data["logContent"] = $data_order_goods['goodsName'] . '#补差价：' . $add_data['money'] . '元。确认收货后返款！';
////                    $data["logUserId"] = $orders_data['userId'];
////                    $data["logType"] = "0";
////                    $data["logTime"] = date("Y-m-d H:i:s");
////                    $log_orders->add($data);
//                    $content = $data_order_goods['goodsName'] . '#补差价：' . $add_data['money'] . '元。确认收货后返款！';
//                    $logParams = [
//                        'orderId' => $orderId,
//                        'logContent' => $content,
//                        'logUserId' => $loginUserInfo['user_id'],
//                        'logUserName' => $loginUserInfo['user_username'],
//                        'orderStatus' => $orders_data['orderStatus'],
//                        'payStatus' => 1,
//                        'logType' => 1,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                }
//            }
//        }
//
//        //订单需要判断是否已分拣====start===============改2020-11-5 16:38:56
//        if (in_array($rsv["deliverType"], [2, 4]) and $rsv["isSelf"] == 0) {
//            $orderId = $rsv['orderId'];
//            $sortInfo = M('sorting')->where(["orderId" => $orderId])->select();
//            if (empty($sortInfo)) {//未分拣则进行呼叫骑手
//                if ($rsv["deliverType"] == 2) {
//                    //预发布 并提交达达订单
//                    $funResData = self::DaqueryDeliverFee($loginUserInfo, $orderId);
//                    if ($funResData['code'] != 0) {
//                        return $funResData;
//                    } else {
//                        //写入订单日志
//                        $content = "商家已通知达达取货";
//                        $logParams = [];
//                        $logParams['orderId'] = $orderId;
//                        $logParams['content'] = $content;
//                        $logParams['logType'] = 1;
//                        $logParams['orderStatus'] = 7;
//                        $logParams['payStatus'] = 1;
//                        $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//                    }
//                    return $funResData;
//                }
//                //自建物流配送
//                if ($rsv["deliverType"] == 4) {
//                    if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
//                        return returnData(false, -1, 'error', '快跑者未配置');
//                    }
//                    $funResData = self::KuaiqueryDeliverFee($loginUserInfo, $orderId);
//                    if ($funResData['code'] != 0) {
//                        return $funResData;
//                    } else {
//                        $content = "商家已通知骑手取货";
//                        $logParams = [];
//                        $logParams['orderId'] = $orderId;
//                        $logParams['content'] = $content;
//                        $logParams['logType'] = 1;
//                        $logParams['orderStatus'] = 7;
//                        $logParams['payStatus'] = 1;
//                        $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//                    }
//                    return $funResData;
//                }
//            }
//        }
//        //发货订单================end==============================
//
//        //配送方式【0：商城配送 | 1：门店配送 | 2：达达配送 | 3：蜂鸟配送 | 4：快跑者 | 5：自建跑腿 | 6：自建司机 |22：自提】
//        if (!in_array($rsv['deliverType'], [0, 1])) {
//            //非门店配送的订单,门店不能直接发货配送
////            $rsdata["status"] = -1;
////            $rsdata["info"] = '该笔订单已交由第三方发货配送';
////            return $rsdata;
//            return returnData(false, -1, 'error', '该笔订单已交由第三方发货配送');
//        }
//        $data["logContent"] = "商家已发货";
////        if ($rsv['isSelf'] == 1 && empty($source)) {
////            return returnData(false, -1, 'error', '请输入取货码');
////        }
////        if ($rsv["isSelf"] == 1 and !empty($source)) {//如果是自提 提货码不为空
////            //判断是否对的上
////            $mod_user_self_goods = M('user_self_goods');
////            $mod_user_self_goods_data = $mod_user_self_goods->where('source = ' . $source)->find();
////            if ($mod_user_self_goods_data['onStart'] == 1) {
////                //return array('status' => -1, 'info' => '已提货订单不能重复提货');
////                return returnData(false, -1, 'error', '已提货订单不能重复提货');
////            }
////            if ($mod_user_self_goods_data['orderId'] != $orderId) {
////                //return array('status' => -1, 'info' => '取货码与订单不符');
////                return returnData(false, -1, 'error', '取货码与订单不符');
////            }
////            //改为已取货
////            $where['id'] = $mod_user_self_goods_data['id'];
////            $saveData['onStart'] = 1;
////            $saveData['onTime'] = date('Y-m-d H:i:s');
////            $mod_user_self_goods->where($where)->save($saveData);
////            $data["logContent"] = "用户已自提";
////        }
//        $orderStatus = 3;
//        if ($rsv['isSelf'] == 1) {
//            //$orderStatus = 4;
//            //写入订单日志
//            $content = "待取货";
//            $logParams = [];
//            $logParams['orderId'] = $orderId;
//            $logParams['content'] = $content;
//            $logParams['logType'] = 1;
//            $logParams['orderStatus'] = 3;
//            $logParams['payStatus'] = 1;
//            D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
//        }
//        $sql = "UPDATE __PREFIX__orders set deliverType=1,orderStatus = {$orderStatus},deliveryTime='" . date('Y-m-d H:i:s') . "' WHERE orderId = $orderId and shopId=" . $shopId;
//        $rs = $this->execute($sql);
//        //订单发货通知
//        $push = D('Adminapi/Push');
//        $push->postMessage(9, $rsv['userId'], $rsv['orderNo'], $shopId);
//
//        $content = $data['logContent'];
//        $logParams = [];
//        $logParams['orderId'] = $orderId;
//        $logParams['content'] = $content;
//        $logParams['logType'] = 1;
//        $logParams['orderStatus'] = 3;
//        $logParams['payStatus'] = 1;
//        $this->addOrderLog($loginUserInfo, $logParams);
//
////        $rsdata["status"] = 1;
////        $rsdata["info"] = '操作成功';
//        return returnData(true);
//    }

    //上面注释的原来的
    public function shopOrderDelivery($loginUserInfo, $obj)
    {
        $orderId = (int)$obj["orderId"];
        $shopId = (int)$obj["shopId"];
        $weightGJson = $obj["weightGJson"];
        $orderModule = new OrdersModule();
        $orderField = 'orderId,orderNo,orderStatus,deliverType,isSelf,realTotalMoney,userId,orderType,shopId,tradeNo';
        $orderDetail = $orderModule->getOrderInfoById($orderId, $orderField, 2);
        if ($orderDetail['orderStatus'] != 2) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前订单不允许发货，请核实');
        }
//        $goodsModule = new GoodsModule();
        $shopModule = new ShopsModule();
        $logOrderModule = new LogOrderModule();
        $shopConfigInfo = $shopModule->getShopConfig($shopId, 'isSorting', 2);
        //下面关于分拣打包相关的是原来的代码,不清楚就不重写了
        $orderSortType = 0;//用于判断是否有分拣任务或开启分拣【0:没有|1:有】
//        $sortOrder = M('sorting')->where(['orderId' => $orderId, 'sortingFlag' => 1])->select();//分拣信息
//        $sortPack = M('sorting_packaging')->where(['orderId' => $orderId])->find();//打包信息
//        //存在分拣任务
//        $orderSortType = 0;//用于判断是否有分拣任务或开启分拣【0:没有|1:有】
//        if (!empty($sortOrder)) {
//            foreach ($sortOrder as $v) {
//                if ($v['status'] != 3) {
//                    return returnData(false, -1, 'error', '请分拣后发货配送');
//                }
//            }
//            if (empty($sortPack)) {
//                return returnData(false, -1, 'error', '请打包后发货配送');
//            }
//            //packType:1:待打包|2:已打包
//            if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
//                return returnData(false, -1, 'error', '请打包后发货配送');
//            }
//            $orderSortType = 1;
//        }
//        //开启分拣
//        if ($shopConfigInfo['isSorting'] == 1) {
//            if (!empty($sortOrder)) {
//                foreach ($sortOrder as $v) {
//                    if ($v['status'] != 3) {
//                        return returnData(false, -1, 'error', '请分拣后发货配送');
//                    }
//                }
//                if (empty($sortPack)) {
//                    return returnData(false, -1, 'error', '请打包后发货配送');
//                }
//                if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
//                    return returnData(false, -1, 'error', '请打包后发货配送');
//                }
//            } else {
//                return returnData(false, -1, 'error', '请分拣后发货配送');
//            }
//            $orderSortType = 1;
//        }
//        //-----------------------end-----------------------------------------
        if ($orderDetail['deliverType'] != 6) {
            $sortOrder = M('sorting')->where(['orderId' => $orderId, 'sortingFlag' => 1])->select();//分拣信息
            $sortPack = M('sorting_packaging')->where(['orderId' => $orderId])->find();//打包信息
            //存在分拣任务
            $orderSortType = 0;//用于判断是否有分拣任务或开启分拣【0:没有|1:有】
            if (!empty($sortOrder)) {
                foreach ($sortOrder as $v) {
                    if ($v['status'] != 3) {
                        return returnData(false, -1, 'error', '请分拣后发货配送');
                    }
                }
                if (empty($sortPack)) {
                    return returnData(false, -1, 'error', '请打包后发货配送');
                }
                //packType:1:待打包|2:已打包
                if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
                    return returnData(false, -1, 'error', '请打包后发货配送');
                }
                $orderSortType = 1;
            }
            //开启分拣
            if ($shopConfigInfo['isSorting'] == 1) {
                if (!empty($sortOrder)) {
                    foreach ($sortOrder as $v) {
                        if ($v['status'] != 3) {
                            return returnData(false, -1, 'error', '请分拣后发货配送');
                        }
                    }
                    if (empty($sortPack)) {
                        return returnData(false, -1, 'error', '请打包后发货配送');
                    }
                    if (!empty($sortPack) && (int)$sortPack['packType'] != 2) {
                        return returnData(false, -1, 'error', '请打包后发货配送');
                    }
                } else {
                    return returnData(false, -1, 'error', '请分拣后发货配送');
                }
                $orderSortType = 1;
            }
            //-----------------------end-----------------------------------------
        }
        //发货扣除库房库存-start
        $stockRes = $orderModule->deductionOrderGoodsStock($orderId, $weightGJson, $loginUserInfo);
        if ($stockRes['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $stockRes['msg']);
        }
        //发货扣除库房库存-end

        $push = D('Adminapi/Push');
        //记录需要退款的商品 在确认收货的时候自动退款  在有分拣任务或开启分拣的情况下此处不进行补差价
        $sorting_module = new SortingModule();
        $goods_module = new GoodsModule();
        $goodsData = array();
        if (count($weightGJson) > 0 && $orderSortType != 1) {
            foreach ($weightGJson as $weightGJsonDetail) {
                $goodsId = (int)$weightGJsonDetail['goodsId'];
                $skuId = (int)$weightGJsonDetail['skuId'];
                $goodsWeight = (float)$weightGJsonDetail['goodWeight'];//实际称重
                $orderGoodsWhere = array(
                    'orderId' => $orderId,
                    'goodsId' => $goodsId,
                    'skuId' => $skuId,
                );
                $orderGoodsDetial = $orderModule->getOrderGoodsInfoByParams($orderGoodsWhere, '*', 2);
                if (empty($orderGoodsDetial)) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前订单和商品不匹配');
                }
                $goodsData[] = array(//用于创建出库单
                    'goods_id' => $goodsId,
                    'sku_id' => $skuId,
                    'nums' => $orderGoodsDetial['goodsNums'],
                    'actual_delivery_quantity' => $goodsWeight,
                );
                $isDiffPirceOrderGoods = $orderModule->isDiffPirceOrderGoods($orderId, $goodsId, $skuId);
                if ($isDiffPirceOrderGoods) {//已经补差价的就跳过
                    continue;
                }
                $orderGoodsParams = array(
                    'id' => $orderGoodsDetial['id'],
                    'weight' => $goodsWeight,
                    'sortingNum' => $goodsWeight,
                );
                $saveOrderGoodsId = $orderModule->saveOrderGoods($orderGoodsParams);
                if (empty($saveOrderGoodsId)) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品#{$orderGoodsDetial['goodsName']}更新称重信息失败");
                }
//                $goodsField = 'goodsId,goodsName,weightG';
//                $currGoodsDetail = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
//                $weightG = (float)$currGoodsDetail['weightG'];//包装系数
//                if ($skuId > 0) {
//                    $currSkuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
//                    $weightG = (float)$currSkuDetail['weigetG'];
//                }
//                $totalMoney_order = $orderGoodsDetial['goodsPrice'] * (int)$orderGoodsDetial['goodsNums'];//原来的当前商品总价
//                $totalMoney_order1 = $orderGoodsDetial['goodsPrice'] / (int)$weightG * $goodsWeight;//根据重量得出的总价
                $totalMoney_order = bc_math($orderGoodsDetial['goodsPrice'], $orderGoodsDetial['goodsNums'], 'bcmul', 2);//原来的当前商品总价
                $goodsUnitPrice = bc_math($totalMoney_order, $orderGoodsDetial['goodsNums'], 'bcdiv', 2);
                $totalMoney_order1 = bc_math($goodsUnitPrice, $goodsWeight, 'bcmul', 2);
                if ($totalMoney_order > $totalMoney_order1 && $totalMoney_order1 > 0) {//需要补差价
                    $diffData = array();
                    $diffData['orderId'] = $orderId;
                    $diffData['tradeNo'] = !empty($orderDetail['tradeNo']) ? $orderDetail['tradeNo'] : '';
                    $diffData['goodsId'] = $goodsId;
                    $diffData['skuId'] = $skuId;
                    $diffData['money'] = $totalMoney_order - $totalMoney_order1;//修复差价
                    //后加,退款金额减去使用的优惠券金额和积分金额
                    $diffData['money'] = bc_math($diffData['money'], bc_math($orderGoodsDetial['couponMoney'], $orderGoodsDetial['scoreMoney'], 'bcadd', 2), 'bcsub', 2);
                    $diffData['userId'] = $orderDetail['userId'];
                    $diffData['weightG'] = $goodsWeight;
                    $diffData['goosNum'] = $orderGoodsDetial['goodsNums'];
                    $diffData['unitPrice'] = $orderGoodsDetial['goodsPrice'];
                    if ($diffData['money'] <= 0) {
                        continue;
                    }
                    $diffId = $orderModule->saveOrdersGoodsPriceDiff($diffData);
                    if (empty($diffId)) {
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '退款记录写入失败');
                    }
                    //信息推送
                    $push->postMessage(8, $orderDetail['userId'], $orderDetail['orderNo'], $shopId);
                    $content = $orderGoodsDetial['goodsName'] . '#补差价：' . $diffData['money'] . '元。确认收货后返款！';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $loginUserInfo['user_id'],
                        'logUserName' => $loginUserInfo['user_username'],
                        'orderStatus' => $orderDetail['orderStatus'],
                        'payStatus' => 1,
                        'logType' => 1,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    $logOrderModule->addLogOrders($logParams);
                }
                if ($totalMoney_order1 > $totalMoney_order && $totalMoney_order1 > 0) {//多分拣的商品金额累积到用户已欠款额度
                    $dec_stock = (float)bc_math($goodsWeight, $orderGoodsDetial['goodsNums'], 'bcsub', 3);
                    if ($dec_stock > 0) {//多分拣的商品金额累积到用户已欠款额度
                        $inc_quota_arrears_res = $sorting_module->incUserQuotaArrears($orderId, $goodsId, $skuId, $dec_stock);
                        if (!$inc_quota_arrears_res) {
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，用户欠款额度处理异常', "用户欠款额度处理异常");
                        }
                    }
                    $edit_goods_stock_res = $goods_module->deductionGoodsStock($goodsId, $skuId, $dec_stock, 1, 1);//如果分拣的商品数量/重量大于购买时,则扣除商品的库存
                    if ($edit_goods_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，商品库房库存不足', "商品库房库存不足");
                    }

                }
            }
            //创建发货出库单-start
            $exWarehouseOrderModule = new ExWarehouseOrderModule();
            $billData = array(
                'pagetype' => 1,
                'shopId' => $shopId,
                'user_id' => $loginUserInfo['user_id'],
                'user_name' => $loginUserInfo['user_username'],
                'remark' => '',
                'relation_order_number' => $orderDetail['orderNo'],
                'relation_order_id' => $orderDetail['orderId'],
                'goods_data' => $goodsData,
            );
            $addBillRes = $exWarehouseOrderModule->addExWarehouseOrder($billData);
            if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "出库单创建失败");
            }
            //创建发货出库单-end
        }
        //本次重点修改称重补差价,下面的代码不涉及,就不改了
        //创建客户结算单-start
        $updateOrderPriceRes = $orderModule->autoUpdateOrderPrice($orderId);
        if (!$updateOrderPriceRes) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "订单金额更新失败");
        }
        $settlementData = array(
            'loginInfo' => $loginUserInfo,
            'relation_order_id' => $orderDetail['orderId'],
            'billFrom' => 1,
        );
        $customerSettlementModule = new CustomerSettlementBillModule();
        $addSettlementRes = $customerSettlementModule->createCustomerSerrlementBill($settlementData);
        if ($addSettlementRes['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "客户结算单创建失败");
        }
        //创建客户结算单-end
        //订单需要判断是否已分拣====start===============改2020-11-5 16:38:56
        if (in_array($orderDetail["deliverType"], [2, 4]) and $orderDetail["isSelf"] == 0) {
            $sortInfo = M('sorting')->where(["orderId" => $orderId])->select();
            if (empty($sortInfo)) {//未分拣则进行呼叫骑手
                if ($orderDetail["deliverType"] == 2) {
                    //预发布 并提交达达订单
                    $funResData = self::DaqueryDeliverFee($loginUserInfo, $orderId);
                    if ($funResData['code'] != 0) {
                        return $funResData;
                    } else {
                        //写入订单日志
                        $content = "商家已通知达达取货";
                        $logParams = [];
                        $logParams['orderId'] = $orderId;
                        $logParams['content'] = $content;
                        $logParams['logType'] = 1;
                        $logParams['orderStatus'] = 7;
                        $logParams['payStatus'] = 1;
                        $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
                    }
                    return $funResData;
                }
                //自建物流配送
                if ($orderDetail["deliverType"] == 4) {
                    if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                        return returnData(false, -1, 'error', '快跑者未配置');
                    }
                    $funResData = self::KuaiqueryDeliverFee($loginUserInfo, $orderId);
                    if ($funResData['code'] != 0) {
                        return $funResData;
                    } else {
                        $content = "商家已通知骑手取货";
                        $logParams = [];
                        $logParams['orderId'] = $orderId;
                        $logParams['content'] = $content;
                        $logParams['logType'] = 1;
                        $logParams['orderStatus'] = 7;
                        $logParams['payStatus'] = 1;
                        $logId[] = D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
                    }
                    return $funResData;
                }
            }
        }
        //发货订单================end==============================

        //配送方式【0：商城配送 | 1：门店配送 | 2：达达配送 | 3：蜂鸟配送 | 4：快跑者 | 5：自建跑腿 | 6：自建司机 |22：自提】
        if (!in_array($orderDetail['deliverType'], [0, 1, 6])) {
            //非门店配送的订单,门店不能直接发货配送
//            $rsdata["status"] = -1;
//            $rsdata["info"] = '该笔订单已交由第三方发货配送';
//            return $rsdata;
            return returnData(false, -1, 'error', '该笔订单已交由第三方发货配送');
        }
        $data["logContent"] = "商家已发货";
//        if ($rsv['isSelf'] == 1 && empty($source)) {
//            return returnData(false, -1, 'error', '请输入取货码');
//        }
//        if ($rsv["isSelf"] == 1 and !empty($source)) {//如果是自提 提货码不为空
//            //判断是否对的上
//            $mod_user_self_goods = M('user_self_goods');
//            $mod_user_self_goods_data = $mod_user_self_goods->where('source = ' . $source)->find();
//            if ($mod_user_self_goods_data['onStart'] == 1) {
//                //return array('status' => -1, 'info' => '已提货订单不能重复提货');
//                return returnData(false, -1, 'error', '已提货订单不能重复提货');
//            }
//            if ($mod_user_self_goods_data['orderId'] != $orderId) {
//                //return array('status' => -1, 'info' => '取货码与订单不符');
//                return returnData(false, -1, 'error', '取货码与订单不符');
//            }
//            //改为已取货
//            $where['id'] = $mod_user_self_goods_data['id'];
//            $saveData['onStart'] = 1;
//            $saveData['onTime'] = date('Y-m-d H:i:s');
//            $mod_user_self_goods->where($where)->save($saveData);
//            $data["logContent"] = "用户已自提";
//        }
        $orderStatus = 3;
        if ($orderDetail['isSelf'] == 1) {
            //$orderStatus = 4;
            //写入订单日志
            $content = "待取货";
            $logParams = [];
            $logParams['orderId'] = $orderId;
            $logParams['content'] = $content;
            $logParams['logType'] = 1;
            $logParams['orderStatus'] = 3;
            $logParams['payStatus'] = 1;
            D('Home/Orders')->addOrderLog($loginUserInfo, $logParams);
        }
        $sql = "UPDATE __PREFIX__orders set orderStatus = {$orderStatus},deliveryTime='" . date('Y-m-d H:i:s') . "' WHERE orderId = $orderId and shopId=" . $shopId;
        $rs = $this->execute($sql);
        //订单发货通知
        $push->postMessage(9, $orderDetail['userId'], $orderDetail['orderNo'], $shopId);

        $content = $data['logContent'];
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 1;
        $logParams['orderStatus'] = 3;
        $logParams['payStatus'] = 1;
        $this->addOrderLog($loginUserInfo, $logParams);

//        $rsdata["status"] = 1;
//        $rsdata["info"] = '操作成功';
        return returnData(true);
    }

    /**
     * 商家批量发货配送订单 -------不支持达达物流
     */
    public function batchShopOrderDelivery($obj)
    {
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId ? $userId : $obj['userId'];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId ? $shopId : $obj['shopId'];
        $source = I('source');
        if ($orderIds == '') return array('status' => -2);
        $orderIds = explode(',', $orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        foreach ($orderIds as $orderId) {


            if ($orderId == '') continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus,orderSrc,userId FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=" . $shopId;
            $rsv = $this->queryRow($sql);
            if ($rsv["orderStatus"] != 2) continue;//状态不符合则跳过


            if ($rsv["deliverType"] == 2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0) {
                //预发布 并提交达达订单
                $funResData = self::DaqueryDeliverFee($obj);
                continue;

            }


            $sql = "UPDATE __PREFIX__orders set orderStatus = 3,deliveryTime='" . date('Y-m-d H:i:s') . "' WHERE orderId = $orderId and shopId=" . $shopId;
            $rs = $this->execute($sql);

//            $data = array();
//            $m = M('log_orders');
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "商家已发货";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $ra = $m->add($data);
            $content = '商家已发货';
            $logParams = [
                'orderId' => $orderId,
                'logContent' => $content,
                'logUserId' => $obj['user_id'],
                'logUserName' => $obj['user_username'],
                'orderStatus' => 3,
                'payStatus' => 1,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);
            $editOrderNum++;

            // --- 发货成功,推送消息 --- @author liusijia --- start ---
            if ($rs) {
                $userInfo = M('users')->where(array('userId' => $rsv['userId'], 'userFlag' => 1))->find();
//                if (empty($userInfo)) continue;
//                if ($rsv['orderSrc'] == 0) {//商城
//
//                } else if ($rsv['orderSrc'] == 1) {//微信
//
//                } else if ($rsv['orderSrc'] == 2) {//手机版
//
//                } else if ($rsv['orderSrc'] == 3) {//app
//                    if (!empty($userInfo) && !empty($userInfo['registration_id']))
//                        pushMessageByRegistrationId('订单消息提醒', "订单编号为 " . $rsv['orderNo'] . " 的订单已发货，请注意查收。", $userInfo['registration_id'], []);
//                } else if ($rsv['orderSrc'] == 4) {//小程序
//
//                }
                $push = D('Adminapi/Push');
                $push->postMessage(9, $userInfo['userId'], $rsv['orderNo'], $shopId);
            }
            // --- 发货成功,推送消息 --- @author liusijia --- end ---
        }
        if ($editOrderNum == 0) return array('status' => -1);//没有符合条件的执行操作
        if ($editOrderNum < $orderNum) return array('status' => -2);//只有部分订单符合操作
        return array('status' => 1);
    }

    /**
     * 商家确认收货
     */
    public function shopOrderReceipt($loginUserInfo, $obj)
    {
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=" . $shopId;
        $rsv = $this->queryRow($sql);
        if ($rsv["orderStatus"] != 4) {
            $rsdata["status"] = -1;
            return $rsdata;
        }

        $sql = "UPDATE __PREFIX__orders set orderStatus = 5 WHERE orderId = $orderId and shopId=" . $shopId;
        $rs = $this->execute($sql);

//        $data = array();
//        $m = M('log_orders');
//        $data["orderId"] = $orderId;
//        $data["logContent"] = "商家确认已收货，订单完成";
//        $data["logUserId"] = $userId;
//        $data["logType"] = 0;
//        $data["logTime"] = date('Y-m-d H:i:s');
//        $ra = $m->add($data);
        $content = '商家确认已收货，订单完成';
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => $loginUserInfo['user_id'],
            'logUserName' => $loginUserInfo['user_username'],
            'orderStatus' => 5,
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        $ra = M('log_orders')->add($logParams);
        $rsdata["status"] = $ra;
        return $rsdata;
    }

    /**
     * 商家确认拒收/不同意拒收
     */
    public function shopOrderRefund($loginUserInfo, $obj)
    {
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $shopId = (int)$obj["shopId"];
        $type = (int)I('type');
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus,useScore FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag = 1 and shopId=" . $shopId;
        $rsv = $this->queryRow($sql);
        if ($rsv["orderStatus"] != -3) {
            $rsdata["status"] = -1;
            return $rsdata;
        }
        //同意拒收
        if ($type == 1) {
            $sql = "UPDATE __PREFIX__orders set orderStatus = -4 WHERE orderId = $orderId and shopId=" . $shopId;
            $rs = $this->execute($sql);
            //加回库存
            if ($rs > 0) {
                $sql = "SELECT goodsId,goodsNums,goodsAttrId,skuId from __PREFIX__order_goods WHERE orderId = $orderId";
                $oglist = $this->query($sql);
                foreach ($oglist as $key => $ogoods) {
                    $goodsId = $ogoods["goodsId"];
                    $goodsNums = $ogoods["goodsNums"];
                    $goodsAttrId = $ogoods["goodsAttrId"];
                    $skuId = $ogoods['skuId'];
                    $goods_goodsNums = gChangeKg($goodsId, $goodsNums, 1, $skuId);
                    $sql = "UPDATE __PREFIX__goods set goodsStock = goodsStock+$goods_goodsNums WHERE goodsId = $goodsId";
                    $this->execute($sql);

                    //更新进销存系统商品的库存
                    //updateJXCGoodsStock($goodsId, $goodsNums, 0);

                    if ($goodsAttrId > 0) {
                        $sql = "UPDATE __PREFIX__goods_attributes set attrStock = attrStock+$goods_goodsNums WHERE id = $goodsAttrId";
                        $this->execute($sql);
                    }
                }

                if ($rsv["useScore"] > 0) {
                    $sql = "UPDATE __PREFIX__users set userScore=userScore+" . $rsv["useScore"] . " WHERE userId=" . $userId;
                    $this->execute($sql);

//                    $data = array();
//                    $m = M('user_score');
//                    $data["userId"] = $userId;
//                    $data["score"] = $rsv["useScore"];
//                    $data["dataSrc"] = 4;
//                    $data["dataId"] = $orderId;
//                    $data["dataRemarks"] = "拒收订单返还";
//                    $data["scoreType"] = 1;
//                    $data["createTime"] = date('Y-m-d H:i:s');
//                    $m->add($data);
                    $content = '拒收订单返还';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $loginUserInfo['user_id'],
                        'logUserName' => $loginUserInfo['user_username'],
                        'orderStatus' => -4,
                        'payStatus' => 1,
                        'logType' => 1,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                }
            }
            $orderStatus = -4;
        } else {//不同意拒收
            if (I('rejectionRemarks') == '') return $rsdata;//不同意拒收必须填写原因
            $sql = "UPDATE __PREFIX__orders set orderStatus = -5 WHERE orderId = $orderId and shopId=" . $shopId;
            $rs = $this->execute($sql);
            $orderStatus = -5;
        }
//        $data = array();
//        $m = M('log_orders');
//        $data["orderId"] = $orderId;
//        $data["logContent"] = ($type == 1) ? "商家同意拒收" : "商家不同意拒收：" . I('rejectionRemarks');
//
//        $data["logUserId"] = $shopId;
//        $data["logType"] = 1;
//        $data["logTime"] = date('Y-m-d H:i:s');
//        $ra = $m->add($data);
        $content = ($type == 1) ? "商家同意拒收" : "商家不同意拒收：" . I('rejectionRemarks');
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => $loginUserInfo['user_id'],
            'logUserName' => $loginUserInfo['user_username'],
            'orderStatus' => $orderStatus,
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        $ra = M('log_orders')->add($logParams);
        $rsdata["status"] = $ra;;
        return $rsdata;
    }

    /**
     * 检查订单是否已支付
     */
    public function checkOrderPay($obj)
    {
        $userId = (int)$obj["userId"];
        $orderId = (int)I("orderId");
        if ($orderId > 0) {
            $sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderId = $orderId AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        } else {
            $orderunique = session("WST_ORDER_UNIQUE");
            $sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderunique = '$orderunique' AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        }
        $rsv = $this->query($sql);
        $oIds = array();
        for ($i = 0; $i < count($rsv); $i++) {
            $oIds[] = $rsv[$i]["orderId"];
        }
        $orderIds = implode(",", $oIds);
        $data = array();
        if (count($rsv) > 0) {
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsAttrName,g.goodsStock,og.goodsNums, og.goodsAttrId, ga.attrStock FROM  __PREFIX__goods g ,__PREFIX__order_goods og
					left join __PREFIX__goods_attributes ga on ga.goodsId=og.goodsId and og.goodsAttrId=ga.id
					WHERE og.goodsId = g.goodsId and og.orderId in($orderIds)";
            $glist = $this->query($sql);
            if (count($glist) > 0) {
                $rlist = array();
                foreach ($glist as $goods) {
                    if ($goods["goodsAttrId"] > 0) {
                        if ($goods["attrStock"] < $goods["goodsNums"]) {
                            $rlist[] = $goods;
                        }
                    } else {
                        if ($goods["goodsStock"] < $goods["goodsNums"]) {
                            $rlist[] = $goods;
                        }
                    }
                }
                if (count($rlist) > 0) {
                    $data["status"] = -2;
                    $data["rlist"] = $rlist;
                } else {
                    $data["status"] = 1;
                }
            } else {
                $data["status"] = 1;
            }
        } else {
            $data["status"] = -1;
        }
        return $data;
    }


    /**
     * 获取订单详情
     */
    public function getOrderDetailsApi($obj)
    {
        $config = $GLOBALS['CONFIG'];
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
//        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=" . $userId . " or shopId=" . $shopId . ")";
//        $order = $this->queryRow($sql);
        $where = [];
        $where['orders.shopId'] = $shopId;
        $where['orders.orderId'] = $orderId;
        $where['orders.orderFlag'] = 1;
        $field = 'orders.*,self.source,self.onStart';
        $order = M('orders orders')
            ->join('left join wst_user_self_goods self on self.orderId=orders.orderId')
            ->where($where)
            ->field($field)
            ->find();
        $autoReceiveDays = (int)$GLOBALS['CONFIG']['autoReceiveDays'];
        $autoReceiveDays = ($autoReceiveDays > 0) ? $autoReceiveDays : 10;//避免有些客户没有设置值
        $lastDay = date("Y-m-d 00:00:00", strtotime("-" . $autoReceiveDays . " days"));
        $orderCreateTime = explode(' ', $order['createTime'])[0];
        $autoReceiveDay = strtotime($lastDay) - strtotime($orderCreateTime);
        if ($autoReceiveDay <= 0) {//剩余自动收货时间
            $order['autoReceiveDay'] = 0;
        } else {
            $order['autoReceiveDay'] = $autoReceiveDay / 86400;
        }
        if ($order['isSelf'] == 1) {
            $order['deliverType'] = 22;//自提
        }
        $order['deliverType'] = (string)$order['deliverType'];//避免前端因为类型报错

        if (empty($order)) return $data;
        $order['source'] = (string)$order['source'];
        $order['onStart'] = (int)$order['onStart'];
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] == 1) {
                $order['realTotalMoney'] = $order['realTotalMoney'];
            } else {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        if ($order['isSelf'] == 1) {
            $order['deliverMoney'] = 0;
        }
        $sql = "select og.orderId,og.weight,og.goodsId ,g.goodsSn,g.SuppPriceDiff,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,og.skuSpecAttr,og.remarks,og.skuId,og.skuSpecAttr,og.skuSpecStr,og.unitName
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        $sortingModule = new SortingModule();
        $order['billDeliveryPriceTotal'] = 0;
        foreach ($goods as $key => $val) {
            $goods[$key]['goodsNums'] = (float)$val['goodsNums'];
            $goods[$key]['keyNum'] = $key + 1;
            $goods[$key]['goodsPriceTotal'] = bc_math($val['shopPrice'], $val['goodsNums'], 'bcmul', 2);
            $goods[$key]['deliveryPrice'] = $val['shopPrice'];//发货单价
            $goods[$key]['deliveryNumOrWeight'] = (string)0;//发货数量/重量
            $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $val['goodsId'], $val['skuId']);
            if (!empty($sortingOrderGoodsDetail)) {
                $personId = $sortingOrderGoodsDetail['personId'];
                $sortingId = $sortingOrderGoodsDetail['id'];
                $sortingGoodsInfo = $sortingModule->getSortingGoodsDetailByParams($personId, $sortingId, $val['goodsId'], $val['skuId']);
                if (!empty($sortingGoodsInfo)) {
                    $goods[$key]['deliveryNumOrWeight'] = $sortingGoodsInfo['sorting_ok_weight'];
                }
            }
            $goods[$key]['deliveryPriceTotal'] = bc_math($goods[$key]['deliveryNumOrWeight'], $goods[$key]['deliveryPrice'], 'bcmul', 2);//发货金额小计
            $order['billDeliveryPriceTotal'] += $goods[$key]['deliveryPriceTotal'];
            //替换编码和商品图片
            if (!empty($val['skuId'])) {
                $skuInfo = M('sku_goods_system')->where(['skuId' => $val['skuId'], 'dataFlag' => 1, 'goodsId' => $val['goodsId']])->find();
                $goods[$key]['goodsSn'] = $skuInfo['skuBarcode'];
                $goods[$key]['goodsThums'] = $skuInfo['skuGoodsImg'];
            }
            $goodsInfo = M('goods')->where(['goodsId' => $val['goodsId']])->field('weightG')->find();
            $goods[$key]['weightG'] = (float)$goodsInfo['weightG'];
            $goods[$key]['personName'] = '';
            $goods[$key]['personMobile'] = '';
            $sql = "select per.userName,per.mobile,gr.startDate,gr.endDate from __PREFIX__sorting_goods_relation gr left join __PREFIX__sorting s on s.id=gr.sortingId left join __PREFIX__sortingpersonnel per on per.id=s.personId where s.sortingFlag=1 and gr.goodsId='" . $val['goodsId'] . "' and s.orderId='" . $val['orderId'] . "'";
            $info = $this->queryRow($sql);
            $goods[$key]['sortStartDate'] = 0;
            $goods[$key]['sortEndDate'] = 0;
            $goods[$key]['sortOverTime'] = 0;
            if ($info) {
                $goods[$key]['personName'] = $info['userName'];
                $goods[$key]['personMobile'] = $info['mobile'];
                $goods[$key]['sortStartDate'] = $info['startDate']; //分拣开始时间
                $goods[$key]['sortEndDate'] = $info['endDate'];//分拣结束时间
                if (is_null($goods[$key]['sortStartDate'])) {
                    $goods[$key]['sortStartDate'] = 0;
                }
                if (is_null($goods[$key]['sortEndDate'])) {
                    $goods[$key]['sortEndDate'] = 0;
                }
                $endDateInt = strtotime($info['endDate']);
                $startDateInt = strtotime($info['startDate']);
                $diffTime = timeDiff($endDateInt, $startDateInt);//耗时
                $goods[$key]['sortOverTime'] = $diffTime['day'] * 24 * 60 + $diffTime['hour'] * 60 + $diffTime['min'];
            }
        }
        $order["goodsList"] = $goods;
        //发票信息
        $order['invoiceInfo'] = [];
        if (isset($order['isInvoice']) && $order['isInvoice'] == 1) {
            $order['invoiceInfo'] = M('invoice')->where("id='" . $order['invoiceClient'] . "'")->find();
        }
        //发票详情(新)
        $order['receiptInfo'] = (array)M('invoice_receipt')->where(['receiptId' => $order['receiptId']])->find();
        if (!empty($order['receiptInfo'])) {
            $userInfo = M('users')->where(['userId' => $order['userId']])->find();
            $order['receiptInfo']['userName'] = $userInfo['userName'];
            $order['receiptInfo']['userPhone'] = $userInfo['userPhone'];
        }
        //后加包邮起步价
        $order['deliveryFreeMoney'] = M('shops')->where("shopId")->getField('deliveryFreeMoney');
        $order['buyer'] = (array)M('users')->where("userId='" . $order['userId'] . "'")->find();

        //优惠券金额
        $order['couponMoney'] = 0;
        $couponInfo = M('coupons')->where(['couponId' => $order['couponId']])->find();
        if ($couponInfo) {
            $order['couponMoney'] = $couponInfo['couponMoney'];
        }
        $order['orderMoney'] = formatAmount($order['totalMoney'] + $order['deliverMoney']);
        //订单状态流转时间
        $appraisesInfo = M('goods_appraises')->where(array(
            'orderId' => $orderId
        ))
            ->order('id asc')
            ->find();
        $order['status_time'] = array(
            'create_time' => (string)$order['createTime'],//提交订单
            'pay_time' => (string)$order['pay_time'],//支付订单
            'delivery_time' => (string)$order['deliveryTime'],//商家发货
            'receive_time' => (string)$order['receiveTime'],//确认收货
            'appraises_time' => (string)$appraisesInfo['createTime'],//完成评价
        );
        //代码太恶心,加到最后面吧
        $order['requireTimeDate'] = date('Y-m-d', strtotime($order['requireTime']));
        $shopModule = new ShopsModule();
        $order['shopName'] = '';
        $shopDetail = $shopModule->getShopsInfoById($shopId, 'shopName,shopTel', 2);
        if (!empty($shopDetail)) {
            $order['shopName'] = $shopDetail['shopName'];
        }
        $order['shopTel'] = $shopDetail['shopTel'];
        $order['payment_userid'] = $order['buyer']['userId'];
        $order['payment_username'] = $order['buyer']['userName'];
        $order['lineName'] = '';
        $order['driverName'] = '';//司机姓名
        $order['driverPhone'] = '';//司机手机号
        $order['inviteUserName'] = '';//业务员名称 PS:暂定分销人员
        $order['inviteUserPhone'] = '';//业务员手机号 PS:暂定分销人员
        if (!empty($order['lineId'])) {
            $order['lineName'] = (new LineModule())->getLineDetailById($order['lineId'])['lineName'];
        }
        if (!empty($order['driverId'])) {
            $driverDetail = (new DriverModule())->getDriverDetailById($order['driverId'], 'driverName,driverPhone', 0);
            $order['driverName'] = $driverDetail['driverName'];//司机姓名
            $order['driverPhone'] = $driverDetail['driverPhone'];//司机联系方式
        }
        $usersModule = new UsersModule();
        $inviteUserDetail = $usersModule->getBusinessPersonnelDetail($userInfo['userPhone']);
        if (!empty($inviteUserDetail)) {
            $order['inviteUserId'] = $inviteUserDetail['userId'];
            $order['inviteUserName'] = $inviteUserDetail['userName'];
            $order['inviteUserPhone'] = $inviteUserDetail['userPhone'];
        }
        if ((float)$order['billDeliveryPriceTotal'] >= (float)$order['totalMoney']) {
            $order['billDiffPriceTotal'] = 0;
        } else {
            $order['billDiffPriceTotal'] = (float)bc_math($order['totalMoney'], $order['billDeliveryPriceTotal'], 'bcsub', 2);
        }
        $order['billDiffPriceTotalRMB'] = num_to_rmb($order['billDiffPriceTotal']);
        $order['payFromName'] = (new OrderEnum())->getPayFromName()[$order['payFrom']];
        return $order;
    }

    /**
     * 获取订单日志
     */
    public function getOrderLogApi($obj)
    {
        $orderId = (int)$obj["orderId"];
        $sql = "SELECT * FROM __PREFIX__log_orders WHERE orderId = $orderId order by logTime asc ";
        $logs = $this->query($sql);
        foreach ($logs as &$item) {
            $item['orderStatusName'] = $this->getOrderStatusName($item['orderStatus']);
            $item['payStatusName'] = $this->getPayStatusName($item['payStatus']);
        }
        unset($item);
        return (array)$logs;
    }

    /**
     * 获取订单状态名称
     * @param int $orderStatus
     * @return string $orderStatusName
     * */
    public function getOrderStatusName(int $orderStatus)
    {
        //订单状态[-8:门店拒绝/门店取消 | -7:用户取消(受理后-店铺已读) | -6:用户取消(已受理后-店铺未读)（支付成功-发货前取消） | -5:门店不同意拒收 | -4:门店同意拒收 | -3:用户拒收 | -2:未付款的订单 | -1：用户取消(未受理前) | 0:未受理 | 1:已受理 | 2:打包中 | 3:配送中 | 4:用户确认收货 | 7:等待骑手接单 | 8:骑手-待取货 | 9：骑手-订单被取消（只写入日志） | 10：骑手-订单过期（并写日志 作为异常订单显示）| 11：骑手-投递异常（只写入日志）| 12:预售订单（未支付）| 13:预售订单（首款已付） | 14：预售订单-已付款 | 15:拼团 | 16:司机待配送|17:司机配送中]
        $orderStatusName = '未知';
        switch ($orderStatus) {
            case -8:
                //$orderStatusName = '门店取消';
                $orderStatusName = '已关闭';
                break;
            case -7:
                //$orderStatusName = '用户取消(受理后-店铺已读)';
                $orderStatusName = '已关闭';
                break;
            case -6:
                //$orderStatusName = '用户取消(受理后-店铺未读)';
                $orderStatusName = '已关闭';
                break;
            case -5:
                //$orderStatusName = '门店不同意拒收';
                $orderStatusName = '已关闭';
                break;
            case -4:
                //$orderStatusName = '门店同意拒收';
                $orderStatusName = '已关闭';
                break;
            case -3:
                //$orderStatusName = '用户拒收';
                $orderStatusName = '已关闭';
                break;
            case -2:
                $orderStatusName = '待付款';
                break;
            case -1:
                //$orderStatusName = '用户取消(未受理前)';
                $orderStatusName = '已关闭';
                break;
            case 0:
                $orderStatusName = '待接单';
                break;
            case 1:
                //$orderStatusName = '已接单';
                $orderStatusName = '待发货';
                break;
            case 2:
                //$orderStatusName = '打包中';
                $orderStatusName = '待发货';
                break;
            case 3:
                $orderStatusName = '待收货';
                break;
            case 4:
                $orderStatusName = '已完成';
                break;
            case 7:
                //$orderStatusName = '等待骑手接单';
                $orderStatusName = '外卖配送';
                break;
            case 8:
                //$orderStatusName = '骑手-待取货';
                $orderStatusName = '外卖配送';
                break;
            case 9:
                //$orderStatusName = '骑手-订单被取消';
                $orderStatusName = '外卖配送';
                break;
            case 10:
                //$orderStatusName = '骑手-订单过期';
                $orderStatusName = '骑手配送';
                break;
            case 11:
                $orderStatusName = '骑手-投递异常';
                break;
            case 12:
                $orderStatusName = '预售订单（未支付）';
                break;
            case 13:
                $orderStatusName = '预售订单（首款已付）';
                break;
            case 14:
                $orderStatusName = '预售订单（首款已付）';
                break;
            case 16:
                //$orderStatusName = '司机待配送';
                $orderStatusName = '外卖配送';
                break;
            case 17:
                //$orderStatusName = '司机配送中';
                $orderStatusName = '外卖配送';
                break;
        }
        return $orderStatusName;
    }

    /**
     * 获取支付状态名称
     * @param int $payStatus
     * @return string $payStatusName
     * */
    public function getPayStatusName(int $payStatus)
    {
        $payStatusName = '未知';
        switch ($payStatus) {
            case 0:
                $payStatusName = '未支付';
                break;
            case 1:
                $payStatusName = '已支付';
                break;
            case 2:
                $payStatusName = '已退款';
                break;
        }
        return $payStatusName;
    }

    /**
     * 获取订单量
     */
    public function getOrdercount($parameter = array())
    {
        $m = M('orders');
        $sql = "SELECT count(orderId) as count FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = date('Y-m-d H:i:s', $parameter['startTime']);
            $endTime = date('Y-m-d H:i:s', $parameter['endTime']);
        }
        if ($startTime && $endTime) {
            $sql .= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql .= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        if (isset($parameter['defPreSale'])) {
            $sql .= " and defPreSale={$parameter['defPreSale']} ";
        }
        return $this->query($sql);
    }

    /**
     * 获取订单量
     */
    public function getOrderForDay($parameter = array())
    {
        $m = M('orders');
        $sql = "SELECT count(orderId) as count,sum(realTotalMoney) as sum_amount FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = date('Y-m-d H:i:s', $parameter['startTime']);
            $endTime = date('Y-m-d H:i:s', $parameter['endTime']);
        }
//        if($startTime && $endTime){
//        $sql = "SELECT count(orderId) as count,sum(realTotalMoney) as sum_amount (select date_format(createTime,'%e') as createTime,realTotalMoney from __PREFIX__orders where createTime between {$startTime} and {$endTime}) t group by t.createTime order by t.createTime";
//            $res = $this->query($sql);
//            echo "<pre>";print_r($sql);exit;
//            return $res;
//        }

        return false;
//        $month = '11';
//        $year = '2018';
//        $max_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);   //当月最后一天
//        $min = $year.'-'.$month.'-01 00:00:00';
//        $max = $year.'-'.$month.'-'.$max_day.' 23:59:59';
//        $sql = "select t.createTime,count(*) as total_num,sum(t.realTotalMoney) as amount (select date_format(createTime,'%e') as createTime,realTotalMoney from __PREFIX__orders where (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') >= '{$min}')
//and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') <= '{$max}')) t group by t.createTime order by t.createTime";
//        $return = mysqli_query($sql);
//        return false;

    }


    /**
     * 获取时间范围内的订单
     */
    public function getTimeOrderForZitiLisrt($parameter = array())
    {
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = date('Y-m-d H:i:s', $parameter['startTime']);
            $endTime = date('Y-m-d H:i:s', $parameter['endTime']);
        }
        if ($startTime && $endTime) {
            $sql .= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql .= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        if (isset($parameter['isSelf'])) {
            $sql .= " and isSelf={$parameter['isSelf']}";
        }
        return $this->query($sql);
    }

    /**
     * 根据订单id获取商品列表
     */
    public function getGoodsListForOrder($parameter = array())
    {

        if (!$parameter['orderId'] || !$parameter['shopId']) {
            return array();
        }
        //orderId检测是否为本店
        $res = $this->where("orderId={$parameter['orderId']} and shopId={$parameter['shopId']}")->field(array('orderId'))->find();
        if (!$res) {
            return array();
        }
        $sql = "select og.orderId, og.goodsId ,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = {$parameter['orderId']}";
        $goods = $this->query($sql);
        return $goods;
    }

    /**
     * 获取每件商品预售量 待优化
     */
    public function getOrderGoodsCount($parameter = array())
    {
        if (!$parameter['shopId']) {
            return array();
        }

        $sql = "select og.goodsId,sum(og.goodsNums) as sum_goodsNums
                from __PREFIX__orders as o left join __PREFIX__order_goods as og on o.orderId = og.orderId
                where o.shopId={$parameter['shopId']} ";
        if (isset($parameter['defPreSale'])) {
            $sql .= " and defPreSale={$parameter['defPreSale']}";
        }
        //时间区间
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = date('Y-m-d H:i:s', $parameter['startTime']);
            $endTime = date('Y-m-d H:i:s', $parameter['endTime']);
        }
        if ($startTime && $endTime) {
            $sql .= " and (o.createTime LIKE '%-%' AND STR_TO_DATE(o.createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql .= " and (o.createTime LIKE '%-%' AND STR_TO_DATE(o.createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        //END

        $sql .= " group by og.goodsId";
        $goods = $this->query($sql);
        if (!$goods) {
            return array();
        }
        $gids = array_get_column($goods, 'goodsId');
        $gm = M('goods');
        $where['goodsId'] = array('in', $gids);//cid在这个数组中，
        $goodsList = $gm->where($where)->field(array('goodsId,goodsSn,goodsName,goodsImg,goodsThums'))->select();
        $goodsForIdArr = get_changearr_key($goodsList, 'goodsId');
        foreach ($goods as $key => &$gv) {
            $goodVals = $goodsForIdArr[$gv['goodsId']];
            if ($goodVals && is_array($goodVals)) {
                $gv = array_merge($gv, $goodVals);
            }
        }
        return $goods;
    }

    /**
     * 获取时间区间订单字段每天总数
     */
    public function getOrderSumFieldsDay($parameter = array(), &$msg = '')
    {
        if (!$parameter['shopId']) {
            return array();
        }
        if ($parameter['fields'] && !in_array($parameter['fields'], array('realTotalMoney'))) {
            $msg = 'fields 不在可选范围内';
            return array();
        }

        $sql = "SELECT
                DATE_FORMAT(createTime, '%Y-%m-%d') triggerDay,
                ";
        if ($parameter['fields']) {
            $sql .= "
                SUM({$parameter['fields']}) sum_{$parameter['fields']},
                ";
        }

        $sql .= "count(orderId) as count_orderId
            FROM
                `wst_orders`";
        $sql .= "
            WHERE shopId={$parameter['shopId']}";

        $sql .= " and isPay=1 and orderStatus=4 ";

        //时间区间
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = date('Y-m-d H:i:s', $parameter['startTime']);
            $endTime = date('Y-m-d H:i:s', $parameter['endTime']);
        }
        if ($startTime && $endTime) {
            $sql .= "  and   createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }
        //END

        $sql .= "
            GROUP BY triggerDay;";

//        echo "<pre>";print_r($sql);exit;
        $goods = $this->query($sql);
        return $goods;
    }

    /**
     * 获取时间区间订单字段总数
     */
    public function getOrderSumFields($parameter = array(), &$msg = '')
    {
        if (!$parameter['shopId']) {
            return array();
        }
        if ($parameter['fields'] && !in_array($parameter['fields'], array('realTotalMoney'))) {
            $msg = 'fields 不在可选范围内';
            return array();
        }

        $sql = "SELECT
";
        if ($parameter['fields']) {
            $sql .= "
                SUM({$parameter['fields']}) sum_{$parameter['fields']},
                ";
        }

        $sql .= " count(orderId) as count_orderId
            FROM
                `wst_orders`";
        $sql .= "
            WHERE shopId={$parameter['shopId']}";
        $where = " where o.shopId={$parameter['shopId']} ";
        //时间区间
        $startTime = date('Y-m-d 00:00:00');
        $endTime = date('Y-m-d 23:59:59');
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = $parameter['startTime'];
            $endTime = $parameter['endTime'];
//            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
//            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }

        $sql .= " and isPay=1 and orderStatus=4 ";
        $where .= " and o.isPay=1 and o.orderStatus=4 ";

        if ($startTime && $endTime) {
            $sql .= "  and   createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
            $where .= "  and   o.createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }
        if (isset($parameter['defPreSale'])) {
            $sql .= " and defPreSale={$parameter['defPreSale']} ";
            $where .= " and o.defPreSale={$parameter['defPreSale']} ";
        }
        if (isset($parameter['isSelf'])) {
            $sql .= " and isSelf={$parameter['isSelf']} ";
            $where .= " and o.isSelf={$parameter['isSelf']} ";
        }
        //END

        $goods = $this->query($sql);

        // --- 计算线上总利润 --- start ---
        $sql = "select if(g.SuppPriceDiff=-1,sum((og.goodsPrice-g.goodsUnit)*og.goodsNums),sum(((og.goodsPrice-g.goodsUnit)/g.weightG)*og.weight)) as total_profit from __PREFIX__order_goods as og left join __PREFIX__goods as g on og.goodsId = g.goodsId left join __PREFIX__orders as o on og.orderId = o.orderId " . $where;
        $xianshang_order_profit = $this->query($sql);
        // --- 计算线上总利润 --- end ---

        // --- 新添加了 线上总营业额、线上真实总营业额、线上总订单数、线下总营业额、线下真实总营业额、线下总订单数 --- @author liusijia 2019-11-16 14:02 --- start ---
        if (is_null($goods[0]['sum_realTotalMoney'])) $goods[0]['sum_realTotalMoney'] = 0;//线上总营业额
        if (is_null($goods[0]['sum_realTotalMoney_true'])) $goods[0]['sum_realTotalMoney_true'] = 0;//线上真实总营业额
        if (is_null($goods[0]['count_orderId'])) $goods[0]['count_orderId'] = 0;//线上总订单数
        $goods[0]['xianshang_count_orderId'] = $goods[0]['count_orderId'];//线上总订单数
        $goods[0]['xianshang_sum_realTotalMoney'] = $goods[0]['sum_realTotalMoney'];//线上总营业额
//        $goods[0]['xianshang_sum_realTotalMoney_true'] = $goods[0]['sum_realTotalMoney_true'];//线上真实总营业额
        $goods[0]['xianshang_sum_realTotalMoney_true'] = empty($xianshang_order_profit[0]['total_profit']) ? 0 : $xianshang_order_profit[0]['total_profit'];//线上总利润

        $sql = "select sum(realpayment) as xianxia_sum_realTotalMoney,sum(realpayment) as xianxia_sum_realTotalMoney_true,count(*) as xianxia_count_orderId from __PREFIX__pos_orders where shopId = " . $parameter['shopId'];
        //统计线下利润
        $sql_1 = "select if(g.SuppPriceDiff=-1,sum((pog.presentPrice-g.goodsUnit)*pog.number),sum(((pog.presentPrice-g.goodsUnit)/g.weightG)*pog.weight)) as total_profit from __PREFIX__pos_orders_goods as pog left join __PREFIX__goods as g on pog.goodsId = g.goodsId left join __PREFIX__pos_orders as po on pog.orderid = po.id where po.shopId = " . $parameter['shopId'];
        if ($startTime && $endTime) {
            $sql .= "  and   addtime BETWEEN '{$startTime}'
            AND '{$endTime}'";
            $sql_1 .= "  and   po.addtime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }

        //此处添加订单量为已结算订单量条件 - 与收银系统列表显示一致
        $sql .= " and state = 3";

        $pos_order_data = $this->query($sql);
        $xianxia_order_profit = $this->query($sql_1);
        if (is_null($pos_order_data[0]['xianxia_sum_realTotalMoney'])) $pos_order_data[0]['xianxia_sum_realTotalMoney'] = 0;//线下总营业额
        if (is_null($pos_order_data[0]['xianxia_sum_realTotalMoney_true'])) $pos_order_data[0]['xianxia_sum_realTotalMoney_true'] = 0;//线下真实总营业额
        if (is_null($pos_order_data[0]['xianxia_count_orderId'])) $pos_order_data[0]['xianxia_count_orderId'] = 0;//线下总订单数
        $goods[0]['xianxia_sum_realTotalMoney'] = $pos_order_data[0]['xianxia_sum_realTotalMoney'];//线下总营业额
//        $goods[0]['xianxia_sum_realTotalMoney_true'] = $pos_order_data[0]['xianxia_sum_realTotalMoney_true'];//线下真实总营业额
        $goods[0]['xianxia_count_orderId'] = $pos_order_data[0]['xianxia_count_orderId'];//线下总订单数
        $goods[0]['xianxia_sum_realTotalMoney_true'] = empty($xianxia_order_profit[0]['total_profit']) ? 0 : $xianxia_order_profit[0]['total_profit'];//线下订单总利润
        $goods[0]['count_orderId'] += $goods[0]['xianxia_count_orderId'];
        $goods[0]['sum_realTotalMoney'] += $goods[0]['xianxia_sum_realTotalMoney'];
        $goods[0]['sum_realTotalMoney_true'] = $goods[0]['xianshang_sum_realTotalMoney_true'] + $goods[0]['xianxia_sum_realTotalMoney_true'];
//        $goods[0]['total_profit'] = $goods[0]['xianshang_order_profit'] + $goods[0]['xianxia_order_profit'];
        // --- 新添加了 线上总营业额、线上真实总营业额、线上总订单数、线下总营业额、线下真实总营业额、线下总订单数 --- @author liusijia 2019-11-16 14:02 --- end ---


        return $goods;
    }


    /**
     * 订单列表商品重量设置 单个商品设重 单位为g
     */
    public function setShopGoodsWeight($obj)
    {

        //检查当前商品是否属于这个店铺的
        $mod_goods = M('goods');
        $mod_order_goods = M('order_goods');
        $where['goodsId'] = $obj["goodsId"];
        $res = $mod_goods->where($where)->find();
        if ($res) {
            if ($res['shopId'] != $obj["shopInfo"]['shopId']) {
                //return array('code' => -1, 'msg' => '商品不属于这个店铺', 'data' => null);
                return returnData(false, -1, 'error', '商品不属于这个店铺');
            }
        }

        //更新重量
        $save_data['weight'] = $obj["weight"];
        unset($where);
        $where['orderId'] = $obj["orderId"];
        $where['goodsId'] = $obj["goodsId"];
        if ($mod_order_goods->where($where)->save($save_data) !== false) {
            //return array('code' => 1, 'msg' => '更新成功', 'data' => null);
            return returnData(true);
        } else {
            //return array('code' => -1, 'msg' => '更新失败', 'data' => null);
            return returnData(false, -1, 'error', '更新失败');
        }


    }

    /**
     * 获取 商品设重 单位为g
     */
    public function getShopGoodsWeight($obj)
    {

        //检查当前商品是否属于这个店铺的
        $mod_goods = M('goods');
        $mod_order_goods = M('order_goods');
        $where['goodsId'] = $obj["goodsId"];
        $res = $mod_goods->where($where)->find();
        if ($res) {
            if ($res['shopId'] != $obj["shopInfo"]['shopId']) {
                //return array('code' => -1, 'msg' => '商品不属于这个店铺', 'data' => null);
                return returnData(false, -1, 'error', '商品不属于这个店铺');
            }
        }

        //获取重量
        unset($where);
        $where['orderId'] = $obj["orderId"];
        $where['goodsId'] = $obj["goodsId"];
        $res = (array)$mod_order_goods->where($where)->find();
        return returnData($res);
//        if ($res) {
//            return array('code' => 1, 'msg' => '获取成功', 'data' => $res);
//        } else {
//            return array('code' => -1, 'msg' => '获取失败', 'data' => null);
//        }


    }


    //商家设置用户订单为已读
    public function setOrderUserHasRead($obj)
    {

        //判断是否是当前店铺的订单
        $mod_orders = M('orders');

        $data_orders = $mod_orders->where("orderId =" . $obj["orderId"])->find();
        if ($data_orders['shopId'] != $obj["shopInfo"]['shopId']) {
            return array('code' => -1, 'msg' => '订单与店铺不符', 'data' => null);
        }

        //更新为已读
        $save['orderStatus'] = -7;

        if ($mod_orders->where("orderId = " . $obj["orderId"])->save($save)) {
            return array('code' => 1, 'msg' => '成功', 'data' => null);
        } else {
            return array('code' => -1, 'msg' => '失败', 'data' => null);
        }


    }

    /**
     * 获取自提订单列表
     * @param string orderNo PS:订单号
     * @param string mobile PS:手机号
     * @param string startDate PS:开始日期
     * @param string endDate PS:结束日期
     * @param string userName PS:会员名称
     * @param string goodsName PS:商品名称
     * @param int page PS:页码
     */
    public function queryCustomOrders($obj)
    {
        $config = $GLOBALS['CONFIG'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取订单失败';
        $apiRet['apiState'] = 'error';
        $shopId = (int)$obj["shopId"];
        $pcurr = $obj['page'];
        $orderNo = $obj['orderNo'];
        $where = " WHERE o.shopId = $shopId AND o.isSelf=1 AND o.orderStatus IN(0,1,2,3) AND o.orderFlag=1 ";
        if (!empty($orderNo)) {
            $where .= " AND o.orderNo='" . $orderNo . "'";
        }
        if (!empty($obj['mobile'])) {
            $where .= " AND u.userPhone ='" . $obj['mobile'] . "' ";
        }
        if (!empty($obj['userName'])) {
            $where .= " AND u.userName ='" . $obj['userName'] . "' ";
        }
        if (!empty($obj['startDate']) && !empty($obj['endDate'])) {
            $where .= " AND o.createTime BETWEEN '" . $obj['startDate'] . "' AND '" . $obj['endDate'] . "' ";
        }
        if (!empty($obj['goodsName'])) {
            $where .= " AND og.goodsName ='" . $obj['goodsName'] . "' ";
        }
        $sql = "SELECT o.orderNo,o.isSelf,o.orderId,o.userId,o.userAddress,o.totalMoney,o.realTotalMoney,o.orderStatus,o.isPay,o.setDeliveryMoney,o.deliverMoney,o.createTime,u.userName,u.userPhone,u.userPhoto FROM __PREFIX__orders o 
        LEFT JOIN __PREFIX__users u ON o.userId=u.userId
        LEFT JOIN __PREFIX__order_goods og ON og.orderId=o.orderId 
        $where";
        $sql .= " GROUP BY o.orderId ORDER BY o.orderId DESC ";
        $data = $this->pageQuery($sql, $pcurr);
        if (count($data['root']) > 0) {
            foreach ($data['root'] as $key => $val) {
                if ($config['setDeliveryMoney'] == 2) {//废弃
                    if ($data['root'][$key]['isPay'] == 1) {
                        //$data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['deliverMoney'];
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                    } else {
                        if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                            $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                        }
                    }
                } else {
                    if ($data['root'][$key]['isPay'] != 1 && $data['root'][$key]['setDeliveryMoney'] > 0) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取订单成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $data;
        }
        return $apiRet;
    }


    //快跑者回调地址
    public function kuaipaocallurl($para)
    {
        $M_Kuaipao = D("Home/Kuaipao");

        $myfile = fopen("kuaipaoCallUrl.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($para);
        fwrite($myfile, "回调详情： $txt \n");
        fclose($myfile);
        $mlogo = M('log_orders');
        $mod_orders = M('orders');

        //建立订单记录

        if (!$M_Kuaipao->checkSign($para)) {//验证回调合法性
            return false;
        }

        $order_data = $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->lock(true)->find();//获取订单详情

//          $myfile = fopen("kuaipaoCallUrl.txt", "a+") or die("Unable to open file!");
        //  $txt = json_encode($order_data);
        //  fwrite($myfile, "本系统订单详情： $txt \n");
        //  fclose($myfile);


        $res = $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->field('orderStatus', 'receiveTime')->lock(true)->find();
        if (empty($order_data['dmMobile'])) {
            $getOrderInfo_res = null;
            $getOrderInfo_info = null;
            $getOrderInfo_error = null;
            $M_Kuaipao->getOrderInfo($para['trade_no'], $getOrderInfo_res, $getOrderInfo_info, $getOrderInfo_error);
            $saveOrderData = [];
            $saveOrderData['dmName'] = '';//骑手姓名
            $saveOrderData['dmMobile'] = '';//骑手电话
            if (!empty($getOrderInfo_res)) {
                $saveOrderData['dmName'] = $getOrderInfo_res['courier_name'];
                $saveOrderData['dmMobile'] = $getOrderInfo_res['courier_tel'];
            }
            $where = [];
            $where['orderId'] = $order_data['orderId'];
            $mod_orders->where($where)->save($saveOrderData);
        }

        if ($para['state'] == 4) {//取单中
            //更改订单状态
            if ($res['orderStatus'] == 8) {
                return true;
            }
            $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->save(array('orderStatus' => 8));

            //事务处理取单状态
            $logOrderDB = M('log_orders');
            $where['orderId'] = $order_data['orderId'];
            $where["logContent"] = '骑手取单中';
            $result = $logOrderDB->where($where)->find();
            if ($result) {
                return true;
            }
            M()->startTrans();
            try {
                // 提交事务
//                $data = array();
//                $data["orderId"] = $order_data['orderId'];
//                $data["logContent"] = "骑手取单中";
//                $data["logUserId"] = 0;
//                $data["logType"] = 0;
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $logOrderDB->add($data);
                $content = '骑手取单中';
                $logParams = [
                    'orderId' => $order_data['orderId'],
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 0,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
                M()->commit();
            } catch (\Exception $e) {
                //回滚事务
                M()->rollback();
            }
        }

        if ($para['state'] == 5) {//已取单

            if ($res['orderStatus'] == 3) {
                return true;
            }
            //更改订单状态
            $saveOrder['orderStatus'] = 3;
            $saveOrder['deliveryTime'] = date('Y-m-d H:i:s', time());;


            $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->save($saveOrder);


            //去除筐位，防止占位
            removeBasket($order_data['orderId']);


//   获取订单信息并更新到订单数据表中 ------------------------------还没做呢 更新骑手信息 并更新到日志里


//查询订单详细信息
            $getOrderInfo_res = null;
            $getOrderInfo_info = null;
            $getOrderInfo_error = null;
            $M_Kuaipao->getOrderInfo($para['trade_no'], $getOrderInfo_res, $getOrderInfo_info, $getOrderInfo_error);
            if ($getOrderInfo_res) {
                $dada_res_data['distance'] = (float)$getOrderInfo_res['distance'] * 1000;
                $dada_res_data['fee'] = $getOrderInfo_res['pay_fee'];
            }

//   $myfile = fopen("kuaipaoCallUrl.txt", "a+") or die("Unable to open file!");
// 			   $txt = json_encode($getOrderInfo_res);
// 			   fwrite($myfile, "快跑订单详情： $txt \n");
// 			   fclose($myfile);


            //更改订单某些字段
            $data = null;
            $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
            $data['deliveryNo'] = $getOrderInfo_res['trade_no'];//来自跑腿平台返回的平台订单号


            //$data["deliverMoney"] =  $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
// 			$data["orderStatus"] =  7;//	订单状态


//   $myfile = fopen("kuaipaoCallUrl.txt", "a+") or die("Unable to open file!");
// 			   $txt = json_encode($data);
// 			   fwrite($myfile, "待更新的数据： $txt \n");
// 			   fclose($myfile);


            $mod_orders->where("orderId = '{$order_data["orderId"]}'")->save($data);

//            $data = array();
//            $data["orderId"] = $order_data['orderId'];
//            $data["logContent"] = "骑手已取到货，正在配送中！";
//            $data["logUserId"] = 0;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $mlogo->add($data);
            //事务处理订单日志

            $logOrderDB = M('log_orders');
            $where['orderId'] = $order_data['orderId'];
            $where["logContent"] = '骑手已取到货，正在配送中！';
            $result = $logOrderDB->where($where)->find();
            if ($result) {
                return true;
            }
            M()->startTrans();
            try {
                // 提交事务
//                $data = array();
//                $data["orderId"] = $order_data['orderId'];
//                $data["logContent"] = "骑手已取到货，正在配送中！";
//                $data["logUserId"] = 0;
//                $data["logType"] = 0;
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $ra = $logOrderDB->add($data);
                $content = '骑手已取到货，正在配送中！';
                $logParams = [
                    'orderId' => $order_data['orderId'],
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 3,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
                M()->commit();
            } catch (\Exception $e) {
                //回滚事务
                M()->rollback();
            }
        }

        if ($para['state'] == 6) {//已送达
            if (!empty($res['receiveTime'])) {
                return true;
            }
            //去除筐位，防止占位
            removeBasket($order_data['orderId']);

            //更改订单状态
            // $mod_orders->where("orderNo = '{$para['trade_no']}'")->save(array('orderStatus'=>3));
            //自动确认收货 需要调用共用的确认收货 暂不影响使用 手动确认收货也可以 可根据要求增加--------------------------------------
//            $data = array();
//            $data["orderId"] = $order_data['orderId'];
//            $data["logContent"] = "骑手已送达";
//            $data["logUserId"] = 0;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $mlogo->add($data);
            $content = '骑手已送达';
            $logParams = [
                'orderId' => $order_data['orderId'],
                'logContent' => $content,
                'logUserId' => 0,
                'logUserName' => '系统',
                'orderStatus' => 4,//订单状态 已送达即已收货
                'payStatus' => 1,
                'logType' => 2,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);

            $saveReceive = [];
//            $saveReceive['receiveTime'] = date('Y-m-d H:i:s');
            $saveReceive['orderStatus'] = 4;
            $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->save($saveReceive);

            $param = [];
            $param['orderId'] = $order_data['orderId'];
            $param['userId'] = $order_data['userId'];
            $param['rsv'] = $order_data;
//            //积分相关操作|商品销量
//            editOrderInfo($param);
//
//            //判断商品是否属于分销商品
//            checkGoodsDistribution($order_data['orderId']);
//
//            //发放地推邀请奖励
//            grantPullNewAmount($order_data['orderId']);
//
//            //判断当前订单是否有差价(列表)需要退
//            editPriceDiffer($param);
//
//            //邀请有礼
//            editUserInfo($param);
            //上面的奖励发送先注释掉吧,不知道什么时候的代码了,也未正确处理奖励
            $orderModule = new OrdersModule();
            $content = "骑手已送达，系统自动确认收货";
            $logParams = [
                'orderId' => $order_data['orderId'],
                'logContent' => $content,
                'logUserId' => 0,
                'logUserName' => '系统',
                'orderStatus' => 4,
                'payStatus' => 1,//支付状态【0：未支付|1：已支付|2：已退款】
                'logType' => 2,
            ];
            $orderModule->confirmReceipt($order_data['orderId'], 1, "", $logParams);

            //订单送达时通知
            $push = D('Adminapi/Push');
            $push->postMessage(10, $order_data['userId'], $order_data['orderNo'], $order_data['shopId']);
        }

        if ($para['state'] == 7) {//（已撤销）	商户或团队撤销订单
            //更改订单状态

            if ($res['orderStatus'] == 9) {
                return true;
            }

            $mod_orders->where("deliveryNo = '{$para['trade_no']}'")->save(array('orderStatus' => 9));
//            $data = array();
//            $data["orderId"] = $order_data['orderId'];
//            $data["logContent"] = "商户或者团队撤销订单，如有问题请电话联系我们";
//            $data["logUserId"] = 0;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//
//            $mlogo->add($data);
            $content = '拒收订单返还';
            $logParams = [
                'orderId' => $order_data['orderId'],
                'logContent' => $content,
                'logUserId' => 0,
                'logUserName' => '系统',
                'orderStatus' => 9,
                'payStatus' => 1,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);
        }
        return true;
    }


    /**
     * 撤销（撤销到打包中）
     * @param $param
     */
    public function revokeToPack($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '撤销失败';
        $apiRet['apiState'] = 'error';

        if (empty($param['shopId']) || empty($param['orderId'])) {
            $apiRet['apiInfo'] = '参数不全';
            return $apiRet;
        }
        $om = M('orders');
        $where = array('orderId' => $param['orderId'], 'shopId' => $param['shopId'], 'orderFlag' => 1);
        $orderInfo = $om->where($where)->find();
        if (empty($orderInfo)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }
        if ($orderInfo['orderStatus'] == 2) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '撤销成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $om->where($where)->save(array('orderStatus' => 2));
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '撤销成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }

        return $apiRet;
    }

    /**
     * 删除订单
     * @param $param
     */
    public function deleteOrder($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '删除失败';
        $apiRet['apiState'] = 'error';

        if (empty($param['shopId']) || empty($param['orderId'])) {
            $apiRet['apiInfo'] = '参数不全';
            return $apiRet;
        }
        $om = M('orders');
        $where = array('orderId' => $param['orderId'], 'shopId' => $param['shopId']);
        $orderInfo = $om->where($where)->find();
        if (empty($orderInfo)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }
        if ($orderInfo['orderFlag'] == -1) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '删除成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $om->where($where)->save(array('orderFlag' => -1));
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '删除成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }

        return $apiRet;
    }

    /**
     * 获取时间区间订单字段总数
     */
    public function getOrderSumFieldsTwo($parameter = array(), &$msg = '')
    {
        if (!$parameter['shopId']) {
            return array();
        }
        if ($parameter['fields'] && !in_array($parameter['fields'], array('realTotalMoney'))) {
            $msg = 'fields 不在可选范围内';
            return array();
        }

        $sql = "SELECT
";
        if ($parameter['fields']) {
            $sql .= "
                SUM({$parameter['fields']}) sum_{$parameter['fields']},
                ";
        }

        $sql .= " count(orderId) as count_orderId
            FROM
                `wst_orders`";
        $where = " WHERE shopId={$parameter['shopId']} ";
        $where_1 = " WHERE o.shopId={$parameter['shopId']} ";
        //时间区间
        if ($parameter['startTime'] && $parameter['endTime']) {
            $startTime = $parameter['startTime'];
            $endTime = $parameter['endTime'];
        }
        // $where.= " and isPay=1 and orderStatus=4 ";
        // $where_1.= " and o.isPay=1 and o.orderStatus=4 ";
        $where .= " and isPay=1 and orderStatus>=0 and isRefund=0 ";
        $where_1 .= " and o.isPay=1 and o.orderStatus>=0 and o.isRefund=0 ";
        if ($startTime && $endTime) {
            $where .= "  and   createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
            $where_1 .= "  and   o.createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }
        if (isset($parameter['defPreSale'])) {
            $where .= " and defPreSale={$parameter['defPreSale']} ";
            $where_1 .= " and o.defPreSale={$parameter['defPreSale']} ";
        }
        if (isset($parameter['isSelf'])) {
            $where .= " and isSelf={$parameter['isSelf']} ";
            $where_1 .= " and o.isSelf={$parameter['isSelf']} ";
        }
        $sql .= $where;
        //END

//        echo "<pre>";print_r($sql);exit;
        $goods = $this->query($sql);
        if ($parameter['fields'] == 'realTotalMoney' && is_null($goods[0]['sum_realTotalMoney'])) {
            $goods[0]['sum_realTotalMoney'] = "0";
        }
        if (is_null($goods[0]['count_orderId'])) {
            $goods[0]['count_orderId'] = 0;
        }
        $goods[0]['sum_realTotalMoney_true'] = 0; //去除补差价的金额
        /*$sql = " select orderId from __PREFIX__orders ".$where;
        $orderList = $this->query($sql);
        if(count($orderList) > 0){
            foreach ($orderList as $val){
                $orderId[] = $val['orderId'];
            }
            $gwhere['orderId'] = ['IN',$orderId];
            $countDiffPrice = M('goods_pricediffe')->where($gwhere)->sum('money'); //不区分是否已退,总归是要退的
            $goods[0]['sum_realTotalMoney_true'] = $goods[0]['sum_realTotalMoney'] - $countDiffPrice;
        }
        if($goods[0]['sum_realTotalMoney_true'] < 0){
            $goods[0]['sum_realTotalMoney_true'] = 0;
        }*/

        // --- 计算线上总利润 --- start ---
        $sql = "select if(g.SuppPriceDiff=-1,sum((og.goodsPrice-g.goodsUnit)*og.goodsNums),sum(((og.goodsPrice-g.goodsUnit)/g.weightG)*og.weight)) as total_profit from __PREFIX__order_goods as og left join __PREFIX__goods as g on og.goodsId = g.goodsId left join __PREFIX__orders as o on og.orderId = o.orderId " . $where_1;
        $xianshang_order_profit = $this->query($sql);
        // --- 计算线上总利润 --- end ---

        // --- 新添加了 线上总营业额、线上真实总营业额、线上总订单数、线下总营业额、线下真实总营业额、线下总订单数 --- @author liusijia 2019-11-16 14:02 --- start ---
        if (is_null($goods[0]['sum_realTotalMoney'])) $goods[0]['sum_realTotalMoney'] = 0;//线上总营业额
        if (is_null($goods[0]['sum_realTotalMoney_true'])) $goods[0]['sum_realTotalMoney_true'] = 0;//线上真实总营业额
        if (is_null($goods[0]['count_orderId'])) $goods[0]['count_orderId'] = 0;//线上总订单数
        $goods[0]['xianshang_count_orderId'] = $goods[0]['count_orderId'];//线上总订单数
        $goods[0]['xianshang_sum_realTotalMoney'] = $goods[0]['sum_realTotalMoney'];//线上总营业额
//        $goods[0]['xianshang_sum_realTotalMoney_true'] = $goods[0]['sum_realTotalMoney_true'];//线上真实总营业额
        $goods[0]['xianshang_sum_realTotalMoney_true'] = empty($xianshang_order_profit[0]['total_profit']) ? 0 : $xianshang_order_profit[0]['total_profit'];//线上订单利润

        $sql = "select sum(realpayment) as xianxia_sum_realTotalMoney,sum(realpayment) as xianxia_sum_realTotalMoney_true,count(*) as xianxia_count_orderId from __PREFIX__pos_orders where shopId = " . $parameter['shopId'];
        //统计线下利润
        $sql_1 = "select if(g.SuppPriceDiff=-1,sum((pog.presentPrice-g.goodsUnit)*pog.number),sum(((pog.presentPrice-g.goodsUnit)/g.weightG)*pog.weight)) as total_profit from __PREFIX__pos_orders_goods as pog left join __PREFIX__goods as g on pog.goodsId = g.goodsId left join __PREFIX__pos_orders as po on pog.orderid = po.id where po.shopId = " . $parameter['shopId'];
        if ($startTime && $endTime) {
            $sql .= "  and   addtime BETWEEN '{$startTime}'
            AND '{$endTime}'";
            $sql_1 .= "  and   po.addtime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }
        $pos_order_data = $this->query($sql);
        $xianxia_order_profit = $this->query($sql_1);
        if (is_null($pos_order_data[0]['xianxia_sum_realTotalMoney'])) $pos_order_data[0]['xianxia_sum_realTotalMoney'] = 0;//线下总营业额
        if (is_null($pos_order_data[0]['xianxia_sum_realTotalMoney_true'])) $pos_order_data[0]['xianxia_sum_realTotalMoney_true'] = 0;//线下真实总营业额
        if (is_null($pos_order_data[0]['xianxia_count_orderId'])) $pos_order_data[0]['xianxia_count_orderId'] = 0;//线下总订单数
        $goods[0]['xianxia_sum_realTotalMoney'] = $pos_order_data[0]['xianxia_sum_realTotalMoney'];//线下总营业额
//        $goods[0]['xianxia_sum_realTotalMoney_true'] = $pos_order_data[0]['xianxia_sum_realTotalMoney_true'];//线下真实总营业额
        $goods[0]['xianxia_count_orderId'] = $pos_order_data[0]['xianxia_count_orderId'];//线下总订单数
        $goods[0]['xianxia_sum_realTotalMoney_true'] = empty($xianxia_order_profit[0]['total_profit']) ? 0 : $xianxia_order_profit[0]['total_profit'];//线下订单总利润

        $goods[0]['count_orderId'] += $goods[0]['xianxia_count_orderId'];
        $goods[0]['sum_realTotalMoney'] += $goods[0]['xianxia_sum_realTotalMoney'];
//        $goods[0]['sum_realTotalMoney_true'] += $goods[0]['xianxia_sum_realTotalMoney_true'];
        $goods[0]['sum_realTotalMoney_true'] = $goods[0]['xianshang_sum_realTotalMoney_true'] + $goods[0]['xianxia_sum_realTotalMoney_true'];
//        $goods[0]['total_profit'] = $goods[0]['xianshang_order_profit'] + $goods[0]['xianxia_order_profit'];
        // --- 新添加了 线上总营业额、线上真实总营业额、线上总订单数、线下总营业额、线下真实总营业额、线下总订单数 --- @author liusijia 2019-11-16 14:02 --- end ---


        return $goods;
    }


    /**
     * 获取会员消费记录
     */
    public function getOrders($parameter)
    {
        $startDate = $parameter['startDate'];
        $endDate = $parameter['endDate'];
        $userName = $parameter['userName'];
        $userPhone = $parameter['userPhone'];
        $shopId = $parameter['shopId'];
        $orderStatus = I('orderStatus');
        if ($orderStatus === '') {
            $orderStatus = 20;
        }
        $pcurr = (int)I("pcurr", 0); //页码
        //where
        $where = " where o.orderFlag=1 and u.userFlag=1 ";
        if ($orderStatus != '20') {
            if ($orderStatus == 5) {
                $where .= " and o.orderStatus in (-3,-4,-5,-6,-7) ";
            } elseif ($orderStatus == 7) {
                $where .= " and o.orderStatus in (7,8,10) ";
            } elseif ($orderStatus == 8) {
                $where .= " and o.orderStatus in (13,14) ";
            } else {
                $where .= " AND o.orderStatus = $orderStatus ";
            }
        }

        if (!empty($startDate)) {
            $where .= " and o.createTime>='" . $startDate . "'";
        }
        if (!empty($endDate)) {
            $where .= " and o.createTime<='" . $endDate . "'";
        }

        if (!empty($userName)) {
            $where .= " and u.userName like '%" . $userName . "%' ";
        }
        if (!empty($userPhone)) {
            $where .= " and u.userUserPhone like '%" . $userPhone . "%' ";
        }
        if (!empty($shopId)) {
            $where .= " and o.shopId = '" . $shopId . "' ";
        }
        $sql = "select o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone from __PREFIX__orders o left join __PREFIX__users u on u.userId=o.userId " . $where;
        $sql .= " order by o.orderId desc ";
        $data = $this->pageQuery($sql, $pcurr);
        return $data;
    }

    /**
     * 获取各种订单状态下的商品(已去重)
     */
    public function getOrderUnquieGoods($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $orderTab = M('orders');
        $orderWhere['orderFlag'] = 1;
        $orderWhere['shopId'] = $param['shopId'];
        $orderStatus = $param['orderStatus'];
        if ($orderStatus != 20) {
            if ($orderStatus == 5) {
                $orderWhere['orderStatus'] = ['IN', "-3,-4,-5,-6,-7"];
            } elseif ($orderStatus == 7) {
                $orderWhere['orderStatus'] = ['IN', "7,8,10"];
            } elseif ($orderStatus == 8) {
                $orderWhere['orderStatus'] = ['IN', "13,14"];
            } else {
                $orderWhere['orderStatus'] = $orderStatus;
            }
        }

        $orderIdArr = $orderTab->where($orderWhere)->getField('orderId', true);
        $order_key_value_arr = $orderTab->where($orderWhere)->getField('orderId,orderNo');
        $orderGoodsTab = M('order_goods');
        $oWhere['orderId'] = ['IN', $orderIdArr];
        //订单商品去重
        $orderGoodsUnquie = $orderGoodsTab
            ->where($oWhere)
            ->group("goodsId")
            ->select();
        if (!empty($orderGoodsUnquie)) {
            foreach ($orderGoodsUnquie as $k => $v) {
                $orderGoodsUnquie[$k]['orderNo'] = $order_key_value_arr[$v['orderId']];
            }
        }
        //所有商品
        $orderGoodsList = $orderGoodsTab
            ->where($oWhere)
            ->select();
        foreach ($orderGoodsUnquie as $key => &$value) {
            $value['totalNums'] = 0;
            foreach ($orderGoodsList as $val) {
                if ($value['goodsId'] == $val['goodsId']) {
                    $value['totalNums'] += $val['goodsNums'];
                }
            }
        }
        unset($value);

        $goodsDataSort = array();
        foreach ($orderGoodsUnquie as $val) {
            $goodsDataSort[] = $val['totalNums'];
        }
        array_multisort($goodsDataSort, SORT_DESC, SORT_NUMERIC, $orderGoodsUnquie);//从低到高排序
        if ($orderGoodsUnquie) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $orderGoodsUnquie;
            return $apiRet;
        }
        return $apiRet;
    }

    /**
     * 补差价订单列表
     */
    public function getDiffMoneyOrders($obj)
    {
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $pcurr = (int)I("page", 0);
        $orderNo = WSTAddslashes(I("orderNo"));
        $userName = WSTAddslashes(I("userName"));
        $userPhone = WSTAddslashes(I("userPhone"));
        $isPay = I('isPay');
        $sql = "select o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,gd.isPay as isPayDiff from __PREFIX__goods_pricediffe gd left join __PREFIX__orders o on o.orderId=gd.orderId left join " . __PREFIX__users . " u on u.userId=o.userId " . " where o.shopId='" . $shopId . "' and gd.money>0 ";

        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND u.userName like '%$userName%'";
        }
        if ($userPhone != "") {
            $sql .= " AND u.userPhone like '%$userPhone%'";
        }
        if ($isPay != 20) {
            if (is_numeric($isPay)) {
                $sql .= " AND gd.isPay ='" . $isPay . "' ";
            }
        }

        $startDate = I('startDate');
        $endDate = I('endDate');
        if (!empty($startDate)) {
            $sql .= " AND o.createTime>='" . $startDate . "'";
        }
        if (!empty($endDate)) {
            $sql .= " AND o.createTime<='" . $endDate . "'";
        }
        $sql .= " AND o.orderFlag=1";
        $sql .= " group by o.orderId order by o.orderId desc ";
        $data = $this->pageQuery($sql, $pcurr);
        //获取取消/拒收原因
        $orderIds = array();
        $noReadrderIds = array();
        $config = $GLOBALS['CONFIG'];
        $goodsDiffTab = M('goods_pricediffe');
        foreach ($data['root'] as $key => $v) {
            $nowtime = time();
            $shopGoodPreSaleEndTimeInt = strtotime($v['ShopGoodPreSaleEndTime']);
            $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 0; //未过期
            if ($nowtime >= $shopGoodPreSaleEndTimeInt) {
                $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 1; //已过期
                $data['root'][$key]['shopGoodPreSaleDelivery'] = 0;
            } else {
                $data['root'][$key]['shopGoodPreSaleDelivery'] = timeDiff($shopGoodPreSaleEndTimeInt, $nowtime);
            }
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data['root'][$key]['isPay'] == 1) {
                    //$data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['deliverMoney'];
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                } else {
                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            } else {//处理常规非常规订单拆分后运费问题
                if ($data['root'][$key]['isPay'] != 1 && $data['root'][$key]['setDeliveryMoney'] > 0) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                    $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                }
            }
            $data['root'][$key]['weightG'] = 0;
            $orderGoods = M('order_goods')->where(['orderId' => $v['orderId']])->select();
            foreach ($orderGoods as $gv) {
                $goodsId[] = $gv['goodsId'];
            }
            $goodsWhere['goodsId'] = ['IN', $goodsId];
            $goods = M('goods')->where($goodsWhere)->field('SuppPriceDiff,goodsId')->select();
            $data['root'][$key]['returnAmount'] = 0;//补差价总金额
            $data['root'][$key]['returnAmountOK'] = 0;//已补差价金额
            $data['root'][$key]['returnAmountNO'] = 0;//未补差价金额
            foreach ($goods as $ggv) {
                if ($ggv['SuppPriceDiff'] == 1) {
                    $data['root'][$key]['weightG'] = 1;
                }
            }
            $where = [];
            $where['goodsId'] = ['IN', $goodsId];
            $where['orderId'] = $v['orderId'];
            $returnAmount = $goodsDiffTab->where($where)->sum('money');
            if ($returnAmount > 0) {
                //补差价总金额
                $data['root'][$key]['returnAmount'] = (float)$returnAmount;
            }
            $where = [];
            $where['goodsId'] = ['IN', $goodsId];
            $where['orderId'] = $v['orderId'];
            $where['isPay'] = 1;
            $returnAmountOK = $goodsDiffTab->where($where)->sum('money');
            $data['root'][$key]['returnAmountOK'] = (float)$returnAmountOK;
            $data['root'][$key]['returnAmountNO'] = bc_math($returnAmount, $returnAmountOK, 'bcsub', 2);
            if ($v['orderStatus'] == -6) $noReadrderIds[] = $v['orderId'];
            $sql = "select logContent from __PREFIX__log_orders where orderId =" . $v['orderId'] . " and logType=0 and logUserId=" . $v['userId'] . " order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
        }
        return $data;
    }

    /**
     * 获取补差价订单详情
     */
    public function getDiffMoneyOrdersDetail($obj)
    {
        $config = $GLOBALS['CONFIG'];
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=" . $userId . " or shopId=" . $shopId . ")";
        $order = $this->queryRow($sql);
        if (empty($order)) return $data;
        $sql = "select og.orderId,og.weight,og.goodsId ,g.goodsSn,g.SuppPriceDiff,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,og.skuId,og.skuSpecAttr
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        foreach ($goods as $key => $val) {
            $goods[$key]['personName'] = '';
            $goods[$key]['personMobile'] = '';
            $sql = "select per.userName,per.mobile,gr.startDate,gr.endDate from __PREFIX__sorting_goods_relation gr left join __PREFIX__sorting s on s.id=gr.sortingId left join __PREFIX__sortingpersonnel per on per.id=s.personId where s.sortingFlag=1 and gr.goodsId='" . $val['goodsId'] . "' and s.orderId='" . $val['orderId'] . "'";
            $info = $this->queryRow($sql);
            $goods[$key]['sortStartDate'] = 0;
            $goods[$key]['sortEndDate'] = 0;
            $goods[$key]['sortOverTime'] = 0;
            if ($info) {
                $goods[$key]['personName'] = $info['userName'];
                $goods[$key]['personMobile'] = $info['mobile'];
                $goods[$key]['sortStartDate'] = $info['startDate']; //分拣开始时间
                $goods[$key]['sortEndDate'] = $info['endDate'];//分拣结束时间
                if (is_null($goods[$key]['sortStartDate'])) {
                    $goods[$key]['sortStartDate'] = 0;
                }
                if (is_null($goods[$key]['sortEndDate'])) {
                    $goods[$key]['sortEndDate'] = 0;
                }
                $endDateInt = strtotime($info['endDate']);
                $startDateInt = strtotime($info['startDate']);
                $diffTime = timeDiff($endDateInt, $startDateInt);//耗时
                $goods[$key]['sortOverTime'] = $diffTime['day'] * 24 * 60 + $diffTime['hour'] * 60 + $diffTime['min'];
            }
            //商品是否有补差价
            $goods[$key]['diffMoney'] = "0.00";//补差价金额
            $goods[$key]['isPayDiff'] = 0;//是否已补(0:未补|1:已补)
//            if ($goods[$key]['SuppPriceDiff'] == 1) {
            //后改--这里去除只有称重才可以补差价的判断，因为在分拣里，标品也是可以补差价的
            $diffWhere = [];
            $diffWhere['orderId'] = $goods[$key]['orderId'];
            $diffWhere['goodsId'] = $goods[$key]['goodsId'];
            $goodsPricediffe = M('goods_pricediffe')->where($diffWhere)->find();
            $goods[$key]['SuppPriceDiff'] = -1;//是否补差价(-1:否|1:是)
            if ($goodsPricediffe) {
                $goods[$key]['SuppPriceDiff'] = 1;//只要存在补差价记录就是补差价商品
                $goods[$key]['diffMoney'] = $goodsPricediffe['money'];//补差价金额
                if ($goodsPricediffe['isPay'] == 1) {
                    $goods[$key]['isPayDiff'] = 1;
                    //$goods[$key]['diffMoney'] = $goodsPricediffe['money'];
                }
            } else {
                $goods[$key]['isPayDiff'] = 1;
            }
//            } else {
//                //非称重商品不补差价
//                $goods[$key]['isPayDiff'] = 1;
//            }
        }
        $order["goodsList"] = $goods;
        //发票信息
        $order['invoiceInfo'] = [];
        if (isset($order['isInvoice']) && $order['isInvoice'] == 1) {
            $order['invoiceInfo'] = M('invoice')->where("id='" . $order['invoiceClient'] . "'")->find();
        }
        //后加包邮起步价
        $order['deliveryFreeMoney'] = M('shops')->where("shopId")->getField('deliveryFreeMoney');
        $order['buyer'] = M('users')->where("userId='" . $order['userId'] . "'")->find();

        //优惠券金额
        $order['couponMoney'] = 0;
        $couponInfo = M('coupons')->where(['couponId' => $order['couponId']])->find();
        if ($couponInfo) {
            $order['couponMoney'] = $couponInfo['couponMoney'];
        }
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] == 1) {
                //$order['realTotalMoney'] = $order['realTotalMoney'] + $order['deliverMoney'];
                $order['realTotalMoney'] = $order['realTotalMoney'];
            } else {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {//处理常规非常规订单拆分后运费问题
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        return $order;
    }

    /**
     * @param $obj
     * @return mixed
     * 修改未补差价金额
     */
    public function updateDiffMoney($obj)
    {
        $orderId = $obj['orderId'];
        $goodsId = $obj['goodsId'];
        $money = $obj["money"];
        $goodsPricediffeModel = M('goods_pricediffe');
        $orderList = $goodsPricediffeModel->where(['orderId' => $orderId, 'isPay' => 0])->select();
        if (empty($orderList)) {
            return returnData(false, -1, 'error', '请确定当前订单是否已补差价');
        }
        $countGoods = count($orderList);
        if ($countGoods > 1 && empty($goodsId)) {
            return returnData(false, -1, 'error', '当前订单下有多件商品补差价,请选择商品');
        }
        if ($countGoods == 1) {
            $where = [];
            $where['orderId'] = $orderId;
            $where['isPay'] = 0;
            $where['id'] = $orderList[0]['id'];
            $orderInfo = $goodsPricediffeModel->where($where)->find();
            if ($orderInfo['money'] != $money) {
                $res = $goodsPricediffeModel->where($where)->save(['money' => $money]);
            } else {
                $res = 1;
            }
        } else {
            $where = [];
            $where['orderId'] = $orderId;
            $where['isPay'] = 0;
            $where['goodsId'] = $goodsId;
            $orderInfo = $goodsPricediffeModel->where($where)->find();
            if (empty($orderInfo)) {
                return returnData(false, -1, 'error', '请确定当前商品是否已补差价');
            }
            if ($orderInfo['money'] != $money) {
                $res = $goodsPricediffeModel->where($where)->save(['money' => $money]);
            } else {
                $res = 1;
            }
        }

        if ($res != 1) {
            return returnData(false, -1, 'error', '更改差价失败');
        }
        return returnData(true);
    }

    /**
     * 订单转化率统计
     * 默认显示一周
     * startDate:Y-m-d
     * endDate:Y-m-d
     */
    public function orderConversionRate($shopId)
    {
        $startDate = I('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = I('endDate', date('Y-m-d'));
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $diff = WSTCompareDate($startDate, $endDate);
        $diff = abs($diff);
        $data = array();
        $m = M("orders");
        for ($i = 0; $i <= $diff; $i++) {
            $date = date('Y-m-d', strtotime($startDate) + $i * 3600 * 24);
            $condition = array('createTime' => array('like', $date . '%'), 'orderFlag' => 1);
            $condition['shopId'] = $shopId;
            $total_order_count = $m->where($condition)->count();
            $total_order_count = empty($total_order_count) ? 0 : $total_order_count;

            $condition['orderStatus'] = 4;
            $complete_order_count = $m->where($condition)->count();
            $complete_order_count = empty($complete_order_count) ? 0 : $complete_order_count;

            $rate = $complete_order_count / $total_order_count;
            if ($rate == 1) {
                $data[$date] = '100.00';
            } else {
                $orderConversionRate = number_format($complete_order_count / $total_order_count, 2);
                $data[$date] = $orderConversionRate;
            }
        }
        return $data;
    }

    /**
     * PS:此防范变动也要更改对应的handleOrderCode方法
     * 获取订单条件
     * @param array $handleOrderWhere
     * */
    public function getOrderWhereStr($handleOrderWhere)
    {
        $where = " o.orderFlag=1 ";
        foreach ($handleOrderWhere as $key => $value) {
            //订单状态
            if ($key == 'orderStatuCode' && !empty($value)) {
                //未支付
                if ($value == 'noPay') {
                    $where .= " and o.isPay=0 ";
                }
                //待审额 PS:其实就是原来的已支付
                if ($value == 'pendingApproval') {
                    $where .= " and o.orderStaus=0 ";
                }
                //待打包
                if ($value == 'unPacked') {
                    $where .= " and o.orderStaus=1 ";
                }
                //待配送
                if ($value == 'waitingDelivery') {
                    $where .= " and o.orderStaus=2 ";
                }
                //自提待取货
                if ($value == 'selfPickUp') {
                    $where .= " and o.orderStaus=3 and o.isSelf=1 ";
                }
                //自提已取货
                if ($value == 'selfPickedUp') {
                    $where .= " and o.orderStaus=4 and o.isSelf=1 ";
                }
                //已取消
                if ($value == 'Cancelled') {
                    // $where .= " and o.orderStaus IN(-7,-6,-1,9)";
                    $where .= " and o.orderStaus IN(-7,-6,-1,-8,-2,9,10,11)";
                }
                //非自提骑手待接单
                if ($value == 'noSelfRiderWaitingOrder') {
                    $where .= " and o.orderStaus=7";
                }
                //骑手已接单
                if ($value == 'riderReceivedOrder') {
                    $where .= " and o.orderStaus=8 ";
                }
                //配送中
                if ($value == 'delivery') {
                    $where .= " and o.orderStaus=3 ";
                }
                //已交货
                if ($value == 'delivered') {
                    $where .= " and o.orderStaus=4 ";
                }
            }

            //订单类型
            if ($key == 'orderType') {
                //普通订单
                if ($value == 'ordinary') {
                    $where .= " and o.orderType=1 ";
                }
                //拼团订单
                if ($value == 'assemble') {
                    $where .= " and o.orderType=2 ";
                }
                //预售订单
                if ($value == 'advanceSale') {
                    $where .= " and o.orderType=3 ";
                }
                //秒杀订单
                if ($value == 'seckill') {
                    $where .= " and o.orderType=4 ";
                }
                //非常规订单
                if ($value == 'unconventional') {
                    $where .= " and o.orderType=5 ";
                }
            }

            //支付方式
            if ($key == 'payType') {
                //微信支付
                if ($value == 'wxPay') {
                    $where .= " and o.payFrom=2 ";
                }
                //支付宝支付
                if ($value == 'aliPay') {
                    $where .= " and o.payFrom=1 ";
                }
                //余额支付
                if ($value == 'balancePay') {
                    $where .= " and o.payFrom=3 ";
                }
                //货到付款
                if ($value == 'cashOnDelivery') {
                    $where .= " and o.payFrom=4 ";
                }
            }
            //配送方式
            if ($key == 'deliveryType') {
                //自提
                if ($value == 'selfDelivery') {
                    $where .= " and o.isSelf=1 ";
                }
                //门店配送
                if ($value == 'shopDelivery') {
                    $where .= " and o.deliverType IN(0,1) ";
                }
                //骑手配送
                if ($value == 'riderDelivery') {
                    $where .= " and o.deliverType IN(2,3,4) ";
                }
            }
            //创建时间
            if ($key == 'createTime') {
                $date = getDateRules($value);
                $where .= " and o.createTime between '{$date['startDate']}' and '{$date['endDate']}' ";
            }
        }
        return $where;
    }

    /**
     * 为了返回的状态和前端传过来的搜索状态统一
     * PS:此方法变动也要更改对应的getOrderWhereStr方法
     * 处理订单的状态
     * @param array $orderList 订单商品数据
     * */
    public function handleOrderCode($orderList)
    {
        if (empty($orderList)) {
            return [];
        }
        foreach ($orderList as $key => $value) {
            //订单支付状态
            $orderList[$key]['orderStatuCode'] = '';
            $orderList[$key]['orderType'] = 'delivered';
            $orderList[$key]['payType'] = '';
            $orderList[$key]['deliveryType'] = '';
            if ($value['isPay'] == 0) {//未支付
                $orderList[$key]['orderStatuCode'] = 'noPay';
            }
            if ($value['orderStatus'] == 0) {//待审额
                $orderList[$key]['orderStatuCode'] = 'pendingApproval';
            }
            if ($value['orderStatus'] == 1) {//待打包
                $orderList[$key]['orderStatuCode'] = 'unPacked';
            }
            if ($value['orderStatus'] == 2) {//待配送
                $orderList[$key]['orderStatuCode'] = 'waitingDelivery';
            }
            if ($value['orderStatus'] == 3 && $value['isSelf'] == 1) {//自提待取货
                $orderList[$key]['orderStatuCode'] = 'selfPickUp';
            }
            if ($value['orderStatus'] == 4 && $value['isSelf'] == 1) {//自提已取货
                $orderList[$key]['orderStatuCode'] = 'selfPickedUp';
            }
            if (in_array($value['orderStatus'], [-7, -6, -1, 9])) {//已取消
                $orderList[$key]['orderStatuCode'] = 'Cancelled';
            }
            if ($value['orderStatus'] == 7) {//非自提骑手待接单
                $orderList[$key]['orderStatuCode'] = 'noSelfRiderWaitingOrder';
            }
            if ($value['orderStatus'] == 8) {//骑手已接单
                $orderList[$key]['orderStatuCode'] = 'riderReceivedOrder';
            }
            if ($value['orderStatus'] == 3) {//配送中
                $orderList[$key]['orderStatuCode'] = 'delivery';
            }
            if ($value['orderStatus'] == 4) {//已交货
                $orderList[$key]['orderStatuCode'] = 'delivered';
            }
            //订单类型
            if ($value['orderType'] == 1) {//普通订单
                $orderList[$key]['orderType'] = 'delivered';
            }
            if ($value['orderType'] == 2) {//拼团订单
                $orderList[$key]['orderType'] = 'assemble';
            }
            if ($value['orderType'] == 3) {//预售订单
                $orderList[$key]['orderType'] = 'advanceSale';
            }
            if ($value['orderType'] == 4) {//秒杀订单
                $orderList[$key]['orderType'] = 'seckill';
            }
            if ($value['orderType'] == 5) {
                //unconventional
                $orderList[$key]['orderType'] = 'unconventional';
            }
            //支付方式
            if ($value['payFrom'] == 1) {//支付宝支付
                $orderList[$key]['payType'] = 'payFrom';
            }
            if ($value['payFrom'] == 2) {//微信支付
                $orderList[$key]['payType'] = 'wxPay';
            }
            if ($value['payFrom'] == 3) {//余额支付
                $orderList[$key]['payType'] = 'balancePay';
            }
            if ($value['payFrom'] == 4) {//货到付款
                $orderList[$key]['payType'] = 'cashOnDelivery';
            }
            //配送方式
            if ($value['isSelf'] == 1) {//自提
                $orderList[$key]['deliveryType'] = 'selfDelivery';
            }
            if (in_array($value['deliverType'], [0, 1])) {//门店配送
                $orderList[$key]['deliveryType'] = 'shopDelivery';
            }
            if (in_array($value['deliverType'], [2, 3, 4])) {//骑手配送
                $orderList[$key]['deliveryType'] = 'riderDelivery';
            }
        }
        return $orderList;
    }

    /**
     * 订单列表
     * @param int $shopId
     * @param varchar $orderStatusCode 订单状态【noPay:未支付|pendingApproval:待审核|unPacked:待打包|waitingDelivery:待配送|selfPickUp:自提待取货|selfPickedUp:自提已取货|Cancelled:已取消|noSelfRiderWaitingOrder:非自提骑手待接单|riderReceivedOrder:骑手已接单|delivery:配送中|delivered:已交货】
     * @param varchar $orderType 订单类型【ordinary:普通订单|assemble:拼团订单|advanceSale:预售订单|seckill:秒杀订单】
     * @param varchar $payType 支付方式【wxPay:微信支付|aliPay:支付宝支付|balancePay:余额支付|货到付款:cashOnDelivery】
     * @param varchar $deliveryType 配送方式【selfDelivery:自提|riderDelivery:骑手配送|shopDelivery:门店配送】
     * @param varchar $createTime 创建时间【today:今天|yesterday:昨天|lastSevenDays:最近7天|lastThirtyDays:最近30天|thisMonth:本月|thisYear:本年|customDate:自定义(例子:2020-05-01 - 2020-05-31)】
     * @param varchar keywords 订单号(用户姓名|电话|订单编号)
     * @param bool export 导出(true:是|false:否)
     */
    public function getOrderList($shopId, $orderStatuCode, $orderType, $payType, $deliveryType, $createTime, $keywords, $page, $pageSize, $export)
    {
        $handleOrderWhere = [];
        $handleOrderWhere['orderStatuCode'] = $orderStatuCode;
        $handleOrderWhere['orderType'] = $orderType;
        $handleOrderWhere['payType'] = $payType;
        $handleOrderWhere['deliveryType'] = $deliveryType;
        $handleOrderWhere['createTime'] = $createTime;
        $where = $this->getOrderWhereStr($handleOrderWhere);
        $where .= " and o.shopId=$shopId ";
        if (!empty($keywords)) {
            $where .= " and (o.orderNo like '%{$keywords}%' or u.userName like '%{$keywords}%' or u.userPhone like '%{$keywords}%') ";
        }
        $field = "o.orderId,o.orderNo,o.orderStatus,o.realTotalMoney,o.payFrom,o.createTime,o.orderType,o.isPay,o.deliverMoney,o.userAddress,o.orderRemarks";
        $field .= ",u.userPhone,u.userName";
        $sql = "select $field from __PREFIX__orders o ";
        $sql .= " left join __PREFIX__users u on u.userId=o.userId ";
        $sql .= " where $where ";
        $sql .= " order by o.orderId desc ";
        if ($export == 'true') {
            //导出
            $res = [];
            $res['root'] = $this->query($sql);
        } else {
            $res = $this->pageQuery($sql, $page, $pageSize);
            if (empty($res['root'])) {
                return $res;
            }
        }
        $orderList = $this->handleOrderCode($res['root']);
        $orderGoodsTab = M('order_goods');
        $config = $GLOBALS['CONFIG'];
        foreach ($orderList as $key => $value) {
            $orderList[$key]['goodsNameStr'] = '';
            $orderList[$key]['orderGoods'] = [];
            $orderGoods = $orderGoodsTab->where(['orderId' => $value['orderId']])->select();
            if (!empty($orderGoods)) {
                foreach ($orderGoods as $okey => $oval) {
                    $orderList[$key]['goodsNameStr'] .= $oval['goodsName'] . '/';
                }
                $orderList[$key]['goodsNameStr'] = rtrim($orderList[$key]['goodsNameStr'], '/');
            }
            $orderList[$key]['orderGoods'] = $orderGoods;
            //处理统一运费
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($orderList[$key]['isPay'] == 1) {
                    //$orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'] + $orderList[$key]['deliverMoney'];
                    $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'];
                } else {
                    if ($orderList[$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'] + $orderList[$key]['setDeliveryMoney'];
                        $orderList['deliverMoney'] = $orderList[$key]['setDeliveryMoney'];
                    }
                }
            } else {//处理常规非常规订单拆分后运费问题
                if ($orderList[$key]['isPay'] != 1 && $orderList[$key]['setDeliveryMoney'] > 0) {
                    $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'] + $orderList[$key]['setDeliveryMoney'];
                    $orderList['deliverMoney'] = $orderList[$key]['setDeliveryMoney'];
                }
            }
        }
        $res['root'] = $orderList;
        if ($export == 'true') {
            $this->exportOrders($res['root'], I());
        }
        return $res;
    }

    /**
     * 导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrders(array $orderList, array $params)
    {
        //处理订单商品信息
        foreach ($orderList as $key => $value) {
            $rowspan = 0;//下面的导出表格会用到
            $orderGoods = $value['orderGoods'];
            $goodsList = [];
            foreach ($value['orderGoods'] as $gval) {
                $goodsInfo = [];
                $goodsInfo['goodsId'] = $gval['goodsId'];
                $goodsInfo['goodsName'] = $gval['goodsName'];
                $goodsInfo['goodsPrice'] = $gval['goodsPrice'];
                $goodsInfo['goodsNums'] = 0;
                $goodsInfo['remarks'] = $gval['remarks'];
                $goodsList[] = $goodsInfo;
            }
            $unquieGoods = arrayUnset($goodsList, 'goodsId');
            foreach ($unquieGoods as $uKey => $uVal) {
                $skulist = [];
                foreach ($orderGoods as $oVal) {
                    if ($oVal['goodsId'] == $uVal['goodsId']) {
                        $rowspan += 1;
                        $unquieGoods[$uKey]['goodsNums'] += $oVal['goodsNums'];
                        if (!empty($oVal['skuId'])) {
                            $skuInfo = [];
                            $skuInfo['skuId'] = $oVal['skuId'];
                            $skuInfo['goodsNums'] = $oVal['goodsNums'];
                            $skuInfo['goodsPrice'] = $oVal['goodsPrice'];
                            $skuInfo['goodsAttrName'] = $oVal['skuSpecAttr'];
                            $skuInfo['remarks'] = $oVal['remarks'];
                            $skulist[] = $skuInfo;
                        }
                    }
                }
                $unquieGoods[$uKey]['skulist'] = $skulist;
            }
            $orderList[$key]['orderGoods'] = $unquieGoods;
            $orderList[$key]['rowspan'] = count($unquieGoods);
        }

        //拼接表格信息
        $dateRules = getDateRules(I('createTime'));
        $date = '';
        $startDate = $dateRules['startDate'];
        $endDate = $dateRules['endDate'];
        if (!empty($startDate) && !empty($endDate)) {
            $date = $startDate . ' - ' . $endDate;
        }
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:100px;'>订单号</th>
                <th style='width:200px;'>收货人信息</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:50px;'>小计</th>
                <th style='width:50px;'>运费</th>
                <th style='width:80px;'>实付金额</th>
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>下单时间</th>
            </tr>";
        $num = 0;
        foreach ($orderList as $okey => $ovalue) {
            $orderGoods = $ovalue['orderGoods'];
            $rowspan = $ovalue['rowspan'];
            $key = $okey + 1;
            $userDetailAddress = '';
            $userDetailAddress .= '用户名：' . $ovalue['userName'] . '<br>';
            $userDetailAddress .= '电话：' . $ovalue['userPhone'] . '<br>';
            $userDetailAddress .= '收货地址：' . $ovalue['userAddress'] . '<br>';
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                /*if(!empty($gVal['skulist'])){
                     $rowspan = count($gVal['skulist']);
                 }*/
                $num++;
                $goodsNums = "<td style='width:30px;'>" . $gVal['goodsNums'] . "</td>";//数量;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }

                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['goodsAttrName'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                                    "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    /*$headTitle = "订单数据";
                    $filename = $headTitle . ".xls";
                    usePublicExport($body,$headTitle,$filename,$date);*/
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                                "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                                "</tr>";
                        }
                    }
                }
            }

        }
        $headTitle = "订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 订单审核/拒绝
     * @param int $shopId
     * @param int $dataType 场景(1:审核通过|2:审核拒绝) PS:默认为审核通过
     * $param varchar $orderIds 订单id,多个订单id可用英文逗号
     * @param varchar $shopCancellationReason 拒绝原因 PS:拒绝操作必填字段
     */
    public function batchExamineShopOrders($shopId, $orderIds, $dataType, $shopCancellationReason)
    {
        $orderIds = explode(',', $orderIds);
        //审核通过(其实就是受理)
        if ($dataType == 1) {
            $res = $this->auditSuccessful($shopId, $orderIds);
        }
        //审核拒绝
        if ($dataType == 2) {
            $res = $this->auditRejection($shopId, $orderIds, $shopCancellationReason);
        }
        return $res;
    }

    /**
     * 订单审核->审核通过(受理成功)
     * @param int $shopId
     * @param varchar $orderIds 订单id信息
     * */
    public function auditSuccessful($shopId, $orderIds)
    {
        foreach ($orderIds as $orderId) {
            if ($orderId == '') continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=" . $shopId;
            $rsv = $this->queryRow($sql);
            $sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderId = $orderId and shopId=" . $shopId;
            //订单状态不符合则跳过 未受理或预售订单-已付款
            if (!in_array($rsv['orderStatus'], [0, 14])) {
                continue;
            }
            $rs = $this->execute($sql);
            if ($rs) {
//                $data = array();
//                $m = M('log_orders');
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "平台审核通过";
//                $data["logUserId"] = $shopId;
//                $data["logType"] = 1;//【0:用户|1:商家平台|2:系统】
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $m->add($data);
                $content = '平台审核通过';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 1,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
        }
        return returnData(true);
    }

    /**
     * 审核拒绝
     * @param int $shopId
     * @param varchar $orderIds 订单id
     * @param varchar $shopCancellationReason 拒绝原因 PS:拒绝操作必填字段
     * */
    public function auditRejection($shopId, $orderIds, $shopCancellationReason)
    {
        foreach ($orderIds as $orderId) {
            if ($orderId == '') continue;//订单号为空则跳过
            $sql = "SELECT orderId,userId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=" . $shopId;
            $rsv = $this->queryRow($sql);
            //审核拒绝
            if ($rsv['orderStatus'] != 0) {
                return returnData(false, -1, 'error', '只有待审核订单才能执行该操作');
            }
            $cancelRes = $this->cancelOrderOK($rsv['userId'], $orderId, $shopId);
            if ($cancelRes['code'] == -1) {
                return returnData(false, -1, 'error', '取消失败，微信退款失败');
            }
            $sql = "UPDATE __PREFIX__orders set orderStatus = -1,shopCancellationReason='{$shopCancellationReason}' WHERE orderId = $orderId and shopId=" . $shopId;
            $rs = $this->execute($sql);
            if ($rs) {
//                $data = array();
//                $m = M('log_orders');
//                $data["orderId"] = $orderId;
//                $data["logContent"] = "平台审核拒绝";
//                $data["logUserId"] = $shopId;
//                $data["logType"] = 1;//【0:用户|1:商家平台|2:系统】
//                $data["logTime"] = date('Y-m-d H:i:s');
//                $m->add($data);
                $content = '平台审核拒绝';
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => 0,
                    'logUserName' => '系统',
                    'orderStatus' => 1,
                    'payStatus' => 1,
                    'logType' => 2,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
        }
        return returnData($rs);
    }

    /**
     * PS:直接复制之前的用户取消订单操作
     * 商户平台取消订单
     * @param int $userId
     * @param int $orderId 订单id
     * @param int $shopId 店铺id
     * */
//    public function cancelOrderOK($userId, $orderId, $shopId)
//    {
//        $where["userId"] = $userId;
//        $where["userFlag"] = 1;
//        //$where1['payType'] = 1;
//        $where1['isPay'] = 1;
//        $where1['userId'] = $userId;
//        $where1['orderFlag'] = 1;
//        $where1['orderStatus'] = array('in', '0,1,2');
//        $where1['orderId'] = $orderId;
//        $orders = M("orders");
//        $order_goods = M("order_goods");
//        $goods = M("goods");//实例化商品表 为改变库存做准备
//        //判断订单是否已经退款
//        $orderInfo = $orders->where("orderId = {$orderId} and isRefund = 0")->find();
//        if (!$orderInfo) {
//            $statusCode = returnData(null, -1, 'error', '订单已经退款了');
//            return $statusCode;
//        }
//        if ($orderInfo['orderStatus'] >= 1) {
//            $statusCode = returnData(null, -1, 'error', '已受理订单不能取消，请联系商家');
//            return $statusCode;
//        }
//        M()->startTrans();//开启事物
//        $data["orderStatus"] = '-6';
//        $hyh_state = $orders->where($where1)->save($data);//把订单状态改为用户取消(发货前 商家未读)
//        if (!$hyh_state) {
//            M()->rollback();
////            $statusCode["statusCode"] = "000061";
//            $statusCode = returnData(null, -1, 'error', '取消失败 或者您已经取消');
//            return $statusCode;
//        }
//        $where3["orderId"] = $orderId;
//        $order_goods_save = $order_goods->where($where3)->field(array("goodsId", "goodsNums", "goodsAttrId"))->select();//订单下的商品
//        for ($i = 0; $i < count($order_goods_save); $i++) {//循环修改每个商品的总库存goodsStock
//            $where4["goodsId"] = $order_goods_save[$i]['goodsId'];
//            $order_goods_save_i_goodsNums = gChangeKg($order_goods_save[$i]['goodsId'], $order_goods_save[$i]['goodsNums'], 1);
//            $goods->where($where4)->setInc('goodsStock', $order_goods_save_i_goodsNums);//加商品库存
//            //更新进销存系统商品的库存
//            //updateJXCGoodsStock($order_goods_save[$i]['goodsId'], $order_goods_save[$i]['goodsNums'], 0);
//            /*
//             * 2019-06-15 start
//             * 加回商品属性库存
//             * */
//            /*if($order_goods_save[$i]['goodsAttrId'] > 0){
//                M('goods_attributes')->where("id='".$order_goods_save[$i]['goodsAttrId']."'")->setInc('attrStock',$order_goods_save_i_goodsNums);
//            }*/
//            /*
//             * 2019-06-15 end
//             * */
//        }
//        //-----------------启动退款逻辑------------------------
//        if ($orderInfo['payFrom'] == 1) {
//            //支付宝
//        } elseif ($orderInfo['payFrom'] == 2) {
//            //微信
//            $cancelRes = order_WxPayRefund($orderInfo['tradeNo'], $orderId, $orderInfo['orderStatus'], 2);//可整单退款
//            if ($cancelRes == -3) {
//                M()->rollback();
//                return returnData(null, -1, 'error', '取消失败，微信退款失败');
//            }
//        } else {
//            //余额
//            //更改订单为已退款  //------可增加事物
//            $orders = M('orders');
//            $save_orders['isRefund'] = 1;
//            $orderEdit = $orders->where("orderId = " . $orderInfo['orderId'])->save($save_orders);
//            if ($orderEdit) {
//                //加回用户余额
//                //$userEditRes = M('users')->where("userId='".$userId."'")->setInc('balance',$orderInfo['needPay']);
//                $balance = M('users')->where(['userId' => $orderInfo['userId']])->getField('balance');
//                $balance += $orderInfo['realTotalMoney'];
//                $refundAmount = $orderInfo['realTotalMoney'];
//                if (in_array($orderInfo['orderStatus'], [0, 13, 14])) {
//                    $balance += $orderInfo['deliverMoney'];
//                    $refundAmount += $orderInfo['deliverMoney'];
//                }
//                $userEditRes = M('users')->where(['userId' => $orderInfo['userId']])->save(['balance' => $balance]);
//                if ($userEditRes) {
//                    //写入订单日志
//                    unset($data);
////                    $log_orders = M("log_orders");
////                    $data["orderId"] = $orderId;
////                    $data["logContent"] = "平台审核拒绝，发起余额退款：" . $refundAmount . '元';
////                    $data["logUserId"] = $shopId;
////                    $data["logType"] = "0";
////                    $data["logTime"] = date("Y-m-d H:i:s");
////                    $log_orders->add($data);
//                    $content = "平台审核拒绝，发起余额退款：" . $refundAmount . '元';
//                    $logParams = [
//                        'orderId' => $orderId,
//                        'logContent' => $content,
//                        'logUserId' => 0,
//                        'logUserName' => '系统',
//                        'orderStatus' => 1,
//                        'payStatus' => 1,
//                        'logType' => 2,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                }
//            }
//        }
//        //优惠券返还
//        $cres = cancelUserCoupon($orderId);
//        if (!$cres) {
//            M()->rollback();
////            $statusCode["statusCode"] = "000062";
//            $statusCode["statusInfo"] = "确认订单成功";
//            $statusCode = returnData($statusCode);
//            return $statusCode;
//        }
//        //END
//        //返还属性库存
//        returnAttrStock($orderId);
//        //退换已使用的积分----------------------------------
//        returnIntegral($orderId, $userId);
//        //返回秒杀库存
//        returnKillStock($orderId);
//        //收回奖励积分---------------------------------未确认收货是没有奖励积分过去的
//        //收回分销金
//        returnDistributionMoney($orderId);
//        //更新商品限制购买记录表
//        updateGoodsOrderNumLimit($orderId);
//        //返还sku库存
//        returnGoodsSkuStock($orderId);
//
//        M()->commit();
//        /*发起退款通知*/
//        $push = D('Adminapi/Push');
//        $push->postMessage(8, $userId, $orderInfo['orderNo'], null);
////        $statusCode["statusCode"] = "000060";
//        $statusCode["statusInfo"] = "取消成功";
//        $statusCode = returnData($statusCode);
//        return $statusCode;
//    }

    /**
     * 补差价退款
     * @param int orderId 订单id
     * @param decimal amount 金额
     * @param int type 打款类型【1：手动打款|2：系统自动打款】
     * */
    public function returnGoodsDiffAmount($loginUserInfo, $orderId, $amount, $type)
    {
        $orderTab = M('orders');
        $where = [];
        $where['orderId'] = $orderId;
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        $where['orderStatus'] = 4;
        $orderInfo = $orderTab->where($where)->find();
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '用户确认收货后才能补差价');
        }
        $where = [];
        $where['orders.orderId'] = $orderId;
        $where['diff.money'] = ['GT', 0];
        $where['diff.isPay'] = 0;
        $field = 'orders.orderId,orders.payFrom,orders.orderNo,orders.userId,orders.tradeNo,orders.realTotalMoney';
        $field .= ',diff.id,diff.goodsId,diff.skuId,diff.money,diff.addTime,diff.isPay';
        $data = M('goods_pricediffe diff')
            ->join('left join wst_orders orders on orders.orderId=diff.orderId')
            ->where($where)
            ->field($field)
            ->group('diff.id')
            ->select();
        if (empty($data)) {
            return returnData(false, -1, 'error', '暂无可补差价的商品');
        }
        $payModule = new PayModule();
        foreach ($data as $key => $value) {
            $pay_transaction_id = $value['tradeNo'];
            $pay_total_fee = $value['realTotalMoney'] * 100;
            $pay_refund_fee = $value['money'] * 100;
            //$pay_refund_fee = $amount * 100;
            $orderId = $value['orderId'];
            $goodsId = $value['goodsId'];
            $skuId = $value['skuId'];
            $userId = $value['userId'];
            $logContent = '';
            if ($value['payFrom'] == 2) {
                if ($type == 1) {
                    //手动打款
                    //更改退款记录
                    $save_data = [];
                    $save_data['isPay'] = 1;
                    $save_data['payTime'] = date('Y-m-d H:i:s');
                    $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId} and skuId={$skuId}")->save($save_data);
                    if (!$diffRes) {
                        return returnData(false, -1, 'error', '补差价操作失败');
                    }
//                    $logContent = "手动补差价退款：" . $value['money'] . '元';
//                    //写入订单日志
//                    $log_orders = M("log_orders");
//                    $data = [];
//                    $data["orderId"] = $orderId;
//                    $data["logContent"] = $logContent;
//                    $data["logUserId"] = $userId;
//                    $data["logType"] = "0";
//                    $data["logTime"] = date("Y-m-d H:i:s");
//                    $log_orders->add($data);
                    $content = "手动补差价退款：" . $value['money'] . '元';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $loginUserInfo['user_id'],
                        'logUserName' => $loginUserInfo['user_name'],
                        'orderStatus' => $orderInfo['orderStatus'],
                        'payStatus' => 1,
                        'logType' => 1,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                } else {
                    //系统打款
                    //微信补差价退款
                    $repay = wxRefundGoods($pay_transaction_id, $pay_total_fee, $pay_refund_fee, $orderId, $goodsId, $skuId, 2, []);
                    if ($repay !== true) {
                        return returnData(false, -1, 'error', '补差价操作失败，已退款或退款余额不足');
                    }
                }
            } elseif ($value['payFrom'] == 3) {
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                if ($type == 1) {
                    $logContent = "手动补差价退款：" . $refundFee . '元';
                } else {
                    //余额补差价退款
                    $userBalance = M('users')->where(['userId' => $userId])->getField('balance');
                    $saveData = [];
                    $saveData['balance'] = $userBalance + $refundFee;
                    $refundRes = M('users')->where(['userId' => $userId])->save($saveData);
                    if ($refundRes == false) {
                        return returnData(false, -1, 'error', '补差价操作失败');
                    }
                    $logContent = "发起余额退款：" . $refundFee . '元';
                }
                //更改退款记录
                $save_data = [];
                $save_data['isPay'] = 1;
                $save_data['payTime'] = date('Y-m-d H:i:s');
                $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId} and skuId={$skuId}")->save($save_data);
                if (!$diffRes) {
                    return returnData(false, -1, 'error', '补差价操作失败');
                }
            } elseif ($value['payFrom'] == 1) {
                //支付宝
                //临时修复,原有逻辑直接复制过来
                $refundFee = $pay_refund_fee / 100;//需要退款的金额
                if ($type == 1) {
                    //手动打款
                    //更改退款记录
                    $save_data = [];
                    $save_data['isPay'] = 1;
                    $save_data['payTime'] = date('Y-m-d H:i:s');
                    $diffRes = M('goods_pricediffe')->where("orderId ={$orderId} and goodsId = {$goodsId} and userId= {$userId} and skuId={$skuId}")->save($save_data);
                    if (!$diffRes) {
                        return returnData(false, -1, 'error', '补差价操作失败');
                    }
                    $content = "手动补差价退款：" . $value['money'] . '元';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $loginUserInfo['user_id'],
                        'logUserName' => $loginUserInfo['user_name'],
                        'orderStatus' => $orderInfo['orderStatus'],
                        'payStatus' => 1,
                        'logType' => 1,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                } else {
                    //系统打款
                    $aliPayRefundRes = $payModule->aliPayRefund($orderInfo['tradeNo'], $refundFee);
                    if ($aliPayRefundRes['code'] != 0) {
                        return returnData(null, -1, 'error', '补差价操作失败');
                    }
                    $logContent = "发起支付宝退款：" . $refundFee . '元';
                }
            }
            if ($value['payFrom'] != 2) {
                //写入订单日志
//                $log_orders = M("log_orders");
//                $data = [];
//                $data["orderId"] = $orderId;
//                $data["logContent"] = $logContent;
//                $data["logUserId"] = $userId;
//                $data["logType"] = "0";
//                $data["logTime"] = date("Y-m-d H:i:s");
//                $log_orders->add($data);
                $content = $logContent;
                $logParams = [
                    'orderId' => $orderId,
                    'logContent' => $content,
                    'logUserId' => $loginUserInfo['user_id'],
                    'logUserName' => $loginUserInfo['user_name'],
                    'orderStatus' => $orderInfo['orderStatus'],
                    'payStatus' => 1,
                    'logType' => 1,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
        }
        return returnData(true);
    }

    /*
     * 自提订单-提货
     * @param array $shopInfo
     * @param int $orderId
     * @param string $source
     * */
    public function selfOrderAction(array $shopInfo, int $orderId, string $source)
    {
        $where = [];
        $where['orders.orderFlag'] = 1;
        $where['orders.shopId'] = $shopInfo['shopId'];
        $where['orders.orderId'] = $orderId;
        $field = 'orders.*,self.id,self.source,self.onStart';
        $orderInfo = M('orders orders')
            ->join("left join wst_user_self_goods self on self.orderId=orders.orderId")
            ->where($where)
            ->field($field)
            ->find();
        if ($orderInfo['source'] != $source) {
            return returnData(false, -1, 'error', '提货码不正确');
        }
        if ($orderInfo['onStart'] != 0) {
            return returnData(false, -1, 'error', '已提货订单不能重复提货');
        }
//        $user_detail = (new UsersModule())->getUsersDetailById($orderInfo['userId'], 'userId,loginName', 2);
        $logParams = [
            'orderId' => $orderId,
            'logContent' => '用户已自提',
            'logUserId' => $shopInfo['user_id'],
            'logUserName' => (string)$shopInfo['user_username'],
            'orderStatus' => 4,
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
//        $res = ConfirmReceipt($user_detail['loginName'], $orderId, 1, '', $logParams);
//        if ($res['statusCode'] != "000062") {
//            return returnData(false, -1, 'error', "提货失败");
//        }
        $orderModule = new OrdersModule();
        $res = $orderModule->confirmReceipt($orderId, 1, '', $logParams);
        if ($res['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, -1, 'error', "提货失败");
        }

        //改为已取货
        $where = [];
        $where['id'] = $orderInfo['id'];
        $saveData['onStart'] = 1;
        $saveData['onTime'] = date('Y-m-d H:i:s');
        $res = M('user_self_goods')->where($where)->save($saveData);
        if (!$res) {
            return returnData(false, -1, 'error', '提货失败');
        }
//        $where = [];
//        $where['orderId'] = $orderId;
//        $save = [];
//        $save['orderStatus'] = 4;
//        $save['receiveTime'] = date('Y-m-d H:i:s');
//        $saveOrderRes = M('orders')->where($where)->save($save);
//        if (!$saveOrderRes) {
//            return returnData(false, -1, 'error', '提货失败-状态更改失败');
//        }
//        M('log_orders')->add($logParams);
//        $order_service_module = new \App\Modules\Orders\OrdersServiceModule();
//        $order_service_module->IncOrderGoodsSale($orderId);//增加商品销量
        return returnData(true);
    }

    /**
     * 系统首页-用户总览
     * @param array $loginUserInfo 当前登陆者信息
     * @return array $data
     * */
    public function getShopUserOverview(array $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');

        $wst_users = M("users");


        $datetime = getDateRules('today');//今日新增
        $where = [];
        $where['userFlag'] = 1;
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $count_today = $wst_users->where($where)->count();


        // $where = [];
        // $where['shopId'] = $shopId;
        // $where['orderFlag'] = 1;
        // $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        // $orderUserList = $orderTab
        //     ->where($where)
        //     ->field('userId')
        //     ->group('userId')
        //     ->select();
        // foreach ($orderUserList as $key => $item) {
        //     //如果该用户不属于新用户则剔除
        //     $date = $datetime['startDate'];
        //     $where = [];
        //     $where['shopId'] = $shopId;
        //     $where['userId'] = $item['userId'];
        //     $where['orderFlag'] = 1;
        //     $where['createTime'] = ['LT', "{$date}"];
        //     $count = $orderTab->where($where)->count();
        //     if ($count > 0) {
        //         unset($orderUserList[$key]);
        //     }
        // }
        // $orderUserList = array_values($orderUserList);
        // $todayNum = count($orderUserList);
        $todayNum = $count_today;


        $datetime = getDateRules('yesterday');//昨日新增
        $where = [];
        $where['userFlag'] = 1;
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $count_yesterday = $wst_users->where($where)->count();
        // $where = [];
        // $where['shopId'] = $shopId;
        // $where['orderFlag'] = 1;
        // $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        // $orderUserList = $orderTab
        //     ->where($where)
        //     ->field('userId')
        //     ->group('userId')
        //     ->select();
        // foreach ($orderUserList as $key => $item) {
        //     //如果该用户不属于新用户则剔除
        //     $date = $datetime['startDate'];
        //     $where = [];
        //     $where['shopId'] = $shopId;
        //     $where['userId'] = $item['userId'];
        //     $where['orderFlag'] = 1;
        //     $where['createTime'] = ['LT', "{$date}"];
        //     $count = $orderTab->where($where)->count();
        //     if ($count > 0) {
        //         unset($orderUserList[$key]);
        //     }
        // }
        // $orderUserList = array_values($orderUserList);
        // $yesterdayNum = count($orderUserList);

        $yesterdayNum = $count_yesterday;

        $datetime = getDateRules('thisMonth');//本月新增
        $where = [];
        $where['userFlag'] = 1;
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $count_thisMonth = $wst_users->where($where)->count();
        // $where = [];
        // $where['shopId'] = $shopId;
        // $where['orderFlag'] = 1;
        // $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        // $orderUserList = $orderTab
        //     ->where($where)
        //     ->field('userId')
        //     ->group('userId')
        //     ->select();
        // foreach ($orderUserList as $key => $item) {
        //     //如果该用户不属于新用户则剔除
        //     $date = $datetime['startDate'];
        //     $where = [];
        //     $where['shopId'] = $shopId;
        //     $where['userId'] = $item['userId'];
        //     $where['orderFlag'] = 1;
        //     $where['createTime'] = ['LT', "{$date}"];
        //     $count = $orderTab->where($where)->count();
        //     if ($count > 0) {
        //         unset($orderUserList[$key]);
        //     }
        // }
        // $orderUserList = array_values($orderUserList);
        // $monthNum = count($orderUserList);

        $monthNum = $count_thisMonth;

        // $where = [];
        // $where['shopId'] = $shopId;
        // $where['orderFlag'] = 1;
        // $orderUserList = $orderTab
        //     ->where($where)
        //     ->field('userId')
        //     ->group('userId')
        //     ->select();


        $where = [];
        $where['userFlag'] = 1;

        $count_allNum = $wst_users->where($where)->count();
        $allNum = $count_allNum;

        $data = [];
        $data['todayNum'] = (int)$todayNum;//今日新增
        $data['yesterdayNum'] = (int)$yesterdayNum;//昨日新增
        $data['monthNum'] = (int)$monthNum;//本月新增
        $data['allNum'] = (int)$allNum;//会员总数
        return $data;
    }

    /**
     * 系统首页-订单统计
     * @param array $loginUserInfo 当前登陆者信息
     * @param $datetime 时间 【today：今天|yesterday：昨天|lastSevenDays：最近7天|thisWeek：本周|lastThirtyDays：最近30天|lastMonth:上月|thisMonth：本月|thisYear：本年|自定义(例子:2020-05-01 - 2020-05-31)】
     * @return array $data
     * */
    public function getShopOrderLinechart(array $loginUserInfo, string $datetime)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');
        if (in_array($datetime, ['today', 'yesterday'])) {
            $timestamp = [];
            $timestamp['startDate'] = '';
            $timestamp['endDate'] = '';
            if ($datetime == 'today') {
                $timestamp['startDate'] = getDateRules('yesterday')['startDate'];
                $timestamp['endDate'] = date('Y-m-d 23:59:59');
            }
            if ($datetime == 'yesterday') {
                $timestamp['startDate'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
                $timestamp['endDate'] = getDateRules('yesterday')['endDate'];
            }
            $datetime = $timestamp;
        } else {
            $timestamp = $datetime;
            $datetime = getDateRules($timestamp);
        }
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        $where['orderStatus'] = ['EGT', 0];
        if (!empty($datetime['startDate']) && !empty($datetime['endDate'])) {
            $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        }
        $allDate = getDateFromRange($datetime['startDate'], $datetime['endDate']);//时间区间内所有的日期
        $orderList = $orderTab
            ->where($where)
            ->field('orderId,createTime')
            ->order('orderId asc')
            ->select();
        $orderData = [];
        $todayDate = date('Y-m-d');
        foreach ($allDate as $dateKey => $dateVal) {
            $orderDataInfo = [];
            $orderDataInfo['datetime'] = $dateVal;
            $orderDataInfo['dayName'] = getWeek($dateVal);
            $orderDataInfo['total'] = 0;
            $orderDataInfo['istoday'] = false;//是否是今天
            if ($dateVal == $todayDate) {
                $orderDataInfo['istoday'] = true;
            }
            foreach ($orderList as $key => $item) {
                $createDate = explode(' ', $item['createTime'])[0];
                if ($createDate == $dateVal) {
                    $orderDataInfo['total'] += 1;
                }
            }
            $orderData[] = $orderDataInfo;
        }
        $data = [];
        $data['orderData'] = $orderData;//折线图统计
        //订单量统计
        //本月订单量
        $datetime = getDateRules('thisMonth');
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        $where['orderStatus'] = ['EGT', 0];
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $thisMonthTotal = $orderTab->where($where)->count();

        //上月订单量
        $datetime = getDateRules('lastMonth');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $lastMonthTotal = $orderTab->where($where)->count();//上月订单量
        //本月减去上月之差除以上月就是增长率
        $monthProportion = bc_math(($thisMonthTotal - $lastMonthTotal), (empty($lastMonthTotal) ? 1 : $lastMonthTotal), 'bcdiv', 2);//同比上月增长率
        $monthProportion = empty($monthProportion) ? 0 : $monthProportion;

        //本周订单量
        $datetime = getDateRules('thisWeek');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $thisWeekTotal = $orderTab->where($where)->count();//本周订单量

        //上周订单量
        $datetime = getDateRules('lastWeek');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $lastWeekTotal = $orderTab->where($where)->count();
        //本周减去上周之差除以上周就是增长率
        $weekProportion = bc_math(($thisWeekTotal - $lastWeekTotal), (empty($lastWeekTotal) ? 1 : $lastWeekTotal), 'bcdiv', 2);//同比上周增长率
        $weekProportion = empty($weekProportion) ? 0 : $weekProportion;

        //今日订单量
        $datetime = getDateRules('today');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $todayTotal = $orderTab->where($where)->count();//本周订单量

        //昨日订单量
        $datetime = getDateRules('yesterday');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $yesterdayTotal = $orderTab->where($where)->count();//本周订单量
        //本周减去上周之差除以上周就是增长率
        $dayProportion = bc_math(($todayTotal - $yesterdayTotal), (empty($yesterdayTotal) ? 1 : $yesterdayTotal), 'bcdiv', 2);//同比昨日增长率
        $dayProportion = empty($dayProportion) ? 0 : $dayProportion;

        $data['month'] = [];//月
        $data['month']['total'] = $thisMonthTotal;
        $data['month']['operator'] = $monthProportion >= 0 ? '+' : '-';
        $data['month']['ratio'] = ($monthProportion * 100) . '%';
        $data['week'] = [];//周
        $data['week']['total'] = $thisWeekTotal;
        $data['week']['operator'] = $weekProportion >= 0 ? '+' : '-';
        $data['week']['ratio'] = ($weekProportion * 100) . '%';
        $data['today'] = [];//日
        $data['today']['total'] = $todayTotal;
        $data['today']['operator'] = $dayProportion >= 0 ? '+' : '-';
        $data['today']['ratio'] = ($dayProportion * 100) . '%';
        return $data;
    }

    /**
     * 系统首页-销售统计
     * @param array $loginUserInfo 当前登陆者信息
     * @param $datetime 时间 【today：今天|yesterday：昨天|lastSevenDays：最近7天|thisWeek：本周|lastThirtyDays：最近30天|lastMonth:上月|thisMonth：本月|thisYear：本年|自定义(例子:2020-05-01 - 2020-05-31)】
     * @return array $data
     * */
    public function getShopSalechart(array $loginUserInfo, string $datetime)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');
        if (in_array($datetime, ['today', 'yesterday'])) {
            $timestamp = [];
            $timestamp['startDate'] = '';
            $timestamp['endDate'] = '';
            if ($datetime == 'today') {
                $timestamp['startDate'] = getDateRules('yesterday')['startDate'];
                $timestamp['endDate'] = date('Y-m-d 23:59:59');
            }
            if ($datetime == 'yesterday') {
                $timestamp['startDate'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
                $timestamp['endDate'] = getDateRules('yesterday')['endDate'];
            }
            $datetime = $timestamp;
        } else {
            $timestamp = $datetime;
            $datetime = getDateRules($timestamp);
        }
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        $where['orderStatus'] = ['EGT', 0];
        if (!empty($datetime['startDate']) && !empty($datetime['endDate'])) {
            $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        }
        $allDate = getDateFromRange($datetime['startDate'], $datetime['endDate']);//时间区间内所有的日期
        $orderList = $orderTab
            ->where($where)
            ->field('orderId,realTotalMoney,createTime')
            ->order('orderId asc')
            ->select();
        $orderData = [];
        $todayDate = date('Y-m-d');
        foreach ($allDate as $dateKey => $dateVal) {
            $orderDataInfo = [];
            $orderDataInfo['datetime'] = $dateVal;
            $orderDataInfo['dayName'] = getWeek($dateVal);
            $orderDataInfo['total'] = 0;
            $orderDataInfo['istoday'] = false;//是否是今天
            if ($dateVal == $todayDate) {
                $orderDataInfo['istoday'] = true;
            }
            foreach ($orderList as $key => $item) {
                $createDate = explode(' ', $item['createTime'])[0];
                if ($createDate == $dateVal) {
                    $orderDataInfo['total'] += $item['realTotalMoney'];
                }
            }
            $orderData[] = $orderDataInfo;
        }
        $data = [];
        $data['orderData'] = $orderData;//折线图统计
        //订单量统计
        //本月订单量
        $datetime = getDateRules('thisMonth');
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        $where['orderStatus'] = ['EGT', 0];
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $thisMonthTotal = $orderTab->where($where)->sum('realTotalMoney');

        //上月销售额
        $datetime = getDateRules('lastMonth');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $lastMonthTotal = $orderTab->where($where)->sum('realTotalMoney');//上月销售额
        //本月减去上月之差除以上月就是增长率
        $monthProportion = bc_math(($thisMonthTotal - $lastMonthTotal), (empty($lastMonthTotal) ? 1 : $lastMonthTotal), 'bcdiv', 2);//同比上月增长率
        $monthProportion = empty($monthProportion) ? 0 : $monthProportion;

        //本周销售额
        $datetime = getDateRules('thisWeek');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $thisWeekTotal = $orderTab->where($where)->sum('realTotalMoney');//本周销售额

        //上周销售额
        $datetime = getDateRules('lastWeek');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $lastWeekTotal = $orderTab->where($where)->sum('realTotalMoney');
        //本周减去上周之差除以上周就是增长率
        $weekProportion = bc_math(($thisWeekTotal - $lastWeekTotal), (empty($lastWeekTotal) ? 1 : $lastWeekTotal), 'bcdiv', 2);//同比上周增长率
        $weekProportion = empty($weekProportion) ? 0 : $weekProportion;

        //今日销售额
        $datetime = getDateRules('today');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $todayTotal = $orderTab->where($where)->sum('realTotalMoney');

        //昨日销售额
        $datetime = getDateRules('yesterday');
        $where['createTime'] = ['between', ["{$datetime['startDate']}", "{$datetime['endDate']}"]];
        $yesterdayTotal = $orderTab->where($where)->sum('realTotalMoney');
        //本周减去上周之差除以上周就是增长率
        $dayProportion = bc_math(($todayTotal - $yesterdayTotal), (empty($yesterdayTotal) ? 1 : $yesterdayTotal), 'bcdiv', 2);//同比昨日增长率
        $dayProportion = empty($dayProportion) ? 0 : $dayProportion;

        $data['month'] = [];//月
        $data['month']['total'] = $thisMonthTotal;
        $data['month']['operator'] = $monthProportion >= 0 ? '+' : '-';
        $data['month']['ratio'] = ($monthProportion * 100) . '%';
        $data['week'] = [];//周
        $data['week']['total'] = $thisWeekTotal;
        $data['week']['operator'] = $weekProportion >= 0 ? '+' : '-';
        $data['week']['ratio'] = ($weekProportion * 100) . '%';
        $data['today'] = [];//日
        $data['today']['total'] = $todayTotal;
        $data['today']['operator'] = $dayProportion >= 0 ? '+' : '-';
        $data['today']['ratio'] = ($dayProportion * 100) . '%';
        return $data;
    }

    /**
     * 修改订单信息
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params 订单表字段
     * @return bool $data
     * */
    public function updateOrderInfo(array $loginUserInfo, array $params)
    {
        $orderTab = M('orders');
        $orderId = (int)$params['orderId'];
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = $this->getOrderDetail($where);
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '暂无匹配的订单');
        }
        $save = [];
        $save['orderRemarks'] = null;
        $save['deliverType'] = null;
        $save['userName'] = null;
        $save['userPhone'] = null;
        $save['areaId1'] = 0;
        $save['areaId2'] = 0;
        $save['areaId3'] = 0;
        parm_filter($save, $params);
        if (empty($save)) {
            return returnData(true);
        }
        //更改配送方式-start
        $updateParams = $save;
        $updateParams['receive_deliverType'] = $params['deliverType'];
        $updateDelivetyRes = $this->updateOrderDeliveryType($loginUserInfo, $orderId, $updateParams);
        if ($updateDelivetyRes['code'] != 0) {
            return $updateDelivetyRes;
        }
        $save = $updateDelivetyRes['data'];
        //更改配送方式-end
        $saveRes = $orderTab->where($where)->save($save);
        if ($saveRes !== false) {
            if ($orderInfo['orderRemarks'] != $params['orderRemarks']) {
                $content = "备注订单（操作备注：{$params['orderRemarks']}）";
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = $orderInfo['orderStatus'];
                $logParams['payStatus'] = 1;
                if ($orderInfo['isPay'] == 1) {
                    //已支付
                    $logParams['payStatus'] = 1;
                } else {
                    //未支付
                    $logParams['payStatus'] = 0;
                }
                if ($orderInfo['isRefund'] == 1) {
                    //已退款
                    $logParams['payStatus'] = 2;
                }
                $this->addOrderLog($loginUserInfo, $logParams);
            }
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '修改失败');
        }
    }

    /**
     * 更改订单配送方式
     * @param array $loginUserInfo 当前操作者信息
     * @param int $orderId 订单id
     * @param array $save 需要修改的订单信息
     * @return array $data
     * */
    public function updateOrderDeliveryType(array $loginUserInfo, int $orderId, array $save)
    {
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = M('orders')->where($where)->find();
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '订单信息有误');
        }
        if (is_numeric($save['deliverType'])) {
            $where = [];
            $where['shopId'] = $loginUserInfo['shopId'];
            $shopInfo = M('shops')->where($where)->find();
            if (!in_array($save['deliverType'], [0, 1, 2, 3, 4, 5, 6, 22])) {
                return returnData(false, -1, 'error', '请传入正确的配送方式');
            }
            //配送方式(0:门店配送 | 1:商城配送 | 2：达达配送 | 3.蜂鸟配送 | 4：快跑者--自建配送团队 | 5：自建跑腿 | 6:自建司机)
            if ($save['deliverType'] == 2) {
                //达达配送
                if (empty($shopInfo['dadaShopId']) || empty($shopInfo['dadaOriginShopId'])) {
                    return returnData(false, -1, 'error', '达达配置不全');
                }
            } elseif ($save['deliverType'] == 3) {
                //3.蜂鸟配送
            } elseif ($save['deliverType'] == 4) {
                if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                    return returnData(false, -1, 'error', '快跑者未配置');
                }
                if (empty($shopInfo['team_token'])) {
                    return returnData(false, -1, 'error', '骑手秘钥未配置');
                }
            } elseif ($save['deliverType'] == 5) {

            }
            //更改了配送方式
            if (!in_array($orderInfo['orderStatus'], [0]) and empty($save['type'])) {
                return returnData(false, -1, 'error', '非待接单状态不能更改配送方式');
            }
            if ($save['receive_deliverType'] == 22) {
                //更改配送方式为自提
                $save['deliverType'] = 1;
                $save['isSelf'] = 1;
            } else {
                if ($orderInfo['isSelf'] == 1) {
                    $save['isSelf'] = 0;
                }
            }
            if (($save['isSelf'] != $orderInfo['isSelf']) && $save['receive_deliverType'] == 22) {
                //生成自提码
                $modUserSelfGoods = M('user_self_goods');
                $selfGoodsData = $modUserSelfGoods->where(['orderId' => $orderId, 'onStart' => 0])->find();//自提状态(0:未提货 1：已提货)
                if (empty($selfGoodsData)) {
                    $insert = [];
                    $insert['orderId'] = $orderId;
                    $insert['source'] = $orderId . $orderInfo['userId'] . $orderInfo['shopId'];
                    $insert['userId'] = $orderInfo['userId'];
                    $insert['shopId'] = $orderInfo['shopId'];
                    $modUserSelfGoods->add($insert);
                }

                $content = '更改配送方式为' . $this->getDeliveryTypeName($save['receive_deliverType']);
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = $orderInfo['orderStatus'];
                $logParams['payStatus'] = 1;
                $this->addOrderLog($loginUserInfo, $logParams);
            }
            if (($save['deliverType'] != $orderInfo['deliverType']) && $save['receive_deliverType'] != 22) {
                $content = '更改配送方式为' . $this->getDeliveryTypeName($save['receive_deliverType']);
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = $orderInfo['orderStatus'];
                $logParams['payStatus'] = 1;
                $this->addOrderLog($loginUserInfo, $logParams);
            }
            if (($save['deliverType'] == $orderInfo['deliverType']) && $orderInfo['isSelf'] == 1 && $orderInfo['isSelf'] != $save['isSelf']) {
                $content = '更改配送方式为' . $this->getDeliveryTypeName($save['receive_deliverType']);
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = $orderInfo['orderStatus'];
                $logParams['payStatus'] = 1;
                $this->addOrderLog($loginUserInfo, $logParams);
            }
        }
        unset($save['receive_deliverType']);
        return returnData($save);
    }

    /**
     * 获取配送方式名称
     * @param int deliverType 配送方式【0：商城配送 | 1：门店配送 | 2：达达配送 | 3：蜂鸟配送 | 4：快跑者 | 5：自建跑腿 | 6：自建司机 |22：自提】
     * @return string $deliverTypeName
     * */
    public function getDeliveryTypeName(int $deliverType)
    {
        $deliverTypeName = '';
        switch ($deliverType) {
            case 0:
                $deliverTypeName = '商城配送';
                break;
            case 1:
                $deliverTypeName = '门店配送';
                break;
            case 2:
                $deliverTypeName = '达达配送';
                break;
            case 3:
                $deliverTypeName = '蜂鸟配送';
                break;
            case 4:
                $deliverTypeName = '快跑者配送';
                break;
            case 5:
                $deliverTypeName = '自建跑腿';
                break;
            case 6:
                $deliverTypeName = '自建司机';
                break;
            case 22:
                $deliverTypeName = '自提';
                break;
        }
        return $deliverTypeName;
    }

    /**
     * 添加订单日志
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params <p>
     *int orderId 订单id
     *string content 操作内容
     *int logType 操作类型【0:用户|1:商家平台|2:系统|3:司机】
     *int orderStatus 订单状态
     *int payStatus 支付状态【0：未支付|1：已支付|2：已退款】
     * </p>
     * @return bool $data
     * */
    public function addOrderLog(array $loginUserInfo, $params)
    {
        $orderId = (int)$params['orderId'];
        $content = (string)$params['content'];
        $logType = (int)$params['logType'];
        $orderStatus = (int)$params['orderStatus'];
        $payStatus = (int)$params['payStatus'];
        $log = [];
        $log['orderId'] = $orderId;
        $log['logContent'] = $content;
        $log['logType'] = $logType;
        $log['orderStatus'] = $orderStatus;
        $log['payStatus'] = $payStatus;
        $log['logTime'] = date('Y-m-d H:i:s');
        if ($logType == 0) {//用户
            $log['logUserId'] = $loginUserInfo['userId'];
            $log['logUserName'] = '用户';
        } elseif ($logType == 1) {//商家平台
            if (empty($loginUserInfo['id'])) {
                //管理员登陆
                $log['logUserId'] = $loginUserInfo['userId'];
                $log['logUserName'] = '管理员-' . $loginUserInfo['userName'];
            } else {
                //职员登陆
                $log['logUserId'] = $loginUserInfo['id'];
                $log['logUserName'] = '职员-' . $loginUserInfo['username'];
            }
        } elseif ($logType == 2) {//系统
            $log['logUserId'] = 0;
            $log['logUserName'] = '系统';
        } elseif ($logType == 3) {//司机
            //暂无该操作
        }
        $logId = M('log_orders')->add($log);
        if ($logId > 0) {
            return $logId;
        } else {
            return false;
        }
    }

    /**
     *删除订单日志
     * @param array $logId 日志id
     * @param int $orderId 订单id
     * @return bool $data
     * */
    public function delOrderLog(array $logId, int $orderId)
    {
        if (empty($logId) && empty($orderId)) {
            return false;
        }
        $where = [];
        if (!empty($orderId)) {
            $where['orderId'] = $orderId;
        }
        if (!empty($logId)) {
            $where['logId'] = ['IN', $logId];
        }
        $tab = M('log_orders');
        $data = $tab->where($where)->delete();
        return $data;
    }

    /**
     * @param $loginUserInfo
     * @param $params
     * @return array|mixed
     * 商家重新发单--呼叫骑手
     */
    public function editCallAgainRider($loginUserInfo, $params)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderId = $params['orderId'];
        $deliverType = (int)$params['deliverType'];
        $isSelf = (int)$params['isSelf'];
        $orderTab = M('orders');
        $where = [];
        $where['orderId'] = $orderId;
        $where['shopId'] = $shopId;
        $orderInfo = $orderTab->where($where)->lock(true)->find();
        if (!empty($deliverType)) {//变更配送方式时进行替换
            $orderInfo["deliverType"] = $deliverType;
        }
        if (!empty($isSelf)) {//本身是自提，变更配送方式时需要替换
            $orderInfo["isSelf"] = $isSelf;
        }
        if (!in_array($orderInfo["deliverType"], [2, 4]) || $orderInfo["isSelf"] != 0) {
            return returnData(false, -1, 'error', '非外卖订单不可呼叫骑手');
        }
        $shopTab = M('shops');
        $where = [];
        $where['shopId'] = $orderInfo['shopId'];
        $shopInfo = $shopTab->where($where)->find();
        $dadaShopId = $shopInfo['dadaShopId'];
        $deliveryNo = $orderInfo["deliveryNo"];//第三方物流平台订单号
        //达达配送
        if ($orderInfo["deliverType"] == 2) {
            if (empty($shopInfo['dadaShopId']) || empty($shopInfo['dadaOriginShopId'])) {
                return returnData(false, -1, 'error', '达达配置不全');
            }
            //获取第三方平台订单状态
            $dadam = D("Home/dada");
            $daDaOrderInfo = $dadam->getTplOrderInfo(array('order_id' => $deliveryNo), $dadaShopId);
            //niaocmsstatic不存在：表示达达订单存在，需要进行状态判断.....
            //niaocmsstatic存在：表示达达订单不存在，直接呼叫骑手就可以了
            if (empty($daDaOrderInfo['niaocmsstatic']) and in_array($daDaOrderInfo['statusCode'], [1, 2, 3, 4, 8, 9, 10])) {
                //statusCode：达达返回订单状态：待接单＝1,待取货＝2,配送中＝3,已完成＝4, 指派单=8,妥投异常之物品返回中=9,妥投异常之物品返回完成=10
                //这几个状态是不可取消订单或变更配送方式
                return returnData(false, -1, 'error', '请确定该骑手订单状态');
            }
            //配送方式变更时用于判断
            if (!empty($params['type'])) {
                return returnData(true);
            }
            //【撤销订单操作已废弃】

            //预发布 并提交达达订单
            $funResData = self::DaqueryDeliverFee($loginUserInfo, $orderId);
            if ($funResData['code'] != 0) {
                return $funResData;
            } else {
                //写入订单日志
                $content = "商家重新通知达达取货";
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = 7;
                $logParams['payStatus'] = 1;
                $logId[] = $this->addOrderLog($loginUserInfo, $logParams);
            }
            return $funResData;
        }

        //自建物流配送
        if ($orderInfo["deliverType"] == 4) {
            if (empty($GLOBALS['CONFIG']['dev_key']) || empty($GLOBALS['CONFIG']['dev_secret'])) {
                return returnData(false, -1, 'error', '快跑者未配置');
            }
            if (empty($shopInfo['team_token'])) {
                return returnData(false, -1, 'error', '骑手秘钥未配置');
            }
            //获取第三方平台订单状态并撤销骑手应单
            //当撤销骑手订单后需要将订单表里的orderStatus状态变更为打包中2
            $M_Kuaipao = D("Home/Kuaipao");
            //查询订单详细信息
            $getOrderInfo_res = null;
            $getOrderInfo_info = null;
            $getOrderInfo_error = null;
            $M_Kuaipao->getOrderInfo($deliveryNo, $getOrderInfo_res, $getOrderInfo_info, $getOrderInfo_error);
            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($getOrderInfo_res);
            fwrite($myfile, "查询订单结果详情： $txt \n");
            fclose($myfile);
            //不存在：表示骑手订单存在，需要进行状态判断.....
            //存在：表示骑手订单不存在，直接呼叫骑手就可以了
            if ($getOrderInfo_res and in_array($getOrderInfo_res['status'], [1, 2, 3, 4, 5, 6])) {
                //第三方status:配送订单状态：1：待发单，2：待抢单，3：待接单，4：取单中，5：送单中，6：已送达，7：已撤销
                return returnData(false, -1, 'error', '请确定该骑手订单状态');
            }

            //配送方式变更时用于判断
            if (!empty($params['type'])) {
                return returnData(true);
            }

            //【撤销该操作已废弃】


            $funResData = self::KuaiqueryDeliverFee($loginUserInfo, $orderId);
            if ($funResData['code'] != 0) {
                return $funResData;
            } else {
                $content = "商家重新通知骑手取货";
                $logParams = [];
                $logParams['orderId'] = $orderId;
                $logParams['content'] = $content;
                $logParams['logType'] = 1;
                $logParams['orderStatus'] = 7;
                $logParams['payStatus'] = 1;
                $logId[] = $this->addOrderLog($loginUserInfo, $logParams);
            }
            return $funResData;
        }
    }

    /**
     * 外卖配送--变更配送方式
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params 订单表字段
     * @return bool $data
     * */
    public function updateDeliveryType(array $loginUserInfo, array $params)
    {
        $orderTab = M('orders');
        $orderId = (int)$params['orderId'];
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = $this->getOrderDetail($where);
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '暂无匹配的订单');
        }
        if (!in_array($orderInfo["deliverType"], [2, 4]) || $orderInfo["isSelf"] != 0) {
            return returnData(false, -1, 'error', '非外卖订单不可变更配送方式');
        }

        if (is_numeric($params['deliverType'])) {
            $saveDeliverType = $params['deliverType'];
        }
        //更改配送方式-start
        $updateParams = [];
        $updateParams['deliverType'] = $saveDeliverType;
        $updateParams['receive_deliverType'] = $params['deliverType'];
        $updateParams['type'] = 1;//用于区分外卖订单
        $updateDelivetyRes = $this->updateOrderDeliveryType($loginUserInfo, $orderId, $updateParams);
        if ($updateDelivetyRes['code'] != 0) {
            return $updateDelivetyRes;
        }
        //订单本身就是骑手配送时 需要进行判断
        if (in_array($orderInfo["deliverType"], [2, 4])) {
            //订单本身就是骑手配送时 进行数据替换并且添加标识用来判断这是订单本身的状态
            $param = [];
            $param['deliverType'] = $orderInfo["deliverType"];
            $param['type'] = 1;
            $param['shopId'] = $orderInfo['shopId'];
            $param['orderId'] = $orderId;
            $editCallAgainRider = $this->editCallAgainRider($loginUserInfo, $param);

            if ($editCallAgainRider['code'] != 0) {
                return $editCallAgainRider;
            }
        }
        //变更骑手配送时
        if (in_array($params["deliverType"], [2, 4])) {
            $params['shopId'] = $orderInfo['shopId'];
            $editCallAgainRider = $this->editCallAgainRider($loginUserInfo, $params);
            if ($editCallAgainRider['code'] != 0) {
                return $editCallAgainRider;
            }
        }

        $save = [];
        if ((int)$params['deliverType'] == 22) {
            $save['isSelf'] = 1;
            $save['deliverType'] = 1;
            $save['orderStatus'] = 3;
        } else {
            $save['deliverType'] = (int)$params['deliverType'];
        }
        if (in_array($params['deliverType'], [1, 6])) {
            $save['orderStatus'] = 2;
        }
        //更改配送方式-end
        $saveRes = $orderTab->where($where)->save($save);

        if ($saveRes !== false) {
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '修改失败');
        }
    }

    /**
     * @param array $loginUserInfo
     * @param array $params
     * @return mixed
     * 变更配送方式---订单未配送
     */
    public function editOrderDeliveryType(array $loginUserInfo, array $params)
    {
        $orderTab = M('orders');
        $orderId = (int)$params['orderId'];
        $where = [];
        $where['orderId'] = $orderId;
        $orderInfo = $this->getOrderDetail($where);
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '暂无匹配的订单');
        }
        if (!in_array($orderInfo["orderStatus"], [0, 1, 2])) {
            return returnData(false, -1, 'error', '请查看配送状态');
        }

        if (is_numeric($params['deliverType'])) {
            $saveDeliverType = $params['deliverType'];
        }
        //更改配送方式-start
        $updateParams = [];
        $updateParams['deliverType'] = $saveDeliverType;
        $updateParams['receive_deliverType'] = $params['deliverType'];
        $updateParams['type'] = 1;//用于区分外卖订单
        $updateDelivetyRes = $this->updateOrderDeliveryType($loginUserInfo, $orderId, $updateParams);
        if ($updateDelivetyRes['code'] != 0) {
            return $updateDelivetyRes;
        }
        //订单本身就是骑手配送时 需要进行判断
        if (in_array($orderInfo["deliverType"], [2, 4])) {
            //订单本身就是骑手配送时 进行数据替换并且添加标识用来判断这是订单本身的状态
            $param = [];
            $param['deliverType'] = $orderInfo["deliverType"];
            $param['type'] = 1;
            $param['shopId'] = $orderInfo['shopId'];
            $param['orderId'] = $orderId;
            $param['isSelf'] = 0;
            $editCallAgainRider = $this->editCallAgainRider($loginUserInfo, $param);

            if ($editCallAgainRider['code'] != 0) {
                return $editCallAgainRider;
            }
        }
        //变更骑手配送时
        if (in_array($params["deliverType"], [2, 4])) {
            $params['shopId'] = $orderInfo['shopId'];
            $params['isSelf'] = 0;
            $editCallAgainRider = $this->editCallAgainRider($loginUserInfo, $params);
            if ($editCallAgainRider['code'] != 0) {
                return $editCallAgainRider;
            }
        }

        $save = [];
        if ((int)$params['deliverType'] == 22) {
            $save['isSelf'] = 1;
            $save['deliverType'] = 1;
            if ($orderInfo['orderStatus'] != 0) {
                $save['orderStatus'] = 3;
            }
        } else {
            $save['deliverType'] = (int)$params['deliverType'];
            $save['isSelf'] = 0;
        }

        //更改配送方式-end
        $saveRes = $orderTab->where($where)->save($save);

        if ($saveRes !== false) {
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '修改失败');
        }
    }

    /**
     * @param $params
     * @return mixed
     * 订单打印|受理并打印
     */
    public function printOrderReceipt($params)
    {
        $orderId = $params['orderId'];
        $shopId = $params['shopId'];
        $isAccept = $params['isAccept'];//是否受理【0:不受理|1:受理】
        $ordersModule = new OrdersModule();
        $shopsModule = new ShopsModule();
        $getPrintInfo = $shopsModule->getPrintInfo($shopId);
        if (empty($getPrintInfo)) {
            return returnData(false, -1, 'error', '请配置默认打印机信息');
        }

        $field = "orderId,orderNo,shopId,orderType,isSelf,deliverType,createTime,requireTime,payFrom,";
        $field .= " realTotalMoney,deliverMoney,totalMoney, ";
        $field .= " userName,userAddress,userPhone,totalMoney,orderRemarks ";
        $orderData = $ordersModule->getOrderInfoById($orderId, $field);
        $orderInfo = $orderData['data'];
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '暂无相关信息');
        }

        $couponInfo = M('coupons')->where(['couponId' => $orderInfo['couponId']])->find();
        $orderInfo['couponMoney'] = number_format($couponInfo['couponMoney'], 2);

        $orderGoodsField = "og.goodsName,og.goodsId,og.orderId,og.skuId,og.skuSpecAttr,og.remarks,og.goodsNums,(og.goodsNums * og.goodsPrice) as goodsPrice";
        $getOrderGoodsList = $ordersModule->getOrderGoodsList($orderId, $orderGoodsField);
        $orderInfo['orderGoodsList'] = $getOrderGoodsList['data'];

        $orderInfo['orderTypeName'] = $ordersModule->getOrderTypeName($orderInfo['orderType']);

        $orderInfo['deliverTypeName'] = $ordersModule->getDeliverTypeName($orderInfo['deliverType']);
        // 如果是自提 显示为 【门店自提】
        if ($orderInfo['isSelf'] == 1) {
            $orderInfo['deliverTypeName'] = "门店自提";
        }

        if (!empty($isAccept)) {//受理并打印
            if ($orderInfo['orderStatus'] != 0) {
                return returnData(false, -1, 'error', '请检查订单状态');
            }
            $orderInfo['printsInfo'] = $getPrintInfo;
            $shopOrderAccept = $ordersModule->shopOrderAccept($orderInfo);
            return $shopOrderAccept;
        }

        $deviceNo = $getPrintInfo['equipmentNumber'];//打印机编号
        $key = $getPrintInfo['secretKey'];//打印密钥
        $times = $getPrintInfo['number'];//打印联数

        //获取店铺信息
        $shop_service_module = new \App\Modules\Shops\ShopsServiceModule();
        $getShopsInfoById_data = $shop_service_module->getShopsInfoById($shopId, '*')['data'];


        // $printContent = getPrintsOrdersTemplate($orderInfo);//打印内容

        //查询打印机状态
        $getPrintsStatus = getPrintsStatus($deviceNo, $key);
        if (empty($getPrintsStatus)) {
            return returnData(false, -1, 'error', '请检查打印机配置');
        }


        //循环开始打印
        if (((int)$times) < 1) {
            $times = 1;
        }
        for ($i = 0; $i < ((int)$times); $i++) {
            if ($times > 1 and $i == 0) {
                $printContent = getPrintsOrdersTemplate($orderInfo, "存根联", $getShopsInfoById_data['shopName']);//打印内容
            } else {
                $printContent = getPrintsOrdersTemplate($orderInfo, "顾客联", $getShopsInfoById_data['shopName']);//打印内容

            }
            //开始打印
            $getPrintsOrders = getPrintsOrders($deviceNo, $key, $printContent, 1);
            if (empty($getPrintsOrders)) {
                return returnData(false, -1, 'error', '请检查打印机配置');
            }
        }


        //查询打印状态
        $orderIndex = $getPrintsOrders['orderindex'];
        $getPrintsOrdersStatus = getPrintsOrdersStatus($deviceNo, $key, $orderIndex);
        if (empty($getPrintsOrdersStatus)) {
            return returnData(false, -1, 'error', '请检查打印机配置');
        }

        return returnData(true);
    }

    /**
     * 订单-订单汇总-商品列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireTime 要求送达日期
     * -int deliveryStatus 发货状态(1:未发货 2:已发货)
     * -int catId 店铺分类id
     * -int lineId 线路id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供货商)id
     * -int regionId 区域id
     * -int orderSrc 订单来源(3:app 4：小程序)
     * -string goodsKeywords 商品关键字
     * -int customerRankId 客户类型id
     * -int export 是否导出(0:否 1:是)
     * -int exportType 导出模板(1:按采购员导出 2:按供应商导出 3:按分类导出 4:按线路导出)
     * @return array
     * */
    public function getOrderGoodsSummary(array $paramsInput)
    {
        $module = new OrdersModule();
        $list = $module->getOrderGoodsSummaryList($paramsInput);
        $shopId = (int)$paramsInput['shopId'];
        $shopConfig = (new ShopsModule())->getShopConfig($shopId, 'whetherPurchase,whetherMathStock,whetherMathNoWarehouse', 2);
        $whetherPurchase = (int)$shopConfig['whetherPurchase'];//是否待采购商品(0:否 1:是)
        $whetherMathStock = (int)$shopConfig['whetherMathStock'];//是否计算库存(0:否 1:是)
        $whetherMathNoWarehouse = (int)$shopConfig['whetherMathNoWarehouse'];//是否计算在途(0:否 1:是)
        $returnList = array();
        $goodsModule = new GoodsModule();
        foreach ($list as $detail) {
            $uniqueTag = $detail['goodsId'] . '@' . $detail['skuId'] . '@' . $detail['purchase_type'] . '@' . $detail['purchaser_or_supplier_id'];
            if (!isset($returnList[$uniqueTag])) {//新增
                $returnDetail = array(
                    'goodsId' => $detail['goodsId'],
                    'skuId' => $detail['skuId'],
                    'goodsCode' => $detail['goodsCode'],
                    'goodsName' => $detail['goodsName'],
                    'shopCatId1' => $detail['shopCatId1'],
                    'shopCatId2' => $detail['shopCatId2'],
                    'shopCatId1Name' => $detail['shopCatId1Name'],
                    'shopCatId2Name' => $detail['shopCatId2Name'],
                    'lineName' => $detail['lineName'],
                    'goodsSpec' => $detail['goodsSpec'],
                    'unitName' => $detail['unitName'],
                    'skuSpecStr' => $detail['skuSpecStr'],
                    'purchase_type' => $detail['purchase_type'],
                    'purchase_type_name' => $detail['purchase_type_name'],
                    'purchaser_or_supplier_id' => $detail['purchaser_or_supplier_id'],
                    'purchaser_or_supplier_name' => $detail['purchaser_or_supplier_name'],
                    'orderNum' => 0,//订单数量
                    'orderIdArr' => array($detail['orderId']),//订单id集合
                    'warehouseOkStock' => $goodsModule->getGoodsCurrentInventory($detail['goodsId'], $detail['skuId'], 1),//库房库存
                    'warehouseNoStock' => $goodsModule->getGoodsCurrentInventory($detail['goodsId'], $detail['skuId'], 3),//在途库存
                    'purchaseTotalStock' => (float)$detail['goodsNums'],//汇总量
                    'purchaseNoStock' => (float)$detail['goodsNums'],//待采购量
                );
                $returnList[$uniqueTag] = $returnDetail;
            } else {//更新
                $returnList[$uniqueTag]['purchaseTotalStock'] += (float)$detail['goodsNums'];
                $returnList[$uniqueTag]['purchaseNoStock'] += (float)$detail['goodsNums'];
                $returnList[$uniqueTag]['orderIdArr'][] = $detail['orderId'];
            }
            $returnList[$uniqueTag]['orderIdArr'] = array_unique($returnList[$uniqueTag]['orderIdArr']);
            $returnList[$uniqueTag]['orderNum'] = count($returnList[$uniqueTag]['orderIdArr']);
            if ($whetherMathStock == 1) {//计算库存
                $returnList[$uniqueTag]['purchaseNoStock'] = (float)bc_math($returnList[$uniqueTag]['warehouseOkStock'], $returnList[$uniqueTag]['purchaseNoStock'], 'bcsub', 3);
                if ($returnList[$uniqueTag]['purchaseNoStock'] > 0) {
                    $returnList[$uniqueTag]['purchaseNoStock'] = 0;
                } else {
                    $returnList[$uniqueTag]['purchaseNoStock'] = abs($returnList[$uniqueTag]['purchaseNoStock']);
                }
            }
            if ($whetherMathNoWarehouse != 1) {//不计算在途库存
                $returnList[$uniqueTag]['warehouseNoStock'] = 0;
            } else {//计算在途库存
                if ($returnList[$uniqueTag]['purchaseNoStock'] > 0) {
                    $returnList[$uniqueTag]['purchaseNoStock'] = (float)bc_math($returnList[$uniqueTag]['purchaseNoStock'], $returnList[$uniqueTag]['warehouseNoStock'], 'bcsub', 3);
                    if ($returnList[$uniqueTag]['purchaseNoStock'] <= 0) {
                        $returnList[$uniqueTag]['purchaseNoStock'] = 0;
                    }
                }
            }
            unset($returnList[$uniqueTag]['orderIdArr']);
            if ($whetherPurchase == 1) {//只处理待采购商品
                if ($returnList[$uniqueTag]['purchaseNoStock'] <= 0) {
                    unset($returnList[$uniqueTag]);
                }
            }
        }
        $returnList = array_values($returnList);
        if ($paramsInput['export'] == 1) {
            //exportType 导出模板(1:按采购员导出 2:按供应商导出 3:按分类导出 4:按线路导出)
            $exportGoodsSummary = array();
            foreach ($returnList as $returnDetail) {
                $goodsDetail = $goodsModule->getGoodsInfoById($returnDetail['goodsId'], 'goodsName,goodsUnit', 2);
                $returnDetail['purchase_price'] = (float)$goodsDetail['goodsUnit'];
                if (!empty($returnDetail['skuId'])) {
                    $skuDetail = $goodsModule->getSkuSystemInfoById($returnDetail['skuId']);
                    $returnDetail['purchase_price'] = (float)$skuDetail['purchase_price'];
                }
                if ($paramsInput['exportType'] == 1 && $returnDetail['purchase_type'] == 1) {//按采购员导出
                    $exportGoodsSummary[$returnDetail['purchaser_or_supplier_name']]['goodsList'][] = $returnDetail;
                }
                if ($paramsInput['exportType'] == 2 && $returnDetail['purchase_type'] == 2) {//按供应商导出
                    $exportGoodsSummary[$returnDetail['purchaser_or_supplier_name']]['goodsList'][] = $returnDetail;
                }
                $uniqueGoodsKey = $returnDetail['goodsId'] . '@' . $returnDetail['skuId'];
                if ($paramsInput['exportType'] == 3 && !empty($returnDetail['shopCatId1Name'])) {//按分类导出
                    if (!isset($exportGoodsSummary[$returnDetail['shopCatId1Name']]['goodsList'][$uniqueGoodsKey])) {
                        $exportGoodsSummary[$returnDetail['shopCatId1Name']]['goodsList'][$uniqueGoodsKey] = $returnDetail;
                    } else {
                        $exportGoodsSummary[$returnDetail['shopCatId1Name']]['goodsList'][$uniqueGoodsKey]['purchaseTotalStock'] += $returnDetail['purchaseTotalStock'];
                        $exportGoodsSummary[$returnDetail['shopCatId1Name']]['goodsList'][$uniqueGoodsKey]['purchaseNoStock'] += $returnDetail['purchaseNoStock'];
                    }
                }
                if ($paramsInput['exportType'] == 4 && !empty($returnDetail['lineName'])) {//按线路导出
                    if (!isset($exportGoodsSummary[$returnDetail['lineName']]['goodsList'][$uniqueGoodsKey])) {
                        $exportGoodsSummary[$returnDetail['lineName']]['goodsList'][$uniqueGoodsKey] = $returnDetail;
                    } else {
                        $exportGoodsSummary[$returnDetail['lineName']]['goodsList'][$uniqueGoodsKey]['purchaseTotalStock'] += $returnDetail['purchaseTotalStock'];
                        $exportGoodsSummary[$returnDetail['lineName']]['goodsList'][$uniqueGoodsKey]['purchaseNoStock'] += $returnDetail['purchaseNoStock'];
                    }
                }
            }
            $this->exportGoodsSummary($paramsInput, $exportGoodsSummary);
        }
        return $returnList;
    }

    /**
     * 订单-订单汇总-商品列表-导出
     * @param array $paramsInput 前端传过来的搜索条件
     * @param array $exportList 需要导出的数据
     * */
    public function exportGoodsSummary(array $paramsInput, array $exportList)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("goodsList")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
        //设置excel工作表名及文件名
        $excel_filename = '订单汇总(按采购员导出)';
        //导出模板(1:按采购员导出 2:按供应商导出 3:按分类导出 4:按线路导出)
        if ($paramsInput['exportType'] == 2) {
            $excel_filename = '订单汇总(按供应商导出)';
        }
        if ($paramsInput['exportType'] == 3) {
            $excel_filename = '订单汇总(按分类导出)';
        }
        if ($paramsInput['exportType'] == 4) {
            $excel_filename = '订单汇总(按线路导出)';
        }
        $excel_filename .= date('YmdHis');
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $sheetNum = 0;
        foreach ($exportList as $key => $detail) {
            if ($sheetNum > 0) {
                $objPHPExcel->createSheet();
            }
            $goodsList = array_values($detail['goodsList']);
            // 操作第一个工作表
            $objPHPExcel->setActiveSheetIndex($sheetNum);
            $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->applyFromArray($styleThinBlackBorderOutline);
            //第一行设置内容
            $splitTag = $key;
            if (!empty($paramsInput['requireTime'])) {
                $splitTag .= '-' . $paramsInput['requireTime'];
            }
            $objPHPExcel->getActiveSheet()->setTitle($key);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', $splitTag);
            //合并
            $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');
            //设置单元格内容加粗
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置excel的表头
            $sheetTitle = array('序号', '商品编码', '商品名称', '店铺分类', '规格', '商品描述', '现有库存', '在途库存', '订购数(汇总)', '最近一次进价', '待采购数量', '单位');
            $letterKey = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
            //设置单元格
//          $objPHPExcel->getActiveSheet()->getStyle('A2:AC2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            //首先是赋值表头
            $letterCount = count($letterKey);
            for ($k = 0; $k < $letterCount; $k++) {
                $objPHPExcel->getActiveSheet()->setCellValue($letterKey[$k] . '2', $sheetTitle[$k]);
                $objPHPExcel->getActiveSheet()->getStyle($letterKey[$k] . '2')->getFont()->setSize(10)->setBold(true);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letterKey[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置每一列的宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension($letterKey[$k])->setWidth(40);
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getStyle($letterKey[$k] . '2')->applyFromArray($styleThinBlackBorderOutline);
            }
            //开始赋值
            for ($i = 0; $i < count($goodsList); $i++) {
                //先确定行
                $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
                $objPHPExcel->getActiveSheet()->getStyle("A{$row}:L{$row}")->applyFromArray($styleThinBlackBorderOutline);
                $temp = $goodsList[$i];
                for ($j = 0; $j < $letterCount; $j++) {
                    switch ($j) {
                        case 0 :
                            //商品ID
                            $cellvalue = $i + 1;
                            break;
                        case 1 :
                            //商品编码
                            $cellvalue = $temp['goodsCode'];
                            break;
                        case 2 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 3 :
                            //店铺分类
                            $cellvalue = $temp['shopCatId1Name'] . '/' . $temp['shopCatId2Name'];
                            break;
                        case 4 :
                            //规格
                            $cellvalue = $temp['skuSpecStr'];
                            break;
                        case 5 :
                            //商品描述
                            $cellvalue = $temp['goodsSpec'];
                            break;
                        case 6 :
                            //现有库存
                            $cellvalue = $temp['warehouseOkStock'];
                            break;
                        case 7 :
                            //在途库存
                            $cellvalue = $temp['warehouseNoStock'];
                            break;
                        case 8 :
                            //订购数(汇总)
                            $cellvalue = $temp['purchaseTotalStock'];
                            break;
                        case 9 :
                            //最近一次进价
                            $cellvalue = $temp['purchase_price'];
                            break;
                        case 10 :
                            //待采购量
                            $cellvalue = $temp['purchaseNoStock'];
                            break;
                        case 11 :
                            //单位
                            $cellvalue = $temp['unitName'];
                            break;
                    }
                    //赋值
                    $objPHPExcel->getActiveSheet()->setCellValue($letterKey[$j] . $row, $cellvalue);
                    //设置字体大小
                    $objPHPExcel->getActiveSheet()->getStyle($letterKey[$j] . $row)->getFont()->setSize(10);
                    //设置单元格内容水平居中
                    $objPHPExcel->getActiveSheet()->getStyle($letterKey[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                // 设置行高
                $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
            }
            $sheetNum++;
        }
        //赋值结束，开始输出
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 订单-订单汇总-商品-订单列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireTime 要求送达日期
     * -int deliveryStatus 发货状态(1:未发货 2:已发货)
     * -int catId 店铺分类id
     * -int lineId 线路id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供货商)id
     * -int regionId 区域id
     * -int orderSrc 订单来源(3:app 4：小程序)
     * -string goodsKeywords 商品关键字
     * -int customerRankId 客户类型id
     * -int goodsId 商品id
     * -int skuId 规格id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_supplier_id (采购员/供应商)id
     * @return array
     * */
    public function getOrderGoodsSummaryDetail(array $paramsInput)
    {
        $module = new OrdersModule();
        $list = $module->getOrderGoodsSummaryList($paramsInput);
        $returnList = array();
        foreach ($list as $detail) {
            $returnDetail = array(
                'orderNo' => $detail['orderNo'],
                'payment_userid' => $detail['payment_userid'],
                'payment_username' => $detail['payment_username'],
                'purchase_type' => $detail['purchase_type'],
                'purchase_type_name' => $detail['purchase_type_name'],
                'purchaser_or_supplier_id' => $detail['purchaser_or_supplier_id'],
                'purchase_type_name' => $detail['purchase_type_name'],
                'purchaser_or_supplier_name' => $detail['purchaser_or_supplier_name'],
                'orderStatusName' => $detail['orderStatusName'],
                'goodsNums' => $detail['goodsNums'],
                'unitName' => $detail['unitName'],
                'skuSpecStr' => $detail['skuSpecStr'],
                'orderRemarks' => $detail['orderRemarks'],
            );
            $returnList[] = $returnDetail;
        }
        return $returnList;
    }

    /**
     * 订单-订单汇总-商品-生成采购单-预提交
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireTime 要求送达日期
     * -int deliveryStatus 发货状态(1:未发货 2:已发货)
     * -int catId 店铺分类id
     * -int lineId 线路id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供货商)id
     * -int regionId 区域id
     * -int orderSrc 订单来源(3:app 4：小程序)
     * -string goodsKeywords 商品关键字
     * -int customerRankId 客户类型id
     * -array goodsChecked 商品信息
     * --int goodsId 商品id
     * --int skuId 规格id
     * --int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * --int purchaser_supplier_id (采购员/供应商)id
     * @return array
     * */
    public function summaryToPurcahsePresubmit(array $paramsInput)
    {
        $shopId = $paramsInput['shopId'];
        $module = new OrdersModule();
        $goodsChecked = $paramsInput['goodsChecked'];
        $shopStaffMemberModule = new ShopStaffMemberModule();
        $supplierModule = new SupplierModule();
        $kindOfData = array();
        foreach ($goodsChecked as $checkItem) {
            $purchase_type = $checkItem['purchase_type'];
            $purchaser_or_supplier_id = $checkItem['purchaser_or_supplier_id'];
            if ($purchase_type == 1) {//市场自采
                $purchaserOrSupplierDetail = $shopStaffMemberModule->getShopStaffMemberDetail($purchaser_or_supplier_id);
            } else {//供应商供货
                $purchaserOrSupplierDetail = $supplierModule->getSupplierDetailById($purchaser_or_supplier_id);
            }
            if ($shopId != $purchaserOrSupplierDetail['shopId']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，采购类型信息与门店不匹配');
            }
            $currParamsInput = $paramsInput;
            unset($currParamsInput['goodsChecked']);
            $currParamsInput['goodsId'] = $checkItem['goodsId'];
            $currParamsInput['skuId'] = $checkItem['skuId'];
            $goodsOrderList = $module->getOrderGoodsSummaryList($currParamsInput);
            if (empty($goodsOrderList)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作异常', '商品信息有误');
            }
            foreach ($goodsOrderList as $okey => $oitem) {
                if (in_array($oitem['orderStatus'], array(3, 17))) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，只有未发货的订单商品才能生成采购单");
                }
                //临时修改采购类型为前端传过来的类型
                $goodsOrderList[$okey]['purchase_type'] = $purchase_type;
                $goodsOrderList[$okey]['purchaser_or_supplier_id'] = $purchaser_or_supplier_id;
                $uniqueTag = $purchase_type . '@' . $purchaser_or_supplier_id;//采购类型,唯一标识
                $kindDetail = array(
                    'id' => $oitem['id']
                );
                $kindOfData[$uniqueTag][] = $kindDetail;
            }
        }
        $returnData = array(
            'data_num' => count($goodsChecked),//选中的数据量
            'split_bill_num' => count($kindOfData),//根据采购类型拆分的单据数量
        );
        return returnData($returnData);
    }

    /**
     * 订单-订单汇总-商品-生成采购单-确认提交
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -date requireTime 要求送达日期
     * -int deliveryStatus 发货状态(1:未发货 2:已发货)
     * -int catId 店铺分类id
     * -int lineId 线路id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供货商)id
     * -int regionId 区域id
     * -int orderSrc 订单来源(3:app 4：小程序)
     * -string goodsKeywords 商品关键字
     * -int customerRankId 客户类型id
     * -array goodsChecked 商品信息
     * --int goodsId 商品id
     * --int skuId 规格id
     * --int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * --int purchaser_supplier_id (采购员/供应商)id
     * @return array
     * */
    public function summaryToPurcahseCreate(array $paramsInput)
    {
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $paramsInput['shopId'] = $shopId;
        $module = new OrdersModule();
        $goodsChecked = $paramsInput['goodsChecked'];
        $shopStaffMemberModule = new ShopStaffMemberModule();
        $supplierModule = new SupplierModule();
        $kindOfData = array();
        $goodsModule = new GoodsModule();
        $date = date('Y-m-d');
        foreach ($goodsChecked as $checkItem) {
            $purchase_type = $checkItem['purchase_type'];
            $purchaser_or_supplier_id = $checkItem['purchaser_or_supplier_id'];
            if ($purchase_type == 1) {//市场自采
                $purchaserOrSupplierDetail = $shopStaffMemberModule->getShopStaffMemberDetail($purchaser_or_supplier_id);
            } else {//供应商供货
                $purchaserOrSupplierDetail = $supplierModule->getSupplierDetailById($purchaser_or_supplier_id);
            }
            if ($shopId != $purchaserOrSupplierDetail['shopId']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，采购类型信息与门店不匹配');
            }
            $currParamsInput = $paramsInput;
            unset($currParamsInput['goodsChecked']);
            $currParamsInput['goodsId'] = $checkItem['goodsId'];
            $currParamsInput['skuId'] = $checkItem['skuId'];
            $goodsOrderList = $module->getOrderGoodsSummaryList($currParamsInput);
            if (empty($goodsOrderList)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作异常', '商品信息有误');
            }
            foreach ($goodsOrderList as $okey => $oitem) {
                if (in_array($oitem['orderStatus'], array(3, 17))) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，只有未发货的订单商品才能生成采购单");
                }
                //临时修改采购类型为前端传过来的类型
                $goodsOrderList[$okey]['purchase_type'] = $purchase_type;
                $goodsOrderList[$okey]['purchaser_or_supplier_id'] = $purchaser_or_supplier_id;
                $uniqueTag = $purchase_type . '@' . $purchaser_or_supplier_id;//采购类型,唯一标识
                $kindDetail = array(
                    'id' => $oitem['id'],
                    'goodsId' => $oitem['goodsId'],
                    'skuId' => $oitem['skuId'],
                    'purchaseTotalNum' => $oitem['goodsNums'],
                    'purchasePriceTotal' => 0,
                    'goodsRemark' => $oitem['remarks'],
                );
                $goodsDetail = $goodsModule->getGoodsInfoById($oitem['goodsId'], 'goodsName,goodsUnit', 2);
                $kindDetail['purchasePriceTotal'] = bc_math($goodsDetail['goodsUnit'], $kindDetail['purchaseTotalNum'], 'bcmul', 2);
                if (!empty($oitem['skuId'])) {
                    $skuDetail = $goodsModule->getSkuSystemInfoById($oitem['skuId'], 2);
                    $kindDetail['purchasePriceTotal'] = bc_math($skuDetail['purchase_price'], $kindDetail['purchaseTotalNum'], 'bcmul', 2);
                }
                $goodsUniqueTag = $oitem['goodsId'] . '@' . $oitem['skuId'];
                $kindOfData[$uniqueTag]['purchase_type'] = $purchase_type;
                $kindOfData[$uniqueTag]['purchaser_or_supplier_id'] = $purchaser_or_supplier_id;
                $kindOfData[$uniqueTag]['plannedDeliveryDate'] = $paramsInput['requireTime'];
                if (empty($paramsInput['requireTime'])) {
                    $kindOfData[$uniqueTag]['plannedDeliveryDate'] = $date;
                }
                if (!isset($kindOfData[$uniqueTag]['goodsList'][$goodsUniqueTag])) {
                    $kindOfData[$uniqueTag]['goodsList'][$goodsUniqueTag] = $kindDetail;
                } else {
                    $kindOfData[$uniqueTag]['goodsList'][$goodsUniqueTag]['purchaseTotalNum'] += $kindDetail['purchaseTotalNum'];
                    $kindOfData[$uniqueTag]['goodsList'][$goodsUniqueTag]['purchasePriceTotal'] += $kindDetail['purchasePriceTotal'];
                }
            }
        }
        $purchaseModule = new PurchaseBillModule();
        $trans = new Model();
        $trans->startTrans();
        foreach ($kindOfData as $kindItem) {
            $billData = array(
                'loginInfo' => $loginInfo,
                'billFrom' => 2,
                'plannedDeliveryDate' => $kindItem['plannedDeliveryDate'],
                'billRemark' => '',
                'goods_data' => array(),
            );
            if ($kindItem['purchase_type'] == 1) {
                $billData['purchaserId'] = $kindItem['purchaser_or_supplier_id'];
            }
            if ($kindItem['purchase_type'] == 2) {
                $billData['supplierId'] = $kindItem['purchaser_or_supplier_id'];
            }
            $billData['goods_data'] = array_values($kindItem['goodsList']);
            $addBillRes = $purchaseModule->createPurchaseBill($billData, $trans);
            if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $addBillRes['msg']);
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 订单-打印-递增打印数量
     * @param array $loginInfo
     * @param int $orderId
     * @return array
     * */
    public function incOrdersPrintNum(array $loginInfo, int $orderId)
    {
        $module = new OrdersModule();
        $orderDetail = $module->getOrderInfoById($orderId, 'shopId,orderStatus,isPay', 2);
        if (empty($orderId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($orderDetail['shopId'] != $loginInfo['shopId']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息与门店不匹配');
        }
        $trans = new Model();
        $trans->startTrans();
        $module = new OrdersModule();
        $result = $module->incOrdersPrintNum($orderId, $trans);
        if (!$result) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单打印数据更新失败');
        }
        $logParams = [
            'orderId' => $orderId,
            'logContent' => '执行打印操作',
            'logUserId' => $loginInfo['user_id'],
            'logUserName' => $loginInfo['user_username'],
            'orderStatus' => $orderDetail['orderStatus'],
            'payStatus' => $orderDetail['isPay'],
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        $logRes = (new LogOrderModule())->addLogOrders($logParams);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }
}
<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use Home\Model\OrdersModel;
use Merchantapi\Model\PurchaseModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单控制器
 */
class OrdersAction extends BaseAction
{
    /**
     * 获取待付款的订单列表
     */
    public function queryByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        session('WST_USER.loginTarget', 'User');
        //判断会员等级
        $rm = D('Home/UserRanks');
        $shopInfo["userRank"] = $rm->getUserRank();
        session('WST_USER', $USER);
        //获取订单列表
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $orderList = $morders->queryByPage($obj);
        $statusList = $morders->getUserOrderStatusCount($obj);
        $um = D('Home/Users');
        $user = $um->getUserById(array("userId" => session('WST_USER.userId')));
        $this->assign("userScore", $shopInfo['userScore']);
        $this->assign("umark", "queryByPage");
        $this->assign("orderList", $orderList);
        $this->assign("statusList", $statusList);
        $this->display("default/users/orders/list");
    }

    /**
     * 获取待付款的订单列表
     */
    public function queryPayByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $payOrders = $morders->queryPayByPage($obj);
        $this->assign("umark", "queryPayByPage");
        $this->assign("payOrders", $payOrders);
        $this->display("default/users/orders/list_pay");
    }

    /**
     * 获取待发货的订单列表
     */
    public function queryDeliveryByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $deliveryOrders = $morders->queryDeliveryByPage($obj);
        $this->assign("umark", "queryDeliveryByPage");
        $this->assign("receiveOrders", $deliveryOrders);
        $this->display("default/users/orders/list_delivery");
    }

    /**
     * 获取退款订单列表
     */
    public function queryRefundByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $refundOrders = $morders->queryRefundByPage($obj);
        $this->assign("umark", "queryRefundByPage");
        $this->assign("receiveOrders", $refundOrders);
        $this->display("default/users/orders/list_refund");
    }

    /**
     * 获取收货的订单列表
     */
    public function queryReceiveByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $receiveOrders = $morders->queryReceiveByPage($obj);
        $this->assign("umark", "queryReceiveByPage");
        $this->assign("receiveOrders", $receiveOrders);
        $this->display("default/users/orders/list_receive");
    }

    /**
     * 获取已取消订单
     */
    public function queryCancelOrders()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $receiveOrders = $morders->queryCancelOrders($obj);
        $this->assign("umark", "queryCancelOrders");
        $this->assign("receiveOrders", $receiveOrders);
        $this->display("default/users/orders/list_cancel");
    }

    /**
     * 获取待评价订单
     */
    public function queryAppraiseByPage()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        self::WSTAssigns();
        $obj["userId"] = (int)$shopInfo['userId'];
        $appraiseOrders = $morders->queryAppraiseByPage($obj);
        $this->assign("umark", "queryAppraiseByPage");
        $this->assign("appraiseOrders", $appraiseOrders);
        $this->display("default/users/orders/list_appraise");
    }


    /**
     * 订单詳情-买家专用
     */
    public function getOrderInfo()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->getOrderDetails($obj);
        $data["orderInfo"] = $rs;
        $this->assign("orderInfo", $rs);
        $this->display("default/order_details");
    }

    /**
     * 废弃
     * 取消订单
     */
    public function orderCancel()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->orderCancel($obj);
        $this->ajaxReturn($rs);
    }

    /**
     * 用户确认收货订单
     */
    public function orderConfirm()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["orderId"] = (int)I("orderId");
        $obj["type"] = (int)I("type");
        $rs = $morders->orderConfirm($obj);
        $this->ajaxReturn($rs);
    }

    /**
     * 核对订单信息
     */
    public function checkOrderInfo()
    {
        $shopInfo = $this->MemberVeri();
        $maddress = D('Home/UserAddress');
        $mcart = D('Home/Cart');
        $rdata = $mcart->getPayCart();
        if ($rdata["cartnull"] == 1) {
            $this->assign("fail_msg", '不能提交空商品的订单!');
            $this->display('default/order_fail');
            exit();
        }
        $catgoods = $rdata["cartgoods"];
        $shopColleges = $rdata["shopColleges"];
        $startTime = $rdata["startTime"];
        $endTime = $rdata["endTime"];
        $gtotalMoney = $rdata["gtotalMoney"];//商品总价（去除配送费）
        $totalMoney = $rdata["totalMoney"];//商品总价（含配送费）
        $totalCnt = $rdata["totalCnt"];

        $userId = session('WST_USER.userId');
        //获取地址列表
        $areaId2 = $this->getDefaultCity();
        $addressList = $maddress->queryByUserAndCity($userId, $areaId2);
        $this->assign("addressList", $addressList);
        $this->assign("areaId2", $areaId2);
        //支付方式
        $pm = D('Home/Payments');
        $payments = $pm->getList();
        $this->assign("payments", $payments);

        //获取当前市的县区
        $m = D('Home/Areas');
        $areaList2 = $m->getDistricts($areaId2);
        $this->assign("areaList2", $areaList2);
        if ($endTime == 0) {
            $endTime = 24;
            $cstartTime = (floor($startTime)) * 4;
            $cendTime = (floor($endTime)) * 4;
        } else {
            $cstartTime = (floor($startTime) + 1) * 4;
            $cendTime = (floor($endTime) + 1) * 4;
        }
        if (floor($startTime) < $startTime) {
            $cstartTime = $cstartTime + 2;
        }
        if (floor($endTime) < $endTime) {
            $cendTime = $cendTime + 2;
        }
        $baseScore = WSTOrderScore();
        $baseMoney = WSTScoreMoney();
        $this->assign("startTime", $cstartTime);
        $this->assign("endTime", $cendTime);
        $this->assign("shopColleges", $shopColleges);
        $this->assign("catgoods", $catgoods);
        $this->assign("gtotalMoney", $gtotalMoney);
        $this->assign("totalMoney", $totalMoney);
        $um = D('Home/Users');
        $user = $um->getUserById(array("userId" => session('WST_USER.userId')));
        $this->assign("userScore", $shopInfo['userScore']);
        $useScore = $baseScore * floor($shopInfo["userScore"] / $baseScore);
        $scoreMoney = $baseMoney * floor($shopInfo["userScore"] / $baseScore);
        if ($totalMoney < $scoreMoney) {//订单金额小于积分金额
            $useScore = $baseScore * floor($totalMoney / $baseMoney);
            $scoreMoney = $baseMoney * floor($totalMoney / $baseMoney);
        }
        $this->assign("canUserScore", $useScore);
        $this->assign("scoreMoney", $scoreMoney);
        $this->display('default/check_order');
    }

    /**
     * 提交订单信息
     *
     */
    public function submitOrder()
    {
        $shopInfo = $this->MemberVeri();
        session("WST_ORDER_UNIQUE", null);
        $morders = D('Home/Orders');
        $rs = $morders->submitOrder();
        $this->ajaxReturn($rs);
    }

    /**
     * 显示下单结果
     */
    public function orderSuccess()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $this->assign("orderInfos", $morders->getOrderListByIds());
        $this->display('default/order_success');
    }

    /**
     * 检查是否已支付
     */
    public function checkOrderPay()
    {
        $morders = D('Home/Orders');
        $USER = session('WST_USER');
        $obj["userId"] = (int)$shopInfo['userId'];
        $rs = $morders->checkOrderPay($obj);
        $this->ajaxReturn($rs);
    }


    /**
     * 过秤列表
     */
    public function getOrderElectronicScaleList()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->getOrderElectronicScaleList($obj);
        $data["orderInfo"] = $rs;
        $this->assign("orderInfo", $rs);
        $this->returnResponse(true, '获取成功', $rs);
    }

    /*************************************************************************/
    /********************************商家訂單管理*****************************/
    /*************************************************************************/
    /**
     * 跳转到商家订单列表
     */
    public function toShopOrdersList()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $this->assign("umark", "toShopOrdersList");
        $this->display("default/shops/orders/list");
    }

    /**
     * 获取商家订单列表
     */
//    public function queryShopOrders(){
//
//        $shopInfo = $this->MemberVeri();
//        $morders = D('Home/Orders');
//        $obj["shopId"] = (int)$shopInfo["shopId"];
//        $obj["userId"] = (int)$shopInfo['userId'];
//        $orders = $morders->queryShopOrders($obj);
//        $this->returnResponse(true,'获取成功',$orders);
////		$this->ajaxReturn($orders);
//    }

    /**
     * 获取商家订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lz7s7i
     */
    public function queryShopOrders()
    {
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $morders = D('Home/Orders');
        $requestParams["shopId"] = (int)$shopInfo["shopId"];
        $requestParams["userId"] = (int)$shopInfo['userId'];
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        $requestParams['sort_field'] = I('sort_field', 'createTime');
        $requestParams['sort_value'] = I('sort_value', 'DESC');
        if (!empty((int)I('p'))) {//兼容之前的老字段
            $requestParams['page'] = (int)I('p');
        }
        $orders = $morders->queryShopOrders($requestParams);
        $this->ajaxReturn(returnData($orders));
    }

//    /**
//     * 获取商家非常规订单列表
//     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vi7c5h
//     */
//    public function getShopOrders()
//    {
//        $shopInfo = $this->MemberVeri();
//        $requestParams = I();
//        $morders = D('Home/Orders');
//        $requestParams["shopId"] = (int)$shopInfo["shopId"];
//        $requestParams["userId"] = (int)$shopInfo['userId'];
//        $requestParams['page'] = (int)I('page', 1);
//        $requestParams['pageSize'] = (int)I('pageSize', 15);
//        $orders = $morders->getShopOrders($requestParams);
//        $this->ajaxReturn(returnData($orders));
//    }

    /**
     * 注:上面注释的方法是原来的
     * 采购-订单商品采购列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/prqs14
     */
    public function getShopOrders()
    {
        $shop_detail = $this->MemberVeri();
        $req = I();
        $params = array(
            'shop_id' => $shop_detail['shopId'],
            'orderNo' => '',//订单号
            'ordertime_start' => '',//下单时间-开始时间
            'ordertime_end' => '',//下单时间-结束时间
            'require_date' => '',//期望送达日期
            'cat_id' => '',//店铺分类id
            'statusMark' => '',//订单状态【toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】,不传默认全部
            'page' => 1,
            'pageSize' => 15,
            'export' => 0,//是否导出(0:否 1:是)
        );
        parm_filter($params, $req);
        $model = new PurchaseModel();
        $result = $model->getPurchaseGoodsList($params);
        $this->ajaxReturn(returnData($result));
    }

//    /**
//     * 删除由非常规订单生成的采购商品
//     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uf8vz2
//     */
//    public function delPurchaseGoods()
//    {
//        $shopInfo = $this->MemberVeri();
//        $goodsId = (int)I('goodsId', 0);
//        $skuId = (int)I('skuId', 0);
//        if (empty($goodsId)) {
//            $this->ajaxReturn(returnData(false, -1, 'error', '请选择删除的商品'));
//        }
//        $requestParams = [];
//        $model = D('Home/Orders');
//        $requestParams["shopId"] = (int)$shopInfo["shopId"];
//        $requestParams["goodsId"] = (int)$goodsId;
//        $requestParams["skuId"] = (int)$skuId;
//        $orders = $model->delPurchaseGoods($requestParams);
//        $this->ajaxReturn($orders);
//    }

    /**
     * 注释:上面注释的方法是原来的
     * 采购-订单商品采购列表-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/urx2o2
     */
    public function delPurchaseGoods()
    {
        $shop_detail = $this->MemberVeri();
        $goods_id = (int)I('goodsId');
        $sku_id = (int)I('skuId');
        $purchase_goods_where = json_decode(htmlspecialchars_decode(I('purchaseGoodsWhere')), true);//一键采购页的搜索条件
        if (empty($purchase_goods_where)) {
            $purchase_goods_where = array();
        }
        $purchase_goods_where['shop_id'] = $shop_detail['shopId'];
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $model = new PurchaseModel();
        $result = $model->delPurchaseGoods($goods_id, $sku_id, $purchase_goods_where);
        $this->ajaxReturn($result);
    }

//    /**
//     * 生成采购单
//     */
//    public function getPurchaseList()
//    {
//        $shopInfo = $this->MemberVeri();
//        $startDate = I('startDate', 0);
//        $endDate = I('endDate', 0);
//        $requestParams = [];
//        $model = D('Home/Orders');
//        $requestParams['shopId'] = (int)$shopInfo['shopId'];
//        $requestParams['userId'] = (int)$shopInfo['userId'];
//        $requestParams['startDate'] = $startDate;
//        $requestParams['endDate'] = $endDate;
//        $orders = $model->getPurchaseList($requestParams);
//        $this->ajaxReturn(returnData($orders));
//    }
//    /**
//     * 生成采购单----由商品采购表生成
//     */
//    public function getPurchaseList()
//    {
//        $shopId = $this->MemberVeri()['shopId'];
//        $model = D('Home/Orders');
//        $orders = $model->getPurchaseList($shopId);
//        $this->ajaxReturn(returnData($orders));
//    }

    /**
     * 注释:上面注释的方法是原来的
     * 采购-一键采购-订单商品采购列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kqcvop
     */
    public function getPurchaseList()
    {
        $shop_detail = $this->MemberVeri();
        $req = I();
        $params = array(
            'shop_id' => $shop_detail['shopId'],
            'orderNo' => '',//订单号
            'ordertime_start' => '',//下单时间-开始时间
            'ordertime_end' => '',//下单时间-结束时间
            'require_date' => '',//期望送达日期
            'cat_id' => '',//店铺分类id
            'statusMark' => '',//订单状态【toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】,不传默认全部
        );
        parm_filter($params, $req);
        $model = new PurchaseModel();
        $orders = $model->getPurchaseJxcGoodsList($params);
        $this->ajaxReturn(returnData($orders));
    }

    /**
     * 统计订单数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cluhe0
     * */
    public function getOrderStatusNum()
    {
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $morders = D('Home/Orders');
        $requestParams['shopId'] = $shopInfo['shopId'];
        $data = $morders->getOrderStatusNum($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /*
     * 自提订单-提货
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mdtxcg
     * */
    public function selfOrderAction()
    {
        $shopInfo = $this->MemberVeri();
        $orderId = (int)I('orderId');
        $source = I('source', '');
        if (empty($orderId) || empty($source)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/Orders');
        $data = $m->selfOrderAction($shopInfo, $orderId, $source);
        $this->ajaxReturn($data);
    }

    /**
     * 商家受理订单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bqrc7h
     */
    public function shopOrderAccept()
    {
        $loginUserInfo = $this->MemberVeri();
        $model = D('Home/Orders');
        $orderId = (int)I('orderId');
        $deliverType = I('deliverType');//后加,更改配送方式【0：商城配送 | 1：门店配送 | 2：达达配送 | 3：蜂鸟配送 | 4：快跑者 | 5：自建跑腿 | 6：自建司机 |22：自提】
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = $model->shopOrderAccept($loginUserInfo, $orderId, $deliverType);
        $this->ajaxReturn($data);
    }

    /**
     * 商家批量受理订单
     */
    public function batchShopOrderAccept()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $rs = $morders->batchShopOrderAccept($shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 商家生产订单
     */
    public function shopOrderProduce()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->shopOrderProduce($obj);
        if ($rs && (float)$rs['status'] > 0) {
            $this->returnResponse(1, '操作成功', $rs);
        } else {
            $msg = !empty($rs['apiInfo']) ? $rs['apiInfo'] : '操作失败';
            $msg = !empty($rs['msg']) ? $rs['msg'] : $msg;
            $this->returnResponse(-1, $msg, []);
        }
//		$this->ajaxReturn($rs);
    }

    public function batchShopOrderProduce()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $rs = $morders->batchShopOrderProduce($shopInfo);
        if ($rs['status'] == 1) {
            $this->returnResponse(1, '操作成功', $rs);
        } else {
            $this->returnResponse(-1, '操作失败', []);
        }
    }

    /**
     * 商家发货配送订单
     */
    public function shopOrderDelivery()
    {
        $loginUserInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$loginUserInfo['user_id'];
        $obj["shopId"] = (int)$loginUserInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $obj["weightGJson"] = I('weightGJson');
        //自定义数据结构解析 $str = 'goodsId=4@skuId=0@goodWeight=30#goodsId=5@skuId=0@goodWeight=50';
        if (!empty($obj["weightGJson"])) {
            $result = explode('#', $obj["weightGJson"]);
            for ($i = 0; $i < count($result); $i++) {
                $result[$i] = explode('@', $result[$i]);
                for ($j = 0; $j < count($result[$i]); $j++) {
                    $result[$i][$j] = explode('=', $result[$i][$j]);
                }
            }
            $goodsWeight = array();


            foreach ($result as $index => $a) {
                if (empty($a[0][0])) {
                    $this->ajaxReturn(returnData($obj["weightGJson"], -1, 'error', 'weightGJson 接受异常，缺少goodsId'));
                }
                if (empty($a[2][0])) {
                    $this->ajaxReturn(returnData($obj["weightGJson"], -1, 'error', 'weightGJson 接受异常，缺少skuId'));
                }
                array_push($goodsWeight, array($a[0][0] => $a[0][1], $a[1][0] => $a[1][1], $a[2][0] => $a[2][1]));
            }
            $obj["weightGJson"] = $goodsWeight;

        } else {
            $obj["weightGJson"] = array();
        }


        if (!is_array($obj["weightGJson"])) {
            $this->ajaxReturn(returnData($obj["weightGJson"], -1, 'error', 'weightGJson 接受异常'));
//            $rsdata["status"] = -1;
//            $rsdata['data'] = $obj["weightGJson"];
//            $rsdata['msg'] = 'weightGJson 接受异常';
//            $this->ajaxReturn($rsdata);
        }

        $obj["isShopGo"] = (int)I("isShopGo", 0);//如果值为1 则指定为商家自定配送
        //$obj["deliverType"] = (int)I("deliverType");
        $data = $morders->shopOrderDelivery($loginUserInfo, $obj);
//        if ($rs['status'] != -1) {
//            $this->ajaxReturn($rs);
//            //$this->returnResponse(1,'操作成功',$rs);
//        } else {
//            $msg = !empty($rs['info']) ? $rs['info'] : '操作失败';
//            //$this->returnResponse(-1,$msg,['status'=>-1,'info'=>$msg]);
//            $this->ajaxReturn($rs);
//        }
        $this->ajaxReturn($data);
    }


    /**
     * 商家发货配送订单
     */
    public function batchShopOrderDelivery()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $rs = $morders->batchShopOrderDelivery($shopInfo);
        if ($rs['status'] != -1) {
            $this->returnResponse(1, '操作成功', []);
        } else {
            $this->returnResponse(-1, '操作失败', []);
        }
//		$this->ajaxReturn($rs);
    }

    /**
     * 商家确认收货订单
     */
    public function shopOrderReceipt()
    {
        $shopInfo = $this->MemberVeri();
        $loginUserInfo = $shopInfo;
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->shopOrderReceipt($loginUserInfo, $obj);
        if ($rs['status'] != -1) {
            $this->returnResponse(1, '操作成功', $rs);
        } else {
            $this->returnResponse(-1, '操作失败', []);
        }
    }

    /**
     * 商家同意拒收/不同意拒收
     */
    public function shopOrderRefund()
    {
        $shopInfo = $this->MemberVeri();
        $loginUserInfo = $shopInfo;
        $USER = session('WST_USER');
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->shopOrderRefund($loginUserInfo, $obj);
        if ($rs['status'] != -1) {
            $this->returnResponse(1, '操作成功', $rs);
        } else {
            $this->returnResponse(-1, '操作失败', []);
        }
    }

    /**
     * 获取用户订单消息提示
     */
    public function getUserMsgTips()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $USER = session('WST_USER');
        $obj["userId"] = (int)$shopInfo['userId'];
        $statusList = $morders->getUserOrderStatusCount($obj);
        $this->ajaxReturn($statusList);
    }

    /**
     * 获取店铺订单消息提示
     */
    public function getShopMsgTips()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $USER = session('WST_USER');
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["userId"] = (int)$shopInfo['userId'];
        $statusList = $morders->getShopOrderStatusCount($obj);
        $this->ajaxReturn($statusList);
    }


    /**
     * 订单詳情
     */
    public function getOrderDetails()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->getOrderDetailsApi($obj);
        //$this->returnResponse(true, '获取成功', $rs);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 订单日志
     */
    public function getOrderLog()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["orderId"] = (int)I("orderId");
        $rs = $morders->getOrderLogApi($obj);
        $reData = array(
            'list' => $rs
        );
        //$this->returnResponse(true, '获取成功', $reData);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取订单量
     */
    public function getOrdercount()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrdercount($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 获取每天收入
     */
    public function getOrderForDay()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderForDay($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 获取时间范围内的订单
     */
    public function getTimeOrderForZitiLisrt()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getTimeOrderForZitiLisrt($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 根据订单id获取订单商品列表
     */
    public function getGoodsListForOrder()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getGoodsListForOrder($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 获取每件商品预售量
     */
    public function getOrderGoodsCount()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderGoodsCount($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 获取时间段内的每天字段数量
     */
    public function getOrderSumFieldsDay()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderSumFieldsDay($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 获取时间段内的字段数量
     */
    public function getOrderSumFields()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderSumFields($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 订单列表商品重量设置 单个商品设重 单位为g
     */
    public function setShopGoodsWeight()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["weight"] = (int)I('weight');
        $obj["goodsId"] = (int)I('goodsId');
        $obj["orderId"] = (int)I('orderId');
        $obj["shopInfo"] = $shopInfo;
        $res = $morders->setShopGoodsWeight($obj);

        $this->ajaxReturn($res);
    }

    /**
     * 获取商品重量
     */
    public function getShopGoodsWeight()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["weight"] = (int)I('weight');
        $obj["goodsId"] = (int)I('goodsId');
        $obj["orderId"] = (int)I('orderId');
        $obj["shopInfo"] = $shopInfo;
        $res = $morders->getShopGoodsWeight($obj);

        $this->ajaxReturn($res);
    }


    //商家设置用户订单为已读
    public function setOrderUserHasRead()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["orderId"] = (int)I('orderId');
        $obj["shopInfo"] = $shopInfo;
        $res = $morders->setOrderUserHasRead($obj);

        $this->ajaxReturn($res);
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
    public function queryCustomOrders()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $morders = D('Home/Orders');
        $obj["shopId"] = (int)$shopInfo["shopId"];
        $obj['page'] = (int)I("page", 1);
        $obj['orderNo'] = I("orderNo");
        $obj['mobile'] = I('mobile');
        $obj['startDate'] = I('startDate');
        $obj['endDate'] = I('endDate');
        $obj['userName'] = I('userName');
        $obj['goodsName'] = I('goodsName');
        $orders = $morders->queryCustomOrders($obj);
        $this->ajaxReturn($orders);
    }

    /**
     * 撤销（撤销到打包中）
     */
    public function revoke()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["shopId"] = (int)$shopInfo["shopId"];
        $obj['orderId'] = I("orderId");
        $orders = $morders->revokeToPack($obj);
        $this->ajaxReturn($orders);
    }

    /**
     * 删除订单
     */
    public function deleteOrder()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $obj["shopId"] = (int)$shopInfo["shopId"];
//        $obj["shopId"] = I('shopId',0,'intval');
        $obj['orderId'] = I("orderId");
        $orders = $morders->deleteOrder($obj);
        $this->ajaxReturn($orders);
    }

    /**
     * 获取时间段内的字段数量Two
     */
    public function getOrderSumFieldsTwo()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['fields'] = I('fields', 'realTotalMoney');
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderSumFieldsTwo($parameter);

        $this->returnResponse(true, '获取成功', $rs);
    }

    /**
     * 订单统计【待定】
     */
    public function getOrderStatistics()
    {
        $shopInfo = $this->MemberVeri();
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderStatistics($parameter);
        $this->returnResponse(true, '获取成功', $rs);
    }


    /*
	 * 获取会员消费记录
	 * @param string startDate
	 * @param string endDate
	 * @param string userName 会员名称
	 * @param string userPhone 会员手机号
	 * @param int orderStatus 订单状态(0=>待受理,1=>已受理,2=>打包中,3=>配送中,4=>已到货,7=>外卖配送,8=>预售,20=>所有)
	 * */
    public function getOrders()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $orders = $morders->getOrders($parameter);
        $this->returnResponse(true, '获取成功', $orders);
    }

    /**
     * 获取各种订单状态下的商品(已去重)
     * @param int orderStatus (
     * 0=>待受理
     * 1=>已受理
     * 2=>打包中
     * 3=>配送中
     * 4=>已到货
     * 7=>外卖配送
     * 8=>预售
     * 20=>所有
     * )
     *
     */
    public function getOrderUnquieGoods()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $morders = D('Home/Orders');
        $parameter = I();
        $parameter['orderStatus'] = I('orderStatus', 0);
        $parameter['shopId'] = $shopInfo['shopId'];
        $rs = $morders->getOrderUnquieGoods($parameter);

        $this->ajaxReturn($rs);
    }


    /*
     * 补差价订单列表
     * @param string token
     * @param string orderNo PS:订单号
     * @param string userName PS:用户名
     * @param string userPhone PS:手机号
     * @param int isPay PS:是否已补(0:否|1:是|20:全部)
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * */
    public function getDiffMoneyOrders()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1];
        $morders = D('Home/Orders');
        $obj["shopId"] = (int)$shopInfo["shopId"];
        $obj["userId"] = (int)$shopInfo['userId'];
        $orders = $morders->getDiffMoneyOrders($obj);
        $this->ajaxReturn(returnData($orders));
    }

    /**
     * 补差价订单列表详情
     * @param string token
     * @param int orderId PS:订单号
     */
    public function getDiffMoneyOrdersDetail()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        if (empty($obj["orderId"])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '参数有误'));
        }
        $rs = $morders->getDiffMoneyOrdersDetail($obj);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 修改未补差价金额
     */
    public function updateDiffMoney()
    {
        $shopInfo = $this->MemberVeri();
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $morders = D('Home/Orders');
        $obj["userId"] = (int)$shopInfo['userId'];
        $obj["shopId"] = (int)$shopInfo['shopId'];
        $obj["orderId"] = (int)I("orderId");
        $obj["goodsId"] = (int)I("goodsId");
        $obj["skuId"] = (int)I("skuId");
        $obj["money"] = I("money");
        if (empty($obj["orderId"])) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择补差价订单'));
        }
        if (empty($obj["money"]) || $obj["money"] <= 0) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请查看补差价金额是否正确'));
        }
        $rs = $morders->updateDiffMoney($obj);
        $this->ajaxReturn($rs);
    }

    /**
     * 订单转化率统计
     * 默认显示一周
     */
    public function orderConversionRate()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $rs = D('Home/Orders')->orderConversionRate($shopId);
        $apiRet['apiData'] = $rs;
        $this->ajaxReturn($apiRet);
    }

    /**
     * PS:状态用字符的原因:
     * 1:该方法中的字段搜索存在组合条件查询,前端一个状态值可能对应数据库中的多个状态
     * 2:避免后期状态发生更改,便于修改
     * 订单列表
     * @param varchar token
     * @param varchar orderStatusCode 订单状态【noPay:未支付|pendingApproval:待审核|unPacked:待打包|waitingDelivery:待配送|selfPickUp:自提待取货|selfPickedUp:自提已取货|Cancelled:已取消|noSelfRiderWaitingOrder:非自提骑手待接单|riderReceivedOrder:骑手已接单|delivery:配送中|delivered:已交货】
     * @param varchar orderType 订单类型【ordinary:普通订单|assemble:拼团订单|advanceSale:预售订单|seckill:秒杀订单】
     * @param varchar payType 支付方式【wxPay:微信支付|aliPay:支付宝支付|balancePay:余额支付|货到付款:cashOnDelivery】
     * @param varchar deliveryType 配送方式【selfDelivery:自提|riderDelivery:骑手配送|shopDelivery:门店配送】
     * @param varchar createTime 创建时间【today:今天|yesterday:昨天|lastSevenDays:最近7天|lastThirtyDays:最近30天|thisMonth:本月|thisYear:本年|自定义(例子:2020-05-01 - 2020-05-31)】
     * @param varchar keywords 订单号(用户姓名|电话|订单编号)
     * @param bool export 导出(true:是|false:否)
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getShopOrderList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $orderStatuCode = I('orderStatusCode');
        $orderType = I('orderType');
        $payType = I('payType');
        $deliveryType = I('deliveryType');
        $createTime = I('createTime');
        $keywords = I('keywords');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $export = I('export', false);
        $rs = D('Home/Orders')->getShopOrderList($shopId, $orderStatuCode, $orderType, $payType, $deliveryType, $createTime, $keywords, $page, $pageSize, $export);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 订单审核/拒绝
     * @param int dataType 场景(1:审核通过|2:审核拒绝) PS:默认为审核通过
     * $param varchar orderIds 订单id,多个订单id可用英文逗号分隔
     * @param varchar shopCancellationReason 拒绝原因 PS:拒绝操作必填字段
     */
    public function batchExamineShopOrders()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $dataType = (int)I('dataType', 1);
        $orderIds = I('orderIds');
        $shopCancellationReason = I('shopCancellationReason');
        if (empty($orderIds)) {
            $apiRet = returnData(null, -1, 'error', '订单id不能为空');
            $this->ajaxReturn($apiRet);
        }
        if ($dataType == 2 && empty($shopCancellationReason)) {
            $apiRet = returnData(null, -1, 'error', '请填写拒绝原因');
            $this->ajaxReturn($apiRet);
        }
        $rs = D('Home/Orders')->batchExamineShopOrders($shopId, $orderIds, $dataType, $shopCancellationReason);
        $this->ajaxReturn($rs);
    }

    /**
     * 补差价退款
     * @param string token
     * @param int orderId 订单id
     * @param decimal amount 金额
     * @param int type 打款类型【1：手动打款|2：系统自动打款】
     * */
    public function returnGoodsDiffAmount()
    {
        $loginUserInfo = $this->MemberVeri()['shopId'];
        $orderId = I('orderId', 0);
        $amount = (float)I('amount', 0);
        $type = I('type');
        if (empty($orderId) || empty($type)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = D('Home/Orders')->returnGoodsDiffAmount($loginUserInfo, $orderId, $amount, $type);
        $this->ajaxReturn($data);
    }

    /**
     * 系统首页-用户总览
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fo4npd
     * */
    public function getShopUserOverview()
    {
        $loginUserInfo = $this->MemberVeri();
        $data = D('Home/Orders')->getShopUserOverview($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 系统首页-订单统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gq7m7w
     * */
    public function getShopOrderLinechart()
    {
        $loginUserInfo = $this->MemberVeri();
        $datetime = I('datetime', '');
        if (empty($datetime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = D('Home/Orders')->getShopOrderLinechart($loginUserInfo, $datetime);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 系统首页-销售统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bslppy
     * */
    public function getShopSalechart()
    {
        $loginUserInfo = $this->MemberVeri();
        $datetime = I('datetime', '');
        if (empty($datetime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = D('Home/Orders')->getShopSalechart($loginUserInfo, $datetime);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 修改订单信息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lbi36p
     */
    public function updateOrderInfo()
    {
        $loginUserInfo = $this->MemberVeri();
        $requestParams = I();
        if (empty($requestParams['orderId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = D('Home/Orders');
        $data = $model->updateOrderInfo($loginUserInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 商家重新发单--呼叫骑手
     */
    public function editCallAgainRider()
    {
        $loginUserInfo = $this->MemberVeri();
        $orderId = (int)I('orderId');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择订单'));
        }
        $requestParams = [];
        $morders = D('Home/Orders');
        $requestParams['orderId'] = (int)$orderId;
        $orders = $morders->editCallAgainRider($loginUserInfo, $requestParams);
        $this->ajaxReturn($orders);
    }

    /**
     * 外卖配送--变更配送方式
     */
    public function updateDeliveryType()
    {
        $loginUserInfo = $this->MemberVeri();
        $orderId = (int)I('orderId');
        $deliverType = I('deliverType');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $requestParams = [];
        $requestParams['orderId'] = $orderId;
        $requestParams['deliverType'] = $deliverType;
        $model = new OrdersModel();
        $data = $model->updateDeliveryType($loginUserInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 变更配送方式---订单未配送
     * 配送方式【 1：门店配送 | 2：达达配送 | 4：自建骑手[快跑者]  | 6：自建司机 |22：自提】
     * https://www.yuque.com/anthony-6br1r/oq7p0p/tm9y10
     */
    public function editOrderDeliveryType()
    {
        $loginUserInfo = $this->MemberVeri();
        $orderId = (int)I('orderId');
        $deliverType = (int)I('deliverType');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择订单'));
        }
        if (!in_array($deliverType, [1, 2, 4, 6, 22])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择变更的配送方式'));
        }
        $requestParams = [];
        $requestParams['orderId'] = $orderId;
        $requestParams['deliverType'] = $deliverType;
        $model = new OrdersModel();
        $data = $model->editOrderDeliveryType($loginUserInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 订单打印|受理并打印
     */
    public function printOrderReceipt()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $orderId = (int)I('orderId');
        $isAccept = (int)I('isAccept', 0);//isAccept 是否受理【0:不受理|1:受理】
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择订单'));
        }
        $requestParams = [];
        $requestParams['orderId'] = $orderId;
        $requestParams['shopId'] = $shopId;
        $requestParams['isAccept'] = $isAccept;
        $model = new OrdersModel();
        $data = $model->printOrderReceipt($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 订单汇总-商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xapulo
     * */
    public function getOrderGoodsSummary()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'requireTime' => '',
            'deliveryStatus' => 1,//发货状态(1:未发货 2:已发货) PS:默认未发货
            'catId' => '',
            'lineId' => '',
            'purchase_type' => '',
            'purchaser_or_supplier_id' => '',
            'regionId' => '',
            'orderSrc' => '',
            'goodsKeywords' => '',
            'customerRankId' => '',
            'export' => 0,//是否导出(0:否 1:是)
            'exportType' => 1,//导出模板(1:按采购员导出 2:按供应商导出 3:按分类导出 4:按线路导出)
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new OrdersModel();
        $result = $mod->getOrderGoodsSummary($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 订单-订单汇总-商品-订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lsvpvz
     * */
    public function getOrderGoodsSummaryDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'requireTime' => '',
            'deliveryStatus' => 1,//发货状态(1:未发货 2:已发货) PS:默认未发货
            'catId' => '',
            'lineId' => '',
            'purchase_type' => '',
            'purchaser_or_supplier_id' => '',
            'regionId' => '',
            'orderSrc' => '',
            'goodsKeywords' => '',
            'customerRankId' => '',
            'goodsId' => 0,
            'skuId' => 0,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['goodsId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new OrdersModel();
        $result = $mod->getOrderGoodsSummaryDetail($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 订单-订单汇总-商品-生成采购单-预提交
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nslkcu
     * */
    public function summaryToPurcahsePresubmit()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'requireTime' => '',
            'deliveryStatus' => 1,//发货状态(1:未发货 2:已发货) PS:默认未发货
            'catId' => '',
            'lineId' => '',
            'purchase_type' => '',
            'purchaser_or_supplier_id' => '',
            'regionId' => '',
            'orderSrc' => '',
            'goodsKeywords' => '',
            'customerRankId' => '',
            'goodsChecked' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goodsChecked'] = json_decode(htmlspecialchars_decode($paramsInput['goodsChecked']), true);
        if (empty($paramsInput['goodsChecked'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品'));
        }
        foreach ($paramsInput['goodsChecked'] as $item) {
            if (empty($item['goodsId'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
            }
            if (empty($item['purchase_type']) || empty($item['purchaser_or_supplier_id']) || !in_array($item['purchase_type'], array(1, 2))) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请补全页面中的采购类型信息'));
            }
        }
        $mod = new OrdersModel();
        $result = $mod->summaryToPurcahsePresubmit($paramsInput);
        $this->ajaxReturn($result);
    }


    /**
     * 订单-订单汇总-商品-生成采购单-确认提交
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sav5gy
     * */
    public function summaryToPurcahseCreate()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'requireTime' => '',
            'deliveryStatus' => 1,//发货状态(1:未发货 2:已发货) PS:默认未发货
            'catId' => '',
            'lineId' => '',
            'purchase_type' => '',
            'purchaser_or_supplier_id' => '',
            'regionId' => '',
            'orderSrc' => '',
            'goodsKeywords' => '',
            'customerRankId' => '',
            'goodsChecked' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goodsChecked'] = json_decode(htmlspecialchars_decode($paramsInput['goodsChecked']), true);
        if (empty($paramsInput['goodsChecked'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品'));
        }
        foreach ($paramsInput['goodsChecked'] as $item) {
            if (empty($item['goodsId'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
            }
            if (empty($item['purchase_type']) || empty($item['purchaser_or_supplier_id']) || !in_array($item['purchase_type'], array(1, 2))) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请补全页面中的采购类型信息'));
            }
        }
        $mod = new OrdersModel();
        $result = $mod->summaryToPurcahseCreate($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 订单-打印-递增打印数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gdga2c
     * */
    public function incOrdersPrintNum()
    {
        $loginInfo = $this->MemberVeri();
        $orderId = (int)I('orderId');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new OrdersModel();
        $result = $mod->incOrdersPrintNum($loginInfo, $orderId);
        $this->ajaxReturn($result);
    }
}

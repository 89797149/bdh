<?php
namespace V3\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单服务类
 */
class OrdersModel extends BaseModel {

    /**
     * 获以订单列表
     */
    public function getOrdersList($obj){
        $userId = $obj["userId"];
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders WHERE userId = $userId AND orderStatus <>-1 order by createTime desc";
        return $m->pageQuery($sql);
    }

    /**
     * 取消订单记录
     */
    public function getcancelOrderList($obj){
        $userId = $obj["userId"];
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders WHERE userId = $userId AND orderStatus =-1 order by createTime desc";
        return $m->pageQuery($sql);
    }

    /**
     * 获取订单详情
     */
    public function getOrdersDetails($obj){
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
    public function getOrdersGoods($obj){

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
    public function getOrdersGoodsDetails($obj){

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
    public function getPayOrders($obj){
        $orderType = (int)$obj["orderType"];
        $orderId = 0;
        $orderunique = 0;
        if($orderType>0){//来在线支付接口
            $uniqueId = $obj["uniqueId"];
            if($orderType==1){
                $orderId = (int)$uniqueId;
            }else{
                $orderunique = WSTAddslashes($uniqueId);
            }
        }else{
            $orderId = (int)$obj["orderId"];
            $orderunique = session("WST_ORDER_UNIQUE");
        }

        if($orderId>0){
            $sql = "SELECT o.orderId, o.orderNo, g.goodsId, g.goodsName ,og.goodsAttrName , og.goodsNums ,og.goodsPrice
				FROM __PREFIX__order_goods og, __PREFIX__goods g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.goodsId = g.goodsId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.needPay>0 AND o.orderStatus = -2 AND o.orderId =$orderId";
        }else{
            $sql = "SELECT o.orderId, o.orderNo, g.goodsId, g.goodsName ,og.goodsAttrName , og.goodsNums ,og.goodsPrice
				FROM __PREFIX__order_goods og, __PREFIX__goods g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.goodsId = g.goodsId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.needPay>0 AND o.orderStatus = -2 AND o.orderunique ='$orderunique'";
        }

        $rslist = $this->query($sql);

        $orders = array();
        foreach ($rslist as $key => $order) {
            $orders[$order["orderNo"]][] = $order;
        }
        if($orderId>0){
            $sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE orderId = $orderId AND isPay=0 AND payType=1 AND needPay>0 AND orderStatus = -2 AND orderFlag =1";
        }else{
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
    public function submitOrder(){
        $rd = array('status'=>-1);
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
        $orderunique = WSTGetMillisecond().$userId;

        $sql = "select * from __PREFIX__cart where userId = $userId and isCheck=1 and goodsCnt>0";
        $shopcart = $this->query($sql);

        $catgoods = array();
        $order = array();
        if(empty($shopcart)){
            $rd['msg'] = '购物车为空!';
            return $rd;
        }else{
            //整理及核对购物车数据
            $paygoods = session('WST_PAY_GOODS');
            $cartIds = array();
            for($i=0;$i<count($shopcart);$i++){
                $cgoods = $shopcart[$i];
                $goodsId = (int)$cgoods["goodsId"];
                $goodsAttrId = (int)$cgoods["goodsAttrId"];

                if(in_array($goodsId, $paygoods)){
                    $goods = $goodsmodel->getGoodsSimpInfo($goodsId,$goodsAttrId);
                    //核对商品是否符合购买要求
                    if(empty($goods)){
                        $rd['msg'] = '找不到指定的商品!';
                        return $rd;
                    }
                    if($goods['goodsStock']<=0){
                        $rd['msg'] = '对不起，商品'.$goods['goodsName'].'库存不足!';
                        return $rd;
                    }
                    if($goods['isSale']!=1){
                        $rd['msg'] = '对不起，商品库'.$goods['goodsName'].'已下架!';
                        return $rd;
                    }
                    $goods["cnt"] = $cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["shopgoods"][] = $goods;
                    $catgoods[$goods["shopId"]]["deliveryFreeMoney"] = $goods["deliveryFreeMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["deliveryMoney"] = $goods["deliveryMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["totalCnt"] = $catgoods[$goods["shopId"]]["totalCnt"]+$cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["totalMoney"] = $catgoods[$goods["shopId"]]["totalMoney"]+($goods["cnt"]*$goods["shopPrice"]);
                    $cartIds[] = $cgoods["cartId"];
                }
            }
            $morders->startTrans();
            try{
                $ordersInfo = $morders->addOrders($userId,$consigneeId,$payway,$needreceipt,$catgoods,$orderunique,$isself);
                $morders->commit();
                if(!empty($cartIds)){
                    $sql = "delete from __PREFIX__cart where userId = $userId and cartId in (".implode(",",$cartIds).")";
                    $this->execute($sql);
                }
                $rd['orderIds'] = implode(",",$ordersInfo["orderIds"]);
                $rd['status'] = 1;
                session("WST_ORDER_UNIQUE",$orderunique);
            }catch(Exception $e){
                $morders->rollback();
                $rd['msg'] = '下单出错，请联系管理员!';
            }
            return $rd;
        }
    }

    /**
     * 生成订单
     */
    public function addOrders($userId,$consigneeId,$payway,$needreceipt,$catgoods,$orderunique,$isself){

        $orderInfos = array();
        $orderIds = array();
        $orderNos = array();
        $remarks = I("remarks");

        $addressInfo = UserAddressModel::getAddressDetails($consigneeId);
        $m = M('orderids');

        //获取会员奖励积分倍数
        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);

        foreach ($catgoods as $key=> $shopgoods){
            //生成订单ID
            $orderSrcNo = $m->add(array('rnd'=>microtime(true)));
            $orderNo = $orderSrcNo."".(fmod($orderSrcNo,7));
            //创建订单信息
            $data = array();
            $pshopgoods = $shopgoods["shopgoods"];
            $shopId = $pshopgoods[0]["shopId"];
            $data["orderNo"] = $orderNo;
            $data["shopId"] = $shopId;
            $deliverType = intval($pshopgoods[0]["deliveryType"]);
            $data["userId"] = $userId;

            $data["orderFlag"] = 1;
            $data["totalMoney"] = $shopgoods["totalMoney"];
            if($isself==1){//自提
                $deliverMoney = 0;
            }else{
                $deliverMoney = ($shopgoods["totalMoney"]<$shopgoods["deliveryFreeMoney"])?$shopgoods["deliveryMoney"]:0;
            }
            $data["deliverMoney"] = $deliverMoney;
            $data["payType"] = $payway;
            $data["deliverType"] = $deliverType;
            $data["userName"] = $addressInfo["userName"];
            $data["areaId1"] = $addressInfo["areaId1"];
            $data["areaId2"] = $addressInfo["areaId2"];
            $data["areaId3"] = $addressInfo["areaId3"];
            $data["communityId"] = $addressInfo["communityId"];
            $data["userAddress"] = $addressInfo["paddress"]." ".$addressInfo["address"];
            $data["userTel"] = $addressInfo["userTel"];
            $data["userPhone"] = $addressInfo["userPhone"];

            //$data['orderScore'] = floor($data["totalMoney"])*$rewardScoreMultiple;
			$data['orderScore'] = getOrderScoreByOrderScoreRate($data["totalMoney"]);
            $data["isInvoice"] = $needreceipt;
            $data["orderRemarks"] = $remarks;
            $data["requireTime"] = I("requireTime");
            $data["invoiceClient"] = I("invoiceClient");
            $data["isAppraises"] = 0;
            $data["isSelf"] = $isself;

            $isScorePay = (int)I("isScorePay",0);
            $scoreMoney = 0;
            $useScore = 0;

            $shop_info = D('Home/Shops')->getShopInfo($shopId);
            if (!empty($shop_info) && $shop_info['commissionRate'] > 0) {
                $data["poundageRate"] = (float)$shop_info['commissionRate'];
                $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100,0,2);
            } else if($GLOBALS['CONFIG']['poundageRate']>0){
                $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
                $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100,0,2);
            }else{
                $data["poundageRate"] = 0;
                $data["poundageMoney"] = 0;
            }
            if($GLOBALS['CONFIG']['isOpenScorePay']==1 && $isScorePay==1){//积分支付
                $baseScore = WSTOrderScore();
                $baseMoney = WSTScoreMoney();
                $sql = "select userId,userScore from __PREFIX__users where userId=$userId";
                $user = $this->queryRow($sql);
                $useScore = $baseScore*floor($user["userScore"]/$baseScore);
                $scoreMoney = $baseMoney*floor($user["userScore"]/$baseScore);
                $orderTotalMoney = $shopgoods["totalMoney"]+$deliverMoney;
                if($orderTotalMoney<$scoreMoney){//订单金额小于积分金额
                    $useScore = $baseScore*floor($orderTotalMoney/$baseMoney);
                    $scoreMoney = $baseMoney*floor($orderTotalMoney/$baseMoney);
                }
                $data["useScore"] = $useScore;
                $data["scoreMoney"] = $scoreMoney;
            }
            $data["realTotalMoney"] = $shopgoods["totalMoney"]+$deliverMoney - $scoreMoney;
            $data["needPay"] = $shopgoods["totalMoney"]+$deliverMoney - $scoreMoney;

            $data["createTime"] = date("Y-m-d H:i:s");
            if($payway==1){
                $data["orderStatus"] = -2;
            }else{
                $data["orderStatus"] = 0;
            }

            $data["orderunique"] = $orderunique;
            $data["isPay"] = 0;
            if($data["needPay"]==0){
                $data["isPay"] = 1;
            }

            $morders = M('orders');
            $orderId = $morders->add($data);

            //订单创建成功则建立相关记录
            if($orderId>0){

                if($GLOBALS['CONFIG']['isOpenScorePay']==1 && $isScorePay==1 && $useScore>0){//积分支付
                    $sql = "UPDATE __PREFIX__users set userScore=userScore-".$useScore." WHERE userId=".$userId;
                    $rs = $this->execute($sql);

                    $data = array();
                    $m = M('user_score');
                    $data["userId"] = $userId;
                    $data["score"] = $useScore;
                    $data["dataSrc"] = 1;
                    $data["dataId"] = $orderId;
                    $data["dataRemarks"] = "订单支付-扣积分";
                    $data["scoreType"] = 2;
                    $data["createTime"] = date('Y-m-d H:i:s');
                    $m->add($data);
                }

                $orderIds[] = $orderId;
                //建立订单商品记录表
                $mog = M('order_goods');
                foreach ($pshopgoods as $key=> $sgoods){
                    $data = array();
                    $data["orderId"] = $orderId;
                    $data["goodsId"] = $sgoods["goodsId"];
                    $data["goodsAttrId"] = (int)$sgoods["goodsAttrId"];
                    if($sgoods["attrVal"]!='')$data["goodsAttrName"] = $sgoods["attrName"].":".$sgoods["attrVal"];
                    $data["goodsNums"] = $sgoods["cnt"];
                    $data["goodsPrice"] = $sgoods["shopPrice"];
                    $data["goodsName"] = $sgoods["goodsName"];
                    $data["goodsThums"] = $sgoods["goodsThums"];
                    $mog->add($data);
                }

                if($payway==0){
                    //建立订单记录
//                    $data = array();
//                    $data["orderId"] = $orderId;
//                    $data["logContent"] = ($pshopgoods[0]["deliverType"]==0)? "下单成功":"下单成功等待审核";
//                    $data["logUserId"] = $userId;
//                    $data["logType"] = 0;
//                    $data["logTime"] = date('Y-m-d H:i:s');
//                    $mlogo = M('log_orders');
//                    $mlogo->add($data);
                    $content = ($pshopgoods[0]["deliverType"]==0)? "下单成功":"下单成功等待审核";
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $userId,
                        'logUserName' => '用户',
                        'orderStatus' => $data["orderStatus"],
                        'payStatus' => 1,
                        'logType' => 0,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                    //建立订单提醒
                    $sql ="SELECT userId,shopId,shopName FROM __PREFIX__shops WHERE shopId=$shopId AND shopFlag=1  ";
                    $users = $this->query($sql);
                    $morm = M('order_reminds');
                    for($i=0;$i<count($users);$i++){
                        $data = array();
                        $data["orderId"] = $orderId;
                        $data["shopId"] = $shopId;
                        $data["userId"] = $users[$i]["userId"];
                        $data["userType"] = 0;
                        $data["remindType"] = 0;
                        $data["createTime"] = date("Y-m-d H:i:s");
                        $morm->add($data);
                    }

                    //修改库存
                    foreach ($pshopgoods as $key=> $sgoods){
						$sgoods_cnt = gChangeKg($sgoods["goodsId"],$sgoods['cnt'],1);
                        $sql="update __PREFIX__goods set goodsStock=goodsStock-".$sgoods_cnt." where goodsId=".$sgoods["goodsId"];
                        $this->execute($sql);

                        //更新进销存系统商品的库存
                        //updateJXCGoodsStock($sgoods["goodsId"],$sgoods['cnt'],1);

                        if((int)$sgoods["goodsAttrId"]>0){
                            $sql="update __PREFIX__goods_attributes set attrStock=attrStock-".$sgoods_cnt." where id=".$sgoods["goodsAttrId"];
                            $this->execute($sql);
                        }
                    }
                }else{
//                    $data = array();
//                    $data["orderId"] = $orderId;
//                    $data["logContent"] = "订单已提交，等待支付";
//                    $data["logUserId"] = $userId;
//                    $data["logType"] = 0;
//                    $data["logTime"] = date('Y-m-d H:i:s');
//                    $mlogo = M('log_orders');
//                    $mlogo->add($data);
                    $content = '订单已提交，等待支付';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => $userId,
                        'logUserName' => '用户',
                        'orderStatus' => -2,
                        'payStatus' => 0,
                        'logType' => 0,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                }
            }
        }

        return array("orderIds"=>$orderIds);
    }

    /**
     * 获取订单参数
     */
    public function getOrderListByIds(){
        $orderunique = session("WST_ORDER_UNIQUE");
        $orderInfos = array('totalMoney'=>0,'isMoreOrder'=>0,'list'=>array());
        $sql = "select orderId,orderNo,totalMoney,deliverMoney,realTotalMoney
		         from __PREFIX__orders where userId=".(int)session('WST_USER.userId')."
		         and orderunique='".$orderunique."' and orderFlag=1 ";
        $rs = $this->query($sql);
        if(!empty($rs)){
            $totalMoney = 0;
            $realTotalMoney = 0;
            foreach ($rs as $key =>$v){
                $orderInfos['list'][] = array('orderId'=>$v['orderId'],'orderNo'=>$v['orderNo']);
                $totalMoney += $v['totalMoney'] + $v['deliverMoney'];
                $realTotalMoney += $v['realTotalMoney'];
            }
            $orderInfos['totalMoney'] = $totalMoney;
            $orderInfos['realTotalMoney'] = $realTotalMoney;
            $orderInfos['isMoreOrder'] = (count($rs)>0)?1:0;
        }
        return $orderInfos;
    }

    /**
     * 获取待付款订单
     */
    public function queryByPage($obj){
        $userId = $obj["userId"];
        $pcurr = (int)I("pcurr",0);
        $sql = "SELECT o.* FROM __PREFIX__orders o
				WHERE userId = $userId AND orderFlag=1 order by orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryPayByPage($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus",0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr",0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus =-2 AND o.isPay = 0 AND needPay >0 AND o.payType = 1 AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryReceiveByPage($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus",0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr",0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp WHERE o.userId = $userId AND o.orderStatus =3 AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryDeliveryByPage($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus",0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr",0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus in ( 0,1,2 ) AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);

        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryRefundByPage($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus",0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $sdate = WSTAddslashes(I("sdate"));
        $edate = WSTAddslashes(I("edate"));
        $pcurr = (int)I("pcurr",0);
        //必须是在线支付的才允许退款

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp
		        WHERE o.userId = $userId AND (o.orderStatus in (-3,-4,-5) or (o.orderStatus in (-1,-4,-6,-7) and payType =1 AND o.isPay =1)) AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryCancelOrders($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $orderStatus = (int)I("orderStatus",0);
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr",0);

        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName
		        FROM __PREFIX__orders o,__PREFIX__shops sp
		        WHERE o.userId = $userId AND o.orderStatus in (-1,-6,-7) AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
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
    public function queryAppraiseByPage($obj){
        $userId = (int)$obj["userId"];
        $orderNo = WSTAddslashes(I("orderNo"));
        $goodsName = WSTAddslashes(I("goodsName"));
        $shopName = WSTAddslashes(I("shopName"));
        $userName = WSTAddslashes(I("userName"));
        $pcurr = (int)I("pcurr",0);
        $sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp WHERE o.userId = $userId AND o.shopId=sp.shopId ";
        if($orderNo!=""){
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND o.userName like '%$userName%'";
        }
        if($shopName!=""){
            $sql .= " AND sp.shopName like '%$shopName%'";
        }
        $sql .= " AND o.orderStatus = 4";
        $sql .= " order by o.orderId desc";
        $pages = $this->pageQuery($sql,$pcurr);
        $orderList = $pages["root"];
        if(count($orderList)>0){
            $orderIds = array();
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $orderIds[] = $order["orderId"];
            }
            //获取涉及的商品
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsThums,og.orderId FROM __PREFIX__order_goods og
					WHERE og.orderId in (".implode(',',$orderIds).")";
            $glist = $this->query($sql);
            $goodslist = array();
            for($i=0;$i<count($glist);$i++){
                $goods = $glist[$i];
                $goodslist[$goods["orderId"]][] = $goods;
            }
            //放回分页数据里
            for($i=0;$i<count($orderList);$i++){
                $order = $orderList[$i];
                $order["goodslist"] = $goodslist[$order['orderId']];
                $pages["root"][$i] = $order;
            }
        }
        return $pages;
    }

    /**
     * 取消订单
     */
    public function orderCancel($obj){
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $rsdata = array('status'=>-1);
        //判断订单状态，只有符合状态的订单才允许改变
        $sql = "SELECT orderId,orderNo,orderStatus,useScore FROM __PREFIX__orders WHERE orderId = $orderId and orderFlag = 1 and userId=".$userId;
        $rsv = $this->queryRow($sql);
        $cancelStatus = array(0,1,2,-2);//未受理,已受理,打包中,待付款订单
        if(!in_array($rsv["orderStatus"], $cancelStatus))return $rsdata;
        //如果是未受理和待付款的订单直接改为"用户取消【受理前】"，已受理和打包中的则要改成"用户取消【受理后-商家未知】"，后者要给商家知道有这么一回事，然后再改成"用户取消【受理后-商家已知】"的状态
        $orderStatus = -6;//取对商家影响最小的状态
        if($rsv["orderStatus"]==0 || $rsv["orderStatus"]==-2)$orderStatus = -1;
        if($orderStatus==-6 && I('rejectionRemarks')=='')return $rsdata;//如果是受理后取消需要有原因
        $sql = "UPDATE __PREFIX__orders set orderStatus = ".$orderStatus." WHERE orderId = $orderId and userId=".$userId;
        $rs = $this->execute($sql);

        $sql = "select ord.deliverType, ord.orderId, og.goodsId ,og.goodsId, og.goodsNums
				from __PREFIX__orders ord , __PREFIX__order_goods og
				WHERE ord.orderId = og.orderId AND ord.orderId = $orderId";
        $ogoodsList = $this->query($sql);
        //获取商品库存
        for($i=0;$i<count($ogoodsList);$i++){
            $sgoods = $ogoodsList[$i];
			$sgoods_goodsNums = gChangeKg($sgoods["goodsId"],$sgoods['goodsNums'],1);
            $sql="update __PREFIX__goods set goodsStock=goodsStock+".$sgoods_goodsNums." where goodsId=".$sgoods["goodsId"];
            $this->execute($sql);

            //更新进销存系统商品的库存
            //updateJXCGoodsStock($sgoods["goodsId"],$sgoods['goodsNums'],0);

        }
        $sql="Delete From __PREFIX__order_reminds where orderId=".$orderId." AND remindType=0";
        $this->execute($sql);

        if($rsv["useScore"]>0){
            $sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["useScore"]." WHERE userId=".$userId;
            $this->execute($sql);

            $data = array();
            $m = M('user_score');
            $data["userId"] = $userId;
            $data["score"] = $rsv["useScore"];
            $data["dataSrc"] = 3;
            $data["dataId"] = $orderId;
            $data["dataRemarks"] = "取消订单返还";
            $data["scoreType"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $m->add($data);
        }
        $data = array();
        $m = M('log_orders');
        $data["orderId"] = $orderId;
        $data["logContent"] = "用户已取消订单".(($orderStatus==-6)?"：".I('rejectionRemarks'):"");
        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;
        return $rsdata;
    }

    /**
     * 用户确认收货
     */
    public function orderConfirm ($obj){
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $type = (int)$obj["type"];
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderScore,orderStatus,poundageRate,poundageMoney,shopId,useScore,scoreMoney FROM __PREFIX__orders WHERE orderId = $orderId and userId=".$userId;
        $rsv = $this->queryRow($sql);
        if($rsv["orderStatus"]!=3){
            $rsdata["status"] = -1;
            return $rsdata;
        }
        //收货则给用户增加积分
        if($type==1){
            $sql = "UPDATE __PREFIX__orders set orderStatus = 4,receiveTime='".date("Y-m-d H:i:s")."'  WHERE orderId = $orderId and userId=".$userId;
            $rs = $this->execute($sql);

            //修改商品销量
            $sql = "UPDATE __PREFIX__goods g, __PREFIX__order_goods og, __PREFIX__orders o SET g.saleCount=g.saleCount+og.goodsNums WHERE g.goodsId= og.goodsId AND og.orderId = o.orderId AND o.orderId=$orderId AND o.userId=".$userId;
            $rs = $this->execute($sql);

            //修改积分
            if($GLOBALS['CONFIG']['isOrderScore']==1 && $rsv["orderScore"]>0){
                $sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["orderScore"].",userTotalScore=userTotalScore+".$rsv["orderScore"]." WHERE userId=".$userId;
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
            if($rsv["scoreMoney"]>0){
                $data = array();
                $m = M('log_sys_moneys');
                $data["targetType"] = 0;
                $data["targetId"] = $userId;
                $data["dataSrc"] = 2;
                $data["dataId"] = $orderId;
                $data["moneyRemark"] = "订单【".$rsv["orderNo"]."】支付 ".$rsv["useScore"]." 个积分，支出 ￥".$rsv["scoreMoney"];
                $data["moneyType"] = 2;
                $data["money"] = $rsv["scoreMoney"];
                $data["createTime"] = date('Y-m-d H:i:s');
                $data["dataFlag"] = 1;
                $m->add($data);
            }
            //收取订单佣金
            if($rsv["poundageMoney"]>0){
                $data = array();
                $m = M('log_sys_moneys');
                $data["targetType"] = 1;
                $data["targetId"] = $rsv["shopId"];
                $data["dataSrc"] = 1;
                $data["dataId"] = $orderId;
                $data["moneyRemark"] = "收取订单【".$rsv["orderNo"]."】".$rsv["poundageRate"]."%的佣金 ￥".$rsv["poundageMoney"];
                $data["moneyType"] = 1;
                $data["money"] = $rsv["poundageMoney"];
                $data["createTime"] = date('Y-m-d H:i:s');
                $data["dataFlag"] = 1;
                $m->add($data);
            }

        }else{
            if(I('rejectionRemarks')=='')return $rsdata;//如果是拒收的话需要填写原因
            $sql = "UPDATE __PREFIX__orders set orderStatus = -3 WHERE orderId = $orderId and userId=".$userId;
            $rs = $this->execute($sql);
        }
        //增加记录
        $data = array();
        $m = M('log_orders');
        $data["orderId"] = $orderId;
        $data["logContent"] = ($type==1)?"用户已收货":"用户拒收：".I('rejectionRemarks');
        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;;
        return $rsdata;
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetails($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=".$userId." or shopId=".$shopId.")";
        $order = $this->queryRow($sql);
        if(empty($order))return $data;
        $data["order"] = $order;
        $sql = "select og.orderId, og.goodsId ,g.goodsSn,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        $data["goodsList"] = $goods;

        $sql = "SELECT * FROM __PREFIX__log_orders WHERE orderId = $orderId ";
        $logs = $this->query($sql);
        $data["logs"] = $logs;
        //发票详情
        $data['invoiceInfo'] = [];
        if(isset($order['isInvoice']) && $order['isInvoice'] == 1){
            $data['invoiceInfo'] = M('invoice')->where("id='".$order['invoiceClient']."'")->find();
        }
        return $data;
    }

    /**
     * 获取订单过秤数据
     */
    public function getOrderElectronicScaleList($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=".$userId." or shopId=".$shopId.")";
        $order = $this->queryRow($sql);
        if(empty($order))return $data;
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
    public function getUserOrderStatusCount($obj){
        $userId = (int)$obj["userId"];
        $data = array();
        $sql = "select orderStatus,COUNT(*) cnt from __PREFIX__orders WHERE orderStatus in (0,1,2,3) and orderFlag=1 and userId = $userId GROUP BY orderStatus";
        $olist = $this->query($sql);
        $data = array('-3'=>0,'-2'=>0,'2'=>0,'3'=>0,'4'=>0);
        for($i=0;$i<count($olist);$i++){
            $row = $olist[$i];
            if($row["orderStatus"]==0 || $row["orderStatus"]==1 || $row["orderStatus"]==2){
                $row["orderStatus"] = 2;
            }
            $data[$row["orderStatus"]] = $data[$row["orderStatus"]]+$row["cnt"];
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
        $sql = "select count(*) cnt from __PREFIX__messages WHERE  receiveUserId=".$userId." and msgStatus=0 and msgFlag=1 ";
        $olist = $this->query($sql);
        $data[100000] = empty($olist)?0:$olist[0]['cnt'];

        return $data;
    }

    /**
     * 获取用户指定状态的订单数目
     */
    public function getShopOrderStatusCount($obj){
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
        $rsdata[100] = $rsdata[0]+$rsdata[5];

        //获取商城信息
        $sql = "select count(*) cnt from __PREFIX__messages WHERE  receiveUserId=".(int)$obj["userId"]." and msgStatus=0 and msgFlag=1 ";
        $olist = $this->query($sql);
        $rsdata[100000] = empty($olist)?0:$olist[0]['cnt'];

        return $rsdata;

    }


    /**
     * 获取商家订单列表
     */
    public function queryShopOrders($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $pcurr = (int)I("pcurr",0);
        $orderStatus = (int)I("statusMark");

        $orderNo = WSTAddslashes(I("orderNo"));
        $userName = WSTAddslashes(I("userName"));
        $userAddress = WSTAddslashes(I("userAddress"));
        $rsdata = array();
        $sql = "SELECT orderNo,isSelf,orderId,userId,userName,userAddress,totalMoney,realTotalMoney,orderStatus,createTime FROM __PREFIX__orders WHERE shopId = $shopId ";
        if($orderStatus==5){
            $sql.=" AND orderStatus in (-3,-4,-5,-6,-7)";
        }elseif($orderStatus==7){
            $sql.=" AND orderStatus in (7,8,10)";
        }elseif($orderStatus==8){
            $sql.=" AND orderStatus in (13,14)";
        }else{
            $sql.=" AND orderStatus = $orderStatus ";
        }

        if($orderNo!=""){
            $sql .= " AND orderNo like '%$orderNo%'";
        }
        if($userName!=""){
            $sql .= " AND userName like '%$userName%'";
        }
        if($userAddress!=""){
            $sql .= " AND userAddress like '%$userAddress%'";
        }
        $sql.=" order by orderId desc ";
        $data = $this->pageQuery($sql,$pcurr);
        //获取取消/拒收原因
        $orderIds = array();
        $noReadrderIds = array();
        foreach ($data['root'] as $key => $v){
            if($v['orderStatus']==-6)$noReadrderIds[] = $v['orderId'];
            $sql = "select logContent from __PREFIX__log_orders where orderId =".$v['orderId']." and logType=0 and logUserId=".$v['userId']." order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
        }

        //要对用户取消【-6】的状态进行处理,表示这一条取消信息商家已经知道了
        // if($orderStatus==5 && count($noReadrderIds)>0){
        // 	$sql = "UPDATE __PREFIX__orders set orderStatus=-7 WHERE shopId = $shopId AND orderId in (".implode(',',$noReadrderIds).")AND orderStatus = -6 ";
        // 	$this->execute($sql);
        // }//2019 手动读取
        return $data;
    }

    /**
     * 商家受理订单-只能受理【未受理】的订单
     */
//    public function shopOrderAccept ($obj){
//        $userId = (int)$obj["userId"];
//        $orderId = (int)$obj["orderId"];
//        $shopId = (int)$obj["shopId"];
//        $rsdata = array();
//        $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=".$shopId;
//        $rsv = $this->queryRow($sql);
//        if($rsv["orderStatus"]!=0){
//            $rsdata["status"] = -1;
//            return $rsdata;
//        }
//
//        $sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderId = $orderId and shopId=".$shopId;
//        $rs = $this->execute($sql);
//
////        $data = array();
////        $m = M('log_orders');
////        $data["orderId"] = $orderId;
////        $data["logContent"] = "商家已受理订单";
////        $data["logUserId"] = $userId;
////        $data["logType"] = 0;
////        $data["logTime"] = date('Y-m-d H:i:s');
////        $ra = $m->add($data);
//
//        //事务处理订单日志
//        $logOrderDB = db('log_orders');
//        $logOrderDB->startTrans();
//        try{
//            // 提交事务
//            $data = array();
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "商家已受理订单";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $ra = $logOrderDB->insert($data);
//            $logOrderDB::commit();
//        } catch (\Exception $e) {
//            //回滚事务
//            Db::rollback();
//        }
//
//        $rsdata["status"] = $ra;
//        return $rsdata;
//    }

    /**
     * 商家批量受理订单-只能受理【未受理】的订单
     */
    public function batchShopOrderAccept($parameter = array()){
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId?$userId:$parameter['userId'];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId?$shopId:$parameter['shopId'];
        if($orderIds=='')return array('status'=>-2);
        $orderIds = explode(',',$orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        foreach ($orderIds as $orderId){
            if($orderId=='')continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=".$shopId;
            $rsv = $this->queryRow($sql);

            //订单状态不符合则跳过 未受理或预售订单-已付款
            if($rsv["orderStatus"]!=0){
                if($rsv["orderStatus"] != 14){
                    continue;
                }
            }

            $sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);

//            $data = array();
//            $m = M('log_orders');
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "商家已受理订单";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $ra = $m->add($data);

            //事务处理订单日志
            $logOrderDB = db('log_orders');
            $logOrderDB->startTrans();
            try{
                // 提交事务
                $data = array();
                $data["orderId"] = $orderId;
                $data["logContent"] = "商家已受理订单";
                $data["logUserId"] = $userId;
                $data["logType"] = 0;
                $data["logTime"] = date('Y-m-d H:i:s');
                $ra = $logOrderDB->insert($data);
                $logOrderDB::commit();
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
            }

            $editOrderNum++;
        }
        if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
        if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
        return array('status'=>1);
    }

    /**
     * 商家打包订单-只能处理[受理]的订单
     */
    public function shopOrderProduce ($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
        $rsv = $this->queryRow($sql);
        if($rsv["orderStatus"]!=1){
            $rsdata["status"] = -1;
            return $rsdata;
        }

        $sql = "UPDATE __PREFIX__orders set orderStatus = 2 WHERE orderId = $orderId and shopId=".$shopId;
        $rs = $this->execute($sql);
//        $data = array();
//        $m = M('log_orders');
//        $data["orderId"] = $orderId;
//        $data["logContent"] = "订单打包中";
//        $data["logUserId"] = $userId;
//        $data["logType"] = 0;
//        $data["logTime"] = date('Y-m-d H:i:s');
//        $ra = $m->add($data);
        //事务
        $logOrderDB = db('log_orders');
        $logOrderDB->startTrans();
        try{
            // 提交事务
            $data = array();
            $data["orderId"] = $orderId;
            $data["logContent"] = "订单打包中";
            $data["logUserId"] = $userId;
            $data["logType"] = 0;
            $data["logTime"] = date('Y-m-d H:i:s');
            $ra = $logOrderDB->add($data);
            $logOrderDB::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
        }

        $rsdata["status"] = $ra;;
        return $rsdata;
    }

    /**
     * 商家批量打包订单-只能处理[受理]的订单
     */
    public function batchShopOrderProduce ($parameter=array()){
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId?$userId:$parameter['userId'];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId?$shopId:$parameter['shopId'];

        if($orderIds=='')return array('status'=>-2);
        $orderIds = explode(',',$orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        foreach ($orderIds as $orderId){
            if($orderId=='')continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
            $rsv = $this->queryRow($sql);
            if($rsv["orderStatus"]!=1)continue;//订单状态不符合则跳过

            $sql = "UPDATE __PREFIX__orders set orderStatus = 2 WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);
//            $data = array();
//            $m = M('log_orders');
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "订单打包中";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $ra = $m->add($data);

            $logOrderDB = db('log_orders');
            $logOrderDB->startTrans();
            try{
                // 提交事务
                $data = array();
                $data["orderId"] = $orderId;
                $data["logContent"] = "订单打包中";
                $data["logUserId"] = $userId;
                $data["logType"] = 0;
                $data["logTime"] = date('Y-m-d H:i:s');
                $ra = $logOrderDB->insert($data);
                $logOrderDB::commit();
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
            }

            $editOrderNum++;
        }
        if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
        if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
        return array('status'=>1);
    }

    /**
     * 使用达达预发布并提交订单
     */
    static function DaqueryDeliverFee($obj){

        $mod_orders = M('orders');
        $order_areas_mod = M('areas');
        $mod_log_orders = M('log_orders');
        //orders表里的字段 updateTime设置为空
        $order_save['updateTime'] = null;
        $order_save['deliverType'] = 2;//达达配送
        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($order_save);
        $mod_orders_data = $mod_orders->where("orderId = '{$obj["orderId"]}'")->find();//当前订单数据

        //判断当前订单是否在达达覆盖范围城市内

        $mod_shops = M('shops');
        $shops_res = $mod_shops->where('shopId='.$mod_orders_data['shopId'])->find();

        $dadaShopId = $shops_res['dadaShopId'];
        $dadaOriginShopId = $shops_res['dadaOriginShopId'];

        $dadam = D("Home/dada");
        $dadamod = $dadam->cityCodeList(null,$dadaShopId);//线上环境
// 		$dadamod = $dadam->cityCodeList(null,73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------

        if(!empty($dadamod['niaocmsstatic'])){
            $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'获取城市出错#'.$dadamod['info']);//获取城市出错
            return $rd;
        }

        $cityNameisWx = str_replace(array('省','市'),'',$order_areas_mod->where("areaId = '{$mod_orders_data["areaId2"]}'")->field('areaName')->find()['areaName']);
        //判断当前是否在达达覆盖范围内
        for($i=0;$i<=count($dadamod)-1;$i++){
            if($cityNameisWx == $dadamod[$i]['cityName']){//如果在配送范围

                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
                $txt ='在达达覆盖范围';
                fwrite($myfile, $txt.'\n');
                fclose($myfile);

                //进行订单预发布

                //备参

                $DaDaData = array(
                    'shop_no'=> $dadaOriginShopId,//	门店编号，门店创建后可在门店列表和单页查看
                    // 	'shop_no'=> '11047059',//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
                    'origin_id'=> $mod_orders_data["orderNo"],//第三方订单ID
                    'city_code'=> $dadamod[$i]['cityCode'],//	订单所在城市的code（查看各城市对应的code值）
                    'cargo_price'=> $mod_orders_data["totalMoney"],//	订单金额 不加运费
                    'is_prepay'=> 0,//	是否需要垫付 1:是 0:否 (垫付订单金额，非运费)
                    'receiver_name'=> $mod_orders_data["userName"],//收货人姓名
                    'receiver_address'=> $mod_orders_data["userAddress"],//	收货人地址
                    'receiver_phone'=> $mod_orders_data["userPhone"],//	收货人手机号
                    'cargo_weight' => 1,
                    'origin_mark_no' => $orderInfo["orderNo"],//订单来源编号，最大长度为30，该字段可以显示在骑士APP订单详情页面
                    // 'callback'=> WSTDomain().'/wstapi/logistics/notify_dada.php' //	回调URL（查看回调说明）
                    'callback' => WSTDomain() . '/Home/dada/dadaOrderCall' //	回调URL（查看回调说明）
                );

                $dada_res_data = $dadam->queryDeliverFee($DaDaData,$dadaShopId);
                // $dada_res_data = $dadam->queryDeliverFee($DaDaData,73753);///测试环境 测试完成 此段删除 开启上行代码-------------------------------------

                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
                $txt =json_encode($dada_res_data);
                fwrite($myfile, "我是发布前的请求#".$txt.'\n');
                fclose($myfile);

                //更改订单某些字段
                $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
                $data['deliveryNo'] = $dada_res_data['deliveryNo'];//来自达达返回的平台订单号

                $data["deliverMoney"] =  $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
                $data["orderStatus"] =  7;//	订单状态
                $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($data);

                //发布订单
                $dadam = D("Home/dada");
                $dadamod = $dadam->addAfterQuery(array('deliveryNo'=>$data['deliveryNo']),$dadaShopId);
                // $dadamod = $dadam->addAfterQuery(array('deliveryNo'=>$data['deliveryNo']),73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------

                if(!empty($dadamod['niaocmsstatic'])){
                    $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'发布订单#'.$dadamod['info']);
                    return $rd;
                }

                //写入订单日志
                $add_data['orderId'] = $obj["orderId"];
                $add_data['logContent'] = '商家已通知达达取货';
                $add_data['logUserId'] = $obj["userId"];
                $add_data['logType'] = 0;
                $add_data['logTime'] = date("Y-m-d H:i:s");
                $res = $mod_log_orders->add($add_data);
                $rsdata["status"] = $res;
                return $rsdata;
            }
        }
    }

    /*
    *快跑者 发布订单
    */
    static function KuaiqueryDeliverFee($obj){
        $mod_orders = M('orders');
        $mod_log_orders = M('log_orders');
        //orders表里的字段 updateTime设置为空
        $order_save['updateTime'] = null;
        $order_save['deliverType'] = 4;//快跑者
        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($order_save);
        $mod_orders_data = $mod_orders->where("orderId = '{$obj["orderId"]}'")->find();//当前订单数据

        // 获取店铺详情数据
        $mod_shops_data = M('shops')->where("shopId = ".$mod_orders_data['shopId'])->find();

        // $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
        // $txt =json_encode($dada_res_data);
        // fwrite($myfile, "我是发布前的请求#".$txt.'\n');
        // fclose($myfile);

        //创建订单 并获取订单详情
        $M_Kuaipao = D("Home/Kuaipao");
        $M_data_Kuaipao = array(
            'team_token'=>$mod_shops_data['team_token'],
            'shop_id'=>$mod_shops_data['shopId'],
            'shop_name'=>$mod_shops_data['shopName'],
            'shop_tel'=>$mod_shops_data['shopTel'],
            'shop_address'=>$mod_shops_data['shopAddress'],
            'shop_tag'=>"{$mod_shops_data['longitude']},{$mod_shops_data['latitude']}",
            'customer_name'=>$mod_orders_data['userName'],
            'customer_tel'=>$mod_orders_data['userTel'],
            'customer_address'=>$mod_orders_data['userAddress'],
            'order_no' => $mod_orders_data['orderNo'],
            'pay_status'=>0,
        );
        $M_res_Kuaipao = null;
        $M_info_Kuaipao = null;
        $M_error_Kuaipao= null;
        $M_Kuaipao->createOrder($M_data_Kuaipao,$M_res_Kuaipao,$M_info_Kuaipao,$M_error_Kuaipao);

        if(!empty($M_res_Kuaipao)){//如果成功
            $dada_res_data['deliveryNo'] = $M_res_Kuaipao['trade_no'];

            //查询订单详细信息
            $getOrderInfo_res = null;
            $getOrderInfo_info = null;
            $getOrderInfo_error = null;
            $M_Kuaipao->getOrderInfo($M_res_Kuaipao['trade_no'],$getOrderInfo_res,$getOrderInfo_info,$getOrderInfo_error);
            if(!empty($getOrderInfo_res)){
                $dada_res_data['distance'] = (float)$getOrderInfo_res['distance']*1000;
                $dada_res_data['fee'] = $getOrderInfo_res['pay_fee'];
            }
        }

        //更改订单某些字段
        $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
        $data['deliveryNo'] = $dada_res_data['deliveryNo'];//来自跑腿平台返回的平台订单号

        $data["deliverMoney"] =  $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
        $data["orderStatus"] =  7;//	订单状态
        $data["dmName"] =  $dada_res_data['courier_name'];//	骑手姓名
        $data["dmMobile"] =  $dada_res_data['courier_tel'];//	骑手电话


        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($data);

        //写入订单日志
//        $add_data['orderId'] = $obj["orderId"];
//        $add_data['logContent'] = '商家已通知骑手取货';
//        $add_data['logUserId'] = $obj["userId"];
//        $add_data['logType'] = 0;
//        $add_data['logTime'] = date("Y-m-d H:i:s");
//        $res = $mod_log_orders->add($add_data);

        //事务处理订单日志
        $logOrderDB = db('log_orders');
        $logOrderDB->startTrans();
        try{
            // 提交事务
            $add_data = array();
            $add_data['orderId'] = $obj["orderId"];
            $add_data['logContent'] = '商家已通知骑手取货';
            $add_data['logUserId'] = $obj["userId"];
            $add_data['logType'] = 0;
            $add_data['logTime'] = date("Y-m-d H:i:s");
            $res = $logOrderDB->insert($add_data);
            $logOrderDB::commit();
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
        }

        $rsdata["status"] = $res;
        return $rsdata;
    }

    /**
     * 商家发货配送订单
     */
    public function shopOrderDelivery ($obj){
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $shopId = (int)$obj["shopId"];
        $weightGJson = $obj["weightGJson"];

        $source = I('source');
        //$deliverType = (int)$obj["deliverType"];
        $data = array();
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus,deliverType,isSelf,realTotalMoney FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
        $rsv = $this->queryRow($sql);

        //记录需要退款的商品 在确认收货的时候自动退款
        if(count($weightGJson) > 0){
            $order_goods = M('order_goods');
            $goods_pricediffe = M('goods_pricediffe');
            $orders = M('orders');
            $mod_goods = M('goods');
            //验证此笔订单是否包含这些商品
            for($i=0;$i<count($weightGJson);$i++){
                $data_order_goods = $order_goods->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find();
                if(!$data_order_goods){
                    $rsdata["status"] = -1;
                    $rsdata["info"] = '当前订单和商品不匹配';
                    return $rsdata;
                }

                //判断本订单的商品是否已经记录过了
                if($goods_pricediffe->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find()){
                    $rsdata["status"] = -1;
                    $rsdata["info"] = '本订单的商品已经处理过了';
                    return $rsdata;
                }

                $orders_data = $orders->where("orderId = '{$orderId}'")->find();
                $goods_this_data = $mod_goods->where("SuppPriceDiff=1 and goodsId = '{$weightGJson[$i]['goodsId']}'")->find();

                //$totalMoney_order = $data_order_goods['goodsPrice'] * (int)$data_order_goods['goodsNums'];//当前商品总价 原来
                //$totalMoney_order1 = $data_order_goods['goodsPrice'] / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价
                $totalMoney_order = $data_order_goods['goodsPrice'] * (float)$data_order_goods['goodsNums'];//当前商品总价 原来
                $totalMoney_order1 = $data_order_goods['goodsPrice'] / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价
                /* $totalMoney_order = goodsPayN($orderId,$weightGJson[$i]['goodsId'],$userId); //单商品总价
                 $totalMoney_order1 = $totalMoney_order / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价*/
                //如果重量不够 补差价
                if($totalMoney_order > $totalMoney_order1){
                    $add_data['orderId'] = $orderId;
                    $add_data['tradeNo'] = $orders_data['tradeNo'];
                    $add_data['goodsId'] = $weightGJson[$i]['goodsId'];
                    //$add_data['money'] = (($totalMoney_order*1000) - ($totalMoney_order1*1000)) / 1000;
                    //$add_data['money'] = $data_order_goods['goodsPrice']-$totalMoney_order1;
                    //$add_data['money'] = $data_order_goods['goodsPrice']-$totalMoney_order1;//修复差价
                    $add_data['money'] = $totalMoney_order-$totalMoney_order1;//修复差价
                    $add_data['addTime'] = date('Y-m-d H:i:s');
                    $add_data['userId'] = $orders_data['userId'];
                    $add_data['weightG'] = $weightGJson[$i]['goodWeight'];
                    $add_data['goosNum'] = $data_order_goods['goodsNums'];
                    $add_data['unitPrice'] = $data_order_goods['goodsPrice'];

                    //写入退款记录
                    if(!$goods_pricediffe->add($add_data)){
                        $rsdata["status"] = -1;
                        $rsdata["info"] = '退款记录写入失败';
                        return $rsdata;
                    }

                    /*发起退款通知*/
                    $push = D('Adminapi/Push');
                    $push->postMessage(8,$userId,$orders_data['orderNo'],$shopId);


                    $log_orders = M("log_orders");
                    $data["orderId"] =  $orderId;
                    $data["logContent"] =  $data_order_goods['goodsName'] . '#补差价：' . sprintf("%.2f",substr(sprintf("%.3f",$add_data['money']), 0, -1)) .'元。确认收货后返款！';
                    $data["logUserId"] =  $orders_data['userId'];
                    $data["logType"] =  "0";
                    $data["logTime"] =  date("Y-m-d H:i:s");

                    $log_orders->add($data);
                }
            }
        }

        /* 	$people = array('2','10');
            if(!in_array($rsv["orderStatus"],$people) and !in_array($rsv["orderStatus"],$people)){
                $rsdata["status"] = -1;
                return $rsdata;
            } */
        if($rsv["orderStatus"]!='2' and $rsv["orderStatus"]!='10'){
            $rsdata["status"] = -1;
            return $rsdata;
        }

        // if($rsv["deliverType"]==2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0){
        if($rsv["deliverType"]==2 and $rsv["isSelf"] == 0){
            //预发布 并提交达达订单
            $funResData = self::DaqueryDeliverFee($obj);
            return $funResData;
        }

        //自建物流配送
        if($rsv["deliverType"]==4 and $rsv["isSelf"] == 0){
            $funResData = self::KuaiqueryDeliverFee($obj);
            return $funResData;
        }

        $data["logContent"] = "商家已发货";
        if($rsv["isSelf"] == 1 and !empty($source)){//如果是自提 提货码不为空
            //判断是否对的上
            $mod_user_self_goods = M('user_self_goods');
            $mod_user_self_goods_data = $mod_user_self_goods->where('source = ' . $source)->find();
            if($mod_user_self_goods_data['orderId'] !=  $orderId){
                return array('status'=>-1,'info'=>'取货码与订单不符');
            }

            //改为已取货
            $where['id'] = $mod_user_self_goods_data['id'];
            $saveData['onStart'] = 1;
            $saveData['onTime'] = date('Y-m-d H:i:s');
            $mod_user_self_goods->where($where)->save($saveData);

            $data["logContent"] = "用户已自提";
        }

        $sql = "UPDATE __PREFIX__orders set deliverType=1,orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderId = $orderId and shopId=".$shopId;
        $rs = $this->execute($sql);

        //判断是否是首次下单
        //是否奖励邀请券 判断是否是第一次下单(第一笔订单 之前一定是0笔) 且是否拥有邀请人 并邀请人有优惠券待恢复使用
        //if(M('orders')->limit(1)->where("orderStatus=4 and userId = '{$userId}'")->count() == 0){
//        if(M('orders')->limit(1)->where("orderStatus=3 and userId = '{$userId}'")->count() == 1){
//        $userInfo = M("users")->where(['userId'=>$userId])->field('firstOrder')->find();
//        if ($userInfo['firstOrder'] == 1) {
//            //本次订单是否满足十元
//            if($rsv['realTotalMoney'] >= 10){
//                //查询是否存在邀请人
//                $find_user_invitation = M('user_invitation')->where("UserToId = '{$userId}'")->find();
//                if($find_user_invitation){
//                    //是否存在待恢复使用的优惠券
//                    $mod_coupons_users = M('coupons_users');
//                    $coupons_save['dataFlag'] = 1;
//                    $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->save($coupons_save);
//                    // $res_mod_coupons_users = $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->select();
//
//                    // if($res_mod_coupons_users){
//                    //   //恢复冻结的优惠券
//                    //
//                    //   for($i=0;$i<count($res_mod_coupons_users);$i++){
//                    //     $coupons_users_save['dataFlag'] = 1;
//                    //     $mod_coupons_users->where("id ='{$res_mod_coupons_users[$i]['id']}'")->save($coupons_users_save);
//                    //   }
//                    //
//                    //
//                    // }
//                }
//                //领过权益以后 首单状态更新
//                $userSave['firstOrder'] = -1;
//                M('users')->where("userId = '{$find_user_invitation['userId']}' and userFlag = 1")->save($userSave);
//            }
//        }

        $m = M('log_orders');
        $data["orderId"] = $orderId;

        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;
        return $rsdata;
    }

    /**
     * 商家批量发货配送订单 -------不支持达达物流
     */
    public function batchShopOrderDelivery ($obj){
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId?$userId:$obj['userId'];
        $orderIds = self::formatIn(",",I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId?$shopId:$obj['shopId'];
        $source = I('source');
        if($orderIds=='')return array('status'=>-2);
        $orderIds = explode(',',$orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        foreach ($orderIds as $orderId){

            if($orderId=='')continue;//订单号为空则跳过
            $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
            $rsv = $this->queryRow($sql);
            if($rsv["orderStatus"]!=2)continue;//状态不符合则跳过

            if($rsv["deliverType"]==2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0){
                //预发布 并提交达达订单
                $funResData = self::DaqueryDeliverFee($obj);
                continue;
            }

            $sql = "UPDATE __PREFIX__orders set orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);

            $data = array();
            $m = M('log_orders');
            $data["orderId"] = $orderId;
            $data["logContent"] = "商家已发货";
            $data["logUserId"] = $userId;
            $data["logType"] = 0;
            $data["logTime"] = date('Y-m-d H:i:s');
            $ra = $m->add($data);
            $editOrderNum++;
        }
        if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
        if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
        return array('status'=>1);
    }

    /**
     * 商家确认收货
     */
    public function shopOrderReceipt ($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
        $rsv = $this->queryRow($sql);
        if($rsv["orderStatus"]!=4){
            $rsdata["status"] = -1;
            return $rsdata;
        }

        $sql = "UPDATE __PREFIX__orders set orderStatus = 5 WHERE orderId = $orderId and shopId=".$shopId;
        $rs = $this->execute($sql);

        $data = array();
        $m = M('log_orders');
        $data["orderId"] = $orderId;
        $data["logContent"] = "商家确认已收货，订单完成";
        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;;
        return $rsdata;
    }

    /**
     * 商家确认拒收/不同意拒收
     */
    public function shopOrderRefund ($obj){
        $userId = (int)$obj["userId"];
        $orderId = (int)$obj["orderId"];
        $shopId = (int)$obj["shopId"];
        $type = (int)I('type');
        $rsdata = array();
        $sql = "SELECT orderId,orderNo,orderStatus,useScore FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag = 1 and shopId=".$shopId;
        $rsv = $this->queryRow($sql);
        if($rsv["orderStatus"]!= -3){
            $rsdata["status"] = -1;
            return $rsdata;
        }
        //同意拒收
        if($type==1){
            $sql = "UPDATE __PREFIX__orders set orderStatus = -4 WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);
            //加回库存
            if($rs>0){
                $sql = "SELECT goodsId,goodsNums,goodsAttrId from __PREFIX__order_goods WHERE orderId = $orderId";
                $oglist = $this->query($sql);
                foreach ($oglist as $key => $ogoods) {
                    $goodsId = $ogoods["goodsId"];
                    $goodsNums = $ogoods["goodsNums"];
                    $goodsAttrId = $ogoods["goodsAttrId"];
					$ogoods_goodsNums = gChangeKg($goodsId,$goodsNums,1);
                    $sql = "UPDATE __PREFIX__goods set goodsStock = goodsStock+$ogoods_goodsNums WHERE goodsId = $goodsId";
                    $this->execute($sql);
                    //更新进销存系统商品的库存
                    //updateJXCGoodsStock($goodsId,$goodsNums,0);
                    if($goodsAttrId>0){
                        $sql = "UPDATE __PREFIX__goods_attributes set attrStock = attrStock+$ogoods_goodsNums WHERE id = $goodsAttrId";
                        $this->execute($sql);
                    }
                }

                if($rsv["useScore"]>0){
                    $sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["useScore"]." WHERE userId=".$userId;
                    $this->execute($sql);

                    $data = array();
                    $m = M('user_score');
                    $data["userId"] = $userId;
                    $data["score"] = $rsv["useScore"];
                    $data["dataSrc"] = 4;
                    $data["dataId"] = $orderId;
                    $data["dataRemarks"] = "拒收订单返还";
                    $data["scoreType"] = 1;
                    $data["createTime"] = date('Y-m-d H:i:s');
                    $m->add($data);
                }
            }
        }else{//不同意拒收
            if(I('rejectionRemarks')=='')return $rsdata;//不同意拒收必须填写原因
            $sql = "UPDATE __PREFIX__orders set orderStatus = -5 WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);
        }
        $data = array();
        $m = M('log_orders');
        $data["orderId"] = $orderId;
        $data["logContent"] = ($type==1)?"商家同意拒收":"商家不同意拒收：".I('rejectionRemarks');
        $data["logUserId"] = $userId;
        $data["logType"] = 0;
        $data["logTime"] = date('Y-m-d H:i:s');
        $ra = $m->add($data);
        $rsdata["status"] = $ra;;
        return $rsdata;
    }

    /**
     * 检查订单是否已支付
     */
    public function checkOrderPay ($obj){
        $userId = (int)$obj["userId"];
        $orderId = (int)I("orderId");
        if($orderId>0){
            $sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderId = $orderId AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        }else{
            $orderunique = session("WST_ORDER_UNIQUE");
            $sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderunique = '$orderunique' AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 AND payType = 1";
        }
        $rsv = $this->query($sql);
        $oIds = array();
        for($i=0;$i<count($rsv);$i++){
            $oIds[] = $rsv[$i]["orderId"];
        }
        $orderIds = implode(",",$oIds);
        $data = array();
        if(count($rsv)>0){
            $sql = "SELECT og.goodsId,og.goodsName,og.goodsAttrName,g.goodsStock,og.goodsNums, og.goodsAttrId, ga.attrStock FROM  __PREFIX__goods g ,__PREFIX__order_goods og
					left join __PREFIX__goods_attributes ga on ga.goodsId=og.goodsId and og.goodsAttrId=ga.id
					WHERE og.goodsId = g.goodsId and og.orderId in($orderIds)";
            $glist = $this->query($sql);
            if(count($glist)>0){
                $rlist = array();
                foreach ($glist as $goods) {
                    if($goods["goodsAttrId"]>0){
                        if($goods["attrStock"]<$goods["goodsNums"]){
                            $rlist[] = $goods;
                        }
                    }else{
                        if($goods["goodsStock"]<$goods["goodsNums"]){
                            $rlist[] = $goods;
                        }
                    }
                }
                if(count($rlist)>0){
                    $data["status"] = -2;
                    $data["rlist"] = $rlist;
                }else{
                    $data["status"] = 1;
                }
            }else{
                $data["status"] = 1;
            }
        }else{
            $data["status"] = -1;
        }
        return $data;
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetailsApi($obj){
        $userId = (int)$obj["userId"];
        $shopId = (int)$obj["shopId"];
        $orderId = (int)$obj["orderId"];
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId and (userId=".$userId." or shopId=".$shopId.")";

        $order = $this->queryRow($sql);
        if(empty($order))return $data;
        $sql = "select og.orderId,og.weight,og.goodsId ,g.goodsSn,g.SuppPriceDiff,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName
				from __PREFIX__goods g , __PREFIX__order_goods og
				WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        $order["goodsList"] = $goods;
        //发票信息
        $order['invoiceInfo'] = [];
        if(isset($order['isInvoice']) && $order['isInvoice'] == 1){
            $order['invoiceInfo'] = M('invoice')->where("id='".$order['invoiceClient']."'")->find();
        }
        //后加包邮起步价
        $order['deliveryFreeMoney'] = M('shops')->where("shopId")->getField('deliveryFreeMoney');
        return $order;
    }

    /**
     * 获取订单日志
     */
    public function getOrderLogApi($obj){
        $orderId = (int)$obj["orderId"];
        $sql = "SELECT * FROM __PREFIX__log_orders WHERE orderId = $orderId ";
        $logs = $this->query($sql);
        return $logs;
    }

    /**
     * 获取订单量
     */
    public function getOrdercount($parameter=array()){
        $m = M('orders');
        $sql = "SELECT count(orderId) as count FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }
        if($startTime && $endTime){
            $sql.= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql.= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        if(isset($parameter['defPreSale'])){
            $sql.= " and defPreSale={$parameter['defPreSale']} ";
        }
        return $this->query($sql);
    }

    /**
     * 获取订单量
     */
    public function getOrderForDay($parameter=array()){
        $m = M('orders');
        $sql = "SELECT count(orderId) as count,sum(realTotalMoney) as sum_amount FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
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
    public function getTimeOrderForZitiLisrt($parameter=array()){
        $m = M('orders');
        $sql = "SELECT * FROM __PREFIX__orders where shopId = {$parameter['shopId']} ";
        $startTime = '';
        $endTime = '';
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }
        if($startTime && $endTime){
            $sql.= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql.= " and (createTime LIKE '%-%' AND STR_TO_DATE(createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        if(isset($parameter['isSelf'])){
            $sql.= " and isSelf={$parameter['isSelf']}";
        }
        return $this->query($sql);
    }

    /**
     * 根据订单id获取商品列表
     */
    public function getGoodsListForOrder($parameter=array()){

        if(!$parameter['orderId'] || !$parameter['shopId']){
            return array();
        }
        //orderId检测是否为本店
        $res = $this->where("orderId={$parameter['orderId']} and shopId={$parameter['shopId']}")->field(array('orderId'))->find();
        if(!$res){
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
    public function getOrderGoodsCount($parameter=array()){
        if(!$parameter['shopId']){
            return array();
        }

        $sql = "select og.goodsId,sum(og.goodsNums) as sum_goodsNums
                from __PREFIX__orders as o left join __PREFIX__order_goods as og on o.orderId = og.orderId
                where o.shopId={$parameter['shopId']} ";
        if(isset($parameter['defPreSale'])){
            $sql .= " and defPreSale={$parameter['defPreSale']}";
        }
        //时间区间
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }
        if($startTime && $endTime){
            $sql.= " and (o.createTime LIKE '%-%' AND STR_TO_DATE(o.createTime, '%Y-%m-%d %H:%i:%s') >= '{$startTime}') ";
            $sql.= " and (o.createTime LIKE '%-%' AND STR_TO_DATE(o.createTime, '%Y-%m-%d %H:%i:%s') <= '{$endTime}') ";
        }
        //END

        $sql .= " group by og.goodsId";
        $goods = $this->query($sql);
        if(!$goods){
            return array();
        }
        $gids = array_get_column($goods,'goodsId');
        $gm = M('goods');
        $where['goodsId'] = array('in',$gids);//cid在这个数组中，
        $goodsList= $gm->where($where)->field(array('goodsId,goodsSn,goodsName,goodsImg,goodsThums'))->select();
        $goodsForIdArr = get_changearr_key($goodsList,'goodsId');
        foreach ($goods as $key => &$gv) {
            $goodVals = $goodsForIdArr[$gv['goodsId']];
            if($goodVals && is_array($goodVals)){
                $gv = array_merge($gv,$goodVals);
            }
        }
        return $goods;
    }

    /**
     * 获取时间区间订单字段每天总数
     */
    public function getOrderSumFieldsDay($parameter=array(),&$msg=''){
        if(!$parameter['shopId']){
            return array();
        }
        if($parameter['fields'] && !in_array($parameter['fields'],array('realTotalMoney'))){
            $msg = 'fields 不在可选范围内';
            return array();
        }

        $sql = "SELECT
                DATE_FORMAT(createTime, '%Y-%m-%d') triggerDay,
                ";
        if($parameter['fields']){
            $sql .= "
                SUM({$parameter['fields']}) sum_{$parameter['fields']},
                ";
        }

        $sql .= "count(orderId) as count_orderId
            FROM
                `wst_orders`";
        $sql .= "
            WHERE shopId={$parameter['shopId']}";

        $sql.= " and isPay=1 and orderStatus=4 ";

        //时间区间
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }
        if($startTime && $endTime){
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
    public function getOrderSumFields($parameter=array(),&$msg = ''){
        if(!$parameter['shopId'] ){
            return array();
        }
        if($parameter['fields'] && !in_array($parameter['fields'],array('realTotalMoney'))){
            $msg = 'fields 不在可选范围内';
            return array();
        }

        $sql = "SELECT
";
        if($parameter['fields']){
            $sql.="
                SUM({$parameter['fields']}) sum_{$parameter['fields']},
                ";
        }

        $sql.=" count(orderId) as count_orderId
            FROM
                `wst_orders`";
        $sql .= "
            WHERE shopId={$parameter['shopId']}";
        //时间区间
        if($parameter['startTime'] && $parameter['endTime']){
            $startTime = date('Y-m-d H:i:s',$parameter['startTime']);
            $endTime = date('Y-m-d H:i:s',$parameter['endTime']);
        }

        $sql.= " and isPay=1 and orderStatus=4 ";

        if($startTime && $endTime){
            $sql .= "  and   createTime BETWEEN '{$startTime}'
            AND '{$endTime}'";
        }
        if(isset($parameter['defPreSale'])){
            $sql.= " and defPreSale={$parameter['defPreSale']} ";
        }
        if(isset($parameter['isSelf'])){
            $sql.= " and isSelf={$parameter['isSelf']} ";
        }
        //END

//        echo "<pre>";print_r($sql);exit;
        $goods = $this->query($sql);
        return $goods;
    }

    /**
     * 订单列表商品重量设置 单个商品设重 单位为g
     */
    public function setShopGoodsWeight($obj){

        //检查当前商品是否属于这个店铺的
        $mod_goods = M('goods');
        $mod_order_goods=M('order_goods');
        $where['goodsId'] = $obj["goodsId"];
        $res = $mod_goods->where($where)->find();
        if($res){
            if($res['shopId'] != $obj["shopInfo"]['shopId']){
                return array('code'=>-1,'msg'=>'商品不属于这个店铺','data'=>null);
            }
        }

        //更新重量
        $save_data['weight'] = $obj["weight"];
        unset($where);
        $where['orderId'] = $obj["orderId"];
        $where['goodsId'] = $obj["goodsId"];
        if($mod_order_goods->where($where)->save($save_data)){
            return array('code'=>1,'msg'=>'更新成功','data'=>null);
        }else{
            return array('code'=>-1,'msg'=>'更新失败','data'=>null);
        }
    }

    /**
     * 获取 商品设重 单位为g
     */
    public function getShopGoodsWeight($obj){

        //检查当前商品是否属于这个店铺的
        $mod_goods = M('goods');
        $mod_order_goods=M('order_goods');
        $where['goodsId'] = $obj["goodsId"];
        $res = $mod_goods->where($where)->find();
        if($res){
            if($res['shopId'] != $obj["shopInfo"]['shopId']){
                return array('code'=>-1,'msg'=>'商品不属于这个店铺','data'=>null);
            }
        }

        //获取重量
        unset($where);
        $where['orderId'] = $obj["orderId"];
        $where['goodsId'] = $obj["goodsId"];
        $res = $mod_order_goods->where($where)->find()['weight'];
        if($res){
            return array('code'=>1,'msg'=>'获取成功','data'=>$res);
        }else{
            return array('code'=>-1,'msg'=>'获取失败','data'=>null);
        }
    }

    //商家设置用户订单为已读
    public function setOrderUserHasRead($obj){

        //判断是否是当前店铺的订单
        $mod_orders = M('orders');

        $data_orders = $mod_orders->where("orderId =".$obj["orderId"])->find();
        if($data_orders['shopId'] != $obj["shopInfo"]['shopId']){
            return array('code' => -1,'msg'=>'订单与店铺不符','data'=>null);
        }

        //更新为已读
        $save['orderStatus'] = -7;

        if($mod_orders->where("orderId = ".$obj["orderId"])->save($save)){
            return array('code' => 1,'msg'=>'成功','data'=>null);
        }else{
            return array('code' => -1,'msg'=>'失败','data'=>null);
        }
    }

}

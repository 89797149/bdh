<?php
/**
 * 发货出库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 16:18
 */

namespace App\Modules\ExWarehouse;


use App\Models\OrderGoodsModel;
use App\Models\OrdersModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\PSD\LineModule;
use App\Modules\PSD\RegionModule;
use App\Modules\Sorting\SortingModule;
use Think\Model;

class DeliveryExWarehouseModule
{
    /**
     * 发货出库-发货出库列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int sortingStatus 分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int lineId 线路id,不传默认全部
     * -int requireDate 要求送达日期
     * -int paymentKeywords 关键字(客户名称/联系人/电话)
     * -int usePage 使用分页(0:不使用 1:使用)
     * -int page 关键字(客户名称/联系人/电话)
     * -int pageSize 分页条数
     * -int export 是否导出(0:不导出 1:导出)
     * @return array
     * */
    public function getDeliveryExWarehouseList(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $requireDate = $paramsInput['requireDate'];
        $paramsInput['page'] = (int)$paramsInput['page'];
        $paramsInput['pageSize'] = (int)$paramsInput['pageSize'];
        $orderModel = new OrdersModel();
        $orderWhere = " orders.shopId={$shopId} and orders.requireTime >= '{$requireDate} 00:00:00' and orders.requireTime <= '{$requireDate} 23:59:59' and orders.isPay=1 and orders.isSelf != 1 and orders.taskId > 0 ";
        if (is_numeric($paramsInput['sortingOrderStatus'])) {
            $paramsInput['sortingOrderStatus'] = (int)$paramsInput['sortingOrderStatus'];
        }
        if (isset($paramsInput['deliveryStatus'])) {
            if (is_numeric($paramsInput['deliveryStatus'])) {
                $paramsInput['deliveryStatus'] = (int)$paramsInput['deliveryStatus'];
                if ($paramsInput['deliveryStatus'] === 0) {
                    $orderWhere .= "and orders.orderStatus IN(2,16) ";
                } elseif ($paramsInput['deliveryStatus'] === 1) {
                    $orderWhere .= "and orders.orderStatus IN(3,4,17) ";
                }
            } else {
                $orderWhere .= "and orders.orderStatus IN(2,3,4,16,17) ";
            }
        }
        if (!empty($paramsInput['lineId'])) {
            $orderWhere .= " and orders.lineId={$paramsInput['lineId']} ";
        }
        if (!empty($paramsInput['paymentKeywords'])) {
            $orderWhere .= " and (users.userName like '%{$paramsInput['paymentKeywords']}%' or orders.userName like '%{$paramsInput['paymentKeywords']}%' or orders.userPhone like '%{$paramsInput['paymentKeywords']}%') ";
        }
        $orderField = 'users.userId as payment_userid,users.userName as payment_username';
        $orderField .= ',orders.orderId,orders.userName as consignee_name,orders.userPhone as consignee_phone,orders.lat,orders.lng,orders.userAddress,orders.lineId,orders.regionId,orders.printNum,orders.orderStatus';
        $todayList = $orderModel
            ->alias('orders')
            ->join("left join wst_users users on users.userId=orders.userId")
            ->where($orderWhere)
            ->field($orderField)
            ->select();
        $returnData = array(
            'currPage' => $paramsInput['page'],
            'pageSize' => $paramsInput['pageSize'],
            'start' => 0,
            'total' => 0,
            'totalPage' => 0,
            'root' => array()
        );
        if (empty($todayList)) {
            return $returnData;
        }
        $ordersModule = new OrdersModule();
        $sortingModule = new SortingModule();
        $uniqueOrderData = array();//客户数据聚合
        $allGoodsList = array();//商品聚合
        foreach ($todayList as $todayVal) {
            if (in_array($todayVal['orderStatus'], array(2, 16))) {//未发货
                $todayVal['delivetyStatus'] = 0;
                $todayVal['delivetyStatusName'] = '未发货';
            } else {//已发货
                $todayVal['delivetyStatus'] = 1;
                $todayVal['delivetyStatusName'] = '已发货';
            }
            $uniqueKey = $todayVal['payment_userid'] . '@' . $todayVal['lat'] . '@' . $todayVal['lng'] . '@' . $todayVal['delivetyStatus'];//客户聚合唯一标识
            $orderId = $todayVal['orderId'];
            $orderGoods = $ordersModule->getOrderGoodsList($orderId, 'og.*', 2);
            $deliveryGoodsPriceTotal = 0;
            $realSortWeightTotal = 0;
            foreach ($orderGoods as $gkey => $gval) {
                $goodsId = $gval['goodsId'];
                $skuId = $gval['skuId'];
                $unitPrice = (float)$gval['goodsPrice'];//单价
                $goodsUniqueKey = $uniqueKey . '@' . $goodsId . '@' . $skuId . '@' . $unitPrice;
                $realSortWeight = 0;
                $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                if (!empty($sortingOrderGoodsDetail)) {
                    $sortingWeightDetail = $sortingModule->getSortingGoodsWeight($sortingOrderGoodsDetail['id'], $goodsId, $skuId);
                    $realSortWeight = $sortingWeightDetail['sorting_ok_weight'];
                }
                $deliveryGoodsPrice = (float)bc_math($unitPrice, $realSortWeight, 'bcmul', 2);
                $orderGoods[$gkey]['unitPrice'] = $unitPrice;//发货单价
                $orderGoods[$gkey]['buyNum'] = (float)$gval['goodsNums'];//购买量
                $orderGoods[$gkey]['realSortWeight'] = $realSortWeight;//实际量
                $orderGoods[$gkey]['deliveryGoodsPrice'] = $deliveryGoodsPrice;//小计
                $orderGoods[$gkey]['orderUniqueKey'] = $uniqueKey;//订单唯一标识
                $orderGoods[$gkey]['sortingStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣 3:未发货 4:已发货)
                $okSortingStatusNum = 0;//已操作分拣商品数量
                $sortingGoodsStatus = 0;//商品分拣状态(0:未分拣 1:已分拣)
                if ($todayVal['delivetyStatus'] === 0) {//未发货
                    if ($gval['actionStatus'] > 1) {
                        $sortingGoodsStatus = 1;
                    }
                } else {
                    $sortingGoodsStatus = 1;
                }
//                $orderGoods[$gkey]['sortingGoodsStatus'] = $sortingGoodsStatus;
                if ($sortingGoodsStatus == 1) {
                    $okSortingStatusNum += 1;
                }
                if (!isset($allGoodsList[$goodsUniqueKey])) {
                    $allGoodsList[$goodsUniqueKey] = $orderGoods[$gkey];
                } else {
                    $allGoodsList[$goodsUniqueKey]['buyNum'] += $orderGoods[$gkey]['buyNum'];
                    $allGoodsList[$goodsUniqueKey]['realSortWeight'] += $realSortWeight;
                    $allGoodsList[$goodsUniqueKey]['deliveryGoodsPrice'] += $deliveryGoodsPrice;
                }
                $allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] += $okSortingStatusNum;
                $allGoodsList[$goodsUniqueKey]['needSortingStatusNum'] += 1;
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] == 0) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 0;
                }
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] > 0 && $allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] < $allGoodsList[$goodsUniqueKey]['needSortingStatusNum']) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 1;
                }
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] >= $allGoodsList[$goodsUniqueKey]['needSortingStatusNum']) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 2;
                }
                $deliveryGoodsPriceTotal += $deliveryGoodsPrice;
                $realSortWeightTotal += $realSortWeight;
            }
            $todayVal['orderUniqueKey'] = $uniqueKey;
            if (!isset($uniqueOrderData[$uniqueKey])) {
                $todayVal['deliveryGoodsPriceTotal'] = $deliveryGoodsPriceTotal;
                $todayVal['realSortWeightTotal'] = $realSortWeightTotal;
                $todayVal['printNum'] = (int)$todayVal['printNum'];
                $uniqueOrderData[$uniqueKey] = $todayVal;
            } else {
                $uniqueOrderData[$uniqueKey]['printNum'] += $todayVal['printNum'];
                $uniqueOrderData[$uniqueKey]['deliveryGoodsPriceTotal'] += $deliveryGoodsPriceTotal;
                $uniqueOrderData[$uniqueKey]['realSortWeightTotal'] += $realSortWeightTotal;
            }
            $uniqueOrderData[$uniqueKey]['orderIdArr'][] = $todayVal['orderId'];
        }
        $returnList = array();
        foreach ($uniqueOrderData as $uniqueOrderKey => &$uniqueOrderDetial) {
            if ($uniqueOrderDetial['delivetyStatus'] != 1) {//未发货
                $uniqueOrderDetial['sortingOrderStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣)
                $uniqueOrderDetial['sortingOrderStatusName'] = '未分拣';
                $okSortingStatusNumTotal = 0;
                $needSortingStatusNumTotal = 0;
                foreach ($allGoodsList as $goodsVal) {
                    if ($goodsVal['orderUniqueKey'] != $uniqueOrderKey) {
                        continue;
                    }
                    $okSortingStatusNumTotal += $goodsVal['okSortingStatusNum'];
                    $needSortingStatusNumTotal += $goodsVal['needSortingStatusNum'];
                }
                if ($okSortingStatusNumTotal > 0 && $okSortingStatusNumTotal < $needSortingStatusNumTotal) {
                    $uniqueOrderDetial['sortingOrderStatus'] = 1;
                    $uniqueOrderDetial['sortingOrderStatusName'] = '部分分拣';
                }
                if ($okSortingStatusNumTotal > 0 && $okSortingStatusNumTotal >= $needSortingStatusNumTotal) {
                    $uniqueOrderDetial['sortingOrderStatus'] = 2;
                    $uniqueOrderDetial['sortingOrderStatusName'] = '已分拣';
                }
            } else {//已发货
                $uniqueOrderDetial['sortingOrderStatus'] = 2;
                $uniqueOrderDetial['sortingOrderStatusName'] = '已发货';
            }
            if (is_numeric($paramsInput['deliveryStatus'])) {
                if ($paramsInput['deliveryStatus'] === 0 && $uniqueOrderDetial['deliveryStatus'] != 0) {
                    continue;
                }
                if ($paramsInput['deliveryStatus'] === 1 && $uniqueOrderDetial['deliveryStatus'] != 1) {
                    continue;
                }
            }
            if (is_numeric($paramsInput['sortingOrderStatus'])) {
                if ($paramsInput['sortingOrderStatus'] === 0 && $uniqueOrderDetial['sortingOrderStatus'] != 0) {
                    continue;
                }
                if ($paramsInput['sortingOrderStatus'] === 1 && $uniqueOrderDetial['sortingOrderStatus'] != 1) {
                    continue;
                }
                if ($paramsInput['sortingOrderStatus'] === 2 && $uniqueOrderDetial['sortingOrderStatus'] != 2) {
                    continue;
                }
            }
            $returnList[] = $uniqueOrderDetial;
        }
        unset($uniqueOrderDetial);
        if ($paramsInput['export'] == 1) {
            $goodsModule = new GoodsModule();
            $goodsCatModule = new GoodsCatModule();
            foreach ($returnList as &$item) {
                $item['goods_list'] = array();
                foreach ($allGoodsList as $allGoodsVal) {
                    if ($allGoodsVal['orderUniqueKey'] != $item['orderUniqueKey']) {
                        continue;
                    }
                    $goodsId = $allGoodsVal['goodsId'];
                    $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, 'goodsName,shopCatId1,shopCatId2', 2);
                    $allGoodsVal['shopCatId1Name'] = '';
                    $allGoodsVal['shopCatId2Name'] = '';
                    $shopCatId1Detail = $goodsCatModule->getShopCatDetailById($goodsDetail['shopCatId1'], 'catName');
                    if (!empty($shopCatId1Detail)) {
                        $allGoodsVal['shopCatId1Name'] = $shopCatId1Detail['catName'];
                    }
                    $shopCatId2Detail = $goodsCatModule->getShopCatDetailById($goodsDetail['shopCatId2'], 'catName');
                    if (!empty($shopCatId2Detail)) {
                        $allGoodsVal['shopCatId2Name'] = $shopCatId2Detail['catName'];
                    }
//                    $orderDetail = $ordersModule->getOrderInfoById($allGoodsVal['orderId'], 'orderNo', 2);
//                    $allGoodsVal['orderNo'] = $orderDetail['orderNo'];
                    $item['goods_list'][] = $allGoodsVal;
                }
            }
            unset($item);
            return $returnList;
        }
        $returnData = array(
            'currPage' => $paramsInput['page'],
            'pageSize' => $paramsInput['pageSize'],
            'start' => ($paramsInput['page'] - 1) * $paramsInput['pageSize'],
            'total' => count($returnList),
            'totalPage' => 0,
            'root' => array()
        );
        $returnData['root'] = array_slice($returnList, $returnData['start'], $returnData['pageSize']);
        $returnData['totalPage'] = ceil($returnData['total'] / $returnData['pageSize']);
        $lineModule = new LineModule();
        $regionModule = new RegionModule();
        foreach ($returnData['root'] as &$item) {
            unset($item['orderId']);
            $item['keyNum'] = ++$returnData['start'];
            $item['lineName'] = '';
            if (!empty($item['lineId'])) {
                $lineDetail = $lineModule->getLineDetailById($item['lineId']);
                $item['lineName'] = !empty($lineDetail['lineName']) ? $lineDetail['lineName'] : '';
            }
            $item['regionName'] = '';
            if (!empty($item['regionId'])) {
                $regionDetail = $regionModule->getRegionDetailById($item['regionId']);
                $item['regionName'] = !empty($regionDetail['regionName']) ? $regionDetail['regionName'] : '';
            }
        }
        unset($item);
        return $returnData;
    }

    /**
     * 发货出库-发货出库详情
     * @param int $shopId 门店id
     * @param int $paymentUserid 客户id
     * @param date $requireDate 要求送达日期
     * @param int $orderId 订单id
     * @return array
     * */
    public function getDeliveryExWarehouseDetailById(int $shopId, int $paymentUserid, $requireDate, $orderId = 0)
    {
        $orderModel = new OrdersModel();
        $orderWhere = " orders.shopId={$shopId} and orders.requireTime >= '{$requireDate} 00:00:00' and orders.requireTime <= '{$requireDate} 23:59:59' and orders.isPay=1 and orders.orderStatus IN(2,3,4,16,17) and orders.userId={$paymentUserid} and orders.isSelf != 1 and orders.taskId > 0 ";
        if (!empty($orderId)) {
            $orderWhere .= " and orders.orderId={$orderId} ";
        }
        $orderField = 'users.userId as payment_userid,users.userName as payment_username';
        $orderField .= ',orders.orderId,orders.userName as consignee_name,orders.userPhone as consignee_phone,orders.lat,orders.lng,orders.userAddress,orders.lineId,orders.regionId,orders.printNum,orders.orderStatus';
        $todayList = $orderModel
            ->alias('orders')
            ->join("left join wst_users users on users.userId=orders.userId")
            ->where($orderWhere)
            ->field($orderField)
            ->select();
        if (empty($todayList)) {
            return array();
        }
        $ordersModule = new OrdersModule();
        $sortingModule = new SortingModule();
        $uniqueOrderData = array();//客户数据聚合
        $allGoodsList = array();//商品聚合
        $lineModule = new LineModule();
        $regionModule = new RegionModule();
        foreach ($todayList as $todayVal) {
            $todayVal['lineName'] = '';
            if (!empty($todayVal['lineId'])) {
                $lineDetail = $lineModule->getLineDetailById($todayVal['lineId']);
                $todayVal['lineName'] = !empty($lineDetail['lineName']) ? $lineDetail['lineName'] : '';
            }
            $todayVal['regionName'] = '';
            if (!empty($todayVal['regionId'])) {
                $regionDetail = $regionModule->getRegionDetailById($todayVal['regionId']);
                $todayVal['regionName'] = !empty($regionDetail['regionName']) ? $regionDetail['regionName'] : '';
            }
            if (in_array($todayVal['orderStatus'], array(2, 16))) {//未发货
                $todayVal['delivetyStatus'] = 0;
                $todayVal['delivetyStatusName'] = '未发货';
            } else {//已发货
                $todayVal['delivetyStatus'] = 1;
                $todayVal['delivetyStatusName'] = '已发货';
            }
            $uniqueKey = $todayVal['payment_userid'] . '@' . $todayVal['lat'] . '@' . $todayVal['lng'] . '@' . $todayVal['delivetyStatus'];//客户聚合唯一标识
            $orderId = $todayVal['orderId'];
            $orderGoodsField = 'og.id,og.goodsId,og.skuId,og.goodsCode,og.goodsName,og.goodsSpec,og.goodsNums as buyNum,og.goodsPrice,og.unitName,og.skuSpecStr,og.actionStatus,og.remarks';
            $orderGoods = $ordersModule->getOrderGoodsList($orderId, $orderGoodsField, 2);
            $deliveryGoodsPriceTotal = 0;
            $realSortWeightTotal = 0;
            foreach ($orderGoods as $gkey => $gval) {
                $goodsId = $gval['goodsId'];
                $skuId = $gval['skuId'];
                $buyUnitPrice = (float)$gval['goodsPrice'];//下单单价
                $deliveryUnitPrice = $buyUnitPrice;//发货单价
                $goodsUniqueKey = $uniqueKey . '@' . $goodsId . '@' . $skuId . '@' . $buyUnitPrice;
                $realSortWeight = 0;
                $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                if (!empty($sortingOrderGoodsDetail)) {
                    $sortingWeightDetail = $sortingModule->getSortingGoodsWeight($sortingOrderGoodsDetail['id'], $goodsId, $skuId);
                    $realSortWeight = $sortingWeightDetail['sorting_ok_weight'];
                }
                $deliveryGoodsPrice = (float)bc_math($buyUnitPrice, $realSortWeight, 'bcmul', 2);//发货金额小计
                $orderGoods[$gkey]['buyUnitPrice'] = (float)$gval['goodsPrice'];
                $orderGoods[$gkey]['realUnitName'] = $gval['unitName'];//实际单位
                $orderGoods[$gkey]['buyNum'] = (float)$gval['buyNum'];//购买量
                $orderGoods[$gkey]['deliveryUnitPrice'] = $deliveryUnitPrice;
                $orderGoods[$gkey]['realSortWeight'] = $realSortWeight;//实际量
                $orderGoods[$gkey]['deliveryGoodsPrice'] = $deliveryGoodsPrice;//小计
                $orderGoods[$gkey]['orderUniqueKey'] = $uniqueKey;//订单唯一标识
                $orderGoods[$gkey]['sortingStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣 3:未发货 4:已发货)
                $okSortingStatusNum = 0;//已操作分拣商品数量
                $sortingGoodsStatus = 0;//商品分拣状态(0:未分拣 1:已分拣)
                if ($todayVal['delivetyStatus'] === 0) {//未发货
                    if ($gval['actionStatus'] > 1) {
                        $sortingGoodsStatus = 1;
                    }
                } else {
                    $sortingGoodsStatus = 1;
                }
                if ($sortingGoodsStatus == 1) {
                    $okSortingStatusNum += 1;
                }
                if (!isset($allGoodsList[$goodsUniqueKey])) {
                    $allGoodsList[$goodsUniqueKey] = $orderGoods[$gkey];
                } else {
                    $allGoodsList[$goodsUniqueKey]['buyNum'] += $orderGoods[$gkey]['buyNum'];
                    $allGoodsList[$goodsUniqueKey]['realSortWeight'] += $realSortWeight;
                    $allGoodsList[$goodsUniqueKey]['deliveryGoodsPrice'] += $deliveryGoodsPrice;
                }
                $allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] += $okSortingStatusNum;
                $allGoodsList[$goodsUniqueKey]['needSortingStatusNum'] += 1;
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] == 0) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 0;
                }
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] > 0 && $allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] < $allGoodsList[$goodsUniqueKey]['needSortingStatusNum']) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 1;
                }
                if ($allGoodsList[$goodsUniqueKey]['okSortingStatusNum'] >= $allGoodsList[$goodsUniqueKey]['needSortingStatusNum']) {
                    $allGoodsList[$goodsUniqueKey]['sortingStatus'] = 2;
                }
                $deliveryGoodsPriceTotal += $deliveryGoodsPrice;
                $realSortWeightTotal += $realSortWeight;
            }
            $todayVal['orderUniqueKey'] = $uniqueKey;
            if (!isset($uniqueOrderData[$uniqueKey])) {
                $todayVal['deliveryGoodsPriceTotal'] = $deliveryGoodsPriceTotal;
                $todayVal['realSortWeightTotal'] = $realSortWeightTotal;
                $todayVal['printNum'] = (int)$todayVal['printNum'];
                $uniqueOrderData[$uniqueKey] = $todayVal;
            } else {
                $uniqueOrderData[$uniqueKey]['printNum'] += $todayVal['printNum'];
                $uniqueOrderData[$uniqueKey]['deliveryGoodsPriceTotal'] += $deliveryGoodsPriceTotal;
                $uniqueOrderData[$uniqueKey]['realSortWeightTotal'] += $realSortWeightTotal;
            }
            $uniqueOrderData[$uniqueKey]['orderIdArr'][] = $todayVal['orderId'];
        }
        $returnList = array();
        foreach ($uniqueOrderData as $uniqueOrderDetial) {
            unset($uniqueOrderDetial['orderId']);
            if ($uniqueOrderDetial['delivetyStatus'] != 1) {//未发货
                $uniqueOrderDetial['sortingOrderStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣)
                $uniqueOrderDetial['sortingOrderStatusName'] = '未分拣';
                $okSortingStatusNumTotal = 0;
                $needSortingStatusNumTotal = 0;
                foreach ($allGoodsList as $goodsVal) {
                    $okSortingStatusNumTotal += $goodsVal['okSortingStatusNum'];
                    $needSortingStatusNumTotal += $goodsVal['needSortingStatusNum'];
                }
                if ($okSortingStatusNumTotal > 0 && $okSortingStatusNumTotal < $needSortingStatusNumTotal) {
                    $uniqueOrderDetial['sortingOrderStatus'] = 1;
                    $uniqueOrderDetial['sortingOrderStatusName'] = '部分分拣';
                }
                if ($okSortingStatusNumTotal > 0 && $okSortingStatusNumTotal >= $needSortingStatusNumTotal) {
                    $uniqueOrderDetial['sortingOrderStatus'] = 2;
                    $uniqueOrderDetial['sortingOrderStatusName'] = '已分拣';
                }
            } else {//已发货
                $uniqueOrderDetial['sortingOrderStatus'] = 2;
                $uniqueOrderDetial['sortingOrderStatusName'] = '已分拣';
            }
            $returnList[] = $uniqueOrderDetial;
        }
        $result = $returnList[0];
        foreach ($allGoodsList as &$goodsDetail) {
            $goodsDetail['statusName'] = '未分拣';//0:未分拣 1:部分分拣 2:已分拣
            if ($goodsDetail['sortingStatus'] == 1) {
                $goodsDetail['statusName'] = '部分分拣';
            }
            if ($goodsDetail['sortingStatus'] == 2) {
                $goodsDetail['statusName'] = '已分拣';
            }
            if ($goodsDetail['sortingStatus'] == 2 && $result['sortingOrderStatus'] == 2) {
                $goodsDetail['statusName'] = '未发货';
            }
            if ($result['delivetyStatus'] == 1) {
                $goodsDetail['statusName'] = '已发货';
            }
        }
        unset($goodsDetail);
        $result['goods_list'] = array_values($allGoodsList);
        return $result;
    }

    /**
     * 发货出库-订单发货出库列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int sortingStatus 分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int lineId 线路id,不传默认全部
     * -int requireDate 要求送达日期
     * -int paymentKeywords 关键字(客户名称/订单号/联系人/电话)
     * -int usePage 使用分页(0:不使用 1:使用)
     * -int page 关键字(客户名称/联系人/电话)
     * -int pageSize 分页条数
     * -int export 是否导出(0:不导出 1:导出)
     * @return array
     * */
    public function getDeliveryExWarehouseOrderList(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $requireDate = $paramsInput['requireDate'];
        $orderModel = new OrdersModel();
        $orderWhere = " orders.shopId={$shopId} and orders.requireTime >= '{$requireDate} 00:00:00' and orders.requireTime <= '{$requireDate} 23:59:59' and orders.isPay=1 and orders.isSelf != 1 and orders.taskId > 0 ";
        if (is_numeric($paramsInput['sortingOrderStatus'])) {
            $paramsInput['sortingOrderStatus'] = (int)$paramsInput['sortingOrderStatus'];
        }
        if (isset($paramsInput['deliveryStatus'])) {
            if (is_numeric($paramsInput['deliveryStatus'])) {
                $paramsInput['deliveryStatus'] = (int)$paramsInput['deliveryStatus'];
                if ($paramsInput['deliveryStatus'] === 0) {
                    $orderWhere .= "and orders.orderStatus IN(2,16) ";
                } elseif ($paramsInput['deliveryStatus'] === 1) {
                    $orderWhere .= "and orders.orderStatus IN(3,4,17) ";
                }
            } else {
                $orderWhere .= "and orders.orderStatus IN(2,3,4,16,17) ";
            }
        }
        if (!empty($paramsInput['lineId'])) {
            $orderWhere .= " and orders.lineId={$paramsInput['lineId']} ";
        }
        if (!empty($paramsInput['paymentKeywords'])) {
            $orderWhere .= " and (users.userName like '%{$paramsInput['paymentKeywords']}%' or orders.userName like '%{$paramsInput['paymentKeywords']}%' or orders.userPhone like '%{$paramsInput['paymentKeywords']}%' or orders.orderNo like '%{$paramsInput['paymentKeywords']}%') ";
        }
        $orderField = 'users.userId as payment_userid,users.userName as payment_username';
        $orderField .= ',orders.orderId,orders.orderNo,orders.userName as consignee_name,orders.userPhone as consignee_phone,orders.lat,orders.lng,orders.userAddress,orders.lineId,orders.regionId,orders.printNum,orders.orderStatus,orders.receiveTime';
        $sql = $orderModel
            ->alias('orders')
            ->join("left join wst_users users on users.userId=orders.userId")
            ->where($orderWhere)
            ->field($orderField)
            ->order('orders.orderId desc')
            ->buildSql();
        if ($paramsInput['usePage'] === 0 || $paramsInput['export'] == 1) {
            $result = array();
            $result['root'] = $orderModel->query($sql);

        } else {
            $result = $orderModel->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        }

        if (empty($result['root'])) {
            if ($paramsInput['usePage'] === 0 || $paramsInput['export'] == 1) {
                return array();
            }
            return $result;
        }
        $todayList = $result['root'];
        $ordersModule = new OrdersModule();
        $sortingModule = new SortingModule();
        $lineModule = new LineModule();
        $regionModule = new RegionModule();
        $keyTag = (int)$result['start'];
        $allGoodsList = array();
        foreach ($todayList as &$todayVal) {
            $todayVal['goods_list'] = array();
            $todayVal['keyNum'] = ++$keyTag;
            $todayVal['lineName'] = '';
            if (!empty($todayVal['lineId'])) {
                $lineDetail = $lineModule->getLineDetailById($todayVal['lineId']);
                $todayVal['lineName'] = !empty($lineDetail['lineName']) ? $lineDetail['lineName'] : '';
            }
            $todayVal['regionName'] = '';
            if (!empty($todayVal['regionId'])) {
                $regionDetail = $regionModule->getRegionDetailById($todayVal['regionId']);
                $todayVal['regionName'] = !empty($regionDetail['regionName']) ? $regionDetail['regionName'] : '';
            }
            $todayVal['receiveTime'] = (string)$todayVal['receiveTime'];
            if (in_array($todayVal['orderStatus'], array(2, 16))) {//未发货
                $todayVal['delivetyStatus'] = 0;
                $todayVal['delivetyStatusName'] = '未发货';
            } else {//已发货
                $todayVal['delivetyStatus'] = 1;
                $todayVal['delivetyStatusName'] = '已发货';
            }
            $orderId = $todayVal['orderId'];
            $orderGoods = $ordersModule->getOrderGoodsList($orderId, 'og.*', 2);
            $deliveryGoodsPriceTotal = 0;
            $realSortWeightTotal = 0;
            $needSortingStatusNumTotal = 0;
            foreach ($orderGoods as $gkey => $gval) {
                $goodsId = $gval['goodsId'];
                $skuId = $gval['skuId'];
                $unitPrice = (float)$gval['goodsPrice'];//单价
                $realSortWeight = 0;
                $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                if (!empty($sortingOrderGoodsDetail)) {
                    $sortingWeightDetail = $sortingModule->getSortingGoodsWeight($sortingOrderGoodsDetail['id'], $goodsId, $skuId);
                    $realSortWeight = $sortingWeightDetail['sorting_ok_weight'];
                }
                $deliveryGoodsPrice = (float)bc_math($unitPrice, $realSortWeight, 'bcmul', 2);
                $orderGoods[$gkey]['unitPrice'] = $unitPrice;//发货单价
                $orderGoods[$gkey]['buyNum'] = (float)$gval['goodsNums'];//购买量
                $orderGoods[$gkey]['realSortWeight'] = $realSortWeight;//实际量
                $orderGoods[$gkey]['deliveryGoodsPrice'] = $deliveryGoodsPrice;//小计
                $orderGoods[$gkey]['sortingStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣 3:未发货 4:已发货)
                $okSortingStatusNum = 0;//已操作分拣商品数量
                $sortingGoodsStatus = 0;//商品分拣状态(0:未分拣 1:已分拣)
                if ($todayVal['delivetyStatus'] === 0) {//未发货
                    if ($gval['actionStatus'] > 1) {
                        $sortingGoodsStatus = 1;
                    }
                } else {
                    $sortingGoodsStatus = 1;
                }
                if ($sortingGoodsStatus == 1) {
                    $okSortingStatusNum += 1;
                }
                $orderGoods[$gkey]['okSortingStatusNum'] += $okSortingStatusNum;
                $orderGoods[$gkey]['needSortingStatusNum'] += 1;
                $allGoodsList[] = $orderGoods[$gkey];
                $deliveryGoodsPriceTotal += $deliveryGoodsPrice;
                $realSortWeightTotal += $realSortWeight;
                $needSortingStatusNumTotal += 1;
            }
            $todayVal['deliveryGoodsPriceTotal'] = $deliveryGoodsPriceTotal;
            $todayVal['realSortWeightTotal'] = $realSortWeightTotal;
            $todayVal['orderIdArr'][] = $todayVal['orderId'];
            if ($todayVal['delivetyStatus'] != 1) {//未发货
                $todayVal['sortingOrderStatus'] = 0;//分拣状态(0:未分拣 1:部分分拣 2:已分拣)
                $todayVal['sortingOrderStatusName'] = '未分拣';
                if ($todayVal['realSortWeightTotal'] > 0 && $todayVal['realSortWeightTotal'] < $needSortingStatusNumTotal) {
                    $uniqueOrderDetial['sortingOrderStatus'] = 1;
                    $uniqueOrderDetial['sortingOrderStatusName'] = '部分分拣';
                }
                if ($todayVal['realSortWeightTotal'] > 0 && $todayVal['realSortWeightTotal'] >= $needSortingStatusNumTotal) {
                    $todayVal['sortingOrderStatus'] = 2;
                    $todayVal['sortingOrderStatusName'] = '已分拣';
                }
            } else {//已发货
                $todayVal['sortingOrderStatus'] = 2;
                $todayVal['sortingOrderStatusName'] = '已分拣';
            }
        }
        unset($todayVal);
        if ($paramsInput['export'] == 1) {
            $goodsModule = new GoodsModule();
            $goodsCatModule = new GoodsCatModule();
            foreach ($todayList as &$todayVal) {
                foreach ($allGoodsList as $goodsDetail) {
                    if ($todayVal['orderId'] != $goodsDetail['orderId']) {
                        continue;
                    }
                    $goodsInfo = $goodsModule->getGoodsInfoById($goodsDetail['goodsId'], 'goodsName,shopCatId1,shopCatId2', 2);
                    $goodsDetail['shopCatId1Name'] = '';
                    $goodsDetail['shopCatId2Name'] = '';
                    $shopCatId1Detail = $goodsCatModule->getShopCatDetailById($goodsInfo['shopCatId1'], 'catName');
                    if (!empty($shopCatId1Detail)) {
                        $goodsDetail['shopCatId1Name'] = $shopCatId1Detail['catName'];
                    }
                    $shopCatId2Detail = $goodsCatModule->getShopCatDetailById($goodsInfo['shopCatId2'], 'catName');
                    if (!empty($shopCatId2Detail)) {
                        $goodsDetail['shopCatId2Name'] = $shopCatId2Detail['catName'];
                    }
                    $todayVal['goods_list'][] = $goodsDetail;
                }
            }
            unset($todayVal);
            return $todayList;
        }
        if ($paramsInput['usePage'] === 0 || $paramsInput['export'] == 1) {
            return $todayList;
        }
        $result['root'] = $todayList;
        return $result;
    }

    /**
     * 发货出库-商品发货差异列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int lineId 线路id
     * -date requireDate 要求送达日期
     * -string goodsKeywords 商品关键字(商品名/编码)
     * -int sortDiffNum 分拣差异值(1:差异小于0 2:差异大于0 3:差异等于0) 不传默认全部
     * -int sortingGoodsStatus 商品分拣状态(0:未分拣 1:已分拣) 不传默认全部
     * -array idArr 订单商品关联唯一标识id
     * -int usePage 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getDiffDeliveryExWarehouseGoods(array $paramsInput)
    {
        $shopId = $paramsInput['shopId'];
        $requireDate = $paramsInput['requireDate'];
        $paramsInput['page'] = (int)$paramsInput['page'];
        $paramsInput['pageSize'] = (int)$paramsInput['pageSize'];
        $orderGoodsModel = new OrderGoodsModel();
        $where = " orders.shopId={$shopId} and orders.requireTime >= '{$requireDate} 00:00:00' and orders.requireTime <= '{$requireDate} 23:59:59' and orders.isPay=1 and orders.isSelf != 1 and orders.orderStatus IN(2,3,4,16,17) ";
        $where .= " and o_goods.reportedOverflow=0 ";
        $where .= " and goods.goodsFlag=1 ";
        if (!empty($paramsInput['goodsKeywords'])) {
            $where .= " and (o_goods.goodsName like '%{$paramsInput['goodsKeywords']}%' or o_goods.goodsCode like '%{$paramsInput['goodsKeywords']}%') ";
        }
        if (isset($paramsInput['sortingGoodsStatus'])) {
            if (is_numeric($paramsInput['sortingGoodsStatus'])) {
                if ($paramsInput['sortingGoodsStatus'] == 0) {
                    $where .= " and o_goods.actionStatus = 0 ";
                }
                if ($paramsInput['sortingGoodsStatus'] == 1) {
                    $where .= " and o_goods.actionStatus > 1 ";
                }
            }
        }
        if (!empty($paramsInput['lineId'])) {
            $where .= " and orders.lineId={$paramsInput['lineId']} ";
        }
        if (!empty($paramsInput['idArr'])) {
            $paramsInput['idArr'] = implode(',', $paramsInput['idArr']);
            $where .= " and o_goods.id IN({$paramsInput['idArr']}) ";
        }
        $field = 'o_goods.id,o_goods.goodsId,o_goods.skuId,o_goods.goodsCode,o_goods.goodsName,o_goods.goodsSpec,o_goods.skuSpecStr,o_goods.actionStatus,o_goods.unitName,o_goods.sortingNum,o_goods.goodsNums,o_goods.remarks';
        $field .= ',orders.orderId,orders.orderNo';
        $field .= ',goods.goodsStock,goods.goodsUnit as purchase_price';
        $sql = $orderGoodsModel
            ->alias('o_goods')
            ->join("left join wst_goods goods on goods.goodsId=o_goods.goodsId")
            ->join("left join wst_orders orders on orders.orderId=o_goods.orderId")
            ->where($where)
            ->field($field)
            ->buildSql();
        $result = $orderGoodsModel->query($sql);
        $returnData = array(
            'currPage' => $paramsInput['page'],
            'pageSize' => $paramsInput['pageSize'],
            'start' => 0,
            'total' => 0,
            'totalPage' => 0,
            'root' => array()
        );
        if (empty($result)) {
            if ($paramsInput['usePage'] === 0) {
                return array();
            }
            return $returnData;
        }
        $goodsModule = new GoodsModule();
        $returnList = array();
        foreach ($result as $item) {
            if (!empty($item['skuId'])) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($item['skuId'], 2);
                $item['goodsStock'] = $skuDetail['skuGoodsStock'];
                $item['purchase_price'] = $skuDetail['purchase_price'];
            }
            $item['sortingGoodsStatusName'] = '未分拣';
            if ($item['actionStatus'] > 1) {
                $item['sortingGoodsStatusName'] = '已分拣';
            }
            $item['sortingDiffNum'] = bc_math($item['goodsStock'], $item['sortingNum'], 'bcsub', 3);
            $item['is_negative'] = 0;//正数
            if ((float)$item['sortingDiffNum'] < 0) {
                $item['is_negative'] = 1;//负数
            }
            //分拣差异值(1:差异小于0 2:差异大于0 3:差异等于0) 不传默认全部
            if (empty($paramsInput['sortDiffNum'])) {
                $returnList[] = $item;
            } else {
                if ($paramsInput['sortDiffNum'] == 1 && (float)$item['sortingDiffNum'] < 0) {
                    $returnList[] = $item;
                } elseif ($paramsInput['sortDiffNum'] == 2 && (float)$item['sortingDiffNum'] > 0) {
                    $returnList[] = $item;
                } elseif ($paramsInput['sortDiffNum'] == 3 && (float)$item['sortingDiffNum'] == 0) {
                    $returnList[] = $item;
                }
            }
        }
        if ($paramsInput['usePage'] === 0) {
            return $returnList;
        }
        $returnData = array(
            'currPage' => $paramsInput['page'],
            'pageSize' => $paramsInput['pageSize'],
            'start' => ($paramsInput['page'] - 1) * $paramsInput['pageSize'],
            'total' => count($returnList),
            'totalPage' => 0,
            'root' => array()
        );
        $returnData['root'] = array_slice($returnList, $returnData['start'], $returnData['pageSize']);
        $returnData['totalPage'] = ceil($returnData['total'] / $returnData['pageSize']);
        return $returnData;
    }
}
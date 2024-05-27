<?php
/**
 * 采购(废弃)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-10
 * Time: 16:01
 */

namespace App\Modules\Purchase;


use App\Models\BaseModel;
use App\Models\PurchaseGoodsModel;
use App\Modules\Goods\GoodsCatModule;
use Think\Model;

class PurchaseModule extends BaseModel
{
    /**
     * 商品采购-删除-多条件
     * @param int $order_id 订单id
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param object $trans
     * @return bool
     * */
    public function delPurchaseGoodsByParams(int $order_id, int $goods_id, $sku_id = 0, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new PurchaseGoodsModel();
        $where = array(
            'orderId' => $order_id,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
        );
        $res = $model->where($where)->delete();
        if (!$res) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 采购-订单商品采购列表
     * @param array $params
     * -int shop_id 门店id
     * -string orderNo 订单号
     * -datetime ordertime_start 下单时间-开始时间
     * -datetime ordertime_end 下单时间-结束时间
     * -date require_date 期望送达日期
     * -int cat_id 店铺分类id
     * -int goods_id 商品id
     * -int sku_id 商品skuId
     * -int cat_id 店铺分类id
     * -int page 页码
     * -int pageSize 分页条数
     * -int  分页条数
     * -int export 是否导出(0:否 1:是)
     * -int onekeyPurchase 一键采购(0:否 1:是)
     * -int isNeedMerge 是否需要合并(0:否 1:是)
     * @return array
     * */
    public function getPurchaseGoodsList(array $params)
    {
        $shop_id = (int)$params['shop_id'];
        $page = (int)$params['page'];
        $page_size = (int)$params['pageSize'];
        $pur_goods_mod = new PurchaseGoodsModel();
        $field = 'pur_g.orderId,pur_g.goodsId,pur_g.goodsNums,pur_g.goodsPrice,pur_g.skuId,pur_g.skuSpecAttr,pur_g.goodsName,pur_g.goodsThums,pur_g.remarks';
        $field .= ',goods.shopCatId1,goods.shopCatId2';
        $where = "orders.shopId={$shop_id} and orders.orderFlag=1 and users.userFlag=1 ";
        if (!empty($params['ordertime_start'])) {
            $where .= " and orders.createTime >= '{$params['ordertime_start']}' ";
        }
        if (!empty($params['ordertime_end'])) {
            $where .= " and orders.createTime <= '{$params['ordertime_end']}' ";
        }
        if (!empty($params['require_date'])) {
            $require_date_start = $params['require_date'] . ' 00:00:00';
            $require_date_end = $params['require_date'] . ' 23:59:59';
            $where .= " and orders.requireTime between '{$require_date_start}' and '{$require_date_end}' ";
        }
        if (!empty($params['goods_id'])) {
            $where .= " and pur_g.goodsId={$params['goods_id']}";
        }
        if (!empty($params['sku_id'])) {
            $where .= " and pur_g.skuId={$params['sku_id']}";
        }
        if (!empty($params['statusMark'])) {
            switch ($params['statusMark']) {
                case 'toBePaid'://待付款
                    $where .= " AND orders.orderStatus = -2 ";
                    break;
                case 'toBeAccepted'://待接单
                    $where .= " AND orders.orderStatus = 0 ";
                    break;
                case 'toBeDelivered'://待发货
                    $where .= " AND orders.orderStatus IN(1,2) ";
                    break;
                case 'toBeReceived'://待收货
                    $where .= " AND orders.orderStatus IN(3) and isSelf=0 ";
                    break;
                case 'confirmReceipt'://已完成
                    $where .= " AND orders.orderStatus = 4 ";
                    break;
                case 'toBePickedUp'://待取货,自提订单,商家发货后
                    $where .= " AND orders.orderStatus = 3 and o.isSelf=1 ";
                    break;
                case 'takeOutDelivery'://外卖配送
                    $where .= " AND orders.orderStatus IN(-6,7,8,9,10,11,16,17) ";
                    break;
                case 'invalid'://无效订单(用户取消或商家拒收)
                    $where .= " AND orders.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    break;
            }
        }
        if (!empty($params['orderNo'])) {
            $where .= " and orders.orderNo like '%{$params['orderNo']}%'";
        }
        $cat_module = new GoodsCatModule();
        if (!empty($params['cat_id'])) {
            $cat_id = (int)$params['cat_id'];
            $cat_detail = $cat_module->getShopCatDetailById($cat_id);
            if (!empty($cat_detail)) {
                if ($cat_detail['level'] == 1) {
                    $where .= " and goods.shopCatId1={$cat_id} ";
                } elseif ($cat_detail['level'] == 2) {
                    $where .= " and goods.shopCatId2={$cat_id} ";
                }
            }
        }
        $prefix = $pur_goods_mod->tablePrefix;
        if ($params['export'] == 1 || $params['onekeyPurchase'] == 1) {
            if ($params['isNeedMerge'] == 1) {
                $result = $pur_goods_mod
                    ->alias('pur_g')
                    ->join("left join {$prefix}goods goods on goods.goodsId=pur_g.goodsId")
                    ->join("left join {$prefix}orders orders on orders.orderId=pur_g.orderId")
                    ->join("left join {$prefix}users users on users.userId=orders.userId")
                    ->where($where)
                    ->field($field)
                    ->group('pur_g.goodsId,pur_g.skuId')
                    ->select();
            } else {
                $result = $pur_goods_mod
                    ->alias('pur_g')
                    ->join("left join {$prefix}goods goods on goods.goodsId=pur_g.goodsId")
                    ->join("left join {$prefix}orders orders on orders.orderId=pur_g.orderId")
                    ->join("left join {$prefix}users users on users.userId=orders.userId")
                    ->where($where)
                    ->field($field)
                    ->select();
                return $result;
            }
        } else {
            $result = $pur_goods_mod
                ->alias('pur_g')
                ->join("left join {$prefix}goods goods on goods.goodsId=pur_g.goodsId")
                ->join("left join {$prefix}orders orders on orders.orderId=pur_g.orderId")
                ->join("left join {$prefix}users users on users.userId=orders.userId")
                ->where($where)
                ->field($field)
                ->limit(($page - 1) * $page_size, $page_size)
                ->group('pur_g.goodsId,pur_g.skuId')
                ->select();
        }
        if ($params['isNeedMerge'] == 1) {
            $result1 = $pur_goods_mod
                ->alias('pur_g')
                ->join("left join {$prefix}goods goods on goods.goodsId=pur_g.goodsId")
                ->join("left join {$prefix}orders orders on orders.orderId=pur_g.orderId")
                ->join("left join {$prefix}users users on users.userId=orders.userId")
                ->where($where)
                ->field($field)
                ->select();

            foreach ($result as $key => &$item) {
                $item['goodsNums'] = 0;
                $item['key_id'] = $key + 1;
                $item['shopCat1Name'] = '';
                $item['shopCat2Name'] = '';
                $shopcat1_detail = $cat_module->getShopCatDetailById($item['shopCatId1']);
                if (!empty($shopcat1_detail)) {
                    $item['shopCat1Name'] = $shopcat1_detail['catName'];
                }
                $shopcat2_detail = $cat_module->getShopCatDetailById($item['shopCatId2']);
                if (!empty($shopcat2_detail)) {
                    $item['shopCat2Name'] = $shopcat2_detail['catName'];
                }
                foreach ($result1 as $item1) {
                    if ($item1['goodsId'] == $item['goodsId'] && $item1['skuId'] == $item['skuId']) {
                        $item['goodsNums'] += $item1['goodsNums'];
                    }
                }
                $item['goodsNums'] = (string)$item['goodsNums'];//统一格式
            }
            unset($item);
        }
        $total_result = $pur_goods_mod
            ->alias('pur_g')
            ->join("left join {$prefix}goods goods on goods.goodsId=pur_g.goodsId")
            ->join("left join {$prefix}orders orders on orders.orderId=pur_g.orderId")
            ->join("left join {$prefix}users users on users.userId=orders.userId")
            ->where($where)
            ->field($field)
            ->group('pur_g.goodsId,pur_g.skuId')
            ->select();

        if ($params['onekeyPurchase'] == 1 || $params['export'] == 1) {
            return $result;
        }
        $count = count((array)$total_result);
        $return_data = array(
            'root' => (array)$result,
            'currPage' => $page,
            'pageSize' => $page_size,
            'start' => ($page - 1) * $page_size,
            'total' => $count,
            'totalPage' => ceil(($count / $page_size)),
        );
        return $return_data;
    }
}
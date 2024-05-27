<?php

namespace App\Modules\Orders;

use App\Models\BaseModel;

/**
 * 统一提供给内部其他模块使用的订单类
 * Class OrdersServiceModule
 * @package App\Modules\Orders
 */
class OrdersServiceModule extends BaseModel
{
    /**
     * 根据条件获取订单商品明细详情
     * @param array $params <p>
     * int id 订单商品明细id
     * int orderId 订单id
     * int goodsId 商品id
     * int skuId skuid
     * </p>
     * @return array
     * */
    public function getOrderGoodsInfoByParams(array $params)
    {
        $module = new OrdersModule();
        $data = $module->getOrderGoodsInfoByParams($params);
        return $data;
    }

    /**
     * 根据条明细id获取订单商品明细详情
     * @param int $id
     * @return array
     * */
    public function getOrderGoodsInfoById(int $id)
    {
        $module = new OrdersModule();
        $data = $module->getOrderGoodsInfoById($id);
        return $data;
    }

    /**
     * 更新订单商品明细信息
     * @param array $params 需要更新的表字段
     * @param object $trans
     * @return array
     * */
    public function updateOrderGoodsInfo(array $params, $trans = null)
    {
        $module = new OrdersModule();
        $data = $module->updateOrderGoodsInfo($params, $trans);
        return $data;
    }

    /**
     * 根据订单id获取订单详情
     * @param int $order_id
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderInfoById(int $order_id, $field = '*')
    {
        $module = new OrdersModule();
        $data = $module->getOrderInfoById($order_id, $field);
        return $data;
    }

    /**
     * 根据订单id获取订单商品列表
     * @param int $order_id
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderGoodsListById(int $order_id, $field = '*')
    {
        $module = new OrdersModule();
        $data = $module->getOrderGoodsListById($order_id, $field);
        return $data;
    }

    /**
     * 增加订单商品销量
     * @param int $order_id 订单id
     * @param object $trans
     * @return bool
     * */
    public function IncOrderGoodsSale(int $order_id, $trans = null)
    {
        $module = new OrdersModule();
        $data = $module->IncOrderGoodsSale($order_id, $trans);
        return (bool)$data;
    }
}
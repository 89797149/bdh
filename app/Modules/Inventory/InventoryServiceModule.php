<?php

namespace App\Modules\Inventory;

use App\Models\BaseModel;

/**
 * 统一提供给内部其他模块使用的盘点类
 * Class InventoryServiceModule
 * @package App\Modules\User
 */
class InventoryServiceModule extends BaseModel
{
    /**
     * 库存盘点-创建盘点单
     * @param array $params
     * @param json $trans
     * @return array
     * */
    public function addInventoryBill(array $params, $trans = null)
    {
        $module = new InventoryModule();
        $data = $module->addInventoryBill($params, $trans);
        return $data;
    }

    /**
     * 库存盘点-更新盘点单
     * @param array $params
     * @param json $trans
     * @return array
     * */
    public function updateInventoryBill(array $params, $trans = null)
    {
        $module = new InventoryModule();
        $data = $module->updateInventoryBill($params, $trans);
        return $data;
    }

    /**
     * 库存盘点-获取盘点记录详情
     * @param int $bill_id
     * @param string $field 表字段
     * @return array
     * */
    public function getInventoryBillDetail(int $bill_id, $field = '*')
    {
        $module = new InventoryModule();
        $data = $module->getInventoryBillDetail($bill_id, $field);
        return $data;
    }

    /**
     * 报损-报损详情
     * @param int $loss_id
     * @param string $field 表字段
     * @return array
     * */
    public function getLossDetail(int $loss_id, $field = '*')
    {
        $module = new InventoryLossModule();
        $data = $module->getLossInfoById($loss_id, $field);
        return $data;
    }

    /**
     * 报损-更新报损
     * @param array $params
     * @param json $trans
     * @return array
     * */
    public function updateLoss(array $params, $trans = null)
    {
        $module = new InventoryLossModule();
        $data = $module->updateLoss($params, $trans);
        return $data;
    }

    /**
     * 报损-删除报损
     * @param string $loss_id
     * @param json $trans
     * @return array
     * */
    public function deleteLoss(array $params, $trans = null)
    {
        $module = new InventoryLossModule();
        $data = $module->deleteLoss($params, $trans);
        return $data;
    }
}
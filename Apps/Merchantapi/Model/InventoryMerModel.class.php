<?php

namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Models\InventoryBillModel;
use App\Models\InventoryLossModel;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Inventory\InventoryServiceModule;
use App\Modules\Inventory\LocationModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 管理端-盘点功能类
 */
class InventoryMerModel extends BaseModel
{

    /**
     * 库存盘点-获取盘点记录列表
     * @param array $params <p>
     * int shop_id 门店id
     * int string inventory_user_name 盘点人员姓名
     * int string confirm_user_name 确认人员姓名
     * string goodsName 商品名称
     * string bill_no 单号
     * int confirm_status 处理状态(0:未确认 1:已确认)
     * date start_date 开始日期
     * date end_date 结束日期
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * */
    public function getInventoryBillList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " shop_id={$shop_id} ";
        $where_find = array();
        $where_find['bill.inventory_user_name'] = function () use ($params) {
            if (empty($params['inventory_user_name'])) {
                return null;
            }
            return array('like', "%{$params['inventory_user_name']}%", 'and');
        };
        $where_find['bill.confirm_user_name'] = function () use ($params) {
            if (empty($params['confirm_user_name'])) {
                return null;
            }
            return array('like', "%{$params['confirm_user_name']}%", 'and');
        };
        $where_find['bill.bill_no'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return array('like', "%{$params['bill_no']}%", 'and');
        };
        $where_find['bill.confirm_status'] = function () use ($params) {
            if (!is_numeric($params['confirm_status'])) {
                return null;
            }
            return array('=', "{$params['confirm_status']}", 'and');
        };
        $where_find['bill.create_time'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            $params['start_date'] = strtotime($params['start_date'] . ' 00:00:00');
            $params['end_date'] = strtotime($params['end_date'] . ' 23:59:59');
            return array('between', "{$params['start_date']} ' and '{$params['end_date']}", 'and');
        };
        $where_find['goods.goodsName'] = function () use ($params) {
            if (empty($params['goodsName'])) {
                return null;
            }
            return array('like', "%{$params['goodsName']}%", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where} and $where_find ";
        }
        $field = 'bill.bill_id,bill.bill_no,bill.total_goods_num,bill.total_profit_loss,bill.inventory_user_name,bill.inventory_time,bill.remark,bill.confirm_status,bill.confirm_time,bill.confirm_user_name,bill.create_time';
        $sql = "select {$field} from __PREFIX__inventory_bill bill ";
        $sql .= " left join wst_inventory_bill_relation relation on relation.bill_id=bill.bill_id ";
        $sql .= " left join wst_goods goods on goods.goodsId=relation.goods_id ";
        $sql .= " where {$where_info} ";
        $sql .= " group by bill.bill_id ";
        $sql .= "order by bill.confirm_status ASC,bill.create_time desc ";
        $list = $this->pageQuery($sql, $page, $pageSize);
        foreach ($list['root'] as &$item) {
            $item['total_profit_loss'] = (float)$item['total_profit_loss'];
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if ($item['confirm_status'] != 1) {
                $item['confirm_time'] = '';
            } else {
                $item['confirm_time'] = date('Y-m-d H:i:s', $item['confirm_time']);
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($list)->setMsg('成功')->toArray();
    }

    /**
     * 盘点库存-确认完成盘点
     * @param int $bill_id 盘点单id
     * @param array login_info
     * @return array
     * */
    public function completeInventoryBill(int $bill_id, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $inventory_service_module = new InventoryServiceModule();
        $bill_result = $inventory_service_module->getInventoryBillDetail($bill_id);
        if ($bill_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('请传入正确的盘点单id')->toArray();
        }
        $bill_info = $bill_result['data'];
        if ($bill_info['confirm_status'] != 0) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('非未确认的盘点单不能执行该操作')->toArray();
        }
        $m = new Model();
        $bill_save = array(
            'bill_id' => $bill_info['bill_id'],
            'confirm_user_id' => $login_info['user_id'],
            'confirm_user_type' => $login_info['user_type'],
            'confirm_user_name' => $login_info['user_username'],
            'confirm_status' => 1,
        );
        $save_result = $inventory_service_module->updateInventoryBill($bill_save, $m);
        if ($save_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，盘点单更新失败')->toArray();
        }
        $goods = $bill_info['goods'];
        $goods_module = new GoodsModule();
        //更新商品库存
        foreach ($goods as $item) {
            $goods_id = (int)$item['goods_id'];
            $sku_id = (int)$item['sku_id'];
            $profit_loss = (float)$item['profit_loss'];
            if ($profit_loss == 0) {
                continue;
            } elseif ($profit_loss > 0) {
                //加库存
                $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $profit_loss, 1, 1, $m);
            } elseif ($profit_loss < 0) {
                //减库存
                $profit_loss = abs($profit_loss);
                $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $profit_loss, 1, 1, $m);
            }
            if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，库存更新失败')->toArray();
            }
            $reset_res = $goods_module->resetGoodsStock($goods_id, $sku_id, $m);
            if ($reset_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，库存重置失败')->toArray();
            }
        }
        $m->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }


    /**
     * 根据父级id获取货位列表
     * @param array $params <p>
     * int shop_id 门店id
     * int parent_id 父级id,默认为0:一级货位
     * int page
     * int pageSize
     * </p>
     * @return array
     * */
    public function getLocationListById(array $params)
    {
        $response = LogicResponse::getInstance();
        $parent_id = $params['parent_id'];
        $shop_id = $params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = " shopId={$shop_id} and parentId={$parent_id} and lFlag=1 ";
        $sql = "select lid,shopId,parentId,name,sort from __PREFIX__location where {$where} order by sort desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }


    /**
     * 报损-报损记录列表
     * @param array $params <p>
     * int shop_id 门店id
     * string inventory_user_name 报损人员姓名
     * string keyword 关键字(商品名称,编码)
     * int confirm_status 确认状态(0:未确认 1:已确认)
     * date startDate 开始日期
     * date endDate 结束日期
     * int one_lid 一级货位id
     * int two_lid 二级货位id
     * int page
     * int pageSize
     * </p>
     * @return array
     * */
    public function getLossList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " loss.shop_id={$shop_id} and loss.is_delete=0";
        $where_find = array();
        $where_find['loss.create_time'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            $start_date = strtotime($params['startDate'] . ' 00:00:00');
            $end_date = strtotime($params['endDate'] . ' 23:59:59');
            return array('between', "{$start_date}' and '{$end_date}", 'and');
        };
        $where_find['loss.confirm_status'] = function () use ($params) {
            if (!is_numeric($params['confirm_status'])) {
                return null;
            }
            return array('=', "{$params['confirm_status']}", 'and');
        };
        $where_find['loss.one_lid'] = function () use ($params) {
            if (!is_numeric($params['one_lid'])) {
                return null;
            }
            return array('=', "{$params['one_lid']}", 'and');
        };
        $where_find['loss.two_lid'] = function () use ($params) {
            if (!is_numeric($params['two_lid'])) {
                return null;
            }
            return array('=', "{$params['two_lid']}", 'and');
        };
        $where_find['loss.inventory_user_name'] = function () use ($params) {
            if (empty($params['inventory_user_name'])) {
                return null;
            }
            return array('like', "%{$params['inventory_user_name']}%", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = " {$where} and {$where_find} ";
        }
        if (!empty($params['keyword'])) {
            $where_info .= " and (goods.goodsName like '%{$params['keyword']}%' or loss.code like '%{$params['keyword']}%')";
        }
        $field = 'loss.loss_id,loss.code,loss.sku_spec_attr,loss.loss_num,loss.loss_reason,loss.remark,loss.confirm_status,loss.confirm_user_name,loss.confirm_time,loss.create_time,loss.sku_id,loss.goods_id,loss.inventory_user_name,loss.one_lid,loss.two_lid';
        $field .= ',goods.goodsName,goods.goodsImg';
        $sql = "select {$field} from __PREFIX__inventory_loss loss left join wst_goods goods on goods.goodsId=loss.goods_id where {$where_info} order by loss.confirm_status ASC,loss.create_time desc";
        $result = $this->pageQuery($sql, $page, $pageSize);
        $goods_module = new GoodsModule();
        $location_module = new LocationModule();
        foreach ($result['root'] as &$item) {
            $item['one_lid_name'] = '';
            $item['two_lid_name'] = '';
            if ($item['one_lid'] > 0) {
                $location_result = $location_module->getLocationInfoById($item['one_lid']);
                $item['one_lid_name'] = (string)$location_result['data']['name'];
            }
            if ($item['two_lid'] > 0) {
                $location_result = $location_module->getLocationInfoById($item['two_lid']);
                $item['two_lid_name'] = (string)$location_result['data']['name'];
            }
            $item['loss_num'] = (float)$item['loss_num'];
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if ($item['confirm_status'] != 1) {
                $item['confirm_time'] = '';
            } else {
                $item['confirm_time'] = date('Y-m-d H:i:s', $item['confirm_time']);
            }
            if ($item['sku_id'] > 0) {
                $sku_system_result = $goods_module->getSkuSystemInfoById($item['sku_id']);
                if ($sku_system_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    continue;
                }
                $sku_info = $sku_system_result['data'];
                if (!empty($sku_info['skuGoodsImg']) && $sku_info['skuGoodsImg'] != -1) {
                    $item['goodsImg'] = $sku_info['skuGoodsImg'];
                }
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 报损-确认报损
     * @param int $loss_id 报损id
     * @param array login_info
     * @return array
     * */
    public function completeLoss(int $loss_id, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $inventory_service_module = new InventoryServiceModule();
        $result = $inventory_service_module->getLossDetail($loss_id);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('请传入正确的报损id')->toArray();
        }
        $info = $result['data'];
        if ($info['confirm_status'] != 0) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('非未确认的报损不能执行该操作')->toArray();
        }
        $m = new Model();
        $m->startTrans();
        $save = array(
            'loss_id' => $info['loss_id'],
            'confirm_user_id' => $login_info['user_id'],
            'confirm_user_type' => $login_info['user_type'],
            'confirm_user_name' => $login_info['user_username'],
            'confirm_status' => 1,
        );
        $save_result = $inventory_service_module->updateLoss($save, $m);
        if ($save_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，报损更新失败')->toArray();
        }
        $goods_module = new GoodsModule();
        //更新商品库存
        $goods_id = (int)$info['goods_id'];
        $sku_id = (int)$info['sku_id'];
        $loss_num = (float)$info['loss_num'];
        if ($loss_num > 0) {
            //减库存
            $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $loss_num, 1, 1, $m);
        }
        if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，库存更新失败')->toArray();
        }
        //出库单
        $exWarehouseModule = new ExWarehouseOrderModule();
        $billData = array(
            'pagetype' => 6,
            'shopId' => $info['shop_id'],
            'user_id' => $info['inventory_user_id'],
            'user_name' => $info['inventory_user_name'],
            'remark' => '',
            'relation_order_number' => '',
            'relation_order_id' => 0,
            'goods_data' => array(
                array(
                    'goods_id' => $info['goods_id'],
                    'sku_id' => $info['sku_id'],
                    'nums' => $info['loss_num'],
                    'actual_delivery_quantity' => $info['loss_num'],
                )
            ),
        );
        $addBillRes = $exWarehouseModule->addExWarehouseOrder($billData, $m);
        if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，出库单创建失败')->toArray();
        }
        $reset_res = $goods_module->resetGoodsStock($goods_id, $sku_id, $m);
        if ($reset_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，库存重置失败')->toArray();
        }
        $m->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }
}
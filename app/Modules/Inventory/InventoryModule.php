<?php

namespace App\Modules\Inventory;


use App\Enum\ExceptionCodeEnum;
use App\Models\InventoryBillModel;
use App\Models\BaseModel;
use App\Models\InventoryBillRelationModel;
use App\Models\InventoryCacheModel;
use App\Modules\Goods\GoodsModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 盘点类
 * Class UsersModule
 * @package App\Modules\Inventory
 */
class InventoryModule extends BaseModel
{
    /**
     * 库存盘点-创建盘点单
     * @param array $params <p>
     * int shop_id 门店id
     * int total_goods_num 商品总数量
     * float total_profit_loss 总盈亏数量
     * int inventory_user_type 盘点人类型(1:管理员 2:职员)
     * int inventory_user_id 盘点人id
     * string inventory_user_name 盘点人姓名
     * datetime inventory_time 盘点时间
     * string remark 备注
     * </p>
     * @param object $trans
     * @return array
     * */
    public function addInventoryBill(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryBillModel();
        $save = array(
            'shop_id' => null,
            'total_goods_num' => null,
            'total_profit_loss' => null,
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'inventory_user_name' => null,
            'inventory_time' => null,
            'remark' => null,
            'create_time' => time(),
        );
        parm_filter($save, $params);
        $insert_id = $model->add($save);
        if (!$insert_id) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('盘点单创建失败')->toArray();
        }
        $date = date('Ymd');
        $bill_save = array(
            'bill_id' => $insert_id,
            'bill_no' => $date . str_pad($insert_id, 10, "0", STR_PAD_LEFT),
        );
        $update_result = $this->updateInventoryBill($bill_save, $m);
        if ($update_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('盘点单更新失败')->toArray();
        }
        $result = array(
            'bill_id' => $insert_id
        );
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 库存盘点-创建盘点单
     * @param array $params <p>
     * int bill_id 盘点id
     * string bill_no 单号
     * int total_goods_num 商品总数量
     * float total_profit_loss 总盈亏数量
     * int inventory_user_type 盘点人类型(1:管理员 2:职员)
     * int inventory_user_id 盘点人id
     * string inventory_user_name 盘点人姓名
     * datetime inventory_time 盘点时间
     * string remark 备注
     * int confirm_status 处理状态(0:未处理 1:已处理)
     * int confirm_user_id 确认人id
     * int confirm_user_type 确认人类型(1:管理员 2:职员)
     * int confirm_user_name 确认人名称
     * </p>
     * @param object $trans
     * @return array
     * */
    public function updateInventoryBill(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryBillModel();
        $save = array(
            'bill_id' => null,
            'bill_no' => null,
            'total_goods_num' => null,
            'total_profit_loss' => null,
            'inventory_user_type' => null,
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'inventory_user_name' => null,
            'inventory_time' => null,
            'remark' => null,
            'confirm_status' => null,
            'confirm_user_id' => null,
            'confirm_user_type' => null,
            'confirm_user_name' => null,
            'update_time' => time(),
        );
        parm_filter($save, $params);
        if ($save['confirm_status'] == 1) {
            $save['confirm_time'] = time();
        }
        $update_res = $model->save($save);
        if ($update_res === false) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('盘点单修改失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->toArray();
    }

    /**
     * 库存盘点-获取盘点单详情
     * @param int $bill_id
     * @param string $field
     * @return array
     * */
    public function getInventoryBillDetail(int $bill_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $bill_model = new InventoryBillModel();
        $where = array(
            'bill_id' => $bill_id
        );
        $info = $bill_model->where($where)->field($field)->find();
        if (empty($info)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        if ($info['confirm_status'] == 0) {
            $info['confirm_time'] = '';
        } elseif ($info['confirm_status'] == 1) {
            $info['confirm_time'] = date('Y-m-d H:i:s', $info['confirm_time']);
        }
        $info['total_profit_loss'] = (float)$info['total_profit_loss'];
        $info['goods'] = array();
        $relation_model = new InventoryBillRelationModel();
        $field = 'relation.goods_id,relation.sku_id,relation.sku_spec_attr,relation.current_stock,relation.old_stock,relation.profit_loss';
        $field .= ',goods.goodsName,goods.goodsImg,goods.unit';
        $goods = $relation_model
            ->alias('relation')
            ->join('left join wst_goods goods on goods.goodsId=relation.goods_id')
            ->where(array(
                'relation.bill_id' => $info['bill_id']
            ))->field($field)->select();
        if (!empty($goods)) {
            $goods_module = new GoodsModule();
            foreach ($goods as &$item) {
                if ($item['sku_id'] > 0) {
                    $sku_result = $goods_module->getSkuSystemInfoById($item['sku_id']);
                    if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        continue;
                    }
                    $sku_info = $sku_result['data'];
                    $item['unit'] = (string)$sku_info['unit'];
                    if (!empty($sku_info) && $sku_info['skuGoodsImg'] != -1) {
                        $item['goodsImg'] = $sku_info['skuGoodsImg'];
                    }
                }
                $item['current_stock'] = (float)$item['current_stock'];
                $item['old_stock'] = (float)$item['old_stock'];
                $item['profit_loss'] = (float)$item['profit_loss'];
            }
            unset($item);
            $info['goods'] = $goods;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($info)->toArray();
    }

    /**
     * 盘点商品缓存-保存/修改
     * @param array $params <p>
     * int cache_id 缓存id
     * int inventory_user_type 盘点人类型(1:管理员 2:职员) PS:主要为了避免管理员账号登陆
     * int inventory_user_id 盘点人员id
     * int goods_id 商品id
     * int sku_id 规格id
     * float current_stock 盘点现库存
     * int checked 选中状态(0:未选中 1:选中)
     * int is_delete 删除状态(0:未删除 1:已删除)
     * </p>
     * @param object $trans
     * @return int $cache_id
     * */
    public function saveInventoryCache(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'goods_id' => null,
            'sku_id' => null,
            'current_stock' => null,
            'is_delete' => 0,
            'update_time' => time()
        );
        parm_filter($save, $params);
        if ($save['is_delete'] == 1) {
            $save['delete_time'] = time();
        }
        $where = array(
            'cache_id' => null
        );
        parm_filter($where, $params);
        if (empty($where['cache_id'])) {
            $save['create_time'] = time();
        }
        $model = new InventoryCacheModel();
        if (empty($params['cache_id'])) {
            $save_res = $model->add($save);
        } else {
            $save_res = $model->where($where)->save($save);
        }
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($params['cache_id'])) {
            $cache_id = $save_res;
        } else {
            $cache_id = $params['cache_id'];
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$cache_id;
    }

    /**
     * 盘点商品缓存-多条件获取盘点商品缓存详情
     * @param array $params <p>
     * int cache_id 缓存id
     * int inventory_user_type 盘点人类型(1:管理员 2:职员)
     * int inventory_user_id 盘点人员id
     * int goods_id 商品id
     * int sku_id 规格id
     * </p>
     * @return array
     * */
    public function getCacheDetailByParams(array $params, $field = '*')
    {
        $where = array(
            'cache_id' => null,
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'goods_id' => null,
            'sku_id' => null,
            'is_delete' => 0,
        );
        parm_filter($where, $params);
        $model = new InventoryCacheModel();
        $detail = $model->where($where)->field($field)->find();
        if (empty($detail)) {
            return array();
        }
        if (!empty($detail['create_time'])) {
            $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);
        }
        return (array)$detail;
    }

    /**
     * 盘点商品缓存-根据id获取盘点商品缓存详情
     * @param int $cache_id
     * @return array
     * */
    public function getCacheDetailById(int $cache_id)
    {
        $where = array(
            'cache_id' => $cache_id,
            'is_delete' => 0,
        );
        $model = new InventoryCacheModel();
        $detail = $model->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);
        return (array)$detail;
    }

    /**
     * 盘点商品缓存-根据盘点人员信息获取盘点商品缓存列表
     * @param int $inventory_user_id 盘点人员id
     * @param int $inventory_user_type 盘点人类型(1:管理员 2:职员)
     * @return array
     * */
    public function getCacheListByInventoryUser(int $inventory_user_id, int $inventory_user_type, $field = '*')
    {
        $where = array(
            'inventory_user_id' => $inventory_user_id,
            'inventory_user_type' => $inventory_user_type,
            'is_delete' => 0,
        );
        $model = new InventoryCacheModel();
        $list = $model->where($where)->field($field)->select();
        if (empty($list)) {
            return array();
        }
        foreach ($list as $item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        unset($item);
        return (array)$list;
    }

    /**
     * 盘点商品缓存-递增缓存现库存
     * @param int $cache_id 缓存id
     * @param float $stock 需要递增的数量
     * @param object $trans
     * @return bool
     * */
    public function incCacheCurrentStock(int $cache_id, $stock, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryCacheModel();
        $where = array(
            'cache_id' => $cache_id
        );
        $res = $model->where($where)->setInc('current_stock', $stock);
        if (!$res) {
            $m->rollback();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (bool)$res;
    }

    /**
     * 盘点商品缓存-递减缓存现库存
     * @param int $cache_id 缓存id
     * @param float $stock 需要递增的数量
     * @param object $trans
     * @return bool
     * */
    public function decCacheCurrentStock(int $cache_id, $stock, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryCacheModel();
        $where = array(
            'cache_id' => $cache_id
        );
        $res = $model->where($where)->setDec('current_stock', $stock);
        if (!$res) {
            $m->rollback();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (bool)$res;
    }

    /**
     * 盘点商品缓存-清除所有的盘点商品缓存
     * @param int $inventory_user_id 盘点人员id
     * @param int $inventory_user_type 盘点人类型(1:管理员 2:职员)
     * @return array
     * */
    public function clearInventoryGoods(int $inventory_user_id, int $inventory_user_type, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $where = array(
            'inventory_user_id' => $inventory_user_id,
            'inventory_user_type' => $inventory_user_type,
            'is_delete' => 0,
        );
        $model = new InventoryCacheModel();
        $save = array(
            'is_delete' => 1,
            'delete_time' => time(),
        );
        $save_res = $model->where($where)->save($save);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 盘点商品缓存-获取当前盘点人员的商品总数,总盈亏数
     * @param int $inventory_user_id 盘点人员id
     * @param int $inventory_user_type 盘点人类型(1:管理员 2:职员)
     * @return array
     * */
    public function getInventoryUserCacheTotal(int $inventory_user_id, int $inventory_user_type)
    {
        $cache_list = $this->getCacheListByInventoryUser($inventory_user_id, $inventory_user_type);
        $old_goods_stock_total = 0;//所有商品总库存(原库存)
        $current_goods_stock_total = 0;//所有商品总库存(盘点现库存)
        $goods_module = new GoodsModule();
        foreach ($cache_list as $item) {
            $goods_id = $item['goods_id'];
            $sku_id = $item['sku_id'];
            $goods_result = $goods_module->getGoodsInfoById($goods_id);
            $goods_data = $goods_result['data'];
            $single_goods_stock = (float)$goods_data['goodsStock'];
            if (!empty($sku_id)) {
                $sku_system_result = $goods_module->getSkuSystemInfoById($sku_id);
                $sku_system_detail = $sku_system_result['data'];
                $single_goods_stock = (float)$sku_system_detail['skuGoodsStock'];
            }
            $old_goods_stock_total += $single_goods_stock;
            $current_goods_stock_total += (float)$item['current_stock'];
        }
        $total_goods_num = count($cache_list);
        $total_profit_loss = (float)bc_math($current_goods_stock_total, $old_goods_stock_total, 'bcsub', 3);
        return array(
            'total_goods_num' => $total_goods_num,//商品总数
            'total_profit_loss' => $total_profit_loss,//商品总盈亏数
        );
    }

    /**
     * 盘点商品缓存-获取当前商品的原库存,盘点现库存,盈亏数
     * @param int $cache_id 缓存id
     * @return array
     * */
    public function getCurrentGoodsCacheTotal(int $cache_id)
    {
        $goods_module = new GoodsModule();
        $cache_detail = $this->getCacheDetailById($cache_id);
        $goods_id = $cache_detail['goods_id'];
        $sku_id = $cache_detail['sku_id'];
        $goods_result = $goods_module->getGoodsInfoById($goods_id);
        $goods_detail = $goods_result['data'];
        $old_stock = (float)$goods_detail['goodsStock'];
        if (!empty($sku_id)) {
            //有规格
            $sku_system_result = $goods_module->getSkuSystemInfoById($sku_id);
            $sku_system_detail = $sku_system_result['data'];
            $old_stock = (float)$sku_system_detail['skuGoodsStock'];
        }
        $current_stock = (float)$cache_detail['current_stock'];
        $profit_loss = (float)bc_math($current_stock, $old_stock, 'bcsub', 3);
        return array(
            'old_stock' => $old_stock,//原库存
            'current_stock' => $current_stock,//现库存
            'profit_loss' => $profit_loss,//盈亏数
        );
    }

}
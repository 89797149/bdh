<?php

namespace App\Modules\Inventory;


use App\Enum\ExceptionCodeEnum;
use App\Models\InventoryLossModel;
use App\Models\ReportLossReasonModel;
use App\Modules\Goods\GoodsModule;
use CjsProtocol\LogicResponse;
use App\Models\BaseModel;
use Think\Model;

/**
 * 商品报损表
 * Class InventoryLossModule
 * @package App\Modules\Inventory
 */
class InventoryLossModule extends BaseModel
{

    /**
     * 添加报损
     * @param array $params <p>
     * int shop_id 门店id
     * int goods_id 商品id
     * int sku_id
     * int one_lid 一级货位id
     * int two_lid 二级货位id
     * string code 编码
     * string sku_spec_attr sku属性值
     * float loss_num 报损数量
     * string loss_reason 报损原因
     * string remark 报损备注
     * string loss_pic 凭证照片
     * int inventory_user_type 报损人员类型
     * int inventory_user_id 报损人员id
     * int inventory_user_name 报损人员名称
     * </p>
     * @param object $trans
     * @return array
     * */
    public function addLoss(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryLossModel();
        $save = array(
            'shop_id' => null,
            'goods_id' => null,
            'sku_id' => null,
            'one_lid' => null,
            'two_lid' => null,
            'code' => null,
            'sku_spec_attr' => null,
            'loss_num' => null,
            'loss_reason' => null,
            'remark' => null,
            'loss_pic' => null,
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'inventory_user_name' => null,
            'create_time' => time(),
        );
        parm_filter($save, $params);
        $insert_id = $model->add($save);
        if (!$insert_id) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加报损失败')->toArray();
        }
        $result = array(
            'loss_id' => $insert_id
        );
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 修改报损
     * @param array $params <p>
     * int loss_id 报损id
     * int goods_id 商品id
     * int sku_id
     * int one_lid 一级货位id
     * int two_lid 二级货位id
     * string code 编码
     * string sku_spec_attr sku属性值
     * float loss_num 报损数量
     * string loss_reason 报损原因
     * string remark 报损备注
     * string loss_pic 凭证照片
     * int inventory_user_type 报损人员类型
     * int inventory_user_id 报损人员id
     * int inventory_user_name 报损人员名称
     * int inventory_user_name 报损人员名称
     * int is_delete 删除状态(0:未删除 1:已删除)
     * int confirm_user_type 确认人员类型(1:管理员 2:职员)
     * int confirm_user_id 确认人员id
     * string confirm_user_name 确认人员姓名
     * datetime confirm_time 确认时间
     * </p>
     * @param object $trans
     * @return array
     * */
    public function updateLoss(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryLossModel();
        $save = array(
            'loss_id' => null,
            'goods_id' => null,
            'sku_id' => null,
            'one_lid' => null,
            'two_lid' => null,
            'code' => null,
            'sku_spec_attr' => null,
            'loss_num' => null,
            'loss_reason' => null,
            'remark' => null,
            'loss_pic' => null,
            'inventory_user_type' => null,
            'inventory_user_id' => null,
            'inventory_user_name' => null,
            'confirm_status' => null,
            'is_delete' => null,
            'confirm_user_type' => null,
            'confirm_user_id' => null,
            'confirm_user_name' => null,
            'update_time' => time(),
        );
        parm_filter($save, $params);
        if ($params['is_delete'] == 1) {
            $save['delete_time'] = time();
        }
        if ($params['confirm_status'] == 1) {
            $save['confirm_time'] = time();
        }
        $res = $model->save($save);
        if ($res === false) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改报损失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData(true)->toArray();
    }

    /**
     * 报损-根据报损id获取报损详情
     * @param int $loss_id 报损id
     * @param string $field 表字段
     * @return array
     * */
    public function getLossInfoById(int $loss_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new InventoryLossModel();
        $where = array(
            'loss_id' => $loss_id,
            'is_delete' => 0,
        );
        $info = $model
            ->where($where)
            ->field($field)
            ->find();
        if (empty($info)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无报损记录')->toArray();
        }
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        if ($info['confirm_status'] != 1) {
            $info['confirm_time'] = '';
        } else {
            $info['confirm_time'] = date('Y-m-d H:i:s', $info['confirm_time']);
        }
        if (!empty($info['loss_pic'])) {
            $info['loss_pic'] = explode(',', $info['loss_pic']);
        }
        $goods_module = new GoodsModule();
        $goods_result = $goods_module->getGoodsInfoById($info['goods_id'], 'goodsName,goodsImg,unit');
        $goods_info = $goods_result['data'];
        $info['goodsName'] = $goods_info['goodsName'];
        $info['goodsImg'] = $goods_info['goodsImg'];
        $info['unit'] = $goods_info['unit'];
        if ($info['sku_id'] > 0) {
            $sku_system_result = $goods_module->getSkuSystemInfoById($info['sku_id']);
            $sku_info = $sku_system_result['data'];
            $info['unit'] = $sku_info['unit'];
            if (!empty($sku_info['skuGoodsImg']) && $sku_info['skuGoodsImg'] != -1) {
                $info['goodsImg'] = $sku_info['skuGoodsImg'];
            }
        }
        $location_module = new LocationModule();
        $info['one_lid_name'] = '';
        $info['two_lid_name'] = '';
        if ($info['one_lid'] > 0) {
            $location_result = $location_module->getLocationInfoById($info['one_lid'], 'name');
            $info['one_lid_name'] = $location_result['data']['name'];
        }
        if ($info['two_lid'] > 0) {
            $location_result = $location_module->getLocationInfoById($info['two_lid'], 'name');
            $info['two_lid_name'] = $location_result['data']['name'];
        }
        $info['loss_num'] = (float)$info['loss_num'];
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($info)->setMsg('成功')->toArray();
    }

    /**
     * 报损-删除报损
     * @param string $loss_id 报损id
     * @param object $trans
     * */
    public function deleteLoss($loss_id, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (is_array($loss_id)) {
            $loss_id = implode(',', $loss_id);
        }
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new InventoryLossModel();
        $where = array(
            'loss_id' => array('IN', $loss_id)
        );
        $save = array(
            'is_delete' => 1,
            'delete_time' => time(),
        );
        $res = $model->where($where)->save($save);
        if (!$res) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('删除失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
    }
}
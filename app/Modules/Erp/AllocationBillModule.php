<?php

namespace App\Modules\Erp;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\JxcAllocationBillInfoModel;
use App\Models\JxcAllocationBillModel;
use App\Modules\Goods\GoodsModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 调拨单
 */
class AllocationBillModule extends BaseModel
{
    /**
     * 保存调拨单信息
     * @param array $params <p>
     * int allId 单据id
     * string number 单号
     * int outShopId 出库仓id
     * int inputShopId 入库仓id
     * int status 调拨状态【-1:平台已拒绝|0:平台待审核|1:调出方待确认|2:等待调入方收货|3:调出方已交货】
     * int warehouseStatus 入库状态(0:待入库|1:部分入库|2:入库完成)
     * int warehouseUserId 入库人员id(PS:同wst_user表中的id) 改版后用处不大
     * int actionUserId 制单人id
     * int actionUserType 制单人类型【1：门店管理员|2：门店职员】
     * string actionUserName 制单人名称
     * string billPic 单据照片
     * string consigneeName 接货人姓名
     * </p>
     * @param object $trans
     * @return array
     * */
    public function saveAllocationBill(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'allId' => null,
            'number' => null,
            'outShopId' => null,
            'inputShopId' => null,
            'status' => null,
            'warehouseStatus' => null,
            'warehouseUserId' => null,
            'actionUserId' => null,
            'actionUserType' => null,
            'actionUserName' => null,
            'billPic' => null,
            'consigneeName' => null,
        );
        if (empty($save['allId'])) {
            $save['createTime'] = date('Y-m-d H:i:s');
        }
        parm_filter($save, $params);
        $model = new JxcAllocationBillModel();
        if (empty($save['allId'])) {
            $save_res = $model->add($save);
        } else {
            $save_res = $model->save($save);
        }
        if ($save_res === false) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('调拨单信息保存失败')->toArray();
        }
        if (empty($save['allId'])) {
            $insert_id = $save_res;
        } else {
            $insert_id = $save['allId'];
        }
        $result = array(
            'allId' => $insert_id
        );
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 保存调拨单商品明细
     * @param array $params <p>
     * int infoId 明细id
     * int allId 调拨单id
     * int goodsId 商品id
     * int skuId skuId
     * float num 调拨数量
     * float warehouseNum 应入库数量
     * float warehouseCompleteNum 已入库数量
     * </p>
     * @param object $trans
     * @return array
     * */
    public function saveAllocationBillInfo(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'infoId' => null,
            'allId' => null,
            'goodsId' => null,
            'skuId' => null,
            'num' => null,
            'warehouseNum' => null,
            'warehouseCompleteNum' => null,
        );
        parm_filter($save, $params);
        $model = new JxcAllocationBillInfoModel();
        if (empty($save['infoId'])) {
            $save_res = $model->add($save);
        } else {
            $save_res = $model->save($save);
        }
        if ($save_res === false) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('调拨单商品明细保存失败')->toArray();
        }
        if (empty($save['infoId'])) {
            $insert_id = $save_res;
        } else {
            $insert_id = $save['infoId'];
        }
        $result = array(
            'infoId' => $insert_id
        );
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 获取调拨单详情
     * @param int $allId 调拨单id
     * @return array
     * */
    public function getAllocationBillDetail(int $allId)
    {
        $where = "bill.dataFlag=1 and bill.allId='{$allId}' ";
        $field = 'bill.allId,bill.number,bill.outShopId,bill.inputShopId,bill.status,bill.createTime,bill.billPic,bill.consigneeName,bill.warehouseStatus,bill.warehouseUserName';
        $field .= ',info.infoId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsStock,goods.goodsImg,goods.goodsSn';
        $field .= ',inputShop.shopName as inputShopName,outShop.shopName as outShopName';
        $erp_module = new ErpModule();
        $model = new JxcAllocationBillInfoModel();
        $prefix = M()->tablePrefix;
        $res = $model
            ->alias('info')
            ->join("left join {$prefix}jxc_allocation_bill bill on bill.allId=info.allId")
            ->join("left join {$prefix}goods goods on goods.goodsId=info.goodsId ")
            ->join("left join {$prefix}shops inputShop on inputShop.shopId=bill.inputShopId ")
            ->join("left join {$prefix}shops outShop on outShop.shopId=bill.outShopId ")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($res)) {
            return array();
        }
        $res['warehouseNoNum'] = bc_math($res['warehouseNum'], $res['warehouseCompleteNum'], 'bcsub', 3);//剩余入库数量
        $goods_module = new GoodsModule();
        $res['skuSpecAttrStr'] = '';//规格
        $res['old_goods_sn'] = $res['goodsSn'];
        if ($res['skuId'] > 0) {
            $sku_system_res = $goods_module->getSkuSystemInfoById($res['skuId']);
            if ($sku_system_res['code'] == ExceptionCodeEnum::SUCCESS) {
                $sku_system_info = $sku_system_res['data'];
                $res['goodsStock'] = $sku_system_info['skuGoodsStock'];
                $res['goodsSn'] = $sku_system_info['skuBarcode'];
                $res['skuSpecAttrStr'] = $sku_system_info['skuSpecAttr'];
            }
        }
        //调拨单日志
        $res['log'] = $erp_module->getBillActionLogListByParams(array(
            'dataId' => $allId,
            'dataType' => 2,
        ));
        //调拨商品明细 PS:后加,前面的逻辑就不动了
        $res['goods'] = array();
        $where = array(
            'info.allId' => $allId
        );
        $field = 'info.infoId,info.allId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsSn,goods.goodsImg,goods.goodsThums,goods.shopPrice,goods.goodsStock';
        $goods = $model
            ->alias('info')
            ->join("left join {$prefix}goods goods on goods.goodsId=info.goodsId")
            ->where($where)
            ->field($field)
            ->select();
        foreach ($goods as &$item) {
            $item['warehouseNoNum'] = bc_math($item['warehouseNum'], $item['warehouseCompleteNum'], 'bcsub', 3);//剩余入库数量
            $item['skuInfo'] = array();
            if ($item['skuId'] > 0) {
                $sku_system_res = $goods_module->getSkuSystemInfoById($item['skuId']);
                if ($sku_system_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    continue;
                }
                $sku_system_info = $sku_system_res['data'];
                $item['goodsStock'] = $sku_system_info['skuGoodsStock'];
                $item['goodsSn'] = $sku_system_info['skuBarcode'];
                $item['shopPrice'] = $sku_system_info['skuShopPrice'];
                $sku_system_info['skuSpecAttr'] = $sku_system_info['skuSpecAttr'];
                $item['skuInfo'] = $sku_system_info;
            }
        }
        unset($item);
        $res['goods'] = (array)$goods;
        return $res;
    }
}





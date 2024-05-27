<?php

namespace App\Modules\Erp;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\JxcGoodsModel;
use App\Models\jxcPurchaseOrderInfoModel;
use App\Models\jxcPurchaseOrderModel;
use App\Models\JxcReinsurancePolicyDetailModel;
use App\Models\JxcReinsurancePolicyModel;
use App\Models\JxcSkuGoodsSelfModel;
use App\Models\JxcSkuGoodsSystemModel;
use App\Models\JxcSupplierModel;
use App\Models\JxcSysConfigModel;
use App\Models\SkuGoodsSystemModel;
use App\Modules\Base;
use Think\Model;

use App\Models\jxcBillActionLogModel;

//采购单/入库单 操作日志表


/**
 * ERP公共服务
 * 采购和入库有拆分
 */
class ErpModule extends BaseModel
{

    /**
     * 添加单据操作日志
     * $params array $params <p>
     *          int dataId 单据id
     *          int dataType 数据类型(1:采购单|2:调拨单)
     *          varchar action 操作描述
     *          int actionUserId 操作人id
     *          varchar actionUserName 姓名
     *          tinyint actionUserType 操作用户的类型(1:总仓|2:门店)
     *          int status 状态(根据数据类型到对应的表中查找状态)
     *          int warehouseStatus 入库状态(0:待入库|1:部分入库|2:已入库)
     *          int warehouseCompleteNum 已入库数量
     * </p>
     * */
    public function addBillActionLog($params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $logData = [];
        $logData['dataId'] = null;
        $logData['dataType'] = null;
        $logData['action'] = null;
        $logData['actionUserId'] = null;
        $logData['actionUserType'] = null;
        $logData['actionUserName'] = null;
        $logData['status'] = null;
        $logData['warehouseStatus'] = null;
        $logData['warehouseCompleteNum'] = null;
        parm_filter($logData, $params);
        $logData['createTime'] = date('Y-m-d H:i:s');
        $res = (new jxcBillActionLogModel())->add($logData);
        if (!$res) {
            $m->rollback();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (bool)$res;
    }

    /**
     * 获取单据日志列表
     * @param array <p>
     * int dataId 单据id
     * int dataType 数据类型(1:采购单|2:调拨单)
     * </p>
     * @return array
     * */
    public function getBillActionLogListByParams(array $params)
    {
        $model = new jxcBillActionLogModel();
        $where = array(
            'dataId' => null,
            'dataType' => null,
        );
        parm_filter($where, $params);
        $field = 'logId,action,actionUserName,createTime';
        $res = $model
            ->where($where)
            ->field($field)
            ->select();
        return (array)$res;
    }

    /**
     * 根据父采购单id获取子分单列表
     * @param int $otpId 采购单id
     * @param string $field 表字段
     * @return array $res
     * */
    public function getReinsuranceListByParentId(int $otpId, $field = '*')
    {
        $reinsurance_model = new JxcReinsurancePolicyModel();
        $where = array(
            'otpId' => $otpId,
        );
        $res = $reinsurance_model
            ->where($where)
            ->field($field)
            ->select();
        foreach ($res as &$item) {
            $supplierId = $item['supplierId'];
            $field = 'supplierId,name,linkMan,linkPhone';
            $supplier_detail = $this->getSupplierDetailById($supplierId, $field);
            $item['supplier_name'] = (string)$supplier_detail['name'];//供应商-供应商名称
            $item['supplier_linkman'] = (string)$supplier_detail['linkMan'];//供应商-联系人
            $item['supplier_linkphone'] = (string)$supplier_detail['linkPhone'];//供应商-联系电话
        }
        unset($item);
        return (array)$res;
    }

    /**
     * 根据分单id获取分单详情
     * @param int $rpId 分单id
     * @param string $field 表字段
     * @return array $res
     * */
    public function getReinsuranceDetailById(int $rpId, $field = '*')
    {
        $reinsurance_model = new JxcReinsurancePolicyModel();
        $where = array(
            'rpId' => $rpId,
        );
        $res = $reinsurance_model
            ->where($where)
            ->field($field)
            ->find();
        if (empty($res)) {
            return array();
        }
        $supplierId = $res['supplierId'];
        $field = 'supplierId,name,linkMan,linkPhone';
        $supplier_detail = $this->getSupplierDetailById($supplierId, $field);
        $res['supplier_name'] = (string)$supplier_detail['name'];//供应商-供应商名称
        $res['supplier_linkman'] = (string)$supplier_detail['linkMan'];//供应商-联系人
        $res['supplier_linkphone'] = (string)$supplier_detail['linkPhone'];//供应商-联系电话
        //获取分单下的采购商品
        $res['reinsuranceGoods'] = $this->getReinsuranceGoodsList($rpId);
        return (array)$res;
    }

    /**
     * @param $rpId
     * @return mixed
     * 获取分单下的采购商品
     */
    public function getReinsuranceGoodsList($rpId)
    {
        $reinsurancePolicyDetailModel = new JxcReinsurancePolicyDetailModel();
        $where = [];
        $where['jrpd.rpId'] = $rpId;
        $res = $reinsurancePolicyDetailModel
            ->alias('jrpd')
            ->join('left join wst_jxc_reinsurance_policy wjrp on wjrp.rpId = jrpd.rpId')
            ->join('left join wst_jxc_purchase_order wjpo on wjpo.otpId = wjrp.otpId')
            ->join('left join wst_jxc_goods wjg on wjg.goodsId = jrpd.goodsId')
            ->group('jrpd.infoId')
            ->where($where)
            ->field('wjpo.otpId,wjrp.rpId,jrpd.goodsId,wjg.goodsCat1 as goodsCatId1,wjg.goodsCat2 as goodsCatId2,wjg.goodsCat3 as goodsCatId3,wjrp.totalNum,wjrp.dataAmount,wjrp.createTime,wjg.sellPrice,jrpd.skuId')
            ->select();
        if (!empty($res)) {
            foreach ($res as $k => $v) {
                if (!empty($v['skuId'])) {
                    $jxcSkuGoodsSystemModel = new JxcSkuGoodsSystemModel();
                    $skuInfo = $jxcSkuGoodsSystemModel->where(['skuId' => $v['skuId']])->find();
                    $res[$k]['sellPrice'] = $skuInfo['sellPrice'];
                }
            }
        }
        return $res;
    }

    /**
     * 保存分单数据
     * @param array $params <p>
     * int rpId 分单id
     * string number 分单单号
     * int type 类型(1:供应商分单|2:总仓直采单)
     * int supplierId 供应商id
     * float totalNum 采购总数量
     * float dataAmount 单据金额
     * float actualAmount 实际金额
     * int status 实际金额
     * float status 状态(0:待处理|1:装拣中|2:配送中|3:已送达|4:已申请结算|5:已发货)
     * int purchaserId 采购员id
     * int settlementId 结算单id
     * int receivingStatus 收货状态(0:采购方待收货|1:采购方已收货)
     * string billPic 单据照片
     * string billPic 单据照片
     * string consigneeName 收货人姓名
     * </p>
     * @param object $trans
     * @return int $rpId
     * */
    public function saveReinsurance(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'otpId' => null,
            'number' => null,
            'type' => null,
            'supplierId' => null,
            'totalNum' => null,
            'dataAmount' => null,
            'actualAmount' => null,
            'status' => null,
            'purchaserId' => null,
            'settlementId' => null,
            'receivingStatus' => null,
            'billPic' => null,
            'consigneeName' => null,
        );
        parm_filter($save, $params);
        if (empty($params['rpId'])) {
            $save['createTime'] = date('Y-m-d H:i:s');
        }
        $where = array(
            'rpId' => null
        );
        parm_filter($where, $params);
        $model = new JxcReinsurancePolicyModel();
        $save_res = $model->where($where)->save($save);
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($params['rpId'])) {
            $rpId = $save_res;
        } else {
            $rpId = $params['rpId'];
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$rpId;
    }

    /**
     * 根据供应商品id获取供应商详情
     * @param int $supplierId 供应商id
     * @param string $field 表字段
     * @return array
     * */
    public function getSupplierDetailById(int $supplierId, $field = '*')
    {
        $model = new JxcSupplierModel();
        $where = array(
            'supplierId' => $supplierId,
            'dataFlag' => 1
        );
        $res = $model->where($where)->field($field)->find();
        return (array)$res;
    }

    /**
     * 保存采购单信息
     * @param array $params <p>
     * int otpId 采购单id
     * string number 单号
     * int shopId 店铺id
     * int actionUserId 制单人id
     * string actionUserName 制单人名称
     * string shopName 采购方店铺名称
     * int dataFrom 数据来源(1:门店管理员添加|2:门店职员添加|3:总仓添加)
     * int billType 单据类型(1:采购单转为入库单|2:手动创建入库单)
     * int supplyType 供货方式(1:供货到总仓|2:供货到分仓)[默认为-1]
     * int status 审核状态(-1:平台已拒绝|0:平台待审核|1:平台已审核)
     * int warehouseStatus 入库状态(0:待入库|1:部分入库|2:入库完成)
     * int warehouseUserId 入库人员id
     * int receivingStatus 收货状态(0:采购方待收货 1:采购方部分收货 2:采购方收货完成)
     * string remark 备注
     * float totalNum 采购总数量(字段类型和门店商品库存字段类型一致)
     * float dataAmount 单据金额
     * string billPic 单据照片 PS:采购方确认收货时使用
     * string consigneeName 接货人姓名 PS:采购方确认收货时使用
     * int takenSupplier 供货方【1总仓供货 2供应商供货】
     * </p>
     * @param object $trans
     * @return int $otpId
     * */
    public function savePurchaseOrder(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'number' => null,
            'shopId' => null,
            'actionUserId' => null,
            'actionUserName' => null,
            'shopName' => null,
            'dataFrom' => null,
            'billType' => null,
            'supplyType' => null,
            'status' => null,
            'warehouseStatus' => null,
            'warehouseUserId' => null,
            'receivingStatus' => null,
            'remark' => null,
            'totalNum' => null,
            'dataAmount' => null,
            'actualAmount' => null,
            'billPic' => null,
            'consigneeName' => null,
            'takenSupplier' => null,
            'dataFlag' => null,
        );
        parm_filter($save, $params);
        $model = new jxcPurchaseOrderModel();
        if (empty($params['otpId'])) {
            $save['createTime'] = date('Y-m-d H:i:s');
            $otpId = $model->add($save);
            if (empty($otpId)) {
                $m->rollback();
                return 0;
            }
        } else {
            $otpId = $params['otpId'];
            $where = array(
                'otpId' => $otpId
            );
            $save_res = $model->where($where)->save($save);
            if ($save_res === false) {
                $m->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$otpId;
    }

    /**
     * 采购单是否达到全部收货完成
     * @param int $otpId 采购单id
     * @return bool
     * */
    public function isPurchaseReceivingComplete(int $otpId)
    {
        $pur_detail = $this->getPurchaseOrderDetailById($otpId, 'otpId,takenSupplier,receivingStatus');
        if (empty($pur_detail)) {
            return false;
        }
        if ($pur_detail['takenSupplier'] == 1) {
            //总仓供货
            if ($pur_detail['receivingStatus'] == 2) {
                return true;
            }
        } else {
            //供应商供货
            $reinsurance_list = $this->getReinsuranceListByParentId($otpId, 'rpId,receivingStatus');
            $count = count($reinsurance_list);
            $complete_num = 0;//已确认收货的分单
            foreach ($reinsurance_list as $item) {
                if ($item['receivingStatus'] == 1) {
                    $complete_num += 1;
                }
            }
            if ($complete_num >= $count) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据采购单id获取采购单详情
     * @param int $otpId 采购单id
     * @param string $field 表字段
     * @return array
     * */
    public function getPurchaseOrderDetailById(int $otpId, $field = '*')
    {
        $pur_model = new jxcPurchaseOrderModel();
        $where = array();
        $where['otpId'] = $otpId;
        $where['dataFlag'] = 1;
        $detail = $pur_model->where($where)->field($field)->find();
        if (empty($detail)) {
            return array();
        }
        $info_model = new jxcPurchaseOrderInfoModel();
        $table_prefix = $info_model->tablePrefix;
        $field = 'info.infoId,info.otpId,info.goodsId,info.skuId,info.remark,info.unitPrice,info.totalNum,info.totalAmount,info.warehouseNum,info.warehouseCompleteNum,info.toSupplierId,info.purchase_price,info.purchase_price_total';
        $field .= ',goods.goodsName';
        $info_where = array(
            'info.otpId' => $otpId
        );
        $info_list = $info_model
            ->alias('info')
            ->join("left join {$table_prefix}jxc_goods goods on goods.goodsId=info.goodsId")
            ->where($info_where)
            ->field($field)
            ->select();
        $detail['info_list'] = (array)$info_list;//采购单明细
        return (array)$detail;
    }

    /**
     * 扣除总仓商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id 商品规格id
     * @param float $num 数量
     * @param object $trans
     * @return bool
     * */
    public function deductionErpStock(int $goods_id, int $sku_id, float $num, $trans = null)
    {
        //目前采购和调拨传过的数量,标品为数量单位,非标品为kg
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $goods_detail = $this->getErpGoodsDetailById($goods_id, 'goodsId,goodsName,stock,standardProduct');//获取总仓商品详情
        if (empty($goods_detail)) {
            $m->rollback();
            return false;
        }
        $stock = $goods_detail['stock'];
        if ($sku_id > 0) {
            $sku_detail = $this->getErpSkuDetailById($sku_id, 'skuId,stock');//获取总仓规格详情
            if (empty($sku_detail)) {
                $m->rollback();
                return false;
            }
            $stock = $sku_detail['stock'];
        }
        $stock = (float)$stock;
        if ($num > $stock) {
            $m->rollback();
            return false;
        }
        if ($sku_id > 0) {
            //扣除规格库存
            $sku_model = new JxcSkuGoodsSystemModel();
            $action_res = $sku_model->where(array(
                'skuId' => $sku_id
            ))->setDec('stock', $num);
        } else {
            //扣除无规格商品库存
            $goods_model = new JxcGoodsModel();
            $action_res = $goods_model->where(array(
                'goodsId' => $goods_id
            ))->setDec('stock', $num);
        }
        if (!$action_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 返还总仓商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id 商品规格id
     * @param float $num 数量
     * @param object $trans
     * @return bool
     * */
    public function returnErpStock(int $goods_id, int $sku_id, float $num, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = new $trans;
        }
        if ($sku_id > 0) {//返还规格商品库存
            $sku_model = new JxcSkuGoodsSystemModel();
            $stock_res = $sku_model->where(array('skuId' => $sku_id))->setInc('stock', $num);
        } else {//返还无规格商品库存
            $goods_model = new JxcGoodsModel();
            $stock_res = $goods_model->where(array('goodsId' => $goods_id))->setInc('stock', $num);
        }
        if (!$stock_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 获取ERP商品详情-根据商品id查找
     * @param int $goods_id 总仓商品id
     * @param string $field 表字段
     * @return array
     * */
    public function getErpGoodsDetailById(int $goods_id, $field = '*')
    {
        $goods_model = new JxcGoodsModel();
        $where = array(
            'goodsId' => $goods_id,
            'dataFlag' => 1,
        );
        $res = $goods_model
            ->where($where)
            ->field($field)
            ->find();
        return (array)$res;
    }

    /**
     * 获取ERP规格详情-根据规格id查找
     * @param int $sku_id 规格id
     * @param string $field 表字段
     * @return array
     * */
    public function getErpSkuDetailById(int $sku_id, $field = '*')
    {
        $sku_model = new JxcSkuGoodsSystemModel();
        $where = array(
            'skuId' => $sku_id,
            'dataFlag' => 1,
        );
        $sku_detail = $sku_model->where($where)->field($field)->find();
        if (empty($sku_detail)) {
            return array();
        }
        $self_model = new JxcSkuGoodsSelfModel();
        $table_prefix = $self_model->tablePrefix;
        $self_where = array(
            'self.skuId' => $sku_id,
            'self.dataFlag' => 1,
        );
        $self_list = $self_model
            ->alias('self')
            ->join("left join {$table_prefix}jxc_sku_spec spec on self.specId=spec.specId")
            ->join("left join {$table_prefix}jxc_sku_spec_attr attr on self.attrId=attr.attrId")
            ->field("spec.specId,spec.specName,attr.attrId,attr.attrName,self.skuId")
            ->where($self_where)
            ->select();
        $sku_detail['self_list'] = (array)$self_list;
        $sku_detail['spec_attr_str'] = implode(array_column($self_list, 'attrName'));
        return $sku_detail;
    }

    /**
     * 获取总仓配置
     * @return array
     * */
    public function getErpConfig()
    {
        $jxc_config_model = new JxcSysConfigModel();
        $config_list = $jxc_config_model->select();
        $config_list = array_reduce($config_list, function (&$arr_Array, $v) {
            $arr_Array[$v['fieldCode']] = $v['fieldValue'];
            return $arr_Array;
        });
        return (array)$config_list;
    }

    /**
     * 采购单明细-保存
     * @param array $params
     * ...wst_jxc_purchase_order_info表字段
     * @param object $trans
     * @return int
     * */
    public function savePurchaseOrderInfo(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'otpId' => null,
            'goodsId' => null,
            'skuId' => null,
            'remark' => null,
            'unitPrice' => null,
            'totalNum' => null,
            'totalAmount' => null,
            'warehouseNum' => null,
            'warehouseCompleteNum' => null,
            'toSupplierId' => null,
            'purchase_price' => null,
            'purchase_price_total' => null,
        );
        parm_filter($save_params, $params);
        $model = new jxcPurchaseOrderInfoModel();
        if (empty($params['infoId'])) {
            $infoId = $model->add($save_params);
            if (empty($infoId)) {
                $db_trans->rollback();
                return 0;
            }
        } else {
            $infoId = $params['infoId'];
            $where = array(
                'infoId' => $infoId
            );
            $save_res = $model->where($where)->save($save_params);
            if ($save_res === false) {
                $db_trans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return $infoId;
    }
}





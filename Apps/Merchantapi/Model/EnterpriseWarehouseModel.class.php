<?php
/**
 * 桌面端-入库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-08
 * Time: 10:16
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\SkuGoodsSystemModel;
use App\Modules\Erp\ErpModule;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Sorting\SortingModule;
use Think\Model;

class EnterpriseWarehouseModel extends BaseModel
{

    /**
     * 商品商城分类列表-列表
     * @return array
     * */
    public function getGoodsCatList()
    {
        $cat_module = new GoodsCatModule();
        $res = $cat_module->getGoodsCatList();
        return $res;
    }

    /**
     * 获取门店商品列表
     * @param array $params
     * int shop_id 门店id
     * string goods_code 商品码
     * int cat_id 分类id
     * int page 页码
     * int page_size 分页条数
     * @return array
     * */
    public function getShopGoodsList(array $params)
    {
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $shop_id = (int)$params['shop_id'];
        $where = " goods.shopId={$shop_id} and goods.goodsFlag=1 and goods.goodsStatus=1 ";
        if (!empty($params['cat_id'])) {
            $cat_module = new GoodsCatModule();
            $cat_detail = $cat_module->getGoodsCatDetailById($params['cat_id']);
            $cat_id = (int)$cat_detail['catId'];
            if ($cat_detail['level'] == 1) {
                $where .= " and goods.goodsCatId1={$cat_id}";
            } elseif ($cat_detail['level'] == 2) {
                $where .= " and goods.goodsCatId2={$cat_id}";
            } elseif ($cat_detail['level'] == 3) {
                $where .= " and goods.goodsCatId3={$cat_id}";
            }
        }
        if (!empty($params['goods_code'])) {
            $goods_code = $params['goods_code'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goods_code, $match)) {
                $goods_code = strtoupper($goods_code);
            }
            $where .= " and (goods.goodsName like '%{$params['goods_code']}%' or goods.py_code like '%{$goods_code}%' or goods.py_initials like '%{$goods_code}%' or goods.goodsSn like '%{$params['goods_code']}%' or system.skuBarcode like '%{$params['goods_code']}%' ) ";
        }
        $field = 'goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsImg,goods.goodsStock,goods.unit,goods.SuppPriceDiff,goods.weightG';
        $field .= ',system.skuId,system.dataFlag as skuFlag';
        $sql = M('goods')
            ->alias('goods')
            ->join("left join wst_sku_goods_system system on system.goodsId=goods.goodsId")
            ->where($where)
            ->field($field)
            ->order("goods.shopCatId2 desc,goods.shopGoodsSort desc")//和商户后台排序保持一致
            ->buildSql();
        $data = $this->pageQuery($sql, $page, $page_size);
        if (empty($data)) {
            return $data;
        }
        $goods_list = $data['root'];
        $goods_module = new GoodsModule();
        $result = array();
        foreach ($goods_list as $g_key => &$g_val) {
            if ($g_val['skuFlag'] == -1) {
                unset($goods_list[$g_key]);
                continue;
            }
            unset($g_val['skuFlag']);
            if (is_null($g_val['skuId'])) {//无规格
                $g_val['skuId'] = '0';
                $g_val['sku_spec_str'] = '';
                $result[] = $g_val;
            } else {//有规格
                $sku_detail = $goods_module->getSkuSystemInfoById($g_val['skuId'], 2);
                $g_val['skuId'] = $sku_detail['skuId'];
                $g_val['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $g_val['goodsImg'] = $sku_detail['skuGoodsImg'];
                $g_val['goodsSn'] = $sku_detail['skuBarcode'];
                $g_val['goodsStock'] = $sku_detail['skuGoodsStock'];
                $g_val['unit'] = $sku_detail['unit'];
                $g_val['weightG'] = $sku_detail['weigetG'];
                $result[] = $g_val;
            }
        }
        unset($g_val);
        $data['root'] = $result;
        return $data;
    }

    /**
     * 创建入库单
     * @param int $id 分拣员id
     * @param array $bill_params 单据参数
     * @return array
     * */
    public function createWarehousingBill(int $id, array $bill_params)
    {
        $sorting_module = new SortingModule();
        $person = $sorting_module->getSortingPersonnelById($id);
        if (empty($person)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '分拣员信息有误');
        }
        $shop_module = new ShopsModule();
        $shop_id = $person['shopid'];
        $shop_detail = $shop_module->getShopsInfoById($shop_id, 'shopId,shopName', 2);
        if (empty($shop_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '门店信息有误');
        }
        $trans = new Model();
        $trans->startTrans();
        $erp_module = new ErpModule();
        $bill_data = array();//入库单主表信息
        $bill_data['shopId'] = $shop_id;
        $bill_data['actionUserId'] = $id;
        $bill_data['actionUserName'] = $person['userName'];
        $bill_data['shopName'] = $shop_detail['shopName'];
        $bill_data['dataFrom'] = 1;
        $bill_data['billType'] = 2;
        $bill_data['status'] = 1;
        $bill_data['warehouseStatus'] = 1;
        $bill_data['warehouseUserId'] = $id;
        $bill_data['receivingStatus'] = 2;
        $bill_data['remark'] = $bill_params['bill_remaks'];
        $bill_id = $erp_module->savePurchaseOrder($bill_data, $trans);
        if (empty($bill_id)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '单据创建失败');
        }
        $number = 'CGD' . date('Ymd') . str_pad($bill_id, 10, "0", STR_PAD_LEFT);
        $log_params = array();//操作日志
        $log_params['dataId'] = $bill_id;
        $log_params['dataType'] = 1;
        $log_params['action'] = "采购方手动创建采购入库单#{$number}";
        $log_params['actionUserId'] = $id;
        $log_params['actionUserType'] = 2;
        $log_params['actionUserName'] = $person['userName'];
        $log_params['status'] = 1;
        $log_params['warehouseStatus'] = 0;
        $log_res = $erp_module->addBillActionLog($log_params, $trans);
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '单据日志记录失败');
        }
        $goods_data = $bill_params['goods_data'];
        $unique_tag_arr = array();
        foreach ($goods_data as $data_val) {
            $goods_id = (int)$data_val['goodsId'];
            $sku_id = (int)$data_val['skuId'];
            $unique_tag = $goods_id . '_' . $sku_id;
            $unique_tag_arr[] = $unique_tag;
        }
        if (count($goods_data) > count(array_unique($unique_tag_arr))) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请去除重复的商品', '商品数据重复');
        }
        $bill_total_num = 0;//单据采购总数量
        $bill_total_amount = 0;//单据总金额
        $goods_module = new GoodsModule();
        $goods_modle = new GoodsModel();
        $sku_system_model = new SkuGoodsSystemModel();
        foreach ($goods_data as $data_val) {
            $goods_id = (int)$data_val['goodsId'];
            $sku_id = (int)$data_val['skuId'];
            $unit_pirce = (float)$data_val['unit_pirce'];
            $num_or_weight = (float)$data_val['num_or_weight'];
            $remarks = (string)$data_val['remarks'];
            $goods_detail = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsName', 2);
            if (empty($goods_detail)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败，商品信息有误', '商品信息有误');
            }
            if ($sku_id > 0) {
                $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                if (empty($sku_detail)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败，sku信息有误', 'sku信息有误');
                }
            }
            $bill_info_params = array();//单据明细
            $bill_info_params['otpId'] = $bill_id;
            $bill_info_params['goodsId'] = $goods_id;
            $bill_info_params['skuId'] = $sku_id;
            $bill_info_params['remark'] = $remarks;
            $bill_info_params['unitPrice'] = $unit_pirce;
            $bill_info_params['totalNum'] = $num_or_weight;
            $bill_info_params['totalAmount'] = $num_or_weight * $unit_pirce;
            $bill_info_params['warehouseNum'] = $num_or_weight;
            $bill_info_params['warehouseCompleteNum'] = $num_or_weight;
            $save_bill_info_res = $erp_module->savePurchaseOrderInfo($bill_info_params, $trans);//保存单据明细
            if (empty($save_bill_info_res)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '单据明细保存失败');
            }

            $log_params = array();//操作日志
            $log_params['dataId'] = $bill_id;
            $log_params['dataType'] = 1;
            $log_params['action'] = "商品#{$goods_detail['goodsName']}入库数量{$num_or_weight}";
            $log_params['actionUserId'] = $id;
            $log_params['actionUserType'] = 2;
            $log_params['actionUserName'] = $person['userName'];
            $log_params['status'] = 1;
            $log_params['warehouseStatus'] = 2;
            $log_params['goodsId'] = $goods_id;
            $log_params['skuId'] = $sku_id;
            $log_params['warehouseCompleteNum'] = $num_or_weight;
            $log_res = $erp_module->addBillActionLog($log_params, $trans);
            if (!$log_res) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '商品日志记录失败');
            }
            if ($num_or_weight <= 0) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goods_detail['goodsName']}的入库数量必须大于0");
            }
            if (empty($sku_id)) {
                $goods_modle->where(array('goodsId' => $goods_id))->save(array('goodsUnit' => $unit_pirce));
            } else {
                $sku_system_model->where(array('skuId' => $sku_id))->save(array('purchase_price' => $unit_pirce));
            }
            $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $num_or_weight, 1, 1, $trans);
            if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '商品库存更新失败');
            }
            $bill_total_num += $num_or_weight;
            $bill_total_amount += $bill_info_params['totalAmount'];
        }

        //更新主单据
        $update_bill_data = array(
            'otpId' => $bill_id,
            'number' => $number,
            'totalNum' => $bill_total_num,
            'dataAmount' => $bill_total_amount,
            'actualAmount' => $bill_total_amount,
            'warehouseStatus' => 2,
        );
        $update_res = $erp_module->savePurchaseOrder($update_bill_data, $trans);
        if (empty($update_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '单据更新失败');
        }
        $log_params = array();
        $log_params['dataId'] = $bill_id;
        $log_params['dataType'] = 1;
        $log_params['action'] = "采购入库单#{$number}入库完成";
        $log_params['actionUserId'] = $id;
        $log_params['actionUserType'] = 2;
        $log_params['actionUserName'] = $person['userName'];
        $log_params['status'] = 1;
        $log_params['warehouseStatus'] = 2;
        $log_res = $erp_module->addBillActionLog($log_params, $trans);
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }
}
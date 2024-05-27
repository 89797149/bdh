<?php
/**
 * 出库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-06
 * Time: 17:48
 */

namespace App\Modules\ExWarehouse;


use App\Enum\ExceptionCodeEnum;
use App\Enum\ExWarehouse\ExWarehouseOrderEnum;
use App\Models\BaseModel;
use App\Models\ExWarehouseOrderGoodsModel;
use App\Models\ExWarehouseOrderModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Staffs\StaffsModule;
use Think\Model;

class ExWarehouseOrderModule extends BaseModel
{
    /**
     * 出库单-创建出库单
     * @param array $billData 单据信息
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int shopId 门店id
     * -int user_id 制单人id
     * -string user_name 制单人姓名
     * -string remark 单据备注
     * -string relation_order_number 关联单号
     * -string relation_order_id 关联单据id
     * -array goods_data 商品数据
     * --int goods_id 商品id
     * --int sku_id 商品skuId
     * --float nums 出库数量
     * --float actual_delivery_quantity 实际出库数量
     * 下面的参数后来改动增加的,非必填 PS:主要用于采购单退回
     * --float [unit_price] 单价
     * --string [goodsRemark] 商品备注
     * --string [remark] 商品描述
     * --float [goods_unit] 商品单位
     * --string [goods_name] 商品名称
     * --string [goods_specs_string] 规格拼接值
     * @param object $trans
     * @return array
     * */
    public function addExWarehouseOrder(array $billData, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $goodsData = $billData['goods_data'];
        if (empty($goodsData)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数错误-单据商品信息有误');
        }
        $goodsModule = new GoodsModule();
        $rowTag = 0;
        $trueGoodsData = array();//最终的商品数据
        $billTotalAmount = 0;//单据总金额
        $realTotalAmount = 0;//实际出库总金额
        foreach ($goodsData as $goodsVal) {
            $rowTag += 1;
            $goodsId = (int)$goodsVal['goods_id'];
            $skuId = (int)$goodsVal['sku_id'];
            $nums = (float)$goodsVal['nums'];
            $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, 'goodsId,goodsName,goodsSpec,goodsUnit,unit', 2);
            if (empty($goodsDetail)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$rowTag}行商品信息有误");
            }
            $unitPrice = $goodsDetail['goodsUnit'];//单价
            if (isset($goodsVal['unit_price'])) {
                $unitPrice = $goodsVal['unit_price'];
            }
            $goodsUniqueTag = $goodsId . '@' . $skuId;//商品唯一标识
            $goodsName = $goodsDetail['goodsName'];
            $unit = $goodsDetail['unit'];
            if (isset($goodsVal['goods_unit'])) {
                $unit = $goodsVal['goods_unit'];
            }
            $skuSpecStr = '';
            if (!empty($skuId)) {
                if (isset($goodsVal['goods_specs_string'])) {
                    $skuSpecStr = $goodsVal['goods_specs_string'];
                    $goodsName .= " sku：{$skuSpecStr}";
                } else {
                    $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
                    if (empty($skuDetail)) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}sku信息有误");
                    }
                    $unitPrice = $skuDetail['purchase_price'];
                    $unit = $skuDetail['unit'];
                    $skuSpecStr = $skuDetail['skuSpecAttrTwo'];
                    $goodsName .= " sku：{$skuSpecStr}";
                }

            }
            if ($nums <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}出库数量必须大于0");
            }
            if (isset($trueGoodsData[$goodsUniqueTag])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}存在重复数据");
            }
            $endGoodsDetail = array(
                'goods_id' => $goodsId,
                'sku_id' => $skuId,
                'nums' => $nums,
                'remark' => isset($goodsVal['remark']) ? $goodsVal['remark'] : (string)$goodsDetail['goodsSpec'],
                'unit_price' => (float)$unitPrice,
                'subtotal' => (float)bc_math($unitPrice, $nums, 'bcmul', 2),
                'actual_delivery_quantity' => $nums,
                'real_subtotal' => (float)bc_math($unitPrice, $nums, 'bcmul', 2),
                'goods_unit' => $unit,
                'goods_name' => isset($goodsVal['goods_name']) ? $goodsVal['goods_name'] : $goodsDetail['goodsName'],
                'goods_specs_string' => $skuSpecStr,
                'goodsRemark' => isset($goodsVal['goodsRemark']) ? $goodsVal['goodsRemark'] : "",
            );
            if (isset($goodsVal['actual_delivery_quantity'])) {
                $endGoodsDetail['actual_delivery_quantity'] = $goodsVal['actual_delivery_quantity'];
                $endGoodsDetail['real_subtotal'] = $goodsVal['actual_delivery_quantity'] * $endGoodsDetail['unit_price'];
            }
            $trueGoodsData[$goodsUniqueTag] = $endGoodsDetail;
            $billTotalAmount += $endGoodsDetail['subtotal'];
            $realTotalAmount += $endGoodsDetail['real_subtotal'];
        }
        $trueGoodsData = array_values($trueGoodsData);
        $config = $GLOBALS['CONFIG'];
        $mainOrderData = array(
            'merchant' => $billData['shopId'],
            'pagetype' => $billData['pagetype'],
            'user_id' => $billData['user_id'],
            'user_name' => $billData['user_name'],
            'remark' => $billData['remark'],
            'examine_status' => 1,
            'total_amount' => $billTotalAmount,
            'real_total_amount' => $realTotalAmount,
            'relation_order_number' => $billData['relation_order_number'],
            'relation_order_id' => $billData['relation_order_id'],
        );
        if ($config['ex_warehouse_order_examine'] != 1 || $mainOrderData['pagetype'] != 2) {
            $mainOrderData['examine_status'] = 2;
            $mainOrderData['ex_warehouse_datetime'] = date('Y-m-d H:i:s');
            $mainOrderData['auditingtime'] = date('Y-m-d H:i:s');
        }
        if ($mainOrderData['pagetype'] == 1) {
            $orderModule = new OrdersModule();
            $orderDetail = $orderModule->getOrderInfoById($mainOrderData['relation_order_id'], 'userId,userName', 2);
            $mainOrderData['customer_username'] = $orderDetail['payment_username'];
        }
        $billId = $this->saveExWarehouseOrder($mainOrderData, $dbTrans);
        if (empty($billId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单创建失败");
        }
        foreach ($trueGoodsData as $trueGoodsDetail) {
            $trueGoodsDetail['ex_order_id'] = $billId;
            $id = $this->saveExWarehouseOrderGoods($trueGoodsDetail, $dbTrans);
            if (empty($id)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单创建失败", "单据商品保存失败");
            }
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_ex_warehouse_order',
            'dataId' => $billId,
            'actionUserId' => $billData['user_id'],
            'actionUserName' => $billData['user_name'],
            'fieldName' => 'examine_status',
            'fieldValue' => 1,
            'remark' => '已创建出库单，等待审核',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单创建失败", "出库单创建日志记录失败");
        }
        if ($mainOrderData['pagetype'] != 2) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_ex_warehouse_order',
                'dataId' => $billId,
                'actionUserId' => $billData['user_id'],
                'actionUserName' => $billData['user_name'],
                'fieldName' => 'examine_status',
                'fieldValue' => 2,
                'remark' => '出库单已通过审核',
            ];
            $logRes = $tableActionModule->addTableActionLog($logParams, $dbTrans);
            if (!$logRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单创建失败", "出库单创建日志记录失败");
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 出库单-保存
     * @param array $params
     * -wst_ex_warehouse_order表字段
     * @param object $object
     * @return int
     * */
    public function saveExWarehouseOrder(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'merchant' => null,
            'number' => null,
            'pagetype' => null,
            'user_id' => null,
            'customer_username' => null,
            'user_name' => null,
            'remark' => null,
            'examine_status' => null,
            'auditinguser_id' => null,
            'auditingtime' => null,
            'relation_order_number' => null,
            'relation_order_id' => null,
            'ex_warehouse_datetime' => null,
            'total_amount' => null,
            'real_total_amount' => null,
            'update_time' => $datetime,
        );
        parm_filter($saveParams, $params);
        $model = new ExWarehouseOrderModel();
        if (empty($params['ex_order_id'])) {
            $saveParams['create_time'] = $datetime;
            $ex_order_id = $model->add($saveParams);
            if (empty($ex_order_id)) {
                $dbTrans->rollback();
                return 0;
            }
            $billNo = 'CK' . date('Ymd') . str_pad($ex_order_id, 10, "0", STR_PAD_LEFT);
            $saveParams = array(
                'ex_order_id' => $ex_order_id,
                'number' => $billNo,
            );
            $saveRes = $model->where(array('ex_order_id' => $ex_order_id))->save($saveParams);
            if ($saveRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $ex_order_id = $params['ex_order_id'];
            $saveRes = $model->where(array('ex_order_id' => $ex_order_id))->save($saveParams);
        }
        if (!$saveRes) {
            $dbTrans->rollback();
            return 0;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$ex_order_id;
    }

    /**
     * 出库单-保存出库单商品信息
     * @param array $params
     * -wst_ex_warehouse_order_goods 表字段
     * @param object $trans
     * @return int
     * */
    public function saveExWarehouseOrderGoods(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $saveParams = array(
            'goods_id' => null,
            'sku_id' => null,
            'ex_order_id' => null,
            'nums' => null,
            'remark' => null,
            'unit_price' => null,
            'subtotal' => null,
            'real_subtotal' => null,
            'actual_delivery_quantity' => null,
            'goods_unit' => null,
            'goods_name' => null,
            'goods_specs_string' => null,
        );
        parm_filter($saveParams, $params);
        $model = new ExWarehouseOrderGoodsModel();
        if (empty($params['id'])) {
            $id = $model->add($saveParams);
            if (!$id) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $saveRes = $model->where(array('id' => $id))->save($saveParams);
            if ($saveRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }

    /**
     * 出库单-单据列表
     * @param array $params
     * -int shopId 门店id
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int examine_status 审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
     * -date bill_date_start 制单日期区间-开始日期
     * -date bill_date_end 制单日期-结束日期
     * -string number_or_creater 单号/制单人
     * -string relation_order_number 关联单号
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getExWarehouseOrderList(array $params)
    {
        $where = 'shops.shopFlag=1 ';
        if (!empty($params['shopId'])) {
            $where .= " and warehouse.merchant={$params['shopId']}";
        }
        $whereFind = array();
        $whereFind['warehouse.pagetype'] = function () use ($params) {
            if (empty($params['pagetype'])) {
                return null;
            }
            return array("=", "{$params['pagetype']}", "and");
        };
        $whereFind['warehouse.examine_status'] = function () use ($params) {
            if (empty($params['examine_status'])) {
                return null;
            }
            return array("=", "{$params['examine_status']}", "and");
        };
        $whereFind['warehouse.relation_order_number'] = function () use ($params) {
            if (empty($params['relation_order_number'])) {
                return null;
            }
            return array("like", "%{$params['relation_order_number']}%", "and");
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        if (!empty($params['bill_date_start'])) {
            $params['bill_date_start'] .= ' 00:00:00';
            $whereInfo .= " and warehouse.create_time >= '{$params['bill_date_start']}'";
        }
        if (!empty($params['bill_date_end'])) {
            $params['bill_date_end'] .= ' 23:59:59';
            $whereInfo .= " and warehouse.create_time <= '{$params['bill_date_end']}'";
        }
        if (!empty($params['number_or_creater'])) {
            $whereInfo .= " and (warehouse.number like '%{$params['number_or_creater']}%' or warehouse.user_name like '%{$params['number_or_creater']}%')";
        }
        if (!empty($params['shop_keywords'])) {
            $whereInfo .= " and (shops.shopName like '%{$params['shop_keywords']}%' or shops.shopSn like '%{$params['shop_keywords']}%')";
        }
        $exWarehouseModel = new ExWarehouseOrderModel();
        $prefix = $exWarehouseModel->tablePrefix;
        $field = "warehouse.*,shops.shopName";
        $sql = $exWarehouseModel
            ->alias('warehouse')
            ->join("left join {$prefix}shops shops on shops.shopId=warehouse.merchant")
            ->where($whereInfo)
            ->field($field)
            ->order('warehouse.ex_order_id desc')
            ->buildSql();
        $sum_total_amount = $exWarehouseModel
            ->alias('warehouse')
            ->join("left join {$prefix}shops shops on shops.shopId=warehouse.merchant")
            ->where($whereInfo)
            ->sum('warehouse.total_amount');
        $sum_real_total_amount = $exWarehouseModel
            ->alias('warehouse')
            ->join("left join {$prefix}shops shops on shops.shopId=warehouse.merchant")
            ->where($whereInfo)
            ->sum('warehouse.real_total_amount');
        $result = $this->pageQuery($sql, $params['page'], $params['pageSize']);
        $root = $result['root'];
        $staffsModule = new StaffsModule();
        foreach ($root as &$item) {
            $item['pagetype_name'] = ExWarehouseOrderEnum::PAGE_TYPE[$item['pagetype']];
            $item['examine_status_name'] = ExWarehouseOrderEnum::EXAMINE_STATUS[$item['examine_status']];
            $item['auditingtime'] = (string)$item['auditingtime'];
            $item['ex_warehouse_datetime'] = (string)$item['ex_warehouse_datetime'];
            $item['auditing_username'] = '';//审核人
            if (!empty($item['auditingtime']) && !empty($item['auditinguser_id']) && $item['pagetype'] == 2) {
                $staffsDetail = $staffsModule->getStaffsDetailById($item['auditinguser_id'], 'staffName');
                $item['auditing_username'] = (string)$staffsDetail['staffName'];
            }
            if (!empty($item['auditingtime']) && empty($item['auditinguser_id']) && $item['pagetype'] != 2) {
                $item['auditing_username'] = '系统';
            }
        }
        unset($item);
        $result['root'] = $root;
        $result['sum_total_amount'] = (float)$sum_total_amount;//出库金额总计(单据金额)
        $result['sum_real_total_amount'] = (float)$sum_real_total_amount;//实际出库金额总计
        return $result;
    }

    /**
     * 出库单-单据详情
     * @param int $ex_order_id 单据id
     * @return array
     * */
    public function getExWarehouseOrderDetailById(int $ex_order_id)
    {
        $model = new ExWarehouseOrderModel();
        $relationModel = new ExWarehouseOrderGoodsModel();
        $where = array(
            'ex_order_id' => $ex_order_id
        );
        $result = $model->where($where)->find();
        if (empty($result)) {
            return array();
        }
        $result['merchant_name'] = '';//仓库名称
        if (!empty($result['merchant'])) {
            $shopModule = new ShopsModule();
            $shopDetail = $shopModule->getShopsInfoById($result['merchant'], 'shopName', 2);
            $result['merchant_name'] = $shopDetail['shopName'];
        }
        $result['pagetype_name'] = ExWarehouseOrderEnum::PAGE_TYPE[$result['pagetype']];
        $result['examine_status_name'] = ExWarehouseOrderEnum::EXAMINE_STATUS[$result['examine_status']];
        $result['auditingtime'] = (string)$result['auditingtime'];
        $result['ex_warehouse_datetime'] = (string)$result['ex_warehouse_datetime'];
        $result['auditing_username'] = '';//审核人
        $staffsModule = new StaffsModule();
        if (!empty($result['auditingtime']) && !empty($result['auditinguser_id']) && $result['pagetype'] == 2) {
            $staffsDetail = $staffsModule->getStaffsDetailById($result['auditinguser_id'], 'staffName');
            $result['auditing_username'] = (string)$staffsDetail['staffName'];
        }
        if (!empty($result['auditingtime']) && empty($result['auditinguser_id']) && $result['pagetype'] != 2) {
            $result['auditing_username'] = '系统';
        }
        $relationWhere = array(
            'ex_order_id' => $ex_order_id
        );
        $goodsData = $relationModel->where($relationWhere)->select();
        $result['goods_data'] = $goodsData;
        $logParams = array(
            'tableName' => 'wst_ex_warehouse_order',
            'dataId' => $ex_order_id
        );
        $logData = (new TableActionLogModule())->getLogListByParams($logParams);
        $newLogData = array();
        foreach ($logData as $logDetail) {
            $newLogData[] = array(
                'logId' => $logDetail['logId'],
                'actionUserName' => $logDetail['actionUserName'],
                'remark' => $logDetail['remark'],
                'createTime' => $logDetail['createTime'],
            );
        }
        $result['log_data'] = $newLogData;
        return $result;
    }

    /**
     * 出库单-商品列表
     * @param array $params
     * -int shopId 门店id
     * -string shop_keywords 门店关键字(名称/编号)
     * -int cat_id 门店分类id
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int examine_status 审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
     * -date warehouse_date_start 出库日期区间-开始日期
     * -date warehouse_date_end 出库日期区间-结束日期
     * -string goods_keywords 商品关键字(商品名称/编码)
     * -string relation_order_number 关联单号
     * -int page 页码
     * -int pageSize 分页条数
     * @param int $usePage 是否使用分页(0:不使用 1:使用)
     * @return array
     * */
    public function getExWarehouseGoods(array $params, $usePage = 1)
    {
        $where = " shops.shopFlag=1 ";
        if (!empty($params['shopId'])) {
            $where .= " warehouse.merchant={$params['shopId']} ";
        }
        $whereFind = array();
        $whereFind['warehouse.pagetype'] = function () use ($params) {
            if (empty($params['pagetype'])) {
                return null;
            }
            return array("=", "{$params['pagetype']}", "and");
        };
        $whereFind['warehouse.examine_status'] = function () use ($params) {
            if (empty($params['examine_status'])) {
                return null;
            }
            return array("=", "{$params['examine_status']}", "and");
        };
        $whereFind['warehouse.number'] = function () use ($params) {
            if (empty($params['relation_order_number'])) {
                return null;
            }
            return array("like", "%{$params['relation_order_number']}%", "and");
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        if (!empty($params['warehouse_date_start'])) {
            $whereInfo .= " and warehouse.ex_warehouse_datetime >= '{$params['warehouse_date_start']}'";
        }
        if (!empty($params['warehouse_date_end'])) {
            $whereInfo .= " and warehouse.ex_warehouse_datetime <= '{$params['warehouse_date_end']}'";
        }
        if (!empty($params['goods_keywords'])) {
            $goodsKeywords = $params['goods_keywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goodsKeywords, $match)) {
                $goodsKeywords = strtoupper($goodsKeywords);
            }
            $whereInfo .= " and (ex_goods.goods_name like '%{$params['goods_keywords']}%' or goods.py_code like '%{$goodsKeywords}%' or goods.py_initials like '%{$goodsKeywords}%' or goods.goodsSn like '%{$params['goods_keywords']}%' or system.skuBarcode like '%{$params['goods_keywords']}%' ) ";
        }
        if (!empty($params['shop_keywords'])) {
            $whereInfo .= " and (shops.shopName like '%{$params['shop_keywords']}%' or shops.shopSn like '%{$params['shop_keywords']}%')";
        }
        $goodsCatModule = new GoodsCatModule();
        if (!empty($params['cat_id']) && !empty($params['shopId'])) {
            $catDetail = $goodsCatModule->getShopCatDetailById($params['cat_id']);
            if ($catDetail['level'] == 1) {
                $whereInfo .= " and goods.shopCatId1={$catDetail['catId']} ";
            }
            if ($catDetail['level'] == 2) {
                $whereInfo .= " and goods.shopCatId2={$catDetail['catId']} ";
            }
        }
        if (!empty($params['cat_id']) && empty($params['shopId'])) {
            $goodsCatDetail = $goodsCatModule->getGoodsCatDetailById($params['cat_id']);
            if ($goodsCatDetail['level'] == 1) {
                $whereInfo .= " and goods.goodsCatId1={$goodsCatDetail['catId']} ";
            }
            if ($goodsCatDetail['level'] == 2) {
                $whereInfo .= " and goods.goodsCatId2={$goodsCatDetail['catId']} ";
            }
            if ($goodsCatDetail['level'] == 3) {
                $whereInfo .= " and goods.goodsCatId3={$goodsCatDetail['catId']} ";
            }
        }
        $exWarehouseGoodsModel = new ExWarehouseOrderGoodsModel();
        $prefix = $exWarehouseGoodsModel->tablePrefix;
        $field = 'ex_goods.id,ex_goods.goods_id,ex_goods.sku_id,ex_goods.ex_order_id,ex_goods.nums,ex_goods.remark,ex_goods.unit_price,ex_goods.subtotal,ex_goods.actual_delivery_quantity,ex_goods.real_subtotal,ex_goods.goods_unit,ex_goods.goods_name,ex_goods.goods_specs_string';
        $field .= ',warehouse.number,warehouse.ex_warehouse_datetime,warehouse.pagetype,warehouse.examine_status,warehouse.customer_username,warehouse.remark as bill_remark';
        $field .= ',goods.shopCatId1,goods.shopCatId2,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $field .= ',shops.shopName';
        $sql = $exWarehouseGoodsModel
            ->alias('ex_goods')
            ->join("left join {$prefix}goods goods on goods.goodsId=ex_goods.goods_id")
            ->join("left join {$prefix}shops shops on shops.shopId=goods.shopId")
            ->join("left join {$prefix}sku_goods_system system on ex_goods.sku_id=system.skuId")
            ->join("left join {$prefix}ex_warehouse_order warehouse on warehouse.ex_order_id=ex_goods.ex_order_id")
            ->field($field)
            ->where($whereInfo)
            ->group('ex_goods.id')
            ->order('ex_goods.id desc')
            ->buildSql();
        if ($usePage == 1) {
            $result = $this->pageQuery($sql, $params['page'], $params['pageSize']);
            $root = $result['root'];
        } else {
            $root = $this->query($sql);
        }
        $goodsModule = new GoodsModule();
        foreach ($root as &$item) {
            $item['goodsSn'] = $item['goodsSn'];
            if ($item['sku_id'] > 0) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($item['sku_id'], 2);
                $item['goodsSn'] = $skuDetail['skuBarcode'];
            }
            $item['pagetype_name'] = ExWarehouseOrderEnum::PAGE_TYPE[$item['pagetype']];
            $item['examine_status_name'] = ExWarehouseOrderEnum::EXAMINE_STATUS[$item['examine_status']];
            $item['ex_warehouse_datetime'] = (string)$item['ex_warehouse_datetime'];
            if (!empty($params['shopId'])) {
                $shopCat1Detail = $goodsCatModule->getShopCatDetailById((int)$item['shopCatId1'], 'catName');
                $item['shopCatId1Name'] = $shopCat1Detail['catName'];
                $shopCat2Detail = $goodsCatModule->getShopCatDetailById((int)$item['shopCatId2'], 'catName');
                $item['shopCatId2Name'] = $shopCat2Detail['catName'];
                $item['cat_name_merge'] = $item['shopCatId1Name'] . '/' . $item['shopCatId2Name'];
            } else {
                $goodsCatId1Detail = $goodsCatModule->getGoodsCatDetailById($item['goodsCatId1'], 'catName');
                $item['goodsCatId1Name'] = $goodsCatId1Detail['catName'];
                $goodsCatId2Detail = $goodsCatModule->getGoodsCatDetailById($item['goodsCatId2'], 'catName');
                $item['goodsCatId2Name'] = $goodsCatId2Detail['catName'];
                $goodsCatId3Detail = $goodsCatModule->getGoodsCatDetailById($item['goodsCatId3'], 'catName');
                $item['goodsCatId3Name'] = $goodsCatId3Detail['catName'];
                $item['cat_name_merge'] = $item['goodsCatId1Name'] . '/' . $item['goodsCatId2Name'] . '/' . $item['goodsCatId3Name'];
            }
        }
        unset($item);
        if ($usePage == 1) {
            $allResult = $exWarehouseGoodsModel
                ->alias('ex_goods')
                ->join("left join {$prefix}goods goods on goods.goodsId=ex_goods.goods_id")
                ->join("left join {$prefix}shops shops on shops.shopId=goods.shopId")
                ->join("left join {$prefix}sku_goods_system system on ex_goods.sku_id=system.skuId")
                ->join("left join {$prefix}ex_warehouse_order warehouse on ex_goods.ex_order_id=ex_goods.ex_order_id")
                ->where($whereInfo)
                ->field($field)
                ->group('ex_goods.id')
                ->select();
            $result['sum_total_amount'] = (float)array_sum(array_column($allResult, 'subtotal'));//出库金额总计(单据金额)
            $result['sum_real_total_amount'] = (float)array_sum(array_column($allResult, 'real_subtotal'));//实际出库金额总计
            $result['root'] = $root;
        } else {
            $result = $root;
        }
        return $result;
    }

    /**
     * 出库单-删除
     * @param string $ex_order_id_str 出库单id,多个用英文逗号分隔
     * @param object $trans
     * @return bool
     * */
    public function deleteExWarehouseOrder($ex_order_id_str, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new ExWarehouseOrderModel();
        $where = array(
            'ex_order_id' => array('in', $ex_order_id_str)
        );
        $result = $model->where($where)->delete();
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        $relationModel = new ExWarehouseOrderGoodsModel();
        $relationWhere = array(
            'ex_order_id' => array('in', $ex_order_id_str)
        );
        $deleteRelationRes = $relationModel->where($relationWhere)->delete();
        if (!$deleteRelationRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}
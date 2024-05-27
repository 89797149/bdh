<?php

namespace App\Modules\Orders;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\CartModel;
use App\Models\GoodsModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;
use Think\Model;

/**
 * 购物车类
 * Class OrdersModule
 * @package App\Modules\Cart
 */
class CartModule extends BaseModel
{
    /**
     * 删除购物车单条数据-根据购物车id
     * @param int $cartId
     * @param object $trans
     * @return bool
     * */
    public function clearCartById(int $cartId, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new CartModel();
        $res = $model->where(array(
            'cartId' => $cartId
        ))->delete();
        if (!$res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 删除购物车单条数据-多条件
     * @param array $params <p>
     * int cartId 购物车id
     * int userId 用户id
     * int goodsId 商品id
     * int goodsAttrId 属性id
     * int skuId sku规格id
     * </p>
     * @param object $trans
     * @return bool
     * */
    public function clearCartByParams(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($params['userId'])) {
            $m->rollback();
            return false;
        }
        $model = new CartModel();
        $where = array(
            'cartId' => null,
            'userId' => null,
            'goodsId' => null,
            'goodsAttrId' => null,
            'skuId' => null,
        );
        parm_filter($where, $params);
        $res = $model->where($where)->delete();
        if (!$res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 获取用户购物车中的商品
     * @param int $users_id 用户id
     * @param array $shop_id_arr 门仓id,多商品请传数组 例子:array(1,2)
     * @param int $is_checked 选中状态(-1:不验证该字段|0:未选中|1:已选中)
     * @param int $scene 场景【0：普通场景|1：确认提交订单】 注：临时修复，不清楚的默认0即可
     * @return array
     * */
    public function getCartGoodsChecked($users_id = 0, $shop_id_arr = array(), $is_checked = 1, $scene = 0)
    {
        $return_data = array(
            'goods_id_str' => '',//商品id
            'goods_list' => array()//商品列表
        );
        $all_shop_id = array();
        if (!is_array($shop_id_arr) && !empty($shop_id_arr)) {
            $shop_id_arr = explode(',', $shop_id_arr);
        }
        if (!empty($shop_id_arr)) {
            $all_shop_id = $shop_id_arr;
        }
        $all_shop_id = array_unique($all_shop_id);
        if (empty($users_id)) {
            return $return_data;
        }
        $users_cart_list = $this->getUserCartList($users_id, $is_checked);
        if (empty($users_cart_list)) {
            return $return_data;
        }
        $users_module = new UsersModule();
        $users_row = $users_module->getUsersDetailById($users_id)['data'];
        $configs = $GLOBALS['CONFIG'];
        $goods_module = new GoodsModule();
        $goods_list = array();
        $all_goods_list_map = [];
        $all_goods_id_arr = array_column($users_cart_list, 'goodsId');
        $all_goods_id_arr = array_unique($all_goods_id_arr);
        if (count($all_goods_id_arr) > 0) {
            $all_goods_list_data = $goods_module->getGoodsListById($all_goods_id_arr);
            $all_goods_list = $all_goods_list_data['data'];
            foreach ($all_goods_list as $all_goods_list_row) {
                $all_goods_list_map[$all_goods_list_row['goodsId']] = $all_goods_list_row;
            }
        }
        $systemSpecListMap = [];
        $skuIdArr = array_column($users_cart_list, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        if (count($skuIdArr) > 0) {
            $systemTab = M('sku_goods_system');
            $sysWhere = [];
            $sysWhere['skuId'] = array('in', $skuIdArr);
            $systemSpecList = $systemTab->where($sysWhere)->select();
            foreach ($systemSpecList as $systemSpecListRow) {
                if ($systemSpecListRow['skuId'] <= 0) {
                    continue;
                }
                $systemSpecListMap[$systemSpecListRow['skuId']] = $systemSpecListRow;
            }
        }
        $shop_module = new ShopsModule();
        $all_shop_list_map = [];
        $shop_list = $shop_module->getShopListByShopId($all_shop_id);
        foreach ($shop_list as $shop_list_row) {
            $all_shop_list_map[$shop_list_row['shopId']] = $shop_list_row;
        }
        foreach ($users_cart_list as $key => $item) {
            $cart_id = (int)$item['cartId'];
            $goods_id = (int)$item['goodsId'];
            $sku_id = (int)$item['skuId'];
            $goods_cnt = (float)$item['goodsCnt'];
//            $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
            $goods_detail = $all_goods_list_map[$goods_id];
            if (!empty($all_shop_id) && !in_array($goods_detail['shopId'], $all_shop_id)) {
                $goods_detail = array();
            }
            $type = 0;
//            $verification_cart_goods_res = $this->verificationCartGoodsStatus($users_id, $goods_id, $goods_cnt, $sku_id, $type);//验证购物车商品的有效状态
            $sku_row = [];
            if ($sku_id > 0) {
                $sku_row = $systemSpecListMap[$sku_id];
            }
            $shop_row = $all_shop_list_map[$item['shopId']];
            $verification_cart_goods_res = $this->verificationCartGoodsStatusNew($users_row, $goods_detail, $shop_row, $goods_cnt, $sku_row, $item, $type);//验证购物车商品的有效状态
            if ($verification_cart_goods_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $this->saveCart(array(
                    'cartId' => $cart_id,
                    'isCheck' => 0,
                ));
                $goods_detail = array();
            }
            $verification_stock_res = $this->verificationCartGoodsStockNew($goods_detail, $goods_cnt, $sku_row);//验证购物车商品库存
            if ($verification_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                if ($scene == 1) {//确认提交订单的时候直接给出错误提示
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_detail['goodsName'] . $verification_stock_res['msg']);
                }
                $this->saveCart(array(
                    'cartId' => $cart_id,
                    'isCheck' => 0,
                ));
                $goods_detail = array();
            }
            if (empty($goods_detail)) {
                continue;
            }
            if ($goods_detail['SuppPriceDiff'] == 1) {
                $goods_detail['is_weight'] = 1;
            } else {
                $goods_detail['is_weight'] = 0;
            }
            $goods_detail['cartId'] = $item['cartId'];
            $goods_detail['userId'] = $item['userId'];
            $goods_detail['isCheck'] = $item['isCheck'];
            $goods_detail['goodsAttrId'] = $item['goodsAttrId'];
            $goods_detail['goodsCnt'] = (float)$item['goodsCnt'];
            $goods_detail['skuId'] = $item['skuId'];
            $goods_detail['remarks'] = $item['remarks'];
            $goods_detail['unconventionality'] = 0;//非常规订单配送时长
            //非常规商品添加延迟配送时长
            if ($goods_detail['isConvention'] == 1) {
                $goods_detail['unconventionality'] = $configs['unconventionality'];
            }
            $goods_list[] = $goods_detail;
        }
        $return_data['goods_id_str'] = '';
        $return_data['goods_list'] = $goods_list;
        if (!empty($goods_list)) {
            $return_data['goods_id_str'] = implode(',', array_unique(array_column($goods_list, 'goodsId')));
        }
        return $return_data;
    }

    /**
     * 立即购买-返回下单商品信息
     * @param int $userId 用户id
     * @param int $buyNowGoodsId 立即购买-商品id
     * @param int $buyNowSkuId 立即购买-skuId
     * @param float $buyNowGoodsCnt 立即购买-数量
     * @return array
     * */
    public function getBuyNowGoodsList(int $userId, int $buyNowGoodsId, $buyNowSkuId = 0, $buyNowGoodsCnt = 0)
    {
        $return_data = array(
            'error' => '',//错误信息
            'goods_id_str' => '',//商品id
            'goods_list' => array()//商品列表
        );
        $goodsModule = new GoodsModule();
        $goodsRow = $goodsModule->getGoodsInfoById($buyNowGoodsId, "*", 2);
        if (empty($goodsRow)) {
            return $return_data;
        }
        $getGoodsSkuRes = $goodsModule->getGoodsSku($buyNowGoodsId, 2);
        if (!empty($getGoodsSkuRes) && empty($buyNowSkuId)) {
            $return_data['error'] = "请选择商品规格";
            return $return_data;
        }
        $returnGoodsRow = $goodsRow;
        $returnGoodsRow['cartId'] = 0;
        $returnGoodsRow['skuId'] = $buyNowSkuId;
        $returnGoodsRow['cartId'] = 0;
        $returnGoodsRow['goodsCnt'] = $buyNowGoodsCnt;
        $returnGoodsRow['isCheck'] = 1;
        if ($returnGoodsRow['isFlashSale'] == 1) {
            $goodsFlashSaleResult = $goodsModule->getGoodsFlashSale($buyNowGoodsId);
            if ($goodsFlashSaleResult['code'] == ExceptionCodeEnum::SUCCESS) {
                $goodsFlashSaleResultData = $goodsFlashSaleResult['data'];
                if ($goodsFlashSaleResultData['minBuyNum'] > 0) {
                    if ($buyNowGoodsCnt < $goodsFlashSaleResultData['minBuyNum']) {
                        $return_data['error'] = "未达到最小起订量" . $goodsFlashSaleResultData['minBuyNum'];
                        return $return_data;
                    }
                    //$returnGoodsRow['goodsCnt'] = $goodsFlashSaleResultData['minBuyNum'];
                }
                $goodsTimeLimitRes = goodsTimeLimit($buyNowGoodsId, $goodsFlashSaleResultData, $returnGoodsRow['goodsCnt']);
                if ($goodsFlashSaleResultData['activeInventory'] <= 0) {
                    $return_data['error'] = "库存不足";
                    return $return_data;
                }
                if (!empty($goodsTimeLimitRes)) {
                    $return_data['error'] = $goodsTimeLimitRes["msg"];
                    return $return_data;
                    //$returnGoodsRow['goodsCnt'] = 1;
                }
            }
        }
        if ($returnGoodsRow['isLimitBuy'] == 1 && $returnGoodsRow['limitCount'] > 0) {
            if ($returnGoodsRow['minBuyNum'] > 0 && $buyNowGoodsCnt < $returnGoodsRow['minBuyNum']) {
                $return_data['error'] = "未达到最小起订量" . $returnGoodsRow['minBuyNum'];
                return $return_data;
            }
//            if ($returnGoodsRow['minBuyNum'] > 0) {
//                $returnGoodsRow['goodsCnt'] = $returnGoodsRow['minBuyNum'];
//            }
            $result = $goodsModule->verificationLimitGoodsBuyLog($userId, $buyNowGoodsId, $returnGoodsRow['goodsCnt']);//验证是否符合限量购条件
            if ($result['code'] == ExceptionCodeEnum::FAIL) {
                $return_data['msg'] = $result['msg'];
                return $return_data;
//                $returnGoodsRow['goodsCnt'] = 1;
            }
        }
        $return_data['goods_list'][] = $returnGoodsRow;
        $return_data['goods_id_str'] = $returnGoodsRow['goodsId'];
        return $return_data;
    }

    /**
     * 获取用户购物车列表
     * @param int $users_id 用户id
     * @param int $is_checked 选中状态(-1:不验证该字段|0:未选中|1:已选中)
     * @return array
     * */
    public function getUserCartList(int $users_id, int $is_checked)
    {
//        $cart_mode = new CartModel();
        $where = array(
            'userId' => $users_id
        );
        if ($is_checked > -1) {
            $where['isCheck'] = $is_checked;
        }
//        $cart_list = $cart_mode->where($where)->select();
        $cart_list = D("CartView")->where($where)->select();
        if (empty($cart_list)) {
            return array();
        }
        $unique_cart_goods = array();
        foreach ($cart_list as $cart_key => $cart_val) {
            $cart_id = $cart_val['cartId'];
            $goods_id = (int)$cart_val['goodsId'];
            $sku_id = (int)$cart_val['skuId'];
            $unique_tag = $goods_id . '@' . $sku_id;
            if (isset($unique_cart_goods[$unique_tag])) {
                $this->clearCartById($cart_id);
                unset($cart_list[$cart_key]);
            }
            $unique_cart_goods[$unique_tag] = $cart_id;
        }
        return (array)array_values($cart_list);
    }


    /**
     * 验证商品库存是否满足下单库存 PS:使用场景:添加购物车|增加购物车商品数量|购物车列表
     * @param int $users_id 用户id
     * @param int $goods_id 商品id
     * @param float $goods_cnt 购买数量或重量
     * @param int $sku_id sku规格id
     * @param int $type 场景【0:用于列表数据 1：普通购买|2：再来一单|3:input编辑购物车商品数量】
     * @return array
     * */
    public function verificationCartGoodsStatus(int $users_id, int $goods_id, float $goods_cnt, int $sku_id, $type = 1)
    {
        $users_module = new UsersModule();
        $order_module = new OrdersModule();
        $field = 'userId,expireTime';
        $users_detail = $users_module->getUsersDetailById($users_id, $field, 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $goods_module = new GoodsModule();
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $shopId = $goods_detail['shopId'];
        $shopDetial = (new ShopsModule())->getShopsInfoById($shopId, 'shopAtive', 2);
        if (empty($shopDetial) || $shopDetial['shopAtive'] != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺{$shopDetial['shopDetail']}休息中");
        }
        $is_weight = (int)$goods_detail['SuppPriceDiff'];
        $ex_goods_cnt = explode('.', $goods_cnt);
        if ($is_weight != 1) {//标品
            if (count($ex_goods_cnt) > 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '标品购买数量只支持整数');
            }
        } else {//非标品
            if ($ex_goods_cnt > 1) {
                $point_num = $ex_goods_cnt[1];
                if (mb_strlen($point_num) > 3) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非标品购买重量最多支持3位小数位');
                }
            }
        }
        $cart_where = array(
            'userId' => $users_id,
            'goodsId' => $goods_id,
        );
        if (!empty($sku_id)) {
            $cart_where['skuId'] = $sku_id;
        }
        $cart_detail = $this->getCartDetailByParams($cart_where);
        if (in_array($type, array(0, 3))) {
            $goods_count = (float)$goods_cnt;
        } else {
            $goods_count = (float)bc_math($cart_detail['goodsCnt'], $goods_cnt, 'bcadd', 3); //购物车下单数量
        }
        $unit = $goods_module->getGoodsUnitByParams($goods_id, $sku_id);
        $cart_detail['goodsCount'] = $goods_count;
        if ($goods_detail['goodsStatus'] != 1) {//商品状态
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已禁售或未审核');
        }
        if ($goods_detail['goodsFlag'] != 1 || $goods_detail['isBecyclebin'] == 1) {//商品已被删除
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已被删除');
        }
        if ($goods_detail['isSale'] != 1) {//商品已被下架
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已被下架');
        }
        $goods_detail['buyNumLimit'] = (float)$goods_detail['buyNumLimit'];
        if (in_array($type, array(0, 1, 3))) {
            #################################关于商品库存的验证请不要写在该区间,下面有个方法verificationStock方法专门处理商品库存相关#################################
            $check_res = $order_module->verificationGoodsOrderNum($goods_id, $users_id);
            if ($check_res['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $check_res['msg']);
            }
            if ($goods_module->isNewPeopleGoods($users_id, $goods_id)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '该商品属于新人专享商品，您不是新人用户');
            }
            if ($goods_detail['isShopPreSale'] == 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '预售商品不能加入购物车');
            }
            if ($goods_detail['buyNum'] > 0 && $goods_count > $goods_detail['buyNum']) {//单笔购买商品数量限制
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '超出限购数量');
            }
            if ($goods_detail['buyNumLimit'] != -1 && $goods_count > $goods_detail['buyNumLimit']) {
                $msg = '单笔订单最多购买' . $goods_detail['buyNumLimit'] . $unit;
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $msg);
            }
            if ($goods_detail['isMembershipExclusive'] == 1) {
                if ($users_detail['expireTime'] <= date('Y-m-d H:i:s')) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前商品是会员专享商品，您不是会员');
                }
            }
            //限购数量
            if ($goods_detail['buyNum'] > 0 && ($cart_detail['goodsCnt'] + $goods_cnt) > $goods_detail['buyNum']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '超出限购数量');
            }
        }
        $data = array();
        $data['cartInfo'] = $cart_detail;//购物车信息
        $data['goodsInfo'] = $goods_detail;//商品信息
        $data['userInfo'] = $users_detail;//用户信息
        return returnData($data);
    }

    /**
     * 验证商品库存是否满足下单库存 PS:使用场景:添加购物车|增加购物车商品数量|购物车列表 临时修复解决方案，无需较真
     * @param array $users_detail 用户详情
     * @param array $goods_row 商品详情
     * @param array $shop_row 店铺详情
     * @param float $goods_cnt 购买数量或重量
     * @param array $sku_row sku规格详情
     * @param array $cart_detail 购物车详情 仅cart表数据或包含goodsCnt字段即可
     * @param int $type 场景【0:用于列表数据 1：普通购买|2：再来一单|3:input编辑购物车商品数量】
     * @return array
     * */
    public function verificationCartGoodsStatusNew(array $users_detail, array $goods_row, array $shop_row, float $goods_cnt, array $sku_row, array $cart_detail, $type = 1)
    {
//        $users_module = new UsersModule();
        $order_module = new OrdersModule();
//        $field = 'userId,expireTime';
//        $users_detail = $users_module->getUsersDetailById($users_id, $field, 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $users_id = $users_detail['userId'];
        $goods_module = new GoodsModule();
//        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        $goods_detail = $goods_row;
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goods_id = $goods_detail['goodsId'];
//        $shopId = $goods_detail['shopId'];
//        $shopDetial = (new ShopsModule())->getShopsInfoById($shopId, 'shopAtive', 2);
        $shopDetial = $shop_row;
        if (empty($shopDetial) || $shopDetial['shopAtive'] != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺{$shopDetial['shopDetail']}休息中");
        }
        $sku_id = 0;
        if (!empty($sku_row)) {
            $sku_id = $sku_row['skuId'];
        }
        $is_weight = (int)$goods_detail['SuppPriceDiff'];
        $ex_goods_cnt = explode('.', $goods_cnt);
        if ($is_weight != 1) {//标品
            if (count($ex_goods_cnt) > 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '标品购买数量只支持整数');
            }
        } else {//非标品
            if ($ex_goods_cnt > 1) {
                $point_num = $ex_goods_cnt[1];
                if (mb_strlen($point_num) > 3) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非标品购买重量最多支持3位小数位');
                }
            }
        }
//        $cart_where = array(
//            'userId' => $users_id,
//            'goodsId' => $goods_id,
//        );
//        if (!empty($sku_id)) {
//            $cart_where['skuId'] = $sku_id;
//        }
//        $cart_detail = $this->getCartDetailByParams($cart_where);
        if (in_array($type, array(0, 3))) {
            $goods_count = (float)$goods_cnt;
        } else {
            $goods_count = (float)bc_math($cart_detail['goodsCnt'], $goods_cnt, 'bcadd', 3); //购物车下单数量
        }
//        $unit = $goods_module->getGoodsUnitByParams($goods_id, $sku_id);
        $unit = $goods_detail['unit'];
        if (!empty($sku_row)) {
            $unit = $sku_row['unit'];
        }
        $cart_detail['goodsCount'] = $goods_count;
        if ($goods_detail['goodsStatus'] != 1) {//商品状态
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已禁售或未审核');
        }
        if ($goods_detail['goodsFlag'] != 1 || $goods_detail['isBecyclebin'] == 1) {//商品已被删除
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已被删除');
        }
        if ($goods_detail['isSale'] != 1) {//商品已被下架
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已被下架');
        }
        $goods_detail['buyNumLimit'] = (float)$goods_detail['buyNumLimit'];
        if (in_array($type, array(0, 1, 3))) {
            #################################关于商品库存的验证请不要写在该区间,下面有个方法verificationStock方法专门处理商品库存相关#################################
            $check_res = $order_module->verificationGoodsOrderNum($goods_id, $users_id);
            if ($check_res['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $check_res['msg']);
            }
            if ($goods_module->isNewPeopleGoods($users_id, $goods_id)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '该商品属于新人专享商品，您不是新人用户');
            }
            if ($goods_detail['isShopPreSale'] == 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '预售商品不能加入购物车');
            }
            if ($goods_detail['buyNum'] > 0 && $goods_count > $goods_detail['buyNum']) {//单笔购买商品数量限制
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '超出限购数量');
            }
            if ($goods_detail['buyNumLimit'] != -1 && $goods_count > $goods_detail['buyNumLimit']) {
                $msg = '单笔订单最多购买' . $goods_detail['buyNumLimit'] . $unit;
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $msg);
            }
            if ($goods_detail['isMembershipExclusive'] == 1) {
                if ($users_detail['expireTime'] <= date('Y-m-d H:i:s')) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前商品是会员专享商品，您不是会员');
                }
            }
            //限购数量
            if ($goods_detail['buyNum'] > 0 && ($cart_detail['goodsCnt'] + $goods_cnt) > $goods_detail['buyNum']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '超出限购数量');
            }
        }
        $data = array();
        $data['cartInfo'] = $cart_detail;//购物车信息
        $data['goodsInfo'] = $goods_detail;//商品信息
        $data['userInfo'] = $users_detail;//用户信息
        return returnData($data);
    }

    /**
     * 购物车详情-根据购物车id获取
     * @param int $cart_id 购物车id
     * @param int $users_id 用户id
     * @return array
     * */
    public function getCartDetailById(int $cart_id, $users_id = 0)
    {
        $model = new CartModel();
        $where = array(
            'cartId' => $cart_id
        );
        if ($users_id > 0) {
            $where['userId'] = $users_id;
        }
        $detail = $model->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        return (array)$detail;
    }

    /**
     * 购物车详情-多参数获取
     * @param array $params <p>
     * int cartId 购物车id
     * int userId 用户id
     * int goodsId 商品id
     * int goodsAttrId 商品属性ID
     * int skuId sku规格id
     * </p>
     * @return array
     * */
    public function getCartDetailByParams(array $params)
    {
        $model = new CartModel();
        $where = array(
            'cartId' => null,
            'userId' => null,
            'goodsId' => null,
            'goodsAttrId' => null,
            'skuId' => null,
        );
        parm_filter($where, $params);
        $detail = $model->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        return (array)$detail;
    }

    /**
     * 保存购物车信息
     * @param array $params <p>
     * int cartId 购物车id
     * int userId 用户ID
     * int isCheck 是否选中
     * int goodsId 商品ID
     * int goodsAttrId 商品属性ID
     * float goodsCnt 商品数量
     * int skuId sku规格id
     * string remarks 智能备注
     * </p>
     * @param object $trans
     * @return int $cart_id
     * */
    public function saveCart(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'userId' => null,
            'isCheck' => null,
            'goodsId' => null,
            'goodsAttrId' => null,
            'goodsCnt' => null,
            'skuId' => null,
            'remarks' => null,
        );
        parm_filter($save, $params);
        $model = new CartModel();
        if (empty($params['cartId'])) {
            $save_res = $model->add($save);
            $cart_id = $save_res;
        } else {
            $save_res = $model->where(array(
                'cartId' => $params['cartId']
            ))->save($save);
            $cart_id = $params['cartId'];
        }
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $cart_id;
    }

    /**
     * 验证购物车商品库存是否满足下单库存 PS:使用场景:添加购物车|增加购物车商品数量|购物车列表
     * @param int $goods_id 商品id
     * @param float $goods_cnt 购买数量/重量 PS:该数量为购物车中最终的购买数量
     * @param int $sku_id sku规格id
     * @return array
     * */
    public function verificationCartGoodsStock(int $goods_id, float $goods_cnt, $sku_id = 0)
    {
        //废除包装系数
        $goods_module = new GoodsModule();
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goods_stock = $goods_module->getGoodsStock($goods_id, $sku_id, $goods_cnt, $goods_detail);
        //sku的属性在getGoodsStock方法中已经处理过了,可以直接使用
        $goods_detail['minBuyNum'] = (float)$goods_detail['minBuyNum'];
        if ($goods_detail['minBuyNum'] > 0 && $goods_cnt < $goods_detail['minBuyNum']) {
            return returnData(['canbuyNum' => $goods_detail['minBuyNum']], ExceptionCodeEnum::FAIL, 'error', '最小起购量为' . $goods_detail['minBuyNum']);
        }
        $goods_cnt_stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
        $unit = $goods_module->getGoodsUnitByParams($goods_id, $sku_id);
        if ($goods_detail['SuppPriceDiff'] == 1) {
            //称重商品
            if (bccomp($goods_cnt_stock, $goods_stock, 3) == 1) {
//                $can_buy_num = bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
//                $msg = "库存数量不足";
//                if ($can_buy_num > 0) {
//                    $msg = "库存数量不足，最多可购买数量{$can_buy_num}";
//                }
//                return returnData(['canbuyNum' => $can_buy_num], -1, 'error', $msg);
                $msg = "库存不足";
                if ($goods_stock > 0) {
                    $msg = "库存不足，最多可购买{$goods_stock}{$unit}";
                }
                return returnData(['canbuyNum' => $goods_stock], ExceptionCodeEnum::FAIL, 'error', $msg);
            }
        } else {
            //标品
            if ($goods_cnt_stock > $goods_stock) {
//                $can_buy_num = bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
//                $can_buy_num = (int)$can_buy_num;
//                return returnData(['canbuyNum' => $can_buy_num], -1, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                return returnData(['canbuyNum' => $goods_stock], ExceptionCodeEnum::FAIL, 'error', '库存不足，最多可购买' . $goods_stock . $unit);
            }
        }
        $data = array();
        $data['goodsStock'] = $goods_stock;//商品总库存
        $data['goodsCnt'] = $goods_cnt;//购买商品数量
        $data['goodsCntStock'] = $goods_cnt_stock;//购买的商品数量的库存总计
        return returnData($data);
    }

    /**
     * 验证购物车商品库存是否满足下单库存 PS:使用场景:添加购物车|增加购物车商品数量|购物车列表
     * @param int $goods_detail 商品详情
     * @param float $goods_cnt 购买数量/重量 PS:该数量为购物车中最终的购买数量
     * @param int $sku_row sku规格详情
     * @return array
     * */
    public function verificationCartGoodsStockNew(array $goods_detail, float $goods_cnt, array $sku_row)
    {
        //废除包装系数
        $goods_module = new GoodsModule();
//        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goods_id = $goods_detail['goodsId'];
        $sku_id = 0;
        if (!empty($sku_row)) {
            $sku_id = $sku_row['sku_id'];
        }
        $goods_stock = $goods_module->getGoodsStock($goods_id, $sku_id, $goods_cnt, $goods_detail);
        //sku的属性在getGoodsStock方法中已经处理过了,可以直接使用
        $goods_detail['minBuyNum'] = (float)$goods_detail['minBuyNum'];
        if ($goods_detail['minBuyNum'] > 0 && $goods_cnt < $goods_detail['minBuyNum']) {
            return returnData(['canbuyNum' => $goods_detail['minBuyNum']], ExceptionCodeEnum::FAIL, 'error', '最小起购量为' . $goods_detail['minBuyNum']);
        }
        $goods_cnt_stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
//        $unit = $goods_module->getGoodsUnitByParams($goods_id, $sku_id);
        $unit = $goods_detail['unit'];
        if (!empty($sku_row)) {
            $unit = $sku_row['unit'];
        }
        if ($goods_detail['SuppPriceDiff'] == 1) {
            //称重商品
            if (bccomp($goods_cnt_stock, $goods_stock, 3) == 1) {
//                $can_buy_num = bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
//                $msg = "库存数量不足";
//                if ($can_buy_num > 0) {
//                    $msg = "库存数量不足，最多可购买数量{$can_buy_num}";
//                }
//                return returnData(['canbuyNum' => $can_buy_num], -1, 'error', $msg);
                $msg = "库存不足";
                if ($goods_stock > 0) {
                    $msg = "库存不足，最多可购买{$goods_stock}{$unit}";
                }
                return returnData(['canbuyNum' => $goods_stock], ExceptionCodeEnum::FAIL, 'error', $msg);
            }
        } else {
            //标品
            if ($goods_cnt_stock > $goods_stock) {
//                $can_buy_num = bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
//                $can_buy_num = (int)$can_buy_num;
//                return returnData(['canbuyNum' => $can_buy_num], -1, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                return returnData(['canbuyNum' => $goods_stock], ExceptionCodeEnum::FAIL, 'error', '库存不足，最多可购买' . $goods_stock . $unit);
            }
        }
        $data = array();
        $data['goodsStock'] = $goods_stock;//商品总库存
        $data['goodsCnt'] = $goods_cnt;//购买商品数量
        $data['goodsCntStock'] = $goods_cnt_stock;//购买的商品数量的库存总计
        return returnData($data);
    }

    /**
     * 处理购物车商品数据,是否有sku需要替换的数据
     * @param array $goods_list 购物车商品数据
     * @return array
     * */
    public function handleCartGoodsSku($goods_list = array())
    {
        //原有方法copy过来的,就不做大的修改了
        if (empty($goods_list)) {
            return array();
        }
        $goods_module = new GoodsModule();
        $replace_sku_field = C('replaceSkuField');//需要被sku属性替换的字段
        $systemSpecListMap = [];
        $skuIdArr = array_column($goods_list, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        if (count($skuIdArr) > 0) {
            $systemSpecList = $goods_module->getSkuSystemListById($skuIdArr)['data'];
            foreach ($systemSpecList as $systemSpecListRow) {
                if ($systemSpecListRow['skuId'] <= 0) {
                    continue;
                }
                $systemSpecListMap[$systemSpecListRow['skuId']] = $systemSpecListRow;
            }
        }
        $shop_module = new ShopsModule();
        $shop_list_map = [];
        $shop_id_arr = array_column($goods_list, 'shopId');
        $shop_id_arr = array_unique($shop_id_arr);
        $shop_list = $shop_module->getShopListByShopId($shop_id_arr);
        foreach ($shop_list as $shop_list_row) {
            $shop_list_map[$shop_list_row['shopId']] = $shop_list_row;
        }
        for ($i = 0; $i < count($goods_list); $i++) {
            $goods_detail = $goods_list[$i];
            //$goods_id = $goods_detail['goodsId'];
            $sku_id = $goods_detail['skuId'];
            $goods_id_arr[$i] = $goods_list[$i];
            $goods_detail['skuSpecStr'] = '';
            $goods_id2[$i] = $goods_detail;
            if ($sku_id > 0) {
//                $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                $sku_detail = $systemSpecListMap[$sku_id];
                $goods_id2[$i]['skuSpecStr'] = $sku_detail['skuSpecAttr'];
                foreach ($replace_sku_field as $rk => $rv) {
                    if ((int)$sku_detail[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        continue;
                    }
                    if (in_array($rk, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if (isset($goods_id2[$i][$rv])) {
                        $goods_id2[$i][$rv] = $sku_detail[$rk];
                    }
                }
            }
        }
        //给每个商品添加自己的店铺
        for ($i = 0; $i < count($goods_id2); $i++) {
//            $goods_id2[$i]["shopcm"] = $shop_module->getShopsInfoById($goods_id2[$i]['shopId'], '*', 2);
            $goods_id2[$i]["shopcm"] = $shop_list_map[$goods_id2[$i]['shopId']];
        }
        $result = array();
        $goods_id2 = getCartGoodsSku($goods_id2);
        $goods_id2 = $goods_module->filterGoods($goods_id2);
        foreach ($goods_id2 as $k => $v) {
//            $v = getCartGoodsSku($v);
//            $goods_module->filterGoods($v);
            $result[$v["shopId"]][] = $v;//根据Id归类
        }
        $result = array_values($result);//重建索引
        return $result;
    }

    /**
     * 检验购物车商品是否符合店铺设置的配送起步价
     * @param int $users_id 用户id
     * @param int $shop_id 门店id
     * @return array
     * */
    public function verificationDeliveryStartMoney(int $users_id, int $shop_id)
    {
        $cart_module = new CartModule();
        $buyNowGoodsId = (int)I('buyNowGoodsId');//立即购买-商品id 注：仅用于立即购买
        $buyNowSkudId = (int)I('buyNowSkuId');//立即购买-skuId 注：仅用于立即购买
        $buyNowGoodsCnt = (float)I('buyNowGoodsCnt');
        if (!empty($buyNowGoodsId)) {//立即购买
            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkudId, $buyNowGoodsCnt);
            if (!empty($cart_goods['goods_list'])) {
                if ($cart_goods['goods_list'][0]['shopId'] != $shop_id) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提交的商品与当前店铺不匹配！');
                }
            }
        } else {
            $cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id));
        }
        //$cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id));
        $goods_list = $cart_goods['goods_list'];
        $result = $cart_module->handleCartGoodsSku($goods_list);
        if (empty($result) || empty($users_id)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '检验店铺配送起步价参数有误');
        }
        $code = ExceptionCodeEnum::SUCCESS;
        $users_module = new UsersModule();
        $field = 'userId,userName';
        $users_detail = $users_module->getUsersDetailById($users_id, $field, 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $shop_name = array();
        $shop_module = new ShopsModule();
        $goods_module = new GoodsModule();
        $shop_list_map = [];
        $shop_id_arr = array_column($goods_list, 'shopId');
        $shop_id_arr = array_unique($shop_id_arr);
        $shop_list = $shop_module->getShopListByShopId($shop_id_arr);
        foreach ($shop_list as $shop_list_row) {
            $shop_list_map[$shop_list_row['shopId']] = $shop_list_row;
        }
        for ($i = 0; $i < count($result); $i++) {
            $current_shop_id = $result[$i][0]['shopId'];
            if ($current_shop_id == $shop_id) {
//                $field = 'shopId,shopName,deliveryStartMoney';
//                $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
                $shop_detail = $shop_list_map[$shop_id];
                $total_money = array();//当前单商品价格总计
                for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
                    //获取当前订单所有商品总价
                    $goods_detail = $result[$i][$i1];
                    $goods_id = $goods_detail['goodsId'];
                    $sku_id = $goods_detail['skuId'];
//                    $goods_attr_id = $goods_detail['goodsAttrId'];
                    $goods_cnt = $goods_detail['goodsCnt'];
                    $goods_price = $goods_module->replaceGoodsPrice($users_detail['userId'], $goods_id, $sku_id, $goods_cnt, $goods_detail);//获取最终商品单价
                    $goods_price_total = (float)bc_math($goods_price, $goods_cnt, 'bcmul', 2);//单商品价格小计
                    $total_money[] = $goods_price_total;
                }
                $total_money = (float)array_sum($total_money);
                if ($total_money < $shop_detail['deliveryStartMoney']) {
                    $code = ExceptionCodeEnum::FAIL;
                    $shop_name[] = $shop_detail['shopName'];
                }
            }
        }
        if ($code != ExceptionCodeEnum::SUCCESS) {
            $arr = array(
                'err_shop_name' => $shop_name
            );
            return returnData($arr, $code, 'error', '未达到店铺配送起步价');
        }
        return returnData(true);
    }

    /**
     * 检验购物车商品是否符合店铺设置的配送起步价 复制上面的方法过来针对特定场景
     * @param array $user_row 用户信息
     * @param int $shop_id 门店id
     * @param array $result 购物车商品数据
     * @return array
     * */
    public function verificationDeliveryStartMoneyNew(array $user_row, int $shop_id, $result)
    {
//        $cart_module = new CartModule();
//        $buyNowGoodsId = (int)I('buyNowGoodsId');//立即购买-商品id 注：仅用于立即购买
//        $buyNowSkudId = (int)I('buyNowSkuId');//立即购买-skuId 注：仅用于立即购买
//        $buyNowGoodsCnt = (float)I('buyNowGoodsCnt');
//        if (!empty($buyNowGoodsId)) {//立即购买
//            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkudId, $buyNowGoodsCnt);
//            if (!empty($cart_goods['goods_list'])) {
//                if ($cart_goods['goods_list'][0]['shopId'] != $shop_id) {
//                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提交的商品与当前店铺不匹配！');
//                }
//            }
//        } else {
//            $cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id));
//        }
        //$cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id));
//        $result = $cart_module->handleCartGoodsSku($goods_list);
        if (empty($result) || empty($user_row)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '检验店铺配送起步价参数有误');
        }
        $code = ExceptionCodeEnum::SUCCESS;
//        $users_module = new UsersModule();
//        $field = 'userId,userName';
//        $users_detail = $users_module->getUsersDetailById($users_id, $field, 2);
        $users_detail = $user_row;
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $shop_name = array();
        $shop_module = new ShopsModule();
        $goods_module = new GoodsModule();
        $shop_list_map = [];
//        $shop_id_arr = array_column($goods_list, 'shopId');
//        $shop_id_arr = array_unique($shop_id_arr);
        $shop_id_arr = array($shop_id);
        $shop_list = $shop_module->getShopListByShopId($shop_id_arr);
        foreach ($shop_list as $shop_list_row) {
            $shop_list_map[$shop_list_row['shopId']] = $shop_list_row;
        }
        for ($i = 0; $i < count($result); $i++) {
            $current_shop_id = $result[$i][0]['shopId'];
            if ($current_shop_id == $shop_id) {
//                $field = 'shopId,shopName,deliveryStartMoney';
//                $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
                $shop_detail = $shop_list_map[$shop_id];
                $total_money = array();//当前单商品价格总计
                for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
                    //获取当前订单所有商品总价
                    $goods_detail = $result[$i][$i1];
                    $goods_id = $goods_detail['goodsId'];
                    $sku_id = $goods_detail['skuId'];
//                    $goods_attr_id = $goods_detail['goodsAttrId'];
                    $goods_cnt = $goods_detail['goodsCnt'];
                    $goods_price = $goods_module->replaceGoodsPrice($users_detail['userId'], $goods_id, $sku_id, $goods_cnt, $goods_detail);//获取最终商品单价
                    $goods_price_total = (float)bc_math($goods_price, $goods_cnt, 'bcmul', 2);//单商品价格小计
                    $total_money[] = $goods_price_total;
                }
                $total_money = (float)array_sum($total_money);
                if ($total_money < $shop_detail['deliveryStartMoney']) {
                    $code = ExceptionCodeEnum::FAIL;
                    $shop_name[] = $shop_detail['shopName'];
                }
            }
        }
        if ($code != ExceptionCodeEnum::SUCCESS) {
            $arr = array(
                'err_shop_name' => $shop_name
            );
            return returnData($arr, $code, 'error', '未达到店铺配送起步价');
        }
        return returnData(true);
    }

    /**
     * 会员节省了多少钱(购物车商品)
     * @param int $users_id 用户id
     * @param array $shop_id_arr 门店id
     * @return float $amount
     * */
    public function getMemberEconomyAmount(int $users_id, $shop_id_arr = array())
    {
        $users_detail = new UsersModule();
        $field = 'userId,expireTime';
        $users_detail = $users_detail->getUsersDetailById($users_id, $field, 2);
        $amount = 0;
        if (empty($users_detail) || $users_detail['expireTime'] < date('Y-m-d H:i:s')) {
            return formatAmount($amount);
        }
        $cart_goods = $this->getCartGoodsChecked($users_id, $shop_id_arr);
        $goods_list = $cart_goods['goods_list'];
        $result = $this->handleCartGoodsSku($goods_list);
        $shop_price_total = 0;
        $member_price_total = 0;
//        $goods_module = new GoodsModule();
        $user_discunt = (float)bc_math($GLOBALS["CONFIG"]["userDiscount"], 100, 'bcdiv', 2);
        $systemSpecListMap = [];
        $skuIdArr = array_column($goods_list, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        if (count($skuIdArr) > 0) {
            $systemTab = M('sku_goods_system');
            $sysWhere = [];
            $sysWhere['skuId'] = array('in', $skuIdArr);
            $systemSpecList = $systemTab->where($sysWhere)->select();
            foreach ($systemSpecList as $systemSpecListRow) {
                if ($systemSpecListRow['skuId'] <= 0) {
                    continue;
                }
                $systemSpecListMap[$systemSpecListRow['skuId']] = $systemSpecListRow;
            }
        }
        foreach ($result as $key => $val) {
            foreach ($val as $son) {
                $goods_detail = $son;
                $shop_price = (float)$goods_detail['shopPrice'];
                $sku_id = (int)$goods_detail['skuId'];
                $goods_cnt = (float)$goods_detail['goodsCnt'];
                $current_member_price = 0;
                if ($goods_detail['memberPrice'] > 0) {
                    $current_member_price = $goods_detail['memberPrice'];
                } else {
                    if ($user_discunt > 0) {
                        $current_member_price = (float)bc_math($shop_price, $user_discunt, 'bcmul', 2);
                    }
                }
                if ($sku_id > 0) {
//                    $sku_system_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                    $sku_system_detail = $systemSpecListMap[$sku_id];
                    $sku_shop_price = $sku_system_detail['skuShopPrice'];
                    if ($sku_system_detail['skuMemberPrice'] > 0) {
                        $current_member_price = $sku_system_detail['skuMemberPrice'];
                    } else {
                        if ($user_discunt > 0) {
                            $current_member_price = (float)bc_math($sku_shop_price, $user_discunt, 'bcmul', 2);
                        }
                    }
                }
                $current_shop_price_total = (float)bc_math($shop_price, $goods_cnt, 'bcmul', 2);
                $current_member_price_total = (float)bc_math($current_member_price, $goods_cnt, 'bcmul', 2);
                $shop_price_total += $current_shop_price_total;
                $member_price_total += $current_member_price_total;
            }
        }
        if ($member_price_total > 0) {
            $amount = abs(bc_math($member_price_total, $shop_price_total, 'bcsub', 2));
        }
        return formatAmount($amount);
    }

    /**
     * 获取加入购物车的商品数量
     * @param int $users_id 用户id
     * @return float
     * */
    public function getCartGoodsCnt(int $users_id, $goods_id = 0, $sku_id = 0)
    {
        $where = array(
            'userId' => $users_id
        );
        if ($goods_id > 0) {
            $where['goodsId'] = $goods_id;
        }
        if ($sku_id > 0) {
            $where['skuId'] = $sku_id;
        }
        $cart_model = new CartModel();
        $goods_cnt = $cart_model->where($where)->sum('goodsCnt');
        return (float)$goods_cnt;
    }
}
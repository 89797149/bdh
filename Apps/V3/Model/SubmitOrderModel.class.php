<?php
/**
 * 提交订单服务类
 * Created by PhpStorm.
 * User: heyh
 * Date: 2020-01-04
 * Time: 13:37
 */

namespace V3\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Orders\OrdersServiceModule;
use function Couchbase\defaultDecoder;
use Think\Model;

class SubmitOrderModel extends BaseModel
{
    /**
     * 获取用户购物车中的商品
     * @param int $userId
     * @param int $isCheck 选中状态(-1:不验证该字段|0:未选中|1:已选中)
     * */
    public function getCartGoodsChecked($userId = 0, $shopId = 0, $isCheck = 1)
    {
        $shopParam = (array)json_decode(htmlspecialchars_decode(I('shopParam', [])), true);//后加,购物车存在多家店铺时,用于兼容单店铺结算
        $shopIdArr = [];
        if (!empty($shopParam)) {
            $shopIdArr = array_column($shopParam, 'shopId');
        }
        $returnData = [
            'goodsId' => '',
            'goodsSku' => [],
        ];
        $cartTab = M('cart');
        if (!empty($userId)) {
            $goodsTab = M('goods');
            $where['userId'] = $userId;
            if ($isCheck > -1) {
                $where['isCheck'] = $isCheck;
            }
            $cartList = $cartTab->where($where)->select();
            if ($cartList) {
                $goodsId = '';
                $goodsSku = [];
                foreach ($cartList as $ck => $value) {
                    $goods_where = ['goodsId' => $value['goodsId']];
                    if (!empty($shopId)) {
                        $goods_where['shopId'] = $shopId;
                    }
                    $goodsInfo = $goodsTab->where($goods_where)->find();
                    if (!empty($shopIdArr) && !in_array($goodsInfo['shopId'], $shopIdArr)) {//后加,购物车存在多家店铺时,用于兼容单店铺结算
                        $goodsInfo = [];
                    }
                    if (!$goodsInfo) {
                        continue;
                    }
                    $goodsId .= $value['goodsId'] . ',';
                    $goodsInfo['cartId'] = $value['cartId'];
                    $goodsInfo['userId'] = $value['userId'];
                    $goodsInfo['isCheck'] = $value['isCheck'];
                    $goodsInfo['goodsAttrId'] = $value['goodsAttrId'];
                    $goodsInfo['goodsCnt'] = $value['goodsCnt'];
                    $goodsInfo['skuId'] = $value['skuId'];
                    $goodsInfo['remarks'] = $value['remarks'];
                    $goodsInfo['memberPrice'] = $goodsInfo['memberPrice'];
                    $goodsInfo['minBuyNum'] = $goodsInfo['minBuyNum'];
                    $goodsInfo['goodsFlag'] = $goodsInfo['goodsFlag'];
                    $goodsInfo['unconventionality'] = "0";//非常规订单配送时长
                    //非常规商品添加延迟配送时长
                    if (!empty($goodsInfo['isConvention']) && $goodsInfo['isConvention'] == 1) {
                        $goodsInfo['unconventionality'] = $GLOBALS['CONFIG']['unconventionality'];
                    }
                    $goodsSku[] = $goodsInfo;
                }
                $goodsId = trim($goodsId, ',');
                $returnData['goodsId'] = $goodsId;
                $returnData['goodsSku'] = getCartGoodsSku($goodsSku);
            }
        }
        $cartGoodsList = $returnData['goodsSku'];
        foreach ($cartGoodsList as $key => $value) {
            $goodsInfo = $value;
            $goodsId = $value['goodsId'];
            $goodsCnt = 0;
            $goodsAttrId = $value['goodsAttrId'];
            $skuId = $value['skuId'];
            $type = 1;
            $verificationCartGoods = $this->verificationCartGoods($userId, $goodsId, $goodsCnt, $goodsAttrId, $skuId, $type);//验证商品的有效状态
            $cartWhere = [];
            $cartWhere['cartId'] = $goodsInfo['cartId'];
            if ($verificationCartGoods['code'] == -1) {
                $cartTab->where($cartWhere)->save(['isCheck' => 0]);
                unset($cartGoodsList[$key]);
            }
            $goodsCount = $goodsInfo['goodsCnt'];
            $verificationStock = $this->verificationStock($goodsId, $goodsCount, $skuId);
            if ($verificationStock['code'] == -1) {
                $cartTab->where($cartWhere)->save(['isCheck' => 0]);
                unset($cartGoodsList[$key]);
            }
        }
        $returnData['goodsSku'] = (array)array_values($cartGoodsList);
        return $returnData;
    }

    /**
     * 获取店铺信息
     * @param int shopId
     * @param string field
     * */
    public function getShopInfo($shopId = 0, $field = '*')
    {
        $shopId = (int)$shopId;
        $shopInfo = M('shops')->where(['shopId' => $shopId])->field($field)->find();
        return (array)$shopInfo;
    }

    /**
     * 获取用户默认地址
     * @param int $userId
     * @param int $addressId PS:地址id为0取默认地址
     * */
    public function getAddress($userId = 0, $addressId = 0)
    {
        $userId = (int)$userId;
        $addressId = (int)$addressId;

        $addressWhere['userId'] = $userId;
        $addressWhere['addressFlag'] = 1;
        if ($addressId == 0) {
            $addressWhere['isDefault'] = 1;
        } else {
            $addressWhere['addressId'] = $addressId;
        }
        $userAddressTab = M('user_address');
        $addressInfo = $userAddressTab->where($addressWhere)->find();
        return (array)$addressInfo;
    }

    /**
     * 获取商品所属店铺
     * @param array $goods PS:购物车商品数据
     * */
    public function getGoodsShop($goods = [])
    {
        $shopArr = [];
        foreach ($goods as $key => $value) {
            $shopArr[] = $value['shopId'];
        }
        $shopArr = array_unique($shopArr);
        $where = [];
        $where['shopId'] = ['IN', $shopArr];
        $shops = M('shops')->where($where)->select();
        return (array)$shops;
    }

    /**
     * 处理商品数据,是否有sku需要替换的数据
     * @param array $goods
     * */
    public function handleGoodsSku($goods = [])
    {
        if (!empty($goods)) {
            $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
            for ($i = 0; $i < count($goods); $i++) {
                $goodsInfo = $goods[$i];
                $goodsId = $goodsInfo['goodsId'];
                $skuId = $goodsInfo['skuId'];
                $goodsIdArr[$i] = $goods[$i];
                $goodsId2[$i] = $goodsInfo;
                if ($skuId > 0) {
                    $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsId, 'skuId' => $skuId])->find();
                    foreach ($replaceSkuField as $rk => $rv) {
                        if ((int)$systemSkuSpec[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
                            continue;
                        }
                        if (in_array($rk, ['dataFlag', 'addTime'])) {
                            continue;
                        }
                        if (isset($goodsId2[$i][$rv])) {
                            $goodsId2[$i][$rv] = $systemSkuSpec[$rk];
                        }
                    }
                }
            }
            //给每个商品添加自己的店铺
            for ($i = 0; $i < count($goodsId2); $i++) {
                $goodsId2[$i]["shopcm"] = $this->getShopInfo($goodsId2[$i]['shopId']);
            }
            $result = array();
            foreach ($goodsId2 as $k => $v) {
                $result[$v["shopId"]][] = $v;//根据Id归类
            }
            $result = array_values($result);//重建索引
            return $result;
        }
        return [];
    }

    /**
     * 统计商品金额
     * @param int $cuid PS:用户优惠券id
     * @param int $users PS:用户信息
     * @param float $amount PS:金额
     * @param int $couponId PS:优惠券id
     * */
    public function checkCoupon($users, $amount, $cuid, $couponId)
    {
        $cartGoods = $this->getCartGoodsChecked($users['userId']);
        $goodsIdStr = $cartGoods['goodsId'];
        $goodsIdArr = explode(',', $goodsIdStr);
        $userCouponTab = M('coupons_users');
        $couponTab = M('coupons');
        $couponWhere = array();
        $couponWhere['id'] = $cuid;
        $couponWhere['userId'] = $users['userId'];
        $couponUserInfo = $userCouponTab->where($couponWhere)->find();
        if ($couponUserInfo) {//判断优惠券是否是本人的
            if ($couponUserInfo['couponStatus'] == 1) {//是否是未使用状态
                if ($couponUserInfo['couponExpireTime'] > date('Y-m-d H:i:s')) {//是否过期
                    $couponWhere = [];
                    $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponUserInfo['couponId'] : $couponUserInfo['ucouponId'];
                    $couponInfo = $couponTab->where($couponWhere)->find();
                    $price = $amount - $couponInfo['couponMoney'];//抵扣的金额
                    $couponAmount = $couponInfo['couponMoney'];//优惠券抵扣金额
//                    if ($couponInfo['spendMoney'] <= $amount) {//是否满足使用条件
//                        $price = $amount - $couponInfo['couponMoney'];//抵扣的金额
//                        $couponAmount = $couponInfo['couponMoney'];//优惠券抵扣金额
//                    } else {
//                        $apiRet = returnData(null,-1,'error','未达到最低消费金额');
//                        return $apiRet;
//                    }
                } else {
                    $apiRet = returnData(null, -1, 'error', '优惠券已过期');
                    return $apiRet;
                }
            } else {
                $apiRet = returnData(null, -1, 'error', '优惠券已使用');
                return $apiRet;
            }
        } else {
            $apiRet = returnData(null, -1, 'error', '优惠券未领取...');
            return $apiRet;
        }
        //检测商
        //品是否满足优惠券权限
        $msg = '';
        $checkArr = array(
            'couponId' => ((!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponUserInfo['couponId'] : $couponUserInfo['ucouponId']),
            'goods_id_arr' => $goodsIdArr,
        );
        $checkRes = check_coupons_auth($checkArr, $msg);
        if (!$checkRes) {
            $apiRet = returnData(null, -1, 'error', '优惠券使用失败');
            return $apiRet;
        }
        $data = [];
        $data['price'] = $price > 0 ? $price : 0;
        $data['couponMoney'] = $couponAmount;
        $data['couponType'] = $couponInfo['couponType'];
        $data['couponId'] = $couponUserInfo['couponId'];
        $data['spendMoney'] = $couponInfo['spendMoney'];
        return returnData($data);
    }

    /**
     * 提交订单-可用优惠券列表
     * @param array $param <p>
     * int userId 用户id
     * int shopId 店铺id
     * array
     * </p>
     * @param array $param [userId|shopId] PS:shopId > 0则只计算和该店铺有关的数据
     */
    public function effectiveCoupons($param)
    {
        //过滤配送劵状态
        if (empty($param['tpType'])) {
            $param['tpType'] = 'neq';
        }
        $shopId = 0;//兼容前置仓和多商户
        if (isset($param['shopId'])) {
            $shopId = (int)$param['shopId'];
        }
        $userId = $param['userId'];
        $goodsTab = M('goods');
        $tab = M('cart');
        $where = [];
        $where['userId'] = $userId;
        $where['isCheck'] = 1;
        $cartList = $tab->where($where)->select();
        $data = null;
        $amount = 0;
        if ($cartList) {
            $time = time();
            $userInfo = M('users')->where(['userId' => $userId])->field('userId,loginName,expireTime')->find();
            foreach ($cartList as $value) {
                $goodsInfo = $goodsTab->where(['goodsId' => $value['goodsId']])->find();
                $goodsPrice = $goodsInfo['shopPrice'];
                if (strtotime($userInfo['expireTime']) > $time) {
                    if ($goodsInfo['memberPrice'] > 0) {
                        $goodsPrice = $goodsInfo['memberPrice'];
                    } else {
                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
                            $goodsPrice = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
                        }
                    }
                }
                $totalPrice = $goodsPrice * $value['goodsCnt'];
                $amount += $totalPrice;
            }
        }
        $couponsTab = M('coupons');
        $couponsUsersTab = M('coupons_users');
        $couponWhere = array();
        $couponWhere['userId'] = $userInfo['userId'];
        $couponWhere['dataFlag'] = 1;
        $couponWhere['couponStatus'] = 1;
        $couponWhere['couponExpireTime'] = ['EGT', date('Y-m-d H:i:s')];
        $userCouponList = $couponsUsersTab->where($couponWhere)->select();
        if ($userCouponList) {
            $coupons = [];
            foreach ($userCouponList as $key => $value) {
                $where = [];
                $where['couponId'] = $value['couponId'];
                $where['dataFlag'] = 1;
                $where['couponType'] = array("{$param['tpType']}", 8);
                $couponInfo = $couponsTab->where($where)->find();
                //用于处理配送劵存在金额---主要兼容这个接口返回其他优惠券数据-----start-----2020-8-21 14:27:33---
//                if ($couponInfo['couponType'] == 8) {
//                    $couponInfo['spendMoney'] = $amount;
//                }
                //------------------------end-------------------------------------------------------------------
                if ($couponInfo && $couponInfo['spendMoney'] <= $amount) {
                    $newData = $couponInfo;
                    $newData['couponExpireTime'] = $value['couponExpireTime'];
                    $newData['userToId'] = $value['userToId'];
                    $newData['userId'] = $value['userId'];
                    $newData['receiveTime'] = $value['receiveTime'];
                    $newData['couponStatus'] = $value['couponStatus'];
                    $newData['userCouponId'] = $value['id'];
                    $newData['selfShopId'] = $shopId;
                    $newData['couponId'] = $value['couponId'];
                    $coupons[] = $newData;
                }
            }
            unset($key);
            unset($value);
            $sort = [];
            foreach ($coupons as $dataval) {
                $sort[] = $dataval['couponMoney'];
            }
            array_multisort($sort, SORT_DESC, SORT_NUMERIC, $coupons);
            if ($shopId > 0) {//后加,兼容前置仓和多商户
                foreach ($coupons as $index => $coupon) {
                    if ($coupon['shopId'] > 0 && $coupon['shopId'] != $shopId) {
                        unset($coupons[$index]);
                    }
                }
                $coupons = array_values($coupons);
            }
            $data = $coupons;
        }
        //后加,过滤掉不满足条件的优惠券
        $couponAuthTab = M('coupons_auth');
        if (!empty($param['orderData']) && !empty($data)) {
            $newCoupon = [];
            $users = $userInfo;
            $result = $param['orderData'];
            foreach ($result as $key => $value) {
                foreach ($value as $sonkey => $sonval) {
                    $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $sonval["goodsAttrId"], $sonval["goodsId"], $sonval["shopPrice"], $sonval["goodsCnt"], $sonval["shopId"])['totalMoney'];
                    //后加sku
                    if ($sonval["skuId"] > 0) {
                        $goodsTotalMoney = getGoodsSkuPrice($users['userId'], $sonval["skuId"], $sonval["goodsId"], $sonval["shopPrice"], $sonval["goodsCnt"], $sonval["shopId"])['totalMoney'];
                    }
                    foreach ($data as $ckey => $cval) {
                        $checkCoupon = $this->checkCoupon($users, $goodsTotalMoney, $cval['userCouponId']);
                        if ($checkCoupon['code'] == 0) {
                            $where = [];
                            $where['couponId'] = $cval['couponId'];
                            $where['type'] = 1;
                            $where['state'] = 1;
                            $couponAuth = $couponAuthTab->where($where)->select();
                            if ($couponAuth) {
                                foreach ($couponAuth as $authVal) {
                                    if ($authVal['toid'] == $sonval["goodsId"]) {
                                        if ($goodsTotalMoney < $checkCoupon['data']['spendMoney']) {
                                            unset($data[$ckey]);
                                            continue;
                                        }
                                    }
                                }
                            }
                            $msg = '';
                            $checkArr = array(
                                'couponId' => $checkCoupon['data']['couponId'],
                                'goods_id_arr' => $sonval["goodsId"],
                            );
                            $checkRes = check_coupons_auth($checkArr, $msg);
                            if ($checkRes) {
                                $newCoupon[] = $data[$ckey];
                            }
                        }
                    }
                }
            }
            if (empty($param['tpType'])) {
                $newCoupon = arrayUnset($newCoupon, 'couponId');
            } else {
                $newCoupon = arrayUnset($newCoupon, 'userCouponId');
            }
            $data = $newCoupon;
        }
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    /**
     * 验证商品的有效性
     * @param array $goods PS:商品数据
     * @param int $userId PS:用户id
     * @param int $addressId PS:地址id
     * @param int $isSelf 是否自提【0：不自提|1：自提】
     * */
    public function checkGoodsStatus($goods = [], $userId = 0, $addressId = 0, $isSelf = 0)
    {
        if (empty($goods) || empty($userId)) {
            //$apiRet = returnData(null,-1,'error','验证商品的有效性,参数异常');
            $apiRet = returnData(null, -1, 'error', '处理中，请稍等');
            return $apiRet;
        }
        $addressInfo = M('user_address')->where(['addressId' => $addressId])->find();
        $lat = (float)$addressInfo['lat'];
        $lng = (float)$addressInfo['lng'];
        $users = M('users')->where(['userId' => $userId, 'userFlag' => 1])->find();
        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
        $goodsTab = M('goods');
        foreach ($goods as $key => $value) {
            $cartGoodsInfo = $goods[$key];
            unset($where);
            $goodsId = $cartGoodsInfo['goodsId'];
            $goodsName = $cartGoodsInfo['goodsName'];
            $skuId = $cartGoodsInfo['skuId'];
            $where['goodsId'] = $goodsId;
            $res_goodsId = $goodsTab->lock(true)->where($where)->find();
//            $checkGoodsFlashSale = checkGoodsFlashSale($goodsId); //检查商品限时状况
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                unset($apiRet);
//                $apiRet['goodsId'] = $goodsId;
//                $apiRet['goodsName'] = $goodsName;
//                $apiRet = returnData($apiRet, -1, 'error', $checkGoodsFlashSale['apiInfo']);
//                return $apiRet;
//            }
//            $checkGoodsFlashSale = getGoodsFlashSale($goodsId,$code = 1); //检查商品限时状况---暂定
//            if ($checkGoodsFlashSale['code'] == -1) {
//                unset($apiRet);
//                $apiRet['goodsId'] = $goodsId;
//                $apiRet['goodsName'] = $goodsName;
//                $apiRet = returnData($apiRet, -1, 'error', '不在时间段内，不能购买');
//                return $apiRet;
//            }
            //针对新人专享商品，判断用户是否可以购买
            $isBuyNewPeopleGoods = isBuyNewPeopleGoods($goodsId, $userId);
            if (!$isBuyNewPeopleGoods) {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $goodsName . ' 是新人专享商品，您不能购买!');
                return $apiRet;
            }
            //检查商品是否属购买数量限制
            if ($cartGoodsInfo) {
                if (isset($cartGoodsInfo['buyNum']) && $cartGoodsInfo['buyNum'] > 0 && $cartGoodsInfo['goodsCnt'] > $cartGoodsInfo['buyNum']) {
                    unset($apiRet);
                    $apiRet['goodsId'] = $goodsId;
                    $apiRet = returnData($apiRet, -1, 'error', '单笔订单最多购买商品 ' . $goodsName . ' ' . $cartGoodsInfo['buyNum'] . '件');
                    return $apiRet;
                }
                if ($cartGoodsInfo['buyNumLimit'] != -1 && $cartGoodsInfo['goodsCnt'] > $cartGoodsInfo['buyNumLimit']) {
                    unset($apiRet);
                    $apiRet['goodsId'] = $goodsId;
                    $apiRet = returnData($apiRet, -1, 'error', '单笔订单最多购买商品 ' . $goodsName . ' ' . $cartGoodsInfo['buyNumLimit'] . '件');
                    return $apiRet;
                }
            }
            if ($res_goodsId['isSale'] == 0) {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $goodsName . '商品已下架');
                return $apiRet;
            }
            if ($res_goodsId['goodsStatus'] == -1) {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $goodsName . '商品已禁售');
                return $apiRet;
            }
            if ($res_goodsId['goodsFlag'] == -1) {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $goodsName . '商品不存在');
                return $apiRet;
            }
            //判断是否为限制下单次数的商品 start
            $checkRes = checkGoodsOrderNum($res_goodsId['goodsId'], $users['userId']);
            if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $checkRes['apiInfo']);
                return $apiRet;
            }
            //判断是否为限制下单次数的商品 end
            if ($skuId > 0) {
                $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsId, 'skuId' => $skuId])->find();
                foreach ($replaceSkuField as $rk => $rv) {
                    if ((int)$systemSkuSpec[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        continue;
                    }
                    if (in_array($rk, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if (isset($res_goodsId[$rv])) {
                        $res_goodsId[$rv] = $systemSkuSpec[$rk];
                    }
                }
            }
            if ($res_goodsId['minBuyNum'] > 0 && $cartGoodsInfo['goodsCnt'] < $res_goodsId['minBuyNum']) {
                unset($apiRet);
                $apiRet['goodsId'] = $goodsId;
                $apiRet['goodsName'] = $goodsName;
                $apiRet = returnData($apiRet, -1, 'error', $goodsName . '最小起购量为' . $res_goodsId['minBuyNum']);
                return $apiRet;
            }
//            //判断是否是限量购----库存替换
//            if($res_goodsId['isLimitBuy'] == 1){
//                $res_goodsId['goodsStock'] = $res_goodsId['limitCount'];
//            }
//            if((int)$res_goodsId['goodsStock'] < (int)$cartGoodsInfo['goodsCnt']){
//                unset($apiRet);
//                $apiRet['goodsId'] = $goodsId;
//                $apiRet['goodsName'] = $goodsName;
//                $apiRet = returnData($apiRet,-1,'error',$goodsName.'商品库存不足');
//                return $apiRet;
//            }
            /*
             * 2019-06-15 start
             * 判断属性库存是否充足
             * */
            $goodsAttr = M('goods_attributes');
            if (!empty($cartGoodsInfo['goodsAttrId'])) {
                $goodsAttrIdArr = explode(',', $cartGoodsInfo['goodsAttrId']);
                foreach ($goodsAttrIdArr as $iv) {
                    $goodsAttrInfo = $goodsAttr->lock(true)->where("id='" . $iv . "'")->find();
                    if ($goodsAttrInfo['attrStock'] <= 0) {
                        unset($apiRet);
                        $apiRet['goodsId'] = $goodsId;
                        $apiRet['goodsName'] = $goodsName;
                        $apiRet = returnData($apiRet, -1, 'error', $goodsName . '商品库存不足');
                        return $apiRet;
                    }
                }
            }
            $shopConfig = M('shop_configs')->where(['shopId' => $res_goodsId['shopId']])->find();
            //验证配送范围是否超出
            if ($shopConfig['deliveryLatLngLimit'] == 1 && $isSelf == 0) {
                if (empty($lat) || empty($lng)) {
                    unset($apiRet);
                    $apiRet['goodsId'] = $goodsId;
                    $apiRet['goodsName'] = $goodsName;
                    $apiRet = returnData($apiRet, -1, 'error', '请填写正确的经纬度');
                    return $apiRet;
                }
                $dcheck = checkShopDistribution($res_goodsId['shopId'], $lng, $lat);
                if (!$dcheck) {
                    unset($apiRet);
                    $apiRet['goodsId'] = $goodsId;
                    $apiRet['goodsName'] = $goodsName;
                    $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
                    return $apiRet;
                }
                //END
            }
            //秒杀商品限量控制
            if ($res_goodsId['isShopSecKill'] == 1) {
                unset($apiRet);
                $apiRet['apiCode'] = '';
                $apiRet['apiInfo'] = '';
                $apiRet['goodsId'] = $res_goodsId['goodsId'];
                $apiRet['goodsName'] = $res_goodsId['goodsName'];
                $apiRet['apiState'] = 'error';
                if ((int)$res_goodsId['shopSecKillNUM'] < (int)$cartGoodsInfo['goodsCnt']) {
                    $apiRet = returnData(null, -1, 'error', $goodsName . '秒杀库存不足');
                    return $apiRet;
                }
                //已经秒杀完成的记录
                $killTab = M('goods_secondskilllimit');
                $existWhere['userId'] = $users['userId'];
                $existWhere['goodsId'] = $res_goodsId['goodsId'];
                $existOrderW['o.orderStatus'] = ['IN', [0, 1, 2, 3, 4]];
                //$existWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
                $existWhere['state'] = 1;
                $existKillLog = $killTab->where($existWhere)->group('orderId')->select();
                $existOrderField = [
                    'o.orderId'
                ];
                $existOrderId = [];
                foreach ($existKillLog as $val) {
                    $existOrderId[] = $val['orderId'];
                }
                $existOrderW['o.orderFlag'] = 1;
                $existOrderW['o.orderId'] = ['IN', $existOrderId];
                $existOrder = M('orders o')
                    ->join("LEFT JOIN wst_order_goods og ON og.orderId=o.orderId")
                    ->where($existOrderW)
                    ->field($existOrderField)
                    ->count();
                if ($existOrder >= $res_goodsId['userSecKillNUM']) {
                    $num = $res_goodsId['userSecKillNUM'] - $existOrder; //剩余可购买次数
                    if ($num < 0) {
                        $num = 0;
                    }
                    $apiRet = returnData(null, -1, 'error', '每个用户最多购买' . $res_goodsId['userSecKillNUM'] . '次该商品' . ', 还能秒杀' . $goodsName . $num . '次');
                    return $apiRet;
                }
            }
        }
        return returnData(true);
    }

    /**
     * 检测商品是否在配送区域
     * @param int $userId 用户id
     * @param int $shopId 店铺id
     * @param int $addressId 地址id
     * */
    public function checkShopCommunitys($userId, $shopId, $addressId)
    {
        if (empty($userId) && empty($shopId) && empty($addressId)) {
            $apiRet = returnData(null, -1, 'error', '检测配送区域参数异常');
            return $apiRet;
        }
        $shopConfig = M('shop_configs')->where(['shopId' => $shopId])->find();
        $cartGoods = $this->getCartGoodsChecked($userId);
        $goodsIdStr = $cartGoods['goodsId'];
        $goodsIdArr = explode(",", $goodsIdStr);//分割出商品id
        //address
        $addressInfo = $this->getAddress($userId, $addressId);
        if ($shopConfig['relateAreaIdLimit'] == 1) {
            //判断商品是否在配送区域内 在确认订单页面 或者购物车就自动验证 提高用户体验度
            $isDistriScope = isDistriScope($goodsIdArr, $addressInfo['areaId3']);
            if (!empty($isDistriScope)) {
                $apiRet = returnData(null, -1, 'error', '商品不在配送区域');
                return $apiRet;
            }
        }
        return returnData();
    }

    /**
     * 更新wst_orders表中的orderToken字段
     * @param array $orderNo PS:订单号
     * @param array $orderToken PS:wst_order_merge表中的orderToken
     * */
    public function updateOrderToken($orderNo = [], $orderToken = '')
    {
        if (empty($orderNo) || empty($orderToken)) {
            $response = returnData(null, -1, 'error', '参数异常');
            return $response;
        }
        $response = returnData();
        $orderTab = M('orders');
        $validator = [];
        foreach ($orderNo as $key => $value) {
            $orderInfo = $orderTab->where(['orderNo' => $value])->field('orderId')->find();
            //写入订单合并表
            $orderMergeInsert = [];
            $orderMergeInsert['orderToken'] = md5($value);
            $orderMergeInsert['value'] = $value;
            $orderMergeInsert['createTime'] = time();
            M('order_merge')->add($orderMergeInsert);
            if ($orderInfo) {
                $saveData = [];
                $saveData['orderToken'] = $orderToken;
                $saveData['singleOrderToken'] = $orderMergeInsert['orderToken'];
                $orderTab->where(['orderId' => $orderInfo['orderId']])->save($saveData);
            } else {
                $validator[] = [$value => '处理失败,订单数据异常'];
            }
        }
        return $response;
    }

    /**
     * 订单送达时间验证,送达时间必须大于下单时间
     * @param int $orderId
     * */
    public function checkRequireTime($orderId)
    {
        $orderId = (int)$orderId;
        $field = 'orderId,requireTime';
        $orderInfo = $this->getOrderInfoById($orderId, $field);
        if (empty($orderInfo)) {
            $response = returnData(null, -1, 'error', '订单数据异常');
            return $response;
        }
        if (empty($orderInfo['requireTime'])) {
            //该字段在接口使用中为非必填,所以为空就不验证了
            return returnData();
        }
        if ($orderInfo['requireTime'] <= $orderInfo['createTime']) {
            $response = returnData(null, -1, 'error', '订单送达时间必须大于下单时间');
            return $response;
        }
        return returnData();
    }

    /**
     *根据订单id获取订单内容
     * @param int $orderId
     * @param string field
     * */
    public function getOrderInfoById($orderId, $field = '')
    {
        if (empty($field)) {
            $field = '*';
        }
        $orderInfo = M('orders')->where(['orderId' => $orderId])->field($field)->find();
        return (array)$orderInfo;
    }

    /**
     *根据用户id获取用户详情
     * @param int $userId
     * @param string field
     * */
    public function getUserInfoById($userId = 0, $field = '')
    {
        if (empty($field)) {
            $field = '*';
        }
        $userInfo = M('users')->where(['userId' => $userId])->field($field)->find();
        return (array)$userInfo;
    }

    /**
     * 预提交-获取会员节省了多少钱
     * @param int $userId
     * */
    public function preSubmitEconomyAmount($userId = 0)
    {
        $field = 'userId,expireTime';
        $userInfo = $this->getUserInfoById($userId, $field);
        $totalMemberPrice = 0;//会员总价
        $totalShopPrice = 0;//店铺总价
        if ($userInfo['expireTime'] > date('Y-m-d H:i:s')) {
            $cartGoods = $this->getCartGoodsChecked($userId);
            $goods = $cartGoods['goodsSku'];
            $shopId = [];
            foreach ($goods as $key => $value) {
                if (!empty($value['shopId'])) {
                    $shopId[] = $value['shopId'];
                }
            }
            $shopWhere = [];
            $shopWhere['shopFlag'] = 1;
            $shopWhere['shopId'] = ['IN', $shopId];
            $shops = M('shops')->where($shopWhere)->select();
            foreach ($shops as $key => $value) {
                foreach ($goods as $gval) {
                    if ($value['shopId'] == $gval['shopId']) {
                        $shops[$key]['goodsList'][] = (array)$gval;
                    }
                }
            }
            //$economyAmount = 0;
            foreach ($shops as $key => $value) {
                foreach ($value['goodsList'] as $gval) {
                    $memberPrice = $gval['memberPrice'];
                    $systemSkuSpec = M('sku_goods_system')->where(['skuId' => $gval["skuId"]])->find();
                    if ($systemSkuSpec && (int)$systemSkuSpec['skuMemberPrice'] > 0) {
                        $memberPrice = $systemSkuSpec['skuMemberPrice'];
                    }
                    //如果设置了会员价，则使用会员价，否则使用会员折扣
                    if ($memberPrice <= 0) {
                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
                            $memberPrice = $gval['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
                        }
                    }
                    $totalMemberPrice += $memberPrice;
                    $totalShopPrice += $gval['shopPrice'];
                }
            }
        }
        $economyAmount = 0;
        if ($totalMemberPrice > 0) {
            $economyAmount = abs((float)($totalMemberPrice - $totalShopPrice));
        }
        return formatAmount($economyAmount);
    }

    /**
     * 检测订单数据是否符合店铺设置的订单配送起步价
     * @param array $result PS:订单数据(该订单数据只是单店铺)
     * */
    public function checkdeliveryStartMoney($result)
    {
        if (empty($result)) {
            $response = returnData(null, -1, 'error', '订单商品信息不能为空');
            return $response;
        }
        $shopId = $result[0]['shopId'];
        $field = 'shopId,shopName,deliveryStartMoney';
        $shopInfo = $this->getShopInfo($shopId, $field);
        $totalMoney = 0;//该笔订单的商品总金额
        foreach ($result as $key => $value) {
            $shopInfo = $this->getShopInfo($value['shopId'], $field);
            $userId = $value['userId'];
            $goodsTotalMoney = getGoodsAttrPrice($userId, $value["goodsAttrId"], $value["goodsId"], $value["shopPrice"], $value["goodsCnt"], $value["shopId"])['totalMoney'];
            if ($value['skuId'] > 0) {
                $goodsTotalMoney = getGoodsSkuPrice($userId, $value['skuId'], $value['goodsId'], $value["shopPrice"], $value["goodsCnt"], $value["shopId"])['totalMoney'];
            }
            $totalMoney += $goodsTotalMoney;
        }
        if ($totalMoney < $shopInfo['deliveryStartMoney']) {
            $response = returnData(null, -1, 'error', "未达到店铺[" . $shopInfo['shopName'] . "]订单配送起步价");
            return $response;
        }
        return returnData();
    }

    /**
     * 预提交订单->获取用户发票信息列表 PS:仅门店支持开具发票时才会返回信息
     * @param int $userId
     * @param int $shopId
     * @return array $data
     * */
    public function getUserInvoices(int $userId, int $shopId)
    {
        $data = [];
        $shopInfo = $this->getShopInfo($shopId, 'isInvoice');
        if ($shopInfo['isInvoice'] != 1) {
            return $data;
        }
        $invoiceTab = M('invoice');
        $where = [];
        $where['userId'] = $userId;
        $data = $invoiceTab->where($where)->order('id desc')->select();
        return (array)$data;
    }

    /**
     *预提交订单->获取用户当前选择的发票信息
     * @param int $userId
     * @param int $invoiceClient
     * @return array $data
     * */
    public function getUserInvoiceInfo($userId, $invoiceClient)
    {
        $data = [];
        if (empty($userId) || empty($invoiceClient)) {
            return $data;
        }
        $invoiceTab = M('invoice');
        $where = [];
        $where['userId'] = $userId;
        $where['id'] = $invoiceClient;
        $data = $invoiceTab->where($where)->find();
        return (array)$data;
    }

    /**
     *获取优惠券详情
     * $param:
     * @param int couponId
     * */
    public function getCouponInfo($param)
    {
        $couponId = (int)$param['couponId'];
        $userId = (int)$param['userId'];
        $tab = M('coupons_users cu');
        $where = [];
        $where['c.dataFlag'] = 1;
        $where['cu.dataFlag'] = 1;
        $where['cu.id'] = $couponId;
        $where['cu.userId'] = $userId;
        $info = $tab
            ->join("left join wst_coupons c on c.couponId=cu.couponId")
            ->where($where)
            ->field('c.*,cu.id as userCouponId')
            ->find();
        return (array)$info;
    }

    /**
     *过滤用户已在其他店铺使用过的优惠券
     * $param:
     * @param array coupons PS:用户关于当前店铺可使用的优惠券
     * @param array shopParam PS:前端传过来的店铺数据 如:[{"shopId":"71","cuid":"83"}]
     * */
    public function filterCoupons($param)
    {
        $coupons = (array)$param['coupons'];
        $shopParam = (array)$param['shopParam'];
        $userId = (int)$param['userId'];
        $shopId = (int)$param['shopId'];
        if (empty($coupons) || empty($shopParam) || empty($userId)) {
            return $coupons;
        }
        foreach ($coupons as $key => $val) {
            foreach ($shopParam as $pkey => $pval) {
                $where = [];
                $where['userId'] = $userId;
                $where['couponId'] = $pval['cuid'];
                $userCouponInfo = $this->getCouponInfo($where);
                if ($val['userCouponId'] == $userCouponInfo['userCouponId'] && $shopId != $pval['shopId']) {
                    unset($coupons[$key]);
                }
            }
            $coupons = array_values($coupons);
        }
        return (array)$coupons;
    }

    /**
     * 会员节省了多少钱
     * @param int $userId
     * */
    public function getMemberEconomyAmount($userId)
    {
        $userId = (int)$userId;
        $userInfo = M('users')->where(['userId' => $userId])->find();
        if ($userInfo['expireTime'] > date('Y-m-d H:i:s')) {//是会员
            $cartGoods = $this->getCartGoodsChecked($userInfo['userId']);
            $goods = $cartGoods['goodsSku'];
            $result = $this->handleGoodsSku($goods);
            $shopPrice = 0;
            $memberPrice = 0;
            foreach ($result as $key => $val) {
                foreach ($val as $son) {
                    $goodsInfo = $son;
                    $systemSkuSpec = M('sku_goods_system')->where(['skuId' => $goodsInfo["skuId"]])->find();
                    if ($systemSkuSpec && (int)$systemSkuSpec['skuMemberPrice'] > 0) {
                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
                    }
                    //如果设置了会员价，则使用会员价，否则使用会员折扣
                    if ($goodsInfo['memberPrice'] > 0) {
                        $goodsMemberPrice = $goodsInfo['memberPrice'];
                    } else {
                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
                            $goodsMemberPrice = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
                        }
                    }
                    $shopPrice += $goodsInfo['shopPrice'] * $son['goodsCnt'];
                    $memberPrice += $goodsMemberPrice * $son['goodsCnt'];
                }
            }
        }
        $amount = 0;
        if ($memberPrice > 0) {
            $amount = abs($memberPrice - $shopPrice);
        }
        return formatAmount($amount);
    }

    /**
     * 验证货到付款参数的有效性,这里不做过多的验证,提交订单接口中都是写好的
     * 文档地址:https://www.yuque.com/docs/share/4a3be39d-da67-4759-b14e-813a4a179a75?#
     * */
    public function checkCashOnDeliveryParams(array $params)
    {
        $fromType = $params['fromType'];
        $useScore = $params['useScore'];
        $payFrom = $params['payFrom'];
        $userId = $params['userId'];
        if ($payFrom != 4) {
            return returnData();
        }
        //货到付款
        $shopTab = M('shops');
        $shopConfigTab = M('shop_configs config');
        $userCouponTab = M('coupons_users');
        $couponTab = M('coupons');
        //也不知道后面前置仓和多商户的差距会有多大,先分开写吧
        if ($fromType == 1) {
            //前置仓
            $shopId = $params['shopId'];
            $where = [];
            $where['shopId'] = $shopId;
            $where['shopFlag'] = 1;
            $shopInfo = $shopTab->where($where)->field('shopId,shopName')->find();
            $where = [];
            $where['shopId'] = $shopId;
            $shopConfigInfo = $shopConfigTab->where($where)->find();
            if (empty($shopConfigInfo)) {
                $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】配置信息有误");
                return $apiRet;
            }
            if ($shopConfigInfo['cashOnDelivery'] != 1) {
                $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】未开启货到付款功能");
                return $apiRet;
            }
            $cuid = $params['cuid'];
            if (!empty($cuid)) {
                if ($shopConfigInfo['cashOnDeliveryCoupon'] != 1) {
                    $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用优惠券");
                    return $apiRet;
                }
                $couponWhere = [];
                $couponWhere['id'] = $cuid;
                $couponWhere['userId'] = $userId;
                $couponUserInfo = $userCouponTab->where($couponWhere)->find();
                if (!empty($couponUserInfo)) {
                    $couponInfo = $couponTab->where(['couponId' => $couponUserInfo['couponId']])->find();
                    if ($couponInfo['couponType'] == 5 && $shopConfigInfo['cashOnDeliveryMemberCoupon'] != 1) {
                        $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用会员券");
                        return $apiRet;
                    }
                }
            }
            if ($useScore == 1 && $shopConfigInfo['cashOnDeliveryScore'] != 1) {
                $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用积分");
                return $apiRet;
            }
        }

        if ($fromType == 2) {
            //多商户
            $shopParam = (array)json_decode($params['shopParam'], true);
            foreach ($shopParam as $key => $value) {
                $shopId = $value['shopId'];
                $where = [];
                $where['shopId'] = $shopId;
                $where['shopFlag'] = 1;
                $shopInfo = $shopTab->where($where)->field('shopId,shopName')->find();
                $where = [];
                $where['shopId'] = $shopId;
                $shopConfigInfo = $shopConfigTab->where($where)->find();
                if (empty($shopConfigInfo)) {
                    $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】配置信息有误");
                    return $apiRet;
                }
                if ($shopConfigInfo['cashOnDelivery'] != 1) {
                    $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】未开启货到付款功能");
                    return $apiRet;
                }
                $cuid = $value['cuid'];
                if (!empty($cuid)) {
                    if ($shopConfigInfo['cashOnDeliveryCoupon'] != 1) {
                        $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用优惠券");
                        return $apiRet;
                    }
                    $couponWhere = [];
                    $couponWhere['id'] = $cuid;
                    $couponWhere['userId'] = $userId;
                    $couponUserInfo = $userCouponTab->where($couponWhere)->find();
                    if (!empty($couponUserInfo)) {
                        $couponInfo = $couponTab->where(['couponId' => $couponUserInfo['couponId']])->find();
                        if ($couponInfo['couponType'] == 5 && $shopConfigInfo['cashOnDeliveryMemberCoupon'] != 1) {
                            $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用会员券");
                            return $apiRet;
                        }
                    }
                }
                if ($useScore == 1 && $shopConfigInfo['cashOnDeliveryScore'] != 1) {
                    $apiRet = returnData(null, -1, 'error', "店铺【{$shopInfo['shopName']}】货到付款不支持使用积分");
                    return $apiRet;
                }
            }
        }
    }

    /**
     * 验证购物车商品状态的有效性 PS:适用场景:添加购物车|增加购物车商品的数量|购物车列表|input编辑购物车商品数量
     * @param int $userId 用户id
     * @param int $goodsId 商品id
     * @param int $goodsCnt 购买数量
     * @param int $goodsAttrId 属性id（已废弃）
     * @param int $skuId 商品skuId
     * @param int $type 场景【1：普通购买|2：再来一单|3:input编辑购物车商品数量】
     * */
    public function verificationCartGoods(int $userId, int $goodsId, int $goodsCnt, int $goodsAttrId, int $skuId, $type = 1)
    {
        $usersTab = M('users');
        $goodsTab = M('goods');
        $cartTab = M('cart');
        $where = [];
        $where['userId'] = $userId;
        $userInfo = $usersTab
            ->where($where)
            ->field(["userId", "expireTime"])
            ->find();
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsInfo = $goodsTab->where($where)->lock(true)->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '商品信息有误');
        }
        $where = [];
        $where['userId'] = $userId;
        $where['goodsId'] = $goodsId;
        if (!empty($skuId)) {
            $where['skuId'] = $skuId;
        }
        $cartInfo = $cartTab->where($where)->find();
        if ($type == 3) {
            $goodsCount = $goodsCnt;
        } else {
            $goodsCount = bc_math((int)$cartInfo['goodsCnt'], $goodsCnt, 'bcadd', 0); //购物车下单数量
        }
        $cartInfo['goodsCount'] = $goodsCount;
        if ($goodsInfo['goodsStatus'] != 1) {//商品状态
            return returnData(false, -1, 'error', '商品已禁售或未审核');
        }
        if ($goodsInfo['goodsFlag'] != 1) {//商品已被删除
            return returnData(false, -1, 'error', '商品已被删除');
        }
        if ($goodsInfo['isSale'] != 1) {//商品已被下架
            return returnData(false, -1, 'error', '商品已被下架');
        }
        /*$goodsInfo = rankGoodsPrice($goodsInfo);
        if(empty($skuId) && $goodsInfo['hasGoodsSku'] == 1){
            return returnData(false, -1, 'error', 'skuId有误');
        }*/
        if (in_array($type, [1, 3])) {
            #################################关于商品库存的验证请不要写在该区间,下面有个方法verificationStock方法专门处理商品库存相关#################################
            $checkRes = checkGoodsOrderNum($goodsId, $userId);//限制下单次数
            if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
                return returnData(false, -1, 'error', $checkRes['apiInfo']);
            }
            $isBuyNewPeopleGoods = isBuyNewPeopleGoods($goodsId, $userId);//针对新人专享商品，判断用户是否可以购买
            if (!$isBuyNewPeopleGoods) {
                return returnData(false, -1, 'error', '该商品属于新人专享商品，您不是新人用户');
            }
            if ($goodsInfo['isShopPreSale'] == 1) {
                return returnData(false, -1, 'error', '预售商品不能加入购物车');
            }
            if ($goodsInfo['buyNum'] > 0 && $goodsCount > $goodsInfo['buyNum']) {//单笔购买商品数量限制
                return returnData(false, -1, 'error', '超出限购数量');
            }
            if ($goodsInfo['buyNumLimit'] != -1 && $goodsCount > $goodsInfo['buyNumLimit']) {
                $msg = '单笔订单最多购买' . $goodsInfo['buyNumLimit'] . '件';
                return returnData(false, -1, 'error', $msg);
            }
            //会员专享
            if ($goodsInfo['isMembershipExclusive'] == 1) {
                if ($userInfo['expireTime'] <= date('Y-m-d H:i:s')) {
                    return returnData(false, -1, 'error', '当前商品是会员专享商品，您不是会员');
                }
            }
            //限购数量
            if ($goodsInfo['buyNum'] > 0 && $cartInfo['goodsCnt'] + $goodsCnt > $goodsInfo['buyNum']) {
                return returnData(false, -1, 'error', '超出限购数量');
            }
        }
        $data = [];
        $data['cartInfo'] = $cartInfo;//购物车信息
        $data['goodsInfo'] = $goodsInfo;//商品信息
        $data['userInfo'] = $userInfo;//用户信息
        return returnData($data);
    }

    /**
     * 验证商品库存是否满足下单库存 PS:使用场景:添加购物车|增加购物车商品数量|购物车列表
     * @param int $goodsId 商品id
     * @param int $goodsCnt 购买数量 PS:该数量为购物车中的购买数量
     * @param int $skuId 商品skuId
     * */
    public function verificationStock(int $goodsId, int $goodsCnt, $skuId = 0)
    {
        $where = [];
        $where['isSale'] = 1;
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['goodsId'] = $goodsId;
        $goodsInfo = M('goods')->where($where)->find();
        if (empty($goodsInfo)) {
            return returnData(null, -1, 'error', '请检查goodsId是否有误');
        }
        if ($goodsInfo['isFlashSale'] == 1) {//判断是否是限时购----进行相关数据替换
            $goodsTimeSnapped = getGoodsFlashSale($goodsId);
            if ($goodsTimeSnapped['code'] == 1) {
                //判断限时购库存----不够时直接使用原始数据
                $goodsTimeLimit = goodsTimeLimit($goodsId, $goodsTimeSnapped, $goodsCnt);
                if (empty($goodsTimeLimit)) {
                    $goodsInfo['marketPrice'] = $goodsTimeSnapped['marketPrice'];
                    $goodsInfo['shopPrice'] = $goodsTimeSnapped['activityPrice'];
                    $goodsInfo['minBuyNum'] = $goodsTimeSnapped['minBuyNum'];
                    $goodsInfo['goodsStock'] = $goodsTimeSnapped['activeInventory'];
                }
            }
        }
        $goodsStock = $goodsInfo['goodsStock'];//商品库存
        $goodsCntStock = $goodsCnt;//购买的商品库存数量,如果是称重商品则需要转换单位
        if ($goodsInfo['isLimitBuy'] == 1) {//限量商品->商品总库存替换成活动库存【限量购】
            $goodsLimitCount = goodsTimeLimit($goodsId, null, $goodsCnt);
            if (empty($goodsLimitCount)) {
                $goodsStock = $goodsInfo['limitCount'];
            }
        }
        if ($skuId <= 0) {//无sku
            if ($goodsInfo['minBuyNum'] > 0 && $goodsCnt < $goodsInfo['minBuyNum']) {
                return returnData(['canbuyNum' => $goodsInfo['minBuyNum']], -1, 'error', '最小起购量为' . $goodsInfo['minBuyNum']);
            }
            if ($goodsInfo['SuppPriceDiff'] == 1) {
                //称重商品
                $goodsCntStock = gChangeKg($goodsId, $goodsCnt, 1);
                //if($goodsCntStock > $goodsStock){
                if (bccomp($goodsCntStock, $goodsStock, 3) == 1) {
                    $canBuyNum = bc_math($goodsStock, ($goodsInfo['weightG'] / 1000), 'bcdiv', 0);//最多可以买过多少数量
                    $canBuyNum = (int)$canBuyNum;
                    return returnData(['canbuyNum' => $canBuyNum], -1, 'error', '库存数量不足，最多可购买数量' . $canBuyNum);
                }
            } else {
                //标品
                $goodsCntStock = gChangeKg($goodsId, $goodsCnt, 1);
                if ($goodsCntStock > $goodsStock) {
                    $canBuyNum = bc_math($goodsStock, $goodsInfo['weightG'], 'bcdiv', 0);//最多可以买过多少数量
                    $canBuyNum = (int)$canBuyNum;
                    return returnData(['canbuyNum' => $canBuyNum], -1, 'error', '库存数量不足，最多可购买数量' . $goodsStock);
                }
            }
        }
        if ($skuId > 0) {//有sku
            $systemTab = M('sku_goods_system');
            $where = [];
            $where['skuId'] = $skuId;
            $where['dataFlag'] = 1;
            $skuInfo = $systemTab->where($where)->find();
            if (empty($skuInfo) || $skuInfo['goodsId'] != $goodsId) {
                return returnData(false, -1, 'error', '商品id和skuId不匹配，请检查');
            }
            $goodsStock = $skuInfo['skuGoodsStock'];
            if ($skuInfo['minBuyNum'] > 0 && $goodsCnt < $skuInfo['minBuyNum']) {
                return returnData(['canbuyNum' => $skuInfo['minBuyNum']], -1, 'error', '最小起购量为' . $skuInfo['minBuyNum']);
            }
            if ($goodsInfo['SuppPriceDiff'] == 1) {
                //称重商品
                $goodsCntStock = gChangeKg($goodsId, $goodsCnt, 1, $skuId);
                //if($goodsCntStock > $goodsStock){
                if (bccomp($goodsCntStock, $goodsStock, 3) == 1) {
                    $canBuyNum = bc_math($goodsStock, ($skuInfo['weigetG'] / 1000), 'bcdiv', 0);//最多可以买过多少数量
                    $canBuyNum = (int)$canBuyNum;
                    return returnData(['canbuyNum' => $canBuyNum], -1, 'error', '库存数量不足，最多可购买数量' . $canBuyNum);
                }
            } else {
                //标品
                $goodsCntStock = gChangeKg($goodsId, $goodsCnt, 1, $skuId);
                if ($goodsCntStock > $goodsStock) {
                    $canBuyNum = bc_math($goodsStock, ($skuInfo['weigetG']), 'bcdiv', 0);//最多可以买过多少数量
                    $canBuyNum = (int)$canBuyNum;
                    return returnData(['canbuyNum' => $canBuyNum], -1, 'error', '库存数量不足，最多可购买数量' . $goodsStock);
                }
            }
        }
        $data = [];
        $data['goodsStock'] = $goodsStock;//商品总库存
        $data['goodsCnt'] = $goodsCnt;//购买商品数量
        $data['goodsCntStock'] = $goodsCntStock;//购买的商品数量的库存总计
        return returnData($data);
    }

    /**
     * 废弃:包装系数已经废除了
     * 处理实际购买数量(包装系数)
     * @param int $goodsId
     * @param int $skuId
     * @param float $goods_cnt 购物车数量/重量
     * @param int float $goods_stock 商品剩余库存
     * @return array
     * */
    public function handleGoodsCntToStock(int $goodsId, int $skuId, $goodsCnt = 1, $goods_stock = 0)
    {
        $goods_service_module = new GoodsServiceModule();
        $field = 'goodsId,goodsName,SuppPriceDiff,weightG,unit';
        $goods_result = $goods_service_module->getGoodsInfoById($goodsId, $field);
        $goods_data = (array)$goods_result['data'];
        $goods_stock = (float)$goods_stock;
        $stock = (float)$goodsCnt;
        //PS:只需要处理标品相关的包装系数库存就可以了
        if ($goods_data['SuppPriceDiff'] == 1) {
            return $stock;
        }
        //包装系数已经废除了,这里默认给个1
        if ($skuId > 0) {
            //有sku
            $sku_system_result = $goods_service_module->getSkuSystemInfoById($skuId);
            $sku_system_data = (array)$sku_system_result['data'];
            $packing_factor = (float)$sku_system_data['weigetG'];
        } else {
            //无sku
            $packing_factor = (float)$goods_data['weightG'];
        }
        if (empty($packing_factor)) {
            //兼容以前的程序,避免没有设置包装系数导致流程不同
            return $stock;
        }
        $stock = (float)bc_math($goodsCnt, $packing_factor, 'bcmul', 2);
        $can_buy_num = (int)bc_math($goods_stock, $packing_factor, 'bcdiv', 2);
        $result = array(
            'goods_cnt' => (int)$goodsCnt,//加入购物车的数量
            'stock' => $stock,//购物车数量转化为库存
            'can_buy_num' => $can_buy_num//剩余可购买份数
        );
        return $result;
    }

    /**
     * 该方法请结合function.php中的reduceGoodsStockByRedis方法使用,未看明白该方法不要随意调用或更改,目前只支持移动端提交订单,这是提交订单的扣库存,其他的请用商品类里面的deductionGoodsStock方法
     * 下单扣除商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuId
     * @param float $buy_cnt 购买的数量/重量(同购物车数量)
     * @param array $orderGoodsInfo 订单商品信息 PS:包含(商品基本信息+购物车信息) 主要针对限时限量的判断
     * @param object $trans
     * @return array
     * */
    public function reduceGoodsStock(int $goods_id, $sku_id = 0, float $buy_cnt, $order_goods_info, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($goods_id) || empty($buy_cnt)) {
            $m->rollback();
            return returnData(false, -1, 'error', '参数有误');
        }
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsName,goodsStock,isShopSecKill,SuppPriceDiff,weightG,shopSecKillNUM,isFlashSale,isLimitBuy,limitCount,limitCountActivityPrice,limitCount';
        $goods_result = $goods_module->getGoodsInfoById($goods_id, $field);
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return returnData(false, -1, 'error', "商品信息有误");
        }
        $goods_info = $goods_result['data'];
        $goods_type = $order_goods_info['goods_type'];//(1:普通商品 2:限量商品 3:限时商品)
        $stock = gChangeKg($goods_id, $buy_cnt, 1, $sku_id);
        //判断商品类型---start PS:后续新增的商品类型加在这里
        $real_goods_type = 1;
        $goods_info['activity_certification'] = 0;//是否通过活动校验被认定为活动商品(0:否 1:是)
        if ($goods_type == 2) {
            //限量活动商品(通过限量规则校验后,最终被认定为限量活动商品)
            $goods_limit_count = goodsTimeLimit($goods_id, null, $buy_cnt, 4);
            if (empty($goods_limit_count)) {
                $goods_info['activity_certification'] = 1;
            }
            if ($goods_info['activity_certification'] == 1) {
                //扣除活动商品库存
//                $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $stock, 2, $m);
                $real_goods_type = 2;
            } else {
                //扣除普通商品库存
//                $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $stock, 1, $m);
                $real_goods_type = 1;
            }
        }
        if ($goods_type == 3) {
            //限时活动商品
            $goods_flash_sale = getGoodsFlashSale($goods_id);
            $goods_limit_count = goodsTimeLimit($goods_id, $goods_flash_sale, $buy_cnt);
            if (empty($goods_limit_count)) {
                $goods_info['activity_certification'] = 1;
            }
            if ($goods_info['activity_certification'] == 1) {
                //扣除活动商品库存
//                $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $stock, 3, $m);
                $real_goods_type = 3;
            } else {
                //扣除普通商品库存
//                $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $stock, 1, $m);
                $real_goods_type = 1;
            }
        }
        $stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $stock, $real_goods_type, 2, $m);
        //判断商品类型---end PS:后续新增的商品类型加在这里
        if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return returnData(false, -1, 'error', "商品【{$order_goods_info['goodsName']}】库存更新失败");
        }
        if (empty($trans)) {
            $m->commit();
        }
        return returnData(true);
    }

    /**
     * 取消订单归还商品库存
     * @param int $id 订单商品表id
     * @param object $trans
     * @return array
     * */
    public function returnOrderGoodsStock(int $id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($id)) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '返还库存失败，参数有误');
        }
        $orders_service_module = new OrdersServiceModule();
        $order_goods_result = $orders_service_module->getOrderGoodsInfoById($id);
        if ($order_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return returnData(false, -1, 'error', '返还库存失败，订单信息有误');
        }
        $order_goods_data = $order_goods_result['data'];
        $order_id = $order_goods_data['orderId'];
        $goods_id = (float)$order_goods_data['goodsId'];
        $sku_id = (float)$order_goods_data['skuId'];
        $goods_cnt = (float)$order_goods_data['goodsNums'];
        $goods_type = (float)$order_goods_data['goods_type'];
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsSn,goodsName,weightG,SuppPriceDiff';
        $goods_result = $goods_module->getGoodsInfoById($goods_id, $field);
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '返还库存失败，订单商品信息有误');
        }
//        $goods_data = $goods_result['data'];
//        $weightG = (float)$goods_data['weightG'];
//        $is_supp_price_diff = (int)$goods_data['SuppPriceDiff'];
//        if ($is_supp_price_diff == 1) {
//            //非标品商品g转成kg
//            $stock = gChangeKg($goods_id, $goods_cnt);
//        } else {
//            //标品-购买份数*包装系数
//            $stock = bc_math($goods_cnt, $weightG, 'bcmul', 3);
//        }
        $stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
//        $stock = (float)$stock;
        $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $stock, $goods_type, 2, $m);
        if ($goods_type == 2) {//限量购,取消后需要删除限量购商品购买记录表, 临时修改bug
            $orderRow = $orders_service_module->getOrderInfoById($order_id)['data'];
            if (!empty($orderRow)) {
                $logWhere = array(
                    'userId' => $orderRow['userId'],
                    'goodsId' => $goods_id,
                    'is_delete' => 0
                );
                $logParams = array(
                    'is_delete' => 1
                );
                M("limit_goods_buy_log")->where($logWhere)->save($logParams);
            }
        }
        if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '返还库存失败');
        }
        if (empty($trans)) {
            $m->commit();
        }
        return returnData(true);
    }
}
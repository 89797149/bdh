<?php


namespace App\Modules\Goods;

use App\Models\AttributesModel;
use App\Models\GoodsAttributesModel;
use App\Models\GoodsCatModel;
use App\Models\BarcodeModel;
use App\Models\GoodsGalleryModel;
use App\Models\GoodsModel;
use App\Models\GoodsLogModel;
use App\Models\GoodsTimeSnappedModel;
use App\Models\LimitGoodsBuyLog;
use App\Models\LimitGoodsBuyLogModel;
use App\Models\OrdersModel;
use App\Models\PurchaseBillGoodsModel;
use App\Models\RankGoodsModel;
use App\Models\RankModel;
use App\Models\RankUserModel;
use App\Models\ShopsCatModel;
use App\Models\SkuGoodsSelfModel;
use App\Models\SkuGoodsSystemModel;
use App\Models\SkuSpecAttrModel;
use App\Models\WarehousingBillGoodsModel;
use App\Modules\Coupons\CouponsModule;
use App\Modules\Orders\CartModule;
use App\Modules\Purchase\PurchaseModule;
use App\Modules\PYCode\PYCodeModule;
use App\Modules\Rank\RankModule;
use App\Modules\Shops\ShopCatsModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\ShopStaffMember\ShopStaffMemberModule;
use App\Modules\Supplier\SupplierModule;
use App\Modules\Users\UsersModule;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use Home\Model\GoodsSkuModel;
use Merchantapi\Model\LiveplayModel;
use Think\Model;
use App\Modules\Rank\RankServiceModule;

//商品类
class GoodsModule extends BaseModel
{
    /**
     * 处理商品结果集
     * @param array $initGoods 商品结果集
     * @return array
     * */
    public function filterGoods(&$initGoods = array(), $debug = 2)
    {
        //PS:目前处理了普通商品,身份价格,会员价,限时商品和限购商品
        if (empty($initGoods)) {
            return $initGoods;
        }
        $users_result = (new UsersModule())->getUsersInfoByMemberToken();
        $user_id = 0;
        if ($users_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $user_id = $users_result['data']['userId'];
        }
        $needHandleGoods = $initGoods;
        if (array_keys($initGoods) !== range(0, count($initGoods) - 1)) {
            //商品详情
            $needHandleGoods = array($initGoods);
        }
        $returnGoods = $this->filterGoodsList($user_id, $needHandleGoods, $debug);
        if (array_keys($initGoods) !== range(0, count($initGoods) - 1)) {
            $returnGoods = $returnGoods[0];
        }
        $initGoods = $returnGoods;
        return (array)$initGoods;
    }

    /**
     * 处理商品结果集
     * @param int $user_id 用户id
     * @param array $goods 商品信息(包含sku信息)
     * @return array
     * */
    public function filterGoodsList($user_id = 0, $goods = array(), $debug = 2)
    {
        if (empty($goods)) {
            return $goods;
        }
        $cart_module = new CartModule();
        //处理普通商品的身份价格-start
        foreach ($goods as $goodsKey => $goodsRow) {
            if (isset($goodsRow['cartId'])) {//如果是购物车商品,则替换购物车价格,最终以shopPrice字段呈现
                $goodsRow['init_price'] = $goodsRow['shopPrice'];//最终价格,用于后续需要运算的地方
                $goodsRow['goodsStock'] = $goodsRow['selling_stock'];//最终价格,用于后续需要运算的地方
                if ($goodsRow['hasGoodsSku'] == 1) {
                    $sku_list = $goodsRow['goodsSku']['skuList'];
                    if (array_keys($sku_list) !== range(0, count($sku_list) - 1)) {
                        $sku_list = array($sku_list);
                    }
                    foreach ($sku_list as $sku_val) {
                        $goodsRow['shopPrice'] = $sku_val['systemSpec']['skuShopPrice'];
                        $goodsRow['init_price'] = $goodsRow['shopPrice'];
                        $goodsRow['goodsStock'] = $sku_val['systemSpec']['selling_stock'];
                    }
                    if (array_keys($goodsRow['goodsSku']['skuList']) !== range(0, count($goodsRow['goodsSku']['skuList']) - 1)) {
                        $sku_list = $sku_list[0];
                    }
                    $goodsRow['goodsSku']['skuList'] = $sku_list;
                }
            }
            $goods[$goodsKey] = $goodsRow;
        }
        $goodsRankListMap = [];
        $goodsRankList = $this->handleGoodsRankPriceBatch($user_id, $goods);
        foreach ($goodsRankList as $goodsRankListRow) {
            $uniKey = $goodsRankListRow['goodsId'] . "@" . $goodsRankListRow['skuId'];
            $goodsRankListMap[$uniKey] = $goodsRankListRow;
        }
        $goodsMemberPriceListMap = [];
        $goodsMemberPriceList = $this->handleGoodsMemberPriceBatch($user_id, $goods);
        foreach ($goodsMemberPriceList as $goodsMemberPriceListRow) {
            $uniKey = $goodsMemberPriceListRow['goodsId'] . "@" . $goodsMemberPriceListRow['skuId'];
            $goodsMemberPriceListMap[$uniKey] = $goodsMemberPriceListRow;
        }
        $getCartGoodsCntListMap = [];
        $cartList = $cart_module->getUserCartList($user_id, -1);
        foreach ($cartList as $cartListRow) {
            if (empty($getCartGoodsCntListMap[$cartListRow['goodsId']])) {
                $getCartGoodsCntListMap[$cartListRow['goodsId']] = $cartListRow['goodsCnt'];
            } else {
                $getCartGoodsCntListMap[$cartListRow['goodsId']] = bc_math($getCartGoodsCntListMap[$cartListRow['goodsId']], $cartListRow['goodsCnt'], 'bcadd', 3);
            }
        }

        foreach ($goods as $key => &$val) {
            $uniKey = $val['goodsId'] . "@" . $val['skuId'];
            $tag = 'default';//商品类型(default:普通商品 limit_buy:限量购 flash_sale:限时购)
            $goods_id = $goods[$key]['goodsId'];
//            if (isset($val['cartId'])) {//如果是购物车商品,则替换购物车价格,最终以shopPrice字段呈现
//                $val['init_price'] = $val['shopPrice'];//最终价格,用于后续需要运算的地方
//                $val['goodsStock'] = $val['selling_stock'];//最终价格,用于后续需要运算的地方
//                if ($val['hasGoodsSku'] == 1) {
//                    $sku_list = $val['goodsSku']['skuList'];
//                    if (array_keys($sku_list) !== range(0, count($sku_list) - 1)) {
//                        $sku_list = array($sku_list);
//                    }
//                    foreach ($sku_list as $sku_val) {
//                        $val['shopPrice'] = $sku_val['systemSpec']['skuShopPrice'];
//                        $val['init_price'] = $val['shopPrice'];
//                        $val['goodsStock'] = $sku_val['systemSpec']['selling_stock'];
//                    }
//                    if (array_keys($val['goodsSku']['skuList']) !== range(0, count($val['goodsSku']['skuList']) - 1)) {
//                        $sku_list = $sku_list[0];
//                    }
//                    $val['goodsSku']['skuList'] = $sku_list;
//                }
//            }
            //处理普通商品的身份价格-start
//            $this->handleGoodsRankPrice($user_id, $val);
            if (!empty($goodsRankListMap[$uniKey])) {
                $val = $goodsRankListMap[$uniKey];
            }
            //处理普通商品的身份价格-end
            //处理普通商品的会员价-start
//            $this->handleGoodsMemberPrice($user_id, $val);
            if (!empty($goodsMemberPriceListMap[$uniKey])) {
                $val = $goodsMemberPriceListMap[$uniKey];
            }
            //处理普通商品的会员价-end
            $goods_info = $val;
            //限量购-start
            if ($goods_info['isLimitBuy'] == 1 && $goods_info['limitCount'] > 0) {
                if (isset($goods_info['cartId'])) {
                    $goods_cnt = $goods_info['goodsCnt'];
                    if ($goods_cnt <= 0) {
                        $goods_cnt = $goods_info['goods_cnt'];
                    }
                    $result = $this->verificationLimitGoodsBuyLog($user_id, $goods_id, $goods_cnt);//验证是否符合限量购条件
                } else {
                    $result = $this->verificationLimitGoodsBuyLog($user_id, $goods_id);//验证是否符合限量购条件
                }
                if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $val["shopPrice"] = $goods_info["limitCountActivityPrice"];
                    $val['init_price'] = $val['shopPrice'];
                    $val['goodsStock'] = $goods_info['limitCount'];
                }
                $tag = 'limit_buy';
            }
            //限量购-end
            //限时购-start
            if ($goods_info['isFlashSale'] == 1) {
                $result = $this->getGoodsFlashSale($goods_id);
                if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $result_data = $result['data'];
                    if (!empty($goods_info['goodsCnt'])) {
                        $goods_info['goods_cnt'] = $goods_info['goodsCnt'];
                    }
                    $goodsTimeLimitRes = null;
                    if (!empty($goods_info['goods_cnt'])) {
                        $goodsTimeLimitRes = goodsTimeLimit($goods_info['goodsId'], $result_data, $goods_info['goods_cnt']);
                    }
                    if ($result_data['activeInventory'] > 0 && empty($goodsTimeLimitRes)) {
                        $val["shopPrice"] = $result_data["activityPrice"];
                        $val["init_price"] = $val["shopPrice"];
                        $val['marketPrice'] = $result_data['marketPrice'];
                        $val['minBuyNum'] = $result_data['minBuyNum'];
                        $val['goodsStock'] = $result_data['activeInventory'];
                        $tag = 'flash_sale';
                    }
                }
            }
            //限时购-end
            if (in_array($tag, array('limit_buy', 'flash_sale')) && $goods_info['hasGoodsSku'] != 0) {//限量购和限时购不支持sku
                $val['hasGoodsSku'] = 0;
                $val['goodsSku']['skuSpec'] = array();
                $val['goodsSku']['skuList'] = array();
            }
            $val['goods_type'] = 1;//(1:普通商品 2:限量商品 3:限时商品)
            if ($tag == 'limit_buy') {
                $val['goods_type'] = 2;
            }
            if ($tag == 'flash_sale') {
                $val['goods_type'] = 3;
            }
            $val['in_the_cart'] = 0;//购物车中是否存在(0:不存在 1:存在)
            $val['goods_cart_num'] = 0;//当前商品购物车数量
            if ($user_id > 0) {
//                $goods_cart_num = $cart_module->getCartGoodsCnt($user_id, $goods_id);
                $goods_cart_num = (float)$getCartGoodsCntListMap[$goods_id];
                if ($goods_cart_num > 0) {
                    $val['in_the_cart'] = 1;
                    $val['goods_cart_num'] = $goods_cart_num;
                }
            }
        }
        unset($val);
        return (array)$goods;
    }

    /**
     * 处理商品结果集-处理商品的身份价格 注:仅仅配合filterGoodsList方法使用
     * @param int $userId 用户id
     * @param array $goodsDetail 商品信息
     * @return array
     * */
    private function handleGoodsRankPrice(int $userId, &$goodsDetail = array())
    {
        if (empty($goodsDetail)) {
            return $goodsDetail;
        }
        $initGoodsDetail = $goodsDetail;
        $rankModule = new RankModule();
        $rankDetail = $rankModule->getUserRankDetialByUserId($userId);
        if (empty($rankDetail)) {
            return $goodsDetail;
        }
        $goodsId = $goodsDetail['goodsId'];
        $rankId = $rankDetail['rankId'];
        $goodsRankList = $this->getGoodsRankListByGoodsId($goodsId, 0);//获取商品无sku的身份价格
        foreach ($goodsRankList as $rankDetail) {
            if ($rankDetail['rankId'] == $rankId) {
                $goodsDetail['shopPrice'] = $rankDetail['price'];
                $goodsDetail['init_price'] = $goodsDetail['shopPrice'];
            }
        }
        if ($goodsDetail['hasGoodsSku'] != 1) {
            return $goodsDetail;
        }
        $skuList = $goodsDetail['goodsSku']['skuList'];
        if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
            $skuList = array($skuList);
        }
        foreach ($skuList as $skuKey => $skuDetail) {
            $skuId = $skuDetail['skuId'];
            $skuRankList = $this->getGoodsRankListByGoodsId($goodsId, $skuId);//获取商品sku的身份价格
            if (!empty($skuRankList)) {
                foreach ($skuRankList as $skuRankDetail) {
                    if ($skuRankDetail['rankId'] == $rankId) {
                        $skuList[$skuKey]['systemSpec']['skuShopPrice'] = $skuRankDetail['price'];
                        if (isset($goodsDetail['cartId'])) {//购物车商品
                            $goodsDetail['shopPrice'] = $skuRankDetail['price'];
                            $goodsDetail['init_price'] = $goodsDetail['shopPrice'];
                        }
                    }
                }
            } else {
                if (isset($goodsDetail['cartId'])) {//购物车商品
                    $goodsDetail['shopPrice'] = $initGoodsDetail['shopPrice'];
                    $goodsDetail['init_price'] = $goodsDetail['shopPrice'];
                }
            }
        }
        if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
            $skuList = $skuList[0];
        }
        $goodsDetail['goodsSku']['skuList'] = $skuList;
        return $goodsDetail;
    }

    /**
     * 处理商品结果集-处理商品的身份价格 注:仅仅配合filterGoodsList方法使用
     * @param int $userId 用户id
     * @param array $goodsDetail 商品信息
     * @return array
     * */
    private function handleGoodsRankPriceBatch(int $userId, &$goodsDetail = array())
    {
        if (empty($goodsDetail)) {
            return $goodsDetail;
        }
//        $initGoodsDetail = $goodsDetail;
        $initGoodsDetail = [];
        foreach ($goodsDetail as $goodsDetailRow) {
            $uniKey = $goodsDetailRow['goodsId'] . "@" . $goodsDetailRow['skuId'];
            $initGoodsDetail[$uniKey] = $goodsDetailRow;
        }
        $rankModule = new RankModule();
        $rankDetail = $rankModule->getUserRankDetialByUserId($userId);
        if (empty($rankDetail)) {
            return $goodsDetail;
        }
        $goodsIdArr = array_column($goodsDetail, 'goodsId');
        $goodsIdArr = array_unique($goodsIdArr);
        $skuIdArr = array_column($goodsDetail, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        $model = new RankGoodsModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'rank_goods.isDelete' => 0,
            'rank_goods.goodsId' => array('in', $goodsIdArr),
            'rank_goods.skuId' => array('in', $skuIdArr),
            'rank.isDelete' => 0,
        );
        $field = 'rank.rankId,rank.rankName,rank_goods.price,rank_goods.goodsId,rank_goods.skuId';
        $rankGoodsListMap = [];
        $rankGoodsList = $model
            ->alias('rank_goods')
            ->join("left join {$prefix}rank rank on rank.rankId=rank_goods.rankId")
            ->where($where)
            ->field($field)
            ->select();
        foreach ($rankGoodsList as $rankGoodsListRow) {
            $uniKey = $rankGoodsListRow['goodsId'] . "@" . $rankGoodsListRow['skuId'];
            $rankGoodsListMap[$uniKey] = $rankGoodsListRow;
        }
        $goodsList = $goodsDetail;
        foreach ($goodsList as $key => $goodsListRow) {
            $uniKey = $goodsListRow['goodsId'] . "@" . $goodsListRow['skuId'];
            $goodsId = $goodsListRow['goodsId'];
//            $rankId = $rankDetail['rankId'];
//            $goodsRankList = $this->getGoodsRankListByGoodsId($goodsId, 0);//获取商品无sku的身份价格
//            foreach ($goodsRankList as $rankDetail) {
//                if ($rankDetail['rankId'] == $rankId) {
//                    $goodsListRow['shopPrice'] = $rankDetail['price'];
//                    $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
//                }
//            }
            $rankDetail = $rankGoodsListMap[$uniKey];
            if (!empty($rankGoodsListMapRow)) {
                $goodsListRow['shopPrice'] = $rankDetail['price'];
                $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
            }
            if ($goodsListRow['hasGoodsSku'] != 1) {
                $goodsList[$key] = $goodsListRow;
                continue;
            }
            $skuList = $goodsListRow['goodsSku']['skuList'];
            if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
                $skuList = array($skuList);
            }
            foreach ($skuList as $skuKey => $skuDetail) {
//                $skuId = $skuDetail['skuId'];
//                $skuRankList = $this->getGoodsRankListByGoodsId($goodsId, $skuId);//获取商品sku的身份价格
                if (!empty($rankDetail)) {
                    $skuList[$skuKey]['systemSpec']['skuShopPrice'] = $rankDetail['price'];
                    if (isset($goodsListRow['cartId'])) {//购物车商品
                        $goodsListRow['shopPrice'] = $rankDetail['price'];
                        $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
                    }
                } else {
                    if (isset($goodsListRow['cartId'])) {//购物车商品
//                        $goodsListRow['shopPrice'] = $initGoodsDetail['shopPrice'];
                        $goodsListRow['shopPrice'] = $initGoodsDetail[$uniKey]['shopPrice'];
                        $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
                    }
                }
            }
            if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
                $skuList = $skuList[0];
            }
            $goodsListRow['goodsSku']['skuList'] = $skuList;
            $goodsList[$key] = $goodsListRow;
        }
        return $goodsList;
    }

    /**
     * 处理商品结果集-处理商品的会员价格 注:仅仅配合filterGoodsList方法使用
     * @param int $userId 用户id
     * @param array $goodsDetail 商品信息
     * @return array
     * */
    private function handleGoodsMemberPrice(int $userId, &$goodsDetail = array())
    {
        if (empty($goodsDetail)) {
            return $goodsDetail;
        }
        $lastGoodsDetail = $goodsDetail;
        $isMember = 0;//是否是有效会员(0:非会员 1:有效会员)
        if ($userId > 0) {
            $user_detail = (new UsersModule())->getUsersDetailById($userId, 'userId,expireTime', 2);
            if ($user_detail['expireTime'] > date('Y-m-d H:i:s')) {
                $isMember = 1;
            }
        }
        if ($isMember != 1) {
            return $goodsDetail;
        }
        $goodsId = $goodsDetail['goodsId'];
        $goodsInfo = $this->getGoodsInfoById($goodsId, 'goodsId,shopPrice,memberPrice', 2);
        $memberDiscount = $GLOBALS["CONFIG"]["userDiscount"] / 100;//会员折扣
        if ($goodsInfo['memberPrice'] > 0) {//商品设置了会员价
            $goodsDetail['shopPrice'] = $goodsInfo['shopPrice'];
            $goodsDetail['memberPrice'] = $goodsInfo['memberPrice'];
            if (isset($lastGoodsDetail['cartId'])) {//如果是购物车商品
                $goodsDetail['memberPrice'] = $lastGoodsDetail['memberPrice'];
                $goodsDetail['shopPrice'] = $lastGoodsDetail['shopPrice'];
                if ($lastGoodsDetail['memberPrice'] > 0) {
                    $goodsDetail['shopPrice'] = $lastGoodsDetail['memberPrice'];
                }
                $goodsDetail['init_price'] = $lastGoodsDetail['shopPrice'];
            }
        }
        if ($goodsInfo['memberPrice'] <= 0 && $memberDiscount > 0) {//商品未设置会员价,但是运营后台设置了会员折扣
            $goodsDetail['shopPrice'] = $goodsInfo['shopPrice'];
            $goodsDetail['memberPrice'] = $goodsDetail['shopPrice'] * $memberDiscount;
            $goodsDetail['memberPrice'] = sprintfNumber($goodsDetail['memberPrice']);
            if (isset($lastGoodsDetail['cartId'])) {//如果是购物车商品
                $goodsDetail['memberPrice'] = $lastGoodsDetail['memberPrice'];
                $goodsDetail['shopPrice'] = $lastGoodsDetail['memberPrice'];
                $goodsDetail['init_price'] = $lastGoodsDetail['shopPrice'];
            }
        }
        if ($goodsDetail['hasGoodsSku'] != 1) {
            return $goodsDetail;
        }
        $skuList = $goodsDetail['goodsSku']['skuList'];
        if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
            $skuList = array($skuList);
        }
        foreach ($skuList as &$skuDetail) {
            $skuId = $skuDetail['skuId'];
            $skuInfo = $this->getSkuSystemInfoById($skuId, 2);
            if (empty($skuInfo)) {
                continue;
            }
            if ($skuInfo['skuMemberPrice'] > 0) {//商品设置了会员价
                $skuDetail['systemSpec']['skuShopPrice'] = $skuInfo['skuShopPrice'];
                $skuDetail['systemSpec']['skuMemberPrice'] = $skuInfo['skuMemberPrice'];
                if (isset($goodsDetail['cartId'])) {//购物车商品
                    $goodsDetail['shopPrice'] = $skuInfo['skuMemberPrice'];
                    $goodsDetail['init_price'] = $goodsDetail['shopPrice'];
                    $goodsDetail['memberPrice'] = $goodsDetail['shopPrice'];
                }
            }
            if ($skuInfo['skuMemberPrice'] <= 0 && $memberDiscount > 0) {//商品未设置会员价,但是运营后台设置了会员折扣
                $skuDetail['systemSpec']['skuShopPrice'] = $skuInfo['skuShopPrice'];
                $skuDetail['systemSpec']['skuMemberPrice'] = $skuDetail['systemSpec']['skuShopPrice'] * $memberDiscount;
                $skuDetail['systemSpec']['skuMemberPrice'] = sprintfNumber($skuDetail['systemSpec']['skuMemberPrice']);
                if (isset($goodsDetail['cartId'])) {//购物车商品
                    $goodsDetail['shopPrice'] = $skuDetail['systemSpec']['skuMemberPrice'];
                    $goodsDetail['init_price'] = $goodsDetail['shopPrice'];
                    $goodsDetail['memberPrice'] = $goodsDetail['shopPrice'];
                }
            }
        }
        unset($skuDetail);
        if (array_keys($goodsDetail['goodsSku']['skuList']) !== range(0, count($goodsDetail['goodsSku']['skuList']) - 1)) {
            $skuList = $skuList[0];
        }
        $goodsDetail['goodsSku']['skuList'] = $skuList;
        return $goodsDetail;
    }

    /**
     * 处理商品结果集-处理商品的会员价格 注:仅仅配合filterGoodsList方法使用
     * @param int $userId 用户id
     * @param array $goodsDetail 商品信息
     * @return array
     * */
    private function handleGoodsMemberPriceBatch(int $userId, &$goodsDetail = array())
    {
        if (empty($goodsDetail)) {
            return $goodsDetail;
        }
        $lastGoodsDetailList = [];
        foreach ($goodsDetail as $goodsDetailRow) {
            $uniKey = $goodsDetailRow['goodsId'] . "@" . $goodsDetailRow['skuId'];
            $lastGoodsDetailList[$uniKey] = $goodsDetailRow;
        }
        $isMember = 0;//是否是有效会员(0:非会员 1:有效会员)
        if ($userId > 0) {
            $user_detail = (new UsersModule())->getUsersDetailById($userId, 'userId,expireTime', 2);
            if ($user_detail['expireTime'] > date('Y-m-d H:i:s')) {
                $isMember = 1;
            }
        }
        if ($isMember != 1) {
            return $goodsDetail;
        }
        $goodsTab = M('goods');
        $systemTab = M('sku_goods_system');
        $goodsListMap = [];
        $goodsIdArr = array_column($goodsDetail, 'goodsId');
        $allGoodsList = $goodsTab->where(['goodsId' => array('in', $goodsIdArr)])->select();
        foreach ($allGoodsList as $allGoodsListRow) {
            $goodsListMap[$allGoodsListRow['goodsId']] = $allGoodsListRow;
        }
        $systemSpecListMap = [];
        $skuIdArr = array_column($goodsDetail, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        if (count($skuIdArr) > 0) {
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
        $goodsList = $goodsDetail;
        foreach ($goodsList as $key => $goodsListRow) {
            $uniKey = $goodsListRow['goodsId'] . "@" . $goodsListRow['skuId'];
            $lastGoodsDetail = $lastGoodsDetailList[$uniKey];
            $goodsId = $goodsListRow['goodsId'];
//            $goodsInfo = $this->getGoodsInfoById($goodsId, 'goodsId,shopPrice,memberPrice', 2);
            $goodsInfo = $goodsListMap[$goodsId];
            $memberDiscount = $GLOBALS["CONFIG"]["userDiscount"] / 100;//会员折扣
            if ($goodsInfo['memberPrice'] > 0) {//商品设置了会员价
                $goodsListRow['shopPrice'] = $goodsInfo['shopPrice'];
                $goodsListRow['memberPrice'] = $goodsInfo['memberPrice'];
                if (isset($lastGoodsDetail['cartId'])) {//如果是购物车商品
                    $goodsListRow['memberPrice'] = $lastGoodsDetail['memberPrice'];
                    $goodsListRow['shopPrice'] = $lastGoodsDetail['shopPrice'];
                    if ($lastGoodsDetail['memberPrice'] > 0) {
                        $goodsListRow['shopPrice'] = $lastGoodsDetail['memberPrice'];
                    }
                    $goodsListRow['init_price'] = $lastGoodsDetail['shopPrice'];
                }
            }
            if ($goodsInfo['memberPrice'] <= 0 && $memberDiscount > 0) {//商品未设置会员价,但是运营后台设置了会员折扣
                $goodsListRow['shopPrice'] = $goodsInfo['shopPrice'];
                $goodsListRow['memberPrice'] = $goodsListRow['shopPrice'] * $memberDiscount;
                $goodsListRow['memberPrice'] = sprintfNumber($goodsListRow['memberPrice']);
                if (isset($lastGoodsDetail['cartId'])) {//如果是购物车商品
                    $goodsListRow['memberPrice'] = $lastGoodsDetail['memberPrice'];
                    $goodsListRow['shopPrice'] = $lastGoodsDetail['memberPrice'];
                    $goodsListRow['init_price'] = $lastGoodsDetail['shopPrice'];
                }
            }
            if ($goodsListRow['hasGoodsSku'] != 1) {
                $goodsList[$key] = $goodsListRow;
                continue;
            }
            $skuList = $goodsListRow['goodsSku']['skuList'];
            if (array_keys($skuList) !== range(0, count($skuList) - 1)) {
                $skuList = array($skuList);
            }
            foreach ($skuList as &$skuDetail) {
                $skuId = $skuDetail['skuId'];
//                $skuInfo = $this->getSkuSystemInfoById($skuId, 2);
                $skuInfo = $systemSpecListMap[$skuId];
                if (empty($skuInfo)) {
                    continue;
                }
                if ($skuInfo['skuMemberPrice'] > 0) {//商品设置了会员价
                    $skuDetail['systemSpec']['skuShopPrice'] = $skuInfo['skuShopPrice'];
                    $skuDetail['systemSpec']['skuMemberPrice'] = $skuInfo['skuMemberPrice'];
                    if (isset($goodsListRow['cartId'])) {//购物车商品
                        $goodsListRow['shopPrice'] = $skuInfo['skuMemberPrice'];
                        $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
                        $goodsListRow['memberPrice'] = $goodsListRow['shopPrice'];
                    }
                }
                if ($skuInfo['skuMemberPrice'] <= 0 && $memberDiscount > 0) {//商品未设置会员价,但是运营后台设置了会员折扣
                    $skuDetail['systemSpec']['skuShopPrice'] = $skuInfo['skuShopPrice'];
                    $skuDetail['systemSpec']['skuMemberPrice'] = $skuDetail['systemSpec']['skuShopPrice'] * $memberDiscount;
                    $skuDetail['systemSpec']['skuMemberPrice'] = sprintfNumber($skuDetail['systemSpec']['skuMemberPrice']);
                    if (isset($goodsListRow['cartId'])) {//购物车商品
                        $goodsListRow['shopPrice'] = $skuDetail['systemSpec']['skuMemberPrice'];
                        $goodsListRow['init_price'] = $goodsListRow['shopPrice'];
                        $goodsListRow['memberPrice'] = $goodsListRow['shopPrice'];
                    }
                }
            }
            unset($skuDetail);
            if (array_keys($goodsListRow['goodsSku']['skuList']) !== range(0, count($goodsListRow['goodsSku']['skuList']) - 1)) {
                $skuList = $skuList[0];
            }
            $goodsListRow['goodsSku']['skuList'] = $skuList;
            $goodsList[$key] = $goodsListRow;
        }
        return $goodsList;
    }

    /**
     * @param $goodsId
     * @return mixed
     * 获取当前时间限时商品信息
     * @return array
     */
    function getGoodsFlashSale($goodsId)
    {
        $response = LogicResponse::getInstance();
        $where = array(
            'goodsId' => $goodsId
        );
        $goodsInfo = M('goods')
            ->where($where)
            ->field('goodsId,isFlashSale,goodsName')
            ->find();
        if ($goodsInfo['isFlashSale'] != 1) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('非限时商品')->toArray();
        }
        $where = array();
        //限时商品条件
        $where['gts.goodsId'] = $goodsId;
        $where['gts.dataFlag'] = 1;
        //限时时间段条件
        $where['wfs.state'] = 1;
        $where['wfs.isDelete'] = 0;
        $flashSaleGoods = M("goods_time_snapped gts")
            ->join("left join wst_flash_sale wfs on wfs.id = gts.flashSaleId")
            ->where($where)
            ->field("gts.*,wfs.id,wfs.startTime,wfs.endTime")
            ->group('gts.tsId')
            ->order('wfs.startTime desc')
            ->select();
        $toDay = date("Y-m-d ", time());
        $toDayTime = date("Y-m-d H:i", time());
        foreach ($flashSaleGoods as $key => $val) {
            if ($val['endTime'] == '00:00' && $val['startTime'] == '00:00') {
                return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($val)->toArray();
            }
            if ($val['endTime'] == '00:00') {
                return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($val)->toArray();
            }
            $startTime = $toDay . $val['startTime'];
            $endTime = $toDay . $val['endTime'];
            if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
                return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($val)->toArray();
            }
        }
        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('不在限购时间段范围')->toArray();
    }

    /**
     * 验证限量购商品是否满足条件 PS:属于原有逻辑后加,这里不验证用户购买库存,如需验证用户购买库存请调用原有公共函数goodsTimeLimit
     * @param int $users_id
     * @param int $goods_id 商品id
     * @param float $number 商品购买数量/重量
     * @return array
     * */
    public function verificationLimitGoodsBuyLog(int $users_id, int $goods_id, $number = 0, $debug = 2)
    {
        $response = LogicResponse::getInstance();
        $goods_result = $this->getGoodsInfoById($goods_id);
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('商品信息有误')->toArray();
        }
        $goods_info = $goods_result['data'];
        if ($goods_info['isLimitBuy'] != 1 && $goods_info['limitCount'] <= 0) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('非限量活动商品或限量活动库存不足')->toArray();
        }
        if ($goods_info['limit_daily'] > 0 && $users_id > 0) {
            if ($number > $goods_info['limit_daily']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('超出每日限购量')->toArray();
            }
            //需要验证每日限购量-start
            $today_date = date('Y-m-d');
            $start_date = strtotime($today_date . ' 00:00:00');
            $end_date = strtotime($today_date . ' 23:59:59');
            $limit_goods_buy_log_model = new LimitGoodsBuyLogModel();
            $where = array(
                'userId' => $users_id,
                'goodsId' => $goods_id,
                'is_delete' => 0,
                'create_time' => array('between', array($start_date, $end_date))
            );
            $log_result = (array)$limit_goods_buy_log_model->where($where)->find();
            if (!empty($log_result)) {
                if ($number > 0) {
                    $log_result['number'] = bc_math($log_result['number'], $number, 'bcadd', 0);
                }
                if ($log_result['number'] > $goods_info['limit_daily']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('超出每日限购量')->toArray();
                }
            }
            //需要验证每日限购量-end
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('限量购商品通过验证')->toArray();
    }

    /**
     * 根据商品id获取商品详情
     * @param int $goods_id 商品id
     * @param string $field 表字段
     * @param int $data_type 返回格式(1:data格式 2:直接返回结果集):PS:主要为了兼容之前的程序
     * @return array
     * */
    public function getGoodsInfoById(int $goods_id, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $goods_model = new GoodsModel();
        $where = array(
            'goodsId' => $goods_id,
            'goodsFlag' => 1,
        );
        $goods_info = (array)$goods_model->where($where)->field($field)->find();
        if (empty($goods_info)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($goods_info)->toArray();
        } else {
            return (array)$goods_info;
        }
    }

    /**
     * 根据参数获取商品详情
     * @param array $params <p>
     * int shopId 门店id
     * string goodsSn 商品编码
     * int goodsId 商品id
     * </p>
     * @param string $field 表字段
     * @param int $data_type 返回数据类型(1:data格式 2:直接返回结果集) PS:主要针对之前的程序
     * @return array
     * */
    public function getGoodsInfoByParams(array $params, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $goods_model = new GoodsModel();
        $where = array(
            'shopId' => null,
            'goodsId' => null,
            'goodsSn' => null,
            'goodsFlag' => 1,
        );
        parm_filter($where, $params);
        $goods_info = (array)$goods_model->where($where)->field($field)->find();
        if (empty($goods_info)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
            } else {
                return array();
            }
        }
        $shop_cats_module = new ShopCatsModule();
        if (!empty($goods_info['shopCatId1'])) {
            $goods_info['shop_catid1_name'] = $shop_cats_module->getShopCatInfoById($goods_info['shopCatId1'])['data']['catName'];
        }
        if (!empty($goods_info['shopCatId2'])) {
            $goods_info['shop_catid2_name'] = $shop_cats_module->getShopCatInfoById($goods_info['shopCatId2'])['data']['catName'];
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($goods_info)->toArray();
        } else {
            return (array)$goods_info;
        }
    }

    /**
     * 根据商品id获取商品列表
     * @param string $goods_id 多个商品id用英文逗号分隔
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsListById($goods_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        if (is_array($goods_id)) {
            $goods_id = implode(',', $goods_id);
        }
        $goods_model = new GoodsModel();
        $where = array(
            'goodsId' => array('IN', $goods_id),
            'goodsFlag' => 1,
        );
        $goods_list = (array)$goods_model->where($where)->field($field)->select();
        if (empty($goods_list)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($goods_list)->toArray();
    }

    /**
     * 添加限量购购买记录
     * @param int $users_id
     * @param array $params <p>
     * int goodsId 商品id
     * int number 购买数量
     * </p>
     * @param object $trans
     * @return array
     * */
    public function addLimitGoodsBuyLog(int $users_id, array $params, $trans = null)
    {
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $response = LogicResponse::getInstance();
        $goods_result = $this->getGoodsInfoById($params['goodsId']);
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('商品信息有误')->toArray();
        }
        $goods_info = $goods_result['data'];
        if ($goods_info['isLimitBuy'] != 1 || $goods_info['limitCount'] <= 0 || $goods_info['limit_daily'] <= 0) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('限量购商品未通过验证')->toArray();
        }
        $today_date = date('Y-m-d');
        $start_date = strtotime($today_date . ' 00:00:00');
        $end_date = strtotime($today_date . ' 23:59:59');
        $limit_goods_buy_log_model = new LimitGoodsBuyLogModel();
        $where = array(
            'userId' => $users_id,
            'goodsId' => $goods_info['goodsId'],
            'is_delete' => 0,
            'create_time' => array('between', array($start_date, $end_date))
        );
        $log_result = (array)$limit_goods_buy_log_model->where($where)->find();
        if (!empty($log_result)) {
            $limit_number = (float)bc_math($log_result['number'], $params['number'], 'bcadd');
            if ($limit_number > (float)$goods_info['limit_daily']) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('超出每日限购量')->toArray();
            }
            $save = array(
                'log_id' => $log_result['log_id'],
                'number' => $limit_number,
                'update_time' => time(),
            );
        } else {
            $limit_number = (float)$params['number'];
            $save = array(
                'userId' => $users_id,
                'goodsId' => $goods_info['goodsId'],
                'number' => $limit_number,
                'create_time' => time(),
            );
        }
        if (empty($log_result)) {
            $res = $limit_goods_buy_log_model->add($save);
        } else {
            $res = $limit_goods_buy_log_model->where(array('log_id' => $log_result['log_id']))->save($save);
        }
        if (!$res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户限购记录失败添加失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('限量购商品通过验证')->toArray();
    }

    /**
     * 返还商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param float $stock 库存
     * @param int $goods_type 需要扣除的库存类型(1:普通商品 2:限量商品 3:限时商品) PS：以用户最终下单的类型为准,不要和商品基本信息中的类型混为一谈
     * @param int $stock_type 库存类型(1:库房库存 2:售卖库存 3:库存库存和售卖库存)
     * @param object $trans
     * @return array
     * */
    public function returnGoodsStock(int $goods_id, int $sku_id, float $stock, $goods_type = 1, $stock_type = 1, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $goods_model = new GoodsModel();
        if ($goods_type == 1) {
            $stock_field = 'goodsStock';
            //最终认定为非活动商品
            if (empty($sku_id)) {
                if ($stock_type == 2) {
                    $stock_field = 'selling_stock';
                }
                $where = array(
                    'goodsId' => $goods_id
                );
                $res = $goods_model->where($where)->setInc($stock_field, $stock);
            } else {
                $stock_field = 'skuGoodsStock';
                if ($stock_type == 2) {
                    $stock_field = 'selling_stock';
                }
                $sku_model = new SkuGoodsSystemModel();
                $where = array(
                    'skuId' => $sku_id
                );
                $res = $sku_model->where($where)->setInc($stock_field, $stock);
            }
        } elseif ($goods_type == 2) {
            //最终认定为限量活动商品
            //限量活动商品不支持商品规格
            $where = array(
                'goodsId' => $goods_id
            );
            $res = $goods_model->where($where)->setInc('limitCount', $stock);
        } elseif ($goods_type == 3) {
            //最终认定为限时活动商品
            //限时活动商品不支持商品规格
            $goods_time_snapped_model = new GoodsTimeSnappedModel();
            $where = array(
                'goodsId' => $goods_id
            );
            $res = $goods_time_snapped_model->where($where)->setInc('activeInventory', $stock);
        }
        if (!$res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('库存修改失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }

    /**
     * 扣除商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param float $stock 库存
     * @param int $goods_type 需要扣除的库存类型(1:普通商品 2:限量商品 3:限时商品) PS：以用户最终下单的类型为准,不要和商品基本信息中的类型混为一谈
     * @param int $stock_type 库存类型(1:库房库存 2:售卖库存)
     * @return array
     * */
    public function deductionGoodsStock(int $goods_id, int $sku_id, float $stock, $goods_type = 1, $stock_type = 1, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $goods_model = new GoodsModel();
        if ($goods_type == 1) {
            $stock_field = 'goodsStock';//goodsStock,selling_stock,skuGoodsStock
            //最终认定为非活动商品
            if (empty($sku_id)) {
                //无规格
                $where = array(
                    'goodsId' => $goods_id
                );
                if ($stock_type == 2) {
                    $stock_field = 'selling_stock';
                }
                $res = $goods_model->where($where)->setDec($stock_field, $stock);
            } else {
                $stock_field = 'skuGoodsStock';
                if ($stock_type == 2) {
                    $stock_field = 'selling_stock';
                }
                //有规格
                $sku_model = new SkuGoodsSystemModel();
                $where = array(
                    'skuId' => $sku_id
                );
                $res = $sku_model->where($where)->setDec($stock_field, $stock);
            }
        } elseif ($goods_type == 2) {
            //最终认定为限量活动商品
            //限量活动商品不支持商品规格
            $where = array(
                'goodsId' => $goods_id
            );
            $res = $goods_model->where($where)->setDec('limitCount', $stock);
        } elseif ($goods_type == 3) {
            //最终认定为限时活动商品
            //限时活动商品不支持商品规格
            $goods_time_snapped_model = new GoodsTimeSnappedModel();
            $where = array(
                'goodsId' => $goods_id
            );
            $res = $goods_time_snapped_model->where($where)->setDec('activeInventory', $stock);
        }
        if (!$res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('库存更新失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }

    /**
     * 重置商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param float $stock 库存
     * @return array
     * */
    public function resetGoodsStock(int $goods_id, int $sku_id, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        if (empty($sku_id)) {
            $goods_model = new GoodsModel();
            $where = array(
                'goodsId' => $goods_id
            );
            $goods_result = $this->getGoodsInfoById($goods_id, 'goodsStock');
            $goods_info = $goods_result['data'];
            if ($goods_info['goodsStock'] < 0) {
                $save = array(
                    'goodsStock' => 0
                );
                $res = $goods_model->where($where)->save($save);
            } else {
                $res = true;
            }
        } else {
            $sku_model = new SkuGoodsSystemModel();
            $where = array(
                'skuId' => $sku_id
            );
            $sku_resut = $this->getSkuSystemInfoById($sku_id);
            $sku_info = $sku_resut['data'];
            if ($sku_info['skuGoodsStock'] < 0) {
                $save = array(
                    'skuGoodsStock' => 0
                );
                $res = $sku_model->where($where)->save($save);
            } else {
                $res = true;
            }
        }
        if (!$res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('库存修改失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }

    /**
     * 根据skuId获取sku详情
     * @param int $skuId
     * @param int $data_type (1:返回data格式 2:直接返回结果集) PS:主要是兼容之前的程序
     * @return array
     * */
    public function getSkuSystemInfoById(int $skuId, $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $sku_model = new SkuGoodsSystemModel();
        $where = array(
            'skuId' => $skuId,
            'dataFlag' => 1
        );
        $system_info = $sku_model->where($where)->find();
        if (empty($system_info)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("skuId:{$skuId}暂无相关数据")->toArray();
            } else {
                return array();
            }
        }
        foreach ($system_info as $k => $v) {
            if (in_array($k, ['dataFlag', 'addTime', 'minBuyNum'])) {
                continue;
            }
            if ((int)$v == -1) {
                $system_info[$k] = '';
            }
        }
        $system_info['skuSpecAttr'] = '';
        $system_info['skuSpecAttrTwo'] = '';
        $sku_self_model = new SkuGoodsSelfModel();
        $where = array(
            'se.skuId' => $skuId,
            'se.dataFlag' => 1,
            'sp.dataFlag' => 1,
            'sr.dataFlag' => 1
        );
        $self_list = $sku_self_model->alias('se')->where($where)
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where($where)
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
            ->order('sp.sort asc')
            ->select();
        $system_info['self_list'] = $self_list;
        if (!empty($self_list)) {
            foreach ($self_list as $val) {
                $system_info['skuSpecAttr'] .= $val['attrName'] . ",";
                $system_info['skuSpecAttrTwo'] .= $val['specName'] . '#' . $val['attrName'] . ",";
            }
        }
        $system_info['skuSpecAttr'] = rtrim($system_info['skuSpecAttr'], ',');
        $system_info['skuSpecAttrTwo'] = rtrim($system_info['skuSpecAttrTwo'], ',');
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($system_info)->setMsg('成功')->toArray();
        } else {
            return (array)$system_info;
        }

    }

    /**
     * 多条件获取sku详情
     * @param array<p>
     * int skuId skuId
     * int goodsId 商品id
     * string skuBarcode sku编码
     * </p>
     * @return array
     * */
    public function getSkuSystemInfoParams(array $params, $dataType = 1)
    {
        $response = LogicResponse::getInstance();
        $sku_model = new SkuGoodsSystemModel();
        $where = array(
            'skuId' => null,
            'goodsId' => null,
            'skuBarcode' => null,
            'dataFlag' => 1,
        );
        parm_filter($where, $params);
        $system_info = $sku_model->where($where)->find();
        if (empty($system_info)) {
            if ($dataType == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("暂无相关数据")->toArray();
            } else {
                return array();
            }
        }
        foreach ($system_info as $k => $v) {
            if (in_array($k, ['dataFlag', 'addTime', 'minBuyNum'])) {
                continue;
            }
            if ((int)$v == -1) {
                $system_info[$k] = '';
            }
        }
        $system_info['skuSpecAttr'] = '';
        $sku_self_model = new SkuGoodsSelfModel();
        $where = array(
            'se.skuId' => $system_info['skuId'],
            'se.dataFlag' => 1,
            'sp.dataFlag' => 1,
            'sr.dataFlag' => 1
        );
        $self_list = $sku_self_model->alias('se')->where($where)
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where($where)
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
            ->order('sp.sort asc')
            ->select();
        $system_info['self_list'] = $self_list;
        if (!empty($self_list)) {
            foreach ($self_list as $val) {
                $system_info['skuSpecAttr'] .= $val['attrName'] . ",";
            }
        }
        $system_info['skuSpecAttr'] = rtrim($system_info['skuSpecAttr'], ',');
        if ($dataType == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($system_info)->setMsg('成功')->toArray();
        } else {
            return $system_info;
        }
    }

    /**
     * @param int $goodsId
     * @return array
     * 根据商品id获取店铺商品分类和商城分类
     */
    public function getGoodsCat(int $goodsId)
    {
        $response = LogicResponse::getInstance();
        $goodsModel = new GoodsModel();

        $where = [];
        $where['g.goodsId'] = $goodsId;
        $where['goodsFlag'] = 1;

        $field = "ws.shopName,g.*,sc.catName as shopCatId1Name,scg.catName as shopCatId2Name,gc.catName as goodsCatId1Name,wgc.catName as goodsCatId2Name,gcw.catName as goodsCatId3Name";
        $goods_info = (array)$goodsModel->alias('g')
            ->join('left join wst_shops_cats sc on sc.catId = g.shopCatId1')
            ->join('left join wst_shops_cats scg on scg.catId = g.shopCatId2')
            ->join('left join wst_goods_cats gc on gc.catId = g.goodsCatId1')
            ->join('left join wst_goods_cats wgc on wgc.catId = g.goodsCatId2')
            ->join('left join wst_goods_cats gcw on gcw.catId = g.goodsCatId3')
            ->join('left join wst_shops ws on ws.shopId = g.shopId')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($goods_info)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($goods_info)->toArray();
    }

    /**
     * @param string $shopCatIds
     * @return array
     * 根据店铺商品分类ID获取分类列表
     */
    public function getShopCatList(string $shopCatIds)
    {
        $shopsCatModel = new ShopsCatModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['catId'] = ['IN', $shopCatIds];
        $shopCatList = $shopsCatModel->where($where)->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($shopCatList)->setMsg('成功')->toArray();
    }

    /**
     * @param string $goodsCatIds
     * @return array
     * 根据商品分类ID获取分类列表
     */
    public function getGoodsCatList(string $goodsCatIds)
    {
        $goodsCatModel = new GoodsCatModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['catId'] = ['IN', $goodsCatIds];
        $goodsCatIdList = $goodsCatModel->where($where)->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($goodsCatIdList)->setMsg('成功')->toArray();
    }

    /**
     * @param int $pid
     * @return array
     * 根据商品分类pid获取分类列表
     */
    public function getGoodsCatListPid(int $pid)
    {
        $goodsCatModel = new GoodsCatModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['parentId'] = $pid;
        $where['catFlag'] = 1;
        $goodsCatIdList = $goodsCatModel->where($where)->order('catSort desc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($goodsCatIdList)->setMsg('成功')->toArray();
    }

    /**
     * @param int $goodsId
     * @return array
     * 根据商品ID获取商品相册
     */
    public function getGoodsGalleryList(int $goodsId)
    {
        $goodsGalleryModel = new GoodsGalleryModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsGalleryList = $goodsGalleryModel->where($where)->order('id asc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($goodsGalleryList)->setMsg('成功')->toArray();
    }

    /**
     * 商品sku列表-商品id获取
     * @param int $goodsId 商品id
     * @param int $dataType 返回格式(1:返回处理过后的数据 2:直接返回数据) 注:该参数后加,兼容
     * @return array
     */
    public function getGoodsSku(int $goodsId, $dataType = 1)
    {
        $responsert = LogicResponse::getInstance();
        $skuGoodsSystemModel = new SkuGoodsSystemModel();
        $homeGoodsSkuModel = new GoodsSkuModel();
        $sysWhere = [];
        $sysWhere ['dataFlag'] = 1;
        $sysWhere ['goodsId'] = $goodsId;
        $systemSpec = $skuGoodsSystemModel->where($sysWhere)->order('skuId asc')->select();
        $systemSpec = $homeGoodsSkuModel->returnSystemSkuValue($systemSpec);
        $response = [];
        if ($systemSpec) {
            foreach ($systemSpec as $value) {
                $spec = [];
                $spec['skuId'] = $value['skuId'];
                $spec['systemSpec']['skuShopPrice'] = $value['skuShopPrice'];
                $spec['systemSpec']['skuMemberPrice'] = $value['skuMemberPrice'];
                $spec['systemSpec']['skuGoodsStock'] = $value['skuGoodsStock'];
                $spec['systemSpec']['skuGoodsImg'] = $value['skuGoodsImg'];
                $spec['systemSpec']['skuBarcode'] = $value['skuBarcode'];
                $spec['systemSpec']['skuMarketPrice'] = $value['skuMarketPrice'];
                $spec['systemSpec']['minBuyNum'] = $value['minBuyNum'];
                $spec['systemSpec']['WeighingOrNot'] = $value['WeighingOrNot'];
                $spec['systemSpec']['weigetG'] = $value['weigetG'];
                $spec['systemSpec']['unit'] = $value['unit'];
                $spec['systemSpec']['purchase_price'] = $value['purchase_price'];
                $spec['systemSpec']['UnitPrice'] = $value['UnitPrice'];

                $spec['selfSpec'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId = sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId = se.attrId")
                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
                    ->order('sp.sort asc')
                    ->select();
                if (empty($spec['selfSpec'])) {
                    continue;
                }
                $spec['specAttrNameStr'] = '';
                $spec['specAttrNameStrTwo'] = '';
                foreach ($spec['selfSpec'] as $selfVal) {
                    $spec['specAttrNameStr'] .= $selfVal['attrName'] . ',';
                    $spec['specAttrNameStrTwo'] .= "{$selfVal['specName']}#{$selfVal['attrName']},";
                }
                $spec['specAttrNameStr'] = rtrim($spec['specAttrNameStr'], ',');
                $spec['specAttrNameStrTwo'] = rtrim($spec['specAttrNameStrTwo'], ',');
                $response[] = $spec;
            }
        }
        if ($dataType == 1) {
            return $responsert->setCode(ExceptionCodeEnum::SUCCESS)->setData($response)->setMsg('成功')->toArray();
        } else {
            return (array)$response;
        }

    }

    /**
     * @param $param
     * @return array
     * 根据商城分类ID修改信息
     */
    public function editGoodsCatInfo($param)
    {
        $catId = $param['catId'];
        unset($param['catId']);
        $goodsCatModel = new GoodsCatModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['catId'] = $catId;
        $where['catFlag'] = 1;
        $editGoodsCatInfo = $goodsCatModel->where($where)->save($param);
        if ($editGoodsCatInfo !== false) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((bool)$editGoodsCatInfo)->setMsg('成功')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('变更数据失败,请重新尝试')->toArray();
    }

    /**
     * @param int $catId
     * @return array
     * 获取商城分类信息
     */
    public function getGoodsCatInfo(int $catId)
    {
        $goodsCatModel = new GoodsCatModel();
        $response = LogicResponse::getInstance();
        $where = [];
        $where['catId'] = $catId;
        $where['catFlag'] = 1;
        $getGoodsCatInfo = $goodsCatModel->where($where)->find();
        if (empty($getGoodsCatInfo)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($getGoodsCatInfo)->setMsg('成功')->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据商品id更新数据
     */
    public function editGoodsInfo($params)
    {
        $response = LogicResponse::getInstance();
        $goods_model = new GoodsModel();
        $where = array(
            'goodsId' => null,
            'shopId' => null,
            'goodsFlag' => 1,
        );
        parm_filter($where, $params);
        unset($params['goodsId'], $params['shopId']);
        $editGoodsInfo = $goods_model->where($where)->save($params);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($editGoodsInfo)->setMsg('成功')->toArray();
    }

    /**
     * 根据参数获取sku详情
     * @param array $params <p>
     * int skuId skuId
     * int goodsId 商品id
     * string skuBarcode sku编码
     * </p>
     * @return array
     * */
    public function getSkuSystemInfoByParams(array $params)
    {
        $response = LogicResponse::getInstance();
        $sku_model = new SkuGoodsSystemModel();
        $where = array(
            'skuId' => null,
            'goodsId' => null,
            'skuBarcode' => null,
            'dataFlag' => 1
        );
        parm_filter($where, $params);
        $system_info = $sku_model->where($where)->find();
        if (empty($system_info)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("暂无相关数据")->toArray();
        }
        $system_info['skuSpecAttr'] = '';
        $sku_self_model = new SkuGoodsSelfModel();
        $skuId = $system_info['skuId'];
        $where = array(
            'se.skuId' => $skuId,
            'se.dataFlag' => 1,
            'sp.dataFlag' => 1,
            'sr.dataFlag' => 1
        );
        $self_list = $sku_self_model->alias('se')->where($where)
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where($where)
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
            ->order('sp.sort asc')
            ->select();
        $system_info['self_list'] = $self_list;
        if (!empty($self_list)) {
            foreach ($self_list as $val) {
                $system_info['skuSpecAttr'] .= $val['attrName'] . ",";
            }
        }
        $system_info['skuSpecAttr'] = rtrim($system_info['skuSpecAttr'], ',');
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($system_info)->setMsg('成功')->toArray();
    }

    /**
     * 根据skuId获取sku列表
     * @param string $skuId 多个skuId用英文逗号分隔
     * @param int $dataType
     * @return array
     * */
    public function getSkuSystemListById($skuId, $dataType = 1)
    {
        if (is_array($skuId)) {
            $skuId = implode(',', $skuId);
        }
        $response = LogicResponse::getInstance();
        $sku_model = new SkuGoodsSystemModel();
        $where = array(
            'skuId' => array('IN', $skuId),
            'dataFlag' => 1
        );
        $list = $sku_model->where($where)->select();
        if (empty($list)) {
            if ($dataType == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("skuId:{$skuId}暂无相关数据")->toArray();
            } else {
                return array();
            }

        }
        $sku_self_model = new SkuGoodsSelfModel();
        foreach ($list as &$item) {
            $item['skuSpecAttr'] = '';
            $where = array(
                'se.skuId' => $item['skuId'],
                'se.dataFlag' => 1,
                'sp.dataFlag' => 1,
                'sr.dataFlag' => 1
            );
            $self_list = $sku_self_model->alias('se')->where($where)
                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                ->where($where)
                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                ->order('sp.sort asc')
                ->select();
            $item['self_list'] = $self_list;
            if (!empty($self_list)) {
                foreach ($self_list as $val) {
                    $item['skuSpecAttr'] .= $val['attrName'] . ",";
                    $item['skuSpecAttrTwo'] .= "{$val['specName']}#{$val['attrName']},";
                }
            }
            $item['skuSpecAttr'] = rtrim($item['skuSpecAttr'], ',');
            $item['skuSpecAttrTwo'] = rtrim($item['skuSpecAttrTwo'], ',');
        }
        unset($item);
        if ($dataType == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($list)->setMsg('成功')->toArray();
        } else {
            return $list;
        }
    }

    /**
     * 扫码获取商品详情
     * @param array $params <p>
     * int shop_id 门店id
     * string $code 商品编码
     * </p>
     * @return array
     * */
    public function searchGoodsInfoByCode(int $shop_id, string $code)
    {
        $response = LogicResponse::getInstance();
        $goods_model = new GoodsModel();
        $system_model = new SkuGoodsSystemModel();
        //处理称重条码-start
        $code_arr = explode('CZ-', $code);
        if (count($code_arr) == 2) {
            //称重条码
            $goods_module = new GoodsModule();
            $barcode_result = $goods_module->getBarcodeInfoByCode($shop_id, $code);
            if ($barcode_result['code'] == ExceptionCodeEnum::SUCCESS) {
                $barcode_info = $barcode_result['data'];
                if ($barcode_info['skuId'] > 0) {
                    $sku_system_result = $goods_module->getSkuSystemInfoById($barcode_info['skuId']);
                    $code = $sku_system_result['data']['skuBarcode'];
                } else {
                    $goods_result = $goods_module->getGoodsInfoById($barcode_info['goodsId']);
                    $code = $goods_result['data']['goodsSn'];
                }
            }
        }
        //处理称重条码-end
        $where = array();
        $where['goods.isBecyclebin'] = 0;
        $where['goods.goodsFlag'] = 1;
        $where['goods.shopId'] = $shop_id;
        $where['system.dataFlag'] = 1;
        $where['system.skuBarcode'] = $code;
        $field = 'goods.goodsId as goods_id,goods.goodsSn as goods_sn,goods.goodsName as goods_name,goods.shopPrice as old_sale_price,goods.goodsUnit as old_purchase_price,goods.shopCatId1 as shop_catid1,goods.shopCatId2 as shop_catid2,goods.goodsStock as goods_stock,goods.SuppPriceDiff,goods.weightG';
        $field .= ',system.skuId as sku_id,system.skuBarcode,system.skuShopPrice,system.skuMemberPrice,system.skuGoodsStock,system.skuMarketPrice,system.skuGoodsImg,system.skuBarcode,system.minBuyNum,system.weigetG,system.WeighingOrNot,system.UnitPrice,system.unit';
        $system_info = $system_model
            ->alias('system')
            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
            ->where($where)
            ->field($field)
            ->find();
        $data = array();
        if (!empty($system_info)) {
            $system_info['sku_spec_attr'] = '';
            $sku_self_model = new SkuGoodsSelfModel();
            $sku_id = $system_info['sku_id'];
            $where = array(
                'se.skuId' => $sku_id,
                'se.dataFlag' => 1,
                'sp.dataFlag' => 1,
                'sr.dataFlag' => 1
            );
            $self_list = $sku_self_model->alias('se')->where($where)
                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                ->where($where)
                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                ->order('sp.sort asc')
                ->select();
            $system_info['self_list'] = $self_list;
            if (!empty($self_list)) {
                foreach ($self_list as $val) {
                    $system_info['sku_spec_attr'] .= $val['attrName'] . ",";
                }
            }
            $system_info['sku_spec_attr'] = rtrim($system_info['sku_spec_attr'], ',');
            $data = $system_info;
        }
        if (empty($system_info)) {
            $field = 'goods.goodsId as goods_id,goods.goodsSn as goods_sn,goods.goodsName as goods_name,goods.shopPrice as old_sale_price,goods.goodsUnit as old_purchase_price,goods.shopCatId1 as shop_catid1,goods.shopCatId2 as shop_catid2,goods.goodsStock as goods_stock,goods.SuppPriceDiff,goods.weightG';
            $where = array();
            $where['goods.isBecyclebin'] = 0;
            $where['goods.shopId'] = $shop_id;
            $where['goodsSn'] = $code;
            $where['goodsFlag'] = 1;
            $goods_info = $goods_model->alias('goods')->where($where)->field($field)->find();
            if (empty($goods_info)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未找到该编码商品')->toArray();
            }
            $goods_info['sku_id'] = 0;
            $goods_info['sku_spec_attr'] = '';
            $data = $goods_info;
        }
        $shop_cats_moduel = new ShopCatsModule();
        $data['shop_catid1_name'] = $shop_cats_moduel->getShopCatInfoById($data['shop_catid1'])['data']['catName'];
        $data['shop_catid2_name'] = $shop_cats_moduel->getShopCatInfoById($data['shop_catid2'])['data']['catName'];
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }

    /**
     * 根据称重条码获取称重条码详情
     * @param int $shop_id 门店id
     * @param string $code 称重条码
     * @param string $field 表字段
     * @return array
     * */
    public function getBarcodeInfoByCode(int $shop_id, string $code, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new BarcodeModel();
        $where = array(
            'shopId' => $shop_id,
            'barcode' => $code,
            'bFlag' => 1,
        );
        $goods_info = (array)$model->where($where)->field($field)->find();
        if (empty($goods_info)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未找到该条码')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($goods_info)->toArray();
    }

    /**
     * 添加商品日志
     * @param array $actionUserInfo
     * @param array $primaryGoodsInfo 原商品信息
     * @param string remark 操作描述
     * @param object $trans
     * @return int $logId
     * */
    public function addGoodsLog(array $actionUserInfo, array $primaryGoodsInfo, $remark = '', $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($primaryGoodsInfo['goodsId'])) {
            $m->rollback();
            return 0;
        }
        $tab = new GoodsLogModel();
        $goodsTab = new GoodsModel();
        $where = [];
        $where['goodsId'] = $primaryGoodsInfo['goodsId'];
        $nowGoodsInfo = $goodsTab->where($where)->find();
        if (empty($nowGoodsInfo)) {
            $m->rollback();
            return false;
        }
        $data = [
            'goodsId' => (int)$nowGoodsInfo['goodsId'],
            'primaryShopPrice' => (float)$primaryGoodsInfo['shopPrice'],
            'nowShopPrice' => (float)$nowGoodsInfo['shopPrice'],
            'primaryMemberPrice' => (float)$primaryGoodsInfo['memberPrice'],
            'nowMemberPrice' => (float)$nowGoodsInfo['memberPrice'],
            'primaryBuyPrice' => (float)$primaryGoodsInfo['goodsUnit'],
            'nowBuyPrice' => (float)$nowGoodsInfo['goodsUnit'],
            'primaryIntegralReward' => (float)$primaryGoodsInfo['integralReward'],
            'nowIntegralReward' => (int)$nowGoodsInfo['integralReward'],
            'primaryGoodsStock' => (float)$primaryGoodsInfo['goodsStock'],
            'nowGoodsStock' => (float)$nowGoodsInfo['goodsStock'],
            'primarySaleStatus' => (int)$primaryGoodsInfo['isSale'],
            'nowSaleStatus' => (int)$nowGoodsInfo['isSale'],
            'primaryGoodsStatus' => (float)$primaryGoodsInfo['goodsStatus'],
            'nowGoodsStatus' => (int)$nowGoodsInfo['goodsStatus'],
            'actionUserId' => (int)$actionUserInfo['user_id'],
            'actionUserName' => (string)$actionUserInfo['user_username'],
            'createTime' => date('Y-m-d H:i:s'),
            'remark' => $remark,
        ];
        $insertId = $tab->add($data);
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$insertId;
    }

    /**
     * 增加商品销量
     * @param int $goods_id 商品id
     * @param int $sku_id 规格id
     * @param float $goods_cnt 购买数量(同购物车中的goodsCnt字段)
     * @param int $goods_type 商品的类型(1:普通商品 2:限量商品 3:限时商品) PS:订单商品表的goods_type字段
     * @param object $trans
     * @return bool
     * */
    public function IncGoodsSale(int $goods_id, int $sku_id, float $goods_cnt, $goods_type = 1, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $goods_model = new GoodsModel();
        $goods_time_snapped_model = new GoodsTimeSnappedModel();
        $where = array(
            'goodsId' => $goods_id
        );
        $res = $goods_model->where($where)->setInc('saleCount', $goods_cnt);//限量活动已售和普通商品已售用的是同一个字段
        if ($goods_type == 3) {
            //最终认定为限时活动商品
            //限时活动商品不支持商品规格
            $where = array(
                'goodsId' => $goods_id
            );
            $res = $goods_time_snapped_model->where($where)->setInc('salesInventory', $goods_cnt);
        }
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
     * 重置商品价格 PS:使用场景:当商品存在其他属性需要替换商品原有的价格时可以使用该方法
     * @param int $user_id 用户id
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param int $goods_attr_id 属性id PS:现在已废弃
     * @param float $goods_cnt 购买数量
     * @param array $last_goods_val 上个节点操作的商品信息,主要用于替换一些商品属性,非必填
     * @return float
     * */
    public function replaceGoodsPrice(int $user_id, int $goods_id, int $sku_id, $goods_cnt = 1, &$last_goods_val = array(), $debug = 2)
    {
        if (empty($goods_id)) {
            return 0;
        }
//        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale,goodsStock,selling_stock';
//        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
//        if (empty($goods_detail)) {
//            return 0;
//        }
//        if (!empty($last_goods_val)) {
//            $last_goods_val['goodsStock'] = $goods_detail['selling_stock'];
//        }
//        $is_limit_buy = $goods_detail['isLimitBuy'];//商品是否参与了限量活动
//        $is_flash_sale = $goods_detail['isFlashSale'];//商品是否参与了限时活动
//        $users_module = new UsersModule();
//        $field = 'userId,expireTime';
//        //普通商品-start
//        $users_detail = $users_module->getUsersDetailById($user_id, $field, 2);
//        $user_discount = (float)$GLOBALS["CONFIG"]["userDiscount"] / 100;//会员折扣
//        $replace_price = (float)$goods_detail['shopPrice'];
//        if ($users_detail['expireTime'] > date('Y-m-d H:i:s')) {
//            if ($goods_detail['memberPrice'] > 0) {
//                $replace_price = (float)$goods_detail['memberPrice'];
//            } elseif ($user_discount > 0) {
//                $replace_price = sprintfNumber($replace_price * $user_discount);
//            }
//        }
//        //普通商品-end
//        //sku规格商品-start
//        if ($sku_id > 0) {//替换为sku价格
//            $sku_system_detail = $this->getSkuSystemInfoById($sku_id, 2);
//            if (!empty($last_goods_val)) {
//                $last_goods_val['goodsStock'] = $sku_system_detail['selling_stock'];
//            }
//            $replace_price = (float)$sku_system_detail['skuShopPrice'];
//            if ($users_detail['expireTime'] > date('Y-m-d H:i:s')) {
//                if ($sku_system_detail['skuMemberPrice'] > 0) {
//                    $replace_price = (float)$sku_system_detail['skuMemberPrice'];
//                } elseif ($user_discount > 0) {
//                    $replace_price = sprintfNumber($replace_price * $user_discount);
//                }
//            }
//        }
//        //sku规格商品-end
//        //限时限量商品-start
//        if ($is_flash_sale == 1 || $is_limit_buy == 1) {
//            $replace_price = $this->repalceActivityGoodsPrice($goods_id, $goods_cnt, $replace_price, $last_goods_val);
//        }
//        if (!empty($last_goods_val)) {
//            $last_goods_val['shopPrice'] = $replace_price;
//        }
//        //限时限量商品-end
//        return (float)$replace_price;
        $this->filterGoods($last_goods_val, $debug);
        return $last_goods_val['shopPrice'];
    }

    /**
     * 获取商品属性价格 PS:该方法拷贝函数库的方法getGoodsAttrPrice
     * @param int $user_id 用户id
     * @param int $goods_id 商品id
     * @param int $goods_attr_id 属性id
     * @return array
     * */
    function getGoodsAttrPrice(int $user_id, int $goods_id, int $goods_attr_id)
    {
        $attr_name = '';//属性名称
        $init_price = 0;//初始化金额
        $field = 'goodsId,goodsName,shopPrice,shopId';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        $shop_id = $goods_detail['shopId'];
        $rank_user_model = new RankUserModel();
        $user_rank_detail = $rank_user_model->where(array(
            'userId' => $user_id,
            'state' => 1
        ))->find();
        $rank_model = new RankModel();
        $rank_detail = $rank_model->where(array(
            'rankId' => $user_rank_detail['rankId'],
            'state' => 1
        ))->find();
        $goods_rank_model = new RankGoodsModel();
        $response = [];
        $response['goodsAttrName'] = '';
        $response['goods_price'] = 0;
        $goods_attributes_model = new GoodsAttributesModel();
        $attributes_model = new AttributesModel();
        if ($goods_attr_id > 0) {
            $children = $goods_attributes_model
                ->where("id IN($goods_attr_id) AND goodsId={$goods_id}")
                ->select();
            if ($children) {
                $attrGoodsRanks = $goods_rank_model
                    ->where("goodsId={$goods_id} AND rankId={$user_rank_detail['rankId']} AND attributesID IS NULL")
                    ->select();
                foreach ($attrGoodsRanks as $ak => $av) {
                    if ($rank_detail['shopId'] == $shop_id) {
                        if ($av['rankId'] == $user_rank_detail['rankId']) {
                            $init_price = $av['price'];
                        }
                    }
                }
                foreach ($children as $ck => $cv) {
                    $attr_name .= $cv['attrVal'] . " ";
                    $attrGoodsRanks = $goods_rank_model->where("goodsId={$goods_id} AND attributesID={$cv['id']}")->select();
                    $parentAttrInfo = $attributes_model->where("attrId = {$cv['attrId']}")->find();
                    if (!$attrGoodsRanks) {
                        if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && !$attrGoodsRanks) {
                            $init_price = $cv['attrPrice'];
                        } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
                            $init_price += $cv['attrPrice'];
                        }
                    } else {
                        if ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 1 && $attrGoodsRanks) {
                            $init_price = $cv['attrPrice'];
                        } elseif ($parentAttrInfo['attrId'] == $cv['attrId'] && $parentAttrInfo['isPriceAttr'] == 2 && !$attrGoodsRanks) {
                            $init_price += $cv['attrPrice'];
                        }
                        if ($shop_id == $rank_detail['shopId'] && $attrGoodsRanks) {
                            foreach ($attrGoodsRanks as $ak => $av) {
                                if ($rank_detail['shopId'] == $shop_id) {
                                    if ($av['rankId'] == $user_rank_detail['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 1) {
                                        //普通价格属性
                                        $init_price = $av['price'];
                                    } elseif ($av['rankId'] == $user_rank_detail['rankId'] && $cv['id'] == $av['attributesID'] && $parentAttrInfo['isPriceAttr'] == 2) {
                                        //叠加价格属性
                                        $init_price += $av['price'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return array(
                'replace_price' => (float)$init_price,
                'attr_name' => $attr_name
            );
        } else {
            $attrGoodsRanks = $goods_rank_model->where("goodsId ={$goods_id} AND rankId = {$user_rank_detail['rankId']} AND attributesID IS NULL")->select();
            foreach ($attrGoodsRanks as $ak => $av) {
                if ($rank_detail['shopId'] == $shop_id) {
                    if ($av['rankId'] == $user_rank_detail['rankId']) {
                        $init_price = $av['price'];
                        return array(
                            'replace_price' => (float)$init_price,
                            'attr_name' => $attr_name,
                        );
                    }
                }
            }
        }
    }

    /**
     * 获取限时或限量商品的价格
     * @param int $goods_id 商品id
     * @param float $goods_cnt 购买数量
     * @param float $last_price 上级计算的价格
     * @param array $last_goods_val 上个节点操作的商品信息,主要用于替换一些商品属性值,非必填
     * @return float
     */
    public function repalceActivityGoodsPrice(int $goods_id, float $goods_cnt, $last_price, &$last_goods_val = array())
    {
        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale,limitCountActivityPrice,limitCount,goodsStock,selling_stock';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            return $last_price;
        }
        if (!empty($last_goods_val)) {
            $last_goods_val['goods_type'] = 1;//购买时商品的类型 1:普通商品 2:限量商品 3:限时商品
            $last_goods_val['goodsStock'] = $goods_detail['selling_stock'];
        }
        $replace_price = $last_price;//如果活动检验不通过直接返回上级计算的价格
        if ($goods_detail['isLimitBuy'] == 1) {//限量购
            $verification_time_limit_res = goodsTimeLimit($goods_id, null, $goods_cnt);
            if (empty($verification_time_limit_res)) {
                $replace_price = $goods_detail["limitCountActivityPrice"];
                if (!empty($last_goods_val)) {
                    $last_goods_val['goods_type'] = 2;
                    $last_goods_val['goodsStock'] = $goods_detail['limitCount'];
                }
            }
        }
        if ($goods_detail['isFlashSale'] == 1) {//限时购
            $verificaton_goods_flash_res = getGoodsFlashSale($goods_id);
            if ($verificaton_goods_flash_res['code'] == -1) {//不是在限时时间段内,直接返回原数据
                return $last_price;
            }
            $verification_flash_sale_res = goodsTimeLimit($goods_id, $verificaton_goods_flash_res, $goods_cnt, 1);
            if (empty($verification_flash_sale_res)) {
                $replace_price = $verificaton_goods_flash_res["activityPrice"];
                if (!empty($last_goods_val)) {
                    $last_goods_val['goods_type'] = 3;
                    $last_goods_val['goodsStock'] = $verificaton_goods_flash_res['activeInventory'];
                }
            }
        }
        return (float)$replace_price;
    }

    /**
     * 获取商品的会员价
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @return float
     * */
    public function getGoodsMemberPrice(int $goods_id, $sku_id = 0)
    {
        if (empty($goods_id)) {
            return 0;
        }
        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            return 0;
        }
        $shop_price = (float)$goods_detail['shopPrice'];
        $init_price = 0;
        $configs = $GLOBALS["CONFIG"];
        $user_discount = (float)bc_math($configs['userDiscount'], 100, 'bcdiv', 2);//会员折扣
        if ($goods_detail['memberPrice'] > 0) {
            $init_price = (float)$goods_detail['memberPrice'];
        } elseif ($user_discount > 0) {
            $init_price = bc_math($shop_price, $user_discount, 'bcmul', 2);
        }
        if ($sku_id > 0) {//替换为sku价格
            $sku_system_detail = $this->getSkuSystemInfoById($sku_id, 2);
            $sku_shop_price = (float)$sku_system_detail['skuShopPrice'];
            $init_price = 0;
            if ($sku_system_detail['skuMemberPrice'] > 0) {
                $init_price = (float)$sku_system_detail['skuMemberPrice'];
            } elseif ($user_discount > 0) {
                $init_price = bc_math($sku_shop_price, $user_discount, 'bcmul', 2);
            }
        }
        return (float)$init_price;
    }

    /**
     * 获取商品的会员价
     * @param array $goods_row 商品信息
     * @param array $sku_row sku信息
     * @return float
     * */
    public function getGoodsMemberPriceNew(array $goods_row, array $sku_row)
    {
        if (empty($goods_row)) {
            return 0;
        }
//        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale';
//        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        $goods_detail = $goods_row;
        if (empty($goods_detail)) {
            return 0;
        }
        $shop_price = (float)$goods_detail['shopPrice'];
        $init_price = 0;
        $configs = $GLOBALS["CONFIG"];
        $user_discount = (float)bc_math($configs['userDiscount'], 100, 'bcdiv', 2);//会员折扣
        if ($goods_detail['memberPrice'] > 0) {
            $init_price = (float)$goods_detail['memberPrice'];
        } elseif ($user_discount > 0) {
            $init_price = bc_math($shop_price, $user_discount, 'bcmul', 2);
        }
        if (!empty($sku_row)) {//替换为sku价格
//            $sku_system_detail = $this->getSkuSystemInfoById($sku_id, 2);
            $sku_shop_price = (float)$sku_row['skuShopPrice'];
            $init_price = 0;
            if ($sku_row['skuMemberPrice'] > 0) {
                $init_price = (float)$sku_row['skuMemberPrice'];
            } elseif ($user_discount > 0) {
                $init_price = bc_math($sku_shop_price, $user_discount, 'bcmul', 2);
            }
        }
        return (float)$init_price;
    }

    /**
     * 根据商品列表返回拆单信息
     * @param array $goods_list 订单商品信息(需要包含购goodsCnt字段)
     * @param int $cuid 用户领取的优惠券记录id PS:如果该参数有值则判断该用户优惠券是否可用
     * @return array
     * */
    public function getGoodsSplitOrder(array $goods_list, $cuid = 0)
    {
        $coupon_module = new CouponsModule();
        $users_id = 0;
        if ($cuid > 0) {
            $user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
            $users_id = $user_coupon_detail['userId'];
            $coupon_detail = $coupon_module->getCouponDetailById($user_coupon_detail['couponId']);
        }
        $num = 0;//订单数量
        $order_list = array();//订单信息
        $use_coupon_detail = array();//当前使用的优惠券信息
        if (empty($goods_list)) {
            return array(
                'must_self' => 0,
                'num' => $num,
                'order_list' => $order_list,
                'can_use_coupon' => 1,
                'use_coupon_detail' => $use_coupon_detail,//返回使用优惠券的信息
                'cuid' => 0
            );
        }
        $convention_zero_order = array(
            'can_use_coupon' => 1,//是否允许使用优惠券(0:不允许 1:允许) PS:主要针对限时限量商品是否开启不享受优惠券
            'goods_money_total' => 0,//商品总金额
            'can_use_conpon_money' => 0,//允许使用优惠券的金额
            'goods_list' => array(),
            'order_tag' => 1,//订单标识(1:常规订单 2:非常规订单 3:限时仅自提 4:限量仅自提)
        );//常规订单
        $convention_one_order = array(
            'can_use_coupon' => 1,//是否允许使用优惠券(0:不允许 1:允许) PS:主要针对限时限量商品是否开启不享受优惠券
            'goods_money_total' => 0,//商品总金额
            'can_use_conpon_money' => 0,//允许使用优惠券的金额
            'goods_list' => array(),
            'order_tag' => 2,//订单标识(1:常规订单 2:非常规订单 3:限时仅自提 4:限量仅自提)
        );//非常规订单
        $must_self = 0;//是否必须自提(0:否 1:是) PS:场景:拆单之后只有一笔订单,而这笔订单刚好在系统拆成了自提单时,则需要告诉前端必须选择自提订单,不能选择非自提
        $all_goods_list_map = [];
        $all_goods_id_arr = array_column($goods_list, 'goodsId');
        $all_goods_id_arr = array_unique($all_goods_id_arr);
        if (count($all_goods_id_arr) > 0) {
            $all_goods_list_data = $this->getGoodsListById($all_goods_id_arr);
            $all_goods_list = $all_goods_list_data['data'];
            foreach ($all_goods_list as $all_goods_list_row) {
                $all_goods_list_map[$all_goods_list_row['goodsId']] = $all_goods_list_row;
            }
        }
        foreach ($goods_list as $key => $item) {
            $goods_id = $item['goodsId'];
            $goods_cnt = (float)$item['goodsCnt'];
//            $goods_detail = $this->getGoodsInfoById($goods_id, '*', 2, 1);
            $goods_detail = $all_goods_list_map[$goods_id];
            if ($goods_detail['SuppPriceDiff'] == 1) {
                $goods_detail['is_weight'] = 1;
            } else {
                $goods_detail['is_weight'] = 0;
            }
            $goods_list[$key]['shopId'] = $goods_detail['shopId'];//勿动
            $goods_detail['goods_cnt'] = $goods_cnt;
            $goods_detail['userId'] = 0;
            $goods_detail['skuId'] = 0;
            $goods_detail['cartId'] = 0;
            $goods_detail['goodsAttrId'] = 0;
            $goods_detail['remarks'] = '';
            if ($item['userId'] > 0) {
                $goods_detail['userId'] = $item['userId'];
            }
            if ($item['skuId'] > 0) {
                $goods_detail['skuId'] = $item['skuId'];
            }
            if ($item['goodsAttrId'] > 0) {
                $goods_detail['goodsAttrId'] = $item['goodsAttrId'];
            }
            if ($item['cartId'] > 0) {
                $goods_detail['cartId'] = $item['cartId'];
            }
            if (!empty($item['remarks'])) {
                $goods_detail['remarks'] = $item['remarks'];
            }
            if ($goods_detail['isConvention'] == 0) {
                $convention_zero_order['goods_list'][] = $goods_detail;
            } else {
                $convention_one_order['goods_list'][] = $goods_detail;
            }
        }
        $shop_id = (int)$goods_list[0]['shopId'];
        $time_limit_self_order = array(
            'can_use_coupon' => 1,//是否允许使用优惠券(0:不允许 1:允许) PS:主要针对限时限量商品是否开启不享受优惠券
            'goods_money_total' => 0,//商品总金额
            'can_use_conpon_money' => 0,//允许使用优惠券的金额
            'goods_list' => array(),
            'order_tag' => 3,//订单标识(1:常规订单 2:非常规订单 3:限时仅自提 4:限量仅自提)
        );//限时活动仅自提
        $limit_num_self_order = array(
            'can_use_coupon' => 1,//是否允许使用优惠券(0:不允许 1:允许) PS:主要针对限时限量商品是否开启不享受优惠券
            'goods_money_total' => 0,//商品总金额
            'can_use_conpon_money' => 0,//允许使用优惠券的金额
            'goods_list' => array(),
            'order_tag' => 4,//订单标识(1:常规订单 2:非常规订单 3:限时仅自提 4:限量仅自提)
        );//限量活动仅自提
        $shop_module = new ShopsModule();
        //处理常规商品中包含的限时限量商品-start
        foreach ($convention_zero_order['goods_list'] as $zero_key => $zero_val) {
            $goods_id = $zero_val['goodsId'];
            $goods_cnt = (float)$zero_val['goods_cnt'];
            if ($this->isTrueLimitGoods($goods_id, $goods_cnt)) {
                //门店是否开启限量活动仅自提
                if ($shop_module->isOpenLimitNumSelf($shop_id)) {
                    $limit_num_self_order['goods_list'][] = $zero_val;
                    unset($convention_zero_order['goods_list'][$zero_key]);
                }
            }
            if ($this->isTrueFlashSaleGoods($goods_id, $goods_cnt)) {
                //门店是否开启限时活动仅自提
                if ($shop_module->isOpenTimeLimitSelf($shop_id)) {
                    $time_limit_self_order['goods_list'][] = $zero_val;
                    unset($convention_zero_order['goods_list'][$zero_key]);
                }
            }
        }
        $convention_zero_order['goods_list'] = array_values($convention_zero_order['goods_list']);
        //处理常规商品中包含的限时限量商品-end
        //处理非常规商品中包含的限时限量商品-start
        foreach ($convention_one_order['goods_list'] as $one_key => $one_val) {
            $goods_id = $one_val['goodsId'];
            $goods_cnt = (float)$one_val['goods_cnt'];
            $shop_id = $one_val['shopId'];
            if ($this->isTrueLimitGoods($goods_id, $goods_cnt)) {
                //门店是否开启限量活动仅自提
                if ($shop_module->isOpenLimitNumSelf($shop_id)) {
                    $limit_num_self_order['goods_list'][] = $one_val;
                    unset($convention_one_order['goods_list'][$one_key]);
                }
            }
            if ($this->isTrueFlashSaleGoods($goods_id, $goods_cnt)) {
                //门店是否开启限时活动仅自提
                if ($shop_module->isOpenTimeLimitSelf($shop_id)) {
                    $time_limit_self_order['goods_list'][] = $one_val;
                    unset($convention_zero_order['goods_list'][$one_key]);
                }
            }
        }
        $convention_one_order['goods_list'] = array_values($convention_one_order['goods_list']);
        //处理非常规商品中包含的限时限量商品-end
        //拆单后的数量-start
        $num = 0;
        if (!empty($convention_zero_order['goods_list'])) {
            $num += 1;
            $order_list[] = $convention_zero_order;
        }
        if (!empty($convention_one_order['goods_list'])) {
            $num += 1;
            $order_list[] = $convention_one_order;
        }
        if (!empty($time_limit_self_order['goods_list'])) {
            $num += 1;
            $order_list[] = $time_limit_self_order;
        }
        if (!empty($limit_num_self_order['goods_list'])) {
            $num += 1;
            $order_list[] = $limit_num_self_order;
        }
        //拆单后的数量-end
        if ($num == 1 && (!empty($time_limit_self_order['goods_list']) || !empty($limit_num_self_order['goods_list'])) && ($shop_module->isOpenTimeLimitSelf($shop_id) || $shop_module->isOpenLimitNumSelf($shop_id))) {
            $must_self = 1;
        }
        //处理每笔订单是否可以使用优惠券-start
        $coupon_use_max_money = 0;//当前优惠券最多可以抵扣的金额 PS:cuid存在时
        $coupon_use_money_arr = array();//用于存放各自订单使用该优惠券的金额,后面将优惠券分配给优惠力度最大的那笔订单
        foreach ($order_list as $order_key => $order_val) {
            $current_order_goods = $order_val['goods_list'];
            $current_order_goods = getCartGoodsSku($current_order_goods);
            $order_can_use_coupon = array();//当前订单允许使用优惠券的情况(0:不允许 1:允许)
            $current_order_goods_price_total = array();//当前订单商品总金额
            $order_can_use_coupon_money = array();//当前订单允许使用优惠券的金额 PS:因为有些商品是不支持使用优惠券的
            $current_order_goods_amount = 0;//当前订单金额小计,可使用优惠券的商品金额
            foreach ($current_order_goods as $current_key => $current_val) {//临时处理,修复bug
                $goods_id = $current_val['goodsId'];
                $goods_cnt = $current_val['goods_cnt'];
                $sku_id = $current_val['skuId'];
                $goods_price = $this->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_cnt, $current_val, 1);//处理过后的商品单价
                $goods_price_total = (float)bc_math($goods_price, $goods_cnt, 'bcmul', 2);
                $current_order_goods_amount += $goods_price_total;
                $current_order_goods[$current_key] = $current_val;
            }
            foreach ($current_order_goods as $current_key => $current_val) {
                $goods_no_can_coupon = 1;//当前商品是否可以使用优惠券(0:不可以 1:可以)
                $goods_id = $current_val['goodsId'];
                $goods_cnt = $current_val['goods_cnt'];
//                $goods_attr_id = $current_val['goodsAttrId'];
                $sku_id = $current_val['skuId'];
                $goods_price = (float)$current_val['shopPrice'];
                $goods_price_total = (float)bc_math($goods_price, $goods_cnt, 'bcmul', 2);
                if ($this->isTrueLimitGoods($goods_id, $goods_cnt) && $shop_module->isLimitNumNocoupons($shop_id)) {//店铺是否限制限量商品不享受优惠券
                    $goods_no_can_coupon = 0;
                    $current_order_goods_amount = $current_order_goods_amount - $goods_price_total;
                }
                if ($this->isTrueFlashSaleGoods($goods_id, $goods_cnt) && $shop_module->isTimeLimitNocoupons($shop_id)) {//店铺是否限制限时商品不享受优惠券
                    $goods_no_can_coupon = 0;
                }
                if ($cuid > 0) {//验证用户的优惠券是否可以使用在当前商品上
                    $coupon_id = $user_coupon_detail['couponId'];
                    $coupon_error = '';
                    $check_arr = array(
                        'couponId' => $coupon_id,
                        'goods_id_arr' => $goods_id,
                    );
                    $check_res = check_coupons_auth($check_arr, $coupon_error);//判断当前商品是否允许使用该优惠券
                    if (!$check_res) {
                        $goods_no_can_coupon = 0;
                    }
                }
                //$goods_price = $this->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_cnt, $current_val);//处理过后的商品单价
//                $goods_price = (float)$current_val['shopPrice'];
                $order_can_use_coupon[$current_key] = $goods_no_can_coupon;
//                $goods_price_total = (float)bc_math($goods_price, $goods_cnt, 'bcmul', 2);
//                if ($goods_price_total < $coupon_detail['spendMoney']) {
//                    $goods_no_can_coupon = 0;
//                }
                if ($current_order_goods_amount < $coupon_detail['spendMoney']) {
                    $goods_no_can_coupon = 0;
                }
                if ($goods_no_can_coupon == 1) {
                    $order_can_use_coupon_money[] = $goods_price_total;//支持使用优惠券金额
                }
                $current_order_goods_price_total[] = $goods_price_total;//当前订单所有的商品金额
                $current_order_goods[$current_key] = $current_val;
            }
            $order_list[$order_key]['goods_list'] = $current_order_goods;
            $order_can_use_coupon = array_sum($order_can_use_coupon);
            if ($order_can_use_coupon <= 0) {
                $order_list[$order_key]['can_use_coupon'] = 0;
            }
            $current_order_goods_price_total = array_sum($current_order_goods_price_total);
            $order_can_use_coupon_money = array_sum($order_can_use_coupon_money);
            $order_list[$order_key]['can_use_conpon_money'] = $order_can_use_coupon_money;//本单允许使用优惠前的商品金额
            $order_can_max_coupon_money = 0;//当前订单最多使用的优惠券金额
            if ($cuid > 0) {//判断当前订单商品金额是否可以使用优惠券(该金额是允许使用优惠券的金额)
                if ($order_can_use_coupon_money < $coupon_detail['spendMoney']) {
                    //当前订单金额不满足优惠券最低消费金额不给使用
                    $order_list[$order_key]['can_use_coupon'] = 0;
                }
                $order_can_max_coupon_money = $coupon_detail['couponMoney'];
                if ($order_can_use_coupon_money < $coupon_detail['couponMoney']) {
                    //当前订单金额小于优惠券面额,则优惠券最多抵扣当前订单金额
                    $order_can_max_coupon_money = $order_can_use_coupon_money;
                }
            }
            $order_list[$order_key]['goods_money_total'] = $current_order_goods_price_total;
            $coupon_use_money_arr[$order_key] = $order_can_max_coupon_money;
        }
        if ($cuid > 0) {
            $use_coupon_detail = $coupon_detail;
            $use_coupon_detail['userCouponId'] = $cuid;
            $use_coupon_detail['coupon_use_max_money'] = 0;//最优优惠金额
            $use_coupon_detail['max_coupon_order_key'] = 0;//最优订单 PS:在该订单上优惠券的金额可以发挥到最大
            arsort($coupon_use_money_arr);
            foreach ($coupon_use_money_arr as $ck => $cv) {
                $use_coupon_detail['coupon_use_max_money'] = (float)$cv;
                $use_coupon_detail['max_coupon_order_key'] = $ck;
                break;
            }
        }
        //处理每笔订单是否可以使用优惠券-end
        $can_use_coupon_num = array_sum(array_column($order_list, 'can_use_coupon'));
        $can_use_coupon = 1;//最终是否可以使用优惠券(0:不允许使用 1:允许使用),适用于前置仓模式
        if ($can_use_coupon_num < 1) {
            $can_use_coupon = 0;
        }
        $return_data = array(
            'num' => $num,//拆单数量
            'order_list' => $order_list,//拆单数据
            'must_self' => $must_self,//是否必须自提(0:否 1:是)
            'can_use_coupon' => $can_use_coupon,//本次支付的订单是否允许使用优惠券(0:不允许 1:允许)
            'use_coupon_detail' => $use_coupon_detail,//当前使用的优惠券信息
            'cuid' => $cuid//用户使用的优惠券记录id
        );
        return $return_data;
    }

    /**
     * 判断商品是否是限时活动商品(此为通过活动规则校验的才会返回true)
     * @param int $goods_id 商品id
     * @param float $goods_cnt 购买数量,默认为0,如果大于0则会参与活动校验
     * @return bool
     * */
    public function isTrueFlashSaleGoods(int $goods_id, $goods_cnt = 0)
    {
        if (empty($goods_id)) {
            return false;
        }
        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            return false;
        }
        $is_flash_sale = $goods_detail['isFlashSale'];//限时
        if ($is_flash_sale != 1) {
            return false;
        }
        $verificaton_goods_flash_res = getGoodsFlashSale($goods_id);
        if ($verificaton_goods_flash_res['code'] == ExceptionCodeEnum::FAIL) {
            //不在限时时间段内
            return false;
        }
        $verification_res = goodsTimeLimit($goods_id, $verificaton_goods_flash_res, $goods_cnt);
        if (!empty($verification_res)) {
            return false;
        }
        return true;
    }

    /**
     * 判断商品是否是限量活动商品(此为通过活动规则校验的才会返回true)
     * @param int $goods_id 商品id
     * @param float $goods_cnt 购买数量,默认为0,如果大于0则会参与活动校验
     * @return bool
     * */
    public function isTrueLimitGoods(int $goods_id, $goods_cnt = 0)
    {
        if (empty($goods_id)) {
            return false;
        }
        $field = 'goodsId,goodsName,shopPrice,memberPrice,isLimitBuy,isFlashSale';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            return false;
        }
        $is_limit_buy = $goods_detail['isLimitBuy'];//限量
        if ($is_limit_buy != 1) {
            return false;
        }
        if ($is_limit_buy == 1) {
            $verification_time_limit_res = goodsTimeLimit($goods_id, null, $goods_cnt);
            if (!empty($verification_time_limit_res)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断商品是否为新人专享商品
     * @param int $users_id 用户id
     * @param int $goods_id 商品id
     * @return bool
     * */
    public function isNewPeopleGoods(int $users_id, int $goods_id)
    {
        $field = 'goodsId,isNewPeople';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            return false;
        }
        if ($goods_detail['isNewPeople'] != 1) {
            return false;
        }
        $order_model = new OrdersModel();
        $order_count = $order_model->where(array('userId' => $users_id, 'orderFlag' => 1))->count();
        if ($order_count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 校验商品必填字段
     * @param array $field 商品字段
     * @param int $shop_id 门店id
     * @param int $goods_id 商品id PS:等于0为新增,大于0为编辑
     * @return array
     * */
    public function verificationGoodsRequiredField(array $field, int $shop_id, $goods_id = 0)
    {
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品字段校验失败');
        $verification_goods_sn_res = $this->verificationGoodsSn($field['goodsSn'], $shop_id, $goods_id);//校验商品编号是否可用
        if ($verification_goods_sn_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $error_data['msg'] = $verification_goods_sn_res['msg'];
            return $error_data;
        }
        if (empty($field['goodsName'])) {
            $error_data['msg'] = '商品名称必填';
            return $error_data;
        }
        if (empty($field['goodsCatId1']) || empty($field['goodsCatId2']) || empty($field['goodsCatId3'])) {
            $error_data['msg'] = '请完商城分类';
            return $error_data;
        }
        if (empty($field['shopCatId1']) || empty($field['shopCatId2'])) {
            $error_data['msg'] = '请完善店铺分类';
            return $error_data;
        }
        if (empty($field['goodsImg'])) {
            $error_data['msg'] = '商品主图必填';
            return $error_data;
        }
        if (empty($field['marketPrice'])) {
            $error_data['msg'] = '市场价必须大于0';
            return $error_data;
        }
//        if (empty($field['shopPrice'])) {
//            $error_data['msg'] = '店铺价必须大于0';
//            return $error_data;
//        }
        if (isset($field['shopPrice'])) {
            if ((float)$field['shopPrice'] < 0) {//支持0元购买
                $error_data['msg'] = '店铺价不能小于0';
                return $error_data;
            }
        }
//        if ($field['marketPrice'] <= $field['shopPrice']) {
//            $error_data['msg'] = '店铺价必须小于市场价';
//            return $error_data;
//        }
        if ((float)$field['goodsStock'] < 0) {
            $error_data['msg'] = '商品库存不得小于0';
            return $error_data;
        }
        if (empty($field['unit'])) {
            $error_data['msg'] = '商品单位必填';
            return $error_data;
        }
//        if ((float)$field['weightG'] <= 0) {//废弃
//            $error_data['msg'] = '包装系数必须大于0';
//            return $error_data;
//        }
        if ($field['SuppPriceDiff'] == 1 && $field['sortOverweightG'] < 0) {
            $error_data['msg'] = '称重产品可超重量不得小于0';
            return $error_data;
        }
        if (!empty($field['plu_code'])) {
            //plu_code不为空需校验plu_code必须为4位数字且不得0开头,不得大于4000
            $plu_code = $field['plu_code'];
            $first_num = substr($plu_code, 0, 1);
//            if (!preg_match('/^(\d{4})$/', $plu_code) || $first_num == 0 || (float)$plu_code > 4000) {
            if ($first_num == 0 || (float)$plu_code > 4000 || (float)$plu_code < 1) {
                $error_data['msg'] = 'plu编码取值范围为1到4000之间';
                return $error_data;
            }
            $goods_sn = $field['goodsSn'];
            $first_num = substr($goods_sn, 0, 1);
            if (!preg_match('/^(\d{5})$/', $goods_sn) || $first_num == 0) {
                $error_data['msg'] = '设置了PLU的商品编码必须为5位数字且不得0开头';
                return $error_data;
            }
        }
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '商品表字段通过校验');
    }

    /**
     * 校验当前商品编码是否可用
     * @param string $code 编码
     * @param shop_id 门店id
     * @param int $goods_id 商品id PS:0代表新增商品,大于0代表编辑商品
     * @return array
     * */
    public function verificationGoodsSn(string $code, int $shop_id, $goods_id = 0)
    {
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品条码错误');
        if (empty($code)) {
            $error_data['msg'] = '商品编号必填';
            return $error_data;
        }
        $where = array(
            'goodsSn' => $code,
            'shopId' => $shop_id,
        );
        $field = 'goodsId,goodsName,shopId,isBecyclebin';
        $goods_detail = $this->getGoodsInfoByParams($where, $field, 2);
        if (!empty($goods_detail)) {
            $current_goods_id = $goods_detail['goodsId'];
            $goods_name = $goods_detail['goodsName'];
            //场景-新增商品
            if (($goods_detail['isBecyclebin'] == 0 && empty($goods_id)) || ($goods_detail['isBecyclebin'] == 0 && $goods_id != $current_goods_id)) {
                $error_data['msg'] = "商品编号与商品{$goods_name}编号重复,请更换其他编号";
                return $error_data;
            }
            if (($goods_detail['isBecyclebin'] == 1 && empty($goods_id)) || ($goods_detail['isBecyclebin'] == 1 && $goods_id != $current_goods_id)) {
                $error_data['msg'] = "商品编号已存在,请更换其他编号,或者删除回收站中的商品{$goods_name}";
                return $error_data;
            }
        }
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '商品编号可用');
    }

    /**
     * 保存商品主表基本信息
     * @param array $params 商品表字段
     * @param object $trans
     * @return array
     * */
    public function saveGoodsDetail(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $goods_model = new GoodsModel();
        $configs = $GLOBALS['CONFIG'];
        $field = array(
            'goodsSn' => null,
            'goodsName' => null,
            'goodsImg' => null,
            'goodsThums' => null,
            'brandId' => null,
            'shopId' => null,
            'marketPrice' => null,
            'shopPrice' => null,
            'goodsStock' => null,
            'saleCount' => null,
            'isBook' => null,
            'bookQuantity' => null,
            'warnStock' => null,
            'goodsUnit' => null,
            'goodsSpec' => null,
            'isSale' => null,
            'isBest' => null,
            'isHot' => null,
            'isRecomm' => null,
            'isNew' => null,
            'isAdminBest' => null,
            'isAdminRecom' => null,
            'recommDesc' => null,
            'goodsCatId1' => null,
            'goodsCatId2' => null,
            'goodsCatId3' => null,
            'shopCatId1' => null,
            'shopCatId2' => null,
            'goodsDesc' => null,
            'isShopRecomm' => null,
            'isIndexRecomm' => null,
            'isActivityRecomm' => null,
            'isInnerRecomm' => null,
            'goodsStatus' => null,
            'attrCatId' => null,
            'goodsKeywords' => null,
            'goodsFlag' => null,
            'statusRemarks' => null,
            'isShopSecKill' => null,
            'ShopGoodSecKillStartTime' => null,
            'ShopGoodSecKillEndTime' => null,
            'isAdminShopSecKill' => null,
            'AdminShopGoodSecKillStartTime' => null,
            'AdminShopGoodSecKillEndTime' => null,
            'AdminSecKillSort' => null,
            'shopSecKillSort' => null,
            'isShopPreSale' => null,
            'ShopGoodPreSaleStartTime' => null,
            'ShopGoodPreSaleEndTime' => null,
            'isAdminShopPreSale' => null,
            'AdminShopGoodPreSaleStartTime' => null,
            'AdminShopGoodPreSaleEndTime' => null,
            'AdminPreSaleSort' => null,
            'shopPreSaleSort' => null,
            'bookQuantityEnd' => null,
            'PreSalePayPercen' => null,
            'markIcon' => null,
            'SuppPriceDiff' => null,
            'weightG' => null,
            'IntelligentRemark' => null,
            'shopGoodsSort' => null,
            'userSecKillNUM' => null,
            'shopSecKillNUM' => null,
            'isDistribution' => null,
            'firstDistribution' => null,
            'SecondaryDistribution' => null,
            'goodsLocation' => null,
            'isMembershipExclusive' => null,
            'memberPrice' => null,
            'integralReward' => null,
            'typeId' => null,
            'goodsCompany' => null,
            'buyNum' => null,
            'seckillSaleNum' => null,
            'isFlashSale' => null,
            'isLimitBuy' => null,
            'limitCountActivityPrice' => null,
            'limitCount' => null,
            'spec' => null,
            'isNewPeople' => null,
            'orderNumLimit' => null,
            'stockWarningNum' => null,
            'integralRate' => null,
            'buyNumLimit' => null,
            'inspectionDiff' => null,
            'minBuyNum' => null,
            'isConvention' => null,
            'sortOverweightG' => null,
            'isBecyclebin' => null,
            'virtualSales' => null,
            'limit_daily' => null,
            'unit' => null,
            'delayed' => null,
            'goodsId' => null,
            'goodsSn' => null,
            'goodsName' => null,
            'goodsImg' => null,
            'goodsThums' => null,
            'brandId' => null,
            'shopId' => null,
            'marketPrice' => null,
            'shopPrice' => null,
            'goodsStock' => null,
            'saleCount' => null,
            'isBook' => null,
            'bookQuantity' => null,
            'warnStock' => null,
            'goodsUnit' => null,
            'goodsSpec' => null,
            'isSale' => null,
            'isBest' => null,
            'isHot' => null,
            'isRecomm' => null,
            'isNew' => null,
            'isAdminBest' => null,
            'isAdminRecom' => null,
            'recommDesc' => null,
            'goodsCatId1' => null,
            'goodsCatId2' => null,
            'goodsCatId3' => null,
            'shopCatId1' => null,
            'shopCatId2' => null,
            'goodsDesc' => null,
            'isShopRecomm' => null,
            'isIndexRecomm' => null,
            'isActivityRecomm' => null,
            'isInnerRecomm' => null,
            'goodsStatus' => null,
            'attrCatId' => null,
            'goodsKeywords' => null,
            'goodsFlag' => null,
            'statusRemarks' => null,
            'isShopSecKill' => null,
            'ShopGoodSecKillStartTime' => null,
            'ShopGoodSecKillEndTime' => null,
            'isAdminShopSecKill' => null,
            'AdminShopGoodSecKillStartTime' => null,
            'AdminShopGoodSecKillEndTime' => null,
            'AdminSecKillSort' => null,
            'shopSecKillSort' => null,
            'isShopPreSale' => null,
            'ShopGoodPreSaleStartTime' => null,
            'ShopGoodPreSaleEndTime' => null,
            'isAdminShopPreSale' => null,
            'AdminShopGoodPreSaleStartTime' => null,
            'AdminShopGoodPreSaleEndTime' => null,
            'AdminPreSaleSort' => null,
            'shopPreSaleSort' => null,
            'bookQuantityEnd' => null,
            'PreSalePayPercen' => null,
            'markIcon' => null,
            'SuppPriceDiff' => null,
            'weightG' => null,
            'IntelligentRemark' => null,
            'shopGoodsSort' => null,
            'userSecKillNUM' => null,
            'shopSecKillNUM' => null,
            'isDistribution' => null,
            'firstDistribution' => null,
            'SecondaryDistribution' => null,
            'goodsLocation' => null,
            'isMembershipExclusive' => null,
            'memberPrice' => null,
            'integralReward' => null,
            'typeId' => null,
            'goodsCompany' => null,
            'buyNum' => null,
            'seckillSaleNum' => null,
            'isFlashSale' => null,
            'isLimitBuy' => null,
            'limitCountActivityPrice' => null,
            'limitCount' => null,
            'spec' => null,
            'isNewPeople' => null,
            'orderNumLimit' => null,
            'stockWarningNum' => null,
            'integralRate' => null,
            'buyNumLimit' => null,
            'inspectionDiff' => null,
            'minBuyNum' => null,
            'goodsId' => null,
            'goodsSn' => null,
            'goodsName' => null,
            'goodsImg' => null,
            'goodsThums' => null,
            'brandId' => null,
            'shopId' => null,
            'marketPrice' => null,
            'shopPrice' => null,
            'goodsStock' => null,
            'saleCount' => null,
            'isBook' => null,
            'bookQuantity' => null,
            'warnStock' => null,
            'goodsUnit' => null,
            'goodsSpec' => null,
            'isSale' => null,
            'isBest' => null,
            'isHot' => null,
            'isRecomm' => null,
            'isNew' => null,
            'isAdminBest' => null,
            'isAdminRecom' => null,
            'recommDesc' => null,
            'goodsCatId1' => null,
            'goodsCatId2' => null,
            'goodsCatId3' => null,
            'shopCatId1' => null,
            'shopCatId2' => null,
            'goodsDesc' => null,
            'isShopRecomm' => null,
            'isIndexRecomm' => null,
            'isActivityRecomm' => null,
            'isInnerRecomm' => null,
            'goodsStatus' => null,
            'attrCatId' => null,
            'goodsKeywords' => null,
            'goodsFlag' => null,
            'statusRemarks' => null,
            'isShopSecKill' => null,
            'ShopGoodSecKillStartTime' => null,
            'ShopGoodSecKillEndTime' => null,
            'isAdminShopSecKill' => null,
            'AdminShopGoodSecKillStartTime' => null,
            'AdminShopGoodSecKillEndTime' => null,
            'AdminSecKillSort' => null,
            'shopSecKillSort' => null,
            'isShopPreSale' => null,
            'ShopGoodPreSaleStartTime' => null,
            'ShopGoodPreSaleEndTime' => null,
            'isAdminShopPreSale' => null,
            'AdminShopGoodPreSaleStartTime' => null,
            'AdminShopGoodPreSaleEndTime' => null,
            'AdminPreSaleSort' => null,
            'shopPreSaleSort' => null,
            'bookQuantityEnd' => null,
            'PreSalePayPercen' => null,
            'markIcon' => null,
            'SuppPriceDiff' => null,
            'weightG' => null,
            'IntelligentRemark' => null,
            'shopGoodsSort' => null,
            'userSecKillNUM' => null,
            'shopSecKillNUM' => null,
            'isDistribution' => null,
            'firstDistribution' => null,
            'SecondaryDistribution' => null,
            'goodsLocation' => null,
            'isMembershipExclusive' => null,
            'memberPrice' => null,
            'integralReward' => null,
            'typeId' => null,
            'goodsCompany' => null,
            'buyNum' => null,
            'seckillSaleNum' => null,
            'isFlashSale' => null,
            'isLimitBuy' => null,
            'limitCountActivityPrice' => null,
            'limitCount' => null,
            'spec' => null,
            'isNewPeople' => null,
            'orderNumLimit' => null,
            'stockWarningNum' => null,
            'integralRate' => null,
            'buyNumLimit' => null,
            'inspectionDiff' => null,
            'minBuyNum' => null,
            'isConvention' => null,
            'sortOverweightG' => null,
            'isBecyclebin' => null,
            'virtualSales' => null,
            'limit_daily' => null,
            'unit' => null,
            'delayed' => null,
            'plu_code' => null,
            'vedio_url' => null,
            'selling_stock' => null,
            'purchase_type' => null,
            'purchaser_or_supplier_id' => null,
        );
        parm_filter($field, $params);
        if (!empty($field['goodsName'])) {
            $goods_name = $field['goodsName'];
            $py_module = new PYCodeModule();
            $py_code_detail = $py_module->getFullSpell($goods_name);
            $field['py_code'] = $py_code_detail['py_code'];
            $field['py_initials'] = $py_code_detail['py_initials'];
        }
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '错误信息');
        $goods_id = (int)$params['goodsId'];
        $shop_id = (int)$params['shopId'];
        if (empty($goods_id)) {
            $field['createTime'] = date('Y-m-d H:i:s');
        }
        $shop_module = new ShopsModule();
        $shop_field = 'shopId,shopName,shopStatus,openLivePlay';
        $shop_detail = $shop_module->getShopsInfoById($shop_id, $shop_field, 2);
        if (empty($shop_detail)) {
            $m->rollback();
            $error_data['msg'] = '当前商品店铺无法识别';
            return $error_data;
        }
        $verification_field_res = $this->verificationGoodsRequiredField($field, $shop_id, $goods_id);//校验商品主表的一些必填字段
        if ($verification_field_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            $error_data['msg'] = $verification_field_res['msg'];
            return $error_data;
        }
        if ($shop_detail['shopStatus'] != 1) {//后加判断
            $m->rollback();
            $error_data['msg'] = '店铺状态信息异常,请联系管理员';
            return $error_data;
        }
        if ($shop_detail['shopStatus'] == 1) {
            //未审核通过的店铺商品不能直接上架
            $field['isSale'] = ((int)$field['isSale'] == 1) ? 1 : 0;
        } else {
            $field['isSale'] = 0;
        }
        if ($field['isSale'] == 1) {
            $field['saleTime'] = date('Y-m-d H:i:s');
        }
        if (empty($field['goodsStatus'])) {
            $field['goodsStatus'] = ($configs['isGoodsVerify'] == 1) ? 0 : 1;//商品状态(-1:禁售 0:未审核 1:已审核)
        }
        if (!empty($field['spec'])) {
            $field['spec'] = htmlspecialchars_decode($field['spec']);
        }
        if ($field['isNewPeople'] == 1) {
            $field['isRecomm'] = 0;
            $field['isBest'] = 0;
            $field['isNew'] = 0;
            $field['isHot'] = 0;
            $field['isMembershipExclusive'] = 0;
            $field['isFlashSale'] = 0;
            $field['isLimitBuy'] = 0;
            $field['isShopSecKill'] = 0;
            $field['isShopPreSale'] = 0;
        }
        if (isset($field['purchase_type'])) {
            if (is_numeric($field['purchase_type'])) {
                if ($field['purchase_type'] == 0) {
                    $field['purchaser_or_supplier_id'] = 0;
                } elseif ($field['purchase_type'] == 1) {//市场自采
                    $purchaserDetail = (new ShopStaffMemberModule())->getShopStaffMemberDetail($field['purchaser_or_supplier_id'], 'username');
                    if (empty($purchaserDetail)) {
                        $m->rollback();
                        $error_data['msg'] = '采购员异常';
                        return $error_data;
                    }
                } elseif ($field['purchase_type'] == 2) {//供应商直供
                    $supplierDetail = (new SupplierModule())->getSupplierDetailById($field['purchaser_or_supplier_id'], 'supplierName');
                    if (empty($supplierDetail)) {
                        $m->rollback();
                        $error_data['msg'] = '供应商异常';
                        return $error_data;
                    }
                }
            }
        }
        if (empty($goods_id)) {//新增商品
            $save_res = $goods_model->add($field);
            $goods_id = $save_res;
        } else {//修改商品
            $save_res = $goods_model->where(array(
                'goodsId' => $goods_id
            ))->save($field);
            $goods_id = $params['goodsId'];
        }
        if ($save_res === false) {
            $m->rollback();
            $error_data['msg'] = '商品信息保存失败';
            return $error_data;
        }
        if (empty($trans)) {
            $m->commit();
        }
        $retur_data = array(
            'goodsId' => $goods_id
        );
        return returnData($retur_data, ExceptionCodeEnum::SUCCESS, 'success', '商品信息保存成功');
    }

    /**
     * 商品相册-删除
     * @param int $goods_id
     * @param object $trans
     * @return  bool
     * */
    public function deleteGoodsGallerys(int $goods_id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new GoodsGalleryModel();
        $save_res = $model->where(array(
            'goodsId' => $goods_id
        ))->delete();
        if ($save_res === false) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 商品相册-新增
     * @param array $params 商品相册表中的字段 PS:一纬数组和多维数组分别对应单个添加和批量添加
     * @param object $trans
     * @return bool
     * */
    public function addGoodsGallerys(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new GoodsGalleryModel();
        if (array_keys($params) !== range(0, count($params) - 1)) {
            //一维单个新增
            $save_data = array(
                'goodsId' => null,
                'shopId' => null,
                'goodsImg' => null,
                'goodsThumbs' => null,
            );
            parm_filter($save_data, $params);
            $save_res = $model->add($save_data);
        } else {
            //多维批量新增
            $save_data = array();
            foreach ($params as $val) {
                $save_info = array(
                    'goodsId' => null,
                    'shopId' => null,
                    'goodsImg' => null,
                    'goodsThumbs' => null,
                );
                parm_filter($save_info, $val);
                $save_data[] = $save_info;
            }
            $save_res = $model->addAll($save_data);
        }
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
     * 商品规格-批量添加
     * @param int $goods_id 商品id
     * @param array $sku_data 商品sku信息
     * @return array
     * */
    public function addGoodsSkuBatch(int $goods_id, array $sku_data, $trans = null)
    {

        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品sku条码错误');
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($goods_id) || empty($sku_data)) {
            $m->rollback();
            $error_data['msg'] = '参数错误';
            return $error_data;
        }
        $repeat_sku_arr = array();
        foreach ($sku_data as $key => $val) {
            if (empty($val['selfSpec']) || empty($val['systemSpec'])) {
                $m->rollback();
                $error_data['msg'] = '请填写完整的sku信息';
                return $error_data;
            }
            $sku_system = $val['systemSpec'];
            $code = $sku_system['skuBarcode'];
            $repeat_sku_arr[] = $code;
            $sku_code_res = $this->verificationSkuBarcode($code, $goods_id);//检验sku条码是否可用
            if ($sku_code_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                $error_data['msg'] = $sku_code_res['msg'];
                return $error_data;
            }
            if ($sku_system['skuGoodsStock'] < 0) {
                $m->rollback();
                $error_data['msg'] = 'sku库存不能小于0';
                return $error_data;
            }
            if (empty($sku_system['unit'])) {
                $m->rollback();
                $error_data['msg'] = 'sku单位不能为空';
                return $error_data;
            }
//            if ((float)$sku_system['weigetG'] <= 0) {
//                $m->rollback();
//                $error_data['msg'] = 'sku包装系数必须大于0';
//                return $error_data;
//            }
//            if ((float)$sku_system['purchase_price'] <= 0) {
//                $m->rollback();
//                $error_data['msg'] = 'sku进货价必须大于0';
//                return $error_data;
//            }

        }
        $unique_arr = array_unique($repeat_sku_arr);
        $repeat_arr = array_diff_assoc($repeat_sku_arr, $unique_arr);
        if (count($repeat_arr) > 0) {
            $m->rollback();
            $error_data['msg'] = '提交的商品sku条码存在相同的条码';
            return $error_data;
        }
        $sku_self_model = new SkuGoodsSelfModel();
        $sku_system_model = new SkuGoodsSystemModel();
        $system_where = array();
        $system_where['dataFlag'] = 1;
        $system_where['goodsId'] = $goods_id;
        $exist_spec_system = $sku_system_model->where($system_where)->select();//已存在的系统规格
        $prefix = $m->tablePrefix;
        if (count($exist_spec_system) > 0) {
            //检验是否有重复添加的sku组合
            foreach ($exist_spec_system as $key => &$value) {
                $where = array();
                $where['self.skuId'] = $value['skuId'];
                $where['self.dataFlag'] = 1;
                $value['self.specSelf'] = $sku_self_model
                    ->alias('self')
                    ->join("left join {$prefix}sku_spec spec on self.specId=spec.specId")
                    ->join("left join {$prefix}sku_spec_attr attr on attr.attrId=self.attrId")
                    ->where($where)
                    ->field('self.id,self.skuId,self.specId,self.attrId,spec.specName,attr.attrName')
                    ->order('self.id asc')
                    ->select();
                if ($value['specSelf']) {
                    $spec_self_string = array();
                    $spec_attr_name = '';
                    foreach ($value['specSelf'] as $val) {
                        $spec_attr = array();
                        $spec_attr['specId'] = (int)$val['specId'];
                        $spec_attr['attrId'] = (int)$val['attrId'];
                        $spec_self_string[] = $spec_attr;
                        $spec_attr_name .= $val['attrName'] . ",";
                    }
                    $value['specSelfString'] = json_encode($spec_self_string);
                    $value['specAttrName'] = trim($spec_attr_name, ',');
                }
            }
            unset($value);
        }
        $self_attr = array();
        $sku_spec_attr_model = new SkuSpecAttrModel();
        foreach ($sku_data as $sku_key => $sku_detial) {
            $self_attr[] = $sku_detial['selfSpec'];
            $attrId_str_info = array();
            $new_spec_attr_arr = array();
            foreach ($sku_detial['selfSpec'] as $wk => $wv) {
                $spec_id = $sku_spec_attr_model->where(array(
                    'attrId' => $wv['attrId']
                ))->getField('specId');
                $attr_id = $wv['attrId'];
                $new_spec_attr_row = [
                    'specId' => (int)$spec_id,
                    'attrId' => (int)$attr_id,
                ];
                $new_spec_attr_arr[] = $new_spec_attr_row;
                $attrId_str_info[] = $attr_id;
            }
            $sku_data[$sku_key]['selfSpec'] = $new_spec_attr_arr;
            $now_system_string = json_encode($sku_data[$sku_key]['selfSpec']);
            foreach ($exist_spec_system as $sv) {
                if ($sv['specSelfString'] == $now_system_string) {
                    $m->rollback();
                    $error_data['msg'] = '添加失败,' . $sv['specAttrName'] . '自定义属性组合已经存在';
                    return $error_data;
                }
            }
        }
        $unique_self_arr_str = array();
        foreach ($self_attr as $item) {
            $unique_self_arr_str[] = implode(',', array_column($item, 'attrId'));
        }
        $unique_arr = array_values(array_unique($unique_self_arr_str));
        // 获取重复数据的数组
        $repeat_arr = array_values(array_diff_assoc($unique_self_arr_str, $unique_arr));
        if (!empty($repeat_arr)) {
            foreach ($repeat_arr as $re_v) {
                $attr_list = $sku_spec_attr_model->where(array('attrId' => array('IN', $re_v), 'dataFlag' => 1))->select();
                if (!empty($attr_list)) {
                    $m->rollback();
                    $repeat_attr_name = implode(',', array_column($attr_list, 'attrName'));
                    $error_data['msg'] = '添加失败,' . $repeat_attr_name . '自定义属性不能提交重复';
                    return $error_data;
                }
            }
        }
        foreach ($sku_data as $sku_key => $sku_detail) {
            //系统规格
            $system_spec = $sku_detail['systemSpec'];
            $system_spec = $this->checkSystemSkuValue($system_spec);
            $system_spec['goodsId'] = $goods_id;
            $system_spec['addTime'] = date('Y-m-d H:i:s');
            $sku_id = $sku_system_model->add($system_spec);


            if (!$sku_id) {
                $m->rollback();
                $error_data['msg'] = 'sku规格添加失败';
                return $error_data;
            }
            //自定义规格
            $self_spec_arr = $sku_detail['selfSpec'];
            foreach ($self_spec_arr as $self_key => $self_val) {
                $self_spec = array();
                $self_spec['skuId'] = $sku_id;
                $self_spec['specId'] = $self_val['specId'];
                $self_spec['attrId'] = $self_val['attrId'];
                $self_id = $sku_self_model->add($self_spec);
                if (!$self_id) {
                    $m->rollback();
                    $error_data['msg'] = 'sku规格添加失败';
                    return $error_data;
                }
            }

            //sku身份价格-start
            $rankArr = $sku_detail['rankArr'];
            $saveGoodsRankRes = $this->addGoodsRank($goods_id, $sku_id, $rankArr, $m);
            if ($saveGoodsRankRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                $error_data['msg'] = $saveGoodsRankRes['msg'];
                return $error_data;
            }
            //sku身份价格-end
        }
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '成功');
    }

    /**
     * 后加,如果传过来的sku属性值为空,则字段值以商品原来的同意义字段值为准
     * @param array systemSpec PS:非自定义参数处理
     * @return array
     * */
    public function checkSystemSkuValue($systemSpec)
    {
        if (!empty($systemSpec)) {
            foreach ($systemSpec as $key => $val) {
                if ($key == 'skuGoodsImg' && $val != '') {
                    $nums = substr_count($val, 'undefined');
                    if ($nums >= 1) {
                        $systemSpec[$key] = '';
                    }
                }
                if (is_null($val) || empty($val)) {
                    $systemSpec[$key] = '-1';
                }
            }
        }
        return $systemSpec;
    }

    /**
     * 校验sku编码是否可用
     * @param string $code sku编号
     * @param string $goods_id 商品id
     * @return array
     * */
    public function verificationSkuBarcode(string $code, int $goods_id)
    {
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品sku条码错误');
        if (empty($code)) {
            $error_data['msg'] = '商品sku条码必填';
            return $error_data;
        }
        $field = 'goodsId,goodsName,shopId';
        $goods_detail = $this->getGoodsInfoById($goods_id, $field, 2);
        if (empty($goods_detail)) {
            $error_data['msg'] = '商品信息有误';
            return $error_data;
        }
        $shop_id = $goods_detail['shopId'];
        $where = array(
            'system.skuBarcode' => $code,
            'system.dataFlag' => 1,
            'goods.goodsFlag' => 1,
            'goods.shopId' => $shop_id,
            'shop.shopFlag' => 1,
        );
        $sku_system_model = new SkuGoodsSystemModel();
        $prefix = M()->tablePrefix;
        $field = 'system.skuId,system.goodsId,system.skuBarcode';
        $sku_detail = $sku_system_model
            ->alias('system')
            ->join("left join {$prefix}goods goods on goods.goodsId=system.goodsId")
            ->join("left join {$prefix}shops shop on shop.shopId=goods.shopId")
            ->where($where)
            ->field($field)
            ->find();
        if (!empty($sku_detail)) {
            if ($sku_detail['goodsId'] != $goods_id) {
                $replace_goods_id = $sku_detail['goodsId'];
                $field = 'goodsId,goodsName,shopId,isBecyclebin';
                $replace_goods_detail = $this->getGoodsInfoById($replace_goods_id, $field, 2);
                if ($replace_goods_detail['isBecyclebin'] == 0) {
                    $error_data['msg'] = "当前商品sku条码与商品{$replace_goods_detail['goodsName']}sku条码重复,请更换其他条码";
                    return $error_data;
                }
                if ($replace_goods_detail['isBecyclebin'] == 1) {
                    $error_data['msg'] = "当前商品sku条码与回收站商品{$replace_goods_detail['goodsName']}sku条码重复,请更换其他条码或删除回收站中的商品";
                    return $error_data;
                }
            }
        }
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '商品sku条码可用');
    }

    /**
     * 同步商品售卖库存
     * @params string $goodsIdStr
     * @return bool
     * */
    public function syncGoodsSellingStock(string $goodsIdStr)
    {
        $goodsModel = new GoodsModel();
        $where = array(
            'goodsId' => array('in', $goodsIdStr)
        );
        $goodsList = $goodsModel->where($where)->field('goodsId,goodsName,goodsStock')->select();
        if (empty($goodsList)) {
            return false;
        }
        $syncGoodsData = array();
        foreach ($goodsList as $goodsDetail) {
            $syncGoodsData[] = array(
                'goodsId' => $goodsDetail['goodsId'],
                'selling_stock' => $goodsDetail['goodsStock'],
            );
        }
        $syncGoodsRes = $goodsModel->saveAll($syncGoodsData, 'wst_goods', 'goodsId');
        if ($syncGoodsRes === false) {
            return false;
        }
        $goodsList = getGoodsSku($goodsList);
        $syncSkuData = array();
        foreach ($goodsList as $goodsDetail) {
            if ($goodsDetail['hasGoodsSku'] != 1) {
                continue;
            }
            $currSkuList = $goodsDetail['goodsSku']['skuList'];
            foreach ($currSkuList as $currSkuDetail) {
                $syncSkuData[] = array(
                    'skuId' => $currSkuDetail['skuId'],
                    'selling_stock' => $currSkuDetail['systemSpec']['skuGoodsStock'],
                );
            }
        }
        if (!empty($syncSkuData)) {
            $skuModel = new SkuGoodsSystemModel();
            $syncSkuRes = $skuModel->saveAll($syncSkuData, 'wst_sku_goods_system', 'skuId');
            if ($syncSkuRes === false) {
                return false;
            }
        }
        return true;

    }

    /**
     * 获取商品库存
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @param float $goodsCnt 购物车最终购买数量/重量
     * @param array $lastGoodsDetail 上个操作的商品信息,主要用于替换商品的一些属性值
     * return float
     * */
    public function getGoodsStock(int $goodsId, int $skuId, float $goodsCnt, &$lastGoodsDetail = array())
    {
        $goodsDetail = $this->getGoodsInfoById($goodsId, 'goodsId,goodsName,goodsStock,selling_stock,isFlashSale,isLimitBuy,limitCount', 2);
        if (empty($goodsDetail)) {
            return 0;
        }
//        $stock = $goodsDetail['goodsStock'];
        $stock = $goodsDetail['selling_stock'];
        if ($skuId > 0) {
            $skuDetail = $this->getSkuSystemInfoById($skuId, 2);
            if (empty($skuDetail)) {
                return 0;
            }
            if (!empty($lastGoodsDetail)) {
                $lastGoodsDetail['goodsStock'] = $skuDetail['skuGoodsStock'];
                $lastGoodsDetail['weightG'] = $skuDetail['weigetG'];
                $lastGoodsDetail['minBuyNum'] = $skuDetail['minBuyNum'];
            }
//            $stock = $skuDetail['skuGoodsStock'];
            $stock = $skuDetail['selling_stock'];
        }
        if ($goodsDetail['isFlashSale'] == 1) {//限时
            $goodsTimeSnappedRes = getGoodsFlashSale($goodsId);
            if ($goodsTimeSnappedRes['code'] == 1) {
                //判断限时购库存,不够时直接使用原始数据
                $goodsTimeLimitRes = goodsTimeLimit($goodsId, $goodsTimeSnappedRes, $goodsCnt);
                if (empty($goodsTimeLimitRes)) {
                    if (!empty($lastGoodsDetail)) {
                        $lastGoodsDetail['marketPrice'] = $goodsTimeSnappedRes['marketPrice'];
                        $lastGoodsDetail['shopPrice'] = $goodsTimeSnappedRes['activityPrice'];
                        $lastGoodsDetail['minBuyNum'] = $goodsTimeSnappedRes['minBuyNum'];
                        $lastGoodsDetail['goodsStock'] = $goodsTimeSnappedRes['activeInventory'];
                    }
                    $stock = $goodsTimeSnappedRes['activeInventory'];
                }
            }
        }
        if ($goodsDetail['isLimitBuy'] == 1) {//限量商品->商品总库存替换成活动库存【限量购】
            $goodsLimitCountRes = goodsTimeLimit($goodsId, array(), $goodsCnt);
            if (empty($goodsLimitCountRes)) {
                $stock = $goodsDetail['limitCount'];
            }
        }
        return (float)$stock;
    }

    /**
     * 商品身份价格-重置
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @param object $trans
     * @return bool
     * */
    public function clearGoodsRank(int $goodsId, $skuId = 0, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new RankGoodsModel();
//        $saveData = array(
//            'isDelete' => 1,
//            'deleteTime' => date('Y-m-d H:i:s'),
//        );
        $where = array(
            'goodsId' => $goodsId,
            'skuId' => $skuId
        );
//        $res = $model->where($where)->save($saveData);
        $res = $model->where($where)->delete();
        if ($res === false) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 商品身份价格-添加身份价格
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @param array $rankArr 身份价格信息
     * @param object $trans
     * @return array
     * */
    public function addGoodsRank(int $goodsId, int $skuId, array $rankArr, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $clearRes = $this->clearGoodsRank($goodsId, $skuId, $dbTrans);//重置商品身份价格
        if (!$clearRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品身份价格重置失败');
        }
        $addGoodsData = array();
        $datetime = date('Y-m-d H:i:s');
        foreach ($rankArr as $item) {
            if (empty($item['rankId'])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品身份价格信息有误,缺少必填参数-rankId');
            }
            if ((float)$item['price'] <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品身份价格信息有误,价格必须大于0');
            }
            $addGoodsDataInfo = array(
                'goodsId' => $goodsId,
                'price' => $item['price'],
                'rankId' => $item['rankId'],
                'createTime' => $datetime,
                'updateTime' => $datetime,
            );
            if (!empty($skuId)) {
                $addGoodsDataInfo['skuId'] = $skuId;
            }
            $addGoodsData[] = $addGoodsDataInfo;
        }
        if (!empty($addGoodsData)) {
            $rankGoodsModel = new RankGoodsModel();
            $addRes = $rankGoodsModel->addAll($addGoodsData);
            if (!$addRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品身份价格更新失败');
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 商品身份-获取商品身份价格列表-商品id查找
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @return array
     * */
    public function getGoodsRankListByGoodsId(int $goodsId, $skuId = 0)
    {
        $model = new RankGoodsModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'rank_goods.isDelete' => 0,
            'rank_goods.goodsId' => $goodsId,
            'rank_goods.skuId' => $skuId,
            'rank.isDelete' => 0,
        );
        $field = 'rank.rankId,rank.rankName,rank_goods.price';
        $res = $model
            ->alias('rank_goods')
            ->join("left join {$prefix}rank rank on rank.rankId=rank_goods.rankId")
            ->where($where)
            ->field($field)
            ->select();
        return (array)$res;
    }

    /**
     * 商品身份-获取sku身份价格列表-商品skuId查找
     * @param int $skuId 商品skuId
     * @return array
     * */
    public function getSkuRankListBySkuId(int $skuId)
    {
        $model = new RankGoodsModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'rank_goods.isDelete' => 0,
            'rank_goods.skuId' => $skuId,
            'rank.isDelete' => 0,
        );
        $field = 'rank.rankId,rank.rankName,rank_goods.price';
        $res = $model
            ->alias('rank_goods')
            ->join("left join {$prefix}rank rank on rank.rankId=rank_goods.rankId")
            ->where($where)
            ->field($field)
            ->select();
        return (array)$res;
    }

    /**
     * 商品单位-获取商品单位名称
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @return string
     * */
    public function getGoodsUnitByParams(int $goodsId, $skuId = 0)
    {
        $goodsModel = new GoodsModel();
        $unit = $goodsModel->where(array('goodsId' => $goodsId))->getField('unit');
        if (!empty($skuId)) {
            $skuSystemModel = new SkuGoodsSystemModel();
            $unit = $skuSystemModel->where(array('skuId' => $skuId))->getField('unit');
        }
        return (string)$unit;
    }

    /**
     * sku属性详情-属性名称获取
     * @param string $specName 属性名称
     * @param string $attrName 属性值
     * @return array
     * */
    public function getSkuAtrrDetailByName(string $specName, string $attrName)
    {
        $model = new SkuSpecAttrModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'attr.attrName' => $attrName,
            'attr.dataFlag' => 1,
            'spec.specName' => $specName,
            'spec.dataFlag' => 1,
        );
        $field = 'attr.attrId,attr.attrName,attr.sort';
        $field .= ',spec.specId,spec.specName';
        $result = $model
            ->alias('attr')
            ->join("left join {$prefix}sku_spec spec on attr.specId=spec.specId")
            ->where($where)
            ->field($field)
            ->find();
        return (array)$result;
    }

    /**
     * 删除商品的sku-根据商品id
     * @param int $goodsId 商品id
     * @param object $trans
     * @return bool
     * */
    public function deleteGoodsSkuByGoodsId(int $goodsId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new SkuGoodsSystemModel();
        $result = $model->where(array('goodsId' => $goodsId))->delete();
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 删除商品的sku-根据商品skuId
     * @param int $skuId 商品skuId
     * @param object $trans
     * @return bool
     * */
    public function deleteGoodsSkuBySkuId(int $skuId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new SkuGoodsSystemModel();
        $result = $model->where(array('skuId' => $skuId))->delete();
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 商品sku-基础信息快捷修改
     * @param array $params
     * @return bool
     */
    public function editSkuInfo($params)
    {
        $model = new SkuGoodsSystemModel();
        $where = array(
            'skuId' => null,
            'shopId' => null,
        );
        parm_filter($where, $params);
        unset($params['skuId'], $params['shopId']);
        $result = $model->where($where)->save($params);
        if ($result === false) {
            return false;
        }
        return true;
    }

    /**
     * 获取商品采购员/供应商
     * @param int $goodsId 采购单id
     * @return string
     * */
    public function getPurchaserOrSupplierName(int $goodsId)
    {
        $str = '';
        $goodsDetail = $this->getGoodsInfoById($goodsId, 'purchase_type,purchaser_or_supplier_id', 2);
        if (!empty($goodsDetail['purchaser_or_supplier_id'])) {
            if ($goodsDetail['purchase_type'] == 1) {//市场自采
                $purchaserDetial = (new ShopStaffMemberModule())->getShopStaffMemberDetail($goodsDetail['purchaser_or_supplier_id'], 'username');
                if (!empty($purchaserDetial)) {
                    $str = $purchaserDetial['username'];
                }
            }
            if ($goodsDetail['purchase_type'] == 2) {//供应商直供
                $supplierDetail = (new SupplierModule())->getSupplierDetailById($goodsDetail['purchaser_or_supplier_id'], 'supplierName');
                if (!empty($supplierDetail)) {
                    $str = $supplierDetail['supplierName'];
                }
            }
        }
        return (string)$str;
    }

    /**
     * 商品-获取商品当前库存
     * @param int $goodsId 商品id
     * @param int $skuId skuId
     * @param int $stockType 库存类型(1:库房库存 2:售卖库存 3:在途库存)
     * @return float
     * */
    public function getGoodsCurrentInventory(int $goodsId, $skuId = 0, $stockType = 1)
    {
        $goodsDetail = $this->getGoodsInfoById($goodsId, 'shopId,goodsName,goodsStock,selling_stock', 2);
        if (empty($goodsDetail)) {
            return 0;
        }
        if (in_array($stockType, array(1, 2))) {
            $returnStock = $goodsDetail['goodsStock'];
            if ($stockType != 1) {
                $returnStock = $goodsDetail['selling_stock'];
            }
            if (!empty($skuId)) {
                $skuDetail = $this->getSkuSystemInfoById($skuId, 2);
                if (empty($skuDetail)) {
                    return 0;
                }
                $returnStock = $skuDetail['skuGoodsStock'];
                if ($stockType != 1) {
                    $returnStock = $skuDetail['selling_stock'];
                }
            }
        }
        if ($stockType == 3) {//在途库存
            $shopId = $goodsDetail['shopId'];
            //商品采购-start
            $purchaseBillGoodsMod = new PurchaseBillGoodsModel();
            $prefix = $purchaseBillGoodsMod->tablePrefix;
            $purGoodsWhere = "pur_g.goodsId={$goodsId} and pur_g.skuId={$skuId} and pur_g.purchaseStatus !=-1 ";
            $purGoodsWhere .= " and pur.purchaseStatus !=-1 and pur.shopId={$shopId} and pur.isDelete=0 ";
            $purGoodsField = 'pur_g.id,pur_g.goodsId,pur_g.skuId,pur_g.purchaseId,pur_g.purchaseTotalNum,pur_g.warehouseOkNum';
            $purBillGoods = $purchaseBillGoodsMod
                ->alias('pur_g')
                ->join("left join {$prefix}purchase_bill pur on pur.purchaseId=pur_g.purchaseId")
                ->where($purGoodsWhere)
                ->field($purGoodsField)
                ->select();
            //商品采购-end
            //商品入库-start
            $wareBillGoodsMod = new WarehousingBillGoodsModel();
            $wareGoodsWhere = " ware_g.goodsId={$goodsId} and ware_g.skuId={$skuId}";
            $wareGoodsWhere .= " and ware.shopId={$shopId} and ware.isDelete=0";
            $wareGoodsField = 'ware_g.id,ware_g.goodsId,ware_g.skuId,ware_g.warehousNumTotal,ware_g.warehouseStatus,ware_g.warehouseOkNum';
            $wareGoodsField .= ',ware.billType,ware.relationBillId';
            $wareBillGoods = $wareBillGoodsMod
                ->alias('ware_g')
                ->join("left join {$prefix}warehousing_bill ware on ware.warehousingId=ware_g.warehousingId ")
                ->where($wareGoodsWhere)
                ->field($wareGoodsField)
                ->select();
            //商品入库-end
            $purBillGoodsNum = array_sum(array_column($purBillGoods, 'purchaseTotalNum'));
            $wareGoodsNum = array_sum(array_column($wareBillGoods, 'warehousNumTotal'));
            $returnStock = bc_math($purBillGoodsNum, $wareGoodsNum, 'bcadd', 3);
            foreach ($wareBillGoods as $wareGoodsDetail) {
                if ($wareGoodsDetail['billType'] != 1) {
                    continue;
                }
                foreach ($purBillGoods as $purBillGoodsDetial) {
                    if ($purBillGoodsDetial['purchaseId'] == $wareGoodsDetail['relationBillId']) {
//                        if ($wareGoodsDetail['warehouseStatus'] == 0) {//未入库
//                            $returnStock -= $purBillGoodsDetial['purchaseTotalNum'];
//                        }
                        if ($wareGoodsDetail['warehouseStatus'] == 0) {//未入库
                            $returnStock -= $purBillGoodsDetial['purchaseTotalNum'];
                        }
                        if ($wareGoodsDetail['warehouseOkNum'] > 0) {//已入库
                            $returnStock -= $purBillGoodsDetial['warehouseOkNum'];
                            $returnStock -= $purBillGoodsDetial['warehouseOkNum'];
                        }
                    }
                }
            }
        }
        return (float)$returnStock;
    }

    /**
     * 商品-获取门店商品列表(包含sku商品)
     * @param array $paramsInput
     * -int shopId 门店id
     * -int catid 分类id
     * -string keywords 商品关键字(商品名/编码)
     * -int hideZeroStock 是否隐藏0库存商品(0:不隐藏 1:隐藏)
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供应商)id
     * -int page 页码
     * -int pageSize 每页条数
     * -int export 是否使用分页(0:不使用 1:使用)
     * @return array
     * */
    public function getShopGoodsAndSkuList(array $paramsInput)
    {
        $searchWhere = array(
            'shopId' => '',
            'catid' => '',
            'keywords' => '',//商品关键字(商品名/编码)
            'hideZeroStock' => 0,//是否隐藏0库存商品(0:不隐藏 1:隐藏)
            'purchase_type' => 0,//采购类型(1:市场自采 2:供应商供货)
            'purchaser_or_supplier_id' => 0,//(采购员/供应商)id
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
            'usePage' => 1,//是否使用分页(0:不使用 1:使用)
        );
        parm_filter($searchWhere, $paramsInput);
        $searchWhere['page'] = (int)$searchWhere['page'];
        $searchWhere['pageSize'] = (int)$searchWhere['pageSize'];
        $allGoods = array();//用于储存最终的商品数据
        $goodsModel = new GoodsModel();
        $goodsCatModule = new GoodsCatModule();
        //基础商品信息
        $shopId = (int)$searchWhere['shopId'];
        $goodsWhere = array(
            'shopId' => $shopId,
            'goodsStatus' => 1,
            'goodsFlag' => 1,
            'isBecyclebin' => 0,
        );
        if (!empty($searchWhere['purchase_type'])) {
            $goodsWhere['purchase_type'] = $searchWhere['purchase_type'];
        }
        if (!empty($searchWhere['purchaser_or_supplier_id'])) {
            $goodsWhere['purchaser_or_supplier_id'] = $searchWhere['purchaser_or_supplier_id'];
        }
        if (!empty($searchWhere['catid'])) {
            $shopCatDetail = $goodsCatModule->getShopCatDetailById($searchWhere['catid']);
            if (!empty($shopCatDetail)) {
                if ($shopCatDetail['level'] == 1) {
                    $goodsWhere['shopCatId1'] = $searchWhere['catid'];
                }
                if ($shopCatDetail['level'] == 2) {
                    $goodsWhere['shopCatId2'] = $searchWhere['catid'];
                }
            }
        }
        $goodsField = 'goodsId,goodsSn as goodsCode,goodsName,goodsImg,shopPrice,goodsStock,goodsUnit as purchase_price,unit as unitName,shopCatId1,shopCatId2,goodsSpec as `describe`';
        $goodsList = $goodsModel
            ->where($goodsWhere)
            ->field($goodsField)
            ->select();
        $allShopCatid1Arr = array_unique(array_column($goodsList, 'shopCatId1'));
        $allShopCatid2Arr = array_unique(array_column($goodsList, 'shopCatId2'));
        $allShopCatidArr = array_merge($allShopCatid1Arr, $allShopCatid2Arr);
        $shopCatList = $goodsCatModule->getShopCatListById($allShopCatidArr, 'catId,catName');
        $goodsStockAmountTotal = 0;//库存金额合计
        $keywords = $searchWhere['keywords'];
        $hideZeroStock = $searchWhere['hideZeroStock'];
        foreach ($goodsList as $goodsDetail) {
            $goodsDetail['skuSpecStr'] = '';//规格值
            $goodsDetail['shopCatId1Name'] = '';
            $goodsDetail['shopCatId2Name'] = '';
            $goodsId = $goodsDetail['goodsId'];
            $goodsSkuList = $this->getGoodsSku($goodsId, 2);
            $goodsDetail['skuId'] = 0;
            $goodsId = $goodsDetail['goodsId'];
            $skuId = 0;
            $goodsDetail['goodsStockAmountTotal'] = (float)bc_math($goodsDetail['purchase_price'], $goodsDetail['goodsStock'], 'bcmul', 2);//库存总金额
            $goodsDetail['warehouseNoStock'] = $this->getGoodsCurrentInventory($goodsId, $skuId, 3);//在途库存
            foreach ($shopCatList as $catDetail) {
                if ($goodsDetail['shopCatId1'] == $catDetail['catId']) {
                    $goodsDetail['shopCatId1Name'] = $catDetail['catName'];
                }
                if ($goodsDetail['shopCatId2'] == $catDetail['catId']) {
                    $goodsDetail['shopCatId2Name'] = $catDetail['catName'];
                }
            }
            if (empty($goodsSkuList)) {
                if (!empty($keywords)) {
                    if (mb_substr_count($goodsDetail['goodsName'], $keywords) <= 0 && mb_substr_count($goodsDetail['goodsSn'], $keywords) <= 0) {
                        continue;
                    }
                }
                if ($hideZeroStock == 1 && $goodsDetail['goodsStock'] <= 0) {//过滤0库存
                    continue;
                }
                $goodsStockAmountTotal += $goodsDetail['goodsStockAmountTotal'];
                $allGoods[] = $goodsDetail;
            } else {
                foreach ($goodsSkuList as $skuDetail) {
                    $skuId = (int)$skuDetail['skuId'];
                    $skuDetailRow = $skuDetail['systemSpec'];
                    $goodsDetail['skuId'] = $skuId;
                    $goodsDetail['skuSpecStr'] = $skuDetail['specAttrNameStrTwo'];
                    $goodsDetail['goodsImg'] = (string)$skuDetailRow['skuGoodsImg'];
                    $goodsDetail['goodsCode'] = (string)$skuDetailRow['skuBarcode'];
                    $goodsDetail['shopPrice'] = (float)$skuDetailRow["skuShopPrice"] > 0 ? (float)$skuDetailRow["skuShopPrice"] : 0;
                    $goodsDetail['goodsStock'] = (float)$skuDetailRow["skuGoodsStock"] > 0 ? (float)$skuDetailRow["skuGoodsStock"] : 0;
                    $goodsDetail['purchase_price'] = (float)$skuDetailRow["purchase_price"] > 0 ? (float)$skuDetailRow["purchase_price"] : 0;
                    $goodsDetail['unitName'] = $skuDetailRow["unit"];
                    $goodsDetail['goodsStockAmountTotal'] = (float)bc_math($goodsDetail['purchase_price'], $goodsDetail['goodsStock'], 'bcmul', 2);//库存总金额
                    $goodsDetail['warehouseNoStock'] = $this->getGoodsCurrentInventory($goodsId, $skuId, 3);//在途库存
                    if (!empty($keywords)) {
                        if (mb_substr_count($goodsDetail['goodsName'], $keywords) <= 0 && mb_substr_count($goodsDetail['goodsSn'], $keywords) <= 0) {
                            continue;
                        }
                    }
                    if ($hideZeroStock == 1 && $goodsDetail['goodsStock'] <= 0) {//过滤0库存
                        continue;
                    }
                    $goodsStockAmountTotal += $goodsDetail['goodsStockAmountTotal'];
                    $allGoods[] = $goodsDetail;
                }
            }
        }
        if ($searchWhere['usePage'] == 1) {//使用分页
            $result = array(
                'currPage' => $searchWhere['page'],
                'pageSize' => $searchWhere['pageSize'],
                'root' => array(),
                'start' => 0,
                'total' => 0,
                'totalPage' => 0,
                'goodsStockAmountTotal' => 0,//库存金额合计
            );
        } else {//无分页
            $result = array(
                'list' => array(),
                'goodsStockAmountTotal' => 0,//库存金额合计
            );
        }
        if (empty($allGoods)) {
            return $result;
        } else {
            if ($searchWhere['usePage'] == 1) {
                $result = array(
                    'currPage' => $searchWhere['page'],
                    'pageSize' => $searchWhere['pageSize'],
                    'root' => array_slice($allGoods, ($searchWhere['page'] - 1) * $searchWhere['pageSize'], $searchWhere['pageSize']),
                    'start' => ($searchWhere['page'] - 1) * $searchWhere['pageSize'],
                    'total' => count($allGoods),
                    'goodsStockAmountTotal' => (float)$goodsStockAmountTotal,//库存金额合计
                );
                $result['totalPage'] = ceil($result['total'] / $result['pageSize']);
            }
            if ($searchWhere['usePage'] != 1) {
                $result = array(
                    'list' => $allGoods,
                    'goodsStockAmountTotal' => (float)$goodsStockAmountTotal
                );
            }
        }
        return $result;
    }

}
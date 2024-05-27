<?php
//关联购物车商品 与 商品信息
namespace V3\Model;

use Think\Model\ViewModel;

class CartViewModel extends ViewModel{
    //配置视图
    public $viewFields = array(
        'Cart'=>array('cartId','userId','isCheck','goodsId','goodsAttrId','goodsCnt','skuId','remarks'),
        'Goods'=>array('marketPrice','goodsFlag','goodsStock','goodsCatId1','goodsCatId2','goodsCatId3','goodsId','IntelligentRemark','shopId','goodsThums','goodsName','isSale','shopPrice','isDistribution','firstDistribution','SecondaryDistribution','isMembershipExclusive','memberPrice','integralReward','goodsSpec','buyNum','buyNumLimit','isShopSecKill','shopSecKillNUM','userSecKillNUM','isShopPreSale','SuppPriceDiff','minBuyNum','_on'=>'Cart.goodsId=Goods.goodsId','isLimitBuy','limitCountActivityPrice','limitCount','isFlashSale'),
        //'Shops'=>array('shopName',"shopId", '_on'=>'Goods.ShopId=Shops.shopId')
    );
}

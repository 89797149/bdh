<?php

namespace Home\Model;

use App\Enum\ExceptionCodeEnum;
use App\Enum\Goods\GoodsEnum;
use App\Models\AutoSaleGoodsModel;
use App\Models\SkuGoodsSelfModel;
use App\Models\SkuGoodsSystemModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\PYCode\PYCodeModule;
use App\Modules\Rank\RankModule;
use App\Modules\Shops\ShopCatsModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\ShopStaffMember\ShopStaffMemberModule;
use App\Modules\Supplier\SupplierModule;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品服务类
 */
class GoodsModel extends BaseModel
{


    /**
     * 商品列表
     */
    public function getGoodsList($obj)
    {
        $searchdata = I('get.keyWords', '', 'strip_tags');//指定过滤方式
        $keyWords = iconv('gbk', 'utf-8', $searchdata);//转换编码

        $areaId2 = $obj["areaId2"];
        $areaId3 = $obj["areaId3"];
        $communityId = (int)I("communityId");
        $c1Id = (int)I("c1Id");
        $c2Id = (int)I("c2Id");
        $c3Id = (int)I("c3Id");
        $pcurr = (int)I("pcurr");
        $mark = (int)I("mark", 1);
        $msort = (int)I("msort", 0);
        $prices = I("prices");
        if ($prices != "") {
            $pricelist = explode("_", $prices);
        }
        $brandId = (int)I("brandId");
        $keyWords = WSTAddslashes(urldecode($keyWords));
        $words = array();
        if ($keyWords != "") {
            $words = explode(" ", $keyWords);
        }

        $sqla = "SELECT  g.goodsId,goodsSn,goodsName,goodsThums,goodsStock,g.saleCount,p.shopId,marketPrice,shopPrice,ga.id goodsAttrId ";
        $sqlb = "SELECT max(shopPrice) maxShopPrice  ";
        $sql = " FROM __PREFIX__goods g
				left join __PREFIX__goods_attributes ga on g.goodsId=ga.goodsId and ga.isRecomm=1
				left join __PREFIX__goods_scores gs on gs.goodsId= g.goodsId
				, __PREFIX__shops p ";
        if ($areaId3 > 0 || $communityId > 0) {
            $sql .= " , __PREFIX__shops_communitys sc ";
        }

        if ($brandId > 0) {
            $sql .= " , __PREFIX__brands bd ";
        }
        $where .= " WHERE p.areaId2 = $areaId2 AND g.shopId = p.shopId AND  g.goodsStatus=1 AND g.goodsFlag = 1 and g.isSale=1 ";
        if ($areaId3 > 0 || $communityId > 0) {
            $where .= " AND sc.shopId=p.shopId ";
            if ($areaId3 > 0) {
                $where .= " AND sc.areaId3 = $areaId3";
            }
            if ($communityId > 0) {
                $where .= " AND sc.communityId = $communityId ";
            }
        }
        if ($brandId > 0) {
            $where .= " AND bd.brandId=g.brandId AND g.brandId = $brandId ";
        }
        if ($c1Id > 0) {
            $where .= " AND g.goodsCatId1 = $c1Id";
        }
        if ($c2Id > 0) {
            $where .= " AND g.goodsCatId2 = $c2Id";
        }
        if ($c3Id > 0) {
            $where .= " AND g.goodsCatId3 = $c3Id";
        }

        if (!empty($words)) {
            $sarr = array();
            foreach ($words as $key => $word) {
                if ($word != "") {
                    $sarr[] = "g.goodsName LIKE '%$word%'";
                }
            }
            $where .= " AND (" . implode(" or ", $sarr) . ")";
        }
        $maxrow = $this->queryRow($sqlb . $sql . $where);
        $maxPrice = $maxrow["maxShopPrice"];


        if ($prices != "" && $pricelist[0] >= 0 && $pricelist[1] >= 0) {
            $where .= " AND (g.shopPrice BETWEEN  " . (int)$pricelist[0] . " AND " . (int)$pricelist[1] . ") ";
        }
        $where .= " group by goodsId  ";
        //排序-暂时没有按好评度排
        $orderFile = array('1' => 'saleCount', '6' => 'saleCount', '7' => 'saleCount', '8' => 'shopPrice', '9' => '(totalScore/totalUsers)', '10' => 'saleTime', '' => 'saleTime', '12' => 'saleCount');
        $orderSort = array('0' => 'ASC', '1' => 'DESC');
        $where .= " ORDER BY " . $orderFile[$mark] . " " . $orderSort[$msort] . ",g.goodsId ";

        $pages = $this->pageQuery($sqla . $sql . $where, $pcurr, 30);

        $rs["maxPrice"] = $maxPrice;
        $brands = array();
        $sql = "SELECT b.brandId, b.brandName FROM __PREFIX__brands b, __PREFIX__goods_cat_brands cb WHERE b.brandId = cb.brandId AND b.brandFlag=1 ";
        if ($c1Id > 0) {
            $sql .= " AND cb.catId = $c1Id";
        }
        $sql .= " GROUP BY b.brandId";
        $blist = $this->query($sql);
        for ($i = 0; $i < count($blist); $i++) {
            $brand = $blist[$i];
            $brands[$brand["brandId"]] = array('brandId' => $brand["brandId"], 'brandName' => $brand["brandName"]);
        }
        $rs["brands"] = $brands;
        $rs["pages"] = $pages;
        $gcats["goodsCatId1"] = $c1Id;
        $gcats["goodsCatId2"] = $c2Id;
        $gcats["goodsCatId3"] = $c3Id;
        $rs["goodsNav"] = self::getGoodsNav($gcats);
        return $rs;
    }


    /**
     * 查询商品信息
     */
    public function getGoodsDetails($obj)
    {
        $goodsId = $obj["goodsId"];
        $sql = "SELECT sc.catName,sc2.catName as pCatName, g.*,shop.shopName,shop.deliveryType,ga.id goodsAttrId,ga.attrPrice,ga.attrStock,
				shop.shopAtive,shop.shopTel,shop.shopAddress,shop.deliveryTime,shop.isInvoice, shop.deliveryStartMoney,g.goodsStock,shop.deliveryFreeMoney,shop.qqNo,
				shop.deliveryMoney ,g.goodsSn,g.saleTime,g.isSale,shop.serviceStartTime,shop.serviceEndTime FROM __PREFIX__goods g left join __PREFIX__goods_attributes ga on g.goodsId=ga.goodsId and ga.isRecomm=1, __PREFIX__shops shop, __PREFIX__shops_cats sc
				LEFT JOIN __PREFIX__shops_cats sc2 ON sc.parentId = sc2.catId
				WHERE g.goodsId = $goodsId AND shop.shopId=sc.shopId AND sc.catId=g.shopCatId1 AND g.shopId = shop.shopId AND g.goodsFlag = 1 ";
        $rs = $this->query($sql);

        if (!empty($rs) && $rs[0]['goodsAttrId'] > 0) {
            $rs[0]['shopPrice'] = $rs[0]['attrPrice'];
            $rs[0]['goodsStock'] = $rs[0]['attrStock'];
        }
        return $rs[0];
    }

    /**
     * 获取商品信息-购物车/核对订单用
     */
    public function getGoodsForCheck($obj)
    {
        $goodsId = (int)$obj["goodsId"];
        $goodsAttrId = (int)$obj["goodsAttrId"];
        $sql = "SELECT sc.catName,sc2.catName as pCatName, g.attrCatId,g.goodsThums,g.goodsId,g.goodsName,g.shopPrice,g.goodsStock
				,g.shopId,shop.shopName,shop.qqNo,shop.deliveryType,shop.shopAtive,shop.shopTel,shop.shopAddress,shop.deliveryTime,shop.isInvoice,
				shop.deliveryStartMoney,g.goodsStock,shop.deliveryFreeMoney,shop.deliveryMoney ,g.goodsSn,shop.serviceStartTime startTime,shop.serviceEndTime endTime
				FROM __PREFIX__goods g, __PREFIX__shops shop, __PREFIX__shops_cats sc
				LEFT JOIN __PREFIX__shops_cats sc2 ON sc.parentId = sc2.catId
				WHERE g.goodsId = $goodsId AND shop.shopId=sc.shopId AND sc.catId=g.shopCatId1 AND g.shopId = shop.shopId AND g.goodsFlag = 1 ";
        $rs = $this->queryRow($sql);
        if (!empty($rs) && $rs['attrCatId'] > 0) {
            $sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=" . $rs['attrCatId'] . "
			        and ga.goodsId=" . $rs['goodsId'] . " and id=" . $goodsAttrId;
            $priceAttrs = $this->queryRow($sql);
            if (!empty($priceAttrs)) {
                $rs['attrId'] = $priceAttrs['attrId'];
                $rs['goodsAttrId'] = $priceAttrs['id'];
                $rs['attrName'] = $priceAttrs['attrName'];
                $rs['attrVal'] = $priceAttrs['attrVal'];
                $rs['shopPrice'] = $priceAttrs['attrPrice'];
                $rs['goodsStock'] = $priceAttrs['attrStock'];
            }
        }
        $rs['goodsAttrId'] = (int)$rs['goodsAttrId'];
        return $rs;
    }

    /**
     * 获取商品的属性
     */
    public function getAttrs($obj)
    {
        $id = (int)$obj["goodsId"];
        $shopId = (int)$obj["shopId"];
        $attrCatId = (int)$obj["attrCatId"];
        $goods = array();
        //获取规格属性
        $sql = "select ga.id,ga.attrVal,ga.attrPrice,ga.attrStock,a.attrId,a.attrName,a.isPriceAttr
		            from __PREFIX__attributes a
		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=" . $id . " where
					a.attrFlag=1 and a.catId=" . $attrCatId . " and a.shopId=" . $shopId . " order by a.attrSort asc, a.attrId asc,ga.id asc";
        $attrRs = $this->query($sql);
        if (!empty($attrRs)) {
            $priceAttr = array();
            $attrs = array();
            foreach ($attrRs as $key => $v) {
                if ($v['isPriceAttr'] == 1) {
                    $goods['priceAttrId'] = $v['attrId'];
                    $goods['priceAttrName'] = $v['attrName'];
                    $priceAttr[] = $v;
                } else {
                    $v['attrContent'] = $v['attrVal'];
                    $attrs[] = $v;
                }
            }
            $goods['priceAttrs'] = $priceAttr;
            $goods['attrs'] = $attrs;
        }
        return $goods;
    }

    /**
     * 获取商品相册
     */
    public function getGoodsImgs()
    {
        $goodsId = (int)I("goodsId");
        //根据goodsID获取shopID
        $res = M("goods")->where(['goodsId' => $goodsId, 'goodsFlag' => 1])->field('shopId')->find();
        $shopId = $res['shopId'];
        $sql = "SELECT img.* FROM __PREFIX__goods_gallerys img WHERE img.goodsId = $goodsId and img.shopId = $shopId";
        $rs = $this->query($sql);
        return $rs;
    }


    /**
     * 获取关联商品
     */
    public function getRelatedGoods()
    {

        $goodsId = (int)I("goodsId");
        $sql = "SELECT g.* FROM __PREFIX__goods g, __PREFIX__goods_relateds gr WHERE g.goodsId = gr.relatedGoodsId AND g.goodsStock>0 AND g.goodsStatus = 1 AND gr.goodsId =$goodsId";
        $rs = $this->query($sql);
        return $rs;

    }

    /**
     * 获取上架中的商品
     */
    public function queryOnSaleByPage()
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopCatId1 = (int)I('shopCatId1', 0);
        $shopCatId2 = (int)I('shopCatId2', 0);
        $goodsName = WSTAddslashes(I('goodsName'));
        $sql = "select g.goodsId,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,ga.isRecomm as attIsRecomm from __PREFIX__goods g
				left join __PREFIX__goods_attributes ga on g.goodsId = ga.goodsId and ga.isRecomm = 1
				where g.goodsFlag=1
		     and g.shopId=" . $shopId . " and g.goodsStatus=1 and g.isSale=1 ";
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";
        $sql .= " order by g.goodsId desc";

        return $this->pageQuery($sql);
    }

    /**
     * 获取下架的商品
     */
    public function queryUnSaleByPage()
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopCatId1 = (int)I('shopCatId1', 0);
        $shopCatId2 = (int)I('shopCatId2', 0);
        $goodsName = WSTAddslashes(I('goodsName'));
        $sql = "select g.goodsId,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,ga.isRecomm as attIsRecomm from __PREFIX__goods  g
				left join __PREFIX__goods_attributes ga on g.goodsId = ga.goodsId and ga.isRecomm = 1
				where g.goodsFlag=1
		      and g.shopId=" . $shopId . " and g.isSale=0 ";
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";
        $sql .= " order by g.goodsId desc";
        return $this->pageQuery($sql);
    }

    /**
     * 获取审核中的商品
     */
    public function queryPenddingByPage()
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopCatId1 = (int)I('shopCatId1', 0);
        $shopCatId2 = (int)I('shopCatId2', 0);
        $goodsName = WSTAddslashes(I('goodsName'));
        $sql = "select g.goodsId,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,ga.isRecomm as attIsRecomm from __PREFIX__goods g
				left join __PREFIX__goods_attributes ga on g.goodsId = ga.goodsId and ga.isRecomm = 1
				where g.goodsFlag=1
		     and g.shopId=" . $shopId . " and g.goodsStatus=0 and isSale=1 ";
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";
        $sql .= " order by g.goodsId desc";
        return $this->pageQuery($sql);
    }

    protected $_validate = array(
        array('goodsSn', 'require', '请输入商品编号!', 1),
        array('goodsName', 'require', '请输入商品名称!', 1),
        array('goodsImg', 'require', '请上传商品图片!', 1),
        array('goodsThums', 'require', '请上传商品缩略图!', 1),
        array('marketPrice', 'double', '请输入市场价格!', 1),
        array('shopPrice', 'double', '请输入店铺价格!', 1),
        array('goodsStock', 'integer', '请输入商品库存!', 1),
        array('goodsUnit', 'require', '请输入商品单位!', 1),
        array('goodsCatId1', 'integer', '请选择商城一级分类!', 1),
        array('goodsCatId2', 'integer', '请选择商城二级分类!', 1),
        array('goodsCatId3', 'integer', '请选择商城三级分类!', 1),
        array('shopCatId1', 'integer', '请选择本店一级分类!', 1),
        array('shopCatId2', 'integer', '请选择本店二级分类!', 1),
        array('shopCatId2', 'integer', '请选择本店二级分类!', 1)
    );

    /**
     * 新增商品
     */
//    public function insert($data)
//    {
//        $rd = array('status' => -1);
//        $shopId = (int)session('WST_USER.shopId');
//        if (empty($shopId)) {
//            $shopId = $this->MemberVeri()['shopId'];
//        }
//        $shopId = $shopId ? $shopId : $data['shopId'];
//        //查询商家状态
//        $sql = "select shopStatus from __PREFIX__shops where shopFlag = 1 and shopId=" . $shopId;
//        $shopStatus = $this->query($sql);
//        if (empty($shopStatus)) {
//            $rd['msg'] = '店铺状态异常，请联系平台管理员';
//            $rd['status'] = -2;
//            return $rd;
//        }
//        M()->startTrans();
//        //这里
//        $m = D('goods');
//        if ($m->create()) {
//            if (!empty($m->goodsSn)) {
//                $goodsWhere['goodsFlag'] = 1;
//                $goodsWhere['goodsSn'] = $m->goodsSn;
//                $goodsWhere['shopId'] = $shopId;
//                $goodsInfo = M('goods')->where($goodsWhere)->find();
//                if ($goodsInfo) {
//                    M()->rollback();
//                    $rd['msg'] = '商品编号重复';
//                    return $rd;
//                }
//                $accepSpecAttrString = json_decode(htmlspecialchars_decode(I('specAttrString')), true);
//                $systemTab = M('sku_goods_system system');
//                foreach ($accepSpecAttrString as $sKey => $sval) {
//                    foreach ($sval as $svalKey => $svalVal) {
//                        $systemWhere = [];
//                        $systemWhere['system.skuBarcode'] = $svalVal['systemSpec']['skuBarcode'];
//                        $systemWhere['system.dataFlag'] = 1;
//                        $systemWhere['goods.goodsFlag'] = 1;
//                        $systemWhere['goods.shopId'] = $shopId;
//                        $systemWhere['shop.shopFlag'] = 1;
//                        $systemCount = $systemTab
//                            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
//                            ->join('left join wst_shops shop on shop.shopId=goods.shopId')
//                            ->where($systemWhere)
//                            ->count();
//                        if ($systemCount > 0) {
//                            M()->rollback();
//                            $rd['msg'] = "sku商品编码【{$svalVal['systemSpec']['skuBarcode']}】已存在，请更换其他编码";
//                            return $rd;
//                        }
//                    }
//                }
//            }
//
//            $m->shopId = $shopId;
//            $m->isBest = ((int)I('isBest') == 1) ? 1 : 0;
//            $m->isRecomm = ((int)I('isRecomm') == 1) ? 1 : 0;
//            $m->isNew = ((int)I('isNew') == 1) ? 1 : 0;
//            $m->isHot = ((int)I('isHot') == 1) ? 1 : 0;
//            //如果商家状态不是已审核则所有商品只能在仓库中
//            if ($shopStatus[0]['shopStatus'] == 1) {
//                $m->isSale = ((int)I('isSale') == 1) ? 1 : 0;
//                if (I('isSale') == 1) {
//                    $m->saleTime = date('Y-m-d H:i:s', time());
//                }
//            } else {
//                $m->isSale = 0;
//            }
//            $m->isSale = ($GLOBALS['CONFIG']['isGoodsVerify'] == 1) ? 0 : 1;
//            $m->goodsDesc = I('goodsDesc');
//            $m->attrCatId = (int)I("attrCatId");
//            $m->goodsStatus = ($GLOBALS['CONFIG']['isGoodsVerify'] == 1) ? 0 : 1;
//            $m->createTime = date('Y-m-d H:i:s');
//            $m->brandId = (int)I("brandId");
//            $m->goodsSpec = I("goodsSpec");
//            $m->goodsKeywords = I("goodsKeywords");
//            $m->saleCount = (int)I("saleCount");//销量
//            $m->isDistribution = (int)I("isDistribution", 0);//是否支持分销
//            $m->firstDistribution = I("firstDistribution", 0);//一级分销
//            $m->SecondaryDistribution = I("SecondaryDistribution", 0);//二级分销
//            $m->buyNum = (int)I("buyNum", 0);//限制购买数量
//            $m->goodsCompany = I("goodsCompany");//商品单位
//            $m->isLimitBuy = I("isLimitBuy", 0);//是否限量(限量购)
//            $m->isFlashSale = I("isFlashSale", 0);//限时购(限时购)
//            $m->spec = $_POST['spec'];//规格
//            //$m->tradePrice = I("tradePrice",0); //批发价
//            $m->goodsLocation = I("goodsLocation");//所属货位
//            $m->orderNumLimit = I("orderNumLimit", '-1'); //限制订单数量 -1为不限制
//            $m->isNewPeople = I("isNewPeople", 0, 'intval');//是否新人专享,0：否  1：是
//            $m->stockWarningNum = I("stockWarningNum", 0, 'intval');//库存预警数量
//            $m->integralRate = I("integralRate", 0, 'trim');//可抵扣积分比例
//            $m->buyNumLimit = I('buyNumLimit', '-1');//限制单笔商品数量(-1为不限量)
//            $m->inspectionDiff = I('inspectionDiff');//核货差异正负值（用于产品核货）
//            $m->minBuyNum = I('minBuyNum', '-1');//最小起订量
//            $m->markIcon = I('markIcon');
//            $m->isConvention = I('isConvention','0');//商品类型[0:常规|1:非常规]
//            $m->sortOverweightG = I('sortOverweightG','0');//可超重量[单位必须为g 用于分拣]
//
//            /*  			$m->IntelligentRemark = I("IntelligentRemark");//智能备注
//
//                        $m->SuppPriceDiff = (int)I("SuppPriceDiff");
//                        $m->weightG = (int)I("weightG"); */
//
//
//            $isShopSecKill = (int)I("isShopSecKill");//是否店铺秒杀
//            $ShopGoodSecKillStartTime = I("ShopGoodSecKillStartTime");
//            $ShopGoodSecKillEndTime = I("ShopGoodSecKillEndTime");
//            //判断是否是店铺秒杀
//            if ($isShopSecKill == 1) {
//                if (empty($ShopGoodSecKillStartTime) || empty($ShopGoodSecKillEndTime)) {
//                    M()->rollback();
//                    $rd['msg'] = '如果启动秒杀 开始和结束时间不能为空';
//                    return $rd;
//                }
//
//                $m->isShopSecKill = $isShopSecKill;
//                $m->ShopGoodSecKillStartTime = $ShopGoodSecKillStartTime;
//                $m->ShopGoodSecKillEndTime = $ShopGoodSecKillEndTime;
//
//            }
//
//            //判断是否预售
//            $isShopPreSale = (int)I("isShopPreSale");//是否店铺预售
//            $ShopGoodPreSaleStartTime = I("ShopGoodPreSaleStartTime");
//            $ShopGoodPreSaleEndTime = I("ShopGoodPreSaleEndTime");
//            $bookQuantityEnd = (int)I("bookQuantityEnd");//预售封顶量
//
//            if ($isShopPreSale == 1) {
//
//                if ($isShopSecKill == 1) {
//                    M()->rollback();
//                    $rd['msg'] = '秒杀和预售不能同时使用';
//                    return $rd;
//                }
//
//                if (empty($ShopGoodPreSaleStartTime) || empty($ShopGoodPreSaleEndTime) || empty($bookQuantityEnd)) {
//                    M()->rollback();
//                    $rd['msg'] = '如果启动预售 开始和结束时间不能为空 预售封顶的量也不能为空';
//                    return $rd;
//                }
//
//                $m->isShopPreSale = $isShopPreSale;
//                $m->ShopGoodPreSaleStartTime = $ShopGoodPreSaleStartTime;
//                $m->ShopGoodPreSaleEndTime = $ShopGoodPreSaleEndTime;
//                $m->bookQuantityEnd = $bookQuantityEnd;
//
//
//            }
//
//            //判断是否会员专享 0:否 1：是
//            $isMembershipExclusive = (int)I("isMembershipExclusive");//是否会员专享
//            $memberPrice = I("memberPrice");//会员价
//            $integralReward = I("integralReward");//积分奖励
//            if ($isMembershipExclusive > 0) {
//                $m->isMembershipExclusive = $isMembershipExclusive;
//                $m->memberPrice = $memberPrice;//会员价
//                $m->integralReward = $integralReward;//积分奖励
//            }
//
//            //如果是新人专享，则取消 推荐、精品、新品、热销、会员专享、限时抢、秒杀、预售
//            if ($m->isNewPeople == 1) {
//                $m->isRecomm = 0;
//                $m->isBest = 0;
//                $m->isNew = 0;
//                $m->isHot = 0;
//                $m->isMembershipExclusive = 0;
//                $m->isFlashSale = 0;
//                $m->isLimitBuy = 0;
//                $m->isShopSecKill = 0;
//                $m->isShopPreSale = 0;
//            }
//            if($m->marketPrice <= $m->shopPrice){
//                M()->rollback();
//                $rd['msg'] = '市场价必须大于店铺价';
//            }
//            $goodsId = $m->add();
//            if (false !== $goodsId) {
//                if ($shopStatus[0]['shopStatus'] == 1) {
//                    $rd['status'] = 1;
//                    $rd['msg'] = '添加成功';
//                    $rd['goodsId'] = $goodsId;
//                } else {
//                    M()->rollback();
//                    $rd['status'] = -3;
//                    $rd['msg'] = '添加失败';
//                    return $rd;
//                }
//                //进销存 添加成功后,在对应的云仓也添加一个商品
////                $jxc = D("Merchantapi/Jxc");
////                $jxc->addCloudWarehouseGoods($shopId, I());
//                //插入限时购商品
//                if (I('isFlashSale') == 1) {
//                    $flashSaleId = I('flashSaleId');
//                    if (!empty($flashSaleId)) {
//                        $flashSaleGoodsTab = M('flash_sale_goods');
//                        $flashSaleId = trim($flashSaleId, ',');
//                        $flashSaleIdArr = explode(',', $flashSaleId);
//                        foreach ($flashSaleIdArr as $val) {
//                            $flashGoods['flashSaleId'] = $val;
//                            $flashGoods['goodsId'] = $goodsId;
//                            $flashGoods['addTime'] = date('Y-m-d H:i:s', time());
//                            $flashGoods['isDelete'] = 0;
//                            $flashSaleGoodsTab->add($flashGoods);
//                        }
//                    }
//                }
//
//                //规格属性
//                if ((int)I("attrCatId") > 0) {
//                    //获取商品类型属性
//                    $sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1
//					       and catId=" . intval(I("attrCatId")) . " and shopId=" . $shopId;
//                    $m = M('goods_attributes');
//                    $attrRs = $m->query($sql);
//                    if (!empty($attrRs)) {
//                        $priceAttrId = 0;
//                        foreach ($attrRs as $key => $v) {
//                            if ($v['isPriceAttr'] == 1) {
//                                $priceAttrId = $v['attrId'];
//                                continue;
//                            } else {
//                                $attr = array();
//                                $attr['shopId'] = $shopId;
//                                $attr['goodsId'] = $goodsId;
//                                $attr['attrId'] = $v['attrId'];
//                                $attr['attrVal'] = I('attr_name_' . $v['attrId']);
//                                $m->add($attr);
//                            }
//                        }
//                        if ($priceAttrId > 0) {
//                            $no = (int)I('goodsPriceNo');
//                            $no = $no > 50 ? 50 : $no;
//                            $totalStock = 0;
//                            for ($i = 0; $i <= $no; $i++) {
//                                $name = trim(I('price_name_' . $priceAttrId . "_" . $i));
//                                if ($name == '') continue;
//                                $attr = array();
//                                $attr['shopId'] = $shopId;
//                                $attr['goodsId'] = $goodsId;
//                                $attr['attrId'] = $priceAttrId;
//                                $attr['attrVal'] = $name;
//                                $attr['attrPrice'] = (float)I('price_price_' . $priceAttrId . "_" . $i);
//                                $attr['isRecomm'] = (int)I('price_isRecomm_' . $priceAttrId . "_" . $i);
//                                $attr['attrStock'] = (int)I('price_stock_' . $priceAttrId . "_" . $i);
//                                $totalStock = $totalStock + (int)$attr['attrStock'];
//                                $m->add($attr);
//                            }
//                            //更新商品总库存
//                            $sql = "update __PREFIX__goods set goodsStock=" . $totalStock . " where goodsId=" . $goodsId;
//                            $m->execute($sql);
//
//                            //更新进销存系统商品的库存
//                            //updateJXCGoodsStock($goodsId,$totalStock,2);
//                        }
//                    }
//                }
//                //保存相册
//                $gallery = I("gallery");
//                if ($gallery != '') {
//                    $str = explode(',', $gallery);
//                    foreach ($str as $k => $v) {
//                        if ($v == '') continue;
//                        $str1 = explode('@', $v);
//                        $data = array();
//                        $data['shopId'] = $shopId;
//                        $data['goodsId'] = $goodsId;
//                        $data['goodsImg'] = $str1[0];
//                        $data['goodsThumbs'] = $str1[1];
//                        $m = M('goods_gallerys');
//                        $m->add($data);
//                    }
//                }
//
//                $goodsLocation = I("goodsLocation");//所属货位
//                //将商品添加到商品货位表
//                if (!empty($goodsLocation)) {
//                    $goods = $m->where('goodsId=' . $goodsId . " and shopId=" . $shopId)->find();
//                    $lm = M('location');
//                    $lgm = M('location_goods');
//                    $twoLocationList = $lm->where(array('shopId' => $shopId, 'parentId' => array('GT', 0), 'lFlag' => 1))->select();
//                    $twoLocationList_arr = array();
//                    if (!empty($twoLocationList)) {
//                        foreach ($twoLocationList as $v) {
//                            $twoLocationList_arr[$v['lid']] = $v;
//                        }
//                    }
//                    $lgm->where(array('shopId' => $shopId, 'lid' => array('not in', $goodsLocation), 'goodsId' => $goods['goodsId'], 'lgFlag' => 1))->save(array('lgFlag' => -1));
//                    $goodsLocation_arr = explode(',', $goodsLocation);
//                    $data_t = array();
//                    foreach ($goodsLocation_arr as $v) {
//                        $locationInfo = $lm->where(array('shopId' => $shopId, 'parentId' => array('GT', 0), 'lid' => $v, 'lFlag' => 1))->find();
//                        if (empty($locationInfo)) continue;
//                        $locationGoodsInfo = $lgm->where(array('shopId' => $shopId, 'lparentId' => $twoLocationList_arr[$v]['parentId'], 'lid' => $v, 'goodsId' => $goods['goodsId'], 'lgFlag' => 1))->find();
//                        if (!empty($locationGoodsInfo)) continue;
//                        $data_t[] = array(
//                            'shopId' => $shopId,
//                            'lparentId' => $twoLocationList_arr[$v]['parentId'],
//                            'lid' => $v,
//                            'goodsId' => $goods['goodsId'],
//                            'goodsSn' => $goods['goodsSn'],
//                            'createTime' => date('Y-m-d H:i:s'),
//                            'lgFlag' => 1
//                        );
//                    }
//                    if (!empty($data_t)) $lgm->addAll($data_t);
//                }
//
//                //goodsIdCopy 该字段只用于商品复用为复用商品的goodsId
//                if (!empty(I('goodsIdCopy'))) {
//                    //复用商品的属性(PS:只有该商品下的属性有属性值才会复制)
//                    $goodsIdCopy = I('goodsIdCopy');
//                    $m = M('goods_attributes');
//                    $goodsAttribute = $m->where("goodsId='" . $goodsIdCopy . "'")->select();
//                    if (count($goodsAttribute) > 0) {
//                        $list = $m->where("goodsId='" . $goodsIdCopy . "'")->field('attrId')->select();
//                        if ($list) {
//                            $parentId = [];
//                            foreach ($list as $val) {
//                                $parentId[] = $val['attrId'];
//                            }
//                            sort($parentId);
//                            $parentIdStr = 0;
//                            $parentId = array_unique($parentId);
//                            if (count($parentId) > 0) {
//                                $parentIdStr = implode(',', $parentId);
//                            }
//                            $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
//                            foreach ($parentList as $key => &$val) {
//                                $children = $m->where("attrId='" . $val['attrId'] . "' AND goodsId='" . $goodsIdCopy . "'")->select();
//                                $val['children'] = $children;
//                            }
//                            unset($val);
//                            //复制属性为新增商品的属性
//                            foreach ($parentList as $gkey => $gval) {
//                                if (count($gval['children']) > 0) {
//                                    $firstAttr = $gval;
//                                    $attrId = $firstAttr['attrId'];
//                                    if ($firstAttr['shopId'] != $shopId) {
//                                        unset($firstAttr['children']);
//                                        unset($firstAttr['attrId']);
//                                        $firstAttr['shopId'] = $shopId;
//                                        $attrId = M('attributes')->add($firstAttr);
//                                    }
//                                    foreach ($gval['children'] as $cval) {
//                                        $secondAttr = $cval;
//                                        unset($secondAttr['id']);
//                                        $secondAttr['shopId'] = $shopId;
//                                        $secondAttr['goodsId'] = $goodsId;
//                                        $secondAttr['attrId'] = $attrId;
//                                        $m->add($secondAttr);
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//                M()->commit();
//
//                //后加sku start
//                if (!empty($accepSpecAttrString['specAttrString'])) {
//                    $goodsSkuModel = D('Home/GoodsSku');
//                    $insertGoodsSkuRes = $goodsSkuModel->insertGoodsSkuModel((int)$goodsId, $accepSpecAttrString);
//                    if ($insertGoodsSkuRes['apiCode'] == -1) {
//                        M('goods')->where(['goodsId' => $goodsId])->delete();
//                        $rd['status'] = -1;
//                        $rd['msg'] = $insertGoodsSkuRes['apiInfo'];
//                        return $rd;
//                    }
//                }
//                //后加sku end
//            }
//        } else {
//            M()->rollback();
//            $rd['msg'] = $m->getError();
//        }
//        return $rd;
//    }

    /**
     * 添加商品-验证字段
     * */
    protected function verificationField(array $requestParams)
    {
        if (empty($requestParams['goodsSn'])) {
            return returnData(false, -1, 'error', '商品编码必填');
        }
        if (empty($requestParams['goodsName'])) {
            return returnData(false, -1, 'error', '商品名称必填');
        }
        if (empty($requestParams['goodsImg'])) {
            return returnData(false, -1, 'error', '商品图片必填');
        }
        if (empty($requestParams['marketPrice'])) {
            return returnData(false, -1, 'error', '市场价必填');
        }
        if (empty($requestParams['shopPrice'])) {
            return returnData(false, -1, 'error', '店铺价必填');
        }
        if ($requestParams['marketPrice'] <= $requestParams['shopPrice']) {
            return returnData(false, -1, 'error', '市场价必须大于店铺价');
        }
        if (empty($requestParams['goodsCatId1']) || empty($requestParams['goodsCatId2']) || empty($requestParams['goodsCatId3'])) {
            return returnData(false, -1, 'error', '请完善商城分类');
        }
        if (empty($requestParams['shopCatId1']) || empty($requestParams['shopCatId2'])) {
            return returnData(false, -1, 'error', '请完善店铺分类');
        }
        if ((float)$requestParams['weightG'] <= 0) {
            return returnData(false, -1, 'error', '请填写正确的包装系数');
        }
        if (empty($requestParams['unit'])) {
            return returnData(false, -1, 'error', '请填写商品单位');
        }
        return returnData(true);
    }

    /**
     * 新增商品
     * @params array $login_info
     * @params array $params
     * @return array $data
     * */
    public function insert(array $login_info, array $params)
    {
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '添加失败');
        $shop_id = $login_info['shopId'];
        $shop_module = new ShopsModule();
        $goods_module = new GoodsModule();
        $field = 'shopId,shopName';
        $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
        if (empty($shop_detail)) {
            $error_data['msg'] = '门店信息有误';
            return $error_data;
        }
        $m = new Model();
        $m->startTrans();
        $params['shopId'] = $shop_id;
        $save_res = $goods_module->saveGoodsDetail($params, $m);//保存商品主表信息
        if ($save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            $error_data['msg'] = $save_res['msg'];
            return $error_data;
        }
        $goods_id = $save_res['data']['goodsId'];
        if (!empty($params['gallery'])) {//保存相册信息
            $gallerys_data = explode(',', $params['gallery']);
            $gallerys_save = array();
            foreach ($gallerys_data as $gallerys_key => $gallerys_value) {
                if (empty($gallerys_value)) {
                    continue;
                }
                $gallerys_value_arr = explode('@', $gallerys_value);
                $gallerys_info['shopId'] = $shop_id;
                $gallerys_info['goodsId'] = $goods_id;
                $gallerys_info['goodsImg'] = $gallerys_value_arr[0];
                $gallerys_info['goodsThumbs'] = $gallerys_value_arr[1];
                $gallerys_save[] = $gallerys_info;
            }
            $gallerys_save_res = $goods_module->addGoodsGallerys($gallerys_save, $m);
            if (!$gallerys_save_res) {
                $m->rollback();
                $error_data['msg'] = '商品相册保存失败';
                return $error_data;
            }
        }
        //sku-start
        $sku_params = json_decode(htmlspecialchars_decode($params['specAttrString']), true);
        if (!empty($sku_params['specAttrString'])) {
            $sku_data = $sku_params['specAttrString'];
            $sku_batch_res = $goods_module->addGoodsSkuBatch($goods_id, $sku_data, $m);//添加商品规格
            if ($sku_batch_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                $error_data['msg'] = $sku_batch_res['msg'];
                return $error_data;
            }
        }
        //sku-end

        //商品身份价格-无sku-start
        $rankArr = json_decode(htmlspecialchars_decode($params['rankArr']), true);
        $saveGoodsRankRes = $goods_module->addGoodsRank($goods_id, 0, $rankArr, $m);
        if ($saveGoodsRankRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            $error_data['msg'] = $saveGoodsRankRes['msg'];
            return $error_data;
        }
        //商品身份价格-无sku-end

        //商品日志-start
        $goods_log = array(
            'goodsId' => $goods_id,
        );
        $log_id = $goods_module->addGoodsLog($login_info, $goods_log, '添加商品', $m);
        if (!$log_id) {
            $m->rollback();
            $error_data['msg'] = '商品日志记录失败';
            return $error_data;
        }
        //商品日志-end
        $m->commit();
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '添加成功');
    }

    /**
     * 编辑商品信息
     */
//    public function edit($data = array())
//    {
//        $rd = array('status' => -1);
//        $goodsId = (int)I("id", 0);
//        $shopId = (int)session('WST_USER.shopId');
//        if (empty($shopId)) {
//            $shopId = $this->MemberVeri()['shopId'];
//        }
//        $shopId = $shopId ? $shopId : $data['shopId'];
//
//        //查询商家状态
//        $sql = "select shopStatus from __PREFIX__shops where shopFlag = 1 and shopId=" . $shopId;
//        $shopStatus = $this->queryRow($sql);
//        if (empty($shopStatus)) {
//            $rd['status'] = -2;
//            return $rd;
//        }
//        //加载商品信息
//        $m = D('goods');
//        $goods = $m->where('goodsId=' . $goodsId . " and shopId=" . $shopId)->find();
//        if (empty($goods)) return array('status' => -1, 'msg' => '无效的商品ID！');
//        if ($m->create()) {
//            $if_weightG = I('weightG');
//            $if_SuppPriceDiff = I('SuppPriceDiff');
//            if ($if_weightG <= 0 and $if_SuppPriceDiff == 1) {//该设置不可以小于等于0
//                return array('status' => -1, 'msg' => '称重产品 重量不可为0');
//            }
//            $goodsSn = $m->goodsSn;
//            if (empty($goodsSn)) {
//                return array('status' => -1, 'msg' => '商品编码不能为空');
//            }
//            $goodsWhere = [];
//            $goodsWhere['shopId'] = $shopId;
//            $goodsWhere['goodsSn'] = $goodsSn;
//            $goodsWhere['goodsFlag'] = 1;
//            $goodsSnInfo = $m->where($goodsWhere)->field('goodsId,goodsSn,goodsName')->find();
//            if ($goodsSnInfo && $goodsSnInfo['goodsId'] != $goods['goodsId']) {
//                return array('status' => -1, 'msg' => "商品编码【{$goodsSn}】已存在，请更换其他编码");
//            }
//            $goodsSkuModel = D('Home/GoodsSku');
//            $accepSpecAttrString = json_decode(htmlspecialchars_decode(I('specAttrString')), true);
//            $systemTab = M('sku_goods_system system');
//            foreach ($accepSpecAttrString as $key => $value) {
//                foreach ($value as $wk => $wv) {
//                    $systemSpec = $wv['systemSpec'];
//                    $skuBarcode = $systemSpec['skuBarcode'];
//                    if (!empty($skuBarcode)) {
//                        $systemWhere = [];
//                        $systemWhere['system.skuBarcode'] = $skuBarcode;
//                        $systemWhere['system.dataFlag'] = 1;
//                        $systemWhere['goods.goodsFlag'] = 1;
//                        $systemWhere['goods.shopId'] = $shopId;
//                        $systemWhere['shop.shopFlag'] = 1;
//                        $systemInfo = $systemTab
//                            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
//                            ->join('left join wst_shops shop on shop.shopId=goods.shopId')
//                            ->where($systemWhere)
//                            ->field('goods.goodsId,goods.goodsName')
//                            ->find();
//                        if ($systemInfo && $systemInfo['goodsId'] != $goods['goodsId']) {
//                            return array('status' => -1, 'msg' => "商品sku编码【{$skuBarcode}】已存在，请更换其他编码");
//                        }
//                    }
//                }
//            }
//
//            $m->isBest = ((int)I('isBest') == 1) ? 1 : 0;
//            $m->isRecomm = ((int)I('isRecomm') == 1) ? 1 : 0;
//            $m->isNew = ((int)I('isNew') == 1) ? 1 : 0;
//            $m->isHot = ((int)I('isHot') == 1) ? 1 : 0;
//            //如果商家状态不是已审核则所有商品只能在仓库中
//            if ($shopStatus['shopStatus'] == 1) {
//                $m->isSale = ((int)I('isSale') == 1) ? 1 : 0;
//                if (I('isSale') == 1) {
//                    $m->saleTime = date('Y-m-d H:i:s', time());
//                }
//            } else {
//                $m->isSale = 0;
//            }
//            $m->goodsName = I('goodsName');
//            $m->goodsDesc = I('goodsDesc');
//            $m->attrCatId = (int)I("attrCatId");
//            $m->goodsStatus = ($GLOBALS['CONFIG']['isGoodsVerify'] == 1) ? 0 : 1;
//            $m->brandId = (int)I("brandId");
//            $m->goodsSpec = I("goodsSpec");
//            $m->goodsKeywords = I("goodsKeywords");
//            $m->PreSalePayPercen = (int)I("PreSalePayPercen");//预付款百分比
//            $m->saleCount = (int)I("saleCount");//销量
//            $m->bookQuantityEnd = (int)I("bookQuantityEnd");//预售封顶量
//            $m->isDistribution = (int)I("isDistribution", 0);//是否支持分销
//            $m->firstDistribution = I("firstDistribution", 0);//一级分销
//            $m->SecondaryDistribution = I("SecondaryDistribution", 0);//二级分销
//            $m->buyNum = (int)I("buyNum", 0);//限制购买数量
//            $m->goodsCompany = I("goodsCompany");//商品单位
//            $m->isLimitBuy = I("isLimitBuy", 0);//是否限量(限量购)
//            $m->isFlashSale = I("isFlashSale", 0);//限时购(限时购)
//            //$m->tradePrice = I("tradePrice",0); //批发价
//            $m->goodsLocation = I("goodsLocation");//所属货位
//            $m->isNewPeople = I("isNewPeople", 0, 'intval');//是否新人专享,0：否  1：是
//            $m->stockWarningNum = I("stockWarningNum", 0, 'intval');//库存预警数量
//            $m->integralRate = I("integralRate", 0, 'trim');//可抵扣积分比例
//            $m->isMembershipExclusive = (int)I("isMembershipExclusive");//是否会员专享
//            $m->memberPrice = I("memberPrice");//会员价
//            $m->shopPrice = I("shopPrice");//店铺价
//            $m->marketPrice = I("marketPrice");//市场价
//            if($m->marketPrice <= $m->shopPrice){
//                $rd = array('status' => -1, 'msg' => "市场价必须大于店铺价");
//                return $rd;
//            }
//            $m->goodsStock = I("goodsStock");//库存
//            $m->stockWarningNum = I("stockWarningNum");//库存预警
//            $m->IntelligentRemark = I("IntelligentRemark");//智能备注
//            $m->SuppPriceDiff = I("SuppPriceDiff");//是否补差价
//            $m->weightG = I("weightG");//重量
//            $m->goodsCatId1 = I("goodsCatId1");//商城一级分类
//            $m->goodsCatId2 = I("goodsCatId2");//商城二级分类
//            $m->goodsCatId3 = I("goodsCatId3");//商城三级分类
//            $m->shopCatId1 = I("shopCatId1");//店铺一级分类
//            $m->shopCatId2 = I("shopCatId2");//店铺二级分类
//            $m->brandId = I("brandId");//品牌
//            $m->goodsUnit = I("goodsUnit");//进货价,不晓得为什么用这个字段
//            $m->integralReward = I("integralReward");//积分奖励
//            $m->spec = $_POST['spec'];//规格
//            $m->orderNumLimit = I("orderNumLimit", '-1'); //限制订单数量 -1为不限制
//            $m->goodsSn = I("goodsSn");//商品条码
//            $m->buyNumLimit = I('buyNumLimit', '-1');//限制单笔商品数量(-1为不限量)
//            $m->inspectionDiff = I('inspectionDiff');//核货差异正负值（用于产品核货）
//            $m->minBuyNum = I('minBuyNum', '-1');//最小起订量
//            $m->goodsImg = I('goodsImg');
//            $m->goodsThums = I('goodsThums');
//            $m->markIcon = I('markIcon');
//            $m->isConvention = I('isConvention','0');//商品类型[0:常规|1:非常规]
//            $m->sortOverweightG = I('sortOverweightG','0');//可超重量[单位必须为g 用于分拣]
//            //如果是新人专享，则取消 推荐、精品、新品、热销、会员专享、限时抢、秒杀、预售
//            if ($m->isNewPeople == 1) {
//                $m->isRecomm = 0;
//                $m->isBest = 0;
//                $m->isNew = 0;
//                $m->isHot = 0;
//                $m->isMembershipExclusive = 0;
//                $m->isFlashSale = 0;
//                $m->isLimitBuy = 0;
//                $m->isShopSecKill = 0;
//                $m->isShopPreSale = 0;
//            }
//
//            $rs = $m->where('goodsId=' . $goods['goodsId'])->save();
//            if (false !== $rs) {
//                //进销存 添加成功后,在对应的云仓也添加一个商品
//                //$jxc = D("Merchantapi/Jxc");
//                //$jxc->addCloudWarehouseGoods($shopId,I());
//
//                //更新进销存系统商品的库存
//                //updateJXCGoodsStock($goodsId,I('goodsStock'),2);
//
//                if ($shopStatus['shopStatus'] == 1) {
//                    //$rd['status'] = 1;
//                    $rd = array('status' => 1, 'msg' => "操作成功");
//                } else {
//                    $rd = array('status' => -1, 'msg' => "店铺状态信息异常，请联系管理员");
//                }
//
//                //插入限时购商品
//                $flashSaleGoodsTab = M('flash_sale_goods');
//                $flashSaleGoodsTab->where("goodsId='" . $goodsId . "'")->save(['isDelete' => 1]);
//                if (I('isFlashSale') == 1) {
//                    $flashSaleId = I('flashSaleId');
//                    if (!empty($flashSaleId)) {
//                        $flashSaleId = trim($flashSaleId, ',');
//                        $flashSaleIdArr = explode(',', $flashSaleId);
//                        $existFlashSale = $flashSaleGoodsTab->where("goodsId='" . $goodsId . "' AND isDelete=0")->select();
//                        foreach ($existFlashSale as $val) {
//                            $existFlashSaleId[] = $val['flashSaleId'];
//                        }
//                        foreach ($flashSaleIdArr as $val) {
//                            $flashGoods['flashSaleId'] = $val;
//                            $flashGoods['goodsId'] = $goodsId;
//                            $flashGoods['addTime'] = date('Y-m-d H:i:s', time());
//                            $flashGoods['isDelete'] = 0;
//                            $flashSaleGoodsTab->add($flashGoods);
//                        }
//                    }
//                }
//
//                //删除属性记录
//                //$m->query("delete from __PREFIX__goods_attributes where goodsId=".$goodsId); 和现在的属性冲突,所以注释
//                //规格属性
//                if (intval(I("attrCatId")) > 0) {
//                    //获取商品类型属性列表
//                    $sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1
//					       and catId=" . intval(I("attrCatId")) . " and shopId=" . $shopId;
//                    $m = M('goods_attributes');
//                    $attrRs = $m->query($sql);
//                    if (!empty($attrRs)) {
//                        $priceAttrId = 0;
//                        $recommPrice = 0;
//                        foreach ($attrRs as $key => $v) {
//                            if ($v['isPriceAttr'] == 1) {
//                                $priceAttrId = $v['attrId'];
//                                continue;
//                            } else {
//                                //新增
//                                $attr = array();
//                                $attr['attrVal'] = trim(I('attr_name_' . $v['attrId']));
//                                $attr['attrPrice'] = 0;
//                                $attr['attrStock'] = 0;
//                                $attr['shopId'] = $shopId;
//                                $attr['goodsId'] = $goodsId;
//                                $attr['attrId'] = $v['attrId'];
//                                $m->add($attr);
//                            }
//                        }
//                        if ($priceAttrId > 0) {
//                            $no = (int)I('goodsPriceNo');
//                            $no = $no > 50 ? 50 : $no;
//                            $totalStock = 0;
//
//                            for ($i = 0; $i <= $no; $i++) {
//                                $name = trim(I('price_name_' . $priceAttrId . "_" . $i));
//                                if ($name == '') continue;
//                                $attr = array();
//                                $attr['shopId'] = $shopId;
//                                $attr['goodsId'] = $goodsId;
//                                $attr['attrId'] = $priceAttrId;
//                                $attr['attrVal'] = $name;
//                                $attr['attrPrice'] = (float)I('price_price_' . $priceAttrId . "_" . $i);
//                                $attr['isRecomm'] = (int)I('price_isRecomm_' . $priceAttrId . "_" . $i);
//                                if ($attr['isRecomm'] == 1) {
//                                    $recommPrice = $attr['attrPrice'];
//                                }
//                                $attr['attrStock'] = (int)I('price_stock_' . $priceAttrId . "_" . $i);
//                                $totalStock = $totalStock + (int)$attr['attrStock'];
//                                $m->add($attr);
//                            }
//                            //更新商品总库存
//                            $sql = "update __PREFIX__goods set goodsStock=" . $totalStock;
//                            if ($recommPrice > 0) {
//                                $sql .= ",shopPrice=" . $recommPrice;
//                            }
//                            $sql .= " where goodsId=" . $goodsId;
//                            $m->execute($sql);
//
//                            //更新进销存系统商品的库存
//                            //updateJXCGoodsStock($goodsId,$totalStock,2);
//                        }
//                    }
//                }
//
//                //保存相册
//                $gallery = I("gallery");
//                $m = M('goods_gallerys');
//                //删除相册信息
//                $m->where('goodsId=' . $goods['goodsId'])->delete();
//                if ($gallery != '') {
//                    $str = explode(',', $gallery);
//                    //保存相册信息
//                    foreach ($str as $k => $v) {
//                        if ($v == '') continue;
//                        $str1 = explode('@', $v);
//                        $data = array();
//                        $data['shopId'] = $goods['shopId'];
//                        $data['goodsId'] = $goods['goodsId'];
//                        $data['goodsImg'] = $str1[0];
//                        $data['goodsThumbs'] = $str1[1];
//                        $m->add($data);
//                    }
//                }
//                $goodsLocation = I("goodsLocation");//所属货位
//                //将商品添加到商品货位表
//                if (!empty($goodsLocation)) {
//                    $lm = M('location');
//                    $lgm = M('location_goods');
//                    $twoLocationList = $lm->where(array('shopId' => $shopId, 'parentId' => array('GT', 0), 'lFlag' => 1))->select();
//                    $twoLocationList_arr = array();
//                    if (!empty($twoLocationList)) {
//                        foreach ($twoLocationList as $v) {
//                            $twoLocationList_arr[$v['lid']] = $v;
//                        }
//                    }
//                    $lgm->where(array('shopId' => $shopId, 'lid' => array('not in', $goodsLocation), 'goodsId' => $goods['goodsId'], 'lgFlag' => 1))->save(array('lgFlag' => -1));
//                    $goodsLocation_arr = explode(',', $goodsLocation);
//                    $data_t = array();
//                    foreach ($goodsLocation_arr as $v) {
//                        $locationInfo = $lm->where(array('shopId' => $shopId, 'parentId' => array('GT', 0), 'lid' => $v, 'lFlag' => 1))->find();
//                        if (empty($locationInfo)) continue;
//                        $locationGoodsInfo = $lgm->where(array('shopId' => $shopId, 'lparentId' => $twoLocationList_arr[$v]['parentId'], 'lid' => $v, 'goodsId' => $goods['goodsId'], 'lgFlag' => 1))->find();
//                        if (!empty($locationGoodsInfo)) continue;
//                        $data_t[] = array(
//                            'shopId' => $shopId,
//                            'lparentId' => $twoLocationList_arr[$v]['parentId'],
//                            'lid' => $v,
//                            'goodsId' => $goods['goodsId'],
//                            'goodsSn' => $goods['goodsSn'],
//                            'createTime' => date('Y-m-d H:i:s'),
//                            'lgFlag' => 1
//                        );
//                    }
//                    if (!empty($data_t)) $lgm->addAll($data_t);
//                }
//                //后加sku start
//                if (!empty($accepSpecAttrString['specAttrString'])) {
////                    $insertGoodsSkuRes = $goodsSkuModel->editGoodsSkuModel((int)$goodsId, $accepSpecAttrString);
////                    if ($insertGoodsSkuRes['apiCode'] == -1) {
////                        $rd['status'] = -1;
////                        $rd['msg'] = $insertGoodsSkuRes['apiInfo'];
////                    }
//                }
//                ////后加sku end
//
//                //更新直播商品库信息
//                $LiveplayModel = D('Merchantapi/Liveplay');
//                $LiveplayModel->syncLiveplayGoods(I(),'goods-edit');
//            }
//        } else {
//            $rd['msg'] = $m->getError();
//        }
//        return $rd;
//    }

    /**
     * 商品-修改商品信息
     * @param array $login_info 登陆者信息
     * @param array $params 需要修改的字段
     * */
    public function edit($login_info, $params)
    {
        $error_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息保存失败');
        $shop_id = (int)$login_info['shopId'];
        $goods_id = (int)$params['goodsId'];
        $shop_module = new ShopsModule();
        $field = 'shopId,shopName';
        $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
        if (empty($shop_detail)) {
            $error_data['msg'] = '门店信息有误';
            return $error_data;
        }
        $goods_module = new GoodsModule();
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            $error_data['msg'] = '商品信息有误';
            return $error_data;
        }
        $m = new Model();
        $m->startTrans();
        $save_res = $goods_module->saveGoodsDetail($params, $m);//保存商品主表信息
        if ($save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            $error_data['msg'] = $save_res['msg'];
            return $error_data;
        }
        //重置商品相册信息
        $gallerys_res = $goods_module->deleteGoodsGallerys($goods_id, $m);
        if (!$gallerys_res) {
            $m->rollback();
            $error_data['msg'] = '商品相册重置失败';
            return $error_data;
        }
        if (!empty($params['gallery'])) {//保存相册信息
            $gallerys_data = explode(',', $params['gallery']);
            $gallerys_save = array();
            foreach ($gallerys_data as $gallerys_key => $gallerys_value) {
                if (empty($gallerys_value)) {
                    continue;
                }
                $gallerys_value_arr = explode('@', $gallerys_value);
                $gallerys_info['shopId'] = $shop_id;
                $gallerys_info['goodsId'] = $goods_id;
                $gallerys_info['goodsImg'] = $gallerys_value_arr[0];
                $gallerys_info['goodsThumbs'] = $gallerys_value_arr[1];
                $gallerys_save[] = $gallerys_info;
            }
            $gallerys_save_res = $goods_module->addGoodsGallerys($gallerys_save, $m);
            if (!$gallerys_save_res) {
                $m->rollback();
                $error_data['msg'] = '商品相册保存失败';
                return $error_data;
            }
        }
        //商品日志-start
        $log_id = $goods_module->addGoodsLog($login_info, $goods_detail, '修改商品', $m);
        if (!$log_id) {
            $m->rollback();
            $error_data['msg'] = '商品日志记录失败';
            return $error_data;
        }
        //商品日志-end
        //商品身份价格-无sku-start
        $rankArr = json_decode(htmlspecialchars_decode($params['rankArr']), true);
        $saveGoodsRankRes = $goods_module->addGoodsRank($goods_id, 0, $rankArr, $m);
        if ($saveGoodsRankRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            $error_data['msg'] = $saveGoodsRankRes['msg'];
            return $error_data;
        }
        //商品身份价格-无sku-end
        $m->commit();
        //更新直播商品库信息-start
        //之前的老写法暂时就不要放到事务里了
        if ($shop_detail['openLivePlay'] == 1) {
            $liveplay_model = new \Merchantapi\Model\LiveplayModel();
            $liveplay_model->syncLiveplayGoods($params, 'goods-edit');
        }
        //更新直播商品库信息-end
        return returnData(true);
    }

    /**
     * 获取商品信息
     */
    public function get($parameter)
    {
        $m = M('goods');
        $id = (int)I('id', 0);
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $parameter['shopId'];
        //$goods = $m->where("goodsId=" . $id . " and shopId=" . $shopId)->find();
        $goods = $m->where("goodsId={$id}")->find();//商品复用用的也是该接口,暂不加shopId
        if (empty($goods)) return array();
        $m = M('goods_gallerys');

        $res = M("goods")->where(['goodsId' => $id, 'goodsFlag' => 1])->field('shopId')->find();
        $whereGallery['goodsId'] = $id;
        //$whereGallery['shopId'] = $shopId//这种方式在在复制商品的场景下是有问题的,兼容复制商品功能,shopId应该从商品信息中获取
        $whereGallery['shopId'] = $res['shopId'];
        $goods['gallery'] = (array)$m->where($whereGallery)->order('id asc')->select();

//        $goods['gallery'] = $m->where('goodsId='.$id)->order('id asc')->select();
        //获取规格属性
//        $sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,ga.isRecomm,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent
//		            ,ga.isRecomm from __PREFIX__attributes a
//		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=" . $id . " where
//					a.attrFlag=1 and a.catId=" . $goods['attrCatId'] . " and a.shopId=" . $shopId . " order by a.attrSort asc, a.attrId asc,ga.id asc";
//        $attrRs = $m->query($sql);
//        if (!empty($attrRs)) {
//            $priceAttr = array();
//            $attrs = array();
//            foreach ($attrRs as $key => $v) {
//                if ($v['isPriceAttr'] == 1) {
//                    if ($v['isRecomm'] == 1) {
//                        $goods['recommPrice'] = $v['attrPrice'];
//                    }
//                    $goods['priceAttrId'] = $v['attrId'];
//                    $goods['priceAttrName'] = $v['attrName'];
//                    $priceAttr[] = $v;
//                } else {
//                    //分解下拉和多选的选项
//                    if ($v['attrType'] == 1 || $v['attrType'] == 2) {
//                        $v['opts']['txt'] = explode(',', $v['attrContent']);
//                        if ($v['attrType'] == 1) {
//                            $vs = explode(',', $v['attrVal']);
//                            //保存多选的值
//                            foreach ($vs as $vv) {
//                                $v['opts']['val'][$vv] = 1;
//                            }
//                        }
//                    }
//                    $attrs[] = $v;
//                }
//            }
//            $goods['priceAttrs'] = $priceAttr;
//            $goods['attrs'] = $attrs;
//        }
        $goods['has_rank'] = 0;
//        $goods['rankList'] = array();
        $goods['rankArr'] = array();
        $rankList = (new GoodsModule())->getGoodsRankListByGoodsId($goods['goodsId'], 0);
        if (!empty($rankList)) {
            $goods['rankArr'] = $rankList;
        }
        $goods['goodsSku'] = [];
        $goodsSkuModel = D('Home/GoodsSku');
        $goodsSku = $goodsSkuModel->getGoodsSku(['goodsId' => $goods['goodsId']]);
        if (!empty($goodsSku['data'])) {
            $goods['goodsSku'] = $goodsSku['data'];
        }
        $goods['purchase_type_name'] = GoodsEnum::getPurchaseType()[$goods['purchase_type']];
        $goods['purchaser_or_supplier_name'] = (new GoodsModule())->getPurchaserOrSupplierName($goods['goodsId']);
        return $goods;
    }

    /**
     * 获取商品信息
     * @param int $shopId
     * @param int $goodsId
     */
    public function getShopGoodsInfo(int $shopId, int $goodsId)
    {
        $m = M('goods');
        $goods = $m->where("goodsId={$goodsId}")->find();//商品复用用的也是该接口,暂不加shopId
        if (empty($goods)) return array();
        $m = M('goods_gallerys');
        $res = M("goods")->where(['goodsId' => $goods, 'goodsFlag' => 1])->field('shopId')->find();
        $whereGallery = [];
        $whereGallery['goodsId'] = $goodsId;
        $whereGallery['shopId'] = $res['shopId'];
        $goods['gallery'] = $m->where($whereGallery)->order('id asc')->select();
        //获取规格属性
        $sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,ga.isRecomm,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent
		            ,ga.isRecomm from __PREFIX__attributes a
		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=" . $goodsId . " where
					a.attrFlag=1 and a.catId=" . $goods['attrCatId'] . " and a.shopId=" . $shopId . " order by a.attrSort asc, a.attrId asc,ga.id asc";
        $attrRs = $m->query($sql);
        if (!empty($attrRs)) {
            $priceAttr = array();
            $attrs = array();
            foreach ($attrRs as $key => $v) {
                if ($v['isPriceAttr'] == 1) {
                    if ($v['isRecomm'] == 1) {
                        $goods['recommPrice'] = $v['attrPrice'];
                    }
                    $goods['priceAttrId'] = $v['attrId'];
                    $goods['priceAttrName'] = $v['attrName'];
                    $priceAttr[] = $v;
                } else {
                    //分解下拉和多选的选项
                    if ($v['attrType'] == 1 || $v['attrType'] == 2) {
                        $v['opts']['txt'] = explode(',', $v['attrContent']);
                        if ($v['attrType'] == 1) {
                            $vs = explode(',', $v['attrVal']);
                            //保存多选的值
                            foreach ($vs as $vv) {
                                $v['opts']['val'][$vv] = 1;
                            }
                        }
                    }
                    $attrs[] = $v;
                }
            }
            $goods['priceAttrs'] = $priceAttr;
            $goods['attrs'] = $attrs;
        }
        return getGoodsSku($goods);
    }

    /**
     * 删除商品
     * @param array $loginUserInfo
     * @param int $goodsId 商品id
     * @return array $data
     */
    public function del(array $loginUserInfo, int $goodsId)
    {
        $tab = M('goods');
        $shopId = $loginUserInfo['shopId'];
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsInfo = $tab->where($where)->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '商品信息有误');
        }
        $data = [];
        //$data["goodsFlag"] = -1;
        if ($goodsInfo['goodsStatus'] != 1) {
            $data['goodsFlag'] = -1;
        } else {
            $data['isBecyclebin'] = 1;//移入回收站
        }
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsId'] = $goodsId;
        $rs = $tab->where($where)->save($data);
        if ($rs === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        //更新直播商品库信息
        $LiveplayModel = new \Merchantapi\Model\LiveplayModel();
        $LiveplayModel->syncLiveplayGoods(['ids' => I('id')], 'batchDel');
        //移入商品回收站
        $updateRes = $this->addBecyclebinGoods($loginUserInfo, $goodsId);
        if (!$updateRes) {
            return returnData(false, -1, 'error', '操作失败');
        }
        //商品日志-start
        $this->addGoodsLog($loginUserInfo, $goodsInfo, '移入回收站');
        //商品日志-end
        return returnData(true);
    }

    /**
     * 批量删除商品
     * @param array $loginUserInfo
     * @param string|array $goodsId
     * @return array $data
     */
    public function batchDel($loginUserInfo, $goodsId)
    {
        $goodsTab = M('goods');
        $shopId = $loginUserInfo['shopId'];
        //$data["goodsFlag"] = -1;
        //$data["isBecyclebin"] = 1;//移入回收站
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsId'] = ['IN', $goodsId];
        $goods_list = $goodsTab->where($where)->field('goodsId,goodsStatus')->select();
        M()->startTrans();
        foreach ($goods_list as $item) {
            $data = [];
            if ($item['goodsStatus'] != 1) {
                $data["goodsFlag"] = -1;
            } else {
                $data["isBecyclebin"] = 1;//移入回收站
            }
            $rs = $goodsTab->where(array('goodsId' => $item['goodsId']))->save($data);
            if ($rs === false) {
                M()->rollback();
                return returnData(false, -1, 'error', '操作失败');
            }
        }
        M()->commit();
        //$rs = $goodsTab->where($where)->save($data);
//        if ($rs === false) {
//            return returnData(false, -1, 'error', '操作失败');
//        }
        //更新直播商品库信息
        $LiveplayModel = new \Merchantapi\Model\LiveplayModel();
        $LiveplayModel->syncLiveplayGoods(I(), 'batchDel');
        //移入商品回收站
        $updateRes = $this->addBecyclebinGoods($loginUserInfo, $goodsId);
        if (!$updateRes) {
            return returnData(false, -1, 'error', '操作失败');
        }
        //商品操作日志-start
        $goodsIdArr = explode(',', $goodsId);
        foreach ($goodsIdArr as $item) {
            $where = [];
            $where['goodsId'] = $item;
            $goodsInfo = $goodsTab->where($where)->find();
            $this->addGoodsLog($loginUserInfo, $goodsInfo, '移入回收站');
        }
        //商品操作日志-end
        return returnData(true);
    }

    /**
     * 批量修改商品状态
     * @param $loginUserInfo 当前登陆者信息
     * @param $requestParams <p>
     * string ids 商品id,多个逗号分隔
     * int tamk 状态值【0:否|1:是】
     * code string 操作类型【isRecomm:推荐|isBes:精品|isNew:新品|isHot:热销|isSale:上下架】
     * </p>
     */
    public function goodsSet(array $loginUserInfo, array $requestParams)
    {
        $shopId = (int)$loginUserInfo['shopId'];
        $code = WSTAddslashes($requestParams['code']);
        $tamk = $requestParams['tamk'];
        $codeArr = array('isBest', 'isNew', 'isHot', 'isRecomm', 'isSale');
        if (in_array($code, $codeArr)) {
            $goodsTab = M('goods');
            $save = [];
            $save[$code] = $tamk;
            $ids = self::formatIn(",", I('ids'));
            if ($code == 'isSale') {
                $save['saleTime'] = date('Y-m-d H:i:s', time());
            }
            $where = [];
            $where['goodsId'] = ['IN', $ids];
            $goodsList = $goodsTab->where($where)->select();
            foreach ($goodsList as $value) {
                if (in_array($value['goodsStatus'], [-1, 0])) {
                    $msg = "商品【{$value['goodsName']}】暂未通过审核，不能操作";
                    return returnData(false, -1, 'error', $msg);
                }
                //商品操作日志-start
                $remark = '';
                if ($code == 'isRecomm') {
                    $remark = '修改推荐状态';
                } elseif ($code == 'isBes') {
                    $remark = '修改精品状态';
                } elseif ($code == 'isNew') {
                    $remark = '修改新品状态';
                } elseif ($code == 'isHot') {
                    $remark = '修改热销状态';
                } elseif ($code == 'isSale') {
                    $remark = '修改上下架状态';
                }
                $this->addGoodsLog($loginUserInfo, $value, $remark);
                //商品操作日志-end
            }
            $where = [];
            $where['shopId'] = $shopId;
            $where['goodsId'] = ['IN', $ids];
            $data = $goodsTab->where($where)->save($save);
            if ($data === false) {
                return returnData(false, -1, 'error', '操作失败');
            }
            if ($code == 'isSale') {
                //更新直播商品库信息
                $LiveplayModel = new \Merchantapi\Model\LiveplayModel();
                $LiveplayModel->syncLiveplayGoods(I(), 'goods-sale');
            }
        }
        return returnData(true);
    }

    /**
     * PS:废弃,请用goodsSet方法
     * 批量上架/下架商品
     */
    public function sale()
    {
        $rd = array('status' => -1);
        $m = M('goods');
        $isSale = (int)I('isSale');
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $ids = self::formatIn(",", I('ids'));
        if ($isSale == 1) {
            //核对店铺状态
            $sql = "select shopStatus from __PREFIX__shops where shopId=" . $shopId;
            $shopRs = $m->query($sql);
            if ($shopRs[0]['shopStatus'] != 1) {
                $rd['status'] = -3;
                return $rd;
            }
            //核对商品是否符合上架的条件
            $sql = "select g.goodsId from __PREFIX__goods g,__PREFIX__shops_cats sc2,__PREFIX__goods_cats gc3
	 		  	    where sc2.shopId=$shopId and g.shopCatId2=sc2.catId and sc2.catFlag=1 and sc2.isShow=1 and g.goodsCatId3=gc3.catId and gc3.catFlag=1 and gc3.isShow=1
	 		  	    and g.goodsId in(" . $ids . ")";

            $goodsRs = $m->query($sql);

            if (count($goodsRs) > 0) {
                $rd['num'] = 0;
                foreach ($goodsRs as $key => $v) {
                    //商品上架操作
                    $data = array();
                    $data["isSale"] = 1;
                    $rs = $m->where("shopId=" . $shopId . " and goodsId =" . $v['goodsId'])->save($data);
                    if (false !== $rs) {
                        $rd['num']++;
                    }
                }
                $rd['status'] = (count(explode(',', $ids)) == $rd['num']) ? 1 : 2;
            } else {
                $rd['status'] = -2;
            }
        } else {
            //商品下架操作
            $data = array();
            $data["isSale"] = 0;
            $rs = $m->where("shopId=" . $shopId . " and goodsId in(" . $ids . ")")->save($data);
            if (false !== $rs) {
                $rd['status'] = 1;
            }
        }

        //更新直播商品库信息
        $LiveplayModel = new \Merchantapi\Model\LiveplayModel();
        $LiveplayModel->syncLiveplayGoods(I(), 'goods-sale');

        return $rd;
    }

    /**
     * 获取店铺商品列表
     */
    public function getShopsGoods($shopId = 0)
    {

        $shopId = ($shopId > 0) ? $shopId : (int)I("shopId");
        $ct1 = (int)I("ct1");
        $ct2 = (int)I("ct2");
        $msort = (int)I("msort");//排序標識

        $sprice = WSTAddslashes(I("sprice"));//开始价格
        $eprice = WSTAddslashes(I("eprice"));//结束价格
        //$goodsName = I("goodsName");//搜索店鋪名
        $goodsName = WSTAddslashes(urldecode(I("goodsName")));//搜索店鋪名
        $words = array();
        if ($goodsName != "") {
            $words = explode(" ", $goodsName);
        }
        $sql = "SELECT sp.shopName, g.saleCount totalnum, sp.shopId ,g.goodsStock, g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn,ga.id goodsAttrId
						FROM __PREFIX__goods g left join __PREFIX__goods_attributes ga on g.goodsId = ga.goodsId and ga.isRecomm=1,__PREFIX__shops sp
						WHERE g.shopId = sp.shopId AND sp.shopFlag=1 AND sp.shopStatus=1 AND g.goodsFlag = 1 AND g.isSale = 1 AND g.goodsStatus = 1 AND g.shopId = $shopId";

        if ($ct1 > 0) {
            $sql .= " AND g.shopCatId1 = $ct1 ";
        }
        if ($ct2 > 0) {
            $sql .= " AND g.shopCatId2 = $ct2 ";
        }
        if ($sprice != "") {
            $sql .= " AND g.shopPrice >= '$sprice' ";
        }
        if ($eprice != "") {
            $sql .= " AND g.shopPrice <= '$eprice' ";
        }

        if (!empty($words)) {
            $sarr = array();
            foreach ($words as $key => $word) {
                if ($word != "") {
                    $sarr[] = "g.goodsName LIKE '%$word%'";
                }
            }
            $sql .= " AND (" . implode(" or ", $sarr) . ")";
        }

        if ($msort == 1) {//综合
            $sql .= " ORDER BY g.saleCount DESC ";
        } else if ($msort == 2) {//人气
            $sql .= " ORDER BY g.saleCount DESC ";
        } else if ($msort == 3) {//销量
            $sql .= " ORDER BY g.saleCount DESC ";
        } else if ($msort == 4) {//价格
            $sql .= " ORDER BY g.shopPrice ASC ";
        } else if ($msort == 5) {//价格
            $sql .= " ORDER BY g.shopPrice DESC ";
        } else if ($msort == 6) {//好评

        } else if ($msort == 7) {//上架时间
            $sql .= " ORDER BY g.saleTime DESC ";
        }
        $rs = $this->pageQuery($sql, I('p'), 30);
        return $rs;

    }


    /**
     * 获取店铺商品列表
     */
    public function getHotGoods($shopId)
    {
        $hotgoods = S("WST_CACHE_HOT_GOODS_" . $shopId);
        if (!$hotgoods) {
            //热销排名
            $sql = "SELECT sp.shopName, g.saleCount totalnum, sp.shopId , g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn
							FROM __PREFIX__goods g,__PREFIX__shops sp
							WHERE g.shopId = sp.shopId AND g.goodsFlag = 1 AND sp.shopFlag=1 AND sp.shopStatus=1 AND g.isSale = 1 AND g.goodsStatus = 1 AND sp.shopId = $shopId
							ORDER BY g.saleCount desc limit 5";
            $hotgoods = $this->query($sql);
            S("WST_CACHE_HOT_GOODS_" . $shopId, $hotgoods, 86400);
        }
        for ($i = 0; $i < count($hotgoods); $i++) {
            $hotgoods[$i]["goodsName"] = WSTMSubstr($hotgoods[$i]["goodsName"], 0, 25);
        }
        return $hotgoods;
    }

    /**
     * 获取商品库存
     */
    public function getGoodsStock($data)
    {
        $goodsId = $data['goodsId'];
        $isBook = $data['isBook'];
        $goodsAttrId = $data['goodsAttrId'];
        if ($isBook == 1) {
            $sql = "select goodsId,(goodsStock+bookQuantity) as goodsStock from __PREFIX__goods where isSale=1 and goodsFlag=1 and goodsStatus=1 and goodsId=" . $goodsId;
        } else {
            $sql = "select goodsId,goodsStock,attrCatId from __PREFIX__goods where isSale=1 and goodsFlag=1 and goodsStatus=1 and goodsId=" . $goodsId;
        }
        $goods = $this->query($sql);
        if ($goods[0]['attrCatId'] > 0) {
            $sql = "select ga.id,ga.attrStock from __PREFIX__goods_attributes ga where ga.goodsId=" . $goodsId . " and id=" . $goodsAttrId;
            $priceAttrs = $this->query($sql);
            if (!empty($priceAttrs)) $goods[0]['goodsStock'] = $priceAttrs[0]['attrStock'];
        }
        if (empty($goods)) return array();
        return $goods[0];
    }


    /**
     * 查询商品简单信息
     */
    public function getGoodsInfo($goodsId, $goodsAttrId)
    {
        $sql = "SELECT g.attrCatId,g.goodsId,g.goodsName,g.goodsStock,g.bookQuantity,g.isBook,g.isSale FROM __PREFIX__goods g WHERE g.goodsId = $goodsId AND g.goodsFlag = 1 AND g.goodsStatus = 1";
        $rs = $this->queryRow($sql);
        if (!empty($rs) && $rs['attrCatId'] > 0) {
            $sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=" . $rs['attrCatId'] . "
			        and ga.goodsId=" . $rs['goodsId'] . " and id=" . $goodsAttrId;
            $priceAttrs = $this->query($sql);
            if (!empty($priceAttrs)) $rs['goodsStock'] = $priceAttrs[0]['attrStock'];
        }
        return $rs;

    }

    /**
     * 查询商品简单信息
     */
    public function getGoodsSimpInfo($goodsId, $goodsAttrId)
    {
        $sql = "SELECT g.*,sp.shopId,sp.shopName,sp.deliveryFreeMoney,sp.deliveryMoney,sp.deliveryStartMoney,sp.isInvoice,sp.serviceStartTime startTime,sp.serviceEndTime endTime,sp.deliveryType
				FROM __PREFIX__goods g, __PREFIX__shops sp
				WHERE g.shopId = sp.shopId AND g.goodsId = $goodsId AND g.isSale=1 AND g.goodsFlag = 1 AND g.goodsStatus = 1";
        $rs = $this->queryRow($sql);
        if (!empty($rs) && $rs['attrCatId'] > 0) {
            $sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=" . $rs['attrCatId'] . "
			        and ga.goodsId=" . $rs['goodsId'] . " and id=" . $goodsAttrId;
            $priceAttrs = $this->queryRow($sql);
            if (!empty($priceAttrs)) {
                $rs['attrId'] = $priceAttrs['attrId'];
                $rs['goodsAttrId'] = $priceAttrs['id'];
                $rs['attrName'] = $priceAttrs['attrName'];
                $rs['attrVal'] = $priceAttrs['attrVal'];
                $rs['shopPrice'] = $priceAttrs['attrPrice'];
                $rs['goodsStock'] = $priceAttrs['attrStock'];
            }
        }
        $rs['goodsAttrId'] = (int)$rs['goodsAttrId'];
        return $rs;

    }


    /**
     * 获取商品类别导航
     */
    public function getGoodsNav($obj = array())
    {
        $goodsId = (int)I("goodsId");
        if ($goodsId > 0) {
            $sql = "SELECT goodsCatId1,goodsCatId2,goodsCatId3 FROM __PREFIX__goods WHERE goodsId = $goodsId";
            $rs = $this->queryRow($sql);
        } else {
            $rs = $obj;
        }
        $gclist = M('goods_cats')->cache('WST_CACHE_GOODS_CAT_URL', 31536000)->where('isShow = 1')->field('catId,catName')->order('catId')->select();
        $catslist = array();
        foreach ($gclist as $key => $gcat) {
            $catslist[$gcat["catId"]] = $gcat;
        }

        $data[] = $catslist[$rs["goodsCatId1"]];
        $data[] = $catslist[$rs["goodsCatId2"]];
        $data[] = $catslist[$rs["goodsCatId3"]];
        return $data;
    }

    /**
     * 查询商品属性价格及库存
     */
    public function getPriceAttrInfo()
    {
        $goodsId = (int)I("goodsId");
        $id = (int)I("id");
        $sql = "select id,attrPrice,attrStock from  __PREFIX__goods_attributes where goodsId=" . $goodsId . " and id=" . $id;
        $rs = $this->query($sql);
        return $rs[0];
    }

    /**
     * 修改商品库存
     * @param array $loginUserInfo
     * @param int $goodsId 商品id
     * @param float $stock 商品库存
     */
    public function editStock(array $loginUserInfo, $goodsId, $stock)
    {
        $goodsTab = M('goods');
        $where = [];
        $where['goodsId'] = $goodsId;
        $goodsInfo = $goodsTab->where($where)->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '商品信息有误');
        }
        $where = [];
        $where['goodsId'] = $goodsId;
        $save = [];
        $save['goodsStock'] = $stock;
        $data = $goodsTab->where($where)->save($save);
        if ($data === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        //商品日志-start
        $this->addGoodsLog($loginUserInfo, $goodsInfo, '修改商品库存');
        //商品日志-end
        return returnData(true);
    }

    /**
     * 修改商品库存,商品编号,价格
     * @param array $loginUserInfo
     * @param int $goodsId 商品名称
     * @param string $vfield 字段名称
     * @param string $vtext 字段值
     * @param array $data
     */
    public function editGoodsBase($loginUserInfo, $goodsId, $vfield, $vtext)
    {
        $shopId = $loginUserInfo['shopId'];
        $goodsTab = M('goods');
        $where = [];
        $where['goodsFlag'] = 1;
        $where['goodsId'] = $goodsId;
        $goodsInfo = $goodsTab->where($where)->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '商品信息有误');
        }
        if ($vfield == 'shopPrice') {
//            if ((float)$goodsInfo['marketPrice'] <= (float)$vtext) {
//                return returnData(false, -1, 'error', '市场价必须大于零售价');
//            }
        }
        if ($vfield == 'selling_stock') {
            if ((float)$vtext < 0) {
                return returnData(false, -1, 'error', '售卖库存不得小于0');
            }
        }
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsId'] = $goodsId;
        $save = [];
        $save[$vfield] = $vtext;
        $data = M('goods')->where($where)->save($save);
        if ($data === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        $remark = '';
        if ($vfield == 'goodsStock') {
            $remark = '修改库存';
        } elseif ($vfield == 'shopPrice') {
            $remark = '修改店铺价格';
        } elseif ($vfield == 'shopGoodsSort') {
            $remark = '修改商品排序';
        } elseif ($vfield == 'selling_stock') {
            $remark = '修改商品售卖库存';
        }
        if (!empty($remark)) {
            $this->addGoodsLog($loginUserInfo, $goodsInfo, $remark);
        }
        return returnData(true);
    }

    public function getKeyList($areaId2)
    {
        $keywords = WSTAddslashes(I("keywords"));
        $m = M('goods');
        $sql = "select DISTINCT goodsName as searchKey from __PREFIX__goods g,__PREFIX__shops sp  where sp.areaId2=$areaId2 and g.shopId=sp.shopId and goodsStatus=1 and goodsFlag=1 and goodsName like '%$keywords%' Order by saleCount desc, goodsName asc limit 10";
        $rs = $this->query($sql);
        return $rs;
    }


    /**
     * 弃用,改用goodsSet方法
     * 修改 推荐/精品/新品/热销/上架
     */
    public function changSaleStatus()
    {
        $rdata = array("status" => -1);
        $goodsId = (int)I("goodsId");
        $tamk = (int)I("tamk");
        $flag = (int)I("flag");
        $data = array();
        if ($tamk == 0) {
            $tamk = 1;
        } else {
            $tamk = 0;
        }
        if ($flag == 1) {
            $data["isRecomm"] = $tamk;
        } else if ($flag == 2) {
            $data["isBest"] = $tamk;
        } else if ($flag == 3) {
            $data["isNew"] = $tamk;
        } else if ($flag == 4) {
            $data["isHot"] = $tamk;
        } else if ($flag == 5) {
            $data["isSale"] = $tamk;
        }

        M('goods')->where("goodsId=$goodsId")->save($data);
        $rdata["status"] = 1;
        $rdata["msg"] = '修改成功';
        return $rdata;
    }

    /**
     * 获取商品历史浏览记录(取最新10條)
     */
    function getViewGoods()
    {
        $m = M();
        $viewGoods = WSTAddslashes(cookie("viewGoods"));
        $viewGoods = array_reverse($viewGoods);
        $goodIds = 0;
        if (!empty($viewGoods)) {
            $goodIds = implode(",", $viewGoods);
        }
        //热销排名
        $sql = "SELECT g.saleCount totalnum, g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn FROM __PREFIX__goods g
				WHERE g.goodsId in ($goodIds) AND g.goodsFlag = 1 AND g.isSale = 1 AND g.goodsStatus = 1
				ORDER BY FIELD(g.goodsId,$goodIds) limit 10";

        $goods = $m->query($sql);
        for ($i = 0; $i < count($goods); $i++) {
            $goods[$i]["goodsName"] = WSTMSubstr($goods[$i]["goodsName"], 0, 25);
        }
        return $goods;

    }

    /**
     * 处理表格中的数据
     * @param string|int $value 表格中获取到的值
     * @param $data_type (数据类型 1:字符串 2:整型(0|1) 3:整型(-1|1))
     * */
    public function handleGetValue($value, $data_type = 1)
    {
        $true_value = trim($value);
        if (empty($value) && $data_type == 1) {
            return '';
        }
        if ($data_type == 2) {
            if (empty($value) || $value == '否') {
                $true_value = 0;
            }
            if (!empty($value) && $value == '否') {
                $true_value = 0;
            }
            if (!empty($value) && $value == '是') {
                $true_value = 1;
            }
        }
        if ($data_type == 3) {
            if (empty($value) || $value == '否') {
                $true_value = -1;
            }
            if (!empty($value) && $value == '否') {
                $true_value = -1;
            }
            if (!empty($value) && $value == '是') {
                $true_value = 1;
            }
        }
        return $true_value;
    }

    /**
     * 上传商品数据
     */
    public function importGoods($params)
    {
        ini_set('memory_limit', '-1');
        $obj_reader = WSTReadExcel($params['file']['savepath'] . $params['file']['savename']);
        $obj_reader->setActiveSheetIndex(0);
        $sheet = $obj_reader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        //$cells = $sheet->getHighestColumn();
//        $os_type = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 1 : 2;
        $shopId = $params['shopId'];
        $import_num = 0;//成功导入商品数量
        //循环读取每个单元格的数据
        $error = array();//返回错误信息
        $pyModule = new PYCodeModule();
        $goodsModule = new GoodsModule();
        $goodsCatModule = new GoodsCatModule();
        $shopCatModule = new ShopCatsModule();
        $rankModule = new RankModule();
        $addGoodsData = array();
        $insertGoodsData = array();
        $updateGoodsData = array();
        $editGoodsData = array();
        $updateSkuData = array();
        $insertSkuData = array();
        $rankArrData = array();

        $allGoodsSn = array();
        for ($row = 3; $row <= $rows; $row++) {//行数是以第3行开始
            //商品基本信息只添加/更新,不删除
            $goodsSn = trim($sheet->getCell("A" . $row)->getValue());//商品编号
            $plu_code = trim($sheet->getCell("B" . $row)->getValue());//PLU编码
            $goodsName = trim($sheet->getCell("C" . $row)->getValue());//商品名称
            $skuBarcode = trim($sheet->getCell("D" . $row)->getValue());//sku编码
            $skuSpecStr = trim($sheet->getCell("E" . $row)->getValue());//sku属性拼接值
            $goodsCatId3Name = trim($sheet->getCell("F" . $row)->getValue());//第三级商品商城分类名称
            $shopCatId2Name = trim($sheet->getCell("G" . $row)->getValue());//第二级商品门店分类名称
            $isSale = trim($sheet->getCell("H" . $row)->getValue());//是否上架
            $isSale = $isSale == '是' ? 1 : 0;
            $goodsSpec = trim($sheet->getCell("I" . $row)->getValue());//商品介绍
            $marketPrice = trim($sheet->getCell("J" . $row)->getValue());//市场价格
            $shopPrice = trim($sheet->getCell("K" . $row)->getValue());//店铺价格
            $memberPrice = trim($sheet->getCell("L" . $row)->getValue());//会员价格
            $goodsUnit = trim($sheet->getCell("M" . $row)->getValue());//进货价格
            $integralReward = trim($sheet->getCell("N" . $row)->getValue());//奖励积分
            $integralRate = trim($sheet->getCell("O" . $row)->getValue());//积分比例
            $goodsStock = trim($sheet->getCell("P" . $row)->getValue());//库房库存
            $selling_stock = trim($sheet->getCell("Q" . $row)->getValue());//售卖库存
            $stockWarningNum = trim($sheet->getCell("R" . $row)->getValue());//库存预警
            $virtualSales = trim($sheet->getCell("S" . $row)->getValue());//虚拟销量
            $orderNumLimit = trim($sheet->getCell("T" . $row)->getValue());//限制下单次数
            $buyNumLimit = trim($sheet->getCell("U" . $row)->getValue());//限制单笔商品数量
            $delayed = trim($sheet->getCell("V" . $row)->getValue());//商品延时
            $spec = trim($sheet->getCell("W" . $row)->getValue());//产品参数
            $rankPriceStr = trim($sheet->getCell("X" . $row)->getValue());//身份价格字符串
            $isNewPeople = trim($sheet->getCell("Y" . $row)->getValue());//新人专享
            $isNewPeople = $isNewPeople == '是' ? 1 : 0;
            $isRecomm = trim($sheet->getCell("Z" . $row)->getValue());//推荐
            $isRecomm = $isRecomm == '是' ? 1 : 0;
            $isBest = trim($sheet->getCell("AA" . $row)->getValue());//精品
            $isBest = $isBest == '是' ? 1 : 0;
            $isNew = trim($sheet->getCell("AB" . $row)->getValue());//新品
            $isNew = $isNew == '是' ? 1 : 0;
            $isHot = trim($sheet->getCell("AC" . $row)->getValue());//热销
            $isHot = $isHot == '是' ? 1 : 0;
            $isMembershipExclusive = trim($sheet->getCell("AD" . $row)->getValue());//会员专享
            $isMembershipExclusive = $isMembershipExclusive == '是' ? 1 : 0;
            $isConvention = trim($sheet->getCell("AE" . $row)->getValue());//常规商品
            $isConvention = $isConvention == '是' ? 0 : 1;
            $IntelligentRemark = trim($sheet->getCell("AF" . $row)->getValue());//处理方式
            $shopGoodsSort = trim($sheet->getCell("AG" . $row)->getValue());//商品排序
            $markIcon = trim($sheet->getCell("AH" . $row)->getValue());//商品标签
            $unit = trim($sheet->getCell("AI" . $row)->getValue());//商品单位
            $SuppPriceDiff = trim($sheet->getCell("AK" . $row)->getValue());//商品称重
            $SuppPriceDiff = $SuppPriceDiff == '是' ? 1 : -1;
            $sortOverweightG = trim($sheet->getCell("AL" . $row)->getValue());//可超重量
            if (empty($goodsSn)) {
                $error[] = "第{$row}行商品编码不能为空";
                continue;
            }
            if (empty($skuBarcode) && empty($goodsName)) {
                $error[] = "第{$row}行商品名称不能为空";
                continue;
            }
            $allGoodsSn[] = $goodsSn . '@' . $skuBarcode;
            $py_code = '';
            $py_initials = '';
            if (!empty($goodsName)) {
                $py_code_detail = $pyModule->getFullSpell($goodsName);
                $py_code = $py_code_detail['py_code'];
                $py_initials = $py_code_detail['py_initials'];
            }
            if (!empty($plu_code)) {
                //plu_code不为空需校验plu_code必须为4位数字且不得0开头，不得大于4000
                $first_num = substr($plu_code, 0, 1);
                if ($first_num == 0 || (float)$plu_code > 4000 || (float)$plu_code < 1) {
                    $error_data['msg'] = "第{$row}行数据plu编码错误，plu编码取值范围为1到4000之间";
                    return $error_data;
                }
                $first_num = substr($goodsSn, 0, 1);
                if (!preg_match('/^(\d{5})$/', $goodsSn) || $first_num == 0) {
                    $error_data['msg'] = "第{$row}行数据商品编码错误，设置了PLU的商品编码必须为5位数字且不得0开头";
                    return $error_data;
                }
            }
            $rankArr = array();
            if (!empty($rankPriceStr)) {
//                if ($os_type == 1) {
//                    $rankArrDecode = explode("\n", $rankPriceStr);
//                } else {
//                    $rankArrDecode = explode("\r\n", $rankPriceStr);
//                }
                $rankArrDecode = explode(PHP_EOL, $rankPriceStr);
                foreach ($rankArrDecode as $decodeInfo) {
                    $decodeInfo = explode('：', $decodeInfo);
                    if ((float)$decodeInfo[1] > 0) {
                        $rankDetail = $rankModule->getRankDetailByParams(array('rankName' => $decodeInfo[0]));
                        if (!empty($rankDetail)) {
                            $rankArr[] = array(
                                'shopId' => $shopId,
                                'goodsSn' => $goodsSn,
                                'skuBarcode' => $skuBarcode,
                                'rankId' => $rankDetail['rankId'],
                                'rankName' => $rankDetail['rankName'],
                                'price' => $decodeInfo[1],
                            );
                        }
                    }
                }
            }
            $rankArrData[$shopId . '@' . $goodsSn . '@' . $skuBarcode] = $rankArr;
            if (empty($skuBarcode)) {//非sku商品
                $existGoodsWhere = array(
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                );
                $existGoods = $goodsModule->getGoodsInfoByParams($existGoodsWhere, 'goodsId', 2);
                $goodsCatId1 = 0;
                $goodsCatId2 = 0;
                $goodsCatId3 = 0;
                $goodsCatId3Detail = $goodsCatModule->getGoodsCatDetailByName($goodsCatId3Name);
                if (!empty($goodsCatId3Detail)) {
                    $goodsCatId3 = $goodsCatId3Detail['catId'];
                    $goodsCatId2Detail = $goodsCatModule->getGoodsCatDetailById($goodsCatId3Detail['parentId']);
                    $goodsCatId2 = $goodsCatId2Detail['catId'];
                    $goodsCatId1Detail = $goodsCatModule->getGoodsCatDetailById($goodsCatId2Detail['parentId']);
                    $goodsCatId1 = $goodsCatId1Detail['catId'];
                }
                $shopCatId2Detail = $shopCatModule->getShopCatInfoByName($shopId, $shopCatId2Name);
                if (!empty($shopCatId2Detail)) {
                    $shopCatId2 = $shopCatId2Detail['catId'];
                    if ($shopCatId2Detail['parentId'] > 0) {
                        $shopCatId1Detail = $shopCatModule->getShopCatInfoById($shopCatId2Detail['parentId'], 'catId,catName', 2);
                        $shopCatId1 = $shopCatId1Detail['catId'];
                    }
                }
                $specStr = '';
                if (!empty($spec)) {
//                    if ($os_type == 1) {
//                        $specArr = explode("\n", $spec);
//                    } else {
//                        $specArr = explode("\r\n", $spec);
//                    }
                    $specArr = explode(PHP_EOL, $spec);
                    $specArrNew = array();
                    foreach ($specArr as $specArrInfo) {
                        $specArrInfoArr = explode('：', $specArrInfo);
                        $specArrInfoArrCount = count($specArrInfoArr);
                        $specName = $specArrInfoArr[0];
                        if ($specArrInfoArrCount > 2) {
                            $currLen = $specArrInfoArrCount - 2;
                            for ($i = 1; $i <= $currLen; $i++) {
                                $specName .= "：";
                            }
                        }
                        $specArrNew[] = array(
                            'specName' => $specName,
                            'specVal' => $specArrInfoArr[$specArrInfoArrCount - 1],
                        );
//                        $specArrNew[] = array(
//                            'specName' => $specArrInfoArr[0],
//                            'specVal' => $specArrInfoArr[1],
//                        );
                    }
                    $specStr = json_encode($specArrNew, JSON_UNESCAPED_UNICODE);
                }
                $markIconStr = '';
                if (!empty($markIcon)) {
//                    if ($os_type == 1) {
//                        $markIconArr = explode("\n", $markIcon);
//                    } else {
//                        $markIconArr = explode("\r\n", $markIcon);
//                    }
                    $markIconArr = explode(PHP_EOL, $markIcon);
                    $markIconStr = implode('@', $markIconArr);
                }
                $publicGoodsData = array(//非sku商品的公共数据
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                    'goodsName' => $goodsName,
                    'plu_code' => $plu_code,
                    'goodsCatId1' => $goodsCatId1,
                    'goodsCatId2' => $goodsCatId2,
                    'goodsCatId3' => $goodsCatId3,
                    //'goodsCatId3' => $goodsCatId3,
                    'shopCatId1' => $shopCatId1,
                    'shopCatId2' => $shopCatId2,
                    'isSale' => $isSale,
                    'goodsSpec' => $goodsSpec,
                    'marketPrice' => $marketPrice,
                    'shopPrice' => $shopPrice,
                    'memberPrice' => $memberPrice,
                    'goodsUnit' => $goodsUnit,
                    'integralReward' => $integralReward,
                    'integralRate' => $integralRate,
                    'goodsStock' => $goodsStock,
                    'selling_stock' => $selling_stock,
                    'stockWarningNum' => $stockWarningNum,
                    'virtualSales' => $virtualSales,
                    'orderNumLimit' => $orderNumLimit,
                    'buyNumLimit' => $buyNumLimit,
                    'delayed' => $delayed,
                    'spec' => $specStr,
                    'isNewPeople' => $isNewPeople,
                    'isRecomm' => $isRecomm,
                    'isBest' => $isBest,
                    'isNew' => $isNew,
                    'isHot' => $isHot,
                    'isMembershipExclusive' => $isMembershipExclusive,
                    'isConvention' => $isConvention,
                    'IntelligentRemark' => $IntelligentRemark,
                    'shopGoodsSort' => $shopGoodsSort,
                    'markIcon' => $markIconStr,
                    'unit' => $unit,
                    'SuppPriceDiff' => $SuppPriceDiff,
                    'sortOverweightG' => $sortOverweightG,
                    'py_code' => $py_code,
                    'py_initials' => $py_initials,
                    'hasSku' => 0,//是否存在sku(0:不存在 1:已存在)
                );
                if (empty($existGoods)) {//添加商品
                    $addGoodsInfo = $publicGoodsData;
                    $addGoodsInfo['goodsStatus'] = ($GLOBALS['CONFIG']['isGoodsVerify'] == 1) ? 0 : 1;
                    if ($addGoodsInfo['isSale'] == 1) {
                        $addGoodsInfo['saleTime'] = date('Y-m-d H:i:s');
                    }
                    $addGoodsInfo['createTime'] = date('Y-m-d H:i:s');
                    $addGoodsData[$goodsSn] = $addGoodsInfo;

                    unset($addGoodsInfo['hasSku']);
                    $insertGoodsData[$goodsSn] = $addGoodsInfo;
                } else {//更新商品
                    $updateGoodsInfo = $publicGoodsData;
                    $updateGoodsInfo['goodsId'] = $existGoods['goodsId'];
                    $updateGoodsData[$goodsSn] = $updateGoodsInfo;

                    unset($updateGoodsInfo['hasSku']);
                    $editGoodsData[$goodsSn] = $updateGoodsInfo;
                }
            }
            if (!empty($skuBarcode) && !empty($skuSpecStr)) {//sku商品
//                if ($os_type == 1) {
//                    $skuSpecArr = explode("\n", $skuSpecStr);
//                } else {
//                    $skuSpecArr = explode("\r\n", $skuSpecStr);
//                }
                $skuSpecArr = explode(PHP_EOL, $skuSpecStr);
                $skuSelfList = array();
                foreach ($skuSpecArr as $attrInfo) {
                    $attrInfo = explode('：', $attrInfo);
                    $specName = $attrInfo[0];
                    $attrName = $attrInfo[1];
                    $specAttrDetail = $goodsModule->getSkuAtrrDetailByName($specName, $attrName);
                    if (!empty($specAttrDetail)) {
                        $skuSelfList[] = $specAttrDetail;
                    }
                }
                if (!empty($skuSelfList)) {
                    $skuDetail = array(
                        'shopId' => $shopId,//用于更新商品的sku信息
                        'goodsSn' => $goodsSn,//用于更新商品的sku信息
                        'skuShopPrice' => $shopPrice,
                        'skuMemberPrice' => $memberPrice,
                        'skuMarketPrice' => 0,
//                        'skuGoodsImg' => '',
                        'skuGoodsStock' => $goodsStock,
                        'skuBarcode' => $skuBarcode,
                        'unit' => $unit,
                        'purchase_price' => $goodsUnit,
                        'selling_stock' => $selling_stock,
                        'addTime' => date('Y-m-d H:i:s'),
                        'skuSelf' => $skuSelfList,//sku组合数据
                    );
                    if (isset($addGoodsData[$goodsSn])) {
                        $addGoodsData[$goodsSn]['hasSku'] = 1;
                        $addGoodsData[$goodsSn]['skuList'][] = $skuDetail;
                        $insertSkuData[$goodsSn]['skuList'][] = $skuDetail;
                    }
                    if (isset($updateGoodsData[$goodsSn])) {
                        $updateGoodsData[$goodsSn]['hasSku'] = 1;
                        $updateGoodsData[$goodsSn]['skuList'][] = $skuDetail;
                        $updateSkuData[$goodsSn]['skuList'][] = $skuDetail;
                    }
                }
            }
            $import_num++;
        }
        if (!empty($allGoodsSn)) {
            if (count($allGoodsSn) != count(array_unique($allGoodsSn))) {
                $status = -3;
                $msg = "导入失败的商品，请检查商品编码或者sku编码是否重复";
                return array('status' => $status, 'importNum' => 0, 'msg' => $msg);
            }
        }
        $status = 1;
        $msg = '成功';
        if (count($error) > 0) {
            $status = -3;
            $msg = "导入失败的商品：\n";
            $msg .= implode("\n", $error);
            return array('status' => $status, 'importNum' => 0, 'msg' => $msg);
        }
        $goodsModel = new \App\Models\GoodsModel();
        if (count($insertGoodsData) > 0) {
            $insertGoodsData = array_values($insertGoodsData);
            $insertGoodsRes = $goodsModel->addAll($insertGoodsData);
            if (!$insertGoodsRes) {
                $status = -3;
                $msg = "导入失败的商品";
                return array('status' => $status, 'importNum' => 0, 'msg' => $msg);
            }
        }
        $skuSystemModle = new SkuGoodsSystemModel();
        $skuSelfModel = new SkuGoodsSelfModel();
        if (count($insertSkuData) > 0) {//新增商品的sku
            foreach ($insertSkuData as $goodsSnTag => $skuDetailVal) {
                $goodsWhere = array(
                    'goodsSn' => $goodsSnTag,
                    'shopId' => $shopId,
                );
                $goodsDetail = $goodsModule->getGoodsInfoByParams($goodsWhere, 'goodsId', 2);
                if (empty($goodsDetail)) {
                    continue;
                }
                $goodsId = $goodsDetail['goodsId'];
                $skuList = $skuDetailVal['skuList'];
                foreach ($skuList as $skuVal) {
                    $skuSystemParams = $skuVal;
                    $skuSystemParams['goodsId'] = $goodsId;
                    unset($skuSystemParams['goodsSn']);
                    unset($skuSystemParams['shopId']);
                    unset($skuSystemParams['skuSelf']);
                    $skuId = $skuSystemModle->add($skuSystemParams);
                    if ($skuId) {
                        $selfList = $skuVal['skuSelf'];
                        $skuSelfData = array();
                        foreach ($selfList as $selfVal) {
                            $skuSelfData[] = array(
                                'skuId' => $skuId,
                                'specId' => $selfVal['specId'],
                                'attrId' => $selfVal['attrId'],
                            );
                        }
                        $skuSelfModel->addAll($skuSelfData);
                    }

                }
            }
        }
        if (count($updateGoodsData) > 0) {//更新商品的sku
            foreach ($updateGoodsData as $updateGoodsDetail) {
                $goodsId = $updateGoodsDetail['goodsId'];
                $hasSku = $updateGoodsDetail['hasSku'];
                $existGoodsSku = $goodsModule->getGoodsSku($goodsId, 2);
                if ($hasSku != 1 && !empty($existGoodsSku)) {//删除商品的sku信息
                    $goodsModule->deleteGoodsSkuByGoodsId($goodsId);
                }
                $skuList = $updateGoodsDetail['skuList'];
                if (!empty($skuList)) {
                    $nowSkuBarCodeArr = array_column($skuList, 'skuBarcode');
                    foreach ($existGoodsSku as $existGoodsSkuDetail) {
                        if (!in_array($existGoodsSkuDetail['systemSpec']['skuBarcode'], $nowSkuBarCodeArr)) {
                            $goodsModule->deleteGoodsSkuBySkuId($existGoodsSkuDetail['skuId']);
                        }
                    }
                    foreach ($skuList as $skuVal) {
                        $skuBarcode = $skuVal['skuBarcode'];
                        $skuWhere = array(
                            'goodsId' => $goodsId,
                            'skuBarcode' => $skuBarcode,
                        );
                        $skuDetail = $goodsModule->getSkuSystemInfoParams($skuWhere, 2);
                        if (empty($skuDetail)) {//新增
                            $skuSystemParams = $skuVal;
                            $skuSystemParams['goodsId'] = $goodsId;
                            unset($skuSystemParams['goodsSn']);
                            unset($skuSystemParams['shopId']);
                            unset($skuSystemParams['skuSelf']);
                            $skuId = $skuSystemModle->add($skuSystemParams);
                            if ($skuId) {
                                $selfList = $skuVal['skuSelf'];
                                $skuSelfData = array();
                                foreach ($selfList as $selfVal) {
                                    $skuSelfData[] = array(
                                        'skuId' => $skuId,
                                        'specId' => $selfVal['specId'],
                                        'attrId' => $selfVal['attrId'],
                                    );
                                }
                                $skuSelfModel->addAll($skuSelfData);
                            }
                        } else {
                            $skuSystemParams = $skuVal;
                            $skuSystemParams['goodsId'] = $goodsId;
                            unset($skuSystemParams['goodsSn']);
                            unset($skuSystemParams['shopId']);
                            unset($skuSystemParams['skuSelf']);
                            $saveRes = $skuSystemModle->where(array('skuId' => $skuDetail['skuId']))->save($skuSystemParams);
                            if ($saveRes !== false) {
                                $skuSelfModel->where(array('skuId' => $skuDetail['skuId']))->delete();
                                $selfList = $skuVal['skuSelf'];
                                $skuSelfData = array();
                                foreach ($selfList as $selfVal) {
                                    $skuSelfData[] = array(
                                        'skuId' => $skuDetail['skuId'],
                                        'specId' => $selfVal['specId'],
                                        'attrId' => $selfVal['attrId'],
                                    );
                                }
                                $skuSelfModel->addAll($skuSelfData);
                            }
                        }
                    }
                }
            }
        }
        if (count($editGoodsData) > 0) {
            $editGoodsRes = $goodsModel->saveAll($editGoodsData, 'wst_goods', 'goodsId');
            if ($editGoodsRes === false) {
                $status = -3;
                $msg = "导入失败的商品";
                return array('status' => $status, 'importNum' => $import_num, 'msg' => $msg);
            }
        }
        if (count($rankArrData) > 0) {//更新商品等级价格
            foreach ($rankArrData as $rankTag => $goodsRankDetail) {
                $rankTagArr = explode('@', $rankTag);
                $shopId = (int)$rankTagArr[0];
                $goodsSn = (string)$rankTagArr[1];
                $skuBarcode = (string)$rankTagArr[2];
                $skuId = 0;
                $goodsWhere = array(
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                );
                $goodsDetail = $goodsModule->getGoodsInfoByParams($goodsWhere, 'goodsId,goodsName', 2);
                if (empty($goodsDetail)) {
                    continue;
                }
                $goodsId = (int)$goodsDetail['goodsId'];
                if (!empty($skuBarcode)) {
                    $skuWhere = array(
                        'goodsId' => $goodsId,
                        'skuBarcode' => $skuBarcode,
                    );
                    $skuDetail = $goodsModule->getSkuSystemInfoParams($skuWhere, 2);
                    if (empty($skuDetail)) {
                        continue;
                    }
                    $skuId = (int)$skuDetail['skuId'];
                }
                $goodsModule->addGoodsRank($goodsId, $skuId, $goodsRankDetail);
            }
        }
        return array('status' => $status, 'importNum' => $import_num, 'msg' => $msg);
    }

    /**
     * 获取商品列表
     */
    public function queryGetGoodsList($paramete = array())
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $paramete['shopId'];
        $shopCatId1 = (int)$paramete['shopCatId1'];
        $shopCatId2 = (int)$paramete['shopCatId2'];
        $goodCatId1 = (int)$paramete['goodCatId1'];
//        $isSale = (int)I('isSale');
//        $goodsStatus = (int)I('goodsStatus');
        $goodsName = WSTAddslashes(I('goodsName'));
        /*$sql = "select g.goodsId,g.shopId,g.weightG,g.SuppPriceDiff,g.goodsSn,g.shopGoodsSort,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,g.saleTime,g.createTime,g.memberPrice,g.integralRate from __PREFIX__goods g where g.goodsFlag=1 and g.shopId=".$shopId;*/
        $sql = "select g.* from __PREFIX__goods g where g.goodsFlag=1 and g.shopId=" . $shopId;
        $sql .= " and isBecyclebin=0 ";//后加回收站字段
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodCatId1 > 0) $sql .= " and g.goodsCatId1=" . $goodCatId1;
        //二开
        if (isset($paramete['goodsStatus'])) $sql .= " and g.goodsStatus=" . $paramete['goodsStatus'];
        if (isset($paramete['isSale']) && in_array($paramete['isSale'], [0, 1])) $sql .= " and g.isSale=" . $paramete['isSale'];
        if (isset($paramete['SuppPriceDiff'])) $sql .= " and g.SuppPriceDiff=" . $paramete['SuppPriceDiff'];
        //END
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";

        if (!empty($paramete['goodsAttr']) && in_array($paramete['goodsAttr'], array('isAdminRecom', 'isAdminBest', 'isNew', 'isHot', 'isMembershipExclusive', 'isShopSecKill', 'isAdminShopSecKill', 'isAdminShopPreSale', 'isShopPreSale'))) $sql .= " and g." . $paramete['goodsAttr'] . " = 1 ";

        $sql .= " order by g.shopGoodsSort desc";
        $list = $this->pageQuery($sql, $paramete['page'], $paramete['pageSize']);
        if (!empty($list['root'])) {
            $goods = $list['root'];
            $shopCatIdArr = [];//店铺分类id
            $goodsCatIdArr = [];//商城分类id
            foreach ($goods as $value) {
                $shopCatIdArr[] = $value['shopCatId1'];
                $shopCatIdArr[] = $value['shopCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId1'];
                $goodsCatIdArr[] = $value['goodsCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId3'];
            }
            $shopCatIdArr = array_unique($shopCatIdArr);
            $shopCatIdStr = implode(',', $shopCatIdArr);
            $shopCatList = M('shops_cats')->where(['catId' => ['IN', $shopCatIdStr]])->select();

            $goodsCatIdArr = array_unique($goodsCatIdArr);
            $goodsCatIdStr = implode(',', $goodsCatIdArr);
            $goodsCatIdList = M('goods_cats')->where(['catId' => ['IN', $goodsCatIdStr]])->select();

            foreach ($goods as $key => $value) {
                foreach ($shopCatList as $shopCat) {
                    if ($value['shopCatId1'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId1Name'] = $shopCat['catName'];
                    }
                    if ($value['shopCatId2'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId2Name'] = $shopCat['catName'];
                    }
                }

                foreach ($goodsCatIdList as $goodsCat) {
                    if ($value['goodsCatId1'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId1Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId2'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId2Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId3'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId3Name'] = $goodsCat['catName'];
                    }
                }
            }
            $list['root'] = $goods;
        }
        $list['root'] = rankGoodsPrice($list['root']);
        $list['root'] = getGoodsSku($list['root']);
        //$list['root'] = array_unique($list['root'], SORT_REGULAR);
        return $list;
    }

    /**
     * @param $param
     * @return array
     * 修改商家商品价格
     */
//    public function upGoodsPrice($param)
//    {
//        $rd = array('status' => -1);
//        if(!empty($param['goodsId'])){
//            $data = array();
//            $data['shopPrice'] = $param['shopPrice'];
//            $m = M('goods');
//            $rs = $m->where("goodsId=" . $param['goodsId'] . " and shopId=" . $param['shopId'])->save($data);
//        }
//        if(!empty($param['skuId'])){
//            $data = array();
//            $data['skuShopPrice'] = $param['shopPrice'];
//            $skuGoodsSystem = M('sku_goods_system');
//            $rs = $skuGoodsSystem->where("skuId=" . $param['skuId'] . " and goodsId=" . $param['goodsId'])->save($data);
//        }
//        if (false !== $rs) {
//            $rd = array('status' => 1, 'msg' => "操作成功");
//        }
//        return $rd;
//    }

    /**
     * 修改门店商品价格
     * @param array $loginUserInfo
     * @param array $requestParams <p>
     * int shopId
     * int skuId
     * float shopPrice
     * </p>
     * @return array $data
     * */
    public function upGoodsPrice(array $loginUserInfo, array $requestParams)
    {
        $shopId = $loginUserInfo['shopId'];
        $goodsId = (int)$requestParams['goodsId'];
        $skuId = (int)$requestParams['skuId'];
        $shopPrice = (float)$requestParams['shopPrice'];
        if (!empty($goodsId) && empty($skuId)) {
            $tab = M('goods');
            $where = [];
            $where['shopId'] = $shopId;
            $where['goodsId'] = $goodsId;
//            $info = $tab->where($where)->find();
//            if ($info['marketPrice'] <= $shopPrice) {
//                return returnData(false, -1, 'error', '零售价必须小于市场价');
//            }
            $save = [];
            $save['shopPrice'] = $shopPrice;
            $data = $tab->where($where)->save($save);
        } else {
            $tab = M('sku_goods_system');
            $save = [];
            $save['skuShopPrice'] = $shopPrice;
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['skuId'] = $skuId;
//            $info = $tab->where($where)->find();
//            if ($info['skuMarketPrice'] <= $shopPrice) {
//                return returnData(false, -1, 'error', '零售价必须小于市场价');
//            }
            $data = $tab->where($where)->save($save);
        }
        if ($data === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    public function upGoodsStock(array $loginUserInfo, array $requestParams)
    {
        $shopId = $loginUserInfo['shopId'];
        $goodsId = (int)$requestParams['goodsId'];
        $skuId = (int)$requestParams['skuId'];
        $goodsStock = $requestParams['goodsStock'];
        if (!empty($goodsId) && empty($skuId)) {
            $tab = M('goods');
            $where = [];
            $where['shopId'] = $shopId;
            $where['goodsId'] = $goodsId;
            $info = $tab->where($where)->find();
            if (empty($info)) {
                return returnData(false, -1, 'error', '暂无相关数据');
            }
            $save = [];
            $save['goodsStock'] = $goodsStock;
            $data = $tab->where($where)->save($save);
        } else {
            $tab = M('sku_goods_system');
            $save = [];
            $save['skuGoodsStock'] = $goodsStock;
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['skuId'] = $skuId;
            $info = $tab->where($where)->find();
            if (empty($info)) {
                return returnData(false, -1, 'error', '暂无相关数据');
            }
            $data = $tab->where($where)->save($save);
        }
        if ($data === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * 弃用,改用editGoodsBase方法
     * 修改排序号
     */
    public function editShopGoodsSort($obj)
    {
        $rd = array('status' => -1);
        $data = array();
        $data["shopGoodsSort"] = $obj["shopGoodsSort"];
        $m = M('goods');
        $rs = $m->where("goodsId=" . $obj['goodsId'] . " and shopId=" . $obj['shopId'])->save($data);
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /*
     * 获取限时
     * @param array $request
    * */
    public function getFlashSale()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $tab = M('flash_sale');
        $list = $tab->where("state=1 AND isDelete=0")->select();
        if ($list) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }

    /*
     * 获取商品对应的限时
     * @param array $request
     * */
    public function getFlashSaleGoods($request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $tab = M('flash_sale_goods');
        $where['isDelete'] = 0;
        $where['goodsId'] = $request['goodsId'];
        $list = $tab->where($where)->field('flashSaleId')->select();
        $res = [];
        if (!empty($list)) {
            foreach ($list as $val) {
                $res[] = $val['flashSaleId'];
            }
        }
        if ($list) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res;
        }
        return $apiRet;
    }

    /**
     * 弃用,改用商品详情
     * 获取商品信息(PS:用于复制商品,此代码复制商品详情)
     */
    public function getGoodsDetailCopy($parameter)
    {
        $m = M('goods');
        $goodsSn = $parameter['goodsSn'];
        $goods = $m->where("goodsSn=" . $goodsSn . " and shopId=" . $shopId)->find();
        if ($goods) {
            $m = M('goods_gallerys');
            $goods['gallery'] = $m->where('goodsSn=' . $goodsSn)->select();
            //获取规格属性
            $sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,ga.isRecomm,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent,ga.isRecomm from __PREFIX__attributes a left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsSn=" . $goodsSn . " where a.attrFlag=1 and a.catId=" . $goods['attrCatId'] . " and a.shopId=" . $shopId . " order by a.attrSort asc, a.attrId asc,ga.id asc";
            $attrRs = $m->query($sql);
            if (!empty($attrRs)) {
                $priceAttr = array();
                $attrs = array();
                foreach ($attrRs as $key => $v) {
                    if ($v['isPriceAttr'] == 1) {
                        if ($v['isRecomm'] == 1) {
                            $goods['recommPrice'] = $v['attrPrice'];
                        }
                        $goods['priceAttrId'] = $v['attrId'];
                        $goods['priceAttrName'] = $v['attrName'];
                        $priceAttr[] = $v;
                    } else {
                        //分解下拉和多选的选项
                        if ($v['attrType'] == 1 || $v['attrType'] == 2) {
                            $v['opts']['txt'] = explode(',', $v['attrContent']);
                            if ($v['attrType'] == 1) {
                                $vs = explode(',', $v['attrVal']);
                                //保存多选的值
                                foreach ($vs as $vv) {
                                    $v['opts']['val'][$vv] = 1;
                                }
                            }
                        }
                        $attrs[] = $v;
                    }
                }
                $goods['priceAttrs'] = $priceAttr;
                $goods['attrs'] = $attrs;
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取商品信息成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $goods;
        }
        return $apiRet;
    }

    /*
     * 搜索商品 PS:用于商品复用
     * @param array $requestParams<p>
     * string shopSn
     * string goodsName
     * string goodsSn
     * int page
     * int pageSize
     * </p>
     * */
    public function queryGetGoodsListCopy(array $requestParams)
    {
        if (!empty($requestParams['shopSn'])) {
            $where = [];
            $where['shopFlag'] = 1;
            $where['shopSn'] = $requestParams['shopSn'];
            $shopInfo = M('shops')->where($where)->find();
            if (empty($shopInfo)) {
                return returnData(false, -1, 'error', '店铺不存在');
            }
        }
        $page = (int)$requestParams['page'];
        $pageSize = (int)$requestParams['pageSize'];
        $where = " g.goodsFlag=1 and g.isSale=1 ";
        $whereFind = [];
        $whereFind['g.shopId'] = function () use ($shopInfo) {
            if (empty($shopInfo['shopId'])) {
                return null;
            }
            return ['=', "{$shopInfo['shopId']}", 'and'];
        };
        $whereFind['g.goodsName'] = function () use ($requestParams) {
            if (empty($requestParams['goodsName'])) {
                return null;
            }
            return ['like', "%{$requestParams['goodsName']}%", 'and'];
        };
        $whereFind['g.goodsSn'] = function () use ($requestParams) {
            if (empty($requestParams['goodsSn'])) {
                return null;
            }
            return ['=', "{$requestParams['goodsSn']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = "{$where} and {$whereFind} ";
        }
        $field = 'g.goodsId,g.shopId,g.SuppPriceDiff,g.goodsSn,g.shopGoodsSort,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,g.saleTime,g.createTime,g.goodsLocation,g.memberPrice';
        $sql = "select {$field} from __PREFIX__goods g where {$whereInfo} ";
        $sql .= " and isBecyclebin=0 ";
        $list = $this->pageQuery($sql, $page, $pageSize);
        return returnData($list);
    }

    /*
    * 复制商品
    * @param string goodsId PS:多个用英文逗号分隔
    * */
    public function goodsCopy($paramete = array())
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $goodsTab = M('goods');
        $attrTab = M('attributes');
        $goodsAttrTab = M('goods_attributes');
        $galleryTab = M('wst_goods_gallerys');
        $rankGoodsTab = M('rank_goods');
        $goodsId = $paramete['goodsId'];
        $goods = $goodsTab->where("goodsId IN($goodsId)")->select();
        if ($goods) {
            $shopInfo = M('shops')->where("shopId='" . $goods[0]['shopId'] . "'")->find();
            $attrList = $attrTab->where("shopId='" . $shopInfo['shopId'] . "' AND attrFlag=1")->order("attrId ASC")->select();
            foreach ($goods as $key => $val) {
                $goodsId = $val['goodsId'];
                $shopId = $val['shopId'];
                unset($val['goodsId']);
                unset($val['shopId']);
                $val['shopId'] = $paramete['shopId'];
                //现在只是复制商品,和店铺相关的需要删除
                unset($val['shopCatId1']);
                unset($val['shopCatId2']);
                unset($val['shopCatId3']);
                $newGoodsId = $goodsTab->add($val);
                //复制商品的属性
                //$newGoodsId = 1;
                if ($newGoodsId) {
                    foreach ($attrList as $v) {
                        $existAttrId = $v['attrId'];
                        unset($v['attrId']);
                        unset($v['shopId']);
                        $v['shopId'] = $paramete['shopId'];
                        $v['createTime'] = date('Y-m-d H:i:s', time());
                        $isExist = $attrTab->where("attrName='" . $v['attrName'] . "' AND shopId='" . $paramete['shopId'] . "'")->find();
                        if (!$isExist) {
                            $newAttrId = $attrTab->add($v);
                        }
                        if ($newAttrId) {
                            $goodsAttrList = $goodsAttrTab->where("shopId='" . $shopId['shopId'] . "' AND attrId='" . $existAttrId . "' AND goodsId='" . $goodsId . "'")->order('id ASC')->select();
                            foreach ($goodsAttrList as $gv) {
                                unset($gv['id']);
                                $gv['shopId'] = $paramete['shopId'];
                                $gv['goodsId'] = $newGoodsId;
                                $goodsAttrTab->add($gv);
                            }
                        }
                    }
                }
                //复制商品的等级
                $rankGoodsList = M('rank_goods')->where("goodsId='" . $goodsId . "'")->order("id ASC")->select();
                if ($rankGoodsList) {
                    foreach ($rankGoodsList as $lv) {
                        unset($lv['id']);
                        $lv['goodsId'] = $newGoodsId;
                        $rankGoodsTab->add($lv);
                    }
                }

                //复制商品的相册
                $galleryList = $galleryTab->where("goodsId='" . $goodsId . "' AND shopId='" . $shopId . "'")->order("id ASC")->select();
                if ($galleryList) {
                    foreach ($galleryList as $rv) {
                        unset($rv['id']);
                        $rv['goodsId'] = $newGoodsId;
                        $rv['shopId'] = $paramete['shopId'];
                        $galleryTab->add($rv);
                    }
                }
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '数据操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * 弃用,改用editGoodsBase接口
     * 修改单商品的价格
     * @param int goodsId
     * @param string price
     */
    public function editGoodsShopPrice($obj)
    {
        $rd = array('status' => -1);
        $data = array();
        $data["shopPrice"] = $obj["shopPrice"];
        $m = M('goods');
        $rs = $m->where("goodsId=" . $obj['goodsId'] . " and shopId=" . $obj['shopId'])->save($data);
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /**
     * 获取商品信息
     */
    public function getCopy($parameter)
    {
        $m = M('goods');
        $id = (int)I('id', 0);
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $parameter['shopId'];
        $goods = $m->where("goodsId='" . $id . "'")->find();
        if (empty($goods)) return array();
        $m = M('goods_gallerys');

        //添加商户Id条件
        $whereGallery = [];
        $whereGallery['goodsId'] = $id;
        $whereGallery['shopId'] = $shopId;
        $goods['gallery'] = $m->where($whereGallery)->order('id asc')->select();

        //获取规格属性
        $sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,ga.isRecomm,a.attrId,a.attrName,a.isPriceAttr,a.attrType,a.attrContent
		            ,ga.isRecomm from __PREFIX__attributes a
		            left join __PREFIX__goods_attributes ga on ga.attrId=a.attrId and ga.goodsId=" . $id . " where
					a.attrFlag=1 and a.catId=" . $goods['attrCatId'] . " and a.shopId=" . $goods['shopId'] . " order by a.attrSort asc, a.attrId asc,ga.id asc";
        $attrRs = $m->query($sql);
        if (!empty($attrRs)) {
            $priceAttr = array();
            $attrs = array();
            foreach ($attrRs as $key => $v) {
                if ($v['isPriceAttr'] == 1) {
                    if ($v['isRecomm'] == 1) {
                        $goods['recommPrice'] = $v['attrPrice'];
                    }
                    $goods['priceAttrId'] = $v['attrId'];
                    $goods['priceAttrName'] = $v['attrName'];
                    $priceAttr[] = $v;
                } else {
                    //分解下拉和多选的选项
                    if ($v['attrType'] == 1 || $v['attrType'] == 2) {
                        $v['opts']['txt'] = explode(',', $v['attrContent']);
                        if ($v['attrType'] == 1) {
                            $vs = explode(',', $v['attrVal']);
                            //保存多选的值
                            foreach ($vs as $vv) {
                                $v['opts']['val'][$vv] = 1;
                            }
                        }
                    }
                    $attrs[] = $v;
                }
            }
            $goods['priceAttrs'] = $priceAttr;
            $goods['attrs'] = $attrs;
        }
        if (!empty($goods['shopCatId1'])) {
            $shopCat = M('shops_cats');
            $shopCat1 = $shopCat->where("shopId='" . $goods['shopId'] . "' AND catId='" . $goods['shopCatId1'] . "'")->find();
            $shopCat2 = $shopCat->where("shopId='" . $goods['shopId'] . "' AND catId='" . $goods['shopCatId2'] . "'")->find();
            $shopCats = $shopCat->where("shopId='" . $parameter['shopId'] . "' AND parentId=0 AND isShow=1 AND catFlag=1")->select();
            $shopCatsSecond = $shopCat->where("shopId='" . $parameter['shopId'] . "' AND parentId>0 AND isShow=1 AND catFlag=1")->select();
            //var_dump($shopCats);exit;
            foreach ($shopCats as $val) {
                if ($val['catName'] == $shopCat1['catName']) {
                    $goods['ownShopCatId1'] = $val['catId'];
                }
            }
            //var_dump($goods['ownShopCatId1']);exit;
            if (isset($goods['ownShopCatId1']) && !empty($goods['ownShopCatId1'])) {
                //$shopCatTwo = $shopCat->where("shopId='".$goods['shopId']."' AND parentId='".$goods['ownShopCatId1']."' AND isShow=1 AND catFlag=1")->select();
                /*foreach ($shopCatTwo as $val){
                    foreach ($shopCatsSecond as $v){
                        if($v['catName'] == $val['catName']){
                            //var_dump($val['catName']);exit;
                            $goods['ownShopCatId2'] = $val['catId'];
                        }
                    }
                }*/
                foreach ($shopCatsSecond as $v) {
                    if ($v['catName'] == $shopCat2['catName']) {
                        //var_dump($val['catName']);exit;
                        $goods['ownShopCatId2'] = $v['catId'];
                    }
                }
            }
            if ($goods['shopId'] == $shopId) {
                $goods['ownShopCatId1'] = $goods['shopCatId1'];
                $goods['ownShopCatId2'] = $goods['shopCatId2'];
            }
            if (empty($goods['ownShopCatId1']) || empty($goods['ownShopCatId2'])) {
                $goods['catSelected'] = 0;
            } else {
                $goods['catSelected'] = 1;
            }
        }
        return $goods;
    }

    /*
    * 获取商城分类
    * @param int parentId 父id
    * */
    public function getSystemGoodsCat($request)
    {
        $res['apiCode'] = '-1';
        $res['apiInfo'] = '获取数据失败';
        $res['apiState'] = 'error';
        $where['isShow'] = 1;
        $where['catFlag'] = 1;
        if (!empty($request['parentId'])) {
            $where['parentId'] = $request['parentId'];
        } else {
            $where['parentId'] = 0;
        }
        $list = M('goods_cats')->where($where)->order('catId desc')->select();
        if ($list) {
            $res['apiCode'] = '0';
            $res['apiInfo'] = '获取数据成功';
            $res['apiState'] = 'success';
            $res['apiData'] = $list;
        }
        return $res;
    }

    /**
     * 获取商品销量统计
     */
    public function queryGetGoodsSaleCount($paramete = array())
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $paramete['shopId'];
        $shopCatId1 = (int)$paramete['shopCatId1'];
        $shopCatId2 = (int)$paramete['shopCatId2'];
        $goodsName = WSTAddslashes(I('goodsName'));
        $startTime = I('startTime');
        $endTime = I('endTime');
        $sql = "select g.goodsId,g.shopId,g.SuppPriceDiff,g.goodsSn,g.shopGoodsSort,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,g.saleTime,g.createTime,(select sum(og.goodsNums) from __PREFIX__order_goods og left join __PREFIX__orders o on o.orderId=og.orderId where og.goodsId=g.goodsId and o.createTime between '{$startTime}' and '{$endTime}') as goodsSaleCount from __PREFIX__goods g where g.goodsFlag=1 and g.shopId=" . $shopId;
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        //二开
        if (isset($paramete['goodsStatus'])) $sql .= " and g.goodsStatus=" . $paramete['goodsStatus'];
        if (isset($paramete['isSale'])) $sql .= " and g.isSale=" . $paramete['isSale'];
        if (isset($paramete['SuppPriceDiff'])) $sql .= " and g.SuppPriceDiff=" . $paramete['SuppPriceDiff'];
        if (!empty($paramete['goodCatId1'])) $sql .= " and g.goodsCatId1=" . $paramete['goodCatId1'];
        //END
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";
        $sql .= " order by goodsSaleCount desc";
        $list = $this->pageQuery($sql);
        foreach ($list['root'] as $key => $val) {
            if (is_null($val['goodsSaleCount'])) {
                $list['root'][$key]['goodsSaleCount'] = 0;
            }
        }

        //$list['root'] = array_unique($list['root'], SORT_REGULAR);
        return $list;
    }

    /**
     * 导出商品列表
     */
    public function exportGoodsList($paramete = array())
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $paramete['shopId'];
        $shopCatId1 = (int)$paramete['shopCatId1'];
        $shopCatId2 = (int)$paramete['shopCatId2'];
//        $isSale = (int)I('isSale');
//        $goodsStatus = (int)I('goodsStatus');
        $goodsName = WSTAddslashes(I('goodsName'));

        $sql = "select g.goodsId,g.shopId,g.stockWarningNum,g.goodsSn,g.goodsName,g.memberPrice,g.goodsCatId3,g.shopCatId2,g.brandId,g.marketPrice,g.shopPrice,g.goodsStock,g.saleCount,g.goodsUnit,g.goodsKeywords,g.isShopRecomm,g.isRecomm,g.isNew,g.isHot,g.goodsDesc,g.isBest,g.isSale from __PREFIX__goods g where g.goodsFlag=1 and g.shopId=" . $shopId;
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        //二开
        if (isset($paramete['goodsStatus'])) $sql .= " and g.goodsStatus=" . $paramete['goodsStatus'];
        if (isset($paramete['isSale'])) $sql .= " and g.isSale=" . $paramete['isSale'];
        if (isset($paramete['SuppPriceDiff'])) $sql .= " and g.SuppPriceDiff=" . $paramete['SuppPriceDiff'];
        //END
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";

        if (!empty($paramete['goodsAttr']) && in_array($paramete['goodsAttr'], array('isAdminRecom', 'isAdminBest', 'isNew', 'isHot', 'isMembershipExclusive', 'isShopSecKill', 'isAdminShopSecKill', 'isAdminShopPreSale', 'isShopPreSale'))) $sql .= " and g." . $paramete['goodsAttr'] . " = 1 ";

        $sql .= " order by g.shopGoodsSort desc";

        $list = $this->query($sql);
        if (empty($list)) return array('code' => -1, 'msg' => '暂无数据');
        if (!empty($list)) {
            $gam = M('goods_attributes');
            $scm = M('shops_cats');
            $gcm = M('goods_cats');
            $bm = M('brands');
            foreach ($list as $k => $v) {
                //商城分类
                $list[$k]['mallCatName'] = $gcm->where(array('catId' => $v['goodsCatId3']))->find()['catName'];
                //店铺分类
                $list[$k]['shopCatName'] = $scm->where(array('catId' => $v['shopCatId2']))->find()['catName'];
                //商品品牌
                $list[$k]['goodsBrandName'] = $bm->where(array('brandId' => $v['brandId']))->find()['brandName'];
//                $list[$k]['goodsAttrData'] = $gam->where(array('shopId'=>$v['shopId'],'goodsId'=>$v['goodsId']))->select();

                // $list[$k]['goodsAttrData'] = M('goods_attributes ga')->join('wst_attributes as a on ga.attrId = a.attrId')->where(array('ga.shopId' => $v['shopId'], 'ga.goodsId' => $v['goodsId'], 'a.attrFlag' => 1))->field('ga.*')->select();

                $where = [];
                $where['dataFlag'] = 1;
                $where['goodsId'] = $list[$k]['goodsId'];
                $list[$k]['goodsAttrData'] = M('sku_goods_system')->where($where)->select();


            }
        }


        $this->exportGoods($list);

        // $this->exportGoods_backup($list);

        //$list['root'] = array_unique($list['root'], SORT_REGULAR);
    }

    /**
     * 导出商品
     * 原来的，可用的
     */
    public function exportGoods_backup($goodsData)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("goodsList")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
        //设置excel工作表名及文件名
        $title = '商品列表';
        $excel_filename = '商品列表_' . date('Ymd_His');
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        //第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excel_filename);
        //合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:AC1');
        //设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置excel的表头
//          $sheet_title = array('商品ID','门店ID','商品编号','商品名称','商品图片','商品缩略图','店铺价格','商品总库存','销售量','是否上架','是否店铺精品','是否热销产品','是否精品','是否新品','上架时间','创建时间','称重补差价','店铺商品排序');
        $sheet_title = array('商品ID', '商品编号', '商品名称', '市场价格', '店铺价格', '库存', '销量', '商品SEO关键字', '商品信息', '推荐', '精品', '新品', '热销', '上架', '商城分类', '本店分类', '品牌', '商品规格');
        // 设置第一行和第一行的行高
//          $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
//          $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
//          $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
        //设置单元格
//          $objPHPExcel->getActiveSheet()->getStyle('A2:AC2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //首先是赋值表头
        for ($k = 0; $k < 18; $k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        }
        //开始赋值
        for ($i = 0; $i < count($goodsData); $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $goodsData[$i];
            for ($j = 0; $j < 18; $j++) {
                //开始为每个单元格赋值
                //初始化地址数据
//                  $address_arr = [];
//                  $address_arr = explode(',',$temp['address_address']);
                //初始化商品数据
//                  $product_name = $product_number = $product_attr = $product_num = $product_price = '';
                /*                  $cl = '，'.chr(10);
                                  foreach ($temp['o_g_model'] as $v) {
                                                         $product_name .= $v['title'].$cl;
                                      $product_number .= $v['attrid'].$cl;
                                      $attr = [];
                                      $attr = json_decode($v['attr_name'],true);
                                      if ($attr) {
                                                                 foreach ($attr as $vv) {
                                                                         $product_attr .= $vv.' ';
                                          }
                                          $product_attr .= $cl;
                                      }
                                      $product_num .= $v['num'].$cl;
                                      $product_price .= $v['user_price'].$cl;
                                  }*/
                switch ($j) {
                    case 0 :
                        //商品ID
                        $cellvalue = $temp['goodsId'];
                        break;
                    case 1 :
                        //商品编号
                        $cellvalue = $temp['goodsSn'];
                        break;
                    case 2 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 3 :
                        //市场价格
                        $cellvalue = $temp['marketPrice'];
                        break;
                    case 4 :
                        //店铺价格
                        $cellvalue = $temp['shopPrice'];
                        break;
                    case 5 :
                        //库存
                        $cellvalue = $temp['goodsStock'];
                        break;
                    case 6 :
                        //销量
                        $cellvalue = $temp['saleCount'];
                        break;
                    case 7 :
                        //商品SEO关键字
                        $cellvalue = $temp['goodsKeywords'];
                        break;
                    case 8 :
                        //商品信息
                        $cellvalue = $temp['goodsDesc'];
                        break;
                    case 9 :
                        //推荐
                        $cellvalue = $temp['isRecomm'];
                        break;
                    case 10 :
                        //精品
                        $cellvalue = $temp['isBest'];
                        break;
                    case 11 :
                        //新品
                        $cellvalue = $temp['isNew'];
                        break;
                    case 12 :
                        //热销
                        $cellvalue = $temp['isHot'];
                        break;
                    case 13 :
                        //是否上架
                        $cellvalue = $temp['isSale'];
                        break;
                    case 14 :
                        //商城分类
                        $cellvalue = $temp['mallCatName'];
                        break;
                    case 15 :
                        //本店分类
                        $cellvalue = $temp['shopCatName'];
                        break;
                    case 16 :
                        //品牌
                        $cellvalue = $temp['goodsBrandName'];
                        break;
                    case 17 :
                        //商品规格
                        $goodsAttrData = $temp['goodsAttrData'];
                        $cellvalue = '';
                        if (!empty($goodsAttrData)) {
                            foreach ($goodsAttrData as $v) {
                                $cellvalue .= $v['id'] . "#" . $v['attrVal'] . '¥' . $v['attrPrice'] . "/";
                            }
                        }
                        $cellvalue = rtrim($cellvalue, '/');
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                /*if ((in_array($j,[15,16,17,18,19])) && "" != $cellvalue) {
                                     $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
                }*/
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(21);
        }
//        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle($title);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
        header('Cache-Control: max-age=0');

        /*
                header('Content-Type: application/vnd.ms-excel');
                header("Content-type: application/octet-stream");
                header("Accept-Ranges: bytes");
                header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
                header('Cache-Control: max-age=0');
                */
        /*
                //下载文件需要用到的头
               Header("Content-type: application/octet-stream");
               Header("Accept-Ranges: bytes");
               Header("Accept-Length:".$file_size);
               Header("Content-Disposition: attachment; filename=".$rel_name);
                */
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 商品数据更新
     * 原来的，可用的
     */
    public function goodsDataUpdate_backup($data)
    {
        $objReader = WSTReadExcel($data['file']['savepath'] . $data['file']['savename']);
        $objReader->setActiveSheetIndex(0);
        $sheet = $objReader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        $cells = $sheet->getHighestColumn();

//        $shopId = (int)session('WST_USER.shopId');
//        if(empty($shopId)){
//            $shopId = $this->MemberVeri()['shopId'];
//        }
//        $shopId = 1;
        $goodsModel = M('goods');
        $goodsAttrModel = M('goods_attributes');
        $importNum = 0;
        //循环读取每个单元格的数据
        for ($row = 3; $row <= $rows; $row++) {//行数是以第3行开始
            $goodsId = trim($sheet->getCell("A" . $row)->getValue());
            $marketPrice = trim($sheet->getCell("D" . $row)->getValue());
            $memberPrice = trim($sheet->getCell("E" . $row)->getValue());
            $shopPrice = trim($sheet->getCell("F" . $row)->getValue());
            $goodsModel->where(array('goodsId' => $goodsId))->save(array('marketPrice' => $marketPrice, 'shopPrice' => $shopPrice, 'memberPrice' => $memberPrice));

            $goodsAttrData = trim($sheet->getCell("R" . $row)->getValue());
            if (!empty($goodsAttrData)) {
                $goodsAttrData_arr = explode('/', $goodsAttrData);
                foreach ($goodsAttrData_arr as $v) {
                    $attr_1 = explode('#', $v);
                    $id = $attr_1[0];
                    $attr_2 = explode('¥', $attr_1[1]);
                    $attr_price = $attr_2[1];
                    $goodsAttrModel->where(array('id' => $id))->save(array('attrPrice' => $attr_price));
                }
            }
            $importNum++;

        }

        return array('status' => 1, 'importNum' => $importNum);
    }

//    /**
//     * 导出商品
//     */
//    public function exportGoods($goodsData)
//    {
//        Vendor("PHPExcel.PHPExcel");
//        Vendor("PHPExcel.PHPExcel.IOFactory");
//        //引入Excel类
//        $objPHPExcel = new \PHPExcel();
//        // 设置excel文档的属性
//        $objPHPExcel->getProperties()->setCreator("cyf")
//            ->setLastModifiedBy("cyf Test")
//            ->setTitle("goodsList")
//            ->setSubject("Test1")
//            ->setDescription("Test2")
//            ->setKeywords("Test3")
//            ->setCategory("Test result file");
//        //设置excel工作表名及文件名
//        $title = '商品列表';
//        $excel_filename = '商品列表_' . date('Ymd_His');
//        // 操作第一个工作表
//        $objPHPExcel->setActiveSheetIndex(0);
//        //第一行设置内容
//        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excel_filename);
//        //合并
//        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
//        //设置单元格内容加粗
//        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//        //设置单元格内容水平居中
//        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//        //设置excel的表头
////          $sheet_title = array('商品ID','门店ID','商品编号','商品名称','商品图片','商品缩略图','店铺价格','商品总库存','销售量','是否上架','是否店铺精品','是否热销产品','是否精品','是否新品','上架时间','创建时间','称重补差价','店铺商品排序');
//        $sheet_title = array('商品ID', '商品编号', '商品名称', '市场价格', '会员价格', '店铺价格', '进货价格', '库存', '库存预警', 'sku-ID', 'sku-店铺价格', 'sku-会员价格', 'sku-单价', 'sku-库存', '推荐', '精品', '新品', '热销', '会员专享', '商品分销', '一级分销金', '二级分销金');
//        // 设置第一行和第一行的行高
////          $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
////          $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
////          $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
//        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V');
//        //设置单元格
////          $objPHPExcel->getActiveSheet()->getStyle('A2:AC2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//        //首先是赋值表头
//        for ($k = 0; $k < 22; $k++) {
//            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
//            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(10)->setBold(true);
//            //设置单元格内容水平居中
//            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//            //设置每一列的宽度
//            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
//            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
//        }
//        //开始赋值
//        for ($i = 0; $i < count($goodsData); $i++) {
//            //先确定行
//            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
//            $temp = $goodsData[$i];
//            for ($j = 0; $j < 22; $j++) {
//                //开始为每个单元格赋值
//                //初始化地址数据
////                  $address_arr = [];
////                  $address_arr = explode(',',$temp['address_address']);
//                //初始化商品数据
////                  $product_name = $product_number = $product_attr = $product_num = $product_price = '';
//                /*                  $cl = '，'.chr(10);
//                                  foreach ($temp['o_g_model'] as $v) {
//                                                         $product_name .= $v['title'].$cl;
//                                      $product_number .= $v['attrid'].$cl;
//                                      $attr = [];
//                                      $attr = json_decode($v['attr_name'],true);
//                                      if ($attr) {
//                                                                 foreach ($attr as $vv) {
//                                                                         $product_attr .= $vv.' ';
//                                          }
//                                          $product_attr .= $cl;
//                                      }
//                                      $product_num .= $v['num'].$cl;
//                                      $product_price .= $v['user_price'].$cl;
//                                  }*/
//                switch ($j) {
//                    case 0 :
//                        //商品ID
//                        $cellvalue = $temp['goodsId'];
//                        break;
//                    case 1 :
//                        //商品编号
//                        $cellvalue = $temp['goodsSn'];
//                        break;
//                    case 2 :
//                        //商品名称
//                        $cellvalue = $temp['goodsName'];
//                        break;
//                    case 3 :
//                        //市场价格
//                        $cellvalue = $temp['marketPrice'];
//                        break;
//                    case 4 :
//                        //会员价格
//                        $cellvalue = $temp['memberPrice'];
//                        break;
//                    case 5 :
//                        //店铺价格
//                        $cellvalue = $temp['shopPrice'];
//                        break;
//                    case 6 :
//                        //进货价格
//                        $cellvalue = $temp['goodsUnit'];
//                        break;
//                    case 7 :
//                        //库存
//                        $cellvalue = $temp['goodsStock'];
//                        break;
//                    case 8 :
//                        //库存预警
//                        $cellvalue = $temp['stockWarningNum'];
//                        break;
//                    case 9 :
//                        //sku-Id
//                        $goodsAttrData = $temp['goodsAttrData'];
//                        $cellvalue = '';
//                        if (!empty($goodsAttrData)) {
//                            foreach ($goodsAttrData as $v) {
//                                $cellvalue .= $v['skuId'] . " ";
//                            }
//                        }
//                        $cellvalue = rtrim($cellvalue);
//                        break;
//                    case 10 :
//                        //sku-店铺价格
//                        $goodsAttrData = $temp['goodsAttrData'];
//                        $cellvalue = '';
//                        if (!empty($goodsAttrData)) {
//                            foreach ($goodsAttrData as $v) {
//                                $cellvalue .= $v['skuShopPrice'] . " ";
//                            }
//                        }
//                        $cellvalue = rtrim($cellvalue);
//                        break;
//                    case 11 :
//                        //sku-会员价格
//                        $goodsAttrData = $temp['goodsAttrData'];
//                        $cellvalue = '';
//                        if (!empty($goodsAttrData)) {
//                            foreach ($goodsAttrData as $v) {
//                                $cellvalue .= $v['skuMemberPrice'] . " ";
//                            }
//                        }
//                        $cellvalue = rtrim($cellvalue);
//                        break;
//                    case 12 :
//                        //sku-单价
//                        $goodsAttrData = $temp['goodsAttrData'];
//                        $cellvalue = '';
//                        if (!empty($goodsAttrData)) {
//                            foreach ($goodsAttrData as $v) {
//                                $cellvalue .= $v['UnitPrice'] . " ";
//                            }
//                        }
//                        $cellvalue = rtrim($cellvalue);
//                        break;
//                    case 13 :
//                        //sku-库存
//                        $goodsAttrData = $temp['goodsAttrData'];
//                        $cellvalue = '';
//                        if (!empty($goodsAttrData)) {
//                            foreach ($goodsAttrData as $v) {
//                                $cellvalue .= $v['skuGoodsStock'] . " ";
//                            }
//                        }
//                        $cellvalue = rtrim($cellvalue);
//                        break;
//                    case 14 :
//                        //推荐
//                        $cellvalue = (int)$temp['isRecomm'];
//                        break;
//                    case 15 :
//                        //精品
//                        $cellvalue = (int)$temp['isBest'];
//                        break;
//                    case 16 :
//                        //新品
//                        $cellvalue = (int)$temp['isNew'];
//                        break;
//                    case 17 :
//                        //热销
//                        $cellvalue = (int)$temp['isHot'];
//                        break;
//                    case 18 :
//                        //会员专享
//                        $cellvalue = (int)$temp['isMembershipExclusive'];
//                        break;
//                    case 19 :
//                        //分销商品
//                        $cellvalue = (int)$temp['isDistribution'];
//                        break;
//                    case 20 :
//                        //一级分销金额
//                        $cellvalue = (float)$temp['firstDistribution'];
//                        break;
//                    case 21 :
//                        //二级分销金额
//                        $cellvalue = (float)$temp['SecondaryDistribution'];
//                        break;
//                    // case 9 :
//                    //     //商品规格
//                    //     // $goodsAttrData = json_encode($temp['goodsSku']);
//
//                    //     // $cellvalue = '';
//                    //     // if (!empty($goodsAttrData)) {
//                    //     //     foreach ($goodsAttrData as $v) {
//                    //     //         $cellvalue .= $v['id'] . "#" . $v['attrVal'] . '¥' . $v['attrPrice'] . " \r\n ";
//                    //     //     }
//                    //     // }
//                    //     // $cellvalue = rtrim($cellvalue, '/');
//                    //     $goodsAttrData = $temp['goodsAttrData'];
//                    //     $cellvalue = '';
//                    //     if (!empty($goodsAttrData)) {
//                    //         foreach ($goodsAttrData as $v) {
//                    //             $cellvalue .= $v['skuId'] . "#" . $v['skuShopPrice'] . '¥' . $v['skuMemberPrice'] .'@'.$v['skuGoodsStock']. " \r\n ";
//                    //             // $cellvalue .= $v['skuId'] . "#" . $v['skuShopPrice'] . '¥' . $v['skuMemberPrice'] . " \r\n ";
//                    //         }
//                    //     }
//                    //     $cellvalue = rtrim($cellvalue, '/');
//                    //     // $cellvalue = json_encode($temp['goodsAttrData']);
//                    //     break;
//                }
//                //赋值
//                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
//                //设置字体大小
//                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
//                //设置单元格内容水平居中
//                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//                // $objPHPExcel->getActiveSheet()->getRowDimension($j)->setRowHeight(50);
//
//                //设置自动换行
//                /*if ((in_array($j,[15,16,17,18,19])) && "" != $cellvalue) {
//                                     $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
//                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
//                }*/
//            }
//            // 设置行高
//            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
//        }
////        unset($res);
//        //赋值结束，开始输出
//        $objPHPExcel->getActiveSheet()->setTitle($title);
//
//        header('Content-Type: application/vnd.ms-excel');
//        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
//        header('Cache-Control: max-age=0');
//
//        /*
//                header('Content-Type: application/vnd.ms-excel');
//                header("Content-type: application/octet-stream");
//                header("Accept-Ranges: bytes");
//                header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
//                header('Cache-Control: max-age=0');
//                */
//        /*
//                //下载文件需要用到的头
//               Header("Content-type: application/octet-stream");
//               Header("Accept-Ranges: bytes");
//               Header("Accept-Length:".$file_size);
//               Header("Content-Disposition: attachment; filename=".$rel_name);
//                */
//        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//        $objWriter->save('php://output');
//    }


    /**
     * 导出商品
     */
    public function exportGoods($goodsData)
    {
        $result = array();
        foreach ($goodsData as $item) {
            $detail = array();
            $detail['goodsId'] = $item['goodsId'];
            $detail['skuId'] = 0;
            $detail['goodsSn'] = $item['goodsSn'];
            $detail['plu_code'] = $item['plu_code'];
            $detail['goodsName'] = $item['goodsName'];
            $detail['skuBarcode'] = '';
            $detail['skuSpecAttr'] = '';//sku属性拼接值
            $detail['goodsCatId3Name'] = $item['goodsCatId3Name'];
            $detail['shopCatId2Name'] = $item['shopCatId2Name'];
            $detail['isSale'] = $item['isSale'];
            $detail['marketPrice'] = $item['marketPrice'];//市场价格
            $detail['shopPrice'] = $item['shopPrice'];//店铺价格
            $detail['memberPrice'] = $item['memberPrice'];//会员价格
            $detail['goodsUnit'] = $item['goodsUnit'];//进货价格
            $detail['integralReward'] = $item['integralReward'];//奖励积分
            $detail['integralRate'] = $item['integralRate'];//积分比例
            $detail['goodsStock'] = $item['goodsStock'];//库房库存
            $detail['selling_stock'] = $item['selling_stock'];//售卖库存
            $detail['stockWarningNum'] = $item['stockWarningNum'];//库存预警
            $detail['virtualSales'] = $item['virtualSales'];//虚拟销量
            $detail['orderNumLimit'] = $item['orderNumLimit'];//限制下单次数
            $detail['buyNumLimit'] = $item['buyNumLimit'];//限制单笔商品数量
            $detail['delayed'] = $item['delayed'];//商品延时
            $detail['spec'] = $item['spec'];//产品参数
            $detail['rankPriceStr'] = '';//身份价格
            $detail['isNewPeople'] = $item['isNewPeople'];//新人专享
            $detail['isRecomm'] = $item['isRecomm'];//推荐
            $detail['isBest'] = $item['isBest'];//精品
            $detail['isNew'] = $item['isNew'];//新品
            $detail['isHot'] = $item['isHot'];//热销
            $detail['isMembershipExclusive'] = $item['isMembershipExclusive'];//会员专享
            $detail['isConvention'] = $item['isConvention'];//商品类型
            $detail['IntelligentRemark'] = $item['IntelligentRemark'];//处理方式
            $detail['shopGoodsSort'] = $item['shopGoodsSort'];//商品排序
            $detail['markIcon'] = $item['markIcon'];//商品标签
            $detail['unit'] = $item['unit'];//商品单位
            $detail['SuppPriceDiff'] = $item['SuppPriceDiff'];//商品称重
            $detail['sortOverweightG'] = $item['sortOverweightG'];//可超重量
            $result[] = $this->andleExportValue($detail);
            if ($item['hasGoodsSku'] == 1) {//sku也作为独立的商品导出
                $skuList = $item['goodsSku']['skuList'];
                foreach ($skuList as $skuDetail) {
                    $detail = array();
                    $detail['goodsId'] = $item['goodsId'];
                    $detail['skuId'] = $skuDetail['skuId'];
                    $detail['goodsSn'] = $item['goodsSn'];
                    $detail['plu_code'] = '';
                    $detail['goodsName'] = '';
                    $detail['skuBarcode'] = $skuDetail['systemSpec']['skuBarcode'];
                    $detail['skuSpecAttr'] = '';//sku属性拼接值
                    if (!empty($skuDetail['selfSpec'])) {
                        foreach ($skuDetail['selfSpec'] as $selfDetail) {
                            $detail['skuSpecAttr'] .= $selfDetail['specName'] . '：' . $selfDetail['attrName'] . "\r\n";
                        }
                        $detail['skuSpecAttr'] = rtrim($detail['skuSpecAttr'], "\r\n");
                    }
                    $detail['goodsCatId3Name'] = '';
                    $detail['shopCatId2Name'] = '';
                    $detail['isSale'] = '';
                    $detail['marketPrice'] = '';//市场价格
                    $detail['shopPrice'] = $skuDetail['systemSpec']['skuShopPrice'];//店铺价格
                    $detail['memberPrice'] = $skuDetail['systemSpec']['skuMemberPrice'];//会员价格
                    $detail['goodsUnit'] = $skuDetail['systemSpec']['purchase_price'];//进货价格
                    $detail['integralReward'] = '';//奖励积分
                    $detail['integralRate'] = '';//积分比例
                    $detail['goodsStock'] = $skuDetail['systemSpec']['skuGoodsStock'];//库房库存
                    $detail['selling_stock'] = $skuDetail['systemSpec']['selling_stock'];//售卖库存
                    $detail['stockWarningNum'] = '';//库存预警
                    $detail['virtualSales'] = '';//虚拟销量
                    $detail['orderNumLimit'] = '';//限制下单次数
                    $detail['buyNumLimit'] = '';//限制单笔商品数量
                    $detail['delayed'] = '';//商品延时
                    $detail['spec'] = '';//产品参数
                    $detail['rankPriceStr'] = '';//身份价格
                    $detail['isNewPeople'] = '';//新人专享
                    $detail['isRecomm'] = '';//推荐
                    $detail['isBest'] = '';//精品
                    $detail['isNew'] = '';//新品
                    $detail['isHot'] = '';//热销
                    $detail['isMembershipExclusive'] = '';//会员专享
                    $detail['isConvention'] = '';//商品类型
                    $detail['IntelligentRemark'] = '';//处理方式
                    $detail['shopGoodsSort'] = '';//商品排序
                    $detail['markIcon'] = '';//商品标签
                    $detail['unit'] = $skuDetail['systemSpec']['unit'];//商品单位
                    $detail['SuppPriceDiff'] = '';//商品称重
                    $detail['sortOverweightG'] = '';//可超重量
                    $result[] = $this->andleExportValue($detail);
                }
            }
        }
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/update_goods.xls';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->getActiveSheet()->getComment('A2')->getText()->createTextRun('Total amount on the current invoice, including VAT.');
        $keyTag = 3;
        foreach ($result as $info) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $info['goodsSn']);//商品编号
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $info['plu_code']);//PLU编码
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $info['goodsName']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $info['skuBarcode']);//sku商品编码
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $info['skuSpecAttr']);//sku属性拼接值
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $info['goodsCatId3Name']);//商城第三级分类
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $info['shopCatId2Name']);//门店第二级分类
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $info['isSale']);//是否上架
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $info['goodsSpec']);//商品介绍
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $info['marketPrice']);//市场价格
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $info['shopPrice']);//店铺价格
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $info['memberPrice']);//会员价格
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $info['goodsUnit']);//进货价格
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $info['integralReward']);//奖励积分
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $info['integralRate']);//积分比例
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $keyTag, $info['goodsStock']);//库房库存
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $keyTag, $info['selling_stock']);//售卖库存
            $objPHPExcel->getActiveSheet()->setCellValue('R' . $keyTag, $info['stockWarningNum']);//库存预警
            $objPHPExcel->getActiveSheet()->setCellValue('S' . $keyTag, $info['virtualSales']);//虚拟销量
            $objPHPExcel->getActiveSheet()->setCellValue('T' . $keyTag, $info['orderNumLimit']);//限制下单次数
            $objPHPExcel->getActiveSheet()->setCellValue('U' . $keyTag, $info['buyNumLimit']);//限制单笔商品数量
            $objPHPExcel->getActiveSheet()->setCellValue('V' . $keyTag, $info['delayed']);//商品延时
            $objPHPExcel->getActiveSheet()->setCellValue('W' . $keyTag, $info['spec']);//产品参数
            $objPHPExcel->getActiveSheet()->setCellValue('X' . $keyTag, $info['rankPriceStr']);//身份价格
            $objPHPExcel->getActiveSheet()->setCellValue('Y' . $keyTag, $info['isNewPeople']);//新人专享
            $objPHPExcel->getActiveSheet()->setCellValue('Z' . $keyTag, $info['isRecomm']);//推荐
            $objPHPExcel->getActiveSheet()->setCellValue('AA' . $keyTag, $info['isBest']);//精品
            $objPHPExcel->getActiveSheet()->setCellValue('AB' . $keyTag, $info['isNew']);//新品
            $objPHPExcel->getActiveSheet()->setCellValue('AC' . $keyTag, $info['isHot']);//热销
            $objPHPExcel->getActiveSheet()->setCellValue('AD' . $keyTag, $info['isMembershipExclusive']);//会员专享
            $objPHPExcel->getActiveSheet()->setCellValue('AE' . $keyTag, $info['isConvention']);//常规商品
            $objPHPExcel->getActiveSheet()->setCellValue('AF' . $keyTag, $info['IntelligentRemark']);//处理方式
            $objPHPExcel->getActiveSheet()->setCellValue('AG' . $keyTag, $info['shopGoodsSort']);//商品排序
            $objPHPExcel->getActiveSheet()->setCellValue('AH' . $keyTag, $info['markIcon']);//商品标签
            $objPHPExcel->getActiveSheet()->setCellValue('AI' . $keyTag, $info['unit']);//商品单位
            $objPHPExcel->getActiveSheet()->setCellValue('AK' . $keyTag, $info['SuppPriceDiff']);//商品称重
            $objPHPExcel->getActiveSheet()->setCellValue('AL' . $keyTag, $info['sortOverweightG']);//可超重量
            $keyTag++;
        }
        $savefileName = '商品数据' . date('Y-m-d H:i:s');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 处理导出的数据
     * */
    public function andleExportValue(&$data)
    {
        $spec = json_decode($data['spec'], true);
        $data['spec'] = '';
        if (!empty($spec)) {
            foreach ($spec as $val) {
                $data['spec'] .= $val['specName'] . '：' . $val['specVal'] . "\r\n";
            }
            $data['spec'] = rtrim($data['spec'], "\r\n");
        }
        if (is_numeric($data['isSale'])) {
            if ($data['isSale'] == 1) {
                $data['isSale'] = '是';
            } elseif ($data['isSale'] == 0) {
                $data['isSale'] = '否';
            }
        }
        if (is_numeric($data['isNewPeople'])) {
            if ($data['isNewPeople'] == 1) {
                $data['isNewPeople'] = '是';
            } elseif ($data['isNewPeople'] == 0) {
                $data['isNewPeople'] = '否';
            }
        }
        if (is_numeric($data['isRecomm'])) {
            if ($data['isRecomm'] == 1) {
                $data['isRecomm'] = '是';
            } elseif ($data['isRecomm'] == 0) {
                $data['isRecomm'] = '否';
            }
        }
        if (is_numeric($data['isBest'])) {
            if ($data['isBest'] == 1) {
                $data['isBest'] = '是';
            } elseif ($data['isBest'] == 0) {
                $data['isBest'] = '否';
            }
        }
        if (is_numeric($data['isNew'])) {
            if ($data['isNew'] == 1) {
                $data['isNew'] = '是';
            } elseif ($data['isNew'] == 0) {
                $data['isNew'] = '否';
            }
        }
        if (is_numeric($data['isHot'])) {
            if ($data['isHot'] == 1) {
                $data['isHot'] = '是';
            } elseif ($data['isHot'] == 0) {
                $data['isHot'] = '否';
            }
        }
        if (is_numeric($data['isMembershipExclusive'])) {
            if ($data['isMembershipExclusive'] == 1) {
                $data['isMembershipExclusive'] = '是';
            } elseif ($data['isMembershipExclusive'] == 0) {
                $data['isMembershipExclusive'] = '否';
            }
        }
        if (is_numeric($data['isConvention'])) {
            if ($data['isConvention'] == 1) {
                $data['isConvention'] = '否';
            } elseif ($data['isConvention'] == 0) {
                $data['isConvention'] = '是';
            }
        }
        if (is_numeric($data['SuppPriceDiff'])) {
            if ($data['SuppPriceDiff'] == 1) {
                $data['SuppPriceDiff'] = '是';
            } elseif ($data['SuppPriceDiff'] == -1) {
                $data['SuppPriceDiff'] = '否';
            }
        }
        if (!empty($data['markIcon'])) {
            $data['markIcon'] = str_replace('@', "\r\n", $data['markIcon']);
        }
        $goodsModule = new GoodsModule();
        $rankModule = new RankModule();
        $rankList = $rankModule->getRankList();
        $rankPriceList = $goodsModule->getGoodsRankListByGoodsId((int)$data['goodsId'], (int)$data['skuId']);
        if (!empty($rankPriceList)) {
            $data['rankPriceStr'] = '';
            foreach ($rankList as $item) {
                $item['price'] = '';
                foreach ($rankPriceList as $rankPriceInfo) {
                    if ($item['rankId'] == $rankPriceInfo['rankId']) {
                        $item['price'] = $rankPriceInfo['price'];
                    }
                }
                $data['rankPriceStr'] .= $item['rankName'] . '：' . $item['price'] . "\r\n";
            }
            $data['rankPriceStr'] = rtrim($data['rankPriceStr'], "\r\n");
        }
        return $data;
    }

    /**
     * 商品数据更新
     */
    public function goodsDataUpdate($data)
    {
        $objReader = WSTReadExcel($data['file']['savepath'] . $data['file']['savename']);
        $objReader->setActiveSheetIndex(0);
        $sheet = $objReader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        $cells = $sheet->getHighestColumn();

//        $shopId = (int)session('WST_USER.shopId');
//        if(empty($shopId)){
//            $shopId = $this->MemberVeri()['shopId'];
//        }
//        $shopId = 1;
        $goodsModel = M('goods');
        $goodsAttrModel = M('sku_goods_system');
        $importNum = 0;
        //循环读取每个单元格的数据
        for ($row = 3; $row <= $rows; $row++) {//行数是以第3行开始
            $goodsId = trim($sheet->getCell("A" . $row)->getValue());
            $marketPrice = trim($sheet->getCell("D" . $row)->getValue());
            $memberPrice = trim($sheet->getCell("E" . $row)->getValue());
            $shopPrice = trim($sheet->getCell("F" . $row)->getValue());

            $goodsUnit = trim($sheet->getCell("G" . $row)->getValue());
            $goodsStock = trim($sheet->getCell("H" . $row)->getValue());
            $stockWarningNum = trim($sheet->getCell("I" . $row)->getValue());
            //isRecomm isBest isNew isHot isMembershipExclusive
            //isDistribution firstDistribution SecondaryDistribution
            $isRecomm = (int)trim($sheet->getCell("O" . $row)->getValue());
            $isBest = (int)trim($sheet->getCell("P" . $row)->getValue());
            $isNew = (int)trim($sheet->getCell("Q" . $row)->getValue());
            $isHot = (int)trim($sheet->getCell("R" . $row)->getValue());
            $isMembershipExclusive = (int)trim($sheet->getCell("S" . $row)->getValue());
            $isDistribution = (int)trim($sheet->getCell("T" . $row)->getValue());
            $firstDistribution = (float)trim($sheet->getCell("U" . $row)->getValue());
            $SecondaryDistribution = (float)trim($sheet->getCell("V" . $row)->getValue());
            if ($isDistribution != 1) {
                $firstDistribution = 0;
                $SecondaryDistribution = 0;
            }
            $save = [];
            $save['marketPrice'] = $marketPrice;
            $save['shopPrice'] = $shopPrice;
            $save['memberPrice'] = $memberPrice;
            $save['goodsUnit'] = $goodsUnit;
            $save['goodsStock'] = $goodsStock;
            $save['stockWarningNum'] = $stockWarningNum;
            $save['isRecomm'] = $isRecomm;
            $save['isBest'] = $isBest;
            $save['isNew'] = $isNew;
            $save['isHot'] = $isHot;
            $save['isMembershipExclusive'] = $isMembershipExclusive;
            $save['isDistribution'] = $isDistribution;
            $save['firstDistribution'] = $firstDistribution;
            $save['SecondaryDistribution'] = $SecondaryDistribution;
            $goodsModel->where(array('goodsId' => $goodsId))->save($save);
            // $goodsAttrData = trim($sheet->getCell("J" . $row)->getValue());
            // if (!empty($goodsAttrData)) {
            //     $goodsAttrData_arr = explode(' ', $goodsAttrData);
            //     foreach ($goodsAttrData_arr as $v) {
            //         if (!empty($v)) {
            //             $attr_1 = explode('#', $v);
            //             $id = trim($attr_1[0]);
            //             $attr_2 = explode('¥', $attr_1[1]);
            //             $attr_3 = explode('@', $attr_1[1]);

            //             $attr_price = trim($attr_2[1]);
            //             $save = [];
            //             $save['skuShopPrice'] = $attr_2[0];

            //             $array_4 = explode('@', $attr_2[1]);
            //             $save['skuMemberPrice'] =$array_4[0];
            //             $save['skuGoodsStock'] = $attr_3[1];

            //             $goodsAttrModel->where(array('skuId' => $id))->save($save);
            //         }
            //     }
            // }
            $skuId = $sheet->getCell("J" . $row)->getValue();
            $skuShopPrice = trim($sheet->getCell("K" . $row)->getValue());
            $skuMemberPrice = trim($sheet->getCell("L" . $row)->getValue());
            $UnitPrice = trim($sheet->getCell("M" . $row)->getValue());
            $skuGoodsStock = trim($sheet->getCell("N" . $row)->getValue());
            // if(!empty($skuId) && !empty($skuShopPrice) && !empty($skuMemberPrice) && !empty($skuGoodsStock)){
            $skuId = explode(' ', $skuId);
            $skuShopPrice = explode(' ', $skuShopPrice);
            $skuMemberPrice = explode(' ', $skuMemberPrice);
            $skuGoodsStock = explode(' ', $skuGoodsStock);
            $UnitPrice = explode(' ', $UnitPrice);
            for ($i = 0; $i <= count($skuId); $i++) {
                $saveData['skuId'] = $skuId[$i];
                if ((int)$saveData['skuId'] > 0) {
                    $saveData['skuShopPrice'] = $skuShopPrice[$i];
                    $saveData['skuMemberPrice'] = $skuMemberPrice[$i];
                    $saveData['skuGoodsStock'] = $skuGoodsStock[$i];
                    $saveData['UnitPrice'] = $UnitPrice[$i];
                    $goodsAttrModel->save($saveData);
                }
            }

            $importNum++;
        }
        $data = [];
        $data['importNum'] = $importNum;
        return returnData($data);
    }

    /**
     * 商品数据更新
     * 注：临时修复，复制上面goodsDataUpdate方法，具体参数就不剖析了，历史遗留代码
     * */
    public function goodsDataUpdateNew($params)
    {
        ini_set('memory_limit', '-1');
        $obj_reader = WSTReadExcel($params['file']['savepath'] . $params['file']['savename']);
        $obj_reader->setActiveSheetIndex(0);
        $sheet = $obj_reader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        $os_type = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 1 : 2;
        $shopId = $params['shopId'];
        $import_num = 0;//成功导入商品数量
        //循环读取每个单元格的数据
        $error = array();//返回错误信息
        $pyModule = new PYCodeModule();
        $goodsModule = new GoodsModule();
        $goodsCatModule = new GoodsCatModule();
        $shopCatModule = new ShopCatsModule();
        $rankModule = new RankModule();
//        $addGoodsData = array();
//        $insertGoodsData = array();
        $updateGoodsData = array();
        $editGoodsData = array();
        $updateSkuData = array();
        $insertSkuData = array();
        $rankArrData = array();

        $allGoodsSn = array();
        for ($row = 3; $row <= $rows; $row++) {//行数是以第3行开始
            //商品基本信息只添加/更新,不删除
            $goodsSn = trim($sheet->getCell("A" . $row)->getValue());//商品编号
            $plu_code = trim($sheet->getCell("B" . $row)->getValue());//PLU编码
            $goodsName = trim($sheet->getCell("C" . $row)->getValue());//商品名称
            $skuBarcode = trim($sheet->getCell("D" . $row)->getValue());//sku编码
            $skuSpecStr = trim($sheet->getCell("E" . $row)->getValue());//sku属性拼接值
            $goodsCatId3Name = trim($sheet->getCell("F" . $row)->getValue());//第三级商品商城分类名称
            $shopCatId2Name = trim($sheet->getCell("G" . $row)->getValue());//第二级商品门店分类名称
            $isSale = trim($sheet->getCell("H" . $row)->getValue());//是否上架
            $isSale = $isSale == '是' ? 1 : 0;
            $goodsSpec = trim($sheet->getCell("I" . $row)->getValue());//商品介绍
            $marketPrice = trim($sheet->getCell("J" . $row)->getValue());//市场价格
            $shopPrice = trim($sheet->getCell("K" . $row)->getValue());//店铺价格
            $memberPrice = trim($sheet->getCell("L" . $row)->getValue());//会员价格
            $goodsUnit = trim($sheet->getCell("M" . $row)->getValue());//进货价格
            $integralReward = trim($sheet->getCell("N" . $row)->getValue());//奖励积分
            $integralRate = trim($sheet->getCell("O" . $row)->getValue());//积分比例
            $goodsStock = trim($sheet->getCell("P" . $row)->getValue());//库房库存
            $selling_stock = trim($sheet->getCell("Q" . $row)->getValue());//售卖库存
            $stockWarningNum = trim($sheet->getCell("R" . $row)->getValue());//库存预警
            $virtualSales = trim($sheet->getCell("S" . $row)->getValue());//虚拟销量
            $orderNumLimit = trim($sheet->getCell("T" . $row)->getValue());//限制下单次数
            $buyNumLimit = trim($sheet->getCell("U" . $row)->getValue());//限制单笔商品数量
            $delayed = trim($sheet->getCell("V" . $row)->getValue());//商品延时
            $spec = trim($sheet->getCell("W" . $row)->getValue());//产品参数
            $rankPriceStr = trim($sheet->getCell("X" . $row)->getValue());//身份价格字符串
            $isNewPeople = trim($sheet->getCell("Y" . $row)->getValue());//新人专享
            $isNewPeople = $isNewPeople == '是' ? 1 : 0;
            $isRecomm = trim($sheet->getCell("Z" . $row)->getValue());//推荐
            $isRecomm = $isRecomm == '是' ? 1 : 0;
            $isBest = trim($sheet->getCell("AA" . $row)->getValue());//精品
            $isBest = $isBest == '是' ? 1 : 0;
            $isNew = trim($sheet->getCell("AB" . $row)->getValue());//新品
            $isNew = $isNew == '是' ? 1 : 0;
            $isHot = trim($sheet->getCell("AC" . $row)->getValue());//热销
            $isHot = $isHot == '是' ? 1 : 0;
            $isMembershipExclusive = trim($sheet->getCell("AD" . $row)->getValue());//会员专享
            $isMembershipExclusive = $isMembershipExclusive == '是' ? 1 : 0;
            $isConvention = trim($sheet->getCell("AE" . $row)->getValue());//常规商品
            $isConvention = $isConvention == '是' ? 0 : 1;
            $IntelligentRemark = trim($sheet->getCell("AF" . $row)->getValue());//处理方式
            $shopGoodsSort = trim($sheet->getCell("AG" . $row)->getValue());//商品排序
            $markIcon = trim($sheet->getCell("AH" . $row)->getValue());//商品标签
            $unit = trim($sheet->getCell("AI" . $row)->getValue());//商品单位
            $SuppPriceDiff = trim($sheet->getCell("AK" . $row)->getValue());//商品称重
            $SuppPriceDiff = $SuppPriceDiff == '是' ? 1 : -1;
            $sortOverweightG = trim($sheet->getCell("AL" . $row)->getValue());//可超重量
            if (empty($goodsSn)) {
                $error[] = "第{$row}行商品编码不能为空";
                continue;
            }
            if (empty($skuBarcode) && empty($goodsName)) {
                $error[] = "第{$row}行商品名称不能为空";
                continue;
            }
            $allGoodsSn[] = $goodsSn . '@' . $skuBarcode;
            $py_code = '';
            $py_initials = '';
            if (!empty($goodsName)) {
                $py_code_detail = $pyModule->getFullSpell($goodsName);
                $py_code = $py_code_detail['py_code'];
                $py_initials = $py_code_detail['py_initials'];
            }
            if (!empty($plu_code)) {
                //plu_code不为空需校验plu_code必须为4位数字且不得0开头，不得大于4000
                $first_num = substr($plu_code, 0, 1);
                if ($first_num == 0 || (float)$plu_code > 4000 || (float)$plu_code < 1) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$row}行数据plu编码错误，plu编码取值范围为1到4000之间");
                }
                $first_num = substr($goodsSn, 0, 1);
                if (!preg_match('/^(\d{5})$/', $goodsSn) || $first_num == 0) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "第{$row}行数据商品编码错误，设置了PLU的商品编码必须为5位数字且不得0开头");
                }
            }
            $rankArr = array();
            if (!empty($rankPriceStr)) {
//                if ($os_type == 1) {
//                    $rankArrDecode = explode("\n", $rankPriceStr);
//                } else {
//                    $rankArrDecode = explode("\r\n", $rankPriceStr);
//                }
                $rankArrDecode = explode(PHP_EOL, $rankPriceStr);
                foreach ($rankArrDecode as $decodeInfo) {
                    $decodeInfo = explode('：', $decodeInfo);
                    if ((float)$decodeInfo[1] > 0) {
                        $rankDetail = $rankModule->getRankDetailByParams(array('rankName' => $decodeInfo[0]));
                        if (!empty($rankDetail)) {
                            $rankArr[] = array(
                                'shopId' => $shopId,
                                'goodsSn' => $goodsSn,
                                'skuBarcode' => $skuBarcode,
                                'rankId' => $rankDetail['rankId'],
                                'rankName' => $rankDetail['rankName'],
                                'price' => $decodeInfo[1],
                            );
                        }
                    }
                }
            }
            $rankArrData[$shopId . '@' . $goodsSn . '@' . $skuBarcode] = $rankArr;
            if (empty($skuBarcode)) {//非sku商品
                $existGoodsWhere = array(
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                );
                $existGoods = $goodsModule->getGoodsInfoByParams($existGoodsWhere, 'goodsId', 2);
                $goodsCatId1 = 0;
                $goodsCatId2 = 0;
                $goodsCatId3 = 0;
                $goodsCatId3Detail = $goodsCatModule->getGoodsCatDetailByName($goodsCatId3Name);
                if (!empty($goodsCatId3Detail)) {
                    $goodsCatId3 = $goodsCatId3Detail['catId'];
                    $goodsCatId2Detail = $goodsCatModule->getGoodsCatDetailById($goodsCatId3Detail['parentId']);
                    $goodsCatId2 = $goodsCatId2Detail['catId'];
                    $goodsCatId1Detail = $goodsCatModule->getGoodsCatDetailById($goodsCatId2Detail['parentId']);
                    $goodsCatId1 = $goodsCatId1Detail['catId'];
                }
                $shopCatId2Detail = $shopCatModule->getShopCatInfoByName($shopId, $shopCatId2Name);
                if (!empty($shopCatId2Detail)) {
                    $shopCatId2 = $shopCatId2Detail['catId'];
                    if ($shopCatId2Detail['parentId'] > 0) {
                        $shopCatId1Detail = $shopCatModule->getShopCatInfoById($shopCatId2Detail['parentId'], 'catId,catName', 2);
                        $shopCatId1 = $shopCatId1Detail['catId'];
                    }
                }
                $specStr = '';
                if (!empty($spec)) {
//                    if ($os_type == 1) {
//                        $specArr = explode("\n", $spec);
//                    } else {
//                        $specArr = explode("\r\n", $spec);
//                    }
                    $specArr = explode(PHP_EOL, $spec);
                    $specArrNew = array();
                    foreach ($specArr as $specArrInfo) {
                        $specArrInfoArr = explode('：', $specArrInfo);
                        $specArrInfoArrCount = count($specArrInfoArr);
                        $specName = $specArrInfoArr[0];
                        if ($specArrInfoArrCount > 2) {
                            $currLen = $specArrInfoArrCount - 2;
                            for ($i = 1; $i <= $currLen; $i++) {
                                $specName .= "：";
                            }
                        }
                        $specArrNew[] = array(
                            'specName' => $specName,
                            'specVal' => $specArrInfoArr[$specArrInfoArrCount - 1],
                        );
                    }
                    $specStr = json_encode($specArrNew, JSON_UNESCAPED_UNICODE);
                }
                $markIconStr = '';
                if (!empty($markIcon)) {
//                    if ($os_type == 1) {
//                        $markIconArr = explode("\n", $markIcon);
//                    } else {
//                        $markIconArr = explode("\r\n", $markIcon);
//                    }
                    $markIconArr = explode(PHP_EOL, $markIcon);
                    $markIconStr = implode('@', $markIconArr);
                }
                $publicGoodsData = array(//非sku商品的公共数据
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                    'goodsName' => $goodsName,
                    'plu_code' => $plu_code,
                    'goodsCatId1' => $goodsCatId1,
                    'goodsCatId2' => $goodsCatId2,
                    'goodsCatId3' => $goodsCatId3,
//                    'goodsCatId3' => $goodsCatId3,
                    'shopCatId1' => $shopCatId1,
                    'shopCatId2' => $shopCatId2,
                    'isSale' => $isSale,
                    'goodsSpec' => $goodsSpec,
                    'marketPrice' => $marketPrice,
                    'shopPrice' => $shopPrice,
                    'memberPrice' => $memberPrice,
                    'goodsUnit' => $goodsUnit,
                    'integralReward' => $integralReward,
                    'integralRate' => $integralRate,
                    'goodsStock' => $goodsStock,
                    'selling_stock' => $selling_stock,
                    'stockWarningNum' => $stockWarningNum,
                    'virtualSales' => $virtualSales,
                    'orderNumLimit' => $orderNumLimit,
                    'buyNumLimit' => $buyNumLimit,
                    'delayed' => $delayed,
                    'spec' => $specStr,
                    'isNewPeople' => $isNewPeople,
                    'isRecomm' => $isRecomm,
                    'isBest' => $isBest,
                    'isNew' => $isNew,
                    'isHot' => $isHot,
                    'isMembershipExclusive' => $isMembershipExclusive,
                    'isConvention' => $isConvention,
                    'IntelligentRemark' => $IntelligentRemark,
                    'shopGoodsSort' => $shopGoodsSort,
                    'markIcon' => $markIconStr,
                    'unit' => $unit,
                    'SuppPriceDiff' => $SuppPriceDiff,
                    'sortOverweightG' => $sortOverweightG,
                    'py_code' => $py_code,
                    'py_initials' => $py_initials,
                    'hasSku' => 0,//是否存在sku(0:不存在 1:已存在)
                );
                if (empty($existGoods)) {//不存在的商品直接跳过，这里只处理批量改价，修改已有的商品信息
                    continue;
                }
                $updateGoodsInfo = $publicGoodsData;
                $updateGoodsInfo['goodsId'] = $existGoods['goodsId'];
                $updateGoodsData[$goodsSn] = $updateGoodsInfo;

                unset($updateGoodsInfo['hasSku']);
                $editGoodsData[$goodsSn] = $updateGoodsInfo;
            }
            if (!empty($skuBarcode) && !empty($skuSpecStr)) {//sku商品
//                if ($os_type == 1) {
//                    $skuSpecArr = explode("\n", $skuSpecStr);
//                } else {
//                    $skuSpecArr = explode("\r\n", $skuSpecStr);
//                }
                $skuSpecArr = explode(PHP_EOL, $skuSpecStr);
                $skuSelfList = array();
                foreach ($skuSpecArr as $attrInfo) {
                    $attrInfo = explode('：', $attrInfo);
                    $specName = $attrInfo[0];
                    $attrName = $attrInfo[1];
                    $specAttrDetail = $goodsModule->getSkuAtrrDetailByName($specName, $attrName);
                    if (!empty($specAttrDetail)) {
                        $skuSelfList[] = $specAttrDetail;
                    }
                }
                if (!empty($skuSelfList)) {
                    $skuDetail = array(
                        'shopId' => $shopId,//用于更新商品的sku信息
                        'goodsSn' => $goodsSn,//用于更新商品的sku信息
                        'skuShopPrice' => $shopPrice,
                        'skuMemberPrice' => $memberPrice,
                        'skuMarketPrice' => 0,
//                        'skuGoodsImg' => '',
                        'skuGoodsStock' => $goodsStock,
                        'skuBarcode' => $skuBarcode,
                        'unit' => $unit,
                        'purchase_price' => $goodsUnit,
                        'selling_stock' => $selling_stock,
                        'addTime' => date('Y-m-d H:i:s'),
                        'skuSelf' => $skuSelfList,//sku组合数据
                    );
                    if (isset($updateGoodsData[$goodsSn])) {
                        $updateGoodsData[$goodsSn]['hasSku'] = 1;
                        $updateGoodsData[$goodsSn]['skuList'][] = $skuDetail;
                        $updateSkuData[$goodsSn]['skuList'][] = $skuDetail;
                    }
                }
            }
            $import_num++;
        }
        if (!empty($allGoodsSn)) {
            if (count($allGoodsSn) != count(array_unique($allGoodsSn))) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "导入失败的商品，请检查商品编码或者sku编码是否重复");
            }
        }
        if (count($error) > 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "导入失败");
        }
        $goodsModel = new \App\Models\GoodsModel();
        $skuSystemModle = new SkuGoodsSystemModel();
        $skuSelfModel = new SkuGoodsSelfModel();
        if (count($insertSkuData) > 0) {//新增商品的sku
            foreach ($insertSkuData as $goodsSnTag => $skuDetailVal) {
                $goodsWhere = array(
                    'goodsSn' => $goodsSnTag,
                    'shopId' => $shopId,
                );
                $goodsDetail = $goodsModule->getGoodsInfoByParams($goodsWhere, 'goodsId', 2);
                if (empty($goodsDetail)) {
                    continue;
                }
                $goodsId = $goodsDetail['goodsId'];
                $skuList = $skuDetailVal['skuList'];
                foreach ($skuList as $skuVal) {
                    $skuSystemParams = $skuVal;
                    $skuSystemParams['goodsId'] = $goodsId;
                    unset($skuSystemParams['goodsSn']);
                    unset($skuSystemParams['shopId']);
                    unset($skuSystemParams['skuSelf']);
                    $skuId = $skuSystemModle->add($skuSystemParams);
                    if ($skuId) {
                        $selfList = $skuVal['skuSelf'];
                        $skuSelfData = array();
                        foreach ($selfList as $selfVal) {
                            $skuSelfData[] = array(
                                'skuId' => $skuId,
                                'specId' => $selfVal['specId'],
                                'attrId' => $selfVal['attrId'],
                            );
                        }
                        $skuSelfModel->addAll($skuSelfData);
                    }

                }
            }
        }
        if (count($updateGoodsData) > 0) {//更新商品的sku
            foreach ($updateGoodsData as $updateGoodsDetail) {
                $goodsId = $updateGoodsDetail['goodsId'];
                $hasSku = $updateGoodsDetail['hasSku'];
                $existGoodsSku = $goodsModule->getGoodsSku($goodsId, 2);
                if ($hasSku != 1 && !empty($existGoodsSku)) {//删除商品的sku信息
                    $goodsModule->deleteGoodsSkuByGoodsId($goodsId);
                }
                $skuList = $updateGoodsDetail['skuList'];
                if (!empty($skuList)) {
                    $nowSkuBarCodeArr = array_column($skuList, 'skuBarcode');
                    foreach ($existGoodsSku as $existGoodsSkuDetail) {
                        if (!in_array($existGoodsSkuDetail['systemSpec']['skuBarcode'], $nowSkuBarCodeArr)) {
                            $goodsModule->deleteGoodsSkuBySkuId($existGoodsSkuDetail['skuId']);
                        }
                    }
                    foreach ($skuList as $skuVal) {
                        $skuBarcode = $skuVal['skuBarcode'];
                        $skuWhere = array(
                            'goodsId' => $goodsId,
                            'skuBarcode' => $skuBarcode,
                        );
                        $skuDetail = $goodsModule->getSkuSystemInfoParams($skuWhere, 2);
                        if (empty($skuDetail)) {//新增
                            $skuSystemParams = $skuVal;
                            $skuSystemParams['goodsId'] = $goodsId;
                            unset($skuSystemParams['goodsSn']);
                            unset($skuSystemParams['shopId']);
                            unset($skuSystemParams['skuSelf']);
                            $skuId = $skuSystemModle->add($skuSystemParams);
                            if ($skuId) {
                                $selfList = $skuVal['skuSelf'];
                                $skuSelfData = array();
                                foreach ($selfList as $selfVal) {
                                    $skuSelfData[] = array(
                                        'skuId' => $skuId,
                                        'specId' => $selfVal['specId'],
                                        'attrId' => $selfVal['attrId'],
                                    );
                                }
                                $skuSelfModel->addAll($skuSelfData);
                            }
                        } else {
                            $skuSystemParams = $skuVal;
                            $skuSystemParams['goodsId'] = $goodsId;
                            unset($skuSystemParams['goodsSn']);
                            unset($skuSystemParams['shopId']);
                            unset($skuSystemParams['skuSelf']);
                            $saveRes = $skuSystemModle->where(array('skuId' => $skuDetail['skuId']))->save($skuSystemParams);
                            if ($saveRes !== false) {
                                $skuSelfModel->where(array('skuId' => $skuDetail['skuId']))->delete();
                                $selfList = $skuVal['skuSelf'];
                                $skuSelfData = array();
                                foreach ($selfList as $selfVal) {
                                    $skuSelfData[] = array(
                                        'skuId' => $skuDetail['skuId'],
                                        'specId' => $selfVal['specId'],
                                        'attrId' => $selfVal['attrId'],
                                    );
                                }
                                $skuSelfModel->addAll($skuSelfData);
                            }
                        }
                    }
                }
            }
        }
        if (count($editGoodsData) > 0) {
            $editGoodsRes = $goodsModel->saveAll($editGoodsData, 'wst_goods', 'goodsId');
            if ($editGoodsRes === false) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "导入失败");
            }
        }
        if (count($rankArrData) > 0) {//更新商品等级价格
            foreach ($rankArrData as $rankTag => $goodsRankDetail) {
                $rankTagArr = explode('@', $rankTag);
                $shopId = (int)$rankTagArr[0];
                $goodsSn = (string)$rankTagArr[1];
                $skuBarcode = (string)$rankTagArr[2];
                $skuId = 0;
                $goodsWhere = array(
                    'shopId' => $shopId,
                    'goodsSn' => $goodsSn,
                );
                $goodsDetail = $goodsModule->getGoodsInfoByParams($goodsWhere, 'goodsId,goodsName', 2);
                if (empty($goodsDetail)) {
                    continue;
                }
                $goodsId = (int)$goodsDetail['goodsId'];
                if (!empty($skuBarcode)) {
                    $skuWhere = array(
                        'goodsId' => $goodsId,
                        'skuBarcode' => $skuBarcode,
                    );
                    $skuDetail = $goodsModule->getSkuSystemInfoParams($skuWhere, 2);
                    if (empty($skuDetail)) {
                        continue;
                    }
                    $skuId = (int)$skuDetail['skuId'];
                }
                $goodsModule->addGoodsRank($goodsId, $skuId, $goodsRankDetail);
            }
        }
        $data = [];
        $data['importNum'] = $import_num;
        return returnData($data);
    }

    /**
     * 库存预警商品列表
     * @param $shopId
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getStockWarningGoodsList($shopId, $page = 1, $pageSize = 10)
    {
        ///$sql = "select * from __PREFIX__goods where goodsStock <= stockWarningNum and isSale=1 and goodsFlag = 1 and shopId = " . $shopId . " order by goodsStock asc ";
        $where = " goodsStock <= stockWarningNum and isSale=1 and goodsFlag = 1 and shopId={$shopId} and isBecyclebin=0 ";
        $sql = "select * from __PREFIX__goods where {$where} order by goodsStock asc ";
        return $this->pageQuery($sql, $page, $pageSize);
    }

    /**
     * 批量上下架
     * @param int $shopId 门店shopId
     * @param int $action 操作【1：有库存批量上架|2：无库存批量下架】
     * @param string goodsIdStr 多个商品id用英文逗号分隔,不传默认为全部
     */
    public function batchUpdateGoodsSale(int $shopId, int $action, string $goodsIdStr)
    {
        $goodsTab = M('goods');
        $goodsIdStr = trim($goodsIdStr, ',');
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsFlag'] = 1;
        $updateInfo = [];
        if ($action == 1) {
            //有库存批量上架
            $where['goodsStock'] = ['GT', 0];
            $updateInfo['isSale'] = 1;
        }
        if ($action == 2) {
            //无库存批量下架
            $where['goodsStock'] = ['elt', 0];
            $updateInfo['isSale'] = 0;
        }
        if (!empty($goodsIdStr)) {
            $where['goodsId'] = ['IN', $goodsIdStr];
        }
        $res = $goodsTab->where($where)->save($updateInfo);
        if ($res === false) {
            return returnData(false, -1, 'error', '更新失败');
        } else {
            return returnData(true);
        }
    }

    /**
     * @param $params
     * @return mixed
     * 获取商家出售中的商品列表【限量抢需要】
     */
    public function getSellGoodsList($params)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $shopId = $params['shopId'];
        $shopCatId1 = (int)$params['shopCatId1'];
        $shopCatId2 = (int)$params['shopCatId2'];
        $goodsCatId1 = (int)$params['goodsCatId1'];
        $goodsCatId2 = (int)$params['goodsCatId2'];
        $goodsCatId3 = (int)$params['goodsCatId3'];
        $goodsName = WSTAddslashes(I('goodsName'));
        ////商品id-商品名称-市场价-限量活动价-最小起订量-销售量-限量库存-总库存
        $field = "g.goodsId,g.goodsName,g.goodsStock,g.marketPrice,g.minBuyNum,g.limitCountActivityPrice,g.saleCount,g.limitCount,g.limit_daily";
        //商品条件 是否限量-是否限时-商品状态(-1:禁售 0:未审核 1:已审核)-删除标志(-1:删除 1:有效)
        $where = " g.isLimitBuy = 0 and g.isFlashSale = 0 and g.goodsStatus = 1 and g.goodsFlag = 1 and g.isBecyclebin=0 and g.shopId = " . $shopId;
        $sql = "select {$field} from __PREFIX__goods g where " . $where;
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodsCatId1 > 0) $sql .= " and g.goodsCatId1=" . $goodsCatId1;
        if ($goodsCatId2 > 0) $sql .= " and g.goodsCatId2=" . $goodsCatId2;
        if ($goodsCatId3 > 0) $sql .= " and g.goodsCatId3=" . $goodsCatId3;
        if ($goodsName != '') $sql .= " and (g.goodsName like '%" . $goodsName . "%' or g.goodsSn like '%" . $goodsName . "%') ";
        $sql .= " order by g.shopGoodsSort desc";
        $list = $this->pageQuery($sql, $page, $pageSize);
        return $list;
    }

    /**
     * 系统首页-商品总览
     * @param array $loginUserInfo 当前登陆者信息
     * @return array $data
     * */
    public function goodsOverview(array $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        $goodsTab = M('goods');
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsFlag'] = 1;
        $where['goodsStatus'] = 1;
        $where['isBecyclebin'] = 0;
        $where['isSale'] = 0;
        $offTheShelfNum = $goodsTab->where($where)->count();

        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsFlag'] = 1;
        $where['goodsStatus'] = 1;
        $where['isSale'] = 1;
        $where['isBecyclebin'] = 0;
        $onTheShelf = $goodsTab->where($where)->count();

        $sql = "select count('goodsId') as stockLack from __PREFIX__goods where goodsStock <= stockWarningNum and goodsFlag = 1 and shopId = {$shopId} and isBecyclebin=0 and goodsStatus=1 ";
        $stockLack = $this->queryRow($sql)['stockLack'];

        $data = [];
        $data['offTheShelf'] = (int)$offTheShelfNum;//已下架
        $data['onTheShelf'] = (int)$onTheShelf;//已上架
        $data['stockLack'] = (int)$stockLack;//库存紧张
        $data['goodsTotal'] = $offTheShelfNum + $onTheShelf;//全部商品
        return $data;
    }

    /**
     * 商家加入回收站
     * @param $goodsId 支持逗号拼接和数组
     * @return bool $data
     * */
    public function addBecyclebinGoods(array $loginUserInfo, $goodsId)
    {
        if (empty($goodsId)) {
            return false;
        }
        if (!is_array($goodsId)) {
            $goodsId = rtrim($goodsId, ',');
            $goodsId = explode(',', $goodsId);
        }
        $goodsId = array_unique($goodsId);
        $goodsTab = M('goods');
        $where['goodsId'] = ['IN', $goodsId];
        $save = [];
        $save['isSale'] = 0;
        $save['goodsStatus'] = 0;
        $updateSaleRes = $goodsTab->where($where)->save($save);//进入回收站前把商品下架
        if ($updateSaleRes === false) {
            return false;
        }
        $saveData = [];
        $binTab = M('recycle_bin');
        foreach ($goodsId as $id) {
            $where = [];
            $where['shopId'] = $loginUserInfo['shopId'];
            $where['dataId'] = $id;
            $binTab->where($where)->delete();
            $saveInfo = [];
            $saveInfo['shopId'] = $loginUserInfo['shopId'];
            $saveInfo['tableName'] = 'wst_goods';
            $saveInfo['dataId'] = $id;
            $saveInfo['createTime'] = date('Y-m-d H:i:s');
            $saveInfo['updateTime'] = date('Y-m-d H:i:s');
            $saveData[] = $saveInfo;
        }
        $insertRes = $binTab->addAll($saveData);
        if (!$insertRes) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 商品回收站
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params <p>
     * int shopCatId1 店铺商品一级分类id
     * int shopCatId2 店铺商品二级分类id
     * string keywords 关键字【商品名称/商品编码】
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * */
    public function getGoodsRecyclebin(array $loginUserInfo, array $params)
    {
        $shopId = $loginUserInfo['shopId'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = " goods.goodsFlag=1 and goods.shopId={$shopId} ";
        $where .= " and bin.status=0 and bin.tableName='wst_goods'";
        $whereFind = [];
        $whereFind['goods.shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return ['=', "{$params['shopCatId1']}", 'and'];
        };
        $whereFind['goods.shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return ['=', "{$params['shopCatId2']}", 'and'];
        };
        where($whereFind);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = " {$where} and {$whereFind} ";
        }
        if (!empty($params['keywords'])) {
            $whereInfo .= " and (goods.goodsSn like '%{$params['keywords']}%' or goods.goodsName like '%{$params['keywords']}%') ";
        }
        $field = 'bin.id';
        $field .= ',goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.shopPrice,goods.marketPrice,goods.goodsUnit,goods.memberPrice,goods.isSale,goods.goodsStatus,goods.goodsStock,goods.saleCount,goods.shopGoodsSort';
        $sql = "select {$field} from __PREFIX__goods goods 
                left join __PREFIX__recycle_bin bin on goods.goodsId=bin.dataId
                where {$whereInfo}
                order by bin.id asc
                ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        return $data;
    }

    /**
     * 回收站-删除商品
     * @param array $loginUserInfo
     * @param string|array $goodsId 商品id
     * @param $actionStatus 操作状态【-1：删除|1：撤销】
     * */
    public function delGoodsRecyclebin($loginUserInfo, $goodsId, $actionStatus)
    {
        $tab = M('recycle_bin');
        $goodsTab = M('goods');
        $skuGoodsTab = M('sku_goods_system');
        $skuGoodsSelfTab = M('sku_goods_self');
        if (!is_array($goodsId)) {
            $goodsId = rtrim($goodsId, ',');
            $goodsId = explode(',', $goodsId);
        }
        $goodsId = array_unique($goodsId);
        $where = [];
        $where['tableName'] = 'wst_goods';
        $where['dataId'] = ['IN', $goodsId];
        $binList = $tab->where($where)->select();
        M()->startTrans();
        foreach ($binList as $item) {
            if ($item['status'] != 0) {
                M()->rollback();
                return returnData(false, -1, 'error', '非待处理状态不能执行该操作');
            }
            $where = [];
            $where['id'] = $item['id'];
            $save = [];
            $save['status'] = $actionStatus;
            $save['updateTime'] = date('Y-m-d H:i:s');
            $binRes = $tab->where($where)->save($save);
            if (!$binRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '操作失败');
            }
            $where = [];
            $where['goodsId'] = $item['dataId'];
            $save = [];
            if ($actionStatus == -1) {
                //删除商品
                $save['goodsFlag'] = -1;
            } else {
                //撤销
                $save['isBecyclebin'] = 0;
            }
            $updateGoodsRes = $goodsTab->where($where)->save($save);
            if ($updateGoodsRes === false) {
                M()->rollback();
                return returnData(false, -1, 'error', '操作失败-修改商品信息失败');
            }
            //删除当前商品的sku相关联信息
            $skuList = $skuGoodsTab->where(['goodsId' => $item['dataId'], 'dataFlag' => 1])->select();
            if (!empty($skuList)) {
                $skuGoodsTab->where(['goodsId' => $item['dataId'], 'dataFlag' => 1])->save(['dataFlag' => -1]);
                $skuInfo = array_get_column($skuList, 'skuId');
                $skuId = implode(',', $skuInfo);
                $where = [];
                $where['skuId'] = ['IN', $skuId];
                $where['dataFlag'] = 1;
                $skuGoodsSelfTab->where($where)->save(['dataFlag' => -1]);
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 商品列表-商品统计
     * @param array $loginUserInfo
     * */
    public function getGoodsCount(array $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        $tab = M('goods');
        //商品列表统计
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['isBecyclebin'] = 0;
        $goodsListCount = $tab->where($where)->count();
        //已售罄商品统计
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['isBecyclebin'] = 0;
        $where['goodsStock'] = ['elt', 0];
        $soldOutCount = $tab->where($where)->count();
        //预警商品统计
        $sql = "select count('goodsId') as earlyWarningCount from __PREFIX__goods where goodsStock <= stockWarningNum and goodsFlag = 1 and shopId = {$shopId} and isBecyclebin=0 and goodsStatus=1 ";
        $earlyWarningCount = $this->queryRow($sql)['earlyWarningCount'];
        //回收站统计
        $where = [];
        $where['bin.shopId'] = $shopId;
        $where['bin.tableName'] = 'wst_goods';
        $where['bin.status'] = 0;
        $where['goods.goodsFlag'] = 1;
        $recyclebinCount = M('recycle_bin bin')
            ->join("left join wst_goods goods on goods.goodsId=bin.dataId")
            ->where($where)
            ->count();
        $data = [];
        $data['goodsListCount'] = (int)$goodsListCount;//商品列表统计
        $data['soldOutCount'] = (int)$soldOutCount;//已售罄商品统计
        $data['earlyWarningCount'] = (int)$earlyWarningCount;//预警商品统计
        $data['recyclebinCount'] = (int)$recyclebinCount;//回收站统计
        return $data;
    }

    /**
     * 获取商品列表
     * @param array $loginUserInfo
     * @param array $params <p>
     * string code 数据类型代码 【goodsList:商品列表|soldOut:已售罄商品|earlyWarning:预警商品|recyclebin:商品回收站】
     * int goodsCatId1 商城一级分类id
     * int goodsCatId2 商城二级分类id
     * int goodsCatId3 商城三级分类id
     * int shopCatId1 店铺一级分类id
     * int shopCatId2 店铺二级分类id
     * int isSale 上架状态【0:下架 | 1:上架】
     * int goodsStatus 审核状态【-1:禁售 | 0：未审核 | 1:已审核】
     * string keywords 关键字【商品名称|商品编码】
     * int SuppPriceDiff 称重商品【-1：非称重|1：称重】
     * int export 导出(0:否 1:是)
     * int syncSellingStock 同步售卖库存(0:否 1:是)
     * int purchase_type 采购类型【0：全部|1:市场自采|2:供应商供货】
     * int purchaser_or_supplier_id (采购员/供应商)id
     * </p>
     */
    public function getShopGoodsList(array $loginUserInfo, array $params)
    {
        $shopId = $loginUserInfo['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = "goods.goodsFlag=1 and goods.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['goods.goodsCatId1'] = function () use ($params) {
            if (empty($params['goodsCatId1'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId1']}", 'and'];
        };
        $whereFind['goods.goodsCatId2'] = function () use ($params) {
            if (empty($params['goodsCatId2'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId2']}", 'and'];
        };
        $whereFind['goods.goodsCatId3'] = function () use ($params) {
            if (empty($params['goodsCatId3'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId3']}", 'and'];
        };
        $whereFind['goods.shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return ['=', "{$params['shopCatId1']}", 'and'];
        };
        $whereFind['goods.shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return ['=', "{$params['shopCatId2']}", 'and'];
        };
        $whereFind['goods.isSale'] = function () use ($params) {
            if (!is_numeric($params['isSale'])) {
                return null;
            }
            return ['=', "{$params['isSale']}", 'and'];
        };
        $whereFind['goods.goodsStatus'] = function () use ($params) {
            if (!is_numeric($params['goodsStatus'])) {
                return null;
            }
            return ['=', "{$params['goodsStatus']}", 'and'];
        };
//        $whereFind['goods.SuppPriceDiff'] = function () use ($params) {
//            if (!is_numeric($params['SuppPriceDiff'])) {
//                return null;
//            }
//            return ['=', "{$params['SuppPriceDiff']}", 'and'];
//        };
        //非常规商品条件
        $whereFind['goods.isConvention'] = function () use ($params) {
            if (!is_numeric($params['isConvention'])) {
                return null;
            }
            return ['=', "{$params['isConvention']}", 'and'];
        };
        where($whereFind);

        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = " {$where} and {$whereFind} ";
        }
        if (is_numeric($params['SuppPriceDiff'])) {
            //后改,查出填写了plu_code的商品,该条件只针对于导出PLU商品
            if ($params['SuppPriceDiff'] == 1) {
                $whereInfo .= " and goods.plu_code != '' ";
            }
        }
        //采购员或供应商搜索
        if (!empty($params['purchase_type'])) {
            $whereInfo .= " and goods.purchase_type = {$params['purchase_type']} ";
            if (!empty($params['purchaser_or_supplier_id'])) {
                $whereInfo .= " and goods.purchaser_or_supplier_id = {$params['purchaser_or_supplier_id']} ";
            }
        }
        if (!empty($params['keywords'])) {
//            $whereInfo .= " and (goods.goodsName like '%{$params['keywords']}%' or goods.goodsSn like '%{$params['keywords']}%') ";
            $goods_code = $params['keywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goods_code, $match)) {
                $goods_code = strtoupper($goods_code);
            }
            $whereInfo .= " and (goods.goodsName like '%{$params['keywords']}%' or goods.py_code like '%{$goods_code}%' or goods.py_initials like '%{$goods_code}%' or goods.goodsSn like '%{$params['keywords']}%') ";
        }
        $field = 'goods.*';
        //【goodsList:商品列表|soldOut:已售罄商品|earlyWarning:预警商品|recyclebin:商品回收站】
        if ($params['code'] == 'soldOut') {
            //已售罄商品
            $whereInfo .= " and goods.isBecyclebin=0 ";
            $whereInfo .= " and goods.goodsStock <= 0 ";
            $sql = "select {$field} from __PREFIX__goods goods where {$whereInfo} ";
//            $sql .= " order by goods.shopGoodsSort desc";
        } elseif ($params['code'] == 'earlyWarning') {
            //预警商品
            $whereInfo .= " and goods.isBecyclebin=0";
            $whereInfo .= " and goods.goodsStock <= stockWarningNum ";
            $sql = "select {$field} from __PREFIX__goods goods where {$whereInfo} ";
//            $sql .= " order by goods.shopGoodsSort desc";
        } elseif ($params['code'] == 'recyclebin') {
            //商品回收站
            $whereInfo .= " and bin.status=0 and tableName='wst_goods' ";
            $sql = "select {$field} from __PREFIX__recycle_bin bin left join __PREFIX__goods goods on goods.goodsId=bin.dataId where {$whereInfo} ";
//            $sql .= " order by bin.id desc";
        } else {
            //商品列表
            $whereInfo .= " and goods.isBecyclebin=0 ";
            $sql = "select {$field} from __PREFIX__goods goods where {$whereInfo} ";
            // $sql .= " order by goods.shopGoodsSort desc";
//            $sql .= " order by goods.createTime desc";
        }
        //后改---所有的查询都走相同的排序--降序
        $sql .= " order by goods.shopCatId2 desc,goods.shopGoodsSort desc";
        $syncSellingStockGoods = array();//需要同步售卖库存的商品
        if ((int)$params['export'] == 1) {
            $list['root'] = (array)$this->query($sql);
            if ($params['syncSellingStock'] == 1) {
                $syncSellingStockGoods = $list['root'];
            }
        } elseif (empty($params['specId'])) {
            $list = $this->pageQuery($sql, $page, $pageSize);
            if ($params['syncSellingStock'] == 1) {
                $syncSellingStockGoods = $this->query($sql);
            }
        } elseif (!empty($params['specId'])) {
            $list['root'] = (array)$this->query($sql);
            if ($params['syncSellingStock'] == 1) {
                $syncSellingStockGoods = $list['root'];
            }
        }
        if (!empty($syncSellingStockGoods)) {//同步售卖库存
            $goodsIdStr = implode(',', array_column($syncSellingStockGoods, 'goodsId'));
            if (empty($goodsIdStr)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可同步的商品');
            }
            $syncRes = (new GoodsModule())->syncGoodsSellingStock($goodsIdStr);
            if (!$syncRes) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '同步售卖库存失败');
            }
        }
        //添加sku规格属性查询===============start==========================================
        if (!empty($params['specId']) && !empty($list['root'])) {
            $systemTab = M('sku_goods_system');
            $whereSku = [];
            $whereSku['specId'] = $params['specId'];
            if (!empty($params['attrId'])) {
                $whereSku['attrId'] = $params['attrId'];
            }
            $skuList = [];
            foreach ($list['root'] as $k => $v) {
                $systemSpec = $systemTab->where(['goodsId' => $v['goodsId'], 'dataFlag' => 1])->select();
                if (!empty($systemSpec)) {
                    $skuInfo = array_unique(array_get_column($systemSpec, 'skuId'));
                    $whereSku['skuId'] = ['IN', $skuInfo];
                    $whereSku['dataFlag'] = 1;
                    $selfSpec = M("sku_goods_self")->where($whereSku)->find();
                    $v['specId'] = $selfSpec['specId'];
                    $v['attrId'] = $selfSpec['attrId'];
                    if (!empty($selfSpec)) {
                        $skuList[] = $v;
                    }
                }
            }
            $count = count($skuList);
            $pageData = array_slice($skuList, ($page - 1) * $pageSize, $pageSize);
            $list['total'] = $count;
            $list['pageSize'] = $pageSize;
            $list['start'] = ($page - 1) * $pageSize;
            $list['root'] = $pageData;
            $list['totalPage'] = ($list['total'] % $pageSize == 0) ? ($list['total'] / $pageSize) : (intval($list['total'] / $pageSize) + 1);
            $list['currPage'] = $page;
        }
        //===============================end==========================================================
        if (!empty($list['root'])) {
            $goods = $list['root'];
            $shopCatIdArr = [];//店铺分类id
            $goodsCatIdArr = [];//商城分类id
            $purchase_id_arr = [];
            $supplier_id_arr = [];
            $purchase_map = [];
            $supplier_map = [];
            foreach ($goods as $value) {
                $shopCatIdArr[] = $value['shopCatId1'];
                $shopCatIdArr[] = $value['shopCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId1'];
                $goodsCatIdArr[] = $value['goodsCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId3'];
                if ($value['purchase_type'] == 1) {
                    $purchase_id_arr[] = $value['purchaser_or_supplier_id'];
                } elseif ($value['purchase_type'] == 2) {
                    $supplier_id_arr[] = $value['purchaser_or_supplier_id'];
                }
            }
            if (!empty($purchase_id_arr)) {
                $shopStaffModule = new ShopStaffMemberModule();
                $purchase_id_arr = array_unique($purchase_id_arr);
                $purchase_list = $shopStaffModule->getShopStaffMemberListByIdArr($purchase_id_arr);
                foreach ($purchase_list as $purchase_list_row) {
                    $purchase_map[$purchase_list_row['id']] = $purchase_list_row['username'];
                }
            }
            if (!empty($supplier_id_arr)) {
                $supplier_id_arr = array_unique($supplier_id_arr);
                $supplierModule = new SupplierModule();
                $supplier_list = $supplierModule->getSupplierListByIdArr($supplier_id_arr);
                foreach ($supplier_list as $supplier_list_row) {
                    $supplier_map[$supplier_list_row['supplierId']] = $supplier_list_row['supplierName'];
                }
            }
            $shopCatIdArr = array_unique($shopCatIdArr);
            $shopCatIdStr = implode(',', $shopCatIdArr);
            $shopCatList = M('shops_cats')->where(['catId' => ['IN', $shopCatIdStr]])->select();

            $goodsCatIdArr = array_unique($goodsCatIdArr);
            $goodsCatIdStr = implode(',', $goodsCatIdArr);
            $goodsCatIdList = M('goods_cats')->where(['catId' => ['IN', $goodsCatIdStr]])->select();

            foreach ($goods as $key => $value) {
                $goods[$key]['purchaser_or_supplier_name'] = "";
                if ($value['purchase_type'] == 1) {
                    $goods[$key]['purchaser_or_supplier_name'] = $purchase_map[$value['purchaser_or_supplier_id']];
                } elseif ($value['purchase_type'] == 2) {
                    $goods[$key]['purchaser_or_supplier_name'] = $supplier_map[$value['purchaser_or_supplier_id']];
                }
                $where = [];
                $where['dataFlag'] = 1;
                $where['goodsId'] = $value['goodsId'];
                $goods[$key]['goodsAttrData'] = M('sku_goods_system')->where($where)->select();
                foreach ($shopCatList as $shopCat) {
                    if ($value['shopCatId1'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId1Name'] = $shopCat['catName'];
                    }
                    if ($value['shopCatId2'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId2Name'] = $shopCat['catName'];
                    }
                }

                foreach ($goodsCatIdList as $goodsCat) {
                    if ($value['goodsCatId1'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId1Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId2'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId2Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId3'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId3Name'] = $goodsCat['catName'];
                    }
                }
            }
            $list['root'] = $goods;
        }
        $list['root'] = getGoodsSku($list['root']);
        if ((int)$params['export'] == 1) {
            $this->exportGoods($list['root']);
        }
        return $list;
    }

    /**
     * 获取审核商品列表
     * @param array $loginUserInfo
     * -int shopCatId1 店铺一级分类id
     * -int shopCatId2 店铺二级分类id
     * -int goodsStatus 审核状态(-1:禁售 0:未审核 默认为全部(禁售和未审核))
     * -string keywords 关键字【商品名称|商品编码】
     * @return array
     */
    public function getExamineGoodsList(array $loginUserInfo, array $params)
    {
        $shopId = $loginUserInfo['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " goodsFlag=1 and shopId={$shopId} and goodsStatus IN(-1,0) and isBecyclebin=0 ";
        $whereFind = array();
        $whereFind['shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return ['=', "{$params['shopCatId1']}", 'and'];
        };
        $whereFind['shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return ['=', "{$params['shopCatId2']}", 'and'];
        };
        $whereFind['goodsStatus'] = function () use ($params) {
            if (!is_numeric($params['goodsStatus'])) {
                return null;
            }
            return ['=', "{$params['goodsStatus']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = " {$where} and {$whereFind} ";
        }
        if (!empty($params['keywords'])) {
            $whereInfo .= " and (goodsName like '%{$params['keywords']}%' or goodsSn like '%{$params['keywords']}%') ";
        }
        $field = 'goodsId,goodsSn,goodsName,goodsImg,shopId,marketPrice,shopPrice,shopCatId1,shopCatId2,goodsStatus,shopGoodsSort,goodsStock,saleCount';
        $sql = "select {$field} from __PREFIX__goods goods where {$whereInfo} ";
        $sql .= " order by shopGoodsSort desc";
        $list = $this->pageQuery($sql, $page, $pageSize);
        //===============================end==========================================================
        if (!empty($list['root'])) {
            $goods = $list['root'];
            $shopCatIdArr = [];//店铺分类id
            foreach ($goods as $value) {
                $shopCatIdArr[] = $value['shopCatId1'];
                $shopCatIdArr[] = $value['shopCatId2'];
            }
            $shopCatIdArr = array_unique($shopCatIdArr);
            $shopCatIdStr = implode(',', $shopCatIdArr);
            $shopCatList = M('shops_cats')->where(array('catId' => ['IN', $shopCatIdStr]))->select();
            foreach ($goods as $key => $value) {
                foreach ($shopCatList as $shopCat) {
                    if ($value['shopCatId1'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId1Name'] = $shopCat['catName'];
                    }
                    if ($value['shopCatId2'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId2Name'] = $shopCat['catName'];
                    }
                }
            }
            $list['root'] = $goods;
        }
        return $list;
    }

    /**
     * 添加商品日志
     * @param array $actionUserInfo
     * @param array $primaryGoodsInfo 原商品信息
     * @param string remark 操作描述
     * @return int $logId
     * */
    public function addGoodsLog(array $actionUserInfo, array $primaryGoodsInfo, $remark = '')
    {
        if (empty($primaryGoodsInfo['goodsId'])) {
            return 0;
        }
        $tab = M('goods_log');
        $goodsTab = M('goods');
        $where = [];
        $where['goodsId'] = $primaryGoodsInfo['goodsId'];
        $nowGoodsInfo = $goodsTab->where($where)->find();
        if (empty($nowGoodsInfo)) {
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
        return (int)$insertId;
    }

    /**
     * 获取商品日志
     * @param array $params <p>
     * int page
     * int pageSize
     * </p>
     * @return array $data
     * */
    public function getGoodsLog(array $params)
    {
        $goodsId = (int)$params['goodsId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " goodsId={$goodsId} ";
        $sql = "select * from __PREFIX__goods_log where {$where} order by id desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        return $data;
    }

    /**
     * 商品-商品定时上下架-新增
     * @param string $goods_id_str 商品id,多个用英文逗号分隔
     * @param int $sale_state 商家状态(0:下架 1:上架)
     * @param string $action_time 操作时间(时分)
     * @return array
     * */
    public function addAutoGoods(array $params)
    {
        $goods_id_arr = array_unique(explode(',', $params['goods_id_str']));
        $auto_goods_table = new AutoSaleGoodsModel();
        $service_module = new GoodsServiceModule();
        $insert_data = array();
        foreach ($goods_id_arr as $goods_id) {
            $where = array(
                'goods_id' => $goods_id,
                'sale_state' => $params['sale_state'],
                'is_delete' => 0,
            );
            $auto_info = $auto_goods_table->where($where)->find();
            $goods_result = $service_module->getGoodsInfoById($goods_id, 'goodsId,goodsName');
            $goods_data = $goods_result['data'];
            if (empty($goods_data)) {
                continue;
            }
            if ($auto_info) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$goods_data['goodsName']}已存在，请勿重复添加");
            }
            $insert_info = array(
                'goods_id' => $goods_id,
                'sale_state' => $params['sale_state'],
                'action_time' => $params['action_time'],
                'create_time' => time(),
            );
            $insert_data[] = $insert_info;
        }
        $res = $auto_goods_table->addAll($insert_data);
        if (!$res) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '添加失败');
        }
        return returnData(true);
    }

    /**
     * 商品-商品定时上下架-修改
     * @param string $id_str 自增id,多个用英文逗号分隔
     * @param int $sale_state 商家状态(0:下架 1:上架)
     * @param string $action_time 操作时间(时分)
     * @return array
     * */
    public function updateAutoGoods(array $params)
    {
        $id_arr = array_unique(explode(',', $params['id_str']));
        $auto_goods_table = new AutoSaleGoodsModel();
        foreach ($id_arr as $id) {
            $where = array(
                'id' => $id,
                'is_delete' => 0,
            );
            $auto_info = $auto_goods_table->where($where)->find();
            if (!$auto_info) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请输入正确的参数");
            }
            $save_info = array(
                'sale_state' => $params['sale_state'],
                'action_time' => $params['action_time'],
                'update_time' => time(),
            );
            $where = array(
                'id' => $auto_info['id']
            );
            $res = $auto_goods_table->where($where)->save($save_info);
            if (!$res) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败");
            }
        }
        return returnData(true);
    }

    /**
     * 商品-商品定时上下架-删除
     * @param stirng $id_str
     * @return array $data
     * */
    public function deleteAutoGoods(string $id_str)
    {
        $auto_goods_table = new AutoSaleGoodsModel();
        $where = array(
            'id' => array('IN', $id_str)
        );
        $save_info = array(
            'is_delete' => 1,
            'delete_time' => time(),
        );
        $res = $auto_goods_table->where($where)->save($save_info);
        if (!$res) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "删除失败");
        }
        return returnData(true);
    }

    /**
     * 获取商品列表
     * @param array $login_info
     * @param array $params <p>
     * int shopCatId1 店铺一级分类id
     * int shopCatId2 店铺二级分类id
     * int sale_state 上架状态【0:下架 | 1:上架】
     * string keywords 关键字【商品名称|商品编码】
     * </p>
     * @return array
     */
    public function getAutoGoodsList(array $login_info, array $params)
    {
        $shop_id = $login_info['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = "goods.goodsFlag=1 and goods.shopId={$shop_id} and goods.isBecyclebin=0 and auto.is_delete=0 ";
        $where_find = array();
        $where_find['goods.shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return array('=', "{$params['shopCatId1']}", 'and');
        };
        $where_find['goods.shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return array('=', "{$params['shopCatId2']}", 'and');
        };
        $where_find['auto.sale_state'] = function () use ($params) {
            if (!is_numeric($params['sale_state'])) {
                return null;
            }
            return array('=', "{$params['sale_state']}", 'and');
        };
        $where_find['auto.create_time'] = function () use ($params) {
            if (empty($params['start_time']) || empty($params['end_time'])) {
                return null;
            }
            $params['start_time'] = strtotime($params['start_time']);
            $params['end_time'] = strtotime($params['end_time']);
            return array('between', "{$params['start_time']}' and '{$params['end_time']}", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_info = " {$where} and {$where_find} ";
        }
        if (!empty($params['keywords'])) {
            $where_info .= " and (goods.goodsName like '%{$params['keywords']}%' or goods.goodsSn like '%{$params['keywords']}%') ";
        }
        $field = 'goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsImg,goods.shopCatId1,goods.shopCatId2';
        $field .= ',auto.id,auto.sale_state,auto.action_time,auto.create_time';
        //商品列表
        $sql = "select {$field} from __PREFIX__goods goods left join __PREFIX__auto_sale_goods auto on auto.goods_id=goods.goodsId where {$where_info} ";
        $sql .= " order by auto.create_time asc";
        $result = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($result['root'])) {
            $goods = $result['root'];
            $shop_catid_arr = array();
            foreach ($goods as $value) {
                $shop_catid_arr[] = $value['shopCatId1'];
                $shop_catid_arr[] = $value['shopCatId2'];
            }
            $shop_catid_arr = array_unique($shop_catid_arr);
            $shop_catid_str = implode(',', $shop_catid_arr);
            $where = array(
                'catId' => array('IN', $shop_catid_str)
            );
            $shop_cat_list = M('shops_cats')->where($where)->select();
            foreach ($goods as $key => $value) {
                $goods[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                foreach ($shop_cat_list as $cat_list) {
                    if ($value['shopCatId1'] == $cat_list['catId']) {
                        $goods[$key]['shopCatId1Name'] = $cat_list['catName'];
                    }
                    if ($value['shopCatId2'] == $cat_list['catId']) {
                        $goods[$key]['shopCatId2Name'] = $cat_list['catName'];
                    }
                }
            }
            $result['root'] = $goods;
        }
        return $result;
    }

    /**
     * 商品-商品定时上下架-批量修改状态
     * @param string $id_str 自增id,多个用英文逗号分隔
     * @param int $sale_state 上架状态(0:下架 1:上架)
     * @return array
     * */
    public function updateAutoGoodsState(string $id_str, int $sale_state)
    {
        $auto_goods_table = new AutoSaleGoodsModel();
        $where = array(
            'id' => array('IN', $id_str)
        );
        $save_info = array(
            'sale_state' => $sale_state,
            'update_time' => time(),
        );
        $res = $auto_goods_table->where($where)->save($save_info);
        if (!$res) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败");
        }
        return returnData(true);
    }

    /**
     * 定时上下架商品
     * */
    public function autoSaleGoods()
    {
        $date = date('H:i');
        $auto_goods_tab = new AutoSaleGoodsModel();
        $where = array(
            'action_time' => $date,
            'is_delete' => 0
        );
        $auto_list = $auto_goods_tab->where($where)->select();
        $on_goods_id = array();//需要上架的商品id
        $off_goods_id = array();//需要下架的商品id
        foreach ($auto_list as $item) {
            if ($item['sale_state'] == 1) {
                $on_goods_id[] = $item['goods_id'];
            } else {
                $off_goods_id[] = $item['goods_id'];
            }
        }
        $goods_tab = new GoodsModel();
        if (!empty($on_goods_id)) {
            $where = array(
                'goodsId' => array('IN', implode(',', $on_goods_id))
            );
            $save = array(
                'isSale' => 1
            );
            $goods_tab->where($where)->save($save);//上架商品
        }
        if (!empty($off_goods_id)) {
            $where = array(
                'goodsId' => array('IN', implode(',', $off_goods_id))
            );
            $save = array(
                'isSale' => 0
            );
            $goods_tab->where($where)->save($save);//下架商品
        }
    }
}
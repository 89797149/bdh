<?php
namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\JxcReinsurancePolicyModel;
use App\Models\SkuGoodsSelfModel;
use App\Models\SkuGoodsSystemModel;
use App\Models\SkuSpecAttrModel;
use App\Models\SkuSpecModel;
use App\Modules\Erp\AllocationBillModule;
use App\Modules\Erp\ErpModule;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Purchase\PurchaseModule;
use function Couchbase\defaultDecoder;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * Erp相关
 */
class ErpModel extends BaseModel
{
    /**
     * 根据erp商品分类父id获取商城分类
     * @param int $parentId 商城分类父id
     */
    public function getErpGoodsCatByParentId(int $parentId)
    {
        $where = [];
        $where['isShow'] = 1;
        $where['dataFlag'] = 1;
        $where['parentId'] = $parentId;
        $rs = M('jxc_goods_cat')->where($where)->order('catid desc')->select();
        if (empty($rs)) {
            return [];
        }
        return (array)$rs;
    }

    /**
     * 已废弃
     * 获取总仓商品列表(用于采购和向总仓调拨商品)
     * @param array $params [
     *                          int goodsCat1 商城一级分类
     *                          int goodsCat2 商城二级分类
     *                          int goodsCat3 商城三级分类
     *                          string goodsName 商品名称
     *                          string goodsSn 商品编码/条码
     *                          int page 分页,默认为1
     *                          pageSize 分页条数,默认15条
     *              ]
     */
//    public function getErpGoodsList(array $params){
//        $goodsCat1 = $params['goodsCat1'];
//        $goodsCat2 = $params['goodsCat2'];
//        $goodsCat3 = $params['goodsCat3'];
//        $goodsName = $params['goodsName'];
//        $goodsSn = $params['goodsSn'];
//        $page = $params['page'];
//        $pageSize = $params['pageSize'];
//        $where = " g.dataFlag=1 and g.isSale=1 and g.examineStatus=1 ";
//        if(!empty($goodsCat1)){
//            $where .= " and g.goodsCat1='".$goodsCat1."'";
//        }
//        if(!empty($goodsCat2)){
//            $where .= " and g.goodsCat2='".$goodsCat2."'";
//        }
//        if(!empty($goodsCat3)){
//            $where .= " and g.goodsCat3='".$goodsCat3."'";
//        }
//        if(!empty($goodsName)){
//            $where .= " and g.goodsName like '%".$goodsName."%'";
//        }
//        if(!empty($goodsSn)){
//            $where .= " and g.goodsSn like '%".$goodsSn."%'";
//        }
//        $sql = "select g.goodsId,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.retailPrice,g.sellPrice,g.buyPirce,g.isSale,g.saleTime,g.goodsCat1,g.goodsCat2,g.goodsCat3,g.createTime,g.sort,g.goodsUnit,g.stockWarning,g.liveDate,g.warehousingTime,g.stock,g.standardProduct,g.weightG,g.endLiveDate from __PREFIX__jxc_goods as g where $where order by goodsId desc";
//        $rs = $this->pageQuery($sql,$page,$pageSize);
//        if(!empty($rs['root'])){
//            $goods = $rs['root'];
//            $goodsCatIds = [];
//            foreach ($goods as $value){
//                $goodsCatIds[] = $value['goodsCat1'];
//                $goodsCatIds[] = $value['goodsCat2'];
//                $goodsCatIds[] = $value['goodsCat3'];
//            }
//            $goodsCatIds = array_unique($goodsCatIds);
//            $catWhere = [];
//            $catWhere['catid'] = ["IN",$goodsCatIds];
//            $catWhere['dataFlag'] = 1;
//            $catWhere['isShow'] = 1;
//            $goodsCatList = M('jxc_goods_cat')->where($catWhere)->select();
//            foreach ($goods as $key=>$value){
//                foreach ($goodsCatList as $val){
//                    if($value['goodsCat1'] == $val['catid']){
//                        $goods[$key]['goodsCat1Name'] = $val['catname'];
//                    }
//                    if($value['goodsCat2'] == $val['catid']){
//                        $goods[$key]['goodsCat2Name'] = $val['catname'];
//                    }
//                    if($value['goodsCat3'] == $val['catid']){
//                        $goods[$key]['goodsCat3Name'] = $val['catname'];
//                    }
//                }
//            }
//            $rs['root'] = $goods;
//        }
//        return $rs;
//    }

    /**
     * 获取门仓商品列表(用于向门店调拨)
     * @param array $params [
     *                          int shopId 门仓id
     *                          int goodsCat1 商城一级分类
     *                          int goodsCat2 商城二级分类
     *                          int goodsCat3 商城三级分类
     *                          string goodsName 商品名称
     *                          string goodsSn 商品编码/条码
     *                          int page 分页,默认为1
     *                          pageSize 分页条数,默认15条
     *              ]
     */
    public function getShopGoodsList(array $params)
    {
        $shopId = $params['shopId'];
        $goodsCat1 = $params['goodsCat1'];
        $goodsCat2 = $params['goodsCat2'];
        $goodsCat3 = $params['goodsCat3'];
        $goodsName = $params['goodsName'];
        $goodsSn = $params['goodsSn'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " g.goodsFlag=1 and g.isSale=1  and g.shopId='" . $shopId . "'";
        if (!empty($goodsCat1)) {
            $where .= " and g.goodsCatId1='" . $goodsCat1 . "'";
        }
        if (!empty($goodsCat2)) {
            $where .= " and g.goodsCatId2='" . $goodsCat2 . "'";
        }
        if (!empty($goodsCat3)) {
            $where .= " and g.goodsCatId3='" . $goodsCat3 . "'";
        }
        if (!empty($goodsName)) {
            $where .= " and g.goodsName like '%" . $goodsName . "%'";
        }
        if (!empty($goodsSn)) {
            $where .= " and g.goodsSn like '%" . $goodsSn . "%'";
        }
        $sql = "select g.shopId,g.goodsId,g.weightG,g.goodsCatId1,g.goodsCatId2,g.goodsCatId3,g.SuppPriceDiff,g.goodsSn,g.shopGoodsSort,g.goodsName,g.goodsImg,g.goodsThums,g.shopPrice,g.goodsStock,g.saleCount,g.isSale,g.isRecomm,g.isHot,g.isBest,g.isNew,g.saleTime,g.createTime,g.memberPrice,g.integralRate from __PREFIX__goods as g where $where order by goodsId desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($rs['root'])) {
            $goods = $rs['root'];
            $goodsCatIds = [];
            foreach ($goods as $value) {
                $goodsCatIds[] = $value['goodsCatId1'];
                $goodsCatIds[] = $value['goodsCatId2'];
                $goodsCatIds[] = $value['goodsCatId3'];
            }
            $goodsCatIds = array_unique($goodsCatIds);
            $catWhere = [];
            $catWhere['catId'] = ["IN", $goodsCatIds];
            $catWhere['catFlag'] = 1;
            $catWhere['isShow'] = 1;
            $goodsCatList = (array)M('goods_cats')->where($catWhere)->select();
            foreach ($goods as $key => $value) {
                foreach ($goodsCatList as $val) {
                    if ($value['goodsCatId1'] == $val['catId']) {
                        $goods[$key]['goodsCat1Name'] = $val['catName'];
                    }
                    if ($value['goodsCatId2'] == $val['catId']) {
                        $goods[$key]['goodsCat2Name'] = $val['catName'];
                    }
                    if ($value['goodsCatId3'] == $val['catId']) {
                        $goods[$key]['goodsCat3Name'] = $val['catName'];
                    }
                }
            }
            $rs['root'] = $goods;
        }
        return $rs;
    }

    /**
     * 获取商品详情(PS:主义门店商品和总仓商品详情,字段是不太一样的)
     * @param int shopId 门仓id PS:传0或不传则为总仓
     * @param int goodsId 商品id
     */
    public function getGoodsDetail(int $goodsId, $shopId = 0)
    {
        if ($shopId > 0) {
            $goodsInfo = $this->getShopGoodsDetail($goodsId, $shopId);
        } else {
            $goodsInfo = $this->getErpGoodsDetail($goodsId);
        }
        return (array)$goodsInfo;
    }


    /**
     * 获取门店商品详情
     * @param int $goodsId 商品id
     * @param int $shopId 店铺id
     * */
    public function getShopGoodsDetail(int $goodsId, int $shopId)
    {
        if (empty($shopId)) {
            return [];
        }
        //门仓商品详情
        $goodsWhere = [];
        $goodsWhere['goodsId'] = $goodsId;
        $goodsWhere['shopId'] = $shopId;
        $goodsInfo = (array)M('goods')->where($goodsWhere)->field("spec", true)->find();
        if (!$goodsInfo) {
            return [];
        }
        $goodsCat1 = $this->getGoodsCatInfo($goodsInfo['goodsCatId1']);
        $goodsCat2 = $this->getGoodsCatInfo($goodsInfo['goodsCatId2']);
        $goodsCat3 = $this->getGoodsCatInfo($goodsInfo['goodsCatId3']);
        $goodsInfo['goodsCat1Name'] = (string)$goodsCat1['catName'];
        $goodsInfo['goodsCat2Name'] = (string)$goodsCat2['catName'];
        $goodsInfo['goodsCat3Name'] = (string)$goodsCat3['catName'];

        //添加商户Id条件
        $galleryWhere = [];
        $galleryWhere['goodsId'] = $goodsId;
        $galleryWhere['shopId'] = $shopId;
        $goodsInfo['gallery'] = (array)M('goods_gallerys')->where($galleryWhere)->select();

        $systemWhere = [];
        $systemWhere['goodsId'] = $goodsId;
        $systemWhere['dataFlag'] = 1;
        $goodsInfo['skuGoodsSystem'] = (array)M('sku_goods_system')->where($systemWhere)->select();
        if (count($goodsInfo['skuGoodsSystem']) <= 0) {
            return $goodsInfo;
        }
        foreach ($goodsInfo['skuGoodsSystem'] as $k => $v) {
            $skuId[] = $v['skuId'];
        }
        $skuWhere = [];
        $skuWhere['s.skuId'] = ["IN", $skuId];
        $skuList = M('sku_goods_self s')->where($skuWhere)
            ->join("left join wst_sku_spec a on s.specId = a.specId")
            ->join("left join wst_sku_spec_attr b on s.attrId = b.attrId ")
            ->field('a.specId,a.specName,b.attrId,b.attrName,skuId')
            ->group('a.specId')
            ->select();
        foreach ($goodsInfo['skuGoodsSystem'] as $k => $v) {
            $skuSpecAttr = [];
            foreach ($skuList as $kk => $vv) {
                if ($vv['skuId'] == $v['skuId']) {
                    $skuSpecAttr[] = $vv;
                }
            }
            $goodsInfo['skuGoodsSystem'][$k]['skuSpecAttr'] = $skuSpecAttr;
        }
        return (array)$goodsInfo;
    }

    /*
     * 根据商品id获取erp商品详情
     * @param int $goodsId
     * */
    public function getErpGoodsDetail(int $goodsId)
    {
        //总仓商品详情
        $goodsInfo = (array)M('jxc_goods')->where(['goodsId' => $goodsId])->find();
        if (!$goodsInfo) {
            return [];
        }
        $goodsInfo['unitName'] = '';
        if (!empty($goodsInfo['unitId'])) {
            $unitName = M('jxc_goods_unit')->where(['id' => $goodsInfo['unitId'], 'dataFlag'])->getField('goodsUnit');
            $goodsInfo['unitName'] = $unitName;
        }
        $goodsInfo['buyPirce'] = $goodsInfo['sellPrice'];
        $goodsCat1 = $this->getErpGoodsCatInfo($goodsInfo['goodsCat1']);
        $goodsCat2 = $this->getErpGoodsCatInfo($goodsInfo['goodsCat2']);
        $goodsCat3 = $this->getErpGoodsCatInfo($goodsInfo['goodsCat3']);
        $goodsInfo['goodsCat1Name'] = (string)$goodsCat1['catname'];
        $goodsInfo['goodsCat2Name'] = (string)$goodsCat2['catname'];
        $goodsInfo['goodsCat3Name'] = (string)$goodsCat3['catname'];
        $galleryWhere = [];
        $galleryWhere['goodsId'] = $goodsId;
        $goodsInfo['gallery'] = (array)M('jxc_goods_gallerys')->where($galleryWhere)->order('id asc')->select();
        $systemWhere = [];
        $systemWhere['goodsId'] = $goodsId;
        $systemWhere['dataFlag'] = 1;
        $systemWhere['examineStatus'] = 1;
        $goodsInfo['skuGoodsSystem'] = (array)M('jxc_sku_goods_system')->where($systemWhere)->select();
        if (count($goodsInfo['skuGoodsSystem']) <= 0) {
            return $goodsInfo;
        }
        foreach ($goodsInfo['skuGoodsSystem'] as $k => $v) {
            $skuId[] = $v['skuId'];
        }
        $skuWhere = [];
        $skuWhere['s.skuId'] = ["IN", $skuId];
        $skuWhere['s.dataFlag'] = 1;
        $skuList = M('jxc_sku_goods_self s')->where($skuWhere)
            ->join("left join wst_jxc_sku_spec a on s.specId = a.specId")
            ->join("left join wst_jxc_sku_spec_attr b on s.attrId = b.attrId ")
            ->field('a.specId,a.specName,b.attrId,b.attrName,skuId,a.sort as specSort,b.sort as attrSort')
            ->select();
        foreach ($goodsInfo['skuGoodsSystem'] as $k => $v) {
            $goodsInfo['skuGoodsSystem'][$k]['buyPirce'] = $v['sellPrice'];
            $skuSpecAttr = [];
            foreach ($skuList as $kk => $vv) {
                if ($vv['skuId'] == $v['skuId']) {
                    $skuSpecAttr[] = $vv;
                }
            }
            $goodsInfo['skuGoodsSystem'][$k]['skuSpecAttr'] = $skuSpecAttr;
            $goodsInfo['skuGoodsSystem'][$k]['spec_attr_str'] = implode('', array_column($skuSpecAttr, 'attrName'));
        }
        return $goodsInfo;
    }

    /*
     * 根据分类id获取erp商品商城分类详情
     * @param int catid 分类id
     * */
    public function getErpGoodsCatInfo(int $catid)
    {
        $catInfo = M('jxc_goods_cat')->where(['catid' => $catid])->find();
        if (!$catInfo) {
            return [];
        }
        return (array)$catInfo;
    }

    /*
     * 根据分类id获取门仓商品商城分类详情
     * @param int catid 分类id
     * */
    public function getGoodsCatInfo(int $catid)
    {
        $catInfo = M('goods_cats')->where(['catId' => $catid])->find();
        if (!$catInfo) {
            return [];
        }
        return (array)$catInfo;
    }

    /**
     *获取门仓列表 PS:用于向某个门仓申请调拨单
     * @param array $params [
     *                          string shopName 门仓名称
     *                          string shopSn 门仓编号
     *                          int page 分页,默认为1
     *                          pageSize 分页条数,默认15条
     *              ]
     */
    public function getShopList(array $params)
    {
        $shopName = $params['shopName'];
        $shopSn = $params['shopSn'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " shopFlag=1 and shopAtive=1 and shopStatus=1 ";
        if (!empty($shopName)) {
            $where .= " and shopName like '%" . $shopName . "%'";
        }
        if (!empty($shopSn)) {
            $where .= " and shopSn like '%" . $shopSn . "%'";
        }
        $sql = "select shopId,shopSn,shopName,shopImg from __PREFIX__shops where $where order by shopId desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * 添加采购单
     * @param array $params
     *-int shopId 门店id
     *-string remark 备注
     *-array detail 采购商品详情
     * -array purchaseGoodsWhere 一键采购页的搜索条件,仅仅针对一键采购页的商品
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function addPurchaseOrder(array $params, array $shopInfo)
    {
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $dbTrans = new Model();
        $dbTrans->startTrans();
        // 启动事务
        $billData = array();
        $billData['shopId'] = $shopInfo['shopId'];
        $billData['actionUserId'] = $actionUserId;
        $billData['actionUserName'] = $actionUserName;
        $billData['shopName'] = $shopInfo['shopName'];
        $billData['dataFrom'] = $shopInfo['login_type'];
        $billData['remark'] = $params['remark'];
        $billData['createTime'] = date('Y-m-d H:i:s');
        $erpModule = new ErpModule();
        $otpId = $erpModule->savePurchaseOrder($billData, $dbTrans);
        if (end($otpId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败');
        }
        $number = 'CGD' . date('Ymd') . str_pad($otpId, 10, "0", STR_PAD_LEFT);
        $orderGoods = array();
        $erpConfig = $erpModule->getErpConfig();
        if (empty($params['detail'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '采购明细异常');
        }
        $localGoodsModule = new GoodsModule();
        $detail = $params['detail'];
        $sumTotalNum = [];//统计采购商品的总数量
        $sumDataAmount = [];//统计采购商品的金额
        $localGoodsModel = new GoodsModel();
        $localSpecTab = new SkuSpecModel();
        $localAttrTab = new SkuSpecAttrModel();
        $localSystemTab = new SkuGoodsSystemModel();
        $localSelfTab = new SkuGoodsSelfModel();
        $purchaseGoodsList = array();//订单采购商品数据
        foreach ($detail as $value) {
            $goodsId = $value['goodsId'];
            $erpGoodsInfo = $this->getGoodsDetail($goodsId, 0);//总仓商品详情
            if (empty($erpGoodsInfo)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '采购商品信息异常');
            }
            //采购单创建成功,如果采购方没有对应的商品,创建本地商品到仓库中
            $localGoodsWhere = array(
                'shopId' => $shopInfo['shopId'],
                'goodsSn' => $erpGoodsInfo['goodsSn'],
            );
            $localGoodsInfo = $localGoodsModule->getGoodsInfoByParams($localGoodsWhere, 'goodsId,goodsName', 2);
            if (empty($localGoodsInfo)) {
                //采购方商品基本信息
                $localGoodsInfo = array();
                $localGoodsInfo['shopId'] = $shopInfo['shopId'];
                $localGoodsInfo['goodsSn'] = $erpGoodsInfo['goodsSn'];
                $localGoodsInfo['goodsName'] = $erpGoodsInfo['goodsName'];
                $localGoodsInfo['goodsImg'] = $erpGoodsInfo['goodsImg'];
                $localGoodsInfo['goodsThums'] = $erpGoodsInfo['goodsThums'];
                $localGoodsInfo['goodsDesc'] = $erpGoodsInfo['goodsDetail'];
                $localGoodsInfo['marketPrice'] = $erpGoodsInfo['retailPrice'];
                $localGoodsInfo['shopPrice'] = $erpGoodsInfo['retailPrice'];
                $localGoodsInfo['goodsUnit'] = $erpGoodsInfo['sellPrice'];
                $localGoodsInfo['goodsStock'] = 0;
                $localGoodsInfo['goodsStatus'] = 1;
                $localGoodsInfo['saleTime'] = date('Y-m-d H:i:s');
                $localGoodsInfo['createTime'] = date('Y-m-d H:i:s');
                $localGoodsInfo['SuppPriceDiff'] = $erpGoodsInfo['standardProduct'];
                $localGoodsInfo['weightG'] = $erpGoodsInfo['weightG'];
                $localGoodsInfo['isSale'] = 0;
                $localGoodsInfo['goodsId'] = $localGoodsModel->add($localGoodsInfo);
                if (empty($localGoodsInfo['goodsId'])) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '门店商品创建失败');
                }
                //采购方商品轮播
                if (!empty($erpGoodsInfo['gallery'])) {
                    $addGalleryRes = $localGoodsModule->addGoodsGallerys($erpGoodsInfo['gallery'], $dbTrans);
                    if (!$addGalleryRes) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '门店商品轮播图添加失败');
                    }
                }
            }
            if (empty($value['sku'])) {
                //处理没有sku的情况
                $handleSku = [];
                $handleSku['skuId'] = 0;
                $handleSku['remark'] = '';
                if (empty($value['totalNum'])) {
                    $dbTrans->rollback();
                    return returnData(null, -1, 'error', '请输入商品[' . $erpGoodsInfo['goodsName'] . ']正确的采购数量');
                }
                $handleSku['totalNum'] = $value['totalNum'];
                if (!empty($value['remark'])) {
                    $handleSku['remark'] = $value['remark'];
                }
                $value['sku'][] = $handleSku;
            }
            $res = [];
            foreach ($value['sku'] as $skuVal) {
                if (isset($res[$skuVal['skuId']])) {
                    unset($skuVal['skuId']);
                } else {
                    $res[$skuVal['skuId']] = $skuVal;
                }
            }
            //$res = array_values($res);
            $oldCount = count($value['sku']);
            $newCount = count($res);
            if ($newCount < $oldCount) {
                $dbTrans->rollback();
                return returnData(null, -1, 'error', '商品[' . $erpGoodsInfo['goodsName'] . ']的skuId重复,请检查');
            }
            $price = $erpGoodsInfo['sellPrice'];
            foreach ($value['sku'] as $v) {
                $skuId = $v['skuId'];
                $skuInfo = array();
                if ($skuId > 0) {
                    $skuInfo = $this->getSkuDetail($skuId);
                    if (!empty($skuInfo['skuId']) && $skuInfo['goodsId'] == $goodsId) {
                        $price = $skuInfo['sellPrice'];
                    } else {
                        $dbTrans->rollback();
                        return returnData(null, -1, 'error', '商品[' . $erpGoodsInfo['goodsName'] . ']的skuId不匹配,错误的skuId' . ":" . $skuId);
                    }
                }
                if ($v['totalNum'] <= 0) {
                    $dbTrans->rollback();
                    return returnData(null, -1, 'error', '采购数量必须大于0');
                }
                //后加,校验总仓库存是否充足-start
                $spec_attr_str = '';
                if (!empty($erpConfig['purchase_stock_check'])) {//开启了总仓库存校验
                    if ($skuId > 0) {
                        $curr_stock = $skuInfo['stock'];
                        $spec_attr_str = '#' . $skuInfo['spec_attr_str'];
                    } else {
                        $curr_stock = $erpGoodsInfo['stock'];
                    }
                    if ($curr_stock < $v['totalNum']) {
                        $dbTrans->rollback();
                        return returnData(null, -1, 'error', "商品[{$erpGoodsInfo['goodsName']}{$spec_attr_str}]库存不足");
                    }
                }
                //后加,校验总仓库存是否充足-end
                //如果采购方没有对应的sku,则添加
                if (!empty($skuInfo)) {
                    $localSkuWhere = array(
                        'goodsId' => $localGoodsInfo['goodsId'],
                        'skuBarcode' => $skuInfo['goodsId'],
                    );
                    $localSkuInfo = $localGoodsModule->getSkuSystemInfoParams($localSkuWhere);
                    if (empty($localSkuInfo)) {
                        foreach ($skuInfo['skuSpecAttr'] as $attrKey => $attrVal) {
                            $where = [];
                            $where['shopId'] = $shopInfo['shopId'];
                            $where['specName'] = $attrVal['specName'];
                            $where['dataFlag'] = 1;
                            $locationSpecInfo = $localSpecTab->where($where)->find();
                            if (empty($locationSpecInfo)) {
                                $locationSpecInfo = [];
                                $locationSpecInfo['specName'] = $attrVal['specName'];
                                $locationSpecInfo['shopId'] = $shopInfo['shopId'];
                                $locationSpecInfo['addTime'] = date('Y-m-d H:i:s');
                                $locationSpecInfo['specId'] = $localSpecTab->add($locationSpecInfo);
                            }
                            $where = [];
                            $where['specId'] = $locationSpecInfo['shopId'];
                            $where['attrName'] = $attrVal['attrName'];
                            $where['dataFlag'] = 1;
                            $locationAttrInfo = $localAttrTab->where($where)->find();
                            if (empty($locationAttrInfo)) {
                                $locationAttrInfo = [];
                                $locationAttrInfo['specId'] = $locationSpecInfo['specId'];
                                $locationAttrInfo['attrName'] = $attrVal['attrName'];
                                $locationAttrInfo['dataFlag'] = 1;
                                $locationAttrInfo['addTime'] = date('Y-m-d H:i:s');
                                $locationAttrInfo['attrId'] = $localAttrTab->add($locationAttrInfo);
                            }
                        }
                        $locationSystemInfo = [];
                        $locationSystemInfo['goodsId'] = $localGoodsInfo['goodsId'];
                        $locationSystemInfo['skuShopPrice'] = $skuInfo['retailPrice'];
                        $locationSystemInfo['skuMemberPrice'] = $skuInfo['retailPrice'];
                        //$locationSystemInfo['skuGoodsStock'] = $skuInfo['stock'];
                        $locationSystemInfo['skuGoodsStock'] = 0;
                        $locationSystemInfo['skuMarketPrice'] = $skuInfo['retailPrice'];
                        $locationSystemInfo['skuGoodsImg'] = $skuInfo['skuGoodsImg'];
                        $locationSystemInfo['skuBarcode'] = $skuInfo['skuBarcode'];
                        $locationSystemInfo['addTime'] = date('Y-m-d H:i:s');
                        $insertSkuId = $localSystemTab->add($locationSystemInfo);
                        if ($insertSkuId) {
                            foreach ($skuInfo['skuSpecAttr'] as $attrKey => $attrVal) {
                                $where = [];
                                $where['specName'] = $attrVal['specName'];
                                $where['shopId'] = $shopInfo['shopId'];
                                $where['dataFlag'] = 1;
                                $locationSpecInfo = $localSpecTab->where($where)->find();
                                $where = [];
                                $where['attrName'] = $attrVal['attrName'];
                                $where['specId'] = $locationSpecInfo['specId'];
                                $where['dataFlag'] = 1;
                                $locationAttrInfo = $localAttrTab->where($where)->find();
                                $selfInfo = [];
                                $selfInfo['skuId'] = $insertSkuId;
                                $selfInfo['specId'] = $locationSpecInfo['specId'];
                                $selfInfo['attrId'] = $locationAttrInfo['attrId'];
                                $localSelfTab->add($selfInfo);
                            }
                        }
                    }
                }
                //添加采购单明细
                $info = array();
                $info['otpId'] = $otpId;
                $info['goodsId'] = $goodsId;
                $info['skuId'] = $skuId;
                $info['remark'] = !empty($v['remark']) ? $v['remark'] : '';
                $info['unitPrice'] = $price;
                $info['totalNum'] = $v['totalNum'];
                $info['totalAmount'] = $v['totalNum'] * $price;
                $info['warehouseNum'] = $v['totalNum'];
//                $infoId = $this->addPurchaseOrderInfo($info);
                $infoId = $erpModule->savePurchaseOrderInfo($info, $dbTrans);
                if (!$infoId) {
//                        M()->rollback();
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '采购单明细添加失败');
                }
                $sumDataAmount[] = $price * $v['totalNum'];
                $sumTotalNum[] = $v['totalNum'];

                //采购单生成,扣除总仓商品库存
                if (!empty($erp_config['purchase_stock_check'])) {//开启了总仓库存校验
                    $deduction_stock_res = $erpModule->deductionErpStock($goodsId, $skuId, $v['totalNum'], $dbTrans);
                    if (!$deduction_stock_res) {//扣除商品库存
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '库存扣除失败');
                    }
                }
            }
            //判断是否是由订单生成的采购商品
            if (!empty($value['isOrder']) && $value['isOrder'] == 1) {
                foreach ($value['sku'] as $vv) {
                    $purchaseGoodsDetail = array();
                    $purchaseGoodsDetail['goodsId'] = $localGoodsInfo['goodsId'];
                    $purchaseGoodsDetail['totalNum'] = $vv['totalNum'];
                    $purchaseGoodsDetail['skuId'] = 0;
                    if ($vv['skuId'] > 0) {
                        $purchaseGoodsDetail['skuId'] = $localSkuInfo['skuId'];
                    }
                    $purchaseGoodsList[] = $purchaseGoodsDetail;
                }
            }
        }
        if (!empty($purchaseGoodsList)) {
            $purchaseModule = new PurchaseModule();
            $purchaseGoodsWhere = $params['purchaseGoodsWhere'];
            $purchaseGoodsWhere['shop_id'] = $shopInfo['shopId'];
            $purchaseGoodsWhere['onekeyPurchase'] = 1;
            $purchaseGoodsWhere['isNeedMerge'] = 0;
            foreach ($purchaseGoodsList as $purDetail) {
                $purchaseGoodsWhere['goods_id'] = $purDetail['goodsId'];
                $purchaseGoodsWhere['sku_id'] = $purDetail['skuId'];
                $purchaseList = $purchaseModule->getPurchaseGoodsList($purchaseGoodsWhere);
                foreach ($purchaseList as $item) {
                    $delRes = $purchaseModule->delPurchaseGoodsByParams($item['orderId'], $item['goodsId'], $item['skuId'], $dbTrans);
                    if (!$delRes) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '订单采购商品记录删除失败');
                    }
                }
            }
        }
        //更新采购单
        $update = array();
        $update['otpId'] = $otpId;
        $update['totalNum'] = array_sum($sumTotalNum);
        $update['dataAmount'] = array_sum($sumDataAmount);
        $update['actualAmount'] = $update['dataAmount'];
        $update['number'] = $number;
        $res = $erpModule->savePurchaseOrder($update, $dbTrans);
        if (empty($res)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '采购单更新失败');
        }

        //操作日志 start
        $logParams = array();
        $logParams['dataId'] = $otpId;
        $logParams['dataType'] = 1;
        $logParams['action'] = "采购方创建采购单#{$number}";
        $logParams['actionUserId'] = $actionUserId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        $logParams['status'] = 0;
        $logParams['warehouseStatus'] = 0;
        $logRes = $erpModule->addBillActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购单创建失败', '日志记录失败');
        }
        $dbTrans->commit();
        return returnData(true);
    }

    /**
     * 获取总仓商品sku详情
     * @param int $skuId
     * @param int $toShopId 门仓id(不传或传0为总仓,有值为被申请调拨的门店id)
     */
    public function getSkuDetail(int $skuId, $toShopId = 0)
    {
        if (empty($toShopId)) {
            //总仓
            $where = [];
            $where['skuId'] = $skuId;
            $where['dataFlag'] = 1;
            $where['examineStatus'] = 1;
            $skuGoodsSystem = M('jxc_sku_goods_system')->where($where)->find();
            if (empty($skuGoodsSystem)) {
                return [];
            }
            $infoWhere = [];
            $infoWhere['s.dataFlag'] = 1;
            $infoWhere['s.skuId'] = $skuId;
            $skuInfo = M('jxc_sku_goods_self s')
                ->join("left join wst_jxc_sku_spec a on s.specId=a.specId")
                ->join("left join wst_jxc_sku_spec_attr b on s.attrId=b.attrId")
                ->field("a.specId,a.specName,b.attrId,b.attrName,s.skuId")
                ->where($infoWhere)
                ->select();
            $spec_attr_arr = array_column($skuInfo, 'attrName');
            $spec_attr_str = implode(',', $spec_attr_arr);
            $skuGoodsSystem['skuSpecAttr'] = $skuInfo;
            $skuGoodsSystem['spec_attr_str'] = $spec_attr_str;
        } else {
            //门仓
            $where = [];
            $where['skuId'] = $skuId;
            $where['dataFlag'] = 1;
            $skuGoodsSystem = M('sku_goods_system')->where($where)->find();
            if (empty($skuGoodsSystem)) {
                return [];
            }
            $infoWhere = [];
            $infoWhere['s.dataFlag'] = 1;
            $infoWhere['s.skuId'] = $skuId;
            $skuInfo = M('sku_goods_self s')
                ->join("left join wst_sku_spec a on s.specId=a.specId")
                ->join("left join wst_sku_spec_attr b on s.attrId=b.attrId")
                ->field("a.specId,a.specName,b.attrId,b.attrName,s.skuId")
                ->where($infoWhere)
                ->select();
            $spec_attr_arr = array_column($skuInfo, 'attrName');
            $spec_attr_str = implode(',', $spec_attr_arr);
            $skuGoodsSystem['skuSpecAttr'] = $skuInfo;
            $skuGoodsSystem['spec_attr_str'] = $spec_attr_str;
        }
        return $skuGoodsSystem;
    }

    /**
     * 添加采购单详情
     * @param array $params [
     *                          int otpId:采购单id
     *                          int goodsId:商品id
     *                          int skuId:skuId
     *                          string unitPrice:采购单价
     *                          int totalNum:采购总数量
     *                          int totalAmount:采购总金额
     *                          string remark:备注
     *                      ]
     * @return bool $res
     */
    public function addPurchaseOrderInfo(array $params)
    {
        $id = M('jxc_purchase_order_info')->add($params);
        return (int)$id;
    }

    /**
     * 修改采购单
     * @param array $params [
     *                          int otpId:采购单id
     *                          string examineStatus:审核状态(0:待审核|1:已审核)
     *                          string warehouse:入库状态(0:未入库|1:部分入库|2:已入库)
     *                          string remark: 备注
     *                          string totalNum:采购总数量(字段类型和门店商品库存字段类型一致)
     *                          string dataAmount:单据金额
     *                          string actualAmount:实际金额
     *                      ]
     * @return bool $res
     */
    public function updatePurchaseOrder(array $params)
    {
        $model = M('jxc_purchase_order');
        $where['otpId'] = $params['otpId'];
        $res = $model->where($where)->save($params);
        if ($res !== false) {
            $res = true;
        }
        return (bool)$res;
    }

    /**
     * 创建订单号
     * @return string orderNo
     */
    public function addOrderno()
    {
        $params = [
            'rnd' => microtime(true)
        ];
        $id = M('orderids')->add($params);
        //$id = $model->id;
        return (int)$id;
    }

    /**
     * 更新采购单
     * @param array $params [
     *                          int otpId:采购单id
     *                          string remark:备注
     *                          array detail:采购商品详情
     *                      ]
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function updatePurchaseOrderDetail(array $params, array $shopInfo)
    {
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        M()->startTrans();
        $otpId = $params['otpId'];
        $where = [];
        $where['otpId'] = $otpId;
        $where['dataFlag'] = 1;
        $purchaseOrderInfo = M('jxc_purchase_order')->where($where)->find();
        if (!$purchaseOrderInfo) {
            return returnData(null, -1, 'error', '无效的采购单id');
        }
        if ($purchaseOrderInfo['status'] == 1) {
            //已经完成审核的采购单就不能编辑了
            return returnData(null, -1, 'error', '该采购单已被审核，不能进行修改');
        }
        if (!empty($params['detail'])) {
            //重置采购单详情
            M('jxc_purchase_order_info')->where(['otpId' => $otpId])->delete();
            $detail = $params['detail'];
            $sumTotalNum = [];//统计采购商品的总数量
            $sumDataAmount = [];//统计采购商品的金额
            foreach ($detail as $key => $value) {
                $goodsId = $value['goodsId'];
                $goodsInfo = $this->getGoodsDetail($goodsId);//商品详情
                if (empty($value['sku'])) {
                    //处理没有sku的情况
                    $handleSku = [];
                    $handleSku['skuId'] = 0;
                    $handleSku['remark'] = '';
                    if (empty($value['totalNum'])) {
                        return returnData(null, -1, 'error', '请输入商品[' . $goodsInfo['goodsName'] . ']正确的采购数量');
                    }
                    $handleSku['totalNum'] = $value['totalNum'];
                    if (!empty($value['remark'])) {
                        $handleSku['remark'] = $value['remark'];
                    }
                    $value['sku'][] = $handleSku;
                }
                $res = [];
                foreach ($value['sku'] as $skuVal) {
                    if (isset($res[$skuVal['skuId']])) {
                        unset($skuVal['skuId']);
                    } else {
                        $res[$skuVal['skuId']] = $skuVal;
                    }
                }
                //$res = array_values($res);
                $oldCount = count($value['sku']);
                $newCount = count($res);
                if ($newCount < $oldCount) {
                    return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId重复,请检查');
                }
                $price = $goodsInfo['sellPrice'];
                foreach ($value['sku'] as $v) {
                    $skuId = $v['skuId'];
                    if ($skuId > 0) {
                        $skuInfo = $this->getSkuDetail($skuId);
                        if (!empty($skuInfo['skuId']) && $skuInfo['goodsId'] == $goodsId) {
                            $price = $goodsInfo['sellPrice'];
                        } else {
                            return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId不匹配,错误的skuId' . ":" . $skuId);
                        }
                    }
                    if ($v['totalNum'] <= 0) {
                        return returnData(null, -1, 'error', '采购数量必须大于0');
                    }
                    //添加采购单明细
                    $info = [];
                    $info['otpId'] = $otpId;
                    $info['goodsId'] = $goodsId;
                    $info['skuId'] = $skuId;
                    $info['remark'] = !empty($v['remark']) ? $v['remark'] : '';
                    $info['unitPrice'] = $price;
                    $info['totalNum'] = $v['totalNum'];
                    $info['totalAmount'] = $v['totalNum'] * $price;
                    $info['warehouseNum'] = $v['totalNum'];
                    $infoId = $this->addPurchaseOrderInfo($info);
                    if (!$infoId) {
                        M()->rollback();
                        return false;
                    }
                    $sumDataAmount[] = $price * $v['totalNum'];
                    $sumTotalNum[] = $v['totalNum'];
                }
            }
            //更新采购单
            $update = [];
            $update['otpId'] = $otpId;
            $update['totalNum'] = array_sum($sumTotalNum);
            $update['dataAmount'] = array_sum($sumDataAmount);
            $update['actualAmount'] = $update['dataAmount'];
            $res = $this->updatePurchaseOrder($update);
            if (!$res) {
                M()->rollback();
                return false;
            }
            //操作日志 start
            $logParams = [];
            $logParams['dataId'] = $otpId;
            $logParams['dataType'] = 1;
            $logParams['action'] = "采购方修改了单据数据";
            $logParams['actionUserId'] = $actionUserId;
            $logParams['actionUserType'] = $shopInfo['login_type'];
            $logParams['actionUserName'] = $actionUserName;
            $this->addBillActionLog($logParams);
            //操作日志 end
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 获取采购单详情
     * @param int otpId 采购单id
     */
    public function getPurchaseOrderDetail(int $otpId)
    {
        $erp_module = new ErpModule();
        $where = [];
        $where['otpId'] = $otpId;
        $where['dataFlag'] = 1;
        $info = M('jxc_purchase_order')->where($where)->find();
        if (!$info) {
            return [];
        }
        $info['log'] = [];
        $info['goodsInfo'] = [];
        //采购单日志
        $where = [];
        $where['dataId'] = $otpId;
        $where['dataType'] = 1;
        $field = 'logId,action,actionUserName,createTime';
        $logList = M('jxc_bill_action_log')
            ->where($where)
            ->field($field)
            ->select();
        if (!empty($logList)) {
            $info['log'] = $logList;
        }
        $info['is_reinsurance'] = 0;//是否存子分单(0:不存在 1:存在)
        $info['reinsurance_list'] = $erp_module->getReinsuranceListByParentId($otpId);
        if (!empty($info['reinsurance_list'])) {
            $info['is_reinsurance'] = 1;
        }
        $detail = $this->getPurchaseOrderInfoListByOtpId($otpId, $info['billType']);
        if ($detail) {
            $shopId = 0;
            $goodsId = [];
            foreach ($detail as $key => $value) {
                if (!in_array($value['goodsId'], $goodsId)) {
                    $goodsId[] = $value['goodsId'];
                }
            }
            foreach ($detail as $key => $value) {
                $detail[$key]['skuInfo'] = [];
                if ($value['skuId'] > 0) {
                    if ($info['billType'] == 2) {
                        $shopId = $info['shopId'];
                    }
                    $skuInfo = $this->getSkuDetail($value['skuId'], $shopId);
                    $detail[$key]['skuInfo'] = $skuInfo;
                }
            }
            $goodsList = [];
            foreach ($goodsId as $value) {
                foreach ($detail as $val) {
                    if ($value == $val['goodsId']) {
                        $goodsInfo['goodsName'] = $val['goodsName'];
                        $goodsInfo['goodsSn'] = $val['goodsSn'];
                        $goodsInfo['goodsId'] = $val['goodsId'];
                        $goodsInfo['goodsImg'] = $val['goodsImg'];
//                        $goodsInfo['retailPrice'] = $val['retailPrice'];
//                        $goodsInfo['sellPrice'] = $val['sellPrice'];
//                        $goodsInfo['buyPirce'] = $val['buyPirce'];
                        //为了避免前端金额字段调用错误,后端将所有的金额额都替换成总仓的销货价,即门仓的采购价-start
                        $goodsInfo['retailPrice'] = $val['sellPrice'];
                        $goodsInfo['sellPrice'] = $val['sellPrice'];
                        $goodsInfo['buyPirce'] = $val['sellPrice'];
                        if ($info['billType'] == 2) {
                            $goodsInfo['buyPirce'] = $val['unitPrice'];
                        }
                        $goodsInfo['totalBuyPrice'] = sprintfNumber($goodsInfo['buyPirce'] * $val['totalNum']);
                        //为了避免前端金额字段调用错误,后端将所有的金额额都替换成总仓的销货价,即门仓的采购价-end
                        $goodsInfo['goodsCatId1'] = $val['goodsCatId1'];
                        $goodsInfo['goodsCatId2'] = $val['goodsCatId2'];
                        $goodsInfo['goodsCatId3'] = $val['goodsCatId3'];
                        $goodsInfo['sku'] = [];
                        $goodsList[] = $goodsInfo;
                    }
                }
            }
            $uniqueGoods = [];
            foreach ($goodsList as $value) {
                if (isset($uniqueGoods[$value['goodsId']])) {
                    unset($value['goodsId']);
                } else {
                    $uniqueGoods[$value['goodsId']] = $value;
                }
            }
            $uniqueGoods = array_values($uniqueGoods);
            foreach ($uniqueGoods as $key => $value) {
                foreach ($detail as $val) {
                    if ($value['goodsId'] == $val['goodsId']) {
                        if (empty($val['skuInfo'])) {
                            $uniqueGoods[$key]['remark'] = $val['remark'];
                            $uniqueGoods[$key]['unitPrice'] = $val['unitPrice'];
                            $uniqueGoods[$key]['totalNum'] = $val['totalNum'];
                            $uniqueGoods[$key]['totalAmount'] = $val['totalAmount'];
                            $uniqueGoods[$key]['warehouseNum'] = $val['warehouseNum'];
                            $uniqueGoods[$key]['warehouseCompleteNum'] = $val['warehouseCompleteNum'];
                            $uniqueGoods[$key]['warehouseNoNum'] = $val['warehouseNum'] - $val['warehouseCompleteNum'];
                            $uniqueGoods[$key]['supplier_name'] = '';
                            if ($info['takenSupplier'] == 1) {
                                $uniqueGoods[$key]['supplier_name'] = '总仓';
                            }
                            if ($info['takenSupplier'] == 2) {
                                $supplier_detail = $erp_module->getSupplierDetailById($val['toSupplierId'], 'supplierId,name');
                                $uniqueGoods[$key]['supplier_name'] = (string)$supplier_detail['name'];
                            }
                        } else {
                            $val['skuInfo']['remark'] = $val['remark'];
                            $val['skuInfo']['unitPrice'] = $val['unitPrice'];
                            //前端调用的是buyPrice,应该调用unitPrice,这里后端直接把字段重置为采购单价-start 勿动
                            $val['skuInfo']['buyPirce'] = $val['unitPrice'];
                            $val['skuInfo']['totalBuyPrice'] = sprintfNumber($val['unitPrice'] * $val['totalNum']);
                            $val['skuInfo']['sellPrice'] = $val['unitPrice'];
                            $val['skuInfo']['retailPrice'] = $val['unitPrice'];
                            //前端调用的是buyPrice,应该调用unitPrice,这里后端直接把字段重置为采购单价-end 勿动
                            $val['skuInfo']['totalNum'] = $val['totalNum'];
                            $val['skuInfo']['totalAmount'] = $val['totalAmount'];
                            $val['skuInfo']['warehouseNum'] = $val['warehouseNum'];
                            $val['skuInfo']['warehouseCompleteNum'] = $val['warehouseCompleteNum'];
                            $val['skuInfo']['warehouseNoNum'] = $val['warehouseNum'] - $val['warehouseCompleteNum'];
                            $val['skuInfo']['skuSpecAttrStr'] = '';
                            foreach ($val['skuInfo']['skuSpecAttr'] as $skuVal) {
                                $val['skuInfo']['skuSpecAttrStr'] .= $skuVal['attrName'] . '，';
                            }
                            $val['skuInfo']['skuSpecAttrStr'] = rtrim($val['skuInfo']['skuSpecAttrStr'], '，');
                            $val['skuInfo']['supplier_name'] = '';
                            if ($info['takenSupplier'] == 1) {
                                $val['skuInfo']['supplier_name'] = '总仓';//供应商名称
                            }
                            if ($info['takenSupplier'] == 2) {
                                $supplier_detail = $erp_module->getSupplierDetailById($val['toSupplierId'], 'supplierId,name');
                                $val['skuInfo']['supplier_name'] = (string)$supplier_detail['name'];
                            }
                            $uniqueGoods[$key]['sku'][] = $val['skuInfo'];
                        }
                    }
                }
            }
            $info['goodsInfo'] = $uniqueGoods;
        }
        return (array)$info;
    }

    /**
     * 根据单据id获取单据对应的时间线
     * @param int $dataId 单据id
     * @param int $type 数据类型(1:采购单|2:调拨单)
     * @return array $data
     */
    public function getExamineLogByDataId(int $dataId, $type = 1)
    {
        $where = "dataId='" . $dataId . "' and dataType=$type ";
        $data = M('jxc_examine_log')
            ->where($where)
            ->field('logId,action,actionUserName,remark,createTime')
            ->order('logId', 'asc')
            ->select();
        if (empty($data)) {
            return [];
        }
        return (array)$data;
    }

    /**
     * 根据采购单id获取采购单详情
     * @param int $otpId 采购单id
     * @param int $billType 单据类型(1:采购单转为入库单|2:手动创建入库单)
     * @return array $data
     */
    public function getPurchaseOrderInfoListByOtpId(int $otpId, int $billType)
    {
        $where = "i.otpId='" . $otpId . "'";
        if ($billType == 1) {
            $fieldPurchase = 'i.*,g.goodsName,g.goodsImg,g.retailPrice,g.sellPrice,g.buyPirce,g.stock,g.goodsSn,g.goodsCat1 as goodsCatId1,g.goodsCat2 as goodsCatId2,g.goodsCat3 as goodsCatId3';
            $data = M('jxc_purchase_order_info i')
                ->join("left join wst_jxc_goods g on g.goodsId=i.goodsId")
                ->where($where)
                ->field($fieldPurchase)
                ->select();
        } else {
            $field = 'i.*,g.goodsName,g.goodsImg,g.shopPrice as retailPrice,g.shopPrice as sellPrice,g.shopPrice as buyPirce,g.goodsStock,g.goodsSn';
            $data = M('jxc_purchase_order_info i')
                ->join("left join wst_goods g on g.goodsId=i.goodsId")
                ->where($where)
                ->field($field)
                ->select();
        }
        if (empty($data)) {
            return [];
        }
        return (array)$data;
    }

    /**
     * 获取采购单列表
     * @param array $params [
     *                          int shopId:门店id
     *                          string number:单号
     *                          string actionUserName:制单人
     *                          int status:审核状态(-1:平台已拒绝|0:平台待审核|1:平台已审核)
     *                          int receivingStatus:收货状态(0:待收货|1:已收货)
     *                          string startDate:开始时间
     *                          string endDate:结束时间
     *                          int page:页码,默认为1
     *                          int pageSize:分页条数,默认为15
     *                  ]
     * @return array $data
     */
    public function getPurchaseOrderList(array $params)
    {
        $page = $params['page'];
        $shopId = $params['shopId'];
        $pageSize = $params['pageSize'];
        $where = " p.dataFlag=1 and p.dataFrom IN(1,2) and p.shopId={$shopId}";
        if (is_numeric($params['receivingStatus'])) {
            $where .= " and p.status=1 and p.receivingStatus={$params['receivingStatus']}";
        }
        $whereFind['p.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['p.actionUserName'] = function () use ($params) {
            if (empty($params['actionUserName'])) {
                return null;
            }
            return ['like', "%{$params['actionUserName']}%", 'and'];
        };
        $whereFind['p.status'] = function () use ($params) {
            if (!is_numeric($params['status'])) {
                return null;
            }
            return ['=', "{$params['status']}", 'and'];
        };
        $whereFind['p.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "p.otpId,p.number,p.shopId,p.actionUserId,p.actionUserName,p.shopName,p.dataFrom,p.billType,p.supplyType,p.status,p.warehouseStatus,p.warehouseUserId,p.receivingStatus,p.remark,p.createTime,p.totalNum,p.dataAmount,p.actualAmount,p.billPic,p.consigneeName,p.takenSupplier";
        $sql = "select {$field} from __PREFIX__jxc_purchase_order p left join wst_shops s on p.shopId=s.shopId where {$whereInfo} order by p.otpId desc ";
        if ($params['export'] != 1) {
            $rs = $this->pageQuery($sql, $page, $pageSize);
            $list = $rs['root'];
        } else {
            $rs = $this->query($sql);
            $list = $rs;
        }
        //增加分单信息-start
        $erp_module = new ErpModule();
        foreach ($list as $key => &$item) {
            $otpId = $item['otpId'];
            $field = 'rpId,number,supplierId,totalNum,dataAmount,actualAmount,status,receivingStatus,billPic,consigneeName';
            $reinsurance_list = $erp_module->getReinsuranceListByParentId($otpId, $field);
            $item['is_reinsurance'] = 0;//是否存在分单数据(0:否 1:是)
            $item['reinsurance_list'] = array();
            if (!empty($reinsurance_list)) {
                $item['is_reinsurance'] = 1;
                $item['reinsurance_list'] = $reinsurance_list;
            }
        }
        unset($item);
        //增加分单信息-end
        if ($params['export'] != 1) {
            $rs['root'] = $list;
        } else {
            $this->handlePurchaseOrderList($list, $params);
        }
        return (array)$rs;
    }

    /**
     * 处理导出的数据格式
     * @param array $list PS:需要导出的采购单数据
     * @param array $params 前端传过来的一些参数
     * */
    public function handlePurchaseOrderList(array $list, array $params)
    {
        if (empty($list)) {
            $this->exportPurchaseData($list, $params);
        }
        $detail = [];
        foreach ($list as $key => $val) {
            $purInfoList = $this->getPurchaseOrderInfoListByOtpId($val['otpId'], $val['billType']);
            if (empty($purInfoList)) {
                continue;
            }
            foreach ($purInfoList as $infoKey => $infoVal) {
                $detail[] = $infoVal;
            }
        }
        foreach ($list as $key => $value) {
            $rowspan = 0;//后面的导出会用到
            $list[$key]['goods'] = [];
            foreach ($detail as $detailVal) {
                if ($detailVal['otpId'] == $value['otpId']) {
                    $list[$key]['goods'][] = $detailVal;
                }
            }
            $goods = $list[$key]['goods'];//该单下所包含的商品
            $skuList = [];//商品所包含的sku信息
            foreach ($goods as $gkey => $gval) {
                $goods[$gkey]['skuInfo'] = [];//sku详细信息
                if (!empty($gval['skuId'])) {
                    if ($value['billType'] == 1) {
                        $shopId = 0;
                    } else {
                        $shopId = $value['shopId'];
                    }
                    $skuInfo = $this->getSkuDetail($gval['skuId'], $shopId);
                    $skuInfo['goodsAttrName'] = '';
                    if (!empty($skuInfo['skuSpecAttr'])) {
                        $specAttrNameArr = [];
                        foreach ($skuInfo['skuSpecAttr'] as $skuVal) {
                            $specAttrNameArr[] = $skuVal['attrName'];
                        }
                        $skuInfo['goodsAttrName'] = implode('，', $specAttrNameArr);
                    }
                    if (!empty($skuInfo)) {
                        if ($skuInfo['skuId'] == $gval['skuId']) {
                            $skuInfo['unitPrice'] = $gval['unitPrice'];
                        }
                        $goods[$gkey]['skuInfo'] = $skuInfo;
                        $skuList[] = $skuInfo;
                    }
                }
            }
            unset($gval);
            $uniqueGoods = $this->uniqueData($goods, 'goodsId');
            foreach ($uniqueGoods as $uniqueKey => $uniqueVal) {
                unset($uniqueGoods[$uniqueKey]['infoId']);
                unset($uniqueGoods[$uniqueKey]['skuInfo']);
                $goodsNums = 0;//统计商品数量
                $goodsPrcie = 0;//统计商品价格
                //$uniqueGoods[$uniqueKey]['goodsNums'] = 0;//统计商品数量
                //$uniqueGoods[$uniqueKey]['goodsPrice'] = 0;//统计商品价格
                $uniqueGoods[$uniqueKey]['skulist'] = [];
                foreach ($goods as $gval) {
                    if ($gval['goodsId'] == $uniqueVal['goodsId']) {
                        $goodsNums += $gval['totalNum'];
                        $goodsPrcie += $gval['unitPrice'];
                        if (empty($gval['skuId'])) {
                            $rowspan += 1;
                            //无sku
                        } else {
                            //有sku
                            $uniqueGoods[$uniqueKey]['skulist'][] = $gval['skuInfo'];
                            $rowspan += 1;
                        }
                    }
                }
            }
            $list[$key]['goods'] = $uniqueGoods;
            $list[$key]['rowspan'] = $rowspan;
        }
        $this->exportPurchaseData($list, $params);//导出需要采购的商品
    }

    /**
     * 处理数据去重
     * @param array $data 需要处理的重复数据
     * @param string $key 指定键名
     * */
    public function uniqueData(array $data, $key)
    {
        $res = [];
        foreach ($data as $value) {
            if (isset($res[$value[$key]])) {
                unset($value[$key]);
            } else {
                $res[$value[$key]] = $value;
            }
        }
        return array_values($res);
    }

    /**
     * 导出需要采购单数据
     * @param array $goods PS:需要导出的采购单数据
     * @param array $params PS:前端传过来的一些参数
     * */
    public function exportPurchaseData(array $list, array $params)
    {
        $param['startDate'] = $params['startDate'];
        $param['endDate'] = $params['endDate'];
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $nowDate = date('Y-m-d H:i:s');
        if (empty($startDate) || empty($endDate)) {
            //$startDate = $nowDate.'00:00:00';
            //$endDate = $nowDate.'23:59:59';
            $startDate = $nowDate;
            $endDate = $nowDate;
        }
        $date = $startDate . ' - ' . $endDate;
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:100px;'>单号</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <!--<th style='width:50px;'>小计</th>-->
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>单据时间</th>
            </tr>";
        $num = 0;
        foreach ($list as $okey => $ovalue) {
            $rowspan = $ovalue['rowspan'];
            $orderGoods = $ovalue['goods'];
            $key = $okey + 1;
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                $countOrderGoods = count($orderGoods);
                if ($countOrderGoods > $rowspan) {
                    $rowspan = $countOrderGoods;
                }
                $num++;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }
                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['number'] . "</td>" .//单号
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['remark'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//单据时间
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['goodsAttrName'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['number'] . "</td>" .//单号
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['remark'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['unitPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                                    //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:50px;' >" . $skuVal['unitPrice'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                                //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                                "</tr>";
                        }
                    }
                }
            }
        }
        $headTitle = "采购单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 入库任务->获取采购单列表
     * @param array $params [
     *                          int shopId:门店id
     *                          string number:单号
     *                          string actionName:制单人
     *                          int examineStatus:审核状态(0:待审核|1:已审核|'':全部)
     *                          int warehouse:入库状态(0:未入库|1:部分入库|2:已入库|'':全部)
     *                          string startDate:开始时间
     *                          string endDate:结束时间
     *                          int page:页码,默认为1
     *                          int pageSize:分页条数,默认为15
     *                  ]
     * @return array $data
     */
    public function getWarehousingPurchaseOrderList(array $params)
    {
        $shopId = $params['shopId'];
        $where = ' p.dataFlag=1 and p.dataFrom=2 and p.action="' . $shopId . '"';
        $whereFind['p.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['p.examineStatus'] = $params['examineStatus'];
        $whereFind['p.warehouse'] = $params['warehouse'];
        $whereFind['p.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select p.*,s.shopName as actionName from __PREFIX__jxc_purchase_order p left join wst_shops s on p.action=s.shopId where $whereInfo order by p.otpId desc ";
        $rs = $this->query($sql);
        //过滤掉已经分配的采购单数据
//        $warehouseInfoTab = M('in_warehouse_info info');
//        foreach ($rs as $key=>$val){
//            $where = [];
//            $where['ware.iwFlag'] = 1;
//            $where['info.dataId'] = $val;
//            $count = $warehouseInfoTab
//                ->join("left join wst_in_warehouse ware on ware.iwid=info.iwid ")
//                ->where($where)
//                ->count();
//            if($count > 0){
//                unset($rs[$key]);
//            }
//        }
//        $rs = array_values($rs);
        return $rs;
    }

    /**
     * 删除采购单
     * @param int $otpId 采购单id
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function delPurchaseOrder(int $otpId)
    {
        $response_data = returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败');
        $erp_module = new ErpModule();
        $detail = $erp_module->getPurchaseOrderDetailById($otpId, 'otpId,number,status');
        if (empty($detail)) {
            $response_data['msg'] = '删除失败';
            return $response_data;
        }
        if ($detail['status'] != 0) {
            $response_data['msg'] = "采购单#{$detail['number']}已审核，不能删除";
            return $response_data;
        }
        $m = new Model();
        $m->startTrans();
        $save = array(
            'otpId' => $otpId,
            'dataFlag' => -1
        );
        $save_res = $erp_module->savePurchaseOrder($save, $m);
        if ($save_res <= 0) {
            $m->rollback();
            $response_data['msg'] = "采购单#{$detail['number']}删除失败";
            return $response_data;
        } else {
            $response_data['data'] = true;
            $response_data['status'] = 'success';
        }
        //删除成功返还总仓库存
        $info_list = $detail['info_list'];
        foreach ($info_list as $info_detail) {
            $goods_name = (string)$info_detail['goodsName'];
            $goods_id = (int)$info_detail['goodsId'];
            $sku_id = (int)$info_detail['skuId'];
            $num = (float)$info_detail['totalNum'];
            $stock_res = $erp_module->returnErpStock($goods_id, $sku_id, $num, $m);
            if (!$stock_res) {
                $m->rollback();
                $response_data['msg'] = "商品#{$goods_name}返还总仓库存失败";
                return $response_data;
            }
        }
        $response_data['code'] = ExceptionCodeEnum::SUCCESS;
        $response_data['msg'] = '删除成功';
        $m->commit();
        return $response_data;
    }

    /**
     * 添加调拨单
     * @param array $params [
     *                          int shopId:门仓id
     *                          int toShopId:被申请的门仓id
     *                          int :门店id
     *                          string remark:备注
     *                          array detail:采购商品详情
     *                      ]
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function addAllocationOrder(array $params, array $shopInfo)
    {
        if ($params['toShopId'] == $shopInfo['shopId']) {
            $apiRet = returnData(null, -1, 'error', '调拨对象不能和当前门店一样');
            return $apiRet;
        }
        M()->startTrans();
        $orderno = $this->addOrderno();//生成订单号
        // 启动事务
        $order = [];
        $order['number'] = "DBD" . $orderno;
        $order['shopId'] = $shopInfo['shopId'];
        $order['toShopId'] = $params['toShopId'];
        $order['action'] = $shopInfo['shopId'];
        $order['dataFrom'] = 2;
        $order['remark'] = $params['remark'];
        $order['createTime'] = date('Y-m-d H:i:s');
        $otpId = M('jxc_allocation_order')->add($order);//添加调拨单主表信息
        //$otpId = 1;
        if ($otpId > 0) {
            //调拨明细
            if (!empty($params['detail'])) {
                $detail = $params['detail'];
                $sumTotalNum = [];//统计采购商品的总数量
                $sumDataAmount = [];//统计采购商品的金额
                foreach ($detail as $key => $value) {
                    $goodsId = $value['goodsId'];
                    $goodsInfo = $this->getGoodsDetail($goodsId, $params['toShopId']);//商品详情
                    if (empty($value['sku'])) {
                        //处理没有sku的情况
                        $handleSku = [];
                        $handleSku['skuId'] = 0;
                        $handleSku['remark'] = '';
                        if (empty($value['totalNum'])) {
                            return returnData(null, -1, 'error', '请输入商品[' . $goodsInfo['goodsName'] . ']正确的采购数量');
                        }
                        $handleSku['totalNum'] = $value['totalNum'];
                        if (!empty($value['remark'])) {
                            $handleSku['remark'] = $value['remark'];
                        }
                        $value['sku'][] = $handleSku;
                    }
                    $res = [];
                    foreach ($value['sku'] as $skuVal) {
                        if (isset($res[$skuVal['skuId']])) {
                            unset($skuVal['skuId']);
                        } else {
                            $res[$skuVal['skuId']] = $skuVal;
                        }
                    }
                    //$res = array_values($res);
                    $oldCount = count($value['sku']);
                    $newCount = count($res);
                    if ($newCount < $oldCount) {
                        return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId重复,请检查');
                    }
                    $price = $goodsInfo['buyPirce'];
                    if ($params['toShopId'] > 0) {
                        $price = $goodsInfo['shopPrice'];
                    }
                    foreach ($value['sku'] as $v) {
                        $skuId = $v['skuId'];
                        if ($skuId > 0) {
                            $skuInfo = $this->getSkuDetail($skuId, $params['toShopId']);
                            if (!empty($skuInfo['skuId']) && $skuInfo['goodsId'] == $goodsId) {
                                $price = $skuInfo['buyPirce'];
                                if ($params['toShopId'] > 0) {
                                    if ($skuInfo['skuShopPrice'] != -1) {
                                        $price = $skuInfo['skuShopPrice'];
                                    }
                                }
                            } else {
                                return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId不匹配,错误的skuId' . ":" . $skuId);
                            }
                        }
                        if ($v['totalNum'] <= 0) {
                            return returnData(null, -1, 'error', '调拨数量必须大于0');
                        }
                        //添加调拨单明细
                        $info = [];
                        $info['allId'] = $otpId;
                        $info['goodsId'] = $goodsId;
                        $info['skuId'] = $skuId;
                        $info['remark'] = !empty($v['remark']) ? $v['remark'] : '';
                        $info['unitPrice'] = $price;
                        $info['totalNum'] = $v['totalNum'];
                        $info['totalAmount'] = $v['totalNum'] * $price;
                        $infoId = M('jxc_allocation_info')->add($info);
                        if (!$infoId) {
                            M()->rollback();
                            return false;
                        }
                        $sumDataAmount[] = $price * $v['totalNum'];
                        $sumTotalNum[] = $v['totalNum'];
                    }
                }
                //更新调拨单
                $update = [];
                $update['allId'] = $otpId;
                $update['totalNum'] = array_sum($sumTotalNum);
                $update['dataAmount'] = array_sum($sumDataAmount);
                $res = $this->updateAllocationOrder($update);
                if (!$res) {
                    M()->rollback();
                    return false;
                }
            }
            //后加时间线
            $examineLogInfo = [];
            $examineLogInfo['dataId'] = $otpId;
            $examineLogInfo['dataType'] = 2;
            $examineLogInfo['action'] = "创建调拨单";
            $examineLogInfo['actionUserId'] = $shopInfo['shopId'];
            $examineLogInfo['actionUserType'] = 2;
            $examineLogInfo['actionUserName'] = $shopInfo['shopName'];
            $examineLogInfo['remark'] = '';
            $examineLogInfo['createTime'] = date('Y-m-d H:i:s');
            $examineLog[] = $examineLogInfo;
            M('jxc_examine_log')->addAll($examineLog);
        }
        M()->commit();
        return returnData();
    }

    /**
     * 修改调拨单
     * @param array $params [
     *                          int allId:调拨单id
     *                          string examineStatus:状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成)
     *                          string warehouse:入库状态(0:未入库|1:部分入库|2:已入库)
     *                          string remark: 调拨单备注
     *                          string refusalRemark: 拒绝原因
     *                          string totalNum:调拨总数量(字段类型和门店商品库存字段类型一致)
     *                          string dataAmount:单据金额
     *                          string actualAmount:实际金额
     *                      ]
     * @return bool $res
     */
    public function updateAllocationOrder(array $params)
    {
        $model = M('jxc_allocation_order');
        $where['allId'] = $params['allId'];
        $res = $model->where($where)->save($params);
        if ($res !== false) {
            $res = true;
        }
        return (bool)$res;
    }

    /**
     * 更新调拨单
     * @param array $params [
     *                          int allId:调拨单id
     *                          int shopId:门仓id
     *                          int toShopId:被申请的门仓id
     *                          string remark:备注
     *                          array detail:调拨商品详情
     *                      ]
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function updateAllocationOrderDetail(array $params, array $shopInfo)
    {
        M()->startTrans();
        $otpId = $params['allId'];
        $where = [];
        $where['allId'] = $otpId;
        $where['dataFlag'] = 1;
        $purchaseOrderInfo = M('jxc_allocation_order')->where($where)->find();
        if (!$purchaseOrderInfo) {
            return returnData(null, -1, 'error', '无效的调拨单id');
        }
        if (!empty($params['detail'])) {
            //重置采购单详情
            M('jxc_allocation_info')->where(['allId' => $otpId])->delete();
            $detail = $params['detail'];
            $sumTotalNum = [];//统计采购商品的总数量
            $sumDataAmount = [];//统计采购商品的金额
            foreach ($detail as $key => $value) {
                $goodsId = $value['goodsId'];
                $goodsInfo = $this->getGoodsDetail($goodsId, $params['toShopId']);//商品详情
                if (empty($value['sku'])) {
                    //处理没有sku的情况
                    $handleSku = [];
                    $handleSku['skuId'] = 0;
                    $handleSku['remark'] = '';
                    if (empty($value['totalNum'])) {
                        return returnData(null, -1, 'error', '请输入商品[' . $goodsInfo['goodsName'] . ']正确的调拨数量');
                    }
                    $handleSku['totalNum'] = $value['totalNum'];
                    if (!empty($value['remark'])) {
                        $handleSku['remark'] = $value['remark'];
                    }
                    $value['sku'][] = $handleSku;
                }
                $res = [];
                foreach ($value['sku'] as $skuVal) {
                    if (isset($res[$skuVal['skuId']])) {
                        unset($skuVal['skuId']);
                    } else {
                        $res[$skuVal['skuId']] = $skuVal;
                    }
                }
                //$res = array_values($res);
                $oldCount = count($value['sku']);
                $newCount = count($res);
                if ($newCount < $oldCount) {
                    return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId重复,请检查');
                }
                $price = $goodsInfo['buyPirce'];
                if ($params['toShopId'] > 0) {
                    $price = $goodsInfo['shopPrice'];
                }
                foreach ($value['sku'] as $v) {
                    $skuId = $v['skuId'];
                    if ($skuId > 0) {
                        $skuInfo = $this->getSkuDetail($skuId, $params['toShopId']);
                        if (!empty($skuInfo['skuId']) && $skuInfo['goodsId'] == $goodsId) {
                            $price = $goodsInfo['buyPirce'];
                            if ($params['toShopId'] > 0) {
                                $price = $skuInfo['skuShopPrice'];
                            }
                        } else {
                            return returnData(null, -1, 'error', '商品[' . $goodsInfo['goodsName'] . ']的skuId不匹配,错误的skuId' . ":" . $skuId);
                        }
                    }
                    if ($v['totalNum'] <= 0) {
                        return returnData(null, -1, 'error', '采购数量必须大于0');
                    }
                    //添加调拨单明细
                    $info = [];
                    $info['allId'] = $otpId;
                    $info['goodsId'] = $goodsId;
                    $info['skuId'] = $skuId;
                    $info['remark'] = !empty($v['remark']) ? $v['remark'] : '';
                    $info['unitPrice'] = $price;
                    $info['totalNum'] = $v['totalNum'];
                    $info['totalAmount'] = $v['totalNum'] * $price;
                    $infoId = M('jxc_allocation_info')->add($info);
                    if (!$infoId) {
                        M()->rollback();
                        return false;
                    }
                    $sumDataAmount[] = $price * $v['totalNum'];
                    $sumTotalNum[] = $v['totalNum'];
                }
            }
            //更新调拨单
            $update = [];
            $update['allId'] = $otpId;
            $update['totalNum'] = array_sum($sumTotalNum);
            $update['dataAmount'] = array_sum($sumDataAmount);
            $res = $this->updateAllocationOrder($update);
            if (!$res) {
                M()->rollback();
                return false;
            }
        }
        M()->commit();
        return returnData();
    }

    /**
     * 获取调拨单详情
     * @param int $allId 调拨单id
     * @param array $shopInfo 门仓信息
     */
    public function getAllocationOrderDetail(int $allId, array $shopInfo)
    {
        $where = [];
        $where['allId'] = $allId;
        $where['dataFlag'] = 1;
        $where['dataFrom'] = 2;//目前只有门店调拨
        $info = M('jxc_allocation_order')->where($where)->find();
        if (!empty($info)) {
            $info['goodsInfo'] = [];//商品明细
            $info['timeLine'] = [];//操作记录
            $info['actionName'] = '';
            $info['shopName'] = '';
            $info['toShopName'] = '';
            if ($info['toShopId'] == 0) {
                $info['toShopName'] = '总仓';
            }
            $shopWhere = [];
            $shopWhere['shopFlag'] = 1;
            $shopWhere['shopId'] = $info['shopId'];
            $shopInfo = M('shops')->where($shopWhere)->find();//申请调拨的门店信息
            if ($shopInfo) {
                $info['actionName'] = $shopInfo['shopName'];
                $info['shopName'] = $shopInfo['shopName'];
            }
            $toShopWhere = [];
            $toShopWhere['shopFlag'] = 1;
            $toShopWhere['shopId'] = $info['toShopId'];
            $toShopInfo = M('shops')->where($toShopWhere)->find();//被申请调拨的门店信息
            if ($toShopInfo) {
                $info['toShopName'] = $toShopInfo['shopName'];
            }
            $timeLine = $this->getExamineLogByDataId($allId, 2);
            if ($timeLine) {
                $info['timeLine'] = $timeLine;
            }
            $detail = $this->getAllocationOrderInfoListByAllId($allId, $info['toShopId']);
            if ($detail) {
                $goodsId = [];
                foreach ($detail as $key => $value) {
                    if (!in_array($value['goodsId'], $goodsId)) {
                        $goodsId[] = $value['goodsId'];
                    }
                }
                foreach ($detail as $key => $value) {
                    $detail[$key]['skuInfo'] = [];
                    if ($value['skuId'] > 0) {
                        $skuInfo = $this->getSkuDetail($value['skuId'], $info['toShopId']);
                        $detail[$key]['skuInfo'] = $skuInfo;
                    }
                }
                $goodsList = [];
                foreach ($goodsId as $value) {
                    foreach ($detail as $val) {
                        if ($value == $val['goodsId']) {
                            $goodsInfo['goodsName'] = $val['goodsName'];
                            $goodsInfo['goodsId'] = $val['goodsId'];
                            $goodsInfo['goodsImg'] = $val['goodsImg'];
                            if ($info['toShopId'] > 0) {
                                $goodsInfo['retailPrice'] = $val['shopPrice'];
                                $goodsInfo['sellPrice'] = $val['shopPrice'];
                                $goodsInfo['buyPirce'] = $val['shopPrice'];
                                $goodsInfo['shopPrice'] = $val['shopPrice'];
                                $goodsInfo['memberPrice'] = $val['memberPrice'];
                                $goodsInfo['marketPrice'] = $val['marketPrice'];
                                $goodsInfo['goodsStock'] = $val['goodsStock'];
                            } else {
                                $goodsInfo['retailPrice'] = $val['retailPrice'];
                                $goodsInfo['sellPrice'] = $val['sellPrice'];
                                $goodsInfo['buyPirce'] = $val['buyPirce'];
                            }
                            $goodsInfo['sku'] = [];
                            $goodsList[] = $goodsInfo;
                        }
                    }
                }
                $uniqueGoods = [];
                foreach ($goodsList as $value) {
                    if (isset($uniqueGoods[$value['goodsId']])) {
                        unset($value['goodsId']);
                    } else {
                        $uniqueGoods[$value['goodsId']] = $value;
                    }
                }
                $uniqueGoods = array_values($uniqueGoods);
                foreach ($uniqueGoods as $key => $value) {
                    foreach ($detail as $val) {
                        if ($value['goodsId'] == $val['goodsId'] && !empty($val['skuInfo'])) {
                            $val['skuInfo']['remark'] = $val['remark'];
                            $val['skuInfo']['warehouse'] = $val['warehouse'];
                            $val['skuInfo']['unitPrice'] = $val['unitPrice'];
                            $val['skuInfo']['totalNum'] = $val['totalNum'];
                            $val['skuInfo']['totalAmount'] = $val['totalAmount'];
                            $val['skuInfo']['num'] = $val['num'];
                            if ($info['toShopId'] > 0) {//暂时先放着,erp商品表和门店表价格字段不统一
                                if ($val['skuInfo']['skuShopPrice'] == -1) {
                                    $val['skuInfo']['skuShopPrice'] = $value['shopPrice'];
                                }
                                if ($val['skuInfo']['skuMemberPrice'] == -1) {
                                    $val['skuInfo']['skuMemberPrice'] = $value['memberPrice'];
                                }
                                if ($val['skuInfo']['skuGoodsStock'] == -1) {
                                    $val['skuInfo']['skuGoodsStock'] = $value['goodsStock'];
                                }
                                if ($val['skuInfo']['skuMarketPrice'] == -1) {
                                    $val['skuInfo']['skuMarketPrice'] = $value['marketPrice'];
                                }
                            }
                            $uniqueGoods[$key]['sku'][] = $val['skuInfo'];
                        } else {
                            $uniqueGoods[$key]['remark'] = $val['remark'];
                            $uniqueGoods[$key]['warehouse'] = $val['warehouse'];
                            $uniqueGoods[$key]['unitPrice'] = $val['unitPrice'];
                            $uniqueGoods[$key]['totalNum'] = $val['totalNum'];
                            $uniqueGoods[$key]['totalAmount'] = $val['totalAmount'];
                            $uniqueGoods[$key]['num'] = $val['num'];
                        }
                    }
                }
                $info['goodsInfo'] = $uniqueGoods;
            }
        }
        return (array)$info;
    }

    /**
     * 根据调拨单id获取调拨单详情
     * @param int $allId 调拨单id
     * @param int $toShopId 被申请调拨的门店,为0则为总仓
     * @return array $data
     */
    public function getAllocationOrderInfoListByAllId(int $allId, int $toShopId)
    {
        $where = "i.allId='" . $allId . "'";
        if ($toShopId <= 0) {
            $data = M('jxc_allocation_info i')
                ->join("left join wst_jxc_goods g on g.goodsId=i.goodsId")
                ->where($where)
                ->field('i.*,g.goodsName,g.goodsImg,g.retailPrice,g.sellPrice,g.buyPirce')
                ->select();
        } else {
            $data = M('jxc_allocation_info i')
                ->join("left join wst_goods g on g.goodsId=i.goodsId")
                ->where($where)
                ->field('i.*,g.goodsName,g.goodsImg,g.shopPrice,g.marketPrice,g.memberPrice,g.goodsStock')
                ->select();
        }
        if (empty($data)) {
            return [];
        }
        return (array)$data;
    }

    /**
     * 获取申请调拨单列表
     * @param array $params [
     *                          string number:单号
     *                          int examineStatus:状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成|200:全部)
     *                          int warehouse:入库状态(0:未入库|1:部分入库|2:已入库|200:全部)
     *                          string startTime:开始时间
     *                          string endTime:结束时间
     *                          int page:页码,默认为1
     *                          int pageSize:分页条数,默认为15
     *                  ]
     * @param array $shopInfo
     * @return array $data
     */
    public function getAllocationOrderList(array $params, $shopInfo)
    {
        $startTime = $params['startTime'];
        $endTime = $params['endTime'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = ' a.dataFlag=1 and a.dataFrom=2 and a.shopId="' . $shopInfo['shopId'] . '"';
        $whereFind['a.examineStatus'] = $params['examineStatus'];
        $whereFind['a.warehouse'] = $params['warehouse'];
        if (!empty($startTime) && !empty($endTime)) {
            $where .= " and a.createTime between '" . $startTime . "' and '" . $endTime . "'";
        }
        $whereFind['a.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['a.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind)) {
            $whereInfo = $where;
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select a.*,s.shopName from __PREFIX__jxc_allocation_order a left join __PREFIX__shops s on s.shopId=a.shopId where $whereInfo order by a.allId desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        if (empty($rs['root'])) {
            return $rs;
        }
        $list = $rs['root'];
        foreach ($list as $key => $value) {
            $list[$key]['actionName'] = $value['shopName'];
            $list[$key]['toShopName'] = '';
            if ($list[$key]['toShopId'] == 0) {
                $list[$key]['toShopName'] = '总仓';
            }
            $toShopWhere = "shopFlag=1 and shopId='" . $value['toShopId'] . "'";
            $toShopInfo = M('shops')->where($toShopWhere)->find();//被申请人调拨的门店id
            if ($toShopInfo) {
                $list[$key]['toShopName'] = $toShopInfo['shopName'];
            }
        }
        $rs['root'] = $list;
        return $rs;
    }

    /**
     * 获取申请调拨单列表
     * @param array $params [
     *                          string number:单号
     *                          int examineStatus:状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成|200:全部)
     *                          int warehouse:入库状态(0:未入库|1:部分入库|2:已入库|200:全部)
     *                          string startTime:开始时间
     *                          string endTime:结束时间
     *                  ]
     * @param array $shopInfo
     * @return array $data
     */
    public function getWarehousingAllocationOrderList(array $params, $shopInfo)
    {
        $startTime = $params['startTime'];
        $endTime = $params['endTime'];
        $where = ' a.dataFlag=1 and a.dataFrom=2 and a.shopId="' . $shopInfo['shopId'] . '"';
        $whereFind['a.examineStatus'] = $params['examineStatus'];
        $whereFind['a.warehouse'] = $params['warehouse'];
        if (!empty($startTime) && !empty($endTime)) {
            $where .= " and a.createTime between '" . $startTime . "' and '" . $endTime . "'";
        }
        $whereFind['a.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['a.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind)) {
            $whereInfo = $where;
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select a.*,s.shopName from __PREFIX__jxc_allocation_order a left join __PREFIX__shops s on s.shopId=a.shopId where $whereInfo order by a.allId desc ";
        $list = $this->query($sql);
        if (empty($list)) {
            $list = [];
        }
        $warehouseInfoTab = M('in_warehouse_info info');//入库明细表
        foreach ($list as $key => $value) {
//            $where = [];
//            $where['ware.iwFlag'] = 1;
//            $where['info.dataId'] = $value['allId'];
//            $count = $warehouseInfoTab
//                ->join("left join wst_in_warehouse ware on ware.iwid=info.iwid")
//                ->where($where)
//                ->count();
//            if($count > 0 ){
//                unset($list[$key]);
//                continue;
//            }
            $list[$key]['actionName'] = $value['shopName'];
            $list[$key]['toShopName'] = '';
            if ($list[$key]['toShopId'] == 0) {
                $list[$key]['toShopName'] = '总仓';
            }
            $toShopWhere = "shopFlag=1 and shopId='" . $value['toShopId'] . "'";
            $toShopInfo = M('shops')->where($toShopWhere)->find();//被申请人调拨的门店id
            if ($toShopInfo) {
                $list[$key]['toShopName'] = $toShopInfo['shopName'];
            }
        }
        return array_values($list);
    }

    /**
     * 获取被申请调拨单列表(其他门店向当前门店发起的调拨请求)
     * @param array $params [
     *                          string number:单号
     *                          int examineStatus:状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成|200:全部)
     *                          int warehouse:入库状态(0:未入库|1:部分入库|2:已入库|200:全部)
     *                          string startTime:开始时间
     *                          string endTime:结束时间
     *                          int page:页码,默认为1
     *                          int pageSize:分页条数,默认为15
     *                  ]
     * @param array $shopInfo
     * @return array $data
     */
    public function getToAllocationOrderList(array $params, $shopInfo)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = ' a.dataFlag=1 and a.dataFrom=2 and a.toShopId="' . $shopInfo['shopId'] . '"';
        $whereFind['a.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['a.examineStatus'] = $params['examineStatus'];
        $whereFind['a.warehouse'] = $params['warehouse'];
        $whereFind['a.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind)) {
            $whereInfo = $where;
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select a.*,s.shopName from __PREFIX__jxc_allocation_order a left join __PREFIX__shops s on s.shopId=a.shopId where $whereInfo order by a.allId desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        if (empty($rs['root'])) {
            return $rs;
        }
        $list = $rs['root'];
        foreach ($list as $key => $value) {
            $list[$key]['actionName'] = $value['shopName'];
            $list[$key]['toShopName'] = '';
            if ($list[$key]['toShopId'] == 0) {
                $list[$key]['toShopName'] = '总仓';
            }
            $toShopWhere = "shopFlag=1 and shopId='" . $value['toShopId'] . "'";
            $toShopInfo = M('shops')->where($toShopWhere)->find();//被申请人调拨的门店id
            if ($toShopInfo) {
                $list[$key]['toShopName'] = $toShopInfo['shopName'];
            }
        }
        $rs['root'] = $list;
        return $rs;
    }

    /**
     * 删除调拨单
     * @param int allId 调拨单id
     * @param array shopInfo
     * @throws SuccessMessage
     */
    public function delAllocationOrder(int $allId, array $shopInfo)
    {
        $where = [];
        $where['allId'] = $allId;
        $where['shopId'] = $shopInfo['shopId'];
        $where['dataFlag'] = 1;
        $info = M('jxc_allocation_order')->where($where)->find();
        if (empty($info)) {
            if (empty($otpId)) {
                return returnData(null, -1, 'error', '无效的allId');
            }
        }
        if ($info['examineStatus'] == 1) {
            return returnData(null, -1, 'error', '调拨单[' . $info['number'] . ']已经审核，不能删除');
        }
        $save = [];
        $save['dataFlag'] = -1;
        $res = M('jxc_allocation_order')->where(['allId' => $allId])->save($save);
        if ($res !== false) {
            return returnData();
        } else {
            return returnData(null, -1, 'error', '调拨单[' . $info['number'] . ']删除失败');
        }
        return (bool)$res;
    }

    /**
     * 更改调拨单的状态 PS:后期再对状态进行完善和更改状态进行限制
     * @param string $allIds 调拨单id,多个用英文逗号分隔
     * @param int examineStatus 状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成)
     * @param string refusalRemark 拒绝原因
     * @param array $shopInfo
     */
    public function examineAllocationOrder($allIds, $examineStatus, $refusalRemark, $shopInfo)
    {
        foreach ($allIds as $val) {
            $where = [];
            $where['allId'] = $val;
            $info = M('jxc_allocation_order')->where($where)->find();
            if (!$info) {
                return returnData(null, -1, 'error', '参数有误，无效的调拨单id:' . $val);
            }
            if ($examineStatus == -1) {
                if ($info['toShopId'] != $shopInfo['shopId']) {
                    return returnData(null, -1, 'error', '调拨单对象和当前门店不一致，无权拒绝');
                } elseif (in_array($info['examineStatus'], [2, 3])) {
                    return returnData(null, -1, 'error', '调拨后的调拨单不能取消');
                }
            } elseif ($examineStatus == 0) {
                if ($info['examineStatus'] >= 1) {
                    return returnData(null, -1, 'error', '审核后的调拨单不能取消');
                }
            } elseif ($examineStatus == 1) {
                if (in_array($info['examineStatus'], [2, 3])) {
                    return returnData(null, -1, 'error', '已调拨或已完成的调拨单不能重复审核');
                }
            } elseif ($examineStatus == 2) {
                if ($info['examineStatus'] < 1) {
                    return returnData(null, -1, 'error', '未通过审核的调拨单不能直接更改到已调拨状态');
                } elseif ($info['examineStatus'] > 2) {
                    return returnData(null, -1, 'error', '已调拨的调拨单不能直接更改到已调拨状态');
                }
            } elseif ($examineStatus == 3) {
                if ($info['examineStatus'] < 2) {
                    return returnData(null, -1, 'error', '未调拨的调拨单不能直接更改到已完成状态');
                }
            }
            $where['allId'] = $val;
            $saveData = [];
            $saveData['examineStatus'] = $examineStatus;
            $saveData['refusalRemark'] = $refusalRemark;
            $rs = M('jxc_allocation_order')->where($where)->save($saveData);
        }
        if ($rs !== false) {
            $examineLog = [];
            foreach ($allIds as $val) {
                $where = [];
                $where['allId'] = $val;
                //后加时间线
                $examineLogInfo = [];
                $examineLogInfo['dataId'] = $val;
                $examineLogInfo['dataType'] = 2;
                $examineLogInfo['action'] = $this->getExamineName($examineStatus);
                $examineLogInfo['actionUserId'] = $shopInfo['shopId'];
                $examineLogInfo['actionUserType'] = 2;
                $examineLogInfo['actionUserName'] = $shopInfo['shopName'];
                $examineLogInfo['remark'] = '';
                $examineLogInfo['createTime'] = date('Y-m-d H:i:s');
                $examineLog[] = $examineLogInfo;
            }
            M('jxc_examine_log')->addAll($examineLog);
            return returnData();
        } else {
            return returnData(null, -1, 'error', '更改调拨单状态失败');
        }
    }

    /**
     * 获取调拨单对应的状态值
     * @param int $examineStatus 状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成)',
     * */
    public function getExamineName($examineStatus)
    {
        switch ($examineStatus) {
            case 0:
                $name = '反审核';
                break;
            case 1:
                $name = '审核通过';
                break;
            case 2:
                $name = '已调拨';
                break;
            case 3:
                $name = '已完成';
                break;
            default :
                $name = '已拒绝';
                break;
        }
        return $name;
    }

    //产品经理调整后 start

    /**
     * 获取总仓商品分类列表
     * @param int $parentId 分类父id
     */
    public function getErpGoodsCats(int $parentId, int $dataType)
    {
        $where = [];
        $where['isShow'] = 1;
        $where['dataFlag'] = 1;
        if ($dataType == 1) {
            $where['parentId'] = $parentId;
        }
        $field = 'catid,parentId,catname';
        $rs = M('jxc_goods_cat')
            ->where($where)
            ->field($field)
            ->order('catid desc')
            ->select();
        if ($dataType == 2) {
            $firstClass = [];
            foreach ($rs as $val) {
                if ($val['parentId'] == 0) {
                    $firstClass[] = $val;
                }
            }
            foreach ($firstClass as &$val) {
                $val['child'] = [];
                foreach ($rs as $rsVal) {
                    if ($rsVal['parentId'] == $val['catid']) {
                        $val['child'][] = $rsVal;
                    }
                }
            }
            unset($val);
            foreach ($firstClass as $key => $value) {
                foreach ($value['child'] as $childKey => $childVal) {
                    foreach ($rs as $rval) {
                        if ($rval['parentId'] == $childVal['catid']) {
                            $firstClass[$key]['child'][$childKey]['child'][] = $rval;
                        }
                    }
                }
            }
            $rs = $firstClass;
        }
        return returnData((array)$rs);
    }

    /**
     * 获取总仓商品列表
     * @param array $params [
     *                          int goodsCat1 一级分类id
     *                          int goodsCat2 二级分类id
     *                          int goodsCat3 三级分类id
     *                          string keywords 关键字(商品名称/商品编码)
     *                          varchar goodsName 商品名称 废除
     *                          varchar goodsSn 商品编码/条码 废除
     *                          int page 分页,默认为1
     *                          int pageSize 分页条数,默认15条
     *              ]
     */
    public function getErpGoodsList(array $params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " g.dataFlag=1 and g.isSale=1 and g.examineStatus=1 ";
        $whereFind = [];
        $whereFind['g.goodsCat1'] = function () use ($params) {
            if (empty($params['goodsCat1'])) {
                return null;
            }
            return ['=', "{$params['goodsCat1']}", 'and'];
        };
        $whereFind['g.goodsCat2'] = function () use ($params) {
            if (empty($params['goodsCat2'])) {
                return null;
            }
            return ['=', "{$params['goodsCat2']}", 'and'];
        };
        $whereFind['g.goodsCat3'] = function () use ($params) {
            if (empty($params['goodsCat3'])) {
                return null;
            }
            return ['=', "{$params['goodsCat3']}", 'and'];
        };
        $whereFind['g.goodsName'] = function () use ($params) {
            if (empty($params['goodsName'])) {
                return null;
            }
            return ['like', "%{$params['goodsName']}%", 'and'];
        };
        $whereFind['g.goodsSn'] = function () use ($params) {
            if (empty($params['goodsSn'])) {
                return null;
            }
            return ['like', "%{$params['goodsSn']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind)) {
            $whereInfo = $where;
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        if (!empty($params['keywords'])) {
            $whereInfo .= " and (g.goodsName like '%{$params['keywords']}%' or g.goodsSn like '%{$params['keywords']}%') ";
        }
        $sql = "select g.goodsId,g.goodsSn,g.goodsName,g.goodsImg, g.goodsThums,g.retailPrice,g.sellPrice,g.buyPirce,g.isSale,g.saleTime,g.goodsCat1,g.goodsCat2,g.goodsCat3,g.createTime,g.sort,g.goodsUnit,g.stockWarning,g.liveDate,g.warehousingTime,g.stock,g.standardProduct,g.weightG,g.endLiveDate from __PREFIX__jxc_goods as g where $whereInfo order by goodsId desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($rs['root'])) {
            $goods = $rs['root'];
            $goodsCatIds = [];
            foreach ($goods as $value) {
                $goodsCatIds[] = $value['goodsCat1'];
                $goodsCatIds[] = $value['goodsCat2'];
                $goodsCatIds[] = $value['goodsCat3'];
            }
            $goodsCatIds = array_unique($goodsCatIds);
            $catWhere = [];
            $catWhere['catid'] = ["IN", $goodsCatIds];
            $catWhere['dataFlag'] = 1;
            $catWhere['isShow'] = 1;
            $goodsCatList = M('jxc_goods_cat')->where($catWhere)->select();
            foreach ($goods as $key => $value) {
                $goods[$key]['buyPirce'] = $value['retailPrice'];
                $goods[$key]['goodsThums'] = $value['goodsImg'];
                $goods[$key]['hasSku'] = -1;
                $systemTab = M('jxc_sku_goods_system');
                $where = [];
                $where['goodsId'] = $value['goodsId'];
                $where['dataFlag'] = 1;
                $where['examineStatus'] = 1;
                $systemCount = $systemTab->where($where)->count();
                if ($systemCount > 0) {
                    $goods[$key]['hasSku'] = 1;
                }
                foreach ($goodsCatList as $val) {
                    if ($value['goodsCat1'] == $val['catid']) {
                        $goods[$key]['goodsCat1Name'] = $val['catname'];
                    }
                    if ($value['goodsCat2'] == $val['catid']) {
                        $goods[$key]['goodsCat2Name'] = $val['catname'];
                    }
                    if ($value['goodsCat3'] == $val['catid']) {
                        $goods[$key]['goodsCat3Name'] = $val['catname'];
                    }
                }
            }
            $rs['root'] = $goods;
        }
        return returnData($rs);
    }

    /**
     * 根据总仓商品id获取商品规格列表
     * @param int $goodsId 商品id
     */
    public function getErpGoodsSkuByGoodsId(int $goodsId)
    {
        $systemTab = M('jxc_sku_goods_system');
        $systemWhere = [];
        $systemWhere['system.goodsId'] = $goodsId;
        $systemWhere['system.dataFlag'] = 1;
        $systemWhere['system.examineStatus'] = 1;
        $systemWhere['unit.dataFlag'] = 1;
        $field = 'system.skuId,system.goodsId,system.retailPrice,system.stock,system.skuGoodsImg,system.skuBarcode,system.supplierId,system.examineStatus,system.createTime,system.sellPrice,system.buyPirce,goods.goodsName,goods.standardProduct,goods.weightG,goods.goodsImg,goods.unitId,unit.goodsUnit';
        $skuGoodsSystem = M('jxc_sku_goods_system system')
            ->join('left join wst_jxc_goods goods on goods.goodsId=system.goodsId')
            ->join('left join wst_jxc_goods_unit unit on unit.id=goods.unitId')
            ->where($systemWhere)
            ->field($field)
            ->select();
        if (empty($skuGoodsSystem)) {
            return returnData();
        }
        foreach ($skuGoodsSystem as $k => $v) {
            $skuId[] = $v['skuId'];
        }
        $skuWhere = [];
        $skuWhere['s.skuId'] = ["IN", $skuId];
        $skuWhere['s.dataFlag'] = 1;
        $skuList = M('jxc_sku_goods_self s')->where($skuWhere)
            ->join("left join wst_jxc_sku_spec a on s.specId = a.specId")
            ->join("left join wst_jxc_sku_spec_attr b on s.attrId = b.attrId ")
            ->field('a.specId,a.specName,b.attrId,b.attrName,skuId')
            //->group('a.specId')
            ->select();
        foreach ($skuGoodsSystem as $k => $v) {
            $skuGoodsSystem[$k]['goodsThums'] = (string)$v['skuGoodsImg'];
            $skuGoodsSystem[$k]['skuGoodsImg'] = (string)$v['skuGoodsImg'];
            $skuGoodsSystem[$k]['goodsUnit'] = (string)$v['goodsUnit'];
            $skuSpecAttr = [];
            foreach ($skuList as $kk => $vv) {
                if ($vv['skuId'] == $v['skuId']) {
                    $skuSpecAttr[] = $vv;
                }
            }
            $skuSpecAttrStr = '';
            foreach ($skuSpecAttr as $av) {
                $skuSpecAttrStr .= $av['attrName'] . '，';
            }
            $skuGoodsSystem[$k]['skuSpecAttr'] = $skuSpecAttr;
            $skuGoodsSystem[$k]['skuSpecAttrStr'] = rtrim($skuSpecAttrStr, '，');
        }
        return returnData((array)$skuGoodsSystem);
    }

    /**
     * 根据调拨条件获取门仓商品列表
     * @param array shopInfo 店铺信息
     * @param int goodsId 总仓商品id
     * @param int skuId 总仓商品规格id
     * @param float num 调拨数量
     */
    public function getAllocationShopGoods($shopInfo, $goodsId, $skuId, $num)
    {
        $erpGoodsInfo = $this->getErpGoodsDetail($goodsId);
        if (empty($erpGoodsInfo['goodsSn'])) {
            return [];
        }
        $lat = $shopInfo['latitude'];
        $lng = $shopInfo['longitude'];
        $goodsSn = $erpGoodsInfo['goodsSn'];
        $skuBarcode = '';
        if (!empty($skuId) && !empty($erpGoodsInfo['skuGoodsSystem'])) {
            $skuGoodsSystem = $erpGoodsInfo['skuGoodsSystem'];
            foreach ($skuGoodsSystem as $skuVal) {
                if ($skuVal['skuId'] == $skuId) {
                    $skuBarcode = $skuVal['skuBarcode'];
                }
            }
            if (empty($skuBarcode)) {
                return [];
            }
        }
        $where = " g.goodsFlag=1 and g.isSale=1 and g.goodsStatus=1 and g.goodsSn='{$goodsSn}' ";
        //$where .= " and s.shopFlag=1 and s.shopStatus=1 and s.shopAtive=1 and s.shopId != '{$shopInfo['shopId']}'";
        $where .= " and s.shopFlag=1 and s.shopStatus=1 and s.shopId != '{$shopInfo['shopId']}'";
        $field = 'g.goodsId,g.goodsName,g.goodsImg';
        if (empty($skuBarcode)) {
            $field .= ",g.goodsStock";
            //$where .= " and g.goodsStock >= $num ";
        } else {
            $field .= ",system.skuId,system.skuGoodsStock as goodsStock ";
        }
        $field .= ',s.shopId,s.shopName';
        $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";//距离
        $sql = "select $field from __PREFIX__goods g left join __PREFIX__shops s on s.shopId=g.shopId ";
        if (!empty($skuBarcode)) {
            $where .= " and system.skuBarcode='{$skuBarcode}' and system.dataFlag=1 ";
            //$where .= " and system.skuGoodsStock >= $num ";
            $sql .= " left join __PREFIX__sku_goods_system system on system.goodsId=g.goodsId ";
        }
        $sql .= " where $where order by distance asc ";
        $rs = $this->query($sql);
        foreach ($rs as $key => $val) {
            if (empty($val['skuId'])) {
                $rs[$key]['skuId'] = 0;
            }
            $skuIdArr = $val['skuId'];
            $rs[$key]['skuSpecAttrStr'] = '';//sku属性值
            $rs[$key]['num'] = $num;//调拨数量
        }
        $rs = empty($rs) ? [] : $rs;
        if (empty($skuIdArr)) {
            return $rs;
        }
        //处理商品的sku信息
        $skuSelfList = M("sku_goods_self se")
            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
            ->where(['se.skuId' => ['IN', $skuIdArr], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
            ->group('specId')
            ->order('sp.sort asc')
            ->select();
        foreach ($rs as $key => $val) {
            foreach ($skuSelfList as $selfVal) {
                if ($val['skuId'] == $selfVal['skuId']) {
                    $val['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
                }
            }
            $rs[$key]['skuSpecAttrStr'] = trim($val['skuSpecAttrStr'], '，');
        }
        $rs = empty($rs) ? [] : $rs;
        return $rs;
    }

    /**
     * 创建调拨单
     * @param int $goods_id 门仓商品id
     * @param int $sku_id 规格id
     * @param float $num 调拨数量
     * @param array $login_info 门店信息
     */
    public function addAllocationBill(int $goods_id, int $sku_id, float $num, array $login_info)
    {
        $input_shop_id = $login_info['shopId'];//入库门店
        $action_user_id = !empty($login_info['id']) ? $login_info['id'] : $login_info['shopId'];
        $action_user_name = !empty($login_info['id']) ? $login_info['name'] : $login_info['shopName'];
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsName,goodsStock,shopId';
        $out_goods_result = $goods_module->getGoodsInfoById($goods_id, $field);//出库商品信息
        if ($out_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, -1, 'error', '操作失败，商品信息有误');
        }
        $out_goods_info = $out_goods_result['data'];
        if (!empty($sku_id)) {
            //存在sku
            $out_sku_result = $goods_module->getSkuSystemInfoById($sku_id);
            if ($out_sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, -1, 'error', '操作失败，请传入正确的skuId');
            }
            $out_goods_info['goodsStock'] = $out_sku_result['data']['skuGoodsStock'];
        }
        $out_goods_info['goodsStock'] = (float)$out_goods_info['goodsStock'];//有sku/无sku最终库存以goodsStock字段计算
        if ((float)$num > (float)$out_goods_info['goodsStock']) {
            return returnData(false, -1, 'error', "商品库存不足，本次最多调拨数量{$out_goods_info['goodsStock']}");
        }
        $out_shop_id = $out_goods_info['shopId'];//出库门店
        M()->startTrans();
        // 启动事务
        $bill_save = array(
            'outShopId' => $out_shop_id,
            'inputShopId' => $input_shop_id,
            'actionUserId' => $action_user_id,
            'actionUserType' => $login_info['login_type'],
            'actionUserName' => $action_user_name,
        );
        $allocation_bill_module = new AllocationBillModule();
        $bill_save_res = $allocation_bill_module->saveAllocationBill($bill_save, M());
        if ($bill_save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', "调拨单创建失败");
        }
        $allId = $bill_save_res['data']['allId'];
        $bill_no = 'DBD' . date('Ymd') . str_pad($allId, 10, "0", STR_PAD_LEFT);
        $save_params = array(
            'allId' => $allId,
            'number' => $bill_no,
        );
        $save_res = $allocation_bill_module->saveAllocationBill($save_params, M());
        if ($save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', "调拨单信息保存失败");
        }
        //添加调拨单明细
        $info = array(
            'allId' => $allId,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
            'num' => $num,
            'warehouseNum' => $num,
        );
        $save_info_res = $allocation_bill_module->saveAllocationBillInfo($info, M());
        if ($save_info_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', "调拨单保存失败");
        }
        //操作日志 start
        $erp_module = new ErpModule();
        $log_params = array();
        $log_params['dataId'] = $allId;
        $log_params['dataType'] = 2;
        $log_params['action'] = "调入仓创建调拨单#{$save_params['number']}";
        $log_params['actionUserId'] = $action_user_id;
        $log_params['actionUserType'] = $login_info['login_type'];
        $log_params['actionUserName'] = $action_user_name;
        $log_params['status'] = 0;
        $log_params['warehouseStatus'] = 0;
        $log_res = $erp_module->addBillActionLog($log_params, M());
        if (!$log_res) {
            M()->rollback();
            return returnData(false, -1, 'error', "调拨单日志记录失败");
        }
        //操作日志 end
        M()->commit();
        return returnData(true);
    }

    /**
     * 调拨单->新建入库单 PS:针对手动入库的场景,单据生成则入库完成
     * @param int $goodsId 门仓商品id
     * @param int $skuId 规格id
     * @param float $num 调拨数量
     * @param int $warehouseUserId 入库人员id
     * @param array $shopInfo 门店信息
     */
    public function addAllocationBillComplete(int $goodsId, int $skuId, float $num, int $warehouseUserId, array $shopInfo)
    {
        $inputShopId = $shopInfo['shopId'];
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $goodsTab = M('goods');
        $where = [];
        $where['goodsId'] = $goodsId;
        $field = 'goodsId,goodsName,goodsStock,shopId';
        $goodsInfo = $goodsTab->where($where)->field($field)->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '操作失败，商品信息有误');
        }
        if (!empty($skuId)) {
            $skuWhere = [];
            $skuWhere['skuId'] = $skuId;
            $skuInfo = M('sku_goods_system')->where($skuWhere)->find();
            if (empty($skuInfo)) {
                return returnData(false, -1, 'error', '操作失败，请传入正确的skuId');
            }
            $goodsInfo['goodsStock'] = $skuInfo['skuGoodsStock'];
        }
        if ($num > $goodsInfo['goodsStock']) {
            return returnData(false, -1, 'error', '商品库存不足');
        }
        $outShopId = $goodsInfo['shopId'];//调出仓id
        M()->startTrans();
        $orderno = $this->addOrderno();//生成订单号
        // 启动事务
        $order = [];
        $order['number'] = "DBD" . $orderno;
        $order['outShopId'] = $outShopId;
        $order['inputShopId'] = $inputShopId;
        $order['actionUserId'] = $actionUserId;
        $order['actionUserType'] = $shopInfo['login_type'];
        $order['actionUserName'] = $actionUserName;
        $order['warehouseUserId'] = $warehouseUserId;
        $order['status'] = 3;
        $order['warehouseStatus'] = 2;
        $order['createTime'] = date('Y-m-d H:i:s');
        $allId = M('jxc_allocation_bill')->add($order);//添加调拨单主表信息
        //添加调拨单明细
        $info = [];
        $info['allId'] = $allId;
        $info['goodsId'] = $goodsId;
        $info['skuId'] = $skuId;
        $info['num'] = $num;
        $info['warehouseNum'] = $num;
        $info['warehouseCompleteNum'] = $num;
        $infoId = M('jxc_allocation_bill_info')->add($info);
        if ($allId <= 0 || $infoId <= 0) {
            M()->rollback();
            return returnData(false, -1, 'error', '调拨失败');
        }
        //操作日志 start
        $logParams = [];
        $logParams['dataId'] = $allId;
        $logParams['dataType'] = 2;
        $logParams['action'] = "调入仓创建入库单#{$order['number']}";
        $logParams['actionUserId'] = !empty($uid) ? $uid : $inputShopId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        $logParams['status'] = 3;
        $logParams['warehouseStatus'] = 2;
        $this->addBillActionLog($logParams);
        //操作日志 end
        M()->commit();
        return returnData(true);
    }

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
    public function addBillActionLog($params)
    {
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
        $res = M('jxc_bill_action_log')->add($logData);
        return (bool)$res;
    }

    /**
     * 我的调入/我的调出
     * @param int $shopId
     * @param int $dataType 数据类型【1：我的调入|2：我的调出】
     * @param array $search 搜索条件
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     */
    public function allocationBillListIO(int $shopId, int $dataType, int $page, int $pageSize, array $search)
    {
        $where = "bill.dataFlag=1 ";
        if ($dataType == 1) {
            //我的调入
            $where .= " and bill.inputShopId='{$shopId}'";
        } else {
            //我的调出
            $where .= " and bill.outShopId='{$shopId}'";
        }
        $whereFind = [];
        $whereFind['bill.number'] = function () use ($search) {
            if (empty($search['number'])) {
                return null;
            }
            return ['like', "%{$search['number']}%", 'and'];
        };
        $whereFind['bill.createTime'] = function () use ($search) {
            if (empty($search['startDate'])) {
                return null;
            }
            return ['between', "{$search['startDate']}' and '{$search['endDate']}", 'and'];
        };
        $whereFind['bill.status'] = function () use ($search) {
            if (!is_numeric($search['status'])) {
                return null;
            }
            return ['=', "{$search['status']}", "and"];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
//        $billStatusWhere = $this->billCodeToStatus($search);//获取单据状态条件
//        if(!empty($billStatusWhere)){
//            $whereInfo .= " and {$billStatusWhere} ";
//        }
        $field = 'bill.allId,bill.number,bill.outShopId,bill.inputShopId,bill.status,bill.warehouseStatus,bill.warehouseUserId,bill.createTime,bill.billPic,bill.consigneeName';
        $field .= ',info.infoId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsStock,goods.goodsImg';
        $field .= ',inputShop.shopName as inputShopName,outShop.shopName as outShopName';
        $field .= ',user.name as warehouseUserName ';
        $sql = "select $field from __PREFIX__jxc_allocation_bill_info info ";
        $sql .= " left join __PREFIX__jxc_allocation_bill bill on bill.allId=info.allId ";
        $sql .= " left join __PREFIX__goods goods on goods.goodsId=info.goodsId ";
        $sql .= " left join __PREFIX__shops inputShop on inputShop.shopId=bill.inputShopId ";
        $sql .= " left join __PREFIX__shops outShop on outShop.shopId=bill.outShopId ";
        $sql .= " left join __PREFIX__user user on user.id=bill.warehouseUserId ";
        $sql .= " where $whereInfo ";
        $sql .= " order by bill.createTime desc ";
        $res = $this->pageQuery($sql, $page, $pageSize);
        if (empty($res['root'])) {
            return $res;
        }
        $list = $res['root'];
        $systemTab = M('sku_goods_system');
        foreach ($list as $key => $value) {
            $list[$key]['warehouseNoNum'] = $value['warehouseNum'] - $value['warehouseCompleteNum'];//剩余入库数量
            $list[$key]['actionUserName'] = $value['inputShopName'];//制单人先用调入仓名称,因为目前只在门仓生成调拨单
            $list[$key]['skuSpecAttrStr'] = '';
            if ($value['skuId'] > 0) {
                $systemInfo = $systemTab->where(['skuId' => $value['skuId'], 'dataFlag' => 1])->find();
                if ($systemInfo) {
                    $list[$key]['goodsStock'] = $systemInfo['skuGoodsStock'];
                }
                //处理商品的sku信息
                $skuSelfList = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->group('specId')
                    ->order('sp.sort asc')
                    ->select();
                foreach ($skuSelfList as $selfVal) {
                    $list[$key]['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
                }
                $list[$key]['skuSpecAttrStr'] = rtrim($list[$key]['skuSpecAttrStr'], '，');
            }
            //$list[$key]['billCode'] = $this->billStatusToCode($value);
        }
        $res['root'] = $list;
        return $res;
    }

    /**
     * 处理调拨单状态条件
     * @param array $params <p>
     *          int status 状态【-1:已拒绝(平台)|0:待审核(平台)|1:确认调拨(调出仓)|2:确认发货(调入仓)|3:已完成】
     *          int warehouseStatus 入库状态(0:待入库|1:部分入库|2:已入库)
     * </p>
     * */
    public function billStatusToCode($params)
    {
        $billCode = '';
        if ($params['status'] == -1) {
            $billCode = 'refuse';
        }
        if ($params['status'] == 0) {
            $billCode = 'audit';
        }
        if ($params['status'] == 1) {
            $billCode = 'allocation';
        }
        if ($params['status'] == 2) {
            $billCode = 'deliver';
        }
        if ($params['status'] == 3) {
            $billCode = 'completed';
        }
        if ($params['warehouseStatus'] == 0 && $params['status'] == 3) {
            $billCode = 'warehouseNo';
        }
        if ($params['warehouseStatus'] == 1 && $params['status'] == 3) {
            $billCode = 'warehousingPartial';
        }
        if ($params['warehouseStatus'] == 2 && $params['status'] == 3) {
            $billCode = 'warehouseCompleted';
        }
        return $billCode;
    }

    /**
     * 处理调拨单状态条件
     * @param array $params <p>
     *          string billCode 状态【refuse:平台已拒绝|audit:平台待审核|allocation:调出方确认调拨|deliver:等待调入方收货|completed:调出方已交货|warehouseNo:调入方未入库|warehousingPartial:调入方部分入库|warehouseCompleted:调入方完成入库】
     * </p>
     * */
    public function billCodeToStatus($params)
    {
        $where = " bill.dataFlag=1 ";
        if (empty($params['billCode'])) {
            return $where;
        }
        if ($params['billCode'] == 'refuse') {
            $where .= " and bill.status=-1 ";
        }
        if ($params['billCode'] == 'audit') {
            $where .= " and bill.status=0 ";
        }
        if ($params['billCode'] == 'allocation') {
            $where .= " and bill.status=1 ";
        }
        if ($params['billCode'] == 'deliver') {
            $where .= " and bill.status=2 ";
        }
        if ($params['billCode'] == 'completed') {
            $where .= " and bill.status=3 ";
        }
        if ($params['billCode'] == 'warehouseNo') {
            $where .= " and bill.warehouseStatus=0 and bill.status=3 ";
        }
        if ($params['billCode'] == 'warehousingPartial') {
            $where .= " and bill.warehouseStatus=1 and bill.status=3 ";
        }
        if ($params['billCode'] == 'warehouseCompleted') {
            $where .= " and bill.warehouseStatus=2 and bill.status=3 ";
        }
        return $where;
    }

    /**
     * 已完成的调拨
     * @param int $shopId
     * @param array $search 搜索条件
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     */
    public function allocationBillListComplete(int $shopId, int $page, int $pageSize, array $search)
    {
        $where = "bill.dataFlag=1 and bill.status=3 and (inputShopId='{$shopId}' or outShopId='{$shopId}')";
        $whereFind = [];
        $whereFind['bill.number'] = function () use ($search) {
            if (empty($search['number'])) {
                return null;
            }
            return ['like', "%{$search['number']}%", 'and'];
        };
        $whereFind['bill.createTime'] = function () use ($search) {
            if (empty($search['startDate'])) {
                return null;
            }
            return ['between', "{$search['startDate']}' and '{$search['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = 'bill.allId,bill.number,bill.outShopId,bill.inputShopId,bill.status,bill.createTime,bill.billPic,bill.consigneeName,bill.warehouseUserId,bill.warehouseStatus';
        $field .= ',info.infoId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsStock,goods.goodsImg';
        $field .= ',inputShop.shopName as inputShopName,outShop.shopName as outShopName';
        $field .= ',user.name as warehouseUserName ';
        $sql = "select $field from __PREFIX__jxc_allocation_bill_info info ";
        $sql .= " left join __PREFIX__jxc_allocation_bill bill on bill.allId=info.allId ";
        $sql .= " left join __PREFIX__goods goods on goods.goodsId=info.goodsId ";
        $sql .= " left join __PREFIX__shops inputShop on inputShop.shopId=bill.inputShopId ";
        $sql .= " left join __PREFIX__shops outShop on outShop.shopId=bill.outShopId ";
        $sql .= " left join __PREFIX__user user on user.id=bill.warehouseUserId ";
        $sql .= " where $whereInfo ";
        $sql .= " order by bill.createTime desc ";
        $res = $this->pageQuery($sql, $page, $pageSize);
        if (empty($res['root'])) {
            return $res;
        }
        $list = $res['root'];
        $systemTab = M('sku_goods_system');
        foreach ($list as $key => $value) {
            $list[$key]['warehouseNoNum'] = $value['warehouseNum'] - $value['warehouseCompleteNum'];//剩余入库数量
            $list[$key]['dataType'] = $shopId == $value['inputShopId'] ? 1 : 2;//调入调出【1：调入|2：调出】
            $list[$key]['actionUserName'] = $value['inputShopName'];//制单人先用调入仓名称,因为目前只在门仓生成调拨单
            $list[$key]['skuSpecAttrStr'] = '';
            if ($value['skuId'] > 0) {
                $systemInfo = $systemTab->where(['skuId' => $value['skuId'], 'dataFlag' => 1])->find();
                if ($systemInfo) {
                    $list[$key]['goodsStock'] = $systemInfo['skuGoodsStock'];
                }
                //处理商品的sku信息
                $skuSelfList = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->group('specId')
                    ->order('sp.sort asc')
                    ->select();
                foreach ($skuSelfList as $selfVal) {
                    $list[$key]['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
                }
                $list[$key]['skuSpecAttrStr'] = rtrim($list[$key]['skuSpecAttrStr'], '，');
            }
            //$list[$key]['billCode'] = $this->billStatusToCode($value);
        }
        $res['root'] = $list;
        return $res;
    }


    /**
     *获取调拨单详情
     * @param int $allId 调拨单id
     */
    public function getAllocationBillDetail(int $allId)
    {
        $where = 'bill.dataFlag=1 ';
        $where .= " and bill.allId='{$allId}'";
        $field = 'bill.allId,bill.number,bill.outShopId,bill.inputShopId,bill.status,bill.createTime,bill.billPic,bill.consigneeName,bill.warehouseStatus';
        $field .= ',info.infoId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsStock,goods.goodsImg,goods.goodsSn';
        $field .= ',inputShop.shopName as inputShopName,outShop.shopName as outShopName';
        $field .= ',user.name as warsehouseUserName';
        $sql = "select $field from __PREFIX__jxc_allocation_bill_info info ";
        $sql .= " left join __PREFIX__jxc_allocation_bill bill on bill.allId=info.allId ";
        $sql .= " left join __PREFIX__goods goods on goods.goodsId=info.goodsId ";
        $sql .= " left join __PREFIX__shops inputShop on inputShop.shopId=bill.inputShopId ";
        $sql .= " left join __PREFIX__shops outShop on outShop.shopId=bill.outShopId ";
        $sql .= " left join __PREFIX__user user on user.id=bill.warehouseUserId ";
        $sql .= " where $where ";
        $res = $this->queryRow($sql);
        if (empty($res)) {
            return [];
        }
        $res['warsehouseUserName'] = (string)$res['warsehouseUserName'];
        $res['warehouseNoNum'] = $res['warehouseNum'] - $res['warehouseCompleteNum'];//剩余入库数量
        //$res['billCode'] = $this->billStatusToCode($res);
        $systemTab = M('sku_goods_system');
        if ($res['skuId'] > 0) {
            $systemInfo = $systemTab->where(['skuId' => $res['skuId'], 'dataFlag' => 1])->find();
            if ($systemInfo) {
                $res['goodsStock'] = $systemInfo['skuGoodsStock'];
                $res['goodsSn'] = $systemInfo['skuBarcode'];
            }
            //处理商品的sku信息
            $skuSelfList = M("sku_goods_self se")
                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                ->where(['se.skuId' => $res['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                ->group('specId')
                ->order('sp.sort asc')
                ->select();
            foreach ($skuSelfList as $selfVal) {
                $res['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
            }
            $res['skuSpecAttrStr'] = rtrim($res['skuSpecAttrStr'], '，');
        }
        //调拨单日志
        $res['log'] = [];
        $where = [];
        $where['dataId'] = $allId;
        $where['dataType'] = 2;
        $field = 'logId,action,actionUserName,createTime';
        $logList = M('jxc_bill_action_log')
            ->where($where)
            ->field($field)
            ->select();
        if (!empty($logList)) {
            $res['log'] = $logList;
        }
        //调拨商品明细 PS:后加,前面的逻辑就不动了
        $res['goods'] = [];
        $where = [];
        $where['info.allId'] = $allId;
        $field = 'info.*';
        $field .= ',goods.goodsName,goods.goodsSn,goods.goodsImg,goods.goodsThums,goods.shopPrice,goods.goodsStock';
        $goods = M('jxc_allocation_bill_info info')
            ->join('left join wst_goods goods on goods.goodsId=info.goodsId')
            ->where($where)
            ->field($field)
            ->select();
        if (!empty($goods)) {
            $goodsSkuModel = D('Home/GoodsSku');
            foreach ($goods as $key => $val) {
                $goods[$key]['warehouseNoNum'] = $val['warehouseNum'] - $val['warehouseCompleteNum'];//剩余入库数量
                $goods[$key]['skuInfo'] = [];
                if ($val['skuId'] > 0) {
                    $skuInfo = $systemTab->where(['skuId' => $res['skuId'], 'dataFlag' => 1])->select();
                    if (empty($skuInfo)) {
                        continue;
                    }
                    $skuInfo = $goodsSkuModel->returnSystemSkuValue($skuInfo)[0];
                    $goods[$key]['goodsStock'] = $skuInfo['skuGoodsStock'];
                    $goods[$key]['goodsSn'] = $skuInfo['skuBarcode'];
                    $goods[$key]['shopPrice'] = $skuInfo['skuShopPrice'];
                    //处理商品的sku信息
                    $skuSelfList = M("sku_goods_self se")
                        ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                        ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                        ->where(['se.skuId' => $skuInfo['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                        ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                        ->group('specId')
                        ->order('sp.sort asc')
                        ->select();
                    foreach ($skuSelfList as $selfVal) {
                        $skuInfo['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
                    }
                    $skuInfo['selfSpec'] = $skuSelfList;
                    $skuInfo['skuSpecAttrStr'] = rtrim($res['skuSpecAttrStr'], '，');
                    $goods[$key]['skuInfo'] = $skuInfo;
                }
            }
            $res['goods'] = $goods;
        }
        return $res;
    }

    /**
     * 更改调拨单的状态
     * @param array $shopInfo
     * @param int $allId 调拨id
     * @param int $dataType 场景【1：场景1->调出方确认调拨|2：场景2->调入方再次发起调拨|3：场景3->调入方收货】
     * @param varchar $billPic 单据照片【场景3】
     * @param varchar $consigneeName 接货人姓名【场景3】
     */
    public function updateAllocationBillStatus($shopInfo, $allId, $dataType, $billPic, $consigneeName)
    {
        $shopId = $shopInfo['shopId'];
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopId;
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $billTab = M('jxc_allocation_bill');
        $billInfo = $this->getAllocationBillDetail($allId);
        if (empty($billInfo)) {
            return returnData(false, -1, 'error', '请输入正确的调拨单id');
        }
        if ($dataType == 1) {
            //调出方确认调拨
            if ($shopId != $billInfo['outShopId']) {
                return returnData(false, -1, 'error', '只有调出仓才有权限操作确认调拨操作');
            }
            if ($billInfo['status'] != 1) {
                return returnData(false, -1, 'error', '只有已审核状态下调出仓才能操作确认调拨');
            }
            $saveData = [];
            $saveData['status'] = 2;
        }
        if ($dataType == 2) {
            //调入方再次发起调拨
            if ($shopId != $billInfo['inputShopId']) {
                return returnData(false, -1, 'error', '只有调入仓才有权限操作再次调拨');
            }
            if ($billInfo['status'] != -1) {
                return returnData(false, -1, 'error', '只有已拒绝状态下调入仓才能再次发起调拨');
            }
            $saveData = [];
            $saveData['status'] = 0;
        }
        if ($dataType == 3) {
            //调出方确认发货
            if ($shopId != $billInfo['outShopId']) {
                return returnData(false, -1, 'error', '只有调出仓才有权限执行该操作');
            }
            if ($billInfo['status'] != 2) {
                return returnData(false, -1, 'error', '调出仓确认调拨后才能执行该操作');
            }
            $saveData = [];
            $saveData['status'] = 3;
            $saveData['billPic'] = $billPic;
            $saveData['consigneeName'] = $consigneeName;
            $billData = array(
                'pagetype' => 3,
                'shopId' => $shopId,
                'user_id' => $shopInfo['user_id'],
                'user_name' => $shopInfo['user_username'],
                'remark' => '',
                'relation_order_number' => $billInfo['number'],
                'relation_order_id' => $billInfo['allId'],
                'goods_data' => array(),
            );
            foreach ($billInfo['goods'] as $goodsVal) {
                $billData['goods_data'][] = array(
                    'goods_id' => $goodsVal['goodsId'],
                    'sku_id' => $goodsVal['skuId'],
                    'nums' => $goodsVal['num'],
                    'actual_delivery_quantity' => $goodsVal['num'],
                );
            }
            (new ExWarehouseOrderModule())->addExWarehouseOrder($billData);
        }
        $res = $billTab->where(['allId' => $allId])->save($saveData);
        //$res = 1;
        if (!$res) {
            return returnData(false, -1, 'error', '操作失败');
        }
        //添加操作日志
        $logParams = [];
        $logParams['dataId'] = $allId;
        $logParams['dataType'] = 2;
        $logParams['actionUserId'] = $actionUserId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        if ($dataType == 1) {
            $logParams['action'] = '调出方确认调拨';
            $logParams['status'] = $saveData['status'];
            $logParams['warehouseStatus'] = 0;
        }
        if ($dataType == 2) {
            $logParams['action'] = '调入方再次发起调拨';
            $logParams['status'] = 0;
            $logParams['warehouseStatus'] = 0;
        }
        if ($dataType == 3) {
            $logParams['action'] = '调出方已交货';
            $logParams['status'] = $saveData['status'];
            $logParams['warehouseStatus'] = 0;
        }
        $this->addBillActionLog($logParams);
        return returnData(true);
    }

    /**
     * 统计调拨单各个状态的总数
     * @param int $shopId
     */
    public function allocationBillCount(int $shopId)
    {
        $billTab = M('jxc_allocation_bill');
        //已完成的调拨
        $where = "(inputShopId='{$shopId}' or outShopId='{$shopId}') and dataFlag=1 and status=3 ";
        $completeCount = $billTab->where($where)->count();
        //我的调入
        $where = "(inputShopId='{$shopId}') and dataFlag=1 ";
        $inputCount = $billTab->where($where)->count();
        //我的调出
        $where = "(outShopId='{$shopId}') and dataFlag=1 ";
        $outCount = $billTab->where($where)->count();
        //未入库
        $where = "status=3 and warehouseStatus=0 and dataFlag=1 ";
        $warehouseStatusNo = $billTab->where($where)->count();
        //部分入库
        $where = "status=3 and warehouseStatus=1 and dataFlag=1 ";
        $warehouseStatusSome = $billTab->where($where)->count();
        //入库完成
        $where = "status=3 and warehouseStatus=2 and dataFlag=1 ";
        $warehouseStatusComplete = $billTab->where($where)->count();

        $res['completeCount'] = (int)$completeCount;
        $res['inputCount'] = (int)$inputCount;
        $res['outCount'] = (int)$outCount;
        $res['warehouseStatusNo'] = (int)$warehouseStatusNo;
        $res['warehouseStatusSome'] = (int)$warehouseStatusSome;
        $res['warehouseStatusComplete'] = (int)$warehouseStatusComplete;
        return $res;
    }

    /**
     * 调拨入库单
     * @param int $shopId
     * @param array $search 搜索条件
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     */
    public function allocationBillListWarehouse(int $shopId, int $page, int $pageSize, array $search)
    {
        $where = "bill.dataFlag=1 and bill.status=3 and (inputShopId='{$shopId}' or outShopId='{$shopId}')";
        $whereFind = [];
        $whereFind['bill.number'] = function () use ($search) {
            if (empty($search['number'])) {
                return null;
            }
            return ['like', "%{$search['number']}%", 'and'];
        };
        $whereFind['bill.createTime'] = function () use ($search) {
            if (empty($search['startDate'])) {
                return null;
            }
            return ['between', "{$search['startDate']}' and '{$search['endDate']}", 'and'];
        };
        $whereFind['bill.warehouseStatus'] = function () use ($search) {
            if (!is_numeric($search['warehouseStatus'])) {
                return null;
            }
            return ['=', "{$search['warehouseStatus']}", 'and '];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = 'bill.allId,bill.number,bill.outShopId,bill.inputShopId,bill.status,bill.createTime,bill.billPic,bill.consigneeName,bill.warehouseUserId,bill.warehouseStatus';
        $field .= ',info.infoId,info.goodsId,info.skuId,info.num,info.warehouseNum,info.warehouseCompleteNum';
        $field .= ',goods.goodsName,goods.goodsStock,goods.goodsImg';
        $field .= ',inputShop.shopName as inputShopName,outShop.shopName as outShopName';
        $field .= ',user.name as warehouseUserName ';
        $sql = "select $field from __PREFIX__jxc_allocation_bill_info info ";
        $sql .= " left join __PREFIX__jxc_allocation_bill bill on bill.allId=info.allId ";
        $sql .= " left join __PREFIX__goods goods on goods.goodsId=info.goodsId ";
        $sql .= " left join __PREFIX__shops inputShop on inputShop.shopId=bill.inputShopId ";
        $sql .= " left join __PREFIX__shops outShop on outShop.shopId=bill.outShopId ";
        $sql .= " left join __PREFIX__user user on user.id=bill.warehouseUserId ";
        $sql .= " where $whereInfo ";
        $sql .= " order by bill.createTime desc ";
        $res = $this->pageQuery($sql, $page, $pageSize);
        if (empty($res['root'])) {
            return $res;
        }
        $list = $res['root'];
        $systemTab = M('sku_goods_system');
        foreach ($list as $key => $value) {
            $list[$key]['warehouseNoNum'] = $value['warehouseNum'] - $value['warehouseCompleteNum'];//剩余入库数量
            $list[$key]['dataType'] = $shopId == $value['inputShopId'] ? 1 : 2;//调入调出【1：调入|2：调出】
            $list[$key]['actionUserName'] = $value['inputShopName'];//制单人先用调入仓名称,因为目前只在门仓生成调拨单
            $list[$key]['skuSpecAttrStr'] = '';
            if ($value['skuId'] > 0) {
                $systemInfo = $systemTab->where(['skuId' => $value['skuId'], 'dataFlag' => 1])->find();
                if ($systemInfo) {
                    $list[$key]['goodsStock'] = $systemInfo['skuGoodsStock'];
                }
                //处理商品的sku信息
                $skuSelfList = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->group('specId')
                    ->order('sp.sort asc')
                    ->select();
                foreach ($skuSelfList as $selfVal) {
                    $list[$key]['skuSpecAttrStr'] .= $selfVal['attrName'] . '，';
                }
                $list[$key]['skuSpecAttrStr'] = rtrim($list[$key]['skuSpecAttrStr'], '，');
            }
            //$list[$key]['billCode'] = $this->billStatusToCode($value);
        }
        $res['root'] = $list;
        return $res;
    }

    /**
     * 更改调拨单入库单的入库数量
     * @param varchar $shopInfo
     * @param int allId 调拨单id
     * @param float $warehouseCompleteNum 入库数量
     */
    public function updateWarehouseCompleteNum($shopInfo, $allId, $warehouseCompleteNum)
    {
        $allocation_module = new AllocationBillModule();
        $shopId = $shopInfo['shopId'];
        $actionUserId = $shopInfo['user_id'];
        $actionUserName = $shopInfo['user_username'];
        $billInfo = $allocation_module->getAllocationBillDetail($allId);
        //$billInfo = $this->getAllocationBillDetail($allId);
        if ($billInfo['inputShopId'] != $shopId) {
            return returnData(false, -1, 'error', "本单据只有入库方[{$billInfo['inputShopName']}]才能操作入库");
        }
        if ($billInfo['status'] < 3) {
            return returnData(false, -1, 'error', '调出方未交货，暂不能入库');
        }
        if ($billInfo['warehouseStatus'] == 2) {
            return returnData(false, -1, 'error', '入库完成的数据不能再次入库');
        }
        $warehouseNoNum = $billInfo['warehouseNum'] - $billInfo['warehouseCompleteNum'];//剩余入库数量
        if ($warehouseCompleteNum <= 0) {
            return returnData(false, -1, 'error', '已完成入库，不能再次入库');
        }
        if ($warehouseCompleteNum > $warehouseNoNum) {
            return returnData(false, -1, 'error', '入库失败，本次最多入库' . $warehouseNoNum);
        }
        M()->startTrans();
        $saveData = array(
            'infoId' => $billInfo['infoId'],
            'warehouseCompleteNum' => bc_math($billInfo['warehouseCompleteNum'], $warehouseCompleteNum, 'bcadd', 3)
        );
        $info_save_res = $allocation_module->saveAllocationBillInfo($saveData, M());
        if ($info_save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', '入库失败');
        }
        $billSave = array(
            'allId' => $billInfo['allId'],
            'warehouseStatus' => 1,
        );
        $bill_save_res = $allocation_module->saveAllocationBill($billSave, M());
        if ($bill_save_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', '调拨单入库状态修改失败');
        }
        $erp_module = new ErpModule();
        //操作日志 start
        $logParams = array();
        $logParams['dataId'] = $allId;
        $logParams['dataType'] = 2;
        $logParams['action'] = "入库商品#{$billInfo['goodsName']} {$billInfo['skuSpecAttrStr']}数量" . $warehouseCompleteNum;
        $logParams['actionUserId'] = $actionUserId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        $logParams['status'] = 3;
        $logParams['warehouseStatus'] = 1;//部分入库
        $logParams['goodsId'] = $billInfo['goodsId'];
        $logParams['skuId'] = $billInfo['skuId'];
        $logParams['warehouseCompleteNum'] = $warehouseCompleteNum;
        $log_res = $erp_module->addBillActionLog($logParams, M());
        if (!$log_res) {
            M()->rollback();
            return returnData(false, -1, 'error', '调拨单日志记录失败');
        }
        //入库成功,增加商品库存
        $goods_module = new GoodsModule();
        //获取入库商品信息-start
        $where = array(
            'shopId' => $billInfo['inputShopId'],
            'goodsSn' => $billInfo['old_goods_sn'],
        );
        $input_goods_result = $goods_module->getGoodsInfoByParams($where, 'goodsId');
        if ($input_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', '入库商品信息有误');
        }
        $input_goods_id = $input_goods_result['data']['goodsId'];
        $input_sku_id = 0;
        if (!empty($billInfo['skuId'])) {
            $out_sku_result = $goods_module->getSkuSystemInfoById($billInfo['skuId']);//出库sku信息
            $where = array(
                'goodsId' => $input_goods_id,
                'skuBarcode' => $out_sku_result['data']['skuBarcode'],
            );
            $input_sku_result = $goods_module->getSkuSystemInfoByParams($where);
            if ($input_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(false, -1, 'error', '入库商品sku信息有误');
            }
            $input_sku_id = $input_sku_result['data']['skuId'];
        }
        $goods_id = $input_goods_id;
        $sku_id = $input_sku_id;
        //获取入库商品信息-end
        $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $warehouseCompleteNum, 1, 1, M());
        if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, -1, 'error', '库存修改失败');
        }
        $current_bill_info = $allocation_module->getAllocationBillDetail($allId);
        if ($current_bill_info['warehouseCompleteNum'] >= $current_bill_info['warehouseNum']) {
            //入库完成(全部入库)
            $billData = array(
                'allId' => $allId,
                'warehouseStatus' => 2
            );
            $bill_save_res = $allocation_module->saveAllocationBill($billData, M());
            if ($bill_save_res['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(false, -1, 'error', '入库状态更新失败');
            }
            //操作日志-start
            $logParams = array();
            $logParams['dataId'] = $allId;
            $logParams['dataType'] = 2;
            $logParams['action'] = "商品#{$billInfo['goodsName']} {$billInfo['skuSpecAttrStr']}入库完成";
            $logParams['actionUserId'] = $actionUserId;
            $logParams['actionUserType'] = $shopInfo['login_type'];
            $logParams['actionUserName'] = $actionUserName;
            $logParams['status'] = 3;
            $logParams['warehouseStatus'] = 2;
            $logParams['goodsId'] = $billInfo['goodsId'];
            $logParams['skuId'] = $billInfo['skuId'];
            $logParams['warehouseCompleteNum'] = 0;
            $log_res = $erp_module->addBillActionLog($logParams, M());
            if (!$log_res) {
                M()->rollback();
                return returnData(false, -1, 'error', '调拨单日志记录失败');
            }
            //操作日志-end
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 获取采购单列表
     * @param array $params [
     *                          int shopId:门店id
     *                          string number:单号
     *                          string actionUserName:制单人
     *                          int warehouseStatus 入库状态(0:待入库|1:部分入库|2:入库完成)
     *                          string startDate:开始时间
     *                          string endDate:结束时间
     *                          int export:导出(0:否|1:是)
     *                          int page:页码,默认为1
     *                          int pageSize:分页条数,默认为15
     *                  ]
     * @return array $data
     */
    public function getWarehouseList(array $params)
    {
        $page = $params['page'];
        $shopId = $params['shopId'];
        $pageSize = $params['pageSize'];
        $where = " p.dataFlag=1 and p.dataFrom IN(1,2) and p.shopId={$shopId} and status=1 and receivingStatus>0 ";
        $whereFind['p.number'] = function () use ($params) {
            if (empty($params['number'])) {
                return null;
            }
            return ['like', "%{$params['number']}%", 'and'];
        };
        $whereFind['p.actionUserName'] = function () use ($params) {
            if (empty($params['actionUserName'])) {
                return null;
            }
            return ['like', "%{$params['actionUserName']}%", 'and'];
        };
        $whereFind['p.warehouseStatus'] = function () use ($params) {
            if (!is_numeric($params['warehouseStatus'])) {
                return null;
            }
            return ['=', "{$params['warehouseStatus']}", 'and'];
        };
        $whereFind['p.createTime'] = function () use ($params) {
            if (empty($params['startDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select p.* from __PREFIX__jxc_purchase_order p left join wst_shops s on p.shopId=s.shopId where $whereInfo order by p.otpId desc ";
        if ($params['export'] != 1) {
            $rs = $this->pageQuery($sql, $page, $pageSize);
            if (!empty($rs['root'])) {
                $list = $rs['root'];
                foreach ($list as $key => $value) {
                    $list[$key]['warehouseNum'] = (float)$value['totalNum'];
                    $list[$key]['warehouseCompleteNum'] = (float)$this->countPurOrderWarehouseNum($value['otpId']);
                    $list[$key]['warehouseNoNum'] = (float)($value['totalNum'] - $list[$key]['warehouseCompleteNum']);
                }
                $rs['root'] = $list;
            }
        } else {
            $rs = $this->query($sql);
            $this->handlePurchaseOrderWarehouse($rs, $params);
        }
        return (array)$rs;
    }

    /**
     * 统计入库单已入库数量
     * @param int $otpId 采购单id
     * @param array $saveData
     * @return bool $res
     */
    public function countPurOrderWarehouseNum(int $otpId)
    {
        $model = M('jxc_purchase_order_info');
        $where['otpId'] = $otpId;
        $sum = $model->where($where)->sum('warehouseCompleteNum');
        return (float)$sum;
    }


    /**
     * 处理导出的数据格式
     * @param array $list PS:需要导出的采购单数据
     * @param array $params 前端传过来的一些参数
     * */
    public function handlePurchaseOrderWarehouse(array $list, array $params)
    {
        if (empty($list)) {
            $this->exportPurchaseData($list, $params);
        }
        $detail = [];
        foreach ($list as $key => $val) {
            $purInfoList = $this->getPurchaseOrderInfoListByOtpId($val['otpId'], $val['billType']);
            if (empty($purInfoList)) {
                continue;
            }
            foreach ($purInfoList as $infoKey => $infoVal) {
                $detail[] = $infoVal;
            }
        }
        foreach ($list as $key => $value) {
            $rowspan = 0;//后面的导出会用到
            $list[$key]['goods'] = [];
            foreach ($detail as $detailVal) {
                if ($detailVal['otpId'] == $value['otpId']) {
                    $list[$key]['goods'][] = $detailVal;
                }
            }
            $goods = $list[$key]['goods'];//该单下所包含的商品
            $skuList = [];//商品所包含的sku信息
            foreach ($goods as $gkey => $gval) {
                $goods[$gkey]['skuInfo'] = [];//sku详细信息
                if (!empty($gval['skuId'])) {
                    if ($value['billType'] == 1) {
                        $shopId = 0;
                    } else {
                        $shopId = $value['shopId'];
                    }
                    $skuInfo = $this->getSkuDetail($gval['skuId'], $shopId);
                    $skuInfo['goodsAttrName'] = '';
                    if (!empty($skuInfo['skuSpecAttr'])) {
                        $specAttrNameArr = [];
                        foreach ($skuInfo['skuSpecAttr'] as $skuVal) {
                            $specAttrNameArr[] = $skuVal['attrName'];
                        }
                        $skuInfo['goodsAttrName'] = implode('，', $specAttrNameArr);
                    }
                    if (!empty($skuInfo)) {
                        if ($skuInfo['skuId'] == $gval['skuId']) {
                            $skuInfo['unitPrice'] = $gval['unitPrice'];
                        }
                        $goods[$gkey]['skuInfo'] = $skuInfo;
                        $skuList[] = $skuInfo;
                    }
                }
            }
            unset($gval);
            $uniqueGoods = $this->uniqueData($goods, 'goodsId');
            foreach ($uniqueGoods as $uniqueKey => $uniqueVal) {
                unset($uniqueGoods[$uniqueKey]['infoId']);
                unset($uniqueGoods[$uniqueKey]['skuInfo']);
                $goodsNums = 0;//统计商品数量
                $goodsPrcie = 0;//统计商品价格
                //$uniqueGoods[$uniqueKey]['goodsNums'] = 0;//统计商品数量
                //$uniqueGoods[$uniqueKey]['goodsPrice'] = 0;//统计商品价格
                $uniqueGoods[$uniqueKey]['skulist'] = [];
                foreach ($goods as $gval) {
                    if ($gval['goodsId'] == $uniqueVal['goodsId']) {
                        $goodsNums += $gval['totalNum'];
                        $goodsPrcie += $gval['unitPrice'];
                        if (empty($gval['skuId'])) {
                            $rowspan += 1;
                            //无sku
                        } else {
                            //有sku
                            $uniqueGoods[$uniqueKey]['skulist'][] = $gval['skuInfo'];
                            $rowspan += 1;
                        }
                    }
                }
            }
            $list[$key]['goods'] = $uniqueGoods;
            $list[$key]['rowspan'] = $rowspan;
        }
        $this->exportPurchaseWarehouseData($list, $params);//导出需要采购的商品
    }

    /**
     * 导出需要采购单数据
     * @param array $goods PS:需要导出的采购单数据
     * @param array $params PS:前端传过来的一些参数
     * */
    public function exportPurchaseWarehouseData(array $list, array $params)
    {
        $param['startDate'] = $params['startDate'];
        $param['endDate'] = $params['endDate'];
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $nowDate = date('Y-m-d H:i:s');
        if (empty($startDate) || empty($endDate)) {
            //$startDate = $nowDate.'00:00:00';
            //$endDate = $nowDate.'23:59:59';
            $startDate = $nowDate;
            $endDate = $nowDate;
        }
        $date = $startDate . ' - ' . $endDate;
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:100px;'>单号</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>采购数量</th>
                <th style='width:50px;'>应入库数量</th>
                <th style='width:50px;'>已入库数量</th>
                <th style='width:50px;'>剩余入库数量</th>
                <!--<th style='width:50px;'>小计</th>-->
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>单据时间</th>
            </tr>";
        $num = 0;
        foreach ($list as $okey => $ovalue) {
            $rowspan = $ovalue['rowspan'];
            $orderGoods = $ovalue['goods'];
            $key = $okey + 1;
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                $countOrderGoods = count($orderGoods);
                if ($countOrderGoods > $rowspan) {
                    $rowspan = $countOrderGoods;
                }
                $num++;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }
                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['number'] . "</td>" .//单号
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//采购数量
                            "<td style='width:50px;' >" . $gVal['warehouseNum'] . "</td>" .//应入库数量
                            "<td style='width:50px;' >" . $gVal['warehouseCompleteNum'] . "</td>" .//已入库数量
                            "<td style='width:50px;' >" . ($gVal['warehouseNum'] - $gVal['warehouseCompleteNum']) . "</td>" .//剩余入库数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['remark'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//单据时间
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['goodsAttrName'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['number'] . "</td>" .//单号
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//采购数量
                            "<td style='width:50px;' >" . $gVal['warehouseNum'] . "</td>" .//应入库数量
                            "<td style='width:50px;' >" . $gVal['warehouseCompleteNum'] . "</td>" .//已入库数量
                            "<td style='width:50px;' >" . ($gVal['warehouseNum'] - $gVal['warehouseCompleteNum']) . "</td>" .//剩余入库数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['remark'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['unitPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                                    "<td style='width:50px;' >" . $gVal['warehouseNum'] . "</td>" .//应入库数量
                                    "<td style='width:50px;' >" . $gVal['warehouseCompleteNum'] . "</td>" .//已入库数量
                                    "<td style='width:50px;' >" . ($gVal['warehouseNum'] - $gVal['warehouseCompleteNum']) . "</td>" .//剩余入库数量
                                    //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['unitPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                            "<td style='width:50px;' >" . $gVal['warehouseNum'] . "</td>" .//应入库数量
                            "<td style='width:50px;' >" . $gVal['warehouseCompleteNum'] . "</td>" .//已入库数量
                            "<td style='width:50px;' >" . ($gVal['warehouseNum'] - $gVal['warehouseCompleteNum']) . "</td>" .//剩余入库数量
                            //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:50px;' >" . $skuVal['unitPrice'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $gVal['totalNum'] . "</td>" .//商品数量
                                "<td style='width:50px;' >" . $gVal['warehouseNum'] . "</td>" .//应入库数量
                                "<td style='width:50px;' >" . $gVal['warehouseCompleteNum'] . "</td>" .//已入库数量
                                "<td style='width:50px;' >" . ($gVal['warehouseNum'] - $gVal['warehouseCompleteNum']) . "</td>" .//剩余入库数量
                                //"<td style='width:50px;'>".$gVal['buyPirce'] * $gVal['totalNum']."</td>".//小计
                                "</tr>";
                        }
                    }
                }
            }
        }
        $headTitle = "采购入库单";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 更改采购单的状态
     * @param array $shopInfo
     * @param int $otpId 采购单id
     * @param int $rpId 分单id PS:后加,兼容分单
     * @param int $dataType 场景【1：场景1->采购方确认收货】
     * @param varchar $billPic 单据照片【场景1】
     * @param varchar $consigneeName 接货人姓名【场景1】
     */
    public function updatePurchaseStatus($shopInfo, $otpId, $rpId, $dataType, $billPic, $consigneeName)
    {
        //原有接口兼容分单,属于写法存在新旧格式
        $shopId = $shopInfo['shopId'];
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopId;
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $purTab = M('jxc_purchase_order');
        $erp_module = new ErpModule();
        $billInfo = $this->getPurchaseOrderDetail($otpId);
        if (empty($billInfo)) {
            return returnData(false, -1, 'error', '请输入正确的采购单id');
        }
        if ($billInfo['takenSupplier'] == 2 && empty($rpId)) {
            return returnData(false, -1, 'error', '请传入分单id');
        }
        if ($dataType == 1) {
            //采购方确认收货
            if ($shopId != $billInfo['shopId']) {
                return returnData(false, -1, 'error', '只有采购方才有权限操作确认收货操作');
            }
            if ($billInfo['status'] != 1) {
                return returnData(false, -1, 'error', '采购单未审核通过，暂不能执行该操作');
            }
            if (!in_array($billInfo['receivingStatus'], array(0, 1))) {
                return returnData(false, -1, 'error', '已收货完成的单据不能重复收货');
            }
            $saveData = array(
                'otpId' => $otpId
            );
            if ($billInfo['takenSupplier'] == 1) {
                //总仓供货不存在分单
                $saveData['receivingStatus'] = 2;//收货状态(0:采购方待收货 1:采购方部分收货 2:采购方收货完成)
                $saveData['billPic'] = $billPic;
                $saveData['consigneeName'] = $consigneeName;

                //报表备参
                $procureOrderList = [];
                foreach ($billInfo['goodsInfo'] as $v) {
                    $goodsList = [];
                    $goodsList['goodsId'] = $v['goodsId'];
                    $goodsList['sellPrice'] = $v['sellPrice'];
                    $goodsList['goodsCatId1'] = $v['goodsCatId1'];
                    $goodsList['goodsCatId2'] = $v['goodsCatId2'];
                    $goodsList['goodsCatId3'] = $v['goodsCatId3'];
                    $goodsList['unitPrice'] = $v['unitPrice'];
                    $goodsList['createTime'] = $billInfo['createTime'];
                    $goodsList['totalNum'] = $v['totalNum'];
                    $goodsList['totalAmount'] = $v['totalAmount'];
                    if (!empty($v['sku'])) {
                        $goodsList['sellPrice'] = $v['sku']['sellPrice'];
                    }
                    $procureOrderList[] = $goodsList;
                }
                $dataAmount = $billInfo['dataAmount'];//单据总金额

            } else {
                //供应商供货存在分单
                $saveData['receivingStatus'] = 1;
                $reinsurance_detail = $erp_module->getReinsuranceDetailById($rpId);
                if ($reinsurance_detail['status'] != 5) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "分单{$reinsurance_detail['number']}未发货，不能确认收货");
                }
                if ($reinsurance_detail['receivingStatus'] != 0) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "分单{$reinsurance_detail['number']}已确认收货，不能重复收货");
                }
                $procureOrderList = $reinsurance_detail['reinsuranceGoods'];//分单商品信息
                $dataAmount = $reinsurance_detail['dataAmount'];//分单金额
            }
        }
        M()->startTrans();
        //$res = $purTab->where(array('otpId' => $otpId))->save($saveData);
        $save_pur_res = $erp_module->savePurchaseOrder($saveData, M());
        if (!$save_pur_res) {
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }
        if ($billInfo['takenSupplier'] == 2) {
            $updateParam = array(
                'rpId' => $rpId,
                'status' => 3,//3:已送达
                'receivingStatus' => 1,//已收货
                'billPic' => $billPic,//单据照片
                'consigneeName' => $consigneeName,//收货人姓名
            );
            $reinsurance_res = $erp_module->saveReinsurance($updateParam, M());
            if (!$reinsurance_res) {
                M()->rollback();
                return returnData(false, -1, 'error', "分单#{$reinsurance_detail['number']}确认收货失败");
            }
        }
        //添加操作日志
        $logParams = [];
        $logParams['dataId'] = $otpId;
        $logParams['dataType'] = 1;
        $logParams['actionUserId'] = $actionUserId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        if ($dataType == 1) {
            $logParams['action'] = '采购方已确认收货完成';
            if ($billInfo['takenSupplier'] == 2) {
                $logParams['action'] = "分单#{$reinsurance_detail['number']}已确认收货";
            }
            $logParams['status'] = $billInfo['status'];

            //添加报表
            $addReportParams = [];//准备参数
            $addReportParams['shopId'] = $billInfo['shopId'];
            $addReportParams['otpId'] = $billInfo['otpId'];
            $addReportParams['procureOrderList'] = $procureOrderList;
            $addReportParams['dataAmount'] = $dataAmount;//供应商到分仓或总仓到分仓的单据总金额
            addReportForms($orderId = 0, $reportType = 4, $addReportParams, M());
        }
        $log_res = $erp_module->addBillActionLog($logParams, M());
        if (!$log_res) {
            M()->rollback();
            return returnData(false, -1, 'error', "确认收货失败");
        }
        if ($billInfo['takenSupplier'] == 2) {
            //分单如果全部确认收货则更改主单据的状态为收货完成
            $is_receiving_complete = $erp_module->isPurchaseReceivingComplete($otpId);
            if ($is_receiving_complete) {
                $pur_save = array(
                    'otpId' => $otpId,
                    'receivingStatus' => 2,
                );
                $complete_res = $erp_module->savePurchaseOrder($pur_save, M());
                if (!$complete_res) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "分单{$reinsurance_detail['number']}确认收货失败");
                }
                $logParams['action'] = "采购方已确认收货完成";
                $log_res = $erp_module->addBillActionLog($logParams, M());
                if (!$log_res) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "确认收货失败");
                }
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 修改采购入库单的入库数量
     * @param array $params [
     *                          int otpId:采购单id
     *                          array detail:入库商品明细
     *                      ]
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function updatePurchaseWarehouseNum(array $params, array $shopInfo)
    {
        //###################################################原有的方法就不重构或重新封装了，就这样吧#########################################################
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $shopId = $shopInfo['shopId'];
        M()->startTrans();
        $locationGoodsTab = M('goods');
        $locationSystemTab = M('sku_goods_system');
        $otpId = $params['otpId'];
        $where = [];
        $where['otpId'] = $otpId;
        $where['dataFlag'] = 1;
        $purchaseOrderInfo = $this->getPurchaseOrderDetail($otpId);
        $totalNum = $purchaseOrderInfo['totalNum'];//采购单采购商品总量
        $purTab = M('jxc_purchase_order');
        $infoTab = M('jxc_purchase_order_info');
        if (!$purchaseOrderInfo) {
            M()->rollback();
            return returnData(null, -1, 'error', '无效的采购单id');
        }
        if ($purchaseOrderInfo['status'] < 1) {
            M()->rollback();
            return returnData(null, -1, 'error', '审核未通过的采购单不能入库');
        }
        if ($purchaseOrderInfo['warehouseStatus'] == 2) {
            //已经完成审核的采购单就不能编辑了
            M()->rollback();
            return returnData(null, -1, 'error', '入库完成的采购单不能再次入库');
        }
        $warehouseGoods = $purchaseOrderInfo['goodsInfo'];
        $erp_module = new ErpModule();
        $goods_module = new GoodsModule();
        $goods_model = new GoodsModel();
        $sku_system_model = new SkuGoodsSystemModel();
        if (!empty($params['detail'])) {
            $detail = $params['detail'];
            foreach ($detail as $dkey => $dval) {
                $dval['current_warehouse_num'] = (float)$dval['current_warehouse_num'];
                foreach ($warehouseGoods as $wkey => $wval) {
                    if ($dval['goodsId'] != $wval['goodsId']) {
                        continue;
                    }
                    $dskuData = $dval['sku'];//前端传过来的sku信息
                    $wskuData = $wval['sku'];//采购单中的sku信息
                    if (!empty($dskuData) && empty($wskuData)) {
                        M()->rollback();
                        return returnData(null, -1, 'error', '请检查参数是否有误');
                    }
                    $where = [];
                    $where['goodsSn'] = $wval['goodsSn'];
                    $where['goodsFlag'] = 1;
                    $where['shopId'] = $shopId;
                    $locationGoodsInfo = $locationGoodsTab->where($where)->find();
                    if (empty($locationGoodsInfo)) {
                        continue;
                    }
                    $goodsId = $locationGoodsInfo['goodsId'];
                    if (empty($dskuData)) {
                        if ($wval['warehouseNoNum'] <= 0) {
                            continue;
                        }
                        if ($dval['current_warehouse_num'] <= 0 && $wval['warehouseNoNum'] > 0) {
                            M()->rollback();
                            return returnData(null, -1, 'error', "商品#{$wval['goodsName']}入库数量必须大于0");
                        }
                        if ($dval['current_warehouse_num'] > $wval['warehouseNoNum']) {//前端字段混淆,后端直接更换字段,不想bb,,,,,添加时候的字段竟然和编辑的字段传的不一样
                            M()->rollback();
                            return returnData(null, -1, 'error', "商品#{$wval['goodsName']}本次最多入库数量为{$wval['warehouseNoNum']}");
                        }
                        $where = [];
                        $where['otpId'] = $otpId;
                        $where['goodsId'] = (int)$wval['goodsId'];
                        $purInfo = $infoTab->where($where)->find();
                        $where = [];
                        $where['infoId'] = $purInfo['infoId'];
//                        $saveData = [];
//                        $saveData['warehouseCompleteNum'] = $purInfo['warehouseCompleteNum'] + $dval['current_warehouse_num'];//修改入库数量
//                        $updateRes = $infoTab->where($where)->save($saveData);
                        $updateRes = $infoTab->where($where)->setInc('warehouseCompleteNum', $dval['current_warehouse_num']);
                        if (!$updateRes) {
                            M()->rollback();
                            return returnData(null, -1, 'error', "商品#{$wval['goodsName']}入库失败");
                        }
                        $goodsUpdate = array(
                            'goodsUnit' => $purInfo['unitPrice'],
                            //'goodsStock' => (float)($locationGoodsInfo['goodsStock'] + $dval['current_warehouse_num']),
                        );
                        $goods_model->where(array('goodsId' => $goodsId))->save($goodsUpdate);
                        $stock_res = $goods_module->returnGoodsStock($goodsId, 0, $dval['current_warehouse_num'], 1, 1, M());
                        if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                            M()->rollback();
                            return returnData(null, -1, 'error', "商品#{$wval['goodsName']}库存修改失败");
                        }
                        //操作日志 start
                        $logParams = [];
                        $logParams['dataId'] = $otpId;
                        $logParams['dataType'] = 1;
                        $logParams['action'] = "商品#{$locationGoodsInfo['goodsName']}入库数量{$dval['current_warehouse_num']}";
                        $logParams['actionUserId'] = $actionUserId;
                        $logParams['actionUserType'] = $shopInfo['login_type'];
                        $logParams['actionUserName'] = $actionUserName;
                        $logParams['status'] = 0;
                        $logParams['warehouseStatus'] = 1;
                        $logParams['goodsId'] = $wval['goodsId'];
                        $logParams['warehouseCompleteNum'] = $dval['current_warehouse_num'];
                        $log_res = $erp_module->addBillActionLog($logParams, M());
                        if (!$log_res) {
                            M()->rollback();
                            return returnData(null, -1, 'error', "商品#{$wval['goodsName']}入库日志记录失败");
                        }
                        //$this->addBillActionLog($logParams);
                        //操作日志 end
                    }
                    if (!empty($dskuData)) {
                        //有sku
                        foreach ($dskuData as $dskuKey => $dskuVal) {
                            $dskuVal['current_warehouse_num'] = (float)$dskuVal['current_warehouse_num'];
                            foreach ($wskuData as $wskuKey => $wskuVal) {
                                if ($dskuVal['skuId'] == $wskuVal['skuId']) {
//                                    if($dskuVal['warehouseNum'] > $wskuVal['warehouseNoNum']){
//                                        return returnData(null,-1,'error',"商品【{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}】本次最多入库数量为{$wskuVal['warehouseNoNum']}");
//                                    }
                                    if ($wskuVal['warehouseNoNum'] <= 0) {
                                        continue;
                                    }
                                    if ($dskuVal['current_warehouse_num'] <= 0 && $wskuVal['warehouseNoNum'] > 0) {
                                        M()->rollback();
                                        return returnData(null, -1, 'error', "商品#{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}入库数量必须大于0");
                                    }
                                    if ($dskuVal['current_warehouse_num'] > $wskuVal['warehouseNoNum']) {
                                        M()->rollback();
                                        return returnData(null, -1, 'error', "商品#{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}本次最多入库数量为{$wskuVal['warehouseNoNum']}");
                                    }
                                    $where = [];
                                    $where['goodsId'] = $goodsId;
                                    $where['skuBarcode'] = $wskuVal['skuBarcode'];
                                    $where['dataFlag'] = 1;
                                    $locationSystemInfo = $locationSystemTab->where($where)->find();
                                    if (empty($locationSystemInfo)) {
                                        continue;
                                    }
                                    $where = [];
                                    $where['otpId'] = $otpId;
                                    $where['goodsId'] = (int)$wskuVal['goodsId'];
                                    $where['skuId'] = (int)$wskuVal['skuId'];
                                    $purInfo = $infoTab->where($where)->find();
                                    $where = [];
                                    $where['infoId'] = $purInfo['infoId'];
                                    $saveData = [];
//                                    $saveData['warehouseCompleteNum'] = bc_math($purInfo['warehouseCompleteNum'], $dskuVal['current_warehouse_num'], 'bcadd', 2);//修改入库数量
//                                    $updateRes = $infoTab->where($where)->save($saveData);
                                    $updateRes = $infoTab->where($where)->setInc('warehouseCompleteNum', $dskuVal['current_warehouse_num']);
                                    if (!$updateRes) {
                                        M()->rollback();
                                        return returnData(null, -1, 'error', "商品#{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}入库失败");
                                    }
                                    //操作日志 start
                                    $logParams = [];
                                    $logParams['dataId'] = $otpId;
                                    $logParams['dataType'] = 1;
                                    $logParams['action'] = "商品#{$locationGoodsInfo['goodsName']} {$wskuVal['skuSpecAttrStr']}入库数量{$dskuVal['current_warehouse_num']}";
                                    $logParams['actionUserId'] = $actionUserId;
                                    $logParams['actionUserType'] = $shopInfo['login_type'];
                                    $logParams['actionUserName'] = $actionUserName;
                                    $logParams['status'] = 0;
                                    $logParams['warehouseStatus'] = 1;
                                    $logParams['goodsId'] = $wskuVal['goodsId'];
                                    $logParams['goodsId'] = $wskuVal['skuId'];
                                    $logParams['warehouseCompleteNum'] = $dskuVal['current_warehouse_num'];
                                    $log_res = $erp_module->addBillActionLog($logParams, M());
                                    if (!$log_res) {
                                        M()->rollback();
                                        return returnData(null, -1, 'error', "商品#{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}入库日志记录失败");
                                    }
                                    //$this->addBillActionLog($logParams);
                                    //操作日志 end
                                    $where = [];
                                    $where['skuId'] = $locationSystemInfo['skuId'];
//                                    $saveData = [];
//                                    $saveData['skuGoodsStock'] = $locationSystemInfo['skuGoodsStock'] + $dskuVal['current_warehouse_num'];
//                                    $locationSystemTab->where($where)->save($saveData);//更新sku库存
                                    $skuSystemUpdate = array(
                                        'purchase_price' => $purInfo['unitPrice'],
//                                        'skuGoodsStock' => (float)($locationSystemInfo['skuGoodsStock'] + $dskuVal['current_warehouse_num']),
                                    );
                                    $sku_system_model->where(array('skuId' => $locationSystemInfo['skuId']))->save($skuSystemUpdate);
                                    $stock_res = $goods_module->returnGoodsStock($dskuVal['goodsId'], $locationSystemInfo['skuId'], $dskuVal['current_warehouse_num'], 1, 1, M());
                                    if ($stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                                        M()->rollback();
                                        return returnData(null, -1, 'error', "商品#{$wval['goodsName']} {$wskuVal['skuSpecAttrStr']}库存修改失败");
                                    }
//                                    $locationSystemTab->where($where)->setInc('skuGoodsStock', $dskuVal['current_warehouse_num']);//更新sku库存
//                                    $where = [];
//                                    $where['goodsId'] = $goodsId;
//                                    $saveData = [];
//                                    $saveData['goodsStock'] = $locationGoodsInfo['goodsStock'] + $dskuVal['current_warehouse_num'];
//                                    $locationGoodsTab->where($where)->save($saveData);//更新商品库存
//                                    $locationGoodsTab->where($where)->setInc('goodsStock', $dskuVal['current_warehouse_num']);//更新商品库存
                                }
                            }
                        }
                    }
                }
            }
            $saveData = [];
            $saveData['warehouseStatus'] = 1;
            $purTab->where(['otpId' => $otpId])->save($saveData);
            $nowTotalNum = $infoTab->where(['otpId' => $otpId])->sum('warehouseCompleteNum');
            if ($nowTotalNum >= $totalNum) {
                //入库完成更改采购单入库状态
                $saveData = [];
                $saveData['warehouseStatus'] = 2;
                $save_res = $purTab->where(['otpId' => $otpId])->save($saveData);
                if (!$save_res) {
                    M()->rollback();
                    return returnData(null, -1, 'error', "入库单状态修改失败");
                }
                //操作日志 start
                $logParams = [];
                $logParams['dataId'] = $otpId;
                $logParams['dataType'] = 1;
                $logParams['action'] = "采购单#{$purchaseOrderInfo['number']}入库完成";
                $logParams['actionUserId'] = $actionUserId;
                $logParams['actionUserType'] = $shopInfo['login_type'];
                $logParams['actionUserName'] = $actionUserName;
                $logParams['warehouseStatus'] = 2;
                $logParams['warehouseCompleteNum'] = 0;
                //$this->addBillActionLog($logParams);
                $erp_module->addBillActionLog($logParams, M());
                //操作日志 end
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 手动创建采购入库单，注意该单据中的商品明细调用的是当前门店的商品信息
     * @param array $params [
     *                          int shopId:门店id
     *                          string remark:备注
     *                          array detail:采购商品详情
     *                          array billType:单据类型(1:采购单转为入库单|2:手动创建入库单)
     *                      ]
     * @param array $shopInfo
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function addPurchaseOrderWarehouse(array $params, array $shopInfo)
    {
        $actionUserId = !empty($shopInfo['id']) ? $shopInfo['id'] : $shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id']) ? $shopInfo['name'] : $shopInfo['shopName'];
        $shopId = $shopInfo['shopId'];
        M()->startTrans();
        $orderno = $this->addOrderno();//生成订单号
        // 启动事务
        $order = [];
        //$order['number'] = "CGD" . $orderno;
        $order['shopId'] = $shopInfo['shopId'];
        $order['actionUserId'] = $actionUserId;
        $order['actionUserName'] = $actionUserName;
        $order['shopName'] = $shopInfo['shopName'];
        $order['dataFrom'] = $shopInfo['login_type'];
        $order['status'] = 1;
        $order['receivingStatus'] = 1;
        $order['billType'] = 2;
        $order['remark'] = $params['remark'];
        $order['createTime'] = date('Y-m-d H:i:s');
        $otpId = M('jxc_purchase_order')->add($order);//添加采购单主表信息
        $locationGoodsTab = M('goods');
        $locationSystemTab = M('sku_goods_system');
        if (!$otpId) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购入库单创建失败');
        }
        $number = 'CGD' . date('Ymd') . str_pad($otpId, 10, "0", STR_PAD_LEFT);
        //操作日志 start
        $logParams = [];
        $logParams['dataId'] = $otpId;
        $logParams['dataType'] = 1;
        $logParams['action'] = "采购方手动创建采购入库单#{$number}";
        $logParams['actionUserId'] = $actionUserId;
        $logParams['actionUserType'] = $shopInfo['login_type'];
        $logParams['actionUserName'] = $actionUserName;
        $logParams['status'] = 1;
        $logParams['warehouseStatus'] = 0;
        $this->addBillActionLog($logParams);
        //操作日志 end
        //添加采购明细
        if (!empty($params['detail'])) {
            $detail = $params['detail'];
            $sumTotalNum = [];//统计采购商品的总数量
            $sumDataAmount = [];//统计采购商品的金额
            foreach ($detail as $key => $value) {
                $where = [];
                $where['goodsId'] = $value['goodsId'];
                $where['shopId'] = $shopInfo['shopId'];
                $locationGoodsInfo = $locationGoodsTab->where($where)->find();
                if (!$locationGoodsInfo) {
                    return returnData(null, -1, 'error', '请检查商品明细是否正确');
                }
                $goodsId = $locationGoodsInfo['goodsId'];
                if (empty($value['sku'])) {
                    $value['warehouseCompleteNum'] = $value['warehouseNum'];//应前端要求使用warehouseNum字段
                    //处理没有sku的情况
                    $handleSku = [];
                    $handleSku['skuId'] = 0;
                    $handleSku['remark'] = '';
                    $handleSku['warehouseCompleteNum'] = $value['warehouseNum'];
                    $handleSku['warehouseNum'] = $value['warehouseNum'];
                    if (empty($value['totalNum'])) {
                        return returnData(null, -1, 'error', '请输入商品[' . $locationGoodsInfo['goodsName'] . ']正确的采购数量');
                    }
                    if (empty($value['warehouseCompleteNum']) || $value['warehouseCompleteNum'] > $value['totalNum']) {
                        return returnData(null, -1, 'error', '请输入商品[' . $locationGoodsInfo['goodsName'] . ']正确的入库数量');
                    }
                    $handleSku['totalNum'] = $value['totalNum'];
                    if (!empty($value['remark'])) {
                        $handleSku['remark'] = $value['remark'];
                    }
                    $handleSku['warehouseCompleteNum'] = $value['warehouseCompleteNum'];
                    $value['sku'][] = $handleSku;
                }
                $res = [];
                foreach ($value['sku'] as $skuVal) {
                    if (isset($res[$skuVal['skuId']])) {
                        unset($skuVal['skuId']);
                    } else {
                        $res[$skuVal['skuId']] = $skuVal;
                    }
                }
                //$res = array_values($res);
                $oldCount = count($value['sku']);
                $newCount = count($res);
                if ($newCount < $oldCount) {
                    return returnData(null, -1, 'error', '商品[' . $locationGoodsInfo['goodsName'] . ']信息有误，请检查');
                }
                $price = $locationGoodsInfo['shopPrice'];
                foreach ($value['sku'] as $v) {
                    $v['warehouseCompleteNum'] = $v['warehouseNum'];
                    $skuId = $v['skuId'];
                    if ($skuId > 0) {
                        $skuInfo = $this->getSkuDetail($skuId, $shopId);
                        if (!empty($skuInfo['skuId']) && $skuInfo['goodsId'] == $goodsId) {
                            $price = $skuInfo['skuShopPrice'];
                        } else {
                            return returnData(null, -1, 'error', '商品[' . $locationGoodsInfo['goodsName'] . ']的skuId不匹配,错误的skuId' . ":" . $skuId);
                        }
                    }
                    if ($v['totalNum'] <= 0) {
                        return returnData(null, -1, 'error', '采购数量必须大于0');
                    }
                    if ($v['warehouseCompleteNum'] <= 0 || $v['warehouseCompleteNum'] > $v['totalNum']) {
                        return returnData(null, -1, 'error', "请输入商品#{$locationGoodsInfo['goodsName']}正确的入库数量");
                    }
                    //添加采购单明细
                    $info = [];
                    $info['otpId'] = $otpId;
                    $info['goodsId'] = $goodsId;
                    $info['skuId'] = $skuId;
                    $info['remark'] = !empty($v['remark']) ? $v['remark'] : '';
                    $info['unitPrice'] = $price;
                    $info['totalNum'] = $v['totalNum'];
                    $info['totalAmount'] = $v['totalNum'] * $price;
                    $info['warehouseNum'] = $v['totalNum'];
                    $info['warehouseCompleteNum'] = $v['warehouseCompleteNum'];
                    $infoId = $this->addPurchaseOrderInfo($info);
                    if (!$infoId) {
                        M()->rollback();
                        return returnData(null, -1, 'error', '添加采购商品明细失败');
                    }
                    //入库成功更新商品库存
                    $where = [];
                    $where['goodsId'] = $goodsId;
                    $saveData = [];
                    $saveData['goodsStock'] = $locationGoodsInfo['goodsStock'] + $v['warehouseCompleteNum'];
                    $locationGoodsTab->where($where)->save($saveData);
                    //入库成功更新商品sku库存
                    if ($skuId > 0) {
                        $where = [];
                        $where['skuId'] = $skuId;
                        $saveData = [];
                        $saveData['skuGoodsStock'] = $skuInfo['skuGoodsStock'] + $v['warehouseCompleteNum'];
                        $locationSystemTab->where($where)->save($saveData);
                    }
                    //操作日志 start
                    $logParams = [];
                    $logParams['dataId'] = $otpId;
                    $logParams['dataType'] = 1;
                    $logParams['action'] = "商品#{$locationGoodsInfo['goodsName']}入库数量{$v['warehouseCompleteNum']}";
                    $logParams['actionUserId'] = $actionUserId;
                    $logParams['actionUserType'] = $shopInfo['login_type'];
                    $logParams['actionUserName'] = $actionUserName;
                    $logParams['status'] = 1;
                    $logParams['warehouseStatus'] = 1;
                    $logParams['goodsId'] = $locationGoodsInfo['goodsId'];
                    $logParams['skuId'] = $skuId;
                    $logParams['warehouseCompleteNum'] = $v['warehouseCompleteNum'];
                    $this->addBillActionLog($logParams);
                    //操作日志 end

                    $sumDataAmount[] = $price * $v['totalNum'];
                    $sumTotalNum[] = $v['totalNum'];
                    $sumTowarehouseCompleteNum[] = $v['warehouseCompleteNum'];
                }
            }
            //更新采购单
            $update = [];
            $update['otpId'] = $otpId;
            $update['totalNum'] = array_sum($sumTotalNum);
            $update['dataAmount'] = array_sum($sumDataAmount);
            $update['warehouseStatus'] = 1;//部分入库
            $update['number'] = $number;
            $warehouseCompleteNum = array_sum($sumTowarehouseCompleteNum);
            if ($warehouseCompleteNum == $update['totalNum']) {
                $update['warehouseStatus'] = 2;//入库完成
            }
            $res = $this->updatePurchaseOrder($update);
            if (!$res) {
                M()->rollback();
                return returnData(null, -1, 'error', '操作失败');
            }
            if ($update['warehouseStatus'] == 2) {
                //操作日志 start
                $logParams = [];
                $logParams['dataId'] = $otpId;
                $logParams['dataType'] = 1;
                $logParams['action'] = "采购入库单#{$number}入库完成";
                $logParams['actionUserId'] = $actionUserId;
                $logParams['actionUserType'] = $shopInfo['login_type'];
                $logParams['actionUserName'] = $actionUserName;
                $logParams['status'] = 1;
                $logParams['warehouseStatus'] = 2;
                $this->addBillActionLog($logParams);
                //操作日志 end
            }
        }
        M()->commit();
        return returnData(true);
    }

    //产品经理调整后 end

    /*&
     * 导入erp商品
     * @param int $shopId 门店id
     * @param string $goodsIds 商品id,多个用英文逗号分隔,-1为导入全部商品
     * */
    public function importErpGoods(int $shopId, string $goodsIds)
    {
        $erpGoodsTab = M('jxc_goods');
        if ($goodsIds == -1) {
            //导入全部
            $where = [];
            $where['dataFlag'] = 1;
            $where['examineStatus'] = 1;
            $erpGoodsCount = $erpGoodsTab->where($where)->count();
            if ($erpGoodsCount <= 0) {
                return returnData(false, -1, 'error', '暂无可导入的商品数据');
            }
            $pageSize = 100;
            $pageCount = ceil($erpGoodsCount / $pageSize);
            for ($i = 1; $i <= $pageCount; $i++) {
                $page = $i;
                $where = [];
                $where['dataFlag'] = 1;
                $where['examineStatus'] = 1;
                $erpGoodsList = $erpGoodsTab
                    ->where($where)
                    ->field('goodsId,goodsSn')
                    ->order('goodsId desc')
                    ->limit(($page - 1) * $pageSize, $pageSize)
                    ->select();
                $this->syncShopGoods($erpGoodsList, $shopId);
            }
        } else {
            //导入部分商品
            $goodsIdsArr = explode(',', $goodsIds);
            $where = [];
            $where['goodsId'] = ['IN', $goodsIdsArr];
            $where['dataFlag'] = 1;
            $where['examineStatus'] = 1;
            $erpGoodsList = $erpGoodsTab
                ->where($where)
                ->field('goodsId,goodsSn')
                ->order('goodsId desc')
                ->select();
            $erpGoodsList = (array)$erpGoodsList;
            if (empty($erpGoodsList)) {
                return returnData(false, -1, 'error', '暂无可导入的商品数据');
            }
            $this->syncShopGoods($erpGoodsList, $shopId);
        }
        return returnData(true);
    }

    /*
     * 导入商品数据
     * @param array $erpGoodsList
     * @param int $shopId
     * */
    public function syncShopGoods($erpGoodsList, $shopId)
    {
        $goodsTab = M('goods');
        $syncGoodsInsert = [];
        foreach ($erpGoodsList as &$value) {
            $erpGoodsId = $value['goodsId'];
            $goodsDetail = $this->getErpGoodsDetail($erpGoodsId);
            $value['detail'] = $goodsDetail;
            $syncGoods = [];//构建商品信息基本参数
            //同步商品商城分类
            $goodsCatIdInfo = $this->syncErpGoodsCat($goodsDetail);
            $syncGoods['goodsCatId1'] = $goodsCatIdInfo['goodsCatId1'];
            $syncGoods['goodsCatId2'] = $goodsCatIdInfo['goodsCatId2'];
            $syncGoods['goodsCatId3'] = $goodsCatIdInfo['goodsCatId3'];
            //同步商品店铺分类
            $goodsShopCatIdInfo = $this->syncErpGoodsShopCat($shopId, $goodsDetail);
            $syncGoods['shopCatId1'] = $goodsShopCatIdInfo['shopCatId1'];
            $syncGoods['shopCatId2'] = $goodsShopCatIdInfo['shopCatId2'];
            //店铺是否已存在该商品
            $shopGoodsParams = [];
            $shopGoodsParams['shopId'] = $shopId;
            $shopGoodsParams['goodsSn'] = $goodsDetail['goodsSn'];
            $shopGoodsDetail = $this->getShopGoodsDetailByParams($shopGoodsParams);
            $syncGoods['goodsSn'] = $goodsDetail['goodsSn'];
            $syncGoods['goodsName'] = $goodsDetail['goodsName'];
            $syncGoods['goodsImg'] = $goodsDetail['goodsImg'];
            $syncGoods['goodsThums'] = $goodsDetail['goodsImg'];//总仓商品没有缩略图,有也是错误的,所以这里直接用商品封面图代替
            $syncGoods['shopId'] = $shopId;
            $syncGoods['marketPrice'] = $goodsDetail['retailPrice'];//市场价
            $syncGoods['shopPrice'] = $goodsDetail['retailPrice'];//店铺价
            $syncGoods['goodsUnit'] = $goodsDetail['sellPrice'];//进货价
            //$syncGoods['goodsStock'] = $goodsDetail['stock'];
            $syncGoods['isSale'] = 0;
            $syncGoods['goodsStatus'] = 1;
            $syncGoods['goodsDesc'] = $goodsDetail['goodsDetail'];
            $syncGoods['SuppPriceDiff'] = $goodsDetail['standardProduct'];
            $syncGoods['weightG'] = $goodsDetail['weightG'];
            $syncGoods['goodsCompany'] = $goodsDetail['unitName'];
            $syncGoods['unit'] = $goodsDetail['unitName'];//单位
            $syncGoods['goodsSpec'] = '无简介';
            if (empty($shopGoodsDetail)) {
                //新增
                $syncGoods['createTime'] = date('Y-m-d H:i:s', time());
                $syncGoodsInsert[] = $syncGoods;
            } else {
                //更新
                $where = [];
                $where['goodsId'] = $shopGoodsDetail['goodsId'];
                $goodsTab->where($where)->save($syncGoods);
            }
        }
        unset($value);
        //同步商品基本信息
        if (!empty($syncGoodsInsert)) {
            $insertRes = $goodsTab->addAll($syncGoodsInsert);
            if (!$insertRes) {
                return returnData(false, -1, 'error', '导入商品失败');
            }
        }

        $gallerysTab = M('goods_gallerys');
        $skuSpecTab = M('sku_spec');
        $skuAttrTab = M('sku_spec_attr');
        $skuSystemTab = M('sku_goods_system');
        $skuSelfTab = M('sku_goods_self');
        $gallerysInsert = [];
        foreach ($erpGoodsList as $value) {
            $erpGoodsDetail = $value['detail'];
            $goodsParams = [];
            $goodsParams['goodsSn'] = $erpGoodsDetail['goodsSn'];
            $goodsParams['shopId'] = $shopId;
            $shopGoodsDetail = $this->getShopGoodsDetailByParams($goodsParams);
            if (empty($shopGoodsDetail)) {
                continue;
            }
            if (!empty($erpGoodsDetail['skuGoodsSystem'])) {
                $erpSkuList = $erpGoodsDetail['skuGoodsSystem'];
                foreach ($erpSkuList as $skuKey => $erpSkuVal) {
                    $skuSpecAttr = $erpSkuVal['skuSpecAttr'];
                    foreach ($skuSpecAttr as $specAttrKey => &$specAttrVal) {
                        $specAttrVal['shopSpecId'] = 0;
                        $specAttrVal['shopAttrId'] = 0;
                        //规格名称
                        $where = [];
                        $where['shopId'] = $shopId;
                        $where['specName'] = $specAttrVal['specName'];
                        $where['dataFlag'] = 1;
                        $shopSkuSpecInfo = $skuSpecTab->where($where)->find();
                        if (empty($shopSkuSpecInfo)) {
                            $specInsert = [];
                            $specInsert['specName'] = $specAttrVal['specName'];
                            $specInsert['shopId'] = $shopId;
                            $specInsert['sort'] = $specAttrVal['specSort'];
                            $specInsert['addTime'] = date('Y-m-d H:i:s', time());
                            $specAttrVal['shopSpecId'] = $skuSpecTab->add($specInsert);
                        } else {
                            $specAttrVal['shopSpecId'] = $shopSkuSpecInfo['specId'];
                        }
                        //规格值
                        $where = [];
                        $where['specId'] = $specAttrVal['shopSpecId'];
                        $where['attrName'] = $specAttrVal['attrName'];
                        $where['dataFlag'] = 1;
                        $shopSkuAttrInfo = $skuAttrTab->where($where)->find();
                        if (empty($shopSkuAttrInfo)) {
                            $specInsert = [];
                            $specInsert['specId'] = $specAttrVal['shopSpecId'];
                            $specInsert['attrName'] = $specAttrVal['attrName'];
                            $specInsert['sort'] = $specAttrVal['attrSort'];
                            $specInsert['addTime'] = date('Y-m-d H:i:s', time());
                            $specAttrVal['shopAttrId'] = $skuAttrTab->add($specInsert);
                        } else {
                            $specAttrVal['shopAttrId'] = $shopSkuAttrInfo['attrId'];
                        }
                    }
                    unset($specAttrVal);
                    $erpSkuList[$skuKey]['skuSpecAttr'] = $skuSpecAttr;
                    $where = [];
                    $where['skuBarcode'] = $erpSkuVal['skuBarcode'];
                    $where['goodsId'] = $shopGoodsDetail['goodsId'];
                    $where['dataFlag'] = 1;
                    $shopSkuInfo = $skuSystemTab->where($where)->find();
                    if (empty($shopSkuInfo)) {
                        //新增sku
                        $systemParams = [];
                        $systemParams['goodsId'] = $shopGoodsDetail['goodsId'];
                        $systemParams['skuShopPrice'] = $erpSkuVal['retailPrice'];
                        $systemParams['skuMarketPrice'] = $erpSkuVal['retailPrice'];
                        //$systemParams['skuGoodsStock'] = $erpSkuVal['stock'];
                        $systemParams['skuGoodsImg'] = $erpSkuVal['skuGoodsImg'];
                        $systemParams['skuBarcode'] = $erpSkuVal['skuBarcode'];
                        $systemParams['addTime'] = date('Y-m-d H:i:s', time());
                        $shopSkuId = $skuSystemTab->add($systemParams);
                        if (empty($shopSkuId)) {
                            continue;
                        }
                    } else {
                        //更新sku
                        $shopSkuId = $shopSkuInfo['skuId'];
                        $skuSaveData = [];
                        $skuSaveData['skuShopPrice'] = $erpSkuVal['retailPrice'];
                        $skuSaveData['skuMarketPrice'] = $erpSkuVal['retailPrice'];
                        //$skuSaveData['skuGoodsStock'] = $erpSkuVal['stock'];
                        $skuSaveData['skuGoodsImg'] = $erpSkuVal['skuGoodsImg'];
                        $skuSystemTab->where(['skuId' => $shopSkuId])->save($skuSaveData);
                    }
                    $skuSelfTab->where(['skuId' => $shopSkuId])->save(['dataFlag' => -1]);//删除原来的规格属性
                    $selfParamsData = [];
                    foreach ($skuSpecAttr as $specAttrValc) {
                        $selfParams = [];
                        $selfParams['skuId'] = $shopSkuId;
                        $selfParams['specId'] = $specAttrValc['shopSpecId'];
                        $selfParams['attrId'] = $specAttrValc['shopAttrId'];
                        $selfParamsData[] = $selfParams;
                    }
                    $skuSelfTab->addAll($selfParamsData);
                }
            }
            //同步商品相册
            $where = [];
            $where['goodsId'] = $shopGoodsDetail['goodsId'];
            $where['shopId'] = $shopId;
            $gallerysTab->where($where)->delete();//先删除商品相册再更新
            if (!empty($erpGoodsDetail['gallery'])) {
                $gallerys = $erpGoodsDetail['gallery'];
                foreach ($gallerys as $galleryVal) {
                    $gallerysData = [];
                    $gallerysData['goodsId'] = $shopGoodsDetail['goodsId'];
                    $gallerysData['shopId'] = $shopId;
                    $gallerysData['goodsImg'] = $galleryVal['goodsImg'];
                    $gallerysData['goodsThumbs'] = $galleryVal['goodsImg'];//经测试,总仓商品相册没有做缩略图,暂用该字段代替
                    $gallerysInsert[] = $gallerysData;
                }
            }
        }
        if (!empty($gallerysInsert)) {
            //添加店铺商品相册
            $gallerysTab->addAll($gallerysInsert);
        }
        return returnData(true);
    }

    /**
     * 获取店铺商品详情
     * @param array $params
     * @return array $data
     * */
    public function getShopGoodsDetailByParams($params)
    {
        $goodsWhere = [];
        $goodsWhere['shopId'] = null;
        $goodsWhere['goodsSn'] = '';
        $goodsWhere['goodsFlag'] = 1;
        parm_filter($goodsWhere, $params);
        $goodsTab = M('goods');
        $data = $goodsTab->where($goodsWhere)->find();
        $data = !empty($data) ? $data : [];
        return $data;
    }

    /**
     *同步商品店铺分类 PS:总仓商品没有店铺分类,暂把商品一二级作为店铺分类的一二级
     * @param int $shopId 门店id
     * @param array $goodsDetail
     * @return array $data
     * */
    public static function syncErpGoodsShopCat(int $shopId, array $goodsDetail)
    {
        $shopGoodsCatTab = M('shops_cats');
        $jxcGoodsCatTab = M('jxc_goods_cat');
        //start ========
        //一级
        $where = [];
        $where['parentId'] = 0;
        $where['shopId'] = $shopId;
        $where['catName'] = $goodsDetail['goodsCat1Name'];
        $where['catFlag'] = 1;
        $cat1Info = $shopGoodsCatTab->where($where)->find();
        if (empty($cat1Info)) {
            $jxcCatWhere = [];
            $jxcCatWhere['catid'] = $goodsDetail['goodsCat1'];
            $jxcCatInfo = $jxcGoodsCatTab->where($jxcCatWhere)->find();
            if (empty($jxcCatInfo)) {
                $cat1Info['catId'] = 0;
            } else {
                $catData = [];
                $catData['shopId'] = $shopId;
                $catData['parentId'] = 0;
                $catData['isShow'] = 1;
                $catData['catName'] = $jxcCatInfo['catname'];
                $catData['catSort'] = $jxcCatInfo['sort'];
                $catData['typeImg'] = $jxcCatInfo['pic'];
                $catData['icon'] = '';
                $cat1Info['catId'] = $shopGoodsCatTab->add($catData);
            }
        }
        $cat1Id = $cat1Info['catId'];
        //二级
        $where = [];
        $where['parentId'] = $cat1Id;
        $where['shopId'] = $shopId;
        $where['catName'] = $goodsDetail['goodsCat2Name'];
        $where['catFlag'] = 1;
        $cat2Info = $shopGoodsCatTab->where($where)->find();
        if (empty($cat2Info)) {
            $jxcCatWhere = [];
            $jxcCatWhere['catid'] = $goodsDetail['goodsCat2'];
            $jxcCatInfo = $jxcGoodsCatTab->where($jxcCatWhere)->find();
            if (empty($jxcCatInfo)) {
                $cat2Info['catId'] = 0;
            } else {
                $catData = [];
                $catData['shopId'] = $shopId;
                $catData['parentId'] = $cat1Id;
                $catData['isShow'] = 1;
                $catData['catName'] = $jxcCatInfo['catname'];
                $catData['catSort'] = $jxcCatInfo['sort'];
                $catData['typeImg'] = $jxcCatInfo['pic'];
                $catData['icon'] = '';
                $cat2Info['catId'] = $shopGoodsCatTab->add($catData);
            }
        }
        $cat2Id = $cat2Info['catId'];
        //end ========
        $data = [
            'shopCatId1' => (int)$cat1Id,//店铺一级分类
            'shopCatId2' => (int)$cat2Id,//店铺二级分类
        ];
        return (array)$data;
    }

    /**
     * 同步erp商品商品分类
     * @param array goodsDetail 商品详情
     * @return array $data
     * */
    public function syncErpGoodsCat(array $goodsDetail)
    {
        //PS:商品商城分类只同步新增不同步更新
        $goodsCatTab = M('goods_cats');
        $jxcGoodsCatTab = M('jxc_goods_cat');
        //start ========
        //一级
        $where = [];
        $where['parentId'] = 0;
        $where['catName'] = $goodsDetail['goodsCat1Name'];
        $where['catFlag'] = 1;
        $cat1Info = $goodsCatTab->where($where)->find();
        if (empty($cat1Info)) {
            $jxcCatWhere = [];
            $jxcCatWhere['catid'] = $goodsDetail['goodsCat1'];
            $jxcCatInfo = $jxcGoodsCatTab->where($jxcCatWhere)->find();
            if (empty($jxcCatInfo)) {
                $cat1Info['catId'] = 0;
            } else {
                $catData = [];
                $catData['parentId'] = 0;
                $catData['isShow'] = 1;
                $catData['catName'] = $jxcCatInfo['catname'];
                $catData['catSort'] = $jxcCatInfo['sort'];
                $catData['typeimg'] = $jxcCatInfo['pic'];
                $catData['priceSection'] = '';
                $catData['appTypeSmallImg'] = '';
                $cat1Info['catId'] = $goodsCatTab->add($catData);
            }
        }
        $cat1Id = $cat1Info['catId'];
        //二级
        $where = [];
        $where['parentId'] = $cat1Id;
        $where['catName'] = $goodsDetail['goodsCat2Name'];
        $where['catFlag'] = 1;
        $cat2Info = $goodsCatTab->where($where)->find();
        if (empty($cat2Info)) {
            $jxcCatWhere = [];
            $jxcCatWhere['catid'] = $goodsDetail['goodsCat2'];
            $jxcCatInfo = $jxcGoodsCatTab->where($jxcCatWhere)->find();
            if (empty($jxcCatInfo)) {
                $cat2Info['catId'] = 0;
            } else {
                $catData = [];
                $catData['parentId'] = $cat1Id;
                $catData['isShow'] = 1;
                $catData['catName'] = $jxcCatInfo['catname'];
                $catData['catSort'] = $jxcCatInfo['sort'];
                $catData['typeimg'] = $jxcCatInfo['pic'];
                $catData['priceSection'] = '';
                $catData['appTypeSmallImg'] = '';
                $cat2Info['catId'] = $goodsCatTab->add($catData);
            }
        }
        $cat2Id = $cat2Info['catId'];
        //三级
        $where = [];
        $where['parentId'] = $cat2Id;
        $where['catName'] = $goodsDetail['goodsCat3Name'];
        $where['catFlag'] = 1;
        $cat3Info = $goodsCatTab->where($where)->find();
        if (empty($cat3Info)) {
            $jxcCatWhere = [];
            $jxcCatWhere['catid'] = $goodsDetail['goodsCat3'];
            $jxcCatInfo = $jxcGoodsCatTab->where($jxcCatWhere)->find();
            if (empty($jxcCatInfo)) {
                $cat3Info['catId'] = 0;
            } else {
                $catData = [];
                $catData['parentId'] = $cat2Id;
                $catData['isShow'] = 1;
                $catData['catName'] = $jxcCatInfo['catname'];
                $catData['catSort'] = $jxcCatInfo['sort'];
                $catData['typeimg'] = $jxcCatInfo['pic'];
                $catData['priceSection'] = '';
                $catData['appTypeSmallImg'] = '';
                $cat3Info['catId'] = $goodsCatTab->add($catData);
            }
        }
        $cat3Id = $cat3Info['catId'];
        //end ========
        $data = [
            'goodsCatId1' => (int)$cat1Id,//商城一级分类id
            'goodsCatId2' => (int)$cat2Id,//商城二级分类id
            'goodsCatId3' => (int)$cat3Id,//商城三级分类id
        ];
        return (array)$data;
    }
}

?>

<?php
namespace Made\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订制模块
 * 商户端(Merchantapi)
 */
class MerchantapiModel extends BaseModel {
    /**
     * 商品管理->新增商品
     */
//    public function Merchantapi_Goods_insert($data){
////        /*
////         * 测试
////         * */
////        /*$goodsId = 172;
////        $this->inertPtype($goodsId);exit;*/
////        $rd = array('status'=>-1);
////        $shopId = (int)session('WST_USER.shopId');
////        //$shopId = 1;//需要删除
////        if(empty($shopId)){
////            $shopId = $this->MemberVeri()['shopId'];
////        }
////        $shopId = $shopId?$shopId:$data['shopId'];
////        //查询商家状态
////        $sql = "select shopStatus from __PREFIX__shops where shopFlag = 1 and shopId=".$shopId;
////        $shopStatus = M()->query($sql);
////        if(empty($shopStatus)){
////            $rd['status'] = -2;
////            return $rd;
////        }
////
////        //这里
////        $m = D('goods');
////        if ($m->create()){
////            if(!empty($m->goodsSn)){s
////                $goodsWhere['goodsFlag'] = 1;
////                $goodsWhere['goodsSn'] = $m->goodsSn;
////                $goodsWhere['shopId'] = $shopId;
////                $goodsInfo = M('goods')->where($goodsWhere)->find();
////                if($goodsInfo){
////                    $rd['info'] = '商品编号重复';
////                    return $rd;
////                }
////            }
////
////            $m->shopId = $shopId;
////            $m->isBest = ((int)I('isBest')==1)?1:0;
////            $m->isRecomm = ((int)I('isRecomm')==1)?1:0;
////            $m->isNew = ((int)I('isNew')==1)?1:0;
////            $m->isHot = ((int)I('isHot')==1)?1:0;
////            //如果商家状态不是已审核则所有商品只能在仓库中
////            if($shopStatus[0]['shopStatus']==1){
////                $m->isSale = ((int)I('isSale')==1)?1:0;
////                if(I('isSale') == 1){
////                    $m->saleTime = date('Y-m-d H:i:s',time());
////                }
////            }else{
////                $m->isSale = 0;
////            }
////            $m->goodsDesc = I('goodsDesc');
////            $m->attrCatId = (int)I("attrCatId");
////            $m->goodsStatus = ($GLOBALS['CONFIG']['isGoodsVerify']==1)?0:1;
////            $m->createTime = date('Y-m-d H:i:s');
////            $m->brandId = (int)I("brandId");
////            $m->goodsSpec = I("goodsSpec");
////            $m->goodsKeywords = I("goodsKeywords");
////            $m->saleCount = (int)I("saleCount");//销量
////            $m->isDistribution = (int)I("isDistribution",0);//是否支持分销
////            $m->firstDistribution = I("firstDistribution",0);//一级分销
////            $m->SecondaryDistribution = I("SecondaryDistribution",0);//二级分销
////            $m->buyNum = (int)I("buyNum",0);//限制购买数量
////            $m->goodsCompany = I("goodsCompany");//商品单位
////            $m->isLimitBuy = I("isLimitBuy",0);//是否限量(限量购)
////            $m->isFlashSale = I("isFlashSale",0);//限时购(限时购)
////            $m->spec = $_POST['spec'];//规格
////            //$m->tradePrice = I("tradePrice",0); //批发价
////            $m->goodsLocation = I("goodsLocation");//所属货位
////            $m->orderNumLimit = I("orderNumLimit",'-1'); //限制订单数量 -1为不限制
////            $m->isNewPeople = I("isNewPeople",0,'intval');//是否新人专享,0：否  1：是
////            $m->stockWarningNum = I("stockWarningNum",0,'intval');//库存预警数量
////            $m->integralRate = I("integralRate",0,'trim');//可抵扣积分比例
////
////            /*  			$m->IntelligentRemark = I("IntelligentRemark");//智能备注
////
////                        $m->SuppPriceDiff = (int)I("SuppPriceDiff");
////                        $m->weightG = (int)I("weightG"); */
////
////
////
////
////            $isShopSecKill = (int)I("isShopSecKill");//是否店铺秒杀
////            $ShopGoodSecKillStartTime = I("ShopGoodSecKillStartTime");
////            $ShopGoodSecKillEndTime = I("ShopGoodSecKillEndTime");
////            //判断是否是店铺秒杀
////            if($isShopSecKill == 1){
////                if(empty($ShopGoodSecKillStartTime) || empty($ShopGoodSecKillEndTime)){
////                    $rd['info'] = '如果启动秒杀 开始和结束时间不能为空';
////                    return $rd;
////                }
////
////                $m->isShopSecKill = $isShopSecKill;
////                $m->ShopGoodSecKillStartTime = $ShopGoodSecKillStartTime;
////                $m->ShopGoodSecKillEndTime = $ShopGoodSecKillEndTime;
////
////            }
////
////            //判断是否预售
////            $isShopPreSale = (int)I("isShopPreSale");//是否店铺预售
////            $ShopGoodPreSaleStartTime = I("ShopGoodPreSaleStartTime");
////            $ShopGoodPreSaleEndTime = I("ShopGoodPreSaleEndTime");
////            $bookQuantityEnd = (int)I("bookQuantityEnd");//预售封顶量
////
////            if($isShopPreSale == 1){
////
////                if($isShopSecKill == 1){
////                    $rd['info'] = '秒杀和预售不能同时使用';
////                    return $rd;
////                }
////
////                if(empty($ShopGoodPreSaleStartTime) || empty($ShopGoodPreSaleEndTime) || empty($bookQuantityEnd)){
////                    $rd['info'] = '如果启动预售 开始和结束时间不能为空 预售封顶的量也不能为空';
////                    return $rd;
////                }
////
////                $m->isShopPreSale = $isShopPreSale;
////                $m->ShopGoodPreSaleStartTime = $ShopGoodPreSaleStartTime;
////                $m->ShopGoodPreSaleEndTime = $ShopGoodPreSaleEndTime;
////                $m->bookQuantityEnd = $bookQuantityEnd;
////
////
////            }
////
////            //判断是否会员专享 0:否 1：是
////            $isMembershipExclusive = (int)I("isMembershipExclusive");//是否会员专享
////            $memberPrice = I("memberPrice");//会员价
////            $integralReward = I("integralReward");//积分奖励
////            if ($isMembershipExclusive > 0) {
////                $m->isMembershipExclusive = $isMembershipExclusive;
////                $m->memberPrice = $memberPrice;//会员价
////                $m->integralReward = $integralReward;//积分奖励
////            }
////
////            //如果是新人专享，则取消 推荐、精品、新品、热销、会员专享、限时抢、秒杀、预售
////            if ($m->isNewPeople == 1) {
////                $m->isRecomm = 0;
////                $m->isBest = 0;
////                $m->isNew = 0;
////                $m->isHot = 0;
////                $m->isMembershipExclusive = 0;
////                $m->isFlashSale = 0;
////                $m->isLimitBuy = 0;
////                $m->isShopSecKill = 0;
////                $m->isShopPreSale = 0;
////            }
////
////            $goodsId = $m->add();
////            //$goodsId = 591;//debug
////            if(false !== $goodsId){
////                if($shopStatus[0]['shopStatus']==1){
////                    $rd['status']= 1;
////                    $rd['goodsId'] = $goodsId;
////                }else{
////                    $rd['status'] = -3;
////                }
////                //进销存 添加成功后,在对应的云仓也添加一个商品
////                $jxc = D("Merchantapi/Jxc");
////                $jxc->addCloudWarehouseGoods($shopId,I());
////                //插入限时购商品
////                if(I('isFlashSale') == 1){
////                    $flashSaleId = I('flashSaleId');
////                    if(!empty($flashSaleId)){
////                        $flashSaleGoodsTab = M('flash_sale_goods');
////                        $flashSaleId = trim($flashSaleId,',');
////                        $flashSaleIdArr = explode(',',$flashSaleId);
////                        foreach ($flashSaleIdArr as $val){
////                            $flashGoods['flashSaleId'] = $val;
////                            $flashGoods['goodsId'] = $goodsId;
////                            $flashGoods['addTime'] = date('Y-m-d H:i:s',time());
////                            $flashGoods['isDelete'] = 0;
////                            $flashSaleGoodsTab->add($flashGoods);
////                        }
////                    }
////                }
////
////                //规格属性
////                if((int)I("attrCatId")>0){
////                    //获取商品类型属性
////                    $sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1
////					       and catId=".intval(I("attrCatId"))." and shopId=".$shopId;
////                    $m = M('goods_attributes');
////                    $attrRs = $m->query($sql);
////                    if(!empty($attrRs)){
////                        $priceAttrId = 0;
////                        foreach ($attrRs as $key =>$v){
////                            if($v['isPriceAttr']==1){
////                                $priceAttrId = $v['attrId'];
////                                continue;
////                            }else{
////                                $attr = array();
////                                $attr['shopId'] = $shopId;
////                                $attr['goodsId'] = $goodsId;
////                                $attr['attrId'] = $v['attrId'];
////                                $attr['attrVal'] = I('attr_name_'.$v['attrId']);
////                                $m->add($attr);
////                            }
////                        }
////                        if($priceAttrId>0){
////                            $no = (int)I('goodsPriceNo');
////                            $no = $no>50?50:$no;
////                            $totalStock = 0;
////                            for ($i=0;$i<=$no;$i++){
////                                $name = trim(I('price_name_'.$priceAttrId."_".$i));
////                                if($name=='')continue;
////                                $attr = array();
////                                $attr['shopId'] = $shopId;
////                                $attr['goodsId'] = $goodsId;
////                                $attr['attrId'] = $priceAttrId;
////                                $attr['attrVal'] = $name;
////                                $attr['attrPrice'] = (float)I('price_price_'.$priceAttrId."_".$i);
////                                $attr['isRecomm'] = (int)I('price_isRecomm_'.$priceAttrId."_".$i);
////                                $attr['attrStock'] = (int)I('price_stock_'.$priceAttrId."_".$i);
////                                $totalStock = $totalStock + (int)$attr['attrStock'];
////                                $m->add($attr);
////                            }
////                            //更新商品总库存
////                            $sql = "update __PREFIX__goods set goodsStock=".$totalStock." where goodsId=".$goodsId;
////                            $m->execute($sql);
////                            //更新进销存系统商品的库存
////                            updateJXCGoodsStock($goodsId,$totalStock,2);
////                        }
////                    }
////                }
////                //保存相册
////                $gallery = I("gallery");
////                if($gallery!=''){
////                    $str = explode(',',$gallery);
////                    foreach ($str as $k => $v){
////                        if($v=='')continue;
////                        $str1 = explode('@',$v);
////                        $data = array();
////                        $data['shopId'] = $shopId;
////                        $data['goodsId'] = $goodsId;
////                        $data['goodsImg'] = $str1[0];
////                        $data['goodsThumbs'] = $str1[1];
////                        $m = M('goods_gallerys');
////                        $m->add($data);
////                    }
////                }
////
////                $goodsLocation = I("goodsLocation");//所属货位
////                //将商品添加到商品货位表
////                if (!empty($goodsLocation)) {
////                    $goods = $m->where('goodsId='.$goodsId." and shopId=".$shopId)->find();
////                    $lm = M('location');
////                    $lgm = M('location_goods');
////                    $twoLocationList = $lm->where(array('shopId'=>$shopId,'parentId'=>array('GT',0),'lFlag'=>1))->select();
////                    $twoLocationList_arr = array();
////                    if (!empty($twoLocationList)) {
////                        foreach($twoLocationList as $v){
////                            $twoLocationList_arr[$v['lid']] = $v;
////                        }
////                    }
////                    $lgm->where(array('shopId'=>$shopId,'lid'=>array('not in',$goodsLocation),'goodsId'=>$goods['goodsId'],'lgFlag'=>1))->save(array('lgFlag'=>-1));
////                    $goodsLocation_arr = explode(',',$goodsLocation);
////                    $data_t = array();
////                    foreach($goodsLocation_arr as $v){
////                        $locationInfo = $lm->where(array('shopId'=>$shopId,'parentId'=>array('GT',0),'lid'=>$v,'lFlag'=>1))->find();
////                        if (empty($locationInfo)) continue;
////                        $locationGoodsInfo = $lgm->where(array('shopId'=>$shopId,'lparentId'=>$twoLocationList_arr[$v]['parentId'],'lid'=>$v,'goodsId'=>$goods['goodsId'],'lgFlag'=>1))->find();
////                        if (!empty($locationGoodsInfo)) continue;
////                        $data_t[] = array(
////                            'shopId'    =>  $shopId,
////                            'lparentId' =>  $twoLocationList_arr[$v]['parentId'],
////                            'lid'       =>  $v,
////                            'goodsId'   =>  $goods['goodsId'],
////                            'goodsSn'   =>  $goods['goodsSn'],
////                            'createTime'=>  date('Y-m-d H:i:s'),
////                            'lgFlag'    =>  1
////                        );
////                    }
////                    if (!empty($data_t)) $lgm->addAll($data_t);
////                }
////
////                //goodsIdCopy 该字段只用于商品复用为复用商品的goodsId
////                if(!empty(I('goodsIdCopy'))){
////                    //复用商品的属性(PS:只有该商品下的属性有属性值才会复制)
////                    $goodsIdCopy = I('goodsIdCopy');
////                    $m = M('goods_attributes');
////                    $goodsAttribute = $m->where("goodsId='".$goodsIdCopy."'")->select();
////                    if(count($goodsAttribute) >0){
////                        $list = $m->where("goodsId='".$goodsIdCopy."'")->field('attrId')->select();
////                        if($list){
////                            $parentId = [];
////                            foreach ($list as $val){
////                                $parentId[] = $val['attrId'];
////                            }
////                            sort($parentId);
////                            $parentIdStr = 0;
////                            $parentId = array_unique($parentId);
////                            if(count($parentId) > 0){
////                                $parentIdStr = implode(',',$parentId);
////                            }
////                            $parentList = M('attributes')->where("attrId IN($parentIdStr) AND attrFlag=1")->select();
////                            foreach ($parentList as $key=>&$val){
////                                $children = $m->where("attrId='".$val['attrId']."' AND goodsId='".$goodsIdCopy."'")->select();
////                                $val['children'] = $children;
////                            }
////                            unset($val);
////                            //复制属性为新增商品的属性
////                            foreach ($parentList as $gkey=>$gval){
////                                if(count($gval['children']) > 0){
////                                    $firstAttr = $gval;
////                                    $attrId = $firstAttr['attrId'];
////                                    if($firstAttr['shopId'] != $shopId){
////                                        unset($firstAttr['children']);
////                                        unset($firstAttr['attrId']);
////                                        $firstAttr['shopId'] = $shopId;
////                                        $attrId = M('attributes')->add($firstAttr);
////                                    }
////                                    foreach ($gval['children'] as $cval){
////                                        $secondAttr = $cval;
////                                        unset($secondAttr['id']);
////                                        $secondAttr['shopId'] = $shopId;
////                                        $secondAttr['goodsId'] = $goodsId;
////                                        $secondAttr['attrId'] = $attrId;
////                                        $m->add($secondAttr);
////                                    }
////                                }
////                            }
////                        }
////                    }
////                }
////
////                //后加sku start
////                $accepSpecAttrString = json_decode(htmlspecialchars_decode(I('specAttrString')),true);
////                if(!empty($accepSpecAttrString['specAttrString'])){
////                    $goodsSkuModel = D('Home/GoodsSku');
////                    $insertGoodsSkuRes = $goodsSkuModel->insertGoodsSkuModel((int)$goodsId,$accepSpecAttrString);
////                    if($insertGoodsSkuRes['apiCode'] == -1){
////                        $rd['status'] = -1;
////                        $rd['msg'] = $insertGoodsSkuRes['apiInfo'];
////                    }
////                }
////                ////后加sku end
////
////                //商品添加完成后,在ERP上同步相关信息 start
////                $this->inertPtype($goodsId);
////                //商品添加完成后,在ERP上同步相关信息 end
////
////            }
////        }else{
////            $rd['msg'] = $m->getError();
////        }
////        return $rd;
////    }
////
////    /**
////     * 编辑商品信息
////     */
////    public function Merchantapi_Goods_edit($data = array()){
////        $rd = array('status'=>-1);
////        $goodsId = (int)I("id",0);
////        $shopId = (int)session('WST_USER.shopId');
////        if(empty($shopId)){
////            $shopId = $this->MemberVeri()['shopId'];
////        }
////        $shopId = $shopId?$shopId:$data['shopId'];
////
////        //查询商家状态
////        $sql = "select shopStatus from __PREFIX__shops where shopFlag = 1 and shopId=".$shopId;
////        $shopStatus = $this->queryRow($sql);
////        if(empty($shopStatus)){
////            $rd['status'] = -2;
////            return $rd;
////        }
////        //加载商品信息
////        $m = D('goods');
////        $goods = $m->where('goodsId='.$goodsId." and shopId=".$shopId)->find();
////        if(empty($goods))return array('status'=>-1,'msg'=>'无效的商品ID！');
////        if ($m->create()){
////            $m->isBest = ((int)I('isBest')==1)?1:0;
////            $m->isRecomm = ((int)I('isRecomm')==1)?1:0;
////            $m->isNew = ((int)I('isNew')==1)?1:0;
////            $m->isHot = ((int)I('isHot')==1)?1:0;
////            //如果商家状态不是已审核则所有商品只能在仓库中
////            if($shopStatus['shopStatus']==1){
////                $m->isSale = ((int)I('isSale')==1)?1:0;
////                if(I('isSale') == 1){
////                    $m->saleTime = date('Y-m-d H:i:s',time());
////                }
////            }else{
////                $m->isSale = 0;
////            }
////            $m->goodsDesc = I('goodsDesc');
////            $m->attrCatId = (int)I("attrCatId");
////            $m->goodsStatus = ($GLOBALS['CONFIG']['isGoodsVerify']==1)?0:1;
////            $m->brandId = (int)I("brandId");
////            $m->goodsSpec = I("goodsSpec");
////            $m->goodsKeywords = I("goodsKeywords");
////            $m->PreSalePayPercen = (int)I("PreSalePayPercen");//预付款百分比
////            $m->saleCount = (int)I("saleCount");//销量
////            $m->bookQuantityEnd = (int)I("bookQuantityEnd");//预售封顶量
////            $m->isDistribution = (int)I("isDistribution",0);//是否支持分销
////            $m->firstDistribution = I("firstDistribution",0);//一级分销
////            $m->SecondaryDistribution = I("SecondaryDistribution",0);//二级分销
////            $m->buyNum = (int)I("buyNum",0);//限制购买数量
////            $m->goodsCompany = I("goodsCompany");//商品单位
////            $m->isLimitBuy = I("isLimitBuy",0);//是否限量(限量购)
////            $m->isFlashSale = I("isFlashSale",0);//限时购(限时购)
////            //$m->tradePrice = I("tradePrice",0); //批发价
////            $m->goodsLocation = I("goodsLocation");//所属货位
////            $m->isNewPeople = I("isNewPeople",0,'intval');//是否新人专享,0：否  1：是
////            $m->stockWarningNum = I("stockWarningNum",0,'intval');//库存预警数量
////            $m->integralRate = I("integralRate",0,'trim');//可抵扣积分比例
////            $m->isMembershipExclusive = (int)I("isMembershipExclusive");//是否会员专享
////            $m->memberPrice = I("memberPrice");//会员价
////            $m->integralReward = I("integralReward");//积分奖励
////            $m->spec = $_POST['spec'];//规格
////            $m->orderNumLimit = I("orderNumLimit",'-1'); //限制订单数量 -1为不限制
////            $m->goodsSn = I("goodsSn");//商品条码
////
////            //如果是新人专享，则取消 推荐、精品、新品、热销、会员专享、限时抢、秒杀、预售
////            if ($m->isNewPeople == 1) {
////                $m->isRecomm = 0;
////                $m->isBest = 0;
////                $m->isNew = 0;
////                $m->isHot = 0;
////                $m->isMembershipExclusive = 0;
////                $m->isFlashSale = 0;
////                $m->isLimitBuy = 0;
////                $m->isShopSecKill = 0;
////                $m->isShopPreSale = 0;
////            }
////
////            $rs = $m->where('goodsId='.$goods['goodsId'])->save();
////            if(false !== $rs){
////                //进销存 添加成功后,在对应的云仓也添加一个商品
////                $jxc = D("Merchantapi/Jxc");
////                $jxc->addCloudWarehouseGoods($shopId,I());
////
////                //ERP商品更新
////                $this->inertPtype($goods['goodsId']);
////                //更新进销存系统商品的库存
////                updateJXCGoodsStock($goodsId,I('goodsStock'),2);
////
////                if($shopStatus['shopStatus']==1){
////                    $rd['status']= 1;
////                }else{
////                    $rd['status']= -3;
////                }
////
////                //插入限时购商品
////                $flashSaleGoodsTab = M('flash_sale_goods');
////                $flashSaleGoodsTab->where("goodsId='".$goodsId."'")->save(['isDelete'=>1]);
////                if(I('isFlashSale') == 1){
////                    $flashSaleId = I('flashSaleId');
////                    if(!empty($flashSaleId)){
////                        $flashSaleId = trim($flashSaleId,',');
////                        $flashSaleIdArr = explode(',',$flashSaleId);
////                        $existFlashSale = $flashSaleGoodsTab->where("goodsId='".$goodsId."' AND isDelete=0")->select();
////                        foreach ($existFlashSale as $val){
////                            $existFlashSaleId[] = $val['flashSaleId'];
////                        }
////                        foreach ($flashSaleIdArr as $val){
////                            $flashGoods['flashSaleId'] = $val;
////                            $flashGoods['goodsId'] = $goodsId;
////                            $flashGoods['addTime'] = date('Y-m-d H:i:s',time());
////                            $flashGoods['isDelete'] = 0;
////                            $flashSaleGoodsTab->add($flashGoods);
////                        }
////                    }
////                }
////
////                //删除属性记录
////                //$m->query("delete from __PREFIX__goods_attributes where goodsId=".$goodsId); 和现在的属性冲突,所以注释
////                //规格属性
////                if(intval(I("attrCatId")) > 0){
////                    //获取商品类型属性列表
////                    $sql = "select attrId,attrName,isPriceAttr from __PREFIX__attributes where attrFlag=1
////					       and catId=".intval(I("attrCatId"))." and shopId=".$shopId;
////                    $m = M('goods_attributes');
////                    $attrRs = $m->query($sql);
////                    if(!empty($attrRs)){
////                        $priceAttrId = 0;
////                        $recommPrice = 0;
////                        foreach ($attrRs as $key =>$v){
////                            if($v['isPriceAttr']==1){
////                                $priceAttrId = $v['attrId'];
////                                continue;
////                            }else{
////                                //新增
////                                $attr = array();
////                                $attr['attrVal'] =  trim(I('attr_name_'.$v['attrId']));
////                                $attr['attrPrice'] = 0;
////                                $attr['attrStock'] = 0;
////                                $attr['shopId'] = $shopId;
////                                $attr['goodsId'] = $goodsId;
////                                $attr['attrId'] = $v['attrId'];
////                                $m->add($attr);
////                            }
////                        }
////                        if($priceAttrId>0){
////                            $no = (int)I('goodsPriceNo');
////                            $no = $no>50?50:$no;
////                            $totalStock = 0;
////
////                            for ($i=0;$i<=$no;$i++){
////                                $name = trim(I('price_name_'.$priceAttrId."_".$i));
////                                if($name=='')continue;
////                                $attr = array();
////                                $attr['shopId'] = $shopId;
////                                $attr['goodsId'] = $goodsId;
////                                $attr['attrId'] = $priceAttrId;
////                                $attr['attrVal'] = $name;
////                                $attr['attrPrice'] = (float)I('price_price_'.$priceAttrId."_".$i);
////                                $attr['isRecomm'] = (int)I('price_isRecomm_'.$priceAttrId."_".$i);
////                                if($attr['isRecomm']==1){
////                                    $recommPrice = $attr['attrPrice'];
////                                }
////                                $attr['attrStock'] = (int)I('price_stock_'.$priceAttrId."_".$i);
////                                $totalStock = $totalStock + (int)$attr['attrStock'];
////                                $m->add($attr);
////                            }
////                            //更新商品总库存
////                            $sql = "update __PREFIX__goods set goodsStock=".$totalStock;
////                            if($recommPrice>0){
////                                $sql .= ",shopPrice=".$recommPrice;
////                            }
////                            $sql .= " where goodsId=".$goodsId;
////                            $m->execute($sql);
////
////                            //更新进销存系统商品的库存
////                            updateJXCGoodsStock($goodsId,$totalStock,2);
////                        }
////                    }
////                }
////
////                //保存相册
////                $gallery = I("gallery");
////                $m = M('goods_gallerys');
////                //删除相册信息
////                $m->where('goodsId='.$goods['goodsId'])->delete();
////                if($gallery!=''){
////                    $str = explode(',',$gallery);
////                    //保存相册信息
////                    foreach ($str as $k => $v){
////                        if($v=='')continue;
////                        $str1 = explode('@',$v);
////                        $data = array();
////                        $data['shopId'] = $goods['shopId'];
////                        $data['goodsId'] = $goods['goodsId'];
////                        $data['goodsImg'] = $str1[0];
////                        $data['goodsThumbs'] = $str1[1];
////                        $m->add($data);
////                    }
////                }
////                $goodsLocation = I("goodsLocation");//所属货位
////                //将商品添加到商品货位表
////                if (!empty($goodsLocation)) {
////                    $lm = M('location');
////                    $lgm = M('location_goods');
////                    $twoLocationList = $lm->where(array('shopId'=>$shopId,'parentId'=>array('GT',0),'lFlag'=>1))->select();
////                    $twoLocationList_arr = array();
////                    if (!empty($twoLocationList)) {
////                        foreach($twoLocationList as $v){
////                            $twoLocationList_arr[$v['lid']] = $v;
////                        }
////                    }
////                    $lgm->where(array('shopId'=>$shopId,'lid'=>array('not in',$goodsLocation),'goodsId'=>$goods['goodsId'],'lgFlag'=>1))->save(array('lgFlag'=>-1));
////                    $goodsLocation_arr = explode(',',$goodsLocation);
////                    $data_t = array();
////                    foreach($goodsLocation_arr as $v){
////                        $locationInfo = $lm->where(array('shopId'=>$shopId,'parentId'=>array('GT',0),'lid'=>$v,'lFlag'=>1))->find();
////                        if (empty($locationInfo)) continue;
////                        $locationGoodsInfo = $lgm->where(array('shopId'=>$shopId,'lparentId'=>$twoLocationList_arr[$v]['parentId'],'lid'=>$v,'goodsId'=>$goods['goodsId'],'lgFlag'=>1))->find();
////                        if (!empty($locationGoodsInfo)) continue;
////                        $data_t[] = array(
////                            'shopId'    =>  $shopId,
////                            'lparentId' =>  $twoLocationList_arr[$v]['parentId'],
////                            'lid'       =>  $v,
////                            'goodsId'   =>  $goods['goodsId'],
////                            'goodsSn'   =>  $goods['goodsSn'],
////                            'createTime'=>  date('Y-m-d H:i:s'),
////                            'lgFlag'    =>  1
////                        );
////                    }
////                    if (!empty($data_t)) $lgm->addAll($data_t);
////                }
////                //后加sku start
////                $accepSpecAttrString = json_decode(htmlspecialchars_decode(I('specAttrString')),true);
////                $goodsSkuModel = D('Home/GoodsSku');
////                $insertGoodsSkuRes = $goodsSkuModel->editGoodsSkuModel((int)$goodsId,$accepSpecAttrString);
////                if($insertGoodsSkuRes['apiCode'] == -1){
////                    $rd['status'] = -1;
////                    $rd['msg'] = $insertGoodsSkuRes['apiInfo'];
////                }
////                ////后加sku end
////            }
////        }else{
////            $rd['msg'] = $m->getError();
////        }
////        return $rd;
////    }
////
////
////    /*
////     * ERP下增加商品, 用商品编号进行关联
////     * @param int $goodsId
////     * */
////    public function inertPtype($goodsId){
////        if((int)$goodsId <= 0 ){
////            return false;
////        }
////        $pinyin = D('Made/Pinyin');
////        //$db = sqlServerDB();
////        $db = connectSqlServer();
////        $goodsInfo = M('goods')->where(['goodsId'=>$goodsId,'goodsFlag'=>1])->find();
////        $goodsSn = $goodsInfo['goodsSn'];
////        $goodsCatTab = M('goods_cats');
////        $goodsCat1Info = $goodsCatTab->where(['catId'=>$goodsInfo['goodsCatId1'],'catFlag'=>1])->find();
////        $goodsCat2List = $goodsCatTab->where(['parentId'=>$goodsInfo['goodsCatId1'],'catFlag'=>1])->select();
////        //检测pype表是否有goodsCat1相关信息,注意等级为1
////        /*$sql = "SELECT typeId,FullName,Pid FROM ptype WHERE FULLName='".$goodsCat1Info['catName']."' AND deleted=0 AND leveal=1 ";
////        $conn = $db->prepare($sql);
////        $conn->execute();
////        $existCat1 = hanldeSqlServerData($conn,'row');*/
////        $sql = "SELECT typeId,FullName,Pid FROM ptype WHERE FULLName='".$goodsCat1Info['catName']."' AND deleted=0 AND leveal=1 ";
////        $existCat1 = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'one'));
////        //处理niaocmcs商城一级分类和ERP系统的处理,和商品表共用
////        $CreateDate = date('Y-m-d H:i:s',time());
////        /*$parid = '00000';
////        $level = 1;
////        $FullName = $goodsCat1Info['catName'];
////        $name = '';
////        $sonnum  = count($goodsCat2List);
////        $UserCode = '';
////        $Standard = '';//规格
////        $type = '';//型号 PS:蓝色/128GB/国行版
////        $PyCode = strtoupper($pinyin::getShortPinyin($goodsCat1Info['catName']));*/
////        //构建字段
////        $fieldArr = [];
////        $fieldArr['parid'] = '00000';
////        $fieldArr['leveal'] = 1;
////        $fieldArr['sonnum'] = count($goodsCat2List);
////        $fieldArr['soncount'] = count($goodsCat2List);
////        $fieldArr['FullName'] = $goodsCat1Info['catName'];
////        $fieldArr['PyCode'] = strtoupper($pinyin::getShortPinyin($goodsInfo['goodsName']));
////        $fieldArr['UserCode'] = '';
////        $fieldArr['name'] = '';
////        $fieldArr['Standard'] = '';
////        $fieldArr['type'] = '';
////        $fieldArr['CreateDate'] = $CreateDate;
////        $fieldArr['UnitsType'] = 1;
////        $fieldArr['BuyUnitId'] = 1;
////        $fieldArr['baseUnitId'] = 1;
////        $fieldArr['SaleUnitId'] = 1;
////        $fieldArr['Unit1'] = 0;
////        $fieldArr['UnitRate2'] = 0;
////        $fieldArr['EntryCode'] = '';
////        $fieldArr['StopBuy'] = 0;
////        $fieldArr['AssistantUnitId'] = 0;
////        $fieldArr['OmPrice'] = 0;
////        $fieldArr['weight'] = 0;
////        $fieldArr['volume'] = 0;
////        $fieldInfo = getBuildField($fieldArr);
////        if(!$existCat1){
////            /*$field = "parid,leveal,sonnum,soncount,UserCode,FullName,Name,Standard,Type,deleted,PyCode,CreateDate";
////            $value = "'{$parid}','{$level}','{$sonnum}','{$sonnum}','{$UserCode}','{$FullName}','{$name}','{$Standard}','{$type}','0','{$PyCode}','{$CreateDate}'";*/
////            $field = $fieldInfo['field'];
////            $value = $fieldInfo['value'];
////            $sql = "INSERT INTO Ptype($field) values ($value)";
////            /*$conn = $db->prepare($sql);
////            $insertCat1Res = $conn->execute();*/
////            $insertCat1Res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////            if(!$insertCat1Res){
////                return false;
////            }
////            $sql = "SELECT IDENT_CURRENT('Ptype')";
////            //$conn = $db->prepare($sql);
////            /*$conn->execute();
////            $insertRow = hanldeSqlServerData($conn,'row');
////            unset($conn);*/
////            $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'one'));
////            $insertCat1Id = $insertRow[''];
////            //添加完成后更新相关字段
////            $typeId = str_pad($insertCat1Id,5,0,STR_PAD_LEFT );
////            $userCode = str_pad($insertCat1Id,2,0,STR_PAD_LEFT );
////            $sql = "UPDATE ptype SET typeId='{$typeId}',UserCode='{$userCode}' WHERE Pid='".$insertCat1Id."' ";
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////            $db->sqlExcute(C('sqlserver_db'),$sql);
////
////            $existCat1['Pid'] = $insertCat1Id;
////            $existCat1['typeId'] = $typeId;
////            $existCat1['FullName'] = $fieldArr['FullName'];
////        }else{
////            //更新一些相关信息
////            $sql = "UPDATE ptype SET sonnum='{$fieldArr['sonnum']}',soncount='{$fieldArr['soncount']}',FuLLName='{$fieldArr['FullName']}',Standard='{$fieldArr['Standard']}',[Type]='{$fieldArr['type']}',PyCode='{$fieldArr['PyCode']}' WHERE Pid='".$existCat1['Pid']."'";
////            /*$conn = $db->prepare($sql);
////            $res = $conn->execute();*/
////            $res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////            if($res){
////                $insertCat1Id = $existCat1['Pid'];
////            }
////        }
////        if(isset($insertCat1Id) && (int)$insertCat1Id > 0 ){
////            //向其他关联表插入数据
////            $this->insertPtypeRelation($existCat1);
////            //处理niaocmcs商城二级分类和ERP系统的处理
////            if(!$goodsCat2List){
////                return false;
////            }
////            foreach ($goodsCat2List as $key=>&$val){
////                //该二级分类下面的三级分类
////                $goodsCat3List = $goodsCatTab->where(['parentId'=>$val['catId'],'catFlag'=>1])->select();
////                $goodsCat3ListCount = count($goodsCat3List);
////                //检测pype表是否有goodsCat2相关信息,注意等级为2
////                $sql = "SELECT typeId,FullName,Pid FROM ptype WHERE FULLName='".$val['catName']."' AND deleted=0 AND leveal=2 AND ParId='".$existCat1['typeId']."' ";
////                /*$conn = $db->prepare($sql);
////                $conn->execute();
////                $existCat2 = hanldeSqlServerData($conn,'row');*/
////                $existCat2 = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////
////                /*$parid = $existCat1['typeId'];
////                $level = 2;
////                $FullName = $val['catName'];
////                $name = '';
////                $sonnum  = $goodsCat3ListCount;
////                $UserCode = '';
////                $Standard = '';//规格
////                $type = '';//型号 PS:蓝色/128GB/国行版
////                $PyCode = strtoupper($pinyin::getShortPinyin($val['catName']));*/
////
////                //构建字段
////                $fieldArr = [];
////                $fieldArr['parid'] = $existCat1['typeId'];
////                $fieldArr['leveal'] = 2;
////                $fieldArr['sonnum'] = $goodsCat3ListCount;
////                $fieldArr['soncount'] = $goodsCat3ListCount;
////                $fieldArr['FullName'] = $val['catName'];
////                $fieldArr['PyCode'] = strtoupper($pinyin::getShortPinyin($val['catName']));
////                $fieldArr['UserCode'] = '';
////                $fieldArr['name'] = '';
////                $fieldArr['Standard'] = '';
////                $fieldArr['type'] = '';
////                $fieldArr['CreateDate'] = date('Y-m-d H:i:s',time());
////                $fieldArr['UnitsType'] = 1;
////                $fieldArr['BuyUnitId'] = 1;
////                $fieldArr['baseUnitId'] = 1;
////                $fieldArr['SaleUnitId'] = 1;
////                $fieldArr['Unit1'] = 0;
////                $fieldArr['UnitRate2'] = 0;
////                $fieldArr['EntryCode'] = '';
////                $fieldArr['StopBuy'] = 0;
////                $fieldArr['AssistantUnitId'] = 0;
////                $fieldArr['OmPrice'] = 0;
////                $fieldArr['weight'] = 0;
////                $fieldArr['volume'] = 0;
////
////                $fieldInfo = getBuildField($fieldArr);
////                if(!$existCat2){
////                    //insert goodsCat2
////                    /*$field = "parid,leveal,sonnum,soncount,UserCode,FullName,Name,Standard,Type,deleted,PyCode,CreateDate";
////                    $value = "'{$parid}','{$level}','{$sonnum}','{$sonnum}','{$UserCode}','{$FullName}','{$name}','{$Standard}','{$type}','0','{$PyCode}','{$CreateDate}'";*/
////                    $field = $fieldInfo['field'];
////                    $value = $fieldInfo['value'];
////                    $sql = "INSERT INTO Ptype($field) values ($value)";
////                    /*$conn = $db->prepare($sql);
////                    $insertCat2Res = $conn->execute();*/
////                    $insertCat2Res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////                    if(!$insertCat2Res){
////                        return false;
////                    }
////                    $sql = "SELECT IDENT_CURRENT('Ptype')";
////                    /*$conn = $db->prepare($sql);
////                    $conn->execute();
////                    $insertRow = hanldeSqlServerData($conn,'row');
////                    unset($conn);*/
////                    $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////                    $insertCat2Id = $insertRow[''];
////                    //添加完成后更新相关字段
////                    $typeId = str_pad($insertCat2Id,5,0,STR_PAD_LEFT );
////                    $userCode = str_pad($insertCat2Id,2,0,STR_PAD_LEFT );
////                    $sql = "UPDATE ptype SET typeId='{$typeId}',UserCode='{$userCode}' WHERE Pid='".$insertCat2Id."' ";
////                    $conn = $db->prepare($sql);
////                    $conn->execute();
////
////                    $existCat2['Pid'] = $insertCat2Id;
////                    $existCat2['typeId'] = $typeId;
////                    $existCat2['FullName'] = $fieldArr['FullName'];
////                }else{
////                    //update goodsCat2
////                    $sql = "UPDATE ptype SET sonnum='{$fieldArr['sonnum']}',soncount='{$fieldArr['soncount']}',FuLLName='{$fieldArr['FullName']}',Standard='{$fieldArr['Standard']}',[Type]='{$fieldArr['type']}',PyCode='{$fieldArr['PyCode']}' WHERE Pid='".$existCat2['Pid']."'";
////                    /*$conn = $db->prepare($sql);
////                    $res = $conn->execute();*/
////                    $res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////                    if($res){
////                        $insertCat2Id = $existCat2['Pid'];
////                    }
////                }
////                if(isset($insertCat2Id) && ($insertCat2Id) > 0 ){
////                    //向其他关联表插入数据
////                    $this->insertPtypeRelation($existCat2);
////                    //处理niaocmcs商城三级分类和ERP系统的处理
////                    if(isset($goodsCat3List) && !empty($goodsCat3List)){
////                        foreach ($goodsCat3List as $cat3key=>$cat3val){
////                            //检测pype表是否有goodsCat3相关信息,注意等级为3
////                            $sql = "SELECT typeId,FullName,Pid FROM ptype WHERE FULLName='".$cat3val['catName']."' AND deleted=0 AND leveal=3 AND ParId='".$existCat2['typeId']."' ";
////
////                            /*$conn = $db->prepare($sql);
////                            $conn->execute();
////                            $existCat3 = hanldeSqlServerData($conn,'row');*/
////                            $existCat3 = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////
////                            /*$parid = $existCat2['typeId'];
////                            $level = 3;
////                            $FullName = $cat3val['catName'];
////                            $name = '';
////                            $UserCode = '';
////                            $Standard = '';//规格
////                            $type = '';//型号 PS:蓝色/128GB/国行版
////                            $PyCode = strtoupper($pinyin::getShortPinyin($cat3val['catName']));*/
////
////                            //构建字段
////                            $fieldArr = [];
////                            $fieldArr['parid'] = $existCat2['typeId'];
////                            $fieldArr['leveal'] = 3;
////                            $fieldArr['sonnum'] = 1;
////                            $fieldArr['soncount'] = 1;
////                            $fieldArr['FullName'] = $cat3val['catName'];
////                            $fieldArr['PyCode'] = strtoupper($pinyin::getShortPinyin($cat3val['catName']));
////                            $fieldArr['UserCode'] = '';
////                            $fieldArr['name'] = '';
////                            $fieldArr['Standard'] = '';
////                            $fieldArr['type'] = '';
////                            $fieldArr['CreateDate'] = date('Y-m-d H:i:s',time());
////                            $fieldArr['UnitsType'] = 1;
////                            $fieldArr['BuyUnitId'] = 1;
////                            $fieldArr['baseUnitId'] = 1;
////                            $fieldArr['SaleUnitId'] = 1;
////                            $fieldArr['Unit1'] = 0;
////                            $fieldArr['UnitRate2'] = 0;
////                            $fieldArr['EntryCode'] = '';
////                            $fieldArr['StopBuy'] = 0;
////                            $fieldArr['AssistantUnitId'] = 0;
////                            $fieldArr['OmPrice'] = 0;
////                            $fieldArr['weight'] = 0;
////                            $fieldArr['volume'] = 0;
////
////                            $fieldInfo = getBuildField($fieldArr);
////
////                            if(!$existCat3){
////                                /*$sonnum  = 1;
////                                //insert goodsCat3
////                                $field = "parid,leveal,sonnum,soncount,UserCode,FullName,Name,Standard,Type,deleted,PyCode,CreateDate";
////                                $value = "'{$parid}','{$level}','{$sonnum}','{$sonnum}','{$UserCode}','{$FullName}','{$name}','{$Standard}','{$type}','0','{$PyCode}','{$CreateDate}'";*/
////                                $field = $fieldInfo['field'];
////                                $value = $fieldInfo['value'];
////                                $sql = "INSERT INTO Ptype($field) values ($value)";
////                                /*$conn = $db->prepare($sql);
////                                $insertCat3Res = $conn->execute();*/
////                                $insertCat3Res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////                                if(!$insertCat3Res){
////                                    return false;
////                                }
////                                $sql = "SELECT IDENT_CURRENT('Ptype')";
////                                /*$conn = $db->prepare($sql);
////                                $conn->execute();
////                                $insertRow = hanldeSqlServerData($conn,'row');
////                                unset($conn);*/
////                                $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////                                $insertCat3Id = $insertRow[''];
////                                //添加完成后更新相关字段
////                                $typeId = str_pad($insertCat3Id,5,0,STR_PAD_LEFT );
////                                $userCode = str_pad($insertCat3Id,2,0,STR_PAD_LEFT );
////                                $sql = "UPDATE ptype SET typeId='{$typeId}',UserCode='{$userCode}' WHERE Pid='".$insertCat3Id."' ";
////                                /*$conn = $db->prepare($sql);
////                                $conn->execute();*/
////                                handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////
////                                $existCat3['Pid'] = $insertCat3Id;
////                                $existCat3['typeId'] = $typeId;
////                                $existCat3['FullName'] = $fieldArr['FullName'];
////                            }else{
////                                //update goodsCat3
////                                $sql = "UPDATE ptype SET FuLLName='{$fieldArr['FullName']}',Standard='{$fieldArr['Standard']}',[Type]='{$fieldArr['type']}',PyCode='{$fieldArr['PyCode']}' WHERE Pid='".$existCat3['Pid']."'";
////                                /*$conn = $db->prepare($sql);
////                                $res = $conn->execute();*/
////                                $res = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////                                if($res){
////                                    $insertCat3Id = $existCat3['Pid'];
////                                }
////                            }
////
////                            if(isset($insertCat3Id) && (int)$insertCat3Id > 0){
////                                //向其他关联表插入数据
////                                $this->insertPtypeRelation($existCat3);
////                            }
////                        }
////                    }
////                }
////            }
////            unset($val);
////            //继续添加商品
////            $this->insertErpGoods($goodsId);
////        }
////    }
////
////    /*
////     * ptype插入数据
////     * @param int $goodsId
////     * */
////    public function insertErpGoods($goodsId){
////        if((int)$goodsId <= 0){
////            return false;
////        }
////        $goodsInfo = M('goods')->where(['goodsId'=>$goodsId,'goodsFlag'=>1])->find();
////        if(!$goodsInfo){
////            return false;
////        }
////        $pinyin = new PinyinModel();
////        $goodsCatInfo1 = $this->getGoodsCatInfo($goodsInfo['goodsCatId1']);
////        $goodsCatInfo2 = $this->getGoodsCatInfo($goodsInfo['goodsCatId2']);
////        $goodsCatInfo3 = $this->getGoodsCatInfo($goodsInfo['goodsCatId3']);
////        $getErpCatInfo1 = $this->getErpCatInfo($goodsCatInfo1['catName'],1,'00000');
////        $getErpCatInfo2 = $this->getErpCatInfo($goodsCatInfo2['catName'],2,$getErpCatInfo1['typeId']);
////        $getErpCatInfo3 = $this->getErpCatInfo($goodsCatInfo3['catName'],3,$getErpCatInfo2['typeId']);
////        //构建基本信息,PS:字段不要乱删,不知道删了哪个字段就不能用了
////        /*$parid = $getErpCatInfo3['typeId'];
////        $level = 4;
////        $sonnum = 0;
////        $FullName = $goodsInfo['goodsName'];
////        $PyCode = strtoupper($pinyin::getShortPinyin($goodsInfo['goodsName']));
////        $UserCode = $goodsInfo['goodsSn'];
////        $name = '';
////        $Standard = '';
////        $type = '';
////        $CreateDate = date('Y-m-d H:i:s',time());
////        $UnitsType = 1;
////        $BuyUnitId = 1;
////        $baseUnitId = 1;
////        $SaleUnitId = 1;
////        $Unit1 = 0;
////        $UnitRate2 = 0;
////        $EntryCode = 0;
////        $StopBuy = 0;
////        $AssistantUnitId = 0;
////        $OmPrice = 0;
////        $weight = 0;
////        $volume = 0;*/
////
////        //构建字段
////        $fieldArr = [];
////        $fieldArr['parid'] = $getErpCatInfo3['typeId'];
////        $fieldArr['leveal'] = 4;
////        $fieldArr['sonnum'] = 0;
////        $fieldArr['soncount'] = 0;
////        $fieldArr['FullName'] = $goodsInfo['goodsName'];
////        $fieldArr['PyCode'] = strtoupper($pinyin::getShortPinyin($goodsInfo['goodsName']));
////        $fieldArr['UserCode'] = $goodsInfo['goodsSn'];
////        $fieldArr['name'] = '';
////        $fieldArr['Standard'] = '';
////        $fieldArr['type'] = '';
////        $fieldArr['CreateDate'] = date('Y-m-d H:i:s',time());
////        $fieldArr['UnitsType'] = 1;
////        $fieldArr['BuyUnitId'] = 1;
////        $fieldArr['baseUnitId'] = 1;
////        $fieldArr['SaleUnitId'] = 1;
////        $fieldArr['Unit1'] = 0;
////        $fieldArr['UnitRate2'] = 0;
////        $fieldArr['EntryCode'] = '';
////        $fieldArr['StopBuy'] = 0;
////        $fieldArr['AssistantUnitId'] = 0;
////        $fieldArr['OmPrice'] = 0;
////        $fieldArr['weight'] = 0;
////        $fieldArr['volume'] = 0;
////
////        $fieldInfo = getBuildField($fieldArr);
////        $sqlSon = "UPDATE ptype SET sonnum=sonnum+1,soncount=soncount+1";
////        $whereSon = " WHERE deleted=0 ";
////        if((int)$goodsInfo['goodsCatId3'] <= 0 ){
////            /*$parid = $getErpCatInfo2['typeId'];
////            $level = 3;*/
////            $fieldArr['parid'] = $getErpCatInfo2['typeId'];
////            $fieldArr['leveal'] = 3;
////            //更新子级数量
////            $whereSon .= " AND typeId='".$getErpCatInfo2['typeId']."' ";
////        }elseif ((int)$goodsInfo['goodsCatId2'] <= 0 ){
////            /*$parid = $getErpCatInfo1['typeId'];
////            $level = 2;*/
////            $fieldArr['parid'] = $getErpCatInfo1['typeId'];
////            $fieldArr['leveal'] = 2;
////            //更新子级数量
////            $whereSon .= " AND typeId='".$getErpCatInfo1['typeId']."' ";
////        }elseif ((int)$goodsInfo['goodsCatId1'] <= 0 ){
////            /*$parid = '00000';
////            $level = 1;*/
////            $fieldArr['parid'] = '00000';
////            $fieldArr['leveal'] = 1;
////            //更新子级数量
////        }else{
////            $whereSon .= " AND typeId='".$getErpCatInfo3['typeId']."' ";
////        }
////
////        $sql = "SELECT typeId,ParId,FullName,Pid FROM ptype WHERE ParId='".$fieldArr['parid']."' AND leveal='".$fieldArr['leveal']."' AND UserCode='".$goodsInfo['goodsSn']."' AND deleted=0 ";
////        $ptypeInfo = sqlQuery($sql,'row');
////        if(!$ptypeInfo){
////            //insert
////            /*$field = "parid,leveal,sonnum,soncount,UserCode,FullName,Name,Standard,Type,deleted,PyCode,CreateDate,UnitsType,BuyUnitId,baseUnitId,SaleUnitId,Unit1,UnitRate2,EntryCode,StopBuy,AssistantUnitId,OmPrice,weight,volume";*/
////            $field = $fieldInfo['field'];
////            /*$value = "'{$parid}','{$level}','{$sonnum}','{$sonnum}','{$UserCode}','{$FullName}','{$name}','{$Standard}','{$type}','0','{$PyCode}','{$CreateDate}','{$UnitsType}','{$BuyUnitId}','{$baseUnitId}','{$SaleUnitId}','{$Unit1}','{$UnitRate2}','{$EntryCode}','{$StopBuy}','{$AssistantUnitId}','{$OmPrice}','{$weight}','{$volume}'";*/
////            $value = $fieldInfo['value'];
////            $sql = "INSERT INTO ptype($field) VALUES($value)";
////            $actionRes = sqlExcute($sql);
////            if($actionRes){
////                $ptypeId = sqlInsertId("ptype");
////                //更新上级信息
////                $sqlSon .= $whereSon;
////                sqlExcute($sqlSon);
////                //添加完成后更新相关字段
////                $typeId = str_pad($ptypeId,5,0,STR_PAD_LEFT );
////                $sql = "UPDATE ptype SET typeId='{$typeId}' WHERE Pid='".$ptypeId."' ";
////                if(sqlExcute($sql)){
////                    $ptypeInfo['Pid'] = $ptypeId;
////                    $ptypeInfo['typeId'] = $typeId;
////                    $ptypeInfo['FullName'] = $fieldArr['FullName'];
////                }
////            }
////        }else{
////            //update
////            $sql = "UPDATE ptype SET sonnum='{$fieldArr['sonnum']}',soncount='{$fieldArr['soncount']}',FuLLName='{$fieldArr['FullName']}',Standard='{$fieldArr['FullName']}',[Type]='{$fieldArr['type']}',PyCode='{$fieldArr['PyCode']}' WHERE Pid='".$ptypeInfo['Pid']."'";
////            if(sqlExcute($sql)){
////                $ptypeId = $ptypeInfo['Pid'];
////            }
////        }
////        if(isset($ptypeId) && (int)$ptypeId > 0 ){
////            //本地商品信息,后加
////            $ptypeInfo['localGoodsInfo'] = $goodsInfo;
////            //向其他关联表插入数据
////            $this->insertPtypeRelation($ptypeInfo);
////        }
////    }
////
////    /*
////     * 获取商城分类信息
////     * @param int catId PS:商城分类id
////     * */
////    public function getGoodsCatInfo($catId){
////        if((int)$catId <= 0){
////            return false;
////        }
////        $returnData = [];
////        $goodsCatInfo = M('goods_cats')->where(['catId'=>$catId,'catFlag'=>1])->find();
////        if($goodsCatInfo){
////            $returnData = $goodsCatInfo;
////        }
////        return $returnData;
////    }
////
////    /*
////     * 获取ERP分类信息
////     * @param string $catname PS:分类名称
////     * @param int $level PS:等级
////     * @param int $parId PS:父id
////     * */
////    public function getErpCatInfo($catname,$level=1,$parId=''){
////        if(empty($catname)){
////            return false;
////        }
////        $returnData = [];
////        $db = connectSqlServer();
////        $sql = "SELECT typeId,FullName,Pid FROM ptype WHERE FullName='".$catname."' AND deleted=0 AND leveal=$level  ";
////        if(!empty($parId)) {
////            $sql .= " AND ParId='" . $parId . "' ";
////        }
////        /*$conn = $db->prepare($sql);
////        $conn->execute();
////        $erpCatInfo = hanldeSqlServerData($conn,'row');*/
////        $erpCatInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////        if($erpCatInfo){
////            $returnData = $erpCatInfo;
////        }
////        return $returnData;
////    }
////    /*
////     *向ptype有关联的表插入数据
////     * @param array $data
////     * */
////    public function insertPtypeRelation($data=[]){
////        if(empty($data)){
////            return false;
////        }
////        //$db = sqlServerDB();
////        $db = connectSqlServer();
////        //Pos_PicUpdateRecord
////        $sql = "SELECT pid FROM Pos_PicUpdateRecord WHERE pid='".$data['Pid']."' ";
////        /*$conn = $db->prepare($sql);
////        $conn->execute();
////        $posInfo = hanldeSqlServerData($conn,'row');*/
////        $posInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),'row'));
////        if(!$posInfo){
////            $field = "pid,updatenumber";
////            $value = "'{$data['Pid']}',1";
////            $sql = "INSERT INTO Pos_PicUpdateRecord ($field) values ($value)";
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////            handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////        }
////        //PType_Units_Ext
////        $sql = "SELECT PtypeID,SortId FROM PType_Units_Ext WHERE PtypeID='".$data['typeId']."' ";
////        /*$conn = $db->prepare($sql);
////        $conn->execute();
////        $extInfo = hanldeSqlServerData($conn,'row');*/
////        $extInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////        if(!$extInfo){
////            $field = "PtypeID,UnitsId,RateType,Rate,EntryCode,IsDefaultUnit,IsUse";
////            $value = "'{$data['typeId']}',1,0,'1','',1,1";
////            $sql = "INSERT INTO PType_Units_Ext ($field) values ($value)";
////            handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////        }
////        //PType_Price
////        $sql = "SELECT PtypeID FROM PType_Price WHERE PtypeID='".$data['typeId']."' ";
////        /*$conn = $db->prepare($sql);
////        $conn->execute();
////        $priceInfo = hanldeSqlServerData($conn,'row');*/
////        $priceInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////        if(!$priceInfo){
////            $field = "PTypeID,UnitID,IsDefaultUnit";
////            $value = "'{$data['typeId']}',1,1";
////            $sql = "INSERT INTO PType_Price ($field) values ($value)";
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////            handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////        }
////        //QP_PType_Ext
////        $sql = "SELECT PtypeID FROM QP_PType_Ext WHERE PtypeID='".$data['typeId']."' ";
////        /*$conn = $db->prepare($sql);
////        $conn->execute();
////        $QPExtInfo = hanldeSqlServerData($conn,'row');*/
////        $QPExtInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////        if(!$QPExtInfo){
////            $field = "PTypeID";
////            $value = "'{$data['typeId']}'";
////            $sql = "INSERT INTO QP_PType_Ext ($field) values ($value)";
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////            handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////        }
////        //PType_AuxiliaryUnits_Ext
////        $sql = "SELECT PtypeID FROM PType_AuxiliaryUnits_Ext WHERE PtypeID='".$data['typeId']."' ";
////       /* $conn = $db->prepare($sql);
////        $conn->execute();
////        $auxiliaryInfo = hanldeSqlServerData($conn,'row');*/
////        $auxiliaryInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
////        if(!$auxiliaryInfo){
////            $field = "PTypeID,SaleUnitID1,SaleUnitRate1";
////            $value = "'{$data['typeId']}',1,'1'";
////            $sql = "INSERT INTO PType_AuxiliaryUnits_Ext ($field) values ($value)";
////            /*$conn = $db->prepare($sql);
////            $conn->execute();*/
////            handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
////        }
////        if(!empty($data['localGoodsInfo'])){
////            $shopId = $data['localGoodsInfo']['shopId'];
////            $localGoods = $data['localGoodsInfo'];
////            $shopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
////            $where = " Sid='{$shopRelation['Sid']}' AND deleted=0 ";
////            $field = " TypeId";
////            $stypeInfo = $this->getStypeInfo($where,$field);
////
////            $where = " STypeID='{$stypeInfo['TypeId']}'";
////            $field = "typeId";
////            $stockInfo = $this->getStockInfo($where,$field);
////
////            $where = " KtypeID='{$stockInfo['typeId']}' AND deleted=0 ";
////            $cargoTypeInfo = $this->getERPCargoTypeInfo($where);
////            if(is_array($cargoTypeInfo) && !empty($cargoTypeInfo)){
////                //PS:账面库存和本地库存挂钩,期初库存第一次记录后不再更改
////                //GOODSSTOCKS_QTY(账面库存)
////                $tableName = 'GOODSSTOCKS_QTY';
////                $where = " PTypeID='{$data['typeId']}' AND KTypeID='{$stockInfo['typeId']}' AND CargoID='{$cargoTypeInfo['CargoID']}' ";
////                $qtyInfo = $this->getTableDataInfo($tableName,$where);
////                //构建账面库存字段
////                $PTypeID = $data['typeId'];
////                $KTypeID = $stockInfo['typeId'];
////                $CargoID = $cargoTypeInfo['CargoID'];
////                $ItemID = 0;
////                $Iscombined = '0';
////                $GoodsNumber = '';
////                $ProduceDate = '';
////                $ValidDate = '';
////                $Qty = $localGoods['goodsStock'];
////                $NQty = 0;
////                $GoodsCostPrice = 0;
////                $LockNumber =0;
////                if(!$qtyInfo){
////                    //insert
////                    $sql = "INSERT INTO GOODSSTOCKS_QTY ([PTypeID], [KTypeID], [CargoID], [ItemID], [Iscombined], [GoodsNumber], [ProduceDate], [ValidDate], [Qty], [NQty], [GoodsCostPrice], [LockNumber]) VALUES ('{$PTypeID}', '{$KTypeID}', '{$CargoID}', '{$ItemID}', '{$Iscombined}', '{$GoodsNumber}', '{$ProduceDate}','{$ValidDate}', {$Qty},{$NQty}, {$GoodsCostPrice}, {$LockNumber})";
////                }else{
////                    //update
////                    $sql = "UPDATE GOODSSTOCKS_QTY SET Qty='{$Qty}' WHERE ID='{$qtyInfo['ID']}'";
////                }
////                sqlExcute($sql);
////
////                //填充期初库存数据
////                //IniGoodsStocks
////                //构建期初库存字段
////                $PtypeId = $data['typeId'];
////                $KtypeId = $stockInfo['typeId'];
////                $JobNumber = '';
////                $OutFactoryDate = date('Y-m-d H:i:s',time());
////                $Qty = $localGoods['goodsStock'];
////                $Price = $localGoods['shopPrice'];
////                $Total = $localGoods['goodsStock'] * $localGoods['shopPrice'];
////                $GoodsOrder = '';
////                $GoodsNumber = '';
////                $ProduceDate = '';
////                $ValidDate = '';
////                $GoodsBTypeID = '';
////
////                $tableName = 'IniGoodsStocks';
////                $where = " PtypeId='{$PtypeId}' AND KtypeId='{$KtypeId}' ";
////                $iniGoodsStocks = $this->getTableDataInfo($tableName,$where);
////                if(!$iniGoodsStocks){
////                    $sql = "INSERT INTO [dbo].[IniGoodsStocks]([PtypeId], [KtypeId], [JobNumber], [OutFactoryDate], [Qty], [Price], [Total], [GoodsOrder], [GoodsNumber], [ProduceDate], [ValidDate], [GoodsBTypeID]) VALUES ('{$PTypeID}', '{$KTypeID}', '{$JobNumber}', '{$OutFactoryDate}', $Qty, $Price, $Total, '{$GoodsOrder}', '{$GoodsNumber}', '{$ProduceDate}', '{$ValidDate}', '{$GoodsBTypeID}')";
////                    $res = sqlExcute($sql);
////                    if($res){
////                        //Ini_GOODSSTOCKS_QTY PS:不知道什么鬼,但和期初库存有关
////                        //构建字段
////                        $PTypeID = $data['typeId'];
////                        $KTypeID = $stockInfo['typeId'];
////                        $CargoID = $cargoTypeInfo['CargoID'];
////                        $ItemID = 0;
////                        $Iscombined = 0;
////                        $GoodsNumber = '0';
////                        $ProduceDate = '';
////                        $ValidDate = '';
////                        $Qty = $localGoods['goodsStock'];
////                        $NQty = '0';
////                        $GoodsCostPrice = '0';
////                        $LockNumber = '0';
////                        $sql = "INSERT INTO [dbo].[Ini_GOODSSTOCKS_QTY]([PTypeID], [KTypeID], [CargoID], [ItemID], [Iscombined], [GoodsNumber], [ProduceDate], [ValidDate], [Qty], [NQty], [GoodsCostPrice], [LockNumber]) VALUES ('{$PTypeID}', '{$KTypeID}', $CargoID, '{$ItemID}', '{$Iscombined}', '{$GoodsNumber}', '{$ProduceDate}', '{$ValidDate}', $Qty, $NQty, '{$GoodsCostPrice}', '{$LockNumber}')";
////                        sqlExcute($sql);
////                    }
////                }
////
////                //本地店铺价格同步到ERP零售价
////                //PType_Price
////                $tableName = 'PType_Price';
////                $where = " PTypeID='{$PtypeId}'";
////                $ptypePriceInfo = $this->getTableDataInfo($tableName,$where);
////                $param = [];
////                $param['shopPrice'] = $ptypePriceInfo['RetailPrice'];
////                //PType_Price 同步零售价
////                $PTypeID = $PtypeId;
////                $RetailPrice = $localGoods['shopPrice'];
////                if(!$ptypePriceInfo){
////                    $sql = "INSERT INTO [dbo].[PType_Price]([PTypeID], [RetailPrice]) VALUES ('{$PtypeId}','{$RetailPrice}')";
////                }else{
////                    $sql = "UPDATE PType_Price SET RetailPrice='{$RetailPrice}' WHERE PTypeID='{$PTypeID}' ";
////                }
////                sqlExcute($sql);
////            }
////        }
////    }
////
////    /*
////     * 根据表名,获取单条表数据
////     * @param string tableName
////     * @param string where
////     * @param string field
////     * */
////    public function getTableDataInfo($tableName,$where,$field="*"){
////        $returnData = false;
////        if(empty($tableName) || !is_string($field)){
////            return $returnData;
////        }
////        if(empty($where)){
////            $where = " 1=1 ";
////        }
////        $sql = " SELECT $field FROM $tableName WHERE $where ";
////        $res = sqlQuery($sql,'row');
////        if($res){
////            $returnData = $res;
////        }
////        return $returnData;
////    }
////
////    /*
////     * 获取仓库货位详情
////     * @param string where
////     * @param string field
////     * */
////    public function getERPCargoTypeInfo($where,$field="*"){
////        if(empty($where) || !is_string($where)){
////            $where = " 1=1 ";
////        }
////        $sql = "SELECT $field FROM CargoType WHERE $where";
////        $res = sqlQuery($sql,'row');
////        if(!$res){
////            $res = false;
////        }
////        return $res;
////    }
////
////    /*
////     * 获取ERP商品详情
////     * @param string where
////     * @param string field
////     * */
////    public function getERPGoodsInfo($where,$field='*'){
////        if(empty($where) || !is_string($where)){
////            $where = " 1=1 ";
////        }
////        $sql = "SELECT $field FROM ptype WHERE $where";
////        $res = sqlQuery($sql,'row');
////        if(!$res){
////            $res = false;
////        }
////        return $res;
////    }
////
////    /*
////     *获取ERP商品分类
////     *@param int $level PS:等级id
////     *@param string $parid PS:父id
////     *@param int $page PS:页码
////     *@param int $pageSize PS:分页条数,默认15条
////     * */
////    public function getERPGoodsCat($level=0,$parid='',$page=1,$pageSize=20){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '暂无相关数据';
////        $apiRes['apiState'] = 'error';
////        $where = "leveal='".$level."' AND deleted=0 AND soncount>0 ";
////        if(!empty($parid)){
////            $where .= " AND ParId='".$parid."' ";
////        }
////        $sql = "SELECT typeId,FullName,Pid,sortId,ParId from ptype WHERE $where   ORDER BY sortId ASC";
////        $db = connectSqlServer();
////        $result = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql));
////        //$result = sqlServerPageQuery($page,$pageSize,'ptype','Pid',$where,$field,$orderBy);
////        if($result){
////            $apiRes['apiCode'] = 0;
////            $apiRes['apiInfo'] = '获取数据成功';
////            $apiRes['apiState'] = 'success';
////            $apiRes['apiData'] = $result;
////        }
////        return $apiRes;
////    }
////
////    /*
////     *获取ERP商品
////     *@param array $reuest
////     * */
////    public function getERPGoods($request){
////        //ps:只做三级查询
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '暂无相关数据';
////        $apiRes['apiState'] = 'error';
////        $where = " deleted=0 AND soncount=0 ";
////        $typeId1 = $request['typeId1'];
////        $typeId2 = $request['typeId2'];
////        $typeId3 = $request['typeId3'];
////        $FullName = $request['FullName'];
////        if(!empty($typeId1)){
////            $typeStr = $typeId1;
////            $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE typeId='".$typeId1."' AND deleted=0 AND leveal=1 AND soncount>0 ";
////            $typeId1Info = sqlQuery($sql,'row');
////            if($typeId1Info && $typeId1Info['soncount'] > 0){
////                $typeStr = $typeId1Info['typeId'];
////                $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE ParId='".$typeId1Info['typeId']."' AND deleted=0 AND leveal=2 AND soncount>0";
////                $typeId2Info = sqlQuery($sql);
////                if($typeId2Info > 0){
////                    $typeStr = [];
////                    foreach ($typeId2Info as $key2=>$value2){
////                        if($value2['soncount'] > 0 ){
////                            $typeStr[] = $value2['typeId'];
////                        }
////                    }
////                    if(!empty($typeStr)){
////                        $typeStr = implode(',',$typeStr);
////                    }
////                    $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE ParId IN($typeStr) AND deleted=0 AND leveal=3 AND soncount>0 ";
////                    $typeId3Info = sqlQuery($sql);
////                    if($typeId3Info){
////                        $typeStr = [];
////                        foreach ($typeId3Info as $key3=>$value3){
////                            if($value3['soncount'] > 0 ){
////                                $typeStr[] = $value3['typeId'];
////                            }
////                        }
////                    }
////                }
////            }
////        }
////        if(!empty($typeId2)){
////            $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE ParId='".$typeId2."' AND deleted=0 AND leveal=3 ";
////            $typeId3Info = sqlQuery($sql);
////            if($typeId3Info){
////                $typeStr = [];
////                foreach ($typeId3Info as $key3=>$value3){
////                    if($value3['soncount'] > 0 ){
////                        $typeStr[] = $value3['typeId'];
////                    }
////                }
////            }
////        }
////        if(!empty($typeId3)){
////            $typeStr = [];
////            $typeStr[] = $typeId3;
////        }
////
////        if(!empty($typeStr)){
////            $typeStr = implode(',',$typeStr);
////        }
////        if(!empty($typeStr)){
////            $where .= " AND ParId IN($typeStr)";
////        }
////        if(!empty($FullName)){
////            $where .= " AND FullName LIKE '%".$FullName."%'";
////        }
////        $orderBy = " ORDER BY sortId ASC ";
////        $page = $request['page'];
////        $pageSize = $request['pageSize'];
////        $field = "*";
////        $result = sqlServerPageQuery($page,$pageSize,'ptype','Pid',$where,$field,$orderBy,2);
////        if(!empty($result['root'])){
////            $apiRes['apiCode'] = 0;
////            $apiRes['apiInfo'] = '获取数据成功';
////            $apiRes['apiState'] = 'success';
////            $apiRes['apiData'] = $result;
////        }
////        return $apiRes;
////    }
////
////
////    /*
////     * @param array $request PS:多个商品用逗号分隔
////     * */
////    public function syncERPGoods($request){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '暂无相关数据';
////        $apiRes['apiState'] = 'error';
////        $goodsId = trim($request['goodsId'],',');
////        $shopId = $request['shopId'];
////        $sql = "SELECT * FROM ptype WHERE deleted=0 AND typeId IN('{$goodsId}') ";
////        $goodsList = sqlQuery($sql);
////        if(empty($goodsList)){
////            $apiRes['apiInfo'] = '操作失败';
////            return $apiRes;
////        }
////        foreach ($goodsList as $key=>$value){
////            //检验本地是否存在该商品分类,不存在则新增
////            $catList = $this->checkCat($value);
////            //检验本地是否存在该商品
////            $checkLocalGoodsRes = $this->checkLocalGoods($shopId,$value,$catList);
////            if(!$checkLocalGoodsRes){
////                $apiRes['apiInfo'] = '添加失败';
////                return $apiRes;
////            }
////        }
////
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////    /*
////     * 检验本地是否存在该商品分类,不存在则新增
////     * @param array $goodsInfo PS:ERP商品详情
////     * */
////    public function checkCat($goodsInfo){
////        if(empty($goodsInfo) || $goodsInfo['leveal'] <= 1){
////            return false;
////        }
////        $request['where'] = " typeId='".$goodsInfo['ParId']."' ";
////        $lastCat = $this->getERTCatInfoId($request);
////        $catlist = [];
////        $catlist[] = $lastCat;
////        //最多只有三级
////        for ($i=$lastCat['leveal']-1;$i>0;$i--){
////            if($lastCat['leveal'] == 3){
////                if($i == 2){
////                    $request['where'] = " typeId='".$lastCat['ParId']."' AND leveal=2 ";
////                    $lastCat2 = $this->getERTCatInfoId($request);
////                    $catlist[] = $lastCat2;
////                }elseif($i == 1){
////                    $request['where'] = " typeId='".$lastCat2['ParId']."' AND leveal=1 ";
////                    $lastCat1 = $this->getERTCatInfoId($request);
////                    $catlist[] = $lastCat1;
////                }
////            }elseif($lastCat['leveal'] == 2){
////                $request['where'] = " typeId='".$lastCat['ParId']."' AND leveal=1 ";
////                $lastCat1 = $this->getERTCatInfoId($request);
////                $catlist[] = $lastCat1;
////            }
////        }
////        $levealArr = [];
////        foreach ($catlist as $val) {
////            $levealArr[] = $val['leveal'];
////        }
////        array_multisort($catlist,SORT_ASC,SORT_NUMERIC,$levealArr);//从低到高排序
////        $newCatId = [];
////        foreach ($catlist as $cval){
////            $insertCat = [];
////            $insertCat['isShow'] = 1;
////            $insertCat['catName'] = $cval['FullName'];
////            if($cval['leveal'] == 1){
////                $localGoodsInfo1 = $this->getlocalGoodsCat(0,0,$cval['FullName']);
////                if(!$localGoodsInfo1){
////                    $insertCat['parentId'] = 0;
////                    $catId1 = M('goods_cats')->add($insertCat);
////                }else{
////                    $catId1 = $localGoodsInfo1['catId'];
////                }
////                $newCatId[] = $catId1;
////            }
////
////            if($cval['leveal'] == 2 && $catId1 > 0 ){
////                $localGoodsInfo2 = $this->getlocalGoodsCat(0,$catId1,$cval['FullName']);
////                if(!$localGoodsInfo2){
////                    $insertCat['parentId'] = $catId1;
////                    $catId2 = M('goods_cats')->add($insertCat);
////                }else{
////                    $catId2 = $localGoodsInfo2['catId'];
////                }
////                $newCatId[] = $catId2;
////            }
////            if($cval['leveal'] == 3 && $catId2 > 0 ){
////                $localGoodsInfo3 = $this->getlocalGoodsCat(0,$catId2,$cval['FullName']);
////                if(!$localGoodsInfo3){
////                    $insertCat['parentId'] = $catId2;
////                    $catId3 = M('goods_cats')->add($insertCat);
////                }else{
////                    $catId3 = $localGoodsInfo3['catId'];
////                }
////                $newCatId[] = $catId3;
////            }
////        }
////        unset($where);
////        $where['catId'] = ["IN",$newCatId];
////        $newCatList = M('goods_cats')->where($where)->select();
////        return $newCatList;
////    }
////
////    /*
////     * 获取ERP分类详情
////     * @param array $request PS:[where]参数可自定义
////     * */
////    public function getERTCatInfoId($request){
////        $returnData = [];
////        $sql = "SELECT typeId,ParId,leveal,FullName,Pid FROM ptype WHERE deleted=0 ";
////        if(!empty($request['where'])){
////            $sql .= "AND ".$request['where'];
////        }
////        $catInfo = sqlQuery($sql,'row');
////        if($catInfo){
////            $returnData = $catInfo;
////        }
////        return $returnData;
////    }
////
////    /*
////     * 获取本地商品商城分类详情
////     * @param int $catId PS:分类id
////     * @param int $parentId PS:父级id
////     * @param string $catName PS:分类名称
////     * */
////    public function getlocalGoodsCat($catId=0,$parentId=0,$catName=''){
////        $where = [];
////        $where['catFlag'] = 1;
////        $where['parentId'] = $parentId;
////        if((int)$catId > 0 ){
////            $where['catId'] = $catId;
////        }
////        if(!empty($catName)){
////            $where['catName'] = $catName;
////        }
////        $catInfo = M('goods_cats')->where($where)->find();
////        return $catInfo;
////    }
////
////
////    /*
////     * 检测ERP商品在本地是否存在,不存在则新增
////     * @param array $goodsInfo
////     * @param array $catList PS:本地分类
////     * */
////    public function checkLocalGoods($shopId,$goodsInfo,$catList=[]){
////        if(empty($goodsInfo) || empty($shopId)){
////            return false;
////        }
////        $goodsTab = M("goods");
////        $localWhere['goodsSn'] = $goodsInfo['UserCode'];
////        $localWhere['goodsFlag'] = 1;
////        $localWhere['shopId'] = $shopId;
////        $localGoodsInfo = $goodsTab->where($localWhere)->field('goodsId,goodsName,goodsSn')->find();
////        if(!$localGoodsInfo){
////            $goods['goodsSn'] = $goodsInfo['UserCode'];
////            $goods['goodsName'] = $goodsInfo['FullName'];
////            $goods['shopId'] = $shopId;
////            $goods['createTime'] = $goodsInfo['CreateDate'];
////            $goods['isSale'] = 0;
////            $goods['goodsStatus'] = 1;
////            $goods['goodsFlag'] = 1;
////            $goods['goodsCatId1'] = 0;
////            $goods['goodsCatId2'] = 0;
////            $goods['goodsCatId3'] = 0;
////            $catId1 = (int)$catList[0]['catId'];
////            $catId2 = (int)$catList[1]['catId'];
////            $catId3 = (int)$catList[2]['catId'];
////            if($catId1 > 0 ){
////                $goods['goodsCatId1'] = $catId1;
////            }
////            if($catId2 > 0 ){
////                $goods['goodsCatId2'] = $catId2;
////            }
////            if($catId3 > 0 ){
////                $goods['goodsCatId3'] = $catId3;
////            }
////            $insertGoodsId = $goodsTab->add($goods);
////            if(!$insertGoodsId){
////                return false;
////            }
////        }else{
////            $insertGoodsId = $localGoodsInfo['goodsId'];
////        }
////        if($insertGoodsId){
////            //更新下库存
////            $shopRelation  = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
////            $where = "Sid='{$shopRelation['Sid']}' AND deleted=0 ";
////            $field = "TypeId";
////            $stypeInfo = $this->getStypeInfo($where,$field);
////
////            $where = "STypeID='{$stypeInfo['TypeId']}' AND deleted=0 ";
////            $field = "typeId";
////            $stockInfo = $this->getStockInfo($where,$field);
////
////            $where = "KtypeID='{$stockInfo['typeId']}' AND deleted=0 ";
////            $field = "CargoID";
////            $cargoTypeInfo = $this->getERPCargoTypeInfo($where,$field);
////            if(!$cargoTypeInfo){
////                return false;
////            }
////
////            $where = "UserCode='{$localGoodsInfo['goodsSn']}' AND deleted=0 ";
////            $ptypeInfo = $this->getERPGoodsInfo($where);
////
////            $tableName = 'GOODSSTOCKS_QTY';
////            $where = "PTypeID='{$ptypeInfo['typeId']}' AND CargoID='{$cargoTypeInfo['CargoID']}' ";
////            $qtyInfo = $this->getTableDataInfo($tableName,$where);
////            if($qtyInfo){
////                //同步库存
////                $param = [];
////                $param['goodsStock'] = $qtyInfo['Qty'];
////                M('goods')->where(['goodsSn'=>$localGoodsInfo['goodsSn'],'shopId'=>$shopId])->save($param);
////            }
////
////            //ERP零售价同步到本地店铺价格
////            //PType_Price
////            $tableName = 'PType_Price';
////            $where = " PTypeID='{$ptypeInfo['typeId']}'";
////            $ptypePriceInfo = $this->getTableDataInfo($tableName,$where);
////            if($ptypePriceInfo){
////                //同步价格
////                $param = [];
////                $param['shopPrice'] = $ptypePriceInfo['RetailPrice'];
////                M('goods')->where(['goodsSn'=>$localGoodsInfo['goodsSn'],'shopId'=>$shopId])->save($param);
////            }
////        }
////        return true;
////    }
////
////    /*
////     *本地订单同步到ERP
////     *@param int orderId PS:订单id,多个用逗号分隔
////     * */
////    public function syncERPOrder($request){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        $shopId = $request['shopId'];
////        $orderIds = $request['orderId'];
////        if(is_string($orderIds)){
////            $orderIds = trim($orderIds,',');
////            $orderIdArr = explode(',',$orderIds);
////        }
////        if(!isset($orderIdArr) || empty($orderIdArr)){
////            $apiRes['apiInfo'] = '订单信息有误';
////            return $apiRes;
////        }
////        $orderTab = M('orders');
////        $orderWhere = [];
////        $orderWhere['orderId'] = ['IN',$orderIdArr];
////        $orderWhere['orderFlag'] = 1;
////        $orderWhere['shopId'] = $shopId;
////        $field = "orderId,orderNo,shopId,orderStatus,totalMoney,deliverMoney,isPay,userId,userAddress,orderScore,orderRemarks,requireTime,createTime,isRefund,realTotalMoney,useScore,receiveTime,deliveryTime,deliveryNo";
////        $orderList = $orderTab->where($orderWhere)->field($field)->select();
////        if(!$orderList){
////            $apiRes['apiInfo'] = '订单信息有误';
////            return $apiRes;
////        }
////
////        foreach ($orderList as $value){
////            //销售出库单
////            //$this->insertERPSaleBill($value);
////            $res = $this->insertERPSaleBill($value);
////            if($res['apiCode'] == -1){
////                return $res;
////            }
////        }
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////
////    /*
////     *添加销售出库单
////     * @param array $orderInfo PS;订单信息
////     * */
////    public function insertERPSaleBill($orderInfo){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(empty($orderInfo)){
////            $apiRes['apiInfo'] = '订单信息有误';
////            return $apiRes;
////        }
////        //$tableName,$prefix='',$db=''
////        //分支机构信息
////        $stypeShopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$orderInfo['shopId'],'isDelete'=>1])->find();
////        $sql = "SELECT TypeId,parid,Sid,FullName FROM Stype WHERE Sid='".$stypeShopRelation['Sid']."' AND deleted=0 ";
////        $stypeInfo = sqlQuery($sql,'row');
////        if(!$stypeInfo){
////            $apiRes['apiInfo'] = '分支机构信息有误';
////            return $apiRes;
////        }
////        //本地店铺信息
////        $shopInfo = M('shops')->where(['shopId'=>$stypeShopRelation['shopId'],'shopFlag'=>1])->field("shopId,shopSn,shopName")->find();
////        //仓库信息
////        $sql = "SELECT typeId,parid,Kid,STypeID FROM Stock WHERE STypeID='".$stypeInfo['TypeId']."' AND deleted=0 ";
////        $stockInfo = sqlQuery($sql,'row');
////        if(!$stockInfo){
////            $apiRes['apiInfo'] = '仓库信息有误';
////            return $apiRes;
////        }
////        //默认货位信息
////        $sql = "SELECT CargoID,UserCode,FullName FROM CargoType WHERE KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
////        $cargoInfo = sqlQuery($sql,'row');
////        if(!$cargoInfo){
////            $apiRes['apiInfo'] = '货位信息有误';
////            return $apiRes;
////        }
////        //默认部门
////        $sql = "SELECT typeid,parid,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
////        $DepartmentInfo = sqlQuery($sql,'row');
////        //默认职员
////        $defalutUserCode = $DepartmentInfo['rec'].'-001';
////        $sql = "SELECT typeid,parid,FullName FROM employee WHERE DtypeID='{$DepartmentInfo['typeid']}' AND STypeID='{$stypeInfo['TypeId']}' AND UserCode='{$defalutUserCode}'";
////        $employeeInfo = sqlQuery($sql,'row');
////        //订单商品信息
////        $orderGoods = M('order_goods og')
////            ->join("left join wst_goods g on og.goodsId=g.goodsId")
////            ->where(['og.orderId'=>$orderInfo['orderId']])
////            ->field("og.*,g.goodsSn")
////            ->select();
////        //验证商品,如果商品在ERP中不存在,就不在执行下面的程序了
////        foreach ($orderGoods as $key=>$val){
////            $sql = "SELECT typeId,UserCode,FullName FROM ptype WHERE deleted=0 AND UserCode='".$val['goodsSn']."'";
////            $erpGoodsInfo = sqlQuery($sql,'row');
////            if(empty($erpGoodsInfo)){
////                $apiRes['apiInfo'] = '商品信息有误';
////                return $apiRes;
////            }
////        }
////        $goodsTab = M('goods');
////        //userInfo
////        $userInfo = M('users')->where(['userId'=>$orderInfo['userId']])->field('userName,userPhone')->find();
////        foreach ($orderGoods as $key=>$val){
////            $orderGoods[$key]['goodsSn'] = $goodsTab->where(['goodsId'=>$val['goodsId']])->getField('goodsSn');
////        }
////        $countGoods = count($orderGoods);
////        $time = date('Y-m-d H:i:s',time());
////
////        /*$field = "BillDate,BillCode,BillType,btypeid,etypeid,ktypeid,ifcheck,checke,totalmoney,totalqty,period,RedWord,draft,DTypeId,LinkMan,LinkTel,LinkAddr,Stypeid,Poster,LastUpdateTime,checkTime,DealBTypeID,CID,Rate,NTotalMoney,BeforePromoBillNumberId,BillTime,WarehouseOutDay,StockInDeadline";*/
////        $sql = "SELECT newid()";
////        $newId = sqlQuery($sql,'row');
////        $newId = $newId[''];
////
////        //该字段列表直接去复制表中正确的数据,转成SQL语句,比较快速,和值的对应正确率较高
////        $field = "[BillDate], [BillCode], [BillType], [Comment], [atypeid], [ptypeid], [btypeid], [etypeid], [ktypeid], [ktypeid2], [ifcheck], [checke], [totalmoney], [totalinmoney], [totalqty], [tax], [period], [explain], [RedWord], [draft], [OrderId], [waybillcode], [goodsnumber], [packway], [TEL], [Uploaded], [OFF_ID], [TeamNo], [AlertDay], [CompleteTotal], [ID], [Assessor], [IfAudit], [Audit_explain], [IfStopMoney], [preferencemoney], [DTypeId], [JF], [VipCardID], [vipCZMoney], [jsStyle], [jsState], [KTypeID3], [LinkMan], [LinkTel], [LinkAddr], [wxManID], [JieJianBillNumberID], [JieJianID], [JieJianState], [BaoXiuBillNumberID], [BaoXiuID], [BuyDate], [Serial], [wxTotal], [ErrDes], [ifHideJieJian], [ifWX], [ifYearBill], [CWCheck], [IfBargainOn], [LastUpdateTime], [Poster], [STypeID], [FromBillNumberID], [CostSale], [IfMulAccount], [OtherInOutType], [PosBillType], [checkTime], [posttime], [updateE], [ShareMode], [IsFreightMoney], [FreightAddr], [FreightPerson], [FreightTel], [IfInvoice], [TransportId], [UF1], [UF2], [UF3], [UF4], [UF5], [ATID], [DealBTypeID], [CID], [Rate], [NTotalMoney], [NTotalInMoney], [NPreferenceMoney], [NVIPCZMoney], [IsIni], [ATypeID1], [NCompleteTotal], [billproperty], [TallyId], [IfCargo], [InvoiceMoney], [NInvoiceMoney], [RedReason], [NewPosBill], [PrePriceNum], [AssetBusinessTypeId], [PromoRuleId], [BeforePromoBillNumberId], [ImportType], [PromotionMsg], [Reconciliation], [ARTOTAL], [APTOTAL], [YSTOTAL], [YFTOTAL], [IsAuto], [StypeID2], [IfConfirm], [TransferType], [FeeDeductType], [IsYapi], [YapiOrderID], [DeliveryAddress], [AddDTypeETypeType], [BillTime], [IsLend], [Discount], [AdPriceType], [ChargeOffBtypeid], [ChargeOffType], [ChargeOffTotal], [NChargeOffTotal], [cwchecker], [InFactRefState], [OutFactRefState], [InFactState], [OutFactState], [ShelveState], [ShelveRefState], [TransPortState], [iscombinationstyle], [progressstate], [citestate], [IsNeedTransport], [shipper], [totalfreight], [ntotalfreight], [transportno], [WarehouseOutDay], [StockInDeadline], [Transporterid], [Transporterfullname], [shipperfullname], [shipperperson], [shippertel], [FaPiaoCode], [PayType], [PosId], [PromonIds], [isByBrandarType], [PayOnTheSpot], [UploadPreOrder], [PreOrderStatus], [PreOrderId]";
////        $explodeTime = explode(' ',$time);
////        $BillDate1 = date('Y-m-d').' 00:00:00';//此参数勿改,会影响单据展示
////        $BillCode2 = $orderInfo['orderNo'];
////        $BillType3 = 11;
////        $Comment4 = '';
////        $atypeid5 = '';
////        $ptypeid6 = '';//商品id
////        $btypeid7 = '00002000640000200001';//结算单位#关联表,先写死
////        $etypeid8 = $employeeInfo['typeid'];//本地系统订单没有这块逻辑,先用默认职员代替
////        $ktypeid9 = $stockInfo['typeId'];//仓库id
////        $ktypeid210 = '';
////        $ifcheck11 = 'f';
////        $checke12 = '00001';//制单人#关联表,先写死
////        $totalmoney13 = $orderInfo['realTotalMoney'];
////        $totalinmoney14 = .0000000000;
////        $totalqty15 = $countGoods;
////        $tax16 = .0000000000;
////        $period17 = 1;
////        $explain18 = '';
////        $RedWord19 = 0;
////        $draft20 = '1';
////        $OrderId21 = 0;
////        $waybillcode22 = '';
////        $goodsnumber23 = '';
////        $packway24 = '';
////        $TEL25 = '';
////        $Uploaded26 = '0';
////        $OFF_ID27 = $newId;
////        $TeamNo28 = NULL;
////        $AlertDay29 = 9999;
////        $CompleteTotal30 = .0000000000;
////        $ID31 = 0;
////        $Assessor32 = '';
////        $IfAudit33 = ' ';
////        $Audit_explain34 = '';
////        $IfStopMoney35 = '0';
////        $preferencemoney36 = '.0000000000';
////        $DTypeId37 = $DepartmentInfo['typeid'];
////        $JF38 = 0;
////        $VipCardID39 = -1;
////        $vipCZMoney40 = .0000000000;
////        $jsStyle41 = '0';
////        $jsState42 = '0';
////        $KTypeID343 = '';
////        $LinkMan44 = $userInfo['userName'];
////        $LinkTel45 = $userInfo['userPhone'];
////        $LinkAddr46 = $orderInfo['userAddress'];
////        $wxManID47 = '';
////        $JieJianBillNumberID48 = -1;
////        $JieJianID49 = -1;
////        $JieJianState50 = -1;
////        $BaoXiuBillNumberID51 = -1;
////        $BaoXiuID52 = -1;
////        $BuyDate53 = '';
////        $Serial54 = '';
////        $wxTotal55 = .0000000000;
////        $ErrDes56 = '';
////        $ifHideJieJian57 = 0;
////        $ifWX58 = 0;
////        $ifYearBill59 = 0;
////        $CWCheck60 = 0;
////        $IfBargainOn61 = 0;
////        $LastUpdateTime62 = $time;
////        $Poster63 = '';
////        $STypeID64 = $stypeInfo['TypeId'];//分支机构id
////        $FromBillNumberID65 = 0;
////        $CostSale66 = '0';
////        $IfMulAccount67 = 0;
////        $OtherInOutType68 = -1;
////        $PosBillType69 = 0;
////        $checkTime70 = $time;
////        $posttime71 = NULL;
////        $updateE72 = '00001';//没注释,不清楚
////        $ShareMode73 = 0;
////        $IsFreightMoney74 = 0;
////        $FreightAddr75 = '';
////        $FreightPerson76 = '';
////        $FreightTel77 = 0;
////        $IfInvoice78 = 0;
////        $TransportId79 = '0';
////        $UF180 = '';
////        $UF281 = '';
////        $UF382 = '';
////        $UF483 = '';
////        $UF584 = '';
////        $ATID85 = 0;
////        //$DealBTypeID86 = '00002000640000200001';//往来单位#关联表,先写死
////        $DealBTypeID86 = '';//往来单位
////        $CID87 = 1;
////        $Rate88 = 1;
////        $NTotalMoney89 = $orderInfo['realTotalMoney'];
////        $NTotalInMoney90 = .0000000000;
////        $NPreferenceMoney91 = .0000000000;
////        $NVIPCZMoney92 = .0000000000;
////        $IsIni93 = '0';
////        $ATypeID194 = '';
////        $NCompleteTotal95 = .0000000000;
////        $billproperty96 = -1;
////        $TallyId97 = '';
////        $IfCargo98 = '0';
////        $InvoiceMoney99 = .0000000000;
////        $NInvoiceMoney100 = .0000000000;
////        $RedReason101 = '';
////        $NewPosBill102 = 0;
////        $PrePriceNum103 = 1;
////        $AssetBusinessTypeId104 = 0;
////        $PromoRuleId105 = 0;
////        $BeforePromoBillNumberId106 = 0;
////        $ImportType107 = 0;
////        $PromotionMsg108 = '';//N''
////        $Reconciliation109 = 'f';
////        $ARTOTAL110 = .0000000000;
////        $APTOTAL111 = .0000000000;
////        $YSTOTAL112 = .0000000000;
////        $YFTOTAL113 = .0000000000;
////        $IsAuto114 = 0;
////        $StypeID2115 = '';
////        $IfConfirm116 = 0;
////        $TransferType117 = 1;
////        $FeeDeductType118 = 0;
////        $IsYapi119 = '0';
////        $YapiOrderID120 = 0;
////        $DeliveryAddress121 = '';
////        $AddDTypeETypeType122 = 0;
////        $BillTime123 = $explodeTime[1];
////        $IsLend124 = '0';
////        $Discount125 = 1;//扣率
////        $AdPriceType126 = 0;
////        $ChargeOffBtypeid127 = '';
////        $ChargeOffType128 = 0;
////        $ChargeOffTotal129 = .0000000000;
////        $NChargeOffTotal130 = .0000000000;
////        $cwchecker131 = '';
////        $InFactRefState132 = 0;
////        $OutFactRefState133 = 0;
////        $InFactState134 = 0;
////        $OutFactState135 = 0;
////        $ShelveState136 = 0;
////        $ShelveRefState137 = 0;
////        $TransPortState138 = 0;
////        $iscombinationstyle139 = 0;
////        $progressstate140 = 0;
////        $citestate141 = 0;
////        $IsNeedTransport142 = '0';
////        $shipper143 = NULL;
////        $totalfreight144 = .0000000000;
////        $ntotalfreight145 = .0000000000;
////        $transportno146 = NULL;
////        $WarehouseOutDay147 = '9999';
////        $StockInDeadline148 = '9999';
////        $Transporterid149 = NULL;
////        $Transporterfullname150 = NULL;
////        $shipperfullname151 = NULL;
////        $shipperperson152 = NULL;
////        $shippertel153 = NULL;
////        $FaPiaoCode154 = '';
////        $PayType155 = 0;
////        $PosId156 = 0;
////        $PromonIds157 = '';
////        $isByBrandarType158 = 0;
////        $PayOnTheSpot159 = '';
////        $UploadPreOrder160 = 0;
////        $PreOrderStatus161 = 0;
////        $PreOrderId162 = '';
////
////        $value = "'{$BillDate1}', '{$BillCode2}', $BillType3, '{$Comment4}', '{$atypeid5}', '{$ptypeid6}', '{$btypeid7}', '{$etypeid8}', '{$ktypeid9}', '{$ktypeid210}', '{$ifcheck11}', '{$checke12}', $totalmoney13, $totalinmoney14, $totalqty15, $tax16, $period17, '{$explain18}', $RedWord19, '{$draft20}', $OrderId21, '{$waybillcode22}', '{$goodsnumber23}', '{$packway24}', '{$TEL25}', '{$Uploaded26}', '{$OFF_ID27}', '{$TeamNo28}', $AlertDay29, $CompleteTotal30, $ID31, '{$Assessor32}', '{$IfAudit33}', '{$Audit_explain34}', '{$IfStopMoney35}', '{$preferencemoney36}', '{$DTypeId37}', $JF38, $VipCardID39, $vipCZMoney40, '{$jsStyle41}', '{$jsState42}', '{$KTypeID343}', '{$LinkMan44}', '{$LinkTel45}', '{$LinkAddr46}', '{$wxManID47}', $JieJianBillNumberID48, $JieJianID49, $JieJianState50, $BaoXiuBillNumberID51, $BaoXiuID52, '{$BuyDate53}', '{$Serial54}', $wxTotal55, '{$ErrDes56}', $ifHideJieJian57, $ifWX58, $ifYearBill59, $CWCheck60, $IfBargainOn61, '{$LastUpdateTime62}', '{$Poster63}', '{$STypeID64}', $FromBillNumberID65, '{$CostSale66}', $IfMulAccount67, $OtherInOutType68, $PosBillType69, '{$checkTime70}', '{$posttime71}', '{$updateE72}', $ShareMode73, $IsFreightMoney74, '{$FreightAddr75}', '{$FreightPerson76}', $FreightTel77, $IfInvoice78, '{$TransportId79}', '{$UF180}', '{$UF281}', '{$UF382}', '{$UF483}', '{$UF584}', $ATID85, '{$DealBTypeID86}', $CID87, $Rate88, $NTotalMoney89, $NTotalInMoney90, $NPreferenceMoney91, $NVIPCZMoney92, '{$IsIni93}', '{$ATypeID194}', $NCompleteTotal95, $billproperty96, '{$TallyId97}', '{$IfCargo98}', $InvoiceMoney99, $NInvoiceMoney100, '{$RedReason101}', $NewPosBill102, $PrePriceNum103, $AssetBusinessTypeId104, $PromoRuleId105, $BeforePromoBillNumberId106, $ImportType107, '{$PromotionMsg108}', '{$Reconciliation109}', $ARTOTAL110, $APTOTAL111, $YSTOTAL112, $YFTOTAL113, $IsAuto114, '{$StypeID2115}', $IfConfirm116, $TransferType117, $FeeDeductType118, '{$IsYapi119}',$YapiOrderID120, '{$DeliveryAddress121}', $AddDTypeETypeType122, '{$BillTime123}', '{$IsLend124}', $Discount125, $AdPriceType126, '{$ChargeOffBtypeid127}', $ChargeOffType128, $ChargeOffTotal129, $NChargeOffTotal130, '{$cwchecker131}', $InFactRefState132, $OutFactRefState133, $InFactState134, $OutFactState135, $ShelveState136, $ShelveRefState137, $TransPortState138, $iscombinationstyle139, $progressstate140, $citestate141, '{$IsNeedTransport142}', '{$shipper143}', $totalfreight144, $ntotalfreight145, '{$transportno146}', '{$WarehouseOutDay147}', '{$StockInDeadline148}', '{$Transporterid149}','{$Transporterfullname150}', '{$shipperfullname151}', '{$shipperperson152}', '{$shippertel153}', '{$FaPiaoCode154}', $PayType155, $PosId156, '{$PromonIds157}', $isByBrandarType158, '{$PayOnTheSpot159}', $UploadPreOrder160, $PreOrderStatus161, '{$PreOrderId162}'";
////        $sql = "INSERT INTO BillIndex($field) VALUES($value) ";
////        $insertRes = sqlExcute($sql);
////        //$insertRes = 1;
////        if(!$insertRes){
////            $apiRes['apiInfo'] = '单据添加失败';
////            return $apiRes;
////        }
////        //BillIndex end
////        $insertId = sqlInsertId('BillIndex');
////        foreach ($orderGoods as $val){
////            $sql = "SELECT typeId,UserCode,FullName FROM ptype WHERE deleted=0 AND UserCode='".$val['goodsSn']."'";
////            $erpGoodsInfo = sqlQuery($sql,'row');
////            if(!$erpGoodsInfo){
////                continue;
////            }
////            $erpGoodsId = $erpGoodsInfo['typeId'];
////            //SaleBill start
////            $BillNumberId = $insertId;//BillIndex表自增id
////            $PtypeId = $erpGoodsId;//商品id
////            $Qty = $val['goodsNums'];//数量
////            $SalePrice = $val['goodsPrice'];//基本本币折前单价
////            $goodsPriceTotal = $val['goodsPrice'] * $val['goodsNums'];
////            $total = $goodsPriceTotal;//金额
////            $OutFactoryDate = date('Y-m-d H:i:s',time());
////            $TaxPrice = $val['goodsPrice'];//折扣单价
////            $TaxTotal = $goodsPriceTotal;//含税金额
////            $SaleTotal = $goodsPriceTotal;//本币折扣前金额
////            $KTypeID = $stypeInfo['TypeId'];//分支机构
////            $STypeID = $stockInfo['typeId'];//仓库id
////            $NSaleTotal = $goodsPriceTotal;//原币金额
////            $NDiscountPrice = $val['goodsPrice'];//基本单位原币折扣单价
////            $NTotal = $val['goodsPrice'];//原币折后金额
////            $NTaxPrice = $val['goodsPrice'];//基本单位原币含税单价
////            $NTaxTotal = $val['goodsPrice'];//原币价税合计
////            $UnitID = '1';//基本单位
////            $MUnitID = '1';//大单位
////            $MQty = $val['goodsNums'];//大单位数量
////            $MSalePrice = $val['goodsPrice'];//大单位单价
////            $CurMSalePrice = $val['goodsPrice'];//大单位原币折前单价
////            $CurMDiscountPrice = $val['goodsPrice'];//大单位原币折后价
////            $CargoID = $cargoInfo['CargoID'];//CargoType表自增id
////            $PriceSource = '手工输入';//价格来源
////            $ShowOrder = '1';
////            //关联表数据,先写死
////            $ETypeID = $employeeInfo['typeid'];//职员id
////            $DraFtFlag = 1;//草稿库存:0:过账|1:草稿
////            $field = "BillNumberId,PtypeId,Qty,SalePrice,total,OutFactoryDate,TaxPrice,TaxTotal,SaleTotal,KTypeID,STypeID,NSaleTotal,NDiscountPrice,NTotal,NTaxPrice,NTaxTotal,UnitID,MUnitID,MQty,MSalePrice,CurMSalePrice,CurMDiscountPrice,CargoID,PriceSource,ShowOrder,ETypeID,DraFtFlag";
////            $value = "'{$BillNumberId}','{$PtypeId}','{$Qty}','{$SalePrice}','{$total}','{$OutFactoryDate}','{$TaxPrice}','{$TaxTotal}','{$SaleTotal}','{$KTypeID}','{$STypeID}','{$NSaleTotal}','{$NDiscountPrice}','{$NTotal}','{$NTaxPrice}','{$NTaxTotal}','{$UnitID}','{$MUnitID}','{$MQty}','{$MSalePrice}','{$CurMSalePrice}','{$CurMDiscountPrice}','{$CargoID}','{$PriceSource}','{$ShowOrder}','{$ETypeID}','{$DraFtFlag}'";
////            $sql = "INSERT INTO SaleBill($field) VALUES($value)";
////            $insertRes = sqlExcute($sql);
////            //SaleBill end
////        }
////        if(!$insertRes){
////            $apiRes['apiInfo'] = '添加ERP销售出库单失败';
////            return $apiRes;
////        }else{
////            $apiRes['apiCode'] = 0;
////            $apiRes['apiState'] = 'success';
////            $apiRes['apiInfo'] = '添加ERP销售出库单失败';
////            return $apiRes;
////        }
////    }
////
////
////    /**
////     * 职员管理->添加职员
////     */
////    public function Merchantapi_User_addUser($parameter=array(),&$msg=''){
////        #检测
////        if(!$parameter){
////            $msg = '数据为空';
////            return false;
////        }
////        //$chekeRes = $this->checkLoginKey($parameter['name'].$msg);
////        $chekeRes = $this->checkLoginKeyNew($parameter['name'],$parameter['shopId']);
////        if(!$chekeRes){
////            $msg = $msg?$msg:'保存失败，账号已存在';
////            return false;
////        }
////        #保存
////        M()->startTrans();//开启事物
////        $addTime = time();
////        $data = array(
////            'name'=>$parameter['name'],
////            'pass'=>md5($parameter['pass'].$addTime),
////            'username'=>$parameter['username'],
////            'email'=>$parameter['email'],
////            'phone'=>$parameter['phone'],
////            //'status'=>$parameter['status'],
////            'addtime'=>$addTime,
////            'shopId'=>$parameter['shopId'],
////        );
////
////        $userTab = M('user');
////        $rs = $userTab->add($data);
////        //$rs  = 17;
////        #角色添加
////        $rflag = true;
////        if(isset($parameter['role']) && $rs){
////            $rflag = $this->saveUserRole($parameter['role'],$rs,$parameter['shopId']);
////        }
////        if(!$rflag){
////            M()->rollback();
////            return false;
////        }
////        //本地添加角色的同时,在ERP添加一个同样的角色
//////        $this->insertEmployee($rs);
////        M()->commit();
////        return $rs;
////    }
////
////    /*
////     *同步角色到ERP
////     * @param int $id PS:职员id
////     * */
////    public function insertEmployee($id){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(!$id){
////            return $apiRes;
////        }
////        $userInfo = M('user')->where("id='".$id."' and status != -1")->find();
////        if(!$userInfo){
////            $apiRes['apiInfo'] = '职员信息有误';
////            return $apiRes;
////        }
////        $adminMode = D('Made/Admin');
////        $response = $adminMode->insertEmployee3($userInfo['shopId'],$userInfo['id']);
////        if($response['apiCode'] == 0){
////            $apiRes['apiCode'] = 0;
////            $apiRes['apiInfo'] = '操作成功';
////            $apiRes['apiState'] = 'success';
////        }
////        return $apiRes;
////    }
////
////    /**
////     * 职员管理->删除职员
////     */
////    public function Merchantapi_User_del($parameter=array(),&$msg=''){
////        $saveData = array(
////            'status' => -1
////        );
////        $rs = M('user')->where("shopId=".$parameter['shopId'].' and id='.$parameter['id'])->save($saveData);
////        if($rs !== false){
////            //删除成功后,删除ERP数据
////            $this->deleteEmployee($parameter['id']);
////        }
////        return $rs;
////    }
////
////    /*
////     * 删除ERP职员数据
////     * @param int $id
////     * */
////    public function deleteEmployee($id){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if((int)$id <= 0){
////            $apiRes['apiInfo'] = '职员信息有误';
////            return $apiRes;
////        }
////        $sql = "UPDATE employee SET deleted=1 WHERE UserCode='".$id."'";
////        $res = sqlExcute($sql);
////        if($res !== false){
////            M('user')->where(['id'=>$id])->save(['status'=>-1]);
////            $apiRes['apiCode'] = 0;
////            $apiRes['apiInfo'] = '操作成功';
////            $apiRes['apiState'] = 'success';
////        }
////    }
////
////    /**
////     * 职员管理->编辑职员
////     */
////    public function Merchantapi_User_edit($parameter=array(),&$msg=''){
////
////        $saveData = array();
////        isset($parameter['username'])?$saveData['username']=$parameter['username']:false;
////        isset($parameter['email'])?$saveData['email']=$parameter['email']:false;
////        isset($parameter['phone'])?$saveData['phone']=$parameter['phone']:false;
////        isset($parameter['status'])?$saveData['status']=$parameter['status']:false;//0=禁用，1=启用
////        M()->startTrans();//开启事物
////        $rs = M('user')->where("shopId=".$parameter['shopId'].' and id='.$parameter['id'])->save($saveData);
////        if(isset($parameter['role']) && $parameter['id'] && $parameter['shopId']){
////            $rs= $this->saveUserRole($parameter['role'],$parameter['id'],$parameter['shopId']);
////            S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}",null);
////        }
////        if($rs === false){
////            M()->rollback();
////            $msg='角色保存失败';
////            return false;
////        }
////
////        //职员更新,同步ERP
////        $adminMode = D('Made/Admin');
////        $response = $adminMode->insertEmployee3($parameter['shopId'],$parameter['id']);
////
////        M()->commit();
////        return $rs;
////    }
////
////    /*
////     * 检测账号
////     * */
////    public function checkLoginKey($loginName='',&$msg='')
////    {
////        if(!$loginName){
////            return false;
////        }
////        $res = M('user')->where("name='{$loginName}'")->find();
////        if($res){
////            return false;
////        }
////        return true;
////    }
////
////    /*
////     * 用户角色保存
////     * */
////    public function saveUserRole($role='',$user_id='',$shopId='')
////    {
////        //检测
////        $m = M('user_role');
////        if(!$role || !$user_id || !$shopId){
////            if(!$role && $user_id && $shopId){//没有勾选，清空
////                $m->where('uid='.(int)$user_id.' and shopId='.$shopId)->delete();
////                return true;
////            }
////            return false;
////        }
////        //删除之前
////        $m->where('uid='.(int)$user_id.' and shopId='.$shopId)->delete();
////        //保存现在
////        $role_arr = array_unique(explode(',',$role));
////        foreach ($role_arr as $key => $rid) {
////            $save_data = array(
////                'uid' => (int)$user_id,
////                'shopId' => $shopId,
////                'rid' => $rid,
////            );
////            $m->add($save_data);
////        }
////        return true;
////    }
////
////    //PS:需要配合在数据中执行alter table wst_user drop index nameindex;
////    public function checkLoginKeyNew($loginName='',$shopId=0)
////    {
////        if(!$loginName || !$shopId){
////            return false;
////        }
////        $res = M('user')->where("name='{$loginName}' and status != -1 and shopId='{$shopId}'")->find();
////        if($res){
////            return false;
////        }
////        return true;
////    }
////
////    /*
////     *本地同步调拨单
////     * @param array $orderInfo PS;订单信息
////     * */
////    public function syncOutBill($request){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        $shopId = $request['shopId'];
////        $houseInfo = $this->checkShopWareHouse($shopId);
////        //验证仓库
////        if(empty($houseInfo['id'])){
////            return $houseInfo;
////        }
////        $otpurchaseId = trim($request['otpurchaseId'],',');
////        $otpurchaseIdArr = explode(',',$otpurchaseId);
////        $otpurchaseList = M('allocationclass al','is_')
////            ->join("left join is_merchant m on m.id=al.merchant")
////            ->join("left join is_user u on u.id=al.user")
////            ->where(['al.merchant'=>$houseInfo['merchant'],'al.id'=>["IN",$otpurchaseIdArr]])
////            ->field('al.*,m.name,u.name as username')
////            ->select();
////        //SELECT al.*,m.name,u.name as username FROM is_allocationclass al left join is_merchant m on m.id=al.merchant left join is_user u on u.id=al.user  WHERE al.merchant = '55' AND al.id IN ('68','89')
////        if(!$otpurchaseList){
////            $apiRes['apiInfo'] = '调拨单数据获取失败';
////            return $apiRes;
////        }
////        foreach ($otpurchaseList as $prekey=>$prevalue){
////            $otpurchaseList[$prekey]['allocationinfo'] = [];
////            $otpurchaseList[$prekey]['shopId'] = $shopId;
////            $allocationinfo = M('allocationinfo info','is_')
////                ->join("left join is_goods g on g.id=info.goods")
////                ->where(['info.pid'=>$prevalue['id']])
////                ->select();
////            foreach ($allocationinfo as $key=>&$val){
////                $val['room'] = M('room r','is_') //所属仓储
////                ->join("left join is_warehouse w on w.id=r.warehouse")
////                    ->where("r.id='".$val['room']."'")
////                    ->find();
////                $val['toroom'] = M('room r','is_') //调拨仓储
////                ->join("left join is_warehouse w on w.id=r.warehouse")
////                    ->where("r.id='".$val['toroom']."'")
////                    ->find();
////                $imgs = json_decode($val['imgs']);
////                //var_dump($imgs);exit;
////                /*foreach ($imgs as $k=>$v){
////                    $imgs[$k] = C("JXC_WEB").$v;
////                }*/
////                $val['imgs'] = $imgs;
////            }
////            unset($val);
////            if($allocationinfo){
////                $otpurchaseList[$prekey]['allocationinfo'] = $allocationinfo;
////            }
////            //主单据
////            //出库
////            $this->insertBillIndex($otpurchaseList[$prekey]);
////            //入库
////            $this->insertBillIndex($otpurchaseList[$prekey],2);
////
////        }
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////
////    /*
////     *向主单据表BillIndex插入数据
////     *@param array $data
////     *@param int type PS:数据类型(1:出库|2:入库)
////     * */
////    public function insertBillIndex($data,$type=1){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(!is_array($data) || !$data){
////            $apiRes['apiInfo'] = '数据不正确';
////            return $apiRes;
////        }
////        //$tableName,$prefix='',$db=''
////        //分支机构信息
////        $stypeShopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$data['shopId']])->find();
////        $sql = "SELECT TypeId,parid,Sid FROM Stype WHERE Sid='".$stypeShopRelation['Sid']."' AND deleted=0 ";
////        $stypeInfo = sqlQuery($sql,'row');
////        if(!$stypeInfo){
////            $apiRes['apiInfo'] = '分支机构信息有误';
////            return $apiRes;
////        }
////        //本地店铺信息
////        $shopInfo = M('shops')->where(['shopId'=>$stypeShopRelation['shopId'],'shopFlag'=>1])->field("shopId,shopSn,shopName")->find();
////        //仓库信息
////        $sql = "SELECT typeId,parid,Kid,STypeID FROM Stock WHERE STypeID='".$stypeInfo['TypeId']."' AND deleted=0 ";
////        $stockInfo = sqlQuery($sql,'row');
////        if(!$stockInfo){
////            $apiRes['apiInfo'] = '仓库信息有误';
////            return $apiRes;
////        }
////        //默认货位信息
////        $sql = "SELECT CargoID,UserCode,FullName FROM CargoType WHERE KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
////        $cargoInfo = sqlQuery($sql,'row');
////        if(!$cargoInfo){
////            $apiRes['apiInfo'] = '货位信息有误';
////            return $apiRes;
////        }
////        //默认部门
////        $sql = "SELECT typeid,parid,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
////        $DepartmentInfo = sqlQuery($sql,'row');
////        //默认职员
////        $defalutUserCode = $DepartmentInfo['rec'].'-001';
////        $sql = "SELECT typeid,parid,FullName FROM employee WHERE DtypeID='{$DepartmentInfo['typeid']}' AND STypeID='{$stypeInfo['TypeId']}' AND UserCode='{$defalutUserCode}'";
////        $employeeInfo = sqlQuery($sql,'row');
////        //商品信息
////        $trueGoods = [];
////        $allocationInfo = $data['allocationinfo'];
////        $totalMoneyQ = 0;
////        foreach ($allocationInfo as $key=>$value){
////            $goodsSn = M('goods','is_')->where(['id'=>$value['room']['goods']])->getField('number');
////            //店铺1(出库)
////            $shopWhere['shopSn'] = $value['room']['number'];
////            $shopWhere['shopFlag'] = 1;
////            $field = 'shopId,shopName,shopSn';
////            $shopInfoQ = $this->getShopInfo($shopWhere,$field);
////            $goodsWhere = ['goodsSn'=>$goodsSn,'shopId'=>$shopInfoQ['shopId'],'goodsFlag'=>1];
////            $goodsInfoQ = M('goods')->where($goodsWhere)->field('goodsSn,goodsName,goodsId,shopPrice')->find();
////            $allocationInfo[$key]['room']['shopInfo'] = $shopInfoQ;
////            $allocationInfo[$key]['room']['goodsInfo'] = $goodsInfoQ;
////            $totalMoneyQ += $goodsInfoQ['shopPrice'] * (int)$value['nums'];
////            //店铺2(入库)
////            $shopWhere['shopSn'] = $value['toroom']['number'];
////            $shopWhere['shopFlag'] = 1;
////            $field = 'shopId,shopName,shopSn';
////            $shopInfoQ = $this->getShopInfo($shopWhere,$field);
////            $goodsWhere = ['goodsSn'=>$goodsSn,'shopId'=>$shopInfoQ['shopId'],'goodsFlag'=>1];
////            $goodsInfoQ = M('goods')->where($goodsWhere)->field('goodsSn,goodsName,goodsId,shopPrice')->find();
////            $allocationInfo[$key]['toroom']['shopInfo'] = $shopInfoQ;
////            $allocationInfo[$key]['toroom']['goodsInfo'] = $goodsInfoQ;
////            //验证商品,如果商品在ERP中不存在,就不执行下面的程序了
////            $sql = "SELECT typeId,FullName,Pid,UserCode FROM Ptype WHERE UserCode='{$goodsSn}' AND deleted=0 ";
////            $erGoodsInfo = sqlQuery($sql,'row');
////            if(!$erGoodsInfo){
////                $apiRes['apiInfo'] = '商品信息有误';
////                return $apiRes;
////            }
////        }
////        $countGoods = count($allocationInfo);
////        $time = date('Y-m-d H:i:s',time());
////        $sql = "SELECT newid()";
////        $newId = sqlQuery($sql,'row');
////        $newId = $newId[''];
////        //该字段列表直接去复制表中正确的数据,转成SQL语句,比较快速,和值的对应正确率较高
////        $field = "[BillDate], [BillCode], [BillType], [Comment], [atypeid], [ptypeid], [btypeid], [etypeid], [ktypeid], [ktypeid2], [ifcheck], [checke], [totalmoney], [totalinmoney], [totalqty], [tax], [period], [explain], [RedWord], [draft], [OrderId], [waybillcode], [goodsnumber], [packway], [TEL], [Uploaded], [OFF_ID], [TeamNo], [AlertDay], [CompleteTotal], [ID], [Assessor], [IfAudit], [Audit_explain], [IfStopMoney], [preferencemoney], [DTypeId], [JF], [VipCardID], [vipCZMoney], [jsStyle], [jsState], [KTypeID3], [LinkMan], [LinkTel], [LinkAddr], [wxManID], [JieJianBillNumberID], [JieJianID], [JieJianState], [BaoXiuBillNumberID], [BaoXiuID], [BuyDate], [Serial], [wxTotal], [ErrDes], [ifHideJieJian], [ifWX], [ifYearBill], [CWCheck], [IfBargainOn], [LastUpdateTime], [Poster], [STypeID], [FromBillNumberID], [CostSale], [IfMulAccount], [OtherInOutType], [PosBillType], [checkTime], [posttime], [updateE], [ShareMode], [IsFreightMoney], [FreightAddr], [FreightPerson], [FreightTel], [IfInvoice], [TransportId], [UF1], [UF2], [UF3], [UF4], [UF5], [ATID], [DealBTypeID], [CID], [Rate], [NTotalMoney], [NTotalInMoney], [NPreferenceMoney], [NVIPCZMoney], [IsIni], [ATypeID1], [NCompleteTotal], [billproperty], [TallyId], [IfCargo], [InvoiceMoney], [NInvoiceMoney], [RedReason], [NewPosBill], [PrePriceNum], [AssetBusinessTypeId], [PromoRuleId], [BeforePromoBillNumberId], [ImportType], [PromotionMsg], [Reconciliation], [ARTOTAL], [APTOTAL], [YSTOTAL], [YFTOTAL], [IsAuto], [StypeID2], [IfConfirm], [TransferType], [FeeDeductType], [IsYapi], [YapiOrderID], [DeliveryAddress], [AddDTypeETypeType], [BillTime], [IsLend], [Discount], [AdPriceType], [ChargeOffBtypeid], [ChargeOffType], [ChargeOffTotal], [NChargeOffTotal], [cwchecker], [InFactRefState], [OutFactRefState], [InFactState], [OutFactState], [ShelveState], [ShelveRefState], [TransPortState], [iscombinationstyle], [progressstate], [citestate], [IsNeedTransport], [shipper], [totalfreight], [ntotalfreight], [transportno], [WarehouseOutDay], [StockInDeadline], [Transporterid], [Transporterfullname], [shipperfullname], [shipperperson], [shippertel], [FaPiaoCode], [PayType], [PosId], [PromonIds], [isByBrandarType], [PayOnTheSpot], [UploadPreOrder], [PreOrderStatus], [PreOrderId]";
////        $explodeTime = explode(' ',$time);
////        $BillDate1 = date('Y-m-d').' 00:00:00';//此参数勿改,会影响单据展示
////        $BillCode2 = $data['number'];
////        if($type == 1){
////            $BillCode2 .= '-CK';
////        }else{
////            $BillCode2 .= '-RK';
////        }
////        $BillType3 = $type==2?188:187;
////        $Comment4 = '';
////        $atypeid5 = '';
////        $ptypeid6 = '';//商品id
////        //$btypeid7 = '00002000640000200001';//结算单位#关联表,先写死
////        $btypeid7 = '';
////        $etypeid8 = $employeeInfo['typeid'];//本地系统订单没有这块逻辑,先用默认职员代替
////        $ktypeid9 = $stockInfo['typeId'];//仓库id
////        $ktypeid210 = '';
////        $ifcheck11 = 'f';
////        $checke12 = '00001';//制单人#关联表,先写死
////        $totalmoney13 = $totalMoneyQ;
////        $totalinmoney14 = .0000000000;
////        $totalqty15 = $countGoods;
////        $tax16 = .0000000000;
////        $period17 = 1;
////        $explain18 = '';
////        $RedWord19 = 0;
////        $draft20 = '1';
////        $OrderId21 = 0;
////        $waybillcode22 = '';
////        $goodsnumber23 = '';
////        $packway24 = '';
////        $TEL25 = '';
////        $Uploaded26 = '0';
////        $OFF_ID27 = $newId;
////        $TeamNo28 = NULL;
////        $AlertDay29 = 9999;
////        $CompleteTotal30 = .0000000000;
////        $ID31 = 0;
////        $Assessor32 = '';
////        $IfAudit33 = ' ';
////        $Audit_explain34 = '';
////        $IfStopMoney35 = '0';
////        $preferencemoney36 = '.0000000000';
////        $DTypeId37 = $DepartmentInfo['typeid'];
////        $JF38 = 0;
////        $VipCardID39 = -1;
////        $vipCZMoney40 = .0000000000;
////        $jsStyle41 = '0';
////        $jsState42 = '0';
////        $KTypeID343 = '';
////        $LinkMan44 = '';
////        $LinkTel45 = '';
////        $LinkAddr46 = '';
////        $wxManID47 = '';
////        $JieJianBillNumberID48 = -1;
////        $JieJianID49 = -1;
////        $JieJianState50 = -1;
////        $BaoXiuBillNumberID51 = -1;
////        $BaoXiuID52 = -1;
////        $BuyDate53 = '';
////        $Serial54 = '';
////        $wxTotal55 = .0000000000;
////        $ErrDes56 = '';
////        $ifHideJieJian57 = 0;
////        $ifWX58 = 0;
////        $ifYearBill59 = 0;
////        $CWCheck60 = 0;
////        $IfBargainOn61 = 0;
////        $LastUpdateTime62 = $time;
////        $Poster63 = '';
////        $STypeID64 = $stypeInfo['TypeId'];//分支机构id
////        $FromBillNumberID65 = 0;
////        $CostSale66 = '0';
////        $IfMulAccount67 = 0;
////        $OtherInOutType68 = -1;
////        $PosBillType69 = 0;
////        $checkTime70 = $time;
////        $posttime71 = NULL;
////        $updateE72 = '00001';//没注释,不清楚
////        $ShareMode73 = 0;
////        $IsFreightMoney74 = 0;
////        $FreightAddr75 = '';
////        $FreightPerson76 = '';
////        $FreightTel77 = 0;
////        $IfInvoice78 = 0;
////        $TransportId79 = '0';
////        $UF180 = '';
////        $UF281 = '';
////        $UF382 = '';
////        $UF483 = '';
////        $UF584 = '';
////        $ATID85 = 0;
////        //$DealBTypeID86 = '00002000640000200001';//往来单位#关联表,先写死
////        $DealBTypeID86 = '';//往来单位
////        $CID87 = 1;
////        $Rate88 = 1;
////        $NTotalMoney89 = $totalMoneyQ;
////        $NTotalInMoney90 = .0000000000;
////        $NPreferenceMoney91 = .0000000000;
////        $NVIPCZMoney92 = .0000000000;
////        $IsIni93 = '0';
////        $ATypeID194 = '';
////        $NCompleteTotal95 = .0000000000;
////        $billproperty96 = -1;
////        $TallyId97 = '';
////        $IfCargo98 = '0';
////        $InvoiceMoney99 = .0000000000;
////        $NInvoiceMoney100 = .0000000000;
////        $RedReason101 = '';
////        $NewPosBill102 = 0;
////        $PrePriceNum103 = 1;
////        $AssetBusinessTypeId104 = 0;
////        $PromoRuleId105 = 0;
////        $BeforePromoBillNumberId106 = 0;
////        $ImportType107 = 0;
////        $PromotionMsg108 = '';//N''
////        $Reconciliation109 = 'f';
////        $ARTOTAL110 = .0000000000;
////        $APTOTAL111 = .0000000000;
////        $YSTOTAL112 = .0000000000;
////        $YFTOTAL113 = .0000000000;
////        $IsAuto114 = 0;
////        $StypeID2115 = '';
////        $IfConfirm116 = 0;
////        $TransferType117 = 1;
////        $FeeDeductType118 = 0;
////        $IsYapi119 = '0';
////        $YapiOrderID120 = 0;
////        $DeliveryAddress121 = '';
////        $AddDTypeETypeType122 = 0;
////        $BillTime123 = $explodeTime[1];
////        $IsLend124 = '0';
////        $Discount125 = 1;//扣率
////        $AdPriceType126 = 0;
////        $ChargeOffBtypeid127 = '';
////        $ChargeOffType128 = 0;
////        $ChargeOffTotal129 = .0000000000;
////        $NChargeOffTotal130 = .0000000000;
////        $cwchecker131 = '';
////        $InFactRefState132 = 0;
////        $OutFactRefState133 = 0;
////        $InFactState134 = 0;
////        $OutFactState135 = 0;
////        $ShelveState136 = 0;
////        $ShelveRefState137 = 0;
////        $TransPortState138 = 0;
////        $iscombinationstyle139 = 0;
////        $progressstate140 = 0;
////        $citestate141 = 0;
////        $IsNeedTransport142 = '0';
////        $shipper143 = NULL;
////        $totalfreight144 = .0000000000;
////        $ntotalfreight145 = .0000000000;
////        $transportno146 = NULL;
////        $WarehouseOutDay147 = '9999';
////        $StockInDeadline148 = '9999';
////        $Transporterid149 = NULL;
////        $Transporterfullname150 = NULL;
////        $shipperfullname151 = NULL;
////        $shipperperson152 = NULL;
////        $shippertel153 = NULL;
////        $FaPiaoCode154 = '';
////        $PayType155 = 0;
////        $PosId156 = 0;
////        $PromonIds157 = '';
////        $isByBrandarType158 = 0;
////        $PayOnTheSpot159 = '';
////        $UploadPreOrder160 = 0;
////        $PreOrderStatus161 = 0;
////        $PreOrderId162 = '';
////
////        $value = "'{$BillDate1}', '{$BillCode2}', $BillType3, '{$Comment4}', '{$atypeid5}', '{$ptypeid6}', '{$btypeid7}', '{$etypeid8}', '{$ktypeid9}', '{$ktypeid210}', '{$ifcheck11}', '{$checke12}', $totalmoney13, $totalinmoney14, $totalqty15, $tax16, $period17, '{$explain18}', $RedWord19, '{$draft20}', $OrderId21, '{$waybillcode22}', '{$goodsnumber23}', '{$packway24}', '{$TEL25}', '{$Uploaded26}', '{$OFF_ID27}', '{$TeamNo28}', $AlertDay29, $CompleteTotal30, $ID31, '{$Assessor32}', '{$IfAudit33}', '{$Audit_explain34}', '{$IfStopMoney35}', '{$preferencemoney36}', '{$DTypeId37}', $JF38, $VipCardID39, $vipCZMoney40, '{$jsStyle41}', '{$jsState42}', '{$KTypeID343}', '{$LinkMan44}', '{$LinkTel45}', '{$LinkAddr46}', '{$wxManID47}', $JieJianBillNumberID48, $JieJianID49, $JieJianState50, $BaoXiuBillNumberID51, $BaoXiuID52, '{$BuyDate53}', '{$Serial54}', $wxTotal55, '{$ErrDes56}', $ifHideJieJian57, $ifWX58, $ifYearBill59, $CWCheck60, $IfBargainOn61, '{$LastUpdateTime62}', '{$Poster63}', '{$STypeID64}', $FromBillNumberID65, '{$CostSale66}', $IfMulAccount67, $OtherInOutType68, $PosBillType69, '{$checkTime70}', '{$posttime71}', '{$updateE72}', $ShareMode73, $IsFreightMoney74, '{$FreightAddr75}', '{$FreightPerson76}', $FreightTel77, $IfInvoice78, '{$TransportId79}', '{$UF180}', '{$UF281}', '{$UF382}', '{$UF483}', '{$UF584}', $ATID85, '{$DealBTypeID86}', $CID87, $Rate88, $NTotalMoney89, $NTotalInMoney90, $NPreferenceMoney91, $NVIPCZMoney92, '{$IsIni93}', '{$ATypeID194}', $NCompleteTotal95, $billproperty96, '{$TallyId97}', '{$IfCargo98}', $InvoiceMoney99, $NInvoiceMoney100, '{$RedReason101}', $NewPosBill102, $PrePriceNum103, $AssetBusinessTypeId104, $PromoRuleId105, $BeforePromoBillNumberId106, $ImportType107, '{$PromotionMsg108}', '{$Reconciliation109}', $ARTOTAL110, $APTOTAL111, $YSTOTAL112, $YFTOTAL113, $IsAuto114, '{$StypeID2115}', $IfConfirm116, $TransferType117, $FeeDeductType118, '{$IsYapi119}',$YapiOrderID120, '{$DeliveryAddress121}', $AddDTypeETypeType122, '{$BillTime123}', '{$IsLend124}', $Discount125, $AdPriceType126, '{$ChargeOffBtypeid127}', $ChargeOffType128, $ChargeOffTotal129, $NChargeOffTotal130, '{$cwchecker131}', $InFactRefState132, $OutFactRefState133, $InFactState134, $OutFactState135, $ShelveState136, $ShelveRefState137, $TransPortState138, $iscombinationstyle139, $progressstate140, $citestate141, '{$IsNeedTransport142}', '{$shipper143}', $totalfreight144, $ntotalfreight145, '{$transportno146}', '{$WarehouseOutDay147}', '{$StockInDeadline148}', '{$Transporterid149}','{$Transporterfullname150}', '{$shipperfullname151}', '{$shipperperson152}', '{$shippertel153}', '{$FaPiaoCode154}', $PayType155, $PosId156, '{$PromonIds157}', $isByBrandarType158, '{$PayOnTheSpot159}', $UploadPreOrder160, $PreOrderStatus161, '{$PreOrderId162}'";
////        $sql = "INSERT INTO BillIndex($field) VALUES($value) ";
////        $insertRes = sqlExcute($sql);
////        //$insertRes = 1;//需要注释
////        if(!$insertRes){
////            $apiRes['apiInfo'] = '单据添加失败';
////            return $apiRes;
////        }
////        //BillIndex end
////        $insertId = sqlInsertId('BillIndex');
////        //写入出库入库明细表
////        $res = $this->syncERPSTypeMoveBill($insertId,$allocationInfo);
////        return $res;
////    }
////
////    /*
////     * 获取分支机构详情
////     *@param string $field
////     *@param string $where
////     * */
////    public function getStypeInfo($where,$field='*'){
////        if(empty($where)){
////            $where = " deleted=0 ";
////        }
////        $sql = "SELECT $field FROM SType WHERE $where ";
////        $stypeInfo = sqlQuery($sql,'row');
////        if(empty($stypeInfo)){
////            return [];
////        }
////        return $stypeInfo;
////    }
////
////    /*
////     * 获取仓详情
////     *@param string $field
////     *@param string $where
////     * */
////    public function getStockInfo($where,$field='*'){
////        if(empty($where)){
////            $where = " deleted=0 ";
////        }
////        $sql = "SELECT $field FROM Stock WHERE $where ";
////        $stypeInfo = sqlQuery($sql,'row');
////        if(empty($stypeInfo)){
////            return [];
////        }
////        return $stypeInfo;
////    }
////
////    /*
////     * 写入ERP调拨出库入库表
////     *@param int $BillNumberId PS:主单据id
////     *@param array $data PS:本地调拨信息详情
////     * @param int $type PS:类型:1:出库|2:入库
////     * */
////    public function syncERPSTypeMoveBill($BillNumberId,$data,$type=1){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(!is_array($data) || empty($data) || empty($BillNumberId)){
////            $apiRes['apiInfo'] = '参数不正确';
////            return $apiRes;
////        }
////
////        $sql = "SELECT BillNumberId,STypeID,ktypeid,ktypeid2 FROM BillIndex WHERE BillNumberId='{$BillNumberId}' ";
////        $billInfo = sqlQuery($sql,'row');
////        foreach ($data as $key=>$val){
////            //后加start 主要为了获取出库和入库的信息
////            //获取入库相关信息
////            $toShopId = $val['toroom']['shopInfo']['shopId'];
////            $toShopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$toShopId,'isDelete'=>1])->find();
////            //机构
////            $where = "Sid='{$toShopRelation['Sid']}' AND deleted=0 ";
////            $toStypeInfo = $this->getStypeInfo($where);
////            //仓
////            $where = "STypeID='{$toStypeInfo['TypeId']}' AND deleted=0 ";
////            $toStockInfo = $this->getStockInfo($where);
////            if(!$toShopRelation || !$toStypeInfo || !$toStockInfo){
////                $apiRes['apiInfo'] = "入库信息有误";
////                return $apiRes;
////            }
////            //获取出库相关信息
////            $shopId = $val['room']['shopInfo']['shopId'];
////            $shopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
////            //机构
////            $where = "Sid='{$shopRelation['Sid']}' AND deleted=0 ";
////            $stypeInfo = $this->getStypeInfo($where);
////            //仓
////            $where = "STypeID='{$stypeInfo['TypeId']}' AND deleted=0 ";
////            $stockInfo = $this->getStockInfo($where);
////            if(!$shopRelation || !$stypeInfo || !$stockInfo){
////                $apiRes['apiInfo'] = "出库信息有误";
////                return $apiRes;
////            }
////            //后加end
////            $info = $val['room'];
////            if($type == 2){
////                $info = $val['toroom'];
////            }
////            $goodsInfo = $info['goodsInfo'];
////            $shopInfo = $info['shopInfo'];
////            $sql = "SELECT typeId,UserCode,FullName FROM ptype WHERE deleted=0 AND UserCode='".$goodsInfo['goodsSn']."'";
////            $erpGoodsInfo = sqlQuery($sql,'row');
////            if(!$erpGoodsInfo){
////                continue;
////            }
////            //字段都列在这里
////            //STypeMoveBill start
////            $BillNumberId1 = $BillNumberId;
////            $BillType2 = 187;
////            $Comment3 = "";
////            $PtypeId4 = $erpGoodsInfo['typeId'];
////            $Qty5 = $val['nums'];
////            $Price6 = $goodsInfo['shopPrice'];
////            $Total7 = $val['nums'] * $shopInfo['shopPrice'];
////            $Jobnumber8 = "0";
////            $GoodsNo9 = 0;
////            $OutFactoryDate10 = '';
////            $IsUnit211 = 0;
////            $Serial12 = "";
////            $InputCostPrice13 = .0000000000;
////            $GoodsOrder14 = "-1";
////            $GoodsNumber15 = "";
////            $ProduceDate16 = "";
////            $ValidDate17 = "";
////            $GoodsBTypeID18 = "";
////            $GoodsCostPrice19 = .0000000000;
////            $GoodsCostTotal20 = .0000000000;
////            $HandZeroCost21 = 0;
////            $Stypeid22 = $billInfo['STypeID'];
////            $Ktypeid23 = $billInfo['ktypeid'];
////            $Ktypeid224 = $toStockInfo['typeId'];
////            $FromBillNumberID25 = 0;
////            $FromBillID26 = 0;
////            $UnitID27 = 1;
////            $NUnitID28 = 0;
////            $NQty29 = .0000000000;
////            $UnitRate30 = .0000000000;
////            $NUnitMsg31 = "";
////            $MUnitID32 = 0;
////            $MQty33 = 1.0000000000;
////            $MUnitRate34 = 1.0000000000;
////            $MUnitMsg35 = "";
////            $MPrice36 = .0000000000;
////            $InCargoID37 = 3;
////            $OutCargoID38 = 37;
////            $ItemID39 = 0;
////            $IsCombined40 = 0;
////            $ShowOrder41 = 1;
////            $OutFactQty42 = .0000000000;
////            $InFactQty43 = .0000000000;
////            $InflowQty44 = .0000000000;
////            $PickedQty45 = .0000000000;
////            $SendedQty46 = .0000000000;
////            $DraftFlag47 = "1";
////            $IsIniFlag48 = "0";
////            $AllotID49 = 0;
////            //STypeMoveBill end
////            $field = "[BillNumberId], [BillType], [Comment], [PtypeId], [Qty], [Price], [Total], [Jobnumber], [GoodsNo], [OutFactoryDate], [IsUnit2], [Serial], [InputCostPrice], [GoodsOrder], [GoodsNumber], [ProduceDate], [ValidDate], [GoodsBTypeID], [GoodsCostPrice], [GoodsCostTotal], [HandZeroCost], [Stypeid], [Ktypeid], [Ktypeid2], [FromBillNumberID], [FromBillID], [UnitID], [NUnitID], [NQty], [UnitRate], [NUnitMsg], [MUnitID], [MQty], [MUnitRate], [MUnitMsg], [MPrice], [InCargoID], [OutCargoID], [ItemID], [IsCombined], [ShowOrder], [OutFactQty], [InFactQty], [InflowQty], [PickedQty], [SendedQty], [DraftFlag], [IsIniFlag], [AllotID]";
////            $value = "'{$BillNumberId1}', $BillType2, '{$Comment3}', '{$PtypeId4}', {$Qty5}, {$Price6}, {$Total7}, '{$Jobnumber8}', {$GoodsNo9}, '{$OutFactoryDate10}', {$IsUnit211}, '{$Serial12}', {$InputCostPrice13}, $GoodsOrder14, '{$GoodsNumber15}', '{$ProduceDate16}', '{$ValidDate17}', '{$GoodsBTypeID18}', {$GoodsCostPrice19}, {$GoodsCostTotal20}, {$HandZeroCost21}, '{$Stypeid22}', '{$Ktypeid23}', '{$Ktypeid224}', {$FromBillNumberID25}, {$FromBillID26}, {$UnitID27}, {$NUnitID28}, {$NQty29}, {$UnitRate30}, '{$NUnitMsg31}', {$MUnitID32}, {$MQty33}, {$MUnitRate34}, '{$MUnitMsg35}', {$MPrice36}, {$InCargoID37}, {$OutCargoID38}, {$ItemID39}, {$IsCombined40}, {$ShowOrder41}, {$OutFactQty42}, {$InFactQty43}, {$InflowQty44}, {$PickedQty45}, {$SendedQty46}, '{$DraftFlag47}', '{$IsIniFlag48}', {$AllotID49}";
////            $sql = "INSERT INTO STypeMoveBill($field) VALUES($value)";
////            $res = sqlExcute($sql);
////            if(!$res){
////                $apiRes['apiInfo'] = "添加商品信息时出错";
////                return $apiRes;
////            }
////        }
////        //更改主单据的ktypeid2信息
////        $sql = "UPDATE BillIndex SET ktypeid2='{$toStockInfo['typeId']}' WHERE BillNumberId='{$BillNumberId}'";
////        sqlExcute($sql);
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////    /*
////     * 获取店铺信息
////     * @param array|string $where
////     * @param string $field
////     * */
////    public function getShopInfo($where,$field='*'){
////        if(empty($where)){
////            return [];
////        }
////        $shopInfo = M('shops')->where($where)->field($field)->find();
////        return $shopInfo;
////    }
////
////    /*
////     * 商户端获取相关调拨单详情
////     * */
////    public function getShopOtpurchaseInfo($param){
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '获取数据失败';
////        $apiRet['apiState'] = 'error';
////        $shopId = $param['shopId'];
////        $houseInfo = $this->checkShopWareHouse($shopId);
////        if(empty($houseInfo['id'])){
////            return $houseInfo;
////        }
////        $info = M('allocationclass al','is_')
////            ->join("left join is_merchant m on m.id=al.merchant")
////            ->join("left join is_user u on u.id=al.user")
////            ->where(['al.merchant'=>$houseInfo['merchant'],'al.id'=>$param['allocationclassId']])
////            ->field('al.*,m.name,u.name as username')
////            ->find();
////        $info['storageName'] = $this->getAllicationTypeName($info['type']);
////        $info['timeDate'] = date("Y-m-d",$info['time']);
////        if(!$info){
////            return $apiRet;
////        }
////        $info['allocationinfo'] = [];
////        $allocationinfo = M('allocationinfo info','is_')
////            ->join("left join is_goods g on g.id=info.goods")
////            ->where(['info.pid'=>$info['id']])
////
////            ->select();
////        foreach ($allocationinfo as $key=>&$val){
////            $val['room'] = M('room r','is_') //所属仓储
////            ->join("left join is_warehouse w on w.id=r.warehouse")
////                ->where("r.id='".$val['room']."'")
////                ->find();
////            $val['toroom'] = M('room r','is_') //调拨仓储
////            ->join("left join is_warehouse w on w.id=r.towarehouse")
////                ->where("r.id='".$val['toroom']."'")
////                ->find();
////            $imgs = json_decode($val['imgs']);
////            foreach ($imgs as $k=>$v){
////                $imgs[$k] = C("JXC_WEB").$v;
////            }
////            $val['imgs'] = $imgs;
////
////        }
////        unset($val);
////        if($allocationinfo){
////            $info['allocationinfo'] = $allocationinfo;
////        }
////        $apiRet['apiCode'] = 0;
////        $apiRet['apiInfo'] = '获取数据成功';
////        $apiRet['apiState'] = 'success';
////        $apiRet['apiData'] = $info;
////        return $apiRet;
////    }
////
////    /*
////     * 验证云仓是否有和店铺关联的仓库
////     * @param int $shopId
////     * */
////    public function checkShopWareHouse($shopId){
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '操作失败,未找到相关联的仓库';
////        $apiRet['apiState'] = 'error';
////        $shopInfo = M('shops')->where(['shopId'=>$shopId,'shopFlag'=>1])->find();
////        $shopConfig = M('shop_configs')->where(['shopId'=>$shopInfo['shopId']])->find();
////        if($shopConfig['isMainWarehouse'] != 1){
////            $apiRet['apiInfo'] = '操作失败,请先开启总仓';
////            return $apiRet;
////        }
////        $houseInfo = M('warehouse','is_')->where(['number'=>$shopInfo['shopSn']])->find();
////        $userInfo = M('user','is_')->where(['id'=>$shopConfig['mainWarehouseUserId']])->find();
////        $houseInfo['userId'] = $userInfo['id']; //is_user表id
////        $houseInfo['username'] = $userInfo['name']; //is_user表id
////        $houseInfo['merchant'] = $userInfo['merchant']; //is_merchant表id
////        if(!$houseInfo){
////            return $apiRet;
////        }
////        return $houseInfo;
////    }
////
////    /**
////     * 商家发货配送订单
////     */
////    public function Merchantapi_Orders_shopOrderDelivery ($obj){
////
////        $userId = (int)$obj["userId"];
////        $orderId = (int)$obj["orderId"];
////        $shopId = (int)$obj["shopId"];
////        $weightGJson = $obj["weightGJson"];
////        $source = I('source');
////        //$deliverType = (int)$obj["deliverType"];
////        $data = array();
////        $rsdata = array();
////        $sql = "SELECT orderId,orderNo,orderStatus,deliverType,isSelf,realTotalMoney FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
////        $rsv = $this->queryRow($sql);
////        //记录需要退款的商品 在确认收货的时候自动退款
////        if(count($weightGJson) > 0){
////            $order_goods = M('order_goods');
////            $goods_pricediffe = M('goods_pricediffe');
////            $orders = M('orders');
////            $mod_goods = M('goods');
////            //验证此笔订单是否包含这些商品
////            for($i=0;$i<count($weightGJson);$i++){
////                $data_order_goods = $order_goods->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find();
////                if(!$data_order_goods){
////                    $rsdata["status"] = -1;
////                    $rsdata["info"] = '当前订单和商品不匹配';
////                    return $rsdata;
////                }
////                //判断本订单的商品是否已经记录过了
////                if($goods_pricediffe->where("goodsId = '{$weightGJson[$i]['goodsId']}' and orderId = '{$orderId}'")->find()){
////                    $rsdata["status"] = -1;
////                    $rsdata["info"] = '本订单的商品已经处理过了';
////                    return $rsdata;
////                }
////                $orders_data = $orders->where("orderId = '{$orderId}'")->find();
////                $goods_this_data = $mod_goods->where("SuppPriceDiff=1 and goodsId = '{$weightGJson[$i]['goodsId']}'")->find();
////                //$totalMoney_order = $data_order_goods['goodsPrice'] * (int)$data_order_goods['goodsNums'];//当前商品总价 原来
////                //$totalMoney_order1 = $data_order_goods['goodsPrice'] / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价
////                $totalMoney_order = $data_order_goods['goodsPrice'] * (int)$data_order_goods['goodsNums'];//当前商品总价 原来
////                $totalMoney_order1 = $data_order_goods['goodsPrice'] / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价
////                /* $totalMoney_order = goodsPayN($orderId,$weightGJson[$i]['goodsId'],$userId); //单商品总价
////                 $totalMoney_order1 = $totalMoney_order / (int)$goods_this_data['weightG'] * $weightGJson[$i]['goodWeight'];//根据重量得出的总价*/
////                //如果重量不够 补差价
////                if($totalMoney_order > $totalMoney_order1 && $totalMoney_order1 > 0){
////                    $add_data['orderId'] = $orderId;
////                    $add_data['tradeNo'] = $orders_data['tradeNo']?$orders_data['tradeNo']:0;
////                    $add_data['goodsId'] = $weightGJson[$i]['goodsId'];
////                    //$add_data['money'] = (($totalMoney_order*1000) - ($totalMoney_order1*1000)) / 1000;
////                    //$add_data['money'] = $data_order_goods['goodsPrice']-$totalMoney_order1;
////                    //$add_data['money'] = $data_order_goods['goodsPrice']-$totalMoney_order1;//修复差价
////                    $add_data['money'] = $totalMoney_order-$totalMoney_order1;//修复差价
////                    $add_data['addTime'] = date('Y-m-d H:i:s');
////                    $add_data['userId'] = $orders_data['userId'];
////                    $add_data['weightG'] = $weightGJson[$i]['goodWeight'];
////                    $add_data['goosNum'] = $data_order_goods['goodsNums'];
////                    $add_data['unitPrice'] = $data_order_goods['goodsPrice'];
////                    //写入退款记录
////                    if(!$goods_pricediffe->add($add_data)){
////                        $rsdata["status"] = -1;
////                        $rsdata["info"] = '退款记录写入失败';
////                        return $rsdata;
////                    }
////
////                    $log_orders = M("log_orders");
////                    $data["orderId"] =  $orderId;
////                    $data["logContent"] =  $data_order_goods['goodsName'] . '#补差价：' . sprintf("%.2f",substr(sprintf("%.3f",$add_data['money']), 0, -1)) .'元。确认收货后返款！';
////                    $data["logUserId"] =  $orders_data['userId'];
////                    $data["logType"] =  "0";
////                    $data["logTime"] =  date("Y-m-d H:i:s");
////                    $log_orders->add($data);
////                }
////            }
////        }
////        /* 	$people = array('2','10');
////            if(!in_array($rsv["orderStatus"],$people) and !in_array($rsv["orderStatus"],$people)){
////                $rsdata["status"] = -1;
////                return $rsdata;
////            } */
////        if($rsv["orderStatus"]!='2' and $rsv["orderStatus"]!='10'){
////            $rsdata["status"] = -1;
////            return $rsdata;
////        }
////
////        // if($rsv["deliverType"]==2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0){
////        if($rsv["deliverType"]==2 and $rsv["isSelf"] == 0){
////            //预发布 并提交达达订单
////            $funResData = self::DaqueryDeliverFee($obj);
////            return $funResData;
////
////        }
////        $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////        $txt = json_encode($rsv);
////        fwrite($myfile, "自建物流开始：$txt \n");
////        fclose($myfile);
////        //自建物流配送
////        if($rsv["deliverType"]==4 and $rsv["isSelf"] == 0){
////            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");;
////            fwrite($myfile, "已进入自建物流 \n");
////            fclose($myfile);
////            $funResData = self::KuaiqueryDeliverFee($obj);
////
////            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////            $txt = json_encode($funResData);
////            fwrite($myfile, "自建物流调用结果：$txt \n");
////            fclose($myfile);
////
////            return $funResData;
////        }
////
////        $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////        fwrite($myfile, "自建物流没走： \n");
////        fclose($myfile);
////
////        $data["logContent"] = "商家已发货";
////        if($rsv["isSelf"] == 1 and !empty($source)){//如果是自提 提货码不为空
////            //判断是否对的上
////            $mod_user_self_goods = M('user_self_goods');
////            $mod_user_self_goods_data = $mod_user_self_goods->where('source = ' . $source)->find();
////            if($mod_user_self_goods_data['orderId'] !=  $orderId){
////                return array('status'=>-1,'info'=>'取货码与订单不符');
////            }
////
////            //改为已取货
////            $where['id'] = $mod_user_self_goods_data['id'];
////            $saveData['onStart'] = 1;
////            $saveData['onTime'] = date('Y-m-d H:i:s');
////            $mod_user_self_goods->where($where)->save($saveData);
////            $data["logContent"] = "用户已自提";
////
////        }
////
////        $sql = "UPDATE __PREFIX__orders set deliverType=1,orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderId = $orderId and shopId=".$shopId;
////        $rs = $this->execute($sql);
////
////        if($rs){
////            //订单同步同步到ERP start
////            $param = [];
////            $param['shopId'] = $shopId;
////            $param['orderId'] = "{$orderId}";
////            $this->syncERPOrder($param);
////            //订单同步同步到ERP end
////        }
////        //判断是否是首次下单
////        //是否奖励邀请券 判断是否是第一次下单(第一笔订单 之前一定是0笔) 且是否拥有邀请人 并邀请人有优惠券待恢复使用
////        //if(M('orders')->limit(1)->where("orderStatus=4 and userId = '{$userId}'")->count() == 0){
////        if(M('orders')->limit(1)->where("orderStatus=3 and userId = '{$userId}'")->count() == 1){
////            //本次订单是否满足十元
////            if($rsv['realTotalMoney'] >= 10){
////                //查询是否存在邀请人
////                $find_user_invitation = M('user_invitation')->where("UserToId = '{$userId}'")->find();
////                if($find_user_invitation){
////                    //是否存在待恢复使用的优惠券
////                    $mod_coupons_users = M('coupons_users');
////                    $coupons_save['dataFlag'] = 1;
////                    $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->save($coupons_save);
////                    // $res_mod_coupons_users = $mod_coupons_users->where("userId = '{$find_user_invitation['userId']}' and dataFlag = -1")->select();
////
////                    // if($res_mod_coupons_users){
////                    //   //恢复冻结的优惠券
////                    //
////                    //   for($i=0;$i<count($res_mod_coupons_users);$i++){
////                    //     $coupons_users_save['dataFlag'] = 1;
////                    //     $mod_coupons_users->where("id ='{$res_mod_coupons_users[$i]['id']}'")->save($coupons_users_save);
////                    //   }
////                    //
////                    //
////                    // }
////                }
////            }
////        }
////
////
////        $m = M('log_orders');
////        $data["orderId"] = $orderId;
////
////        $data["logUserId"] = $userId;
////        $data["logType"] = 0;
////        $data["logTime"] = date('Y-m-d H:i:s');
////        $ra = $m->add($data);
////        $rsdata["status"] = $ra;
////        return $rsdata;
////    }
////
////    /**
////     * 商家批量发货配送订单 -------不支持达达物流
////     */
////    public function Merchantapi_Orders_batchShopOrderDelivery ($obj){
////        $USER = session('WST_USER');
////        $userId = (int)$USER["userId"];
////        $userId = $userId?$userId:$obj['userId'];
////        $orderIds = self::formatIn(",",I("orderIds"));
////        $shopId = (int)$USER["shopId"];
////        $shopId = $shopId?$shopId:$obj['shopId'];
////        $source = I('source');
////        if($orderIds=='')return array('status'=>-2);
////        $orderIds = explode(',',$orderIds);
////        $orderNum = count($orderIds);
////        $editOrderNum = 0;
////        foreach ($orderIds as $orderId){
////            if($orderId=='')continue;//订单号为空则跳过
////            $sql = "SELECT orderId,orderNo,orderStatus,orderSrc,userId FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;
////            $rsv = $this->queryRow($sql);
////            if($rsv["orderStatus"]!=2)continue;//状态不符合则跳过
////            if($rsv["deliverType"]==2 and $obj['isShopGo'] == 0 and $rsv["isSelf"] == 0){
////                //预发布 并提交达达订单
////                $funResData = self::DaqueryDeliverFee($obj);
////                continue;
////
////            }
////            $sql = "UPDATE __PREFIX__orders set orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderId = $orderId and shopId=".$shopId;
////            $rs = $this->execute($sql);
////            if($rs){
////                //订单同步同步到ERP start
////                $param = [];
////                $param['shopId'] = $shopId;
////                $param['orderId'] = "{$orderId}";
////                $this->syncERPOrder($param);
////                //订单同步同步到ERP end
////            }
////            $data = array();
////            $m = M('log_orders');
////            $data["orderId"] = $orderId;
////            $data["logContent"] = "商家已发货";
////            $data["logUserId"] = $userId;
////            $data["logType"] = 0;
////            $data["logTime"] = date('Y-m-d H:i:s');
////            $ra = $m->add($data);
////            $editOrderNum++;
////
////            // --- 发货成功,推送消息 --- @author liusijia --- start ---
////            if ($rs) {
////                $userInfo = M('users')->where(array('userId'=>$rsv['userId'],'userFlag'=>1))->find();
////                if (empty($userInfo)) continue;
////                if ($rsv['orderSrc'] == 0) {//商城
////
////                } else if ($rsv['orderSrc'] == 1) {//微信
////
////                } else if ($rsv['orderSrc'] == 2) {//手机版
////
////                } else if ($rsv['orderSrc'] == 3) {//app
////                    if (!empty($userInfo) && !empty($userInfo['registration_id']))
////                        pushMessageByRegistrationId('订单消息提醒', "订单编号为 ".$rsv['orderNo']." 的订单已发货，请注意查收。", $userInfo['registration_id'], []);
////                } else if ($rsv['orderSrc'] == 4) {//小程序
////
////                }
////            }
////            // --- 发货成功,推送消息 --- @author liusijia --- end ---
////        }
////        if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
////        if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
////        return array('status'=>1);
////    }
////
////    /**
////     * 使用达达预发布并提交订单
////     */
////    static function DaqueryDeliverFee($obj){
////        $mod_orders = M('orders');
////        $order_areas_mod = M('areas');
////        $mod_log_orders = M('log_orders');
////        //orders表里的字段 updateTime设置为空
////        $order_save['updateTime'] = null;
////        $order_save['deliverType'] = 2;//达达配送
////        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($order_save);
////        $mod_orders_data = $mod_orders->where("orderId = '{$obj["orderId"]}'")->find();//当前订单数据
////
////        //判断当前订单是否在达达覆盖范围城市内
////
////        $mod_shops = M('shops');
////        $shops_res = $mod_shops->where('shopId='.$mod_orders_data['shopId'])->find();
////
////        $dadaShopId = $shops_res['dadaShopId'];
////        $dadaOriginShopId = $shops_res['dadaOriginShopId'];
////
////        $dadam = D("Home/dada");
////        $dadamod = $dadam->cityCodeList(null,$dadaShopId);//线上环境
////// 		$dadamod = $dadam->cityCodeList(null,73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
////
////        if(!empty($dadamod['niaocmsstatic'])){
////            $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'获取城市出错#'.$dadamod['info']);//获取城市出错
////            return $rd;
////        }
////
////        $cityNameisWx = str_replace(array('省','市'),'',$order_areas_mod->where("areaId = '{$mod_orders_data["areaId2"]}'")->field('areaName')->find()['areaName']);
////        //判断当前是否在达达覆盖范围内
////        for($i=0;$i<=count($dadamod)-1;$i++){
////            if($cityNameisWx == $dadamod[$i]['cityName']){//如果在配送范围
////                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
////                $txt ='在达达覆盖范围';
////                fwrite($myfile, $txt.'\n');
////                fclose($myfile);
////
////                //进行订单预发布
////
////                //备参
////
////                $DaDaData = array(
////                    'shop_no'=> $dadaOriginShopId,//	门店编号，门店创建后可在门店列表和单页查看
////                    // 	'shop_no'=> '11047059',//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
////                    'origin_id'=> $mod_orders_data["orderNo"],//第三方订单ID
////                    'city_code'=> $dadamod[$i]['cityCode'],//	订单所在城市的code（查看各城市对应的code值）
////                    'cargo_price'=> $mod_orders_data["totalMoney"],//	订单金额 不加运费
////                    'is_prepay'=> 0,//	是否需要垫付 1:是 0:否 (垫付订单金额，非运费)
////                    'receiver_name'=> $mod_orders_data["userName"],//收货人姓名
////                    'receiver_address'=> $mod_orders_data["userAddress"],//	收货人地址
////                    'receiver_phone'=> $mod_orders_data["userPhone"],//	收货人手机号
////                    'callback'=> WSTDomain().'/wstapi/logistics/notify_dada.php' //	回调URL（查看回调说明）
////                );
////
////
////                $dada_res_data = $dadam->queryDeliverFee($DaDaData,$dadaShopId);
////                // $dada_res_data = $dadam->queryDeliverFee($DaDaData,73753);///测试环境 测试完成 此段删除 开启上行代码-------------------------------------
////
////                $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
////                $txt =json_encode($dada_res_data);
////                fwrite($myfile, "我是发布前的请求#".$txt.'\n');
////                fclose($myfile);
////
////                //更改订单某些字段
////                $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
////                $data['deliveryNo'] = $dada_res_data['deliveryNo'];//来自达达返回的平台订单号
////
////                $data["deliverMoney"] =  $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
////                $data["orderStatus"] =  7;//	订单状态
////                $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($data);
////
////
////                //发布订单
////                $dadam = D("Home/dada");
////                $dadamod = $dadam->addAfterQuery(array('deliveryNo'=>$data['deliveryNo']),$dadaShopId);
////                // $dadamod = $dadam->addAfterQuery(array('deliveryNo'=>$data['deliveryNo']),73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
////
////                if(!empty($dadamod['niaocmsstatic'])){
////                    $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'发布订单#'.$dadamod['info']);
////                    return $rd;
////                }
////
////                //写入订单日志
////                $add_data['orderId'] = $obj["orderId"];
////                $add_data['logContent'] = '商家已通知达达取货';
////                $add_data['logUserId'] = $obj["userId"];
////                $add_data['logType'] = 0;
////                $add_data['logTime'] = date("Y-m-d H:i:s");
////                $res = $mod_log_orders->add($add_data);
////                $rsdata["status"] = $res;
////                return $rsdata;
////            }
////        }
////    }
////
////
////    /*
////    *快跑者 发布订单
////    */
////    static function KuaiqueryDeliverFee($obj){
////        $mod_orders = M('orders');
////        $mod_log_orders = M('log_orders');
////        //orders表里的字段 updateTime设置为空
////        $order_save['updateTime'] = null;
////        $order_save['deliverType'] = 4;//快跑者
////        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($order_save);
////        $mod_orders_data = $mod_orders->where("orderId = '{$obj["orderId"]}'")->find();//当前订单数据
////
////        // 获取店铺详情数据
////        $mod_shops_data = M('shops')->where("shopId = ".$mod_orders_data['shopId'])->find();
////
////        // $myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
////        // $txt =json_encode($dada_res_data);
////        // fwrite($myfile, "我是发布前的请求#".$txt.'\n');
////        // fclose($myfile);
////
////        //创建订单 并获取订单详情
////        $M_Kuaipao = D("Home/Kuaipao");
////        $M_data_Kuaipao = array(
////            'team_token'=>$mod_shops_data['team_token'],
////            'shop_id'=>$mod_shops_data['shopId'],
////            'shop_name'=>$mod_shops_data['shopName'],
////            'shop_tel'=>$mod_shops_data['shopTel'],
////            'shop_address'=>$mod_shops_data['shopAddress'],
////            'shop_tag'=>"{$mod_shops_data['longitude']},{$mod_shops_data['latitude']}",
////            'customer_name'=>$mod_orders_data['userName'],
////            'customer_tel'=>$mod_orders_data['userPhone'],
////            'customer_address'=>$mod_orders_data['userAddress'],
////            'order_no' => $mod_orders_data['orderNo'],
////            'pay_status'=>0,
////        );
////        $M_res_Kuaipao = null;
////        $M_info_Kuaipao = null;
////        $M_error_Kuaipao= null;
////        $M_Kuaipao->createOrder($M_data_Kuaipao,$M_res_Kuaipao,$M_info_Kuaipao,$M_error_Kuaipao);
////
////        $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////        $txt = json_encode($M_data_Kuaipao);
////        fwrite($myfile, "自建物流调用结果详情： $txt # $M_res_Kuaipao #  $M_info_Kuaipao # $M_error_Kuaipao \n");
////        fclose($myfile);
////
////        if($M_res_Kuaipao){//如果成功
////            $dada_res_data['deliveryNo'] = $M_res_Kuaipao['trade_no'];
////
////            //查询订单详细信息
////            $getOrderInfo_res = null;
////            $getOrderInfo_info = null;
////            $getOrderInfo_error = null;
////            $M_Kuaipao->getOrderInfo($M_res_Kuaipao['trade_no'],$getOrderInfo_res,$getOrderInfo_info,$getOrderInfo_error);
////
////
////            $myfile = fopen("kuaipao.txt", "a+") or die("Unable to open file!");
////            $txt = json_encode($getOrderInfo_res);
////            fwrite($myfile, "查询订单结果详情： $txt \n");
////            fclose($myfile);
////
////            if($getOrderInfo_res){
////                $dada_res_data['distance'] = (float)$getOrderInfo_res['distance']*1000;
////                $dada_res_data['fee'] = $getOrderInfo_res['pay_fee'];
////            }
////        }
////
////        //更改订单某些字段
////        $data['distance'] = $getOrderInfo_res['distance'];//配送距离(单位：米)
////        $data['deliveryNo'] = $getOrderInfo_res['trade_no'];//来自跑腿平台返回的平台订单号
////
////        $data["deliverMoney"] =  $getOrderInfo_res['fee'];//	实际运费(单位：元)，运费减去优惠券费用
////        $data["orderStatus"] =  7;//	订单状态
////
////        //这时候没有骑手信息的
////// 			$data["dmName"] =  $dada_res_data['courier_name'];//	骑手姓名
////// 			$data["dmMobile"] =  $dada_res_data['courier_tel'];//	骑手电话
////
////
////        $mod_orders->where("orderId = '{$obj["orderId"]}'")->save($data);
////
////        //写入订单日志
////        $add_data['orderId'] = $obj["orderId"];
////        $add_data['logContent'] = '商家已通知骑手取货';
////        $add_data['logUserId'] = $obj["userId"];
////        $add_data['logType'] = 0;
////        $add_data['logTime'] = date("Y-m-d H:i:s");
////        $res = $mod_log_orders->add($add_data);
////        $rsdata["status"] = $res;
////        return $rsdata;
////    }
////
////
////    /*
////     *本地商品批量同步到ERP
////     * @param array $param
////     * */
////    public function syncGoodsToERP($param){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        $goodsIdArr = explode(',',$param['goodsIds']);
////        $where = [];
////        $where['goodsId'] = ['IN',$goodsIdArr];
////        $where['goodsFlag'] = 1;
////        $goodsList = M('goods')->where($where)->field('goodsId,goodsName,goodsSn')->select();
////        if(!$goodsList){
////            $apiRes['apiInfo'] = '暂无符合条件的商品';
////            return $apiRes;
////        }
////        foreach ($goodsList as $index => $item) {
////            $this->inertPtype($item['goodsId']);
////        }
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////    /*
////     * 商户端生成调拨单
////     * */
////    public function Merchantapi_TotalInventory_createOtpurchase($param){
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '操作失败';
////        $apiRet['apiState'] = 'error';
////        $houseInfo = $this->checkShopWareHouse($param['shopId']);
////        if(empty($houseInfo['id'])){
////            return $houseInfo;
////        }
////        $goods = json_decode($param['goods'],true);
////        $isGoodsTab = M('goods','is_');
////        foreach ($goods as $key=>&$val){
////            $isGoodsInfo = $isGoodsTab->where(['id'=>$val['goods']])->find();
////            $myGoodsInfo = $isGoodsTab->where(['number'=>$isGoodsInfo['number'],"warehouse"=>$houseInfo['id']])->find();
////            $toRoomInfo = M('room','is_')->where(['warehouse'=>$houseInfo['id'],'goods'=>$myGoodsInfo['id']])->find();
////            $val['toroomId'] = $toRoomInfo['id'];
////            $val['goodsSn'] = $isGoodsInfo['number'];
////            if($val['towarehouseId'] == $houseInfo['id']){
////                $apiRet['apiInfo'] = '操作失败,所属仓库和调拨仓库不能相同';
////                return $apiRet;
////            }
////        }
////        unset($val);
////        M()->startTrans();//开启事物
////        //添加is_allocationclass信息
////        $allocationclass = [];
////        $allocationclass['merchant'] = $houseInfo['merchant'];
////        $allocationclass['time'] = time();
////        $allocationclass['number'] = $this->get_number('DBD');
////        $allocationclass['user'] = $houseInfo['userId'];
////        $allocationclass['file'] = '';
////        $allocationclass['data'] = $param['remark'];
////        $allocationclass['type'] = 0;
////        $allocationclass['auditinguser'] = $houseInfo['userId'];
////        $allocationclass['auditingtime'] = time();
////        $allocationclassId = M('allocationclass','is_')->add($allocationclass);
////        if($allocationclassId){
////            //添加操作日志 start
////            $content = "新增调拨单[ ".$allocationclass['number']." ]"; //内容
////            $parameter['shopId'] = $param['shopId'];
////            $parameter['content'] = $content;
////            $parameter['opurchaseclassId'] = $allocationclassId;
////            $this->insertOpurchaseActionLog($parameter,0,2);
////            //添加操作日志 end
////            //添加allocationinfo信息
////            foreach ($goods as $val){
////                $allocationinfo = [];
////                $allocationinfo['pid'] = $allocationclassId;
////                $allocationinfo['room'] = $val['roomId'];
////                $allocationinfo['goods'] = $val['goods'];
////                $allocationinfo['goodsSn'] = $val['number'];
////                $allocationinfo['warehouse'] = $val['towarehouseId'];
////                $allocationinfo['serial'] = '';
////                $allocationinfo['nums'] = $val['nums'];
////                $allocationinfo['towarehouse'] = $houseInfo['id'];
////                $allocationinfo['toroom'] = $val['toroomId'];
////                $allocationinfo['data'] = $val['data'];
////                $opurchaseinfoId = M('allocationinfo','is_')->add($allocationinfo);
////            }
////            if($opurchaseinfoId){
////                M()->commit();
////                //调拨单同步到ERP start
////                $request = [];
////                $request['shopId'] = $param['shopId'];
////                $request['otpurchaseId'] = $allocationclassId;
////                $this->syncOutBill($request);
////                //调拨单同步到ERP end
////                $apiRet['apiCode'] = 0;
////                $apiRet['apiInfo'] = '添加调拨单成功';
////                $apiRet['apiState'] = 'success';
////                return $apiRet;
////            }else{
////                M()->rollback();
////                $apiRet['apiInfo'] = '添加调拨单详细信息失败';
////                return $apiRet;
////            }
////        }else{
////            M()->rollback();
////            $apiRet['apiInfo'] = '添加调拨单失败';
////            return $apiRet;
////        }
////    }
////
////    /*
////     *生成单据编号(copy进销存保持统一,type的值自己摸索或自定义)
////     * @param string $type PS:('CGDD'=>'采购单','DBD'=>调拨单,"QTRKD"=>其他入库单)
////     * */
////    public function get_number($type){
////        $number=$type.date('Ymdhis',time());
////        return $number;
////    }
////
////    /**
////     * 添加采购单操作日志 wst_opurchase_action_log
////     * @param int $type PS:类型(0:niaocms商户|1:进销存商户)
////     * @param int $dataType PS:数据类型(1:采购单|2:调拨单)
////     */
////
////    public function insertOpurchaseActionLog($parameter,$type=0,$dataType=1){
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '操作失败';
////        $apiRet['apiState'] = 'error';
////        $shopId = $parameter['shopId'];
////        if($shopId){
////            $config['shopId'] = $shopId;
////            $config['isMainWarehouse'] = 1;
////            $shopConfig = M('shop_configs')->where($config)->field('mainWarehouseUsername,mainWarehouseUserId')->find();
////            if(!$shopConfig){
////                return $apiRet;
////            }
////            $isUserInfo = M('user','is_')->where(['id'=>$shopConfig['mainWarehouseUserId']])->find();
////            if(!$isUserInfo){
////                $apiRet['apiInfo'] = '总仓用户信息有误';
////                return $apiRet;
////            }
////            $log['opurchaseclassId'] = $parameter['opurchaseclassId'];
////            $log['shopId'] = $shopId;
////            $log['merchantId'] = $isUserInfo['merchant'];
////            $log['type'] = $type;
////            $log['dataType'] = $dataType;
////            $log['content'] = $parameter['content'];
////            $log['addTime'] = date("Y-m-d H:i:s",time());
////            $insertLogId = M('opurchase_action_log')->add($log);
////            if($insertLogId){
////                $apiRet['apiCode'] = 0;
////                $apiRet['apiInfo'] = '操作成功';
////                $apiRet['apiState'] = 'success';
////            }
////        }
////        return $apiRet;
////    }
////
////    /*
////     *采购单同步到ERP
////     * */
////    public function syncInputBill($request){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        $shopId = $request['shopId'];
////        $houseInfo = $this->checkShopWareHouse($shopId);
////        //验证仓库
////        if(empty($houseInfo['id'])){
////            return $houseInfo;
////        }
////        $otpurchaseId = trim($request['opurchaseclassId'],',');
////        $otpurchaseIdArr = explode(',',$otpurchaseId);
////        $otpurchaseList = M('opurchaseclass op','is_')
////            ->join("left join is_merchant m on m.id=op.merchant")
////            ->join("left join is_user u on u.id=op.user")
////            ->where(['op.merchant'=>$houseInfo['merchant'],'op.id'=>["IN",$otpurchaseIdArr]])
////            ->field('op.*,m.name,u.name as username')
////            ->select();
////        if(!$otpurchaseList){
////            $apiRes['apiInfo'] = '采购单数据获取失败';
////            return $apiRes;
////        }
////        foreach ($otpurchaseList as $prekey=>$prevalue){
////            $otpurchaseList[$prekey]['opurchaseinfo'] = [];
////            $otpurchaseList[$prekey]['shopId'] = $shopId;
////            $opurchaseinfo = M('opurchaseinfo info','is_')
////                ->join("left join is_goods g on g.id=info.goods")
////                ->where(['info.pid'=>$prevalue['id']])
////                ->select();
////            $otpurchaseList[$prekey]['opurchaseinfo'] = $opurchaseinfo;
////            $this->insertOrderIndex($otpurchaseList[$prekey]);
////
////        }
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////    /*
////     * 采购单详情
////     * OrderIndex
////     * @param array $data
////     * */
////    public function insertOrderIndex($data){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(empty($data) || !is_array($data)){
////            return $apiRes;
////        }
////        $field = "[btypeid], [etypeid], [ktypeid], [BillDate], [ReachDate], [Billcode], [billtype], [totalmoney], [totalqty], [period], [checked], [redWord], [BillOver], [comment], [explain], [Checke], [Tax], [BillStatus], [etypeid2], [WayMode], [WayBillCode], [GoodsNumber], [PackWay], [TEL], [IfAudit], [DtypeId], [IsFinished], [CanAlert], [DelayType], [DelayValue], [OrderState], [IsMy], [IsRead], [IsHandle], [STypeID], [FromBillNumberID], [CustomerTotal], [IsIni], [NTotalMoney], [CID], [Rate], [billproperty], [DealBTypeID], [NCustomerTotal], [PrePriceNum], [IsYapi], [YapiOrderID], [atypeID], [totalinmoney], [ntotalinmoney], [checkTime], [Discount], [VipCardID]";
////        $shopId = $data['shopId'];
////        //分支机构信息
////        $stypeShopRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
////        $where = "Sid='".$stypeShopRelation['Sid']."' AND deleted=0";
////        $stypeInfo = $this->getTableDataInfo('Stype',$where);
////        if(!$stypeInfo){
////            $apiRes['apiInfo'] = '分支机构信息有误';
////            return $apiRes;
////        }
////        //本地店铺信息
////        $shopInfo = M('shops')->where(['shopId'=>$stypeShopRelation['shopId'],'shopFlag'=>1])->field("shopId,shopSn,shopName")->find();
////        //仓库信息
////        $tableName = 'Stock';
////        $where = "STypeID='".$stypeInfo['TypeId']."' AND deleted=0";
////        $stockInfo = $this->getTableDataInfo($tableName,$where);
////        if(!$stockInfo){
////            $apiRes['apiInfo'] = '仓库信息有误';
////            return $apiRes;
////        }
////        //默认货位信息
////        $tableName = 'CargoType';
////        $where = "KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
////        $cargoInfo = $this->getTableDataInfo($tableName,$where);
////        if(!$cargoInfo){
////            $apiRes['apiInfo'] = '货位信息有误';
////            return $apiRes;
////        }
////        //默认部门
////        $tableName = 'Department';
////        $where = "usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
////        $DepartmentInfo = $this->getTableDataInfo($tableName,$where);
////        //默认职员
////        $defalutUserCode = $DepartmentInfo['rec'].'-001';
////        $tableName = 'employee';
////        $where = "DtypeID='{$DepartmentInfo['typeid']}' AND STypeID='{$stypeInfo['TypeId']}' AND UserCode='{$defalutUserCode}'";
////        $employeeInfo = $this->getTableDataInfo($tableName,$where);
////
////        $date = date('Y-m-d ',time());
////        $date .= "00:00:00.000";//勿动
////        $btypeid1 = "";//结算单位
////        $etypeid2 = $employeeInfo['typeid'];//职员
////        $ktypeid3 = $stockInfo['typeId'];
////        $BillDate4 = $date;
////        $ReachDate5 = $date;
////        $Billcode6 = $data['number'];
////        $billtype7 = 301;
////        $totalmoney8 = 1.0000000000;
////        $totalqty9 = 1.0000000000;
////        $period10 = 1;
////        $checked11 = "0";
////        $redWord12 = "0";
////        $BillOver13 = "0";
////        $comment14 = "";
////        $explain15 = "";
////        $Checke16 = "00001";
////        $Tax17 = .0000000000;
////        $BillStatus18 = 0;
////        $etypeid219 = "";
////        $WayMode20 = "";
////        $WayBillCode21 = "";
////        $GoodsNumber22 = "";
////        $PackWay23 = "";
////        $TEL24 = "";
////        $IfAudit25 = 1;
////        $DtypeId26 = $DepartmentInfo['typeid'];
////        $IsFinished27 = "000000";
////        $CanAlert28 = "1";
////        $DelayType29 = "0";
////        $DelayValue30 = "0";
////        $OrderState31 = "0";
////        $IsMy32 = "000000";
////        $IsRead33 = "000000";
////        $IsHandle34 = "000000";
////        $STypeID35 = $stypeInfo['TypeId'];
////        $FromBillNumberID36 = 0;
////        $CustomerTotal37 = ".0000000000";
////        $IsIni38 = "0";
////        $NTotalMoney39 = 1.0000000000;
////        $CID40 = 1;
////        $Rate41 = "1.0000000000";
////        $billproperty42 = "-1";
////        $DealBTypeID43 = "";//往来单位
////        $NCustomerTotal44 = ".0000000000";
////        $PrePriceNum45 = "1";
////        $IsYapi46 = "0";
////        $YapiOrderID47 = "0";
////        $YapiOrderID48 = "0";
////        $totalinmoney49 = ".0000000000";
////        $ntotalinmoney50 = ".0000000000";
////        $checkTime51 = $date;
////        $Discount52 = 1;
////        $VipCardID53 = -1;
////        $value = "'{$btypeid1}', '{$etypeid2}', '{$ktypeid3}', '{$BillDate4}', '{$ReachDate5}', '{$Billcode6}', {$billtype7}, {$totalmoney8}, {$totalqty9}, {$period10}, '{$checked11}', '{$redWord12}', '{$BillOver13}', '{$comment14}', '{$explain15}', '{$Checke16}', {$Tax17}, {$BillStatus18}, '{$etypeid219}', '{$WayMode20}', '{$WayBillCode21}', '{$GoodsNumber22}', '{$PackWay23}', '{$TEL24}', {$IfAudit25}, '{$DtypeId26}', '{$IsFinished27}', '{$CanAlert28}', '{$DelayType29}', '{$DelayValue30}', '{$OrderState31}', {$IsMy32}, {$IsRead33}, {$IsHandle34}, '{$STypeID35}', {$FromBillNumberID36}, {$CustomerTotal37}, '{$IsIni38}', {$NTotalMoney39}, {$CID40}, {$Rate41}, {$billproperty42}, '{$DealBTypeID43}', {$NCustomerTotal44}, {$PrePriceNum45}, '{$IsYapi46}', '{$YapiOrderID47}', '{$YapiOrderID48}', {$totalinmoney49}, {$ntotalinmoney50}, '{$checkTime51}', {$Discount52}, {$VipCardID53}";
////        $sql = "INSERT INTO OrderIndex($field) VALUES($value)";
////        $insertRes = sqlExcute($sql);
////        //$insertRes = 1;//需要删除
////        if(!$insertRes){
////            $apiRes['apiInfo'] = "主单据添加失败";
////        }
////        $billNumberId = sqlInsertId('OrderIndex');
////        $data['styleId'] = $stypeInfo['TypeId'];
////        $data['stockId'] = $stockInfo['typeId'];
////        $data['cargoId'] = $cargoInfo['CargoID'];
////        return $this->insertOrderBill($billNumberId,$data);
////    }
////
////    /*
////     *待处理
////     * @param int $billNumberId PS:主单据id
////     * @param array $data
////     * */
////    public function insertOrderBill($billNumberId,$data){
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
////        if(empty($billNumberId) || empty($data)){
////            return $apiRes;
////        }
////        foreach ($data['opurchaseinfo'] as $index => $datum) {
////            $field = "[billNumberID], [ptypeid], [qty], [price], [total], [ReachQty], [comment], [Checked], [TeamNO1], [PassQty], [IsUnit2], [Discount], [DiscountPrice], [TaxPrice], [TaxTotal], [SaleTotal], [Tax], [KTypeID], [STypeID], [FromBillNumberID], [FromBillID], [ReachTotal], [ReachTaxTotal], [RequestBillNumberID], [RequestBillID], [WaitQty], [AskBillNumberID], [AskBillID], [TaxMoney], [NSalePrice], [NSaleTotal], [NDiscountPrice], [NTotal], [NTaxPrice], [NTaxTotal], [NTaxMoney], [UnitID], [NUnitID], [NQty], [UnitRate], [NUnitMsg], [MUnitID], [MQty], [MUnitRate], [MUnitMsg], [MSalePrice], [MDiscountPrice], [MTaxPrice], [CurMSalePrice], [CurMDiscountPrice], [CurMTaxPrice], [ItemID], [IsCombined], [BillOver], [GoodsNumber], [CargoID], [entrycode], [GoodsCostPrice], [GoodsOrder], [ProduceDate], [ValidDate], [IsGift], [YapiID], [PriceSource], [Id], [PromoType], [ShowOrder], [stopreason], [isrequest], [oldcurmtaxprice], [oldqty], [LockNumber], [pindexid], [combinationptypeid], [SaleOrderReachQty], [SaleOrderID]";
////            $tableName = 'ptype';
////            $where = "UserCode='{$datum['goodsSn']}' AND deleted=0 ";
////            $fields = "typeId,UserCode,Pid";
////            $ptypeInfo = $this->getTableDataInfo($tableName,$where,$fields);
////
////            $billNumberID1 = $billNumberId;
////            $ptypeid2 = $ptypeInfo['typeId'];
////            $qty3 = $datum['nums'];
////            $price4 = $datum['sell'];
////            $total5 = $datum['nums'] * $price4;
////            $ReachQty6 = .0000000000;
////            $comment7 = '';
////            $Checked8 = '0';
////            $TeamNO19 = '';
////            $PassQty10 = .0000000000;
////            $IsUnit211 = '0';
////            $Discount12 = 0;
////            $DiscountPrice13 = 0;
////            $TaxPrice14 = 0;
////            $TaxTotal15 = 0;
////            $SaleTotal16 = 0;
////            $Tax17 = .0000000000;
////            $KTypeID18 = $data['stockId'];
////            $STypeID19 = $data['styleId'];
////            $FromBillNumberID20 = 0;
////            $FromBillID21 = 0;
////            $ReachTotal22 = .0000000000;
////            $ReachTaxTotal23 = .0000000000;
////            $RequestBillNumberID24 = 0;
////            $RequestBillID25 = 0;
////            $WaitQty26 = .0000000000;
////            $AskBillNumberID27 = 0;
////            $AskBillID28 = 0;
////            $TaxMoney29 = .0000000000;
////            $NSalePrice30 = 1.0000000000;
////            $NSaleTotal31 = 1.0000000000;
////            $NDiscountPrice32 = 1.0000000000;
////            $NTotal33 = 1.0000000000;
////            $NTaxPrice34 = 1.0000000000;
////            $NTaxTotal35 = 1.0000000000;
////            $NTaxMoney36 = .0000000000;
////            $UnitID37 = 1;
////            $NUnitID38 = 0;
////            $NQty39 = .0000000000;
////            $UnitRate40 = .0000000000;
////            $NUnitMsg41 = '';
////            $MUnitID42 = 1;
////            $MQty43 = 1.0000000000;
////            $MUnitRate44 = 1.0000000000;
////            $MUnitMsg45 = '';
////            $MSalePrice46 = 1.0000000000;
////            $MDiscountPrice47 = 1.0000000000;
////            $MTaxPrice48 = 1.0000000000;
////            $CurMSalePrice49 = 1.0000000000;
////            $CurMDiscountPrice50 = 1.0000000000;
////            $CurMTaxPrice51 = 1.0000000000;
////            $ItemID52 = 0;
////            $IsCombined53 = 0;
////            $BillOver54 = '0';
////            $GoodsNumber55 = '';
////            $CargoID56 = $datum['cargoId'];
////            $entrycode57 = '';//条码
////            $GoodsCostPrice58 = $datum['buy'];
////            $GoodsOrder59 = -1;
////            $ProduceDate60 = '';
////            $ValidDate61 = '';
////            $IsGift62 = 0;
////            $YapiID63 = '';
////            $PriceSource64 = '';
////            $Id65 = 1;
////            $PromoType66 = 0;
////            $ShowOrder67 = 1;
////            $stopreason68 = '';
////            $isrequest69 = 0;
////            $oldcurmtaxprice70 = .0000;
////            $oldqty71 = .0000;
////            $LockNumber72 = .0000000000;
////            $pindexid73 = '';
////            $combinationptypeid74 = '';
////            $SaleOrderReachQty75 = .0000000000;
////            $SaleOrderID76 = 0;
////            $value = "{$billNumberID1}, '{$ptypeid2}', {$qty3}, $price4, $total5, $ReachQty6, '{$comment7}', '{$Checked8}', '{$TeamNO19}', $PassQty10, '{$IsUnit211}', {$Discount12}, {$DiscountPrice13}, $TaxPrice14, $TaxTotal15, $SaleTotal16, $Tax17, '{$KTypeID18}', '{$STypeID19}', $FromBillNumberID20, $FromBillID21, $ReachTotal22, $ReachTaxTotal23, $RequestBillNumberID24, $RequestBillID25, $WaitQty26, $AskBillNumberID27, $AskBillID28, $TaxMoney29, $NSalePrice30, $NSaleTotal31, $NDiscountPrice32, $NTotal33, $NTaxPrice34, $NTaxTotal35, $NTaxMoney36, $UnitID37, $NUnitID38, $NQty39, $UnitRate40, '{$NUnitMsg41}', $MUnitID42, $MQty43, '{$MUnitRate44}', '{$MUnitMsg45}', $MSalePrice46, $MDiscountPrice47, $MTaxPrice48, $CurMSalePrice49, $CurMDiscountPrice50, $CurMTaxPrice51, $ItemID52, '{$IsCombined53}', '{$BillOver54}', '{$GoodsNumber55}', '{$CargoID56}', '{$entrycode57}', $GoodsCostPrice58, '{$GoodsOrder59}', '{$ProduceDate60}', '{$ValidDate61}', '{$IsGift62}', '{$YapiID63}', '{$PriceSource64}', {$Id65}, {$PromoType66}, '{$ShowOrder67}', '{$stopreason68}', $isrequest69, $oldcurmtaxprice70, $oldqty71, '{$LockNumber72}', '{$pindexid73}', '{$combinationptypeid74}', $SaleOrderReachQty75,$SaleOrderID76";
////            $sql = "INSERT INTO OrderBill($field) VALUES($value) ";
////            sqlExcute($sql);
////        }
////        //没用事务,判断也无用
////        $apiRes['apiCode'] = 0;
////        $apiRes['apiInfo'] = '操作成功';
////        $apiRes['apiState'] = 'success';
////        return $apiRes;
////    }
////
////    /*
////     * 商户端生成采购单
////     * */
////    public function Merchantapi_TotalInventory_createPurchase($param){
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '操作失败';
////        $apiRet['apiState'] = 'error';
////        $houseInfo = $this->checkShopWareHouse($param['shopId']);
////        if(empty($houseInfo['id'])){
////            return $houseInfo;
////        }
////        $goods = json_decode($param['goods'],true);
////        $goodsTab = M('goods');
////        foreach ($goods as $key=>&$val){
////            $goodsInfo = $goodsTab->where(['goodsId'=>$val['goods']])->field('goodsSn,goodsName')->find();
////            $val['goodsSn'] = $goodsInfo['goodsSn'];
////            $val['goodsName'] = $goodsInfo['goodsName'];
////        }
////        unset($val);
////        $checkGoods = $this->checkWareHouseGoods($houseInfo['id'],$goods);
////        if($checkGoods['state'] !== true){
////            $apiRet['apiInfo'] = $checkGoods['errorMsgStr'];
////            return $apiRet;
////        }
////        $newGoods = $checkGoods['newGoods'];
////        M()->startTrans();//开启事物
////        //添加is_opurchaseclass信息
////        $opurchaseclass = [];
////        $opurchaseclass['merchant'] = $houseInfo['merchant'];
////        $opurchaseclass['time'] = time();
////        $opurchaseclass['number'] = $this->get_number('CGDD');
////        $opurchaseclass['user'] = $houseInfo['userId'];
////        $opurchaseclass['type'] = 0;
////        $opurchaseclass['auditinguser'] = $houseInfo['userId'];
////        $opurchaseclass['auditingtime'] = time();
////        $opurchaseclass['data'] = $param['remark'];
////        $opurchaseclass['storage'] = 0;
////        $opurchaseId = M('opurchaseclass','is_')->add($opurchaseclass);
////        if($opurchaseId){
////            //添加操作日志 start
////            $content = "新增采购订单[ ".$opurchaseclass['number']." ]"; //内容
////            $parameter['shopId'] = $param['shopId'];
////            $parameter['content'] = $content;
////            $parameter['opurchaseclassId'] = $opurchaseId;
////            $this->insertOpurchaseActionLog($parameter);
////            //添加操作日志 end
////            //添加is_opurchaseinfo信息
////            foreach ($newGoods as $val){
////                $opurchaseinfo = [];
////                $opurchaseinfo['pid'] = $opurchaseId;
////                $opurchaseinfo['goods'] = $val['goods'];
////                $opurchaseinfo['goodsSn'] = $val['goods'];
////                $opurchaseinfo['nums'] = $val['nums'];
////                $opurchaseinfo['readynums'] = 0;
////                $opurchaseinfo['data'] = $val['data'];
////                $opurchaseinfo['userId'] = $houseInfo['userId'];
////                $opurchaseinfo['username'] = $houseInfo['username'];
////                $opurchaseinfo['storage'] = 0;
////                $opurchaseinfo['goodsSn'] = $val['goodsSn'];
////                $opurchaseinfoId = M('opurchaseinfo','is_')->add($opurchaseinfo);
////            }
////            if($opurchaseinfoId){
////                $request = [];
////                $request['opurchaseclassId'] = $opurchaseId;
////                $request['shopId'] = $param['shopId'];
////                $res = $this->syncInputBill($request);
////                M()->commit();
////                $apiRet['apiCode'] = 0;
////                $apiRet['apiInfo'] = '添加采购单成功';
////                $apiRet['apiState'] = 'success';
////                return $apiRet;
////            }else{
////                M()->rollback();
////                $apiRet['apiInfo'] = '添加采购单详细信息失败';
////                return $apiRet;
////            }
////        }else{
////            M()->rollback();
////            $apiRet['apiInfo'] = '添加采购单失败';
////            return $apiRet;
////        }
////    }
////
////    /*
////     * 验证云仓是否有相对应的商品
////     * @param int $wareHouseId PS:仓库id
////     * @param arr $goods PS:商品
////     * */
////    public function checkWareHouseGoods($wareHouseId,$goods){
////        $errorMsg = [];
////        $errorMsgStr = '';
////        $newGoods = [];
////        $isGoodsTab = M('goods','is_');
////        $state = true;
////        foreach ($goods as $key=>&$val){
////            $isGoodsInfo = $isGoodsTab->where(['number'=>$val['goodsSn'],'warehouse'=>$wareHouseId])->find();
////            if(!$isGoodsInfo){
////                $state = false;
////                $goodsError = [
////                    "goods"=>$val['goods'],
////                    "goodsName"=>$val['goodsName'],
////                ];
////                $errorMsg[] = $goodsError;
////                $errorMsgStr .= $val['goodsName']." | ";
////            }else{
////                $newGoodsInfo['goods'] = $isGoodsInfo['id'];
////                $newGoodsInfo['nums'] = $val['nums'];
////                $newGoodsInfo['data'] = $val['data'];
////                $newGoodsInfo['goodsSn'] = $isGoodsInfo['number'];
////                $newGoodsInfo['goodsName'] = $isGoodsInfo['name'];
////                $newGoods[] = $newGoodsInfo;
////            }
////        }
////        unset($val);
////        $data['state'] = $state;
////        $data['errorMsg'] = $goodsError;
////        $data['errorMsgStr'] = !empty($errorMsgStr)?trim($errorMsgStr," | ")." 云仓商品对应有误":"";
////        $data['newGoods'] = $newGoods;
////        return $data;
////    }
////
    public function testApi(){
        // $fieldArr = getTableField('BillIndex');
        // if(!empty($fieldArr)){
        //     $field = '';
        //     foreach ($fieldArr as $val){
        //         $field .= $val['COLUMN_NAME'].",";
        //     }
        //     $field = trim($field,',');
        //     var_dump($field);exit;
        // }
        dd('test');
        $where = [];
        $where['shopId'] = 128;
        $outGoods = M('goods')->where($where)->field('goodsSn,goodsId,goodsName,goodsImg,goodsThums')->select();

        $where = [];
        $where['shopId'] = 132;
        $inputGoods = M('goods')->where($where)->field('goodsSn,goodsId,shopId,goodsName,goodsImg,goodsThums')->select();

        $goods_gallerys = M('goods_gallerys');
        $goodsTab = M('goods');
        foreach($outGoods as $value1){
            foreach($inputGoods as $value2){
                if($value1['goodsSn'] == $value2['goodsSn']){
                    $saveData = [];
                    $saveData['goodsImg'] = $value1['goodsImg'];
                    $saveData['goodsThums'] = $value1['goodsThums'];
                    $goodsTab->where(['goodsId'=>$value2['goodsId']])->save($saveData);
                    $goodsaaaa = $goods_gallerys->where(['goodsId'=>$value1['goodsId']])->select();
                    if($goodsaaaa){
                        foreach($goodsaaaa as $ga){
                            $ga = [];
                            $ga['goodsId'] = $value2['goodsId'];
                            $ga['shopId'] = $value2['shopId'];
                            $ga['goodsImg'] = $value2['goodsImg'];
                            $ga['goodsThumbs'] = $value2['goodsThumbs'];
                            $goods_gallerys->add($ga);
                        }
                    }
                }
            }
        }
        dd($inputGoods);
        dd('ok');
        $sql = "SELECT a.name, b.rows FROM sysobjects AS a INNER JOIN sysindexes AS b ON a.id = b.id WHERE (a.type = 'u') AND (b.indid IN (0, 1)) ORDER BY a.name,b.rows DESC";
        $data = sqlQuery($sql);
        if(I('debug') == 1){
            $data1 = htmlspecialchars_decode(I('data1'));
            $data1 = json_decode($data1,true);
            $data2 = htmlspecialchars_decode(I('data2'));
            $data2 = json_decode($data2,true);
            $diff = [];
            foreach ($data1 as $value1){
                foreach ($data2 as $value2){
                    if($value1['name'] == $value2['name']){
                        if($value1['rows'] != $value2['rows']){
                            $diff[] = $value2;
                        }
                    }
                }
            }
        }
        echo json_encode($data);exit;
        dd($data);
        $goods = M('goods')->where(['shopId'=>108,'isSale'=>1])->select();
        foreach($goods as $key=>$val){
            $where = [];
            $where['goodsSn'] = $val['goodsSn'];
            $where['shopId'] = 127;
            $goodsInfo = M('goods')->where($where)->find();
            if($goodsInfo){
                $update = [];
                $update['goodsThums'] = $val['goodsThums'];
                $update['goodsImg'] = $val['goodsImg'];
                $update['goodsUnit'] = $val['goodsUnit'];
                $update['goodsDesc'] = $val['goodsDesc'];
                $update['isSale'] = 1;
                $update['goodsStatus'] = $val['goodsStatus'];
                $update['saleTime'] = $val['saleTime'];
                $res = M('goods')->where(['goodsId'=>$goodsInfo['goodsId']])->save($update);
            }
        }
        dd('exit');
    }

    /**
     * 更新店铺出售中商品的库存
     * @param int $shopId
     * */
    public function updateShopErpGoodsStock(int $shopId){
        $rs = true;
        $where = [];
        $where['dataFlag'] = 1;
        $where['shopId'] = $shopId;
        $shopStockRelation = madeDB('shops_erp_stock_relation')->where($where)->find();//获取仓库关联
        if(empty($shopStockRelation)){
            $rs = false;
        }
        $shopStockRelation['priceNameSetName'] = '';
        $sql = "select FiledName from PriceNameSet where Id='".$shopStockRelation['priceNameSetId']."'";
        $priceNameSetInfo = sqlQuery($sql,'row');
        if(!empty($priceNameSetInfo)){
            $shopStockRelation['priceNameSetName'] = $priceNameSetInfo['FiledName'];
        }
        $goodsTab = M('goods');
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsFlag'] = 1;
        //$where['isSale'] = 1;
        $totalCount = $goodsTab->where($where)->count();
        $pici = ceil($totalCount / 1000);
        if($pici > 0 ){
            $limit = 1000;
            for($i=1;$i<=$pici;$i++){
                $page = $i;
                $goodsList = $goodsTab->where($where)->limit(($page-1)*$limit,$limit)->select();
                if(!empty($goodsList)){
                    $goodsSnStr = '';
                    foreach ($goodsList as $key=>$value){
                        $goodsSnStr .= "'".$value['goodsSn']."',";
                    }
                    $goodsSnStr = trim($goodsSnStr,',');
                    $goodsSnStr = trim($goodsSnStr,',');
                    $sql = "select typeId,UserCode,FullName from ptype where UserCode IN($goodsSnStr)";
                    $erpGoods = sqlQuery($sql);
                    if(!empty($erpGoods)){
                        $erpGoodsTypeIdStr = '';
                        foreach ($erpGoods as $erpVal){
                            $erpGoodsTypeIdStr .= "'".$erpVal['typeId']."',";
                        }
                        $erpGoodsTypeIdStr = trim($erpGoodsTypeIdStr,',');
                        //获取管家婆商品的价格
                        $sql = "select [PtypeId],[PreBuyPrice1],[PreBuyPrice2],[PreBuyPrice3],[PreBuyPrice4],[PreBuyPrice5],[PreSalePrice1],[PreSalePrice2],[PreSalePrice3],[PreSalePrice4],[PreSalePrice5],[PreSalePrice6],[PreSalePrice7],[PreSalePrice8],[PreSalePrice9],[PreSalePrice10],[RetailPrice],[TopSalePrice],[LowSalePrice],[TopBuyPrice],[XiWaMaxNumber],[ReferPrice],[RecAmgSTypeBuyPrice],[RecAmgSTypeSalePrice],[UnitID],[IsDefaultUnit] from PType_Price where PtypeID IN($erpGoodsTypeIdStr) and IsDefaultUnit=1";
                        $erpGoodsPrices = sqlQuery($sql);
                    }
                    //获取仓库对应的商品库存
                    $sql = "select KtypeId,PtypeId,Qty from GoodsStocks where PtypeId IN($erpGoodsTypeIdStr) and KtypeId='".$shopStockRelation['typeId']."'";
                    $goodsStock = sqlQuery($sql);
                    $saveData = [];
                    foreach ($goodsList as $key=>$value){
                        //更新名称等信息
                        foreach ($erpGoods as $erpGoodVal){
                            if($value['goodsSn'] == $erpGoodVal['UserCode']){
                                $goodsList[$key]['goodsName'] = $erpGoodVal['FullName'];
                                //更新库存
                                foreach ($goodsStock as $goodsStockVal){
                                    if($goodsStockVal['PtypeId'] == $erpGoodVal['typeId']){
                                        $goodsList[$key]['goodsStock'] = $goodsStockVal['Qty'];
                                    }
                                }
                                //更新价格
                                foreach ($erpGoodsPrices as $erpGoodsPriceVal){
                                    if($erpGoodsPriceVal['PtypeId'] == $erpGoodVal['typeId']){
                                        $goodsList[$key]['marketPrice'] = $erpGoodsPriceVal['RetailPrice'];//市场价
                                        $goodsList[$key]['shopPrice'] = $erpGoodsPriceVal[$shopStockRelation['priceNameSetName']];//店铺价
                                    }
                                }
                            }
                        }
                        $saveDataInfo = [];
                        $saveDataInfo['goodsId'] = $value['goodsId'];
                        $saveDataInfo['goodsName'] = $goodsList[$key]['goodsName'];
                        $saveDataInfo['goodsStock'] = $goodsList[$key]['goodsStock'];
                        $saveDataInfo['marketPrice'] = $goodsList[$key]['marketPrice'];
                        $saveDataInfo['shopPrice'] = $goodsList[$key]['shopPrice'];
                        $saveData[] = $saveDataInfo;
                        unset($saveDataInfo);
                    }
                    $this->saveAll($saveData,'wst_goods','goodsId');
                }
            }
        }
        /*if($rs == true){
            return returnData(true);
        }else{
            return returnData(false,'-1','error',"更新失败");
        }*/
        $rs = true;
        return returnData($rs);
    }

    /**
     * 批量更新数据
     * @param [array] $datas [更新数据]
     * @param [string] $table_name [表名]
     */
    public function saveAll($datas,$table_name,$pk,$andWhere="1=1"){
        ini_set ('memory_limit', '100M');
        $sql = ''; //Sql
        $lists = []; //记录集$lists
        foreach ($datas as $data) {
            foreach ($data as $key=>$value) {
                if($pk===$key){
                    if(in_array($pk,['goodsSn'])){
                        $ids[]="'".$value."'";
                    }else{
                        $ids[]=$value;
                    }
                }else{
                    $lists[$key].= sprintf("WHEN %u THEN '%s' ",$data[$pk],$value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql.= sprintf("`%s` = CASE `%s` %s END,",$key,$pk,$value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s ) and %s ',$table_name,rtrim($sql,','),$pk,implode(',',$ids),$andWhere);
        return M()->execute($sql);
    }

    /**
     * 商家批量受理订单-只能受理【未受理】的订单
     */
    public function Merchantapi_Orders_batchShopOrderAccept($parameter = array()){
        $USER = session('WST_USER');
        $userId = (int)$USER["userId"];
        $userId = $userId?$userId:$parameter['userId'];
        $orderIds = self::formatIn(",", I("orderIds"));
        $shopId = (int)$USER["shopId"];
        $shopId = $shopId?$shopId:$parameter['shopId'];
        if($orderIds=='')return array('status'=>-2);
        $orderIds = explode(',',$orderIds);
        $orderNum = count($orderIds);
        $editOrderNum = 0;
        foreach ($orderIds as $orderId){
            if($orderId=='')continue;//订单号为空则跳过
            $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=".$shopId;
            $rsv = $this->queryRow($sql);
            //订单状态不符合则跳过 未受理或预售订单-已付款
            /*if($rsv["orderStatus"]!=0){
                if($rsv["orderStatus"] != 14){
                    if($rsv["orderStatus"] != 13){
                        continue;
                    }
                }
            }*/
            //订单状态不符合则跳过 未受理或预售订单-已付款
            if(!in_array($rsv['orderStatus'],[0,14])){
                continue;
            }
            if($parameter['login_type'] != 2){
                return returnData(false,'-1','error',"为了保证ERP和鸟CMS商城信息的一致性,请使用职员账号操作该功能");
            }
            // $sql = "select * from SaleBill where BillNumberId='1527678'";
            // $row = sqlQuery($sql);
            // dd($row);
            //需要打开注释
            $this->syncErpBtype($rsv);//获取下单用户的信息同步到管家婆
            //同步订单信息到管家婆业务草稿中的销售出库单
            $this->insertERPSaleBill($rsv,$parameter);
            $sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderId = $orderId and shopId=".$shopId;
            $rs = $this->execute($sql);
            //$rs = 1;
            $data = array();
            $m = M('log_orders');
            $data["orderId"] = $orderId;
            $data["logContent"] = "商家已受理订单";
            $data["logUserId"] = $userId;
            $data["logType"] = 0;
            $data["logTime"] = date('Y-m-d H:i:s');
            $ra = $m->add($data);
            $editOrderNum++;
        }
        if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
        if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
        $sql = "dbcc freeproccache";
        sqlExcute($sql);
        return array('status'=>1);
    }

    /**
     * 同步下单用户数据到管家婆 PS: niaocms用户和管家婆用户关联字段为UserCode
     * @param array $orderInfo 订单信息
     * */
    public function syncErpBtype(array $orderInfo){
        if(empty($orderInfo)){
            return returnData(false,'-1','error',"订单信息有误");
        }
        $userInfo = M('users')->where(['userId'=>$orderInfo['userId']])->find();
        $userInfo['areaId1Name'] = $this->getAreaInfo($orderInfo['areaId1']);
        $userInfo['areaId2Name'] = $this->getAreaInfo($orderInfo['areaId2']);
        $userInfo['areaId3Name'] = $this->getAreaInfo($orderInfo['areaId3']);
        $where = [];
        $where['dataFlag'] = 1;
        $where['userId'] = $userInfo['userId'];
        $where['addressId'] = $orderInfo['addressId'];
        $userRelation = madeDB('users_erp_btype_relation')->where($where)->find();
        $sql = "select typeId from btype where typeId='".$userRelation['btypeTypeId']."' and deleted=0";
        $existErpUserInfo = sqlQuery($sql,'row');
        if(!$existErpUserInfo){
            madeDB('users_erp_btype_relation')->where(['id'=>$userRelation['id']])->save(['dataFlag'=>-1]);
            $userRelation = [];
        }
        //str_pad($insertCat1Id,5,0,STR_PAD_LEFT )
        $pinyin = new PinyinModel();
        if(empty($userRelation)){
            $sql = "select typeId,parid,FullName from btype where UserCode='niaocms' and deleted=0 ";
            $parentInfo = sqlQuery($sql,'row');//获取父级,目前把niaocms商城用户放到第二级[第一级为所有用户,第二级为小鸟CMS商城客户,第三级为省,第四季为市,第五级为区,第六级为用户资料]
            if(empty($parentInfo)){
                //return returnData(false,'-1','error',"请添加管家婆客户资料二级分类[小鸟CMS商城客户]");
                $PyCode = strtoupper($pinyin::getShortPinyin('小鸟CMS商城客户'));
                $createTime = date('Y-m-d H:i:s');
                $field = "parid,leveal,soncount,sonnum,FullName,CreateDate,Client,Tel1,UserCode,denominatedid,PyCode";//denominatedid结算单位
                $values = "'00001',2,0,0,'小鸟CMS商城客户','{$createTime}',1,'','niaocms','','{$PyCode}'";
                $sql = "insert into btype($field) values($values)";
                $insertParentRes = sqlExcute($sql);
                if($insertParentRes){
                    $sql = "SELECT IDENT_CURRENT('btype')";
                    $insertRow = sqlQuery($sql,'row');
                    $parentId = $insertRow[''];
                    //添加完成后更新相关字段
                    $parentTypeId = '00001'.str_pad($parentId,5,0,STR_PAD_LEFT );
                    $sql = "UPDATE btype SET typeId='{$parentTypeId}',denominatedid='{$parentTypeId}' WHERE Bid='".$parentId."' ";
                    sqlExcute($sql);
                    //更新父级信息
                    $sql = "update btype set soncount=soncount+1,sonnum=sonnum+1 where typeId='00001' and deleted=0 ";
                    sqlExcute($sql);
                }
            }
            //第一次绑定，添加本地关联数据，并同步用户数据到管家婆
            //同步省
            $sql = "select typeId,parid,leveal from btype where leveal=3 and parid='".$parentInfo['typeId']."' and deleted=0 and FullName='".$userInfo['areaId1Name']."'";
            $erpProvinceInfo = sqlQuery($sql,'row');
            $provinceTypeId = $erpProvinceInfo['typeId'];
            if(empty($erpProvinceInfo)){
                $createTime = date('Y-m-d H:i:s');
                //添加省
                $sql = "select XiWaMaxNumber from btype order by XiWaMaxNumber desc ";
                $xiWaMaxNumberNew = sqlQuery($sql,'row');
                $xiWaMaxNumber = $xiWaMaxNumberNew['XiWaMaxNumber'] + 1;

                $PyCode = strtoupper($pinyin::getShortPinyin($userInfo['areaId1Name']));
                $field = "parid,leveal,soncount,sonnum,FullName,CreateDate,Client,Tel1,PyCode,XiWaMaxNumber";
                $values = "'{$parentInfo['typeId']}',3,0,0,'{$userInfo['areaId1Name']}','{$createTime}',1,'','{$PyCode}',$xiWaMaxNumber";
                $sql = "insert into btype($field) values($values)";
                $insertProvinceRes = sqlExcute($sql);
                if($insertProvinceRes){
                    $sql = "SELECT IDENT_CURRENT('btype')";
                    $insertRow = sqlQuery($sql,'row');
                    $insertCat1Id = $insertRow[''];
                    //添加完成后更新相关字段
                    $provinceTypeId = $parentInfo['typeId'].str_pad($insertCat1Id,5,0,STR_PAD_LEFT );
                    $userCode = $parentInfo['typeId'].str_pad($insertCat1Id,2,0,STR_PAD_LEFT );
                    $sql = "UPDATE btype SET typeId='{$provinceTypeId}',UserCode='{$userCode}',denominatedid='{$provinceTypeId}' WHERE Bid='".$insertCat1Id."' ";
                    $res = sqlExcute($sql);
                    //更新父级信息
                    $sql = "update btype set soncount=soncount+1,sonnum=sonnum+1 where typeId='".$parentInfo['typeId']."' and deleted=0 ";
                    sqlExcute($sql);
                }
            }else{
                $sql = "update btype set soncount=9999,sonnum=9999 where typeId='".$provinceTypeId."' and deleted=0 ";
                sqlExcute($sql);
            }
            //同步市
            $sql = "select typeId,parid,leveal from btype where leveal=4 and parid='".$provinceTypeId."' and deleted=0 and FullName='".$userInfo['areaId2Name']."'";
            $erpCityInfo = sqlQuery($sql,'row');
            $cityTypeId = $erpCityInfo['typeId'];
            if(empty($erpCityInfo)){
                $PyCode = strtoupper($pinyin::getShortPinyin($userInfo['areaId2Name']));
                $createTime = date('Y-m-d H:i:s');
                //添加市
                $sql = "select XiWaMaxNumber from btype order by XiWaMaxNumber desc ";
                $xiWaMaxNumberNew = sqlQuery($sql,'row');
                $xiWaMaxNumber = $xiWaMaxNumberNew['XiWaMaxNumber'] + 1;

                $field = "parid,leveal,soncount,sonnum,FullName,CreateDate,Client,Tel1,PyCode,XiWaMaxNumber";
                $values = "'{$provinceTypeId}',4,0,0,'{$userInfo['areaId2Name']}','{$createTime}',1,'','{$PyCode}',$xiWaMaxNumber";
                $sql = "insert into btype($field) values($values)";
                $insertCityRes = sqlExcute($sql);
                if($insertCityRes){
                    $sql = "SELECT IDENT_CURRENT('btype')";
                    $insertRow = sqlQuery($sql,'row');
                    $insertCat2Id = $insertRow[''];
                    //添加完成后更新相关字段
                    $cityTypeId = $provinceTypeId.str_pad($insertCat2Id,5,0,STR_PAD_LEFT );
                    $userCode = $provinceTypeId.str_pad($insertCat2Id,2,0,STR_PAD_LEFT );
                    $sql = "UPDATE btype SET typeId='{$cityTypeId}',UserCode='{$userCode}',denominatedid='{$cityTypeId}' WHERE Bid='".$insertCat2Id."' ";
                    $res = sqlExcute($sql);
                    //更新父级信息
                    $sql = "update btype set soncount=soncount+1,sonnum=sonnum+1 where typeId='".$provinceTypeId."' and deleted=0 ";
                    sqlExcute($sql);
                }
            }else{
                $sql = "update btype set soncount=9999,sonnum=9999 where typeId='".$cityTypeId."' and deleted=0 ";
                sqlExcute($sql);
            }
            //同步区
            $sql = "select typeId,parid,leveal from btype where leveal=5 and parid='".$cityTypeId."' and deleted=0 and FullName='".$userInfo['areaId3Name']."'";
            $erpAreaInfo = sqlQuery($sql,'row');
            $areaTypeId = $erpAreaInfo['typeId'];
            if(empty($erpAreaInfo)){
                $PyCode = strtoupper($pinyin::getShortPinyin($userInfo['areaId3Name']));
                $createTime = date('Y-m-d H:i:s');
                //添加区
                $sql = "select XiWaMaxNumber from btype order by XiWaMaxNumber desc ";
                $xiWaMaxNumberNew = sqlQuery($sql,'row');
                $xiWaMaxNumber = $xiWaMaxNumberNew['XiWaMaxNumber'] + 1;

                $field = "parid,leveal,soncount,sonnum,FullName,CreateDate,Client,Tel1,PyCode,XiWaMaxNumber";
                $values = "'{$cityTypeId}',5,0,0,'{$userInfo['areaId3Name']}','{$createTime}',1,'','{$PyCode}',$xiWaMaxNumber";
                $sql = "insert into btype($field) values($values)";
                $insertAreaRes = sqlExcute($sql);
                if($insertAreaRes){
                    $sql = "SELECT IDENT_CURRENT('btype')";
                    $insertRow = sqlQuery($sql,'row');
                    $insertCat3Id = $insertRow[''];
                    //添加完成后更新相关字段
                    $areaTypeId = $provinceTypeId.str_pad($insertCat3Id,5,0,STR_PAD_LEFT );
                    $userCode = $provinceTypeId.str_pad($insertCat3Id,2,0,STR_PAD_LEFT );
                    $sql = "UPDATE btype SET typeId='{$areaTypeId}',UserCode='{$userCode}',denominatedid='{$areaTypeId}' WHERE Bid='".$insertCat3Id."' ";
                    $res = sqlExcute($sql);
                    //更新父级信息
                    $sql = "update btype set soncount=soncount+1,sonnum=sonnum+1 where typeId='".$cityTypeId."' and deleted=0 ";
                    sqlExcute($sql);
                }
            }else{
                $sql = "update btype set soncount=9999,sonnum=9999 where typeId='".$areaTypeId."' and deleted=0 ";
                sqlExcute($sql);
            }
            //同步用户
            $sql = "select typeId,parid,leveal from btype where typeId='".$userRelation['btypeTypeId']."' and deleted=0 ";
            $erpUserInfo = sqlQuery($sql,'row');
            if(empty($erpUserInfo)){
                $PyCode = strtoupper($pinyin::getShortPinyin($userInfo['userName']));
                $createTime = date('Y-m-d H:i:s');
                //添加用户
                $sql = "select XiWaMaxNumber from btype order by XiWaMaxNumber desc ";
                $xiWaMaxNumberNew = sqlQuery($sql,'row');
                $xiWaMaxNumber = $xiWaMaxNumberNew['XiWaMaxNumber'] + 1;

                $FullName = $userInfo['userName']." ".$userInfo['userPhone']." ".$orderInfo['userAddress'];
                $field = "parid,leveal,soncount,sonnum,FullName,CreateDate,Client,Tel1,sheng,shi,xian,PyCode,LinkBirthDay1,LinkBirthDay2,ARLimitTime,paymentLTime,TelAndAddress,XiWaMaxNumber";
                $values = "'{$areaTypeId}',6,0,0,'{$FullName}','{$createTime}',1,'{$userInfo['userPhone']}','{$userInfo['areaId1Name']}','{$userInfo['areaId2Name']}','{$userInfo['areaId3Name']}','{$PyCode}','1980-01-01 00:00:00.000','1980-01-01 00:00:00.000',9999,9999,'{$orderInfo['userAddress']}',$xiWaMaxNumber";
                $sql = "insert into btype($field) values($values)";
                $insertUserRes = sqlExcute($sql);
                if($insertUserRes){
                    $sql = "SELECT IDENT_CURRENT('btype')";
                    $insertRow = sqlQuery($sql,'row');
                    $insertUserId = $insertRow[''];
                    $userTypeId = $provinceTypeId.str_pad($insertUserId,5,0,STR_PAD_LEFT );
                    $userCode = $provinceTypeId.str_pad($insertUserId,2,0,STR_PAD_LEFT );
                    $sql = "UPDATE btype SET typeId='{$userTypeId}',UserCode='{$userCode}',denominatedid='{$userTypeId}' WHERE Bid='".$insertUserId."' ";
                    $res = sqlExcute($sql);
                    $sql = "update btype set soncount=soncount+1,sonnum=sonnum+1 where typeId='".$areaTypeId."' and deleted=0 ";
                    $tst = sqlExcute($sql);
                    //向关联表插入数据
                    $saveData = [];
                    $saveData['userId'] = $userInfo['userId'];
                    $saveData['addressId'] = $orderInfo['addressId'];
                    $saveData['btypeTypeId'] = $userTypeId;
                    madeDB('users_erp_btype_relation')->add($saveData);
                }
            }
        }else{
            $FullName = $userInfo['userName']." ".$userInfo['userPhone'].' '.$orderInfo['userAddress'];
            $sql = "update btype set FullName='{$FullName}',TelAndAddress='{$orderInfo['userAddress']}' where typeId = '{$userRelation['btypeTypeId']}'";
            sqlExcute($sql);
        }
        return true;
    }

    /**
     * 获取地区数据的地区名称
     * @param int $areaId 地区id
     * */
    public function getAreaInfo(int $areaId){
        $where = [];
        $where['areaId'] = $areaId;
        $where['areaFlag'] = 1;
        $areaInfo = M('areas')->where($where)->find();
        if(empty($areaInfo)){
            return '';
        }
        return $areaInfo['areaName'];
    }

    /*
     *添加销售出库单
     * @param array $orderInfo PS;订单信息
     * @param array $actionUser 操作用户
     * */
    public function insertERPSaleBill($orderInfo,$actionUser){
        if(empty($orderInfo)){
            return returnData(false,'-1','error',"订单信息有误");
        }
        $where = [];
        $where['dataFlag'] = 1;
        $where['userId'] = $orderInfo['userId'];
        $where['addressId'] = $orderInfo['addressId'];
        $userRelation = madeDB('users_erp_btype_relation')->where($where)->find();
        $sql = "select typeId,parid,FullName,UserCode from btype where typeId='".$userRelation['btypeTypeId']."'";
        $btypeInfo = sqlQuery($sql,'row');
        if(empty($btypeInfo)){
            return returnData(false,'-1','error',"客户信息有误");
        }
        $where = [];
        $where['shopId'] = $orderInfo['shopId'];
        $where['dataFlag'] = 1;
        $shopStockRelation = madeDB('shops_erp_stock_relation')->where($where)->find();
        //仓库信息
        $sql = "SELECT typeId,parid,Kid,STypeID,leveal,UserCode FROM Stock WHERE typeId='".$shopStockRelation['typeId']."' AND deleted=0 ";
        $stockInfo = sqlQuery($sql,'row');
        if(empty($stockInfo)){
            return returnData(false,'-1','error',"仓库信息有误");
        }
        $sql = "select TypeId,parid,FullName,UserCode from Stype where TypeId='".$stockInfo['STypeID']."'";
        $styleInfo = sqlQuery($sql,'row');
        //默认货位信息
        $sql = "SELECT CargoID,UserCode,FullName FROM CargoType WHERE KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
        $cargoInfo = sqlQuery($sql,'row');
        if(empty($cargoInfo)){
            return returnData(false,'-1','error',"默认货位信息有误");
        }
        //默认部门
        $firstStock = $stockInfo;
        if($firstStock['leveal'] != 1){
            //获取一级仓库
            $UserCodeArr = explode('-',$stockInfo['UserCode']);
            $sql = "select TypeId,parid,FullName,UserCode from Stock where UserCode='".$UserCodeArr[0]."'";
            $firstStock = sqlQuery($sql,'row');
        }
        $sql = "SELECT typeid,parid,FullName,rec FROM Department WHERE usercode='".$firstStock['UserCode']."' AND deleted=0 ";
        $DepartmentInfo = sqlQuery($sql,'row');
        //默认职员
        /*$defalutUserCode = $DepartmentInfo['rec'].'-001';
        $sql = "SELECT typeid,parid,FullName FROM employee WHERE DtypeID='{$DepartmentInfo['typeid']}' AND STypeID='{$stypeInfo['TypeId']}' AND UserCode='{$defalutUserCode}'";
        $employeeInfo = sqlQuery($sql,'row');*/
        //订单商品信息
        $orderGoods = M('order_goods og')
            ->join("left join wst_goods g on og.goodsId=g.goodsId")
            ->where(['og.orderId'=>$orderInfo['orderId']])
            ->field("og.*,g.goodsSn")
            ->select();
        //验证商品,如果商品在ERP中不存在,就不在执行下面的程序了
        foreach ($orderGoods as $key=>$val){
            $sql = "SELECT typeId,UserCode,FullName FROM ptype WHERE deleted=0 AND UserCode='".$val['goodsSn']."'";
            $erpGoodsInfo = sqlQuery($sql,'row');
            if(empty($erpGoodsInfo)){
                return returnData(false,'-1','error',"商品信息有误");
            }
        }
        $goodsTab = M('goods');
        //userInfo
        $userInfo = M('users')->where(['userId'=>$orderInfo['userId']])->field('userName,userPhone')->find();
        $userInfoStr = $userInfo['userName'].'，'.$userInfo['userPhone'].'，'.$orderInfo['userAddress'];
        foreach ($orderGoods as $key=>$val){
            $orderGoods[$key]['goodsSn'] = $goodsTab->where(['goodsId'=>$val['goodsId']])->getField('goodsSn');
        }
        $countGoods = count($orderGoods);
        $time = date('Y-m-d H:i:s',time());
        /*$field = "BillDate,BillCode,BillType,btypeid,etypeid,ktypeid,ifcheck,checke,totalmoney,totalqty,period,RedWord,draft,DTypeId,LinkMan,LinkTel,LinkAddr,Stypeid,Poster,LastUpdateTime,checkTime,DealBTypeID,CID,Rate,NTotalMoney,BeforePromoBillNumberId,BillTime,WarehouseOutDay,StockInDeadline";*/
        $sql = "SELECT newid()";
        $newId = sqlQuery($sql,'row');
        $newId = $newId[''];

        //该字段列表直接去复制表中正确的数据,转成SQL语句,比较快速,和值的对应正确率较高
        $field = "[BillDate], [BillCode], [BillType], [Comment], [atypeid], [ptypeid], [btypeid], [etypeid], [ktypeid], [ktypeid2], [ifcheck], [checke], [totalmoney], [totalinmoney], [totalqty], [tax], [period], [explain], [RedWord], [draft], [OrderId], [waybillcode], [goodsnumber], [packway], [TEL], [Uploaded], [OFF_ID], [TeamNo], [AlertDay], [CompleteTotal], [ID], [Assessor], [IfAudit], [Audit_explain], [IfStopMoney], [preferencemoney], [DTypeId], [JF], [VipCardID], [vipCZMoney], [jsStyle], [jsState], [KTypeID3], [LinkMan], [LinkTel], [LinkAddr], [wxManID], [JieJianBillNumberID], [JieJianID], [JieJianState], [BaoXiuBillNumberID], [BaoXiuID], [BuyDate], [Serial], [wxTotal], [ErrDes], [ifHideJieJian], [ifWX], [ifYearBill], [CWCheck], [IfBargainOn], [LastUpdateTime], [Poster], [STypeID], [FromBillNumberID], [CostSale], [IfMulAccount], [OtherInOutType], [PosBillType], [checkTime], [posttime], [updateE], [ShareMode], [IsFreightMoney], [FreightAddr], [FreightPerson], [FreightTel], [IfInvoice], [TransportId], [UF1], [UF2], [UF3], [UF4], [UF5], [ATID], [DealBTypeID], [CID], [Rate], [NTotalMoney], [NTotalInMoney], [NPreferenceMoney], [NVIPCZMoney], [IsIni], [ATypeID1], [NCompleteTotal], [billproperty], [TallyId], [IfCargo], [InvoiceMoney], [NInvoiceMoney], [RedReason], [NewPosBill], [PrePriceNum], [AssetBusinessTypeId], [PromoRuleId], [BeforePromoBillNumberId], [ImportType], [PromotionMsg], [Reconciliation], [ARTOTAL], [APTOTAL], [YSTOTAL], [YFTOTAL], [IsAuto], [StypeID2], [IfConfirm], [TransferType], [FeeDeductType], [IsYapi], [YapiOrderID], [DeliveryAddress], [AddDTypeETypeType], [BillTime], [IsLend], [Discount], [AdPriceType], [ChargeOffBtypeid], [ChargeOffType], [ChargeOffTotal], [NChargeOffTotal], [cwchecker], [InFactRefState], [OutFactRefState], [InFactState], [OutFactState], [ShelveState], [ShelveRefState], [TransPortState], [iscombinationstyle], [progressstate], [citestate], [IsNeedTransport], [shipper], [totalfreight], [ntotalfreight], [transportno], [WarehouseOutDay], [StockInDeadline], [Transporterid], [Transporterfullname], [shipperfullname], [shipperperson], [shippertel], [FaPiaoCode], [PayType], [PosId], [PromonIds], [isByBrandarType], [PayOnTheSpot], [UploadPreOrder], [PreOrderStatus], [PreOrderId]";
        $explodeTime = explode(' ',$time);
        $BillDate1 = date('Y-m-d').' 00:00:00';//此参数勿改,会影响单据展示
        $BillCode2 = $orderInfo['orderNo'];
        $BillType3 = 11;
        $Comment4 = '';
        $atypeid5 = '';
        $ptypeid6 = '';//商品id
        $btypeid7 = $btypeInfo['typeId'];//结算单位#关联表
        $etypeid8 = '';//本地系统订单没有这块逻辑,先用默认职员代替
        if($actionUser['login_type'] == 2){
            $where = [];
            $where['userId'] = $actionUser['id'];
            $where['shopId'] = $orderInfo['shopId'];
            $employeeInfo = madeDB('user_erp_employee_relation')->where($where)->find();
            $etypeid8 = $employeeInfo['typeId'];
        }
        $ktypeid9 = $stockInfo['typeId'];//仓库id
        $ktypeid210 = '';
        $ifcheck11 = 'f';
        //$checke12 = $btypeInfo['btypeTypeId'];//制单人
        $checke12 = $etypeid8;
        $totalmoney13 = $orderInfo['realTotalMoney'];
        $totalinmoney14 = .0000000000;
        $totalqty15 = $countGoods;
        $tax16 = .0000000000;
        $period17 = 1;
        $explain18 = $userInfoStr;
        $RedWord19 = 0;
        $draft20 = '1';
        $OrderId21 = 0;
        $waybillcode22 = '';
        $goodsnumber23 = '';
        $packway24 = '';
        $TEL25 = '';
        $Uploaded26 = '0';
        $OFF_ID27 = $newId;
        $TeamNo28 = NULL;
        $AlertDay29 = 9999;
        $CompleteTotal30 = .0000000000;
        $ID31 = 0;
        $Assessor32 = '';
        $IfAudit33 = ' ';
        $Audit_explain34 = '';
        $IfStopMoney35 = '0';
        $preferencemoney36 = '.0000000000';
        $DTypeId37 = $DepartmentInfo['typeid'];
        $JF38 = 0;
        $VipCardID39 = -1;
        $vipCZMoney40 = .0000000000;
        $jsStyle41 = '0';
        $jsState42 = '0';
        $KTypeID343 = '';
        $LinkMan44 = $userInfo['userName'];
        $LinkTel45 = $userInfo['userPhone'];
        $LinkAddr46 = $orderInfo['userAddress'];
        $wxManID47 = '';
        $JieJianBillNumberID48 = -1;
        $JieJianID49 = -1;
        $JieJianState50 = -1;
        $BaoXiuBillNumberID51 = -1;
        $BaoXiuID52 = -1;
        $BuyDate53 = '';
        $Serial54 = '';
        $wxTotal55 = .0000000000;
        $ErrDes56 = '';
        $ifHideJieJian57 = 0;
        $ifWX58 = 0;
        $ifYearBill59 = 0;
        $CWCheck60 = 0;
        $IfBargainOn61 = 0;
        $LastUpdateTime62 = $time;
        $Poster63 = '';
        $STypeID64 = $styleInfo['TypeId'];//分支机构id
        $FromBillNumberID65 = 0;
        $CostSale66 = '0';
        $IfMulAccount67 = 0;
        $OtherInOutType68 = -1;
        $PosBillType69 = 0;
        $checkTime70 = $time;
        $posttime71 = NULL;
        $updateE72 = $etypeid8;//没注释,不清楚
        $ShareMode73 = 0;
        $IsFreightMoney74 = 0;
        $FreightAddr75 = '';
        $FreightPerson76 = '';
        $FreightTel77 = 0;
        $IfInvoice78 = 0;
        $TransportId79 = '0';
        $UF180 = '';
        $UF281 = '';
        $UF382 = '';
        $UF483 = '';
        $UF584 = '';
        $ATID85 = 0;
        //$DealBTypeID86 = '00002000640000200001';//往来单位#关联表,先写死
        $DealBTypeID86 = $btypeInfo['typeId'];//往来单位
        $CID87 = 1;
        $Rate88 = 1;
        $NTotalMoney89 = $orderInfo['realTotalMoney'];
        $NTotalInMoney90 = .0000000000;
        $NPreferenceMoney91 = .0000000000;
        $NVIPCZMoney92 = .0000000000;
        $IsIni93 = '0';
        $ATypeID194 = '';
        $NCompleteTotal95 = .0000000000;
        $billproperty96 = -1;
        $TallyId97 = '';
        $IfCargo98 = '0';
        $InvoiceMoney99 = .0000000000;
        $NInvoiceMoney100 = .0000000000;
        $RedReason101 = '';
        $NewPosBill102 = 0;
        $PrePriceNum103 = 1;
        $AssetBusinessTypeId104 = 0;
        $PromoRuleId105 = 0;
        $BeforePromoBillNumberId106 = 0;
        $ImportType107 = 0;
        $PromotionMsg108 = '';//N''
        $Reconciliation109 = 'f';
        $ARTOTAL110 = .0000000000;
        $APTOTAL111 = .0000000000;
        $YSTOTAL112 = .0000000000;
        $YFTOTAL113 = .0000000000;
        $IsAuto114 = 0;
        $StypeID2115 = '';
        $IfConfirm116 = 0;
        $TransferType117 = 1;
        $FeeDeductType118 = 0;
        $IsYapi119 = '0';
        $YapiOrderID120 = 0;
        $DeliveryAddress121 = $explain18;//送货地址
        $AddDTypeETypeType122 = 0;
        $BillTime123 = $explodeTime[1];
        $IsLend124 = '0';
        $Discount125 = 1;//扣率
        $AdPriceType126 = 0;
        $ChargeOffBtypeid127 = '';
        $ChargeOffType128 = 0;
        $ChargeOffTotal129 = .0000000000;
        $NChargeOffTotal130 = .0000000000;
        $cwchecker131 = '';
        $InFactRefState132 = 0;
        $OutFactRefState133 = 0;
        $InFactState134 = 0;
        $OutFactState135 = 0;
        $ShelveState136 = 0;
        $ShelveRefState137 = 0;
        $TransPortState138 = 0;
        $iscombinationstyle139 = 0;
        $progressstate140 = 0;
        $citestate141 = 0;
        $IsNeedTransport142 = '0';
        $shipper143 = NULL;
        $totalfreight144 = .0000000000;
        $ntotalfreight145 = .0000000000;
        $transportno146 = NULL;
        $WarehouseOutDay147 = '9999';
        $StockInDeadline148 = '9999';
        $Transporterid149 = NULL;
        $Transporterfullname150 = NULL;
        $shipperfullname151 = NULL;
        $shipperperson152 = NULL;
        $shippertel153 = NULL;
        $FaPiaoCode154 = '';
        $PayType155 = 0;
        $PosId156 = 0;
        $PromonIds157 = '';
        $isByBrandarType158 = 0;
        $PayOnTheSpot159 = '';
        $UploadPreOrder160 = 0;
        $PreOrderStatus161 = 0;
        $PreOrderId162 = '';

        $value = "'{$BillDate1}', '{$BillCode2}', $BillType3, '{$Comment4}', '{$atypeid5}', '{$ptypeid6}', '{$btypeid7}', '{$etypeid8}', '{$ktypeid9}', '{$ktypeid210}', '{$ifcheck11}', '{$checke12}', $totalmoney13, $totalinmoney14, $totalqty15, $tax16, $period17, '{$explain18}', $RedWord19, '{$draft20}', $OrderId21, '{$waybillcode22}', '{$goodsnumber23}', '{$packway24}', '{$TEL25}', '{$Uploaded26}', '{$OFF_ID27}', '{$TeamNo28}', $AlertDay29, $CompleteTotal30, $ID31, '{$Assessor32}', '{$IfAudit33}', '{$Audit_explain34}', '{$IfStopMoney35}', '{$preferencemoney36}', '{$DTypeId37}', $JF38, $VipCardID39, $vipCZMoney40, '{$jsStyle41}', '{$jsState42}', '{$KTypeID343}', '{$LinkMan44}', '{$LinkTel45}', '{$LinkAddr46}', '{$wxManID47}', $JieJianBillNumberID48, $JieJianID49, $JieJianState50, $BaoXiuBillNumberID51, $BaoXiuID52, '{$BuyDate53}', '{$Serial54}', $wxTotal55, '{$ErrDes56}', $ifHideJieJian57, $ifWX58, $ifYearBill59, $CWCheck60, $IfBargainOn61, '{$LastUpdateTime62}', '{$Poster63}', '{$STypeID64}', $FromBillNumberID65, '{$CostSale66}', $IfMulAccount67, $OtherInOutType68, $PosBillType69, '{$checkTime70}', '{$posttime71}', '{$updateE72}', $ShareMode73, $IsFreightMoney74, '{$FreightAddr75}', '{$FreightPerson76}', $FreightTel77, $IfInvoice78, '{$TransportId79}', '{$UF180}', '{$UF281}', '{$UF382}', '{$UF483}', '{$UF584}', $ATID85, '{$DealBTypeID86}', $CID87, $Rate88, $NTotalMoney89, $NTotalInMoney90, $NPreferenceMoney91, $NVIPCZMoney92, '{$IsIni93}', '{$ATypeID194}', $NCompleteTotal95, $billproperty96, '{$TallyId97}', '{$IfCargo98}', $InvoiceMoney99, $NInvoiceMoney100, '{$RedReason101}', $NewPosBill102, $PrePriceNum103, $AssetBusinessTypeId104, $PromoRuleId105, $BeforePromoBillNumberId106, $ImportType107, '{$PromotionMsg108}', '{$Reconciliation109}', $ARTOTAL110, $APTOTAL111, $YSTOTAL112, $YFTOTAL113, $IsAuto114, '{$StypeID2115}', $IfConfirm116, $TransferType117, $FeeDeductType118, '{$IsYapi119}',$YapiOrderID120, '{$DeliveryAddress121}', $AddDTypeETypeType122, '{$BillTime123}', '{$IsLend124}', $Discount125, $AdPriceType126, '{$ChargeOffBtypeid127}', $ChargeOffType128, $ChargeOffTotal129, $NChargeOffTotal130, '{$cwchecker131}', $InFactRefState132, $OutFactRefState133, $InFactState134, $OutFactState135, $ShelveState136, $ShelveRefState137, $TransPortState138, $iscombinationstyle139, $progressstate140, $citestate141, '{$IsNeedTransport142}', '{$shipper143}', $totalfreight144, $ntotalfreight145, '{$transportno146}', '{$WarehouseOutDay147}', '{$StockInDeadline148}', '{$Transporterid149}','{$Transporterfullname150}', '{$shipperfullname151}', '{$shipperperson152}', '{$shippertel153}', '{$FaPiaoCode154}', $PayType155, $PosId156, '{$PromonIds157}', $isByBrandarType158, '{$PayOnTheSpot159}', $UploadPreOrder160, $PreOrderStatus161, '{$PreOrderId162}'";
        $sql = "INSERT INTO BillIndex($field) VALUES($value) ";
        $insertRes = sqlExcute($sql);
        //$insertRes = 1;
        if(!$insertRes){
            $apiRes['apiInfo'] = '单据添加失败';
            return $apiRes;
        }
        //BillIndex end
        $insertId = sqlInsertId('BillIndex');
        foreach ($orderGoods as $val){
            $sql = "SELECT typeId,UserCode,FullName,baseUnitId,SaleUnitId,BuyUnitId,ProduceUnitId,StockUnitId FROM ptype WHERE deleted=0 AND UserCode='".$val['goodsSn']."'";
            $erpGoodsInfo = sqlQuery($sql,'row');
            if(!$erpGoodsInfo){
                continue;
            }
            $erpGoodsId = $erpGoodsInfo['typeId'];
            //SaleBill start
            $BillNumberId = $insertId;//BillIndex表自增id
            $PtypeId = $erpGoodsId;//商品id
            $Qty = $val['goodsNums'];//数量
            $SalePrice = $val['goodsPrice'];//基本本币折前单价
            $goodsPriceTotal = $val['goodsPrice'] * $val['goodsNums'];
            $total = $goodsPriceTotal;//金额
            $OutFactoryDate = date('Y-m-d H:i:s',time());
            $TaxPrice = $val['goodsPrice'];//折扣单价
            $TaxTotal = $goodsPriceTotal;//含税金额
            $SaleTotal = $goodsPriceTotal;//本币折扣前金额
            $KTypeID = $stockInfo['typeId'];//分支机构
            $STypeID = $styleInfo['TypeId'];//仓库id
            $NSaleTotal = $goodsPriceTotal;//原币金额
            $NDiscountPrice = $val['goodsPrice'];//基本单位原币折扣单价
            $NTotal = $val['goodsPrice'] * $val['goodsNums'];//原币折后金额
            $NTaxPrice = $val['goodsPrice'];//基本单位原币含税单价
            $NTaxTotal = $val['goodsPrice'] * $val['goodsNums'];//原币价税合计
            $UnitID = $erpGoodsInfo['baseUnitId'];//基本单位
            $MUnitID = $UnitID;//大单位
            $MQty = $val['goodsNums'];//大单位数量
            $MSalePrice = $val['goodsPrice'];//大单位单价
            $CurMSalePrice = $val['goodsPrice'];//大单位原币折前单价
            $CurMDiscountPrice = $val['goodsPrice'];//大单位原币折后价
            $CargoID = $cargoInfo['CargoID'];//CargoType表自增id
            $PriceSource = '手工输入';//价格来源
            $ShowOrder = '1';

            $DiscountPrice = $val['goodsPrice'];
            $NSalePrice = $val['goodsPrice'];
            $MDiscountPrice = $val['goodsPrice'];
            $MTaxPrice = $val['goodsPrice'];
            $CurMTaxPrice = $val['goodsPrice'];
            //关联表数据,先写死
            //$ETypeID = $employeeInfo['typeid'];//职员id
            $ETypeID = '';//职员id
            if($actionUser['login_type'] == 2){
                $where = [];
                $where['userId'] = $actionUser['id'];
                $where['shopId'] = $orderInfo['shopId'];
                $employeeInfo = madeDB('user_erp_employee_relation')->where($where)->find();
                $ETypeID = $employeeInfo['typeId'];
            }
            $DraFtFlag = 1;//草稿库存:0:过账|1:草稿
            $field = "BillNumberId,PtypeId,Qty,SalePrice,total,OutFactoryDate,TaxPrice,TaxTotal,SaleTotal,KTypeID,STypeID,NSaleTotal,NDiscountPrice,NTotal,NTaxPrice,NTaxTotal,UnitID,MUnitID,MQty,MSalePrice,CurMSalePrice,CurMDiscountPrice,CargoID,PriceSource,ShowOrder,[etypeid],DraFtFlag,DiscountPrice,NSalePrice,MDiscountPrice,MTaxPrice,CurMTaxPrice";
            $value = "'{$BillNumberId}','{$PtypeId}','{$Qty}','{$SalePrice}','{$total}','{$OutFactoryDate}','{$TaxPrice}','{$TaxTotal}','{$SaleTotal}','{$KTypeID}','{$STypeID}','{$NSaleTotal}','{$NDiscountPrice}','{$NTotal}','{$NTaxPrice}','{$NTaxTotal}','{$UnitID}','{$MUnitID}','{$MQty}','{$MSalePrice}','{$CurMSalePrice}','{$CurMDiscountPrice}','{$CargoID}','{$PriceSource}','{$ShowOrder}','{$ETypeID}',{$DraFtFlag},'{$DiscountPrice}','{$NSalePrice}','{$MDiscountPrice}','{$MTaxPrice}','{$CurMTaxPrice}'";
            $sql = "INSERT INTO SaleBill($field) VALUES($value)";
            $insertRes = sqlExcute($sql);
            //SaleBill end
        }
        if(!$insertRes){
            $apiRes['apiInfo'] = '添加ERP销售出库单失败';
            return $apiRes;
        }else{
            $apiRes['apiCode'] = 0;
            $apiRes['apiState'] = 'success';
            $apiRes['apiInfo'] = '添加ERP销售出库单成功';
            return $apiRes;
        }
    }

    //保存分类
    public function Merchantapi_ShopsCats_addCats($parameter=array()){
        $res = ['code'=>0,'msg'=>'添加成功'];
        $m = M('shops_cats');
        $number = $parameter['number'];//分类编码
        $where = [];
        $where['shopId'] = $parameter['shopId'];
        $where['catFlag'] = 1;
        $where['number'] = $number;
        $catInfo = $m->where($where)->find();
        if($catInfo){
            $res['code'] = -1;
            $res['msg'] = '分类编码已被使用，请更换其他编码';
            return $res;
        }
        $data = array();
        $data['shopId'] = $parameter['shopId'];
        $data['parentId'] = $parameter['parentId']?$parameter['parentId']:0;
        $data['isShow'] = isset($parameter['isShow'])?$parameter['isShow']:1;
        $data['catName'] = $parameter['catName'];
        $data['catSort'] = $parameter['catSort'];
        $data['catFlag'] = 1;
        $data['icon'] = $parameter['icon'];
        $data['number'] = $number;//分类编码
        $insertRes = $m->add($data);
        if($insertRes){
            $res['code'] = 0;
            $res['msg'] = '添加成功';
        }else{
            $res['code'] = -1;
            $res['msg'] = '添加失败';
        }
        return $res;
    }

    /**
     * 编辑分类信息
     */
    public function Merchantapi_ShopsCats_editName($params){
        $rd = array('status'=>-1,'msg'=>'修改失败');
        $id = $params['id'];
        $m = M('shops_cats');
        unset($params['id']);
        $rs = $m->where("catId=".$id)->save($params);
        if($rs !== false){
            $rd = array('status'=>1,'msg'=>'修改成功');
        }
        return $rd;
    }

    /*
     * 更新职员信息
     * @param array $stockInfo 仓库信息
     * */
    public function syncEmployee($shopInfo){
        $where = [];
        $where['shopId'] = $shopInfo['shopId'];
        $where['dataFlag'] = 1;
        $shopStockRelation = madeDB('shops_erp_stock_relation')->where($where)->find();
        //仓库信息
        $sql = "SELECT typeId,parid,Kid,STypeID FROM Stock WHERE typeId='".$shopStockRelation['typeId']."' AND deleted=0 ";
        $stockInfo = sqlQuery($sql,'row');
        if(empty($stockInfo)){
            return returnData(false,'-1','error',"ERP仓库信息有误");
        }
        $stockInfo['shopId'] = $shopInfo['shopId'];
        $sql = "SELECT typeId,Parid,leveal,FullName,Eid,UserCode,Department,DtypeID,STypeID,Mob,Mail FROM employee WHERE STypeID ='".$stockInfo['STypeID']."' AND leveal=3 AND deleted=0 ";
        $employeeList = sqlQuery($sql);
        if(empty($employeeList)){
            return returnData(null,-1,'error','ERP暂无相关职员');
        }
        $userRelationData = [];
        $userRoleInfoData = [];
        foreach ($employeeList as $key=>$val){
            $where = [];
            $where['dataFlag'] = 1;
            $where['typeId'] = $val['typeId'];
            $where['shopId'] = $stockInfo['shopId'];
            $employeeRelationInfo = madeDB('user_erp_employee_relation')->where($where)->find();//职员和职员关联
            //角色
            $where = [];
            $where['shopId'] = $stockInfo['shopId'];
            $where['name'] = $val['Department'];
            $roleInfo = M('role')->where($where)->find();
            if(empty($roleInfo)){
                $roleData = [];
                $roleData['name'] = $val['Department'];
                $roleData['status'] = 1;
                $roleData['shopId'] = $stockInfo['shopId'];
                $roleId = M('role')->add($roleData);
            }else{
                $roleId = $roleInfo['id'];
            }
            $userWhere = [];
            $userWhere['id'] = $employeeRelationInfo['userId'];
            $userWhere['status'] = ["IN",["0","-2"]];
            $userInfo = M('user')->where($userWhere)->find();
            if(empty($userInfo)){
                $employeeRelationInfo = [];
                madeDB('user_erp_employee_relation')->where(['shopId'=>$stockInfo['shopId'],'userId'=>$employeeRelationInfo['userId']])->save(['dataFlag'=>-1]);
            }
            if(empty($employeeRelationInfo)){
                $addTime = time();
                $userInfo = [];
                $userInfo['name'] = $val['FullName'];
                $userInfo['pass'] = md5($val['FullName'].$addTime);
                $userInfo['username'] = $val['FullName'];
                $userInfo['email'] = $val['Mail'];
                $userInfo['phone'] = $stockInfo['Mob'];
                $userInfo['addtime'] = $addTime;
                $userInfo['shopId'] = $stockInfo['shopId'];
                $userId = M('user')->add($userInfo);
                if($userId > 0 ){
                    $userRelationDataInfo = [];
                    $userRelationDataInfo['userId'] = $userId;
                    $userRelationDataInfo['typeId'] = $val['typeId'];
                    $userRelationDataInfo['shopId'] = $stockInfo['shopId'];
                    $userRelationDataInfo['dataFlag'] = 1;
                    $userRelationData[] = $userRelationDataInfo;
                }
            }else{
                $userId = $employeeRelationInfo['userId'];
                $editUserInfo = [];
                $editUserInfo['username'] = $val['FullName'];
                $editUserInfo['phone'] = $val['Mob'];
                $editUserInfo['email'] = $val['Mail'];
                M('user')->where(['id'=>$employeeRelationInfo['userId']])->save($editUserInfo);
            }
            $where = [];
            $where['rid'] = $roleId;
            $where['uid'] = $userId;
            $where['shopId'] = $stockInfo['shopId'];
            $userRoleInfo = M('user_role')->where($where)->find();
            if(empty($userRoleInfo)){
                $userRoleInfoDataInfo = [];
                $userRoleInfoDataInfo['rid'] = $roleId;
                $userRoleInfoDataInfo['uid'] = $userId;
                $userRoleInfoDataInfo['shopId'] = $stockInfo['shopId'];
                $userRoleInfoData[] = $userRoleInfoDataInfo;
            }
        }
        if(!empty($userRoleInfoData)){
            M('user_role')->addAll($userRoleInfoData);
        }
        if(!empty($userRelationData)){
            madeDB('user_erp_employee_relation')->addAll($userRelationData);
        }
        //return true;
        return returnData(true);
    }
}
?>
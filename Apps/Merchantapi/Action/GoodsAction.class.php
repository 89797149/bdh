<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;
use App\Modules\PYCode\PYCodeModule;
use Home\Model\GoodsModel;

use App\Modules\Rank\RankServiceModule;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品控制器
 */
class GoodsAction extends BaseAction
{
    /**
     * 商品列表
     */
    public function getGoodsList()
    {

        $searchdata = I('get.keyWords', '', 'strip_tags');//指定过滤方式
        $keyWords = iconv('gbk', 'utf-8', $searchdata);//转换编码

        $mgoods = D('Home/Goods');
        $mareas = D('Home/Areas');
        $mcommunitys = D('Home/Communitys');
        //获取默认城市及县区
        $areaId2 = $this->getDefaultCity();
        $districts = $mareas->getDistricts($areaId2);
        //获取社区
        $areaId3 = (int)I("areaId3");
        $communitys = array();
        if ($areaId3 > 0) {
            $communitys = $mcommunitys->getByDistrict($areaId3);
        }
        $this->assign('communitys', $communitys);

        //获取商品列表
        $obj["areaId2"] = $areaId2;
        $obj["areaId3"] = $areaId3;
        $rslist = $mgoods->getGoodsList($obj);
        $brands = $rslist["brands"];
        $pages = $rslist["pages"];
        $goodsNav = $rslist["goodsNav"];
        $this->assign('goodsList', $rslist);
        //动态划分价格区间
        $maxPrice = $rslist["maxPrice"];
        $minPrice = 0;
        $pavg5 = ($maxPrice / 5);
        $prices = array();
        $price_grade = 0.0001;
        for ($i = -2; $i <= log10($maxPrice); $i++) {
            $price_grade *= 10;
        }
        //区间跨度
        $span = ceil(($maxPrice - $minPrice) / 8 / $price_grade) * $price_grade;
        if ($span == 0) {
            $span = $price_grade;
        }
        for ($i = 1; $i <= 8; $i++) {
            $prices[($i - 1) * $span . "_" . ($span * $i)] = ($i - 1) * $span . "-" . ($span * $i);
            if (($span * $i) > $maxPrice) break;
        }
        if (count($prices) < 5) {
            $prices = array();
            $prices["0_100"] = "0-100";
            $prices["100_200"] = "100-200";
            $prices["200_300"] = "200-300";
            $prices["300_400"] = "300-400";
            $prices["400_500"] = "400-500";
        }
        $this->assign('c1Id', (int)I("c1Id"));
        $this->assign('c2Id', (int)I("c2Id"));
        $this->assign('c3Id', (int)I("c3Id"));
        $this->assign('msort', (int)I("msort", 0));
        $this->assign('mark', (int)I("mark", 0));
        $this->assign('stime', I("stime"));//上架开始时间
        $this->assign('etime', I("etime"));//上架结束时间

        $this->assign('areaId3', (int)I("areaId3", 0));
        $this->assign('communityId', (int)I("communityId", 0));

        $pricelist = explode("_", I("prices"));
        $this->assign('sprice', (int)$pricelist[0]);
        $this->assign('eprice', (int)$pricelist[1]);

        $this->assign('brandId', (int)I("brandId", 0));
        $this->assign('keyWords', $keyWords);
        $this->assign('brands', $brands);
        $this->assign('goodsNav', $goodsNav);
        $this->assign('pages', $pages);
        $this->assign('prices', $prices);
        $priceId = $prices[I("prices")];
        $this->assign('priceId', (strlen($priceId) > 1) ? I("prices") : '');
        $this->assign('districts', $districts);
        $this->display('default/goods_list');
    }


    /**
     * 查询商品详情
     *
     */
    public function getGoodsDetails()
    {

        $goods = D('Home/Goods');
        $kcode = I("kcode");
        $scrictCode = base64_encode(md5("wstmall" . date("Y-m-d")));

        //查询商品详情
        $goodsId = (int)I("goodsId");
        $this->assign('goodsId', $goodsId);
        $obj["goodsId"] = $goodsId;
        $goodsDetails = $goods->getGoodsDetails($obj);
        if ($kcode == $scrictCode || ($goodsDetails["isSale"] == 1 && $goodsDetails["goodsStatus"] == 1)) {
            if ($kcode == $scrictCode) {//来自后台管理员
                $this->assign('comefrom', 1);
            }

            $shopServiceStatus = 1;
            if ($goodsDetails["shopAtive"] == 0) {
                $shopServiceStatus = 0;
            }
            $goodsDetails["serviceEndTime"] = str_replace('.', ':', $goodsDetails["serviceEndTime"]);
            $goodsDetails["serviceEndTime"] = str_replace('.', ':', $goodsDetails["serviceEndTime"]);
            $goodsDetails["serviceStartTime"] = str_replace('.', ':', $goodsDetails["serviceStartTime"]);
            $goodsDetails["serviceStartTime"] = str_replace('.', ':', $goodsDetails["serviceStartTime"]);
            $goodsDetails["shopServiceStatus"] = $shopServiceStatus;
            $goodsDetails['goodsDesc'] = htmlspecialchars_decode($goodsDetails['goodsDesc']);


            $areas = D('Home/Areas');
            $shopId = intval($goodsDetails["shopId"]);
            $obj["shopId"] = $shopId;
            $obj["areaId2"] = $this->getDefaultCity();
            $obj["attrCatId"] = $goodsDetails['attrCatId'];
            $shops = D('Home/Shops');
            $shopScores = $shops->getShopScores($obj);
            $this->assign("shopScores", $shopScores);

            $shopCity = $areas->getDistrictsByShop($obj);
            $this->assign("shopCity", $shopCity[0]);

            $shopCommunitys = $areas->getShopCommunitys($obj);
            $this->assign("shopCommunitys", json_encode($shopCommunitys));

            $this->assign("goodsImgs", $goods->getGoodsImgs());
            $this->assign("relatedGoods", $goods->getRelatedGoods($goodsId));
            $this->assign("goodsNav", $goods->getGoodsNav());
            $this->assign("goodsAttrs", $goods->getAttrs($obj));
            $this->assign("goodsDetails", $goodsDetails);

            $viewGoods = cookie("viewGoods");
            if (!in_array($goodsId, $viewGoods)) {
                $viewGoods[] = $goodsId;
            }
            if (!empty($viewGoods)) {
                cookie("viewGoods", $viewGoods, 25920000);
            }
            //获取关注信息
            $m = D('Home/Favorites');
            $this->assign("favoriteGoodsId", $m->checkFavorite($goodsId, 0));
            $m = D('Home/Favorites');
            $this->assign("favoriteShopId", $m->checkFavorite($shopId, 1));
            //客户端二维码
            $this->assign("qrcode", base64_encode("{type:'goods',content:'" . $goodsId . "',key:'niaocms'}"));
            $this->display('default/goods_details');
        } else {
            $this->display('default/goods_notexist');
        }

    }

    /**
     * 获取商品库存
     *
     */
    public function getGoodsStock()
    {
        $data = array();
        $data['goodsId'] = (int)I('goodsId');
        $data['isBook'] = (int)I('isBook');
        $data['goodsAttrId'] = (int)I('goodsAttrId');
        $goods = D('Home/Goods');
        $goodsStock = $goods->getGoodsStock($data);
        echo json_encode($goodsStock);

    }

    /**
     * 获取服务社区
     *
     */
    public function getServiceCommunitys()
    {

        $areas = D('Home/Areas');
        $serviceCommunitys = $areas->getShopCommunitys();
        echo json_encode($serviceCommunitys);
    }

    //商家商品列表
    public function queryGetGoodsList()
    {
        $shopInfo = $this->MemberVeri();
        //获取商家商品分类
//        $m = D('Home/ShopsCats');
//        $this->assign('shopCatsList',$m->queryByList($shopInfo['shopId'],0));
//        $m = D('Home/Goods');
        $m = new GoodsModel();
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $paramete['page'] = I('page', 1);
        $paramete['pageSize'] = I('pageSize', 15);
        $data = $m->queryGetGoodsList($paramete);
        //$this->returnResponse(1, '获取成功', $page);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 修改商家商品价格
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/poml7z
     */
    public function upGoodsPrice()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId', 0);
        $skuId = (int)I('skuId', 0);
        $shopPrice = I('shopPrice');
        if (empty($goodsId) && empty($skuId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $requestParams = [];
        $requestParams['goodsId'] = $goodsId;
        $requestParams['skuId'] = $skuId;
        $requestParams['shopPrice'] = $shopPrice;
        $m = new GoodsModel();
        $rs = $m->upGoodsPrice($loginUserInfo, $requestParams);
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商家商品库存
     * https://www.yuque.com/anthony-6br1r/oq7p0p/meynae
     */
    public function upGoodsStock()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId', 0);
        $skuId = (int)I('skuId', 0);
        $goodsStock = I('goodsStock');
        if (empty($goodsId) && empty($skuId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if ($goodsStock < 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', '库存不能小于0'));
        }
        $requestParams = [];
        $requestParams['goodsId'] = $goodsId;
        $requestParams['skuId'] = $skuId;
        $requestParams['goodsStock'] = $goodsStock;
        $m = new GoodsModel();
        $rs = $m->upGoodsStock($loginUserInfo, $requestParams);
        $this->ajaxReturn($rs);
    }

    /**
     * 分页查询-出售中的商品
     */
    public function queryOnSaleByPage()
    {
        $shopInfo = $this->MemberVeri();
        //获取商家商品分类
        $m = D('Home/ShopsCats');
        $this->assign('shopCatsList', $m->queryByList($shopInfo['shopId'], 0));
        $m = D('Home/Goods');
        $page = $m->queryOnSaleByPage($shopInfo['shopId']);
        $pager = new \Think\Page($page['total'], $page['pageSize']);
        $page['pager'] = $pager->show();
        $this->assign('Page', $page);
        $this->assign("umark", "queryOnSaleByPage");
        $this->assign("shopCatId2", I('shopCatId2'));
        $this->assign("shopCatId1", I('shopCatId1'));
        $this->assign("goodsName", I('goodsName'));
        $this->display("default/shops/goods/list_onsale");
    }

    /**
     * 查询电子秤
     */
    public function ElectronicScaleList()
    {
        $shopInfo = $this->MemberVeri();

        $this->display("default/shops/orders/list_ElectronicScaleList");
    }

    /**
     * 分页查询-仓库中的商品
     */
    public function queryUnSaleByPage()
    {
        $shopInfo = $this->MemberVeri();

        //获取商家商品分类
        $m = D('Home/ShopsCats');
        $this->assign('shopCatsList', $m->queryByList($shopInfo['shopId'], 0));
        $m = D('Home/Goods');
        $page = $m->queryUnSaleByPage($shopInfo['shopId']);
        $pager = new \Think\Page($page['total'], $page['pageSize']);
        $page['pager'] = $pager->show();
        $this->assign('Page', $page);
        $this->assign("umark", "queryUnSaleByPage");
        $this->assign("shopCatId2", I('shopCatId2'));
        $this->assign("shopCatId1", I('shopCatId1'));
        $this->assign("goodsName", I('goodsName'));
        $this->display("default/shops/goods/list_unsale");
    }

    /**
     * 分页查询-在审核中的商品
     */
    public function queryPenddingByPage()
    {
        $shopInfo = $this->MemberVeri();

        //获取商家商品分类
        $m = D('Home/ShopsCats');
        $this->assign('shopCatsList', $m->queryByList($shopInfo['shopId'], 0));
        $m = D('Home/Goods');
        $page = $m->queryPenddingByPage($shopInfo['shopId']);
        $pager = new \Think\Page($page['total'], $page['pageSize']);
        $page['pager'] = $pager->show();
        $this->assign('Page', $page);
        $this->assign("umark", "queryPenddingByPage");
        $this->assign("shopCatId2", I('shopCatId2'));
        $this->assign("shopCatId1", I('shopCatId1'));
        $this->assign("goodsName", I('goodsName'));
        $this->display("default/shops/goods/list_pendding");
    }

//	/**
//	 * 跳到新增/编辑商品
//	 */
//    public function toEdit(){
//		$shopInfo = $this->MemberVeri();
//
//		//获取商品分类信息
//		$m = D('Home/GoodsCats');
//		$this->assign('goodsCatsList',$m->queryByList());
//		//获取商家商品分类
//		$m = D('Home/ShopsCats');
//		$this->assign('shopCatsList',$m->queryByList($shopInfo['shopId'],0));
//		//获取商品类型
//		$m = D('Home/AttributeCats');
//		$this->assign('attributeCatsCatsList',$m->queryByList());
//		$m = D('Home/Goods');
//		$object = array();
//    	if(I('id',0)>0){
//    		$object = $m->get();
//    	}else{
//    		$object = $m->getModel();
//    	}
//    	$this->assign('object',$object);
//    	$this->assign("umark",I('umark'));
//        $this->display("default/shops/goods/edit");
//	}


    /**
     * 跳到新增/编辑商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vq4cxz
     */
    public function getGoodsInfo()
    {
        $shopInfo = $this->MemberVeri();
//        $m = D('Home/Goods');
        $m = new GoodsModel();
        //$object = array();
        if (I('id', 0) > 0) {
            $object = $m->get($shopInfo);
        } else {
            $object = $m->getModel();
        }
        //$this->returnResponse(1, '获取成功', $object);
        $this->ajaxReturn(returnData($object));
//    	$this->assign('object',$object);
//    	$this->assign("umark",I('umark'));
//        $this->display("default/shops/goods/edit");
    }

    /**
     * 编辑商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ladloc
     */
    public function edit()
    {
        $login_info = $this->MemberVeri();
        $request_params = I();
        $goods_id = (int)$request_params['goodsId'];
        if (empty($goods_id)) {
            return returnData(false, -1, 'error', '参数有误');
        }
        $m = new GoodsModel();
        $data = $m->edit($login_info, $request_params);
        $this->ajaxReturn($data);
    }

    /**
     * 新增商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mqu89f
     */
    public function insert()
    {
        $login_info = $this->MemberVeri();
        $params = I();
//        $m = D('Home/Goods');
        $m = new GoodsModel();
        $data = $m->insert($login_info, $params);
        $this->ajaxReturn($data);
    }


    /**
     * 删除商品
     */
    public function del()
    {
        $loginUserInfo = $this->MemberVeri();
        //$m = D('Home/Goods');
        $m = new GoodsModel();
        $goodsId = I('id');
        if (empty($goodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $rs = $m->del($loginUserInfo, $goodsId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 批量设置商品状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/rggenx
     */
    public function goodsSet()
    {
        $loginUserInfo = $this->MemberVeri();
        $requestParams = I();
        if (empty($requestParams['ids']) || empty($requestParams['code'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $requestParams['tamk'] = (int)I('tamk', 1);
        $m = D('Home/Goods');
        $data = $m->goodsSet($loginUserInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 批量删除商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/viwtq2
     */
    public function batchDel()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = I('ids');
        if (empty($goodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        //$m = D('Home/Goods');
        $m = new GoodsModel();
        $rs = $m->batchDel($loginUserInfo, $goodsId);
        $this->ajaxReturn($rs);
    }

    /**
     * 废弃,请用goodsSet方法
     * 修改商品上架/下架状态
     */
    public function sale()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $rs = $m->sale();
        $this->ajaxReturn($rs);
    }


    /**
     * 核对商品信息
     */
    public function checkGoodsStock()
    {

        $m = D('Home/Cart');
        $catgoods = $m->checkGoodsStock();
        $this->ajaxReturn($catgoods);

    }

    /**
     * 获取验证码
     */
    public function getGoodsVerify()
    {
        $data = array();
        $data["status"] = 1;
        $verifyCode = base64_encode(md5("wstmall" . date("Y-m-d")));
        $data["verifyCode"] = $verifyCode;
        $this->ajaxReturn($data);
    }

    /**
     * 查询商品属性价格及库存
     */
    public function getPriceAttrInfo()
    {
        $goods = D('Home/Goods');
        $rs = $goods->getPriceAttrInfo();
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商品库存
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gf5n66
     */
    public function editStock()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $stock = (float)I('stock');
        if (empty($goodsId) || $stock < 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/Goods');
        $rs = $m->editStock($loginUserInfo, $goodsId, $stock);
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商品库存,价格,排序
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/howglb
     */
    public function editGoodsBase()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $vfield = trim(I('vfield', ''));
        $vtext = I('vtext', '');
        if (empty($vfield) || empty($vtext) || empty($goodsId) || !in_array($vfield, ['goodsStock', 'shopPrice', 'shopGoodsSort', 'selling_stock'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/Goods');
        $rs = $m->editGoodsBase($loginUserInfo, $goodsId, $vfield, $vtext);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取商品搜索提示列表
     */
    public function getKeyList()
    {
        $m = D('Home/Goods');
        $areaId2 = $this->getDefaultCity();
        $rs = $m->getKeyList($areaId2);
        $this->ajaxReturn($rs);
    }

    /**
     * 弃用,改用goodsSet方法
     * 修改 推荐/精品/新品/热销/上架
     */
    public function changSaleStatus()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $rs = $m->changSaleStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 上传商品数据
     */
    public function importGoods()
    {
        $shopInfo = $this->MemberVeri();
        $config = array(
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('xls', 'xlsx', 'xlsm'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );
        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $rv = array('status' => -1);
        if (!$rs) {
            $rv['msg'] = $upload->getError();
        } else {
//            $m = D('Home/Goods');
            $m = new GoodsModel();
            $rs['shopId'] = $shopInfo['shopId'];
            $rv = $m->importGoods($rs);
        }
        $this->ajaxReturn($rv);
    }


    /**
     * 弃用,改用editGoodsBase方法
     * 修改店铺商品排序号
     */
    public function editShopGoodsSort()
    {
        $obj['shopId'] = $this->MemberVeri()['shopId'];
        $obj['goodsId'] = (int)I("goodsId", 0);
        $obj["shopGoodsSort"] = (int)I("shopGoodsSort");
        $m = D('Home/Goods');
        $rs = $m->editShopGoodsSort($obj);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取限时
     */
    public function getFlashSale()
    {
        //$shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $res = $m->getFlashSale();
        $this->ajaxReturn($res);
    }

    /**
     * 获取商品限时
     */
    public function getFlashSaleGoods()
    {
        $shopInfo = $this->MemberVeri();
        $goodsId = I('goodsId');
        if (empty($goodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Home/Goods');
        $request['goodsId'] = $goodsId;
        $res = $m->getFlashSaleGoods($request);
        $this->ajaxReturn($res);
    }


    /**
     * 弃用,改用商品详情getGoodsInfo
     * 获取商品信息(PS用于复制商品)
     */
    public function getGoodsDetailCopy()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $goodsSn = I('goodsSn', '');
        if (empty($goodsSn)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/Goods');
        $res = $m->getGoodsDetailCopy($shopId, $goodsSn);
        $this->ajaxReturn($res);
    }

    /*
     * 搜索商品 PS:用于商品复用
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xzo03x
     * */
    public function queryGetGoodsListCopy()
    {
        $this->MemberVeri();
        $requestParams = I();
        $m = D('Home/Goods');
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        if (empty($requestParams['shopSn']) && empty($requestParams['goodsName']) && empty($requestParams['goodsSn'])) {
            //$this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
            $data = [
                'total' => 0,
                'page' => $requestParams['page'],
                'pageSize' => $requestParams['pageSize'],
                'start' => 0,
                'root' => [],
                'totalPage' => 0,
                'currPage' => $requestParams['page'],
            ];
            $this->ajaxReturn(returnData($data));
        }
        $res = $m->queryGetGoodsListCopy($requestParams);
        $this->ajaxReturn($res);
    }

    /*
     * 复制商品
     * @param string goodsId PS:多个用英文逗号分隔
     * */
    public function goodsCopy()
    {
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>8];
        $request = I();
        !empty($request['goodsId']) ? $data['goodsId'] = $request['goodsId'] : false;
        $data['goodsId'] = $request['goodsId'];
        if (empty($data['goodsId'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $data['shopId'] = $shopInfo['shopId'];
        //$m = D('Home/Goods');
        $m = new GoodsModel();
        $res = $m->goodsCopy($data);
        $this->ajaxReturn($res);
    }

    /**
     * 复制商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sai683
     */
    public function copyInsert()
    {
        $shopInfo = $this->MemberVeri();
        $goodsIdCopy = (int)I('goodsIdCopy');
        if (empty($goodsIdCopy)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        //$m = D('Home/Goods');
        $m = new GoodsModel();
        $rs = $m->insert($shopInfo, I());
        $this->ajaxReturn($rs);
    }

    /**
     * 弃用,改用editGoodsBase接口
     * 修改单商品的价格
     * @param int goodsId
     * @param string price
     */
    public function editGoodsShopPrice()
    {
        $obj['shopId'] = $this->MemberVeri()['shopId'];
        $obj['goodsId'] = (int)I("goodsId", 0);
        $obj["shopPrice"] = I("price");
        $m = D('Home/Goods');
        $rs = $m->editGoodsShopPrice($obj);
        $this->ajaxReturn($rs);
    }

    /**
     * 跳到新增/编辑商品
     */
    public function getGoodsInfoCopy()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $object = array();
        if (I('id', 0) > 0) {
            $object = $m->getCopy($shopInfo);
        } else {
            $object = $m->getModel();
        }

        $this->returnResponse(1, '获取成功', $object);
    }

    /*
     * 获取商城分类
     * @param int parentId 父id
     * */
    public function getSystemGoodsCat()
    {
        $m = D('Home/Goods');
        $request['parentId'] = I('parentId', 0);
        $rs = $m->getSystemGoodsCat($request);
        $this->ajaxReturn($rs);
    }

    /*
     * 商品销量统计
     * */
    public function queryGetGoodsSaleCount()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $page = $m->queryGetGoodsSaleCount($paramete);
        $this->returnResponse(1, '获取成功', $page);
    }

    //商家导出商品列表
    public function exportGoodsList()
    {
        $shopInfo = $this->MemberVeri();
        //获取商家商品分类
//        $m = D('Home/ShopsCats');
//        $this->assign('shopCatsList',$m->queryByList($shopInfo['shopId'],0));
        $m = D('Home/Goods');
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $m->exportGoodsList($paramete);
        $this->returnResponse(1, '导出成功', array());
    }

    /**
     * 商品数据更新
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vu92aa
     * 以导入 excel 文件的方式 更新商品数据
     */
    public function goodsDataUpdate()
    {
//        $shopInfo = $this->MemberVeri();
        $shopId = $this->MemberVeri()['shopId'];
        $config = array(
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('xls', 'xlsx', 'xlsm'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );
        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        if (!$rs) {
            $this->ajaxReturn(returnData(false, -1, 'error', $upload->getError()));
        }
//        $m = D('Home/Goods');
        $m = new GoodsModel();
//        $data = $m->goodsDataUpdate($rs);
        $rs['shopId'] = $shopId;
        $data = $m->goodsDataUpdateNew($rs);//上面注释的是有问题的,重新复制该方法进行修复
        $this->ajaxReturn($data);
    }

    /**
     * 库存预警商品列表
     */
    public function stockWarningGoodsList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
//        if (empty($shopId)) {
//            $apiRet['apiInfo'] = '参数不全';
//            $this->ajaxReturn($apiRet);
//        }
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');
        $m = D('Home/Goods');
        $data = $m->getStockWarningGoodsList($shopId, $page, $pageSize);

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
//
//        $this->ajaxReturn($apiRet);
        $this->ajaxReturn(returnData($data));

    }

    /**
     * 统计库存预警商品的数量
     */
    public function stockWarningGoodsNum()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
        if (empty($shopId)) {
            $apiRet['apiInfo'] = '参数不全';
            $this->ajaxReturn($apiRet);
        }
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');
        $m = D('Home/Goods');
        $list = $m->getStockWarningGoodsList($shopId, $page, $pageSize);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = array('num' => $list['total']);

        $this->ajaxReturn($apiRet);
    }

    /**
     * 导出商品 plu 文件
     * 用于大华电子秤导入
     */
    public function exportGoodsPlu()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $goodsId = I('goodsId', '', 'trim');
        if (empty($goodsId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $goods_list = M('goods')->order('goodsId desc')->where(array('goodsId' => array('in', $goodsId)))->select();
        if (empty($goods_list)) {
            $apiRet['apiInfo'] = "无数据";
            $this->ajaxReturn($apiRet);
        }

        $content = "";
        foreach ($goods_list as $v) {

//            //根据包装系数计算500g的单价
//            $gm = (float)$v['shopPrice'] / (float)$v['weightG'];
//            $price = $gm * 500;
//            $price = sprintf("%.2f", $price);

            //特殊写法，请勿乱动

//            $content .= $v['goodsSn'] . ',0,,,' . $price . ',,,' . $v['goodsName'] . '
            $model_type = 0;
            if ($v['SuppPriceDiff'] != 1) {
                $model_type = 1;
                $price = $v['shopPrice'];
            } else {
                //根据包装系数计算500g的单价
//                $gm = (float)$v['shopPrice'] / (float)$v['weightG'];
//                $price = $gm * 500;
                $price = $v['shopPrice'];//上面的注释，貌似包装系数已经废弃了
                $price = sprintf("%.2f", $price);
            }
            $content .= "{$v['plu_code']},{$v['goodsName']},{$v['goodsSn']},{$price},{$model_type},0,0,0" . '
            ';
        }
        $content = mb_convert_encoding($content, "GBK", "UTF-8");
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename = plu.plu"); //文件命名
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        echo $content;
    }

    /**
     * 批量上下架
     * @param int action 操作【1：有库存批量批量上架|2：无库存批量下架】
     * @param string goodsIdStr 多个商品id用英文逗号分隔,不传默认为全部
     */
    public function batchUpdateGoodsSale()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $params = [];
        $params['action'] = null;
        $params['goodsIdStr'] = '';
        parm_filter($params, $requestParams);
        if (empty($params['action'])) {
            $error = returnData(null, -1, 'error', '字段有误');
            $this->ajaxReturn($error);
        }
        $m = D('Home/Goods');
        $res = $m->batchUpdateGoodsSale($shopId, $params['action'], $params['goodsIdStr']);
        $this->ajaxReturn($res);
    }

    /**
     * 获取商家出售中的商品列表【限量抢需要】
     */
    public function getSellGoodsList()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $params = I();
        $params['shopId'] = $shopInfo['shopId'];
//        $params['shopId'] = 1;
        $list = $m->getSellGoodsList($params);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 系统首页-商品总览
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/usmuew
     * */
    public function goodsOverview()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $data = $m->goodsOverview($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 商品回收站
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dhadxv
     * */
    public function getGoodsRecyclebin()
    {
        $loginUserInfo = $this->MemberVeri();
        $requestParams = I();
        $requestParams['page'] = I('page', 1);
        $requestParams['pageSize'] = I('pageSize', 15);
        $m = D('Home/Goods');
        $data = $m->getGoodsRecyclebin($loginUserInfo, $requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 商品回收站-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dt4x58
     * */
    public function delGoodsRecyclebin()
    {
        $loginUserInfo = $this->MemberVeri();
        $goodsId = I('goodsId');
        $actionStatus = (int)I('actionStatus');
        if (empty($goodsId) || !in_array($actionStatus, [-1, 1])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/Goods');
        $data = $m->delGoodsRecyclebin($loginUserInfo, $goodsId, $actionStatus);
        $this->ajaxReturn($data);
    }

    /**
     * 商品列表-统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/co15hg
     * */
    public function getGoodsCount()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/Goods');
        $data = $m->getGoodsCount($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     *门店商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uoprd7
     * */
    public function getShopGoodsList()
    {
        $loginUserInfo = $this->MemberVeri();
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
//        $m = D('Home/Goods');
        $m = new GoodsModel();
        $data = $m->getShopGoodsList($loginUserInfo, $params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取审核商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wng5lr
     * @param int shopCatId1 店铺一级分类id
     * @param int shopCatId2 店铺二级分类id
     * @param string keywords 关键字【商品名称|商品编码】
     * @param int goodsStatus 审核状态(-1:禁售 0:未审核 默认为全部(禁售和未审核))
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function getExamineGoodsList()
    {
        $loginUserInfo = $this->MemberVeri();
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $m = new GoodsModel();
        $data = $m->getExamineGoodsList($loginUserInfo, $params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 商品操作日志
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nclbnb
     * */
    public function getGoodsLog()
    {
        $this->MemberVeri();
        $params = I();
        if (empty($params['goodsId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $m = D('Home/Goods');
        $data = $m->getGoodsLog($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 商品-商品定时上下架-新增
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ndvhid
     * */
    public function addAutoGoods()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $goods_id_str = rtrim(I('goods_id_str'), ',');
        $sale_state = (int)I('sale_state');
        $action_time = I('action_time');
        if (empty($goods_id_str) || empty($action_time)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!in_array($sale_state, array(0, 1))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的上下架状态'));
        }
        $params = array(
            'shop_id' => $shop_id,
            'goods_id_str' => $goods_id_str,
            'sale_state' => $sale_state,
            'action_time' => $action_time,
        );
        $model = new GoodsModel();
        $data = $model->addAutoGoods($params);
        $this->ajaxReturn($data);
    }

    /**
     * 商品-商品定时上下架-修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gqe7hg
     * */
    public function updateAutoGoods()
    {
        $this->MemberVeri();
        $id_str = rtrim(I('id_str'), ',');
        $sale_state = (int)I('sale_state');
        $action_time = I('action_time');
        if (empty($id_str) || empty($action_time)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!in_array($sale_state, array(0, 1))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的上下架状态'));
        }
        $params = array(
            'id_str' => $id_str,
            'sale_state' => $sale_state,
            'action_time' => $action_time,
        );
        $model = new GoodsModel();
        $data = $model->updateAutoGoods($params);
        $this->ajaxReturn($data);
    }

    /**
     * 商品-商品定时上下架-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sgkpgm
     * */
    public function deleteAutoGoods()
    {
        $this->MemberVeri();
        $id_str = rtrim(I('id_str'), ',');
        if (empty($id_str)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $model = new GoodsModel();
        $data = $model->deleteAutoGoods($id_str);
        $this->ajaxReturn($data);
    }

    /**
     * 商品-商品定时上下架-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vwvcas
     * */
    public function getAutoGoodsList()
    {
        $login_info = $this->MemberVeri();
        $model = new GoodsModel();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $data = $model->getAutoGoodsList($login_info, $params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 商品-商品定时上下架-批量修改状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bz0m3d
     * */
    public function updateAutoGoodsState()
    {
        $this->MemberVeri();
        $id_str = rtrim(I('id_str'), ',');
        $sale_state = (int)I('sale_state');
        if (empty($id_str)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!in_array($sale_state, array(0, 1))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的上下架状态'));
        }
        $model = new GoodsModel();
        $data = $model->updateAutoGoodsState($id_str, $sale_state);
        $this->ajaxReturn($data);
    }

    /**
     * 设置默认商品单位,默认包装系数
     * @param key 执行秘钥
     * */
    public function autoGoodsUnit()
    {
        $key = I('key');
        if ($key != 'niaocms') {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入执行秘钥'));
        }
        $goods_model = M('goods');
        $where = array(
            'unit' => '',
            'goodsFlag' => 1,
        );
        $field = 'goodsId,goodsName,unit,SuppPriceDiff,weightG';
        $no_unit_goods = $goods_model->where($where)->field($field)->select();
        foreach ($no_unit_goods as $goods_key => $goods_detail) {
            $goods_id = $goods_detail['goodsId'];
            $save_data = array();
            if ($goods_detail['SuppPriceDiff'] == 1) {
                //非标品
                $save_data['unit'] = 'g';
            } else {
                //标品
                $save_data['unit'] = '份';
                $save_data['weightG'] = 1;
            }
            $goods_model->where(array(
                'goodsId' => $goods_id
            ))->save($save_data);
        }
        $sku_system_model = M('sku_goods_system');
        $sku_where = array();
        $sku_where['system.dataFlag'] = 1;
        $sku_where['system.unit'] = '';
        $sku_where['goods.goodsFlag'] = 1;
        $field = 'system.skuId,system.unit,system.weigetG,goods.SuppPriceDiff';
        $sku_list = M('sku_goods_system system')
            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
            ->where($sku_where)
            ->field($field)
            ->select();
        foreach ($sku_list as $sku_key => $sku_detail) {
            $sku_id = $sku_detail['skuId'];
            $sku_data = array();
            if ($sku_detail['SuppPriceDiff'] == 1) {
                //非标品
                $sku_data['unit'] = 'g';
                $sku_data['weigetG'] = 1;//sku重量未知,默认为1
            } else {
                //标品
                $sku_data['unit'] = '份';
                $sku_data['weigetG'] = 1;
            }
            $sku_system_model->where(array(
                'skuId' => $sku_id
            ))->save($sku_data);
        }
        $this->ajaxReturn(returnData(true));
    }

    /**
     * 初始化商品名称的拼音码
     * */
    public function initGoodsPyCode()
    {
        $page = I('page', 1);
        $page_size = I('page_size', 1000);
        $where = array(
            'goodsFlag' => 1,
        );
        $goods_list = M('goods')
            ->where($where)
            ->field('goodsId,goodsName,shopId')
            ->limit(($page - 1) * $page_size, $page_size)
            ->select();
        $goods_module = new GoodsModule();
        $py_module = new PYCodeModule();
        foreach ($goods_list as $goods_detail) {
            $save_params = array(
                'shopId' => $goods_detail['shopId'],
                'goodsId' => $goods_detail['goodsId'],
                'goodsName' => $goods_detail['goodsName'],
            );
            $py_code_detail = $py_module->getFullSpell($save_params['goodsName']);
            $save_params['py_code'] = $py_code_detail['py_code'];
            $save_params['py_initials'] = $py_code_detail['py_initials'];
            $save_res = $goods_module->editGoodsInfo($save_params);
        }
        dd('done');
    }

}
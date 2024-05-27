<?php

namespace V3\Action;

use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;
use App\Modules\Users\UsersModule;
use App\Modules\Users\UsersServiceModule;
use Think\Cache\Driver\Redis;
use V3\Model\ApiModel;
use V3\Model\AssembleModel;
use function App\Util\responseError;
use function App\Util\responseSuccess;
use App\Models\UsersModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器 app
 */
class IndexAction extends BaseAction
{

    /* api图标logo */
    public function niaocms_logo()
    {
        $m = D('V3/System');
        $logo = $m->loadConfigs();
        $data['logo'] = $logo['mallLogo'];
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn(returnData($data));
        }//返回方式处理
    }

    /* API 获取店铺广告列表 */
    public function banner()
    {
        $shopId = (int)I("shopId", 0);
        $mod = D("V3/Api");
        if (I("apiAll") == 1) {
            return $mod->getShopAds($shopId);
        } else {
            $this->ajaxReturn(returnData($mod->getShopAds($shopId)));
        }//返回方式处理
    }

    /* API 获取商城广告列表 自动将1 3级城市转为二级城市*/
    public function adminBanner()
    {
        $areaId = I("adcode");
        $mods = M("areas")->where("areaId = '{$areaId}'")->find();

        if (empty($mods['parentId'])) {
            $areaId = $mods['parentId'];
        } else {
            $areaId = $mods['areaId'];
        }

        $mod = D("V3/Api");
        //if(I("apiAll") == 1){return WSTAds($mods['parentId'],-1);}else{$this->ajaxReturn(WSTAds($mods['parentId'],-1));}//返回方式处理
        if (I("apiAll") == 1) {
            return $mod->getAds($areaId, -1);
        } else {
            $this->ajaxReturn($mod->getAds($areaId, -1));
        }//返回方式处理
    }

    /* api 根据经纬度获取定位 */
    public function lat_lng()
    {
        $lat = (float)I('lat');
        $lng = (float)I("lng");
        $num = (int)I("num");
        $coordtype = I("coordtype");

        $mod = D("V3/Api");
        $data = $mod->getlat_lng($lat, $lng, $num, $coordtype);
        //$xml = simplexml_load_string($data);

        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn(returnData($data));
        }//返回方式处理
    }

    /* api 获取每个分类下精品推荐的商品*/
    public function goodscat()
    {
        /* $areaId2 = I("areaid2");
        $gcm = D('V3/GoodsCats');
        $catList = $gcm->getGoodsCatsAndGoodsForIndex($areaId2);
        if(I("apiAll") == 1){return $catList;}else{$this->ajaxReturn($catList);}//返回方式处理 */
        $lat = I("lat");
        $lng = I("lng");
        $adcode = I("adcode");

        if (empty($lat) || empty($lng) || empty($adcode)) {
            // $res['apiCode'] = '-1';
            // $res['apiInfo'] = '参数有误';
            // $res['apiState'] = 'error';
            // $res['apiData'] = null;

            $res = returnData(null, -1, 'error', '参数有误');
            if (I("apiAll") == 1) {
                return $res;
            } else {
                $this->ajaxReturn($res);
            }//返回方式处理
        }

        $gcm = D("V3/Api");
        $data = $gcm->getGoodsCatsAndGoodsForIndex($lat, $lng, $adcode);
        $data = returnData($data);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /* api 获取附近店铺 为每个店铺添加当前位置与店铺的距离------暂且弃用 采用该接口：nearbyshopMap

    这个接口与nearbyshopMap有距离计算差距 后面可能会考虑移植到nearbyshopMap
    */
    public function nearbyshop()
    {

        $areas = D('V3/Api');
        $areaId3 = I('adcode');
        $user_lat = I('lat');
        $user_lng = I('lng');
        $page = I('page', 1);

        $areaList = $areas->getDistricts($areaId3, $page, $user_lat, $user_lng);
        for ($i = 0; $i <= count($areaList) - 1; $i++) {
            $z_lat_lng[$i] = $areas->getDistanceBetweenPointsNew($user_lat, $user_lng, $areaList[$i]['latitude'], $areaList[$i]['longitude']);
            $areaList[$i]['distance'] = sprintf("%.2f", $z_lat_lng[$i]['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($areaList as $user) {
            $shopsDataSort[] = $user['distance'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $areaList);//从低到高排序

        if (!empty($areaList)) {
            foreach ($areaList as $k => $v) {
                $areaList[$k]['communityName'] = $areas->getCommunity($v['communityId']);
            }
        }

        if (I("apiAll") == 1) {
            return $areaList;
        } else {
            $this->ajaxReturn(returnData($areaList));
        }//返回方式处理
    }

    /* api 获取在地图配送范围内的店铺*/
//    public function nearbyshopMap(){
//        $areas= D('V3/Api');
//        $areaId3 = I('adcode');
//        $user_lat = I('lat');
//        $user_lng = I('lng');
//
//        $areaList = $areas->getDistrictsMap($areaId3,$user_lat,$user_lng);
//        $areaList=returnData($areaList);
//        if(I("apiAll") == 1){return $areaList;}else{$this->ajaxReturn($areaList);}//返回方式处理
//    }

    /**
     * 获取附近店铺
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     * @param bool distance 距离排序【true:执行距离排序|false:无操作】
     * @param bool shopSale 店铺销量排序【true:执行店铺销量排序|false:无操作】
     * @param bool shopScore 店铺评分排序【true:执行店铺评分排序|false:无操作】
     * @param varchar shopTypeId 店铺类型Id,多个店铺类型id用英文逗号分隔
     * @param bool deliveryFreeMoney 减免运费【true:筛选减免运费的店铺|false:无操作】
     * @param int dataType 场景类型【1:前置仓|2:多商户】
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     * */
    public function nearbyshopMap()
    {
        $userId = (int)$this->getMemberInfo()['userId'];//只获取用户信息,不校验登陆状态
        $areas = D('V3/Api');
        $areaId3 = (int)I('adcode');
        $lat = (float)I('lat');
        $lng = (float)I('lng');
        $dataType = I('dataType', 1);
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 10);
//        if (empty($lat) || empty($lng) || empty($areaId3)) {
//            $res = returnData(null, -1, 'error', '参数有误');
//            if (I("apiAll") == 1) {
//                return $res;
//            } else {
//                $this->ajaxReturn($res);
//            }//返回方式处理
//        }
        $requestParams = I();
        $findWhere = [];//后加,用于做一些筛选,排序之类的功能
        $findWhere['distance'] = null;
        $findWhere['shopSale'] = null;
        $findWhere['shopScore'] = null;
        $findWhere['shopTypeId'] = null;
        $findWhere['deliveryFreeMoney'] = null;
        parm_filter($findWhere, $requestParams);
        $areaList = $areas->nearbyshopMap($userId, $areaId3, $lat, $lng, $page, $pageSize, $dataType, $findWhere);
        $areaList = returnData($areaList);
        if (I("apiAll") == 1) {
            return $areaList;
        } else {
            $this->ajaxReturn($areaList);
        }//返回方式处理
    }


    /* api 会员签到 --- APP --- */
    public function sign()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $mod = D("V3/Api");
        $datas = $mod->MemberSign($loginName);

        if (I("apiAll") == 1) {
            return $datas;
        } else {
            $this->ajaxReturn($datas);
        }//返回方式处理
    }

    /* api 会员签到 --- 小程序 --- 弃用 */
    public function xcxSign()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $mod = D("V3/Api");
        $datas = $mod->xcxMemberSign($loginName);
        $datas = returnData($datas);
        if (I("apiAll") == 1) {
            return $datas;
        } else {
            $this->ajaxReturn($datas);
        }//返回方式处理
    }

    /* API 根据Id获取店铺详细信息 */
    public function getShopIdInformation()
    {
        $shopId = (int)I("shopid");
        $lat = (int)I("lat");
        $lng = (int)I("lng");
        if (empty($shopId)) {
            if (I("apiAll") == 1) {
//                return array('code'=>-1,'msg'=>'有参数不能为空');
                return returnData(null, -1, 'error', '有参数不能为空');
            } else {
//                $this->ajaxReturn(array('code'=>-1,'msg'=>'有参数不能为空'));
                $this->ajaxReturn(returnData(null, -1, 'error', '有参数不能为空'));
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopIdInformation($shopId, $lat, $lng);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* API 根据店铺Id获取当前店铺所有商品 */
    public function getShopAllGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I("shopId");
        $page = I('page', 1);
        if (empty($shopId)) {
            if (I("apiAll") == 1) {
//                return array('code'=>-1,'msg'=>'有参数不能为空');
                return returnData(null, -1, 'error', '有参数不能为空');
            } else {
//                $this->ajaxReturn(array('code'=>-1,'msg'=>'有参数不能为空'));
                $this->ajaxReturn(returnData(null, -1, 'error', '有参数不能为空'));
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopGoods($userId, $shopId, $page);
        $mod = returnData((array)$mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* API 当前店铺所有分类 */
    public function getShoptypes()
    {
        $shopId = (int)I("shopid");
        if (empty($shopId)) {

            $returnData = returnData(null, -1, 'error', '有参数不能为空');

            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopIdType($shopId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /*api 获取店铺某个一级分类下的所有商品 */
    public function getShopTypeOneGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I("shopid");
        $shopCatId1 = (int)I("shopcatid1");
        if (empty($shopId)) {
            $returnData = returnData(null, -1, 'error', 'shopid为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }

        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 1000, 'intval');
        $m = D("V3/Api");
        $mod = $m->getShopTypeOneGoods($userId, $shopId, $shopCatId1, $page);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /*api 获取店铺某个二级分类下的所有商品 */
    public function getShopTypeTwoGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I("shopid");
        $shopCatId2 = (int)I("shopcatid2");
        if (empty($shopId)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }

        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 1000, 'intval');
        $m = D("V3/Api");
        $mod = $m->getShopTypeTwoGoods($userId, $shopId, $shopCatId2, $page);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /*api 商品Id获取商品详情 */
    public function getGoodDetails()
    {
        $userId = $this->getMemberInfo()['userId'];
        if (empty($userId)) {
            $userId = 0;
        }
        $goodsId = (int)I("goodsId");
        if (empty($goodsId)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
//        $m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->getGoodDetails($goodsId, $userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* api 根据商品id获取店铺信息 */
    public function getGoodsIdShopData()
    {
        $goodsId = (int)I("goodsid");
        if (empty($goodsId)) {
            if (I("apiAll") == 1) {
                return array("GoodsidIsEmpty");
            } else {
                $this->ajaxReturn(array("GoodsidIsEmpty"));
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getGoodsIdShopData($goodsId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* api获取商城分类一级二级 */
    public function getNiaoMallType()
    {
        $m = D("V3/Api");
        $mod = $m->getNiaoMallType();
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* api获取商城第三级分类 */
    public function getNiaoMallThreeType()
    {
        $catId2 = I('catId2', 0, 'intval');
        if (empty($catId2)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getNiaoMallThreeType($catId2);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* api 商品Id获取商品相册 */
    public function getGoodphotoAlbum()
    {
        $goodsId = (int)I("goodsid");
        if (empty($goodsId)) {
            if (I("apiAll") == 1) {
                return array("GoodsidIsEmpty");
            } else {
                $this->ajaxReturn(array("GoodsidIsEmpty"));
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getGoodphotoAlbum($goodsId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* api 获取商品评价 */
    public function getGoodEvaluate()
    {
        $goodsId = (int)I("goodsId");
        $page = (int)I("page", 1);
        $pageSize = (int)I("pageSize", 10);

        if (empty($goodsId) || empty($page)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getGoodEvaluate($goodsId, $page, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理

    }

    /* 商品评价-指定好中差获取 默认好评 */
    public function getGoodEvaluateDes()
    {
        $goodsId = (int)I("goodsId");
        $page = (int)I("page", 1);
        $compScore = (int)I("compScore", 2);
        $pageSize = (int)I("pageSize", 10);

        if (empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getGoodEvaluateDes($goodsId, $page, $compScore, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理

    }

    /* api 获取商品描述(商品介绍) */
    public function getGoodIntroduce()
    {
        $goodsId = (int)I("goodsId");
        if (empty($goodsId)) {
            if (I("apiAll") == 1) {
                return array("GoodsidIsEmpty");
            } else {
                $this->ajaxReturn(array("GoodsidIsEmpty"));
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getGoodIntroduce($goodsId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* 关注商品*/
    public function goodFollow()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $targetId = I("targetId");

        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->goodFollow($loginName, $targetId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* 取消关注商品 */
    public function goodnoFollow()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $targetId = I('targetId');

        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->goodnoFollow($loginName, $targetId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }


    /* 关注和取消关注店铺 */
    public function shopFollow()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $targetId = I('targetId');

        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->shopFollow($loginName, $targetId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* 取消关注店铺 */
    public function shopnoFollow()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $targetId = I("targetId");

        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->shopnoFollow($loginName, $targetId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* 用户注册 */
    public function userReg()
    {
        $mobileNumber = I("mobileNumber");
        $code = I("code");
        $loginPwd = I("loginPwd");
        if (empty($mobileNumber)) {
            $type = returnData(null, -1, 'error', '账号不能为空');
            if (I("apiAll") == 1) {
                return $type;
            } else {
                $this->ajaxReturn($type);
            }//返回方式处理
        }
        $userEm = M("users")->where("loginName = '{$mobileNumber}'")->find();
        if (!empty($userEm)) {
            $type = array();
//            $type["statusCode"] = "000027";
            $type = returnData(null, -1, 'error', '用户已经存在');
            if (I("apiAll") == 1) {
                return $type;
            } else {
                $this->ajaxReturn($type);
            }//返回方式处理
        }

        if ($code != S("app_reg_mobileNumber_{$mobileNumber}")) {
//            $data['statusCode'] = "000022";
            $data = returnData(null, -1, 'error', '验证码错误 或者已过期');
            if (I("apiAll") == 1) {
                return $data;
            } else {
                $this->ajaxReturn($data);
            }//返回方式处理
        }


        $mod = M("users");
        $data["loginSecret"] = rand(1000, 9999);
        $data['loginPwd'] = md5($loginPwd . $data['loginSecret']);
        $data['userType'] = 0;
        $data['loginName'] = $mobileNumber;
        $data['userPhone'] = $mobileNumber;
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['userFlag'] = 1;
        $add_is_ok_id = $mod->add($data);

        if (!empty($add_is_ok_id)) {
            $userInfo = M('users')->where(array('userPhone' => $mobileNumber, 'userFlag' => 1))->find();
            //注册成功发送推送信息
            $push = D('Adminapi/Push');
            $push->postMessage(4, $userInfo['userId']);
            //判断是否是被邀请
            $Invitation = I('InvitationID', 0);//原始邀请人的userId
            if (!empty($Invitation)) {
                //有邀请人的状态
                self::InvitationFriend($Invitation, $add_is_ok_id);
            } else {
                $inviteInfo = M('invite_cache_record')->where(array('inviteePhone' => $mobileNumber, 'icrFlag' => 1))->find();
                if (!empty($inviteInfo)) self::InvitationFriend($inviteInfo['inviterId'], $add_is_ok_id);
            }

            //新人专享大礼
            $isNewPeopleGift = self::FunNewPeopleGift($add_is_ok_id);

            self::distributionRelation($data['userPhone'], $add_is_ok_id);//写入用户分销关系表
//            self::InvitationFriendSetmeal($add_is_ok_id, $Invitation); //邀请好友开通会员送券

            //判断新人专享获得的积分是否为空
            //if(!empty($isNewPeopleGift)){
//            $data1['isNewPeopleGift'] = $isNewPeopleGift;
            //}

            S("app_reg_mobileNumber_{$mobileNumber}", null);
            $data1['statusCode'] = "000023";
            if (I("apiAll") == 1) {
                return $data1;
            } else {
                $this->ajaxReturn($data1);
            }//返回方式处理
        } else {
            $data1['statusCode'] = "000024";
            if (I("apiAll") == 1) {
                return $data1;
            } else {
                $this->ajaxReturn($data1);
            }//返回方式处理
        }

    }

    //获取验证码 存在缓存里
    public function getVerificationCode()
    {
        $mobileNumber = WSTAddslashes(I("mobileNumber"));
        $code = mt_rand(100000, 999999);
        $rs = array();
        if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $mobileNumber)) {
            $rs['statusCode'] = '000018';
            $rs = returnData(null, -1, 'error', '手机号码格式错误');
            if (I("apiAll") == 1) {
                return $rs;
            } else {
                $this->ajaxReturn($rs);
            }//返回方式处理
        }

        //验证手机号是否已经注册 该逻辑不适用此处 此处逻辑有更广使用范围
        /* 		$where["loginName"] = $mobileNumber;
                $where["userFlag"] = 1;
                $userPhone = M("users")->where($where)->count();
                if($userPhone != 0){
                    $data['statusCode'] = "000021";
                    if(I("apiAll") == 1){return $data;}else{$this->ajaxReturn($data);}//返回方式处理
                } */

        S("app_reg_mobileNumber_{$mobileNumber}", $code, 1200);
        $m = D("V3/Api");

        //网建
        $send_sms_res = (int)WSTSendSMS($mobileNumber, "验证码：{$code}");
        if ($send_sms_res > 0) {
            //记录到短信日志表-start
            M('log_sms')->add(
                array(
                    'smsSrc' => 0,
                    'smsUserId' => 0,
                    'smsContent' => "验证码：{$code}",
                    'smsPhoneNumber' => $mobileNumber,
                    'smsReturnCode' => $send_sms_res,
                    'smsFunc' => 'getVerificationCode',
                    'createTime' => date('Y-m-d H:i:s'),
                    'smsCode' => $code,
                    'smsIP' => get_client_ip(),
                )
            );
            //记录到短信日志表-emd

            $data['statusCode'] = "000019";
            $data = returnData('短信发送成功');
            if (I("apiAll") == 1) {
                return $data;
            } else {
                $this->ajaxReturn($data);
            }//返回方式处理
        }

        /*
        //阿里云发送
        if($m->SmsReg($mobileNumber,$code)){
            $data['statusCode'] = "000019";
            if(I("apiAll") == 1){return $data;}else{$this->ajaxReturn($data);}//返回方式处理
        } */
        $data['statusCode'] = "000020";
        $data = returnData(null, -1, 'error', '短信发送失败');

        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /*******
     *邀请好友 好友必须下单才有优惠券 先领取优惠券 状态dataFlag -1 在用户确认收货的时候进行处理 还有自动收货的时候
     *后期可优化使用队列进行处理
     ******/
    static function InvitationFriend($Invitation, $userId)
    {
        //送优惠券
        $mod_users = M('users');
        $mod_user_Invitation = M('user_invitation');
        //更新历史邀请人数 +1
        $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum', 1);
        //自动领取优惠券
        $where['dataFlag'] = 1;
        $where['couponType'] = 3;
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();

        $m = D("V3/Api");

        for ($i = 0; $i < count($data); $i++) {
            $m->okCoupons($Invitation, $data[$i]['couponId'], 3, -1, $userId);
        }

        //写入邀请关系
        $add_data['userId'] = (int)$Invitation;
        $add_data['source'] = 2;//app邀请好友获得
        $add_data['UserToId'] = (int)$userId;
        $add_data['reward'] = count($data);//优惠券数量
        $add_data['createTime'] = date("Y-m-d H:i:s");
        //获取邀请者给被邀请者的奖励次数
        $inviteRewardNum = (int)$GLOBALS["CONFIG"]['inviteNumReward'];
        $inviteNumRules = $GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
        $add_data['inviteRewardNum'] = $inviteRewardNum;
        //1.优惠券||2.返现||3.积分---------由于之前已经有一次邀请好友就赠送优惠券，所以要将获取的配置次数减一
        if ($inviteNumRules == 1 && $inviteRewardNum > 0) {
            $add_data['inviteRewardNum'] = intval($inviteRewardNum - 1);
        }
        $mod_user_Invitation->add($add_data);

        //可以对$Invitation 里的userId进行判断 是否存在 不是很重要 这边暂不做

        /*
        $mod_users = M('users');
        $mod_user_Invitation = M('user_invitation');
        $mod_user_score = M('user_score');
        //更新历史邀请人数 +1
        $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum',1);

        //随机奖励积分
        $num = explode("-",$GLOBALS["CONFIG"]['InvitationRange']);
        $Integral = rand($num[0],$num[1]);
        $mod_users->where("userId = '{$Invitation}'")->setInc('userScore',(int)$Integral);
        //写入邀请关系
        $add_data['userId'] = (int)$Invitation;
        $add_data['source'] = 2;//app邀请好友获得
        $add_data['UserToId'] = (int)$userId;
        $add_data['reward'] = (int)$Integral;
        $add_data['createTime'] = date("Y-m-d H:i:s");
        $mod_user_Invitation->add($add_data);

        //更新历史邀请积分
        $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoInte',(int)$Integral);//数据库字段请默认为0

        //写入用户积分流水
        unset($add_data);
        $add_data['userId'] = $Invitation;
        $add_data['score'] = $Integral;
        $add_data['dataSrc'] = 9;//app邀请好友获得
        $add_data['dataId'] = 0;
        $add_data['dataRemarks'] = "app邀请好友获得";
        $add_data['scoreType'] = 1;
        $add_data['createTime'] = date("Y-m-d H:i:s");
        $mod_user_score->add($add_data); */

    }

    /*******
     *邀请好友开通会员 好友必须开通会员才有优惠券 先领取优惠券 状态dataFlag -1 在用户开通会员成功的时候进行处理
     * @param int userId PS：注册用户id
     * @param int InvitationID PS：邀请人id
     ******/
    static function InvitationFriendSetmeal($userId, $Invitation = 0)
    {
        $userInfo = M('users')->where(['userId' => $userId])->field('userId,userPhone')->find();
        if ($Invitation > 0) {
            //后加
            //通过分享链接
            //自动领取优惠券
            $where['dataFlag'] = 1;
            $where['couponType'] = 6; //邀请开通会员
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->select();
            $m = D("Weimendian/Api");
            for ($i = 0; $i < count($data); $i++) {
                $m->okCoupons($Invitation, $data[$i]['couponId'], 6, -1, $userId);
            }

            $tab = M('setmeal_invitation');
            $insert['userId'] = $Invitation;
            $insert['userPhone'] = $userInfo['userPhone'];
            $insert['addTime'] = date('Y-m-d H:i:s', time());
            $tab->add($insert);
        } else {
            //相当于面对面分享
            $setmealInvitation = M('setmeal_invitation')->where(['userPhone' => $userInfo['userPhone']])->find();
            if ($setmealInvitation) {
                $Invitation = $setmealInvitation['userId'];
                //送优惠券
                $mod_users = M('users');
                //更新历史邀请人数 +1
                $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum', 1);//方便其他地方引用为了不影响过多逻辑暂不判断是否成功
                //自动领取优惠券
                $where['dataFlag'] = 1;
                $where['couponType'] = 6; //邀请开通会员
                $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
                $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
                $data = M('coupons')->where($where)->order('createTime desc')->select();
                $m = D("Weimendian/Api");
                for ($i = 0; $i < count($data); $i++) {
                    $m->okCoupons($Invitation, $data[$i]['couponId'], 6, -1, $userId);
                }
            }
        }
    }


    /*******
     *新人专享大礼
     ******/
    static function FunNewPeopleGift($userId)
    {
        $m = D("V3/Api");
        //新人奖励运费券 
        $freightCouponsNum = $GLOBALS["CONFIG"]['freightCoupons'];
        if (!empty($freightCouponsNum)) {
            $freightCouponsNum = (int)$freightCouponsNum;
            $where['dataFlag'] = 1;
            $where['couponType'] = 8;
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->find();
            if (!empty($data)) {
                for ($i = 0; $i < $freightCouponsNum; $i++) {
                    $c = $m->okCoupons($userId, $data['couponId'], 8);  //运费券8
                }
            }
        }
        //获取新人优惠券
        $where = array();
        $where['dataFlag'] = 1;
        $where['couponType'] = 2;
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();
        // get_couponsList_auth($data);
        for ($i = 0; $i < count($data); $i++) {
            $m->okCoupons($userId, $data[$i]['couponId'], 2);//新人专享2
        }
        return $data;

        /*	$mod_users = M('users');
             $mod_user_score = M('user_score');

            //随机奖励积分
            $num = explode("-",$GLOBALS["CONFIG"]['newPeopleGift']);
            $Integral = rand($num[0],$num[1]);
            $mod_users->where("userId='{$userId}'")->setInc('userScore',$Integral);

            //写入用户积分流水
            unset($add_data);
            $add_data['userId'] = $userId;
            $add_data['score'] = $Integral;
            $add_data['dataSrc'] = 11;//app新人专享大礼
            $add_data['dataId'] = 0;
            $add_data['dataRemarks'] = "app新人专享大礼获得";
            $add_data['scoreType'] = 1;
            $add_data['createTime'] = date("Y-m-d H:i:s");
            $mod_user_score->add($add_data);

            return $Integral; */
    }

    static function wxLogin($funData)
    {
        $weiResData['unionid'] = $funData['unionid'];
        $weiResData['openid'] = $funData['openid'];

        $userName = $funData['nickname'];
        $userPhoto = $funData['headimgurl'];
        $user_Phone = $funData['mobileNumber'];//用户手机号
        $user_loginPwd = $funData['loginPwd'];//用户密码 未加密
        $user_smsCode = $funData['smsCode'];//短信验证码
//         if(empty($userName) || empty($userPhoto)){
// //            $apiRet['apiCode']=-1;
// //            $apiRet['apiInfo']='字段有误';
// //            $apiRet['apiState']='error';
//             $apiRet = returnData($userPostData,-1,'error','字段有误');
// //            $apiRet['apiData']=$userPostData;
//             return $apiRet;
//         }

        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理

        $modUsers = M('users');

        if (empty($weiResData['unionid'])) {
            $apiRet = array();
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='unionid为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'unionid为空');
            return $apiRet;
        }
        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理

        //检查此微信 是否绑定手机
        $modUsersIsEmpty = $modUsers->where("WxUnionid='{$weiResData['unionid']}' and loginName !=''")->find();

        //如果为空 即为注册
        if (empty($modUsersIsEmpty)) {

            //判断参数 是否齐全
            if (empty($user_Phone) || empty($user_loginPwd) || empty($user_smsCode)) {
//                $apiRet['apiCode']='000080';
//                $apiRet['apiInfo']='请绑定手机号或绑定手机号时参数携带错误';
//                $apiRet['apiState']='error';
                $apiRet = returnData(null, '000080', 'error', '请绑定手机号', '请绑定手机号');
                return $apiRet;
            }

            //校验短信验证码
            if ($user_smsCode != S("app_reg_mobileNumber_{$user_Phone}")) {
//                $apiRet['apiCode']='000082';
//                $apiRet['apiInfo']='验证码错误！';
//                $apiRet['apiState']='error';
                $apiRet = returnData(null, -1, 'error', '验证码错误！');
                return $apiRet;
            } else {
                S("app_reg_mobileNumber_{$user_Phone}", null);
            }

            //判断当前手机号是否已被注册 如果已被注册 则进行直接绑定微信
            $userPhoneIsEmpty = $modUsers->where("loginName='{$user_Phone}'")->find();
            if (!empty($userPhoneIsEmpty)) {
                unset($data);
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['openId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;
                $modUsers->where("loginName='{$user_Phone}'")->save($data);
                /* 	$apiRet['apiCode']='000081';
                    $apiRet['apiInfo']='手机号已被绑定，请使用手机号在 pc端或者app进行登录';
                    $apiRet['apiState']='error';
                    if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理 */
            } else {
                unset($data);
                $data['loginName'] = $user_Phone;
                $data['loginSecret'] = rand(1000, 9999);
                $data['loginPwd'] = md5($user_loginPwd . $data['loginSecret']);
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['createTime'] = date('Y-m-d H:i:s');
                $data['openId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;

                $add_is_ok_id = $modUsers->add($data);

                //判断是否是被邀请
                $Invitation = I('InvitationID');//原始邀请人的userId
                if (!empty($Invitation)) {
                    self::InvitationFriend($Invitation, $add_is_ok_id);
                } else {
                    $inviteInfo = M('invite_cache_record')->where(array('inviteePhone' => $user_Phone, 'icrFlag' => 1))->find();
                    if (!empty($inviteInfo)) self::InvitationFriend($inviteInfo['inviterId'], $add_is_ok_id);
                }

                //新人专享大礼
                $isNewPeopleGift = self::FunNewPeopleGift($add_is_ok_id);
            }
        }

        //登陆生成token

        $where['WxUnionid'] = $weiResData['unionid'];
        $where['userFlag'] = 1;
        $modUsersData = $modUsers->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'openId', 'userPhone'))->find();//再次获取用户所有字段

        //判断新人专享获得的礼包是否为空
        if (!empty($isNewPeopleGift)) {
            $modUsersData['isNewPeopleGift'] = $isNewPeopleGift;
        }

        if (empty($modUsersData)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='用户被禁用，或者不存在';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '用户被禁用，或者不存在');
            return $apiRet;
        }
        //记录登录日志
        $User = M("log_user_logins");
        $data = array();
        $data["userId"] = $modUsersData['userId'];
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $data["loginSrc"] = 3;
        $User->add($data);

        $logdata['lastIP'] = get_client_ip();
        $logdata['lastTime'] = date('Y-m-d H:i:s');
        $modUsers->where("userId = '{$modUsersData['userId']}'")->save($logdata);

        //生成用唯一token
        $memberToken = md5(uniqid('', true) . $code . $modUsersData['userId'] . $modUsersData['loginName'] . (string)microtime());
        if (!userTokenAdd($memberToken, $modUsersData)) {
//            $apiRes['apiCode'] = -1;
//            $apiRes['apiInfo'] = '登陆失败';
//            $apiRes['apiState'] = 'error';
//            $apiRes['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '登陆失败');
            return $apiRet;
        }

        $modUsersData['memberToken'] = $memberToken;
        // $apiRes['apiCode'] = '111111';
        // $apiRes['apiInfo'] = '登陆成功';
        // $apiRes['apiState'] = 'success';
        // $apiRes['apiData'] = $modUsersData;
        $apiRes = returnData($modUsersData, 0, 'success', '登陆成功');
        return $apiRes;
    }

    /* 用户登陆 */
//    public function userLogin()
//    {
//        $mobileNumber = I("mobileNumber");
//        $loginPwd = I("loginPwd");
//
//
//        $get_unionid = I('unionid');
//        $get_headimgurl = I('headimgurl');
//        $get_openid = I('openid');
//        $get_nickname = I('nickname');
//        $get_smsCode = I('smsCode');
//
//        //判断是否是微信登陆
//        if (!empty($get_unionid)) {
//            $funData['unionid'] = $get_unionid;
//            $funData['headimgurl'] = $get_headimgurl;
//            $funData['openid'] = $get_openid;
//            $funData['nickname'] = $get_nickname;
//            $funData['smsCode'] = $get_smsCode;
//            $funData['mobileNumber'] = $mobileNumber;
//            $funData['loginPwd'] = $loginPwd;
//            if (I("apiAll") == 1) {
//                return self::wxLogin($funData);
//            } else {
//                $this->ajaxReturn(self::wxLogin($funData));
//            }//返回方式处理
//        }
//
//        $users = M("users");
//        $loginSecret = $users->where("userPhone = '{$mobileNumber}'")->field(array("loginSecret"))->find();//获取安全码
//        $where['userPhone'] = $mobileNumber;
//        $where['userFlag'] = 1;
//        $where['loginPwd'] = md5($loginPwd . $loginSecret['loginSecret']);
//        $mod = $users->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'userPhone'))->find();
//        if (empty($mod)) {
////            $data["apiCode"] = "000025";
////            $data["apiState"] = "error";
////            $data["apiInfo"] = "账号或者密码错误哦";
//            $data = returnData(null, -1, 'error', '账号或者密码错误哦');
//            if (I("apiAll") == 1) {
//                return $data;
//            } else {
//                $this->ajaxReturn($data);
//            }//返回方式处理
//        }
//        if (empty($mod["userPhoto"])) {//用户头像为空 获取默认头像
//            $mod["userPhoto"] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
//
//        }
//        //记录登录日志
//        $User = M("log_user_logins");
//        $data = array();
//        $data["userId"] = $mod['userId'];
//        $data["loginTime"] = date('Y-m-d H:i:s');
//        $data["loginIp"] = get_client_ip();
//        $data["loginSrc"] = 2;
//        $User->add($data);
//
//        $logdata['lastIP'] = get_client_ip();
//        $logdata['lastTime'] = date('Y-m-d H:i:s');
//        $users->where("userId = '{$mod['userId']}'")->save($logdata);
//
//        //生成用唯一token
//        $memberToken = md5(uniqid('', true) . $mobileNumber . $loginPwd . $loginSecret . (string)microtime());
//
//        //session(array('name'=>$memberToken,'expire'=>86400*30));
//        //session($memberToken,$mod);
//        //S($memberToken,$mod,86400*30);
//        if (!userTokenAdd($memberToken, $mod)) {
////            $apiRes['apiCode'] = -1;
////            $apiRes['apiInfo'] = '登陆失败';
////            $apiRes['apiState'] = 'error';
////            $apiRes['apiData'] = null;
//            $apiRes = returnData(null, -1, 'error', '登陆失败');
//            if (I("apiAll") == 1) {
//                return $apiRes;
//            } else {
//                $this->ajaxReturn($apiRes);
//            }//返回方式处理
//        }
//
//        $mod['memberToken'] = $memberToken;
//
////        $apiRes['apiCode'] = '111111';
////        $apiRes['apiInfo'] = '登陆成功';
////        $apiRes['apiState'] = 'success';
////        $apiRes['apiData'] = $mod;
//        $apiRes = returnData($mod);
//        if (I("apiAll") == 1) {
//            return $apiRes;
//        } else {
//            $this->ajaxReturn($apiRes);
//        }//返回方式处理
//    }

    /**
     * 用户账号密码登陆
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/xxs80c
     * */
    public function userLogin()
    {
        $params = array(
            'mobileNumber' => I("mobileNumber"),//手机号
            'loginPwd' => I("loginPwd"),//密码
            'unionid' => I("unionid"),//微信用户唯一id
            'headimgurl' => I("headimgurl"),//用户微信头像
            'openid' => I("openid"),//用户openid
            'nickname' => I("nickname"),//昵称
            'smsCode' => I("smsCode"),//验证码
            'InvitationID' => (int)I("InvitationID"),//邀请人id
        );
        $model = new ApiModel();
        $result = $model->userLogin($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError($result['code'], $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }


    /**
     * 登陆-账号密码登陆
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/aik5ri
     * */
    public function passwordLogin()
    {
        $account = I('account');
        $password = I('password');
        if (empty($account)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入账号'));
        }
        if (empty($password)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入密码'));
        }
        $model = new ApiModel();
        $result = $model->passwordLogin($account, $password);
        $this->ajaxReturn($result);
    }

    /**
     * 添加购物车/加入购物车
     * @param int goodsId 商品id
     * @param float goodsCnt 购买数量
     * @param int goodsAttrId 属性id（已废弃）
     * @param int skuId 商品skuId
     * @param int type 场景【1：普通购买|2：再来一单】
     * */
    public function addToCart()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $goodsId = (int)I("goodsId");
        $goodsCnt = (float)I("goodsCnt", 1);
        $skuId = (int)I("skuId");//后加skuId
        $type = (int)I('type', 1);//(1:用于普通流程|2:用于再来一单)
        if (empty($goodsId)) {
            $apiRet = returnData(false, ExceptionCodeEnum::FAIL, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if ($goodsCnt <= 0) {
            $apiRet = returnData(false, ExceptionCodeEnum::FAIL, 'error', '购买数量或重量必须大于0');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = new ApiModel();
        $mod = $m->addToCart($userId, $goodsId, $goodsCnt, $skuId, $type);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /* 增加购物车商品数量 */
    /*public function plusCartGoodsnum(){
        $loginName =$this->MemberVeri()['loginName'];//身份认证
        $goodsId = I("goodsId");
        $goodsCnt = I("goodsCnt");
        $skuId = I("skuId",0);//后加skuId
        if(empty($goodsId)||empty($goodsCnt)){
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null,-1,'error','字段有误');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->plusCartGoodsnum($loginName,$goodsId,$goodsCnt,$skuId);
        $mod=returnData($mod);
        if(I("apiAll") == 1){return $mod;}else{$this->ajaxReturn($mod);}//返回方式处理

    }*/

    /**
     *增加购物车商品数量
     * @param string memberToken
     * @param int cartId PS:购物车id
     * @param float goodsCnt PS:数量/重量
     * */
    public function plusCartGoodsnum()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $cartId = I("cartId");
        $goodsCnt = I("goodsCnt", 1);
        if (empty($cartId) || empty($goodsCnt)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
//        $m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->plusCartGoodsnum($userId, $cartId, (float)$goodsCnt);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }
    }


    /* 减去购物车商品数量 */
//    public function reduceCartGoodsnum(){
//        $loginName =$this->MemberVeri()['loginName'];//身份认证
//        $goodsId = I("goodsId");//cartId
//        $goodsCnt = I("goodsCnt");
//        $skuId = I("skuId",0); //后加skuId
//        if(empty($goodsId)||empty($goodsCnt)){
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null,-1,'error','字段有误');
//            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
//        }
//        $m = D("V3/Api");
//        $mod = $m->reduceCartGoodsnum($loginName,$goodsId,$goodsCnt,$skuId);
//        $mod = returnData($mod);
//        if(I("apiAll") == 1){return $mod;}else{$this->ajaxReturn($mod);}//返回方式处理
//    }

    /**
     *减去购物车商品数量
     * @param string memberToken
     * @param int cartId PS:购物车id
     * @param float goodsCnt PS:数量
     * */
    public function reduceCartGoodsnum()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $cartId = I("cartId");
        $goodsCnt = I("goodsCnt", 1);
        if (empty($cartId) || empty($goodsCnt)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }
//        $m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->reduceCartGoodsnum($userId, $cartId, (float)$goodsCnt);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }
    }

    /**
     * 获取购物车商品
     * @param string memberToken
     * @param int shopId
     * @param int dataType 模式(1:前置仓,2:多商户)
     * */
    public function getCartInfo()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $shopId = (int)I('shopId', 0);
        //$m = D("V3/Api");
        $m = new ApiModel();
        $param = [];
        $param['userId'] = $userId;
        $param['shopId'] = $shopId;
        $param['dataType'] = I('dataType', 1);
        if (!empty(I('dataFormat'))) {
            $param['dataType'] = I('dataFormat');
        }
        if ($param['dataType'] == 1 && empty($shopId)) {//前置仓模式需要传shopId用来失效非当前店铺商品
            $this->ajaxReturn(returnData(null, -1, 'error', '字段有误'));
        }
        $mod = $m->getCartInfo($param);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 删除购物车中的商品
     * @param string memberToken
     * @param int cartId PS:购物车id
     * */
    public function delCartGoods()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $cartId = I("cartId");//cartId
        if (empty($cartId)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }
        $m = D("V3/Api");
        $mod = $m->delCartGoods($loginName, $cartId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }
    }

    //获取用户默认收货地址
    public function address()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $mod = $m->address($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取用户所有地址
    public function getAllAddress()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $param = [];
        $param['shopId'] = I('shopId');
        $param['userId'] = (int)$userId;
        $mod = $m->getAllAddress($param);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //联动查询数据
    public function cityQuery()
    {
        $m = D("V3/Api");
        $parentId = I("parentId");
        $mod = $m->cityQuery($parentId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据第三级城市查询社区
    public function getAreaIdCommunityName()
    {
        $m = D("V3/Api");
        $areaId3 = I("areaId3");
        $mod = $m->getAreaIdCommunityName($areaId3);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //更新用户收货地址
//    public function updataAddress()
//    {
//        $loginName = $this->MemberVeri()['loginName'];//身份认证
//        $m = D("V3/Api");
//        //$loginName = I("loginName");
//
//        $addressId = I("addressId");
//        $userName = I("userName");
//        $userPhone = I("userPhone");
//        $areaId1 = I("areaId1");
//        $areaId2 = I("areaId2");
//        $areaId3 = I("areaId3");
//        $communityId = I("communityId");
//        $address = I("address");
//        $isDefault = I("isDefault");
//        $lat = I("lat");
//        $lng = I("lng");
//        $setaddress = (string)I("setaddress");
//        if (empty($lat) || empty($lng)) {
////            return array('code'=>-1,'msg'=>'经纬度不能为空');
//            return returnData(null, -1, 'error', '经纬度不能为空');
//        }
//        $mod = $m->updataAddress($loginName, $addressId, $userName, $userPhone, $areaId1, $areaId2, $areaId3, $communityId, $address, $isDefault, $lat, $lng, $setaddress);
//        $mod = returnData($mod);
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }

    /**
     * 更新用户收货地址 注:重写上面注释的方法,艹
     * */
    public function updataAddress()
    {
        $userId = $this->MemberVeri()['userId'];
        $params = array(
            'userId' => (int)$userId,
            'addressId' => (int)I('addressId'),
            'userName' => I("userName"),
            'userPhone' => I("userPhone"),
            'areaId1' => (int)I("areaId1"),
            'areaId2' => (int)I("areaId2"),
            'areaId3' => (int)I("areaId3"),
            'communityId' => (int)I("communityId"),
            'address' => I("address"),
            'isDefault' => (int)I("isDefault"),
            'lat' => (float)I("lat"),
            'lng' => (float)I("lng"),
            'setaddress' => (string)I("setaddress"),
        );
        if (empty($params['addressId']) || empty($params['lat']) || empty($params['lng']) || empty($params['areaId1']) || empty($params['areaId2']) || empty($params['areaId3'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }

        if (!preg_match(SmsEnum::MOBILE_FORMAT, $params['userPhone'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机号格式不正确'));
        }
        $mod = new ApiModel();
        $result = $mod->updataAddress($params);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }
    }

    //获取某个用户收货地址
    public function getOneAddress()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");

        $addressId = I("addressId");
        if (empty($addressId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->getOneAddress($loginName, $addressId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //删除用户某个收货地址
    public function delUserAddress()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");

        $addressId = I("addressId");
        if (empty($addressId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->delUserAddress($loginName, $addressId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //添加地址
    public function addUserAddress()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $userName = I("userName");
        $userPhone = I("userPhone");
        $areaId1 = I("areaId1");
        $areaId2 = I("areaId2");
        $areaId3 = I("areaId3");
        $communityId = I("communityId");
        $address = I("address");
        $isDefault = I("isDefault");
        $lat = I("lat");
        $lng = I("lng");
        $setaddress = I("setaddress");
        if (empty($lat) || empty($lng) || empty($areaId1) || empty($areaId2) || empty($areaId3)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '经纬度不能为空 城市areaid代码不能为空'));
        }
        if (!preg_match(SmsEnum::MOBILE_FORMAT, $userPhone)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '手机号格式不正确'));
        }
        $mod = $m->addUserAddress($loginName, $userName, $userPhone, $areaId1, $areaId2, $areaId3, $communityId, $address, $isDefault, $lat, $lng, $setaddress);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取用户头像和昵称
    public function getUserNameImg()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");

        $mod = $m->getUserNameImg($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取用户资料
    public function getUserInfor()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $mod = $m->getUserInfor($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //更新用户昵称
    public function saveUserName()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");

        $UserName = I("userName");
        if (empty($UserName)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->saveUserName($loginName, $UserName);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //更新真实姓名
    public function saveRealName()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");

        $realName = I("realName");
        if (empty($realName)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->saveRealName($loginName, $realName);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //更新用户性别
    public function saveUserSex()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $UserSex = I("userSex");
        if (empty($UserSex)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->saveUserSex($loginName, $UserSex);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //更新用户QQ
    public function saveUserQQ()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $userQQ = I("userQQ");
        if (empty($userQQ)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->saveUserQQ($loginName, $userQQ);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 更新用户手机号
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/nrp5cf
     * @param varchar memberToken
     * @param varchar userPhone 用户手机号
     * @param varchar verificationCode 短信验证码
     * */
    public function saveUserPhone()
    {
        $login_user_info = $this->MemberVeri();
        $userId = (int)$login_user_info['userId'];
        $userPhone = (string)I("userPhone");
        $smsCode = (string)I("verificationCode");
        if (empty($userPhone) || empty($smsCode)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $m = D("V3/Api");
        $result = $m->saveUserPhone($userId, $userPhone, $smsCode);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess(true));
    }

    //更新用户邮箱
    public function saveUserEmail()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $userEmail = I('userEmail');
        if (empty($userEmail)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->saveUserEmail($loginName, $userEmail);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商品收藏
    public function getFavoritesGoods()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $m = D("V3/Api");
        $mod = $m->getFavoritesGoods($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取店铺收藏
    public function getFavoritesShops()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $mod = $m->getFavoritesShops($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-待付款
    public function pendingPayment()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
//        $m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->pendingPayment($loginName);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-取消订单
    public function cancelOrder()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        //$m = D("V3/Api");
        $m = new ApiModel();
        $orderId = I("orderId");
        if (empty($orderId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->cancelOrder($loginName, $orderId);
        //$mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-取消订单  只适用于已付款订单取消
    public function cancelOrderOK()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $orderId = I("orderId");
        if (empty($orderId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->cancelOrderOK($userId, $orderId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-待发货
    public function toBeShipped()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $mod = $m->toBeShipped($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-待收货
    public function waitforGoodGreceipt()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $mod = $m->waitforGoodGreceipt($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-确认收货
    public function ConfirmReceipt()
    {
//        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $userId = $this->MemberVeri()['userId'];
        $orderId = I("orderId");//订单id
        $type = I("type");//提交类型 1确认收货 -1拒收
        $rejectionRemarks = I("rejectionRemarks");//拒收原因
        if (empty($orderId) || empty($type)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if ($type == -1 and empty($rejectionRemarks)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='拒绝理由不得为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '拒绝理由不得为空');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

//        $mod = ConfirmReceipt($loginName, $orderId, $type, $rejectionRemarks);
        $mod = new ApiModel();
        $result = $mod->confirmReceipt($userId, $orderId, $type, $rejectionRemarks);
//        $mod = returnData($mod);
        if (I("apiAll") == 1) {
//            return $mod;
            return $result;
        } else {
//            $this->ajaxReturn($mod);
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    //订单-待评价
    public function waitforEvaluate()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证

        $m = D("V3/Api");
        $mod = $m->waitforEvaluate($loginName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单—去评价
    public function goEvaluate()
    {

    }

    //结算页

    //核对订单


    //提交订单--货到付款 原来的
    public function SubmitOrder_t()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        //$loginName = '15395136032';
        $m = D("V3/Api");
        $addressId = I("addressId");
        //$goodsId = I("goodsId");
        $goodsId = getCartGoodsChecked($this->MemberVeri()['userId'])['goodsId'];
        //$goodsSku = json_decode($_POST['goodsSku'],true);//后加商品sku,例子[{"goodsId":84,"skuId":5},{"goodsId":84,"skuId":0}]
        $goodsSku = getCartGoodsChecked($this->MemberVeri()['userId'])['goodsSku'];
        $orderRemarks = I('orderRemarks');
        $requireTime = I('requireTime');
        $couponId = I('couponId');
        $isSelf = 0;
        $getuseScore = (int)I('getuseScore', 0);//使用积分
        $cuid = I('cuid');//wst_coupons_users 表中的 id

        $lng = I('lng');//先依赖前端获取地址经纬度传输过来
        $lat = I('lat');//后期可依赖查询地址id获取
        if (empty($addressId) || empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        //$mod = $m->SubmitOrder($loginName,$addressId,$goodsId,$orderRemarks,$requireTime,$couponId,$isSelf,$getuseScore,$lng,$lat,$cuid);
        $mod = $m->SubmitOrderSku($loginName, $addressId, $goodsId, $orderRemarks, $requireTime, $couponId, $isSelf, $getuseScore, $lng, $lat, $cuid, $goodsSku);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 提交订单--货到付款
     * @return mixed
     */
    public function SubmitOrder()
    {
        $loginName = $this->MemberVeri()['loginName'];//身份认证
        $m = D("V3/Api");
        $addressId = I("addressId");
        $goodsId = I("goodsId");
        $orderRemarks = I('orderRemarks');
        $requireTime = I('requireTime');
        $couponId = I('couponId');
        $isSelf = I('isSelf', 0);
        $getuseScore = (int)I('getuseScore', 0);//使用积分

        $lng = I('lng');//先依赖前端获取地址经纬度传输过来
        $lat = I('lat');//后期可依赖查询地址id获取

//        $payFrom = I('payFrom',0,'intval');//支付来源 0:现金 1:支付宝，2：微信,3:余额
        $payFrom = 0;
        $cuid = I('cuid', 0, 'intval');
        if (empty($addressId) || empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $mod = $m->SubmitOrder($loginName, $addressId, $goodsId, $orderRemarks, $requireTime, $couponId, $isSelf, $getuseScore, $lng, $lat, $payFrom, $cuid);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 提交订单-微信付款
     * */
    /*public function WxSubmitOrder(){
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $addressId = I("addressId");
        $orderRemarks = I('orderRemarks');
        $requireTime = I('requireTime');
        $couponId = I('couponId');
        $isSelf = I('isSelf');
        $getuseScore = (int)I('getuseScore',0);//使用积分
        $cuid = I('cuid');//wst_coupons_users 表中的 id
        $payFrom = I('payFrom','2'); //支付方式 (1=>支付宝,2=>微信,3=>余额)
        if(empty($addressId)){
            $apiRet = returnData(null,-1,'error','字段有误');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }
        $param = [];
        $param['userId'] = (int)$userId;
        $param['addressId'] = (int)$addressId;
        $param['orderRemarks'] = $orderRemarks;
        $param['requireTime'] = $requireTime;
        $param['couponId'] = (int)$couponId;
        $param['isSelf'] = (int)$isSelf;
        $param['useScore'] = $getuseScore;
        $param['cuid'] = $cuid;
        $param['payFrom'] = $payFrom;
        $mod = $m->WxSubmitOrderSku($param);
        if(I("apiAll") == 1){return $mod;}else{$this->ajaxReturn($mod);}//返回方式处理
    }*/

    /**
     * PS:在原有基础前置仓模式下兼容多商户模式,前置仓已经在用该接口了,所以就不重构了
     * 提交订单-微信付款
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/tnbzax
     * 共用参数
     * @param string memberToken
     * @param int fromType PS:应用模式,默认为前置仓(1:前置仓|2:多商户)
     * @param int isSelf PS:是否自提(1:自提)
     * @param int useScore PS:是否使用积分(1:使用积分)
     * @param int payFrom PS:支付方式(1:支付宝|2:微信|3:余额|4:货到付款)
     * @param int addressId PS:地址id
     * @param datetime requireTime PS:送达时间
     * @param int delivery_time_id PS:配送时间段id
     * 前置仓参数
     * @param int cuid PS:用户领取的优惠券id
     * @param string orderRemarks PS:订单备注
     * @param int invoiceClient PS:发票id
     * 多商户参数
     * @param jsonString shopParam
     * invoiceClient:发票id|cuid:用户领取的优惠券id|orderRemarks:订单备注
     * 例子:
     * [{"shopId":"1","invoiceClient":"0","cuid":"12","orderRemarks":"订单备注"},{"shopId":"2","invoiceClient":"17","cuid":"12","orderRemarks":"订单备注"}]
     * */
    public function WxSubmitOrder()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $addressId = I("addressId");
        $orderRemarks = I('orderRemarks');
        $requireTime = I('requireTime');
        $couponId = I('couponId');
        $isSelf = I('isSelf');
        $delivery_time_id = (int)I('delivery_time_id');//配送时间段id
        if (empty($delivery_time_id)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择送达时间'));
        }
        $useScore = (int)I('useScore', 0);//使用积分
        $getuseScore = (int)I('getuseScore', 0);
        $wuCouponId = (int)I('wuCouponId', 0);//运费劵ID
        $orderSrc = (int)I('orderSrc', 4);
        if (isset($_REQUEST['getuseScore'])) {
            //PS:旧接口遗留字段getuseScore字段,同useScore字段作用一样,后端直接兼容下,前端就不做硬性要求了
            $useScore = $getuseScore;
        }
        $cuid = I('cuid');//wst_coupons_users 表中的 id
        if (empty($cuid) && !empty($couponId)) {
            $cuid = $couponId;
        }
        $payFrom = I('payFrom'); //支付方式 (1=>支付宝,2=>微信,3=>余额,4=>货到付款)
        $fromType = (int)I('fromType', 1);//应用模式(1:前置仓|2:多商户)
        if (empty($addressId) && $isSelf != 1) {//自提订单就不需要验证收货地址了
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if (empty($payFrom)) {
            $apiRet = returnData(null, -1, 'error', '请选择支付方式');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $shopId = I('shopId', 0, 'intval');
        $param = [];
        //公用参数
        $param['userId'] = (int)$userId;
        $param['addressId'] = (int)$addressId;
        $param['requireTime'] = $requireTime;
        $param['isSelf'] = (int)$isSelf;
        $param['useScore'] = $useScore;
        $param['payFrom'] = $payFrom;
        $param['orderSrc'] = $orderSrc;
        $param['fromType'] = $fromType;
        $param['wuCouponId'] = (int)$wuCouponId;//运费劵ID
        //前置仓参数
        $param['orderRemarks'] = $orderRemarks;
        $param['couponId'] = (int)$couponId;//旧接口遗留字段,已废弃
        $param['cuid'] = $cuid;
        $param['shopId'] = $shopId;
        $param['invoiceClient'] = (int)I('invoiceClient');
        //多商户参数
        $param['shopParam'] = htmlspecialchars_decode(I('shopParam'));
        $param['delivery_time_id'] = $delivery_time_id;

        $param['buyNowGoodsId'] = I('buyNowGoodsId', 0);//立即购买-商品id 注：仅用于立即购买
        $param['buyNowSkuId'] = I('buyNowSkuId', 0);//立即购买-skuId 注：仅用于立即购买
        $param['buyNowGoodsCnt'] = I('buyNowGoodsCnt', 0);
        $mod = new ApiModel();
        $result = $mod->wxSubmitOrder($param);//前期测试接口,稳定后换回正式接口
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    //获取商城自营店铺
    public function toShopHome()
    {
        $lat = I('lat');
        $lng = I('lng');
        $adcode = I('adcode');
        $page = I('page', 1);
        if (empty($lat) || empty($lng) || empty($adcode)) {
//            $res['apiCode'] = '-1';
//            $res['apiInfo'] = '参数有误';
//            $res['apiState'] = 'error';
//            $res['apiData'] = null;
            $res = returnData(null, -1, 'error', '参数有误');
            if (I("apiAll") == 1) {
                return $res;
            } else {
                $this->ajaxReturn($res);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->toShopHome($lat, $lng, $adcode, $page);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城分类 二级分类下所有商品
    public function typeTwoGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $goodsCatId2 = I("goodsCatId2", 0);
        $page = I("page", 1);
        $pageSize = I("pageSize", 20);
        $lat = (float)I("lat");
        $lng = (float)I("lng");

        $m = D("V3/Api");
        $mod = $m->typeTwoGoods($userId, $goodsCatId2, $page, $pageSize, $lat, $lng);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城分类 获取一级分类下所有商品
    public function ShopTypeOneGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $goodsCatId1 = I("goodsCatId1");
        if (intval($goodsCatId1) == 0) {
            $mod = returnData(null, -1, 'error', '参数有误');
            if (I("apiAll") == 1) {
                return $mod;
            } else {
                $this->ajaxReturn($mod);
            }//返回方式处理

        }
        $m = D("V3/Api");
        $mod = $m->ShopTypeOneGoods($userId, $goodsCatId1);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //app微信支付成功验证-改变订单状态提供给微信服务器
    public function AppWxVerification()
    {
        $data = file_get_contents('php://input');//接收微信数据
        $m = D("V3/Api");
        $mod = $m->AppWxVerification($data);
        if ($mod) {
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        } else {
            header("Content-type:text/xml");
            $resxml['return_code'] = 'FAIL';
            exit(arrayToXml($resxml));
        }
    }

    //小程序 - 微信支付成功验证-改变订单状态提供给微信服务器
    public function xcxWxVerification()
    {
        $data = file_get_contents('php://input');//接收微信数据
        $m = D("V3/Api");
        $mod = $m->xcxWxVerification($data);
        if ($mod) {
            header("Content-type:text/xml");
            $resxml['return_code'] = 'SUCCESS';
            exit(arrayToXml($resxml));
        } else {
            header("Content-type:text/xml");
            $resxml['return_code'] = 'FAIL';
            exit(arrayToXml($resxml));
        }

    }

    //app 首页4个随机分类
    public function randtypelist()
    {
        $m = D("V3/Api");
        $shopId = (int)I('shopId');
        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $mod = $m->randtypelist($shopId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城4个随机分类
    public function adminRandtypelist()
    {
        $m = D("V3/Api");
        $mod = $m->adminRandtypelist();
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //地点检索
    public function placeapi()
    {
        $wd = I("wd");
        $region = I('region');
        $lat = I('lat');
        $lng = I('lng');
        if (empty($wd) || empty($region)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->placeapi($wd, $region, $lat, $lng);
        header('Content-type: application/json');
        $mod = returnData(json_decode($mod, true));
        // exit($mod);//百度已经返回json 不需要再次json了
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }


    //地点检索提示
    public function placeapiTips()
    {
        $wd = I("wd");
        $region = I('region');
        $lat = I('lat');
        $lng = I('lng');
        if (empty($wd) || empty($region)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->placeapiTips($wd, $region, $lat, $lng);
        header('Content-type: application/json');
        $mod = returnData(json_decode($mod, true));
        // exit($mod);//百度已经返回json 不需要再次json了
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取后台设置的已开放的城市列表和各下的所有级别的城市 根据字母排序
    public function getishowcitylist()
    {
        $m = D("V3/Api");
        $mod = $m->getCityGroupByKey();
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    // 获取商城开放的顶级城市列表 (热门城市)
    public function hotcitylist()
    {
        $m = D("V3/Api");
        $mod = $m->hotcitylist();
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    // todo 重构一个 接口 调用店铺的
    // https://api.gaoshouadmin.com/v3/index/hotcitylist
    public function hotcitylist_new(){
        $m = D("V3/Api");
        $mod = $m->hotcitylist();
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据地区名称获取adcode 城市代码
    public function areaNameGetAdcode()
    {
        $areaName = I('areaName');
        if (empty($areaName)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->areaNameGetAdcode($areaName);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据一级地区名称获取adcode 城市代码
    public function areaNameOneGetAdcode()
    {
        $areaName = I('areaName');
        if (empty($areaName)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->areaNameOneGetAdcode($areaName);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城 一级分类列表
    public function getShopTypeOnelistData()
    {
        $m = D("V3/Api");
        $mod = $m->getShopTypeOnelistData();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城首页一级分类列表（支持多商户/多门店）| 获取首页商城分类
    public function getShopTypeOnelistDataByIndex()
    {
        $shopId = I('shopId', 0, 'intval');
        $m = D("V3/Api");
        $mod = $m->getShopTypeOnelistDataByIndex($shopId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城 二级分类列表
    public function getShopTypeTwolistData()
    {
        $m = D("V3/Api");
        $mod = $m->getShopTypeTwolistData();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城 三级分类列表
    public function getShopTypeThreelistData()
    {
        $m = D("V3/Api");
        $mod = $m->getShopTypeThreelistData();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城 二三级分类列表
    public function getShopTypeTwoAndThreelistData()
    {
        $m = D("V3/Api");
        $mod = $m->getShopTypeTwoAndThreelistData();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城 一二三级分类列表
    public function getShopTypeOneAndTwoAndThreelistData()
    {
        $m = D("V3/Api");
        $mod = $m->getShopTypeOneAndTwoAndThreelistData();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据商品id获取商品轮播图
    public function goodsBanners()
    {
        $goodsId = I('goodsId');

        if (empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $res = M("goods")->where(['goodsId' => $goodsId, 'goodsFlag' => 1])->field('shopId')->find();
        $shopId = $res['shopId'];

        $m = D("V3/Api");
        $mod = $m->goodsBanners($goodsId, $shopId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据第三级城市获取品牌
    public function AdcodeBrands()
    {
        $areaId3 = I('adcode');
        $page = I('page', 1);
        if (empty($areaId3)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->AdcodeBrands($areaId3, $page);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取热销商品列表
    public function getHotGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
//        $areaId3 = I('adcode');
//        if (empty($areaId3)) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
        $lat = (float)I('lat');
        $lng = (float)I('lng');
        if (empty($lat) || empty($lng)) {
            $apiRet = returnData(null, -1, 'error', '字段有误-经纬度必传');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

//        $m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->getHotGoodsList($userId, $lat, $lng);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据 店铺id获取所有评价列表
    public function getShopEvaluateList()
    {
        $shopId = I('shopId');
        $page = I('page', 1);
        if (empty($shopId) || empty($page)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getShopEvaluateList($shopId, $page);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取指定店铺类型评价 好中差 默认好评
    public function getShopEvaluateListDes()
    {
        $shopId = I('shopId');
        $page = I('page', 1);
        $compScore = (int)I("compScore", 2);
        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getShopEvaluateListDes($shopId, $page, $compScore);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据店铺id 获取店铺热销商品
    public function getShopHotGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = I('shopId');

        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 10, 'intval');

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getShopHotGoodsList($shopId, $page, $userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取第三级分类下的商品
    public function getShopTypeidGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);
        $typeThreeId = I('typeThreeId');

        if (empty($adcode) || empty($lat) || empty($lng) || empty($typeThreeId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $request = I();
        isset($request['priceSort']) ? $sort['priceSort'] = $request['priceSort'] : false;
        isset($request['saleCount']) ? $sort['saleCount'] = $request['saleCount'] : false;
        isset($request['brandId']) ? $sort['brandId'] = $request['brandId'] : false;
        isset($request['goodsAttrId']) ? $sort['goodsAttrId'] = $request['goodsAttrId'] : false;
        $m = D("V3/Api");
        $mod = $m->getShopTypeidGoodsList($userId, $adcode, $lat, $lng, $typeThreeId, $page, $sort);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城分类 获取指定店铺商品
    public function getShopTypeidIsOneGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $page = I('page', 1);
        $typeThreeId = I('typeThreeId');
        $shopId = I('shopId');

        if (empty($typeThreeId) || empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getShopTypeidIsOneGoodsList($userId, $typeThreeId, $page, $shopId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商品的综合评价 缓存以后都加在控制器上
    public function goodDetailAppraisess()
    {
        $goodsId = I('goodsId');

        if (empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = S("NIAO_CACHE_goodDetailAppraisess{$goodsId}");
        if (empty($mod)) {
            $m = D("V3/Api");
            $mod = $m->goodDetailAppraises($goodsId);
            S("NIAO_CACHE_goodDetailAppraisess{$goodsId}", $mod, 24 * 60 * 60);
        }

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺的综合评价
    public function shopDetailAppraisess()
    {
        $shopId = I('shopId');

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = S("NIAO_CACHE_shopDetailAppraisess{$shopId}");
        if (empty($mod)) {
            $m = D("V3/Api");
            $mod = $m->shopDetailAppraisess($shopId);
            S("NIAO_CACHE_shopDetailAppraisess{$shopId}", $mod, C("allApiCacheTime"));
        }

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //小程序 消息推送
    public function msgsend()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = '5e4ba45125093cf72a52298f1d303ea4';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //菜谱搜索
    public function menuQuery()
    {
        $wd = I('wd');

        //$wd = urlencode($wd);
        if (empty($wd)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $resData = menuTokenFind(md5(base64_encode($wd) . 'menuQuery'));
        if ($resData) {
            header('Content-type: application/json');
            exit($resData);
        }

        $apiParames['key'] = $GLOBALS["CONFIG"]["menu_key"];
        $apiParames['menu'] = $wd;
        $apiParames['rn'] = 30;
        header('Content-type: application/json');
        $resData = curlRequest('http://apis.juhe.cn/cook/query.php', $apiParames, true);

        if (json_decode($resData, true)['resultcode'] == '200') {
            menuTokenAdd(md5(base64_encode($wd) . 'menuQuery'), $resData);
            exit($resData);
        }
        exit($resData);
    }

    //菜谱分类列表
    public function menuCategory()
    {
        $resData = menuTokenFind(md5('menuCategory'));
        if ($resData) {
            header('Content-type: application/json');
            exit($resData);
        }

        $apiParames['key'] = $GLOBALS["CONFIG"]["menu_key"];
        header('Content-type: application/json');
        $resData = curlRequest('http://apis.juhe.cn/cook/category', $apiParames, true);

        if (json_decode($resData, true)['resultcode'] == '200') {
            menuTokenAdd(md5('menuCategory'), $resData);
            exit($resData);
        }
        exit($resData);
    }

    //根据分类id获取菜谱列表
    public function menuIndex()
    {
        $cid = I('cid');
        $pn = I('pn', 0);//页数 默认第0页
        //$wd = urlencode($wd);
        if (empty($cid)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $resData = menuTokenFind(md5((string)$cid . (string)$pn . 'menuIndex'));
        if ($resData) {
            header('Content-type: application/json');
            exit($resData);
        }

        //json方式存储到数据库
        $apiParames['key'] = $GLOBALS["CONFIG"]["menu_key"];
        $apiParames['rn'] = 30;//一页30条
        $apiParames['pn'] = $pn;//页数
        $apiParames['cid'] = $cid;//分类id
        header('Content-type: application/json');
        $resData = curlRequest('http://apis.juhe.cn/cook/index', $apiParames, true);

        if (json_decode($resData, true)['resultcode'] == '200') {
            menuTokenAdd(md5((string)$cid . (string)$pn . 'menuIndex'), $resData);
            exit($resData);
        }
        exit($resData);
    }

    //根据菜谱id获取菜谱详细信息
    public function menuQueryid()
    {
        $id = I('id');
        if (empty($id)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $resData = menuTokenFind(md5($id . 'menuQueryid'));
        if ($resData) {
            header('Content-type: application/json');
            exit($resData);
        }

        //json方式存储到数据库
        $apiParames['key'] = $GLOBALS["CONFIG"]["menu_key"];
        $apiParames['id'] = $id;//菜谱id
        header('Content-type: application/json');
        $resData = curlRequest('http://apis.juhe.cn/cook/queryid', $apiParames, true);

        if (json_decode($resData, true)['resultcode'] == '200') {
            menuTokenAdd(md5($id . 'menuQueryid'), $resData);
            exit($resData);
        }
        exit($resData);
    }

    //首页-商城秒杀商品
    public function indexSecKillList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');

        if (empty($adcode) || empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->indexSecKillList($userId, $adcode, $lat, $lng);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城秒杀商品列表-分页
    public function indexSecKillAllLists()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);

        if (empty($adcode) || empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->indexSecKillAllLists($userId, $adcode, $lat, $lng, $page);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺秒杀商品列表
    public function ShopSecKillAllLists()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = I('shopId');

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->ShopSecKillAllLists($userId, $shopId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取客户端ip
    public function getClientIp()
    {
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=array('ip'=>get_client_ip());
        $apiRet = returnData(array('ip' => get_client_ip()));
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    //获取订单详情
    public function getOrderDetail()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $orderId = I('orderId');
        if (empty($orderId)) {
            // $apiRet['apiCode']=-1;
            // $apiRet['apiInfo']='字段有误';
            // $apiRet['apiState']='error';

            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getOrderDetail($orderId, $userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商品是否被关注
    public function goodIsFollow()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $targetId = I('targetId');
        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->goodIsFollow($targetId, $userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺是否被关注
    public function shopIsFollow()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $targetId = I('targetId');
        if (empty($targetId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->shopIsFollow($targetId, $userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城快讯 、通知
    public function adminShopNotice()
    {
        $mod = S("NIAO_CACHE_adminShopNotice");
        if (empty($mod)) {
            $m = D("V3/Api");
            $mod = $m->adminShopNotice();
            S("NIAO_CACHE_adminShopNotice", $mod, C("allApiCacheTime"));
        }
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据订单id 获取订单日志
    public function userOrderLog()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $orderId = I('orderId');
        if (empty($orderId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->userOrderLog($orderId, $userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    // app - 抽奖随机
    public function rndIntegral()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $m = D("V3/Api");
        $mod = $m->rndIntegral($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    // 小程序 - 抽奖随机
    public function xcxRndIntegral()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $m = D("V3/Api");
        $mod = $m->xcxRndIntegral($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //抽奖记录列表 分页
    public function ranListIntegralPage()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $page = I('page', 1);
        $mod = $m->ranListIntegralPage($userId, $page);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //积分记录列表 分页
    public function scoreHisList()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $page = I('page', 1);
        $mod = $m->scoreHisList($userId, $page);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //用户等级列表
    public function userListranks()
    {
        $mod = S("NIAO_CACHE_userListranks");
        if (empty($mod)) {
            $m = D("V3/Api");
            $mod = $m->userListranks();
            S("NIAO_CACHE_userListranks", $mod, C("allApiCacheTime"));
        }

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //用户等级信息(针对总后台等级)
    public function userRankInfo()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->userRankInfo($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //用户等级信息(针对商家等级)
    public function userRankInfoShop()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->userRankInfoShop($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取全部订单-分页
    public function getAllOrdersList()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $page = I('page', 1);
        $pageSize = I('pageSize', 10);

        $mod = $m->getAllOrdersList($userId, $page, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城精品
    public function mallFQ()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);

        if (empty($adcode) || empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->mallFQ($userId, $adcode, $lat, $lng, $page);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城推荐
    public function recFQ()
    {
        $userId = $this->getMemberInfo()['userId'];

        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);

        if (empty($lat) || empty($lng)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '字段有误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $pageSize = I('pageSize', 10);
        $mod = $m->recFQ($userId, $adcode, $lat, $lng, $page, $pageSize);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理}
    }

    //商城新品
    public function getNewGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
//        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);
        $pageSize = I('pageSize', 20);

        if (empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
//        $mod = $m->getNewGoods($adcode, $lat, $lng, $page, $pageSize, $userId);
        $mod = $m->getNewGoods($lat, $lng, $page, $pageSize, $userId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 店铺新品
     * @param int shopId
     * @param int
     * */
    public function getNewGoodsShop()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I('shopId');
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 20);
        /*if(empty($shopId)){
            $apiRet = returnData(null,-1,'error','字段有误');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }*/
        $adcode = I('adcode');
        $lng = I('lng');
        $lat = I('lat');

        //$m = D("V3/Api");
        $m = new ApiModel();
        $mod = $m->getNewGoodsShop($shopId, $page, $pageSize, $userId, $adcode, $lng, $lat);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺精品
    public function shopFQ()
    {
        $userId = $this->getMemberInfo()['userId'];
        $m = D("V3/Api");
        $shopId = I('shopId');

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $mod = $m->shopFQ($userId, $shopId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺推荐
    public function shopRecFQ()
    {
        $userId = $this->getMemberInfo()['userId'];
//        $m = D("V3/Api");
        $m = new ApiModel();
        $shopId = I('shopId');
        $page = I('page', 1);
        $pageSize = I('pageSize', 10);
        //前端增加了无店铺提示,所以这个提示要去掉
//        if(empty($shopId)){
//            // $apiRet['apiCode']=-1;
//            // $apiRet['apiInfo']='字段有误';
//            // $apiRet['apiState']='error';
//            $apiRet = returnData(null,-1,'error','参数有误');
//            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
//        }

        $mod = $m->shopRecFQ($userId, $shopId, $page, $pageSize);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //文章分类列表
    public function articleCats()
    {
        $m = D("V3/Api");
        $mod = $m->articleCats();

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //文章列表
    public function areasList()
    {
        $m = D("V3/Api");
        $catId = I('catId', null);

        $mod = $m->areasList($catId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //文章详情
    public function areasDetail()
    {
        $m = D("V3/Api");
        $articleId = I('articleId');

        if (empty($articleId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $mod = $m->areasDetail($articleId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城信息 包括商城联系方式
    public function mallDetail()
    {

        $config = $GLOBALS['CONFIG'];
        $data['livePlayTemplate'] = $GLOBALS["CONFIG"]['livePlayTemplate'];//直播列表模式[1:一排两个|2:一排三个]
        $data['mallLogo'] = $GLOBALS["CONFIG"]['mallLogo'];//商城Logo
        $data['mallName'] = $GLOBALS["CONFIG"]['mallName'];//商城名称
        $data['goodsImg'] = $GLOBALS["CONFIG"]['goodsImg'];//默认图片
        $data['mallFooter'] = $GLOBALS["CONFIG"]['mallFooter'];//底部设置
        $data['phoneNo'] = $GLOBALS["CONFIG"]['phoneNo'];//联系电话
        $data['qqNo'] = $GLOBALS["CONFIG"]['qqNo'];//QQ
        $data['hotSearchs'] = $GLOBALS["CONFIG"]['hotSearchs'];//热搜索词
        $data['appDownAndroid'] = $GLOBALS["CONFIG"]['appDownAndroid'];//安卓下载地址
        $data['appDownIos'] = $GLOBALS["CONFIG"]['appDownIos'];//苹果下载地址
        $data['scoreCashRatio'] = $GLOBALS["CONFIG"]['scoreCashRatio'];//积分与金钱兑换比例
        $data['isOpenScorePay'] = $GLOBALS["CONFIG"]['isOpenScorePay'];//开启积分支付
        $data['showBalance'] = $GLOBALS["CONFIG"]['showBalance'];//是否显示余额入口
        $data['AppExamine'] = $GLOBALS["CONFIG"]['AppExamine'];//app审核状态
        $data['AfterSaleTime'] = $GLOBALS["CONFIG"]['AfterSaleTime'];//售后期限

        $data['indexButtionIcon1'] = $GLOBALS["CONFIG"]['indexButtionIcon1'];//按钮1图标
        $data['indexButtionText1'] = $GLOBALS["CONFIG"]['indexButtionText1'];//按钮1文字
        $data['indexButtionIcon2'] = $GLOBALS["CONFIG"]['indexButtionIcon2'];//按钮2图标
        $data['indexButtionText2'] = $GLOBALS["CONFIG"]['indexButtionText2'];//按钮2文字
        $data['indexButtionIcon3'] = $GLOBALS["CONFIG"]['indexButtionIcon3'];//按钮3图标
        $data['indexButtionText3'] = $GLOBALS["CONFIG"]['indexButtionText3'];//按钮3文字
        $data['indexButtionIcon4'] = $GLOBALS["CONFIG"]['indexButtionIcon4'];//按钮4图标
        $data['indexButtionText4'] = $GLOBALS["CONFIG"]['indexButtionText4'];//按钮4文字
        $data['indexButtionIcon5'] = $GLOBALS["CONFIG"]['indexButtionIcon5'];//按钮5图标
        $data['indexButtionText5'] = $GLOBALS["CONFIG"]['indexButtionText5'];//按钮5文字
        $data['indexButtionIcon6'] = $GLOBALS["CONFIG"]['indexButtionIcon6'];//按钮6图标
        $data['indexButtionText6'] = $GLOBALS["CONFIG"]['indexButtionText6'];//按钮6文字
        $data['indexButtionIcon7'] = $GLOBALS["CONFIG"]['indexButtionIcon7'];//按钮7图标
        $data['indexButtionText7'] = $GLOBALS["CONFIG"]['indexButtionText7'];//按钮7文字
        $data['indexButtionIcon8'] = $GLOBALS["CONFIG"]['indexButtionIcon8'];//按钮8图标
        $data['indexButtionText8'] = $GLOBALS["CONFIG"]['indexButtionText8'];//按钮8文字
        $data['indexButtionIcon9'] = $GLOBALS["CONFIG"]['indexButtionIcon9'];//按钮9图标
        $data['indexButtionText9'] = $GLOBALS["CONFIG"]['indexButtionText9'];//按钮9文字
        $data['customerServiceLink'] = $GLOBALS["CONFIG"]['customerServiceLink'];//客服链接
        $data['inviteFriendRule'] = $GLOBALS["CONFIG"]['inviteFriendRule'];//邀请好友规则
        $data['qiniuDomain'] = $GLOBALS["CONFIG"]['qiniuDomain'];//七牛资源域名
        $data['qiniuUploadUrl'] = $GLOBALS["CONFIG"]['qiniuUploadUrl'];//七牛区域上传路径
        $data['shareContent'] = $GLOBALS["CONFIG"]['shareContent'];//APP分享自定义内容
        $data['defaultCity'] = $GLOBALS["CONFIG"]['defaultCity'];//默认城市
        $data['wxSmallImgSrc'] = $GLOBALS["CONFIG"]['wxSmallImgSrc'];//小程序二维码
        $data['mallDesc'] = $GLOBALS["CONFIG"]['mallDesc'];
        $data['rechargeRules'] = $GLOBALS['CONFIG']['rechargeRules'];
        $data['privacyAgreement'] = (string)$GLOBALS['CONFIG']['privacyAgreement'];//隐私协议
        $data['serviceAgreement'] = (string)$GLOBALS['CONFIG']['serviceAgreement'];// 服务协议
        $data['distributionTips'] = (string)$GLOBALS['CONFIG']['distributionTips'];// 分销规则
        $data['setDeliveryMoney'] = 0;//统一运费
        $data['deliveryFreeMoney'] = 0;//统一运费,包邮起步价
        $data['distributionImg'] = (string)$GLOBALS['CONFIG']['distributionImg'];//分销封面图
        $data['pullNewImg'] = (string)$GLOBALS['CONFIG']['pullNewImg'];//地推封面图
        $data['useCouponType'] = (string)$GLOBALS['CONFIG']['useCouponType'];//预提交-优惠券展示【1：用户手动选择|2：系统默认使用最大面值优惠券】
//        if ($config['setDeliveryMoney'] == 2) {
//            $data['setDeliveryMoney'] = $config['deliveryMoney'];
//            $data['deliveryFreeMoney'] = $config['deliveryFreeMoney'];
//        }
        $payments = M("payments")->select();
        $data['cod'] = [];
        $data['alipay'] = [];
        $data['weixin'] = [];
        foreach ($payments as $val) {
            if (!empty($val['payConfig'])) {
                $payConfig = json_decode($val['payConfig'], true);
                unset($val['payConfig']);
                $merge = array_merge($val, $payConfig);
            }
            if ($val['payCode'] == 'cod') {
                $data['cod'] = $val;
            } elseif ($val['payCode'] == 'alipay') {
                $data['alipay'] = $merge;
            } else if ($val['payCode'] == 'weixin') {
                $data['weixin'] = $merge;
            }

        }

        // $apiRet['apiCode']=0;
        // $apiRet['apiInfo']='获取商城信息成功';
        // $apiRet['apiState']='success';
        // $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    //对商品进行评价 - app
    public function goodAppraises()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");

        $goodsId = (int)I('goodsId');
        $content = I('content');
        $orderId = (int)I('orderId');
        $goodsScore = (int)I('goodsScore');
        $serviceScore = (int)I('serviceScore');
        $timeScore = (int)I('timeScore');
        $appraisesAnnex = I('appraisesAnnex');//不强制携带

//        if (empty($goodsId) || empty($content) || empty($orderId) || empty($goodsScore) || empty($serviceScore) || empty($timeScore)) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
        if (empty($goodsId) || empty($orderId)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '字段有误'));
        }
        if (empty($content)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请填写商品评价'));
        }

        //判断分数 是否超过5分
        if ($goodsScore > 5 || $serviceScore > 5 || $timeScore > 5) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='评分不能超过5分';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '评分不能超过5分');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if ($goodsScore <= 0) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择商品评分'));
        }
        if ($serviceScore <= 0) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择服务评分'));
        }
        if ($timeScore <= 0) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择时效评分'));
        }
        //判断分数 是否超过5分
//        if ($goodsScore <= 0 || $serviceScore <= 0 || $timeScore <= 0) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='评分不能为0或低于0分';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '评分不能为0或低于0分');
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }

        //我去 传的太多 合并一下吧
        $funData['userId'] = $userId;
        $funData['goodsId'] = $goodsId;
        $funData['content'] = $content;
        $funData['orderId'] = $orderId;
        $funData['goodsScore'] = $goodsScore;
        $funData['serviceScore'] = $serviceScore;
        $funData['timeScore'] = $timeScore;
        $funData['appraisesAnnex'] = $appraisesAnnex;

        $scro = (int)$funData['goodsScore'] + (int)$funData['serviceScore'] + (int)$funData['timeScore'];
        switch ($scro) {
            case $scro <= 5 :
                $funData['compScore'] = 0;
                break;
            case $scro > 5 and $scro <= 10:
                $funData['compScore'] = 1;
                break;
            case $scro > 10 and $scro <= 15:
                $funData['compScore'] = 2;
                break;
            default:
                $funData['compScore'] = null;
        }

        $mod = $m->goodAppraises($funData);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //对商品进行评价 - 小程序
    public function xcxGoodAppraises()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");

        $goodsId = (int)I('goodsId');
        $content = I('content');
        $orderId = (int)I('orderId');
        $goodsScore = (int)I('goodsScore');
        $serviceScore = (int)I('serviceScore');
        $timeScore = (int)I('timeScore');
        $appraisesAnnex = I('appraisesAnnex');//不强制携带

        if (empty($goodsId) || empty($content) || empty($orderId) || empty($goodsScore) || empty($serviceScore) || empty($timeScore)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //判断分数 是否超过5分
        if ($goodsScore > 5 || $serviceScore > 5 || $timeScore > 5) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='评分不能超过5分';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '评分不能超过5分');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //判断分数 是否超过5分
        if ($goodsScore <= 0 || $serviceScore <= 0 || $timeScore <= 0) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='评分不能为0或低于0分';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '评分不能为0或低于0分');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //我去 传的太多 合并一下吧
        $funData['userId'] = $userId;
        $funData['goodsId'] = $goodsId;
        $funData['content'] = $content;
        $funData['orderId'] = $orderId;
        $funData['goodsScore'] = $goodsScore;
        $funData['serviceScore'] = $serviceScore;
        $funData['timeScore'] = $timeScore;
        $funData['appraisesAnnex'] = $appraisesAnnex;

        $scro = (int)$funData['goodsScore'] + (int)$funData['serviceScore'] + (int)$funData['timeScore'];
        switch ($scro) {
            case $scro <= 5 :
                $funData['compScore'] = 0;
                break;
            case $scro > 5 and $scro <= 10:
                $funData['compScore'] = 1;
                break;
            case $scro > 10 and $scro <= 15:
                $funData['compScore'] = 2;
                break;
            default:
                $funData['compScore'] = null;
        }

        $mod = $m->xcxGoodAppraises($funData);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //订单-待评价列表
    public function timeEvaluate()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->timeEvaluate($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**e
     * 单个上传图片 移动端请使用目录：mobileUP
     */
    public function uploadPic()
    {

        // 指定允许来源域名访问
//        header('Access-Control-Allow-Origin: '.@$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Origin:*');
        header("Access-Control-Allow-Credentials: true");
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header("Access-Control-Allow-Methods: *");

        //if(I("apiAll") == 1){return $_FILES;}else{$this->ajaxReturn($_FILES);}//返回方式处理

        $userId = $this->MemberVeri()['userId'];
        //判断是否登录

        $config = array(
            'maxSize' => 1 * 1024 * 1024, //上传的文件大小限制 (0-不做限制) 目前限制最大1M
            'exts' => array('jpg', 'png', 'gif', 'jpeg'), //允许上传的文件后缀
            'rootPath' => './Upload/', //保存根路径
            'driver' => 'LOCAL', // 文件上传驱动
            'subName' => array('date', 'Y-m'),
            'savePath' => I('dir', 'uploads') . "/"
        );

        $dirs = explode(",", C("WST_UPLOAD_DIR"));
        if (!in_array(I('dir', 'uploads'), $dirs)) {
            echo '非法文件目录！';
            return false;
        }

        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $Filedata = key($_FILES);
        if (!$rs) {
            //$this->error($upload->getError());
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']=$upload->getError();
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', $upload->getError());
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        } else {
            $images = new \Think\Image();
            $images->open('./Upload/' . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename']);
            $newsavename = str_replace('.', '_thumb.', $rs[$Filedata]['savename']);
            $vv = $images->thumb(I('width', 300), I('height', 300))->save('./Upload/' . $rs[$Filedata]['savepath'] . $newsavename);
            if (C('WST_M_IMG_SUFFIX') != '') {
                $msuffix = C('WST_M_IMG_SUFFIX');
                $mnewsavename = str_replace('.', $msuffix . '.', $rs[$Filedata]['savename']);
                $mnewsavename_thmb = str_replace('.', "_thumb" . $msuffix . '.', $rs[$Filedata]['savename']);
                $images->open('./Upload/' . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename']);
                $images->thumb(I('width', 700), I('height', 700))->save('./Upload/' . $rs[$Filedata]['savepath'] . $mnewsavename);
                $images->thumb(I('width', 250), I('height', 250))->save('./Upload/' . $rs[$Filedata]['savepath'] . $mnewsavename_thmb);
            }
            $rs[$Filedata]['savepath'] = "Upload/" . $rs[$Filedata]['savepath'];
            $rs[$Filedata]['savethumbname'] = $newsavename;
            $rs['status'] = 1;
            header("resdata:" . json_encode($rs)); //兼容wex5

            if (I("apiAll") == 1) {
                return $rs;
            } else {
                $this->ajaxReturn($rs);
            }//返回方式处理
        }
    }

    //邀请的历史积分 以及 人数 和列表
    public function getInvitationInfo()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->getInvitationInfo($userId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //邀请的历史积分 以及 人数 和列表
    public function getCacheInvitationInfo()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->getCacheInvitationInfo($userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取商城预售列表 分页
    public function PreSaleList()
    {
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);

        if (empty($adcode) || empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->PreSaleList($adcode, $lat, $lng, $page);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺预售商品列表
    public function ShopPreSaleAllLists()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = I('shopId');

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->ShopPreSaleAllLists($shopId, $userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //提交订单--预售订单 -------------开发中  购物车接口判断是否是预售 预售产品不允许加入购物车  提交订单接口判断是否存在预售商品 存在就不能提交订单
    public function PreSaleSubmitOrder()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $addressId = (int)I('addressId');
        $goodsId = (int)I('goodsId');
        $goodsNum = (int)I('goodsNum');

        if (empty($addressId) || empty($goodsId) || empty($goodsNum)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $funData['addressId'] = $addressId;
        $funData['goodsId'] = $goodsId;
        $funData['goodsNum'] = $goodsNum;

        $funData['lng'] = I('lng');
        $funData['lat'] = I('lat');

        $mod = $m->PreSaleSubmitOrder($funData);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //店铺商品搜索
    public function mallSearch()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = I('shopId', 0);
        $wd = I('wd');
        $salesSort = I('salesSort', '', 'trim');//销售量  值有：desc asc
        $Price = I('Price', '', 'trim');//价格 值有：desc asc

        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
//            $apiRet = returnData(array(), -1, 'error', '字段有误');
            $apiRet = returnData(array(), -1, 'error', 'shopId字段不能为空');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        if (empty($wd)) {
            $apiRet = returnData(array(), 0, 'success', '操作成功');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $page['page'] = I('page', 1, 'intval');
        $page['pageSize'] = I('pageSize', 10, 'intval');

        $m = D("V3/Api");
        $mod = $m->mallSearch($shopId, $wd, $page, $salesSort, $Price, $userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //商城搜索

    /**
     * 商城-商城搜索
     * @param int shopId 门店id,传shopId就是前置仓模式,否则就是多商户模式
     * @param varchar wd 关键字
     * @param int adcode 区域id
     * @param float lat 纬度
     * @param float lng 经度
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     * @param int dataFormat 返回数据格式【1：以商品列表形式展示|2：店铺信息中包含商品列表】
     * */
    public function adminMallSearch()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I('shopId');
        $adcode = (int)I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 10);
        $wd = I('wd');
        $dataFormat = (int)I('dataFormat', 1);
        if (empty($lat) || empty($lng)) {
            $apiRet = returnData(array(), -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if (empty($wd)) {
            $apiRet = returnData(array());
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->adminMallSearch($shopId, $userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //提交订单-预售
    public function PreSaleSub()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $addressId = I("addressId");
        $goodsId = (int)abs(I("goodsId"));
        $goodsNum = (int)abs(I("goodsNum"));
        $skuId = I('skuId', 0);//新加skuId

        $lng = I('lng');
        $lat = I('lat');

        if (empty($addressId) || empty($goodsId) || empty($goodsNum)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //$mod = $m->PreSaleSub($userId,$addressId,$goodsId,$goodsNum,$lng,$lat);
        $mod = $m->PreSaleSubSku($userId, $addressId, $goodsId, $goodsNum, $lng, $lat, $skuId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //提交订单-预售 - 小程序
    public function xcxPreSaleSub()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $addressId = I("addressId");
        $goodsId = (int)abs(I("goodsId"));
        $goodsNum = (int)abs(I("goodsNum"));

        $lng = I('lng');
        $lat = I('lat');

        if (empty($addressId) || empty($goodsId) || empty($goodsNum)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $mod = $m->xcxPreSaleSub($userId, $addressId, $goodsId, $goodsNum, $lng, $lat);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //品牌 商品列表-分页
    public function indexBrandLists()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);
        $brandId = (int)I('brandId');

        if (empty($adcode) || empty($lat) || empty($lng) || empty($brandId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->indexBrandLists($adcode, $lat, $lng, $page, $brandId, $userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //根据订单号 获取 订单ID
    public function getOrderID()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $orderNo = I('orderNo');
        if (empty($orderNo)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getOrderID($orderNo);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取优惠券列表
    public function getCoupons()
    {
        $userId = 0;
        $memberToken = I('memberToken');
        if (!empty($memberToken)) {
            $userId = $this->MemberVeri()['userId'];
        }
        $m = D("V3/Api");
        $parameter = I('');
        $parameter['userId'] = $userId;
        $mod = $m->getCoupons($parameter);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //领取优惠券
    public function okCoupons()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $couponId = I('couponId');
        if (empty($couponId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->okCoupons($userId, $couponId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取会员的未使用优惠券
    public function getUserCoupons()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $m = D("V3/Api");
        $mod = $m->getUserCoupons($userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取会员的已使用优惠券
    public function getUserCouponsYes()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $m = D("V3/Api");
        $mod = $m->getUserCouponsYes($userId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取会员的已过期优惠券
    public function getUserCouponsNo()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $m = D("V3/Api");
        $mod = $m->getUserCouponsNo($userId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取自提码
    public function getUserCouponsNum()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $orderId = (int)I('orderId');
        $m = D("V3/Api");
        $mod = $m->getUserCouponsNum($userId, $orderId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //获取店铺配置
    public function getShopConfig()
    {
        $shopId = I('shopId');
        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getShopConfig($shopId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

//    //售后申诉
//    public function Mobcomplains()
//    {
//        $userId = $this->MemberVeri()['userId'];
//
//        $m = D("V3/Api");
//        $mod = $m->Mobcomplains($userId);
//
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }

    /**
     * 申请售后-提交售后申请
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/yv0xg7
     * */
    public function Mobcomplains()
    {
        $userId = $this->MemberVeri()['userId'];
        $requestParams = I();
        $params = array();
        $params['id'] = 0;
        $params['returnNum'] = 0;
        $params['complainType'] = '';
        $params['complainContent'] = '';
        $params['complainAnnex'] = '';
        parm_filter($params, $requestParams);
        if (empty($params['id'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-id'));
        }
        if (empty($params['complainType'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择退款原因'));
        }
        if (empty($params['complainContent'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请填写问题描述'));
        }
        if ((float)$params['returnNum'] <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货数量必须大于0'));
        }
        $mod = new ApiModel();
        $params['userId'] = $userId;
        $result = $mod->Mobcomplains($params);
        $this->ajaxReturn($result);
    }

    //售后列表
//    public function MobcomplainsList()
//    {
//        $userId = $this->MemberVeri()['userId'];
//
//        $m = D("V3/Api");
//        $mod = $m->MobcomplainsList($userId);
//        $mod = returnData($mod);
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }

    /**
     * 申请售后-售后订单列表
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/hcyxcf
     * */
    public function MobcomplainsList()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $mod = $m->MobcomplainsList($userId);
        $this->ajaxReturn(returnData($mod));
    }

    /**
     * 申请售后-订单商品详情
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/cfy0xa
     * */
    public function getMobcomplainsGoodsDetail()
    {
        $this->MemberVeri()['userId'];
        $id = (int)I('id');//订单商品唯一标识id
        $returnNum = (float)I('returnNum');//退货数量
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-id'));
        }
        if ($returnNum <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货数量必须大于0'));
        }
        $mod = new ApiModel();
        $result = $mod->getMobcomplainsGoodsDetail($id, $returnNum);
        $this->ajaxReturn($result);
    }

    //用户售后申请 列表 分页
//    public function userComplainsList()
//    {
//        $userId = $this->MemberVeri()['userId'];
//        $page = (int)I('page', 1);
//
//        if (empty($page)) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(array(), -1, 'error', '字段有误');
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
//
//        $m = D("V3/Api");
//        $mod = $m->userComplainsList($page, $userId);
//
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }

    /**
     * 售后记录-用户售后申请列表
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/ukqfpn
     * */
    public function userComplainsList()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D("V3/Api");
        $mod = $m->userComplainsList($userId, $page, $pageSize);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //用户售后详情 退款详情
    public function userComplainsDetail()
    {
        $userId = $this->MemberVeri()['userId'];
        $complainId = (int)I('complainId', 0);
        if (empty($complainId)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '字段有误'));
        }
        $m = D("V3/Api");
        $data = $m->userComplainsDetail($complainId, $userId);
        $this->ajaxReturn(returnData($data));
    }

    //计算最大退款差价
    public function ComplainsMoney()
    {
        $this->ajaxReturn(returnData(null, -1, 'error', '接口已废弃'));
        $userId = $this->MemberVeri()['userId'];

        $orderId = (int)I('orderId');
        $goodsId = (int)I('goodsId');
        $skuId = (int)I('skuId');

        if (empty($orderId) || empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=goodsPayN($orderId,$goodsId,$userId);
//        $apiRet = returnData(goodsPayN($orderId, $goodsId, $skuId, $userId));
        $m = new ApiModel();
        $apiRet = $m->getComplainsMoney($orderId, $goodsId, $skuId);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    //获取商城分类 第一级分类下的商品
    public function getAdminTypeidIsOneGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);
        $pageSize = I('pageSize', 20);
        $typeThreeId = I('typeThreeId');

        if (empty($adcode) || empty($lat) || empty($lng) || empty($typeThreeId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getAdminTypeidIsOneGoodsList($adcode, $lat, $lng, $typeThreeId, $page, $userId, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    public function wxtk()
    {
        //var_dump(wxRefund('4200000194201811280952930017',2080,2080,55,4,22));

    }

    //自动收货(未做)(已有自动收货定时任务)

    //获取今日已抽多少次
    public function userPrizeCount()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证
        $m = D("V3/Api");
        $count = $m->getUserPrizeCount($userId);

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取数据成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=array('count'=>$count);
        $apiRet = returnData(array('count' => $count));
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    //获取今日剩余抽奖次数
    public function userPrizeSurplusCount()
    {

    }

    //获取抽奖记录 需分页(已有，上面有个 ranListIntegralPage 函数)
    public function userPrizeHistory()
    {

    }

    //购买抽奖次数
    public function purchasePrizeCount()
    {

    }

    public function test()
    {
        if (I("apiAll") == 1) {
            return M("payments")->lock(true)->where("id = '4'")->count();
        } else {
            $this->ajaxReturn(M("payments")->lock(true)->where("id = '4'")->count());
        }//返回方式处理
    }

    //根据当前经纬度校验店铺是否允许配送
    public function checkShopDistribution()
    {
        $shopId = I('shopId');
        $lng = I('lng');
        $lat = I('lat');
        $res = checkShopDistribution($shopId, $lng, $lat);
        if (!$res) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='超出配送范围';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '超出配送范围');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='允许配送';
//        $apiRet['apiState']='success';
        $apiRet = returnData($res);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    //获取最新动态码
    public function getUsersDynamiccode()
    {
        $Users = $this->MemberVeri();
//        $Users['userId'] = 1;
        $m = D("V3/Api");
        $mod = $m->getUsersDynamiccode($Users);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    //通过地址获取经纬度
    public function area_getlat_lng()
    {
//        $Users =$this->MemberVeri();
        $m = D("V3/Api");
        $parameter = I();
        $res = $m->area_getlat_lng($parameter);
        $xml = simplexml_load_string($res);
        $data = json_decode(json_encode($xml), TRUE);
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取成功';
//        $apiRet['apiData'] = $data['result'];
//        $apiRet['apiState']='success';
        $apiRet = returnData($data['result']);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 获取小程序二维码
     */
    public function getProgramQrCode()
    {
        // 暂时注释$this->isLogin();
        // 暂时注释$this->checkPrivelege('hyzh_04');
        $jsonVal = I('jsonVal');
        if (!empty($jsonVal)) {
            // $jsonVal =  html_entity_decode($jsonVal); //需要字符解密 非转实体字符 辉修复2019
            $jsonVal = htmlspecialchars_decode($jsonVal);
        } else {
//            $rs['status'] = -1;
//            $rs['msg'] = "参数不能为空";
            $rs = returnData(null, -1, 'error', '参数不能为空');
            if (I("apiAll") == 1) {
                return $rs;
            } else {
                $this->ajaxReturn($rs);
            }//返回方式处理
        }
        $access_token = getWxAccessToken();
        if (!$access_token) {
//            $rs['status'] = -1;
            $rs = returnData(null, -1, 'error', '参数不能为空');

            if (I("apiAll") == 1) {
                return $rs;
            } else {
                $this->ajaxReturn($rs);
            }//返回方式处理
        }
        //获取图标
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        $res = file_get_contents_post($url, $jsonVal);

        // header('Content-type: image/jpeg');

        //由于使用接口 转码为base64_encode
        $res = base64_encode($res);
        echo $res;
    }

    //区分可用优惠券列表和不可用列表 检测权限
    public function getCouponAuthList()
    {
        $userId = $this->MemberVeri()['userId'];//身份认证

        $goodsId = I('goodsId');//请以英文逗号分隔
        if (empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getCouponAuthList($goodsId, $userId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * @return mixed
     * 根据订单id生成发票
     */
    public function addInvoiceReceipt()
    {
        $userId = $this->MemberVeri()['userId'];
//        $userId = 267;
        $m = D("V3/Api");
        $mod = $m->addInvoiceReceipt($userId);
//        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * @return mixed
     * 获取未开发票的订单
     */
    public function getPermitInvoiceList()
    {
        $userId = $this->MemberVeri()['userId'];
//        $userId = 267;
        $m = new ApiModel();
        $mod = $m->getPermitInvoiceList((int)$userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * @return mixed
     * 获取发票历史
     */
    public function getInvoiceHistoryList()
    {
        $userId = $this->MemberVeri()['userId'];
//        $userId = 267;
        $m = D("V3/Api");
        $mod = $m->getInvoiceHistoryList($userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * @return mixed
     * 获取发票详情
     */
    public function getInvoiceHistoryInfo()
    {
        $userId = $this->MemberVeri()['userId'];
//        $userId = 267;
        $m = D("V3/Api");
        $mod = $m->getInvoiceHistoryInfo($userId);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 抬头列表
     * */
    public function getInvoiceList()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 19;
        $m = D("V3/Api");
        $mod = $m->invoiceList($userId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 抬头添加
     * */
    public function InvoiceSave()
    {
        $userId = $this->MemberVeri()['userId'];
        $invoiceId = (int)I('id', 0);
        $m = D("V3/Api");
        $data["headertype"] = (int)I("headertype", 1);
        $data["headerName"] = I("headerName");
        $data["taxNo"] = I("taxNo");
        $data["address"] = I("address");
        $data["number"] = I("number");
        $data["depositaryBank"] = I("depositaryBank");
        $data["account"] = I("account");
        $data["userId"] = $userId;
        if ($invoiceId > 0) {
            $result = $m->invoiceSave($invoiceId, $data);
        } else {
            $data["addtime"] = date('Y-m-d H:i:s', time());
            $result = $m->invoiceInsert($data);
        }

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 抬头删除
     * */
    public function InvoiceDelete()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 18;
        $m = D("V3/Api");
        $result = $m->invoiceDel($userId);


        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 获取商品属性
     * */
    public function getGoodsAttr()
    {
        $m = D("V3/Api");
        $paramter = I();
        $data = [];
        !empty($paramter['goodsId']) ? $data['goodsId'] = $paramter['goodsId'] : 0;
        $mod = $m->getGoodsAttr($data);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *获取邀请人列表 PS:此邀请人列表是直属下线和二级下线混合在一个列表
     * */
    public function invitation()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $data = [];
        $data['userId'] = $userId;
        $mod = $m->invitation($data);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *获取邀请人列表 PS:直属下线
     * @param string memberToken
     * @param int page
     * @param int pageSize
     * */
    public function myInvitationFirst()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = "22";
        $m = D("V3/Api");
        $pageSize = (int)I('pageSize', 20);//条数 默认10
        $page = (int)I('page', 1);//指定页码
        $data = [];
        $data['userId'] = $userId;
        $data['pageSize'] = $pageSize;
        $data['page'] = $page;
        $mod = $m->myInvitationFirst($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *获取邀请人列表 PS:二级下线
     * @param string memberToken
     * @param int page
     * @param int pageSize
     * */
    public function myInvitationSecond()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = "22";
        $m = D("V3/Api");
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 20);
        $data = [];
        $data['userId'] = $userId;
        $data['page'] = $page;
        $data['pageSize'] = $pageSize;
        $mod = $m->myInvitationSecond($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *分销记录
     * */
    public function distribution()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 39;
        $pageSize = (int)I('pageSize', 20);//条数 默认20
        $page = (int)I('page', 1);//指定页码
        $m = D("V3/Api");
        $data = [];
        $data['userId'] = $userId;
        $data['pageSize'] = $pageSize;
        $data['page'] = $page;
        $mod = $m->distribution($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 记录用户分销关系表 【弃用】
     * @param varchar $userPhone PS:注册人手机号
     * @param int $add_is_ok_id PS:注册人id
     * */
    static function distributionRelation($userPhone, $add_is_ok_id)
    {
        $m = D("V3/Api");
        $data = $m->distributionRelation($userPhone, $add_is_ok_id);
        return $data;
    }

    /**
     * 分销数据显示助手函数
     * @param string memberToken
     * @param payType 提现方式【    1：银行卡|2：微信|3：支付宝】
     * @param dataType 提现类型【1：分销提现|2：用户余额提现】 不传默认为分销提现,因为分销提现已经存在了
     */
    public function getDwHandleInfo()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $data = [];
        $data['userId'] = $userId;
        $data['payType'] = I('payType', 1);
        $data['dataType'] = I('dataType', 1);
        if (!in_array($data['payType'], [1, 2, 3])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的提现方式'));
        }
        if (!in_array($data['dataType'], [1, 2])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的提现场景'));
        }
        $mod = $m->getDwHandleInfo($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 用户申请提现
     * @param int dataType 提现类型【1：分销提现|2：用户余额提现】 不传默认为分销提现
     * @param string money 提现金额
     * @param int payType 提现方式【1：银行卡|2：微信|3：支付宝】
     * @param string withdrawalAccount 提款账号
     * @param string actualName 持卡人姓名|真实姓名
     * @param int backId 银行卡ID
     * */
    public function distributionWithdraw()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $data = [];
        $data['userId'] = $userId;
        $data['dataType'] = I('dataType', 1);
        $data['payType'] = I('payType', 1);
        $data['money'] = I('money');
        $data['withdrawalMethod'] = I('payType');
        $data['withdrawalAccount'] = I('withdrawalAccount');
        $data['actualName'] = I('actualName');
        $data['bankId'] = I('backId');
        if (empty($data['money']) || empty($data['withdrawalAccount'])) {
            $apiRet = returnData(false, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if ($data['payType'] == 1 && empty($data['bankId'])) {
            $apiRet = returnData(false, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if (in_array($data['payType'], [1, 3]) && empty($data['actualName'])) {
            $apiRet = returnData(false, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        $mod = $m->distributionWithdraw($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *用户申请分销佣金提现
     * @param money varchar PS:提现金额
     * */
    public function distributionWithdraw2()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $data = [];
        $data['userId'] = $userId;
        $data['money'] = I('money');
        $data['type'] = I('type');
        $mod = $m->distributionWithdraw2($data);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }


    /**
     *用户提现记录
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/gur36r
     * */
    public function withdrawList()
    {
        $loginUserInfo = $userId = $this->MemberVeri();
        $userId = $loginUserInfo['userId'];
        $page = (int)I('page', 1);//条数 默认20
        $pageSize = (int)I('pageSize', 20);//指定页码
        $m = D("V3/Api");
        $params = array();
        $params['userId'] = $userId;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['dataFrom'] = I('dataFrom', 5);
        $params['dataFrom'] = empty($params['dataFrom']) ? 5 : $params['dataFrom'];
        $mod = $m->withdrawList($params);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn(returnData($mod));
        }//返回方式处理
    }

    /**
     * 依据第三级城市名 获取三级数据
     * */
    public function analysiscityList()
    {
        $name = I('name');
        $m = D("V3/Api");
        $mod = $m->analysiscityList($name);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取三级分类下面的品牌
     * @param adcode PS:三级分类id
     * */
    public function threeTypeBrand()
    {
        $adcode = I('adcode');
        if (empty($adcode)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $request['adcode'] = $adcode;
        $m = D("V3/Api");
        $mod = $m->threeTypeBrand($request);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商城二级分类下面的商品的所有属性
     * @param adcode PS:二级分类id
     * */
    public function twoTypeAttr()
    {
        $m = D("V3/Api");
        $request = I();
        $data['twoTypeId'] = $request['twoTypeId'];
        if (empty($data['twoTypeId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->twoTypeAttr($data);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 删除订单
     * @param orderId PS:订单id
     * */
    public function deleteOrder()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = '46';
        $orderId = I('orderId');
        if (empty($orderId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $request['orderId'] = $orderId;
        $request['userId'] = $userId;
        $m = D("V3/Api");
        $mod = $m->deleteOrder($request);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 分销记录
     * @param int userId 邀请人id
     * @param string userPhone 被邀请人手机号
     * @param string code 验证码
     * @param int dataType 数据类型【1：分销邀请记录|2：地推邀请记录】
     * */
    public function distributionLog()
    {
        $requestParams = I();
        if (empty($requestParams['userId']) || empty($requestParams['userPhone']) || empty($requestParams['code'])) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $params = [];
        $params['userId'] = 0;
        $params['userPhone'] = '';
        $params['code'] = '';
        $params['dataType'] = 1;
        parm_filter($params, $requestParams);
        $m = D("V3/Api");
        $mod = $m->distributionLog($params);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取手机验证码 PS:用于分销
     * @param string userPhone 手机号
     */
    public function distributionPhoneVerify()
    {
        $phone = WSTAddslashes(I("userPhone"));
        if (empty($phone)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $phone)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '手机号格式不正确'));
        }
        $phoneVerify = mt_rand(100000, 999999);
        $msg = "您正在获取邀请验证码，验证码为:" . $phoneVerify . "，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0, $phone, $msg, 'distributionPhoneVerify', $phoneVerify);
        $rv['time'] = 30 * 60;
        if ($rv['status'] == 1) {
            $rv = returnData(true, 0, 'success', '短信发送成功', '');
        } else {
            $rv = returnData(false, -1, 'error', $rv['msg']);
        }
        $this->ajaxReturn($rv);
    }

    /**
     * 获取所有非自营店的商品
     * @param int page
     * */
    public function normalShopGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $page = I('page', 1);
        $m = D("V3/Api");
        $response = [];
        !empty($page) ? $response['page'] = $page : $response['page'] = 1;
        $response['userId'] = (int)$userId;
        $mod = $m->normalShopGoods($response);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱收藏列表
     * @param int page
     * */
    public function menusCollectionList()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 44;
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $m = D("V3/Api");
        $response = [];
        !empty($page) ? $response['page'] = $page : $response['page'] = 1;
        $response['userId'] = $userId;
        $response['pageSize'] = $pageSize;
        $mod = $m->menusCollectionList($response);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱收藏添加
     * @param int menuId
     * */
    public function menusCollection()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 46;
        $menuId = I('menuId', 0);
        $m = D("V3/Api");
        $response = [];
        !empty($menuId) ? $response['menuId'] = $menuId : false;
        if (empty($response['menuId'])) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数错误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '参数错误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $response['userId'] = $userId;
        $response['addTime'] = date('Y-m-d H:i:s', time());
        $mod = $m->menusCollection($response);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱收藏删除
     * @param int menuId
     * */
    public function menusCollectionDelete()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 46;
        $menuId = I('menuId', 0);
        $m = D("V3/Api");
        $response = [];
        !empty($menuId) ? $response['menuId'] = $menuId : false;
        if (empty($response['menuId'])) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数错误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '参数错误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $response['userId'] = $userId;
        $mod = $m->menusCollectionDelete($response);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 今日推荐 PS(取数据库最新的一条)
     * */
    public function recommendList()
    {
        $m = D("V3/Api");
        $mod = $m->recommendList();


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 今日菜单
     * @param int page
     * @param int catId
     * @param int pageSize
     * */
    public function menusList()
    {
        $m = D("V3/Api");
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $catId = I('catId', 0);
        $mod = $m->menusList($catId, $page, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱分类
     * */
    public function menusCatList()
    {
        $m = D("V3/Api");
        $mod = $m->menusCatList();
//        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱搜索
     * @param string $keyword PS:菜谱标题或食材
     * */
    public function searchMenu()
    {
        $m = D("V3/Api");
        $page = I('page', 1);
        $keyword = I('keyword');
        $pageSize = I('pageSize', 10);
        /*if(empty($keyword)){
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '字段有误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null,-1,'error','字段有误');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }*/
        $mod = $m->searchMenu($keyword, $page, $pageSize);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱详情
     * @param int $menuId
     * */
    public function menuInfo()
    {
        //$userId = $this->MemberVeri()['userId'];
        $memberToken = I('memberToken');
        if (!empty($memberToken)) {
            $userId = $this->MemberVeri()['userId'];
        }
        $m = D("V3/Api");
        $menuId = I('menuId', 0);
        if (empty($menuId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '字段有误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->menuInfo($menuId, $userId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 菜谱详情
     * @param int $menuId
     * @param string lat
     * @param string lng
     * @param int adcode //店铺社区对应的区县ID
     * @param int shopId //店铺id shopId>0则为前置仓,否则为多商户
     * */
    public function menuInfoNew()
    {
        //$userId = $this->MemberVeri()['userId'];
        $memberToken = I('memberToken');
        $lat = I('lat');
        $lng = I('lng');
        $adcode = I('adcode');
        $shopId = I('shopId', 0);
        if (!empty($memberToken)) {
            $userId = $this->MemberVeri()['userId'];
        }
        $m = D("V3/Api");
        $menuId = I('menuId', 0);
        if (empty($menuId)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $mod = $m->menuInfoNew($menuId, $userId, $lat, $lng, $adcode, $shopId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 推荐商品菜单
     * @param int goodsId
     * @param int page
     * */
    public function recommendMenus()
    {
        $m = D("V3/Api");
        $goodsId = I('goodsId', 0);
        if (empty($goodsId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '字段有误';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $resquest['page'] = I('page', 1);
        $resquest['pageSize'] = I('pageSize', 15);
        $resquest['goodsId'] = $goodsId;
        $mod = $m->recommendMenus($resquest);


        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    // ===============================================================
    // ====================== - 拼团活动 - start - ===================
    // ===============================================================

    /**
     * 店铺拼团商品
     * 多社区
     */
    /*public function shopAssembleGoods(){

        $areaId3 = I('adcode');
        $user_lat = I('lat');
        $user_lng = I('lng');
        $page = I('page',1);

        $areas= D('V3/Api');
        $areaList = $areas->getDistricts($areaId3,$page);

        for($i=0;$i<=count($areaList)-1;$i++){
            $z_lat_lng[$i] = $areas->getDistanceBetweenPointsNew($user_lat,$user_lng,$areaList[$i]['latitude'],$areaList[$i]['longitude']);
            $areaList[$i]['distance'] = sprintf("%.2f",$z_lat_lng[$i]['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($areaList as $user) {
            $shopsDataSort[] = $user['distance'];
        }
        array_multisort($shopsDataSort,SORT_ASC,SORT_NUMERIC,$areaList);//从低到高排序

        $m = D('V3/Assemble');
        if (!empty($areaList)) {
            foreach ($areaList as $k=>$v) {
                $areaList[$k]['goodsList'] = rankGoodsPrice($m->getShopAssembleGoods($v['shopId']));
                //隐藏隐私字段
                unset($areaList[$k]['shopSn']);
                unset($areaList[$k]['userId']);
                unset($areaList[$k]['bankId']);
                unset($areaList[$k]['bankNo']);
                unset($areaList[$k]['bankUserName']);
            }
        }

        if(I("apiAll") == 1){return array('code'=>0, 'areaList'=>$areaList);}else{$this->ajaxReturn(array('code'=>0, 'areaList'=>$areaList));}//返回方式处理
    }*/

    /**
     * 店铺拼团商品
     * 多门店
     */
//    public function shopAssembleGoods(){
//        $userId = $this->getMemberInfo()['userId'];
//        $shopId = I('shopId',0,'intval');
//        if (empty($shopId)) if(I("apiAll") == 1){
////            return array('code'=>1,'msg'=>'参数不全');
//            return returnData(null,-1,'error','参数不全');
//        }else{
////            $this->ajaxReturn(array('code'=>1,'msg'=>'参数不全'));
//            $this->ajaxReturn(returnData(null,-1,'error','参数不全'));
//        }//返回方式处理
//        $page = I('page',1,'intval');
//        $pageSize = I('pageSize',10,'intval');
//
//        $m = D('V3/Assemble');
//        $list = $m->getShopAssembleGoods($shopId,$page,$pageSize,$userId);
//        $list = returnData($list);
//
//
//        if(I("apiAll") == 1){
////            return array('code'=>0,'msg'=>'操作成功','data'=>$list);
//            return $list;
//        }else{
////            $this->ajaxReturn(array('code'=>0,'msg'=>'操作成功','data'=>$list));
//            $this->ajaxReturn($list);
//        }//返回方式处理
//    }

    /**
     * 店铺拼团商品 兼容前置仓和多商户
     * @param int shopId 店铺id,有值为前置仓,无值为多商户
     * @param float lat 纬度
     * @param float lng 经度
     * @param int page 页码
     * @param int pageSize 分页条数
     * */
    public function shopAssembleGoods()
    {
        $user_id = $this->getMemberInfo()['userId'];
        $shop_id = (int)I('shopId');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 10);
        $lat = (float)I('lat');
        $lng = (float)I('lng');
//        $m = D('V3/Assemble');
        $mod = new AssembleModel();
        $result = $mod->getShopAssembleGoods($shop_id, $user_id, $page, $page_size, $lat, $lng);
        $this->ajaxReturn($result);
    }

    /**
     * 拼团商品详情
     * @param int aid 拼团活动id
     */
    public function assembleGoodsDetail()
    {
        $aid = I('aid', 0, 'intval');
        if (empty($aid)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '参数不全'));
        }
//        $m = D('V3/Assemble');
        $mod = new AssembleModel();
        $result = $mod->getAssembleDetail($aid);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 所有店铺里的拼团活动
     * //前置仓模式
     * @param int shopId 店铺id
     * @param int page 页码
     * @param int pageSize 分页条数
     * //多商户模式
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function assembleActivityList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = I('shopId', 0, 'intval');
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');
        $m = D('V3/Assemble');
        $res = $m->getAssembleActivityList($userId, $shopId, $adcode, $lat, $lng, $page, $pageSize);
        $this->ajaxReturn($res);
    }

    /**
     * 生成拼团订单,PS:拼团订单不支持余额支付,特此备注
     */
//    public function createAssembleOrder()
//    {
//        $user = $this->MemberVeri();
//        $m = D('V3/Assemble');
//        $result = $m->submitAssembleOrder($user);
//        if (I("apiAll") == 1) {
//            return $result;
//        } else {
//            $this->ajaxReturn($result);
//        }//返回方式处理
//    }

    /**
     * 生成拼团订单
     * @param int aid 活动ID
     * @param int consigneeId 地址id
     * @param int isself 是否自提【0：不自提|1：自提】
     * @param int needreceipt 是否需要发票【0：不需要|1：需要】
     * @param int invoiceClient 发票id
     * @param varchar orderRemarks 订单备注
     * @param dateTime requireTime 要求送达时间
     * @param int skuId 商品skuId
     * @param int goodsCnt 商品数量,默认为1
     * @param int goodsAttrId 商品属性id
     * @param int pid 拼团ID
     * */
    public function createAssembleOrder()
    {
        //该代码里的主逻辑皆为之前的逻辑,如要了解,请看上面注释的方法
        $userId = $this->MemberVeri()['userId'];
        $requestParams = I();
        $params = array(
            'aid' => 0,
            'consigneeId' => 0,
            'isself' => 0,
            'needreceipt' => 0,
            'invoiceClient' => 0,
            'orderRemarks' => '',
            'remarks' => '',
            'requireTime' => '',
            'skuId' => 0,
            'goodsCnt' => 1,
            'goodsAttrId' => 0,
            'pid' => 0,
        );
        parm_filter($params, $requestParams);
        if (empty($params['aid'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', "缺少必填参数-aid"));
        }
        $params['payFrom'] = I('payFrom', 2);
        if ($params['payFrom'] != 2) {
            $this->ajaxReturn(returnData(false, -1, 'error', "拼团暂只支持微信支付"));
        }
        if ($params['isself'] != 1 && empty($params['consigneeId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', "请填写正确的收货地址"));
        }
        $params['userId'] = $userId;
        $mod = new AssembleModel();
        $result = $mod->submitAssembleOrder($params);
        $this->ajaxReturn($result);
    }

    /**
     * 参与拼团订单
     */
    public function joinAssembleOrder()
    {
        $user = $this->MemberVeri();
        $m = D('V3/Assemble');
        $result = $m->joinAssembleOrder($user);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 微信支付成功后，订单的处理（弃用）
     */
    public function orderPaySuccess()
    {
        $orderId = I('orderId', 0, 'intval');
        $tradeNo = I('tradeNo', '', 'trim');
        $m = D('V3/Assemble');
        $result = $m->updateOrder($orderId, $tradeNo);
        $result = returnData($result);
        if (I("apiAll") == 1) {
            return array('code' => $result ? 0 : 1);
        } else {
            $this->ajaxReturn(array('code' => $result ? 0 : 1));
        }//返回方式处理
    }

    /**
     * 微信支付失败后，订单的处理(弃用)
     */
    public function orderPayFail()
    {
        $param = array(
            'userId' => I('userId', 0, 'intval'),
            'orderId' => I('orderId', 0, 'intval')
        );

        $m = D('V3/Assemble');

        if (I("apiAll") == 1) {
            return $m->assembleOrderCancel($param);
        } else {
            $this->ajaxReturn(returnData($m->assembleOrderCancel($param)));
        }//返回方式处理
    }

    /**
     * 拼团商品搜索(多商户/多门店)
     * //多门店
     * @param int shopId 门店id
     * @param varchar keywords 关键字
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     * //多商户
     * @param varchar keywords 关键字
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     */
    public function searchAssembleGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $requestParams = I();
        $params = [];
        $params['keywords'] = '';
        $params['shopId'] = 0;
        $params['adcode'] = 0;
        $params['lng'] = 0;
        $params['lat'] = 0;
        $params['page'] = 1;
        $params['pageSize'] = 10;
        parm_filter($params, $requestParams);
        $params['userId'] = (int)$userId;
        if (empty($params['keywords'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入搜索关键字'));
        }
        $m = D('V3/Assemble');
        $res = $m->getAssembleGoodsListByKeywords($params);
        $this->ajaxReturn($res);
    }

    /**
     * 用户拼团订单列表
     * @param int flag 拼团状态【-1：拼团失败|2：拼团中|3:拼团成功】
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     */
    public function userAssembleOrderList()
    {
        $userId = $this->MemberVeri()['userId'];
        $flag = (int)I('flag');
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 10);
        $m = D('V3/Assemble');
        $res = $m->getUserAssembleOrderList($userId, $flag, $page, $pageSize);
        $this->ajaxReturn($res);
    }

    /**
     * 用户拼团订单详情
     */
//    public function userAssembleOrderDetail()
//    {
//        $orderId = I('orderId', 0, 'intval');
//        if (empty($orderId)) if (I("apiAll") == 1) {
////            return array('code'=>1, 'msg'=>'参数不全');
//            return returnData(null, -1, 'error', '参数不全');
//        } else {
////            $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));
//            $this->ajaxReturn(returnData(null, -1, 'error', '参数不全'));
//        }//返回方式处理
//
//        $m = D('V3/Assemble');
//        $orderDetail = $m->getOrdersDetails($orderId);
//        $orderGoods = $m->getOrdersGoods($orderId);
//        $surplusPeopleNum = $m->getSurplusPeopleNum($orderId);//拼团剩余人数
//        $currentAssembleUserList = $m->getCurrentAssembleUserList($orderId);//当前拼团用户列表
//        $buyPeopleNum = $m->getBuyPeopleNum($orderId);//获得当前拼团购买人数
//        $data = array('orderDetail' => $orderDetail, 'orderGoods' => $orderGoods, 'surplusPeopleNum' => $surplusPeopleNum, 'currentAssembleUserList' => $currentAssembleUserList, 'buyPeopleNum' => $buyPeopleNum);
//        $data = returnData($data);
//
//        if (I("apiAll") == 1) {
//            return $data;
//        } else {
//            $this->ajaxReturn($data);
//        }//返回方式处理
//    }

    /**
     * 用户拼团订单详情
     * @param int $orderId 订单id
     * */
    public function userAssembleOrderDetail()
    {
        $userId = $this->MemberVeri()['userId'];
        $orderId = I('orderId', 0, 'intval');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = D('V3/Assemble');
        $res = $m->getUserAssembleOrderDetail($userId, $orderId);
        $this->ajaxReturn($res);
    }

    /**
     * 团长拼团详情
     * @param int $orderId 订单id
     * */
    public function userAssembleGoodsDetail()
    {
        $pid = I('pid', 0, 'intval');
        if (empty($pid)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = D('V3/Assemble');
        $res = $m->getUserAssembleGoodsDetail($pid);
        $this->ajaxReturn($res);
    }


    /**
     * 购物车列表（弃用）
     */
    public function cartList()
    {
        $loginName = $this->MemberVeri()['loginName'];
        if (empty($loginName)) if (I("apiAll") == 1) {
//            return array('code'=>1, 'msg'=>"参数不全");
            return returnData(null, -1, 'error', '参数不全');
        } else {
//            $this->ajaxReturn(array('code'=>1, 'msg'=>"参数不全"));
            $this->ajaxReturn(returnData(null, -1, 'error', '参数不全'));
        }//返回方式处理

        $m = D('V3/Api');
        $list = $m->getCartInfo($loginName);
        $list = returnData($list);

        if (I("apiAll") == 1) {
            return array('code' => 0, 'list' => $list);
        } else {
            $this->ajaxReturn(array('code' => 0, 'list' => $list));
        }//返回方式处理
    }

    /**
     * 新增购物车（弃用）
     */
    public function addCart()
    {
        $loginName = $this->MemberVeri()['loginName'];
        if (empty($loginName)) if (I("apiAll") == 1) {
//            return array('code'=>1, 'msg'=>"参数不全");
            return returnData(null, -1, 'error', '参数不全');
        } else {
//            $this->ajaxReturn(array('code'=>1, 'msg'=>"参数不全"));
            $this->ajaxReturn(returnData(null, -1, 'error', '参数不全'));
        }//返回方式处理

        $goodsId = I('goodsId', 0, 'intval');
        $goodsCnt = I('goodsCnt', 0, 'trim');
        $goodsAttrId = I('goodsAttrId', 0, 'intval');

        //$m = D('V3/Api');
        $m = new ApiModel();
        $result = $m->addToCart($loginName, $goodsId, $goodsCnt, $goodsAttrId);
        $result = returnData($result);


        if (I("apiAll") == 1) {
            return array('code' => $result);
        } else {
            $this->ajaxReturn(array('code' => $result));
        }//返回方式处理
    }

    /**
     * 取消拼团订单
     * @param int orderId 订单id
     */
    public function assembleOrderCancel()
    {
        $userId = $this->MemberVeri()['userId'];
        $orderId = I('orderId', '', 'trim');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = D('V3/Assemble');
        $res = $m->assembleOrderCancel($userId, $orderId);
        $this->ajaxReturn($res);
    }

    /**
     * 猜你喜欢
     * 拼团
     * //多门店业务参数
     * @param int shopId 店铺id
     * @param int num 读取数量(默认为10)
     * //多商户业务参数
     * @param int num 读取数量(默认为10)
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     * @return array
     */
    public function assembleGuessYouLike()
    {
//        $userId = $this->getMemberInfo()['userId'];
        $m = D('V3/Assemble');
        $shopId = I('shopId', 0, 'intval');
        $num = I('num', 10, 'intval');
        $adcode = I('adcode');
        $lng = I('lng');
        $lat = I('lat');
        $res = $m->getAssembleGuessYouLikeGoods($shopId, $num, $adcode, $lat, $lng);
        $this->ajaxReturn($res);
    }

    // ===============================================================
    // ====================== - 拼团活动 - end - ===================
    // ===============================================================


    /**
     * 附近社区
     */
    public function nearCommunitys()
    {
        $areaId3 = I('adcode');
        $user_lat = I('lat');
        $user_lng = I('lng');
        $page = I('page', 1);

        $areas = D('V3/Api');
        $areaList = $areas->getDistricts($areaId3, $page);

        for ($i = 0; $i <= count($areaList) - 1; $i++) {
            $z_lat_lng[$i] = $areas->getDistanceBetweenPointsNew($user_lat, $user_lng, $areaList[$i]['latitude'], $areaList[$i]['longitude']);
            $areaList[$i]['distance'] = sprintf("%.2f", $z_lat_lng[$i]['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($areaList as $user) {
            $shopsDataSort[] = $user['distance'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $areaList);//从低到高排序

        if (!empty($areaList)) {
            foreach ($areaList as $k => $v) {
                $areaList[$k]['communityName'] = $areas->getCommunity($v['communityId']);
            }
        }

        if (I("apiAll") == 1) {
            return array('code' => 0, 'areaList' => $areaList);
        } else {
            $this->ajaxReturn(array('code' => 0, 'areaList' => $areaList));
        }//返回方式处理
    }

    /**
     * 手机验证码登陆
     * */
//    public function account()
//    {
//        $resquest = I();
//        $data = [];
//        !empty($resquest['userPhone']) ? $data['userPhone'] = $resquest['userPhone'] : false;
//        !empty($resquest['smsCode']) ? $data['smsCode'] = $resquest['smsCode'] : false;
//        !empty($resquest['userName']) ? $data['userName'] = $resquest['userName'] : $data['userName'] = '未设置昵称';
//        !empty($resquest['userPhoto']) ? $data['userPhoto'] = $resquest['userPhoto'] : $data['userPhoto'] = 'http://' . $GLOBALS['_SERVER']['HTTP_HOST'] . '/wxImgs/photo.jpg';
//        if (empty($data['userPhone']) || empty($data['smsCode'])) {
////            $apiRet['apiCode'] = -1;
////            $apiRet['apiInfo'] = '字段有误';
////            $apiRet['apiState'] = 'error';
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
//        $m = D("V3/Api");
//        $res = $m->account($data);
//
//        if (I("apiAll") == 1) {
//            return $res;
//        } else {
//            $this->ajaxReturn($res);
//        }//返回方式处理
//    }

    /**
     *手机短信验证码登陆
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/bgl7bo
     * */
    public function account()
    {
        $request_params = I();
        $params = array(
            'userPhone' => null,
            'smsCode' => null,
            'userName' => null,
            'userPhoto' => null,
            'InvitationID' => 0,
        );
        parm_filter($params, $request_params);
        if (empty($params['userName'])) {
            $params['userName'] = '未设置昵称';
        }
        if (empty($params['userPhoto'])) {
            $params['userPhoto'] = 'http://' . $GLOBALS['_SERVER']['HTTP_HOST'] . '/wxImgs/photo.jpg';
        }
        if (empty($params['userPhone']) || empty($params['smsCode'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '字段有误'));
        }
        $model = new ApiModel();
        $result = $model->account($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $result_data = $result['data'];
        $this->ajaxReturn(responseSuccess($result_data));
    }

    /**
     * 获取手机验证码 PS:用于手机验证码登陆
     * @param varchar $phone
     */
    public function acountSmsVerify()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '短信发送失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '短信发送失败');
        $phone = WSTAddslashes(I("userPhone"));
        if (empty($phone)) {
//            $apiRet['apiInfo']='字段有误';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $phone)) {
//            $apiRet['apiInfo']='手机号格式不正确';
            $apiRet = returnData(null, -1, 'error', '手机号格式不正确');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $phoneVerify = mt_rand(100000, 999999);
        $msg = "您正在获取登陆验证码，验证码为:" . $phoneVerify . "，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0, $phone, $msg, 'acountSmsVerify', $phoneVerify);
        $rv['time'] = 30 * 60;
        if ($rv['status'] != 1) {
            $msg = !empty($rv['msg']) ? $rv['msg'] : '短信发送失败';
            $apiRet = returnData(null, -1, 'error', $msg);
            $this->ajaxReturn($apiRet);
        }
        $rv = returnData($rv);
        if (I("apiAll") == 1) {
            return $rv;
        } else {
            $this->ajaxReturn($rv);
        }//返回方式处理
    }

    /**
     * 设置默认地址
     * @param int addressId
     */
    public function setDefaultAddress()
    {
        $userId = $this->MemberVeri()['userId'];
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '操作失败');

        $addressId = I('addressId');
        if (empty($addressId)) {
//            $apiRet['apiInfo'] = '字段有误';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $request['userId'] = $userId;
        $request['addressId'] = $addressId;
        $m = D("V3/Api");
        $res = $m->setDefaultAddress($request);
//        $res = returnData($res);

        if (I("apiAll") == 1) {
            return $res;
        } else {
            $this->ajaxReturn($res);
        }//返回方式处理
    }

    /**
     * 团长申请
     * @param string name
     * @param string address
     * @param string groupName
     * @param string mobile
     * @param string wxNumber
     */
    public function submitGroup()
    {
        //$userId = $this->MemberVeri()['userId'];
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '操作失败');
        $request = I();
        $data = [];
        !empty($request['name']) ? $data['name'] = $request['name'] : false;
        !empty($request['address']) ? $data['address'] = $request['address'] : false;
        !empty($request['groupName']) ? $data['groupName'] = $request['groupName'] : false;
        !empty($request['mobile']) ? $data['mobile'] = $request['mobile'] : false;
        !empty($request['wxNumber']) ? $data['wxNumber'] = $request['wxNumber'] : false;
        if (empty($data['name']) || empty($data['address']) || empty($data['groupName']) || empty($data['mobile'])) {
//            $apiRet['apiInfo'] = '字段有误';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $data['addTime'] = date("Y-m-d H:i:s", time());
        $data['updateTime'] = date("Y-m-d H:i:s", time());
        $m = D("V3/Api");
        $res = $m->submitGroup($data);
//        $res = returnData($res);
        if (I("apiAll") == 1) {
            return $res;
        } else {
            $this->ajaxReturn($res);
        }//返回方式处理
    }

    // =========================================================
    // ========== 类似美团一样的购买会员机制  - start ==========
    // =========================================================

    /**
     * 会员套餐列表
     */
    public function setmealList()
    {

        $m = D('V3/Api');
        //原接口
//        $result = $m->getSetmealList();
        //修改后接口
        $result = $m->getSetmealcouponsList();
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 购买会员套餐
     */
    public function buySetmeal()
    {
        $user = $this->MemberVeri();
//        $user = array('userId'=>I('userId',0,'intval'));
        $smId = I('smId', 0, 'intval');

        if (empty($user) || empty($smId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            $apiRet['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->buySetmeal($user, $smId);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 获取抢购加量包列表
     */
    public function couponsetList()
    {

        $m = D('V3/Api');
        $result = $m->getCouponsetList();
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 购买抢购加量包
     */
    public function buyCouponset()
    {
        $user = $this->MemberVeri();
        $csId = I('csId', 0, 'intval');

        if (empty($user) || empty($csId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            $apiRet['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->buyCouponset($user, $csId);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 店铺优惠券
     */
    public function shopCouponList()
    {
        $user = $this->MemberVeri();
        $shopId = I('shopId', 0, 'intval');

        if (empty($user) || empty($shopId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            $apiRet['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->getShopCouponList($shopId);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 兑换店铺券时，商城券和店铺券的显示
     */
    public function exchangeCouponInfo()
    {
        $user = $this->MemberVeri();
        $couponId = I('couponId', 0, 'intval');

//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '操作失败');

        if (empty($user) || empty($couponId)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //判断会员是否已过期
        if ($user['expireTime'] < date('Y-m-d H:i:s')) {
//            $apiRet['apiInfo'] = '当前会员已过期';
            $apiRet = returnData(null, -1, 'error', '当前会员已过期');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $shopCouponInfo = $m->getShopCouponInfo($couponId);
        $shopCouponInfo = returnData($shopCouponInfo);

        if (empty($shopCouponInfo)) if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理

        $commonCouponInfo = $m->getCommonCouponInfo(array('userId' => $user['userId'], 'couponMoney' => $shopCouponInfo['commonCouponMoney']));
//        $commonCouponInfo = returnData($commonCouponInfo);

        if ($commonCouponInfo['apiCode'] == -1) {
            if (I("apiAll") == 1) {
                return $commonCouponInfo;
            } else {
                $this->ajaxReturn($commonCouponInfo);
            }//返回方式处理
        }
        $commonCouponInfos = $commonCouponInfo['apiData'];

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = array(
//            'shopCouponInfo' => $shopCouponInfo,
//            'commonCouponInfo' => $commonCouponInfos
//        );
        $apiRet = returnData(array(
            'shopCouponInfo' => $shopCouponInfo,
            'commonCouponInfo' => $commonCouponInfos
        ));


        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 兑换门店优惠券
     */
    public function exchangeShopCoupon()
    {
        $user = $this->MemberVeri();
        $shopCouponId = I('shopCouponId', 0, 'intval');
        $id = I('id', 0, 'intval');

//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '操作失败');

        if (empty($user) || empty($shopCouponId) || empty($id)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //判断会员是否已过期
        if ($user['expireTime'] < date('Y-m-d H:i:s')) {
//            $apiRet['apiInfo'] = '当前会员已过期';
            $apiRet = returnData(null, -1, 'error', '当前会员已过期');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->exchangeShopCoupon($shopCouponId, $id);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 购买会员记录
     */
    public function buyUserRecord()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');

        if (empty($userId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            $apiRet['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');

        $result = $m->getBuyUserRecord($userId, $page, $pageSize);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 优惠券使用记录
     */
    public function useCouponRecord()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');

        if (empty($userId)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数不全';
//            $apiRet['apiState'] = 'error';
//            $apiRet['apiData'] = null;
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->getUseCouponRecord($userId, $page, $pageSize);
//        $result = returnData($result);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 附近商家兑换券
     */
    public function nearShopCoupon()
    {
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId',0,'intval');
        $areaId3 = I('adcode');
        $user_lat = I('lat');
        $user_lng = I('lng');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');

//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '获取数据失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(array(), -1, 'error', '获取数据失败');
        if (empty($userId) || empty($areaId3)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(array(), -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $areas = D('V3/Api');
        $result = $areas->getNearShopCouponList($userId, $areaId3, $user_lat, $user_lng, $page, $pageSize);


//        if (!$result) if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '获取数据成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $result;
        $result = empty($result) ? array() : $result;
        $apiRet = returnData((array)$result);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 会员专享券列表
     */
    public function userCouponList()
    {
        $m = D('V3/Api');
        $list = $m->getUserCouponList();

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '获取数据成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $list = empty($list) ? array() : $list;
        $apiRet = returnData($list);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    // =========================================================
    // ========== 类似美团一样的购买会员机制  - end ==========
    // =========================================================

    /**
     * 获取充值金额配置列表
     */
    public function rechargesetList()
    {
        $m = D('V3/Api');
        $list = $m->getRechargesetList();

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '获取数据成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $list = empty($list) ? array() : $list;
        $apiRet = returnData($list);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 获取充值规则配置
     */
    public function getRechargeRules()
    {
        $data = $GLOBALS['CONFIG']['rechargeRules'];
        $apiRet = returnData($data);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 接受邀请
     */
    public function acceptInvite()
    {
        $inviterId = I('inviterId', 0, 'intval');
        $phone = I('phone', '', 'trim');
//        $sessionPhone = session('VerifyCode_userPhone');
//        $verify = session('VerifyCode_userPhone_verify');
//        $startTime = (int)session('VerifyCode_userPhone_Time');
        $sessionPhone = S('VerifyCode_userPhone_' . $phone);
        $verify = S('VerifyCode_userPhone_verify_' . $phone);
        $startTime = (int)S('VerifyCode_userPhone_Time_' . $phone);
        $mobileCode = I("mobileCode");

//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = '';
        $apiRet = returnData(null, -1, 'error', '操作失败');

        if (empty($inviterId) || empty($phone) || empty($mobileCode)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //有效时间为30分钟
        if ((time() - $startTime) > 1800) {
//            $apiRet['apiInfo'] = '短信验证码已失效';
            $apiRet = returnData(null, -1, 'error', '短信验证码已失效');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        if ($verify != $mobileCode || $sessionPhone != $phone) {
//            $apiRet['apiInfo'] = '手机号或短信验证码错误';
            $apiRet = returnData(null, -1, 'error', '手机号或短信验证码错误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D('V3/Api');
        $result = $m->doAcceptInvite($inviterId, $phone);
//        $result = returnData($result);

        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 获取手机验证码 PS:用于面对面邀请
     * @param varchar $phone
     */
    public function getPhoneInviteVerify()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '短信发送失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '短信发送失败');

        $phone = WSTAddslashes(I("userPhone"));
        if (empty($phone)) {
//            $apiRet['apiInfo']='字段有误';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if (!preg_match("/^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$/", $phone)) {
//            $apiRet['apiInfo']='手机号格式不正确';
            $apiRet = returnData(null, -1, 'error', '手机号格式不正确');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $phoneVerify = mt_rand(100000, 999999);
        $msg = "您正在获取验证码，验证码为:" . $phoneVerify . "，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0, $phone, $msg, 'getPhoneInviteVerify', $phoneVerify);
        $rv['time'] = 30 * 60;
//        $rv['phoneVerify'] = $phoneVerify;

        if ($rv['status'] == 1) {
//            session_start();
//            session('VerifyCode_userPhone',$phone);
//            session('VerifyCode_userPhone_verify',$phoneVerify);
//            session('VerifyCode_userPhone_Time',time());
            //$rs["phoneVerifyCode"] = $phoneVerify;
            S('VerifyCode_userPhone_' . $phone, $phone, 1800);
            S('VerifyCode_userPhone_verify_' . $phone, $phoneVerify, 1800);
            S('VerifyCode_userPhone_Time_' . $phone, time(), 1800);
        }
        if (I("apiAll") == 1) {
            return $rv;
        } else {
            if ($rv['status'] == 1) {
                $rv = returnData($rv);
            } else {
                $rv = returnData(null, -1, 'error', $rv['msg']);
            }
            $this->ajaxReturn($rv);
        }
    }

    /**
     * 获取限时
     * */
    public function getFlashSale()
    {
        $m = D("V3/Api");
        $mod = $m->getFlashSale();
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取店铺限时商品 - 不可弃用---老项目在使用 不可删除
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * @param int page 页码
     * @param int pageSize 页码条数
     * */
//    public function getShopFlashSaleGoods()
//    {
//        $userId = $this->getMemberInfo()['userId'];
//        $shopId = I('shopId');
//        $flashSaleId = I('flashSaleId');
//        if (empty($shopId)) {
//            // $apiRet['apiCode']=-1;
//            // $apiRet['apiInfo']='字段有误';
//            // $apiRet['apiState']='error';
//
//
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//
//
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
//        $page = I('page', 1);
//        $pageDataNum = I('pageSize', 15);
//        $m = D("V3/Api");
//        $mod = $m->getShopFlashSaleGoods($userId, $shopId, $flashSaleId, $page, $pageDataNum);
//
//        $mod = returnData($mod);
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }

    /**
     * 获取限时商品
     * 多商户-传递adcode
     * 前置仓-shopId
     * */
    public function getShopFlashSaleGoods()
    {
        $this->getMemberInfo()['userId'];
        $shopId = I('shopId', 0);
        $areaId3 = I('adcode', 0);
        $flashSaleId = I('flashSaleId');
        if ((empty($shopId) && empty($areaId3)) || empty($flashSaleId)) {
            $apiRet = returnData(null, -1, 'error', '数据不合法');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopFlashSaleGoods($shopId, $flashSaleId, $areaId3);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商城限时商品
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * @param int page 页码
     * @param int pageDataNum 页码条数
     * */
    public function getMallFlashSaleGoods()
    {
        $userId = $this->getMemberInfo()['userId'];
        $flashSaleId = I('flashSaleId');
        $adcode = I('adcode');
        $lng = I('lng');
        $lat = I('lat');
        if (empty($flashSaleId) || empty($adcode) || empty($lat) || empty($lng)) {
            // $apiRet['apiCode']=-1;
            // $apiRet['apiInfo']='字段有误';
            // $apiRet['apiState']='error';


            $apiRet = returnData(null, -1, 'error', '字段有误');


            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $page = I('page', 1);
        $pageDataNum = I('pageDataNum', 15);
        $m = D("V3/Api");
        $mod = $m->getMallFlashSaleGoods($userId, $adcode, $lat, $lng, $flashSaleId, $page, $pageDataNum);

        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }


    /**
     * 获取商城限时商品 ----老项目在用不可删除
     * @param int flashSaleId wst_flash_sale表id
     * @param int adcode PS:等同于wst_shops_communitys.areaId3
     * @param int limit 获取商品条数
     * */
    public function getFlashSaleGoods_backup()
    {
        $userId = $this->getMemberInfo()['userId'];
        $areaId3 = I('adcode');
        $flashSaleId = I('flashSaleId');
        $limit = I('limit', 15);
        if (empty($areaId3)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getFlashSaleGoods($userId, $areaId3, $flashSaleId, $limit);
//        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商城限时商品-多多商户-不传递shopId
     * 不可弃用----老项目在用
     * */
    public function getFlashSaleGoods()
    {
        $userId = $this->getMemberInfo()['userId'];

        $areaId3 = I('adcode');
        $flashSaleId = I('flashSaleId');

        if (empty($flashSaleId) || empty($areaId3)) {
            $apiRet = returnData(null, -1, 'error', '数据不合法');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getFlashSaleGoods($areaId3, $flashSaleId);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

//    /*
//     * 获取店铺限购商品----不要删除--老项目在用
//     * @param int shopId
//     * @param int page 页码
//     * @param int catId 店铺一级分类id
//     * @param int pageSize 分页条数
//     * */
//    public function getShopLimitBuyGoods()
//    {
//        $userId = $this->getMemberInfo()['userId'];
//        $shopId = I('shopId');
//        if (empty($shopId)) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
//        $page = I('page', 1);
//        $pageDataNum = I('pageSize', 15);
//        $catId = I('catId', 0);//0代表全部
//        $m = D("V3/Api");
//        $mod = $m->getShopLimitBuyGoods($userId, $shopId, $page, $pageDataNum, $catId);
////        $mod = returnData($mod);
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }
    /**
     * @return mixed
     * 获取限量购商品列表
     * 前置仓/多商户
     * shopID/adcode
     * 商户id/区县id
     */
    public function getShopLimitBuyGoods()
    {
        $this->getMemberInfo()['userId'];
        $shopId = I('shopId', 0);
        $areaId3 = I('adcode', 0);
        if (empty($shopId) && empty($areaId3)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopLimitBuyGoods($shopId, $areaId3);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取店铺限购商品分类
     * @param int shopId
     * */
    public function getShopLimitBuyGoodsCat()
    {
        $shopId = I('shopId');
        if (empty($shopId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopLimitBuyGoodsCat($shopId);
//        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商城限购商品---不要删除--老项目在用
     * @param int adcode PS:等同于wst_shops_communitys.areaId3
     * @param int page 分页
     * @param int pageDataNum 分页条数
     * @param int catId 商城一级分类id
     * */
//    public function getLimitBuyGoods()
//    {
//        $userId = $this->getMemberInfo()['userId'];
//        $areaId3 = I('adcode');
//        if (empty($areaId3)) {
////            $apiRet['apiCode']=-1;
////            $apiRet['apiInfo']='字段有误';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '字段有误');
//
//            if (I("apiAll") == 1) {
//                return $apiRet;
//            } else {
//                $this->ajaxReturn($apiRet);
//            }//返回方式处理
//        }
//        $page = I('page', 1);
//        $pageDataNum = I('pageDataNum', 15);
//        $catId = I('catId', 0);
//        $m = D("V3/Api");
//        $mod = $m->getLimitBuyGoods($userId, $areaId3, $page, $pageDataNum, $catId);
////        $mod = returnData($mod);
//
//        if (I("apiAll") == 1) {
//            return $mod;
//        } else {
//            $this->ajaxReturn($mod);
//        }//返回方式处理
//    }
    /**
     * @return mixed
     * 多商户获取限量购商品列表
     * 不可弃用---老项目在使用
     */
    public function getLimitBuyGoods()
    {
        $this->getMemberInfo()['userId'];
        $areaId3 = I('adcode');
        if (empty($areaId3)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getLimitBuyGoods($areaId3);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商城限购商品分类
     * @param int adcode PS:等同于wst_shops_communitys.areaId3
     * */
    public function getLimitBuyGoodsCat()
    {
        $areaId3 = I('adcode');
        if (empty($areaId3)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("Weimendian/Api");
        $mod = $m->getLimitBuyGoodsCat($areaId3);
//        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 订单状态下的数量
     * */
    public function getOrderStateNum()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        //$userId = 46;
        $mod = $m->getOrderStateNum($userId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 根据 社区id 来获取附近商家
     */
    public function nearShopByCommunityId()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = '';
        $apiRet = returnData(null, -1, 'error', '操作失败');
        $communityId = I('communityId', 0, 'intval');
        if (empty($communityId)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $param = array(
            'communityId' => $communityId,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $areas = D('V3/Api');
        $list = $areas->nearShopByCommunityId($param);

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $apiRet = returnData($list);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 会员专享商品列表
     */
    public function membershipExclusiveGoodsList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = '';
        $apiRet = returnData(array(), -1, 'error', '操作失败');

        $shopId = I('shopId', 0, 'intval');
        /*if (empty($shopId)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(array(),-1,'error','参数不全');

            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }*/
        $adcode = I('adcode');
        $lng = I('lng');
        $lat = I('lat');

        $where = array(
            'isMembershipExclusive' => 1,
            'isSale' => 1,
            'goodsFlag' => 1
        );
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');

        $areas = D('V3/Api');
        $list = rankGoodsPrice($areas->getMembershipExclusiveGoodsList($where, $page, $pageSize, $shopId, $adcode, $lng, $lat));

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $apiRet = returnData($list);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     *防止商户订单号重复
     * @param string str
     * */
    public function createStr()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '操作失败');

        $str = I('str');
        $uniqid = md5(uniqid());
        $token = md5($uniqid . mt_rand(1, 1000000));
        $add['orderToken'] = $token;
        $add['value'] = $str;
        $add['createTime'] = time();
        $res = M('order_merge')->add($add);
        if ($res) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '操作成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['orderToken'] = $add['orderToken'];
            $apiRet = returnData($add['orderToken']);

        }
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * (分销)已接受邀请记录
     */
    public function distributionInvitationAccepted()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $areas = D('V3/Api');
        $data = $areas->getDistributionInvitationAccepted($userId, $page, $pageSize);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    //微信小程序一键登录 仅限于微信
    public function wechatMiniProgramLogin()
    {
        //接收参数
        $userPostData['code'] = I('code');
        $userPostData['userName'] = I('userName', '未设置昵称');
        $userPostData['userPhoto'] = I('userPhoto');//不传就不设置了

        //备参
        $code = $userPostData['code'];
        $encryptedData = I('encryptedData');
        $iv = I('iv');
        $userName = $userPostData['userName'];
        $userPhoto = $userPostData['userPhoto'];
        // $user_Phone = ;//用户手机号
        //校验参数
        if (empty($code) || empty($encryptedData) || empty($iv)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }

        //通过微信 先获取 部分参数
        $UserModel = new \V3\Model\UserModel();
        $users_module = new UsersModule();

        $weiResData = $UserModel->getWechatUserInfo($code);//unionid session_key...

        //远程解析unionid 是否用户已存在 可直接登陆
        if (!empty($weiResData['unionid'])) {
            unset($userData);
            $userData = $UserModel->isUnionid($weiResData['unionid']);
            if (!empty($userData['userId'])) {
                $UserModel->UserLogInfo($userData['userId']);//记录登陆日志
                $save_data = array(
                    'userPhoto' => $userPhoto,
                    // 'userName' => $userName,//不更新名称
                );
                $users_module->updateUsersInfo($userData['userId'], $save_data);//更新用户头像和用户名
                $ret = returnData($UserModel->login($userData['userId']));//完成登陆
                $this->ajaxReturn($ret);
            }
        }

        if (empty($weiResData['session_key'])) {//至少要解析出该参数 否则下一步无法进行解密
            unset($apiRet);
            $apiRet = returnData(null, -1, 'error', '登陆异常，请联系平台人员！', 'session_key无法获取，可能配置异常');
            $this->ajaxReturn($apiRet);
        }

        //本地解密获取unionid 是否用户存在 存在可直接登陆  可能这里的数据永远不会包含这个字段 为了程序稳定 先写在这 不影响
        $WXBizDataCrypt_Data = $UserModel->WXBizDataCrypt($encryptedData, $iv, $weiResData['session_key']);
        if (!empty($WXBizDataCrypt_Data['unionid'])) {
            unset($userData);
            $userData = $UserModel->isUnionid($WXBizDataCrypt_Data['unionid']);
            if (!empty($userData['userId'])) {
                $UserModel->UserLogInfo($userData['userId']);//记录登陆日志
                $save_data = array(
                    'userPhoto' => $userPhoto,
                    // 'userName' => $userName,//不更新名称
                );
                $users_module->updateUsersInfo($userData['userId'], $save_data);//更新用户头像和用户名
                $ret = returnData($UserModel->login($userData['userId']));//unionid存在完成登陆
                $this->ajaxReturn($ret);
            }

        }


        //根据解密出的手机号 是否用户存在 存在可直接登陆
        if (!empty($WXBizDataCrypt_Data['purePhoneNumber'])) {
            unset($userData);
            $userData = $UserModel->isUserLoginName($WXBizDataCrypt_Data['purePhoneNumber']);
            if (!empty($userData['userId'])) {
                $UserModel->UserLogInfo($userData['userId']);//记录登陆日志
                $save_data = array(
                    'userPhoto' => $userPhoto,
                    // 'userName' => $userName,//不更新名称
                );
                $users_module->updateUsersInfo($userData['userId'], $save_data);//更新用户头像和用户名
                $ret = returnData($UserModel->login($userData['userId']));//完成登陆
                $this->ajaxReturn($ret);
            }
        }


        //未注册的手机号 通过解密获取手机号 等信息 进行注册
        $regData['loginName'] = $WXBizDataCrypt_Data['purePhoneNumber'];
        $regData['userName'] = $userName;
        $regData['userPhoto'] = $userPhoto;
        $regData['openId'] = $WXBizDataCrypt_Data['openId'];
        $regData['WxUnionid'] = $WXBizDataCrypt_Data['unionid'];
        $regData['userPhone'] = $WXBizDataCrypt_Data['purePhoneNumber'];

        unset($userId);
        $userId = $UserModel->reg($regData);
        //对已注册的新人进行登陆 返回token
        if (!empty($userId)) {
            //注册成功后
            $UserModel->UserPushInfo($userId, 4);//注册成功推送
//            $UserModel->UserLogInfo($userId);//记录登陆日志 --前端调用的是 addUsersLoginLog ，当前这个去掉，重复了
            $UserModel->InvitationFriend($userId);//邀请好友奖励
            $UserModel->FunNewPeopleGift($userId);//新人专享大礼
            $UserModel->InvitationFriendSetmeal($userId); //邀请好友开通会员送券
            $UserModel->distributionRelation($userId);//写入分销与地推关系
            //完成登陆
            $ret = returnData($UserModel->login($userId));
            $this->ajaxReturn($ret);
        } else {
            $ret = returnData(null, -1, 'error', '登陆失败');
            $this->ajaxReturn($ret);
        }


        //本接口注册 不考虑unionid 可能注册之后 没有unionid  其他接口可能要直接绑定unionid 而不是绑定手机号 所以这个在其他接口需要自动帮助用户绑定unionid

        //别的微信绑定接口 应该还是要 满足这俩参数 毕竟这边不一定可能就会存在这个参数 $regData['openId'] = $WXBizDataCrypt_Data['openId'];  $regData['WxUnionid'] = $WXBizDataCrypt_Data['unionid'];


    }

    /* 用户登陆 和注册  --- 小程序【弃用了】 --- */
    public function xcxUserLogin()
    {

        /*
        可自己解密数据 获取用户信息
        import('Vendor.WXBizDataCrypt.WXBizDataCrypt');
        $pc = new \WXBizDataCrypt($appid, $sessionKey); */
        //$userPostData = file_get_contents('php://input');
        //$userPostData= json_decode($userPostData,true);
        $userPostData['code'] = I('code');
        $userPostData['userName'] = I('userName');
        $userPostData['userPhoto'] = I('userPhoto');
        $userPostData['userPhone'] = I('userPhone');
        $userPostData['loginPwd'] = I('loginPwd');
        $userPostData['smsCode'] = I('smsCode');

        //if(I("apiAll") == 1){return $userPostData;}else{$this->ajaxReturn($userPostData);}//返回方式处理
        /* 	$code = I('code');
            $userName = I('userName');
            $userPhoto = I('userPhoto'); */

        $code = $userPostData['code'];
        $encryptedData = I('encryptedData');
        $iv = I('iv');

        $userName = $userPostData['userName'];
        $userPhoto = $userPostData['userPhoto'];
        $user_Phone = $userPostData['userPhone'];//用户手机号
        $user_loginPwd = $userPostData['loginPwd'];//用户密码 未加密
        $user_smsCode = $userPostData['smsCode'];//短信验证码

        //if(I("apiAll") == 1){return $code.$userName.$userPhoto;}else{$this->ajaxReturn($code.$userName.$userPhoto);}//返回方式处理

        if (empty($userName)) {
            $userName = '未设置昵称';
        }
        if (empty($userPhoto)) {
            $userPhoto = 'http://' . $GLOBALS['_SERVER']['HTTP_HOST'] . '/wxImgs/photo.jpg';//获取域名拼接
        }

        if (empty($code) || empty($userName) || empty($userPhoto)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=$userPostData;
            $apiRet = returnData($userPostData, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $appid = $GLOBALS["CONFIG"]["xiaoAppid"];
        $secret = $GLOBALS["CONFIG"]["xiaoSecret"];

        $weiResData = curlRequest("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code", '', false, 1);

        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理

        $weiResData = json_decode($weiResData, true);
//   prt($weiResData);
        $modUsers = M('users');

        //判断是否存在 如果存在直接登录------------------------------代码与一下代码有重复 后期需要优化
        if (!empty($weiResData['unionid']) and empty($encryptedData) or empty($iv)) {

            //登陆生成token

            $where['WxUnionid'] = $weiResData['unionid'];
            $where['userFlag'] = 1;

            $modUsersData = $modUsers->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'openId', 'userPhone'))->find();//再次获取用户所有字段

            if (empty($modUsersData)) {

//                $apiRet['apiCode']='000080';
//                $apiRet['apiInfo']='请绑定手机号或绑定手机号时参数携带错误';
//                $apiRet['apiState']='error';
                $apiRet = returnData(null, '000080', 'error', '请绑定手机号');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }

            //记录登录日志

            $User = M("log_user_logins");
            $data = array();
            $data["userId"] = $modUsersData['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            $data["loginSrc"] = 3;
            $User->add($data);

            $logdata['lastIP'] = get_client_ip();
            $logdata['lastTime'] = date('Y-m-d H:i:s');
            $modUsers->where("userId = '{$modUsersData['userId']}'")->save($logdata);

            //生成用唯一token
            $memberToken = md5(uniqid('', true) . $code . $modUsersData['userId'] . $modUsersData['loginName'] . (string)microtime());
            if (!userTokenAdd($memberToken, $modUsersData)) {
//                $apiRes['apiCode'] = -1;
//                $apiRes['apiInfo'] = '登陆失败';
//                $apiRes['apiState'] = 'error';
//                $apiRes['apiData'] = null;
                $apiRes = returnData(null, -1, 'error', '登陆失败');
                if (I("apiAll") == 1) {
                    return $apiRes;
                } else {
                    $this->ajaxReturn($apiRes);
                }//返回方式处理
            }

            $modUsersData['memberToken'] = $memberToken;

//            $apiRes['apiCode'] = '111111';
//            $apiRes['apiInfo'] = '登陆成功';
//            $apiRes['apiState'] = 'success';
//            $apiRes['apiData'] = $modUsersData;
            $apiRes = returnData($modUsersData);

            if (I("apiAll") == 1) {
                return $apiRes;
            } else {
                $this->ajaxReturn($apiRes);
            }//返回方式处理
        }
        if (empty($weiResData['unionid'])) {
            if (empty($encryptedData) or empty($iv)) {
//                $apiRet['apiCode']=-1;
//                $apiRet['apiInfo']='iv或encryptedData 字段有误';
//                $apiRet['apiState']='error';
//                $apiRet['apiData']=$userPostData;
                $apiRet = returnData($userPostData, -1, 'error', 'iv或encryptedData 字段有误');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }

            //如果返回空的 就进行解密获取
            import('Vendor.WXBizDataCrypt.WXBizDataCrypt');

            $sessionKey = $weiResData['session_key'];

            $pc = new \WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $redata);
            // 	        $myfile = fopen("hhhhhhhhhhhhhhheeeeeeeeeeeee.txt", "a+") or die("Unable to open file!");

            // 			fwrite($myfile, "errorcode:$errCode # resdata: $redata \r\n");
            // 			fclose($myfile);

            if ($errCode == 0) {

                // 		     $myfile = fopen("hhhhhhhhhhhhhhheeeeeeeeeeeee.txt", "a+") or die("Unable to open file!");

                // 			fwrite($myfile, "恭喜我要解析啦 \r\n");
                // 			fclose($myfile);

                //解析出unionid
                $weiResData = json_decode($redata, true);

            } else {

                // 		    $myfile = fopen("hhhhhhhhhhhhhhheeeeeeeeeeeee.txt", "a+") or die("Unable to open file!");

                // 			fwrite($myfile, "你咋瞎几把走 \r\n");
                // 			fclose($myfile);

                $apiRet = array();
//                $apiRet['apiCode']=-1;
//                $apiRet['apiInfo']='解析unionid---errCode';
//                $apiRet['apiState']='error';
//                $apiRet['apiData']=$errCode;
                $apiRet = returnData($errCode, -1, 'error', '解析unionid---errCode');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理

            }
            if (empty($weiResData['unionId'])) {
                $apiRet = array();
//                $apiRet['apiCode']=-1;
//                $apiRet['apiInfo']='获取不到unionid,请求为空，解密为空';
//                $apiRet['apiState']='error';
//                $apiRet['apiData']=$weiResData;
                $apiRet = returnData($weiResData, -1, 'error', '获取不到unionid,请求为空，解密为空');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }

            //兼容解密后的unionId  解密后的I为大写
            $weiResData['unionid'] = $weiResData['unionId'];
            $weiResData['openid'] = $weiResData['openId'];
        }
        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理

        //检查此微信 是否绑定手机
        $modUsersIsEmpty = $modUsers->where("WxUnionid='{$weiResData['unionid']}' and loginName !=''")->find();

        //如果为空 即为注册
        if (empty($modUsersIsEmpty)) {
            //一键获取手机号登陆
            if (!empty($weiResData['phoneNumber'])) {
                $user_Phone = $weiResData['phoneNumber'];
            } else {
                //判断参数 是否齐全
                //if (empty($user_Phone) || empty($user_loginPwd) || empty($user_smsCode)) {
                if (empty($user_Phone) || empty($user_smsCode)) {
//                $apiRet['apiCode']='000080';
//                $apiRet['apiInfo']='请绑定手机号或绑定手机号时参数携带错误';
//                $apiRet['apiState']='error';

                    // $apiRet = returnData(null,-1,'error','请绑定手机号或绑定手机号时参数携带错误');
                    $apiRet = returnData(null, '000080', 'error', '请绑定手机号');
                    if (I("apiAll") == 1) {
                        return $apiRet;
                    } else {
                        $this->ajaxReturn($apiRet);
                    }//返回方式处理
                }

                //校验短信验证码
                if ($user_smsCode != S("app_reg_mobileNumber_{$user_Phone}")) {
//                $apiRet['apiCode']='000082';
//                $apiRet['apiInfo']='验证码错误！';
//                $apiRet['apiState']='error';
                    $apiRet = returnData(null, -1, 'error', '验证码错误！');
                    if (I("apiAll") == 1) {
                        return $apiRet;
                    } else {
                        $this->ajaxReturn($apiRet);
                    }//返回方式处理
                } else {
                    S("app_reg_mobileNumber_{$user_Phone}", null);
                }
            }


            //判断当前手机号是否已被注册 如果已被注册 则进行直接绑定微信
            $userPhoneIsEmpty = $modUsers->where("loginName='{$user_Phone}'")->find();
            if (!empty($userPhoneIsEmpty)) {
                unset($data);
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['openId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;
                $modUsers->where("loginName='{$user_Phone}'")->save($data);
                /* 	$apiRet['apiCode']='000081';
                    $apiRet['apiInfo']='手机号已被绑定，请使用手机号在 pc端或者app进行登录';
                    $apiRet['apiState']='error';
                    if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理 */
            } else {
                unset($data);
                $data['loginName'] = $user_Phone;
                $data['loginSecret'] = rand(1000, 9999);
                //$data['loginPwd'] = md5($user_loginPwd . $data['loginSecret']);//账号密码登陆已废弃
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['createTime'] = date('Y-m-d H:i:s');
                $data['openId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;
                $data['firstOrder'] = 1;
                $add_is_ok_id = $modUsers->add($data);

                //注册成功发送推送信息
                $push = D('Adminapi/Push');
                $push->postMessage(4, $add_is_ok_id);

                //判断是否是被邀请
                $Invitation = I('InvitationID', 0);//原始邀请人的userId
                if (!empty($Invitation)) {
                    self::InvitationFriend($Invitation, $add_is_ok_id);
                } else {
                    $inviteInfo = M('invite_cache_record')->where(array('inviteePhone' => $user_Phone, 'icrFlag' => 1))->find();
                    if (!empty($inviteInfo)) self::InvitationFriend($inviteInfo['inviterId'], $add_is_ok_id);
                }

                //新人专享大礼
                $isNewPeopleGift = self::FunNewPeopleGift($add_is_ok_id);
                self::distributionRelation($data['userPhone'], $add_is_ok_id);//写入用户分销/地推关系表
                self::InvitationFriendSetmeal($add_is_ok_id, $Invitation); //邀请好友开通会员送券
            }
        }

        //登陆生成token

        $where['WxUnionid'] = $weiResData['unionid'];
        $where['userFlag'] = 1;

        $modUsersData = $modUsers->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'openId', 'userPhone'))->find();//再次获取用户所有字段

        //判断新人专享获得的积分是否为空
        //if(!empty($isNewPeopleGift)){
        // $modUsersData['isNewPeopleGift'] = $isNewPeopleGift;
        //}

        if (empty($modUsersData)) {
            $modUsersData['isNewPeopleGift'] = $isNewPeopleGift;
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='用户被禁用，或者不存在';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '用户被禁用，或者不存在', "历史数据异常 尝试删除重新注册");
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        //记录登录日志

        $User = M("log_user_logins");
        $data = array();
        $data["userId"] = $modUsersData['userId'];
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $data["loginSrc"] = 3;
        $User->add($data);

        $logdata['lastIP'] = get_client_ip();
        $logdata['lastTime'] = date('Y-m-d H:i:s');
        $modUsers->where("userId = '{$modUsersData['userId']}'")->save($logdata);

        //生成用唯一token
        $memberToken = md5(uniqid('', true) . $code . $modUsersData['userId'] . $modUsersData['loginName'] . (string)microtime());
        if (!userTokenAdd($memberToken, $modUsersData)) {
//            $apiRes['apiCode'] = -1;
//            $apiRes['apiInfo'] = '登陆失败';
//            $apiRes['apiState'] = 'error';
//            $apiRes['apiData'] = null;
            $apiRes = returnData(null, -1, 'error', '登陆失败');
            if (I("apiAll") == 1) {
                return $apiRes;
            } else {
                $this->ajaxReturn($apiRes);
            }//返回方式处理
        }

        $modUsersData['memberToken'] = $memberToken;

//        $apiRes['apiCode'] = '111111';
//        $apiRes['apiInfo'] = '登陆成功';
//        $apiRes['apiState'] = 'success';
//        $apiRes['apiData'] = $modUsersData;
        $apiRes = returnData($modUsersData);

        if (I("apiAll") == 1) {
            return $apiRes;
        } else {
            $this->ajaxReturn($apiRes);
        }//返回方式处理
    }

    /* 用户登陆 和注册 --- 小程序【弃用了】 ---  */
    public function userLoginNew()
    {
        /*
        可自己解密数据 获取用户信息
        import('Vendor.WXBizDataCrypt.WXBizDataCrypt');
        $pc = new \WXBizDataCrypt($appid, $sessionKey); */
        //$userPostData = file_get_contents('php://input');
        //$userPostData= json_decode($userPostData,true);
        $userPostData['code'] = I('code');
        $userPostData['userName'] = I('userName');
        $userPostData['userPhoto'] = I('userPhoto');
        $user_Phonea = I('userPhoto');
        /*$userPostData['userPhone'] = I('userPhone');
        $userPostData['loginPwd'] = I('loginPwd');*/
        $userPostData['smsCode'] = I('smsCode');

        //if(I("apiAll") == 1){return $userPostData;}else{$this->ajaxReturn($userPostData);}//返回方式处理
        /* 	$code = I('code');
            $userName = I('userName');
            $userPhoto = I('userPhoto'); */

        $code = $userPostData['code'];
        $encryptedData = I('encryptedData');
        $iv = I('iv');

        //后加
        $encryptedData2 = I('encryptedData2');
        $iv2 = I('iv2');


        $userName = $userPostData['userName'];
        $userPhoto = $userPostData['userPhoto'];
//        $user_Phone = $userPostData['userPhone'];//用户手机号
//        $user_loginPwd = $userPostData['loginPwd'];//用户密码 未加密
        $user_smsCode = $userPostData['smsCode'];//短信验证码
        //if(I("apiAll") == 1){return $code.$userName.$userPhoto;}else{$this->ajaxReturn($code.$userName.$userPhoto);}//返回方式处理
        if (empty($userName)) {
            $userName = '未设置昵称';
        }
        if (empty($userPhoto)) {
            $userPhoto = 'http://' . $GLOBALS['_SERVER']['HTTP_HOST'] . '/wxImgs/photo.jpg';//获取域名拼接
        }
        /*if(empty($code) || empty($userName) || empty($userPhoto)){
            $apiRet['apiCode']=-1;
            $apiRet['apiInfo']='字段有误';
            $apiRet['apiState']='error';
            $apiRet['apiData']=$userPostData;
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }*/
        $appid = $GLOBALS["CONFIG"]["xiaoAppid"];
        $secret = $GLOBALS["CONFIG"]["xiaoSecret"];
        $weiResData = curlRequest("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code", '', false, 1);
        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理
        $weiResData = json_decode($weiResData, true);
        $modUsers = M('users');
        //判断是否存在 如果存在直接登录------------------------------代码与一下代码有重复 后期需要优化
        if (!empty($weiResData['unionid']) and empty($encryptedData) or empty($iv)) {
            $user_Phone = I('userPhone');
            /*$myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = $user_smsCode . '+++++' . S("app_reg_mobileNumber_{$user_Phone}") . '手机号' . $user_Phone;
            fwrite($myfile, "走了吗1111111：$txt \n");
            fclose($myfile);*/
            //登陆生成token
            $where['WxUnionid'] = $weiResData['unionid'];
            $where['userFlag'] = 1;
            $modUsersData = $modUsers->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'openId', 'userPhone', 'WxOpenId'))->find();//再次获取用户所有字段
            if (empty($modUsersData)) {
//                $apiRet['apiCode'] = '000080';
//                $apiRet['apiInfo'] = '请绑定手机号或绑定手机号时参数携带错误';
//                $apiRet['apiState'] = 'error';
                // $apiRet = returnData(null,-1,'error','请绑定手机号或绑定手机号时参数携带错误');
                $apiRet = returnData(null, '000080', 'error', '请绑定手机号');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }


            //记录登录日志

            $User = M("log_user_logins");
            $data = array();
            $data["userId"] = $modUsersData['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            $data["loginSrc"] = 3;
            $User->add($data);

            $logdata['lastIP'] = get_client_ip();
            $logdata['lastTime'] = date('Y-m-d H:i:s');
            if (empty($modUsersData['WxOpenId'])) $logdata['WxOpenId'] = $weiResData['openid'];
            $modUsers->where("userId = '{$modUsersData['userId']}'")->save($logdata);

            //生成用唯一token
            $memberToken = md5(uniqid('', true) . $code . $modUsersData['userId'] . $modUsersData['loginName'] . (string)microtime());
            if (!userTokenAdd($memberToken, $modUsersData)) {
//                $apiRes['apiCode'] = -1;
//                $apiRes['apiInfo'] = '登陆失败';
//                $apiRes['apiState'] = 'error';
//                $apiRes['apiData'] = null;
                $apiRes = returnData(null, -1, 'error', '登陆失败');
                if (I("apiAll") == 1) {
                    return $apiRes;
                } else {
                    $this->ajaxReturn($apiRes);
                }//返回方式处理
            }

            $modUsersData['memberToken'] = $memberToken;

//            $apiRes['apiCode'] = '111111';
//            $apiRes['apiInfo'] = '登陆成功';
//            $apiRes['apiState'] = 'success';
//            $apiRes['apiData'] = $modUsersData;
            $apiRes = returnData($modUsersData);

            if (I("apiAll") == 1) {
                return $apiRes;
            } else {
                $this->ajaxReturn($apiRes);
            }//返回方式处理


        }


        if (empty($weiResData['unionid'])) {


            if (empty($encryptedData) or empty($iv)) {
//                $apiRet['apiCode'] = -1;
//                $apiRet['apiInfo'] = 'iv或encryptedData 字段有误';
//                $apiRet['apiState'] = 'error';
//                $apiRet['apiData'] = $userPostData;
                $apiRet = returnData($userPostData, -1, 'error', 'iv或encryptedData 字段有误');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }


            //如果返回空的 就进行解密获取
            import('Vendor.WXBizDataCrypt.WXBizDataCrypt');

            $sessionKey = $weiResData['session_key'];

            $pc = new \WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encryptedData, $iv, $redata);

            $err2Code = $pc->decryptData($encryptedData2, $iv2, $redata2);


            /*$myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");

            fwrite($myfile, "errorcode:$err2Code # resdata2: $redata2 \r\n");
            fclose($myfile);*/

            if ($errCode == 0) {


                // 		     $myfile = fopen("hhhhhhhhhhhhhhheeeeeeeeeeeee.txt", "a+") or die("Unable to open file!");

                // 			fwrite($myfile, "恭喜我要解析啦 \r\n");
                // 			fclose($myfile);


                //解析出unionid
                $weiResData = json_decode($redata, true);
                $weiResData2 = json_decode($redata2, true);

            } else {

                // 		    $myfile = fopen("hhhhhhhhhhhhhhheeeeeeeeeeeee.txt", "a+") or die("Unable to open file!");

                // 			fwrite($myfile, "你咋瞎几把走 \r\n");
                // 			fclose($myfile);


                $apiRet = array();
//                $apiRet['apiCode'] = -1;
//                $apiRet['apiInfo'] = '解析unionid---errCode';
//                $apiRet['apiState'] = 'error';
//                $apiRet['apiData'] = $errCode;
                $apiRet = returnData($errCode, -1, 'error', '解析unionid---errCode');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理

            }
            if (empty($weiResData['unionId'])) {
                $apiRet = array();
//                $apiRet['apiCode'] = -1;
//                $apiRet['apiInfo'] = '获取不到unionid,请求为空，解密为空';
//                $apiRet['apiState'] = 'error';
//                $apiRet['apiData'] = $weiResData;
                $apiRet = returnData($weiResData, -1, 'error', '获取不到unionid,请求为空，解密为空');
                if (I("apiAll") == 1) {
                    return $apiRet;
                } else {
                    $this->ajaxReturn($apiRet);
                }//返回方式处理
            }

            //兼容解密后的unionId  解密后的I为大写
            $weiResData['unionid'] = $weiResData['unionId'];
            $weiResData['openid'] = $weiResData['openId'];
        }
        //if(I("apiAll") == 1){return $weiResData;}else{$this->ajaxReturn($weiResData);}//返回方式处理

        //检查此微信 是否绑定手机
        $modUsersIsEmpty = $modUsers->where("WxUnionid='{$weiResData['unionid']}' and loginName !=''")->find();


        //如果为空 即为注册
        if (empty($modUsersIsEmpty)) {
            $IsmsCode = I('smsCode');
            if (!isset($_POST['encryptedData2']) && !empty($IsmsCode)) {
                $user_Phone = I('userPhone');
                if ($user_smsCode != S("app_reg_mobileNumber_{$user_Phone}")) {
                    /*$myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = $user_smsCode . '+++++' . S("app_reg_mobileNumber_{$user_Phone}") . '手机号' . $user_Phone;
            fwrite($myfile, "走了吗1111111：$txt \n");
            fclose($myfile);*/
//                    $apiRet['apiCode'] = '000082';
//                    $apiRet['apiInfo'] = '验证码错误！';
//                    $apiRet['apiState'] = 'error';
                    $apiRet = returnData(null, -1, 'error', '验证码错误！');
                    if (I("apiAll") == 1) {
                        return $apiRet;
                    } else {
                        $this->ajaxReturn($apiRet);
                    }//返回方式处理
                } else {
                    S("app_reg_mobileNumber_{$user_Phone}", null);
                }
            } else {
                $user_Phone = $weiResData2['purePhoneNumber'];
            }
            //判断参数 是否齐全
            /*if(empty($user_Phone) || empty($user_loginPwd) || empty($user_smsCode)){
                $apiRet['apiCode']='000080';
                $apiRet['apiInfo']='请绑定手机号或绑定手机号时参数携带错误';
                $apiRet['apiState']='error';
                if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
            }*/
            //校验短信验证码
            /*$myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = $user_smsCode . '+++++' . S("app_reg_mobileNumber_{$user_Phone}") . '手机号' . $user_Phone;
            fwrite($myfile, "走了吗1111111：$txt \n");
            fclose($myfile);*/
            //判断当前手机号是否已被注册 如果已被注册 则进行直接绑定微信
            $userPhoneIsEmpty = $modUsers->where("loginName='{$user_Phone}'")->find();
            if (!empty($userPhoneIsEmpty)) {
                unset($data);
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['openId'] = $weiResData['openid'];
                $data['WxOpenId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;
                $modUsers->where("loginName='{$user_Phone}'")->save($data);
                /* 	$apiRet['apiCode']='000081';
                    $apiRet['apiInfo']='手机号已被绑定，请使用手机号在 pc端或者app进行登录';
                    $apiRet['apiState']='error';
                    if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理 */
            } else {
                unset($data);
                $data['loginName'] = $user_Phone;
                //$data['loginSecret'] = rand(1000,9999);
                //$data['loginPwd'] =md5($user_loginPwd.$data['loginSecret']);
                $data['userName'] = $userName;
                $data['userPhoto'] = $userPhoto;
                $data['createTime'] = date('Y-m-d H:i:s');
                $data['openId'] = $weiResData['openid'];
                $data['WxOpenId'] = $weiResData['openid'];
                $data['userFrom'] = 3;
                $data['WxUnionid'] = $weiResData['unionid'];
                $data['userPhone'] = $user_Phone;
                $data['firstOrder'] = 1;
                $add_is_ok_id = $modUsers->add($data);

                //判断是否是被邀请
                $Invitation = I('InvitationID', 0);//原始邀请人的userId

                //后加,修复邀请无效的bug start
                if (empty($Invitation)) {
                    $cacheRecordTable = M('invite_cache_record');
                    $recordWhere = [];
                    $recordWhere['inviteePhone'] = $user_Phone;
                    $recordWhere['icrFlag'] = 1;
                    $recordInfo = $cacheRecordTable->where($recordWhere)->order('id desc')->find();
                    if ($recordInfo) {
                        $Invitation = $recordInfo['inviterId'];
                    }
                }

                //后加 end

                if (!empty($Invitation)) {
                    self::InvitationFriend($Invitation, $add_is_ok_id);
                }

                //新人专享大礼
                $isNewPeopleGift = self::FunNewPeopleGift($add_is_ok_id);
                self::distributionRelation($data['userPhone'], $add_is_ok_id);//写入用户分销关系表

                self::InvitationFriendSetmeal($add_is_ok_id, $Invitation); //邀请好友开通会员送券
            }


        } else {
            /*$myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                $txt = $user_smsCode . '+++++' . S("app_reg_mobileNumber_{$user_Phone}") . '手机号' . I('userPhone');
                fwrite($myfile, "走了吗1111111：$txt \n");
				fclose($myfile);*/
            $IuserPhone = I('userPhone');
            if (!empty($IuserPhone)) {
                //暂时先顶着
                $user_Phone = I('userPhone');
                $user_smsCode = I('smsCode');
                $users = M('users')->where(['userFlag' => 1, 'userPhone' => $user_Phone])->find();
                if ($user_smsCode != S("app_reg_mobileNumber_{$user_Phone}")) {
//                    $apiRet['apiCode'] = '000082';
//                    $apiRet['apiInfo'] = '验证码错误！';
//                    $apiRet['apiState'] = 'error';
                    $apiRet = returnData(null, -1, 'error', '验证码错误！');
                    if (I("apiAll") == 1) {
                        return $apiRet;
                    } else {
                        $this->ajaxReturn($apiRet);
                    }//返回方式处理
                }
                if (!$users) {
//                    $apiRes['apiCode'] = -1;
//                    $apiRes['apiInfo'] = '登陆失败';
//                    $apiRes['apiState'] = 'error';
//                    $apiRes['apiData'] = null;
                    $apiRes = returnData(null, -1, 'error', '登陆失败');

                    if (I("apiAll") == 1) {
                        return $apiRes;
                    } else {
                        $this->ajaxReturn($apiRes);
                    }//返回方式处理
                }
                //生成用唯一token
                $memberToken = md5(uniqid('', true) . $user_smsCode . $users['userId'] . $users['loginName'] . (string)microtime());
                $modUsersData = ['loginName' => $user_Phone, 'userId' => $users['userId'], 'userPhoto' => $userPhoto, 'userName' => $userName, 'openId' => $users['openId'], 'userPhone' => $user_Phone];
                if (!userTokenAdd($memberToken, $modUsersData)) {
//                    $apiRes['apiCode'] = -1;
//                    $apiRes['apiInfo'] = '登陆失败';
//                    $apiRes['apiState'] = 'error';
//                    $apiRes['apiData'] = null;
                    $apiRet = returnData(null, -1, 'error', '登陆失败');
                    if (I("apiAll") == 1) {
                        return $apiRes;
                    } else {
                        $this->ajaxReturn($apiRes);
                    }//返回方式处理
                }
                $modUsersData['memberToken'] = $memberToken;
//                $apiRes['apiCode'] = '111111';
//                $apiRes['apiInfo'] = '登陆成功';
//                $apiRes['apiState'] = 'success';
//                $apiRes['apiData'] = $modUsersData;
                $apiRes = returnData($modUsersData);

                if (I("apiAll") == 1) {
                    return $apiRes;
                } else {
                    $this->ajaxReturn($apiRes);
                }//返回方式处理
            }
        }

        //登陆生成token

        $where['WxUnionid'] = $weiResData['unionid'];
        $where['userFlag'] = 1;

        $modUsersData = $modUsers->where($where)->field(array("loginName", "userId", "userPhoto", "userName", 'openId', 'userPhone'))->find();//再次获取用户所有字段

        //判断新人专享获得的积分是否为空
        //if(!empty($isNewPeopleGift)){
        $modUsersData['isNewPeopleGift'] = $isNewPeopleGift;
        //}


        if (empty($modUsersData)) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '用户被禁用，或者不存在';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '用户被禁用，或者不存在');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }


        //记录登录日志

        $User = M("log_user_logins");
        $data = array();
        $data["userId"] = $modUsersData['userId'];
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $data["loginSrc"] = 3;
        $User->add($data);

        $logdata['lastIP'] = get_client_ip();
        $logdata['lastTime'] = date('Y-m-d H:i:s');
        $modUsers->where("userId = '{$modUsersData['userId']}'")->save($logdata);

        //生成用唯一token
        $memberToken = md5(uniqid('', true) . $code . $modUsersData['userId'] . $modUsersData['loginName'] . (string)microtime());
        if (!userTokenAdd($memberToken, $modUsersData)) {
//            $apiRes['apiCode'] = -1;
//            $apiRes['apiInfo'] = '登陆失败';
//            $apiRes['apiState'] = 'error';
//            $apiRes['apiData'] = null;
            $apiRes = returnData(null, -1, 'error', '登陆失败');

            if (I("apiAll") == 1) {
                return $apiRes;
            } else {
                $this->ajaxReturn($apiRes);
            }//返回方式处理
        }

        $modUsersData['memberToken'] = $memberToken;

//        $apiRes['apiCode'] = '111111';
//        $apiRes['apiInfo'] = '登陆成功';
//        $apiRes['apiState'] = 'success';
//        $apiRes['apiData'] = $modUsersData;
        $apiRes = returnData($modUsersData);

        if (I("apiAll") == 1) {
            return $apiRes;
        } else {
            $this->ajaxReturn($apiRes);
        }//返回方式处理


    }

    //店铺新人专享商品列表
    public function ShopNewPeopleAllList()
    {
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $societyId = I('societyId');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');

        if (empty($adcode) || empty($lat) || empty($lng)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->ShopNewPeopleAllList($adcode, $lat, $lng, $societyId, $page, $pageSize);
//        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取对应属性的商品列表
     * @param string memberToken
     * @param 类型 type(1:会员专享 | 2:推荐 | 3:新品 | 4:热销)
     * */
    public function getAttrGoodsList()
    {
        $userId = $this->getMemberInfo()['userId'];
        $adcode = I('adcode');
        $lat = I('lat');
        $lng = I('lng');
        $page = I('page', 1);
        $type = I('type', 1);

        if (empty($adcode) || empty($lat) || empty($lng) || empty($type)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $m = D("V3/Api");
        $mod = $m->getAttrGoodsList($adcode, $lat, $lng, $type, $page, $userId);
//        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取邀请数据
     * */
    public function getSetmealInvitation()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 46;
        $m = D("V3/Api/Api");
        $param = I();
        $param['userId'] = $userId;
        $mod = $m->getSetmealInvitation($param);
        $mod = returnData($mod);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取店铺限时商品数据量统计
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * */
    public function getShopFlashSaleGoodsCount()
    {
        $shopId = I('shopId');
        $flashSaleId = I('flashSaleId');
        if (empty($shopId) || empty($flashSaleId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->getShopFlashSaleGoodsCount($shopId, $flashSaleId);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取商品的sku列表
     * @param int goodsId
     * */
    public function getGoodsSkuList()
    {
        $goodsId = I('goodsId');
        if (empty($goodsId)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='字段有误';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $param['goodsId'] = $goodsId;
        $m = D("Weimendian/Api");
        $mod = $m->getGoodsSkuList($param);
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 获取手机验证码
     */
    public function getSmsVerify()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '短信发送失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '短信发送失败');

        $type = I('type', 0, 'intval');//1:andriod 2:IOS
        $phone = $this->MemberVeri()['userPhone'];
        if (empty($phone) || empty($type)) {
//            $apiRet['apiInfo']='字段有误';
            $apiRet = returnData(null, -1, 'error', '字段有误');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $phoneVerify = mt_rand(100000, 999999);
        $down_url = '';
        if ($type == 1) {//android
            $down_url = $GLOBALS['CONFIG']['appDownAndroid'];
        } else if ($type == 2) {//ios
            $down_url = $GLOBALS['CONFIG']['appDownIos'];
        }
        $msg = "下载APP享更多优惠、积分抵现，下单还可领分享红包~立即下载戳" . $down_url;
        $rv = D('Home/LogSms')->sendSMS(0, $phone, $msg, 'getSmsVerify', $phoneVerify);
        $result = array('code' => -1);
        if ($rv['status'] == 1) {//成功
            $result['code'] = 0;
        }
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn(returnData($result));
        }//返回方式处理
    }

    /**
     * 获取app下载地址
     * @return array
     */
    public function getAppDownUrl()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
        $apiRet = returnData(array(), -1, 'error', '操作失败');
        $type = I('type', 0, 'intval');//1:andriod 2:IOS
        if (empty($type)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $down_url = '';
        if ($type == 1) {//android
            $down_url = $GLOBALS['CONFIG']['appDownAndroid'];
        } else if ($type == 2) {//ios
            $down_url = $GLOBALS['CONFIG']['appDownIos'];
        }

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = array(
//            'down_url'=>$down_url
//        );
        $apiRet = returnData(array(
            'down_url' => $down_url
        ));

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 筛选商品,筛选出可用（在配送范围内）和不可用（不在配送范围内）的商品
     */
    public function checkShopDistributionGoods()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
        $apiRet = returnData(array(), -1, 'error', '操作失败');

        $addressId = I('addressId', 0, 'intval');//地址ID
        $goodsId = I('goodsId', '', 'trim');//商品ID，多个以,连接
        if (empty($addressId) || empty($goodsId)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $data = checkShopDistributionGoods($addressId, $goodsId);

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $data;
        $apiRet = returnData($data);

        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 创建分享图片
     */
    public function createShareImage()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
        $apiRet = returnData(array(), -1, 'error', '操作失败');

//        $share_image_url = 'http://www.qzhaoduocai.com/choujiang/timeLine/index.html?memberToken=27849c0fae6b636e4fa3480beab17bc0&goodsId=270&shopId=37';
        $image_url = I('image_url', '', 'trim');//图片url，例如：http://www.qzhaoduocai.com/choujiang/timeLine/index.html
        $memberToken = I('memberToken', '', 'trim');//token
        $goodsId = I('goodsId', 0, 'intval');//商品ID
        $shopId = I('shopId', 0, 'intval');//店铺ID
        if (empty($image_url) || empty($memberToken) || empty($goodsId) || empty($shopId)) {
//            $apiRet['apiInfo'] = '参数不全';
            $apiRet = returnData(null, -1, 'error', '参数不全');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $sim = M('share_image');
        $where = array('shopId' => $shopId, 'goodsId' => $goodsId);
        $share_image_info = $sim->where($where)->find();
        if (!empty($share_image_info['image'])) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '操作成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $share_image_info;
            $apiRet = returnData($share_image_info);

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $goods_info = M('goods')->where(array('goodsId' => $goodsId, 'shopId' => $shopId))->find();
        if (empty($goods_info)) {
//            $apiRet['apiInfo'] = '商品不存在';
            $apiRet = returnData(null, -1, 'error', '商品不存在');

            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }

        $dir = WSTRootPath() . "/bin/";
        $file_dir = "temp/";
        $filename = "share_" . md5($goods_info['goodsName'] . $goods_info['shopPrice'] . $goods_info['goodsImg'] . $goods_info['goodsDesc'] . $goods_info['memberPrice']) . ".jpg";
        $share_image_url = $image_url . "?memberToken=" . $memberToken . "&goodsId=" . $goodsId . "&shopId=" . $shopId;

        $qiniuDomain = $GLOBALS['CONFIG']['qiniuDomain'];

        $param = array(
            'share_image_url' => $share_image_url,
            'filename' => $filename,
            'dir' => $dir,
            'file_dir' => $file_dir
        );

        $result = curlRequest(WSTRootDomain() . "/bin/imgserver.php", $param, 1);
        $result = json_decode($result, true);
        if ($result['code'] == 0) {
            $path = $dir . $file_dir . $filename;
            $result_new = uploadQiniuPic($path, $filename);
            if ($result_new['code'] == 0) {
                $image = $qiniuDomain . '/' . $result_new['data']['key'];
                //如果存在，则编辑，否则，则添加
                if (!empty($share_image_info)) {
                    $sim->where($where)->save(array('image' => $image));
                } else {
                    $sim->add(array('shopId' => $shopId, 'goodsId' => $goodsId, 'image' => $image));
                }

                //删除原图
                unlink($path);

//                $apiRet['apiCode'] = 0;
//                $apiRet['apiInfo'] = '操作成功';
//                $apiRet['apiState'] = 'success';
//                $apiRet['apiData'] = $sim->where($where)->find();
                $apiRet = returnData($sim->where($where)->find());
            }
        }
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理
    }

    /**
     * 获取商家配置
     * @param int shopId
     */
    public function getShopCfg()
    {
        //$this->MemberVeri();
        // $apiRet['apiCode'] = -1;
        // $apiRet['apiInfo'] = '参数不全';
        // $apiRet['apiState'] = 'error';
        // $apiRet['apiData'] = array();
        $request = I();
        if (empty($request['shopId'])) {
            return returnData(null, -1, 'error', '参数有误');
        }
        $m = D('V3/Api');
        $shopId = (int)$request['shopId'];
        $data = $m->getShopCfg((int)$shopId);
        // $apiRet['apiCode'] = 0;
        // $apiRet['apiInfo'] = '操作成功';
        // $apiRet['apiState'] = 'success';
        // $apiRet['apiData'] = $data;

        $data = returnData($data);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理

    }

    /**
     * 改变购物中商品的状态[前置仓]
     * @param string memberToken
     * @param string cartId PS:购物车数据id,多个用英文逗号隔开,传空则为全选反选
     * @param int isChecked PS:选中状态(true|false),针对全选反选
     * @param string clear 清除失效商品的已选中状态(true|false)
     * */
    public function changeCartGoodsStatus()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $PcartId = I('cartId');
        $isChecked = I('isChecked');
        if (empty($PcartId) && empty($isChecked)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        $isChecked = I('isChecked');
        $param = [];
        $param['cartId'] = I('cartId');
        $param['isChecked'] = $isChecked;
        $param['clear'] = I('clear');
        $param['userId'] = $userId;
        $data = $m->changeCartGoodsStatus($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 改变购物中商品的状态[多商户]
     * @param string memberToken
     * @param string cartId PS:购物车数据id,多个用英文逗号隔开
     * @param int shopId PS:店铺id
     * @param int checkAll PS:全选/反选(1:店铺全选|2:店铺反选|3:所有全选|4:所有反选) PS:如果选择单商品,可以不用传shopId,chekAll,操作店铺全选时必须传shopId和checkAll
     * @param string clear 清除失效商品的已选中状态(true|false)
     * */
    public function changeCartGoodsStatusDSH()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $checkAll = (int)I('checkAll');
        $clear = I('clear');
        $cartId = trim(I('cartId'), ',');
        $shopId = I('shopId');
        if (($checkAll == 1 || $checkAll == 2) && empty($shopId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误', '店铺全选缺少shopId参数');
            $this->ajaxReturn($apiRet);
        }
        if (empty(I('cartId')) && empty($checkAll)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if (!empty($checkAll) && !in_array($checkAll, [1, 2, 3, 4])) {
            $apiRet = returnData(null, -1, 'error', '参数有误', 'checkAll参数值异常');
            $this->ajaxReturn($apiRet);
        }
        if (!empty($clear) && empty($cartId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误', '缺少cartId参数');
            $this->ajaxReturn($apiRet);
        }
        $param = [];
        $param['cartId'] = I('cartId');
        $param['cartId'] = $cartId;
        $param['checkAll'] = $checkAll;
        $param['clear'] = $clear;
        $param['userId'] = $userId;
        $param['shopId'] = (int)I('shopId');
        $data = $m->changeCartGoodsStatusDSH($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 废弃
     * 获取购物车商品合计价格
     * @param string memberToken
     * @param string cartId PS:多个用逗号隔开,cartId传空则默认计算所有选中状态的数据
     * */
    public function sumCartGoodsAmount()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        /*if(empty(I('cartId'))){
            $apiRet = returnData(null,-1,'error','参数有误');
            $this->ajaxReturn($apiRet);
        }*/
        $param = [];
        $param['cartId'] = I('cartId');
        $param['userId'] = $userId;
        $data = $m->sumCartGoodsAmount($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 废弃:现在从预提交订单中获取可用优惠券列表
     *  提交订单-可用优惠券列表
     * @param string memberToken
     * */
    public function effectiveCoupons()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $param = [];
        $param['userId'] = $userId;
        $data = $m->effectiveCoupons($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 预提交订单 适用于前置仓
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/yti87t
     * @param string memberToken
     * @param int addressId
     * @param int isSelf
     * @param int cuid PS:wst_coupons_users 表中的 id
     * @param int ucouponId PS:升级后的优惠券id 暂未用到
     * @param int couponId PS:优惠券id PS:暂未用到
     * @param int useScore PS:是否使用积分(1:使用)
     * @param int invoiceClient PS:发票id
     * */
    public function preSubmit()
    {
        $userId = $this->MemberVeri()['userId'];
//        $m = D("V3/Api");
        $m = new ApiModel();
        $param = [];
        $param['userId'] = (int)$userId;
        $param['addressId'] = (int)I('addressId');
        $param['isSelf'] = I('isSelf');
        $param['cuid'] = I('cuid');//用户领取的运费券的记录id wst_coupons_users 表中的 id
        $param['ucouponId'] = I('ucouponId');//升级后的优惠券id
        $param['couponId'] = I('couponId');
        $param['useScore'] = I('useScore');//是否使用积分(1:使用)
        $param['invoiceClient'] = I('invoiceClient', 0);//发票id
        $param['shopId'] = I('shopId', 0);//店铺ID
        $param['wuCouponId'] = I('wuCouponId', 0);//用户领取的运费券的记录id
        $param['buyNowGoodsId'] = I('buyNowGoodsId', 0);//立即购买-商品id 注：仅用于立即购买
        $param['buyNowSkuId'] = I('buyNowSkuId', 0);//立即购买-skuId 注：仅用于立即购买
        $param['buyNowGoodsCnt'] = I('buyNowGoodsCnt', 0);//立即购买-数量 注：仅用于立即购买
        $data = $m->preSubmit($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 预提交订单 适用于多商户
     * PS:
     * 需要提示前端注意的地方:同一个优惠券只能在一家店铺使用
     * 店铺:确认是否开具发票|订单备注|优惠券
     * 共用:积分|是否自提
     * 共用:运费:统一运费|叠加运费
     * @param jsonString $shopParam
     * invoiceClient:发票id|cuid:用户领取的优惠券id
     * [{"shopId":"1","invoiceClient":"0","cuid":"12"},{"shopId":"2","invoiceClient":"1","cuid":"12"}]
     * @param string memberToken
     * @param int isSelf PS:是否自提(1:自提)
     * @param int addressId PS:地址id
     * @param int useScore PS:使用积分(1:使用积分)
     * */
    public function preSubmitDSH()
    {
        $userId = $this->MemberVeri()['userId'];
//        $m = D("V3/Api");
        $m = new ApiModel();
        $param = [];
        $param['userId'] = (int)$userId;
        $param['addressId'] = (int)I('addressId');
        $param['shopParam'] = htmlspecialchars_decode(I('shopParam'));
        $param['isSelf'] = (int)I('isSelf', 0);
        $param['useScore'] = (int)I('useScore', 0);
        $param['wuCouponId'] = I('wuCouponId', 0);//运费劵ID
        $data = $m->preSubmitDSH($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 统计商品评论数量
     * @param int goodsId
     * */
    public function countGoodsAppraises()
    {
        $goodsId = (int)I("goodsId");
        if (empty($goodsId)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->countGoodsAppraises($goodsId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 意见反馈
     * @param string memberToken
     * @param string content
     * @param string imgs
     * */
    public function submitFeedback()
    {
        $memberToken = I('memberToken');
        if (!empty($memberToken)) {
            $userId = $this->MemberVeri()['userId'];
        }
        $content = I('content');
        $imgs = I('imgs');
        if (empty($content)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $param = [];
        $param['content'] = $content;
        $param['imgs'] = $imgs;
        $param['userId'] = $userId;
        $mod = $m->submitFeedback($param);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 测试用
     * @param string openId
     * @param int payType PS:支付方式(0:现金|1:支付宝|2:微信|3:余额)
     * @param int dataFrom PS:来源(0:商城 1:微信 2:手机版 3:app 4：小程序)
     * */
    public function jsapi()
    {
        $m = D("V3/Api");
        $mod = $m->jsapi();
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 统一下单支付
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/qi5imp
     * @param string memberToken
     * @param int payType PS:支付方式(1:支付宝|2:微信)
     * @param int dataFrom PS:来源(0:商城 1:微信 2:手机版 3:app 4：小程序 5:公众号(后加))
     * @param int dataType PS:功能(1:下单支付|2:重新支付|3:余额充值|4:开通绿卡|5:优惠券购买(加量包))
     * @param string openId
     * */
    public function unifiedOrder()
    {
        //接收参数
        $userId = $this->MemberVeri()['userId'];
//        $userId = 267;
        $dataType = (int)I('dataType');
        $dataFrom = (int)I('dataFrom');
        $payType = (int)I('payType');
        //验证基本数据
        if (empty($dataType) || empty($dataFrom) || empty($payType)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            $this->ajaxReturn($returnData);
        }
        if (!in_array($payType, [1, 2, 3])) {
            $returnData = returnData(null, -1, 'error', '支付方式传参错误');
            $this->ajaxReturn($returnData);
        }
        //$m = D("V3/Api");
        $m = new ApiModel();
        $param = [];
        $param['userId'] = $userId;
        $param['dataType'] = $dataType;
        $param['dataFrom'] = $dataFrom;
        $param['payType'] = $payType;
        $param['openId'] = I('openId');
        $param['dataValue'] = htmlspecialchars_decode(I('dataValue'));
        if ($dataFrom == 4 && $payType == 1) {
            $returnData = returnData(null, -1, 'error', '支付方式传参错误');
            $this->ajaxReturn($returnData);
        }
        if ($dataFrom == 4 && empty($param['openId'])) {
            $returnData = returnData(null, -1, 'error', '微信小程序支付，传参缺少openId');
            $this->ajaxReturn($returnData);
        }
        if (empty($param['dataValue'])) {
            $returnData = returnData(null, -1, 'error', 'dataValue传参不合法');
            $this->ajaxReturn($returnData);
        }
        switch ($dataType) {
            case 1:
                //下单支付
                $mod = $m->orderUnifiedOrder($param);
                break;
            case 2:
                //重新支付
                $mod = $m->againUnifiedOrder($param);
                break;
            case 3:
                //余额充值
                $mod = $m->balanceUnifiedOrder($param);
                break;
            case 4:
                //开通绿卡
                $mod = $m->buySetMealUnifiedOrder($param);
                break;
            case 5:
                //优惠券购买(加量包)
                $mod = $m->buyCouponUnifiedOrder($param);
                break;
            case 6:
                //用户还款
                $mod = $m->userRepayment($param);
                break;

        }
        $this->ajaxReturn($mod);
    }

    /**
     * 获取购物车商品数量
     * @param string memberToken
     * @param int shopId 店铺id,传shopId为店铺购物车商品数量,不传shopId为所有店铺购物车商品数量
     * */
    public function getCartGoodsNum()
    {
        $userId = $this->getMemberInfo()['userId'];
        $shopId = (int)I('shopId');
        $m = D('V3/Api');
        $param = [];
        $param['userId'] = $userId;
        $param['shopId'] = $shopId;
        $mod = $m->getCartGoodsNum($param);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     *解析openId
     * @param int type 支付场景类型(1:微信小程序|2:公众号)
     * @param string code 前端传过来的code
     * */
    public function getOpenId()
    {
        $userId = $this->MemberVeri()['userId'];
        $type = I('type', 1);
        $code = I('code');
        $m = D('V3/Api');
        $res = $m->getOpenId($type, $code, $userId);
        if (I("apiAll") == 1) {
            return $res;
        } else {
            $this->ajaxReturn($res);
        }//返回方式处理
    }

    /**
     * 获得用户余额流水
     */
    public function getUserBalanceList()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D('V3/Api');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 10, 'intval');
        $mod = $m->getUserBalanceList($userId, $page, $pageSize);
        $mod = empty($mod) ? array() : $mod;
        $mod = returnData($mod);

        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 轮询订单状态
     * @param string memberToken
     * @param string orderToken
     * */
    public function pollingOrderStatus()
    {
        $userId = $this->MemberVeri()['userId'];
        $orderToken = I('orderToken');
        if (empty($orderToken)) {
            $returnData = returnData(null, -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $m = D('V3/Api');
        $param = [];
        $param['userId'] = $userId;
        $param['orderToken'] = $orderToken;
        $mod = $m->pollingOrderStatus($param);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }

    /**
     * 发票详情
     * @return mixed
     */
    public function InvoiceDetail()
    {
        $userId = $this->MemberVeri()['userId'];
        //$userId = 18;
        $m = D("V3/Api");
        $result = $m->invoiceDetail($userId);


        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 领取店铺优惠券[返回店铺可用优惠券列表]
     * @param string memberToken
     * @param int shopId PS:店铺id
     * */
    public function receiveShopCoupon()
    {
        $userId = $this->MemberVeri()['userId'];
        $shopId = (int)I('shopId');
        if (empty($shopId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        $m = D("V3/Api");
        $param = [];
        $param['userId'] = $userId;
        $param['shopId'] = $shopId;
        $data = $m->receiveShopCoupon($param);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }//返回方式处理
    }

    /**
     * 添加用户登录日志
     * loginSrc ,0:商城 1:webapp 2:App 3：小程序
     */
    public function addUsersLoginLog()
    {
        $userId = $this->MemberVeri()['userId'];
        $loginSrc = I('loginSrc', -1, 'intval');
        if (empty($userId) || $loginSrc < 0) {
            $returnData = returnData(array(), -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $result = $m->addUsersLoginLog($userId, $loginSrc);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * app热更新
     */
    public function appHotUpdate()
    {
        $version = I('version', '', 'trim');
        if (empty($version)) {
            $returnData = returnData(array(), -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $hotUpdateVersion = $GLOBALS['CONFIG']['hotUpdateVersion'];//热更新版本号
        $hotUpdateWgtUrl = $GLOBALS['CONFIG']['hotUpdateWgtUrl'];//（热更新）wgt包的下载地址,用于 wgt 方式更新
        $appDownAndroid = $GLOBALS['CONFIG']['appDownAndroid'];//安卓下载地址

        $result = array(
            'hotUpdateVersion' => $hotUpdateVersion,//热更新版本号
            'update' => str_compare($hotUpdateVersion, $version),//是否有更新，0：否，1：是
            'wgtUrl' => $hotUpdateWgtUrl,//wgt 包的下载地址，用于 wgt 方式更新。
            'pkgUrl' => $appDownAndroid//安卓下载地址
        );
        $result = returnData($result);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     *此方法待前端确定用不用
     * 获取微信公众号授权地址
     * @param string redirectUrl 跳转路径
     * @param type PS:后期可以根据业务场景进行扩展
     * */
    function getWxCode()
    {
        $redirectUrl = I('redirectUrl', '');
        $m = D("V3/Api");
        $result = $m->getWxCode($redirectUrl);
        if (I("apiAll") == 1) {
            return $result;
        } else {
            $this->ajaxReturn($result);
        }//返回方式处理
    }

    /**
     * 极光推送设置别名
     */
    public function jgPushSetAlias()
    {
        $userId = $this->MemberVeri()['userId'];
        $registration_id = I('registration_id', '', 'trim');
        if (empty($registration_id)) {
            $returnData = returnData(array(), -1, 'error', '有参数不能为空');
            if (I("apiAll") == 1) {
                return $returnData;
            } else {
                $this->ajaxReturn($returnData);
            }//返回方式处理
        }
        $param = array('alias' => $userId);
        $param = json_encode($param);
        $url = $GLOBALS["CONFIG"]["jgApiUrl"] . "/v3/devices/" . $registration_id;
        $result = curlRequest($url, $param, 1, 1);
        $result = json_decode($result, true);
        echo "<pre>";
        var_dump($result);
        exit();
    }

    /**
     * 减购物车商品数量(依赖商品id和skuId)
     * @param int goodsId 商品id
     * @param int skuId skuId
     * @param string goodsAttrId 商品属性id
     * @param int goodsCnt 数量
     * */
    public function subtractToCart()
    {
        $userId = $this->MemberVeri()['userId'];
        $goodsId = (int)I("goodsId");
        $goodsCnt = (int)I("goodsCnt");
        $goodsAttrId = I('goodsAttrId', 0);
        $goodsAttrId = trim($goodsAttrId, ',');
        $skuId = (int)I("skuId");//后加skuId
        if (empty($goodsId) || empty($goodsCnt)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->subtractToCart($userId, $goodsId, $goodsCnt, $goodsAttrId, $skuId);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }//返回方式处理
    }


    /**
     * 根据订单id获取配送员配置
     */
    public function getDeliveryClerkLatLng()
    {
        $userId = $this->MemberVeri()['userId'];
        $orderId = I("orderId");//订单id

        if (empty($orderId)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }


        //获取订单第三方id 增加userId防止超出数据权限
        $orders_data = M('orders')->where("orderId = {$orderId} and userId = {$userId}")->find();
        $tradeNo = $orders_data['deliveryNo'];
        if (empty($tradeNo)) {
            $apiRet = returnData(null, -1, 'error', '该订单不存在第三方配送订单');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }

        $retData['order'] = array(
            'latitude' => $orders_data['lat'],
            'longitude' => $orders_data['lng'],
        );
        //获取店铺经纬度
        $shops_data = M('shops')->where('shopId = ' . $orders_data['shopId'])->find();
        $retData['shop'] = array(
            'latitude' => $shops_data['latitude'],
            'longitude' => $shops_data['longitude'],
        );

        //获取骑手经纬度
        $m = D("V3/Kuaipao");
        $res = '';
        $m->getOrderInfo($tradeNo, $res, $info, $error);

        $retData['driver'] = array(
            'gate_time' => $res['gate_time'],
            'latitude' => $res['latitude'],
            'longitude' => $res['longitude'],
            'info' => $info,
            'error' => $error
        );
        $apiRet = returnData($retData);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }//返回方式处理

    }

    /**
     * 根据店铺活动标识获取活动页数据详情
     * 前置仓专用接口
     */
    public function getBannerActivityDetail()
    {
        $activityId = I("activityId");//活动标识
        if (empty($activityId)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }

        $m = D("V3/Api");
        $ret = $m->getBannerActivityDetail($activityId);
        $apiRet = returnData($ret);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }
    }

    /**
     * 返回支付方式列表
     */
    public function getPayList()
    {
        $m = D("V3/Api");
        $ret = $m->getPayList();
        $apiRet = returnData($ret);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }
    }

    /**
     * @return mixed
     * 获取广告列表[广告位置标识码]
     */
    public function getAdList()
    {
        $m = D("V3/Api");
        $adCode = I("adlocationCode");//活动标识
        if (empty($adCode)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }
        }
        $shopId = I('shopId');
        if (empty($shopId)) {
            $shopId = 0;
        }
        $ret = $m->getAdList($adCode, $shopId);
        $apiRet = returnData($ret);
        if (I("apiAll") == 1) {
            return $apiRet;
        } else {
            $this->ajaxReturn($apiRet);
        }
    }

    /**
     * 地推拉新列表
     * @param string memberToken
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * */
    public function getPullNewLogList()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D("V3/Api");
        $data = $m->getPullNewLogList($userId, $page, $pageSize);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 地推收益明细
     * @param string memberToken
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * */
    public function getPullNewAmountLogList()
    {
        $userId = $this->MemberVeri()['userId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D("V3/Api");
        $data = $m->getPullNewAmountLogList($userId, $page, $pageSize);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 用户提交商户申请
     */
    public function submitSettlement()
    {
        $userId = $this->MemberVeri()['userId'];
        $params = I();
        $params['userId'] = $userId;

        $m = D("V3/Api");
        $data = $m->submitSettlement($params);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    //判断用户是否已经申请店铺了 true已注册 false未注册
    public function isRegSubmitSettlement()
    {
        $userId = $this->MemberVeri()['userId'];
        $m = D("V3/Api");
        $data = $m->isRegSubmitSettlement($userId);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 获取公告列表-商城公告/店铺公告
     * @param int shopId 门店id,不传默认为商城公告,传了就是店铺公告
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * */
    public function getAnnouncementList()
    {
        $shopId = (int)I('shopId', 0);
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D("V3/Api");
        $data = $m->getAnnouncementList($shopId, $page, $pageSize);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 获取公告详情
     * @param int id
     * */
    public function getAnnouncementDetail()
    {
        $id = (int)I('id', 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '字段有误'));
        }
        $m = D("V3/Api");
        $data = $m->getAnnouncementDetail($id);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 获取时间分类与时间点
     */
    public function getdeliveryTime()
    {
        $m = new ApiModel();
        $shopId = I("shopId", 0);
        $data = $m->getdeliveryTime($shopId);
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 获取返现规则
     */
    public function getRechargeConfig()
    {
        $m = D("V3/Api");
        $data = $m->getRechargeConfig();
        if (I("apiAll") == 1) {
            return $data;
        } else {
            $this->ajaxReturn($data);
        }
    }

    /**
     * 上传图片 PS:该方法从home模块直接复制过来的
     * 支持单个图片上传、多个图片上传
     * 使用的是 tp 自带的七牛云驱动上传，只是上传速度实在是太慢了
     * 正常的，可用的
     */
    public function uploadPicQiniu()
    {
        $setting = C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
        $setting['saveName'] = array('getQiniuImgName', I('str'));
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];
        $upload = new \Think\Upload($setting);
        $rs = $upload->upload($_FILES);
        if (empty($rs)) {
            return $this->ajaxReturn(returnData(null, -1, 'error', $upload->getError()));
        }
        $flag = I('flag', 1);
        if ($flag == 1) {//PC端
            foreach ($rs as $v) {
                $pArray[] = $setting['driverConfig']['domain'] . strtr($v['name'], '/', '_');
            }
        } else if ($flag == 2) {//移动端
            foreach ($rs as $v) {
                $pArray[] = $setting['driverConfig']['domain'] . strtr($v['name'], '/', '_');
            }
        }
        $data['url'] = $pArray;
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 编辑购物车商品数量
     * @param string memberToken
     * @param int cartId 购物车cartId
     * @param float goodsCnt 变更数量
     * */
    public function inputCartGoodsNum()
    {
        $userId = $this->MemberVeri()['userId'];
        $cartId = (int)I('cartId');
        $goodsCnt = (float)I('goodsCnt');
        if (empty($cartId)) {
            $apiRet = returnData(false, ExceptionCodeEnum::FAIL, 'error', '字段有误');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        if ($goodsCnt <= 0) {
            $apiRet = returnData(false, ExceptionCodeEnum::FAIL, 'error', '购买数量或重量必须大于0');
            if (I("apiAll") == 1) {
                return $apiRet;
            } else {
                $this->ajaxReturn($apiRet);
            }//返回方式处理
        }
        $m = D("V3/Api");
        $mod = $m->inputCartGoodsNum($userId, $cartId, $goodsCnt);
        if (I("apiAll") == 1) {
            return $mod;
        } else {
            $this->ajaxReturn($mod);
        }
    }

    /**
     * 获取直播/短视频列表
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/rl0d3g
     * @param int shopId 门店id,传空为多商户,否则为前置仓
     * @param array type 直播类型【1:小程序直播|2:系统原生直播|3:第三方推流直播|4:短视频】
     * @param string keywords 产品/店铺/标题
     * @param int goodsCatId3 商城三级分类id
     * @param int live_status 直播状态【1:即将开始|2:正在直播|3:已结束】
     * @param int liveSort 排序【1：最新|2：最早】
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     * */
    public function getLiveplayList()
    {
        $requestParams = I();
        $params = [];
        $params['shopId'] = 0;
        $params['type'] = '';
        $params['keywords'] = '';
        $params['goodsCatId3'] = 0;
        $params['live_status'] = '';
        $params['liveSort'] = '';
        parm_filter($params, $requestParams);
        $params['type'] = json_decode(htmlspecialchars_decode($params['type']), true);
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        if (!is_array($params['type'])) {
            $params['type'] = [];
        }
        $m = D("V3/Api");
        $mod = $m->getLiveplayList($params);
        $this->ajaxReturn($mod);
    }

    /**
     * 获取直播/短视频详情
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/tryr30
     * @param int liveplayId 直播/短视频id
     * */
    public function getLiveplayDetail()
    {
        $liveplayId = (int)I('liveplayId');
        if (empty($liveplayId)) {
            $apiRet = returnData(null, -1, 'error', '字段有误');
            $this->ajaxReturn($apiRet);
        }
        $m = D("V3/Api");
        $mod = $m->getLiveplayDetail($liveplayId);
        $this->ajaxReturn($mod);
    }

    /**
     * 获取商城三级分类列表
     * */
    public function getPlatformGoodsCatList()
    {
        $m = D("V3/Api");
        $mod = $m->getPlatformGoodsCatList();
        $this->ajaxReturn($mod);
    }

    /**
     * 售后日志
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/qi85p0
     * */
    public function getOrderComplainsLog()
    {
        $complainId = (int)I('complainId');
        if (empty($complainId)) {
            $apiRet = returnData(false, -1, 'error', '字段有误');
            $this->ajaxReturn($apiRet);
        }
        $m = D("V3/Api");
        $data = $m->getOrderComplainsLog($complainId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 验证登陆名是否已经存在
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/brh8bk
     * */
    public function verificationLoginName()
    {
        $loginName = (string)I('loginName');
        if (empty($loginName)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        if (!is_mobile($loginName)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请输入正确的手机号'));
        }
        $model = new ApiModel();
        $result = $model->verificationLoginName($loginName);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 用户还款-发起还款(已废弃,请调用统一下单接口)
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/ggbvv8
     * */
    public function userRepayment()
    {
        $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '已废弃，请调用统一下单接口'));
        $userId = $this->MemberVeri()['userId'];
        $money = (float)I('money');
        $payType = (int)I('pay_type');//还款方式(2:微信 3:余额)
        $payFrom = (int)I('pay_from');//来源(1:小程序 2:APP)
        $openId = (string)I('openId');//用户openId,小程序微信支付必填
        if ($money <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款金额必须大于0'));
        }
        if (!in_array($payType, array(2, 3))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的还款方式'));
        }
        if (!in_array($payFrom, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的支付来源'));
        }
        if ($payFrom == 1 && $payType == 2 && empty($openId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-openId'));
        }
        $m = new ApiModel();
        $params = array(
            'userId' => $userId,
            'money' => $money,
            'payType' => $payType,
            'payFrom' => $payFrom,
            'openId' => $openId,
        );
        $data = $m->userRepayment($params);
        $this->ajaxReturn($data);
    }

    /**
     * 用户还款-查询还款状态
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/ehrlvf
     * */
    public function queryUserRepayment()
    {
        $this->MemberVeri();
        $notify_key = I('notify_key');
        if (empty($notify_key)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new ApiModel();
        $result = $m->queryUserRepayment($notify_key);
        $this->ajaxReturn($result);
    }


    /*
     * 店铺所有字段
      ["shopId"] => string(3) "186"
    ["shopSn"] => string(18) "shop-6316a3d468cd3"
    ["userId"] => string(6) "200638"
    ["areaId1"] => string(6) "230000"
    ["areaId2"] => string(6) "230100"
    ["areaId3"] => string(6) "230102"
    ["goodsCatId1"] => string(1) "0"
    ["goodsCatId2"] => string(1) "0"
    ["goodsCatId3"] => string(1) "0"
    ["isSelf"] => string(1) "0"
    ["shopName"] => string(27) "北大荒生活优选超市"
    ["shopCompany"] => string(9) "北大荒"
    ["shopImg"] => string(36) "qiniu://FnKLrhIRaj3umYopF7Hv2P5WUGhU"
    ["shopTel"] => string(11) "13222222222"
    ["shopAddress"] => string(39) "哈尔滨市南岗区渭水路冬奥村"
    ["avgeCostMoney"] => string(4) "0.00"
    ["deliveryStartMoney"] => string(4) "0.00"
    ["deliveryMoney"] => string(4) "0.00"
    ["deliveryFreeMoney"] => string(4) "0.00"
    ["deliveryCostTime"] => string(1) "0"
    ["deliveryTime"] => string(0) ""
    ["deliveryType"] => string(1) "0"
    ["bankId"] => string(2) "33"
    ["bankNo"] => string(16) "6222222222222222"
    ["isInvoice"] => string(1) "0"
    ["invoiceRemarks"] => string(0) ""
    ["serviceStartTime"] => string(4) "8.00"
    ["serviceEndTime"] => string(5) "17.00"
    ["shopStatus"] => string(1) "1"
    ["statusRemarks"] => string(0) ""
    ["shopAtive"] => string(1) "1"
    ["shopFlag"] => string(1) "1"
    ["createTime"] => string(19) "2022-09-06 09:35:16"
    ["latitude"] => string(9) "45.749708"
    ["longitude"] => string(10) "126.705456"
    ["mapLevel"] => string(2) "14"
    ["qqNo"] => string(0) ""
    ["bankUserName"] => string(6) "测试"
    ["dadaShopId"] => string(0) ""
    ["dadaOriginShopId"] => string(0) ""
    ["deliveryLatLng"] => string(407) "[{&quot;O&quot;:45.876022,&quot;M&quot;:126.463232,&quot;lng&quot;:126.463232,&quot;lat&quot;:45.876022},{&quot;O&quot;:45.87889,&quot;M&quot;:126.820288,&quot;lng&quot;:126.820288,&quot;lat&quot;:45.87889},{&quot;O&quot;:45.62928,&quot;M&quot;:126.898566,&quot;lng&quot;:126.898566,&quot;lat&quot;:45.62928},{&quot;O&quot;:45.6288,&quot;M&quot;:126.43302,&quot;lng&quot;:126.43302,&quot;lat&quot;:45.6288}]"
    ["deliveryLatLngName"] => string(0) ""
    ["shopSecKillNUM"] => string(1) "0"
    ["isInvoicePoint"] => string(1) "0"
    ["team_token"] => string(0) ""
    ["reward_rate"] => string(1) "0"
    ["commissionRate"] => string(1) "0"
    ["predeposit"] => string(4) "0.00"
    ["isDistributionSorter"] => string(1) "0"
    ["openLivePlay"] => string(1) "1"
     */

    // todo 新的 方法 获取 所有 店铺
    // 2022/10/10 刘超
    // https://api.gaoshouadmin.com/v3/index/mapLatLngToAddress?lat=45.76021&lng=126.66837
    public function mapLatLngToAddress(){
        $lat = (float)I('lat',45.76021);
        $lng = (float)I('lng',126.66837);
        $keyword = I('keyword');
        $con = [
            's.shopName' => ['like','%'.$keyword.'%']
        ];
        $list = M('shops')
            ->alias('s')
            ->where($con)
            ->field('
                s.shopId,
                s.shopName,
                s.shopAddress,
                s.latitude,
                s.longitude,
                round((st_distance(point(s.longitude,s.latitude),point('.$lng.','.$lat.')) * 111.195),2) as dis
            ')
            ->order('dis asc')
            ->select();
        // 返回数据
        $data['list'] = $list;
        $result = returnData($data);
        $this->ajaxReturn($result);
    }

 //todo  lp 复制超哥代码
     public function mapLatLngToAddress_new(){
         $lat = (float)I('lat',45.76021);
         $lng = (float)I('lng',126.66837);
         $keyword = I('keyword');
         $con = [
             'shopAtive'=>1,
             's.shopName' => ['like','%'.$keyword.'%']
         ];
         $list = M('shops')
             ->alias('s')
             ->where($con)
             ->field('
                 s.shopId,
                 s.shopName,
                 s.shopAddress,
                 s.latitude,
                 s.longitude,
                 round((st_distance(point(s.longitude,s.latitude),point('.$lng.','.$lat.')) * 111.195),2) as dis
             ')
             ->order('dis asc')
             ->select();
         // 返回数据
         $data['list'] = $list;
         $result = returnData($data);
         $this->ajaxReturn($result);
     }

    /**
     * 地图-逆地址解析-经纬度转地址
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/nmxndt
     * */
    public function mapLatLngToAddress_old()
    {
        $lat = (float)I('lat');
        $lng = (float)I('lng');
        if (empty($lat) || empty($lng)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', 'lat,lng字段参数必传'));
        }
        $mod = new ApiModel();
        $result = $mod->mapLatLngToAddress($lat, $lng);
        $this->ajaxReturn($result);
    }


    /**
     * 地图-地址-关键字搜索
     * 文档链接地址:https://www.yuque.com/youzhibu/qdmx37/fw2pgl
     * */
    public function mapPlaceByKeywords()
    {
        $keywords = I("keywords");//地址检索关键字
        $cityName = I("city_name");//城市名称 例子:上海市
        $lat = (float)I("lat"); //纬度
        $lng = (float)I("lng");//经度
        if (empty($keywords)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', 'keywords字段参数必传'));
        }
        $mod = new ApiModel();
        $result = $mod->mapPlaceByKeywords($keywords, $cityName, $lat, $lng);
        $this->ajaxReturn($result);
    }

    //    注销用户
    public function LogOffUser()
    {
        $userId = $this->MemberVeri()['userId'];
        $User = M("users"); // 实例化User对象
        $status = $User->where("userId={$userId}")->delete();

        if ($status) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::SUCCESS));
        } else {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '注销失败'));
        }

    }

}
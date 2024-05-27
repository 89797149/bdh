<?php

namespace Merchantapi\Action;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Shops\ShopsModule;
use Home\Model\ShopsModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺控制器
 */
class ShopsAction extends BaseAction
{
    /**
     * 跳到商家首页面
     */
    public function toShopHome()
    {
        $mshops = D('Home/Shops');
        $shopId = (int)I('shopId');
        //如果沒有传店铺ID进来则取默认自营店铺
        if ($shopId == 0) {
            $areaId2 = $this->getDefaultCity();
            $shopId = $mshops->checkSelfShopId($areaId2);
        }
        $shops = $mshops->getShopInfo($shopId);
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        $this->assign('shops', $shops);

        if (!empty($shops)) {
            $this->assign('shopId', $shopId);
            $this->assign('ct1', (int)I("ct1"));
            $this->assign('ct2', (int)I("ct2"));
            $this->assign('msort', (int)I("msort"));
            $this->assign('sj', I("sj", 0));
            $this->assign('sprice', I("sprice"));//上架开始时间
            $this->assign('eprice', I("eprice"));//上架结束时间
            $this->assign('goodsName', urldecode(I("goodsName")));//上架结束时间

            $mshopscates = D('Home/ShopsCats');
            $shopscates = $mshopscates->getShopCateList($shopId);
            $this->assign('shopscates', $shopscates);

            $mgoods = D('Home/Goods');
            $shopsgoods = $mgoods->getShopsGoods($shopId);
            $this->assign('shopsgoods', $shopsgoods);
            //获取评分
            $obj = array();
            $obj["shopId"] = $shopId;
            $shopScores = $mshops->getShopScores($obj);

            $this->assign("shopScores", $shopScores);

            $m = D('Home/Favorites');
            $this->assign("favoriteShopId", $m->checkFavorite($shopId, 1));
            $this->assign('actionName', ACTION_NAME);

            $this->assign('isSelf', $shops["isSelf"]);

        }
        $this->display("default/shop_home");
    }

    /**
     * 跳到店铺街
     */
    public function toShopStreet()
    {
        $searchdata = I('get.keyWords', '', 'strip_tags');//指定过滤方式
        $keyWords = iconv('gbk', 'utf-8', $searchdata);//转换编码
        $areas = D('Home/Areas');
        $areaId2 = $this->getDefaultCity();
        $areaList = $areas->getDistricts($areaId2);//获取城市下的区
        $mshops = D('Home/Shops');
        $obj = array();
        if ((int)cookie("bstreesAreaId3")) {
            $obj["areaId3"] = (int)cookie("bstreesAreaId3");
        } else {
            $obj["areaId3"] = ((int)I('areaId3') > 0) ? (int)I('areaId3') : $areaList[0]['areaId'];
            cookie("bstreesAreaId3", $obj["areaId3"]);
        }
        $this->assign('areaId3', $obj["areaId3"]);
        $this->assign('keyWords', $keyWords);
        $this->assign('areaList', $areaList);
        $this->display("default/shop_street");
    }

    /**
     * 获取县区内的商铺
     */
    public function getDistrictsShops()
    {
        $mshops = D('Home/Shops');
        $obj["areaId3"] = (int)I("areaId3");
        $obj["shopName"] = WSTAddslashes(I("shopName"));
        $obj["deliveryStartMoney"] = (float)I("deliveryStartMoney");
        $obj["deliveryMoney"] = (float)I("deliveryMoney");
        $obj["shopAtive"] = (int)I("shopAtive");
        cookie("bstreesAreaId3", $obj["areaId3"]);

        $dsplist = $mshops->getDistrictsShops($obj);
        $this->ajaxReturn($dsplist);
    }

    /**
     * 获取社区内的商铺
     */
    public function getShopByCommunitys()
    {

        $mshops = D('Home/Shops');
        $obj["communityId"] = (int)I("communityId");
        $obj["areaId3"] = (int)I("areaId3");
        $obj["shopName"] = WSTAddslashes(I("shopName"));
        $obj["deliveryStartMoney"] = (float)I("deliveryStartMoney");
        $obj["deliveryMoney"] = (float)I("deliveryMoney");
        $obj["shopAtive"] = (int)I("shopAtive", -1);
        $ctplist = $mshops->getShopByCommunitys($obj);
        $pages = $rslist["pages"];

        $this->assign('ctplist', $pages);
        $this->ajaxReturn($ctplist);

    }

    /**
     * 跳到商家登录页面
     */
    public function login()
    {
        $USER = session('WST_USER');
        if (!empty($USER) && $USER['userType'] == 1) {
            $this->redirect("Shops/index");
        } else {
            $this->display("default/shop_login");
        }
    }

    /**
     * 商家登录验证
     */
    public function checkLogin()
    {
//        $rs = array('status' => -2);
//        $rs["status"] = 1;
        //验证码，为了方便测试先注释了-------------
//        if (!$this->checkVerify("4") && ($GLOBALS['CONFIG']["captcha_model"]["valueRange"] != "" && strpos($GLOBALS['CONFIG']["captcha_model"]["valueRange"], "3") >= 0)) {
//            $rs["status"] = -2;
//            $rs["msg"] = "验证码错误";//验证码错误
//            $this->ajaxReturn($rs);
//        }
        //---------------------------------
        $type = I('type', 1);
        if ($type == 1) {//管理员登录
            $m = D('Home/Shops');
            $rs = $m->login();
            $userName = $rs['shop']['shopName'];
        } else {//超级管理员登录
            //根据接口是职员登录，不知道为啥写超级管理员登录
            $m = D('Home/User');
            $rs = $m->login();
            $userName = $rs['shop']['username'];
        }

//        if ($rs['status'] != 1) {
//            $rs["msg"] = "登录失败";
//            $this->ajaxReturn($rs);
//        }
        if ($rs['status'] != 1) {
            $rs["status"] = -1;
            $rs['msg'] = '账号密码错误';
            $this->ajaxReturn($rs);
        }
        $rs['msg'] = '登录成功';
        $shop = $rs['shop'];
        //生成用唯一token
        $code = 'shops';
        $memberToken = md5(uniqid('', true) . $code . $shop['shopId'] . $shop['loginName'] . (string)microtime());
        $shop['login_type'] = $type;
        if (!userTokenAdd($memberToken, $shop)) {
            $rs["status"] = -1;
            //$rs["msg"] = "登录失败";
            $rs["msg"] = "账号密码错误";

            $this->ajaxReturn($rs);
        }
        session('WST_USER', $rs['shop']);
        $params = [];
        $params['shopId'] = $shop['shopId'];
        $params['module_type'] = 2;
        $params['type'] = $type;
        if ($type == 2) {
            //职员id
            $params['userId'] = $shop['id'];
            $params['staffNid'] = $rs['staffNid'];
        } else {
            //总管理员id
            $params['userId'] = $shop['userId'];
        }
        $rs["data"] = array(
            'token' => $memberToken,
            'userName' => $userName,
            'routerInfo' => getUserPrivilege($params),//获取权限信息
        );
        //END

        unset($rs['shop'], $rs['shopInfo'], $rs['staffNid']);
        $this->ajaxReturn($rs);
    }

    /**
     * 退出
     */
    public function logout()
    {
        session('WST_USER', null);
        echo "1";
    }

    /**
     * 跳到商家中心页面
     */
    public function index()
    {
        $shopInfo = $this->MemberVeri();
        $spm = D('Home/Shops');
        $data['shop'] = $spm->loadShopInfo($shopInfo['userId']);
        $obj["shopId"] = $data['shop']['shopId'];
        $details = $spm->getShopDetails($obj);
        $data['details'] = $details;

        $this->assign('shopInfo', $data);

        $this->display("default/shops/index");
    }
//	/**
//	 * 编辑商家资料
//	 */
//	public function toEdit(){
//		$m = D('Home/Shops');
//        $shopInfo = $this->MemberVeri();
//		$shop = $m->get((int)$shopInfo['shopId']);
//
//		//获取银行列表
//		$m = D('Adminapi/Banks');
//		$this->assign('bankList',$m->queryByList(0));
//		//获取商品信息
//
//		$this->assign('object',$shop);
//		$this->assign("umark","toEdit");
//		$this->display("default/shops/edit_shop");
//	}


//	/**
//	 * 设置商家资料
//	 */
//	public function toShopCfg(){
//		$shopInfo = $this->MemberVeri();
//        $USER = session('WST_USER');
//		//获取商品信息
//		$m = D('Home/Shops');
//		$this->assign('object',$m->getShopCfg((int)$shopInfo['shopId']));
//		$this->assign("umark","setShop");
//		$this->display("default/shops/cfg_shop");
//	}

    /**
     * 获取商家配置
     */
    public function getShopCfg()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Shops');
        $data = $m->getShopCfg((int)$shopInfo['shopId']);
        $this->returnResponse(1, '获取成功', $data);

    }


    /**
     * 获取用户权限
     */
    public function getUserAuth()
    {
        $shopInfo = $this->MemberVeri();
        $type = $shopInfo['login_type'];//[2职员 1总管理员]

        $params = [];
        $params['shopId'] = $shopInfo['shopId'];
        $params['module_type'] = 2;
        $params['type'] = (int)$type;

        if ($type == 2) {
            //职员id
            $params['userId'] = $shopInfo['id'];
            $param = [];
            $param['userId'] = $shopInfo['id'];
            $param['shopId'] = $shopInfo['shopId'];
            $userModel = D('Home/User');
            $staffNid = $userModel->getStaffPower($param);
            $params['staffNid'] = $staffNid;
        } else {
            //总管理员id
            $params['userId'] = $shopInfo['userId'];
        }
        $data = getUserPrivilege($params);

        $this->ajaxReturn(returnData($data));


    }


    /**
     * 查询店铺名称是否存在
     */
    public function checkShopName()
    {
        $m = D('Home/Shops');
        $rs = $m->checkShopName(I('shopName'), (int)I('id'));
        echo json_encode($rs);
    }

    /**
     * 新增/修改操作
     */
    public function editShopCfg()
    {
        $shopInfo = $this->MemberVeri();
        $USER = session('WST_USER');
        $m = new ShopsModel();
        $rs = returnData(false, -1, -1, '请选择配置店铺信息');
        if ($shopInfo['shopId'] > 0) {
            $rs = $m->editShopCfg((int)$shopInfo['shopId']);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $shopInfo = $this->MemberVeri();
        $m = D('Home/Shops');
        $rs = array('status' => -1);
        if ($shopInfo['shopId'] > 0) {
            $rs = $m->edit((int)$shopInfo['shopId']);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 跳到修改用户密码
     */
    public function toEditPass()
    {
        $shopInfo = $this->MemberVeri();
        $this->assign("umark", "toEditPass");
        $this->display("default/shops/edit_pass");
    }

    /**
     * 申请开店
     */
    public function toOpenShopByUser()
    {
        $this->isUserLogin();
        $USER = session('WST_USER');
        if (!empty($USER) && $USER['userType'] == 0) {
            //获取用户申请状态
            $m = D('Home/Shops');
            $shop = $m->checkOpenShopStatus((int)$USER['userId']);

            if (empty($shop)) {
                //获取商品分类信息
                $m = D('Home/GoodsCats');
                $this->assign('goodsCatsList', $m->queryByList());
                //获取地区信息
                $m = D('Home/Areas');
                $this->assign('areaList', $m->getProvinceList());
                //获取所在城市信息
                $cityId = $this->getDefaultCity();
                $area = $m->getArea($cityId);
                $this->assign('area', $area);
                //获取银行列表
                $m = D('Home/Banks');
                $this->assign('bankList', $m->queryByList(0));
                $object = $m->getModel();
                $object['areaId1'] = $area['parentId'];
                $object['areaId2'] = $area['areaId'];
                $this->assign('object', $object);
                $this->display("default/users/open_shop");
            } else {
                if ($shop["shopStatus"] == 1) {
                    $shops = $m->loadShopInfo((int)$USER['userId']);
                    $USER = array_merge($USER, $shops);
                    session('WST_USER', $USER);
                    $this->assign('msg', '您的申请已通过，请刷新页面后点击右上角的"卖家中心"进入店铺界面.');
                    $this->display("default/users/user_msg");
                } else {
                    if ($shop["shopStatus"] == -1) {
                        $this->assign('msg', '您的申请审核不通过【原因：' . $shop["statusRemarks"] . '】,请<a style="color:blue;" href="' . U('Home/Shops/toEditShopByUser') . '"> 点击这里 </a>进行修改！');
                    } else {
                        $this->assign('msg', '您的申请正在审核中...');
                    }
                    $this->display("default/users/user_msg");
                }
            }
        } else {
            $this->redirect("Shops/index");
        }
    }

    /**
     * 申请开店
     */
    public function toEditShopByUser()
    {
        $this->isUserLogin();
        $USER = session('WST_USER');
        if (!empty($USER) && $USER['userType'] == 0) {
            //获取用户申请状态
            $sm = D('Home/Shops');
            $shop = $sm->checkOpenShopStatus((int)$USER['userId']);

            if ($shop["shopStatus"] == -1) {
                //获取商品分类信息
                $m = D('Home/GoodsCats');
                $this->assign('goodsCatsList', $m->queryByList());
                //获取地区信息
                $m = D('Home/Areas');
                $this->assign('areaList', $m->getProvinceList());
                //获取所在城市信息
                $cityId = $this->getDefaultCity();
                //$area = $m->getArea($cityId);
                //$this->assign('area',$area);
                //获取银行列表
                $m = D('Home/Banks');
                $this->assign('bankList', $m->queryByList(0));
                //$object = $m->getModel();
                $object = $sm->getShopByUser((int)$USER['userId']);

                $this->assign('object', $object);
                $this->display("default/users/open_shop");
            }
        } else {
            $this->redirect("Shops/index");
        }
    }

    /**
     * 会员提交开店申请
     */
    public function openShopByUser()
    {
        $this->isUserLogin();
        $rs = array('status' => -1);
        if ($GLOBALS['CONFIG']['phoneVerfy'] == 1) {
            $verify = session('VerifyCode_userPhone');
            $startTime = (int)session('VerifyCode_userPhone_Time');
            $mobileCode = I("mobileCode");
            if ((time() - $startTime) > 120) {
                $rs['msg'] = '验证码已失效!';
            }
            if ($mobileCode == "" || $verify != $mobileCode) {
                $rs['msg'] = '验证码错误!';
            }
        } else {
            if (!$this->checkVerify("1")) {
                $rs['msg'] = '验证码错误!';
            }
        }
        if ($rs['msg'] == '') {
            $USER = session('WST_USER');
            $m = D('Home/Shops');
            $userId = (int)$USER['userId'];
            $shop = $m->getShopByUser($userId);
            if ($shop['shopId'] > 0) {

                $rs = $m->edit((int)$shop['shopId'], true);
            } else {
                //如果用户没注册则先建立账号
                if ($userId > 0) {
                    $rs = $m->addByUser($userId);
                    if ($rs['status'] > 0) $USER['shopStatus'] = 0;
                }
            }
        }
        $this->ajaxReturn($rs);
    }


    /**
     * 游客跳到开店申请
     */
    public function toOpenShop()
    {

        //2018-3-16 修复已登陆用户 进入当前页面注册的bug
        $USER = session('WST_USER');
        if (!empty($USER) && $USER['userType'] == 0) {
            $this->redirect('Shops/toOpenShopByUser', null, 5, '<h1>页面跳转中...</h1>');

        }

        //获取商品分类信息
        $m = D('Home/GoodsCats');
        $this->assign('goodsCatsList', $m->queryByList());
        //获取省份信息
        $m = D('Home/Areas');
        $this->assign('areaList', $m->getProvinceList());
        //获取所在城市信息
        $cityId = $this->getDefaultCity();
        $area = $m->getArea($cityId);
        $this->assign('area', $area);
        //获取银行列表
        $m = D('Home/Banks');
        $this->assign('bankList', $m->queryByList(0));
        $object = $m->getModel();
        $this->assign('object', $object);
        $this->display("default/open_shop");

    }

    /**
     * 游客提交开店申请
     */
    public function openShop()
    {
        $m = D('Home/Shops');
        $rs = array('status' => -1);
        /*if($GLOBALS['CONFIG']['phoneVerfy']==1){
            $verify = session('VerifyCode_userPhone');
            $startTime = (int)session('VerifyCode_userPhone_Time');
            $mobileCode = I("mobileCode");
            if((time()-$startTime)>120){
                $rs['msg'] = '验证码已失效!';
            }
            if($mobileCode=="" || $verify != $mobileCode){
                $rs['msg'] = '验证码错误!';
            }
        }else{
            if(!$this->checkVerify("1")){
                $rs['msg'] = '验证码错误!';
            }
        }*/
        if ($rs['msg'] == '') {
            $rs = $m->addByVisitor();
            $m = D('Home/Users');
            $user = $m->get($rs['userId']);
            if (!empty($user)) session('WST_USER', $user);
        }
        if ($rs['userId']) {
            $this->returnResponse(1, '注册成功', []);
        } else {
            $this->returnResponse(-1, $rs['msg'] ? $rs['msg'] : '注册失败', []);
        }
//    	$this->ajaxReturn($rs);
    }

    /**
     * 获取店铺搜索提示列表
     */
    public function getKeyList()
    {
        $m = D('Home/Shops');
        $areaId2 = $this->getDefaultCity();
        $rs = $m->getKeyList($areaId2);
        $this->ajaxReturn($rs);
    }

    //获取银行列表
    public function getbankList()
    {
//        $shopInfo = $this->MemberVeri();
        $m = D('Adminapi/Banks');
//        $this->assign('bankList',$m->queryByList(0));
        $bankList = $m->queryByList(0);
        $this->returnResponse(1, '获取成功', array('list' => $bankList));
    }

    /**
     * 编辑商家资料
     */
    public function getShopInfo()
    {
        $m = D('Home/Shops');
        $shopInfo = $this->MemberVeri();
        $shop = $m->get((int)$shopInfo['shopId']);
        //获取商品信息
        $this->returnResponse(1, '获取成功', $shop);
    }


//######################  二开  ######################

    /**
     * 获取店铺统计信息
     */
    public function getShopDetails()
    {
        $shopInfo = $this->MemberVeri();
        $spm = D('Home/Shops');
        $obj["shopId"] = $shopInfo['shopId'];
        $details = $spm->getShopDetails($obj);
        $this->returnResponse(1, '获取成功', $details);
    }

    /**
     * 获取店铺每天的订单量和订单金额
     */
    public function getShopTodayOrder()
    {
        $shopInfo = $this->MemberVeri();
        $spm = D('Home/Shops');
        $obj["shopId"] = $shopInfo['shopId'];
        $details = $spm->getShopTodayOrder($obj);
        $this->returnResponse(1, '获取成功', $details);
    }

    /**
     * 创建进销存职员账号
     */
    public function createJxcUserAccount()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shop = $this->MemberVeri();
//        $shop = array('shopId'=>I('shopId',0,'intval'));

        if (empty($shop)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $spm = D('Merchantapi/Jxc');
        $result = $spm->createJxcUserAccount($shop);

        $this->ajaxReturn($result);
    }

    /**
     * 商户端-退出登陆
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fpk1oi
     * */
    public function loginOut()
    {
        $this->MemberVeri();
        $token = I('token');
        $m = D('Home/User');
        $data = $m->loginOut($token);
        $this->ajaxReturn($data);
    }

    /**
     * 系统首页-营业状况
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nwf0gw
     * */
    public function getShopBusinessStatus()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/Shops');
        $datetime = I('datetime', '');
        $data = $m->getShopBusinessStatus($loginUserInfo, $datetime);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 系统首页-待处理事务
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cxlq2t
     * */
    public function getShopPendingStatus()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/Shops');
        $data = $m->getShopPendingStatus($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 系统首页-门店消息
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kky2r8
     * */
    public function getShopMessage()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/Shops');
        $data = $m->getShopMessage($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 店铺配置修改-快捷修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/os99ga
     * */
    public function quickUpdateConfig(){
        $loginUserInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsReq['shopId'] = $loginUserInfo['shopId'];
        $mod = new ShopsModule();
        $result = $mod->quickUpdateConfig($paramsReq);
        if(!$result){
            $this->ajaxReturn(returnData(false,ExceptionCodeEnum::FAIL,'error','修改失败'));
        }
        $this->ajaxReturn(returnData(true));
    }
}
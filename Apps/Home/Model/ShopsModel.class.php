<?php

namespace Home\Model;

use App\Modules\Shops\ShopsModule;
use http\Encoding\Stream;
use http\QueryString;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺服务类
 */
class ShopsModel extends BaseModel
{
    /**
     * 查询店铺关键字
     */
    public function checkShopName($val, $id = 0)
    {
        $rd = array('status' => -1);
        if (!WSTCheckFilterWords($val, $GLOBALS['CONFIG']['limitAccountKeys'])) {
            $rd['status'] = -2;
            return $rd;
        }
        $sql = " shopName ='%s' and shopFlag=1 ";
        $keyArr = array($val);
        if ($id > 0) $sql .= " and shopId!=" . $id;
        $rs = $this->where($sql, $keyArr)->count();
        if ($rs == 0) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /**
     * 商家登录验证
     */
    public function login()
    {
        $rd = array('status' => -1);
        $loginName = WSTAddslashes(I('loginName'));
        $m = M('users');
        //$users = $m->where('(loginName="' . $loginName . '" or userPhone="' . $loginName . '" or userEmail="' . $loginName . '") and userFlag=1 and userStatus=1')->find();
        $users = $m->where('(loginName="' . $loginName . '") and userFlag=1 and userStatus=1')->find();
        // if ($users['loginPwd'] == md5(I('loginPwd') . $users['loginSecret']) && $users['userType'] >= 1) {
        if ($users['loginPwd'] == md5(I('loginPwd') . $users['loginSecret'])) {//修复用户无法登陆商户
            //加载商家信息
            $s = M('shops');
            $shops = $s->where('userId=' . $users['userId'] . " and shopFlag=1")->find();
            $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
            $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
            $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
            $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
            $users = array_merge($shops, $users);
            $rd['shop'] = $users;
            $rd['status'] = 1;
            $m->lastTime = date('Y-m-d H:i:s');
            $m->lastIP = get_client_ip();
            $m->where(' userId=' . $shops['userId'])->save();
            //记录登录日志
            $data = array();
            $data["userId"] = $shops['userId'];
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = get_client_ip();
            M('log_user_logins')->add($data);

        }
        return $rd;
    }

    /**
     * 加载商家信息
     */
    public function loadShopInfo($userId)
    {
        $shops = $this->queryRow('select s.*,u.userType,u.userPhone from __PREFIX__shops s,__PREFIX__users u where s.userId=u.userId and u.userId=' . $userId);
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        return $shops;
    }


    //---------更新达达门店信息-----------

    /**
     *    $rd['status'] = -6  更新门店出错
     *    $getData['shopName'] = $getData['shopName'];//门店名称
     *    $getData['shopId'] = $getData['shopId'];//店铺ID
     *    $getData['areaId3'] = $getData['areaId3'];//第三级城市id
     *    $getData['areaId1'] = $getData['areaId1'];//第一级城市id
     *    $getData['shopAddress'] = $getData['shopAddress'];//店铺地址
     *    $getData['longitude'] = $getData['longitude'];//经度
     *    $getData['latitude'] = $getData['latitude'];//纬度
     ***/
    static function updateDadaShop($getData)
    {
        $DaDaData_areas_mod = M('areas');
        $DaDaData_shops_mod = M('shops');

        $shops_res = $DaDaData_shops_mod->where('shopId = ' . $getData['shopId'])->find();

        unset($DaDaData);

        //	门店名称
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('shopName')->find()['shopName'] !== $getData["shopName"]) {
            $DaDaData['station_name'] = $getData["shopName"];
        }

        //城市名称
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('areaId2')->find()['areaId2'] !== $getData["areaId2"]) {
            $DaDaData['city_name'] = str_replace(array('省', '市'), '', $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName']);
        }

        //区域名称(如,浦东新区)
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('areaId3')->find()['areaId3'] !== $getData["areaId3"]) {
            $DaDaData['area_name'] = str_replace(array('区', '县'), '', $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName']);
        }

        //详细地址
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('shopAddress')->find()['shopAddress'] !== $getData["shopAddress"]) {
            $DaDaData['station_address'] =
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' . $getData["shopAddress"];
        }

        //门店经度
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('longitude')->find()['longitude'] !== $getData["longitude"]) {
            $DaDaData['lng'] = $getData["longitude"];
        }

        //门店纬度
        if ($DaDaData_shops_mod->where("shopId=" . $getData['shopId'])->field('latitude')->find()['latitude'] !== $getData["latitude"]) {
            $DaDaData['lat'] = $getData["latitude"];
        }

        //业务类型
        if (!empty($DaDaData)) {
            $DaDaData['business'] = 19;
        }


        $DaDaData['origin_shop_id'] = $shops_res['dadaOriginShopId'];


        if (!empty($shops_res['dadaShopId']) and !empty($DaDaData)) {
            unset($dadamod);

            $dadam = D("Home/dada");
            $dadamod = $dadam->apiShopUpdate($DaDaData, $shops_res['dadaShopId']);

            if (!empty($dadamod['niaocmsstatic'])) {
                $rd = array('status' => -6, 'data' => $dadamod, 'info' => '更新门店出错#' . $dadamod['info']);//更新门店出错
                return $rd;
            }
        }
    }


    /*
        *达达-当前城市是否开通达达物流
        $getData['shopId'];//店铺id
         $getData['areaId2'];//二级城市id
    */
    static function dadaIsCity($getData)
    {
        $shops_mod = M('shops');
        $order_areas_mod = M('areas');


        //判断当前城市是否开通了达达业务
        $shops_data_res = $shops_mod->where("shopId = '{$getData['shopId']}'")->find();

        $dadam = D("Home/dada");
        $dadamod = $dadam->cityCodeList(null, $shops_data_res['dadaShopId']);//线上环境
        //$dadamod = $dadam->cityCodeList(null,73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------

        if (!empty($dadamod['niaocmsstatic'])) {
            $rd = array('status' => -6, 'data' => $dadamod, 'info' => '获取城市出错#' . $dadamod['info']);//获取城市出错
            //return $rd;
            return false;
        }

        $cityNameisWx = str_replace(array('省', '市'), '', $order_areas_mod->where("areaId = '{$getData['areaId2']}'")->field('areaName')->find()['areaName']);
        //判断当前是否在达达覆盖范围内
        for ($i = 0; $i <= count($dadamod) - 1; $i++) {

            if ($cityNameisWx == $dadamod[$i]['cityName']) {//如果在配送范围
                return true;
            }
        }

        return false;

    }


    /**
     * 注册达达物流商户
     *$rd['status'] = 1  注册达达商户成功
     *$rd['status'] = -7  未开通城市
     *$rd['status'] = -4  注册达达物流商户出错
     *$rd['status'] = -5  创建门店出错
     */
    static function dadaLogistics($getData)
    {

        $m = M('shops');
        $DaDaData_areas_mod = M('areas');

        /*
        需要的字段
        $getData['shopId'] = $getData['shopId'];//店铺id
        $getData['areaId2'] = $getData['areaId2'];//二级城市id
        $getData['userPhone'] = $getData['userPhone'];//商家手机号
        $getData['areaId1'] = $getData['areaId1'];//第一级城市id
        $getData['shopCompany'] = $getData['shopCompany'];//公司名称
        $getData['areaId3'] = $getData['areaId3'];//第三级城市id
        $getData['shopAddress'] = $getData['shopAddress'];//门店地址
        $getData['userName'] = $getData['userName'];//用户名称
        $getData['qqNo'] = $getData['qqNo'];//用户QQ
        $getData['shopName'] = $getData['shopName'];//门店名称 */

        //判断当前城市是否开通了达达业务
        // $iscity = self::dadaIsCity($getData);
        // if(!$iscity){
        // 	return array('status'=>-7,'info'=>'未开通城市#');//未开通城市
        // }


        $DaDaData = array(
            'mobile' => $getData['userPhone'],
            'city_name' => str_replace(array('省', '市'), '', $DaDaData_areas_mod->where("areaId = '{$getData['areaId2']}'")->field('areaName')->find()['areaName']),
            'enterprise_name' => $getData['shopCompany'],
            'enterprise_address' =>
                $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'] . ',' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'] . ',' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'] . ',' . $getData["shopAddress"],
            'contact_name' => $getData["userName"],
            'contact_phone' => $getData['userPhone'],
            'email' => $getData['qqNo'] . '@qq.com'
        );


        unset($dadamod);
        $dadam = D("Home/dada");
        $dadamod = $dadam->merchantAdd($DaDaData);
        if (empty($dadamod['niaocmsstatic'])) {
            $shops_merchantAdd_dadaShopId['dadaShopId'] = $dadamod;
            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaShopId);
            $source_id = $dadamod;
        } else {

            $rd = array('status' => -4, 'data' => $dadamod, 'info' => '注册达达物流商户出错#' . $dadamod['info']);//注册达达物流商户出错
            return $rd;
        }

        //---------创建门店----------
        unset($DaDaData);
        $DaDaData = array(array(
            'station_name' => $getData["shopName"],//	门店名称
            'business' => 19,//业务类型
            'city_name' => str_replace(array('省', '市'), '', $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName']),//城市名称
            'area_name' => str_replace(array('区', '县'), '', $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName']),//区域名称(如,浦东新区)
            'station_address' => $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'] . ',' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'] . ',' .
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'] . ',' . $getData["shopAddress"],//------------------
            'lng' => $getData["longitude"],//门店经度
            'lat' => $getData["latitude"],//门店纬度
            'contact_name' => $getData["userName"],//联系人姓名
            'phone' => $getData['userPhone'],//	联系人电话
        ));


        //echo (json_encode($DaDaData));
        unset($dadamod);
        $dadamod = $dadam->apiShopAdd($DaDaData, $source_id);//返回数组
        //exit($dadamod);
        //$dadamod = json_decode($dadamod,true);


        if (!empty($dadamod['successList'][0]['originShopId'])) {
            $shops_merchantAdd_dadaOriginShopId['dadaOriginShopId'] = $dadamod['successList'][0]['originShopId'];
            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaOriginShopId);
            $rd = array('status' => 1, 'info' => '创建门店成功');//创建门店成功
            return $rd;
        } else {

            $rd = array('status' => -5, 'data' => $dadamod, 'info' => '创建门店出错#' . $dadamod['info']);//创建门店出错
            return $rd;
        }

    }


    /**
     * 游客开店
     */
    public function addByVisitor()
    {
        $rd = array('status' => -1);

        $userRules = array(
            array('loginName', 'require', '账号不能为空！', 1),
            array('loginPwd', 'require', '密码不能为空！', 1, '', 1),
            array('userName', 'require', '店主姓名不能为空！', 1, '', 1),
            array('userPhone', 'require', '手机号不能为空！', 1),
        );

        $shopRules = array(
            array('areaId1', 'integer', '请选择所在省份!', 1),
            array('areaId2', 'integer', '请选择所在城市!', 1),
            array('areaId3', 'integer', '请选择所在县区!', 1),
            array('goodsCatId1', 'integer', '请选择行业！', 1),
            array('shopName', 'require', '请输入店铺名称!', 1),
            array('shopCompany', 'require', '请输入公司名称!', 1),
            array('shopTel', 'require', '请输入公司!电话', 1),
            array('shopImg', 'require', '请上传公司图标!', 1),
            array('shopAddress', 'require', '请输入公司地址!', 1),
            array('bankId', 'integer', '请选择银行!', 1),
            array('bankNo', 'require', '请输入银行卡号!', 1),
            array('bankUserName', 'require', '请输入银行卡所有人名称!', 1),
            array('latitude', 'require', '请标记店铺地址!', 1),
            array('longitude', 'require', '请标记店铺地址!', 1),
            array('mapLevel', 'integer', '请标记店铺地址!', 1),
            array('isInvoice', array(0, 1), '无效的开发票状态！', 1, 'in'),
            array('serviceStartTime', 'double', '请选择店铺开始时间!', 1),
            array('serviceEndTime', 'double', '请选择店铺结束时间!', 1),
            array('deliveryType', array(0, 2), '请选择店铺配送方式!', 1, 'in')
        );
        $duser = D('Home/Users');
        //检测账号是否存在
        $hasLoginName = $duser->checkLoginKey(I("loginName"));
        if ($hasLoginName['status'] != 1) {
            $rd = array('status' => -2, 'msg' => ($hasLoginName['status'] == -2) ? "不能使用该账号" : "该账号已存在");
            return $rd;
        }
        $hasUserPhone = $duser->checkLoginKey(I("userPhone"), 0, false);
        if ($hasUserPhone['status'] != 1) {
            $rd = array('status' => -7, 'msg' => "该手机号已存在");;
            return $rd;
        }
        $hasShopName = $this->checkShopName(I('shopName'), 0);
        if ($hasShopName['status'] != 1) {
            $rd = array('status' => -8, 'msg' => ($hasShopName['status'] == -2) ? "不能使用该店铺名称" : "该店铺名称已存在");;
            return $rd;
        }
        $u = M('users');
        $s = M('shops');
        if (!$u->validate($userRules)->create()) {
            $rd['msg'] = $u->getError();
            return $rd;
        }
        if (!$s->validate($shopRules)->create()) {
            $rd['msg'] = $s->getError();
            return $rd;
        }
        if (I('relateAreaId') == '' && I('relateCommunityId') == '') {
            $rd['msg'] = '请选择配送区域!';
            return $rd;
        }
        $u->loginSecret = rand(1000, 9999);
        $u->loginPwd = md5($u->loginPwd . $u->loginSecret);
        $u->userStatus = 1;
        $u->userType = 0;
        $u->userFlag = 1;
        $u->createTime = date('Y-m-d H:i:s');
        M()->startTrans();//开启事物

        $userId = $u->add();
        if (false !== $userId) {
            $s->userId = $userId;
            $s->deliveryStartMoney = (float)I('deliveryStartMoney');
            $s->deliveryFreeMoney = (float)I("deliveryFreeMoney", 0);
            $s->deliveryMoney = (float)I("deliveryMoney", 0);
            $s->avgeCostMoney = (float)I("avgeCostMoney", 0);
            $s->deliveryCostTime = (int)I("deliveryCostTime", 0);
            $s->invoiceRemarks = I("invoiceRemarks");
            $s->qqNo = I("qqNo");
            $s->shopStatus = 0;
            $s->shopAtive = (int)I("shopAtive", 1) ? 1 : 0;
            $s->shopFlag = 1;
            $s->createTime = date('Y-m-d H:i:s');
            $s->deliveryLatLng = I("deliveryLatLng");
            $s->dadaShopId = I("dadaShopId");
            $s->dadaOriginShopId = I("dadaOriginShopId");
            $shopId = $s->add();
            if (false !== $shopId) {
                $rd['status'] = 1;
                $rd['userId'] = $userId;
                //增加商家评分记录
                $data = array();
                $data['shopId'] = $shopId;
                $m = M('shop_scores');
                $m->add($data);
                //建立店铺和社区的关系
                $relateArea = self::formatIn(",", I('relateAreaId'));
                $relateCommunity = self::formatIn(",", I('relateCommunityId'));
                if ($relateArea != '') {
                    $m = M('shops_communitys');
                    $relateAreas = explode(',', $relateArea);
                    foreach ($relateAreas as $v) {
                        if ($v == '' || $v == '0') continue;
                        $tmp = array();
                        $tmp['shopId'] = $shopId;
                        $tmp['areaId1'] = (int)I("areaId1");
                        $tmp['areaId2'] = (int)I("areaId2");
                        $tmp['areaId3'] = $v;
                        $tmp['communityId'] = 0;
                        $ra = $m->add($tmp);
                    }
                }
                if ($relateCommunity != '') {
                    $m = M('communitys');
                    $lc = $m->where('communityFlag=1 and (communityId in(0,' . $relateCommunity . ") or areaId3 in(0," . $relateArea . "))")->select();
                    if (count($lc) > 0) {
                        $m = M('shops_communitys');
                        foreach ($lc as $key => $v) {
                            $tmp = array();
                            $tmp['shopId'] = $shopId;
                            $tmp['areaId1'] = $v['areaId1'];
                            $tmp['areaId2'] = $v['areaId2'];
                            $tmp['areaId3'] = $v['areaId3'];
                            $tmp['communityId'] = $v['communityId'];
                            $ra = $m->add($tmp);
                        }
                    }
                }

                //记录登录日志
                $data = array();
                $data["userId"] = $userId;
                $data["loginTime"] = date('Y-m-d H:i:s');
                $data["loginIp"] = get_client_ip();
                M('log_user_logins')->add($data);
            }
        }


        //判断是否选择了达达物流 是的话 就进行处理
        //M()->commit();
        //M()->rollback();
        unset($isdeliveryType);
        $isdeliveryType = I('deliveryType');
        $dadaShopId = I('dadaShopId');
        $dadaOriginShopId = I('dadaOriginShopId');
        if ($isdeliveryType == 2 && empty($dadaShopId) && empty($dadaOriginShopId)) {

            $getData['shopId'] = $shopId;//店铺id
            $getData['areaId2'] = (int)I('areaId2');//二级城市id
            $getData['userPhone'] = I('userPhone');//商家手机号
            $getData['areaId1'] = (int)I('areaId1');//第一级城市id
            $getData['shopCompany'] = I('shopCompany');//公司名称
            $getData['areaId3'] = I('areaId3');//第三级城市id
            $getData['shopAddress'] = I('shopAddress');//门店地址
            $getData['userName'] = I('userName');//用户名称
            $getData['qqNo'] = I("qqNo");//用户QQ
            $getData['shopName'] = I('shopName');//门店名称

            $resDadaIsCity = self::dadaLogistics($getData);
            if ($resDadaIsCity['status'] == -7) {
                M()->rollback();
                $rd['status'] = -1;
                $rd['msg'] = '达达在当前地区未开通城市!';
                return $rd;
            }

            if ($resDadaIsCity['status'] == -4) {
                M()->rollback();
                $rd['status'] = -1;
                if (empty($resDadaIsCity['info'])) {
                    $rd['msg'] = '注册达达物流商户出错!';
                } else {
                    $rd['msg'] = $resDadaIsCity['info'];
                }
                //$rd['msg'] = '注册达达物流商户出错!';
                return $rd;
            }
            if ($resDadaIsCity['status'] == -5) {
                M()->rollback();
                $rd['status'] = -1;
                $rd['msg'] = '创建门店出错!';
                return $rd;
            }
            //注册达达商户成功 在成功返回前进行事物提交
            /* 	if($resDadaIsCity['status'] == 1){
                    M()->commit();
                } */


        }


        M()->commit();


        return $rd;
    }

    /**
     * 会员注册开通店铺
     */
    public function addByUser($userId)
    {
        $rd = array('status' => -1);
        //检测用户是否已经有开店申请或者开店了
        $sql = "select count(*) counts from __PREFIX__shops s where s.shopFlag=1 and userId=" . $userId;
        $checkRs = $this->queryRow($sql);
        if ($checkRs['counts'] > 0) {
            $rd['msg'] = '店铺申请已存在，请勿重复申请!';
            return $rd;
        }
        $userRules = array(
            array('userName', 'require', '店主姓名不能为空！', 1, '', 1),
            array('userPhone', 'require', '手机号不能为空！', 1),
        );
        //新注册账号
        $shopRules = array(
            array('areaId1', 'integer', '请选择所在省份!', 1),
            array('areaId2', 'integer', '请选择所在城市!', 1),
            array('areaId3', 'integer', '请选择所在县区!', 1),
            array('goodsCatId1', 'integer', '请选择行业！', 1),
            array('shopName', 'require', '请输入店铺名称!', 1),
            array('shopCompany', 'require', '请输入公司名称!', 1),
            array('shopTel', 'require', '请输入公司!电话', 1),
            array('shopImg', 'require', '请上传公司图标!', 1),
            array('shopAddress', 'require', '请输入公司地址!', 1),
            array('bankId', 'integer', '请选择银行!', 1),
            array('bankNo', 'require', '请输入银行卡号!', 1),
            array('bankUserName', 'require', '请输入银行卡所有人名称!', 1),
            array('latitude', 'require', '请标记店铺地址!', 1),
            array('longitude', 'require', '请标记店铺地址!', 1),
            array('mapLevel', 'integer', '请标记店铺地址!', 1),
            array('isInvoice', array(0, 1), '无效的开发票状态！', 1, 'in'),
            array('serviceStartTime', 'double', '请选择店铺开始时间!'),
            array('serviceEndTime', 'double', '请选择店铺结束时间!', 1),
            array('deliveryType', array(0, 2), '请选择店铺配送方式!', 1, 'in')
        );
        $hasShopName = $this->checkShopName(I('shopName'), 0);
        if ($hasShopName['status'] != 1) {
            $rd = array('status' => -8, 'msg' => ($hasShopName['status'] == -2) ? "不能使用该店铺名称" : "该店铺名称已存在");;
            return $rd;
        }
        $hasUserPhone = D('Adminapi/Users')->checkLoginKey(I("userPhone"), $userId, false);
        if ($hasUserPhone['status'] != 1) {
            $rd = array('status' => -7, 'msg' => '该手机号已存在!');;
            return $rd;
        }
        $u = M('users');
        $s = M('shops');
        if (!$u->validate($userRules)->create()) {
            $rd['msg'] = $u->getError();
            return $rd;
        }
        if (!$s->validate($shopRules)->create()) {
            $rd['msg'] = $s->getError();
            return $rd;
        }
        if (I('relateAreaId') == '' && I('relateCommunityId') == '') {
            $rd['msg'] = '请选择配送区域!';
            return $rd;
        }


        M()->startTrans();//开启事物


        $rs = $u->where('userId=' . $userId)->save();
        if (false !== $rs) {
            $s->userId = $userId;
            $s->isSelf = 0;
            $s->deliveryType = 0;
            $s->deliveryStartMoney = (float)I('deliveryStartMoney', 0);
            $s->deliveryFreeMoney = (float)I("deliveryFreeMoney", 0);
            $s->deliveryMoney = (float)I("deliveryMoney", 0);
            $s->avgeCostMoney = (float)I("avgeCostMoney", 0);
            $s->deliveryCostTime = (int)I("deliveryCostTime", 0);
            $s->shopStatus = 0;
            $s->shopAtive = (int)I("shopAtive", 1) ? 1 : 0;
            $s->shopFlag = 1;
            $s->createTime = date('Y-m-d H:i:s');
            $s->qqNo = I("qqNo");
            $s->invoiceRemarks = I("invoiceRemarks");
            $shopId = $s->add();
            if (false !== $shopId) {

                $rd['status'] = 1;
                //增加商家评分记录
                $data = array();
                $data['shopId'] = $shopId;
                $m = M('shop_scores');
                $m->add($data);
                //建立店铺和社区的关系
                $relateArea = I('relateAreaId');
                $relateCommunity = I('relateCommunityId');
                if ($relateArea != '') {
                    $m = M('shops_communitys');
                    $relateAreas = explode(',', $relateArea);
                    foreach ($relateAreas as $v) {
                        if ($v == '' || $v == '0') continue;
                        $tmp = array();
                        $tmp['shopId'] = $shopId;
                        $tmp['areaId1'] = (int)I("areaId1");
                        $tmp['areaId2'] = (int)I("areaId2");
                        $tmp['areaId3'] = $v;
                        $tmp['communityId'] = 0;
                        $ra = $m->add($tmp);
                    }
                }
                if ($relateCommunity != '') {
                    $m = M('communitys');
                    $lc = $m->where('communityFlag=1 and (communityId in(0,' . $relateCommunity . ") or areaId3 in(0," . $relateArea . "))")->select();
                    if (count($lc) > 0) {
                        $m = M('shops_communitys');
                        foreach ($lc as $key => $v) {
                            $tmp = array();
                            $tmp['shopId'] = $shopId;
                            $tmp['areaId1'] = $v['areaId1'];
                            $tmp['areaId2'] = $v['areaId2'];
                            $tmp['areaId3'] = $v['areaId3'];
                            $tmp['communityId'] = $v['communityId'];
                            $ra = $m->add($tmp);
                        }
                    }
                }
            }
        }


        //判断是否选择了达达物流 是的话 就进行处理
        //M()->commit();
        //M()->rollback();
        unset($isdeliveryType);
        $isdeliveryType = I('deliveryType');
        if ($isdeliveryType == 2) {

            $getData['shopId'] = $shopId;//店铺id
            $getData['areaId2'] = (int)I('areaId2');//二级城市id
            $getData['userPhone'] = I('userPhone');//商家手机号
            $getData['areaId1'] = (int)I('areaId1');//第一级城市id
            $getData['shopCompany'] = I('shopCompany');//公司名称
            $getData['areaId3'] = I('areaId3');//第三级城市id
            $getData['shopAddress'] = I('shopAddress');//门店地址
            $getData['userName'] = I('userName');//用户名称
            $getData['qqNo'] = I("qqNo");//用户QQ
            $getData['shopName'] = I('shopName');//门店名称

            $resDadaIsCity = self::dadaIsCity($getData);
            if ($resDadaIsCity['status'] == -7) {
                M()->rollback();
                $rd['status'] = -1;
                $rd['msg'] = '达达在当前地区未开通城市!';
                return $rd;
            }

            if ($resDadaIsCity['status'] == -4) {
                M()->rollback();
                $rd['status'] = -1;
                $rd['msg'] = '注册达达物流商户出错!';
                return $rd;
            }
            if ($resDadaIsCity['status'] == -5) {
                M()->rollback();
                $rd['status'] = -1;
                $rd['msg'] = '创建门店出错!';
                return $rd;
            }
            //注册达达商户成功 在成功返回前进行事物提交
            /* if($resDadaIsCity['status'] == 1){
                M()->commit();
            } */


        }


        M()->commit();
        return $rd;
    }

    /**
     * 修改
     */
    public function edit($shopId, $isApply = false)
    {

        M()->startTrans();//开启事物

        $rd = array('status' => -1);
        if ($shopId == 0) return $rd;
        $m = M('shops');
        //加载商店信息
        $shops = $m->where('shopId=' . $shopId)->find();
        $hasShopName = $this->checkShopName(I('shopName'), $shopId);
        if ($hasShopName['status'] == 0) {
            $rd = array('status' => -8, 'msg' => '该店铺名称已存在!');
            return $rd;
        }
        if (!WSTCheckFilterWords(I('shopName'), $GLOBALS['CONFIG']['limitAccountKeys'])) {
            $rd['msg'] = '不能使用该店铺名称';
            return $rd;
        }
        $data = array();
        $data["shopName"] = I("shopName");
        $data["shopCompany"] = I("shopCompany");
        $data["shopImg"] = I("shopImg");
        $data["shopAddress"] = I("shopAddress");
        $data["deliveryStartMoney"] = I("deliveryStartMoney", 0);
        $data["deliveryCostTime"] = I("deliveryCostTime", 0);
        $data["deliveryFreeMoney"] = I("deliveryFreeMoney", 0);
        $data["deliveryMoney"] = I("deliveryMoney", 0);
        $data["avgeCostMoney"] = I("avgeCostMoney", 0);
        $data["isInvoice"] = (int)I("isInvoice", 1);
        $data["serviceStartTime"] = I("serviceStartTime");
        $data["serviceEndTime"] = I("serviceEndTime");
        $data["shopAtive"] = (int)I("shopAtive", 1);
        $data["shopTel"] = I("shopTel");
        $data["bankId"] = (int)I("bankId");
        $data["bankNo"] = I("bankNo");
        $data["bankUserName"] = I("bankUserName");
        $data["deliveryType"] = I("deliveryType");
        //$data["sortingAutoDelivery"] = I("sortingAutoDelivery",0);//分拣完成自动发货配送

        $data['longitude'] = I('longitude');//经度嗯
        $data['latitude'] = I('latitude');//纬度

        if ($isApply) {
            $data["shopStatus"] = 0;
        }
        if ($this->checkEmpty($data, true)) {
            if (strlen($data['shopTel']) != 11) {
                //座机电话验证规则
                $chars = "/^([0-9]{3,4}-)?[0-9]{7,8}$/";
                if (!preg_match($chars, $data['shopTel'])) {
                    $rd['status'] = -1;
                    $rd['msg'] = '请输入正确的手机号码或电话号码';
                    return $rd;
                }
                if (strlen($data['shopTel']) == substr_count($data['shopTel'], '0')) {
                    $rd['status'] = -1;
                    $rd['msg'] = '店铺电话不合法';
                    return $rd;
                }
            } else {
                //手机号验证规则
                if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $data['shopTel'])) {
                    $rd['status'] = -1;
                    $rd['msg'] = '请输入正确的手机号码或电话号码';
                    return $rd;
                }
            }
            $data["qqNo"] = I("qqNo");
            $data["invoiceRemarks"] = I("invoiceRemarks");
            $data["deliveryLatLng"] = I("deliveryLatLng");
            $data["isInvoicePoint"] = I("isInvoicePoint");
            $data["dadaShopId"] = I("dadaShopId");
            $data["dadaOriginShopId"] = I("dadaOriginShopId");
            $data['team_token'] = I('team_token');//配送团队标识
            $data['reward_rate'] = I('reward_rate');//抽奖比例
            $data['isDistributionSorter'] = I('isDistributionSorter', 0);//是否分配分拣员(0：否 1：是)
            $data['openLivePlay'] = I('openLivePlay');//直播权限【0：关闭|1：开启】
            //$data['isDistributionBasket'] = I('isDistributionBasket',0);//是否分配筐位(0:否 1：是)
            //$data['sortingType'] = I('sortingType',0);//分拣方式,(0:按整笔订单   1：按商品),默认为0

            $rs = $m->where("shopId=" . $shopId)->save($data);
            if (false !== $rs) {
                S('WST_CACHE_RECOMM_SHOP_' . $shops['areaId2'], null);
                $USER = session('WST_USER');
                $data["serviceEndTime"] = str_replace('.', ':', $data["serviceEndTime"]);
                $data["serviceStartTime"] = str_replace('.', ':', $data["serviceStartTime"]);
                session('WST_USER', array_merge($USER, $data));
                $rd['status'] = 1;
                //修改用户资料
                $m = M('users');
                $data = array();
                $data['userName'] = I("userName");
                $m->where("userId=" . $shops['userId'])->save($data);
                if ($shops['isSelf'] == 0) {
                    //建立店铺和社区的关系
                    $relateArea = I('relateAreaId');
                    $relateCommunity = I('relateCommunityId');
                    $m = M('shops_communitys');
                    if ($relateArea != '') {
                        $m->where('shopId=' . $shopId)->delete();
                        $relateAreas = explode(',', $relateArea);
                        foreach ($relateAreas as $v) {
                            if ($v == '' || $v == '0') continue;
                            $tmp = array();
                            $tmp['shopId'] = $shopId;
                            $tmp['areaId1'] = (int)I("areaId1");
                            $tmp['areaId2'] = (int)I("areaId2");
                            $tmp['areaId3'] = $v;
                            $tmp['communityId'] = 0;
                            $ra = $m->add($tmp);
                        }
                    }
                    if ($relateCommunity != '') {
                        $m = M('communitys');
                        $lc = $m->where('communityFlag=1 and (communityId in(0,' . $relateCommunity . ") or areaId3 in(0," . $relateArea . "))")->select();
                        if (count($lc) > 0) {
                            $m = M('shops_communitys');
                            foreach ($lc as $key => $v) {
                                $tmp = array();
                                $tmp['shopId'] = $shopId;
                                $tmp['areaId1'] = $v['areaId1'];
                                $tmp['areaId2'] = $v['areaId2'];
                                $tmp['areaId3'] = $v['areaId3'];
                                $tmp['communityId'] = $v['communityId'];
                                $ra = $m->add($tmp);
                            }
                        }
                    }
                }
            }
        }


        //如果店铺存在达达店铺 则进行更新
        // if (!empty($shops['dadaShopId'])) {
        //     /*$getData['shopName'] = I("shopName");//门店名称
        //     $getData['areaId2'] = $shops['areaId2'];//二级城市
        //     $getData['shopId'] = $shopId;//店铺ID
        //     $getData['areaId3'] = $shops['areaId3'];//第三级城市id
        //     $getData['areaId1'] = $shops['areaId1'];//第一级城市id
        //     $getData['shopAddress'] = I('shopAddress');//店铺地址
        //     $getData['longitude'] = I('longitude');//经度
        //     $getData['latitude'] = I('latitude');//纬度

        //     $isok = self::updateDadaShop($getData);
        //     if($isok['status'] == -6){
        //         $rd['status']= -1;
        //         if(isset($isok['info']) && !empty($isok['info'])){
        //             $rd = array('msg' => $isok['info']);
        //         }else{
        //             $rd = array('msg'=>'更新达达门店出错');
        //         }
        //         //$rd = array('msg'=>'更新达达门店出错');
        //         M()->rollback();
        //         return $rd;
        //     }*/

        // } else {//否则判断是否选择了 达达物流 如果选择了 进行注册

        //     //if(I("deliveryType") == 2){
        //     if (I("deliveryType") == 2 && empty($data["dadaShopId"]) && empty($data["dadaOriginShopId"])) { //如果用户手动填写达达商户id和门店编号则不再注册达达
        //         $getData['shopId'] = $shopId;//店铺id
        //         $getData['areaId2'] = $shops['areaId2'];//二级城市id
        //         $getData['userPhone'] = I('shopTel');//商家手机号
        //         $getData['areaId1'] = $shops['areaId1'];//第一级城市id
        //         $getData['shopCompany'] = I('shopCompany');//公司名称
        //         $getData['areaId3'] = $shops['areaId3'];//第三级城市id
        //         $getData['shopAddress'] = I('shopAddress');//门店地址
        //         $getData['userName'] = I('userName');//用户名称
        //         $getData['qqNo'] = I('qqNo');//用户QQ
        //         $getData['shopName'] = I('shopName');//门店名称 */
        //         $getData['longitude'] = I("longitude");//经度
        //         $getData['latitude'] = I("latitude");//纬度

        //         $resDadaIsCity = self::dadaLogistics($getData);
        //         if ($resDadaIsCity['status'] == -7) {
        //             M()->rollback();
        //             $rd['status'] = -1;
        //             $rd['msg'] = '达达在当前地区未开通城市!';
        //             return $rd;
        //         }

        //         if ($resDadaIsCity['status'] == -4) {
        //             M()->rollback();
        //             $rd['status'] = -1;
        //             if (empty($resDadaIsCity['info'])) {
        //                 $rd['msg'] = '注册达达物流商户出错!';
        //             } else {
        //                 $rd['msg'] = $resDadaIsCity['info'];
        //             }
        //             //$rd['msg'] = '注册达达物流商户出错!';
        //             return $rd;
        //         }
        //         if ($resDadaIsCity['status'] == -5) {
        //             M()->rollback();
        //             $rd['status'] = -1;
        //             $rd['msg'] = '创建门店出错!';
        //             return $rd;
        //         }

        //     }


        // }


        M()->commit();
        return $rd;
    }


    /**
     * @param $shopId
     * @return mixed
     * 修改店铺设置
     */
    public function editShopCfg($shopId)
    {

        $mc = M('shop_configs');
        //加载商店信息
        $shopcg = $mc->where('shopId=' . $shopId)->find();

        $scdata = array();
        $scdata["shopId"] = $shopId;
        $scdata["shopTitle"] = I("shopTitle");
        $scdata["shopKeywords"] = I("shopKeywords");
        $scdata["shopBanner"] = I("shopBanner");
        $scdata["shopDesc"] = I("shopDesc");

        //该字段目前要求存储结构如下
        /**
         * 【广告管理】
         * 分隔符 $@$  用于分割 商品id/活动标识
         * 分隔符#@#  用于分割 幻灯片
         *  105$@$2 2代表活动页 105代表活动页标识
         * #@#50$@$1   #@#代表分割符分割轮播图   50代表商品id $@$1  1代表商品
         *shopAdsUrl:105$@$2#@#50$@$1
         */
        $scdata["appMiaosha"] = I("appMiaosha");
        $scdata["appYushou"] = I("appYushou");
        $scdata["AppRenqi"] = I("AppRenqi");

        $scdata["appMiaoshaSRC"] = I("appMiaoshaSRC");
        $scdata["appYushouSRC"] = I("appYushouSRC");
        $scdata["AppRenqiSRC"] = I("AppRenqiSRC");

        $scdata["appTypeimg"] = I("appTypeimg");
        $scdata["appTypeimg2"] = I("appTypeimg2");
        $scdata["appTypeimg3"] = I("appTypeimg3");

        $scdata["isSorting"] = I("isSorting");//是否自动分配拣货员(1：是 -1：否)
        $scdata["isReceipt"] = I("isReceipt");//是否自动接单(1：是 -1：否)
        if (is_numeric(I("sorting_threshold"))) {
            $scdata["sorting_threshold"] = I("sorting_threshold");//分拣阀值设置
        }
        if ($scdata["isReceipt"] == -1) {
            $scdata["advance_print_time"] = 0;
        }
        if ($scdata["isReceipt"] == 1) {
            $scdata["advance_print_time"] = (int)I("advance_print_time");//提前打印(分钟)
        }


        $scdata["isDis"] = I("isDis");//是否限制下单距离
        $scdata["sortingAutoDelivery"] = I("sortingAutoDelivery", 0);//分拣完成自动发货配送
        $scdata["overDeliveryLimit"] = I('overDeliveryLimit', 1); //订单是否超出配送范围(0:不限制 | 1:限制)
        $scdata["deliveryLatLngLimit"] = I('deliveryLatLngLimit', 1);//配送范围限制(0:不限制|1:限制)
        $scdata["relateAreaIdLimit"] = I('relateAreaIdLimit', 1);//配送区域限制(0:不限制|1:限制)
        $scdata['isDistributionBasket'] = I('isDistributionBasket', 0);//是否分配筐位(0:否 1：是)
        $scdata['sortingType'] = I('sortingType', 0);//分拣方式,(0:按整笔订单   1：按商品),默认为0
        $scdata['cashOnDelivery'] = I('cashOnDelivery', '-1');//开启货到付款【-1：未开启|1：已开启】
        $scdata['cashOnDeliveryCoupon'] = I('cashOnDeliveryCoupon', '-1');//货到付款支持优惠券【-1：不支持|1：支持】
        $scdata['cashOnDeliveryScore'] = I('cashOnDeliveryScore', '-1');//货到付款支持积分【-1：不支持|1：支持】
        $scdata['cashOnDeliveryMemberCoupon'] = I('cashOnDeliveryMemberCoupon', '-1');//货到付款会员券【-1：不支持|1：支持】
        if ($scdata["overDeliveryLimit"] == 1) {
            $scdata["deliveryLatLngLimit"] = 1;
            $scdata["relateAreaIdLimit"] = 1;
        }
        $scdata['open_suspension_chain'] = (int)I('open_suspension_chain');//是否开启悬挂链(0:不开启 1:开启)
        if ($scdata['isSorting'] != 1) {
            $scdata['open_suspension_chain'] = 0;
        }
        $scdata['open_time_limit_self'] = (int)I('open_time_limit_self');//开启限时秒杀仅自提(0:不开启 1:开启)
        $scdata['open_limit_num_self'] = (int)I('open_limit_num_self');//开启限量商品仅自提(0:不开启 1:开启)
        $scdata['time_limit_nocoupons'] = (int)I('time_limit_nocoupons');//是否限制限时秒杀不享受优惠券(0:不限制 1:限制)
        $scdata['limit_num_nocoupons'] = (int)I('limit_num_nocoupons');//是否限制限量商品不享受优惠券(0:不限制 1:限制)
        //$scdata["addShopTeamDesc"] = I('addShopTeamDesc');//店铺加群描述 PS:加群本地项目不需要,先注释
        //$scdata["addShopTeamPic"] = I('addShopTeamPic');//店铺加群图片
        $scdata['whetherPurchase'] = (int)I('whetherPurchase');//订单汇总-待采购商品(0:不勾选 1:勾选)
        $scdata['whetherMathStock'] = (int)I('whetherMathStock');//订单汇总-计算库存(0:不勾选 1:勾选)
        $scdata['whetherMathNoWarehouse'] = (int)I('whetherMathNoWarehouse');//订单汇总-计算在途(0:不勾选 1:勾选)
        //剔除 null或''  前端传null和''貌似接收都是 null所以还是让前端自己排除字段 后端不需要处理
        $scdata = array_filter($scdata, function ($v, $k) {
            if ($v === null or $v === '') {
                // if($v===null){
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        if (isset($_POST['shopAdsUrl'])) {
            $scdata["shopAdsUrl"] = I("shopAdsUrl");
        }
        if (isset($_POST['shopAds'])) {
            $scdata["shopAds"] = I("shopAds");
        }
        //单独添加自动接单字段验证----防止前后端传值错误造成不必要的问题!
        if (is_numeric($scdata['isReceipt']) && !in_array($scdata['isReceipt'], [1, -1])) {//是否自动接单(1：是 -1：否)
            return returnData(false, -1, -1, '请选择正确的自动接单状态');
        }
        if ($scdata['isReceipt'] == 1) {
            $shopsModule = new ShopsModule();
            $getPrintsInfo = $shopsModule->getPrintsList($shopId);
            if (!empty($getPrintsInfo)) {
                $isDefaultType = 0;//如果存在打印机判断是否存在默认【0:不存在|1:存在】
                foreach ($getPrintsInfo as $key => $value) {
                    if ($value['isDefault'] == 1) {//是否默认【0:否|1:默认】
                        $isDefaultType = 1;
                    }
                }
                if ($isDefaultType == 0) {
                    return returnData(false, -1, -1, '请配置默认打印机后再次开启自动受理功能');
                }
            }
        }
        if ($shopcg["configId"] > 0) {
            if (isset($scdata['isSorting']) && $scdata["isSorting"] != -1) {//是否自动分配拣货员(1：是 -1：否) 当开启分拣时需要判断当前店铺是否存在框位和分拣员
                $list = M('basket')->where(array('shopId' => $shopId, 'bFlag' => 1))->field('bid,orderNum')->select();
                if (empty($list)) {
                    return returnData(false, -1, -1, '请添加框位后开启分拣功能');
                }
                //获取分拣员
                $mod_sortingpersonnel = M('sortingpersonnel');
                $where = array();
                $where['shopid'] = $shopId;
                $where['isdel'] = 1;//是否删除(1：未删除 -1：已删除)
                $users = $mod_sortingpersonnel->where($where)->select();
                if (count($users) <= 0) {
                    return returnData(false, -1, -1, '请先添加拣货员后开启分拣功能');
                }
            }
            $rs = $mc->where("shopId=" . $shopId)->save($scdata);
        } else {
            $mc->add($scdata);
        }
        S('WST_CACHE_RECOMM_SHOP_' . $shopcg['areaId2'], null);
        return returnData(true, 0, 1, '操作成功');
    }

    /**
     * 获取指定对象
     */
    public function get($id)
    {
        $m = M('shops');
        $rs = $m->where("shopId=" . (int)$id)->find();
        $m = M('users');
        $us = $m->where("userId=" . $rs['userId'])->find();
        $rs['userName'] = $us['userName'];
        $rs['userPhone'] = $us['userPhone'];
        //获取店铺社区关系
        $m = M('shops_communitys');
        $rc = $m->where('shopId=' . (int)$id)->select();
        $relateArea = array();
        $relateCommunity = array();
        if (count($rc) > 0) {
            foreach ($rc as $v) {
                if ($v['communityId'] == 0 && !in_array($v['areaId3'], $relateArea)) $relateArea[] = $v['areaId3'];
                if (!in_array($v['communityId'], $relateCommunity)) $relateCommunity[] = $v['communityId'];
            }
        }
        $rs['relateArea'] = implode(',', $relateArea);
        $rs['relateCommunity'] = implode(',', $relateCommunity);
        $rs['deliveryLatLng'] = html_entity_decode($rs['deliveryLatLng']);
        return $rs;
    }

    public function getShopByUser($userId)
    {

        $m = M('users');
        $us = $m->where("userId=" . $userId)->find();

        $m = M('shops');
        $rs = $m->where("userId=" . $userId)->find();

        $rs['userName'] = $us['userName'];
        $rs['userPhone'] = $us['userPhone'];

        //获取店铺社区关系
        $m = M('shops_communitys');
        $rc = $m->where('shopId=' . (int)$rs["shopId"])->select();
        $relateArea = array();
        $relateCommunity = array();
        if (count($rc) > 0) {
            foreach ($rc as $v) {
                if ($v['communityId'] == 0 && !in_array($v['areaId3'], $relateArea)) $relateArea[] = $v['areaId3'];
                if (!in_array($v['communityId'], $relateCommunity)) $relateCommunity[] = $v['communityId'];
            }
        }
        $rs['relateArea'] = implode(',', $relateArea);
        $rs['relateCommunity'] = implode(',', $relateCommunity);
        return $rs;
    }

    /**
     * 获取指定对象
     */
    public function getShopCfg($id)
    {

        $mc = M('shop_configs');
        $rs = $mc->where("shopId=" . $id)->find();
        $shopAds = array();
        if ($rs["shopAds"] != '') {
            $shopAdsImg = explode('#@#', $rs["shopAds"]);
            $shopAdsUrl = explode('#@#', $rs["shopAdsUrl"]);
            for ($i = 0; $i < count($shopAdsImg); $i++) {
                $adsImg = $shopAdsImg[$i];
                $shopAds[$i]["adImg"] = $adsImg;
                $imgpaths = explode('.', $adsImg);
                $shopAds[$i]["adImg_thumb"] = $imgpaths[0] . "_thumb." . $imgpaths[1];
                $shopAds[$i]["adUrl"] = $shopAdsUrl[$i];

                $shopAds[$i]["adImg_thumb"] = rtrim($shopAds[$i]["adImg_thumb"], '#@');
                $shopAds[$i]["adUrl"] = rtrim($shopAds[$i]["adUrl"], '#@');
                $shopAds[$i]["adImg"] = rtrim($shopAds[$i]["adImg"], '#@');
            }
        }
        $rs['shopAds'] = $shopAds;
        $rs['advance_print_time'] = (float)$rs['advance_print_time'];
        return $rs;
    }

    /**
     * 获取店铺信息
     */
    public function getShopInfo($oshopId = 0)
    {
        $m = M('shops');
        $shopId = (int)I("shopId");
        $shopId = ($shopId == 0) ? $oshopId : $shopId;
        $rs = $m->where("shopStatus=1 and shopId=" . $shopId)->find();
        if (empty($rs)) return array();
        $mc = M('shop_configs');
        $spc = $mc->where("shopId=" . $shopId)->find();
        $shopAds = array();
        if ($spc["shopAds"] != '') {
            $shopAdsImg = explode('#@#', $spc["shopAds"]);
            $shopAdsUrl = explode('#@#', $spc["shopAdsUrl"]);
            for ($i = 0; $i < count($shopAdsImg); $i++) {
                $adsImg = $shopAdsImg[$i];
                $shopAds[$i]["adImg"] = $adsImg;
                $imgpaths = explode('.', $adsImg);
                $shopAds[$i]["adImg_thumb"] = $imgpaths[0] . "_thumb." . $imgpaths[1];
                $shopAds[$i]["adUrl"] = $shopAdsUrl[$i];
            }
        }
        $rs['shopAds'] = $shopAds;
        $rs['shopTitle'] = $spc["shopTitle"];
        $rs['shopDesc'] = $spc["shopDesc"];
        $rs['shopKeywords'] = $spc["shopKeywords"];
        $rs['shopBanner'] = $spc["shopBanner"];

        //热销排名
        $sql = "SELECT g.saleCount, g.shopId , g.goodsId , g.goodsName,g.goodsImg, g.goodsThums,g.shopPrice,g.marketPrice, g.goodsSn
						FROM __PREFIX__goods g
						WHERE g.goodsFlag = 1 AND g.isAdminBest = 1 AND g.isSale = 1 AND g.goodsStatus = 1 AND g.shopId = $shopId
						ORDER by g.saleCount desc limit 5";
        $hotgoods = $this->query($sql);
        $rs["hotgoods"] = $hotgoods;

        return $rs;
    }


    /**
     * 统计附近的商铺
     */
    public function getDistrictsShops($obj)
    {
        $m = M('areas');
        $areaId3 = (int)$obj["areaId3"];
        $shopName = WSTAddslashes($obj["shopName"]);
        $keyWords = WSTAddslashes(I("keyWords"));
        $deliveryStartMoney = $obj["deliveryStartMoney"];
        if ($deliveryStartMoney != -1) {
            $deliverys = explode("-", $deliveryStartMoney);
            $deliveryStart = intval($deliverys[0]);
            $deliveryEnd = intval($deliverys[1]);
        }

        $deliveryMoney = $obj["deliveryMoney"];
        if ($deliveryMoney != -1) {
            $mdeliverys = explode("-", $deliveryMoney);
            $mdeliveryStart = intval($mdeliverys[0]);
            $mdeliveryEnd = intval($mdeliverys[1]);
        }

        $shopAtive = (int)$obj["shopAtive"];


        $words = array();
        $words1 = array();
        $words2 = array();
        if ($keyWords != "") {
            $keyWords = urldecode($keyWords);
            $words1 = explode(" ", $keyWords);
        }


        $words = array();
        if ($shopName != "") {
            $keyWords = urldecode($shopName);
            $words2 = explode(" ", $keyWords);
        }
        $words = array_merge($words1, $words2);
        $dsplist = array();
        $sql = "SELECT communityId,communityName from __PREFIX__communitys WHERE communityFlag=1 AND isShow = 1 AND areaId3=" . $areaId3;
        $ctlist = $this->query($sql);
        $ctsplist = array();
        for ($k = 0; $k < count($ctlist); $k++) {
            $community = $ctlist[$k];
            $communityId = $community["communityId"];
            $sql = "SELECT count(*) as spcnt from __PREFIX__shops_communitys sc,__PREFIX__shops sp WHERE sp.shopStatus = 1 AND sp.shopFlag = 1 AND sc.shopId = sp.shopId AND communityId=" . $communityId;

            if (!empty($words)) {
                $sarr = array();
                foreach ($words as $key => $word) {
                    if ($word != "") {
                        $sarr[] = "sp.shopName LIKE '%$word%'";
                    }
                }
                $sql .= " AND (" . implode(" or ", $sarr) . ")";
            }
            /*if($keyWords!="" && $shopName!=""){
                $sql .= " AND (sp.shopName like '%$keyWords%' OR shopName like '%$shopName%')";
            }else{
                if($keyWords!=""){
                    $sql .= " AND sp.shopName like '%$keyWords%'";
                }
                if($shopName!=""){
                    $sql .= " AND sp.shopName like '%$shopName%'";
                }
            }*/
            if ($deliveryStart != "" && $deliveryStart >= 0) {
                $sql .= " AND deliveryStartMoney >= $deliveryStart";
            }
            if ($deliveryEnd != "" && $deliveryEnd > 0) {
                $sql .= " AND deliveryStartMoney < $deliveryEnd";
            }

            if ($mdeliveryStart != "" && $mdeliveryStart >= 0) {
                $sql .= " AND deliveryMoney >= $mdeliveryStart";
            }
            if ($mdeliveryEnd != "" && $mdeliveryEnd > 0) {
                $sql .= " AND deliveryMoney < $mdeliveryEnd";
            }

            if ($shopAtive != "" && $shopAtive >= 0) {
                $sql .= " AND shopAtive = $shopAtive";
            }
            $splist = $this->query($sql);
            $spcnt = $splist[0]["spcnt"];
            $community["spcnt"] = $spcnt;
            if ($spcnt > 0) $ctsplist[] = $community;
        }
        return $ctsplist;
    }

    /**
     * 统计附近的商铺
     */
    public function getShopByCommunitys($obj)
    {

        $communityId = (int)$obj["communityId"];
        $shopName = $obj["shopName"];
        $keyWords = WSTAddslashes(urldecode(I("keyWords")));
        $pcurr = (int)I("curr");
        $deliveryStartMoney = $obj["deliveryStartMoney"];
        if ($deliveryStartMoney != -1) {
            $deliverys = explode("-", $deliveryStartMoney);
            $deliveryStart = intval($deliverys[0]);
            $deliveryEnd = intval($deliverys[1]);
        }

        $deliveryMoney = $obj["deliveryMoney"];
        if ($deliveryMoney != -1) {
            $mdeliverys = explode("-", $deliveryMoney);
            $mdeliveryStart = intval($mdeliverys[0]);
            $mdeliveryEnd = intval($mdeliverys[1]);
        }

        $words = array();
        $words1 = array();
        $words2 = array();
        if ($keyWords != "") {
            $words1 = explode(" ", $keyWords);
        }


        $words = array();
        if ($shopName != "") {
            $keyWords = urldecode($shopName);
            $words2 = explode(" ", $keyWords);
        }
        $words = array_merge($words1, $words2);

        $shopAtive = $obj["shopAtive"];
        $dsplist = array();
        $sql = "SELECT sp.shopId,sp.shopName,sp.shopAddress,sp.deliveryStartMoney,sp.shopAtive,sp.deliveryMoney,sp.shopImg,sp.deliveryCostTime,sp.deliveryFreeMoney
		   ,sp.avgeCostMoney from __PREFIX__shops_communitys sc,__PREFIX__shops sp WHERE sp.shopStatus = 1 AND sp.shopFlag = 1 AND sc.shopId = sp.shopId AND sc.communityId=" . $communityId;

        /*if($keyWords!="" && $shopName!=""){
            $sql .= " AND (sp.shopName like '%$keyWords%' OR shopName like '%$shopName%')";
        }else{
            if($keyWords!=""){
                $sql .= " AND sp.shopName like '%$keyWords%'";
            }
            if($shopName!=""){
                $sql .= " AND sp.shopName like '%$shopName%'";
            }
        }*/

        if (!empty($words)) {
            $sarr = array();
            foreach ($words as $key => $word) {
                if ($word != "") {
                    $sarr[] = "sp.shopName LIKE '%$word%'";
                }
            }
            $sql .= " AND (" . implode(" or ", $sarr) . ")";
        }

        if ($deliveryStart != "" && $deliveryStart >= 0) {
            $sql .= " AND deliveryStartMoney >= $deliveryStart";
        }
        if ($deliveryEnd != "" && $deliveryEnd > 0) {
            $sql .= " AND deliveryStartMoney < $deliveryEnd";
        }

        if ($mdeliveryStart != "" && $mdeliveryStart >= 0) {
            $sql .= " AND deliveryMoney >= $mdeliveryStart";
        }
        if ($mdeliveryEnd != "" && $mdeliveryEnd > 0) {
            $sql .= " AND deliveryMoney < $mdeliveryEnd";
        }

        if ($shopAtive > -1) {
            $sql .= " AND shopAtive = $shopAtive";
        }
        $dslist = $this->pageQuery($sql, $pcurr);

        return $dslist;
    }

    /**
     * 统计店铺信息
     */
    public function getShopDetails($obj)
    {

        $shopId = (int)$obj["shopId"];
        $dsplist = array();
        $sql = "SELECT totalScore,totalScore ,
				       goodsScore,goodsUsers,
				       serviceScore,serviceUsers,
					   timeScore,timeUsers
				FROM __PREFIX__shop_scores WHERE shopId = $shopId";
        $scores = $this->queryRow($sql);
        $data = array();
        $data["goodsScore"] = $scores["goodsUsers"] ? round($scores["goodsScore"] / $scores["goodsUsers"]) : 0;
        $data["timeScore"] = $scores["timeUsers"] ? round($scores["timeScore"] / $scores["timeUsers"]) : 0;
        $data["serviceScore"] = $scores["serviceUsers"] ? round($scores["serviceScore"] / $scores["serviceUsers"]) : 0;
        //待审核商品
        $sql = "SELECT count(*) cnt FROM __PREFIX__goods WHERE goodsStatus = 0 and goodsFlag=1 and isSale=1 and shopId = $shopId";
        $goods = $this->queryRow($sql);
        $data["waitGoodsCnt"] = $goods["cnt"];
        //仓库中商品
        $sql = "SELECT count(*) cnt FROM __PREFIX__goods WHERE isSale = 0 and goodsFlag=1 AND shopId = $shopId";
        $goods = $this->queryRow($sql);
        $data["waitSaleGoodsCnt"] = $goods["cnt"];
        //出售中的商品
        $sql = "SELECT count(*) cnt FROM __PREFIX__goods WHERE isSale = 1 AND goodsStatus = 1 and goodsFlag=1 AND shopId = $shopId";
        $goods = $this->queryRow($sql);
        $data["onSaleGoodsCnt"] = $goods["cnt"];
        //买家留言
        $sql = "SELECT count(*) cnt FROM __PREFIX__goods_appraises WHERE shopId = $shopId";
        $appraises = $this->queryRow($sql);
        $data["appraisesCnt"] = $appraises["cnt"];
        //待受理订单
        $sql = "SELECT count(*) cnt FROM __PREFIX__orders WHERE shopId = $shopId AND orderStatus = 0 and orderFlag=1";
        $orders = $this->queryRow($sql);
        $data["waitHandleOrderCnt"] = $orders["cnt"];
        //待发货订单
        $sql = "SELECT count(*) cnt FROM __PREFIX__orders WHERE shopId = $shopId AND orderStatus in (1,2) and orderFlag=1";
        $orders = $this->queryRow($sql);
        $data["waitSendOrderCnt"] = $orders["cnt"];

        //待结束
        $sql = "SELECT count(*) cnt FROM __PREFIX__orders WHERE shopId = $shopId AND orderStatus in (3,-3) and orderFlag=1";
        $appOrders = $this->queryRow($sql);
        $data["appraisesOrderCnt"] = $appOrders["cnt"];

        //周订单量
        $wdate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));
        $sql = "SELECT count(*) cnt, sum(totalMoney) totalMoney FROM __PREFIX__orders WHERE shopId = $shopId AND createTime >='$wdate' and orderFlag=1 and orderStatus>=0 ";
        $orders = $this->queryRow($sql);
        $data["weekOrderCnt"] = $orders["cnt"];
        $data["weekOrderMoney"] = $orders["totalMoney"] ? $orders["totalMoney"] : 0;


        //一个月订单量
        $mdate = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")));
        $sql = "SELECT count(*) cnt, sum(totalMoney) totalMoney FROM __PREFIX__orders WHERE shopId = $shopId AND createTime >='$mdate' and orderFlag=1 and orderStatus>=0 ";
        $orders = $this->queryRow($sql);
        $data["monthOrderCnt"] = $orders["cnt"];
        $data["monthOrderMoney"] = $orders["totalMoney"] ? $orders["totalMoney"] : 0;
        return $data;
    }

    /**
     * 获取店铺评分
     */
    public function getShopScores($obj)
    {
        $shopId = (int)$obj["shopId"];
        $sql = "SELECT totalScore,totalScore ,goodsScore,goodsUsers,serviceScore,serviceUsers,timeScore,timeUsers
				FROM __PREFIX__shop_scores WHERE shopId = $shopId";
        $scores = $this->queryRow($sql);
        $data = array();
        $goodsScore = $scores["goodsUsers"] ? sprintf('%.1f', $scores["goodsScore"] / $scores["goodsUsers"]) : 0;
        $timeScore = $scores["timeUsers"] ? sprintf('%.1f', $scores["timeScore"] / $scores["timeUsers"]) : 0;
        $serviceScore = $scores["serviceUsers"] ? sprintf('%.1f', $scores["serviceScore"] / $scores["serviceUsers"]) : 0;
        $data["goodsScore"] = $goodsScore;
        $data["timeScore"] = $timeScore;
        $data["serviceScore"] = $serviceScore;
        return $data;
    }

    /**
     * 检查用户是否正在申请开店
     */
    public function checkOpenShopStatus($userId)
    {
        $m = M('shops');
        $sql = "select shopStatus,statusRemarks from __PREFIX__shops where userId=" . $userId . " and shopFlag=1 ";
        $row = $this->queryRow($sql);
        return $row;
    }


    /**
     * 获取自营店铺
     */
    public function getSelfShop($areaId2)
    {
        $m = M('shops');
        $sql = "select * from __PREFIX__shops where areaId2=" . $areaId2 . " and isSelf=1 and shopFlag=1 and shopStatus = 1";
        $shop = $this->queryRow($sql);
        return $shop;
    }

    /**
     * 检测自营店铺ID
     */
    function checkSelfShopId($areaId2)
    {
        $m = M('shops');
        $sql = "select shopId from __PREFIX__shops where areaId2=" . $areaId2 . " and isSelf=1 and shopFlag=1 and shopStatus = 1";
        $shop = $this->queryRow($sql);
        return (int)$shop['shopId'];
    }

    /**
     * 获取店铺搜索提示列表
     * @return \Think\mixed
     */
    public function getKeyList($areaId2)
    {
        $keywords = I("keywords");

        /***2016-10-1修复字符集问题导致无法正常搜索功能***/
        $searchdata = I('get.keyWords', '', 'strip_tags');//指定过滤方式
        $keyWords = iconv('gbk', 'utf-8', $searchdata);//转换编码

        $data = array();
        $data['shopStatus'] = 1;
        $data['areaId2'] = $areaId2;
        $data['shopFlag'] = 1;
        $data['shopName'] = array('like', '%' . $keywords . '%');
        $rs = $this->where($data)->distinct(true)->field('shopName as searchKey')->limit(10)->select();
        return $rs ? $rs : array();
    }

    /**
     * 获取店铺每天的订单量和订单金额
     */
    public function getShopTodayOrder($obj)
    {
        $startTime = date('Y-m-d', time()) . " 0:00:00";
        $endTime = date('Y-m-d', time()) . " 23:59:59";
        $orderNum = M('orders')->where("shopId='" . $obj['shopId'] . "' AND orderStatus IN(0,1,2,3,4) AND createTime >='" . $startTime . "' AND createTime <='" . $endTime . "'")->count('orderId');
        $realTotalMoney = M('orders')->where("shopId='" . $obj['shopId'] . "' AND orderStatus IN(0,1,2,3,4) AND createTime >='" . $startTime . "' AND createTime <='" . $endTime . "'")->sum('realTotalMoney');
        $totalMoney = M('orders')->where("shopId='" . $obj['shopId'] . "' AND orderStatus IN(0,1,2,3,4) AND createTime >='" . $startTime . "' AND createTime <='" . $endTime . "'")->sum('totalMoney');
        $info['orderNum'] = !empty($orderNum) ? $orderNum : 0;
        $info['totalMoney'] = !empty($totalMoney) ? $totalMoney : '0.00';
        $info['realTotalMoney'] = !empty($realTotalMoney) ? $realTotalMoney : '0.00';
        return $info;
    }

    /**
     *系统首页-营业状况
     * @param array $loginUserInfo 当前登陆者信息
     * @param string $datetime 时间 【today：今天|yesterday：昨天|lastSevenDays：最近7天|thisWeek：本周|lastThirtyDays：最近30天|thisMonth：本月|thisYear：本年|自定义(例子:2020-05-01 - 2020-05-31)】
     * */
    public function getShopBusinessStatus(array $loginUserInfo, string $datetime)
    {
        $shopId = $loginUserInfo['shopId'];
        $datetime = getDateRules($datetime);
        $startDate = $datetime['startDate'];
        $endDate = $datetime['endDate'];
        $posOrderTab = M('pos_orders');//线下收银表
        $ordersTab = M('orders');//线上订单表

        $where = [];
        $where['shopId'] = $shopId;
        $where['state'] = 3;
        if (!empty($datetime['startDate']) && !empty($datetime['endDate'])) {
            $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        }
        $posOrderNum = $posOrderTab->where($where)->count();//线下订单量
        $posOrderAmount = $posOrderTab->where($where)->sum('realpayment');//线下营业额

        $where = [];
        $where['shopId'] = $shopId;
        $where['orderStatus'] = ['EGT', 0];
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;
        //$where['isRefund'] = 0;
        if (!empty($datetime['startDate']) && !empty($datetime['endDate'])) {
            $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        }
        $lineOrderNum = $ordersTab->where($where)->count();//线上订单量
        $lineOrderAmount = $ordersTab->where($where)->sum('realTotalMoney');//线上营业额

        $orderNum = bc_math($lineOrderNum, $posOrderNum, 'bcadd', 0);//总订单量
        $orderAmount = bc_math($lineOrderAmount, $posOrderAmount, 'bcadd', 2);//总营业额
        $data['posOrderNum'] = (int)$posOrderNum;
        $data['posOrderAmount'] = formatAmount($posOrderAmount);
        $data['lineOrderNum'] = (int)$lineOrderNum;
        $data['lineOrderAmount'] = formatAmount($lineOrderAmount);
        $data['orderNum'] = (int)$orderNum;
        $data['orderAmount'] = formatAmount($orderAmount);
        return $data;
    }

    /**
     *系统首页-待处理事务
     * @param array $loginUserInfo 当前登陆者信息
     * */
    public function getShopPendingStatus(array $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        //$where['isSelf'] = 0;
        $where['orderStatus'] = ['IN', [1, 2]];
        $toBeDeliveredNum = $orderTab->where($where)->count();

        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['isSelf'] = 1;
        $where['orderStatus'] = ['IN', [3]];
        $toBePickedUpNum = $orderTab->where($where)->count();

//        $purchaseOrderTab = M('jxc_purchase_order');
//        $where = [];
//        $where['shopId'] = $shopId;
//        $where['status'] = 0;
//        $where['dataFlag'] = 1;
//        $noExamineNum = $purchaseOrderTab->where($where)->count();//待审核单据数量
        $purchaseOrderTab = M('purchase_bill');
        $where = [];
        $where['shopId'] = $shopId;
        $where['purchaseStatus'] = 0;
        $where['isDelete'] = 0;
        $noExamineNum = $purchaseOrderTab->where($where)->count();//待审核单据数量

//        $where = [];
//        $where['shopId'] = $shopId;
//        $where['status'] = 1;
//        $where['receivingStatus'] = 0;
//        $where['dataFlag'] = 1;
//        $alreadyExamineNum = $purchaseOrderTab->where($where)->count();//已审核待收货数量
//        $where = [];
//        $where['shopId'] = $shopId;
//        $where['status'] = 1;
//        $where['receivingStatus'] = 1;
//        $where['warehouseStatus'] = ['IN', [0, 1]];
//        $where['dataFlag'] = 1;
//        $warehouseStatusNum = $purchaseOrderTab->where($where)->count();//待入库&部分入库数量
//        $toBePurchasedNum = $noExamineNum + $alreadyExamineNum + $warehouseStatusNum;

        $where = [];
        $where['o.shopId'] = $shopId;
        $where['o.orderFlag'] = 1;
        $where['o.shopId'] = $shopId;
        $where['c.complainStatus'] = 0;
        $toBeReturned = M('order_complains c')
            ->join("left join wst_orders o on o.orderId=c.orderId")
            ->where($where)
            ->count();

        $where = [];
        $where['o.shopId'] = $shopId;
        $where['o.orderFlag'] = 1;
        $where['o.shopId'] = $shopId;
        $where['c.complainStatus'] = 1;
        $toBeConfirmReturn = M('order_complains c')
            ->join("left join wst_orders o on o.orderId=c.orderId")
            ->where($where)
            ->count();

        $where = [];
        $where['o.shopId'] = $shopId;
        $where['o.orderFlag'] = 1;
        $where['o.shopId'] = $shopId;
        $where['c.complainStatus'] = 2;
        $where['c.returnAmountStatus'] = 0;
        $toBeRefundApplication = M('order_complains c')
            ->join("left join wst_orders o on o.orderId=c.orderId")
            ->where($where)
            ->count();
        $data['toBeDelivered'] = (int)$toBeDeliveredNum;//待发货订单
        $data['toBePickedUp'] = (int)$toBePickedUpNum;//待取货订单
        //$data['toBePurchased'] = (int)$toBePurchasedNum;//采购单待处理 (待审核)+(已审核待收货)+(已收货待入库)
        $data['toBePurchased'] = (int)$noExamineNum;//采购单待处理 (待审核)
        $data['toBeReturned'] = (int)$toBeReturned;//待处理退货订单
        $data['toBeConfirmReturn'] = (int)$toBeConfirmReturn;//待确认退货订单
        $data['toBeRefundApplication'] = (int)$toBeRefundApplication;//待处理退款申请
        return $data;
    }

    /**
     *系统首页-门店消息
     * @param array $loginUserInfo 当前登陆者信息
     * @return array $data
     * */
    public function getShopMessage(array $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        $orderTab = M('orders');
        $goodsTab = M('goods');
        //订单相关
        $where = [];
        $where['shopId'] = $shopId;
        $where['orderFlag'] = 1;
        $where['orderStatus'] = -2;
        $toBePay = $orderTab->where($where)->count();//待付款
        $where['orderStatus'] = ['IN', [1, 2]];
        $toBeDeliver = $orderTab->where($where)->count();//待发货
        $where['orderStatus'] = 3;
        $alreadyDeliver = $orderTab->where($where)->count();//已发货
        $where['orderStatus'] = 4;
        $alreadyComplete = $orderTab->where($where)->count();//已完成
        //商品相关
        $sql = "select count('goodsId') as stockLack from __PREFIX__goods where goodsStock <= stockWarningNum and isSale=1 and goodsFlag = 1 and shopId = {$shopId}";
        $stockLack = $this->queryRow($sql)['stockLack'];//即将售罄商品
        $where = [];
        $where['shopId'] = $shopId;
        $where['goodsFlag'] = 1;
        $where['goodsStock'] = ['ELT', 0];
        $stockInsufficient = $goodsTab->where($where)->count();
        $data = [];
        $data['toBePay'] = (int)$toBePay;//待付款
        $data['toBeDeliver'] = (int)$toBeDeliver;//待发货
        $data['alreadyDeliver'] = (int)$alreadyDeliver;//已发货
        $data['alreadyComplete'] = (int)$alreadyComplete;//已完成 暂不展示
        $data['stockLack'] = (int)$stockLack;//即将售罄商品
        $data['stockInsufficient'] = (int)$stockInsufficient;//库存不足
        return $data;
    }
}

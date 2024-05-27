<?php

namespace Adminapi\Model;

use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\User\UserServiceModule;
use App\Modules\Users\UsersServiceModule;

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
     * @param $val
     * @param int $id
     * @return array
     * 查询店铺关键字
     */
    public function checkShopName($val, $id = 0)
    {
        $rd = array('status' => -1);
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
     * @param $val
     * @param int $id
     * @return int
     * 查询登录关键字 注册时 纯数字不能等于11位
     */
    public function checkLoginKey($val, $id = 0)
    {
        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1";
        $keyArr = array($val, $val, $val);
        if ($id > 0) $sql .= " and userId!=" . (int)$id;
        $m = M('users');
        $rs = $m->where($sql, $keyArr)->count();


        if ($rs == 0) {
            return 1;
        }
        return 0;
    }

    //---------更新达达门店信息-----------

    /**
     * @param $getData
     * @return array
     * $rd['status'] = -6  更新门店出错
     *
     * $getData['shopName'] = $getData['shopName'];//门店名称
     * $getData['areaId2'] = $getData['areaId2'];//二级城市
     * $getData['shopId'] = $getData['shopId'];//店铺ID
     * $getData['areaId3'] = $getData['areaId3'];//第三级城市id
     * $getData['areaId1'] = $getData['areaId1'];//第一级城市id
     * $getData['shopAddress'] = $getData['shopAddress'];//店铺地址
     * $getData['longitude'] = $getData['longitude'];//经度
     * $getData['latitude'] = $getData['latitude'];//纬度
     */
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
            $dadam = D("Adminapi/dada");
            $dadamod = $dadam->apiShopUpdate($DaDaData, $shops_res['dadaShopId']);

            if (!empty($dadamod['niaocmsstatic'])) {
                $rd = array('status' => -6, 'data' => $dadamod, 'info' => '更新门店出错#' . $dadamod['info'], 'source_id' => $shops_res['dadaShopId']);//更新门店出错
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

        $dadam = D("Adminapi/dada");
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
     * @param $getData
     * @return array
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
        $dadam = D("Adminapi/dada");
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
     * @param $userInfo
     * @return mixed
     * 新增店铺
     */
    public function insert($userInfo)
    {
        //先建立账号
        //用户资料
        $data = array();
        $data["loginSecret"] = rand(1000, 9999);
        $data["loginPwd"] = md5(I('loginPwd') . $data['loginSecret']);
        //店铺资料
        $sdata = array();
        $sdata["shopName"] = I("shopName");//门店名称
        $sdata["shopCompany"] = I("shopCompany");//公司名称
        $sdata["shopImg"] = I("shopImg");//门店图标
        $sdata["shopAddress"] = I("shopAddress");//门店地址
        $sdata["shopTel"] = I("shopTel");//门店电话
        $sdata["areaId1"] = I("areaId1");//所属省份ID
        $sdata["areaId2"] = I("areaId2");//所属市区ID
        $sdata["areaId3"] = I("areaId3");//所属县区ID
//        $sdata["goodsCatId1"] = I("goodsCatId1", '');//商店分类
        $sdata["bankId"] = I("bankId");//银行ID
        $sdata["bankNo"] = I("bankNo");//银行卡号
        $sdata["bankUserName"] = I("bankUserName");//银行卡所有人
        $sdata["serviceStartTime"] = I("serviceStartTime");//开始营业时间
        $sdata["serviceEndTime"] = I("serviceEndTime");//结束营业时间
        $sdata["commissionRate"] = I("commissionRate");//订单佣金比例（%）
        $sdata["shopAtive"] = (int)I("shopAtive", 1);//店铺营业状态(1:营业中 0：休息中)
        M()->startTrans();//开启事物
        if ($this->checkEmpty($data, true) && $this->checkEmpty($sdata, true)) {
            $sdata["serviceStartTime"] = floatval(str_replace(':', '.', I("serviceStartTime")));//开始营业时间
            $sdata["serviceEndTime"] = floatval(str_replace(':', '.', I("serviceEndTime")));//结束营业时间
            $sdata["shopSn"] = uniqid('shop-');//门店编号
            $data["userName"] = I("userName");//用户名称
            $data["userPhone"] = I("userPhone");//绑定手机
            $data["loginName"] = I("loginName");//账号
            $data["userType"] = 1;//用户类型(0:普通会员 1:商户)
//            $data["userScore"] = I("userScore", 0);//用户积分
//            $data["userFlag"] = 1;//账号状态[0:禁用|1:启用]
            $data["createTime"] = date('Y-m-d H:i:s');//创建时间
            $m = M('users');
            $usersServiceModule = new UsersServiceModule();
            $getUserInfo = $usersServiceModule->getUsersDetailByWhere(['loginName' => $data['loginName']], 'userId');
            $userId = (int)$getUserInfo['data']['userId'];
            if (!empty($userId)) {//是会员
                $m->where(array('userId' => $userId))->save(array('loginSecret' => $data["loginSecret"], 'loginName' => $data["loginName"], 'loginPwd' => $data["loginPwd"]));
            } else {//不是会员
                if (empty($data["loginName"])) {
                    return returnData(false, -1, 'error', '请输入登录账号');
                }
                $userId = $m->add($data);
            }
            if (false !== $userId) {
                $sdata["userId"] = $userId;//用户id
                $sdata["isSelf"] = (int)I("isSelf", 0);//是否自营(1:自营 0:非自营)
                $sdata["deliveryType"] = I("deliveryType", 0, 'intval');//配送方式(0:门店配送 | 1:商城配送 | 2：达达配送 | 3.蜂鸟配送 | 4：快跑者--自建配送团队 | 5：自建跑腿 | 6:自建司机)
                $sdata["deliveryStartMoney"] = I("deliveryStartMoney", 0);//配送订单起步价
                $sdata["deliveryCostTime"] = I("deliveryCostTime", 0);//配送承诺时间
                $sdata["deliveryFreeMoney"] = I("deliveryFreeMoney", 0);//包邮起步价
                $sdata["deliveryMoney"] = I("deliveryMoney", 0);//配送费
                $sdata["avgeCostMoney"] = I("avgeCostMoney", 0);//平均消费金额
                $sdata["longitude"] = (float)I("longitude");//店铺经度
                $sdata["latitude"] = (float)I("latitude");//店铺纬度
                $sdata["mapLevel"] = (int)I("mapLevel", 13);//缩放比例
                $sdata["isInvoice"] = (int)I("isInvoice", 1);//能否开发票(1:能 0:不能)
                $sdata["shopStatus"] = (int)I("shopStatus", 1);//店铺状态(-2:已停止 -1:拒绝 0：未审核 1:已审核)
                $sdata["shopAtive"] = (int)I("shopAtive", 1);//店铺营业状态(1:营业中 0：休息中)
                $sdata["shopFlag"] = 1;
                $sdata["createTime"] = date('Y-m-d H:i:s');//创建时间
                $sdata['statusRemarks'] = I('statusRemarks');//状态说明(一般用于停止和拒绝说明)
                $sdata['qqNo'] = I('qqNo');//店铺服务QQ号码
                $sdata["invoiceRemarks"] = I("invoiceRemarks");//发票说明
                $sdata["isInvoicePoint"] = I("isInvoicePoint");//发票点数
                $sdata["dadaShopId"] = I("dadaShopId");//配送平台商户id(达达 快跑者)
                $sdata["dadaOriginShopId"] = I("dadaOriginShopId");//达达门店编码
                $sdata["team_token"] = I("team_token");//配送团队标识(一般用于快跑者)
                $sdata["openLivePlay"] = I("openLivePlay", 0);//直播权限【0：关闭|1：开启】

                $m = M('shops');
                $shopId = $m->add($sdata);
                if ($shopId === false) {
                    M()->rollback();
                    return returnData(false, -1, 'error', '添加店铺失败');
                }
                $configShop = [];
                $configShop['shopId'] = $shopId;
                $configShop['isDis'] = -1;//是否限制下单距离(1:是 -1:否)
                $configShop['deliveryLatLngLimit'] = 0;//配送范围限制(0:不限制|1:限制)
                $configShop['relateAreaIdLimit'] = 0;//配送区域限制(0:不限制|1:限制)
                $shopConfig = M('shop_configs')->add($configShop);
                if (empty($shopConfig)) {
                    M()->rollback();
                    return returnData(false, -1, 'error', '添加店铺配置失败');
                }

                if (false !== $shopId) {
                    //复制商家的商品【单独接口】
//                    $param['shopSnCopy'] = I("shopSnCopy");
//                    $param['shopId'] = $shopId;
//                    copyShopGoods($param);
                    //增加商家评分记录
                    $data = array();
                    $data['shopId'] = $shopId;
                    $m = M('shop_scores');
                    $m->add($data);
                }
            }
        }

        //判断是否选择了达达物流 是的话 就进行处理
        unset($isdeliveryType);
        $isdeliveryType = I('deliveryType');
        if ($isdeliveryType == 2 && empty($sdata["dadaShopId"]) && empty($sdata["dadaOriginShopId"])) { //如果用户手动填写达达商户id和门店编号则不再注册达达
            $getData['shopId'] = $shopId;//店铺id
            $getData['areaId2'] = $sdata["areaId2"];//二级城市id
            $getData['userPhone'] = $data["userPhone"];//商家手机号
            $getData['areaId1'] = $sdata["areaId1"];//第一级城市id
            $getData['shopCompany'] = $sdata["shopCompany"];//公司名称
            $getData['areaId3'] = $sdata["areaId3"];//第三级城市id
            $getData['shopAddress'] = $sdata["shopAddress"];//门店地址
            $getData['userName'] = $data["userName"];//用户名称
            $getData['qqNo'] = $sdata['qqNo'];//用户QQ
            $getData['shopName'] = $sdata["shopName"];//门店名称

            $resDadaIsCity = self::dadaLogistics($getData);
            if ($resDadaIsCity['status'] == -7) {
                M()->rollback();
                return returnData(false, -1, 'error', '达达在当前地区未开通城市!');
            }

            if ($resDadaIsCity['status'] == -4) {
                M()->rollback();
                return returnData(false, -1, 'error', '注册达达物流商户出错!');
            }
            if ($resDadaIsCity['status'] == -5) {
                M()->rollback();
                return returnData(false, -1, 'error', '创建门店出错!');
            }
        }

        //提交事物
        M()->commit();
        $describe = "[{$userInfo['loginName']}]新增店铺:[{$sdata["shopName"]}]";
        addOperationLog($userInfo['loginName'], $userInfo['staffId'], $describe, 1);
        return returnData(true);
    }

    /**
     * @param $params
     * @param $userData
     * @return mixed
     * 编辑店铺配送范围
     */
    public function editShopDeliveryArea($params, $userData)
    {
        $shopId = (int)$params['shopId'];
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        if (empty($shopsInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        //按范围配送
        if (!empty($params['deliveryLatLng'])) {
            $save = [];
            $save['deliveryLatLng'] = $params['deliveryLatLng'];
            $save['deliveryLatLngName'] = $params['deliveryLatLngName'];//区域地址
            $shopsServiceModule->editShopsInfo($shopId, $save);
        }
        //按区域配送
        if (!empty($params['relateAreaId'])) {
            M('shops_communitys')->where(['shopId' => $shopId])->delete();
            $relateArea = self::formatIn(",", $params['relateAreaId']);
            $relateCommunity = self::formatIn(",", $params['relateCommunityId']);
            $relateAreas = explode(',', $relateArea);
            foreach ($relateAreas as $v) {
                if ($v == '' || $v == '0') continue;
                $tmp = array();
                $tmp['shopId'] = $shopId;
                $tmp['areaId1'] = (int)I("areaId1");
                $tmp['areaId2'] = (int)I("areaId2");
                $tmp['areaId3'] = $v;
                $tmp['communityId'] = 0;
                $res = M('shops_communitys')->add($tmp);
            }
            if (!empty($relateCommunity)) {
                $lc = M('communitys')->where('communityFlag=1 and (communityId in(0,' . $relateCommunity . ") or areaId3 in(0," . $relateArea . "))")->select();
                if (count($lc) > 0) {
                    foreach ($lc as $key => $v) {
                        $tmp = array();
                        $tmp['shopId'] = $shopId;
                        $tmp['areaId1'] = $v['areaId1'];
                        $tmp['areaId2'] = $v['areaId2'];
                        $tmp['areaId3'] = $v['areaId3'];
                        $tmp['communityId'] = $v['communityId'];
                        $res = M('shops_communitys')->add($tmp);
                    }
                }
            }
        }
        $describe = "[{$userData['loginName']}]编辑了店铺:[{$shopsInfo['data']['shopName']}]的配送范围";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
        return returnData(true);
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取店铺配送范围详情
     */
    public function getShopDeliveryArea($shopId)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        if (empty($shopsInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        $rest = [];
        //按范围配送
        $rest['deliveryLatLng'] = (string)$shopsInfo['data']['deliveryLatLng'];//配送区域(不规则局域 存储json字符串)
        $rest['deliveryLatLngName'] = (string)$shopsInfo['data']['deliveryLatLngName'];//区域地址
        $rc = M('shops_communitys')->where(['shopId' => $shopId])->select();
        $relateArea = array();
        $relateCommunity = array();
        if (count($rc) > 0) {
            foreach ($rc as $v) {
                if ($v['communityId'] == 0 && !in_array($v['areaId3'], $relateArea)) $relateArea[] = $v['areaId3'];
                if (!in_array($v['communityId'], $relateCommunity)) $relateCommunity[] = $v['communityId'];
            }
        }
        //按区域配送
        $rest['relateArea'] = implode(',', $relateArea);
        $rest['relateCommunity'] = implode(',', $relateCommunity);
        return returnData($rest, 0, 'success', '操作成功');
    }

    /**
     * @param $userData
     * @return mixed
     * 修改店铺信息
     */
    public function edit($userData)
    {
        $shopId = (int)I('shopId', 0);
        //获取店铺资料
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        if (empty($shopsInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        $shops = $shopsInfo['data'];
        $shopName = I("shopName");
        $data = array();
//        $data["shopSn"] = I("shopSn");
        $data["areaId1"] = I("areaId1");
        $data["areaId2"] = I("areaId2");
        $data["areaId3"] = I("areaId3");
        $data["goodsCatId1"] = I("goodsCatId1", '');
        $data["isSelf"] = (int)I("isSelf", 0);
        $data["deliveryType"] = I("deliveryType", 0, 'intval');

        $data["shopName"] = I("shopName");
        $data["shopCompany"] = I("shopCompany");
        $data["shopImg"] = I("shopImg");
        $data["shopAddress"] = I("shopAddress");
        $data["deliveryStartMoney"] = I("deliveryStartMoney", 0);
        $data["deliveryCostTime"] = I("deliveryCostTime", 0);
        $data["deliveryFreeMoney"] = I("deliveryFreeMoney", 0);
        $data["deliveryMoney"] = I("deliveryMoney", 0);
        $data["avgeCostMoney"] = I("avgeCostMoney", 0);
        $data["bankId"] = I("bankId");
        $data["bankNo"] = I("bankNo");
        $data["bankUserName"] = I("bankUserName");
        $data["longitude"] = I("longitude");
        $data["latitude"] = I("latitude");
        $data["mapLevel"] = (int)I("mapLevel", 13);
        $data["isInvoice"] = I("isInvoice", 1);
        $data["serviceStartTime"] = I('serviceStartTime');
        $data["serviceEndTime"] = I('serviceEndTime');
//        $data["shopStatus"] = (int)I("shopStatus", 0);
        $data["shopAtive"] = (int)I("shopAtive", 1);
        $data["shopTel"] = I("shopTel");
        $data["commissionRate"] = I("commissionRate");


        if ($this->checkEmpty($data, true)) {
            $data["serviceStartTime"] = floatval(str_replace(':', '.', I("serviceStartTime")));
            $data["serviceEndTime"] = floatval(str_replace(':', '.', I("serviceEndTime")));
            $data["statusRemarks"] = I("statusRemarks");
//            $data['qqNo'] = I('qqNo');
            $data["invoiceRemarks"] = I("invoiceRemarks");
            $data["isInvoicePoint"] = I("isInvoicePoint");
            $data["dadaShopId"] = I("dadaShopId");
            $data["dadaOriginShopId"] = I("dadaOriginShopId");
            $data["team_token"] = I("team_token");
            $data["openLivePlay"] = I("openLivePlay");
            $editShopInfo = $shopsServiceModule->editShopsInfo($shopId, $data);
            $rs = $editShopInfo['data'];
            if (false !== $rs) {
                //检查用户类型
                $usersServiceModule = new UsersServiceModule();
                $userInfo = $usersServiceModule->getUsersDetailByWhere(['userId' => $shops['userId']]);
                $userType = $userInfo['data'];

                //保存用户资料
                $data = array();
                $data["userName"] = I("userName");
                $data["userPhone"] = I("userPhone");


                //如果是普通用户则提升为店铺会员
                if ($userType['userType'] == 0) {
                    $data["userType"] = 1;
                }
                $usersServiceModule->updateUsersInfo($shops['userId'], $data);
            }
        }
        $describe = "[{$userData['loginName']}]编辑了店铺:[{$shopName}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
        return returnData(true);
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取店铺详情
     */
    public function get($shopId)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $rs = $shopsInfo['data'];
        if (empty($rs)) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        $rs["serviceStartTime"] = str_replace('.', ':', $rs["serviceStartTime"]);
        $rs["serviceEndTime"] = str_replace('.', ':', $rs["serviceEndTime"]);
        $usersServiceModule = new UsersServiceModule();
        $userInfo = $usersServiceModule->getUsersDetailByWhere(['userId' => $rs['userId']], 'userName,userPhone');
        $us = $userInfo['data'];
        $rs['userName'] = $us['userName'];
        $rs['userPhone'] = $us['userPhone'];
        //获取店铺社区关系
        $rc = M('shops_communitys')->where(['shopId' => $shopId])->select();
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
        return returnData($rs, 0, 'success', '操作成功');
    }

    /**
     * 停止或者拒绝店铺
     */
    public function reject()
    {
        $shopId = (int)I('id', 0);
        //获取店铺资料
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shops = $shopsInfo['data'];
        if (empty($shops)) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        $data = array();
        $data['shopStatus'] = (int)I('shopStatus', -1);
        $data['statusRemarks'] = I('statusRemarks');
        if ($this->checkEmpty($data, true)) {
            $shopsInfo = $shopsServiceModule->editShopsInfo($shopId, $data);
            $rs = (int)$shopsInfo['data'];
            if (false !== $rs) {
                //如果[已通过的店铺]被改为停止或者拒绝的话也要停止了该店铺的商品
                if ($shops['shopStatus'] != $data['shopStatus']) {
                    $shopMessage = '';
                    if ($data['shopStatus'] != 1) {
                        $saveInfo = [];
                        $saveInfo['isSale'] = 0;
                        $saveInfo['goodsStatus'] = 0;
                        $saveInfo['shopId'] = $shopId;
                        $goodsServiceModule = new GoodsServiceModule();
                        $goodsServiceModule->editGoodsInfo($saveInfo);
                        if ($data['shopStatus'] == 0) {
                            $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
                        } else {
                            $shopMessage = I('statusRemarks');
                        }
                    }
                    $yj_data = array(
                        'msgType' => 0,
                        'sendUserId' => session('WST_STAFF.staffId'),
                        'receiveUserId' => $shops['userId'],
                        'msgContent' => $shopMessage,
                        'createTime' => date('Y-m-d H:i:s'),
                        'msgStatus' => 0,
                        'msgFlag' => 1,
                    );
                    M('messages')->add($yj_data);
                }
                return returnData(true);
            }
        }
        return returnData(false, -1, 'error', '操作失败');
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * 获取店铺列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $shopId = (int)I('shopId', 0);
        $shopWords = WSTAddslashes(I('shopWords'));
        $shopName = WSTAddslashes(I('shopName'));
        if (!empty($shopName)) {
            $shopWords = $shopName;
        }
        $shopSn = WSTAddslashes(I('shopSn'));
        $field = "s.shopId,s.shopSn,s.shopName,s.shopImg ,s.shopAtive,s.shopStatus ,s.predeposit,s.openLivePlay,s.shopTel,s.serviceStartTime,s.serviceEndTime ";
        $field .= " ,u.userName,gc.catName,u.loginName,u.userPhone ";
        $field .= " ,a.areaName as areaName1,wa.areaName as areaName2,war.areaName as areaName3 ";
        $sql = "select {$field} from __PREFIX__shops s 
                left join __PREFIX__users u on u.userId = s.userId 
                left join __PREFIX__areas a on a.areaId = s.areaId1 
                left join __PREFIX__areas wa on wa.areaId = s.areaId2
                left join __PREFIX__areas war on war.areaId = s.areaId3
                left join __PREFIX__goods_cats gc on gc.catId = s.goodsCatId1 where s.shopStatus = 1 and s.shopFlag = 1 ";
        if (!empty($shopId)) {
            $sql .= " and s.shopId = {$shopId}";
        }
        //店铺名称|编号
        if (!empty($shopWords)) {
            $sql .= " and (s.shopName like '%{$shopWords}%' or s.shopSn like '%{$shopWords}%') ";
        }
        if (!empty($shopSn)) {
            $sql .= " and s.shopSn like '%{$shopSn}%'";
        }
        //所属省份ID
        if (!empty($areaId1)) {
            $sql .= " and s.areaId1 = {$areaId1}";
        }
        //所属市区ID
        if (!empty($areaId2)) {
            $sql .= " and s.areaId2 = {$areaId2}";
        }
        //所属县区ID
        if (!empty($areaId3)) {
            $sql .= " and s.areaId3 = {$areaId3}";
        }

        $sql .= "group by s.shopId order by s.shopId desc";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$item) {
                $item['loginName'] = (string)$item['loginName'];
                $item['userPhone'] = (string)$item['userPhone'];
            }
        }
        return $data;
    }

    /**
     * @param $shopId
     * @param $loginPwd
     * @param $userData
     * @return mixed
     * 重置店铺密码
     */
    public function updateShopPwd($shopId, $loginPwd, $userData)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shops = $shopsInfo['data'];
        if (empty($shops)) {
            return returnData(false, -1, 'error', '请查看店铺是否存在');
        }
        $userWhere = [];
        $userWhere['userId'] = (int)$shops['userId'];
        $userWhere['userFlag'] = 1;
//        $userWhere['userStatus'] = 1;
        $usersServiceModule = new UsersServiceModule();
        $getUserInfo = $usersServiceModule->getUsersDetailByWhere($userWhere);
        $users = $getUserInfo['data'];
        if (empty($users)) {
            return returnData(false, -1, 'error', '请查看店铺账户是否存在');
        }
        $save = [];
        $save["loginSecret"] = rand(1000, 9999);
        $save['loginPwd'] = md5($loginPwd . $save['loginSecret']);
        $editUserInfo = $usersServiceModule->updateUsersInfo($shops['userId'], $save);
        $date = $editUserInfo['code'];
        if ($date != -1) {
            $describe = "[{$userData['loginName']}]重置了店铺:[{$shops['shopName']}]的登录密码";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
            return returnData(true, 0, 'success', '重置密码成功');
        }
        return returnData(false, -1, 'error', '操作失败');
    }

    /**
     * @param $shopId
     * @param $shopUrl
     * @return array
     * 总后台快捷登录商户后台
     */
    public function shopTokenLogin($shopId, $shopUrl)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shops = $shopsInfo['data'];
        if (empty($shops)) {
            return returnData(false, -1, 'error', '请查看店铺是否存在');
        }
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceEndTime"] = str_replace('.', ':', $shops["serviceEndTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        $shops["serviceStartTime"] = str_replace('.', ':', $shops["serviceStartTime"]);
        $usersServiceModule = new UsersServiceModule();
        $getUserInfo = $usersServiceModule->getUsersDetailByWhere(['userId' => $shops['userId']]);
        $users = $getUserInfo['data'];
        $users = array_merge($shops, $users);
        $shops = $users;
        $userSave = [];
        $userSave['lastTime'] = date('Y-m-d H:i:s');
        $userSave['lastIP'] = get_client_ip();
        $usersServiceModule->updateUsersInfo($shops['userId'], $userSave);
        //记录登录日志
        $data = array();
        $data["userId"] = $shops['userId'];
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        M('log_user_logins')->add($data);

        //生成用唯一token
        $code = 'shops';
        $memberToken = md5(uniqid('', true) . $code . $shopId . $shops['loginName'] . (string)microtime());
        $shops['login_type'] = 1;
        userTokenAdd($memberToken, $shops);
        $res = $shopUrl . "?" . "token={$memberToken}";
        return returnData($res, 0, 'success', '操作成功');
    }

    /**
     * 获取待审核店铺列表
     */
    public function queryPeddingByPage($page = 1, $pageSize = 15)
    {
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $shopId = (int)I('shopId', 0);
        $shopWords = WSTAddslashes(I('shopWords'));
        $field = "s.shopId,s.shopSn,s.shopName,s.shopImg ,s.shopAtive,s.shopStatus ,s.predeposit,s.openLivePlay,s.shopTel,s.serviceStartTime,s.serviceEndTime ";
        $field .= " ,u.userName,gc.catName ";
        $field .= " ,a.areaName as areaName1,wa.areaName as areaName2,war.areaName as areaName3,gc.catName ";
        $sql = "select {$field} from __PREFIX__shops s 
                left join __PREFIX__users u on u.userId = s.userId 
                left join __PREFIX__areas a on a.areaId = s.areaId1 
                left join __PREFIX__areas wa on wa.areaId = s.areaId2
                left join __PREFIX__areas war on war.areaId = s.areaId3
                left join __PREFIX__goods_cats gc on gc.catId = s.goodsCatId1 where s.shopStatus <= 0 and s.shopFlag = 1 ";
        if (!empty($shopId)) {
            $sql .= " and s.shopId = {$shopId}";
        }
        //店铺名称|编号
        if (!empty($shopWords)) {
            $sql .= " and (s.shopName like '%{$shopWords}%' or s.shopSn like '%{$shopWords}%') ";
        }
        //所属省份ID
        if (!empty($areaId1)) {
            $sql .= " and s.areaId1 = {$areaId1}";
        }
        //所属市区ID
        if (!empty($areaId2)) {
            $sql .= " and s.areaId2 = {$areaId2}";
        }
        //所属县区ID
        if (!empty($areaId3)) {
            $sql .= " and s.areaId3 = {$areaId3}";
        }

        $sql .= "group by s.shopId order by s.shopId desc";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $list = $data['root'];
            foreach ($list as $key => &$value) {
                $value['catName'] = (string)$value['catName'];
            }
            unset($value);
            $data['root'] = $list;
        }
        return $data;
    }

    /**
     * 获取列表
     */
    public function queryByList()
    {
        $sql = "select * from __PREFIX__shops order by shopId desc";
        return $this->pageQuery($sql);
    }

    /**
     * @param $shopId
     * @param $userData
     * @return mixed
     * 删除店铺及相关信息
     */
    public function del($shopId, $userData)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shop = $shopsInfo['data'];
        if (empty($shop)) {
            return returnData(false, -1, 'error', '请查看店铺是否存在');
        }
        //下架所有商品
        $saveInfo = [];
        $saveInfo['shopId'] = $shopId;
        $saveInfo['isSale'] = 0;
        $saveInfo['goodsStatus'] = -1;
        $goodsServiceModule = new GoodsServiceModule();
        $goodsServiceModule->editGoodsInfo($saveInfo);
        //删除登录账号
        $sql = "delete From __PREFIX__users where userId = {$shop['userId']}";
        $this->execute($sql);
        //标记店铺删除状态
        $data = [];
        $data["shopFlag"] = -1;
        $data["shopStatus"] = -2;
        $saveShopInfo = $shopsServiceModule->editShopsInfo($shopId, $data);
        $rs = $saveShopInfo['data'];
        if (false !== $rs) {
            $describe = "[{$userData['loginName']}]删除了店铺:[{$shop['shopName']}]";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
            return returnData(true);
        }
        return returnData(false, -1, 'error', '操作失败');
    }

    /**
     * 获取待审核的店铺数量
     */
    public function queryPenddingShopsNum()
    {
        $rd = array('status' => -1);
        $sql = "select count(*) counts from __PREFIX__shops where shopStatus=0 and shopFlag=1";
        $rs = $this->query($sql);
        $rd['num'] = $rs[0]['counts'];
        return $rd;
    }

    /**
     * 获取店铺列表,不带分页
     */
    public function getShopList()
    {
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $sql = "select shopId,shopSn,shopName,u.userName,shopAtive,shopStatus,gc.catName from __PREFIX__shops s,__PREFIX__users u ,__PREFIX__goods_cats gc
	 	     where gc.catId=s.goodsCatId1 and s.userId=u.userId and shopStatus=1 and shopFlag=1 ";
        if (I('shopName') != '') $sql .= " and shopName like '%" . WSTAddslashes(I('shopName')) . "%'";
        if (I('shopSn') != '') $sql .= " and shopSn like '%" . WSTAddslashes(I('shopSn')) . "%'";
        if (I('shopId') > 0) $sql .= " and shopId=" . I('shopId');
        if ($areaId1 > 0) $sql .= " and areaId1=" . $areaId1;
        if ($areaId2 > 0) $sql .= " and areaId2=" . $areaId2;
        $sql .= " order by shopId desc";
        return $this->query($sql);
    }

    /**
     * 获取所有已审核店铺列表,不带分页
     */
    public function getAllShopList()
    {
        return M('shops')->where(array('shopStatus' => 1, 'shopFlag' => 1))->field('shopId,shopName')->select();
    }

    /*
     * 获取店铺分类
     * @param int $shopId 门店id
     * */
    public function getShopCatList(int $shopId)
    {
        $tab = M('shops_cats');
        $where = [];
        $where['shopId'] = $shopId;
        $where['catFlag'] = 1;
        $catlist = $tab
            ->where($where)
            ->select();
        $firstCats = [];
        foreach ($catlist as &$value) {
            if ($value['parentId'] == 0) {
                $value['child'] = [];
                $firstCats[] = $value;
            }
        }
        unset($value);
        foreach ($catlist as $value) {
            foreach ($firstCats as $firstCatKey => $firstCatValue) {
                if ($value['parentId'] == $firstCatValue['catId']) {
                    $firstCats[$firstCatKey]['child'][] = $value;
                }
            }
        }
        return returnData($firstCats);
    }

    /**
     * @param $shopWords
     * @return mixed
     * 获取店铺列表【用于搜索下拉列表】
     */
    public function getSearchShopsList($shopWords)
    {
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getSearchShopsList($shopWords, 'shopId,shopName');
        return returnData($shopsInfo['data']);
    }

    /**
     * @param $params
     * @param $userData
     * @return mixed
     * 复用店铺信息
     */
    public function addCopyShopInfo($params, $userData)
    {
        $shopId = $params['shopId'];
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shops = $shopsInfo['data'];
        if (empty($shops)) {
            return returnData(false, -1, 'error', '请查看复用店铺是否存在');
        }
        M()->startTrans();
        //门店绑定用户信息============start================================
        $data = [];
        $data["loginSecret"] = rand(1000, 9999);
        $data["loginPwd"] = md5($params['loginPwd'] . $data['loginSecret']);
        $data['userName'] = $params["userName"];
        $data["loginName"] = $params['loginName'];//账号
        $data["userType"] = 1;//用户类型(0:普通会员 1:商户)
        $data["createTime"] = date('Y-m-d H:i:s');//创建时间
        $usersServiceModule = new UsersServiceModule();
        $getUserInfo = $usersServiceModule->getUsersDetailByWhere(['loginName' => $data['loginName']], 'userId');
        $userId = (int)$getUserInfo['data']['userId'];
        if (!empty($userId)) {//是会员
            $userShopsInfo = $shopsServiceModule->getShopInfoByUserId($userId);
            $userShopsData = $userShopsInfo['data'];
            if (!empty($userShopsData)) {
                return returnData(false, -1, 'error', '该账号已绑定门店,请选择其他账号');
            }
            $save = [];
            $save['loginSecret'] = $data["loginSecret"];
            $save['loginName'] = $data["loginName"];
            $save['userName'] = $data["userName"];
            $save['loginPwd'] = $data["loginPwd"];
            $save['userType'] = 1;//用户类型(0:普通会员 1:商户)
            $editUserInfo = M('users')->where(array('userId' => $userId))->save($save);
            if (empty($editUserInfo)) {
                M()->rollback();
                return returnData(false, -1, 'error', '编辑门店所有人信息失败');
            }
        } else {//不是会员
            $userId = M('users')->add($data);
            if (empty($userId)) {
                M()->rollback();
                return returnData(false, -1, 'error', '新增门店所有人信息失败');
            }
        }
        //==========================end=================================
        //复用店铺配置信息============start==============================
        unset($shops['shopId']);
        $shops['createTime'] = date('Y-m-d H:i:s');
        $shops["shopSn"] = uniqid('shop-');//门店编号
        $shops["userId"] = $userId;//门店绑定用户id【门店所有人ID】
        unset($shops['loginName']);
        unset($shops['userPhone']);
        $newShopId = M('shops')->add($shops);
        if (empty($newShopId)) {
            M()->rollback();
            return returnData(false, -1, 'error', '复用店铺失败');
        }
        $newShopConfigId = M('shop_configs')->add(['shopId' => $newShopId]);
        if (empty($newShopConfigId)) {
            M()->rollback();
            return returnData(false, -1, 'error', '新增店铺配置失败');
        }
        //增加商家评分记录【这是统计评论的】
        $data = [];
        $data['shopId'] = $newShopId;
        $addScoresInfo = M('shop_scores')->add($data);
        if (empty($addScoresInfo)) {
            M()->rollback();
            return returnData(false, -1, 'error', '新增商家评分记录失败');
        }
        //复制商家的商品
        $param = [];
        $param['copyShopId'] = $shopId;//复用店铺id
        $param['shopId'] = $newShopId;//新增店铺id
        copyShopGoodsNew($param);
        M()->commit();
        $describe = "[{$userData['loginName']}]复用了店铺:[{$shops['shopName']}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
        return returnData(true);
    }

    /**
     * @param $params
     * @param $userData
     * @return mixed
     * 店铺状态变更
     * -2:已停止 1:已审核
     */
    public function editShopAuditStatus($params, $userData)
    {
        $shopId = $params['shopId'];
        //获取店铺资料
        $shopsServiceModule = new ShopsServiceModule();
        $shopsInfo = $shopsServiceModule->getShopsInfoById($shopId);
        $shops = $shopsInfo['data'];
        if (empty($shops)) {
            return returnData(false, -1, 'error', '暂无相关店铺数据');
        }
        if ($shops['shopStatus'] == $params['shopStatus']) {
            return returnData(false, -1, 'error', '状态未进行变更');
        }
        $data = [];
        $data['shopStatus'] = $params['shopStatus'];
        $data['statusRemarks'] = $params['statusRemarks'];
        $shopsInfo = $shopsServiceModule->editShopsInfo($shopId, $data);
        $rs = (int)$shopsInfo['data'];
        if (false !== $rs) {
            //如果[已通过的店铺]被改为停止或者拒绝的话也要停止了该店铺的商品
            if ($shops['shopStatus'] != $data['shopStatus']) {
                $shopMessage = '';
                $msg = '已审核';
                if ($data['shopStatus'] != 1) {
                    $saveInfo = [];
                    $saveInfo['isSale'] = 0;
                    $saveInfo['goodsStatus'] = 0;
                    $saveInfo['shopId'] = $shopId;
                    $goodsServiceModule = new GoodsServiceModule();
                    $goodsServiceModule->editGoodsInfo($saveInfo);
                    if ($data['shopStatus'] == 0) {
                        $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
                    } else {
                        $shopMessage = I('statusRemarks');
                    }
                    $msg = '已停止';
                }
                $yj_data = array(
                    'msgType' => 0,
                    'sendUserId' => session('WST_STAFF.staffId'),
                    'receiveUserId' => $shops['userId'],
                    'msgContent' => $shopMessage,
                    'createTime' => date('Y-m-d H:i:s'),
                    'msgStatus' => 0,
                    'msgFlag' => 1,
                );
                M('messages')->add($yj_data);
                $describe = "[{$userData['loginName']}]编辑了店铺:[{$shops['shopName']}]状态为{$msg}";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
            }
            return returnData(true);
        }
        return returnData(false, -1, 'error', '操作失败');
    }
}
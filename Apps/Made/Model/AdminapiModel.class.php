<?php
namespace Made\Model;
set_time_limit(0);

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订制模块
 * 总后台(Adminapi)
 */
class AdminapiModel extends BaseModel {
    /**
     * 注册达达物流商户
     *$rd['status'] = 1  注册达达商户成功
     *$rd['status'] = -7  未开通城市
     *$rd['status'] = -4  注册达达物流商户出错
     *$rd['status'] = -5  创建门店出错
     */
//    static function Admin_Shops_dadaLogistics($getData){
//
//        $m = M('shops');
//        $DaDaData_areas_mod = M('areas');
//
//        /*
//        需要的字段
//        $getData['shopId'] = $getData['shopId'];//店铺id
//        $getData['areaId2'] = $getData['areaId2'];//二级城市id
//        $getData['userPhone'] = $getData['userPhone'];//商家手机号
//        $getData['areaId1'] = $getData['areaId1'];//第一级城市id
//        $getData['shopCompany'] = $getData['shopCompany'];//公司名称
//        $getData['areaId3'] = $getData['areaId3'];//第三级城市id
//        $getData['shopAddress'] = $getData['shopAddress'];//门店地址
//        $getData['userName'] = $getData['userName'];//用户名称
//        $getData['qqNo'] = $getData['qqNo'];//用户QQ
//        $getData['shopName'] = $getData['shopName'];//门店名称 */
//
//        //判断当前城市是否开通了达达业务
//        // $iscity = self::dadaIsCity($getData);
//        // if(!$iscity){
//        // 	return array('status'=>-7,'info'=>'未开通城市#');//未开通城市
//        // }
//
//
//
//        $DaDaData = array(
//            'mobile'=> $getData['userPhone'],
//            'city_name'=> str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$getData['areaId2']}'")->field('areaName')->find()['areaName']),
//            'enterprise_name'=> $getData['shopCompany'],
//            'enterprise_address'=>
//                $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'].','.
//                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'].','.
//                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'].','.$getData["shopAddress"],
//            'contact_name'=> $getData["userName"],
//            'contact_phone'=> $getData['userPhone'],
//            'email'=> $getData['qqNo'].'@qq.com'
//        );
//
//
//        unset($dadamod);
//        $dadam = D("Admin/dada");
//        $dadamod = $dadam->merchantAdd($DaDaData);
//
//        if(empty($dadamod['niaocmsstatic'])){
//            $shops_merchantAdd_dadaShopId['dadaShopId'] = $dadamod;
//            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaShopId);
//            $source_id = $dadamod;
//        }else{
//
//            $rd = array('status'=>-4,'data'=>$dadamod,'info'=>'注册达达物流商户出错#'.$dadamod['info']);//注册达达物流商户出错
//            return $rd;
//        }
//
//        //---------创建门店----------
//        unset($DaDaData);
//        $DaDaData = array(array(
//            'station_name'=> $getData["shopName"],//	门店名称
//            'business'=> 19,//业务类型
//            'city_name'=>  str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName']),//城市名称
//            'area_name'=> str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName']),//区域名称(如,浦东新区)
//            'station_address'=> $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'].','.
//                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'].','.
//                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'].','.$getData["shopAddress"],//------------------
//            'lng'=> $getData["longitude"],//门店经度
//            'lat'=> $getData["latitude"],//门店纬度
//            'contact_name'=> $getData["userName"],//联系人姓名
//            'phone'=> $getData['userPhone'],//	联系人电话
//        ));
//
//
//        //echo (json_encode($DaDaData));
//        unset($dadamod);
//        $dadamod = $dadam->apiShopAdd($DaDaData,$source_id);//返回数组
//        //exit($dadamod);
//        //$dadamod = json_decode($dadamod,true);
//
//
//        if(!empty($dadamod['successList'][0]['originShopId'])){
//            $shops_merchantAdd_dadaOriginShopId['dadaOriginShopId'] = $dadamod['successList'][0]['originShopId'];
//            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaOriginShopId);
//            $rd = array('status'=>1,'info'=>'创建门店成功');//创建门店成功
//            return $rd;
//        }else{
//
//            $rd = array('status'=>-5,'data'=>$dadamod,'info'=>'创建门店出错#'.$dadamod['info']);//创建门店出错
//            return $rd;
//        }
//
//    }
//
//    /**
//     * 店铺管理->新增店铺
//     */
//    public function Admin_Shops_insert(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
//        //先建立账号
//        $hasLoginName = self::Admin_Shops_checkLoginKey(I("loginName"));
//        /*$hasUserPhone = self::checkLoginKey(I("userPhone"));
//        if($hasLoginName==0 || $hasUserPhone==0){
//            $rd = array('status'=>-2);
//            return $rd;
//        }*/
//        if($hasLoginName==0){
//            $rd = array('status'=>-2);
//            return $rd;
//        }
//        //用户资料
//        $data = array();
//        $data["loginName"] = I("loginName");
//        $data["loginSecret"] = rand(1000,9999);
//        $data["loginPwd"] = md5(I('loginPwd').$data['loginSecret']);
//        $data["userName"] = I("userName");
//        $data["userPhone"] = I("userPhone");
//        //店铺资料
//        $sdata = array();
//        $sdata["shopSn"] = I("shopSn");
//        $sdata["areaId1"] = (int)I("areaId1");
//        $sdata["areaId2"] = (int)I("areaId2");
//        $sdata["areaId3"] = (int)I("areaId3");
//        $sdata["goodsCatId1"] = (int)I("goodsCatId1");
//        $sdata["shopName"] = I("shopName");
//        $sdata["shopCompany"] = I("shopCompany");
//        $sdata["shopImg"] = I("shopImg");
//        $sdata["shopAddress"] = I("shopAddress");
//        $sdata["bankId"] = (int)I("bankId");
//        $sdata["bankNo"] = I("bankNo");
//        $sdata["bankUserName"] = I("bankUserName");
//        /*$sdata["serviceStartTime"] = I("serviceStartTime");
//        $sdata["serviceEndTime"] = I("serviceEndTime");*/
//        $sdata["serviceStartTime"] = "0.00";
//        $sdata["serviceEndTime"] = "23.59";
//        $sdata["shopTel"] = I("shopTel");
//        $sdata["commissionRate"] = I("commissionRate");
//        M()->startTrans();//开启事物
//        if($this->checkEmpty($data,true) && $this->checkEmpty($sdata,true)){
//            $data["userStatus"] = (int)I("userStatus",1);
//            $data["userType"] = (int)I("userType",1);
//            $data["userEmail"] = I("userEmail");
//            $data["userQQ"] = I("userQQ");
//            $data["userScore"] = I("userScore",0);
//            $data["userTotalScore"] = I("userTotalScore",0);
//            $data["userFlag"] = 1;
//            $data["createTime"] = date('Y-m-d H:i:s');
//            $m = M('users');
//            $userId = $m->add($data);
//            //$userId = (int)M('')->query("show table status like '__PREFIX__users'")[0]['Auto_increment'];
//
//            if(false !== $userId){
//                $sdata["userId"] = $userId;
//                $sdata["isSelf"] = (int)I("isSelf",0);
//                /* if($sdata["isSelf"]==1){
//                    $sdata["deliveryType"] = 1;
//                }else{
//                    $sdata["deliveryType"] = 0;
//                } */
//                //$sdata["deliveryType"] = I("deliveryType");
//                $sdata["deliveryType"] = 0;
//
//                $sdata["deliveryStartMoney"] = I("deliveryStartMoney",0);
//                $sdata["deliveryCostTime"] = I("deliveryCostTime",0);
//                $sdata["deliveryFreeMoney"] = I("deliveryFreeMoney",0);
//                $sdata["deliveryMoney"] = I("deliveryMoney",0);
//                $sdata["avgeCostMoney"] = I("avgeCostMoney",0);
//                $sdata["longitude"] = (float)I("longitude");
//                $sdata["latitude"] = (float)I("latitude");
//                $sdata["mapLevel"] = (int)I("mapLevel",13);
//                $sdata["isInvoice"] = (int)I("isInvoice",1);
//                $sdata["shopStatus"] = (int)I("shopStatus",1);
//                $sdata["shopAtive"] = (int)I("shopAtive",1);
//                $sdata["shopFlag"] = 1;
//                $sdata["createTime"] = date('Y-m-d H:i:s');
//                $sdata['statusRemarks'] = I('statusRemarks');
//                $sdata['qqNo'] = I('qqNo');
//                $sdata["invoiceRemarks"] = I("invoiceRemarks");
//                $sdata["deliveryLatLng"] = I("deliveryLatLng");
//                $sdata["isInvoicePoint"] = I("isInvoicePoint");
//                $sdata["dadaShopId"] = I("dadaShopId");
//                $sdata["dadaOriginShopId"] = I("dadaOriginShopId");
//                //$sdata["team_token"] = I("team_token");
//
//                $m = M('shops');
//                $shopId = $m->add($sdata);
//
//                if ($shopId === false) {
//                    // echo '添加店铺失败';
//                    return '添加店铺失败';
//                }
//                M('shop_configs')->add(array('shopId'=>$shopId));
//
//                //$shopId = (int)M('')->query("show table status like '__PREFIX__shops'")[0]['Auto_increment'];
//
//
//
//
//
//
//
//
//
//                /* //---------注册达达商户------------
//                $DaDaData_areas_mod = M('areas');
//                $DaDaData = array(
//                    'mobile'=> $data["userPhone"],
//                    'city_name'=> str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName']),
//                    'enterprise_name'=> $sdata["shopCompany"],
//                    'enterprise_address'=>
//                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName'].','.
//                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName'].','.
//                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName'].','.$sdata["shopAddress"],
//                    'contact_name'=> $data["userName"],
//                    'contact_phone'=> $data["userPhone"],
//                    'email'=> $sdata['qqNo'].'@qq.com'
//                );
//
//
//                //exit(json_encode($DaDaData));
//                $dadam = D("Admin/dada");
//                $dadamod = $dadam->merchantAdd($DaDaData);
//
//
//
//
//                if(empty($dadamod['niaocmsstatic'])){
//                    $shops_merchantAdd_dadaShopId['dadaShopId'] = $dadamod;
//                    $m->where("shopId = '{$shopId}'")->save($shops_merchantAdd_dadaShopId);
//                    $source_id = $dadamod;
//                }else{
//                    //事物回滚
//                    M()->rollback();
//                    $rd = array('status'=>-4,'data'=>$dadamod,'info'=>'注册达达物流商户出错#'.$dadamod['info']);//注册达达物流商户出错
//                    return $rd;
//                }
//
//                //---------创建门店----------
//                unset($DaDaData);
//                 $DaDaData = array(array(
//                    'station_name'=> $sdata["shopName"],//	门店名称
//                    'business'=> 19,//业务类型
//                    'city_name'=>  str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName']),//城市名称
//                    'area_name'=> str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName']),//区域名称(如,浦东新区)
//                    'station_address'=> $DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName'].','.
//                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName'].','.
//                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName'].','.$sdata["shopAddress"],
//                    'lng'=> $sdata["longitude"],//门店经度
//                    'lat'=> $sdata["latitude"],//门店纬度
//                    'contact_name'=> $data["userName"],//联系人姓名
//                    'phone'=> $data["userPhone"],//	联系人电话
//                ));
//
//
//                //echo (json_encode($DaDaData));
//                unset($dadamod);
//                $dadamod = $dadam->apiShopAdd($DaDaData,$source_id);//返回数组
//                //exit($dadamod);
//                //$dadamod = json_decode($dadamod,true);
//
//
//
//                if(!empty($dadamod['successList'][0]['originShopId'])){
//                    $shops_merchantAdd_dadaOriginShopId['dadaOriginShopId'] = $dadamod['successList'][0]['originShopId'];
//                    $m->where("shopId = '{$shopId}'")->save($shops_merchantAdd_dadaOriginShopId);
//                    //提交事物
//                    M()->commit();
//                }else{
//                    //否则回滚
//                    M()->rollback();
//                    $rd = array('status'=>-5,'data'=>$dadamod,'info'=>'创建门店出错#'.$dadamod['info']);//创建门店出错
//                    return $rd;
//                }
//                 */
//
//
//
//                if(false !== $shopId){
//                    //复制商家的商品
//                    $param['shopSnCopy'] = I("shopSnCopy");
//                    $param['shopId'] = $shopId;
//                    copyShopGoods($param);
//                    $rd['code']= 0;
//                    $rd['msg'] = "操作成功";
//                    //增加商家评分记录
//                    $data = array();
//                    $data['shopId'] = $shopId;
//                    $m = M('shop_scores');
//                    $m->add($data);
//                    //建立店铺和社区的关系
//
//                    $relateArea = self::formatIn(",", I('relateAreaId'));
//                    $relateCommunity = self::formatIn(",", I('relateCommunityId'));
//                    if($relateArea!=''){
//                        $m = M('shops_communitys');
//                        $relateAreas = explode(',',$relateArea);
//                        foreach ($relateAreas as $v){
//                            if($v=='' || $v=='0')continue;
//                            $tmp = array();
//                            $tmp['shopId'] = $shopId;
//                            $tmp['areaId1'] = (int)I("areaId1");
//                            $tmp['areaId2'] = (int)I("areaId2");
//                            $tmp['areaId3'] = $v;
//                            $tmp['communityId'] = 0;
//                            $ra = $m->add($tmp);
//                        }
//                    }
//                    if($relateCommunity!=''){
//                        $m = M('communitys');
//                        $lc = $m->where('communityFlag=1 and (communityId in(0,'.$relateCommunity.") or areaId3 in(0,".$relateArea."))")->select();
//                        if(count($lc)>0){
//                            $m = M('shops_communitys');
//                            foreach ($lc as $key => $v){
//                                $tmp = array();
//                                $tmp['shopId'] = $shopId;
//                                $tmp['areaId1'] = $v['areaId1'];
//                                $tmp['areaId2'] = $v['areaId2'];
//                                $tmp['areaId3'] = $v['areaId3'];
//                                $tmp['communityId'] = $v['communityId'];
//                                $ra = $m->add($tmp);
//                            }
//                        }
//                    }
//                }
//
//            }
//
//        }
//
//
//
//        //判断是否选择了达达物流 是的话 就进行处理
//        //M()->commit();
//        //M()->rollback();
//        unset($isdeliveryType);
//        $isdeliveryType = I('deliveryType');
//        if($isdeliveryType == 2 && empty($sdata["dadaShopId"]) && empty($sdata["dadaOriginShopId"])){ //如果用户手动填写达达商户id和门店编号则不再注册达达
//
//            $getData['shopId'] = $shopId;//店铺id
//            $getData['areaId2'] = $sdata["areaId2"];//二级城市id
//            $getData['userPhone'] =  $data["userPhone"];//商家手机号
//            $getData['areaId1'] = $sdata["areaId1"];//第一级城市id
//            $getData['shopCompany'] = $sdata["shopCompany"];//公司名称
//            $getData['areaId3'] = $sdata["areaId3"];//第三级城市id
//            $getData['shopAddress'] = $sdata["shopAddress"];//门店地址
//            $getData['userName'] = $data["userName"];//用户名称
//            $getData['qqNo'] = $sdata['qqNo'];//用户QQ
//            $getData['shopName'] = $sdata["shopName"];//门店名称
//
//            $resDadaIsCity = self::Admin_Shops_dadaLogistics($getData);
//            if($resDadaIsCity['status'] == -7){
//                M()->rollback();
//                $rd['status']= -1;
//                $rd['msg'] = '达达在当前地区未开通城市!';
//                return $rd;
//            }
//
//            if($resDadaIsCity['status'] == -4){
//                M()->rollback();
//                $rd['status']= -1;
//                $rd['msg'] = '注册达达物流商户出错!';
//                return $rd;
//            }
//            if($resDadaIsCity['status'] == -5){
//                M()->rollback();
//                $rd['status']= -1;
//                $rd['msg'] = '创建门店出错!';
//                return $rd;
//            }
//            //注册达达商户成功 在成功返回前进行事物提交
//            /* 	if($resDadaIsCity['status'] == 1){
//                    M()->commit();
//                } */
//
//
//
//
//        }
//
//        //提交事物
//        M()->commit();
//
//        //更新ERP仓库相关的信息start;
//        $sdata['shopId'] = $shopId;
//        $this->updateStockTable($sdata);
//        //更新ERP仓库相关的信息end
//
//        $rd['hyh'] = 110;
//        return $rd;
//    }
//    /**
//     * 店铺管理->修改店铺
//     */
//    public function Admin_Shops_edit(){
//
//        M()->startTrans();//开启事物
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
//        $shopId = (int)I('id',0);
//        if($shopId==0)return $rd;
//        $m = M('shops');
//        //获取店铺资料
//        $shops = $m->where("shopId=".$shopId)->find();
//        //检测手机号码是否存在
//        /*if(I("userPhone")!=''){
//            $hasUserPhone = self::checkLoginKey(I("userPhone"),$shops['userId']);
//            if($hasUserPhone==0){
//                $rd = array('status'=>-2);
//                return $rd;
//            }
//        }*/
//        $data = array();
//        $data["shopSn"] = I("shopSn");
//        $data["areaId1"] = (int)I("areaId1");
//        $data["areaId2"] = (int)I("areaId2");
//        $data["areaId3"] = (int)I("areaId3");
//        $data["goodsCatId1"] = (int)I("goodsCatId1");
//        $data["isSelf"] = (int)I("isSelf",0);
//        /* if($data["isSelf"]==1){
//            $data["deliveryType"] = 1;
//        }else{
//            $data["deliveryType"] = 0;
//        } */
//        //$data["deliveryType"] = I("deliveryType");
//        $data["deliveryType"] = 0;
//
//        $data["shopName"] = I("shopName");
//        $data["shopCompany"] = I("shopCompany");
//        $data["shopImg"] = I("shopImg");
//        $data["shopAddress"] = I("shopAddress");
//        $data["deliveryStartMoney"] = I("deliveryStartMoney",0);
//        $data["deliveryCostTime"] = I("deliveryCostTime",0);
//        $data["deliveryFreeMoney"] = I("deliveryFreeMoney",0);
//        $data["deliveryMoney"] = I("deliveryMoney",0);
//        $data["avgeCostMoney"] = I("avgeCostMoney",0);
//        $data["bankId"] = I("bankId");
//        $data["bankNo"] = I("bankNo");
//        $data["bankUserName"] = I("bankUserName");
//        $data["longitude"] = (float)I("longitude");
//        $data["latitude"] = (float)I("latitude");
//        $data["mapLevel"] = (int)I("mapLevel",13);
//        $data["isInvoice"] = I("isInvoice",1);
////		$data["serviceStartTime"] = I("serviceStartTime");
////		$data["serviceEndTime"] = I("serviceEndTime");
//        $data["serviceStartTime"] = "0.00";
//        $data["serviceEndTime"] = "23.59";
//        $data["shopStatus"] = (int)I("shopStatus",0);
//        $data["shopAtive"] = (int)I("shopAtive",1);
//        $data["shopTel"] = I("shopTel");
//        $data["commissionRate"] = I("commissionRate");
//
//
//        /* //---------更新达达门店信息-----------
//        $DaDaData_areas_mod = M('areas');
//        unset($DaDaData);
//
//        //	门店名称
//        if($m->where("shopId=".$shopId)->field('shopName')->find()['shopName'] !== $data["shopName"]){
//            $DaDaData['station_name']=$data["shopName"];
//        }
//
//        //城市名称
//        if($m->where("shopId=".$shopId)->field('areaId2')->find()['areaId2'] !== $data["areaId2"]){
//            $DaDaData['city_name']=str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName']);
//        }
//
//        //区域名称(如,浦东新区)
//        if($m->where("shopId=".$shopId)->field('areaId3')->find()['areaId3'] !== $data["areaId3"]){
//            $DaDaData['area_name']=str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName']);
//        }
//
//        //详细地址
//        if($m->where("shopId=".$shopId)->field('shopAddress')->find()['shopAddress'] !== $data["shopAddress"]){
//            $DaDaData['station_address']=
//            $DaDaData_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'].' '.
//            $DaDaData_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'].' '.
//            $DaDaData_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'].' '.$data["shopAddress"];
//        }
//
//        //门店经度
//        if($m->where("shopId=".$shopId)->field('longitude')->find()['longitude'] !== $data["longitude"]){
//            $DaDaData['lng']=$data["longitude"];
//        }
//
//        //门店纬度
//        if($m->where("shopId=".$shopId)->field('latitude')->find()['latitude'] !== $data["latitude"]){
//            $DaDaData['lat']=$data["latitude"];
//        }
//
//
//    //	$data["userName"] = I("userName");
//    //	$data["userPhone"] = I("userPhone");
//     //	$DaDaData = array(
//    //		'contact_name'=> $data["userName"],//联系人姓名
//    //		'phone'=> $data["userPhone"],//	联系人电话
//    //	);
//
//        //业务类型
//        if(!empty($DaDaData)){
//            $DaDaData['business']=19;
//        }
//
//
//
//        if(!empty($shops['dadaOriginShopId']) and !empty($DaDaData)){
//            unset($dadamod);
//            $dadam = D("Admin/dada");
//            $dadamod = $dadam->apiShopUpdate($DaDaData,$shops['dadaOriginShopId']);
//
//            if(!empty($dadamod['niaocmsstatic'])){
//                $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'更新门店出错#'.$dadamod['info']);//更新门店出错
//                return $rd;
//            }
//        } */
//
//
//
//
//        //更新用户资料
//        $sdata = array();
//        $sdata["userName"] = I("userName");
//        $sdata["userPhone"] = I("userPhone");
//        $mod_users = M('users');
//        $mod_users->where("userId ='{$shops['userId']}'")->save($sdata);
//
//
//
//        if($this->checkEmpty($data,true)){
//            $data['qqNo'] = I('qqNo');
//            $data["invoiceRemarks"] = I("invoiceRemarks");
//            $data["deliveryLatLng"] = I("deliveryLatLng");
//            $data["isInvoicePoint"] = I("isInvoicePoint");
//            $data["dadaShopId"] = I("dadaShopId");
//            $data["dadaOriginShopId"] = I("dadaOriginShopId");
//            //$data["team_token"] = I("team_token");
//            $rs = $m->where("shopId=".$shopId)->save($data);
//            if(false !== $rs){
//                //更新ERP仓库相关的信息start;
//                $data['shopId'] = $shopId;
//                $this->updateStockTable($data);
//                //更新ERP仓库相关的信息end
//                $shopMessage = '';
//                //如果[已通过的店铺]被改为未审核的话也要停止了该店铺的商品
//                if($shops['shopStatus']!=$data['shopStatus']){
//                    if($data['shopStatus']!=1){
//                        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=0 where shopId=".$shopId;
//                        $m->execute($sql);
//                        $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
//                    }
//                    if($shops['shopStatus']!=1 && $data['shopStatus']==1){
//                        $shopMessage = "您的店铺状态已被改为“已审核”状态，您可以出售自己的商品啦~";
//                    }
//                    $yj_data = array(
//                        'msgType' => 0,
//                        'sendUserId' => session('WST_STAFF.staffId'),
//                        'receiveUserId' => $shops['userId'],
//                        'msgContent' => $shopMessage,
//                        'createTime' => date('Y-m-d H:i:s'),
//                        'msgStatus' => 0,
//                        'msgFlag' => 1,
//                    );
//                    M('messages')->add($yj_data);
//                }
//                //检查用户类型
//                $m = M('users');
//                $userType = $m->where('userId='.$shops['userId'])->getField('userType');
//
//                //保存用户资料
//                $data = array();
//                $data["userName"] = I("userName");
//                $data["userPhone"] = I("userPhone");
//
//                //如果是普通用户则提升为店铺会员
//                if($userType==0){
//                    $data["userType"] = 1;
//                }
//                $urs = $m->where("userId=".$shops['userId'])->save($data);
//                $rd['code']= 0;
//                $rd['msg'] = '操作成功';
//
//                //建立店铺和社区的关系
//                $relateArea = self::formatIn(",", I('relateAreaId'));
//                $relateCommunity = self::formatIn(",", I('relateCommunityId'));
//
//                $m = M('shops_communitys');
//                $m->where('shopId='.$shopId)->delete();
//                if($relateArea!=''){
//                    $relateAreas = explode(',',$relateArea);
//                    foreach ($relateAreas as $v){
//                        if($v=='' || $v=='0')continue;
//                        $tmp = array();
//                        $tmp['shopId'] = $shopId;
//                        $tmp['areaId1'] = (int)I("areaId1");
//                        $tmp['areaId2'] = (int)I("areaId2");
//                        $tmp['areaId3'] = $v;
//                        $tmp['communityId'] = 0;
//                        $ra = $m->add($tmp);
//                    }
//                }
//                if($relateCommunity!=''){
//                    $m = M('communitys');
//                    $lc = $m->where('communityFlag=1 and (communityId in(0,'.$relateCommunity.") or areaId3 in(0,".$relateArea."))")->select();
//                    if(count($lc)>0){
//                        $m = M('shops_communitys');
//                        foreach ($lc as $key => $v){
//                            $tmp = array();
//                            $tmp['shopId'] = $shopId;
//                            $tmp['areaId1'] = $v['areaId1'];
//                            $tmp['areaId2'] = $v['areaId2'];
//                            $tmp['areaId3'] = $v['areaId3'];
//                            $tmp['communityId'] = $v['communityId'];
//                            $ra = $m->add($tmp);
//                        }
//                    }
//                }
//            }
//        }
//
//
//
//
//        //如果店铺存在达达店铺 则进行更新
//
//        if(!empty($shops['dadaShopId'])){
//            /*$getData['shopName'] = I("shopName");//门店名称
//            $getData['areaId2'] = (int)I("areaId2");//二级城市
//            $getData['shopId'] = $shopId;//店铺ID
//            $getData['areaId3'] = (int)I("areaId3");//第三级城市id
//            $getData['areaId1'] = (int)I("areaId1");//第一级城市id
//            $getData['shopAddress'] = I("shopAddress");//店铺地址
//            $getData['longitude'] = I("longitude");//经度
//            $getData['latitude'] = I("latitude");//纬度
//
//            $isok = self::updateDadaShop($getData);
//            if($isok['status'] == -6){
//                $rd['status']= -1;
//                //$rd = array('msg'=>'更新达达门店出错');
//                $rd = array('msg'=>$isok);
//                M()->rollback();
//                return $rd;
//            }*/
//
//        }else{//否则判断是否选择了 达达物流 如果选择了 进行注册
//            //if(I("deliveryType") == 2){
//            if(I("deliveryType") == 2 && empty($data["dadaShopId"]) && empty($data["dadaOriginShopId"])){ //如果用户手动填写达达商户id和门店编号则不再注册达达
//                $getData['shopId'] = $shopId;//店铺id
//                $getData['areaId2'] = (int)I("areaId2");//二级城市id
//                $getData['userPhone'] = I("shopTel");//商家手机号
//                $getData['areaId1'] = (int)I("areaId1");//第一级城市id
//                $getData['shopCompany'] = I("shopCompany");//公司名称
//                $getData['areaId3'] = (int)I("areaId3");//第三级城市id
//                $getData['shopAddress'] = I("shopAddress");//门店地址
//                $getData['userName'] = I('userName');//用户名称
//                $getData['qqNo'] = I('qqNo');//用户QQ
//                $getData['shopName'] = I('shopName');//门店名称 */
//                $getData['longitude'] = I("longitude");//经度
//                $getData['latitude'] = I("latitude");//纬度
//
//                $resDadaIsCity = self::dadaLogistics($getData);
//                if($resDadaIsCity['status'] == -7){
//                    M()->rollback();
//                    $rd['status']= -1;
//                    $rd['msg'] = '达达在当前地区未开通城市!';
//                    return $rd;
//                }
//
//                if($resDadaIsCity['status'] == -4){
//                    M()->rollback();
//                    $rd['status']= -1;
//                    $rd['msg'] = '注册达达物流商户出错!';
//                    return $rd;
//                }
//                if($resDadaIsCity['status'] == -5){
//                    M()->rollback();
//                    $rd['status']= -1;
//                    $rd['msg'] = '创建门店出错!';
//                    return $rd;
//                }
//
//            }
//
//
//        }
//
//
//        //提交事物
//        M()->commit();
//        return $rd;
//    }
//
//    /**
//     * 店铺管理->停止或者拒绝店铺
//     */
//    public function Admin_Shops_reject(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
//        $shopId = I('id',0);
//        if($shopId==0)return rd;
//        $m = M('shops');
//        //获取店铺资料
//        $shops = $m->where("shopId=".$shopId)->find();
//        $data = array();
//        $data['shopStatus'] = (int)I('shopStatus',-1);
//        $data['statusRemarks'] = I('statusRemarks');
//        if($this->checkEmpty($data,true)){
//            $rs = $m->where("shopId=".$shopId)->save($data);
//            if(false !== $rs){
//                //如果[已通过的店铺]被改为停止或者拒绝的话也要停止了该店铺的商品
//                if($shops['shopStatus']!=$data['shopStatus']){
//                    $shopMessage = '';
//                    if($data['shopStatus']!=1){
//                        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=0 where shopId=".$shopId;
//                        $m->execute($sql);
//                        if($data['shopStatus']==0){
//                            $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
//                        }else{
//                            $shopMessage = I('statusRemarks');
//                        }
//                    }
//                    $yj_data = array(
//                        'msgType' => 0,
//                        'sendUserId' => session('WST_STAFF.staffId'),
//                        'receiveUserId' => $shops['userId'],
//                        'msgContent' => I('statusRemarks'),
//                        'createTime' => date('Y-m-d H:i:s'),
//                        'msgStatus' => 0,
//                        'msgFlag' => 1,
//                    );
//                    M('messages')->add($yj_data);
//                }
//                $rd = array('code'=>0,'msg'=>'操作成功');
//            }
//
//        }
//        return $rd;
//    }
//
//    /**
//     * 店铺管理->删除店铺
//     */
//    public function Admin_Shops_del(){
//        $shopId = (int)I('id');
//        $rd = array('code'=>-1,'msg'=>'操作失败');
//        //下架所有商品
//        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=-1 where shopId=".$shopId;
//        $this->execute($sql);
//        $sql = "select userId from __PREFIX__shops where shopId=".$shopId;
//        $shop = $this->queryRow($sql);
//        //删除登录账号
//        $sql = "update __PREFIX__users set userFlag=-1 where userId=".$shop['userId'];
//        $this->execute($sql);
//        //标记店铺删除状态
//        $data = array();
//        $data["shopFlag"] = -1;
//        $data["shopStatus"] = -2;
//        $rs = M("shops")->where("shopId=".$shopId)->save($data);
//        if(false !== $rs){
//            //删除店铺,如果该店铺有对应的分支机构和仓库,也要删除
//            $this->deleteStock($shopId);
//            $rd = array('code'=>0,'msg'=>'操作成功');
//        }
//        return $rd;
//    }
//
//    /**
//     * 店铺管理->查询登录关键字 注册时 纯数字不能等于11位
//     */
//    public function Admin_Shops_checkLoginKey($val,$id = 0){
//        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1";
//        $keyArr = array($val,$val,$val);
//        if($id>0)$sql.=" and userId!=".(int)$id;
//        $m = M('users');
//        $rs = $m->where($sql,$keyArr)->find();
//
//
//        if($rs==0){
//            return 1;
//        }
//        return 0;
//    }
//
//    /**
//     * 删除分支机构和仓库
//     * @param int $shopId
//     * */
//    public function deleteStock($shopId){
//        $shopId = (int)$shopId;
//        if($shopId <= 0){
//            return false;
//        }
//        //$db = sqlServerDB();
//        $db = connectSqlServer();
//        $relationInfo = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
//        if(isset($relationInfo['shopId']) && $relationInfo['shopId'] > 0 ){
//            //SType
//            $Sid = $relationInfo['Sid'];
//            $sql = "SELECT TypeId,Sid,FullName FROM SType WHERE Sid='{$Sid}' ";
//            /*$conn = $db->prepare($sql);
//            $conn->execute();
//            $stypeInfo = hanldeSqlServerData($conn,'row');*/
//            $stypeInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
//            if(!$stypeInfo){
//                return false;
//            }
//            //Stock
//            $sql = "SELECT typeId,Kid,FullName FROM Stock WHERE STypeID='".$stypeInfo['TypeId']."' AND deleted=0 ";
//            /*$conn = $db->prepare($sql);
//            $conn->execute();
//            $stockInfo = hanldeSqlServerData($conn,'row');*/
//            $stockInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
//            //delete
//            if($stockInfo){
//                //删除仓库
//                $sql = "UPDATE Stock SET deleted=1 WHERE Kid='".$stockInfo['Kid']."'";
//                /*$conn = $db->prepare($sql);
//                $updateRes = $conn->execute();*/
//                $updateRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//                if($updateRes !== false){
//                    //删除分支机构和店铺关系绑定记录
//                    $deleteRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'Sid'=>$stypeInfo['Sid']])->save(['isDelete'=>-1]);
//                    if($deleteRelation !== false){
//                        //删除分支机构
//                        $sql = "UPDATE SType SET deleted=1 WHERE Sid='".$stypeInfo['Sid']."'";
//                        /*$conn = $db->prepare($sql);
//                        $updateRes = $conn->execute();*/
//                        $updateRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//                    }
//                }
//            }
//        }
//    }
//
//    /**
//     * 添加或更新店铺的同时也要将ERP上对应的分支机构和仓库更新
//     * @ array shopData PS:店铺信息
//     * */
//    public function updateStockTable($shopData){
//        $shopId = I('id');
//        if(isset($shopData['shopId'])){
//            $shopId = $shopData['shopId'];
//        }
//        $userId = M('shops')->where(['shopId'=>$shopId])->getField('userId');
//        $shopData['userName'] = '';
//        $shopData['userPhone'] = '';
//        if((int)$userId <= 0){
//            return false;
//        }
//        $userInfo = M('users')->where(['userId'=>$userId])->field('userName,userPhone')->find();
//        if($userInfo){
//            $shopData['userPhone'] = $userInfo['userPhone'];
//            $shopData['userName'] = $userInfo['userName'];
//        }
//        //wst_shops和ERP下的SType 关联表
//        $relationWhere['shopId'] = $shopData['shopId'];
//        $relationWhere['isDelete'] = 1;
//        $relationRes = madeDB('shops_stype_relation')->where($relationWhere)->find();
//        $pinyin = D('Made/Pinyin');
//        $shortPinyin = strtoupper($pinyin::getShortPinyin($shopData['shopName']));
//        //$db = sqlServerDB();
//        $db = connectSqlServer();
//        if($relationRes){
//            //update
//            $actionId = $relationRes['Sid'];
//        }else{
//            //添加一个分支机构
//            $PyCode = $shortPinyin;
//            $field = "parid,leveal,sonnum,soncount,FullName,PyCode,calcFullName,Address,Tel,LinkMan";
//            //INSERT INTO SType (parid,leveal,sonnum,soncount,FullName,PyCode,calcFullName,Address,Tel,LinkMan) VALUES ('00000',1,0,0,'','','','','','ceshi510')
//            $sql = "INSERT INTO SType ($field) VALUES ('00000',1,0,0,'{$shopData['shopName']}','".$PyCode."','{$shopData['shopName']}','{$shopData['shopAddress']}','{$shopData['shopTel']}','{$shopData['userName']}') ";
//            /*$conn = $db->prepare($sql);
//            $insertRes = $conn->execute();*/
//            $insertRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//            if($insertRes){
//                $sql = "SELECT IDENT_CURRENT('SType')";
//                /*$conn = $db->prepare($sql);
//                $conn->execute();
//                $insertRow = hanldeSqlServerData($conn,'row');
//                unset($conn);*/
//                $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
//                $actionId = $insertRow[''];
//            }
//            if(isset($actionId) && $actionId > 0 ){
//                //分支机构和店铺绑定
//                $relation = [];
//                $relation['shopId'] = $shopData['shopId'];
//                $relation['Sid'] = $actionId;
//                madeDB('shops_stype_relation')->add($relation);
//            }
//        }
//        if(isset($actionId) && $actionId > 0 ){
//            $typeId = str_pad($actionId,5,0,STR_PAD_LEFT );
//            $userCode = str_pad($actionId,2,0,STR_PAD_LEFT );
//            $verifyID = str_pad($actionId,5,0,STR_PAD_LEFT );
//            //FullName,PyCode,calcFullName,Address,Tel
//            $sql = "UPDATE SType SET TypeId='".$typeId."', UserCode='".$userCode."', VerifyID='".$verifyID."',FullName='{$shopData['shopName']}',PyCode='{$shortPinyin}',calcFullName='{$shopData['shopName']}',Address='{$shopData['shopAddress']}',Tel='{$shopData['shopTel']}',LinkMan='{$shopData['userName']}' WHERE Sid='".$actionId."'";
//            /*$conn = $db->prepare($sql);
//            $updataStype = $conn->execute();*/
//            $updataStype = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//            if($updataStype){
//                //创建一个隶属于该分支机构的仓
//                $sql = "SELECT Kid,StypeId FROM Stock ";
//                $sql .= " WHERE STypeID ='".$typeId."' AND deleted=0 ";
//                /*$conn = $db->prepare($sql);
//                $conn->execute();
//                $stockInfo = hanldeSqlServerData($conn,'row');*/
//                $stockInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
//                $shopData['shopName'] .= '仓库';
//                $shortPinyinStock = strtoupper($pinyin::getShortPinyin($shopData['shopName']));
//                if(!$stockInfo){
//                    $field = 'parid,leveal,sonnum,soncount,FullName,Tel,LinkMan,PyCode,STypeID,mobile,[Add]';
//                    $value = "'00000',1,0,0,'{$shortPinyinStock}','{$shopData['shopTel']}','{$shopData['userName']}','$shortPinyin','$typeId','{$shopData['userPhone']}','{$shopData['shopAddress']}'";
//                    $sql = "INSERT INTO Stock($field) VALUES ($value) ";
//                    /*$conn = $db->prepare($sql);
//                    $insertStock = $conn->execute();*/
//                    $insertStock = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//                    if($insertStock){
//                        $sql = "SELECT IDENT_CURRENT('Stock')";
//                        /*$conn = $db->prepare($sql);
//                        $conn->execute();
//                        $insertRow = hanldeSqlServerData($conn,'row');*/
//                        $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
//                        $stockId = $insertRow[''];
//                    }
//                }else{
//                    $stockId = $stockInfo['Kid'];
//                }
//                if(isset($stockId) && (int)$stockId > 0 ){
//                    //更新下仓库其他相关信息
//                    $typeId = str_pad($stockId,5,0,STR_PAD_LEFT );
//                    $userCode = str_pad($stockId,2,0,STR_PAD_LEFT );
//                    $sql = "UPDATE Stock SET FullName='{$shopData['shopName']}',Tel='{$shopData['shopTel']}',LinkMan='{$shopData['userName']}',PyCode='{$shortPinyinStock}',mobile='{$shopData['userPhone']}',typeId='{$typeId}',userCode='{$userCode}',[Add]='{$shopData['shopAddress']}' WHERE Kid='{$stockId}' ";
//                    /*$conn = $db->prepare($sql);
//                    $conn->execute();*/
//                    handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
//                    //向货位表添加信息;PS:该表没有数据,后面有些操作无法进行
//                    $this->insertCargoType($stockId);
//                    //向部门表添加部分信息
//                    $insertDepartment = $this->insertDepartment($shopId);
//                    if($insertDepartment['apiCode'] == 0){
//                        //$rec = $insertDepartment['apiData']['rec'];
//                        //向职员表插入数据,这个时候相当于添加一个一级的职员分类
//                        $this->insertEmployee($shopId);
//                    }
//                }
//            }
//        }
//        //释放资源
//        unset($conn);
//        unset($db);
//    }
//
//    /**
//     * 添加默认货位
//     * @param int $stockId PS:仓库id
//     * */
//    public function insertCargoType($stockId){
//        $stockId = (int)$stockId;
//        if($stockId > 0){
//            $sql = "SELECT typeId,parid,leveal,sonnum,soncount,FullName,UserCode,Kid FROM Stock WHERE Kid='".$stockId."' AND deleted=0 ";
//            $stockInfo = sqlQuery($sql,'row');
//            if(!$stockInfo){
//                return false;
//            }
//            $sql = "SELECT CargoID FROM CargoType WHERE KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
//            $cargoType = sqlQuery($sql,'row');
//            if(!$cargoType){
//                $userCode = $stockInfo['FullName']."默认货位";
//                $fullName = $userCode;
//                $comment = $userCode;
//                $isDefault = 1;
//                $pathNo = 0;
//                $WdSyncFlag = 0;
//                $PyCode = '';
//                $field = "UserCode,FullName,KtypeID,Comment,PyCode,IsDefault,PathNo,WdSyncFlag";
//                $value = "'{$userCode}','{$fullName}','{$stockInfo['typeId']}','{$comment}','{$PyCode}','{$isDefault}','{$pathNo}','{$WdSyncFlag}' ";
//                $sql = "INSERT INTO CargoType($field) VALUES($value)";
//                sqlExcute($sql);
//            }
//        }
//    }
//
//    /**
//     * 添加部门
//     * @param int $shopId
//     * */
//    public function insertDepartment($shopId){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '添加部门失败';
//        $apiRes['apiState'] = 'error';
//        $shopId = (int)$shopId;
//        $shopInfo = M('shops s')
//            ->join("left join wst_users u on u.userId=s.userId")
//            ->where("s.shopId='".$shopId."' AND s.shopFlag=1")
//            ->field("s.*,u.userName,u.userPhone")
//            ->find();
//        if(!$shopInfo){
//            $apiRes['apiInfo'] = '店铺信息有误';
//            return $apiRes;
//        }
//        $pinyin = new PinyinModel();
//        //field
//        $field = "[parid], [leveal], [sonnum], [soncount],[FullName], [Name], [Comment], [Tel], [LinkMan],[PyCode],[usercode]";
//
//        //value
//        $parid = '00000';
//        $leveal = 1;
//        $sonnum = 0;
//        $soncount = 0;
//        $FullName = $shopInfo['shopName'].'分部';
//        $usercode = $shopInfo['shopSn'];//分部编号和店铺编号关联
//        $Name = '';
//        $Comment = '';
//        $Tel = $shopInfo['userPhone'];
//        $LinkMan = $shopInfo['userName'];
//        $PyCode = strtoupper($pinyin::getShortPinyin($FullName));
//        $value = "'{$parid}',$leveal,$sonnum,$soncount,'{$FullName}','{$Name}','{$Comment}','{$Tel}','{$LinkMan}','{$PyCode}','{$usercode}'";
//
//        $sql = "SELECT typeid,parid,leveal,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
//        $departmentInfo = sqlQuery($sql,'row');
//        if(!$departmentInfo){
//            //insert
//            $sql = "INSERT INTO Department($field) VALUES($value)";
//            $insertRes = sqlExcute($sql);
//            if(!$insertRes){
//                return $apiRes;
//            }
//            $insertId = sqlInsertId('Department');
//            //$departmentInfo
//        }else{
//            $insertId = $departmentInfo['rec'];
//        }
//        if(isset($insertId) && (int)$insertId > 0){
//            //update
//            $typeId = str_pad($insertId,5,0,STR_PAD_LEFT );
//            //$usercode = str_pad($insertId,3,0,STR_PAD_LEFT );
//            $sql = "UPDATE Department SET typeid='{$typeId}',FullName='{$FullName}',Tel='{$shopInfo['userPhone']}',LinkMan='{$shopInfo['userName']}',PyCode='{$PyCode}' WHERE rec='".$insertId."' AND deleted=0";
//            sqlExcute($sql);
//        }
//
//        $returnData['rec'] = $insertId;
//        $apiRes['apiCode'] = 0;
//        $apiRes['apiInfo'] = '操作成功';
//        $apiRes['apiState'] = 'success';
//        $apiRes['apiData'] = $returnData;
//        return $apiRes;
//    }
//
//    /**
//     * 向ERP添加职员信息(相当于二级分类)
//     * @param int $rec PS:部门id
//     * @param int $shopId PS:店铺id
//     * @param int $leveal 等级[2:二级|3:三级]
//     * @param int $localEmployeeId 本地职员id
//     * */
//    public function insertEmployee($shopId=0,$leveal=2){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '操作失败';
//        $apiRes['apiState'] = 'error';
//        if(empty($shopId)){
//            return $apiRes;
//        }
//        //shops
//        $shopInfo = M('shops')->where(['shopId'=>$shopId,'shopFlag'=>1])->field('shopId,shopSn,shopName')->find();
//        if(!$shopInfo){
//            $apiRes['apiInfo'] = '店铺信息有误';
//            return $apiRes;
//        }
//        //shops_stype_relation
//        $shopStypeRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopInfo['shopId'],'isDelete'=>1])->find();
//        //Stype
//        $sql = "SELECT TypeId,FullName,Sid FROM Stype WHERE Sid='".$shopStypeRelation['Sid']."' AND deleted=0 ";
//        $stypeInfo = sqlQuery($sql,'row');
//        //Department
//        $sql = "SELECT typeid,usercode,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
//        $departmentInfo = sqlQuery($sql,'row');
//        if(!$stypeInfo || !$departmentInfo){
//            $apiRes['apiInfo'] = '分支机构信息有误或者默认部门信息有误';
//            return $apiRes;
//        }
//        //构建参数
//        $BtypeID = '00000';
//        //$typeId = '0000100035';
//        $Parid = '00001';
//        $soncount = 1;
//        $sonnum = 1;
//        $FullName = $shopInfo['shopName'].'分部';
//        $Name = '';
//        $UserCode = $departmentInfo['rec'];
//        $Department = $departmentInfo['FullName'];
//        $DtypeID = $departmentInfo['typeid'];//和Department表中的typeid关联
//        $PyCode = getPyCode($FullName);
//        $MaxPre = 0;//每单抹零限额
//        $STypeID = $stypeInfo['TypeId'];
//        $HrPersonStatu = 4;
//
//        $field = "BtypeID,Parid,leveal,soncount,sonnum,FullName,Name,UserCode,Department,DtypeID,PyCode,MaxPre,STypeID,HrPersonStatu";
//        $value = "'{$BtypeID}','{$Parid}','{$leveal}','{$soncount}','{$sonnum}','{$FullName}','{$Name}','{$UserCode}','{$Department}','{$DtypeID}','{$PyCode}','{$MaxPre}','{$STypeID}','{$HrPersonStatu}'";
//        $sql = "SELECT typeId,FullName,Eid FROM employee WHERE DtypeID='".$departmentInfo['typeid']."' AND leveal=$leveal AND UserCode='".$departmentInfo['rec']."' AND deleted=0 ";
//        $employeeInfo = sqlQuery($sql,'row');
//        if(!$employeeInfo){
//            //employee(职员表)
//            $sql = "INSERT INTO employee($field) VALUES($value)";
//            $insertEmplyeeRes = sqlExcute($sql);
//            if(!$insertEmplyeeRes){
//                $apiRes['apiInfo'] = '职员分类添加失败';
//                return $apiRes;
//            }
//            $employeeId = sqlInsertId('employee');
//        }else{
//            $employeeId = $employeeInfo['Eid'];
//        }
//        if(isset($employeeId) && (int)$employeeId > 0){
//            $typeId = str_pad($employeeId,5,0,STR_PAD_LEFT );
//            $typeId = '00001'.$typeId;
//            $sql = "UPDATE employee SET typeId='{$typeId}' WHERE Eid='".$employeeId."'";
//            sqlExcute($sql);
//            //添加职员(三级)
//            $this->insertEmployee3($shopId,0,1);
//            $apiRes['apiCode'] = 0;
//            $apiRes['apiInfo'] = '操作成功';
//            $apiRes['apiState'] = 'success';
//        }else{
//            $apiRes['apiCode'] = -1;
//            $apiRes['apiInfo'] = '操作失败';
//            $apiRes['apiState'] = 'error';
//        }
//        return $apiRes;
//    }
//
//    /**
//     * 向ERP添加职员信息(3级)
//     * @param int $shopId PS:店铺id
//     * @param int $isDefault 是否默认职员(0:否|1:是)
//     * @param int $localEmployeeId 本地职员id
//     * */
//    public function insertEmployee3($shopId=0,$localEmployeeId=0,$isDefault=0){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '操作失败';
//        $apiRes['apiState'] = 'error';
//        if(empty($shopId)){
//            return $apiRes;
//        }
//        //shops
//        $shopInfo = M('shops')->where(['shopId'=>$shopId,'shopFlag'=>1])->field('shopId,shopSn,shopName')->find();
//        if(!$shopInfo){
//            $apiRes['apiInfo'] = '店铺信息有误';
//            return $apiRes;
//        }
//        //shops_stype_relation
//        $shopStypeRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopInfo['shopId'],'isDelete'=>1])->find();
//        //Stype
//        $sql = "SELECT TypeId,FullName,Sid FROM Stype WHERE Sid='".$shopStypeRelation['Sid']."' AND deleted=0 ";
//        $stypeInfo = sqlQuery($sql,'row');
//        //Department
//        $sql = "SELECT typeid,usercode,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
//        $departmentInfo = sqlQuery($sql,'row');
//        //获取二级信息,二级其实相当于分类,三级才是职员信息
//        $leveal2Data = $this->getEmployeeInfoLeveal2($stypeInfo['TypeId']);
//        if($leveal2Data['apiCode'] == -1){
//            $apiRes['apiInfo'] = "职员二级分类信息有误";
//            return $apiRes;
//        }
//        $leveal2Info = $leveal2Data['apiData'];
//
//        //构建参数
//        $BtypeID = '00000';
//        //$typeId = '0000100035';
//        $Parid = $leveal2Info['typeId'];
//        $leveal = 3;
//        $soncount = 0;
//        $sonnum = 0;
//        $FullName = $shopInfo['shopName'].'(默认职员)';
//        $Name = '';
//        $UserCode = $leveal2Info['UserCode'].'-'.'001';//默认职员相当于店铺登陆者
//        $Department = $departmentInfo['FullName'];
//        $DtypeID = $departmentInfo['typeid'];//和Department表中的typeid关联
//        $MaxPre = 0;//每单抹零限额
//        $STypeID = $stypeInfo['TypeId'];
//        $HrPersonStatu = 4;
//        if($isDefault == 0){
//            $userInfo = M('user')->where("id='".$localEmployeeId."' and status != -1")->find();
//            if(!$userInfo){
//                $apiRes['apiInfo'] = '职员信息有误';
//                return $apiRes;
//            }
//            $uid = str_pad($userInfo['id'],3,0,STR_PAD_LEFT );
//            $FullName = $userInfo['name'];
//            //$UserCode = $departmentInfo['rec'].'-'.$uid;
//            $UserCode = $userInfo['id'];//默认职员相当于店铺登陆者
//        }
//        $PyCode = getPyCode($FullName);
//
//        $field = "BtypeID,Parid,leveal,soncount,sonnum,FullName,Name,UserCode,Department,DtypeID,PyCode,MaxPre,STypeID,HrPersonStatu";
//        $value = "'{$BtypeID}','{$Parid}','{$leveal}','{$soncount}','{$sonnum}','{$FullName}','{$Name}','{$UserCode}','{$Department}','{$DtypeID}','{$PyCode}','{$MaxPre}','{$STypeID}','{$HrPersonStatu}'";
//
//        $sql = "SELECT typeId,FullName,Eid FROM employee WHERE DtypeID='".$departmentInfo['typeid']."' AND leveal=$leveal AND UserCode='".$UserCode."' AND deleted=0 ";
//        $employeeInfo = sqlQuery($sql,'row');
//        if(!$employeeInfo){
//            //employee(职员表)
//            $sql = "INSERT INTO employee($field) VALUES($value)";
//            $insertEmplyeeRes = sqlExcute($sql);
//            if(!$insertEmplyeeRes){
//                $apiRes['apiInfo'] = '职员分类添加失败';
//                return $apiRes;
//            }
//            $employeeId = sqlInsertId('employee');
//
//            if($leveal == 3){
//                //更新二级的的自己数量
//                $sql = "UPDATE employee SET soncount=soncount+1,sonnum=sonnum+1 WHERE Eid='".$leveal2Info['Eid']."'";
//                sqlExcute($sql);
//            }
//        }else{
//            $employeeId = $employeeInfo['Eid'];
//        }
//        if(isset($employeeId) && (int)$employeeId > 0){
//            $typeId = str_pad($employeeId,5,0,STR_PAD_LEFT );
//            $typeId = $leveal2Info['typeId'].$typeId;
//            $sql = "UPDATE employee SET typeId='{$typeId}' WHERE Eid='".$employeeId."'";
//            sqlExcute($sql);
//            $apiRes['apiCode'] = 0;
//            $apiRes['apiInfo'] = '操作成功';
//            $apiRes['apiState'] = 'success';
//        }else{
//            $apiRes['apiCode'] = -1;
//            $apiRes['apiInfo'] = '操作失败';
//            $apiRes['apiState'] = 'error';
//        }
//        return $apiRes;
//    }
//
//    /**
//     * 获取分支机构对应的二级分类(只有一条)
//     * @param int $STypeID PS:机构id
//     * */
//    public function getEmployeeInfoLeveal2($STypeID){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '暂无相关数据';
//        $apiRes['apiState'] = 'error';
//        if(empty($STypeID)){
//            $apiRes['apiInfo'] = '分支机构信息有误';
//            return $apiRes;
//        }
//        //一个本地店铺暂时只做一个分部
//        $sql = "SELECT typeId,Parid,leveal,UserCode,FullName,Department,DtypeID,STypeID FROM employee WHERE deleted=0 AND leveal=2 AND STypeID='".$STypeID."' ";
//        $leveal2Info = sqlQuery($sql,'row');
//        if($leveal2Info){
//            $apiRes['apiCode'] = 0;
//            $apiRes['apiInfo'] = '获取数据成功';
//            $apiRes['apiState'] = 'success';
//            $apiRes['apiData'] = $leveal2Info;
//        }
//        return $apiRes;
//    }
//
//    /**
//     * 总后台->店铺->批量同步店铺信息到ERP
//     */
//    public function batchShopToErp($param){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '操作失败';
//        $apiRes['apiState'] = 'error';
//        $shopId = explode(',',$param['shopId']);
//        $where = [];
//        $where['shopId'] = ['IN',$shopId];
//        $shops = M('shops')->where($where)->field('shopId,shopSn,shopName,shopFlag')->select();
//        if(empty($shops)){
//            $apiRes['apiInfo'] = '暂无符合条件的店铺';
//            return $apiRes;
//        }
//        //验证店铺数据
//        foreach ( $shops as $index => $value) {
//            if($value['shopFlag'] == -1){
//                $apiRes['apiInfo'] = "店铺['{$value['shopName']}']状态有误";
//                return $apiRes;
//            }
//        }
//        foreach ($shops as $i => $item) {
//            //更新ERP仓库相关的信息start;
//            $data['shopId'] = $item['shopId'];
//            $this->updateStockTable($data);
//            //更新ERP仓库相关的信息end
//        }
//        $apiRes['apiCode'] = 0;
//        $apiRes['apiInfo'] = '操作成功';
//        $apiRes['apiState'] = 'success';
//        return $apiRes;
//    }
//
//    /**
//     *获取ERP分支机构列表
//     * */
//    public function getSTypeList($param){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '暂无相关数据';
//        $apiRes['apiState'] = 'error';
//        $page = $param['page'];
//        $pageSize = $param['pageSize'];
//        $leveal = $param['leveal'];
//        $keyword = $param['keyword'];
//        $where = " leveal='{$leveal}' AND deleted=0 ";
//        if(!empty($keyword)){
//            $where .= " AND FullName LIKE '%{$keyword}%' ";
//        }
//
//        $orderBy = " ORDER BY Sid ASC ";
//        $field = "typeId,FullName,Sid,UserCode";
//        $result = sqlServerPageQuery($page,$pageSize,'SType','Sid',$where,$field,$orderBy);
//        if(!empty($result['root'])){
//            $apiRes['apiCode'] = 0;
//            $apiRes['apiInfo'] = '获取数据成功';
//            $apiRes['apiState'] = 'success';
//            $apiRes['apiData'] = $result;
//        }
//        return $apiRes;
//    }
//
//    /**
//     *总后台->店铺->分支机构同步到本地店铺
//     * */
//    public function batchSTypeToShops($param){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '操作失败';
//        $apiRes['apiState'] = 'error';
//        $leveal = 1;//固定值,传1
//        $Sid = 0;
//        if(!empty($param['Sid'])){
//            $Sid = $param['Sid'];
//        }
//        $where = " Sid IN($Sid) AND deleted=0 AND leveal=$leveal ";
//        $field = "TypeId,FullName,Sid,UserCode,Tel,Address";
//        $sql = "SELECT $field FROM SType WHERE $where ORDER BY Sid ASC";
//        $styleList = sqlQuery($sql);
//        if(!$styleList){
//            $apiRes['apiInfo'] = '没有符合条件的分支机构';
//            return $apiRes;
//        }
//        //验证数据是否符合条件
//        foreach ($styleList as $index => $item) {
//            M()->startTrans();//开启事物
//            $where = "parid='00000' AND deleted=0 AND STypeID='{$item['TypeId']}' AND leveal=1 ";
//            $sql = "SELECT typeId,parid,leveal,FullName,UserCode,LinkMan,mobile,Kid,LinkMan,mobile FROM Stock WHERE $where ";
//            $stockInfo = sqlQuery($sql,'row');
//            if(!$stockInfo){
//                $apiRes['apiInfo'] = "分支机构[{$item['FullName']}]缺少默认仓库,或者信息有误";
//                return $apiRes;
//            }
//
//            if(empty($stockInfo['LinkMan']) || empty($stockInfo['mobile'])){
//                $apiRes['apiInfo'] = "请补全仓库[{$stockInfo['FullName']}]的联系人和手机号";
//                return $apiRes;
//            }
//
//            $shopRelation = madeDB('shops_stype_relation')->where(['Sid'=>$item['Sid'],'isDelete'=>1])->find();
//            if(!$shopRelation){
//                //先建立账号
//                $hasLoginName = self::Admin_Shops_checkLoginKey($stockInfo['mobile']);
//                if(!$hasLoginName){
//                    $apiRes['apiInfo'] = "手机号或者登陆名已存在,请更换仓库[{$stockInfo['FullName']}]的手机号";
//                    return $apiRes;
//                }
//                //用户资料
//                $data = [];
//                $data["loginName"] = $stockInfo['mobile'];
//                $data["loginSecret"] = rand(1000,9999);
//                $pwd = $stockInfo['mobile'];
//                $data["loginPwd"] = md5($pwd.$data['loginSecret']);
//                $data["userName"] = $stockInfo['LinkMan'];
//                $data["userPhone"] = $stockInfo['mobile'];
//                $data["userStatus"] = 1;
//                $data["userType"] = 1;
//                $data["userEmail"] = '';
//                $data["userQQ"] = '';
//                $data["userScore"] = 0;
//                $data["userTotalScore"] = 0;
//                $data["userFlag"] = 1;
//                $data["createTime"] = date('Y-m-d H:i:s');
//                $m = M('users');
//                $userId = $m->add($data);
//                if(!$userId){
//                    M()->rollback();
//                    $apiRes['apiInfo'] = "添加用户失败";
//                    return $apiRes;
//                }
//                //添加店铺信息
//                $shop['shopSn'] = $item['UserCode'];
//                $shop['userId'] = $userId;
//                $shop['shopName'] = $item['FullName'];
//                $shop['shopTel'] = $item['Tel'];
//                $shop['shopAddress'] = $item['Address'];
//                $shop['createTime'] = date('Y-m-d H:i:s',time());
//                $defaultGoodsCat = M('goods_cats')->where(['isShow'=>1,'parentId'=>0,'catFlag'=>1])->order('catId asc')->find();
//                $shop['goodsCatId1'] = $defaultGoodsCat['catId'];
//                $shopId = M('shops')->add($shop);
//                if(!$shopId){
//                    M()->rollback();
//                }
//                $shop['shopId'] = $shopId;
//                M('shop_configs')->add(array('shopId'=>$shopId));
//                //绑定关系
//                $relation = [];
//                $relation['Sid'] = $item['Sid'];
//                $relation['shopId'] = $shopId;
//                $relationRes = madeDB('shops_stype_relation')->add($relation);
//                M()->commit();
//                $this->updateStockTable($shop);
//            }
//        }
//        $apiRes['apiCode'] = 0;
//        $apiRes['apiInfo'] = '操作成功';
//        $apiRes['apiState'] = 'success';
//        return $apiRes;
//    }

    //分界线,需求更改
    //在此备注,不要纠结下面用*号代替字段,没有那么多的时间去一一定义那么多的字段,变量名称别讲究规范,管家婆的数据字段就是这样的
    /**
     * 获取管家婆仓库列表
     * @param array $params
     * @param string Name 仓库简名
     * @param string FullName 仓库名称
     * @param int page
     * @param int pageSize
     */
    public function getErpStockList(array $params){
        $Name = $params['Name'];
        $FullName = $params['FullName'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $table = 'Stock';
        $preKey = "Kid";
        $field = "*";
        $orderBy = " order by Kid desc ";
        $where = " sonnum=0 and deleted=0 ";
        if(!empty($FullName)){
            $where .= " and FullName LIKE '%{$FullName}%' ";
        }
        if(!empty($Name)){
            $where .= " and Name LIKE '%{$Name}%' ";
        }
        //$rs = sqlServerPageQuery($page,$pageSize,$table,$preKey,$where,$field,$orderBy);
        $sql = "select * from Stock where $where order by Kid desc ";
        $rs = sqlQuery($sql);
        if(empty($rs)){
            $rs = [];
        }
        return returnData($rs);
    }

    /**
     *获取管家婆商品分类
     * @param array $params
     *@param string leveal PS:等级id
     *@param string ParId PS:父id,获取一级分类时传0,取值分类列表中的typeId字段
     * */
    public function getErpGoodsCatList(array $params){
        $leveal = $params['leveal'];
        $ParId = $params['ParId'];
        $where = "leveal='".$leveal."' AND deleted=0 AND soncount>0 ";
        if(!empty($ParId)){
            $where .= " and ParId='".$ParId."' ";
        }
        $sql = "select typeId,FullName,Pid,sortId,ParId from ptype where $where   order by Pid asc ";
        $db = connectSqlServer();
        $rs = handleReturnData($db->sqlQuery(getDatabaseConfig(),$sql));
        if(empty($rs)){
            $rs = [];
        }
        return returnData($rs);
    }

    /**
     *获取管家婆商品品牌
     * */
    public function getErpBrandarList(){
        $where = "leveal=1 AND SonNum=0 ";
        $sql = "select BrandID,TypeId,Leveal,UserCode,FullName from brandar_Commodity where $where   order by BrandID desc";
        $db = connectSqlServer();
        $rs = handleReturnData($db->sqlQuery(getDatabaseConfig(),$sql));
        if(empty($rs)){
            $rs = [];
        }
        return returnData($rs);
    }

    /**
     * 获取管家婆商品列表
     * @param array $params
     * @param string FullName 商品名称
     * @param string BrandarTypeID 品牌id
     * @param string typeId1 商品一级分类id
     * @param string typeId2 商品二级分类id
     * @param int page 分页
     * @param int pageSize 分页条数
     */
    public function getErpGoodsList($params){
        //ps:只做两级查询
        $where = " deleted=0 AND soncount=0 ";
        $typeId1 = $params['typeId1'];
        $typeId2 = $params['typeId2'];
        $FullName = $params['FullName'];
        $BrandarTypeID = $params['BrandarTypeID'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $table = "ptype";
        $preKey = "Pid";
        $orderBy = " ORDER BY Pid desc ";
        $field = "[typeId],[ParId],[leveal],[UserCode],[FullName],[Type],[Standard],[UsefulLifeMonth],[UsefulLifeDay],[ValidDays],[CreateDate],[Pid],[BrandarTypeID],[ScaleWeightType],[weight]";
        if(!empty($typeId1)){
            $typeStr = $typeId1;
            $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE typeId='".$typeId1."' AND deleted=0 AND leveal=1 AND soncount>0 ";
            $typeId1Info = sqlQuery($sql,'row');
            if($typeId1Info && $typeId1Info['soncount'] > 0){
                $typeStr = $typeId1Info['typeId'];
                $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE ParId='".$typeId1Info['typeId']."' AND deleted=0 AND leveal=2 AND soncount>0";
                $typeId2Info = sqlQuery($sql);
                $typeStr = [$typeStr];
                if($typeId2Info){
                    $typeStr = [];
                    foreach ($typeId2Info as $key2=>$value2){
                        if($value2['soncount'] > 0 ){
                            $typeStr[] = $value2['typeId'];
                        }
                    }
                }
            }
        }
        if(!empty($typeId2)){
            $typeStr = [];
            $typeStr[] = $typeId2;
        }
        if(!empty($typeStr)){
            $typeStr = implode(',',$typeStr);
        }
        if(!empty($typeStr)){
            $where .= " AND ParId IN({$typeStr})";
        }
        if(!empty($FullName)){
            $where .= " AND FullName LIKE '%".$FullName."%'";
        }
        if(!empty($BrandarTypeID)){
            $where .= " AND BrandarTypeID='".$BrandarTypeID."'";
        }
        $rs = sqlServerPageQuery($page,$pageSize,$table,$preKey,$where,$field,$orderBy,2);
        if(empty($rs['root'])){
            $rs = [];
        }
        $goods = $rs['root'];
        foreach ($goods as $key=>$val){
            $sql = "select PTypeID,RetailPrice,UnitID from PType_Price where PTypeID={$val['typeId']} and IsDefaultUnit=1 ";
            $priceInfo = sqlQuery($sql,'row');
            $goods[$key]['RetailPrice'] = formatAmount($priceInfo['RetailPrice']);//零售价
            $sql = "select UnitsId,FullName from PType_Units where UnitsId={$priceInfo['UnitID']} ";
            $unitInfo = sqlQuery($sql,'row');
            $goods[$key]['UnitName'] = $unitInfo['FullName'];//基本单位
        }
        $rs['root'] = $goods;
        return returnData($rs);
    }

    /**
     * 获取管家婆商品列表
     * @param array $params
     * @param string typeId1 商品一级分类id
     */
    public function getErpGoodsListNoPage($params){
        //ps:只做两级查询
        $where = " deleted=0 AND soncount=0 ";
        $typeId1 = $params['typeId1'];
        $table = "ptype";
        $orderBy = " ORDER BY Pid desc ";
        $field = "[typeId],[ParId],[leveal],[UserCode],[FullName],[Type],[Standard],[UsefulLifeMonth],[UsefulLifeDay],[ValidDays],[CreateDate],[Pid],[BrandarTypeID],[ScaleWeightType],[weight]";
        if(!empty($typeId1)){
            $typeStr = $typeId1;
            $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE typeId='".$typeId1."' AND deleted=0 AND leveal=1 AND soncount>0 ";
            $typeId1Info = sqlQuery($sql,'row');
            if($typeId1Info && $typeId1Info['soncount'] > 0){
                $typeStr = $typeId1Info['typeId'];
                $sql = "SELECT typeId,Pid,soncount,sonnum,FullName FROM ptype WHERE ParId='".$typeId1Info['typeId']."' AND deleted=0 AND leveal=2 AND soncount>0";
                $typeId2Info = sqlQuery($sql);
                $typeStr = [$typeStr];
                if($typeId2Info){
                    $typeStr = [];
                    foreach ($typeId2Info as $key2=>$value2){
                        if($value2['soncount'] > 0 ){
                            $typeStr[] = $value2['typeId'];
                        }
                    }
                }
            }
        }
        if(!empty($typeStr)){
            $typeStr = implode(',',$typeStr);
        }
        if(!empty($typeStr)){
            $where .= " AND ParId IN({$typeStr})";
        }
        $sql = "select $field from $table where $where $orderBy ";
        $goods = sqlQuery($sql);
        if(empty($goods)){
            $goods = [];
        }
        $typeId = [];
        foreach ($goods as $key=>$val){
            $goods[$key]['RetailPrice'] = 0;//零售价
            $goods[$key]['UnitName'] = '';//基本单位
            $typeId[] = $val['typeId'];
        }
        $typeIdStr = implode(',',$typeId);
        $sql = "select PTypeID,RetailPrice,UnitID from PType_Price where PTypeID IN({$typeIdStr}) and IsDefaultUnit=1 ";
        $priceList = sqlQuery($sql);
        if(!empty($priceList)){
            $UnitIDs = [];
            foreach ($priceList as $key=>$value){
                $UnitIDs[] = $value['UnitID'];
            }
            if(!empty($UnitIDs)){
                $UnitIDs = array_unique($UnitIDs);
                $UnitIDsStr = implode(',',$UnitIDs);
                $sql = "select UnitsId,FullName from PType_Units where UnitsId IN({$UnitIDsStr}) ";
                $UnitIDList = sqlQuery($sql);
                foreach ($priceList as $key=>$value){
                    foreach ($UnitIDList as $v){
                        if($v['UnitsId'] == $value['UnitID']){
                            $priceList[$key]['UnitName'] = $v['FullName'];
                        }
                    }
                }
            }
            foreach ($goods as $key=>$value){
                foreach ($priceList as $pval){
                    if($pval['PTypeID'] == $value['typeId']){
                        $goods[$key]['RetailPrice'] = formatAmount($pval['RetailPrice']);//零售价
                        $goods[$key]['UnitName'] = $pval['UnitName'];//基本单位
                    }
                }
            }
        }
        /*foreach ($goods as $key=>$val){
            $sql = "select PTypeID,RetailPrice,UnitID from PType_Price where PTypeID={$val['typeId']} and IsDefaultUnit=1 ";
            $priceInfo = sqlQuery($sql,'row');
            $goods[$key]['RetailPrice'] = 0;//零售价
            $goods[$key]['UnitName'] = '';//基本单位
            if(!empty($priceInfo)){
                $goods[$key]['RetailPrice'] = formatAmount($priceInfo['RetailPrice']);//零售价
                $sql = "select UnitsId,FullName from PType_Units where UnitsId={$priceInfo['UnitID']} ";
                $unitInfo = sqlQuery($sql,'row');
                $goods[$key]['UnitName'] = $unitInfo['FullName'];//基本单位
            }
        }*/
        return $goods;
    }

    /*
     *获取管家婆地区价格列表
     * */
    public function getErpPriceNameList(){
        $sql = "select Id,FiledName,SetName from PriceNameSet order by Id asc ";
        $db = connectSqlServer();
        $rs = handleReturnData($db->sqlQuery(getDatabaseConfig(),$sql));
        if(empty($rs)){
            $rs = [];
        }
        return returnData($rs);
    }

    /**
     *同步管家婆数据
     * @param array $params
     * @param string goodsData 是否导入商品数据(true:是|false:否)
     * @param int allGoods 导入类型(1:全部商品|2:部分商品),PS:当选则点击部分商品的时候出现选择商品的操作,点击弹出商品列表给用户选择商品
     * @param string goodsTypeIds 多个商品用英文逗号分隔,取值商品列表的typeId字段
     * @param string brandData 是否导入品牌数据(true:是|false:否)
     * @param string goodsCatData 是否导入分类数据(true:是|false:否)
     * @param string stockTypeId 选择的同步仓库的typeId
     * @param string priceNameId 选择的地区售价Id
     * @param string goodsStock 同步库存(true:是|false:否)
     * @param string updateGoods 是否更新商品信息(true:是|false:否),PS:是,除商品编码、商品所属分类、商品关联品牌外的字段做二次更新;
    否,除商品计量单位、商品预设售价外的字段不做二次更新
     * */
    public function syncErpGoods($params){
        $goodsData = $params['goodsData'];
        $allGoods = $params['allGoods'];
        $goodsTypeIds = $params['goodsTypeIds'];
        $brandData = $params['brandData'];
        $goodsCatData = $params['goodsCatData'];
        $stockTypeId = $params['stockTypeId'];
        $priceNameId = $params['priceNameId'];
        $goodsStock = $params['goodsStock'];
        $updateGoods = $params['updateGoods'];
        //处理仓库信息
        $sql = "select * from Stock where typeId='".$stockTypeId."' ";
        $stockInfo = sqlQuery($sql,'row');
        if(empty($stockInfo['FullName']) || empty($stockInfo['UserCode']) || empty($stockInfo['mobile'])){
            return returnData(null,-1,'error','管家婆仓库['.$stockInfo['FullName'].'],基本信息不全，请先补全仓库的全名，编号，联系人，手机号等基本信息');
        }
        $stockInfo['priceNameSetId'] = $priceNameId;//选择的地区售价Id
        $relationWhere = [];
        $relationWhere['dataFlag'] = 1;
        $relationWhere['typeId'] = $stockInfo['typeId'];
        $shopRelationInfo = madeDB('shops_erp_stock_relation')->where($relationWhere)->find();
        $shopInfo = M('shops')->where(['shopId'=>$shopRelationInfo['shopId'],'shopFlag'=>1])->field('shopId')->find();
        if(empty($shopInfo)){
            madeDB('shops_erp_stock_relation')->where($relationWhere)->delete();
            M('users')->where(['userPhone'=>$stockInfo['mobile']])->save(['userFlag'=>-1]);
            //后加,防止本地数据删除造成的关联失效
            $shopRelationInfo = [];
        }
        if(empty($shopRelationInfo)){
            //创建店铺
            $shopId = $this->addNiaoShop($stockInfo);
            $stockInfo['shopId'] = $shopId;
        }else{
            //更新店铺的名称,联系人,手机号
            $stockInfo['shopId'] = $shopRelationInfo['shopId'];
            $updateRes = $this->updateNiaoShop($stockInfo);
            if($updateRes !== false){
                $shopId = $shopRelationInfo['shopId'];
            }
        }
        if($shopId <= 0){
            return returnData(null,-1,'error','操作失败，店铺信息有误');
        }
        //处理职员信息
        $this->syncEmployee($stockInfo);
        //处理品牌信息
        if($brandData == 'true'){
            $this->syncErpBrands();
        }
        //处理门店分类
        if($goodsCatData == 'true'){
            $erpGoodsCatList = $this->syncErpGoodsCat($shopId);
        }
        //处理商品数据
        if($goodsData == 'true'){
            if($allGoods == 1){
                //导入全部商品
                $getCat1List = $this->getErpGoodsCatList(['leveal'=>1,'ParId'=>'00000']);
                if(!empty($getCat1List['data'])){
                    $cat1List = $getCat1List['data'];
                    foreach ($cat1List as $value){
                        $goods[] = $this->getErpGoodsListNoPage(['typeId1'=>$value['typeId']]);
                    }
                    $mergeGoods = [];
                    foreach ($goods as $item){
                        $mergeGoods = array_merge($mergeGoods,$item);
                    }
                }
            }elseif ($allGoods == 2){
                //导入部分商品
                $mergeGoods = [];
                if(!empty($goodsTypeIds)){
                    $goodsTypeIdArr = explode(',',$goodsTypeIds);
                    $typeIdStr = '';
                    foreach ($goodsTypeIdArr as $item){
                        $typeIdStr .= "'".$item."',";
                    }
                    $typeIdStr = trim($typeIdStr,',');
                    $where = " deleted=0 AND soncount=0 and typeId IN({$typeIdStr})";
                    $table = "ptype";
                    $orderBy = " ORDER BY Pid desc ";
                    $field = "[typeId],[ParId],[leveal],[UserCode],[FullName],[Type],[Standard],[UsefulLifeMonth],[UsefulLifeDay],[ValidDays],[CreateDate],[Pid],[BrandarTypeID],[ScaleWeightType],[weight]";
                    $sql = "select $field from $table where $where $orderBy ";
                    $mergeGoods = sqlQuery($sql);
                }
            }
            $goodsId = [];
            foreach ($mergeGoods as $key=>$val){
                $mergeGoods[$key]['shopPrice'] = 0;
                $mergeGoods[$key]['goodsStock'] = 0;
                $goodsId[] = $val['typeId'];
            }
            $goodsIdStr = "";
            if(!empty($goodsId)){
                foreach ($goodsId as $val){
                    $goodsIdStr .= "'".$val."',";
                }
                $goodsIdStr = trim($goodsIdStr,',');
            }
            if(!empty($mergeGoods)){
                //处理地区售价售价
                if(!empty($priceNameId)){
                    $sql = "select FiledName from PriceNameSet where Id ='".$priceNameId."'";
                    $priceNameSetInfo = sqlQuery($sql,'row');
                    $priceName = $priceNameSetInfo['FiledName'];
                }
            }
            //获取商品所有价格
            if(!empty($goodsIdStr)){
                $sql = "select [PTypeID],[$priceName] from PType_Price where PTypeID IN({$goodsIdStr})and IsDefaultUnit=1";
                $priceList = sqlQuery($sql);
                foreach ($mergeGoods as $key=>$val){
                    foreach ($priceList as $pval){
                        if($pval['PTypeID'] == $val['typeId']){
                            $mergeGoods[$key]['shopPrice'] = $pval[$priceName];
                        }
                    }
                }
            }

            //处理商品库存
            if($goodsStock == 'true'){
                $sql = "select PtypeId,Qty from GoodsStocks where PtypeId IN({$goodsIdStr}) and KtypeId='".$stockInfo['typeId']."'";
                $stockList = sqlQuery($sql);
                if(!empty($stockList)){
                    foreach ($mergeGoods as $key=>$val){
                        foreach ($stockList as $gval){
                            if($gval['PtypeId'] == $val['typeId']){
                                $mergeGoods[$key]['goodsStock'] = $gval['Qty'];
                            }
                        }
                    }
                }
            }
            //同步商品信息
            $erpGoodsCatId = [];
            $goodsTab = M('goods');
            $insertGoodsData = [];
            foreach ($mergeGoods as $key=>$val){
                $erpGoodsCatId[] = $val['ParId'];
                $goodsInfo = $goodsTab->where(['goodsSn'=>$val['UserCode'],'goodsFlag'=>1,'shopId'=>$shopId])->find();
                if(empty($goodsInfo)){
                    //添加商品信息
                    $insertInfo = [];
                    $insertInfo['shopId'] = $shopId;
                    $insertInfo['goodsSn'] = $val['UserCode'];
                    $insertInfo['goodsName'] = $val['FullName'];
                    $insertInfo['marketPrice'] = (float)$val['RetailPrice'];
                    $insertInfo['shopPrice'] = $val['shopPrice'];
                    $insertInfo['goodsStatus'] = 0;
                    $insertInfo['goodsStock'] = $val['goodsStock'];
                    $insertInfo['isSale'] = 0;
                    $insertInfo['goodsSpec'] = $val['Standard'];//商品规格
                    $insertInfo['createTime'] = date('Y-m-d H:i:s');
                    $insertInfo['erpGoodsCatId'] = $val['ParId'];//后面需要unset掉
                    $insertInfo['leveal'] = $val['leveal'];//后面需要unset掉
                    $insertInfo['typeId'] = $val['typeId'];//后面需要unset掉
                    $insertInfo['ParId'] = $val['ParId'];//后面需要unset掉
                    /*$sql = "select typeId,ParId,leveal from ptype where typeId='".$val['ParId']."'";
                    $erpGoodsInfo = sqlQuery($sql,'row');
                    if($erpGoodsInfo){
                        if($erpGoodsInfo['leveal'] == 1){
                            $insertInfo['shopCatId1'] = (int)madeDB('shops_cats_erp_ptype')->where(['shopId'=>$shopId,'typeId'=>$val['typeId'],'dataFlag'=>1])->getField('catId');
                            $insertInfo['shopCatId2'] = 0;
                        }elseif ($erpGoodsInfo['leveal'] == 2){
                            $insertInfo['shopCatId2'] = (int)madeDB('shops_cats_erp_ptype')->where(['shopId'=>$shopId,'typeId'=>$erpGoodsInfo['typeId'],'dataFlag'=>1])->getField('catId');
                            $sql = "select typeId,ParId,leveal from ptype where typeId='".$erpGoodsInfo['ParId']."'";
                            $erpGoodsInfo = sqlQuery($sql,'row');
                            if($erpGoodsInfo){
                                $insertInfo['shopCatId1'] = (int)madeDB('shops_cats_erp_ptype')->where(['shopId'=>$shopId,'typeId'=>$erpGoodsInfo['typeId'],'dataFlag'=>1])->getField('catId');
                            }
                        }
                    }*/
                    $insertInfo['SuppPriceDiff'] = -1;
                    $insertInfo['weightG'] = 0;
                    if($val['ScaleWeightType'] == 1){
                        $insertInfo['SuppPriceDiff'] = 1;
                        $insertInfo['weightG'] = $val['weight'];
                    }
                    $insertGoodsData[] = $insertInfo;
                }else{
                    //更新商品信息
                    //@param string updateGoods 是否更新商品信息(true:是|false:否),PS:是,除商品编码、商品所属分类、商品关联品牌外的字段做二次更新;
                    //  否,除商品计量单位、商品预设售价外的字段不做二次更新
                    $saveData = [];
                    if($updateGoods == 'true'){
                        $saveData['goodsName'] = $val['FullName'];
                        $saveData['marketPrice'] = (float)$val['RetailPrice'];
                        $saveData['shopPrice'] = $val['shopPrice'];
                        $saveData['goodsStock'] = $val['goodsStock'];
                        $saveData['SuppPriceDiff'] = -1;
                        $saveData['weightG'] = 0;
                        $saveData['goodsSpec'] = $val['Standard'];//商品规格
                        if($val['ScaleWeightType'] == 1){
                            $saveData['SuppPriceDiff'] = 1;
                            $saveData['weightG'] = $val['weight'];
                        }
                        foreach ($erpGoodsCatList as $ekey=>$eval){
                            //leveal
                            if($val['ParId'] == $eval['typeId'] && $val['leveal'] ==2){
                                $saveData['shopCatId1'] = $eval['catId'];
                                $saveData['shopCatId2'] = 0;
                            }
                            if($val['leveal'] ==3){
                                foreach ($eval['sonCat'] as $sonKey=>$sonVal){
                                    if($sonVal['typeId'] == $val['ParId']){
                                        $saveData['shopCatId1'] = $eval['catId'];
                                        $saveData['shopCatId2'] = $sonVal['catId'];
                                    }
                                }
                            }
                        }
                    }else{
                        $saveData['marketPrice'] = $val['RetailPrice'];
                        $saveData['shopPrice'] = $val['shopPrice'];
                    }
                    $goodsTab->where(['goodsId'=>$goodsInfo['goodsId']])->save($saveData);
                }
            }
            foreach ($insertGoodsData as $key=>$val){
                foreach ($erpGoodsCatList as $ekey=>$eval){
                    //leveal
                    if($val['erpGoodsCatId'] == $eval['typeId'] && $val['leveal'] ==2){
                        $insertGoodsData[$key]['shopCatId1'] = $eval['catId'];
                        $insertGoodsData[$key]['shopCatId2'] = 0;
                    }
                    if($val['leveal'] ==3){
                        foreach ($eval['sonCat'] as $sonKey=>$sonVal){
                            if($sonVal['typeId'] == $val['ParId']){
                                $insertGoodsData[$key]['shopCatId1'] = $eval['catId'];
                                $insertGoodsData[$key]['shopCatId2'] = $sonVal['catId'];
                            }
                        }
                    }
                }
                unset($insertGoodsData[$key]['erpGoodsCatId']);
                unset($insertGoodsData[$key]['leveal']);
                unset($insertGoodsData[$key]['ParId']);
                unset($insertGoodsData[$key]['typeId']);
            }
            if(!empty($insertGoodsData)){
                M('goods')->addAll($insertGoodsData);
            }
        }
        return returnData(true);
    }

    /*
     * 更新职员信息
     * @param array $stockInfo 仓库信息
     * */
    public function syncEmployee(array $stockInfo){
        if(empty($stockInfo)){
            return returnData(null,-1,'error','仓库信息不正确');
        }
        $sql = "SELECT typeId,Parid,leveal,FullName,Eid,UserCode,Department,DtypeID,STypeID FROM employee WHERE STypeID ='".$stockInfo['STypeID']."' AND leveal=3 AND deleted=0 ";
        $employeeList = sqlQuery($sql);
        if(empty($employeeList)){
            return returnData(null,-1,'error','暂无相关职员');
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
                $userInfo['email'] = '';
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
        return true;
    }

    /**
     * 同步管家婆上的分类信息
     * @param int $shopId 门店id
     * */
    public function syncErpGoodsCat(int $shopId){
        if(empty($shopId)){
            return false;
        }
        $shopsCatTab = M('shops_cats');
        $erpGoodsCatWhere = [];
        $erpGoodsCatWhere['leveal'] = 1;
        $erpGoodsCatWhere['ParId'] = '00000';
        $getErpGoodsCatList = $this->getErpGoodsCatList($erpGoodsCatWhere);
        if(!empty($getErpGoodsCatList['data'])){
            $erpGoodsCatList = $getErpGoodsCatList['data'];
            foreach ($erpGoodsCatList as $key=>$value){
                $sonCatWhere = [];
                $sonCatWhere['leveal'] = 2;
                $sonCatWhere['ParId'] = $value['typeId'];
                $getSonCatList = $this->getErpGoodsCatList($sonCatWhere);
                if(!empty($getSonCatList['data'])){
                    $erpGoodsCatList[$key]['sonCat'] =  $getSonCatList['data'];
                }
            }
            $shopCatRelationData = [];
            foreach ($erpGoodsCatList as $key=>$value){
                $shopCatWhere = [];
                $shopCatWhere['dataFlag'] = 1;
                $shopCatWhere['typeId'] = $value['typeId'];
                $shopCatWhere['shopId'] = $shopId;
                $shopCatRelation = madeDB('shops_cats_erp_ptype')->where($shopCatWhere)->find();
                $niaoShopCatInfo = $shopsCatTab->where(['catId'=>$shopCatRelation['catId'],'catFlag'=>1])->field('catId')->find();
                if(empty($niaoShopCatInfo)){
                    madeDB('shops_cats_erp_ptype')->where($shopCatWhere)->delete();
                    $shopCatRelation = [];
                }
                if(empty($shopCatRelation)){
                    //添加店铺一级分类
                    $cat1 = [];
                    $cat1['shopId'] = $shopId;
                    $cat1['parentId'] = 0;
                    $cat1['catName'] = $value['FullName'];
                    $cat1['number'] = $value['typeId'];
                    $catId = $shopsCatTab->add($cat1);
                    if($catId){
                        $erpGoodsCatList[$key]['catId'] = $catId;
                        $shopCatRelationInfo = [];
                        $shopCatRelationInfo['shopId'] = $shopId;
                        $shopCatRelationInfo['catId'] = $catId;
                        $shopCatRelationInfo['typeId'] = $value['typeId'];
                        $shopCatRelationData[] = $shopCatRelationInfo;
                    }
                }else{
                    //更新店铺一级分类
                    //暂时注释掉,管家婆的分类和niaocms分类数据字长相差太大,避免修改过后的数据又被覆盖
                    $shopsCatTab->where(['catId'=>$shopCatRelation['catId']])->save(['catName'=>$value['FullName']]);
                    $catId = $shopCatRelation['catId'];
                    $erpGoodsCatList[$key]['catId'] = $catId;
                }
                //处理店铺二级分类
                foreach ($value['sonCat'] as $skey=>$sval){
                    $shopCatWhere = [];
                    $shopCatWhere['dataFlag'] = 1;
                    $shopCatWhere['typeId'] = $sval['typeId'];
                    $shopCatWhere['shopId'] = $shopId;
                    $shopCatRelation = madeDB('shops_cats_erp_ptype')->where($shopCatWhere)->find();
                    $niaoShopCatInfo = $shopsCatTab->where(['catId'=>$shopCatRelation['catId'],'catFlag'=>1])->field('catId')->find();
                    if(empty($niaoShopCatInfo)){
                        madeDB('shops_cats_erp_ptype')->where($shopCatWhere)->delete();
                        $shopCatRelation = [];
                    }
                    if(empty($shopCatRelation)){
                        //添加店铺二级分类
                        $cat2 = [];
                        $cat2['shopId'] = $shopId;
                        $cat2['parentId'] = $catId;
                        $cat2['catName'] = $sval['FullName'];
                        $cat2['number'] = $sval['typeId'];
                        $catId2 = $shopsCatTab->add($cat2);
                        if($catId2){
                            $erpGoodsCatList[$key]['sonCat'][$skey]['catId'] = $catId2;
                            $shopCatRelationInfo = [];
                            $shopCatRelationInfo['shopId'] = $shopId;
                            $shopCatRelationInfo['catId'] = $catId2;
                            $shopCatRelationInfo['typeId'] = $sval['typeId'];
                            $shopCatRelationData[] = $shopCatRelationInfo;
                        }
                    }else{
                        //更新店铺二级分类
                        $shopsCatTab->where(['catId'=>$shopCatRelation['catId']])->save(['catName'=>$sval['FullName']]);
                        $catId2 = $shopCatRelation['catId'];
                        $erpGoodsCatList[$key]['sonCat'][$skey]['catId'] = $catId2;
                    }
                }
            }
            if(!empty($shopCatRelationData)){
                madeDB('shops_cats_erp_ptype')->addAll($shopCatRelationData);//niaocms门店分类和管家婆商品分类关联
            }
        }
        return $erpGoodsCatList;
    }
    /**
     * 同步管家婆上的品牌
     * */
    public function syncErpBrands(){
        $brandTab = M('brands');
        $getBrandList = $this->getErpBrandarList();
        if(!empty($getBrandList['data'])){
            $brandList = $getBrandList['data'];
            //$niaoBrandData = [];
            $brandRelation = [];
            foreach ($brandList as $key=>$value){
                $brandWhere = [];
                $brandWhere['TypeId'] = $value['TypeId'];
                $brandWhere['dataFlag'] = 1;
                $niaoBrand = madeDB('brands_erp_brandar_commodity_relation')->where($brandWhere)->find();
                $niaoBrandInfo = $brandTab->where(['brandId'=>$niaoBrand['brandId'],'brandFlag'=>1])->field('brandId')->find();
                if(empty($niaoBrandInfo)){
                    madeDB('brands_erp_brandar_commodity_relation')->where($brandWhere)->delete();
                    $niaoBrand = [];
                }
                if(empty($niaoBrand)){
                    //添加品牌
                    //构建niaocms品牌数据
                    $niaoBrandDataInfo = [];
                    $niaoBrandDataInfo['brandName'] = $value['FullName'];
                    $niaoBrandDataInfo['createTime'] = date('Y-m-d H:i:s');
                    $niaoBrandDataInfo['brandFlag'] = 1;
                    $brandId = $brandTab->add($niaoBrandDataInfo);
                    if($brandId){
                        //构建品牌关联数据
                        $brandRelationInfo = [];
                        $brandRelationInfo['brandId'] = $brandId;
                        $brandRelationInfo['TypeId'] = $value['TypeId'];
                        $brandRelation[] = $brandRelationInfo;
                    }
                }else{
                    //更新品牌
                    $brandTab->where(['brandId'=>$niaoBrand['brandId']])->save(['brandName'=>$value['FullName']]);
                }
            }
            if(!empty($brandRelation)){
                madeDB('brands_erp_brandar_commodity_relation')->addAll($brandRelation);
            }
        }

    }

    /**
     * 根据TypeId获取管家婆分级机构详情
     * @param string $TypeId 分支机TypeId
     * */
    public function getSTypeDetailByTypeId($TypeId){
        $sql = "select * from SType where TypeId='".$TypeId."' ";
        $rs = sqlQuery($sql,'row');
        if(empty($rs)){
            return [];
        }
        return $rs;
    }

    /**
     * 新增niaocms店铺
     * @param array $stockInfo 管家婆仓库信息
     */
    public function addNiaoShop(array $stockInfo){
        if(empty($stockInfo)){
            return returnData(null,-1,'error','管家婆仓库信息有误');
        }
        $StypeInfo = $this->getSTypeDetailByTypeId($stockInfo['STypeID']);
        //用户资料
        $data = array();
        $data["loginSecret"] = rand(1000,9999);
        $data["loginPwd"] = md5($stockInfo['mobile'].$data['loginSecret']);
        $data["userName"] = $stockInfo['LinkMan'];
        $data["userPhone"] = $stockInfo['mobile'];
        //店铺资料
        $sdata = array();
        $sdata["shopSn"] = $stockInfo['UserCode'];
        $sdata["areaId1"] = 0;
        $sdata["areaId2"] = 0;
        $sdata["areaId3"] = 0;
        $sdata["goodsCatId1"] = M('goods_cats')->where(['parentId'=>0,'isShow'=>1,'catFlag'=>1])->getField('catId');
        $sdata["shopName"] = $stockInfo['FullName'];
        $sdata["shopCompany"] = $StypeInfo['FullName'];
        $sdata["shopImg"] = 'Upload/shops/2018-11/5befb03c75bd4.png';
        $sdata["shopAddress"] = $stockInfo['Add'];
        $sdata["bankId"] = 0;
        $sdata["bankNo"] = '';
        $sdata["bankUserName"] = '';
        //$sdata["serviceStartTime"] = floatval(str_replace(':','.',I("serviceStartTime")));
        //$sdata["serviceEndTime"] = floatval(str_replace(':','.',I("serviceEndTime")));
        $sdata["serviceStartTime"] = "0.00";
        $sdata["serviceEndTime"] = "23.59";
        $sdata["shopTel"] = $stockInfo['mobile'];
        $sdata["commissionRate"] = 0;
        M()->startTrans();//开启事物
        if($this->checkEmpty($data,true) && $this->checkEmpty([],true)){
            $data["loginName"] = $stockInfo['mobile'];
            $data["userStatus"] = 1;
            $data["userType"] = 1;
            $data["userEmail"] = '';
            $data["userQQ"] = '';
            $data["userScore"] = 0;
            $data["userTotalScore"] = 0;
            $data["userFlag"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $m = M('users');
            $userId = $m->where(array('userPhone'=>$data["userPhone"],'userFlag'=>1))->find()['userId'];
            if (!empty($userId)) {//是会员
                $m->where(array('userId'=>$userId))->save(array('loginSecret'=>$data["loginSecret"],'loginPwd'=>$data["loginPwd"]));
            } else {//不是会员
                if (empty($data["loginName"])) {
                    return returnData(null,-1,'error','请输入登录账号');
                }
                $userId = $m->add($data);
            }
            $shop_info = M('shops')->where(array('userId'=>$userId,'shopFlag'=>1))->find();
            if (!empty($shop_info)) {
                return returnData(null,-1,'error','该会员已存在，店铺请勿重新添加');
            }
            if(false !== $userId){
                $sdata["userId"] = $userId;
                $sdata["isSelf"] = 0;
                //$sdata["deliveryType"] = I("deliveryType");
                $sdata["deliveryType"] = 0;
                $sdata["deliveryStartMoney"] = 0;
                $sdata["deliveryCostTime"] = 0;
                $sdata["deliveryFreeMoney"] = 0;
                $sdata["deliveryMoney"] = 0;
                $sdata["avgeCostMoney"] = 0;
                $sdata["longitude"] = 0;
                $sdata["latitude"] = 0;
                $sdata["mapLevel"] = 13;
                $sdata["isInvoice"] = 0;
                $sdata["shopStatus"] = 0;
                $sdata["shopAtive"] = (int)I("shopAtive",1);
                $sdata["shopFlag"] = 1;
                $sdata["createTime"] = date('Y-m-d H:i:s');
                $sdata['statusRemarks'] = '';
                $sdata['qqNo'] = '';
                $sdata["invoiceRemarks"] = '';
                $sdata["deliveryLatLng"] = '';
                $sdata["isInvoicePoint"] = '';
                $sdata["dadaShopId"] = 0;
                $sdata["dadaOriginShopId"] = 0;
                $sdata["team_token"] = '';
                $m = M('shops');
                $shopId = $m->add($sdata);
                if ($shopId === false) {
                    return returnData(null,-1,'error','添加店铺['.$stockInfo['FullName'].']失败');
                }
                M('shop_configs')->add(array('shopId'=>$shopId));
                $shopRelation = [];
                $shopRelation['shopId'] = $shopId;
                $shopRelation['typeId'] = $stockInfo['typeId'];
                $shopRelation['priceNameSetId'] = $stockInfo['priceNameSetId'];
                madeDB('shops_erp_stock_relation')->add($shopRelation);//门店和买家婆仓库进行关联
                if(false !== $shopId){
                    //增加商家评分记录
                    $data = array();
                    $data['shopId'] = $shopId;
                    $m = M('shop_scores');
                    $m->add($data);
                    //建立店铺和社区的关系
                    $relateArea = self::formatIn(",", I('relateAreaId'));
                    $relateCommunity = self::formatIn(",", I('relateCommunityId'));
                    if($relateArea!=''){
                        $m = M('shops_communitys');
                        $relateAreas = explode(',',$relateArea);
                        foreach ($relateAreas as $v){
                            if($v=='' || $v=='0')continue;
                            $tmp = array();
                            $tmp['shopId'] = $shopId;
                            $tmp['areaId1'] = (int)I("areaId1");
                            $tmp['areaId2'] = (int)I("areaId2");
                            $tmp['areaId3'] = $v;
                            $tmp['communityId'] = 0;
                            $ra = $m->add($tmp);
                        }
                    }
                    if($relateCommunity!=''){
                        $m = M('communitys');
                        $lc = $m->where('communityFlag=1 and (communityId in(0,'.$relateCommunity.") or areaId3 in(0,".$relateArea."))")->select();
                        if(count($lc)>0){
                            $m = M('shops_communitys');
                            foreach ($lc as $key => $v){
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
        //判断是否选择了达达物流 是的话 就进行处理
        unset($isdeliveryType);
        $isdeliveryType = 0;
        if($isdeliveryType == 2 && empty($sdata["dadaShopId"]) && empty($sdata["dadaOriginShopId"])){ //如果用户手动填写达达商户id和门店编号则不再注册达达
            $getData['shopId'] = $shopId;//店铺id
            $getData['areaId2'] = $sdata["areaId2"];//二级城市id
            $getData['userPhone'] =  $data["userPhone"];//商家手机号
            $getData['areaId1'] = $sdata["areaId1"];//第一级城市id
            $getData['shopCompany'] = $sdata["shopCompany"];//公司名称
            $getData['areaId3'] = $sdata["areaId3"];//第三级城市id
            $getData['shopAddress'] = $sdata["shopAddress"];//门店地址
            $getData['userName'] = $data["userName"];//用户名称
            $getData['qqNo'] = $sdata['qqNo'];//用户QQ
            $getData['shopName'] = $sdata["shopName"];//门店名称
            $resDadaIsCity = self::dadaLogistics($getData);
            if($resDadaIsCity['status'] == -7){
                M()->rollback();
                return returnData(null,-1,'error','达达在当前地区未开通城市');
            }
            if($resDadaIsCity['status'] == -4){
                M()->rollback();
                return returnData(null,-1,'error','注册达达物流商户出错');
            }
            if($resDadaIsCity['status'] == -5){
                M()->rollback();
                return returnData(null,-1,'error','创建门店出错');
            }
        }
        //提交事物
        M()->commit();
        return (int)$shopId;
    }

    /**
     * 修改niaocms店铺信息
     * @param array $stockInfo 管家婆仓库信息
     */
    public function updateNiaoShop($stockInfo){
        if(empty($stockInfo)){
            return returnData(null,-1,'error','管家婆仓库信息有误');
        }
        M()->startTrans();//开启事物
        $StypeInfo = $this->getSTypeDetailByTypeId($stockInfo['STypeID']);
        $m = M('shops');
        //获取店铺资料
        $shopWhere['shopId'] = $stockInfo['shopId'];
        $shops = $m->where($shopWhere)->find();
        $data = array();
        $data["shopName"] = $stockInfo['FullName'];
        $data["shopCompany"] = $StypeInfo['FullName'];
        $data["shopTel"] = $stockInfo['mobile'];
        //更新用户资料
        $sdata = array();
        $sdata["userName"] = $stockInfo['LinkMan'];
        $sdata["userPhone"] = $stockInfo['mobile'];
        $mod_users = M('users');
        $mod_users->where("userId ='{$shops['userId']}'")->save($sdata);
        if($this->checkEmpty($data,true)){
            $data["shopAddress"] = $stockInfo['Add'];
            $data['qqNo'] = '';
            $rs = $m->where("shopId=".$stockInfo['shopId'])->save($data);
            if($rs === false){
                M()->rollback();
                return returnData(null,-1,'error','修改店铺['.$shops['shopName'].']失败');
            }
            madeDB('shops_erp_stock_relation')->where(['shopId'=>$stockInfo['shopId'],'dataFlag'=>1])->save(['priceNameSetId'=>$stockInfo['priceNameSetId']]);
        }
        //提交事物
        M()->commit();
        if($rs !== false){
            return true;
        }
        return (bool)$rs;
    }

    ///////同步店铺商品信息
    /**
     * 获取门店列表
     * @params array $params
     * @param string shopName 门店名称
     * @param string shopSn 门店编号
     * @param int page
     * @param int pageSize
     */
    public function getShopList($params)
    {
        $shopName = $params['shopName'];
        $shopSn = $params['shopSn'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " shopFlag=1 and shopStatus=1 ";
        if(!empty($shopName)){
            $where .= " and shopName like '%".$shopName."%' ";
        }
        if(!empty($shopSn)){
            $where .= " and shopSn like '%".$shopSn."%' ";
        }
        //$sql = "select shopId,shopName,shopSn,shopImg,shopAddress from __PREFIX__shops where $where order by shopId desc";
        $rs = M('shops')->where($where)->select();
        if(empty($rs)){
            $rs = [];
        }
        //$rs = $this->pageQuery($sql,$page,$pageSize);
        return returnData($rs);
    }

    /**
     * 获取门店分类
     * @param int $shopId 门店id
     * @param int $parentId 父分类id
     */
    public function getShopCatList(int $shopId,int $parentId)
    {
        $where = [];
        $where['shopId'] = $shopId;
        $where['parentId'] = $parentId;
        $where['catFlag'] = 1;
        $rs = M('shops_cats')->where($where)->select();
        if(empty($rs)){
            $rs = [];
        }
        return returnData($rs);
    }

    /**
     * 获取门店商品
     * $params array $params
     * @param int shopId 店铺id
     * @param int goodsName 商品名称
     * @param int goodsSn 商品编号
     * @param int goodsCatId1 店铺一级分类
     * @param int goodsCatId2 店铺二级分类
     * */
    public function getShopGoodsList(array $params){
        $shopId = $params['shopId'];
        $goodsName = $params['goodsName'];
        $goodsSn = $params['goodsSn'];
        $goodsCatId1 = $params['goodsCatId1'];
        $goodsCatId2 = $params['goodsCatId2'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " goodsFlag=1 and shopId='".$shopId."'";
        if(!empty($goodsName)){
            $where .= " and goodsName like '%".$goodsName."%' ";
        }
        if(!empty($goodsSn)){
            $where .= " and goodsSn like '%".$goodsSn."%' ";
        }
        if(!empty($goodsCatId1)){
            $where .= " and shopCatId1='".$goodsCatId1."'";
        }
        if(!empty($goodsCatId2)){
            $where .= " and shopCatId2='".$goodsCatId2."'";
        }
        $sql = "select goodsId,goodsName,goodsSn,goodsImg,marketPrice,shopPrice,goodsStock from __PREFIX__goods where $where ";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return returnData($rs);
    }

    /**
     * 同步门店商品信息,使用该功能需要先同步管家婆商品数据,该方法只更新数据不新加数据
     * @params array $params
     * @param int shopId 选择同步店铺id
     * @param int toShopId 选择要同步的店铺id,多个用英文逗号分隔
     * @param int allGoods 类型(1:全部商品|2:部分商品),PS:当选则点击部分商品的时候出现选择商品的操作,点击弹出商品列表给用户选择商品
     * @param string goodsIds 多个商品用英文逗号分隔
     * * @param string updateGoods 是否更新商品信息(true:是|false:否),PS:是,除商品编码、商品所属店铺分类、商品价格、商品库存外的字段做二次更新
    否,除商品图片、商品相册外的字段不做二次更新
     * */
    public function syncShopGoods(array $params){
        $shopId = $params['shopId'];
        $toShopId = explode(',',$params['toShopId']);
        foreach ($toShopId as $key=>$value){
            if($value == $shopId){
                unset($toShopId[$key]);
            }
        }
        $allGoods = $params['allGoods'];
        $goodsIds = $params['goodsIds'];
        $updateGoods = $params['updateGoods'];
        if($allGoods == 1){
            //更新所有商品信息
            //被申请
            $where = [];
            $where['shopId'] = $shopId;
            $where['goodsFlag'] = 1;
            $goods = M('goods')->where($where)->order('shopGoodsSort desc')->select();
            //申请
            // $where = [];
            // $where['shopId'] = ["IN",$toShopId];
            // $where['goodsFlag'] = 1;
            // $locationGoods = M('goods')->where($where)->select();//被申请
        }else{
            //更新部分商品的信息
            //被申请
            $where = [];
            $where['shopId'] = $shopId;
            $where['goodsFlag'] = 1;
            $where['goodsId'] = ["IN",explode(',',$goodsIds)];
            $goods = M('goods')->where($where)->select();
            $goodsSn = [];
            foreach ($goods as $key=>$val){
                $goodsSn[] = $val['goodsSn'];
            }
            //申请
            $where = [];
            $where['shopId'] = ["IN",$toShopId];
            $where['goodsFlag'] = 1;
            $where['goodsSn'] = ["IN",$goodsSn];
            $locationGoods = M('goods')->where($where)->select();//被申请
        }
        if(!empty($goods)){
            //获取管家婆商品的编码(和本地商品的关联字段)
            $goodsSnStr = '';
            $shopCatId = [];//获取商品对应的门店分类id
            foreach ($goods as $gv){
                $goodsSnStr .= "'".$gv['goodsSn']."',";
            }
            $goodsSnStr = trim($goodsSnStr,',');
            $sql = "select typeId,UserCode from ptype where UserCode IN($goodsSnStr)";
            $erpGoods = sqlQuery($sql);
            $erpGoodsTypeIdStr = '';
            $gallerysTab = M('goods_gallerys');
            foreach ($goods as $key=>$value){
                $goods[$key]['gallerys'] = [];//商品相册
                $gallerys = $gallerysTab->where(['goodsId'=>$value['goodsId']])->select();
                if(!empty($gallerys)){
                    $goods[$key]['gallerys'] = $gallerys;
                }
                $shopCatId[] = $value['shopCatId1'];
                $shopCatId[] = $value['shopCatId2'];
                foreach ($erpGoods as $erpVal){
                    if($value['goodsSn'] == $erpVal['UserCode']){
                        $goods[$key]['typeId'] = $erpVal['typeId'];
                        $erpGoodsTypeIdStr .= "'".$erpVal['typeId']."',";
                    }
                }
            }
            unset($value);
            $goods = getGoodsSku($goods);
            $erpGoodsTypeIdStr = trim($erpGoodsTypeIdStr,',');
            $shopCatId = array_unique($shopCatId);
            if(!empty($shopCatId)){
                $shopCatId = implode(',',$shopCatId);
                $where = [];
                $where['catId'] = ["IN",$shopCatId];
                $where['dataFlag'] = 1;
                $shopCatRelation = madeDB('shops_cats_erp_ptype')->where($where)->select();
                foreach ($goods as $key=>$value){
                    $goods[$key]['shopCatId1TypeId'] = 0;//关联管家婆分类的typeId
                    $goods[$key]['shopCatId2TypeId'] = 0;//关联管家婆分类的typeId
                    foreach ($shopCatRelation as $reKey=>$reVal){
                        if($value['shopCatId1'] == $reVal['catId']){
                            $goods[$key]['shopCatId1TypeId'] = $reVal['typeId'];
                        }
                        if($value['shopCatId2'] == $reVal['catId']){
                            $goods[$key]['shopCatId2TypeId'] = $reVal['typeId'];
                        }
                    }
                }
            }
            //该复制的复制,该更新的更新
            //申请,内存溢出,换个写法试试
            $where = [];
            $where['shopId'] = ["IN",$toShopId];
            $where['goodsFlag'] = 1;
            $totalCount = M('goods')->where($where)->count();
            $pici = ceil($totalCount / 1000);
            if($pici > 0 ){
                $page = 1;
                $limit = 1000;
                $pageDataNum = $page * $limit;
                for($i=1;$i<=$pici;$i++){
                    $page = $i;
                    $locationGoods = M('goods')->where($where)->limit(($page-1)*$limit,$limit)->select();
                    $res = $this->updateLocationGoods($goods,$locationGoods,$toShopId,$erpGoodsTypeIdStr);
                    if(!$res){
                        return returnData(null,-1,'error','同步商品信息失败');
                    }
                    $pageDataNum = $page * 1000;
                }
            }else{
                $res = $this->updateLocationGoods($goods,[],$toShopId,$erpGoodsTypeIdStr);
            }
        }
        if(!$res){
            return returnData(null,-1,'error','同步商品信息失败');
        }
        return returnData(true);
    }

    /**
     * 复制获取更新门店的商品信息
     * @param array $goods 将要复制的商品信息
     * @param array $locationGoods 将要复制的商品信息
     * @param array $toShopId 需要写入数据库商品的店铺id
     * @param string $erpGoodsTypeIdStr 本地商品对应管家婆商品的typeId
     * */
    public function updateLocationGoods(array $goods,array $locationGoods,array $toShopId,string $erpGoodsTypeIdStr)
    {
        ini_set ('memory_limit', '1000M');
        if(empty($goods) || empty($toShopId)){
            return false;
        }
        $where = [];
        $where['dataFlag'] = 1;
        $where['shopId'] = ['IN',$toShopId];
        $shopStockRelation = madeDB('shops_erp_stock_relation')->where($where)->select();
        if(empty($shopStockRelation)){
            //后加 start
            //如果niaocms的店铺和erp的仓库没有关联,说明该店铺是并不是从erp上拉取下来的,所以应该走普通的店铺复制功能即可
            $shopSnCopy = M('shops')->where(['shopId'=>$goods[0]['shopId']])->getField('shopSn');
            foreach($toShopId as $shopVal){
                $copyParams = [];
                $copyParams['shopId'] = $shopVal;
                $copyParams['shopSnCopy'] = $shopSnCopy;
                copyShopGoods($copyParams);
            }
            return true;
            //后加 end
            //return false;
        }
        //$shopTab = M('shops');//店铺表
        $shopCatTab = M('shops_cats');//店铺分类表
        $shopId = $goods[0]['shopId'];//被复制信息的那个店铺的id
        $toShopInfo = [];
        foreach ($toShopId as $value){
            $shopInfo = [];
            $shopInfo['shopId'] = $value;
            $shopInfo['stockTypeId'] = 0;//对应管家婆仓库id
            $shopInfo['priceNameSetId'] = 0;//地区价格id
            $shopInfo['priceNameSetName'] = '';//地区价格名称
            foreach ($shopStockRelation as $ssrkey=>$ssrval){
                if($value == $ssrval['shopId']){
                    $shopInfo['stockTypeId'] = $ssrval['typeId'];
                    $shopInfo['priceNameSetId'] = $ssrval['priceNameSetId'];
                    $sql = "select FiledName from PriceNameSet where Id='".$ssrval['priceNameSetId']."'";
                    $priceNameSetInfo = sqlQuery($sql,'row');
                    if(!empty($priceNameSetInfo)){
                        $shopInfo['priceNameSetName'] = $priceNameSetInfo['FiledName'];
                    }
                }
            }
            $toShopInfo[] = $shopInfo;
        }
        //获取被复制店铺的店铺分类信息start
        $where = [];
        $where['shopId'] = $shopId;
        $where['dataFlag'] = 1;
        $shopCatRelation = madeDB('shops_cats_erp_ptype')->where($where)->select();
        $where = [];
        $where['shopId'] = $shopId;
        $where['catFlag'] = 1;
        $where['parentId'] = 0;
        $shopCatsOne = $shopCatTab->where($where)->select();//被复制店铺一级分类
        $where = [];
        $where['shopId'] = $shopId;
        $where['catFlag'] = 1;
        $where['parentId'] = ['GT',0];
        $shopCatsTwo = $shopCatTab->where($where)->select();//被复制店铺二级分类
        foreach ($shopCatsOne as $key=>$val){
            $shopCatsOne[$key]['sonCat'] = [];
            foreach ($shopCatsTwo as $sVal){
                if($sVal['parentId'] == $val['catId']){
                    $shopCatsOne[$key]['sonCat'][] = $sVal;
                }
            }
        }
        foreach ($shopCatsOne as $key=>$value){
            foreach ($shopCatRelation as $rkey=>$rval){
                if($rval['catId'] == $value['catId']){
                    $shopCatsOne[$key]['typeId'] = $rval['typeId'];
                }
            }
            foreach ($value['sonCat'] as $sonKey=>$sonVal){
                foreach ($shopCatRelation as $rkey=>$rval){
                    if($rval['catId'] == $sonVal['catId']){
                        $shopCatsOne[$key]['sonCat'][$sonKey]['typeId'] = $rval['typeId'];
                    }
                }
            }
        }
        //同步被复制店铺的店铺分类end
        $this->updateLocationGoodsCat($shopCatsOne,$toShopInfo);//复制店铺的分类
        $this->updateNiaoGoods($goods,$locationGoods,$toShopInfo,$erpGoodsTypeIdStr);//复制门店的商品
        return true;
    }

    /**
     * 复制门店的商品
     * *param array $goods 被复制店铺的商品
     * *param array $locationGoods 复制店铺的商品
     * *param array $toShopInfo 复制店铺
     * *param array $erpGoodsTypeIdStr 本地商品对应管家婆商品的typeId
     * */
    public function updateNiaoGoods(array $goods,array $locationGoods,array $toShopInfo,string $erpGoodsTypeIdStr){
        ini_set ('memory_limit', '1000M');
        if(empty($goods)){
            return false;
        }
        $spareGoods = $goods;//备用变量
        //获取管家婆商品的价格
        $sql = "select [PtypeId],[PreBuyPrice1],[PreBuyPrice2],[PreBuyPrice3],[PreBuyPrice4],[PreBuyPrice5],[PreSalePrice1],[PreSalePrice2],[PreSalePrice3],[PreSalePrice4],[PreSalePrice5],[PreSalePrice6],[PreSalePrice7],[PreSalePrice8],[PreSalePrice9],[PreSalePrice10],[RetailPrice],[TopSalePrice],[LowSalePrice],[TopBuyPrice],[XiWaMaxNumber],[ReferPrice],[RecAmgSTypeBuyPrice],[RecAmgSTypeSalePrice],[UnitID],[IsDefaultUnit] from PType_Price where PtypeID IN($erpGoodsTypeIdStr) and IsDefaultUnit=1";
        $erpGoodsPrices = sqlQuery($sql);
        $goodsTab = M('goods');
        $gallerysTab = M('goods_gallerys');
        foreach ($toShopInfo as $value){
            $needInsertGoodsData = [];
            $needUpdateGoodsData = [];
            if(!empty($locationGoods)){
                foreach ($goods as $gkye=>$gval){
                    foreach ($locationGoods as $lkey=>$lval){
                        if($lval['goodsSn'] == $gval['goodsSn'] && $lval['shopId'] == $value['shopId']){
                            $needUpdateGoodsInfo = $gval;
                            //$needUpdateGoodsInfo['shopCatId1'] = $this->getShopGoodsCatId($needUpdateGoodsInfo['shopCatId1'],$value['shopId']);
                            //$needUpdateGoodsInfo['shopCatId2'] = $this->getShopGoodsCatId($needUpdateGoodsInfo['shopCatId1'],$value['shopId']);
                            //$needUpdateGoodsInfo['shopId'] = $value['shopId'];
                            $needUpdateGoodsInfo['shopId'] = $value['shopId'];
                            $needUpdateGoodsInfo['goodsId'] = $lval['goodsId'];
                            unset($needUpdateGoodsInfo['isSale']);
                            unset($needUpdateGoodsInfo['goodsSn']);
                            unset($needUpdateGoodsInfo['marketPrice']);
                            unset($needUpdateGoodsInfo['shopPrice']);
                            unset($needUpdateGoodsInfo['goodsStock']);
                            unset($needUpdateGoodsInfo['saleCount']);
                            unset($needUpdateGoodsInfo['createTime']);
                            unset($needUpdateGoodsInfo['hasGoodsSku']);
                            unset($needUpdateGoodsInfo['gallerys']);
                            unset($needUpdateGoodsInfo['hasGoodsSku']);
                            unset($needUpdateGoodsInfo['cartNum']);
                            unset($needUpdateGoodsInfo['goodsSku']);
                            unset($needUpdateGoodsInfo['shopCatId1TypeId']);
                            unset($needUpdateGoodsInfo['shopCatId2TypeId']);
                            //$test = $goodsTab->where(['goodsId'=>$lval['goodsId']])->save($needUpdateGoodsInfo);
                            //var_dump($test);
                            $needUpdateGoodsData[] = $needUpdateGoodsInfo;
                            unset($needUpdateGoodsInfo);
                            unset($goods[$gkye]);
                        }
                    }
                }
                if(!empty($goods)){
                    unset($needInsertGoodsData);
                    foreach ($goods as $gkye=>$gval){
                        $where = [];
                        $where['goodsFlag'] = 1;
                        $where['goodsSn'] = $gval['goodsSn'];
                        $where['shopId'] = $value['shopId'];
                        $existGoodsInfo = $goodsTab->where($where)->find();
                        if($existGoodsInfo){
                            continue;
                        }
                        $needInsertGoodsInfo = $gval;
                        //$needInsertGoodsInfo['shopCatId1'] = $this->getShopGoodsCatId($needInsertGoodsInfo['shopCatId1'],$value['shopId']);
                        //$needInsertGoodsInfo['shopCatId2'] = $this->getShopGoodsCatId($needInsertGoodsInfo['shopCatId1'],$value['shopId']);
                        $needInsertGoodsInfo['shopId'] = $value['shopId'];
                        $needInsertGoodsInfo['createTime'] = date('Y-m-d H:i:s');
                        unset($needInsertGoodsInfo['isSale']);
                        unset($needInsertGoodsInfo['goodsId']);
                        unset($needInsertGoodsInfo['marketPrice']);
                        unset($needInsertGoodsInfo['shopPrice']);
                        unset($needInsertGoodsInfo['goodsStock']);
                        unset($needInsertGoodsInfo['saleCount']);
                        unset($needInsertGoodsInfo['gallerys']);
                        unset($needInsertGoodsInfo['hasGoodsSku']);
                        unset($needInsertGoodsInfo['cartNum']);
                        unset($needInsertGoodsInfo['goodsSku']);
                        unset($needInsertGoodsInfo['shopCatId1TypeId']);
                        unset($needInsertGoodsInfo['shopCatId2TypeId']);
                        $needInsertGoodsData[] = $needInsertGoodsInfo;
                        unset($needInsertGoodsInfo);
                    }
                }
            }else{
                unset($needInsertGoodsData);
                foreach ($goods as $gkye=>$gval){
                    $needInsertGoodsInfo = $gval;
                    //$needInsertGoodsInfo['shopCatId1'] = $this->getShopGoodsCatId($needInsertGoodsInfo['shopCatId1'],$value['shopId']);
                    //$needInsertGoodsInfo['shopCatId2'] = $this->getShopGoodsCatId($needInsertGoodsInfo['shopCatId1'],$value['shopId']);
                    $needInsertGoodsInfo['shopId'] = $value['shopId'];
                    $needInsertGoodsInfo['createTime'] = date('Y-m-d H:i:s');
                    $needInsertGoodsInfo['isSale'] = 0;
                    //unset($needInsertGoodsInfo['isSale']);
                    unset($needInsertGoodsInfo['goodsId']);
                    unset($needInsertGoodsInfo['marketPrice']);
                    unset($needInsertGoodsInfo['shopPrice']);
                    unset($needInsertGoodsInfo['goodsStock']);
                    unset($needInsertGoodsInfo['saleCount']);
                    unset($needInsertGoodsInfo['gallerys']);
                    unset($needInsertGoodsInfo['hasGoodsSku']);
                    unset($needInsertGoodsInfo['cartNum']);
                    unset($needInsertGoodsInfo['goodsSku']);
                    unset($needInsertGoodsInfo['shopCatId1TypeId']);
                    unset($needInsertGoodsInfo['shopCatId2TypeId']);
                    $needInsertGoodsData[] = $needInsertGoodsInfo;
                    unset($needInsertGoodsInfo);
                }
            }
            if(!empty($needUpdateGoodsData)){
                $this->saveAll($needUpdateGoodsData,'wst_goods','goodsId');
                unset($needUpdateGoodsData);
            }
            if(!empty($needInsertGoodsData)){
                $goodsTab->addAll($needInsertGoodsData);
                unset($needInsertGoodsData);
            }
            //获取店铺现在的商品
            $nowShopGoods = $goodsTab->where(['shopId'=>$value['shopId'],'goodsFlag'=>1])->select();
            //商品更新完了,更新下价格|库存|分类
            //获取店铺的分类
            $where = [];
            $where['shopId'] = $value['shopId'];
            $where['dataFlag'] = 1;
            $shopGoodsCat = madeDB('shops_cats_erp_ptype')->where($where)->select();
            //获取仓库对应的商品库存
            $sql = "select KtypeId,PtypeId,Qty from GoodsStocks where PtypeId IN($erpGoodsTypeIdStr) and KtypeId='".$value['stockTypeId']."'";
            $goodsStock = sqlQuery($sql);
            $saveData = [];
            $nowShopGoodsGallerys = [];
            foreach ($spareGoods as $spareKey=>$spareVal){
                foreach ($nowShopGoods as $nowKey => $nowVal) {
                    if($nowVal['goodsSn'] == $spareVal['goodsSn'] && $spareVal['hasGoodsSku'] == 1){
                        $goodsSkuInfo = $spareVal['goodsSku'];
                        $skuSpec = $goodsSkuInfo['skuSpec'];
                        if(!empty($skuSpec)){
                            foreach ($skuSpec as $specKey=>$specVal){
                                $specInfo = M('sku_spec')->where(['shopId'=>$value['shopId'],'specName'=>$specVal['specName'],'dataFlag'=>1])->find();
                                $specId = $specInfo['specId'];
                                if(empty($specInfo)){
                                    $inserSpec = [];
                                    $inserSpec['shopId'] = $value['shopId'];
                                    $inserSpec['specName'] = $specVal['specName'];
                                    $inserSpec['addTime'] = date('Y-m-d H:i:s');
                                    $specId = M('sku_spec')->add($inserSpec);
                                }
                                if(!empty($specVal['attrList'])){
                                    foreach ($specVal['attrList'] as $attrVal){
                                        $attrInfo = M('sku_spec_attr')->where(['attrName'=>$attrVal['attrName'],'dataFlag'=>1,'specId'=>$specId])->find();
                                        $attrId = $attrInfo['attrId'];
                                        if(empty($attrInfo)){
                                            $attrInsert = [];
                                            $attrInsert['specId'] = $specId;
                                            $attrInsert['attrName'] = $attrVal['attrName'];
                                            //$attrInsert['sort'] = $attrVal['sort'];
                                            //$attrInsert['dataFlag'] = $attrVal['dataFlag'];
                                            $attrInsert['addTime'] = date('Y-m-d H:i:s');
                                            $attrId = M('sku_spec_attr')->add($attrInsert);
                                        }
                                    }
                                }
                            }
                        }
                        M('sku_goods_system')->where(['goodsId'=>$nowVal['goodsId']])->save(['dataFlag'=>-1]);
                        $skuList = $goodsSkuInfo['skuList'];
                        if(!empty($skuList)){
                            foreach ($skuList as $skuKey=>$skuVal){
                                if(!empty($skuVal['systemSpec'])){
                                    $skuSystemInfo = $skuVal['systemSpec'];
                                    $skuSystemInfo['goodsId'] = $nowVal['goodsId'];
                                    $skuSystemInfo['dataFlag'] = 1;
                                    $skuSystemInfo['addTime'] = date('Y-m-d H:i:s');
                                    unset($skuSystemInfo['cartNum']);
                                    $skuId = M('sku_goods_system')->add($skuSystemInfo);
                                    unset($skuSystemInfo);
                                    if(!empty($skuVal['selfSpec']) && $skuId > 0 ){
                                        $selfSpec = $skuVal['selfSpec'];
                                        foreach ($selfSpec as $selfKey=>$selfVal){
                                            $goodsSpecInfo = M('sku_spec')->where(['shopId'=>$value['shopId'],'specName'=>$selfVal['specName'],'dataFlag'=>1])->find();
                                            $goodsAttrInfo = M('sku_spec_attr')->where(['attrName'=>$selfVal['attrName'],'dataFlag'=>1,'specId'=>$goodsSpecInfo['specId']])->find();
                                            if(!empty($goodsSpecInfo) && !empty($goodsAttrInfo)){
                                                $selfValInfo = [];
                                                $selfValInfo['skuId'] = $skuId;
                                                $selfValInfo['specId'] = $goodsSpecInfo['specId'];
                                                $selfValInfo['attrId'] = $goodsAttrInfo['attrId'];
                                                M('sku_goods_self')->add($selfValInfo);
                                                unset($selfValInfo);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if($nowVal['goodsSn'] == $spareVal['goodsSn'] && !empty($spareVal['gallerys'])){
                        //更新商品相册
                        $gallerysTab->where(['goodsId'=>$nowVal['goodsId']])->delete();
                        foreach ($spareVal['gallerys'] as $galleryVal){
                            $galleryValInfo = [];
                            $galleryValInfo['shopId'] = $value['shopId'];
                            $galleryValInfo['goodsId'] = $nowVal['goodsId'];
                            $galleryValInfo['goodsImg'] = $galleryVal['goodsImg'];
                            $galleryValInfo['goodsThumbs'] = $galleryVal['goodsThumbs'];
                            $nowShopGoodsGallerys[] = $galleryValInfo;
                        }
                    }
                }
                //更新分类
                foreach ($shopGoodsCat as $catKey=>$catVal){
                    if($spareVal['shopCatId1TypeId'] == $catVal['typeId'] && $value['shopId'] == $catVal['shopId']){
                        $spareGoods[$spareKey]['shopCatId1'] = $catVal['catId'];
                    }
                    if($spareVal['shopCatId2TypeId'] == $catVal['typeId'] && $value['shopId'] == $catVal['shopId']){
                        $spareGoods[$spareKey]['shopCatId2'] = $catVal['catId'];
                    }
                }
                //更新库存
                foreach ($goodsStock as $stockKey=>$stockVal){
                    if($stockVal['PtypeId'] == $spareVal['typeId']){
                        $spareGoods[$spareKey]['goodsStock'] = $stockVal['Qty'];
                    }
                }
                //更新价格
                foreach ($erpGoodsPrices as $priceKey=>$priceVal){
                    if($priceVal['PtypeId'] == $spareVal['typeId']){
                        $spareGoods[$spareKey]['shopPrice'] = $priceVal[$value['priceNameSetName']];
                        $spareGoods[$spareKey]['marketPrice'] = $priceVal['RetailPrice'];
                    }
                }
                $saveDataInfo = [];
                $saveDataInfo['shopCatId1'] = $spareGoods[$spareKey]['shopCatId1'];
                $saveDataInfo['shopCatId2'] = $spareGoods[$spareKey]['shopCatId2'];
                $saveDataInfo['goodsStock'] = $spareGoods[$spareKey]['goodsStock'];
                $saveDataInfo['shopPrice'] = $spareGoods[$spareKey]['shopPrice'];
                $saveDataInfo['marketPrice'] = $spareGoods[$spareKey]['marketPrice'];
                $saveDataInfo['goodsSn'] = $spareGoods[$spareKey]['goodsSn'];
                $saveData[] = $saveDataInfo;
            }
            if(!empty($nowShopGoodsGallerys)){
                $gallerysTab->addAll($nowShopGoodsGallerys);
            }
            if(!empty($saveData)){
                $updateRes = $this->saveAll($saveData,'wst_goods',"goodsSn","shopId='".$value['shopId']."'");
            }
        }
        return true;
    }

    /**
     * 批量更新数据
     * @param [array] $datas [更新数据]
     * @param [string] $table_name [表名]
     */
    public function saveAll($datas,$table_name,$pk,$andWhere="1=1"){
        ini_set ('memory_limit', '1000M');
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
     * 获取复制店铺的商品店铺分类id
     * @param int $catId 被复制店铺商品的店铺分类id
     * @param int $shopId 复制店铺的id
     * */
    public function getShopGoodsCatId(int $catId,int $shopId){
        $typeId = madeDB('shops_cats_erp_ptype')->where(['catId'=>$catId,'dataFlag'=>1])->getField('typeId');
        $shopCatRelationCatId = madeDB('shops_cats_erp_ptype')->where(['shopId'=>$shopId,'typeId'=>$typeId,'dataFlag'=>1])->getField('catId');
        return (int)$shopCatRelationCatId;
    }

    /**
     * 复制店铺的店铺分类
     * @param array $shopCatsOne 被复制店铺的店铺分类
     * @param array $toShopInfo 复制店铺的店铺信息
     * */
    public function updateLocationGoodsCat(array $shopCatsOne,array $toShopInfo){
        if(empty($shopCatsOne) || empty($toShopInfo)){
            return false;
        }
        $shopCatTab = M('shops_cats');
        $shopCatRelationData = [];
        foreach ($toShopInfo as $key=>$value){
            foreach ($shopCatsOne as $catOneKey=>$catOneVal){
                $where = [];
                $where['shopId'] = $value['shopId'];
                $where['typeId'] = $catOneVal['typeId'];
                $where['dataFlag'] = 1;
                $catOneRelation = madeDB('shops_cats_erp_ptype')->where($where)->find();
                if(!empty($catOneRelation)){
                    $shopCat1Info = $shopCatTab->where(['catId'=>$catOneRelation['catId'],'shopId'=>$value['shopId'],'catFlag'=>1])->field('catId')->find();
                    if(empty($shopCat1Info)){
                        $shopCatTab->where(['catId'=>$catOneRelation['catId'],'shopId'=>$value['shopId']])->save(['catFlag'=>-1]);
                        madeDB('shops_cats_erp_ptype')->where(['shopId'=>$value['shopId'],'typeId'=>$catOneVal['typeId']])->save(['dataFlag'=>-1]);
                        $catOneRelation = [];
                    }
                }
                if(empty($catOneRelation)){
                    //添加分类一级
                    $saveData = [];
                    $saveData['shopId'] = $value['shopId'];
                    $saveData['parentId'] = $catOneVal['parentId'];
                    $saveData['isShow'] = $catOneVal['isShow'];
                    $saveData['catName'] = $catOneVal['catName'];
                    $saveData['catSort'] = $catOneVal['catSort'];
                    $saveData['number'] = $catOneVal['number'];
                    $saveData['icon'] = $catOneVal['icon'];
                    $saveData['typeImg'] = $catOneVal['typeImg'];
                    $catId1 = $shopCatTab->add($saveData);
                    if($catId1){
                        $shopCatRelationInfo = [];
                        $shopCatRelationInfo['shopId'] = $value['shopId'];
                        $shopCatRelationInfo['typeId'] = $catOneVal['typeId'];
                        $shopCatRelationInfo['catId'] = $catId1;
                        $shopCatRelationInfo['dataFlag'] = 1;
                        $shopCatRelationData[] = $shopCatRelationInfo;
                    }
                }else{
                    //编辑分类一级
                    $saveData = [];
                    $saveData['parentId'] = $catOneVal['parentId'];
                    $saveData['isShow'] = $catOneVal['isShow'];
                    $saveData['catName'] = $catOneVal['catName'];
                    $saveData['catSort'] = $catOneVal['catSort'];
                    $saveData['number'] = $catOneVal['number'];
                    $saveData['icon'] = $catOneVal['icon'];
                    $saveData['typeImg'] = $catOneVal['typeImg'];
                    $shopCatTab->where(['catId'=>$catOneRelation['catId']])->save($saveData);
                    $catId1 = $catOneRelation['catId'];
                }
                //二级分类
                foreach ($catOneVal['sonCat'] as $catTwoKey=>$catTwoVal){
                    $where = [];
                    $where['shopId'] = $value['shopId'];
                    $where['typeId'] = $catTwoVal['typeId'];
                    $where['dataFlag'] = 1;
                    $catTowRelation = madeDB('shops_cats_erp_ptype')->where($where)->find();
                    if(!empty($catTowRelation)){
                        $shopCat2Info = $shopCatTab->where(['catId'=>$catTowRelation['catId'],'shopId'=>$value['shopId'],'catFlag'=>1])->field('catId')->find();
                        if(empty($shopCat2Info)){
                            $shopCatTab->where(['catId'=>$catTowRelation['catId'],'shopId'=>$value['shopId']])->save(['catFlag'=>-1]);
                            $catTowRelation = [];
                            madeDB('shops_cats_erp_ptype')->where(['shopId'=>$value['shopId'],'typeId'=>$catTwoVal['typeId']])->save(['dataFlag'=>-1]);
                        }
                    }
                    if(empty($catTowRelation)){
                        //添加分类二级
                        $saveData = [];
                        $saveData['shopId'] = $value['shopId'];
                        $saveData['parentId'] = $catId1;
                        $saveData['isShow'] = $catTwoVal['isShow'];
                        $saveData['catName'] = $catTwoVal['catName'];
                        $saveData['catSort'] = $catTwoVal['catSort'];
                        $saveData['number'] = $catTwoVal['number'];
                        $saveData['icon'] = $catTwoVal['icon'];
                        $saveData['typeImg'] = $catTwoVal['typeImg'];
                        $catId2 = $shopCatTab->add($saveData);
                        if($catId2){
                            $shopCatRelationInfo = [];
                            $shopCatRelationInfo['shopId'] = $value['shopId'];
                            $shopCatRelationInfo['typeId'] = $catTwoVal['typeId'];
                            $shopCatRelationInfo['catId'] = $catId2;
                            $shopCatRelationInfo['dataFlag'] = 1;
                            $shopCatRelationData[] = $shopCatRelationInfo;
                        }
                    }else{
                        //编辑分类二级
                        $saveData = [];
                        $saveData['parentId'] = $catId1;
                        $saveData['isShow'] = $catTwoVal['isShow'];
                        $saveData['catName'] = $catTwoVal['catName'];
                        $saveData['catSort'] = $catTwoVal['catSort'];
                        $saveData['number'] = $catTwoVal['number'];
                        $saveData['icon'] = $catTwoVal['icon'];
                        $saveData['typeImg'] = $catTwoVal['typeImg'];
                        $shopCatTab->where(['catId'=>$catTowRelation['catId']])->save($saveData);
                        $catId2 = $catTowRelation['catId'];
                    }
                }
            }
        }
        if(!empty($shopCatRelationData)){
            madeDB('shops_cats_erp_ptype')->addAll($shopCatRelationData);
        }
        return true;
    }

    /**
     * 自动重置门店流水号(定时任务,每天执行一次)
     */
    public function autoResetShopSerialNumber(){
        $where = [];
        $where['shopFlag'] = 1;
        $save = [];
        $save['serialNumber'] = 0;
        M('shops')->where($where)->save($save);
    }

}
?>
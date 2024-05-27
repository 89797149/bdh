<?php
namespace Made\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订制模块
 * 总后台(Admin)
 */
class AdminModel extends BaseModel {
    /**
     * 注册达达物流商户
     *$rd['status'] = 1  注册达达商户成功
     *$rd['status'] = -7  未开通城市
     *$rd['status'] = -4  注册达达物流商户出错
     *$rd['status'] = -5  创建门店出错
     */
    static function Admin_Shops_dadaLogistics($getData){

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
            'mobile'=> $getData['userPhone'],
            'city_name'=> str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$getData['areaId2']}'")->field('areaName')->find()['areaName']),
            'enterprise_name'=> $getData['shopCompany'],
            'enterprise_address'=>
                $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'].','.
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'].','.
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'].','.$getData["shopAddress"],
            'contact_name'=> $getData["userName"],
            'contact_phone'=> $getData['userPhone'],
            'email'=> $getData['qqNo'].'@qq.com'
        );


        unset($dadamod);
        $dadam = D("Adminapi/dada");
        $dadamod = $dadam->merchantAdd($DaDaData);

        if(empty($dadamod['niaocmsstatic'])){
            $shops_merchantAdd_dadaShopId['dadaShopId'] = $dadamod;
            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaShopId);
            $source_id = $dadamod;
        }else{

            $rd = array('status'=>-4,'data'=>$dadamod,'info'=>'注册达达物流商户出错#'.$dadamod['info']);//注册达达物流商户出错
            return $rd;
        }

        //---------创建门店----------
        unset($DaDaData);
        $DaDaData = array(array(
            'station_name'=> $getData["shopName"],//	门店名称
            'business'=> 19,//业务类型
            'city_name'=>  str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName']),//城市名称
            'area_name'=> str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName']),//区域名称(如,浦东新区)
            'station_address'=> $DaDaData_areas_mod->where("areaId = '{$getData['areaId1']}'")->field('areaName')->find()['areaName'].','.
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId2"]}'")->field('areaName')->find()['areaName'].','.
                $DaDaData_areas_mod->where("areaId = '{$getData["areaId3"]}'")->field('areaName')->find()['areaName'].','.$getData["shopAddress"],//------------------
            'lng'=> $getData["longitude"],//门店经度
            'lat'=> $getData["latitude"],//门店纬度
            'contact_name'=> $getData["userName"],//联系人姓名
            'phone'=> $getData['userPhone'],//	联系人电话
        ));


        //echo (json_encode($DaDaData));
        unset($dadamod);
        $dadamod = $dadam->apiShopAdd($DaDaData,$source_id);//返回数组
        //exit($dadamod);
        //$dadamod = json_decode($dadamod,true);


        if(!empty($dadamod['successList'][0]['originShopId'])){
            $shops_merchantAdd_dadaOriginShopId['dadaOriginShopId'] = $dadamod['successList'][0]['originShopId'];
            $m->where("shopId = '{$getData['shopId']}'")->save($shops_merchantAdd_dadaOriginShopId);
            $rd = array('status'=>1,'info'=>'创建门店成功');//创建门店成功
            return $rd;
        }else{

            $rd = array('status'=>-5,'data'=>$dadamod,'info'=>'创建门店出错#'.$dadamod['info']);//创建门店出错
            return $rd;
        }

    }

    /**
     * 店铺管理->新增店铺
     */
    public function Admin_Shops_insert(){
        $rd = array('status'=>-1);
        //先建立账号
        $hasLoginName = self::Admin_Shops_checkLoginKey(I("loginName"));
        /*$hasUserPhone = self::checkLoginKey(I("userPhone"));
        if($hasLoginName==0 || $hasUserPhone==0){
            $rd = array('status'=>-2);
            return $rd;
        }*/
        if($hasLoginName==0){
            $rd = array('status'=>-2);
            return $rd;
        }
        //用户资料
        $data = array();
        $data["loginName"] = I("loginName");
        $data["loginSecret"] = rand(1000,9999);
        $data["loginPwd"] = md5(I('loginPwd').$data['loginSecret']);
        $data["userName"] = I("userName");
        $data["userPhone"] = I("userPhone");
        //店铺资料
        $sdata = array();
        $sdata["shopSn"] = I("shopSn");
        $sdata["areaId1"] = (int)I("areaId1");
        $sdata["areaId2"] = (int)I("areaId2");
        $sdata["areaId3"] = (int)I("areaId3");
        $sdata["goodsCatId1"] = (int)I("goodsCatId1");
        $sdata["shopName"] = I("shopName");
        $sdata["shopCompany"] = I("shopCompany");
        $sdata["shopImg"] = I("shopImg");
        $sdata["shopAddress"] = I("shopAddress");
        $sdata["bankId"] = (int)I("bankId");
        $sdata["bankNo"] = I("bankNo");
        $sdata["bankUserName"] = I("bankUserName");
        /*$sdata["serviceStartTime"] = I("serviceStartTime");
        $sdata["serviceEndTime"] = I("serviceEndTime");*/
        $sdata["serviceStartTime"] = "0.00";
        $sdata["serviceEndTime"] = "23.59";
        $sdata["shopTel"] = I("shopTel");
        $sdata["commissionRate"] = I("commissionRate");
        M()->startTrans();//开启事物
        if($this->checkEmpty($data,true) && $this->checkEmpty($sdata,true)){
            $data["userStatus"] = (int)I("userStatus",1);
            $data["userType"] = (int)I("userType",1);
            $data["userEmail"] = I("userEmail");
            $data["userQQ"] = I("userQQ");
            $data["userScore"] = I("userScore",0);
            $data["userTotalScore"] = I("userTotalScore",0);
            $data["userFlag"] = 1;
            $data["createTime"] = date('Y-m-d H:i:s');
            $m = M('users');
            $userId = $m->add($data);
            //$userId = (int)M('')->query("show table status like '__PREFIX__users'")[0]['Auto_increment'];

            if(false !== $userId){
                $sdata["userId"] = $userId;
                $sdata["isSelf"] = (int)I("isSelf",0);
                /* if($sdata["isSelf"]==1){
                    $sdata["deliveryType"] = 1;
                }else{
                    $sdata["deliveryType"] = 0;
                } */
                //$sdata["deliveryType"] = I("deliveryType");
                $sdata["deliveryType"] = 0;

                $sdata["deliveryStartMoney"] = I("deliveryStartMoney",0);
                $sdata["deliveryCostTime"] = I("deliveryCostTime",0);
                $sdata["deliveryFreeMoney"] = I("deliveryFreeMoney",0);
                $sdata["deliveryMoney"] = I("deliveryMoney",0);
                $sdata["avgeCostMoney"] = I("avgeCostMoney",0);
                $sdata["longitude"] = (float)I("longitude");
                $sdata["latitude"] = (float)I("latitude");
                $sdata["mapLevel"] = (int)I("mapLevel",13);
                $sdata["isInvoice"] = (int)I("isInvoice",1);
                $sdata["shopStatus"] = (int)I("shopStatus",1);
                $sdata["shopAtive"] = (int)I("shopAtive",1);
                $sdata["shopFlag"] = 1;
                $sdata["createTime"] = date('Y-m-d H:i:s');
                $sdata['statusRemarks'] = I('statusRemarks');
                $sdata['qqNo'] = I('qqNo');
                $sdata["invoiceRemarks"] = I("invoiceRemarks");
                $sdata["deliveryLatLng"] = I("deliveryLatLng");
                $sdata["isInvoicePoint"] = I("isInvoicePoint");
                $sdata["dadaShopId"] = I("dadaShopId");
                $sdata["dadaOriginShopId"] = I("dadaOriginShopId");
                //$sdata["team_token"] = I("team_token");

                $m = M('shops');
                $shopId = $m->add($sdata);

                if ($shopId === false) {
                    // echo '添加店铺失败';
                    return '添加店铺失败';
                }
                M('shop_configs')->add(array('shopId'=>$shopId));

                //$shopId = (int)M('')->query("show table status like '__PREFIX__shops'")[0]['Auto_increment'];









                /* //---------注册达达商户------------
                $DaDaData_areas_mod = M('areas');
                $DaDaData = array(
                    'mobile'=> $data["userPhone"],
                    'city_name'=> str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName']),
                    'enterprise_name'=> $sdata["shopCompany"],
                    'enterprise_address'=>
                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName'].','.
                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName'].','.
                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName'].','.$sdata["shopAddress"],
                    'contact_name'=> $data["userName"],
                    'contact_phone'=> $data["userPhone"],
                    'email'=> $sdata['qqNo'].'@qq.com'
                );


                //exit(json_encode($DaDaData));
                $dadam = D("Adminapi/dada");
                $dadamod = $dadam->merchantAdd($DaDaData);




                if(empty($dadamod['niaocmsstatic'])){
                    $shops_merchantAdd_dadaShopId['dadaShopId'] = $dadamod;
                    $m->where("shopId = '{$shopId}'")->save($shops_merchantAdd_dadaShopId);
                    $source_id = $dadamod;
                }else{
                    //事物回滚
                    M()->rollback();
                    $rd = array('status'=>-4,'data'=>$dadamod,'info'=>'注册达达物流商户出错#'.$dadamod['info']);//注册达达物流商户出错
                    return $rd;
                }

                //---------创建门店----------
                unset($DaDaData);
                 $DaDaData = array(array(
                    'station_name'=> $sdata["shopName"],//	门店名称
                    'business'=> 19,//业务类型
                    'city_name'=>  str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName']),//城市名称
                    'area_name'=> str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName']),//区域名称(如,浦东新区)
                    'station_address'=> $DaDaData_areas_mod->where("areaId = '{$sdata["areaId1"]}'")->field('areaName')->find()['areaName'].','.
                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId2"]}'")->field('areaName')->find()['areaName'].','.
                    $DaDaData_areas_mod->where("areaId = '{$sdata["areaId3"]}'")->field('areaName')->find()['areaName'].','.$sdata["shopAddress"],
                    'lng'=> $sdata["longitude"],//门店经度
                    'lat'=> $sdata["latitude"],//门店纬度
                    'contact_name'=> $data["userName"],//联系人姓名
                    'phone'=> $data["userPhone"],//	联系人电话
                ));


                //echo (json_encode($DaDaData));
                unset($dadamod);
                $dadamod = $dadam->apiShopAdd($DaDaData,$source_id);//返回数组
                //exit($dadamod);
                //$dadamod = json_decode($dadamod,true);



                if(!empty($dadamod['successList'][0]['originShopId'])){
                    $shops_merchantAdd_dadaOriginShopId['dadaOriginShopId'] = $dadamod['successList'][0]['originShopId'];
                    $m->where("shopId = '{$shopId}'")->save($shops_merchantAdd_dadaOriginShopId);
                    //提交事物
                    M()->commit();
                }else{
                    //否则回滚
                    M()->rollback();
                    $rd = array('status'=>-5,'data'=>$dadamod,'info'=>'创建门店出错#'.$dadamod['info']);//创建门店出错
                    return $rd;
                }
                 */



                if(false !== $shopId){
                    //复制商家的商品
                    $param['shopSnCopy'] = I("shopSnCopy");
                    $param['shopId'] = $shopId;
                    copyShopGoods($param);
                    $rd['status']= 1;
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
        //M()->commit();
        //M()->rollback();
        unset($isdeliveryType);
        $isdeliveryType = I('deliveryType');
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

            $resDadaIsCity = self::Admin_Shops_dadaLogistics($getData);
            if($resDadaIsCity['status'] == -7){
                M()->rollback();
                $rd['status']= -1;
                $rd['msg'] = '达达在当前地区未开通城市!';
                return $rd;
            }

            if($resDadaIsCity['status'] == -4){
                M()->rollback();
                $rd['status']= -1;
                $rd['msg'] = '注册达达物流商户出错!';
                return $rd;
            }
            if($resDadaIsCity['status'] == -5){
                M()->rollback();
                $rd['status']= -1;
                $rd['msg'] = '创建门店出错!';
                return $rd;
            }
            //注册达达商户成功 在成功返回前进行事物提交
            /* 	if($resDadaIsCity['status'] == 1){
                    M()->commit();
                } */




        }

        //提交事物
        M()->commit();

        //更新ERP仓库相关的信息start;
        $sdata['shopId'] = $shopId;
        $this->updateStockTable($sdata);
        //更新ERP仓库相关的信息end

        $rd['hyh'] = 110;
        return $rd;
    }
    /**
     * 店铺管理->修改店铺
     */
    public function Admin_Shops_edit(){

        M()->startTrans();//开启事物
        $rd = array('status'=>-1);
        $shopId = (int)I('id',0);
        if($shopId==0)return $rd;
        $m = M('shops');
        //获取店铺资料
        $shops = $m->where("shopId=".$shopId)->find();
        //检测手机号码是否存在
        /*if(I("userPhone")!=''){
            $hasUserPhone = self::checkLoginKey(I("userPhone"),$shops['userId']);
            if($hasUserPhone==0){
                $rd = array('status'=>-2);
                return $rd;
            }
        }*/
        $data = array();
        $data["shopSn"] = I("shopSn");
        $data["areaId1"] = (int)I("areaId1");
        $data["areaId2"] = (int)I("areaId2");
        $data["areaId3"] = (int)I("areaId3");
        $data["goodsCatId1"] = (int)I("goodsCatId1");
        $data["isSelf"] = (int)I("isSelf",0);
        /* if($data["isSelf"]==1){
            $data["deliveryType"] = 1;
        }else{
            $data["deliveryType"] = 0;
        } */
        //$data["deliveryType"] = I("deliveryType");
        $data["deliveryType"] = 0;

        $data["shopName"] = I("shopName");
        $data["shopCompany"] = I("shopCompany");
        $data["shopImg"] = I("shopImg");
        $data["shopAddress"] = I("shopAddress");
        $data["deliveryStartMoney"] = I("deliveryStartMoney",0);
        $data["deliveryCostTime"] = I("deliveryCostTime",0);
        $data["deliveryFreeMoney"] = I("deliveryFreeMoney",0);
        $data["deliveryMoney"] = I("deliveryMoney",0);
        $data["avgeCostMoney"] = I("avgeCostMoney",0);
        $data["bankId"] = I("bankId");
        $data["bankNo"] = I("bankNo");
        $data["bankUserName"] = I("bankUserName");
        $data["longitude"] = (float)I("longitude");
        $data["latitude"] = (float)I("latitude");
        $data["mapLevel"] = (int)I("mapLevel",13);
        $data["isInvoice"] = I("isInvoice",1);
//		$data["serviceStartTime"] = I("serviceStartTime");
//		$data["serviceEndTime"] = I("serviceEndTime");
        $data["serviceStartTime"] = "0.00";
        $data["serviceEndTime"] = "23.59";
        $data["shopStatus"] = (int)I("shopStatus",0);
        $data["shopAtive"] = (int)I("shopAtive",1);
        $data["shopTel"] = I("shopTel");
        $data["commissionRate"] = I("commissionRate");


        /* //---------更新达达门店信息-----------
        $DaDaData_areas_mod = M('areas');
        unset($DaDaData);

        //	门店名称
        if($m->where("shopId=".$shopId)->field('shopName')->find()['shopName'] !== $data["shopName"]){
            $DaDaData['station_name']=$data["shopName"];
        }

        //城市名称
        if($m->where("shopId=".$shopId)->field('areaId2')->find()['areaId2'] !== $data["areaId2"]){
            $DaDaData['city_name']=str_replace(array('省','市'),'',$DaDaData_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName']);
        }

        //区域名称(如,浦东新区)
        if($m->where("shopId=".$shopId)->field('areaId3')->find()['areaId3'] !== $data["areaId3"]){
            $DaDaData['area_name']=str_replace(array('区','县'),'',$DaDaData_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName']);
        }

        //详细地址
        if($m->where("shopId=".$shopId)->field('shopAddress')->find()['shopAddress'] !== $data["shopAddress"]){
            $DaDaData['station_address']=
            $DaDaData_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'].' '.
            $DaDaData_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'].' '.
            $DaDaData_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'].' '.$data["shopAddress"];
        }

        //门店经度
        if($m->where("shopId=".$shopId)->field('longitude')->find()['longitude'] !== $data["longitude"]){
            $DaDaData['lng']=$data["longitude"];
        }

        //门店纬度
        if($m->where("shopId=".$shopId)->field('latitude')->find()['latitude'] !== $data["latitude"]){
            $DaDaData['lat']=$data["latitude"];
        }


    //	$data["userName"] = I("userName");
    //	$data["userPhone"] = I("userPhone");
     //	$DaDaData = array(
    //		'contact_name'=> $data["userName"],//联系人姓名
    //		'phone'=> $data["userPhone"],//	联系人电话
    //	);

        //业务类型
        if(!empty($DaDaData)){
            $DaDaData['business']=19;
        }



        if(!empty($shops['dadaOriginShopId']) and !empty($DaDaData)){
            unset($dadamod);
            $dadam = D("Adminapi/dada");
            $dadamod = $dadam->apiShopUpdate($DaDaData,$shops['dadaOriginShopId']);

            if(!empty($dadamod['niaocmsstatic'])){
                $rd = array('status'=>-6,'data'=>$dadamod,'info'=>'更新门店出错#'.$dadamod['info']);//更新门店出错
                return $rd;
            }
        } */




        //更新用户资料
        $sdata = array();
        $sdata["userName"] = I("userName");
        $sdata["userPhone"] = I("userPhone");
        $mod_users = M('users');
        $mod_users->where("userId ='{$shops['userId']}'")->save($sdata);



        if($this->checkEmpty($data,true)){
            $data['qqNo'] = I('qqNo');
            $data["invoiceRemarks"] = I("invoiceRemarks");
            $data["deliveryLatLng"] = I("deliveryLatLng");
            $data["isInvoicePoint"] = I("isInvoicePoint");
            $data["dadaShopId"] = I("dadaShopId");
            $data["dadaOriginShopId"] = I("dadaOriginShopId");
            //$data["team_token"] = I("team_token");
            $rs = $m->where("shopId=".$shopId)->save($data);
            if(false !== $rs){
                //更新ERP仓库相关的信息start;
                $data['shopId'] = $shopId;
                $this->updateStockTable($data);
                //更新ERP仓库相关的信息end
                $shopMessage = '';
                //如果[已通过的店铺]被改为未审核的话也要停止了该店铺的商品
                if($shops['shopStatus']!=$data['shopStatus']){
                    if($data['shopStatus']!=1){
                        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=0 where shopId=".$shopId;
                        $m->execute($sql);
                        $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
                    }
                    if($shops['shopStatus']!=1 && $data['shopStatus']==1){
                        $shopMessage = "您的店铺状态已被改为“已审核”状态，您可以出售自己的商品啦~";
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
                //检查用户类型
                $m = M('users');
                $userType = $m->where('userId='.$shops['userId'])->getField('userType');

                //保存用户资料
                $data = array();
                $data["userName"] = I("userName");
                $data["userPhone"] = I("userPhone");

                //如果是普通用户则提升为店铺会员
                if($userType==0){
                    $data["userType"] = 1;
                }
                $urs = $m->where("userId=".$shops['userId'])->save($data);
                $rd['status']= 1;

                //建立店铺和社区的关系
                $relateArea = self::formatIn(",", I('relateAreaId'));
                $relateCommunity = self::formatIn(",", I('relateCommunityId'));

                $m = M('shops_communitys');
                $m->where('shopId='.$shopId)->delete();
                if($relateArea!=''){
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




        //如果店铺存在达达店铺 则进行更新

        // if(!empty($shops['dadaShopId'])){
        //     /*$getData['shopName'] = I("shopName");//门店名称
        //     $getData['areaId2'] = (int)I("areaId2");//二级城市
        //     $getData['shopId'] = $shopId;//店铺ID
        //     $getData['areaId3'] = (int)I("areaId3");//第三级城市id
        //     $getData['areaId1'] = (int)I("areaId1");//第一级城市id
        //     $getData['shopAddress'] = I("shopAddress");//店铺地址
        //     $getData['longitude'] = I("longitude");//经度
        //     $getData['latitude'] = I("latitude");//纬度

        //     $isok = self::updateDadaShop($getData);
        //     if($isok['status'] == -6){
        //         $rd['status']= -1;
        //         //$rd = array('msg'=>'更新达达门店出错');
        //         $rd = array('msg'=>$isok);
        //         M()->rollback();
        //         return $rd;
        //     }*/

        // }else{//否则判断是否选择了 达达物流 如果选择了 进行注册
        //     //if(I("deliveryType") == 2){
        //     if(I("deliveryType") == 2 && empty($data["dadaShopId"]) && empty($data["dadaOriginShopId"])){ //如果用户手动填写达达商户id和门店编号则不再注册达达
        //         $getData['shopId'] = $shopId;//店铺id
        //         $getData['areaId2'] = (int)I("areaId2");//二级城市id
        //         $getData['userPhone'] = I("shopTel");//商家手机号
        //         $getData['areaId1'] = (int)I("areaId1");//第一级城市id
        //         $getData['shopCompany'] = I("shopCompany");//公司名称
        //         $getData['areaId3'] = (int)I("areaId3");//第三级城市id
        //         $getData['shopAddress'] = I("shopAddress");//门店地址
        //         $getData['userName'] = I('userName');//用户名称
        //         $getData['qqNo'] = I('qqNo');//用户QQ
        //         $getData['shopName'] = I('shopName');//门店名称 */
        //         $getData['longitude'] = I("longitude");//经度
        //         $getData['latitude'] = I("latitude");//纬度

        //         $resDadaIsCity = self::dadaLogistics($getData);
        //         if($resDadaIsCity['status'] == -7){
        //             M()->rollback();
        //             $rd['status']= -1;
        //             $rd['msg'] = '达达在当前地区未开通城市!';
        //             return $rd;
        //         }

        //         if($resDadaIsCity['status'] == -4){
        //             M()->rollback();
        //             $rd['status']= -1;
        //             $rd['msg'] = '注册达达物流商户出错!';
        //             return $rd;
        //         }
        //         if($resDadaIsCity['status'] == -5){
        //             M()->rollback();
        //             $rd['status']= -1;
        //             $rd['msg'] = '创建门店出错!';
        //             return $rd;
        //         }

        //     }


        // }


        //提交事物
        M()->commit();
        return $rd;
    }

    /**
     * 店铺管理->停止或者拒绝店铺
     */
    public function Admin_Shops_reject(){
        $rd = array('status'=>-1);
        $shopId = I('id',0);
        if($shopId==0)return rd;
        $m = M('shops');
        //获取店铺资料
        $shops = $m->where("shopId=".$shopId)->find();
        $data = array();
        $data['shopStatus'] = (int)I('shopStatus',-1);
        $data['statusRemarks'] = I('statusRemarks');
        if($this->checkEmpty($data,true)){
            $rs = $m->where("shopId=".$shopId)->save($data);
            if(false !== $rs){
                //如果[已通过的店铺]被改为停止或者拒绝的话也要停止了该店铺的商品
                if($shops['shopStatus']!=$data['shopStatus']){
                    $shopMessage = '';
                    if($data['shopStatus']!=1){
                        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=0 where shopId=".$shopId;
                        $m->execute($sql);
                        if($data['shopStatus']==0){
                            $shopMessage = "您的店铺状态已被改为“未审核”状态，如有疑问请与商场管理员联系。";
                        }else{
                            $shopMessage = I('statusRemarks');
                        }
                    }
                    $yj_data = array(
                        'msgType' => 0,
                        'sendUserId' => session('WST_STAFF.staffId'),
                        'receiveUserId' => $shops['userId'],
                        'msgContent' => I('statusRemarks'),
                        'createTime' => date('Y-m-d H:i:s'),
                        'msgStatus' => 0,
                        'msgFlag' => 1,
                    );
                    M('messages')->add($yj_data);
                }
                $rd['status'] = 1;
            }

        }
        return $rd;
    }

    /**
     * 店铺管理->删除店铺
     */
    public function Admin_Shops_del(){
        $shopId = (int)I('id');
        $rd = array('status'=>-1);
        //下架所有商品
        $sql = "update __PREFIX__goods set isSale=0,goodsStatus=-1 where shopId=".$shopId;
        $this->execute($sql);
        $sql = "select userId from __PREFIX__shops where shopId=".$shopId;
        $shop = $this->queryRow($sql);
        //删除登录账号
        $sql = "update __PREFIX__users set userFlag=-1 where userId=".$shop['userId'];
        $this->execute($sql);
        //标记店铺删除状态
        $data = array();
        $data["shopFlag"] = -1;
        $data["shopStatus"] = -2;
        $rs = M("shops")->where("shopId=".$shopId)->save($data);
        if(false !== $rs){
            //删除店铺,如果该店铺有对应的分支机构和仓库,也要删除
            $this->deleteStock($shopId);
            $rd['status']= 1;
        }
        return $rd;
    }

    /**
     * 店铺管理->查询登录关键字 注册时 纯数字不能等于11位
     */
    public function Admin_Shops_checkLoginKey($val,$id = 0){
        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1";
        $keyArr = array($val,$val,$val);
        if($id>0)$sql.=" and userId!=".(int)$id;
        $m = M('users');
        $rs = $m->where($sql,$keyArr)->find();


        if($rs==0){
            return 1;
        }
        return 0;
    }

    /*
     * 删除分支机构和仓库
     * @param int $shopId
     * */
    public function deleteStock($shopId){
        $shopId = (int)$shopId;
        if($shopId <= 0){
            return false;
        }
        //$db = sqlServerDB();
        $db = connectSqlServer();
        $relationInfo = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'isDelete'=>1])->find();
        if(isset($relationInfo['shopId']) && $relationInfo['shopId'] > 0 ){
            //SType
            $Sid = $relationInfo['Sid'];
            $sql = "SELECT TypeId,Sid,FullName FROM SType WHERE Sid='{$Sid}' ";
            /*$conn = $db->prepare($sql);
            $conn->execute();
            $stypeInfo = hanldeSqlServerData($conn,'row');*/
            $stypeInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
            if(!$stypeInfo){
                return false;
            }
            //Stock
            $sql = "SELECT typeId,Kid,FullName FROM Stock WHERE STypeID='".$stypeInfo['TypeId']."' AND deleted=0 ";
            /*$conn = $db->prepare($sql);
            $conn->execute();
            $stockInfo = hanldeSqlServerData($conn,'row');*/
            $stockInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
            //delete
            if($stockInfo){
                //删除仓库
                $sql = "UPDATE Stock SET deleted=1 WHERE Kid='".$stockInfo['Kid']."'";
                /*$conn = $db->prepare($sql);
                $updateRes = $conn->execute();*/
                $updateRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
                if($updateRes !== false){
                    //删除分支机构和店铺关系绑定记录
                    $deleteRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopId,'Sid'=>$stypeInfo['Sid']])->save(['isDelete'=>-1]);
                    if($deleteRelation !== false){
                        //删除分支机构
                        $sql = "UPDATE SType SET deleted=1 WHERE Sid='".$stypeInfo['Sid']."'";
                        /*$conn = $db->prepare($sql);
                        $updateRes = $conn->execute();*/
                        $updateRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
                    }
                }
            }
        }
    }

    /*
     * 添加或更新店铺的同时也要将ERP上对应的分支机构和仓库更新
     * @ array shopData PS:店铺信息
     * */
    public function updateStockTable($shopData){
        $shopId = I('id');
        if(isset($shopData['shopId'])){
            $shopId = $shopData['shopId'];
        }
        $userId = M('shops')->where(['shopId'=>$shopId])->getField('userId');
        $shopData['userName'] = '';
        $shopData['userPhone'] = '';
        if((int)$userId <= 0){
            return false;
        }
        $userInfo = M('users')->where(['userId'=>$userId])->field('userName,userPhone')->find();
        if($userInfo){
            $shopData['userPhone'] = $userInfo['userPhone'];
            $shopData['userName'] = $userInfo['userName'];
        }
        //wst_shops和ERP下的SType 关联表
        $relationWhere['shopId'] = $shopData['shopId'];
        $relationWhere['isDelete'] = 1;
        $relationRes = madeDB('shops_stype_relation')->where($relationWhere)->find();
        $pinyin = D('Made/Pinyin');
        $shortPinyin = strtoupper($pinyin::getShortPinyin($shopData['shopName']));
        //$db = sqlServerDB();
        $db = connectSqlServer();
        if($relationRes){
            //update
            $actionId = $relationRes['Sid'];
        }else{
            //添加一个分支机构
            $PyCode = $shortPinyin;
            $field = "parid,leveal,sonnum,soncount,FullName,PyCode,calcFullName,Address,Tel,LinkMan";
            //INSERT INTO SType (parid,leveal,sonnum,soncount,FullName,PyCode,calcFullName,Address,Tel,LinkMan) VALUES ('00000',1,0,0,'','','','','','ceshi510')
            $sql = "INSERT INTO SType ($field) VALUES ('00000',1,0,0,'{$shopData['shopName']}','".$PyCode."','{$shopData['shopName']}','{$shopData['shopAddress']}','{$shopData['shopTel']}','{$shopData['userName']}') ";
            /*$conn = $db->prepare($sql);
            $insertRes = $conn->execute();*/
            $insertRes = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
            if($insertRes){
                $sql = "SELECT IDENT_CURRENT('SType')";
                /*$conn = $db->prepare($sql);
                $conn->execute();
                $insertRow = hanldeSqlServerData($conn,'row');
                unset($conn);*/
                $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
                $actionId = $insertRow[''];
            }
            if(isset($actionId) && $actionId > 0 ){
                //分支机构和店铺绑定
                $relation = [];
                $relation['shopId'] = $shopData['shopId'];
                $relation['Sid'] = $actionId;
                madeDB('shops_stype_relation')->add($relation);
            }
        }
        if(isset($actionId) && $actionId > 0 ){
            $typeId = str_pad($actionId,5,0,STR_PAD_LEFT );
            $userCode = str_pad($actionId,2,0,STR_PAD_LEFT );
            $verifyID = str_pad($actionId,5,0,STR_PAD_LEFT );
            //FullName,PyCode,calcFullName,Address,Tel
            $sql = "UPDATE SType SET TypeId='".$typeId."', UserCode='".$userCode."', VerifyID='".$verifyID."',FullName='{$shopData['shopName']}',PyCode='{$shortPinyin}',calcFullName='{$shopData['shopName']}',Address='{$shopData['shopAddress']}',Tel='{$shopData['shopTel']}',LinkMan='{$shopData['userName']}' WHERE Sid='".$actionId."'";
            /*$conn = $db->prepare($sql);
            $updataStype = $conn->execute();*/
            $updataStype = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
            if($updataStype){
                //创建一个隶属于该分支机构的仓
                $sql = "SELECT Kid,StypeId FROM Stock ";
                $sql .= " WHERE STypeID ='".$typeId."' AND deleted=0 ";
                /*$conn = $db->prepare($sql);
                $conn->execute();
                $stockInfo = hanldeSqlServerData($conn,'row');*/
                $stockInfo = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
                $shopData['shopName'] .= '仓库';
                $shortPinyinStock = strtoupper($pinyin::getShortPinyin($shopData['shopName']));
                if(!$stockInfo){
                    $field = 'parid,leveal,sonnum,soncount,FullName,Tel,LinkMan,PyCode,STypeID,mobile,[Add]';
                    $value = "'00000',1,0,0,'{$shortPinyinStock}','{$shopData['shopTel']}','{$shopData['userName']}','$shortPinyin','$typeId','{$shopData['userPhone']}','{$shopData['shopAddress']}'";
                    $sql = "INSERT INTO Stock($field) VALUES ($value) ";
                    /*$conn = $db->prepare($sql);
                    $insertStock = $conn->execute();*/
                    $insertStock = handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
                    if($insertStock){
                        $sql = "SELECT IDENT_CURRENT('Stock')";
                        /*$conn = $db->prepare($sql);
                        $conn->execute();
                        $insertRow = hanldeSqlServerData($conn,'row');*/
                        $insertRow = handleReturnData($db->sqlQuery(C('sqlserver_db'),$sql,'row'));
                        $stockId = $insertRow[''];
                    }
                }else{
                    $stockId = $stockInfo['Kid'];
                }
                if(isset($stockId) && (int)$stockId > 0 ){
                    //更新下仓库其他相关信息
                    $typeId = str_pad($stockId,5,0,STR_PAD_LEFT );
                    $userCode = str_pad($stockId,2,0,STR_PAD_LEFT );
                    $sql = "UPDATE Stock SET FullName='{$shopData['shopName']}',Tel='{$shopData['shopTel']}',LinkMan='{$shopData['userName']}',PyCode='{$shortPinyinStock}',mobile='{$shopData['userPhone']}',typeId='{$typeId}',userCode='{$userCode}',[Add]='{$shopData['shopAddress']}' WHERE Kid='{$stockId}' ";
                    /*$conn = $db->prepare($sql);
                    $conn->execute();*/
                    handleReturnData($db->sqlExcute(C('sqlserver_db'),$sql),'write');
                    //向货位表添加信息;PS:该表没有数据,后面有些操作无法进行
                    $this->insertCargoType($stockId);
                    //向部门表添加部分信息
                    $insertDepartment = $this->insertDepartment($shopId);
                    if($insertDepartment['apiCode'] == 0){
                        //$rec = $insertDepartment['apiData']['rec'];
                        //向职员表插入数据,这个时候相当于添加一个一级的职员分类
                        $this->insertEmployee($shopId);
                    }
                }
            }
        }
        //释放资源
        unset($conn);
        unset($db);
    }

    /*
     * 添加默认货位
     * @param int $stockId PS:仓库id
     * */
    public function insertCargoType($stockId){
        $stockId = (int)$stockId;
        if($stockId > 0){
            $sql = "SELECT typeId,parid,leveal,sonnum,soncount,FullName,UserCode,Kid FROM Stock WHERE Kid='".$stockId."' AND deleted=0 ";
            $stockInfo = sqlQuery($sql,'row');
            if(!$stockInfo){
                return false;
            }
            $sql = "SELECT CargoID FROM CargoType WHERE KtypeID='".$stockInfo['typeId']."' AND deleted=0 ";
            $cargoType = sqlQuery($sql,'row');
            if(!$cargoType){
                $userCode = $stockInfo['FullName']."默认货位";
                $fullName = $userCode;
                $comment = $userCode;
                $isDefault = 1;
                $pathNo = 0;
                $WdSyncFlag = 0;
                $PyCode = '';
                $field = "UserCode,FullName,KtypeID,Comment,PyCode,IsDefault,PathNo,WdSyncFlag";
                $value = "'{$userCode}','{$fullName}','{$stockInfo['typeId']}','{$comment}','{$PyCode}','{$isDefault}','{$pathNo}','{$WdSyncFlag}' ";
                $sql = "INSERT INTO CargoType($field) VALUES($value)";
                sqlExcute($sql);
            }
        }
    }

    /*
     * 添加部门
     * @param int $shopId
     * */
    public function insertDepartment($shopId){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '添加部门失败';
        $apiRes['apiState'] = 'error';
        $shopId = (int)$shopId;
        $shopInfo = M('shops s')
            ->join("left join wst_users u on u.userId=s.userId")
            ->where("s.shopId='".$shopId."' AND s.shopFlag=1")
            ->field("s.*,u.userName,u.userPhone")
            ->find();
        if(!$shopInfo){
            $apiRes['apiInfo'] = '店铺信息有误';
            return $apiRes;
        }
        $pinyin = new PinyinModel();
        //field
        $field = "[parid], [leveal], [sonnum], [soncount],[FullName], [Name], [Comment], [Tel], [LinkMan],[PyCode],[usercode]";

        //value
        $parid = '00000';
        $leveal = 1;
        $sonnum = 0;
        $soncount = 0;
        $FullName = $shopInfo['shopName'].'分部';
        $usercode = $shopInfo['shopSn'];//分部编号和店铺编号关联
        $Name = '';
        $Comment = '';
        $Tel = $shopInfo['userPhone'];
        $LinkMan = $shopInfo['userName'];
        $PyCode = strtoupper($pinyin::getShortPinyin($FullName));
        $value = "'{$parid}',$leveal,$sonnum,$soncount,'{$FullName}','{$Name}','{$Comment}','{$Tel}','{$LinkMan}','{$PyCode}','{$usercode}'";

        $sql = "SELECT typeid,parid,leveal,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
        $departmentInfo = sqlQuery($sql,'row');
        if(!$departmentInfo){
            //insert
            $sql = "INSERT INTO Department($field) VALUES($value)";
            $insertRes = sqlExcute($sql);
            if(!$insertRes){
                return $apiRes;
            }
            $insertId = sqlInsertId('Department');
            //$departmentInfo
        }else{
            $insertId = $departmentInfo['rec'];
        }
        if(isset($insertId) && (int)$insertId > 0){
            //update
            $typeId = str_pad($insertId,5,0,STR_PAD_LEFT );
            //$usercode = str_pad($insertId,3,0,STR_PAD_LEFT );
            $sql = "UPDATE Department SET typeid='{$typeId}',FullName='{$FullName}',Tel='{$shopInfo['userPhone']}',LinkMan='{$shopInfo['userName']}',PyCode='{$PyCode}' WHERE rec='".$insertId."' AND deleted=0";
            sqlExcute($sql);
        }

        $returnData['rec'] = $insertId;
        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '操作成功';
        $apiRes['apiState'] = 'success';
        $apiRes['apiData'] = $returnData;
        return $apiRes;
    }

    /*
     * 向ERP添加职员信息(相当于二级分类)
     * @param int $rec PS:部门id
     * @param int $shopId PS:店铺id
     * @param int $leveal 等级[2:二级|3:三级]
     * @param int $localEmployeeId 本地职员id
     * */
    public function insertEmployee($shopId=0,$leveal=2){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '操作失败';
        $apiRes['apiState'] = 'error';
        if(empty($shopId)){
            return $apiRes;
        }
        //shops
        $shopInfo = M('shops')->where(['shopId'=>$shopId,'shopFlag'=>1])->field('shopId,shopSn,shopName')->find();
        if(!$shopInfo){
            $apiRes['apiInfo'] = '店铺信息有误';
            return $apiRes;
        }
        //shops_stype_relation
        $shopStypeRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopInfo['shopId'],'isDelete'=>1])->find();
        //Stype
        $sql = "SELECT TypeId,FullName,Sid FROM Stype WHERE Sid='".$shopStypeRelation['Sid']."' AND deleted=0 ";
        $stypeInfo = sqlQuery($sql,'row');
        //Department
        $sql = "SELECT typeid,usercode,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
        $departmentInfo = sqlQuery($sql,'row');
        if(!$stypeInfo || !$departmentInfo){
            $apiRes['apiInfo'] = '分支机构信息有误或者默认部门信息有误';
            return $apiRes;
        }
        //构建参数
        $BtypeID = '00000';
        //$typeId = '0000100035';
        $Parid = '00001';
        $soncount = 1;
        $sonnum = 1;
        $FullName = $shopInfo['shopName'].'分部';
        $Name = '';
        $UserCode = $departmentInfo['rec'];
        $Department = $departmentInfo['FullName'];
        $DtypeID = $departmentInfo['typeid'];//和Department表中的typeid关联
        $PyCode = getPyCode($FullName);
        $MaxPre = 0;//每单抹零限额
        $STypeID = $stypeInfo['TypeId'];
        $HrPersonStatu = 4;

        $field = "BtypeID,Parid,leveal,soncount,sonnum,FullName,Name,UserCode,Department,DtypeID,PyCode,MaxPre,STypeID,HrPersonStatu";
        $value = "'{$BtypeID}','{$Parid}','{$leveal}','{$soncount}','{$sonnum}','{$FullName}','{$Name}','{$UserCode}','{$Department}','{$DtypeID}','{$PyCode}','{$MaxPre}','{$STypeID}','{$HrPersonStatu}'";
        $sql = "SELECT typeId,FullName,Eid FROM employee WHERE DtypeID='".$departmentInfo['typeid']."' AND leveal=$leveal AND UserCode='".$departmentInfo['rec']."' AND deleted=0 ";
        $employeeInfo = sqlQuery($sql,'row');
        if(!$employeeInfo){
            //employee(职员表)
            $sql = "INSERT INTO employee($field) VALUES($value)";
            $insertEmplyeeRes = sqlExcute($sql);
            if(!$insertEmplyeeRes){
                $apiRes['apiInfo'] = '职员分类添加失败';
                return $apiRes;
            }
            $employeeId = sqlInsertId('employee');
        }else{
            $employeeId = $employeeInfo['Eid'];
        }
        if(isset($employeeId) && (int)$employeeId > 0){
            $typeId = str_pad($employeeId,5,0,STR_PAD_LEFT );
            $typeId = '00001'.$typeId;
            $sql = "UPDATE employee SET typeId='{$typeId}' WHERE Eid='".$employeeId."'";
            sqlExcute($sql);
            //添加职员(三级)
            $this->insertEmployee3($shopId,0,1);
            $apiRes['apiCode'] = 0;
            $apiRes['apiInfo'] = '操作成功';
            $apiRes['apiState'] = 'success';
        }else{
            $apiRes['apiCode'] = -1;
            $apiRes['apiInfo'] = '操作失败';
            $apiRes['apiState'] = 'error';
        }
        return $apiRes;
    }

    /*
     * 向ERP添加职员信息(3级)
     * @param int $shopId PS:店铺id
     * @param int $isDefault 是否默认职员(0:否|1:是)
     * @param int $localEmployeeId 本地职员id
     * */
    public function insertEmployee3($shopId=0,$localEmployeeId=0,$isDefault=0){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '操作失败';
        $apiRes['apiState'] = 'error';
        if(empty($shopId)){
            return $apiRes;
        }
        //shops
        $shopInfo = M('shops')->where(['shopId'=>$shopId,'shopFlag'=>1])->field('shopId,shopSn,shopName')->find();
        if(!$shopInfo){
            $apiRes['apiInfo'] = '店铺信息有误';
            return $apiRes;
        }
        //shops_stype_relation
        $shopStypeRelation = madeDB('shops_stype_relation')->where(['shopId'=>$shopInfo['shopId'],'isDelete'=>1])->find();
        //Stype
        $sql = "SELECT TypeId,FullName,Sid FROM Stype WHERE Sid='".$shopStypeRelation['Sid']."' AND deleted=0 ";
        $stypeInfo = sqlQuery($sql,'row');
        //Department
        $sql = "SELECT typeid,usercode,FullName,rec FROM Department WHERE usercode='".$shopInfo['shopSn']."' AND deleted=0 ";
        $departmentInfo = sqlQuery($sql,'row');
        //获取二级信息,二级其实相当于分类,三级才是职员信息
        $leveal2Data = $this->getEmployeeInfoLeveal2($stypeInfo['TypeId']);
        if($leveal2Data['apiCode'] == -1){
            $apiRes['apiInfo'] = "职员二级分类信息有误";
            return $apiRes;
        }
        $leveal2Info = $leveal2Data['apiData'];

        //构建参数
        $BtypeID = '00000';
        //$typeId = '0000100035';
        $Parid = $leveal2Info['typeId'];
        $leveal = 3;
        $soncount = 0;
        $sonnum = 0;
        $FullName = $shopInfo['shopName'].'(默认职员)';
        $Name = '';
        $UserCode = $leveal2Info['UserCode'].'-'.'001';//默认职员相当于店铺登陆者
        $Department = $departmentInfo['FullName'];
        $DtypeID = $departmentInfo['typeid'];//和Department表中的typeid关联
        $MaxPre = 0;//每单抹零限额
        $STypeID = $stypeInfo['TypeId'];
        $HrPersonStatu = 4;
        if($isDefault == 0){
            $userInfo = M('user')->where("id='".$localEmployeeId."' and status != -1")->find();
            if(!$userInfo){
                $apiRes['apiInfo'] = '职员信息有误';
                return $apiRes;
            }
            $uid = str_pad($userInfo['id'],3,0,STR_PAD_LEFT );
            $FullName = $userInfo['name'];
            //$UserCode = $departmentInfo['rec'].'-'.$uid;
            $UserCode = $userInfo['id'];//默认职员相当于店铺登陆者
        }
        $PyCode = getPyCode($FullName);

        $field = "BtypeID,Parid,leveal,soncount,sonnum,FullName,Name,UserCode,Department,DtypeID,PyCode,MaxPre,STypeID,HrPersonStatu";
        $value = "'{$BtypeID}','{$Parid}','{$leveal}','{$soncount}','{$sonnum}','{$FullName}','{$Name}','{$UserCode}','{$Department}','{$DtypeID}','{$PyCode}','{$MaxPre}','{$STypeID}','{$HrPersonStatu}'";

        $sql = "SELECT typeId,FullName,Eid FROM employee WHERE DtypeID='".$departmentInfo['typeid']."' AND leveal=$leveal AND UserCode='".$UserCode."' AND deleted=0 ";
        $employeeInfo = sqlQuery($sql,'row');
        if(!$employeeInfo){
            //employee(职员表)
            $sql = "INSERT INTO employee($field) VALUES($value)";
            $insertEmplyeeRes = sqlExcute($sql);
            if(!$insertEmplyeeRes){
                $apiRes['apiInfo'] = '职员分类添加失败';
                return $apiRes;
            }
            $employeeId = sqlInsertId('employee');

            if($leveal == 3){
                //更新二级的的自己数量
                $sql = "UPDATE employee SET soncount=soncount+1,sonnum=sonnum+1 WHERE Eid='".$leveal2Info['Eid']."'";
                sqlExcute($sql);
            }
        }else{
            $employeeId = $employeeInfo['Eid'];
        }
        if(isset($employeeId) && (int)$employeeId > 0){
            $typeId = str_pad($employeeId,5,0,STR_PAD_LEFT );
            $typeId = $leveal2Info['typeId'].$typeId;
            $sql = "UPDATE employee SET typeId='{$typeId}' WHERE Eid='".$employeeId."'";
            sqlExcute($sql);
            $apiRes['apiCode'] = 0;
            $apiRes['apiInfo'] = '操作成功';
            $apiRes['apiState'] = 'success';
        }else{
            $apiRes['apiCode'] = -1;
            $apiRes['apiInfo'] = '操作失败';
            $apiRes['apiState'] = 'error';
        }
        return $apiRes;
    }

    /*
     * 获取分支机构对应的二级分类(只有一条)
     * @param int $STypeID PS:机构id
     * */
    public function getEmployeeInfoLeveal2($STypeID){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '暂无相关数据';
        $apiRes['apiState'] = 'error';
        if(empty($STypeID)){
            $apiRes['apiInfo'] = '分支机构信息有误';
            return $apiRes;
        }
        //一个本地店铺暂时只做一个分部
        $sql = "SELECT typeId,Parid,leveal,UserCode,FullName,Department,DtypeID,STypeID FROM employee WHERE deleted=0 AND leveal=2 AND STypeID='".$STypeID."' ";
        $leveal2Info = sqlQuery($sql,'row');
        if($leveal2Info){
            $apiRes['apiCode'] = 0;
            $apiRes['apiInfo'] = '获取数据成功';
            $apiRes['apiState'] = 'success';
            $apiRes['apiData'] = $leveal2Info;
        }
        return $apiRes;
    }

    /**
     * 总后台->店铺->批量同步店铺信息到ERP
     */
    public function batchShopToErp($param){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '操作失败';
        $apiRes['apiState'] = 'error';
        $shopId = explode(',',$param['shopId']);
        $where = [];
        $where['shopId'] = ['IN',$shopId];
        $shops = M('shops')->where($where)->field('shopId,shopSn,shopName,shopFlag')->select();
        if(empty($shops)){
            $apiRes['apiInfo'] = '暂无符合条件的店铺';
            return $apiRes;
        }
        //验证店铺数据
        foreach ( $shops as $index => $value) {
            if($value['shopFlag'] == -1){
                $apiRes['apiInfo'] = "店铺['{$value['shopName']}']状态有误";
                return $apiRes;
            }
        }
        foreach ($shops as $i => $item) {
            //更新ERP仓库相关的信息start;
            $data['shopId'] = $item['shopId'];
            $this->updateStockTable($data);
            //更新ERP仓库相关的信息end
        }
        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '操作成功';
        $apiRes['apiState'] = 'success';
        return $apiRes;
    }

    /*
     *获取ERP分支机构列表
     * */
    public function getSTypeList($param){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '暂无相关数据';
        $apiRes['apiState'] = 'error';
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $leveal = $param['leveal'];
        $keyword = $param['keyword'];
        $where = " leveal='{$leveal}' AND deleted=0 ";
        if(!empty($keyword)){
            $where .= " AND FullName LIKE '%{$keyword}%' ";
        }

        $orderBy = " ORDER BY Sid ASC ";
        $field = "typeId,FullName,Sid,UserCode";
        $result = sqlServerPageQuery($page,$pageSize,'SType','Sid',$where,$field,$orderBy);
        if(!empty($result['root'])){
            $apiRes['apiCode'] = 0;
            $apiRes['apiInfo'] = '获取数据成功';
            $apiRes['apiState'] = 'success';
            $apiRes['apiData'] = $result;
        }
        return $apiRes;
    }

    /*
     *总后台->店铺->分支机构同步到本地店铺
     * */
    public function batchSTypeToShops($param){
        $apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '操作失败';
        $apiRes['apiState'] = 'error';
        $leveal = 1;//固定值,传1
        $Sid = 0;
        if(!empty($param['Sid'])){
            $Sid = $param['Sid'];
        }
        $where = " Sid IN($Sid) AND deleted=0 AND leveal=$leveal ";
        $field = "TypeId,FullName,Sid,UserCode,Tel,Address";
        $sql = "SELECT $field FROM SType WHERE $where ORDER BY Sid ASC";
        $styleList = sqlQuery($sql);
        if(!$styleList){
            $apiRes['apiInfo'] = '没有符合条件的分支机构';
            return $apiRes;
        }
        //验证数据是否符合条件
        foreach ($styleList as $index => $item) {
            M()->startTrans();//开启事物
            $where = "parid='00000' AND deleted=0 AND STypeID='{$item['TypeId']}' AND leveal=1 ";
            $sql = "SELECT typeId,parid,leveal,FullName,UserCode,LinkMan,mobile,Kid,LinkMan,mobile FROM Stock WHERE $where ";
            $stockInfo = sqlQuery($sql,'row');
            if(!$stockInfo){
                $apiRes['apiInfo'] = "分支机构[{$item['FullName']}]缺少默认仓库,或者信息有误";
                return $apiRes;
            }

            if(empty($stockInfo['LinkMan']) || empty($stockInfo['mobile'])){
                $apiRes['apiInfo'] = "请补全仓库[{$stockInfo['FullName']}]的联系人和手机号";
                return $apiRes;
            }

            $shopRelation = madeDB('shops_stype_relation')->where(['Sid'=>$item['Sid'],'isDelete'=>1])->find();
            if(!$shopRelation){
                //先建立账号
                $hasLoginName = self::Admin_Shops_checkLoginKey($stockInfo['mobile']);
                if(!$hasLoginName){
                    $apiRes['apiInfo'] = "手机号或者登陆名已存在,请更换仓库[{$stockInfo['FullName']}]的手机号";
                    return $apiRes;
                }
                //用户资料
                $data = [];
                $data["loginName"] = $stockInfo['mobile'];
                $data["loginSecret"] = rand(1000,9999);
                $pwd = $stockInfo['mobile'];
                $data["loginPwd"] = md5($pwd.$data['loginSecret']);
                $data["userName"] = $stockInfo['LinkMan'];
                $data["userPhone"] = $stockInfo['mobile'];
                $data["userStatus"] = 1;
                $data["userType"] = 1;
                $data["userEmail"] = '';
                $data["userQQ"] = '';
                $data["userScore"] = 0;
                $data["userTotalScore"] = 0;
                $data["userFlag"] = 1;
                $data["createTime"] = date('Y-m-d H:i:s');
                $m = M('users');
                $userId = $m->add($data);
                if(!$userId){
                    M()->rollback();
                    $apiRes['apiInfo'] = "添加用户失败";
                    return $apiRes;
                }
                //添加店铺信息
                $shop['shopSn'] = $item['UserCode'];
                $shop['userId'] = $userId;
                $shop['shopName'] = $item['FullName'];
                $shop['shopTel'] = $item['Tel'];
                $shop['shopAddress'] = $item['Address'];
                $shop['createTime'] = date('Y-m-d H:i:s',time());
                $defaultGoodsCat = M('goods_cats')->where(['isShow'=>1,'parentId'=>0,'catFlag'=>1])->order('catId asc')->find();
                $shop['goodsCatId1'] = $defaultGoodsCat['catId'];
                $shopId = M('shops')->add($shop);
                if(!$shopId){
                    M()->rollback();
                }
                $shop['shopId'] = $shopId;
                M('shop_configs')->add(array('shopId'=>$shopId));
                //绑定关系
                $relation = [];
                $relation['Sid'] = $item['Sid'];
                $relation['shopId'] = $shopId;
                $relationRes = madeDB('shops_stype_relation')->add($relation);
                M()->commit();
                $this->updateStockTable($shop);
            }
        }
        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '操作成功';
        $apiRes['apiState'] = 'success';
        return $apiRes;
    }



}
?>
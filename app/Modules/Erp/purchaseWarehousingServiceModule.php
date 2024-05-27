<?php
namespace App\Modules\Erp;

use App\Models\GoodsModel;
// use App\Models\LimitGoodsBuyLog;
// use App\Models\LimitGoodsBuyLogModel;
// use App\Models\SkuGoodsSelfModel;
// use App\Models\SkuGoodsSystemModel;
// use App\Modules\Shops\ShopCatsModule;
// use App\Modules\Users\UsersModule;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

use App\Modules\Erp\purchaseWarehousing\enteringWarehouseModule;//入库服务类

use App\Modules\Goods\GoodsModule;//商品服务类

use App\Modules\Erp\ErpModule;//Erp通用基础服务类

/**
 * 采购入库服务类 主要调用各module中的原子函数实现整个业务
 */
class purchaseWarehousingServiceModule extends Base
{

  /**
     * 采购入库单-入库操作
     */
    public function purchaseWarehousing($otpId,$goodsArr,$shopInfo){

        $response = LogicResponse::getInstance();
        $Mod_enteringWarehouseModule = new enteringWarehouseModule();//入库服务类
        $Mod_GoodsModule = new GoodsModule();//商品服务类
        $model_trans = new Model();
        $mod_ErpModule = new ErpModule();//进销存通用基础服务类
        

        //检测是否可入库
        $state = $Mod_enteringWarehouseModule->isEnteringWarehouse($otpId);
        if($state == false){
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('该入库单未收货或已完成，或不存在该入库单')->toArray();
        }

        //校验商品是否属于采购入库单
        foreach ($goodsArr as $item) {
            if($Mod_enteringWarehouseModule->isGoodsInEnteringWarehouse($otpId,$item['goodsId'],$item['skuId']) == false){
                $msg = $item['goodsName'].'#不在采购单中';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        $model_trans->startTrans();//开启事物

        //更改入库单商品数量
        foreach ($goodsArr as $item) {
            //所调用的函数内不需要事物 不存在嵌套事物 所以这里不管调用函数那边的事物
            if($Mod_enteringWarehouseModule->updateGoodsWarehouseCompleteNum($otpId,$item['totalNum'],$item['goodsId'],$item['skuId']) == false){
                $model_trans->rollback();
                $msg = $item['goodsName'].'#入库失败 一般为入库数量不对';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }

        //获取采购单详情
        $order_deital = (new enteringWarehouseModule())->getEnteringWarehouse($otpId);

        //写入采购单日志
        $actionUserId = !empty($shopInfo['id'])?$shopInfo['id']:$shopInfo['shopId'];
        $actionUserName = !empty($shopInfo['id'])?$shopInfo['name']:$shopInfo['shopName'];
        $inputShopId = $shopInfo['shopId'];
        
        foreach ($goodsArr as $item) {
            $logParams = [];
            $logParams['dataId'] = $otpId;
            $logParams['dataType'] = 1;
            $logParams['action'] = "商品#{$item['goodsName']}入库+{$item['totalNum']}";

            $logParams['actionUserId'] = !empty($uid)?$uid:$inputShopId;
            $logParams['actionUserType'] = $shopInfo['login_type'];
            $logParams['actionUserName'] = $actionUserName;

            $logParams['status'] = $order_deital['warehouseStatus'];
            $logParams['warehouseStatus'] = 2;

            if($mod_ErpModule->addBillActionLog($logParams) == false){
                $model_trans->rollback();
                $msg = $item['goodsName'].'#日志添加失败';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        

        //变更#分仓#商品库存
        foreach ($goodsArr as $item) {

            // 由于采购单里是存放总仓商品id 这里需要根据goodsSn查询拿到分仓商品id 获取时不得为空
            $goodsDtail = $Mod_GoodsModule->getGoodsInfoByParams(['goodsSn'=>$item['goodsSn'],'shopId'=>$shopInfo['shopId']])['data'];
            if(empty($goodsDtail)){
                $model_trans->rollback();
                $msg = $item['goodsName'].'#库存变更失败,原因为 分仓不存在该商品';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }

            $status_returnGoodsStock = $Mod_GoodsModule->returnGoodsStock($goodsDtail['goodsId'],$item['skuId'],$item['totalNum'],1,1,$model_trans)['code'];
            if($status_returnGoodsStock != 0){
                // $sql = (new Model())->getLastSql();
                $model_trans->rollback();
                $msg = $item['goodsName'].'#库存变更失败';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }

        //写入商品相关日志 TODO:暂未支持sku商品
        foreach ($goodArr as $item) {
            //查询商品信息
            // $good_detail = $Mod_GoodsModule->getGoodsInfoById($item['goodsId']);
            $goodsDtail = $Mod_GoodsModule->getGoodsInfoByParams(['goodsSn'=>$item['goodsSn'],'shopId'=>$shopInfo['shopId']])['data'];
            if(empty($goodsDtail)){
                $model_trans->rollback();
                $msg = $item['goodsName'].'#库存变更失败,原因为 分仓不存在该商品';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }


            if(!$Mod_GoodsModule->addGoodsLog($shopInfo,$goodsDtail,"{$item['goodsName']}#入库+{$item['totalNum']}")){
                $model_trans->rollback();
                $msg = $item['goodsName'].'#商品日志写入错误';
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        
        


        //变更入库单状态
        $otpId_state = 1;//部分入库
        if($Mod_enteringWarehouseModule->getGoodswarehouseStatus($otpId) == true){
            $otpId_state = 2;//完成入库
        }
        if($Mod_enteringWarehouseModule->updateStateEnteringWarehouse($otpId,$otpId_state) == false and (new enteringWarehouseModule())->getEnteringWarehouse($otpId)['warehouseStatus'] != $otpId_state){
            $model_trans->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('入库单状态变更失败')->toArray();
        }
        

        //TODO:修改入库单-入库人员 后面要新加字段冗余入库人员姓名暂未做
        
        //提交事物
        $model_trans->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->toArray();

        //所需要的商品格式
         // [{
        //     "goodsId": "68",
        //        "skuId":123,
        //         "goodsName": "笔记本",
        //         "goodsImg": "qiniu://Fr67dmGbRs_SfRrK_80i1LnX8_Aj",
        //         "buyPirce": "5.00",
        //         "warehouseCompleteNum": "0",
        //         "warehouseNoNum": 3,
        //         "warehouseNum": "3"
        //     "totalNum": 103,
        //     "toSupplierId": "",
        //     "remark": ""
        // }, {
        //     "goodsId": "1562",
            // "skuId":123,
        //     "totalNum": 6,
        //     "remark": "",
        //         "goodsName": "420ml厨邦香醋王",
        //         "goodsImg": "",
        //         "buyPirce": "10.80"
        // }]

    }

     



     /**
      * TODO:未开始
      * 采购退货单
      */



}
<?php
namespace App\Modules\Erp\purchaseWarehousing;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

use App\Models\jxcPurchaseOrderModel;//采购单主表
use App\Models\jxcPurchaseOrderInfoModel;//采购单明细表【采购单商品表】


/**
 * 入库服务类 主要提供原子函数
 * 采购入库单是同一个张表
 */
class enteringWarehouseModule extends Base
{

    /**
     * 校验采购入库单状态 是否可入库 部分入库也是可以继续入库
     * otpId 采购单id
     * return true可入库|false不可入库
     */
    public function isEnteringWarehouse($otpId){
        
        //条件预备
        $where['otpId'] = (int)$otpId;//采购单id
        $where['status'] = 1;//审核状态1:平台已审核
        $where['warehouseStatus'] = array('in','0,1');//入库状态(0:待入库|1:部分入库|2:入库完成)
        $where['receivingStatus'] = 1;//收货状态(0:采购方待收货|1:采购方已收货)
        
        $where['dataFlag'] = 1;//有效状态(-1:删除|1:有效)

        $data = (new jxcPurchaseOrderModel())->where($where)->count();
        if($data > 0){
            return true;
        }else{
            return false;
        }



    }


     /**
      * 【入库单id】 获取入库单详情
      * otpId 采购单id
      */
    public function getEnteringWarehouse($otpId){
        //条件预备
        $where['otpId'] = (int)$otpId;//采购单id
        $where['dataFlag'] = 1;//有效状态(-1:删除|1:有效)

        $data = (new jxcPurchaseOrderModel())->where($where)->find();
        
        return $data;

    }


      /**
       * 【商品id|商品skuId】校验商品是否属于某采购入库单
       * 如果只有条码 那就通过商品域下的根据条码获取商品信息 拿到ID即可 
       * goodsId 商品id
       * skuId
       * return true存在|false不存在
       */
      public function isGoodsInEnteringWarehouse($otpId,$goodsId,$skuId){
             //条件预备
            $where['otpId'] = (int)$otpId;//采购单id
            if(!empty($skuId)){
                $where['skuId'] = (int)$skuId;
            }
            $where['goodsId'] = (int)$goodsId;
            

            $data = (new jxcPurchaseOrderInfoModel())->where($where)->count();
            if($data > 0){
                return true;//存在
            }else{
                return false;
            }

        }


       /**
        * 【入库单id、变更入库状态】入库单 入库状态变更
        * 注意这里只能变更入库相关状态 其他状态不支持
        * return true成功|false失败
        */

        public function updateStateEnteringWarehouse($otpId,$state){

            //条件预备
            $where['otpId'] = (int)$otpId;//采购单id
            $where['dataFlag'] = 1;//有效状态(-1:删除|1:有效)
            $where['warehouseStatus'] = array('in','0,1,2');//约束只能变更入库部分的状态

            //数据预备
            $save['warehouseStatus'] = (int)$state;//入库状态(0:待入库|1:部分入库|2:入库完成)
            $data = (new jxcPurchaseOrderModel())->where($where)->save($save);
            if($data){
                return true;
            }else{
                return false;
            }
        }

        /**
         * 获取入库单商品数据集【根据采购单ID】
         */

        public function getGoodsList($otpId){
            //条件预备
            $where['otpId'] = (int)$otpId;//采购单id
            $data = (new jxcPurchaseOrderInfoModel())->where($where)->select();
            return $data;
        }


        /**
         * 单次更新入库单商品【已入库数量】
         * sum 本次单品合计入库量
         * otpId 采购单id
         */
        public function updateGoodsWarehouseCompleteNum($otpId,$sum,$goodsId,$skuId){
            //条件预备
            $where['otpId'] = (int)$otpId;//采购单id
            $where['goodsId'] = (int)$goodsId;
            $where['warehouseNum'] = array('exp',">= warehouseCompleteNum+{$sum}");//剩余入库数量不得大于应入库数量
            if(!empty($skuId)){
                $where['skuId'] = (int)$skuId;
            }
            $data = (new jxcPurchaseOrderInfoModel())->where($where)->setInc("warehouseCompleteNum",(int)$sum);
            if($data){
                return true;
            }else{
                return false;
            }
        }

        /**
         * 获取入库单商品是否齐全 一般用于计算【待入库、部分入库、已入库】状态
         * return 齐全true|不齐全false
         * 可能会发生0入库变成了待入库 不过能走到这个逻辑一般不会存在0的情况
         */
        public function getGoodswarehouseStatus($otpId){
            //条件预备
            $where['otpId'] = (int)$otpId;//采购单id
            $where['warehouseNum'] = array('exp',' > warehouseCompleteNum');//应入小于已入

            $data = (new jxcPurchaseOrderInfoModel())->where($where)->count();

            // $sql = (new Model())->getLastSql();
            // exit($sql);

            if($data == 0){
                return true;//都已入库
            }else{
                return false;
            }
        }



}
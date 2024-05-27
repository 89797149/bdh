<?php

namespace Adminapi\Action;

use Adminapi\Model\CronJobsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 定时任务控制器
 */
class CronJobsAction extends BaseAction
{
    public function checkLocation()
    {
        if ('localhost' != strtolower($_SERVER['HTTP_HOST'])) {
            WSTLog("Apps/Runtime/Logs/url.log", strtolower($_SERVER['HTTP_HOST']) . "--denied", true);
            exit();
        }
    }

    //检测接口密码
    public function checkPass()
    {

    }

    /**
     * 自动收货
     */
    public function autoReceivie()
    {
        // $this->checkLocation();
        WSTLog("Apps/Runtime/Logs/autoReceivie.log", "自动收货--start\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoReceivie();
        WSTLog("Apps/Runtime/Logs/autoReceivie.log", "自动收货--end\r\n", true);
        echo "done";
    }

    /**
     * 自动好评
     */
    public function autoGoodAppraise()
    {
        // $this->checkLocation();
        WSTLog("Apps/Runtime/Logs/autoGoodAappraise.log", "自动好评--start\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoGoodAppraise();
        WSTLog("Apps/Runtime/Logs/autoGoodAappraise.log", "自动好评--end\r\n", true);
        echo "done";
    }

    /**
     * 自动结算
     */
    public function autoSettlement()
    {
        // $this->checkLocation();
        WSTLog("Apps/Runtime/Logs/autoSettlement.log", "自动好评--start\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoSettlement();//7天自动好评
        WSTLog("Apps/Runtime/Logs/autoSettlement.log", "自动好评--end\r\n", true);
        echo "done";
    }

    //自动取消未付款的订单 返还优惠券 积分 库存
    public function autoReOrder()
    {
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoReNopayOrder.log", "自动取消未支付订单--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoReOrder();//10分钟自动取消未支付订单
        WSTLog("Apps/Runtime/Logs/autoReNopayOrder.log", "自动取消未支付订单--end#$time\r\n", true);
        echo "done";

    }

    //拼团失败，自动退费用、退库存（弃用）
    /*public function autoReAssembleOrder(){
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoReAssembleOrder.log","拼团失败，自动退费用、退库存--start#$time\r\n",true);
        $m = D('Adminapi/CronJobs');
        $m->autoReAssembleOrder();//10分钟自动退费用、退库存
        WSTLog("Apps/Runtime/Logs/autoReAssembleOrder.log","拼团失败，自动退费用、退库存--end#$time\r\n",true);
        echo "done";

    }*/

    //自动更新拼团状态（弃用）
    public function autoUpdateAssembleState()
    {
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoUpdateAssembleState.log", "自动更新拼团状态--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoUpdateAssembleState();//每分钟自动更新拼团状态
        WSTLog("Apps/Runtime/Logs/autoUpdateAssembleState.log", "自动更新拼团状态--end#$time\r\n", true);
        echo "done";

    }

    //(每月)自动发放优惠券
    public function autoSendCoupon()
    {
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoSendCoupon.log", "自动发放优惠券--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoSendCoupon();//每分钟自动更新拼团状态
        WSTLog("Apps/Runtime/Logs/autoSendCoupon.log", "自动发放优惠券--end#$time\r\n", true);
        echo "done";

    }

    //(每分钟)(盘点端)自动更改盘点任务状态（弃用）
    //比如：盘点时间到了，自动更改盘点状态，将待盘点改为盘点中
    public function autoUpdateInventoryState()
    {
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoUpdateInventoryState.log", "自动更改盘点任务状态--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoUpdateInventoryState();//每分钟自动更新盘点任务状态
        WSTLog("Apps/Runtime/Logs/autoUpdateInventoryState.log", "自动更改盘点任务状态--end#$time\r\n", true);
        echo "done";
    }

    //(每分钟)(盘点端)自动更改入库任务状态（弃用）
    //比如：入库时间到了，自动更改入库状态，将待入库改为入库中
    public function autoUpdateInWarehouseState()
    {
        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/autoUpdateInWarehouseState.log", "自动更改入库任务状态--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoUpdateInWarehouseState();//每分钟自动更新入库任务状态
        WSTLog("Apps/Runtime/Logs/autoUpdateInWarehouseState.log", "自动更改入库任务状态--end#$time\r\n", true);
        echo "done";
    }

    /**
     * 5分钟未支付，自动取消Pos订单
     * 每分钟执行一次
     */
    public function autoCancelPosOrder()
    {
        // $this->checkLocation();
        WSTLog("Apps/Runtime/Logs/autoCancelPosOrder.log", "自动取消POS订单--start\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->autoCancelPosOrder();
        WSTLog("Apps/Runtime/Logs/autoCancelPosOrder.log", "自动取消POS订单--end\r\n", true);
        echo "done";
    }

    //(每分钟)精准营销数据入redis
    //每分钟执行一次
    public function precisionMarketingDataToRedis()
    {
//        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/precisionMarketingDataToRedis.log", "精准营销数据入redis--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->precisionMarketingDataToRedis();//精准营销数据入 redis
        WSTLog("Apps/Runtime/Logs/precisionMarketingDataToRedis.log", "精准营销数据入redis--end#$time\r\n", true);
        echo "done";
    }

    //(每分钟)redis中精准营销数据发送到用户
    //每分钟执行一次
    public function sendMsgToUserFromPrecisionMarketingData()
    {
//        $this->checkPass();
        $time = date("Y-m-d H:i:s");
        WSTLog("Apps/Runtime/Logs/sendMsgToUserFromPrecisionMarketingData.log", "redis中精准营销数据发送到用户--start#$time\r\n", true);
        $m = D('Adminapi/CronJobs');
        $m->sendMsgToUserFromPrecisionMarketingData();//精准营销数据入 redis
        WSTLog("Apps/Runtime/Logs/sendMsgToUserFromPrecisionMarketingData.log", "redis中精准营销数据发送到用户--end#$time\r\n", true);
        echo "done";
    }

    /**
     * 检查配送费 PS;只针对同一运费模式
     * */
    public function autoCheckOrderDeliverMoney()
    {
        $m = D('Adminapi/CronJobs');
        $m->autoCheckOrderDeliverMoney();
        echo "done";
    }

    /**
     * 处理个别用户已收货补差价未到的问题
     * */
    public function autoHandleGoodsDiffMoney()
    {
        $m = D('Adminapi/CronJobs');
        $m->autoHandleGoodsDiffMoney();
        echo "done";
    }

    /**
     * 定时更新直播相关的参数
     **/
    public function autoUpdateLivePlay()
    {
        //$m = D('Adminapi/CronJobs');
        $m = new CronJobsModel();
        $m->autoUpdateLivePlay();
        echo "done";
    }

    /**
     * 定时上下架商品
     * */
    public function autoSaleGoods()
    {
        $m = new CronJobsModel();
        $m->autoSaleGoods();
        echo "done";
    }

    /**
     * 定时受理订单、打印小票
     */
    public function autoAcceptanceOrder()
    {
        $m = new CronJobsModel();
        $m->autoAcceptanceOrder();
        echo "done";
    }
}
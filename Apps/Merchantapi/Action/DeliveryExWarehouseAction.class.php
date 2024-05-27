<?php
/**
 * 发货出库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 15:26
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\DeliveryExWarehouseModel;

class DeliveryExWarehouseAction extends BaseAction
{
    /**
     * 发货出库-发货出库列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zpaoey
     * */
    public function getDeliveryExWarehouseList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'sortingOrderStatus' => '',//分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
            'deliveryStatus' => '',//发货状态(0:未发货 1:已发货) 不传默认全部
            'lineId' => '',//线路id,不传默认全部
            'requireDate' => '',//要求送达日期
            'paymentKeywords' => '',//关键字(客户名称/联系人/电话)
            'page' => 1,//分页
            'pageSize' => 15,//分页条数
            'export' => 0,//是否导出(0:不导出 1:导出)
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['requireDate'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->getDeliveryExWarehouseList($paramsInput);
        $this->ajaxReturn($result);

    }

    /**
     * 发货出库-发货出库详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/apynmk
     * */
    public function getDeliveryExWarehouseDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paymentUserid = (int)I('payment_userid');
        $requireDate = I('requireDate');
        $orderId = (int)I('orderId');
        if (empty($paymentUserid)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择客户'));
        }
        if (empty($requireDate)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->getDeliveryExWarehouseDetail($shopId, $paymentUserid, $requireDate, $orderId);
        $this->ajaxReturn($result);

    }

    /**
     * 发货出库-订单发货出库列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/htvwct
     * */
    public function getDeliveryExWarehouseOrderList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'sortingOrderStatus' => '',//分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
            'deliveryStatus' => '',//发货状态(0:未发货 1:已发货) 不传默认全部
            'lineId' => '',//线路id,不传默认全部
            'requireDate' => '',//要求送达日期
            'paymentKeywords' => '',//关键字(客户名称/订单号/联系人/电话)
            'page' => 1,//分页
            'pageSize' => 15,//分页条数
            'export' => 0,//是否导出(0:不导出 1:导出)
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['requireDate'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->getDeliveryExWarehouseOrderList($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 发货出库-打印
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dxi8g4
     * */
    public function printDeliveryExWarehouse()
    {
        $loginInfo = $this->MemberVeri();
        $orderIdArr = json_decode(htmlspecialchars_decode(I('orderIdArr')), true);
        if (empty($orderIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择客户'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->printDeliveryExWarehouse($loginInfo, $orderIdArr);
        $this->ajaxReturn($result);
    }

    /**
     * 发货出库-执行发货出库
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/igova5
     * */
    public function actionDeliveryExWarehouse()
    {
        $loginInfo = $this->MemberVeri();
        $orderIdArr = json_decode(htmlspecialchars_decode(I('orderIdArr')), true);
        if (empty($orderIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择客户'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->actionDeliveryExWarehouse($loginInfo, $orderIdArr);
        $this->ajaxReturn($result);

    }

    /**
     * 发货出库-一键发货出库
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gxpqd9
     * */
    public function oneKeyActionDeliveryExWarehouse()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'requireDate' => '',
            'paymentKeywords' => '',
            'lineId' => '',
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['requireDate'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->oneKeyActionDeliveryExWarehouse($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 发货出库-获取打印数据
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/aqfd1r
     * */
    public function getPrintDeliveryExWarehouseData()
    {
        $this->MemberVeri();
        $orderIdArr = json_decode(htmlspecialchars_decode(I('orderIdArr')), true);
        if (empty($orderIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要打印的数据'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->getPrintDeliveryExWarehouseData($orderIdArr);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 发货出库-商品发货差异列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yl7e82
     * */
    public function getDiffDeliveryExWarehouseGoods()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'lineId' => '',
            'requireDate' => '',
            'goodsKeywords' => '',//商品关键字(商品名/编码)
            'sortDiffNum' => '',//分拣差异值(1:差异小于0 2:差异大于0 3:差异等于0) 不传默认全部
            'sortingGoodsStatus' => '',//商品分拣状态(0:未分拣  1:已分拣) 不传默认全部
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
            'export' => 0,//导出(0:不导出 1:导出)
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['requireDate'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $model = new DeliveryExWarehouseModel();
        $result = $model->getDiffDeliveryExWarehouseGoods($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 发货出库-发货差异商品报溢
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/czdf0r
     * */
    public function diffGoodsReportedOverflow()
    {
        $loginInfo = $this->MemberVeri();
        $requireDate = I('requireDate');
        if (empty($requireDate)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择日期'));
        }
        $idArr = json_decode(htmlspecialchars_decode(I('idArr')), true);//订单商品唯一关联id,不传默认全部
        $model = new DeliveryExWarehouseModel();
        $result = $model->diffGoodsReportedOverflow($requireDate, $idArr, $loginInfo);
        $this->ajaxReturn($result);
    }

}
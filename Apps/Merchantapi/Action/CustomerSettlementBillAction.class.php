<?php
/**
 * 客户结算单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-16
 * Time: 19:13
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\CustomerSettlementBillModel;

class CustomerSettlementBillAction extends BaseAction
{
    /**
     * 结算单-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/scwy12
     * */
    public function getCustomerSettlementBillList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'payType' => '',//支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
            'createDateStart' => '',//单据创建日期区间-开始日期
            'createDateEnd' => '',//单据创建日期区间-结束日期
            'customerKeywords' => '',//客户信息(客户名称/联系人/手机号)
            'settlementNo' => '',//结算单号
            'relation_order_number' => '',//业务单号
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new CustomerSettlementBillModel();
        $result = $mod->getCustomerSettlementBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 结算单-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ean0fr
     * */
    public function getCustomerSettlementBillDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $settlementId = (int)I('settlementId');
        $export = (int)I('export');//导出(0:不导出 1:导出)
        if (empty($settlementId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-settlementId异常'));
        }
        $mod = new CustomerSettlementBillModel();
        $result = $mod->getCustomerSettlementBillDetail($settlementId, $shopId, $export);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 客户结算-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/onmyxv
     * */
    public function getCustomerSettlementList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'requireDateStart' => '',//要求送达日期区间-开始日期
            'requireDateEnd' => '',//要求送达日期区间-结束日期
            'customerKeywords' => '',//关键字搜索(客户名称/手机号/订单号)
            'customerSettlementDateStart' => '',//结算日期区间-开始日期
            'customerSettlementDateEnd' => '',//结算日期区间-结束日期
            'payTimeStart' => '',//支付日期区间-开始日期
            'payTimeEnd' => '',//支付日期区间-结束日期
            'customerSettlementStatus' => '',//客户结算状态(0:未结算 1:已结算) 不传默认全部
            'deliveryStatus' => '',//发货状态(0:未发货 1:已发货) 不传默认全部
            'returnStatus' => '',//退货状态(0:无退货 1:有退货) 不传默认全部
            'lineId' => '',//线路id
            'driverId' => '',//司机id
            'rankId' => '',//客户类型id
            'payFrom' => array(),//支付方式(1:支付宝，2：微信,3:余额,4:货到付款)
            'export' => 0,//导出(0:否 1:是)
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['payFrom'] = json_decode(htmlspecialchars_decode($paramsInput['payFrom']), true);
        $mod = new CustomerSettlementBillModel();
        $result = $mod->getCustomerSettlementList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 客户结算-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bbryxs
     * */
    public function getCustomerSettlementDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $orderId = (int)I('orderId');
        $export = (int)I('export');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-orderId异常'));
        }
        $mod = new CustomerSettlementBillModel();
        $result = $mod->getCustomerSettlementDetail($orderId, $export, $shopId);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 客户结算-结算记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ompcmx
     * */
    public function getCustomerSettlementLog()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $orderId = (int)I('orderId');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-orderId异常'));
        }
        $mod = new CustomerSettlementBillModel();
        $result = $mod->getCustomerSettlementLog($orderId, $shopId);
        $this->ajaxReturn($result);
    }

    /**
     * 客户结算-结算-结算预览
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mbsfsr
     * */
    public function preDoCustomerSettlement()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $orderId = (int)I('orderId');
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-orderId异常'));
        }
        $mod = new CustomerSettlementBillModel();
        $result = $mod->preDoCustomerSettlement($orderId, $shopId);
        $this->ajaxReturn($result);
    }

    /**
     * 客户结算-结算-确认结算
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wizg03
     * */
    public function doCustomerSettlement()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'payType' => 0,
            'payerName' => '',
            'billPic' => array(),
            'billRemark' => '',
            'settlementList' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['settlementList'] = json_decode(htmlspecialchars_decode($paramsInput['settlementList']), true);
        if (empty($paramsInput['settlementList'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-settlementList异常'));
        }
        $paramsInput['billPic'] = json_decode(htmlspecialchars_decode($paramsInput['billPic']), true);
        $mod = new CustomerSettlementBillModel();
        $result = $mod->doCustomerSettlement($paramsInput);
        $this->ajaxReturn($result);
    }
}
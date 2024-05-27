<?php
/**
 * 调拨单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-26
 * Time: 10:27
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\AllocationBillModel;

class AllocationBillAction extends BaseAction
{
    /**
     * 调拨-获取可调拨的门店商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qmyxgm
     * */
    public function getAllocationGoodsList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'goodsId' => 0,
            'skuId' => 0,
            'num' => 0,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['goodsId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨商品有误'));
        }
        if ((float)$paramsInput['num'] <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->getAllocationGoodsList($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 调拨单-申请调拨
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/quno7t
     */
    public function addAllocationBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'goodsId' => 0,
            'skuId' => 0,
            'num' => 0,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['goodsId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨商品有误'));
        }
        if ((float)$paramsInput['num'] <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->addAllocationBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 调拨单-调入方修改单据
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/oy0yco
     */
    public function updateAllocationBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'allId' => 0,
            'goodsId' => 0,
            'skuId' => 0,
            'num' => 0,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['allId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        if (empty($paramsInput['goodsId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨商品有误'));
        }
        if ((float)$paramsInput['num'] <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->updateAllocationBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 调拨单-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ioucrx
     * */
    public function getAllocationBillList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'type' => 1,//类型(1:我的调入 2:我的调出 3:已完成的调拨)
            'billNo' => '',
            'goodsKeywords' => '',
            'status' => '', //调拨状态【1:调出方待确认|2:等待调入方收货|3:调出方已交货】
            'billDateStart' => '', //制单日期-开始日期
            'billDateEnd' => '', //制单日期-结束日期
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new AllocationBillModel();
        $result = $mod->getAllocationBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 调拨单-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lwan1v
     * */
    public function getAllocationBillDetail()
    {
        $this->MemberVeri();
        $allId = I('allId');
        if (empty($allId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->getAllocationBillDetail($allId);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 调拨单-调出方确认调拨
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ubeack
     * */
    public function confirmAllocation()
    {
        $loginInfo = $this->MemberVeri();
        $allId = I('allId');
        if (empty($allId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->confirmAllocation($allId, $loginInfo);
        $this->ajaxReturn($result);
    }

    /**
     * 调拨单-调出方发货
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pm29fo
     * */
    public function deliveryAllocation()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'allId' => 0,
            'billPic' => '',
            'consigneeName' => '',
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['allId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        if (empty($paramsInput['billPic'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请上传单据照片'));
        }
        if (empty($paramsInput['consigneeName'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请填写接货人姓名'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->deliveryAllocation($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 调拨单-调入方删除单据
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wytiy3
     * */
    public function delAllocation()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $allIdArr = json_decode(htmlspecialchars_decode(I('allIdArr')), true);
        if (empty($allIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要删除数据'));
        }
        if (!is_array($allIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new AllocationBillModel();
        $result = $mod->delAllocation($allIdArr, $shopId);
        $this->ajaxReturn($result);
    }
}
<?php

namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 发票功能类
 */
class InvoiceReceiptAction extends BaseAction
{
    /**
     * 获取发票列表
     */
    public function getPermitInvoiceList()
    {
        $shopInfo = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/InvoiceReceipt');
        $list = $m->getPermitInvoiceList($shopInfo);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * @return mixed
     * 变更发票状态
     */
    public function updatePermitInvoiceInfo()
    {
        $shopInfo = $this->MemberVeri()['shopId'];
        $receiptId = (int)I('receiptId');
        $invoiceImg = I('invoiceImg');
        if (empty($receiptId) || empty($invoiceImg)) {
            $list = returnData(null, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($list);
        }
//        $isOpenInfo = array('1', '-1');
//        if (!in_array($isOpen, $isOpenInfo)) {
//            $list = returnData(null, -1, 'error', '数据错误', '数据错误');
//            $this->ajaxReturn($list);
//        }
        $m = D('Merchantapi/InvoiceReceipt');
        $list = $m->updatePermitInvoiceInfo($shopInfo, $receiptId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 删除发票
     */
    public function delPermitInvoiceInfo()
    {
        $shopInfo = $this->MemberVeri()['shopId'];
        $receiptId = I('receiptId');
        if (empty($receiptId)) {
            $retdata = returnData(null, -1, 'error', '删除失败', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = D('Merchantapi/InvoiceReceipt');
        $data = $m->delPermitInvoiceInfo($shopInfo, $receiptId);
        if ($data == 1) {
            $retdata = returnData(null);
        } else {
            $retdata = returnData(null, -1, 'error', '删除数据失败', '删除数据失败');
        }

        $this->ajaxReturn($retdata);
    }
}
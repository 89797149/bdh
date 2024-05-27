<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 发票功能类
 */
class InvoiceReceiptModel extends BaseModel
{

    /**
     * @param $shopInfo
     * @return array
     * 获取发票列表
     */
    public function getPermitInvoiceList($shopInfo)
    {
        $headertype = I('headertype');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $number = I('number');
        $isOpen = I('isOpen');
        $userName = I('userName');
        $sql = "SELECT ir.*,u.userName FROM wst_invoice_receipt ir LEFT JOIN wst_users u ON u.userId = ir.userId WHERE ir.shopId = $shopInfo";
        if (!empty($headertype)) {
            $sql .= " and ir.headertype = $headertype";
        }
        if (!empty($number)) {
            $sql .= " and ir.number = $number";
        }
        if (!empty($isOpen)) {
            $sql .= " and ir.isOpen = $isOpen";
        }
        if (!empty($userName)) {
            $sql .= " and u.userName like '%" . $userName . "%'";
        }
        $data = $this->pageQuery($sql, $page, $pageSize);
        return empty($data) ? array() : $data;
    }

    /**
     * @param $shopId
     * @param $receiptId
     * @return mixed
     * 删除发票
     */
    public function delPermitInvoiceInfo($shopId, $receiptId)
    {
        $where['receiptId'] = $receiptId;
        $where['shopId'] = $shopId;
        $data = M('invoice_receipt')->where($where)->find();
        if ($data) {
            if ($data['isOpen'] == -1) {
                return -1;
            }
        }
        return M('invoice_receipt')->where($where)->delete();
    }

    /**
     * @param $shopId
     * @param $receiptId
     * @return mixed
     * 变更发票状态
     */
    public function updatePermitInvoiceInfo($shopId, $receiptId)
    {
        $save['isOpen'] = 1;
        if (!empty(I('invoiceImg'))) {
            $save['invoiceImg'] = I('invoiceImg');
        }
        $where['receiptId'] = $receiptId;
        $where['shopId'] = $shopId;
        return M('invoice_receipt')->where($where)->save($save);
    }
}
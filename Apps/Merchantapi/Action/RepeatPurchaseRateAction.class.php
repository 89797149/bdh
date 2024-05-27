<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 复购率报表
 */
class RepeatPurchaseRateAction extends BaseAction{

    /**
     * 复购率报表统计
     */
    public function repeatPurchaseRate(){
        $shopId = $this->MemberVeri()['shopId'];
        $rs = D('Merchantapi/RepeatPurchaseRate')->repeatPurchaseRate($shopId);
        $this->ajaxReturn($rs);
    }
};
?>
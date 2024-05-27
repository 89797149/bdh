<?php
 namespace Adminapi\Action;
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
        $this->isLogin();
        $rs = D('Adminapi/RepeatPurchaseRate')->repeatPurchaseRate();
        $this->ajaxReturn($rs);
    }
};
?>
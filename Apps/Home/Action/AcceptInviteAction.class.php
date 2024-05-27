<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器
 */
class AcceptInviteAction extends BaseAction {
	/**
     * 分销页面
	 */
    public function index(){
        $this->display("default/acceptInvite/index");
    }

    /**
     * 下载页
     */
    public function download(){
        $this->display("default/acceptInvite/download");
    }
}
<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 数据导入控制器
 */
class ImportsAction extends BaseAction{
	/**
	 * 数据导入首页
	 */
    public function index(){
    	$shopInfo = $this->MemberVeri();
    	$this->assign("umark","Imports");
    	$this->display('default/shops/import');
	}
};
?>
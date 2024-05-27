<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺分类控制器
 */
class ShopsCatsAction extends BaseAction{
	/**
	 * 列表查询
	 */
    public function queryByList(){
		$m = D('Adminapi/ShopsCats');
		$list = $m->queryByList(I('shopId',0),I('id',0));
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
};
?>
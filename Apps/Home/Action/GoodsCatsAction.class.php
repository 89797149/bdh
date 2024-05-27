<?php
 namespace Home\Action;;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品分类控制器
 */
class GoodsCatsAction extends BaseAction{
	/**
	 * 列表查询
	 */
    public function queryByList(){
		$m = D('Adminapi/GoodsCats');
		$list = $m->queryByList((int)I('id'));
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
};
?>
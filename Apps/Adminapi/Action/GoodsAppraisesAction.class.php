<?php
 namespace Adminapi\Action;
 use Adminapi\Model\GoodsAppraisesModel;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品评价控制器
 */
class GoodsAppraisesAction extends BaseAction{
	
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('sppl_03');
		$m = D('Adminapi/Goods_appraises');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
		$this->checkPrivelege('sppl_04');

        $id = I('id',0,'intval');
        if ($id <= 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }

        $m = new GoodsAppraisesModel();
        $detail = $m->get();

        $this->ajaxReturn($detail);
	}
	/**
	 * 修改商品评价
	 */
	public function edit(){
		$this->isLogin();
		$this->checkPrivelege('sppl_04');
		$m = D('Adminapi/Goods_appraises');
    	$rs = $m->edit();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('sppl_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = new GoodsAppraisesModel();
    	$list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}
}
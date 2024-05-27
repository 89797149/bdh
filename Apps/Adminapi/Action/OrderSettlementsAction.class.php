<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 订单结算控制器
 */
class OrderSettlementsAction extends BaseAction{
	/**
	 * 订单结算列表
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('js_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/OrderSettlements');
    	$list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}
	
	/**
	 * 结算详情
	 */
	public function settlementDetail(){
		$this->isLogin();
		$this->checkPrivelege('js_04');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
		$m = D('Adminapi/OrderSettlements');
        $detail = $m->get();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
	}
	
	/**
	 * 结算
	 */
	public function settlement(){
		$this->isLogin();
		$this->checkPrivelege('js_04');
		$m = D('Adminapi/OrderSettlements');
		$rs = $m->settlement();
		$this->ajaxReturn($rs);
	}
	
    /**
	 * 结算详情
	 */
	public function detail(){
		$this->isLogin();
		$this->checkPrivelege('js_04');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
		$m = D('Adminapi/OrderSettlements');
        $detail = $m->getDetail();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
	}
	
};
?>
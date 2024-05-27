<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 收银订单控制器
 */
class PosOrdersAction extends BaseAction{
	/**
	 * Pos订单分页查询
	 */
	public function getPosOrderList(){
		$this->isLogin();
		$this->checkPrivelege('syddlb_00');
		$page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/PosOrders');
    	$list = $m->getPosOrderList($page,$pageSize);
        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}

	/**
	 * 查看Pos订单详情
	 */
	public function getPosOrderDetail(){
		$this->isLogin();
		//$this->checkPrivelege('ddlb_00');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '操作成功';
            $this->ajaxReturn($rs);
        }
		$m = D('Adminapi/PosOrders');
        $detail = $m->getPosOrderDetail();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
	}

    /**
     * Pos结算订单分页查询
     */
    public function getPosOrderSettlementList(){
        $this->isLogin();
        $this->checkPrivelege('syddjs_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $m = D('Adminapi/PosOrders');
        $list = $m->getPosOrderSettlementList($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
    }

    /**
     * 跳去结算页面
     */
    public function toSettlement(){
        $this->isLogin();
        $this->checkPrivelege('syddjs_00');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/PosOrders');
        $detail = $m->toSettlement();

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
        $this->checkPrivelege('syddjs_01');
        $m = D('Adminapi/PosOrders');
        $rs = $m->settlement();
        $this->ajaxReturn($rs);
    }

    /**
     * 订单结算详情
     */
    public function settlementInfo(){
        $this->isLogin();
        //$this->checkPrivelege('syddjs_00');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/PosOrders');
        $detail = $m->settlementInfo();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }
}
?>
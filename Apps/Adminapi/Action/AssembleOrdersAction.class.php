<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 拼团订单
 */
class AssembleOrdersAction extends BaseAction{
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('ptddgl_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/AssembleOrders');
    	$list = $m->queryByPage($page,$pageSize);

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}

	/**
	 * 查看订单详情
	 */
	public function detail(){
		$this->isLogin();
		$this->checkPrivelege('ptddlb_01');
//        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
		$m = D('Adminapi/AssembleOrders');
        $detail = $m->getDetail();

//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $detail;
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($detail));
	}

}
?>
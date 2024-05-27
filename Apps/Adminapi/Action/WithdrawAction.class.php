<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 提现控制器
 */
class WithdrawAction extends BaseAction{
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
	    $m = D('Adminapi/Withdraw');
        $detail = $m->getWithdraw();
        $this->returnResponse(0,'操作成功',$detail);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Withdraw');
    	$rs = array();
    	if(I('id',0)>0){
//    		$this->checkPrivelege('txlb_02');
    		$rs = $m->edit();
    	}else{
//    		$this->checkPrivelege('txlb_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
//		$this->checkPrivelege('txlb_03');
		$m = D('Adminapi/Withdraw');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
        $this->isLogin();
//		$this->checkPrivelege('txlb_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/Withdraw');
    	$list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
	}
};
?>

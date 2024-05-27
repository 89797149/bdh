<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 导航控制器
 */
class NavsAction extends BaseAction{
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
	    $m = D('Adminapi/Navs');
        $detail = $m->get();
        $this->returnResponse(0,'操作成功',$detail);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Navs');
    	$rs = array();
    	if(I('id',0)>0){
    		$rs = $m->edit();
    	}else{
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('dhgl_03');
		$m = D('Adminapi/Navs');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/Navs');
    	$list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
	}
	/**
	 * 是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('dhgl_02');
	 	$m = D('Adminapi/Navs');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }
    /**
	 * 是否新窗口打开
	 */
	 public function editiIsOpen(){
	 	$this->isLogin();
	 	$this->checkPrivelege('dhgl_02');
	 	$m = D('Adminapi/Navs');
		$rs = $m->editiIsOpen();
		$this->ajaxReturn($rs);
	 }
};
?>
<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 社区控制器
 */
class CommunitysAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function detail(){
		$this->isLogin();
	    $m = D('Adminapi/Communitys');
        $this->checkPrivelege('sqlb_02');
        $detail = $m->get();

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$detail);
        $rs = returnData($detail);
        $this->ajaxReturn($rs);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Communitys');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('sqlb_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('sqlb_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('sqlb_03');
		$m = D('Adminapi/Communitys');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('sqlb_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/Communitys');
    	$list = $m->queryByPage($page,$pageSize);

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/Communitys');
		$list = $m->queryByList();
//		$rs = array();
//		$rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//		$rs['data'] = $list;
        $rs = returnData($list);
		$this->ajaxReturn($rs);
	}
    /**
	 * 显示商品是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('sqlb_02');
	 	$m = D('Adminapi/Communitys');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }
};
?>
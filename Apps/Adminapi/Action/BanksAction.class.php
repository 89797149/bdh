<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 银行控制器
 */
class BanksAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function detail(){
		$this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
//            $this->returnResponse(-1,'参数不全');
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
	    $m = D('Adminapi/Banks');
        $detail = $m->get();
        //$this->returnResponse(0,'操作成功',$detail);
        $this->ajaxReturn(returnData($detail));
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Banks');
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
		$this->checkPrivelege('yhgl_03');
		$m = D('Adminapi/Banks');
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
		$m = D('Adminapi/Banks');
    	$list = $m->queryByPage($page,$pageSize);
//        $this->returnResponse(0,'操作成功',$list);
        $this->ajaxReturn(returnData($list));
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/Banks');
		$list = $m->queryByList();
//        $this->returnResponse(0,'操作成功',$list);
        $this->ajaxReturn(returnData($list));
	}
};
?>
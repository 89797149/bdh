<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 文章分类控制器
 */
class ArticleCatsAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$this->isLogin();
	    $m = D('Adminapi/ArticleCats');
//    	$object = array();
//    	if(I('id',0)>0){
//    		$this->checkPrivelege('wzfl_02');
//    		$object = $m->get();
//    	}else{
//    		$this->checkPrivelege('wzfl_01');
//    		$object = $m->getModel();
//    		$object['parentId'] = I('parentId',0);
//    	}
//    	$this->assign('object',$object);
//		$this->view->display('/articlecats/edit');
        $this->checkPrivelege('wzfl_02');
        $result = $m->get();
        $this->ajaxReturn(returnData($result));
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/ArticleCats');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('wzfl_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('wzfl_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 修改名称
	 */
	public function editName(){
		$this->isLogin();
		$m = D('Adminapi/ArticleCats');
    	$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
    	if(I('id',0)>0){
    		$this->checkPrivelege('wzfl_02');
    		$rs = $m->editName();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('wzfl_03');
		$m = D('Adminapi/ArticleCats');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('wzfl_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $pid = I('parentId',0);
		$m = D('Adminapi/ArticleCats');
    	$list = $m->queryByPage($pid,$page,$pageSize);

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/ArticleCats');
		$list = $m->queryByList(I('id',0));
//		$rs = array();
//		$rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//		$rs['data'] = $list;
//		$this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
	}
    /**
	 * 显示分类是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('wzfl_02');
	 	$m = D('Adminapi/ArticleCats');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }

    /**
     * 获得文章分类列表
     */
    public function getArticleCatList(){
        $this->isLogin();
        $m = D('Adminapi/ArticleCats');
        $list = $m->getArticleCatList(I('parentId',0));

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
        $this->ajaxReturn(returnData($list));
    }

};
?>
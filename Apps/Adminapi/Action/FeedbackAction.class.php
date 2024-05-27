<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 意见反馈
 */
class FeedbackAction extends BaseAction{
    /**
     * 反馈列表
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('yjfklb_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $m = D('Adminapi/Feedback');
        $list = $m->queryByPage($page,$pageSize,$startDate,$endDate);
        $this->returnResponse(0,'操作成功',$list);
    }

	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$this->isLogin();
	    $m = D('Adminapi/Feedback');
	    $id = I('id',0);
    	if($id > 0){
    		$this->checkPrivelege('yjfklb_01');
    		$object = $m->getInfo($id);
    	}
        $this->returnResponse(0,'操作成功',$object);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
        $response = ['code'=>0,'msg'=>'操作成功'];
		$this->isLogin();
		$m = D('Adminapi/Feedback');
		$param = I();
		$paramTo['status'] = $param['status'];
        $paramTo['id'] = $param['id'];
    	if($param['id'] > 0){
    		$this->checkPrivelege('yjfklb_01');
    		$rs = $m->edit($paramTo);
    	}
    	if($rs['status'] != 1){
    	    $response['code'] = -1;
    	    $response['msg'] = '操作失败';
        }
        $this->returnResponse($response['code'],$response['msg']);
	}
	/**
	 * 删除操作
	 */
	public function del(){
	    $response = ['code'=>0,'msg'=>'操作成功'];
		$this->isLogin();
		$this->checkPrivelege('cpfl_03');
		$m = D('Adminapi/Feedback');
		$param = I();
        isset($param['id'])?$paramTo['id']=$param['id']:false;
    	$rs = $m->del($paramTo);
    	if($rs['status'] != 1){
    	    $response['code'] = -1;
    	    $response['msg'] = '操作失败';
        }
        $this->returnResponse($response['code'],$response['msg']);
	}
}
?>
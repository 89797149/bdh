<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员等级控制器
 */
class UserRanksAction extends BaseAction{
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
    	$rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $this->checkPrivelege('hydj_02');
        $m = D('Adminapi/UserRanks');
        $detail = $m->get();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/UserRanks');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('hydj_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('hydj_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('hydj_03');
		$m = D('Adminapi/UserRanks');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('hydj_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/UserRanks');
    	$list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Adminapi/UserRanks');
		$list = $m->queryByList();
		$rs = array();
		$rs['code'] = 0;
        $rs['msg'] = '操作成功';
		$rs['data'] = $list;
		$this->ajaxReturn($rs);
	}
};
?>
<?php
 namespace Adminapi\Action;
 use Adminapi\Model\StaffsModel;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 职员控制器
 */
class StaffsAction extends BaseAction{
	/**
	 * 查看详情
	 */
	public function detail(){
		$this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
	    $m = D('Adminapi/Staffs');
        $detail = $m->get();
        $this->returnResponse(0,'操作成功',$detail);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Adminapi/Staffs');
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
		$m = D('Adminapi/Staffs');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
   /**
	 * 查看
    * 弃用
	 */
	/*public function toView(){
		$this->isLogin();
		$this->checkPrivelege('zylb_00');
		$m = D('Adminapi/Staffs');
		if(I('id')>0){
			$object = $m->get();
			$this->assign('object',$object);
		}
		$this->view->display('/staffs/view');
	}*/
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = new StaffsModel();
    	$list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
	}
	/**
	 * 查询用户账号
	 */
	public function checkLoginKey(){
		$this->isLogin();
		$m = D('Adminapi/Staffs');
		$rs = $m->checkLoginKey();
		$this->ajaxReturn($rs);
	}
    /**
	 * 显示职员账号是否启用/停用
	 */
	 public function editStatus(){
	 	$this->isLogin();
	 	$m = D('Adminapi/Staffs');
		$rs = $m->editStatus();
		$this->ajaxReturn($rs);
	 }
	
	/**
	 * 修改职员密码
	 */
	public function editPass(){
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $staffId = $this->MemberVeri()['staffId'];
        if (!$staffId) {
            $rs['msg'] = "参数不全";
            $this->ajaxReturn($rs);
        }
		$m = D('Adminapi/Staffs');
   		$rs = $m->editPass($staffId);
    	$this->ajaxReturn($rs);
	}

};
?>
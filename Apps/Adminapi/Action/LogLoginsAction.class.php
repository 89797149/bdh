<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 登录日志控制器
 */
class LogLoginsAction extends BaseAction{
   /**
	 * 查看
	 */
	public function detail(){
		$this->isLogin();
		$id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
		$m = D('Adminapi/LogLogins');
        $detail = $m->get();
        $this->returnResponse(0,'操作成功',$detail);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/LogLogins');
    	$list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
	}
};
?>
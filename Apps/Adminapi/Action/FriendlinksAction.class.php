<?php
namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 友情连接控制器
 */
class FriendlinksAction extends BaseAction {
	/**
	 * 分页列表
	 */
    public function index(){
    	$this->isLogin();
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
    	$m = D('Adminapi/Friendlinks');
    	$list = $m->queryPage($page,$pageSize);

        $this->returnResponse(0,'操作成功',$list);
    }

    /**
     * 查看详情
     */
    public function detail(){
    	$this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
    	$m = D('Adminapi/Friendlinks');
        $detail = $m->get();

        $this->returnResponse(0,'操作成功',$detail);
    }
    
    /**
     * 新增/修改
     */
    public function edit(){
    	$this->isLogin();
    	$m = D('Adminapi/Friendlinks');
    	$rs = array();
    	if(I('id')>0){
    		$rs = $m->edit();
    	}else{
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 删除
     */
    public function del(){
    	$this->isLogin();
    	$m = D('Adminapi/Friendlinks');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
    }
}
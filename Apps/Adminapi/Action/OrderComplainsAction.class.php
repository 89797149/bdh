<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 订单投诉控制器
 */
class OrderComplainsAction extends BaseAction{
	/**
	 * 订单投诉列表
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('ddts_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
		$m = D('Adminapi/OrderComplains');
    	$list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
	}
	
	/**
	 * 获取订单详情
	 */
    public function detail(){
		$this->isLogin();
		$this->checkPrivelege('ddts_00');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }

		$m = D('Adminapi/OrderComplains');
        $detail = $m->getDetail();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;

        $this->ajaxReturn($rs);
	}
	/**
	 * 跳去处理页面
	 */
    public function toHandle(){
		$this->isLogin();
		$this->checkPrivelege('ddts_04');
		$m = D('Adminapi/OrderComplains');
		if(I('id')>0){
			$object = $m->getDetail();
			$this->assign('object',$object);
		}
		$this->assign('referer',$_SERVER['HTTP_REFERER']);
		$this->display("/ordercomplains/handle");
	}
	
	/**
	 * 转交给应诉人回应
	 */
	public function deliverRespond(){
		$this->isLogin();
		$this->checkPrivelege('ddts_04');
		$rs = array('status'=>-1,'msg'=>'无效的投诉信息!');
		if((int)I('id')>0){
			$rs = D('Adminapi/OrderComplains')->deliverRespond();
		}
		$this->ajaxReturn($rs);
	}
	/**
	 * 仲裁投诉记录
	 */
	public function finalHandle(){
		$this->isLogin();
		$this->checkPrivelege('ddts_04');
		$rs = array('status'=>-1,'msg'=>'无效的投诉信息!');
		if((int)I('id')>0){
			$rs = D('Adminapi/OrderComplains')->finalHandle();
		}
		$this->ajaxReturn($rs);
	}
};
?>
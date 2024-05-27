<?php
 namespace Adminapi\Action;
 use Adminapi\Model\MemberRptsModel;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员报表控制器
 */
class MemberRptsAction extends BaseAction{
	/**
	 * 跳转到消费页面
	 */
	public function index(){
		$this->isLogin();
//		$this->checkPrivelege('dttj_00');
	
        $this->display("/memberRpts/member");
	}
	
	
	/*
	 * 获取会员消费记录
	 * @param string startDate
	 * @param string endDate
	 * @param string userName 会员名称
	 * @param string userPhone 会员手机号
	 * @param int shopId 店铺id
	 * @param int orderStatus 订单状态(0=>待受理,1=>已受理,2=>打包中,3=>配送中,4=>已到货,7=>外卖配送,8=>预售,20=>所有)
	 * */
	public function getOrders(){
        $this->isLogin();
        $parameter = I();
        $m = new MemberRptsModel();
		$rs = $m->getOrders($parameter);
        $this->ajaxReturn($rs);

	}
	
	//获取订单详情
	public function getOrderDetail(){
		$this->isLogin();
		$orderId = I('orderId');
		if(empty($orderId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择查看订单'));
		}
        $m = new MemberRptsModel();
		$rs = $m->getOrderDetail($orderId);
		$this->ajaxReturn($rs);
	}
}
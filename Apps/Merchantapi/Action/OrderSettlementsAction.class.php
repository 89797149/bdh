<?php
 namespace Merchantapi\Action;;
 /**
  * 太旧了,懒得改,废弃
  * */
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 订单结算控制器
 */
class OrderSettlementsAction extends BaseAction{
	
	/**
	 * 跳去订单结算页面
	 */
    public function toSettlementIndex(){
    	$shopInfo = $this->MemberVeri();  	
		$this->display("default/shops/orders/settlements");
	}
	/**
	 * 订单结算列表
	 */
	public function querySettlementsList(){
		$shopInfo = $this->MemberVeri();
		$rs = array('status'=>1);
		$data = D('Home/OrderSettlements')->querySettlementsByPage();
        $this->returnResponse(1,'获取成功',$data);
	}
	/**
	 * 未结算订单
	 */
    public function queryUnSettlementOrdersByPage(){
		$shopInfo = $this->MemberVeri();
		$rs = array('status'=>1);
		$rs['data'] = D('Home/OrderSettlements')->queryUnSettlementOrdersByPage($shopInfo);
		$this->ajaxReturn($rs);
	}
	/**
	 * 获取已结算的订单列表
	 */
	public function querySettlementsOrdersByPage(){
		$shopInfo = $this->MemberVeri();
		$rs = array('status'=>1);
		$rs['data'] = D('Home/OrderSettlements')->querySettlementsOrdersByPage($shopInfo);
		$this->ajaxReturn($rs);
	}
	
	/**
	 * 订单结算申请
	 */
	public function settlement(){
		$shopInfo = $this->MemberVeri();
		$rs = D('Home/OrderSettlements')->settlement($shopInfo);
		if($rs['status'] == 1){
            $this->returnResponse(1,'操作成功',[]);
        }else{
            $this->returnResponse(-1,'操作失败',[]);
        }

		$this->ajaxReturn($rs);
	}
	
	/**
	 * 跳转到自提订单
	 */
	public function coupons(){
		$shopInfo = $this->MemberVeri();  	
		$this->display("default/shops/orders/coupons");
	}
	
	/**
	 * 根据取货码查询订单
	 */
	public function queryCouponsOrders(){
        $shopInfo = $this->MemberVeri();
		$rs = D('Home/OrderSettlements')->queryCouponsOrders($shopInfo);
        $this->returnResponse(1,'获取成功',$rs);
	}
	
};
?>
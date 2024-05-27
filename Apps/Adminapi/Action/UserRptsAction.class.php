<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员报表控制器
 */
class UserRptsAction extends BaseAction{
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('dttj_00');
		$this->assign('startDate',date('Y-m-d H:i:s',strtotime("-31 day")));
		$this->assign('endDate',date('Y-m-d H:i:s'));
		//获取地区信息
		$m = D('Adminapi/Areas');
		$this->assign('areaList',$m->queryShowByList(0));
        $this->display("/reports/orders");
	}
	
	/**
	 * 按月/日统计订单
	 */
	public function queryByMonthAndDays(){
		$this->isLogin();
		$this->checkPrivelege('dttj_00');
		$rs = D('Adminapi/OrderRpts')->queryByMonthAndDays();
		$this->ajaxReturn($rs);
	}

    /**
     * 会员注册统计
     * 默认显示一周
     */
	public function userRegister(){
        $this->isLogin();
        $rs = D('Adminapi/UserRpts')->userRegister();
        $this->ajaxReturn($rs);
    }

    /**
     * 日活人数统计
     * 默认显示一周
     */
    public function dayActivity(){
        $this->isLogin();
        $rs = D('Adminapi/UserRpts')->dayActivity();
        $this->ajaxReturn($rs);
    }
};
?>
<?php
 namespace Adminapi\Action;

use Adminapi\Model\CouponsetModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券控制器
 */
class CouponsetAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function detail(){
		$this->isLogin();
        $this->checkPrivelege('yhqpzlb_02');
//        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = I('id',0,'intval');
        if (empty($id)) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
	    $m = D('Adminapi/Couponset');
        $detail = $m->getCouponset();

//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $detail;
        $rs = returnData($detail);
        $this->ajaxReturn($rs);
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = new CouponsetModel();
    	if(I('id',0)>0){
    		$this->checkPrivelege('yhqpzlb_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('yhqpzlb_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('yhqpzlb_03');
		$m = D('Adminapi/Couponset');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}

	/**
	 * 分页查询
	 */
	public function index(){
        $this->isLogin();
		$this->checkPrivelege('yhqpzlb_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $m = new CouponsetModel();
    	$list = $m->queryByPage($page,$pageSize);

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
	}

    /**
     * 获取会员专享优惠券列表[无分页]
     */
	public function getCouponsList(){
        $this->isLogin();
        $this->checkPrivelege('yhqpzlb_04');
        $m = D('Adminapi/Couponset');
        $list = $m->getCouponsList();

//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
	}
};
?>

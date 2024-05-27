<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券控制器
 */
class NewpeoplAction extends BaseAction{

   /**
	 * 拉新列表
     */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('lx_00');
		$m = D('Adminapi/Newpeople');
		
		$page = (int)I('page',1);
        $pageSize = I('pageSize',50,'intval');

		$object = $m->get($page,$pageSize);
		
		$this->ajaxReturn($object);
	}
	
	 //通过手机号搜索用户
	 public function getuser(){
		$this->isLogin();
		$m = D('Adminapi/Newpeople');
		
		$phone = I('phone');
		if(empty($phone)){
			$this->ajaxReturn(array('code'=>-1,'msg'=>'参数不能为空'));
		}
		
		$object = $m->getuser($phone);
		
		$this->ajaxReturn($object);
	 }
	 
	 //获取优惠券数量
	 public function getCoupons(){
		 $this->isLogin();
		$m = D('Adminapi/Newpeople');
		
		$userId = I('userId');
		if(empty($userId)){
			$this->ajaxReturn(array('code'=>-1,'msg'=>'参数不能为空'));
		}
		
		$object = $m->getCoupons($userId);
		
		$this->ajaxReturn($object);
	 }
	 
	 
	 //结算 直接清算
	 public function Settlement(){
		  $this->isLogin();
		$this->checkPrivelege('lx_01');
		$m = D('Adminapi/Newpeople');
		
		$userId = I('userId');
		if(empty($userId)){
			$this->ajaxReturn(array('code'=>-1,'msg'=>'参数不能为空'));
		}
		
		$object = $m->Settlement($userId);
		
		$this->ajaxReturn($object);
		 
		 
	 }
	

	
};
?>
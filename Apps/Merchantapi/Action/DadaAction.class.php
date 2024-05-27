<?php
 namespace Merchantapi\Action;;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 达达订单控制器
 */
class DadaAction extends BaseAction{

	//达达订单回调地址
	public function dadaOrderCall(){
		
		$m = D("Home/Dadano");
		$calldata = file_get_contents("php://input");
		if(empty($calldata)){
			$apiRet['apiCode']=-1;
			$apiRet['apiInfo']='字段有误';
			$apiRet['apiState']='error';
			$this->ajaxReturn($apiRet);
			
		}
		
		$mod = $m->dadaOrderCall($calldata);
		$this->ajaxReturn($mod);
      
    }
	
};
?>
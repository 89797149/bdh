<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 购物车控制器
 */
class CartAction extends BaseAction {
	/**
	 * 跳到购物车列表
	 */
    public function toCart(){
   		$m = D('Home/Cart');
		$cartInfo = $m->getCartInfo();
   		$pnow = (int)I("pnow",0);
   		$this->assign('cartInfo',$cartInfo);
   		$this->display('default/cart_pay_list');

    }
    
    /**
     * 添加商品到购物车(ajax)
     */
	public function addToCartAjax(){
   		$m = D('Home/Cart');
   		$rs = $m->addToCart();
   		$this->ajaxReturn($rs);
    }
    /**
     * 修改购物车商品
     * 
     */
    public function changeCartGoods(){
    	$m = D('Home/Cart');
   		$res = $m->addToCart();
   		echo "{status:1}";
    }
    
	/**
	 * 获取购物车信息
	 * 
	 */
	public function getCartInfo() {
		$m = D('Home/Cart');
		$cartInfo = $m->getCartInfo();
		$axm = (int)I("axm",0);
		if($axm ==1){
			echo json_encode($cartInfo);
		}else{
			$this->assign('cartInfo',$cartInfo);
			$this->display('default/cart_pay_list');
		}
		
	}
	
	/**
	 * 获取购物车商品数量
	 */
	public function getCartGoodCnt(){
		echo json_encode(array("goodscnt"=>WSTCartNum()));
	}
    
	/**
	 * 检测购物车中商品库存
	 * 
	 */
	public function checkCartGoodsStock(){
		$m = D('Home/Cart');
		$res = $m->checkCatGoodsStock();
		echo json_encode($res);

	}
	
	
	
	/**
	 * 删除购物车中的商品
	 * 
	 */
	public function delCartGoods(){	
		$m = D('Home/Cart');	
		$rs = $m->delCartGoods();
		$this->ajaxReturn($rs);
		
	}
	
	/**
	 * 修改购物车中的商品数量
	 * 
	 */
	public function changeCartGoodsNum(){
			
		$m = D('Home/Cart');
		$rs = $m->changeCartGoodsnum();
		$this->ajaxReturn($rs);
		
	}
	
	/**
	 *去购物车结算
	 * 
	 */
	public function toCatpaylist(){	
		$m = D('Home/Cart');
		$cartInfo = $goodsmodel->getCartInfo();
		$this->assign("cartInfo",$cartInfo);
		
		$this->display('default/cat_pay_list');
	}
	
}
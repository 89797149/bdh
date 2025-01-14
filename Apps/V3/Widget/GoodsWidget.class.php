<?php
namespace Mendianapi\Widget;
use Think\Controller;
/**
 * ============================================================================
 * NiaoCMS开源商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 
 */
class GoodsWidget extends Controller {
	/**
	 * 查看商品历史记录
	 */
	public function getViewGoods(){
		$m = D('Home/Goods');
		$goodslist = $m->getViewGoods();
		$this->assign("goodslist",$goodslist);
		$this->display("default/widget/view_history");
	}
	
	/**
	 * 热销排名
	 */
	public function getHotGoods($shopId){
		$m = D('Home/Goods');
		$hotgoods = $m->getHotGoods($shopId);
		$this->assign("hotgoods",$hotgoods);
		$this->display("default/widget/hot_goods");
	}
	
}
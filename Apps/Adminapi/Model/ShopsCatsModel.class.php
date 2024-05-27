<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺分类服务类
 */
class ShopsCatsModel extends BaseModel {
	 /**
	  * 获取列表
	  */
	  public function queryByList($shopId,$parentId){
		 return $this->where('shopId='.(int)$shopId.' and catFlag=1 and parentId='.(int)$parentId)->select();
	  }
	 
};
?>
<?php
 namespace Merchantapi\Action;;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品评价控制器
 */
class GoodsAppraisesAction extends BaseAction{
//	/**
//	 * 分页查询
//	 */
//	public function index(){
//		$shopInfo = $this->MemberVeri();
//
//		//获取商家商品分类
//		$m = D('Home/ShopsCats');
//		$this->assign('shopCatsList',$m->queryByList($shopInfo['shopId'],0));
//		$m = D('Home/Goods_appraises');
//    	$page = $m->queryByPage($shopInfo['shopId']);
//    	$pager = new \Think\Page($page['total'],$page['pageSize']);
//    	$page['pager'] = $pager->show();
//    	$this->assign('Page',$page);
//    	$this->assign("shopCatId2",I('shopCatId2'));
//    	$this->assign("shopCatId1",I('shopCatId1'));
//    	$this->assign("goodsName",I('goodsName'));
//    	$this->assign("umark","GoodsAppraises");
//        $this->display("default/shops/goodsappraises/list");
//	}

    /**
     * 分页查询
     */
    public function getlist(){
        $shopInfo = $this->MemberVeri();
        //获取商家商品分类
//        $m = D('Home/ShopsCats');
//        $this->assign('shopCatsList',$m->queryByList($shopInfo['shopId'],0));
        $m = D('Home/Goods_appraises');
        $page = $m->queryByPage($shopInfo['shopId']);
//        $pager = new \Think\Page($page['total'],$page['pageSize']);
//        $page['pager'] = $pager->show();
        $this->returnResponse(1,'获取成功',$page);
    }

     /**
      * 回复商品评价
      */
    public function updateAppraises(){
        $shopInfo = $this->MemberVeri();
        $id = (int)I("id");
        if(empty($id)){
            $apiRet = returnData(null,-1,'error','字段有误');
            $this->ajaxReturn($apiRet);
        }
        $m = D('Home/Goods_appraises');
        $page = $m->updateAppraises($shopInfo['shopId']);
        $this->returnResponse(1,'添加成功',$page);
    }

     /**
      * 添加虚拟评论
      */
    public function addAppraises(){
        $shopInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $content = I('content');
        $goodsScore = (int)I('goodsScore');
        $serviceScore = (int)I('serviceScore');
        $timeScore = (int)I('timeScore');
        if(empty($goodsId) || empty($content) || empty($goodsScore)|| empty($serviceScore)|| empty($timeScore)){
            $apiRet = returnData(null,-1,'error','字段有误');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }

        //判断分数 是否超过5分
        if($goodsScore >5 || $serviceScore>5 || $timeScore>5){
            $apiRet = returnData(null,-1,'error','评分不能超过5分');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }

        //判断分数 是否超过5分
        if($goodsScore <=0 || $serviceScore <=0 || $timeScore <=0){
            $apiRet = returnData(null,-1,'error','评分不能为0或低于0分');
            if(I("apiAll") == 1){return $apiRet;}else{$this->ajaxReturn($apiRet);}//返回方式处理
        }
        $m = D('Home/Goods_appraises');
        $page = $m->addAppraises($shopInfo['shopId']);
        $this->returnResponse(1,'添加成功',$page);
    }
	
	/**
	 * 获取指定商品评价
	 */
	public function getAppraise(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/Goods_appraises');
    	$appraise = $m->getAppraise();
    	$this->assign('appraise',$appraise);
    	
        $this->display("default/shops/goodsappraises/appraise");
	}
	/******************************************************************
	 *                         会员操作
	 ******************************************************************/
	/**
	 * 订单评价
	 */
    public function toAppraise(){
    	$shopInfo = $this->MemberVeri();
    	$USER = session('WST_USER');
    	$morders = D('Home/Goods_appraises');
    	$obj["userId"] = $shopInfo['userId'];
    	$obj["orderId"] = (int)I("orderId");
		$rs = $morders->getOrderAppraises($obj);
		$this->assign("orderInfo",$rs);
		$this->display("default/users/orders/appraise");
	}
	/**
	 * 添加评价
	 */
    public function addGoodsAppraises(){
    	$shopInfo = $this->MemberVeri();
    	$morders = D('Home/Goods_appraises');
    	$obj["userId"] = $shopInfo['userId'];
    	$obj["orderId"] = (int)I("orderId");
    	$obj["goodsId"] = (int)I("goodsId");
    	$obj["goodsAttrId"] = (int)I("goodsAttrId");
		$rs = $morders->addGoodsAppraises($obj);
		$this->ajaxReturn($rs);
	}	
	/**
	 * 获取评价
	 */
    public function getAppraisesList(){
    	$shopInfo = $this->MemberVeri();
    	$morders = D('Home/Goods_appraises');
    	$obj["userId"] = $shopInfo['userId'];
    	$this->assign("umark","getAppraisesList");
		$appraiseList = $morders->getAppraisesList($obj);
		$this->assign("appraiseList",$appraiseList);
		$this->display("default/users/orders/list_appraise_manage");
	} 
	/**
	 * 获取前台评价列表
	 */
	public function getGoodsappraises(){
		$goods = D('Home/Goods_appraises');
		$goodsAppraises = $goods->getGoodsAppraises();
		$this->ajaxReturn($goodsAppraises);
	}

     /**
      * 删除商品评论
      * @param int id PS:评价id
      */
     public function delAppraise(){
         $shopInfo = $this->MemberVeri();
         $m = D('Home/Goods_appraises');
         $appraise = $m->delAppraise();
         $this->ajaxReturn($appraise);
     }
}
<?php
 namespace Merchantapi\Action;

 use Home\Model\OrderComplainsModel;

 ;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单投诉控制器
 */
class OrderComplainsAction extends BaseAction{

	/**
	 * 获取用户投诉列表
	 */
    public function queryUserComplainByPage(){
    	$shopInfo = $this->MemberVeri();
    	self::WSTAssigns();
		$Page = D('Home/OrderComplains')->queryUserComplainByPage($obj);
		$this->assign("umark","queryUserComplainByPage");
		$this->assign("Page",$Page);
		$this->display("default/users/orders/list_complain");
	}

	/**
	 * 订单投诉
	 */
	public function complain(){
		$shopInfo = $this->MemberVeri();
		$data = D('Home/OrderComplains')->getOrderInfo();
		$this->assign("data",$data);
		$this->display("default/users/orders/complain");
	}

	/**
	 * 保存订单投诉信息
	 */
	public function saveComplain(){
		$shopInfo = $this->MemberVeri();
		$rs = D('Home/OrderComplains')->saveComplain();
		$this->ajaxReturn($rs);
	}

	/**
	 * 用户查投诉详情
	 */
	public function getUserComplainDetail(){
		$shopInfo = $this->MemberVeri();
		$data = D('Home/OrderComplains')->getComplainDetail(0);
		$this->assign("data",$data);
		$this->assign("umark","queryUserComplainByPage");
		$this->display("default/users/orders/complain_detail");
	}

	/**
	 * 获取商家被投诉订单
	 */
    public function queryShopComplainByPage(){
    	$shopInfo = $this->MemberVeri();
    	self::WSTAssigns();
        $obj['shopId'] = (int)$shopInfo['shopId'];
		$Page = D('Home/OrderComplains')->queryShopComplainByPage($obj);
        $this->returnResponse(1,'获取成功',$Page);
	}

	/**
	 * 退货申请统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ebaoyo
	 * */
    public function getOrderComplainsListNum(){
        $shopId = $this->MemberVeri()['shopId'];
        $data = D('Home/OrderComplains')->getOrderComplainsListNum($shopId);
        $this->ajaxReturn(returnData($data));
	}

    /**
     * 获取商家退货列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ygfl17
     */
    public function getOrderComplainsList(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $requestParams['shopId'] = $shopInfo['shopId'];
        $requestParams['page'] = (int)I('page',1);
        $requestParams['pageSize'] = (int)I('pageSize',15);
        $data = D('Home/OrderComplains')->getOrderComplainsList($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
	 * 订单投诉
	 */
	public function respond(){
		$shopInfo = $this->MemberVeri();
		$data = D('Home/OrderComplains')->getComplainDetail(1);



		$this->assign("data",$data);
		$this->display("default/shops/orders/respond");
	}
	/**
	 * 订单投诉
	 */
	public function saveRespond(){
		$shopInfo = $this->MemberVeri();
		$rs = D('Home/OrderComplains')->saveRespond($shopInfo);
        if($rs['status'] == 1){
            $this->returnResponse(1,'操作成功',[]);
        }else{
            $this->returnResponse(-1,'操作失败',$rs['msg']);
        }
	}

    /**
     * 订单应诉
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/oxny05
     */
    public function respondingOrder(){
        $loginUserInfo = $this->MemberVeri();
        $requestParams = I();
        if(empty($requestParams['complainId']) || empty($requestParams['actionStatus'])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if(!in_array($requestParams['actionStatus'],[-1,1,2])){
            $this->ajaxReturn(returnData(false, -1, 'error', '操作状态值错误'));
        }
        if((float)$requestParams['returnAmount'] < 0){
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的退款金额'));
        }
        $data = D('Home/OrderComplains')->respondingOrder($loginUserInfo,$requestParams);
        $this->ajaxReturn($data);
    }

    /**
	 * 查投诉详情
	 */
	public function getShopComplainDetail(){
		$shopInfo = $this->MemberVeri();
		$data = D('Home/OrderComplains')->getComplainDetail(1,$shopInfo);
        $this->returnResponse(1,'获取成功',$data);
	}

	/**
	 * 获取投诉(退货/退款)详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/yaprgz
	 * */
    public function getOrderComplainDetail(){
        $shopId = $this->MemberVeri()['shopId'];
        $complainId = (int)I('complainId');
        if(empty($complainId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = D('Home/OrderComplains')->getOrderComplainDetail($shopId,$complainId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 已废弃
     * 获取商家被投诉订单
     */
    public function queryShopComplainNums(){
        $shopInfo = $this->MemberVeri();
        $obj['shopId'] = (int)$shopInfo['shopId'];
        $res = D('Home/OrderComplains')->queryShopComplainNum($obj);
        $this->returnResponse(1,'获取成功',$res);
    }

    /**
     * 退货/退款申请操作日志
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cf88i3
     * */
    public function getOrderComplainsLog(){
        $this->MemberVeri();
        $complainId = (int)I('complainId');
        if(empty($complainId)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = D('Home/OrderComplains')->getOrderComplainsLog($complainId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退款列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zx4gvt
     * */
    public function getReturnAmountList(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $requestParams['shopId'] = $shopInfo['shopId'];
        $requestParams['page'] = (int)I('page',1);
        $requestParams['pageSize'] = (int)I('pageSize',15);
        $data = D('Home/OrderComplains')->getReturnAmountList($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退款统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uir0vg
     * */
    public function getReturnAmountNum(){
        $shopId = $this->MemberVeri()['shopId'];
        $data = D('Home/OrderComplains')->getReturnAmountNum($shopId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退款操作
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wm9h20
     * */
    public function doReturnAmount(){
        $login_user_info = $this->MemberVeri();
        $request_params = I();
        $complainId = (int)I('complainId');
        if(empty($complainId) || !in_array($request_params['returnAmountStatus'],[-1,1])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $request_params['complainId'] = $complainId;
        $m = new OrderComplainsModel();
        $data = $m->doReturnAmount($login_user_info,$request_params);
//        $data = D('Home/OrderComplains')->doReturnAmount($login_user_info,$request_params);
        $this->ajaxReturn($data);
    }
}
?>
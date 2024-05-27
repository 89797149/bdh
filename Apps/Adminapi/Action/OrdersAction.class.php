<?php

namespace Adminapi\Action;

use Adminapi\Model\OrdersModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单控制器
 */
class OrdersAction extends BaseAction
{
    /**
     * 分页查询
     */
//	public function index(){
//		$this->isLogin();
//		$this->checkPrivelege('ddlb_00');
//		$page = I('page',1,'intval');
//        $pageSize = I('pageSize',15,'intval');
//		$m = D('Adminapi/Orders');
//    	$list = $m->queryByPage($page,$pageSize);
//
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
//	}
    /**
     * 退款分页查询
     */
    public function queryRefundByPage()
    {
        $this->isLogin();
        $this->checkPrivelege('tk_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/Orders');
        $list = $m->queryRefundByPage($page, $pageSize);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

//    /**
//     * 查看订单详情
//     */
//    public function detail()
//    {
//        $this->isLogin();
//        $this->checkPrivelege('ddlb_00');
//        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
//        $id = I('id', 0, 'intval');
//        if ($id <= 0) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
//        }
//        $m = D('Adminapi/Orders');
//        $detail = $m->getDetail();
//
//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $detail;
//
//        $this->ajaxReturn($rs);
//    }

    /**
     * 查看退款订单详情
     */
    public function refundOrderDetail()
    {
        $this->isLogin();
        $this->checkPrivelege('tk_00');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/Orders');
        $detail = $m->getDetail();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }

    /**
     * 退款详情
     */
    public function refundDetail()
    {
        $this->isLogin();
        $this->checkPrivelege('tk_04');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/Orders');
        $detail = $m->get();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }

    /**
     * 退款
     */
    public function refund()
    {
        $this->isLogin();
        $this->checkPrivelege('tk_04');
        $m = D('Adminapi/Orders');
        $rs = $m->refund();
        $this->ajaxReturn($rs);
    }

    /*
     * 企业退款到零钱
     * */
    public function wxPayTransfers()
    {
        $this->isLogin();
        $m = D('Adminapi/Orders');
        $rs = $m->wxPayTransfers();
        $this->ajaxReturn($rs);
    }

    /**
     * 获取所有未退款的退款订单数量
     */
    public function queryRefundNum()
    {
        $m = D('Adminapi/Orders');
        $res = $m->queryRefundNum();
        $this->ajaxReturn($res);
    }

    /**
     * 合并单列表
     */
//    public function orderMergeList(){
//        $this->isLogin();
////        $this->checkPrivelege('tk_00');
//        $page = I('page',1,'intval');
//        $pageSize = I('pageSize',15,'intval');
//        $orderToken = I('orderToken','','trim');
//        $m = D('Adminapi/Orders');
//        $list = $m->orderMergeList($orderToken,$page,$pageSize);
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 合并子订单列表
//     */
//    public function mergeChildOrderList(){
//        $this->isLogin();
//        $id = I('id',0,'intval');
//        $m = D('Adminapi/Orders');
//        $list = $m->mergeChildOrderList($id);
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
//        $this->ajaxReturn($rs);
//    }

    /**
     * 查询微信交易号包含的订单
     * @param string tradeNo 微信交易号
     */
    public function mergeChildOrderList()
    {
        $this->isLogin();
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $tradeNo = I('tradeNo');
        if (empty($tradeNo)) {
            $rs['msg'] = '请输入微信订单号';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/Orders');
        $list = $m->mergeChildOrderList($tradeNo);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 统计订单数量
     * https://www.yuque.com/youzhibu/ruah6u/nxinkl
     * */
    public function getOrderStatusNum()
    {
        $this->isLogin();
        $requestParams = I('');
        $m = new OrdersModel();
        $data = $m->getOrderStatusNum($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取订单列表
     * https://www.yuque.com/youzhibu/ruah6u/ty8ywn
     */
    public function index()
    {
        $this->isLogin();
        $requestParams = I('');
        $m = new OrdersModel();
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        $orders = $m->queryShopOrders($requestParams);
        $this->ajaxReturn(returnData($orders));
    }

    /**
     * 获取订单详情
     * https://www.yuque.com/youzhibu/ruah6u/nmhusf
     */
    public function detail()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $orderId = (int)I("orderId");
        if (empty($orderId)) {
            $this->ajaxReturn(returnData([], 'error', '请选择订单', '失败'));
        }
        $rs = $m->getOrderDetailsApi($orderId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取订单日志
     * https://www.yuque.com/youzhibu/ruah6u/qbgmge
     */
    public function getOrderLog()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $orderId = (int)I("orderId");
        if (empty($orderId)) {
            $this->ajaxReturn(returnData([], 'error', '请选择订单', '失败'));
        }
        $rs = $m->getOrderLog($orderId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 退货申请统计
     * https://www.yuque.com/youzhibu/ruah6u/sniksv
     * */
    public function getOrderComplainsListNum()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $data = $m->getOrderComplainsListNum();
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取商家退货列表
     * https://www.yuque.com/youzhibu/ruah6u/fygk6r
     */
    public function getOrderComplainsList()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $requestParams = I('');
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        $data = $m->getOrderComplainsList($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取投诉(退货)详情
     */
    public function getOrderComplainDetail()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $complainId = (int)I('complainId');
        if (empty($complainId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = $m->getOrderComplainDetail($shopId = 0, $complainId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退货/退款申请操作日志
     * https://www.yuque.com/youzhibu/ruah6u/mxhfnm
     */
    public function getOrderComplainsLog()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $complainId = (int)I('complainId');
        if (empty($complainId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = $m->getOrderComplainsLog($complainId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取退款列表
     * https://www.yuque.com/youzhibu/ruah6u/xdfd44
     * */
    public function getReturnAmountList()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $requestParams = I('');
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        $data = $m->getReturnAmountList($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退款统计
     * https://www.yuque.com/youzhibu/ruah6u/aen8cx
     */
    public function getReturnAmountNum()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $shopWords = I('shopWords', 0);
        $data = $m->getReturnAmountNum($shopWords);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 补差价订单列表
     * https://www.yuque.com/youzhibu/ruah6u/fzz42v
     */
    public function getDiffMoneyOrders()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $requestParams = I('');
        $requestParams['page'] = (int)I('page', 1);
        $requestParams['pageSize'] = (int)I('pageSize', 15);
        $data = $m->getDiffMoneyOrders($requestParams);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 补差价订单列表详情
     * @param string token
     * @param int orderId PS:订单号
     * https://www.yuque.com/youzhibu/ruah6u/dfgmgn
     */
    public function getDiffMoneyOrdersDetail()
    {
        $this->isLogin();
        $m = new OrdersModel();
        $orderId = (int)I("orderId");
        if (empty($orderId)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '请选择查看订单'));
        }
        $rs = $m->getDiffMoneyOrdersDetail($orderId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 订单退货应诉
     * https://www.yuque.com/youzhibu/ruah6u/keroym
     */
    public function respondingOrder()
    {
        $loginUserInfo = $this->isLogin();
        $requestParams = I();
        if (empty($requestParams['complainId']) || empty($requestParams['actionStatus'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!in_array($requestParams['actionStatus'], [-1, 1, 2])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '操作状态值错误'));
        }
        if ((float)$requestParams['returnAmount'] < 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请输入正确的退款金额'));
        }
        $m = new OrdersModel();
        $data = $m->respondingOrder($loginUserInfo, $requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 退款操作
     * https://www.yuque.com/youzhibu/ruah6u/ekbnhi
     */
    public function doReturnAmount()
    {
        $login_user_info = $this->isLogin();
        $request_params = I();
        $complainId = (int)I('complainId');
        if (empty($complainId) || !in_array($request_params['returnAmountStatus'], [-1, 1])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $request_params['complainId'] = $complainId;
        $m = new OrdersModel();
        $data = $m->doReturnAmount($login_user_info, $request_params);
        $this->ajaxReturn($data);
    }
}
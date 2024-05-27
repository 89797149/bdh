<?php
namespace V3\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 智能箱类
 */
class BoxAction extends BaseAction {

    /**
     * 申请箱子
     */
    public function applyBox(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $shopId = I('shopId', 0, 'intval');
        $addressId = I('addressId', 0, 'intval');
        if (empty($userId) || empty($shopId) || empty($addressId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $m = D('V3/Box');
        $result = $m->applyBox($userId,$shopId,$addressId);

        if ($result) $data = array('code'=>0,'msg'=>'申请箱子成功');
        else $data = array('code'=>-1,'msg'=>'没有可供申请的箱子');

        if(I("apiAll") == 1){return $data;}else{$this->ajaxReturn($data);}//返回方式处理
    }

    /**
     * 箱子订单列表
     */
    public function boxOrderList(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        if (empty($userId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $param = array(
            'userId'    =>  $userId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval')
        );

        $m = D('V3/Box');
        $list = $m->getBoxOrderList($param);

        if(I("apiAll") == 1){return array('code'=>0,'list'=>$list);}else{$this->ajaxReturn(array('code'=>0,'list'=>$list));}//返回方式处理
    }

    /**
     * 确认订单
     */
    public function confirmOrder(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $orderId = I('orderId', 0, 'intval');
        if (empty($userId) || empty($orderId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $m = D('V3/Box');
        $result = $m->confirmOrder($userId,$orderId);

        if(I("apiAll") == 1){return array('code'=>$result?0:-1);}else{$this->ajaxReturn(array('code'=>$result?0:-1));}//返回方式处理
    }

    /**
     * 删除订单
     */
    public function deleteOrder(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $orderId = I('orderId', 0, 'intval');
        if (empty($userId) || empty($orderId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $m = D('V3/Box');
        $result = $m->deleteOrder($userId,$orderId);

        if(I("apiAll") == 1){return array('code'=>$result?0:-1);}else{$this->ajaxReturn(array('code'=>$result?0:-1));}//返回方式处理
    }

}

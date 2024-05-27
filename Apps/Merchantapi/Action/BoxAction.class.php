<?php
namespace Merchantapi\Action;
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
     * 箱子列表
     */
    public function boxList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval'),
            'name'      =>  I('name', '', 'trim')
        );

        $m = D('Merchantapi/Box');
        $list = $m->getBoxList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 新增/编辑 箱子
     */
    public function editBox(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $id = I('id', 0, 'intval');

        $name = I('name','','trim');
        if (empty($name)) $this->ajaxReturn(array('code'=>2, 'msg'=>'名称不能为空'));

        $deposit = I('deposit', '','trim');
        if (empty($deposit)) $this->ajaxReturn(array('code'=>3, 'msg'=>'押金不能为空'));

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/Box');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editBox(array('boxId'=>$id,'shopId'=>$shopId),$data);
        } else {//新增
            $data['createTime'] = date('Y-m-d H:i:s');
            $data['state'] = 0;
            $result = $m->addBox($data);
        }

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 删除箱子
     */
    public function deleteBox(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Box');
        $result = $m->deleteBox(array('boxId'=>$id,'shopId'=>$shopId),array('state'=>-1));

        $this->ajaxReturn($result);
    }

    /**
     * 箱子订单列表
     */
    public function boxOrderList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval')
        );

        $m = D('Merchantapi/Box');
        $list = $m->getBoxOrderList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 箱子订单 - 受理
     */
    public function acceptanceOrder(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $orderId = I('orderId', 0, 'intval');
        if (empty($shopId) || empty($orderId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Box');
        $result = $m->acceptanceOrder(array('shopId'=>$shopId,'orderId'=>$orderId));

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 箱子订单 - 主动完成订单
     */
    public function completeOrder(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $orderId = I('orderId', 0, 'intval');
        if (empty($shopId) || empty($orderId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Box');
        $result = $m->completeOrder(array('shopId'=>$shopId,'orderId'=>$orderId));

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 出借列表
     */
    public function userBoxList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval')
        );

        $m = D('Merchantapi/Box');
        $list = $m->getUserBoxList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 删除出借记录
     */
    public function deleteUserBox(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Box');
        $result = $m->editUserBox(array('id'=>$id,'shopId'=>$shopId),array('state'=>-1));

        $this->ajaxReturn(array('code'=>$result));
    }
    
}
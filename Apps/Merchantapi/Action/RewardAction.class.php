<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 抽奖功能类
 */
class RewardAction extends BaseAction {

    /**
     * 奖品列表
     */
    public function rewardList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval'),
            'name'      =>  I('name', '', 'trim')
        );

        $m = D('Merchantapi/Reward');
        $list = $m->getRewardList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 新增/编辑 奖品
     */
    public function editReward(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $id = I('id', 0, 'intval');

        $name = I('name','','trim');
        if (empty($name)) $this->ajaxReturn(array('code'=>2, 'msg'=>'名称不能为空'));

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/Reward');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editReward(array('rewardId'=>$id,'shopId'=>$shopId),$data);
        } else {//新增
            $data['state'] = 0;
            $result = $m->addReward($data);
        }

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 删除奖品
     */
    public function deleteReward(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Reward');
        $result = $m->deleteReward(array('rewardId'=>$id,'shopId'=>$shopId),array('state'=>-1));

        if ($result == 2) $this->ajaxReturn(array('code'=>-1,'msg'=>'当前奖品，有些中奖用户还未领取，因此不可删除'));
        $this->ajaxReturn(array('code'=>$result?0:-1));
    }

    /**
     * 中奖人员列表
     */
    public function rewardUserList(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $userName = I('userName','','trim');
        $userPhone = I('userPhone','','trim');
        $rank = I('rank','','trim');

        $param = array(
            'shopId'    =>  $shopId,
            'userName'  =>  $userName,
            'userPhone' =>  $userPhone,
            'rank'      =>  $rank,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 25, 'intval')
        );

        $m = D('Merchantapi/Reward');
        $list = $m->getRewardUserList($param);

        $this->ajaxReturn(array('code'=>0,'list'=>$list));
    }

    /**
     * 中奖人员列表 - 领取奖品
     */
    public function getReward(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Reward');
        $result = $m->getReward(array('shopId'=>$shopId,'id'=>$id));

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }

    /**
     * 删除中奖人员(弃用)
     */
    public function deleteRewardUser(){
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        if (empty($shopId) || empty($id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Reward');
        $result = $m->deleteRewardUser(array('shopId'=>$shopId,'id'=>$id));

        $this->ajaxReturn(array('code'=>$result ? 0 : -1));
    }
    
}
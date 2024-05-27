<?php
namespace v3\Action;
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
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $shopId = I('shopId', 1, 'intval');
        if (empty($userId) || empty($shopId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $m = D('v3/Reward');
        $list = $m->getRewardList($shopId);

        if(I("apiAll") == 1){return array('code'=>0,'list'=>$list);}else{$this->ajaxReturn(array('code'=>0,'list'=>$list));}//返回方式处理
    }

    /**
     * 抽奖
     */
    public function doReward(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $shopId = I('shopId', 1, 'intval');
        if (empty($userId) || empty($shopId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $orderNum = 10;//在门店最少成功购买单数

        $m = D('v3/Reward');
        $result = $m->doReward($userId,$shopId,$orderNum);

        if(I("apiAll") == 1){return $result;}else{$this->ajaxReturn($result);}//返回方式处理
    }
	
	/**
     * 用户抽奖记录列表
     */
    public function userRewardList(){
        $userId = $this->MemberVeri()['userId'];
//        $userId = I('userId', 0, 'intval');
        $shopId = I('shopId', 1, 'intval');
        if (empty($userId) || empty($shopId)) if(I("apiAll") == 1){return array('code'=>1, 'msg'=>'参数不全');}else{$this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));}//返回方式处理

        $param = array(
            'userId'    =>  $userId,
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),//当前页
            'pageSize'  =>  I('pageSize', 10, 'intval')//每页显示个数
        );

        $m = D('v3/Reward');
        $list = $m->getUserRewardList($param);

        if(I("apiAll") == 1){return array('code'=>0,'list'=>$list);}else{$this->ajaxReturn(array('code'=>0,'list'=>$list));}//返回方式处理
    }

}

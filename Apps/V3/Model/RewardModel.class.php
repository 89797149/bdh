<?php
namespace V3\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 抽奖功能类
 */
class RewardModel extends BaseModel {

    /**
     * 获得商家奖品列表
     * @param $param
     */
    public function getRewardList($shopId){
        $where = array('shopId'=>$shopId,'state'=>0);
        return M('reward')->where($where)->field('*,rewardImg as rewardImgs')->select();
    }

    /**
     * 抽奖 - 动作
     * @param $userId
     * @param $shopId
     */
    public function doReward($userId,$shopId,$orderNum=10){
        //判断用户上个月是否成功购买过orderNum单
        $buyNum = $this->userLastMonthBuyNum($userId,$shopId);
        if ($buyNum < $orderNum) return array('code'=>-1,'msg'=>'对不起，您没有抽奖资格');

        //判断用户是否已抽过奖
        $rewardRecord = $this->userRewardRecord($userId,$shopId);
        if (!empty($rewardRecord)) return array('code'=>-1,'msg'=>'您已经抽过奖了');

        //获得商家详情
        $shopInfo = $this->getShopsInfo($shopId);
        if (empty($shopInfo['reward_rate'])) return array('code'=>-1,'msg'=>'请在门店设置抽奖比例');
        $reward_rate = $shopInfo['reward_rate'];

        //获得门店抽奖活动信息
        $shopReward = $this->getShopsReward($shopId);
        if (empty($shopReward)) return array('code'=>-1,'msg'=>'门店没有抽奖活动');

        $rewardInfo = array();
        $len = count($shopReward);
        $t = 0;
        for ($i=0;$i<$len;$i++) {
            $rewardLen = ($shopReward[$i]['num'] < $shopReward[$i]['rate']) ? $shopReward[$i]['num'] : $shopReward[$i]['rate'];
            $t += $rewardLen;
            for ($j=0;$j<$rewardLen;$j++) {
                $rewardInfo[] = array(
                    'rewardId'  =>  $shopReward[$i]['rewardId'],
                    'shopId'  =>  $shopReward[$i]['shopId'],
                    'rank'  =>  $shopReward[$i]['rank'],
                    'name'  =>  $shopReward[$i]['name'],
                    'rewardImg'  =>  $shopReward[$i]['rewardImg']
                );
            }
        }
        //未中奖
        if ($t < $reward_rate) {
            $noRewardLen = intval($reward_rate - $t);
            for ($i=0;$i<$noRewardLen;$i++) {
                $rewardInfo[] = array(
                    'rewardId'  =>  0,
                    'shopId'    =>  $shopId,
                    'rank'      =>  '',
                    'name'      =>  '',
                    'rewardImg' =>  ''
                );
            }
        }

        //获得门店抽奖人员记录
        $shopRewardUser = $this->getShopRewardUser($shopId);

        //去掉以前的抽奖记录
        if (!empty($shopRewardUser)) {
            foreach ($shopRewardUser as $v) {
                foreach ($rewardInfo as $kr=>$vr) {
                    if ($v['rewardId'] == $vr['rewardId']) {
                        unset($rewardInfo[$kr]);
                        break;
                    }
                }
            }
        }

        $rewardInfo = array_values($rewardInfo);

        $num = mt_rand(0,count($rewardInfo));//随机数
        $haveRewardInfo = $rewardInfo[$num];

        if (empty($haveRewardInfo)) return array('code'=>-1,'msg'=>'抽奖活动已结束');
        //处理中奖信息
        $userInfo = M('users')->where(array('userId'=>$userId))->find();
        M('reward_user')->add(array(
            'shopId'    =>  $shopId,
            'userId'    =>  $userId,
            'userName'  =>  @$userInfo['userName'],
            'userPhone' =>  @$userInfo['userPhone'],
            'rewardId'  =>  $haveRewardInfo['rewardId'],
            'name'      =>  $haveRewardInfo['name'],
            'rewardImg' =>  $haveRewardInfo['rewardImg'],
            'rank'      =>  $haveRewardInfo['rank'],
            'is_get'    =>  0,
            'createTime'=>  date('Y-m-d H:i:s'),
            'state'     =>  0
        ));

        if (empty($haveRewardInfo['rewardId'])) return array('code'=>-1,'msg'=>'您没有中奖');
        else return array('code' => 0, 'rewardInfo' => $haveRewardInfo);
    }

    /**
     * 获得用户上个月在门店成功购买次数
     */
    public function userLastMonthBuyNum($userId,$shopId){
        $orderWhere = array(
            'userId'        =>  $userId,
            'shopId'        =>  $shopId,
            'createTime'    =>  array('like',date('Y-m',strtotime('-1 day')) . "%"),
            'orderStatus'   =>  4
        );
        return M('orders')->where($orderWhere)->count();
    }

    /**
     * 获得用户在门店的抽奖记录
     * @param $userId
     * @param $shopId
     */
    public function userRewardRecord($userId,$shopId){
        $rewardUserWhere = array(
            'userId'        =>  $userId,
            'shopId'        =>  $shopId,
            'createTime'    =>  array('like',date('Y-m') . "%")
        );
        return M('reward_user')->where($rewardUserWhere)->find();
    }

    /**
     * 获得商家详情
     * @param $shopId
     * @return mixed
     */
    public function getShopsInfo($shopId){
        $where = array(
            'shopId'    =>  $shopId
        );
        return M('shops')->where($where)->find();
    }

    /**
     * 获得门店奖品列表
     * @param $shopId
     * @return mixed
     */
    public function getShopsReward($shopId){
        $where = array(
            'shopId'    =>  $shopId,
            'state'     =>  0
        );
        return M('reward')->where($where)->select();
    }

    /**
     * 获得门店抽奖人员记录
     * @param $shopId
     * @return mixed
     */
    public function getShopRewardUser($shopId){
        $where = array(
            'shopId'    =>  $shopId,
            'createTime'    =>  array('like',date('Y-m') . "%")
        );
        return M('reward_user')->where($where)->select();
    }
	
	/**
     * 获得用户在门店的所有抽奖记录
     * @param $userId
     * @param $shopId
     */
    public function getUserRewardList($param = array()){
        $rewardUserWhere = array(
            'userId'        =>  $param['userId'],
            'shopId'        =>  $param['shopId']
        );
        return M('reward_user')->where($rewardUserWhere)->order('createTime desc')->limit(($param['page']-1)*$param['pageSize'],$param['pageSize'])->select();
    }

}
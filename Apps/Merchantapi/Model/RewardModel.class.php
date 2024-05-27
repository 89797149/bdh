<?php
namespace Merchantapi\Model;
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
    public function getRewardList($param){
        $where = " shopId = " . $param['shopId'] . " and state = 0 ";
        if (!empty($param['name'])) $where .= " and name like '%" . $param['name'] . "%' ";
        $sql = "select *,rewardImg as rewardImgs from __PREFIX__reward where " . $where . " order by rewardId desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 编辑奖励
     * @param $where
     * @param $data
     */
    public function editReward($where,$data){
        return M('reward')->where($where)->save($data);
    }

    /**
     * 新增奖品
     * @param $data
     */
    public function addReward($data){
        return M('reward')->add($data);
    }

    /**
     * 删除奖品
     * @param $where
     * @param $data
     */
    public function deleteReward($where,$data){
        $mr = M('reward');
        $rewardInfo = $mr->where($where)->find();
        $where_new = $where;
        $where_new['is_get'] = 0;
        $where_new['state'] = 0;
        $where_new['name'] = $rewardInfo['name'];
        $where_new['rank'] = $rewardInfo['rank'];
        $m = M('reward_user');
        $reward_user = $m->where($where_new)->find();
//        if (empty($reward_user)) $m->where($where)->save($data);
//        else return 2;
        if (!empty($reward_user)) return 2;

        $result = $mr->where($where)->save($data);
        return $result;
    }

    /**
     * 获得抽奖人员列表
     * @param $param
     */
    public function getRewardUserList($param){
        $where = " ru.shopId = " . $param['shopId'] . " and ru.state = 0 ";
        if (!empty($param['userName'])) $where .= " and ru.userName = '" . $param['userName'] . "' ";
        if (!empty($param['userPhone'])) $where .= " and ru.userPhone = '" . $param['userPhone'] . "' ";
        if (!empty($param['rank'])) $where .= " and ru.rank = '" . $param['rank'] . "' ";
        $sql = "select ru.*,s.shopName from __PREFIX__reward_user as ru inner join __PREFIX__shops as s on ru.shopId = s.shopId where " . $where . " order by ru.createTime desc";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }

    /**
     * 抽奖人员列表 - 领取奖品
     * @param $where
     */
    public function getReward($where){
        $m = M('reward_user');
        $rewardUserData = $m->where($where)->find();
        $result = 0;
        if (!empty($rewardUserData) && empty($rewardUserData['is_get']))
            $result = $m->where($where)->save(array('is_get'=>1));

        return $result;
    }

    /**
     * 删除抽奖人员(弃用)
     * @param $where
     */
    public function deleteRewardUser($where){
        $m = M('reward_user');
        $rewardUserData = $m->where($where)->find();
        $result = 0;
        if (!empty($rewardUserData) && !empty($rewardUserData['is_get']))
            $result = $m->where($where)->save(array('state'=>-1));

        return $result;
    }

}
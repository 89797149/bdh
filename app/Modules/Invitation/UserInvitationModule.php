<?php
/**
 * 用户邀请相关
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-27
 * Time: 11:20
 */

namespace App\Modules\Invitation;


use App\Models\UserInvitationModel;
use Think\Model;

class UserInvitationModule
{
    /**
     * 用户邀请记录-详情-受邀人id查找
     * @param int $UserToId 受邀人id
     * @return array
     * */
    public function getUserInvitationDetail(int $UserToId)
    {
        $model = new UserInvitationModel();
        $result = $model->where(array('UserToId' => $UserToId))->find();
        return (array)$result;
    }

    /**
     * 用户邀请记录-保存
     * @param array $params
     * -wst_user_invitation表字段
     * @return int
     * */
    public function saveUserInvitation(array $params, $trans)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $saveParams = array(
            'userId' => null,
            'source' => null,
            'UserToId' => null,
            'reward' => null,
            'invitationStatus' => null,
            'inviteRewardNum' => null,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($saveParams, $params);
        $model = new UserInvitationModel();
        if (empty($params['id'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $id = $model->add($saveParams);
            if (empty($id)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        return (int)$id;
    }

    /**
     * 用户邀请记录-递减邀请者奖励次数
     * @param int $id 数据id
     * @param int $num 数量
     * @param object $trans
     * */
    public function decInviteRewardNum(int $id, $num = 1, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new UserInvitationModel();
        $res = $model->where(array('id' => $id))->setDec('inviteRewardNum', $num);
        if (!$res) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}
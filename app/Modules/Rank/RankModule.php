<?php

namespace App\Modules\Rank;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\RankModel;

//身份表
use App\Models\RankUserModel;

//用户和身份管理表
use App\Models\RankGoodsModel;

//商品和等级/sku关联价格表

use Think\Model;

// 身份管理领域类
class RankModule extends BaseModel
{

    /**
     * 身份-列表
     * @param array $params 参数
     * -string rankName 等级名称
     * @param string $field 表字段
     * @return array
     * */
    public function getRankList(array $params, $field = '*')
    {
        $where = array(
            'isDelete' => 0,
        );
        if (empty($params['rankName'])) {
            $where['rankName'] = array('like', "%{$params['rankName']}%");
        }
        $mod = new RankModel();
        $res = $mod->where($where)->field($field)->order('rankCost desc,rankId asc')->select();
        return (array)$res;
    }

    /**
     * 身份-身份名称是否已经存在
     * @param string $rankName 等级名称
     * @return bool
     * */
    public function isExistRankName(string $rankName)
    {
        $model = new RankModel();
        $where = array(
            'isDelete' => 0,
            'rankName' => $rankName
        );
        $count = $model->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 身份-详情-多条件查找
     * @param array $params
     * -int rankId 等级id
     * -string rankName 等级名称
     * @param string $field 表字段
     * @return array
     * */
    public function getRankDetailByParams(array $params, $field = '*')
    {
        $where = array(
            'rankId' => null,
            'rankName' => null,
            'isDelete' => 0,
        );
        parm_filter($where, $params);
        $model = new RankModel();
        $res = $model->where($where)->field($field)->find();
        return (array)$res;
    }

    /**
     * 身份-详情-id查找
     * @param int $rankId 等级id
     * @param string $field 表字段
     * @return array
     * */
    public function getRankDetailById(int $rankId, $field = '*')
    {
        $where = array(
            'rankId' => $rankId,
            'isDelete' => 0,
        );
        $model = new RankModel();
        $res = $model->where($where)->field($field)->find();
        return (array)$res;
    }

    /**
     * 身份-保存身份信息
     * @param array $params
     *  wst_rank表字段
     * @return int
     * */
    public function saveRank(array $params)
    {
        $saveData = array(
            'rankName' => null,
            'rankCost' => null,
            'shopId' => null,
            'isDelete' => null,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($saveData, $params);
        if (isset($saveData['isDelete'])) {
            if ($saveData['isDelete'] == 1) {
                $saveData['deleteTime'] = date('Y-m-d H:i:s');
            }
        }
        $model = new RankModel();
        if (empty($params['rankId'])) {
            $saveData['createTime'] = date('Y-m-d H:i:s');
            $rankId = $model->add($saveData);
            if (empty($rankId)) {
                return 0;
            }
        } else {
            $rankId = $params['rankId'];
            $saveRes = $model->where(array('rankId' => $rankId))->save($saveData);
            if (!$saveRes) {
                return 0;
            }
        }
        return (int)$rankId;
    }

    /**
     * 用户身份-获取用户身份详情-用户id查找
     * @param int $userId 用户id
     * @param int $verificationDelete 是否校验删除(0:不校验 1:校验)
     * @return array
     * */
    public function getUserRankDetialByUserId(int $userId, $verificationDelete = 1)
    {
        $rankUserModel = new RankUserModel();
        $prefix = $rankUserModel->tablePrefix;
        $where = array(
            'rank1.isDelete' => 0,
            'rank_user.userId' => $userId,
        );
        if ($verificationDelete == 1) {
            $where['rank_user.isDelete'] = 0;
        }
        $field = 'rank_user.id,rank1.rankId,rank1.rankName';
        $res = $rankUserModel
            ->alias('rank_user')
            ->join("left join {$prefix}rank rank1 on rank1.rankId=rank_user.rankId")
            ->where($where)
            ->field($field)
            ->find();
        return (array)$res;
    }

    /**
     * 用户身份-保存用户身份
     * @param array $params
     * -int id 用户身份关联id
     * -int userId 用户id
     * -int isDelete 删除状态(0:未删除 1:已删除)
     * @param object $trans
     * @return int
     * */
    public function saveUserRank(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new RankUserModel();
        $saveParams = array(
            'rankId' => null,
            'userId' => null,
            'isDelete' => null,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($saveParams, $params);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = date('Y-m-d H:i:s');
            }
            if ($saveParams['isDelete'] == 0) {
                $saveParams['deleteTime'] = NULL;
            }
        }
        if (empty($params['id'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $id = $model->add($saveParams);
            if (!$id) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $saveRes = $model->where(array('id' => $id))->save($saveParams);
            if (empty($saveRes)) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }

    /**
     * 用户身份-为用户绑定身份
     * @param int $userId 用户id
     * @param int $rankId 身份id
     * @param object $trans
     * @return bool
     * */
    public function bindUserToRank(int $userId, int $rankId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $userRankDetail = $this->getUserRankDetialByUserId($userId, 0);
        $userRankData = array(
            'userId' => $userId,
            'rankId' => $rankId,
        );
        if (!empty($userRankDetail)) {
            $userRankData['isDelete'] = 0;
            $userRankData['id'] = $userRankDetail['id'];
        }
        $saveRes = $this->saveUserRank($userRankData, $dbTrans);
        if (empty($saveRes)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '绑定失败');
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 用户身份-解绑用户身份
     * @param int $userId 用户id
     * @param object $trans
     * @return bool
     * */
    public function unbindUserToRank(int $userId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $mod = new RankUserModel();
        $saveData = array(
            'isDelete' => 1,
            'deleteTime' => date('Y-m-d H:i:s'),
        );
        $res = $mod->where(array('userId' => $userId))->save($saveData);
        if (empty($res)) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}
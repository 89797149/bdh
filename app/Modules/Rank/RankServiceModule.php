<?php

namespace App\Modules\Rank;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\RankGoodsModel;
use App\Models\RankUserModel;
use App\Modules\Rank\RankModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Users\UsersModule;
use Think\Model;

/*
 * 身份
 * */

class RankServiceModule extends BaseModel
{
    /**
     * 身份-身份列表
     * @param string $rankName 等级名称
     * @return array
     * */
    public function getRankList(string $rankName)
    {
        $mod = new RankModule();
        $params = array(
            'rankName' => $rankName
        );
        $field = 'rankId,rankName,rankCost';
        return $mod->getRankList($params, $field);
    }

    /**
     * 身份-修改身份
     * @param int $rankId 等级id
     * @param string $rankName 等级名称
     * @return array
     * */
    public function updateRank(int $rankId, string $rankName,string $rankCost)
    {
        $mod = new RankModule();
        $rankDetial = $mod->getRankDetailByParams(array('rankName' => $rankName));
        if (!empty($rankDetial)) {
            if ($rankDetial['rankId'] != $rankId) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$rankName}已存在，请更换其它名称");
            }
        }
        $rankData = array(
            'rankId' => $rankId,
            'rankName' => $rankName,
            'rankCost' => $rankCost,
        );
        $saveRes = $mod->saveRank($rankData);
        if (!$saveRes) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "更新失败");
        }
        return returnData(true);
    }

    /**
     * 身份-添加身份
     * @param string $rankName 等级名称
     * @return array
     * */
    public function addRank(string $rankName,string $rankCost)
    {
        $module = new RankModule();
        if ($module->isExistRankName($rankName)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$rankName}已存在，请勿重复添加");
        }
        $rankData = array(
            'rankName' => $rankName,
            'rankCost' => $rankCost
        );
        $res = $module->saveRank($rankData);
        if (!$res) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '添加失败');
        }
        return returnData(true);
    }

    /**
     * 身份-身份详情
     * @param int $rankId 等级id
     * @return array
     * */
    public function getRankById($rankId)
    {
        $mod = new RankModule();
        $field = 'rankId,rankName,rankCost';
        return $mod->getRankDetailById($rankId, $field);
    }

    /**
     * 身份-删除
     * @param int $rankId 等级id
     * @return array
     * */
    public function deleteRank($rankId)
    {
        $mod = new RankModule();
        $rankData = array(
            'rankId' => $rankId,
            'isDelete' => 1,
        );
        $res = $mod->saveRank($rankData);
        if (empty($res)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        $rankUserModel = new RankUserModel();//用户身份关联表
        $rankGoodsModel = new RankGoodsModel();//商品身份关联表
        $saveData = array(
            'isDelete' => 1,
            'deleteTime' => date('Y-m-d H:i:s'),
        );
        $where = array(
            'rankId' => $rankId
        );
        $rankUserModel->where($where)->save($saveData);
        $rankGoodsModel->where($where)->save($saveData);
        return returnData(true);
    }


    /**
     * 用户身份-为用户绑定身份
     * @param int $userId 用户id
     * @param int $rankId 身份id
     * @return array
     * */
    public function bindUserToRank(int $userId, int $rankId)
    {
        $mod = new RankModule();
        $trans = new Model();
        $trans->startTrans();
        $res = $mod->bindUserToRank($userId, $rankId, $trans);
        if (!$res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '绑定失败');
        }
        $saveUsersParams = array(
            'rankId' => $rankId
        );
        $saveUsersRes = (new UsersModule())->updateUsersInfo($userId, $saveUsersParams, $trans);
        if ($saveUsersRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '绑定失败');
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 用户身份-解绑用户身份
     * @param int $userId 用户id
     * @return array
     * */
    public function unbindUserToRank(int $userId)
    {
        $trans = new Model();
        $trans->startTrans();
        $mod = new RankModule();
        $res = $mod->unbindUserToRank($userId, $trans);
        if (!$res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败');
        }
        $saveUsersParams = array(
            'rankId' => 0
        );
        $saveUsersRes = (new UsersModule())->updateUsersInfo($userId, $saveUsersParams, $trans);
        if ($saveUsersRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败');
        }
        $trans->commit();
        return returnData(true);
    }


}
<?php

namespace App\Modules\Users;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\Users\UsersModule;
use Think\Model;

/**
 * 统一提供给内部其他模块使用的用户信息类
 * Class UsersServiceModule
 * @package App\Modules\Users
 */
class UsersServiceModule extends BaseModel
{
    /**
     * 根据用户id获取用户详情
     * @param int $user_id 用户id
     * @param string $field 表字段
     * @return array
     * */
    public function getUsersDetailById(int $userId, $field = '*')
    {
        $users_module = new UsersModule();
        $data = $users_module->getUsersDetailById($userId, $field);
        return $data;
    }

    /**
     * 根据条件获取用户详情
     * @param array $params 业务参数
     * @param string $field 表字段
     * @return array
     * */
    public function getUsersDetailByWhere(array $params, $field = '*')
    {
        $users_module = new UsersModule();
        $data = $users_module->getUsersDetailByWhere($params, $field);
        return $data;
    }

    /**
     * 获取邀请详情
     * @param array $params 业务参数
     * @param string $field 表字段
     * @return array
     * */
    public function getRecordInfoByWhere(array $params, $field = '*')
    {
        $users_module = new UsersModule();
        $data = $users_module->getRecordInfoByWhere($params, $field);
        return $data;
    }

    /**
     * 修改用户信息 PS:该方法谁用谁扩展字段
     * @param int $userId
     * @param array $save
     * @param object $trans 事务M()
     * @return array
     * */
    public function updateUsersInfo(int $userId, $save = array(), $trans = null)
    {
        $users_module = new UsersModule();
        $data = $users_module->updateUsersInfo($userId, $save, $trans);
        return $data;
    }

    /**
     * 根据memmberToken获取用户信息
     * @return array
     * */
    public function getUsersInfoByMemberToken()
    {
        $users_module = new UsersModule();
        $data = $users_module->getUsersInfoByMemberToken();
        return $data;
    }

    /**
     * 返还用户积分
     * @param int $users_id 用户id
     * @param int $score 返还的积分
     * @return array
     * */
    public function return_users_score(int $users_id, int $score, $trans = null)
    {
        $users_module = new UsersModule();
        $data = $users_module->return_users_score($users_id, $score, $trans);
        return $data;
    }

    /**
     * 扣除户积分
     * @param int $users_id 用户id
     * @param int $score 扣除的积分
     * @return array
     * */
    public function deduction_users_score(int $users_id, int $score, $trans = null)
    {
        $users_module = new UsersModule();
        $data = $users_module->deduction_users_score($users_id, $score, $trans);
        return $data;
    }
}
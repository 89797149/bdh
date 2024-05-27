<?php

namespace App\Modules\User;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\User\UserModule;
use Think\Model;

/**
 * 统一提供给内部其他模块使用的用户信息类
 * Class UserServiceModule
 * @package App\Modules\User
 */
class UserServiceModule extends BaseModel
{
    /**
     * 根据职员id获取职员详情
     * @param int $id 职员id
     * @param string $field 表字段
     * @return array
     * */
    public function getUserDetailById(int $id, $field = '*')
    {
        $user_module = new UserModule();
        $data = $user_module->getUserDetailById($id, $field);
        return $data;
    }
}
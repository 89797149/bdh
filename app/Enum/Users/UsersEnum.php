<?php

namespace App\Enum\Users;

/**
 * 用户公共枚举
 * Class UsersEnum
 */
class UsersEnum
{
    const BIND_MOBILE = '000080';//请绑定手机号
    //PS:实际根据数据表字段来定义
    //删除状态(0:未删除 1:已删除)
    const DELETED = -1;
    const NOT_DELETED = 1;

    public function getUsersState()
    {
        return array(
            self::BIND_MOBILE => '请绑定手机号',
            self::DELETED => '已删除',
            self::NOT_DELETED => '未删除',
        );
    }

}
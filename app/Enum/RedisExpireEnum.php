<?php
namespace App\Enum;

class RedisExpireEnum
{
    const EXPIRE_SECOND_ONE = 1;//1秒
    const EXPIRE_SECOND_TEN = 10;//10秒
    const EXPIRE_SECOND_THIRTY = 30;//30秒

    const EXPIRE_MINUTE_ONE = 60;//1分钟
    const EXPIRE_MINUTE_FIVE = 300;//5分钟
    const EXPIRE_MINUTE_TEN = 600;//10分钟
    const EXPIRE_MINUTE_THIRTY = 1800;//30分钟

    const EXPIRE_HOUR_ONE = 3600;//1小时
    const EXPIRE_HOUR_TWO = 7200;//2小时
    const EXPIRE_HOUR_TEN = 36000;//10小时


    const EXPIRE_DAY_ONE = 86400;//1天
    const EXPIRE_DAY_TEN = 864000;//10天
    const EXPIRE_DAY_SEVEN = 7*86400;
    const EXPIRE_DAY_THIRTY = 30*86400;
}
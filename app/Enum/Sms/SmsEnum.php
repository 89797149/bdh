<?php

namespace App\Enum\Sms;

/**
 * 短信公共枚举
 * Class SmsEnum
 */
class SmsEnum
{
    const OUTTIME = 1800;//短信过期时间
    const NOT_DELETED = 1;
    const MOBILE_FORMAT = '#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#';
    public function getSmsState()
    {
        return array(
            self::OUTTIME => '验证码已过期',
        );
    }
}
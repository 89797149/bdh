<?php


namespace App\Modules\Log;

use App\Models\LogSmsModel;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

//短信日志类-为LogServiceModule类服务
class LogSmsModule extends Base
{
    /**
     * 根据验证码获取短信日志详情
     * @param array $params <p>
     * int smsId 短信id
     * string smsPhoneNumber 短信号码
     * string smsCode 短信码
     * </p>
     * @param string $field 表字段
     * @return array
     * */
    public function getLogSmsInfoByWhere(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $log_sms_model = new LogSmsModel();
        $where = array(
            'smsId' => null,
            'smsPhoneNumber' => '',
            'smsCode' => '',
            'dataFlag' => 1
        );
        parm_filter($where, $params);
        $data = $log_sms_model
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setData($data)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 销毁短信验证码
     * @param int $smsId 短信id
     * @return array
     * */
    public function destructionSms(int $smsId)
    {
        $response = LogicResponse::getInstance();
        $log_sms_model = new LogSmsModel();
        $where = array(
            'smsId' => $smsId
        );
        $save = array(
            'dataFlag' => -1
        );
        $result = $log_sms_model->where($where)->save($save);
        if (!$result) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码销毁失败');
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功');
    }
}
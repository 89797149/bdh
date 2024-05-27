<?php


namespace App\Modules\Log;

use App\Modules\Log\TableActionLogModule;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use http\Encoding\Stream;

//该类供应其他内部模块调用
class LogServiceModule extends Base
{
    /**
     * 添加表操作日志
     * @param array $params
     * @param object $trans 事务
     * @return array
     * */
    public function addTableActionLog(array $params, $trans = null)
    {
        $table_action_log_module = new TableActionLogModule();
        $data = $table_action_log_module->addTableActionLog($params, $trans);
        return $data;
    }

    /**
     * 获取短信日志详情
     * @param array $params <p>
     * int smsId 短信id
     * string smsPhoneNumber 短信号
     * string smsCode 短信验证码
     * </p>
     * @return array
     * */
    public function getLogSmsInfo(array $params)
    {
        $log_sms_module = new LogSmsModule();
        $data = $log_sms_module->getLogSmsInfoByWhere($params);
        return $data;
    }

    /**
     * 添加用户登陆日志
     * @param array $params
     * @param object $trans 事务
     * @return array
     * */
    public function addLogUserLogins(array $params, $trans = null)
    {
        $login_user_logins_module = new LogUserLoginsModule();
        $data = $login_user_logins_module->addLogUserLogins($params, $trans);
        return $data;
    }

    /**
     * 销毁短信验证码
     * @param int $smsId 短信id
     * @return array
     * */
    public function destructionSms(int $smsId)
    {
        $module = new LogSmsModule();
        $data = $module->destructionSms($smsId);
        return $data;
    }

    /**
     * @param int $orderId
     * @return array
     * 根据订单id获取订单日志
     */
    public function getLogOrderList(int $orderId)
    {
        $log_order_model = new LogOrderModule();
        $data = $log_order_model->getLogOrderList($orderId);
        return $data;
    }

    /**
     * @param array $params
     * @return array
     * 总后台添加操作日志
     */
    public function addOperationLog(array $params)
    {
        $logOperationModule = new LogOperationModule();
        $data = $logOperationModule->addOperationLog($params);
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 根据条件获取总后台操作日志
     */
    public function getOperationLogList(array $params)
    {
        $logOperationModule = new LogOperationModule();
        $data = $logOperationModule->getOperationLogList($params);
        return $data;
    }
}
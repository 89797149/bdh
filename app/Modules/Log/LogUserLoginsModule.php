<?php


namespace App\Modules\Log;

use App\Models\LogUserLoginsModel;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

//用户登陆日志类-为LogServiceModule类服务
class LogUserLoginsModule extends Base
{
    /**
     * 添加用户登陆日志
     * @param array $params <p>
     * int userId 用户id
     * string loginSrc 来源 0:商城  1:webapp  2:App
     * string loginRemark 登录备注信息
     * </p>
     * @param object $trans 事务
     * @return array
     * */
    public function addLogUserLogins(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $log_user_logins_model = new LogUserLoginsModel();
        $save = array(
            'userId' => null,
            'loginTime' => date('Y-m-d H:i:s'),
            'loginIp' => get_client_ip(),
            'loginSrc' => null,
            'loginRemark' => '',
        );
        parm_filter($save, $params);
        $insert_id = $log_user_logins_model->add($save);
        if (!$insert_id) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('登陆日志记录失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        $data = array(
            'insert_id' => $insert_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间获取日活量
     */
    public function getDailyAmountCount($startTime = '',$endTime = '')
    {
        $response = LogicResponse::getInstance();
        $log_user_logins_model = new LogUserLoginsModel();
        $where = [];
        $where['loginTime'] = ['between',[$startTime,$endTime]];
        if(empty($startTime) && empty($endTime)){
            $getDailyAmountCount = $log_user_logins_model->count();
        }else{
            $getDailyAmountCount = $log_user_logins_model->where($where)->count();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((int)$getDailyAmountCount)->toArray();
    }
}
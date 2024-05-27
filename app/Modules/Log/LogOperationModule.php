<?php


namespace App\Modules\Log;

use App\Models\LogOperationModel;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;

//总后台操作日志类-为LogServiceModule类服务
class LogOperationModule extends Base
{
    /**
     * @param $params
     * @return array
     * 总后台添加操作日志
     */
    public function addOperationLog($params)
    {
        $response = LogicResponse::getInstance();
        $logOperationModel = new LogOperationModel();
        $data = $logOperationModel->add($params);
        return $response->setData($data)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据条件获取总后台操作日志
     */
    public function getOperationLogList($params)
    {
        $operationType = $params['operationType'];
        $startTime = $params['startTime'];
        $endTime = $params['endTime'];
        $response = LogicResponse::getInstance();
        $logOperationModel = new LogOperationModel();
        $where = [];
        if(!empty($operationType)){
            $where['operationType'] = (int)$operationType;//操作行为类型【1:增加、2:删除、3:修改】
        }
        if(!empty($startTime)){
            $where['createTime'] = ['between',[$startTime,$endTime]];
        }
        if(empty($where)){
            $getOperationLogList = $logOperationModel->order('createTime desc')->select();
        }else{
            $getOperationLogList = $logOperationModel->where($where)->order('createTime desc')->select();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$getOperationLogList)->toArray();
    }
}
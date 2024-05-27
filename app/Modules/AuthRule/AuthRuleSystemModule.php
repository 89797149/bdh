<?php

namespace App\Modules\AuthRule;


use App\Enum\ExceptionCodeEnum;
use App\Models\AuthRuleModel;
use App\Models\AuthRuleSystemModel;
use App\Models\BaseModel;
use App\Models\RolesModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class AuthRuleModule
 * @package App\Modules\AuthRule
 * 该类只为AuthRuleServiceModule类服务
 */
class AuthRuleSystemModule extends BaseModel
{
    /**
     * @return mixed
     * 获取系统模式路由关联表列表
     */
    public function getAuthRuleSystemList()
    {
        $authRuleSystemModel = new AuthRuleSystemModel();
        $where = [];
        $rest = $authRuleSystemModel->where($where)->select();
        return $rest;
    }

    /**.
     * @param $systemModeId
     * @return mixed
     * 获取系统模式路由详情
     */
    public function getAuthRuleSystemInfo($systemModeId)
    {
        $authRuleSystemModel = new AuthRuleSystemModel();
        $where = [];
        $where['systemModeId'] = ['neq',$systemModeId];
        $rest = $authRuleSystemModel->where($where)->select();
        return $rest;
    }

    /**
     * @param $params
     * @return bool
     * 根据系统模式id更新
     */
    public function editAuthRuleSystemInfo($params)
    {
        $authRuleSystemModel = new AuthRuleSystemModel();
        $where = [];
        $where['systemModeId'] = $params['systemModeId'];

        $param = [];
        $param['authRuleIds'] = $params['authRuleIds'];//路由id【英文逗号隔开】

        $rest = $authRuleSystemModel->where($where)->save($param);
        return $rest;
    }
}
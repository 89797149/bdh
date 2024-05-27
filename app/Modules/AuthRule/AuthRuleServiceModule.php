<?php

namespace App\Modules\AuthRule;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use CjsProtocol\LogicResponse;

/**
 * Class AuthRuleServiceModule
 * @package App\Modules\AuthRule
 * 统一提供给内部其他模块使用的区域类
 */
class AuthRuleServiceModule extends BaseModel
{
    /**
     * @param $moduleType
     * @return array
     * 根据所属模块获取菜单节点列表
     */
    public function getAuthRuleList($moduleType)
    {
        $authRuleModule = new AuthRuleModule();
        $data = $authRuleModule->getAuthRuleList((int)$moduleType);
        return $data;
    }

    /**
     * @param $id
     * @return array
     * 获取菜单节点详情
     */
    public function getAuthRuleInfo($id)
    {
        $authRuleModule = new AuthRuleModule();
        $data = $authRuleModule->getAuthRuleInfo($id);
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 根据菜单节点id更新
     */
    public function editAuthRuleInfo($params)
    {
        $authRuleModule = new AuthRuleModule();
        $data = $authRuleModule->editAuthRuleInfo($params);
        return $data;
    }

    /**
     * @return array
     * 获取系统模式路由关联表列表
     */
    public function getAuthRuleSystemList()
    {
        $response = LogicResponse::getInstance();
        $authRuleSystemModule = new AuthRuleSystemModule();
        $data = $authRuleSystemModule->getAuthRuleSystemList();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$data)->toArray();
    }

    /**
     * @param $systemModeId
     * @return array
     * 获取系统模式路由详情
     */
    public function getAuthRuleSystemInfo($systemModeId)
    {
        $response = LogicResponse::getInstance();
        $authRuleSystemModule = new AuthRuleSystemModule();
        $data = $authRuleSystemModule->getAuthRuleSystemInfo($systemModeId);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$data)->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据系统模式id更新
     */
    public function editAuthRuleSystemInfo($params)
    {
        $response = LogicResponse::getInstance();
        $authRuleSystemModule = new AuthRuleSystemModule();
        $data = $authRuleSystemModule->editAuthRuleSystemInfo($params);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }
}
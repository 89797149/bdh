<?php

namespace App\Modules\Roles;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\RolesModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class RolesModule
 * @package App\Modules\Roles
 * 该类只为RolesServiceModule类服务
 */
class RolesModule extends BaseModel
{
    /**
     * @param $staffRoleId
     * @return array
     * 根据职员的角色ID获取角色列表
     */
    public function getRolesListByRoleIds($staffRoleId)
    {
        $response = LogicResponse::getInstance();
        $rolesModel = new RolesModel();
        $where = [];
        $where['roleId'] = ['IN', $staffRoleId];
        $where['roleFlag'] = 1;
        $rest = $rolesModel->where($where)->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$rest)->toArray();
    }

    /**
     * @return array
     * 获取角色列表
     */
    public function getRolesList()
    {
        $response = LogicResponse::getInstance();
        $rolesModel = new RolesModel();
        $where = [];
        $where['roleFlag'] = 1;
        $rest = $rolesModel->where($where)->order('roleId desc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$rest)->toArray();
    }

    /**
     * @param $roleId
     * @return array
     * 根据角色id获取详情
     */
    public function getRolesInfo($roleId)
    {
        $response = LogicResponse::getInstance();
        $rolesModel = new RolesModel();
        $where = [];
        $where['roleId'] = $roleId;
        $where['roleFlag'] = 1;
        $rest = $rolesModel->where($where)->find();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据角色id更新
     */
    public function editRolesInfo($params)
    {
        $response = LogicResponse::getInstance();
        $rolesModel = new RolesModel();

        $where = [];
        $where['roleId'] = $params['roleId'];
        $where['roleFlag'] = 1;

        $param = [];
        $param['roleName'] = null;//角色名称
        $param['remark'] = null;//角色描述
        $param['grant'] = null;//权限列表
        $param['roleFlag'] = null;//删除标志(-1:删除 1:有效)
        parm_filter($param, $params);

        $rest = $rolesModel->where($where)->save($param);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }
}
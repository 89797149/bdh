<?php

namespace App\Modules\Roles;


use App\Models\BaseModel;

/**
 * Class RolesServiceModule
 * @package App\Modules\Roles
 * 统一提供给内部其他模块使用的区域类
 */
class RolesServiceModule extends BaseModel
{
    /**
     * @param $staffRoleId
     * @return array
     * 根据职员的角色ID获取角色列表
     */
    public function getRolesListByRoleIds($staffRoleId)
    {
        $rolesModule = new RolesModule();
        $data = $rolesModule->getRolesListByRoleIds($staffRoleId);
        return $data;
    }

    /**
     * @return array
     * 获取角色列表
     */
    public function getRolesList()
    {
        $rolesModule = new RolesModule();
        $data = $rolesModule->getRolesList();
        return $data;
    }

    /**
     * @param $roleId
     * @return array
     * 根据角色id获取详情
     */
    public function getRolesInfo($roleId)
    {
        $rolesModule = new RolesModule();
        $data = $rolesModule->getRolesInfo((int)$roleId);
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 根据角色id更新
     */
    public function editRolesInfo($params)
    {
        $rolesModule = new RolesModule();
        $data = $rolesModule->editRolesInfo($params);
        return $data;
    }
}
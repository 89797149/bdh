<?php

namespace Adminapi\Model;

use App\Modules\Roles\RolesServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 角色服务类
 */
class RolesModel extends BaseModel
{
    /**
     * 新增
     */
    public function insert()
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $data = [];
        $data["roleName"] = I("roleName");
        $data["createTime"] = date('Y-m-d H:i:s');
        $data["roleFlag"] = 1;
        if ($this->checkEmpty($data)) {
            $data["remark"] = I("remark");
            $rs = $this->add($data);
            if (false !== $rs) {
                $rd = returnData(true);
            }
        }
        return $rd;
    }

    /**
     * 修改【旧】
     */
    public function edit()
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I("id", 0);
        $data['roleName'] = I("roleName");
        if ($this->checkEmpty($data)) {
            $data["remark"] = I("remark");
            $rs = $this->where("roleId=" . $id)->save($data);
            if (false !== $rs) {
                $rd = returnData(true);
            }
        }
        return $rd;
    }

    /**
     * @return mixed
     * 修改商城职员角色信息
     */
    public function editRolesInfo()
    {
        $id = (int)I("id", 0);
        $rolesServiceModule = new RolesServiceModule();
        $getRolesInfo = $rolesServiceModule->getRolesInfo($id);
        if (empty($getRolesInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $data = [];
        $data['roleName'] = I("roleName");
        $data["remark"] = I("remark");
        $data["roleId"] = $id;

        $res = $rolesServiceModule->editRolesInfo($data);
        if (empty($res['data'])) {
            return returnData(false, -1, 'error', '暂无数据变更');
        }
        return returnData(true);
    }

    /**
     * 获取指定对象
     */
    public function get()
    {
        return $this->where("roleId=" . (int)I('id'))->find();
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return mixed
     * 获取角色列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $rolesServiceModule = new RolesServiceModule();
        $res = $rolesServiceModule->getRolesList();
        $rest = $res['data'];
        $rolesList = arrayPage($rest, $page, $pageSize);
        if (!empty($rolesList['root'])) {
            $staffsList = M('staffs')->where(['staffFlag' => 1])->select();
            $staffRoleId = array_get_column($staffsList, 'staffRoleId');
            foreach ($rolesList['root'] as $k => $v) {
                $roleNum = 0;
                foreach ($staffRoleId as $val) {
                    $staffRoleIds = explode(',', $val);
                    if (in_array($v['roleId'], $staffRoleIds)) {
                        $roleNum += 1;
                    }
                }
                $rolesList['root'][$k]['roleNum'] = (int)$roleNum;
            }
        }
        return returnData($rolesList);
    }

    /**
     * 获取列表
     */
    public function queryByList()
    {
        return $this->select();
    }

    /**
     * 删除
     */
    public function del()
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $rs = $this->delete((int)I('id'));
        if (false !== $rs) {
            $rd = returnData(true);
        }
        return $rd;
    }

    /**
     * @param $params
     * @return mixed
     * 分配权限
     */
    public function distributionAuth($params)
    {
        $roleId = $params['roleId'];
        $grant = $params['grant'];
        if (!empty($grant)) {//获取第三级权限，前端只传了一级和二级权限
            $where = [];
            $where['module_type'] = 1;
            $where['pid'] = ['neq', 0];
            $where['pid'] = ['IN', $grant];
            $authRuleList = M('auth_rule')->where($where)->select();
            $authId = implode(',', array_get_column($authRuleList, 'id'));
            $authIds = $grant;
            if (!empty($authId)) {
                $authIds = $grant . ',' . $authId;
            }
            $grants = explode(',', $authIds);
            $grant = implode(',', array_unique($grants));
        }

        $data = [];
        $data['grant'] = (string)$grant;
        $data['roleId'] = $roleId;

        $rolesServiceModule = new RolesServiceModule();
        $res = $rolesServiceModule->editRolesInfo($data);
        if (empty($res['data'])) {
            return returnData(false, -1, 'error', '暂无权限变更');
        }
        return returnData(true);
    }
}
<?php
/**
 * 门店职员
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:54
 */

namespace App\Modules\ShopStaffMember;


use App\Models\UserModel;
use App\Modules\Roles\RolesModule;

class ShopStaffMemberModule
{
    /**
     * 门店职员-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string keywords 关键字(姓名/手机号)
     * -int status 状态(0:正常 -2:禁用)
     * -int use_page 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 分页条数
     * @param string $field 表字段
     * @return array
     * */
    public function getShopStaffMemberList(array $paramsInput, $field = '*')
    {
        $shopId = $paramsInput['shopId'];
        $where = "status != -1 and shopId = {$shopId}";
        if (!empty($paramsInput['keywords'])) {
            $where .= " and (username like '%{$paramsInput['keywords']}%' or phone like '%{$paramsInput['keywords']}%') ";
        }
        if (isset($paramsInput['status'])) {
            if (is_numeric($paramsInput['status'])) {
                $where .= " and status={$paramsInput['status']} ";
            }
        }
        $model = new UserModel();
        $sql = $model->where($where)->field($field)->order("addtime desc")->buildSql();
        if ($paramsInput['use_page'] == 1) {
            $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        } else {
            $result = $model->query($sql);
        }
        if (isset($result['root'])) {
            $list = $result['root'];
        } else {
            $list = $result;
        }
        foreach ($list as &$item) {
            $item['email'] = (string)$item['email'];
            $item['lastTime'] = (string)$item['lastTime'];
            $item['remark'] = (string)$item['remark'];
            $item['roleName'] = $this->getShopStaffMemberRoleName($item['id']);
            unset($item['pass']);
        }
        unset($item);
        if (isset($result['root'])) {
            $result['root'] = $list;
        } else {
            $result = $list;
        }
        return $result;
    }

    /**
     * 职员-获取职员角色名称
     * @param int $id 职员id
     * @return string
     */
    public function getShopStaffMemberRoleName(int $id)
    {
        $where = array(
            'u_role.uid' => $id
        );
        $userRoleList = M('user_role u_role')
            ->join('left join wst_role role on role.id = u_role.rid')
            ->where($where)
            ->field('name')
            ->select();
        $roleName = array_column($userRoleList, 'name');
        $roleNames = implode(',', array_unique($roleName));
        return (string)$roleNames;
    }

    /**
     * 职员-详情-id查找
     * @param int $id 职员id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopStaffMemberDetail(int $id, $field = '*')
    {
        $model = new UserModel();
        $where = array(
            'id' => $id,
        );
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }

    /**
     * 职员-详情-id批量查找
     * @param array $idArr 职员id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopStaffMemberListByIdArr($idArr, $field = '*')
    {
        $model = new UserModel();
        $where = array(
            'id' => array('in', $idArr),
        );
        $result = $model->where($where)->field($field)->select();
        return (array)$result;
    }
}
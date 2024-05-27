<?php

namespace Merchantapi\Model;

use think\Image;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商户端节点
 */
class NodeModel extends BaseModel
{

    /**
     * 获取当前操作者的权限列表
     * @param int $loginUserId 当前操作者用户id
     * TODO:如果当前用户管理员可以默认返回所有权限 不走权限判断
    public function getActionNodeList(int $loginUserId)
    {
        $rule_tab = M('auth_rule');
        $field = 'id,pid,title,icon,page_hidden,component';
            $userRoleTab = M('user_role');
            $roleNodeTab = M('role_node');
            $where = array(
                'uid' => $loginUserId
            );
            $userRoleList = $userRoleTab
                ->where($where)
                ->select();
            if (empty($userRoleList)) {
                return array();
            }
            $roleIdArr = array_column($userRoleList, 'rid');
            $where = array(
                'rid' => array('IN', $roleIdArr)
            );
            $roleNodeList = $roleNodeTab
                ->where($where)
                ->select();
            if (empty($roleNodeList)) {
                return array();
            }
            $nodeIdArr = array_column($roleNodeList, 'nid');
            $nodeIdArr = array_values(array_unique($nodeIdArr));
            $where = array(
                'id' => array('IN', array($nodeIdArr)),
                'module_type' => 2,
                'status' => 0,
                'page_hidden' => array('IN', array(0, -1)),
            $menu_list = $rule_tab
                ->field($field)
                ->order('weigh asc')
                ->select();
            $frame_pid_arr = array();
            $where = array(
                'module_type' => 2,
                'status' => 0,
                //'menu_type' => array('IN', array(0, 1)),//该字段貌现在貌似无用,都是0
            $menu_list = $rule_tab
                ->field($field)
                ->order('weigh asc')
                ->select();
            $frame_pid_arr = array();
            $frame_pid_arr = array_values(array_unique($frame_pid_arr));
        if (empty($menu_list)) {
        }
        $menu_id_arr = array_column($menu_list, 'id');
            foreach ($child as $val) {
                    $item['child'][] = $val;
                }
            }
        }
        unset($item);
        return $menu_list;

    /**
     * @param $param
     * @return array
     * 获取权限列表【树形】
     */
    public function getAuthRuleList($param)
    {
        $roleNodeModel = M('role_node');
        $rnList = $roleNodeModel->where('rid=' . (int)$param['rid'] . ' and shopId=' . $param['shopId'])->select();
        $staffNid = array_unique(array_get_column($rnList, 'nid'));
        $params = [];
        $params['shopId'] = $param['shopId'];
        $params['userId'] = $param['userId'];
        $params['staffNid'] = $staffNid;
        $params['module_type'] = 2;
        $params['checked'] = 1;
        $res = getStaffPrivilege($params);
        return (array)$res;
    }

}
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
     * TODO:如果当前用户管理员可以默认返回所有权限 不走权限判断     */
    public function getActionNodeList(int $loginUserId)
    {
        $rule_tab = M('auth_rule');
        $field = 'id,pid,title,icon,page_hidden,component';        if ($loginUserId > 0) {
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
                'page_hidden' => array('IN', array(0, -1)),                //'menu_type' => array('IN', array(0, 1)),//该字段貌现在貌似无用,都是0                'component' => array('IN', array('Layout', 'Frame')),            );
            $menu_list = $rule_tab                ->where($where)
                ->field($field)
                ->order('weigh asc')
                ->select();
            $frame_pid_arr = array();            foreach ($menu_list as $item) {                if ($item['pid'] > 0 && $item['component'] == 'Frame') {                    $frame_pid_arr[] = $item['pid'];                }            }            $frame_pid_arr = array_values(array_unique($frame_pid_arr));            foreach ($menu_list as $key => $val) {                if (in_array($val['id'], $frame_pid_arr)) {                    unset($menu_list[$key]);                }            }            $menu_list = array_values($menu_list);        } else {
            $where = array(
                'module_type' => 2,
                'status' => 0,
                //'menu_type' => array('IN', array(0, 1)),//该字段貌现在貌似无用,都是0                'page_hidden' => array('IN', array(0, -1)),                'component' => array('IN', array('Layout', 'Frame')),            );
            $menu_list = $rule_tab                ->where($where)
                ->field($field)
                ->order('weigh asc')
                ->select();
            $frame_pid_arr = array();            foreach ($menu_list as $item) {                if ($item['pid'] > 0 && $item['component'] == 'Frame') {                    $frame_pid_arr[] = $item['pid'];                }            }
            $frame_pid_arr = array_values(array_unique($frame_pid_arr));            foreach ($menu_list as $key => $val) {                if (in_array($val['id'], $frame_pid_arr)) {                    unset($menu_list[$key]);                }            }            $menu_list = array_values($menu_list);        }
        if (empty($menu_list)) {            return array();
        }
        $menu_id_arr = array_column($menu_list, 'id');        $where = array(            'status' => 0,            'page_hidden' => array('IN', array(0, -1)),            'pid' => array('IN', $menu_id_arr),        );        $child = $rule_tab            ->where($where)            ->field($field)            ->order('weigh asc')            ->select();        foreach ($menu_list as &$item) {            $item['child'] = array();
            foreach ($child as $val) {                $component = explode('@/views', $val['component']);                if (empty($component[0])) {                    $val['component'] = $component[1];                }                if ($val['pid'] == $item['id']) {
                    $item['child'][] = $val;
                }
            }
        }
        unset($item);
        return $menu_list;    }

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
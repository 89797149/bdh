<?php

namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员服务类
 */
class UserModel extends BaseModel
{
    //##########################  二开  #######################################

    /**
     * 获取用户
     */
    public function getlist($parameter = array(), &$msg = '')
    {
//        $where = array(
//            'shopId'=>$parameter['shopId'],
//        );
//        isset($parameter['username'])?$where['username']=$parameter['username']:false;
//        isset($parameter['email'])?$where['email']=$parameter['email']:false;
//        isset($parameter['phone'])?$where['phone']=$parameter['phone']:false;
//        isset($parameter['status'])?$where['status']=$parameter['status']:false;
//        if(!in_array($where['status'],array(0,-2))){//超出选项
//            $where['status'] = -3;
//        }elseif(!isset($where['status'])){
//            $where['status|neq'] = -1;
//        }
//        $where_str = arrChangeSqlStr($where);
        $phone = WSTAddslashes($parameter['phone']);
        $where_str = "status != -1 and shopId = {$parameter['shopId']}";
        if (!empty($parameter['phone'])) {
//            $where_str .= " and ( username LIKE '%{$phone}%' or phone = {$phone})";
            $where_str .= " and (username LIKE '%{$phone}%' or phone LIKE '%{$phone}%')";
        }
        $sql = "SELECT * FROM __PREFIX__user 
				WHERE {$where_str} order by addtime desc";
        $list = $this->pageQuery($sql, $parameter['page'], $parameter['pageSize']);
        foreach ($list['root'] as $k => $v) {
            $param = [];
            $param['uid'] = $v['id'];
            $param['shopId'] = $v['shopId'];
            $list['root'][$k]['roleName'] = $this->getUserRoleName($param);//获取职员角色名称
        }
        return $list;
    }

    /**
     * @param $param
     * @return string
     * 获取职员角色名称
     */
    public function getUserRoleName($param)
    {
        $userRoleList = M('user_role wur')
            ->join('left join wst_role wr on wr.id = wur.rid')
            ->where(['wur.uid' => $param['uid'], 'wur.shopId' => $param['shopId']])
            ->select();
        $roleName = array_get_column($userRoleList, 'name');
        $roleNames = implode(',', array_unique($roleName));
        return (string)$roleNames;
    }

    /**
     * 添加用户
     */
    public function addUser($parameter = array(), &$msg = '')
    {
        $res = [
            'code' => 0,
            'msg' => '添加成功',
        ];
        #检测
        if (!$parameter) {
            $res['code'] = -1;
            $res['msg'] = '参数有误';
            return $res;
        }
        //$chekeRes = $this->checkLoginKey($parameter['name'].$msg);
        //$chekeRes = $this->checkLoginKeyNew($parameter['name'],$parameter['shopId']);
        $chekeRes = $this->where("phone='{$parameter['phone']}' and status != -1")->find();
        if ($chekeRes) {
            $shopInfo = M('shops')->where(['shopId' => $chekeRes['shopId'], 'shopFlag' => 1])->find();
            if ($shopInfo) {
                $res['code'] = -1;
                $res['msg'] = '保存失败，该账号已被使用，请更换其他账号';
                return $res;
            }
            M('user')->where(['id' => $chekeRes['id']])->delete();//店铺不存在了就删除吧,避免影响其他逻辑
        }
        #保存
        M()->startTrans();//开启事物
        $addTime = date('Y-m-d H:i:s');
        $addTimeNew = strtotime($addTime);//日期转时间戳
        $data = array(
//            'name'=>$parameter['name'],
            'pass' => md5($parameter['pass'] . $addTimeNew),
            'username' => $parameter['username'],
//            'email'=>$parameter['email'],
            'phone' => $parameter['phone'],
            //'status'=>$parameter['status'],
            'addtime' => date('Y-m-d H:i:s'),
            'shopId' => $parameter['shopId'],
            'remark' => $parameter['remark'],
        );

        $rs = $this->add($data);
        #角色添加
        $rflag = true;
        if (isset($parameter['role']) && $rs) {
            $rflag = $this->saveUserRole($parameter['role'], $rs, $parameter['shopId']);
        }
        if (!$rflag) {
            M()->rollback();
            $res['code'] = -1;
            $res['msg'] = '保存失败';
            return $res;
        }
        M()->commit();
        return $res;
    }

    /**
     * @param $param
     * @return mixed
     * 编辑职员状态
     */
    public function upUserStatus($param)
    {
        $userModel = M('user');
        $res = $userModel->where(['shopId' => $param['shopId'], 'id' => $param['id']])->save(['status' => $param['status']]);
        if (empty($res)) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    //用户角色保存
    public function saveUserRole($role = '', $user_id = '', $shopId = '')
    {
        //检测
        $m = M('user_role');
        if (!$role || !$user_id || !$shopId) {
            if (!$role && $user_id && $shopId) {//没有勾选，清空
                $m->where('uid=' . (int)$user_id . ' and shopId=' . $shopId)->delete();
                return true;
            }
            return false;
        }
        //删除之前
        $m->where('uid=' . (int)$user_id . ' and shopId=' . $shopId)->delete();
        //保存现在
        $role_arr = array_unique(explode(',', $role));
        foreach ($role_arr as $key => $rid) {
            $save_data = array(
                'uid' => (int)$user_id,
                'shopId' => $shopId,
                'rid' => $rid,
            );
            $m->add($save_data);
        }
        return true;
    }

    /**
     * 用户管理员登录
     */
    public function login($parameter = array(), &$msg = '')
    {
        if (!$parameter) {
            $parameter = I();
        }
        $rd = array('status' => -1);
        $loginName = WSTAddslashes($parameter['loginName']);
        $users = $this->where("phone = '{$loginName}' and status=0")->find();
        $verifyShopInfo = verifyShopInfo((int)$users['shopId']);
        if (empty($verifyShopInfo)) {
            $rd['msg'] = '请联系总管理员';
            return $rd;
        }
        $addTime = strtotime($users['addtime']);//日期转时间戳
//        print_r($addTime);die;
//        print_r(md5($parameter['loginPwd'].$addTime));die;
//        print_r($users['pass']);die;
        if ($users['pass'] != md5($parameter['loginPwd'] . $addTime)) {
            $rd['msg'] = '账号或密码错误';
            return $rd;
        }
        M('wst_user')->where(['id' => $users['id'], 'shopId' => $users['shopId']])->save(['lastTime' => date('Y-m-d H:i:s')]);
        //获取职员权限信息
        $param = [];
        $param['userId'] = $users['id'];
        $param['shopId'] = $users['shopId'];
        $staffNid = $this->getStaffPower($param);
        $rd['shop'] = $users;
        $rd['shopInfo'] = $verifyShopInfo;
        $rd['staffNid'] = $staffNid;
        $rd['status'] = 1;
        return $rd;

    }

    /**
     * @param $params
     * @return array
     * 获取职员权限信息
     */
    public function getStaffPower($params)
    {
        $userRoleModel = M('user_role');
        $roleModel = M('role');
        $roleNodeModel = M('role_node');
        $authRuleModel = M('auth_rule');
        $ruleInfo = $userRoleModel->where('uid=' . (int)$params['userId'] . ' and shopId=' . $params['shopId'])->select();
        $rid_arr = array_get_column($ruleInfo, 'rid');
        $rids = implode(',', array_unique($rid_arr));
        if (empty($rids)) {
            return [];
        }

        $roleList = $roleModel->where('status=1 and id in(' . $rids . ') and shopId=' . $params['shopId'])->select();
        $check_ridArr = array_get_column($roleList, 'id');
        $check_rids = implode(',', array_unique($check_ridArr));
        if (empty($check_rids)) {
            return [];
        }

        $where = [];
        $where['rid'] = ["IN", $check_rids];
        $where['shopId'] = $params['shopId'];
        $nrList = $roleNodeModel->where($where)->select();
        $ruleList = array_unique(array_get_column($nrList, 'nid'));
        if (empty($ruleList)) {
            return [];
        }
        //因为添加权限时只有添加到了二级，所以需要知道二级下面三级的ID
        $nodeId = [];
        foreach ($ruleList as $v) {
            $authRuleInfo = $authRuleModel->where(['module_type' => 2, 'id' => $v])->find();
            if ($authRuleInfo['pid'] != 0) {
                $nodeId[] = $v;
            }
        }
        $nodeIds = implode(',', $nodeId);
        $authRuleList = $authRuleModel->where('pid in(' . $nodeIds . ') and module_type = 2')->select();
        $authRuleId = array_get_column($authRuleList, 'id');
        $res = array_unique(array_merge($ruleList, $authRuleId));
        return $res;
    }

    public function checkLoginKey($loginName = '', &$msg = '')
    {
        if (!$loginName) {
            return false;
        }
        $res = $this->where("name='{$loginName}' and status != -1")->find();
        if ($res) {
            return false;
        }
        return true;
    }

    //PS:需要配合在数据中执行alter table wst_user drop index nameindex;
    public function checkLoginKeyNew($loginName = '', $shopId = 0)
    {
        if (!$loginName || !$shopId) {
            return false;
        }
        $res = $this->where("name='{$loginName}' and status != -1 and shopId='{$shopId}'")->find();
        if ($res) {
            return false;
        }
        return true;
    }




    //

    /**
     * 删除用户
     */
    public function del($parameter = array(), &$msg = '')
    {
        $saveData = array(
            'status' => -1
        );
        $rs = $this->where("shopId=" . $parameter['shopId'] . ' and id=' . $parameter['id'])->save($saveData);
        return $rs;
    }


    /**
     * 编辑用户
     */
    public function edit($parameter = array(), &$msg = '')
    {

        $res = [
            'code' => 0,
            'msg' => '保存成功'
        ];
        $saveData = array();
//        isset($parameter['name'])?$saveData['name']=$parameter['name']:false;
        isset($parameter['username']) ? $saveData['username'] = $parameter['username'] : false;
//        isset($parameter['email'])?$saveData['email']=$parameter['email']:false;
        isset($parameter['phone']) ? $saveData['phone'] = $parameter['phone'] : false;
        isset($parameter['status']) ? $saveData['status'] = $parameter['status'] : false;//0=禁用，1=启

        if (!empty($parameter['phone'])) {
            $chekeRes = $this->where("phone='{$parameter['phone']}' and status != -1")->find();
            if ($chekeRes && $chekeRes['id'] != $parameter['id']) {
                $shopInfo = M('shops')->where(['shopId' => $chekeRes['shopId'], 'shopFlag' => 1])->find();
                if ($shopInfo) {
                    $res['code'] = -1;
                    $res['msg'] = '保存失败，账号已存在，请更换其他账号名称';
                    return $res;
                }
                M('user')->where(['id' => $chekeRes['id']])->delete();//店铺不存在了就删除吧,避免影响其他逻辑
            }
        }
        //后加,修复修改密码无效 start
        //oldPassword
        //newPassword
//        if(!empty($parameter['oldPassword']) || !empty($parameter['newPassword'])){
//            $oldPassword = trim($parameter['oldPassword'],'');
//            $newPassword = trim($parameter['newPassword'],'');
//            if($oldPassword != $newPassword){
//                $res['code'] = -1;
//                $res['msg'] = '两次密码输入的不一致';
//                return $res;
//            }
//            $userInfo = M('user')->where(['id'=>$parameter['id']])->find();
//            $saveData['pass'] = md5($newPassword.$userInfo['addtime']);
//        }
        if (!empty($parameter['pass']) || !empty($parameter['repass'])) {
            $pass = trim($parameter['pass'], '');
            $repass = trim($parameter['repass'], '');
            if ($pass != $repass) {
                $res['code'] = -1;
                $res['msg'] = '两次密码输入的不一致';
                return $res;
            }
            $userInfo = M('user')->where(['id' => $parameter['id']])->find();
            $addTime = strtotime($userInfo['addtime']);
            $saveData['pass'] = md5($pass . $addTime);
        }
        $saveData['remark'] = $parameter['remark'];
        //后加,修复修改密码无效 end
        M()->startTrans();//开启事物
        $rs = M('user')->where("shopId=" . $parameter['shopId'] . ' and id=' . $parameter['id'])->save($saveData);
        if (isset($parameter['role']) && $parameter['id'] && $parameter['shopId']) {
            $rs = $this->saveUserRole($parameter['role'], $parameter['id'], $parameter['shopId']);
            S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}", null);
        }
        if ($rs === false) {
            M()->rollback();
            $res['code'] = -1;
            $res['msg'] = '角色保存失败';
            return $res;
        }
        M()->commit();
        return $rs;
    }

    /**
     *修改职员密码
     * @param array $loginUserInfo 当前登陆者信息
     * @param string $password
     * */
    public function editUserPassword($loginUserInfo, $password)
    {
        if ($loginUserInfo['id'] > 0) {
            $id = $loginUserInfo['id'];
            $userTab = M('user');
            $where = [];
            $where['id'] = $id;
            $userInfo = $userTab->where($where)->find();
            if (empty($userInfo)) {
                return returnData(false, -1, 'error', '修改密码失败，用户信息有误');
            }
            $data = [];
            $data["pass"] = md5($password . $userInfo['addtime']);
            $res = $userTab->where($where)->save($data);
            if (false !== $res) {
                return returnData(true);
            } else {
                return returnData(false, -1, 'error', '修改密码失败');
            }
        } else {
            $usersTab = M('users');
            $userId = $loginUserInfo['userId'];
            $where = [];
            $where['userFlag'] = 1;
            $where['userId'] = $userId;
            $userInfo = $usersTab->where($where)->find();
            if (empty($userInfo)) {
                return returnData(false, -1, 'error', '修改密码失败，用户信息有误');
            }
            $data = [];
            $data["loginPwd"] = md5($password . $userInfo['loginSecret']);
            $res = $usersTab->where($where)->save($data);
            if (false !== $res) {
                return returnData(true);
            } else {
                return returnData(false, -1, 'error', '修改密码失败');
            }
        }
    }

    /**
     * 获取职员/管理员信息
     * @param array $loginUserInfo 操作者信息
     * */
    public function getActionUserInfo(array $loginUserInfo)
    {
        if (empty($loginUserInfo['id'])) {
            $actionUserInfo = [];
            $actionUserInfo['shopId'] = $loginUserInfo['shopId'];
            $actionUserInfo['user_id'] = $loginUserInfo['userId'];
            $actionUserInfo['user_username'] = $loginUserInfo['userName'];
            $actionUserInfo['user_type'] = 1;//超级管理员
        } else {
            $actionUserInfo = [];
            $actionUserInfo['shopId'] = $loginUserInfo['shopId'];
            $actionUserInfo['user_id'] = $loginUserInfo['id'];
            $actionUserInfo['user_username'] = $loginUserInfo['username'];
            $actionUserInfo['user_type'] = 2;//职员
        }
        return $actionUserInfo;
    }

    /**
     * 职员/管理员-获取权限列表
     * @param array $loginUserInfo
     * @return array $data
     * */
    public function getLoginUserNodeList(array $loginUserInfo)
    {
        $actionUserInfo = $this->getActionUserInfo($loginUserInfo);
        $nodeModel = D('Merchantapi/Node');
        if ($actionUserInfo['user_type'] == 1) {
            $data = $nodeModel->getActionNodeList(0);
        } else {
            $data = $nodeModel->getActionNodeList($actionUserInfo['user_id']);
        }
        return $data;
    }

    /**
     * 职员/管理员-设置常用菜单
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $id 权限id
     * @return bool $data
     * */
    public function settingOftenUsedMenu(array $loginUserInfo, array $id)
    {
        $actionUserInfo = $this->getActionUserInfo($loginUserInfo);
        $oftenTab = M('often_used_menu');
        $where = [];
        $where['loginUserId'] = $actionUserInfo['user_id'];
        $where['loginUserType'] = $actionUserInfo['user_type'];
        $data = (array)$oftenTab->where($where)->find();
        $id = array_unique($id);
        if (count($id) > 9) {
            return returnData(false, -1, 'error', '常用菜单最多设置9个');
        }
        if (empty($data)) {
            $saveData = [];
            $saveData['loginUserId'] = $actionUserInfo['user_id'];
            $saveData['loginUserType'] = $actionUserInfo['user_type'];
            $saveData['nodeId'] = implode(',', $id);
            $saveData['createTime'] = date('Y-m-d H:i:s', time());
            $saveData['updateTime'] = date('Y-m-d H:i:s', time());
            $res = $oftenTab->add($saveData);
        } else {
            $where = [];
            $where['oftenId'] = $data['oftenId'];
            $saveData = [];
            $saveData['nodeId'] = implode(',', $id);
            $saveData['updateTime'] = date('Y-m-d H:i:s', time());
            $res = $oftenTab->where($where)->save($saveData);
        }
        if ($res === false) {
            return returnData(false, -1, 'error', '设置失败');
        } else {
            return returnData(true);
        }
    }

    /**
     * 职员/管理员-获取常用菜单列表
     * @param array $loginUserInfo 当前登陆者信息
     * @return array $data
     * */
    public function getSettingOftenUsedMenuList(array $loginUserInfo)
    {
        $actionUserInfo = $this->getActionUserInfo($loginUserInfo);
        $where = [];
        $where['loginUserId'] = $actionUserInfo['user_id'];
        $where['loginUserType'] = $actionUserInfo['user_type'];
        $data = M('often_used_menu')->where($where)->find();
        if (empty($data['nodeId'])) {
            return [];
        }
        $rule_table = M('auth_rule');
        $where = [];
        $where['id'] = ['IN', $data['nodeId']];
        $where['status'] = 0;
        $where['page_hidden'] = array('in', array(0, -1));
        //$nodeList = M('node')->where($where)->select();
        $field = 'id,pid,title,component,icon,path';
        $rule_list = $rule_table->where($where)->field($field)->select();
        if (empty($rule_list)) {
            return array();
        }
        $pid_arr = array_unique(array_column($rule_list, 'pid'));
        $where = [];
        $where['id'] = ['IN', $pid_arr];
        $where['status'] = 0;
        $parent_list = $rule_table->where($where)->field($field)->select();
        foreach ($rule_list as &$item) {
            $item['icon'] = '';
            foreach ($parent_list as $val) {
                if ($item['pid'] == $val['id']) {
                    $item['icon'] = $val['icon'];
                }
            }
            $parent_info2 = $rule_table->where(array(
                'id' => $item['pid'],
            ))->find();
            if (!empty($parent_info2['pid'])) {
                $parent_info1 = $rule_table->where(array(
                    'id' => $parent_info2['pid'],
                ))->find();
            }
            $component = $parent_info1['path'] . '/' . $parent_info2['path'] . '/' . $item['path'];
            $item['component'] = $component;
        }
        unset($item);
        return (array)$rule_list;
    }

    /**
     * 商户端-退出登陆
     * @param string $token
     * @return array $data
     * */
    public function loginOut(string $token)
    {
        $where = [];
        $where['token'] = $token;
        $data = M('user_token')->where($where)->delete();
        if ($data) {
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '操作失败');
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取职员详情
     */
    public function getUserInfo($param)
    {
        $userModel = M('user');
        $rs = $userModel->where(['shopId' => $param['shopId'], 'id' => $param['id']])->find();
        if (!empty($rs)) {
            $roleList = M('user_role wur')->where("wur.uid = {$rs['id']} and wur.shopId = {$param['shopId']}")->join('join wst_role wr on wr.id = wur.rid')->field('wur.rid')->select();
            $check_ridArr = array_get_column($roleList, 'rid');
            $userRoleId = implode(',', array_unique($check_ridArr));
            $rs['userRoleId'] = $userRoleId;
        }
        return $rs;
    }

}
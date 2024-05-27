<?php

namespace Merchantapi\Action;
use Home\Model\UserModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员控制器
 */
class UserAction extends BaseAction
{

    /**
     * 管理员获取
     */
    public function getlist()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
//        $m = D('Home/User');
        $m = new UserModel();
        $msg = '';
        $res = $m->getlist($parameter, $msg);
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 获取职员详情
     */
    public function getUserInfo()
    {
        $shopInfo = $this->MemberVeri();
        $param = [];
        $param['shopId'] = $shopInfo['shopId'];
        $param['id'] = I('id', 0);
        $m = D('Home/User');
        $res = $m->getUserInfo($param);
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 编辑职员状态
     */
    public function upUserStatus()
    {
        $shopInfo = $this->MemberVeri();
        $id = I('id', 0);
        $status = (int)I('status');
        if (empty($id)) {
            $res = returnData(false, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($res);
        }
        if (!in_array($status, [0, -2])) {
            $res = returnData(false, -1, 'error', '操作失败', '数据错误');
            $this->ajaxReturn($res);
        }
        $param = [];
        $param['shopId'] = $shopInfo['shopId'];
        $param['id'] = $id;
        $param['status'] = $status;
        $m = D('Home/User');
        $res = $m->upUserStatus($param);
        $this->ajaxReturn($res);
    }

    /**
     * 管理员获取
     */
    public function checkLoginKey()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/User');
        $msg = '';
        $res = $m->checkLoginKey($parameter, $msg);
        if (!$res) {
            $this->returnResponse(-1, $msg ? $msg : '不可使用');
        }
        $this->returnResponse(0, '可使用');
    }

    /**
     * 店铺添加账号
     */
    public function addUser()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];

        if ($parameter['pass'] != $parameter['repass'] || empty($parameter['repass'])) {
            $res = returnData(false, -1, 'error', '两次密码输入的不一致', '数据错误');
            $this->ajaxReturn($res);
        }
        $m = D('Home/User');
        $msg = '';
        $res = $m->addUser($parameter, $msg);
        if ($res['code'] != 0) {
            $msg = $res['msg'];
            $this->returnResponse(-1, $msg ? $msg : '添加失败');
        }
        $this->returnResponse(0, '添加成功');
    }


    /**
     * 编辑账号
     */
    public function edit()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/User');
        $msg = '';
        $res = $m->edit($parameter, $msg);
        if ($res['code'] != 0) {
            $msg = $res['msg'];
            $this->returnResponse(-1, $msg ? $msg : '编辑失败');
        }
        $this->returnResponse(0, '编辑成功');
    }

    /**
     * 删除账号
     */
    public function del()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/User');
        $msg = '';
        $res = $m->del($parameter, $msg);
        if (!$res) {
            $this->returnResponse(-1, $msg ? $msg : '删除失败');
        }
        $this->returnResponse(0, '删除成功');
    }

    /**
     * 根据token获取职员信息
     */
    public function getUserInfoByToken()
    {
        $shopInfo = $this->MemberVeri();

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $shopInfo;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 职员/管理员-获取权限列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mkfkc4
     * */
    public function getLoginUserNodeList()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/User');
        $data = $m->getLoginUserNodeList($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 职员/管理员-设置常用菜单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xepc3p
     * */
    public function settingOftenUsedMenu()
    {
        $loginUserInfo = $this->MemberVeri();
        $id = I('id', '');
        if (empty($id)) {
            $id_arr = array();
        } else {
            $id_arr = explode(',', $id);
        }
        $m = D('Home/User');
        $data = $m->settingOftenUsedMenu($loginUserInfo, $id_arr);
        $this->ajaxReturn($data);
    }

    /**
     * 职员/管理员-获取常用菜单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ovhdzn
     * */
    public function getSettingOftenUsedMenuList()
    {
        $loginUserInfo = $this->MemberVeri();
        $m = D('Home/User');
        $data = $m->getSettingOftenUsedMenuList($loginUserInfo);
        $this->ajaxReturn(returnData($data));
    }

}
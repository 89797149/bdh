<?php
namespace Merchantapi\Action;
use Home\Model\RoleModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员控制器
 */
class RoleAction extends BaseAction {

    /**
     * 获取
     */
    public function getlist(){
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/Role');
        $msg = '';
        $res = $m->getlist($parameter,$msg);
        $this->ajaxReturn(returnData($res));
    }


    /**
     * 添加
     */
    public function add(){
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/Role');
        $msg = '';
        $res = $m->addrole($parameter,$msg);
        $this->ajaxReturn($res);
    }

    /**
     * 编辑角色状态
     */
    public function upRoleStatus(){
        $shopInfo = $this->MemberVeri();
        $id = I('id',0);
        $status = (int)I('status',0);
        if(empty($id) || empty($status)){
            $res= returnData(false,-1,'error','有字段不允许为空','数据错误');
            $this->ajaxReturn($res);
        }
        if(!in_array($status,[1,-1])){
            $res= returnData(false,-1,'error','操作失败','数据错误');
            $this->ajaxReturn($res);
        }
        $param = [];
        $param['shopId'] = $shopInfo['shopId'];
        $param['id'] = $id;
        $param['status'] = $status;
        $m = D('Home/Role');
        $res = $m->upRoleStatus($param);
        $this->ajaxReturn($res);
    }

    /**
     * 编辑账号
     */
    public function edit(){
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = new RoleModel();
        $msg = '';
        $res = $m->edit($parameter,$msg);
        $this->ajaxReturn($res);
    }

    /**
     * 删除账号
     */
    public function del(){
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Home/Role');
        $msg = '';
        $res = $m->del($parameter,$msg);
        $this->ajaxReturn($res);
    }

    /**
     * 获取角色详情
     */
    public function getRoleInfo(){
        $shopInfo = $this->MemberVeri();
        $param = [];
        $param['shopId'] = $shopInfo['shopId'];
        $param['id'] = I('id',0);
        $m = D('Home/Role');
        $res = $m->getRoleInfo($param);
        $this->ajaxReturn(returnData($res));
    }

}
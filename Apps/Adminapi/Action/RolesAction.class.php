<?php

namespace Adminapi\Action;

use Adminapi\Model\RolesModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 权限控制器
 */
class RolesAction extends BaseAction
{
    /**
     * 查看详情
     */
    public function detail()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = new RolesModel();
        $detail = $m->get();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $this->isLogin();
        $m = new RolesModel();
        if (I('id', 0) > 0) {
//    		$rs = $m->edit();//旧
            $rs = $m->editRolesInfo();
        } else {
            $rs = $m->insert();
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $this->isLogin();
        $m = new RolesModel();
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 分配权限
     */
    public function distributionAuth()
    {
        $this->isLogin();
        $roleId = (int)I("id", 0);
        $grant = I("grant");
        if (empty($roleId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择角色'));
        }
        $params = [];
        $params['roleId'] = $roleId;
        $params['grant'] = $grant;
        $m = new RolesModel();
        $rs = $m->distributionAuth($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取角色列表
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new RolesModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->ajaxReturn($list);
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = new RolesModel();
        $list = $m->queryByList();
        $this->returnResponse(0, '操作成功', $list);
    }
}
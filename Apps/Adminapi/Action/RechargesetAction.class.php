<?php

namespace Adminapi\Action;

use Adminapi\Model\RechargesetModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券控制器
 */
class RechargesetAction extends BaseAction
{
    /**
     * 跳到新增/编辑页面
     */
    public function detail()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/Rechargeset');
        $detail = $m->getRechargeset();
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $userData = $this->isLogin();
        $m = new RechargesetModel();
        if (I('id', 0) > 0) {
            $rs = $m->edit($userData);
        } else {
            $rs = $m->insert($userData);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $userData = $this->isLogin();
        $m = new RechargesetModel();
        $rs = $m->del($userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/Rechargeset');
        $list = $m->queryByPage($page, $pageSize);
        $this->returnResponse(0, '操作成功', $list);
    }
}

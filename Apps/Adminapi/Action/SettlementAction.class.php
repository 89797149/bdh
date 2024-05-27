<?php

namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 用户申请入驻
 */
class SettlementAction extends BaseAction
{

    /**
     * 获取用户申请入驻列表
     */
    public function getList()
    {
        $this->isLogin();
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $where['delete'] = 1;
        $data = M("user_settlement")->order("status asc")->where($where)->select();
        $rs = arrayPage($data, $page, $pageSize);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 审核入驻信息
     */
    public function updateSettlement()
    {
        $this->isLogin();
        $id = (int)I("id");
        $status = (int)I("status");
        if (!in_array($status, [0, 1])) {
            $this->ajaxReturn(returnData(false, -1, 'error', "参数有误"));
        }

        $where['id'] = $id;
        $save['status'] = $status;
        $data = M("user_settlement")->where($where)->save($save);
        if ($data) {
            $rs = returnData(true);
        } else {
            $rs = returnData(false, -1, 'error', "操作失败");
        }

        $this->ajaxReturn($rs);
    }

    /**
     * 删除入驻信息
     */
    public function delSettlement()
    {
        $this->isLogin();
        $id = (int)I("id");

        $where['id'] = $id;
        $data = M("user_settlement")->where($where)->delete();

        if ($data) {
            $rs = returnData(true);
        } else {
            $rs = returnData(false, -1, 'error', "删除失败");
        }

        $this->ajaxReturn($rs);
    }

}
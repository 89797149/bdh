<?php

namespace Adminapi\Action;

use Adminapi\Model\AdsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告控制器
 */
class AdsAction extends BaseAction
{
    /**
     * 查看详情
     */
    public function detail()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = new AdsModel();
        $detail = $m->get();
        $this->ajaxReturn(returnData($detail));
    }

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $userData = $this->isLogin();
        $m = new AdsModel();
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
        $m = new AdsModel();
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
        $m = new AdsModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = D('Adminapi/Ads');
        $list = $m->queryByList();
        $this->ajaxReturn(returnData((array)$list));
    }
}
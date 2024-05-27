<?php

namespace Adminapi\Action;

use Adminapi\Model\ArticlesModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 文章控制器
 */
class ArticlesAction extends BaseAction
{
    /**
     * 跳到新增/编辑页面
     */
    public function toEdit()
    {
        $this->isLogin();
        $m = D('Adminapi/Articles');
        $this->checkPrivelege('wzlb_02');
        $object = $m->get();
        $data = array();
        $data['catList'] = D('Adminapi/ArticleCats')->getCatLists();
        $data['object'] = $object;
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 新增/修改文章操作
     */
    public function edit()
    {
//		$this->isLogin();
        $staffId = $this->MemberVeri()['staffId'];
        $m = new ArticlesModel();
        if (I('id', 0) > 0) {
            $this->checkPrivelege('wzlb_02');
            $rs = $m->edit($staffId);
        } else {
            $this->checkPrivelege('wzlb_01');
            $rs = $m->insert($staffId);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $this->isLogin();
        $this->checkPrivelege('wzlb_03');
        $m = D('Adminapi/Articles');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 查看
     */
    public function detail()
    {
        $this->isLogin();
        $this->checkPrivelege('wzlb_00');
        $m = D('Adminapi/Articles');
        $data = $m->get();
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('wzlb_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new ArticlesModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = D('Adminapi/Articles');
        $list = $m->queryByList();
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 显示文章是否显示/隐藏
     */
    public function editiIsShow()
    {
        $this->isLogin();
        $this->checkPrivelege('wzlb_02');
        $m = D('Adminapi/Articles');
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
    }
}
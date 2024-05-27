<?php

namespace Adminapi\Action;

use Adminapi\Model\MenusModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 菜谱
 */
class MenusAction extends BaseAction
{
    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
//        $m = D('Adminapi/Menus');
        $m = new MenusModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->returnResponse(0, '操作成功', $list);
    }

    /**
     * 菜谱详情
     */
    public function detail()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/Menus');
        $detail = $m->getInfo($id);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 菜谱详情
     */
    /*public function detail(){
        $this->isLogin();
        $this->display('/menus/edit');
    }*/

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $this->isLogin();
        $m = D('Adminapi/Menus');
        $param = I();
        //var_dump(objectToArray(json_decode($_POST['step'])));exit;
        isset($param['id']) ? $response['id'] = $param['id'] : false;
        isset($param['catId']) ? $response['catId'] = $param['catId'] : false;
        isset($param['title']) ? $response['title'] = $param['title'] : false;
        isset($param['pic']) ? $response['pic'] = $param['pic'] : false;
        isset($param['content']) ? $response['content'] = $param['content'] : false;
        isset($_POST['ingredient']) ? $response['ingredient'] = objectToArray(json_decode($_POST['ingredient'])) : false; //食材
        isset($_POST['step']) ? $response['step'] = objectToArray(json_decode($_POST['step'])) : false; //食材
        if ($response['id'] > 0) {
            $rs = $m->edit($response);
        } else {
            $response['addTime'] = date('Y-m-d H:i:s', time());
            $rs = $m->insert($response);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $this->isLogin();
        $m = new MenusModel();
        $param = I('');
        isset($param['id']) ? $response['id'] = $param['id'] : false;
        $rs = $m->del($param);
        $this->ajaxReturn($rs);
    }

    /**
     *菜单列表 分页
     */
    public function ajaxMenus()
    {
        $this->isLogin();
        $m = D('Adminapi/Menus');
        $page = (int)I('page', 1);
        $pageSize = I('pageSize', 15, 'intval');
        $object = $m->getList($page, $pageSize);
        $this->ajaxReturn($object);
    }

    /*
     * 菜谱详情
     * @param int $menuId
     * */
    public function menuInfo()
    {
        $this->isLogin();
        $m = D("Adminapi/Menus");
        $menuId = I('menuId', 0);
        if (empty($menuId)) {
            $this->returnResponse(-1, '字段有误');
        }
        $mod = $m->menuInfo($menuId);
        $this->ajaxReturn($mod);
    }

    /**
     * 修改菜谱列表状态为显示/隐藏
     */
    public function editiIsShow()
    {
        $this->isLogin();
        $m = D('Adminapi/Menus');
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
    }
}
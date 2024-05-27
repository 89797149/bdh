<?php

namespace Adminapi\Action;

use Adminapi\Model\MenusCatModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 菜谱分类
 */
class MenusCatAction extends BaseAction
{
    /**
     * 菜谱列表，带分页
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/MenusCat');
        $list = $m->queryByPage($page, $pageSize);
        $this->returnResponse(0, '操作成功', $list);
    }

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
        $m = D('Adminapi/MenusCat');
        $detail = $m->getInfo($id);
        $this->returnResponse(0, '操作成功', $detail);
    }
    /**
     * 新增/修改操作
     */
    /*public function edit(){
        $this->isLogin();
        $m = D('Adminapi/MenusCat');
        $param = I();
        isset($param['id'])?$response['id']=$param['id']:false;
        isset($param['catname'])?$response['catname']=$param['catname']:false;
        if($response['id'] > 0){
            $rs = $m->edit($response);
        }else{
            $response['addTime'] = date('Y-m-d H:i:s',time());
            $rs = $m->insert($response);
        }
        $this->ajaxReturn($rs);
    }*/
    public function edit()
    {
        $this->isLogin();
        $m = D('Adminapi/MenusCat');
        $requestParams = I();
        $params = [];
        $params['id'] = null;
        $params['catname'] = null;
        $params['pic'] = null;
        parm_filter($params, $requestParams);
        if ($params['id'] > 0) {
            $rs = $m->edit($params);
        } else {
            if (empty($params['catname'])) {
                $this->returnResponse(-1, '分类名称不能为空');
            }
            $params['addTime'] = date('Y-m-d H:i:s', time());
            $rs = $m->insert($params);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $this->isLogin();
        $m = new MenusCatModel();
        $param = I('');
        isset($param['id']) ? $response['id'] = $param['id'] : false;
        $rs = $m->del($response);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取菜谱分类
     */
    public function getCatList()
    {
        $this->isLogin();
        $m = new MenusCatModel();
        $params = [];
        $params['catname'] = I('catname');
        parm_filter($params, I(''));
        $rs = $m->getCatList($params);
        $this->ajaxReturn($rs);
    }
}
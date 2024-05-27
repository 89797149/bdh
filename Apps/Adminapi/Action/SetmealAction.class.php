<?php
namespace Adminapi\Action;

use Adminapi\Model\SetmealModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 套餐控制器
 */
class SetmealAction extends BaseAction
{
    /**
     * 查看套餐详情
     */
    public function detail()
    {
        $this->isLogin();
        $this->checkPrivelege('tclb_02');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/Setmeal');
        $detail = $m->getSetmeal();

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/修改套餐操作
     */
    public function edit()
    {
        $userData = $this->isLogin();
        $m = new SetmealModel();
        if (I('id', 0) > 0) {
            $this->checkPrivelege('tclb_02');
            $rs = $m->edit($userData);
        } else {
            $this->checkPrivelege('tclb_01');
            $rs = $m->insert($userData);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除套餐操作
     */
    public function del()
    {
        $userData = $this->isLogin();
        $this->checkPrivelege('tclb_03');
        $m = new SetmealModel();
        $rs = $m->del($userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('tclb_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/Setmeal');
        $m = new SetmealModel();
        $list = $m->queryByPage($page, $pageSize);

        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }
}

;
?>

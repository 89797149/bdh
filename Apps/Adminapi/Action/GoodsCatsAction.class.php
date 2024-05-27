<?php

namespace Adminapi\Action;

use Adminapi\Model\GoodsCatsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品分类控制器
 */
class GoodsCatsAction extends BaseAction
{

    /**
     * 分类详情
     */
    public function detail()
    {
        $this->isLogin();
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = I('id', 0, 'intval');
        if ($id <= 0) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/GoodsCats');
        $this->checkPrivelege('spfl_02');
        $detail = $m->get($id);

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;

        $this->ajaxReturn($rs);
    }

    /**
     * 新增/修改操作
     */
    public function edit()
    {
        $this->isLogin();
        $m = D('Adminapi/GoodsCats');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (I('id', 0) > 0) {
            $this->checkPrivelege('spfl_02');
            $rs = $m->edit();
        } else {
            $this->checkPrivelege('spfl_01');
            $rs = $m->insert();
        }

        $this->ajaxReturn($rs);
    }

    /**
     * 修改名称
     */
    public function editName()
    {
        $this->isLogin();
        $m = D('Adminapi/GoodsCats');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (I('id', 0) > 0) {
            $this->checkPrivelege('spfl_02');
            $rs = $m->editName();
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_03');
        $m = D('Adminapi/GoodsCats');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_00');
        $m = D('Adminapi/GoodsCats');
        $list = $m->getCatAndChild();
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取分类
     * https://www.yuque.com/youzhibu/ruah6u/kdz9vl
     */
    public function getCatslist()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_00');
        $m = new GoodsCatsModel();
        $parentId = (int)I('parentId', 0);
//        $list = $m->getCatAndChild();【旧】
        $list = $m->getCatsList($parentId);
        $this->ajaxReturn(returnData($list));

    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = D('Adminapi/GoodsCats');
        $list = $m->queryByList(I('id'));
        $rs = array();
        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商品分类为显示/隐藏
     */
    public function editiIsShow()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_02');
        $m = new GoodsCatsModel();
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
    }

    /**
     * 修改排序
     * https://www.yuque.com/youzhibu/ruah6u/mtx3qx
     */
    public function editSort()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_02');
        $m = new GoodsCatsModel();
        $id = I('catId', 0, 'intval');
        if ($id <= 0) {
            $rs = returnData(false, -1, 'error', "请选择查看商品");
            $this->ajaxReturn($rs);
        }
        $catSort = (int)I('catSort', 0);

        $params = [];
        $params['catId'] = $id;
        $params['catSort'] = $catSort;
        $rs = $m->editSort($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 修改typeImg 分类图片
     * https://www.yuque.com/youzhibu/ruah6u/bxo3va
     */
    public function editTypeImg()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_02');
        $m = new GoodsCatsModel();
        $id = I('catId', 0, 'intval');
        if ($id <= 0) {
            $rs = returnData(false, -1, 'error', "请选择查看商品");
            $this->ajaxReturn($rs);
        }

        $typeImg = I('typeimg', 0);
        if (empty($typeImg)) {
            $rs = returnData(false, -1, 'error', "请上传分类图片");
            $this->ajaxReturn($rs);
        }

        $params = [];
        $params['catId'] = $id;
        $params['typeimg'] = $typeImg;
        $rs = $m->editTypeImg($params);
        $this->ajaxReturn($rs);
    }


    /**
     * 修改App分类小图标
     * https://www.yuque.com/youzhibu/ruah6u/asa5wm
     */
    public function editAppTypeSmallImg()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_02');
        $m = new GoodsCatsModel();
        $id = I('catId', 0, 'intval');
        if ($id <= 0) {
            $rs = returnData(false, -1, 'error', "请选择查看商品");
            $this->ajaxReturn($rs);
        }

        $appTypeSmallImg = I('appTypeSmallImg', 0);
        if (empty($appTypeSmallImg)) {
            $rs = returnData(false, -1, 'error', "请上传分类小图标");
            $this->ajaxReturn($rs);
        }

        $params = [];
        $params['catId'] = $id;
        $params['appTypeSmallImg'] = $appTypeSmallImg;
        $rs = $m->editAppTypeSmallImg($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 修改首页显示状态
     * https://www.yuque.com/youzhibu/ruah6u/wfd4rr
     */
    public function editIsShowIndexType()
    {
        $this->isLogin();
        $this->checkPrivelege('spfl_02');
        $m = new GoodsCatsModel();
        $id = I('catId', 0, 'intval');
        if ($id <= 0) {
            $rs = returnData(false, -1, 'error', "请选择操作商品");
            $this->ajaxReturn($rs);
        }

        $isShowIndex = I('isShowIndex', 0, 'intval');//是否显示在首页（0：否 1：是）
        if (!in_array($isShowIndex,[0,1])) {
            $rs = returnData(false, -1, 'error', "请选择正确的状态");
            $this->ajaxReturn($rs);
        }

        $params = [];
        $params['catId'] = $id;
        $params['isShowIndex'] = $isShowIndex;
        $rs = $m->editIsShowIndexType($params);
        $this->ajaxReturn($rs);
    }
}
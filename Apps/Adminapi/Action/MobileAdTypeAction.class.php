<?php

namespace Adminapi\Action;
use Adminapi\Model\MobileAdTypeModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告类型控制器
 */
class MobileAdTypeAction extends BaseAction
{
    /**
     * 新增|编辑广告分类信息
     */
    public function addAdTypeInfo()
    {
        $userData = $this->isLogin();
        $m = new MobileAdTypeModel();
        if (I('adTypeId', 0) > 0) {
            $rs = $m->edit($userData);
        } else {
            $rs = $m->insert($userData);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除广告信息
     */
    public function delAdTypeInfo()
    {
        $userData = $this->isLogin();
        $m = new MobileAdTypeModel();
        $rs = $m->del($userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取列表分页
     */
    public function getAdTypeList()
    {
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Adminapi/MobileAdType');
        $list = $m->getAdTypeList($page, $pageSize);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告分类详情
     */
    public function getAdTypeDetail()
    {
        $this->isLogin();
        $adTypeId = I('adTypeId', 0, 'intval');
        if (empty($adTypeId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/MobileAdType');
        $detail = $m->getAdTypeDetail($adTypeId);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 获取列表无分页
     */
    public function getAdType()
    {
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $m = D('Adminapi/MobileAdType');
        $list = $m->getAdType();
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/编辑广告位置信息
     */
    public function addAdLocationInfo()
    {
        $userData = $this->isLogin();
        $m = new MobileAdTypeModel();
        if (I('adLocationId', 0) > 0) {
            $rs = $m->editAdLocationInfo($userData);
        } else {
            $rs = $m->addAdLocationInfo($userData);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置详情
     */
    public function getAdLocationDetail()
    {
        $this->isLogin();
        $adLocationId = I('adLocationId', 0, 'intval');
        if (empty($adLocationId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/MobileAdType');
        $detail = $m->getAdLocationDetail($adLocationId);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 删除广告位置信息
     */
    public function delAdLocationInfo()
    {
        $userData = $this->isLogin();
        $m = new MobileAdTypeModel();
        $rs = $m->delAdLocationInfo($userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置列表分页
     */
    public function getAdLocationList()
    {
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Adminapi/MobileAdType');
        $list = $m->getAdLocationList($page, $pageSize);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置列表无分页
     */
    public function getAdLocation()
    {
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $m = D('Adminapi/MobileAdType');
        $list = $m->getAdLocation();
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }
}
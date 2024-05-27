<?php

namespace Merchantapi\Action;
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
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAdType');
        $data = array();
        if (I('adTypeId', 0) > 0) {
            $data = $m->editAdTypeInfo($shopId);
        } else {
            $data = $m->insertAdTypeInfo($shopId);
        }
        $this->ajaxReturn($data);
    }

    /**
     * 删除广告信息
     */
    public function delAdTypeInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAdType');
        $rs = $m->delAdTypeInfo($shopId);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告分类
     */
    public function getAdTypeList()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Merchantapi/MobileAdType');
        $list = $m->getAdTypeList($page, $pageSize, $shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告分类详情
     */
    public function getAdTypeDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $adTypeId = I('adTypeId', 0, 'intval');
        if (empty($adTypeId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Merchantapi/MobileAdType');
        $detail = $m->getAdTypeDetail($adTypeId, $shopId);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 获取广告分类无分页
     */
    public function getAdType()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $m = D('Merchantapi/MobileAdType');
        $list = $m->getAdType($shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/编辑广告位置信息
     */
    public function addAdLocationInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAdType');
        $rs = array();
        if (I('adLocationId', 0) > 0) {
            $rs = $m->editAdLocationInfo($shopId);
        } else {
            $rs = $m->addAdLocationInfo($shopId);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置详情
     */
    public function getAdLocationDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $adLocationId = I('adLocationId', 0, 'intval');
        if (empty($adLocationId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Merchantapi/MobileAdType');
        $detail = $m->getAdLocationDetail($adLocationId, $shopId);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 删除广告位置信息
     */
    public function delAdLocationInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAdType');
        $rs = $m->delAdLocationInfo($shopId);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置列表分页
     */
    public function getAdLocationList()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Merchantapi/MobileAdType');
        $list = $m->getAdLocationList($page, $pageSize, $shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取广告位置列表无分页
     */
    public function getAdLocation()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $m = D('Merchantapi/MobileAdType');
        $list = $m->getAdLocation($shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }
}
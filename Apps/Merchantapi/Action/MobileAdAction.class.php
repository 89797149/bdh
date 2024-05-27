<?php

namespace Merchantapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告控制器
 */
class MobileAdAction extends BaseAction
{
    /**
     * 新增|编辑广告信息
     */
    public function addAdInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAd');
        $requestParams = I();
        if(empty($requestParams['adTitle']) || empty($requestParams['adImage']) || empty($requestParams['adTypeId']) || empty($requestParams['adLocationId'])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if ($requestParams['adId'] > 0 ) {
            $rs = $m->editAdInfo($shopId,$requestParams);
        } else {
            $rs = $m->insertAdInfo($shopId,$requestParams);
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除广告信息
     */
    public function delAdInfo()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/MobileAd');
        $rs = $m->delAdInfo($shopId);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取列表分页
     */
    public function getAdList()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Merchantapi/MobileAd');
        $list = $m->getAdList($page, $pageSize, $shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取指定信息
     */
    public function getAdDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $adId = I('adId', 0, 'intval');
        if (empty($adId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Merchantapi/MobileAd');
        $detail = $m->getAdDetail($adId, $shopId);
        $this->returnResponse(0, '操作成功', $detail);
    }
}
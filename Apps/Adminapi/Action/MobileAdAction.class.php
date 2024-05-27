<?php

namespace Adminapi\Action;
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
        $this->isLogin();
        $m = D('Adminapi/MobileAd');
        $rs = array();
        if (I('adId', 0) > 0) {
            $rs = $m->edit();
        } else {
            $rs = $m->insert();
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除广告信息
     */
    public function delAdInfo()
    {
        $this->isLogin();
        $m = D('Adminapi/MobileAd');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 获取列表分页
     */
    public function getAdList()
    {
        $this->isLogin();
        $this->checkPrivelege('ppgl_00');

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = D('Adminapi/MobileAd');
        $list = $m->getAdList($page, $pageSize);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取指定信息
     */
    public function getAdDetail()
    {
        $this->isLogin();
        $adId = I('adId', 0, 'intval');
        if (empty($adId)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/MobileAd');
        $detail = $m->getAdDetail($adId);
        $this->returnResponse(0, '操作成功', $detail);
    }
}
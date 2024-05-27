<?php

namespace Adminapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 限量购控制器
 */
class GoodsCountSnappedAction extends BaseAction
{
    /**
     * 获取限量购商品列表
     */
    public function getGoodsCountSnappedList()
    {
        $this->isLogin();
        $m = D('Adminapi/GoodsCountSnapped');
        $list = $m->getGoodsCountSnappedList();
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 新增限量购商品
     */
    public function addGoodsCountSnapped()
    {
        $this->isLogin();
        $goodsInfo = I('goodsInfo','','trim');
        $goodsInfo = json_decode($goodsInfo,true);
        if (empty($goodsInfo)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = D('Adminapi/GoodsCountSnapped');
        $data = $m->addGoodsCountSnapped($goodsInfo);
        $this->ajaxReturn($data);
    }

    /**
     * 修改限量购商品
     */
    public function editGoodsCountSnapped()
    {
        $this->isLogin();
        $csId = I('csId');
        $marketPrice = I('marketPrice');
        $activityPrice = I('activityPrice');
        $minBuyNum = I('minBuyNum');
        $salesInventory = I('salesInventory');
        if (empty($csId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        if (empty($marketPrice) && empty($activityPrice) && empty($minBuyNum) && empty($salesInventory)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = D('Adminapi/GoodsCountSnapped');
        $data = $m->editGoodsCountSnapped();
        $this->ajaxReturn($data);
    }

    /**
     * 删除限量购商品
     */
    public function deleteGoodsCountSnapped()
    {
        $this->isLogin();
        $csId = I('csId');

        if (empty($csId)) {
            $retdata = returnData(null, -1, 'error', '参数有误', '数据错误');
            $this->ajaxReturn($retdata);
        }

        $m = D('Adminapi/GoodsCountSnapped');
        $data = $m->deleteGoodsCountSnapped($csId);
        $this->ajaxReturn($data);
    }
}
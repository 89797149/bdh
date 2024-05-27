<?php

namespace Merchantapi\Action;
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
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = 1;
        $m = D('Merchantapi/GoodsCountSnapped');
        $list = $m->getGoodsCountSnappedList($shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 新增限量购商品
     */
    public function addGoodsCountSnapped()
    {
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = 1;
        $goodsInfo = I('goodsInfo', '', 'trim');
        $goodsInfo = json_decode($goodsInfo, true);
        if (empty($goodsInfo)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = D('Merchantapi/GoodsCountSnapped');
        $data = $m->addGoodsCountSnapped($goodsInfo, $shopId);
        $this->ajaxReturn($data);
    }

    /**
     * 修改限量购商品
     */
    public function editGoodsCountSnapped()
    {
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = 1;
        $goodsId = I('goodsId');
        $marketPrice = I('marketPrice');//市场价
        $limitCountActivityPrice = I('limitCountActivityPrice');//限量活动价
        $minBuyNum = I('minBuyNum');//最小起订量
        $saleCount = I('saleCount');//销售量
        $limitCount = I('limitCount');//限量库存
//        $isSale = I('isSale');//是否上架(0:不上架 1:上架)
        if (empty($goodsId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        if (empty($marketPrice) && empty($limitCountActivityPrice) && empty($minBuyNum) && empty($saleCount) && empty($limitCount)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = D('Merchantapi/GoodsCountSnapped');
        $data = $m->editGoodsCountSnapped($shopId);
        $this->ajaxReturn($data);
    }

    /**
     * 删除限量购商品
     */
    public function deleteGoodsCountSnapped()
    {
        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = 1;
        $goodsId= I('goodsId');

        if (empty($goodsId)) {
            $retdata = returnData(null, -1, 'error', '参数有误', '数据错误');
            $this->ajaxReturn($retdata);
        }

        $m = D('Merchantapi/GoodsCountSnapped');
        $data = $m->deleteGoodsCountSnapped($goodsId, $shopId);
        $this->ajaxReturn($data);
    }
}
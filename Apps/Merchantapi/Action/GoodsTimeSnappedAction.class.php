<?php

namespace Merchantapi\Action;

use Merchantapi\Model\GoodsTimeSnappedModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 限时抢购控制器
 */
class GoodsTimeSnappedAction extends BaseAction
{
    /**
     * 获取已售商品列表
     */

    /**
     * 获取时间段
     */
    public function getLimitedTimeList()
    {
        $shopId = $this->MemberVeri()['shopId'];

        if (empty($shopId)) {
            $retData = returnData(null, -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }

        $model = new GoodsTimeSnappedModel();
        $list = $model->getLimitedTimeList($shopId);
        $rs = array('code' => 0, 'msg' => '操作成功', 'data' => $list);
        $this->ajaxReturn($rs);
    }


    /**
     * 添加限时抢购
     */
    public function addLimitedTimeSale()
    {
        $shopId = $this->MemberVeri()['shopId'];

        if (empty($shopId)) {
            $retData = returnData(null, -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }

        $goodsInfo = I('goodsInfo', '', 'trim');
        $goodsInfo = json_decode($goodsInfo, true);

        if (empty($goodsInfo)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '活动商品不可以为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $model = new GoodsTimeSnappedModel();
        $result = $model->addLimitedTimeSales($goodsInfo, $shopId);
        $this->ajaxReturn($result);
    }

    /**
     * 限时抢购列表
     */
    public function getLimitedTimeSaleList()
    {
        $post['flashSaleId'] = (int)I("flashSaleId");
        $post['shopId'] = $this->MemberVeri()['shopId'];

        if (empty($post['shopId'])) {
            $retData = returnData(null, -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }

        $model = new GoodsTimeSnappedModel();
        $data = $model->getLimitedTimeSaleList($post);
        if ($data) {
            $retData = returnData($data);
        } else {
            $retData = returnData($data, -1, 'error', '获取失败', '数据错误');
        }
        $this->ajaxReturn($retData);
    }

    /**
     * 限时抢购删除
     */
    public function delLimitedTimeSale()
    {
        $post['tsId'] = I('tsId');
        $post['shopId'] = $this->MemberVeri()['shopId'];

        if (empty($post['shopId'])) {
            $retData = returnData(null, -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }

        $model = D('Merchantapi/GoodsTimeSnapped');
        $data = $model->delLimitedTimeSale($post);
        if ($data) {
            $retData = returnData(true, 0, 'success', '操作成功');
        } else {
            $retData = returnData(false, -1, 'error', '删除失败', '数据错误');
        }
        $this->ajaxReturn($retData);
    }

    /**
     * 编辑限时抢购
     */
    public function updateLimitedTimeSale()
    {
        $tsId = I('tsId', 0);
        if ($tsId == 0) {
            $retData = returnData(null, -1, 'error', '获取失败', '传参不正确');
            return $retData;
        }
        $post['shopId'] = $this->MemberVeri()['shopId'];
//        $post['shopId'] = 1;
        if (empty($post['shopId'])) {
            $retData = returnData(null, -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }


        $post = [];
        $post['tsId'] = $tsId;
        $marketPrice = I('marketPrice');
        if (!empty($marketPrice)) {//市场价格
            $post['marketPrice'] = $marketPrice;
        }
        $activityPrice = I('activityPrice');
        if (!empty($activityPrice)) {//活动价格
            $post['activityPrice'] = $activityPrice;
        }
        $minBuyNum = I('minBuyNum');
        if (!empty($minBuyNum)) {//起订量
            $post['minBuyNum'] = $minBuyNum;
        }
        $salesInventory = I('salesInventory');
        if (!empty($salesInventory)) {//已售库存
            $post['salesInventory'] = $salesInventory;
        }
        $activeInventory = I('activeInventory');
//        if (!empty($activeInventory)) {//活动库存
//            $post['activeInventory'] = $activeInventory;
//        }
        $post['activeInventory'] = (float)$activeInventory;

        $model = D('Merchantapi/GoodsTimeSnapped');
        $data = $model->updateLimitedTimeSale($post);
        $this->ajaxReturn($data);
    }

    /*
     * 获取出售商品列表
     */

    public function getSaleItemsList()
    {
        $params = I();

        $params['shopId'] = $this->MemberVeri()['shopId'];
        if (empty($params['shopId'])) {
            $retData = returnData($params['shopId'], -1, 'error', '商户ID不合法');
            $this->ajaxReturn($retData);
        }

        $model = D('Merchantapi/GoodsTimeSnapped');
        $list = $model->getSellGoodsList($params);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

}


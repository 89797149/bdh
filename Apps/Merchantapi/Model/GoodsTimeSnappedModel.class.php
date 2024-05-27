<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 限时抢购服务类
 */
class GoodsTimeSnappedModel extends BaseModel
{
    /**
     * 新增限时抢购商品
     */
    public function addLimitedTimeSales($goodsInfo, $shopId)
    {
        $flashSaleId = (int)I("flashSaleId", 0);
        if (empty($flashSaleId)) {
            $result = array('code' => -1, 'msg' => '时间段信息不可以为空', 'data' => array());
            return $result;
        }

        $goodsTimeSnapped = M('goods_time_snapped');
        //入库前筛选判断录入数据
        $addData = [];
        foreach ($goodsInfo as $key => &$val) {
            $where = [];
            $where['gts.dataFlag'] = 1;
            $where['wg.goodsId'] = $val['goodsId'];
            $where['gts.flashSaleId'] = $flashSaleId;

            $dateInfo = $goodsTimeSnapped
                ->alias('gts')
                ->join('left join wst_goods wg ON wg.goodsId = gts.goodsId')
                ->where($where)
                ->find();
            if (!empty($dateInfo)) {
                $msg = "商品:" . $dateInfo['goodsName'] . "已添加.";
                return ['code' => -1, 'msg' => $msg . '请勿重复添加'];
            }

            $conditon = [];
            $conditon['goodsId'] = $val['goodsId'];
            $date = M('goods')->where($conditon)->find();
            if ($date['isLimitBuy'] == 1) {
                return ['code' => -1, 'msg' => $date['goodsName'] . '已参与限量商品，不能再参与限时商品'];
            }

//            if (floatval($date['goodsStock']) <= 0.00) {
//                return ['code' => -1, 'msg' => $date['goodsName'] . '的商品库存已不足'];
//            }

            if (intval($val['minBuyNum']) == 0) {
                return ['code' => -1, 'msg' => $date['goodsName'] . '未设置最小起订量'];
            }

//            if (intval($val['activeInventory']) == 0) {
//                return ['code' => -1, 'msg' => $date['goodsName'] . '的活动抢购库存要大于0'];
//            }
            if (floatval($val['activityPrice']) > floatval($val['marketPrice'])) {
                return ['code' => -1, 'msg' => $date['goodsName'] . '的活动价格不得高于市场价格'];
            }

            if ($shopId != 0) {
                $val['shopId'] = $shopId;
            }
            $addData[$key]['goodsId'] = $val['goodsId'];
            $addData[$key]['shopId'] = $shopId;
            $addData[$key]['marketPrice'] = $val['marketPrice'];
            $addData[$key]['activityPrice'] = $val['activityPrice'];
            $addData[$key]['minBuyNum'] = $val['minBuyNum'];
            $addData[$key]['activeInventory'] = $val['activeInventory'];
            $addData[$key]['flashSaleId'] = $flashSaleId; //限时时间
            $addData[$key]['createTime'] = date("y-m-d H:i:s", time()); //添加时间


        }

        //添加事务处理 - 针对Goods批量更新库存
        M()->startTrans();
        foreach ($addData as $key => $val) {
//            $goodsInfo = M('goods')->where(array('goodsId' => $val['goodsId']))->field('goodsStock,goodsName')->lock(true)->find();
//            $activeInventory = floatval($goodsInfo['goodsStock']) - floatval($val['activeInventory']);
//            if ($activeInventory < 0) {
//                return ['code' => -1, 'msg' => $goodsInfo['goodsName'] . '的活动库存不得超出总库存'];
//            }
            $setField = array('isFlashSale' => 1, 'memberPrice' => 0);
//                $result = M('goods')->where(array('goodsId'=>$val['goodsId']))->setField('goodsStock',$activeInventory);
            $result = M('goods')->where(array('goodsId' => $val['goodsId']))->setField($setField);
            if ($result === false) {
                return ['code' => -1, 'msg' => $goodsInfo['goodsName'] . '的库存数据更新失败！'];
            }
        }
        $data = $goodsTimeSnapped->addAll($addData);
        if ($data) {
            M()->commit();
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        } else {
            M()->rollback();
            return ['code' => -1, 'msg' => '操作失败'];
        }
        return $rd;
    }

    /**
     * 获取限时抢购列表
     */
    public function getLimitedTimeSaleList($post)
    {
        $where = [];
        $where['gts.dataFlag'] = 1;
        $where['wg.goodsFlag'] = 1;
        $where['wg.goodsStatus'] = 1;
        $where['gts.shopId'] = $post['shopId'];
        if (!empty($post['flashSaleId'])) {
            $where['gts.flashSaleId'] = $post['flashSaleId'];
        }

        //活动库存
        $activeInventory = I('activeInventory');
        if (!empty($activeInventory)) {
            $where['gts.activeInventory'] = $activeInventory;
        }
        //商品名称
        $goodsName = WSTAddslashes(I('goodsName'));
        if (!empty($goodsName)) {
            $where['wg.goodsName'] = array('like', '%' . $goodsName . '%');
        }
        //编码
        $goodsSn = WSTAddslashes(I('goodsSn'));
        if (!empty($goodsSn)) {
            $where['wg.goodsSn'] = array('like', '%' . $goodsSn . '%');
        }
        //顶级商品分类ID
        $goodsCatId1 = I('goodsCatId1');
        if (!empty($goodsCatId1)) {
            $where['wg.goodsCatId1'] = $goodsCatId1;
        }
        //第二级商品分类ID
        $goodsCatId2 = I('goodsCatId2');
        if (!empty($goodsCatId2)) {
            $where['wg.goodsCatId2'] = $goodsCatId2;
        }
        //第三级商品分类ID
        $goodsCatId3 = I('goodsCatId3');
        if (!empty($goodsCatId3)) {
            $where['wg.goodsCatId3'] = $goodsCatId3;
        }

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $goodsTimeSnapped = M('goods_time_snapped');
        $field = 'fs.id,fs.startTime,fs.endTime,';
        $field .= 'gts.tsId,gts.flashSaleId,gts.activeInventory,gts.dataFlag,wg.goodsName,gts.marketPrice,gts.activityPrice,gts.minBuyNum,gts.createTime,wg.goodsStock';
        $goodsTimeSnapped
            ->alias('gts')
            ->join('left join wst_goods wg ON wg.goodsId = gts.goodsId')
            ->join('left join wst_flash_sale fs ON fs.id = gts.flashSaleId')
            ->where($where)
            ->field($field)
            ->group('gts.tsId')
            ->order('gts.tsId desc')
            ->select();
        $sql = $goodsTimeSnapped->getLastSql();
        $resList = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($resList['root'])) {
            foreach ($resList['root'] as $key => &$value) {
                $value['time'] = $value['startTime'] . ' 至 ' . $value['endTime'];
            }
        }
        return $resList;
    }

    /**
     * 删除显示抢购的商品信息
     */
    public function delLimitedTimeSale($post)
    {
        $where['tsId'] = $post['tsId'];
        if ($post['shopId'] > 0) {
            $where['shopId'] = $post['shopId'];
        }
        //添加事务处理 - 删除活动商品返还总库存
        M()->startTrans();
        $goodsTimeSnapped = M('goods_time_snapped');
        $save['dataFlag'] = -1;
        $result = $goodsTimeSnapped->where($where)->save($save);

        $goodsTimeSnappedInfo = $goodsTimeSnapped->where($where)->lock(true)->find();
//        $addGoodActiveRes = M('goods')->where(['goodsId' => $goodsTimeSnappedInfo['goodsId']])->setInc('goodsStock', floatval($goodsTimeSnappedInfo['activeInventory']));
//        if ($addGoodActiveRes === false) {
//            M()->rollback();
//            return false;
//        }

        //查看限时购---对应商品是否存在
        $whereInfo = [];
        $whereInfo['goodsId'] = $goodsTimeSnappedInfo['goodsId'];
        $whereInfo['dataFlag'] = 1;
        $goodsTimeInfo = $goodsTimeSnapped->where($whereInfo)->select();
        if (count($goodsTimeInfo) <= 0) {
            $saveInfo = [];
            $saveInfo['goodsId'] = $goodsTimeSnappedInfo['goodsId'];
            $saveInfo['goodsFlag'] = 1;
            $saveGoodsInfo = M('goods')->where($saveInfo)->save(['isFlashSale' => 0]);
            if ($saveGoodsInfo === false) {
                M()->rollback();
            }
        }
//        if ($result !== false && $addGoodActiveRes !== false) {
        if ($result !== false) {
            M()->commit();
//            return $result;
            return true;
        } else {
            M()->rollback();
            return false;
        }
    }

    /**
     * @param $post
     * @return array
     * 更新编辑信息
     */
    public function updateLimitedTimeSale($post)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $where = [];
        $where['dataFlag'] = 1;
        $where['tsId'] = $post['tsId'];
        $goodsInfo = M('goods_time_snapped')->where($where)->find();
        if (empty($goodsInfo)) {
            $rd['msg'] = "请查看商品是否存在";
            return $rd;
        }
        //活动价
//        if (!empty($post['activityPrice'])) {
//            if ($post['activityPrice'] >= $goodsInfo['marketPrice']) {
//                $rd['msg'] = "活动价不能大于或等于市场价";
//                return $rd;
//            }
//        }
//        //最小起订量
//        if (!empty($post['minBuyNum'])) {
//            if ($post['minBuyNum'] >= $goodsInfo['activeInventory']) {
//                $rd['msg'] = "最小起订量不能大于活动库存";
//                return $rd;
//            }
//        }
        if ($post['activityPrice'] >= $post['marketPrice']) {
            $rd['msg'] = "活动价不能大于或等于市场价";
            return $rd;
        }
        //最小起订量
        if ($post['minBuyNum'] > $post['activeInventory']) {
            $rd['msg'] = "最小起订量不能大于活动库存";
            return $rd;
        }
//        //活动库存
//        if (!empty($post['activeInventory'])) {
//            $whereInfo = [];
//            $whereInfo['goodsId'] = $goodsInfo['goodsId'];
//            $whereInfo['goodsFlag'] = 1;
//            $whereInfo['isFlashSale'] = 1;
//            $goods = M('goods');
//            $goodsInfoSnapped = $goods->where($whereInfo)->find();
//            $limitCount = $goodsInfoSnapped['goodsStock'] - ($post['activeInventory'] - $goodsInfo['activeInventory']);
//            if ($limitCount < 0) {
//                $rd['msg'] = "请查看当前商品库存是否充足";
//                return $rd;
//            }
//            //变更商品信息
//            $saveGoods = [];
//            $saveGoods['goodsStock'] = $limitCount;
//            $saveGoods['memberPrice'] = 0;
//            $saveGoods['isFlashSale'] = 1;
//            $goods->where($whereInfo)->save($saveGoods);
//        }
        $result = M('goods_time_snapped')->where($where)->save($post);
        if ($result !== false) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param $shopId
     * @return array
     * 时间段列表
     */
    public function getLimitedTimeList($shopId)
    {
        $where = [];
        $where['state'] = 1; // 0:不显示 1:显示
        $where['isDelete'] = 0;
        $where['shopId'] = $shopId;
        $field = 'id,startTime,endTime';
        $list = M('flash_sale')->where($where)->field($field)->order('startTime asc')->select();
        $res = [];
        foreach ($list as $key => $value) {
            $res[$key]['id'] = $value['id'];
            $res[$key]['time'] = $value['startTime'] . ' 至 ' . $value['endTime'];
        }
        return $res;
    }

    /**
     * 获取商品列表
     */
    public function getSellGoodsList($params)
    {
        $shopId = $params['shopId'];
        $flashSaleId = (int)I("flashSaleId");

        $where = [];
        $where['g.shopId'] = $shopId;
        $where['g.goodsFlag'] = 1;
        $goodsName = WSTAddslashes(I('goodsName'));
        if (!empty($goodsName)) {
            $where['g.goodsName'] = array('like', '%' . $goodsName . '%');
        }
        $shopCatId1 = (int)$params['shopCatId1'];
        if ($shopCatId1 > 0) {
            $where['g.shopCatId1'] = $shopCatId1;
        }
        $shopCatId2 = (int)$params['shopCatId2'];
        if ($shopCatId2 > 0) {
            $where['g.shopCatId2'] = $shopCatId2;
        }
        $goodsCatId1 = (int)$params['goodsCatId1'];
        if ($goodsCatId1 > 0) {
            $where['g.goodsCatId1'] = $goodsCatId1;
        }
        $goodsCatId2 = (int)$params['goodsCatId2'];
        if ($goodsCatId2 > 0) {
            $where['g.goodsCatId2'] = $goodsCatId2;
        }
        $goodsCatId3 = (int)$params['goodsCatId3'];
        if ($goodsCatId3 > 0) {
            $where['g.goodsCatId3'] = $goodsCatId3;
        }

//        $condition = ' isFlashSale = 1 or  isLimitBuy = 1 ';
        $where['isLimitBuy'] = array('eq', 0);
        $where['isBecyclebin'] = 0;
        $field = "g.goodsId,g.goodsName,g.marketPrice,g.minBuyNum,g.goodsStock";
        $goodsList = M('goods')->alias('g')->where($where)->field($field)->order('g.shopGoodsSort desc')->select();

        $snappedUpList = M('goods_time_snapped')->where(['flashSaleId' => $flashSaleId, 'shopId' => $shopId, 'dataFlag' => 1])->field(['goodsId'])->select();
        $ids = array_column($snappedUpList, 'goodsId');
        $res = [];

        foreach ($goodsList as $k => &$v) {
            if (!in_array($v['goodsId'], $ids)) {
                $v['activityPrice'] = 0.00;
                $v['salesInventory'] = 0;
                $v['activeInventory'] = 0.00;
                $res[] = $v;
            }
        }

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $count = count($res);
        $pageData = array_slice($res, ($page - 1) * $pageSize, $pageSize);
        $pager['total'] = $count;
        $pager['pageSize'] = $pageSize;
        $pager['start'] = ($page - 1) * $pageSize;
        $pager['root'] = $pageData;
        $pager['totalPage'] = ($pager['total'] % $pageSize == 0) ? ($pager['total'] / $pageSize) : (intval($pager['total'] / $pageSize) + 1);
        $pager['currPage'] = $page;

        return $pager;
    }
}


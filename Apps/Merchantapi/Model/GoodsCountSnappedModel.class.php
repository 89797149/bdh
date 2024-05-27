<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 限量购类
 */
class GoodsCountSnappedModel extends BaseModel
{
    /**
     * @param $shopId
     * @return array
     * 获取限量购商品列表
     */
    public function getGoodsCountSnappedList($shopId)
    {
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        //商品id-商品名称-市场价-限量活动价-最小起订量-销售量-限量库存-总库存
        $field = "goodsId,goodsName,marketPrice,limitCountActivityPrice,minBuyNum,saleCount,limitCount,goodsStock,isSale,limit_daily";
        $sql = "select {$field} from wst_goods where isLimitBuy = 1 and goodsFlag = 1 and shopId = " . $shopId;
        //活动库存
        if (I('limitCount') != '') {
            $sql .= " and limitCount = " . I('limitCount');
        }
        //商品名称
        if (I('goodsName') != '') {
            $sql .= " and goodsName like '%" . WSTAddslashes(I('goodsName')) . "%'";
        }
        //编码
        if (I('goodsSn') != '') {
            $sql .= " and goodsSn like '%" . WSTAddslashes(I('goodsSn')) . "%'";
        }
        //顶级商品分类ID
        if (I('goodsCatId1') != '') {
            $sql .= " and goodsCatId1 = " . I('goodsCatId1');
        }
        //第二级商品分类ID
        if (I('goodsCatId2') != '') {
            $sql .= " and goodsCatId2 = " . I('goodsCatId2');
        }
        //第三级商品分类ID
        if (I('goodsCatId3') != '') {
            $sql .= " and goodsCatId3 = " . I('goodsCatId3');
        }
        $sql .= ' order by goodsId desc';
        return $this->pageQuery($sql, (int)$page, (int)$pageSize);
    }


    /**
     * @param $goodsId
     * @param $shopId
     * @return array
     * 删除限量购商品
     */
    public function deleteGoodsCountSnapped($goodsId, $shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $sql = "UPDATE wst_goods SET limitCount = 0 , isLimitBuy = 0, limit_daily = -1 WHERE goodsId = {$goodsId} and shopId = {$shopId} and isLimitBuy = 1 and goodsFlag = 1";
        $data = $this->execute($sql);
        if ($data) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param $goodsInfo
     * @param $shopId
     * @return array
     * 新增限量购商品
     */
    public function addGoodsCountSnapped($goodsInfo, $shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $goods = M('goods');
        $goodsIds = [];
        $res = [];
        $rest = [];
        foreach ($goodsInfo as $k => $v) {
            if (isset($v['limit_daily'])) {//主要为了兼容之前的程序能够正常运行
                $v['limit_daily'] = (float)$v['limit_daily'];
            }
            $goodsIds[] = $v['goodsId'];
            $goodsDate = $goods->where("shopId = {$shopId} and goodsFlag = 1 and goodsId = " . $v['goodsId'])->find();
            if (empty($goodsDate)) {
                $rd['msg'] = "请查看商品是否存在";
                return $rd;
            }
            if ($goodsDate['isFlashSale'] == 1) {
                $rd['msg'] = $goodsDate['goodsName'] . "已参与限时商品，不能再参与限量商品";
                return $rd;
            }
            if ($goodsDate['isLimitBuy'] == 1) {
                $rd['msg'] = "商品:" . $goodsDate['goodsName'] . "已添加";
                return $rd;
            }
//            if ($v['limitCount'] > $goodsDate['goodsStock'] || $goodsDate['goodsStock'] <= 0) {
//                $rd['msg'] = "商品:" . $goodsDate['goodsName'] . "活动库存超出了您的当前商品库存";
//                return $rd;
//            }
//            $goodsStock = $goodsDate['goodsStock'] - $v['limitCount'];
//            if ($goodsStock < 0) {
//                $rd['msg'] = "请检查商品:" . $goodsDate['goodsName'] . "的当前商品库存";
//                return $rd;
//            }
            if ($v['minBuyNum'] >= $v['limitCount'] && $v['minBuyNum'] != -1) {
                $rd['msg'] = "商品:" . $goodsDate['goodsName'] . "最小起订量不能大于活动库存";
                return $rd;
            }
            if ($v['marketPrice'] < $v['limitCountActivityPrice']) {
                $rd['msg'] = "商品:" . $goodsDate['goodsName'] . "市场价必须大于活动价";
                return $rd;
            }

            //limit_daily每日限购量
            if (isset($v['limit_daily'])) {//主要为了兼容之前的程序能够正常运行
                if ($v['limit_daily'] != -1 && $v['limit_daily'] <= 0) {
                    $rd['msg'] = "商品:" . $goodsDate['goodsName'] . "每日限购量必须大于0";
                    return $rd;
                }
            }
            $res['goodsId'] = $v['goodsId'];
            $res['marketPrice'] = $v['marketPrice'];//市场价
            $res['limitCountActivityPrice'] = $v['limitCountActivityPrice'];//限量活动价
            $res['minBuyNum'] = $v['minBuyNum'];//最小起订量
            $res['saleCount'] = $v['saleCount'];//销售量
            $res['limitCount'] = $v['limitCount'];//限量库存
//            $res['goodsStock'] = $goodsStock;//总库存
            $res['isLimitBuy'] = 1;//是否限量购(0=>否,1=>是)
            $res['memberPrice'] = 0;//会员价
            $res['limit_daily'] = isset($v['limit_daily']) ? $v['limit_daily'] : '-1';//每日限购量
            $rest[] = $res;
        }
        $data = $this->saveAll($rest, 'wst_goods', 'goodsId');
        if ($data) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param $datas [更新数据] [array]
     * @param $table_name [表名]
     * @param $pk
     * @param string $andWhere
     * @return mixed
     */
    public function saveAll($datas, $table_name, $pk, $andWhere = "1=1")
    {
        ini_set('memory_limit', '100M');
        $sql = ''; //Sql
        $lists = []; //记录集$lists
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk === $key) {
                    if (in_array($pk, ['goodsSn'])) {
                        $ids[] = "'" . $value . "'";
                    } else {
                        $ids[] = $value;
                    }
                } else {
                    $lists[$key] .= sprintf("WHEN %u THEN '%s' ", $data[$pk], $value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s ) and %s ', $table_name, rtrim($sql, ','), $pk, implode(',', $ids), $andWhere);
        return M()->execute($sql);
    }

    /**
     * @param $shopId
     * @return array
     * 修改限量购商品
     */
    public function editGoodsCountSnapped($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $goods = M('goods');
        $requestParam = I('');
        //备参与过滤
        $save = [];
        $save['marketPrice'] = null;//市场价
        $save['limitCountActivityPrice'] = null;//限量活动价
        $save['minBuyNum'] = null;//最小起订量
        $save['saleCount'] = null;//销售量
        $save['limitCount'] = null;//限量库存
        $save['limit_daily'] = null;//每日限购量
//        $save['isSale'] = null;//是否上架(0:不上架 1:上架)
        parm_filter($save, $requestParam);
        $where['goodsId'] = I('goodsId');
        $where['goodsFlag'] = 1;
        $where['shopId'] = $shopId;
        $where['isLimitBuy'] = 1;
        $goodsInfo = $goods->where($where)->find();
        if (empty($goodsInfo)) {
            $rd['msg'] = "请查看商品是否存在";
            return $rd;
        }
        //市场价
        if (!empty($save['marketPrice'])) {
            if ($save['marketPrice'] <= $goodsInfo['limitCountActivityPrice']) {
                $rd['msg'] = "市场价不能小于或等于活动价";
                return $rd;
            }
        }
        //活动价
        if (!empty($save['limitCountActivityPrice'])) {
            if ($save['limitCountActivityPrice'] >= $goodsInfo['marketPrice']) {
                $rd['msg'] = "活动价不能大于或等于市场价";
                return $rd;
            }
        }
        //最小起订量
        if (!empty($save['minBuyNum'])) {
            if ($save['minBuyNum'] >= $goodsInfo['limitCount']) {
                $rd['msg'] = "最小起订量不能大于活动库存";
                return $rd;
            }
        }
        //活动库存
//        if (!empty($save['limitCount'])) {
//            $limitCount = $goodsInfo['goodsStock'] - ($save['limitCount'] - $goodsInfo['limitCount']);
//            if ($limitCount < 0) {
//                $rd['msg'] = "请查看当前商品库存是否充足";
//                return $rd;
//            }
//            $save['goodsStock'] = $limitCount;
//        }
        //每日限购量
        if (isset($save['limit_daily'])) {//兼容原有程序正常运行
            $save['limit_daily'] = (int)$save['limit_daily'];
            if ($save['limit_daily'] != -1 && $save['limit_daily'] <= 0) {
                $rd['msg'] = "每日限购量必须大于0";
                return $rd;
            }
        }
        $data = $goods->where($where)->save($save);
        if ($data !== false) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }
}
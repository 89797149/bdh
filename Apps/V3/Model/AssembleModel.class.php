<?php

namespace V3\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Assemble\AssembleModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Pay\PayModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 拼团活动类
 */
class AssembleModel extends BaseModel
{

    /**
     * 获取拼团活动列表
     * 弃用
     */
    public function getAssembleList($data)
    {
        $sql = "select aa.*,g.goodsName from __PREFIX__assemble_activity as aa left join __PREFIX__goods as g on aa.goodsId = g.goodsId where aa.state = " . $data['state'] . " and aa.shopId = " . $data['shopId'];
        if (!empty($data['title'])) $sql .= " and aa.title like '%" . $data['title'] . "%'";

        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 添加拼团活动
     */
    public function insertAssemble($data = array())
    {
        return M('assemble_activity')->add($data);
    }

    /**
     * 编辑拼团活动
     */
    public function updateAssemble($aid, $data = array())
    {
        return M('assemble_activity')->where('aid = ' . $aid)->save($data);
    }

    /**
     * 获取活动详情
     * @param int $aid
     */
//    public function assembleDetail(int $aid)
//    {
//        $where = [];
//        $where['activity.aid'] = $aid;
//        $where['activity.aFlag'] = 1;
//        $field = 'activity.aid,activity.shopId,activity.title,activity.groupPeopleNum,activity.limitNum,activity.goodsId,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour,activity.aFlag,activity.activityStatus,';
//        $field .= 'goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.goodsDesc,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.integralReward,goods.buyNum,goods.spec,';
//        $field .= 'shop.deliveryMoney,shop.deliveryFreeMoney,shop.shopAddress';
//        $res = M('assemble_activity activity')
//            ->join('left join wst_goods goods on goods.goodsId = activity.goodsId')
//            ->join('left join wst_shops shop on activity.shopId = shop.shopId')
//            ->where($where)
//            ->field($field)
//            ->find();
//        if (!empty($res)) {
//            $res = $this->getCountGoodsAssembleUser($res);
//            $goodsInfo = $this->getActivityGoods($res['aid']);
//            $res['hasGoodsSku'] = $goodsInfo['hasGoodsSku'];
//            $res['goodsSku'] = $goodsInfo['goodsSku'];
//        }
//        return returnData($res);
//    }

    /**
     * 拼团-活动详情
     * @param int $aid 活动id
     * @return array
     * */
    public function getAssembleDetail(int $aid)
    {
        $assemble_module = new AssembleModule();
        $detail = $assemble_module->getAssembleActiveDetailById($aid);
        if (empty($detail)) {
            return array();
        }
        return $detail;
    }

    /**
     * 删除拼团活动
     */
    public function deleteAssemble($aid, $shopId)
    {
        $where = 'aid = ' . $aid . ' and shopId = ' . $shopId;
        $result = M('assemble_activity')->where($where)->delete();
        if ($result) M('user_activity_relation')->where($where)->delete();
        return $result;
    }

    /**
     * 获取拼团用户
     */
    public function getAssembleUser($aid)
    {
        return M('user_activity_relation as uar')->join('wst_users as u on uar.uid = u.userId')->where('uar.aid = ' . $aid)->field('u.username,uar.*')->select();
    }

    /**
     * 拼团订单列表（正在进行中和拼团失败）
     */
    public function getAssembleOrderList($data)
    {
        $sql = "select o.* from __PREFIX__orders as o left join __PREFIX__user_activity_relation as uar on o.orderId = uar.orderId left join __PREFIX__assemble as a on a.pid = uar.pid where o.orderStatus = 15 and (a.state = -1 or (a.startTime <= '" . $data['curTime'] . "' and a.endTime >= '" . $data['curTime'] . "' and a.state = 0)) and a.shopId = " . $data['shopId'];
        return $this->pageQuery($sql, $data['page'], $data['pageSize']);
    }

    /**
     * 获得拼团商品
     * 多社区
     */
    /*public function getShopAssembleGoods($shopId){
        $curTime = date('Y-m-d H:i:s');
        $sql = "select g.*,a.* from __PREFIX__goods as g left join __PREFIX__assemble as a on a.goodsId = g.goodsId where a.startTime <= '" . $curTime . "' and a.endTime >= '" . $curTime . "' and a.shopId = " . $shopId . " and a.state = 0 and g.isSale = 1";
        return $this->query($sql);
    }*/

//    /**
//     * 获得拼团商品
//     * 多门店
//     */
//    public function getShopAssembleGoods($shopId,$page=1,$pageSize=10,$userId=0){
//        $curTime = date('Y-m-d H:i:s');
//        $sql = "select g.*,a.* from __PREFIX__goods as g left join __PREFIX__assemble_activity as a on a.goodsId = g.goodsId where a.startTime <= '" . $curTime . "' and a.endTime >= '" . $curTime . "' and a.shopId = " . $shopId . " and g.isSale = 1";
//        $res = $this->pageQuery($sql,$page,$pageSize);
//        if(!empty($res['root'])){
//            $res['root'] = handleNewPeople($res['root'],$userId);
//            $res['root'] = $this->getCountGoodsAssembleUser($res['root']);
//        }
//        return $res;
//    }

    /**
     * 店铺拼团商品 兼容前置仓和多商户
     * @param int $shopId 店铺id,有值为前置仓,无值为多商户
     * @param float lat 纬度
     * @param float lng 经度
     * @param int page 页码
     * @param int pageSize 分页条数
     * */
//    public function getShopAssembleGoods($shopId, $userId = 0, $page = 1, $pageSize = 10, $lat = '', $lng = '')
//    {
//        //很多项目都已经在用了,就不重写了,返回格式还是按照以前的来,,,,过滤结果集也会导致分页数据不准确
//        $nowTime = (float)(date('H') . '.' . date('i'));//获取当前时间
//        $curTime = date('Y-m-d H:i:s');
//        $where = "a.startTime <= '{$curTime}' and a.endTime >= '{$curTime}' and g.isSale = 1 and a.activityStatus=1 ";
//        if (empty($shopId)) {
//            //多商户
//            if (empty($lat) || empty($lng)) {
//                $data = returnData(null, -1, 'error', '多商户模式，经纬度不能为空');
//                return $data;
//            }
//            $where = " s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 ";//店铺基本条件
//            $where .= " and s.serviceStartTime <= '{$nowTime}' and s.serviceEndTime >= '{$nowTime}' ";//店铺营业时间
//        } else {
//            //前置仓
//            $where .= " and a.aFlag = 1 ";
//            $where .= " and a.shopId = {$shopId}";
//        }
//        $field = "g.goodsId,g.goodsSn,g.goodsName,g.goodsImg,g.goodsThums,g.shopId,g.marketPrice,g.shopPrice,g.goodsStock,g.saleCount,g.goodsSpec,g.isSale,g.saleTime,g.markIcon,g.SuppPriceDiff,g.weightG,g.IntelligentRemark,g.isMembershipExclusive,g.memberPrice";
//        $field .= ",a.aid,a.title,a.groupPeopleNum,a.limitNum,a.tprice,a.startTime,a.endTime,a.describle,a.createTime,a.limitHour";
//        $field .= ",s.shopName,s.latitude,s.longitude ";
//        $orderBy = " g.saleCount desc ";
//        if (!empty($lat) && !empty($lng)) {
//            $orderBy = " distance asc ";
//            $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";//距离
//        }
//        $sql = "select $field from __PREFIX__goods as g left join __PREFIX__assemble_activity as a on a.goodsId = g.goodsId left join __PREFIX__shops s on s.shopId=a.shopId where $where ";
//        $sql .= "order by $orderBy ";
//        $res = $this->pageQuery($sql, $page, $pageSize);//特别备注:该地方以前调的就是这个方法,为了不影响前端使用,故保留
//        if (!empty($res['root'])) {
//            $data = $res['root'];
//            foreach ($data as $key => $val) {
//                $goodsInfo = $this->getActivityGoods($val['aid']);
//                $data[$key]['hasGoodsSku'] = $goodsInfo['hasGoodsSku'];
//                $data[$key]['goodsSku'] = $goodsInfo['goodsSku'];
//            }
//            if (empty($shopId)) {
//                //多商户模式需要校验配送范围
//                $returnData = [];
//                $countData = count($data);
//                for ($i = 0; $i < count($data); $i++) {
//                    $countData = $res['total'];
//                    $shopId = $data[$i]['shopId'];
//                    if (checkShopDistribution($shopId, $lng, $lat)) {
//                        $returnData[] = $data[$i];
//                    } else {
//                        $countData -= 1;
//                    }
//                }
//                $res['total'] = (int)$countData;
//                $res['root'] = (array)$returnData;
//            }
//            $res['root'] = handleNewPeople($data, $userId);
//            $res['root'] = $this->getCountGoodsAssembleUser($data);
//        }
//        $res['total'] = (int)$res['total'];
//        $res['root'] = empty($res['root']) ? [] : (array)$res['root'];
//        return returnData($res);
//    }

    /**
     * 店铺拼团商品 兼容前置仓和多商户
     * @param int $shopId 店铺id,有值为前置仓,无值为多商户
     * @param float $lat 纬度
     * @param float $lng 经度
     * @param int $page 页码
     * @param int $pageSize 分页条数
     * @return array
     * */
    public function getShopAssembleGoods($shop_id, $user_id = 0, $page = 1, $page_size = 10, $lat = '', $lng = '')
    {
        //很多项目都已经在用了,就不重写了,返回格式还是按照以前的来,,,,过滤结果集也会导致分页数据不准确
        $now_hour = (float)(date('H') . '.' . date('i'));//获取当前时间
        $curr_date = date('Y-m-d H:i:s');
        $where = "activity.startTime <= '{$curr_date}' and activity.endTime >= '{$curr_date}' and activity.activityStatus=1 and activity.aFlag = 1 ";
        $where .= " and goods.isSale = 1 ";
        if (empty($shop_id)) {
            //多商户
            if (empty($lat) || empty($lng)) {
                $data = returnData(null, -1, 'error', '多商户模式，经纬度不能为空');
                return $data;
            }
            $where = " shops.shopStatus=1 and shops.shopFlag=1 and shops.shopAtive=1 ";//店铺基本条件
            $where .= " and shops.serviceStartTime <= '{$now_hour}' and shops.serviceEndTime >= '{$now_hour}' ";//店铺营业时间
        } else {
            //前置仓
            $where .= " and activity.shopId = {$shop_id}";
        }
        $field = "goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.shopId,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.unit";
        $field .= ",activity.aid,activity.title,activity.groupPeopleNum,activity.limitNum,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour";
        $field .= ",shops.shopName,shops.latitude,shops.longitude ";
        $orderBy = " goods.saleCount desc ";
        if (!empty($lat) && !empty($lng)) {
            $orderBy = " distance asc ";
            $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-shops.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(shops.latitude*PI()/180)*POW(SIN(($lng*PI()/180-shops.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";//距离
        }
        $sql = "select {$field} from __PREFIX__goods as goods left join __PREFIX__assemble_activity as activity on activity.goodsId = goods.goodsId left join __PREFIX__shops shops on shops.shopId=activity.shopId where {$where} ";
        $sql .= "order by {$orderBy} ";
        $res = $this->pageQuery($sql, $page, $page_size);//特别备注:该地方以前调的就是这个方法,为了不影响前端使用,故保留
        if (!empty($res['root'])) {
            $data = $res['root'];
            foreach ($data as $key => $val) {
                $assemble_module = new AssembleModule();
                $goods_detail = $assemble_module->getActivityGoods($val['aid']);
                $data[$key]['hasGoodsSku'] = $goods_detail['hasGoodsSku'];
                $data[$key]['goodsSku'] = $goods_detail['goodsSku'];
            }
            if (empty($shop_id)) {
                //多商户模式需要校验配送范围
                $return_data = array();
                $count_data = count($data);
                for ($i = 0; $i < count($data); $i++) {
                    $count_data = $res['total'];
                    $shop_id = $data[$i]['shopId'];
                    if (checkShopDistribution($shop_id, $lng, $lat)) {
                        $return_data[] = $data[$i];
                    } else {
                        $count_data -= 1;
                    }
                }
                $res['total'] = (int)$count_data;
                $res['root'] = (array)$return_data;
            }
            $res['root'] = handleNewPeople($data, $user_id);
            $res['root'] = $assemble_module->getCountGoodsAssembleUser($data);
        }
        $res['total'] = (int)$res['total'];
        $res['root'] = empty($res['root']) ? [] : (array)$res['root'];
        return returnData($res);
    }

    /*
     * 获取该商品已被多少用户拼过
     * @param array $array
     * */
    public function countGoodsAssembleUser($param)
    {
        $goodsId = (int)$param['goodsId'];
        $count = 0;
        $activity = M('assemble_activity')->where(['goodsId' => $goodsId, 'aFlag' => 1])->select();//该商品参与过多少次拼团活动
        if ($activity) {
            $aid = [];
            foreach ($activity as $val) {
                $aid[] = $val['aid'];
            }
            $where = [];
            $where['n.aid'] = ["IN", $aid];
            $list = M('user_activity_relation n')
                ->join("left join wst_orders o on o.orderId=n.orderId")
                ->field('n.*,o.orderStatus,o.isPay')
                ->where($where)->select();
            foreach ($list as $val) {
                if ($val['isPay'] == 1) {
                    $count += 1;
                }
            }
        }
        return (int)$count;
    }

    /*
     *处理商品的拼团人数 PS:此方法是后加,兼容之前的countGoodsAssembleUser方法
     * @param array $goods PS:商品数据
     * */
    public function getCountGoodsAssembleUser($goods)
    {
        $goods = (array)$goods;
        if (empty($goods)) {
            return $goods;
        }
        if (array_keys($goods) !== range(0, count($goods) - 1)) {
            //详情
            $goods['assembleUserNum'] = $this->countGoodsAssembleUser(['goodsId' => $goods['goodsId']]);
        } else {
            //列表
            foreach ($goods as $key => $val) {
                $goods[$key]['assembleUserNum'] = $this->countGoodsAssembleUser(['goodsId' => $val['goodsId']]);
            }
        }
        return $goods;
    }

    /**
     * 所有店铺里的拼团活动
     * //前置仓模式
     * @param int shopId 店铺id
     * @param int page 页码
     * @param int pageSize 分页条数
     * //多商户模式
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function getAssembleActivityList($userId, $shopId, $adcode, $lat, $lng, $page, $pageSize)
    {
        //处理拼团状态
        $orderTab = M('orders');
        $relationTab = M('user_activity_relation');
        $assembleTab = M('assemble');
        $where = [];
        $where['state'] = 0;
        $assembleList = $assembleTab->where($where)->select();
        if (!empty($assembleList)) {
            foreach ($assembleList as $v) {
                $where = [];
                $where['aid'] = $v['aid'];
                $where['pid'] = $v['pid'];
                //拼团成功
                if ($v['buyPeopleNum'] >= $v['groupPeopleNum']) {
                    //更改拼团的状态
                    $saveData = [];
                    $saveData['state'] = 1;
                    $assembleTab->where($where)->save($saveData);
                    $relationTab->where($where)->save($saveData);
                    //更改订单的状态
                    $orderIdArr = $relationTab->where($where)->getField('orderId', true);
                    $orderWhere = [];
                    $orderWhere['orderId'] = ['IN', $orderIdArr];
                    $saveOrderData = [];
                    $saveOrderData['orderStatus'] = 0;
                    $orderTab->where($orderWhere)->save($saveOrderData);
                }
                //活动时间过期，拼团失败
                if ($v['endTime'] <= date('Y-m-d H:i:s')) {
                    $saveData = [];
                    $saveData['state'] = -1;
                    $assembleTab->where($where)->save($saveData);
                    $relationTab->where($where)->save($saveData);
                }
            }
        }
        //拼团失败，自动退费用、退库存
        D('Home/Assemble')->autoReAssembleOrder();
        $curTime = date('Y-m-d H:i:s');
        $result = [];
        if (!empty($shopId)) {
            $where = "(activity.startTime >= '{$curTime}' and activity.endTime >= '{$curTime}') or (activity.startTime <= '{$curTime}' and activity.endTime >= '{$curTime}') and activity.shopId = {$shopId} and activity.activityStatus=1 and activity.aFlag = 1 ";
            $result = M('assemble_activity activity')
                ->field('activity.*,shops.shopImg')
                ->join('wst_shops shops on activity.shopId = shops.shopId')
                ->where($where)
                ->order('activity.createTime desc')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();
        }
        if (empty($shopId) && !empty($adcode) && !empty($lat) && !empty($lng)) {
            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where = "where shops.shopStatus=1 and shops.shopFlag=1 and shops.shopAtive=1 and shops.serviceStartTime <= '{$newTime}' and shops.serviceEndTime >= '{$newTime}' ";
            $where .= " and (activity.startTime >= '{$curTime}' and activity.endTime >= '{$curTime}') or (activity.startTime <= '{$curTime}' and activity.endTime >= '{$curTime}') and activity.activityStatus=1 and activity.aFlag = 1 ";
            $where .= " and communitys.areaId3={$adcode} ";
            $sql = "select DISTINCT activity.*,shops.shopImg,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-shops.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(shops.latitude*PI()/180)*POW(SIN(($lng*PI()/180-shops.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops shops left join wst_shops_communitys communitys on shops.shopId=communitys.shopId left join __PREFIX__assemble_activity activity on shops.shopId = activity.shopId " . $where . " order by distance asc,activity.createTime desc limit " . ($page - 1) * $pageSize . "," . $pageSize;
            $result = $this->query($sql);
        }
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $goodsDetail = $this->getActivityGoods($v['aid']);
                $result[$k]['goodsDetail'] = $goodsDetail;
                $result[$k]['isNewPeople'] = (int)$result[$k]['goodsDetail']['isNewPeople'];
                $result[$k]['assembleUserNum'] = $this->countGoodsAssembleUser($v);
            }
            //过滤没有商品信息的数据
            $res = [];
            foreach ($result as $key => $value) {
                if (!empty($value['goodsDetail'])) {
                    $res[] = $value;
                }
            }
            $result = handleNewPeople($res, $userId);//剔除新人专享商品
            if (empty($shopId) && !empty($result)) {
                $countData = count($result);
                $returnData = [];
                for ($i = 0; $i < $countData; $i++) {
                    $shopId = $result[$i]['shopId'];
                    if (checkShopDistribution($shopId, $lng, $lat)) {//验证是否在配送范围
                        $returnData[] = $result[$i];
                    }
                }
                $result = $returnData;
            }
        }
        if (empty($result)) {
            $result = [];
        }
        return returnData((array)$result);
    }

    /**
     * 获取拼团商品信息
     * @param int $aid 活动id
     * */
    public function getActivityGoods(int $aid)
    {
        $activityTab = M('assemble_activity activity');
        $where = [];
        $where['activity.aid'] = $aid;
        $activityInfo = $activityTab->where($where)->find();
        if (empty($activityInfo)) {
            return returnData([]);
        }
        $goodsId = $activityInfo['goodsId'];
        $where = [];
        $where['goodsId'] = $goodsId;
        $where['goodsFlag'] = 1;
        $where['isSale'] = 1;
        $goodsInfo = M('goods')->where($where)->find();
        if (empty($goodsInfo)) {
            return [];
        }
        $where = [];
        $where['ac_goods_sku.aid'] = $activityInfo['aid'];
        $where['system.dataFlag'] = 1;
        $field = "ac_goods_sku.tprice,system.*";
        $activityGoodsSku = M('assemble_activity_goods_sku ac_goods_sku')
            ->join("left join wst_sku_goods_system system on system.skuId=ac_goods_sku.skuId")
            ->where($where)
            ->field($field)
            ->select();
        $goodsInfo['hasGoodsSku'] = -1;//是否有拼团sku【0：无|1：有】
        $goodsInfo['goodsSku'] = [];
        $goodsInfo['tprice'] = $activityInfo['tprice'];
        if (!empty($activityGoodsSku)) {
            $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
            $goodsSelfTab = M('sku_goods_self self');
            $skuList = [];
            foreach ($activityGoodsSku as $key => $value) {
                $spec = [];
                $spec['skuId'] = $value['skuId'];
                foreach ($replaceSkuField as $rek => $rev) {
                    if (isset($value[$rek])) {
                        $spec['systemSpec'][$rek] = $value[$rek];
                    }
                    if (in_array($rek, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if ((int)$spec['systemSpec'][$rek] == -1) {
                        //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        $spec['systemSpec'][$rek] = $goodsInfo[$rev];
                    }
                }
                $spec['systemSpec']['tprice'] = $value['tprice'];
                $selfSpec = $goodsSelfTab
                    ->join("left join wst_sku_spec sp on self.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=self.attrId")
                    ->where(['self.skuId' => $value['skuId'], 'self.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
                    ->order('sp.sort asc')
                    ->select();
                if (empty($selfSpec)) {
                    unset($activityGoodsSku[$key]);
                    continue;
                }
                $spec['selfSpec'] = $selfSpec;
                $skuList[] = $spec;
            }
            if (count($skuList) > 0) {
                $goodsInfo['hasGoodsSku'] = 1;
            }
            //skuSpec
            $skuSpec = [];
            $skuSpecAttr = [];
            foreach ($skuList as $value) {
                foreach ($value['selfSpec'] as $va) {
                    $skuSpecAttr[] = $va;
                    $skuSpecInfo['specId'] = $va['specId'];
                    $skuSpecInfo['specName'] = $va['specName'];
                    $skuSpec[] = $skuSpecInfo;
                }
            }
            $skuSpec = arrayUnset($skuSpec, 'specId');
            $skuSpecAttr = arrayUnset($skuSpecAttr, 'attrId');
            foreach ($skuSpec as $skey => &$sval) {
                foreach ($skuSpecAttr as $v) {
                    if ($v['specId'] == $sval['specId']) {
                        $attrInfo['skuId'] = $v['skuId'];
                        $attrInfo['attrId'] = $v['attrId'];
                        $attrInfo['attrName'] = $v['attrName'];
                        $sval['attrList'][] = $attrInfo;
                    }
                }
            }
            unset($sval);
            $goodsInfo['goodsSku']['skuList'] = $skuList;
            $goodsInfo['goodsSku']['skuSpec'] = $skuSpec;
        }
        return $goodsInfo;
    }

    /**
     * PS:已经在用的接口,接口返回格式保留之前的格式,就不修改了
     * 用户拼团订单列表
     * @param int $userId
     * @param int $flag 拼团状态【-1：拼团失败|2：拼团中|3:拼团成功】
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     */
    public function getUserAssembleOrderList($userId, $flag, $page, $pageSize)
    {
//        $where = " (orders.orderStatus = 15 or orders.orderStatus = 0) ";
        $where = " orders.orderType = 2 ";
        $where .= " and relation.uid={$userId} ";
        if ($flag == -1) {
            $where .= " and (relation.state = -1 or relation.state = -3) ";//拼团失败
        }
        if ($flag == 2) {
            $where .= " and relation.state = 0 ";//拼团中
        }
        if ($flag == 3) {
            $where .= " and relation.state = 1 ";//拼团成功
        }
        $field = 'orders.*,ogoods.goodsId,ogoods.goodsNums,ogoods.goodsPrice,ogoods.goodsAttrId,ogoods.goodsAttrName,ogoods.goodsName,ogoods.goodsThums,ogoods.weight,ogoods.skuId,ogoods.skuSpecAttr,shop.shopName,shop.shopImg,goods.goodsDesc,relation.aid,relation.state as assembleState,assemble.pid,assemble.groupPeopleNum,assemble.tprice,assemble.endTime as assembleEndTime';
        $sql = "select $field from __PREFIX__orders orders ";
        $sql .= " left join __PREFIX__order_goods ogoods on orders.orderId = ogoods.orderId ";
        $sql .= " left join __PREFIX__user_activity_relation relation on orders.orderId = relation.orderId ";
        $sql .= " left join __PREFIX__goods goods on goods.goodsId = ogoods.goodsId ";
        $sql .= " left join __PREFIX__shops shop on orders.shopId = shop.shopId ";
        $sql .= " left join __PREFIX__assemble assemble on assemble.pid = relation.pid";
        $sql .= " left join __PREFIX__assemble_activity activity on relation.aid = assemble.aid ";
        $sql .= " where $where group by orders.orderId order by orders.createTime desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($result['root'])) {
            $goodsModule = new GoodsModule();
            $config = $GLOBALS['CONFIG'];
            foreach ($result['root'] as $k => $v) {
                $result['root'][$k]['unit'] = $goodsModule->getGoodsUnitByParams($v['goodsId'], $v['skuId']);
                $surplusPeopleNum = $this->getSurplusPeopleNum($v['orderId']);//拼团剩余人数
                $result['root'][$k]['surplusPeopleNum'] = $surplusPeopleNum;
                //后加,兼容统一运费 start
                if ($config['setDeliveryMoney'] == 2) {
                    if ($v['isPay'] == 1) {
                        //$result['root'][$k]['realTotalMoney'] = $v['realTotalMoney']+$v['deliverMoney'];
                        $result['root'][$k]['realTotalMoney'] = $v['realTotalMoney'];
                    } else {
                        if ($v['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $result['root'][$k]['realTotalMoney'] = $v['realTotalMoney'] + $v['setDeliveryMoney'];
                            $result['root'][$k]['deliverMoney'] = $v['setDeliveryMoney'];
                        }
                    }
                }
                //后加,兼容统一运费 end
            }
        }
        return returnData($result);
    }

    /**
     * 获取订单详情
     */
    public function getOrdersDetails($orderId, $field = 'orderId')
    {
        /*$sql = "SELECT od.*,sp.shopName,sp.shopImg
				FROM __PREFIX__orders od, __PREFIX__shops sp,__PREFIX__user_activity_relation ur
				WHERE od.shopId = sp.shopId And od.orderId = $orderId ";*/
        $sql = "SELECT od.*,sp.shopName,sp.shopImg,ur.pid,ur.aid,ur.state as assembleState,aa.groupPeopleNum,asse.buyPeopleNum,asse.endTime as assembleEndTime,aa.describle,sp.deliveryMoney,sp.deliveryFreeMoney,sp.shopAddress,aa.tprice
				FROM __PREFIX__orders od LEFT JOIN __PREFIX__shops sp ON od.shopId = sp.shopId LEFT JOIN __PREFIX__user_activity_relation ur on od.orderId=ur.orderId left join __PREFIX__assemble_activity as aa on aa.aid = ur.aid  left join __PREFIX__assemble as asse on asse.pid = ur.pid
				WHERE od.shopId = sp.shopId";
        if ($field == 'orderId') {
            $sql .= " And od.orderId = $orderId ";
        } elseif ($field == 'pid') {
            $sql .= " And ur.pid = $orderId ";
        } else {
            die('暂未实现');
        }
        $rs = $this->query($sql);
        //后加,兼容统一运费 start
        if (!empty($rs)) {
            $config = $GLOBALS['CONFIG'];
            if ($config['setDeliveryMoney'] == 2) {
                foreach ($rs as $rk => $rv) {
                    if ($rv['isPay'] == 1) {
                        //$rs[$rk]['realTotalMoney'] = $rs[$rk]['realTotalMoney'] + $rs[$rk]['setDeliveryMoney'];
                        $rs[$rk]['realTotalMoney'] = $rs[$rk]['realTotalMoney'];
                    } else {
                        if ($rs[$rk]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $rs[$rk]['realTotalMoney'] = $rs[$rk]['realTotalMoney'] + $rs[$rk]['setDeliveryMoney'];
                            $rs[$rk]['deliverMoney'] = $rs[$rk]['setDeliveryMoney'];
                        }
                    }
                }
            }
        }
        //后加,兼容统一运费 end

        return $rs;
    }

    /*
     * 获取用户和拼团活动关联表
     * */
    public function getUserActivityRelation($orderId)
    {
        $info = M('user_activity_relation')->where(['orderId' => $orderId])->find();
        return $info;
    }

    /**
     * 获取订单商品信息
     */
    public function getOrdersGoods($orderId)
    {
        /*$sql = "SELECT g.*,og.goodsNums as ogoodsNums,og.goodsPrice as ogoodsPrice,og.skuId,og.skuSpecAttr
				FROM __PREFIX__order_goods og, __PREFIX__goods g
				WHERE og.orderId = $orderId AND og.goodsId = g.goodsId ";*/
        $sql = "SELECT g.*,og.goodsNums as ogoodsNums,og.goodsPrice as ogoodsPrice,og.skuId,og.skuSpecAttr,og.remarks,sp.deliveryMoney,sp.deliveryFreeMoney,aa.tprice
				FROM __PREFIX__order_goods og left join  __PREFIX__goods g on og.goodsId = g.goodsId LEFT JOIN __PREFIX__shops sp ON g.shopId = sp.shopId LEFT JOIN __PREFIX__user_activity_relation ur on og.orderId=ur.orderId left join __PREFIX__assemble_activity as aa on aa.aid = ur.aid
				WHERE og.orderId = $orderId";
        $rs = $this->query($sql);
        $goodsModule = new GoodsModule();
        foreach ($rs as &$item) {
            $item['unit'] = $goodsModule->getGoodsUnitByParams($item['goodsId'], $item['skuId']);
        }
        $rs = $this->getCountGoodsAssembleUser($rs);
        return $rs;
    }

    /**
     * 拼团商品搜索(多商户/多门店)
     * //多门店
     * @param array $params <p>
     * int shopId 门店id
     * varchar keywords 关键字
     * int page 页码
     * int pageSize 分页条数,默认10条
     * </p>
     * //多商户
     * @param array $params <p>
     * varchar keywords 关键字
     * int adcode 区县id
     * float lat 纬度
     * float lng 经度
     * </p>
     */
    public function getAssembleGoodsListByKeywords($params)
    {
        $curTime = date('Y-m-d H:i:s');
        $shopId = $params['shopId'];
        $field = 'activity.aid,activity.shopId,activity.title,activity.groupPeopleNum,activity.limitNum,activity.goodsId,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour,activity.aFlag,activity.activityStatus,';
        $field .= 'goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.goodsDesc,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.integralReward,goods.buyNum,goods.spec';
        $data = [];
        if (!empty($shopId)) {
            //多门店
            $where = "(goods.goodsName like '%{$params['keywords']}%' or goods.goodsSn like '%{$params['keywords']}%') and goods.isSale = 1 ";
            $where .= " and activity.startTime <= '{$curTime}' and activity.endTime >= '{$curTime}' and activity.shopId = {$shopId} and activity.activityStatus=1 ";
            $data = M('goods goods')
                ->join('left join  wst_assemble_activity activity on goods.goodsId = activity.goodsId')
                ->where($where)
                ->field($field)
                ->limit(($params['page'] - 1) * $params['pageSize'], $params['pageSize'])
                ->select();
        }
        if (empty($shopId) && !empty($params['adcode']) && !empty($params['lng']) && !empty($params['lat'])) {
            //多商户
            $lat = $params['lat'];
            $lng = $params['lng'];
            $adcode = $params['adcode'];
            $startPage = ($params['page'] - 1) * $params['pageSize'];
            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where = " shops.shopStatus=1 and shops.shopFlag=1 and shops.shopAtive=1 and shops.serviceStartTime <= '{$newTime}' and shops.serviceEndTime >= '{$newTime}' ";
            $where .= " and (goods.goodsName like '%{$params['keywords']}%' or goods.goodsSn like '%{$params['keywords']}%') and goods.isSale = 1 ";
            $where .= " and activity.startTime <= '{$curTime}' and activity.endTime >= '{$curTime}' and activity.activityStatus=1 ";
            $where .= " and communitys.areaId3={$adcode} ";
            $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-shops.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(shops.latitude*PI()/180)*POW(SIN(($lng*PI()/180-shops.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";
            $sql = "select $field from __PREFIX__shops shops ";
            $sql .= " left join wst_shops_communitys communitys on communitys.shopId=shops.shopId";
            $sql .= " left join __PREFIX__goods goods on goods.shopId=shops.shopId ";
            $sql .= " left join __PREFIX__assemble_activity activity on goods.goodsId = activity.goodsId ";
            $sql .= " where $where group by activity.aid order by distance asc limit $startPage, {$params['pageSize']} ";
            $data = $this->query($sql);
            $countData = count($data);
            for ($i = 0; $i < $countData; $i++) {
                $shopId = $data[$i]['shopId'];
                if (checkShopDistribution($shopId, $lng, $lat)) {//验证是否在配送范围
                    $returnData[] = $data[$i];
                }
            }
            $data = $returnData;
        }
        $data = handleNewPeople($data, $params['userId']);
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $goodsInfo = $this->getActivityGoods($val['aid']);
                $data[$key]['hasGoodsSku'] = $goodsInfo['hasGoodsSku'];
                $data[$key]['goodsSku'] = $goodsInfo['goodsSku'];
            }
        }
        return returnData($data);
    }

    /**
     * 通过商品ID来获得拼团活动
     * @param $aid 活动id
     * @param $goodsId 商品id
     * @param $skuId skuId
     * @return mixed
     */
    public function getAssembleGoodsByAidAndGoodsid($aid, $goodsId)
    {
        $data = M('assemble_activity')->where("shopId != 0 and goodsId = " . $goodsId . " and aid = " . $aid)->find();
        return $data;
    }

    /**
     * 下单 (商品入购物车)(原来的)
     */
    /*public function submitAssembleOrder($USER){
        $rd = array('status'=>-1);
        //$USER = session('WST_USER');
        $goodsmodel = D('Mendianapi/Goods');
        //$morders = D('Mendianapi/Orders');
        //$morders = $this;
        $totalMoney = 0;
        $totalCnt = 0;
        //$userId = (int)session('WST_USER.userId');
        $userId = intval($USER['userId']);

        $consigneeId = (int)I("consigneeId");
        //$payway = (int)I("payway");
        $isself = (int)I("isself");
        $needreceipt = (int)I("needreceipt");
        $orderunique = WSTGetMillisecond().$userId;

        $sql = "select * from __PREFIX__cart where userId = $userId and isCheck=1 and goodsCnt>0";
        $shopcart = $this->query($sql);

        $catgoods = array();
        $order = array();
        if(empty($shopcart)){
            $rd['msg'] = '购物车为空!';
            return $rd;
        }else{
            //整理及核对购物车数据
            $paygoods = session('WST_PAY_GOODS');
            $cartIds = array();
            for($i=0;$i<count($shopcart);$i++){
                $cgoods = $shopcart[$i];
                $goodsId = (int)$cgoods["goodsId"];
                $goodsAttrId = (int)$cgoods["goodsAttrId"];

                if(in_array($goodsId, $paygoods)){
                    $goods = $goodsmodel->getGoodsSimpInfo($goodsId,$goodsAttrId);
                    //核对商品是否符合购买要求
                    if(empty($goods)){
                        $rd['msg'] = '找不到指定的商品!';
                        return $rd;
                    }
                    if($goods['goodsStock']<=0){
                        $rd['msg'] = '对不起，商品'.$goods['goodsName'].'库存不足!';
                        return $rd;
                    }
                    if($goods['isSale']!=1){
                        $rd['msg'] = '对不起，商品库'.$goods['goodsName'].'已下架!';
                        return $rd;
                    }
                    $goods["cnt"] = $cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["shopgoods"][] = $goods;
                    $catgoods[$goods["shopId"]]["deliveryFreeMoney"] = $goods["deliveryFreeMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["deliveryMoney"] = $goods["deliveryMoney"];//店铺免运费最低金额
                    $catgoods[$goods["shopId"]]["totalCnt"] = $catgoods[$goods["shopId"]]["totalCnt"]+$cgoods["goodsCnt"];
                    $catgoods[$goods["shopId"]]["totalMoney"] = $catgoods[$goods["shopId"]]["totalMoney"]+($goods["cnt"]*$goods["shopPrice"]);
                    $cartIds[] = $cgoods["cartId"];
                }
            }
            $this->startTrans();
            try{
                $ordersInfo = $this->addOrders($userId,$consigneeId,$needreceipt,$catgoods,$orderunique,$isself);
                $this->commit();
                if(!empty($cartIds)){
                    $sql = "delete from __PREFIX__cart where userId = $userId and cartId in (".implode(",",$cartIds).")";
                    $this->execute($sql);
                }
                //向用户和活动关联表插入数据
                $data_arr = array();
                foreach ($ordersInfo as $v) {
                    $data_arr[] = array(
                        'uid'           =>  $userId,
                        'aid'           =>  I('aid',0,'intval'),
                        'shopId'        =>  I('shopId',0,'intval'),
                        'orderId'       =>  $v,
                        'createTime'    =>  date('Y-m-d H:i:s'),
                        'state'         =>  0
                    );
                }
                M('user_activity_relation')->addAll($data_arr);
                $rd['orderIds'] = implode(",",$ordersInfo["orderIds"]);
                $rd['status'] = 1;
                $rd['code'] = 0;
                session("WST_ORDER_UNIQUE",$orderunique);
            }catch(Exception $e){
                $this->rollback();
                $rd['code'] = 1;
                $rd['msg'] = '下单出错，请联系管理员!';
            }
            return $rd;
        }
    }*/

    /**
     * 生成订单 （原来的）
     */
    /*public function addOrders($userId,$consigneeId,$needreceipt,$catgoods,$orderunique,$isself){

        $orderInfos = array();
        $orderIds = array();
        $orderNos = array();
        $remarks = I("remarks");

        $addressInfo = UserAddressModel::getAddressDetails($consigneeId);
        $m = M('orderids');

        foreach ($catgoods as $key=> $shopgoods){
            //生成订单ID
            $orderSrcNo = $m->add(array('rnd'=>microtime(true)));
            $orderNo = $orderSrcNo."".(fmod($orderSrcNo,7));
            //创建订单信息
            $data = array();
            $pshopgoods = $shopgoods["shopgoods"];
            $shopId = $pshopgoods[0]["shopId"];
            $data["orderNo"] = $orderNo;
            $data["shopId"] = $shopId;
            $deliverType = intval($pshopgoods[0]["deliveryType"]);
            $data["userId"] = $userId;

            $data["orderFlag"] = 1;
            $data["totalMoney"] = $shopgoods["totalMoney"];
            if($isself==1){//自提
                $deliverMoney = 0;
            }else{
                $deliverMoney = ($shopgoods["totalMoney"]<$shopgoods["deliveryFreeMoney"])?$shopgoods["deliveryMoney"]:0;
            }
            $data["deliverMoney"] = $deliverMoney;
            $data["payType"] = 1;
            $data["deliverType"] = $deliverType;
            $data["userName"] = $addressInfo["userName"];
            $data["areaId1"] = $addressInfo["areaId1"];
            $data["areaId2"] = $addressInfo["areaId2"];
            $data["areaId3"] = $addressInfo["areaId3"];
            $data["communityId"] = $addressInfo["communityId"];
            $data["userAddress"] = $addressInfo["paddress"]." ".$addressInfo["address"];
            $data["userTel"] = $addressInfo["userTel"];
            $data["userPhone"] = $addressInfo["userPhone"];

            $data['orderScore'] = getOrderScoreByOrderScoreRate($data["totalMoney"]);
            $data["isInvoice"] = $needreceipt;
            $data["orderRemarks"] = $remarks;
            $data["requireTime"] = I("requireTime");
            $data["invoiceClient"] = I("invoiceClient");
            $data["isAppraises"] = 0;
            $data["isSelf"] = $isself;

            $isScorePay = (int)I("isScorePay",0);
            $scoreMoney = 0;
            $useScore = 0;

            if($GLOBALS['CONFIG']['poundageRate']>0){
                $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
                $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100,0,2);
            }else{
                $data["poundageRate"] = 0;
                $data["poundageMoney"] = 0;
            }

            $data["realTotalMoney"] = $shopgoods["totalMoney"]+$deliverMoney - $scoreMoney;
            $data["needPay"] = $shopgoods["totalMoney"]+$deliverMoney - $scoreMoney;

            $data["createTime"] = date("Y-m-d H:i:s");
            $data["orderStatus"] = 15;

            $data["orderunique"] = $orderunique;
            $data["isPay"] = 0;
            if($data["needPay"]==0){
                $data["isPay"] = 1;
            }

            $morders = M('orders');
            $orderId = $morders->add($data);

            //订单创建成功则建立相关记录
            if($orderId>0){

                $orderIds[] = $orderId;
                //建立订单商品记录表
                $mog = M('order_goods');
                foreach ($pshopgoods as $key=> $sgoods){
                    $data = array();
                    $data["orderId"] = $orderId;
                    $data["goodsId"] = $sgoods["goodsId"];
                    $data["goodsAttrId"] = (int)$sgoods["goodsAttrId"];
                    if($sgoods["attrVal"]!='')$data["goodsAttrName"] = $sgoods["attrName"].":".$sgoods["attrVal"];
                    $data["goodsNums"] = $sgoods["cnt"];
                    $data["goodsPrice"] = $sgoods["shopPrice"];
                    $data["goodsName"] = $sgoods["goodsName"];
                    $data["goodsThums"] = $sgoods["goodsThums"];
                    $mog->add($data);
                }

                //建立订单提醒
                $sql ="SELECT userId,shopId,shopName FROM __PREFIX__shops WHERE shopId=$shopId AND shopFlag=1  ";
                $users = $this->query($sql);
                $morm = M('order_reminds');
                for($i=0;$i<count($users);$i++){
                    $data = array();
                    $data["orderId"] = $orderId;
                    $data["shopId"] = $shopId;
                    $data["userId"] = $users[$i]["userId"];
                    $data["userType"] = 0;
                    $data["remindType"] = 0;
                    $data["createTime"] = date("Y-m-d H:i:s");
                    $morm->add($data);
                }

                //修改库存
                foreach ($pshopgoods as $key=> $sgoods){
					$sgoods_cnt = gChangeKg($sgoods["goodsId"],$sgoods['cnt'],1);
                    $sql="update __PREFIX__goods set goodsStock=goodsStock-".$sgoods_cnt." where goodsId=".$sgoods["goodsId"];
                    $this->execute($sql);
                    if((int)$sgoods["goodsAttrId"]>0){
                        $sql="update __PREFIX__goods_attributes set attrStock=attrStock-".$sgoods_cnt." where id=".$sgoods["goodsAttrId"];
                        $this->execute($sql);
                    }
                }

                $data = array();
                $data["orderId"] = $orderId;
                $data["logContent"] = "订单已提交，等待支付";
                $data["logUserId"] = $userId;
                $data["logType"] = 0;
                $data["logTime"] = date('Y-m-d H:i:s');
                $mlogo = M('log_orders');
                $mlogo->add($data);
            }
        }

        return array("orderIds"=>$orderIds);

    }*/

    /**
     * 下单(不入购物车)
     */
//    public function submitAssembleOrder($USER){
//
//        $memberToken = I('memberToken', '', 'trim');//token
//        $lat = I('lat', '', 'trim');
//        $lng = I('lng', '', 'trim');
//
////        $loginName = I('loginName', '', 'trim');
//        $goodsId = I('goodsId', '', 'intval');
//        $goodsCnt = I('goodsCnt', 1, 'intval');
//        $goodsAttrId = I('goodsAttrId', '', 'intval');
//        $skuId = I('skuId',0);
//        $curTime = date('Y-m-d H:i:s');
//
//        //拼团ID
//        $pid = I('pid', 0, 'intval');
//        $aid = I('aid',0,'intval');
////        $shopId = I('shopId',0,'intval');
//        //获得活动信息
//        $activityInfo = $this->getAssembleGoodsByAidAndGoodsid($aid,$goodsId,$skuId);
//        if (empty($activityInfo)) return returnData(array(),1,'error',"商品未做拼团活动");
//        if ($activityInfo['startTime'] > $curTime) return returnData(array(),2,'error',"活动尚未开始");
//        if ($activityInfo['endTime'] < $curTime) return returnData(array(),3,'error',"活动已结束");
//
//        $shopId = $activityInfo['shopId'];
//        if (!empty($pid) && !empty($aid) && !empty($shopId)) {
//            $asse_info = M('assemble')->where(array('pid'=>$pid,'userId'=>$USER['userId']))->find();
//            if (!empty($asse_info)) {
//                return returnData(array(),-1,'error',"请不要重复提交");
//            }
//
//            $uar_data = M('user_activity_relation')->where(array('pid'=>$pid,'aid'=>$aid,'shopId'=>$shopId))->order('createTime asc')->find();
//            if (!empty($uar_data) && $uar_data['state'] == -3) {
//                return returnData(array(),-1,'error',"该拼团开团失败");
//            }
//            $uar_where = array(
//                'uar.pid' => $pid,
//                'uar.aid' => $aid,
//                'uar.shopId' => $shopId,
//                'uar.state' => 0,
//                'o.isPay' => 1,
//                'o.isRefund'    =>  0,
//                'o.orderStatus' =>  array(' in ','0,15')
//            );
//            $uar_assemble_data = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->find();
//            if (empty($uar_assemble_data)) {
//                return returnData(array(),-1,'error',"该拼团已失效");
//            }
//        }
//
//        //判断用户是否已经参与过活动，如果已经参与过，则不可再次参与
////        $uaDatasss = M('user_activity_relation')->where(array('aid'=>$activityInfo['aid'],'uid'=>$USER['userId'],'shopId'=>$activityInfo['shopId']))->find();
////        if (!empty($uaDatasss)) return array('code'=>-1, 'msg'=>"您已经参与过活动了，不可重复参与");
//
////        if (!checkShopDistribution($activityInfo['shopId'], $lng, $lat))
////            return array('code'=>4, 'msg'=>'不在配送范围内，不可购买');//不在配送范围内，不可购买
//
//        $mod_goods = M("goods");
//        if($mod_goods->where("goodsId='{$goodsId}'")->find()['isShopPreSale'] == 1)
//            return returnData(array(),'000083','error',"预售商品不可购买");
//
//        $where['isSale'] = 1;
//        $where['goodsStatus'] = 1;
//        $where['goodsFlag'] = 1;
//        $where['goodsId'] = $goodsId;
//        $mod = $mod_goods->where($where)->find();
//
//        if (empty($mod)) return returnData(array(),'000028','error',"商品不存在");
//        if ($mod['goodsStock'] <= 0) return returnData(array(),'000074','error',"库存不足!");
//
//        //后加sku判断
//        if($skuId > 0 ){
//            $systemSkuTab = M('sku_goods_system');
//            $goodsSkuInfo = $systemSkuTab->where(['skuId'=>$skuId])->find();
//            if($goodsSkuInfo['skuGoodsStock'] <= 0){
//                return returnData(array(),'000074','error',"库存不足!");
//            }
//        }
//
//        /*
//         * 2019-06-15 start
//         * 判断属性库存是否存在
//         * */
//        if(!empty($goodsAttrId)){
//            $goodsAttrIdAttr = explode(',',$goodsAttrId);
//            foreach ($goodsAttrIdAttr as $key=>$val){
//                $goodsAttrInfo = M('goods_attributes')->where("goodsId='".$goodsId."' AND id='".$val."'")->find();
//                if($goodsAttrInfo['attrStock'] <= 0) return returnData(array(),'000074','error',"库存不足!");
//            }
//        }
//        /*
//         * 2019-06-15 end
//         * */
//
//
//        $rd = array('code'=>-1);
//        //$USER = session('WST_USER');
//        $goodsmodel = D('Mendianapi/Goods');
//        //$morders = D('Mendianapi/Orders');
//        //$morders = $this;
//        $totalMoney = 0;
//        $totalCnt = 0;
//        //$userId = (int)session('WST_USER.userId');
//        $userId = intval($USER['userId']);
//
//        $consigneeId = (int)I("consigneeId");
//        //$payway = (int)I("payway");
//        $isself = (int)I("isself");
//        $needreceipt = (int)I("needreceipt");
//        $orderunique = WSTGetMillisecond().$userId;
//
//        //检查收货地址是否存在
//        $where1["addressId"] = (int)$consigneeId;
//        $where1["addressFlag"] = 1;
//        $where1["userId"] = $userId;
//        $user_address = M("user_address");
//        $res_user_address = $user_address->where($where1)->find();
//        if(empty($res_user_address)){
//            return returnData(array(),-1,'error','请添加收货地址');
//        }
//
//        $shopConfig = M('shop_configs')->where(['shopId'=>$shopId])->find();
//        if($shopConfig['deliveryLatLngLimit'] ==1){
//            $dcheck = checkShopDistribution($shopId,$res_user_address['lng'],$res_user_address['lat']);
//            if(!$dcheck){
//                unset($apiRet);
//                $gds_info['goodsId']=$mod['goodsId'];
//                $gds_info['goodsName']=$mod['goodsName'];
//                return returnData($gds_info,'000074','error','配送范围超出');
//            }
//            //END
//        }
//
//        $goods = $this->getGoodsSimpInfo($goodsId,$goodsAttrId);
//        //后加sku
//        $goods['skuId'] = 0;
//        if($skuId > 0 ){
//            $goods['skuId'] = $skuId;
//            $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//            foreach ($replaceSkuField as $rk=>$rv){
//                if(isset($goods[$rv])){
//                    $goods[$rv] = $goodsSkuInfo[$rk];
//                }
//            }
//        }
//        //核对商品是否符合购买要求
//        if (empty($goods)) return returnData(array(),5,'error',"找不到指定的商品");
//        if ($goods['goodsStock'] <= 0) return returnData(array(),6,'error','对不起，商品 ' . $goods['goodsName'] . ' 库存不足');
//        if ($goods['isSale'] != 1) return returnData(array(),7,'error','对不起，商品 ' . $goods['goodsName'] . ' 已下架');
//
//        $goods["cnt"] = $goodsCnt;
//        $shopGoodsData = array(
//            'shopgoods' =>  $goods,
//            'deliveryFreeMoney' =>  $goods["deliveryFreeMoney"],//店铺免运费最低金额
//            'deliveryMoney' =>  $goods["deliveryMoney"],//店铺免运费最低金额
//            'totalCnt'  =>  $goodsCnt,
//            'totalMoney'    =>  $goods["cnt"]*$activityInfo['tprice']
//        );
//        $this->startTrans();
//        try{
//            $orderInfo = $this->addOrders($userId,$consigneeId,$needreceipt,$shopGoodsData,$orderunique,$isself);
//            $this->commit();
//
//            $om = M('orders');
//            $uarm = M('user_activity_relation');
//            $aam = M('assemble_activity');
//            $am = M('assemble');
//            $assemble_info = $am->where(array('pid'=>$pid))->find();
//            if (empty($pid) || empty($assemble_info)) {
//                $assemble_data = array(
//                    'aid'   =>  $activityInfo['aid'],
//                    'shopId'    =>  $activityInfo['shopId'],
//                    'title' =>  $activityInfo['title'],
//                    'buyPeopleNum'  =>  0,
//                    'groupPeopleNum'    =>  $activityInfo['groupPeopleNum'],
//                    'limitNum'  =>  $activityInfo['limitNum'],
//                    'goodsId'   =>  $activityInfo['goodsId'],
//                    'tprice'    =>  $activityInfo['tprice'],
//                    'startTime' => $curTime,
//                    'endTime'   =>  date('Y-m-d H:i:s',strtotime("+" . $activityInfo['limitHour'] . " hours")),
//                    'createTime'    =>  $curTime,
//                    'state' =>  0,
//                    'isRefund'  =>  0,
//                    'pFlag' =>  1,
//                    'userId'    =>  $userId
//                );
//                $pid = $am->add($assemble_data);
//            }
//            //向用户和活动关联表插入数据
//            $data_arr = array(
//                'uid'           =>  $userId,
//                'pid'           =>  $pid,
//                'aid'           =>  $aid,
//                'shopId'        =>  empty($shopId)?$mod['shopId']:$shopId,
//                'orderId'       =>  $orderInfo['orderId'],
//                'createTime'    =>  date('Y-m-d H:i:s'),
//                'state'         =>  (empty($pid) || empty($assemble_info)) ? -3 : 0
//            );
//            $uarm->add($data_arr);
//
//            $awhere = array('pid'=>$pid);
////            $am->where($awhere)->setInc('buyPeopleNum',1);
//            $assemble_info = $am->where($awhere)->find();
//            $uarwhere = array('aid'=>$activityInfo['aid'],'pid'=>$pid);
//            if ($assemble_info['buyPeopleNum'] >= $assemble_info['groupPeopleNum']) {//拼团成功
//                /*$am->where($awhere)->save(array('state'=>1));
//                $uarm->where($uarwhere)->save(array('state'=>1));
//                $uar_orderid_arr = $uarm->where($uarwhere)->getField('orderId',true);
//                $om->where(array('orderId'=>array('in',$uar_orderid_arr)))->save(array('orderStatus'=>0));*/
//            } else if($assemble_info['endTime'] <= date('Y-m-d H:i:s')) {//时间过期，拼团失败
//                /*$am->where($awhere)->save(array('state'=>-1));
//                $uarm->where($uarwhere)->save(array('state'=>-1));*/
//            }
//
////            $rd['orderInfo'] = $orderInfo;
////            $rd['code'] = 0;
//            return returnData(array('orderInfo'=>$orderInfo));
//        }catch(Exception $e){
//            $this->rollback();
////            $rd['code'] = -1;
////            $rd['msg'] = '下单出错，请联系管理员!';
//            return returnData(array(),-1,'error',"下单出错，请联系管理员!");
//        }
////        return $rd;
//    }

    /**
     * PS:该逻辑copy上面注释的方法
     * 生成拼团订单
     * @param array $params
     * int userId 用户id
     * int aid 活动ID
     * int consigneeId 地址id
     * int isself 是否自提【0：不自提|1：自提】
     * int needreceipt 是否需要发票【0：不需要|1：需要】
     * int invoiceClient 发票id
     * string orderRemarks 订单备注
     * string remarks 智能备注
     * datetime dateTime requireTime 要求送达时间
     * int skuId 商品skuId
     * float goodsCnt 商品数量,默认为1
     * int goodsAttrId 商品属性id
     * int pid 拼团ID
     * int $pay_from 支付方式 (2：微信) 目前拼团只支持微信支付
     * @return array
     * */
    public function submitAssembleOrder(array $params)
    {
        $user_id = (int)$params['userId'];
        $addresss_id = $params['consigneeId'];
        $isself = $params['isself'];
        $needreceipt = $params['needreceipt'];
        $invoice_client = $params['invoiceClient'];
        $order_remarks = $params['orderRemarks'];
        $remarks = (string)$params['remarks'];
        $require_time = $params['requireTime'];
        $goods_cnt = $params['goodsCnt'];
        $sku_id = $params['skuId'];
        $pid = (int)$params['pid'];
        $pay_from = $params['payFrom'];

        vendor('RedisLock.RedisLock');
        $redis = new \Redis;
        $redis->connect(C('redis_host1'), C('redis_port1'));
        //$redis->connect('127.0.0.1',6379);
        $redisLock = \RedisLock::getInstance($redis);
        $redisLock->lock($user_id, 10);
        $users_module = new UsersModule();
        $users_detail = $users_module->getUsersDetailById($user_id, 'userId,userName,userPhone', 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $aid = $params['aid'];
        $assemble_module = new AssembleModule();
        $orders_module = new OrdersModule();
        $activity_detail = $assemble_module->getAssembleActiveDetailById($aid);
        if (empty($activity_detail)) {
            return returnData(false, -1, 'error', "拼团活动异常");
        }
        if ($activity_detail['activityStatus'] != 1) {
            return returnData(false, -1, 'error', "拼团活动未开启");
        }
        if ($pay_from != 2) {
            return returnData(false, -1, 'error', "拼团只支持微信支付方式");
        }
        $shop_id = $activity_detail['shopId'];
        $goods_id = $activity_detail['goodsId'];
        $now_time = date('Y-m-d H:i:s');
        if ($activity_detail['startTime'] > $now_time) {
            return returnData(false, -1, 'error', "活动尚未开始");
        }
        if ($activity_detail['endTime'] < $now_time) {
            return returnData(false, -1, 'error', "活动已结束");
        }
        if (!empty($pid)) {
            $verification_assemble_res = $assemble_module->verificationAssemble($pid, $user_id);
            if ($verification_assemble_res['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $verification_assemble_res['msg']);
            }
        }
        $verification_assemble_goods_status_res = $assemble_module->verificationAssembleGoodsStatus($goods_id, $sku_id);
        if ($verification_assemble_goods_status_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $verification_assemble_goods_status_res['msg']);
        }
        $verification_assemble_goods_stock_res = $assemble_module->verificationAssembleGoodsStock($goods_id, $sku_id, $goods_cnt);
        if ($verification_assemble_goods_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $verification_assemble_goods_stock_res['msg']);
        }
        $sku_spec_str = '';
        if (!empty($sku_id)) {
            $activity_goods_sku_detail = $assemble_module->getActivityGoodsSkuDetailByParams($aid, $goods_id, $sku_id);
            if (!empty($activity_goods_sku_detail)) {
                $activity_detail['tprice'] = $activity_goods_sku_detail['tprice'];
                $sku_spec_str = $activity_goods_sku_detail['sku_detail']['skuSpecAttr'];
                $activity_detail['goodsImg'] = $activity_goods_sku_detail['sku_detail']['skuGoodsImg'];
            }
        }
        $address_detail = array();
        if ($isself != 1) {
            $address_detail = $users_module->getUserAddressDetail($user_id, $addresss_id);
            if (empty($address_detail)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请选择收货地址");
            }
            $verification_address_res = $orders_module->verificationShopDistribution($user_id, $addresss_id, $shop_id);
            if (!$verification_address_res) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货地址不在店铺配送范围");
            }
        }
        $shop_module = new ShopsModule();
        $shop_detail = $shop_module->getShopsInfoById($shop_id, '*', 2);
        if (empty($shop_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺信息有误");
        }
        $order_no = $orders_module->getOrderAutoId();
        $trans = new Model();
        $trans->startTrans();
        $order_data = array();//创建订单信息
        $order_data["orderNo"] = $order_no;
        $order_data["shopId"] = $shop_id;
        $order_data['deliverType'] = (int)$shop_detail['deliveryType'];
        $order_data["userId"] = $user_id;
        $order_data["totalMoney"] = (float)$activity_detail['tprice'];
        $order_data['deliverMoney'] = (float)$shop_detail['deliveryMoney'];
        if ($isself == 1) {//自提
            $order_data['deliverMoney'] = 0;
            //自提订单不需要用户的收货地址而是取货的店铺地址
            $address_detail = [];
            $address_detail['areaId1'] = $shop_detail['areaId1'];
            $address_detail['areaId2'] = $shop_detail['areaId2'];
            $address_detail['areaId3'] = $shop_detail['areaId3'];
            $address_detail['setaddress'] = '';
            $address_detail['postCode'] = '';
            $address_detail['communityId'] = 0;
            $address_detail['userName'] = $users_detail['userName'];
            $address_detail['address'] = $shop_detail['shopAddress'];
            $address_detail['lat'] = $shop_detail['latitude'];
            $address_detail['lng'] = $shop_detail['longitude'];
            $address_detail['userPhone'] = $users_detail['userPhone'];
        } else {
            if ($order_data['totalMoney'] >= (float)$shop_detail['deliveryFreeMoney']) {
                $order_data['deliverMoney'] = 0;
            }
        }
        $order_data["payType"] = 1;
        $order_data["payFrom"] = $pay_from;
        $order_data["userName"] = $address_detail["userName"];
        $order_data["areaId1"] = $address_detail['areaId1'];
        $order_data["areaId2"] = $address_detail['areaId2'];
        $order_data["areaId3"] = $address_detail['areaId3'];
        $order_data["communityId"] = $address_detail["communityId"];
        //收货地址
        if ($isself == 1) {
            $order_data['userAddress'] = $address_detail['address'];
        } else {
            $address_detail['areaId1Name'] = $this->getAreaName($address_detail['areaId1']);
            $address_detail['areaId2Name'] = $this->getAreaName($address_detail['areaId2']);
            $address_detail['areaId3Name'] = $this->getAreaName($address_detail['areaId3']);
            $order_data['userAddress'] = '';
            if (handleCity($address_detail['areaId1Name'])) {
                $order_data['userAddress'] .= $address_detail['areaId1Name'] . ' ';
            }
            $order_data['userAddress'] .= $address_detail['areaId2Name'] . ' ';
            $order_data['userAddress'] .= $address_detail['areaId3Name'] . ' ';
            $order_data["userAddress"] .= $this->getCommunity($address_detail['communityId']) . $address_detail['setaddress'] . $address_detail["address"];
        }
        // $data["userAddress"] = (empty($addressInfo)?'':$addressInfo["paddress"]) . " " . (empty($addressInfo)?'':$addressInfo["address"]);
        $order_data["userPhone"] = $address_detail['userPhone'];
        $order_data['orderScore'] = getOrderScoreByOrderScoreRate($order_data["totalMoney"]);
        $order_data["orderRemarks"] = $order_remarks;
        $order_data["requireTime"] = $require_time;
        $order_data["invoiceClient"] = $invoice_client;//发票id
        $order_data["isInvoice"] = $order_data['invoiceClient'] > 0 ? 1 : 0;//是否需要发票[1:需要 0:不需要]
        $order_data["isAppraises"] = 0;
        $order_data["isSelf"] = $isself;
        if (!empty($shop_detail) && $shop_detail['commissionRate'] > 0) {
            $order_data["poundageRate"] = (float)$shop_detail['commissionRate'];
            $order_data["poundageMoney"] = WSTBCMoney($order_data["totalMoney"] * $order_data["poundageRate"] / 100, 0, 2);
        } else if ($GLOBALS['CONFIG']['poundageRate'] > 0) {
            $order_data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
            $order_data["poundageMoney"] = WSTBCMoney($order_data["totalMoney"] * $order_data["poundageRate"] / 100, 0, 2);
        } else {
            $order_data["poundageRate"] = 0;
            $order_data["poundageMoney"] = 0;
        }
        $order_data["realTotalMoney"] = $order_data['deliverMoney'] + $order_data['totalMoney'];
        $order_data["needPay"] = $order_data['realTotalMoney'];
        $order_data["createTime"] = date("Y-m-d H:i:s");
        $order_data["orderStatus"] = 15;
        $order_data["orderunique"] = WSTGetMillisecond() . $user_id;//不晓得干嘛的额
        $order_data["isPay"] = 0;
        if ($order_data['realTotalMoney'] <= 0) {
//            $order_data['needPay'] = 0.01;
//            $order_data['realTotalMoney'] = 0.01;
            $order_data['isPay'] = 1;
            $order_data['pay_time'] = date('Y-m-d H:i:s');
            $order_data['isReceivables'] = 2;//(psd)是否收款(0:待收款|1:预收款|2:已收款(全款))

        }
        $order_data['lat'] = $address_detail['lat'];
        $order_data['lng'] = $address_detail['lng'];
        $order_data['orderType'] = 2;
        $order_id = $orders_module->saveOrdersDetail($order_data, $trans);//创建订单
        if (empty($order_id)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "下单失败", "订单创建失败");
        }
        if ($isself == 1) {
            $create_res = $orders_module->createBootstrapCode($order_id, $trans);
            if (empty($create_res)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '自提码创建失败');
            }
        }
        $merge_data = array(
            'value' => $order_no,
            'realTotalMoney' => $order_data['realTotalMoney'],
        );
        $merge_res = $orders_module->addOrderMerge($merge_data, $trans);
        if (!$merge_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '订单合并记录失败');
        }
        $order_data['orderToken'] = md5($order_no);
        $update_token_res = $orders_module->updateOrderToken(array($order_no), $order_data['orderToken'], $trans);//更新订单的合并支付标识
        $update_set_delivery_money_res = true;
        if ($pay_from != 3) {
            $update_set_delivery_money_res = $orders_module->updateOrderSetDeliveryMoney(array($order_no), $order_data['deliverMoney'], $trans);//更新订单的setDeliveryMoney字段
        }
        if (!$update_token_res && $update_set_delivery_money_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '运费处理失败');
        }
        //处理订单商品
        $order_goods_info = array(
            'orderId' => $order_id,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
            'skuSpecAttr' => $sku_spec_str,
            'goodsNums' => $goods_cnt,
            'goodsPrice' => $activity_detail['tprice'],
            'goodsName' => $activity_detail['goodsName'],
            'goodsThums' => $activity_detail['goodsImg'],
            'remarks' => $remarks,
            'goods_type' => 1,
        );
        $order_goods_res = $orders_module->saveOrderGoods($order_goods_info, $trans);
        if (!$order_goods_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '订单商品明细添加失败');
        }
        $order_goods_info['goodsCnt'] = $goods_cnt;
        $dec_stock_res = reduceGoodsStockByRedis($order_goods_info, $trans);//减去商品库存
        if ($dec_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', $dec_stock_res['msg']);
        }
        $remind_res = $orders_module->addOrderRemind($order_id, $trans);//建立订单提醒记录
        if (!$remind_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '订单提醒记录失败');
        }
        //写入订单日志
        $content = '用户下单未支付';
        $log_params = [
            'orderId' => $order_id,
            'logContent' => $content,
            'logUserId' => $user_id,
            'logUserName' => '用户',
            'orderStatus' => -2,
            'payStatus' => 0,
            'logType' => 0,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        if ($order_data['realTotalMoney'] <= 0 || $pay_from == 4) {
            $log_params['orderStatus'] = 0;
            $log_params['payStatus'] = 1;
            $log_params['logContent'] = "下单成功";
        }
        $log_res = (new LogOrderModule())->addLogOrders($log_params, $trans);
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '订单日志记录失败');
        }
        $assemble_detail = $assemble_module->getAssembleDetailByPid($pid);
        if (empty($assemble_detail)) {
            $assemble_data = [
                'aid' => $aid,
                'shopId' => $shop_id,
                'title' => $activity_detail['title'],
                'buyPeopleNum' => 0,
                'groupPeopleNum' => $activity_detail['groupPeopleNum'],
                'limitNum' => $activity_detail['limitNum'],
                'goodsId' => $goods_id,
                'skuId' => $sku_id,
                'tprice' => $activity_detail['tprice'],
                'startTime' => $now_time,
                'endTime' => date('Y-m-d H:i:s', strtotime("+" . $activity_detail['limitHour'] . " hours")),
                'state' => 0,
                'isRefund' => 0,
                'pFlag' => 1,
                'userId' => $user_id,
                'assembleUserName' => $users_detail['userName'],
                'assembleUserPhone' => $users_detail['userPhone'],
            ];
            $pid = $assemble_module->saveAssemble($assemble_data, $trans);
            if (empty($pid)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '拼团记录失败');
            }
        }
        $activity_relation = array(
            'uid' => $user_id,
            'pid' => $pid,
            'aid' => $aid,
            'shopId' => $shop_id,
            'orderId' => $order_id,
            'state' => (empty($pid) || empty($assemble_detail)) ? -3 : 0
        );
        if ($order_data['isPay'] == 1) {
            $activity_relation['is_pay'] = 1;
        }
        $save_relation_res = $assemble_module->addUserActivityRelation($activity_relation, $trans);
        if (!$save_relation_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败', '用户拼团关联记录失败');
        }
        $this->completeAssemble($order_id);
        $return_data = array(
            'orderId' => $order_id,
            'orderToken' => $order_data['orderToken'],
            'orderNo' => strencode($order_no),
            'totalMoney' => strencode($order_data['realTotalMoney']),
        );
        $trans->commit();
        $redisLock->unlock($user_id);
        return returnData(array('orderInfo' => $return_data));
    }

    /**
     * 查询商品简单信息
     */
    public function getGoodsSimpInfo($goodsId, $goodsAttrId)
    {
        $sql = "SELECT g.*,sp.shopId,sp.shopName,sp.deliveryFreeMoney,sp.deliveryMoney,sp.deliveryStartMoney,sp.isInvoice,sp.serviceStartTime startTime,sp.serviceEndTime endTime,sp.deliveryType
				FROM __PREFIX__goods g, __PREFIX__shops sp
				WHERE g.shopId = sp.shopId AND g.goodsId = $goodsId AND g.isSale=1 AND g.goodsFlag = 1 AND g.goodsStatus = 1";
        $rs = $this->queryRow($sql);
        if (!empty($rs) && $rs['attrCatId'] > 0) {
            $sql = "select ga.id,ga.attrPrice,ga.attrStock,a.attrName,ga.attrVal,ga.attrId from __PREFIX__attributes a,__PREFIX__goods_attributes ga
			        where a.attrId=ga.attrId and a.catId=" . $rs['attrCatId'] . "
			        and ga.goodsId=" . $rs['goodsId'] . " and id=" . $goodsAttrId;
            $priceAttrs = $this->queryRow($sql);
            if (!empty($priceAttrs)) {
                $rs['attrId'] = $priceAttrs['attrId'];
                $rs['goodsAttrId'] = $priceAttrs['id'];
                $rs['attrName'] = $priceAttrs['attrName'];
                $rs['attrVal'] = $priceAttrs['attrVal'];
                $rs['shopPrice'] = $priceAttrs['attrPrice'];
                $rs['goodsStock'] = $priceAttrs['attrStock'];
            }
        }
        $rs['goodsAttrId'] = (int)$rs['goodsAttrId'];
        return $rs;

    }

    /**
     * 参与拼团下单(不入购物车)
     */
    public function joinAssembleOrder($USER)
    {

        $memberToken = I('memberToken', '', 'trim');//token
        $lat = I('lat', '', 'trim');
        $lng = I('lng', '', 'trim');

//        $loginName = I('loginName', '', 'trim');
        $goodsId = I('goodsId', '', 'intval');
        $goodsCnt = (float)I('goodsCnt');
        $goodsAttrId = I('goodsAttrId', '', 'intval');
        $skuId = I('skuId', 0);
        $curTime = date('Y-m-d H:i:s');

        //拼团ID
        $pid = I('pid', 0, 'intval');
//        $aid = I('aid',0,'intval');
//        $shopId = I('shopId',0,'intval');

        $asse_info = M('assemble')->where(array('pid' => $pid, 'pFlag' => 1))->find();
        if (empty($asse_info)) {
            return returnData(array(), -1, 'error', "该拼团不存在");
        }
        $aid = $asse_info['aid'];
        $shopId = $asse_info['shopId'];

        //获得活动信息
        $activityInfo = $this->getAssembleGoodsByAidAndGoodsid($aid, $goodsId);
        /*        if (empty($activityInfo)) return returnData(array(),1,'error',"商品未做拼团活动");
                if ($activityInfo['startTime'] > $curTime) return returnData(array(),2,'error',"活动尚未开始");
                if ($activityInfo['endTime'] < $curTime) return returnData(array(),3,'error',"活动已结束");
        */
        $shopId = $activityInfo['shopId'];
        if (!empty($pid) && !empty($aid) && !empty($shopId)) {
            $uar_data = M('user_activity_relation')->where(array('pid' => $pid, 'aid' => $aid, 'shopId' => $shopId, 'state' => 0))->find();
            if (empty($uar_data)) {
                return returnData(array(), -1, 'error', "该拼团已失效");
            }
        }

        //判断用户是否已经参与过活动，如果已经参与过，则不可再次参与
//        $uaDatasss = M('user_activity_relation')->where(array('aid'=>$activityInfo['aid'],'uid'=>$USER['userId'],'shopId'=>$activityInfo['shopId']))->find();
//        if (!empty($uaDatasss)) return array('code'=>-1, 'msg'=>"您已经参与过活动了，不可重复参与");

//        if (!checkShopDistribution($activityInfo['shopId'], $lng, $lat))
//            return array('code'=>4, 'msg'=>'不在配送范围内，不可购买');//不在配送范围内，不可购买

        $mod_goods = M("goods");
        if ($mod_goods->where("goodsId='{$goodsId}'")->find()['isShopPreSale'] == 1)
            return returnData(array(), '000083', 'error', "预售商品不可购买");

        $where['isSale'] = 1;
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['goodsId'] = $goodsId;
        $mod = $mod_goods->where($where)->find();

        if (empty($mod)) return returnData(array(), '000028', 'error', "商品不存在");
        if ($mod['goodsStock'] <= 0) return returnData(array(), '000074', 'error', "库存不足!");

        //后加sku判断
        if ($skuId > 0) {
            $systemSkuTab = M('sku_goods_system');
            $goodsSkuInfo = $systemSkuTab->where(['skuId' => $skuId])->find();
            if ($goodsSkuInfo['skuGoodsStock'] <= 0) {
                return returnData(array(), '000074', 'error', "库存不足!");
            }
        }

        /*
         * 2019-06-15 start
         * 判断属性库存是否存在
         * */
        if (!empty($goodsAttrId)) {
            $goodsAttrIdAttr = explode(',', $goodsAttrId);
            foreach ($goodsAttrIdAttr as $key => $val) {
                $goodsAttrInfo = M('goods_attributes')->where("goodsId='" . $goodsId . "' AND id='" . $val . "'")->find();
                if ($goodsAttrInfo['attrStock'] <= 0) return returnData(array(), '000074', 'error', "库存不足!");
            }
        }
        /*
         * 2019-06-15 end
         * */


        $rd = array('code' => -1);
        //$USER = session('WST_USER');
        $goodsmodel = D('Mendianapi/Goods');
        //$morders = D('Mendianapi/Orders');
        //$morders = $this;
        $totalMoney = 0;
        $totalCnt = 0;
        //$userId = (int)session('WST_USER.userId');
        $userId = intval($USER['userId']);

        $consigneeId = (int)I("consigneeId");
        //$payway = (int)I("payway");
        $isself = (int)I("isself");
        $needreceipt = (int)I("needreceipt");
        $orderunique = WSTGetMillisecond() . $userId;

        $goods = $goodsmodel->getGoodsSimpInfo($goodsId, $goodsAttrId);
        //后加sku
        $goods['skuId'] = 0;
        if ($skuId > 0) {
            $goods['skuId'] = $skuId;
            $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
            foreach ($replaceSkuField as $rk => $rv) {
                if (isset($goods[$rv])) {
                    $goods[$rv] = $goodsSkuInfo[$rk];
                }
            }
        }
        //核对商品是否符合购买要求
        if (empty($goods)) return returnData(array(), 5, 'error', "找不到指定的商品");
        if ($goods['goodsStock'] <= 0) return returnData(array(), 6, 'error', '对不起，商品 ' . $goods['goodsName'] . ' 库存不足');
        if ($goods['isSale'] != 1) return returnData(array(), 7, 'error', '对不起，商品 ' . $goods['goodsName'] . ' 已下架');

        $goods["cnt"] = $goodsCnt;
        $shopGoodsData = array(
            'shopgoods' => $goods,
            'deliveryFreeMoney' => $goods["deliveryFreeMoney"],//店铺免运费最低金额
            'deliveryMoney' => $goods["deliveryMoney"],//店铺免运费最低金额
            'totalCnt' => $goodsCnt,
            'totalMoney' => $goods["cnt"] * $activityInfo['tprice']
        );
        $this->startTrans();
        try {
            $orderInfo = $this->addOrders($userId, $consigneeId, $needreceipt, $shopGoodsData, $orderunique, $isself);
            $this->commit();

            $om = M('orders');
            $uarm = M('user_activity_relation');
            $aam = M('assemble_activity');
            $am = M('assemble');
            $assemble_info = $am->where(array('pid' => $pid))->find();
            if (empty($pid) || empty($assemble_info)) {
                $assemble_data = array(
                    'aid' => $activityInfo['aid'],
                    'shopId' => $activityInfo['shopId'],
                    'title' => $activityInfo['title'],
                    'buyPeopleNum' => 0,
                    'groupPeopleNum' => $activityInfo['groupPeopleNum'],
                    'limitNum' => $activityInfo['limitNum'],
                    'goodsId' => $activityInfo['goodsId'],
                    'tprice' => $activityInfo['tprice'],
                    'startTime' => $curTime,
                    'endTime' => date('Y-m-d H:i:s', strtotime("+" . $activityInfo['limitHour'] . " hours")),
                    'createTime' => $curTime,
                    'state' => 0,
                    'isRefund' => 0,
                    'pFlag' => 1,
                    'userId' => $userId
                );
                $pid = $am->add($assemble_data);
            }
            //向用户和活动关联表插入数据
            $data_arr = array(
                'uid' => $userId,
                'pid' => $pid,
                'aid' => $aid,
                'shopId' => empty($shopId) ? $mod['shopId'] : $shopId,
                'orderId' => $orderInfo['orderId'],
                'createTime' => date('Y-m-d H:i:s'),
                'state' => 0
            );
            $uarm->add($data_arr);

            $awhere = array('pid' => $pid);
            $am->where($awhere)->setInc('buyPeopleNum', 1);
            $assemble_info = $am->where($awhere)->find();
            $uarwhere = array('aid' => $activityInfo['aid'], 'pid' => $pid);
            if ($assemble_info['buyPeopleNum'] >= $assemble_info['groupPeopleNum']) {//拼团成功
                $am->where($awhere)->save(array('state' => 1));
                $uarm->where($uarwhere)->save(array('state' => 1));
                $uar_orderid_arr = $uarm->where($uarwhere)->getField('orderId', true);
                $om->where(array('orderId' => array('in', $uar_orderid_arr)))->save(array('orderStatus' => 0));
            } else if ($assemble_info['endTime'] <= date('Y-m-d H:i:s')) {//时间过期，拼团失败
                $am->where($awhere)->save(array('state' => -1));
                $uarm->where($uarwhere)->save(array('state' => -1));
            }

//            $rd['orderInfo'] = $orderInfo;
//            $rd['code'] = 0;
            return returnData(array('orderInfo' => $orderInfo));
        } catch (Exception $e) {
            $this->rollback();
//            $rd['code'] = -1;
//            $rd['msg'] = '下单出错，请联系管理员!';
            return returnData(array(), -1, 'error', "下单出错，请联系管理员!");
        }
//        return $rd;
    }

    //获取id社区名称
    static function getCommunity($communityId)
    {
        $where1["communityId"] = $communityId;
        $where1["isShow"] = 1;
        $where1["communityFlag"] = 1;
        $mod = M("communitys")->where($where1)->field(array("communityName"))->find();//获取用户社区名称
        return $mod["communityName"];
    }

    //获取城市名字
    static function getAreaName($areaIdx)
    {
        $areas = M("areas");
        $where2["areaId"] = $areaIdx;
        $areaIds = $areas->where($where2)->field(array("areaName"))->find();
        return $areaIds["areaName"];
    }

    /**
     * 生成订单
     */
    public function addOrders($userId, $consigneeId, $needreceipt, $shopgoods, $orderunique, $isself)
    {

        $orderInfos = array();
        $orderIds = array();
        $orderNos = array();
        $remarks = I("remarks");
        $users_module = new UsersModule();
        $users_detail = $users_module->getUsersDetailById($userId, '*', 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $addressInfo = UserAddressModel::getAddressDetails($consigneeId, $userId);

        $m = M('orderids');

        //获取会员奖励积分倍数
        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);

        //生成订单ID
        $orderSrcNo = $m->add(array('rnd' => microtime(true)));
        $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));
        //创建订单信息
        $data = array();
        $pshopgoods = $shopgoods["shopgoods"];
        $shopId = $pshopgoods["shopId"];
        $shop_info = D('Home/Shops')->getShopInfo($shopId);
        $data["orderNo"] = $orderNo;
        $data["shopId"] = $shopId;
        $deliverType = intval($pshopgoods["deliveryType"]);
        $data["userId"] = $userId;

        $data["orderFlag"] = 1;
        $data["totalMoney"] = $shopgoods["totalMoney"];
        if ($isself == 1) {//自提
            $deliverMoney = 0;
            //自提订单不需要用户的收货地址而是取货的店铺地址
            $addressInfo = [];
            $addressInfo['areaId1'] = $shop_info['areaId1'];
            $addressInfo['areaId2'] = $shop_info['areaId2'];
            $addressInfo['areaId3'] = $shop_info['areaId3'];
            $addressInfo['setaddress'] = '';
            $addressInfo['postCode'] = '';
            $addressInfo['communityId'] = 0;
            $addressInfo['userName'] = $shop_info['shopName'];
            $addressInfo['address'] = $shop_info['shopAddress'];
            $addressInfo['lat'] = $shop_info['latitude'];
            $addressInfo['lng'] = $shop_info['longitude'];
            $addressInfo['userPhone'] = $shop_info['shopTel'];
        } else {
            $deliverMoney = ($shopgoods["totalMoney"] < $shopgoods["deliveryFreeMoney"]) ? $shopgoods["deliveryMoney"] : 0;
        }
        //兼容统一运费 start
        $config = $GLOBALS['CONFIG'];
        if ($config['setDeliveryMoney'] == 2) {
            $deliverMoney = 0;
            if ($isself != 1) {
                if ($shopgoods["totalMoney"] < $config['deliveryFreeMoney']) {
                    $data['setDeliveryMoney'] = $config['deliveryMoney'];
                }
            }
        }
        //兼容统一运费 end
        $data["deliverMoney"] = $deliverMoney;
        $data["payType"] = 1;
        $data["payFrom"] = I('payFrom', 2);
        $data["deliverType"] = $deliverType;
        $data["userName"] = empty($addressInfo) ? '' : $addressInfo["userName"];
        $data["areaId1"] = empty($addressInfo) ? '' : $addressInfo["areaId1"];
        $data["areaId2"] = empty($addressInfo) ? '' : $addressInfo["areaId2"];
        $data["areaId3"] = empty($addressInfo) ? '' : $addressInfo["areaId3"];
        $data["communityId"] = empty($addressInfo) ? '' : $addressInfo["communityId"];

        //huihui 2020.5.3 18:22


        //收货地址
        $addressInfo['areaId1Name'] = $this->getAreaName($addressInfo['areaId1']);
        $addressInfo['areaId2Name'] = $this->getAreaName($addressInfo['areaId2']);
        $addressInfo['areaId3Name'] = $this->getAreaName($addressInfo['areaId3']);

        $data['userAddress'] = '';
        if (handleCity($addressInfo['areaId1Name'])) {
            $data['userAddress'] .= $addressInfo['areaId1Name'] . ' ';
        }
        $data['userAddress'] .= $addressInfo['areaId2Name'] . ' ';
        $data['userAddress'] .= $addressInfo['areaId3Name'] . ' ';
        $data["userAddress"] .= $this->getCommunity($data['communityId']) . $addressInfo['setaddress'] . $addressInfo["address"];


        // $data["userAddress"] = (empty($addressInfo)?'':$addressInfo["paddress"]) . " " . (empty($addressInfo)?'':$addressInfo["address"]);
        $data["userTel"] = empty($addressInfo) ? '' : $addressInfo["userTel"];
        $data["userPhone"] = empty($addressInfo) ? '' : $addressInfo["userPhone"];

        //$data['orderScore'] = floor($data["totalMoney"])*$rewardScoreMultiple;
        $data['orderScore'] = getOrderScoreByOrderScoreRate($data["totalMoney"]);
        $data["orderRemarks"] = I('orderRemarks');
        $data["requireTime"] = I("requireTime");
        $data["invoiceClient"] = (int)I('invoiceClient', 0);//发票id
        $data["isInvoice"] = $data['invoiceClient'] > 0 ? 1 : 0;//是否需要发票[1:需要 0:不需要]
        $data["isAppraises"] = 0;
        $data["isSelf"] = $isself;

        $isScorePay = (int)I("isScorePay", 0);
        $scoreMoney = 0;
        $useScore = 0;

        if (!empty($shop_info) && $shop_info['commissionRate'] > 0) {
            $data["poundageRate"] = (float)$shop_info['commissionRate'];
            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);
        } else if ($GLOBALS['CONFIG']['poundageRate'] > 0) {
            $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);
        } else {
            $data["poundageRate"] = 0;
            $data["poundageMoney"] = 0;
        }
        //判断是否使用发票
        if ($data['isInvoice'] == 1) {
            if (isset($shop_info['isInvoicePoint']) && !empty($shop_info['isInvoicePoint'])) {
                $shopgoods["totalMoney"] += $shopgoods["totalMoney"] * ($shop_info['isInvoicePoint'] / 100);
            }
        }
        $data["realTotalMoney"] = $shopgoods["totalMoney"] + $deliverMoney - $scoreMoney;
        $realTotalMoney = $shopgoods["totalMoney"] + $deliverMoney - $scoreMoney;
        $data["needPay"] = $shopgoods["totalMoney"] + $deliverMoney - $scoreMoney;

        $data["createTime"] = date("Y-m-d H:i:s");
        $data["orderStatus"] = 15;

        $data["orderunique"] = $orderunique;
        $data["isPay"] = 0;
        //由于可能出现0.01的情况所以需要判断这种情况
        if ($data['realTotalMoney'] <= 0) {
            $data['needPay'] = 0.01;
            $data['realTotalMoney'] = 0.01;
        }
        $data['lat'] = $addressInfo['lat'];
        $data['lng'] = $addressInfo['lng'];
        $data['orderType'] = 2;
        $morders = M('orders');
        $orderId = $morders->add($data);

        //订单创建成功则建立相关记录
        if ($orderId > 0) {

            if ($isself) {//如果为自提订单 生成提货码
                //生成提货码
                $mod_user_self_goods = M('user_self_goods');
                $mod_user_self_goods_add['orderId'] = $orderId;
                $mod_user_self_goods_add['source'] = $orderId . $userId . $shopId;
                $mod_user_self_goods_add['userId'] = $userId;
                $mod_user_self_goods_add['shopId'] = $shopId;
                $mod_user_self_goods->add($mod_user_self_goods_add);
            }

            if ($config['setDeliveryMoney'] == 2) {
                //统一运费
                $data['realTotalMoney'] += $data['setDeliveryMoney'];
            }
            M('order_merge')->add(array(
                'orderToken' => md5($orderNo),
                'value' => $orderNo,
                'createTime' => time(),
                'realTotalMoney' => $data['realTotalMoney']
            ));

            $morders->where(['orderId' => $orderId])->save(['orderToken' => md5($orderNo)]);

            //建立订单商品记录表
            $mog = M('order_goods');
            $sgoods = $pshopgoods;
            $data = array();
            $data["orderId"] = $orderId;
            $data["goodsId"] = $sgoods["goodsId"];
            $data["goodsAttrId"] = (int)$sgoods["goodsAttrId"];
            $data["skuId"] = (int)$sgoods["skuId"];
            //2019-6-14 start
            if ($sgoods["attrVal"] != '') $data["goodsAttrName"] = $sgoods["attrName"] . ":" . $sgoods["attrVal"];
            $data["goodsNums"] = $sgoods["cnt"];
            $data["goodsPrice"] = $sgoods["shopPrice"];
            $data["goodsName"] = $sgoods["goodsName"];
            $data["goodsThums"] = $sgoods["goodsThums"];
            $data['remarks'] = I('remarks', '');
            if (!empty($data['skuId'])) {
                $goodsSelfTab = M('sku_goods_self self');
                $selfSpec = $goodsSelfTab
                    ->join("left join wst_sku_spec sp on self.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=self.attrId")
                    ->where(['self.skuId' => $data['skuId'], 'self.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
                    ->order('sp.sort asc')
                    ->select();
                $skuSpecAttr = '';
                if (!empty($selfSpec)) {
                    foreach ($selfSpec as $value) {
                        $skuSpecAttr .= $value['attrName'] . '，';
                    }
                    $skuSpecAttr = rtrim($skuSpecAttr, '，');
                    $skuSpecAttr = mb_substr($skuSpecAttr, 0, -1, 'utf-8');
                }
                $data['skuSpecAttr'] = $skuSpecAttr;
            }
            $mog->add($data);

            //建立订单提醒
            $sql = "SELECT userId,shopId,shopName FROM __PREFIX__shops WHERE shopId=$shopId AND shopFlag=1  ";
            $users = $this->query($sql);
            $data = array();
            $morms = M('order_reminds');
            for ($i = 0; $i < count($users); $i++) {
                $data[$i]['orderId'] = $orderId;
                $data[$i]['userId'] = empty($users) ? '' : $users[$i]["userId"];
                $data[$i]['shopId'] = $shopId;
                $data[$i]['remindType'] = 0;
                $data[$i]['userType'] = 0;
                $data[$i]['createTime'] = date("Y-m-d H:i:s");
            }
            $morms->addAll($data);

            //修改库存
            $sgoods_cnt = gChangeKg($sgoods["goodsId"], $sgoods['cnt'], 1);
            $sql = "update __PREFIX__goods set goodsStock=goodsStock-" . $sgoods_cnt . " where goodsId=" . $sgoods["goodsId"];
            $this->execute($sql);

            //更新进销存系统商品的库存
            //updateJXCGoodsStock($sgoods["goodsId"],$sgoods['cnt'],1);

            if ((int)$sgoods["goodsAttrId"] > 0) {
                $sql = "update __PREFIX__goods_attributes set attrStock=attrStock-" . $sgoods_cnt . " where id=" . $sgoods["goodsAttrId"];
                $this->execute($sql);
            }

            if ($sgoods['skuId'] > 0) {
                $sql = "update __PREFIX__sku_goods_system set skuGoodsStock=skuGoodsStock-" . $sgoods_cnt . " where skuId=" . $sgoods["skuId"];
                $this->execute($sql);
            }

//            $data = array();
//            $data["orderId"] = $orderId;
//            $data["logContent"] = "用户下单未支付（超时会自动取消）";
//            $data["logUserId"] = $userId;
//            $data["logType"] = 0;
//            $data["logTime"] = date('Y-m-d H:i:s');
//            $mlogo = M('log_orders');
//            $mlogo->add($data);
            //写入订单日志
            $content = '用户下单未支付';
            $logParams = [
                'orderId' => $orderId,
                'logContent' => $content,
                'logUserId' => $userId,
                'logUserName' => '用户',
                'orderStatus' => -2,
                'payStatus' => 0,
                'logType' => 0,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            M('log_orders')->add($logParams);
        }
//        return array('orderId'=>$orderId,'orderToken'=>md5($orderNo), 'orderNo'=>strencode($orderNo), 'totalMoney'=>strencode($shopgoods["totalMoney"]));
        return array('orderId' => $orderId, 'orderToken' => md5($orderNo), 'orderNo' => strencode($orderNo), 'totalMoney' => strencode($realTotalMoney));
    }

    /**
     * 取消拼团订单
     * 原来的
     */
//    public function assembleOrderCancel_t($obj){
//        $userId = (int)$obj["userId"];
//        $orderId = (int)$obj["orderId"];
//        $rsdata = array('status'=>-1);
//        //判断订单状态，只有符合状态的订单才允许改变
//        $sql = "SELECT orderId,orderNo,orderStatus,useScore,payFrom,tradeNo,needPay FROM __PREFIX__orders WHERE orderId = $orderId and orderFlag = 1 and userId=".$userId;
//        $rsv = $this->queryRow($sql);
//        //$cancelStatus = array(0,1,2,-2);//未受理,已受理,打包中,待付款订单
//        $cancelStatus = array(15);//拼团
//        if(!in_array($rsv["orderStatus"], $cancelStatus))return $rsdata;
//        //如果是未受理和待付款的订单直接改为"用户取消【受理前】"，已受理和打包中的则要改成"用户取消【受理后-商家未知】"，后者要给商家知道有这么一回事，然后再改成"用户取消【受理后-商家已知】"的状态
//        $orderStatus = -6;//取对商家影响最小的状态
//        if($rsv["orderStatus"]==0 || $rsv["orderStatus"]==-2)$orderStatus = -1;
//        //if($orderStatus==-6 && I('rejectionRemarks')=='')return $rsdata;//如果是受理后取消需要有原因
//        $sql = "UPDATE __PREFIX__orders set orderStatus = ".$orderStatus." WHERE orderId = $orderId and userId=".$userId;
//        $rs = $this->execute($sql);
//
//        $sql = "select ord.deliverType, ord.orderId, og.goodsId ,og.goodsId, og.goodsNums,og.skuId,og.skuSpecAttr
//				from __PREFIX__orders ord , __PREFIX__order_goods og
//				WHERE ord.orderId = og.orderId AND ord.orderId = $orderId";
//        $ogoodsList = $this->query($sql);
//        //获取商品库存
//        for($i=0;$i<count($ogoodsList);$i++){
//            $sgoods = $ogoodsList[$i];
//            $sgoods_goodsNums = gChangeKg($sgoods["goodsId"],$sgoods['goodsNums'],1);
//            $sql="update __PREFIX__goods set goodsStock=goodsStock+".$sgoods_goodsNums." where goodsId=".$sgoods["goodsId"];
//            $this->execute($sql);
//
//            //更新进销存系统商品的库存
//            //updateJXCGoodsStock($sgoods["goodsId"],$sgoods['goodsNums'],0);
//        }
//        $sql="Delete From __PREFIX__order_reminds where orderId=".$orderId." AND remindType=0";
//        $this->execute($sql);
//
//        //活动未结束时，处理拼团人数
//        /*$userActivityRelation = M('user_activity_relation')->where("orderId = '" . $orderId . "' and uid = " . $userId . " and state = 0")->find();
//        if (!empty($userActivityRelation)) {
//            $am = M('assemble');
//            $awhere = array('aid'=>$userActivityRelation['aid'],'pid'=>$userActivityRelation['pid']);
//            $am->where($awhere)->setDec("buyPeopleNum",1);
//            M('user_activity_relation')->where("orderId = '" . $orderId . "' and uid = " . $userId)->delete();
//
//            $assemble_info = $am->where($awhere)->find();
//            if ($assemble_info['buyPeopleNum'] >= $assemble_info['groupPeopleNum']) {//拼团成功
//                $am->where($awhere)->save(array('state'=>1));
//            } else if($assemble_info['endTime'] <= date('Y-m-d H:i:s')) {//时间过期，拼团失败
//                $am->where($awhere)->save(array('state'=>-1));
//            }
//        }*/
//
//        //优惠券返还
//        $cres = cancelUserCoupon($orderId);
//        if(!$cres){
//            return $rsdata;
//        }
//        //END
//
//        //返还属性库存
//        returnAttrStock($orderId);
//        //退换已使用的积分----------------------------------
//        returnIntegral($orderId,$userId);
//        //返回秒杀库存
//        returnKillStock($orderId);
//        //收回奖励积分---------------------------------未确认收货是没有奖励积分过去的
//        //收回分销金
//        returnDistributionMoney($orderId);
//
//
//        //更新商品限制购买记录表
//        updateGoodsOrderNumLimit($orderId);
//
//        //返还sku库存
//        returnGoodsSkuStock($orderId);
//        //
//        //-----------------启动退款逻辑------------------------
//        if($rsv['payFrom'] == 1){
//            //支付宝
//        }elseif ($rsv['payFrom'] == 2){
//            //微信
//            order_WxPayRefund($rsv['tradeNo'],$rsv['needPay'],$orderId);//可整单退款
//        }else{
//            //余额
//            //更改订单为已退款  //------可增加事物
//            $orders = M('orders');
//            $save_orders['isRefund'] = 1;
//            $orderEdit = $orders->where("orderId = ".$rsv['orderId'])->save($save_orders);
//            if($orderEdit){
//                //加回用户余额
//                $userEditRes = M('users')->where("userId='".$userId."'")->setInc('balance',$rsv['needPay']);
//                if($userEditRes){
//                    //写入订单日志
//                    unset($data);
//                    $log_orders = M("log_orders");
//                    $data["orderId"] =  $orderId;
//                    $data["logContent"] =  "用户取消订单，发起余额退款：".$rsv['needPay'] . '元';
//                    $data["logUserId"] =  $userId;
//                    $data["logType"] =  "0";
//                    $data["logTime"] =  date("Y-m-d H:i:s");
//                    $log_orders->add($data);
//                }
//            }
//        }
//
//        $data = array();
//        $m = M('log_orders');
//        $data["orderId"] = $orderId;
////        $data["logContent"] = "用户已取消订单".(($orderStatus==-6)?"：".I('rejectionRemarks'):"");
//        $data["logContent"] = "用户已取消订单:拼团失败";
//        $data["logUserId"] = $userId;
//        $data["logType"] = 0;
//        $data["logTime"] = date('Y-m-d H:i:s');
//        $ra = $m->add($data);
//        $rsdata["status"] = $ra;
//        if($ra == 1){
//            /*发起退款通知*/
//            $push = D('Adminapi/Push');
//            $push->postMessage(8,$userId,$rsv['orderNo'],null);
//        }
//        return $rsdata;
//    }

    /**
     * 取消拼团订单
     * 现在正在用的
     * @param int $userId 用户id
     * @param int $orderId 订单id
     */
//    public function assembleOrderCancel(int $userId, int $orderId)
//    {
//        $userId = (int)$userId;
//        $orderId = (int)$orderId;
//        //判断订单状态，只有符合状态的订单才允许改变
//        $where = [];
//        $where['orderId'] = $orderId;
//        $where['userId'] = $userId;
//        $where['orderFlag'] = 1;
//        $rsv = M('orders')->where($where)->find();
////        $sql = "SELECT orderId,orderNo,orderStatus,useScore,payFrom,tradeNo,needPay FROM __PREFIX__orders WHERE orderId = $orderId and orderFlag = 1 and userId=".$userId;
////        $rsv = $this->queryRow($sql);
//        M()->startTrans();
//        //$cancelStatus = array(0,1,2,-2);//未受理,已受理,打包中,待付款订单
//        $cancelStatus = array(15);//拼团
//        if (!in_array($rsv["orderStatus"], $cancelStatus)) {
//            return returnData(false, -1, 'error', '取消失败，订单状态有误');
//        }
//        //如果是未受理和待付款的订单直接改为"用户取消【受理前】"，已受理和打包中的则要改成"用户取消【受理后-商家未知】"，后者要给商家知道有这么一回事，然后再改成"用户取消【受理后-商家已知】"的状态
//        $orderStatus = -6;//取对商家影响最小的状态
//        if ($rsv["orderStatus"] == 0 || $rsv["orderStatus"] == -2) {
//            $orderStatus = -1;
//        }
//        //if($orderStatus==-6 && I('rejectionRemarks')=='')return $rsdata;//如果是受理后取消需要有原因
//        $sql = "UPDATE __PREFIX__orders set orderStatus = " . $orderStatus . " WHERE orderId = $orderId and userId=" . $userId;
//        $rs = $this->execute($sql);
//        if (!$rs) {
//            M()->rollback();
//            return returnData(false, -1, 'error', '取消失败，更改订单状态失败');
//        }
//        $sql = "select ord.deliverType, ord.orderId, og.goodsId ,og.goodsId, og.goodsNums,og.skuId,og.skuSpecAttr
//				from __PREFIX__orders ord , __PREFIX__order_goods og
//				WHERE ord.orderId = og.orderId AND ord.orderId = $orderId";
//        $ogoodsList = $this->query($sql);
//        //获取商品库存
//        for ($i = 0; $i < count($ogoodsList); $i++) {
//            $sgoods = $ogoodsList[$i];
//            $sgoods_goodsNums = gChangeKg($sgoods["goodsId"], $sgoods['goodsNums'], 1);
//            $sql = "update __PREFIX__goods set goodsStock=goodsStock+" . $sgoods_goodsNums . " where goodsId=" . $sgoods["goodsId"];
//            $this->execute($sql);
//
//            //更新进销存系统商品的库存
////            updateJXCGoodsStock($sgoods["goodsId"],$sgoods['goodsNums'],0);
//        }
//        $sql = "Delete From __PREFIX__order_reminds where orderId=" . $orderId . " AND remindType=0";
//        $this->execute($sql);
//
//        //活动未结束时，处理拼团人数
//        /*$userActivityRelation = M('user_activity_relation')->where("orderId = '" . $orderId . "' and uid = " . $userId . " and state = 0")->find();
//        if (!empty($userActivityRelation)) {
//            $am = M('assemble');
//            $awhere = array('aid'=>$userActivityRelation['aid'],'pid'=>$userActivityRelation['pid']);
//            $am->where($awhere)->setDec("buyPeopleNum",1);
//            M('user_activity_relation')->where("orderId = '" . $orderId . "' and uid = " . $userId)->delete();
//
//            $assemble_info = $am->where($awhere)->find();
//            if ($assemble_info['buyPeopleNum'] >= $assemble_info['groupPeopleNum']) {//拼团成功
//                $am->where($awhere)->save(array('state'=>1));
//            } else if($assemble_info['endTime'] <= date('Y-m-d H:i:s')) {//时间过期，拼团失败
//                $am->where($awhere)->save(array('state'=>-1));
//            }
//        }*/
//
//        //优惠券返还
//        /*$cres = cancelUserCoupon($orderId);
//        if(!$cres){
//            return $rsdata;
//        }*/
//        //END
//
//        //返还属性库存
//        returnAttrStock($orderId);
//        //退换已使用的积分----------------------------------
////        returnIntegral($orderId,$userId);
//        //返回秒杀库存
////        returnKillStock($orderId);
//        //收回奖励积分---------------------------------未确认收货是没有奖励积分过去的
//        //收回分销金
////        returnDistributionMoney($orderId);
//
//
//        //更新商品限制购买记录表
////        updateGoodsOrderNumLimit($orderId);
//
//        //返还sku库存
//        returnGoodsSkuStock($orderId);
//        //
//        //-----------------启动退款逻辑------------------------
//        if ($rsv['payFrom'] == 1) {
//            //支付宝
//        } elseif ($rsv['payFrom'] == 2) {
//            //微信
//            $loginUserInfo = [
//                'user_id' => $userId,
//                'user_username' => '用户',
//            ];
//            $cancelRes = order_WxPayRefund($rsv['tradeNo'], $orderId, $rsv['orderStatus'], 0, $loginUserInfo);//可整单退款
//            if ($cancelRes == -3) {
//                M()->rollback();
//                return returnData(null, -1, 'error', '取消失败，微信退款失败');
//            }
//        } elseif ($rsv['payFrom'] == 3) {
//            //余额
//            //更改订单为已退款  //------可增加事物
//            $orders = M('orders');
//            $save_orders['isRefund'] = 1;
//            $orderEdit = $orders->where("orderId = " . $rsv['orderId'])->save($save_orders);
//            if ($orderEdit) {
//                //加回用户余额
//                //$userEditRes = M('users')->where("userId='".$userId."'")->setInc('balance',$orderInfo['needPay']);
//                $balance = M('users')->where(['userId' => $rsv['userId']])->getField('balance');
//                $balance += $rsv['realTotalMoney'];
//                $refundAmount = $rsv['realTotalMoney'];
//                if (in_array($rsv['orderStatus'], [0, 13, 14])) {
//                    $balance += $rsv['deliverMoney'];
//                    $refundAmount += $rsv['deliverMoney'];
//                }
//                $userEditRes = M('users')->where(['userId' => $rsv['userId']])->save(['balance' => $balance]);
//                if ($userEditRes) {
//                    //写入订单日志
//                    unset($data);
////                    $log_orders = M("log_orders");
////                    $data["orderId"] = $orderId;
////                    $data["logContent"] = "用户取消订单，发起余额退款：" . $refundAmount . '元';
////                    $data["logUserId"] = $userId;
////                    $data["logType"] = "0";
////                    $data["logTime"] = date("Y-m-d H:i:s");
////                    $log_orders->add($data);
//                    $content = "用户取消订单，发起余额退款：" . $refundAmount . '元';
//                    $logParams = [
//                        'orderId' => $orderId,
//                        'logContent' => $content,
//                        'logUserId' => $userId,
//                        'logUserName' => '用户',
//                        'orderStatus' => -6,
//                        'payStatus' => 2,
//                        'logType' => 0,
//                        'logTime' => date('Y-m-d H:i:s'),
//                    ];
//                    M('log_orders')->add($logParams);
//                }
//            }
//        }
//
////        $data = array();
////        $m = M('log_orders');
////        $data["orderId"] = $orderId;
//////        $data["logContent"] = "用户已取消订单".(($orderStatus==-6)?"：".I('rejectionRemarks'):"");
////        $data["logContent"] = "用户已取消订单:拼团失败";
////        $data["logUserId"] = $userId;
////        $data["logType"] = 0;
////        $data["logTime"] = date('Y-m-d H:i:s');
////        $ra = $m->add($data);
////        $rsdata["status"] = $ra;
//        $content = "用户已取消订单:拼团失败";
//        $logParams = [
//            'orderId' => $orderId,
//            'logContent' => $content,
//            'logUserId' => $userId,
//            'logUserName' => '用户',
//            'orderStatus' => -6,
//            'payStatus' => 2,
//            'logType' => 0,
//            'logTime' => date('Y-m-d H:i:s'),
//        ];
//        $logId = M('log_orders')->add($logParams);
//        if ($logId) {
//            /*发起退款通知*/
//            $push = D('Adminapi/Push');
//            $push->postMessage(8, $userId, $rsv['orderNo'], null);
//        }
//        //$rsdata["status"] = true;
//        M()->commit();
//        return returnData(true);
//    }

    /**
     * 取消拼团订单 原代码在上面注释的位置
     * @param int $user_id 用户id
     * @param int $order_id 订单id
     * @return array
     */
    public function assembleOrderCancel(int $user_id, int $order_id)
    {
        $order_module = new OrdersModule();
        $order_detail = $order_module->getOrderInfoById($order_id, "*", 2);
        if (!in_array($order_detail["orderStatus"], array(15, 0))) {
            if (in_array($order_detail['orderStatus'], array(-1, -6))) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败，已取消订单不能重复取消', '订单状态有误');
            }
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败，已受理订单不能取消', '订单状态有误');
        }
        $order_status = -6;//取对商家影响最小的状态
        if ($order_detail["orderStatus"] == 0 || $order_detail["orderStatus"] == -2) {
            $order_status = -1;
        }
        $trans = new Model();
        $trans->startTrans();
        $order_data = array(
            'orderId' => $order_detail['orderId'],
            'orderStatus' => $order_status,
        );
        $save_order_res = $order_module->saveOrdersDetail($order_data, $trans);
        if (empty($save_order_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '订单状态更改失败');
        }
        $order_goods = $order_module->getOrderGoodsList($order_id, 'og.*', 2);
        $goods_module = new GoodsModule();
        foreach ($order_goods as $item) {
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $goods_cnt = $item['goodsNums'];
            $stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
            $return_stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $stock, 1, 2, $trans);
            if ($return_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '库存返还失败');
            }
        }
        if ($order_detail['orderStatus'] == 0) {
            if ($order_detail['payFrom'] == 1) {//支付宝
                //支付宝
                $payModule = new PayModule();
                $aliPayRefundRes = $payModule->aliPayRefund($order_detail['tradeNo'], $order_detail['realTotalMoney']);
                if ($aliPayRefundRes['code'] != 0) {
                    $trans->rollback();
                    return returnData(null, -1, 'error', '取消失败，支付宝退款失败');
                }
                //复制以前的代码,按照以前的逻辑写
                //写入订单日志
                $logParams = [
                    'orderId' => $order_id,
                    'logContent' => "用户取消订单，发起支付宝退款：{$order_detail['realTotalMoney']}元",
                    'logUserId' => $user_id,
                    'logUserName' => '用户',
                    'orderStatus' => -6,
                    'payStatus' => 2,
                    'logType' => 0,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
            if ($order_detail['payFrom'] == 2) {//微信
                //微信
                $login_userinfo = array(
                    'user_id' => $user_id,
                    'user_username' => '用户',
                );
                $cancel_res = order_WxPayRefund($order_detail['tradeNo'], $order_id, $order_detail['orderStatus'], 0, $login_userinfo);
                if ($cancel_res == -3) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '微信退款失败');
                }
            }
        }

        $content = "用户已取消订单:拼团失败";
        $log_params = [
            'orderId' => $order_id,
            'logContent' => $content,
            'logUserId' => $user_id,
            'logUserName' => '用户',
            'orderStatus' => -6,
            'payStatus' => 2,
            'logType' => 0,
        ];
        $log_res = (new LogOrderModule())->addLogOrders($log_params, $trans);
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '订单日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 编辑订单
     * @param $orderId
     * @param $tradeNo
     */
    public function updateOrder($orderId, $tradeNo)
    {
        return M('orders')->where('orderId = ' . $orderId)->save(array('tradeNo' => $tradeNo));
    }

    /**
     * 猜你喜欢
     * 拼团
     * //多门店业务参数
     * @param int $shopId 店铺id
     * @param int $num 读取数量(默认为10)
     * //多商户业务参数
     * @param int $num 读取数量(默认为10)
     * @param int $adcode 区县id
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return array
     */
    public function getAssembleGuessYouLikeGoods($shopId, $num = 10, $adcode, $lat, $lng)
    {
        $curTime = date('Y-m-d H:i:s');
        $data = [];
        $field = 'activity.aid,activity.shopId,activity.title,activity.groupPeopleNum,activity.limitNum,activity.goodsId,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour,activity.aFlag,activity.activityStatus,';
        $field .= 'goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.goodsDesc,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.integralReward,goods.buyNum,goods.spec,goods.unit';
        if (!empty($shopId)) {
            $where = array();
            $where['activity.startTime'] = array('LT', $curTime);
            $where['activity.endTime'] = array('GT', $curTime);
            $where['activity.activityStatus'] = 1;
            $where['activity.shopId'] = $shopId;
            $where['activity.aFlag'] = 1;
            $where['goods.isSale'] = 1;
            $where['goods.goodsFlag'] = 1;
            $where['goods.goodsStatus'] = 1;
            $data = M('goods goods')
                ->join('left join wst_assemble_activity activity on activity.goodsId = goods.goodsId')
                ->where($where)
                ->field($field)
                ->order('rand()')
                ->limit($num)
                ->select();
        }
        if (empty($shopId) && !empty($adcode) && !empty($lat) && !empty($lng)) {
            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where = " shops.shopStatus=1 and shops.shopFlag=1 and shops.shopAtive=1 and shops.serviceStartTime <= '{$newTime}' and shops.serviceEndTime >= '{$newTime} '";
            $where .= " and activity.startTime <= '{$curTime}' and activity.endTime >= '{$curTime}' and activity.activityStatus=1 and activity.aFlag=1 ";
            $where .= " and communitys.areaId3={$adcode} ";
            $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-shops.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(shops.latitude*PI()/180)*POW(SIN(($lng*PI()/180-shops.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance";
            $sql = "select $field from __PREFIX__shops shops ";
            $sql .= " left join wst_shops_communitys communitys on shops.shopId=communitys.shopId ";
            $sql .= " left join __PREFIX__goods goods on goods.shopId=shops.shopId ";
            $sql .= " left join __PREFIX__assemble_activity activity on goods.goodsId = activity.goodsId ";
            $sql .= " where $where group by activity.aid order by rand() limit $num ";
            $data = $this->query($sql);
            //多商户验证配送范围
            $returnData = [];
            $countData = count($data);
            for ($i = 0; $i < $countData; $i++) {
                $shopId = $data[$i]['shopId'];
                if (checkShopDistribution($shopId, $lng, $lat)) {
                    $returnData[] = $data[$i];
                }
            }
            $data = $returnData;
        }
        $data = handleNewPeople($data);
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $goodsInfo = $this->getActivityGoods($val['aid']);
                $data[$key]['hasGoodsSku'] = $goodsInfo['hasGoodsSku'];
                $data[$key]['goodsSku'] = $goodsInfo['goodsSku'];
            }
        }
        return returnData($data);
    }

    /**
     * 获取拼团剩余人数
     * @param $orderId
     */
    public function getSurplusPeopleNum($orderId)
    {
        /*$where['uar.orderId'] = $orderId;
        $assemble_info = M('user_activity_relation as uar')
            ->join('left join wst_assemble as a on uar.pid = a.pid')
            ->where($where)
            ->field('a.buyPeopleNum,a.groupPeopleNum')
            ->find();
        $surplusPeopleNum = 0;
        if (!empty($assemble_info)) {
            $surplusPeopleNum = intval($assemble_info['groupPeopleNum'] - $assemble_info['buyPeopleNum']);
        }
        return $surplusPeopleNum;*/

        $uar_info = M('user_activity_relation')->where(array('orderId' => $orderId))->find();
        $groupPeopleNum = M('assemble')->where(array('pid' => $uar_info['pid']))->getField('groupPeopleNum');
        $uar_where = array(
            'uar.pid' => $uar_info['pid'],
            'uar.aid' => $uar_info['aid'],
            'uar.shopId' => $uar_info['shopId'],
            'o.isPay' => 1,
//            'o.isRefund' => 0,
//            'o.orderStatus' => array(' in ', '0,15')
        );
        $buyNum = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->count();
        $buyNum = empty($buyNum) ? 0 : $buyNum;
        return $groupPeopleNum - $buyNum;
    }

    /**
     * 获得当前拼团购买人数
     * @param $orderId
     */
    public function getBuyPeopleNum($orderId)
    {
        $uar_info = M('user_activity_relation')->where(array('orderId' => $orderId))->find();
        return M('assemble')->where(array('pid' => $uar_info['pid']))->getField('buyPeopleNum');
    }

    /**
     * 获得当前拼团用户列表
     * @param $orderId
     */
    public function getCurrentAssembleUserList($orderId)
    {
        $user_activity_relation_m = M('user_activity_relation');
        $user_activity_relation_info = $user_activity_relation_m->where(array('orderId' => $orderId))->find();
//        $uid_arr = $user_activity_relation_m->where(array('pid'=>$user_activity_relation_info['pid']))->order('createTime asc')->getField('uid',true);
        $uar_where = array(
            'uar.pid' => $user_activity_relation_info['pid'],
            'uar.aid' => $user_activity_relation_info['aid'],
            'uar.shopId' => $user_activity_relation_info['shopId'],
            'uar.is_pay' => 1,
            'o.isPay' => 1,
            //'o.isRefund' => 0,
            //'o.orderStatus' => array(' in ', '0,15')
        );
        $uid_arr = array();
//        $uid_arr = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->getField('uar.uid', true);
        $uar_list = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->field('uar.uid')->where($uar_where)->order('uar.createTime asc')->select();
        if (!empty($uar_list)) {
            foreach ($uar_list as $v) {
                $uid_arr[] = $v['uid'];
            }
        }
        $user_list = array();
        if (!empty($uid_arr)) {
            $uid_str = implode(',', $uid_arr);
            $user_list = M('users as u')->join('wst_user_activity_relation as uar on uar.uid = u.userId')->field('u.userId,userName,u.userPhoto,u.userPhone')->where("u.userId in($uid_str) and uar.pid = " . $user_activity_relation_info['pid'] . ' and uar.is_pay=1')->order('uar.createTime asc')->select();
        }
        return $user_list;
    }

    /**
     * 完成拼团
     * @param $orderId
     */
    public function completeAssemble($orderId)
    {

//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."):开始 \r\n");
//        fclose($myfile);
//
//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."),参数:orderId = $orderId \r\n");
//        fclose($myfile);

        $om = M('orders');
        $uarm = M('user_activity_relation');
        $aam = M('assemble_activity');
        $am = M('assemble');
        $uarm_one_data = $uarm->where(array('orderId' => $orderId))->find();

//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."),sql_1:".M()->getLastSql()." \r\n");
//        fclose($myfile);
//
//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."),数据_1:".json_encode($uarm_one_data)." \r\n");
//        fclose($myfile);

        $asse_info = $am->where(array('pid' => $uarm_one_data['pid']))->find();

//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."),sql_2:".M()->getLastSql()." \r\n");
//        fclose($myfile);
//
//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."),数据_2:".json_encode($asse_info)." \r\n");
//        fclose($myfile);

        if (!empty($asse_info)) {
            //订单状态标记为拼团（15）
            $om->where(array('orderId' => $orderId))->save(array('orderStatus' => 15));

//            $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//            fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_3:" . M()->getLastSql() . " \r\n");
//            fclose($myfile);

            if ($asse_info['state'] == 0) {
                //订单状态标记为拼团（15）
//                $om->where(array('orderId' => $orderId))->save(array('orderStatus' => 15));

//                $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_3:" . M()->getLastSql() . " \r\n");
//                fclose($myfile);

                //如果是开团人，则拼团状态标记为拼团中
                if ($uarm_one_data['state'] == -3) {
                    $uarm->where(array('orderId' => $orderId))->save(array('state' => 0));

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_4:" . M()->getLastSql() . " \r\n");
//                    fclose($myfile);

                    $uarm_one_data = $uarm->where(array('orderId' => $orderId))->find();

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_5:" . M()->getLastSql() . " \r\n");
//                    fclose($myfile);

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),数据_5:" . json_encode($uarm_one_data) . " \r\n");
//                    fclose($myfile);
                }
                //拼团未完成
                if ($uarm_one_data['state'] == 0) {
                    $uar_where = array(
                        'uar.pid' => $uarm_one_data['pid'],
                        'uar.aid' => $uarm_one_data['aid'],
                        'uar.shopId' => $uarm_one_data['shopId'],
                        'o.isPay' => 1,
                        'o.isRefund' => 0,
                        'o.orderStatus' => array(' in ', '0,15')
                    );

                    //验证拼团是否已过期，如果过期，则退款，并取消订单
                    /*$assemble_info = $am->where(array('pid' => $uarm_one_data['pid']))->find();
                    if (!empty($assemble_info) && $assemble_info['endTime'] <= date('Y-m-d H:i:s')) {//拼团失败
                        $where_t = array(
                            'pid' => $uarm_one_data['pid'],
                            'aid' => $uarm_one_data['aid'],
                            'shopId' => $uarm_one_data['shopId'],
                        );
                        $uarm->where($where_t)->save(array('state' => -1));
                        //退款，取消拼团订单
                        //已支付拼团订单
                        $order_list = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->field('o.*')->where($uar_where)->select();
                        if (!empty($order_list)) {
                            //退款，并取消拼团订单
                            foreach ($order_list as $v) {
                                $this->assembleOrderCancel(array('userId' => $v['userId'], 'orderId' => $v['orderId']));
                            }
                        }
                    }*/

                    $am->where(array('pid' => $uarm_one_data['pid']))->setInc('buyPeopleNum', 1);

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_6:" . M()->getLastSql() . " \r\n");
//                    fclose($myfile);

                    //获取成团人数
                    $groupPeopleNum = $am->where(array('pid' => $uarm_one_data['pid']))->getField('groupPeopleNum');

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_7:" . M()->getLastSql() . " \r\n");
//                    fclose($myfile);
//
//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),数据_7: groupPeopleNum = $groupPeopleNum \r\n");
//                    fclose($myfile);

                    $buyNum = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->count();

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_8:" . M()->getLastSql() . " \r\n");
//                    fclose($myfile);

//                    $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                    fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),数据_8: buyNum = $buyNum \r\n");
//                    fclose($myfile);

                    if ($buyNum >= $groupPeopleNum) {//拼团成功
                        $assembel_success_order = array();
//                    $assembel_success_order = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->getField('uar.orderId', true);
                        $uar_list = M('user_activity_relation as uar')->join('wst_orders as o on o.orderId = uar.orderId')->where($uar_where)->field('uar.orderId')->select();
                        if (!empty($uar_list)) {
                            foreach ($uar_list as $uarv) {
                                $assembel_success_order[] = $uarv['orderId'];
                            }
                        }

//                        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                        fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_9:" . M()->getLastSql() . " \r\n");
//                        fclose($myfile);
//
//                        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                        fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),数据_9:" . json_encode($assembel_success_order) . " \r\n");
//                        fclose($myfile);

                        $orderId_str = implode(',', $assembel_success_order);
                        $uarm->where("orderId in($orderId_str)")->save(array('state' => 1));

//                        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                        fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_10:" . M()->getLastSql() . " \r\n");
//                        fclose($myfile);

                        $om->where("orderId in($orderId_str)")->save(array('orderStatus' => 0));//将订单标记为待受理

//                        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                        fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_11:" . M()->getLastSql() . " \r\n");
//                        fclose($myfile);

                        //修改拼团状态为拼团成功
                        $am->where(array('pid' => $uarm_one_data['pid']))->save(array('state' => 1));

//                        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                        fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),sql_12:" . M()->getLastSql() . " \r\n");
//                        fclose($myfile);

                    }
                }
            } else {//拼团已完成，则退款，并取消订单

//                $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//                fwrite($myfile, "微信回调执行拼团(" . date('Y-m-d H:i:s') . "),最后:拼团已完成，则退款，并取消订单 \r\n");
//                fclose($myfile);

                //$this->assembleOrderCancel(array('userId' => $uarm_one_data['uid'], 'orderId' => $orderId));
                $this->assembleOrderCancel($uarm_one_data['uid'], $orderId);
            }
        }

//        $myfile = fopen("completeAssemble.txt", "a+") or die("Unable to open file!");
//        fwrite($myfile, "微信回调执行拼团(".date('Y-m-d H:i:s')."):结束 \r\n");
//        fclose($myfile);
    }

    /*
     * PS:重写用户拼团详情,接口返回格式按照以前的,方法体中调用的方法皆为之前已存在的,没什么错误,就不修改了
     * 用户拼团详情
     * @param int $orderId 订单id
     * */
    public function getUserAssembleOrderDetail(int $userId, int $orderId)
    {
        $returnData = $data = [
            'orderDetail' => [],
            'orderGoods' => [],
            'surplusPeopleNum' => 0,//拼团剩余人数
            'currentAssembleUserList' => [],//当前拼团用户列表
            'buyPeopleNum' => 0//获得当前拼团购买人数
        ];
        $orderDetails = $this->getOrdersDetails($orderId);
        if (empty($orderDetails) || $orderDetails[0]['userId'] != $userId) {
            return returnData($returnData);
        }
        $returnData['orderDetail'] = $orderDetails;
        $returnData['orderGoods'] = $this->getOrdersGoods($orderId);
        $returnData['surplusPeopleNum'] = $this->getSurplusPeopleNum($orderId);
        $returnData['currentAssembleUserList'] = $this->getCurrentAssembleUserList($orderId);
        $returnData['buyPeopleNum'] = $this->getBuyPeopleNum($orderId);
        return returnData($returnData);
    }

    /**
     * 获取活动详情
     * @param int $pid
     */
    public function getUserAssembleGoodsDetail($pid)
    {
        $where = [];
        $where['asse.pid'] = $pid;
        $where['activity.aFlag'] = 1;
        $field = 'activity.aid,activity.shopId,activity.title,activity.groupPeopleNum,activity.limitNum,activity.goodsId,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour,activity.aFlag,activity.activityStatus,';
        $field .= 'goods.goodsName,goods.goodsImg,goods.goodsThums,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.goodsDesc,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.integralReward,goods.buyNum,goods.spec,';
        $field .= 'ur.state as assembleState,asse.pid,asse.buyPeopleNum,asse.startTime assembleStartTime,asse.endTime as assembleEndTime,asse.userId assembleUserId,asse.assembleUserName,asse.assembleUserPhone';
        $res = M('assemble_activity activity')
            ->join('left join wst_goods goods on goods.goodsId = activity.goodsId')
            ->join('left join wst_assemble asse on asse.aid = activity.aid')
            ->join('left join wst_user_activity_relation ur on ur.pid = asse.pid')
            ->where($where)
            ->field($field)
            ->find();
        $res['assembleUserList'] = M('users as u')->join('wst_user_activity_relation as uar on uar.uid = u.userId')->field('u.userId,userName,u.userPhoto,u.userPhone,uar.state')->where("uar.is_pay=1 and uar.pid = " . $pid)->order('uar.createTime asc')->select();
        $activityGoodsSku = M('assemble_activity_goods_sku ac_goods_sku')
            ->join("left join wst_sku_goods_system system on system.skuId=ac_goods_sku.skuId")
            ->where($where)
            ->field($field)
            ->select();
        $goodsInfo['hasGoodsSku'] = -1;//是否有拼团sku【0：无|1：有】
        $goodsInfo['goodsSku'] = [];
        if (!empty($activityGoodsSku)) {
            $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
            $goodsSelfTab = M('sku_goods_self self');
            $skuList = [];
            foreach ($activityGoodsSku as $key => $value) {
                $spec = [];
                $spec['skuId'] = $value['skuId'];
                foreach ($replaceSkuField as $rek => $rev) {
                    if (isset($value[$rek])) {
                        $spec['systemSpec'][$rek] = $value[$rek];
                    }
                    if (in_array($rek, ['dataFlag', 'addTime'])) {
                        continue;
                    }
                    if ((int)$spec['systemSpec'][$rek] == -1) {
                        //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                        $spec['systemSpec'][$rek] = $goodsInfo[$rev];
                    }
                }
                $spec['systemSpec']['tprice'] = $value['tprice'];
                $selfSpec = $goodsSelfTab
                    ->join("left join wst_sku_spec sp on self.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=self.attrId")
                    ->where(['self.skuId' => $value['skuId'], 'self.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                    ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
                    ->order('sp.sort asc')
                    ->select();
                if (empty($selfSpec)) {
                    unset($activityGoodsSku[$key]);
                    continue;
                }
                $spec['selfSpec'] = $selfSpec;
                $skuList[] = $spec;
            }
            if (count($skuList) > 0) {
                $goodsInfo['hasGoodsSku'] = 1;
            }
        }
        $res['goodsInfo'] = $goodsInfo;
        return returnData($res);
    }
}
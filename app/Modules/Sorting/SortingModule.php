<?php
/**
 * 分拣
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-18
 * Time: 14:34
 */

namespace App\Modules\Sorting;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\SortingGoodsRelationModel;
use App\Models\SortingModel;
use App\Models\SortingPersonnelModel;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\PSD\LineModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;
use Think\Model;

class SortingModule extends BaseModel
{
    /**
     * 分拣员-登陆
     * @param int $id 分拣员id
     * @return string $token
     * */
    public function toLogin(int $id)
    {
        $token = '';
        $detail = $this->getSortingPersonnelById($id);
        if (empty($detail)) {
            return $token;
        }
        $token_tag = md5(uniqid('', true) . $detail['account'] . $detail['password'] . (string)microtime());
        if (userTokenAdd($token_tag, $detail)) {
            $token = $token_tag;
        }
        return $token;
    }

    /**
     * 分拣员-详情-根据账号查找
     * @param string $account 账号
     * @param string $field 表字段
     * @return array $result
     * */
    public function getSortingPersonnelByAccount(string $account, $field = '*')
    {
        $model = new SortingPersonnelModel();
        $where = array(
            'account' => $account,
            'isdel' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 分拣员-详情-根据id查找
     * @param int $id 分拣员id
     * @param string $field 表字段
     * @return array $result
     * */
    public function getSortingPersonnelById(int $id, $field = '*')
    {
        $model = new SortingPersonnelModel();
        $where = array(
            'id' => $id,
            'isdel' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 分拣员-校验密码是否正确
     * @param int $id 分拣员id
     * @param string $password 明文密码
     * @return bool
     * */
    public function isTruePassword(int $id, string $password)
    {
        $detail = $this->getSortingPersonnelById($id, 'password');
        if (empty($detail)) {
            return false;
        }
        $detail_password = $detail['password'];
        if ($detail_password != md5($password)) {
            return false;
        }
        return true;
    }

    /**
     * 分拣员-保存个人信息
     * @params array $params
     * -int id 分拣员id
     * -int shopid 门店id
     * -string userName 分拣员姓名
     * -string mobile 手机号
     * -int state 在线状态(1：在线 -1：不在线)
     * -string password 明文密码
     * @return int $id
     * */
    public function saveSortingPersonnelDetial(array $params)
    {
        $save_params = array(
            'shopid' => null,
            'userName' => null,
            'mobile' => null,
            'userName' => null,
            'state' => null,
            'password' => null,
        );
        parm_filter($save_params, $params);
        if (isset($save_params['password'])) {
            $save_params['password'] = md5($save_params['password']);
        }
        $model = new SortingPersonnelModel();
        if (empty($params['id'])) {
            $id = $model->add($save_params);
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $save_res = $model->where($where)->save($save_params);
            if ($save_res === false) {
                return 0;
            }
        }
        return $id;
    }

    /**
     * 分拣商品店铺分类-多条件查找
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期,不传默认当天
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int goods_category 商品品类(1:标品 2:非标品 不传默认全部)
     * -int line_id 线路id,不传默认全部
     * @return array
     * */
    public function getSortingGoodsShopCatsByParams(array $params)
    {
        $sorting_goods = $this->getSortingGoodsByParams($params);
        $return_goods_list = $this->mergeSortingGoodsToGoods($sorting_goods, $params);
        if (empty($return_goods_list)) {
            return array();
        }
        $shop_catid1 = array_unique(array_column($return_goods_list, 'shopCatId1'));
        $shop_catid2 = array_unique(array_column($return_goods_list, 'shopCatId2'));
        $cat_id_arr = array_merge($shop_catid1, $shop_catid2);
        $cat_module = new GoodsCatModule();
        $field = 'catId,catName,icon,typeImg,parentId';
        $cat_list = $cat_module->getShopCatListById($cat_id_arr, $field);
        $result = array();
        if (empty($cat_list)) {
            return $result;
        }
        $first_cat_list = array();
        foreach ($cat_list as $item) {
            if ($item['parentId'] != 0) {
                continue;
            }
            $first_cat_list[] = $item;
        }
        foreach ($first_cat_list as &$first_cat_detail) {
            $first_cat_detail['icon'] = (string)$first_cat_detail['icon'];
            $first_cat_detail['typeImg'] = (string)$first_cat_detail['typeImg'];
            $first_cat_detail['child'] = array();
            foreach ($cat_list as $cat_detail) {
                if ($cat_detail['parentId'] != $first_cat_detail['catId']) {
                    continue;
                }
                $cat_detail['icon'] = (string)$cat_detail['icon'];
                $cat_detail['typeImg'] = (string)$cat_detail['typeImg'];
                $first_cat_detail['child'][] = $cat_detail;
            }
        }
        unset($first_cat_detail);
        $result = $first_cat_list;
        return $result;
    }

    /**
     * 分拣商品-多条件查找
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期,不传默认当天
     * -int goods_category 商品品类(1:标品 2:非标品 不传默认全部)
     * -int line_id 线路id,不传默认全部
     * -int cat_id 分类id,一级二级都支持
     * -int goods_id 商品id
     * -string goods_name 商品名称
     * -string payment_username 下单客户名称
     * @return array $result
     * */
    public function getSortingGoodsByParams(array $params)
    {
        if (empty($params['id'])) {
            return array();
        }
        if (empty($params['delivery_time'])) {
            $params['delivery_time'] = date('Y-m-d');
        }
        $start_time = $params['delivery_time'] . ' 00:00:00';
        $end_time = $params['delivery_time'] . ' 23:59:59';
        $prefix = M()->tablePrefix;
        $id = $params['id'];
        $sort_where = array(
            'sort.personId' => $id,
            'orders.orderFlag' => 1,
            'orders.requireTime' => array('between', array("{$start_time}", "{$end_time}")),
            'task.dataFlag' => 1,
//            'task.earliestTime' => array('EGT', "{$start_time}"),
//            'task.lateTime' => array('ELT', "{$end_time}"),
            'dispatching.dataFlag' => 1,
        );
        if (!empty($params['line_id'])) {//线路id
            $sort_where['task.lineId'] = array('IN', $params['line_id']);
        }
        if (!empty($params['payment_username'])) {
            $sort_where['users.userName'] = array('like', "%{$params['payment_username']}%");
        }
        $field = 'sort.personId,sort.id';
        $field .= ',orders.orderId,orders.userId,orders.orderRemarks,orders.requireTime';
        $field .= ',users.userName as payment_username';
        $field .= ',task.taskId,task.lineId';
        $field .= ',dispatching.sort';
        $sorting_list = M('sorting sort')
            ->join("left join {$prefix}orders orders on sort.orderId=orders.orderId")
            ->join("left join {$prefix}users users on users.userId=orders.userId")
            ->join("left join {$prefix}psd_wave_task task on task.taskId=orders.taskId")
            ->join("left join {$prefix}psd_dispatching_sort dispatching on dispatching.orderId=orders.orderId")
            ->where($sort_where)
            ->field($field)
            ->group('sort.id')
            ->select();
        if (empty($sorting_list)) {
            return array();
        }
        $sorting_id_arr = array_column($sorting_list, 'id');
        $sorting_id_str = implode(',', $sorting_id_arr);
        $sort_goods_relation = new SortingGoodsRelationModel();
//        $relation_where = array(
//            'relation.sortingId' => array('IN', implode(',', $sorting_id_arr)),
//            'relation.dataFlag' => 1
//        );
        $relation_where = " relation.sortingId IN({$sorting_id_str}) and relation.dataFlag=1 ";
        if ($params['goods_category'] == 1) {//标品
//            $relation_where['relation.SuppPriceDiff'] = -1;
            $relation_where .= " and relation.SuppPriceDiff = -1 ";
        } elseif ($params['goods_category'] == 2) {//非标品
//            $relation_where['relation.SuppPriceDiff'] = 1;
            $relation_where .= " and relation.SuppPriceDiff = 1 ";
        }
        if (!empty($params['cat_id'])) {//按店铺分类筛选
            $goods_cat_module = new GoodsCatModule();
            $cat_detail = $goods_cat_module->getShopCatDetailById($params['cat_id']);
            if (empty($cat_detail)) {
                return array();
            }
            if ($cat_detail['level'] == 1) {
                $relation_where .= " and goods.shopCatId1 = {$cat_detail['catId']} ";
            } else {
                $relation_where .= " and goods.shopCatId2 = {$cat_detail['catId']} ";
            }
        }
        if (!empty($params['goods_id'])) {
            $relation_where .= " and relation.goodsId = {$params['goods_id']} ";
        }
        if (!empty($params['sku_id'])) {
            $relation_where .= " and relation.skuId = {$params['sku_id']} ";
        }
        if (!empty($params['goods_name'])) {
//            $relation_where['goods.goodsName'] = array('like', "%{$params['goods_name']}%");
            if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $params['goods_name'], $match)) {
                $py_code = $params['goods_name'];
            } else {
                $py_code = strtoupper($params['goods_name']);
            }
            $relation_where .= " and (goods.goodsName like '%{$params['goods_name']}%' or goods.py_code like '%{$py_code}%' or goods.py_initials like '%{$py_code}%' or goods.goodsSn like '%{$params['goods_name']}%') ";
        }
        $sort_goods_field = 'relation.id,relation.sortingId,relation.goodsId,relation.skuId,relation.SuppPriceDiff,relation.status,relation.orderWeight,relation.goodsNum,relation.lack_stock_status';
        $sort_goods_field .= ',goods.goodsName,goods.goodsImg,goods.goodsSn,goods.shopCatId1,goods.shopCatId2,goods.SuppPriceDiff,goods.unit,goods.shopId';
        $sort_goods_field .= ',sort.orderId';
        $sort_goods = $sort_goods_relation
            ->alias('relation')
            ->join("left join {$prefix}goods goods on goods.goodsId=relation.goodsId")
            ->join("left join {$prefix}sorting sort on sort.id=relation.sortingId")
            ->where($relation_where)
            ->field($sort_goods_field)
            ->select();
        if (empty($sort_goods)) {
            return array();
        }
        $goods_module = new GoodsModule();
        $shop_module = new ShopsModule();
        $line_module = new LineModule();
        $order_module = new OrdersModule();
        foreach ($sort_goods as &$goods_detail) {
            $shop_id = $goods_detail['shopId'];
            $shop_detail = $shop_module->getShopsInfoById($shop_id, 'shopName', 2);
            $goods_detail['shopName'] = '';
            if (!empty($shop_detail['shopName'])) {
                $goods_detail['shopName'] = $shop_detail['shopName'];
            }
            $orderGoodsParams = array(
                'orderId' => $goods_detail['orderId'],
                'goodsId' => $goods_detail['goodsId'],
                'skuId' => $goods_detail['skuId'],
            );
            $orderGoodsDetail = $order_module->getOrderGoodsInfoByParams($orderGoodsParams, 'remarks', 2);
            $goods_detail['goods_remark'] = (string)$orderGoodsDetail['remarks'];
            $goods_detail['userId'] = 0;//客户id
            $goods_detail['payment_username'] = '';//客户名称
            foreach ($sorting_list as $sorting_detail) {
                if ($sorting_detail['orderId'] == $goods_detail['orderId']) {
                    $goods_detail['payment_username'] = $sorting_detail['payment_username'];
                    $goods_detail['userId'] = $sorting_detail['userId'];
                    $goods_detail['sort'] = $sorting_detail['sort'];
                    $goods_detail['orderRemarks'] = $sorting_detail['orderRemarks'];
                    $goods_detail['lineId'] = $sorting_detail['lineId'];
                    $goods_detail['requireTime'] = $sorting_detail['requireTime'];
                }
            }
            $goods_detail['lineName'] = '';
            if (!empty($goods_detail['lineId'])) {
                $line_detail = $line_module->getLineDetailById($goods_detail['lineId']);
                $goods_detail['lineName'] = $line_detail['lineName'];
            }
            $goods_detail['sku_spec_str'] = '';//sku属性拼接值
            if ($goods_detail['skuId'] > 0) {
                $sku_detail = $goods_module->getSkuSystemInfoById($goods_detail['skuId'], 2);
                if (empty($sku_detail)) {
                    continue;
                }
                $goods_detail['goodsImg'] = $sku_detail['skuGoodsImg'];
                $goods_detail['goodsSn'] = $sku_detail['skuBarcode'];
                $goods_detail['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $goods_detail['unit'] = $sku_detail['unit'];
            }
        }
        unset($goods_detail);
        return $sort_goods;
    }

    /**
     * 按商品分拣-合并商品并处理分拣状态
     * @param array $sorting_goods 分拣商品
     * @params array $params
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * @return array
     * */
    public function mergeSortingGoodsToGoods(array $sorting_goods, array $search_params)
    {
        $params = array(
            'sorting_status' => ''
        );
        parm_filter($params, $search_params);
        if (empty($sorting_goods)) {
            return array();
        }
        $return_goods_list = array();
        foreach ($sorting_goods as $goods_detail) {
            $goods_id = $goods_detail['goodsId'];
            $sku_id = $goods_detail['skuId'];
            $tag_key = $goods_id . '_' . $sku_id;
            if (isset($return_goods_list[$tag_key])) {
                $return_goods_list[$tag_key]['sorting_total_num'] += 1;
                if ($goods_detail['status'] > 0) {
                    //分拣客户-已分拣数量
                    $return_goods_list[$tag_key]['sorting_ok_num'] += 1;
                }
                continue;
            }
            $return_goods_detail = array(
                'goodsId' => $goods_detail['goodsId'],
                'skuId' => $goods_detail['skuId'],
                'sku_spec_str' => $goods_detail['sku_spec_str'],
                'goodsName' => $goods_detail['goodsName'],
                'goodsImg' => $goods_detail['goodsImg'],
                'goodsSn' => $goods_detail['goodsSn'],
                'sorting_total_num' => 1,//分拣客户-总数量
                'sorting_status' => 1,//分拣状态(1:未分拣 2:分拣中 3:标记缺货 4:部分缺货 5:已完成)
                'shopCatId1' => $goods_detail['shopCatId1'],
                'shopCatId2' => $goods_detail['shopCatId2'],
                'unit' => $goods_detail['unit'],
                'SuppPriceDiff' => $goods_detail['SuppPriceDiff'],
                'lack_stock_status' => $goods_detail['lack_stock_status'],
            );
            if ($goods_detail['status'] > 0) {
                //分拣客户-已分拣数量
                $return_goods_detail['sorting_ok_num'] = 1;
            } else {
                $return_goods_detail['sorting_ok_num'] = 0;
            }
            $return_goods_list[$tag_key] = $return_goods_detail;
        }
        //分拣状态
        foreach ($return_goods_list as $key => &$return_detail) {
            if ($return_detail['sorting_ok_num'] > 0) {//分拣中
                $return_detail['sorting_status'] = 2;
            }
            if ($return_detail['lack_stock_status'] == 1) {//部分缺货
                $return_detail['sorting_status'] = 4;
            }
            if ($return_detail['lack_stock_status'] == 2) {//全部缺货
                $return_detail['sorting_status'] = 3;
            }
            if ($return_detail['sorting_ok_num'] >= $return_detail['sorting_total_num']) {//已完成
                $return_detail['sorting_status'] = 5;
            }
            //分拣客户-未分拣数量
            $return_detail['sorting_no_num'] = $return_detail['sorting_total_num'] - $return_detail['sorting_ok_num'];
            if ($params['sorting_status'] == 1) {//未分拣
                if ($return_detail['sorting_ok_num'] > 0) {
                    unset($return_goods_list[$key]);
                }
            } elseif ($params['sorting_status'] == 2) {//已分拣
                if ($return_detail['sorting_ok_num'] == 0) {
                    unset($return_goods_list[$key]);
                }
            }
        }
        unset($return_detail);
        return array_values($return_goods_list);
    }

    /**
     * 订单分拣商品信息-详情
     * @param int $order_id 订单id
     * @param int $goods_id 订单id
     * @param int $sku_id 商品skuId
     * @return array
     * */
    public function getSortingOrderGoodsDetail(int $order_id, int $goods_id, int $sku_id)
    {
        $model = new SortingModel();
        $prefix = M()->tablePrefix;
        $field = 'sort.id,sort.personId,sort.status as sort_status';
        $field .= ',relation.goodsId,relation.goodsNum,relation.sortingGoodsNum,relation.basketGoodsNum,relation.packGoodsNum,relation.status as sort_goods_status,relation.startDate,relation.endDate,relation.weightG,relation.orderWeight,relation.SuppPriceDiff';
        $where = array(
            'sort.orderId' => $order_id,
            'relation.dataFlag' => 1,
            'relation.goodsId' => $goods_id,
            'relation.skuId' => $sku_id,
        );
        $detail = $model
            ->alias('sort')
            ->join("left join {$prefix}sorting_goods_relation relation on relation.sortingId=sort.id")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($detail)) {
            return array();
        }
        if (empty($detail['startDate'])) {
            $detail['startDate'] = '';
        }
        if (empty($detail['endDate'])) {
            $detail['endDate'] = '';
        }
        return $detail;
    }

    /**
     * 分拣商品-详情-多条件查找
     * @param int $person_id 分拣员id
     * @param int $sorting_id 分拣任务id
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuId
     * @return array
     * */
    public function getSortingGoodsDetailByParams(int $person_id, int $sorting_id, $goods_id, $sku_id)
    {
        $model = new SortingGoodsRelationModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'sort.personId' => $person_id,
            'relation.goodsId' => $goods_id,
            'relation.sortingId' => $sorting_id,
            'relation.skuId' => $sku_id,
            'relation.dataFlag' => 1,
        );
        $field = 'relation.id,relation.sortingId,relation.goodsId,relation.lack_stock_status,relation.status,relation.startDate,relation.endDate';
        $field .= ',goods.goodsName,goods.goodsImg,goods.SuppPriceDiff,goods.weightG,goods.unit,goods.goodsSn';
        $field .= ',user.userId,user.userName as payment_username';
        $field .= ',sort.orderId';
        $field .= ',orders.payFrom,orders.orderStatus,orders.shopId,orders.orderRemarks,orders.taskId,orders.requireTime';
        $result = $model
            ->alias('relation')
            ->join("left join {$prefix}sorting sort on sort.id=relation.sortingId")
            ->join("left join {$prefix}goods goods on goods.goodsId=relation.goodsId")
            ->join("left join {$prefix}orders orders on orders.orderId=sort.orderId")
            ->join("left join {$prefix}users user on user.userId=orders.userId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $orderGoodsParams = array(
            'orderId' => $result['orderId'],
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
        );
        $orderGoodsDetail = (new OrdersModule())->getOrderGoodsInfoByParams($orderGoodsParams, 'remarks', 2);
        $result['goods_remark'] = (string)$orderGoodsDetail['remarks'];//商品备注
        $result['sku_spec_str'] = '';//sku属性拼接值
        if (!empty($sku_id)) {
            $goods_module = new GoodsModule();
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (!empty($sku_detail)) {
                $result['goodsImg'] = $sku_detail['skuGoodsImg'];
                $result['weightG'] = $sku_detail['weigetG'];
                $result['goodsSn'] = $sku_detail['skuBarcode'];
                $result['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $result['unit'] = $sku_detail['unit'];
            }
        }
        $goods_weight_detail = $this->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
        $result['goodsNum'] = $goods_weight_detail['goodsNum'];//同购物车数量
        $result['sorting_total_weight'] = $goods_weight_detail['sorting_total_weight'];//需要分拣的总数量/重量
        $result['sorting_ok_weight'] = $goods_weight_detail['sorting_ok_weight'];//已分拣数量/重量
        $result['sorting_no_weight'] = $goods_weight_detail['sorting_no_weight'];//未分拣数量/重量
        return $result;
    }

    /**
     * 分拣商品-获取分拣商品的数量/重量
     * @param int $sorting_id 分拣任务id
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuId
     * @return array
     * */
    public function getSortingGoodsWeight(int $sorting_id, int $goods_id, int $sku_id)
    {
        $return_data = array(
            'goodsNum' => 0,//同购物车数量
            'sorting_total_weight' => 0,//需要分拣的总数量/重量,标品为数量单位,非标品默认单位斤
            'sorting_ok_weight' => 0,//已分拣数量/重量,标品为数量单位,非标品默认单位斤
            'sorting_no_weight' => 0,//未分拣数量/重量,标品为数量单位,非标品默认单位斤
        );
        $model = new SortingGoodsRelationModel();
        $prefix = M()->tablePrefix;
        $where = array(
            'relation.sortingId' => $sorting_id,
            'relation.goodsId' => $goods_id,
            'relation.skuId' => $sku_id,
            'relation.dataFlag' => 1,
        );
        $field = 'relation.goodsId,relation.goodsNum,relation.SuppPriceDiff,relation.sortingGoodsNum,relation.basketGoodsNum,relation.packGoodsNum,relation.status,relation.weightG as sorting_weightG,relation.lack_stock_status';
        $field .= ',goods.weightG as goods_weightG';
        $sorting_goods_detail = $model
            ->alias('relation')
            ->join("left join {$prefix}goods goods on goods.goodsId=relation.goodsId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($sorting_goods_detail)) {
            return $return_data;
        }
        if (!empty($sku_id)) {
            $goods_module = new GoodsModule();
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (!empty($sku_detail)) {
                $sorting_goods_detail['goods_weightG'] = $sku_detail['weigetG'];
            }
        }
        $return_data['goodsNum'] = (float)$sorting_goods_detail['goodsNum'];
        //废弃包装系数
//        $return_data['sorting_total_weight'] = $return_data['goodsNum'] * (float)$sorting_goods_detail['goods_weightG'];
        $return_data['goodsNum'] = $return_data['goodsNum'];
        $return_data['sorting_total_weight'] = $return_data['goodsNum'];
        $return_data['sorting_ok_weight'] = (float)$sorting_goods_detail['sortingGoodsNum'];
        $return_data['sorting_no_weight'] = (float)bc_math($return_data['sorting_total_weight'], $return_data['sorting_ok_weight'], 'bcsub', 3);
        if ($sorting_goods_detail['SuppPriceDiff'] == 1) {//非标品
//            $return_data['sorting_total_weight'] = ((float)$sorting_goods_detail['goodsNum'] * (float)$sorting_goods_detail['goods_weightG']) / 500;
//            $return_data['sorting_total_weight'] = ((float)$sorting_goods_detail['goodsNum'] * (float)$sorting_goods_detail['goods_weightG']);
//            $return_data['sorting_ok_weight'] = $sorting_goods_detail['sorting_weightG'] / 500;
            $return_data['sorting_ok_weight'] = (float)$sorting_goods_detail['sorting_weightG'];
            $return_data['sorting_no_weight'] = (float)bc_math($return_data['sorting_total_weight'], $return_data['sorting_ok_weight'], 'bcsub', 3);
        }
        if ($return_data['sorting_no_weight'] < 0) {
            $return_data['sorting_no_weight'] = 0;
        }
        return $return_data;
    }

    /**
     * 分拣商品数量-递增
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @param float $num_or_weight 分拣数量或重量
     * @param object $trans 用于事务
     * @return bool
     * */
    public function incSortingGoodsNum(int $sorting_goods_relationid, float $num_or_weight, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
//        $goods_module = new GoodsModule();
//        $goods_detail = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsName,SuppPriceDiff', 2);
//        if (empty($goods_detail)) {
//            $db_trans->rollback();
//            return false;
//        }
        $sorting_goods_detail = $this->getSortingGoodsDetailById($sorting_goods_relationid);
        if (empty($sorting_goods_detail)) {
            $db_trans->rollback();
            return false;
        }
        $soring_id = $sorting_goods_detail['sortingId'];
        $goods_id = $sorting_goods_detail['goodsId'];
        $sku_id = $sorting_goods_detail['skuId'];
        $model = new SortingGoodsRelationModel();
        $where = array(
            'sortingId' => $soring_id,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
            'dataFlag' => 1,
        );
        if ($sorting_goods_detail['SuppPriceDiff'] != 1) {//标品
            $result = $model->where($where)->setInc('sortingGoodsNum', $num_or_weight);
        } else {//非标品
//            $num_or_weight = $num_or_weight * 500;//斤转g
            $result = $model->where($where)->setInc('weightG', $num_or_weight);
        }
        if ($result === false) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 分拣商品信息-保存
     * @params array $params
     * -int id 自增id
     * -int sortingId 分拣任务id
     * -int goodsId 商品id
     * -int goodsNum 商品数量
     * -int sortingGoodsNum 已分拣数量【仅适用于标品(非称重商品)】
     * -int basketGoodsNum 已入框数量【仅适用于标品(非称重商品)】
     * -int packGoodsNum 已打包数量【仅适用于标品(非称重商品)】
     * -int status 分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成(待入框),3=>已入框(已完成))
     * -datetime startDate 开始分拣时间
     * -datetime endDate 结束分拣时间
     * -int dataFlag 有效数据(-1=>无效,1=>有效)
     * -int skuId skuId
     * -float weightG 分拣合计称重【预打包合计条码重量 g】
     * -float orderWeight 订单商品总重量 g
     * -string barcode 条码[分拣时的条码]
     * -int SuppPriceDiff 称重补差价[-1：否 1：是]
     * -int lack_stock_status 缺货状态(0:未缺货 1:部分缺货 2:全部缺货) 注:目前仅用于B端分拣
     * return int
     * */
    public function saveSortingGoods(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'sortingId' => null,
            'goodsId' => null,
            'goodsNum' => null,
            'sortingGoodsNum' => null,
            'basketGoodsNum' => null,
            'packGoodsNum' => null,
            'status' => null,
            'startDate' => null,
            'endDate' => null,
            'dataFlag' => null,
            'skuId' => null,
            'weightG' => null,
            'orderWeight' => null,
            'barcode' => null,
            'SuppPriceDiff' => null,
            'lack_stock_status' => null,
        );
        parm_filter($save_params, $params);
        $model = new SortingGoodsRelationModel();
        if (empty($params['id'])) {
            $id = $model->save($save_params);
            if (empty($id)) {
                $db_trans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $save_res = $model->where($where)->save($save_params);
            if ($save_res === false) {
                $db_trans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return $id;

    }


    /**
     * 分拣任务-详情-根据id获取
     * @param int $sorting_id 分拣任务id
     * @param string $field 表字段
     * @return array
     * */
    public function getSortingDetailById(int $sorting_id, $field = '*')
    {
        $model = new SortingModel();
        $where = array(
            'id' => $sorting_id
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        $relation_where = array(
            'relation.dataFlag' => 1,
            'relation.sortingId' => $sorting_id,
        );
        $relation_field = 'relation.goodsId,relation.goodsNum,relation.sortingGoodsNum,relation.basketGoodsNum,relation.packGoodsNum,relation.status,relation.startDate,relation.endDate,relation.skuId,relation.weightG,relation.orderWeight,relation.barcode,relation.SuppPriceDiff,relation.lack_stock_status';
        $relation_field .= ',goods.goodsName,goods.goodsImg';
        $prefix = M()->tablePrefix;
        $sort_goods = (new SortingGoodsRelationModel())
            ->alias('relation')
            ->join("left join {$prefix}goods goods on relation.goodsId=goods.goodsId")
            ->where($relation_where)
            ->field($relation_field)
            ->select();
        if (empty($sort_goods)) {
            return array();
        }
        $goods_module = new GoodsModule();
        foreach ($sort_goods as &$item) {
            $item['sku_spec_str'] = '';
            $sku_id = $item['skuId'];
            if (!empty($sku_id)) {
                $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                if (empty($sku_detail)) {
                    continue;
                }
                $item['sku_spec_str'] = $sku_detail['skuSpecAttr'];
            }

        }
        unset($item);
        $result['sort_goods'] = $sort_goods;
        return $result;
    }

    /**
     * 分拣任务-保存
     * @param array $params
     * -int id 分拣任务id
     * -string settlementNo 单号
     * -int uid 用户id
     * -int orderId 订单id
     * -int type 分拣类型(0=>按订单分拣,1=>按商品分拣)
     * -int settlement 是否结算(-1：未结算 1：已结算)
     * -int shopid 店铺id
     * -int basketId 筐ID
     * -int personId 分拣员id
     * -int status 分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成(待入框),3=>已入框(已完成))
     * -int sortingFlag 有效状态(-1=>无效,1=>有效)
     * -datetime startDate 开始分拣时间
     * -datetime endDate 结束分拣时间
     * -int isPack 是否打包[-1:未进入|1:已进入]
     * @param object $trans
     * */
    public function saveSortingData(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'settlementNo' => null,
            'uid' => null,
            'orderId' => null,
            'type' => null,
            'settlement' => null,
            'shopid' => null,
            'basketId' => null,
            'personId' => null,
            'status' => null,
            'sortingFlag' => null,
            'startDate' => null,
            'endDate' => null,
            'updatetime' => date('Y-m-d H:i:s'),
            'isPack' => null,
        );
        parm_filter($save_params, $params);
        $model = new SortingModel();
        if (empty($params['id'])) {
            $save_params['addtime'] = date('Y-m-d H:i:s');
            $id = $model->add($save_params);
            if (empty($id)) {
                $db_trans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $save_res = $model->where($where)->save($save_params);
            if (!$save_res) {
                $db_trans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return (int)$id;
    }

    /**
     * 分拣任务-更新分拣任务的状态
     * @param int $sorting_id 分拣任务id
     * @param int $from_type 类型(1:移动端 2:桌面端)
     * @param object $trans
     * @return bool
     * */
    public function autoUpdateSortingStatus(int $sorting_id, $from_type = 1, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $sorting_detail = $this->getSortingDetailById($sorting_id);
        if (empty($sorting_detail)) {
            $db_trans->rollback();
            return false;
        }
        $sort_goods = $sorting_detail['sort_goods'];
        $person_id = $sorting_detail['personId'];
        $sort_total_weight = 0;//需要分拣的总数量/重量
        $sort_ok_weight = 0;//已经分拣的总数量/重量
        $is_sorting_status = 0;//是否分拣过(0:未分拣过 1:已分拣过)
        $sort_start_date = array();
        $sort_end_date = array();
        foreach ($sort_goods as &$detial) {
            $goods_id = $detial['goodsId'];
            $sku_id = $detial['skuId'];
            $sorting_goods_detail = $this->getSortingGoodsDetailByParams($person_id, $sorting_id, $goods_id, $sku_id);
            if (empty($sorting_goods_detail)) {
                continue;
            }
            $total_sort_weight += $sorting_goods_detail['sorting_total_weight'];
            $sort_ok_weight += $sorting_goods_detail['sorting_ok_weight'];
            if ($sorting_goods_detail['status'] > 0) {
                $is_sorting_status = 1;
                $sort_start_date[] = $sorting_goods_detail['startDate'];
                $sort_end_date[] = $sorting_goods_detail['endDate'];
            }
        }
        unset($detial);
        $sorting_status = $sorting_detail['status'];//分拣任务的状态
        asort($sort_start_date);
        rsort($sort_end_date);
        if ($is_sorting_status > 0) {
            if ($sorting_status == 0) {//商品已分拣更新分拣任务的状态
                $sorting_params = array(
                    'id' => $sorting_id,
                    'status' => 1,
                    'startDate' => $sort_start_date[0],
                );
                if ($sort_ok_weight >= $sort_total_weight) {
                    $sorting_params['endDate'] = $sort_end_date[0];
                    $sorting_params['status'] = 2;
                    if ($from_type == 2) {
                        $sorting_params['status'] = 3;
                    }
                }
                $save_sorting_res = $this->saveSortingData($sorting_params, $trans);
                if (empty($save_sorting_res)) {
                    $db_trans->rollback();
                    return false;
                }
            }
            if ($sorting_status > 0 && $sorting_status < 2 && $sort_ok_weight >= $sort_total_weight) {//分拣商品已完成更新分拣任务的状态
                $sorting_params = array(
                    'id' => $sorting_id,
                    'status' => 2,
                    'endDate' => $sort_end_date[0],
                );
                if ($from_type == 2) {
                    $sorting_params['status'] = 3;
                }
                $save_sorting_res = $this->saveSortingData($sorting_params, $trans);
                if (empty($save_sorting_res)) {
                    $db_trans->rollback();
                    return false;
                }
            }
        }
        if ($is_sorting_status == 0) {//更改分拣任务为未分拣
            $sorting_params = array(
                'id' => $sorting_id,
                'status' => 0,
                'startDate' => '',
                'endDate' => '',
            );
            $save_sorting_res = $this->saveSortingData($sorting_params, $trans);
            if (empty($save_sorting_res)) {
                $db_trans->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 分拣商品-详情-多条件查找
     * @param int $id 分拣商品唯一标识id
     * @return array
     * */
    public function getSortingGoodsDetailById(int $id)
    {
        $model = new SortingGoodsRelationModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'relation.id' => $id,
            'relation.dataFlag' => 1,
        );
        $field = 'relation.id,relation.sortingId,relation.goodsId,relation.skuId,relation.lack_stock_status,relation.status,relation.startDate,relation.endDate,relation.SuppPriceDiff';
        $field .= ',goods.goodsName,goods.goodsImg,goods.weightG,goods.unit,goods.goodsSn';
        $field .= ',user.userId,user.userName as payment_username';
        $field .= ',sort.orderId';
        $field .= ',orders.payFrom,orders.orderStatus,orders.shopId,orders.taskId';
        $result = $model
            ->alias('relation')
            ->join("left join {$prefix}sorting sort on sort.id=relation.sortingId")
            ->join("left join {$prefix}goods goods on goods.goodsId=relation.goodsId")
            ->join("left join {$prefix}orders orders on orders.orderId=sort.orderId")
            ->join("left join {$prefix}users user on user.userId=orders.userId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $result['sku_spec_str'] = '';//sku属性拼接值
        $sku_id = $result['skuId'];
        $sorting_id = $result['sortingId'];
        $goods_id = $result['goodsId'];
        if (!empty($sku_id)) {
            $goods_module = new GoodsModule();
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (!empty($sku_detail)) {
                $result['goodsImg'] = $sku_detail['skuGoodsImg'];
                $result['weightG'] = $sku_detail['weigetG'];
                $result['goodsSn'] = $sku_detail['skuBarcode'];
                $result['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $result['unit'] = $sku_detail['unit'];
            }
        }
        $goods_weight_detail = $this->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
        $result['goodsNum'] = $goods_weight_detail['goodsNum'];//同购物车数量
        $result['sorting_total_weight'] = $goods_weight_detail['sorting_total_weight'];//需要分拣的总数量/重量
        $result['sorting_ok_weight'] = $goods_weight_detail['sorting_ok_weight'];//已分拣数量/重量
        $result['sorting_no_weight'] = $goods_weight_detail['sorting_no_weight'];//未分拣数量/重量
        return $result;
    }

    /**
     * 分拣商品-重置
     * @param int $id 分拣商品唯一标识id
     * @param object $trans
     * @return bool
     * */
    public function resetSortingGoods(int $id, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new SortingGoodsRelationModel();
        $where = array(
            'id' => $id
        );
        $save_params = array(
            'sortingGoodsNum' => 0,
            'status' => 0,
            'startDate' => '',
            'endDate' => '',
            'weightG' => 0,
            'lack_stock_status' => 0,
        );
        $res = $model->where($where)->save($save_params);
        if ($res === false) {
            $db_trans->rollback();
            return false;
        }
        $sorting_goods_detail = $this->getSortingGoodsDetailById($id);
        $order_module = new OrdersModule();
        $order_goods_where = array(
            'orderId' => $sorting_goods_detail['orderId'],
            'goodsId' => $sorting_goods_detail['goodsId'],
            'skuId' => $sorting_goods_detail['skuId'],
        );
        $order_goods_detail = $order_module->getOrderGoodsInfoByParams($order_goods_where, 'id', 2);
        if (empty($order_goods_detail)) {
            $db_trans->rollback();
            return false;
        }
        $save_og_params = array(
            'id' => $order_goods_detail['id'],
            'actionStatus' => 0,
            'sortingNum' => 0,
            'nuclearCargoNum' => 0,
        );
        $save_og_res = $order_module->saveOrderGoods($save_og_params, $db_trans);
        if (empty($save_og_res)) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 重置全部缺货的商品操作的记录
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @param object $trans
     * @return array
     * */
    public function resetSortingGoodsRelation(int $sorting_goods_relationid, $trans)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $detail = $this->getSortingGoodsDetailById($sorting_goods_relationid);
        if (empty($detail)) {
            $db_trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '分拣商品信息有误');
        }
        if ($detail['lack_stock_status'] != 2) {
            $db_trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '非全部缺货的商品');
        }
        $order_id = $detail['orderId'];
        $goods_id = $detail['goodsId'];
        $sku_id = $detail['skuId'];
        $orders_module = new OrdersModule();
//        $goods_module = new GoodsModule();
//        $dec_stock = $detail['sorting_no_weight'];
//        if ($detail['SuppPriceDiff'] == 1) {
////            $dec_stock = $dec_stock / 2;//斤转kg 废除单位换算
//        }
//        $dec_goods_stock = $goods_module->deductionGoodsStock($goods_id, $sku_id, $dec_stock, 1, 1, $db_trans);
//        if ($dec_goods_stock['code'] != ExceptionCodeEnum::SUCCESS) {
//            $db_trans->rollback();
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '商品补差价信息重置失败');
//        }
        $reset_diff_price_res = $orders_module->delOrderGoodsPriceDiff($order_id, $goods_id, $db_trans);//删除补差价记录
        if (!$reset_diff_price_res) {
            $db_trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '商品补差价信息重置失败');
        }
        $goods_complains_detail = $orders_module->getOrderGoodsComplainsDetailByParams($order_id, $goods_id, $sku_id, 'complainId');//获取商品的售后申请记录
        if (!empty($goods_complains_detail)) {
            $complain_id = $goods_complains_detail['complainId'];
            $reset_complains = $orders_module->delOrderGoodsComplains($order_id, $goods_id, $sku_id, $db_trans);
            if (!$reset_complains) {
                $db_trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '商品售后申请信息重置失败');
            }
            $reset_complainsrecord = $orders_module->delOrderGoodsComplainsrecord($order_id, $goods_id, $sku_id, $db_trans);
            if (!$reset_complainsrecord) {
                $db_trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '商品售后退款信息重置失败');
            }
            $action_log_res = (new TableActionLogModule())->delTableActionLog('wst_order_complains', $complain_id, $db_trans);
            if ($action_log_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后状态日志记录删除失败');
            }
        }
        $log_params = array(
            'orderId' => $order_id,
            'orderStatus' => 16,
        );
        $log_res = (new LogOrderModule())->delLogOrdersByParams($log_params, $db_trans);//重置订单日志,代码位置勿动
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志重置失败');
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return returnData(true);
    }


    /**
     * 按客户分拣-合并订单并处理分拣状态
     * @param array $sorting_goods 分拣商品
     * @params array $params
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * @return array
     * */
    public function mergeSortingOrdersToCustomer(array $sorting_goods, array $search_params)
    {
        $params = array(
            'sorting_status' => ''
        );
        parm_filter($params, $search_params);
        if (empty($sorting_goods)) {
            return array();
        }
        $return_list = array();
        foreach ($sorting_goods as $goods_detail) {
            $order_id = $goods_detail['orderId'];
            $tag_key = $order_id;
            if (isset($return_list[$tag_key])) {
                $return_list[$tag_key]['sorting_total_num'] += 1;
                if ($goods_detail['status'] > 0) {
                    //分拣客户-已分拣数量
                    $return_list[$tag_key]['sorting_ok_num'] += 1;
                }
                if ($goods_detail['lack_stock_status'] > $return_list[$tag_key]['lack_stock_status']) {
                    $return_list[$tag_key]['lack_stock_status'] = $goods_detail['lack_stock_status'];
                }
                continue;
            }
            $return_detail = array(
                'orderId' => $order_id,
                'userId' => $goods_detail['userId'],
                'payment_username' => $goods_detail['payment_username'],
                'sorting_total_num' => 1,//分拣商品-总数量
                'sorting_status' => 1,//分拣状态(1:未分拣 2:分拣中 3:缺货 5:已完成)
                'lack_stock_status' => 0,//缺货状态(0:未缺货 1:部分缺货 2:全部缺货)
            );
            if ($goods_detail['status'] > 0) {
                //分拣商品-已分拣数量
                $return_detail['sorting_ok_num'] = 1;
            } else {
                $return_detail['sorting_ok_num'] = 0;
            }
            if ($goods_detail['lack_stock_status'] > 0) {
                $return_detail['lack_stock_status'] = $goods_detail['lack_stock_status'];
            }
            $return_list[$tag_key] = $return_detail;
        }
        //分拣状态
        foreach ($return_list as $key => &$return_detail) {
            if ($return_detail['sorting_ok_num'] > 0) {//分拣中
                $return_detail['sorting_status'] = 2;
            }
            if ($return_detail['lack_stock_status'] == 1 || $return_detail['lack_stock_status'] == 2) {//缺货
                $return_detail['sorting_status'] = 3;
            }
            if ($return_detail['sorting_ok_num'] >= $return_detail['sorting_total_num']) {//已完成
                $return_detail['sorting_status'] = 5;
            }
            //分拣商品-未分拣数量
            $return_detail['sorting_no_num'] = $return_detail['sorting_total_num'] - $return_detail['sorting_ok_num'];
            if ($params['sorting_status'] == 1) {//未分拣
                if ($return_detail['sorting_ok_num'] > 0) {
                    unset($return_list[$key]);
                }
            } elseif ($params['sorting_status'] == 2) {//已分拣
                if ($return_detail['sorting_ok_num'] == 0) {
                    unset($return_list[$key]);
                }
            }
        }
        unset($return_detail);
        return array_values($return_list);
    }

    /**
     * 按客户分拣-获取客户订单商品
     * @param int $person_id 分拣员id
     * @param int $order_id 订单id
     * @param string [$goods_name] 商品名称
     * @return array
     * */
    public function getSortingOrderGoods(int $person_id, int $order_id, string $goods_name)
    {
        $sorting_model = new SortingModel();
        $sorting_field = 'id';
        $sorting_where = array(
            'personId' => $person_id,
            'orderId' => $order_id,
        );
        $sorting_detail = $sorting_model->where($sorting_where)->field($sorting_field)->find();
        if (empty($sorting_detail)) {
            return array();
        }
        $prefix = M()->tablePrefix;
        $sorting_id = $sorting_detail['id'];
        $relation_tab = new SortingGoodsRelationModel();
        $sort_goods_field = 'relation.id,relation.sortingId,relation.goodsId,relation.skuId,relation.SuppPriceDiff,relation.status,relation.orderWeight,relation.goodsNum,relation.lack_stock_status';
        $sort_goods_field .= ',goods.goodsName,goods.goodsImg,goods.goodsSn,goods.shopCatId1,goods.shopCatId2,goods.SuppPriceDiff,goods.unit';
        $sort_goods_field .= ',sort.orderId';
//        $relation_where = array(
//            'relation.dataFlag' => 1,
//            'relation.sortingId' => $sorting_id,
//        );
        $relation_where = " relation.dataFlag=1 and relation.sortingId={$sorting_id} ";
        if (!empty($goods_name)) {
            $py_code = $goods_name;
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goods_name, $match)) {
                $py_code = strtoupper($goods_name);
            }
            $relation_where .= " and (goods.goodsName like '%{$goods_name}%' or goods.py_code like '%{$py_code}%' or goods.py_initials like '%{$py_code}%' or goods.goodsSn like '%{$goods_name}%') ";
        }
        $sort_goods = $relation_tab
            ->alias('relation')
            ->join("left join {$prefix}goods goods on goods.goodsId=relation.goodsId")
            ->join("left join {$prefix}sorting sort on sort.id=relation.sortingId")
            ->where($relation_where)
            ->field($sort_goods_field)
            ->select();
        if (empty($sort_goods)) {
            return array();
        }
        $goods_module = new GoodsModule();
        foreach ($sort_goods as &$goods_detail) {
            $goods_id = $goods_detail['goodsId'];
            $sku_id = $goods_detail['skuId'];
            $orderGoodsParams = array(
                'orderId' => $order_id,
                'goodsId' => $goods_id,
                'skuId' => $sku_id,
            );
            $orderGoodsDetail = (new OrdersModule())->getOrderGoodsInfoByParams($orderGoodsParams, 'remarks', 2);
            $goods_detail['goods_remark'] = (string)$orderGoodsDetail['remarks'];
            $goods_detail['sku_spec_str'] = '';//sku属性拼接值
            if ($goods_detail['skuId'] > 0) {
                $sku_detail = $goods_module->getSkuSystemInfoById($goods_detail['skuId'], 2);
                if (empty($sku_detail)) {
                    continue;
                }
                $goods_detail['goodsImg'] = $sku_detail['skuGoodsImg'];
                $goods_detail['goodsSn'] = $sku_detail['skuBarcode'];
                $goods_detail['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $goods_detail['unit'] = $sku_detail['unit'];
            }
            $goods_weight = $this->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
            $goods_detail['goodsNum'] = $goods_weight['goodsNum'];
            $goods_detail['sorting_total_weight'] = $goods_weight['sorting_total_weight'];
            $goods_detail['sorting_ok_weight'] = $goods_weight['sorting_ok_weight'];
            $goods_detail['sorting_no_weight'] = $goods_weight['sorting_no_weight'];
        }
        unset($goods_detail);
        return $sort_goods;
    }

    /**
     * 分拣商品数量超出用户购买数量,增加用户已欠款额度
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @param int $overflowWeight 多分拣的数量/重量
     * @param object $trans
     * @return bool
     * */
    public function incUserQuotaArrears(int $orderId, int $goodsId, int $skuId, $overflowWeight, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderModule = new OrdersModule();
        $moneyOfWeight = $orderModule->getOrderGoodsPriceNumG($orderId, $goodsId, $skuId);
        $overflowMoney = (float)bc_math($moneyOfWeight, $overflowWeight, 'bcmul', 2);
        if ($overflowMoney > 0) {
            $orderDetail = $orderModule->getOrderInfoById($orderId, 'userId', 2);
            $userId = $orderDetail['userId'];
            $userModule = new UsersModule();
            $incRes = $userModule->incQuotaArrears($userId, (float)$overflowMoney, $trans);
            if (!$incRes) {
                $dbTrans->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 分拣商品数量超出用户购买数量,减少用户已欠款额度
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @param int $overflowWeight 多分拣的数量/重量
     * @param object $trans
     * @return bool
     * */
    public function decUserQuotaArrears(int $orderId, int $goodsId, int $skuId, $overflowWeight, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderModule = new OrdersModule();
        $moneyOfWeight = $orderModule->getOrderGoodsPriceNumG($orderId, $goodsId, $skuId);
        $overflowMoney = (float)bc_math($moneyOfWeight, $overflowWeight, 'bcmul', 2);
        if ($overflowMoney > 0) {
            $orderDetail = $orderModule->getOrderInfoById($orderId, 'userId', 2);
            $userId = $orderDetail['userId'];
            $userModule = new UsersModule();
            $incRes = $userModule->decQuotaArrears($userId, $overflowMoney, $trans);
            if (!$incRes) {
                $dbTrans->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 分拣完成-创建出库单
     * @param int $sortingId 分拣任务id
     * @param object $trans
     * @return array
     * */
    public function completeSortingDoWarehouse(int $sortingId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $sortingDetail = $this->getSortingDetailById($sortingId);
        if (empty($sortingDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣任务信息有误');
        }
        $sortingGoods = $sortingDetail['sort_goods'];
        $orderModule = new OrdersModule();
        $orderId = $sortingDetail['orderId'];
        $orderDetail = $orderModule->getOrderInfoById($orderId, 'orderId,shopId,orderNo', 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单信息有误');
        }
        $shopId = $orderDetail['shopId'];
        $exWarehouseModule = new ExWarehouseOrderModule();
        $sortingPerson = $this->getSortingPersonnelById($sortingDetail['personId']);
        $billData = array(
            'pagetype' => 1,
            'shopId' => $shopId,
            'user_id' => $sortingDetail['personId'],
            'user_name' => (string)$sortingPerson['userName'],
            'remark' => '',
            'relation_order_number' => $orderDetail['orderNo'],
            'relation_order_id' => $orderDetail['orderId'],
            'goods_data' => array(),
        );
        foreach ($sortingGoods as $sortingGoodsVal) {
            $goodsId = $sortingGoodsVal['goodsId'];
            $skuId = $sortingGoodsVal['skuId'];
            $orderGoodsDetailParams = array(
                'orderId' => $orderId,
                'goodsId' => $goodsId,
                'skuId' => $skuId,
            );
            $orderGoodsDetail = $orderModule->getOrderGoodsInfoByParams($orderGoodsDetailParams, '*', 2);
            if (empty($orderGoodsDetail)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单商品信息有误');
            }
            $sortingGoodsDetail = $this->getSortingGoodsDetailByParams($sortingDetail['personId'], $sortingId, $goodsId, $skuId);
            $nums = $orderGoodsDetail['goodsNums'];
            $billGoodsData = array();
            $billGoodsData['goods_id'] = $goodsId;
            $billGoodsData['sku_id'] = $skuId;
            $billGoodsData['nums'] = (float)$nums;
            $billGoodsData['actual_delivery_quantity'] = (float)$sortingGoodsDetail['sorting_ok_weight'];
            $billData['goods_data'][] = $billGoodsData;
        }
        $addBillRes = $exWarehouseModule->addExWarehouseOrder($billGoodsData, $dbTrans);
        if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '出库单创建失败');
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }
}
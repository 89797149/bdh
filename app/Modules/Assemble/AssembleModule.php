<?php
/**
 * 拼团相关
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-28
 * Time: 10:44
 */

namespace App\Modules\Assemble;


use App\Enum\ExceptionCodeEnum;
use App\Models\AssembleActivityGoodsSkuModel;
use App\Models\AssembleActivityModel;
use App\Models\AssembleModel;
use App\Models\BaseModel;
use App\Models\SkuGoodsSelfModel;
use App\Models\UserActivityRelationModel;
use App\Modules\Goods\GoodsModule;
use Think\Model;

class AssembleModule extends BaseModel
{
    /**
     * 获取拼团商品信息-根据活动id获取
     * @param int $aid 活动id
     * @return array
     * */
    public function getActivityGoods(int $aid)
    {
        $active_model = new AssembleActivityModel();
        $where = array(
            'aid' => $aid
        );
        $where['aid'] = $aid;
        $active_detail = $active_model->where($where)->find();
        if (empty($active_detail)) {
            return array();
        }
        $goods_id = $active_detail['goodsId'];
        $goods_module = new GoodsModule();
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            return array();
        }
        if ($goods_detail['isSale'] != 1) {
            return array();
        }
        $activity_goods_sku_model = new AssembleActivityGoodsSkuModel();
        $where = array(
            'ac_goods_sku.aid' => $aid,
            'system.dataFlag' => 1,
        );
        $prefix = M()->tablePrefix;
        $field = "ac_goods_sku.tprice,system.*";
        $activity_goods_sku = $activity_goods_sku_model
            ->alias('ac_goods_sku')
            ->join("left join {$prefix}sku_goods_system system on system.skuId=ac_goods_sku.skuId")
            ->where($where)
            ->field($field)
            ->select();
        $goods_detail['hasGoodsSku'] = -1;//是否有拼团sku【0：无|1：有】
        $goods_detail['goodsSku'] = array();
        $goods_detail['tprice'] = $active_detail['tprice'];
        if (empty($activity_goods_sku)) {
            return $goods_detail;
        }
        $self_model = new SkuGoodsSelfModel();
        $replace_sku_field = C('replaceSkuField');//需要被sku属性替换的字段
        $sku_list = array();
        foreach ($activity_goods_sku as $key => $value) {
            $sku_id = $value['skuId'];
            $spec = array();
            $spec['skuId'] = $sku_id;
            foreach ($replace_sku_field as $rek => $rev) {
                if (isset($value[$rek])) {
                    $spec['systemSpec'][$rek] = $value[$rek];
                }
                if (in_array($rek, ['dataFlag', 'addTime'])) {
                    continue;
                }
                if ((int)$spec['systemSpec'][$rek] == -1) {
                    //如果sku属性值为-1,则调用商品原本的值(详情查看config)
                    $spec['systemSpec'][$rek] = $goods_detail[$rev];
                }
            }
            $spec['systemSpec']['tprice'] = $value['tprice'];
            $self_where = array(
                'self.skuId' => $sku_id,
                'self.dataFlag' => 1,
                'sp.dataFlag' => 1,
                'sr.dataFlag' => 1
            );
            //原有的代码,就不改了
            $self_spec_list = $self_model
                ->alias('self')
                ->join("left join {$prefix}sku_spec sp on self.specId=sp.specId")
                ->join("left join {$prefix}sku_spec_attr sr on sr.attrId=self.attrId")
                ->where($self_where)
                ->field('self.id,self.skuId,self.specId,self.attrId,sp.specName,sr.attrName')
                ->order('sp.sort asc')
                ->select();
            if (empty($self_spec_list)) {
                unset($activity_goods_sku[$key]);
                continue;
            }
            $spec['selfSpec'] = $self_spec_list;
            $sku_list[] = $spec;
        }
        if (count($sku_list) > 0) {
            $goods_detail['hasGoodsSku'] = 1;
        }
        //skuSpec
        $sku_spec_list = array();
        $sku_spec_attr = array();
        foreach ($sku_spec_list as $value) {
            foreach ($value['selfSpec'] as $va) {
                $sku_spec_attr[] = $va;
                $sku_spec_info['specId'] = $va['specId'];
                $sku_spec_info['specName'] = $va['specName'];
                $sku_spec_list[] = $sku_spec_info;
            }
        }
        $sku_spec_list = arrayUnset($sku_spec_list, 'specId');
        $sku_spec_attr = arrayUnset($sku_spec_attr, 'attrId');
        foreach ($sku_spec_list as &$sval) {
            foreach ($sku_spec_attr as $v) {
                if ($v['specId'] == $sval['specId']) {
                    $attrInfo['skuId'] = $v['skuId'];
                    $attrInfo['attrId'] = $v['attrId'];
                    $attrInfo['attrName'] = $v['attrName'];
                    $sval['attrList'][] = $attrInfo;
                }
            }
        }
        unset($sval);
        $goods_detail['goodsSku']['skuList'] = $sku_list;
        $goods_detail['goodsSku']['skuSpec'] = $sku_spec_list;
        return $goods_detail;
    }

    /**
     * 获取拼团商品的拼团人数
     * @param array $goods 拼团商品数据
     * @return array
     * */
    public function getCountGoodsAssembleUser($goods)
    {
        $goods = (array)$goods;
        if (empty($goods)) {
            return $goods;
        }
        if (array_keys($goods) !== range(0, count($goods) - 1)) {
            //详情
            $goods['assembleUserNum'] = $this->countGoodsAssembleUser($goods['goodsId']);
        } else {
            //列表
            foreach ($goods as $key => $val) {
                $goods[$key]['assembleUserNum'] = $this->countGoodsAssembleUser($val['goodsId']);
            }
        }
        return $goods;
    }

    /**
     * 获取该拼团商品已被多少用户拼过
     * @param int $goods_id 商品id
     * @param array $array
     * */
    public function countGoodsAssembleUser(int $goods_id)
    {
        $count = 0;
        $where = array(
            'goodsId' => $goods_id,
            'aFlag' => 1,
        );
        $assemble_activity_model = new AssembleActivityModel();
        $activity_lsit = $assemble_activity_model
            ->where($where)
            ->select();//该商品参与过多少次拼团活动
        if (!empty($activity_lsit)) {
            $aid = array();
            foreach ($activity_lsit as $val) {
                $aid[] = $val['aid'];
            }
            $where = [];
            $where['relation.aid'] = ["IN", $aid];
            $relation_model = new UserActivityRelationModel();
            $field = 'relation.uid,relation.pid,relation.aid,relation.shopId,relation.orderId,relation.createTime,relation.state';
            $field .= ',o.orderStatus,o.isPay';
            $list = $relation_model
                ->alias('relation')
                ->join("left join wst_orders o on o.orderId=relation.orderId")
                ->field($field)
                ->where($where)->select();
            foreach ($list as $val) {
                if ($val['isPay'] == 1) {
                    $count += 1;
                }
            }
        }
        return (int)$count;
    }

    /**
     * 拼团活动-详情-id获取
     * @param int $aid 活动id
     * @return array
     * */
    public function getAssembleActiveDetailById(int $aid)
    {
        $where = array();
        $where['activity.aid'] = $aid;
        $where['activity.aFlag'] = 1;
        $field = 'activity.aid,activity.shopId,activity.title,activity.groupPeopleNum,activity.limitNum,activity.goodsId,activity.tprice,activity.startTime,activity.endTime,activity.describle,activity.createTime,activity.limitHour,activity.aFlag,activity.activityStatus,';
        $field .= 'goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.marketPrice,goods.shopPrice,goods.goodsStock,goods.saleCount,goods.goodsSpec,goods.isSale,goods.goodsDesc,goods.saleTime,goods.markIcon,goods.SuppPriceDiff,goods.weightG,goods.IntelligentRemark,goods.isMembershipExclusive,goods.memberPrice,goods.integralReward,goods.buyNum,goods.spec,goods.unit,';
        $field .= 'shop.deliveryMoney,shop.deliveryFreeMoney,shop.shopAddress';
        $model = new AssembleActivityModel();
        $prefix = M()->tablePrefix;
        $result = $model
            ->alias('activity')
            ->join("left join {$prefix}goods goods on goods.goodsId = activity.goodsId")
            ->join("left join {$prefix}shops shop on activity.shopId = shop.shopId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $result = $this->getCountGoodsAssembleUser($result);
        $goodsInfo = $this->getActivityGoods($aid);
        $result['hasGoodsSku'] = $goodsInfo['hasGoodsSku'];
        $result['goodsSku'] = $goodsInfo['goodsSku'];
        return $result;
    }

    /**
     * 拼团-校验拼团
     * @param int $pid 拼团id
     * @param int $user_id 用户id
     * @return array
     * */
    public function verificationAssemble(int $pid, $user_id)
    {
        //该方法逻辑直接复制之前的代码，太多，不重写了
        $assemble_model = new AssembleModel();
        $prefix = M()->tablePrefix;
        $where = array();
        $where['pid'] = $pid;
        $where['userId'] = $user_id;
        $where['pFlag'] = 1;
        $assemble_detail = $assemble_model->where($where)->find();
        if (!empty($assemble_detail)) {
//            return returnData(false, -1, 'error', "请不要重复提交");
            return returnData(false, -1, 'error', "您已经参与过该拼团了，请不要重复提交");
        }
        unset($where['userId']);
        $assemble_detail = $assemble_model->where($where)->find();
        $relation_model = new UserActivityRelationModel();
        $aid = $assemble_detail['aid'];
        $shop_id = $assemble_detail['shopId'];
        $relation_where = array(
            'pid' => $pid,
            'aid' => $aid,
            'shopId' => $shop_id,
        );
        $relation_detail = $relation_model
            ->where($relation_where)
            ->order('createTime asc')
            ->find();
        if (!empty($relation_detail)) {
            if ($relation_detail['state'] == -3) {
                return returnData(false, -1, 'error', "该拼团开团失败");
            }
        }
        $realtion_where = array(
            'relation.pid' => $pid,
            'relation.aid' => $aid,
            'relation.shopId' => $shop_id,
            'relation.state' => 0,
            'orders.isPay' => 1,
            'orders.isRefund' => 0,
            'orders.orderStatus' => array(' in ', '0,15')
        );
        $uar_assemble_data = $relation_model
            ->alias('relation')
            ->join("left join {$prefix}orders orders on orders.orderId = relation.orderId")
            ->where($realtion_where)
            ->find();
        if (empty($uar_assemble_data)) {
            return returnData(false, -1, 'error', "该拼团已失效");
        }
        return returnData(true);
    }

    /**
     * 校验拼团商品的状态
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuid
     * @return array
     * */
    public function verificationAssembleGoodsStatus(int $goods_id, int $sku_id)
    {
        $goods_module = new GoodsModule();
        $goods_field = 'goodsId,goodsName,goodsImg,goodsSn,isSale';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $goods_field, 2);
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        if ($goods_detail['isSale'] != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品已被下架');
        }
        if (!empty($sku_id)) {
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if ($sku_detail) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品sku信息有误');
            }
            if ($sku_detail['goodsId'] != $goods_id) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品和sku不匹配');
            }
        }
        return returnData(true);
    }

    /**
     * 校验拼团商品的库存
     * @param int $goods_id 商品id
     * @param int $sku_id 商品sku_id
     * @param float $goods_cnt 购买的数量
     * @return array
     * */
    public function verificationAssembleGoodsStock(int $goods_id, int $sku_id, float $goods_cnt)
    {
        $goods_module = new GoodsModule();
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, '*', 2);
        if (empty($goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
//        $goods_stock = (float)$goods_detail['goodsStock'];
        $goods_stock = (float)$goods_detail['selling_stock'];
        $goods_cnt_stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
        if ($sku_id <= 0) {
            if ($goods_detail['SuppPriceDiff'] == 1) {
                //称重商品
                if (bccomp($goods_cnt_stock, $goods_stock, 3) == 1) {
//                    $can_buy_num = bc_math($goods_stock, ($goods_detail['weightG'] / 1000), 'bcdiv', 0);//最多可以买过多少数量
//                    $can_buy_num = (int)$can_buy_num;
                    $can_buy_num = bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
                    $can_buy_num = (int)$can_buy_num;
                    return returnData(['canbuyNum' => $can_buy_num], ExceptionCodeEnum::FAIL, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                }
            } else {
                //标品
                if ($goods_cnt_stock > $goods_stock) {
                    $goods_stock = (int)$goods_stock;
                    $can_buy_num = (int)bc_math($goods_stock, $goods_detail['weightG'], 'bcdiv', 0);
                    return returnData(['canbuyNum' => $can_buy_num], ExceptionCodeEnum::FAIL, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                }
            }
        }
        if ($sku_id > 0) {//有sku
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (empty($sku_detail) || $sku_detail['goodsId'] != $goods_id) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品id和skuId不匹配，请检查');
            }
//            $goods_stock = $sku_detail['skuGoodsStock'];
            $goods_stock = $sku_detail['selling_stock'];
            if ($goods_detail['SuppPriceDiff'] == 1) {
                //称重商品
                if (bccomp($goods_cnt_stock, $goods_stock, 3) == 1) {
//                    $can_buy_num = bc_math($goods_stock, ($sku_detail['weightG'] / 1000), 'bcdiv', 0);//最多可以买过多少数量
                    $can_buy_num = bc_math($goods_stock, $sku_detail['weightG'], 'bcdiv', 0);//最多可以买过多少数量
                    $can_buy_num = (int)$can_buy_num;
                    return returnData(['canbuyNum' => $can_buy_num], -1, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                }
            } else {
                //标品
                if ($goods_cnt_stock > $goods_stock) {
                    $goods_stock = (int)$goods_stock;
                    $can_buy_num = (int)bc_math($goods_stock, $sku_detail['weigetG'], 'bcdiv', 0);
                    return returnData(['canbuyNum' => $can_buy_num], -1, 'error', '库存数量不足，最多可购买数量' . $can_buy_num);
                }
            }
        }
        return returnData(true);
    }

    /**
     * 拼团商品的sku信息
     * @param int $aid 拼团活动id
     * @param int $goods_id 拼团商品id
     * @param int $sku_id 商品规格id
     * @return array
     * */
    public function getActivityGoodsSkuDetailByParams(int $aid, int $goods_id, int $sku_id)
    {
        $model = new AssembleActivityGoodsSkuModel();
        $where = array(
            'aid' => $aid,
            'goodsId' => $goods_id,
            'sku_id' => $sku_id,
        );
        $detail = $model->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        $detail['sku_detail'] = array();
        if ($sku_id > 0) {
            $goods_module = new GoodsModule();
            $detail['sku_detail'] = $goods_module->getSkuSystemInfoById($sku_id, 2);
        }
        return $detail;
    }

    /**
     * 用户和拼团活动关联-保存
     * @param array $params
     * ...wst_user_activity_relation表字段
     * @param object $trans
     * @return int
     * */
    public function addUserActivityRelation(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new UserActivityRelationModel();
        $save_params = array(
            'uid' => null,
            'pid' => null,
            'aid' => null,
            'shopId' => null,
            'orderId' => null,
            'state' => null,
            'is_pay' => null,
            'createTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($save_params, $params);
        $save_res = $model->add($save_params);
        if (!$save_res) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 拼团记录-详情-id获取
     * @param int $pid 拼团id
     * @param string $field 表字段
     * @return array
     * */
    public function getAssembleDetailByPid(int $pid, string $field)
    {
        $where = array(
            'pFlag' => 1,
            'pid' => $pid
        );
        $model = new AssembleModel();
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 拼团记录-保存
     * @param array $params
     * ...wst_assemble表字段
     * @param object $trans
     * @return int
     * */
    public function saveAssemble(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'aid' => null,
            'shopId' => null,
            'title' => null,
            'buyPeopleNum' => null,
            'groupPeopleNum' => null,
            'limitNum' => null,
            'goodsId' => null,
            'skuId' => null,
            'tprice' => null,
            'startTime' => null,
            'endTime' => null,
            'describle' => null,
            'state' => null,
            'isRefund' => null,
            'pFlag' => null,
            'userId' => null,
            'assembleUserName' => null,
            'assembleUserPhone' => null,
        );
        parm_filter($save_params, $params);
        $model = new AssembleModel();
        if (empty($params['pid'])) {
            $save_params['createTime'] = date('Y-m-d H:i:s');
            $pid = $model->add($save_params);
            if (empty($pid)) {
                $db_trans->rollback();
                return 0;
            }
        } else {
            $pid = $params['pid'];
            $where = array(
                'pid' => $pid
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
        return $pid;
    }
}
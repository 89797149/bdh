<?php

namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Models\PromotionClassModel;
use App\Models\PromotionClassRelationModel;
use App\Models\PromotionFullModel;
use App\Models\PromotionSpecialDMModel;
use App\Models\PromotionSpecialSingleModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Pos\PromotionModule;
use App\Modules\Shops\ShopCatsModule;
use CjsProtocol\LogicResponse;
use Think\Model;
use v3\Action\RewardAction;

/**
 * 促销
 * */
class PromotionModel extends BaseModel
{
    /**
     * DM档期计划-新增档期计划
     * @param array $params <p>
     * string title DM档期标题
     * date sale_start_date 售价开始时间
     * date sale_end_date 售价结束时间
     * date purchase_start_date 进价开始时间
     * date purchase_end_date 进价结束时间
     * string remark 备注
     * </p>
     * @param array $login_info
     * */
    public function addSchedule(array $params, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $module = new PromotionModule();
        $shop_id = $login_info['shopId'];
        $params['shop_id'] = $shop_id;
        $where = array(
            'shop_id' => $shop_id,
            'title' => $params['title'],
        );
        $info_result = $module->getScheduleInfoByParams($where);
        if ($info_result['code'] == ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("{$params['title']}档期名称已存在，请更换档期名称")->toArray();
        }
        $where = array(
            'shop_id' => $shop_id,
            'sale_start_date' => $params['sale_start_date'],
            'sale_end_date' => $params['sale_end_date'],
        );
        $info_result = $module->getScheduleInfoByParams($where);
        if ($info_result['code'] == ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("档期售价日期{$params['sale_start_date']}~{$params['sale_end_date']}已存在，请更换其他日期")->toArray();
        }
        $result = $module->addSchedule($params);
        return $result;
    }

    /**
     * DM档期计划-修改档期计划
     * @param array $params <p>
     * @param int schedule_id 档期id
     * string title DM档期标题
     * date sale_start_date 售价开始时间
     * date sale_end_date 售价结束时间
     * date purchase_start_date 进价开始时间
     * date purchase_end_date 进价结束时间
     * string remark 备注
     * </p>
     * @param array $login_info
     * */
    public function updateSchedule(array $params, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $module = new PromotionModule();
        $shop_id = $login_info['shopId'];
        $params['shop_id'] = $shop_id;
        $where = array(
            'shop_id' => $shop_id,
            'title' => $params['title'],
        );
        $info_result = $module->getScheduleInfoByParams($where);
        if ($info_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $info_data = $info_result['data'];
            if ($info_data['schedule_id'] !== $params['schedule_id']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("{$params['title']}档期名称已存在，请更换档期名称")->toArray();
            }
        }
        $where = array(
            'shop_id' => $shop_id,
            'sale_start_date' => $params['sale_start_date'],
            'sale_end_date' => $params['sale_end_date'],
        );
        $info_result = $module->getScheduleInfoByParams($where);
        if ($info_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $info_data = $info_result['data'];
            if ($info_data['schedule_id'] != $params['schedule_id']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("档期售价日期{$params['sale_start_date']}~{$params['sale_end_date']}已存在，请更换其他日期")->toArray();
            }
        }
        $result = $module->updateSchedule($params);
        if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
            //修改和档期计划相关的促销单促销开始和结束时间
            $promotion_result = $module->getPromotionListByScheduleId($info_data['schedule_id']);
            if (!empty($promotion_result)) {
                $promotion_data = $promotion_result['data'];
                foreach ($promotion_data as $item) {
                    $promotion_id = $item['promotion_id'];
                    $save = array(
                        'promotion_id' => $promotion_id,
                        'start_date' => $params['sale_start_date'],
                        'end_date' => $params['sale_end_date'],
                    );
                    $module->updatePromotion($save);
                }
            }
        }
        return $result;
    }

    /**
     * DM档期计划-获取档期计划列表
     * @param array $params <p>
     * int shop_id
     * string title DM档期标题
     * date sale_start_date 售价开始时间
     * date sale_end_date 售价结束时间
     * </p>
     * @return object
     */
    public function getScheduleList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = (int)$params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "shop_id={$shop_id} and is_delete=0 ";
        $where_find = array();
        $where_find['title'] = function () use ($params) {
            if (empty($params['title'])) {
                return null;
            }
            return array('like', "%{$params['title']}%", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find)) {
            $where_info = $where;
        } else {
            $where_info = "{$where} and {$where_find}";
        }
        if (!empty($params['sale_start_date'])) {
            $where_info .= " and sale_start_date >= '{$params['sale_start_date']}'";
        }
        if (!empty($params['sale_end_date'])) {
            $where_info .= " and sale_end_date <= '{$params['sale_end_date']}'";
        }
        $field = 'schedule_id,title,sale_start_date,sale_end_date,sale_day,purchase_start_date,purchase_end_date,purchase_day,remark,use_tag,create_time';
        $sql = "select {$field} from __PREFIX__promotion_schedule where {$where_info} order by schedule_id desc";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            }
            unset($item);
            $data['root'] = $root;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($data)->toArray();
    }

    /**
     * DM档期计划-获取未过期的档期计划列表
     * @param array $params <p>
     * int shop_id
     * string title DM档期标题
     * date sale_start_date 售价开始时间
     * date sale_end_date 售价结束时间
     * string sort_field 排序字段(schedule_id:档期id)
     * string sort_value 排序值(ASC:正序 DESC:倒序)
     * </p>
     * @return object
     */
    public function getEffectiveScheduleList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = (int)$params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $sort_field = $params['sort_field'];
        $sort_value = $params['sort_value'];
        $date = date('Y-m-d');
        $where = "shop_id={$shop_id} and is_delete=0 and sale_end_date >= '{$date}'";
        $where_find = array();
        $where_find['title'] = function () use ($params) {
            if (empty($params['title'])) {
                return null;
            }
            return array('like', "%{$params['title']}%", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find)) {
            $where_info = $where;
        } else {
            $where_info = "{$where} and {$where_find}";
        }
        if (!empty($params['sale_start_date'])) {
            $where_info .= " and sale_start_date >= '{$params['sale_start_date']}'";
        }
        if (!empty($params['sale_end_date'])) {
            $where_info .= " and sale_end_date <= '{$params['sale_end_date']}'";
        }
        $field = 'schedule_id,title,sale_start_date,sale_end_date,sale_day,purchase_start_date,purchase_end_date,purchase_day,remark,use_tag,create_time';
        $sql = "select {$field} from __PREFIX__promotion_schedule where {$where_info} order by {$sort_field} {$sort_value}";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            }
            unset($item);
            $data['root'] = $root;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($data)->toArray();
    }

    /**
     * DM档期计划-获取档期计划详情
     * 文档链接地址:
     * @param int schedule_id 档期计划id
     * @return array
     */
    public function getScheduleInfo(int $schedule_id)
    {
        $module = new PromotionModule();
        $field = 'schedule_id,title,sale_start_date,sale_end_date,sale_day,purchase_start_date,purchase_end_date,purchase_day,remark,use_tag,create_time';
        $result = $module->getScheduleInfoById($schedule_id, $field);
        return $result;
    }

    /**
     * DM档期计划-获取档期计划详情
     * 文档链接地址:
     * @param string schedule_id 档期计划id,多个用英文逗号分隔
     * @return array
     */
    public function delSchedule(string $schedule_id)
    {
        $module = new PromotionModule();
        $result = $module->delSchedule($schedule_id);
        return $result;
    }

    /**
     * 促销单-新增DM商品特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/biy7o4
     * @param array params 业务参数
     * @param int schedule_id 档期计划id
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function addDMSpecial(array $params, int $schedule_id, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        $schedule_result = $this->getScheduleInfo($schedule_id);
        if ($schedule_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择正确的档期计划")->toArray();
        }
        $schedule_data = $schedule_result['data'];
        if (strtotime($schedule_data['sale_end_date'] . ' 23:59:59') < time()) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("档期计划已过期，请选择可用的档期计划")->toArray();
        }
        $start_date = $schedule_data['sale_start_date'];
        $end_date = $schedule_data['sale_end_date'];
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName,shopPrice,goodsUnit');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            if ($item['sale_price'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销售价必须大于0")->toArray();
            }
            if ($item['purchase_price'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销进价必须大于0")->toArray();
            }
            $sku_id = (int)$item['sku_id'];
            $sku_info = array();
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
//            if ((float)$item['purchase_price'] > (float)$item['sale_price']) {
//                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销售价必须大于促销进价")->toArray();
//            }
            if ($sku_id > 0) {
                $item['shopPrice'] = $sku_info['skuShopPrice'];
                $item['goodsUnit'] = $goods_info['goodsUnit'];//PS:sku暂无进价,所以继续沿用商品主表中的进价字段
            } else {
                $item['shopPrice'] = $goods_info['shopPrice'];
                $item['goodsUnit'] = $goods_info['goodsUnit'];
            }
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        $promotion_data = array(
            'shop_id' => $shop_id,
            'schedule_id' => $schedule_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => 1,
            'creator_type' => $login_info['user_type'],
            'creatot_id' => $login_info['user_id'],
            'creator_name' => $login_info['user_username'],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->addPromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单创建失败")->toArray();
        }
        $promotion_data = $insert_promotion_res['data'];
        $promotion_id = $promotion_data['promotion_id'];
        $special_arr = array();
        $create_time = time();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            //$info['schedule_id'] = (int)$schedule_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['purchase_price'] = (float)$item['purchase_price'];
            $info['sale_price'] = (float)$item['sale_price'];
            $info['limited_purchase'] = (int)$item['limited_purchase'];
            $info['limited_single'] = (int)$item['limited_single'];
            $promotion_profit = bc_math($info['sale_price'], $info['purchase_price'], 'bcsub', 4);
            $info['promotion_profit'] = bc_math($promotion_profit, $info['purchase_price'], 'bcdiv', 2);//（促销售价-促销进价）/促销进价
            $profit_rate = (float)bc_math($item['shopPrice'], $item['goodsUnit'], 'bcsub', 2);
            $info['profit_rate'] = (float)bc_math($profit_rate, $item['goodsUnit'], 'bcdiv', 2);//（原售价-原进价）/原进价
            $info['remark'] = (string)$item['remark'];
            $info['create_time'] = $create_time;
            $special_arr[] = $info;
        }
        $dm_model = new PromotionSpecialDMModel();
        $result = $dm_model->addAll($special_arr);
        if (!$result) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
        }
        $m->commit();
        //更改DM档期计划的引用状态-start
        $module->updateScheduleUseTag($schedule_id);
        //更改DM档期计划的引用状态-end
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-新增单品特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/biy7o4
     * @param array params 业务参数
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function addSpecialSingle(array $params, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        $start_date_arr = array();
        $end_date_arr = array();
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            if (empty($item['start_date']) || empty($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始日期和结束日期")->toArray();
            }
            if (empty($item['start_time']) || empty($item['end_time'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始时间和结束时间")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            $sku_id = (int)$item['sku_id'];
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
            if (strtotime($item['start_date']) > strtotime($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的开始促销日期不能大于结束促销日期")->toArray();
            }
            if ((float)$item['special_price'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价")->toArray();
            }
            if ((float)$item['score'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价积分")->toArray();
            }
            $start_date_arr[] = $item['start_date'];
            $end_date_arr[] = $item['end_date'];
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        usort($start_date_arr, 'compareByTimeStamp');
        usort($end_date_arr, 'compareByTimeStamp');
        $promotion_data = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date_arr[count($start_date_arr) - 1],
            'end_date' => $end_date_arr[0],
            'data_type' => 2,
            'creator_type' => $login_info['user_type'],
            'creatot_id' => $login_info['user_id'],
            'creator_name' => $login_info['user_username'],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->addPromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单创建失败")->toArray();
        }
        $promotion_data = $insert_promotion_res['data'];
        $promotion_id = $promotion_data['promotion_id'];
        $special_arr = array();
        $create_time = time();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['special_price'] = (float)$item['special_price'];
            $info['limited_purchase'] = (int)$item['limited_purchase'];
            $info['limited_single'] = (int)$item['limited_single'];
            $info['score'] = (int)$item['score'];
            $info['start_date'] = (string)$item['start_date'];
            $info['end_date'] = (string)$item['end_date'];
            $info['start_time'] = (string)$item['start_time'];
            $info['end_time'] = (string)$item['end_time'];
            $info['remark'] = (string)$item['remark'];
            $info['create_time'] = $create_time;
            $special_arr[] = $info;
        }
        $special_single_model = new PromotionSpecialSingleModel();
        $result = $special_single_model->addAll($special_arr);
        if (!$result) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('特价商品添加失败')->toArray();
        }
        $m->commit();
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-买满数量后特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/biy7o4
     * @param array params 业务参数
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function addSpecialFull(array $params, string $start_date, string $end_date, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        if (strtotime($start_date) > strtotime($end_date)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("开始日期不能大于结束日期")->toArray();
        }
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            if ($item['special_price'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的特价必须大于0")->toArray();
            }
            if ($item['need_buy_num'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的每笔需购必须大于0")->toArray();
            }
            $sku_id = (int)$item['sku_id'];
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
            if ((float)$item['special_price'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价")->toArray();
            }
            if ((float)$item['need_buy_num'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}每笔需购必须大于0")->toArray();
            }
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        $promotion_data = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => 3,
            'creator_type' => $login_info['user_type'],
            'creatot_id' => $login_info['user_id'],
            'creator_name' => $login_info['user_username'],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->addPromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单创建失败")->toArray();
        }
        $promotion_data = $insert_promotion_res['data'];
        $promotion_id = $promotion_data['promotion_id'];
        $special_arr = array();
        $create_time = time();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['special_price'] = (float)$item['special_price'];
            $info['need_buy_num'] = (int)$item['need_buy_num'];
            $info['remark'] = (string)$item['remark'];
            $info['create_time'] = $create_time;
            $special_arr[] = $info;
        }
        $full_model = new PromotionFullModel();
        $result = $full_model->addAll($special_arr);
        if (!$result) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
        }
        $m->commit();
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改按类别折扣
     * 文档链接地址:
     * @param array $class_list 参与折扣的分类
     * @param array $filter_goods 不参与折扣的商品
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function addSpecialClass(array $class_list, array $filter_goods, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $shop_cats_module = new ShopCatsModule();
        //验证参数有效性-start
        $start_date_arr = array();
        $end_date_arr = array();
        foreach ($class_list as &$item) {
            if (empty($item['shop_class_id2'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全分类信息")->toArray();
            }
            if (empty($item['start_date']) || empty($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始日期和结束日期")->toArray();
            }
            if (empty($item['start_time']) || empty($item['end_time'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始时间和结束时间")->toArray();
            }
            $class_result = $shop_cats_module->getShopCatInfoById($item['shop_class_id2'], 'catId,catName,parentId');
            if ($class_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查分类信息是否有误")->toArray();
            }
            $class_info = $class_result['data'];
            if (strtotime($item['start_date']) > strtotime($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}的开始促销日期不能大于结束促销日期")->toArray();
            }
            if ((float)$item['discount'] <= 0 || (float)$item['discount'] >= 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}请输入正确的折扣区间，大于0且小于1")->toArray();
            }
            if ((int)$item['need_buy_num'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}的需购数量必须大于0")->toArray();
            }
            $item['shop_class_id1'] = $class_info['parentId'];
            $start_date_arr[] = $item['start_date'];
            $end_date_arr[] = $item['end_date'];
        }
        unset($item);
        $class_id_arr = array_column($class_list, 'shop_class_id2');
        $unique_class_id = array_unique($class_id_arr);
        $field = 'catId,catName';
        $class_list_result = $shop_cats_module->getShopCatListById($unique_class_id, $field);
        if ($class_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择分类")->toArray();
        }
        $cat_list = $class_list_result['data'];
        $diff_class_id = array_diff_assoc($class_id_arr, $unique_class_id);
        if (count($diff_class_id) > 0) {
            foreach ($diff_class_id as $val) {
                foreach ($cat_list as $cat_val) {
                    if ($class_list['catId'] == $val) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$cat_val['catName']}存在重复数据，请去除重复数据")->toArray();
                    }
                }
            }
        }
        //验证参数有效性-end
        usort($start_date_arr, 'compareByTimeStamp');
        usort($end_date_arr, 'compareByTimeStamp');
        $promotion_data = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date_arr[count($start_date_arr) - 1],
            'end_date' => $end_date_arr[0],
            'data_type' => 4,
            'creator_type' => $login_info['user_type'],
            'creatot_id' => $login_info['user_id'],
            'creator_name' => $login_info['user_username'],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->addPromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单创建失败")->toArray();
        }
        $promotion_data = $insert_promotion_res['data'];
        $promotion_id = $promotion_data['promotion_id'];
        if (!empty($filter_goods)) {
            $goods_module = new GoodsModule();
            $filter_goods = arrayUnset($filter_goods, 'goods_id');
            $relation_data = array();
            foreach ($filter_goods as $filter_val) {
                $goods_result = $goods_module->getGoodsInfoById($filter_val['goods_id'], 'goodsId,goodsName');
                if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("goodsId:{$filter_val['goods_id']}传参有误")->toArray();
                }
                $goods_info = $goods_result['data'];
                $del_res = $module->delSpecialClassRelationByParams($promotion_id, $filter_val['goods_id'], $filter_val['sku_id'], $m);
                if ($del_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("过滤商品添加失败")->toArray();
                }
                if (!empty($filter_val['sku_id'])) {
                    $sku_info_result = $goods_module->getSkuSystemInfoById($filter_val['sku_id']);
                    if ($sku_info_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku_id传参有误")->toArray();
                    }
                    $sku_info = $sku_info_result['data'];
                    if ($sku_info['goodsId'] != $filter_val['goods_id']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息传参有误")->toArray();
                    }
                }
                $info = array(
                    'promotion_id' => (int)$promotion_id,
                    'goods_id' => (int)$filter_val['goods_id'],
                    'sku_id' => (int)$filter_val['sku_id'],
                    'create_time' => time(),
                );
                $relation_data[] = $info;
            }
            $class_relation_model = new PromotionClassRelationModel();
            $add_relation_res = $class_relation_model->addAll($relation_data);
            if (!$add_relation_res) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("过滤商品添加失败")->toArray();
            }
        }
        $special_arr = array();
        $create_time = time();
        foreach ($class_list as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['shop_class_id1'] = (int)$item['shop_class_id1'];
            $info['shop_class_id2'] = (int)$item['shop_class_id2'];
            $info['discount'] = (float)$item['discount'];
            $info['need_buy_num'] = (int)$item['need_buy_num'];
            $info['start_date'] = (string)$item['start_date'];
            $info['end_date'] = (string)$item['end_date'];
            $info['start_time'] = (string)$item['start_time'];
            $info['end_time'] = (string)$item['end_time'];
            $info['remark'] = (string)$item['remark'];
            $info['create_time'] = $create_time;
            $special_arr[] = $info;
        }
        $special_class_module = new PromotionClassModel();
        $result = $special_class_module->addAll($special_arr);
        if (!$result) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
        }
        $m->commit();
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-获取促销单列表
     * @param array $params <p>
     * int shop_id 门店id
     * string bill_date 制单日期:days300:300天内 today:今天 yesterday:昨天 days3:近3天 lastWeek:上周 thisWeek:本周 twoWeek:近两周 自定义日期(例子:2020-11-11) 自定义日期区间:(例子:2020-11-12 - 2020-12-15)
     * int examine_status 审核状态(0:未审核 1:已审核)
     * int print_status 打印状态(0:未打印 1:已打印)
     * int data_type 促销方式(1:DM档期计划 2:单品特价 3:买满数量后特价 4:按类别折扣)
     * string bill_no 单据编号
     * string creator_name 制单人
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     * @return array
     */
    public function getPromotionList(array $params, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = (int)$params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = " pro.shop_id={$shop_id} ";
        $where_find = array();
        $where_find['pro.examine_status'] = function () use ($params) {
            if (!is_numeric($params['examine_status'])) {
                return null;
            }
            return array('=', "{$params['examine_status']}", 'and');
        };
        $where_find['pro.print_status'] = function () use ($params) {
            if (!is_numeric($params['print_status'])) {
                return null;
            }
            return array('=', "{$params['print_status']}", 'and');
        };
        $where_find['pro.data_type'] = function () use ($params) {
            if (empty($params['data_type'])) {
                return null;
            }
            return array('=', "{$params['data_type']}", 'and');
        };
        $where_find['pro.bill_no'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return array('like', "%{$params['bill_no']}%", 'and');
        };
        $where_find['pro.creator_name'] = function () use ($params) {
            if (empty($params['creator_name'])) {
                return null;
            }
            return array('like', "%{$params['creator_name']}%", 'and');
        };
        $where_find['goods.goodsName'] = function () use ($params) {
            if (empty($params['goods_keywords'])) {
                return null;
            }
            return array('like', "%{$params['goods_keywords']}%", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_info = "{$where} and {$where_find}";
        }
        if (!empty($params['bill_date'])) {
            $date_arr = getDateRules($params['bill_date']);
            $start_date = strtotime($date_arr['startDate']);
            $end_date = strtotime($date_arr['endDate']);
            $where_info .= " and pro.create_time >= {$start_date} and pro.create_time <= {$end_date} ";
        }
        $field = 'pro.promotion_id,pro.bill_no,pro.examine_status,pro.print_status,pro.take_effect_status,pro.start_date,pro.end_date,pro.data_type,pro.creator_name,pro.examine_time,pro.remark,pro.create_time';
        $sql = "select {$field} from __PREFIX__promotion pro ";
        $sql .= "where {$where_info}";
        $sql .= " order by pro.promotion_id desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($result['root'])) {
            $list = $result['root'];
            foreach ($list as &$item) {
                $item['examine_username'] = '';
                if ($item['examine_status'] == 1) {
                    $item['examine_username'] = $login_info['userName'];
                }
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                if ($item['examine_status'] == 0) {
                    $item['examine_time'] = '';
                } elseif ($item['examine_status'] == 1) {
                    $item['examine_time'] = date('Y-m-d H:i:s', $item['examine_time']);
                }
            }
            unset($item);
            $result['root'] = $list;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-获取促销单详情
     * @param int $promotion_id 促销单id
     * @param int $export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function getPromotionDetail(int $promotion_id, $export)
    {
        $module = new PromotionModule();
        $result = $module->getPromotionInfoById($promotion_id);
        if ($export == 1) {
            $response = LogicResponse::getInstance();
            $data = $result['data'];
            if ($data['data_type'] == 4) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('分类折扣不支持导入导出操作')->toArray();
            }
            $this->exportPromotionGoods($data);
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
        }
        return $result;
    }

    /**
     * 导出促销单商品
     * @param array $data 促销单详情数
     * @return array
     * */
    public function exportPromotionGoods(array $data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("促销单{$data['bill_no']}")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
        //设置excel工作表名及文件名
        $title = "促销单(" . $data['bill_no'] . ")商品信息";
        $excel_filename = "促销单(" . $data['bill_no'] . ")商品信息";
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        //第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excel_filename);
        //合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        //设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置excel的表头
        $data_type = $data['data_type'];
        if ($data_type == 1) {
            //DM商品特价
            $sheet_title = array('是否为SKU编码(是|否)', '编码', '商品名称', '规格', '原进价', '促销进价', '原售价', '促销售价', '全场限购', '单笔限购', '促销毛利', '毛利率', '备注', '商品类别');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N');
        } elseif ($data_type == 2) {
            //单商品特价
            $sheet_title = array('是否为SKU编码(是|否)', '编码', '商品名称', '规格', '零售价', '特价', '全场限购', '单笔限购', '特价积分', '开始促销日期', '结束促销日期', '开始促销时间', '结束促销时间', '备注', '商品类别');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O');
        } elseif ($data_type == 3) {
            //买满数量后特价
            $sheet_title = array('是否为SKU编码(是|否)', '编码', '商品名称', '规格', '零售价', '每笔需购', '特价', '备注', '商品类别');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        }
        $row_total = count($letter);
        for ($k = 0; $k < $row_total; $k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        }
        //开始赋值
        $goods_list = $data['goods_list'];
        for ($i = 0; $i < count($goods_list); $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $goods_list[$i];
            for ($j = 0; $j < $row_total; $j++) {
                switch ($j) {
                    case 0 :
                        //是否为SKU编码
                        if ($temp['sku_id'] > 0) {
                            $cellvalue = '是';
                        } else {
                            $cellvalue = '否';
                        }
                        break;
                    case 1 :
                        //编号
                        $cellvalue = $temp['goods_sn'];
                        break;
                    case 2 :
                        //商品名称
                        $cellvalue = $temp['goods_name'];
                        break;
                    case 3 :
                        //规格
                        $cellvalue = $temp['sku_spec_attr'];
                        break;
                    case 4 :
                        if ($data_type == 1) {
                            //原进价
                            $cellvalue = $temp['old_purchase_price'];
                        } elseif ($data_type == 2) {
                            //零售价
                            $cellvalue = $temp['old_purchase_price'];
                        } elseif ($data_type == 3) {
                            //零售价
                            $cellvalue = $temp['old_purchase_price'];
                        }
                        break;
                    case 5 :
                        if ($data_type == 1) {
                            //促销进价
                            $cellvalue = $temp['purchase_price'];
                        } elseif ($data_type == 2) {
                            //特价
                            $cellvalue = $temp['special_price'];
                        } elseif ($data_type == 3) {
                            //每笔需购
                            $cellvalue = $temp['need_buy_num'];
                        }
                        break;
                    case 6 :
                        if ($data_type == 1) {
                            //原售价
                            $cellvalue = $temp['old_sale_price'];
                        } elseif ($data_type == 2) {
                            //全场限购
                            $cellvalue = $temp['limited_purchase'];
                        } elseif ($data_type == 3) {
                            //特价
                            $cellvalue = $temp['special_price'];
                        }
                        break;
                    case 7 :
                        if ($data_type == 1) {
                            //促销售价
                            $cellvalue = $temp['sale_price'];
                        } elseif ($data_type == 2) {
                            //单笔限购
                            $cellvalue = $temp['limited_single'];
                        } elseif ($data_type == 3) {
                            //备注
                            $cellvalue = $temp['remark'];
                        }
                        break;
                    case 8 :
                        if ($data_type == 1) {
                            //全场限购
                            $cellvalue = $temp['limited_purchase'];
                        } elseif ($data_type == 2) {
                            //特价积分
                            $cellvalue = $temp['score'];
                        } elseif ($data_type == 3) {
                            //商品类别
                            $cellvalue = $temp['shop_catid2_name'];
                        }
                        break;
                    case 9 :
                        if ($data_type == 1) {
                            //单笔限购
                            $cellvalue = $temp['limited_single'];
                        } elseif ($data_type == 2) {
                            //开始促销日期
                            $cellvalue = $temp['start_date'];
                        }
                        break;
                    case 10 :
                        if ($data_type == 1) {
                            //促销毛利
                            $cellvalue = $temp['promotion_profit'];
                        } elseif ($data_type == 2) {
                            //结束促销日期
                            $cellvalue = $temp['end_date'];
                        }
                        break;
                    case 11 :
                        if ($data_type == 1) {
                            //毛利率
                            $cellvalue = $temp['profit_rate'];
                        } elseif ($data_type == 2) {
                            //开始促销时间
                            $cellvalue = $temp['start_time'];
                        }
                        break;
                    case 12 :
                        if ($data_type == 1) {
                            //备注
                            $cellvalue = $temp['remark'];
                        } elseif ($data_type == 2) {
                            //结束促销时间
                            $cellvalue = $temp['end_time'];
                        }
                        break;
                    case 13 :
                        if ($data_type == 1) {
                            //商品类别
                            $cellvalue = $temp['shop_catid2_name'];
                        } elseif ($data_type == 2) {
                            //备注
                            $cellvalue = $temp['remark'];
                            break;
                        }
                    case 13 :
                        if ($data_type == 2) {
                            //备注
                            $cellvalue = $temp['shop_catid2_name'];
                            break;
                        }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
        }
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle($title);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 促销单-修改DM商品特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sdn7gg
     * @param int $promotion_id 促销单id
     * @param array params 业务参数
     * @param int schedule_id 档期计划id
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function updateDMSpecial(int $promotion_id, array $params, int $schedule_id, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        //$shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        $schedule_result = $this->getScheduleInfo($schedule_id);
        if ($schedule_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择正确的档期计划")->toArray();
        }
        $schedule_data = $schedule_result['data'];
        if (strtotime($schedule_data['sale_end_date'] . ' 23:59:59') < time()) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("档期计划已过期，请选择可用的档期计划")->toArray();
        }
        $start_date = $schedule_data['sale_start_date'];
        $end_date = $schedule_data['sale_end_date'];
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName,shopPrice,goodsUnit');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            if ($item['purchase_price'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销进价必须大于0")->toArray();
            }
            if ($item['sale_price'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销售价必须大于0")->toArray();
            }
            $sku_id = (int)$item['sku_id'];
            $sku_info = array();
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
//            if ((float)$item['purchase_price'] > (float)$item['sale_price']) {
//                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的促销售价必须大于促销进价")->toArray();
//            }
            if ($sku_id > 0) {
                $item['shopPrice'] = $sku_info['skuShopPrice'];
                $item['goodsUnit'] = $goods_info['goodsUnit'];//PS:sku暂无进价,所以继续沿用商品主表中的进价字段
            } else {
                $item['shopPrice'] = $goods_info['shopPrice'];
                $item['goodsUnit'] = $goods_info['goodsUnit'];
            }
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        $promotion_data = array(
            'promotion_id' => $promotion_id,
            'schedule_id' => $schedule_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->updatePromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单修改失败")->toArray();
        }
        $module->updateSchedule();
        $special_arr = array();
        $create_time = time();
        $dm_model = new PromotionSpecialDMModel();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            //$info['schedule_id'] = (int)$schedule_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['purchase_price'] = (float)$item['purchase_price'];
            $info['sale_price'] = (float)$item['sale_price'];
            $info['limited_purchase'] = (int)$item['limited_purchase'];
            $info['limited_single'] = (int)$item['limited_single'];
            $promotion_profit = bc_math($info['sale_price'], $info['purchase_price'], 'bcsub', 4);
            $info['promotion_profit'] = bc_math($promotion_profit, $info['purchase_price'], 'bcdiv', 2);//（促销售价-促销进价）/促销进价
            $profit_rate = (float)bc_math($item['shopPrice'], $item['goodsUnit'], 'bcsub', 2);
            $info['profit_rate'] = (float)bc_math($profit_rate, $item['goodsUnit'], 'bcdiv', 2);//（原售价-原进价）/原进价
            $info['remark'] = (string)$item['remark'];
            if (empty($item['dm_id'])) {
                //新增
                $info['create_time'] = $create_time;
                $special_arr[] = $info;
            } else {
                //编辑
                $dm_result = $module->getSpecialDMInfoById($item['dm_id'], 'dm_id');
                if ($dm_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("DM商品特价id:{$item['dm_id']}传参有误")->toArray();
                }
                $dm_info = $dm_result['data'];
                $save = $info;
                $save['dm_id'] = $dm_info['dm_id'];
                $update_res = $module->updateDMGoods($save);
                if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("DM商品特价id:{$item['dm_id']}修改失败")->toArray();
                }
            }
        }
        $current_dm_id = array_column($params, 'dm_id');
        if (!empty($special_arr)) {
            foreach ($special_arr as $val) {
                $insert_id = $dm_model->add($val);
                if (!$insert_id) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
                }
                $current_dm_id[] = $insert_id;
            }
        }
        $m->commit();
        //删除被移除的数据-start
        $confirm_result = $module->getPromotionInfoById($promotion_id);
        $confirm_data = (array)$confirm_result['data'];
        $confirm_dm_list = $confirm_data['goods_list'];
        $confirm_dm_id = array_column($confirm_dm_list, 'dm_id');
        if (!empty($current_dm_id)) {
            foreach ($confirm_dm_id as $item) {
                if (!in_array($item, $current_dm_id)) {
                    $module->delSpecialDMById($item);
                }
            }
        } else {
            $module->delSpecialDMById($confirm_dm_id);
        }
        //删除被移除的数据-end
        //更改档期计划的引用状态-start
        $module->updateScheduleUseTag($schedule_id);
        //更改档期计划的引用状态-end
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改单品特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sdn7gg
     * @param int $promotion_id 促销单id
     * @param array params 业务参数
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function updateSpecialSingle(int $promotion_id, array $params, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        //$shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        $start_date_arr = array();
        $end_date_arr = array();
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            if (empty($item['start_date']) || empty($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始日期和结束日期")->toArray();
            }
            if (empty($item['start_time']) || empty($item['end_time'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全促销开始时间和结束时间")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            $sku_id = (int)$item['sku_id'];
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
            if (strtotime($item['start_date']) > strtotime($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的开始促销日期不能大于结束促销日期")->toArray();
            }
            if ((float)$item['special_price'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价")->toArray();
            }
            if ((float)$item['score'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价积分")->toArray();
            }
            $start_date_arr[] = $item['start_date'];
            $end_date_arr[] = $item['end_date'];
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        usort($start_date_arr, 'compareByTimeStamp');
        usort($end_date_arr, 'compareByTimeStamp');
        $promotion_data = array(
            'promotion_id' => $promotion_id,
            'start_date' => $start_date_arr[count($start_date_arr) - 1],
            'end_date' => $end_date_arr[0],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->updatePromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单修改失败")->toArray();
        }
        $special_arr = array();
        $create_time = time();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['special_price'] = (float)$item['special_price'];
            $info['limited_purchase'] = (int)$item['limited_purchase'];
            $info['limited_single'] = (int)$item['limited_single'];
            $info['score'] = (int)$item['score'];
            $info['start_date'] = (string)$item['start_date'];
            $info['end_date'] = (string)$item['end_date'];
            $info['start_time'] = (string)$item['start_time'];
            $info['end_time'] = (string)$item['end_time'];
            $info['remark'] = (string)$item['remark'];
            if (empty($item['single_id'])) {
                //新增
                $info['create_time'] = $create_time;
                $special_arr[] = $info;
            } else {
                //编辑
                $single_result = $module->getSpecialSingleInfoById($item['single_id']);
                if ($single_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("特价商品id:{$item['single_id']}传参有误")->toArray();
                }
                $single_info = $single_result['data'];
                $save = $info;
                $save['single_id'] = $single_info['single_id'];
                $update_res = $module->updateSingleGoods($save);
                if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("单商品商品特价id:{$item['single_id']}修改失败")->toArray();
                }
            }
        }
        $current_single_id = array_column($params, 'single_id');
        if (!empty($special_arr)) {
            $special_single_model = new PromotionSpecialSingleModel();
            foreach ($special_arr as $val) {
                $insert_id = $special_single_model->add($val);
                if (!$insert_id) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('特价商品添加失败')->toArray();
                }
                $current_single_id[] = $insert_id;
            }
        }
        $m->commit();
        //删除被移除的数据-start
        $confirm_result = $module->getPromotionInfoById($promotion_id);
        $confirm_data = (array)$confirm_result['data'];
        $confirm_single_list = $confirm_data['goods_list'];
        $confirm_single_id = array_column($confirm_single_list, 'single_id');
        if (!empty($current_single_id)) {
            foreach ($confirm_single_id as $item) {
                if (!in_array($item, $current_single_id)) {
                    $module->delSpecialSingleById($item);
                }
            }
        } else {
            $module->delSpecialSingleById($confirm_single_id);
        }
        //删除被移除的数据-end
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改买满数量后特价
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sdn7gg
     * @param int $promotion_id 促销单id
     * @param array params 业务参数
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function updateSpecialFull(int $promotion_id, array $params, string $start_date, string $end_date, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        //$shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $goods_module = new GoodsModule();
        //验证参数有效性-start
        if (strtotime($start_date) > strtotime($end_date)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("开始日期不能大于结束日期")->toArray();
        }
        $sku_id_arr = array();
        foreach ($params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            $sku_id = (int)$item['sku_id'];
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
            if ((float)$item['special_price'] < 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}请输入正确的特价")->toArray();
            }
            if ((float)$item['need_buy_num'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}每笔需购必须大于0")->toArray();
            }
        }
        unset($item);
        $goods_id_arr = array_column($params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //验证参数有效性-end
        $promotion_data = array(
            'promotion_id' => $promotion_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->updatePromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单修改失败")->toArray();
        }
        $special_arr = array();
        $create_time = time();
        foreach ($params as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['goods_id'] = (int)$item['goods_id'];
            $info['sku_id'] = (int)$item['sku_id'];
            $info['special_price'] = (float)$item['special_price'];
            $info['need_buy_num'] = (int)$item['need_buy_num'];
            $info['remark'] = (string)$item['remark'];
            if (empty($item['full_id'])) {
                //新增
                $info['create_time'] = $create_time;
                $special_arr[] = $info;
            } else {
                //编辑
                $full_result = $module->getSpecialFullInfoById($item['full_id']);
                if ($full_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("买满数量后特价id:{$item['full_id']}传参有误")->toArray();
                }
                $full_info = $full_result['data'];
                $save = $info;
                $save['full_id'] = $full_info['full_id'];
                $update_res = $module->updateFullGoods($save);
                if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("买满数量特价id:{$item['full_id']}修改失败")->toArray();
                }
            }
        }
        $current_full_id = array_column($params, 'full_id');
        if (!empty($special_arr)) {
            $full_model = new PromotionFullModel();
            foreach ($special_arr as $val) {
                $insert_id = $full_model->add($val);
                if (!$insert_id) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
                }
                $current_full_id[] = $insert_id;
            }
        }
        $m->commit();
        //删除被移除的数据-start
        $confirm_result = $module->getPromotionInfoById($promotion_id);
        $confirm_data = (array)$confirm_result['data'];
        $confirm_full_list = $confirm_data['goods_list'];
        $confirm_full_id = array_column($confirm_full_list, 'full_id');
        if (!empty($current_full_id)) {
            foreach ($confirm_full_id as $item) {
                if (!in_array($item, $current_full_id)) {
                    $module->delSpecialFullById($item);
                }
            }
        } else {
            $module->delSpecialFullById($confirm_full_id);
        }
        //删除被移除的数据-end
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改按类别折扣
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sdn7gg
     * @param int $promotion_id 促销单id
     * @param array $class_list 参与折扣的分类
     * @param array $filter_goods 不参与折扣的商品
     * @param string $remark 促销单备注
     * @param array $login_info
     * @return array
     */
    public function updateSpecialClass(int $promotion_id, array $class_list, array $filter_goods, string $remark, array $login_info)
    {
        $response = LogicResponse::getInstance();
        //$shop_id = $login_info['shopId'];
        $module = new PromotionModule();
        $shop_cats_module = new ShopCatsModule();
        //验证参数有效性-start
        $start_date_arr = array();
        $end_date_arr = array();
        foreach ($class_list as &$item) {
            if (empty($item['shop_class_id2']) || empty($item['start_date']) || empty($item['end_date']) || empty($item['start_time']) || empty($item['end_time'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $class_result = $shop_cats_module->getShopCatInfoById($item['shop_class_id2'], 'catId,catName,parentId');
            if ($class_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查分类信息是否有误")->toArray();
            }
            $class_info = $class_result['data'];
            if (strtotime($item['start_date']) > strtotime($item['end_date'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}的开始促销日期不能大于结束促销日期")->toArray();
            }
            if ((float)$item['discount'] <= 0 || (float)$item['discount'] >= 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}请输入正确的折扣区间，大于0且小于1")->toArray();
            }
            if ((int)$item['need_buy_num'] <= 0) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$class_info['catName']}的需购数量必须大于0")->toArray();
            }
            $item['shop_class_id1'] = $class_info['parentId'];
            $start_date_arr[] = $item['start_date'];
            $end_date_arr[] = $item['end_date'];
        }
        unset($item);
        $class_id_arr = array_column($class_list, 'shop_class_id2');
        $unique_class_id = array_unique($class_id_arr);
        $field = 'catId,catName';
        $class_list_result = $shop_cats_module->getShopCatListById($unique_class_id, $field);
        if ($class_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择分类")->toArray();
        }
        $cat_list = $class_list_result['data'];
        $diff_class_id = array_diff_assoc($class_id_arr, $unique_class_id);
        if (count($diff_class_id) > 0) {
            foreach ($diff_class_id as $val) {
                foreach ($cat_list as $cat_val) {
                    if ($class_list['catId'] == $val) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类{$cat_val['catName']}存在重复数据，请去除重复数据")->toArray();
                    }
                }
            }
        }
        //验证参数有效性-end
        usort($start_date_arr, 'compareByTimeStamp');
        usort($end_date_arr, 'compareByTimeStamp');
        $promotion_data = array(
            'promotion_id' => $promotion_id,
            'start_date' => $start_date_arr[count($start_date_arr) - 1],
            'end_date' => $end_date_arr[0],
            'remark' => $remark,
        );
        $m = new Model();
        $m->startTrans();
        $insert_promotion_res = $module->updatePromotion($promotion_data, $m);
        if ($insert_promotion_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单修改失败")->toArray();
        }
        if (!empty($filter_goods)) {
            $goods_module = new GoodsModule();
            $filter_goods = arrayUnset($filter_goods, 'goods_id');
            foreach ($filter_goods as $filter_val) {
                $goods_result = $goods_module->getGoodsInfoById($filter_val['goods_id'], 'goodsId,goodsName');
                if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("goodsId:{$filter_val['goods_id']}传参有误")->toArray();
                }
                $goods_info = $goods_result['data'];
                $del_res = $module->delSpecialClassRelationByParams($promotion_id, $filter_val['goods_id'], $filter_val['sku_id'], $m);
                if ($del_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("过滤商品修改失败")->toArray();
                }
                if (!empty($filter_val['sku_id'])) {
                    $sku_info_result = $goods_module->getSkuSystemInfoById($filter_val['sku_id']);
                    if ($sku_info_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku_id传参有误")->toArray();
                    }
                    $sku_info = $sku_info_result['data'];
                    if ($sku_info['goodsId'] != $filter_val['goods_id']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息传参有误")->toArray();
                    }
                }
                $info = array(
                    'promotion_id' => (int)$promotion_id,
                    'goods_id' => (int)$filter_val['goods_id'],
                    'sku_id' => (int)$filter_val['sku_id'],
                    'create_time' => time(),
                );
                $relation_data[] = $info;
            }
            $class_relation_model = new PromotionClassRelationModel();
            $add_relation_res = $class_relation_model->addAll($relation_data);
            if (!$add_relation_res) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("过滤商品添加失败")->toArray();
            }
        } else {
            $del_res = $module->delSpecialClassRelationByParams($promotion_id, null, null, $m);
            if ($del_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("过滤商品修改失败")->toArray();
            }
        }
        $special_arr = array();
        $create_time = time();
        foreach ($class_list as $item) {
            $info = array();
            $info['promotion_id'] = (int)$promotion_id;
            $info['shop_class_id1'] = (int)$item['shop_class_id1'];
            $info['shop_class_id2'] = (int)$item['shop_class_id2'];
            $info['discount'] = (float)$item['discount'];
            $info['need_buy_num'] = (int)$item['need_buy_num'];
            $info['start_date'] = (string)$item['start_date'];
            $info['end_date'] = (string)$item['end_date'];
            $info['start_time'] = (string)$item['start_time'];
            $info['end_time'] = (string)$item['end_time'];
            $info['remark'] = (string)$item['remark'];
            if (empty($item['discount_id'])) {
                //新增
                $info['create_time'] = $create_time;
                $special_arr[] = $info;
            } else {
                //编辑
                $discount_result = $module->getSpecialDiscountInfoById($item['discount_id']);
                if ($discount_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("分类折扣id:{$item['discount_id']}传参有误")->toArray();
                }
                $discount_info = $discount_result['data'];
                $save = $info;
                $save['discount_id'] = $discount_info['discount_id'];
                $update_res = $module->updateDiscountGoods($save);
                if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("按分类折扣id:{$item['discount_id']}修改失败")->toArray();
                }
            }
        }
        $current_discount_id = array_column($class_list, 'discount_id');
        if (!empty($special_arr)) {
            $special_class_module = new PromotionClassModel();
            foreach ($special_arr as $val) {
                $insert_id = $special_class_module->add($val);
                if (!$insert_id) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
                }
                $current_discount_id[] = $insert_id;
            }
        }
        $m->commit();
        //删除被移除的数据-start
        $confirm_result = $module->getPromotionInfoById($promotion_id);
        $confirm_data = (array)$confirm_result['data'];
        $confirm_class_list = $confirm_data['class_list'];
        $confirm_discount_id = array_column($confirm_class_list, 'discount_id');
        if (!empty($current_discount_id)) {
            foreach ($confirm_discount_id as $item) {
                if (!in_array($item, $current_discount_id)) {
                    $module->delSpecialClassById($item);
                }
            }
        } else {
            $module->delSpecialClassById($confirm_discount_id);
        }
        //删除被移除的数据-end
        $returnData = array(
            'promotion_id' => $promotion_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($returnData)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改促销单状态(审核 打印)
     * @param string promotion_id 促销单id,多个用英文逗号分隔
     * @param string field 业务字段名(examine_status:审核状态 print_status:打印状态)
     * @param int value 业务字段值(1:审核通过,打印)
     * @param array $login_info
     * @return json
     * */
    public function examinePromotion($promotion_id, string $field, int $value, array $login_info)
    {
        $response = LogicResponse::getInstance();
        if (in_array($field, array('examine_status'))) {
            if ($login_info['user_type'] != 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('非总管理员无权审核促销单')->toArray();
            }
        }
        $module = new PromotionModule();
        $promotion_result = $module->getPromotionListById($promotion_id);
        if ($promotion_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败，错误的促销单')->toArray();
        }
        $promotion_list = $promotion_result['data'];
        $m = new Model();
        if ($field == 'examine_status') {
            $save = array(
                'examine_status' => 1,
                'take_effect_status' => 1
            );
        } elseif ($field == 'print_status') {
            $save = array(
                'print_status' => 1,
            );
        }
        foreach ($promotion_list as $item) {
            if ($field == 'examine_status') {
                if ($item['examine_status'] == 1) {
                    $m->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("促销单{$item['bill_no']}已审核，不能重复审核")->toArray();
                }
            }
            $save['promotion_id'] = $item['promotion_id'];
            $update_res = $module->updatePromotion($save);
            if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("审核失败")->toArray();
            }
        }
        $m->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-修改促销单商品的生效状态
     * @param string $id 促销商品数据id(取值dm_id:DM商品特价id,single_id:单商品特价id,full_id:买满数量后特价id,discount_id:分类折扣id)
     * @param int $data_type 促销方式(1:DM档期计划 2:单品特价 3:买满数量后特价 4:按类别折扣)
     * @param int $effect_status 状态值(0:生效 1:终止)
     * @return json
     * */
    public function updateGoodsEffectStatus($id, int $effect_status, int $data_type, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $id_arr = explode(',', $id);
        $module = new PromotionModule();
        $m = new Model();
        $promotion_id_arr = array();
        foreach ($id_arr as $item) {
            $id = $item;
            if ($data_type == 1) {
                $result = $module->getSpecialDMInfoById($id);
            } elseif ($data_type == 2) {
                $result = $module->getSpecialSingleInfoById($id);
            } elseif ($data_type == 3) {
                $result = $module->getSpecialFullInfoById($id);
            } elseif ($data_type == 4) {
                $result = $module->getSpecialDiscountInfoById($id);
            }
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('提交的数据存在异常数据')->toArray();
            }
            $info = $result['data'];
            $promotion_result = $module->getPromotionInfoById($info['promotion_id']);
            $promotion_info = (array)$promotion_result['data'];
            if ($promotion_info['examine_status'] != 1) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("未审核通过的促销单{$info['bill_no']}不能执行该操作")->toArray();
            }
            $promotion_id = $info['promotion_id'];
            $promotion_id_arr[] = $promotion_id;
            $save = array(
                'take_effect_status' => $effect_status
            );
            if ($data_type == 1) {
                $save['dm_id'] = $id;
                $update_res = $module->updateDMGoods($save, $m);
            } elseif ($data_type == 2) {
                $save['single_id'] = $id;
                $update_res = $module->updateSingleGoods($save, $m);
            } elseif ($data_type == 3) {
                $save['full_id'] = $id;
                $update_res = $module->updateFullGoods($save, $m);
            } elseif ($data_type == 4) {
                $save['discount_id'] = $id;
                $update_res = $module->updateDiscountGoods($save, $m);
            }
            if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败')->toArray();
            }
        }
        $m->commit();
        foreach ($promotion_id_arr as $promotion_id) {
            $module->updatePromotionEffectStatus($promotion_id);//更新促销单生效状态
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
    }

    /**
     * 促销单-导入促销单商品
     * @param int $shop_id 门店id
     * @param int $data_type 促销方式(1:DM商品特价 2:单商品特价 3:买满数量后特价)
     * @param file $file
     * @return array
     * */
    public function importPromotionGoods(int $shop_id, int $data_type, array $file)
    {
        $response = LogicResponse::getInstance();
        $obj_reader = WSTReadExcel($file['file']['savepath'] . $file['file']['savename']);
        $obj_reader->setActiveSheetIndex(0);
        $sheet = $obj_reader->getActiveSheet();
        $rows = $sheet->getHighestRow();
        //$cells = $sheet->getHighestColumn();
        //数据集合
        //循环读取每个单元格的数据
        $goods_module = new GoodsModule();
        $data = array();
        for ($row = 3; $row <= $rows; $row++) {//行数是以第3行开始
            $is_sku = (string)trim($sheet->getCell("A" . $row)->getValue());
            $goods_sn = (string)trim($sheet->getCell("B" . $row)->getValue());
            if ($data_type == 1) {
                //DM商品特价
                $purchase_price = (float)trim($sheet->getCell("C" . $row)->getValue());
                $sale_price = (float)trim($sheet->getCell("D" . $row)->getValue());
                $limited_purchase = (int)trim($sheet->getCell("E" . $row)->getValue());
                $limited_single = (int)trim($sheet->getCell("F" . $row)->getValue());
                $remark = (string)trim($sheet->getCell("G" . $row)->getValue());
                if (empty($is_sku) || empty($goods_sn)) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请补全第{$row}行的字段信息")->toArray();
                }
            } elseif ($data_type == 2) {
                //单商品特价
                $special_price = (float)trim($sheet->getCell("C" . $row)->getValue());//特价
                $limited_purchase = (int)trim($sheet->getCell("D" . $row)->getValue());//全场限购
                $limited_single = (int)trim($sheet->getCell("E" . $row)->getValue());//单笔限购
                $score = (int)trim($sheet->getCell("F" . $row)->getValue());//特价积分
                $start_date = (int)trim($sheet->getCell("G" . $row)->getValue());//开始促销日期
                $n = intval(($start_date - 25569) * 3600 * 24); //转换成1970年以来的秒数, 时区相差8小时的
                $start_date = gmdate('Y-m-d', $n);
                $end_date = (int)trim($sheet->getCell("H" . $row)->getValue());//结束促销日期
                $n = intval(($end_date - 25569) * 3600 * 24); //转换成1970年以来的秒数, 时区相差8小时的
                $end_date = gmdate('Y-m-d', $n);
                if (empty($start_date) || empty($end_date)) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("第{$row}行请输入正确的开始和结束促销日期")->toArray();
                }
                if (strtotime($start_date) > strtotime($end_date)) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("第{$row}行开始促销日期不能大于结束促销日期")->toArray();
                }
                $start_time = trim($sheet->getCell("I" . $row)->getValue());//开始促销时间
                $start_time = date("H:i:s", strtotime('0:00:00') + 24 * 60 * 60 * $start_time);
                $end_time = (string)trim($sheet->getCell("J" . $row)->getValue());//结束促销时间
                $end_time = date("H:i:s", strtotime('0:00:00') + 24 * 60 * 60 * $end_time);
                $remark = (string)trim($sheet->getCell("K" . $row)->getValue());//备注
            } elseif ($data_type == 3) {
                //买满数量后特价
                $need_buy_num = (int)trim($sheet->getCell("C" . $row)->getValue());//每笔需购
                $special_price = (float)trim($sheet->getCell("D" . $row)->getValue());//特价
                $remark = (string)trim($sheet->getCell("E" . $row)->getValue());//备注
            }
            if ($is_sku == '否') {//是否为sku编码
                $is_sku = 0;
            } else {
                $is_sku = 1;
            }
            $info = array();
            $where = array(
                'shopId' => $shop_id,
                'goodsSn' => $goods_sn,
            );
            $goods_result = $goods_module->getGoodsInfoByParams($where);
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("第{$row}行的编码不正确")->toArray();
            }
            $goods_info = $goods_result['data'];
            $info['goods_id'] = (int)$goods_info['goodsId'];
            $info['sku_id'] = 0;
            $info['sku_spec_attr'] = '';
            $info['goods_sn'] = $goods_info['goodsSn'];
            $info['goods_name'] = $goods_info['goodsName'];
            $info['old_purchase_price'] = (float)$goods_info['goodsUnit'];
            $info['old_sale_price'] = (float)$goods_info['shopPrice'];
            if ($data_type == 1) {
                $info['purchase_price'] = (float)$purchase_price;
                $info['sale_price'] = $sale_price;
                $info['limited_purchase'] = $limited_purchase;
                $info['limited_single'] = $limited_single;
                $promotion_profit = bc_math($info['sale_price'], $info['purchase_price'], 'bcsub', 4);
                $info['promotion_profit'] = (bc_math($promotion_profit, $info['purchase_price'], 'bcdiv', 2) * 100) . '%';//（促销售价-促销进价）/促销进价
                $profit_rate = (float)bc_math($goods_info['shopPrice'], $goods_info['goodsUnit'], 'bcsub', 2);
                $info['profit_rate'] = ((float)bc_math($profit_rate, $goods_info['goodsUnit'], 'bcdiv', 2) * 100) . '%';//（原售价-原进价）/原进价
            } elseif ($data_type == 2) {
                //单商品特价
                $info['special_price'] = $special_price;
                $info['limited_purchase'] = $limited_purchase;
                $info['limited_single'] = $limited_single;
                $info['score'] = $score;
                $info['start_date'] = $start_date;
                $info['end_date'] = $end_date;
                $info['start_time'] = $start_time;
                $info['end_time'] = $end_time;
                $info['remark'] = $remark;

            } elseif ($data_type == 3) {
                //买满数量后折扣
                $info['need_buy_num'] = $need_buy_num;
                $info['special_price'] = $special_price;
                $info['remark'] = $remark;
            }
            $info['remark'] = $remark;//备注
            $info['shop_catid1_name'] = (string)$goods_info['shop_catid1_name'];//商品门店一级分类名称
            $info['shop_catid2_name'] = (string)$goods_info['shop_catid2_name'];//商品门店二级分类名称
            if ($is_sku == 1) {
                $where = array(
                    'goodsId' => $goods_info['goodsId'],
                    'skuBarcode' => $goods_sn,
                );
                $sku_result = $goods_module->getSkuSystemInfoByParams($where);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("第{$row}行的编码不正确")->toArray();
                }
                $sku_info = $sku_result['data'];
                $info['sku_id'] = $sku_info['skuId'];
                $info['goods_sn'] = $sku_info['skuBarcode'];
                $info['sku_spec_attr'] = $sku_info['skuSpecAttr'];
                if ($data_type == 1) {
                    //DM商品特价
                    $info['purchase_price'] = (float)$purchase_price;
                    $info['old_sale_price'] = (float)$sku_info['skuShopPrice'];
                    $info['sale_price'] = $sale_price;
                    $info['limited_purchase'] = $limited_purchase;
                    $info['limited_single'] = $limited_single;
                    $promotion_profit = bc_math($info['sale_price'], $info['purchase_price'], 'bcsub', 4);
                    $info['promotion_profit'] = (bc_math($promotion_profit, $info['purchase_price'], 'bcdiv', 2) * 100) . '%';//（促销售价-促销进价）/促销进价
                    $profit_rate = (float)bc_math($sku_info['skuShopPrice'], $goods_info['goodsUnit'], 'bcsub', 2);
                    $info['profit_rate'] = ((float)bc_math($profit_rate, $goods_info['goodsUnit'], 'bcdiv', 2) * 100) . '%';//（原售价-原进价）/原进价
                } elseif ($data_type == 2) {
                    //单商品特价
                    $info['old_sale_price'] = $sku_info['skuShopPrice'];
                } elseif ($data_type == 3) {
                    //买满数量后折扣
                    $info['old_sale_price'] = $sku_info['skuShopPrice'];
                }
            }
            $data[] = $info;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 促销-扫码搜索商品
     * @param int $shop_id 门店id
     * @param string $code 编码
     * */
    public function searchGoodsInfoByCode(int $shop_id, string $code)
    {
        $response = LogicResponse::getInstance();
        $goods_module = new GoodsModule();
        $result = $goods_module->searchGoodsInfoByCode($shop_id, $code);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未找到该编码商品')->toArray();
        }
        $data = $result['data'];
        if ($data['sku_id'] > 0) {
            $data['old_sale_price'] = $data['skuShopPrice'];
            $data['goods_sn'] = $data['skuBarcode'];
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }

}
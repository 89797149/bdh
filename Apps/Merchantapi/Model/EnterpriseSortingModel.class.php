<?php
/**
 * 桌面端分拣
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-18
 * Time: 14:31
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Modules\Barcode\BarcodeModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\PSD\LineModule;
use App\Modules\PSD\TaskModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Sorting\SortingModule;
use Think\Model;

class EnterpriseSortingModel extends BaseModel
{

    /**
     * 配置-获取门仓配置
     * @param int $shop_id 门仓id
     * @return array
     * */
    public function getShopConfig(int $shop_id)
    {
        $shop_module = new  ShopsModule();
        $field = 'shopId,sorting_threshold';
        $shop_config = $shop_module->getShopConfig($shop_id, $field, 2);
        return $shop_config;
    }

    /**
     * 登陆
     * @param string $account 账号
     * @param string $password 密码
     * @return array $result
     * */
    public function login(string $account, string $password)
    {
        $sorting_module = new SortingModule();
        $field = 'id,userName,mobile,state,account';
        $sorting_er = $sorting_module->getSortingPersonnelByAccount($account, $field);
        if (empty($sorting_er)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "账号或密码有误", "账号不存在");
        }
        $id = $sorting_er['id'];
        $is_true_password = $sorting_module->isTruePassword($id, $password);
        if (!$is_true_password) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "账号和密码不匹配", "密码错误");
        }
        $token = $sorting_module->toLogin($id);
        if (empty($token)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "登陆失败", "登陆失败");
        }
        $sorting_er['token'] = $token;
        return returnData($sorting_er, ExceptionCodeEnum::SUCCESS, 'success', '登陆成功');
    }

    /**
     * 分拣员-详情
     * @param int $id 分拣员id
     * @return array $result
     * */
    public function getSortingPersonnelDetial(int $id)
    {
        $soring_module = new SortingModule();
        $field = 'id,userName,mobile,state,account';
        $result = $soring_module->getSortingPersonnelById($id, $field);
        return $result;
    }

    /**
     * 分拣员-更新个人信息
     * @params array $params
     * -int id 分拣员id
     * -string userName 分拣员姓名
     * -string mobile 手机号
     * -int state 在线状态(1：在线 -1：不在线)
     * -string password 明文密码
     * @return bool $result
     * */
    public function updateSortingPersonnelDetial(array $params)
    {
        $result = (new SortingModule())->saveSortingPersonnelDetial($params);
        if (empty($result)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败");
        }
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', "修改成功");
    }

    /**
     * 配送端-线路列表
     * @param int $shop_id 门店id
     * @return array $result
     * */
    public function getDeliveryLineList(int $shop_id)
    {
        $psd_module = new LineModule();
        $params = array(
            'shop_id' => $shop_id
        );
        $result = $psd_module->getLineList($params);
        return $result;
    }

    /**
     * 按商品分拣-获取商品分类
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int goods_category 商品品类(1:标品 2:非标品 不传默认全部)
     * -int line_id 线路id,不传默认全部
     * @return array $result
     * */
    public function getSortingGoodsCatsToGoods(array $params)
    {
        $sorting_module = new SortingModule();
        $cat_list = $sorting_module->getSortingGoodsShopCatsByParams($params);
        return $cat_list;
    }

    /**
     * 按商品分拣-获取商品列表
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int goods_category 商品品类(1:标品 2:非标品 不传默认全部)
     * -int line_id 线路id,多个用英文逗号分隔,不传默认全部
     * -int cat_id 二级分类id
     * -string goods_name 搜索-商品名称
     * @return array
     * */
    public function getSortingGoodsToGoods(array $params)
    {
        $sorting_module = new SortingModule();
        $goods_list = $sorting_module->getSortingGoodsByParams($params);
        $return_goods_list = $sorting_module->mergeSortingGoodsToGoods($goods_list, $params);//按商品分拣-合并商品并处理分拣状态
        $sort_arr = array();
        foreach ($return_goods_list as $item) {
            $sort_arr[] = $item['sorting_status'];
        }
        array_multisort($sort_arr, SORT_ASC, $return_goods_list);
        return $return_goods_list;
    }

    /**
     * 按商品分拣-获取商品下的客户列表
     * @param array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int goods_id 商品id
     * -int sku_id 商品skuId
     * -string payment_username 搜索-客户名称
     * -int sort_order 排序(1:默认 2:客户名称 3:客户编码 4:线路)
     * @return array
     * */
    public function getSortingCustomerToGoods(array $params)
    {
        $sorting_module = new SortingModule();
        $goods_module = new GoodsModule();
        $sorting_order_goods = $sorting_module->getSortingGoodsByParams($params);
        $goods_id = $params['goods_id'];
        $sku_id = $params['sku_id'];
        $goods_field = 'goodsId,goodsName,goodsImg,goodsSn,unit,SuppPriceDiff,weightG';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $goods_field, 2);
        if (empty($goods_detail)) {
            return array();
        }
        $goods_detail['sku_spec_str'] = '';//商品sku属性拼接值
        if ($sku_id > 0) {
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (!empty($sku_detail)) {
                if ($sku_detail['goodsId'] != $goods_id) {
                    return array();
                }
                $goods_detail['goodsImg'] = $sku_detail['skuGoodsImg'];
                $goods_detail['goodsSn'] = $sku_detail['skuBarcode'];
                $goods_detail['sku_spec_str'] = $sku_detail['skuSpecAttr'];
                $goods_detail['weightG'] = $sku_detail['weigetG'];
                $goods_detail['unit'] = $sku_detail['unit'];
            }
        }
        $goods_detail['goods_sorting_total_weight'] = 0;//商品分拣总数量/重量,标品为数量单位,非标品默认单位斤
        $goods_detail['goods_sorting_ok_weight'] = 0;//商品已分拣总数量/重量,标品为数量单位,非标品默认单位斤
        $goods_detail['goods_sorting_no_weight'] = 0;//商品未分拣总数量/重量,标品为数量单位,非标品默认单位斤
        if (empty($sorting_order_goods)) {
            $goods_detail['customer_list'] = array();
            return $goods_detail;
        }
        $return_list = array();//客户列表
        foreach ($sorting_order_goods as $item) {
            $sorting_id = $item['sortingId'];
            $order_id = $item['orderId'];
            $user_id = $item['userId'];
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $return_detail = array(
                'id' => $item['id'],
                'orderId' => $order_id,
                'orderRemarks' => $item['orderRemarks'],
                'sortingId' => $item['sortingId'],
                'userId' => $user_id,//下单人-用户id
                'payment_username' => $item['payment_username'],//下单人-用户名称
                'sort' => $item['sort'],//线路排序
                'goods_remark' => $item['goods_remark'],//商品备注
            );
            $sorting_goods_detail = $sorting_module->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
            $return_detail['goodsNums'] = $sorting_goods_detail['goodsNum'];
            $return_detail['sorting_total_weight'] = $sorting_goods_detail['sorting_total_weight'];
            $return_detail['sorting_ok_weight'] = $sorting_goods_detail['sorting_ok_weight'];
            $return_detail['sorting_no_weight'] = $sorting_goods_detail['sorting_no_weight'];
            if ($item['status'] == 3) {
                $return_detail['sorting_no_weight'] = 0;
            }
            $return_detail['lack_stock_status'] = (int)$item['lack_stock_status'];//缺货状态(0:未缺货 1:部分缺货 2:全部缺货)
            $goods_detail['goods_sorting_total_weight'] += $return_detail['sorting_total_weight'];
            $goods_detail['goods_sorting_ok_weight'] += $return_detail['sorting_ok_weight'];
            $goods_detail['goods_sorting_no_weight'] += $return_detail['sorting_no_weight'];
            $goods_detail['goods_sorting_no_weight'] = $goods_detail['goods_sorting_no_weight'] < 0 ? 0 : $goods_detail['goods_sorting_no_weight'];
            $return_list[] = $return_detail;
        }
        //排序
        $sort_order = $params['sort_order'];
        $sort_arr = array();
        foreach ($return_list as $item) {
            if ($sort_order == 1) {//默认排序,已分拣数量正序
                $sort_arr[] = $item['sorting_ok_weight'];
            } elseif ($sort_order == 2) {//客户名称
                $sort_arr[] = $item['payment_username'];
            } elseif ($sort_order == 3) {//客户编码
                $sort_arr[] = $item['userId'];
            } elseif ($sort_order == 4) {//线路
                $sort_arr[] = $item['sort'];
            }
        }
        if ($sort_order == 2) {
            utf8_array_asort($sort_arr);
        }
        array_multisort($sort_arr, SORT_ASC, $return_list);
        $goods_detail['customer_list'] = $return_list;
        return $goods_detail;
    }

    /**
     * 按商品分拣-获取商品下的客户任务详情
     * @param int $id 分拣员id
     * @param int $sorting_relationid 分拣商品唯一标识id
     * @return array
     * */
    public function getSortingCustomerDetailToGoods(int $id, int $sorting_relationid)
    {
        $sorting_module = new SortingModule();
        $person_id = (int)$id;
        $relation_detail = $sorting_module->getSortingGoodsDetailById($sorting_relationid);
        if (empty($relation_detail)) {
            return array();
        }
        $sorting_id = (int)$relation_detail['sortingId'];
        $goods_id = (int)$relation_detail['goodsId'];
        $sku_id = (int)$relation_detail['skuId'];
        $result = $sorting_module->getSortingGoodsDetailByParams($person_id, $sorting_id, $goods_id, $sku_id);
        if (empty($result)) {
            return array();
        }
        $result['is_allow_reset'] = 2;//是否显示重置按钮(1:显示 2:不显示)
        $result['allow_lack_value'] = 1;//当前允许的缺货操作(1:不显示缺货按钮 2:标记缺货 3:部分缺货)
        $result['goods_sorting_status'] = 1;//商品分拣状态(1:未分拣 2:已分拣)
        $result['lack_stock_status'] = (int)$result['lack_stock_status'];//商品缺货状态(0:未缺货 1:部分缺货 2:全部缺货)
        if ($result['status'] > 0) {
            $result['goods_sorting_status'] = 2;
            $result['is_allow_reset'] = 1;
        }
        if (in_array($result['orderStatus'], array(17, 4))) {
            $result['is_allow_reset'] = 2;
        }
        if ($result['sorting_ok_weight'] == 0) {
            $result['allow_lack_value'] = 2;
        }
        if ($result['lack_stock_status'] > 0) {
            $result['is_allow_reset'] = 1;
        }
        if ($result['sorting_ok_weight'] > 0 && $result['sorting_ok_weight'] < $result['sorting_total_weight']) {
            $result['allow_lack_value'] = 3;
        }
        if (in_array($result['lack_stock_status'], array(1, 2))) {
            $result['allow_lack_value'] = 1;
        }
        $template = $GLOBALS['CONFIG']['sorting_print_template'];
//        $shop_module = new ShopsModule();
//        $shop_detail = $shop_module->getShopsInfoById($result['shopId'], 'shopName', 2);
        $goods_name = $result['goodsName'];
        $goods_sn = $result['goodsSn'];
        $unit_name = $result['unit'];
//        $shop_name = $shop_detail['shopName'];
        $shop_name = $result['payment_username'];
        $task_module = new TaskModule();
        $task_detail = $task_module->getTaskDetailById($result['taskId'], 'lineId');
        $line_module = new LineModule();
        $line_detail = $line_module->getLineDetailById($task_detail['lineId']);
        $line_name = $line_detail['lineName'];
        $goods_num_weight = $sorting_module->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
        $num_or_weight = $goods_num_weight['sorting_ok_weight'];
        $delivety_date = date('Y-m-d', strtotime($result['requireTime']));
        $html = $template;
        $html = str_replace("{shop_name}", $shop_name, $html);
        $html = str_replace("{line_name}", $line_name, $html);
        $html = str_replace("{goods_name}", $goods_name, $html);
        $html = str_replace("{num_or_weight}", $num_or_weight, $html);
        $html = str_replace("{unit_name}", $unit_name, $html);
        $svg_url = (new BarcodeModule())->createBarcodeImg($goods_sn);
        $html = str_replace('src="{img_url}"', "src='{img_url}'", $html);
        $html = str_replace("{img_url}", $svg_url, $html);
        $html = str_replace("{goods_sn}", $goods_sn, $html);
        $html = str_replace("{delivery_date}", $delivety_date, $html);
        $prin_info = array(
            'html' => $html,
            'goods_sn' => $goods_sn
        );
        $result['print_data'] = $prin_info;
        return $result;
    }

    /**
     * 分拣商品-执行分拣
     * @param int $id 分拣员id
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @param int $num_or_weight 分拣数量或重量
     * @return bool
     * */
    public function actionSortingGoods(int $id, int $sorting_goods_relationid, float $num_or_weight)
    {
        //PS:逻辑变更,废除单位换算
        //分拣数量和重量不做太严苛的校验,只要不小于0即可,仿蔬东坡
        //一次分拣,多次分拣,定值分拣,这里只做一次分拣
        $sorting_module = new SortingModule();
        $orders_module = new OrdersModule();
        $sorting_goods_detail = $sorting_module->getSortingGoodsDetailById($sorting_goods_relationid);
//        $sorting_goods_detail = $sorting_module->getSortingGoodsDetailByParams($id, $sorting_id, $goods_id, $sku_id);
        if (empty($sorting_goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "分拣商品信息有误");
        }
        if ($sorting_goods_detail['status'] > 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请勿重复分拣");
        }
        $sorting_id = $sorting_goods_detail['sortingId'];
        $goods_id = $sorting_goods_detail['goodsId'];
        $sku_id = $sorting_goods_detail['skuId'];
        $person_detail = $sorting_module->getSortingPersonnelById($id, 'username');
        $person_name = $person_detail['username'];
        $order_id = $sorting_goods_detail['orderId'];
        $user_id = $sorting_goods_detail['userId'];
        $shop_id = $sorting_goods_detail['shopId'];
        $is_weight = $sorting_goods_detail['SuppPriceDiff'];
        if ($is_weight == -1) {
            if (count(explode('.', $num_or_weight)) > 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "标品分拣数量必须为整数");
            }
        }
        $trans = new Model();
        $trans->startTrans();
        $datetime = date('Y-m-d H:i:s');
        $lack_stock_status = $sorting_goods_detail['lack_stock_status'];//缺货状态(0:未缺货 1:部分缺货 2:全部缺货) 注:目前仅用于桌面端分拣
        if ($lack_stock_status == 2) {//清除之前全部缺货的差价记录和售后/补款记录,因为标记了全部缺货还是可分拣的
            $reset_relation_res = $sorting_module->resetSortingGoodsRelation($sorting_goods_relationid, $trans);
            if ($reset_relation_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '失败，清除之前缺货的差价记录和售后/补款记录');
            }
        }
        $inc_goods_res = $sorting_module->incSortingGoodsNum($sorting_goods_relationid, $num_or_weight, $trans);//递增商品分拣数量/重量
        if (!$inc_goods_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "分拣失败", "分拣商品数量或重量递增失败");
        }
        //后加-更新订单商品表的状态-start
        $order_goods_where = array(
            'orderId' => $sorting_goods_detail['orderId'],
            'goodsId' => $sorting_goods_detail['goodsId'],
            'skuId' => $sorting_goods_detail['skuId'],
        );
        $order_goods_detail = $orders_module->getOrderGoodsInfoByParams($order_goods_where, 'id,goodsId,skuId', 2);
        if (empty($order_goods_detail)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "分拣失败", "订单商品信息有误");
        }
        $save_og_params = array(
            'id' => $order_goods_detail['id'],
            'actionStatus' => 2,
            'sortingNum' => $num_or_weight,
        );
//        if ($is_weight == 1) {
//            $save_og_params['sortingNum'] = $num_or_weight * 500;//斤转g
//        }
        $save_og_res = $orders_module->saveOrderGoods($save_og_params, $trans);//更新订单商品的分拣状态
        if (empty($save_og_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "分拣失败", "更新订单商品的分拣状态失败");
        }
        //后加-更新订单商品表的状态-end

        $buy_goods_num = $sorting_goods_detail['sorting_total_weight'];//,购买时的数量/重量(斤)
        $dec_stock = 0;//需要返还或扣除的商品库存
        if ($num_or_weight > $buy_goods_num) {
            $dec_stock = ($num_or_weight - $buy_goods_num);
        }
        if ($num_or_weight < $buy_goods_num) {
            $dec_stock = ($num_or_weight - $buy_goods_num);
        }
//        if ($sorting_goods_detail['SuppPriceDiff'] == 1) {//非标品
//            $dec_stock = $dec_stock / 2;//非标品重量斤转kg
//            $num_or_weight = $num_or_weight / 2;
//        }
        $edit_goods_stock_res = array(
            'code' => ExceptionCodeEnum::SUCCESS
        );
        $goods_module = new GoodsModule();
        //扣除库房库存,不必处理售卖库存
        if ($dec_stock >= 0) {
            if ($dec_stock > 0) {//多分拣的商品金额累积到用户已欠款额度
                $inc_quota_arrears_res = $sorting_module->incUserQuotaArrears($order_id, $goods_id, $sku_id, $dec_stock, $trans);
                if (!$inc_quota_arrears_res) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败，用户欠款额度处理异常', "用户欠款额度处理异常");
                }
            }
            $dec_stock = $num_or_weight;//逻辑变更了,以前端传值为准
            $edit_goods_stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $dec_stock, 1, 1, $trans);//如果分拣的商品数量/重量大于购买时,则扣除商品的库存
            if ($edit_goods_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败，商品库房库存不足', "商品库房库存不足");
            }
        }
        $log_order_module = new LogOrderModule();
        if ($dec_stock < 0) {//如果分拣的商品数量/重量小于购买时,则返还商品的库存

            $edit_goods_stock_res = $goods_module->deductionGoodsStock($goods_id, $sku_id, $num_or_weight, 1, 1, $trans);//扣除库房库存
            $abs_dec_stock = abs($dec_stock);
//            $edit_goods_stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $abs_dec_stock, 1, 2, $trans);
            //补差价记录(attention: 1:分拣数量/重量不足 2:分拣数量/重量为0)
            $tag_buy_goods_weight = $buy_goods_num;
            if ($sorting_goods_detail['SuppPriceDiff'] == 1) {
//                $tag_buy_goods_weight = $buy_goods_num / 2;//购买的重量(斤)转kg
                $tag_buy_goods_weight = $buy_goods_num;//购买的重量(斤)转kg
            }
            $order_pay_from = $sorting_goods_detail['payFrom'];
            if ($abs_dec_stock <= $tag_buy_goods_weight && in_array($order_pay_from, array(1, 2, 3))) {//分拣数量/重量不足,补差价记录
                $order_goods_params = array(
                    'orderId' => $order_id,
                    'goodsId' => $goods_id,
                    'skuId' => $sku_id,
                );
                $order_goods_detail = $orders_module->getOrderGoodsInfoByParams($order_goods_params, 'id,goodsNums,goodsName,goodsPrice', 2);
                if (empty($order_goods_detail)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '订单商品有误');
                }
                $diff_price_params = array(
                    'orderId' => $order_id,
                    'goodsId' => $goods_id,
                    'skuId' => $sku_id,
                    'money' => 0,
                    'weightG' => 0,
                    'userId' => $user_id,
                    'isPay' => 0,
                    'goosNum' => $order_goods_detail['goodsNums'],
                    'unitPrice' => $order_goods_detail['goodsPrice'],
                );
                $num_or_g_price = $orders_module->getOrderGoodsPriceNumG($order_id, $goods_id, $sku_id);//元/数量,g
                if ($sorting_goods_detail['SuppPriceDiff'] == 1) {
//                    $diff_price_params['weightG'] = $abs_dec_stock * 1000;
                    $diff_price_params['weightG'] = $abs_dec_stock;
                    $diff_price_params['money'] = sprintfNumber($num_or_g_price * $diff_price_params['weightG']);
                } else {
                    $diff_price_params['money'] = sprintfNumber((float)$abs_dec_stock * $num_or_g_price);
                }
                if ($abs_dec_stock == $tag_buy_goods_weight) {
                    $diff_price_params['money'] = $order_goods_detail['goodsPrice'] * $order_goods_detail['goodsNums'];
                }
                $save_price_diff_res = $orders_module->saveOrdersGoodsPriceDiff($diff_price_params, $trans);
                if (empty($save_price_diff_res)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "补差价记录失败");
                }
                $content = $order_goods_detail['goodsName'] . '#补差价：' . $diff_price_params['money'] . '元。确认收货后返款！';
                $log_params = array(
                    'orderId' => $order_id,
                    'logContent' => $content,
                    'logUserId' => $id,
                    'logUserName' => '分拣员#' . $person_name,
                    'orderStatus' => 16,
                    'payStatus' => 1,
                    'logType' => 1,
                    'logTime' => date('Y-m-d H:i:s'),
                );
                $log_res = $log_order_module->addLogOrders($log_params, $trans);
                if (!$log_res) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "订单日志记录失败");
                }
            }
            if ($abs_dec_stock == $tag_buy_goods_weight && in_array($order_pay_from, array(1, 2, 3))) {//分拣数量/重量为0,写入售后记录,防止用户端再次申请售后
                //售后记录
                $complains_datetime = date('Y-m-d H:i:s');
                $complains_params = array();
                $complains_params['orderId'] = $order_id;
                $complains_params['complainType'] = 5;//5：其他
                $complains_params['deliverRespondTime'] = date('Y-m-d H:i:s');
                $complains_params['complainContent'] = "商品缺货";
                $complains_params['complainAnnex'] = " ";
                $complains_params['goodsId'] = $goods_id;
                $complains_params['skuId'] = $sku_id;
                $complains_params['complainTargetId'] = $user_id;
                $complains_params['respondTargetId'] = $shop_id;
                $complains_params['returnFreight'] = -1;//商家是否退运费【-1：不退运费|1：退运费】
                $complains_params['returnAmountStatus'] = 1;//退款状态【-1：已拒绝|0：待处理|1：已处理】
                $complains_params['needRespond'] = 1;//系统自动递交给店家
                $complains_params['complainStatus'] = 2;//投诉/退货状态【-1：已拒绝|0：待处理|1：退货中|2：已完成】
                $complains_params['returnAmount'] = 0;//实际退款金额 统计重复需要修改
                $complains_params['createTime'] = $complains_datetime;
                $complains_params['updateTime'] = $complains_datetime;
                $complains_params['respondTime'] = $complains_datetime;//应诉时间
                $complain_id = $orders_module->saveOrderGoodsComplains($complains_params, $trans);
                if (empty($complain_id)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后记录写入失败');
                }
                //写入售后退款记录表，并标注已退款
                $record_params = array();
                $record_params['orderId'] = $order_id;
                $record_params['goodsId'] = $goods_id;
                $record_params['money'] = 0;
                $record_params['addTime'] = $complains_datetime;
                $record_params['payType'] = 1;
                $record_params['userId'] = $user_id;
                $record_params['skuId'] = $sku_id;
                $record_params['complainId'] = (int)$complain_id;
                $save_rcord_res = $orders_module->saveOrderGoodsComplainsrecord($record_params, $trans);
                if (empty($save_rcord_res)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后退款记录写入失败');
                }
                //写入售后状态变动记录表
                $action_log_params = [
                    'tableName' => 'wst_order_complains',
                    'dataId' => $complain_id,
                    'actionUserId' => $id,
                    'actionUserName' => $person_name,
                    'fieldName' => 'complainStatus',
                    'fieldValue' => 2,
                    'remark' => "分拣员：{$person_name}记录,当前商品缺货,全部补差价",
                    'createTime' => date('Y-m-d H:i:s'),
                ];
                $action_log_res = (new TableActionLogModule())->addTableActionLog($action_log_params, $trans);
                if ($action_log_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后状态日志记录失败');
                }
            }
        }
        if ($edit_goods_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败，商品库房库存不足', "商品库房库存不足");
        }
        $sort_g_data = array(
            'id' => $sorting_goods_detail['id'],
            'startDate' => $datetime,
            'endDate' => $datetime,
//            'status' => 2,//目前的逻辑是只能分拣一次,所以只要一分拣即完成
            'status' => 3,//直接已完成
            'lack_stock_status' => 0,//只要已分拣就重置缺货状态为未缺货 注:蔬东坡是按商品分拣不会重置该状态,但是按客户分拣会重置该状态
        );
        $update_res = $sorting_module->saveSortingGoods($sort_g_data, $trans);
        if (empty($update_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "分拣失败", "更新分拣商品信息失败");
        }
        //更新订单商品表中的分拣任务状态
        $update_sorting_status_res = $sorting_module->autoUpdateSortingStatus($sorting_id, 2, $trans);//更新分拣任务的状态
        if (!$update_sorting_status_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "分拣任务状态更新失败");
        }
        //更新订单的应收等价格
        $updateOrderPriceRes = $orders_module->autoUpdateOrderPrice($order_id, $trans);
        if (!$updateOrderPriceRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "订单价格更新失败");
        }
        $trans->commit();
        //增加打印相关
        $sorting_goods_detail_n = $this->getSortingCustomerDetailToGoods($id, $sorting_goods_relationid);
        $result = array();
        $result['print_data'] = $sorting_goods_detail_n['print_data'];
        return returnData($result);
    }

    /**
     * 分拣商品-标记缺货/部分缺货
     * @param int $id 分拣员id
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @param int $lack_stock_status 缺货状态(1:部分缺货 2:全部缺货)
     * @return array
     * */
    public function lackSortingGoods(int $id, int $sorting_goods_relationid, int $lack_stock_status)
    {
        $sorting_module = new SortingModule();
        $sorting_goods_detail = $sorting_module->getSortingGoodsDetailById($sorting_goods_relationid);
        if (empty($sorting_goods_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据有误');
        }
        if ($sorting_goods_detail['lack_stock_status'] > 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请勿重复标记缺货');
        }
        $sorting_total_weight = $sorting_goods_detail['sorting_total_weight'];//购买时的数量/重量(斤)
        $sorting_ok_weight = $sorting_goods_detail['sorting_ok_weight'];//已分拣的数量/重量(斤)
        $sorting_no_weight = $sorting_goods_detail['sorting_no_weight'];//未分拣的数量/重量(斤)
        if ($sorting_ok_weight >= $sorting_total_weight) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前商品无需缺货处理');
        }
        if ($lack_stock_status == 1 && $sorting_ok_weight <= 0) {//应该是全部缺货
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前商品应该标记全部缺货');
        }
        if ($lack_stock_status == 2 && $sorting_ok_weight > 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前商品应该标记部分缺货');
        }
        $person_detail = $sorting_module->getSortingPersonnelById($id, 'username');
        $person_name = $person_detail['username'];
        $trans = new Model();
        $trans->startTrans();
        $save_params = array(
            'id' => $sorting_goods_relationid,
            'lack_stock_status' => $lack_stock_status,
        );
        $save_res = $sorting_module->saveSortingGoods($save_params, $trans);
        if (empty($save_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '分拣商品状态修改失败');
        }
//        $goods_module = new GoodsModule();
        $log_order_module = new LogOrderModule();
        $orders_module = new OrdersModule();
        $order_id = $sorting_goods_detail['orderId'];
        $goods_id = $sorting_goods_detail['goodsId'];
        $sku_id = $sorting_goods_detail['skuId'];
        $user_id = $sorting_goods_detail['userId'];
//        $is_weight = $sorting_goods_detail['SuppPriceDiff'];
        $shop_id = $sorting_goods_detail['shopId'];
//        不需要处理售卖库存
//        $return_stock = $sorting_no_weight;
//        if ($sorting_no_weight == 0) {
//            $trans->rollback();
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '分拣商品状态修改失败');
//        }
//        if ($is_weight == 1) {//非标品斤转kg
//            $return_stock = $return_stock / 2;
//        }
//        if ($lack_stock_status == 2 && $sorting_goods_detail['status'] > 0) {
//            $return_goods_stock = $goods_module->returnGoodsStock($goods_id, $sku_id, $return_stock, 1, 2, $trans);
//            if ($return_goods_stock['code'] != ExceptionCodeEnum::SUCCESS) {
//                $trans->rollback();
//                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '商品库存返还失败');
//            }
//        }
        $order_pay_from = $sorting_goods_detail['payFrom'];
        if (in_array($order_pay_from, array(1, 2, 3))) {//分拣数量/重量不足,补差价记录
            $order_goods_params = array(
                'orderId' => $order_id,
                'goodsId' => $goods_id,
                'skuId' => $sku_id,
            );
            $order_goods_detail = $orders_module->getOrderGoodsInfoByParams($order_goods_params, 'id,goodsNums,goodsName,goodsPrice', 2);
            if (empty($order_goods_detail)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '订单商品有误');
            }
            if ($lack_stock_status == 2) {//全部缺货,部分缺货在分拣的时候就已经处理了
                $reset_relation_res = $sorting_module->resetSortingGoodsRelation($sorting_goods_relationid, $trans);
                if ($reset_relation_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '失败，清除之前缺货的差价记录和售后/补款记录');
                }
                $diff_price_params = array(
                    'orderId' => $order_id,
                    'goodsId' => $goods_id,
                    'skuId' => $sku_id,
                    'money' => $order_goods_detail['goodsPrice'] * $order_goods_detail['goodsNums'],
                    'weightG' => 0,
                    'userId' => $user_id,
                    'isPay' => 0,
                    'goosNum' => $order_goods_detail['goodsNums'],
                    'unitPrice' => $order_goods_detail['goodsPrice'],
                );
//                $num_or_g_price = $orders_module->getOrderGoodsPriceNumG($order_id, $goods_id, $sku_id);//元/数量,g
//                if ($is_weight == 1) {//非标品
//                    $diff_price_params['weightG'] = $sorting_no_weight * 500;
//                    $diff_price_params['money'] = sprintfNumber($num_or_g_price * $diff_price_params['weightG']);
//                } else {
//                    $diff_price_params['money'] = sprintfNumber((float)$sorting_no_weight * $num_or_g_price);
//                }
                $save_price_diff_res = $orders_module->saveOrdersGoodsPriceDiff($diff_price_params, $trans);
                if (empty($save_price_diff_res)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "补差价记录失败");
                }
                $content = $order_goods_detail['goodsName'] . '#补差价：' . $diff_price_params['money'] . '元。确认收货后返款！';
                $log_params = array(
                    'orderId' => $order_id,
                    'logContent' => $content,
                    'logUserId' => $id,
                    'logUserName' => '分拣员#' . $person_name,
                    'orderStatus' => 16,
                    'payStatus' => 1,
                    'logType' => 1,
                    'logTime' => date('Y-m-d H:i:s'),
                );
                $log_res = $log_order_module->addLogOrders($log_params, $trans);
                if (!$log_res) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', "订单日志记录失败");
                }
            }
        }
        if ($sorting_ok_weight == 0) {//分拣数量/重量为0,写入售后记录,防止用户端再次申请售后
//售后记录
            $complains_datetime = date('Y-m-d H:i:s');
            $complains_params = array();
            $complains_params['orderId'] = $order_id;
            $complains_params['complainType'] = 5;//5：其他
            $complains_params['deliverRespondTime'] = date('Y-m-d H:i:s');
            $complains_params['complainContent'] = "商品缺货";
            $complains_params['complainAnnex'] = " ";
            $complains_params['goodsId'] = $goods_id;
            $complains_params['skuId'] = $sku_id;
            $complains_params['complainTargetId'] = $user_id;
            $complains_params['respondTargetId'] = $shop_id;
            $complains_params['returnFreight'] = -1;//商家是否退运费【-1：不退运费|1：退运费】
            $complains_params['returnAmountStatus'] = 1;//退款状态【-1：已拒绝|0：待处理|1：已处理】
            $complains_params['needRespond'] = 1;//系统自动递交给店家
            $complains_params['complainStatus'] = 2;//投诉/退货状态【-1：已拒绝|0：待处理|1：退货中|2：已完成】
            $complains_params['returnAmount'] = 0;//实际退款金额 统计重复需要修改
            $complains_params['createTime'] = $complains_datetime;
            $complains_params['updateTime'] = $complains_datetime;
            $complains_params['respondTime'] = $complains_datetime;//应诉时间
            $complain_id = $orders_module->saveOrderGoodsComplains($complains_params, $trans);
            if (empty($complain_id)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后记录写入失败');
            }
            //写入售后退款记录表，并标注已退款
            $record_params = array();
            $record_params['orderId'] = $order_id;
            $record_params['goodsId'] = $goods_id;
            $record_params['money'] = 0;
            $record_params['addTime'] = $complains_datetime;
            $record_params['payType'] = 1;
            $record_params['userId'] = $user_id;
            $record_params['skuId'] = $sku_id;
            $record_params['complainId'] = (int)$complain_id;
            $save_rcord_res = $orders_module->saveOrderGoodsComplainsrecord($record_params, $trans);
            if (empty($save_rcord_res)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后退款记录写入失败');
            }
            //写入售后状态变动记录表
            $action_log_params = [
                'tableName' => 'wst_order_complains',
                'dataId' => $complain_id,
                'actionUserId' => $id,
                'actionUserName' => $person_name,
                'fieldName' => 'complainStatus',
                'fieldValue' => 2,
                'remark' => "分拣员：{$person_name}记录,当前商品缺货,全部补差价",
                'createTime' => date('Y-m-d H:i:s'),
            ];
            $action_log_res = (new TableActionLogModule())->addTableActionLog($action_log_params, $trans);
            if ($action_log_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣失败', '售后状态日志记录失败');
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 分拣商品-重置
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @return array
     * */
    public function resetSortingGoods(int $sorting_goods_relationid)
    {
        $sorting_module = new SortingModule();
        $detail = $sorting_module->getSortingGoodsDetailById($sorting_goods_relationid);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分拣的商品信息有误');
        }
        if (in_array($detail['orderStatus'], array(3, 4, 17))) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前状态不允许重置');
        }
//        $is_weight = $detail['SuppPriceDiff'];//1:称重商品
        $task_id = $detail['taskId'];
        $task_module = new TaskModule();
        if ($task_module->isReceived($task_id)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '司机已接单不允许重置');
        }
        if ($detail['status'] == 0 && $detail['lack_stock_status'] == 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前分拣商品无需重置');
        }
        $order_module = new OrdersModule();
        $order_id = $detail['orderId'];
        $goods_id = $detail['goodsId'];
        $sku_id = $detail['skuId'];
        $sorting_id = $detail['sortingId'];
        $trans = new Model();
        $trans->startTrans();
        //重置:任务状态,分拣商品状态,补差价,售后申请记录,售后退款记录,订单操作日记
        $reset_goods_res = $sorting_module->resetSortingGoods($sorting_goods_relationid, $trans);
        if (!$reset_goods_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '重置分拣商品信息失败');
        }
        $goods_complains_detail = $order_module->getOrderGoodsComplainsDetailByParams($order_id, $goods_id, $sku_id, 'complainId');//获取商品的售后申请记录
        if ($detail['sorting_ok_weight'] <= $detail['sorting_total_weight']) {
            $reset_diff_price_res = $order_module->delOrderGoodsPriceDiff($order_id, $goods_id, $trans);//删除补差价记录
            if (!$reset_diff_price_res) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '商品补差价信息重置失败');
            }
            if (!empty($goods_complains_detail)) {
                $reset_complains = $order_module->delOrderGoodsComplains($order_id, $goods_id, $sku_id, $trans);
                if (!$reset_complains) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '商品售后申请信息重置失败');
                }
                $reset_complainsrecord = $order_module->delOrderGoodsComplainsrecord($order_id, $goods_id, $sku_id, $trans);
                if (!$reset_complainsrecord) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '商品售后退款信息重置失败');
                }
            }
        }
        //扣/返库存
        if ($detail['sorting_ok_weight'] > 0) {
            $goods_module = new GoodsModule();
            if ($detail['sorting_ok_weight'] >= $detail['sorting_total_weight']) {
                $overflow_stock = $detail['sorting_ok_weight'] - $detail['sorting_total_weight'];
                if ($overflow_stock > 0) {//多分拣的商品金额累积到用户已欠款额度
                    $dec_quota_arrears_res = $sorting_module->decUserQuotaArrears($order_id, $goods_id, $sku_id, $overflow_stock, $trans);
                    if (!$dec_quota_arrears_res) {
                        $trans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败，用户欠款额度处理异常', "用户欠款额度处理异常");
                    }
                }
//                if ($is_weight == 1) {
//                    $return_stock = $return_stock / 2;
//                }
                $return_stock = $detail['sorting_ok_weight'];
                $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $return_stock, 1, 1, $trans);
            }
            if ($detail['sorting_ok_weight'] < $detail['sorting_total_weight']) {
//                $dec_stock = $detail['sorting_total_weight'] - $detail['sorting_ok_weight'];
                $dec_stock = $detail['sorting_ok_weight'];
//                if ($is_weight == 1) {
//                    $dec_stock = $dec_stock / 2;
//                }
                $stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $dec_stock, 1, 1, $trans);
            }
            if ($stock_res['code'] == ExceptionCodeEnum::FAIL) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '商品库存处理失败');
            }
        }
        if ($detail['lack_stock_status'] == 2 && !empty($goods_complains_detail)) {
            $complain_id = $goods_complains_detail['complainId'];
            $action_log_res = (new TableActionLogModule())->delTableActionLog('wst_order_complains', $complain_id, $trans);
            if ($action_log_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '售后状态日志记录删除失败');
            }
        }
        $reset_sorting_res = $sorting_module->autoUpdateSortingStatus($sorting_id, 2, $trans);
        if (!$reset_sorting_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '重置失败', '分拣任务信息重置失败');
        }
        $trans->commit();
        $log_params = array(
            'orderId' => $order_id,
            'orderStatus' => 16,
        );
        (new LogOrderModule())->delLogOrdersByParams($log_params);//重置订单日志,代码位置勿动
        return returnData(true);
    }

    /**
     * 分拣-一键分拣/一键打印-获取分拣商品数量
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int [sorting_status] 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int [goods_category] 商品品类(1:标品 2:非标品 不传默认全部)
     * -int [line_id] 线路id,多个用英文逗号分隔,不传默认全部
     * -int [cat_id] 二级分类id,不传默认全部
     * -string [goods_name] 搜索-商品名称
     * -int [goods_id] 商品id
     * @return array
     * */
    public function getSortingGoodsCount(array $params)
    {
        $template = $GLOBALS['CONFIG']['sorting_print_template'];
        $template_arr = explode('<style>', $template);
        $template_start = $template_arr[0];
        $template_end = "<style>" . $template_arr[1];
        $sorting_module = new SortingModule();
        $sorting_goods_list = $sorting_module->getSortingGoodsByParams($params);
        $print_list = array();//需要打印的商品信息
        $print_merge_str = '';
        $sorting_goods_count = count($sorting_goods_list);
        foreach ($sorting_goods_list as $key => $item) {
            if ($params['sorting_status'] == 1) {
                if ($item['status'] > 0) {
                    unset($sorting_goods_list[$key]);
                    continue;
                }
            } elseif ($params['sorting_status'] == 2) {
                if ($item['status'] == 0) {
                    unset($sorting_goods_list[$key]);
                    continue;
                }
            }
            if (empty($template)) {
                continue;
            }
            $sorting_id = $item['sortingId'];
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $goods_name = $item['goodsName'];
            $goods_sn = $item['goodsSn'];
            $unit_name = $item['unit'];
//            $shop_name = $item['shopName'];
            $shop_name = $item['payment_username'];
            $line_name = $item['lineName'];
            $goods_num_weight = $sorting_module->getSortingGoodsWeight($sorting_id, $goods_id, $sku_id);
            $num_or_weight = $goods_num_weight['sorting_total_weight'];
            if ($params['from_type'] != 2) {
                $num_or_weight = $goods_num_weight['sorting_ok_weight'];
            }
            $delivety_date = date('Y-m-d', strtotime($item['requireTime']));
            $html = $template;
            $html = str_replace("{shop_name}", $shop_name, $html);
            $html = str_replace("{line_name}", $line_name, $html);
            $html = str_replace("{goods_name}", $goods_name, $html);
            $html = str_replace("{num_or_weight}", $num_or_weight, $html);
            $html = str_replace("{unit_name}", $unit_name, $html);
            $html = str_replace("{delivery_date}", $delivety_date, $html);
            $svg_url = (new BarcodeModule())->createBarcodeImg($goods_sn);
            $html = str_replace('src="{img_url}"', "src='{img_url}'", $html);
            $html = str_replace("{img_url}", $svg_url, $html);
            $html = str_replace("{goods_sn}", $goods_sn, $html);

            $html_str = $template_start;
            $html_str = str_replace("{shop_name}", $shop_name, $html_str);
            $html_str = str_replace("{line_name}", $line_name, $html_str);
            $html_str = str_replace("{goods_name}", $goods_name, $html_str);
            $html_str = str_replace("{num_or_weight}", $num_or_weight, $html_str);
            $html_str = str_replace("{unit_name}", $unit_name, $html_str);
            $html_str = str_replace("{delivery_date}", $delivety_date, $html_str);
            $svg_url = (new BarcodeModule())->createBarcodeImg($goods_sn);
            $html_str = str_replace("{img_url}", $svg_url, $html_str);
            $html_str = str_replace("{goods_sn}", $goods_sn, $html_str);
            $html_str = str_replace('src="{img_url}"', "src='{img_url}'", $html_str);
            if ($key + 1 == $sorting_goods_count) {
                $html_str = str_replace('<div class="pageT"></div>', '', $html_str);
            }
            $print_merge_str .= $html_str;
            $print_info = array(
                'html' => $html,
                'goods_sn' => $goods_sn
            );
            $print_list[] = $print_info;
        }
        $print_merge_str .= $template_end;//前端不要数组,要合并后的字符串
        $result = array(
            'count' => count($sorting_goods_list),
            'print_goods_list' => $print_list,
            'print_merge_str' => $print_merge_str
        );
        return returnData($result);
    }

    /**
     * 分拣-一键分拣-确认分拣
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int [goods_category] 商品品类(1:标品 2:非标品 不传默认全部)
     * -int [line_id] 线路id,多个用英文逗号分隔,不传默认全部
     * -int [cat_id] 二级分类id,不传默认全部
     * -string [goods_name] 搜索-商品名称
     * -int [goods_id] 商品id
     * @return bool
     * */
    public function oneKeySortingGoods(array $params)
    {
        $person_id = $params['id'];
        $sorting_module = new SortingModule();
        $sorting_goods_list = $sorting_module->getSortingGoodsByParams($params);
        foreach ($sorting_goods_list as $key => $item) {
            if ($item['status'] > 0) {
                unset($sorting_goods_list[$key]);
            }
        }
        if (empty($sorting_goods_list)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无符合条件的数据');
        }
        foreach ($sorting_goods_list as $item) {
            $sorting_goods_relationid = $item['id'];
            $detail = $sorting_module->getSortingGoodsDetailById($sorting_goods_relationid);
            $num_or_weight = $detail['sorting_no_weight'];
            $action_res = $this->actionSortingGoods($person_id, $sorting_goods_relationid, $num_or_weight);
            if ($action_res['code'] != ExceptionCodeEnum::SUCCESS) {
                continue;
            }
            if ($detail['lack_stock_status'] > 0) {
                $sorting_goods = array(
                    'id' => $sorting_goods_relationid,
                    'lack_stock_status' => 0,
                );
                $sorting_module->saveSortingGoods($sorting_goods);
            }
        }
        return returnData(true);
    }

    /**
     * 废弃
     * 分拣-打印/批量打印
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int [sorting_status] 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int [goods_category] 商品品类(1:标品 2:非标品 不传默认全部)
     * -int [line_id] 线路id,多个用英文逗号分隔,不传默认全部
     * -int [cat_id] 二级分类id,不传默认全部
     * -string [goods_name] 搜索-商品名称
     * -int [goods_id] 商品id
     * @return array
     * */
    public function oneKeyPrintSortingGoods(array $params)
    {
        $sorting_module = new SortingModule();
        $sorting_goods_list = $sorting_module->getSortingGoodsByParams($params);
        foreach ($sorting_goods_list as $key => $item) {
            if ($params['sorting_status'] == 1) {
                if ($item['status'] > 0) {
                    unset($sorting_goods_list[$key]);
                    continue;
                }
            } elseif ($params['sorting_status'] == 2) {
                if ($item['status'] == 0) {
                    unset($sorting_goods_list[$key]);
                    continue;
                }
            }
            //TODO:执行打印操作,待修改
        }
        $result = returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '暂未对接打印机,前端可以按文档参数先对接上');
        return $result;

    }

    /**
     * 废弃
     * 分拣-单个商品打印
     * @param int $id 分拣员id
     * @param int $sorting_goods_relationid 分拣商品唯一标识id
     * @return array
     * */
    public function oneGoodsPrintSortingGoods(int $id, int $sorting_goods_relationid)
    {
        //TODO:执行打印操作,待修改
        $result = returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '暂未对接打印机,前端可以按文档参数先对接上');
        return $result;
    }

    /**
     * 按客户分拣-获取客户订单列表
     * @params array $params
     * -int id 分拣员id
     * -date delivery_time 发货日期
     * -int sorting_status 分拣状态(1:未分拣 2:已分拣 不传默认全部)
     * -int goods_category 商品品类(1:标品 2:非标品 不传默认全部)
     * -int line_id 线路id,多个用英文逗号分隔,不传默认全部
     * -string payment_username 搜索-客户名称
     * @return array
     * */
    public function getCustomerToCustomer(array $params)
    {
        $sorting_module = new SortingModule();
        $goods_list = $sorting_module->getSortingGoodsByParams($params);
        $return_list = $sorting_module->mergeSortingOrdersToCustomer($goods_list, $params);//按客户分拣-合并客户订单
        $sort_arr = array();
        foreach ($return_list as $item) {
            $sort_arr[] = $item['sorting_status'];
        }
        array_multisort($sort_arr, SORT_ASC, $return_list);
        return $return_list;
    }

    /**
     * 按客户分拣-获取客户订单商品列表
     * @param int $id 分拣员id
     * @param int $order_id 订单id
     * @param string $goods_name 商品名称
     * @return array
     * */
    public function getOrderGoodsToCustomer(int $id, int $order_id, string $goods_name)
    {
        $order_module = new OrdersModule();
        $order_field = 'orderId,orderNo,userId';
        $order_detail = $order_module->getOrderDetailByParams(array('orderId' => $order_id), $order_field);
        if (empty($order_detail)) {
            return array();
        }
        $order_detail['sorting_goods'] = array();
        $sorting_module = new SortingModule();
        $sorting_order_goods = $sorting_module->getSortingOrderGoods($id, $order_id, $goods_name);
        if (empty($sorting_order_goods)) {
            return $order_detail;
        }
        $order_detail['sorting_goods'] = $sorting_order_goods;
        return $order_detail;
    }

    /**
     * 按客户分拣-获取客户订单商品详情
     * @param $id 分拣员id
     * @param $sorting_goods_relationid 分拣商品唯一标识id
     * @return array
     * */
    public function getOrderGoodsDetailToCustomer(int $id, int $sorting_goods_relationid)
    {
        $sorting_module = new SortingModule();
        $detail = $sorting_module->getSortingGoodsDetailById($sorting_goods_relationid);
        if (empty($detail)) {
            return array();
        }
        $sorting_goods_detail = $this->getSortingCustomerDetailToGoods($id, $sorting_goods_relationid);
        return $sorting_goods_detail;
    }
}
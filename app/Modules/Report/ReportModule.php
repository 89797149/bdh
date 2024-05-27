<?php

namespace App\Modules\Report;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\OrdersModel;
use App\Models\OrdersReportModel;
use App\Models\UsersModel;
use CjsProtocol\LogicResponse;
use http\Encoding\Stream;
use Think\Model;

/**
 * 报表类
 * Class ShopsModule
 * @package App\Modules\Shops
 */
class ReportModule extends BaseModel
{
    /**
     * 根据门店id获取门店详情
     * @param array <p>
     * int shopId 门店id
     * int reportId 报表id
     * date reportDate 报表日期
     * </p>
     * @return array
     * */
    public function getReportInfoByParams(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new OrdersReportModel();
        $where = array(
            'shopId' => null,
            'reportId' => null,
            'reportDate' => null,
        );
        parm_filter($where, $params);
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }


    /**
     * 根据报表id获取报表详情
     * @param int $reportId 报表id
     * @param string $field 表字段
     * @return array
     * */
    public function getReportInfoById(int $reportId, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new OrdersReportModel();
        $where = array(
            'reportId' => $reportId,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * 根据条件获取订单销售统计列表
     */
    public function getReportSalesList($where, $field = "sor.*")
    {
        $data = M('sales_order_report sor')
            ->join('left join wst_users wu ON wu.userId = sor.userId')
            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
            ->join('left join wst_shops s ON s.shopId = sor.shopId')
            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = sor.goodsCatId1')
            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = sor.goodsCatId2')
            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = sor.goodsCatId3')
            ->where($where)
            ->field($field)
            ->group('sor.id')
            ->select();
        return (array)$data;
    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * 根据条件获取线上报表统计总列表
     */
    public function getOrderReportList($where, $field = "wor.*")
    {
        $data = M('order_report wor')
            ->join('left join wst_shops s on s.shopId = wor.shopId')
            ->where($where)
            ->field($field)
            ->select();
        return (array)$data;
    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * 根据条件获取商品报损列表
     */
    public function getInventoryLossList($where, $field = "il.*")
    {
        $data = M('inventory_loss il')
            ->join('left join wst_goods wg ON wg.goodsId = il.goods_id')
            ->join('left join wst_shops s ON s.shopId = il.shop_id')
            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = wg.goodsCatId1')
            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = wg.goodsCatId2')
            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = wg.goodsCatId3')
            ->where($where)
            ->field($field)
            ->group('il.loss_id')
            ->select();
        return (array)$data;
    }


    /**
     * 注：原有逻辑不清楚，将原有逻辑丢在该方法中，后续统一修改
     * 综合统计-会员统计
     * @param string $start_datetime 时间区间-开始时间
     * @param string $end_datetime 时间区间-结束时间
     * @return array
     * */
    public function memberCountReport($start_datetime = "", $end_datetime = "")
    {
        //该区间的代码逻辑都是原有的逻辑，只不过是把代码位置移动到这里了而已
        $member_count = 0;//会员总数 注：指定时间内的注册会员数
        $paid_order_member_count = 0;//下单会员数 注：会员总数中已下单并付款的会员数
        $member_order_count = 0;//会员订单总数 注：会员总数对应的已付款订单数
        $member_order_amount = 0;//会员购物总额 注：会员订单总数对应的订单实付金额
        $member_buy_rate = "0%";//会员购买率 注：下单会员数 / 会员总数 x 100%
        $member_avg_order = 0; //会员平均订单数 注：会员订单总数 / 会员总数
        $member_avg_order_amount = 0;//会员平均购物额 注：会员购物总额 / 会员订单总数
        $member_balance_pay_amount = 0;//会员余额支付 注：会员总数对应的用户所支出的余额
        $member_wx_pay_amount = 0;//会员微信支付 注：会员总数对应的用户所支出的微信金额
        $member_alipay_pay_amount = 0;//会员支付宝支付 注：会员总数对应的用户所支出的支付宝金额

        $users_model = new UsersModel();
        $member_where = array(
            'userFlag' => 1,
        );
        if (!empty($start_datetime) && !empty($end_datetime)) {
            $member_where['createTime'] = ['between', [$start_datetime, $end_datetime]];
        }
        $member_list = $users_model->where($member_where)->field("userId")->select();
        $user_id_arr = array_column($member_list, 'userId');
        $member_count = count($user_id_arr);
        if ($member_count > 0) {
            $orders_tab = new OrdersModel();
            $paid_order_member_where = array(
                'orderFlag' => 1,
                'isPay' => 1,
                'userId' => array('in', $user_id_arr),
            );
            $paid_order_member_count = $orders_tab->where($paid_order_member_where)->count("distinct(userId)");

            $member_order_count = $orders_tab->where($paid_order_member_where)->count("orderId");
            $member_order_amount = $orders_tab->where($paid_order_member_where)->sum("realTotalMoney");
            $member_buy_rate = (float)bc_math($member_order_count, $member_count, 'bcdiv', 2) . '%';
            $member_avg_order = bc_math($member_order_count, $member_count, 'bcdiv', 2);
            $member_avg_order = ceil($member_avg_order);
            $member_avg_order_amount = bc_math($member_order_amount, $member_order_count, 'bcdiv', 2);

            $member_balance_pay_amount_where = array(
                'orderFlag' => 1,
                'isPay' => 1,
                'payFrom' => 3,
                'userId' => array('in', $user_id_arr),
            );
            $member_balance_pay_amount = $orders_tab->where($member_balance_pay_amount_where)->sum('realTotalMoney');

            $notify_log_where = [
                'userId' => array('in', $user_id_arr),
                'notifyStatus' => 1,
            ];
            $notify_log_tab = M('notify_log');
            $notify_log_list = $notify_log_tab->where($notify_log_where)->select();

            foreach ($notify_log_list as $notify_log_list_row) {
                $request_json_decode = json_decode($notify_log_list_row['requestJson'], true);
                $amount = (float)$request_json_decode['amount'];
                $pay_type = (int)$request_json_decode['payType'];//1：支付宝|2：微信
                if ($pay_type == 1) {
                    $member_alipay_pay_amount = bc_math($member_alipay_pay_amount, $amount, 'bcadd', 2);
                } elseif ($pay_type == 2) {
                    $member_wx_pay_amount = bc_math($member_wx_pay_amount, $amount, 'bcadd', 2);
                }
            }
        }

        $result = array();
        $result['member_count'] = (int)$member_count;
        $result['paid_order_member_count'] = (int)$paid_order_member_count;
        $result['member_order_count'] = (int)$member_order_count;
        $result['member_order_amount'] = (float)$member_order_amount;
        $result['member_buy_rate'] = (string)$member_buy_rate;
        $result['member_avg_order'] = (int)$member_avg_order;
        $result['member_avg_order_amount'] = (float)$member_avg_order_amount;
        $result['member_balance_pay_amount'] = (float)$member_balance_pay_amount;
        $result['member_wx_pay_amount'] = (float)$member_wx_pay_amount;
        $result['member_alipay_pay_amount'] = (float)$member_alipay_pay_amount;
        return $result;
    }

}
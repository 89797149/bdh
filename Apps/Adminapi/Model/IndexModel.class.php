<?php

namespace Adminapi\Model;

use App\Modules\Config\SysConfigClassServiceModule;
use App\Modules\Log\LogServiceModule;
use App\Modules\Log\LogUserLoginsModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Users\UsersModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 首页服务类
 */
class IndexModel extends BaseModel
{
    /**
     * 获取商品配置分类信息
     */
    public function loadConfigsForParent($parentId)
    {
        $sql = "select * from " . $this->tablePrefix . "sys_configs where fieldType!='hidden' and parentId = {$parentId} order by parentId asc,fieldSort asc";
        $rs = $this->query($sql);
//        $configs = array();
//        if (count($rs) > 0) {
//            foreach ($rs as $key => $v) {
//                if ($v['fieldType'] == 'radio' || $v['fieldType'] == 'select') {
//                    $v['txt'] = explode('||', $v['valueRangeTxt']);
//                    $v['val'] = explode(',', $v['valueRange']);
//                }
//                $configs[$v['parentId']][] = $v;
//            }
//        }
//        unset($rs);
        return $rs;
    }

    /**
     * @return array
     * 保存商城配置信息
     */
    public function saveConfigsForCode()
    {
        $m = M('sys_configs');

        $p_all = I('');
        foreach ($p_all as $key => $val) {

            if (empty($val)) {
                $val = '';
            }

            $result = $m->where('fieldCode="' . $key . '"')->setField('fieldValue', $val);
            if (false === $result) {
                $rd['code'] = -1;
            }
        }

        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $sql = "select * from " . $this->tablePrefix . "sys_configs where fieldType!='hidden' order by parentId asc,fieldSort asc";
        $rs = $this->query($sql);
        if (!empty($rs)) {

            foreach ($rs as $key => $v) {

                //  echo I($v['fieldCode'])."\n".$v['fieldCode'];

                if (empty(I($v['fieldCode'])) && !is_numeric(I($v['fieldCode']))) {
                    continue;
                }
                if (in_array($v['fieldCode'], array('authorization_code', 'sorting_print_template'))) {
                    //勿动,会影响直播配置
                    $field_value = htmlspecialchars_decode(I($v['fieldCode']));
                } else {
                    $field_value = WSTAddslashes(I($v['fieldCode']));
                }

                $result = $m->where('fieldCode="' . $v['fieldCode'] . '"')->setField('fieldValue', $field_value);
                if (false === $result) {
                    $rd['code'] = -1;
                }
            }
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
            WSTDataFile("mall_config", '', null);
            //清除缓存
            WSTDelDir(C('WST_RUNTIME_PATH'));
        }
        return $rd;
    }


    /**
     * 保存授权码
     */
    public function saveLicense()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $m = M('sys_configs');
        $result = $m->where('fieldCode="mallLicense"')->setField('fieldValue', I('license'));
        if (false !== $result) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
            WSTDataFile("mall_config", '', null);
        }
        return $rd;
    }

    /**
     * 一周动态
     * @return [type] [description]
     */
    public function getWeekInfo()
    {
        $ret = array();
        //用户
        $weekDate = date('Y-m-d 00:00:00', time() - 604800);//一周内
        $ret['userNew'] = M('Users')->where('userFlag=1 and createTime>"' . $weekDate . '"')->count();//新增用户

        //申请店铺
        $ret['shopApply'] = M('Shops')->where('shopStatus >= 0 and shopFlag=1 and createTime>"' . $weekDate . '"')->count();

        //新增商品
        $ret['goodsNew'] = M('goods')->where('goodsFlag=1 and createTime>"' . $weekDate . '"')->count();
        //新增订单
        $ret['ordersNew'] = M('orders')->where('orderFlag=1 and orderStatus >=0 and createTime>"' . $weekDate . '"')->count();
        //新增店铺
        $map['shopStatus'] = 1;
        $ret['shopNew'] = M('Shops')->where('shopStatus = 1 and shopFlag=1 and createTime>"' . $weekDate . '"')->count();
        return $ret;
    }

    /**
     * 统计信息
     * @return array 统计信息的数组
     */
    public function getSumInfo()
    {
        $ret = array();
        $ret['userSum'] = M('Users')->where('userFlag=1')->count();//新增用户
        //申请店铺
        $ret['shopApplySum'] = M('Shops')->where('shopStatus = 0 and shopFlag=1')->count();
        //商品
        $ret['goodsSum'] = M('goods')->where('goodsFlag=1')->count();
        //订单
        $ret['ordersSum'] = M('orders')->where('orderFlag=1 and orderStatus >=0')->count();
        //订单总金额
        $ret['moneySum'] = M('orders')->where('orderFlag=1 and orderStatus >=0')->sum('totalMoney');

        //店铺
        $ret['shopSum'] = M('Shops')->where('shopStatus = 1 and shopFlag=1')->count();
        return $ret;
    }

    /**
     * 清空数据
     */
    public function cleanData()
    {
        M('orderids')->where("id > 0")->delete();
        M('users')->where('userId > 0')->save(['userFlag' => -1]);
        M('shops')->where('shopId > 0')->save(['shopFlag' => -1]);
        M('goods')->where('goodsId > 0')->save(['goodsFlag' => -1]);
        M('goods_appraises')->where('goodsId > 0')->delete();
        M('orders')->where('orderId > 0')->save(['orderFlag' => -1]);
        M('user_invitation')->where('id > 0')->delete();
        M('log_orders')->where('logId > 0')->delete();
        M('auto_create_orderid')->where('id > 0')->delete();
        M('box_order')->where('orderId > 0')->delete();
        M('order_complains')->where('complainId > 0')->delete();
        M('order_complainsrecord')->where('id > 0')->delete();
        M('order_goods')->where('orderId > 0')->delete();
        M('pos_order_id')->where('id > 0')->delete();
        M('pos_order_settlements')->where('settlementId > 0')->delete();
        M('pos_orders')->where('id > 0')->delete();
        M('pos_orders_goods')->where('id > 0')->delete();
        M('pos_return_orders')->where('id > 0')->delete();
        M('pos_return_orders_goods')->where('id > 0')->delete();
        M('buy_user_record')->where('burId > 0')->delete();
        M('distribution_withdraw')->where('id > 0')->delete();
        M('withdraw')->where('id > 0')->delete();
        M('push_record')->where('id > 0')->delete();
    }

    /**
     * @return array
     * 首页统计订单客户相关数据
     */
    public function getStatisticsInfo()
    {
        $ordersModule = new OrdersModule();
        $usersModule = new UsersModule();
        $logUserLoginsModule = new LogUserLoginsModule();
        $lastDayTime = getDateRules('yesterday');//昨天
        $todayTime = getDateRules('today');//当天
        $thisWeekTime = getDateRules('thisWeek');//本周
        $lastWeekTime = getDateRules('lastWeek');//上周
        $lastMonthTime = getDateRules('lastMonth');//上月
        $thisMonthTime = getDateRules('thisMonth');//本月

        $rest = [];
        //==========订单订单实付金额(总)=======start==========================================
        //昨天
        $lastDayMoney = $ordersModule->getOrderRealTotalMoney($lastDayTime['startDate'], $lastDayTime['endDate']);
        $rest['lastDayMoney'] = $lastDayMoney['data']['realTotalMoney'];
        //当天
        $todayMoney = $ordersModule->getOrderRealTotalMoney($todayTime['startDate'], $todayTime['endDate']);
        $rest['todayOrderMoney'] = $todayMoney['data']['realTotalMoney'];
        //本周
//        $thisWeekMoney = $ordersModule->getOrderRealTotalMoney($thisWeekTime['startDate'], $thisWeekTime['endDate']);
        //上周
//        $lastWeekMoney = $ordersModule->getOrderRealTotalMoney($lastWeekTime['startDate'], $lastWeekTime['endDate']);
        //同比率（本周-上周）/上周 x 100%
//        $rest['weekOrderMoneyRatio'] = round(($thisWeekMoney['data']['realTotalMoney'] - $lastWeekMoney['data']['realTotalMoney']) / $lastWeekMoney['data']['realTotalMoney'] * 100, 2) . "%";
        //同比率（当天-昨天）/昨天 x 100%
        $rest['weekOrderMoneyRatio'] = round(($todayMoney['data']['realTotalMoney'] - $lastDayMoney['data']['realTotalMoney']) / $lastDayMoney['data']['realTotalMoney'] * 100, 2) . "%";
        if ($rest['weekOrderMoneyRatio'] >= 0) {//判断正负值
            $rest['weekOrderMoneyRatioVerity'] = 1;
        } else {
            $rest['weekOrderMoneyRatioVerity'] = -1;
        }
        //=======================================end=========================================
        //==========新增用户=======start=====================================================
        //昨天
        $lastDayMoney = $usersModule->getNewUsersCount($lastDayTime['startDate'], $lastDayTime['endDate']);
        $rest['lastDayNewUsersCount'] = $lastDayMoney['data'];
        //当天
        $todayMoney = $usersModule->getNewUsersCount($todayTime['startDate'], $todayTime['endDate']);
        $rest['todayNewUsersCount'] = $todayMoney['data'];
        //本周
//        $thisWeekMoney = $usersModule->getNewUsersCount($thisWeekTime['startDate'], $thisWeekTime['endDate']);
        //上周
//        $lastWeekMoney = $usersModule->getNewUsersCount($lastWeekTime['startDate'], $lastWeekTime['endDate']);
        //同比率（本周-上周）/上周 x 100%
//        $rest['weekNewUsersCountRatio'] = round(($thisWeekMoney['data'] - $lastWeekMoney['data']) / $lastWeekMoney['data'] * 100, 2) . "%";
        //同比率（当天-昨天）/昨天 x 100%
        $rest['weekNewUsersCountRatio'] = round(($todayMoney['data'] - $lastDayMoney['data']) / $lastDayMoney['data'] * 100, 2) . "%";
        if ($rest['weekNewUsersCountRatio'] >= 0) {//判断正负值
            $rest['weekNewUsersCountRatioVerity'] = 1;
        } else {
            $rest['weekNewUsersCountRatioVerity'] = -1;
        }
        //=======================================end==========================================
        //==========访客数=========start=====================================================
        //昨天
        $lastDayMoney = $logUserLoginsModule->getDailyAmountCount($lastDayTime['startDate'], $lastDayTime['endDate']);
        $rest['lastDayDailyAmountCount'] = $lastDayMoney['data'];
        //当天
        $todayMoney = $logUserLoginsModule->getDailyAmountCount($todayTime['startDate'], $todayTime['endDate']);
        $rest['todayDailyAmountCount'] = $todayMoney['data'];
        //本周
//        $thisWeekMoney = $logUserLoginsModule->getDailyAmountCount($thisWeekTime['startDate'], $thisWeekTime['endDate']);
        //上周
//        $lastWeekMoney = $logUserLoginsModule->getDailyAmountCount($lastWeekTime['startDate'], $lastWeekTime['endDate']);
        //同比率（本周-上周）/上周 x 100%
//        $rest['weekDailyAmountCountRatio'] = round(($thisWeekMoney['data'] - $lastWeekMoney['data']) / $lastWeekMoney['data'] * 100, 2) . "%";
        $rest['weekDailyAmountCountRatio'] = round(($todayMoney['data'] - $lastDayMoney['data']) / $lastDayMoney['data'] * 100, 2) . "%";
        if ($rest['weekDailyAmountCountRatio'] >= 0) {//判断正负值
            $rest['weekDailyAmountCountRatioVerity'] = 1;
        } else {
            $rest['weekDailyAmountCountRatioVerity'] = -1;
        }
        //=======================================end==========================================
        //店铺数量
        $rest['shopsCount'] = M('shops')->where(['shopFlag' => 1, 'shopStatus' => 1])->count();
        //==========订单数=========start=====================================================
        //当天
        $todayMoney = $ordersModule->getOrderCount($todayTime['startDate'], $todayTime['endDate']);
        $rest['todayOrderCount'] = $todayMoney['data']['orderCount'];
        $rest['todayUserPayCount'] = $todayMoney['data']['userPayCount'];
        //本周
//        $thisWeekMoney = $ordersModule->getOrderCount($thisWeekTime['startDate'], $thisWeekTime['endDate']);
        //昨天
        $lastWeekMoney = $ordersModule->getOrderCount($lastDayTime['startDate'], $lastDayTime['endDate']);
        //本月
        $thisMonthOrder = $ordersModule->getOrderCount($thisMonthTime['startDate'], $thisMonthTime['endDate']);
        $rest['thisMonthOrderCount'] = $thisMonthOrder['data']['orderCount'];
        $rest['thisMonthUserPayCount'] = $thisMonthOrder['data']['userPayCount'];
        //上月
        $lastMonthOrder = $ordersModule->getOrderCount($lastMonthTime['startDate'], $lastMonthTime['endDate']);
        $rest['lastMonthOrderCount'] = $lastMonthOrder['data']['orderCount'];
        $rest['lastMonthUserPayCount'] = $lastMonthOrder['data']['userPayCount'];
        //同比率（当天-昨天）/昨天 x 100%
        $rest['weekOrdersCountRatio'] = round(($todayMoney['data']['orderCount'] - $lastWeekMoney['data']['orderCount']) / $lastWeekMoney['data']['orderCount'] * 100, 2) . "%";
        if ($rest['weekOrdersCountRatio'] >= 0) {//判断正负值
            $rest['weekOrdersCountRatioVerity'] = 1;
        } else {
            $rest['weekOrdersCountRatioVerity'] = -1;
        }
        $rest['weekUserPayCountRatio'] = round(($todayMoney['data']['userPayCount'] - $lastWeekMoney['data']['userPayCount']) / $lastWeekMoney['data']['userPayCount'] * 100, 2) . "%";
        if ($rest['weekUserPayCountRatio'] >= 0) {//判断正负值
            $rest['weekUserPayCountRatioVerity'] = 1;
        } else {
            $rest['weekUserPayCountRatioVerity'] = -1;
        }
        //同比率（本月-上月）/上月*100%
        $rest['monthOrdersCountRatio'] = round(($thisMonthOrder['data']['orderCount'] - $lastMonthOrder['data']['orderCount']) / $lastMonthOrder['data']['orderCount'] * 100, 2) . "%";
        if ($rest['monthOrdersCountRatio'] >= 0) {//判断正负值
            $rest['monthOrdersCountRatioVerity'] = 1;
        } else {
            $rest['monthOrdersCountRatioVerity'] = -1;
        }
        $rest['monthUserPayCountRatio'] = round(($thisMonthOrder['data']['userPayCount'] - $lastMonthOrder['data']['userPayCount']) / $lastMonthOrder['data']['userPayCount'] * 100, 1) . "%";
        if ($rest['monthUserPayCountRatio'] >= 0) {//判断正负值
            $rest['monthUserPayCountRatioVerity'] = 1;
        } else {
            $rest['monthUserPayCountRatioVerity'] = -1;
        }
        //=======================================end==========================================
        return $rest;
    }

    /**
     * @param $typeTime
     * @return array
     * 获取商品销量排行
     * 0:全部，1:今天，2:昨天，3:最近7天，4:最近30天，5:本月，6:本年
     */
    public function getGoodsSalesRanking($typeTime)
    {
        $where = [];
        $where['o.orderFlag'] = 1;
        $where['o.orderStatus'] = ['IN', [3, 4]];//3:配送中 | 4:用户确认收货

        if ($typeTime == 1) {
            $dayTime = getDateRules('today');//今天
        } elseif ($typeTime == 2) {
            $dayTime = getDateRules('yesterday');//昨天
        } elseif ($typeTime == 3) {
            $dayTime = getDateRules('lastSevenDays');//最近7天
        } elseif ($typeTime == 4) {
            $dayTime = getDateRules('lastThirtyDays');//最近30天
        } elseif ($typeTime == 5) {
            $dayTime = getDateRules('thisMonth');//本月
        } elseif ($typeTime == 6) {
            $dayTime = getDateRules('thisYear');//本年
        }

        if (!empty($typeTime)) {
            $where['o.createTime'] = ['between', [$dayTime['startDate'], $dayTime['endDate']]];
        } else {
            $dayTime = getDateRules('lastSevenDays');//最近7天
            $where['o.createTime'] = ['between', [$dayTime['startDate'], $dayTime['endDate']]];
        }
        $field = "wog.goodsId,wog.goodsName,wog.goodsNums,wg.goodsStock,ws.shopName";
        $orderGoods = M('orders o')
            ->join("left join wst_order_goods wog on wog.orderId = o.orderId")
            ->join("left join wst_goods wg on wg.goodsId = wog.goodsId")
            ->join("left join wst_shops ws on ws.shopId = o.shopId")
            ->where($where)
            ->field($field)
            ->select();
        $orderGoodsList = [];
        if (!empty($orderGoods)) {
            $goodsIds = array_unique(array_get_column($orderGoods, 'goodsId'));
            foreach ($goodsIds as $v) {
                $goodsNums = 0;
                foreach ($orderGoods as $val) {
                    if ((int)$val['goodsId'] == (int)$v) {
                        $goodsName = $val['shopName'] . "#" . $val['goodsName'];
                        $goodsNums += $val['goodsNums'];
                        $goodsStock = $val['goodsStock'];
                    }
                }
                $goodsList = [];
                $goodsList['goodsId'] = $v;
                $goodsList['goodsName'] = (string)$goodsName;
                $goodsList['goodsNums'] = $goodsNums;
                $goodsList['goodsStock'] = $goodsStock;
                $orderGoodsList[] = $goodsList;
            }
            $goodsNum = array_column($orderGoodsList, 'goodsNums');//取值
            array_multisort($goodsNum, SORT_DESC, $orderGoodsList);//排序
            $orderGoodsList = array_slice($orderGoodsList, 0, 20);//取前20条
            $goodsNumSum = array_sum(array_column($orderGoodsList, 'goodsNums'));//求和
            foreach ($orderGoodsList as $k => $value) {
                $orderGoodsList[$k]['goodsCountRatio'] = round($value['goodsNums'] / $goodsNumSum * 100, 1);//前端要求不需要百分号
            }
        }
        return $orderGoodsList;
    }

    /**
     * @param $typeTime
     * @return mixed
     * 获取成交用户数据
     * 0:全部，1:今天，2:昨天，3:最近7天，4:最近30天，5:本月，6:本年
     */
    public function getDealUserInfo($typeTime)
    {
        $ordersModule = new OrdersModule();
        $logUserLoginsModule = new LogUserLoginsModule();

        $where = [];
        $where['o.orderFlag'] = 1;
        $where['o.orderStatus'] = ['IN', [3, 4]];//3:配送中 | 4:用户确认收货

        if ($typeTime == 1) {
            $dayTime = getDateRules('today');//今天
        } elseif ($typeTime == 2) {
            $dayTime = getDateRules('yesterday');//昨天
        } elseif ($typeTime == 3) {
            $dayTime = getDateRules('lastSevenDays');//最近7天
        } elseif ($typeTime == 4) {
            $dayTime = getDateRules('lastThirtyDays');//最近30天
        } elseif ($typeTime == 5) {
            $dayTime = getDateRules('thisMonth');//本月
        } elseif ($typeTime == 6) {
            $dayTime = getDateRules('thisYear');//本年
        }
        //访客人数
        $userAmountCount = $logUserLoginsModule->getDailyAmountCount($dayTime['startDate'], $dayTime['endDate']);
        $rest['userAmountCount'] = $userAmountCount['data'];
        //订单数|支付人数
        $orderInfo = $ordersModule->getOrderCount($dayTime['startDate'], $dayTime['endDate']);
        $rest['orderCount'] = $orderInfo['data']['orderCount'];//下单数
        $rest['userPayCount'] = $orderInfo['data']['userPayCount'];//支付人数
        //获取金额
        $todayMoney = $ordersModule->getOrderRealTotalMoney($dayTime['startDate'], $dayTime['endDate']);
        $rest['realTotalMoney'] = $todayMoney['data']['realTotalMoney'];//支付金额
        $rest['noPayMoney'] = $todayMoney['data']['noPayMoney'];//未支付金额
        $rest['allMoney'] = $todayMoney['data']['allMoney'];//下单金额
        //访客-下单转化率 下单人数/访客人数 x 100%
        $rest['userConversionRate'] = round($rest['orderCount'] / $rest['userAmountCount'] * 100, 2) . "%";
        //下单-下单转化率 支付人数/下单人数 x 100%
        $rest['orderConversionRate'] = round($rest['userPayCount'] / $rest['orderCount'] * 100, 2) . "%";
        return $rest;
    }

    /**
     * @param $params
     * @return mixed
     * 获取总后台操作日志
     * typeTime:0:全部，1:今天，2:昨天，3:最近7天，4:最近30天，5:本月，6:本年.7:自定义时间
     * operationType:  操作行为类型【0:全部 1:增加、2:删除、3:修改】
     */
    public function getOperationLogList($params)
    {
        $typeTime = $params['typeTime'];
        $operationType = $params['operationType'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $logServiceModule = new LogServiceModule();


        if ($typeTime == 1) {
            $dayTime = getDateRules('today');//今天
        } elseif ($typeTime == 2) {
            $dayTime = getDateRules('yesterday');//昨天
        } elseif ($typeTime == 3) {
            $dayTime = getDateRules('lastSevenDays');//最近7天
        } elseif ($typeTime == 4) {
            $dayTime = getDateRules('lastThirtyDays');//最近30天
        } elseif ($typeTime == 5) {
            $dayTime = getDateRules('thisMonth');//本月
        } elseif ($typeTime == 6) {
            $dayTime = getDateRules('thisYear');//本年
        } elseif ($typeTime == 7) {
            $dateCode = $params['startDate'] . " - " . $params['endDate'];
            $dayTime = getDateRules($dateCode);//自定义时间
        }

        $param = [];
        $param['startTime'] = $dayTime['startDate'];
        $param['endTime'] = $dayTime['endDate'];
        $param['operationType'] = $operationType;
        $getOperationLogList = $logServiceModule->getOperationLogList($param);

        $rest = arrayPage($getOperationLogList['data'], $page, $pageSize);

        return $rest;
    }
}
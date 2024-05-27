<?php

namespace App\Modules\Orders;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Orders\OrderGoodsEnum;
use App\Models\BaseModel;
use App\Models\CouponsUsersModel;
use App\Models\DistributionInvitationModel;
use App\Models\GoodsAttributesModel;
use App\Models\GoodsModel;
use App\Models\GoodsOrderNumLimitModel;
use App\Models\GoodsPriceDiffeModel;
use App\Models\GoodsSecondsKillLimitModel;
use App\Models\OrderComplainsModel;
use App\Models\OrderComplainsrecordModel;
use App\Models\OrderGoodsModel;
use App\Models\OrderidsModel;
use App\Models\OrderMergeModel;
use App\Models\OrderRemindsModel;
use App\Models\OrdersModel;
use App\Models\ShopsModel;
use App\Models\UserInvitationModel;
use App\Models\UserSelfGoodsModel;
use App\Modules\Disribution\DistributionModule;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Invitation\UserInvitationModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Log\LogSysMoneysModule;
use App\Modules\Notify\NotifyModule;
use App\Modules\Pay\PayModule;
use App\Modules\PSD\LineModule;
use App\Modules\PSD\TaskModule;
use App\Modules\Shops\ShopCatsModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\Coupons\CouponsModule;
use App\Modules\ShopStaffMember\ShopStaffMemberModule;
use App\Modules\Sorting\SortingModule;
use App\Modules\Supplier\SupplierModule;
use App\Modules\Users\UsersModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 用户类,该类只为OrdersServiceModule类服务
 * Class OrdersModule
 * @package App\Modules\Orders
 */
class OrdersModule extends BaseModel
{
    /**
     * 根据条件获取订单商品明细详情
     * @param array $params <p>
     * int id 订单商品明细id
     * int orderId 订单id
     * int goodsId 商品id
     * int skuId skuid
     * </p>
     * @param string $field 表字段
     * @param int $data_type 返回格式(1:返回data格式 2:直接返回结果集) PS:主要是兼容之前的程序
     * @return array
     * */
    public function getOrderGoodsInfoByParams(array $params, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $model = new OrderGoodsModel();
        $where = array(
            'id' => null,
            'orderId' => null,
            'goodsId' => null,
            'skuId' => null,
        );
        parm_filter($where, $params);
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未查到相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
        } else {
            return $result;
        }
    }

    /**
     * 根据明细id获取订单商品明细详情
     * @param int $id 订单商品唯一标识id
     * @param string $field 表字段
     * @param int $data_type 返回数据格式,
     * @return array
     * */
    public function getOrderGoodsInfoById(int $id, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $model = new OrderGoodsModel();
        $where = array(
            'id' => $id
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未查到相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
        } else {
            return $result;
        }

    }

    /**
     * 更新订单商品明细信息
     * @param array $params 需要更新的表字段
     * @param object $trans
     * @return array
     * */
    public function updateOrderGoodsInfo(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        $order_goods_model = new OrderGoodsModel();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $save = array(
            'id' => 0,
            'goodsNum' => null,
            'weight' => null,
            'actionStatus' => null,
            'sortingNum' => null,
            'nuclearCargoNum' => null,
            'scoreMoney' => null,
            'scoreMoney' => null,
            'hook_id' => null,
            'bind_hook_date' => null,
        );
        parm_filter($save, $params);
        $result = $order_goods_model->save($save);
        if (!$result) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('更新失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
    }

    /**
     * 根据订单id获取订单详情
     * @param int $order_id
     * @param string $field 表字段
     * @param int $data_type 返回格式(1:返回data格式 2:直接返回结果集) PS:主要是兼容之前的程序
     * @return array
     * */
    public function getOrderInfoById(int $order_id, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $order_model = new OrdersModel();
        $where = array(
            'orderId' => $order_id,
            'orderFlag' => 1,
        );
        $order_info = $order_model->where($where)->field($field)->find();
        if (empty($order_info)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无数据')->toArray();
            } else {
                return array();
            }
        }
        if (isset($order_info['userId'])) {
            $users_module = new UsersModule();
            $payment_userinfo = $users_module->getUsersDetailById($order_info['userId'], 'userName', 2);//下单人信息
            $order_info['payment_username'] = $payment_userinfo['userName'];
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($order_info)->setMsg('成功')->toArray();
        } else {
            return (array)$order_info;
        }
    }

    /**
     * 根据订单id获取订单商品
     * @param int $order_id
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderGoodsListById(int $order_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $order_goods_model = new OrderGoodsModel();
        $where = array(
            'orderId' => $order_id
        );
        $result = $order_goods_model->where($where)->field($field)->order('id asc')->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 获取订单状态名称
     * @param int $orderStatus
     * @return string $orderStatusName
     * */
    public function getOrderStatusName(int $orderStatus)
    {
        //订单状态[-8:门店拒绝/门店取消 | -7:用户取消(受理后-店铺已读) | -6:用户取消(已受理后-店铺未读)（支付成功-发货前取消） | -5:门店不同意拒收 | -4:门店同意拒收 | -3:用户拒收 | -2:未付款的订单 | -1：用户取消(未受理前) | 0:未受理 | 1:已受理 | 2:打包中 | 3:配送中 | 4:用户确认收货 | 7:等待骑手接单 | 8:骑手-待取货 | 9：骑手-订单被取消（只写入日志） | 10：骑手-订单过期（并写日志 作为异常订单显示）| 11：骑手-投递异常（只写入日志）| 12:预售订单（未支付）| 13:预售订单（首款已付） | 14：预售订单-已付款 | 15:拼团 | 16:司机待配送|17:司机配送中]
        $orderStatusName = '未知';
        switch ($orderStatus) {
            case -8:
                //$orderStatusName = '门店取消';
                $orderStatusName = '已关闭';
                break;
            case -7:
                //$orderStatusName = '用户取消(受理后-店铺已读)';
                $orderStatusName = '已关闭';
                break;
            case -6:
                //$orderStatusName = '用户取消(受理后-店铺未读)';
                $orderStatusName = '已关闭';
                break;
            case -5:
                //$orderStatusName = '门店不同意拒收';
                $orderStatusName = '已关闭';
                break;
            case -4:
                //$orderStatusName = '门店同意拒收';
                $orderStatusName = '已关闭';
                break;
            case -3:
                //$orderStatusName = '用户拒收';
                $orderStatusName = '已关闭';
                break;
            case -2:
                $orderStatusName = '待付款';
                break;
            case -1:
                //$orderStatusName = '用户取消(未受理前)';
                $orderStatusName = '已关闭';
                break;
            case 0:
                $orderStatusName = '待接单';
                break;
            case 1:
                //$orderStatusName = '已接单';
                $orderStatusName = '待发货';
                break;
            case 2:
                //$orderStatusName = '打包中';
                $orderStatusName = '待发货';
                break;
            case 3:
                $orderStatusName = '待收货';
                break;
            case 4:
                $orderStatusName = '已完成';
                break;
            case 7:
                //$orderStatusName = '等待骑手接单';
                $orderStatusName = '外卖配送';
                break;
            case 8:
                //$orderStatusName = '骑手-待取货';
                $orderStatusName = '外卖配送';
                break;
            case 9:
                //$orderStatusName = '骑手-订单被取消';
                $orderStatusName = '外卖配送';
                break;
            case 10:
                //$orderStatusName = '骑手-订单过期';
                $orderStatusName = '骑手配送';
                break;
            case 11:
                $orderStatusName = '骑手-投递异常';
                break;
            case 12:
                $orderStatusName = '预售订单（未支付）';
                break;
            case 13:
                $orderStatusName = '预售订单（首款已付）';
                break;
            case 14:
                $orderStatusName = '预售订单（首款已付）';
                break;
            case 16:
                //$orderStatusName = '司机待配送';
                $orderStatusName = '外卖配送';
                break;
            case 17:
                //$orderStatusName = '司机配送中';
                $orderStatusName = '外卖配送';
                break;
        }
        return $orderStatusName;
    }

    /**
     * 获取支付状态名称
     * @param int $payStatus
     * @return string $payStatusName
     * */
    public function getPayStatusName(int $payStatus)
    {
        $payStatusName = '未知';
        switch ($payStatus) {
            case 0:
                $payStatusName = '未支付';
                break;
            case 1:
                $payStatusName = '已支付';
                break;
            case 2:
                $payStatusName = '已退款';
                break;
        }
        return $payStatusName;
    }

    /**
     * 获取支付方式名称
     * @param int $payFrom
     * @return string $payFrom 【1:支付宝|2：微信|3:余额|4:货到付款】
     * */
    public function getPayName(int $payFrom)
    {
        $pay_name = "未知";
        switch ($payFrom) {
            case 1:
                $pay_name = '支付宝';
                break;
            case 2:
                $pay_name = '微信';
                break;
            case 3:
                $pay_name = '余额';
                break;
            case 4:
                $pay_name = '货到付款';
                break;
        }
        return $pay_name;
    }

    /**
     * @param int $deliverType
     * @return string
     * 获取配送方式名称
     */
    public function getDeliverTypeName(int $deliverType)
    {
        //配送方式[0:商城配送[废弃] | 1:门店配送 | 2：达达配送 | 3.蜂鸟配送 | 4:快跑者 | 5:自建跑腿 | 6:自建司机]
        $deliverTypeName = '未知';
        switch ($deliverType) {
            case 0:
                $deliverTypeName = '商城配送';
                break;
            case 1:
                $deliverTypeName = '门店配送';
                break;
            case 2:
                $deliverTypeName = '达达配送';
                break;
            case 3:
                $deliverTypeName = '蜂鸟配送';
                break;
            case 4:
                $deliverTypeName = '快跑者';
                break;
            case 5:
                $deliverTypeName = '自建跑腿';
                break;
            case 6:
                $deliverTypeName = '自建司机';
                break;
        }
        return $deliverTypeName;
    }

    /**
     * @param int $orderType
     * @return string
     * 获取订单类型或订单来源名称
     */
    public function getOrderTypeName(int $orderType)
    {
        //订单类型或订单来源(1.普通订单|2.拼团订单|3.预售订单|4.秒杀订单|5:非常规订单)
        $orderTypeName = '未知';
        switch ($orderType) {
            case 1:
                $orderTypeName = '普通订单';
                break;
            case 2:
                //$orderTypeName = '达达配送';
                $orderTypeName = '拼团订单';
                break;
            case 3:
                $orderTypeName = '预售订单';
                break;
            case 4:
                $orderTypeName = '秒杀订单';
                break;
            case 5:
                $orderTypeName = '非常规订单';
                break;
        }
        return $orderTypeName;
    }

    /**
     * 根据订单id获取订单商品和商品信息
     * @param int $order_id
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderGoodsList(int $order_id, $field = 'og.*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $order_goods_model = new OrderGoodsModel();
        $where = array(
            'orderId' => $order_id
        );
        $result = $order_goods_model->alias('og')
            ->join('left join wst_goods g on g.goodsId = og.goodsId')
            ->where($where)
            ->field($field)
            ->order('og.id asc')
            ->select();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        } else {
            return $result;
        }
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间
     * 获取实际订单总金额总和
     */
    public function getOrderRealTotalMoney($startTime = '', $endTime = '')
    {
        $response = LogicResponse::getInstance();
        $order_model = new OrdersModel();
        $where = [];
        $where['orderFlag'] = 1;
        $where['isPay'] = 1;//已支付

        if (!empty($startTime) and !empty($endTime)) {
            $where['createTime'] = ['between', [$startTime, $endTime]];
        }
        $realTotalMoney = $order_model->where($where)->sum('realTotalMoney');//已支付
        $where['isPay'] = 0;//未支付
        $noPayMoney = $order_model->where($where)->sum('realTotalMoney');//未支付
        unset($where['isPay']);
        $allMoney = $order_model->where($where)->sum('realTotalMoney');//已支付和未支付
        $where['isRefund'] = 1;//是否退款[0:否 1：是]
        $orderRefundMoney = $order_model->where($where)->sum('realTotalMoney');//全部退货订单
        unset($where['isRefund']);
        $where['orderStatus'] = ['IN', [-8, -7, -6, -1]];//-8:门店拒绝/门店取消 | -7:用户取消(受理后-店铺已读) | -6:用户取消(已受理后-店铺未读)（支付成功-发货前取消） -1：用户取消(未受理前)
        $orderCancelMoney = $order_model->where($where)->sum('realTotalMoney');//取消订单的总订单金额
        $where['orderStatus'] = 4;
        $orderTradedMoney = $order_model->where($where)->sum('realTotalMoney');//已成交订单金额
        $rest = [];
        $rest['realTotalMoney'] = (float)$realTotalMoney;
        $rest['noPayMoney'] = (float)$noPayMoney;
        $rest['allMoney'] = (float)$allMoney;
        $rest['invalidOrderMoney'] = $noPayMoney + $orderRefundMoney + $orderCancelMoney;//无效订单金额
        $rest['orderTradedMoney'] = (float)$orderTradedMoney;//已成交订单金额
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->setMsg('成功')->toArray();
    }

    /**
     * 注：咱也不知道之前是以什么逻辑写的，复制下来在原有基础上改动吧......
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间
     * 获取实际订单总金额总和
     */
    public function getOrderRealTotalMoneyNew($startTime = '', $endTime = '')
    {
        $response = LogicResponse::getInstance();
        $order_tab = new OrdersModel();
        $common_where = [
            'isPay' => 1,
            'orderFlag' => 1,
        ];
        if (!empty($startTime) and !empty($endTime)) {
            $common_where['pay_time'] = ['between', [$startTime, $endTime]];
        }
        $sale_order_amount_sum = 0;//销售总额 注：已支付成功的
        $effective_order_amount_sum = 0;//有效订单总额 注：已付款未取消订单
        $invalid_order_amount_sum = 0;//无效订单总额 注：已付款已取消订单
        $completed_order_amount_sum = 0;//已成交订单总额 注：已付款已收货


        $sale_order_amount_sum_where = $common_where;
        $sale_order_amount_sum = $order_tab->where($sale_order_amount_sum_where)->sum('realTotalMoney');

        $effective_order_amount_sum_where = $common_where;
        $effective_order_amount_sum_where['orderStatus'] = ['not in', [-6, -1]];
        $effective_order_amount_sum = $order_tab->where($effective_order_amount_sum_where)->sum('realTotalMoney');

        $invalid_order_amount_sum_where = $common_where;
        $invalid_order_amount_sum_where['orderStatus'] = ['in', [-6, -1]];
        $invalid_order_amount_sum = $order_tab->where($invalid_order_amount_sum_where)->sum('realTotalMoney');

        $completed_order_amount_sum_where = $common_where;
        $completed_order_amount_sum_where['orderStatus'] = 4;
        $completed_order_amount_sum = $order_tab->where($completed_order_amount_sum_where)->sum('realTotalMoney');

        $rest = [];
        //返回字段继续沿用之前的
        $rest['sale_order_amount_sum'] = (float)$sale_order_amount_sum;
        $rest['effective_order_amount_sum'] = (float)$effective_order_amount_sum;
        $rest['invalid_order_amount_sum'] = (float)$invalid_order_amount_sum;
        $rest['invalidOrderMoney'] = (float)$invalid_order_amount_sum;//无效订单金额
        $rest['completed_order_amount_sum'] = (float)$completed_order_amount_sum;//已成交订单金额
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->setMsg('成功')->toArray();
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间
     * 获取订单数
     * 支付人数
     */
    public function getOrderCount($startTime = '', $endTime = '')
    {
        $response = LogicResponse::getInstance();
        $order_model = new OrdersModel();
        $where = [];
        $where['orderFlag'] = 1;
        if (!empty($startTime) and !empty($endTime)) {
            $where['createTime'] = ['between', [$startTime, $endTime]];
        }

        $orderCount = $order_model->where($where)->count();
        $where['isPay'] = 1;
        $userPayCount = $order_model->where($where)->count();//已付款订单数量
        $userPayOrderCount = $order_model->where($where)->group('userId')->count();//已付款用户数量
        $where['isPay'] = 0;
        $noPayCount = $order_model->where($where)->count();//未付款订单
        unset($where['isPay']);
        $where['isRefund'] = 1;//是否退款[0:否 1：是]
        $orderRefundCount = $order_model->where($where)->count();//全部退货订单
        unset($where['isRefund']);
        $where['orderStatus'] = ['IN', [-8, -7, -6, -1]];//-8:门店拒绝/门店取消 | -7:用户取消(受理后-店铺已读) | -6:用户取消(已受理后-店铺未读)（支付成功-发货前取消） -1：用户取消(未受理前)
        $orderCancelCount = $order_model->where($where)->count();//取消订单的总订单数
        $where['orderStatus'] = 4;
        $orderTradedCount = $order_model->where($where)->count();//已成交订单总数
        $rest = [];
        $rest['orderCount'] = (int)$orderCount;//全部订单数量
        $rest['userPayCount'] = (int)$userPayCount;//已支付订单数量
        $rest['userPayOrderCount'] = (int)$userPayOrderCount;//下单会员数
        $rest['noPayCount'] = (int)$noPayCount;//未付款订单
        $rest['orderRefundCount'] = (int)$orderRefundCount;//全部退货订单
        $rest['orderCancelCount'] = (int)$orderCancelCount;//取消订单的总订单数
        $rest['orderTradedCount'] = (int)$orderTradedCount;//已成交订单总数
        $rest['invalidOrderCount'] = $noPayCount + $orderRefundCount + $orderCancelCount;//无效订单总数
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->setMsg('成功')->toArray();
    }

    /**
     * 注：咱也不知道之前是以什么逻辑写的，现在复制一份上面的代码在原有基础上修改
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间
     * 获取订单数
     * 支付人数
     */
    public function getOrderCountNew($startTime = '', $endTime = '')
    {
        $response = LogicResponse::getInstance();
        $order_tab = new OrdersModel();
        $common_where = [
            'isPay' => 1,
            'orderFlag' => 1,
        ];
        if (!empty($startTime) and !empty($endTime)) {
            $common_where['pay_time'] = ['between', [$startTime, $endTime]];
        }

        $all_order_count = 0;//订单总数 注：有效订单总数与无效订单总数的和
        $effective_order_count = 0;//有效订单总数 注：已付款未取消订单
        $invalid_order_count = 0;//无效订单总数 注：已付款已取消订单
        $completed_order_count = 0;//已成交订单总数 注：已付款已收货

        $effective_order_count_where = $common_where;
        $effective_order_count_where['orderStatus'] = ['not in', [-6, -1]];
        $effective_order_count = $order_tab->where($effective_order_count_where)->count('orderId');

        $invalid_order_count_where = $common_where;
        $invalid_order_count_where['orderStatus'] = ['in', [-6, -1]];
        $invalid_order_count = $order_tab->where($invalid_order_count_where)->count('orderId');

        $completed_order_count_where = $common_where;
        $completed_order_count_where['orderStatus'] = 4;
        $completed_order_count = $order_tab->where($completed_order_count_where)->count('orderId');


        $rest = [];
        $rest['all_order_count'] = (int)bc_math($effective_order_count, $invalid_order_count, 'bcadd', 2);
        $rest['effective_order_count'] = $effective_order_count;
        $rest['invalid_order_count'] = $invalid_order_count;
        $rest['completed_order_count'] = $completed_order_count;
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->setMsg('成功')->toArray();
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param array $whereInfo 其他条件
     * @return array
     * 根据时间区间|条件获取订单列表
     * 注：数据表别名
     */
    /**
     * @param string $startTime
     * @param string $endTime
     * @param array $whereInfo 其他条件
     * @param string $field
     * @param string $sort
     * @return array
     * 根据时间区间|条件获取订单列表
     * 注：数据表别名
     */
    public function getOrderList($startTime = '', $endTime = '', $field = 'o.*,wc.couponMoney', $sort = 'desc')
    {
        $response = LogicResponse::getInstance();
        $order_model = new OrdersModel();
        $where = [];
        $where['o.orderFlag'] = 1;
        $where['o.orderStatus'] = 4;
        $where['o.isPay'] = 1;
        if (!empty($startTime) and !empty($endTime)) {
//            $where['o.createTime'] = ['between', [$startTime, $endTime]];
            $where['o.pay_time'] = ['between', [$startTime, $endTime]];
        }

        $orderList = $order_model->alias('o')
            ->join('left join wst_coupons wc on wc.couponId = o.couponId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->where($where)
            ->field($field)
//            ->order("o.createTime {$sort}")
            ->order("o.pay_time {$sort}")
            ->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$orderList)->setMsg('成功')->toArray();
    }

    /**
     * @param $shopIds
     * @return mixed
     * 获取未受理的订单
     */
    public function getToBeAcceptedOrdersList($shopIds)
    {
        $order_model = new OrdersModel();
        $where = [];
        $where['o.orderFlag'] = 1;
        $where['o.orderStatus'] = 0;
        $where['o.isRefund'] = 0;
        $where['o.isPay'] = 1;
        $where['o.shopId'] = ['IN', $shopIds];

        $field = "o.orderId,o.orderNo,o.shopId,o.isSelf,o.orderType,o.deliverType,o.createTime,o.requireTime,o.payFrom,";
        $field .= " o.realTotalMoney,o.deliverMoney,wc.couponMoney,o.totalMoney, ";
        $field .= " o.userName,o.userAddress,o.userPhone,o.totalMoney,o.orderRemarks,o.requireTime ";
        $orderList = $order_model->alias('o')
            ->join('left join wst_coupons wc on wc.couponId = o.couponId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->where($where)
            ->field($field)
            ->order("o.createTime asc")
            ->select();
        $time = time();
        $shop_module = new ShopsModule();
        foreach ($orderList as $key => $val) {
            if (!empty($val['requireTime'])) {
                $shop_config = $shop_module->getShopConfig($val['shopId'], 'advance_print_time', 2);
                $advance_print_time = (int)$shop_config['advance_print_time'];
                $requireTime = strtotime($val['requireTime']) - ($advance_print_time * 60);//提前受理订单
                if ($requireTime > $time) {
                    unset($orderList[$key]);
                }
            }
        }
        $orderList = array_values($orderList);
        if (!empty($orderList)) {
            //$orderGoodsField = "og.goodsName,og.goodsNums,(og.goodsNums * og.goodsPrice) as goodsPrice";
            $orderGoodsField = "og.goodsName,og.goodsId,og.orderId,og.skuId,og.skuSpecAttr,og.remarks,og.goodsNums,(og.goodsNums * og.goodsPrice) as goodsPrice";
            foreach ($orderList as $k => $v) {
                $getOrderGoodsList = $this->getOrderGoodsList($v['orderId'], $orderGoodsField);
                $orderList[$k]['orderGoodsList'] = $getOrderGoodsList['data'];
                $orderList[$k]['orderTypeName'] = $this->getOrderTypeName($v['orderType']);

                $orderList[$k]['deliverTypeName'] = $this->getDeliverTypeName($v['deliverType']);
                // 如果是自提 显示为 【门店自提】
                if ($v['isSelf'] == 1) {
                    $orderList[$k]['deliverTypeName'] = "门店自提";
                }

            }
        }
        return $orderList;
    }

    /**
     * @param $params
     * @return bool|mixed
     * 定时受理订单、打印小票
     */
    public function shopOrderAccept($params)
    {
        $shopId = $params['shopId'];
        $orderId = $params['orderId'];
        $ordersModel = new \Home\Model\OrdersModel();
        $orderModel = D('Home/Orders');
        $orderTab = M('orders');
        $where = [];
        $where['orderId'] = $orderId;
        $where['shopId'] = $shopId;
        $orderInfo = $orderTab->where($where)->lock(true)->find();
        //受理订单-start
        M()->startTrans();
        $where = [];
        $where['orderId'] = $orderId;
        $saveData = [];
        $saveData['orderStatus'] = 1;
        $shop_service_module = new ShopsServiceModule();
        $shop_config_result = $shop_service_module->getShopConfig($shopId);
        $shop_config_data = $shop_config_result['data'];
        if ($shop_config_data['open_suspension_chain'] == 1) {
            //如果开启了悬挂链将上报状态改为待上报
            $saveData['is_reporting'] = 0;
        }
        $updateRes = $orderTab->where($where)->save($saveData);
        if ($updateRes === false) {
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }
        $logId = [];
        $content = "系统已自动受理订单";
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 2;
        $logParams['orderStatus'] = 1;
        $logParams['payStatus'] = 1;
        $logId[] = $ordersModel->addOrderLog([], $logParams);
        //受理订单-start
        //打包订单-start
        $produceRes = $ordersModel->shopOrderProduce($params, $orderId, M());
        if ($produceRes['code'] != 0) {
            $ordersModel->delOrderLog($logId);
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }

        $content = "订单打包中";
        $logParams = [];
        $logParams['orderId'] = $orderId;
        $logParams['content'] = $content;
        $logParams['logType'] = 2;
        $logParams['orderStatus'] = 2;
        $logParams['payStatus'] = 1;
        $logId[] = $ordersModel->addOrderLog([], $logParams);
        //打包订单-end
        //发布订单-start
        $where = [];
        $where['orderId'] = $orderInfo['orderId'];
        $orderInfo = $orderTab->where($where)->find();
        if ($orderInfo['isSelf'] == 1) {
            //自提
//            $where = [];
//            $where['orderId'] = $orderId;
//            $save = [];
//            $save['orderStatus'] = 3;
//            $updateOrderRes = $orderTab->where($where)->save($save);
//            if ($updateOrderRes === false) {
//                $ordersModel->delOrderLog($logId);
//                M()->rollback();
//                return returnData(false, -1, 'error', '变更订单状态失败');
//            } else {
//                //写入订单日志
//                $content = "待取货";
//                $logParams = [];
//                $logParams['orderId'] = $orderId;
//                $logParams['content'] = $content;
//                $logParams['logType'] = 2;
//                $logParams['orderStatus'] = 3;
//                $logParams['payStatus'] = 1;
//                $logId[] = D('Home/Orders')->addOrderLog([], $logParams);
//            }
        }
        //自建司机配送
        if ($orderInfo['deliverType'] == 6 and $orderInfo["isSelf"] == 0) {
            $funResData = $orderModel::dirverQueryDeliverFee([], $orderId);
            if ($funResData['code'] != 0) {
                $ordersModel->delOrderLog($logId, $orderId);
                M()->rollback();
                return returnData(false, -1, 'error', '自建司机配送操作失败');
            }
        }
        //开始打印小票========start===================
        //获取店铺信息
        $getShopsInfoById_data = $shop_service_module->getShopsInfoById($shopId, '*')['data'];


        if (!empty($params['printsInfo'])) {//判断是否存在打印机
            $deviceNo = $params['printsInfo']['equipmentNumber'];//打印机编号
            $key = $params['printsInfo']['secretKey'];//打印密钥
            $times = $params['printsInfo']['number'];//打印联数

            //查询打印机状态
            $getPrintsStatus = getPrintsStatus($deviceNo, $key);
            if (empty($getPrintsStatus)) {
                M()->rollback();
                return returnData(false, -1, 'error', '请检查打印机配置');
            }
            //循环开始打印
            for ($i = 0; $i < ((int)$times); $i++) {
                if ($times > 1 and $i == 0) {
                    $printContent = getPrintsOrdersTemplate($params, "存根联", $getShopsInfoById_data['shopName']);//打印内容
                } else {
                    $printContent = getPrintsOrdersTemplate($params, "顾客联", $getShopsInfoById_data['shopName']);//打印内容

                }
                $getPrintsOrders = getPrintsOrders($deviceNo, $key, $printContent, 1);
                if (empty($getPrintsOrders)) {
                    M()->rollback();
                    return returnData(false, -1, 'error', '请检查打印机配置');
                }
            }

            //查询打印状态
            $orderIndex = $getPrintsOrders['orderindex'];
            $getPrintsOrdersStatus = getPrintsOrdersStatus($deviceNo, $key, $orderIndex);
            if (empty($getPrintsOrdersStatus)) {
                M()->rollback();
                return returnData(false, -1, 'error', '请检查打印机配置');
            }
        }
        //打印小票结束==========end====================
        M()->commit();
//        非常订单添加到商品采购单
        if (!empty($orderId)) {
            $orderDate = implode(',', [$orderId]);
            $ordersModel->addPurchaseGoods($orderDate);
        }
        return returnData(true);
    }

    /**
     * 增加订单商品销量
     * @param int $order_id 订单id
     * @param object $trans
     * @return bool
     * */
    public function IncOrderGoodsSale($order_id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $order_goods_result = $this->getOrderGoodsListById($order_id);
        if ($order_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return false;
        }
        $order_goods_list = $order_goods_result['data'];
        $goods_module = new GoodsModule();
        foreach ($order_goods_list as $item) {
            $goods_id = (int)$item['goodsId'];
            $sku_id = (int)$item['skuId'];
            $goods_type = (int)$item['goods_type'];
            $goods_cnt = (float)$item['goodsNums'];
            $save_res = $goods_module->IncGoodsSale($goods_id, $sku_id, $goods_cnt, $goods_type, $m);
            if (!$save_res) {
                $m->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 获取订单生成id
     * @return int
     * */
    public function getOrderAutoId()
    {
        $orderids_model = new OrderidsModel();
        $outo_id = $orderids_model->add(array(
            'rnd' => microtime(true)
        ));
        return $outo_id;
    }

    /**
     * 保存订单信息
     * @param array $params <p>
     *  .....订单表的所有字段,太多了,省略
     * </p>
     * @param object $trans
     * @return int $order_id
     * */
    public function saveOrdersDetail(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'orderNo' => null,
            'areaId1' => null,
            'areaId2' => null,
            'areaId3' => null,
            'shopId' => null,
            'orderStatus' => null,
            'totalMoney' => null,
            'deliverMoney' => null,
            'payType' => null,
            'payFrom' => null,
            'isSelf' => null,
            'isPay' => null,
            'deliverType' => null,
            'userId' => null,
            'userName' => null,
            'communityId' => null,
            'userAddress' => null,
            'userPhone' => null,
            'userPostCode' => null,
            'orderScore' => null,
            'isInvoice' => null,
            'invoiceClient' => null,
            'orderRemarks' => null,
            'requireTime' => null,
            'isAppraises' => null,
            'isClosed' => null,
            'isRefund' => null,
            'refundRemark' => null,
            'orderunique' => null,
            'orderSrc' => null,
            'orderFlag' => null,
            'needPay' => null,
            'tradeNo' => null,
            'settlementId' => null,
            'realTotalMoney' => null,
            'poundageRate' => null,
            'poundageMoney' => null,
            'useScore' => null,
            'scoreMoney' => null,
            'receiveTime' => null,
            'deliveryTime' => null,
            'DadaOutOrderFreight' => null,
            'deliveryNo' => null,
            'distance' => null,
            'dmName' => null,
            'dmMobile' => null,
            'updateTime' => null,
            'dmId' => null,
            'PreSalePay' => null,
            'PreSalePayPercen' => null,
            'defPreSale' => null,
            'couponId' => null,
            'basketId' => null,
            'orderToken' => null,
            'setDeliveryMoney' => null,
            'singleOrderToken' => null,
            'lat' => null,
            'lng' => null,
            'userDelete' => null,
            'orderType' => null,
            'shopCancellationReason' => null,
            'receiptId' => null,
            'driverId' => null,
            'taskType' => null,
            'isReceivables' => null,
            'taskId' => null,
            'receivablesAmount' => null,
            'regionId' => null,
            'distributionSort' => null,
            'isPurchase' => null,
            'pay_time' => null,
            'chain_id' => null,
            'is_reporting' => null,
            'delivery_coupon_id' => null,
            'delivery_coupon_money' => null,
            'coupon_money' => null,
            'delivery_coupon_use_money' => null,
            'coupon_use_money' => null,
            'lineId' => null,
            'customerRankId' => null,
            'printNum' => null,
            'customerSettlementId' => null,
            'customerSettlementDate' => null,
            'receivableAmount' => null,
            'receivedAmount' => null,
            'paidInAmount' => null,
            'zeroAmount' => null,
            'uncollectedAmount' => null,
            'returnStatus' => null,
            'diffAmount' => null,
            'diffAmountTime' => null,
            'returnAmount' => null,
            'customerSettlementRemark' => null,
            'customerSettlementPic' => null,
        );
        parm_filter($save, $params);
        $model = new OrdersModel();
        if (empty($params['orderId'])) {
            $save['createTime'] = date('Y-m-d H:i:s');
            $save_res = $model->add($save);
        }
        if (!empty($params['orderId'])) {
            $save_res = $model->where(array(
                'orderId' => $params['orderId']
            ))->save($save);
        }
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($trans)) {
            $m->commit();
        }
        if (empty($params['orderId'])) {
            $order_id = $save_res;
        } else {
            $order_id = $params['orderId'];
        }
        return (int)$order_id;
    }

    /**
     * 生成订单提货码
     * @param int $order_id
     * @param object $trans
     * @return string
     * */
    public function createBootstrapCode(int $order_id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $code = '';//自提码
        $field = 'orderId,isSelf,userId,shopId';
        $order_detail = $this->getOrderInfoById($order_id, $field, 2);
        if (empty($order_detail) || $order_detail['isSelf'] != 1) {
            $m->rollback();
            return $code;
        }
        $users_id = $order_detail['userId'];
        $shop_id = $order_detail['shopId'];
        $save = array(
            'orderId' => $order_id,
            'source' => $order_id . $users_id . $shop_id,
            'userId' => $users_id,
            'shopId' => $shop_id
        );
        $model = new UserSelfGoodsModel();
        $save_res = $model->add($save);
        if (!$save_res) {
            $model->rollback();
            return '';
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $save['source'];
    }

    /**
     * 订单商品分摊优惠券金额
     * @param int $order_id 订单id
     * @param int goods_id 商品id
     * @param float goods_price_total 当前商品金额小计
     * @param float total_money 订单商品金额
     * @return float
     * */
    public function goodsShareCouponAmount(int $order_id, int $goods_id, $goods_price_total, $total_money)
    {
        $share_money = 0;
        if (empty($order_id) || empty($goods_id) || empty($goods_price_total) || empty($total_money)) {
            return $share_money;
        }
        $order_module = new OrdersModule();
        $field = 'orderId,couponId,coupon_use_money';
        $order_detail = $order_module->getOrderInfoById($order_id, $field, 2);

        //公式:优惠券金额[A] * (商品价格/订单商品金额)[B] - 商品价格[C]
        $A = (float)$order_detail['coupon_use_money'];
        $B = (float)bc_math($goods_price_total, $total_money, 'bcdiv');
        $C = $goods_price_total;
        $goods_coupon_discount = (float)bc_math(bc_math($A, $B, 'bcmul'), $C, 'bcsub');
        $curr_coupon_money = (float)bc_math($goods_coupon_discount, $goods_price_total, 'bcadd');
        if ($curr_coupon_money > 0) {
            //精度损失问题由用户来承担
            $coupon_money_arr = explode('.', $curr_coupon_money);
            if (!empty($coupon_money_arr[1])) {
                $xiaoshu = $coupon_money_arr[1];
                if (strlen($xiaoshu) > 2) {
                    $xiaoshu = (float)bc_math(substr($xiaoshu, 0, 2), 1, 'bcadd') / 100;
                    $curr_coupon_money = $coupon_money_arr[0] + $xiaoshu;
                }
            }
        }
        $share_money = $curr_coupon_money;
        $order_goods_list = $order_module->getOrderGoodsList($order_id, 'og.*', 2);
        if (count($order_goods_list) > 0) {//上面的历史代码不动了,这里只是增加段逻辑,避免商品使用的优惠券金额总计大于优惠券的金额
            $used_coupon_amount = 0;
            foreach ($order_goods_list as $order_goods_row) {
                $used_coupon_amount = bc_math($used_coupon_amount, $order_goods_row['couponMoney'], 'bcadd', 2);
            }
            $curr_used_coupon_amount = bc_math($used_coupon_amount, $share_money, 'bcadd', 2);
            if ((float)$curr_used_coupon_amount > (float)$order_detail['coupon_use_money']) {
                $share_money = bc_math($order_detail['coupon_use_money'], $used_coupon_amount, 'bcsub', 2);
            }
        }
        return (float)$share_money;
    }

    /**
     * 订单商品分摊积分抵扣金额
     * @param int order_use_score 当前订单使用的总积分
     * @param int goods_id 商品id
     * @param float goods_price_total 当前商品金额小计
     * @return float
     * */
    public function goodsShareScoreAmount(int &$order_use_score, int $goods_id, $goods_price_total)
    {
        $share_money = 0;
        if (empty($order_use_score) || empty($goods_id) || empty($goods_price_total)) {
            return $share_money;
        }
        if ($order_use_score <= 0) {
            return $share_money;
        }
        $configs = $GLOBALS['CONFIG'];
        $score_cash_ratio = $configs['scoreCashRatio'];
        $score_cash_ratio_arr = explode(':', $score_cash_ratio);
        $score_cash_ratio0 = 0;
        $score_cash_ratio1 = 0;
        if (count($score_cash_ratio_arr) == 2) {
            $score_cash_ratio0 = (float)$score_cash_ratio_arr[0];
            $score_cash_ratio1 = (float)$score_cash_ratio_arr[1];
        }
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsName,integralRate';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        $goods_integral_rate = (float)$goods_detail['integralRate'];
        //获取比例
        $discount_price = $goods_price_total;
        $goods_score = (float)$discount_price / (float)$score_cash_ratio1 * (float)$score_cash_ratio0;
        $integral_rate_score = (int)($goods_score * ($goods_integral_rate / 100));//当前商品可以抵扣的积分
        if ($integral_rate_score >= $order_use_score) {
            $integral_rate_score = $order_use_score;
        }
        $share_money = (int)$integral_rate_score / (float)$score_cash_ratio0 * (float)$score_cash_ratio1;
        $order_use_score = (int)bc_math($order_use_score, $integral_rate_score, 'bcsub', 0);//剩余可分配积分
        if ($order_use_score < 0) {
            $order_use_score = 0;
        }
        return (float)$share_money;
    }

    /**
     * 保存订单商品明细
     * @param array $params <p>
     * ...订单商品明细表字段
     * </p>
     * @param object $trans
     * @return int
     * */
    public function saveOrderGoods(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $info = array(
            'orderId' => null,
            'goodsId' => null,
            'goodsNums' => null,
            'goodsPrice' => null,
            'goodsAttrId' => null,
            'goodsAttrName' => null,
            'skuId' => null,
            'skuSpecAttr' => null,
            'goodsName' => null,
            'goodsThums' => null,
            'weight' => null,
            'remarks' => null,
            'actionStatus' => null,
            'sortingNum' => null,
            'nuclearCargoNum' => null,
            'couponMoney' => null,
            'scoreMoney' => null,
            'hook_id' => null,
            'bind_hook_date' => null,
            'goods_type' => null,
            'goodsScore' => null,
            'is_weight' => null,
            'purchase_type' => null,
            'purchaser_or_supplier_id' => null,
            'goodsImg' => null,
            'unitName' => null,
            'goodsCode' => null,
            'skuSpecStr' => null,
            'goodsSpec' => null,
            'reportedOverflow' => null,
        );
        parm_filter($info, $params);
        $model = new OrderGoodsModel();
        if (empty($params['id'])) {
            $save_res = $model->add($info);
            $id = $save_res;
        } else {
            $save_res = $model->where(array(
                'id' => $params['id']
            ))->save($info);
            $id = $params['id'];
        }
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$id;
    }

    /**
     * 限制商品下单次数
     * @param int $goods_id 商品id
     * @param int $user_id 用户id
     * @param object $trans
     * @return bool
     * */
    public function limitGoodsOrderNum(int $goods_id, $user_id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($goods_id) || empty($user_id)) {
            $m->rollback();
            return false;
        }
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsName,orderNumLimit,isShopSecKill';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        if ($goods_detail['orderNumLimit'] > 0 && $goods_detail['isShopSecKill'] != 1) {
            $model = new GoodsOrderNumLimitModel();
            $where = array(
                'userId' => $user_id,
                'goodsId' => $goods_id,
            );
            $exists_detail = $model->where($where)->find();
            if (empty($exists_detail)) {
                //insert
                $limit_log = array();
                $limit_log['userId'] = $user_id;
                $limit_log['goodsId'] = $goods_id;
                $limit_log['buyNum'] = 1;
                $limit_log['orderNum'] = $goods_detail["orderNumLimit"];
                $limit_log['state'] = -1;
                $limit_log['logTime'] = date("Y-m-d H:i:s", time());
                $save_res = $model->add($limit_log);
            } else {
                //edit
                $log_edit = array();
                $log_edit['buyNum'] = $exists_detail['buyNum'] + 1;
                $log_edit['logTime'] = date("Y-m-d H:i:s", time());
                $log_edit['state'] = -1;
                $save_res = $model->where(['id' => $exists_detail['id']])->save($log_edit);
            }
            if ($save_res === false) {
                $m->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 秒杀商品限量记录
     * @param int $order_id 订单id
     * @param int $goods_id 商品id
     * @param float $goods_cnt 商品数量
     * @param object $trans
     * @return bool
     * */
    public function addGoodsSecondskilllimit(int $order_id, int $goods_id, float $goods_cnt, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($order_id) || empty($goods_id) || empty($goods_cnt)) {
            $m->rollback();
            return false;
        }
        $goods_module = new GoodsModule();
        $field = 'goodsId,goodsName,isShopSecKill,ShopGoodSecKillEndTime';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        if ($goods_detail['isShopSecKill'] != 1) {
            $m->rollback();
            return false;
        }
        $field = 'orderId,userId';
        $order_detail = $this->getOrderInfoById($order_id, $field, 2);
        $user_id = $order_detail['userId'];
        $model = new GoodsSecondsKillLimitModel();
        for ($kii_i = 0; $kii_i < $goods_cnt; $kii_i++) {
            //一件商品一条数据
            $kill_data['goodsId'] = $goods_id;
            $kill_data['userId'] = $user_id;
            $kill_data['endTime'] = $goods_detail["ShopGoodSecKillEndTime"];
            $kill_data['orderId'] = $order_id;
            $kill_data['state'] = 1;
            $kill_data['addtime'] = date('Y-m-d H:i:s');
            $res = $model->add($kill_data);
            if (!$res) {
                $model->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 建立订单提醒记录
     * @param int $order_id 订单id
     * @param object $trans
     * @return bool
     * */
    public function addOrderRemind(int $order_id, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $order_module = new OrdersModule();
        $field = 'orderId,shopId,userId';
        $order_detail = $order_module->getOrderInfoById($order_id, $field, 2);
        if (empty($order_detail)) {
            $m->rollback();
            return false;
        }
        $save = array(
            'orderId' => $order_detail['orderId'],
            'shopId' => $order_detail['shopId'],
            'userId' => $order_detail['userId'],
            'createTime' => date('Y-m-d H:i:s')
        );
        $model = new OrderRemindsModel();
        $save_res = $model->add($save);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 获取订单商品列表对应的店铺信息
     * @param $goods_list 商品列表
     * @return array $shop_list
     * */
    public function getAllOrderShopList(array $goods_list)
    {
        $shop_list = array();
        $shop_id_arr = array_unique(array_column($goods_list, 'shopId'));
        if (!empty($shop_id_arr)) {
            $shop_model = new ShopsModel();
            $shop_list = $shop_model->where(array(
                'shopId' => array('in', $shop_id_arr)
            ))->select();
        }
        return (array)$shop_list;
    }

    /**
     * 校验限制商品下单次数
     * @param int $goods_id 商品id
     * @param int $users_id 用户id
     * @return array
     * */
    public function verificationGoodsOrderNum(int $goods_id, int $users_id)
    {
        //该方法中禁止使用事务!!!
        if (empty($goods_id) || empty($users_id)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '限制商品下单次数校验不通过');
        }
        $goods_module = new GoodsModule();
        $field = "goodsId,orderNumLimit,goodsName,isShopSecKill";
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        if ($goods_detail['orderNumLimit'] == -1) {
            return returnData(true);
        }
        if ($goods_detail['isShopSecKill'] == 1) {
            return returnData(true);
        }
        $limit = $goods_detail['orderNumLimit'];
        if ($limit == 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '限制下单次数为' . $limit);
        }
        $goods_ordernum_limti_detail = $this->getGoodsOrdernumLimit($users_id, $goods_id);
        if (empty($goods_ordernum_limti_detail)) {
            return returnData(true);
        }
        $limit_model = new GoodsOrderNumLimitModel();
        $limit_model->where(array(
            'id' => $goods_ordernum_limti_detail['id']
        ))->save(array(
            'orderNum' => $limit
        ));//如果存在记录,需要更新orderNumLimit,避免后台修改orderNumLimit的值
        $goods_ordernum_limti_detail = $this->getGoodsOrdernumLimit($users_id, $goods_id);//重新获取一遍
        if ($goods_ordernum_limti_detail['buyNum'] >= $goods_ordernum_limti_detail['orderNum']) {
            $diff_num = (int)bc_math($goods_ordernum_limti_detail['orderNum'], $goods_ordernum_limti_detail['buyNum'], 'bcsub', 0);
            if ($diff_num <= 0) {
                $diff_num = 0;
            }
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '限制下单次数为' . $limit . ",剩余下单次数为" . $diff_num);
        }
        return returnData(true);
    }

    /**
     * 获取用户商品下单记录-仅针对设置了限制下单次数的商品
     * @param int $users_id
     * @param int $goods_id
     * @return array
     * */
    public function getGoodsOrdernumLimit(int $users_id, int $goods_id)
    {
        $where = array(
            'userId' => $users_id,
            'goodsId' => $goods_id,
        );
        $model = new GoodsOrderNumLimitModel();
        $detail = $model->where($where)->find();
        return (array)$detail;
    }

    /**
     * 校验用户收货地址是否在店铺配送范围
     * @param int $users_id 用户id
     * @param int $address_id 收回地址id
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function verificationShopDistribution(int $users_id, int $address_id, int $shop_id)
    {
        if (empty($address_id) || empty($shop_id) || empty($users_id)) {
            return false;
        }
        $users_module = new UsersModule();
        $shop_module = new ShopsModule();
        $address_detail = $users_module->getUserAddressDetail($users_id, $address_id);
        if (empty($address_detail)) {
            return false;
        }
        $lat = $address_detail['lat'];
        $lng = $address_detail['lng'];
        $field = 'shopId,deliveryLatLng';
        $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
        if (empty($shop_detail['deliveryLatLng'])) {
            return false;
        }
        $delivery_latlng = htmlspecialchars_decode($shop_detail['deliveryLatLng']);
        $pts = json_decode($delivery_latlng, true);
        foreach ($pts as $data) {
            if (!empty($data['M'])) {
                $lng_M = $data['M'];
            } else {
                $lng_M = $data['lng'];
            }
            if (!empty($data['O'])) {
                $lat_O = $data['O'];
            } else {
                $lat_O = $data['lat'];
            }
            $lnglat_arr[] = array('lng' => $lng_M, 'lat' => $lat_O);
        }
        if (!$pts || !is_array($pts)) {
            return false;
        }
//        $arr = bd_decrypt($lng, $lat);
//        $lng = $arr['gg_lon'];
//        $lat = $arr['gg_lat'];
        $point = [
            'lng' => $lng,
            'lat' => $lat,
        ];
        return (bool)is_point_in_polygon($point, $lnglat_arr);
    }

    /**
     * 校验用户收货地址是否在店铺配送范围
     * @param array $users_row 用户id
     * @param array $address_row 收回地址id
     * @param array $shop_row 门店id
     * @return bool
     * */
    public function verificationShopDistributionNew(array $users_row, array $address_row, array $shop_row)
    {
        if (empty($users_row) || empty($address_row) || empty($shop_row)) {
            return false;
        }
//        $users_module = new UsersModule();
//        $shop_module = new ShopsModule();
//        $address_detail = $users_module->getUserAddressDetail($users_id, $address_id);
        $address_detail = $address_row;
        if (empty($address_detail)) {
            return false;
        }
        $lat = $address_detail['lat'];
        $lng = $address_detail['lng'];
//        $field = 'shopId,deliveryLatLng';
//        $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
        $shop_detail = $shop_row;
        if (empty($shop_detail['deliveryLatLng'])) {
            return false;
        }
        $delivery_latlng = htmlspecialchars_decode($shop_detail['deliveryLatLng']);
        $pts = json_decode($delivery_latlng, true);
        foreach ($pts as $data) {
            if (!empty($data['M'])) {
                $lng_M = $data['M'];
            } else {
                $lng_M = $data['lng'];
            }
            if (!empty($data['O'])) {
                $lat_O = $data['O'];
            } else {
                $lat_O = $data['lat'];
            }
            $lnglat_arr[] = array('lng' => $lng_M, 'lat' => $lat_O);
        }
        if (!$pts || !is_array($pts)) {
            return false;
        }
//        $arr = bd_decrypt($lng, $lat);
//        $lng = $arr['gg_lon'];
//        $lat = $arr['gg_lat'];
        $point = [
            'lng' => $lng,
            'lat' => $lat,
        ];
        return (bool)is_point_in_polygon($point, $lnglat_arr);
    }

    /**
     * 获取订单可用优惠券列表
     * @param int $users_id 用户id
     * @param int $shop_id 门店id
     * @param data_type 类型(1:普通优惠券 2:运费券)
     * @return array
     */
    public function get_order_can_use_coupons(int $users_id, int $shop_id, $data_type = 1)
    {
        $cart_module = new CartModule();
        $goods_module = new GoodsModule();
        $buyNowGoodsId = (int)I("buyNowGoodsId");//立即购买-商品id 注：仅用于立即购买
        $buyNowSkuId = (int)I("buyNowSkuId");//立即购买-skuId 注：仅用于立即购买
        $buyNowGoodsCnt = (float)I("buyNowGoodsCnt");//立即购买-数量
        if (!empty($buyNowGoodsId)) {
            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkuId, $buyNowGoodsCnt);
        } else {
            $cart_goods = $cart_module->getCartGoodsChecked($users_id, $shop_id);
        }
        $goods_list = $cart_goods['goods_list'];//购物车门店商品数据
        $result = $cart_module->handleCartGoodsSku($goods_list);
        if (empty($result)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请先选择要支付的商品');
        }
        $coupon_module = new CouponsModule();
        $user_coupon_list = $coupon_module->getUserNotExpiredCouponList($users_id);//获取用户已领取的优惠券(未使用未过期)
        if (empty($user_coupon_list)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用的优惠券');
        }
        $can_use_coupons = array();//最终可用优惠券列表
        foreach ($user_coupon_list as $key => $coupon_detail) {
            if ($data_type == 1) {
                if ($coupon_detail['couponType'] == 8) {
                    $coupon_detail = array();
                    unset($user_coupon_list[$key]);
                }
            } elseif ($data_type == 2) {//如果是运费券则去除非运费券数据
                if ($coupon_detail['couponType'] != 8) {
                    $coupon_detail = array();
                    unset($user_coupon_list[$key]);
                }
            }
            if (empty($coupon_detail)) {
                continue;
            }
            $coupon_detail['selfShopId'] = $coupon_detail['shopId'];//以前就有的变量,不清楚干什么的,留着吧
            $coupon_detail['userCouponId'] = $coupon_detail['id'];//用户领取的优惠券记录id
            $can_use_coupons[] = $coupon_detail;
        }
        $sort = array();
        foreach ($can_use_coupons as $coupon_detail) {
            $sort[] = $coupon_detail['couponMoney'];
        }
        array_multisort($sort, SORT_DESC, SORT_NUMERIC, $can_use_coupons);//优惠券面额从大到小排序
        //过滤掉不满足条件的优惠券-start
        $split_order_res = $goods_module->getGoodsSplitOrder($goods_list);//判断当前订单是否允许使用优惠券
        $all_order_can_coupons = $split_order_res['can_use_coupon'];//拆单后的订单是否存在可以使用优惠券的订单(0:不存在 1:存在)
        if ($all_order_can_coupons != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用优惠券');
        }
        //以下优惠券只要满足其中一笔订单即可使用
        foreach ($can_use_coupons as $key => $coupon_detail) {
            $user_coupon_id = $coupon_detail['userCouponId'];
            $current_coupon_can_use_data = $goods_module->getGoodsSplitOrder($goods_list, $user_coupon_id);//当前优惠券可用状况,即判断当前优惠券是否可以用于这些拆单上
            $is_can_use_current_coupon = $current_coupon_can_use_data['can_use_coupon'];//当前优惠券是否可用于这些拆单上
            if ($is_can_use_current_coupon != 1) {
                unset($can_use_coupons[$key]);
            }
        }
        $can_use_coupons = array_values($can_use_coupons);
        if (empty($can_use_coupons)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用优惠券');
        }
        return returnData($can_use_coupons);
    }

    /**
     * 获取订单可用优惠券列表
     * @param int $users_id 用户id
     * @param int $shop_id 门店id
     * @param data_type 类型(1:普通优惠券 2:运费券)
     * @return array
     */
    public function get_order_can_use_coupons_new(int $users_id, int $shop_id, $goods_list, $result, $data_type = 1)
    {
//        $cart_module = new CartModule();
        $goods_module = new GoodsModule();
//        $buyNowGoodsId = (int)I("buyNowGoodsId");//立即购买-商品id 注：仅用于立即购买
//        $buyNowSkuId = (int)I("buyNowSkuId");//立即购买-skuId 注：仅用于立即购买
//        $buyNowGoodsCnt = (float)I("buyNowGoodsCnt");//立即购买-数量
//        if (!empty($buyNowGoodsId)) {
//            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkuId, $buyNowGoodsCnt);
//        } else {
//            $cart_goods = $cart_module->getCartGoodsChecked($users_id, $shop_id);
//        }
//        $goods_list = $cart_goods['goods_list'];//购物车门店商品数据
//        $result = $cart_module->handleCartGoodsSku($goods_list);
        if (empty($result)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请先选择要支付的商品');
        }
        $coupon_module = new CouponsModule();
        $user_coupon_list = $coupon_module->getUserNotExpiredCouponList($users_id);//获取用户已领取的优惠券(未使用未过期)
        if (empty($user_coupon_list)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用的优惠券');
        }
        $can_use_coupons = array();//最终可用优惠券列表
        foreach ($user_coupon_list as $key => $coupon_detail) {
            if ($data_type == 1) {
                if ($coupon_detail['couponType'] == 8) {
                    $coupon_detail = array();
                    unset($user_coupon_list[$key]);
                }
            } elseif ($data_type == 2) {//如果是运费券则去除非运费券数据
                if ($coupon_detail['couponType'] != 8) {
                    $coupon_detail = array();
                    unset($user_coupon_list[$key]);
                }
            }
            if (empty($coupon_detail)) {
                continue;
            }
            $coupon_detail['selfShopId'] = $coupon_detail['shopId'];//以前就有的变量,不清楚干什么的,留着吧
            $coupon_detail['userCouponId'] = $coupon_detail['id'];//用户领取的优惠券记录id
            $can_use_coupons[] = $coupon_detail;
        }
        $sort = array();
        foreach ($can_use_coupons as $coupon_detail) {
            $sort[] = $coupon_detail['couponMoney'];
        }
        array_multisort($sort, SORT_DESC, SORT_NUMERIC, $can_use_coupons);//优惠券面额从大到小排序
        //过滤掉不满足条件的优惠券-start
        $split_order_res = $goods_module->getGoodsSplitOrder($goods_list);//判断当前订单是否允许使用优惠券
        $all_order_can_coupons = $split_order_res['can_use_coupon'];//拆单后的订单是否存在可以使用优惠券的订单(0:不存在 1:存在)
        if ($all_order_can_coupons != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用优惠券');
        }
        //以下优惠券只要满足其中一笔订单即可使用
        foreach ($can_use_coupons as $key => $coupon_detail) {
            $user_coupon_id = $coupon_detail['userCouponId'];
            $current_coupon_can_use_data = $goods_module->getGoodsSplitOrder($goods_list, $user_coupon_id);//当前优惠券可用状况,即判断当前优惠券是否可以用于这些拆单上
            $is_can_use_current_coupon = $current_coupon_can_use_data['can_use_coupon'];//当前优惠券是否可用于这些拆单上
            if ($is_can_use_current_coupon != 1) {
                unset($can_use_coupons[$key]);
            }
        }
        $can_use_coupons = array_values($can_use_coupons);
        if (empty($can_use_coupons)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无可用优惠券');
        }
        return returnData($can_use_coupons);
    }

    /**
     * 验证货到付款参数的有效性,这里不做过多的验证,提交订单中做校验
     * @param array $params 这里的参数不做过多解释,从提交订单中拆出来的方法,具体请看提交订单接口文档
     * 文档地址:https://www.yuque.com/docs/share/4a3be39d-da67-4759-b14e-813a4a179a75?#
     * @return array
     * */
    public function checkCashOnDeliveryParams(array $params)
    {
        $from_type = (int)$params['fromType'];
        $use_score = (int)$params['useScore'];
        $pay_from = (int)$params['payFrom'];
        if ($pay_from != 4) {//非货到付款就没必要往下走了
            return returnData(true);
        }
        $shop_module = new ShopsModule();
        $coupon_module = new CouponsModule();
        if ($from_type == 1) {
            //前置仓
            $shop_id = (int)$params['shopId'];
            $field = 'shopId,shopName';
            $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
            $shop_config = $shop_module->getShopConfig($shop_id, '*', 2);
            if (empty($shop_config)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_detail['shopName']}】配置信息有误");
            }
            $shop_name = $shop_detail['shopName'];
            if ($shop_config['cashOnDelivery'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】未开启货到付款功能");
            }
            $cuid = (int)$params['cuid'];//用户领取的优惠券记录id
            if (!empty($cuid)) {
                if ($shop_config['cashOnDeliveryCoupon'] != 1) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】货到付款不支持使用优惠券");
                }
                $user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
                $coupon_id = $user_coupon_detail['couponId'];
                $coupon_detail = $coupon_module->getCouponDetailById($coupon_id);
                if (empty($coupon_detail)) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "优惠券信息有误");
                }
                if ($coupon_detail['couponType'] == 5 && $shop_config['cashOnDeliveryMemberCoupon'] != 1) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】货到付款不支持使用会员券");
                }
            }
            if ($use_score == 1 && $shop_config['cashOnDeliveryScore'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】货到付款不支持使用积分");
            }
        }

        if ($from_type == 2) {
            //多商户
            $shop_param = (array)json_decode($params['shopParam'], true);
            foreach ($shop_param as $key => $value) {
                $shop_id = (int)$value['shopId'];
                $field = 'shopId,shopName';
                $shop_detail = $shop_module->getShopsInfoById($shop_id, $field, 2);
                $shop_config = $shop_module->getShopConfig($shop_id, '*', 2);
                $shop_name = $shop_detail['shopName'];
                if (empty($shop_config)) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】配置信息有误");
                }
                if ($shop_config['cashOnDelivery'] != 1) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】未开启货到付款功能");
                }
                $cuid = (int)$value['cuid'];
                if (!empty($cuid)) {
                    if ($shop_config['cashOnDeliveryCoupon'] != 1) {
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】货到付款不支持使用优惠券");
                    }
                    $user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
                    $coupon_id = $user_coupon_detail['couponId'];
                    $coupon_detail = $coupon_module->getCouponDetailById($coupon_id);
                    if (empty($coupon_detail)) {
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "优惠券信息有误");
                    }
                    if ($coupon_detail['couponType'] == 5 && $shop_config['cashOnDeliveryMemberCoupon'] != 1) {
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺【{$shop_name}】货到付款不支持使用会员券");
                    }
                }
                if ($use_score == 1 && $shop_config['cashOnDeliveryScore'] != 1) {
                    return returnData(null, -1, 'error', "店铺【{$shop_name}】货到付款不支持使用积分");
                }
            }
        }
        return returnData(true);
    }

    /**
     * 提交订单验证商品的有效性
     * @param array $goods_list 将要支付的商品数据
     * @param int $users_id 用户id
     * @param int $address_id 守护地址id
     * @param int $is_self 是否自提【0：不自提|1：自提】
     * @return array
     * */
    public function verificationGoodsStatus($goods_list = array(), $users_id = 0, $address_id = 0, $is_self = 0)
    {
        if (empty($goods_list) || empty($users_id)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '处理中，请稍等');
        }
        $goods_module = new GoodsModule();
        $shop_module = new ShopsModule();
        foreach ($goods_list as $key => $value) {
            $cart_goods_detail = $value;
            $goods_id = $cart_goods_detail['goodsId'];
            $sku_id = $cart_goods_detail['skuId'];
            $goods_name = $cart_goods_detail['goodsName'];
            $goods_cnt = (float)$cart_goods_detail['goodsCnt'];
            $shop_id = $cart_goods_detail['shopId'];
            $shop_detail = $shop_module->getShopsInfoById($shop_id, 'shopId,shopName', 2);
            $shop_name = $shop_detail['shopName'];
            $is_new_people_goods = $goods_module->isNewPeopleGoods($users_id, $goods_id);//判断是否是新人专享
            if ($is_new_people_goods) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . ' 是新人专享商品，您不能购买!');
            }
            $unit = $goods_module->getGoodsUnitByParams($goods_id, $sku_id);
            //校验商品是否属购买数量限制
            if ($cart_goods_detail['buyNum'] > 0 && $goods_cnt > (float)$cart_goods_detail['buyNum']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "单笔订单最多购买商品{$goods_name}{$cart_goods_detail['buyNum']}{$unit}");
            }
            if ($cart_goods_detail['buyNumLimit'] != -1 && $goods_cnt > (float)$cart_goods_detail['buyNumLimit']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "单笔订单最多购买商品{$goods_name}{$cart_goods_detail['buyNumLimit']}{$unit}");
            }
            if ($cart_goods_detail['isSale'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . '商品已下架');
            }
            if ($cart_goods_detail['goodsStatus'] == -1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . '商品已禁售');
            }
            if ($cart_goods_detail['goodsFlag'] == -1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . '商品不存在');
            }
            //校验是否为限制下单次数的商品-start
            $verification_goods_order_num_res = $this->verificationGoodsOrderNum($goods_id, $users_id);
            if ($verification_goods_order_num_res['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . $verification_goods_order_num_res['msg']);
            }
            //校验是否为限制下单次数的商品-end
            if ((float)$cart_goods_detail['minBuyNum'] > 0 && $goods_cnt < (float)$cart_goods_detail['minBuyNum']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . "最小起购量为{$cart_goods_detail['minBuyNum']}{$unit}");
            }
            if (!empty($sku_id)) {
                $sku_system_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                if ((float)$sku_system_detail['skuGoodsStock'] < $goods_cnt) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . '商品库存不足');
                }
            }
            //检验商品是否在配送范围
            if ($is_self != 1) {
                if (empty($address_id)) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择收货地址');
                }
                $distribution_res = $this->verificationShopDistribution($users_id, $address_id, $shop_id);
                if (!$distribution_res) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', $goods_name . "商品不在店铺{$shop_name}配送范围");
                }
            }
        }
        return returnData(true);
    }

    /**
     * 订单送达时间验证,送达时间必须大于下单时间
     * @param int $orderId
     * @return array
     * */
    public function verificationRequireTime(int $orderId)
    {
        $field = 'orderId,requireTime';
        $order_detail = $this->getOrderInfoById($orderId, $field, 2);
        if (empty($order_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单数据异常');
        }
        if (empty($order_detail['requireTime'])) {
            //该字段在接口使用中为非必填,所以为空就不验证了
            return returnData(true);
        }
        if ($order_detail['requireTime'] <= $order_detail['createTime']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单送达时间必须大于下单时间');
        }
        return returnData(true);
    }

    /**
     * 获取会员节省了多少钱
     * @param int $users_id 用户id
     * @return float
     * */
    public function preSubmitEconomyAmount($users_id = 0, $shopId)
    {
        $amount = 0;
        $user_module = new UsersModule();
        $field = 'userId,expireTime';
        $users_detail = $user_module->getUsersDetailById($users_id, $field, 2);
        if (empty($users_detail)) {
            return $amount;
        }
        if ($users_detail['expireTime'] < date('Y-m-d H:i:s')) {
            return $amount;
        }
        $total_member_price = 0;//会员总价
        $total_shop_price = 0;//店铺总价
        $cart_module = new CartModule();
        $cart_goods = $cart_module->getCartGoodsChecked($users_id, $shopId);
        $goods_list = $cart_goods['goods_list'];
        $shop_model = new ShopsModel();
        $shop_id_arr = array_unique(array_column($goods_list, 'shopId'));
        $shop_list = $shop_model->where(array(
            'shopFlag' => 1,
            'shopId' => array('IN', $shop_id_arr),
        ))->select();
        $sku_id_arr = [];
        $sku_list_map = [];
        foreach ($shop_list as $key => $value) {
            foreach ($goods_list as $goods_detail) {
                if ($value['shopId'] == $goods_detail['shopId']) {
                    $shop_list[$key]['goods_list'][] = $goods_detail;
                    if ($goods_detail['skuId'] > 0) {
                        $sku_id_arr[] = $goods_detail['skuId'];
                    }
                }
            }
        }
        if (count($sku_id_arr) > 0) {
            $systemTab = M('sku_goods_system');
            $sysWhere = [];
            $sysWhere['skuId'] = array('in', $sku_id_arr);
            $systemSpecList = $systemTab->where($sysWhere)->select();
            foreach ($systemSpecList as $systemSpecListRow) {
                if ($systemSpecListRow['skuId'] <= 0) {
                    continue;
                }
                $sku_list_map[$systemSpecListRow['skuId']] = $systemSpecListRow;
            }
        }
//        $goods_module = new GoodsModule();
        $user_discount = (float)bc_math($GLOBALS["CONFIG"]["userDiscount"], 100, 'bcdiv', 2);
        foreach ($shop_list as $key => $value) {
            foreach ($value['goods_list'] as $goods_detail) {
                $shop_price = (float)$goods_detail['shopPrice'];
                $sku_id = $goods_detail['skuId'];
                $member_price = $goods_detail['memberPrice'];
                //$sku_system_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                $sku_system_detail = $sku_list_map[$sku_id];
                if ((float)$sku_system_detail['skuMemberPrice'] > 0) {
                    $member_price = $sku_system_detail['skuMemberPrice'];
                }
                //如果设置了会员价，则使用会员价，否则使用会员折扣
                if ($member_price <= 0 && $user_discount > 0) {
                    $member_price = (float)bc_math($shop_price, $user_discount, 'bcmul', 2);
                }
                $total_member_price += $member_price;
                $total_shop_price += $shop_price;
            }
        }
        if ($total_member_price > 0) {
            $amount = abs(bc_math($total_member_price, $total_shop_price, 'bcsub', 2));
        }
        return formatAmount($amount);
    }

    /**
     * 保存订单合并支付记录
     * @param array $params <p>
     * string value 存储合并的订单[大A分割]
     * float realTotalMoney 实付金额
     * </p>
     * @param object $trans
     * @return bool
     * */
    public function addOrderMerge(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $model = new OrderMergeModel();
        $save_data = array(
            'value' => null,
            'realTotalMoney' => null,
            //'createTime' => date('Y-m-d H:i:s'),
            'createTime' => time(),
        );
        parm_filter($save_data, $params);
        if (!empty($save_data['value'])) {
            $save_data['orderToken'] = md5($save_data['value']);
        }
        $save_res = $model->add($save_data);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 更新订单的合并支付标识
     * @param array $order_no_arr 订单标识
     * @param string $order_token 合并订单标识
     * @param object $trans
     * @return bool
     * */
    public function updateOrderToken(array $order_id_arr, string $order_token, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($order_id_arr) || empty($order_token)) {
            $m->rollback();
            return false;
        }
        foreach ($order_id_arr as $order_no) {
            $field = 'orderId,orderNo,realTotalMoney';
            $order_detail = $this->getOrderDetailByParams(array(
                'orderNo' => $order_no
            ), $field);
            if (empty($order_detail)) {
                $m->rollback();
                return false;
            }
            if (count($order_id_arr) > 1) {
                $merge_data = array(
                    'value' => $order_no,
                    'realTotalMoney' => $order_detail['realTotalMoney'],
                );
                $merge_res = $this->addOrderMerge($merge_data, $m);
                if (!$merge_res) {
                    $m->rollback();
                    return false;
                }
            }
            $save_order_data = array(
                'orderId' => $order_detail['orderId'],
                'orderToken' => $order_token,
                'singleOrderToken' => md5($order_no),
            );
            $save_order_res = $this->saveOrdersDetail($save_order_data, $m);
            if (!$save_order_res) {
                $m->rollback();
                return false;
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 更新订单的setDeliveryMoney字段
     * @param array $order_no_arr 订单号
     * @param float $set_delivery_money 用于第三方支付重新支付补运费
     * @param object $trans
     * @return bool
     * */
    public function updateOrderSetDeliveryMoney(array $order_no_arr, float $set_delivery_money, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        if (empty($order_no_arr)) {
            $m->rollback();
            return false;
        }
        if (count($order_no_arr) > 1 && $set_delivery_money > 0) {
            foreach ($order_no_arr as $order_no) {
                $field = 'orderId,orderNo,realTotalMoney';
                $order_detail = $this->getOrderDetailByParams(array(
                    'orderNo' => $order_no
                ), $field);
                if (empty($order_detail)) {
                    $m->rollback();
                    return false;
                }
                $save_order_data = array(
                    'orderId' => $order_detail['orderId'],
                    'setDeliveryMoney' => $set_delivery_money
                );
                $save_order_res = $this->saveOrdersDetail($save_order_data, $m);
                if (!$save_order_res) {
                    $m->rollback();
                    return false;
                }
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 订单详情-多参数
     * @param array $params <p>
     * int orderId 订单id
     * string orderNo 订单号
     * </p>
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderDetailByParams(array $params, $field = '*')
    {
        $order_model = new OrdersModel();
        $where = array(
            'orderId' => null,
            'orderNo' => null,
        );
        parm_filter($where, $params);
        $order_detail = $order_model->where($where)->field($field)->find();
        if (empty($order_detail)) {
            return array();
        }
        if (isset($order_detail['userId'])) {
            $user_detail = (new UsersModule())->getUsersDetailById($order_detail['userId'], 'userName', 2);
            $order_detail['payment_username'] = $user_detail['userName'];
        }
        return (array)$order_detail;
    }

    /**
     * 用户下单成功记录地推收益明细
     * @param int $userId 受邀人id
     * @param string $order_token 合并订单标识
     * @param object $trans
     * @return bool
     * */
    public function addPullNewAmountLog(int $users_id, string $order_token, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $users_module = new UsersModule();
        $users_detail = $users_module->getUsersDetailById($users_id, '*', 2);
        if (empty($users_detail)) {
            $m->rollback();
            return false;
        }
        if ($users_detail['firstOrder'] == 1) {
            $prefix = $m->tablePrefix;
            $user_phone = $users_detail['userPhone'];
            $distribution_invitation_model = new DistributionInvitationModel();
            $where = array();
            $where['invitation.userPhone'] = $user_phone;
            $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
            $field .= ',users.balance';
            $invitation_detail = $distribution_invitation_model
                ->alias('invitation')
                ->join("left join {$prefix}users users on users.userId=invitation.userId")
                ->field($field)
                ->where($where)
                ->find();
            if (!empty($invitation_detail) && $invitation_detail['dataType'] == 2) {
                $field = 'pullNewPermissions,pullNewRegister,pullNewOrder';
                $invitation_users_detail = $users_module->getUsersDetailById($invitation_detail['userId'], $field, 2);
                //用支付成功后,发放邀请奖励,状态为待入账
                if ($invitation_users_detail['pullNewPermissions'] == 1) {
                    $configs = $GLOBALS['CONFIG'];
                    $pull_new_order = $configs['pullNewOrder'];//奖励规则-用户成功下单
                    //如果用户开启了拉新权限,但是没有配置奖励,而平台商城信息却配置了,则采用商品信息中的奖励规则,否则采用用户中的配置规则
                    if ($invitation_users_detail['pullNewOrder'] > 0) {
                        $pull_new_order = $invitation_users_detail['pullNewOrder'];
                    }
                    if ($pull_new_order > 0) {
                        $pull_amount_log = array(
                            'userId' => $users_id,
                            'inviterId' => $invitation_detail['userId'],
                            'dataType' => 2,
                            'orderToken' => $order_token,
                            'amount' => $pull_new_order,
                            'status' => 0,
                        );
                        $pull_res = $users_module->savePullNewAmountLog($pull_amount_log, $m);
                        if (!$pull_res) {
                            $m->rollback();
                            return false;
                        }
                    }
                }
            }
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 订单-获取需要支付的商品总金额
     * @param int $user_id 用户id
     * @param array $goods_list 支付的商品数据,注:从购物车查出来的数据
     * @return float
     * */
    public function getAllShopGoodsMoney(int $user_id, array $goods_list)
    {
        if (empty($user_id) || empty($goods_list)) {
            return 0;
        }
        $total_money = array();
        $goods_module = new GoodsModule();
        foreach ($goods_list as $goods_item) {
            $goods_id = $goods_item['goodsId'];
            $sku_id = $goods_item['skuId'];
            $goods_cnt = $goods_item['goodsCnt'];
            $init_price = $goods_module->replaceGoodsPrice($user_id, $goods_id, $sku_id, $goods_cnt, $goods_item);//替换商品价格
            $total_money[] = (float)$init_price * (float)$goods_cnt;
        }
        $total_money = array_sum($total_money);
        return $total_money;
    }

    /**
     * 订单商品-写入补差价记录
     * @param array $params
     * -int id 自增id
     * -int orderId 订单id
     * -string tradeNo 在线支付交易流水
     * -int goodsId 商品ID
     * -flaot money 退款金额(单位元)
     * -int payType 退款金额(单位元) 废弃
     * -int userId 用户id
     * -float weightG 总重量(单位g)
     * -int isPay 是否已补(否：0 是：1)
     * -datetime payTime 退款提交时间
     * -int goosNum 商品购买数量
     * -float unitPrice 单价
     * @param object $trans 用于事务
     * @return int
     * */
    public function saveOrdersGoodsPriceDiff(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'orderId' => null,
            'tradeNo' => null,
            'goodsId' => null,
            'skuId' => null,
            'money' => null,
            'payType' => null,
            'userId' => null,
            'weightG' => null,
            'isPay' => null,
            'payTime' => null,
            'goosNum' => null,
            'unitPrice' => null,
        );
        parm_filter($save_params, $params);
        $model = new GoodsPriceDiffeModel();
        if (empty($params['id'])) {
            $save_params['addTime'] = date('Y-m-d H:i:s');
            $id = $model->add($save_params);
            if (!id) {
                $db_trans->rollback();
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
     * 订单商品-删除补差价记录
     * @param int $order_id 订单id
     * @param int $goosd_id 商品id
     * @param object $trans
     * @return bool
     * */
    public function delOrderGoodsPriceDiff(int $order_id, int $goosd_id, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new GoodsPriceDiffeModel();
        $where = array(
            'orderId' => $order_id,
            'goodsId' => $goosd_id,
        );
        $res = $model->where($where)->delete();
        if ($res === false) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 订单商品-价格-元/g,数量
     * @param int $order_id 订单id
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuId
     * @return float
     * */
    public function getOrderGoodsPriceNumG(int $order_id, int $goods_id, int $sku_id)
    {
        $num_or_g_price = 0;
        $order_goods_params = array(
            'orderId' => $order_id,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
        );
        $order_goods_detail = $this->getOrderGoodsInfoByParams($order_goods_params, 'id,goodsNums,goodsPrice', 2);
        if (empty($order_goods_detail)) {
            return (float)$num_or_g_price;
        }
        $goods_module = new GoodsModule();
        $goods_field = 'goodsId,goodsName,SuppPriceDiff,weightG';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $goods_field, 2);
        if (!empty($sku_id)) {
            $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
            if (empty($sku_detail)) {
                return (float)$num_or_g_price;
            }
            $goods_detail['weightG'] = $sku_detail['weigetG'];
        }
//        $goods_weightG = (float)$goods_detail['weightG'];
        //废弃包装系数
//        $goods_weightG = (float)$order_goods_detail['goodsNums'];
        $order_goods_total_money = (float)bc_math($order_goods_detail['goodsPrice'], $order_goods_detail['goodsNums'], 'bcmul', 2);//订单商品金额小计
        $num_or_g_price = bc_math($order_goods_total_money, $order_goods_detail['goodsNums'], 'bcdiv', 2);
        return (float)$num_or_g_price;
    }

    /**
     * 订单商品-保存售后记录
     * @param array $params 订单投诉表字段
     * @param object $trans 用于事务
     * @return int
     * */
    public function saveOrderGoodsComplains(array $params, $trans)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $save_params = array(
            'orderId' => null,
            'complainType' => null,
            'complainTargetId' => null,
            'respondTargetId' => null,
            'needRespond' => null,
            'deliverRespondTime' => null,
            'complainContent' => null,
            'complainAnnex' => null,
            'complainStatus' => null,
            'respondContent' => null,
            'respondAnnex' => null,
            'respondTime' => null,
            'finalResult' => null,
            'finalResultTime' => null,
            'finalHandleStaffId' => null,
            'goodsId' => null,
            'skuId' => null,
            'returnNum' => null,
            'driverId' => null,
            'driverComplainStatus' => null,
            'driverComplainContent' => null,
            'dataType' => null,
            'returnFreight' => null,
            'returnAmountStatus' => null,
            'returnAmount' => null,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($save_params, $params);
        $model = new OrderComplainsModel();
        if (empty($params['complainId'])) {
            $save_params['createTime'] = date('Y-m-d H:i:s');
            $complainId = $model->add($save_params);
            if (!$complainId) {
                $db_trans->rollback();
                return 0;
            }
        } else {
            $complainId = $params['complainId'];
            $where = array(
                'complainId' => $complainId
            );
            $save_res = $model->where($where)->save($save_params);
            if (!$save_res) {
                return 0;
            }
        }
        return (int)$complainId;
    }

    /**
     * 订单商品-售后申请记录-详情-多条件查找
     * @param int $order_id 订单id
     * @param int $goods_id 商品id
     * @param int $sku_id 商品skuId
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderGoodsComplainsDetailByParams(int $order_id, int $goods_id, int $sku_id, $field = '*')
    {
        $where = array(
            'orderId' => $order_id,
            'goodsId' => $goods_id,
            'skuId' => $sku_id,
        );
        $model = new OrderComplainsModel();
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        if ($result['complainStatus'] == 2) {
            $result['original_road_money'] = (float)$result['returnAmount'];//原路退回金额
            $result['quota_road_money'] = 0;//扣除欠款中的金额
            $orderGoodsParams = array(
                'orderId' => $order_id,
                'goodsId' => $goods_id,
                'skuId' => $sku_id,
            );
            $orderGoodsDetail = $this->getOrderGoodsInfoByParams($orderGoodsParams, '*', 2);
            $orderGoodsDetail['weight'] = (float)$orderGoodsDetail['weight'];
            $orderGoodsDetail['receivingGoodsNums'] = $orderGoodsDetail['goodsNums'];
            $orderGoodsDetail['diffWeight'] = 0;//发货数量/重量超出购买数量/重量的部分
            if ($orderGoodsDetail['weight'] > 0) {
                $orderGoodsDetail['receivingGoodsNums'] = $orderGoodsDetail['weight'];
            }
            $sortingModule = new SortingModule();
            $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($order_id, $goods_id, $sku_id);
            if (!empty($sortingOrderGoodsDetail)) {
                $personId = $sortingOrderGoodsDetail['personId'];
                $sortingId = $sortingOrderGoodsDetail['id'];
                $sortingGoodsInfo = $sortingModule->getSortingGoodsDetailByParams($personId, $sortingId, $goods_id, $sku_id);
                if (!empty($sortingGoodsInfo)) {
                    $orderGoodsDetail['receivingGoodsNums'] = $sortingGoodsInfo['sorting_ok_weight'];
                }
            }
            $orderGoodsDetail['receivingGoodsNums'] = (float)$orderGoodsDetail['receivingGoodsNums'];//实收数量/重量
            $orderGoodsDetail['goodsNums'] = (float)$orderGoodsDetail['goodsNums'];//购买数量/重量
            if ($orderGoodsDetail['receivingGoodsNums'] > $orderGoodsDetail['goodsNums']) {
                $diffWeight = (float)bc_math($orderGoodsDetail['receivingGoodsNums'], $orderGoodsDetail['goodsNums'], 'bcsub', 3);//超出实际购买的数量/重量
                $orderGoodsMoneyTotal = (float)bc_math($orderGoodsDetail['goodsNums'], $orderGoodsDetail['goodsPrice'], 'bcmul', 2);
                $weightPrice = (float)bc_math($orderGoodsMoneyTotal, $orderGoodsDetail['goodsNums'], 'bcdiv', 2);
                $diffWeightMoney = (float)bc_math($weightPrice, $diffWeight, 'bcmul', 2);
                if ($diffWeightMoney > 0) {
                    $result['diffWeight'] = $diffWeight;
                    $result['quota_road_money'] = $diffWeightMoney;
                    $result['original_road_money'] = (float)bc_math($result['returnAmount'], $result['quota_road_money'], 'bcsub', 2);
                }
            }
        }
        return $result;
    }

    /**
     * 订单商品-售后申请记录-删除
     * @param int $order_id 订单id
     * @param int [$goods_id] 商品id
     * @param int [$sku_id] 商品skuId
     * @param object $trans
     * @return bool
     * */
    public function delOrderGoodsComplains(int $order_id, $goods_id = 0, $sku_id = 0, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new OrderComplainsModel();
        $where = array(
            'orderId' => $order_id
        );
        if (!empty($goods_id)) {
            $where['goodsId'] = $goods_id;
        }
        if (!empty($sku_id)) {
            $where['skuId'] = $sku_id;
        }
        $result = $model->where($where)->delete();
        if (!$result) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 订单商品-保存售后退款记录
     * @param array $params
     * -int id 自增id
     * -int orderId 订单ID
     * -int goodsId 商品ID
     * -float money 退款金额
     * -int payType 支付类型[1:微信|2:支付宝|3:余额]
     * -int userId 用户id
     * -int skuId 商品skuId
     * -int complainId 投诉/退货id
     * @param object $trans
     * @return int
     * */
    public function saveOrderGoodsComplainsrecord(array $params, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new OrderComplainsrecordModel();
        $save_params = array(
            'orderId' => null,
            'tradeNo' => null,
            'goodsId' => null,
            'money' => null,
            'payType' => null,
            'userId' => null,
            'skuId' => null,
            'complainId' => null,
        );
        parm_filter($save_params, $params);
        if (empty($params['id'])) {
            $save_params['addTime'] = date('Y-m-d H:i:s');
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
     * 订单商品-保存售后退款记录-删除
     * @param int $order_id 订单id
     * @param int [$goods_id] 商品id
     * @param int [$sku_id] 商品skuId
     * @param object $trans
     * @return bool
     * */
    public function delOrderGoodsComplainsrecord(int $order_id, $goods_id = 0, $sku_id = 0, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $model = new OrderComplainsrecordModel();
        $where = array(
            'orderId' => $order_id
        );
        if (!empty($goods_id)) {
            $where['goodsId'] = $goods_id;
        }
        if (!empty($sku_id)) {
            $where['skuId'] = $sku_id;
        }
        $result = $model->where($where)->delete();
        if (!$result) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }

    /**
     * 订单商品是否补过差价
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @return bool
     * */
    public function isDiffPirceOrderGoods(int $orderId, int $goodsId, int $skuId)
    {
        $diff_model = new GoodsPriceDiffeModel();
        $where = array(
            'orderId' => $orderId,
            'goodsId' => $goodsId,
            'skuId' => $skuId,
        );
        $count = $diff_model->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }


    /**
     * 发货-扣除库房库存
     * 注:该方法只针对无分拣端发货,有分拣的不能走该方法,只能走各自分拣端的发货流程
     * @param int $orderId 订单id
     * @param array $weightGJson 实际商品称重信息
     * @params array 登陆者信息
     * -int goodsId 商品id
     * -int skuId 商品skuId
     * -float goodWeight 商品实际称重,原来就是用的这个字段
     * @return array
     * */
    public function deductionOrderGoodsStock(int $orderId, array $weightGJson, array $loginUserInfo, $trans = null)
    {
        //注:该方法只针对无分拣端发货,有分拣的不能走走该方法,只能走各自分拣端的发货流程
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderDetail = $this->getOrderDetailByParams(array('orderId' => $orderId));
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单信息有误');
        }
        if ($orderDetail['orderStatus'] != 2) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前订单不允许发货，请核实');
        }
        $shopId = $orderDetail['shopId'];
        $orderGoods = $this->getOrderGoodsList($orderId, 'og.*', 2);
        $shopConfig = (new ShopsModule())->getShopConfig($shopId, 'shopId,isSorting', 2);
        $isOpenSorting = $shopConfig['isSorting'];//是否开启分拣(1:开启)
        $goodsModule = new GoodsModule();
//        $sortingModule = new SortingModule();
        //{"remark":"单据备注","goods_data":[{"goods_id":337,"sku_id":849,"nums":2},{"goods_id":331,"sku_id":0,"nums":3}]}
        $exWarehouseGoods = array();
        foreach ($orderGoods as $oGoodsDetail) {
            $goodsId = $oGoodsDetail['goodsId'];
            $skuId = $oGoodsDetail['skuId'];
            $goodsNums = $oGoodsDetail['goodsNums'];//购买份数
            $goodsInfo = $goodsModule->getGoodsInfoById($goodsId, 'goodsName,SuppPriceDiff,goodsStock,goodsSpec,goodsUnit', 2);
            $goodsName = $goodsInfo['goodsName'];
            $goodsStock = (float)$goodsInfo['goodsStock'];
            if (!empty($skuId)) {
                $skuInfo = $goodsModule->getSkuSystemInfoById($skuId, 2);
                $goodsStock = (float)$skuInfo['skuGoodsStock'];
            }
            $suppPriceDiff = $goodsInfo['SuppPriceDiff'];//是否非标品(1:非标品)
            $num_or_weight = gChangeKg($goodsId, $goodsNums, 1, $skuId);//实际购买数量/重量
            $realStock = $num_or_weight;//最终需要扣除的库房库存
            $exWarehouseGoodsDetail = array(
                'goods_id' => $goodsId,
                'sku_id' => $skuId,
                'nums' => (float)$goodsNums,
                'actual_delivery_quantity' => (float)$realStock,
                'unit_price' => (float)$goodsInfo['goodsUnit'],
            );
            $exWarehouseGoods[] = $exWarehouseGoodsDetail;
            if ($suppPriceDiff == 1) {//非标品
                if ($isOpenSorting == 1) {//开启了分拣
                    //放在分拣端处理
//                    $sortingGoodsDetial = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                }
                if ($isOpenSorting != 1) {//未开启分拣
                    foreach ($weightGJson as $weightDetail) {
                        if ($goodsId == $weightDetail['goodsId'] && $skuId == $weightDetail['skuId']) {
//                            $realStock = $weightDetail['goodWeight'] / 1000;
                            $realStock = (float)$weightDetail['goodWeight'];//不再处理库存单位换算
                        }
                    }
                    if ($realStock > $goodsStock) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "发货失败，商品{$goodsName}库房库存不足");
                    }
                }
            }
            if ($suppPriceDiff != 1) {//标品
                if ($isOpenSorting == 1) {//开启了分拣
//                    $sortingGoodsDetial = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                    //放在分拣端处理
                }
                if ($isOpenSorting != 1) {//未开启分拣
                    if ($realStock > $goodsStock) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "发货失败，商品{$goodsName}库房库存不足");
                    }
                }
            }
            $stockRes = $goodsModule->deductionGoodsStock($goodsId, $skuId, $realStock, 1, 1, $dbTrans);
            if ($stockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "发货失败，商品{$goodsName}扣除库存失败");
            }
        }
        $exWarehouseModule = new ExWarehouseOrderModule();
        $warehouseBillData = array(
            'pagetype' => 1,
            'shopId' => $shopId,
            'user_id' => $loginUserInfo['user_id'],
            'user_name' => $loginUserInfo['user_username'],
            'relation_order_number' => $orderDetail['orderNo'],
            'relation_order_id' => $orderDetail['orderId'],
            'goods_data' => $exWarehouseGoods,
        );
        $addWarehouseRes = $exWarehouseModule->addExWarehouseOrder($warehouseBillData, $dbTrans);
        if ($addWarehouseRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "发货失败，出库单据创建失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 支付请求记录-添加
     * @param array $params
     * -int type 数据类型(1:微信下单支付|2:微信重新支付|3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包)|6:商家还款)
     * -jsonString requestJson 请求支付参数
     * -string orderToken 订单标识,wst_order_merge表中的orderToken字段
     * @return string
     * */
    public function createNotifyLog($val)
    {
        $requestJson = json_decode($val, true);
        $add['userId'] = $requestJson['userId'];
        $add['type'] = $val['dataType'];
        $add['requestJson'] = $val['requestJson'];
        $add['orderToken'] = $val['orderToken'];
        $add['requestTime'] = date('Y-m-d H:i:s', time());
        $tab = M('notify_log');
        $autoId = $tab->add($add);
        $token = '';
        if ($autoId) {
            $token = str_pad($autoId, 32, "0", STR_PAD_LEFT) . time();
            $tab->where(["id" => $autoId])->save(['key' => md5($token)]);
        }
        if (!empty($token)) {
            $token = md5($token);
        }
        return $token;
    }

    /**
     * 支付请求记录-修改
     * @param int $notify_id 请求id
     * @param jsonString $response_json 回调记录
     * @param int $notify_status 回调状态(-1:未处理|1:已处理)
     * @return bool
     * */
    public function updateNotifyLog(int $notify_id, string $response_json, $notify_status = 1)
    {
        $save_params = array(
            'responseJson' => $response_json,
            'notifyStatus' => $notify_status,
            'responseTime' => date('Y-m-d H:i:s')
        );
        $res = M('notify_log')->where(array(
            'id' => $notify_id
        ))->save($save_params);
        return (bool)$res;
    }

    /**
     * 支付请求记录-详情-根据key获取
     * @param string $key 支付请求标识
     * @return array
     * */
    public function getNotifyLogDetail(string $key)
    {
        $model = M('notify_log');
        $result = $model->where(array('key' => $key))->find();
        return (array)$result;
    }

    /**
     * 订单-确认收货
     * @param int $orderId 订单id
     * @param int $type 操作(-1:拒收 1:确认收货)
     * @param string $rejectionRemarks 拒收备注
     * @param string $logParams 日志信息
     * @param object $trans
     * @return array
     * */
    public function confirmReceipt(int $orderId, $type = 1, $rejectionRemarks = '', $logParams = array(), $trans = null)
    {
        $config = $GLOBALS['CONFIG'];
        $orderField = 'orderId,userId,orderNo,orderScore,orderStatus,poundageRate,poundageMoney,shopId,useScore,scoreMoney,payType,payFrom,receiveTime,taskId';
        $orderDetail = $this->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        if (!empty($orderDetail['receiveTime']) && $orderDetail['receiveTime'] != '0000-00-00 00:00:00') {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已收货订单不能重复收货');
        }
        $userId = (int)$orderDetail['userId'];
        $userModule = new UsersModule();
        $userField = 'userId,userName,userPhone,firstOrder,distributionAuthority,pullNewPermissions,pullNewRegister,pullNewOrder';
        $userDetail = $userModule->getUsersDetailById($userId, $userField, 2);
        $firstOrderNew = $userDetail['firstOrder'];
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $logSysMoneysModule = new LogSysMoneysModule();
        if ($type == 1) {//确认收货
            $datetime = date("Y-m-d H:i:s");
            $orderParams = array(
                'orderId' => $orderId,
                'orderStatus' => 4,
                'receiveTime' => $datetime,
            );
            if ($orderDetail['payType'] != 1) {
                $orderParams['isPay'] = 1;
            }
            if ($orderDetail['payFrom'] == 4) {
                $orderParams['pay_time'] = $datetime;
            }
            $incGoodsSaleRes = $this->IncOrderGoodsSale($orderId, $dbTrans);//增加商品销量
            if (!$incGoodsSaleRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '订单商品销量更新失败');
            }
            if ($config['isOrderScore'] == 1 && $orderDetail['orderScore'] > 0) {//更新用户积分
                $incScoreRes = $userModule->incUserScore($userId, (int)$orderDetail['orderScore'], $dbTrans);
                if (!$incScoreRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '用户积分更新失败');
                }
                $scoreParams = array();
                $scoreParams["userId"] = $userId;
                $scoreParams["score"] = $orderDetail["orderScore"];
                $scoreParams["dataSrc"] = 1;
                $scoreParams["dataId"] = $orderId;
                $scoreParams["dataRemarks"] = "交易获得";
                $scoreParams["scoreType"] = 1;
                $addScoreLog = $userModule->addUserScore($scoreParams, $dbTrans);
                if (!$addScoreLog) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '积分日志记录失败');
                }
            }
            //积分支付支出
            if ($orderDetail["scoreMoney"] > 0) {//原有逻辑,不清楚干嘛的,这里继续保留吧
                $logSysParams = array();
                $logSysParams["targetType"] = 0;
                $logSysParams["targetId"] = $userId;
                $logSysParams["dataSrc"] = 2;
                $logSysParams["dataId"] = $orderId;
                $logSysParams["moneyRemark"] = "订单【{$orderDetail['orderNo']}】支付{$orderDetail["useScore"]}个积分，支出 ￥{$orderDetail["scoreMoney"]}";
                $logSysParams["moneyType"] = 2;
                $logSysParams["money"] = $orderDetail["scoreMoney"];
                $logSysRes = $logSysMoneysModule->saveLogSysMoneys($logSysParams, $dbTrans);
                if (empty($logSysRes)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '平台流水记录失败');
                }
            }
            if ($orderDetail["poundageMoney"] > 0) {//收取订单佣金
                $logSysParams = array();
                $logSysParams["targetType"] = 1;
                $logSysParams["targetId"] = $orderDetail["shopId"];
                $logSysParams["dataSrc"] = 1;
                $logSysParams["dataId"] = $orderId;
                $logSysParams["moneyRemark"] = "收取订单【{$orderDetail["orderNo"]}】{$orderDetail["poundageRate"]}%的佣金￥{$orderDetail["poundageMoney"]}";
                $logSysParams["moneyType"] = 1;
                $logSysParams["money"] = $orderDetail["poundageMoney"];
                $logSysRes = $logSysMoneysModule->saveLogSysMoneys($logSysParams, $dbTrans);
                if (empty($logSysRes)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '平台流水记录失败');
                }
            }
            //发放地推邀请奖励 注：备注下：地推奖励只针对首单，这里只实现领取奖励，发放在登录和下单的时候已经处理过了
            $pullRes = $this->grantPullNewAmount($orderId, $dbTrans);
            if ($pullRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', $pullRes['msg']);
            }
            //发放分销商品奖励 注：针对每笔订单
            $distributionRes = $this->orderGoodsDistribution($orderId, $dbTrans);
            if ($distributionRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', $distributionRes['msg']);
            }
        }
        if ($type != 1) {//拒收
            $orderParams = array(
                'orderId' => $orderId,
                'orderStatus' => -3,
            );
        }
        $saveOrderRes = $this->saveOrdersDetail($orderParams, $dbTrans);
        if (empty($saveOrderRes)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', '订单更新失败');
        }
        $diffMoneyRes = $this->returnOrderGoodsDiffMoney($orderId, $dbTrans);//退差价
        if ($diffMoneyRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', $diffMoneyRes['msg']);
        }
//        $firstOrder = $this->doFirstOrder($orderId, $dbTrans);//首次下单,奖励邀请人
//        if ($firstOrder['code'] != ExceptionCodeEnum::SUCCESS) {
//            $dbTrans->rollback();
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', $firstOrder['msg']);
//        }
        $invitationRes = $this->doOrderInvitation($orderId, $dbTrans);//下单处理邀请奖励
        if ($invitationRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '确认收货失败', $invitationRes['msg']);
        }
        if ($firstOrderNew != -1) {//主逻辑完成后,修改用户非首单
            $userParams = array(
                'firstOrder' => -1,
            );
            $saveUserRes = $userModule->updateUsersInfo($userId, $userParams, $dbTrans);
            if ($saveUserRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $dbTrans->rollback();
                return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '确认收货失败', '用户信息更新失败');
            }
        }
        $logOrderModule = new LogOrderModule();
        if (empty($logParams)) {
            $content = ($type == 1) ? "用户已收货" : "用户拒收：" . $rejectionRemarks;
            $logParams = [
                'orderId' => $orderId,
                'logContent' => $content,
                'logUserId' => $userId,
                'logUserName' => '用户',
                'orderStatus' => 4,
                'payStatus' => 1,
                'logType' => 1,
            ];
        }
        $logRes = $logOrderModule->addLogOrders($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '确认收货失败', '订单日志记录失败');
        }
        if (!empty($orderDetail['taskId'])) {//更新配送端任务的配送状态
            $updateTastRes = (new TaskModule())->completeTaskDeliveryStatus($orderDetail['taskId'], $dbTrans);
            if (!$updateTastRes) {
                $dbTrans->rollback();
                return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '确认收货失败', '配送单任务更新失败');
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
//        (new NotifyModule())->postMessage(10, $userId, $orderDetail['orderNo'], $orderDetail['shopId']);
        return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '收货成功');
    }

    /**
     * 订单-发放地推奖励
     * @param int $orderId 订单id
     * @param object $trans
     * @return array
     * */
    function grantPullNewAmount(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderField = 'orderId,userId,orderToken';
        $orderDetail = $this->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $userId = $orderDetail['userId'];
        $userModule = new UsersModule();
        $userField = 'userId,userName,userPhone';
        $userDetail = $userModule->getUsersDetailById($userId, $userField, 2);
        $distributionModule = new DistributionModule();
        $invitationInfo = $distributionModule->getDistributionInvitationDetailByPhone($userDetail['userPhone'], 2);//获取地推邀请记录
        if (empty($invitationInfo)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '无地推邀请，不需要处理');
        }
        $pullLogDetail = $distributionModule->getPullNewAmountLogByParams($userId, $orderDetail['orderToken'], 2, 0);//获取地推收益明细
        if (empty($pullLogDetail)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '无地推收益明细，不需要处理');
        }
        $pullLogParams = array(
            'id' => $pullLogDetail['id'],
            'status' => 1,
        );
        $updateAmountLogRes = $distributionModule->savePullNewAmountLog($pullLogParams, $dbTrans);
        if (empty($updateAmountLogRes)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '地推明细更新失败');
        }
        $balanceLog = array(
            'userId' => $invitationInfo['userId'],
            'balance' => $pullLogDetail['amount'],
            'dataSrc' => 1,
            'orderNo' => '',
            'dataRemarks' => "拉新奖励-用户成功下单",
            'balanceType' => 1,
            'shopId' => 0
        );
        //更新用户余额并记录余额变动日志
        $balanceLog = $userModule->addUserBalance($balanceLog, $dbTrans);
        if (!$balanceLog) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '余额明细日志记录失败');
        }
        $balanceRes = $userModule->incUsersBalance($userId, $pullLogDetail['amount'], $dbTrans);
        if (!$balanceRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户余额更新失败');
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单-发放商品分销奖励
     * @param int $orderId 订单id
     * @param object $trans
     * @return array
     * */
    public function orderGoodsDistribution(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderModule = new OrdersModule();
        $orderField = 'orderId,orderStatus,userId';
        $orderDetail = $orderModule->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $userId = $orderDetail['userId'];
        $orderGoods = $orderModule->getOrderGoodsList($orderId, 'og.*', 2);
        $goodsModule = new GoodsModule();
        $shopCatModule = new ShopCatsModule();
        $distributionModule = new DistributionModule();
        $userModule = new UsersModule();
        foreach ($orderGoods as $detail) {
            $goodsPrice = (float)$detail['goodsPrice'];
            $goodsNums = (float)$detail['goodsNums'];
            $goodsId = $detail['goodsId'];
            $goodsField = 'goodsId,goodsName,shopCatId2';
            $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
            if (empty($goodsDetail)) {
                continue;
            }
            $shopCatId2 = $goodsDetail['shopCatId2'];
            $shopCatField = 'catId,distributionLevel1Amount,distributionLevel2Amount';
            $shopCatDetail = $shopCatModule->getShopCatInfoById($shopCatId2, $shopCatField, 2);
            if (empty($shopCatDetail['distributionLevel1Amount']) && empty($shopCatDetail['distributionLevel2Amount'])) {
                continue;
            }
            $level1Amount = (float)sprintfNumber($goodsPrice * (float)$shopCatDetail['distributionLevel1Amount']) * $goodsNums;
            $level2Amount = (float)sprintfNumber($goodsPrice * (float)$shopCatDetail['distributionLevel2Amount']) * $goodsNums;
            $relationList = $distributionModule->getUserDistributionRelationList($userId);
            foreach ($relationList as $relationDetail) {
                $userDistribution = array(
                    'goodsId' => $detail['goodsId'],
                    'userId' => $relationDetail['pid'],
                    'UserToId' => $relationDetail['userId'],
                    'orderId' => $orderId,
                    'distributionLevel' => $relationDetail['distributionLevel'],
                    'buyerId' => $userId,
                    'state' => 1,
                );
                if ($userDistribution['distributionLevel'] == 1) {
                    $userDistribution['distributionMoney'] = $level1Amount;
                } elseif ($userDistribution['distributionLevel'] == 2) {
                    $userDistribution['distributionMoney'] = $level2Amount;
                }
                if ($userDistribution['distributionMoney'] > 0) {
                    $saveDistributionRes = $distributionModule->saveUserDistribution($userDistribution, $dbTrans);
                    if (empty($saveDistributionRes)) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分销金记录失败');
                    }
                    $incRes = $userModule->incDistributionMoney($userDistribution['userId'], $userDistribution['distributionMoney'], $dbTrans);
                    if (!$incRes) {
                        $dbTrans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户分销金更新失败');
                    }
                }
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单-补差价商品列表
     * @param int $orderId 订单id
     * @param int $isPay 是否已补(0:未补 1:已补 3:全部)
     * @return array
     * */
    public function getOrderGoodsDiffList(int $orderId, $isPay = 0)
    {
        $model = new GoodsPriceDiffeModel();
        $where = array(
            'orderId' => $orderId,
        );
        if (in_array($isPay, array(0, 1))) {
            $where['isPay'] = $isPay;
        }
        $result = $model->where($where)->select();
        return (array)$result;
    }

    /**
     * 订单-订单商品补差价
     * @param int $orderId 订单id
     * @param object $trans
     * @return array
     * */
    public function returnOrderGoodsDiffMoney(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderGoodsDiffs = $this->getOrderGoodsDiffList($orderId);//获取需要补差价的商品
        if (empty($orderGoodsDiffs)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '当前订单无需处理补差价');
        }
        $orderField = 'orderId,shopId,orderNo,userId,tradeNo,realTotalMoney,payFrom';
        $orderDetail = $this->getOrderInfoById($orderId, $orderField, 2);
        $userId = $orderDetail['userId'];
        $payTransactionId = $orderDetail['tradeNo'];
        $payTotalFee = (float)$orderDetail['realTotalMoney'] * 100;
        $userModule = new UsersModule();
        $logModule = new LogOrderModule();
        $payModule = new PayModule();
        foreach ($orderGoodsDiffs as $detail) {
            $goodsId = $detail['goodsId'];
            $skuId = $detail['skuId'];
            $payRefundFee = (float)$detail['money'] * 100;
            if ($orderDetail['payFrom'] == 1) {//支付宝
                //临时修复,原有代码直接复制过来的
                $aliPayRefundRes = $payModule->aliPayRefund($orderDetail['tradeNo'], $detail['money']);
                if ($aliPayRefundRes['code'] != 0) {
                    $repay = false;
                } else {
                    $repay = true;
                    $content = "补差价退款：" . $detail['money'] . '元';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 4,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    $logRes = $logModule->addLogOrders($logParams, $dbTrans);
                    if (!$logRes) {
                        $repay = false;
                    }
                    $diffParams = array(
                        'id' => $detail['id'],
                        'isPay' => 1,
                        'payTime' => date('Y-m-d H:i:s'),
                    );
                    $diffRes = $this->saveOrdersGoodsPriceDiff($diffParams, $dbTrans);//更改记录为已补
                    if (empty($diffRes)) {
                        $repay = false;
                    }
                }
            } elseif ($orderDetail['payFrom'] == 2) {//微信
                $repay = wxRefundGoods($payTransactionId, $payTotalFee, $payRefundFee, $orderId, $goodsId, $skuId, 2, []);//原有代码就不修改了
            } elseif ($orderDetail['payFrom'] == 3) {//余额
                //余额补差价退款
                $refundFee = $payRefundFee / 100;//需要退款的金额
                $incRes = $userModule->incUsersBalance($userId, $refundFee, $dbTrans);
                if ($incRes) {
                    $repay = true;
                    $content = "补差价退款：" . $refundFee . '元';
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 4,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    $logRes = $logModule->addLogOrders($logParams, $dbTrans);
                    if (!$logRes) {
                        $repay = false;
                    }
                    //补差价余额日志
                    $userBalanceData = array();
                    $userBalanceData['userId'] = $userId;
                    $userBalanceData['balance'] = $refundFee;
                    $userBalanceData['dataSrc'] = 1;
                    $userBalanceData['orderNo'] = $orderDetail['orderNo'];
                    $userBalanceData['dataRemarks'] = "补差价退款：" . $refundFee . '元';
                    $userBalanceData['balanceType'] = 1;
                    $userBalanceData['createTime'] = date("Y-m-d H:i:s");
                    $userBalanceData['shopId'] = $orderDetail['shopId'];
                    $balanceLog = $userModule->addUserBalance($userBalanceData, $dbTrans);
                    if (!$balanceLog) {
                        $repay = false;
                    }
                    $diffParams = array(
                        'id' => $detail['id'],
                        'isPay' => 1,
                        'payTime' => date('Y-m-d H:i:s'),
                    );
                    $diffRes = $this->saveOrdersGoodsPriceDiff($diffParams, $dbTrans);//更改记录为已补
                    if (empty($diffRes)) {
                        $repay = false;
                    }
                }
                if ($repay !== true) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '补差价失败');
                }
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单-处理首次下单
     * @param int $orderId 订单id
     * @param object $trans
     * @return array
     * */
    public function doFirstOrder(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderField = "orderId,userId,realTotalMoney";
        $orderDetail = $this->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $userId = $orderDetail['userId'];
        $userModule = new UsersModule();
        $userField = 'userId,firstOrder';
        $userDetail = $userModule->getUsersDetailById($userId, $userField, 2);
        if ($userDetail['firstOrder'] != 1) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '非首单，无需处理');
        }
        $userInvitationModule = new UserInvitationModule();
        $invitationDetail = $userInvitationModule->getUserInvitationDetail($userId);
        if (empty($invitationDetail)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '没有邀请记录，无需处理');
        }
        $invitationUser = $userModule->getUsersDetailById($invitationDetail['userId'], 'userId,userName', 2);
        if (!empty($invitationUser)) {//该区间的逻辑之前就是这样的,不太清楚,就不做修改了
            //订单完成后更新用户的邀新状态
            $saveData = array();
            $saveData['id'] = $invitationDetail['id'];
            $saveData['invitationStatus'] = 1;
            $saveRes = $userInvitationModule->saveUserInvitation($saveData, $dbTrans);
            if (empty($saveRes)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '邀请记录更新失败');
            }
            if ((float)$orderDetail['realTotalMoney'] >= $GLOBALS["CONFIG"]["InvitationOrderMoney"]) {
                //是否存在待恢复使用的优惠券
                $couponsUsersModel = new CouponsUsersModel();
                $couponsWhere = array(
                    'userId' => $invitationDetail['userId'],
                    'userToId' => $userId,
                    'dataFlag' => -1,
                );
                $couponsParams = array(
                    'dataFlag' => 1
                );
                $couponsUsersModel->where($couponsWhere)->save($couponsParams);
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单-处理邀请奖励
     * @param int $orderId 订单id
     * @param object $trans
     * @return array
     * */
    public function doOrderInvitation(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderField = "orderId,userId,realTotalMoney,orderNo";
        $orderDetail = $this->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $userModule = new UsersModule();
        $userId = $orderDetail['userId'];
        $userDetail = $userModule->getUsersDetailById($userId, 'userId,firstOrder', 2);
//        $firstOrderNew = $userDetail['firstOrder'];
        $userInvitationModule = new UserInvitationModule();
        $invitationDetail = $userInvitationModule->getUserInvitationDetail($userId);
        if (empty($invitationDetail)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '无邀请记录，无需处理');
        }
        if ($invitationDetail['inviteRewardNum'] > 0 && (float)$orderDetail['realTotalMoney'] >= $GLOBALS["CONFIG"]["InvitationOrderMoney"]) {//该区间代码是原有逻辑,这里就不做大改动了,没有那么多时间
            //用于判断是否减去邀请奖励次数
            $typeStatus = 0;
            //-------------------------------------
            $inviteNumRules = (int)$GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
//            if ($inviteNumRules == 1 && $firstOrderNew == -1) {             //优惠券
            if ($inviteNumRules == 1) {             //优惠券
                //获取邀请优惠券
                $couponModule = new CouponsModule();
                $where = array();
                $where['couponType'] = 3;
                $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
                $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
                $couponList = $couponModule->getCouponListByParams($where, 'couponId');
                foreach ($couponList as $cDetail) {
                    $couponModule->okCoupons($invitationDetail['userId'], $cDetail['couponId'], 3, $userId);
                }
                //用于判断是否走到这里
                $typeStatus = 1;
            } elseif ($inviteNumRules == 2) {//返现
                $invitationMoney = $GLOBALS["CONFIG"]['InvitationMoney'];//返现百分比
                $money = sprintfNumber(($orderDetail['realTotalMoney'] * $invitationMoney / 100));
                $balanceData = array(
                    'userId' => $invitationDetail['userId'],
                    'balance' => $money,
                    'dataSrc' => 1,
                    'orderNo' => $orderDetail['orderNo'],
                    'dataRemarks' => "邀请用户订单返现",
                    'balanceType' => 1,
                    'shopId' => 0
                );
                $balanceRes = $userModule->addUserBalance($balanceData, $dbTrans);
                if (!$balanceRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单返现，余额日志记录失败');
                }
                $incRes = $userModule->incUsersBalance($invitationDetail['userId'], $money, $dbTrans);
                if (!$incRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '邀请用户，订单返现，余额更新失败');
                }
                //用于判断是否走到这里
                $typeStatus = 1;
            } elseif ($inviteNumRules == 3) {//积分
                $num = explode("-", $GLOBALS["CONFIG"]['InvitationRange']);
                $score = rand($num[0], $num[1]);
                $incScoreRes = $userModule->incUserScore($invitationDetail['userId'], $score, $dbTrans);
                if (!$incScoreRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '邀请用户奖励积分，积分更新失败');
                }
                $scoreData = array();
                $scoreData["userId"] = $invitationDetail['userId'];
                $scoreData["score"] = $score;
                $scoreData["dataSrc"] = 8;//8：小程序邀请好友获得
                $scoreData["dataId"] = $orderId;
                $scoreData["dataRemarks"] = "邀请好友赠送获得";
                $scoreData["scoreType"] = 1;
                $scoreLog = $userModule->addUserScore($scoreData, $dbTrans);
                if (!$scoreLog) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '邀请用户奖励积分，积分记录失败');
                }
                //用于判断是否走到这里
                $typeStatus = 1;
            }
            if ($typeStatus == 1) {
                $decRes = $userInvitationModule->decInviteRewardNum($invitationDetail['id'], 1, $dbTrans);
                if (!$decRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '奖励次数更新失败');
                }
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单汇总-订单采购商品列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireTime 要求送达日期
     * -int deliveryStatus 发货状态(1:未发货 2:已发货)
     * -int catId 店铺分类id
     * -int lineId 线路id
     * -int purchase_type 采购类型(1:市场自采 2:供应商供货)
     * -int purchaser_or_supplier_id (采购员/供货商)id
     * -int regionId 区域id
     * -int orderSrc 订单来源(3:app 4：小程序)
     * -string goodsKeywords 商品关键字
     * -int customerRankId 客户类型id
     * -int goodsId 商品id
     * -int skuId 规格id
     * @return array
     * */
    public function getOrderGoodsSummaryList(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $requireTime = (string)$paramsInput['requireTime'];
        $deliveryStatus = (int)$paramsInput['deliveryStatus'];
        if (!in_array($deliveryStatus, array(1, 2))) {
            return array();
        }
        $where = "orders.shopId={$shopId} and orders.orderFlag=1 ";
        $where .= " and goods.goodsFlag=1 ";
        $where .= " and users.userFlag=1 ";
        if (!empty($paramsInput['goodsId'])) {
            $where .= "and o_goods.goodsId={$paramsInput['goodsId']} ";
        }
        if (!empty($paramsInput['skuId'])) {
            $where .= "and o_goods.skuId={$paramsInput['skuId']} ";
        }
        if (!empty($paramsInput['skuId'])) {
            $where .= "and o_goods.skuId={$paramsInput['skuId']} ";
        }
        if ($deliveryStatus == 1) {//未发货
            $where .= " and orders.orderStatus = 2 ";
        } else {//已发货
            $where .= " and orders.orderStatus IN(3,16)";
        }
        if (!empty($requireTime)) {
            $where .= " and orders.requireTime between '{$requireTime} 00:00:00' and '{$requireTime} 23:59:59' ";
        }
        if (!empty($paramsInput['catId'])) {
            $shopCatDetail = (new GoodsCatModule())->getShopCatDetailById($paramsInput['catId']);
            if (!empty($shopCatDetail)) {
                if ($shopCatDetail['level'] == 1) {
                    $where .= " and goods.shopCatId1={$paramsInput['catId']}";
                }
                if ($shopCatDetail['level'] == 2) {
                    $where .= " and goods.shopCatId2={$paramsInput['catId']}";
                }
            }
        }
        if (!empty($paramsInput['lineId'])) {
            $where .= " and orders.lineId={$paramsInput['lineId']} ";
        }
        if (!empty($paramsInput['regionId'])) {
            $where .= " and orders.regionId={$paramsInput['regionId']} ";
        }
        if (!empty($paramsInput['purchase_type'])) {
            $where .= " and o_goods.purchase_type={$paramsInput['purchase_type']} ";
        }
        if (!empty($paramsInput['purchaser_or_supplier_id'])) {
            $where .= " and o_goods.purchaser_or_supplier_id={$paramsInput['purchaser_or_supplier_id']} ";
        }
        if (!empty($paramsInput['orderSrc'])) {
            $where .= " and orders.orderSrc={$paramsInput['orderSrc']} ";
        }
        if (!empty($paramsInput['goodsKeywords'])) {
            $goodsKeywords = $paramsInput['goodsKeywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goodsKeywords, $match)) {
                $goodsKeywords = strtoupper($goodsKeywords);
            }
            $where .= " and (o_goods.goodsName like '%{$paramsInput['goodsKeywords']}%' or goods.py_code like '%{$goodsKeywords}%' or goods.py_initials like '%{$goodsKeywords}%' or o_goods.goodsCode like '%{$paramsInput['goodsKeywords']}%') ";
        }
        if (!empty($paramsInput['customerRankId'])) {
            $where .= " and orders.customerRankId={$paramsInput['customerRankId']} ";
        }
        $model = new OrderGoodsModel();
        $prefix = $model->tablePrefix;
        $field = 'o_goods.id,o_goods.goodsId,o_goods.skuId,o_goods.skuSpecStr,o_goods.goodsName,o_goods.goodsCode,o_goods.goodsImg,o_goods.unitName,o_goods.purchase_type,o_goods.purchaser_or_supplier_id,o_goods.goodsNums,o_goods.remarks';
        $field .= ',orders.orderId,orders.orderNo,orders.orderStatus,orders.orderRemarks,orders.lineId';
        $field .= ',users.userId as payment_userid,users.userName as payment_username';
        $field .= ',goods.shopCatId1,goods.shopCatId2,goods.goodsSpec';
        $list = $model
            ->alias('o_goods')
            ->join("left join {$prefix}orders orders on orders.orderId=o_goods.orderId")
            ->join("left join {$prefix}users users on users.userId=orders.userId")
            ->join("left join {$prefix}goods goods on goods.goodsId=o_goods.goodsId")
            ->where($where)
            ->field($field)
            ->select();
        if (empty($list)) {
            return array();
        }
        $orderGoodsEnum = new OrderGoodsEnum();
        $goodsCatModule = new GoodsCatModule();
        $lineModule = new LineModule();
        foreach ($list as &$item) {
            $item['purchase_type_name'] = $orderGoodsEnum::getPurchaseType()[$item['purchase_type']];
            $item['purchaser_or_supplier_name'] = $this->getOrderGoodsPurchaserOrSupplier($item['id']);
            if (!in_array($item['orderStatus'], array(3, 17))) {
                $item['orderStatusName'] = '待发货';
            } elseif ($item['orderStatus'] == 4) {
                $item['orderStatusName'] = '已完成';
            } else {
                $item['orderStatusName'] = '待收货';
            }
            $item['shopCatId1Name'] = $goodsCatModule->getShopCatDetailById($item['shopCatId1'])['catName'];
            $item['shopCatId2Name'] = $goodsCatModule->getShopCatDetailById($item['shopCatId2'])['catName'];
            $item['lineName'] = '';
            if (!empty($item['lineId'])) {
                $lineDetial = $lineModule->getLineDetailById($item['lineId']);
                $item['lineName'] = !empty($lineDetial['lineName']) ? $lineDetial['lineName'] : '';
            }
        }
        return $list;
    }

    /**
     * 订单商品-获取单据采购员/供应商
     * @param int $id 订单商品关联唯一标识id
     * @return string
     * */
    public function getOrderGoodsPurchaserOrSupplier(int $id)
    {
        $str = '';
        $detail = $this->getOrderGoodsInfoById($id, 'purchase_type,purchaser_or_supplier_id', 2);
        if (!empty($detail['purchaser_or_supplier_id'])) {
            if ($detail['purchase_type'] == 1) {//市场自采
                $purchaserDetial = (new ShopStaffMemberModule())->getShopStaffMemberDetail($detail['purchaser_or_supplier_id'], 'username');
                if (!empty($purchaserDetial)) {
                    $str = $purchaserDetial['username'];
                }
            }
            if ($detail['purchase_type'] == 2) {//供应商直供
                $supplierDetail = (new SupplierModule())->getSupplierDetailById($detail['purchaser_or_supplier_id'], 'supplierName');
                if (!empty($supplierDetail)) {
                    $str = $supplierDetail['supplierName'];
                }
            }
        }
        return (string)$str;
    }

    /**
     * 订单-打印-递增打印数量
     * @param int $orderId 订单id
     * @param object $trans
     * @return bool
     * */
    public function incOrdersPrintNum(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new OrdersModel();
        $incRes = $model->where(array('orderId' => $orderId))->setInc('printNum');
        if (!$incRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 根据订单id获取订单列表
     * @param int $orderIdArr
     * @param string $field 表字段
     * @return array
     * */
    public function getOrderListById(array $orderIdArr, $field = '*')
    {
        $orderModel = new OrdersModel();
        $where = array(
            'orderId' => array('in', $orderIdArr),
            'orderFlag' => 1,
        );
        $orderList = $orderModel->where($where)->field($field)->select();
        if (empty($orderList)) {
            return array();
        }
        return $orderList;
    }

    /**
     * 订单售后详情-complainId查找
     * @param int $complainId
     * @param string $field 表字段
     * @return array
     * */
    public function getComplainsDetailById(int $complainId, $field = '*')
    {
        $model = new OrderComplainsModel();
        $where = array(
            'complainId' => $complainId
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 订单售后列表-orderId查找
     * @param int $orderId 订单id
     * @param string $field 表字段
     * @return array
     * */
    public function getComplainsListByorderId(int $orderId, $field = '*')
    {
        $model = new OrderComplainsModel();
        $where = array(
            'orderId' => $orderId
        );
        $result = $model->where($where)->field($field)->select();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 订单-价格-自动更新已付,应付,未付等
     * @param int $orderId 订单id
     * @param object $trans
     * @return bool
     * */
    public function autoUpdateOrderPrice(int $orderId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderData = $this->getOrderInfoById($orderId);
        $orderRow = $orderData['data'];
        $orderGoodsData = $this->getOrderGoodsList($orderId);
        $orderGoodsList = $orderGoodsData['data'];
        $receivableAmount = 0;//应收金额
        //$receivedAmount = 0;//已收金额
//        $paidInAmount = 0;//实收金额
        $uncollectedAmount = 0;//未收金额
        $diffAmount = 0;//退补差价金额
        //$diffAmountTime = null;//退补差价时间
        $buyOrderGoodsAmountTotal = 0;//下单商品金额
        foreach ($orderGoodsList as $item) {
            $sortingNum = (float)$item['sortingNum'];//分拣数量
            $buyNum = (float)$item['goodsNums'];//下单数量
            $buyUnitPrice = (float)$item['goodsPrice'];//下单单价
            $buyGoodsPriceTotal = $buyUnitPrice * $buyNum;//下单商品金额小计
            $sortGoodsPriceTotal = $buyUnitPrice * $sortingNum;//分拣商品金额小计
            $buyOrderGoodsAmountTotal += $buyGoodsPriceTotal;
            $receivableAmount += $sortGoodsPriceTotal;
            $diffNum = bc_math($sortingNum, $buyNum, 'bcsub', 2);
            $diffNumAmount = bc_math($diffNum, $buyUnitPrice, 'bcmul', 2);
            $diffAmount += $diffNumAmount;
        }
        $receivedAmount = $receivableAmount;
        $paidInAmount = $receivableAmount;
        if ($orderRow['payFrom'] == 4) {//货到付款
            $receivedAmount = 0;
            $uncollectedAmount = $receivableAmount;
        }
        $saveOrderParams = array(
            'orderId' => $orderId,
            'receivableAmount' => $receivableAmount,
            'receivedAmount' => $receivedAmount,
            'paidInAmount' => $paidInAmount,
            'uncollectedAmount' => $uncollectedAmount,
            'diffAmount' => $diffAmount,
        );
        if (!empty($diffAmount)) {
            $saveOrderParams['diffAmountTime'] = date('Y-m-d H:i:s');
        }
        $saveRes = $this->saveOrdersDetail($saveOrderParams, $dbTrans);
        if (!$saveRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}
<?php

namespace Adminapi\Model;

use AlipayTradePayContentBuilder;
use AlipayTradeQueryContentBuilder;
use AlipayTradeService;
use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\PosExchangeGoodsLogModel;
use App\Models\PosGivebackOrdersGoodsModel;
use App\Models\PosGivebackOrdersModel;
use App\Models\PosOrdersGoodsModel;
use App\Models\PosOrdersModel;
use App\Models\PosReportModel;
use App\Models\PosReportRelationModel;
use App\Models\ShopsModel;
use App\Models\SkuGoodsSystemModel;
use App\Models\UserScoreModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Log\LogServiceModule;
use App\Modules\Pos\PosServiceModule;
use App\Modules\Pos\PromotionModule;
use App\Modules\Users\UsersServiceModule;
use CjsProtocol\LogicResponse;
use CLogFileHandler;
use GoodsDetail;
use Log;
use MicroPay;
use PayNotifyCallBack;
use Think\Model;
use WxPayMicroPay;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 收银端
 */
class CashierModel extends BaseModel
{
    /**
     * @return array
     * 收银订单统计
     * https://www.yuque.com/youzhibu/ruah6u/mpeb9y
     */
    public function countPosOrders()
    {
        $pos_orders_tab = M('pos_orders po');
        $where = [];
        $where['po.state'] = 3;
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['ws.shopName'] = ['like', "%{$shopWords}%"];
            $maps['ws.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        //订单总数
        $order_count_total = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->count();
        //订单金额
        $order_amount_total = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->sum('po.realpayment');
        //订单现金金额
        $order_cash_amount = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->sum('po.cash');
        //订单微信金额
        $order_cash_wechat = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->sum('po.wechat');
        //订单支付宝金额
        $order_cash_alipay = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->sum('po.alipay');
        //订单银联金额
        $order_cash_unionpay = $pos_orders_tab->join('left join wst_shops ws on ws.shopId = po.shopId')->where($where)->sum('po.unionpay');
        $data = array(
            'order_count_total' => (int)$order_count_total,
            'order_amount_total' => formatAmount($order_amount_total),
            'order_cash_amount' => formatAmount($order_cash_amount),
            'order_cash_wechat' => formatAmount($order_cash_wechat),
            'order_cash_alipay' => formatAmount($order_cash_alipay),
            'order_cash_unionpay' => formatAmount($order_cash_unionpay),
        );
        return $data;
    }


    /**
     * 退货记录统计
     * @return array
     * https://www.yuque.com/youzhibu/ruah6u/sog02d
     * */
    public function countReturnGoodsLog()
    {
        $response = LogicResponse::getInstance();
        $where = [];
        $where['o.state'] = 3;
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['ws.shopName'] = ['like', "%{$shopWords}%"];
            $maps['ws.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
        $give_orders_model = M('pos_giveback_orders b');
        $return_order_num = $give_orders_model
            ->join('left join wst_pos_orders o on o.id=b.orderId ')
            ->join('left join wst_shops ws on ws.shopId = o.shopId')
            ->where($where)
            ->count('b.orderId');
        $return_order_amount = $give_orders_model
            ->join('left join wst_pos_orders o on o.id=b.orderId ')
            ->join('left join wst_shops ws on ws.shopId = o.shopId')
            ->where($where)
            ->sum('b.realpayment');
        $return_goods_num = M('pos_giveback_orders_goods bg')
            ->join('left join wst_pos_giveback_orders b on b.backId=bg.backId ')
            ->join('left join wst_pos_orders o on o.id=b.orderId ')
            ->join('left join wst_shops ws on ws.shopId = o.shopId')
            ->where($where)
            ->sum('bg.number');
        $result = array(
            'return_order_num' => (int)$return_order_num,
            'return_order_amount' => (float)$return_order_amount,
            'return_goods_num' => (int)$return_goods_num,
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 获取退货记录列表
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function getReturnGoodsLogList(array $params)
    {
        $response = LogicResponse::getInstance();
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = " o.state = 3 ";
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO,og.goodsName,og.goodsSn,og.presentPrice,s.shopName";
        $sql = "select {$field} from __PREFIX__pos_giveback_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                left join __PREFIX__shops s on s.shopId = o.shopId
                left join __PREFIX__pos_orders_goods og on og.id=log.relation_id
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                $item['presentPrice_total'] = bc_math($item['presentPrice'], $item['number'], 'bcmul', 2);
            }
            unset($item);
            $data['root'] = $list;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * @return array
     * 换货记录统计
     */
    public function countExchangeGoodsLog()
    {
        $response = LogicResponse::getInstance();
        $where = [];
        $where['o.state'] = 3;
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['ws.shopName'] = ['like', "%{$shopWords}%"];
            $maps['ws.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
        $exchange_log_list = M('pos_exchange_goods_log log')
            ->join("left join wst_pos_orders o on o.id=log.orderId")
            ->join('left join wst_shops ws on ws.shopId = o.shopId')
            ->field('log.*,ws.shopName')
            ->where($where)
            ->order('log.log_id asc')
            ->select();
        $exchange_order_num = count(array_unique(array_column($exchange_log_list, 'orderId')));
        $return_goods_num = array_sum(array_column($exchange_log_list, 'return_num'));
        $exchange_goods_num = array_sum(array_column($exchange_log_list, 'exchange_num'));
        $due_amount = 0;//补商家的
        $negative_amount = 0;//补用户的
        foreach ($exchange_log_list as $item) {
            if ($item['diff_type'] == 1) {
                $negative_amount += $item['diff_amount'];
            } elseif ($item['diff_type'] == 2) {
                $due_amount += $item['diff_amount'];
            }
        }
        $diff_amount = $due_amount - $negative_amount;
        if ($diff_amount < 0) {
            $diff_amount_symbol = '-';
        } else {
            $diff_amount_symbol = '+';
        }
        $result = array(
            'exchange_order_num' => (int)$exchange_order_num,//换货单数
            'return_goods_num' => (float)$return_goods_num,//退回商品数
            'exchange_goods_num' => (int)$exchange_goods_num,//换出商品数
            'diff_amount_symbol' => $diff_amount_symbol,//顾客补差价金额正负标志
            'diff_amount' => formatAmount(abs($diff_amount)),//顾客补差价金额
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 获取换货记录列表
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function getExchangeGoodsLogList(array $params)
    {
        $response = LogicResponse::getInstance();
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = " o.state = 3 ";
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO,s.shopName";
        $sql = "select {$field} from __PREFIX__pos_exchange_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                left join __PREFIX__shops s on s.shopId = o.shopId
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                if ($item['diff_amount'] == 0) {
                    $item['diff_amount'] = '￥0.00';
                } elseif ($item['diff_amount'] > 0) {
                    if ($item['diff_type'] == 1) {
                        $item['diff_amount'] = '-￥' . $item['diff_amount'];
                    } elseif ($item['diff_type'] == 2) {
                        $item['diff_amount'] = '￥' . $item['diff_amount'];
                    }
                }
            }
            unset($item);
            $data['root'] = $list;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 获取Pos订单列表
     * where
     * @param string orderNo PS:订单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大金额(金额区间)
     * @param string minMoney PS:最小金额(金额区间)
     * @param int state PS:状态 (1:待结算 | 2：已取消 | 3：已结算)
     * @param int pay PS:支付方式 (1:现金支付 | 2：余额支付 | 3：银联支付 | 4：微信支付 | 5：支付宝支付 | 6：组合支付)
     * @param string name PS:收银员账号
     * @param string username PS:收银员姓名
     * @param string phone PS:收银员手机号
     * @param string identity PS:身份 1:会员 2：游客
     * @param string userName 用户名
     * @param string userPhone 用户手机号
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int pageSupport 支持分页【-1：无分页|1：有分页】
     * 默认为1
     */
    public function getPosOrderList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $field = "po.*,u.username as user_username,u.phone as user_phone,us.userName,us.userPhone,og.promotion_type,s.shopName ";
        $field1 = " sum(po.realpayment) as total_order_money ";//主要用来统计相关条件下的总订单金额
        $where = '';
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where = "  (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }

        $whereFind = [];
        $whereFind['og.promotion_type'] = function () use ($params) {
            if (empty($params['promotion_type'])) {
                return null;
            }
            return ['=', "{$params['promotion_type']}", 'and'];
        };
        $whereFind['po.orderNO'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['po.addtime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
//            $params['startDate'] = $params['startDate'] . ' 00:00:00';
//            $params['endDate'] = $params['endDate'] . ' 23:59:59';
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['po.realpayment'] = function () use ($params) {
            if (!is_numeric($params['maxMoney']) || !is_numeric($params['minMoney'])) {
                return null;
            }
            if ((float)$params['maxMoney'] <= 0) {
                return null;
            }
            return ['between', "{$params['minMoney']}' and '{$params['maxMoney']}", 'and'];
        };
        $whereFind['po.state'] = function () use ($params) {
            if (empty($params['state']) || !in_array($params['state'], [1, 2, 3])) {
                return null;
            }
            return ['=', "{$params['state']}", 'and'];
        };
        $whereFind['po.pay'] = function () use ($params) {
            if (empty($params['pay']) || !in_array($params['pay'], [1, 2, 3, 4, 5, 6])) {
                return null;
            }
            return ['=', "{$params['pay']}", 'and'];
        };
        $whereFind['u.name'] = function () use ($params) {
            if (empty($params['user_name'])) {
                return null;
            }
            return ['like', "%{$params['user_name']}%", 'and'];
        };
        $whereFind['u.username'] = function () use ($params) {
            if (empty($params['user_username'])) {
                return null;
            }
            return ['like', "%{$params['user_username']}%", 'and'];
        };
        $whereFind['u.phone'] = function () use ($params) {
            if (empty($params['user_phone'])) {
                return null;
            }
            return ['like', "%{$params['user_phone']}%", 'and'];
        };
        $whereFind['us.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['us.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        where($whereFind);
        if ($params['identity'] == 1) {//会员
            $where .= " and po.memberId > 0 ";
        } else if ($params['identity'] == 2) {//游客
            $where .= " and po.memberId = 0 ";
        }
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            if (empty($where)) {
                $whereInfo = $whereFind;
            } else {
                $whereInfo = $where . ' and ' . $whereFind;
            }
        }

        $sql = "select $field from __PREFIX__pos_orders po
        left join __PREFIX__pos_orders_goods og on og.orderid = po.id
        left join __PREFIX__user u on po.userId = u.id
        left join __PREFIX__shops s on s.shopId = po.shopId
        left join __PREFIX__users us on po.memberId = us.userId ";
        $sql .= " where {$whereInfo}";

        $sql .= " group by po.id ";
        $sql .= " order by po.id desc ";
        if ($params['pageSupport'] == 1) {
            $res = $this->pageQuery($sql, $page, $pageSize);
        } else {
            $res = [];
            $res['root'] = $this->query($sql);
            $res['total'] = count($res['root']);
        }
        $field1 .= " ,count(po.id) as order_count_total , sum(po.realpayment) as order_amount_total , sum(po.cash) as order_cash_amount , sum(po.wechat) as order_cash_wechat , sum(po.alipay) as order_cash_alipay , sum(po.unionpay) as order_cash_unionpay";
        //主要用来统计相关条件下的总订单金额
        $sql1 = "select $field1 from __PREFIX__pos_orders po
        left join __PREFIX__user u on po.userId = u.id
        left join __PREFIX__shops s on s.shopId = po.shopId
        left join __PREFIX__users us on po.memberId = us.userId ";
        //        left join __PREFIX__pos_orders_goods og on og.orderid = po.id  //影响统计
        $sql1 .= " where {$whereInfo}";
        $sql1 .= "group by po.id ";
        $total_order_money = $this->query($sql1);
        $order_count_total = count($total_order_money);
        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
        $res['total_order_money'] = $total_order_money[0]['total_order_money'];
        $openPresaleCash = M("sys_configs")->where("fieldCode='openPresaleCash'")->getField('fieldValue'); //是否开启预存款
        $pos_orders_goods_tab = M('pos_orders_goods');
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                $val['pay_time'] = (string)$val['pay_time'];
                //$val['user_name'] = (string)$val['user_name'];
                $val['user_username'] = (string)$val['user_username'];
                $val['user_phone'] = (string)$val['user_phone'];
                $val['memberId'] = (int)$val['memberId'];
                $val['userName'] = (string)$val['userName'];
                $val['userPhone'] = (string)$val['userPhone'];
                $val['pay'] = (int)$val['pay'];
                $val['integral'] = (int)$val['integral'];
                $val['outTradeNo'] = (string)$val['outTradeNo'];
                $val['discount'] = ((float)$val['discount'] * 10) . '%';
                if ($openPresaleCash == 1) {
                    $val['trueRealpayment'] = $val['realpayment'];
                }
                $val['total_favorablePrice'] = (float)$pos_orders_goods_tab->where(array(
                    'orderid' => $val['id'],
                    'state' => 1
                ))->sum('favorablePrice');
                $val['total_favorablePrice'] = formatAmountNum($val['total_favorablePrice']);
                //获取订单商品信息
                $posOrderWhere = [];
                $posOrderWhere['state'] = 1;//状态[1:已加入 -1：已删除]
                $posOrderWhere['orderid'] = $val['id'];//所属订单id
                $posOrdersGoodsList = $pos_orders_goods_tab
                    ->alias('pg')
                    ->join("left join wst_goods g on g.goodsId = pg.goodsId")
                    ->field("pg.*,g.goodsThums,g.goodsImg")
                    ->where($posOrderWhere)
                    ->select();
                $res['root'][$key]['posOrdersGoodsList'] = $posOrdersGoodsList;
            }
            unset($val);
        }
        //统计信息
        $res['order_count_total'] = $order_count_total;//订单总数
        $res['order_amount_total'] = formatAmount(array_sum(array_column($total_order_money, 'order_amount_total')));//订单金额
        $res['order_cash_amount'] = formatAmount(array_sum(array_column($total_order_money, 'order_cash_amount')));//订单现金金额
        $res['order_cash_wechat'] = formatAmount(array_sum(array_column($total_order_money, 'order_cash_wechat')));//订单微信金额
        $res['order_cash_alipay'] = formatAmount(array_sum(array_column($total_order_money, 'order_cash_alipay')));//订单支付宝金额
        $res['order_cash_unionpay'] = formatAmount(array_sum(array_column($total_order_money, 'order_cash_unionpay'))); //订单银联金额

        return returnData($res);
    }

    /**
     * 获取Pos订单详情
     * @param int $posId Pos订单id
     */
    public function getPosOrderDetail(int $posId)
    {
        $field = "po.*,u.name as user_name,u.username as user_username,u.phone as user_phone,users.userName,users.userPhone,s.shopName ";
        $where = " where po.id = {$posId} ";
//        //店铺名称|编号
//        if (!empty($params['shopWords'])) {
//            $where = " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
//        }
        $sql = "select {$field} from __PREFIX__pos_orders po 
                left join __PREFIX__user u on po.userId = u.id 
                left join __PREFIX__shops s on s.shopId = po.shopId
                left join __PREFIX__users users on users.userId = po.memberId ";
        $sql .= $where;
        $res = (array)$this->queryRow($sql);
        if (empty($res)) {
            return array();
        }
        $res['goods_amount_total'] = 0;
        if ($res) {
            $res['discount'] = ((float)$res['discount'] * 10) . '%';
            $res['userName'] = (string)$res['userName'];
            $res['userPhone'] = (string)$res['userPhone'];
            $res['user_name'] = (string)$res['user_name'];
            $res['user_username'] = (string)$res['user_username'];
            $res['user_phone'] = (string)$res['user_phone'];
            $res['pay_time'] = (string)$res['pay_time'];
            $res['is_return_state'] = 1;//本单可退货状态(-1:不能退货 1:可以退货)
            $res['is_exchange_state'] = 1;//本单可换货状态(-1:不能换货 1:可以换货)
            //商品信息
            $orderGoodsTab = M('pos_orders_goods pg');
            $goodsList = (array)$orderGoodsTab
                ->join("left join wst_goods g on g.goodsId=pg.goodsId")
                ->where(['orderid' => $res['id']])
                ->field("pg.*,g.goodsThums,g.goodsImg")
                ->select();
            $goods_num = 0;//所有商品数量
            $return_num = 0;//已退货数量
            $exchange_num = 0;//已换货数量
            $discount_price = 0;
            foreach ($goodsList as &$item) {
                $discount_price += ($item['originalPrice'] * $item['discount']);
                $goods_num += $item['number'];
                $return_num += $item['refundNum'];
                $exchange_num += $item['exchangeNum'];
                $item['discount'] = ((float)$item['discount'] * 100) . '%';
                $res['goods_amount_total'] += $item['subtotal'];
                $res['original_price_total'] += $item['originalPrice'];
            }
            unset($item);
            if ($return_num != 0 || $exchange_num != 0) {
                if ($return_num >= $goods_num || $exchange_num >= $goods_num) {
                    $res['is_return_state'] = -1;
                    $res['is_exchange_state'] = -1;
                }
            }
            $res['goodslist'] = $goodsList;
            $res['goods_amount_total'] = formatAmount($res['goods_amount_total']);//商品信息-合计
            //退货商品信息
            $res['return_goods'] = array();
            $pos_service_module = new PosServiceModule();
            $giveback_order_result = $pos_service_module->getGivebackOrdersInfoByOrderId($posId);
            $res['return_goods_amount_total'] = 0;//退货商品信息金额合计
            if ($giveback_order_result['code'] == ExceptionCodeEnum::SUCCESS) {
                $giveback_order_data = $giveback_order_result['data'];
                $backId = $giveback_order_data['backId'];
                $return_goods_result = $pos_service_module->getGivebackGoodsListById($backId);
                if ($return_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $return_goods_data = $return_goods_result['data'];
                    foreach ($return_goods_data as &$item) {
                        $item['skuSpecAttr'] = '';
                        foreach ($goodsList as $val) {
                            if ($val['goodsId'] == $item['goodsId'] && $val['skuId'] == $item['skuId']) {
                                $item['skuSpecAttr'] = $val['skuSpecAttr'];
                            }
                        }
                        $item['presentPrice_total'] = $item['subtotal'];
                    }
                    unset($item);
                    $res['return_goods'] = $return_goods_data;
                    $res['return_goods_amount_total'] = array_sum(array_column($return_goods_data, 'subtotal'));
                }
            }
            //换货商品信息-退回
            $res['exchange_goods_return'] = array();
            $res['exchange_return_amount_total'] = 0;//换货-退回商品-金额合计
            $exchange_goods_return = $pos_service_module->getExchangeRelationListByOrderId($posId, 1);
            $goods_service_module = new GoodsServiceModule();
            $before_amount = 0;
            if ($exchange_goods_return['code'] == ExceptionCodeEnum::SUCCESS) {
                $exchange_return_goods = $exchange_goods_return['data'];
                foreach ($exchange_return_goods as $item) {
                    $order_goods_result = $pos_service_module->getPosOrdersGoodsInfo($item['relation_id']);
                    if ($order_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $order_goods_data = $order_goods_result['data'];
                        $goods_info_result = $goods_service_module->getGoodsInfoById($order_goods_data['goodsId']);
                        $goods_info_data = $goods_info_result['data'];
                        $goods_info = array();
                        $goods_info['goodsId'] = $order_goods_data['goodsId'];
                        $goods_info['goodsName'] = $order_goods_data['goodsName'];
                        $goods_info['goodsImg'] = (string)$goods_info_data['goodsImg'];
                        $goods_info['goodsThums'] = (string)$goods_info_data['goodsThums'];
                        $goods_info['goodsSn'] = $order_goods_data['goodsSn'];
                        $goods_info['skuSpecAttr'] = $order_goods_data['skuSpecAttr'];
                        $goods_info['return_num_total'] = $item['return_num_total'];
                        $goods_info['return_present_price'] = $item['return_present_price'];
                        $goods_info['return_present_total'] = $item['return_present_total'];
                        $goods_info['return_present_total'] = $item['return_present_total'];
                        $goods_info['return_subtotal'] = $item['return_present_total'];
                        $res['exchange_goods_return'][] = $goods_info;
                        $res['exchange_return_amount_total'] += $goods_info['return_subtotal'];
                        $before_amount += $item['return_present_total'];
                    }
                }
                unset($item);
            }
            $res['exchange_amount_total'] = formatAmount($res['exchange_amount_total']);
            //换货商品信息-换出
            $res['exchange_goods_exchange'] = array();
            $exchange_goods = array();
            $exchange_goods_exchange = $pos_service_module->getExchangeRelationListByOrderId($posId, 2);
            $after_amount = 0;
            if ($exchange_goods_exchange['code'] == ExceptionCodeEnum::SUCCESS) {
                $exchange_goods_data = $exchange_goods_exchange['data'];
                foreach ($exchange_goods_data as &$item) {
                    $goods_info_result = $goods_service_module->getGoodsInfoById($order_goods_data['goodsId']);
                    $goods_info_data = $goods_info_result['data'];
                    $goods_info = array();
                    $goods_info['goodsId'] = $item['goodsId'];
                    $goods_info['goodsSn'] = $item['goodsSn'];
                    $goods_info['goodsName'] = $goods_info_data['goodsName'];
                    $goods_info['skuSpecAttr'] = $item['skuSpecAttr'];
                    $goods_info['goodsImg'] = (string)$goods_info_data['goodsImg'];
                    $goods_info['goodsThums'] = (string)$goods_info_data['goodsThums'];
                    $goods_info['exchange_num'] = $item['exchange_num'];
                    $goods_info['exchange_subtotal'] = $item['exchange_subtotal'];
                    $goods_info['present_price'] = $item['present_price'];
                    $exchange_goods[] = $goods_info;
                    $after_amount += $item['exchange_subtotal'];
                }
                unset($item);
                $res['exchange_goods_exchange'] = $exchange_goods;
            }
            //换货前小计 PS:退回商品金额统计
            $res['before_amount'] = formatAmount($before_amount);
            //换货后小计 PS:换出商品金额统计
            $res['after_amount'] = formatAmount($after_amount);
            //顾客补差价
            $res['diff_money'] = $res['after_amount'] - $res['before_amount'];
            $oprea = '';
            if ($res['diff_money'] < 0) {
                $oprea = '-';
            }
            $res['diff_money'] = $oprea . formatAmount(abs($res['diff_money']));
            //订单日志
            $res['logs'] = $this->getPosOrdersLog($res['id']);
            //费用信息-商品合计
            $res['original_price_total'] = formatAmount($res['original_price_total']);
            //费用信息-折扣金额
            $res['discount_amount'] = bc_math($res['original_price_total'], $discount_price, 'bcsub', 2);
            $res['order_amount_total'] = $res['goods_amount_total'];
        }
        return $res;
    }

    /**
     * 收银订单日志
     * @param int $pos_order_id 收银订单id
     * @return array
     * */
    public function getPosOrdersLog(int $pos_order_id)
    {
        $table_action_log_tab = M('table_action_log');
        $where = array(
            'tal.tableName' => 'wst_pos_orders',
            'tal.dataId' => $pos_order_id,
        );
        $field = 'tal.logId,tal.actionUserName,tal.fieldName,tal.fieldValue,tal.remark,tal.createTime,s.shopName ';
        $data = (array)$table_action_log_tab
            ->alias('tal')
            ->join('left join wst_pos_orders po on po.id = tal.dataId')
            ->join('left join wst_shops s on s.shopId = po.shopId')
            ->where($where)
            ->field($field)
            ->order('tal.logId asc')
            ->select();
        foreach ($data as &$item) {
            $item['pay_status_name'] = '未支付';
            if ($item['fieldName'] == 'state' && $item['fieldValue'] == 3) {
                $item['pay_status_name'] = '已支付';
            }
            if ($item['fieldName'] == 'isRefund' && $item['fieldValue'] > 0) {
                $item['pay_status_name'] = '已支付';
            }
            if ($item['fieldName'] == 'exchangeStatus' && $item['fieldValue'] > 0) {
                $item['pay_status_name'] = '已支付';
            }
            unset($item['fieldName']);
            unset($item['fieldValue']);
        }
        unset($item);
        return $data;
    }

    #############################收银报表-start###################################

    /**
     * 报表-营业数据统计
     * @param string $keywords 店铺关键字(店铺名称/店铺编号)
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export (0:不导出 1:导出)
     * @return array
     * */
    public function businessStatisticsReport(string $keywords, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $pos_report_model = new PosReportModel();
        $table_prefix = $pos_report_model->tablePrefix;
        $field = 'report.report_id,report.report_date,report.sales_order_num,report.return_order_num,report.exchange_order_num,report.recharge_num,report.buy_setmeal_num,report.sales_order_money,report.return_order_money,report.exchange_order_money,report.recharge_wxpay_money,report.recharge_alipay_money,report.recharge_cash_money,report.buy_setmeal_wxpay_money,report.buy_setmeal_alipay_money,report.buy_setmeal_cash_money';
        $field .= ",shop.shopName,shop.shopSn";
        $where = array(
            'report.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'report.is_delete' => 0
        );
        $where['_string'] = ' (sales_order_num > 0 or return_order_num > 0 or exchange_order_num > 0 or recharge_num > 0 ) ';//销售订单/退货单/换货单/充值
        if (!empty($keywords)) {
            $where['_string'] .= " and (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%') ";
        }
        $sql = $pos_report_model
            ->alias('report')
            ->join("left join {$table_prefix}shops shop on shop.shopId=report.shop_id")
            ->where($where)
            ->field($field)
            ->order('report_date asc')
            ->buildSql();//报表日期数据
        if ($export != 1) {
            $report_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $report_data = array();
            $report_data['root'] = $this->query($sql);
        }
        $report_list = $report_data['root'];
        $sales_order_num = 0;//销售订单总数
        $return_order_num = 0;//退货单总数
        $exchange_order_num = 0;//换货单总数
        $recharge_num = 0;//充值总数
        //$buy_setmeal_num = 0;//购买套餐总数
        $sales_order_money = 0;//销售订单总金额
        $return_order_money = 0;//退货单总金额
        $exchange_order_money = 0;//换货单总金额
        $recharge_money = 0;//充值总金额 微信充值金额+支付宝充值金额+现金充值金额
        //$buy_setmeal_money = 0;//购买套餐总金额 微信购买套餐金额+支付宝购买套餐金额+现金购买套餐金额
        foreach ($report_list as &$item) {
            $sales_order_num += (int)$item['sales_order_num'];
            $return_order_num += (int)$item['return_order_num'];
            $exchange_order_num += (int)$item['exchange_order_num'];
            $recharge_num += (int)$item['recharge_num'];
            //$buy_setmeal_num += (int)$item['buy_setmeal_num'];
            $sales_order_money += (float)$item['sales_order_money'];
            $return_order_money += (float)$item['return_order_money'];
            $exchange_order_money += (float)$item['exchange_order_money'];
            //$current_buy_setmeal_money = (float)((float)$item['buy_setmeal_wxpay_money'] + (float)$item['buy_setmeal_alipay_money'] + (float)$item['buy_setmeal_cash_money']);//档期购买套餐总金额
            //$buy_setmeal_money += $current_buy_setmeal_money;
            $current_recharge_money = (float)((float)$item['recharge_wxpay_money'] + (float)$item['recharge_alipay_money'] + (float)$item['recharge_cash_money']);//充值总金额
            $recharge_money += $current_recharge_money;
            $item['recharge_money'] = formatAmount($current_recharge_money);
            //$item['buy_setmeal_money'] = formatAmount($current_buy_setmeal_money);
            $current_actual_money = ((float)$item['sales_order_money'] + (float)$item['exchange_order_money'] + (float)$item['recharge_money']) - (float)$item['return_order_money'];//当前实际金额=(销售订单金额+换货补差价金额+充值金额)-退货金额
            $item['actual_money'] = formatAmount($current_actual_money);
        }
        unset($item);
        //PS:换货金额有正负之分,正数代表用户补给商家的款,负数代表商家补给用户的款
        //实际金额=(销售订单金额+换货补差价金额+充值金额+购买套餐金额)-退货金额
        $actual_money = ($sales_order_money + $exchange_order_money + $recharge_money) - $return_order_money;//实际金额=(销售订单金额+换货补差价金额)-退货金额
        $report_data['root'] = $report_list;
        $report_data['sum'] = array(//统计字段
            'sales_order_num' => formatAmount($sales_order_num, 0),//销售订单总数
            'return_order_num' => formatAmount($return_order_num, 0),//退货单总数
            'exchange_order_num' => formatAmount($exchange_order_num, 0),//换货单总数
            'recharge_num' => formatAmount($recharge_num, 0),//充值总数
            //'buy_setmeal_num' => formatAmount($buy_setmeal_num, 0),//购买套餐总数
            'sales_order_money' => formatAmount($sales_order_money),//销售订单总金额
            'return_order_money' => formatAmount($return_order_money),//退货单总金额
            'exchange_order_money' => formatAmount($exchange_order_money),//换货单总金额
            'recharge_money' => formatAmount($recharge_money),//充值总金额 微信充值金额+支付宝充值金额+现金充值金额
            //'buy_setmeal_money' => formatAmount($buy_setmeal_money),//购买套餐总金额 微信购买套餐金额+支付宝购买套餐金额+现金购买套餐金额
            'actual_money' => formatAmount($actual_money),//实际金额
        );
        if ($export == 1) {//导出营业数据
            $this->exportBusinessStatistics($report_data);
        }
        return $report_data;
    }

    /**
     * 营业数据-导出
     * @param array $report_data 营业报表数据
     * */
    public function exportBusinessStatistics(array $report_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '营业数据报表';
        $excel_filename = '营业数据报表' . date('YmdHis');
        $sheet_title = array('日期', '订单总量', '退货单总量', '换货单总量', '充值总量', '订单总金额', '退货单总金额', '换货单补差价金额', '充值总金额', '实际金额');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $list = $report_data['root'];
        if (!empty($list)) {
            $sum = $report_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'report_date' => '合计',
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'return_order_num' => $sum['return_order_num'],//退货单总数
                'exchange_order_num' => $sum['exchange_order_num'],//换货单总数
                'recharge_num' => $sum['recharge_num'],//充值总数
                'sales_order_money' => $sum['sales_order_money'],//订单总金额
                'return_order_money' => $sum['return_order_money'],//退货单总金额
                'exchange_order_money' => $sum['exchange_order_money'],//换货单总金额
                'recharge_money' => $sum['recharge_money'],//充值总金额
                'actual_money' => $sum['actual_money'],//实际金额
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总数
                        break;
                    case 2:
                        $cellvalue = $detail['return_order_num'];//退货单总数
                        break;
                    case 3:
                        $cellvalue = $detail['exchange_order_num'];//换货单总数
                        break;
                    case 4:
                        $cellvalue = $detail['recharge_num'];//充值总量
                        break;
                    case 5:
                        $cellvalue = $detail['sales_order_money'];//订单总金额
                        break;
                    case 6:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 7:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价总金额
                        break;
                    case 8:
                        $cellvalue = $detail['recharge_money'];//充值总金额
                        break;
                    case 9:
                        $cellvalue = $detail['actual_money'];//实际金额
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 商品销量统计报表
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * int page 页码
     * int page_size 分页条数
     * int data_type 统计类型(1:按商品统计 2:按分类统计)
     * int model_type 模式(1:列表模式 2:图标模式)
     * int goods_cat_id1 商品商城一级分类id
     * int goods_cat_id2 商品商城二级分类id
     * int goods_cat_id3 商品商城三级分类id
     * string goods_keywords 商品名称或商品编码 PS:仅仅按商品统计的场景需要
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function goodsSaleReport(array $params)
    {
        if ($params['data_type'] == 1) {//按商品统计
            $response_data = $this->saleReportToGoods($params);
        } elseif ($params['data_type'] == 2) {//按分类统计
            $response_data = $this->saleReportToCat($params);
        }
        if ($params['export'] == 1) {//导出
            $this->exportgoodsSale($response_data, $params);
        }
        return $response_data;
    }

    /**
     * 导出商品销量统计报表
     * @param array $response_data 业务数据
     * @param array $attach 附加参数
     * */
    public function exportgoodsSale(array $response_data, $attach = array())
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $list = $response_data['root'];
        $data_type = $attach['data_type'];//统计类型(1:按商品统计 2:按分类统计)
        $title = '商品销量统计报表';
        $excel_filename = '商品销量统计报表' . date('YmdHis');
        if ($data_type == 1) {//按商品统计
//            $sheet_title = array('商品名称', '商品编码', '商品分类', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '换货-退回数量', '换货-换出数量', '换货-补差价金额', '实际金额');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
            $sheet_title = array('商品名称', '商品编码', '商品分类', '订单数量', '销售数量', '销售金额');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F');
        } else {//按分类统计
//            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '换货-退回数量', '换货-换出数量', '换货-补差价金额', '实际金额');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额');
            $letter = array('A', 'B', 'C', 'D');
        }
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            if ($data_type == 1) {//按商品统计
                $list[] = array(
                    'goodsName' => '合计',
                    'order_num' => $sum['sales_order_num'],//订单总量
                    'sale_num' => $sum['goods_sale_num'],//销售总量
                    'sale_money' => $sum['goods_sale_money'],//销售总金额
//                    'return_goods_num' => $sum['goods_return_num'],//退货总量
//                    'return_goods_money' => $sum['goods_return_money'],//退货总金额
//                    'exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回总量
//                    'exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出总量
//                    'exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货-补差价金额
//                    'actual_money' => $sum['actual_money'],//实际金额
                );
            } else {//按分类统计
                $list[] = array(
                    'goodsCatName3' => '合计',
                    'order_num' => $sum['sales_order_num'],//订单总量
                    'sale_num' => $sum['goods_sale_num'],//销售总量
                    'sale_money' => $sum['goods_sale_money'],//销售总金额
//                    'return_goods_num' => $sum['goods_return_num'],//退货总量
//                    'return_goods_money' => $sum['goods_return_money'],//退货总金额
//                    'exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回总量
//                    'exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出总量
//                    'exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货-补差价金额
//                    'actual_money' => $sum['actual_money'],//实际金额
                );
            }
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                if ($data_type == 1) {//按商品统计
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsName'];//商品名称
                            break;
                        case 1:
                            $cellvalue = $detail['goodsSn'];//商品编码
                            break;
                        case 2:
                            $cellvalue = $detail['goodsCatName3'];//商品分类
                            break;
                        case 3:
                            $cellvalue = $detail['order_num'];//订单数量
                            break;
                        case 4:
                            $cellvalue = $detail['sale_num'];//销售数量
                            break;
                        case 5:
                            $cellvalue = $detail['sale_money'];//销售金额
                            break;
//                        case 6:
//                            $cellvalue = $detail['return_goods_num'];//退货数量
//                            break;
//                        case 7:
//                            $cellvalue = $detail['return_goods_money'];//退货金额
//                            break;
//                        case 8:
//                            $cellvalue = $detail['exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['exchange_diff_money'];//换货-补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
                    }
                } else {//按分类统计
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsCatName3'];//分类名称
                            break;
                        case 1:
                            $cellvalue = $detail['order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['sale_money'];//销售金额
                            break;
//                        case 4:
//                            $cellvalue = $detail['return_goods_num'];//退货数量
//                            break;
//                        case 5:
//                            $cellvalue = $detail['return_goods_money'];//退货金额
//                            break;
//                        case 6:
//                            $cellvalue = $detail['exchange_input_num'];//换货-退回数量
//                            break;
//                        case 7:
//                            $cellvalue = $detail['exchange_out_num'];//换货-换出数量
//                            break;
//                        case 8:
//                            $cellvalue = $detail['exchange_diff_money'];//换货-补差价金额
//                            break;
//                        case 9:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 商品销量统计报表-按商品统计
     * @param array $params <p>
     *  string keywords 店铺关键字(店铺名称/店铺编号)
     *  int goods_cat_id1 商品商城一级分类id
     *  int goods_cat_id2 商品商城二级分类id
     *  int goods_cat_id3 商品商城三级分类id
     *  int model_type 模式(1:列表模式 2:图标模式)
     * </p>
     * @param array $report_list 报表日期列表
     * @return array
     * */
    public function saleReportToGoods(array $params)
    {
        $keywords = $params['keywords'];
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $goods_keywords = $params['goods_keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $model_type = $params['model_type'];
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $relation_where = " relation.is_delete = 0 ";
        $relation_where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        //$relation_where .= " and relation.data_type IN(1,4,5) ";//商品销量,目前处理了订单,退货单,换货单
        $relation_where .= " and relation.data_type=1 ";//商品销量,目前处理了订单
        if (!empty($goods_cat_id1)) {
            $relation_where .= " and goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $relation_where .= " and goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $relation_where .= " and goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $relation_where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        if (!empty($keywords)) {
            $relation_where .= " and (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%') ";
        }
        $field = 'relation.id,relation.report_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_id,relation.goods_paid_price,goods_paid_price_total,relation.refund_money,relation.report_date,relation.is_return_goods,relation.return_goods_num,relation.goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price';
        $field .= ',shop.shopName,shop.shopSn';
        $field .= ',goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $relation_list = (array)$report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->join("left join {$table_prefix}goods as goods on goods.goodsId=relation.goods_id")
            ->where($relation_where)
            ->field($field)
            ->select();
        if ($model_type == 1) {//列表模式
            $response_data = $this->saleReportToGoodsToList($relation_list, $params);
        } else {//图表模式
            $response_data = $this->saleReportToGoodsToChart($relation_list);
        }
        return $response_data;
    }

    /**
     * 商品销量统计报表-按商品统计-列表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToGoodsToList(array $relation_list, $attach = array())
    {
        $page = $attach['page'];
        $page_size = $attach['page_size'];
        $relation_id_arr = array_unique(array_column($relation_list, 'id'));
        $relation_id_str = implode(',', $relation_id_arr);
        $sales_order_id_arr = array_unique(array_column($relation_list, 'order_id'));
        $sales_order_num = count($sales_order_id_arr);//订单总量
        $goods_sale_num = 0;//商品销售数量
        $goods_sale_money = 0;//商品销售金额
        $goods_return_money = 0;//商品退货金额
        $goods_exchange_input_num = 0;//商品换货退回数量
        $goods_exchange_out_num = 0;//商品换货换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $actual_money = 0;//实际金额
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $goods_cat_module = new GoodsCatModule();
        $where = array(
            'id' => array('IN', $relation_id_str),
        );
        $field = 'goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->join("left join {$table_prefix}shops shop on relation.shop_id=shop.shopId")
            ->where($where)
            ->field($field)
            ->group('goods_id')
            ->buildSql();
        if ($attach['export'] != 1) {
            $goods_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_data = array();
            $goods_data['root'] = $this->query($sql);
        }
        $goods_list = (array)$goods_data['root'];
        $goods_cat_id_arr = array();//商品商城分类id
        foreach ($goods_list as &$goods_detail) {
            $goods_cat_id_arr[] = $goods_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId3'];
            $curr_order_id_arr = array();//当前商品关联的订单id
            $curr_sale_num = 0;//当前商品销售数量
            $curr_sale_money = 0;//当前商品销售金额
            $curr_refund_money = 0;//当前商品退货金额
            $curr_return_goods_num = 0;//当前商品退货数量
            $curr_exchange_input_num = 0;//换货-当前商品退回商品数量
            $curr_exchange_out_num = 0;//换货-当前商品换出商品数量
            $curr_exchange_diff_money = 0;//换货-当前商品换货补差价金额 PS:存在正负值,正数用户补给商家,负数为商家补给用户
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goods_id'] != $goods_detail['goodsId']) {
                    continue;
                }
                $curr_order_id_arr[] = $relation_detail['order_id'];
                $curr_sale_num += (float)$relation_detail['goods_num'];
                $curr_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_refund_money += (float)$relation_detail['refund_money'];
                $curr_return_goods_num += (float)$relation_detail['return_goods_num'];
                $curr_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
            }
            $curr_order_num = (int)count(array_unique($curr_order_id_arr));//当前商品订单数量
            $curr_actual_money = (float)(((float)$curr_sale_money - (float)$curr_refund_money) - (float)$curr_exchange_diff_money);//当前商品实际金额 = 销售金额-退回金额+补差价金额
            $goods_detail['order_num'] = formatAmount($curr_order_num, 0);
            $goods_detail['sale_num'] = formatAmount($curr_sale_num, 0);
            $goods_detail['sale_money'] = formatAmount($curr_sale_money);
            $goods_detail['return_goods_num'] = formatAmount($curr_return_goods_num, 0);
            $goods_detail['return_goods_money'] = formatAmount($curr_refund_money);
            $goods_detail['exchange_input_num'] = formatAmount($curr_exchange_input_num, 0);
            $goods_detail['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);
            $goods_detail['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);
            $goods_detail['actual_money'] = formatAmount($curr_actual_money);

            $goods_sale_num += $curr_sale_num;
            $goods_sale_money += $curr_sale_money;
            $goods_return_money += $curr_refund_money;
            $goods_exchange_input_num += $curr_exchange_input_num;
            $goods_exchange_out_num += $curr_exchange_out_num;
            $goods_exchange_diff_money += $curr_exchange_diff_money;
            $actual_money += $curr_actual_money;
        }
        unset($goods_detail);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_list as &$goods_detail) {//获取商品分类信息
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                    $goods_detail['goodsCatName1'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                    $goods_detail['goodsCatName2'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                    $goods_detail['goodsCatName3'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($goods_detail);
        $goods_data['root'] = $goods_list;
        $goods_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//订单总量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//商品销售数量
            'goods_sale_money' => formatAmount($goods_sale_money),//商品销售金额
            'goods_return_num' => formatAmount($goods_return_money, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//换货-商品退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//换货-商品换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-补差价金额
            'actual_money' => formatAmount($actual_money)//实际金额
        );
        return $goods_data;
    }

    /**
     * 商品销量统计报表-按商品统计-图标模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToGoodsToChart(array $relation_list)
    {
        $sale_goods_list = array();//订货商品列表
        $return_goods_list = array();//退货商品列表
        $exchange_goods_list = array();//换货商品数据
        $return_goods_money = 0;//商品退货总金额
        $sale_goods_money = 0;//商品订货总金额
        $sale_goods_num = 0;//订货商品总数量
        $return_goods_num = 0;//退货商品总数量
        $goods_exchange_out_num = 0;//换货-换出商品总数量
        $goods_exchange_input_num = 0;//换货-退回商品总数量
        $goods_exchange_diff_money = 0;//换货-换货补差价金额
        foreach ($relation_list as $relation_detail) {//拼接商品列表数据
            $goods_id = $relation_detail['goodsId'];
            $goods_paid_price = (float)$relation_detail['goods_paid_price'];//当前单商品实付金额
            $current_order_goods_num = (int)$relation_detail['goods_num'];//当前订单购买该商品的数量
            $current_order_return_goods_num = (int)$relation_detail['return_goods_num'];//当前订单已退货该商品的数量
            $curr_goods_exchange_out_num = (int)$relation_detail['exchange_goods_out_num'];//当前订单该商品换出数量
            $curr_goods_exchange_input_num = (int)$relation_detail['exchange_goods_input_num'];//当前订单该商品换出数量
            $curr_goods_exchange_diff_price = (float)$relation_detail['exchange_diff_price'];//当前订单该商品补差价金额
            $surplus_order_goods_num = (int)bc_math($current_order_goods_num, $current_order_return_goods_num, 'bcsub', 0);//当前订单该商品剩余订货数量
            $current_order_return_goods_money = (float)bc_math($goods_paid_price, $current_order_return_goods_num, 'bcmul', 2);//当前订单已退货商品的金额
            $surplus_order_goods_money = (float)bc_math($goods_paid_price, $surplus_order_goods_num, 'bcmul', 2);//当前订单该商品剩余订货金额
            $sale_goods_money += $surplus_order_goods_money;
            $return_goods_money += $current_order_return_goods_money;
            $sale_goods_num += $surplus_order_goods_num;
            $return_goods_num += $current_order_return_goods_num;
            $goods_exchange_out_num += $curr_goods_exchange_out_num;
            $goods_exchange_input_num += $curr_goods_exchange_input_num;
            $goods_exchange_diff_money += $curr_goods_exchange_diff_price;
            //订货商品
            if ($relation_detail['is_return_goods'] != 2) {//只处理未全部退货的商品
                if (!isset($sale_goods_list[$goods_id])) {
                    //新增
                    $sale_goods_list[$goods_id] = array(
                        'goodsId' => formatAmount($goods_id, 0),
                        'goodsName' => $relation_detail['goodsName'],
                        'sale_money' => formatAmount($surplus_order_goods_money),//当前商品订货总金额
                        'sale_num' => formatAmount($surplus_order_goods_num, 0),//当前商品订货总数量
                        'percent' => '',//比例 = 当前商品订货总金额/订货总金额*100
                    );
                } else {
                    //已存在直接更新商品的总订货价
                    $current_sale_money = bc_math($sale_goods_list[$goods_id]['sale_money'], $surplus_order_goods_money, 'bcadd', 2);
                    $sale_goods_list[$goods_id]['sale_money'] = formatAmount($current_sale_money);//当前商品订货总金额
                    $current_sale_num = bc_math($sale_goods_list[$goods_id]['sale_num'], $surplus_order_goods_num, 'bcadd', 2);
                    $sale_goods_list[$goods_id]['sale_num'] = formatAmount($current_sale_num, 0);//当前商品订货总数量
                }
            }
            //退货商品
            if ($relation_detail['is_return_goods'] != 0) {
                if (!isset($return_goods_list[$goods_id])) {
                    //新增
                    $return_goods_list[$goods_id] = array(
                        'goodsId' => $goods_id,
                        'goodsName' => $relation_detail['goodsName'],
                        'return_money' => formatAmount($current_order_return_goods_money),//当前商品退货总金额
                        'return_sum' => formatAmount($current_order_return_goods_num, 0),//当前商品退货总数量
                        'percent' => '',//比例 = 当前商品退货总金额/退货总金额*100
                    );
                } else {
                    //已存在直接更新商品的退货总金额
                    $current_return_money = bc_math($return_goods_list[$goods_id]['return_money'], $current_order_return_goods_money, 'bcadd', 2);
                    $return_goods_list[$goods_id]['return_money'] = formatAmount($current_return_money);//当前商品退货总金额
                    $current_return_sum = bc_math($return_goods_list[$goods_id]['return_num'], $current_order_return_goods_num, 'bcadd', 2);
                    $return_goods_list[$goods_id]['return_num'] = formatAmount($current_return_sum, 0);//当前商品订货总数量
                }
            }
            //换货-商品换出列表
            if ($relation_detail['is_exchange_goods'] != 0) {
                if (!isset($exchange_goods_list[$goods_id])) {
                    //新增
                    $exchange_goods_list[$goods_id] = array(
                        'goodsId' => $goods_id,
                        'goodsName' => $relation_detail['goodsName'],
                        'exchange_diff_money' => formatAmount($curr_goods_exchange_diff_price),//当前商品换货补差价金额
                        'exchange_out_num' => formatAmount($curr_goods_exchange_out_num, 0),//当前商品换出总数量
                        'percent' => '',//比例 = 当前商品换货补差价金额/换货补差价总金额*100
                    );
                } else {
                    //已存在直接更新商品的总订货价
                    $curr_exchange_diff_money = bc_math($exchange_goods_list[$goods_id]['exchange_diff_money'], $curr_goods_exchange_diff_price, 'bcadd', 2);
                    $exchange_goods_list[$goods_id]['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);//当前商品换货补差价金额
                    $curr_exchange_out_num = bc_math($exchange_goods_list[$goods_id]['exchange_out_num'], $curr_goods_exchange_out_num, 'bcadd', 2);
                    $exchange_goods_list[$goods_id]['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);//当前商品换货补价差数量
                }
            }
        }
        foreach ($sale_goods_list as &$goods_detail) {//处理销售商品金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['sale_money'], $sale_goods_money, 'bcdiv', 4) * 100) . '%';
        }
        unset($goods_detail);
        foreach ($return_goods_list as &$goods_detail) {//处理退货商品退货金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['return_money'], $return_goods_money, 'bcdiv', 4) * 100) . '%';;
        }
        unset($goods_detail);
        foreach ($exchange_goods_list as &$goods_detail) {//处理换货商品补差价金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['exchange_diff_money'], $goods_exchange_diff_money, 'bcdiv', 4) * 100) . '%';;
        }
        unset($goods_detail);
        $response_data = array(
            'sale_goods_list' => array_values($sale_goods_list),//订货商品列表
            'return_goods_list' => array_values($return_goods_list),//退货商品列表
            'exchange_goods_list' => array_values($exchange_goods_list),//退货商品列表
            'sale_goods_money' => formatAmount($sale_goods_money),//订货商品总金额
            'sale_goods_num' => formatAmount($sale_goods_num),//订货商品总数量
            'return_goods_num' => formatAmount($return_goods_num),//退货商品总数量
            'return_goods_money' => formatAmount($return_goods_money),//退货商品总金额
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num),//换货-商品换出总数量
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num),//换货-商品退回总数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-换货补差价金额
        );
        return $response_data;
    }

    /**
     * 商品销量统计报表-按分类统计
     * @param array $params <p>
     *  string keywords 店铺关键字(店铺名称/店铺编号)
     *  int goods_cat_id1 商品商城一级分类id
     *  int goods_cat_id2 商品商城二级分类id
     *  int goods_cat_id3 商品商城三级分类id
     *  int model_type 模式(1:列表模式 2:图标模式)
     * </p>
     * @param array $report_list 报表日期列表
     * @return array
     * */
    public function saleReportToCat(array $params)
    {
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $model_type = $params['model_type'];
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $relation_where = array();
        $relation_where['relation.report_date'] = array('between', array("{$start_date}", "{$end_date}"));
//        $relation_where['relation.data_type'] = array('IN', '1,4,5');//商品销量,目前仅仅处理和销售订单相关的数据
        $relation_where['relation.data_type'] = 1;//商品销量,目前仅仅处理和销售订单相关的数据
        $relation_where['relation.is_delete'] = 0;
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId1'] = $goods_cat_id1;
        }
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId2'] = $goods_cat_id2;
        }
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId3'] = $goods_cat_id3;
        }
        $keywords = $params['keywords'];
        if (!empty($keywords)) {
            $relation_where['_string'] = " (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = 'relation.id,relation.report_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_id,relation.goods_paid_price,goods_paid_price_total,relation.refund_money,relation.report_date,relation.is_return_goods,relation.return_goods_num,relation.goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.goods_cat_id1 as goodsCatId1,relation.goods_cat_id2 as goodsCatId2,relation.goods_cat_id3 as goodsCatId3';
        $relation_list = (array)$report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods_cats as cats on cats.catId=relation.goods_cat_id3")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($relation_where)
            ->field($field)
            ->select();
        if ($model_type == 1) {//列表模式
            $response_data = $this->saleReportToCatToList($relation_list, $params);
        } else {//图表模式
            $response_data = $this->saleReportToCatToChart($relation_list);
        }
        return $response_data;
    }

    /**
     * 商品销量统计报表-按分类统计-列表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $params
     * @return array
     * */
    public function saleReportToCatToList(array $relation_list, $params)
    {
        $keywords = $params['keywords'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_id_arr = array_unique(array_column($relation_list, 'id'));
        $relation_id_str = implode(',', $relation_id_arr);
        $order_id_arr = array_unique(array_column($relation_list, 'order_id'));
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.id' => array('IN', $relation_id_str),
        );
        if (!empty($keywords)) {
            $where['_string'] = " (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = 'relation.goods_cat_id1 as goodsCatId1,relation.goods_cat_id2 as goodsCatId2,relation.goods_cat_id3 as goodsCatId3';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods_cats cats on cats.catId=relation.goods_cat_id3")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_cat_id3')
            ->buildSql();
        if ($params['export'] != 1) {
            $goods_cat_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_cat_data = array();
            $goods_cat_data['root'] = $this->query($sql);
        }
        $goods_cat_list = (array)$goods_cat_data['root'];
        $goods_cat_id_arr = array();//商品商城分类id
        $sales_order_num = count($order_id_arr);//订单总量
        $goods_sale_num = 0;//商品销售总数量
        $goods_sale_money = 0;//商品销售总金额
        $goods_return_num = 0;//商品退货总数量
        $goods_return_money = 0;//商品退货总金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货-补差价金额
        //$actual_money = 0;//实际金额 = 销售金额-退货金额+换货补差价金额
        foreach ($goods_cat_list as &$cat_detail) {
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId1'];
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId2'];
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId3'];
            $curr_order_id_arr = array();//当前分类订单id
            $curr_sale_num = 0;//当前分类商品销售数量
            $curr_sale_money = 0;//当前分类商品销售金额
            $curr_return_num = 0;//当前分类商品退货数量
            $curr_return_money = 0;//当前分类商品退货金额
            $curr_exchange_input_num = 0;//当前分类商品换货-退回数量
            $curr_exchange_out_num = 0;//当前分类商品换货-换出数量
            $curr_exchange_diff_money = 0;//当前分类商品换货-补差价金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goodsCatId3'] != $cat_detail['goodsCatId3']) {
                    continue;
                }
                $curr_order_id_arr[] = $relation_detail['order_id'];
                $curr_sale_num += (float)$relation_detail['goods_num'];
                $curr_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_return_num += (float)$relation_detail['return_goods_num'];
                $curr_return_money += (float)$relation_detail['refund_money'];
                $curr_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
            }
            $curr_order_num = count(array_unique($curr_order_id_arr));
            $curr_actual_money = (float)(((float)$curr_sale_money - (float)$curr_return_money) + (float)$curr_exchange_diff_money);//当前分类商品实际总金额 = 销售金额-退回金额+换货补差价金额
            $cat_detail['order_num'] = formatAmount($curr_order_num, 0);
            $cat_detail['sale_num'] = formatAmount($curr_sale_num, 0);
            $cat_detail['sale_money'] = formatAmount($curr_sale_money);
            $cat_detail['return_goods_num'] = formatAmount($curr_return_num, 0);
            $cat_detail['return_goods_money'] = formatAmount($curr_return_money);
            $cat_detail['exchange_input_num'] = formatAmount($curr_exchange_input_num, 0);
            $cat_detail['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);
            $cat_detail['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);
            $cat_detail['actual_money'] = formatAmount($curr_actual_money);
            $goods_sale_num += $curr_sale_num;
            $goods_sale_money += $curr_sale_money;
            $goods_return_num += $curr_return_num;
            $goods_return_money += $curr_return_money;
            $goods_exchange_input_num += $curr_exchange_input_num;
            $goods_exchange_out_num += $curr_exchange_out_num;
            $goods_exchange_diff_money += $curr_exchange_diff_money;
        }
        unset($cat_detail);
        $actual_money = (float)(($goods_sale_money - $goods_return_money) - $goods_exchange_diff_money);//实际金额 = 销售金额-退货金额+换货补差价金额
        $goods_cat_module = new GoodsCatModule();
        $cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as &$cat_detail) {
            foreach ($cat_list as $cat_info) {
                if ($cat_detail['goodsCatId1'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName1'] = $cat_info['catName'];
                }
                if ($cat_detail['goodsCatId2'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName2'] = $cat_info['catName'];
                }
                if ($cat_detail['goodsCatId3'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName3'] = $cat_info['catName'];
                }
            }
        }
        unset($cat_detail);
        $goods_cat_data['root'] = $goods_cat_list;
        $goods_cat_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//订单总量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//商品销售总量
            'goods_sale_money' => formatAmount($goods_sale_money),//商品销售总金额
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货总量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货总金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//换货-商品退回总数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//换货-商品换出总数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-补差价金额
            'actual_money' => formatAmount($actual_money),//实际金额
        );
        return $goods_cat_data;
    }

    /**
     * 商品销量统计报表-按商品统计-图表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToCatToChart(array $relation_list)
    {
        $goods_cat_module = new GoodsCatModule();
        $goods_cat_id_arr = array();//商品商城分类id
        $sale_goods_money = 0;//订货商品金额
        $return_goods_money = 0;//退货商品金额
        $sale_goods_num = 0;//订货商品数量
        $return_goods_num = 0;//退货商品数量
        $goods_exchange_input_num = 0;//换货-商品退回数量
        $goods_exchange_out_num = 0;//换货-商品换出数量
        $goods_exchange_diff_money = 0;//换货-商品补差价金额
        foreach ($relation_list as $relation_detail) {//拼接商品列表数据
            $goods_cat_id_arr[] = $relation_detail['goodsCatId3'];
        }
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');//三级分类列表
        $cat_sale_list = array();//销货数据
        $cat_return_list = array();//退货数据
        $cat_exchange_list = array();//换货数据
        foreach ($relation_list as &$relation_detail) {
            $relation_detail['catName'] = '';
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $relation_detail['goodsCatId3']) {
                    $relation_detail['catName'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($relation_detail);
        foreach ($relation_list as $relation_detail) {
            $goods_cat_id3 = $relation_detail['goodsCatId3'];
            $is_return_goods = $relation_detail['is_return_goods'];
            $is_exchange_goods = $relation_detail['is_exchange_goods'];
            $public_cat_detail = array(
                'catId' => $goods_cat_id3,
                'catName' => $relation_detail['catName'],
            );
            $current_goods_paid_price = (float)$relation_detail['goods_paid_price'];//单商品实付金额
            $current_goods_num = (int)$relation_detail['goods_num'];//当前商品购买数量
            $current_return_goods_num = (int)$relation_detail['return_goods_num'];//退货数量
            $current_return_goods_money = (float)bc_math($current_return_goods_num, $current_goods_paid_price, 'bcmul', 2);//当前商品退货金额
            $current_exchange_input_num = (int)$relation_detail['exchange_goods_input_num'];//换货-当前商品退回数量
            $current_exchange_out_num = (int)$relation_detail['exchange_goods_out_num'];//换货-当前商品换出数量
            $current_exchange_diff_money = (float)$relation_detail['exchange_diff_price'];//换货-当前商品换货补差价金额
            $surplus_goods_num = (int)bc_math($current_goods_num, $current_return_goods_num, 'bcsub', 0);//当前商品有效订货数量
            $surplus_goods_money = (float)bc_math($surplus_goods_num, $current_goods_paid_price, 'bcmul', 2);//当前商品有效订货金额
            if ($is_return_goods != 2) {//只处理未全部退货的数据
                if (!isset($cat_sale_list[$goods_cat_id3])) {
                    //新增
                    $cat_sale_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_sale_list[$goods_cat_id3]['sale_num'] = formatAmount($surplus_goods_num, 0);//订货商品数量
                    $cat_sale_list[$goods_cat_id3]['sale_money'] = formatAmount($surplus_goods_money);//订货商品金额
                } else {
                    //更新
                    $sale_num = (int)bc_math($cat_sale_list[$goods_cat_id3]['sale_num'], $surplus_goods_num, 'bcadd', 0);
                    $sale_money = (float)bc_math($cat_sale_list[$goods_cat_id3]['sale_money'], $surplus_goods_money, 'bcadd', 2);
                    $cat_sale_list[$goods_cat_id3]['sale_num'] = formatAmount($sale_num, 0);//订货商品数量
                    $cat_sale_list[$goods_cat_id3]['sale_money'] = formatAmount($sale_money);//订货商品金额
                }
            }
            if ($is_return_goods != 0) {//退货数据
                if (!isset($cat_return_list[$goods_cat_id3])) {
                    //新增
                    $cat_return_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_return_list[$goods_cat_id3]['return_num'] = formatAmount($current_return_goods_num, 0);//退货商品数量
                    $cat_return_list[$goods_cat_id3]['return_money'] = formatAmount($current_return_goods_money);//退货商品金额
                } else {
                    //更新
                    $return_num = (int)bc_math($cat_return_list[$goods_cat_id3]['return_num'], $current_return_goods_num, 'bcadd', 0);
                    $return_money = (float)bc_math($cat_return_list[$goods_cat_id3]['return_money'], $current_return_goods_money, 'bcadd', 2);
                    $cat_return_list[$goods_cat_id3]['return_num'] = formatAmount($return_num, 0);//退货商品数量
                    $cat_return_list[$goods_cat_id3]['return_money'] = formatAmount($return_money);//退货商品金额
                }
            }
            if ($is_exchange_goods != 0) {//换货数据
                if (!isset($cat_exchange_list[$goods_cat_id3])) {
                    //新增
                    $cat_exchange_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_exchange_list[$goods_cat_id3]['exchange_input_num'] = formatAmount($current_exchange_input_num, 0);//换货-退回商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_out_num'] = formatAmount($current_exchange_out_num, 0);//换货-换出商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_diff_money'] = formatAmount($current_exchange_diff_money);//换货-换货补差价金额
                } else {
                    //更新
                    $current_exchange_out_num = (int)bc_math([$goods_cat_id3]['exchange_input_num'], $current_exchange_input_num, 'bcadd', 0);
                    $current_exchange_out_num = (int)bc_math([$goods_cat_id3]['exchange_out_num'], $current_exchange_out_num, 'bcadd', 0);
                    $return_money = (float)bc_math($cat_exchange_list[$goods_cat_id3]['exchange_diff_money'], $current_exchange_out_num, 'bcadd', 2);
                    $cat_exchange_list[$goods_cat_id3]['exchange_out_num'] = formatAmount($return_num, 0);//换货-换出商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_diff_money'] = formatAmount($return_money);//换货-换货补差价金额
                }
            }
            $sale_goods_money += $surplus_goods_money;
            $return_goods_money += $current_return_goods_money;
            $sale_goods_num += $surplus_goods_num;
            $goods_exchange_input_num += $current_exchange_input_num;
            $goods_exchange_out_num += $current_exchange_out_num;
            $goods_exchange_diff_money += $current_exchange_diff_money;
            $return_goods_num += $current_return_goods_num;
        }
        foreach ($cat_sale_list as &$sale_detail) {//处理销售商品金额比例
            $percent = (float)bc_math($sale_detail['sale_money'], $sale_goods_money, 'bcdiv', 4);
            $sale_detail['percent'] = ($percent * 100) . '%';
        }
        unset($sale_detail);
        foreach ($cat_return_list as &$return_detail) {//处理退货商品金额比例
            $percent = (float)bc_math($return_detail['return_money'], $return_goods_money, 'bcdiv', 4);
            $return_detail['percent'] = ($percent * 100) . '%';
        }
        unset($return_detail);
        foreach ($cat_exchange_list as &$exchange_detail) {//处理换货商品金额比例
            $percent = (float)bc_math($exchange_detail['exchange_diff_money'], $goods_exchange_diff_money, 'bcdiv', 4);
            $exchange_detail['percent'] = ($percent * 100) . '%';
        }
        unset($exchange_detail);
        $response_data = array();
        $response_data['cat_sale_list'] = array_values($cat_sale_list);
        $response_data['cat_return_list'] = array_values($cat_return_list);
        $response_data['cat_exchange_list'] = array_values($cat_exchange_list);
        $response_data['sum'] = array(
            'sale_goods_num' => formatAmount($sale_goods_num, 0),//订货商品数量
            'sale_goods_money' => formatAmount($sale_goods_money),//订货商品金额
            'return_goods_num' => formatAmount($return_goods_num, 0),//商品退货数量
            'return_goods_money' => formatAmount($return_goods_money),//商品退货总金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num),//换货-商品退回总数
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num),//换货-商品退回总数
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-商品换货补差价金额
        );
        return $response_data;
    }

    /**
     * 商品销量统计报表-客户详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qmclsk
     * @param array $params <p>
     * int shop_id 门店id
     * int goods_id 商品id
     * int cat_id 分类id
     * date start_date 开始日期
     * date end_date 结束日期
     * int data_type 统计类型(1:按商品统计 2:按类型统计)
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function goodsSaleReportCustomerDetail(array $params)
    {
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $data_type = (int)$params['data_type'];
        if ($data_type == 1) {//按商品统计-客户详情
            $response_data = $this->saleReportCustomerDetailToGoods($params);
        } else {//按分类统计-客户详情
            $response_data = $this->saleReportCustomerDetailToCat($params);
        }
        $response_data['start_date'] = $start_date;
        $response_data['end_date'] = $end_date;
        return $response_data;
    }

    /**
     * 商品销量统计报表-按商品统计-客户详情
     * @param array $params <p>
     * int goods_id 商品id
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function saleReportCustomerDetailToGoods(array $params)
    {
        $goods_module = new GoodsModule();
        $goods_cat_module = new GoodsCatModule();
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $goods_id = (int)$params['goods_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $field = 'goodsId,goodsName,goodsSn,goodsCatId1,goodsCatId2,goodsCatId3,goodsSpec';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        $goods_cat_id = array($goods_detail['goodsCatId1'], $goods_detail['goodsCatId2'], $goods_detail['goodsCatId3']);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id, 'catId,catName');
        foreach ($goods_cat_list as $cat_detail) {
            if ($goods_detail['goodsCatId1'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName1'] = $cat_detail['catName'];
            }
            if ($goods_detail['goodsCatId2'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName2'] = $cat_detail['catName'];
            }
            if ($goods_detail['goodsCatId3'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName3'] = $cat_detail['catName'];
            }
        }
        $where = array(
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.goods_id' => $goods_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.is_delete' => 0,
        );
        $field = 'relation.id,users.userId,users.userName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        $customer_data = $this->pageQuery($sql, $page, $page_size);//获取客户列表信息
        $customer_list = (array)$customer_data['root'];
        $relation_where = array(
//            'data_type' => array('IN', array(1, 4, 5)),
            'data_type' => 1,
            'goods_id' => $goods_id,
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'is_delete' => 0,
        );
        $field = 'id,report_date,user_id,order_id,goods_num,goods_paid_price,goods_paid_price_total,is_return_goods,return_goods_num,goods_paid_price_total,exchange_goods_input_num,exchange_goods_out_num,exchange_diff_price';
        $relation_list = (array)$relation_model
            ->where($relation_where)
            ->field($field)
            ->select();//获取相关报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $customer_detail['goods_sale_num'] += (int)$relation_detail['goods_num'];
                $customer_detail['goods_sale_money'] += (float)$relation_detail['goods_paid_price_total'];
                $customer_detail['goods_return_num'] += (int)$relation_detail['return_goods_num'];
                $customer_detail['goods_return_money'] += (float)$relation_detail['refund_money'];
                $customer_detail['goods_exchange_input_num'] += (int)$relation_detail['exchange_goods_input_num'];
                $customer_detail['goods_exchange_out_num'] += (int)$relation_detail['exchange_goods_out_num'];
                $customer_detail['goods_exchange_diff_money'] += (float)$relation_detail['exchange_diff_price'];
                $actual_money = ((float)$relation_detail['goods_paid_price_total'] - (float)$relation_detail['refund_money'] + (float)$relation_detail['exchange_diff_price']);
                $customer_detail['actual_money'] += $actual_money;

            }
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            //处理返回的格式,避免出现格式不统一的情况
            $customer_detail['goods_sale_num'] = formatAmount($customer_detail['goods_sale_num'], 0);
            $customer_detail['goods_sale_money'] = formatAmount($customer_detail['goods_sale_money']);
            $customer_detail['goods_return_num'] = formatAmount($customer_detail['goods_return_num'], 0);
            $customer_detail['goods_return_money'] = formatAmount($customer_detail['goods_return_money']);
            $customer_detail['goods_exchange_input_num'] = formatAmount($customer_detail['goods_exchange_input_num'], 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($customer_detail['goods_exchange_out_num'], 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($customer_detail['goods_exchange_diff_money']);
            $customer_detail['actual_money'] = formatAmount($customer_detail['actual_money']);
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['goods_detail'] = $goods_detail;
        return $customer_data;
    }

    /**
     * 商品销量统计报表-按分类统计-客户详情
     * @param array $params <p>
     * string report_id_str 报表日期id,多个用英文逗号分隔
     * int cat_id 商品第三级分类id
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function saleReportCustomerDetailToCat(array $params)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $goods_cat_module = new GoodsCatModule();
        $cat_id = (int)$params['cat_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $field = 'catId,catName,parentId';
        $cat_detail = $goods_cat_module->getGoodsCatDetailById($cat_id, $field);
        $cat_detail['goodsCatId1'] = '';
        $cat_detail['goodsCatName1'] = '';
        $cat_detail['goodsCatId2'] = '';
        $cat_detail['goodsCatName2'] = '';
        $cat_detail['goodsCatId3'] = $cat_detail['catId'];
        $cat_detail['goodsCatName3'] = $cat_detail['catName'];
        if (!empty($cat_detail['parentId'])) {
            $cat2_detail = $goods_cat_module->getGoodsCatDetailById($cat_detail['parentId'], $field);
            $cat_detail['goodsCatId2'] = $cat2_detail['catId'];
            $cat_detail['goodsCatName2'] = $cat2_detail['catName'];
            if (!empty($cat2_detail['parentId'])) {
                $cat1_detail = $goods_cat_module->getGoodsCatDetailById($cat2_detail['parentId'], $field);
                $cat_detail['goodsCatId1'] = $cat1_detail['catId'];
                $cat_detail['goodsCatName1'] = $cat1_detail['catName'];
            }
        }
        $where = array(
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.goods_cat_id3' => $cat_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.is_delete' => 0,
        );
        $field = 'relation.id,users.userId,users.userName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        $customer_data = $this->pageQuery($sql, $page, $page_size);//获取客户信息列表
        $customer_list = (array)$customer_data['root'];
        $relation_where = array(
            'data_type' => array('IN', array(1, 4, 5)),
            'goods_cat_id3' => $cat_id,
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'is_delete' => 0,
        );
        $field = 'id,report_date,user_id,order_id,goods_num,goods_paid_price,goods_paid_price_total,is_return_goods,return_goods_num,goods_paid_price_total,exchange_goods_input_num,exchange_goods_out_num,exchange_diff_price';
        $relation_list = (array)$relation_model
            ->where($relation_where)
            ->field($field)
            ->select();//获取相关报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $customer_detail['goods_sale_num'] += (int)$relation_detail['goods_num'];
                $customer_detail['goods_sale_money'] += (float)$relation_detail['goods_paid_price_total'];
                $customer_detail['goods_return_num'] += (int)$relation_detail['return_goods_num'];
                $customer_detail['goods_return_money'] += (float)$relation_detail['refund_money'];
                $customer_detail['goods_exchange_input_num'] += (int)$relation_detail['exchange_goods_input_num'];
                $customer_detail['goods_exchange_out_num'] += (int)$relation_detail['exchange_goods_out_num'];
                $customer_detail['goods_exchange_diff_money'] += (float)$relation_detail['exchange_diff_price'];
                $actual_money = ((float)$relation_detail['goods_paid_price_total'] - (float)$relation_detail['refund_money'] + (float)$relation_detail['exchange_diff_price']);
                $customer_detail['actual_money'] += $actual_money;

            }
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            //处理返回的格式,避免出现格式不统一的情况
            $customer_detail['goods_sale_num'] = formatAmount($customer_detail['goods_sale_num'], 0);
            $customer_detail['goods_sale_money'] = formatAmount($customer_detail['goods_sale_money']);
            $customer_detail['goods_return_num'] = formatAmount($customer_detail['goods_return_num'], 0);
            $customer_detail['goods_return_money'] = formatAmount($customer_detail['goods_return_money']);
            $customer_detail['goods_exchange_input_num'] = formatAmount($customer_detail['goods_exchange_input_num'], 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($customer_detail['goods_exchange_out_num'], 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($customer_detail['goods_exchange_diff_money']);
            $customer_detail['actual_money'] = formatAmount($customer_detail['actual_money']);
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['cat_detail'] = $cat_detail;
        return $customer_data;
    }

    /**
     * 客户统计
     * @param int $shop_id 门店id
     * @param string $user_name 客户名称
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function customerReport(int $shop_id, $user_name, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.shop_id' => $shop_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.is_delete' => 0,
        );
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where['relation.user_id'] = 0;
            } else {
                $where['users.userName'] = array('like', "%{$user_name}%");
            }
        }
        $field = 'relation.id,users.userId,users.userName';
        //获取客户信息列表
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        if ($export != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.id,relation.report_date,relation.user_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_return_goods,relation.return_goods_num,relation.goods_paid_price_total,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.return_orders_id,relation.exchange_orders_id';
        //获取相关报表明细
        $relation_list = (array)$relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->select();
        $sales_order_num = 0;//销售订单总数
        $return_order_num = 0;//退货单总数
        $exchange_order_num = 0;//换货单总数
        $recharge_num = 0;//充值总数
//        $buy_setmeal_num = 0;//购买套餐总数
        $sales_order_money = 0;//销售订单总金额
        $return_order_money = 0;//退货单总金额
        $exchange_order_money = 0;//换货单补差价总金额
        $recharge_money = 0;//充值总金额
//        $buy_setmeal_money = 0;//购买套餐总金额
        $actual_money = 0;//实际金额
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $current_sales_order_num = array();//当前客户销售订单总数
            $current_return_order_num = array();//当前客户退货单总数
            $current_exchange_order_num = array();//当前客户换货单总数
            $current_recharge_num = 0;//当前客户充值总数
//            $current_buy_setmeal_num = 0;//当前客户购买套餐总数
            $current_sales_order_money = 0;//当前客户销售订单总金额
            $current_return_order_money = 0;//当前客户退货单总金额
            $current_exchange_order_money = 0;//当前客户换货单补差价总金额
            $current_recharge_money = 0;//当前客户充值总金额
//            $current_buy_setmeal_money = 0;//当前客户购买套餐总金额
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $data_type = (int)$relation_detail['data_type'];//业务类型(1:订单 2:会员充值 3:购买套餐 4:退货单 5:换货单) PS:目前已知业务类型
                if ($data_type == 1) {//销售订单
                    $current_sales_order_num[] = $relation_detail['order_id'];
                    $current_sales_order_money += (float)$relation_detail['goods_paid_price_total'];
                } elseif ($data_type == 2) {//会员充值
                    $current_recharge_num += 1;
                    $current_recharge_money += (float)$relation_detail['recharge_money'];
                } elseif ($data_type == 3) {//购买套餐
//                    $current_buy_setmeal_num += 1;
//                    $current_buy_setmeal_money += (float)$relation_detail['buy_setmeal_money'];
                } elseif ($data_type == 4) {//退货单
                    $current_return_order_num[] = $relation_detail['return_orders_id'];
                    $current_return_order_money += (float)$relation_detail['refund_money'];
                } elseif ($data_type == 5) {//换货单
                    $current_exchange_order_num[] = $relation_detail['exchange_orders_id'];
                    $current_exchange_order_money += (float)$relation_detail['exchange_diff_price'];
                }
            }
            $current_actual_money = bc_math($current_sales_order_money, $current_return_order_money, 'bcsub', 2);//当前客户实际金额=销售商品金额-退货金额+换货补差价金额
            $current_actual_money = bc_math($current_actual_money, $current_exchange_order_money, 'bcadd', 2);
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            $customer_detail['sales_order_num'] = formatAmount(count(array_unique($current_sales_order_num)), 0);//当前客户销售单总数量
            $customer_detail['return_order_num'] = formatAmount(count(array_unique($current_return_order_num)), 0);//当前客户退货单总数量
            $customer_detail['exchange_order_num'] = formatAmount(count(array_unique($current_exchange_order_num)), 0);//当前客户换货单总数量
            $customer_detail['recharge_num'] = formatAmount($current_recharge_num, 0);//当前客户充值总数
//            $customer_detail['buy_setmeal_num'] = formatAmount($current_buy_setmeal_num, 0);//当前客户购买套餐总数
            $customer_detail['sales_order_money'] = formatAmount($current_sales_order_money);//当前客户购买销售订单
            $customer_detail['return_order_money'] = formatAmount($current_return_order_money);//当前客户退货单总金额
            $customer_detail['exchange_order_money'] = formatAmount($current_exchange_order_money);//当前客户换货补差价总金额
            $customer_detail['recharge_money'] = formatAmount($current_recharge_money);//当前客户充值总金额
//            $customer_detail['buy_setmeal_money'] = formatAmount($current_buy_setmeal_money);//当前客户充值总金额
            $customer_detail['actual_money'] = formatAmount($current_actual_money);//当前客户实际金额
            $sales_order_num += $customer_detail['sales_order_num'];
            $return_order_num += $customer_detail['return_order_num'];
            $exchange_order_num += $customer_detail['exchange_order_num'];
            $recharge_num += $customer_detail['recharge_num'];
//            $buy_setmeal_num += $customer_detail['buy_setmeal_num'];
            $sales_order_money += $customer_detail['sales_order_money'];
            $return_order_money += $customer_detail['return_order_money'];
            $exchange_order_money += $customer_detail['exchange_order_money'];
            $recharge_money += $customer_detail['recharge_money'];
//            $buy_setmeal_money += $customer_detail['buy_setmeal_money'];
            $actual_money += $customer_detail['actual_money'];
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),
            'return_order_num' => formatAmount($return_order_num, 0),
            'exchange_order_num' => formatAmount($exchange_order_num, 0),
            'recharge_num' => formatAmount($recharge_num, 0),
//            'buy_setmeal_num' => formatAmount($buy_setmeal_num, 0),
            'sales_order_money' => formatAmount($sales_order_money),
            'return_order_money' => formatAmount($return_order_money),
            'exchange_order_money' => formatAmount($exchange_order_money),
            'recharge_money' => formatAmount($recharge_money),
//            'buy_setmeal_money' => formatAmount($buy_setmeal_money),
            'actual_money' => formatAmount($actual_money),
        );
        if ($export == 1) {
            $this->exportCustomer($customer_data);
        }
        return $customer_data;
    }

    /**
     * 客户毛利-导出
     * @param array $response_data 业务数据
     * */
    public function exportCustomer(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户统计报表';
        $excel_filename = '客户统计报表' . date('YmdHis');
        $sheet_title = array('客户名称', '订单总量', '退货单总量', '换货单总量', '充值总量', '订单总金额', '退货单总金额', '换货单补差价金额', '充值总金额', '实际金额');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'userName' => '合计',
                'sales_order_num' => $sum['sales_order_num'],
                'return_order_num' => $sum['return_order_num'],
                'exchange_order_num' => $sum['exchange_order_num'],
                'recharge_num' => $sum['recharge_num'],
                'sales_order_money' => $sum['sales_order_money'],
                'return_order_money' => $sum['return_order_money'],
                'exchange_order_money' => $sum['exchange_order_money'],
                'recharge_money' => $sum['recharge_money'],
                'actual_money' => $sum['actual_money'],
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总量
                        break;
                    case 2:
                        $cellvalue = $detail['return_order_num'];//退货单总量
                        break;
                    case 3:
                        $cellvalue = $detail['exchange_order_num'];//换货单总量
                        break;
                    case 4:
                        $cellvalue = $detail['recharge_num'];//充值总数量
                        break;
                    case 5:
                        $cellvalue = $detail['sales_order_money'];//订单总金额
                        break;
                    case 6:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 7:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价金额
                        break;
                    case 8:
                        $cellvalue = $detail['recharge_money'];//充值总金额
                        break;
                    case 9:
                        $cellvalue = $detail['actual_money'];//换货单补差价金额
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 订单统计报表
     * @param int $shop_id 门店id
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function ordersReport(string $keywords, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $report_model = new PosReportModel();
        $table_prefix = $report_model->tablePrefix;
        $where = array(
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),//只统计订单,退货单,换货单
            'is_delete' => 0
        );
        $where['_string'] = "(sales_order_num > 0 or return_order_num > 0 or exchange_order_num > 0)";
        if (!empty($keywords)) {
            $where['_string'] = "(shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = 'report_id,shop_id,report_date,return_order_num,return_order_money,exchange_order_num,exchange_order_money,exchange_cash_money,exchange_wxpay_money,exchange_alipay_money,sales_order_num,sales_order_money,sales_order_practical_money,sales_order_need_money,sales_order_cash_money,sales_order_balance_money,sales_order_wxpay_money,sales_order_alipay_money,sales_order_goods_money,sales_order_score_money,sales_order_use_score,recharge_num,recharge_wxpay_money,recharge_alipay_money,recharge_cash_money,buy_setmeal_num,buy_setmeal_wxpay_money,buy_setmeal_alipay_money,buy_setmeal_cash_money,is_delete,return_cash_money,return_wxpay_money,return_alipay_money';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $report_model
            ->alias('report')
            ->join("left join {$table_prefix}shops shop on shop.shopId=report.shop_id")
            ->where($where)
            ->field($field)
            ->order('report_date asc')
            ->buildSql();//报表日期数据
        if ($export != 1) {
            $report_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $report_data = array();
            $report_data['root'] = $this->query($sql);
        }
        $report_list = (array)$report_data['root'];
        $sales_order_num = 0;//销售订单总数
        $sales_order_use_score = 0;//销售订单积分抵扣
        $sales_order_goods_money = 0;//销售订单商品总金额
        $sales_order_need_money = 0;//销售订单应收总金额
        $sales_order_score_money = 0;//销售订单积分抵扣总金额
        $sales_order_practical_money = 0;//销售订单实收总金额
        $return_cash_money = 0;//现金退款金额
//        $recharge_wxpay_money = 0;//余额微信充值总金额
//        $recharge_alipay_money = 0;//余额支付宝充值总金额
//        $recharge_cash_money = 0;//余额现金充值总金额
//        $buy_setmeal_wxpay_money = 0;//购买套餐总金额(微信)
//        $buy_setmeal_alipay_money = 0;//购买套餐总金额(支付宝)
//        $buy_setmeal_cash_money = 0;//购买套餐总金额(现金)
        $cash_pay_money = 0;//现金支付总金额
        $balance_pay_money = 0;//余额支付总金额
        $wxpay_money = 0;//微信支付总金额
        $alipay_money = 0;//支付宝支付总金
        $return_order_num = 0;//退货单总数量
        $return_order_money = 0;//退货单总金额
        $exchange_order_num = 0;//换货单笔数
        $exchange_order_money = 0;//换货补差价金额总计
        $cash_arrival_money = 0;//现金实际到账金额
        $wxpay_arrival_money = 0;//微信实际到账金额
        $alipay_arrival_money = 0;//支付宝实际到账金额
        $income_money = 0;//实际收入
        foreach ($report_list as &$report_detail) {
            $sales_order_num += (float)$report_detail['sales_order_num'];
            $sales_order_goods_money += (float)$report_detail['sales_order_money'];
            $sales_order_need_money += (float)$report_detail['sales_order_need_money'];
            $sales_order_use_score += (float)$report_detail['sales_order_use_score'];
            $sales_order_score_money += (float)$report_detail['sales_order_score_money'];
            $sales_order_practical_money += (float)$report_detail['sales_order_practical_money'];
            $return_cash_money += (float)$report_detail['return_cash_money'];
            $wxpay_money += (float)$report_detail['sales_order_wxpay_money'];
            $alipay_money += (float)$report_detail['sales_order_alipay_money'];
            $return_order_num += (float)$report_detail['return_order_num'];
            $return_order_money += (float)$report_detail['return_order_money'];
            $exchange_order_num += (float)$report_detail['exchange_order_num'];
            $exchange_order_money += (float)$report_detail['exchange_order_money'];
            $cash_pay_money += (float)(((float)$report_detail['sales_order_cash_money'] + (float)$report_detail['exchange_cash_money']) - (float)$report_detail['return_cash_money']);
            $balance_pay_money += (float)((float)$report_detail['sales_order_balance_money']);
            $current_cash_arrival_money = ((float)$report_detail['sales_order_cash_money'] + (float)$report_detail['exchange_cash_money']) - ((float)$report_detail['return_cash_money']);//当前日期-现金实际到账金额 = (销售订单金额(现金) + 换货补差价(现金)) - (退货单金额(现金))
            $current_wxpay_arrival_money = ((float)$report_detail['sales_order_wxpay_money'] + (float)$report_detail['exchange_cash_money']) - ((float)$report_detail['return_wxpay_money']);//当前日期-微信实际到账金额 = (销售订单金额(微信) + 换货补差价(微信)) - (退货单金额(微信))
            $current_alipay_arrival_money = ((float)$report_detail['sales_order_alipay_money'] + (float)$report_detail['exchange_alipay_money']) - ((float)$report_detail['return_alipay_money']);//当前日期-支付宝实际到账金额 = (销售订单金额(支付宝) + 换货补差价(支付宝)) - (退货单金额(支付宝))
            $current_income_money = $current_cash_arrival_money + $current_wxpay_arrival_money + $current_alipay_arrival_money;//当前日期-实际收入 = 当期实际到账金额(现金 + 微信 + 支付宝)
            $report_detail['cash_arrival_money'] = formatAmount($current_cash_arrival_money);
            $report_detail['wxpay_arrival_money'] = formatAmount($current_wxpay_arrival_money);
            $report_detail['alipay_arrival_money'] = formatAmount($current_alipay_arrival_money);
            $report_detail['income_money'] = formatAmount($current_income_money);
            $cash_arrival_money += $current_cash_arrival_money;
            $wxpay_arrival_money += $current_wxpay_arrival_money;
            $alipay_arrival_money += $current_alipay_arrival_money;
            $income_money += $current_income_money;
        }
        unset($report_detail);
        $report_data['root'] = $report_list;
        $report_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//销售订单总数
            'sales_order_goods_money' => formatAmount($sales_order_goods_money),//销售订单商品总金额
            'sales_order_need_money' => formatAmount($sales_order_need_money),//销售订单应收总金额
            'sales_order_use_score' => formatAmount($sales_order_use_score),//销售订单积分抵扣
            'sales_order_score_money' => formatAmount($sales_order_score_money),//销售订单积分抵扣总金额
            'sales_order_practical_money' => formatAmount($sales_order_practical_money),//销售订单实收总金额
            'return_cash_money' => formatAmount($return_cash_money),//现金退款金额
//            'recharge_wxpay_money' => formatAmount($recharge_wxpay_money),//余额微信充值总金额
//            'recharge_alipay_money' => formatAmount($recharge_alipay_money),//余额支付宝充值总金额
//            'recharge_cash_money' => formatAmount($recharge_cash_money),//余额现金充值总金额
//            'buy_setmeal_wxpay_money' => formatAmount($buy_setmeal_wxpay_money),//购买套餐总金额(微信)
//            'buy_setmeal_alipay_money' => formatAmount($buy_setmeal_alipay_money),//购买套餐总金额(支付宝)
//            'buy_setmeal_cash_money' => formatAmount($buy_setmeal_cash_money),//购买套餐总金额(现金)
            'cash_pay_money' => formatAmount($cash_pay_money),//现金支付总金额
            'balance_pay_money' => formatAmount($balance_pay_money),//余额支付总金额
            'wxpay_money' => formatAmount($wxpay_money),//微信支付总金额
            'alipay_money' => formatAmount($alipay_money),//支付宝支付总金
            'return_order_num' => formatAmount($return_order_num, 0),//退货单总数量
            'return_order_money' => formatAmount($return_order_money),//退货单总金额额
            'exchange_order_num' => formatAmount($exchange_order_num, 0),//换货单总数
            'exchange_order_money' => formatAmount($exchange_order_money),//换货补差价金额总计
            'cash_arrival_money' => formatAmount($cash_arrival_money),//现金实际到账金额
            'wxpay_arrival_money' => formatAmount($wxpay_arrival_money),//微信实际到账金额
            'alipay_arrival_money' => formatAmount($alipay_arrival_money),//支付宝实际到账金额
            'income_money' => formatAmount($income_money),//实际收入
        );
        if ($export == 1) {//导出
            $this->exportOrdersReport($report_data);
        }
        return $report_data;
    }

    /**
     * 订单统计报表-导出
     * @param array $response_data 业务数据
     * */
    public function exportOrdersReport(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '订单统计报表';
        $excel_filename = '订单统计报表' . date('YmdHis');
        $sheet_title = array('日期', '订单总数', '订单商品总金额', '订单应收总金额', '订单实收总金额', '订单现金支付总金额', '订单余额支付总金额', '订单微信支付总金额', '订单支付宝支付总金额', '订单积分抵扣', '单积分抵扣金额', '现金实际到账金额', '微信实际到账金额', '支付宝实际到账金额', '退货单总数', '退货单总金额', '换货单总数', '换货单补差价金额', '实际收入');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S');
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'report_date' => '合计',
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'sales_order_goods_money' => $sum['sales_order_goods_money'],//商品总金额
                'sales_order_need_money' => $sum['sales_order_need_money'],//订单应收总金额
                'sales_order_score_money' => $sum['sales_order_score_money'],//订单积分抵扣总金额
                'sales_order_use_score' => $sum['sales_order_use_score'],//订单积分抵扣总金额
                'sales_order_practical_money' => $sum['sales_order_practical_money'],//订单实收总金额
                'sales_order_cash_money' => $sum['cash_pay_money'],//订单现金支付总金额
                'sales_order_balance_money' => $sum['balance_pay_money'],//订单余额支付总金额
                'return_cash_money' => $sum['return_cash_money'],//现金退款金额
                'cash_arrival_money' => $sum['cash_pay_money'],//现金支付总金额
                'balance_pay_money' => $sum['balance_pay_money'],//余额支付总金额
                'sales_order_wxpay_money' => $sum['wxpay_money'],//微信支付总金额
                'sales_order_alipay_money' => $sum['alipay_money'],//支付宝支付总金
                'return_order_num' => $sum['return_order_num'],//退货单总数量
                'return_order_money' => $sum['return_order_money'],//退货单总金额
                'exchange_order_num' => $sum['exchange_order_num'],//换货单总数
                'exchange_order_money' => $sum['exchange_order_money'],//换货补差价金额
                'cash_arrival_money' => $sum['cash_arrival_money'],//现金实际到账金额
                'wxpay_arrival_money' => $sum['wxpay_arrival_money'],//微信实际到账金额
                'alipay_arrival_money' => $sum['alipay_arrival_money'],//支付宝实际到账金额
                'income_money' => $sum['income_money'],//实际收入
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总数
                        break;
                    case 2:
                        $cellvalue = $detail['sales_order_goods_money'];//订单商品总金额
                        break;
                    case 3:
                        $cellvalue = $detail['sales_order_need_money'];//订单应收总金额
                        break;
                    case 4:
                        $cellvalue = $detail['sales_order_practical_money'];//订单实收总金额
                        break;
                    case 5:
                        $cellvalue = $detail['sales_order_cash_money'];//订单现金支付总金额
                        break;
                    case 6:
                        $cellvalue = $detail['sales_order_balance_money'];//订单余额支付总金额
                        break;
                    case 7:
                        $cellvalue = $detail['sales_order_wxpay_money'];//订单微信支付总金额
                        break;
                    case 8:
                        $cellvalue = $detail['sales_order_alipay_money'];//订单支付宝支付总金额
                        break;
                    case 9:
                        $cellvalue = $detail['sales_order_use_score'];//订单积分抵扣
                        break;
                    case 10:
                        $cellvalue = $detail['sales_order_score_money'];//订单积分抵扣金额
                        break;
                    case 11:
                        $cellvalue = $detail['cash_arrival_money'];//现金实际到账金额
                        break;
                    case 12:
                        $cellvalue = $detail['wxpay_arrival_money'];//微信实际到账金额
                        break;
                    case 13:
                        $cellvalue = $detail['alipay_arrival_money'];//支付宝实际到账金额
                        break;
                    case 14:
                        $cellvalue = $detail['return_order_num'];//退货单总数
                        break;
                    case 15:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 16:
                        $cellvalue = $detail['exchange_order_num'];//换货单总数
                        break;
                    case 17:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价金额
                        break;
                    case 18:
                        $cellvalue = $detail['income_money'];//实际收入
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 销售毛利统计
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * int date_type 统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * string user_name 客户名称 PS:仅按客户统计时需要
     * int page 页码
     * int page_size 分页条数
     * int export 导出 (0:导出 1:不导出)
     * </p>
     * @return array
     * */
    public function saleGrossProfit(array $params)
    {
        $data_type = (int)$params['data_type'];
        if ($data_type == 1) {//按商品统计
            $response_data = $this->saleGrossProfitToGoods($params);
        } elseif ($data_type == 2) {//按分类统计
            $response_data = $this->saleGrossProfitToCat($params);
        } else {//按客户统计
            $response_data = $this->saleGrossProfitToCustomer($params);
        }
        if ($params['export'] == 1) {//导出
            $this->exportSaleGrossProfit($response_data, $params);
        }
        return $response_data;
    }

    /**
     * 销售毛利-导出
     * @param array $response_data
     * @param array $attach 附加参数
     * */
    public function exportSaleGrossProfit(array $response_data, array $attach)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '销售毛利';
        $excel_filename = '销售毛利' . date('YmdHis');
        $data_type = $attach['data_type'];//统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
        if ($data_type == 1) {//按商品统计
//            $sheet_title = array('商品名称', '商品编码', '所属分类', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
            $sheet_title = array('商品名称', '商品编码', '所属分类', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        } elseif ($data_type == 2) {//按分类统计
//            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        } elseif ($data_type == 3) {//按客户统计
//            $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
            $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        }
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $end_list_data = array(
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'goods_sale_num' => $sum['goods_sale_num'],//销售数量
                'goods_sale_money' => $sum['goods_sale_money'],//销售金额
                'goods_cost_money' => $sum['goods_cost_money'],//成本金额
//                'goods_return_num' => $sum['goods_return_num'],//退货数量
//                'goods_return_money' => $sum['goods_return_money'],//退货金额
//                'goods_return_cost_money' => $sum['goods_return_cost_money'],//退货成本
//                'goods_exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回数量
//                'goods_exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出数量
//                'goods_exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货补差价金额
//                'goods_exchange_cost_money' => $sum['goods_exchange_cost_money'],//换货补差价成本金额
//                'actual_money' => $sum['actual_money'],//实际金额
//                'actual_cost_money' => $sum['actual_cost_money'],//实际成本金额
                'gross_profit' => $sum['gross_profit'],//毛利
            );
            if ($data_type == 1) {//按商品统计
                $end_list_data['goodsName'] = '合计';
            } elseif ($data_type == 2) {//按分类统计
                $end_list_data['catName'] = '合计';
            } elseif ($data_type == 3) {//按客户名统计
                $end_list_data['userName'] = '合计';
            }
            $list[] = $end_list_data;
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                //这里每种类型分开写,避免后面字段变动
                $cellvalue = '';
                if ($data_type == 1) {//按商品统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['goodsName'];//商品名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['goodsSn'];//商品编码
//                            break;
//                        case 2:
//                            if (!empty($detail['goodsCatName1'])) {
//                                $cellvalue = $detail['goodsCatName1'] . '/' . $detail['goodsCatName2'] . '/' . $detail['goodsCatName3'];//所属分类
//                            }
//                            break;
//                        case 3:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 12:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 15:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 16:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 17:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
//                    }
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsName'];//商品名称
                            break;
                        case 1:
                            $cellvalue = $detail['goodsSn'];//商品编码
                            break;
                        case 2:
                            if (!empty($detail['goodsCatName1'])) {
                                $cellvalue = $detail['goodsCatName1'] . '/' . $detail['goodsCatName2'] . '/' . $detail['goodsCatName3'];//所属分类
                            }
                            break;
                        case 3:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 4:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 5:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 6:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 7:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 8:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                } elseif ($data_type == 2) {//按分类统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['catName'];//分类名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 2:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 3:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 12:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 15:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
//                    }

                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['catName'];//分类名称
                            break;
                        case 1:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 4:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 5:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 6:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                } elseif ($data_type == 3) {//按客户统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['userName'];//客户名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 2:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 3:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 12:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 15:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['userName'];//客户名称
                            break;
                        case 1:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 4:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 5:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 6:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 销售毛利统计-按商品统计
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToGoods(array $params)
    {
        $keywords = $params['keywords'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $goods_keywords = $params['goods_keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5)";
        $where = " relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($goods_cat_id1)) {
            $where .= " goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $where .= " goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $where .= " goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        if (!empty($keywords)) {
            $where .= " and (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = 'relation.id,goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $relation_model
            ->alias('relation')
            ->join(" left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->join(" left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_id')
            ->buildSql();//商品列表信息
        if ($params['export'] != 1) {
            $goods_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_data = array();
            $goods_data['root'] = $this->query($sql);
        }
        $goods_list = $goods_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->join(" left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->join(" left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        $goods_cat_id_arr = array();
        foreach ($goods_list as &$goods_detail) {
            $goods_id = $goods_detail['goodsId'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId3'];
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_goods_id = $relation_detail['goods_id'];
                if ($goods_id != $current_goods_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (int)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_diff_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            }
            $goods_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $goods_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $goods_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $goods_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $goods_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $goods_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $goods_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $goods_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $goods_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $goods_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货-换货补差价金额
            $goods_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $goods_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $goods_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $goods_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $goods_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $sales_order_num[] = $relation_detail['order_id'];
            $goods_sale_num += (int)$goods_detail['goods_sale_num'];
            $goods_sale_money += (float)$goods_detail['goods_sale_money'];
            $goods_cost_money += (float)$goods_detail['goods_cost_money'];
            $goods_return_num += (int)$goods_detail['goods_return_num'];
            $goods_return_money += (float)$goods_detail['goods_return_money'];
            $goods_return_cost_money += (float)$goods_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$goods_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$goods_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$goods_detail['goods_exchange_cost_money'];
            $actual_money += (float)$goods_detail['actual_money'];
            $actual_cost_money += (float)$goods_detail['actual_cost_money'];
            $gross_profit += (float)$goods_detail['gross_profit'];
        }
        unset($goods_detail);
        $goods_cat_module = new GoodsCatModule();
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_list as &$goods_detail) {
            $goods_detail['goodsCatName1'] = '';
            $goods_detail['goodsCatName2'] = '';
            $goods_detail['goodsCatName3'] = '';
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                    $goods_detail['goodsCatName1'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                    $goods_detail['goodsCatName2'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                    $goods_detail['goodsCatName3'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($goods_detail);
        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退货数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $goods_data['root'] = $goods_list;
            $goods_data['sum'] = $sum;
            $response_data = $goods_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利统计-按分类统计
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToCat(array $params)
    {
        //$shop_id = $params['shop_id'];
        $keywords = $params['keywords'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5)";
        $where = " relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($goods_cat_id1)) {
            $where .= " goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $where .= " goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $where .= " goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        if (!empty($keywords)) {
            $where .= " and (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = 'goods_cat_id1 as goodsCatId1,goods_cat_id2 as goodsCatId2,goods_cat_id3 as goodsCatId3 ';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_cat_id3')
            ->buildSql();//第三级分类列表数据
        if ($params['export'] != 1) {
            $goods_cat_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_cat_data = array();
            $goods_cat_data['root'] = $this->query($sql);
        }
        $goods_cat_list = (array)$goods_cat_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        $goods_cat_id_arr = array();
        foreach ($goods_cat_list as &$cat_detail) {
            $goods_cat_id_arr[] = $cat_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $cat_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $cat_detail['goodsCatId3'];
            $cat_detail['catId'] = $cat_detail['goodsCatId3'];
            $cat_detail['goodsCatName1'] = '';
            $cat_detail['goodsCatName2'] = '';
            $cat_detail['goodsCatName3'] = '';
            $cat_detail['catName'] = '';
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_cat_id = $relation_detail['goods_cat_id3'];
                if ($cat_detail['goodsCatId3'] != $current_cat_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (int)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (int)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (int)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_diff_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            }
            $cat_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $cat_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $cat_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $cat_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $cat_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $cat_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $cat_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $cat_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $cat_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $cat_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货金额
            $cat_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $cat_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $cat_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $cat_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $cat_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $sales_order_num[] = $relation_detail['order_id'];
            $goods_sale_num += (int)$cat_detail['goods_sale_num'];
            $goods_sale_money += (float)$cat_detail['goods_sale_money'];
            $goods_cost_money += (float)$cat_detail['goods_cost_money'];
            $goods_return_num += (int)$cat_detail['goods_return_num'];
            $goods_return_money += (float)$cat_detail['goods_return_money'];
            $goods_return_cost_money += (float)$cat_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$cat_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$cat_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$cat_detail['goods_exchange_diff_money'];
            $goods_exchange_cost_money += (float)$cat_detail['goods_exchange_cost_money'];
            $actual_money += (float)$cat_detail['actual_money'];
            $actual_cost_money += (float)$cat_detail['actual_cost_money'];
            $gross_profit += (float)$cat_detail['gross_profit'];
        }
        unset($cat_detail);
        $goods_cat_module = new GoodsCatModule();
        $cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as &$cat_detail) {
            foreach ($cat_list as $detail) {
                if ($cat_detail['goodsCatId1'] == $detail['catId']) {
                    $cat_detail['goodsCatName1'] = (string)$detail['catName'];
                }
                if ($cat_detail['goodsCatId2'] == $detail['catId']) {
                    $cat_detail['goodsCatName2'] = (string)$detail['catName'];
                }
                if ($cat_detail['catId'] == $detail['catId']) {
                    $cat_detail['goodsCatName3'] = (string)$detail['catName'];
                }
                $cat_detail['catName'] = $cat_detail['goodsCatName1'] . '/' . $cat_detail['goodsCatName2'] . '/' . $cat_detail['goodsCatName3'];
            }
        }
        unset($cat_detail);
        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money, 0),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $goods_cat_data['root'] = $goods_cat_list;
            $goods_cat_data['sum'] = $sum;
            $response_data = $goods_cat_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利统计-按客户统计
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * string userName 客户名称
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToCustomer(array $params)
    {
        //$shop_id = $params['shop_id'];
        $keywords = $params['keywords'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $user_name = $params['user_name'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
        $m = new Model();
        $table_prefix = $m->tablePrefix;
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5) ";
        $where = " relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where .= " and relation.user_id=0 ";
            } else {
                $where .= " and users.userName like '%{$user_name}%' ";
            }
        }
        if (!empty($keywords)) {
            $where .= " and (shop.shopName like '%{$keywords}%' or shop.shopSn like '%{$keywords}%')";
        }
        $field = ' users.userId,users.userName ';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->group('relation.user_id')
            ->buildSql();//第三级分类列表数据
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $user_name = (string)$customer_detail['userName'];
            if (empty($curr_user_id)) {
                $user_name = '游客';
            }
            $customer_detail['userId'] = $user_id;
            $customer_detail['userName'] = $user_name;
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货-换货补差价金额
            $curr_goods_exchange_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (int)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (int)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            }
            $customer_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $customer_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $customer_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $customer_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $customer_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $customer_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $customer_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $customer_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $customer_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $customer_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货金额
            $customer_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $customer_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $customer_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $customer_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $customer_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $sales_order_num[] = $relation_detail['order_id'];
            $goods_sale_num += (int)$customer_detail['goods_sale_num'];
            $goods_sale_money += (float)$customer_detail['goods_sale_money'];
            $goods_cost_money += (float)$customer_detail['goods_cost_money'];
            $goods_return_num += (int)$customer_detail['goods_return_num'];
            $goods_return_money += (float)$customer_detail['goods_return_money'];
            $goods_return_cost_money += (float)$customer_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$customer_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$customer_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$customer_detail['goods_exchange_diff_money'];
            $goods_exchange_cost_money += (float)$customer_detail['goods_exchange_cost_money'];
            $actual_money += (float)$customer_detail['actual_money'];
            $actual_cost_money += (float)$customer_detail['actual_cost_money'];
            $gross_profit += (float)$customer_detail['gross_profit'];
        }
        unset($customer_detail);

        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money, 0),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $customer_data['root'] = $customer_list;
            $customer_data['sum'] = $sum;
            $response_data = $customer_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利-客户详情 PS:仅支持按商品统计
     * @param array $params <p>
     * string keywords 店铺关键字(店铺名称/店铺编号)
     * date start_date 开始日期
     * date end_date 结束日期
     * int goods_id 商品id
     * int page 页码
     * int page_size 分页条数
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function saleGrossProfitCustomerDetail(array $params)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $keywords = $params['keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $goods_id = (int)$params['goods_id'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $where = array(
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.goods_id' => $goods_id,
//            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.data_type' => 1,
            'relation.is_delete' => 0,
        );
        if (!empty($keywords)) {
            $where['_string'] = " (shop.shopName like '%$keywords%' or shop.shopSn like '%$keywords%') ";
        }
        $field = 'users.userId,users.userName';
        $field .= ',shop.shopName,shop.shopSn';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->group('relation.user_id')
            ->buildSql();
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $field .= ',users.userId,users.userName';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->join("left join {$table_prefix}shops shop on shop.shopId=relation.shop_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $user_name = (string)$customer_detail['userName'];
            if (empty($user_id)) {
                $user_name = '游客';
            }
            $customer_detail['userId'] = $user_id;
            $customer_detail['userName'] = $user_name;
            $curr_goods_sale_num = 0;//销售商品数量
            $curr_goods_sale_money = 0;//销售商品金额
            $curr_goods_cost_money = 0;//销售商品成本金额
            $curr_goods_return_num = 0;//退货数量
            $curr_goods_return_money = 0;//退货金额
            $curr_goods_return_cost_money = 0;//退货成本金额
            $curr_goods_exchange_input_num = 0;//换货-退回数量
            $curr_goods_exchange_out_num = 0;//换货-换出数量
            $curr_goods_exchange_diff_money = 0;//换货补差价金额
            $curr_goods_exchange_cost_money = 0;//换货补差价成本金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['user_id'] != $user_id) {
                    continue;
                }
                $sales_order_id_arr[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (float)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (float)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
            }
            $curr_sales_order_num = count(array_unique($sales_order_id_arr));//销售订单数量
            $curr_actual_money = (float)(((float)$curr_goods_sale_money - (float)$curr_goods_return_money) + (float)$curr_goods_exchange_diff_money);//实际金额 = 商品销售金额 - 商品退货金额 + 商品换货补差价金额
            $curr_actual_cost_money = (float)(((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money) + (float)$curr_goods_exchange_cost_money);//实际成本金额 = 商品成本金额 - 退货成本金额 + 换货补差价成本金额
            $curr_gross_profit = (float)bc_math($curr_actual_money, $curr_actual_cost_money, 'bcsub', 4);//毛利 = 实际金额 - 实际成本
            $curr_gross_profit_rate = ((float)bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['sales_order_num'] = formatAmount($curr_sales_order_num, 0);
            $customer_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);
            $customer_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);
            $customer_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);
            $customer_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);
            $customer_detail['goods_return_money'] = formatAmount($curr_goods_return_money);
            $customer_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);
            $customer_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);
            $customer_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);
            $customer_detail['actual_money'] = formatAmount($curr_actual_money);
            $customer_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);
            $customer_detail['gross_profit'] = formatAmount($curr_gross_profit);
            $customer_detail['gross_profit_rate'] = $curr_gross_profit_rate;

        }
        unset($customer_detail);
        $goods_module = new GoodsModule();
        $goods_cat_module = new GoodsCatModule();
        $field = 'goodsId,goodsName,goodsSn,goodsCatId1,goodsCatId2,goodsCatId3';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        $goods_detail['goodsCatName1'] = '';
        $goods_detail['goodsCatName2'] = '';
        $goods_detail['goodsCatName3'] = '';
        $goods_cat_id_arr = array($goods_detail['goodsCatId1'], $goods_detail['goodsCatId2'], $goods_detail['goodsCatId3']);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as $cat_detail) {
            if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                $goods_detail['goodsCatName1'] = $cat_detail['catName'];
            }
            if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                $goods_detail['goodsCatName2'] = $cat_detail['catName'];
            }
            if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                $goods_detail['goodsCatName3'] = $cat_detail['catName'];
            }
        }
        $customer_data['root'] = $customer_list;
        $customer_data['goods_detail'] = $goods_detail;
        if ($params['export'] == 1) {//导出
            $this->exportSaleGrossProfitCustomerDetail($customer_data);
        }
        return (array)$customer_data;
    }

    /**
     * 销售毛利-客户详情-导出
     * @param array $response_data 业务数据
     * */
    public function exportSaleGrossProfitCustomerDetail(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '销售毛利-客户详情';
        $excel_filename = '销售毛利-客户详情' . date('YmdHis');
//        $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
        $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
//                switch ($j) {
//                    case 0:
//                        $cellvalue = $detail['userName'];//客户名称
//                        break;
//                    case 1:
//                        $cellvalue = $detail['sales_order_num'];//订单数量
//                        break;
//                    case 2:
//                        $cellvalue = $detail['goods_sale_num'];//销售数量
//                        break;
//                    case 3:
//                        $cellvalue = $detail['goods_sale_money'];//销售金额
//                        break;
//                    case 4:
//                        $cellvalue = $detail['goods_cost_money'];//成本金额
//                        break;
//                    case 5:
//                        $cellvalue = $detail['goods_return_num'];//退货数量
//                        break;
//                    case 6:
//                        $cellvalue = $detail['goods_return_money'];//退货金额
//                        break;
//                    case 7:
//                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
//                        break;
//                    case 8:
//                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                        break;
//                    case 9:
//                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                        break;
//                    case 10:
//                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
//                        break;
//                    case 11:
//                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
//                        break;
//                    case 12:
//                        $cellvalue = $detail['actual_money'];//实际金额
//                        break;
//                    case 13:
//                        $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                        break;
//                    case 14:
//                        $cellvalue = $detail['gross_profit'];//毛利
//                        break;
//                    case 15:
//                        $cellvalue = $detail['gross_profit_rate'];//毛利率
//                        break;
//                }

                switch ($j) {
                    case 0:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单数量
                        break;
                    case 2:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 3:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 4:
                        $cellvalue = $detail['goods_cost_money'];//成本金额
                        break;
                    case 5:
                        $cellvalue = $detail['gross_profit'];//毛利
                        break;
                    case 6:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 客户毛利 PS:本质上其实是按照每笔订单来统计的
     * @param array $params <p>
     * int shop_id 门店id
     * date start_date 开始日期
     * date end_date 结束日期
     * string user_name 客户名称
     * string bill_no 单号
     * int page 页码
     * int page_size 分页条数
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function customerGrossProfit(array $params)
    {
        $shop_id = (int)$params['shop_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $user_name = (string)$params['user_name'];
        $bill_no = (string)$params['bill_no'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array();
        $where['relation.shop_id'] = $shop_id;
        $where['relation.data_type'] = array('IN', array(1, 4, 5));
        $where['relation.report_date'] = array('between', array($start_date, $end_date));
        $where['relation.is_delete'] = 0;
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where['relation.user_id'] = 0;
            } else {
                $where['users.userName'] = array('like', "%{$user_name}%");
            }
        }
        if (!empty($bill_no)) {
            $where['orders.orderNO'] = array('like', "%{$bill_no}%");
        }
        $field = 'relation.report_date';
        $field .= ',users.userId,users.userName';
        $field .= ',orders.id as order_id,orders.orderNO';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}pos_orders orders on orders.id=relation.order_id")
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('relation.order_id')
            ->buildSql();
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.order_id,relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $field .= ',users.userId,users.userName';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}pos_orders orders on orders.id=relation.order_id")
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($customer_list as &$customer_detal) {
            $user_id = (int)$customer_detal['userId'];
            $user_name = (string)$customer_detal['userName'];
            if (empty($user_id)) {
                $user_name = '游客';
            }
            $customer_detal['userId'] = formatAmount($user_id, 0);
            $customer_detal['userName'] = $user_name;
            $order_id = $customer_detal['order_id'];
            $goods_sale_num = 0;//商品销售数量
            $goods_sale_money = 0;//商品销售金额
            $goods_cost_money = 0;//销售成本金额
            $goods_return_num = 0;//退货数量
            $goods_return_money = 0;//退回金额
            $goods_return_cost_money = 0;//退回成本金额
            $goods_exchange_input_num = 0;//换货-退回数量
            $goods_exchange_out_num = 0;//换货-换出数量
            $goods_exchange_diff_money = 0;//换货-换货补差价金额
            $goods_exchange_cost_money = 0;//换货-换货补差价成本金额
            $actual_money = 0;//实际金额 = (销售金额-退货金额) + 补差价金额
            $actual_cost_money = 0;//实际成本金额 (实际成本-退货成本) + 换货补差价成本
//            $gross_profit = '';//毛利 = 实际金额 - 实际成本
//            $gross_profit_rate = '';//毛利率=毛利/营业收入×100%
            foreach ($relation_list as $relation_detail) {
                $current_order_id = $relation_detail['order_id'];
                if ($order_id != $current_order_id) {
                    continue;
                }
                $goods_sale_num += (float)$relation_detail['goods_num'];
                $goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $goods_return_num += (float)$relation_detail['return_goods_num'];
                $goods_return_money += (float)$relation_detail['refund_money'];
                $goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $actual_money += (float)(((float)$goods_sale_money - (float)$goods_return_money) + (float)$goods_exchange_diff_money);
                $actual_cost_money += (float)(((float)$goods_cost_money - (float)$goods_return_cost_money) + (float)$goods_exchange_cost_money);
            }
            $gross_profit = (float)$actual_money - (float)$actual_cost_money;//毛利 = 实际金额 - 实际成本
            $gross_profit_rate = ((float)bc_math($gross_profit, $actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $customer_detal['goods_sale_num'] = formatAmount($goods_sale_num, 0);
            $customer_detal['goods_sale_money'] = formatAmount($goods_sale_money);
            $customer_detal['goods_cost_money'] = formatAmount($goods_cost_money);
            $customer_detal['goods_return_num'] = formatAmount($goods_return_num, 0);
            $customer_detal['goods_return_money'] = formatAmount($goods_return_money);
            $customer_detal['goods_return_cost_money'] = formatAmount($goods_return_cost_money);
            $customer_detal['goods_exchange_input_num'] = formatAmount($goods_exchange_input_num, 0);
            $customer_detal['goods_exchange_out_num'] = formatAmount($goods_exchange_out_num, 0);
            $customer_detal['goods_exchange_diff_money'] = formatAmount($goods_exchange_diff_money);
            $customer_detal['goods_exchange_cost_money'] = formatAmount($goods_exchange_cost_money);
            $customer_detal['actual_money'] = formatAmount($actual_money);
            $customer_detal['actual_cost_money'] = formatAmount($actual_cost_money);
            $customer_detal['gross_profit'] = formatAmount($gross_profit);
            $customer_detal['gross_profit_rate'] = $gross_profit_rate;
        }
        unset($customer_detal);
        $customer_data['root'] = $customer_list;
        if ($params['export'] == 1) {//导出
            $this->exportCustomerGrossProfit($customer_data);
        }
        return $customer_data;
    }

    /**
     * 客户毛利-导出
     * @param array $response_data 业务参数
     * */
    public function exportCustomerGrossProfit(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户毛利';
        $excel_filename = '客户毛利' . date('YmdHis');
        $sheet_title = array('日期', '客户名称', '订单号', '销售数量', '销售金额', '销售成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 2:
                        $cellvalue = $detail['orderNO'];//订单号
                        break;
                    case 3:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 4:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 5:
                        $cellvalue = $detail['goods_cost_money'];//销售成本金额
                        break;
                    case 6:
                        $cellvalue = $detail['goods_return_num'];//退货数量
                        break;
                    case 7:
                        $cellvalue = $detail['goods_return_money'];//退货金额
                        break;
                    case 8:
                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
                        break;
                    case 9:
                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
                        break;
                    case 10:
                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
                        break;
                    case 11:
                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
                        break;
                    case 12:
                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
                        break;
                    case 13:
                        $cellvalue = $detail['actual_money'];//实际金额
                        break;
                    case 14:
                        $cellvalue = $detail['actual_money'];//实际成本金额
                        break;
                    case 15:
                        $cellvalue = $detail['actual_money'];//毛利
                        break;
                    case 16:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 客户毛利-详情
     * @param int $order_id 订单id
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export 导出(0:导出 1:不导出)
     * @return array
     * */
    public function customerGrossProfitToDetail(int $order_id, int $page, int $page_size, int $export)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.order_id' => $order_id
        );
        $field = 'goods.goodsId,goods.goodsName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_id')
            ->buildSql();
        $goods_data = $this->pageQuery($sql, $page, $page_size);
        $goods_list = (array)$goods_data['root'];
        $field = 'relation.order_id,relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($goods_list as &$goods_detail) {
            $goods_sale_num = 0;//销售数量
            $goods_sale_money = 0;//销售金额
            $goods_cost_money = 0;//成本金额
            $goods_return_num = 0;//退货数量
            $goods_return_money = 0;//退货金额
            $goods_return_cost_money = 0;//退货成本金额
            $goods_exchange_input_num = 0;//换货-退回数量
            $goods_exchange_out_num = 0;//换货-换出数量
            $goods_exchange_diff_money = 0;//换货-换货补差价金额
            $goods_exchange_cost_money = 0;//换货-换货补差价成本金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goods_id'] != $goods_detail['goodsId']) {
                    continue;
                }
                $goods_sale_num += (float)$relation_detail['goods_num'];
                $goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $goods_return_num += (float)$relation_detail['return_goods_num'];
                $goods_return_money += (float)$relation_detail['refund_money'];
                $goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
            }
            $actual_money = (float)(((float)$goods_sale_money - (float)$goods_return_money) + (float)$goods_exchange_diff_money);//实际金额 = (销售金额 - 退货金额) + 换货补差价金额
            $actual_cost_money = (float)(((float)$goods_cost_money - (float)$goods_return_cost_money) + (float)$goods_exchange_cost_money);//实际成本金额 = (销售成本金额 - 退货成本金额) + 换货补差价成本金额
            $gross_profit = (float)$actual_money - (float)$actual_cost_money;//毛利 = 实际金额 - 实际成本
            $gross_profit_rate = ((float)bc_math($gross_profit, $actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $goods_detail['goods_sale_num'] = formatAmount($goods_sale_num, 0);
            $goods_detail['goods_sale_money'] = formatAmount($goods_sale_money);
            $goods_detail['goods_cost_money'] = formatAmount($goods_cost_money);
            $goods_detail['goods_return_num'] = formatAmount($goods_return_num, 0);
            $goods_detail['goods_return_money'] = formatAmount($goods_return_money);
            $goods_detail['goods_return_cost_money'] = formatAmount($goods_return_cost_money);
            $goods_detail['goods_exchange_input_num'] = formatAmount($goods_exchange_input_num, 0);
            $goods_detail['goods_exchange_out_num'] = formatAmount($goods_exchange_out_num, 0);
            $goods_detail['goods_exchange_diff_money'] = formatAmount($goods_exchange_diff_money);
            $goods_detail['goods_exchange_cost_money'] = formatAmount($goods_exchange_cost_money);
            $goods_detail['actual_money'] = formatAmount($actual_money);
            $goods_detail['actual_cost_money'] = formatAmount($actual_cost_money);
            $goods_detail['gross_profit'] = formatAmount($gross_profit);
            $goods_detail['gross_profit_rate'] = $gross_profit_rate;
        }
        unset($goods_detail);
        $goods_data['root'] = $goods_list;
        if ($export == 1) {
            $this->exportCustomerGrossProfitToDetail($goods_data);
        }
        return $goods_data;
    }

    /**
     * 客户毛利-详情-导出
     * @param array $response_data 业务数据
     * */
    public function exportCustomerGrossProfitToDetail(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户毛利';
        $excel_filename = '客户毛利-详情' . date('YmdHis');
        $sheet_title = array('商品名称', '销售数量', '销售金额', '销售成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['goodsName'];//商品名称
                        break;
                    case 1:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 2:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 3:
                        $cellvalue = $detail['goods_cost_money'];//销售成本金额
                        break;
                    case 4:
                        $cellvalue = $detail['goods_return_num'];//退货数量
                        break;
                    case 5:
                        $cellvalue = $detail['goods_return_money'];//退货金额
                        break;
                    case 6:
                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
                        break;
                    case 7:
                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
                        break;
                    case 8:
                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
                        break;
                    case 9:
                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
                        break;
                    case 10:
                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
                        break;
                    case 11:
                        $cellvalue = $detail['actual_money'];//实际金额
                        break;
                    case 12:
                        $cellvalue = $detail['actual_cost_money'];//实际成本金额
                        break;
                    case 13:
                        $cellvalue = $detail['gross_profit'];//毛利
                        break;
                    case 14:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    #############################收银报表-end###################################
}
<?php

namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Models\OrdersModel;
use App\Modules\Chain\ChainServiceModule;
use App\Modules\Shops\ShopsServiceModule;
use CjsProtocol\LogicResponse;

/**
 * 悬挂链类
 */
class ChainModel extends BaseModel
{
    /**
     * 钩子-新增钩子
     * @param array $params
     * @return array
     * */
    public function addHook(array $params)
    {
        $response = LogicResponse::getInstance();
        $chain_service_module = new ChainServiceModule();
        $hook_result = $chain_service_module->getHookInfoByParams(array(
            'shop_id' => $params['shop_id'],
            'hook_code' => (string)$params['hook_code']
        ));
        if ($hook_result['code'] == ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("编码{$params['hook_code']}已存在")->toArray();
        }
        $reuslt = $chain_service_module->addHook($params);
        return $reuslt;
    }

    /**
     * 钩子-修改钩子
     * @param array $params
     * */
    public function updateHook(array $params)
    {
        $response = LogicResponse::getInstance();
        $chain_service_module = new ChainServiceModule();
        $hook_result = $chain_service_module->getHookInfoById($params['hook_id']);
        if ($hook_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $hook_result;
        }
        $hook_result = $chain_service_module->getHookInfoByParams(array(
            'shop_id' => $params['shop_id'],
            'hook_code' => (string)$params['hook_code']
        ));
        if ($hook_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $hook_data = $hook_result['data'];
            if ($hook_data['hook_id'] != $params['hook_id']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("编码{$params['hook_code']}已存在")->toArray();
            }
        }
        $reuslt = $chain_service_module->updateHook($params);
        return $reuslt;
    }

    /**
     * 钩子-获取钩子列表
     * @param array $params <p>
     * int shop_id
     * string hook_code 钩子编码
     * datetime start_date 时间区间-开始时间
     * datetime end_date 时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @return array
     * */
    public function getHookList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "shop_id={$shop_id} and is_delete=0 ";
        $where_find = array();
        $where_find['hook_code'] = function () use ($params) {
            if (empty($params['hook_code'])) {
                return null;
            }
            return array('like', "%{$params['hook_code']}%", 'and');
        };
        $where_find['create_time'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            $params['start_date'] = strtotime($params['start_date']);
            $params['end_date'] = strtotime($params['end_date']);
            return array('>=', "{$params['start_date']}' and create_time<='{$params['end_date']}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where_find} and {$where} ";
        }
        $sql = "select hook_id,hook_code,create_time from __PREFIX__hook where {$where_info} order by hook_id desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        if (empty($result['root'])) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        }
        $list = $result['root'];
        foreach ($list as &$item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        unset($item);
        $result['root'] = $list;
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 获取钩子详情
     * @param int $shop_id
     * @param int $hook_id 钩子id
     * @return array
     * */
    public function getHookInfo(int $hook_id)
    {
        $service_module = new ChainServiceModule();
        $field = 'hook_id,hook_code,create_time';
        $result = $service_module->getHookInfoById($hook_id, $field);
        return $result;
    }

    /**
     * 钩子-删除钩子
     * @param string $hook_id 钩子id,多个用英文逗号分隔
     * @return array
     * */
    public function delHook(string $hook_id)
    {
        $service_module = new ChainServiceModule();
        $result = $service_module->delHook($hook_id);
        return $result;
    }

    /**
     * 下链口-新增下链口
     * @param array $params
     * @return array
     * */
    public function addChain(array $params)
    {
        $response = LogicResponse::getInstance();
        $chain_service_module = new ChainServiceModule();
        $result = $chain_service_module->getChainInfoByParams(array(
            'shop_id' => $params['shop_id'],
            'chain_code' => (string)$params['chain_code']
        ));
        if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("编码{$params['chain_code']}已存在")->toArray();
        }
        $reuslt = $chain_service_module->addChain($params);
        return $reuslt;
    }

    /**
     * 下链口-修改下链口
     * @param array $params
     * @return array
     * */
    public function updateChain(array $params)
    {
        $response = LogicResponse::getInstance();
        $chain_service_module = new ChainServiceModule();
        $result = $chain_service_module->getChainInfoById($params['chain_id']);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $result;
        }
        $result = $chain_service_module->getChainInfoByParams(array(
            'shop_id' => $params['shop_id'],
            'chain_code' => (string)$params['chain_code']
        ));
        if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
            $data = $result['data'];
            if ($data['chain_id'] != $params['chain_id']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("编码{$params['chain_code']}已存在")->toArray();
            }
        }
        $reuslt = $chain_service_module->updateChain($params);
        return $reuslt;
    }

    /**
     * 下链口-修改下链口状态
     * @param int $chain_id 下链口id
     * @param int $status 状态(-1:禁用 1:启用)
     * @return array
     * */
    public function updateChainStatus(int $chain_id, int $status)
    {
        $chain_service_module = new ChainServiceModule();
        $save = array(
            'chain_id' => $chain_id,
            'status' => $status
        );
        $reuslt = $chain_service_module->updateChain($save);
        return $reuslt;
    }

    /**
     * 下链口-获取下链口列表
     * @param array $params <p>
     * int shop_id
     * string chain_code 下联口编码
     * int status 状态(-1:禁用 1:启用)
     * datetime start_date 时间区间-开始时间
     * datetime end_date 时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @return array
     * */
    public function getChainList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "shop_id={$shop_id} and is_delete=0 ";
        $where_find = array();
        $where_find['chain_code'] = function () use ($params) {
            if (empty($params['chain_code'])) {
                return null;
            }
            return array('like', "%{$params['chain_code']}%", 'and');
        };
        $where_find['status'] = function () use ($params) {
            if (empty($params['status'])) {
                return null;
            }
            return array('=', "{$params['status']}", 'and');
        };
        $where_find['create_time'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            $params['start_date'] = strtotime($params['start_date']);
            $params['end_date'] = strtotime($params['end_date']);
            return array('>=', "{$params['start_date']}' and create_time<='{$params['end_date']}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where_find} and {$where} ";
        }
        $sql = "select chain_id,chain_code,status,current_order_num,create_time from __PREFIX__chain where {$where_info} order by chain_id desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        if (empty($result['root'])) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        }
        $list = $result['root'];
        foreach ($list as &$item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        unset($item);
        $result['root'] = $list;
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 获取下链口详情
     * @param int $shop_id
     * @param int $chain_id 下链口id
     * @return array
     * */
    public function getChainInfo(int $chain_id)
    {
        $service_module = new ChainServiceModule();
        $field = 'chain_id,chain_code,status,current_order_num,create_time';
        $result = $service_module->getChainInfoById($chain_id, $field);
        return $result;
    }

    /**
     * 下链口-删除链口
     * @param string $chain_id 下链口id,多个用英文逗号分隔
     * @return array
     * */
    public function delChain(string $chain_id)
    {
        $service_module = new ChainServiceModule();
        $result = $service_module->delChain($chain_id);
        return $result;
    }

    /**
     * 商品绑钩记录
     * @param array $params <p>
     * int hook_id 钩子id
     * int shop_id 门店id
     * string order_no 订单号
     * string goods_name 商品名称
     * string goods_sn 商品编码
     * datetime start_date 时间区间-开始时间
     * datetime end_date 时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * */
    public function getGoodsBindHoodLogs(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $hook_id = $params['hook_id'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "orders.shopId={$shop_id} and orders.orderFlag=1 and g.goodsFlag=1 and og.hook_id = {$hook_id} ";
        $field = 'orders.orderId,orders.orderNo';
        $field .= ',g.goodsName,g.goodsSn,g.goodsImg';
        $field .= ',og.goodsPrice,og.id,og.skuSpecAttr,og.bind_hook_date';
        $field .= ',hook.hook_code';
        $where_find = array();
        $where_find['orders.orderNo'] = function () use ($params) {
            if (empty($params['order_no'])) {
                return null;
            }
            return array('like', "%{$params['order_no']}%", 'and');
        };
        $where_find['g.goodsName'] = function () use ($params) {
            if (empty($params['goods_name'])) {
                return null;
            }
            return array('like', "%{$params['goods_name']}%", 'and');
        };
        $where_find['g.goodsSn'] = function () use ($params) {
            if (empty($params['goods_sn'])) {
                return null;
            }
            return array('like', "%{$params['goods_sn']}%", 'and');
        };
        $where_find['hook.hook_code'] = function () use ($params) {
            if (empty($params['hook_code'])) {
                return null;
            }
            return array('like', "%{$params['hook_code']}%", 'and');
        };
        $where_find['og.bind_hook_date'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            return array('between', "{$params['start_date']}' and '{$params['end_date']}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = "{$where}";
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where_find} and {$where} ";
        }
        $sql = "select {$field} from __PREFIX__order_goods og ";
        $sql .= " left join __PREFIX__orders orders on orders.orderId=og.orderId ";
        $sql .= " left join __PREFIX__goods g on g.goodsId=og.goodsId ";
        $sql .= " left join __PREFIX__hook hook on hook.hook_id=og.hook_id ";
        $sql .= " where {$where_info} ";
        $sql .= "order by og.bind_hook_date desc ";
        $result = $this->pageQuery($sql, $page, $pageSize);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 下链口-查看订单记录
     * @param array $params <p>
     *  int chain_id 下链口id
     *  string orderNo 订单号
     *  string userName 收货人姓名
     *  string receivePeple 收货人姓名/手机号码
     *  string userPhone 收货人手机号
     *  string buyer_userName 买家姓名
     *  string buyer_userPhone 买家手机号
     *  int onStart 自提状态(0:未提货 1：已提货)
     *  int isSelf 自提订单【0：否|1：是】
     *  string source 自提码
     *  int payFrom 支付来源[1:支付宝，2：微信,3:余额,4:货到付款]
     *  int orderType 订单类型或订单来源(1.普通订单|2.拼团订单|3.预售订单|5.非常规订单)
     *  int deliverType 配送方式[0:商城配送 | 1:门店配送 | 2：达达配送 | 3.蜂鸟配送 | 4:快跑者 | 5:自建跑腿 | 6:自建司机 |22:自提]
     *  int areaId1 省id
     *  int areaId2 市id
     *  int areaId3 区id
     *  datetime startDate 下单时间-开始时间
     *  datetime endDate 下单时间-结束时间
     *  int statusMark 订单状态【all:全部|toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】
     *  int export 导出【0：否|1：是】
     *  int page 页码
     *  int pageSize 分页条数
     * </p>
     */
    public function getChainOrderLog(array $params)
    {
        //直接复制订单管理下面的订单列表
        $response = LogicResponse::getInstance();
        $shopId = $params["shopId"];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $export = (int)$params['export'];
        $where = " o.shopId={$shopId} and o.orderFlag=1 and o.chain_id={$params['chain_id']}";
        $whereFind = [];
        $whereFind['o.orderNo'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['o.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['o.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        $whereFind['self.source'] = function () use ($params) {
            if (empty($params['source'])) {
                return null;
            }
            return ['like', "%{$params['source']}%", 'and'];
        };
        $whereFind['self.onStart'] = function () use ($params) {
            if (!is_numeric($params['onStart']) || !in_array($params['onStart'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['onStart']}", 'and'];
        };
        $whereFind['o.payFrom'] = function () use ($params) {
            if (empty($params['payFrom'])) {
                return null;
            }
            return ['=', "{$params['payFrom']}", 'and'];
        };
        $whereFind['o.orderType'] = function () use ($params) {
            if (!is_numeric($params['orderType']) || !in_array($params['orderType'], [1, 2, 3, 5])) {
                return null;
            }
            return ['=', "{$params['orderType']}", 'and'];
        };
        $whereFind['o.deliverType'] = function () use ($params) {
            if (!is_numeric($params['deliverType']) || !in_array($params['deliverType'], [0, 1, 2, 3, 4, 5, 6])) {
                return null;
            }
            return ['=', "{$params['deliverType']}", 'and'];
        };
        $whereFind['o.isSelf'] = function () use ($params) {
            if (!is_numeric($params['isSelf']) || !in_array($params['isSelf'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['isSelf']}", 'and'];
        };
        if (is_numeric($params['deliverType']) && in_array($params['deliverType'], [22])) {
            unset($whereFind['o.isSelf']);
            $whereFind['o.isSelf'] = function () use ($params) {
                return ['=', "1", 'and'];
            };
        }
        $whereFind['o.areaId1'] = function () use ($params) {
            if (empty($params['areaId1'])) {
                return null;
            }
            return ['=', "{$params['areaId1']}", 'and'];
        };
        $whereFind['o.areaId2'] = function () use ($params) {
            if (empty($params['areaId2'])) {
                return null;
            }
            return ['=', "{$params['areaId2']}", 'and'];
        };
        $whereFind['o.areaId3'] = function () use ($params) {
            if (empty($params['areaId3'])) {
                return null;
            }
            return ['=', "{$params['areaId3']}", 'and'];
        };
        $whereFind['o.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['u.userName'] = function () use ($params) {
            if (empty($params['buyer_userName'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userName']}%", 'and'];
        };
        $whereFind['u.userPhone'] = function () use ($params) {
            if (empty($params['buyer_userPhone'])) {
                return null;
            }
            return ['like', "%{$params['buyer_userPhone']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        if (!empty($params['receivePeple'])) {
            $whereInfo .= " and (o.userName like '%{$params['receivePeple']}%' or o.userPhone like '%{$params['receivePeple']}%')";
        }
        $statusMark = [];
        $statusMark['statusMark'] = null;
        parm_filter($statusMark, $params);
        $sql = "select o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,self.source,self.onStart from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " left join __PREFIX__user_self_goods self on self.orderId=o.orderId " . " where {$whereInfo} ";
        //以下sql，主要用来统计相关条件下的总金额
        $sql1 = "select sum(o.realTotalMoney) as total_order_money from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId=o.userId " . " left join __PREFIX__user_self_goods self on self.orderId=o.orderId " . " where {$whereInfo} ";
        if (!empty($statusMark['statusMark'])) {
            $statusMark = $statusMark['statusMark'];
            switch ($statusMark) {
                case 'toBePaid'://待付款
                    $sql .= " AND o.orderStatus = -2 ";
                    $sql1 .= " AND o.orderStatus = -2 ";
                    break;
                case 'toBeAccepted'://待接单
                    $sql .= " AND o.orderStatus = 0 ";
                    $sql1 .= " AND o.orderStatus = 0 ";
                    break;
                case 'toBeDelivered'://待发货
                    $sql .= " AND o.orderStatus IN(1,2) ";
                    $sql1 .= " AND o.orderStatus IN(1,2) ";
                    break;
                case 'toBeReceived'://待收货
                    $sql .= " AND o.orderStatus IN(3) and isSelf=0 ";
                    $sql1 .= " AND o.orderStatus IN(3) and isSelf=0 ";
                    break;
                case 'confirmReceipt'://已完成
                    $sql .= " AND o.orderStatus = 4 ";
                    $sql1 .= "AND o.orderStatus = 4 ";
                    break;
                case 'toBePickedUp'://待取货,自提订单,商家发货后
                    $sql .= " AND o.orderStatus = 3 and o.isSelf=1 ";
                    $sql1 .= "AND o.orderStatus = 3 and o.isSelf=1 ";
                    break;
                case 'takeOutDelivery'://外卖配送
                    $sql .= " AND o.orderStatus IN(7,8,10,16,17) ";
                    $sql1 .= " AND o.orderStatus IN(7,8,10,16,17) ";
                    break;
                case 'invalid'://无效订单(用户取消或商家拒收)
                    $sql .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    $sql1 .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    break;
            }
        }
        $sql .= " order by o.orderId desc ";
        if ($export != 1) {
            $data = $this->pageQuery($sql, $page, $pageSize);
        } else {
            $data['root'] = $this->query($sql);
        }
        $total_order_money = $this->query($sql1);
        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
        $data['total_order_money'] = $total_order_money[0]['total_order_money'];
        //获取取消/拒收原因
        $noReadrderIds = array();
        $config = $GLOBALS['CONFIG'];

        $shop_config_tab = M('shop_configs');
        $sorting_tab = M('sorting');
        $sorting_packaging_tab = M('sorting_packaging');
        foreach ($data['root'] as $key => $v) {
            if ($v['isSelf'] == 1) {
                $data['root'][$key]['deliverType'] = 22;
            }
            $data['root'][$key]['deliverType'] = (string)$data['root'][$key]['deliverType'];//避免前端因为类型报错
            $data['root'][$key]['source'] = (string)$v['source']; //自提码
            $data['root'][$key]['onStart'] = (int)$v['onStart']; //自提状态(0:未提货 1：已提货)
            $nowtime = time();
            $shopGoodPreSaleEndTimeInt = strtotime($v['ShopGoodPreSaleEndTime']);
            $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 0; //未过期
            if ($nowtime >= $shopGoodPreSaleEndTimeInt) {
                $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 1; //已过期
                $data['root'][$key]['shopGoodPreSaleDelivery'] = 0;
            } else {
                $data['root'][$key]['shopGoodPreSaleDelivery'] = timeDiff($shopGoodPreSaleEndTimeInt, $nowtime);
            }
            $data['root'][$key]['weightG'] = 0;
            $orderGoods = M('order_goods')->where(['orderId' => $v['orderId']])->select();
            $data['root'][$key]['goodslist'] = $orderGoods;
            foreach ($orderGoods as $gv) {
                $goodsId[] = $gv['goodsId'];
            }
            $goodsWhere['goodsId'] = ['IN', $goodsId];
            $goods = M('goods')->where($goodsWhere)->field('SuppPriceDiff')->select();
            foreach ($goods as $ggv) {
                if ($ggv['SuppPriceDiff'] == 1) {
                    $data['root'][$key]['weightG'] = 1;
                }
            }
            if ($v['orderStatus'] == -6) $noReadrderIds[] = $v['orderId'];
            $sql = "select logContent from __PREFIX__log_orders where orderId =" . $v['orderId'] . " and logType=0 and logUserId=" . $v['userId'] . " order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
            if ($config['setDeliveryMoney'] == 2) {
                if ($data['root'][$key]['isPay'] == 1) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                } else {
                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            }
            //增加可发货状态-start
            $data['root'][$key]['can_delivery_state'] = -1;//可发货状态(0:不可发货|1:可发货)
            if (empty($params['statusMark']) || $params['statusMark'] == 'toBeDelivered') {
                if ($v['orderStatus'] == 2) {
                    $data['root'][$key]['can_delivery_state'] = 1;
                }
                $shop_config_info = $shop_config_tab->where(
                    array(
                        'shopId' => $v['shopId']
                    ))->field('isSorting')->find();//门店配置【是否开启分拣】1：是 -1：否
                $sort_order_info = $sorting_tab->where(array(
                    'orderId' => $v['orderId'],
                    'sortingFlag' => 1
                ))->select();//分拣信息
                $sort_pack_info = $sorting_packaging_tab->where(array(
                    'orderId' => $v['orderId']
                ))->find();//打包信息
                //存在分拣任务
                if (!empty($sort_order_info)) {
                    //packType:1:待打包|2:已打包
                    if ((int)$sort_pack_info['packType'] != 2) {
                        $data['root'][$key]['can_delivery_state'] = 0;
                    }
                }
                //开启分拣
                if ($shop_config_info['isSorting'] == 1) {
                    if (!empty($sort_order_info) && (int)$sort_pack_info['packType'] != 2) {
                        $data['root'][$key]['can_delivery_state'] = 0;
                    }
                }
                //增加可发货状态-end
            }
        }
        if ($params['statusMark'] == 'toBeDelivered' && !empty($data['root'])) {
            $list = $data['root'];
            $sort_arr = array();
            foreach ($list as $key => $value) {
                $sort_arr[] = $value['can_delivery_state'];
            }
            array_multisort($sort_arr, SORT_DESC, $list);
            $data['root'] = $list;
        }
        if ($export == 1) {
            $this->exportOrderList($data['root'], I());
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }

    /**
     *悬挂链回调,返回当前在链上订单、载具关系及状态
     * @param int $shop_id 门店id
     * @param array $area_info 各段区域状态
     * */
    public function pollinfo(int $shop_id, array $area_info)
    {
        $shop_service_module = new ShopsServiceModule();
        $shop_result = $shop_service_module->getShopConfig($shop_id);
        $shop_data = $shop_result['data'];
        if ($shop_data['open_suspension_chain'] != 1) {
            return array(
                'code' => -1,
                'error' => array(
                    'msg' => '门店未开启悬挂链'
                ),
            );
        }
        $field = 'orders.orderId';
        $where = "orders.orderFlag=1 and orders.is_reporting=0 and orders.shopId={$shop_id}";
        $where .= " and sorting.status=3 ";
        $list = M('sorting_goods_relation relation')
            ->join('left join wst_sorting sorting on sorting.id=relation.sortingId')
            ->join('left join wst_orders orders on orders.orderId=sorting.orderId')
            ->where($where)
            ->field($field)
            ->select();
        if (count($list) <= 0) {
            return array(
                'code' => -1,
                'error' => array(
                    'msg' => '暂无数据'
                ),
            );
        }
        $order_id_arr = array_unique(array_column($list, 'orderId'));
        $where = array(
            'og.orderId' => array('IN', $order_id_arr),
            'og.hook_id' => array('GT', 0)
        );
        $order_goods_list = M('order_goods og')
            ->join('left join wst_orders orders on orders.orderId=og.orderId')
            ->where($where)
            ->field('og.*,orders.orderNo,orders.chain_id')
            ->order('og.id asc')
            ->select();
        if (count($order_goods_list) <= 0) {
            return array(
                'code' => -1,
                'error' => array(
                    'msg' => '暂无数据'
                ),
            );
        }
        $order_list = array();
        $chain_service_module = new ChainServiceModule();
        foreach ($order_goods_list as $item) {
            $hook_id = (int)$item['hook_id'];
            $hook_result = $chain_service_module->getHookInfoById($hook_id);
            if ($hook_result['code'] != ExceptionCodeEnum::SUCCESS) {
                continue;
            }
            $hook_data = $hook_result['data'];
            $chain_id = (int)$item['chain_id'];
            $chain_result = $chain_service_module->getChainInfoById($chain_id);
            if ($chain_result['code'] != ExceptionCodeEnum::SUCCESS) {
                continue;
            }
            $chain_data = $chain_result['data'];
            $info = array();
            $info['orderId'] = $item['orderNo'];
            $info['carrierCode'] = $hook_data['hook_code'];//载具号
            $info['priority'] = (int)$item['id'];
            $chain_code_arr = explode('C', $chain_data['chain_code']);
            $info['exportId'] = (int)$chain_code_arr[1];//下链口
            $info['complete'] = 1;
            $info['finish'] = 1;
            $info['clear'] = 1;
            $info['status'] = 0;
            //$microtime = getMicroSecondsTimestamp();
            $info['optId'] = (int)microtime(true);
            $order_list[] = $info;
        }
        M('orders')->where(
            array(
                'orderId' => array('IN', $order_id_arr)
            )
        )->save(array(
            'is_reporting' => 1
        ));
        $response = array();
        $response['code'] = 0;
        $response['error'] = array(
            'msg' => '成功'
        );
        $area_list = array();
        foreach ($area_info as $key => $item) {
            $info = array();
            $info['areaCode'] = $key;
            $info['working'] = $item;
            $info['optId'] = (int)microtime(true);
            $area_list[] = $info;
        }
        $response['data'] = array(
            'orderList' => $order_list,
            'areaList' => $area_list
        );
        return $response;
    }

}

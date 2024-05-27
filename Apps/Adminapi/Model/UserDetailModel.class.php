<?php

namespace Adminapi\Model;

use App\Modules\Disribution\DistributionModule;
use App\Modules\Rank\RankModule;
use App\Modules\Users\UsersModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员详情服务类
 */
class UserDetailModel extends BaseModel
{
    /**
     * @return array
     * 会员详情
     * 1.基本信息 2.推广下线明细 3.消费明细 4.积分明细 5.签到记录 6.持有优惠券 7.余额变动记录 8.地推拉新记录 9.地推收益明细 10.登录日志
     */
    public function getUserDetail()
    {
        $type = (int)I('type', 1);
        $userId = I('userId');
        $loginName = I('loginName');
        $userName = I('userName');
        $createTime = I('createTime');
        $pageSize = (int)I('pageSize', 15);
        $page = (int)I('page', 1);
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (!empty($userId)) {
            $where = " userId = '$userId'";
        }
        if (!empty($loginName)) {
            $where = " loginName like '%" . $loginName . "%'";
        }
        if (!empty($userName)) {
            $where = " userName like '%" . $userName . "%'";
        }
        if (!empty($createTime)) {
            $where = " createTime = '$createTime'";
        }
        if (empty($where)) {
            $rd['msg'] = '参数有误';
            return $rd;
        }
        $userInfo = M('users')->where('userFlag = 1')->where($where)->find();
        if (empty($userInfo)) {
            $rd['msg'] = '用户不存在';
            return $rd;
        }
        //1.基本信息
        if ($type == 1) {
            $inviteInfo = M('invite_cache_record icr')
                ->join('left join wst_users u ON u.userId = icr.inviterId')
                ->where("icr.inviteePhone = " . $userInfo['userPhone'])
                ->field('u.userId,u.userName')
                ->find();
            if (empty($inviteInfo)) {
                $inviteInfo = M('distribution_invitation icr')
                    ->join('left join wst_users u ON u.userId = icr.userId')
                    ->where("icr.userPhone = " . $userInfo['userPhone'])
                    ->field('u.userId,u.userName')
                    ->find();
            }
            $userInfo['inviteUserId'] = $inviteInfo['userId'];
            $userInfo['inviteUserName'] = $inviteInfo['userName'];
            $buyUserRecord = M('buy_user_record bur')
                ->join('left join wst_users u ON u.userId = bur.userId')
                ->where("bur.userId='" . $userInfo['userId'] . "' AND bur.burFlag = 1")
                ->limit(1)
                ->order('burId', 'asc')
                ->field('bur.buyTime')
                ->find();
            $Date = date("Y-m-d");
            $d1 = strtotime($Date);
            $d2 = strtotime($Date);
            if (!empty($buyUserRecord['buyTime'])) {
                $d2 = strtotime($buyUserRecord['buyTime']);
            }
            $userInfo['memberDate'] = round(($d1 - $d2) / 3600 / 24);
            $withdraw = M('distribution_withdraw dw')
                ->join('left join wst_users u ON u.userId = dw.userId')
                ->where("dw.userId='" . $userInfo['userId'] . "' AND dw.state = 1")
                ->field('sum(dw.money) as money')
                ->select();
            $userInfo['withdrawMoney'] = number_format($withdraw[0]['money'], '2');
            $userAddress = M('user_address ua')
                ->join('left join wst_users u ON u.userId = ua.userId')
                ->join('left join wst_areas wa ON wa.areaId = ua.areaId1 ')
                ->join('left join wst_areas wb ON wb.areaId = ua.areaId2 ')
                ->join('left join wst_areas wc ON wc.areaId = ua.areaId3 ')
                ->where("ua.userId='" . $userInfo['userId'] . "' AND ua.addressFlag = 1 ")//AND ua.isDefault = 1(默认地址)
                ->field('ua.*,wa.areaName as areaName1,wb.areaName as areaName2,wc.areaName as areaName3')
                ->select();
            $userInfo['userAddress'] = (array)$userAddress;
            //后加---start--2020-11-5-- 消费金额 订单数量 优惠券（张）商品评价 退货记录  登录次数 收藏商品 邀请好友
            $orderPrice = M('orders')
                ->where(['orderFlag' => 1, 'userId' => $userInfo['userId'], 'isPay' => 1, 'isRefund' => 0, 'orderStatus' => 4])
                ->field("sum(realTotalMoney) as orderPrice,count(orderId) as orderCount")
                ->select();//消费金额 订单数量
            $couponsCount = M('coupons_users cu')
                ->join('left join wst_coupons wc ON wc.couponId = cu.couponId')
                ->where('cu.dataFlag = 1 and cu.userId = ' . $userInfo['userId'])
                ->count();//优惠券
            $goodsAppraisesCount = M('goods_appraises')->where(['userId' => $userInfo['userId']])->count();//商品评价
            $orderComplainCount = M('order_complainsrecord')->where(['userId' => $userInfo['userId']])->count();//退货记录
            $userLoginCount = M('log_user_logins')->where(['userId' => $userInfo['userId']])->count();//登录次数
            $userFavoritesCount = M('favorites')->where(['userId' => $userInfo['userId'], 'favoriteType' => 0])->count();//收藏商品
            $userInvitationCount = M('user_invitation')->where(['userId' => $userInfo['userId']])->count();//邀请好友
            //------------end---------------
            $userInfo['orderPrice'] = (string)number_format($orderPrice[0]['orderPrice'], '2');//消费金额
            $userInfo['orderCount'] = (int)$orderPrice[0]['orderCount'];//订单数量
            $userInfo['couponsCount'] = (int)$couponsCount;//优惠券
            $userInfo['goodsAppraisesCount'] = (int)$goodsAppraisesCount;//商品评价
            $userInfo['orderComplainCount'] = (int)$orderComplainCount;//退货记录
            $userInfo['userLoginCount'] = (int)$userLoginCount;//登录次数
            $userInfo['userFavoritesCount'] = (int)$userFavoritesCount;//收藏商品
            $userInfo['userInvitationCount'] = (int)$userInvitationCount;//邀请好友
            $userInfo['has_rank'] = 0;//存在身份(0:不存在 1:存在)
            $userInfo['rankDetail'] = (object)array();
            $userRankDetial = (new RankModule())->getUserRankDetialByUserId($userId);
            if (!empty($userRankDetial)) {
                $userInfo['has_rank'] = 1;
                $userInfo['rankDetail'] = array(
                    'rankId' => $userRankDetial['rankId'],
                    'rankName' => $userRankDetial['rankName'],
                );
            }
            $userDetail = $userInfo;
        }
        //2.推广下线明细1
        if ($type == 2) {
            $m = M('distribution_relation i');
            $distributionLevel = (int)I('distributionLevel');
            $where = "i.pid='" . $userInfo['userId'] . "' and u.userFlag=1 ";
            if (!empty($distributionLevel)) {
                $where .= "AND i.distributionLevel = $distributionLevel";
            }
//            $count = $m->where($where)->count();
            $count = $m
                ->join("left join wst_users u on u.userId=i.userId")
                ->where($where)
                ->count();
            $totalCount = $m
                ->join("left join wst_users u on u.userId=i.userId")
                ->where("u.userFlag=1 and i.pid = " . $userInfo['userId'])
                ->count();
            $firstCount = $m
                ->join("left join wst_users u on u.userId=i.userId")
                ->where("u.userFlag=1 and i.pid ='" . $userInfo['userId'] . "' AND i.distributionLevel = 1 ")
                ->count();
            $secondCount = $m
                ->join("left join wst_users u on u.userId=i.userId")
                ->where("u.userFlag=1 and i.pid ='" . $userInfo['userId'] . "' AND i.distributionLevel = 2 ")
                ->count();
            $list = (array)$m
                ->join("LEFT JOIN wst_users u ON i.userId = u.userId")
                ->where($where)
                ->field('i.userId,u.userName,u.balance,u.userScore,i.addTime,i.distributionLevel')
                ->order('i.id DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();
            foreach ($list as $key => &$value) {
                $firstTime = M('orders o')
                    ->join("LEFT JOIN wst_users u ON u.userId = o.userId")
                    ->where("o.userId='" . $value['userId'] . "' AND o.orderStatus = 4")
                    ->field('o.createTime')
                    ->limit(1)
                    ->order('o.orderId asc')
                    ->find();
                $value['firstTime'] = $firstTime['createTime'];
            }
            unset($value);
            $userDetail = [];
            $userDetail['totalCount'] = (int)$totalCount;
            $userDetail['firstCount'] = (int)$firstCount;
            $userDetail['secondCount'] = (int)$secondCount;
            $userDetail['list'] = $list;
        }
        //3.消费明细
        if ($type == 3) {
            $field = "orderId,orderNo,orderStatus,realTotalMoney,payType,createTime,orderType,isPay,deliverMoney,userPhone,isRefund,payFrom";
            $orderList = M('orders')
                ->where("orderFlag = 1 and userId = " . $userInfo['userId'])
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->order('orderId desc')
                ->field($field)
                ->select();
            $count = M('orders')->where("orderFlag = 1 and userId = " . $userInfo['userId'])->count();
            $orderGoodsTab = M('order_goods');
            $skuGoodsSystem = M('sku_goods_system');
            $config = $GLOBALS['CONFIG'];
            $money = 0.00;
            $monthOrders = 0;
            $monthAmount = 0;
            foreach ($orderList as $key => $value) {
                $orderGoods = $orderGoodsTab->where(['orderId' => $value['orderId']])->select();
                $orderCount = count($orderGoods);
                $orderList[$key]['orderGoodsCount'] = $orderCount;
                $orderList[$key]['orderGoods'] = [];
                //sku信息
                foreach ($orderGoods as $k => $v) {
                    $skuGoodsInfo = $skuGoodsSystem->where("goodsId = " . $v['goodsId'] . " and skuId = " . $v['skuId'])->find();
                    if (!empty($skuGoodsInfo)) {
                        $spec = M("sku_goods_self se")
                            ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                            ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                            ->where(['se.skuId' => $skuGoodsInfo['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                            ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                            ->order('sp.sort asc')
                            ->select();
                        $skuGoodsInfo['skuList'] = $spec;
                    }
                    $orderGoods[$k]['skuGoodsInfo'] = (array)$skuGoodsInfo;
                }
                $orderList[$key]['orderGoods'] = $orderGoods;
                //处理统一运费
                if ($config['setDeliveryMoney'] == 2) {
                    if ($orderList[$key]['isPay'] == 1) {
                        //$orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'] + $orderList[$key]['deliverMoney'];
                        $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'];
                    } else {
                        if ($orderList[$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney'] + $orderList[$key]['setDeliveryMoney'];
                            $orderList[$key]['deliverMoney'] = $orderList[$key]['setDeliveryMoney'];
                        }
                    }
                }
                if ($value['isPay'] == 1 && $value['isRefund'] == 0) {
                    $money = $money + $orderList[$key]['realTotalMoney'];
                    $appointmentTime = date('Y-m');
                    $tomorrowTime = date("Y-m", strtotime("+1 months $appointmentTime"));
                    if ($value['createTime'] >= $appointmentTime && $value['createTime'] < $tomorrowTime) {
                        $monthOrders = $monthOrders + 1;
                        $monthAmount = $monthAmount + $orderList[$key]['realTotalMoney'];
                    }
                }
            }
            $userDetail = [];
            $userDetail['totalOrders'] = $count;
            $userDetail['money'] = $money;
            $userDetail['monthOrders'] = $monthOrders;
            $userDetail['monthAmount'] = $monthAmount;
            $userDetail['list'] = array_values($orderList);
        }
        //4.积分明细
        if ($type == 4) {
            $model = M('user_score');
            $userScoreList = $model
                ->where('userId = ' . $userInfo['userId'])
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->field('score,dataSrc,dataRemarks,scoreType,createTime')
                ->select();
            $count = $model->where('userId = ' . $userInfo['userId'])->count();
            $userDetail = [];
            $userDetail['list'] = (array)$userScoreList;
        }
        //5.签到记录
        if ($type == 5) {
            $model = M('user_score');
            $userScoreList = $model
                ->where('dataSrc = 5 and userId = ' . $userInfo['userId'])
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->field('score,dataSrc,dataRemarks,scoreType,createTime')
                ->select();
            $count = $model->where('dataSrc = 5 and userId = ' . $userInfo['userId'])->count();
            $userDetail = [];
            $userDetail['list'] = (array)$userScoreList;
        }
        //6.持有优惠券
        if ($type == 6) {
            $couponsList = M('coupons_users cu')
                ->join('left join wst_coupons wc ON wc.couponId = cu.couponId')
                ->where('cu.dataFlag = 1 and cu.userId = ' . $userInfo['userId'])
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->field('cu.receiveTime,cu.couponStatus,cu.couponExpireTime,wc.couponName,wc.couponMoney')
                ->select();
            $count = M('coupons_users cu')
                ->join('left join wst_coupons wc ON wc.couponId = cu.couponId')
                ->where('cu.dataFlag = 1 and cu.userId = ' . $userInfo['userId'])
                ->count();
            $userDetail = [];
            $userDetail['list'] = (array)$couponsList;
        }
        //7.余额变动记录
        if ($type == 7) {
            $userBalance = M('user_balance ub')
                ->join('left join wst_orders wo ON wo.orderNo = ub.orderNo')
                ->where(' ub.userId = ' . $userInfo['userId'])
                ->field('ub.*,wo.payFrom')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();
            $count = M('user_balance ub')
                ->join('left join wst_orders wo ON wo.orderNo = ub.orderNo')
                ->where(' ub.userId = ' . $userInfo['userId'])
                ->count();
            $userDetail = [];
            $userDetail['list'] = (array)$userBalance;
        }
        //8.地推拉新记录
        if ($type == 8) {
            $startDate = I('startDate', '');//受邀时间区间-开始时间
            $endDate = I('endDate', '');//受邀时间区间-结束时间
            $usersToIdUserName = I('usersToIdUserName');//受邀人名称
            $usersToIdUserUserPhone = I('usersToIdUserUserPhone');//受邀人手机号
            $pullNewLogTab = M('pull_new_log log');
            $where = [];
            $where['log.inviterId'] = $userId;
            $where['users.userFlag'] = 1;
            $where['usersToId.userFlag'] = 1;
            if (!empty($startDate) && !empty($endDate)) {
                $where['log.createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
            }
            if (!empty($usersToIdUserName)) {
                $where['usersToId.userName'] = ['like', "%{$usersToIdUserName}%"];
            }
            if (!empty($usersToIdUserUserPhone)) {
                $where['usersToId.userPhone'] = ['like', "%{$usersToIdUserUserPhone}%"];
            }
            $field = 'log.id,users.createTime,log.inviterId as userId,users.userName,users.userPhoto,users.userPhone';
            $field .= ',log.userId as usersToIdUserId,usersToId.userName as usersToIdUserName,usersToId.userPhoto as usersToIdUserPhoto,usersToId.userPhone as usersToIdUserUserPhone';
            $field .= ',invitation.addTime as invitationTime';
            $data = $pullNewLogTab
                ->join("left join wst_users users on users.userId=log.inviterId")
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->join("left join wst_distribution_invitation invitation on invitation.userId=log.inviterId and usersToId.userPhone=invitation.userPhone")
                ->where($where)
                ->field($field)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->group('log.userId')
                ->order('log.id desc')
                ->select();
            $pullNewAmountLog = M('pull_new_amount_log');
            if (!empty($data)) {
                foreach ($data as $key => $val) {
                    $data[$key]['firstShoppingTime'] = '';
                    $log = [];
                    $log['orderId'] = ['GT', 0];
                    $log['userId'] = $val['usersToIdUserId'];
                    $amountLog = $pullNewAmountLog->where($where)->field('createTime')->order('id asc')->find();
                    if (!empty($amountLog)) {
                        $data[$key]['firstShoppingTime'] = $amountLog['createTime'];
                    }
                }
            }
            $cwhere = array(
                'log.inviterId' => $userId,
                'usersToId.userFlag' => 1
            );
            $count = $pullNewLogTab
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->where($cwhere)
                ->count();
            $userDetail = [];
            $userDetail['list'] = array_filter(array_values($data));
        }
        //9.地推收益明细
        if ($type == 9) {
            $pullNewAmountTab = M('pull_new_amount_log log');
            $startDate = I('startDate', '');//受邀时间区间-开始时间
            $endDate = I('endDate', '');//受邀时间区间-结束时间
            $status = I('status', '');//入账状态【0：待入账|1：已入账】
            $dataType = I('dataType', '');//收益类型【1：邀请成功注册|2：成功下单】
            $where = [];
            $where['log.inviterId'] = $userId;
            if (is_numeric($status)) {
                $where['log.status'] = $status;
            }
            if (is_numeric($dataType)) {
                $where['log.dataType'] = $dataType;
            }
            if (!empty($startDate) && !empty($endDate)) {
                $where['log.createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
            }
            $field = 'log.id,log.inviterId as userId,users.userName,users.userPhoto,users.userPhone,log.dataType,log.status,log.amount,log.createTime';
            $field .= ',log.userId as usersToIdUserId,usersToId.userName as usersToIdUserName,usersToId.userPhoto as usersToIdUserPhoto,usersToId.userPhone as usersToIdUserUserPhone';
            $data = $pullNewAmountTab
                ->join("left join wst_users users on users.userId=log.inviterId")
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->where($where)
                ->field($field)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->order('log.id desc')
                ->select();
            $count = $pullNewAmountTab
                ->join("left join wst_users users on users.userId=log.inviterId")
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->join("left join wst_distribution_invitation invitation on invitation.userId=log.inviterId")
                ->where($where)
                ->field($field)
                ->group('invitation.userId')
                ->order('log.id desc')
                ->count();
            //$pullNewAmount = 0;//拉新收入金额-总收入金额
            //$pullNewAmountPending = 0;//拉新收入金额-待入账金额
            //$pullNewAmountSolve = 0;//拉新收入金额-已入账金额
            $pullNewAmount = $pullNewAmountTab
                ->join("left join wst_users users on users.userId=log.inviterId")
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->where($where)
                ->sum('log.amount');
            $where['status'] = 1;
            $pullNewAmountSolve = $pullNewAmountTab
                ->join("left join wst_users users on users.userId=log.inviterId")
                ->join("left join wst_users usersToId on usersToId.userId=log.userId")
                ->where($where)
                ->sum('log.amount');
            $pullNewAmountPending = $pullNewAmount - $pullNewAmountSolve;
            if ($pullNewAmountPending <= 0) {
                $pullNewAmountPending = 0;
            }
            $userDetail = [];
            $userDetail['list'] = (array)$data;
            $userDetail['pullNewAmount'] = (double)formatAmount($pullNewAmount);
            $userDetail['pullNewAmountPending'] = (double)formatAmount($pullNewAmountPending);
            $userDetail['pullNewAmountSolve'] = (double)formatAmount($pullNewAmountSolve);
        }
        //10.登录日志
        if ($type == 10) {
            $data = M('log_user_logins')->where(['userId' => $userInfo['userId']])->select();
            $count = M('log_user_logins')->where(['userId' => $userInfo['userId']])->count();
            $userDetail = [];
            $userDetail['list'] = (array)$data;
        }
        $typeInfo = [2, 3, 4, 5, 6, 7, 8, 9, 10];
        if (in_array($type, $typeInfo)) {
            $userDetail['totalPage'] = ceil($count / $pageSize);//总页数
            $userDetail['currentPage'] = $page;//当前页码
            $userDetail['count'] = (int)$count;//总数量
            $userDetail['pageSize'] = $pageSize;//页码条数
            if (is_null($userDetail['list'])) {
                $userDetail['list'] = [];
            }
        }
        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $userDetail;
        //我操,这么恶心的吗
        if ($type == 11) {//分销佣金记录
            $disParams = I();
            $disParams['invitationUserId'] = $userId;
            $result = (new DistributionModule())->getDistributionLogList($disParams);
            $result['list'] = $result['root'];
            unset($result['root']);
            return returnData($result);
        }
        if ($type == 12) {//用户收货地址
            $userModule = new UsersModule();
            $rs['data']['list'] = $userModule->getUserAddressList($userId);
        }
        return $rs;
    }

    /**
     * @return array
     * 平台修改会员密码
     */
    public function editUserPwd()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $loginPwd = I('loginPwd');
        $userId = (int)I('userId');
        $userInfo = M('users')->where("userId = $userId")->find();
        if (empty($userInfo)) {
            $rd['msg'] = '用户不存在';
            return $rd;
        }
        $data["loginSecret"] = rand(1000, 9999);
        $data["loginPwd"] = md5($loginPwd . $data['loginSecret']);
        $user = M('users')->where("userId = $userId")->save($data);
        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $user;
        return $rs;
    }
}
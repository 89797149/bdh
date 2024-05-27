<?php

namespace Home\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Pay\PayModule;
use App\Modules\Settlement\CustomerSettlementBillModule;
use App\Modules\Sorting\SortingModule;
use App\Modules\WarehousingBill\WarehousingBillModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单投诉服务类
 */
class OrderComplainsModel extends BaseModel
{
    /**
     * 获取用户投诉列表
     */
    public function queryUserComplainByPage()
    {
        $userId = (int)session('WST_USER.userId');
        $orderNo = WSTAddslashes(I('orderNo'));
        $sql = "select oc.complainId,o.orderId,o.orderNo,p.shopId,p.shopName,oc.complainContent,oc.complainStatus,oc.complainTime
		        from __PREFIX__order_complains oc left join __PREFIX__shops p on oc.respondTargetId=p.shopId,
		        __PREFIX__orders o where oc.orderId=o.orderId and o.orderFlag=1 and o.userId=" . $userId;
        if ($orderNo != '') $sql .= " and o.orderNo like '%" . $orderNo . "%'";
        $sql .= " order by oc.complainId desc";
        return $this->pageQuery($sql, (int)I('p'), 30);
    }

    /**
     * 获取商家被投诉列表
     */
    public function queryShopComplainByPage($obj = array())
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $obj['shopId'];
        $orderNo = WSTAddslashes(I('orderNo'));
        $sql = "select oc.complainId,o.orderId,o.orderNo,u.userName,u.loginName,oc.complainContent,oc.complainStatus,oc.complainTime
		        from __PREFIX__order_complains oc left join __PREFIX__users u on oc.complainTargetId=u.userId,
		        __PREFIX__orders o where oc.needRespond=1 and oc.orderId=o.orderId and o.orderFlag=1 and oc.respondTargetId=" . $shopId;
        if ($orderNo != '') $sql .= " and o.orderNo like '%" . $orderNo . "%'";
        $sql .= " order by oc.complainId desc";
        return $this->pageQuery($sql, (int)I('p'), 30);
    }

    /**
     * 退货申请处理统计
     * @param int $shopId
     * */
    public function getOrderComplainsListNum(int $shopId)
    {
        $shopId = (int)$shopId;
        $tab = M('order_complains oc');
        $where = [];
        $where['oc.respondTargetId'] = $shopId;
        $where['o.orderFlag'] = 1;
        $where['u.userFlag'] = 1;
        $alllist = $tab
            ->join('left join wst_users u on oc.complainTargetId=u.userId')
            ->join('left join wst_orders o on o.orderId=oc.orderId')
            ->where($where)
            ->select();
        $allNum = 0;//全部
        $rejectedNum = 0;//已拒绝
        $pendingNum = 0;//待处理
        $returningNum = 0;//退货中
        $completedNum = 0;//已完成
        foreach ($alllist as $item) {
            $allNum += 1;
            if ($item['complainStatus'] == -1) {
                $rejectedNum += 1;
            } elseif ($item['complainStatus'] == 0) {
                $pendingNum += 1;
            } elseif ($item['complainStatus'] == 1) {
                $returningNum += 1;
            } elseif ($item['complainStatus'] == 2) {
                $completedNum += 1;
            }
        }
        $data = [];
        $data['allNum'] = $allNum;
        $data['rejectedNum'] = $rejectedNum;
        $data['pendingNum'] = $pendingNum;
        $data['returningNum'] = $returningNum;
        $data['completedNum'] = $completedNum;
        return $data;
    }

    /**
     *获取商家退货单列表
     * @param array $params <p>
     * int shopId
     * int complainStatus 状态【-1：已拒绝|0：待处理|1：退货中|2：已完成】
     * string orderNo 订单号
     * string receivingPeople 联系人姓名/手机号/账号
     * string startDate 退货时间区间-开始时间
     * string endDate 退货时间区间-结束时间
     * string respondTime_start 应诉时间区间-开始时间
     * string respondTime_end 应诉时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @return array $data
     */
    public function getOrderComplainsList(array $params)
    {
        $shopId = (int)$params['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = "oc.respondTargetId={$shopId} and o.orderFlag=1 and u.userFlag=1 ";
        if (!empty($params['receivingPeople'])) {
            $where .= " and (u.userName like '%{$params['receivingPeople']}%' or u.loginName like '%{$params['receivingPeople']}%'or u.userPhone like '%{$params['receivingPeople']}%') ";
        }
        $whereFind = [];
        $whereFind['oc.complainStatus'] = function () use ($params) {
            if (!is_numeric($params['complainStatus'])) {
                return null;
            }
            return ['=', "{$params['complainStatus']}", 'and'];
        };
        $whereFind['o.orderNo'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['oc.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['oc.respondTime'] = function () use ($params) {
            if (empty($params['respondTime_start']) || empty($params['respondTime_end'])) {
                return null;
            }
            return ['between', "{$params['respondTime_start']}' and '{$params['respondTime_end']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = " {$where} and {$whereFind} ";
        }
        $field = 'oc.complainId,oc.complainContent,oc.complainStatus,oc.createTime,oc.respondTime,oc.returnAmount';
        $field .= ',o.orderId,o.orderNo,o.realTotalMoney,o.shopId';
        $field .= ',u.loginName,u.userName,u.userPhone';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__users u on oc.complainTargetId=u.userId
                left join __PREFIX__orders o on o.orderId=oc.orderId 
                where {$whereInfo} ";
        $sql .= " group by oc.complainId ";
        $sql .= " order by oc.complainId desc";
        if ($params['export'] == 1) {
            //导出
            $data['root'] = $this->query($sql);
        } else {
            $data = $this->pageQuery($sql, $page, $pageSize);
        }
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                $item['proposalReturnAmount'] = 0;//建议退款金额
                $checkRespondTime = strtotime($item['respondTime']);
                if (!$checkRespondTime) {
                    $item['respondTime'] = '';
                }
                $detail = $this->getOrderComplainDetail($shopId, $item['complainId']);
                if (!empty($detail)) {
                    $item['proposalReturnAmount'] = $detail['realyGoodsPrice'];
                }
            }
            unset($item);
            $data['root'] = $list;
        }
        if ($params['export'] == 1) {
            $this->exportOrderComplainsList($data['root'], $params);
        }
        return $data;
    }

    /**
     * 导出退货数据
     * @param array $list 需要导出的单据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrderComplainsList(array $list, array $params)
    {
        foreach ($list as &$item) {
            $item['goodslist'] = [];
            $detail = $this->getOrderComplainDetail($item['shopId'], $item['complainId']);
            if (!empty($detail['goodsList'])) {
                $item['goodslist'] = $detail['goodsList'];
            }
        }
        unset($item);
        //拼接表格信息
        $date = '';
        $startDate = '';
        $endDate = '';
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        }
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:100px;'>服务单号</th>
                <th style='width:150px;'>申请时间</th>
                <th style='width:120px;'>用户账号</th>
                <th style='width:80px;'>建议退款金额</th>
                <th style='width:80px;'>实退金额</th>
                <th style='width:150px;'>联系人</th>
                <th style='width:150px;'>申请状态</th>
                <th style='width:150px;'>处理时间</th>
                <th style='width:150px;'>商品名称</th>
                <th style='width:150px;'>规格</th>
                <th style='width:80px;'>商品单价</th>
            </tr>";
        foreach ($list as $okey => $ovalue) {
            $statusName = '未知';
            if ($ovalue['complainStatus'] == -1) {
                $statusName = '已拒绝';
            } elseif ($ovalue['complainStatus'] == 0) {
                $statusName = '待处理';
            } elseif ($ovalue['complainStatus'] == 1) {
                $statusName = '退货中';
            } elseif ($ovalue['complainStatus'] == 2) {
                $statusName = '已完成';
            }
            $orderGoods = $ovalue['goodslist'];
            $key = $okey + 1;
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gkey => $gVal) {
                if ($gkey == 0) {
                    $body .=
                        "<tr align='center'>" .
                        "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                        "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//服务单号
                        "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//申请时间
                        "<td style='width:120px;' rowspan='{$rowspan}'>" . $ovalue['loginName'] . "</td>" .//用户账号
                        "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['proposalReturnAmount'] . "</td>" .//建议退款金额
                        "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['returnAmount'] . "</td>" .//实退金额
                        "<td style='width:200px;' rowspan='{$rowspan}'>" . $ovalue['userName'] . "</td>" .//联系人
                        "<td style='width:150px;' rowspan='{$rowspan}'>" . $statusName . "</td>" .//申请状态
                        "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['respondTime'] . "</td>" .//处理时间
                        "<td style='width:150px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                        "<td style='width:150px;' >" . $gVal['skuSpecAttr'] . "</td>" .//规格
                        "<td style='width:80px;' >" . $gVal['shopPrice'] . "</td>" .//商品单价
                        "</tr>";
                } else {
                    $body .=
                        "<tr align='center'>" .
                        "<td style='width:150px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                        "<td style='width:150px;' >" . $gVal['skuSpecAttr'] . "</td>" .//规格
                        "<td style='width:80px;' >" . $gVal['shopPrice'] . "</td>" .//商品单价
                        "</tr>";
                }
            }
        }
        $headTitle = "退货数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo()
    {
        $userId = (int)session('WST_USER.userId');
        $orderId = (int)I('orderId');
        //判断是否提交过投诉
        $sql = "select complainId from __PREFIX__order_complains where orderId=" . $orderId . " and complainTargetId=" . $userId;
        $rs = $this->queryRow($sql);
        $data = array('complainStatus' => 1);
        if ($rs['complainId'] == '') {
            //获取订单信息
            $sql = "select o.realTotalMoney,o.orderNo,o.orderId,o.createTime,o.deliverMoney,o.requireTime,p.shopName,p.shopId
			        from __PREFIX__orders o left join __PREFIX__shops p on o.shopId=p.shopId where o.orderId=" . $orderId . " and o.userId=" . $userId;
            $order = $this->queryRow($sql);
            if ($order) {
                //获取相关商品
                $sql = "select og.orderId, og.goodsId ,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName
						from __PREFIX__goods g , __PREFIX__order_goods og
						WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
                $goods = $this->query($sql);
                $order["goodsList"] = $goods;
            }
            $data['order'] = $order;
            $data['complainStatus'] = 0;
        }
        return $data;
    }

    /**
     * 保存订单投诉信息
     */
    public function saveComplain()
    {
        $rd = array('status' => -1);
        $userId = (int)session('WST_USER.userId');
        $rules = array(
            array('orderId', 'integer', '无效的订单！', 1),
            array('complainType', array(1, 2, 3, 4), '无效的投诉类型！', 1, 'in'),
            array('complainContent', 'require', '投诉内容不能为空！', 1)
        );
        if ($this->validate($rules)->create()) {
            //判断订单是否该用户的
            $sql = "select o.orderId,o.shopId from __PREFIX__orders o
			        where o.orderId=" . $this->orderId . " and o.userId=" . $userId;
            $order = $this->queryRow($sql);
            if (!$order) {
                $rd['msg'] = "无效的订单信息";
                return $rd;
            }
            //判断是否提交过投诉
            $sql = "select complainId from __PREFIX__order_complains where orderId=" . $this->orderId . " and complainTargetId=" . $userId;
            $rs = $this->queryRow($sql);
            if ((int)$rs['complainId'] > 0) {
                $rd['msg'] = "该订单已进行了投诉,请勿重提提交投诉信息";
                return $rd;
            }

            $this->complainTargetId = $userId;
            $this->respondTargetId = $order['shopId'];
            $this->complainStatus = 0;
            $this->complainTime = date('Y-m-d H:i:s');
            if (I('complainAnnex') != '') $this->complainAnnex = I('complainAnnex');
            $rs = $this->add();
            if ($rs !== false) {
                $rd['status'] = 1;
            } else {
                $rd['msg'] = '提交订单投诉信息失败';
            }
        } else {
            $rd['msg'] = $this->getError();
        }
        return $rd;
    }

    /**
     * 获取投诉详情
     */
    public function getComplainDetail($userType = 0, $data = array())
    {
        $userId = (int)session('WST_USER.userId');
        $userId = $userId ? $userId : $data['userId'];
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $data['shopId'];
        $id = (int)I('id');
        //获取订单信息
        $sql = "select oc.*,o.realTotalMoney,o.orderNo,o.orderId,o.createTime,o.userId,o.deliverMoney,o.requireTime,o.useScore,o.scoreMoney,o.couponId,p.shopName,p.shopId
			        from __PREFIX__order_complains oc,__PREFIX__orders o left join __PREFIX__shops p on o.shopId=p.shopId
			        where oc.orderId=o.orderId and oc.complainId=" . $id;
        if ($userType == 0) {
            $sql .= " and oc.complainTargetId=" . $userId;
        } else {
            $sql .= " and oc.needRespond=1 and oc.respondTargetId=" . $shopId;
        }
        $rs = $this->queryRow($sql);
        $rs['couponMoney'] = '0.00';
        if (empty($rs['useScore'])) {
            $rs['useScore'] = 0;
        }
        if (empty($rs['scoreMoney'])) {
            $rs['scoreMoney'] = '0.00';
        }
        if (!empty($rs['couponId'])) {
            $couponInfo = M('coupons')->where(['couponId' => $rs['couponId']])->find();
            $rs['couponMoney'] = $couponInfo['couponMoney'];
        }
        $refundFee = 0;
        if ($rs['complainStatus'] == 4) {
            $where = [];
            $where['orderId'] = $rs['orderId'];
            $where['goodsId'] = $rs['goodsId'];
            $where['skuId'] = $rs['skuId'];
            $complainsrecordInfo = M('order_complainsrecord')->where($where)->find();
            if ($complainsrecordInfo) {
                $refundFee = $complainsrecordInfo['money'];
            }
        }
        $rs['returnMoney'] = formatAmount($refundFee);
        if ($rs) {
            if ($rs['complainAnnex'] != '') $rs['complainAnnex'] = explode(',', $rs['complainAnnex']);
            if ($rs['respondAnnex'] != '') $rs['respondAnnex'] = explode(',', $rs['respondAnnex']);
            //获取相关商品
            $sql = "select og.orderId, og.goodsId ,og.skuId,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,g.SuppPriceDiff,og.skuId,og.skuSpecAttr
						from __PREFIX__goods g , __PREFIX__order_goods og
						WHERE g.goodsId = og.goodsId AND og.orderId =" . $rs['orderId'];
            $goods = $this->query($sql);
            foreach ($goods as $key => $val) {
                $goods[$key]['realyGoodsPrice'] = handleGoodsPayN($val['orderId'], $val['goodsId'], $val['skuId'], $rs['userId']) * $goods[$key]['goodsNums'];
                $goods[$key]['diffMoney'] = 0;
                if ($val['SuppPriceDiff'] == 1) {
                    //当前商品是否补差价过
                    $resPay = M('goods_pricediffe')->where("orderId='" . $val['orderId'] . "' AND goodsId='" . $val['goodsId'] . "' AND isPay=1")->sum('money');
                    $goods[$key]['realyGoodsPrice'] = $goods[$key]['realyGoodsPrice'] - $resPay;
                    $goods[$key]['diffMoney'] = $resPay;
                }
            }
            $rs["goodsList"] = $goods;
        }
        return $rs;
    }

    /**
     * 获取投诉(退货)详情
     * @param int $shopId
     * @param int $complainId 投诉/退货id
     */
    public function getOrderComplainDetail(int $shopId, int $complainId)
    {
        //获取订单信息
        $field = 'oc.*';
        $field .= ',o.realTotalMoney,o.orderNo,o.orderId,o.createTime,o.userId,o.deliverMoney,o.requireTime,o.useScore,o.scoreMoney,o.couponId,o.coupon_use_money';
        $field .= ',p.shopName,p.shopId';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__orders o on o.orderId=oc.orderId 
                left join __PREFIX__shops p on o.shopId=p.shopId
			    where oc.complainId=" . $complainId;
        $sql .= " and oc.needRespond=1 and oc.respondTargetId=" . $shopId;
        $rs = $this->queryRow($sql);
        if (empty($rs)) {
            return [];
        }
        $rs['couponMoney'] = 0;
        if (empty($rs['useScore'])) {
            $rs['useScore'] = 0;
        }
        if (empty($rs['scoreMoney'])) {
            $rs['scoreMoney'] = 0;
        }
        $rs['couponId'] = (int)$rs['couponId'];
        if (!empty($rs['couponId'])) {
//            $couponInfo = M('coupons')->where(['couponId' => $rs['couponId']])->find();
//            $rs['couponMoney'] = (float)$couponInfo['couponMoney'];
            $rs['couponMoney'] = $rs['coupon_use_money'];
        }
        $refundFee = 0;
        if ($rs['complainStatus'] == 4) {
            $where = [];
            $where['orderId'] = $rs['orderId'];
            $where['goodsId'] = $rs['goodsId'];
            $where['skuId'] = $rs['skuId'];
            $complainsrecordInfo = M('order_complainsrecord')->where($where)->find();
            if ($complainsrecordInfo) {
                $refundFee = $complainsrecordInfo['money'];
            }
        }
        $rs['returnMoney'] = (float)$refundFee;
        if (!empty($rs['complainAnnex'])) {
            $rs['complainAnnex'] = explode(',', $rs['complainAnnex']);
        } else {
            $rs['complainAnnex'] = [];
        }
        if (!empty($rs['respondAnnex'])) {
            $rs['respondAnnex'] = explode(',', $rs['respondAnnex']);
        } else {
            $rs['respondAnnex'] = [];
        }
        //获取相关商品
        $sql = "select og.orderId, og.goodsId ,og.skuId,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,g.SuppPriceDiff,og.skuId,og.skuSpecAttr,og.is_weight,og.couponMoney,og.scoreMoney
						from __PREFIX__goods g , __PREFIX__order_goods og
						WHERE g.goodsId = og.goodsId AND og.orderId =" . $rs['orderId'];
        $goods = $this->query($sql);
        $rs['goodsPriceTotal'] = 0;//合计
        $rs['realyGoodsPrice'] = 0;//建议退款金额合计
        //应诉商品信息
        $rs['returnNum'] = (float)$rs['returnNum'];
        $rs['returnNum'] = "{$rs['returnNum']}";
        foreach ($goods as $key => $val) {
            if ($val['goodsId'] != $rs['goodsId']) {
                unset($goods[$key]);
                continue;
            }
            if ($val['goodsId'] == $rs['goodsId'] && $val['skuId'] != $rs['skuId']) {
                unset($goods[$key]);
                continue;
            }
            $goods[$key]['goodsNums'] = $rs['returnNum'];
            $goods[$key]['goodsPriceTotal'] = $val['shopPrice'] * $goods[$key]['goodsNums'];//单商品金额小计
            $goods[$key]['realyGoodsPrice'] = handleGoodsPayN($val['orderId'], $val['goodsId'], $val['skuId'], $rs['userId']) * $goods[$key]['goodsNums'];//建议退款金额
            $discountAmount = bc_math($val['couponMoney'], $val['scoreMoney'], 'bcadd', 2); //该商品购买时使用的积分抵扣和优惠券抵扣
            if ($val['goodsNums'] == $rs['returnNum']) {//购买和退货数量一样直接全退
                $goods[$key]['realyGoodsPrice'] = bc_math($goods[$key]['goodsPriceTotal'], $discountAmount, 'bcsub', 2);
            }
            $goods[$key]['diffMoney'] = 0;
            if ($val['is_weight'] == 1) {
                //当前商品是否补差价过
                $resPay = M('goods_pricediffe')->where("orderId='" . $val['orderId'] . "' AND goodsId='" . $val['goodsId'] . "' AND isPay=1" . " and skuId={$val['skuId']}")->sum('money');
                $goods[$key]['realyGoodsPrice'] = bc_math($goods[$key]['realyGoodsPrice'], $resPay, 'bcsub', 2);
                $goods[$key]['diffMoney'] = (float)$resPay;
            }
            $rs['goodsPriceTotal'] += $goods[$key]['goodsPriceTotal'];
            $rs['realyGoodsPrice'] += $goods[$key]['realyGoodsPrice'];
        }
        //申诉人信息
        $where = [];
        $where['userId'] = $rs['complainTargetId'];
        $where['userFlag'] = 1;
        $field = 'userName,loginName,userPhone';
        $complainTargetInfo = M('users')->where($where)->field($field)->find();
        $rs['userName'] = $complainTargetInfo['userName'];
        $rs['loginName'] = $complainTargetInfo['loginName'];
        $rs['userPhone'] = $complainTargetInfo['userPhone'];
        $rs["goodsList"] = array_values($goods);
        $rs['is_can_deliverMoney'] = 1;//是否能退运费(1:已退运费 2:运费为0不需要退 3:可退运费)
        $rs['can_return_deliverMoney'] = '0.00';//最多可退运费金额
        $order_complains_tab = M('order_complains');
        $where = [];
        $where['orderId'] = $rs['orderId'];
        $where['returnFreight'] = 1;
        $return_deliverMoney_count = $order_complains_tab->where($where)->count();
        if ($return_deliverMoney_count <= 0 && (float)$rs['deliverMoney'] > 0) {
            $rs['is_can_deliverMoney'] = 3;
            $rs['can_return_deliverMoney'] = $rs['deliverMoney'];
        } elseif ($return_deliverMoney_count <= 0 && (float)$rs['deliverMoney'] <= 0) {
            $rs['is_can_deliverMoney'] = 2;
            $rs['can_return_deliverMoney'] = 0;
        }
        $rs['can_return_deliverMoney'] = formatAmount($rs['can_return_deliverMoney'], 2);
        return $rs;
    }

    /**
     * 保存订单商家应诉信息
     */
    public function saveRespond($shopInfo = array())
    {
        $loginUserInfo = $this->MemberVeri();
        $config = $GLOBALS['CONFIG'];
        $rd = array('status' => -1);
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $shopInfo['shopId'];
        $complainId = (int)I('complainId');
        $rules = array(
            array('respondContent', 'require', '应诉内容不能为空！', 1)
        );
        if ($this->validate($rules)->create()) {
            //判断是否提交过应诉和是否有效的投诉信息
            $sql = "select needRespond,complainStatus from __PREFIX__order_complains where complainId=" . $complainId . " and respondTargetId=" . $shopId;
            $rs = $this->queryRow($sql);
            if ((int)$rs['needRespond'] != 1) {
                $rd['msg'] = "无效的投诉信息";
                return $rd;
            }
            if ((int)$rs['complainStatus'] != 1) {
                $rd['msg'] = "该投诉订单已进行了应诉,请勿重复提交应诉信息";
                return $rd;
            }

            //是否退款 目前整单退 需调整单商品退款-----退款相关接口都有做出相应调整  此逻辑可封装函数 直接传入
            if ((int)I('isPay') == 1) {

                $mod_order_complains = M('order_complains')->where("complainId = {$complainId}")->find();
                $mod_order = M('orders')->where("orderId = {$mod_order_complains['orderId']}")->find();
                $pay_orderId = $mod_order_complains['orderId'];
                $pay_goodsId = $mod_order_complains['goodsId'];
                $pay_userId = $mod_order_complains['complainTargetId'];
                $pay_logUserId = $shopInfo['shopId'];
                $pay_transaction_id = $mod_order['tradeNo'];
                if ($config['setDeliveryMoney'] == 2) {//废弃
                    //兼容多商户和统一运费
                    $orderTokenInfo = M('order_merge')->where(['orderToken' => $mod_order['orderToken']])->find();
                    $orderNoArr = explode('A', $orderTokenInfo['value']);
                    $totalFee = 0;
                    foreach ($orderNoArr as $key => $value) {
                        $orderSingle = M('orders')->where(['orderNo' => $value])->find();
                        //$realTotalMoney = $orderSingle['realTotalMoney'] + $orderSingle['deliverMoney'];
                        $realTotalMoney = $orderSingle['realTotalMoney'];
                        $totalFee += $realTotalMoney;
                    }
                    $mod_order['realTotalMoney'] = $totalFee;
                } else {//处理常规非常规订单分单后运费问题
                    $orderTokenInfo = M('order_merge')->where(['orderToken' => $mod_order['orderToken']])->find();
                    $orderNoArr = explode('A', $orderTokenInfo['value']);
                    $totalFee = 0;
                    if (count($orderNoArr) > 1) {
                        foreach ($orderNoArr as $key => $value) {
                            $orderSingle = M('orders')->where(['orderNo' => $value])->find();
                            $realTotalMoney = $orderSingle['realTotalMoney'];
                            $totalFee += $realTotalMoney;
                        }
                        $mod_order['realTotalMoney'] = $totalFee;
                    }
                }
                $pay_total_fee = $mod_order['realTotalMoney'] * 100;
                $config = $GLOBALS['CONFIG'];
                $pay_refund_fee = goodsPayN($mod_order_complains['orderId'], $mod_order_complains['goodsId'], $mod_order_complains['complainTargetId']) * 100;
                //------最终需要考虑已经退还的差价 比如称重导致 以免退款失败-------
                //查询是否有差价退还
                $goodsInfo = M('goods')->where("goodsId='" . $mod_order_complains['goodsId'] . "'")->find();
                //改为手动退款
                $pay_refund_fee = I('refundFee');
                if ($pay_refund_fee <= 0) {
                    $rd['status'] = -1;
                    $rd['msg'] = "请填写正确的退款金额";
                    return $rd;
                }
                if ($goodsInfo['SuppPriceDiff'] == 1) {

                    //当前商品是否补差价过
                    // M('goods_pricediffe')->where()->find();

                    //------------------goods_pricediffe-----------------
                    //$resPay = M('goods_pricediffe')->where("orderId=".$mod_order_complains['orderId'])->sum('money');
                    /*$resPay = M('goods_pricediffe')->where("orderId='".$mod_order_complains['orderId']."' AND goodsId='".$goodsInfo['goodsId']."' AND isPay=1")->sum('money');
                    $pay_refund_fee = $pay_refund_fee - ($resPay*100);*/
                }


                //支付来源   0:现金 1:支付宝，2：微信 ，3：余额
                if ($mod_order['payFrom'] == 3) {//余额
                    $ispayOK = M('users')->where(array('userId' => $mod_order['userId']))->setInc('balance', $pay_refund_fee);
                    //退款成功，写入余额流水
                    if ($ispayOK) {
                        $updateBalance = M('user_balance')->add(array(
                            'userId' => $mod_order['userId'],
                            'balance' => $pay_refund_fee,
                            'dataSrc' => 1,
                            'orderNo' => $mod_order['orderNo'],
                            'dataRemarks' => '退款',
                            'balanceType' => 1,
                            'createTime' => date('Y-m-d H:i:s'),
                            'shopId' => $mod_order['shopId']
                        ));
                        if ($updateBalance) {
                            $add_data['orderId'] = $mod_order['orderId'];//订单id
                            $add_data['goodsId'] = $pay_goodsId;
                            $add_data['money'] = $pay_refund_fee;
                            $add_data['addTime'] = date('Y-m-d H:i:s');
                            $add_data['payType'] = 1;
                            $add_data['userId'] = $pay_userId;
                            $add_data['skuId'] = $mod_order_complains['skuId'];
                            M('order_complainsrecord')->add($add_data);

                            $push = D('Adminapi/Push');
                            $push->postMessage(8, $mod_order['userId'], $mod_order['orderNo'], $mod_order['shopId']);
                        }
                    }
                } else if ($mod_order['payFrom'] == 2) {//微信

                    //$ispayOK = wxRefund($pay_transaction_id,$pay_total_fee,$pay_refund_fee,$pay_orderId,$pay_goodsId,$pay_logUserId,$pay_userId);-1;//数据验证不通过-4;//有参数为空-3;//退款失败-2;//退款异常true 成功false 失败
                    $ispayOK = wxRefund($pay_transaction_id, $pay_total_fee, $pay_refund_fee * 100, $pay_orderId, $pay_goodsId, $pay_logUserId, $pay_userId, $mod_order_complains['skuId']);
                    -1;//数据验证不通过-4;//有参数为空-3;//退款失败-2;//退款异常true 成功false 失败
                }
                //$ispayOK = true;
                if ($ispayOK == true) {//如果退款成功 更改投诉状态

                    $this->complainStatus = 4;
                    $this->respondTime = date('Y-m-d H:i:s');
                    if (I('respondAnnex') != '') $this->respondAnnex = I('respondAnnex');

                    //写入系统自动仲裁
                    $this->finalResultTime = date('Y-m-d H:i:s');
                    $this->finalResult = '商家已同意！允许退款';
                    $this->finalHandleStaffId = 1;

                    $whereCheck['complainStatus'] = 4;
                    $whereCheck['complainId'] = $complainId;
                    $whereCheck['finalHandleStaffId'] = array('exp', 'is null');
                    $res = $this->where($whereCheck)->lock(true)->find();
                    if ($res) {
                        $rd["status"] = -1;
                        return $rd;
                    }

                    $rs = $this->where('complainId=' . $complainId)->save();

                    if ($rs !== false) {
                        //写入订单日志
                        unset($data);
//                        $log_orders = M("log_orders");
//                        $data["orderId"] =  $mod_order_complains['orderId'];
//                        $data["logContent"] =  "商家同意退款：".I('refundFee') . '元';
//                        $data["logUserId"] =  $mod_order_complains['complainTargetId'];
//                        $data["logType"] =  "0";
//                        $data["logTime"] =  date("Y-m-d H:i:s");
//                        $log_orders->add($data);
                        $content = "商家同意退款：" . I('refundFee') . '元';
                        $logParams = [
                            'orderId' => $mod_order_complains['orderId'],
                            'logContent' => $content,
                            'logUserId' => 0,
                            'logUserName' => '系统',
                            'orderStatus' => $mod_order['orderStatus'],
                            'payStatus' => 1,
                            'logType' => 2,
                            'logTime' => date('Y-m-d H:i:s'),
                        ];
                        M('log_orders')->add($logParams);

                        $rd['status'] = 1;
                        //添加报表
                        $rest = [];
                        $rest['refundFee'] = I('refundFee');
                        $rest['complainId'] = $complainId;

                    } else {
                        $rd['msg'] = '提交订单应诉信息失败';
                    }

                } else {
                    $rd['msg'] = '退款失败';
                    return $rd;
                }


                return $rd;


            }


            $this->complainStatus = 3;
            $this->respondTime = date('Y-m-d H:i:s');
            if (I('respondAnnex') != '') $this->respondAnnex = I('respondAnnex');
            $rs = $this->where('complainId=' . $complainId)->save();
            if ($rs !== false) {
                $rd['status'] = 1;
            } else {
                $rd['msg'] = '提交订单应诉信息失败';
            }
        } else {
            $rd['msg'] = $this->getError();
        }
        return $rd;
    }

    /**
     * 订单应诉
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params <p>
     * int complainId 投诉单id
     * int actionStatus 操作状态【-1:拒绝退货|1：确认退货|2：确认收货】
     * string remark 操作备注
     * int returnFreight 商家是否退运费【-1：不退运费|1：退运费】
     * float returnAmount 退款金额
     * </p>
     */
    public function respondingOrder(array $loginUserInfo, array $params)
    {
        $shopId = $loginUserInfo['shopId'];
        $complainId = (int)$params['complainId'];
        $remark = (string)$params['remark'];//操作备注
        $actionStatus = (int)$params['actionStatus'];
        $tab = M('order_complains');
        $where = [];
        $where['complainId'] = $complainId;
        $where['respondTargetId'] = $shopId;
        $complainsInfo = $tab->where($where)->find();
        if ($complainsInfo['needRespond'] != 1) {
            return returnData(false, -1, 'error', '无效的投诉信息');
        }
        $where = [];
        $where['orderId'] = $complainsInfo['orderId'];
        $orderInfo = M('orders')->where($where)->find();

        $actionTab = M('table_action_log');
        $actionParams = [];
        $actionParams['tableName'] = 'wst_order_complains';
        $actionParams['dataId'] = $complainId;
        $actionParams['actionUserId'] = $loginUserInfo['user_id'];
        $actionParams['actionUserName'] = $loginUserInfo['user_username'];
        $actionParams['createTime'] = date('Y-m-d H:i:s');
        $actionParams['remark'] = $remark;

        $save = [];
        $save['updateTime'] = date('Y-m-d H:i:s');
        if ($actionStatus == -1) {
            if ($complainsInfo['complainStatus'] != 0) {
                return returnData(false, -1, 'error', '非待处理状态不能执行该操作');
            }
            $actionParams['fieldName'] = 'complainStatus';
            $actionParams['fieldValue'] = -1;
            $save['complainStatus'] = -1;
            $save['respondTime'] = date('Y-m-d H:i:s');
            $logContent = '商家拒绝退货';
        } elseif ($actionStatus == 1) {
            if ($complainsInfo['complainStatus'] != 0) {
                return returnData(false, -1, 'error', '非待处理状态不能执行该操作');
            }
            $actionParams['fieldName'] = 'complainStatus';
            $actionParams['fieldValue'] = 1;
            $save['returnFreight'] = null;
            $save['returnAmount'] = null;
            $save['complainStatus'] = 1;
            $save['respondTime'] = date('Y-m-d H:i:s');
            parm_filter($save, $params);
            if ((float)$save['returnAmount'] > 0) {
                $proposal_info = $this->getOrderComplainDetail($shopId, $complainId);
                $max_return_amount = $proposal_info['realyGoodsPrice'];
                if ($save['returnFreight'] == 1) {
                    $max_return_amount = bc_math($max_return_amount, $proposal_info['deliverMoney'], bcadd, 2);
                }
                if ((float)$save['returnAmount'] > $max_return_amount) {
                    return returnData(false, -1, 'error', "本次最多退款：{$max_return_amount}元");
                }
            }
            $logContent = '商家已同意退货，正在退货中';
        } elseif ($actionStatus == 2) {
            if ($complainsInfo['complainStatus'] != 1) {
                return returnData(false, -1, 'error', '非退货中状态不能执行该操作');
            }
            $actionParams['fieldName'] = 'complainStatus';
            $actionParams['fieldValue'] = 2;
            $save['complainStatus'] = 2;
            $logContent = '商家已确认收货，退货完成';
        }
        M()->startTrans();
        $where = [];
        $where['complainId'] = $complainId;
        $updateRes = $tab->where($where)->save($save);
        if (!$updateRes) {
            M()->rollback();
            return returnData(false, -1, 'error', '操作失败');
        }
        //添加报表-start
        if ($actionStatus == 2) {
            $param = [];
            $param['complainId'] = $complainId;
            $param['returnAmount'] = $complainsInfo['returnAmount'];//实际退款金额
            addReportForms($complainsInfo['orderId'], 5, $param, M());

            //创建入库单
            //代码太旧,直接临时在这里加入
            $goodsModule = new GoodsModule();
            $goodsDetail = $goodsModule->getGoodsInfoById($complainsInfo['goodsId'], 'goodsUnit,goodsName', 2);
            $warehousPrice = $goodsDetail['goodsUnit'];
            if (!empty($complainsInfo['skuId'])) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($complainsInfo['skuId'], 2);
                $warehousPrice = $skuDetail['purchase_price'];
            }
            $warehousBill = array(
                'loginInfo' => $loginUserInfo,
                'billType' => 4,
                'relationBillId' => $complainsInfo['complainId'],
                'relationBillNo' => $orderInfo['orderNo'],
                'goodsData' => array(
                    array(
                        'goodsId' => $complainsInfo['goodsId'],
                        'skuId' => $complainsInfo['skuId'],
                        'warehousNumTotal' => $complainsInfo['returnNum'],
                        'warehousPrice' => $warehousPrice,
                        'goodsRemark' => '',
                    )
                )
            );
            $warehousingModule = new WarehousingBillModule();
            $wareRes = $warehousingModule->createWarehousingBill($warehousBill, M());
            if ($wareRes['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货失败", "入库单创建失败");
            }
        }
        //添加报表-end
        $actionTab->add($actionParams);//退货操作日志
        //订单操作日志
        $logParams = [
            'orderId' => $complainsInfo['orderId'],
            'logContent' => $logContent,
            'logUserId' => $loginUserInfo['user_id'],
            'logUserName' => $loginUserInfo['user_username'],
            'orderStatus' => $orderInfo['orderStatus'],
            'payStatus' => 1,
            'logType' => 1,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        M('log_orders')->add($logParams);
        M()->commit();
        return returnData(true);
    }

    /**
     * 已废弃
     * 获取商家被投诉订单,未解决的数量
     */
    public function queryShopComplainNum($obj = array())
    {
        $shopId = (int)session('WST_USER.shopId');
        if (empty($shopId)) {
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId ? $shopId : $obj['shopId'];
        $orderNo = WSTAddslashes(I('orderNo'));
        $sql = "select count(oc.complainId) from __PREFIX__order_complains oc left join __PREFIX__users u on oc.complainTargetId=u.userId,
		        __PREFIX__orders o where oc.needRespond=1 and oc.orderId=o.orderId and o.orderFlag=1 and oc.respondTargetId=" . $shopId;
        $sql .= " and oc.complainStatus IN(0,1)";
        $res = $this->query($sql);
        $num = 0;
        if ($res[0]['count(oc.complainId)'] > 0) {
            $num = $res[0]['count(oc.complainId)'];
        }
        return ['state' => 0, 'num' => $num];
    }

    /**
     *退货/退款申请操作日志
     * @param int $complainId
     * @return array $data
     * */
    public function getOrderComplainsLog($complainId)
    {
        $tab = M('table_action_log');
        $where = [];
        $where['tableName'] = 'wst_order_complains';
        $where['dataId'] = $complainId;
        $data = $tab->where($where)->select();
        return (array)$data;
    }

    /**
     *退款列表
     * @param array $params <p>
     * int shopId
     * int returnAmountStatus 退款状态【-1：已拒绝|0：待处理|1：已处理】
     * string orderNo 订单号
     * string receivingPeople 联系人姓名/手机号/账号
     * string startDate 申请时间区间-开始时间
     * string endDate 申请时间区间-结束时间
     * string respondTime_start 应诉时间区间-开始时间
     * string respondTime_end 应诉时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @return array $data
     */
    public function getReturnAmountList(array $params)
    {
        $shopId = (int)$params['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = "oc.respondTargetId={$shopId} and o.orderFlag=1 and u.userFlag=1 and oc.complainStatus=2 ";
        if (!empty($params['receivingPeople'])) {
            $where .= " and (u.userName like '%{$params['receivingPeople']}%' or u.loginName like '%{$params['receivingPeople']}%'or u.userPhone like '%{$params['receivingPeople']}%') ";
        }
        $whereFind = [];
        $whereFind['oc.returnAmountStatus'] = function () use ($params) {
            if (!is_numeric($params['returnAmountStatus'])) {
                return null;
            }
            return ['=', "{$params['returnAmountStatus']}", 'and'];
        };
        $whereFind['o.orderNo'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['oc.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['oc.respondTime'] = function () use ($params) {
            if (empty($params['respondTime_start']) || empty($params['respondTime_end'])) {
                return null;
            }
            return ['between', "{$params['respondTime_start']}' and '{$params['respondTime_end']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = " {$where} and {$whereFind} ";
        }
        $field = 'oc.complainId,oc.complainContent,oc.returnAmountStatus,oc.createTime,oc.returnAmount,oc.goodsId';
        $field .= ',o.orderId,o.orderNo,o.realTotalMoney';
        $field .= ',u.loginName,u.userName,u.userPhone';
        $field .= ',record.addTime as returnAmountTime ';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__users u on oc.complainTargetId=u.userId
                left join __PREFIX__orders o on o.orderId=oc.orderId
                left join __PREFIX__order_complainsrecord record on record.complainId=oc.complainId
                where {$whereInfo} ";
        $sql .= " group by oc.complainId ";
        $sql .= " order by oc.complainId desc";
        if ($params['export'] == 1) {
            //导出
            $data['root'] = $this->query($sql);
        } else {
            $data = $this->pageQuery($sql, $page, $pageSize);
        }
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                $item['returnAmountTime'] = (string)$item['returnAmountTime'];
            }
            unset($item);
            $data['root'] = $list;
        }
        if ($params['export'] == 1) {
            $this->exportReturnAmountList($data['root'], $params);
        }
        return $data;
    }

    /**
     * 导出退款列表
     * @param array $orderList 需要导出的退款数据
     * @param array $params 前端传过来的参数
     * */
    public function exportReturnAmountList(array $list, array $params)
    {
        //拼接表格信息
        $date = '';
        $startDate = '';
        $endDate = '';
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        }
        //794px
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        $body .= "
            <tr>
                <th style='width:40px;'>序号</th>
                <th style='width:150px;'>服务单号</th>
                <th style='width:150px;'>申请时间</th>
                <th style='width:150px;'>用户账号</th>
                <th style='width:80px;'>退款金额</th>
                <th style='width:150px;'>申请状态</th>
                <th style='width:150px;'>处理时间</th>
            </tr>";
        $num = 0;
        foreach ($list as $okey => $ovalue) {
            $statusName = '未知';
            if ($ovalue['returnAmountStatus'] == -1) {
                $statusName = '已拒绝';
            } elseif ($ovalue['returnAmountStatus'] == 0) {
                $statusName = '待处理';
            } elseif ($ovalue['returnAmountStatus'] == 1) {
                $statusName = '已处理';
            }
            $key = $okey + 1;
            $rowspan = 1;
            $body .=
                "<tr align='center'>" .
                "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//申请时间
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['loginName'] . "</td>" .//用户账号
                "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['returnAmount'] . "</td>" .//退款金额
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $statusName . "</td>" .//申请状态
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['returnAmountTime'] . "</td>" .//处理时间
                "</tr>";
        }
        $headTitle = "退款数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 退款统计
     * @param int $shopId
     * */
    public function getReturnAmountNum(int $shopId)
    {
        $shopId = (int)$shopId;
        $tab = M('order_complains oc');
        $where = [];
        $where['oc.respondTargetId'] = $shopId;
        $where['oc.complainStatus'] = 2;
        $where['o.orderFlag'] = 1;
        $where['u.userFlag'] = 1;
        $alllist = $tab
            ->join('left join wst_users u on oc.complainTargetId=u.userId')
            ->join('left join wst_orders o on o.orderId=oc.orderId')
            ->where($where)
            ->select();
        $allNum = 0;//全部
        $rejectedNum = 0;//已拒绝
        $pendingNum = 0;//待处理
        $completedNum = 0;//已处理
        foreach ($alllist as $item) {
            $allNum += 1;
            if ($item['returnAmountStatus'] == -1) {
                $rejectedNum += 1;
            } elseif ($item['returnAmountStatus'] == 0) {
                $pendingNum += 1;
            } elseif ($item['returnAmountStatus'] == 1) {
                $completedNum += 1;
            }
        }
        $data = [];
        $data['allNum'] = $allNum;
        $data['rejectedNum'] = $rejectedNum;
        $data['pendingNum'] = $pendingNum;
        $data['completedNum'] = $completedNum;
        return $data;
    }

    /**
     * 退款操作
     * @param array $login_user_info 当前登陆者信息
     * @param array $params <p>
     * int complainId 退款单id
     * int returnAmountStatus 退款状态【-1：拒绝退款|1：同意退款】
     * string remark 操作备注
     * </p>
     * */
    public function doReturnAmount(array $login_user_info, array $params)
    {
        $tab = M('order_complains');
        $order_tab = M('orders');
        $shopId = $login_user_info['shopId'];
        $config = $GLOBALS['CONFIG'];
        $returnAmountStatus = $params['returnAmountStatus'];
        $complainId = (int)$params['complainId'];
        $remark = (string)$params['remark'];
        $where = [];
        $where['complainId'] = $complainId;
        $where['respondTargetId'] = $shopId;
        $info = $tab->where($where)->find();
        if ($info['needRespond'] != 1) {
            return returnData(false, -1, 'error', '无效的单据');
        }
        if ($info['complainStatus'] != 2) {
            return returnData(false, -1, 'error', '退货未完成的单据不能退款');
        }
        if ($info['returnAmountStatus'] != 0) {
            return returnData(false, -1, 'error', '处理过的单据不能重复处理');
        }
        vendor('RedisLock.RedisLock');
        $redis = new \Redis;
        $redis->connect(C('redis_host1'), C('redis_port1'));
        $redisLock = \RedisLock::getInstance($redis);
        $lockName = "doReturnAmount:id:" . $params['complainId'];
        $redisLockRes = $redisLock->lock($lockName);
        if (!$redisLockRes) {
            return returnData(false, -1, 'error', '系统繁忙，请稍后处理');
        }
        $orderId = $info['orderId'];
        $where = [];
        $where['orderId'] = $orderId;
        $order_info = $order_tab->where($where)->find();
        $goodsId = $info['goodsId'];
        $skuId = $info['skuId'];
        $userId = $info['complainTargetId'];
        $pay_transaction_id = (string)$order_info['tradeNo'];
        $order_merge_tab = M('order_merge');
        if ($config['setDeliveryMoney'] == 2) {//废弃
            //兼容多商户和统一运费
            $where = [];
            $where['orderToken'] = $order_info['orderToken'];
            $order_token_info = $order_merge_tab->where($where)->find();
            $orderNo_arr = explode('A', $order_token_info['value']);
            $total_fee = 0;
            foreach ($orderNo_arr as $key => $value) {
                $order_single = $order_tab->where(['orderNo' => $value])->find();
                $realTotalMoney = $order_single['realTotalMoney'];
                $total_fee += $realTotalMoney;
            }
            $order_info['realTotalMoney'] = $total_fee;
        } else {//处理常规非常规订单拆单后运费问题
            $where = [];
            $where['orderToken'] = $order_info['orderToken'];
            $order_token_info = $order_merge_tab->where($where)->find();
            $orderNo_arr = explode('A', $order_token_info['value']);
            $total_fee = 0;
            if (count($orderNo_arr) > 1) {
                foreach ($orderNo_arr as $key => $value) {
                    $order_single = $order_tab->where(['orderNo' => $value])->find();
                    $realTotalMoney = $order_single['realTotalMoney'];
                    //$total_fee += $realTotalMoney;
                    $total_fee = bc_math($total_fee, $realTotalMoney, 'bcadd', 2);
                }
                $order_info['realTotalMoney'] = $total_fee;
            }
        }
        M()->startTrans();
        $table_action_log_tab = M('table_action_log');
        $log_orders_tab = M('log_orders');
        if ($returnAmountStatus == -1) {
            //拒绝退款
            $save = [];
            $save['returnAmountStatus'] = -1;
            $save['updateTime'] = date('Y-m-d H:i:s');
            $where = [];
            $where['complainId'] = $complainId;
            $updateRes = $tab->where($where)->save($save);
            if (!$updateRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '操作失败');
            }
            //添加报表-start
            addReportForms($orderId, 2, array('complainId' => $complainId, 'refundFee' => 0), M());
            //添加报表-end
            //写入订单日志
            $logParams = [
                'orderId' => $orderId,
                'logContent' => '商家拒绝退款',
                'logUserId' => $login_user_info['user_id'],
                'logUserName' => $login_user_info['user_username'],
                'orderStatus' => $order_info['orderStatus'],
                'payStatus' => 2,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            $log_orders_tab->add($logParams);
            //写入状态变动记录表
            $logParams = [
                'tableName' => 'wst_order_complains',
                'dataId' => $complainId,
                'actionUserId' => $login_user_info['user_id'],
                'actionUserName' => $login_user_info['user_username'],
                'fieldName' => 'returnAmountStatus',
                'fieldValue' => $params['returnAmountStatus'],
                'remark' => $remark,
                'createTime' => date('Y-m-d H:i:s'),
            ];
            $table_action_log_tab->add($logParams);
            M()->commit();
            return returnData(true);
        }
        //同意退款
        $orderModuel = new OrdersModule();
        $complainDetail = $orderModuel->getOrderGoodsComplainsDetailByParams($orderId, $goodsId, $skuId);
        $info['returnAmount'] = $complainDetail['original_road_money'];
        if ($complainDetail['quota_road_money'] > 0) {
            $sorting_module = new SortingModule();
            $inc_quota_arrears_res = $sorting_module->decUserQuotaArrears($orderId, $goodsId, $skuId, $complainDetail['diffWeight'], M());
            if (!$inc_quota_arrears_res) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，用户欠款额度处理异常', "用户欠款额度处理异常");
            }
        }
        $pay_total_fee = (float)bc_math($order_info['realTotalMoney'], 100, 'bcmul', 2);
        $pay_refund_fee = $info['returnAmount'];//退款金额
        if ($info['returnAmount'] > 0) {
            //创建客户结算单
            $customerSettlementModule = new CustomerSettlementBillModule();
            $settlementData = array(
                'loginInfo' => $login_user_info,
                'relation_order_id' => $complainId,
                'billFrom' => 2,
            );
            $addSettlementRes = $customerSettlementModule->createCustomerSerrlementBill($settlementData, M());
            if ($addSettlementRes['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "客户结算单创建失败");
            }
            //支付来源   0:现金 1:支付宝，2：微信 ，3：余额
            if ($order_info['payFrom'] == 3) {
                $payType = 3;//支付类型[1:微信 2:支付宝 3:余额]
                $users_tab = M('users');
                $where = [];
                $where['userId'] = $userId;
                $returnBalanceRes = $users_tab->where($where)->setInc('balance', $pay_refund_fee);
                if (!$returnBalanceRes) {
                    M()->rollback();
                    return returnData(false, -1, 'error', '退款失败');
                }
                $update_balance_res = M('user_balance')->add(array(
                    'userId' => $userId,
                    'balance' => $pay_refund_fee,
                    'dataSrc' => 1,
                    'orderNo' => $order_info['orderNo'],
                    'dataRemarks' => '退款',
                    'balanceType' => 1,
                    'createTime' => date('Y-m-d H:i:s'),
                    'shopId' => $order_info['shopId']
                ));
                if (!$update_balance_res) {
                    M()->rollback();
                    return returnData(false, -1, 'error', '余额流水记录失败');
                }
                $logContent = "发起余额退款：" . $pay_refund_fee . '元';
                $pay_res = returnData(true);
                $push = D('Adminapi/Push');
                $push->postMessage(8, $userId, $order_info['orderNo'], $order_info['shopId']);
            } elseif ($order_info['payFrom'] == 2) {//微信
                $payType = 1;
                $pay_res = wxRefund($pay_transaction_id, $pay_total_fee, $pay_refund_fee * 100, $orderId, $goodsId, $skuId);
                $logContent = "发起微信退款：" . $pay_refund_fee . '元';
            } elseif ($order_info['payFrom'] == 1) {//支付宝
                $payType = 2;
                $payModule = new PayModule();
                $pay_res = $payModule->aliPayRefund($pay_transaction_id, $pay_refund_fee);
                $logContent = "发起支付宝退款：" . $pay_refund_fee . '元';
            }
        } else {
            $payType = $order_info['payFrom'];
            $pay_res = returnData(true);
        }
        if ($pay_res['code'] != 0) {
            M()->rollback();
            $err_msg = '退款失败';
            if ($order_info['payFrom'] == 2 || $order_info['payFrom'] == 1) {
                if (!empty($pay_res['msg'])) {
                    $err_msg = $pay_res['msg'];
                }
            }
            return returnData(false, -1, 'error', $err_msg);
        }
        //添加报表-start
        addReportForms($orderId, 2, array('complainId' => $complainId, 'refundFee' => $pay_refund_fee), M());
        //添加报表-end
        //写入订单日志
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $logContent,
            'logUserId' => 0,
            'logUserName' => '系统',
            'orderStatus' => $order_info['orderStatus'],
            'payStatus' => 2,
            'logType' => 0,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        $log_orders_tab->add($logParams);
        $add_data = [];
        $add_data['orderId'] = $order_info['orderId'];
        $add_data['goodsId'] = $goodsId;
        $add_data['money'] = $pay_refund_fee;
        $add_data['addTime'] = date('Y-m-d H:i:s');
        $add_data['payType'] = $payType;
        $add_data['userId'] = $userId;
        $add_data['skuId'] = $skuId;
        $add_data['complainId'] = $complainId;
        $record_res = M('order_complainsrecord')->add($add_data);
        if (!$record_res) {
            M()->rollback();
            return returnData(false, -1, 'error', '退款记录失败');
        }
        //退款成功,更改退款状态
        $where = [];
        $where['complainId'] = $complainId;
        $save = [];
        $save['returnAmountStatus'] = 1;
        $save['updateTime'] = date('Y-m-d H:i:s');
        $returnRes = $tab->where($where)->save($save);
        if (!$returnRes) {
            M()->rollback();
            return returnData(false, -1, 'error', '退款失败-退款状态修改失败');
        }
        //写入状态变动记录表
        $logParams = [
            'tableName' => 'wst_order_complains',
            'dataId' => $complainId,
            'actionUserId' => $login_user_info['user_id'],
            'actionUserName' => $login_user_info['user_username'],
            'fieldName' => 'returnAmountStatus',
            'fieldValue' => $params['returnAmountStatus'],
            'remark' => $remark,
            'createTime' => date('Y-m-d H:i:s'),
        ];
        $table_action_log_tab->add($logParams);
        M()->commit();
        //添加报表
        $rest = [];
        $rest['refundFee'] = $info['returnAmount'];
        $rest['complainId'] = $complainId;
        $redisLock->unlock($lockName);
        return returnData(true);
    }

}

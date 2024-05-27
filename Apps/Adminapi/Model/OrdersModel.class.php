<?php

namespace Adminapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Goods\GoodsModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Pay\PayModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\Users\UsersServiceModule;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单服务类
 */
class OrdersModel extends BaseModel
{
    /**
     * 获取订单详细信息
     */
    public function getDetail()
    {

        $id = (int)I('id', 0);
        $sql = "select o.*,s.shopName from __PREFIX__orders o
	 	         left join __PREFIX__shops s on o.shopId=s.shopId 
	 	         where o.orderFlag=1 and o.orderId=" . $id;
        $rs = $this->queryRow($sql);
        //获取用户详细地址
        $sql = 'select communityName,a1.areaName areaName1,a2.areaName areaName2,a3.areaName areaName3 from __PREFIX__communitys c 
		        left join __PREFIX__areas a1 on a1.areaId=c.areaId1 
		        left join __PREFIX__areas a2 on a2.areaId=c.areaId2
		        left join __PREFIX__areas a3 on a3.areaId=c.areaId3
		        where c.communityId=' . $rs['communityId'];
        $cRs = $this->queryRow($sql);
        $rs['userAddress'] = $cRs['areaName1'] . $cRs['areaName2'] . $cRs['areaName3'] . $cRs['communityName'] . $rs['userAddress'];
        //获取日志信息

        $sql = "select lo.*,u.loginName,u.userType,s.shopName from __PREFIX__log_orders lo
		         left join __PREFIX__users u on lo.logUserId = u.userId
		         left join __PREFIX__shops s on u.userType!=0 and s.userId=u.userId
		         where orderId=" . $id . ' order by lo.logTime asc ';
        $rs['log'] = $this->query($sql);
        //获取相关商品
        $sql = "select og.*,g.goodsThums,g.goodsName,g.goodsId from __PREFIX__order_goods og
			        left join __PREFIX__goods g on og.goodsId=g.goodsId
			        where og.orderId = " . $id;
        $rs['goodslist'] = $this->query($sql);

        //处理发票信息
        if ($rs['isInvoice'] == 1 && $rs['invoiceClient'] > 0) {
            $where['id'] = $rs['invoiceClient'];
            $field = 'inv.headertype,inv.headerName,inv.taxNo,inv.address,inv.number,inv.depositaryBank,inv.account,inv.addtime,u.userName';
            $rs['invoiceInfo'] = M('invoice')->alias('inv')
                ->join("LEFT JOIN wst_users u ON u.userId=inv.userId ")
                ->where($where)
                ->field($field)
                ->find();
            if (!$rs['invoiceInfo']) {
                $rs['invoiceInfo'] = array();
            }
        } else {
            $rs['invoiceInfo'] = array();
        }

        $config = $GLOBALS['CONFIG'];
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($rs['isPay'] == 1) {
                //$rs['realTotalMoney'] = $rs['realTotalMoney']+$rs['deliverMoney'];
                $rs['realTotalMoney'] = $rs['realTotalMoney'];
            } else {
                if ($rs['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $rs['realTotalMoney'] = $rs['realTotalMoney'] + $rs['setDeliveryMoney'];
                    $rs['deliverMoney'] = $rs['setDeliveryMoney'];
                }
            }
        } else {//处理常规非常规拆单后运费问题
            if ($rs['isPay'] != 1 && $rs['setDeliveryMoney'] > 0) {
                $rs['realTotalMoney'] = $rs['realTotalMoney'] + $rs['setDeliveryMoney'];
                $rs['deliverMoney'] = $rs['setDeliveryMoney'];
            }
        }
        if ($rs['isSelf'] == 1) {
            $rs['deliverMoney'] = 0;
        }
        return $rs;
    }

    /**
     * 获取订单信息
     */
    public function get()
    {
        return $this->where('isRefund=0 and payType=1 and isPay=1 and orderFlag=1 and orderStatus in (-1,-4,-6,-7) and orderId=' . (int)I('id'))->find();
    }

    /**
     * 订单分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {

        $shopName = WSTAddslashes(I('shopName'));
        $orderNo = WSTAddslashes(I('orderNo'));
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $areaId3 = (int)I('areaId3', 0);
        $orderStatus = I('orderStatus', -9999);
        $startDate = I('startDate');//2018-02-12 00:00:00
        $endDate = I('endDate');
        if ($orderStatus === '') {
            $orderStatus = -9999;
        }
        $export = (int)I('export');
        $sql = "select o.orderId,o.orderNo,o.totalMoney,o.realTotalMoney,o.orderStatus,o.setDeliveryMoney,o.isPay,o.orderRemarks,o.deliverMoney,o.payType,o.createTime,s.shopName,o.userName,o.userPhone,o.userAddress from __PREFIX__orders o
	 	         left join __PREFIX__shops s on o.shopId=s.shopId  where o.orderFlag=1 ";
        if ($areaId1 > 0) $sql .= " and s.areaId1=" . $areaId1;
        if ($areaId2 > 0) $sql .= " and s.areaId2=" . $areaId2;
        if ($areaId3 > 0) $sql .= " and s.areaId3=" . $areaId3;
        if ($shopName != '') $sql .= " and (s.shopName like '%" . $shopName . "%' or s.shopSn like '%" . $shopName . "%')";
        if ($orderNo != '') $sql .= " and o.orderNo like '%" . $orderNo . "%' ";
        if ($orderStatus != -9999 && $orderStatus != -100 && !in_array($orderStatus, [16, 17, 18, 19])) $sql .= " and o.orderStatus=" . $orderStatus;
        if ($orderStatus == -100) $sql .= " and o.orderStatus in(-6,-7)";
        //后加状态
        if ($orderStatus == 16) {
            //已支付
            $sql .= " and o.isPay=1 ";
        }
        if ($orderStatus == 17) {
            //已支付未退款
            $sql .= " and o.isPay=1 and o.isRefund=0 and orderStatus >=0 ";
        }
        if ($orderStatus == 18) {
            //已支付已退款
            $sql .= " and o.isPay=1 and o.isRefund=1 ";
        }
        /*if($orderStatus == 19){
            //未支付
            $sql .= " and o.isPay=0 ";
        }*/
        //后加状态
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " and o.createTime between '" . $startDate . "' and '" . $endDate . "' ";
        }
        $sql .= " order by orderId desc";
        if ($export != 1) {
            $page = $this->pageQuery($sql, $page, $pageSize);
        } else {
            //导出
            $page = [];
            $page['root'] = $this->query($sql);
        }
        $config = $GLOBALS['CONFIG'];
        //获取涉及的订单及商品
        if (count($page['root']) > 0) {
            $orderIds = array();
            foreach ($page['root'] as $key => $v) {
                $orderIds[] = $v['orderId'];
            }
            $sql = "select og.* from __PREFIX__order_goods og
			        where og.orderId in(" . implode(',', $orderIds) . ")";
            $rs = $this->query($sql);
            $goodslist = array();
            foreach ($rs as $key => $v) {
                $goodslist[$v['orderId']][] = $v;
            }
            foreach ($page['root'] as $key => $v) {
                if ($config['setDeliveryMoney'] == 2) {//废弃
                    if ($page['root'][$key]['isPay'] == 1) {
                        //$page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney']+$page['root'][$key]['deliverMoney'];
                        $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'];
                    } else {
                        if ($page['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'] + $page['root'][$key]['setDeliveryMoney'];
                            $page['root'][$key]['deliverMoney'] = $page['root'][$key]['setDeliveryMoney'];
                        }
                    }
                } else {//处理常规非常规订单拆分后运费问题
                    if ($page['root'][$key]['isPay'] != 1 && $page['root'][$key]['setDeliveryMoney'] > 0) {
                        $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'] + $page['root'][$key]['setDeliveryMoney'];
                        $page['root'][$key]['deliverMoney'] = $page['root'][$key]['setDeliveryMoney'];
                    }
                }
                $page['root'][$key]['goodslist'] = $goodslist[$v['orderId']];
            }
        }
        if ($export == 1) {
            $this->exportOrderList($page['root'], I());
        }
        return $page;
    }

    /**
     * 导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrderList(array $orderList, array $params)
    {
        //处理订单商品信息
        foreach ($orderList as $key => $value) {
            $rowspan = 0;//下面的导出表格会用到
            $orderGoods = $value['goodslist'];
            $goodsList = [];
            foreach ($value['goodslist'] as $gval) {
                $goodsInfo = [];
                $goodsInfo['goodsId'] = $gval['goodsId'];
                $goodsInfo['goodsName'] = $gval['goodsName'];
                $goodsInfo['goodsPrice'] = $gval['goodsPrice'];
                $goodsInfo['goodsNums'] = 0;
                $goodsInfo['remarks'] = $gval['remarks'];
                $goodsList[] = $goodsInfo;
            }
            $unquieGoods = arrayUnset($goodsList, 'goodsId');
            foreach ($unquieGoods as $uKey => $uVal) {
                $skulist = [];
                foreach ($orderGoods as $oVal) {
                    if ($oVal['goodsId'] == $uVal['goodsId']) {
                        $unquieGoods[$uKey]['goodsNums'] += $oVal['goodsNums'];
                        if (!empty($oVal['skuId'])) {
                            $skuInfo = [];
                            $skuInfo['skuId'] = $oVal['skuId'];
                            $skuInfo['goodsNums'] = $oVal['goodsNums'];
                            $skuInfo['goodsPrice'] = $oVal['goodsPrice'];
                            $skuInfo['goodsAttrName'] = $oVal['goodsAttrName'];
                            $skuInfo['remarks'] = $oVal['remarks'];
                            $skulist[] = $skuInfo;
                        }
                    }
                }
                $unquieGoods[$uKey]['skulist'] = $skulist;
            }
            $orderList[$key]['goodslist'] = $unquieGoods;
            $orderList[$key]['rowspan'] = count($unquieGoods);
        }

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
                <th style='width:100px;'>订单号</th>
                <th style='width:100px;'>店铺名称</th>
                <th style='width:200px;'>收货人信息</th>
                <th style='width:150px;'>商品</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:50px;'>小计</th>
                <th style='width:80px;'>实付金额</th>
                <th style='width:100px;'>订单状态</th>
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>下单时间</th>
            </tr>";
        $num = 0;
        foreach ($orderList as $okey => $ovalue) {
            $orderStautsMsg = '';
            if ($ovalue['orderStatus'] == -7) {
                $orderStautsMsg = '⽤⽤户取消 (受理后)';
            }
            if ($ovalue['orderStatus'] == -6) {
                $orderStautsMsg = '⽤户取消(受理中)';
            }
            if ($ovalue['orderStatus'] == -5) {
                $orderStautsMsg = '⻔店不同意拒收';
            }
            if ($ovalue['orderStatus'] == -4) {
                $orderStautsMsg = '⻔店同意拒收';
            }
            if ($ovalue['orderStatus'] == -3) {
                $orderStautsMsg = '用户拒收';
            }
            if ($ovalue['orderStatus'] == -2) {
                $orderStautsMsg = '未付款的订单';
            }
            if ($ovalue['orderStatus'] == -1) {
                $orderStautsMsg = '⽤户取消(未受理)';
            }
            if ($ovalue['orderStatus'] == 0) {
                $orderStautsMsg = '未受理';
            }
            if ($ovalue['orderStatus'] == 1) {
                $orderStautsMsg = '已受理';
            }
            if ($ovalue['orderStatus'] == 2) {
                $orderStautsMsg = '打包中';
            }
            if ($ovalue['orderStatus'] == 3) {
                $orderStautsMsg = '配送中';
            }
            if ($ovalue['orderStatus'] == 4) {
                $orderStautsMsg = '⽤户确认收货';
            }
            if ($ovalue['orderStatus'] == 7) {
                $orderStautsMsg = '等待骑⼿接单';
            }
            if ($ovalue['orderStatus'] == 8) {
                $orderStautsMsg = '骑⼿-待取货';
            }
            if ($ovalue['orderStatus'] == 9) {
                $orderStautsMsg = '骑⼿-订单被取消';
            }
            if ($ovalue['orderStatus'] == 10) {
                $orderStautsMsg = '骑⼿-订单过期';
            }
            if ($ovalue['orderStatus'] == 11) {
                $orderStautsMsg = '骑⼿-投递异常';
            }
            if ($ovalue['orderStatus'] == 12) {
                $orderStautsMsg = '预售订单(未⽀付)';
            }
            if ($ovalue['orderStatus'] == 13) {
                $orderStautsMsg = '预售订单(⾸款已付)';
            }
            if ($ovalue['orderStatus'] == 14) {
                $orderStautsMsg = '预售订单-已付款';
            }
            if ($ovalue['orderStatus'] == 15) {
                $orderStautsMsg = '拼团';
            }
            $orderGoods = $ovalue['goodslist'];
            $rowspan = $ovalue['rowspan'];
            $key = $okey + 1;
            $userDetailAddress = '';
            $userDetailAddress .= '用户名：' . $ovalue['userName'] . '<br>';
            $userDetailAddress .= '电话：' . $ovalue['userPhone'] . '<br>';
            $userDetailAddress .= '收货地址：' . $ovalue['userAddress'] . '<br>';
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                /*if(!empty($gVal['skulist'])){
                    $rowspan = count($gVal['skulist']);
                }*/
                $num++;
                $goodsNums = "<td style='width:30px;'>" . $gVal['goodsNums'] . "</td>";//数量;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }
                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['shopName'] . "</td>" .//店铺名称
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $orderStautsMsg . "</td>" .//订单状态
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['goodsAttrName'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['shopName'] . "</td>" .//店铺名称
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $orderStautsMsg . "</td>" .//订单状态
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                                    "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    /*$headTitle = "订单数据";
                    $filename = $headTitle . ".xls";
                    usePublicExport($body,$headTitle,$filename,$date);*/
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                                "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                                "</tr>";
                        }
                    }
                }
            }

        }
        $headTitle = "订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * 获取退款列表
     */
    public function queryRefundByPage()
    {
        $page = I('page', 1);
        $shopName = WSTAddslashes(I('shopName'));
        $orderNo = WSTAddslashes(I('orderNo'));
        $isRefund = (int)I('isRefund', -1);
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $areaId3 = (int)I('areaId3', 0);
        $sql = "select o.orderId,o.orderNo,o.isSelf,o.totalMoney,o.realTotalMoney,o.setDeliveryMoney,o.isPay,o.orderStatus,o.isRefund,o.deliverMoney,o.payType,o.createTime,s.shopName,o.userName from __PREFIX__orders o
	 	         left join __PREFIX__shops s on o.shopId=s.shopId  where o.orderFlag=1 and o.orderStatus in (-1,-4,-6,-7) and payType=1 and isPay=1 ";
        if ($areaId1 > 0) $sql .= " and s.areaId1=" . $areaId1;
        if ($areaId2 > 0) $sql .= " and s.areaId2=" . $areaId2;
        if ($areaId3 > 0) $sql .= " and s.areaId3=" . $areaId3;
        if ($isRefund > -1) $sql .= " and o.isRefund=" . $isRefund;
        if ($shopName != '') $sql .= " and (s.shopName like '%" . $shopName . "%' or s.shopSn like '%" . $shopName . "%')";
        if ($orderNo != '') $sql .= " and o.orderNo like '%" . $orderNo . "%' ";
        $sql .= " order by orderId desc";
        $page = $this->pageQuery($sql, $page);
        //获取涉及的订单及商品
        if (count($page['root']) > 0) {
            $orderIds = array();
            foreach ($page['root'] as $key => $v) {
                $orderIds[] = $v['orderId'];
            }
            $sql = "select og.orderId,og.goodsThums,og.goodsName,og.goodsId from __PREFIX__order_goods og
			        where og.orderId in(" . implode(',', $orderIds) . ")";
            $rs = $this->query($sql);
            $goodslist = array();
            foreach ($rs as $key => $v) {
                $goodslist[$v['orderId']][] = $v;
            }
            $config = $GLOBALS['CONFIG'];
            foreach ($page['root'] as $key => $v) {
                $page['root'][$key]['goodslist'] = $goodslist[$v['orderId']];
                if ($config['setDeliveryMoney'] == 2) {//废弃
                    if ($page['root'][$key]['isPay'] == 1) {
                        //$page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney']+$page['root'][$key]['deliverMoney'];
                        $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'];
                    } else {
                        if ($page['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                            $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'] + $page['root'][$key]['setDeliveryMoney'];
                            $page['root'][$key]['deliverMoney'] = $page['root'][$key]['setDeliveryMoney'];
                        }
                    }
                } else {
                    if ($page['root'][$key]['isPay'] != 1 && $page['root'][$key]['setDeliveryMoney'] > 0) {
                        $page['root'][$key]['realTotalMoney'] = $page['root'][$key]['realTotalMoney'] + $page['root'][$key]['setDeliveryMoney'];
                        $page['root'][$key]['deliverMoney'] = $page['root'][$key]['setDeliveryMoney'];
                    }
                }
            }
        }
        return $page;
    }

    /**
     * 退款
     */
    public function refund()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());

        $rs = $this->where('isRefund=0 and orderFlag=1 and orderStatus in (-1,-4,-6,-7) and payType=1 and isPay=1 and orderId=' . (int)I('id'))->find();
        if ($rs['orderId'] != '') {
            $data = array();
            $data['isRefund'] = 1;
            $data['refundRemark'] = I('content');
            $rss = $this->where("orderId=" . (int)I('id', 0))->save($data);
            if (false !== $rss) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            } else {
                $rd['code'] = -2;
            }
        }
        return $rd;
    }

    /**
     * 企业付款到零钱
     * @params $amount 订单金额 必填(是)
     * @params $openid 用户openid 必填(是)
     * @params $orderNo 订单号 必填(是)
     * @params $desc 退款描述 必填(否)
     * @params $check_name 用户姓名 必填(否)
     */
    public function wxPayTransfers($data)
    {
        vendor('WxPay.lib.WxPayTransfers');
        $transfers = new \WxPayTransfers();
        /*$amount = 0.01;
        $openid = 'oyq0348T4x_MPvGTD8k1tshVECUg';
        $orderNo = '1000001520';*/
        $amount = $data['amount'];
        $openid = $data['openid'];
        $orderNo = $data['orderNo'];
        $transfersRes = $transfers->sendMoney($amount, $openid, $orderNo);
        return $transfersRes;
    }

    /**
     * 取消拼团订单
     * @param int $user_id 用户uid
     * @param int $order_id 订单id
     * @return array
     */
    public function assembleOrderCancel(int $user_id, int $order_id)
    {
        $order_module = new OrdersModule();
        $order_detail = $order_module->getOrderInfoById($order_id, "*", 2);
        if (!in_array($order_detail["orderStatus"], array(15, 0))) {
            if (in_array($order_detail['orderStatus'], array(-1, -6))) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败，已取消订单不能重复取消', '订单状态有误');
            }
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败，已受理订单不能取消', '订单状态有误');
        }
        $order_status = -6;//取对商家影响最小的状态
        if ($order_detail["orderStatus"] == 0 || $order_detail["orderStatus"] == -2) {
            $order_status = -1;
        }
        $trans = new Model();
        $trans->startTrans();
        $order_data = array(
            'orderId' => $order_detail['orderId'],
            'orderStatus' => $order_status,
        );
        $save_order_res = $order_module->saveOrdersDetail($order_data, $trans);
        if (empty($save_order_res)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '订单状态更改失败');
        }
        $order_goods = $order_module->getOrderGoodsList($order_id, 'og.*', 2);
        $goods_module = new GoodsModule();
        foreach ($order_goods as $item) {
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $goods_cnt = $item['goodsNums'];
            $stock = gChangeKg($goods_id, $goods_cnt, 1, $sku_id);
            $return_stock_res = $goods_module->returnGoodsStock($goods_id, $sku_id, $stock, 1, 2, $trans);
            if ($return_stock_res['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '库存返还失败');
            }
        }
//        if ($order_detail['orderStatus'] == 0) {
        if ($order_detail['isPay'] == 1) {
            if ($order_detail['payFrom'] == 1) {
                //支付宝
                $payModule = new PayModule();
                $aliPayRefundRes = $payModule->aliPayRefund($order_detail['tradeNo'], $order_detail['realTotalMoney']);
                if ($aliPayRefundRes['code'] != 0) {
                    $trans->rollback();
                    return returnData(null, -1, 'error', '取消失败，支付宝退款失败');
                }
                //复制以前的代码,按照以前的逻辑写
                //写入订单日志
                $logParams = [
                    'orderId' => $order_id,
                    'logContent' => "用户取消订单，发起支付宝退款：{$order_detail['realTotalMoney']}元",
                    'logUserId' => $user_id,
                    'logUserName' => '用户',
                    'orderStatus' => -6,
                    'payStatus' => 2,
                    'logType' => 0,
                    'logTime' => date('Y-m-d H:i:s'),
                ];
                M('log_orders')->add($logParams);
            }
            if ($order_detail['payFrom'] == 2) {//微信
                //微信
                $login_userinfo = array(
                    'user_id' => $user_id,
                    'user_username' => '用户',
                );
                $cancel_res = order_WxPayRefund($order_detail['tradeNo'], $order_id, $order_detail['orderStatus'], 0, $login_userinfo);
                if ($cancel_res == -3) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '微信退款失败');
                }
            }
        }

        $content = "用户已取消订单:拼团失败";
        $log_params = [
            'orderId' => $order_id,
            'logContent' => $content,
            'logUserId' => $user_id,
            'logUserName' => '用户',
            'orderStatus' => -6,
            'payStatus' => 2,
            'logType' => 0,
        ];
        $log_res = (new LogOrderModule())->addLogOrders($log_params, $trans);
        if (!$log_res) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败', '订单日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 获取所有未退款的退款订单数量
     */
    public function queryRefundNum()
    {
        $shopName = WSTAddslashes(I('shopName'));
        $orderNo = WSTAddslashes(I('orderNo'));
        $isRefund = (int)I('isRefund', -1);
        $areaId1 = (int)I('areaId1', 0);
        $areaId2 = (int)I('areaId2', 0);
        $areaId3 = (int)I('areaId3', 0);
        $sql = "select count(o.orderId) from __PREFIX__orders o
	 	         left join __PREFIX__shops s on o.shopId=s.shopId  where o.orderFlag=1 and o.orderStatus in (-1,-4,-6,-7) and payType=1 and isPay=1 ";
        if ($areaId1 > 0) $sql .= " and s.areaId1=" . $areaId1;
        if ($areaId2 > 0) $sql .= " and s.areaId2=" . $areaId2;
        if ($areaId3 > 0) $sql .= " and s.areaId3=" . $areaId3;
        if ($shopName != '') $sql .= " and (s.shopName like '%" . $shopName . "%' or s.shopSn like '%" . $shopName . "%')";
        if ($orderNo != '') $sql .= " and o.orderNo like '%" . $orderNo . "%' ";
        $sql .= " and o.isRefund=0";
        $sql .= " order by orderId desc";
        $res = $this->query($sql);
        $num = 0;
        if ($res[0]['count(o.orderId)'] > 0) {
            $num = $res[0]['count(o.orderId)'];
        }
        return ['state' => 0, 'num' => $num];
    }

//    /**
//     * 合并单列表
//     */
//    public function orderMergeList($orderToken,$page=1,$pageSize=15){
//        $sql = "select * from __PREFIX__order_merge where 1=1 and value != '' ";
//        if (!empty($orderToken)) $sql .= " and orderToken = '$orderToken' ";
//        $sql.=" order by createTime desc";
//        $result = $this->pageQuery($sql,$page,$pageSize);
//        if (!empty($result['root'])) {
//            foreach ($result['root'] as $k=>$v){
//                $result['root'][$k]['create_time'] = date('Y-m-d H:i:s',$v['createTime']);
//            }
//        }
//        return $result;
//    }
//
//    /**
//     * 合并子订单列表
//     * @param $id
//     */
//    public function mergeChildOrderList($id){
//        $order_merge_list = M('order_merge')->where(array('id'=>$id))->find();
//        $value = explode('A',$order_merge_list['value']);
//        return M('orders o')->join('wst_shops as s on o.shopId = s.shopId')->field('o.orderId,o.orderNo,o.totalMoney,o.realTotalMoney,o.orderStatus,o.setDeliveryMoney,o.deliverMoney,o.payType,o.createTime,s.shopName,o.userName,o.isPay')->where(array('o.orderNo'=>array('in',$value),'o.orderFlag'=>1))->order('o.createTime desc')->select();
//    }

    /**
     * 查询微信交易号包含的订单
     * @param string tradeNo 微信交易号
     */
    public function mergeChildOrderList($tradeNo)
    {
        $config = $GLOBALS['CONFIG'];
        $data = M('orders o')->join('wst_shops as s on o.shopId = s.shopId')->field('o.orderId,o.orderNo,o.totalMoney,o.realTotalMoney,o.orderStatus,o.setDeliveryMoney,o.deliverMoney,o.payType,o.createTime,s.shopName,o.userName,o.isPay')->where(['tradeNo' => $tradeNo])->order('o.createTime desc')->select();
        foreach ($data as $key => $value) {
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data[$key]['isPay'] == 1) {
                    //$data[$key]['realTotalMoney'] = $data[$key]['realTotalMoney']+$data[$key]['deliverMoney'];
                    $data[$key]['realTotalMoney'] = $data[$key]['realTotalMoney'];
                } else {
                    if ($data[$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data[$key]['realTotalMoney'] = $data[$key]['realTotalMoney'] + $data[$key]['setDeliveryMoney'];
                        $data[$key]['deliverMoney'] = $data[$key]['setDeliveryMoney'];
                    }
                }
            } else {//处理常规非常规订单拆分后运费问题
                if ($data[$key]['isPay'] != 1 && $data[$key]['setDeliveryMoney'] > 0) {
                    $data[$key]['realTotalMoney'] = $data[$key]['realTotalMoney'] + $data[$key]['setDeliveryMoney'];
                    $data[$key]['deliverMoney'] = $data[$key]['setDeliveryMoney'];
                }
            }
        }
        return (array)$data;
    }

    /**
     * @param array $params
     * @return array
     * 统计订单数量
     */
    public function getOrderStatusNum(array $params)
    {
        $where = " o.orderFlag=1 ";
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
        $whereFind['o.payFrom'] = function () use ($params) {
            if (empty($params['payFrom'])) {
                return null;
            }
            return ['=', "{$params['payFrom']}", 'and'];
        };
        $whereFind['o.deliverType'] = function () use ($params) {
            if (empty($params['deliverType'])) {
                return null;
            }
            return ['=', "{$params['deliverType']}", 'and'];
        };
        $whereFind['o.orderType'] = function () use ($params) {
            if (empty($params['orderType'])) {
                return null;
            }
            return ['=', "{$params['orderType']}", 'and'];
        };
        $whereFind['o.isSelf'] = function () use ($params) {
            if (!is_numeric($params['isSelf']) || !in_array($params['isSelf'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['isSelf']}", 'and'];
        };
        $whereFind['self.onStart'] = function () use ($params) {
            if (!is_numeric($params['onStart']) || !in_array($params['onStart'], [0, 1])) {
                return null;
            }
            return ['=', "{$params['onStart']}", 'and'];
        };
        $whereFind['self.source'] = function () use ($params) {
            if (empty($params['source'])) {
                return null;
            }
            return ['=', "{$params['source']}", 'and'];
        };
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
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $whereInfo .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }

        //以下sql，主要用来统计相关条件下的总金额
        $orderTab = M('orders o');
        $all = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo)->count();//全部
        $andWhere = ' and orderStatus = -2 ';
        $toBePaid = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();;//待付款
        $andWhere = ' and orderStatus=0 ';
        $toBeAcceptedList = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->select();//待接单/待受理
        $array = [];
        date_default_timezone_set("Asia/Shanghai");
        foreach ($toBeAcceptedList as $v) {
            //防止格式异常导致计算错误
            $dateTime = date('Y-m-d H:i:s', strtotime($v['requireTime']));
            $unconventionality = 1 * 60 * 60;//非常规订单配送时长
            $requireTime = date("Y-m-d H:i:s", strtotime($dateTime) - $unconventionality);
            if (strtotime($requireTime) <= time()) {
                $array[] = $v;
            }
        }
        $toBeAccepted = count($array);
        $andWhere = ' and orderStatus IN(1,2) ';
        $toBeDelivered = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//待发货
        $andWhere = ' and orderStatus IN(3) and isSelf = 0 ';
        $toBeReceived = $orderTab
            ->join('left join wst_users u on u.userId=o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//待收货
        $andWhere = ' and orderStatus IN(3) and isSelf = 1 ';
        $toBePickedUp = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//待取货 PS:自提订单,商家发货后
        $andWhere = ' and orderStatus = 4 ';
        $confirmReceipt = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//已完成
        $andWhere = ' and orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ';
        $invalid = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//已失效(用户取消或门店拒收)
        $andWhere = ' and orderStatus IN(7,8,10,16,17) ';
        $takeOutDelivery = $orderTab
            ->join('left join wst_users u on u.userId = o.userId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->join('left join wst_user_self_goods self on self.orderId = o.orderId')
            ->where($whereInfo . $andWhere)->count();//外卖配送
        $data = [];
        $data['all'] = (int)$all;
        $data['toBePaid'] = (int)$toBePaid;
        $data['toBeAccepted'] = (int)$toBeAccepted;
        $data['toBeDelivered'] = (int)$toBeDelivered;
        $data['toBeReceived'] = (int)$toBeReceived;
        $data['confirmReceipt'] = (int)$confirmReceipt;
        $data['toBePickedUp'] = (int)$toBePickedUp;
        $data['invalid'] = (int)$invalid;
        $data['takeOutDelivery'] = (int)$takeOutDelivery;
        return $data;
    }

    /**
     * 获取商家订单列表
     * @param array $params <p>
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
    public function queryShopOrders(array $params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $export = (int)$params['export'];
        $where = "  o.orderFlag=1 ";
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
            $params['endDate'] = date("Y-m-d", strtotime($params['endDate'])) . " 23:59:59";
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
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $whereInfo .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }
        $statusMark = [];
        $statusMark['statusMark'] = null;
        parm_filter($statusMark, $params);
        $field = 'o.orderId,o.orderNo,o.shopId,o.orderStatus,o.totalMoney,o.deliverMoney,o.payType,o.payFrom,o.isSelf,o.isPay,o.deliverType,o.userId,o.userName,o.userAddress,o.userPhone,o.orderRemarks,o.needPay,o.realTotalMoney,o.useScore,o.orderType,o.createTime,o.userTel,o.areaId1,o.areaId2,o.areaId3,o.pay_time,o.requireTime,o.setDeliveryMoney';
        $field .= ',u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,self.source,self.onStart';
        $field .= ',s.shopName';
        $sql = "select {$field} from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId = o.userId " . " left join __PREFIX__user_self_goods self on self.orderId = o.orderId " . " left join __PREFIX__shops s on s.shopId = o.shopId " . " where {$whereInfo} ";
        //以下sql，主要用来统计相关条件下的总金额
        $sql1 = "select sum(o.realTotalMoney) as total_order_money from " . __PREFIX__orders . " o left join " . __PREFIX__users . " u on u.userId = o.userId " . " left join __PREFIX__user_self_goods self on self.orderId = o.orderId " . " left join __PREFIX__shops s on s.shopId = o.shopId " . " where {$whereInfo} ";
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
                    $sql .= " AND o.orderStatus IN(7,8,9,10,11,16,17) ";
                    $sql1 .= " AND o.orderStatus IN(7,8,9,10,11,16,17) ";
                    break;
                case 'invalid'://无效订单(用户取消或商家拒收)
                    $sql .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    $sql1 .= " AND o.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    break;
            }
        }
        //排序 主要针对待接单
        if ($params['statusMark'] == "toBeAccepted") {
            //默认以下单时间-升序
            if (empty($params['createTimeSort']) && empty($params['requireTimeSort'])) {
                $params['createTimeSort'] = "asc";
            }

            if (!empty($params['createTimeSort'])) {
                $sql .= " order by o.createTime {$params['createTimeSort']} ,";
            } else {
                $sql .= " order by ";
            }

            //送达时间
            if (!empty($params['requireTimeSort'])) {
                $sql .= "  o.requireTime {$params['requireTimeSort']} ,";
            }
            $sql .= "  o.orderId desc ";
        } else {
            $sql .= " order by o.orderId desc ";
        }

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
        $array = [];
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
            if (!empty($orderGoods)) {
                foreach ($orderGoods as $k => $val) {
                    $orderGoods[$k]['shopName'] = $v['shopName'];
                }
            }
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
            $sql = "select logContent from __PREFIX__log_orders where orderId =" . $v['orderId'] . " and logType = 0 and logUserId =" . $v['userId'] . " order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data['root'][$key]['isPay'] == 1) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                } else {
                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            } else {//处理常规非常规订单拆分后运费问题
                if ($data['root'][$key]['isPay'] != 1 && $data['root'][$key]['setDeliveryMoney'] > 0) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                    $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
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
            //判断未达到受理时间的订单
            if ($params['statusMark'] == "toBeAccepted") {
                //防止格式异常导致计算错误
                $dateTime = date('Y-m-d H:i:s', strtotime($v['requireTime']));
                $unconventionality = 1 * 60 * 60;//订单配送时长
                $requireTime = date("Y-m-d H:i:s", strtotime($dateTime) - $unconventionality);
                if (strtotime($requireTime) <= time()) {
                    $array[] = $data['root'][$key];
                }
            }
        }
        //判断未达到受理时间的订单
        if ($params['statusMark'] == 'toBeAccepted') {
            if (empty($array)) {
                $data['root'] = [];
            } else {
                $data['root'] = $array;
            }
        }
        if ($params['statusMark'] == 'toBeDelivered' && !empty($data['root'])) {
            $list = $data['root'];
            $sort_arr = array();
            foreach ($list as $key => $value) {
                $sort_arr[] = $value['can_delivery_state'];
            }
            array_multisort($sort_arr, SORT_DESC, $list);
            if (empty($list)) {
                $data['root'] = [];
            } else {
                $data['root'] = $list;
            }
        }
        if ($export == 1) {
            $this->exportOrderListNew($data['root'], I(''));
        }
        if (empty($data['root'])) {
            $data['root'] = [];
        }

        return $data;
    }

    /**
     * 导出订单
     * @param array $orderList 需要导出的订单数据
     * @param array $params 前端传过来的参数
     * */
    public function exportOrderListNew(array $orderList, array $params)
    {
        //处理订单商品信息
        foreach ($orderList as $key => $value) {
            $rowspan = 0;//下面的导出表格会用到
            $orderGoods = $value['goodslist'];
            $goodsList = [];
            foreach ($value['goodslist'] as $gval) {
                $goodsInfo = [];
                $goodsInfo['goodsId'] = $gval['goodsId'];
                $goodsInfo['goodsName'] = $gval['goodsName'];
                $goodsInfo['goodsPrice'] = $gval['goodsPrice'];
                $goodsInfo['shopName'] = $gval['shopName'];
                $goodsInfo['goodsNums'] = 0;
                $goodsInfo['remarks'] = $gval['remarks'];
                $goodsList[] = $goodsInfo;
            }
            $unquieGoods = arrayUnset($goodsList, 'goodsId');
            foreach ($unquieGoods as $uKey => $uVal) {
                $skulist = [];
                foreach ($orderGoods as $oVal) {
                    if ($oVal['goodsId'] == $uVal['goodsId']) {
                        $rowspan += 1;
                        $unquieGoods[$uKey]['goodsNums'] += $oVal['goodsNums'];
                        if (!empty($oVal['skuId'])) {
                            $skuInfo = [];
                            $skuInfo['skuId'] = $oVal['skuId'];
                            $skuInfo['goodsNums'] = $oVal['goodsNums'];
                            $skuInfo['goodsPrice'] = $oVal['goodsPrice'];
                            $skuInfo['goodsAttrName'] = $oVal['goodsAttrName'];
                            $skuInfo['remarks'] = $oVal['remarks'];
                            $skulist[] = $skuInfo;
                        }
                    }
                }
                $unquieGoods[$uKey]['skulist'] = $skulist;
            }
            $orderList[$key]['goodslist'] = $unquieGoods;
            $orderList[$key]['rowspan'] = count($unquieGoods);
        }

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
                <th style='width:100px;'>订单号</th>
                <th style='width:200px;'>收货人信息</th>
                <th style='width:150px;'>商品</th>
                <th style='width:150px;'>门店名称</th>
                <th style='width:50px;'>价格</th>
                <th style='width:100px;'>规格</th>
                <th style='width:50px;'>数量</th>
                <th style='width:50px;'>小计</th>
                <th style='width:50px;'>运费</th>
                <th style='width:80px;'>实付金额</th>
                <th style='width:100px;'>备注</th>
                <th style='width:150px;'>下单时间</th>
                <th style='width:150px;'>支付方式</th>
            </tr>";
        $num = 0;
        $orderModule = new OrdersModule();
        foreach ($orderList as $okey => $ovalue) {
            $pay_name = $orderModule->getPayName($ovalue['payFrom']);
            $orderGoods = $ovalue['goodslist'];
            $rowspan = $ovalue['rowspan'];
            $key = $okey + 1;
            $userDetailAddress = '';
            $userDetailAddress .= '用户名：' . $ovalue['userName'] . '<br>';
            $userDetailAddress .= '电话：' . $ovalue['userPhone'] . '<br>';
            $userDetailAddress .= '收货地址：' . $ovalue['userAddress'] . '<br>';
            //打个补丁 start
            $rowspan = count($orderGoods);
            foreach ($orderGoods as $gVal) {
                if (!empty($gVal['skulist'])) {
                    $rowspan += count($gVal['skulist']) - 1;
                }
            }
            unset($gVal);
            //打个补丁 end
            foreach ($orderGoods as $gkey => $gVal) {
                /*if(!empty($gVal['skulist'])){
                     $rowspan = count($gVal['skulist']);
                 }*/
                $num++;
                $goodsNums = "<td style='width:30px;'>" . $gVal['goodsNums'] . "</td>";//数量;
                $specName = '无';
                $goodsRowspan = 1;
                if (!empty($gVal['skulist'])) {
                    $goodsRowspan = count($gVal['skulist']);
                }

                if ($gkey == 0) {
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['shopName'] . "</td>" .//门店名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $pay_name . "</td>" .//支付方式
                            "</tr>";
                    } else {
                        $specName = $gVal['skulist'][0]['goodsAttrName'];
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//订单号
                            "<td style='width:200px;' rowspan='{$rowspan}'>" . $userDetailAddress . "</td>" .//地址
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['shopName'] . "</td>" .//门店名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "<td style='width:50px;' rowspan='{$rowspan}'>" . $ovalue['deliverMoney'] . "</td>" .//运费
                            "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['realTotalMoney'] . "</td>" .//实付金额
                            "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['orderRemarks'] . "</td>" .//备注
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['createTime'] . "</td>" .//下单时间
                            "<td style='width:150px;' rowspan='{$rowspan}'>" . $pay_name . "</td>" .//支付方式
                            "</tr>";
                    }
                    if (!empty($gVal['skulist'])) {
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            if ($skuKey != 0) {
                                $body .=
                                    "<tr>" .
                                    "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                                    "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                                    "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                                    "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                                    "</tr>";
                            }
                        }
                    }
                } else {
                    /*$headTitle = "订单数据";
                    $filename = $headTitle . ".xls";
                    usePublicExport($body,$headTitle,$filename,$date);*/
                    if (empty($gVal['skulist'])) {
                        $body .=
                            "<tr align='center'>" .
                            "<td style='width:80px;' >" . $gVal['goodsName'] . "</td>" .//商品名称
                            "<td style='width:80px;' >" . $gVal['shopName'] . "</td>" .//门店名称
                            "<td style='width:50px;' >" . $gVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $specName . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "</tr>";
                    } else {
                        $goodsRowspan = count($gVal['skulist']);
                        foreach ($gVal['skulist'] as $skuKey => $skuVal) {
                            $goodsName = "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['goodsName'] . "</td>";//商品名称;
                            if ($skuKey != 0) {
                                $goodsName = '';
                            }
                            $body .=
                                "<tr>" .
                                $goodsName .
                                "<td style='width:80px;' rowspan='{$goodsRowspan}'>" . $gVal['shopName'] . "</td>";//门店名称;
                            "<td style='width:50px;' >" . $skuVal['goodsPrice'] . "</td>" .//商品价格
                            "<td style='width:100px;' >" . $skuVal['goodsAttrName'] . "</td>" .//商品规格
                            "<td style='width:50px;' >" . $gVal['goodsNums'] . "</td>" .//商品数量
                            "<td style='width:50px;'>" . $gVal['goodsPrice'] * $gVal['goodsNums'] . "</td>" .//小计
                            "</tr>";
                        }
                    }
                }
            }

        }
        $headTitle = "订单数据";
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * @param $orderId
     * @return mixed
     * 获取订单详情
     */
    public function getOrderDetailsApi($orderId)
    {
        $config = $GLOBALS['CONFIG'];
        $data = array();
        $where = [];
        $where['orders.orderId'] = $orderId;
        $where['orders.orderFlag'] = 1;
        $field = 'orders.*,self.source,self.onStart,s.shopName';
        $order = M('orders orders')
            ->join('left join wst_user_self_goods self on self.orderId = orders.orderId')
            ->join('left join wst_shops s on s.shopId = orders.shopId')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($order)) {
            return $data;
        }
        $autoReceiveDays = (int)$GLOBALS['CONFIG']['autoReceiveDays'];
        $autoReceiveDays = ($autoReceiveDays > 0) ? $autoReceiveDays : 10;//避免有些客户没有设置值
        $lastDay = date("Y-m-d 00:00:00", strtotime("-" . $autoReceiveDays . " days"));
        $orderCreateTime = explode(' ', $order['createTime'])[0];
        $autoReceiveDay = strtotime($lastDay) - strtotime($orderCreateTime);
        if ($autoReceiveDay <= 0) {//剩余自动收货时间
            $order['autoReceiveDay'] = 0;
        } else {
            $order['autoReceiveDay'] = $autoReceiveDay / 86400;
        }
        if ($order['isSelf'] == 1) {
            $order['deliverType'] = 22;//自提
        }
        $order['deliverType'] = (string)$order['deliverType'];//避免前端因为类型报错

        if (empty($order)) return $data;
        $order['source'] = (string)$order['source'];
        $order['onStart'] = (int)$order['onStart'];
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] == 1) {
                $order['realTotalMoney'] = $order['realTotalMoney'];
            } else {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        if ($order['isSelf'] == 1) {
            $order['deliverMoney'] = 0;
        }
        $fieldGoods = " og.orderId,og.weight,og.goodsId ,g.goodsSn,g.SuppPriceDiff,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,og.skuSpecAttr,og.remarks,og.skuId,og.skuSpecAttr ";
        $fieldGoods .= " ,g.weightG";
        $sql = "select {$fieldGoods} from __PREFIX__goods g , __PREFIX__order_goods og WHERE g.goodsId = og.goodsId AND og.orderId = $orderId";
        $goods = $this->query($sql);
        $usersServiceModule = new UsersServiceModule();
        $shopsServiceModule = new ShopsServiceModule();
        $fieldInfo = " per.userName,per.mobile,gr.startDate,gr.endDate ";
        foreach ($goods as $key => $val) {
            //替换编码和商品图片
            if (!empty($val['skuId'])) {
                $skuInfo = M('sku_goods_system')->where(['skuId' => $val['skuId'], 'dataFlag' => 1, 'goodsId' => $val['goodsId']])->find();
                $goods[$key]['goodsSn'] = $skuInfo['skuBarcode'];
                $goods[$key]['goodsThums'] = $skuInfo['skuGoodsImg'];
            }
            $goods[$key]['personName'] = '';
            $goods[$key]['personMobile'] = '';
            $sql = "select {$fieldInfo} from __PREFIX__sorting_goods_relation gr left join __PREFIX__sorting s on s.id = gr.sortingId left join __PREFIX__sortingpersonnel per on per.id = s.personId where s.sortingFlag = 1 and gr.goodsId = {$val['goodsId']} and s.orderId = {$val['orderId']}";
            $info = $this->queryRow($sql);
            $goods[$key]['sortStartDate'] = 0;
            $goods[$key]['sortEndDate'] = 0;
            $goods[$key]['sortOverTime'] = 0;
            if ($info) {
                $goods[$key]['personName'] = $info['userName'];
                $goods[$key]['personMobile'] = $info['mobile'];
                $goods[$key]['sortStartDate'] = $info['startDate']; //分拣开始时间
                $goods[$key]['sortEndDate'] = $info['endDate'];//分拣结束时间
                if (is_null($goods[$key]['sortStartDate'])) {
                    $goods[$key]['sortStartDate'] = 0;
                }
                if (is_null($goods[$key]['sortEndDate'])) {
                    $goods[$key]['sortEndDate'] = 0;
                }
                $endDateInt = strtotime($info['endDate']);
                $startDateInt = strtotime($info['startDate']);
                $diffTime = timeDiff($endDateInt, $startDateInt);//耗时
                $goods[$key]['sortOverTime'] = $diffTime['day'] * 24 * 60 + $diffTime['hour'] * 60 + $diffTime['min'];
            }
        }
        $order["goodsList"] = $goods;
        //发票信息
        $order['invoiceInfo'] = [];
        if (isset($order['isInvoice']) && $order['isInvoice'] == 1) {
            $order['invoiceInfo'] = M('invoice')->where(['id' => $order['invoiceClient']])->find();
        }
        //发票详情(新)
        $order['receiptInfo'] = (array)M('invoice_receipt')->where(['receiptId' => $order['receiptId']])->find();
        $userData = $usersServiceModule->getUsersDetailById($order['userId']);
        $userInfo = $userData['data'];
        if (!empty($order['receiptInfo'])) {
            $order['receiptInfo']['userName'] = $userInfo['userName'];
            $order['receiptInfo']['userPhone'] = $userInfo['userPhone'];
        }
        //后加包邮起步价
        $shopInfo = $shopsServiceModule->getShopsInfoById($order['shopId'], 'deliveryFreeMoney');
        $order['deliveryFreeMoney'] = $shopInfo['data']['deliveryFreeMoney'];
        $order['buyer'] = (array)$userInfo;

        //优惠券金额
        $order['couponMoney'] = 0;
        $couponInfo = M('coupons')->where(['couponId' => $order['couponId']])->find();
        if ($couponInfo) {
            $order['couponMoney'] = $couponInfo['couponMoney'];
        }
        $order['orderMoney'] = formatAmount($order['totalMoney'] + $order['deliverMoney']);
        //订单状态流转时间
        $appraisesInfo = M('goods_appraises')->where(array(
            'orderId' => $orderId
        ))
            ->order('id asc')
            ->find();
        $order['status_time'] = array(
            'create_time' => (string)$order['createTime'],//提交订单
            'pay_time' => (string)$order['pay_time'],//支付订单
            'delivery_time' => (string)$order['deliveryTime'],//商家发货
            'receive_time' => (string)$order['receiveTime'],//确认收货
            'appraises_time' => (string)$appraisesInfo['createTime'],//完成评价
        );
        return $order;
    }

    /**
     * @param $orderId
     * @return array
     * 获取订单日志
     */
    public function getOrderLog($orderId)
    {
        $logOrderModule = new LogOrderModule();
        $ordersModule = new OrdersModule();
        $logOrderList = $logOrderModule->getLogOrderList((int)$orderId);
        $logs = $logOrderList['data'];
        foreach ($logs as &$item) {
            $item['orderStatusName'] = $ordersModule->getOrderStatusName($item['orderStatus']);
            $item['payStatusName'] = $ordersModule->getPayStatusName($item['payStatus']);
        }
        unset($item);
        return (array)$logs;
    }

    /**
     * @return array
     * 退货申请统计
     */
    public function getOrderComplainsListNum()
    {
        //店铺名称|编号
        $shopWords = I('shopWords', 0);

        $tab = M('order_complains oc');

        $where = " o.orderFlag = 1 and u.userFlag = 1 ";
        //店铺名称|编号
        if (!empty($shopWords)) {
            $where .= " and (s.shopName like '%{$shopWords}%' or s.shopSn like '%{$shopWords}%') ";
        }
        $alllist = $tab
            ->join('left join wst_users u on oc.complainTargetId = u.userId')
            ->join('left join wst_orders o on o.orderId = oc.orderId')
            ->join('left join wst_shops s on s.shopId = oc.respondTargetId')
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
        $data['allNum'] = (int)$allNum;
        $data['rejectedNum'] = (int)$rejectedNum;
        $data['pendingNum'] = (int)$pendingNum;
        $data['returningNum'] = (int)$returningNum;
        $data['completedNum'] = (int)$completedNum;
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
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " o.orderFlag = 1 and u.userFlag = 1 ";
        if (!empty($params['receivingPeople'])) {
            $where .= " and (u.userName like '%{$params['receivingPeople']}%' or u.loginName like '%{$params['receivingPeople']}%'or u.userPhone like '%{$params['receivingPeople']}%') ";
        }

        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
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
        $field = 'oc.complainId,oc.complainContent,oc.complainStatus,oc.createTime,oc.respondTime,oc.returnAmount ';
        $field .= ',o.orderId,o.orderNo,o.realTotalMoney,o.shopId ';
        $field .= ',u.loginName,u.userName,u.userPhone ';
        $field .= ',s.shopName ';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__users u on oc.complainTargetId = u.userId
                left join __PREFIX__orders o on o.orderId = oc.orderId 
                left join __PREFIX__shops s on s.shopId = oc.respondTargetId 
                where {$whereInfo} ";
        $sql .= " group by oc.complainId ";
        $sql .= " order by oc.complainId desc";
        if ($params['export'] == 1) {
            //导出
            $data['root'] = $this->query($sql);
        } else {
            $data = $this->pageQuery($sql, $page, $pageSize);
        }
        if (!empty($data['root'])) {
            $list = $data['root'];
            foreach ($list as &$item) {
                $item['proposalReturnAmount'] = 0;//建议退款金额
                $checkRespondTime = strtotime($item['respondTime']);
                if (!$checkRespondTime) {
                    $item['respondTime'] = '';
                }
                $detail = $this->getOrderComplainDetail($item['shopId'], $item['complainId']);
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
                <th style='width:300px;'>服务单号</th>
                <th style='width:100px;'>门店名称</th>
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
                        "<td style='width:300px;' rowspan='{$rowspan}'>" . $ovalue['orderNo'] . "</td>" .//服务单号
                        "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['shopName'] . "</td>" .//门店名称
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
     * 获取投诉(退货)详情
     * @param int $shopId
     * @param int $complainId 投诉/退货id
     */
    public function getOrderComplainDetail(int $shopId, int $complainId)
    {
        //获取订单信息
        $field = 'oc.*';
        $field .= ',o.realTotalMoney,o.orderNo,o.orderId,o.createTime,o.userId,o.deliverMoney,o.requireTime,o.useScore,o.scoreMoney,o.couponId';
        $field .= ',p.shopName,p.shopId';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__orders o on o.orderId = oc.orderId 
                left join __PREFIX__shops p on o.shopId = p.shopId
			    where oc.complainId = " . $complainId;
        $sql .= " and oc.needRespond=1 ";
        if (!empty($shopId)) {
            $sql .= "  and oc.respondTargetId = $shopId";
        }
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
            $couponInfo = M('coupons')->where(['couponId' => $rs['couponId']])->find();
            $rs['couponMoney'] = (float)$couponInfo['couponMoney'];
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
        $fieldGoods = " og.orderId, og.goodsId ,og.skuId,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,g.SuppPriceDiff,og.skuId,og.skuSpecAttr ";
        $ordersGoods = new OrdersModule();
        $goodsList = $ordersGoods->getOrderGoodsList($rs['orderId'], $fieldGoods);
        $goods = $goodsList['data'];
        $rs['goodsPriceTotal'] = 0;//合计
        $rs['realyGoodsPrice'] = 0;//建议退款金额合计
        //应诉商品信息
        foreach ($goods as $key => $val) {
            if (!empty($rs['goodsId']) && $val['goodsId'] != $rs['goodsId']) {
                unset($goods[$key]);
                continue;
            }
            $goods[$key]['goodsPriceTotal'] = $val['shopPrice'] * $val['goodsNums'];//单商品金额小计
            $goods[$key]['realyGoodsPrice'] = handleGoodsPayN($val['orderId'], $val['goodsId'], $val['skuId'], $rs['userId']) * $goods[$key]['goodsNums'];//建议退款金额
            $goods[$key]['diffMoney'] = 0;
            //当前商品是否补差价过
            $resPay = M('goods_pricediffe')->where("orderId='" . $val['orderId'] . "' AND goodsId='" . $val['goodsId'] . "' AND isPay=1")->sum('money');
            $goods[$key]['realyGoodsPrice'] = bc_math($goods[$key]['realyGoodsPrice'], $resPay, 'bcsub', 2);;
            $goods[$key]['diffMoney'] = (float)$resPay;
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
     *退货/退款申请操作日志
     * @param int $complainId
     * @return array $data
     * */
    public function getOrderComplainsLog($complainId)
    {
        $tableName = 'wst_order_complains';
        $tableActionLog = new TableActionLogModule();
        $data = $tableActionLog->getTableActionLogList($tableName, $complainId);
        return (array)$data['data'];
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
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " o.orderFlag = 1 and u.userFlag = 1 and oc.complainStatus = 2 ";
        if (!empty($params['receivingPeople'])) {
            $where .= " and (u.userName like '%{$params['receivingPeople']}%' or u.loginName like '%{$params['receivingPeople']}%'or u.userPhone like '%{$params['receivingPeople']}%') ";
        }
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
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
        $field .= ',s.shopName ';
        $sql = "select {$field} from __PREFIX__order_complains oc 
                left join __PREFIX__users u on oc.complainTargetId = u.userId
                left join __PREFIX__orders o on o.orderId = oc.orderId
                left join __PREFIX__shops s on s.shopId = o.shopId
                left join __PREFIX__order_complainsrecord record on record.complainId = oc.complainId
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
                <th style='width:150px;'>门店名称</th>
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
                "<td style='width:150px;' rowspan='{$rowspan}'>" . $ovalue['shopName'] . "</td>" .//门店名称
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
     * @param $shopWords  店铺名称|编号
     * @return array
     * 退款统计
     */
    public function getReturnAmountNum($shopWords)
    {
        $tab = M('order_complains oc');
        $where = "oc.complainStatus = 2 and o.orderFlag = 1 and u.userFlag = 1 ";
        if (!empty($shopWords)) {
            $where .= " and (s.shopName like '%{$shopWords}%' or s.shopSn like '%{$shopWords}%') ";
        }
        $alllist = $tab
            ->join('left join wst_users u on oc.complainTargetId = u.userId')
            ->join('left join wst_orders o on o.orderId = oc.orderId')
            ->join('left join wst_shops s on s.shopId = o.shopId')
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
        $data['allNum'] = (int)$allNum;
        $data['rejectedNum'] = (int)$rejectedNum;
        $data['pendingNum'] = (int)$pendingNum;
        $data['completedNum'] = (int)$completedNum;
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 补差价订单列表
     */
    public function getDiffMoneyOrders($params)
    {
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $orderNo = WSTAddslashes($params['orderNo']);
        $userName = WSTAddslashes($params['userName']);
        $userPhone = WSTAddslashes($params['userPhone']);
        $isPay = $params['isPay'];
        $where = " gd.money > 0 ";
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }
        $field = " o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,gd.isPay as isPayDiff";
        $sql = "select {$field} from __PREFIX__goods_pricediffe gd 
                left join __PREFIX__orders o on o.orderId = gd.orderId 
                left join __PREFIX__shops s on s.shopId = o.shopId 
                left join __PREFIX__users u on u.userId = o.userId   where {$where} ";

        if ($orderNo != "") {
            $sql .= " AND o.orderNo like '%$orderNo%'";
        }
        if ($userName != "") {
            $sql .= " AND u.userName like '%$userName%'";
        }
        if ($userPhone != "") {
            $sql .= " AND u.userPhone like '%$userPhone%'";
        }
        if ($isPay != 20) {
            if (is_numeric($isPay)) {
                $sql .= " AND gd.isPay ='" . $isPay . "' ";
            }
        }

        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        if (!empty($startDate)) {
            $sql .= " AND o.createTime>='" . $startDate . "'";
        }
        if (!empty($endDate)) {
            $sql .= " AND o.createTime<='" . $endDate . "'";
        }
        $sql .= " AND o.orderFlag=1";
        $sql .= " group by o.orderId order by o.orderId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        //获取取消/拒收原因
        $noReadrderIds = array();
        $config = $GLOBALS['CONFIG'];
        $goodsDiffTab = M('goods_pricediffe');
        foreach ($data['root'] as $key => $v) {
            $nowtime = time();
            $shopGoodPreSaleEndTimeInt = strtotime($v['ShopGoodPreSaleEndTime']);
            $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 0; //未过期
            if ($nowtime >= $shopGoodPreSaleEndTimeInt) {
                $data['root'][$key]['shopGoodPreSaleDeliveryOver'] = 1; //已过期
                $data['root'][$key]['shopGoodPreSaleDelivery'] = 0;
            } else {
                $data['root'][$key]['shopGoodPreSaleDelivery'] = timeDiff($shopGoodPreSaleEndTimeInt, $nowtime);
            }
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data['root'][$key]['isPay'] == 1) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'];
                } else {
                    if ($data['root'][$key]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                        $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                    }
                }
            } else {
                if ($data['root'][$key]['isPay'] != 1 && $data['root'][$key]['setDeliveryMoney'] > 0) {
                    $data['root'][$key]['realTotalMoney'] = $data['root'][$key]['realTotalMoney'] + $data['root'][$key]['setDeliveryMoney'];
                    $data['root'][$key]['deliverMoney'] = $data['root'][$key]['setDeliveryMoney'];
                }
            }
            $data['root'][$key]['weightG'] = 0;
            $orderGoods = M('order_goods')->where(['orderId' => $v['orderId']])->select();
            foreach ($orderGoods as $gv) {
                $goodsId[] = $gv['goodsId'];
            }
            $goodsWhere['goodsId'] = ['IN', $goodsId];
            $goods = M('goods')->where($goodsWhere)->field('SuppPriceDiff,goodsId')->select();
            $data['root'][$key]['returnAmount'] = 0;//补差价总金额
            $data['root'][$key]['returnAmountOK'] = 0;//已补差价金额
            $data['root'][$key]['returnAmountNO'] = 0;//未补差价金额
            foreach ($goods as $ggv) {
                if ($ggv['SuppPriceDiff'] == 1) {
                    $data['root'][$key]['weightG'] = 1;
                }
            }
            $where = [];
            $where['goodsId'] = ['IN', $goodsId];
            $where['orderId'] = $v['orderId'];
            $returnAmount = $goodsDiffTab->where($where)->sum('money');
            if ($returnAmount > 0) {
                //补差价总金额
                $data['root'][$key]['returnAmount'] = (float)$returnAmount;
            }
            $where = [];
            $where['goodsId'] = ['IN', $goodsId];
            $where['orderId'] = $v['orderId'];
            $where['isPay'] = 1;
            $returnAmountOK = $goodsDiffTab->where($where)->sum('money');
            $data['root'][$key]['returnAmountOK'] = (float)$returnAmountOK;
            $data['root'][$key]['returnAmountNO'] = bc_math($returnAmount, $returnAmountOK, 'bcsub', 2);
            if ($v['orderStatus'] == -6) $noReadrderIds[] = $v['orderId'];
            $orderWhere = " orderId = {$v['orderId']} and logType = 0 and logUserId = {$v['userId']}";
            $sql = "select logContent from __PREFIX__log_orders where {$orderWhere} order by logId desc limit 1";
            $ors = $this->query($sql);
            $data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
        }
        return $data;
    }

    /**
     * @param $orderId
     * @return mixed
     * 获取补差价订单详情
     */
    public function getDiffMoneyOrdersDetail($orderId)
    {
        $ordersModule = new OrdersModule();
        $config = $GLOBALS['CONFIG'];
        $data = array();

        $orderInfo = $ordersModule->getOrderInfoById($orderId);
        $order = $orderInfo['data'];

        if (empty($order)) {
            return $data;
        }
        $field = "og.orderId,og.weight,og.goodsId ,g.goodsSn,g.SuppPriceDiff,og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,og.skuId,og.skuSpecAttr ";
        $goodsList = $ordersModule->getOrderGoodsList($orderId, $field);
        $goods = $goodsList['data'];

        $infoField = "per.userName,per.mobile,gr.startDate,gr.endDate";
        foreach ($goods as $key => $val) {
            $goods[$key]['personName'] = '';
            $goods[$key]['personMobile'] = '';
            $whereInfo = " s.sortingFlag = 1 and gr.goodsId = {$val['goodsId']} and s.orderId = {$val['orderId']}";
            $sql = "select {$infoField} from __PREFIX__sorting_goods_relation gr 
                    left join __PREFIX__sorting s on s.id = gr.sortingId 
                    left join __PREFIX__sortingpersonnel per on per.id = s.personId where {$whereInfo}";
            $info = $this->queryRow($sql);
            $goods[$key]['sortStartDate'] = 0;
            $goods[$key]['sortEndDate'] = 0;
            $goods[$key]['sortOverTime'] = 0;
            if ($info) {
                $goods[$key]['personName'] = $info['userName'];
                $goods[$key]['personMobile'] = $info['mobile'];
                $goods[$key]['sortStartDate'] = $info['startDate']; //分拣开始时间
                $goods[$key]['sortEndDate'] = $info['endDate'];//分拣结束时间
                if (is_null($goods[$key]['sortStartDate'])) {
                    $goods[$key]['sortStartDate'] = 0;
                }
                if (is_null($goods[$key]['sortEndDate'])) {
                    $goods[$key]['sortEndDate'] = 0;
                }
                $endDateInt = strtotime($info['endDate']);
                $startDateInt = strtotime($info['startDate']);
                $diffTime = timeDiff($endDateInt, $startDateInt);//耗时
                $goods[$key]['sortOverTime'] = $diffTime['day'] * 24 * 60 + $diffTime['hour'] * 60 + $diffTime['min'];
            }
            //商品是否有补差价
            $goods[$key]['diffMoney'] = "0.00";//补差价金额
            $goods[$key]['isPayDiff'] = 0;//是否已补(0:未补|1:已补)
            //后改--这里去除只有称重才可以补差价的判断，因为在分拣里，标品也是可以补差价的
            $diffWhere = [];
            $diffWhere['orderId'] = $goods[$key]['orderId'];
            $diffWhere['goodsId'] = $goods[$key]['goodsId'];
            $goodsPricediffe = M('goods_pricediffe')->where($diffWhere)->find();
            $goods[$key]['SuppPriceDiff'] = -1;//是否补差价(-1:否|1:是)
            if ($goodsPricediffe) {
                $goods[$key]['SuppPriceDiff'] = 1;//只要存在补差价记录就是补差价商品
                $goods[$key]['diffMoney'] = $goodsPricediffe['money'];//补差价金额
                if ($goodsPricediffe['isPay'] == 1) {
                    $goods[$key]['isPayDiff'] = 1;
                }
            } else {
                $goods[$key]['isPayDiff'] = 1;
            }
        }
        $order["goodsList"] = $goods;
        //发票信息
        $order['invoiceInfo'] = [];
        if (isset($order['isInvoice']) && $order['isInvoice'] == 1) {
            $order['invoiceInfo'] = M('invoice')->where("id='" . $order['invoiceClient'] . "'")->find();
        }
        $usersServiceModule = new UsersServiceModule();
        $shopsServiceModule = new ShopsServiceModule();
        //后加包邮起步价
        $shopInfo = $shopsServiceModule->getShopsInfoById($order['shopId'], 'deliveryFreeMoney');
        $order['deliveryFreeMoney'] = $shopInfo['data']['deliveryFreeMoney'];
        $userData = $usersServiceModule->getUsersDetailById($order['userId']);
        $userInfo = $userData['data'];
        $order['buyer'] = $userInfo;

        //优惠券金额
        $order['couponMoney'] = 0;
        $couponInfo = M('coupons')->where(['couponId' => $order['couponId']])->find();
        if ($couponInfo) {
            $order['couponMoney'] = $couponInfo['couponMoney'];
        }
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($order['isPay'] == 1) {
                $order['realTotalMoney'] = $order['realTotalMoney'];
            } else {
                if ($order['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                    $order['deliverMoney'] = $order['setDeliveryMoney'];
                }
            }
        } else {
            if ($order['isPay'] != 1 && $order['setDeliveryMoney'] > 0) {
                $order['realTotalMoney'] = $order['realTotalMoney'] + $order['setDeliveryMoney'];
                $order['deliverMoney'] = $order['setDeliveryMoney'];
            }
        }
        return $order;
    }

    /**
     * @param array $loginUserInfo 当前登陆者信息
     * @param array $params
     * @return mixed
     * 订单应诉
     * int complainId 投诉单id
     * int actionStatus 操作状态【-1:拒绝退货|1：确认退货|2：确认收货】
     * string remark 操作备注
     * int returnFreight 商家是否退运费【-1：不退运费|1：退运费】
     * float returnAmount 退款金额
     */
    public function respondingOrder(array $loginUserInfo, array $params)
    {

        $complainId = (int)$params['complainId'];
        $remark = (string)$params['remark'];//操作备注
        $actionStatus = (int)$params['actionStatus'];
        $tab = M('order_complains');
        $where = [];
        $where['complainId'] = $complainId;
        $complainsInfo = $tab->where($where)->find();
        if ($complainsInfo['needRespond'] != 1) {
            return returnData(false, -1, 'error', '无效的投诉信息');
        }
        $where = [];
        $where['orderId'] = $complainsInfo['orderId'];
        $orderInfo = M('orders')->where($where)->find();

        $shopId = $orderInfo['shopId'];

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
            $logContent = '运营管理员拒绝退货';
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
            $logContent = '运营管理员已同意退货，正在退货中';
        } elseif ($actionStatus == 2) {
            if ($complainsInfo['complainStatus'] != 1) {
                return returnData(false, -1, 'error', '非退货中状态不能执行该操作');
            }
            $actionParams['fieldName'] = 'complainStatus';
            $actionParams['fieldValue'] = 2;
            $save['complainStatus'] = 2;
            $logContent = '运营管理员已确认收货，退货完成';
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
     * @param array $login_user_info 当前登陆者信息
     * @param array $params
     * @return mixed
     * 退款操作
     * int complainId 退款单id
     * int returnAmountStatus 退款状态【-1：拒绝退款|1：同意退款】
     * string remark 操作备注
     */
    public function doReturnAmount(array $login_user_info, array $params)
    {
        $tab = M('order_complains');
        $order_tab = M('orders');

        $config = $GLOBALS['CONFIG'];
        $returnAmountStatus = $params['returnAmountStatus'];
        $complainId = (int)$params['complainId'];
        $remark = (string)$params['remark'];
        $where = [];
        $where['complainId'] = $complainId;
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
        } else {//处理常规非常规订单运费问题
            $where = [];
            $where['orderToken'] = $order_info['orderToken'];
            $order_token_info = $order_merge_tab->where($where)->find();
            $orderNo_arr = explode('A', $order_token_info['value']);
            if (count($orderNo_arr) > 1) {
                $total_fee = 0;
                foreach ($orderNo_arr as $key => $value) {
                    $order_single = $order_tab->where(['orderNo' => $value])->find();
                    $realTotalMoney = $order_single['realTotalMoney'];
                    $total_fee += $realTotalMoney;
                }
                $order_info['realTotalMoney'] = $total_fee;
            }
        }
        $pay_total_fee = $order_info['realTotalMoney'] * 100;
        $pay_refund_fee = $info['returnAmount'];//退款金额
        M()->startTrans();
        $table_action_log_tab = M('table_action_log');
        $log_orders_tab = M('log_orders');
        if ($returnAmountStatus == -1) {
            //拒绝退款
            $save = [];
            $save['returnAmountStatus'] = -1;
            $save['updateTime'] = date('Y-m-d H:i:s');
            $save['returnAmountTime'] = date('Y-m-d H:i:s');
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
                'logContent' => '运营管理员拒绝退款',
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
        if ($info['returnAmount'] > 0) {
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
            }
        } else {
            $payType = $order_info['payFrom'];
            $pay_res = returnData(true);
        }
        if ($pay_res['code'] != 0) {
            M()->rollback();
            return returnData(false, -1, 'error', '退款失败');
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

        return returnData(true);
    }
}
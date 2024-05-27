<?php

namespace Adminapi\Model;

use App\Models\UserDistributionModel;
use App\Modules\Disribution\DistributionModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 分销类
 */
class DistributionModel extends BaseModel
{
    /**
     * 分销分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $userPhone = WSTAddslashes(I('userPhone'));
        $orderNo = WSTAddslashes(I('orderNo'));
        $where = " WHERE 1=1 ";
        if (!empty($userPhone)) {
            $where .= "AND u.userPhone='" . $userPhone . "'";
        }
        if (!empty($orderNo)) {
            $where .= "AND o.orderNo='" . $orderNo . "'";
        }
        $sql = "SELECT d.*,g.goodsName,g.goodsImg,o.orderNo,u.userName,u.userPhone,u.userPhoto FROM __PREFIX__user_distribution d LEFT JOIN __PREFIX__goods g ON d.goodsId=g.goodsId LEFT JOIN __PREFIX__orders o ON o.orderId=d.orderId INNER JOIN __PREFIX__users u ON o.userId=u.userId $where";
        $sql .= " ORDER BY d.id DESC";
        $page = $this->pageQuery($sql, $page, $pageSize);
        if ($page['root']) {
            $userTab = M('users');
            foreach ($page['root'] as $key => $val) {
                $invitation = $userTab->where("userId='" . $val['userId'] . "'")->find();
                $userToId = $userTab->where("userId='" . $val['UserToId'] . "'")->find();
                $page['root'][$key]['invitation'] = (array)$invitation;
                $page['root'][$key]['userToId'] = (array)$userToId;
            }
        }
        return $page;
    }

    /*
     * 编辑分销状态
     * */
    public function editStatus($data)
    {
        $response = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (empty($data['id'])) {
            $response['msg'] = '参数不全';
            return $response;
        }
        $tab = M('user_distribution');
        $distributionInfo = $tab->where("id='" . $data['id'] . "'")->find();
        $res = M('user_distribution')->where("id='" . $data['id'] . "'")->save($data);
        if ($res) {
            M('users')->where("userId='" . $distributionInfo['userId'] . "'")->setInc('distributionMoney', $distributionInfo['distributionMoney']);
            $response['code'] = 0;
            $response['msg'] = '操作成功';
        }
        return $response;
    }

    /**
     * @param $reviewerId
     * @param $userData
     * @return mixed
     * 分销佣金提现审核 (同意 & 拒绝)
     */
    public function distributionWithdrawAudit($reviewerId, $userData)
    {
        $id = I('id');
        $state = I('state');
        $remarks = WSTAddslashes(I('remarks'));
        //获取审核人信息
        if (empty($state) && !in_array($state, ['2', '3'])) {
            if (empty($data['id'])) {
                return returnData(false, -1, 'error', '传参不正确');
            }
        }

        $field = 'dw.userId,dw.state,dw.orderNo,u.distributionMoney,dw.money,dw.receivables,dw.dataFrom,u.userName';
        $distributionInfo = M('distribution_withdraw dw')->join("LEFT JOIN wst_users u ON u.userId=dw.userId ")->where(['id' => $id])->field($field)->find();
        if ($distributionInfo['state'] != 1) {
            return returnData(false, -1, 'error', '已审核状态不允许更改');
        }
        $describe = "[{$userData['loginName']}]编辑[{$distributionInfo['userName']}]的分销佣金提现审核";
        //获取审核人信息
        $data['reviewerId'] = $reviewerId;
        if ($state == 3) {
            //审核拒绝
            $data['state'] = $state;
            $data['updateTime'] = date('Y-m-d H:i:s', time());
            $data['remarks'] = $remarks;
            $dwDB = M('distribution_withdraw');
            $dwDB->startTrans(); // 启动事务
            try {
                $res = $dwDB->where(['id' => $id])->save($data);
                if ($res) {
                    if ($distributionInfo['dataFrom'] != 4) {
                        $userInfo = M('users')->where(['userId' => $distributionInfo['userId']])->find();
                        $sms = '';
                        if ($distributionInfo['dataFrom'] == 1) {
                            //余额提现
                            $field = 'balance';
                            //$nowMoney = $userInfo['balance'] + $distributionInfo['money'];
                            $nowMoney = bc_math($userInfo['balance'], $distributionInfo['money'], 'bcadd', 2);
                            $sms = '余额提现采购';
                            $describe .= "余额提现失败";
                        }
                        if ($distributionInfo['dataFrom'] == 5) {
                            //分销提现
                            $field = 'distributionMoney';
                            $nowMoney = bc_math($userInfo['distributionMoney'], $distributionInfo['money'], 'bcadd', 2);
                            $sms = '分销提现失败';
                            $describe .= "分销提现失败";
                        }
                        $sql = "update __PREFIX__users set $field={$nowMoney} where userId={$userInfo['userId']}";
                        $data = $this->execute($sql);
                        if (!$data) {
                            M()->rollback();
                            return returnData(false, -1, 'error', '提现失败');
                        }
                        if ($distributionInfo['dataFrom'] == 1) {
                            $balanceLog = [];
                            $balanceLog['userId'] = $userInfo['userId'];
                            $balanceLog['balance'] = $distributionInfo['money'];
                            $balanceLog['dataSrc'] = 1;
                            $balanceLog['orderNo'] = $distributionInfo['orderNo'];
                            $balanceLog['dataRemarks'] = $sms;
                            $balanceLog['balanceType'] = 1;
                            $balanceLog['createTime'] = date('Y-m-d H:i:s', time());
                            $balanceLog['shopId'] = 0;
                            M('user_balance')->add($balanceLog);
                        }
//                        M('users')->where(['userId'=>$distributionInfo['userId']])->setInc('distributionMoney',$distributionInfo['money']);
                    }
                    //司机提现拒绝-----返还
                    if ($distributionInfo['dataFrom'] == 4) {
                        M('psd_driver')->where(['driverId' => $distributionInfo['userId']])->setInc('cash', $distributionInfo['money']);
//                        M('psd_driver_balance')->where(['withdrawId'=>$id])->delete();
                    }
                }
                $dwDB->commit();// 提交事务
            } catch (\Exception $e) {
                // 回滚事务
                $dwDB->rollback();
            }
        } else if ($state == 2) {
            //提现成功
            $data['remarks'] = $remarks;
            $actualPayment = floatval(I('actualPayment'));
            if ($actualPayment > floatval($distributionInfo['receivables'])) {
                return returnData(false, -1, 'error', '实际打款金额不得高于应收款');
            }
            //司机提现
            if ($distributionInfo['dataFrom'] == 4) {
                if ($actualPayment != floatval($distributionInfo['receivables'])) {
                    return returnData(false, -1, 'error', '司机提现实际打款金额等于应收款');
                }
                $driverInfo = M('psd_driver')->where(['driverId' => $distributionInfo['userId']])->lock(true)->find();
//                M('psd_driver')->where(['driverId'=>$distributionInfo['userId']])->setDec('cash',$distributionInfo['money']);
                //创建司机余额流水日志
                $balanceLog = [];
                $balanceLog['driverId'] = $driverInfo['driverId'];
                $balanceLog['shopId'] = $driverInfo['shopId'];
                $balanceLog['withdrawId'] = $id;//提现id
                $balanceLog['balance'] = $distributionInfo['money'];//金额
                $balanceLog['oldBalance'] = formatAmount($driverInfo['cash'] + $distributionInfo['money']);//变化前金额
                $balanceLog['waveTaskSn'] = "";//配送单号
                $balanceLog['dataRemarks'] = "司机余额提现";//描述/备注
                $balanceLog['balanceType'] = 2;//余额标识(1:收入 2：支出)
                $balanceLog['createTime'] = date("Y-m-d H:i:s");//创建时间
                M('psd_driver_balance')->add($balanceLog);
            }
            $data['actualPayment'] = $actualPayment;
            $data['state'] = $state;
            $data['updateTime'] = date('Y-m-d H:i:s', time());
            $res = M('distribution_withdraw')->where(['id' => $id])->save($data);
        }
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
        return returnData(true);
    }

    /**
     * 分销佣金提现列表 - 分页显示
     */
    public function distributionWithdraw($data)
    {
        if (!empty($data['userPhone'])) {
            $where['u.userName'] = $data['userPhone'];
        }
        //搜索条件预留 - 后续根据需要增加
        if (!empty($data['orderNo'])) {
            $where['dw.orderNo'] = $data['orderNo'];
        }
        //提现来源【1用户余额提现、2供应商提现、3商户提现、4司机提现、5.分销提现】
        if (!empty(I('dataFrom'))) {
            $where['dw.dataFrom'] = I('dataFrom');
        }
        if (!empty($data['state'])) {
            $where['dw.state'] = $data['state'];
        }
        $where['dw.dataFlag'] = 1;

        $order = 'dw.id DESC';

        $field = '';
        //用户信息：用户昵称 用户ID 分销提现金额 (提现前余额)
        $field .= 'u.userName,u.userId,u.distributionMoney,';
        /**
         * 主键ID - 提现金额 - 打款状态 - 创建时间 - 审核时间 - 提现手续费
         * 提现方式 [姓名 银行卡号 银行开户地址]
         * 转账备注 - 提现账号 - 审核人Id - 所属银行 - 未通过原因
         * 提现来源【1用户余额提现、2供应商提现、3商户提现、4司机提现、5.分销提现（后续业务与用户余额合并），分销提现逻辑已存在而其他提现逻辑还未开发，该字段又是后来才增加的，所以默认为分销提现'
         */
        $field .= 'dw.id,dw.money,dw.state,dw.addTime,dw.updateTime,dw.transferCharge,dw.receivables,dw.actualPayment,';
        $field .= 'dw.withdrawalMethod,dw.transferChargeMoney,dw.withdrawalAccount,';
        $field .= 'dw.remarks,dw.actualName,dw.reviewerId,dw.bankName,dw.auditRemarks,dw.dataFrom,';
        $field .= 's.staffName as reviewName, ';
        //司机名称
        $field .= 'wpd.driverName,wpd.driverId,wpd.cash ';
        $mod = M('distribution_withdraw dw');
        $sql = $mod
            ->join("LEFT JOIN wst_users u ON u.userId=dw.userId ")
            ->join("LEFT JOIN wst_psd_driver wpd ON wpd.driverId = dw.userId ")
            ->join("LEFT JOIN wst_staffs s ON s.staffId=dw.reviewerId ")
            ->where($where)
            ->field($field)
            ->order($order)
            ->order("field(dw.state,1,2,3)")
            ->select();

        $sql = $mod->_sql();

        $result = $this->pageQuery($sql, $data['page'], $data['pageSize']);
        //数据处理
        if (!empty($result['root'])) {
            foreach ($result['root'] as $key => &$val) {
                if ($val['dataFrom'] != 4) {
                    $val['transferCharge'] = $val['transferCharge'] . '%'; //提现手续费 -百分比
//                    $val['balance'] = $val['distributionMoney'] - $val['money'] ;//提现后余额
                }
                $val['withdrawalMethodName'] = $this->getWithdrawalMethod($val['withdrawalMethod']);
                //司机需要
                if ($val['dataFrom'] == 4) {
                    $val['userName'] = $val['driverName'];
                    $val['userId'] = $val['driverId'];
                    $val['distributionMoney'] = $val['cash'];
                }
                $val['stateName'] = $this->getState($val['state']);
                $val['distributionMoney'] = (float)$val['distributionMoney'];
            }
            unset($val);
        }
        if (empty($result['root'])) {
            $result['root'] = [];
            $result['total'] = 0;
        }
        return $result;
    }

//  //已提现金额 - 总
//  if ($val['getWithdrawalMethod'] == 2){
//      $val['totalWithdrawMoney'] += $val['money'];
//  }
//  //待审核金额 - 总
//  if ($val['getWithdrawalMethod'] == 1){
//      $val['totalAuditMoney'] += $val['money'];
//  }

    /**
     * 分销提现 - 审核状态
     */
    public function getState($param)
    {
        $state = '';
        switch ($param) {
            case '1':
                $state = '审核中';
                break;
            case '2':
                $state = '提现成功';
                break;
            case '3':
                $state = '已拒绝';
                break;
        }
        return $state;
    }

    /**
     * 分销提现 - 提现方式
     */
    public function getWithdrawalMethod($param)
    {
        $withdrawalMethod = '';
        switch ($param) {
            case '1':
                $withdrawalMethod = '银行卡';
                break;
            case '2':
                $withdrawalMethod = '微信';
                break;
            case '3':
                $withdrawalMethod = '支付宝';
                break;
        }
        return $withdrawalMethod;
    }

    /*
	  * 编辑提现状态
	  * */
    public function editWithdrawStatus($data)
    {
        $response['code'] = -1;
        $response['msg'] = '操作失败';
        $response['data'] = array();
        if (empty($data['id'])) {
            $response['msg'] = '参数不全';
            return $response;
        }
        $tab = M('distribution_withdraw');
        $withdrawInfo = $tab->where("id='" . $data['id'] . "'")->find();
        if ($withdrawInfo['state'] != 0) {
            $response['msg'] = '状态有误';
            return $response;
        }
        $m = D('Adminapi/Orders');
        /*$amount = 0.01;
        $openid = 'oyq0348T4x_MPvGTD8k1tshVECUg';
        $orderNo = '1000001520';*/
        $userInfo = M('users')->where("userId='" . $withdrawInfo['userId'] . "'")->field("userId", "distributionMoney", "openId")->find();
        if (empty($userInfo['openId'])) {
            $response['msg'] = '该用户openid为空';
            return $response;
        }
        if (I('payType', 0) == 0) {
            $tran['amount'] = $withdrawInfo['money'];
            $tran['openid'] = $userInfo['openId'];
            $tran['orderNo'] = $withdrawInfo['orderNo'];
            $withdrawRes = $m->wxPayTransfers($tran);
            if ($withdrawRes['result_code'] == 'SUCCESS') {
                $editData['state'] = 1;
                $editData['paymentNo'] = $withdrawRes['payment_no'];
                $editData['updateTime'] = date('Y-m-d H:i:s', time());
                $res = $tab->where("id='" . $data['id'] . "'")->save($editData);
                if ($res) {
                    $response['code'] = 0;
                    $response['msg'] = '操作成功';
                }
            }
        } else {
            unset($editData);
            $editData['state'] = 1;
            $editData['updateTime'] = date('Y-m-d H:i:s', time());
            $res = $tab->where("id='" . $data['id'] . "'")->save($editData);
            if ($res) {
                $response['code'] = 0;
                $response['msg'] = '操作成功';
            }
        }
        return $response;
    }

    /*
     * 提现详情
     * */
    public function getInfo($id)
    {
        $info = M('distribution_withdraw')->where("id='" . $id . "'")->find();
        $userInfo = M('users')->where("userId='" . $info['userId'] . "'")->find();
        $info['userName'] = $userInfo['userName'];
        $info['userPhone'] = $userInfo['userPhone'];
        $info['userPhoto'] = $userInfo['userPhoto'];
        return $info;
    }

    /**
     * 获取分销记录 (后加接口)
     */
    public function getDistributionList($param)
    {
        $apiRes['code'] = -1;
        $apiRes['msg'] = '获取数据失败';
        $apiRes['data'] = array();
        $where = " where 1=1 and o.orderFlag=1 and u.userFlag=1 ";
        if (!empty($param['startDate']) && !empty($param['endDate'])) {
            $where .= " and ud.addtime between '" . $param['startDate'] . "' and '" . $param['endDate'] . "' ";
        }
        if (!empty($param['userName'])) {
            $where .= " and u.userName like '%" . $param['userName'] . "%' ";
        }
        if (!empty($param['userPhone'])) {
            $where .= " and u.userPhone like '%" . $param['userPhone'] . "%' ";
        }
        if ($param['maxMoeny'] !== '' && $param['minMoeny'] !== '') {
            $where .= " and ud.distributionMoney between '" . $param['minMoeny'] . "' and '" . $param['maxMoeny'] . "' ";
        }
        if (!empty($param['orderNo'])) {
            $where .= " and o.orderNo='" . $param['orderNo'] . "' ";
        }
        if ($param['state'] != 20) {
            $where .= " and ud.state='" . $param['state'] . "' ";
        }
        if (!empty($param['goodsCatId1'])) {
            $where .= " and g.goodsCatId1='" . $param['goodsCatId1'] . "' ";
        }
        if (!empty($param['goodsCatId2'])) {
            $where .= " and g.goodsCatId2='" . $param['goodsCatId2'] . "' ";
        }
        if (!empty($param['goodsCatId3'])) {
            $where .= " and g.goodsCatId3='" . $param['goodsCatId3'] . "' ";
        }
        $sql = "select ud.*,u.userName,u.userPhone,u.userPhoto,o.orderNo,g.goodsName,g.goodsImg,g.goodsThums from __PREFIX__user_distribution ud 
        left join __PREFIX__orders o on o.orderId=ud.orderId
        left join __PREFIX__users u on u.userId=ud.userId 
        left join __PREFIX__goods g on g.goodsId=ud.goodsId ";
        $sql .= $where;
        $sql .= "order by id desc ";
        $res = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        $sql = "select sum(ud.distributionMoney) from __PREFIX__user_distribution ud 
        left join __PREFIX__orders o on o.orderId=ud.orderId
        left join __PREFIX__users u on u.userId=ud.userId 
        left join __PREFIX__goods g on g.goodsId=ud.goodsId ";
        $sql .= $where;
        $countMoney = $this->queryRow($sql)['sum(ud.distributionMoney)'];
        $res['countMoney'] = '0.00';
        if (!is_null($res['countMoney'])) {
            $res['countMoney'] = $countMoney; //金额统计
        }
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                if ($val['distributionLevel'] == 1) {
                    $val['distributionLevelName'] = '直属下线';
                } else {
                    $val['distributionLevelName'] = '二级下线';
                }
                $orderGoods = M('order_goods')->where(['orderId' => $val['orderId'], 'goodsId' => $val['goodsId']])->field('id,goodsId,orderId,goodsNums')->find();
                $val['goodsNum'] = $orderGoods['goodsNums'];
                $buyerInfo = M('users')->where(['userId' => $val['buyerId']])->field('userName,userPhone,userPhoto')->find();
                //下单者信息
                $val['buyer_userName'] = $buyerInfo['userName'];
                $val['buyer_userPhone'] = $buyerInfo['userPhone'];
                $val['buyer_userPhoto'] = $buyerInfo['userPhoto'];
            }
            unset($val);
            $apiRes['code'] = 0;
            $apiRes['msg'] = '获取数据成功';
            $apiRes['data'] = $res;
        }
        return $apiRes;
    }

    /**
     * 分销记录金额统计 (后加接口,配合分销记录使用)
     */
    public function getDistributionListCountMoney($param)
    {
        $apiRes['code'] = 0;
        $apiRes['msg'] = '获取数据成功';
        $apiRes['data'] = array();
        $where = " where 1=1 and o.orderFlag=1 and u.userFlag=1 ";
        if (!empty($param['startDate']) && !empty($param['endDate'])) {
            $where .= " and ud.addtime between '" . $param['startDate'] . "' and '" . $param['endDate'] . "' ";
        }
        if (!empty($param['userName'])) {
            $where .= " and u.userName like '%" . $param['userName'] . "%' ";
        }
        if (!empty($param['userPhone'])) {
            $where .= " and u.userPhone like '%" . $param['userPhone'] . "%' ";
        }
        if ($param['maxMoeny'] !== '' && $param['minMoeny'] !== '') {
            $where .= " and ud.distributionMoney between '" . $param['minMoeny'] . "' and '" . $param['maxMoeny'] . "' ";
        }
        if (!empty($param['orderNo'])) {
            $where .= " and o.orderNo='" . $param['orderNo'] . "' ";
        }
        if ($param['state'] != 20) {
            $where .= " and ud.state='" . $param['state'] . "' ";
        }
        if (!empty($param['goodsCatId1'])) {
            $where .= " and g.goodsCatId1='" . $param['goodsCatId1'] . "' ";
        }
        if (!empty($param['goodsCatId2'])) {
            $where .= " and g.goodsCatId2='" . $param['goodsCatId2'] . "' ";
        }
        if (!empty($param['goodsCatId3'])) {
            $where .= " and g.goodsCatId3='" . $param['goodsCatId3'] . "' ";
        }
        $sql = "select sum(ud.distributionMoney) from __PREFIX__user_distribution ud 
        left join __PREFIX__orders o on o.orderId=ud.orderId
        left join __PREFIX__users u on u.userId=ud.userId 
        left join __PREFIX__goods g on g.goodsId=ud.goodsId ";
        $sql .= $where;
        $res = $this->queryRow($sql)["sum(ud.distributionMoney)"];
        $apiRes['data'] = '0.00';
        if (!is_null($res)) {
            $apiRes['data'] = $res;
        }
        return $apiRes;
    }


    /**
     * 会员分销关系查询 (后加接口)
     * @param string userPhone 会员手机号
     * @param string loginName 登陆账号
     */
    public function getDistributionRelation($param)
    {
        $apiRes['code'] = -1;
        $apiRes['msg'] = '获取数据失败';
        $apiRes['data'] = array();
        $where['userFlag'] = 1;
        if (!empty($param['userPhone'])) {
            $where['userPhone'] = $param['userPhone'];
        }
        if (!empty($param['loginName'])) {
            $where['loginName'] = $param['loginName'];
        }
        $userInfo = M('users')->where($where)->field('userId,userName,userPhone')->find();
        if (!$userInfo) {
            $apiRes['msg'] = '该用户已被删除或被禁用';
            return $apiRes;
        }

        $where = [];
        $where['dr.userId'] = $userInfo['userId'];
        $where['u.userFlag'] = 1;
        $res = M('distribution_relation dr')
            ->join("left join wst_users u on u.userId=dr.userId")
            ->field('dr.*,u.userName,u.userPhone,u.loginName,u.userPhoto')
            ->where($where)
            ->select();
        if ($res) {
            foreach ($res as $key => &$val) {
                $invitationUserInfo = M('users')->where(['userId' => $val['pid']])->field('userId,userName,userPhone,loginName')->find();
                $val['invitation_userName'] = $invitationUserInfo['userName'];
                $val['invitation_loginName'] = $invitationUserInfo['loginName'];
                $val['invitation_userPhone'] = $invitationUserInfo['userPhone'];
                $val['invitation_userPhoto'] = $invitationUserInfo['userPhoto'];
            }
            unset($val);
            $apiRes['code'] = 0;
            $apiRes['msg'] = '获取数据成功';
            $apiRes['data'] = $res;
        }
        return $apiRes;
    }

    /**
     * 分销佣金提现列表 分页
     */
    public function getDistributionWithdraw($data)
    {
        $apiRes['code'] = -1;
        $apiRes['msg'] = '获取数据失败';
        $apiRes['data'] = array();

        $where = " WHERE 1=1 ";
        if (!empty($data['userPhone'])) {
            $where .= "AND u.userPhone='" . $data['userPhone'] . "'";
        }
        if (!empty($data['loginName'])) {
            $where .= "AND u.loginName='" . $data['loginName'] . "'";
        }
        if (!empty($data['orderNo'])) {
            $where .= "AND dw.orderNo='" . $data['orderNo'] . "'";
        }
        if (!empty($data['startDate']) && !empty($data['endDate'])) {
            $where .= "AND dw.addTime between '" . $data['startDate'] . "' and '" . $data['endDate'] . "' ";
        }
        if ($data['state'] != 20) {
            $where .= "AND dw.state='" . $data['state'] . "'";
        }
        $sql = "SELECT dw.*,u.userName,u.userPhone,u.userPhoto FROM wst_distribution_withdraw dw LEFT JOIN wst_users u ON u.userId =dw.userId $where";
        $sql .= " ORDER BY dw.id DESC";
        $res = $this->pageQuery($sql, $data['page'], $data['pageSize']);
        if (!empty($res['root'])) {
            foreach ($res['root'] as $key => &$val) {
                switch ($val['state']) {
                    case 1:
                        $val['stateName'] = "<span class=\"label label-success\" '>提现成功</span>";
                        break;
                    case 2:
                        $val['stateName'] = "<span class=\"label label-warning\" '>已拒绝</span>";
                        break;
                    default:
                        $val['stateName'] = "<span class=\"label label-warning\" '>待审核</span>";
                        break;
                }
            }
            unset($val);

            $apiRes['code'] = 0;
            $apiRes['msg'] = '获取数据成功';
            $apiRes['data'] = $res;
        }
        return $apiRes;
    }

    /**
     * 分销提现接口金额统计 (后加接口,配合分销提现列表接口使用)
     */
    public function getDistributionWithdrawCountMoney($data)
    {
        $apiRes['apiCode'] = 0;
        $apiRes['apiInfo'] = '获取数据成功';
        $apiRes['apiState'] = 'success';

        $where = " WHERE 1=1 ";
        if (!empty($data['userPhone'])) {
            $where .= "AND u.userPhone='" . $data['userPhone'] . "'";
        }
        if (!empty($data['loginName'])) {
            $where .= "AND u.loginName='" . $data['loginName'] . "'";
        }
        if (!empty($data['orderNo'])) {
            $where .= "AND dw.orderNo='" . $data['orderNo'] . "'";
        }
        if (!empty($data['startDate']) && !empty($data['endDate'])) {
            $where .= "AND dw.addTime between '" . $data['startDate'] . "' and '" . $data['endDate'] . "' ";
        }
        if ($data['state'] != 20) {
            $where .= "AND dw.state='" . $data['state'] . "'";
        }
        $sql = "SELECT sum(dw.money) FROM wst_distribution_withdraw dw LEFT JOIN wst_users u ON u.userId =dw.userId $where";
        $res = $this->queryRow($sql)['sum(dw.money)'];
        $apiRes['apiData'] = '0.00';
        if (!is_null($res)) {
            $apiRes['apiData'] = $res;
        }
        return $apiRes;
    }

    /**
     * 分销-用户分销记录列表
     * @param array $params
     * -string orderNo 订单号
     * -string goodsName 商品名
     * -string paymentUserPhone 下单人手机号
     * -datetime addtimeStart 时间-开始时间
     * -datetime addtimeEnd 时间-结束时间
     * -string invitationName 邀请人名称
     * -string inviteeName 受邀人名称
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     */
    public function getDistributionLogList(array $params)
    {
        $disModule = new DistributionModule();
        $result = $disModule->getDistributionLogList($params);
        return $result;
    }

}

?>
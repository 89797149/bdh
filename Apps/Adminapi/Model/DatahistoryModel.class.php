<?php

namespace Adminapi\Model;
// use function Qiniu\base64_urlSafeDecode;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 数据历史
 */
class DatahistoryModel extends BaseModel
{

    /**
     * 短信日志列表
     */
    public function logSmsList($startDate, $endDate, $phone, $smsLoginName, $page = 1, $pageSize = 15)
    {
        $where = " WHERE 1=1 ";
        if (!empty($startDate)) {
            if (count(explode('-', $startDate)) > 1) {
                $startTime = $startDate;
            } else {
                $startTime = date('Y-m-d H:i:s', $startDate);
            }
            $where .= " and ls.createTime > '" . $startTime . "'";
        }
        if (!empty($endDate)) {
            if (count(explode('-', $endDate)) > 1) {
                $endTime = $endDate;
            } else {
                $endTime = date('Y-m-d H:i:s', $endDate);
            }
            $where .= " and ls.createTime <= '" . $endTime . "'";
        }
        if (!empty($phone)) {
            $where .= " and ls.smsPhoneNumber like '%" . $phone . "%' ";
        }
        if (!empty($smsLoginName)) {
            $where .= " and u.loginName like '%" . $smsLoginName . "%' ";
        }
        $sql = "select ls.*,u.loginName as smsLoginName from __PREFIX__log_sms ls left join __PREFIX__users u on ls.smsUserId = u.userId $where order by ls.createTime desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * @param $params
     * @return array
     * 积分日志列表
     */
    public function logUserScoreList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $userPhone = $params['userPhone'];
        $loginName = $params['loginName'];
        $dataSrc = $params['dataSrc'];
        $scoreType = $params['scoreType'];

        $where = " WHERE 1 = 1 ";
        if (!empty($startDate)) {
            if (count(explode('-', $startDate)) > 1) {
                $startTime = $startDate;
            } else {
                $startTime = date('Y-m-d H:i:s', $startDate);
            }
            $where .= " and us.createTime > '" . $startTime . "'";
        }
        if (!empty($endDate)) {
            if (count(explode('-', $endDate)) > 1) {
                $endTime = $endDate;
            } else {
                $endTime = date('Y-m-d H:i:s', $endDate);
            }
            $where .= " and us.createTime <= '" . $endTime . "'";
        }
        if ($dataSrc > 0) {
            $where .= " and us.dataSrc = " . $dataSrc;
        }
        if ($scoreType > 0) {
            $where .= " and us.scoreType = " . $scoreType;
        }
        if (!empty($userPhone)) {
            $where .= " and u.userPhone like '%" . $userPhone . "%' ";
        }
        if (!empty($loginName)) {
            $where .= " and u.loginName like '%" . $loginName . "%' ";
        }

        $field = "us.*,u.loginName,u.userPhone";
        $sql = "select {$field} from __PREFIX__user_score us 
                left join __PREFIX__users u on us.userId = u.userId 
                {$where} order by us.createTime desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        $dataSrcArr = $this->integralDataSrcList();
        $scoreTypeArr = array('1' => '收入', '2' => '支出');
        if (!empty($rs['root'])) {
            foreach ($rs['root'] as $k => $v) {
                $rs['root'][$k]['dataSrc'] = $dataSrcArr[$v['dataSrc']];
                $rs['root'][$k]['scoreType'] = $scoreTypeArr[$v['scoreType']];
            }
        }
        return $rs;
    }

    /**
     * 积分来源列表
     */
    public function integralDataSrcList()
    {
        return array(
            '0' => '全部',
            '1' => '订单',
            '2' => '评价',
            '3' => '订单取消返还',
            '4' => '拒收返还',
            '5' => 'app签到',
            '6' => '小程序签到获取',
            '7' => '抽奖获得',
            '8' => '小程序邀请好友获得',
            '9' => 'app邀请好友获得',
            '10' => '小程序新人专享大礼',
            '11' => 'app新人专享大礼',
            '12' => '线下门店消费'
        );
    }

    /**
     * 余额流水列表
     */
    public function logUserBalanceList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $userPhone = $params['userPhone'];
        $loginName = $params['loginName'];
        $dataSrc = $params['dataSrc'];
        $balanceType = $params['balanceType'];

        $where = " WHERE 1=1 ";
        if (!empty($startDate)) {
            if (count(explode('-', $startDate)) > 1) {
                $startTime = $startDate;
            } else {
                $startTime = date('Y-m-d H:i:s', $startDate);
            }
            $where .= " and ub.createTime > '" . $startTime . "'";
        }
        if (!empty($endDate)) {
            if (count(explode('-', $endDate)) > 1) {
                $endTime = $endDate;
            } else {
                $endTime = date('Y-m-d H:i:s', $endDate);
            }
            $where .= " and ub.createTime <= '" . $endTime . "'";
        }
        if ($dataSrc > 0) {
            $where .= " and ub.dataSrc = " . $dataSrc;
        }
        if ($balanceType > 0) {
            $where .= " and ub.balanceType = " . $balanceType;
        }
        if (!empty($userPhone)) {
            $where .= " and u.userPhone like '%" . $userPhone . "%' ";
        }
        if (!empty($loginName)) {
            $where .= " and u.loginName like '%" . $loginName . "%' ";
        }
        $sql = "select ub.*,u.loginName,u.userPhone,s.shopName from __PREFIX__user_balance ub left join __PREFIX__users u on ub.userId = u.userId left join __PREFIX__shops s on ub.shopId = s.shopId $where order by ub.createTime desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        $dataSrcArr = array('1' => '线上', '2' => '线下');
        $balanceTypeArr = array('1' => '收入', '2' => '支出');
        if (!empty($rs['root'])) {
            foreach ($rs['root'] as $k => $v) {
                $rs['root'][$k]['dataSrc'] = $dataSrcArr[$v['dataSrc']];
                $rs['root'][$k]['balanceType'] = $balanceTypeArr[$v['balanceType']];
            }
        }
        return $rs;
    }
}
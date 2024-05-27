<?php

namespace Adminapi\Action;

use Adminapi\Model\DatahistoryModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 数据历史
 */
class DatahistoryAction extends BaseAction
{

    /**
     * 短信日志列表
     */
    public function logSmsList()
    {
        $this->isLogin();
//        $this->checkPrivelege('yjfklb_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $phone = I('userPhone');
        $smsLoginName = I('smsLoginName');
        $m = new DatahistoryModel();
        $list = $m->logSmsList($startDate, $endDate, $phone, $smsLoginName, $page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 积分日志列表
     */
    public function logUserScoreList()
    {
        $this->isLogin();

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $userPhone = I('userPhone');
        $loginName = I('loginName');
        $dataSrc = I('dataSrc', 0, 'intval');
        $scoreType = I('scoreType', 0, 'intval');

        $params = [];
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;
        $params['userPhone'] = $userPhone;
        $params['loginName'] = $loginName;
        $params['dataSrc'] = $dataSrc;
        $params['scoreType'] = $scoreType;
        $m = new DatahistoryModel();
        $list = $m->logUserScoreList($params);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 积分来源列表
     */
    public function integralDataSrcList()
    {
//        $this->isLogin();
//        $this->checkPrivelege('yjfklb_00');
        $m = D('Adminapi/Datahistory');
        $list = $m->integralDataSrcList();
//        $this->returnResponse(0,'操作成功',$list);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 余额流水列表
     */
    public function logUserBalanceList()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $startDate = I('startDate');
        $endDate = I('endDate');
        $userPhone = I('userPhone');
        $loginName = I('loginName');
        $dataSrc = I('dataSrc', 0, 'intval');
        $balanceType = I('balanceType', 0, 'intval');

        $params = [];
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;
        $params['userPhone'] = $userPhone;
        $params['loginName'] = $loginName;
        $params['dataSrc'] = $dataSrc;
        $params['balanceType'] = $balanceType;

        $m = new DatahistoryModel();
        $list = $m->logUserBalanceList($params);
        $this->ajaxReturn(returnData($list));
    }
}
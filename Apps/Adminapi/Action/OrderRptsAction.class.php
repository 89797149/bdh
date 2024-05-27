<?php

namespace Adminapi\Action;

use Adminapi\Model\OrderRptsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单报表控制器
 */
class OrderRptsAction extends BaseAction
{
    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('dttj_00');
        $this->assign('startDate', date('Y-m-d H:i:s', strtotime("-31 day")));
        $this->assign('endDate', date('Y-m-d H:i:s'));
        //获取地区信息
        $m = D('Adminapi/Areas');
        $this->assign('areaList', $m->queryShowByList(0));
        $this->display("/reports/orders");
    }

    /**
     * 按月/日统计订单
     */
    public function queryByMonthAndDays()
    {
        $this->isLogin();
        $this->checkPrivelege('dttj_00');
        $rs = D('Adminapi/OrderRpts')->queryByMonthAndDays();
        $this->ajaxReturn($rs);
    }

    /**
     * 退款日志记录查询
     */
    public function refundFee()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $params = [];
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;

        $m = new OrderRptsModel();
        $list = $m->refundFee($params);

        $this->ajaxReturn($list);
    }

    /**
     * 订单转化率统计
     * 默认显示一周
     */
    public function orderConversionRate()
    {
        $this->isLogin();
        $rs = D('Adminapi/OrderRpts')->orderConversionRate();
        $this->ajaxReturn($rs);
    }
}
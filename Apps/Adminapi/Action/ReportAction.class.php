<?php

namespace Adminapi\Action;

use Adminapi\Model\ReportModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 报表控制器
 */
class ReportAction extends BaseAction
{
    /**
     * 获取营业数据列表
     */
    public function businessList()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $shopWords = I('shopWords', 0);
        $params = [];
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['shopWords'] = $shopWords;
        $m = new ReportModel();
        $list = $m->businessListTow($params);
//        $list = $m->businessList($params);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取营业数据详情----按分类查看
     */
    public function businessDetail()
    {
        $this->isLogin();
        $reportId = I('reportId');
        if (empty($reportId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $data = $m->businessDetailTwo($reportId);
//        $data = $m->businessDetail($reportId);
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '获取数据失败', '数据错误');
        }
        $this->ajaxReturn($retdata);
    }

    /**
     * 获取商品销售列表
     */
    public function commoditySaleList()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $type = (int)I('statType', 1);//1:按商品统计|2：按分类统计
        $m = new ReportModel();
        $list = $m->commoditySaleList($startDate, $endDate, $type);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 商品销售----获取用户商品销量明细---根据商品ID获取/根据分类ID获取
     */
    public function commoditySalesUserDetail()
    {
        $this->isLogin();
        $goodsId = (int)I('goodsId');
        $goodsCatId3 = (int)I('goodsCatId3');
        $m = new ReportModel();
        $data = $m->commoditySalesUserDetail($goodsId, $goodsCatId3);
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '获取数据失败', '数据错误');
        }
        $this->ajaxReturn($retdata);
    }

    /**
     * 商品销售---图表模式
     */
    public function CommoditySaleData()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->CommoditySaleData($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取订单统计列表
     */
    public function statOrder()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->statOrder($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取客户统计列表
     */
    public function getUsersList()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->getUsersList($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取销售毛利列表
     */
    public function profitList()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->profitList($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 销售毛利列表--商品列表---获取商品详情
     */
    public function commodityProfitList()
    {
        $this->isLogin();
        $goodsId = (int)I('goodsId');
        if (empty($goodsId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $data = $m->commodityProfitList($goodsId);
        if ($data) {
            $retdata = returnData($data);
        } else {
            $retdata = returnData($data, -1, 'error', '获取数据失败', '数据错误');
        }
        $this->ajaxReturn($retdata);
    }

    /**
     * 销售毛利---图表模式
     */
    public function profitStatData()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->profitStatData($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 客户毛利
     */
    public function userStatic()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->userStatic($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 客户毛利----详情
     */
    public function userProfitStatList()
    {
        $this->isLogin();
        $userId = I('userId', 0);//客户id
        if (empty($userId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->userProfitStatList($userId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 采购汇总
     */
    public function purchaseStatDetail()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->purchaseStatDetail($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 损耗概况-----待完善
     * TODO  待完善--不明白
     */
    public function storeLossSummary()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->storeLossSummary($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 库房损耗
     */
    public function storeLoss()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->storeLoss($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 退货报损
     */
    public function returnLoss()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->returnLoss($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取综合统计
     * 1:全部|2:最近30天|3:最近90天|4:自定义时间
     * https://www.yuque.com/youzhibu/ruah6u/tsvuyc
     */
    public function getComprehensiveReport()
    {
        $this->isLogin();
        $type = (int)I('type', 1);
        if (!in_array($type, [1, 2, 3, 4])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择查询时间', '数据错误'));
        }
        $params = [];
        $params['type'] = $type;
        if ($type == 4) {
            $startDate = I('startDate');//开始时间
            $endDate = I('endDate');//结束时间
            if (empty($startDate) || empty($endDate)) {
                $this->ajaxReturn(returnData(false, -1, 'error', '请选择自定义查询时间', '数据错误'));
            }
            $params['startDate'] = $startDate;
            $params['endDate'] = $endDate;
        }

        $m = new ReportModel();
        $list = $m->getComprehensiveReport($params);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 获取综合订单统计
     * type 1:全部|2:最近30天|3:最近90天|4:自定义时间
     * https://www.yuque.com/youzhibu/ruah6u/yvd0zt
     */
    public function getComprehensiveOrdersList()
    {
        $this->isLogin();
        $type = (int)I('type', 0);
        $sort = I('sort', 'desc');
        $page = (int)I('page', 1, 'intval');
        $pageSize = (int)I('pageSize', 15, 'intval');
        if (!in_array($type, [1, 2, 3, 4])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择查询时间', '数据错误'));
        }
        $params = [];
        $params['type'] = $type;//时间类型
        $params['sort'] = $sort;//时间类型
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        if ($type == 4) {
            $startDate = I('startDate');//开始时间
            $endDate = I('endDate');//结束时间
            if (empty($startDate) || empty($endDate)) {
                $this->ajaxReturn(returnData(false, -1, 'error', '请选择自定义查询时间', '数据错误'));
            }
            $params['startDate'] = $startDate;
            $params['endDate'] = $endDate;
        }

        $m = new ReportModel();
        $list = $m->getComprehensiveOrdersList($params);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 报损损耗
     */
    public function breakageLoss()
    {
        $this->isLogin();
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '请选择查询时间', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->breakageLoss($startDate, $endDate);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }
}
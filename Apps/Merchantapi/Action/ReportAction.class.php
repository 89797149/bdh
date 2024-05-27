<?php

namespace Merchantapi\Action;

use Merchantapi\Model\ReportModel;

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
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gk9bgt
     * @param string token
     * @param string startDate 时间区间-开始时间
     * @param string endDate 时间区间-结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function businessList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = new ReportModel();
        $data = $m->businessListTwo($startDate, $endDate, $page, $pageSize, $shopId);
//        if (I('debug') == 1) {
//            $data = $m->businessListTwo($startDate, $endDate, $page, $pageSize, $shopId);
//        } else {
//            $data = $m->businessList($startDate, $endDate, $page, $pageSize, $shopId);
//        }
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取营业数据详情----按分类查看
     */
    public function businessDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $reportId = I('reportId');
        if (empty($reportId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        if(I('debug') == 1){
            $data = $m->businessDetailTwo($reportId, $shopId);
        }else{
            $data = $m->businessDetail($reportId, $shopId);
        }
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
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $type = (int)I('statType', 1);//1:按商品统计|2：按分类统计
        $m = new ReportModel();
        if(I('debug') == 1){
            $list = $m->commoditySaleListTwo($startDate, $endDate, $type, $profitType = 0, $shopId);
        }else{
            $list = $m->commoditySaleList($startDate, $endDate, $type, $profitType = 0, $shopId);
        }
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 商品销售----获取用户商品销量明细---根据商品ID获取/根据分类ID获取
     */
    public function commoditySalesUserDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $goodsId = (int)I('goodsId');
        $goodsCatId3 = (int)I('goodsCatId3');
        $m = new ReportModel();
        $data = $m->commoditySalesUserDetail($goodsId, $goodsCatId3, $profitType = 0, $shopId);
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
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->CommoditySaleData($startDate, $endDate, $profitType = 0, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取订单统计列表
     */
    public function statOrder()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->statOrder($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取客户统计列表
     */
    public function getUsersList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->getUsersList($startDate, $endDate, $profitType = 0, $userType = 0, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 获取销售毛利列表
     */
    public function profitList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->profitList($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 销售毛利列表--商品列表---获取商品详情
     */
    public function commodityProfitList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $goodsId = (int)I('goodsId');
        if (empty($goodsId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $data = $m->commodityProfitList($goodsId, $shopId);
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
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->profitStatData($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 客户毛利
     */
    public function userStatic()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->userStatic($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 客户毛利----详情
     */
    public function userProfitStatList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $userId = I('userId', 0);//客户id
        if (empty($userId)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->userProfitStatList($userId, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 采购汇总
     */
    public function purchaseStatDetail()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->purchaseStatDetail($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 损耗概况-----待完善
     * TODO d 待完善--不明白
     */
    public function storeLossSummary()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->storeLossSummary($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 库房损耗
     */
    public function storeLoss()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->storeLoss($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 退货报损
     */
    public function returnLoss()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '有字段不允许为空', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->returnLoss($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }

    /**
     * 报损损耗
     */
    public function breakageLoss()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $startDate = I('startDate');//开始时间
        $endDate = I('endDate');//结束时间
        if (empty($startDate) || empty($endDate)) {
            $data = array();
            $retdata = returnData($data, -1, 'error', '请选择查询时间', '数据错误');
            $this->ajaxReturn($retdata);
        }
        $m = new ReportModel();
        $list = $m->breakageLoss($startDate, $endDate, $shopId);
        $data = returnData($list);
        $this->ajaxReturn($data);
    }
}
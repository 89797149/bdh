<?php

namespace Adminapi\Action;

use App\Enum\ExceptionCodeEnum;
use function App\Util\responseError;
use function App\Util\responseSuccess;
use Adminapi\Model\CashierModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com,
 * 联系QQ:1692136178
 * ============================================================================
 * 收银端
 */
class CashierAction extends BaseAction
{
    /**
     * 收银订单数据统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ggghk7
     * @param string token
     * @return json
     * */
    public function countPosOrders()
    {
        $this->isLogin();
        $model = new CashierModel();
        $data = $model->countPosOrders();
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 退货记录统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ycpuk5
     * */
    public function countReturnGoodsLog()
    {
        $this->isLogin();
        $model = new CashierModel();
        $result = $model->countReturnGoodsLog();
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 退货记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lg6tgh
     * @param string token
     * @param string bill_no 单号
     * @param date startDate 时间区间-开始时间
     * @param date endDate 时间区间-结束时间
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getReturnGoodsLogList()
    {
        $this->isLogin();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['shopWords'] = I('shopWords', 0);
        $model = new CashierModel();
        $result = $model->getReturnGoodsLogList($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 换货记录统计
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sge6fp
     * */
    public function countExchangeGoodsLog()
    {
        $this->isLogin();
        $model = new CashierModel();
        $result = $model->countExchangeGoodsLog();
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 换货记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ih4oxe
     * @param string token
     * @param string bill_no 单号
     * @param date startDate 时间区间-开始时间
     * @param date endDate 时间区间-结束时间
     * @param string action_user_name 操作人
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getExchangeGoodsLogList()
    {
        $this->isLogin();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['shopWords'] = I('shopWords', 0);
        $model = new CashierModel();
        $result = $model->getExchangeGoodsLogList($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 收银订单列表
     * https://www.yuque.com/youzhibu/ruah6u/mpeb9y
     */
    public function getPosOrderList()
    {
        $this->isLogin();
        $params = I();
        $params['page'] = I('page', 1);
        $params['pageSize'] = I('pageSize', 15);
        $params['pageSupport'] = I('pageSupport', 1);
        $params['shopWords'] = I('shopWords', 0);//店铺名称|编号
        $params['state'] = 3;//收银订单只展示已结算的
        $model = new CashierModel();
        $res = $model->getPosOrderList($params);
        $this->ajaxReturn($res);
    }

    /**
     * 获取Pos订单详情
     * https://www.yuque.com/youzhibu/ruah6u/ft32q4
     */
    public function getPosOrderDetail()
    {
        $this->isLogin();
        $posId = (int)I('posId');
        if (empty($posId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择查看订单'));
        }
        $model = new CashierModel();
        $data = $model->getPosOrderDetail($posId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取收银订单日志
     * @param string token
     * @param int pos_order_id 收银订单id
     * @return json
     *https://www.yuque.com/youzhibu/ruah6u/eihid6
     * */
    public function getPosOrdersLog()
    {
        $this->MemberVeri();
        $pos_order_id = (int)I('pos_order_id');
        if (empty($pos_order_id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $model = new CashierModel();
        $data = $model->getPosOrdersLog($pos_order_id);
        $this->ajaxReturn(returnData($data));
    }

    #############################收银报表-start###################################

    /**
     * 营业数据统计报表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/nnsup4
     * @param string token
     * @param strng keywords 店铺关键字(店铺名称/店铺编号)
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function businessStatisticsReport()
    {
        $this->MemberVeri();
        $keywords = I('keywords');
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $model = new CashierModel();
        $result = $model->businessStatisticsReport($keywords, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 商品销量统计报表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/smqgth
     * @param string token
     * @param string keywords 店铺关键字(店铺名称/店铺编号)
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int data_type 统计类型(1:按商品统计 2:按分类统计)
     * @param int model_type 模式(1:列表模式 2:图表模式)
     * @param int goodsCatId1 商品商城一级分类id
     * @param int goodsCatId2 商品商城二级分类id
     * @param int goodsCatId3 商品商城三级分类id
     * @param string goods_keywords 商品名称或商品编码 PS:仅仅按商品统计的场景需要
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function goodsSaleReport()
    {
        $this->MemberVeri();
        $keywords = I('keywords');
        $start_date = I('startDate');
        $end_date = I('endDate');
        $goods_keywords = (string)I('goods_keywords');
        $data_type = (int)I('data_type', 1);
        $model_type = (int)I('model_type', 1);
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (!in_array($data_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计类型'));
        }
        if (!in_array($model_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计模式'));
        }
        $model = new CashierModel();
        $params = array(
            'keywords' => $keywords,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'page' => $page,
            'page_size' => $page_size,
            'data_type' => $data_type,
            'model_type' => $model_type,
            'goods_cat_id1' => (int)I('goodsCatId1'),
            'goods_cat_id2' => (int)I('goodsCatId2'),
            'goods_cat_id3' => (int)I('goodsCatId3'),
            'goods_keywords' => $goods_keywords,
            'export' => $export
        );
        $result = $model->goodsSaleReport($params);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 商品销量统计报表-客户详情
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/wq3hz9
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int data_type 统计类型(1:按商品统计 2:按分类统计)
     * @param int goodsId 商品id
     * @param int catId 分类id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function goodsSaleReportCustomerDetail()
    {
        $this->MemberVeri();
        $start_date = I('startDate');
        $end_date = I('endDate');
        $data_type = (int)I('data_type', 1);
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $goods_id = (int)I('goodsId');
        $cat_id = (int)I('catId');
        if ($data_type == 1 && empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品id不能为空'));
        }
        if ($data_type == 2 && empty($cat_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '分类id不能为空'));
        }
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (!in_array($data_type, array(1, 2))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的统计类型'));
        }
        $params = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => $data_type,
            'page' => $page,
            'page_size' => $page_size,
            'goods_id' => $goods_id,
            'cat_id' => $cat_id,
        );
        $m = new CashierModel();
        $data = $m->goodsSaleReportCustomerDetail($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户统计报表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/hdu7ly
     * @param string token
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param string userName 客户名称
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerReport()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $user_name = (string)I('userName');
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $data = $m->customerReport($shop_id, $user_name, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 订单统计报表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/mmlzp2
     * @param string token
     * @param string keywords 店铺名称/店铺编号
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function ordersReport()
    {
        $this->MemberVeri();
        $keywords = I('keywords');
        $start_date = I('startDate');
        $end_date = I('endDate');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $data = $m->ordersReport($keywords, $start_date, $end_date, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 销售毛利统计
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/nnvung
     * @param string token
     * @param string keywords 店铺关键字(店铺名称/店铺编号)
     * @param date startDate 开始日期
     * @param date endDate 结束日期
     * @param int date_type 统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
     * @param int model_type 统计模式(1:列表模式 2:图表模式)
     * @param int goodsCatId1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * @param int goodsCatId2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * @param int goodsCatId3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * @param string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * @param string userName 客户名称 PS:仅按客户统计时需要
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function saleGrossProfit()
    {
        $this->MemberVeri();
        $keywords = I('keywords');
        $start_date = I('startDate');
        $end_date = I('endDate');
        $data_type = I('data_type', 1);
        $model_type = I('model_type', 1);
        $goods_cat_id1 = (int)I('goods_cat_id1');
        $goods_cat_id2 = (int)I('goods_cat_id2');
        $goods_cat_id3 = (int)I('goods_cat_id3');
        $goods_keywords = (string)I('goods_keywords');
        $user_name = (string)I('userName');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $m = new CashierModel();
        $params = array(
            'keywords' => $keywords,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data_type' => $data_type,
            'model_type' => $model_type,
            'goods_cat_id1' => $goods_cat_id1,
            'goods_cat_id2' => $goods_cat_id2,
            'goods_cat_id3' => $goods_cat_id3,
            'goods_keywords' => $goods_keywords,
            'user_name' => $user_name,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export
        );
        $data = $m->saleGrossProfit($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 销售毛利-客户详情 PS:仅销售毛利-按商品统计时才有该操作
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/dgtt82
     * @param date startDate 开始日期
     * @param date endData 结束日期
     * @param int goodsId 商品id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function saleGrossProfitCustomerDetail()
    {
        $this->MemberVeri();
        $keywords = I('keywords');
        $start_date = I('startDate');
        $end_date = I('endDate');
        $goods_id = (int)I('goodsId');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的商品'));
        }
        $params = array(
            'keywords' => $keywords,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'goods_id' => $goods_id,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export
        );
        $m = new CashierModel();
        $data = $m->saleGrossProfitCustomerDetail($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户毛利
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/mdwp3f
     * @param date startDate 开始日期
     * @param date endData 结束日期
     * @param string userName 客户名称
     * @param string bill_no 单号
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerGrossProfit()
    {
        $login_detail = $this->MemberVeri();
        $shop_id = $login_detail['shopId'];
        $start_date = I('startDate');
        $end_date = I('endDate');
        $user_name = I('userName');
        $bill_no = I('bill_no');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($start_date) || empty($end_date)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的开始日期和结束日期'));
        }
        $params = array(
            'shop_id' => $shop_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_name' => $user_name,
            'bill_no' => $bill_no,
            'page' => $page,
            'page_size' => $page_size,
            'export' => $export,
        );
        $m = new CashierModel();
        $data = $m->customerGrossProfit($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 客户毛利-详情
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/gu0lu1
     * @param string token
     * @param int order_id 订单id
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return json
     * */
    public function customerGrossProfitToDetail()
    {
        $this->MemberVeri();
        $order_id = I('order_id');
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export');
        if (empty($order_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $m = new CashierModel();
        $data = $m->customerGrossProfitToDetail($order_id, $page, $page_size, $export);
        $this->ajaxReturn(returnData($data));
    }
    #############################收银报表-start###################################
}
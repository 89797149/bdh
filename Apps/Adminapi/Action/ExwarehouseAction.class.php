<?php
/**
 * 出库管理
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-06
 * Time: 16:58
 */

namespace Adminapi\Action;


use App\Enum\ExceptionCodeEnum;
use Adminapi\Model\ExWarehouseModel;

class ExwarehouseAction extends BaseAction
{
    /**
     * 出库单-单据列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/froccd
     * */
    public function getExWarehouseOrderList()
    {
        $this->isLogin();
        $requstParams = I();
        $params = array(
            'shop_keywords' => '',
            'pagetype' => '',
            'examine_status' => '',
            'bill_date_start' => '',
            'bill_date_end' => '',
            'number_or_creater' => '',
            'relation_order_number' => '',
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($params, $requstParams);
        $mod = new ExWarehouseModel();
        $result = $mod->getExWarehouseOrderList($params);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-单据详情
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/gyekgm
     * */
    public function getExWarehouseOrderDetail()
    {
        $this->isLogin();
        $exOrderId = (int)I('ex_order_id');
        if (empty($exOrderId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $export = (int)I('export');//导出(0:否 1:是)
        $mod = new ExWarehouseModel();
        $result = $mod->getExWarehouseOrderDetail($exOrderId, $export);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-商品列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/imfzqa
     * */
    public function getExWarehouseGoods()
    {
        $this->isLogin();
        $requstParams = I();
        $params = array(
            'shop_keywords' => '',
            'pagetype' => '',
            'cat_id' => '',
            'examine_status' => '',
            'warehouse_date_start' => '',
            'warehouse_date_end' => '',
            'goods_keywords' => '',
            'relation_order_number' => '',
            'page' => 1,
            'pageSize' => 15,
            'export' => 0,
        );
        parm_filter($params, $requstParams);
        $mod = new ExWarehouseModel();
        $result = $mod->getExWarehouseGoods($params);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-单据审核
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/bc1g8n
     * */
    public function examineExWarehouseOrder()
    {
        $loginInfo = $this->isLogin();
        $exOrderId = (int)I('ex_order_id');
        $status = (int)I('status');//审核操作(-1:拒绝 1:审核通过)
        if (empty($exOrderId) || empty($status)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $mod = new ExWarehouseModel();
        $result = $mod->examineExWarehouseOrder($loginInfo, $exOrderId, $status);
        $this->ajaxReturn($result);
    }
}
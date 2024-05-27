<?php
/**
 * 出库管理
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-06
 * Time: 16:58
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\ExWarehouseModel;

class ExwarehouseAction extends BaseAction
{
    /**
     * 出库单-创建
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gs18rm
     * */
    public function addExWarehouseOrder()
    {
        $loginInfo = $this->MemberVeri();
        $billData = json_decode(htmlspecialchars_decode(I('bill_data')), true);//单据信息
        if (empty($billData)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参错误'));
        }
        $mod = new ExWarehouseModel();
        $billData['shopId'] = $loginInfo['shopId'];
        $billData['user_id'] = $loginInfo['user_id'];
        $billData['user_name'] = $loginInfo['user_username'];
        $billData['relation_order_number'] = '';
        $billData['relation_order_id'] = 0;
        $result = $mod->addExWarehouseOrder($billData);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-更新
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wq95n8
     * */
    public function updateExWarehouseOrder()
    {
        $loginInfo = $this->MemberVeri();
        $billData = json_decode(htmlspecialchars_decode(I('bill_data')), true);//单据信息
        if (empty($billData)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参错误'));
        }
        if (empty($billData['ex_order_id'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参错误-缺少参数ex_order_id'));
        }
        $mod = new ExWarehouseModel();
        $billData['shopId'] = $loginInfo['shopId'];
        $billData['user_id'] = $loginInfo['user_id'];
        $billData['user_name'] = $loginInfo['user_username'];
        $result = $mod->updateExWarehouseOrder($billData);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-单据列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wwh0vc
     * */
    public function getExWarehouseOrderList()
    {
        $loginInfo = $this->MemberVeri();
        $requstParams = I();
        $params = array(
            'shopId' => $loginInfo['shopId'],
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
     * 出库单-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mw5q47
     * */
    public function deleteExWarehouseOrder()
    {
        $this->MemberVeri();
        $exOrderIdStr = I('ex_order_id_str');//多个用英文逗号分隔
        if (empty($exOrderIdStr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $mod = new ExWarehouseModel();
        $result = $mod->deleteExWarehouseOrder($exOrderIdStr);
        $this->ajaxReturn($result);
    }

    /**
     * 出库单-单据详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ncuq1l
     * */
    public function getExWarehouseOrderDetail()
    {
        $this->MemberVeri();
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
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ipg2is
     * */
    public function getExWarehouseGoods()
    {
        $loginInfo = $this->MemberVeri();
        $requstParams = I();
        $params = array(
            'shopId' => $loginInfo['shopId'],
            'cat_id' => '',
            'pagetype' => '',
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
}
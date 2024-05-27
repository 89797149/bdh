<?php
/**
 * 入库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-23
 * Time: 19:07
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\WarehousingBillModel;

class WarehousingBillAction extends BaseAction
{
    /**
     * 入库单-添加
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qtyrxv
     * */
    public function addWarehousingBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'billType' => 2,//单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
            'billRemark' => '',
            'goods_data' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goods_data'] = json_decode(htmlspecialchars_decode($paramsReq['goods_data']), true);
        if (empty($paramsInput['goods_data'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择入库商品'));
        }
        $paramsInput['goodsData'] = $paramsInput['goods_data'];
        $mod = new WarehousingBillModel();
        $result = $mod->addWarehousingBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 入库单-单据列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ypc5sy
     * */
    public function getWarehousingBillList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'warehousingStatus' => '',//入库状态(0:未入库 1:已入库)
            'billType' => '',//单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
            'dateType' => 1,//日期类型(1:制单日期 2:入库日期)
            'billInputStatus' => '',//单据录入状态(0:未录入 1:部分录入 2:已录入)
            'dateStart' => '',//日期区间-开始日期
            'dateEnd' => '',//日期区间-结束日期
            'billNo' => '',//单号
            'creatorName' => '',//制单人
            'relationBillNo' => '',//关联单号
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new WarehousingBillModel();
        $result = $mod->getWarehousingBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 入库单-商品列表(入库查询)
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hdarwd
     * */
    public function getWarehousingBillGoodsList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'catid' => '',//当前门店分类id
            'billType' => '',//单据类型
            'warehousingTimeStart' => '',//入库日期区间-开始日期
            'warehousingTimeEnd' => '',//入库日期区间-结束日期
            'goodsKeywords' => '',//商品关键字
            'billNo' => '',//入库单号
            'export' => 0,//是否导出(0:否 1:是)
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new WarehousingBillModel();
        $result = $mod->getWarehousingBillGoodsList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 入库单-详情/导出
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wdvyow
     * */
    public function getWarehousingBillDetail()
    {
        $this->MemberVeri();
        $warehousingId = (int)I('warehousingId');
        if (empty($warehousingId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $export = (int)I('export');
        $keywords = (string)I('keywords');
        $mod = new WarehousingBillModel();
        $result = $mod->getWarehousingBillDetail($warehousingId, $keywords, $export);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 入库单-录入/编辑
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/np51nu
     * */
    public function inputWarehousingGoods()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'warehousingId' => 0,
            'billRemark' => '',
            'goods_data' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goods_data'] = json_decode(htmlspecialchars_decode($paramsInput['goods_data']), true);
        if (empty($paramsInput['warehousingId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少warehousingId'));
        }
        if (empty($paramsInput['goods_data'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少商品信息'));
        }
        $mod = new WarehousingBillModel();
        $result = $mod->inputWarehousingGoods($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 入库单-审核入库
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fflho2
     * */
    public function actionWarehouse()
    {
        $loginInfo = $this->MemberVeri();
        $warehousingId = (int)I('warehousingId');
        if (empty($warehousingId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少warehousingId'));
        }
        $mod = new WarehousingBillModel();
        $result = $mod->actionWarehouse($loginInfo, $warehousingId);
        $this->ajaxReturn($result);
    }

    /**
     * 入库单-打印-记录打印次数
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uwgi2c
     * */
    public function incWarehousingBillPrintNum()
    {
        $loginInfo = $this->MemberVeri();
        $warehousingId = (int)I('warehousingId');
        if (empty($warehousingId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少warehousingId'));
        }
        $mod = new WarehousingBillModel();
        $result = $mod->incWarehousingBillPrintNum($loginInfo, $warehousingId);
        $this->ajaxReturn($result);
    }
}
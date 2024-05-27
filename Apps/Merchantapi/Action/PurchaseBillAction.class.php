<?php
/**
 * 采购单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:28
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\BaseModel;
use Merchantapi\Model\PurchaseBillModel;

class PurchaseBillAction extends BaseAction
{
    /**
     * 采购单-添加采购单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vy7bem
     * */
    public function addPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'purchaserId' => 0,
            'supplierId' => 0,
            'plannedDeliveryDate' => 0,
            'billRemark' => '',
            'goods_data' => array()
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goods_data'] = (array)json_decode(htmlspecialchars_decode($paramsInput['goods_data']), true);
        $mod = new PurchaseBillModel();
        $result = $mod->addPurchaseBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 采购单-修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/euh6ry
     * */
    public function updatePurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'purchaseId' => 0,
            'purchaserId' => 0,
            'supplierId' => 0,
            'plannedDeliveryDate' => 0,
            'billRemark' => '',
            'goods_data' => array()
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['purchaseId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少purchaseId'));
        }
        $paramsInput['goods_data'] = (array)json_decode(htmlspecialchars_decode($paramsInput['goods_data']), true);
        $mod = new PurchaseBillModel();
        $result = $mod->updatePurchaseBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 采购单-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uy4wu2
     * */
    public function getPurchaseBillList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'billNo' => '',
            'goodsKeywords' => '',
            'billType' => '',//单据类型(1:市场自采 2:供应商直供)
            'purchaserId' => '',
            'supplierId' => '',
            'createDateStart' => '',
            'createDateEnd' => '',
            'plannedDeliveryDateStart' => '',
            'plannedDeliveryDateDEnd' => '',
            'purchaseStatus' => '',//采购状态(-1:关闭 0:待采购 1:部分收货 2:全部收货)
            'supplierConfirm' => '',//供货状态(0:未确认 1:已确认)
            'billFrom' => '',//单据来源(1:手动创建采购单 2:订单汇总生产采购单 3:预采购生成采购单 4:现场采购订单 5:采购任务生成采购单 6:采购任务生成采购单(联营))
            'export' => 0,//是否导出
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($paramsInput, $paramsReq);
        $model = new PurchaseBillModel();
        $result = $model->getPurchaseBillList($paramsInput);
        $this->ajaxReturn($result);
    }

    /*
     * 采购单-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nh27ew
     * */
    public function getPurchaseBillDetail()
    {
        $this->MemberVeri();
        $purchaseId = I('purchaseId');
        if (empty($purchaseId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $keywords = trim(I('keywords'));
        $export = (int)I('export');
        $mod = new PurchaseBillModel();
        $result = $mod->getPurchaseBillDetail($purchaseId, $keywords, $export);
        return $this->ajaxReturn(returnData($result));
    }

    /*
     * 采购单-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/uht2gt
     * */
    public function delPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $purchaseIdArr = json_decode(htmlspecialchars_decode(I('purchaseIdArr')), true);
        if (empty($purchaseIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要删除的数据'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->delPurchaseBill($loginInfo, $purchaseIdArr);
        return $this->ajaxReturn($result);
    }

    /*
     * 采购单-关闭
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ogve9z
     * */
    public function closePurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $purchaseIdArr = json_decode(htmlspecialchars_decode(I('purchaseIdArr')), true);
        if (empty($purchaseIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要关闭的数据'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->closePurchaseBill($loginInfo, $purchaseIdArr);
        return $this->ajaxReturn($result);
    }

    /**
     * 采购单-确认收货
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hh8i8x
     * */
    public function deliveryPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'purchaseId' => 0,
            'goods_data' => array()
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['purchaseId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少purchaseId'));
        }
        $paramsInput['goods_data'] = (array)json_decode(htmlspecialchars_decode($paramsInput['goods_data']), true);
        $mod = new PurchaseBillModel();
        $result = $mod->deliveryPurchaseBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 订单商品采购-商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/prqs14
     */
    public function getOrderGoodsPurchaseList()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $loginInfo['shopId'],
            'orderNo' => '',//订单号
            'ordertime_start' => '',//下单时间-开始时间
            'ordertime_end' => '',//下单时间-结束时间
            'require_date' => '',//期望送达日期
            'cat_id' => '',//店铺分类id
            'statusMark' => '',//订单状态【toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】,不传默认全部
            'page' => 1,
            'pageSize' => 15,
            'export' => 0,//是否导出(0:否 1:是)
            'onekeyPurchase' => 0,//是否是一键采购(0:否 1:是)
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new PurchaseBillModel();
        $result = $mod->getOrderGoodsPurchaseList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 订单商品采购-删除商品
     * 文档链接地址:
     * */
    public function delOrderGoodsPurchase()
    {
        $loginInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $skuId = (int)I('skuId');
        $purchaseGoodsWhere = json_decode(htmlspecialchars_decode(I('purchaseGoodsWhere')), true);//一键采购页的搜索条件
        if (empty($purchaseGoodsWhere)) {
            $purchaseGoodsWhere = array();
        }
        $purchaseGoodsWhere['shopId'] = $loginInfo['shopId'];
        if (empty($goodsId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->delOrderGoodsPurchase($goodsId, $skuId, $purchaseGoodsWhere);
        $this->ajaxReturn($result);
    }

    /**
     * 采购单-打印-记录打印次数
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cv24pz
     * */
    public function incPurchaseBillPrintNum()
    {
        $loginInfo = $this->MemberVeri();
        $purchaseId = (int)I('purchaseId');
        if (empty($purchaseId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-purchaseId'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->incPurchaseBillPrintNum($loginInfo, $purchaseId);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退回-验证采购单数据是否可用
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cgilwy
     * */
    public function verificationPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $purchaseId = (int)I('purchaseId');
        if (empty($purchaseId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择采购单数据'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->verificationPurchaseBill($purchaseId, $shopId);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退回-创建采购退回单据
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bqry8d
     * */
    public function addReturnPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'is_examine_submit' => 0,//审核提交(0:提交不审核 1:提交并审核)
            'purchaseId' => '',
            'returnBillRemark' => '',
            'goods_list' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['purchaseId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择采购单数据'));
        }
        $paramsInput['goods_list'] = json_decode(htmlspecialchars_decode($paramsInput['goods_list']), true);
        if (empty($paramsInput['goods_list'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要退货的商品'));
        }
        foreach ($paramsInput['goods_list'] as $item) {
            if (empty($item['id'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少id'));
            }
            if ((float)$item['returnGoodsNum'] <= 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货数量必须大于0'));
            }
            if ((float)$item['returnGoodsPrice'] < 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货单价异常'));
            }
        }
        $mod = new PurchaseBillModel();
        $result = $mod->addReturnPurchaseBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退回-修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gw0ly6
     * */
    public function updateReturnPurchaseBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'returnId' => 0,
            'is_examine_submit' => 0,//审核提交(0:提交不审核 1:提交并审核)
            'returnBillRemark' => '',
            'goods_list' => array(),
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['returnId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择退货单'));
        }
        $paramsInput['goods_list'] = json_decode(htmlspecialchars_decode($paramsInput['goods_list']), true);
        if (empty($paramsInput['goods_list'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要退货的商品'));
        }
        foreach ($paramsInput['goods_list'] as $item) {
            if (empty($item['id'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-缺少id'));
            }
            if ((float)$item['returnGoodsNum'] <= 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货数量必须大于0'));
            }
            if ((float)$item['returnGoodsPrice'] < 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货单价异常'));
            }
        }
        $mod = new PurchaseBillModel();
        $result = $mod->updateReturnPurchaseBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退货单-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ip0xxz
     * */
    public function getPurchaseReturnBillList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'billType' => '',//单据类型(1:市场自采 2:供应商直供) 不传值默认全部
            'purchaserId' => '',//采购员id
            'supplierId' => '',//供应商id
            'createDateStart' => '',//创建日期区间-开始日期
            'createDateEnd' => '',//创建日期区间-结束日期
            'returnBillNo' => '',//退货单号
            'purchaseBillNo' => '',//采购单号
            'export' => 0,//导出(0:不导出 1:导出)
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new PurchaseBillModel();
        $result = $mod->getPurchaseReturnBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 采购退货单-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tc5izr
     * */
    public function getPurchaseReturnBillDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $returnId = I('returnId');
        if (empty($returnId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $export = (int)I('export');
        $mod = new PurchaseBillModel();
        $result = $mod->getPurchaseReturnBillDetail($returnId, $export, $shopId);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 采购退货单-关闭
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/aynkx0
     * */
    public function closePurchaseReturnBill()
    {
        $loginInfo = $this->MemberVeri();
        $returnId = I('returnId');
        if (empty($returnId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->closePurchaseReturnBill($returnId, $loginInfo);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退货单-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/wpenm4
     * */
    public function delPurchaseReturnBill()
    {
        $loginInfo = $this->MemberVeri();
        $returnId = I('returnId');
        if (empty($returnId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->delPurchaseReturnBill($returnId, $loginInfo);
        $this->ajaxReturn($result);
    }

    /**
     * 采购退货单-列表-审核
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mpke6p
     * */
    public function examinePurchaseReturnBill()
    {
        $loginInfo = $this->MemberVeri();
        $returnId = I('returnId');
        if (empty($returnId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误'));
        }
        $mod = new PurchaseBillModel();
        $result = $mod->examinePurchaseReturnBill($returnId, $loginInfo);
        $this->ajaxReturn($result);
    }

}
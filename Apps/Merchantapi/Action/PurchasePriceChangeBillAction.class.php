<?php
/**
 * 成本调整单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-14
 * Time: 17:40
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\PurchasePriceChangeBillModel;

class PurchasePriceChangeBillAction extends BaseAction
{
    /**
     * 成本调整单-创建
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xk7br3
     * */
    public function addPurchasePriceChangeBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'changeBillRemark' => '',//单据备注
            'goods_list' => array(),//商品信息
        );
        parm_filter($paramsInput, $paramsReq);
        $paramsInput['goods_list'] = json_decode(htmlspecialchars_decode($paramsInput['goods_list']), true);
        if (empty($paramsInput['goods_list'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要调整成本价的商品'));
        }
        foreach ($paramsInput['goods_list'] as &$item) {
            if (!isset($item['skuId'])) {
                $item['skuId'] = 0;
            }
            if (!isset($item['goodsRemark'])) {
                $item['goodsRemark'] = '';
            }
            if (empty($item['goodsId'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-goodsId异常'));
            }
            if ((float)$item['nowPurchasePrice'] < 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '成本金额不能小于0'));
            }
        }
        unset($item);
        $mod = new PurchasePriceChangeBillModel();
        $result = $mod->addPurchasePriceChangeBill($paramsInput);
        $this->ajaxReturn($result);

    }

    /**
     * 成本调整单-修改
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tgwmsp
     * */
    public function updatePurchasePriceChangeBill()
    {
        $loginInfo = $this->MemberVeri();
        $paramsReq = I();
        $paramsInput = array(
            'loginInfo' => $loginInfo,
            'changeId' => '',
            'examine' => 0,
            'changeBillRemark' => '',//单据备注
            'goods_list' => array(),//商品信息
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['changeId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-changeId异常'));
        }
        $paramsInput['goods_list'] = json_decode(htmlspecialchars_decode($paramsInput['goods_list']), true);
        if (empty($paramsInput['goods_list'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要调整成本价的商品'));
        }
        foreach ($paramsInput['goods_list'] as &$item) {
            if (empty($item['id'])) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-id异常'));
            }
            if ((float)$item['nowPurchasePrice'] < 0) {
                $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '成本金额不能小于0'));
            }
            if (!isset($item['goodsRemark'])) {
                $item['goodsRemark'] = '';
            }
        }
        unset($item);
        $mod = new PurchasePriceChangeBillModel();
        $result = $mod->updatePurchasePriceChangeBill($paramsInput);
        $this->ajaxReturn($result);
    }

    /**
     * 成本调整单-列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xm5pbv
     * */
    public function getPurchasePriceChangeBillList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'createDateStart' => '',//单据日期区间-开始日期
            'createDateEnd' => '',//单据日期区间-结束日期
            'keywords' => '',//关键字(单号/制单人)
            'page' => 1,//页码
            'pageSize' => 1,//每页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new PurchasePriceChangeBillModel();
        $result = $mod->getPurchasePriceChangeBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 成本调整单-详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xg95b4
     * */
    public function getPurchasePriceChangeBillDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $changeId = (int)I('changeId');
        if (empty($changeId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-changeId'));
        }
        $mod = new PurchasePriceChangeBillModel();
        $result = $mod->getPurchasePriceChangeBillDetail($changeId, $shopId);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 成本调整单-删除
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/feuxlx
     * */
    public function delPurchasePriceChangeBill()
    {
        $loginInfo = $this->MemberVeri();
        $changeId = (int)I('changeId');
        if (empty($changeId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误-changeId'));
        }
        $mod = new PurchasePriceChangeBillModel();
        $result = $mod->delPurchasePriceChangeBill($changeId, $loginInfo);
        $this->ajaxReturn($result);
    }
}
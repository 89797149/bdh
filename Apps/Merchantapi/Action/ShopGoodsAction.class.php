<?php
/**
 * 重新创建一个商品类来写后续新增的业务逻辑
 * 门店商品
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-14
 * Time: 11:15
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\ShopGoodsModel;

class ShopGoodsAction extends BaseAction
{
    /*
     * 商品-现有商品库存列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gy24a2
     * */
    public function existingGoodsStockList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'catid' => '',
            'keywords' => '',//商品关键字(商品名/编码)
            'hideZeroStock' => 0,//是否隐藏0库存商品(0:不隐藏 1:隐藏)
            'purchase_type' => '',//采购类型(1:市场自采 2:供应商供货)
            'purchaser_or_supplier_id' => '',//(采购员/供应商)id
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
            'export' => 0,//导出(0:不导出 1:导出)
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new ShopGoodsModel();
        $result = $mod->existingGoodsStockList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 商品-现有库存-成本变更记录
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zzxl6x
     * */
    public function getGoodsPriceChangeList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'goodsId' => 0,
            'skuId' => 0,
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($paramsInput, $paramsReq);
        if (empty($paramsInput['goodsId'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择有效的商品'));
        }
        $mod = new ShopGoodsModel();
        $result = $mod->getGoodsPriceChangeList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

}
<?php
/**
 * 结算单(线上)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 13:07
 */

namespace Adminapi\Model;


use App\Modules\Settlement\SettlementBillModule;
use App\Modules\Shops\ShopsModule;

class SettlementBillModel extends BaseModel
{
    /**
     * 结算单-结算单列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string billNo 结算单号
     * -int settlementStatus 结算状态(0:未结算 1:已结算) 不传默认全部
     * -date createDateStart 申请结算日期-开始日期
     * -date createDateEnd 申请结算日期-结束日期
     * -date settlementDateStart 结算日期区间-开始日期
     * -date settlementDateEnd 结算日期区间-结束日期
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getSettlementBillList(array $paramsInput)
    {
        $module = new SettlementBillModule();
        $result = $module->getSettlementBillList($paramsInput);
        $shopModule = new ShopsModule();
        foreach ($result['root'] as &$item) {
            $shopDetail = $shopModule->getShopsInfoById($item['shopId'], 'shopName', 2);
            $item['shopName'] = (string)$shopDetail['shopName'];
        }
        unset($item);
        return $result;

    }

}
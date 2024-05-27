<?php
/**
 * 区域
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 15:38
 */

namespace Merchantapi\Model;


use App\Modules\PSD\RegionModule;

class RegionModel extends BaseModel
{

    /**
     * 区域-区域列表
     * @param int $shopId 门店id
     * @return array
     * */
    public function getRegionList(int $shopId)
    {
        $module = new RegionModule();
        $paransInput = array(
            'shopId' => $shopId,
        );
        $result = $module->getRegionList($paransInput);
        return $result;
    }
}
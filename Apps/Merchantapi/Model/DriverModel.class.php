<?php
/**
 * 司机
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-18
 * Time: 15:53
 */

namespace Merchantapi\Model;


use App\Modules\PSD\DriverModule;

class DriverModel extends BaseModel
{
    /**
     * 司机列表
     * @param int $shopId 门店id
     * @return array
     * */
    public function getDriverList(int $shopId)
    {
        $module = new DriverModule();
        $result = $module->getDriverDetailList($shopId, 'driverId,driverName');
        return $result;
    }
}
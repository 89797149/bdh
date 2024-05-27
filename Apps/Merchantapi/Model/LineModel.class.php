<?php
/**
 * 线路
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 15:38
 */

namespace Merchantapi\Model;


use App\Modules\PSD\LineModule;

class LineModel extends BaseModel
{

    /**
     * 线路-线路列表
     * @param int $shopId 门店id
     * @return array
     * */
    public function getLineList(int $shopId)
    {
        $module = new LineModule();
        $paransInput = array(
            'shop_id' => $shopId,
        );
        $result = $module->getLineList($paransInput);
        return $result;
    }
}
<?php

namespace App\Modules\FlashSale;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\Shops\ShopsModule;
use Think\Model;

/**
 * Class FlashSaleServiceModule
 * @package App\Modules\FlashSale
 * 限时服务类
 */
class FlashSaleServiceModule extends BaseModel
{
    /**
     * @param int $shop_id
     * @return array
     * 根据条件获取限时时间列表
     */
    public function getFlashSaleListById(array $params,string $field)
    {
        $flashSaleModule = new FlashSaleModule();
        $data = $flashSaleModule->getFlashSaleListById($params,$field);
        return $data;
    }

    /**
     * @param array $params
     * @param string $field
     * @return array
     * 根据条件获取限时时间详情
     */
    public function getFlashSaleDetailByParam(array $params,string $field)
    {
        $flashSaleModule = new FlashSaleModule();
        $data = $flashSaleModule->getFlashSaleDetailByParam($params,$field);
        return $data;
    }

    /**
     * @param array $params
     * @return array
     * 根据条件更新限时时间信息
     */
    public function editFlashSaleInfo(array $params)
    {
        $flashSaleModule = new FlashSaleModule();
        $data = $flashSaleModule->editFlashSaleInfo($params);
        return $data;
    }

    /**
     * @param int $id
     * @return mixed
     * 根据条件获取限时商品【注意参数加别名】
     */
    public function getFlashSaleGoods(array $params)
    {
        $flashSaleModule = new FlashSaleModule();
        $data = $flashSaleModule->getFlashSaleGoods($params);
        return $data;
    }
}
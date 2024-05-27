<?php

namespace App\Modules\Shops;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\Shops\ShopsModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 门店服务类
 * 统一提供给内部其他模块使用的收银类
 * Class PosServiceModule
 * @package App\Modules\Goods
 */
class ShopsServiceModule extends BaseModel
{

    /**
     * 根据门店id获取门店信息
     * @param int $shop_id 门店id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopsInfoById(int $shop_id, string $field)
    {
        $shops_module = new ShopsModule();
        $data = $shops_module->getShopsInfoById($shop_id, $field);
        return $data;
    }

    /**
     * @param int $shop_id
     * @param array $params
     * @return array
     * 根据店铺id更新店铺信息
     */
    public function editShopsInfo(int $shop_id, array $params)
    {
        $shops_module = new ShopsModule();
        $data = $shops_module->editShopsInfo($shop_id, $params);
        return $data;
    }

    /**
     * @param string $shopWords
     * @param string $field
     * @return array
     * 获取店铺列表【用于搜索下拉列表】
     */
    public function getSearchShopsList(string $shopWords,string $field)
    {
        $shops_module = new ShopsModule();
        $data = $shops_module->getSearchShopsList($shopWords,$field);
        return $data;
    }

    /**
     * 获取店铺配置
     * @param int $shop_id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopConfig(int $shop_id, $field = '*')
    {
        $shops_module = new ShopsModule();
        $data = $shops_module->getShopConfig($shop_id, $field);
        return $data;
    }

    /**
     * @param $userId
     * @param string $field
     * @return array
     * 根据门店绑定的用户id获取门店详情
     */
    public function getShopInfoByUserId($userId,$field = '*'){
        $shops_module = new ShopsModule();
        $data = $shops_module->getShopInfoByUserId($userId,$field);
        return $data;
    }
/**
     * 获取店铺一级分类
     * @param int $shop_id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopFirstClass(int $shop_id, $field = '*')
    {
        $shops_module = new ShopsModule();
        $data = $shops_module->getShopFirstClass($shop_id, $field);
        return $data;
    }

    /**
     * @return mixed
     * 获取【开启自动受理状态】【在营业时间】的店铺
     */
    public function getShopsList(){
        $response = LogicResponse::getInstance();
        $shops_module = new ShopsModule();
        $data = $shops_module->getShopsList();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }
}
<?php

namespace App\Modules\Areas;


use App\Models\BaseModel;

/**
 * Class AreasServiceModule
 * @package App\Modules\Users
 * 统一提供给内部其他模块使用的区域类
 */
class AreasServiceModule extends BaseModel
{
    /**
     * @return array
     * 获取区域列表【树形】
     */
    public function getAreasList()
    {
        $areasModule = new AreasModule();
        $data = $areasModule->getAreasList();
        return $data;
    }
}
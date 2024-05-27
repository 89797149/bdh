<?php

namespace App\Modules\Config;


use App\Models\BaseModel;

/**
 * Class SysConfigsServiceModule
 * @package App\Modules\Config
 * 统一提供给内部其他模块使用的区域类
 */
class SysConfigsServiceModule extends BaseModel
{
    /**
     * @param $parentId
     * @param $field
     * @return array
     * 获取总后台配置分类
     * 可根据所属类别ID获取
     */
    public function getConfigsList($parentId,$field)
    {
        $sysConfigsModule = new SysConfigsModule();
        $data = $sysConfigsModule->getConfigsList((int)$parentId,$field);
        return $data;
    }

    /**
     * @param array $params
     * @return array
     * 根据商城配置id更新所属类别ID
     */
    public function editConfigsInfo(array $params)
    {
        $sysConfigsModule = new SysConfigsModule();
        $data = $sysConfigsModule->editConfigsInfo($params);
        return $data;
    }
}
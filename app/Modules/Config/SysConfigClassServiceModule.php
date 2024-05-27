<?php

namespace App\Modules\Config;


use App\Models\BaseModel;

/**
 * Class SysConfigClassServiceModule
 * @package App\Modules\Config
 * 统一提供给内部其他模块使用的区域类
 */
class SysConfigClassServiceModule extends BaseModel
{
    /**
     * @return array
     * 获取总后台配置分类
     */
    public function getConfigClassList()
    {
        $sysConfigClassModule = new SysConfigClassModule();
        $data = $sysConfigClassModule->getConfigClassList();
        return $data;
    }

    /**
     * @param int $Id
     * @return array
     * 根据配置分类ID获取配置分类I详情
     */
    public function getConfigClassInfo(int $Id)
    {
        $sysConfigClassModule = new SysConfigClassModule();
        $data = $sysConfigClassModule->getConfigClassInfo($Id);
        return $data;
    }

    /**
     * @param array $params
     * @return array
     * 根据配置分类ID编辑排序
     */
    public function editConfigClassInfo(array $params)
    {
        $sysConfigClassModule = new SysConfigClassModule();
        $data = $sysConfigClassModule->editConfigClassInfo($params);
        return $data;
    }
}
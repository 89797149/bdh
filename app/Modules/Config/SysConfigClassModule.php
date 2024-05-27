<?php

namespace App\Modules\Config;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\SysConfigTypeModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class SysConfigClassModule
 * @package App\Modules\Config
 * 该类只为SysConfigClassServiceModule类服务
 */
class SysConfigClassModule extends BaseModel
{
    /**
     * @return array
     * 获取总后台配置分类
     */
    public function getConfigClassList()
    {
        $response = LogicResponse::getInstance();
        $sysConfigTypeModel = new SysConfigTypeModel();
        $where = [];
        $where['dataFlag'] = 1;
        $getConfigClassList = $sysConfigTypeModel->where($where)->order('sort asc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$getConfigClassList)->toArray();
    }

    /**
     * @param $Id
     * @return array
     * 根据分类id获取分类详情
     */
    public function getConfigClassInfo($Id)
    {
        $response = LogicResponse::getInstance();
        $sysConfigTypeModel = new SysConfigTypeModel();
        $where = [];
        $where['dataFlag'] = 1;
        $where['id'] = $Id;
        $getConfigClassList = $sysConfigTypeModel->where($where)->find();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($getConfigClassList)->setMsg('成功')->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据分类id更新数据
     */
    public function editConfigClassInfo($params)
    {
        $response = LogicResponse::getInstance();
        $sysConfigTypeModel = new SysConfigTypeModel();
        $where = [];
        $where['dataFlag'] = 1;
        $where['id'] = $params['id'];
        $save = [];
        $save['sort'] = null;
        $save['className'] = null;
        parm_filter($save,$params);
        $editConfigClassInfo = $sysConfigTypeModel->where($where)->save($save);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($editConfigClassInfo)->setMsg('成功')->toArray();
    }
}
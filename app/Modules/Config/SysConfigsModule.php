<?php

namespace App\Modules\Config;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\SysConfigsModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class SysConfigsModule
 * @package App\Modules\Config
 * 该类只为SysConfigsServiceModule类服务
 */
class SysConfigsModule extends BaseModel
{
    /**
     * @param int $parentId
     * @param string $field
     * @return array
     * 获取总后台配置分类
     * 可根据所属类别ID获取
     */
    public function getConfigsList($parentId = 0,$field = 'configId,parentId,fieldName')
    {
        $response = LogicResponse::getInstance();
        $sysConfigsModel = new SysConfigsModel();
        $where = [];
        $where['fieldType'] = ['neq',"hidden"];
        if(!empty($parentId)){
            $where['parentId'] = $parentId;
        }
        $getConfigsList = $sysConfigsModel->where($where)->field($field)->order('fieldSort desc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$getConfigsList)->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据商城配置id更新所属类别ID
     */
    public function editConfigsInfo($params)
    {
        $response = LogicResponse::getInstance();
        $sysConfigsModel = new SysConfigsModel();
        $where = [];
        $where['fieldType'] = ['neq',"hidden"];
        $where['configId'] = ['IN',$params['configId']];
        $editConfigClassInfo = $sysConfigsModel->where($where)->save(['parentId'=>$params['id']]);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($editConfigClassInfo)->setMsg('成功')->toArray();
    }
}
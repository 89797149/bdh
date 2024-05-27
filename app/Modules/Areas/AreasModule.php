<?php

namespace App\Modules\Areas;


use App\Enum\ExceptionCodeEnum;
use App\Models\AreasModel;
use App\Models\BaseModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class AreasModule
 * @package App\Modules\Areas
 * 该类只为AreasServiceModule类服务
 */
class AreasModule extends BaseModel
{
    /**
     * @return array
     * 获取区域列表【树形】
     */
    public function getAreasList()
    {
        $response = LogicResponse::getInstance();
        $areasModel = new AreasModel();
        $rest = $areasModel->where(['areaFlag' => 1, 'isShow' => 1])->select();
        $tree = [];
        $newData = [];
        //循环重新排列
        foreach ($rest as $datum) {
            $newData[$datum['areaId']] = $datum;
        }

        foreach ($newData as $key => $datum) {
            if ($datum['parentId'] > 0) {
                //不是根节点的将自己的地址放到父级的child节点
                $newData[$datum['parentId']]['children'][] = &$newData[$key];
            } else {
                //根节点直接把地址放到新数组中
                $tree[] = &$newData[$datum['areaId']];
            }
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($tree)->toArray();
    }

    /**
     * 获取地区详情
     * @param int areaId
     * @param string $field 表字段
     * @return array
     * */
    public function getAreaDetailById(int $areaId, $field = '*')
    {
        $model = new AreasModel();
        $detail = $model->where(array(
            'areaId' => $areaId,
            'areaFlag' => 1,
        ))->field($field)->find();
        return (array)$detail;
    }

    /**
     * 获取指定地区列表
     * @param int areaIdArr
     * @param string $field 表字段
     * @return array
     * */
    public function getAreaListByIdArr(array $areaIdArr, $field = '*')
    {
        $model = new AreasModel();
        $detail = $model->where(array(
            'areaId' => array('in', $areaIdArr),
            'areaFlag' => 1,
        ))->field($field)->select();
        return (array)$detail;
    }
}
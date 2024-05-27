<?php


namespace App\Modules\Inventory;

use App\Enum\ExceptionCodeEnum;
use App\Models\LocationModel;
use CjsProtocol\LogicResponse;
use App\Models\BaseModel;
use Think\Model;

//货位类
class LocationModule extends BaseModel
{
    /**
     * 根据父级id获取货位列表
     * @param int $shop_id 门店id
     * @param int $parent_id 父级id,默认为0:一级货位
     * @param string $field 表字段
     * @return array
     * */
    public function getLocationListById(int $shop_id, $parent_id = 0, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new LocationModel();
        $where = array(
            'shopId' => $shop_id,
            'parentId' => $parent_id,
            'lFlag' => 1,
        );
        $result = $model->where($where)->field($field)->order('sort desc')->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无货位')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 根据货位id获取货位详情
     * @param int $id 货位id
     * @param string $field 表字段
     * @return array
     * */
    public function getLocationInfoById(int $id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new LocationModel();
        $where = array(
            'lid' => $id,
            'lFlag' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无货位')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 获取货位列表-树状
     * @param int $shop_id 门店id
     * @param string $field 表字段
     * @return array
     * */
    public function getLocationListTree(int $shop_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new LocationModel();
        $where = array(
            'shopId' => $shop_id,
            'parentId' => 0,
            'lFlag' => 1,
        );
        $result = $model->where($where)->field($field)->order('sort desc')->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无货位')->toArray();
        }
        foreach ($result as &$item) {
            $item['child'] = array();
            $where = array(
                'parentId' => $item['lid'],
                'lFlag' => 1,
            );
            $child_list = $model->where($where)->field($field)->order('sort desc')->select();
            if (!empty($child_list)) {
                $item['child'] = $child_list;
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

}
<?php

namespace App\Modules\Shops;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\ShopCatsModel;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 门店分类
 * Class ShopCatsModule
 * @package App\Modules\ShopCats
 */
class ShopCatsModule extends BaseModel
{
    /**
     * 根据门店分类id获取分类详情
     * @param int $catId
     * @param string $field 表字段
     * @return array
     * */
    public function getShopCatInfoById(int $catId, $field = '*', $dataType = 1)
    {
        $response = LogicResponse::getInstance();
        $model = new ShopCatsModel();
        $result = $model->where(array(
            'catId' => $catId,
            'catFlag' => 1
        ))->field($field)->find();
        if (empty($result)) {
            if ($dataType == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($dataType == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        } else {
            return $result;
        }
    }

    /**
     * 根据门店分类名称获取分类详情
     * @param int $shopId 门店id
     * @param string $catName 分类名称
     * @param string $field 表字段
     * @return array
     * */
    public function getShopCatInfoByName(int $shopId, string $catName, $field = '*')
    {
        $model = new ShopCatsModel();
        $result = $model->where(array(
            'shopId' => $shopId,
            'catName' => $catName,
            'catFlag' => 1
        ))->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 根据门店分类id获取分类列表
     * @param string|array $catId 多个用英文逗号分隔
     * @param string $field 表字段
     * @param int $data_type 返回数据格式(1:data格式 2:直接返回结果集)
     * @return array
     * */
    public function getShopCatListById($catId, $field = '*', $data_type = 1)
    {
        if (is_array($catId)) {
            $catId = implode(',', $catId);
        }
        $response = LogicResponse::getInstance();
        $model = new ShopCatsModel();
        $result = $model->where(array(
            'catId' => array('IN', $catId),
            'catFlag' => 1
        ))->field($field)->select();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        } else {
            return (array)$result;
        }
    }

    /**
     * 获取门店一级分类
     * @param int $shop_id
     * @return array
     * */
    public function getShopFirstClass(int $shop_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new ShopCatsModel();
        $result = $model->where(array(
            'shopId' => $shop_id,
            'parentId' => 0,
            'catFlag' => 1
        ))->field($field)->order('catSort asc')->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 店铺分类-保存分类信息
     * @param array $params
     * -int catId 分类id
     * -int shopId 门店id
     * -int parentId 父级id
     * -int isShow 是否显示(0:隐藏 1:显示)
     * -string catName 分类名称
     * -int catSort 排序号
     * -int catFlag 删除标志(1:有效 -1:删除)
     * -string icon 图标
     * -string typeImg 长图
     * -int isShowIndex 是否显示在首页（-1：否 1：是）
     * -string describe 简介
     * -float distributionLevel1Amount 分销-一级佣金设置
     * -float distributionLevel1Amount 分销-二级佣金设置
     * @return int catId
     * */
    public function saveShopCat(array $params)
    {
        $save_params = array(
            'shopId' => null,
            'parentId' => null,
            'isShow' => null,
            'catName' => null,
            'catSort' => null,
            'catFlag' => null,
            'icon' => null,
            'typeImg' => null,
            'isShowIndex' => null,
            'describe' => null,
            'distributionLevel1Amount' => null,
            'distributionLevel2Amount' => null,
        );
        parm_filter($save_params, $params);
        $model = new ShopCatsModel();
        if (empty($params['catId'])) {
            $catId = $model->add($save_params);
            if (empty($catId)) {
                return 0;
            }
        } else {
            $save_res = $model->where(array(
                'catId' => $params['catId']
            ))->save($save_params);
            if ($save_res === false) {
                return 0;
            }
            $catId = $params['catId'];
        }
        return (int)$catId;
    }
}
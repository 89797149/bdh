<?php

namespace App\Modules\FlashSale;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\FlashSaleModel;
use App\Models\GoodsTimeSnappedModel;
use App\Models\ShopsModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;

/**
 * Class FlashSaleModule
 * @package App\Modules\FlashSale
 * 限时类
 */
class FlashSaleModule extends BaseModel
{
    /**
     * @param int $shop_id
     * @return array
     * 根据店铺id获取限时时间列表
     */
    public function getFlashSaleListById($params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $flashSaleModel = new FlashSaleModel();
        $where = [];
        $where['shopId'] = null;
        $where['startTime'] = null;
        $where['endTime'] = null;
        $where['isDelete'] = 0;
        parm_filter($where, $params);
        $result = $flashSaleModel->where($where)->field($field)->order('id desc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$result)->setMsg('成功')->toArray();
    }

    /**
     * @param array $params
     * @param string $field
     * @return array
     * 根据条件获取限时时间详情
     */
    public function getFlashSaleDetailByParam(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $flashSaleModel = new FlashSaleModel();
        $where = [];
        $where['id'] = null;
        $where['shopId'] = null;
        $where['startTime'] = null;
        $where['endTime'] = null;
        $where['isDelete'] = 0;
        parm_filter($where, $params);
        $result = $flashSaleModel->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * @param array $params
     * @return array
     * 根据条件更新限时时间信息
     */
    public function editFlashSaleInfo(array $params)
    {
        $response = LogicResponse::getInstance();
        $flashSaleModel = new FlashSaleModel();
        $where = [];
        $where['id'] = null;
        $where['shopId'] = null;
        $where['isDelete'] = 0;
        parm_filter($where, $params);
        $where['isDelete'] = 0;
        unset($params['id'], $params['shopId']);
        $result = $flashSaleModel->where($where)->save($params);
//        if(empty($result)){
//            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
//        }
        if ($result === false) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('操作失败')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * @param $params
     * @return array
     * 根据条件获取限时商品【注意参数加别名】
     */
    public function getFlashSaleGoods($params)
    {
        $response = LogicResponse::getInstance();
        $goodsTimeSnappedModel = new GoodsTimeSnappedModel();
        $where = [];
        $where['fs.id'] = null;
        $where['fs.shopId'] = null;
        $where['fs.isDelete'] = 0;
        $where['gts.dataFlag'] = 1;
        $where['goods.goodsFlag'] = 1;
        $where['goods.goodsStatus'] = 1;
        parm_filter($where, $params);
        $result = $goodsTimeSnappedModel->alias('gts')
            ->join('left join wst_flash_sale fs on fs.id = gts.flashSaleId and fs.shopId = gts.shopId')
            ->join('left join wst_goods goods on goods.goodsId = gts.goodsId')
            ->where($where)
            ->field('gts.*,goods.goodsName')
            ->group('tsId')
            ->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$result)->setMsg('成功')->toArray();
    }
}
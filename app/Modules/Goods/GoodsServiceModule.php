<?php

namespace App\Modules\Goods;


use App\Enum\ExceptionCodeEnum;
use App\Modules\Users\UsersModule;
use App\Models\BaseModel;
use Think\Model;

/**
 * 统一提供给内部其他模块使用的商品类
 * Class GoodsServiceModule
 * @package App\Modules\Goods
 */
class GoodsServiceModule extends BaseModel
{
    /**
     * 根据商品id获取商品详情
     * @param int $goods_id
     * @param string $field 表字段
     * @return array
     * */
    public function getGoodsInfoById(int $goods_id, $field = '*')
    {
        $module = new GoodsModule();
        $data = $module->getGoodsInfoById($goods_id, $field);
        return $data;
    }

    /**
     * 验证限量购商品有效性 PS:属于原有逻辑后加,这里不验证用户购买库存,如需验证用户购买库存请调用原有公共函数goodsTimeLimit
     * @param int goods_id 商品id
     * @param float $goodsCnt 购买数量/重量
     * @return array
     * */
    public function verificationLimitGoodsBuyLog(int $goods_id, float $number, $debug = 2)
    {
        $module = new GoodsModule();
        $users_module = new UsersModule();
        $users_result = $users_module->getUsersInfoByMemberToken();
        $users_id = 0;
        if ($users_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $users_id = $users_result['data']['userId'];
        }
        $data = $module->verificationLimitGoodsBuyLog($users_id, $goods_id, $number, $debug);
        return $data;
    }

    /**
     * 添加商品限量购购买记录
     * @param int $users_id
     * @param array $params <p>
     * int goodsId 商品id
     * int number 购买数量
     * </p>
     * @param object $trans 用于事务
     * @return array
     * */
    public function addLimitGoodsBuyLog(int $users_id, array $params, $trans = null)
    {
        $module = new GoodsModule();
        $data = $module->addLimitGoodsBuyLog($users_id, $params, $trans);
        return $data;
    }

    /**
     * 返还商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param float $stock 库存
     * @param int $stock_type 库存类型(1:库房库存 2:售卖库存)
     * @return array
     * */
    public function returnGoodsStock(int $goods_id, int $sku_id, float $stock, $goods_type = 1, $stock_type = 1, $trans = null)
    {
        $module = new GoodsModule();
        $data = $module->returnGoodsStock($goods_id, $sku_id, $stock, $goods_type, $stock_type, $trans);
        return $data;
    }

    /**
     * 扣除商品库存
     * @param int $goods_id 商品id
     * @param int $sku_id skuId
     * @param float $stock 库存
     * @param int $goods_type 需要扣除的库存类型(1:普通商品 2:限量商品 3:限时商品) PS：以用户最终下单的类型为准,不要和商品基本信息中的类型混为一谈
     * @param int $stock_type 库存类型(1:库房库存 2:售卖库存)
     * @param object $trans
     * @return array
     * */
    public function deductionGoodsStock(int $goods_id, int $sku_id, float $stock, $goods_type = 1, $stock_type = 1, $trans = null)
    {
        $module = new GoodsModule();
        $data = $module->deductionGoodsStock($goods_id, $sku_id, $stock, $goods_type, $stock_type, $trans);
        return $data;
    }

    /**
     * 根据skuId获取sku信息详情
     * */
    public function getSkuSystemInfoById(int $skuId)
    {
        $module = new GoodsModule();
        $data = $module->getSkuSystemInfoById($skuId);
        return $data;
    }

    /**
     * @param int $goodsId
     * @return mixed
     * 根据商品id获取店铺商品分类和商城分类
     */
    public function getGoodsCat(int $goodsId)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsCat($goodsId);
        return $data;
    }

    /**
     * @param string $shopCatIds
     * @return array
     * 根据店铺商品分类ID获取分类列表
     */
    public function getShopCatList(string $shopCatIds)
    {
        $module = new GoodsModule();
        $data = $module->getShopCatList($shopCatIds);
        return $data;
    }

    /**
     * @param string $goodsCatIds
     * @return array
     * 根据商品分类ID获取分类列表
     */
    public function getGoodsCatList(string $goodsCatIds)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsCatList($goodsCatIds);
        return $data;
    }

    /**
     * @param int $pid
     * @return mixed
     * 根据商品分类pid获取分类列表
     */
    public function getGoodsCatListPid(int $pid)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsCatListPid($pid);
        return $data;
    }

    /**
     * @param int $goodsId
     * @return array
     * 根据商品ID或商品相册
     */
    public function getGoodsGalleryList(int $goodsId)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsGalleryList($goodsId);
        return $data;
    }

    /**
     * @param int $goodsId
     * @return array
     * 根据商品ID获取商品sku信息
     */
    public function getGoodsSku(int $goodsId)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsSku($goodsId);
        return $data;
    }

    /**
     * @param array $param
     * catId 自增ID
     * 修改内容
     * @return mixed
     * 根据商城分类ID修改信息
     */
    public function editGoodsCatInfo(array $param)
    {
        $module = new GoodsModule();
        $data = $module->editGoodsCatInfo($param);
        return $data;
    }

    /**
     * @param int $catId
     * @return array
     * 获取商城分类信息
     */
    public function getGoodsCatInfo(int $catId)
    {
        $module = new GoodsModule();
        $data = $module->getGoodsCatInfo($catId);
        return $data;
    }

    /**
     * @param array $param
     * @return array
     * 根据商品id更新数据
     */
    public function editGoodsInfo(array $param)
    {
        $module = new GoodsModule();
        $data = $module->editGoodsInfo($param);
        return $data;
    }
}
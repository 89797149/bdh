<?php

namespace App\Modules\Shops;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\PrintsModel;
use App\Models\ShopCatsModel;
use App\Models\ShopConfigsModel;
use App\Models\ShopsModel;
use App\Modules\Users\UsersModule;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;

/**
 * 门店类
 * Class ShopsModule
 * @package App\Modules\Shops
 */
class ShopsModule extends BaseModel
{
    /**
     * 根据门店id获取门店详情
     * @param int $shop_id
     * @param string $field 表字段
     * @param int data_type (1:返回data格式 2:直接返回结果集) PS:主要为了兼容之前的程序
     * @return array
     * */
    public function getShopsInfoById(int $shop_id, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $shops_model = new ShopsModel();
        $result = $shops_model->where(array(
            'shopId' => $shop_id,
            'shopFlag' => 1,
        ))->field($field)->find();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关店铺数据')->toArray();
            } else {
                return array();
            }
        }
        $user_module = new UsersModule();
        $users_result = $user_module->getUsersDetailById($result['userId']);
        $users_detail = $users_result['data'];
        $result['loginName'] = (string)$users_detail['loginName'];
        $result['userPhone'] = (string)$users_detail['userPhone'];
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        } else {
            return (array)$result;
        }
    }


    /**
     * @param int $shop_id
     * @param array $params
     * @return array
     * 根据店铺id更新店铺信息
     */
    public function editShopsInfo(int $shop_id, array $params)
    {
        $response = LogicResponse::getInstance();
        $shops_model = new ShopsModel();
        $result = $shops_model->where(array(
            'shopId' => $shop_id,
            'shopFlag' => 1,
        ))->save($params);
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setData($result)->setMsg('变更店铺信息失败，请重新尝试')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * @param int $shopWords
     * @param string $field
     * @return array
     * 获取店铺列表【用于搜索下拉列表】
     */
    public function getSearchShopsList($shopWords = 0, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $shops_model = new ShopsModel();
        $where = "shopFlag = 1 ";
        if (!empty($shopWords)) {
            $where .= " and (shopName like '%{$shopWords}%' or shopSn like '%{$shopWords}%') ";
        }
        $result = $shops_model->where($where)->field($field)->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$result)->setMsg('成功')->toArray();
    }

    /**
     * 获取门店配置
     * @param int $shop_id
     * @param string $field 表字段
     * @param int $data_format (1:返回data格式 2:直接返回结果) PS:主要是兼容之前的程序
     * @return array
     * */
    public function getShopConfig(int $shop_id, $field = '*', $data_format = 1)
    {
        $response = LogicResponse::getInstance();
        $table = new ShopConfigsModel();
        $result = $table->where(array(
            'shopId' => $shop_id
        ))->field($field)->find();
        if ($data_format == 1) {
            if (empty($result)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关店铺数据')->toArray();
            }
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
        } else {
            return (array)$result;
        }
    }

    /**
     * @param $userId
     * @param $field
     * @return array
     * 根据门店绑定的用户id获取门店详情
     */
    public function getShopInfoByUserId($userId, $field)
    {
        $response = LogicResponse::getInstance();
        $shops_model = new ShopsModel();
        $result = $shops_model->where(array(
            'userId' => $userId,
            'shopFlag' => 1,
        ))->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关店铺数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 获取门店一级分类
     * @param int $shop_id
     * @param array $field 表字段
     * @return array
     * */
    public function getShopFirstClass(int $shop_id, $field = '*')
    {
        $module = new ShopCatsModule();
        return $module->getShopFirstClass($shop_id, $field);
    }

    /**
     * @return mixed
     * 获取【开启自动受理状态】【在营业时间】的店铺
     */
    public function getShopsList()
    {
        $shops_model = new ShopsModel();
        $dateTime = date('H');
        $where = array();
        $where['config.isReceipt'] = 1;//是否自动接单(1：是 -1：否)
        $where['shop.shopFlag'] = 1;
        $where['shop.serviceStartTime'] = ['elt', $dateTime];//开始营业时间
        $where['shop.serviceEndTime'] = ['gt', $dateTime];//结束营业时间
        $where['shop.shopAtive'] = 1;//店铺营业状态(1:营业中 0：休息中)
        $field = "shop.shopId";
        $result = $shops_model->alias('shop')
            ->join('left join wst_shop_configs as config on config.shopId = shop.shopId')
            ->where($where)
            ->field($field)
            ->select();
        return (array)$result;
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取店铺打印机配置列表
     */
    public function getPrintsList($shopId)
    {
        $printsModel = new PrintsModel();
        $where = [];
        $where['dataFlag'] = 1;//是否自动接单(1：是 -1：否)
        $where['shopId'] = $shopId;//门店id
        $result = $printsModel->where($where)->select();
        return $result;
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取默认打印机详情
     */
    public function getPrintInfo($shopId)
    {
        $printsModel = new PrintsModel();
        $where = [];
        $where['isDefault'] = 1;//是否默认【0:否|1:默认】
        $where['dataFlag'] = 1;//是否自动接单(1：是 -1：否)
        $where['shopId'] = $shopId;//门店id
        $result = $printsModel->where($where)->find();
        return $result;
    }

    /**
     * 门店是否开启限时秒杀仅自提
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function isOpenTimeLimitSelf(int $shop_id)
    {
        //open_time_limit_self开启限时秒杀仅自提(0:不开启 1:开启)
        $shop_config_detail = $this->getShopConfig($shop_id, 'open_time_limit_self', 2);
        if ($shop_config_detail['open_time_limit_self'] != 1) {
            return false;
        }
        return true;
    }

    /**
     * 门店是否开启限量商品仅自提
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function isOpenLimitNumSelf(int $shop_id)
    {
        //open_limit_num_self 开启限量商品仅自提(0:不开启 1:开启)
        $shop_config_detail = $this->getShopConfig($shop_id, 'open_limit_num_self', 2);
        if ($shop_config_detail['open_limit_num_self'] != 1) {
            return false;
        }
        return true;
    }

    /**
     * 门店是否限制限时秒杀不享受优惠券(0:不限制 1:限制)
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function isTimeLimitNocoupons(int $shop_id)
    {
        //time_limit_nocoupons 是否限制限时秒杀不享受优惠券(0:不限制 1:限制)
        $shop_config_detail = $this->getShopConfig($shop_id, 'time_limit_nocoupons', 2);
        if ($shop_config_detail['time_limit_nocoupons'] != 1) {
            return false;
        }
        return true;
    }

    /**
     * 门店是否限制限量商品不享受优惠券(0:不限制 1:限制)
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function isLimitNumNocoupons(int $shop_id)
    {
        //limit_num_nocoupons  是否限制限量商品不享受优惠券(0:不限制 1:限制)
        $shop_config_detail = $this->getShopConfig($shop_id, 'limit_num_nocoupons', 2);
        if ($shop_config_detail['limit_num_nocoupons'] != 1) {
            return false;
        }
        return true;
    }

    /**
     * 门店列表-门店id获取
     * @param array $shopIdArr 门店id
     * @param string $field 表字段
     * @return array
     * */
    public function getShopListByShopId(array $shopIdArr, $field = '*')
    {
        $shopIdArr = array_unique($shopIdArr);
        $model = new ShopsModel();
        $where = array(
            'shopFlag' => 1,
            'shopId' => array('IN', $shopIdArr),
        );
        $res = $model->where($where)->field($field)->select();
        return (array)$res;
    }

    /**
     * 店铺配置-快捷修改
     * @param array $paramsInput
     * wst_shop_configs表字段
     * @return bool
     * */
    public function quickUpdateConfig(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $saveParams = array(
            'shopId' => null,
            'shopTitle' => null,
            'shopKeywords' => null,
            'shopDesc' => null,
            'shopBanner' => null,
            'shopAds' => null,
            'shopAdsUrl' => null,
            'appMiaosha' => null,
            'appYushou' => null,
            'AppRenqi' => null,
            'appMiaoshaSRC' => null,
            'appYushouSRC' => null,
            'AppRenqiSRC' => null,
            'appTypeimg' => null,
            'appTypeimg2' => null,
            'appTypeimg3' => null,
            'isSorting' => null,
            'sorting_threshold' => null,
            'isReceipt' => null,
            'isDistributionBasket' => null,
            'sortingType' => null,
            'isMainWarehouse' => null,
            'mainWarehouseUsername' => null,
            'mainWarehousePwd' => null,
            'mainWarehouseUserId' => null,
            'sortingAutoDelivery' => null,
            'overDeliveryLimit' => null,
            'deliveryLatLngLimit' => null,
            'relateAreaIdLimit' => null,
            'addShopTeamDesc' => null,
            'addShopTeamPic' => null,
            'isCountWeightG' => null,
            'isDistributionSorter' => null,
            'cashOnDelivery' => null,
            'cashOnDeliveryCoupon' => null,
            'cashOnDeliveryScore' => null,
            'cashOnDeliveryMemberCoupon' => null,
            'open_suspension_chain' => null,
            'open_time_limit_self' => null,
            'time_limit_nocoupons' => null,
            'limit_num_nocoupons' => null,
            'advance_print_time' => null,
            'whetherPurchase' => null,
            'whetherPurchase' => null,
            'whetherMathStock' => null,
            'whetherMathNoWarehouse' => null,
        );
        parm_filter($saveParams, $paramsInput);
        $model = new ShopConfigsModel();
        $result = $model->where(array('shopId' => $shopId))->save($saveParams);
        if ($result === false) {
            return false;
        }
        return true;
    }

    /**
     * 获取配送范围内的店铺
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return array
     * */
    public function getCanUseShopByLatLng(float $lat, float $lng)
    {
        if (empty($lat) || empty($lng)) {
            return array();
        }
        $shopModel = new ShopsModel();
        $where = array(
            'shops.shopFlag' => 1,
            'shops.shopStatus' => 1,
            'shops.shopAtive' => 1
        );
        $where['users.userFlag'] = 1;
        $field = "shops.*";
        $resData = $shopModel
            ->alias("shops")
            ->join("left join wst_users users on users.userId=shops.userId")
            ->where($where)
            ->field($field)
            ->select();
        foreach ($resData as $key => $val) {
            $verfRes = $this->inShopByLatLng($val["shopId"], $lat, $lng);
            if (!$verfRes) {
                unset($resData[$key]);
            }
        }
        if (empty($resData)) {
            return array();
        }
        return array_values($resData);
    }

    /**
     * 店铺配送范围-校验经纬度是否在店铺配送范围内
     * @param int $shopId 店铺id
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return bool
     * */
    public function inShopByLatLng(int $shopId, float $lat, float $lng)
    {
        $field = "deliveryLatLng";
        $shopRow = $this->getShopsInfoById($shopId, $field, 2);
        if (empty($shopRow)) {
            return false;
        }
        $deliveryLatlng = htmlspecialchars_decode($shopRow['deliveryLatLng']);
        $pts = json_decode($deliveryLatlng, true);
        foreach ($pts as $data) {
            if (!empty($data['M'])) {
                $lng_M = $data['M'];
            } else {
                $lng_M = $data['lng'];
            }
            if (!empty($data['O'])) {
                $lat_O = $data['O'];
            } else {
                $lat_O = $data['lat'];
            }
            $lnglat_arr[] = array('lng' => $lng_M, 'lat' => $lat_O);
        }
        if (!$pts || !is_array($pts)) {
            return false;
        }
        $point = [
            'lng' => $lng,
            'lat' => $lat,
        ];
        return is_point_in_polygon($point, $lnglat_arr);
    }
}
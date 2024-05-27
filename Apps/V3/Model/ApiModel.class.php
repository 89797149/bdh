<?php

namespace V3\Model;

use App\Enum\ExceptionCodeEnum;
use App\Enum\Sms\SmsEnum;
use App\Enum\Users\UsersEnum;
use App\Modules\Areas\AreasModule;
use App\Modules\Coupons\CouponsModule;
use App\Modules\DeliveryTime\DeliveryTimeModule;
use App\Modules\Disribution\DistributionModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Log\LogSmsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Map\MapModule;
use App\Modules\Message\MessageModule;
use App\Modules\Notify\NotifyModule;
use App\Modules\Orders\CartModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Pay\PayModule;
use App\Modules\PSD\DriverModule;
use App\Modules\Rank\RankModule;
use App\Modules\Repayment\RepaymentModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\Sorting\SortingModule;
use App\Modules\Users\UsersModule;
use function CjsConsole\str_contains;
use CjsProtocol\LogicResponse;
use App\Modules\Users\UsersServiceModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Models\UsersModel;
use App\Models\ShopsModel;
use Symfony\Component\DependencyInjection\Tests\Compiler\I;
use App\Modules\Log\LogServiceModule;
use Think\Model;
use App\Modules\Rank\RankServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * Api服务类
 */
class ApiModel extends BaseModel
{

    /**
     *Geocoding API  经纬度转换为详细地址 num为0 1 是否显示周边 默认显示周边
     *coordtype  bd09ll百度坐标 wgs84ll火星坐标
     *数据类型 json xml
     */
    public function getlat_lng($lat, $lng, $num = 0, $coordtype = 'wgs84ll')
    {
        $ak = $GLOBALS["CONFIG"]["baiduMap_ak"];
        $url = "http://api.map.baidu.com/geocoder/v2/?";
        $bd_en_res = bd_encrypt($lng, $lat);
        $curr_lat = $bd_en_res['bd_lat'];
        $curr_lng = $bd_en_res['bd_lon'];
        $ret = file_get_contents($url . "ak={$ak}&location={$curr_lat},{$curr_lng}&output=json&pois={$num}&latest_admin=1&coordtype=$coordtype");
        $result = json_decode($ret, true);
        if ($result['status'] == 0) {
            $areaData = $result['result']['addressComponent'];
            $areaTab = M('areas');
            $provinceCode = $areaTab->where(['areaName' => $areaData['province']])->getField('areaId');
            $cityCode = $areaTab->where(['areaName' => $areaData['city']])->getField('areaId');
            $areaCode = $areaTab->where(['areaName' => $areaData['district']])->getField('areaId');
            if (is_null($provinceCode)) {
                $provinceCode = '';
            }
            if (is_null($cityCode)) {
                $cityCode = '';
            }
            if (is_null($areaCode)) {
                $areaCode = '';
            }
            $areaData['provinceCode'] = $provinceCode;
            $areaData['cityCode'] = $cityCode;
            $areaData['areaCode'] = $areaCode;
            $result['result']['addressComponent'] = $areaData;
//            $result['result']['location']['lng'] = $lng;
//            $result['result']['location']['lat'] = $lat;
            $pois = $result["result"]["pois"];
            foreach ($pois as &$row) {
                $point = $row["point"];
                if (empty($point['x']) || empty($point['y'])) {
                    continue;
                }
                $bd_res = bd_decrypt($point['x'], $point['y']);
                $point['y'] = $bd_res['gg_lat'];
                $point['x'] = $bd_res['gg_lon'];
                $row["point"] = $point;
            }
            unset($row);
            $result["result"]["pois"] = $pois;
        }

        return $result;
    }

    /**
     * API获取店铺广告
     */
    public function getShopAds($shopId)
    {
        $data = M('shop_configs')->where('shopId = ' . $shopId)->field('shopAds,shopAdsUrl')->find();
        $data['shopAds'] = explode('#@#', $data['shopAds']);
        $data['shopAdsUrl'] = explode('#@#', $data['shopAdsUrl']);
        for ($i = 0; $i < count($data['shopAds']); $i++) {
            $res[$i]['shopAds'] = $data['shopAds'][$i];
            $res[$i]['goodsId'] = $data['shopAdsUrl'][$i];
        }

        return $res;
    }

    /**
     * API获取广告
     */
    public function getAds($areaId2, $adType)
    {
        $today = date("Y-m-d");
        $data = S('APP_CACHE_ADS_' . $areaId2 . "_" . $adType);
        if (!$data) {
            //获取所在省份
            $sql = "select parentId from __PREFIX__areas where areaFlag=1 and areaId=" . $areaId2;
            $areaId1 = $this->queryRow($sql);
            $areaId1 = (int)$areaId1['parentId'];
            /*$sql = "select adId,adName,adURL,adFile,appBannerFile,appBannerFileShop,appBannerFileGood,content from __PREFIX__ads WHERE (areaId2 = $areaId2 or areaId1 = 0 or (areaId1=".$areaId1." and areaId2=0))
						AND adStartDate<='$today' AND adEndDate >='$today' and adPositionId=".$adType." order by adSort asc";*/
            $sql = "select adId,adName,adURL,adFile,appBannerFile,appBannerFileShop,appBannerFileGood,content from __PREFIX__ads WHERE (areaId2 = $areaId2 or areaId1 = 0 or areaId2=" . $areaId1 . ")
						AND adStartDate<='$today' AND adEndDate >='$today' and adPositionId=" . $adType . " order by adSort asc";
            $data = $this->query($sql);
            S('APP_CACHE_ADS_' . $areaId2 . "_" . $adType, $data, C("allApiCacheTime"));
        }
        return returnData((array)$data);
    }

    /* api 获取附近的商家
	*/
    public function getDistricts($areaId3, $page = 1, $lat, $lng)
    {
        $data = S("getDistricts_" . $areaId3 . '_' . $page);
        $pageDataNum = 10;
        if (empty($data)) {

            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;

            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $areaId3;

            $mod = M("shops")->where($where)
                ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
                ->join("LEFT JOIN wst_users  ON wst_users.userId=wst_shops.userId")
                ->field("wst_shops.*,wst_users.userName")
                ->group('wst_shops.shopId')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            if (count($mod) <= 0) {
                //后来新加,没有获取到附近店铺的时候,随便获取一条可用的店铺数据即可
                /*unset($where['wst_shops_communitys.areaId3']);
                $mod[] = M("shops")->where($where)
                    ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
                    ->order('rand()')
                    ->find();*/

                //$lat = '31.23888';
                //$lng = '121.520828';
                if ($lat == 'undefined') {
                    $lat = '';
                }
                if ($lng == 'undefined') {
                    $lng = '';
                }
                //获取到后台设置的默认城市,拿到经纬度即可,不必验证店铺状态
                if (empty($lat) || empty($lng)) {
                    $defaultCity = M('sys_configs')->where(['fieldCode' => 'defaultCity'])->getField('fieldValue');
                    $shopRelationWhere['areaId2'] = $defaultCity;
                    $shopRelation = M('shops')->where(['areaId2' => $defaultCity])->field('latitude,longitude')->find();
                    if ($shopRelation) {
                        $lat = $shopRelation['latitude'];
                        $lng = $shopRelation['longitude'];
                    }
                }
                //默认城市没有获取到相关的店铺经纬度信息就随机取一条店铺的经纬度数据
                if (empty($lat) || empty($lng)) {
                    $shopRelation = M('shops')->where("shopFlag=1 and latitude != '' and longitude != '' ")->field('latitude,longitude')->find();
                    if ($shopRelation) {
                        $lat = $shopRelation['latitude'];
                        $lng = $shopRelation['longitude'];
                    }
                }
                // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $newTime . "' and s.serviceEndTime >= '" . $newTime . "' ";
                $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 ";

                $sql = "select s.*,u.userName,(select count('g.goodsId') from __PREFIX__goods g where g.shopId=s.shopId and g.goodsFlag=1 and g.goodsStatus=1 and g.isSale=1 ) as goodsCount,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s left join wst_shops_communitys sc on s.shopId=sc.shopId left join __PREFIX__users u on u.userId=s.userId " . $where . " order by distance";
                $goodsList = $this->query($sql);
                foreach ($goodsList as $val) {
                    if ($val['goodsCount'] > 0) {
                        $mod[] = $val;
                        break;
                    }
                }
            }
            S("getDistricts_" . $areaId3 . '_' . $page, $mod, C("app_shops_cache_time"));

            return $mod; //此处不能处理返回格式
        }
        return $data;

    }

    /* api 获取附近的商家 依据矩形地图
	*/
    public function getDistrictsMap($areaId3, $lat, $lng)
    {
        $newTime = date('H') . '.' . date('i');//获取当前时间
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;

        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
        //配送区域条件
        $where["wst_shops_communitys.areaId3"] = $areaId3;

        $mod = M("shops")->where($where)
            ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
            ->group('wst_shops.shopId')
            ->select();
        if (count($mod) <= 0) {
            //后来新加,没有获取到附近店铺的时候,随便获取一条可用的店铺数据即可
            /*unset($where['wst_shops_communitys.areaId3']);
            $mod[] = M("shops")->where($where)
                ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
                ->order('rand()')
                ->find();*/
            if ($lat == 'undefined') {
                $lat = '';
            }
            if ($lng == 'undefined') {
                $lng = '';
            }

            //如果不存在经纬度 就从默认城市中随便取出一条店铺数据
            if (intval($lat) == 0 or intval($lng) == 0) {
                return self::getdefaultShop();
            }

            //huihui-2020-1-7
            //此处不需要经纬度
            // //获取到后台设置的默认城市,拿到经纬度即可,不必验证店铺状态
            // if(empty($lat) || empty($lng)){
            //     $defaultCity = M('sys_configs')->where(['fieldCode'=>'defaultCity'])->getField('fieldValue');
            //     $shopRelationWhere['areaId2'] = $defaultCity;
            //     $shopRelation = M('shops')->where(['areaId2'=>$defaultCity])->field('latitude,longitude')->find();
            //     if($shopRelation){
            //         $lat = $shopRelation['latitude'];
            //         $lng = $shopRelation['longitude'];
            //     }
            // }

            // //默认城市没有获取到相关的店铺经纬度信息就随机取一条店铺的经纬度数据
            // if(empty($lat) || empty($lng)){
            //     $shopRelation = M('shops')->where("shopFlag=1 and latitude != '' and longitude != '' ")->field('latitude,longitude')->find();
            //     if($shopRelation){
            //         $lat = $shopRelation['latitude'];
            //         $lng = $shopRelation['longitude'];
            //     }
            // }
            // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $newTime . "' and s.serviceEndTime >= '" . $newTime . "' ";
            $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 ";

            $sql = "select s.*,u.userName,(select count('g.goodsId') from __PREFIX__goods g where g.shopId=s.shopId and g.goodsFlag=1 and g.goodsStatus=1 and g.isSale=1 ) as goodsCount,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s left join wst_shops_communitys sc on s.shopId=sc.shopId left join __PREFIX__users u on u.userId=s.userId " . $where . " order by distance";
            $goodsList = $this->query($sql);
            foreach ($goodsList as $val) {
                if ($val['goodsCount'] > 0) {
                    $mod[] = $val;
                    break;
                }
            }
        }
        //必须使用百度经纬度
        //获取在配送范围的店铺
        for ($i = 0; $i < count($mod); $i++) {
            if (checkShopDistribution($mod[$i]['shopId'], $lng, $lat)) {
                //在配送范围内 返回当前门店
                return [$mod[$i]];
            }
        }
        //如果没有在配送范围的数据 直接获取最近的店铺位置
        return $mod;
    }

    /**
     * 获取附近店铺
     * @param int adcode 区县id
     * @param float lat 纬度
     * @param float lng 经度
     * @param bool distance 距离排序【true:执行距离排序|false:无操作】
     * @param bool shopSale 店铺销量排序【true:执行店铺销量排序|false:无操作】
     * @param bool shopScore 店铺评分排序【true:执行店铺评分排序|false:无操作】
     * @param varchar shopTypeId 店铺类型Id,多个店铺类型id用英文逗号分隔
     * @param bool deliveryFreeMoney 减免运费【true:筛选减免运费的店铺|false:无操作】
     * @param int dataType 场景类型【1:前置仓|2:多商户】
     * @param int page 页码
     * @param int pageSize 分页条数,默认10条
     * */
    public function nearbyshopMap($userId, $areaId3, $lat, $lng, $page, $pageSize, $dataType, $findWhere)
    {
        $nowTime = (float)(date('H') . '.' . date('i'));//获取当前时间
        $field = 's.shopId,s.shopSn,s.openLivePlay,s.userId,s.areaId1,s.areaId2,s.areaId3,s.goodsCatId1,s.goodsCatId2,s.goodsCatId3,s.isSelf,s.shopName,s.shopCompany,s.shopImg,s.shopTel,s.shopAddress,s.avgeCostMoney,s.deliveryStartMoney,s.deliveryMoney,s.deliveryFreeMoney,s.deliveryCostTime,s.deliveryTime,s.deliveryType,s.bankId,s.bankNo,s.isInvoice,s.invoiceRemarks,s.serviceStartTime,s.serviceEndTime,s.shopAtive,s.latitude,s.longitude,s.qqNo,com.communityId,s.goodsCatId1 as shopTypeId ';
        $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";//距离
        $field .= ",(select sum(og.goodsNums) from __PREFIX__order_goods og  left join __PREFIX__orders o on o.orderId=og.orderId where o.shopId=s.shopId and o.isPay=1 ) as shopSale ";//销量
        $field .= ",(select totalScore from __PREFIX__shop_scores score where score.shopId=s.shopId) as shopScore ";//评分
        $where = " s.shopStatus=1 and s.shopFlag=1 ";//店铺基本条件
        //$where .= " and s.serviceStartTime <= '{$nowTime}' and s.serviceEndTime >= '{$nowTime}' ";//店铺营业时间
//        if (!empty($areaId3)) {
//            $where .= " and com.areaId3=$areaId3 ";//店铺社区条件
//        }
        //减免运费
        if ($findWhere['deliveryFreeMoney'] == true) {
            $where .= " and s.deliveryFreeMoney > 0 ";
        }
        //店铺类型
        if ($findWhere['shopTypeId'] == true) {
            $shopTypeId = trim($findWhere['shopTypeId'], ',');
            $where .= " and s.goodsCatId1 IN('{$shopTypeId}')";
        }
        $orderBy = 'order by distance asc';
        //综合排序 PS:先按店铺评分排序
        if ($findWhere['shopScore'] == true) {
            $orderBy = "order by shopScore desc";
        }
        //距离
        if ($findWhere['distance'] == true) {
            $orderBy = "order by distance asc";
        }
        //销量
        if ($findWhere['shopSale'] == true) {
            $orderBy = "order by shopSale desc ";
        }
        $start = ($page - 1) * $pageSize;
        $sql = "select $field from __PREFIX__shops s left join __PREFIX__shops_communitys com on com.shopId=s.shopId ";
        $sql .= " where $where group by s.shopId ";
        $sql .= $orderBy;
        $sql .= " limit $start,$pageSize";
        $shops = $this->query($sql);
        if ($dataType == 1) {
            //该区间的代码是原来的代码只用于前置仓,也不需要改动
            //如果是前置仓模式,在没有符合店铺的情况下随便丢一条数据返回,因为多商户涉及到搜索店铺,所以就不返回了
            if (count($shops) <= 0) {
                if ($lat == 'undefined') {
                    $lat = '';
                }
                if ($lng == 'undefined') {
                    $lng = '';
                }
                //如果不存在经纬度 就从默认城市中随便取出一条店铺数据
                if (intval($lat) == 0 or intval($lng) == 0) {
                    return self::getdefaultShop();
                }
                // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $nowTime . "' and s.serviceEndTime >= '" . $nowTime . "' ";
                $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 ";

                $sql = " select s.*,u.userName,(select count('g.goodsId') from __PREFIX__goods g where g.shopId=s.shopId and g.goodsFlag=1 and g.goodsStatus=1 and g.isSale=1 ) as goodsCount,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s left join wst_shops_communitys sc on s.shopId=sc.shopId left join __PREFIX__users u on u.userId=s.userId " . $where . " order by distance ";
                $goodsList = $this->query($sql);
                $shops = [];
                foreach ($goodsList as $val) {
                    if ($val['goodsCount'] > 0) {
                        $shops[] = $val;
                        break;
                    }
                }
                return $shops;//没有符合条件的,随便丢一条出去
            }
        }
        if (empty($shops)) {
            return [];
        }
        $returnData = [];
        //必须使用百度经纬度
        //获取在配送范围的店铺
        for ($i = 0; $i < count($shops); $i++) {
            $shops[$i]['distributionScope'] = -1;//是否在配送范围【-1：不在配送范围|1：在配送范围】
            $shopId = $shops[$i]['shopId'];
            if (checkShopDistribution($shopId, $lng, $lat)) {
                $shops[$i]['distributionScope'] = 1;
            }
            $shops[$i]['coupons'] = $this->getShopCoupons($shopId, $userId)['data'];
            $shops[$i]['shopScore'] = $this->getShopScores($shopId)['data']['zfScore'];
            $shops[$i]['shopSale'] = (int)$shops[$i]['shopSale'];
            $shops[$i]['shopCartNum'] = $this->getShopCartNum($userId, $shopId);//店铺购物车商品数量
            $returnData[] = $shops[$i];
        }
        //前置仓,前端取得是下标为0的数据,所以这里返回下标为0的数据尽量为可用的
        if ($dataType == 1) {
            $shopsDataSort = [];
            foreach ($returnData as $value) {
                $shopsDataSort[] = $value['distributionScope'];
            }
            array_multisort($shopsDataSort, SORT_DESC, $returnData);
            $invalidShop = [];//不在配送范围的店铺排到后面
            foreach ($returnData as $key => $value) {
                if ($value['distributionScope'] == -1) {
                    $invalidShop[] = $returnData[$key];
                    unset($returnData[$key]);
                } else {
                    $distanceArr[] = $value['distance'];
                }
            }
            if (!empty($distanceArr) && !empty($returnData)) {
                array_multisort($distanceArr, SORT_ASC, $returnData);
            }
            if (count($returnData) <= 0) {
                $returnData = $shops;
            } else {
                $returnData = array_merge($returnData, $invalidShop);
                $returnData = array_values($returnData);
            }
        }
        return $returnData;
    }

    /**
     * 获取店铺购物车商品数量
     * @param int $userId
     * @param int $shopId
     * @return float
     * */
    public function getShopCartNum($userId, $shopId)
    {
        if (empty($userId) || empty($shopId)) {
            return 0;
        }
        $where = [];
        $where['cart.userId'] = $userId;
        $where['goods.goodsFlag'] = 1;
        $where['goods.shopId'] = $shopId;
        $where['shops.shopFlag'] = 1;
        $data = M('cart cart')
            ->join('left join wst_goods goods on goods.goodsId=cart.goodsId')
            ->join('left join wst_shops shops on shops.shopId=goods.shopId')
            ->where($where)
            ->sum('cart.goodsCnt');
        return (float)$data;
    }

    //获取默认店铺数据
    static function getdefaultShop()
    {
        $mod = M('shops');
        $where['shopStatus'] = 1;
        $where['shopAtive'] = 1;
        $where['shopFlag'] = 1;
        $where['areaId2'] = $GLOBALS['CONFIG']['defaultCity'];
        return (array)$mod->where($where)->select();

    }

    /* 计算两个经纬度的距离 */
    public function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        $returnData = compact('miles', 'feet', 'yards', 'kilometers', 'meters');
        return $returnData;//此处不能使用返回标准字段
    }

    /* 店铺当月已完成的订单数量统计 */
    public function month_order_num($shopId)
    {
        $returnData = M('orders')->where(array('shopId' => $shopId, 'receiveTime' => array('like', date('Y-m') . '%'), 'orderFlag' => 1))->count();
        return returnData($returnData);
    }

    public function getGoodsCatsAndGoodsForIndex($lat, $lng, $adcode)
    {

        //把用户经纬度转换为高德坐标 暂不转先看距离相差多大
        $adData = M("shops_communitys")->where("areaId3 = '{$adcode}'")->select();
        foreach ($adData as $key => &$val) {
            $adDatas[$val['shopId']] = $val;
        }
        unset($val);
        $adDatas = array_values($adDatas);

        $shops = M("shops");
        $newTime = date('H') . '.' . date('i');//获取当前时间
        $shopsWhere['shopStatus'] = 1;
        $shopsWhere['shopAtive'] = 1;
        $shopsWhere['shopFlag'] = 1;
        // $shopsWhere['serviceStartTime'] = array('ELT', (float)$newTime);//店铺营业时间
        // $shopsWhere['serviceEndTime'] = array('EGT', (float)$newTime);//店铺休息时间

        foreach ($adDatas as $key => $val) {
            $shopsData[] = $shops->where($shopsWhere)->field("latitude,longitude,shopName,shopId")->find();
        }
        $shopsData = arrayUnset($shopsData, 'shopId');

        //店铺经纬度（高德） 依据用户位置按照近到远排序到数组
        for ($i = 0; $i <= count($shopsData) - 1; $i++) {
            $shopsData[$i]['shopsDataSort'] = getDistanceBetweenPointsNew($shopsData[$i]['latitude'], $shopsData[$i]['longitude'], $lat, $lng)['kilometers'];
        }
        foreach ($shopsData as $key => &$val) {
            $val['shopsDataSort'] = getDistanceBetweenPointsNew($shopsData[$i]['latitude'], $shopsData[$i]['longitude'], $lat, $lng)['kilometers'];
        }
        unset($val);
        $shopsDataSort = array();
        foreach ($shopsData as $user) {
            $shopsDataSort[] = $user['shopsDataSort'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $shopsData);//从低到高排序

        //return $shopsData;

        $mgoods = M("goods");

        $wap['goodsFlag'] = 1;
        $wap['isAdminBest'] = 1;
        $wap['isAdminRecom'] = 1;
        $wap['isSale'] = 1;
        $wap['goodsStatus'] = 1;

        for ($i = 0; $i <= count($shopsData) - 1; $i++) {
            $wap['shopId'] = $shopsData[$i]['shopId'];
            $moou[$i] = $mgoods->field(array("shopId", "goodsId", "goodsThums", "goodsName", "shopPrice", "goodsCatId1", "goodsUnit", "goodsStock", "isDistribution", "firstDistribution", "SecondaryDistribution", "markIcon"))->where($wap)->order("saleCount desc")->limit(100)->select();
            //if(!empty($moou[$i])){
            $modend['niao_goods'][$i] = $moou[$i];
            //}

        }

        //获取分类
        $map['parentId'] = 0;
        $map['isShow'] = 1;
        $map['catFlag'] = 1;
        $mod = M("goods_cats")->where($map)->order("catSort asc")->select();

        $modend['typelist'] = $mod;
        $modend['niao_goods'][0] = rankGoodsPrice($modend['niao_goods'][0]); //商品等级价格处理

        return returnData($modend);
    }

    /********会员签到**** APP *****/
    public function MemberSign($loginName)
    {
        $map['loginName'] = $loginName;
        $mod = M("users")->where($map)->find();
        if (empty($mod)) {
//            $data["StatusCode"] = "000001";
            $data = returnData(null, -1, 'error', '用户不存在');
            return $data;
        }
        $time = date("Y-m-d");
//        $user_score = M("user_score")->where("userId = '{$mod['userId']}' and createTime >= '{$time}'")->select();//查找当前用户大于等于今天的签到记录
        if ($mod['lastSignInTime'] < $time) {//未签到
            /* $users_save = M("users")->where("userId = '{$mod['userId']}'")->find();
			$num = $users_save['userScore'] + C("app_user_score");
			$users['userScore'] = $num;

			$save_user= M("users");
			$save = $save_user->where("userId = '{$mod['userId']}'")->save($users);//更新用户积分 */

//            M('users')->where("userId = {$mod['userId']}")->setInc('userScore', $GLOBALS["CONFIG"]["Qscore"]);
            //积分处理-start
            $users_service_module = new UsersServiceModule();
            $score = (int)$GLOBALS["CONFIG"]["Qscore"];
            $users_id = $mod['userId'];
            $result = $users_service_module->return_users_score($users_id, $score);
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(null, -1, 'error', $result['msg']);
            }
            //积分处理-end
            M('users')->where("userId = {$mod['userId']}")->save(array('lastSignInTime' => date('Y-m-d H:i:s')));

            $user_score_add = M("user_score");//添加积分记录
            $user_add['dataSrc'] = 5;//来源app
            $user_add['userId'] = $mod['userId'];//用户id
            $user_add['score'] = $GLOBALS["CONFIG"]["Qscore"];//积分
            $user_add['dataRemarks'] = "每日签到获得";
            $user_add['scoreType'] = 1;
            $user_add['dataId'] = 1;//来源记录
            $user_add['createTime'] = date("Y-m-d H:i:s");
            $add = $user_score_add->add($user_add);
            if ($add) {
//                $data['StatusCode'] = "000004";
                $data['StatusInfo'] = "签到成功";
                $data['getIntegral'] = $GLOBALS["CONFIG"]["Qscore"];
                $data['currentScore'] = M('users')->where("userId = {$mod['userId']}")->find()['userScore'];
                $data = returnData($data);
                return $data;
            }
        } else {//已签到
//            $data['StatusCode'] = "000003";
            $data['currentScore'] = M('users')->where("userId = {$mod['userId']}")->find()['userScore'];
            $data = returnData($data, 0, 'success', '已经签到');
            return $data;
        }
    }

    /********会员签到*** 小程序 ******/
    public function xcxMemberSign($loginName)
    {
        $map['loginName'] = $loginName;
        $mod = M("users")->where($map)->find();
        if (empty($mod)) {
//            $data["StatusCode"] = "000001";
            $data = returnData(null, -1, 'error', '用户不存在');
            return $data;
        }
        $time = date("Y-m-d");

        $user_score = M("user_score")->where("userId = '{$mod['userId']}' and createTime >= '{$time}'")->select();//查找当前用户大于等于今天的签到记录
        if (empty($user_score)) {
            /* $users_save = M("users")->where("userId = '{$mod['userId']}'")->find();
				$num = $users_save['userScore'] + C("app_user_score");
				$users['userScore'] = $num;

				$save_user= M("users");
				$save = $save_user->where("userId = '{$mod['userId']}'")->save($users);//更新用户积分 */
//            M('users')->where("userId = {$mod['userId']}")->setInc('userScore', $GLOBALS["CONFIG"]["Qscore"]);
            //积分处理-start
            $users_service_module = new UsersServiceModule();
            $score = (int)$GLOBALS["CONFIG"]["Qscore"];
            $users_id = $mod['userId'];
            $result = $users_service_module->return_users_score($users_id, $score);
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(null, -1, 'error', $result['msg']);
            }
            //积分处理-end
            $user_score_add = M("user_score");//添加积分记录
            $user_add['dataSrc'] = 6;//来源小程序
            $user_add['userId'] = $mod['userId'];//用户id
            $user_add['score'] = $GLOBALS["CONFIG"]["Qscore"];//积分
            $user_add['dataRemarks'] = "小程序签到获得";
            $user_add['scoreType'] = 1;
            $user_add['dataId'] = 1;//来源记录
            $user_add['createTime'] = date("Y-m-d H:i:s");
            $add = $user_score_add->add($user_add);
            if ($add) {
//                $data['StatusCode'] = "000004";
                $data['StatusInfo'] = "签到成功";
                $data = returnData($data);
                return $data;
            }
        } else {
//            $data['StatusCode'] = "000003";
            $data = returnData(null, -1, 'error', '已经签到');
            return $data;
        }
    }

    /**********根据店铺Id获取店铺信息**********/
    public function getShopIdInformation($shopId, $lat, $lng)
    {
        $data = S("niao_api_getShopIdInformation_id_{$shopId}");

        if (empty($data)) {
            $map['shopId'] = $shopId;
            $map['shopFlag'] = 1;
            $mod = M("shops")->where($map)->field(array("bankId", "bankNo", "bankUserName"), true)->find();
            $mod['distance'] = getDistanceBetweenPointsNew($lat, $lng, $mod['latitude'], $mod['longitude'])['kilometers'];
            S("niao_api_getShopIdInformation_id_{$shopId}", $mod, C("niao_app_getShopIdInformation_cache_time"));
            return $mod;
        }
        return $data;
    }

    /* 获取当前店铺所有商品 */
    public function getShopGoods($userId, $shopId, $page = 1)
    {
        $pageDataNum = I('pageSize', 10, 'intval');
        $data = S("niao_app_getShopGoods_{$shopId}_{$page}_{$pageDataNum}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $map['isMembershipExclusive'] = 0;
                }
            }


            $map['shopId'] = $shopId;
            $map['isSale'] = 1;
            $map['goodsStatus'] = 1;
            $map['goodsFlag'] = 1;
            $mod = M("goods")
                ->where($map)
                ->order("shopGoodsSort desc")
                ->field(array("goodsDesc"), true)
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $mod = rankGoodsPrice($mod); //商品等级价格处理
            S("niao_app_getShopGoods_{$shopId}_{$page}_{$pageDataNum}", $mod, C("niao_app_getShopAllGoods_cache_time"));
            return $mod;
        }
        return $data;
    }

    /* 根据店铺Id获取当前店铺分类 */
//    public function getShopIdType($shopId)
//    {
//        $data = S("niao_app_getShopIdType_{$shopId}");
//        if (empty($data)) {
//            $map['shopId'] = $shopId;
//            $map['isShow'] = 1;
//            $map['catFlag'] = 1;
//            //$map['parentId'] = 0;
//            $mod = M("shops_cats")->order('catSort asc')->where($map)->select();
//            $data = empty($mod) ? array() : self::getTreeArrays($mod);
//            S("niao_app_getShopIdType_{$shopId}", $data, C("niao_app_getShopIdType_cache_time"));//数据重新排序后 放入缓存
//            return array_merge($data);
//        }
//        return array_merge($data);
//    }

    /* 根据店铺Id获取当前店铺分类 */
    public function getShopIdType($shopId)
    {
        $typeOneList = S("niao_app_getShopIdType_{$shopId}");
        if (empty($typeOneList)) {
            $shopCatTab = M('shops_cats');
            $typeOne = [];
            $typeOne['shopId'] = $shopId;
            $typeOne['isShow'] = 1;
            $typeOne['catFlag'] = 1;
            $typeOne['parentId'] = 0;
            $typeOneList = $shopCatTab->order('catSort asc')->where($typeOne)->select();
            if (!empty($typeOneList)) {
                $typeOneIdArr = [];
                foreach ($typeOneList as $key => $value) {
                    $typeOneIdArr[] = $value['catId'];
                }
                $typeOneIdStr = implode(',', $typeOneIdArr);
                $typeTwo = [];
                $typeTwo['shopId'] = $shopId;
                $typeTwo['isShow'] = 1;
                $typeTwo['catFlag'] = 1;
                $typeTwo['parentId'] = ['IN', $typeOneIdStr];
                $typeTwoList = $shopCatTab->order('catSort asc,catId asc')->where($typeTwo)->select();
                foreach ($typeOneList as $oneKey => $oneVal) {
                    $typeOneList[$oneKey]['type2'] = [];
                    foreach ($typeTwoList as $twoKey => $twoVal) {
                        if ($twoVal['parentId'] == $oneVal['catId']) {
                            $typeOneList[$oneKey]['type2'][] = $twoVal;
                        }
                    }
                }
            }
            S("niao_app_getShopIdType_{$shopId}", $typeOneList, C("niao_app_getShopIdType_cache_time"));//数据重新排序后 放入缓存
        }
        return $typeOneList;
    }

    static function getTreeArrays($arr, $parentId = 0)
    {//一维数组  循环出菜单
        //$tree=array();
        static $tree = array();
        foreach ($arr as $key => $value) {
            if ($value['parentId'] == $parentId) {
                $tree[$key] = $value;
                foreach ($arr as $key2 => $value2) {
                    if ($value['catId'] == $value2['parentId']) {
                        $tree[$key]['type2'][] = $value2;
                    }

                }
            }

        }
        return $tree;
    }

    /* 获取店铺某个一级分类下的所有商品 */
    public function getShopTypeOneGoods($userId, $shopId, $shopCatId1, $page)
    {
        $data = S("niao_app_getShopOneTypeGoods_{$shopId}{$shopCatId1}_{$page['page']}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $map['isMembershipExclusive'] = 0;
                }
            }

            $map['shopId'] = $shopId;
            $map['shopCatId1'] = $shopCatId1;
            $map['isSale'] = 1;
            $map['goodsStatus'] = 1;
            $map['goodsFlag'] = 1;
//            $map['isFlashSale'] = 0;//是否限时
//            $map['isLimitBuy'] = 0;//是否限时
            $data = M("goods")->order("shopGoodsSort desc")->where($map)->field()->limit(($page['page'] - 1) * $page['pageSize'], $page['pageSize'])->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("niao_app_getShopOneTypeGoods_{$shopId}{$shopCatId1}_{$page['page']}", $data, C("niao_app_getShopTypeGoods_cache_time"));
        }
        //查找商城一级分类横图
        $where['catId'] = $data[0]['goodsCatId1'];
        $where['parentId'] = 0;
        $typeImgUrl = M('goods_cats')->where($where)->field('typeimg,appTypeSmallImg')->find();
        $res['typeImgUrl'] = empty($typeImgUrl) ? array() : $typeImgUrl;
        $res['goodslist'] = empty($data) ? array() : $data;
        return $res;
    }

    /* 获取店铺某个二级分类下的所有商品 */
    public function getShopTypeTwoGoods($userId, $shopId, $shopCatId2, $page)
    {
        $data = S("niao_app_getShopTwoTypeGoods_{$shopId}{$shopCatId2}_{$page['page']}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $map['isMembershipExclusive'] = 0;
                }
            }

            $map['shopId'] = $shopId;
            $map['shopCatId2'] = $shopCatId2;
            $map['isSale'] = 1;
            $map['goodsStatus'] = 1;
            $map['goodsFlag'] = 1;
//            $map['isFlashSale'] = 0;//是否限时
//            $map['isLimitBuy'] = 0;//是否限时
            $mod = M("goods")->order("shopGoodsSort desc")->where($map)->field()->limit(($page['page'] - 1) * $page['pageSize'], $page['pageSize'])->select();
            $mod = rankGoodsPrice($mod);
            S("niao_app_getShopTwoTypeGoods_{$shopId}{$shopCatId2}_{$page['page']}", $mod, C("niao_app_getShopTypeGoods_cache_time"));
            return empty($mod) ? array() : $mod;
        }
        return $data;
    }

    /* 根据商品Id获取商品详情(相册另外获取) */
    public function getGoodDetails($goodsId, $userId = 0)
    {
        $data = S("niao_app_getGoodDetails_{$goodsId}");
        if (empty($data)) {
            $map['goodsId'] = $goodsId;
            $map['isSale'] = 1;
            $map['goodsStatus'] = 1;
            $map['goodsFlag'] = 1;
            $mod = M("goods")->where($map)->field(array("goodsDesc"), true)->find();//应该使用find 前端已经做好了 暂时不改了 不影响
            $mod = rankGoodsPrice($mod); //商品等级价格处理
            if ($mod) {
                //获取店铺信息加入进去
                $shop_info = M('shops')->where("shopId = '{$mod['shopId']}'")->find();
//                $mod['shopTel'] = M('shops')->where("shopId = '{$mod['shopId']}'")->find()['shopTel'];
                $mod['shopTel'] = $shop_info['shopTel'];
                $mod['deliveryCostTime'] = $shop_info['deliveryCostTime'];
                $mod['deliveryTime'] = $shop_info['deliveryTime'];
                $mod['serviceStartTime'] = $shop_info['serviceStartTime'];
                $mod['serviceEndTime'] = $shop_info['serviceEndTime'];
                //商品相册
                //$mod['gallerys'] = M('goods_gallerys')->where(['goodsId'=>$goodsId])->order('id asc')->select();
            }
            $mod['server_time'] = time();
            $mod['timeList'] = array();
//            $skuSpec = [];
//            $skuList = [];
//            //限量购商品处理返回数据---用于判断限量购商品详情 PS:反馈:限时和限量是没有sku的
//            if ($mod['isLimitBuy'] == 1) {
//                if ($mod['limitCount'] > 0) {
//                    $mod['shopPrice'] = $mod['limitCountActivityPrice'];
//                    $mod['goodsStock'] = $mod['limitCount'];
//                }
//                $mod['hasGoodsSku'] = 0;
//                $mod['goodsSku'] = [];
//                $mod['goodsSku']['skuSpec'] = $skuSpec;
//                $mod['goodsSku']['skuList'] = $skuList;
//            }
            //限时购商品处理返回数据----用于判断限时购商品详情
            if ($mod['isFlashSale'] == 1) {
                $goodsTimeSnapped = getGoodsFlashSaleDetails($goodsId);
                if (!empty($goodsTimeSnapped['list'])) {
//                    if (!empty($goodsTimeSnapped['goodsDetail'])) {
//                        $mod['shopPrice'] = $goodsTimeSnapped['goodsDetail']['activityPrice'];
//                        $mod['goodsStock'] = $goodsTimeSnapped['goodsDetail']['activeInventory'];
//                        $mod['minBuyNum'] = $goodsTimeSnapped['goodsDetail']['minBuyNum'];
//                    }
                    $mod['timeList'] = $goodsTimeSnapped['list'];
                }
//                $mod['hasGoodsSku'] = 0;
//                $mod['goodsSku'] = [];
//                $mod['goodsSku']['skuSpec'] = $skuSpec;
//                $mod['goodsSku']['skuList'] = $skuList;
            }


            //数据缓存
            S("niao_app_getGoodDetails_{$goodsId}", $mod, C("niao_app_getGoodDetails_cache_time"));
            return (array)$mod;
        }
        $data['server_time'] = time();
        return $data;
    }

    /* 根据商品Id获取店铺信息 */
    public function getGoodsIdShopData($goodsid)
    {
        $data = S("niao_app_getGoodsIdShopDat_{$goodsid}");
        if (empty($data)) {
            $map['goodsId'] = $goodsid;
            $mod = M("goods")->where($map)->find();
            $map1['shopId'] = $mod['shopId'];
            $map1['shopStatus'] = 1;
            $map1['shopFlag'] = 1;
            $mod2 = M("shops")->where($map1)->find();
            S("niao_app_getGoodsIdShopDat_{$goodsid}", $mod2, C("niao_app_getGoodsIdShopDat_{$goodsid}"));
            return $mod2;
        }
        return $data;
    }

    /* 根据商品Id获取商品相册*/
    public function getGoodphotoAlbum($goodsId)
    {
        $data = S("niao_app_getGoodphotoAlbum_{$goodsId}");
        if (empty($data)) {
            $map['goodsId'] = $goodsId;
            $mod = M("goods_gallerys")->where($map)->select();
            S("niao_app_getGoodphotoAlbum_{$goodsId}", $mod, C("niao_app_getGoodphotoAlbum_cache_time"));
            return $mod;
        }
        return $data;
    }

    /* 获取商城分类一级和二级 */
    public function getNiaoMallType()
    {
        $where['isShow'] = 1;
        $where['catFlag'] = 1;
        $where['parentId'] = 0;
        $mod = M("goods_cats")->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();

        for ($i = 0; $i < count($mod); $i++) {
            $mod[$i]["type2"] = self::get_type($mod[$i]['catId']);
        }

        return $mod;

        /* foreach($mod as $k => $v){
			$data = self::get_type($v['catId']);
			foreach($data as $k2 => $v2){
				 //$v['type2'][$k2]=$datas[$k][$k2] = $v2;
				//$datas[$k][$k2] = $v2;
				$datas[$k][$k2] = $v2;
			}

			foreach($datas[$k] as $k3 =>$V3){
				$v['type2'][$k3] = $V3;
			}

		} */
        /*
		foreach($datas as $k3 => $V3){
			foreach($V3 as $k4 => $v4){
				$datas1['type'][$k3][$k4] = $v4;
			}
		}  */
        //return $v;

    }

    static function get_type($typecatId)
    {//pdo下使用参数绑定 提高效率
        $Model = M('goods_cats');
        $where['isShow'] = 1;
        $where['catFlag'] = 1;
        $where['parentId'] = $typecatId;
        return $list = $Model->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();
    }

    /* 根据第二级获取商城分类第三级以及商品 */
    public function getNiaoMallThreeType($catId2)
    {
        $mod = S("niao_app_getThirdCatAndGoods_{$catId2}");
        if (empty($mod)) {
            $where['isShow'] = 1;
            $where['catFlag'] = 1;
            $where['parentId'] = $catId2;
            $mod = M("goods_cats")->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();

            for ($i = 0; $i < count($mod); $i++) {
                $map = array();
                $map['goodsCatId3'] = $mod[$i]['catId'];
                $map['isSale'] = 1;
                $map['goodsStatus'] = 1;
                $map['goodsFlag'] = 1;
                $goodsList = M("goods")->order("shopGoodsSort desc")->where($map)->select();
                $goodsList = rankGoodsPrice($goodsList);
                $mod[$i]['goodsList'] = $goodsList;
            }
            S("niao_app_getThirdCatAndGoods_{$catId2}", $mod, C("niao_app_getShopTypeGoods_cache_time"));
        }

        return $mod;
    }

    /* 获取商品评价 */
    public function getGoodEvaluate($goodsId, $page = 1, $pageSize = 10)
    {
        $data = S("niao_app_getGoodEvaluate_{$goodsId}_{$page}");
        if (empty($data)) {
            $parameter = I();
            $pageDataNum = $pageSize;
            $where['goodsId'] = $goodsId;
            $where['isShow'] = 1;
            isset($parameter['compScore']) ? $where['compScore'] = $parameter['compScore'] : false;
            $mod_oods_appraises = M('goods_appraises');
            $data = $mod_oods_appraises->where($where)->order('createTime desc')->limit(($page - 1) * $pageDataNum, $pageDataNum)->select();

            foreach ($data as $k => $v) {
                if (!empty($v['appraisesAnnex'])) {
                    $data[$k]['appraisesAnnex'] = explode(',', $v['appraisesAnnex']);
                } else {
                    $data[$k]['appraisesAnnex'] = [];
                }
            }

            for ($i = 0; $i <= count($data) - 1; $i++) {
                $scro = (int)$data[$i]['goodsScore'] + (int)$data[$i]['serviceScore'] + (int)$data[$i]['timeScore'];
                switch ($scro) {
                    case $scro <= 5 :
                        $data[$i]['status'] = 0;

                        break;
                    case $scro > 5 and $scro <= 10:
                        $data[$i]['status'] = 1;

                        break;
                    case $scro > 10 and $scro <= 15:
                        $data[$i]['status'] = 2;

                        break;
                    default:
//                        $apiRet['apiCode']=-1;
//                        $apiRet['apiInfo']='数据异常 有鬼...';
//                        $apiRet['apiState']='error';
//                        $apiRet['apiData']=null;
                        $apiRet = returnData(null, -1, 'error', '数据异常 有鬼...');
                }
            }

            $modUsers = M('users');
            $where = array();
            $where['userFlag'] = 1;
            for ($i = 0; $i <= count($data) - 1; $i++) {
                $where['userId'] = $data[$i]['userId'];
                $uerData = $modUsers->where($where)->field('userName,userPhoto')->find();
                if (!empty($uerData['userPhoto'])) {
                    $data[$i]['userPhoto'] = $uerData['userPhoto'];
                } else {
                    $data[$i]['userPhoto'] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
                }
                if (!empty($uerData['userName'])) {
                    $data[$i]['userName'] = $uerData['userName'];
                } else {
                    $data[$i]['userName'] = "未设置";
                }
            }
            S("niao_app_getGoodEvaluate_{$goodsId}_{$page}", $data, C("niao_app_getGoodEvaluate_cache_time"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商品评价列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $data = is_array($data) == true ? $data : [];
        $apiRet = returnData($data);
        return $apiRet;
    }

    /* 商品评价-指定好中差获取 */
    public function getGoodEvaluateDes($goodsId, $page = 1, $compScore, $pageSize)
    {
        $data = S("niao_app_getGoodEvaluate_{$goodsId}_{$page}_{$compScore}");
        if (empty($data)) {
            $pageDataNum = $pageSize;
            $where['goodsId'] = $goodsId;
            $where['isShow'] = 1;
            $where['compScore'] = $compScore;
            $mod_oods_appraises = M('goods_appraises');
            $data = $mod_oods_appraises->where($where)->order('createTime desc')->limit(($page - 1) * $pageDataNum, $pageDataNum)->select();

            $modUsers = M('users');
            $where = array();
            $where['userFlag'] = 1;
            for ($i = 0; $i <= count($data) - 1; $i++) {
                $where['userId'] = $data[$i]['userId'];
                $uerData = $modUsers->where($where)->field('userName,userPhoto')->find();
                if (!empty($uerData['userPhoto'])) {
                    $data[$i]['userPhoto'] = $uerData['userPhoto'];
                } else {
                    $data[$i]['userPhoto'] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
                }
                if (!empty($uerData['userName'])) {
                    $data[$i]['userName'] = $uerData['userName'];
                } else {
                    $data[$i]['userName'] = "未设置";
                }
            }
            S("niao_app_getGoodEvaluate_{$goodsId}_{$page}_{$compScore}", $data, C("niao_app_getGoodEvaluate_cache_time"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商品评价列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $data = is_array($data) == true ? $data : [];
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取评价用户名 pdo下请使用参数绑定提高效率
    static function getEvaluateUsername($userId)
    {
        $where['userId'] = $userId;
        $where['userFlag'] = 1;
        $mod = M("users")->where($where)->field(array('userName', 'userPhoto'))->find();
        if (empty($mod["userPhoto"])) {
            $mod["userPhoto"] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
        }
        return $mod;
    }

    /* 获取商品描述(商品介绍) */
    public function getGoodIntroduce($goodsId)
    {
        //$data = S("niao_app_getGoodIntroduce_{$goodsId}");
        if (empty($data)) {
            $where['goodsId'] = $goodsId;
            $mod = M("goods")->where($where)->field(array("goodsDesc"))->find();
            //S("niao_app_getGoodIntroduce_{$goodsId}", $mod, C("niao_app_getGoodIntroduce_cache_time"));
            return $mod;
        }
        return $data;
    }

    /* 关注和取消关注商品 type = 0:商品 1:店铺
		对象Id targetId
	*/
    public function goodFollow($loginName, $targetId)
    {

        $where_goods['goodsId'] = $targetId;
        $where_goods['goodsStatus'] = 1;
        $where_goods['goodsFlag'] = 1;
        $isgoods = M("goods")->where($where_goods)->field(array("goodsId"))->find();

        if (empty($isgoods)) {
//            $data['StatusCode'] = "000006";
            $data = returnData(null, -1, 'error', '关注的商品不存在');
            return $data;
        }

        $where_users['loginName'] = $loginName;
        $where_users['userFlag'] = 1;
        $user = M("users")->where($where_users)->field(array("userId"))->find();

        $mod = M("favorites");
        $data['userId'] = $user["userId"];
        $data['favoriteType'] = 0;
        $data['targetId'] = $targetId;
        $data['createTime'] = date("Y-m-d H:i:s");
        if ($mod->add($data)) {
//            $datas['StatusCode'] = "000007";
            $datas['StatusInfo'] = "关注商品成功";
            $datas = returnData($datas);
            return $datas;
        } else {
//            $datas['StatusCode'] = "000008";
            $datas = returnData(null, -1, 'error', '关注商品失败');
            return $datas;
        }
    }

    //取消商品关注
    public function goodnoFollow($loginName, $targetId)
    {
        $where_goods['goodsId'] = $targetId;
        $where_goods['goodsStatus'] = 1;
        $where_goods['goodsFlag'] = 1;
        $isgoods = M("goods")->where($where_goods)->field(array("goodsId"))->find();
        if (empty($isgoods)) {
//            $data['StatusCode'] = "000016";
            $data = returnData(null, -1, 'error', '取关的商品不存在');
            return $data;
        }

        $where_users['loginName'] = $loginName;
        $where_users['userFlag'] = 1;
        $user = M("users")->where($where_users)->field(array("userId"))->find();

        $mod = M("favorites");

        $where['userId'] = $user["userId"];
        $where['targetId'] = $targetId;
        $where['favoriteType'] = 0;
        if ($mod->where($where)->delete()) {
//            $datas['StatusCode'] = "000009";
            $datas['StatusInfo'] = "取关商品成功";
            $datas = returnData($datas);
            return $datas;
        } else {
//            $datas['StatusCode'] = "000010";
            $datas = returnData(null, -1, 'error', '取关商品失败');
            return $datas;
        }
    }


    /* 关注店铺 */
    public function shopFollow($loginName, $targetId)
    {

        $where_shops["shopId"] = $targetId;
        $where_shops["shopStatus"] = 1;
        $where_shops["shopFlag"] = 1;
        $isshops = M("shops")->where($where_shops)->field(array("shopId"))->find();

        if (empty($isshops)) {
//            $data['StatusCode'] = "000011";
            $data = returnData(null, -1, 'error', '关注的店铺不存在');
            return $data;
        }

        $user = M("users")->where("loginName = '{$loginName}'")->field(array("userId"))->find();

        $mod = M("favorites");
        $data['userId'] = $user["userId"];
        $data['favoriteType'] = 1;
        $data['targetId'] = $targetId;
        $data['createTime'] = date("Y-m-d H:i:s");
        if ($mod->add($data)) {
//            $datas['StatusCode'] = "000012";
            $datas['StatusInfo'] = "关注店铺成功";
            $datas = returnData($datas);
            return $datas;
        } else {
//            $datas['StatusCode'] = "000013";
            $datas = returnData(null, -1, 'error', '关注店铺失败');
            return $datas;
        }
    }

    /* 取消关注店铺 */
    public function shopnoFollow($loginName, $targetId)
    {
        $where_shops["shopId"] = $targetId;
        $where_shops["shopStatus"] = 1;
        $where_shops["shopFlag"] = 1;
        $isshops = M("shops")->where($where_shops)->field(array("shopId"))->find();
        if (empty($isshops)) {
//            $data['StatusCode'] = "000017";
            $data = returnData(null, -1, 'error', '取关的店铺不存在');
            return $data;
        }
        $user = M("users")->where("loginName = '{$loginName}'")->field(array("userId"))->find();
        $mod = M("favorites");
        $where['userId'] = $user["userId"];
        $where['targetId'] = $targetId;
        $where['favoriteType'] = 1;
        if ($mod->where($where)->delete()) {
//            $datas['StatusCode'] = "000014";
            $datas['StatusInfo'] = "取关店铺成功";
            $datas = returnData($datas);
            return $datas;
        } else {
//            $datas['StatusCode'] = "000015";
            $datas = returnData(null, -1, 'error', '取关店铺失败');
            return $datas;
        }
    }

    public function SmsReg($number, $code)
    {//调用命名空间处理 注册验证
        //$GLOBALS['CONFIG']['mallName']; 商城名字
        import('Org.Taobao.top.TopClient');
        import('Org.Taobao.top.ResultSet');
        import('Org.Taobao.top.RequestCheckUtil');
        import('Org.Taobao.top.TopLogger');
        import('Org.Taobao.top.request.AlibabaAliqinFcSmsNumSendRequest');
        //将需要的类引入，并且将文件名改为原文件名.class.php的形式
        $c = new \TopClient;
        $c->appkey = C("alidayu_appkey");//appkey
        $c->secretKey = C("alidayu_secretKey");//secretKey
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("1111");//消息返回”中会透传回该参
        $req->setSmsType("normal");//短信类型，传入值请填写normal
        $req->setSmsFreeSignName("身份验证");//短信签名，传入的短信签名必须是在阿里大鱼“管理中心-短信签名管理”中的可用签名
        //$req->setSmsParam("{'code':'7758258','product':'辉哥奴隶'}");//短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。示例：针对模板“验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”，传参时需传入{"code":"1234","product":"alidayu"}
        $req->setSmsParam("{'code':'{$code}','product':'{$GLOBALS['CONFIG']['mallName']}'}");//短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。示例：针对模板“验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”，传参时需传入{"code":"1234","product":"alidayu"}
        $req->setRecNum("{$number}");//接受人手机号
        //$req->setSmsTemplateCode("SMS_3925176");//短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。示例：SMS_585014

        $req->setSmsTemplateCode("SMS_3925173");//短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。示例：SMS_585014
        $resp = $c->execute($req);
        //var_dump($resp);
        //dump($resp);

        if (isset($resp->code)) {
            return false;
        } else {
            return true;//成功
        }
    }

    public function SmsAuth($number, $code)
    {//短信 身份验证
        import('Org.Taobao.top.TopClient');
        import('Org.Taobao.top.ResultSet');
        import('Org.Taobao.top.RequestCheckUtil');
        import('Org.Taobao.top.TopLogger');
        import('Org.Taobao.top.request.AlibabaAliqinFcSmsNumSendRequest');
        $c = new \TopClient;
        $c->appkey = C("alidayu_appkey");//appkey
        $c->secretKey = C("alidayu_secretKey");//secretKey
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("1111");//消息返回”中会透传回该参
        $req->setSmsType("normal");//短信类型，传入值请填写normal
        $req->setSmsFreeSignName("身份验证");//短信签名，传入的短信签名必须是在阿里大鱼“管理中心-短信签名管理”中的可用签名

        $req->setSmsParam("{'code':{$code},'product':'支付宝'}");
        $req->setRecNum("{$number}");//接受人手机号

        $req->setSmsTemplateCode("SMS_3925173");//短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。示例：SMS_585014
        $resp = $c->execute($req);

        if (isset($resp->code)) {
            return false;
        } else {
            return true;//成功
        }
    }

    /**
     * 添加购物车
     * @param int $userId 用户id
     * @param int $goodsId 商品id
     * @param float $goodsCnt 购买数量/重量
     * @param int $skuId 商品skuId
     * @param int $type 场景【1：普通购买|2：再来一单】
     * @return array
     * */
    public function addToCart(int $userId, int $goodsId, float $goodsCnt, int $skuId, int $type)
    {
        $cart_module = new CartModule();
        $verificationCartGoods = $cart_module->verificationCartGoodsStatus($userId, $goodsId, $goodsCnt, $skuId, $type);
        if ($verificationCartGoods['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationCartGoods;
        }
        $goodsInfo = $verificationCartGoods['data']['goodsInfo'];
        $goodsId = $goodsInfo['goodsId'];
        $cartInfo = $verificationCartGoods['data']['cartInfo'];
        $goodsCount = $cartInfo['goodsCount'];
        #################################关于商品库存的验证可以写在该区间#################################
        $verificationStock = $cart_module->verificationCartGoodsStock($goodsId, $goodsCount, $skuId);
        if ($verificationStock['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationStock;
        }
        $cart_data = array(
            'userId' => $userId,
            'goodsId' => $goodsId,
            'isCheck' => 1,
            'goodsAttrId' => 0,
            'goodsCnt' => $goodsCnt,
            'skuId' => $skuId,
            'remarks' => I('remarks', '', 'trim')
        );
        if (empty($cartInfo['cartId'])) {//购物车不存在该商品则新增
            $cart_res = $cart_module->saveCart($cart_data);
            if ($cart_res) {
//                $status["statusInfo"] = "添加购物车成功";
                return returnData(true, ExceptionCodeEnum::SUCCESS, 'sucess', '加入购物车成功');
            } else {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '加入购物车失败');
            }
        } else {//购物车已存在该商品则更新购买数量
            $cart_data['goodsCnt'] = $goodsCount;
            $cart_data['cartId'] = $cartInfo['cartId'];
            $cart_res = $cart_module->saveCart($cart_data);
            if ($cart_res) {
//                $status["statusInfo"] = "更新成功";//更新数量成功
//                return returnData($status);
                return returnData(true, ExceptionCodeEnum::SUCCESS, 'sucess', '更新成功');
            } else {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败');
            }
        }
    }

    /**
     * 增加购物车商品数量
     * @param int $userId
     * @param int $cartId 购物车id
     * @param float $goodsCnt 购买数量/重量
     * @return array
     */
    public function plusCartGoodsnum(int $userId, int $cartId, float $goodsCnt)
    {
        $cart_module = new CartModule();
        $cartInfo = $cart_module->getCartDetailById($cartId);
        if (empty($cartInfo)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无相关数据');
        }
        $goodsId = $cartInfo['goodsId'];
        $skuId = $cartInfo['skuId'];
        $verificationCartGoods = $cart_module->verificationCartGoodsStatus($userId, $goodsId, $goodsCnt, $skuId);//验证购物车商品的有效性
        if ($verificationCartGoods['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationCartGoods;
        }
        $cartInfo = $verificationCartGoods['data']['cartInfo'];
        $goodsCount = $cartInfo['goodsCount'];
        $verificationStock = $cart_module->verificationCartGoodsStock($goodsId, $goodsCount, $skuId);
        if ($verificationStock['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationStock;
        }
        if (empty($cartInfo['cartId'])) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，购物车不存在当前商品');
        }
        $cart_data = array(
            'cartId' => $cartInfo['cartId'],
            'goodsCnt' => $cartInfo['goodsCount']
        );
        $cart_res = $cart_module->saveCart($cart_data);
        if ($cart_res) {
            $res = array();
            $res['count'] = $cart_module->getCartGoodsCnt($userId);//统计购物车现在的数量
            return returnData($res);
        } else {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '增加失败');
        }
    }

    /**
     * 减去购物车商品数量
     * @param int $userId
     * @param int $cartId 购物车id
     * @param float $goodsCnt 数量
     * @return array
     * */
    public function reduceCartGoodsnum(int $userId, int $cartId, float $goodsCnt)
    {
//        $goodsTab = M("goods");
//        $cartTab = M("cart");
//        $where = [];
//        $where['cartId'] = $cartId;
//        $cartInfo = $cartTab->where($where)->find();
        $cart_module = new CartModule();
        $cartInfo = $cart_module->getCartDetailById($cartId, $userId);
        if (empty($cartInfo)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无相关数据');
        }
        $cart_data = array();
        $cart_data['cartId'] = $cartInfo['cartId'];
        $cart_data["goodsCnt"] = (float)bc_math($cartInfo["goodsCnt"], $goodsCnt, 'bcsub', 0);
        if ($cart_data["goodsCnt"] <= 0) {
            $cart_data["goodsCnt"] = 1;//最低限制为1,低于1前端自行提示然后调用删除购物车接口
        }
        $cart_res = $cart_module->saveCart($cart_data);
        if ($cart_res) {
//            $status["statusInfo"] = "减去成功";//更新数量成功
            $status['count'] = $cart_module->getCartGoodsCnt($userId);//统计购物车现在的数量
            return returnData($status);
        } else {
            return returnData(fasle, ExceptionCodeEnum::FAIL, 'error', '减去失败');
        }
    }

    /**
     * PS:前置仓和多商户共存,业务逻辑混乱,需求变更太快,后期需要重构===============================
     * 获取购物车所有商品
     * */
    public function getCartInfo($param)
    {
        $userId = $param['userId'];
        $current_shopId = $param['shopId'];
        $cartModule = new CartModule();
        $cartGoodsList = $cartModule->getUserCartList($userId, -1);
        $overTimeGoods = [];//失效商品
        $cartModule = new CartModule();
        $goodsModule = new GoodsModule();
        $shopIdArr = array_column($cartGoodsList, 'shopId');
        $shopListMap = [];
        if (count($shopIdArr) > 0) {
            $shopModule = new ShopsModule();
            $shopIdArr = array_unique($shopIdArr);
            $shopList = $shopModule->getShopListByShopId($shopIdArr);
            foreach ($shopList as $shopRow) {
                $shopListMap[$shopRow['shopId']] = $shopRow;
            }
        }
        $users_module = new UsersModule();
        $users_detail = $users_module->getUsersDetailById($userId, "*", 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $goodsListMap = [];
        $goodsIdArr = array_column($cartGoodsList, 'goodsId');
        if (count($goodsIdArr) > 0) {
            $goodsListData = $goodsModule->getGoodsListById($goodsIdArr);
            $goodsList = $goodsListData['data'];
            foreach ($goodsList as $goodsListRow) {
                $goodsListMap[$goodsListRow['goodsId']] = $goodsListRow;
            }
        }
        $skuIdArr = array_column($cartGoodsList, 'skuId');
        $skuListMap = [];
        if (count($skuIdArr) > 0) {
            $skuIdArr = array_unique($skuIdArr);
            $skuList = $goodsModule->getSkuSystemListById($skuIdArr, 2);
            foreach ($skuList as $skuListRow) {
                if ($skuListRow['skuId'] <= 0) {
                    continue;
                }
                $skuListMap[$skuListRow['skuId']] = $skuListRow;
            }
        }
        foreach ($cartGoodsList as $key => $value) {
            $goodsInfo = $cartGoodsList[$key];
            $goodsId = $goodsInfo['goodsId'];
            $shopId = $cartGoodsList[$key]['shopId'];
            $goodsCnt = 0;//原有购物车数据上新增的购买数量
            $skuId = $goodsInfo['skuId'];
            if ($param['dataType'] == 1) {
                //如果是前置仓模式,失效非当前门店的商品
                if ($shopId != $current_shopId) {
                    $saveCartData = array(
                        'cartId' => $goodsInfo['cartId'],
                        'isCheck' => 0,
                    );
                    $cartModule->saveCart($saveCartData);
                    $cartGoodsList[$key]['errorMsg'] = '非当前门店商品';
                    $cartGoodsList[$key]['isCheck'] = 0;
                    $overTimeGoods[] = $cartGoodsList[$key];
                    continue;
                }
            }
//            $cartGoodsList[$key]["shopcm"] = self::getCartShops($shopId);
            $cartGoodsList[$key]["shopcm"] = $shopListMap[$shopId];
//            $verificationCartGoods = $submitModel->verificationCartGoods($userId, $goodsId, $goodsCnt, $goodsAttrId, $skuId);//验证购物车商品状态的有效性
            $goodsListMapRow = $goodsListMap[$goodsId];
            $skuRow = [];
            if ($value['skuId'] > 0) {
                $skuRow = $skuListMap[$value['skuId']];
            }
            $verificationCartGoods = $cartModule->verificationCartGoodsStatusNew($users_detail, $goodsListMapRow, $shopListMap[$shopId], $goodsCnt, $skuRow, $value);//验证购物车商品状态的有效性
            if ($verificationCartGoods['code'] == -1) {
                $saveCartData = array(
                    'cartId' => $goodsInfo['cartId'],
                    'isCheck' => 0,
                );
                $cartModule->saveCart($saveCartData);
                $cartGoodsList[$key]['errorMsg'] = $verificationCartGoods['msg'];
                $cartGoodsList[$key]['isCheck'] = 0;
                $overTimeGoods[] = $cartGoodsList[$key];
                continue;
            }
            $goodsCnt = (float)$goodsInfo['goodsCnt'];//原有购物车数据上购买数量,不要乱删
//            $verificationStock = $submitModel->verificationStock($goodsId, $goodsCnt, $skuId);//验证商品库存是否充足
            $verificationStock = $cartModule->verificationCartGoodsStockNew($goodsListMapRow, $goodsCnt, $skuRow);
            if ($verificationStock['code'] == -1) {
                $saveCartData = array(
                    'cartId' => $goodsInfo['cartId'],
                    'isCheck' => 0,
                );
                $cartModule->saveCart($saveCartData);
                $cartGoodsList[$key]['errorMsg'] = $verificationStock['msg'];
                $cartGoodsList[$key]['isCheck'] = 0;
                $overTimeGoods[] = $cartGoodsList[$key];
                continue;
            }
            $cartGoodsList[$key]['goodsCnt'] = $goodsCnt;
//            $cartGoodsList[$key]['unit'] = $goodsModule->getGoodsUnitByParams($goodsId, $skuId);
            $unit = $goodsListMapRow['unit'];
            if (!empty($skuRow)) {
                $unit = $skuRow['unit'];
            }
            $cartGoodsList[$key]['unit'] = $unit;
        }
        unset($key);
        unset($value);
        $overTimeGoods = array_values($overTimeGoods);
        $handleGoodsData = [];
        foreach ($cartGoodsList as $key => $val) {
            $handleGoodsData[$val["shopId"]][] = $val;//根据店铺id归类
        }
        unset($key);
        unset($val);
        $result = [];
        foreach ($handleGoodsData as $hkey => $hval) {
            foreach ($hval as $sonkey => $sonval) {
                $result["shopcm"][$sonval["shopcm"]["shopId"]] = $sonval["shopcm"];//过滤shopId相同的店铺
                $result["goods"][] = $sonval;
            }
        }
        $result["shopcm"] = array_values($result["shopcm"]);//店铺 数据重新排序
        foreach ($result['goods'] as $key => $val) {
            $result["goods"][$key]['shopName'] = $val['shopcm']['shopName'];
            unset($result["goods"][$key]['shopcm']);
        }
        $result['goods'] = getCartGoodsSku($result['goods']);
        if (!empty($result['goods'])) {
            foreach ($result['goods'] as $k => $v) {
//                $shopData = self::getCartShops($v['shopId']);
                $shopData = $shopListMap[$v['shopId']];
                if (empty($shopData)) unset($result['goods'][$k]);
            }
            if (!empty($overTimeGoods)) {
                foreach ($overTimeGoods as $value) {
                    foreach ($result['goods'] as $key => $val) {
                        if ($val['goodsId'] == $value['goodsId'] && $val['isCheck'] == 0) {
                            unset($result['goods'][$key]);
                        }
                    }
                }
                $result['goods'] = array_values($result['goods']);
            }
        }
        //清理数据格式
        foreach ($result['shopcm'] as $key => $val) {
            if (empty($val['shopId'])) {
                unset($result['shopcm'][$key]);
            }
        }
        $result["shopcm"] = array_values($result["shopcm"]);
        //PS:上面已经存在的逻辑就不做修改了
        //处理购物车的选中状态和是否全选(后加,兼容多商户) ---start
        $checkboxShop = $result['shopcm'];
        $checkboxGoods = $result['goods'];
        if (!empty($checkboxShop)) {
            $checkAllNum = 0;//购物车全选的数量
            foreach ($checkboxShop as $key => $val) {
                $countGoodsCnt = 0;//统计单个店铺中购买的商品数量(按wst_cart表中的goodsCnt字段统计)
                $countShopGoods = 0;//统计购物车单个店铺的商品数量
                $shopCheckAll = 0;//是否选中单个店铺全部商品(0:否|1:是)
                $shopCheckNum = 0;//选中店铺商品的数量
                foreach ($checkboxGoods as $gkey => $gval) {
                    $checkboxShop[$key]['checkAll'] = 0;//店铺是否全选(0:否|1:是)
                    if ($gval['shopId'] == $val['shopId']) {
                        if ($gval['isCheck'] == 1) {
                            $shopCheckNum += 1;
                        }
                        $countShopGoods += 1;
                        $countGoodsCnt += $gval['goodsCnt'];
                    }
                }
                if ($countShopGoods == $shopCheckNum) {
                    $checkAllNum += 1;
                    $shopCheckAll = 1;
                }
                $checkboxShop[$key]['count'] = $countGoodsCnt;//购买数量统计(按wst_cart表中goodsCnt字段统计)
                $checkboxShop[$key]['goodsCount'] = $countShopGoods;//商品数量(按商品分)
                $checkboxShop[$key]['checkAll'] = $shopCheckAll;
                $checkboxShop[$key]['checkNum'] = $shopCheckNum;
                $receiveCouponWhere = [];
                $receiveCouponWhere['userId'] = $userId;
                $receiveCouponWhere['shopId'] = $val['shopId'];
                $shopCoupons = $this->receiveShopCoupon($receiveCouponWhere);//经过处理后的可用店铺优惠券
                $checkboxShop[$key]['couponList'] = [];
                if ($shopCoupons['code'] != -1) {
                    $checkboxShop[$key]['couponList'] = $shopCoupons['data'];
                }
            }
        }
        //处理购物车的选中状态和是否全选(后加,兼容多商户) ---end
        //返回结果集
        if ($param['dataType'] == 1) {
            //此区间代码仅仅是为了区分前置仓和多商户返回的数据更精准
            foreach ($checkboxShop as $key => $val) {
                if ($val['shopId'] != $current_shopId) {
                    unset($checkboxShop[$key]);
                }
            }
            $checkboxShop = array_values($checkboxShop);
            foreach ($result['goods'] as $key => $val) {
                if ($result['goods'][$key]['shopId'] != $current_shopId) {
                    unset($result['goods'][$key]);
                }
            }
        }
        //应前端要求,处理失效商品的格式
        if (!empty($overTimeGoods)) {
            $overTimeGoods = arrayUnset($overTimeGoods, 'cartId');
            $handleOverTimeGoods = $checkboxShop;
            foreach ($handleOverTimeGoods as $key => $value) {
                $handleOverTimeGoods[$key]['goods'] = [];
                foreach ($overTimeGoods as $val) {
                    if ($param['dataType'] == 1) {
                        //前置仓把所有失效商品都返回到当前门店
                        $handleOverTimeGoods[$key]['goods'][] = $val;
                    } else {
                        if ($val['shopId'] == $value['shopId']) {
                            $handleOverTimeGoods[$key]['goods'][] = $val;
                        }
                    }
                }
                if (count($handleOverTimeGoods[$key]['goods']) <= 0) {
                    unset($handleOverTimeGoods[$key]);
                }
            }
            $handleOverTimeGoods = array_values($handleOverTimeGoods);
        }
        $response = [];
        $checkNum = 0;//已选中的购物车商品数量
        $response['shopcm'] = (array)$checkboxShop;
        $response['count'] = 0;//商品购买总数(wst_cart中的goodsCnt字段)
        $response['goodsCount'] = 0;//商品总数(按商品分)
        $response['checkAll'] = 0;//是否全选(0:否|1:是)
        $response['checkNum'] = $checkNum;
        $response['goods'] = (array)array_values($result['goods']);
        $response['overTimeGoods'] = (array)$handleOverTimeGoods;
        $response['totalPrice'] = 0;//商品价格统计
//        $cartModel = D('V3/Cart');
//        $cartModel = new CartModel();
//        $response['goods'] = $cartModel->handleGoodsMemberPrice($response['goods'], $userId);//处理购物车商品的价格,涉及价格变动,最终以shopPrice字段呈现
//        //限时购活动------限量购活动-----不是则返回上一个获取的数据
//        $response['goods'] = $cartModel->activePriceReplaceShopPrice($response['goods']);//处理购物车商品的价格,涉及价格变动,最终以shopPrice字段呈现
        $goodsModule = new GoodsModule();
        $goodsModule->filterGoods($response['goods']);
        if (empty($response['goods'])) {
            $response['count'] = 0;
        }
        foreach ($response['goods'] as $val) {
            $response['count'] += $val['goodsCnt'];
            $response['goodsCount'] += 1;
            $response['totalPrice'] += $val['shopPrice'] * $val['goodsCnt'];
        }
        //多商户
        foreach ($checkboxShop as $key => $val) {
            $checkNum += $val['checkNum'];
        }
        $totalNum = count($checkboxShop);
        if ($checkAllNum > 0 && $totalNum == $checkAllNum && count($response['goods']) > 0) {
            $response['checkAll'] = 1;
        }
        $response['checkNum'] = $checkNum;
        //该标注以上的代码已经达到兼容原来的前置仓和多商户的目的了
        $shops = $checkboxShop;
        $goods = $response['goods'];
        foreach ($shops as $key => $val) {
            $shops[$key]['goods'] = [];
            foreach ($goods as $gkey => $gval) {
                if ($val['shopId'] == $gval['shopId']) {
                    $shops[$key]['goods'][] = $gval;
                }
            }
        }
        $response['shopcm'] = (array)$shops;
        //unset($response['goods']);
        if ($param['dataType'] == 1) {
            //前置仓
            if (!empty($response['overTimeGoods'])) {
                $shopcm = $response['overTimeGoods'][0];
                if (!empty($shopcm['goods'])) {
                    $response['overTimeGoods'] = $shopcm['goods'];
                }
            }
        }


        return $response;
    }

    /*
	static function getCartGoods($goodsId){//pdo下请使用参数绑定提高效率
		$where["goodsId"] = $goodsId;
		$where["goodsStatus"] = 1;
		$where["goodsFlag"] = 1;
		$goods = M("goods")->where($where)->field(array("goodsId","goodsName","goodsThums","shopId","shopPrice","goodsStock"))->find();
		return $goods;
	} */

    static function getCartShops($shopId)
    {
        $where["shopId"] = $shopId;
        $where["shopStatus"] = 1;
        $where["shopFlag"] = 1;
        //$shops = M("shops")->where($where)->field(array('shopName','deliveryType','deliveryMoney','shopId','shopImg','deliveryStartMoney'))->find();
        $shops = M("shops")->where($where)->find();
        //$shops = M("shops")->where($where)->find();
        return $shops;
    }

    /**
     * 复制上面的
     * 删除购物车商品
     * */
    public function delCartGoods($loginName, $cartId)
    {
        $cartIdArray = explode(",", $cartId);
        $userInfo = M("users")->where("loginName = '{$loginName}'")->find();
        $where["userId"] = $userInfo["userId"];
        $where["userFlag"] = $userInfo["userId"];
        $cartTab = M('cart');
        foreach ($cartIdArray as $k => $v) {
            $where = [];
            $where['userId'] = $userInfo['userId'];
            $where["cartId"] = $v;
            $cartInfo = $cartTab->where($where)->find();
            if (!$cartInfo) {
                $info = 'cartId: ' . $v . '和当前用户id不匹配';
                $statusCode = returnData(null, -1, 'error', '删除失败', $info);
                return $statusCode;
            }
            $res = $cartTab->where($where)->delete();
        }
        if ($res) {
            $statusCode["statusInfo"] = "删除成功";
            $statusCode = returnData($statusCode);
            return $statusCode;
        } else {
            $statusCode = returnData(null, -1, 'error', '删除失败');
            return $statusCode;
        }
    }

    //获取用户默认收货地址
    public function address($loginName)
    {
        $userId = M("users")->where("loginName = '{$loginName}'")->field(array("userId"))->find();
        $where["userId"] = $userId["userId"];
        $where["isDefault"] = 1;
        $where["addressFlag"] = 1;
        $getress = M("user_address")->where($where)->find();//获取用户地址
        if (empty($getress)) {
            return array();
        }

        $where1["communityId"] = $getress["communityId"];
        $where1["isShow"] = 1;
        $where1["communityFlag"] = 1;
        $communitys = M("communitys")->where($where1)->field(array("communityName"))->find();//获取用户社区名称


        $getress["areaIdCode1"] = $getress["areaId1"];
        $getress["areaIdCode2"] = $getress["areaId2"];
        $getress["areaIdCode3"] = $getress["areaId3"];


        $getress["communityName"] = $communitys["communityName"];

        $where2["areaId"] = $getress["areaId1"];
        $areas = M("areas");
        $areaId1 = $areas->where($where2)->field(array("areaName"))->find();

        $where2["areaId"] = $getress["areaId2"];
        $areaId2 = $areas->where($where2)->field(array("areaName"))->find();

        $where2["areaId"] = $getress["areaId3"];
        $areaId3 = $areas->where($where2)->field(array("areaName"))->find();

        $getress["areaId1"] = $areaId1["areaName"];
        $getress["areaId2"] = $areaId2["areaName"];
        $getress["areaId3"] = $areaId3["areaName"];

        return $getress;
    }

    //获取用户所有地址
    public function getAllAddress($param)
    {
        $userId = $param['userId'];
        $shopId = trim($param['shopId'], ',');
        $shopIdArr = explode(',', $shopId);
        $where["addressFlag"] = 1;
        $where["userId"] = $userId;
        $getress = M("user_address")->where($where)->select();//获取用户地址
        for ($i = 0; $i < count($getress); $i++) {
            $getress[$i]["areaIdCode1"] = $getress[$i]["areaId1"];
            $getress[$i]["areaIdCode2"] = $getress[$i]["areaId2"];
            $getress[$i]["areaIdCode3"] = $getress[$i]["areaId3"];
            $getress[$i]["communityId"] = self::getCommunity($getress[$i]["communityId"]);
            $getress[$i]["areaId1"] = self::getAreaName($getress[$i]["areaId1"]);
            $getress[$i]["areaId2"] = self::getAreaName($getress[$i]["areaId2"]);
            $getress[$i]["areaId3"] = self::getAreaName($getress[$i]["areaId3"]);
            $getress[$i]["isUse"] = 1;//0:不可用 1：可用
            if (empty($shopId) || empty($getress[$i]["lng"]) || empty($getress[$i]["lat"])) {
                $getress[$i]["isUse"] = 1;
            } else {
                //判断是否在配送范围内
                $getress[$i]["isUse"] = 0;
                foreach ($shopIdArr as $val) {
                    $dcheck = checkShopDistribution($val, $getress[$i]["lng"], $getress[$i]["lat"]);
                    if ($dcheck) {
                        $getress[$i]["isUse"] = 1;
                    }
                }
            }
        }
        return (array)$getress;
    }

    //获取id社区名称
    static function getCommunity($communityId)
    {
        $where1["communityId"] = $communityId;
        $where1["isShow"] = 1;
        $where1["communityFlag"] = 1;
        $mod = M("communitys")->where($where1)->field(array("communityName"))->find();//获取用户社区名称
        $communityName = '';
        if (!empty($mod["communityName"])) {
            $communityName = $mod['communityName'];
        }
        return $communityName;
    }

    //获取城市名字
    static function getAreaName($areaIdx)
    {
        $areas = M("areas");
        $where2["areaId"] = $areaIdx;
        $areaIds = $areas->where($where2)->field(array("areaName"))->find();
        return $areaIds["areaName"];
    }

    //用于联动查询城市
    public function cityQuery($parentId)
    {
        $areas = M("areas");
        $where["parentId"] = $parentId;
        /* switch($parentId)
		{
			case "310000"://上海
			$where["parentId"] =  "310100";
			break;

			case "110000"://北京
			$where["parentId"] =  "110100";
			break;

			case "500000"://重庆
			$where["parentId"] =  "500100";
			break;

			case "120000"://天津
			$where["parentId"] =  "120100";
			break;

			default:
			$where["parentId"] =  $parentId;
		} */

        $areaIds = $areas->where($where)->field(array("areaName", "areaId", "areaType", "parentId"))->select();
        return $areaIds;
    }

    //根据第三级城市查询社区
    public function getAreaIdCommunityName($areaId3)
    {
        $where["areaId3"] = $areaId3;
        $where["isShow"] = '1';
        $where["communityFlag"] = '1';
        $mod = M("communitys")->where($where)->field(array("communityName", "communityId"))->select();
        return $mod;
    }

//    //更新用户收货地址 isDefault为空不改 0取消默认 1默认
//    public function updataAddress($loginName, $addressId, $userName, $userPhone, $areaId1, $areaId2, $areaId3, $communityId, $address, $isDefault, $lat, $lng, $setaddress)
//    {
//        $where["loginName"] = $loginName;
//        $where["userFlag"] = 1;
//        $user = M("users")->where($where)->field(array("userId"))->find();
//
//        $where1["userId"] = $user["userId"];
//        $where1["addressId"] = $addressId;
//        $user_address = M("user_address");
//
//        $data["lng"] = $lng;
//        $data["lat"] = $lat;
//        $data["userName"] = $userName;
//        $data["userPhone"] = $userPhone;
//        $data["areaId1"] = $areaId1;
//        $data["areaId2"] = $areaId2;
//        $data["areaId3"] = $areaId3;
//        $data["communityId"] = $communityId;
//        $data["address"] = $address;
//        $data["setaddress"] = $setaddress;
//        $data = array_filter($data);//去除空数组
//        $data["isDefault"] = $isDefault;
//        $isDe = array("0", "1");
//        $isDe = in_array($data["isDefault"], $isDe);
//        if ($isDe !== true) {
//            unset($data["isDefault"]);
//        }
//        $mod = $user_address->where($where1)->save($data);
//        if ($mod) {
//            if ($data["isDefault"] == "1") {
//                $data1["isDefault"] = "0";
//                $user_address->where("isDefault = '1' and userId = '{$user["userId"]}' and addressId != '{$addressId}'")->save($data1);
//            }
////            $statusCode["statusCode"] = "000041";
//            $statusCode["statusInfo"] = "更新成功";
//            $statusCode = returnData($statusCode);
//            return $statusCode;
//        } else {
////            $statusCode["statusCode"] = "000042";
//            $statusCode = returnData(null, -1, 'error', '更新失败或者无需修改信息一样');
//            return $statusCode;
//        }
//    }

    /**
     * 更新用户收货地址 注:重写上面注释的方法
     * @param array $params
     * @return array
     * */
    public function updataAddress(array $params)
    {
        $user_address = M("user_address");
        $data = array(
            'userId' => null,
            'userName' => null,
            'userPhone' => null,
            'areaId1' => null,
            'areaId2' => null,
            'areaId3' => null,
            'communityId' => null,
            'address' => null,
            'isDefault' => null,
            'lng' => null,
            'lat' => null,
            'setaddress' => null,
        );
        parm_filter($data, $params);
        $isDe = array("0", "1");
        $isDe = in_array($data["isDefault"], $isDe);
        if ($isDe !== true) {
            unset($data["isDefault"]);
        }
        $addressId = $params['addressId'];
        $userId = $params['userId'];
        $res = $user_address->where(array(
            'addressId' => $addressId,
            'userId' => $userId,
        ))->save($data);
        if ($res !== false) {
            if ($data["isDefault"] == 1) {
                $data1["isDefault"] = 0;
                $user_address->where("isDefault = 1 and userId = '{$userId}' and addressId != '{$addressId}'")->save($data1);
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '更新成功');
        } else {
//            $statusCode["statusCode"] = "000042";
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败');
        }
    }

    //获取单个地址信息
    public function getOneAddress($loginName, $addressId)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1["addressFlag"] = 1;
        $where1["userId"] = $user["userId"];
        $where1["addressId"] = $addressId;
        $mod = M("user_address")->where($where1)->find();
        if ($mod) {
            return $mod;
        } else {
//            $statusCode["statusCode"] = "000043";
            $statusCode = returnData(null, -1, 'error', '获取失败');
            return $statusCode;
        }
    }

    //删除用户某个收货地址
    public function delUserAddress($loginName, $addressId)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1["addressFlag"] = 1;
        $where1["userId"] = $user["userId"];
        $where1["addressId"] = $addressId;

        $data["addressFlag"] = -1;
        $mod = M("user_address")->where($where1)->save($data);
        if ($mod) {
//            $statusCode["statusCode"] = "000044";
            $statusCode["statusInfo"] = "删除成功";
            $statusCode = returnData($statusCode);
            return $statusCode;
        } else {
//            $statusCode["statusCode"] = "000045";
            $statusCode = returnData(null, -1, 'error', '删除失败');
            return $statusCode;
        }
    }

    //添加地址
    public function addUserAddress($loginName, $userName, $userPhone, $areaId1, $areaId2, $areaId3, $communityId, $address, $isDefault, $lat, $lng, $setaddress)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $data["userId"] = $user["userId"];
        $data["userName"] = $userName;
        $data["userPhone"] = $userPhone;
        $data["areaId1"] = $areaId1;
        $data["areaId2"] = $areaId2;
        $data["areaId3"] = $areaId3;
        $data["communityId"] = $communityId;
        $data["address"] = $address;
        $data["isDefault"] = $isDefault;
        $data["createTime"] = date("Y-m-d H:i:s");
        $data["lng"] = $lng;
        $data["lat"] = $lat;
        $data["setaddress"] = $setaddress;

        $isDe = array("0", "1");//是否默认 如果不在此数组直接报错
        $isDe = in_array($data["isDefault"], $isDe);
        if ($isDe !== true) {
//            return $statusCode["statusCode"] = "000046";
            $statusCode = returnData(null, -1, 'error', '默认地址的值 只能为0或者1 你的值违法！');
            return $statusCode;
        }

        $user_address = M("user_address");
        $mod = $user_address->add($data);
        if ($data["isDefault"] == "1") {//如果添加的是1默认地址 把用户当前所有地址重置为0
            $data1["isDefault"] = "0";
            $user_address->where("isDefault = '1' and userId = '{$user["userId"]}' and addressId != '{$mod}'")->save($data1);
        }

        if ($mod) {
//            $statusCode["statusCode"] = "000047";
            $statusCode["statusInfo"] = "添加成功";
            $statusCode = returnData($statusCode);
            return $statusCode;
        } else {
//            $statusCode["statusCode"] = "000048";
            $statusCode = returnData(null, -1, 'error', '添加失败');
            return $statusCode;
        }
    }

    //获取用户头像和昵称
    public function getUserNameImg($loginName)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId", "userName", "userPhoto"))->find();
        if (empty($user["userPhoto"])) {
            $user["userPhoto"] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
        }

        return $user;
    }

    //获取用户资料
    public function getUserInfor($loginName)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        //$user = M("users")->where($where)->field(array("userId","userName","userPhoto","userSex","userQQ","userPhone","userEmail",'userScore'))->find();
        $user = M("users")->where($where)->find();
        if (empty($user["userPhoto"])) {
            $user["userPhoto"] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
        }
        $invitation = M('distribution_relation')->where("userId='" . $user['userId'] . "' AND distributionLevel=1")->find();
        $user['invitationUserId'] = (int)$invitation['userId'];//邀请人用户id
        $user['invitationUserName'] = (string)$user['userName'];//邀请人用户昵称
        $user['invitationUserPhone'] = (string)$user['userPhone'];//邀请人用户手机号
        $user['invitationUserPhoto'] = (string)$user['userPhoto'];//邀请人用户头像
        if ($invitation['pid'] != $user['userId'] && $invitation['pid'] > 0) {
            //存在上级邀请人
            $invitationInfo = M('users')->where("userId='" . $invitation['pid'] . "'")->find();
            $user['invitationUserName'] = (string)$invitationInfo['userName'];//邀请人用户手机号
            $user['invitationUserPhone'] = (string)$invitationInfo['userPhone'];//邀请人用户手机号
            $user['invitationUserPhoto'] = (string)$invitationInfo['userPhoto'];//邀请人用户头像
        }
        $user['myInvitationNum'] = M('distribution_relation d')
            ->join("left join wst_users u on u.userId=d.userId")
            ->where("d.pid='" . $user['userId'] . "' AND d.distributionLevel=1 and u.userFlag=1")
            ->count();//此人邀请的所有人数 PS:直属下线
        $user['myInvitationNum'] = (int)$user['myInvitationNum'];

        $where = [];
        $where['userId'] = $user['userId'];
        $where['dataFlag'] = 1;
        $where['state'] = 2;
        $user['oldDistributionMoney'] = (float)M("distribution_withdraw")->where($where)->sum('money');
        //是否签到
        $time = date("Y-m-d");
//        $userScore = M("user_score")->where("userId = '".$user['userId']."' and createTime >='".$time."'")->select();//查找当前用户大于等于今天的签到记录
        if ($user['lastSignInTime'] >= $time) {
            $user['isSign'] = 1;
        } else {
            $user['isSign'] = 0;
        }
        // //是否签到
        // $time = date("Y-m-d");
        // $userScore = M("user_score")->where("userId = '".$user['userId']."' and createTime >='".$time."'")->select();//查找当前用户大于等于今天的签到记录
        // if($userScore){
        //     $user['isSign'] = 1;
        // }else{
        //     $user['isSign'] = 0;
        // }
        //是否是有效绿卡会员
        if (!empty($user['expireTime']) && $user['expireTime'] > date('Y-m-d H:i:s', time())) {
            $user['isMember'] = 1;
        } else {
            $user['isMember'] = 0;
        }
        $user['pullNewUrl'] = '';//地推码链接
        $user['pullNewAmount'] = 0;//拉新收入金额-总收入金额
        $user['pullNewAmountPending'] = 0;//拉新收入金额-待入账金额
        $user['pullNewAmountSolve'] = 0;//拉新收入金额-已入账金额
        if ($user['pullNewPermissions'] == 1) {
            $time = time();
            //dataType 【1：分销|2：地推】
            $user['pullNewUrl'] = WSTDomain() . "/h5/#/marketing?userId={$user['userId']}&dataType=2";
            $pullAmountTab = M('pull_new_amount_log');
            $where = [];
            $where['inviterId'] = $user['userId'];
            $user['pullNewAmount'] = $pullAmountTab->where($where)->sum('amount');
            $where = [];
            $where['inviterId'] = $user['userId'];
            $where['status'] = 1;
            $user['pullNewAmountSolve'] = $pullAmountTab->where($where)->sum('amount');
            $user['pullNewAmountPending'] = $user['pullNewAmount'] - $user['pullNewAmountSolve'];
        }
        $user['pullNewAmount'] = formatAmount($user['pullNewAmount']);
        $user['pullNewAmountPending'] = formatAmount($user['pullNewAmountPending']);
        $user['pullNewAmountSolve'] = formatAmount($user['pullNewAmountSolve']);
        return $user;
    }

    //更新用户昵称
    public function saveUserName($loginName, $UserName)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $users = M("users");
        $data["userName"] = $UserName;
        $status = $users->where($where)->save(array_filter($data));
        if ($status) {
//            return $statusCode["statusCode"] = "000049";
            return returnData();
        } else {
//            return $statusCode["statusCode"] = "000050";
            $statusCode = returnData(null, -1, 'error', '更新用户昵称失败 或者是用户未更改');
            return $statusCode;
        }
    }

    //更新真实姓名
    public function saveRealName($loginName, $realName)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $users = M("users");
        $data["realName"] = $realName;
        $status = $users->where($where)->save(array_filter($data));
        if ($status) {
//            return $statusCode["statusCode"] = "000049";
            return returnData();
        } else {
//            return $statusCode["statusCode"] = "000050";
            $statusCode = returnData(null, -1, 'error', '更新真实姓名失败 或者是用户未更改');
            return $statusCode;
        }
    }

    //更新用户性别
    public function saveUserSex($loginName, $UserSex)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $users = M("users");
        $data["userSex"] = $UserSex;

        $isDe = array("1", "2", "3");//是否默认 如果不在此数组直接报错
        $isDe = in_array($data["userSex"], $isDe);
        if ($isDe !== true) {
//            return $statusCode["statusCode"] = "000053";
            $statusCode["statusInfo"] = "更新成功";
            $statusCode = returnData($statusCode);
            return $statusCode;
        }

        $status = $users->where($where)->save(array_filter($data));
        if ($status) {
//            return $statusCode["statusCode"] = "000051";
            $statusCode["statusInfo"] = "更新成功";
            $statusCode = returnData($statusCode);
            return $statusCode;
        } else {
//            return $statusCode["statusCode"] = "000052";
            return returnData(null, -1, 'error', '更新失败或者用户未更改');
        }
    }

    //更新用户QQ
    public function saveUserQQ($loginName, $userQQ)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $users = M("users");
        $data["userQQ"] = $userQQ;
        $status = $users->where($where)->save(array_filter($data));
        if ($status) {
//            return $statusCode["statusCode"] = "000054";
            $statusCode["statusInfo"] = "更新成功";
            return returnData($statusCode);
        } else {
//            return $statusCode["statusCode"] = "000055";
            return returnData(null, -1, 'error', '更新失败或者用户未更改');
        }
    }

    /**
     * 更新用户手机号
     * @param int $userId 用户id
     * @param varchar userPhone 用户手机号
     * @param varchar verificationCode 短信验证码
     * */
//    public function saveUserPhone($userId, $userPhone, $verificationCode)
//    {
//        $where = [];
//        $where["userId"] = $userId;
//        $where["userFlag"] = 1;
//        $usersTab = M("users");
//        $userInfo = $usersTab->where($where)->find();
//        if ($userInfo['userPhone'] == $userPhone) {
//            $apiRet = returnData(false, -1, 'error', '新手机号不能和旧手机号相同');
//            return $apiRet;
//        }
//        $saveData = [];
//        $saveData["userPhone"] = $userPhone;
//        //添加新手机是否已绑定验证
//        $checkPhone = $usersTab->where(['userPhone' => $saveData["userPhone"]])->find();
//        if ($checkPhone) {
//            $apiRet = returnData(false, -1, 'error', '该手机号已绑定,请更换手机号操作');
//            return $apiRet;
//        }
//        //短信验证码校验
//        $smsTab = M('log_sms');
//        $whereSmsInfo = [];
//        $whereSmsInfo['dataFlag'] = 1;
//        $whereSmsInfo['smsCode'] = $verificationCode;
//        $whereSmsInfo['smsPhoneNumber'] = $userPhone;
//        $smsInfo = $smsTab->where($whereSmsInfo)->order("smsId DESC")->limit(1)->find();
//
////        if (!$smsInfo) {
////            $apiRet = returnData(false, -1, 'error', '验证码不正确');
////            return $apiRet;
////        }
//        //if ($smsInfo && (strtotime($smsInfo['createTime']) + 1800) < time()) {
//        if (!$smsInfo || ((time() - strtotime($smsInfo['createTime'])) > 1800)) {
//            $apiRet = returnData(false, -1, 'error', '验证码已经失效');
//            return $apiRet;
//        }
//
//        $status = $usersTab->where($where)->save($saveData);
//        if ($status) {
//            $smsTab->where(['smsId' => $smsInfo['smsId']])->save(['dataFlag' => -1]);
//            return returnData(true);
//        } else {
//            return returnData(false, -1, 'error', '更新用户手机号失败');
//        }
//    }

    /**
     * 更新用户手机号
     * int $userId 用户id
     * string userPhone 手机号
     * string smsCode 验证码
     * @return array
     * */
    public function saveUserPhone(int $userId, string $userPhone, string $smsCode)
    {
        $response = LogicResponse::getInstance();
        $users_service_module = new UsersServiceModule();
        $user_info_result = $users_service_module->getUsersDetailById($userId);
        $user_info_data = $user_info_result['data'];
        if (!is_mobile($userPhone)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('手机号格式不正确')->toArray();
        }
        if ($user_info_data['userPhone'] == $userPhone) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('新手机号不能和旧手机号相同')->toArray();
        }
        $log_service_module = new LogServiceModule();
        $where = array(
            'smsPhoneNumber' => $userPhone,
            'smsCode' => $smsCode,
        );
        $sms_result = $log_service_module->getLogSmsInfo($where);
        if ($sms_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确')->toArray();
        }
        $sms_data = $sms_result['data'];
        $sms_enum = new SmsEnum();
        $sms_state = $sms_enum->getSmsState();
        if ((time() - strtotime($sms_data['createTime'])) > SmsEnum::OUTTIME) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($sms_state[SmsEnum::OUTTIME])->toArray();
        }
        $model = M();
        $model->startTrans();
        $save = array(
            'userPhone' => $userPhone
        );
        $save_result = $users_service_module->updateUsersInfo($userId, $save, $model);
        if ($save_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改失败')->toArray();
        }
        //更新相关店铺的手机号
        $users_model = new UsersModel();
        $where = array(
            'userPhone' => $user_info_data['userPhone'],
            'userFlag' => 1,
        );
        $users_list = $users_model->where($where)->field('userId')->select();
        $users_id = array_column($users_list, 'userId');
        $shop_model = new ShopsModel();
        $where = array(
            'userId' => array('IN', $users_id),
            'shopFlag' => 1
        );
        $shop_list = $shop_model->where($where)->field('userId,shopId')->select();
        if (!empty($shop_list)) {
            $save = array(
                'userPhone' => $userPhone
            );
            foreach ($shop_list as $value) {
                $result = $users_service_module->updateUsersInfo($value['userId'], $save, $model);
                if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                    $model->rollback();
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改失败')->toArray();
                }
            }
        }
        $log_service_module->destructionSms($sms_data['smsId']);//销毁验证码
        $model->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('修改成功')->toArray();
    }

    //更新用户邮箱
    public function saveUserEmail($loginName, $userEmail)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $users = M("users");
        $data["userEmail"] = $userEmail;
        $status = $users->where($where)->save(array_filter($data));
        if ($status) {
//            return $statusCode["statusCode"] = "000058";
            $statusCode["statusInfo"] = "更新成功";
            return returnData($statusCode);
        } else {
//            return $statusCode["statusCode"] = "000059";
            return returnData(null, -1, 'error', '更新失败或者用户未更改');
        }
    }

    //获取商品收藏
    public function getFavoritesGoods($loginName)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1["userId"] = $user["userId"];
        $where1["favoriteType"] = "0";
        $mod = M("favorites")->where($where1)->field(array("targetId"))->select();

        $goods = M("goods");
        for ($i = 0; $i < count($mod); $i++) {
            $where2["goodsId"] = $mod[$i]['targetId'];
            $goodsData = $goods->where($where2)->find();
            $mod[$i] = $goodsData;
            /*$mod[$i]["goodsName"] =  $goodsData["goodsName"];
            $mod[$i]["goodsThums"] =  $goodsData["goodsThums"];
            $mod[$i]["shopPrice"] =  $goodsData["shopPrice"];
            $mod[$i]["goodsId"] =  $goodsData["goodsId"];
            $mod[$i]["goodsSpec"] =  $goodsData["goodsSpec"];
            $mod[$i]["memberPrice"] =  $goodsData["memberPrice"];
            $mod[$i]["marketPrice"] =  $goodsData["marketPrice"];
            $mod[$i]["goodsStock"] =  $goodsData["goodsStock"];*/
            unset($mod[$i]["targetId"]);
        }
        $mod = rankGoodsPrice($mod);
        return (array)$mod;
    }

    //获取店铺收藏
    public function getFavoritesShops($loginName)
    {
        $where = [];
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(['userId'])->find();
        $where = [];
        $where["userId"] = $user["userId"];
        $where["favoriteType"] = "1";
        $res = M("favorites")->where($where)->field(["targetId"])->select();
        foreach ($res as $key => $value) {
            $where = "s.shopId='{$value['targetId']}'";
            $field = 's.shopId,s.shopName,s.shopCompany,s.shopImg,s.shopTel,s.shopAddress,s.avgeCostMoney,s.deliveryStartMoney,s.deliveryMoney,s.deliveryFreeMoney,s.deliveryCostTime,s.deliveryTime,s.serviceStartTime,s.serviceEndTime,s.deliveryType';
            $field .= ",(select sum(og.goodsNums) from __PREFIX__order_goods og  left join __PREFIX__orders o on o.orderId=og.orderId where o.shopId=s.shopId and o.isPay=1 ) as shopSale ";//销量
            $field .= ",(select totalScore from __PREFIX__shop_scores score where score.shopId=s.shopId) as shopScore ";//评分
            $sql = "select {$field} from __PREFIX__shops s where $where ";
            $shopInfo = $this->queryRow($sql);
            if (empty($shopInfo['shopId'])) {
                unset($res[$key]["targetId"]);
            }
            $shopInfo['shopScore'] = $this->getShopScores($shopInfo['shopId'])['data']['zfScore'];
            $res[$key] = $shopInfo;
        }
        return array_values($res);
    }

    //订单-待付款
    public function pendingPayment($loginName)
    {
        $config = $GLOBALS['CONFIG'];
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        //$where1['payType'] = 1;
        $where1['isPay'] = 0;
        $where1['userId'] = $user["userId"];
        $where1['orderFlag'] = 1;
        $where1['userDelete'] = 1;
        $where1['orderStatus'] = -2;

        $shopId = I('shopId', 0, 'intval');
        if (!empty($shopId)) $where1['shopId'] = $shopId;

        $res_orders_list = (array)M("orders")
            // ->join('LEFT JOIN wst_order_merge m ON m.id=c.menuId')
            ->where($where1)->field(array("orderId", "orderNo", "shopId", "realTotalMoney", 'orderStatus', 'createTime', 'isSelf', 'isPay', 'orderToken', 'setDeliveryMoney', 'singleOrderToken', 'needPay'))->order('createTime desc')->select();

        $mod_order_goods = M("order_goods");
        $mod_goods = M("goods");
        $mod_shops = M("shops");

        //获取订单下所有的商品

        unset($field_name);
        $field_name = array(
            'goodsImg',
            'shopId',
            'goodsStock',
            'saleCount',
            'goodsUnit',
            'goodsSpec',
            'SuppPriceDiff'
        );

        $rePayTime = $config['rePayTime'];
        if (empty($rePayTime)) {
            $rePayTime = 5;
        }
        $overTime = $rePayTime * 60;//订单超时时间
        $goodsModule = new GoodsModule();
        for ($i = 0; $i < count($res_orders_list); $i++) {
            $res_orders_list[$i]['overTime'] = strtotime($res_orders_list[$i]['createTime']) + $overTime;
            $mergeInfo = M('order_merge')->where(['orderToken' => $res_orders_list[$i]['orderToken']])->find();
            if ($mergeInfo) {
                $orderNoArr = explode('A', $mergeInfo['value']);
                if (count($orderNoArr) > 1) {
                    $res_orders_list[$i]['orderToken'] = $res_orders_list[$i]['singleOrderToken'];
                }
            }
            $res_orders_list[$i]['assembleState'] = 3;//拼团状态，正常订单
            $user_activity_relation_info = M('user_activity_relation')->where(array('orderId' => $res_orders_list[$i]['orderId'], 'uid' => $user["userId"]))->find();
            if (!empty($user_activity_relation_info)) $res_orders_list[$i]['assembleState'] = $user_activity_relation_info['state'];//拼团状态，拼团订单

            $res_orders_list[$i]['goodsData'] = $mod_order_goods->where("orderId = '{$res_orders_list[$i]['orderId']}'")->select();


            //获取店铺头像 和 店铺姓名
            $mod_shop_resdata = $mod_shops->where("shopId = '{$res_orders_list[$i]['shopId']}'")->field(array('shopImg', 'shopName', 'deliveryFreeMoney', 'deliveryMoney'))->find();
            $res_orders_list[$i]['shopImg'] = $mod_shop_resdata['shopImg'];
            $res_orders_list[$i]['shopName'] = $mod_shop_resdata['shopName'];
            //给前端加字段deliveryFreeMoney
            $res_orders_list[$i]['deliveryFreeMoney'] = $mod_shop_resdata['deliveryFreeMoney'];
            if ($config['setDeliveryMoney'] == 2) {//废弃
                $res_orders_list[$i]['deliveryFreeMoney'] = $config['deliveryFreeMoney'];
                $res_orders_list[$i]['realTotalMoney'] = $res_orders_list[$i]['realTotalMoney'] + $res_orders_list[$i]['setDeliveryMoney'];
            } else {
                if ($res_orders_list['isPay'] != 1 && $res_orders_list[$i]['setDeliveryMoney'] > 0) {
                    $res_orders_list[$i]['realTotalMoney'] = $res_orders_list[$i]['realTotalMoney'] + $res_orders_list[$i]['setDeliveryMoney'];
                    $res_orders_list[$i]['deliverMoney'] = $res_orders_list[$i]['setDeliveryMoney'];
                }
            }
            for ($j = 0; $j < count($res_orders_list[$i]['goodsData']); $j++) {
                $oneGood = $mod_goods->where("goodsId = '{$res_orders_list[$i]['goodsData'][$j]['goodsId']}'")->field($field_name)->find();
                $res_orders_list[$i]['goodsData'][$j]['unit'] = $goodsModule->getGoodsUnitByParams($res_orders_list[$i]['goodsData'][$j]['goodsId'], $res_orders_list[$i]['goodsData'][$j]['skuId']);
                $res_orders_list[$i]['goodsData'][$j]['goodsNums'] = (float)$res_orders_list[$i]['goodsData'][$j]['goodsNums'];
                $res_orders_list[$i]['goodsData'][$j]['totalGoodsPrice'] = sprintfNumber((float)$res_orders_list[$i]['goodsData'][$j]['goodsPrice'] * $res_orders_list[$i]['goodsData'][$j]['goodsNums']);
                $res_orders_list[$i]['goodsData'][$j]['SuppPriceDiff'] = $oneGood['SuppPriceDiff'];
                $res_orders_list[$i]['goodsData'][$j]['goodsImg'] = $oneGood['goodsImg'];
                $res_orders_list[$i]['goodsData'][$j]['shopId'] = $oneGood['shopId'];
                $res_orders_list[$i]['goodsData'][$j]['goodsStock'] = $oneGood['goodsStock'];
                $res_orders_list[$i]['goodsData'][$j]['saleCount'] = $oneGood['saleCount'];
                $res_orders_list[$i]['goodsData'][$j]['goodsSpec'] = $oneGood['goodsSpec'];

            }

            //sku属性,后加
            $res_orders_list[$i]['goodsData'] = getCartGoodsSku($res_orders_list[$i]['goodsData']);
        }


//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='待付款列表获取成功！';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$res_orders_list;
        $apiRet = returnData($res_orders_list);
        return $apiRet;


        /* 		for($i=0;$i<count($order);$i++){
			$where2["shopId"] =  $order[$i]["shopId"];
			$where3["orderId"] = $order[$i]["orderId"];
			$data_shops = $shops->where($where2)->field(array("shopName","shopImg"))->find();//条件没判断店铺是否还在经营 有几率会导致用户的待付款出现问题
			$order[$i]["shopName"] = $data_shops['shopName'];
			$order[$i]["shopImg"] = $data_shops['shopImg'];
			$order[$i]["order_goods"] = $order_goods->where($where3)->select();//订单下的商品
		} */
        //return $res_orders_list;
    }

    //订单-取消订单 （不适用已付款订单取消）
    public function cancelOrder($loginName, $orderId)
    {
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $userInfo = M("users")->where($where)->field(array("userId"))->find();
        $orders = M("orders");
        M()->startTrans();//开启事物
        $where = [];
        $where['isPay'] = 0;
        $where['userId'] = $userInfo["userId"];
        $where['orderFlag'] = 1;
        $where['orderStatus'] = array('in', '-2,12');
        $where['orderId'] = $orderId;
        $saveData["orderStatus"] = '-1';
        $updateRes = $orders->where($where)->save($saveData);//把订单状态改为用户取消(未受理前)
        if (!$updateRes) {//解决导致商品库存会在多次请求而增加的bug
            M()->rollback();
            return returnData(null, -1, 'error', '取消失败 或者您已经取消');
        }
        $where = [];
        $where["og.orderId"] = $orderId;
        $field = "goods.goodsId,goods.goodsName,goods.goodsStock,goods.isShopSecKill,goods.SuppPriceDiff,goods.weightG,goods.isLimitBuy,goods.limitCountActivityPrice,goods.limitCount,goods.isFlashSale,";
        $field .= "og.id,og.goodsNums,og.goodsAttrId,og.skuId";
        $orderGoods = M('order_goods og')
            ->join('left join wst_goods goods on goods.goodsId=og.goodsId')
            ->where($where)
            ->field($field)
            ->select();
        foreach ($orderGoods as $key => $value) {
            $returnOrderGoodsStock = returnOrderGoodsStock($value['id']);//取消订单归还商品库存
            if ($returnOrderGoodsStock['code'] == -1) {
                M()->rollback();
                return $returnOrderGoodsStock;
            }
        }
        //优惠券返还
        $cres = cancelUserCoupon($orderId);
        if (!$cres) {
            M()->rollback();
//            $statusCode["statusCode"] = "000062";
            //$statusCode["statusInfo"] = "确认订单成功";
            //$statusCode = returnData($statusCode);
            //return $statusCode;
            return returnData(null, -1, 'error', '订单取消失败');
        }
        //订单日志
//        $log_orders = M("log_orders");
//        $log_orders_data["orderId"] = $orderId;
//        $log_orders_data["logContent"] = "订单已取消";
//        $log_orders_data["logUserId"] = $userInfo["userId"];
//        $log_orders_data["logType"] = "0";
//        $log_orders_data["logTime"] = date("Y-m-d H:i:s");
//        $log_orders->add($log_orders_data);

        $content = "订单已取消";
        $logParams = [
            'orderId' => $orderId,
            'logContent' => $content,
            'logUserId' => $userInfo['userId'],
            'logUserName' => '用户',
            'orderStatus' => -1,
            'payStatus' => 0,
            'logType' => 0,
            'logTime' => date('Y-m-d H:i:s'),
        ];
        M('log_orders')->add($logParams);
        //END
        //返还属性库存
        //returnAttrStock($orderId);
        //返还积分
        returnIntegral($orderId, $userInfo["userId"]);
        //返回秒杀库存
        //returnKillStock($orderId);

        //更新商品限制购买记录表
        updateGoodsOrderNumLimit($orderId);

        //返还sku库存
        //returnGoodsSkuStock($orderId);

        M()->commit();
//        $statusCode["statusCode"] = "000060";
        $statusCode["statusInfo"] = "取消成功";
        $statusCode = returnData($statusCode);
        $push = D('Adminapi/Push');
        $push->postMessage(5, $userInfo["userId"]);
        return $statusCode;
    }


    //订单-取消订单  只适用于已付款订单取消  退回微信支付
    public function cancelOrderOK($userId, $orderId)
    {
        $orders = M("orders");
        //判断订单是否已经退款
        $orderInfo = $orders->where("orderId = {$orderId} and isRefund = 0")->find();
        if (!$orderInfo) {
            $statusCode = returnData(null, -1, 'error', '订单已经退款了');
            return $statusCode;
        }
        if ($orderInfo['orderStatus'] >= 1) {
            $statusCode = returnData(null, -1, 'error', '已受理订单不能取消，请联系商家');
            return $statusCode;
        }
        M()->startTrans();//开启事物
        //$where['payType'] = 1;
        $where = [];
        $where['isPay'] = 1;
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $where['orderStatus'] = array('in', '0,1,2');
        $where['orderId'] = $orderId;
        $saveData["orderStatus"] = '-6';
        $updateRes = $orders->where($where)->save($saveData);//把订单状态改为用户取消(发货前 商家未读)
        if (!$updateRes) {
            M()->rollback();
            return returnData(null, -1, 'error', '取消失败 或者您已经取消');
        }
        $where = [];
        $where["og.orderId"] = $orderId;
        $field = "goods.goodsId,goods.goodsName,goods.goodsStock,goods.isShopSecKill,goods.SuppPriceDiff,goods.weightG,";
        $field .= "og.id,og.goodsNums,og.goodsAttrId,og.skuId";
        $orderGoods = M('order_goods og')
            ->join('left join wst_goods goods on goods.goodsId=og.goodsId')
            ->where($where)
            ->field($field)
            ->select();
        foreach ($orderGoods as $key => $value) {
            $returnOrderGoodsStock = returnOrderGoodsStock($value['id'], M());//取消订单归还商品库存
            if ($returnOrderGoodsStock['code'] == -1) {
                M()->rollback();
                return $returnOrderGoodsStock;
            }
        }
        //-----------------启动退款逻辑------------------------
        if ($orderInfo['realTotalMoney'] > 0) {
            $payModule = new PayModule();
            if ($orderInfo['payFrom'] == 1) {
                //支付宝
                $aliPayRefundRes = $payModule->aliPayRefund($orderInfo['tradeNo'], $orderInfo['realTotalMoney']);
                if ($aliPayRefundRes['code'] != 0) {
                    M()->rollback();
                    return returnData(null, -1, 'error', '取消失败，支付宝退款失败');
                }
                //复制以前的代码,按照以前的逻辑写
                $orders = M('orders');
                $save_orders['isRefund'] = 1;
                $orderEdit = $orders->where("orderId = " . $orderInfo['orderId'])->save($save_orders);
                if ($orderEdit) {
                    //写入订单日志
                    $logParams = [
                        'orderId' => $orderId,
                        'logContent' => "用户取消订单，发起支付宝退款：{$orderInfo['realTotalMoney']}元",
                        'logUserId' => $userId,
                        'logUserName' => '用户',
                        'orderStatus' => -6,
                        'payStatus' => 2,
                        'logType' => 0,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
                }
            } elseif ($orderInfo['payFrom'] == 2) {

                //微信
                $loginUserInfo = [
                    'user_id' => $userId,
                    'user_username' => '用户',
                ];
                $cancelRes = order_WxPayRefund($orderInfo['tradeNo'], $orderId, $orderInfo['orderStatus'], 0, $loginUserInfo);//可整单退款
                if ($cancelRes == -3) {
                    M()->rollback();
                    return returnData(null, -1, 'error', '取消失败，微信退款失败');
                }
            } elseif ($orderInfo['payFrom'] == 3) {
                //余额
                //更改订单为已退款  //------可增加事物
                $orders = M('orders');
                $save_orders['isRefund'] = 1;
                $orderEdit = $orders->where("orderId = " . $orderInfo['orderId'])->save($save_orders);
                if ($orderEdit) {
                    //加回用户余额
                    //$userEditRes = M('users')->where("userId='".$userId."'")->setInc('balance',$orderInfo['needPay']);
                    $balance = M('users')->where(['userId' => $orderInfo['userId']])->getField('balance');
                    $balance += $orderInfo['realTotalMoney'];
                    $refundAmount = $orderInfo['realTotalMoney'];
                    if (in_array($orderInfo['orderStatus'], [0, 13, 14])) {
//                    $balance += $orderInfo['deliverMoney'];
//                    $refundAmount += $orderInfo['deliverMoney'];
                    }
                    $userEditRes = M('users')->where(['userId' => $orderInfo['userId']])->save(['balance' => $balance]);
                    if ($userEditRes) {
                        //写入订单日志
                        unset($data);
//                    $log_orders = M("log_orders");
//                    $data["orderId"] = $orderId;
//                    $data["logContent"] = "用户取消订单，发起余额退款：" . $refundAmount . '元';
//                    $data["logUserId"] = $userId;
//                    $data["logType"] = "0";
//                    $data["logTime"] = date("Y-m-d H:i:s");
//                    $log_orders->add($data);
                        $content = "用户取消订单，发起余额退款：" . $refundAmount . '元';
                        $logParams = [
                            'orderId' => $orderId,
                            'logContent' => $content,
                            'logUserId' => $userId,
                            'logUserName' => '用户',
                            'orderStatus' => -6,
                            'payStatus' => 2,
                            'logType' => 0,
                            'logTime' => date('Y-m-d H:i:s'),
                        ];
                        M('log_orders')->add($logParams);
                        //余额记录
                        M('user_balance')->add(array(
                            'userId' => $userId,
                            'balance' => $refundAmount,
                            'dataSrc' => 1,
                            'orderNo' => $orderInfo['orderNo'],
                            'dataRemarks' => "余额退回",
                            'balanceType' => 1,
                            'createTime' => date('Y-m-d H:i:s'),
                            'shopId' => $orderInfo['shopId']
                        ));
                    }
                }
            }
        }
        //优惠券返还
        $cres = cancelUserCoupon($orderId);
        if (!$cres) {
            M()->rollback();
            return returnData(null, -1, 'error', '订单取消失败');
        }
        //END
        //返还属性库存
        //returnAttrStock($orderId);

        //退换已使用的积分----------------------------------
        returnIntegral($orderId, $userId);

        //返回秒杀库存
        //returnKillStock($orderId);

        //收回奖励积分---------------------------------未确认收货是没有奖励积分过去的

        //更新商品限制购买记录表
        updateGoodsOrderNumLimit($orderId);

        //返还sku库存
        //returnGoodsSkuStock($orderId);
        //添加报表-start
        addReportForms($orderId, 3, array('refundFee' => $orderInfo['realTotalMoney']), M());
        //添加报表-end
        M()->commit();
        //取消地推拉新奖励
        cancelPullAmount($orderId);
        /*发起退款通知*/
        $push = D('Adminapi/Push');
        $push->postMessage(8, $userId, $orderInfo['orderNo'], null);
//        $statusCode["statusCode"] = "000060";
        $statusCode["statusInfo"] = "取消成功";
        $statusCode = returnData($statusCode);
        return $statusCode;
    }

    //订单-待发货
    public function toBeShipped($loginName)
    {
        $config = $GLOBALS['CONFIG'];
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1['userId'] = $user["userId"];
        $where1['orderFlag'] = 1;
        $where1['userDelete'] = 1;
        $where1['orderStatus'] = array('in', '0,1,2,14');

        $shopId = I('shopId', 0, 'intval');
        if (!empty($shopId)) $where1['shopId'] = $shopId;

        $orders = M("orders");
        $order_goods = M("order_goods");
        $shops = M("shops");
        $mod_goods = M('goods');

        $order_select = (array)$orders->where($where1)->order('createTime desc')->field(array("orderStatus", "orderId", "orderNo", "shopId", "realTotalMoney", "createTime", "isSelf", "isPay", "payType", "setDeliveryMoney", "deliverMoney"))->select();//查找所有未发货的订单


        $goodsModule = new GoodsModule();
        $field_name = array('goodsImg', 'shopId', 'goodsStock', 'saleCount', 'goodsUnit', 'goodsSpec', 'SuppPriceDiff');
        for ($i = 0; $i < count($order_select); $i++) {
            $order_select[$i]['assembleState'] = 3;//拼团状态，正常订单
            $user_activity_relation_info = M('user_activity_relation')->where(array('orderId' => $order_select[$i]["orderId"], 'uid' => $user["userId"]))->find();
            if (!empty($user_activity_relation_info)) $order_select[$i]['assembleState'] = $user_activity_relation_info['state'];//拼团状态，拼团订单

            $where2["shopId"] = $order_select[$i]["shopId"];
            $where3["orderId"] = $order_select[$i]["orderId"];
            $data_shops = $shops->where($where2)->field(array("shopName", "shopImg"))->find();//条件没判断店铺是否还在经营 有几率会导致用户的待发货出现问题
            $order_select[$i]["shopName"] = $data_shops['shopName'];
            $order_select[$i]["shopImg"] = $data_shops['shopImg'];
            $order_select[$i]["goodsData"] = $order_goods->where($where3)->select();//订单下的商品
            for ($j = 0; $j < count($order_select[$i]['goodsData']); $j++) {
                $oneGood = $mod_goods->where("goodsId = '{$order_select[$i]['goodsData'][$j]['goodsId']}'")->field($field_name)->find();
                $order_select[$i]['goodsData'][$j]['unit'] = $goodsModule->getGoodsUnitByParams($order_select[$i]['goodsData'][$j]['goodsId'], $order_select[$i]['goodsData'][$j]['skuId']);
                $order_select[$i]['goodsData'][$j]['goodsNums'] = (float)$order_select[$i]['goodsData'][$j]['goodsNums'];
                $order_select[$i]['goodsData'][$j]['totalGoodsPrice'] = sprintfNumber((float)$order_select[$i]['goodsData'][$j]['goodsPrice'] * $order_select[$i]['goodsData'][$j]['goodsNums']);
                $order_select[$i]['goodsData'][$j]['SuppPriceDiff'] = $oneGood['SuppPriceDiff'];

            }
            if ($config['setDeliveryMoney'] == 2) {
                if ($order_select[$i]['isPay'] == 1) {
                    //$order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['deliverMoney'];
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'];
                } else {
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['setDeliveryMoney'];
                }
            }
            //sku属性,后加
            $order_select[$i]['goodsData'] = array_values(array_filter(getCartGoodsSku($order_select[$i]['goodsData'])));
        }


        return $order_select;

    }

    //订单-待收货
    public function waitforGoodGreceipt($loginName)
    {
        $config = $GLOBALS['CONFIG'];
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1['userId'] = $user["userId"];
        $where1['orderFlag'] = 1;
        $where1['userDelete'] = 1;
//        $where1['orderStatus'] = 3;//原来的
        $where1['orderStatus'] = array('in', '3,7,8,9,10,11,16,17');

        $shopId = I('shopId', 0, 'intval');
        if (!empty($shopId)) $where1['shopId'] = $shopId;

        $orders = M("orders");
        $order_goods = M("order_goods");
        $shops = M("shops");
        $mod_goods = M('goods');

        $order_select = $orders->where($where1)->field(array("orderStatus", "orderId", "orderNo", "shopId", "realTotalMoney", "createTime", "isSelf", "payType", "isPay", "setDeliveryMoney", "deliverMoney"))->order('orderId desc')->select();//查找所有未发货的订单
        $goodsModule = new GoodsModule();
        for ($i = 0; $i < count($order_select); $i++) {
            if ($config['setDeliveryMoney'] == 2) {
                if ($order_select[$i]['isPay'] == 1) {
                    //$order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['deliverMoney'];
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'];
                } else {
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['setDeliveryMoney'];
                }
            }
            $where2["shopId"] = $order_select[$i]["shopId"];
            $where3["orderId"] = $order_select[$i]["orderId"];
            $data_shops = $shops->where($where2)->field(array("shopName", "shopImg"))->find();//条件没判断店铺是否还在经营 有几率会导致用户的待收货出现问题
            $order_select[$i]["shopName"] = $data_shops['shopName'];
            $order_select[$i]["shopImg"] = $data_shops['shopImg'];
            $order_select[$i]["goodsData"] = $order_goods->where($where3)->select();//订单下的商品


            for ($j = 0; $j < count($order_select[$i]["goodsData"]); $j++) {
                $oneGood = $mod_goods->where("goodsId = '{$order_select[$i]["goodsData"][$j]['goodsId']}'")->find();
                $order_select[$i]["goodsData"][$j]['unit'] = $goodsModule->getGoodsUnitByParams($order_select[$i]["goodsData"][$j]['goodsId'], $order_select[$i]["goodsData"][$j]['skuId']);
                $order_select[$i]["goodsData"][$j]['goodsNums'] = (float)$order_select[$i]["goodsData"][$j]['goodsNums'];
                $order_select[$i]['goodsData'][$j]['totalGoodsPrice'] = sprintfNumber((float)$order_select[$i]['goodsData'][$j]['goodsPrice'] * $order_select[$i]['goodsData'][$j]['goodsNums']);
                $order_select[$i]["goodsData"][$j]['SuppPriceDiff'] = $oneGood['SuppPriceDiff'];
                $order_select[$i]["goodsData"][$j]['goodsImg'] = $oneGood['goodsImg'];
                $order_select[$i]["goodsData"][$j]['shopId'] = $oneGood['shopId'];
                $order_select[$i]["goodsData"][$j]['goodsStock'] = $oneGood['goodsStock'];
                $order_select[$i]["goodsData"][$j]['saleCount'] = $oneGood['saleCount'];
                $order_select[$i]["goodsData"][$j]['goodsSpec'] = $oneGood['goodsSpec'];


            }

            //sku属性,后加
            $order_select[$i]['goodsData'] = getCartGoodsSku($order_select[$i]['goodsData']);


        }

        return $order_select;

    }

    //订单-待评价
    public function waitforEvaluate($loginName)
    {
        $config = $GLOBALS['CONFIG'];
        $where["loginName"] = $loginName;
        $where["userFlag"] = 1;
        $user = M("users")->where($where)->field(array("userId"))->find();

        $where1['userId'] = $user["userId"];
        $where1['orderFlag'] = 1;
        $where1['userDelete'] = 1;
        $where1['orderStatus'] = 4;
        $where1['isAppraises'] = "0";

        $shopId = I('shopId', 0, 'intval');
        if (!empty($shopId)) $where1['shopId'] = $shopId;

        $orders = M("orders");
        $order_goods = M("order_goods");
        $shops = M("shops");

        $order_select = $orders->where($where1)->field(array("orderId", "orderNo", "shopId", "realTotalMoney", "deliverMoney", "setDeliveryMoney", "isPay"))->select();//查找所有未发货的订单

        for ($i = 0; $i < count($order_select); $i++) {
            $where2["shopId"] = $order_select[$i]["shopId"];
            $where3["orderId"] = $order_select[$i]["orderId"];
            $data_shops = $shops->where($where2)->field(array("shopName", "shopImg"))->find();//条件没判断店铺是否还在经营 有几率会导致用户的待付款出现问题
            $order_select[$i]["shopName"] = $data_shops['shopName'];
            $order_select[$i]["shopImg"] = $data_shops['shopImg'];
            $order_select[$i]["order_goods"] = $order_goods->where($where3)->select();//订单下的商品
            if ($config['setDeliveryMoney'] == 2) {
                if ($order_select[$i]['isPay'] == 1) {
                    //$order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['deliverMoney'];
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'];
                } else {
                    $order_select[$i]['realTotalMoney'] = $order_select[$i]['realTotalMoney'] + $order_select[$i]['setDeliveryMoney'];
                }
            }
            //sku属性,后加
            $order_select[$i]['order_goods'] = getCartGoodsSku($order_select[$i]['order_goods']);
        }

        return $order_select;
    }

    //获取商城自营店铺  （查询排序需要按照经纬度排序 暂未使用经纬度排序）
    public function toShopHome($lat, $lng, $adcode, $page = 1)
    {
        $mod = S("NIAO_CACHE_toShopHome_{$adcode}_{$page}");
        $pageDataNum = 10;
        if (empty($mod)) {
            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where["isSelf"] = 1;
            $where["shopStatus"] = 1;
            $where["shopFlag"] = 1;
            //$where["areaId3"] = $adcode;
            $where["shopAtive"] = 1;
            // $where["serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $mod = M("shops")->where($where)
                ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
                ->group('wst_shops.shopId')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            S("NIAO_CACHE_toShopHome_{$adcode}_{$page}", $mod, C("allApiCacheTime"));
        }
        for ($i = 0; $i <= count($mod) - 1; $i++) {

            $mod[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($mod[$i]['latitude'], $mod[$i]['longitude'], $lat, $lng)['kilometers']);
        }
        $shopsDataSort = array();
        foreach ($mod as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $mod);//从低到高排序

        return $mod;
    }

    //商城分类 二级分类下所有商品
    public function typeTwoGoods($userId, $goodsCatId2, $page, $pageSize, $lat, $lng)
    {
        $data = S("niao_app_typeTwoGoods_{$goodsCatId2}_{$lat}-{$lng}-{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['isMembershipExclusive'] = 0;
                }
            }
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return returnData();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["shopId"] = array("in", $shopIdArr);

            $where["isSale"] = 1;
            $where["goodsCatId2"] = $goodsCatId2;
            $where["goodsStatus"] = 1;
            $where["goodsFlag"] = 1;
//            $where["isFlashSale"] = 0;//是否限时
//            $where["isLimitBuy"] = 0;//是否限量购
            $mod = M("goods")->where($where)
                ->order('shopCatId2 desc,shopGoodsSort desc,isAdminBest desc,isAdminRecom desc')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            $mod = filterRecycleGoods($mod);//过滤回收站的商品
            $mod = rankGoodsPrice($mod); //处理商品等级价格
            S("niao_app_typeTwoGoods_{$goodsCatId2}_{$lat}-{$lng}-{$page}", $mod, C("niao_app_typeTwoGoods_cache_time"));
            return (array)$mod;
        }
        return $data;
    }

    //商城分类 二级分类下所有商品
    /*public function typeTwoGoods($goodsCatId2){
        $data = S("niao_app_typeTwoGoods_{$goodsCatId2}");
        if(empty($data)){
            //获取所有的三级分类id
            $threeCatId = M('goods_cats')->where("parentId='".$goodsCatId2."' AND isShow=1 AND catFlag=1")->select();
            if(!empty($threeCatId)){
                $goodsTab = M('goods');
                $goods = [];
                foreach ($threeCatId as $value){
                    $where = " isSale=1 AND goodsStatus=1 AND goodsFlag=1 AND goodsCatId3='".$value['catId']."'";
                    $goods[] = $goodsTab->where($where)->field('goodsId')->limit(20)->select();
                }
                $goodsId = [];
                foreach ($goods as $value){
                    foreach ($value as $val){
                        $goodsId[] = $val['goodsId'];
                    }
                }
                $goodsId = array_unique($goodsId);
                $goodsIdStr = 0;
                if(!empty($goodsId)){
                    $goodsIdStr = implode(',',$goodsId);
                }
                $list = M("goods g")
                    ->join("LEFT JOIN wst_shops s ON s.shopId=g.shopId")
                    ->where("goodsId IN($goodsIdStr) AND s.shopFlag=1")
                    ->field("g.*")
                    ->select();
                $mod = rankGoodsPrice($list); //处理商品等级价格
                S("niao_app_typeTwoGoods_{$goodsCatId2}",$mod,C("niao_app_typeTwoGoods_cache_time"));
            }

            return (array)$mod;
        }
        return $data;
    }*/

    //提交订单 ---货到付款 原来的
//    public function SubmitOrder_t($loginName, $addressId, $goodsId, $orderRemarks, $requireTime, $couponId, $isSelf = 0, $getuseScore, $lng, $lat, $cuid)
//    {
//        M()->startTrans();
//        $mlogo = M('log_orders');
//        $orders = M("orders");
//        $goods = M("goods");
//        $cart = M("cart");
//        $order_goods = M("order_goods");
//        $order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $order_communitys_mod = M('communitys');
//        $shops_mod = M('shops');
//        $sm = D('Home/Shops');
//
//        //获取用户Id 根据登陆名
//        $where["userFlag"] = 1;
//        $where["loginName"] = $loginName;
//        $users = M("users")->where($where)->find();
//        //$users['userId'];
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($users['userId']);
//
//        //检查收货地址是否存在
//        $where1["addressId"] = (int)$addressId;
//        $where1["addressFlag"] = 1;
//        $where1["userId"] = $users['userId'];
//        $user_address = M("user_address");
//        $res_user_address = $user_address->where($where1)->find();
//        if (empty($res_user_address)) {
//            M()->rollback();
//            unset($apiRet);
////            $apiRet['apiCode']='000065';
////            $apiRet['apiInfo']='请添加收货地址';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//            return $apiRet;
//        }
//        //检查商品Id 是否在购物车
//        $goodsId = explode(",", $goodsId);//分割出商品id
//        $goodsIdArr = $goodsId;
//        $where2["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsId); $i++) {
//            $where2["goodsId"] = $goodsId[$i];
//            $iS_goodsId = $cart->where($where2)->find();
//
//            if (empty($iS_goodsId)) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000066';
////                $apiRet['apiInfo']='此商品不存在于购物车';
//                $apiRet['goodsId'] = $goodsId[$i];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '此商品不存在于购物车');
//                return $apiRet;
//
//            }
//
//            $checkGoodsFlashSale = checkGoodsFlashSale($iS_goodsId['goodsId']); //检查商品限时状况
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                return $checkGoodsFlashSale;
//            }
//
//            $goods_info = M("goods")->where("goodsId='{$goodsId[$i]}'")->find();
//            //针对新人专享商品，判断用户是否可以购买
//            $isBuyNewPeopleGoods = isBuyNewPeopleGoods($goodsId[$i], $users['userId']);
//            if (!$isBuyNewPeopleGoods) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000041';
////                $apiRet['apiInfo']=$goods_info['goodsName'] . ' 是新人专享商品，您不能购买!';
//                $apiRet['goodsId'] = $goodsId[$i];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', $goods_info['goodsName'] . ' 是新人专享商品，您不能购买!');
//                return $apiRet;
//            }
//        }
//        //判断商品是否在售 以及库存是否充足 注意预售 和秒杀的商品
//        for ($i = 0; $i < count($goodsId); $i++) {
//            unset($where);
//
//            $where['goodsId'] = $goodsId[$i];
//            $res_goodsId = $goods->lock(true)->where($where)->find();
//
//            if ($res_goodsId['isSale'] == 0) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000069';
////                $apiRet['apiInfo']='商品已下架';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//
//                $apiRet = returnData($apiRet, -1, 'error', '商品已下架');
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsStatus'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000070';
////                $apiRet['apiInfo']='商品已禁售';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品已禁售');
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsFlag'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000071';
////                $apiRet['apiInfo']='商品不存在';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品不存在');
//
//                return $apiRet;
//            }
//
//            //判断是否为限制下单次数的商品 start
//            $checkRes = checkGoodsOrderNum($res_goodsId['goodsId'], $users['userId']);
//            if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//                return $checkRes;
//            }
//
//            //获取购物车里的商品数量
//            unset($where);
//            $where['goodsId'] = $goodsId[$i];
//            $cart_res_goodsId = $cart->where($where)->find();
//
//            if ((int)$res_goodsId['goodsStock'] < (int)$cart_res_goodsId['goodsCnt']) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000072';
////                $apiRet['apiInfo']='商品库存不足';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                return $apiRet;
//            }
//
//            /**
//             * 2019-06-15 start
//             * 判断属性库存是否充足
//             * */
//            $goodsAttr = M('goods_attributes');
//            if (!empty($cart_res_goodsId['goodsAttrId'])) {
//                $goodsAttrIdArr = explode(',', $cart_res_goodsId['goodsAttrId']);
//                foreach ($goodsAttrIdArr as $iv) {
//                    $goodsAttrInfo = $goodsAttr->lock(true)->where("id='" . $iv . "'")->find();
//                    if ($goodsAttrInfo['attrStock'] <= 0) {
//                        M()->rollback();
//                        unset($apiRet);
////                        $apiRet['apiCode']='000072';
////                        $apiRet['apiInfo']='商品库存不足';
//                        $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                        $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                        $apiRet['apiState']='error';
//                        $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                        return $apiRet;
//                    }
//                }
//            }
//            /**
//             * 2019-06-15 end
//             * */
//
//            //检测配送区域
//            /*if(!$lng || !$lat){
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000073';
////                $apiRet['apiInfo']='经纬度必填';
//                $apiRet['goodsId']=$res_goodsId['goodsId'];
//                $apiRet['goodsName']=$res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet,-1,'error','经纬度必填');
//
//                return $apiRet;
//            }*/
//
//            $shopConfig = M('shop_configs')->where(['shopId' => $res_goodsId['shopId']])->find();
//            if ($shopConfig['deliveryLatLngLimit'] == 1) {
//                $dcheck = checkShopDistribution($res_goodsId['shopId'], $res_user_address['lng'], $res_user_address['lat']);
//                if (!$dcheck) {
//                    M()->rollback();
//                    unset($apiRet);
////                    $apiRet['apiCode']='000074';
////                    $apiRet['apiInfo']='配送范围超出';
//                    $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                    $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//
//                    return $apiRet;
//                }
//                //END
//            }
//
//            //秒杀商品限量控制
//            if ($res_goodsId['isShopSecKill'] == 1) {
//                unset($apiRet);
//                $apiRet['apiCode'] = '';
//                $apiRet['apiInfo'] = '';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
//                $apiRet['apiState'] = 'error';
//                if ((int)$res_goodsId['shopSecKillNUM'] < (int)$cart_res_goodsId['goodsCnt']) {
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000075';
////                    $apiRet['apiInfo'] = '秒杀库存不足';
//                    $apiRet = returnData(null, -1, 'error', '秒杀库存不足');
//
//                    return $apiRet;
//                }
//                //已经秒杀完成的记录
//                $killTab = M('goods_secondskilllimit');
////                $killWhere['userId'] = $users['userId'];
////                $killWhere['goodsId'] = $res_goodsId['goodsId'];
////                $killWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
////                $killWhere['state'] = 1;
////                $killLog = $killTab->where($killWhere)->count(); //一条记录代表一次成功秒杀
////                if(($killLog >= $res_goodsId['userSecKillNUM']) || ((int)$cart_res_goodsId['goodsCnt']+$killLog >$res_goodsId['userSecKillNUM'])){
////                    $num = $res_goodsId['userSecKillNUM'] - $killLog; //剩余次数
////                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多秒杀'.$res_goodsId['userSecKillNUM'].'件该商品'.', 还能秒杀'.$num.'件';
////                    return $apiRet;
////                }
//                $existWhere['userId'] = $users['userId'];
//                $existWhere['goodsId'] = $res_goodsId['goodsId'];
//                $existOrderW['o.orderStatus'] = ['IN', [0, 1, 2, 3, 4]];
//                //$existWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
//                $existWhere['state'] = 1;
//                $existKillLog = $killTab->where($existWhere)->group('orderId')->select();
//
//                $existOrderField = [
//                    'o.orderId'
//                ];
//                $existOrderId = [];
//                foreach ($existKillLog as $val) {
//                    $existOrderId[] = $val['orderId'];
//                }
//                $existOrderW['o.orderFlag'] = 1;
//                $existOrderW['o.orderId'] = ['IN', $existOrderId];
//                $existOrder = M('orders o')
//                    ->join("LEFT JOIN wst_order_goods og ON og.orderId=o.orderId")
//                    ->where($existOrderW)
//                    ->field($existOrderField)
//                    ->count();
//                if ($existOrder >= $res_goodsId['userSecKillNUM']) {
//                    $num = $res_goodsId['userSecKillNUM'] - $existOrder; //剩余可购买次数
//                    if ($num < 0) {
//                        $num = 0;
//                    }
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多购买'.$res_goodsId['userSecKillNUM'].'次该商品'.', 还能秒杀'.$num.'次';
//                    $apiRet = returnData(null, -1, 'error', '每个用户最多购买' . $res_goodsId['userSecKillNUM'] . '次该商品' . ', 还能秒杀' . $num . '次');
//                    return $apiRet;
//                }
//            }
//
//        }
//
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $res_user_address['areaId3']);
//            if (!empty($isDistriScope)) {
//                M()->rollback();
//                return $isDistriScope;
//            }
//        }
//
//        //判断每笔订单 是否达到配送条件
//        $where3["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsId); $i++) {
//            $where3["goodsId"] = $goodsId[$i];//获取购物车中商品数据
//            $goodsId[$i] = $cart->where($where3)->find();
//
//            $where4["goodsId"] = $goodsId[$i]["goodsId"];//获取商品数据
//            $goodsId2[$i] = $goods->where($where4)->field(array("goodsDesc"), true)->find();
//            $goodsId2[$i]["cartId"] = $goodsId[$i]["cartId"];
//            $goodsId2[$i]["userId"] = $goodsId[$i]["userId"];
//            $goodsId2[$i]["isCheck"] = $goodsId[$i]["isCheck"];
//            $goodsId2[$i]["goodsAttrId"] = $goodsId[$i]["goodsAttrId"];
//            $goodsId2[$i]["goodsCnt"] = $goodsId[$i]["goodsCnt"];
//            $goodsId2[$i]["remarks"] = $goodsId[$i]["remarks"];
//        }
//        //return $goodsId2;
//        $result = array();
//        foreach ($goodsId2 as $k => $v) {
//            $result[$v["shopId"]][] = $v;//根据Id归类
//        }
//        //$result = array_merge($result);
//        $result = array_values($result);//重建索引
//        $result[0] = rankGoodsPrice($result[0]); //商品等级价格处理
//        //return $result;
//        //生成订单数据
//        $orderids = M('orderids');
//        $user_address = M("user_address")->where("addressId = '{$addressId}' and addressFlag = '1'")->find();//用户地址数据
//
//        //订单公用参数
//        $data["areaId1"] = $user_address["areaId1"];
//        $data["areaId2"] = $user_address["areaId2"];
//        $data["areaId3"] = $user_address["areaId3"];
//        $data["payType"] = "0";//支付方式
//        $data["isSelf"] = $isSelf;//是否自提
//        $data["isPay"] = "0";//是否支付
//
//        $data["userId"] = $users['userId'];//用户Id
//        $data["userName"] = $user_address["userName"];//收货人名称
//        $data["communityId"] = $user_address["communityId"];//收货地址所属社区id
//
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $user_address['setaddress'] .
//            $user_address["address"];//收件人地址
//
//        $data["userPhone"] = $user_address["userPhone"];//收件人手机
//        $data["userPostCode"] = $user_address["postCode"];//收件人邮编
//        //$data["isInvoice"] = "0";//是否需要发票
//        $data["isInvoice"] = (int)I('isInvoice', 0);//是否需要发票
//        $data["invoiceClient"] = (int)I('invoiceClient', 0);//发票id
//
//        $data["orderRemarks"] = $orderRemarks;//订单备注
//        $data["requireTime"] = $requireTime;//要求送达时间
//        //$data["orderRemarks"] = "app下单";//订单备注
//        //$data["requireTime"] = date("Y-m-d H:i:s",time()+3600);//要求送达时间
//
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "3";//订单来源
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = 0;//支付来源
//        //$data["settlementId"] = "0";//结算记录ID 用户确认收货补上id 创建 wst_order_settlements 结算表 再更改结算记录Id
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        //return $data;
//        for ($i = 0; $i < count($result); $i++) {
//            $shopId = $result[$i][0]['shopId'];
//            //生成订单号
//            $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//            $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//            $data["orderNo"] = $orderNo;//订单号
//            $data["shopId"] = $result[$i]["0"]["shopcm"]["shopId"];//店铺id
//            $data["orderStatus"] = "-2";//订单状态为受理
//            $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//            $result1 = $result[$i];
//            for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
//                //获取当前订单所有商品总价
//                $totalMoney[$i][$i1] = (float)$result[$i][$i1]["shopPrice"] * (int)$result[$i][$i1]["goodsCnt"];
//                //$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i1]["goodsAttrId"],$result[$i][$i1]["goodsId"],$result[$i][$i1]["shopPrice"],$result[$i][$i1]["goodsCnt"],$result[$i][$i1]["shopId"])['goodsPrice'];
//                $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result[$i][$i1]["goodsAttrId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
//                $totalMoney[$i][$i1] = $goodsTotalMoney;
//            }
//            $totalMoney[$i] = array_sum($totalMoney[$i]);//计算总金额
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//
//            //--------------------------------------------------
//
//            //如果优惠券不为空
//            /*if(!empty($couponId)){
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere['couponId'] = $couponId;
//                $couponWhere['userId'] = $users['userId'];
//                if($mod_coupons_users->where($couponWhere)->find()){//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['couponId'] = $couponId;
//                    if($mod_coupons_users->where($couponWhere)->find()){//是否是未使用状态
//                        $couponWhere = array();
//                        $couponWhere['validStartTime'] =  array('ELT',date('Y-m-d'));
//                        $couponWhere['validEndTime'] = array('EGT',date('Y-m-d'));
//                        if($mod_coupons->where($couponWhere)->find()){//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = $couponId;
//                            if($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]){//是否满足使用条件
//                                $mod_coupons->where($couponWhere)->find();
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            }else{
//                                $apiRet['apiCode']='000104';
//                                $apiRet['apiInfo']='未达到最低消费金额';
//                                $apiRet['apiData'] = null;
//                                $apiRet['apiState']='error';
//                                return $apiRet;
//                            }
//                        }else{
//                            $apiRet['apiCode']='000105';
//                            $apiRet['apiInfo']='优惠券已过期';
//                            $apiRet['apiData'] = null;
//                            $apiRet['apiState']='error';
//                            return $apiRet;
//                        }
//                    }else{
//                        $apiRet['apiCode']='000106';
//                        $apiRet['apiInfo']='优惠券已使用';
//                        $apiRet['apiData'] = null;
//                        $apiRet['apiState']='error';
//                        return $apiRet;
//                    }
//                }else{
//                    $apiRet['apiCode']='000107';
//                    $apiRet['apiInfo']='优惠券未领取...';
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => $couponId,
//                    'good_id_arr' =>$goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr,$msg);
//                if(!$checkRes){
//                    $apiRet['apiCode']='000108';
//                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//            }*/
//
//            //--------------------------------------------------
//
//            //$data["totalMoney"] = (float)$totalMoney[$i];//商品总金额
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i])*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]);//所得积分
//
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- start ---
//            for ($j1 = 0; $j1 < count($result1); $j1++) {//商品总金额
//                $goods_integral = 0;
//                //获取当前订单所有商品总价
//                $totalMoney_s[$i][$j1] = (float)$result1[$j1]["shopPrice"] * (int)$result1[$j1]["goodsCnt"];
//                //$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i1]["goodsAttrId"],$result[$i][$i1]["goodsId"],$result[$i][$i1]["shopPrice"],$result[$i][$i1]["goodsCnt"],$result[$i][$i1]["shopId"])['goodsPrice'];
//                $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result1[$j1]["goodsAttrId"], $result1[$j1]["goodsId"], $result1[$j1]["shopPrice"], $result1[$j1]["goodsCnt"], $result1[$j1]["shopId"])['totalMoney'];
//                $totalMoney_s[$i][$j1] = $goodsTotalMoney;
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result1[$j1]["goodsId"]))->find();
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMoney_s[$i][$j1] = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['memberPrice'];
//                        $goods_integral = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['integralReward'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMoney_s[$i][$j1] = $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//            }
//            if (is_array($totalMoney_s[$i])) {
//                $totalMoney[$i] = array_sum($totalMoney_s[$i]);//计算总金额
//            } else {
//                $totalMoney[$i] = $totalMoney_s[$i];//计算总金额
//            }
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//            /*
//            //如果优惠券不为空
//            if(!empty($cuid)){
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere['id'] = $cuid;
//                $couponWhere['userId'] = $users['userId'];
//                if($mod_coupons_users->where($couponWhere)->find()){//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['id'] = $cuid;
//                    if($mod_coupons_users->where($couponWhere)->find()){//是否是未使用状态
//                        $couponUserInfo = $mod_coupons_users->where($couponWhere)->find();
//                        $couponWhere = array();
//                        $couponWhere['validStartTime'] = array('ELT', date('Y-m-d'));
//                        $couponWhere['validEndTime'] = array('EGT', date('Y-m-d'));
//                        $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                        if ($mod_coupons->where($couponWhere)->find()) {//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                            if ($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]) {//是否满足使用条件
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            } else {
//                                $apiRet['apiCode'] = '000104';
//                                $apiRet['apiInfo'] = '未达到最低消费金额';
//                                $apiRet['apiData'] = null;
//                                $apiRet['apiState'] = 'error';
//                                return $apiRet;
//                            }
//                        } else {
//                            $apiRet['apiCode'] = '000105';
//                            $apiRet['apiInfo'] = '优惠券已过期';
//                            $apiRet['apiData'] = null;
//                            $apiRet['apiState'] = 'error';
//                            return $apiRet;
//                        }
//                    }else{
//                        $apiRet['apiCode']='000106';
//                        $apiRet['apiInfo']='优惠券已使用';
//                        $apiRet['apiData'] = null;
//                        $apiRet['apiState']='error';
//                        return $apiRet;
//                    }
//                }else{
//                    $apiRet['apiCode']='000107';
//                    $apiRet['apiInfo']='优惠券未领取...';
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => ((!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId']),
//                    'good_id_arr' =>$goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr,$msg);
//                if(!$checkRes){
//                    $apiRet['apiCode']='000108';
//                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//            }*/
//
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i]+$goods_integral)*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]) + $goods_integral;//所得积分
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- end ---
//
//            $data["deliverMoney"] = $result[$i]["0"]["shopcm"]["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//
//            $data["deliverType"] = "1";//配送方式 门店配送
//
//            //设置订单配送方式
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//
//            //当前店铺是否是达达配送 且当前订单 不为自提
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 2 and $isSelf !== 1) {
//                $funData['shopId'] = $shopId;
//                $funData['areaId2'] = $data["areaId2"];
//                $funData['orderNo'] = $data["orderNo"];
//                $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//                $funData['userName'] = $data["userName"];//收货人姓名
//                $funData['userAddress'] = $data["userAddress"];//收货人地址
//                $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//                $dadaresFun = self::dadaDeliver($funData);
//
//                if ($dadaresFun['status'] == -6) {
//                    M()->rollback();
//                    //获取城市出错
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，获取城市出错';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == -7) {
//                    M()->rollback();
//                    //不在达达覆盖城市内
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == 0) {
//                    //获取成功
//                    $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                    $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//                    // $data["deliverMoney"] =  $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//                }
//                $data["deliverType"] = "2";//配送方式 达达配送
//            }
//
//            //--------------------金额满减运费 start---------------------------
//
//            if ((float)$data["totalMoney"] >= (float)$result[$i]["0"]["shopcm"]["deliveryFreeMoney"]) {
//                $data["deliverMoney"] = 0;
//            }
//            //$data["totalMoney"]
//            //-----------------------金额满减运费end----------------------
//
//            //在使用运费之前处理好运费一些相关问题
//            if ($isSelf == 1) {//如果为自提订单 将运费重置为0
//                $data["deliverMoney"] = 0;
//            }
//
//            $data["needPay"] = $totalMoney[$i] + $data["deliverMoney"];//需缴费用 加运费-----
//            $data["realTotalMoney"] = (float)$totalMoney[$i] + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//
//            //使用积分
//            /*if($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1){
//                //获取比例
//                $scoreScoreCashRatio = explode(':',$GLOBALS['CONFIG']['scoreCashRatio']);
//                //最多可抵用的钱
//                $realTotalMoney = (float)$data["realTotalMoney"]-0.01;
//                //根据比例计算 积分最多可抵用的钱  总积分/比例积分*比例金额
//                $zdSMoney = (int)$users['userScore'] / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//                //$realTotalMoneyAzdSMoney = (float)$realTotalMoney - (float)$zdSMoney;
//                //计算做多可抵用多少钱的结果
//                if((float)$realTotalMoney >= (float)$zdSMoney){
//                    $zuiduoMoney = $zdSMoney;
//                }else{
//                    $zuiduoMoney = $realTotalMoney;
//                }
//                //计算反推出 相应的可抵用的积分
//                $zuiduoScore = $zuiduoMoney / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//
//                //return array('a'=>$zuiduoMoney,'b'=>$scoreScoreCashRatio);
//                //return array('抵扣最终的钱'=>$zuiduoMoney,'所参与抵扣的积分'=>$zuiduoScore);
//
//                $data["needPay"] = $totalMoney[$i]+$data["deliverMoney"]-$zuiduoMoney;//需缴费用 加运费 减去抵用的钱
//                $data["realTotalMoney"] = (float)$totalMoney[$i]+(float)$data["deliverMoney"]-$zuiduoMoney;//实际订单总金额 加运费 减去地用的钱
//
//
//                $data["useScore"] = $zuiduoScore;//本次交易使用的积分数
//                $data["scoreMoney"] = $zuiduoMoney;//积分兑换的钱 完成交易 确认收货在给积分
//            }*/
//
//            $shop_info = $sm->getShopInfo($data["shopId"]);
//            $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//            $appRealTotalMoney[$i] = $data["realTotalMoney"];//获取订单金额
//
//            $data['orderStatus'] = 0; //待受理
//            $data['isPay'] = 0; //已支付
//
//            //写入订单
//            $orderId[$i] = $orders->add($data);
//            if ($isSelf) {//如果为自提订单 生成提货码
//                //生成提货码
//                $mod_user_self_goods = M('user_self_goods');
//                $mod_user_self_goods_add['orderId'] = $orderId[$i];
//                $mod_user_self_goods_add['source'] = $orderId[$i] . $users['userId'] . $shopId;
//                $mod_user_self_goods_add['userId'] = $users['userId'];
//                $mod_user_self_goods_add['shopId'] = $shopId;
//                $mod_user_self_goods->add($mod_user_self_goods_add);
//            }
//
//            //建立订单记录
////            $data_order["orderId"] = $orderId[$i];
////            $data_order["logContent"] = "小程序下单成功，等待支付";
////            $data_order["logUserId"] = $users['userId'];
////            $data_order["logType"] = 0;
////            $data_order["logTime"] = date('Y-m-d H:i:s');
////
////            $mlogo->add($data_order);
//            $content = "小程序下单成功，等待支付";
//            $logParams = [
//                'orderId' => $orderId[$i],
//                'logContent' => $content,
//                'logUserId' => $users['userId'],
//                'logUserName' => '用户',
//                'orderStatus' => -2,
//                'payStatus' => 0,
//                'logType' => 0,
//                'logTime' => date('Y-m-d H:i:s'),
//            ];
//            M('log_orders')->add($logParams);
//
//            //更新优惠券状态为已使用
//            /*if(!empty($couponId)){
//                $mod_coupons_users_data['orderNo'] = $orderNo;
//                $mod_coupons_users_data['couponStatus'] = 0;
//                $mod_coupons_users->where("couponId = " . $couponId)->save($mod_coupons_users_data);
//            }*/
//
//            //使用积分
//            /*if($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1){
//                //减去用户当前所持有积分
//                M('users')->where("userId = {$users['userId']}")->setDec('userScore',$zuiduoScore);
//                //加上用户历史消费积分
//                M('users')->where("userId = {$users['userId']}")->setInc('userTotalScore',$zuiduoScore);
//                //写入积分消费记录
//                $user_add_pay['dataSrc'] = 1;//来源订单
//                $user_add_pay['userId'] = $users['userId'];//用户id
//                $user_add_pay['score'] = $zuiduoScore;//积分
//                $user_add_pay['dataRemarks'] = "抵用现金";
//                $user_add_pay['scoreType'] = 2;
//                $user_add_pay['createTime'] = date("Y-m-d H:i:s");
//                M("user_score")->add($user_add_pay);
//            }*/
//
//            //将订单商品写入order_goods
//            for ($i2 = 0; $i2 < count($result[$i]); $i2++) {//循环商品数据
//                $data_order_goods["orderId"] = $orderId[$i];
//                $data_order_goods["goodsId"] = $result[$i][$i2]["goodsId"];
//                $data_order_goods["goodsNums"] = $result[$i][$i2]["goodsCnt"];
//                $data_order_goods["goodsPrice"] = $result[$i][$i2]["shopPrice"];
//                //$data_order_goods["goodsAttrId"] = 0;原来
//                $data_order_goods["goodsAttrId"] = $result[$i][$i2]["goodsAttrId"];
//                $data_order_goods["remarks"] = $result[$i][$i2]["remarks"];
//                if (is_null($data_order_goods["remarks"])) {
//                    $data_order_goods["remarks"] = '';
//                }
//                //2019-6-14 start
//                $getGoodsAttrPrice = getGoodsAttrPrice($users['userId'], $result[$i][$i2]["goodsAttrId"], $result[$i][$i2]["goodsId"], $result[$i][$i2]["shopPrice"], $result[$i][$i2]["goodsCnt"], $result[$i][$i2]["shopId"]);
//                $data_order_goods["goodsAttrName"] = $getGoodsAttrPrice['goodsAttrName'];
//                $data_order_goods["goodsPrice"] = $getGoodsAttrPrice["goodsPrice"];
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result[$i][$i2]["goodsId"]))->find();
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $data_order_goods["goodsPrice"] = $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $data_order_goods["goodsPrice"] = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//                if (!empty($result[$i][$i2]["goodsAttrId"])) {
//                    /**
//                     * 2019-06-15
//                     * 减少属性的库存
//                     * */
//                    $goodsAttrId = $result[$i][$i2]["goodsAttrId"];
//                    $goodsAttrList = M('goods_attributes')->where("id IN($goodsAttrId)")->select();
//                    foreach ($goodsAttrList as $lv) {
//                        if ($lv['goodsId'] == $result[$i][$i2]['goodsId']) {
////                            M('goods_attributes')->where("id='".$lv['id']."'")->setDec('attrStock',$result[$i][$i2]['goodsCnt']);
//                            $updateGoodsAttrStockResult = updateGoodsAttrStockByRedis($lv['id'], $result[$i][$i2]['goodsCnt']);
//                            if ($updateGoodsAttrStockResult['code'] == -1) {
//                                M()->rollback();
//                                return $updateGoodsAttrStockResult;
//                            }
//                        }
//                    }
//                }
//                //2019-6-14 end
//                $data_order_goods["goodsName"] = $result[$i][$i2]["goodsName"];
//                $data_order_goods["goodsThums"] = $result[$i][$i2]["goodsThums"];
//
//                $order_goods->add($data_order_goods);
//
//                //减去对应商品数量
//                $where5["goodsId"] = $result[$i][$i2]['goodsId'];
////                $goods->where($where5)->setDec('goodsStock',$result[$i][$i2]['goodsCnt']);
//                $updateGoodsStockResult = updateGoodsStockByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                if ($updateGoodsStockResult['code'] == -1) {
//                    M()->rollback();
//                    return $updateGoodsStockResult;
//                }
//
//                //更新进销存系统商品的库存
//                //updateJXCGoodsStock($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt'], 1);
//
//                /**
//                 * 2019-06-15 end
//                 * */
//                if ($result[$i][$i2]['isShopSecKill'] == 1) {
////                    $goods->where($where5)->setDec('shopSecKillNUM',$result[$i][$i2]['goodsCnt']); //减去对应商品的秒杀量
//                    $updateGoodsShopSecKillNUMResult = updateGoodsShopSecKillNUMByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                    if ($updateGoodsShopSecKillNUMResult['code'] == -1) {
//                        M()->rollback();
//                        return $updateGoodsShopSecKillNUMResult;
//                    }
//                }
//
//
//                //限制商品下单次数
//                limitGoodsOrderNum($result[$i][$i2]["goodsId"], $users['userId']);
//
//                //清空对应的购物车商品
//                $where6["userId"] = $users['userId'];
//                $where6["goodsId"] = $result[$i][$i2]['goodsId'];
//                $cart->where($where6)->delete();
//
//                //写入秒杀记录表
//                if ($result[$i][$i2]["isShopSecKill"] == 1) {
//                    for ($kii_i = 0; $kii_i < $data_order_goods["goodsNums"]; $kii_i++) {
//                        //一件商品一条数据
//                        $killData['goodsId'] = $result[$i][$i2]["goodsId"];
//                        $killData['userId'] = $users['userId'];
//                        $killData['endTime'] = $result[$i][$i2]["ShopGoodSecKillEndTime"];
//                        $killData['orderId'] = $orderId[$i];
//                        $killData['state'] = 1;
//                        $killData['addtime'] = date('Y-m-d H:i:s', time());
//                        $killTab->add($killData);
//                    }
//                }
//
//            }
//            //建立订单提醒
//
//            $data_order_reminds["orderId"] = $orderId[$i];
//            $data_order_reminds["shopId"] = $shopId;//店铺id
//            $data_order_reminds["userId"] = $users['userId'];
//            //$data["userType"] = "0";
//            //$data["remindType"] = "0";
//            $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//
//            $order_reminds_statusCode = $order_reminds->add($data_order_reminds);
//
//            // 获取生成的所有订单号 返回给小程序支付
//            $wxorderNo[$i] = $orderNo;
//        }
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode(array_sum($appRealTotalMoney));//多个订单总金额 单位元
//            //$statusCode["orderNo"] = base64_encode(json_encode($wxorderNo));//订单号
//            $statusCode["orderNo"] = strencode(implode("A", $wxorderNo));//订单号  多个订单号用 A隔开
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5(implode("A", $wxorderNo));
//            $wst_order_merge_data['value'] = implode("A", $wxorderNo);
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//            M()->commit();
//            unset($apiRet);
////            $apiRet['apiCode']='000067';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//
//        } else {
//            M()->rollback();
//            unset($apiRet);
////            $apiRet['apiCode']='000068';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//    }

    /**
     * 提交订单 - 货到付款
     * @param $loginName
     * @param $addressId
     * @param $goodsId
     * @param $orderRemarks
     * @param $requireTime
     * @param $couponId
     * @param int $isSelf
     * @param $getuseScore
     * @param $lng
     * @param $lat
     * @param $payFrom
     * @param $cuid
     * @return mixed
     */
//    public function SubmitOrder($loginName, $addressId, $goodsId, $orderRemarks, $requireTime, $couponId, $isSelf = 0, $getuseScore, $lng, $lat, $payFrom, $cuid)
//    {
//        M()->startTrans();
//        $mlogo = M('log_orders');
//        $orders = M("orders");
//        $goods = M("goods");
//        $cart = M("cart");
//        $order_goods = M("order_goods");
//        $order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $order_communitys_mod = M('communitys');
//        $shops_mod = M('shops');
//        $sm = D('Home/Shops');
//
//        //获取用户Id 根据登陆名
//        $where["userFlag"] = 1;
//        $where["loginName"] = $loginName;
//        $users = M("users")->where($where)->find();
//        //$users['userId'];
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($users['userId']);
//
//        //检查收货地址是否存在
//        $where1["addressId"] = (int)$addressId;
//        $where1["addressFlag"] = 1;
//        $where1["userId"] = $users['userId'];
//        $user_address = M("user_address");
//        $res_user_address = $user_address->where($where1)->find();
//        if (empty($res_user_address)) {
//            M()->rollback();
//            unset($apiRet);
//            // $apiRet['apiCode']='000065';
//            // $apiRet['apiInfo']='请添加收货地址';
//            // $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//            return $apiRet;
//        }
//        //检查商品Id 是否在购物车
//        $goodsId = explode(",", $goodsId);//分割出商品id
//        $goodsIdArr = $goodsId;
//        $where2["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsId); $i++) {
//            $where2["goodsId"] = $goodsId[$i];
//            $iS_goodsId = $cart->where($where2)->find();
//
//            if (empty($iS_goodsId)) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000066';
////                $apiRet['apiInfo']='此商品不存在于购物车';
//                $apiRet['goodsId'] = $goodsId[$i];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '此商品不存在于购物车');
//                return $apiRet;
//
//            }
//
//            $checkGoodsFlashSale = checkGoodsFlashSale($goodsId[$i]); //检查商品限时状况
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                return $checkGoodsFlashSale;
//            }
//
//            $goods_info = M("goods")->where("goodsId='{$goodsId[$i]}'")->find();
//            //针对新人专享商品，判断用户是否可以购买
//            $isBuyNewPeopleGoods = isBuyNewPeopleGoods($goodsId[$i], $users['userId']);
//            if (!$isBuyNewPeopleGoods) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000041';
////                $apiRet['apiInfo']=$goods_info['goodsName'] . ' 是新人专享商品，您不能购买!';
//                $apiRet['goodsId'] = $goodsId[$i];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', $goods_info['goodsName'] . ' 是新人专享商品，您不能购买!');
//                return $apiRet;
//            }
//
//            //检查商品是否属购买数量限制
//            if ($iS_goodsId) {
//                $goodsInfo = M('goods')->where("goodsId='" . $goodsId[$i] . "'")->find();
//                if (isset($goodsInfo['buyNum']) && $goodsInfo['buyNum'] > 0 && $iS_goodsId['goodsCnt'] > $goodsInfo['buyNum']) {
//                    M()->rollback();
//                    unset($apiRet);
////                    $apiRet['apiCode']='000101';
////                    $apiRet['apiInfo']='单笔订单最多购买商品 '.$goodsInfo['goodsName'].' '.$goodsInfo['buyNum'].'件';
//                    $apiRet['goodsId'] = $goodsId[$i];
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData($apiRet, -1, 'error', '单笔订单最多购买商品 ' . $goodsInfo['goodsName'] . ' ' . $goodsInfo['buyNum'] . '件');
//                    return $apiRet;
//                }
//            }
//        }
//        //判断商品是否在售 以及库存是否充足 注意预售 和秒杀的商品
//        for ($i = 0; $i < count($goodsId); $i++) {
//            unset($where);
//
//            $where['goodsId'] = $goodsId[$i];
//            $res_goodsId = $goods->lock(true)->where($where)->find();
//
//            if ($res_goodsId['isSale'] == 0) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000069';
////                $apiRet['apiInfo']='商品已下架';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品已下架');
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsStatus'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000070';
////                $apiRet['apiInfo']='商品已禁售';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品已禁售');
//
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsFlag'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000071';
////                $apiRet['apiInfo']='商品不存在';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品不存在');
//
//                return $apiRet;
//            }
//
//            //判断是否为限制下单次数的商品 start
//            $checkRes = checkGoodsOrderNum($res_goodsId['goodsId'], $users['userId']);
//            if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//                return $checkRes;
//            }
//            //判断是否为限制下单次数的商品 end
//
//
//            //获取购物车里的商品数量
//            unset($where);
//            $where['goodsId'] = $goodsId[$i];
//            $cart_res_goodsId = $cart->where($where)->find();
//
//            if ((int)$res_goodsId['goodsStock'] < (int)$cart_res_goodsId['goodsCnt']) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000072';
////                $apiRet['apiInfo']='商品库存不足';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                return $apiRet;
//            }
//
//            /**
//             * 2019-06-15 start
//             * 判断属性库存是否充足
//             * */
//            $goodsAttr = M('goods_attributes');
//            if (!empty($cart_res_goodsId['goodsAttrId'])) {
//                $goodsAttrIdArr = explode(',', $cart_res_goodsId['goodsAttrId']);
//                foreach ($goodsAttrIdArr as $iv) {
//                    $goodsAttrInfo = $goodsAttr->lock(true)->where("id='" . $iv . "'")->find();
//                    if ($goodsAttrInfo['attrStock'] <= 0) {
//                        M()->rollback();
//                        unset($apiRet);
////                        $apiRet['apiCode']='000072';
////                        $apiRet['apiInfo']='商品库存不足';
//                        $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                        $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                        $apiRet['apiState']='error';
//                        $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                        return $apiRet;
//                    }
//                }
//            }
//            /**
//             * 2019-06-15 end
//             * */
//
//            //检测配送区域
//            /*if(!$lng || !$lat){
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000073';
////                $apiRet['apiInfo']='经纬度必填';
//                $apiRet['goodsId']=$res_goodsId['goodsId'];
//                $apiRet['goodsName']=$res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet,-1,'error','经纬度必填');
//
//                return $apiRet;
//            }*/
//            $shopConfig = M('shop_configs')->where(['shopId' => $res_goodsId['shopId']])->find();
//            if ($shopConfig['deliveryLatLngLimit'] == 1) {
//                $dcheck = checkShopDistribution($res_goodsId['shopId'], $res_user_address['lng'], $res_user_address['lat']);
//                if (!$dcheck) {
//                    M()->rollback();
//                    unset($apiRet);
////                    $apiRet['apiCode']='000074';
////                    $apiRet['apiInfo']='配送范围超出';
//                    $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                    $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//
//                    return $apiRet;
//                }
//                //END
//            }
//
//
//            //秒杀商品限量控制
//            if ($res_goodsId['isShopSecKill'] == 1) {
//                unset($apiRet);
//                $apiRet['apiCode'] = '';
//                $apiRet['apiInfo'] = '';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
//                $apiRet['apiState'] = 'error';
//                if ((int)$res_goodsId['shopSecKillNUM'] < (int)$cart_res_goodsId['goodsCnt']) {
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000075';
////                    $apiRet['apiInfo'] = '秒杀库存不足';
//                    $apiRet = returnData(null, -1, 'error', '秒杀库存不足');
//                    return $apiRet;
//                }
//                //已经秒杀完成的记录
//                $killTab = M('goods_secondskilllimit');
////                $killWhere['userId'] = $users['userId'];
////                $killWhere['goodsId'] = $res_goodsId['goodsId'];
////                $killWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
////                $killWhere['state'] = 1;
////                $killLog = $killTab->where($killWhere)->count(); //一条记录代表一次成功秒杀
////                if(($killLog >= $res_goodsId['userSecKillNUM']) || ((int)$cart_res_goodsId['goodsCnt']+$killLog >$res_goodsId['userSecKillNUM'])){
////                    $num = $res_goodsId['userSecKillNUM'] - $killLog; //剩余次数
////                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多秒杀'.$res_goodsId['userSecKillNUM'].'件该商品'.', 还能秒杀'.$num.'件';
////                    return $apiRet;
////                }
//                $existWhere['userId'] = $users['userId'];
//                $existWhere['goodsId'] = $res_goodsId['goodsId'];
//                $existOrderW['o.orderStatus'] = ['IN', [0, 1, 2, 3, 4]];
//                //$existWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
//                $existWhere['state'] = 1;
//                $existKillLog = $killTab->where($existWhere)->group('orderId')->select();
//
//                $existOrderField = [
//                    'o.orderId'
//                ];
//                $existOrderId = [];
//                foreach ($existKillLog as $val) {
//                    $existOrderId[] = $val['orderId'];
//                }
//                $existOrderW['o.orderFlag'] = 1;
//                $existOrderW['o.orderId'] = ['IN', $existOrderId];
//                $existOrder = M('orders o')
//                    ->join("LEFT JOIN wst_order_goods og ON og.orderId=o.orderId")
//                    ->where($existOrderW)
//                    ->field($existOrderField)
//                    ->count();
//                if ($existOrder >= $res_goodsId['userSecKillNUM']) {
//                    $num = $res_goodsId['userSecKillNUM'] - $existOrder; //剩余可购买次数
//                    if ($num < 0) {
//                        $num = 0;
//                    }
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多购买'.$res_goodsId['userSecKillNUM'].'次该商品'.', 还能秒杀'.$num.'次';
//                    $apiRet = returnData(null, -1, 'error', '每个用户最多购买' . $res_goodsId['userSecKillNUM'] . '次该商品' . ', 还能秒杀' . $num . '次');
//                    return $apiRet;
//                }
//            }
//
//        }
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $res_user_address['areaId3']);
//            if (!empty($isDistriScope)) {
//                M()->rollback();
//                return $isDistriScope;
//            }
//        }
//
//        //判断每笔订单 是否达到配送条件
//        $where3["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsId); $i++) {
//            $where3["goodsId"] = $goodsId[$i];//获取购物车中商品数据
//            $goodsId[$i] = $cart->where($where3)->find();
//
//            $where4["goodsId"] = $goodsId[$i]["goodsId"];//获取商品数据
//            $goodsId2[$i] = $goods->where($where4)->field(array("goodsDesc"), true)->find();
//            $goodsId2[$i]["cartId"] = $goodsId[$i]["cartId"];
//            $goodsId2[$i]["userId"] = $goodsId[$i]["userId"];
//            $goodsId2[$i]["isCheck"] = $goodsId[$i]["isCheck"];
//            $goodsId2[$i]["goodsAttrId"] = $goodsId[$i]["goodsAttrId"];
//            $goodsId2[$i]["goodsCnt"] = $goodsId[$i]["goodsCnt"];
//            $goodsId2[$i]["remarks"] = $goodsId[$i]["remarks"];
//        }
//        //return $goodsId2;
//
//        //给每个商品添加自己的店铺
//        for ($i = 0; $i < count($goodsId2); $i++) {
//            $goodsId2[$i]["shopcm"] = self::getCartShops($goodsId2[$i]['shopId']);
//        }
//        //return $goodsId2;
//        $result = array();
//        foreach ($goodsId2 as $k => $v) {
//            $result[$v["shopId"]][] = $v;//根据Id归类
//        }
//        //$result = array_merge($result);
//        $result = array_values($result);//重建索引
//        $result[0] = rankGoodsPrice($result[0]); //商品等级价格处理
//        //return $result;
//        //生成订单数据
//        $orderids = M('orderids');
//        $user_address = M("user_address")->where("addressId = '{$addressId}' and addressFlag = '1'")->find();//用户地址数据
//
//        //订单公用参数
//        $data["areaId1"] = $user_address["areaId1"];
//        $data["areaId2"] = $user_address["areaId2"];
//        $data["areaId3"] = $user_address["areaId3"];
//        $data["payType"] = "0";//支付方式 0：货到付款 1：在线支付
//        $data["isSelf"] = $isSelf;//是否自提
//        $data["isPay"] = "0";//是否支付
//
//        $data["userId"] = $users['userId'];//用户Id
//        $data["userName"] = $user_address["userName"];//收货人名称
//        $data["communityId"] = $user_address["communityId"];//收货地址所属社区id
//
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $user_address['setaddress'] .
//            $user_address["address"];//收件人地址
//
//        $data["userPhone"] = $user_address["userPhone"];//收件人手机
//        $data["userPostCode"] = $user_address["postCode"];//收件人邮编
//        //$data["isInvoice"] = "0";//是否需要发票
//        $data["isInvoice"] = (int)I('isInvoice', 0);//是否需要发票
//        $data["invoiceClient"] = (int)I('invoiceClient', 0);//发票id
//
//        $data["orderRemarks"] = $orderRemarks;//订单备注
//        $data["requireTime"] = $requireTime;//要求送达时间
//        //$data["orderRemarks"] = "app下单";//订单备注
//        //$data["requireTime"] = date("Y-m-d H:i:s",time()+3600);//要求送达时间
//
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "3";//订单来源 0:商城 1:微信 2:手机版 3:app 4：小程序
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = $payFrom;//支付来源 0:现金 1:支付宝，2：微信,3:余额
//        //$data["settlementId"] = "0";//结算记录ID 用户确认收货补上id 创建 wst_order_settlements 结算表 再更改结算记录Id
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        //检测是否符合店铺设置的配送起步价
//        $checkInfo = checkdeliveryStartMoney($result, $users);
//        if ($checkInfo['state'] == false) {
//            unset($apiRet);
////            $apiRet['apiCode'] = '000202';
////            $apiRet['apiInfo'] = '未达到店铺订单配送起步价';
////            $apiRet['apiState'] = 'error';
////            $apiRet['apiData'] = $checkInfo['shopInfo'];
//            $apiRet = returnData($checkInfo['shopInfo'], -1, 'error', '未达到店铺订单配送起步价');
//            return $apiRet;
//        }
//        //return $data;
//        for ($i = 0; $i < count($result); $i++) {
//            $shopId = $result[$i][0]['shopId'];
//            //生成订单号
//            $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//            $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//            $data["orderNo"] = $orderNo;//订单号
//            $data["shopId"] = $result[$i]["0"]["shopcm"]["shopId"];//店铺id
//            $data["orderStatus"] = "0";//订单状态为受理
//            $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//            $result1 = $result[$i];
//            for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
//                //获取当前订单所有商品总价
//                $totalMoney[$i][$i1] = (float)$result[$i][$i1]["shopPrice"] * (int)$result[$i][$i1]["goodsCnt"];
//                //$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i1]["goodsAttrId"],$result[$i][$i1]["goodsId"],$result[$i][$i1]["shopPrice"],$result[$i][$i1]["goodsCnt"],$result[$i][$i1]["shopId"])['goodsPrice'];
//                $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result[$i][$i1]["goodsAttrId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
//                $totalMoney[$i][$i1] = $goodsTotalMoney;
//            }
//            $totalMoney[$i] = array_sum($totalMoney[$i]);//计算总金额
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//            //--------------------------------------------------
//
//            // ---------- 优惠券的使用(修改后的) --- @author liusijia --- 2019-08-15 18:46 --- start ---
//            $couponUserInfo = array();
//            //如果优惠券不为空
//            if (!empty($cuid)) {
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere = array();
//                $couponWhere['id'] = $cuid;
//                $couponWhere['userId'] = $users['userId'];
//                if ($mod_coupons_users->where($couponWhere)->find()) {//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['id'] = $cuid;
//                    if ($mod_coupons_users->where($couponWhere)->find()) {//是否是未使用状态
//                        $couponUserInfo = $mod_coupons_users->where($couponWhere)->find();
//                        $couponWhere = array();
////                        $couponWhere['validStartTime'] = array('ELT', date('Y-m-d'));
////                        $couponWhere['validEndTime'] = array('EGT', date('Y-m-d'));
//                        $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                        if ($couponUserInfo['couponExpireTime'] > date('Y-m-d H:i:s')) {//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];;
//                            if ($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]) {//是否满足使用条件
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            } else {
//                                M()->rollback();
////                                $apiRet['apiCode'] = '000104';
////                                $apiRet['apiInfo'] = '未达到最低消费金额';
////                                $apiRet['apiData'] = null;
////                                $apiRet['apiState'] = 'error';
//                                $apiRet = returnData(null, -1, 'error', '未达到最低消费金额');
//                                return $apiRet;
//                            }
//                        } else {
//                            M()->rollback();
////                            $apiRet['apiCode'] = '000105';
////                            $apiRet['apiInfo'] = '优惠券已过期';
////                            $apiRet['apiData'] = null;
////                            $apiRet['apiState'] = 'error';
//                            $apiRet = returnData(null, -1, 'error', '优惠券已过期');
//                            return $apiRet;
//                        }
//                    } else {
//                        M()->rollback();
////                        $apiRet['apiCode']='000106';
////                        $apiRet['apiInfo']='优惠券已使用';
////                        $apiRet['apiData'] = null;
////                        $apiRet['apiState']='error';
//                        $apiRet = returnData(null, -1, 'error', '优惠券已使用');
//                        return $apiRet;
//                    }
//                } else {
//                    M()->rollback();
////                    $apiRet['apiCode']='000107';
////                    $apiRet['apiInfo']='优惠券未领取...';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '优惠券未领取...');
//
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => ((!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId']),
//                    'goods_id_arr' => $goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr, $msg);
//                if (!$checkRes) {
//                    M()->rollback();
////                    $apiRet['apiCode']='000108';
////                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '优惠券使用失败，' . $msg);
//
//                    return $apiRet;
//                }
//            }
//            // ---------- 优惠券的使用(修改后的) --- @author liusijia --- 2019-08-15 18:46 --- end ---
//
//
//            //--------------------------------------------------
//
//
//            //$data["totalMoney"] = (float)$totalMoney[$i];//商品总金额
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i])*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]);//所得积分
//
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- start ---
//            for ($j1 = 0; $j1 < count($result1); $j1++) {//商品总金额
//                $goods_integral = 0;
//                //获取当前订单所有商品总价
//                $totalMoney_s[$i][$j1] = (float)$result1[$j1]["shopPrice"] * (int)$result1[$j1]["goodsCnt"];
//                //$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i1]["goodsAttrId"],$result[$i][$i1]["goodsId"],$result[$i][$i1]["shopPrice"],$result[$i][$i1]["goodsCnt"],$result[$i][$i1]["shopId"])['goodsPrice'];
//                $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result1[$j1]["goodsAttrId"], $result1[$j1]["goodsId"], $result1[$j1]["shopPrice"], $result1[$j1]["goodsCnt"], $result1[$j1]["shopId"])['totalMoney'];
//                $totalMoney_s[$i][$j1] = $goodsTotalMoney;
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result1[$j1]["goodsId"]))->find();
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMoney_s[$i][$j1] = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['memberPrice'];
//                        $goods_integral = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['integralReward'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMoney_s[$i][$j1] = $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//            }
//            if (is_array($totalMoney_s[$i])) {
//                $totalMoney[$i] = array_sum($totalMoney_s[$i]);//计算总金额
//            } else {
//                $totalMoney[$i] = $totalMoney_s[$i];//计算总金额
//            }
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//
//            //如果优惠券不为空
//            if (!empty($cuid)) {
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere = array();
//                $couponWhere['id'] = $cuid;
//                $couponWhere['userId'] = $users['userId'];
//                if ($mod_coupons_users->where($couponWhere)->find()) {//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['id'] = $cuid;
//                    if ($mod_coupons_users->where($couponWhere)->find()) {//是否是未使用状态
//                        $couponUserInfo = $mod_coupons_users->where($couponWhere)->find();
//                        $couponWhere = array();
////                        $couponWhere['validStartTime'] = array('ELT', date('Y-m-d'));
////                        $couponWhere['validEndTime'] = array('EGT', date('Y-m-d'));
//                        $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                        if ($couponUserInfo['couponExpireTime'] > date('Y-m-d H:i:s')) {//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                            if ($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]) {//是否满足使用条件
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            } else {
//                                M()->rollback();
////                                $apiRet['apiCode'] = '000104';
////                                $apiRet['apiInfo'] = '未达到最低消费金额';
////                                $apiRet['apiData'] = null;
////                                $apiRet['apiState'] = 'error';
//                                $apiRet = returnData(null, -1, 'error', '未达到最低消费金额');
//                                return $apiRet;
//                            }
//                        } else {
//                            M()->rollback();
////                            $apiRet['apiCode'] = '000105';
////                            $apiRet['apiInfo'] = '优惠券已过期';
////                            $apiRet['apiData'] = null;
////                            $apiRet['apiState'] = 'error';
//                            $apiRet = returnData(null, -1, 'error', '优惠券已过期');
//
//                            return $apiRet;
//                        }
//                    } else {
//                        M()->rollback();
////                        $apiRet['apiCode']='000106';
////                        $apiRet['apiInfo']='优惠券已使用';
////                        $apiRet['apiData'] = null;
////                        $apiRet['apiState']='error';
//                        $apiRet = returnData(null, -1, 'error', '优惠券已使用');
//
//                        return $apiRet;
//                    }
//                } else {
//                    M()->rollback();
////                    $apiRet['apiCode']='000107';
////                    $apiRet['apiInfo']='优惠券未领取...';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '优惠券未领取...');
//
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => ((!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId']),
//                    'goods_id_arr' => $goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr, $msg);
//                if (!$checkRes) {
//                    M()->rollback();
////                    $apiRet['apiCode']='000108';
////                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '优惠券使用失败，' . $msg);
//
//                    return $apiRet;
//                }
//            }
//
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i]+$goods_integral)*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]) + $goods_integral;//所得积分
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- end ---
//
//            $data["deliverMoney"] = $result[$i]["0"]["shopcm"]["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//
//            $data["deliverType"] = "1";//配送方式 门店配送
//
//            //设置订单配送方式
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//
//            //当前店铺是否是达达配送 且当前订单 不为自提
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 2 and $isSelf !== 1) {
//
//
//                $funData['shopId'] = $shopId;
//                $funData['areaId2'] = $data["areaId2"];
//                $funData['orderNo'] = $data["orderNo"];
//                $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//                $funData['userName'] = $data["userName"];//收货人姓名
//                $funData['userAddress'] = $data["userAddress"];//收货人地址
//                $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//                $dadaresFun = self::dadaDeliver($funData);
//
//                if ($dadaresFun['status'] == -6) {
//                    M()->rollback();
//                    //获取城市出错
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，获取城市出错';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == -7) {
//                    M()->rollback();
//                    //不在达达覆盖城市内
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == 0) {
//                    //获取成功
//                    $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                    $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//
//                    // $data["deliverMoney"] =  $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//
//                }
//                $data["deliverType"] = "2";//配送方式 达达配送
//            }
//
//
//            //--------------------金额满减运费 start---------------------------
//
//
//            if ((float)$data["totalMoney"] >= (float)$result[$i]["0"]["shopcm"]["deliveryFreeMoney"]) {
//                $data["deliverMoney"] = 0;
//            }
//            //$data["totalMoney"]
//            //-----------------------金额满减运费end----------------------
//
//            //在使用运费之前处理好运费一些相关问题
//            if ($isSelf == 1) {//如果为自提订单 将运费重置为0
//                $data["deliverMoney"] = 0;
//            }
//
//
//            $data["needPay"] = $totalMoney[$i] + $data["deliverMoney"];//需缴费用 加运费-----
//            $data["realTotalMoney"] = (float)$totalMoney[$i] + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//
//
//            //使用积分
//            if ($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1) {
//                //获取比例
//                $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//                //最多可抵用的钱
//                $realTotalMoney = (float)$data["realTotalMoney"] - 0.01;
//                //根据比例计算 积分最多可抵用的钱  总积分/比例积分*比例金额
//                $zdSMoney = (int)$users['userScore'] / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//                //$realTotalMoneyAzdSMoney = (float)$realTotalMoney - (float)$zdSMoney;
//                //计算做多可抵用多少钱的结果
//                if ((float)$realTotalMoney >= (float)$zdSMoney) {
//                    $zuiduoMoney = $zdSMoney;
//                } else {
//                    $zuiduoMoney = $realTotalMoney;
//                }
//                //计算反推出 相应的可抵用的积分
//                $zuiduoScore = $zuiduoMoney / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//
//                //return array('a'=>$zuiduoMoney,'b'=>$scoreScoreCashRatio);
//                //return array('抵扣最终的钱'=>$zuiduoMoney,'所参与抵扣的积分'=>$zuiduoScore);
//
//                $data["needPay"] = $totalMoney[$i] + $data["deliverMoney"] - $zuiduoMoney;//需缴费用 加运费 减去抵用的钱
//                $data["realTotalMoney"] = (float)$totalMoney[$i] + (float)$data["deliverMoney"] - $zuiduoMoney;//实际订单总金额 加运费 减去地用的钱
//
//
//                $data["useScore"] = $zuiduoScore;//本次交易使用的积分数
//                $data["scoreMoney"] = $zuiduoMoney;//积分兑换的钱 完成交易 确认收货在给积分
//            }
//
//            $shop_info = $sm->getShopInfo($data["shopId"]);
//            $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//            $appRealTotalMoney[$i] = $data["realTotalMoney"];//获取订单金额
//            //如果是余额支付的话,判断用户余额是否充足
//            if ($payFrom == 3 && $users['balance'] < $data['realTotalMoney']) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode'] = '000078';
////                $apiRet['apiInfo'] = '余额不足';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState'] = 'error';
//                $apiRet = returnData(null, -1, 'error', '余额不足');
//                return $apiRet;
//            }
//
//
//            //如果是余额支付,扣除用户对应的余额
//            if ($payFrom == 3 && $users['balance'] >= $data['realTotalMoney']) {
//                $userEdit['balance'] = $users['balance'] - $data['realTotalMoney'];
//                $userEditRes = M('users')->where("userId='" . $users['userId'] . "'")->save($userEdit);
//                if ($userEditRes === false) {
//                    M()->rollback();
//                    unset($apiRet);
////                    $apiRet['apiCode'] = '000070';
////                    $apiRet['apiInfo'] = '余额支付失败';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState'] = 'error';
//                    $apiRet = returnData(null, -1, 'error', '余额支付失败');
//                    return $apiRet;
//                }
//                $data['orderStatus'] = 0; //支付成功
//                $data['isPay'] = 1; //已支付
//
//                //余额消费记录
//                M('user_balance')->add(array(
//                    'userId' => $users['userId'],
//                    'balance' => $data['realTotalMoney'],
//                    'dataSrc' => 1,
//                    'orderNo' => $orderNo,
//                    'dataRemarks' => "订单支出",
//                    'balanceType' => 2,
//                    'createTime' => date('Y-m-d H:i:s'),
//                    'shopId' => 0
//                ));
//            }
//
//            //写入订单
//            $orderId[$i] = $orders->add($data);
//            if ($isSelf) {//如果为自提订单 生成提货码
//                //生成提货码
//                $mod_user_self_goods = M('user_self_goods');
//                $mod_user_self_goods_add['orderId'] = $orderId[$i];
//                $mod_user_self_goods_add['source'] = $orderId[$i] . $users['userId'] . $shopId;
//                $mod_user_self_goods_add['userId'] = $users['userId'];
//                $mod_user_self_goods_add['shopId'] = $shopId;
//                $mod_user_self_goods->add($mod_user_self_goods_add);
//            }
//
//            //建立订单记录
//            $data_order["orderId"] = $orderId[$i];
//            $data_order["logContent"] = "小程序下单成功，等待支付";
//            if ($payFrom == 3) {
//                $data_order["logContent"] = "小程序下单成功，余额支付成功";
//            }
////            $data_order["logUserId"] = $users['userId'];
////            $data_order["logType"] = 0;
////            $data_order["logTime"] = date('Y-m-d H:i:s');
////
////            $mlogo->add($data_order);
//            $content = $data_order['logContent'];
//            $logParams = [
//                'orderId' => $orderId[$i],
//                'logContent' => $content,
//                'logUserId' => $users['userId'],
//                'logUserName' => '用户',
//                'orderStatus' => $data['orderStatus'],
//                'payStatus' => 0,
//                'logType' => 0,
//                'logTime' => date('Y-m-d H:i:s'),
//            ];
//            M('log_orders')->add($logParams);
//
//
//            //更新优惠券状态为已使用 - 原来的
//            /*if(!empty($couponId)){
//                $mod_coupons_users_data['orderNo'] = $orderNo;
//                $mod_coupons_users_data['couponStatus'] = 0;
//                $mod_coupons_users->where("couponId = " . $couponId)->save($mod_coupons_users_data);
//            }*/
//            //更新优惠券状态为已使用 - 修改后的
//            if (!empty($cuid)) {
//                $mod_coupons_users_data['orderNo'] = $orderNo;
//                $mod_coupons_users_data['couponStatus'] = 0;
////                $mod_coupons_users->where("couponId = " . $couponId)->save($mod_coupons_users_data);//原来的
//                $mod_coupons_users->where("id = " . $cuid)->save($mod_coupons_users_data);
//            }
//
//            //使用积分
//            $users_service_module = new UsersServiceModule();
//            if ($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1) {
//                //减去用户当前所持有积分
////                M('users')->where("userId = {$users['userId']}")->setDec('userScore', $zuiduoScore);
//                //积分处理-start
//                $score = (int)$zuiduoScore;
//                $users_id = $users['userId'];
//                $result = $users_service_module->deduction_users_score($users_id, $score, M());
//                if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
//                    M()->rollback();
//                    return returnData(null, -1, 'error', $result['msg']);
//                }
//                //积分处理-end
//                //加上用户历史消费积分
//                M('users')->where("userId = {$users['userId']}")->setInc('userTotalScore', $zuiduoScore);
//                //写入积分消费记录
//                $user_add_pay['dataSrc'] = 1;//来源订单
//                $user_add_pay['userId'] = $users['userId'];//用户id
//                $user_add_pay['score'] = $zuiduoScore;//积分
//                $user_add_pay['dataRemarks'] = "抵用现金";
//                $user_add_pay['scoreType'] = 2;
//                $user_add_pay['createTime'] = date("Y-m-d H:i:s");
//                M("user_score")->add($user_add_pay);
//            }
//
//            //将订单商品写入order_goods
//            for ($i2 = 0; $i2 < count($result[$i]); $i2++) {//循环商品数据
//                $data_order_goods["orderId"] = $orderId[$i];
//                $data_order_goods["goodsId"] = $result[$i][$i2]["goodsId"];
//                $data_order_goods["goodsNums"] = $result[$i][$i2]["goodsCnt"];
//                $data_order_goods["goodsPrice"] = $result[$i][$i2]["shopPrice"];
//                //$data_order_goods["goodsAttrId"] = 0;原来
//                $data_order_goods["goodsAttrId"] = $result[$i][$i2]["goodsAttrId"];
//                $data_order_goods["remarks"] = $result[$i][$i2]["remarks"];
//                if (is_null($data_order_goods["remarks"])) {
//                    $data_order_goods["remarks"] = '';
//                }
//                //2019-6-14 start
//                $getGoodsAttrPrice = getGoodsAttrPrice($users['userId'], $result[$i][$i2]["goodsAttrId"], $result[$i][$i2]["goodsId"], $result[$i][$i2]["shopPrice"], $result[$i][$i2]["goodsCnt"], $result[$i][$i2]["shopId"]);
//                $data_order_goods["goodsAttrName"] = $getGoodsAttrPrice['goodsAttrName'];
//                $data_order_goods["goodsPrice"] = $getGoodsAttrPrice["goodsPrice"];
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result[$i][$i2]["goodsId"]))->find();
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $data_order_goods["goodsPrice"] = $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $data_order_goods["goodsPrice"] = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//                if (!empty($result[$i][$i2]["goodsAttrId"])) {
//                    /*$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i2]["goodsAttrId"],$result[$i][$i2]["goodsId"],$result[$i][$i2]["shopPrice"],$result[$i][$i2]["goodsCnt"],$result[$i][$i2]["shopId"]);
//                    $data_order_goods["goodsAttrName"] = $getGoodsAttrPrice['goodsPrice'];
//                    $data_order_goods["goodsPrice"] = $getGoodsAttrPrice["goodsAttrName"];*/
//                    /**
//                     * 2019-06-15
//                     * 减少属性的库存
//                     * */
//                    $goodsAttrId = $result[$i][$i2]["goodsAttrId"];
//                    //$goodsAttrList = M('goods_attributes')->where("id IN($goodsAttrId)")->setDec('attrStock',$result[$i][$i2]['goodsCnt']);
//                    $goodsAttrList = M('goods_attributes')->where("id IN($goodsAttrId)")->select();
//                    foreach ($goodsAttrList as $lv) {
//                        if ($lv['goodsId'] == $result[$i][$i2]['goodsId']) {
////                            M('goods_attributes')->where("id='".$lv['id']."'")->setDec('attrStock',$result[$i][$i2]['goodsCnt']);
//                            $updateGoodsAttrStockResult = updateGoodsAttrStockByRedis($lv['id'], $result[$i][$i2]['goodsCnt']);
//                            if ($updateGoodsAttrStockResult['code'] == -1) {
//                                M()->rollback();
//                                return $updateGoodsAttrStockResult;
//                            }
//                        }
//                    }
//                }
//                //2019-6-14 end
//                $data_order_goods["goodsName"] = $result[$i][$i2]["goodsName"];
//                $data_order_goods["goodsThums"] = $result[$i][$i2]["goodsThums"];
//
//                $order_goods->add($data_order_goods);
//
//                //减去对应商品数量
//                $where5["goodsId"] = $result[$i][$i2]['goodsId'];
////                $goods->where($where5)->setDec('goodsStock',$result[$i][$i2]['goodsCnt']);
//                $updateGoodsStockResult = updateGoodsStockByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                if ($updateGoodsStockResult['code'] == -1) {
//                    M()->rollback();
//
//                    return $updateGoodsStockResult;
//                }
//
//                //更新进销存系统商品的库存
//                //updateJXCGoodsStock($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt'], 1);
//
//                /**
//                 * 2019-06-15 end
//                 * */
//                if ($result[$i][$i2]['isShopSecKill'] == 1) {
////                    $goods->where($where5)->setDec('shopSecKillNUM',$result[$i][$i2]['goodsCnt']); //减去对应商品的秒杀量
//                    $updateGoodsShopSecKillNUMResult = updateGoodsShopSecKillNUMByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                    if ($updateGoodsShopSecKillNUMResult['code'] == -1) {
//                        M()->rollback();
//                        return $updateGoodsShopSecKillNUMResult;
//                    }
//                }
//
//                //限制商品下单次数
//                limitGoodsOrderNum($result[$i][$i2]["goodsId"], $users['userId']);
//
//                //清空对应的购物车商品
//                $where6["userId"] = $users['userId'];
//                $where6["goodsId"] = $result[$i][$i2]['goodsId'];
//                $where6["skuId"] = $result[$i][$i2]['skuId'];
//                $cart->where($where6)->delete();
//
//                //写入秒杀记录表
//                if ($result[$i][$i2]["isShopSecKill"] == 1) {
//                    for ($kii_i = 0; $kii_i < $data_order_goods["goodsNums"]; $kii_i++) {
//                        //一件商品一条数据
//                        $killData['goodsId'] = $result[$i][$i2]["goodsId"];
//                        $killData['userId'] = $users['userId'];
//                        $killData['endTime'] = $result[$i][$i2]["ShopGoodSecKillEndTime"];
//                        $killData['orderId'] = $orderId[$i];
//                        $killData['state'] = 1;
//                        $killData['addtime'] = date('Y-m-d H:i:s', time());
//                        $killTab->add($killData);
//                    }
//                }
//
//            }
//            //建立订单提醒
//
//
//            $data_order_reminds["orderId"] = $orderId[$i];
//            $data_order_reminds["shopId"] = $shopId;//店铺id
//            $data_order_reminds["userId"] = $users['userId'];
//            //$data["userType"] = "0";
//            //$data["remindType"] = "0";
//            $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//
//
//            $order_reminds_statusCode = $order_reminds->add($data_order_reminds);
//
//
//            // 获取生成的所有订单号 返回给小程序支付
//            $wxorderNo[$i] = $orderNo;
//
//            // --- 生成订单成功,推送消息 --- @author liusijia --- start ---
//            /*if ($orderId[$i] > 0) {
//                //订单来源 是 3（app)
//                if (!empty($users) && !empty($users['registration_id']))
//                    pushMessageByRegistrationId('订单消息提醒', "尊敬的顾客，您购买的商品已生成订单，订单编号为 ".$orderNo." 。", $users['registration_id'], []);
//            }*/
//            // --- 生成订单成功,推送消息 --- @author liusijia --- end ---
//        }
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode(array_sum($appRealTotalMoney));//多个订单总金额 单位元
//            //$statusCode["orderNo"] = base64_encode(json_encode($wxorderNo));//订单号
//            $statusCode["orderNo"] = strencode(implode("A", $wxorderNo));//订单号  多个订单号用 A隔开
//
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5(implode("A", $wxorderNo));
//            $wst_order_merge_data['value'] = implode("A", $wxorderNo);
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//            M()->commit();
//            unset($apiRet);
////            $apiRet['apiCode']='000067';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//
//        } else {
//            M()->rollback();
//            unset($apiRet);
////            $apiRet['apiCode']='000068';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//
//
//    }

    //提交订单 ---货到付款 支持sku,过渡期使用
//    public function SubmitOrderSku($loginName, $addressId, $goodsId, $orderRemarks, $requireTime, $couponId, $isSelf = 0, $getuseScore, $lng, $lat, $cuid, $goodsSku)
//    {
//        M()->startTrans();
//        $mlogo = M('log_orders');
//        $orders = M("orders");
//        $goods = M("goods");
//        $cart = M("cart");
//        $order_goods = M("order_goods");
//        $order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $order_communitys_mod = M('communitys');
//        $shops_mod = M('shops');
//        $sm = D('Home/Shops');
//        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//
//        //获取用户Id 根据登陆名
//        $where["userFlag"] = 1;
//        $where["loginName"] = $loginName;
//        $users = M("users")->where($where)->find();
//        //$users['userId'];
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($users['userId']);
//
//        //检查收货地址是否存在
//        $where1["addressId"] = (int)$addressId;
//        $where1["addressFlag"] = 1;
//        $where1["userId"] = $users['userId'];
//        $user_address = M("user_address");
//        $res_user_address = $user_address->where($where1)->find();
//        if (empty($res_user_address)) {
//            M()->rollback();
//            unset($apiRet);
////            $apiRet['apiCode']='000065';
////            $apiRet['apiInfo']='请添加收货地址';
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//            return $apiRet;
//        }
//        //检查商品Id 是否在购物车
//        $goodsId = explode(",", $goodsId);//分割出商品id
//        $where2["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsSku); $i++) {
//            $productId = $goodsSku[$i]['goodsId'];//商品id
//            $skuId = $goodsSku[$i]['skuId'];
//            $where2["goodsId"] = $productId;
//            $where2["skuId"] = $skuId;
//            $iS_goodsId = $cart->where($where2)->find();
//
//            if (empty($iS_goodsId)) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000066';
////                $apiRet['apiInfo']='此商品不存在于购物车';
//                $apiRet['goodsId'] = $productId;
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '此商品不存在于购物车');
//                return $apiRet;
//
//            }
//
//            $goods_info = M("goods")->where("goodsId='{$productId}'")->find();
//            //针对新人专享商品，判断用户是否可以购买
//            $isBuyNewPeopleGoods = isBuyNewPeopleGoods($productId, $users['userId']);
//            if (!$isBuyNewPeopleGoods) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000041';
////                $apiRet['apiInfo']=$goods_info['goodsName'] . ' 是新人专享商品，您不能购买!';
//                $apiRet['goodsId'] = $productId;
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', $goods_info['goodsName'] . ' 是新人专享商品，您不能购买!');
//                return $apiRet;
//            }
//        }
//        //判断商品是否在售 以及库存是否充足 注意预售 和秒杀的商品
//        for ($i = 0; $i < count($goodsSku); $i++) {
//            unset($where);
//            $productId = $goodsSku[$i]['goodsId'];//商品id
//            $skuId = $goodsSku[$i]['skuId'];
//            $where['goodsId'] = $productId;
//            $res_goodsId = $goods->lock(true)->where($where)->find();
//
//            if ($res_goodsId['isSale'] == 0) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000069';
////                $apiRet['apiInfo']='商品已下架';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品已下架');
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsStatus'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000070';
////                $apiRet['apiInfo']='商品已禁售';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品已禁售');
//
//                return $apiRet;
//            }
//
//            if ($res_goodsId['goodsFlag'] == -1) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000071';
////                $apiRet['apiInfo']='商品不存在';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品不存在');
//
//                return $apiRet;
//            }
//
//            $checkGoodsFlashSale = checkGoodsFlashSale($goodsId[$i]); //检查商品限时状况
//            if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//                return $checkGoodsFlashSale;
//            }
//
//            //判断是否为限制下单次数的商品 start
//            $checkRes = checkGoodsOrderNum($res_goodsId['goodsId'], $users['userId']);
//            if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//                return $checkRes;
//            }
//
//
//            //获取购物车里的商品数量
//            unset($where);
//            $where['goodsId'] = $productId;
//            $where['skuId'] = $skuId;
//            $cart_res_goodsId = $cart->where($where)->find();
//            if ($skuId > 0) {
//                $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsSku[$i]['goodsId'], 'skuId' => $skuId])->find();
//                foreach ($replaceSkuField as $rk => $rv) {
//                    if ((int)$systemSkuSpec[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                        continue;
//                    }
//                    if (in_array($rk, ['dataFlag', 'addTime'])) {
//                        continue;
//                    }
//                    if (isset($res_goodsId[$rv])) {
//                        $res_goodsId[$rv] = $systemSkuSpec[$rk];
//                    }
//                }
//            }
//
//            if ((int)$res_goodsId['goodsStock'] < (int)$cart_res_goodsId['goodsCnt']) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000072';
////                $apiRet['apiInfo']='商品库存不足';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                return $apiRet;
//            }
//
//            /**
//             * 2019-06-15 start
//             * 判断属性库存是否充足
//             * */
//            $goodsAttr = M('goods_attributes');
//            if (!empty($cart_res_goodsId['goodsAttrId'])) {
//                $goodsAttrIdArr = explode(',', $cart_res_goodsId['goodsAttrId']);
//                foreach ($goodsAttrIdArr as $iv) {
//                    $goodsAttrInfo = $goodsAttr->lock(true)->where("id='" . $iv . "'")->find();
//                    if ($goodsAttrInfo['attrStock'] <= 0) {
//                        M()->rollback();
//                        unset($apiRet);
////                        $apiRet['apiCode']='000072';
////                        $apiRet['apiInfo']='商品库存不足';
//                        $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                        $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                        $apiRet['apiState']='error';
//                        $apiRet = returnData($apiRet, -1, 'error', '商品库存不足');
//
//                        return $apiRet;
//                    }
//                }
//            }
//            /**
//             * 2019-06-15 end
//             * */
//
//            //检测配送区域
//            /*if(!$lng || !$lat){
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000073';
////                $apiRet['apiInfo']='经纬度必填';
//                $apiRet['goodsId']=$res_goodsId['goodsId'];
//                $apiRet['goodsName']=$res_goodsId['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet,-1,'error','经纬度必填');
//                return $apiRet;
//            }*/
//            $shopConfig = M('shop_configs')->where(['shopId' => $res_goodsId['shopId']])->find();
//            if ($shopConfig['deliveryLatLngLimit'] == 1) {
//                $dcheck = checkShopDistribution($res_goodsId['shopId'], $res_user_address['lng'], $res_user_address['lat']);
//                if (!$dcheck) {
//                    M()->rollback();
//                    unset($apiRet);
////                    $apiRet['apiCode']='000074';
////                    $apiRet['apiInfo']='配送范围超出';
//                    $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                    $apiRet['goodsName'] = $res_goodsId['goodsName'];
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//
//                    return $apiRet;
//                }
//                //END
//            }
//
//
//            //秒杀商品限量控制
//            if ($res_goodsId['isShopSecKill'] == 1) {
//                unset($apiRet);
//                $apiRet['apiCode'] = '';
//                $apiRet['apiInfo'] = '';
//                $apiRet['goodsId'] = $res_goodsId['goodsId'];
//                $apiRet['goodsName'] = $res_goodsId['goodsName'];
//                $apiRet['apiState'] = 'error';
//                if ((int)$res_goodsId['shopSecKillNUM'] < (int)$cart_res_goodsId['goodsCnt']) {
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000075';
////                    $apiRet['apiInfo'] = '秒杀库存不足';
//                    $apiRet = returnData(null, -1, 'error', '秒杀库存不足');
//                    return $apiRet;
//                }
//                //已经秒杀完成的记录
//                $killTab = M('goods_secondskilllimit');
////                $killWhere['userId'] = $users['userId'];
////                $killWhere['goodsId'] = $res_goodsId['goodsId'];
////                $killWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
////                $killWhere['state'] = 1;
////                $killLog = $killTab->where($killWhere)->count(); //一条记录代表一次成功秒杀
////                if(($killLog >= $res_goodsId['userSecKillNUM']) || ((int)$cart_res_goodsId['goodsCnt']+$killLog >$res_goodsId['userSecKillNUM'])){
////                    $num = $res_goodsId['userSecKillNUM'] - $killLog; //剩余次数
////                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多秒杀'.$res_goodsId['userSecKillNUM'].'件该商品'.', 还能秒杀'.$num.'件';
////                    return $apiRet;
////                }
//                $existWhere['userId'] = $users['userId'];
//                $existWhere['goodsId'] = $res_goodsId['goodsId'];
//                //$existWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
//                $existWhere['state'] = 1;
//                $existKillLog = $killTab->where($existWhere)->group('orderId')->select();
//
//                $existOrderField = [
//                    'o.orderId'
//                ];
//                $existOrderId = [];
//                foreach ($existKillLog as $val) {
//                    $existOrderId[] = $val['orderId'];
//                }
//                $existOrderW['o.orderFlag'] = 1;
//                $existOrderW['o.orderId'] = ['IN', $existOrderId];
//                $existOrder = M('orders o')
//                    ->join("LEFT JOIN wst_order_goods og ON og.orderId=o.orderId")
//                    ->where($existOrderW)
//                    ->field($existOrderField)
//                    ->count();
//                if ($existOrder >= $res_goodsId['userSecKillNUM']) {
//                    $num = $res_goodsId['userSecKillNUM'] - $existOrder; //剩余可购买次数
//                    if ($num < 0) {
//                        $num = 0;
//                    }
//                    M()->rollback();
////                    $apiRet['apiCode'] = '000076';
////                    $apiRet['apiInfo'] = '每个用户最多购买'.$res_goodsId['userSecKillNUM'].'次该商品'.', 还能秒杀'.$num.'次';
//                    $apiRet = returnData(null, -1, 'error', '每个用户最多购买' . $res_goodsId['userSecKillNUM'] . '次该商品' . ', 还能秒杀' . $num . '次');
//                    return $apiRet;
//                }
//            }
//
//        }
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $res_user_address['areaId3']);
//            if (!empty($isDistriScope)) {
//                M()->rollback();
//                return $isDistriScope;
//            }
//        }
//
//        //判断每笔订单 是否达到配送条件
//        $where3["userId"] = $users['userId'];
//        for ($i = 0; $i < count($goodsSku); $i++) {
//            $productId = $goodsSku[$i]['goodsId'];
//            $skuId = $goodsSku[$i]['skuId'];
//            $where3["goodsId"] = $productId;//获取购物车中商品数据
//            $where3["skuId"] = $skuId;
//            $goodsId[$i] = $cart->where($where3)->find();
//
//            $where4["goodsId"] = $productId;//获取商品数据
//            $goodsId2[$i] = $goods->where($where4)->field(array("goodsDesc"), true)->find();
//            if ($skuId > 0) {
//                $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsSku[$i]['goodsId'], 'skuId' => $skuId])->find();
//                foreach ($replaceSkuField as $rk => $rv) {
//                    if ((int)$systemSkuSpec[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                        continue;
//                    }
//                    if (in_array($rk, ['dataFlag', 'addTime'])) {
//                        continue;
//                    }
//                    if (isset($goodsId2[$i][$rv])) {
//                        $goodsId2[$i][$rv] = $systemSkuSpec[$rk];
//                    }
//                }
//            }
//            $goodsId2[$i]["cartId"] = $goodsId[$i]["cartId"];
//            $goodsId2[$i]["userId"] = $goodsId[$i]["userId"];
//            $goodsId2[$i]["isCheck"] = $goodsId[$i]["isCheck"];
//            $goodsId2[$i]["goodsAttrId"] = $goodsId[$i]["goodsAttrId"];
//            $goodsId2[$i]["skuId"] = $skuId;
//            $goodsId2[$i]["goodsCnt"] = $goodsId[$i]["goodsCnt"];
//            $goodsId2[$i]["remarks"] = $goodsId[$i]["remarks"];
//        }
//        //return $goodsId2;
//
//        //给每个商品添加自己的店铺
//        for ($i = 0; $i < count($goodsId2); $i++) {
//            $goodsId2[$i]["shopcm"] = self::getCartShops($goodsId2[$i]['shopId']);
//        }
//        //return $goodsId2;
//        $result = array();
//        foreach ($goodsId2 as $k => $v) {
//            $result[$v["shopId"]][] = $v;//根据Id归类
//        }
//        //$result = array_merge($result);
//        $result = array_values($result);//重建索引
//        $result[0] = rankGoodsPrice($result[0]); //商品等级价格处理
//        //return $result;
//        //生成订单数据
//        $orderids = M('orderids');
//        $user_address = M("user_address")->where("addressId = '{$addressId}' and addressFlag = '1'")->find();//用户地址数据
//
//        //订单公用参数
//        $data["areaId1"] = $user_address["areaId1"];
//        $data["areaId2"] = $user_address["areaId2"];
//        $data["areaId3"] = $user_address["areaId3"];
//        $data["payType"] = "0";//支付方式
//        $data["isSelf"] = $isSelf;//是否自提
//        $data["isPay"] = "0";//是否支付
//
//        $data["userId"] = $users['userId'];//用户Id
//        $data["userName"] = $user_address["userName"];//收货人名称
//        $data["communityId"] = $user_address["communityId"];//收货地址所属社区id
//
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $user_address['setaddress'] .
//            $user_address["address"];//收件人地址
//
//        $data["userPhone"] = $user_address["userPhone"];//收件人手机
//        $data["userPostCode"] = $user_address["postCode"];//收件人邮编
//        //$data["isInvoice"] = "0";//是否需要发票
//        $data["isInvoice"] = (int)I('isInvoice', 0);//是否需要发票
//        $data["invoiceClient"] = (int)I('invoiceClient', 0);//发票id
//
//        $data["orderRemarks"] = $orderRemarks;//订单备注
//        $data["requireTime"] = $requireTime;//要求送达时间
//        //$data["orderRemarks"] = "app下单";//订单备注
//        //$data["requireTime"] = date("Y-m-d H:i:s",time()+3600);//要求送达时间
//
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "3";//订单来源
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = 0;//支付来源
//        //$data["settlementId"] = "0";//结算记录ID 用户确认收货补上id 创建 wst_order_settlements 结算表 再更改结算记录Id
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        //return $data;
//        for ($i = 0; $i < count($result); $i++) {
//            $shopId = $result[$i][0]['shopId'];
//            //生成订单号
//            $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//            $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//            $data["orderNo"] = $orderNo;//订单号
//            $data["shopId"] = $result[$i]["0"]["shopcm"]["shopId"];//店铺id
//            $data["orderStatus"] = "-2";//订单状态为受理
//            $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//            $result1 = $result[$i];
//            for ($i1 = 0; $i1 < count($result[$i]); $i1++) {//商品总金额
//                //获取当前订单所有商品总价
//                $totalMoney[$i][$i1] = (float)$result[$i][$i1]["shopPrice"] * (int)$result[$i][$i1]["goodsCnt"];
//                $getGoodsAttrPrice = getGoodsAttrPrice($users['userId'], $result[$i][$i1]["goodsAttrId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
//                $totalMoney[$i][$i1] = $getGoodsAttrPrice;
//                if ($result[$i][$i1]['skuId'] > 0) {
//                    $totalMoney[$i][$i1] = getGoodsSkuPrice($users['userId'], $result[$i][$i1]["skuId"], $result[$i][$i1]["goodsId"], $result[$i][$i1]["shopPrice"], $result[$i][$i1]["goodsCnt"], $result[$i][$i1]["shopId"])['totalMoney'];
//                }
//            }
//            $totalMoney[$i] = array_sum($totalMoney[$i]);//计算总金额
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//
//            //--------------------------------------------------
//
//            //如果优惠券不为空
//            /*if(!empty($couponId)){
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere['couponId'] = $couponId;
//                $couponWhere['userId'] = $users['userId'];
//                if($mod_coupons_users->where($couponWhere)->find()){//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['couponId'] = $couponId;
//                    if($mod_coupons_users->where($couponWhere)->find()){//是否是未使用状态
//                        $couponWhere = array();
//                        $couponWhere['validStartTime'] =  array('ELT',date('Y-m-d'));
//                        $couponWhere['validEndTime'] = array('EGT',date('Y-m-d'));
//                        if($mod_coupons->where($couponWhere)->find()){//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = $couponId;
//                            if($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]){//是否满足使用条件
//                                $mod_coupons->where($couponWhere)->find();
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            }else{
//                                $apiRet['apiCode']='000104';
//                                $apiRet['apiInfo']='未达到最低消费金额';
//                                $apiRet['apiData'] = null;
//                                $apiRet['apiState']='error';
//                                return $apiRet;
//                            }
//                        }else{
//                            $apiRet['apiCode']='000105';
//                            $apiRet['apiInfo']='优惠券已过期';
//                            $apiRet['apiData'] = null;
//                            $apiRet['apiState']='error';
//                            return $apiRet;
//                        }
//                    }else{
//                        $apiRet['apiCode']='000106';
//                        $apiRet['apiInfo']='优惠券已使用';
//                        $apiRet['apiData'] = null;
//                        $apiRet['apiState']='error';
//                        return $apiRet;
//                    }
//                }else{
//                    $apiRet['apiCode']='000107';
//                    $apiRet['apiInfo']='优惠券未领取...';
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => $couponId,
//                    'good_id_arr' =>$goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr,$msg);
//                if(!$checkRes){
//                    $apiRet['apiCode']='000108';
//                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//            }*/
//
//
//            //--------------------------------------------------
//
//
//            //$data["totalMoney"] = (float)$totalMoney[$i];//商品总金额
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i])*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]);//所得积分
//
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- start ---
//            for ($j1 = 0; $j1 < count($result1); $j1++) {//商品总金额
//                $goods_integral = 0;
//                //获取当前订单所有商品总价
//                $totalMoney_s[$i][$j1] = (float)$result1[$j1]["shopPrice"] * (int)$result1[$j1]["goodsCnt"];
//                //$getGoodsAttrPrice = getGoodsAttrPrice($users['userId'],$result[$i][$i1]["goodsAttrId"],$result[$i][$i1]["goodsId"],$result[$i][$i1]["shopPrice"],$result[$i][$i1]["goodsCnt"],$result[$i][$i1]["shopId"])['goodsPrice'];
//                $goodsTotalMoney = getGoodsAttrPrice($users['userId'], $result1[$j1]["goodsAttrId"], $result1[$j1]["goodsId"], $result1[$j1]["shopPrice"], $result1[$j1]["goodsCnt"], $result1[$j1]["shopId"])['totalMoney'];
//                if ($result1[$j1]["skuId"] > 0) {
//                    $goodsTotalMoney = getGoodsSkuPrice($users['userId'], $result1[$j1]["skuId"], $result1[$j1]["goodsId"], $result1[$j1]["shopPrice"], $result1[$j1]["goodsCnt"], $result1[$j1]["shopId"])['totalMoney'];
//                }
//                $totalMoney_s[$i][$j1] = $goodsTotalMoney;
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result1[$j1]["goodsId"]))->find();
//                    $systemSkuSpec = M('sku_goods_system')->where(['skuId' => $result1[$j1]["skuId"]])->find();
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMoney_s[$i][$j1] = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['memberPrice'];
//                        $goods_integral = (int)$result1[$j1]["goodsCnt"] * $goodsInfo['integralReward'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMoney_s[$i][$j1] = $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//            }
//            if (is_array($totalMoney_s[$i])) {
//                $totalMoney[$i] = array_sum($totalMoney_s[$i]);//计算总金额
//            } else {
//                $totalMoney[$i] = $totalMoney_s[$i];//计算总金额
//            }
//
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                $shopInfo = M('shops')->where("shopId='" . $shopId . "' AND isInvoice=1")->field('shopId,isInvoice,isInvoicePoint')->find();
//                if ($shopInfo) {
//                    $totalMoney[$i] += $totalMoney[$i] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//
//            $totalMoney2[$i] = $totalMoney[$i];//纯粹的商品总金额
//            /*
//            //如果优惠券不为空
//            if(!empty($cuid)){
//                $mod_coupons_users = M('coupons_users');
//                $mod_coupons = M('coupons');
//                $couponWhere['id'] = $cuid;
//                $couponWhere['userId'] = $users['userId'];
//                if($mod_coupons_users->where($couponWhere)->find()){//判断优惠券是否是本人的
//                    $couponWhere = array();
//                    $couponWhere['couponStatus'] = 1;
//                    $couponWhere['id'] = $cuid;
//                    if($mod_coupons_users->where($couponWhere)->find()){//是否是未使用状态
//                        $couponUserInfo = $mod_coupons_users->where($couponWhere)->find();
//                        $couponWhere = array();
//                        $couponWhere['validStartTime'] = array('ELT', date('Y-m-d'));
//                        $couponWhere['validEndTime'] = array('EGT', date('Y-m-d'));
//                        $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                        if ($mod_coupons->where($couponWhere)->find()) {//是否过期
//                            $couponWhere = array();
//                            $couponWhere['couponId'] = (!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId'];
//                            if ($mod_coupons->where($couponWhere)->find()['spendMoney'] <= $totalMoney[$i]) {//是否满足使用条件
//                                $totalMoney[$i] = $totalMoney[$i] - (int)$mod_coupons->where($couponWhere)->find()['couponMoney'];
//                                $data['couponId'] = $couponId;
//                            } else {
//                                $apiRet['apiCode'] = '000104';
//                                $apiRet['apiInfo'] = '未达到最低消费金额';
//                                $apiRet['apiData'] = null;
//                                $apiRet['apiState'] = 'error';
//                                return $apiRet;
//                            }
//                        } else {
//                            $apiRet['apiCode'] = '000105';
//                            $apiRet['apiInfo'] = '优惠券已过期';
//                            $apiRet['apiData'] = null;
//                            $apiRet['apiState'] = 'error';
//                            return $apiRet;
//                        }
//                    }else{
//                        $apiRet['apiCode']='000106';
//                        $apiRet['apiInfo']='优惠券已使用';
//                        $apiRet['apiData'] = null;
//                        $apiRet['apiState']='error';
//                        return $apiRet;
//                    }
//                }else{
//                    $apiRet['apiCode']='000107';
//                    $apiRet['apiInfo']='优惠券未领取...';
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//                //检测商品是否满足优惠券权限
//                $msg = '';
//                $checkArr = array(
//                    'couponId' => ((!empty($couponUserInfo) && empty($couponUserInfo['ucouponId'])) ? $couponId : $couponUserInfo['ucouponId']),
//                    'good_id_arr' =>$goodsIdArr,
//                );
//                $checkRes = check_coupons_auth($checkArr,$msg);
//                if(!$checkRes){
//                    $apiRet['apiCode']='000108';
//                    $apiRet['apiInfo']='优惠券使用失败，'.$msg;
//                    $apiRet['apiData'] = null;
//                    $apiRet['apiState']='error';
//                    return $apiRet;
//                }
//            }*/
//
//            $data["totalMoney"] = (float)$totalMoney2[$i];//商品总金额
//            //$data["orderScore"] = floor($totalMoney[$i]+$goods_integral)*$rewardScoreMultiple;//所得积分
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$i]) + $goods_integral;//所得积分
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- end ---
//
//            $data["deliverMoney"] = $result[$i]["0"]["shopcm"]["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//
//            $data["deliverType"] = "1";//配送方式 门店配送
//
//            //设置订单配送方式
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//
//            //当前店铺是否是达达配送 且当前订单 不为自提
//            if ($result[$i]["0"]["shopcm"]["deliveryType"] == 2 and $isSelf !== 1) {
//
//
//                $funData['shopId'] = $shopId;
//                $funData['areaId2'] = $data["areaId2"];
//                $funData['orderNo'] = $data["orderNo"];
//                $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//                $funData['userName'] = $data["userName"];//收货人姓名
//                $funData['userAddress'] = $data["userAddress"];//收货人地址
//                $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//                $dadaresFun = self::dadaDeliver($funData);
//
//                if ($dadaresFun['status'] == -6) {
//                    M()->rollback();
//                    //获取城市出错
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，获取城市出错';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == -7) {
//                    M()->rollback();
//                    //不在达达覆盖城市内
////                    $apiRet['apiCode']='000068';
////                    $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                    $apiRet['apiData'] = null;
////                    $apiRet['apiState']='error';
//                    $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == 0) {
//                    //获取成功
//                    $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                    $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//
//                    // $data["deliverMoney"] =  $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//
//                }
//                $data["deliverType"] = "2";//配送方式 达达配送
//            }
//
//
//            //--------------------金额满减运费 start---------------------------
//
//
//            if ((float)$data["totalMoney"] >= (float)$result[$i]["0"]["shopcm"]["deliveryFreeMoney"]) {
//                $data["deliverMoney"] = 0;
//            }
//            //$data["totalMoney"]
//            //-----------------------金额满减运费end----------------------
//
//            //在使用运费之前处理好运费一些相关问题
//            if ($isSelf == 1) {//如果为自提订单 将运费重置为0
//                $data["deliverMoney"] = 0;
//            }
//
//
//            $data["needPay"] = $totalMoney[$i] + $data["deliverMoney"];//需缴费用 加运费-----
//            $data["realTotalMoney"] = (float)$totalMoney[$i] + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//
//
//            //使用积分
//            /*if($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1){
//                //获取比例
//                $scoreScoreCashRatio = explode(':',$GLOBALS['CONFIG']['scoreCashRatio']);
//                //最多可抵用的钱
//                $realTotalMoney = (float)$data["realTotalMoney"]-0.01;
//                //根据比例计算 积分最多可抵用的钱  总积分/比例积分*比例金额
//                $zdSMoney = (int)$users['userScore'] / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//                //$realTotalMoneyAzdSMoney = (float)$realTotalMoney - (float)$zdSMoney;
//                //计算做多可抵用多少钱的结果
//                if((float)$realTotalMoney >= (float)$zdSMoney){
//                    $zuiduoMoney = $zdSMoney;
//                }else{
//                    $zuiduoMoney = $realTotalMoney;
//                }
//                //计算反推出 相应的可抵用的积分
//                $zuiduoScore = $zuiduoMoney / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//
//                //return array('a'=>$zuiduoMoney,'b'=>$scoreScoreCashRatio);
//                //return array('抵扣最终的钱'=>$zuiduoMoney,'所参与抵扣的积分'=>$zuiduoScore);
//
//                $data["needPay"] = $totalMoney[$i]+$data["deliverMoney"]-$zuiduoMoney;//需缴费用 加运费 减去抵用的钱
//                $data["realTotalMoney"] = (float)$totalMoney[$i]+(float)$data["deliverMoney"]-$zuiduoMoney;//实际订单总金额 加运费 减去地用的钱
//
//
//                $data["useScore"] = $zuiduoScore;//本次交易使用的积分数
//                $data["scoreMoney"] = $zuiduoMoney;//积分兑换的钱 完成交易 确认收货在给积分
//            }*/
//
//            $shop_info = $sm->getShopInfo($data["shopId"]);
//            $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//            $appRealTotalMoney[$i] = $data["realTotalMoney"];//获取订单金额
//
//            $data['orderStatus'] = 0; //待受理
//            $data['isPay'] = 0; //已支付
//
//            //写入订单
//            $orderId[$i] = $orders->add($data);
//            if ($isSelf) {//如果为自提订单 生成提货码
//                //生成提货码
//                $mod_user_self_goods = M('user_self_goods');
//                $mod_user_self_goods_add['orderId'] = $orderId[$i];
//                $mod_user_self_goods_add['source'] = $orderId[$i] . $users['userId'] . $shopId;
//                $mod_user_self_goods_add['userId'] = $users['userId'];
//                $mod_user_self_goods_add['shopId'] = $shopId;
//                $mod_user_self_goods->add($mod_user_self_goods_add);
//            }
//
//            //建立订单记录
////            $data_order["orderId"] = $orderId[$i];
////            $data_order["logContent"] = "小程序下单成功，等待支付";
////            $data_order["logUserId"] = $users['userId'];
////            $data_order["logType"] = 0;
////            $data_order["logTime"] = date('Y-m-d H:i:s');
////
////            $mlogo->add($data_order);
//            $content = '小程序下单成功，等待支付';
//            $logParams = [
//                'orderId' => $orderId[$i],
//                'logContent' => $content,
//                'logUserId' => $users['userId'],
//                'logUserName' => '用户',
//                'orderStatus' => -2,
//                'payStatus' => 0,
//                'logType' => 0,
//                'logTime' => date('Y-m-d H:i:s'),
//            ];
//            M('log_orders')->add($logParams);
//
//
//            //更新优惠券状态为已使用
//            /*if(!empty($couponId)){
//                $mod_coupons_users_data['orderNo'] = $orderNo;
//                $mod_coupons_users_data['couponStatus'] = 0;
//                $mod_coupons_users->where("couponId = " . $couponId)->save($mod_coupons_users_data);
//            }*/
//
//            //使用积分
//            /*if($getuseScore > 0 and $GLOBALS['CONFIG']['isOpenScorePay'] == 1){
//                //减去用户当前所持有积分
//                M('users')->where("userId = {$users['userId']}")->setDec('userScore',$zuiduoScore);
//                //加上用户历史消费积分
//                M('users')->where("userId = {$users['userId']}")->setInc('userTotalScore',$zuiduoScore);
//                //写入积分消费记录
//                $user_add_pay['dataSrc'] = 1;//来源订单
//                $user_add_pay['userId'] = $users['userId'];//用户id
//                $user_add_pay['score'] = $zuiduoScore;//积分
//                $user_add_pay['dataRemarks'] = "抵用现金";
//                $user_add_pay['scoreType'] = 2;
//                $user_add_pay['createTime'] = date("Y-m-d H:i:s");
//                M("user_score")->add($user_add_pay);
//            }*/
//
//
//            //将订单商品写入order_goods
//            for ($i2 = 0; $i2 < count($result[$i]); $i2++) {//循环商品数据
//                $data_order_goods["orderId"] = $orderId[$i];
//                $data_order_goods["goodsId"] = $result[$i][$i2]["goodsId"];
//                $data_order_goods["goodsNums"] = $result[$i][$i2]["goodsCnt"];
//                $data_order_goods["goodsPrice"] = $result[$i][$i2]["shopPrice"];
//                //$data_order_goods["goodsAttrId"] = 0;原来
//                $data_order_goods["goodsAttrId"] = $result[$i][$i2]["goodsAttrId"];
//                $data_order_goods["remarks"] = $result[$i][$i2]["remarks"];
//                if (is_null($data_order_goods["remarks"])) {
//                    $data_order_goods["remarks"] = '';
//                }
//                //2019-6-14 start
//                $getGoodsAttrPrice = getGoodsAttrPrice($users['userId'], $result[$i][$i2]["goodsAttrId"], $result[$i][$i2]["goodsId"], $result[$i][$i2]["shopPrice"], $result[$i][$i2]["goodsCnt"], $result[$i][$i2]["shopId"]);
//                if ($result[$i][$i2]["skuId"] > 0) {
//                    $getGoodsAttrPrice = getGoodsSkuPrice($users['userId'], $result[$i][$i2]["skuId"], $result[$i][$i2]["goodsId"], $result[$i][$i2]["shopPrice"], $result[$i][$i2]["goodsCnt"], $result[$i][$i2]["shopId"]);
//                }
//                $data_order_goods["skuId"] = $result[$i][$i2]["skuId"];
//                $data_order_goods["goodsAttrName"] = $getGoodsAttrPrice['goodsAttrName'];
//                $data_order_goods["goodsPrice"] = $getGoodsAttrPrice["goodsPrice"];
//                if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $goods->where(array('goodsId' => $result[$i][$i2]["goodsId"]))->find();
//                    $systemSkuSpec = M('sku_goods_system')->where(['skuId' => $result[$i][$i2]["skuId"]])->find();
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $data_order_goods["goodsPrice"] = $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $data_order_goods["goodsPrice"] = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//                if (!empty($result[$i][$i2]["goodsAttrId"])) {
//                    /**
//                     * 2019-06-15
//                     * 减少属性的库存
//                     * */
//                    $goodsAttrId = $result[$i][$i2]["goodsAttrId"];
//                    $goodsAttrList = M('goods_attributes')->where("id IN($goodsAttrId)")->select();
//                    foreach ($goodsAttrList as $lv) {
//                        if ($lv['goodsId'] == $result[$i][$i2]['goodsId']) {
////                            M('goods_attributes')->where("id='".$lv['id']."'")->setDec('attrStock',$result[$i][$i2]['goodsCnt']);
//                            $updateGoodsAttrStockResult = updateGoodsAttrStockByRedis($lv['id'], $result[$i][$i2]['goodsCnt']);
//                            if ($updateGoodsAttrStockResult['code'] == -1) {
//                                M()->rollback();
//                                return $updateGoodsAttrStockResult;
//                            }
//                        }
//                    }
//                }
//                //2019-6-14 end
//                $data_order_goods["goodsName"] = $result[$i][$i2]["goodsName"];
//                $data_order_goods["goodsThums"] = $result[$i][$i2]["goodsThums"];
//
//                $order_goods->add($data_order_goods);
//
//                //减去对应商品数量
//                $where5["goodsId"] = $result[$i][$i2]['goodsId'];
////                $goods->where($where5)->setDec('goodsStock',$result[$i][$i2]['goodsCnt']);
//                $updateGoodsStockResult = updateGoodsStockByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                if ($updateGoodsStockResult['code'] == -1) {
//                    M()->rollback();
//
//                    return $updateGoodsStockResult;
//                }
//
//                //限制商品下单次数
//                limitGoodsOrderNum($result[$i][$i2]["goodsId"], $users['userId']);
//
//                //更改商品sku的库存
//                if ($result[$i][$i2]['skuId'] > 0) {
//                    $updateGoodsSkuStockResult = updateGoodsSkuStockByRedis($result[$i][$i2]['skuId'], $result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                    if ($updateGoodsSkuStockResult['code'] == -1) {
//                        M()->rollback();
//                        return $updateGoodsSkuStockResult;
//                    }
//                }
//
//                //更新进销存系统商品的库存
//                //updateJXCGoodsStock($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt'], 1);
//
//                /**
//                 * 2019-06-15 end
//                 * */
//                if ($result[$i][$i2]['isShopSecKill'] == 1) {
////                    $goods->where($where5)->setDec('shopSecKillNUM',$result[$i][$i2]['goodsCnt']); //减去对应商品的秒杀量
//                    $updateGoodsShopSecKillNUMResult = updateGoodsShopSecKillNUMByRedis($result[$i][$i2]['goodsId'], $result[$i][$i2]['goodsCnt']);
//                    if ($updateGoodsShopSecKillNUMResult['code'] == -1) {
//                        M()->rollback();
//                        return $updateGoodsShopSecKillNUMResult;
//                    }
//                }
//
//                //清空对应的购物车商品
//                $where6["userId"] = $users['userId'];
//                $where6["goodsId"] = $result[$i][$i2]['goodsId'];
//                $where6["skuId"] = $result[$i][$i2]['skuId'];
//                $cart->where($where6)->delete();
//
//                //写入秒杀记录表
//                if ($result[$i][$i2]["isShopSecKill"] == 1) {
//                    for ($kii_i = 0; $kii_i < $data_order_goods["goodsNums"]; $kii_i++) {
//                        //一件商品一条数据
//                        $killData['goodsId'] = $result[$i][$i2]["goodsId"];
//                        $killData['userId'] = $users['userId'];
//                        $killData['endTime'] = $result[$i][$i2]["ShopGoodSecKillEndTime"];
//                        $killData['orderId'] = $orderId[$i];
//                        $killData['state'] = 1;
//                        $killData['addtime'] = date('Y-m-d H:i:s', time());
//                        $killTab->add($killData);
//                    }
//                }
//
//            }
//            //建立订单提醒
//
//
//            $data_order_reminds["orderId"] = $orderId[$i];
//            $data_order_reminds["shopId"] = $shopId;//店铺id
//            $data_order_reminds["userId"] = $users['userId'];
//            //$data["userType"] = "0";
//            //$data["remindType"] = "0";
//            $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//
//
//            $order_reminds_statusCode = $order_reminds->add($data_order_reminds);
//
//
//            // 获取生成的所有订单号 返回给小程序支付
//            $wxorderNo[$i] = $orderNo;
//        }
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode(array_sum($appRealTotalMoney));//多个订单总金额 单位元
//            //$statusCode["orderNo"] = base64_encode(json_encode($wxorderNo));//订单号
//            $statusCode["orderNo"] = strencode(implode("A", $wxorderNo));//订单号  多个订单号用 A隔开
//
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5(implode("A", $wxorderNo));
//            $wst_order_merge_data['value'] = implode("A", $wxorderNo);
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//            M()->commit();
//            unset($apiRet);
////            $apiRet['apiCode']='000067';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//
//        } else {
//            M()->rollback();
//            unset($apiRet);
////            $apiRet['apiCode']='000068';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//    }

    //获取达达运费
    static function dadaDeliver($funData)
    {
        $shops_mod = M('shops');
        $order_areas_mod = M('areas');
        $funData['shopId'] = $funData['shopId'];
        $funData['areaId2'] = $funData['areaId2'];
        $funData['orderNo'] = $funData['orderNo'];
        $funData['totalMoney'] = $funData['totalMoney'];//订单金额 不加运费
        $funData['userName'] = $funData['userName'];//收货人姓名
        $funData['userAddress'] = $funData['userAddress'];//收货人地址
        $funData['userPhone'] = $funData['userPhone'];//收货人手机

        //-----------------------调用达达 计算运费------------------------
        //判断当前订单是否在达达覆盖范围城市内  如果不是就依据店铺运费
        $shops_data_res = $shops_mod->where("shopId = '{$funData['shopId']}'")->find();

        $dadam = D("V3/dada");
        $dadamod = $dadam->cityCodeList(null, $shops_data_res['dadaShopId']);//线上环境
        //$dadamod = $dadam->cityCodeList(null,73753);//测试环境 测试完成 此段删除 开启上行代码-------------------------------------

        if (!empty($dadamod['niaocmsstatic'])) {
            $rd = array('status' => -6, 'data' => $dadamod, 'info' => '获取城市出错#' . $dadamod['info']);//获取城市出错
            return $rd;
        }

        $cityNameisWx = str_replace(array('省', '市'), '', $order_areas_mod->where("areaId = '{$funData['areaId2']}'")->field('areaName')->find()['areaName']);
        //判断当前是否在达达覆盖范围内
        $isDadafrom = false;;
        for ($i = 0; $i <= count($dadamod) - 1; $i++) {

            if ($cityNameisWx == $dadamod[$i]['cityName']) {//如果在配送范围
                /* 				$myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
				$txt ='在达达覆盖范围';
				fwrite($myfile, $txt.'\n');
				fclose($myfile); */

                $isDadafrom = true;
                $dadaCityCode = $dadamod[$i]['cityCode'];
                break;
            }
        }

        if (!$isDadafrom) {
            $rd = array('status' => -7, 'data' => null, 'info' => '不在达达覆盖城市内！');
            return $rd;
        }
        //进行订单预发布

        //备参

        $DaDaData = array(
            'shop_no' => $shops_data_res['dadaOriginShopId'],//	门店编号，门店创建后可在门店列表和单页查看
            //'shop_no'=> '11047059',//测试环境 测试完成 此段删除 开启上行代码-------------------------------------
            'origin_id' => $funData['orderNo'],//第三方订单ID
            'city_code' => $dadaCityCode,//	订单所在城市的code（查看各城市对应的code值）
            'cargo_price' => $funData['totalMoney'],//	订单金额 不加运费
            'is_prepay' => 0,//	是否需要垫付 1:是 0:否 (垫付订单金额，非运费)
            'receiver_name' => $funData["userName"],//收货人姓名
            'receiver_address' => $funData["userAddress"],//	收货人地址
            'receiver_phone' => $funData["userPhone"],//	收货人手机号
            'cargo_weight' => 1,
            'origin_mark_no' => $orderInfo["orderNo"],//订单来源编号，最大长度为30，该字段可以显示在骑士APP订单详情页面

            //'callback'=> 'https://www.niaocms.cn/dada.php' //	回调URL（查看回调说明）
            // 'callback' => WSTDomain() . '/wstapi/logistics/notify_dada.php' //	回调URL（查看回调说明）
            'callback' => WSTDomain() . '/Home/dada/dadaOrderCall' //	回调URL（查看回调说明）
        );

        $dada_res_data = $dadam->queryDeliverFee($DaDaData, $shops_data_res['dadaShopId']);
        //$dada_res_data = $dadam->queryDeliverFee($DaDaData,73753);///测试环境 测试完成 此段删除 开启上行代码-------------------------------------

        /* 	$myfile = fopen("dadares.txt", "a+") or die("Unable to open file!");
			$txt =json_encode($dada_res_data);
			fwrite($myfile, $txt.'\n');
			fclose($myfile); */

        $data['distance'] = $dada_res_data['distance'];//配送距离(单位：米)
        $data['deliveryNo'] = $dada_res_data['deliveryNo'];//来自达达返回的平台订单号
        $data["deliverMoney"] = $dada_res_data['fee'];//	实际运费(单位：元)，运费减去优惠券费用
        $rd = array('status' => 0, 'data' => $data, 'info' => 'ok');
        return $rd;
    }

    /**
     * PS:在原有基础前置仓模式下兼容多商户模式,前置仓已经在用该接口了,所以就不重构了
     * 提交订单-微信付款
     * 共用参数
     * @param string memberToken
     * @param int fromType PS:应用模式,默认为前置仓(1:前置仓|2:多商户)
     * @param int orderSrc 订单来源(3:APP 4:小程序)
     * @param int isSelf PS:是否自提(1:自提)
     * @param int useScore PS:是否使用积分(1:使用积分)
     * @param int payFrom PS:支付方式(1:支付宝|2:微信|3:余额|4:货到付款)
     * @param int addressId PS:地址id
     * @param datetime requireTime PS:送达时间
     * @param int delivery_time_id PS:配送时间id
     * @param int buyNowGoodsId 立即购买-商品id 注：仅用于立即购买
     * @param int buyNowSkudId 立即购买-skuId 注：仅用于立即购买
     * 前置仓参数
     * @param int cuid PS:用户领取的优惠券id
     * @param string orderRemarks PS:订单备注
     * @param int invoiceClient PS:发票id
     * 多商户参数
     * @param jsonString shopParam
     * invoiceClient:发票id|cuid:用户领取的优惠券id|orderRemarks:订单备注
     * */
    public function wxSubmitOrder($param)
    {
        #########PS:前置仓 多商户 京东到家
        $order_module = new OrdersModule();
        $cart_module = new CartModule();
        $goods_service_module = new GoodsServiceModule();
        $goods_module = new GoodsModule();
        $users_module = new UsersModule();
        $shop_module = new ShopsModule();
        $notify_module = new NotifyModule();
        $coupon_module = new CouponsModule();
        $log_orders_module = new LogOrderModule();
        $configs = $GLOBALS['CONFIG'];
        $score_cash_ratio = explode(':', $configs['scoreCashRatio']);//积分金额比例
        $score_cash_ratio0 = 0;
        $score_cash_ratio1 = 0;
        if (is_array($score_cash_ratio) && count($score_cash_ratio) == 2) {
            $score_cash_ratio0 = (float)$score_cash_ratio[0];
            $score_cash_ratio1 = (float)$score_cash_ratio[1];
        }
        $open_score_pay = $configs['isOpenScorePay'];//是否开启了积分支付(1:开启)
        //验证货到付款-start
        $verification_cash_on_delivery_res = $order_module->checkCashOnDeliveryParams($param);
        if ($verification_cash_on_delivery_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $verification_cash_on_delivery_res['msg']);
        }
        //验证货到付款-end
        $address_id = (int)$param['addressId'];
        $users_id = (int)$param['userId'];
        $cuid = (int)$param['cuid'];
        $is_self = (int)$param['isSelf'];
        $use_score = (int)$param['useScore'];
        $order_remarks = (string)$param['orderRemarks'];
        $require_time = (string)$param['requireTime'];
        $delivery_time_id = (int)$param['delivery_time_id'];//配送时间段id
        if (!empty($delivery_time_id) && $delivery_time_id != -1) {
            //传时间id,现在用的是前端传的requireTime字段后期可不要前端传这个字段
            $delivery_module = new DeliveryTimeModule();
            $delivery_time_detail = $delivery_module->getDeliveryTimeInfo($delivery_time_id, 2);
            $require_time = date('Y-m-d') . ' ' . $delivery_time_detail['timeEnd'] . ':00';
            if (!empty($delivery_time_detail)) {
                $delivery_type_detail = $delivery_module->getDeliveryTimeTypeInfo($delivery_time_detail['deliveryTimeTypeId'], 2);
                $number = (int)$delivery_type_detail['number'];
                if ($number > 0) {
                    $require_time = strtotime($require_time) + ($number * 3600 * 24);
                    $require_time = date('Y-m-d H:i:s', $require_time);
                }
            }
        }
        $pay_from = (int)$param['payFrom'];
        $from_type = (int)$param['fromType'];//应用模式(1:前置仓|2:多商户)
        $shop_param = (array)json_decode($param['shopParam'], true);
        $shop_id = (int)$param['shopId'];
        $wu_coupon_id = (int)$param['wuCouponId'];//运费劵ID
        $invoice_client = (int)$param['invoiceClient'];//发票id
        if ($from_type == 2) {
            //多商户模式
            $shop_id = array_column($shop_param, 'shopId');
        }

        $buyNowGoodsId = (int)$param['buyNowGoodsId'];//立即购买-商品id 注：仅用于立即购买
        $buyNowSkuId = (int)$param['buyNowSkuId'];//立即购买-skuId 注：仅用于立即购买
        $buyNowGoodsCnt = (float)$param['buyNowGoodsCnt'];//立即购买-数量 注：仅用于立即购买
        if (!empty($buyNowGoodsId)) {//立即购买 目前只针对前置仓
            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkuId, $buyNowGoodsCnt);
            if (!empty($cart_goods['goods_list'])) {
                if ($cart_goods['goods_list'][0]['shopId'] != $shop_id) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提交的商品与当前店铺不匹配！');
                }
            } else {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品库存不足或已被下架！');
            }
        } else {
            $cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id), 1, 1);
            if (isset($cart_goods['code']) && $cart_goods['code'] == ExceptionCodeEnum::FAIL) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', $cart_goods['msg']);
            }
        }
//        $cart_goods = $cart_module->getCartGoodsChecked($users_id, $shop_id);
        $cart_goods['goods_list'] = getCartGoodsSku($cart_goods['goods_list']);
        $goods_list = $cart_goods['goods_list'];
        if (!empty($shop_param)) {
            //兼容京东到家模式,某一家店铺结算
            $shop_id_arr = array_column($shop_param, 'shopId');
            foreach ($goods_list as $key => $value) {
                if (!in_array($value['shopId'], $shop_id_arr)) {
                    unset($goods_list[$key]);
                }
            }
            $goods_list = array_values($goods_list);
        }
        $all_shop_list = $order_module->getAllOrderShopList($goods_list);//获取当前要支付的商品的所有店铺
        if (empty($all_shop_list)) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提交的商品信息有误');
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '处理中，请勿频繁支付');//防止前端在微信回调回来前频繁点击提交订单
        }
        $area_id_arr = [];
        $all_shop_list_map = [];
        foreach ($all_shop_list as $all_shop_list_row) {
            $all_shop_list_map[$all_shop_list_row['shopId']] = $all_shop_list_row;
            $area_id_arr[] = $all_shop_list_row['areaId1'];
            $area_id_arr[] = $all_shop_list_row['areaId2'];
            $area_id_arr[] = $all_shop_list_row['areaId3'];
        }
        $all_shop_goods_money = $order_module->getAllShopGoodsMoney($users_id, $goods_list);//获取所有商品的购买金额总计
        //$init_price = $goods_module->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_attr_id, $goods_cnt);//替换商品价格
        M()->startTrans();
        $users_detail = $users_module->getUsersDetailById($users_id, '*', 2);
        if (empty($users_detail)) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $customerRankId = 0;
        $customerRankDetail = (new RankModule())->getUserRankDetialByUserId($users_id);
        if (!empty($customerRankDetail)) {
            $customerRankId = $customerRankDetail['rankId'];
        }
        //已欠款额度
        if ((float)$users_detail['quota_arrears'] > 0) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '账户存在欠款，请先处理已欠款额度');
        }
        $shop_id = $goods_list[0]['shopId'];//默认使用前置仓模式
        $address_detail = $users_module->getUserAddressDetail($users_id, $address_id);
        $lat = 0;
        $lng = 0;
        if ($is_self != 1) {
            if (empty($address_detail)) {
                M()->rollback();
                return returnData(false, -1, 'error', '请选择正确的收货地址');
            }
            $area_id_arr[] = $address_detail['areaId1'];
            $area_id_arr[] = $address_detail['areaId2'];
            $area_id_arr[] = $address_detail['areaId3'];
        }
        $area_module = new AreasModule();
        $area_id_arr = array_unique($area_id_arr);
        $area_list = [];
        $area_list_map = [];
        if (count($area_id_arr) > 0) {
            $area_list = $area_module->getAreaListByIdArr($area_id_arr);
        }
        foreach ($area_list as $area_list_row) {
            $area_list_map[$area_list_row['areaId']] = $area_list_row;
        }
        if ($from_type == 1) {
            //前置仓
            if ($is_self != 1) {//非自提校验收货地址
//                $verification_address_res = $order_module->verificationShopDistribution($users_id, $address_id, $shop_id);
                $verification_address_res = $order_module->verificationShopDistributionNew($users_detail, $address_detail, $all_shop_list_map[$shop_id]);
                if (!$verification_address_res) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "收货地址不在店铺配送范围");
                }
            }
            if (!empty($cuid)) {//校验用户优惠券是否可用
                $verification_user_coupon_res = $coupon_module->verificationUserCoupon($cuid);
                if (!$verification_user_coupon_res) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "优惠券不可用");
                }
            }
//            $verification_delivery_start_monry_res = $cart_module->verificationDeliveryStartMoney($users_id, $shop_id);
            $verification_delivery_start_monry_res = $cart_module->verificationDeliveryStartMoneyNew($users_detail, $shop_id, array($goods_list));
            if ($verification_delivery_start_monry_res['code'] != ExceptionCodeEnum::SUCCESS) {//校验订单金额是否达到门店配送起步价
                M()->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单金额未达到店铺配送起步价");
            }
        } else {
            //多商户
            foreach ($shop_param as $shop_val) {
                $current_shop_id = $shop_val['shopId'];
                $current_cuid = (int)$shop_val['cuid'];
                $field = 'shopId,shopName';
                $current_shop_detail = $shop_module->getShopsInfoById($current_shop_id, $field, 2);
                $current_shop_name = $current_shop_detail['shopName'];
                if ($is_self != 1) {
                    $verification_address_res = $order_module->verificationShopDistribution($users_id, $address_id, $current_shop_id);
                    if (!$verification_address_res) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货地址不在店铺{$current_shop_name}配送范围");
                    }
                }
                if (!empty($current_cuid)) {
                    $verification_user_coupon_res = $coupon_module->verificationUserCoupon($current_cuid);
                    if (!$verification_user_coupon_res) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "店铺{$current_shop_name}优惠券不可用");
                    }
                }
                $verification_delivery_start_monry_res = $cart_module->verificationDeliveryStartMoney($users_id, $current_shop_id);
                if ($verification_delivery_start_monry_res['code'] != ExceptionCodeEnum::SUCCESS) {//校验订单金额是否达到门店配送起步价
                    M()->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单金额未达到店铺{$current_shop_name}配送起步价");
                }
            }
        }
        if (!empty($wu_coupon_id)) {
            $verification_delivery_coupon_res = $coupon_module->verificationUserCoupon($wu_coupon_id);
            if (!$verification_delivery_coupon_res) {
                M()->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "运费券不可用");
            }
        }
        if (!empty($address_detail)) {
            $lat = $address_detail['lat'];
            $lng = $address_detail['lng'];
//            $arr = bd_decrypt($lng, $lat);
//            $lng = $arr['gg_lon'];
//            $lat = $arr['gg_lat'];
        }
        //统一运费参数值处理-start
        if ($configs['deliveryMoney'] <= 0) {
            $configs['deliveryMoney'] = 0;
        }
        if ($configs['deliveryFreeMoney'] <= 0) {
            $configs['deliveryFreeMoney'] = 0;
        }
        //统一运费参数值处理-end
        //校验支付商品的有效性-start
        $verification_goods_res = $order_module->verificationGoodsStatus($goods_list, $users_id, $address_id, $is_self);
        if ($verification_goods_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return $verification_goods_res;
        }
        //校验支付商品的有效性-end
        $result = $cart_module->handleCartGoodsSku($goods_list);
//        if (count($result) > 0) {
//            foreach ($result as $key => $val) {
//                $result[$key] = rankGoodsPrice($val);
//            }
//        }
        //拆单-常规|非常规 -start
        $all_goods = $result;
        $shop_orders = array();
        $shop_must_self = array();//是否必须自提
        foreach ($all_goods as $all_goods_key => $all_goods_val) {
            $current_shop_goods = $all_goods_val;
            if ($from_type == 2) {
                //多商户模式
                $current_goods_shop_id = $all_goods_val[0]['shopId'];
                foreach ($shop_param as $shop_val) {
                    if ($current_goods_shop_id == $shop_val['shopId'] && !empty($shop_val['cuid'])) {
                        $cuid = (int)$shop_val['cuid'];
                    }
                }
            }
            $current_split_order = $goods_module->getGoodsSplitOrder($current_shop_goods, $cuid);
            $shop_must_self[] = $current_split_order['must_self'];
            $shop_orders[$all_goods_key]['max_coupon_order_key'] = 0;//优先使用优惠券的订单索引
            if (!empty($current_split_order['use_coupon_detail'])) {
                $shop_orders[$all_goods_key]['max_coupon_order_key'] = $current_split_order['use_coupon_detail']['max_coupon_order_key'];
            }
            $shop_orders[$all_goods_key]['order_list'] = $current_split_order['order_list'];
        }
        $shop_must_self_num = array_sum($shop_must_self);
        if ($shop_must_self_num == count($all_shop_list) && $is_self != 1) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "当前订单仅限自提");
        }
        //鸡儿,为什么常规非常规拆完单了不处理运费的问题###################################################################
        //拆单-常规|非常规 -end
        $is_used_delivery_coupon = 0;//使用运费券免运费(0:未使用 1:已使用)
        $all_order_num = 0;//订单数量总计,用于处理运费
        foreach ($shop_orders as $shop_order_item) {
            $all_order_num += count($shop_order_item['order_list']);
        }
        $order_num_key = 0;//订单标识,注:用于标识处理到第几笔订单了,第一笔订单加上运费就可以了,其他的不需要再加运费了
        $setDeliveryMoney_tag = 0;//补运费,用于第三方支付重新支付填补运费
        foreach ($shop_orders as $shop_order_key => $shop_order_val) {
            $max_coupon_order_key = $shop_order_val['max_coupon_order_key'];//指定使用优惠券的订单索引 PS:在该订单上优惠券优惠金额最高
            $max_delivery_coupon_order_key = $shop_order_val['max_coupon_order_key'];//指定使用运费券的订单索引 PS:在该订单上运费券优惠金额最高
            $current_shop_order = $shop_order_val['order_list'];//当前门店订单列表
            foreach ($current_shop_order as $current_order_key => $current_order_val) {
                $order_num_key++;
                $current_order_data = $current_order_val;//当前订单详情
                $order_tag = $current_order_data['order_tag'];//订单标识(1:常规订单 2:非常规订单 3:限时仅自提 4:限量仅自提)
                $can_use_coupon = $current_order_data['can_use_coupon'];//是否允许使用优惠券(0:不允许 1:允许) PS:主要针对限时限量商品是否开启不享受优惠券
                $current_goods = $current_order_data['goods_list'];//订单商品数据
                $users_detail = $users_module->getUsersDetailById($users_id, '*', 2);
                $shop_id = $current_goods[0]['shopId'];
//                $shop_detail = $shop_module->getShopsInfoById($shop_id, '*', 2);
                $shop_detail = $all_shop_list_map[$shop_id];
                $shop_param_detail = array();
                if ($from_type == 2) {
                    foreach ($shop_param as $shop_val) {
                        if ($shop_detail['shopId'] == $shop_val['shopId']) {
                            $shop_param_detail = (array)$shop_val;
                        }
                    }
                }
                $auto_id = $order_module->getOrderAutoId();
                $bill_no = $auto_id . "" . (fmod($auto_id, 7));//以前就是这样的方式
                $order_data = array();
                $order_data['customerRankId'] = $customerRankId;
                $order_data['setDeliveryMoney'] = 0;
                $order_data['orderNo'] = $bill_no;
                $order_data['shopId'] = $shop_id;
                $order_data['orderStatus'] = -2;
                $order_data['isReceivables'] = 0;//是否收款(0:待收款|1:预收款|2:已收款(全款))
                if ($pay_from == 3) {
                    $order_data['isReceivables'] = 2;
                } elseif ($pay_from == 4) {
                    $order_data['orderStatus'] = 0;//如果是货到付款,订单默认为待受理状态
                }
                $order_data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号 PS:原来就是这种写法
                if ($is_self == 1) {
                    //自提订单不需要用户的收货地址而是取货的店铺地址
                    $address_detail = array();
                    $address_detail['areaId1'] = $shop_detail['areaId1'];
                    $address_detail['areaId2'] = $shop_detail['areaId2'];
                    $address_detail['areaId3'] = $shop_detail['areaId3'];
                    $address_detail['setaddress'] = '';
                    $address_detail['postCode'] = '';
                    $address_detail['communityId'] = 0;
                    $address_detail['address'] = $shop_detail['shopAddress'];
                    $address_detail['lat'] = $shop_detail['latitude'];
                    $address_detail['lng'] = $shop_detail['longitude'];
                    $address_detail['userName'] = $users_detail['userName'];
                    $address_detail['userPhone'] = $users_detail['userPhone'];
                }

//                $address_detail['areaId1Name'] = $this->getAreaName($address_detail['areaId1']);
//                $address_detail['areaId2Name'] = $this->getAreaName($address_detail['areaId2']);
//                $address_detail['areaId3Name'] = $this->getAreaName($address_detail['areaId3']);
                $address_detail['areaId1Name'] = $area_list_map[$address_detail['areaId1']]['areaName'];
                $address_detail['areaId2Name'] = $area_list_map[$address_detail['areaId2']]['areaName'];
                $address_detail['areaId3Name'] = $area_list_map[$address_detail['areaId3']]['areaName'];
                $order_data["areaId1"] = $address_detail["areaId1"];
                $order_data["areaId2"] = $address_detail["areaId2"];
                $order_data["areaId3"] = $address_detail["areaId3"];
                $order_data["payType"] = "1";//支付方式
                if ($pay_from == 4) {
                    $order_data['payType'] = 0;
                }
                $order_data["isSelf"] = $is_self;//是否自提
                if (in_array($order_tag, array(3, 4))) {
                    $order_data['isSelf'] = 1;//限时仅自提,限量仅自提
                }
                $order_data["isPay"] = 0;//是否支付
                $order_data["userId"] = $users_id;//用户Id
                $order_data["userName"] = $address_detail["userName"];//收货人名称
                $order_data["communityId"] = $address_detail["communityId"];//收货地址所属社区id
                if ($is_self == 1) {
                    $order_data['userAddress'] = $address_detail['address'];
                } else {
                    $order_data['userAddress'] = '';
                    if (handleCity($address_detail['areaId1Name'])) {
                        $order_data['userAddress'] .= $address_detail['areaId1Name'] . ' ';
                    }
                    $order_data['userAddress'] .= $address_detail['areaId2Name'] . ' ';
                    $order_data['userAddress'] .= $address_detail['areaId3Name'] . ' ';
                    if ($order_data['communityId'] > 0) {
                        $order_data["userAddress"] .= $this->getCommunity($order_data['communityId']) . $address_detail['setaddress'] . $address_detail["address"];
                    } else {
                        $order_data["userAddress"] .= $address_detail['setaddress'] . $address_detail["address"];
                    }
                }
                $order_data["userPhone"] = $address_detail["userPhone"];//收件人手机
                $order_data["userPostCode"] = $address_detail["postCode"];//收件人邮编
                $order_data['invoiceClient'] = $invoice_client;//发票id
                $order_data['isInvoice'] = $order_data['invoiceClient'] > 0 ? 1 : 0;//是否需要发票
                $order_data["orderRemarks"] = $order_remarks;//订单备注
                $order_data["isAppraises"] = 0;//是否点评
                $order_data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
                $order_data["orderSrc"] = (int)$param['orderSrc'];//订单来源
                $order_data["orderFlag"] = 1;//订单有效标志
                $order_data["payFrom"] = $pay_from;//支付来源
                $order_data['requireTime'] = $require_time;
                //在前置仓的基础上兼容多商户
                if ($from_type == 2) {
                    $order_data['invoiceClient'] = (isset($shop_param_detail['invoiceClient']) && (int)$shop_param_detail['invoiceClient'] > 0) ? $shop_param_detail['invoiceClient'] : 0;
                    $order_data['isInvoice'] = (isset($shop_param_detail['invoiceClient']) && $shop_param_detail['invoiceClient']) > 0 ? 1 : 0;
                    $order_data['orderRemarks'] = isset($shop_param_detail['orderRemarks']) ? $shop_param_detail['orderRemarks'] : '';
                }
                $delayed_arr = array();//商品延时 单位分钟
                $order_total_money = array();//该订单商品金额总计 最纯粹的金额
                $can_use_score = 0;//该订单可以抵扣的积分
                $users_use_score = 0;//该订单用户使用的积分
                $exists_convention = 0;//是否存在非常规商品(0:不存在 1:存在)
                foreach ($current_goods as $curr_goods_key => $curr_goods_val) {
//                    $goods_id = (int)$curr_goods_val['goodsId'];
//                    $sku_id = (int)$curr_goods_val['skuId'];
//                    $goods_attr_id = (int)$curr_goods_val['goodsAttrId'];
                    $goods_cnt = (float)$curr_goods_val['goods_cnt'];
                    $goods_delayed = (int)$curr_goods_val['delayed'];
                    if ($goods_delayed > 0) {
                        $delayed_arr[] = $goods_delayed;
                    }
//                    $init_price = $goods_module->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_cnt, $curr_goods_val);//替换商品价格
                    $init_price = $curr_goods_val['shopPrice'];
                    $current_goods[$curr_goods_key]['init_price'] = formatAmount($init_price);
                    $goods_price_total = (float)bc_math($init_price, $goods_cnt, 'bcmul', 2);//该商品金额小计
                    $order_total_money[] = $goods_price_total;
                    if ($open_score_pay == 1) {
                        //用户使用积分
                        $discount_price = $goods_price_total;
                        //获取比例
                        $goods_score = (float)$discount_price / (float)$score_cash_ratio1 * (float)$score_cash_ratio0;
                        $integral_rate_score = (int)($goods_score * ($curr_goods_val['integralRate'] / 100));//该商品允许抵扣的积分
                        if ($use_score == 1) {
                            $users_use_score += (int)$integral_rate_score;
                        }
                        $can_use_score += $integral_rate_score;//用户最多可以抵扣的积分,后面还要计算
                    }
                    if ($curr_goods_val['isConvention'] == 1) {
                        $exists_convention = 1;
                    }
                    $current_goods[$curr_goods_key]['goods_price_total'] = $goods_price_total;
                    $current_goods[$curr_goods_key]['goodsCnt'] = $goods_cnt;
                }
                $order_total_money = array_sum($order_total_money);//后面就不要再操作该字段的金额了
                $math_total_money = (float)$order_total_money;//用于后面的金额计算
                //判断是否使用发票
                if ($order_data['isInvoice'] == 1) {
                    if (isset($shop_detail['isInvoicePoint']) && !empty($shop_detail['isInvoicePoint'])) {
                        $math_total_money += $math_total_money * ((float)$shop_detail['isInvoicePoint'] / 100);
                    }
                }
                //检验优惠券是否可用
                $cuid = (int)(isset($shop_param_detail['cuid']) ? $shop_param_detail['cuid'] : $cuid);
                $coupon_money = 0;//优惠券金额
                $coupon_id = 0;//优惠券id
                $coupon_use_max_money = 0;//该优惠券最多可抵扣金额
                if (!empty($cuid)) {
                    $init_user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
                    if ($init_user_coupon_detail['couponStatus'] != 1) {
                        $cuid = 0;//主要是针对拆单之后,优惠券已被其他订单使用,跳过后面的优惠券校验
                    }
                }
                if (!empty($cuid)) {
                    $verification_coupon = $coupon_module->verificationUserCoupon($cuid);
                    if (!$verification_coupon) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "优惠券不可用");
                    }
                }
                if (!empty($cuid) && $max_coupon_order_key == $current_order_key && $can_use_coupon == 1) {//将优惠券分配给最优订单(优惠券优惠金额最大化)
                    $split_order_data = $goods_module->getGoodsSplitOrder($current_goods, $cuid);
                    $use_coupon_detail = $split_order_data['use_coupon_detail'];
                    if (empty($use_coupon_detail)) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请选择可用的优惠券");
                    }
                    if ($use_coupon_detail['max_coupon_order_key'] != $max_coupon_order_key) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "优惠券使用失败");
                    }
//                    $match_current_order = $split_order_data['order_list'][$current_order_key];
                    $match_current_order = $split_order_data['order_list'][0];
                    $can_use_conpon_money = $match_current_order['can_use_conpon_money'];
                    if ($can_use_conpon_money < $use_coupon_detail['spendMoney']) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "未达到优惠券最低消费金额");
                    }
                    $coupon_id = $use_coupon_detail['couponId'];
                    $coupon_money = (float)$use_coupon_detail['couponMoney'];
                    $coupon_use_max_money = $use_coupon_detail['coupon_use_max_money'];
                }
                //检验运费券是否可用
                $delivery_coupon_id = 0;//运费券id
                $delivery_coupon_money = 0;//运费券金额
                $delivery_coupon_use_max_money = 0;//运费券最多抵扣金额
                if (!empty($delivery_coupon_id)) {
                    $init_delivery_coupon_detail = $coupon_module->getUserCouponDetail($wu_coupon_id);
                    if ($init_delivery_coupon_detail['couponStatus'] != 1) {
                        $wu_coupon_id = 0;//主要针对拆单之后,运费券已被使用,跳过后面的校验
                    }
                }
                if (!empty($init_delivery_coupon_detail) && $init_delivery_coupon_detail['couponStatus'] == 0) {
                    $is_used_delivery_coupon = 1;//已有分单使用过运费券
                }
                if (!empty($wu_coupon_id)) {
                    $verification_delivery_coupon = $coupon_module->verificationUserCoupon($wu_coupon_id);
                    if (!$verification_delivery_coupon) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "运费券不可用");
                    }
                }
                if (!empty($wu_coupon_id) && $max_delivery_coupon_order_key == $current_order_key && $can_use_coupon == 1) {
                    $split_order_data = $goods_module->getGoodsSplitOrder($current_goods, $wu_coupon_id);
                    $use_coupon_detail = $split_order_data['use_coupon_detail'];
                    if (empty($use_coupon_detail)) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "请选择可用的优惠券");
                    }
//                    $match_current_order = $split_order_data['order_list'][$current_order_key];
                    $match_current_order = $split_order_data['order_list'][0];
                    $can_use_conpon_money = $match_current_order['can_use_conpon_money'];
                    if ($can_use_conpon_money < $use_coupon_detail['spendMoney'] && $use_coupon_detail['spendMoney'] > 0) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', "未达到运费券最低消费金额");
                    }
                    $delivery_coupon_id = $use_coupon_detail['couponId'];
                    $delivery_coupon_money = $use_coupon_detail['couponMoney'];
                    $delivery_coupon_use_max_money = $use_coupon_detail['coupon_use_max_money'];
                }
                $order_data["totalMoney"] = $order_total_money;//商品总金额
                $order_data["orderScore"] = getOrderScoreByOrderScoreRate($order_total_money);//所得积分
                //设置订单配送方式-start
                $order_data['deliverType'] = 1;//配送方式 门店配送
                if ($shop_detail["deliveryType"] == 4 and $is_self !== 1) {
                    $order_data["deliverType"] = 4;//配送方式 快跑者配送
                }
//                if ($shop_detail["deliveryType"] == 6 and $is_self !== 1) {
//                    $order_data["deliverType"] = 6;//配送方式 自建司机
//                }
                if ($shop_detail["deliveryType"] == 6) {
                    $order_data["deliverType"] = 6;//配送方式 自建司机
                }
                //当前店铺是否是达达配送 且当前订单 不为自提
                if ($shop_detail["deliveryType"] == 2 and $is_self !== 1) {
                    $order_data["deliverType"] = 2;//配送方式 达达配送
                }
                //设置订单配送方式-end
                $order_data['deliverMoney'] = (float)$shop_detail['deliveryMoney'];
//                if ($configs['setDeliveryMoney'] != 2 && (float)$order_data["totalMoney"] >= (float)$shop_detail['deliveryFreeMoney']) {
//                    $order_data["deliverMoney"] = 0;
//                }
                if ((float)$all_shop_goods_money >= (float)$shop_detail['deliveryFreeMoney']) {
                    $order_data["deliverMoney"] = 0;
                }
                //统一运费
//                if ($configs['setDeliveryMoney'] == 2) {//废弃
//                    $order_data["deliverMoney"] = 0;
//                    $order_data['setDeliveryMoney'] = $configs['deliveryMoney'];
//                }
                //运费券
                if ($delivery_coupon_id > 0) {
//                    $order_data["deliverMoney"] = (float)bc_math($order_data['deliverMoney'], $delivery_coupon_money, 'bcsub', 2);
                    if ($delivery_coupon_use_max_money >= $order_total_money) {
                        $delivery_coupon_use_max_money = $order_data['deliverMoney'];
                    }
                    $order_data["deliverMoney"] = (float)bc_math($order_data['deliverMoney'], $delivery_coupon_use_max_money, 'bcsub', 2);
                    if ($order_data['deliverMoney'] < 0) {
                        $order_data['deliverMoney'] = 0;
                    }
                    $order_data["setDeliveryMoney"] = 0;
                    $order_data['delivery_coupon_id'] = $delivery_coupon_id;
                    $order_data['delivery_coupon_money'] = $delivery_coupon_money;//运费券面额
                    $order_data['delivery_coupon_use_money'] = $delivery_coupon_use_max_money;//运费券实际抵扣金额
                }
                //如果为自提订单 将运费重置为0
                if ($is_self == 1 || in_array($order_tag, array(3, 4))) {
                    $order_data["deliverMoney"] = 0;
                    $order_data['setDeliveryMoney'] = 0;
                }
                $real_total_money = $math_total_money;//实际金额
                $order_data["useScore"] = 0;//该单使用的积分
                $order_data["scoreMoney"] = 0;//该单积分抵扣的金额
                if ($users_detail['userScore'] > 0 and $use_score > 0 and $configs['isOpenScorePay'] == 1) {
                    $current_users_score = $users_detail['userScore'];//用户剩余积分
                    if ($current_users_score < $users_use_score) {
                        $order_data["useScore"] = $current_users_score;
                        $order_data["scoreMoney"] = (int)$order_data['useScore'] / (float)$score_cash_ratio0 * (float)$score_cash_ratio1;
                    } else {
                        $order_data["useScore"] = $users_use_score;
                        $order_data["scoreMoney"] = (int)$order_data['useScore'] / (float)$score_cash_ratio0 * (float)$score_cash_ratio1;
                    }
                }
                if ($order_data['scoreMoney'] > 0) {
                    $real_total_money = (float)bc_math($real_total_money, $order_data['scoreMoney'], 'bcsub', 2);//实际金额减去积分抵扣金额
                }
//                if ($configs['setDeliveryMoney'] != 2) {
//                    $real_total_money = (float)bc_math($real_total_money, $order_data['deliverMoney'], 'bcadd', 2);//实际金额加上运费
//                }
                if ($coupon_id > 0) {
                    $order_data['couponId'] = $coupon_id;
                    $order_data['coupon_money'] = $coupon_money;//优惠券面额
                    $order_data['coupon_use_money'] = $coupon_use_max_money;//优惠券实际抵扣金额
//                    $real_total_money = (float)bc_math($real_total_money, $coupon_money, 'bcsub', 2);//实际金额减去优惠券金额
                    $real_total_money = (float)bc_math($real_total_money, $coupon_use_max_money, 'bcsub', 2);//实际金额减去优惠券金额
                }
                $current_order_setDeliveryMoney = 0;//修复常规非常规拆单后运费问题
                if ($is_used_delivery_coupon == 1) {
                    //已有分单使用过优惠券,则其他的分单不参与运费计算
                    $real_total_money = (float)bc_math($real_total_money, $order_data['deliverMoney'], 'bcsub', 2);//实际金额加上运费
                    $order_data['setDeliveryMoney'] = 0;
                    $order_data['deliverMoney'] = 0;
                } else {
                    if ($order_num_key == 1) {//第一笔订单加上运费,其他的就不加了
                        if ($all_order_num > 1) {//存在分单
                            if ($order_data['deliverMoney'] > 0) {
                                $order_data['setDeliveryMoney'] = $order_data['deliverMoney'];//重新支付的时候实付金额加上该字段,避免提交订单但是没有付款而导致少收取运费
                                $order_data['deliverMoney'] = 0;
                                $setDeliveryMoney_tag = $order_data['setDeliveryMoney'];
                                $current_order_setDeliveryMoney = $order_data['setDeliveryMoney'];
                            }
                            //$real_total_money = (float)bc_math($real_total_money, $order_data['setDeliveryMoney'], 'bcadd', 2);//实际金额加上运费
                        } else {
                            //$real_total_money = (float)bc_math($real_total_money, $order_data['deliverMoney'], 'bcadd', 2);//实际金额加上运费
                            $order_data['setDeliveryMoney'] = 0;
                            $current_order_setDeliveryMoney = $order_data['deliverMoney'];
                            $setDeliveryMoney_tag = $current_order_setDeliveryMoney;
                            if ($pay_from != 3) {
                                $order_data['deliverMoney'] = $current_order_setDeliveryMoney;
                                $real_total_money += $order_data['deliverMoney'];
                            }
                        }
                    }
                }
                if ($real_total_money < 0) {
                    $real_total_money = 0;
                }
                $order_data['realTotalMoney'] = (float)$real_total_money;//实付金额
                $order_data['needPay'] = (float)$real_total_money;//需付金额
                $order_data["poundageRate"] = (!empty($shop_detail) && (float)$shop_detail['commissionRate'] > 0) ? (float)$shop_detail['commissionRate'] : (float)$configs['poundageRate'];//佣金比例
                $order_data["poundageMoney"] = WSTBCMoney($order_data["totalMoney"] * $order_data["poundageRate"] / 100, 0, 2);//佣金
                //余额支付-start
                if ($pay_from == 3 && $order_data['realTotalMoney'] > 0) {
                    $order_data['deliverMoney'] = $current_order_setDeliveryMoney;
                    $order_data['realTotalMoney'] += $order_data['deliverMoney'];
                    if (!$users_module->isAdequateBalance($users_id, $order_data['realTotalMoney'])) {//校验余额是否充足
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '余额不足');
                    }
                    if (!$users_module->deductionUsersBalance($users_id, $order_data['realTotalMoney'], M())) {//扣除余额
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '余额支付失败');
                    }
                    $order_data['orderStatus'] = 0;//支付成功
                    $order_data['isReceivables'] = 2;//(psd)是否收款(0:待收款|1:预收款|2:已收款(全款))
                    $order_data['isPay'] = 1;//已支付
                    $order_data['pay_time'] = date('Y-m-d H:i:s');//支付时间
                    if ($configs['setDeliveryMoney'] == 1) {
                        $user_balance_data = array(
                            'userId' => $users_id,
                            'balance' => (float)$order_data['realTotalMoney'],
                            'dataSrc' => 1,
                            'orderNo' => $bill_no,
                            'dataRemarks' => "余额支付",
                            'balanceType' => 2
                        );
                        $balance_data_res = $users_module->addUserBalance($user_balance_data, M());
                        if (!$balance_data_res) {
                            M()->rollback();
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '余额流水记录失败');
                        }
                        //支付通知
//                        $push = D('Adminapi/Push');//以前就是这样写的,暂保留,有时间需要改掉
//                        $push->postMessage(7, $users_id, $bill_no, $shop_id);
                        $notify_module->postMessage(7, $users_id, $bill_no, $shop_id);
                    }
                }
                //余额支付-end
                $order_data['lat'] = $lat;
                $order_data['lng'] = $lng;
                if ($pay_from == 4) {
                    //货到付款
                    $order_data['payType'] = 0;
                    $order_data['orderStatus'] = 0;
                    $order_data['isPay'] = 1;
                }
                if ($exists_convention == 1) {
                    $order_data['orderType'] = 5;
                    //非常规订单配送时长累加
                    $unconventionality = (float)$configs['unconventionality'] * 60 * 60;//非常规订单配送时长
                    $require_time = date("Y-m-d H:i:s", strtotime($require_time) + $unconventionality);
                    $order_data['requireTime'] = $require_time;
                }
                if ((float)$order_data['realTotalMoney'] <= 0 && $order_data['payFrom'] != 4) {
                    //非货到付款,但是实付金额为0的订单,不用掉支付,直接成功即可
                    $order_data['orderStatus'] = 0;
                    $order_data['isPay'] = 1;
                    $order_data['pay_time'] = date('Y-m-d H:i:s');
                }
                //订单商品延时
                if (count($delayed_arr) > 0) {
                    array_multisort($delayed_arr, SORT_DESC);
                    $real_delayed = $delayed_arr[0] * 60;
                    if (!empty($order_data['requireTime'])) {
                        $require_time = strtotime($order_data['requireTime']) + $real_delayed;
                        $order_data['requireTime'] = date('Y-m-d H:i:s', $require_time);
                    }
                }
                $order_data['needPay'] = $order_data['realTotalMoney'];
                $order_data['receivableAmount'] = $order_data['realTotalMoney'];//应收金额
                $order_data['receivedAmount'] = $order_data['realTotalMoney'];//已收金额
                $order_data['paidInAmount'] = $order_data['realTotalMoney'];//实收金额
                $order_data['uncollectedAmount'] = 0;//未收金额
                if ($order_data['payFrom'] == 4) {
                    $order_data['receivedAmount'] = 0;//已收金额
                    $order_data['paidInAmount'] = 0;//实收金额
                    $order_data['uncollectedAmount'] = $order_data['realTotalMoney'];//未收金额
                }
                $order_id = $order_module->saveOrdersDetail($order_data, M());
                if (empty($order_id)) {
                    M()->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单创建失败');
                }
                $check_requiretime_res = $order_module->verificationRequireTime($order_id);//检测送达时间
                if ($check_requiretime_res['code'] != ExceptionCodeEnum::SUCCESS) {
                    M()->rollback();
                    return $check_requiretime_res;
                }
                if ($is_self == 1) {
                    //如果是自提订单,生成自提码
                    $create_res = $order_module->createBootstrapCode($order_id, M());
                    if (empty($create_res)) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提货码生成失败');
                    }
                }
                //创建订单记录-start
                $content = '下单成功，等待支付';
                $log_params = array(
                    'orderId' => $order_id,
                    'logContent' => $content,
                    'logUserId' => $users_id,
                    'logUserName' => '用户',
                    'orderStatus' => -2,
                    'payStatus' => 0,
                    'logType' => 0,
                    'logTime' => date('Y-m-d H:i:s'),
                );
                if ($pay_from == 3) {
                    $log_params['orderStatus'] = 0;
                    $log_params['payStatus'] = 1;
                    $log_params['logContent'] = "下单成功，余额支付成功";
                } elseif ($pay_from == 4) {
                    $log_params['orderStatus'] = 0;
                    $log_params['payStatus'] = 1;
                    $log_params['logContent'] = "下单成功，货到付款";
                }
                if ($order_data['realTotalMoney'] <= 0 || $pay_from == 4) {
                    $log_params['orderStatus'] = 0;
                    $log_params['payStatus'] = 1;
                    $log_params['logContent'] = "下单成功";
                }
                $log_res = $log_orders_module->addLogOrders($log_params, M());
                if (!$log_res) {
                    M()->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单日志记录失败');
                }
                //创建订单记录-end
                //用户积分-start
                if ($use_score == 1 && $configs['isOpenScorePay'] == 1) {
                    $score = (int)$order_data['useScore'];
                    $reset_score_result = $users_module->deduction_users_score($users_id, $score, M());
                    if ($reset_score_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', $reset_score_result['msg']);
                    }
                    //用户积分流水-start
                    $user_score_data = array(
                        'userId' => $users_id,
                        'dataSrc' => 1,
                        'score' => $score,
                        'dataId' => $order_id,
                        'dataRemarks' => "抵用现金【原有积分{$users_detail['userScore']}】",
                        'scoreType' => 2
                    );
                    $score_data_res = $users_module->addUserScore($user_score_data, M());
                    if (!$score_data_res) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '积分流水记录失败');
                    }
                    //用户积分流水-end
                }
                //用户积分-end
                //订单商品明细-start
                $current_order_use_score = (int)$order_data['useScore'];//当前订单使用的积分
                $sku_list_map = [];
                $sku_id_arr = array_column($current_goods, 'skuId');
                if (count($sku_id_arr) > 0) {
                    $systemSpecList = $goods_module->getSkuSystemListById($sku_id_arr, 2);
                    foreach ($systemSpecList as $systemSpecListRow) {
                        if ($systemSpecListRow['skuId'] <= 0) {
                            continue;
                        }
                        $sku_list_map[$systemSpecListRow['skuId']] = $systemSpecListRow;
                    }
                }
                foreach ($current_goods as $curr_goods_key => $curr_goods_val) {
                    $goods_id = (int)$curr_goods_val['goodsId'];
                    $sku_id = (int)$curr_goods_val['skuId'];
                    $goods_attr_id = (int)$curr_goods_val['goodsAttrId'];
                    $goods_cnt = (float)$curr_goods_val['goods_cnt'];
//                    $shop_price = $curr_goods_val['shopPrice'];
                    $cart_id = $curr_goods_val['cartId'];
                    $init_price = $curr_goods_val['init_price'];//经过处理后的最终价格
//                    $goods_type = 1;//购买时商品的类型 1:普通商品 2:限量商品 3:限时商品
                    $goods_type = $curr_goods_val['goods_type'];//购买时商品的类型 1:普通商品 2:限量商品 3:限时商品
                    if (!$goods_module->isTrueLimitGoods($goods_id, $goods_cnt)) {//通过限量活动校验成功的商品
                        $goods_type = 1;
                    }
//                    if ($goods_module->isTrueFlashSaleGoods($goods_id, $goods_cnt)) {//通过限时活动校验成功的商品
//                        $goods_type = 3;
//                    }
                    $order_goods_info = array(
                        'orderId' => $order_id,
                        'goodsId' => $goods_id,
                        'goodsNums' => $goods_cnt,
                        'goodsPrice' => $init_price,
                        'goodsAttrId' => $goods_attr_id,
                        'skuId' => $sku_id,
                        'remarks' => '',//智能备注
                        'purchase_type' => $curr_goods_val['purchase_type'],
                        'purchaser_or_supplier_id' => $curr_goods_val['purchaser_or_supplier_id'],
                        'goodsImg' => $curr_goods_val['goodsImg'],
                        'unitName' => $curr_goods_val['unit'],
                        'goodsCode' => $curr_goods_val['goodsSn'],
                        'skuSpecStr' => '',
                        'goodsSpec' => (string)$curr_goods_val['goodsSpec'],
                    );
                    $remarks = (string)$curr_goods_val['remarks'];//购物车商品备注
                    $intelligent_remark = $curr_goods_val['IntelligentRemark'];//商品智能备注
                    if (!empty($remarks) && !empty($intelligent_remark)) {
                        $intelligent_remark_arr = explode('@', $intelligent_remark);
                        if (in_array($remarks, $intelligent_remark_arr)) {
                            $order_goods_info['remarks'] = $remarks;
                        }
                    }
                    if ($sku_id > 0) {//商品sku规格
//                        $sku_system_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
                        $sku_system_detail = $sku_list_map[$sku_id];
                        $order_goods_info['skuSpecAttr'] = (string)$sku_system_detail['skuSpecAttr'];
                        $order_goods_info['skuSpecStr'] = (string)$sku_system_detail['skuSpecAttrTwo'];
                        $order_goods_info['unit'] = (string)$sku_system_detail['unit'];
                        $order_goods_info['goodsImg'] = (string)$sku_system_detail['skuGoodsImg'];
                        $order_goods_info['goodsCode'] = (string)$sku_system_detail['skuBarcode'];
                    }
                    $order_goods_info["goodsName"] = $curr_goods_val["goodsName"];
                    $order_goods_info["goodsThums"] = $curr_goods_val["goodsThums"];
                    $order_goods_info['couponMoney'] = 0;//优惠券抵扣金额
                    $order_goods_info['scoreMoney'] = 0;//积分抵扣金额
                    $order_goods_info['is_weight'] = $curr_goods_val['is_weight'];
                    $goods_price_total = (float)$curr_goods_val['goods_price_total'];//商品金额小计
                    if (!empty($coupon_id)) {//优惠券金额分摊到商品
                        $goods_share_coupon_amount = $order_module->goodsShareCouponAmount($order_id, $goods_id, $goods_price_total, $order_data['totalMoney']);
                        $order_goods_info['couponMoney'] = $goods_share_coupon_amount;
                    }
                    if ($order_data['useScore'] > 0) {//积分金额分摊到商品
                        $goods_share_score_amount = $order_module->goodsShareScoreAmount($current_order_use_score, $goods_id, $goods_price_total);
                        $order_goods_info['scoreMoney'] = $goods_share_score_amount;
                    }
                    $order_goods_info['goods_type'] = $goods_type;
                    $curr_goods_val['goods_type'] = $goods_type;
                    $curr_goods_val['is_weight'] = $goods_type;
                    $order_goods_res = $order_module->saveOrderGoods($order_goods_info, M());
                    if (!$order_goods_res) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单商品明细添加失败');
                    }
                    //扣除商品库存
                    $updateGoodsStockResult = reduceGoodsStockByRedis($curr_goods_val, M());//减去商品库存
                    if ($updateGoodsStockResult['code'] != ExceptionCodeEnum::SUCCESS) {
                        M()->rollback();
                        return $updateGoodsStockResult;
                    }
                    if ($goods_module->isTrueLimitGoods($goods_id, $goods_cnt)) {//通过限量活动校验成功的商品
                        $limit_log_params = array(
                            'goodsId' => $goods_id,
                            'number' => $goods_cnt,
                        );
                        $limit_log_result = $goods_service_module->addLimitGoodsBuyLog($users_id, $limit_log_params, M());//记录限量购购买记录
                        if ($limit_log_result['result'] != ExceptionCodeEnum::SUCCESS) {
                            M()->rollback();
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '限量购购买记录添加失败');
                        }
//                        $goods_type = 2;
                    }
                    //限制商品下单次数
                    $add_limit_res = $order_module->limitGoodsOrderNum($goods_id, $users_id, M());
                    if (!$add_limit_res) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，限制商品下单次数记录失败');
                    }
                    //写入秒杀记录
                    if ($curr_goods_val['isShopSecKill'] == 1) {
                        //现在貌似已废弃
                        $kill_res = $order_module->addGoodsSecondskilllimit($order_id, $goods_id, $goods_cnt, M());
                        if (!$kill_res) {
                            M()->rollback();
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，商品秒杀限量记录失败');
                        }
                    }
                    //清除商品对应的购物车数据
                    if ($cart_id > 0) {
                        $clear_cart_res = $cart_module->clearCartById($cart_id, M());
                        if (!$clear_cart_res) {
                            M()->rollback();
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，购物车商品清除失败');
                        }
                    }
                }
                //订单商品明细-end
                if ($order_data['isPay'] == 1) {
                    $report_res = addReportForms($order_id, 1, array(), M());//添加报表
                    if ($report_res['code'] == -1) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，报表记录失败');
                    }
                }
                //订单生成,修改用户优惠券状态为已使用
                if ($order_data['couponId'] > 0) {
                    $user_coupon_data = array(
                        'id' => $cuid,
                        'orderNo' => $bill_no,
                        'couponStatus' => 0,
                    );
                    $save_user_coupon_res = $coupon_module->saveUsersCoupon($user_coupon_data, M());
                    if (empty($save_user_coupon_res)) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，用户优惠券使用失败');
                    }
                }
                //订单生成,修改用户运费券状态为已使用
                if ($order_data['delivery_coupon_id'] > 0) {
                    $user_coupon_data = array(
                        'id' => $wu_coupon_id,
                        'orderNo' => $bill_no,
                        'couponStatus' => 0,
                    );
                    $save_delivery_coupon_res = $coupon_module->saveUsersCoupon($user_coupon_data, M());
                    if (empty($save_delivery_coupon_res)) {
                        M()->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，用户运费券使用失败');
                    }
                    $is_used_delivery_coupon = 1;//运费券已被使用
                }
                $remind_res = $order_module->addOrderRemind($order_id, M());//建立订单提醒记录
                if (!$remind_res) {
                    M()->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，订单提醒记录失败');
                }
                $notify_module->postMessage(6, $users_id, $bill_no, $shop_id);//推送,保留之前的写法
                $order_no_arr[] = $order_data['orderNo'];
                $real_total_money_arr[] = $order_data['realTotalMoney'];
                $order_id_arr[] = $order_id;
            }
        }
        //==============================end==============================================================
        $return_data = array();
        $real_total_money_arr_money = array_sum($real_total_money_arr);
        if ($all_order_num > 1) {
            $real_total_money_arr_money += $setDeliveryMoney_tag;
        }
        $return_data["appRealTotalMoney"] = formatAmount($real_total_money_arr_money);//多个订单总金额 单位元
        $return_data["orderNo"] = implode("A", $order_no_arr);//订单号  多个订单号用A分隔
        $return_data['orderId'] = strencode(implode("A", $order_id_arr));//订单id  多个订单id用A隔开
        $current_economize_amount = $order_module->preSubmitEconomyAmount($users_id, $shop_id);
        $return_data['economyAmount'] = formatAmount($current_economize_amount);//会员节省了多少钱
        $merge_data = array(
            'value' => $return_data['orderNo'],
            'realTotalMoney' => $return_data['appRealTotalMoney'],
        );
        $merge_res = $order_module->addOrderMerge($merge_data, M());
        if (!$merge_res) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败');
        }
        if (count($order_no_arr) > 0) {
            $return_data['orderToken'] = md5($return_data['orderNo']);
            $update_token_res = $order_module->updateOrderToken($order_no_arr, $return_data['orderToken'], M());//更新订单的合并支付标识
            $update_set_delivery_money_res = true;
            if ($pay_from != 3) {
                $update_set_delivery_money_res = $order_module->updateOrderSetDeliveryMoney($order_no_arr, $setDeliveryMoney_tag, M());//更新订单的setDeliveryMoney字段
            }
            if (!$update_token_res && $update_set_delivery_money_res) {
                M()->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败');
            }
        }
        $return_data['orderToken'] = md5($return_data["orderNo"]);
        //地推相关
        $pull_res = $order_module->addPullNewAmountLog($users_id, $return_data['orderToken'], M());
        if (!$pull_res) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '下单失败，地推记录失败');
        }
        //积分处理-start
        $score_res = $users_module->deduction_users_score($users_id, 0, M());
        if ($score_res['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $score_res['msg']);
        }
        //积分处理-end

        foreach ($order_id_arr as $order_id) {
            //临时修复，订单自动受理
            $order_row = $order_module->getOrderInfoById($order_id, "*", 2);
            if ($order_row['isPay'] != 1) {
                continue;
            }
            $getShopsList = $shop_module->getShopsList();
            $printsInfo = [];
            if (!empty($getShopsList)) {//处理在营业时间内的店铺自动接单
                foreach ($getShopsList as $shopIdKey => $shopIdRow) {
                    if ($shopIdRow['shopId'] != $order_row['shopId']) {
                        continue;
                    }
                    $getPrintsInfo = $shop_module->getPrintsList($shopIdRow['shopId']);
                    if (!empty($getPrintsInfo)) {
                        foreach ($getPrintsInfo as $printInfo) {
                            if ($printInfo['isDefault'] == 1) {//是否默认【0:否|1:默认】
                                $printsInfo = $printInfo;
                            }
                        }
                    }
                }
            }
            if (!empty($printsInfo)) {
                $order_row["printsInfo"] = $printsInfo;
                $order_module->shopOrderAccept($order_row);
            }
        }
        M()->commit();
        return returnData($return_data, ExceptionCodeEnum::SUCCESS, 'success', '提交成功');
    }

    /**
     * 订单-确认收货
     * @param int $userId 用户id
     * @param int $orderId 订单id
     * @param int $type 操作(-1:拒收 1:确认收货)
     * @param string $rejectionRemarks 拒收原因
     * @return array
     * */
    public function confirmReceipt(int $userId, int $orderId, $type = 1, $rejectionRemarks = '')
    {
        $orderModule = new OrdersModule();
        $orderDetail = $orderModule->getOrderInfoById($orderId, 'orderId,userId', 2);
        if ($orderDetail['userId'] != $userId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户和订单信息不匹配');
        }
        $result = $orderModule->confirmReceipt($orderId, $type, $rejectionRemarks);
        return $result;
    }

//    public function WxSubmitOrder($param)
//    {//旧的
//        $goods_service_module = new GoodsServiceModule();
////        $submitMod = D('V3/SubmitOrder');
//        //$submitMod = new SubmitOrderModel();
//        $submitMod = new SubmitOrderModel();
//        $checkCashOnDeliveryParamsRes = $submitMod->checkCashOnDeliveryParams($param);//后加验证货到付款参数的有效性
//        if ($checkCashOnDeliveryParamsRes['code'] == -1) {
//            $apiRet = returnData(null, -1, 'error', $checkCashOnDeliveryParamsRes['msg']);
//            return $apiRet;
//        }
//        $addressId = $param['addressId'];
//        $userId = $param['userId'];
//        $cuid = $param['cuid'];
//        $isSelf = $param['isSelf'];
//        $getuseScore = $param['useScore'];
//        $orderRemarks = $param['orderRemarks'];
//        $requireTime = $param['requireTime'];
//        $payFrom = $param['payFrom'];
//        $fromType = $param['fromType'];//应用模式(1:前置仓|2:多商户)
//        $shopParam = (array)json_decode($param['shopParam'], true);
//        $shopId = $param['shopId'];
//        $wuCouponId = $param['wuCouponId'];//运费劵ID
//        if ($fromType == 2) {
//            $shopId = 0;
//        }
//        $cartGoods = $submitMod->getCartGoodsChecked($userId, $shopId);
//        $goodsSku = $cartGoods['goodsSku'];
//        if (!empty($shopParam)) {
//            //兼容京东到家模式,某一家店铺结算
//            $shopIdArr = array_column($shopParam, 'shopId');
//            foreach ($goodsSku as $key => $value) {
//                if (!in_array($value['shopId'], $shopIdArr)) {
//                    unset($goodsSku[$key]);
//                }
//            }
//            $goodsSku = array_values($goodsSku);
//        }
//        M()->startTrans();
//        $mlogo = M('log_orders');
//        $orders = M("orders");
//        $goodsTab = M("goods");
//        $systemTab = M('sku_goods_system');
//        $cart = M("cart");
//        $userTab = M('users');
//        $orderGoodsTab = M("order_goods");
//        $orderRemindsTab = M('order_reminds');
//        $userScoreTab = M('user_score');
//        $sm = D('Home/Shops');
//        //根据用户id获取用户信息
//        $userInfo = $this->getUserInfoById($userId);
//        $userId = (int)$userInfo['userId'];
//        $shopId = $goodsSku[0]['shopId'];
////        $submitMod = D('V3/SubmitOrder');
//        //检查收货地址是否存在
//        $addressInfo = $submitMod->getAddress($userId, $addressId);
//        if ($isSelf != 1) {//自提订单就不用验证收货地址了
//            if (empty($addressInfo)) {
//                M()->rollback();
//                unset($apiRet);
//                $apiRet = returnData(null, -1, 'error', '请添加正确的收货地址');
//                return $apiRet;
//            }
//            if ($fromType == 1) {
//                //前置仓
//                //验证商品是否在配送区域
//                $checkShopCommunitys = $submitMod->checkShopCommunitys($userId, $shopId, $addressId);
//                if (isset($checkShopCommunitys['code']) && $checkShopCommunitys['code'] == -1) {
//                    M()->rollback();
//                    return $checkShopCommunitys;
//                }
//            } else {
//                //多商户
//                foreach ($shopParam as $shopVal) {
//                    $shopId = $shopVal['shopId'];
//                    $checkShopCommunitys = $submitMod->checkShopCommunitys($userId, $shopId, $addressId);
//                    if (isset($checkShopCommunitys['code']) && $checkShopCommunitys['code'] == -1) {
//                        M()->rollback();
//                        return $checkShopCommunitys;
//                    }
//                }
//            }
//        }
//        $config = $GLOBALS['CONFIG'];
//        if ($config['deliveryMoney'] <= 0) {
//            $config['deliveryMoney'] = 0;
//        }
//        if ($config['deliveryFreeMoney'] <= 0) {
//            $config['deliveryFreeMoney'] = 0;
//        }
//        //验证商品的有效性
//        $checkGoodsStatus = $submitMod->checkGoodsStatus($goodsSku, $userId, $addressId, $isSelf);
//        if (isset($checkGoodsStatus['code']) && $checkGoodsStatus['code'] == -1) {
//            M()->rollback();
//            return $checkGoodsStatus;
//        }
//        //判断每笔订单 是否达到配送条件
//        $result = $submitMod->handleGoodsSku($goodsSku);
//        if (count($result > 0)) {
//            foreach ($result as $key => $val) {
//                $result[$key] = rankGoodsPrice($val);
//            }
//        }
//        //生成订单数据
//        $orderids = M('orderids');
//        //检测是否符合店铺设置的配送起步价
//        $checkInfo = checkdeliveryStartMoney($result, $userInfo);
//        if (isset($checkInfo['state']) && $checkInfo['state'] == false) {
//            M()->rollback();
//            unset($apiRet);
//            $apiRet = returnData($checkInfo['shopInfo'], -1, 'error', '未达到店铺订单配送起步价');
//            return $apiRet;
//        }
//        //=========非常规商品/常规商品start===================2020-8-25 16:51:34===========================
//        $conventionList = $result;
//        $result = [];
//        foreach ($conventionList as $key => $value) {
//            $noConvent = [];//非常规商品
//            $convent = [];//常规商品
//            foreach ($value as $k => $v) {
//                if ($v['isConvention'] == 0) {
//                    $noConvent[] = $v;
//                } elseif ($v['isConvention'] == 1) {
//                    $convent[] = $v;
//                }
//            }
//            if (!empty($noConvent)) {
//                $result[] = $noConvent;
//            }
//            if (!empty($convent)) {
//                $result[] = $convent;
//            }
//        }
//        //==============================end==============================================================
//        foreach ($result as $key => $value) {
//            $userInfo = $this->getUserInfoById($userId);
//            $shopId = $value[0]['shopId'];
//            $shopInfo = $submitMod->getShopInfo($shopId);
//            $shopParamInfo = [];
//            if ($fromType == 2) {
//                foreach ($shopParam as $sval) {
//                    if ($shopInfo['shopId'] == $sval['shopId']) {
//                        $shopParamInfo = (array)$sval;
//                    }
//                }
//            }
//            //生成订单号
//            $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//            $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//            $data = [];
//            $data["orderNo"] = $orderNo;//订单号
//            $data["shopId"] = $shopId;
//            $data["orderStatus"] = "-2";//订单状态为待支付
//            $data['isReceivables'] = 0;//是否收款(0:待收款|1:预收款|2:已收款(全款))
//            if ($payFrom == 3) {
//                $data['isReceivables'] = 2;
//            } elseif ($payFrom == 4) {
//                //如果是货到付款,订单默认为待受理状态
//                $data['orderStatus'] = 0;
//            }
//            $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//            //订单公用参数
//            if ($isSelf == 1) {
//                //自提订单不需要用户的收货地址而是取货的店铺地址
//                $addressInfo = [];
//                $addressInfo['areaId1'] = $shopInfo['areaId1'];
//                $addressInfo['areaId2'] = $shopInfo['areaId2'];
//                $addressInfo['areaId3'] = $shopInfo['areaId3'];
//                $addressInfo['setaddress'] = '';
//                $addressInfo['postCode'] = '';
//                $addressInfo['communityId'] = 0;
//                $addressInfo['userName'] = $userInfo['userName'];
//                $addressInfo['address'] = $shopInfo['shopAddress'];
//                $addressInfo['lat'] = $shopInfo['latitude'];
//                $addressInfo['lng'] = $shopInfo['longitude'];
//                $addressInfo['userPhone'] = $userInfo['userPhone'];
//            }
//            $data["areaId1"] = $addressInfo["areaId1"];
//            $data["areaId2"] = $addressInfo["areaId2"];
//            $data["areaId3"] = $addressInfo["areaId3"];
//            $data["payType"] = "1";//支付方式
//            if ($payFrom == 4) {
//                //货到付款
//                $data['payType'] = 0;
//            }
//            $data["isSelf"] = $isSelf;//是否自提
//            $data["isPay"] = "0";//是否支付
//            $data["userId"] = $userId;//用户Id
//            $data["userName"] = $addressInfo["userName"];//收货人名称
//            $data["communityId"] = $addressInfo["communityId"];//收货地址所属社区id
//            //收货地址
//            $addressInfo['areaId1Name'] = $this->getAreaName($addressInfo['areaId1']);
//            $addressInfo['areaId2Name'] = $this->getAreaName($addressInfo['areaId2']);
//            $addressInfo['areaId3Name'] = $this->getAreaName($addressInfo['areaId3']);
//            $data['userAddress'] = '';
//            if (handleCity($addressInfo['areaId1Name'])) {
//                $data['userAddress'] .= $addressInfo['areaId1Name'] . ' ';
//            }
//            $data['userAddress'] .= $addressInfo['areaId2Name'] . ' ';
//            $data['userAddress'] .= $addressInfo['areaId3Name'] . ' ';
//            $data["userAddress"] .= $this->getCommunity($data['communityId']) . $addressInfo['setaddress'] . $addressInfo["address"];
//            $data["userPhone"] = $addressInfo["userPhone"];//收件人手机
//            $data["userPostCode"] = $addressInfo["postCode"];//收件人邮编
//            $data['invoiceClient'] = (int)$param['invoiceClient'];//发票id
//            $data['isInvoice'] = $data['invoiceClient'] > 0 ? 1 : 0;//是否需要发票
//            $data["orderRemarks"] = $orderRemarks;//订单备注
//            $data["isAppraises"] = "0";//是否点评
//            $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//            $data["orderSrc"] = "3";//订单来源
//            $data["orderFlag"] = "1";//订单有效标志
//            $data["payFrom"] = $payFrom;//支付来源
//            $data['requireTime'] = $requireTime;
//            //在前置仓的基础上兼容多商户
//            if ($fromType == 2) {
//                $data['invoiceClient'] = (isset($shopParamInfo['invoiceClient']) && (int)$shopParamInfo['invoiceClient'] > 0) ? $shopParamInfo['invoiceClient'] : 0;
//                $data['isInvoice'] = (isset($shopParamInfo['invoiceClient']) && $shopParamInfo['invoiceClient']) > 0 ? 1 : 0;
//                $data['orderRemarks'] = isset($shopParamInfo['orderRemarks']) ? $shopParamInfo['orderRemarks'] : '';
//            }
//            $singleData = $result[$key];
//            $type = 0;//用于判断非常规订单【0:不存在|1:存在】
//            $delayed_arr = array();
//            foreach ($singleData as $singleKey => $singleVal) {
//                $goods_delayed = (int)$singleVal['delayed'];
//                if ($goods_delayed > 0) {
//                    $delayed_arr[] = $goods_delayed;
//                }
//                //判断限量购商品---进行数据替换
//                if ($singleVal['isLimitBuy'] == 1) {
//                    $goodsLimitCount = goodsTimeLimit($singleVal['goodsId'], $singleVal, null, $singleVal['goodsCnt']);
//                    if (empty($goodsLimitCount)) {
//                        $result[$key][$singleKey]["shopPrice"] = $singleVal["limitCountActivityPrice"];
//                        $result[$key][$singleKey]["isFlashSaleOk"] = 1;//用于判断是否是限量购商品
//                        $result[$key][$singleKey]['limit_buy_state'] = 1;//是否满足限量商品的条件(-1:未通过限量活动校验 1:通过限量活动校验)
//                    }
//                }
//                //判断限时购商品---进行数据替换
//                if ($singleVal['isFlashSale'] == 1) {
//                    $getGoodsFlashSale = getGoodsFlashSale($singleVal['goodsId']);
//                    if ($getGoodsFlashSale['code'] == 1) {
//                        $goodsTimeLimit = goodsTimeLimit($singleVal['goodsId'], $singleVal, $getGoodsFlashSale, $singleVal['goodsCnt']);
//                        if (empty($goodsTimeLimit)) {
//                            $result[$key][$singleKey]["shopPrice"] = $getGoodsFlashSale["activityPrice"];
//                            $result[$key][$singleKey]["marketPrice"] = $getGoodsFlashSale["marketPrice"];
//                            $result[$key][$singleKey]["goodsStock"] = $getGoodsFlashSale["activeInventory"];
//                            $result[$key][$singleKey]["minBuyNum"] = $getGoodsFlashSale["minBuyNum"];
//                            $result[$key][$singleKey]["isFlashSaleOk"] = 1;//用于判断是否是限时购商品
//                            $result[$key][$singleKey]['flash_sale_state'] = 1;//是否满足限时商品的条件(-1:未通过限时活动校验 1:通过限时活动校验)
//                        }
//                    }
//                }
//                //获取当前订单所有商品总价
//                $totalMoney[$key][$singleKey] = (float)$result[$key][$singleKey]["shopPrice"] * (int)$singleVal["goodsCnt"];
//                $goodsTotalMoney = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                //后加sku
//                if ($singleVal["skuId"] > 0) {
//                    $goodsTotalMoney = getGoodsSkuPrice($userId, $singleVal["skuId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                }
//                $totalMoney[$key][$singleKey] = $goodsTotalMoney;
//                if ($singleVal['isConvention'] == 1) {
//                    $type = 1;//用于判断非常规订单【0:不存在|1:存在】
//                }
//            }
//            unset($singleKey);
//            unset($singleVal);
//            $totalMoney[$key] = array_sum($totalMoney[$key]);//计算总金额
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                if (isset($shopInfo['isInvoicePoint']) && !empty($shopInfo['isInvoicePoint'])) {
//                    $totalMoney[$key] += $totalMoney[$key] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//            $totalGoodsMoney[$key] = $totalMoney[$key];//纯粹的商品总金额
//            //-------------------------------------------------
//            //检验优惠券是否可用
//            $cuid = (int)(isset($shopParamInfo['cuid']) ? $shopParamInfo['cuid'] : $cuid);
//            if (!empty($cuid)) {
//                $checkCouponRes = $submitMod->checkCoupon($userInfo, $totalGoodsMoney[$key], $cuid);
//                if ($checkCouponRes['code'] == -1) {
//                    return $checkCouponRes;
//                }
//            }
//            //检验运费券是否可用
//            if (!empty($wuCouponId)) {
//                $wuCouponCouponRes = $submitMod->checkCoupon($userInfo, $totalGoodsMoney[$key], $wuCouponId);
//                if ($wuCouponCouponRes['code'] == -1) {
//                    return $wuCouponCouponRes;
//                }
//            }
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- start ---
//            $integral = 0;//赠送积分
//            foreach ($singleData as $singleKey => $singleVal) {
//                $cartGoodsInfo = $singleVal;
//                $goodsId = $cartGoodsInfo['goodsId'];
//                $skuId = $cartGoodsInfo['skuId'];
//                //判断限量购商品---进行数据替换
//                if ($cartGoodsInfo['isLimitBuy'] == 1) {
//                    $goodsLimitCount = goodsTimeLimit($cartGoodsInfo['goodsId'], $cartGoodsInfo, null, $cartGoodsInfo['goodsCnt']);
//                    if (empty($goodsLimitCount)) {
//                        $result[$key][$singleKey]["shopPrice"] = $cartGoodsInfo["limitCountActivityPrice"];
//                        $result[$key][$singleKey]["isFlashSaleOk"] = 1;//用于判断是否是限量购商品
//                        $result[$key][$singleKey]['limit_buy_state'] = 1;//是否满足限量商品的条件(-1:未通过限量活动校验 1:通过限量活动校验)
//                    }
//                }
//                //判断限时购商品---进行数据替换
//                if ($cartGoodsInfo['isFlashSale'] == 1) {
//                    $getGoodsFlashSale = getGoodsFlashSale($cartGoodsInfo['goodsId']);
//                    if ($getGoodsFlashSale['code'] == 1) {
//                        $goodsTimeLimit = goodsTimeLimit($cartGoodsInfo['goodsId'], $cartGoodsInfo, $getGoodsFlashSale, $cartGoodsInfo['goodsCnt']);
//                        if (empty($goodsTimeLimit)) {
//                            $result[$key][$singleKey]["shopPrice"] = $getGoodsFlashSale["activityPrice"];
//                            $result[$key][$singleKey]["marketPrice"] = $getGoodsFlashSale["marketPrice"];
//                            $result[$key][$singleKey]["goodsStock"] = $getGoodsFlashSale["activeInventory"];
//                            $result[$key][$singleKey]["minBuyNum"] = $getGoodsFlashSale["minBuyNum"];
//                            $result[$key][$singleKey]["isFlashSaleOk"] = 1;//用于判断是否是限时购商品
//                            $result[$key][$singleKey]['flash_sale_state'] = 1;//是否满足限时商品的条件(-1:未通过限时活动校验 1:通过限时活动校验)
//                        }
//                    }
//                }
//                //获取当前订单所有商品总价
//                $totalMoney_s[$key][$singleKey] = (float)$result[$key][$singleKey]["shopPrice"] * (int)$cartGoodsInfo["goodsCnt"];
//                $goodsTotalMoney = getGoodsAttrPrice($userId, $cartGoodsInfo["goodsAttrId"], $goodsId, $result[$key][$singleKey]["shopPrice"], $cartGoodsInfo["goodsCnt"], $cartGoodsInfo["shopId"])['totalMoney'];
//                if ($skuId > 0) {
//                    $goodsTotalMoney = getGoodsSkuPrice($userId, $skuId, $goodsId, $result[$key][$singleKey]["shopPrice"], $cartGoodsInfo["goodsCnt"], $cartGoodsInfo["shopId"])['totalMoney'];
//                }
//                $totalMoney_s[$key][$singleKey] = $goodsTotalMoney;
//                $goodsInfo = $goodsTab->where(['goodsId' => $goodsId])->find();
//                if (($userInfo['expireTime'] > date('Y-m-d H:i:s')) && empty($result[$key][$singleKey]["isFlashSaleOk"])) {//是会员并且不是限时限量商品
//                    $systemSkuSpec = M('sku_goods_system')->where(['skuId' => $skuId])->find();
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMoney_s[$key][$singleKey] = (int)$cartGoodsInfo["goodsCnt"] * $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMoney_s[$key][$singleKey] = $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//                //商品奖励积分
//                $integral += getRewardsIntegral($cartGoodsInfo,$totalMoney_s[$key][$singleKey]);
//            }
//            unset($singleKey);
//            unset($singleVal);
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- end ---
//            if (is_array($totalMoney_s[$key])) {
//                $totalMoney[$key] = array_sum($totalMoney_s[$key]);//计算总金额
//            } else {
//                $totalMoney[$key] = $totalMoney_s[$key];//计算总金额
//            }
//            //判断是否使用发票
//            if ($data['isInvoice'] == 1) {
//                if (isset($shopInfo['isInvoicePoint']) && !empty($shopInfo['isInvoicePoint'])) {
//                    $totalMoney[$key] += $totalMoney[$key] * ($shopInfo['isInvoicePoint'] / 100);
//                }
//            }
//            $goodsTotalMoney = [];
//            $goodsTotalMoney[$key] = $totalMoney[$key];//纯粹的商品总金额
//            $data["totalMoney"] = (float)$goodsTotalMoney[$key];//商品总金额
////            $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney[$key]);//所得积分
//            $data["orderScore"] = $integral;//所得积分
//
//            //统一运费
//            if ($config['setDeliveryMoney'] == 2) {
//                $result[$key]["0"]["shopcm"]["deliveryMoney"] = $config['deliveryMoney'];
//                $result[$key]["0"]["shopcm"]["deliveryFreeMoney"] = $config['deliveryFreeMoney'];
//            }
//            $data["deliverMoney"] = $result[$key]["0"]["shopcm"]["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//            $data["deliverType"] = "1";//配送方式 门店配送
//            //设置订单配送方式
//            if ($result[$key]["0"]["shopcm"]["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//            if ($result[$key]["0"]["shopcm"]["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//            if ($result[$key]["0"]["shopcm"]["deliveryType"] == 6 and $isSelf !== 1) {
//                $data["deliverType"] = "6";//配送方式 自建司机
//            }
//            //当前店铺是否是达达配送 且当前订单 不为自提
//            if ($result[$key]["0"]["shopcm"]["deliveryType"] == 2 and $isSelf !== 1) {
//                $funData = [];
//                $funData['shopId'] = $shopId;
//                $funData['areaId2'] = $data["areaId2"];
//                $funData['orderNo'] = $data["orderNo"];
//                $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//                $funData['userName'] = $data["userName"];//收货人姓名
//                $funData['userAddress'] = $data["userAddress"];//收货人地址
//                $funData['userPhone'] = $data["userPhone"];//收货人手机
//                $dadaresFun = self::dadaDeliver($funData);
//                if ($dadaresFun['status'] == -6) {
//                    M()->rollback();
//                    $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == -7) {
//                    M()->rollback();
//                    $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//                    return $apiRet;
//                }
//                if ($dadaresFun['status'] == 0) {
//                    //获取成功
//                    $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                    $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//                }
//                $data["deliverType"] = "2";//配送方式 达达配送
//            }
//            //--------------------金额满减运费 start---------------------------
//            if ($config['setDeliveryMoney'] != 2 && (float)$data["totalMoney"] >= (float)$result[$key]["0"]["shopcm"]["deliveryFreeMoney"]) {
//                $data["deliverMoney"] = 0;
//            }
//            if ($config['setDeliveryMoney'] == 2) {
//                $data['deliverMoney'] = 0;
//                $data['setDeliveryMoney'] = $config['deliveryMoney'];
//            }
//            //-----------------------金额满减运费end----------------------
//            //------------------运费劵使用start-----------------------------------
//            if (!empty($wuCouponId)) {
//                $data["deliverMoney"] = 0;
//                $data["setDeliveryMoney"] = 0;
//            }
//            //---------------运费劵使用end-------------------------------------
//            //在使用运费之前处理好运费一些相关问题
//            if ($isSelf == 1) {//如果为自提订单 将运费重置为0
//                $data["deliverMoney"] = 0;
//                $data['setDeliveryMoney'] = 0;
//            }
//            $data["needPay"] = $totalMoney[$key] + $data["deliverMoney"];//需缴费用 加运费-----
//            $data["realTotalMoney"] = (float)$totalMoney[$key] + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//            if ($userInfo['userScore'] > 0 and $getuseScore > 0 and $config['isOpenScorePay'] == 1) {
//                //临时增加,解决积分不正确的问题,后面有时间优化
//                $end_goods_price_total = 0;
//                $end_goods_score_total = 0;
//                foreach ($singleData as $singleKey => $singleVal) {
//                    //判断限量购商品---进行数据替换
//                    if ($singleVal['isLimitBuy'] == 1) {
//                        $goodsLimitCount = goodsTimeLimit($singleVal['goodsId'], $singleVal, null, $singleVal['goodsCnt']);
//                        if (empty($goodsLimitCount)) {
//                            $result[$key][$singleKey]["shopPrice"] = $singleVal["limitCountActivityPrice"];
//                            $singleVal['shopPrice'] = $singleVal["limitCountActivityPrice"];
//                            $singleVal['goodsStock'] = $singleVal["limitCount"];
//                            $result[$key][$singleKey]["isFlashSaleOk"] = 1;
//                            $result[$key][$singleKey]['limit_buy_state'] = 1;//是否满足限时商品的条件(-1:未通过限时活动校验 1:通过限时活动校验)
//                        }
//                    }
//                    //判断限时购商品---进行数据替换
//                    if ($singleVal['isFlashSale'] == 1) {
//                        $getGoodsFlashSale = getGoodsFlashSale($singleVal['goodsId']);
//                        if ($getGoodsFlashSale['code'] == 1) {
//                            $goodsTimeLimit = goodsTimeLimit($singleVal['goodsId'], $singleVal, $getGoodsFlashSale, $singleVal['goodsCnt']);
//                            if (empty($goodsTimeLimit)) {
//                                $result[$key][$singleKey]["shopPrice"] = $getGoodsFlashSale["activityPrice"];
//                                $result[$key][$singleKey]['marketPrice'] = $getGoodsFlashSale['marketPrice'];
//                                $result[$key][$singleKey]['minBuyNum'] = $getGoodsFlashSale['minBuyNum'];
//                                $result[$key][$singleKey]['goodsStock'] = $getGoodsFlashSale['activeInventory'];
//                                $singleVal['shopPrice'] = $getGoodsFlashSale["activityPrice"];
//                                $singleVal['goodsStock'] = $getGoodsFlashSale["activeInventory"];
//                                $result[$key][$singleKey]["isFlashSaleOk"] = 1;
//                                $result[$key][$singleKey]['flash_sale_state'] = 1;//是否满足限时商品的条件(-1:未通过限时活动校验 1:通过限时活动校验)
//                            }
//                        }
//                    }
//                    //2019-6-14 start
//                    $getGoodsAttrPrice = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                    if ($singleVal["skuId"] > 0) {
//                        $getGoodsAttrPrice = getGoodsSkuPrice($userId, $singleVal['skuId'], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                    }
//                    $end_goods_price = (float)$getGoodsAttrPrice["goodsPrice"];
//                    //$goodsInfo = $goodsTab->where(['goodsId' => $singleVal["goodsId"]])->find();
//                    $goods_result = $goods_service_module->getGoodsInfoById($singleVal["goodsId"]);
//                    $goodsInfo = $goods_result['data'];
//                    //$systemSkuSpec = $systemTab->where(['skuId' => $singleVal["skuId"]])->find();
//                    $system_result = $goods_service_module->getSkuSystemInfoById($singleVal["skuId"]);
//                    $systemSkuSpec = $system_result['data'];
//                    if (($userInfo['expireTime'] > date('Y-m-d H:i:s')) && empty($result[$key][$singleKey]["isFlashSaleOk"])) {//是会员并且不是限时限量商品
//                        if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                            $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                        }
//                        //如果设置了会员价，则使用会员价，否则使用会员折扣
//                        if ($goodsInfo['memberPrice'] > 0) {
//                            $end_goods_price = $goodsInfo['memberPrice'];
//                        } else {
//                            if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                                $end_goods_price = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                            }
//                        }
//                    }
//                    $current_price_total = bc_math($end_goods_price, $singleVal['goodsCnt'], 'bcmul');
//                    $end_goods_price_total += $current_price_total;
//                    $discountPrice = $current_price_total;
//                    $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//                    $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//                    $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//当前商品可以抵扣的积分
//                    $end_goods_score_total += $integralRateScore;
//                }
//                unset($singleKey);
//                unset($singleVal);
//                //临时增加,解决积分不正确的问题,后面有时间优化
//                $end_goods_price_total = 0;
//                $end_goods_score_total = 0;
//                foreach ($singleData as $singleKey => $singleVal) {
//                    //判断限量购商品---进行数据替换
//                    if ($singleVal['isLimitBuy'] == 1) {
//                        $goodsLimitCount = goodsTimeLimit($singleVal['goodsId'], $singleVal, null, $singleVal['goodsCnt']);
//                        if (empty($goodsLimitCount)) {
//                            $result[$key][$singleKey]["shopPrice"] = $singleVal["limitCountActivityPrice"];
//                            $singleVal['shopPrice'] = $singleVal["limitCountActivityPrice"];
//                            $singleVal['goodsStock'] = $singleVal["limitCount"];
//                            $result[$key][$singleKey]["isFlashSaleOk"] = 1;
//                            $result[$key][$singleKey]["limit_buy_sale"] = 1;//是否满足限量活动限制(-1:不满足 1:满足)
//                        }
//                    }
//                    //判断限时购商品---进行数据替换
//                    if ($singleVal['isFlashSale'] == 1) {
//                        $getGoodsFlashSale = getGoodsFlashSale($singleVal['goodsId']);
//                        if ($getGoodsFlashSale['code'] == 1) {
//                            $goodsTimeLimit = goodsTimeLimit($singleVal['goodsId'], $singleVal, $getGoodsFlashSale, $singleVal['goodsCnt']);
//                            if (empty($goodsTimeLimit)) {
//                                $result[$key][$singleKey]["shopPrice"] = $getGoodsFlashSale["activityPrice"];
//                                $result[$key][$singleKey]['marketPrice'] = $getGoodsFlashSale['marketPrice'];
//                                $result[$key][$singleKey]['minBuyNum'] = $getGoodsFlashSale['minBuyNum'];
//                                $result[$key][$singleKey]['goodsStock'] = $getGoodsFlashSale['activeInventory'];
//                                $singleVal['shopPrice'] = $getGoodsFlashSale["activityPrice"];
//                                $singleVal['goodsStock'] = $getGoodsFlashSale["activeInventory"];
//                                $result[$key][$singleKey]["isFlashSaleOk"] = 1;
//                                $result[$key][$singleKey]["flash_sale_state"] = 1;//是否满足限时活动限制(-1:不满足 1:满足)
//                            }
//                        }
//                    }
//                    //2019-6-14 start
//                    $getGoodsAttrPrice = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                    if ($singleVal["skuId"] > 0) {
//                        $getGoodsAttrPrice = getGoodsSkuPrice($userId, $singleVal['skuId'], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                    }
//                    $end_goods_price = (float)$getGoodsAttrPrice["goodsPrice"];
//                    //$goodsInfo = $goodsTab->where(['goodsId' => $singleVal["goodsId"]])->find();
//                    $goods_result = $goods_service_module->getGoodsInfoById($singleVal["goodsId"]);
//                    $goodsInfo = $goods_result['data'];
//                    //$systemSkuSpec = $systemTab->where(['skuId' => $singleVal["skuId"]])->find();
//                    $system_result = $goods_service_module->getSkuSystemInfoById($singleVal["skuId"]);
//                    $systemSkuSpec = $system_result['data'];
//                    if (($userInfo['expireTime'] > date('Y-m-d H:i:s')) && empty($result[$key][$singleKey]["isFlashSaleOk"])) {//是会员并且不是限时限量商品
//                        if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                            $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                        }
//                        //如果设置了会员价，则使用会员价，否则使用会员折扣
//                        if ($goodsInfo['memberPrice'] > 0) {
//                            $end_goods_price = $goodsInfo['memberPrice'];
//                        } else {
//                            if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                                $end_goods_price = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                            }
//                        }
//                    }
//                    $current_price_total = bc_math($end_goods_price, $singleVal['goodsCnt'], 'bcmul');
//                    $end_goods_price_total += $current_price_total;
//                    $discountPrice = $current_price_total;
//                    $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//                    $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//                    $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//当前商品可以抵扣的积分
//                    $end_goods_score_total += $integralRateScore;
//                }
//                unset($singleKey);
//                unset($singleVal);
//                //获取比例
//                $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//                $userScore = $userInfo['userScore'];
//                if ($userScore < $end_goods_score_total) {
//                    $data["useScore"] = $userScore;
//                    $data["scoreMoney"] = (int)$userScore / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//                } else {
//                    $data["useScore"] = $end_goods_score_total;
//                    $data["scoreMoney"] = (int)$end_goods_score_total / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//                }
//                $data["needPay"] = $totalMoney[$key] + $data["deliverMoney"] - $data["scoreMoney"];//需缴费用 加运费 减去抵用的钱
//                $data["useScore"] = $data["useScore"];//本次交易使用的积分数
//                $data["scoreMoney"] = $data["scoreMoney"];//积分兑换的钱 完成交易 确认收货在给积分
//            }
////            if ($getuseScore > 0 and $config['isOpenScorePay'] == 1) {
////                //获取比例
////                $discountPrice = $totalMoney[$key];
////                $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
////                $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
////                $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
////                $scoreAmount = (int)$integralRateScore / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
////                $userScore = $userInfo['userScore'];
////                $userScoreAmount = (int)$userScore / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
////                if ($userScore < $integralRateScore) {
////                    $data["useScore"] = $userScore;
////                    $data["scoreMoney"] = $userScoreAmount;
////                } else {
////                    $data["useScore"] = $integralRateScore;
////                    $data["scoreMoney"] = $scoreAmount;
////                }
////                $data["needPay"] = $totalMoney[$key] + $data["deliverMoney"] - $data["scoreMoney"];//需缴费用 加运费 减去抵用的钱
////                $data["useScore"] = $data["useScore"];//本次交易使用的积分数
////                $data["scoreMoney"] = $data["scoreMoney"];//积分兑换的钱 完成交易 确认收货在给积分
////            }
//            //处理统一运费的问题
//            if ($config['setDeliveryMoney'] == 2) {
//                $handleAmount = (float)$totalMoney[$key] - $data["scoreMoney"];
//                $data["realTotalMoney"] = (float)$totalMoney[$key] - $data["scoreMoney"];//实际订单总金额 加运费 减去抵用的钱
//            } else {
//                $data["realTotalMoney"] = (float)$totalMoney[$key] + (float)$data["deliverMoney"] - $data["scoreMoney"];//实际订单总金额 加运费 减去抵用的钱
//                $handleAmount = (float)$totalMoney[$key] + (float)$data["deliverMoney"] - $data["scoreMoney"];//实际订单总金额 加运费 减去地用的钱
//            }
//            if ($checkCouponRes['code'] == 0) {
//                //使用优惠券后的金额
//                $data['realTotalMoney'] -= $checkCouponRes['data']['couponMoney'];
//                $data['couponId'] = $checkCouponRes['data']['couponId'];
//
//                $handleAmount -= $checkCouponRes['data']['couponMoney'];
//            }
//            $data['realTotalMoney'] = formatAmount($data['realTotalMoney']);
//            $shopInfo = $sm->getShopInfo($data["shopId"]);
//            $data["poundageRate"] = (!empty($shopInfo) && $shopInfo['commissionRate'] > 0) ? (float)$shopInfo['commissionRate'] : (float)$config['poundageRate'];//佣金比例
//            $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//            if ($config['setDeliveryMoney'] == 2) {
//                $appRealTotalMoney[$key] = $handleAmount;//获取订单金额
//                if ($payFrom == 3) {
//                    $data['realTotalMoney'] = $handleAmount;
//                }
//            } else {
//                $appRealTotalMoney[$key] = $data["realTotalMoney"];//获取订单金额
//            }
//            //如果是余额支付的话,判断用户余额是否充足
//            if ($payFrom == 3 && $userInfo['balance'] < $data['realTotalMoney']) {
//                M()->rollback();
//                unset($apiRet);
//                $apiRet = returnData(null, -1, 'error', '余额不足');
//                return $apiRet;
//            }
//            //如果是余额支付,扣除用户对应的余额
//            if ($payFrom == 3 && $userInfo['balance'] >= $data['realTotalMoney']) {
//                if ((float)$data['realTotalMoney'] <= 0) {
//                    $data['realTotalMoney'] = 0;
//                }
//                $balance = M('users')->where(['userId' => $userId])->getField('balance');
//                $saveData = [];
//                $saveData['balance'] = $balance - $data['realTotalMoney'];
//                $userEditRes = M('users')->where("userId='" . $userId . "'")->save($saveData);
//                if ($userEditRes === false) {
//                    M()->rollback();
//                    unset($apiRet);
//                    $apiRet = returnData(null, -1, 'error', '余额支付失败');
//                    return $apiRet;
//                }
//                $data['orderStatus'] = 0; //支付成功
//                $data['isPay'] = 1; //已支付
//                $data['pay_time'] = date('Y-m-d H:i:s');
//                //余额记录
//                if ($config['setDeliveryMoney'] == 1) {
//                    M('user_balance')->add(array(
//                        'userId' => $userId,
//                        'balance' => $data['realTotalMoney'],
//                        'dataSrc' => 1,
//                        'orderNo' => $orderNo,
//                        'dataRemarks' => "余额支付",
//                        'balanceType' => 2,
//                        'createTime' => date('Y-m-d H:i:s'),
//                        'shopId' => 0
//                    ));
//                    //支付通知
//                    $push = D('Adminapi/Push');
//                    $push->postMessage(7, $userId, $data['orderNo'], $shopId);
//                }
//            }
////            if ($data['realTotalMoney'] <= 0) {
////                //为了避免前端调用支付失败
////                $data['realTotalMoney'] = 0.01;
////            }
//            $data['lat'] = $addressInfo['lat'];
//            $data['lng'] = $addressInfo['lng'];
//            if ($payFrom == 4) {
//                //货到付款
//                $data['payType'] = 0;
//                $data['orderStatus'] = 0;
//                $data['isPay'] = 1;
//            }
//            //非常规订单
//            //用于判断非常规订单【0:不存在|1:存在】
//            if ($type == 1) {
//                //非常规订单
//                $data['orderType'] = 5;
//                //非常规订单配送时长累加
//                $unconventionality = $config['unconventionality'] * 60 * 60;//非常规订单配送时长
//                $requireTime = date("Y-m-d H:i:s", strtotime($requireTime) + $unconventionality);
//                $data['requireTime'] = $requireTime;
//            }
//            //写入订单
//            if ((float)$data['realTotalMoney'] <= 0 && $data['payFrom'] != 4) {
//                $data['orderStatus'] = 0;
//                $data['isPay'] = 1;
//                $data['pay_time'] = date('Y-m-d H:i:s');
//            }
//            if ((float)$data['realTotalMoney'] <= 0) {
//                $data['realTotalMoney'] = 0;
//            }
//            //订单商品延时
//            if (count($delayed_arr) > 0) {
//                array_multisort($delayed_arr, SORT_DESC);
//                $real_delayed = $delayed_arr[0] * 60;
//                if (!empty($data['requireTime'])) {
//                    $require_time = strtotime($data['requireTime']) + $real_delayed;
//                    $data['requireTime'] = date('Y-m-d H:i:s', $require_time);
//                }
//            }
//            $orderId[$key] = $orders->add($data);
//            $checkRequireTimeRes = $submitMod->checkRequireTime($orderId[$key]);
//            if ($checkRequireTimeRes['code'] == -1) {
//                M()->rollback();
//                return $checkRequireTimeRes;
//            }
//            $preSubmitEconomyAmount = $submitMod->preSubmitEconomyAmount($userId);//本次订单节省了多少钱
//            if ($isSelf) {//如果为自提订单 生成提货码
//                //生成提货码
//                $modUserSelfGoods = M('user_self_goods');
//                $insert = [];
//                $insert['orderId'] = $orderId[$key];
//                $insert['source'] = $orderId[$key] . $userId . $shopId;
//                $insert['userId'] = $userId;
//                $insert['shopId'] = $shopId;
//                $modUserSelfGoods->add($insert);
//            }
//            //建立订单记录
//            $content = '小程序下单成功，等待支付';
//            $logParams = [
//                'orderId' => $orderId[$key],
//                'logContent' => $content,
//                'logUserId' => $userId,
//                'logUserName' => '用户',
//                'orderStatus' => -2,
//                'payStatus' => 0,
//                'logType' => 0,
//                'logTime' => date('Y-m-d H:i:s'),
//            ];
//            if ($payFrom == 3) {
//                $logParams['orderStatus'] = 0;
//                $logParams['payStatus'] = 1;
//                $logParams['logContent'] = "小程序下单成功，余额支付成功";
//            }
//            if ((float)$data['realTotalMoney'] <= 0) {
//                $logParams['orderStatus'] = 0;
//                $logParams['payStatus'] = 1;
//                $logParams['logContent'] = "小程序下单成功";
//            }
//            M('log_orders')->add($logParams);
//
//            //使用积分
//            $users_service_module = new UsersServiceModule();
//            if ($getuseScore > 0 and $config['isOpenScorePay'] == 1) {
//                //减去用户当前所持有积分
////                $userTab->where("userId = {$userId}")->setDec('userScore', $data['useScore']);
//                //积分处理-start
//                $score = (int)$data['useScore'];
//                $users_id = $userId;
//                $reset_score_result = $users_service_module->deduction_users_score($users_id, $score, M());
//                if ($reset_score_result['code'] != ExceptionCodeEnum::SUCCESS) {
//                    M()->rollback();
//                    return returnData(null, -1, 'error', $reset_score_result['msg']);
//                }
//                //积分处理-end
//                //加上用户历史消费积分【用户收货后添加历史积分】
////                $userTab->where("userId = {$userId}")->setInc('userTotalScore', $data['useScore']);
//                //写入积分消费记录
//                $userScoreInsert = [];
//                $userScoreInsert['dataSrc'] = 1;//来源订单
//                $userScoreInsert['userId'] = $userId;//用户id
//                $userScoreInsert['score'] = $data['useScore'];//积分
//                $userScoreInsert["dataId"] = (int)$orderId[$key];
//                $userScoreInsert['dataRemarks'] = "抵用现金【原有积分{$userInfo['userScore']}】";
//                $userScoreInsert['scoreType'] = 2;
//                $userScoreInsert['createTime'] = date("Y-m-d H:i:s");
//                M('user_score')->add($userScoreInsert);
//            }
//            //将订单商品写入order_goods
//            $useScoreTotal = $data['useScore'];//本单使用的积分
//            $surplusScore = $useScoreTotal;//可以分配给商品的积分
//            foreach ($singleData as $singleKey => $singleVal) {
//                //判断限量购商品---进行数据替换
//                if ($singleVal['isLimitBuy'] == 1) {
//                    $goodsLimitCount = goodsTimeLimit($singleVal['goodsId'], $singleVal, null, $singleVal['goodsCnt']);
//                    if (empty($goodsLimitCount)) {
//                        $result[$key][$singleKey]["shopPrice"] = $singleVal["limitCountActivityPrice"];
//                        $singleVal['shopPrice'] = $singleVal["limitCountActivityPrice"];
//                        $singleVal['goodsStock'] = $singleVal["limitCount"];
//                        $result[$key][$singleKey]["isFlashSaleOk"] = 1;//用于判断是否是限量购商品
//                        $result[$key][$singleKey]["limit_buy_state"] = 1;//是否满足限量活动(-1:不满足 1:满足)
//                        $singleVal['isFlashSaleOk'] = 1;
//                        //加入商品限量购购买记录-start
//                        $limit_log_params = array(
//                            'goodsId' => $singleVal['goodsId'],
//                            'number' => $singleVal['goodsCnt'],
//                        );
//                        $limit_log_result = $goods_service_module->addLimitGoodsBuyLog($userId, $limit_log_params, M());
//                        if ($limit_log_result['result'] != ExceptionCodeEnum::SUCCESS) {
//                            M()->rollback();
//                            return returnData(false, -1, 'error', '限量购购买记录添加失败');
//                        }
//                        //加入商品限量购购买记录-end
//                    }
//                    $singleVal['isLimitBuy'] = 0;//重置限量状态
//                }
//                //判断限时购商品---进行数据替换
//                if ($singleVal['isFlashSale'] == 1) {
//                    $getGoodsFlashSale = getGoodsFlashSale($singleVal['goodsId']);
//                    if ($getGoodsFlashSale['code'] == 1) {
//                        $goodsTimeLimit = goodsTimeLimit($singleVal['goodsId'], $singleVal, $getGoodsFlashSale, $singleVal['goodsCnt']);
//                        if (empty($goodsTimeLimit)) {
//                            $result[$key][$singleKey]["shopPrice"] = $getGoodsFlashSale["activityPrice"];
//                            $result[$key][$singleKey]['marketPrice'] = $getGoodsFlashSale['marketPrice'];
//                            $result[$key][$singleKey]['minBuyNum'] = $getGoodsFlashSale['minBuyNum'];
//                            $result[$key][$singleKey]['goodsStock'] = $getGoodsFlashSale['activeInventory'];
//                            $singleVal['shopPrice'] = $getGoodsFlashSale["activityPrice"];
//                            $singleVal['goodsStock'] = $getGoodsFlashSale["activeInventory"];
//                            $result[$key][$singleKey]["isFlashSaleOk"] = 1;
//                            $result[$key][$singleKey]["flash_sale_state"] = 1;//是否满足限时活动(-1:不满足 1:满足)
//                            $singleVal['isFlashSaleOk'] = 1;
//                        }
//                    }
//                    $singleVal['isFlashSale'] = 0;//重置限时状态
//                }
//                $orderGoods = [];
//                $orderGoods["orderId"] = $orderId[$key];
//                $orderGoods["goodsId"] = $singleVal["goodsId"];
//                $orderGoods["goodsNums"] = $singleVal["goodsCnt"];
//                $orderGoods["goodsPrice"] = $result[$key][$singleKey]["shopPrice"];
//                $orderGoods["goodsAttrId"] = $singleVal["goodsAttrId"];
//                $orderGoods["remarks"] = '';
//                if (is_null($singleVal["remarks"])) {
//                    $orderGoods["remarks"] = '';
//                } else {
//                    //验证下前端传过来的只能备注是否正确
//                    if (!empty($singleVal['IntelligentRemark'])) {
//                        $IntelligentRemarkArr = explode('@', $singleVal['IntelligentRemark']);
//                        if (in_array($singleVal['remarks'], $IntelligentRemarkArr)) {
//                            $orderGoods['remarks'] = $singleVal['remarks'];
//                        }
//                    }
//                }
//                //2019-6-14 start
//                $getGoodsAttrPrice = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                $orderGoods["goodsAttrName"] = $getGoodsAttrPrice['goodsAttrName'];
//                if ($singleVal["skuId"] > 0) {
//                    $getGoodsAttrPrice = getGoodsSkuPrice($userId, $singleVal['skuId'], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                    $orderGoods['skuSpecAttr'] = $getGoodsAttrPrice['goodsAttrName'];
//                }
//                $orderGoods["skuId"] = $singleVal["skuId"];
//                $orderGoods["goodsPrice"] = $getGoodsAttrPrice["goodsPrice"];
//                $goodsInfo = $goodsTab->where(['goodsId' => $singleVal["goodsId"]])->find();
//                $systemSkuSpec = $systemTab->where(['skuId' => $singleVal["skuId"]])->find();
//                //如果是限时限量购就不走会员
//                if (($userInfo['expireTime'] > date('Y-m-d H:i:s')) && empty($result[$key][$singleKey]["isFlashSaleOk"])) {//是会员
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $orderGoods["goodsPrice"] = $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $orderGoods["goodsPrice"] = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                }
//                //2019-6-14 end
//                $orderGoods["goodsName"] = $singleVal["goodsName"];
//                $orderGoods["goodsThums"] = $singleVal["goodsThums"];
//                //2020-6-04 增加订单商品优惠券抵扣金额和积分抵扣金额 start
//                //上面的逻辑就不理了,太多了,头疼
//                $orderGoods['couponMoney'] = 0;//优惠券抵扣金额
//                $orderGoods['scoreMoney'] = 0;//积分抵扣金额
//                $singleGoodsTotal = bc_math($orderGoods['goodsPrice'], $orderGoods['goodsNums'], 'bcmul');
//                if (!empty($cuid)) {
//                    $userCouponWhere = [];
//                    $userCouponWhere['userId'] = $userId;
//                    $userCouponWhere['couponId'] = $cuid;
//                    $couponInfo = $submitMod->getCouponInfo($userCouponWhere);
//                    $msg = '';
//                    $checkArr = array(
//                        'couponId' => $couponInfo['couponId'],
//                        'good_id_arr' => $orderGoods['goodsId'],
//                    );
//                    $checkRes = check_coupons_auth($checkArr, $msg);
//                    if ($checkRes == true) {
//                        //公式:优惠券金额[A] * (商品价格/订单商品金额)[B] - 商品价格[C]
//                        $A = $couponInfo['couponMoney'];
//                        $B = bc_math($singleGoodsTotal, $data['totalMoney'], 'bcdiv');
//                        $C = $singleGoodsTotal;
//                        $goodsCouponDiscount = bc_math(bc_math($A, $B, 'bcmul'), $C, 'bcsub');
//                        $couponMoney = bc_math($goodsCouponDiscount, $singleGoodsTotal, 'bcadd');
//                        if ($couponMoney > 0) {
//                            //精度损失问题由用户来承担
//                            $couponMoneyArr = explode('.', $couponMoney);
//                            if (!empty($couponMoneyArr[1])) {
//                                $xiaoshu = $couponMoneyArr[1];
//                                if (strlen($xiaoshu) > 2) {
//                                    $xiaoshu = bc_math(substr($xiaoshu, 0, 2), 1, 'bcadd') / 100;
//                                    $couponMoney = $couponMoneyArr[0] + $xiaoshu;
//                                }
//                            }
//                            $orderGoods['couponMoney'] = $couponMoney;
//                        }
//                    }
//                }
//                if ($getuseScore > 0 and $config['isOpenScorePay'] == 1) {
//                    //获取比例
//                    $discountPrice = $singleGoodsTotal;
//                    $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//                    $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//                    $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//当前商品可以抵扣的积分
//                    if ($surplusScore > 0) {
//                        if ($integralRateScore >= $surplusScore) {
//                            $integralRateScore = $surplusScore;
//                            $orderGoods['scoreMoney'] = (float)$integralRateScore / ((float)$scoreScoreCashRatio[0] * (float)$scoreScoreCashRatio[1]);
//                            $surplusScore = abs(($surplusScore - $integralRateScore));//剩余可分配积分
//                        } else {
//                            $orderGoods['scoreMoney'] = (float)$integralRateScore / (float)$scoreScoreCashRatio[0] * (float)$scoreScoreCashRatio[1];
//                            $surplusScore = abs(($surplusScore - $integralRateScore));
//                        }
//                    }
//                }
//                //2020-6-04 增加订单商品优惠券抵扣金额和积分抵扣金额 end
//                //增加购买时商品的类型 1:普通商品 2:限量商品 3:限时商品
//                $orderGoods['goods_type'] = 1;
//                $singleVal['goods_type'] = 1;
//                if ($result[$key][$singleKey]["limit_buy_state"] == 1) {
//                    $orderGoods['goods_type'] = 2;
//                    $singleVal['goods_type'] = 2;
//                }
//                if ($result[$key][$singleKey]["flash_sale_state"] == 1) {
//                    $orderGoods['goods_type'] = 3;
//                    $singleVal['goods_type'] = 3;
//                }
//                //商品奖励积分
//                $orderGoods['goodsScore'] = getRewardsIntegral($singleVal,$singleGoodsTotal);
//                $orderGoodsTab->add($orderGoods);
//                //减去对应商品数量
//                //$updateGoodsStockResult = updateGoodsStockByRedis($singleVal['goodsId'], $singleVal['goodsCnt']);
//                $updateGoodsStockResult = reduceGoodsStockByRedis($singleVal, M());//减去商品库存
//                if ($updateGoodsStockResult['code'] != ExceptionCodeEnum::SUCCESS) {
//                    M()->rollback();
//                    return $updateGoodsStockResult;
//                }
//                //限制商品下单次数
//                limitGoodsOrderNum($singleVal["goodsId"], $userId);
//                //清空对应的购物车商品
//                $cartWhere = [];
//                $cartWhere['cartId'] = $singleVal['cartId'];
//                $cart->where($cartWhere)->delete();
//                //写入秒杀记录表
//                if ($singleVal["isShopSecKill"] == 1) {
//                    $killTab = M('goods_secondskilllimit');
//                    for ($kii_i = 0; $kii_i < $orderGoods["goodsNums"]; $kii_i++) {
//                        //一件商品一条数据
//                        $killData['goodsId'] = $singleVal['goodsId'];
//                        $killData['userId'] = $userId;
//                        $killData['endTime'] = $singleVal["ShopGoodSecKillEndTime"];
//                        $killData['orderId'] = $orderId[$key];
//                        $killData['state'] = 1;
//                        $killData['addtime'] = date('Y-m-d H:i:s', time());
//                        $killTab->add($killData);
//                    }
//                }
//            }
//            unset($singleKey);
//            unset($singleVal);
//            //更新优惠券状态为已使用 - 修改后的-----不要删除【可能后期需要修改】
////            if (!empty($cuid)) {
////                $couponUserInsert = [];
////                $couponUserInsert['orderNo'] = $orderNo;
////                $couponUserInsert['couponStatus'] = 0;
////                M('coupons_users')->where("id = " . $cuid)->save($couponUserInsert);
////            }
////            //更新运费券状态为已使用 - 修改后的---------------------start----------
////            if (!empty($wuCouponId)) {
////                $couponUserInsert = [];
////                $couponUserInsert['orderNo'] = $orderNo;
////                $couponUserInsert['couponStatus'] = 0;
////                M('coupons_users')->where("id = " . $wuCouponId)->save($couponUserInsert);
////            }
//            //-------------------------------end------------------------------------
//            //建立订单提醒
//            $orderRemindInsert = [];
//            $orderRemindInsert["orderId"] = $orderId[$key];
//            $orderRemindInsert["shopId"] = $shopId;//店铺id
//            $orderRemindInsert["userId"] = $userId;
//            $orderRemindInsert["createTime"] = date("Y-m-d H:i:s");
//            $orderRemindRes = $orderRemindsTab->add($orderRemindInsert);
//            // 获取生成的所有订单号 返回给小程序支付
//            $wxorderNo[$key] = $orderNo;
//            // --- 生成订单成功,推送消息 --- @author liusijia --- start ---
//            if ($orderId[$key] > 0) {
//                $push = D('Adminapi/Push');
//                $push->postMessage(6, $userId, $orderNo, $shopId);
//            }
//            // --- 生成订单成功,推送消息 --- @author liusijia --- end ---
//            //添加报表-start
//            addReportForms($orderId[$key], 1, array(), M());
//            //添加报表-end
//        }
//        if ($orderRemindRes) {
//            unset($statusCode);
//            //更新优惠券状态为已使用 - 修改后的-------后加---后期可能要修改---------------------------------------------
//            if (!empty($cuid)) {
//                $couponUserInsert = [];
//                $couponUserInsert['orderNo'] = $orderNo;
//                $couponUserInsert['couponStatus'] = 0;
//                M('coupons_users')->where("id = " . $cuid)->save($couponUserInsert);
//            }
//            //更新运费券状态为已使用 - 修改后的---------------------start----------
//            if (!empty($wuCouponId)) {
//                $couponUserInsert = [];
//                $couponUserInsert['orderNo'] = $orderNo;
//                $couponUserInsert['couponStatus'] = 0;
//                M('coupons_users')->where("id = " . $wuCouponId)->save($couponUserInsert);
//            }
//            //----------------------------------------------------------------------------------
//            $appRealTotalMoney = array_sum($appRealTotalMoney);
//            if ($config['setDeliveryMoney'] == 2) {
//                if ($appRealTotalMoney >= $config['deliveryFreeMoney']) {
//                    $config['deliveryMoney'] = 0;
//                }
//                if ($isSelf == 1 || !empty($wuCouponId)) {
//                    $config['deliveryMoney'] = 0;
//                }
//                $appRealTotalMoney += $config['deliveryMoney'];
//                if ($appRealTotalMoney < $config['deliveryFreeMoney']) {
//                    if ($payFrom == 3) {
//                        $balance = M('users')->where(['userId' => $userId])->getField('balance');
//                        $saveData = [];
//                        $saveData['balance'] = $balance - $config['deliveryMoney'];
//                        $userEditRes = M('users')->where("userId='" . $userId . "'")->save($saveData);
//
//                        if ($userEditRes === false) {
//                            M()->rollback();
//                            unset($apiRet);
//                            $apiRet = returnData(null, -1, 'error', '余额支付失败');
//                            return $apiRet;
//                        }
//                        $orderTab = M('orders');
//                        $firstOrderInfo = $orderTab->where(['orderNo' => $wxorderNo[0]])->field('orderId,realTotalMoney')->find();
//                        $saveData = [];
//                        $saveData['deliverMoney'] = $config['deliveryMoney'];
//                        $saveData['realTotalMoney'] = bc_math($firstOrderInfo['realTotalMoney'], $config['deliveryMoney'], 'bcadd', 2);
//                        $orderTab->where(['orderNo' => $wxorderNo[0]])->save($saveData);
//                        $balanceTab = M('user_balance');
//                        $balanceLog = $balanceTab->where(['orderNo' => $wxorderNo[0]])->find();
//                        $balaceData = [];
//                        $balaceData['balance'] = bc_math($balanceLog['balance'], $config['deliveryMoney'], 'bcadd', 2);
//                        $balanceTab->where(['balanceId' => $balanceLog['balanceId']])->save($balaceData);
//                        M('user_balance')->add(array(
//                            'userId' => $userId,
//                            'balance' => $saveData['realTotalMoney'],
//                            'dataSrc' => 1,
//                            'orderNo' => $orderNo,
//                            'dataRemarks' => "余额支付",
//                            'balanceType' => 2,
//                            'createTime' => date('Y-m-d H:i:s'),
//                            'shopId' => 0
//                        ));
//                    }
//                }
//            }
//            $statusCode["appRealTotalMoney"] = strencode($appRealTotalMoney);//多个订单总金额 单位元
//            //$statusCode["orderNo"] = base64_encode(json_encode($wxorderNo));//订单号
//            $statusCode["orderNo"] = strencode(implode("A", $wxorderNo));//订单号  多个订单号用 A隔开
//            $statusCode['orderId'] = strencode(implode("A", $orderId));//订单号  多个订单号用 A隔开
//            $statusCode['economyAmount'] = $preSubmitEconomyAmount;//会员节省了多少钱
//            //写入订单合并表
//            $orderMergeInsert = [];
//            $orderMergeInsert['orderToken'] = md5(implode("A", $wxorderNo));
//            $orderMergeInsert['value'] = implode("A", $wxorderNo);
//            $orderMergeInsert['realTotalMoney'] = $appRealTotalMoney;
//            $orderMergeInsert['createTime'] = time();
//            M('order_merge')->add($orderMergeInsert);
//            $statusCode['orderToken'] = $orderMergeInsert['orderToken'];
//            $submitMod->updateOrderToken($wxorderNo, $orderMergeInsert['orderToken']);
//            //地推相关
//            $this->addPullNewAmountLog($userId, $orderMergeInsert['orderToken']);
//            //积分处理-start
//            $users_id = $userId;
//            $score = 0;
//            $result = $users_service_module->deduction_users_score($users_id, $score, M());
//            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
//                M()->rollback();
//                return returnData(null, -1, 'error', $result['msg']);
//            }
//            //积分处理-end
//            M()->commit();
//            unset($apiRet);
//            $apiRet = returnData($statusCode, 0, 'success', '提交成功');
//            //记录变量 测试代码 记得删除
//            //errorLog(print_r(get_defined_vars(),true));
//            return $apiRet;
//        } else {
//            M()->rollback();
//            unset($apiRet);
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            //记录变量 测试代码 记得删除
//            //errorLog(print_r(get_defined_vars(),true));
//            return $apiRet;
//        }
//    }

    /**
     * 用户成功下单记录地推收益明细
     * @param int $userId 受邀人id
     * @param string $orderToken orderToken
     * */
    public function addPullNewAmountLog(int $userId, string $orderToken)
    {
        $usersTab = M('users');
        $userInfo = $usersTab->where(['userId' => $userId])->find();//受邀人信息
        //如果用户不是首次下单,就不发放地推奖励了
        if (empty($userInfo) || $userInfo['firstOrder'] != 1) {
            return false;
        }
        $userPhone = $userInfo['userPhone'];
        $invitationLogTab = M('distribution_invitation invitation');
        $where = [];
        $where['invitation.userPhone'] = $userPhone;
        $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
        $field .= ',users.balance';
        $invitationInfo = $invitationLogTab
            ->join("left join wst_users users on users.userId=invitation.userId")
            ->field($field)
            ->where($where)
            ->find();
        if (empty($invitationInfo) || $invitationInfo['dataType'] != 2) {
            return false;
        }
        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $invitationInfo['userId'];
        $invitationUser = $usersTab->where($where)->field('pullNewPermissions,pullNewRegister,pullNewOrder')->find();//邀请人信息
        //用支付成功后,发放邀请奖励,状态为待入账
        if ($invitationUser['pullNewPermissions'] == 1) {
            $configs = $GLOBALS['CONFIG'];
            $pullNewOrder = $configs['pullNewOrder'];//奖励规则-用户成功下单
            //如果用户开启了拉新权限,但是没有配置奖励,而平台商城信息却配置了,则采用商品信息中的奖励规则,否则采用用户中的配置规则
            if ($invitationUser['pullNewOrder'] > 0) {
                $pullNewOrder = $invitationUser['pullNewOrder'];
            }
            if ($pullNewOrder > 0) {
                $date = date('Y-m-d H:i:s', time());
                $amountLog = [];
                $amountLog['userId'] = $userId;
                $amountLog['inviterId'] = $invitationInfo['userId'];
                $amountLog['dataType'] = 2;
                $amountLog['orderToken'] = $orderToken;
                $amountLog['amount'] = $pullNewOrder;
                $amountLog['status'] = 0;
                $amountLog['createTime'] = $date;
                $amountLog['updateTime'] = $date;
                $insertAmountLogRes = M('pull_new_amount_log')->add($amountLog);
                if (!$insertAmountLogRes) {
                    return false;
                }
            }
        }
        $saveData = [];
        $saveData['firstOrder'] = -1;
        $usersTab->where(['userId' => $userId])->save($saveData);
        return true;
    }

    /**
     * 统一下单支付
     * @param string memberToken
     * @param int payType PS:支付方式(1:支付宝|2:微信)
     * @param int dataFrom PS:来源(0:商城 1:微信 2:手机版 3:app 4：小程序)
     * @param int dataType PS:功能(1:微信下单支付|2:微信重新支付|3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
     * @param string openId
     * */
    public function unifiedOrder($param)
    {
        $payType = $param['payType'];
        $dataType = $param['dataType'];
        if ($payType == 1) {
            $apiRet = returnData(null, -1, 'error', '暂不支持支付宝支付');
            return $apiRet;
        } elseif ($payType == 2) {
            switch ($dataType) {
                case 1://普通订单
                    $res = $this->orderUnifiedOrder($param);
                    break;
                case 2://微信重新支付
                    $res = $this->againUnifiedOrder($param);
                    break;
                case 3://微信余额充值
                    $res = $this->orderUnifiedOrder($param);
                    break;
                case 4://开通绿卡
                    $res = $this->orderUnifiedOrder($param);
                    break;
                case 5://优惠券购买(加量包)
                    $res = $this->orderUnifiedOrder($param);
                    break;
            }
        }
        return $res;
    }

    /**
     *生成32位字符,并保存附加参数
     * @param jsonString $val
     * */
    public function createNotifyLog($val)
    {
        $requestJson = json_decode($val,true);
        $add['userId'] = $requestJson['userId'];
        $add['type'] = $val['dataType'];//数据类型(1:微信下单支付|2:微信重新支付|3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
        $add['requestJson'] = $val['requestJson'];
        $add['orderToken'] = $val['orderToken'];
        $add['requestTime'] = date('Y-m-d H:i:s', time());
        $tab = M('notify_log');
        $autoId = $tab->add($add);
        $token = '';
        if ($autoId) {
            $token = str_pad($autoId, 32, "0", STR_PAD_LEFT) . time();
            $tab->where(["id" => $autoId])->save(['key' => md5($token)]);
        }
        if (!empty($token)) {
            $token = md5($token);
        }
        return $token;
    }


    /**
     * 普通订单
     * @param array $param
     * */
    public function orderUnifiedOrder($param)
    {
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        $userId = $param['userId'];
        $mergeTab = M('order_merge');
        $mergeInfo = $mergeTab->where(['orderToken' => $dataValue['orderToken']])->find();
        $orderNoStr = $mergeInfo['value'];
        $orderNoArr = explode('A', $orderNoStr);
        $where = [];
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $where['orderNo'] = ['IN', $orderNoArr];
        $orderList = M('orders')->where($where)->select();
        if (count($orderList) < count($orderNoArr)) {
            $apiRet = returnData(null, -1, 'error', '支付失败，非法数据请求', '非法数据请求，订单合并表数据和实际订单不符');
            return $apiRet;
        }
        $orderToken = $dataValue['orderToken'];
        $payAmount = $mergeInfo['realTotalMoney'];
        M()->startTrans();
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $userId;
        $attach['openId'] = $param['openId'];
        $attach['orderToken'] = $orderToken;
        $attach['amount'] = $payAmount;
        $attach['payType'] = $param['payType'];
        $attach['dataFrom'] = $param['dataFrom'];
        $param['requestJson'] = json_encode($attach);
        $param['orderToken'] = $orderToken;
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '支付失败，数据异常');
            return $apiRet;
        }
        $payModule = new PayModule();
        if ($param['payType'] == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($param['payType'] == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['orderToken'] = $orderToken;
        $resData['notifySign'] = $sign;
        $resData['realTotalMoney'] = formatAmount($payAmount);//实付金额
        $resData['timestamp'] = time();//时间戳
        M()->commit();
        return returnData($resData, 0, 'success', '生成成功');
    }

    /**
     * 重新支付
     * @param array $param
     * */
    /*public function againUnifiedOrder($param){
        $config = $GLOBALS['CONFIG'];
        $dataValue = json_decode($param['dataValue'],true);//业务参数
        $userId = $param['userId'];
        $mergeTab = M('order_merge');
        $mergeInfo = $mergeTab->where(['orderToken'=>$dataValue['orderToken']])->find();
        $orderNoStr = $mergeInfo['value'];
        $orderNoArr = explode('A',$orderNoStr);
        $where = [];
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $where['orderNo'] = ['IN',$orderNoArr];
        $orderList = M('orders')->where($where)->select();
        if(count($orderList) < count($orderNoArr)){
            $apiRet = returnData(null,-1,'error','支付失败，非法数据请求','非法数据请求，订单合并表数据和实际订单不符');
            return $apiRet;
        }
        $orderToken = $dataValue['orderToken'];
        $payAmount = 0;
        $time = time();
        //订单超时时间
        $rePayTime = $config['rePayTime'];
        if(empty($rePayTime)){
            $rePayTime = 5;
        }
        $overTime = $rePayTime * 60;//订单超时时间
        foreach ($orderList as $key=>$value){
            $checkOverTime = strtotime($value['createTime']) + $overTime;
            if($checkOverTime < time()){
                $apiRet = returnData(null,-1,'error','订单已超时，请重新下单');
                return $apiRet;
            }
            $payAmount += $value['realTotalMoney'];
            if($config['setDeliveryMoney'] == 2){
                if($orderList[$key]['realTotalMoney'] < $config['deliveryFreeMoney']){
                    $orderList[$key]['realTotalMoney'] = $orderList[$key]['realTotalMoney']+$orderList[$key]['setDeliveryMoney'];
                    $orderList[$key]['deliverMoney'] = $orderList[$key]['setDeliveryMoney'];
                    $payAmount += $orderList[$key]['deliverMoney'];
                }
            }
            if($value['isPay'] == 1){
                $apiRet = returnData(null,-1,'error','订单已支付，不能重复提交支付');
                return $apiRet;
            }
            //重新支付需要判断是否过了锁定期,先给个30秒吧
            $orderCreateTime = strtotime($value['createTime']);
            $diffTime = $time - $orderCreateTime;
            $lockTime = 30;//锁定时间
            if($diffTime < $lockTime){
                $tipTime = $lockTime - $diffTime;
                $apiRet = returnData(null,-1,'error','请勿频繁发送请求，解除锁定时间还剩余'.$tipTime.'秒');
                return $apiRet;
            }
        }
        M()->startTrans();
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $userId;
        $attach['orderToken'] = $orderToken;
        $param['requestJson'] = json_encode($attach);
        $param['orderToken'] = $attach['orderToken'];
        $sign = $this->createNotifyLog($param);
        if(empty($sign)){
            $apiRet = returnData(null,-1,'error','支付失败，数据异常');
            return $apiRet;
        }
        //构建参数
        $payParam['openId'] = $param['openId'];
        $payParam['orderNo'] = $sign;
        $payParam['amount'] = $payAmount;
        $payParam['attach'] = '';
        $payParam['dataFrom'] = $param['dataFrom'];
        $payParam['payType'] = $param['payType'];
        $payRes =  unifiedOrder($payParam);//统一下单支付
        if($payRes['result_code'] !== 'SUCCESS'){
            M()->rollback();
            $msg = $payRes['err_code_des'];
            if(!isset($payRes['err_code_des'])){
                $msg = $payRes['return_msg'];
            }
            return returnData(null,-1,'error','支付失败，'.$msg);
        }
        $payRes['realTotalMoney'] = $payAmount;
        $payRes['orderToken'] = $orderToken;
        $payRes['notifySign'] = $sign;
        M()->commit();
        return returnData($payRes,0,'success','生成成功');
    }*/

    public function againUnifiedOrder($param)
    {
        $config = $GLOBALS['CONFIG'];
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        $orderId = $dataValue['orderId'];
        $userId = $param['userId'];
        $orderTab = M('orders');
        $where = [];
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $where['orderId'] = $orderId;
        $orderInfo = $orderTab->where($where)->find();
        if (!$orderInfo) {
            return returnData(null, -1, 'error', '支付失败，订单不存在', '支付失败，订单不存在');
        }
        if ($orderInfo['orderType'] == 2) {
            return returnData(null, -1, 'error', '支付失败，拼团订单不允许重新支付', '支付失败，拼团订单不允许重新支付');
        }

        $payAmount = 0;
        $time = time();
        //订单超时时间
        $rePayTime = $config['rePayTime'];
        if (empty($rePayTime)) {
            $rePayTime = 5;
        }
        $overTime = $rePayTime * 60;//订单超时时间
        $checkOverTime = strtotime($orderInfo['createTime']) + $overTime;
        if ($checkOverTime < time()) {
            $apiRet = returnData(null, -1, 'error', '订单已超时，请重新下单');
            return $apiRet;
        }
//        $payAmount += $orderInfo['realTotalMoney'];
        $payAmount = bc_math($payAmount, $orderInfo['realTotalMoney'], 'bcadd', 2);
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($orderInfo['realTotalMoney'] < $config['deliveryFreeMoney']) {
                $orderInfo['realTotalMoney'] = $orderInfo['realTotalMoney'] + $orderInfo['setDeliveryMoney'];
                $orderInfo['deliverMoney'] = $orderInfo['setDeliveryMoney'];
//                $payAmount += $orderInfo['deliverMoney'];
                $payAmount = bc_math($payAmount, $orderInfo['deliverMoney'], 'bcadd', 2);
            }
        } else {
            if ($orderInfo['isPay'] != 1 && $orderInfo['setDeliveryMoney'] > 0) {
                $orderInfo['realTotalMoney'] = $orderInfo['realTotalMoney'] + $orderInfo['setDeliveryMoney'];
                $orderInfo['deliverMoney'] = $orderInfo['setDeliveryMoney'];
//                $payAmount += $orderInfo['deliverMoney'];
                $payAmount = bc_math($payAmount, $orderInfo['deliverMoney'], 'bcadd', 2);
            }
        }
        if ($orderInfo['isPay'] == 1) {
            $apiRet = returnData(null, -1, 'error', '订单已支付，不能重复提交支付');
            return $apiRet;
        }
        //重新支付需要判断是否过了锁定期,先给个30秒吧
        $orderCreateTime = strtotime($orderInfo['createTime']);
        $diffTime = $time - $orderCreateTime;
        $lockTime = 30;//锁定时间
        if ($diffTime < $lockTime) {
            $tipTime = $lockTime - $diffTime;
            $apiRet = returnData(null, -1, 'error', '请勿频繁发送请求，解除锁定时间还剩余' . $tipTime . '秒');
            return $apiRet;
        }

        //写入订单合并表
        $orderToken = md5($orderInfo['orderNo'] . $userId . $orderId . time() . uniqid());
        $orderMergeInsert = [];
        $orderMergeInsert['orderToken'] = $orderToken;
        $orderMergeInsert['value'] = $orderInfo['orderNo'];
        $orderMergeInsert['realTotalMoney'] = $payAmount;
        $orderMergeInsert['createTime'] = time();
        $insertMerge = M('order_merge')->add($orderMergeInsert);
        if ($insertMerge) {
            $updateOrderToken = [];
            $updateOrderToken['orderToken'] = $orderToken;
            $orderTab->where(['orderId' => $orderId])->save($updateOrderToken);
        } else {
            $apiRet = returnData(null, -1, 'error', '支付失败');
            return $apiRet;
        }

        M()->startTrans();

        $attach = [];//附加参数,用于回调
        $attach['userId'] = $userId;
        $attach['openId'] = $param['openId'];
        $attach['orderToken'] = $orderToken;
        $attach['amount'] = $payAmount;
        $attach['payType'] = $param['payType'];
        $attach['dataFrom'] = $param['dataFrom'];
        $param['requestJson'] = json_encode($attach);
        $param['orderToken'] = $orderToken;
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '支付失败，数据异常');
            return $apiRet;
        }
        $payModule = new PayModule();
        if ($param['payType'] == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($param['payType'] == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['orderToken'] = $orderToken;
        $resData['notifySign'] = $sign;
        $resData['realTotalMoney'] = formatAmount($payAmount);//实付金额
        $resData['timestamp'] = time();//时间戳
        M()->commit();
        return returnData($resData, 0, 'success', '生成成功');
    }


    /**
     * 余额充值
     * @param array $param
     * */
    public function balanceUnifiedOrder($param)
    {
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        //生成统一下单记录
        M()->startTrans();
        if ($dataValue['amount'] <= 0) {
            $apiRet = returnData(null, -1, 'error', '充值失败，充值金额不正确');
            return $apiRet;
        }
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $param['userId'];
        $attach['amount'] = $dataValue['amount'];
        $attach['openId'] = $param['openId'];
        $attach['payType'] = $param['payType'];
        $attach['dataFrom'] = $param['dataFrom'];
        $param['requestJson'] = json_encode($attach);
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '充值失败，数据异常');
            return $apiRet;
        }
        $payModule = new PayModule();
        if ($param['payType'] == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($param['payType'] == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['notifySign'] = $sign;
        $resData['amount'] = formatAmount($dataValue['amount']);//实付金额
        $resData['timestamp'] = time();//时间戳
        M()->commit();
        return returnData($resData, 0, 'success', '充值成功');
    }

    /**
     * 开通绿卡
     * @param array $param
     * */
    public function buySetMealUnifiedOrder($param)
    {
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        $tab = M('set_meal');
        $where = [];
        $where['smId'] = $dataValue['smId'];
        $where['isEnable'] = 1;
        $where['smFlag'] = 1;
        $setMealInfo = $tab->where($where)->find();
        if (!$setMealInfo) {
            $apiRet = returnData(null, -1, 'error', '开通绿卡失败，套餐已被下架或删除');
            return $apiRet;
        }
        //生成统一下单记录
        M()->startTrans();
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $param['userId'];
        $attach['smId'] = $setMealInfo['smId'];
        $attach['amount'] = $setMealInfo['money'];
        $attach['openId'] = $param['openId'];
        $attach['payType'] = $param['payType'];
        $attach['dataFrom'] = $param['dataFrom'];
        $param['requestJson'] = json_encode($attach);
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '开通绿卡失败，数据异常');
            return $apiRet;
        }
        $payModule = new PayModule();
        if ($param['payType'] == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($param['payType'] == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['smId'] = $dataValue['smId'];
        $resData['notifySign'] = $sign;
        $resData['timestamp'] = time();//时间戳
        M()->commit();
//        $push = D('Adminapi/Push');
//        $push->postMessage(1, $param['userId']);
        return returnData($resData, 0, 'success', '开通绿卡成功');
    }

    /**
     * 会员优惠券购买（加量包）
     * @param array $param
     * */
    public function buyCouponUnifiedOrder($param)
    {
        $dataValue = json_decode($param['dataValue'], true);//业务参数
        $userInfo = M('users')->where(['userId' => $param['userId'], 'userFlag' => 1])->find();
        //判断会员是否已过期
        if (!$userInfo || $userInfo['expireTime'] < date('Y-m-d H:i:s')) {
            $apiRet = returnData(null, -1, 'error', '购买失败，当前会员已过期，请先去购买会员套餐');
            return $apiRet;
        }
        //验证加量包数据
        $where = [
            'cs.csId' => $dataValue['csId'],//wst_coupon_set表中的主键
            'cs.csFlag' => 1,
            'c.couponType' => 5,
            'c.dataFlag' => 1,
            'c.type' => 1
        ];
        $couponsetInfo = M('coupon_set as cs')->join("wst_coupons as c on cs.couponId = c.couponId")->where($where)->field('cs.csId,cs.couponId,cs.num,cs.nprice')->find();
        if (empty($couponsetInfo) || $couponsetInfo['num'] <= 0) {
            $apiRet = returnData(null, -1, 'error', '购买失败，加量包或优惠券异常');
            return $apiRet;
        }
        //生成统一下单记录
        M()->startTrans();
        $attach = [];//附加参数,用于回调
        $attach['userId'] = $param['userId'];
        $attach['csId'] = $couponsetInfo['csId'];
        $attach['amount'] = $couponsetInfo['nprice'];
        $attach['openId'] = $param['openId'];
        $attach['payType'] = $param['payType'];
        $attach['dataFrom'] = $param['dataFrom'];
        $param['requestJson'] = json_encode($attach);
        $sign = $this->createNotifyLog($param);
        if (empty($sign)) {
            $apiRet = returnData(null, -1, 'error', '购买失败，数据异常');
            return $apiRet;
        }
        $payModule = new PayModule();
        if ($param['payType'] == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($param['payType'] == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['csId'] = $dataValue['csId'];
        $resData['notifySign'] = $sign;
        $resData['timestamp'] = time();//时间戳
        M()->commit();
        return returnData($resData, 0, '购买成功');
    }

    //商城分类 获取一级分类下所有商品
    public function ShopTypeOneGoods($userId, $goodsCatId1)
    {
        $data = S("niao_app_ShopTypeOneGoods_{$goodsCatId1}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['isMembershipExclusive'] = 0;
                }
            }

            $where["isSale"] = 1;
            $where["goodsCatId1"] = $goodsCatId1;
            $where["goodsStatus"] = 1;
            $where["goodsFlag"] = 1;
//            $where["isFlashSale"] = 0;//是否限时
//            $where["isLimitBuy"] = 0;//是否限量购
            $mod = M("goods")->where($where)->field(array("goodsId", "goodsThums", "goodsName", "shopPrice", "goodsStock", "isDistribution", "firstDistribution", "SecondaryDistribution", "markIcon", "isNewPeople"))->select();
            $mod = handleNewPeople($mod, $userId);
            $mod = rankGoodsPrice($mod); //商品等级价格
            S("niao_app_ShopTypeOneGoods_{$goodsCatId1}", $mod, C("niao_app_ShopTypeOneGoods_cache_time"));
            return $mod;
        }
        return $data;
    }

    //预售订单处理-首付款

    /******
     *
     * $funData['cash_fee'] = $funData['cash_fee'];//现金支付金额
     * $funData['out_trade_no'] = $funData['out_trade_no'];//商户订单号
     * $funData['transaction_id'] = $funData['transaction_id'];//微信支付订单号
     ******/
    static function PresaleWxVeri($funData)
    {
        $log_orders = M("log_orders");
        $mod_orders = M('orders');
        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $funData['cash_fee'] * 0.01;//分转换成元

        $where["orderNo"] = $funData['out_trade_no'];
        $mod_orders_data = $mod_orders->where($where)->find();
        $mod_orders_Money = (float)$mod_orders_data['PreSalePay'];//当前订单首付款价格

        if ((float)trim($cash_fee_toYuan) !== $mod_orders_Money) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "预售首款：微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务

        $where["orderNo"] = $funData['out_trade_no'];//根据订单号修改状态 和更新微信订单号
        $where["orderStatus"] = "12";
        $data_log_orders["orderStatus"] = "13";//改为首付款 已支付
        $data_log_orders["tradeNo"] = $funData['transaction_id'];
        $data_log_orders["isPay"] = 0;//预付款 不算订单已支付

        //如果预付款跟订单金额相同 则直接算全款-----全款处理
        if ($mod_orders_data['realTotalMoney'] == $cash_fee_toYuan) {
            $data_log_orders["isPay"] = 1;//全款预定 已付款
            // $data_log_orders["orderStatus"] = 0;//改为 未受理
            $data_log_orders["orderStatus"] = 14;//改为 预售订单已付款
        }

        $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
        if ($orderSaveStatic) {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 写更新成功  \n");
            fclose($myfile);
        } else {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 更新失败 \n");
            fclose($myfile);

            return false;
        }

        //写入订单日志 用户完成微信支付
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];
        $log_orders_data = $mod_orders->where($where)->find();

        unset($data);
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];

        $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
        $data["logContent"] = "app微信预支付完成";
        $data["logUserId"] = $log_orders_data["userId"];
        $data["logType"] = "0";
        $data["logTime"] = date("Y-m-d H:i:s");

        $log_orders->add($data);

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    static function xcxPresaleWxVeri($funData)
    {

        $log_orders = M("log_orders");
        $mod_orders = M('orders');

        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $funData['cash_fee'] * 0.01;//分转换成元

        $where["orderNo"] = $funData['out_trade_no'];
        $mod_orders_data = $mod_orders->where($where)->find();
        $mod_orders_Money = (float)$mod_orders_data['PreSalePay'];//当前订单首付款价格

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($mod_orders_data);
        fwrite($myfile, "预售首款：我来了：$txt \n");
        fclose($myfile);

        if ((float)trim($cash_fee_toYuan) !== $mod_orders_Money) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "预售首款：微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务

        $where["orderNo"] = $funData['out_trade_no'];//根据订单号修改状态 和更新微信订单号
        $where["orderStatus"] = "12";
        $data_log_orders["orderStatus"] = "13";//改为首付款 已支付
        $data_log_orders["tradeNo"] = $funData['transaction_id'];
        $data_log_orders["isPay"] = 0;//预付款 不算订单已支付

        //如果预付款跟订单金额相同 则直接算全款-----全款处理
        if ($mod_orders_data['realTotalMoney'] == $cash_fee_toYuan) {
            $data_log_orders["isPay"] = 1;//全款预定 已付款
            // $data_log_orders["orderStatus"] = 0;//改为 未受理
            $data_log_orders["orderStatus"] = 14;//改为 预售订单已付款
        }

        $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
        if ($orderSaveStatic) {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 写更新成功  \n");
            fclose($myfile);
        } else {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 更新失败 \n");
            fclose($myfile);

            return false;
        }

        //写入订单日志 用户完成微信支付
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];
        $log_orders_data = $mod_orders->where($where)->find();

        unset($data);
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];

        $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
        $data["logContent"] = "小程序微信预支付完成";
        $data["logUserId"] = $log_orders_data["userId"];
        $data["logType"] = "0";
        $data["logTime"] = date("Y-m-d H:i:s");

        $log_orders->add($data);

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    //预售订单处理-尾款

    /******
     *
     * $funData['cash_fee'] = $funData['cash_fee'];//现金支付金额
     * $funData['out_trade_no'] = $funData['out_trade_no'];//商户订单号
     * $funData['transaction_id'] = $funData['transaction_id'];//微信支付订单号
     ******/
    static function PresaleWxVeriD($funData)
    {
        $log_orders = M("log_orders");
        $mod_orders = M('orders');

        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $funData['cash_fee'] * 0.01;//分转换成元

        $where["orderNo"] = $funData['out_trade_no'];
        $mod_orders_data = $mod_orders->where($where)->find();
        $mod_orders_Money = (float)$mod_orders_data['PreSalePay'];//当前订单首付款价格

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($funData);
        fwrite($myfile, "预售尾款：我来了：$txt \n");
        fclose($myfile);

        if ((float)trim($cash_fee_toYuan) + $mod_orders_Money !== (float)$mod_orders_data['needPay']) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "预售尾款：微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务

        $where["orderNo"] = $funData['out_trade_no'];//根据订单号修改状态 和更新微信订单号
        $where["orderStatus"] = "13";
        $data_log_orders["orderStatus"] = 0;//改为 未受理
        $data_log_orders["tradeNo"] = $funData['transaction_id'];
        if (!empty($mod_orders_data['tradeNo'])) {
            $data_log_orders["tradeNo"] = $mod_orders_data['tradeNo'] . 'A' . $funData['transaction_id'];
        }

        $data_log_orders["isPay"] = 1;//订单已支付

        $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
        if ($orderSaveStatic) {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 写更新成功  \n");
            fclose($myfile);
        } else {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 更新失败 \n");
            fclose($myfile);

            return false;
        }

        //写入订单日志 用户完成微信支付
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];
        $log_orders_data = $mod_orders->where($where)->find();

        unset($data);
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];

        $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
        $data["logContent"] = "app微信尾款支付完成";
        $data["logUserId"] = $log_orders_data["userId"];
        $data["logType"] = "0";
        $data["logTime"] = date("Y-m-d H:i:s");

        $log_orders->add($data);

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    // 小程序端
    static function xcxPresaleWxVeriD($funData)
    {
        $log_orders = M("log_orders");
        $mod_orders = M('orders');

        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $funData['cash_fee'] * 0.01;//分转换成元

        $where["orderNo"] = $funData['out_trade_no'];
        $mod_orders_data = $mod_orders->where($where)->find();
        $mod_orders_Money = (float)$mod_orders_data['PreSalePay'];//当前订单首付款价格

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($funData);
        fwrite($myfile, "预售尾款：我来了：$txt \n");
        fclose($myfile);

        if ((float)trim($cash_fee_toYuan) + $mod_orders_Money !== (float)$mod_orders_data['needPay']) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "预售尾款：微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务

        $where["orderNo"] = $funData['out_trade_no'];//根据订单号修改状态 和更新微信订单号
        $where["orderStatus"] = "13";
        $data_log_orders["orderStatus"] = 0;//改为 未受理
        $data_log_orders["tradeNo"] = $funData['transaction_id'];
        if (!empty($mod_orders_data['tradeNo'])) {
            $data_log_orders["tradeNo"] = $mod_orders_data['tradeNo'] . 'A' . $funData['transaction_id'];
        }

        $data_log_orders["isPay"] = 1;//订单已支付

        $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
        if ($orderSaveStatic) {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 写更新成功  \n");
            fclose($myfile);
        } else {
            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "{$funData['out_trade_no']}更改订单状态 更新失败 \n");
            fclose($myfile);

            return false;
        }

        //写入订单日志 用户完成微信支付
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];
        $log_orders_data = $mod_orders->where($where)->find();

        unset($data);
        unset($where);
        $where["orderNo"] = $funData['out_trade_no'];

        $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
        $data["logContent"] = "小程序微信尾款支付完成";
        $data["logUserId"] = $log_orders_data["userId"];
        $data["logType"] = "0";
        $data["logTime"] = date("Y-m-d H:i:s");

        $log_orders->add($data);

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    //app微信支付成功验证-改变订单状态
    public function AppWxVerification($data)
    {
        //M()->startTrans();//开启事务
        // M()->rollback();//回滚
        // M()->commit();//事务提交
        //M("payments")->lock(true)->find(3);

        $data = WxXmlToArray($data);//把微信数据转为数组

        if ($data == false) {
            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, $txt);
        fclose($myfile);

        //判断订单是否已处理
        $appid = $data["appid"];//应用ID
        $mch_id = $data["mch_id"];//商户号
        $result_code = $data["result_code"];//业务结果
        $total_fee = $data["total_fee"];//总金额
        //$cash_fee = $data["cash_fee"];//现金支付金额
        $cash_fee = $data["total_fee"];//修复支付金额对比异常问题 后期重构

        $transaction_id = $data["transaction_id"];//微信支付订单号
        $out_trade_no = $data["out_trade_no"];//商户订单号

        $out_trade_no = M('order_merge')->where("orderToken='{$out_trade_no}'")->find()['value'];
        if (empty($out_trade_no)) {
            $out_trade_no = $data["out_trade_no"];
        }

        $out_trade_no = explode('A', $out_trade_no); //商户订单号分割

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($out_trade_no);
        fwrite($myfile, "商户解密后的订单号：$txt \n");
        fclose($myfile);

        //业务结果
        if ($result_code !== "SUCCESS") {

            return false;
        }

        //判断应用id与商户号是否能对得上 对的上就是微信服务器发来的
        $data_payments = M("payments")->find(3);
        $data_payments = json_decode($data_payments["payConfig"], true);

        if ($data_payments['appId'] !== $appid and $data_payments['mchId'] !== $mch_id) {

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "判断应用id与商户号是否能对得上 对的上就是微信服务器发来的\n");
        fclose($myfile);

        //判断订单状态 是否处理过了 （包括预售'12'订单的判断）
        $mod_orders = M('orders');
        for ($i = 0; $i < count($out_trade_no); $i++) {
            $where["orderNo"] = $out_trade_no[$i];
            $where['orderStatus'] = array('in', '-2,12,13');
            $mod_orders_num = $mod_orders->where($where)->find();
            if (!$mod_orders_num) {
                return false;
            }

            //判断是否是未支付的预售订单 是就跳转到预售订单结果处理 预售只能结算一个订单  （首付款）
            if ($mod_orders_num['orderStatus'] == 12) {
                $funData['cash_fee'] = $cash_fee;//现金支付金额
                $funData['out_trade_no'] = $out_trade_no[$i];//商户订单号
                $funData['transaction_id'] = $transaction_id;//微信支付订单号

                $thatIsRes = self::PresaleWxVeri($funData);

                return $thatIsRes;
            }

            //预售--支付尾款
            if ($mod_orders_num['orderStatus'] == 13) {

                $funData['cash_fee'] = $cash_fee;//现金支付金额
                $funData['out_trade_no'] = $out_trade_no[$i];//商户订单号
                $funData['transaction_id'] = $transaction_id;//微信支付订单号
                $thatIsRes = self::PresaleWxVeriD($funData);
                return $thatIsRes;
            }
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "判断订单是否处理过了\n");
        fclose($myfile);

        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $cash_fee * 0.01;//分转换成元
        for ($i = 0; $i < count($out_trade_no); $i++) {
            $where["orderNo"] = $out_trade_no[$i];
            $mod_orders_data = $mod_orders->where($where)->find();
            $mod_orders_Money[$i] = $mod_orders_data['realTotalMoney'];
        }
        $mod_orders_Money = array_sum($mod_orders_Money);//所有订单总价

        if ((float)trim($cash_fee_toYuan) !== (float)trim($mod_orders_Money)) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务
        $log_orders = M("log_orders");
        $mod_orders = M('orders');
        for ($i = 0; $i < count($out_trade_no); $i++) {
            $where["orderNo"] = $out_trade_no[$i];//根据订单号修改状态 和更新微信订单号
            $where["orderStatus"] = "-2";
            $data_log_orders["orderStatus"] = "0";//改为未受理
            $data_log_orders["tradeNo"] = $transaction_id;
            $data_log_orders["isPay"] = 1;

            $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
            if ($orderSaveStatic) {
                $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                $txt = json_encode($data);
                fwrite($myfile, "{$out_trade_no[$i]}更改订单状态 写更新成功  \n");
                fclose($myfile);
            } else {

                return false;

                $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                $txt = json_encode($data);
                fwrite($myfile, "{$out_trade_no[$i]}更改订单状态 更新失败 \n");
                fclose($myfile);
            }

            //写入订单日志 用户完成微信支付
            unset($where);
            $where["orderNo"] = $out_trade_no[$i];
            $log_orders_data = $mod_orders->where($where)->find();

            unset($data);
            unset($where);
            $where["orderNo"] = $out_trade_no[$i];

            $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
            $data["logContent"] = "app微信支付完成";
            $data["logUserId"] = $log_orders_data["userId"];
            $data["logType"] = "0";
            $data["logTime"] = date("Y-m-d H:i:s");

            $log_orders->add($data);
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    //小程序微信支付成功验证-改变订单状态
    public function xcxWxVerification($data)
    {
        //M()->startTrans();//开启事务
        // M()->rollback();//回滚
        // M()->commit();//事务提交
        //M("payments")->lock(true)->find(3);

        $data = WxXmlToArray($data);//把微信数据转为数组

        if ($data == false) {
            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, $txt);
        fclose($myfile);

        //判断订单是否已处理
        $appid = $data["appid"];//应用ID
        $mch_id = $data["mch_id"];//商户号
        $result_code = $data["result_code"];//业务结果
        $total_fee = $data["total_fee"];//总金额
        $cash_fee = $data["cash_fee"];//现金支付金额
        $transaction_id = $data["transaction_id"];//微信支付订单号
        $out_trade_no = $data["out_trade_no"];//商户订单号

        $out_trade_no = M('order_merge')->where("orderToken='{$out_trade_no}'")->find()['value'];
        if (empty($out_trade_no)) {
            $out_trade_no = $data["out_trade_no"];
        }

        $out_trade_no = explode('A', $out_trade_no); //商户订单号分割

        //业务结果
        if ($result_code !== "SUCCESS") {
            return false;
        }

        //判断应用id与商户号是否能对得上 对的上就是微信服务器发来的
        $data_payments = M("payments")->find(3);
        $data_payments = json_decode($data_payments["payConfig"], true);

        if ($data_payments['appId'] !== $appid and $data_payments['mchId'] !== $mch_id) {

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "判断应用id与商户号是否能对得上 对的上就是微信服务器发来的\n");
        fclose($myfile);

        //判断订单状态 是否处理过了 （包括预售'12'订单的判断）
        $mod_orders = M('orders');
        for ($i = 0; $i < count($out_trade_no); $i++) {
            $where["orderNo"] = $out_trade_no[$i];
            $where['orderStatus'] = array('in', '-2,12,13,15');
            $mod_orders_num = $mod_orders->where($where)->find();
            if (!$mod_orders_num) {

                return false;
            }

            //判断是否是未支付的预售订单 是就跳转到预售订单结果处理 预售只能结算一个订单  （首付款）
            if ($mod_orders_num['orderStatus'] == 12) {

                $funData['cash_fee'] = $cash_fee;//现金支付金额
                $funData['out_trade_no'] = $out_trade_no[$i];//商户订单号
                $funData['transaction_id'] = $transaction_id;//微信支付订单号

                $thatIsRes = self::xcxPresaleWxVeri($funData);

                return $thatIsRes;
            }

            //预售--支付尾款
            if ($mod_orders_num['orderStatus'] == 13) {

                $funData['cash_fee'] = $cash_fee;//现金支付金额
                $funData['out_trade_no'] = $out_trade_no[$i];//商户订单号
                $funData['transaction_id'] = $transaction_id;//微信支付订单号
                $thatIsRes = self::xcxPresaleWxVeriD($funData);
                return $thatIsRes;
            }
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "判断订单是否处理过了\n");
        fclose($myfile);

        //看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态
        $cash_fee_toYuan = $cash_fee * 0.01;//分转换成元
        for ($i = 0; $i < count($out_trade_no); $i++) {
            $where["orderNo"] = $out_trade_no[$i];
            $mod_orders_data = $mod_orders->where($where)->find();
            $mod_orders_Money[$i] = $mod_orders_data['realTotalMoney'];
        }
        $mod_orders_Money = array_sum($mod_orders_Money);//所有订单总价

        if ((float)trim($cash_fee_toYuan) !== (float)trim($mod_orders_Money)) {

            $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
            $txt = json_encode($data);
            fwrite($myfile, "微信金额：{$cash_fee_toYuan}#本地计算金额：{$mod_orders_Money}当前所有订单的金额 对比失败\n");
            fclose($myfile);

            return false;
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "看订单金额是否匹配 根据订单获取当前所有订单的金额 并改变状态 已执行\n");
        fclose($myfile);

        //更改订单状态 写入微信支付订单号 tradeNo
        // M()->startTrans();//开启事务
        $log_orders = M("log_orders");
        $mod_orders = M('orders');
        for ($i = 0; $i < count($out_trade_no); $i++) {
            // --- 原来的 --- start ----
            // $where["orderNo"] = $out_trade_no[$i];//根据订单号修改状态 和更新微信订单号
            // $where["orderStatus"] = "-2";
            // $data_log_orders["orderStatus"] = "0";//改为未受理
            // $data_log_orders["tradeNo"] = $transaction_id;
            // $data_log_orders["isPay"] = 1;
            // --- 原来的 --- end ----

            // --- 修改后的 --- start ----
            $orderNo = $out_trade_no[$i];
            $orderInfo = $mod_orders->where(array('orderNo' => $orderNo))->find();
            $where["orderNo"] = $out_trade_no[$i];//根据订单号修改状态 和更新微信订单号
            $where["orderStatus"] = ($orderInfo['orderStatus'] == 15) ? 15 : -2;
            if ($orderInfo['orderStatus'] == -2) $data_log_orders["orderStatus"] = "0";//改为未受理
            $data_log_orders["tradeNo"] = $transaction_id;
            $data_log_orders["isPay"] = 1;
            // --- 修改后的 --- end ----

            $orderSaveStatic = $mod_orders->where($where)->save($data_log_orders);//更新订单信息
            if ($orderSaveStatic) {
                $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                $txt = json_encode($data);
                fwrite($myfile, "{$out_trade_no[$i]}更改订单状态 写更新成功  \n");
                fclose($myfile);
            } else {

                return false;

                $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
                $txt = json_encode($data);
                fwrite($myfile, "{$out_trade_no[$i]}更改订单状态 更新失败 \n");
                fclose($myfile);
            }

            //写入订单日志 用户完成微信支付
            unset($where);
            $where["orderNo"] = $out_trade_no[$i];
            $log_orders_data = $mod_orders->where($where)->find();

            unset($data);
            unset($where);
            $where["orderNo"] = $out_trade_no[$i];

            $data["orderId"] = $mod_orders->where($where)->find()['orderId'];
            $data["logContent"] = "小程序微信支付完成";
            $data["logUserId"] = $log_orders_data["userId"];
            $data["logType"] = "0";
            $data["logTime"] = date("Y-m-d H:i:s");

            $log_orders->add($data);
        }

        $myfile = fopen("paywx.txt", "a+") or die("Unable to open file!");
        $txt = json_encode($data);
        fwrite($myfile, "更改订单状态 写入微信支付订单号 tradeNo  \n");
        fclose($myfile);

        return true;
    }

    //小程序 首页4个随机分类
    public function randtypelist($shopId)
    {
        $data = S("niao_weiapp_randtypelists" . $shopId);
        if (empty($data)) {
            //获取店铺随即分类
            $where['shopId'] = $shopId;
            $where['isShow'] = 1;
            $where['parentId'] = 0;
            $where['catFlag'] = 1;
            $data = M('shops_cats')->order('rand()')->where($where)->limit(4)->select();

//            $result['apiCode'] = 0;
//            $result['apiInfo'] = "ok";
//            $result['apiData'] = $data;
            $result = returnData($data);
            S("niao_weiapp_randtypelists" . $shopId, $result, C("allApiCacheTime"));
            return $result;
        }

        return $data;
    }

    //商城4个随机分类
    public function adminRandtypelist()
    {
        $data = S("niao_app_randtypelists");
        if (empty($data)) {
            $where['parentId'] = 0;
            $where['isShow'] = 1;
            $where['catFlag'] = 1;
            $data = M('goods_cats')->order('rand()')->where($where)->limit(4)->select();

//            $result['apiCode'] = 0;
//            $result['apiInfo'] = "ok";
//            $result['apiData'] = $data;
            $result = returnData($data);

            S("niao_app_randtypelists", $result, C("allApiCacheTime"));
            return $result;
        }
        return $data;
    }

    //地点检索
    public function placeapi($wd, $region, $lat, $lng)
    {
        $ak = $GLOBALS["CONFIG"]["baiduMap_ak"];
        $url = "http://api.map.baidu.com/place/v2/search?";
        $url = $url . "query={$wd}&region={$region}&output=json&ak={$ak}";
        $result = json_decode(file_get_contents($url), true);
        if ($result['status'] == 0) {
            $areaData = $result['results'];
            $areaTab = M('areas');
            foreach ($areaData as $key => $val) {
                if (!empty($val['location'])) {
                    $location = $val['location'];
                    $bd_res = bd_decrypt($location['lng'], $location['lat']);
                    $areaData[$key]['location']['lat'] = $bd_res['gg_lat'];
                    $areaData[$key]['location']['lng'] = $bd_res['gg_lon'];
                }
                $provinceCode = '';
                $cityCode = '';
                $areaCode = '';
                if (isset($val['province'])) {
                    $provinceCode = $areaTab->where(['areaName' => $val['province']])->getField('areaId');
                }
                if (isset($val['city'])) {
                    $cityCode = $areaTab->where(['areaName' => $val['city']])->getField('areaId');
                }
                if (isset($val['area'])) {
                    $areaCode = $areaTab->where(['areaName' => $val['area']])->getField('areaId');
                }
                if (is_null($provinceCode)) {
                    $provinceCode = '';
                }
                if (is_null($cityCode)) {
                    $cityCode = '';
                }
                if (is_null($areaCode)) {
                    $areaCode = '';
                }
                $areaData[$key]['provinceCode'] = $provinceCode;
                $areaData[$key]['cityCode'] = $cityCode;
                $areaData[$key]['areaCode'] = $areaCode;
                if (!empty($lat) and !empty($lng)) {
                    $areaData[$key]['Kilometer'] = getDistanceBetweenPointsNew($val['location']['lat'], $val['location']['lng'], $lat, $lng);
                }


            }
            $result['results'] = $areaData;
        }
        return json_encode($result);
    }

    //地点检索提示
    public function placeapiTips($wd, $region, $lat, $lng)
    {
        $ak = $GLOBALS["CONFIG"]["baiduMap_ak"];
        $url = "http://api.map.baidu.com/place/v2/suggestion?";
        $url = $url . "query={$wd}&region={$region}&output=json&ak={$ak}";
        $result = json_decode(file_get_contents($url), true);
        if ($result['status'] == 0) {
            $areaData = $result['result'];
            $areaTab = M('areas');
            foreach ($areaData as $key => $val) {
                if (!empty($val['location'])) {
                    $location = $val['location'];
                    $bd_res = bd_decrypt($location['lng'], $location['lat']);
                    $areaData[$key]['location']['lat'] = $bd_res['gg_lat'];
                    $areaData[$key]['location']['lng'] = $bd_res['gg_lon'];
                    $result['result'][$key]['location']['lat'] = $bd_res['gg_lat'];
                    $result['result'][$key]['location']['lng'] = $bd_res['gg_lon'];
                }
                $provinceCode = '';
                $cityCode = '';
                $areaCode = '';
                if (isset($val['province'])) {
                    $provinceCode = $areaTab->where(['areaName' => $val['province']])->getField('areaId');
                }
                if (isset($val['city'])) {
                    $cityCode = $areaTab->where(['areaName' => $val['city']])->getField('areaId');
                }
                if (isset($val['area'])) {
                    $areaCode = $areaTab->where(['areaName' => $val['area']])->getField('areaId');
                }
                if (is_null($provinceCode)) {
                    $provinceCode = '';
                }
                if (is_null($cityCode)) {
                    $cityCode = '';
                }
                if (is_null($areaCode)) {
                    $areaCode = '';
                }
                $areaData[$key]['provinceCode'] = $provinceCode;
                $areaData[$key]['cityCode'] = $cityCode;
                $areaData[$key]['areaCode'] = $areaCode;
                if (!empty($lat) and !empty($lng)) {
                    $areaData[$key]['Kilometer'] = getDistanceBetweenPointsNew($val['location']['lat'], $val['location']['lng'], $lat, $lng);
                }

            }
            $result['results'] = $areaData;
        }
        return json_encode($result);
    }

    /**
     * 获取所有城市-根据字母分类
     */
    public function getCityGroupByKey()
    {
        $rs = array();
        $rslist = M('areas')->cache('WST_CACHE_CITY_000', 31536000)->where('isShow=1 AND areaFlag = 1 AND areaType=1')->field('areaId,areaName,areaKey')->order('areaKey, areaSort')->select();
        foreach ($rslist as $key => $row) {
            $rs[$row["areaKey"]][] = $row;
        }
        return $rs;
    }

    //获取商城开放的顶级城市列表 (热门城市)
    public function hotcitylist()
    {
        $tab = M('areas');
        $parents = $tab->where("isShow=1 AND areaFlag = 1 AND parentId=0")->select();
        $pid = [];
        foreach ($parents as $val) {
            $pid[] = $val['areaId'];
        }
        $where = [];
        $where['isShow'] = 1;
        $where['areaFlag'] = 1;
        $where['areaType'] = 1;
        $where['parentId'] = ['IN', $pid];
        $rslist = M('areas')->where($where)->field('areaId,areaName')->order('parentId, areaSort')->select();
        return (array)$rslist;
    }

    //根据地区名称获取adcode 城市代码 只能获取 二三两级城市
    public function areaNameGetAdcode($areaName)
    {
        //$where['areaName'] = $areaName;
        //and parentId !==0
        $areaName1 = str_replace("县", "区", $areaName);
        $areaName2 = str_replace("区", "县", $areaName);
        $where['areaName'] = array('in', "{$areaName1},{$areaName2}");
        $where['parentId'] = array('NEQ', 0);

        $mod = M("areas")->where($where)->field('areaId')->find();
        if (empty($mod)) {
//            $res['apiCode'] = -1;
//            $res['apiInfo'] = 'error';
//            $res['apiData'] =$mod;
            $res = returnData($mod, -1, 'error', '有误');
            return $res;
        }
//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$mod;
        $res = returnData($mod);
        return $res;
    }

    //根据一级地区名称获取adcode 城市代码
    public function areaNameOneGetAdcode($areaName)
    {

        $mod = M("areas")->where("areaName ='{$areaName}' and parentId=0")->field('areaId')->find();
        if (empty($mod)) {
//            $res['apiCode'] = -1;
//            $res['apiInfo'] = 'error';
//            $res['apiData'] =$mod;
            $res = returnData($mod, -1, 'error', '有误');

            return $res;
        }
//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$mod;
        $res = returnData($mod);

        return $res;
    }

    //获取商城 一级分类列表
    public function getShopTypeOnelistData()
    {
        $where['isShow'] = 1;
        $where['catFlag'] = 1;
        $where['parentId'] = 0;
        //$mod = M("goods_cats")->cache('niao_cache_getShopTypeOnelistData',31536000)->where($where)->field(array("isShow","priceSection","catFlag","isFloor"),true)->order("catSort asc")->select();
        $mod = M("goods_cats")->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();

//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$mod;
        $res = returnData((array)$mod);
        return $res;
    }

    //获取商城 一级分类列表 | 获取首页商城分类 | 获取首页店铺分类
    public function getShopTypeOnelistDataByIndex($shopId)
    {
        if ($shopId > 0) {
            $where = array();
            $where['parentId'] = 0;
            $where['isShow'] = 1;
            $where['catFlag'] = 1;
            $where['shopId'] = $shopId;
            $where['isShowIndex'] = 1;
            $mod = M('shops_cats')->where($where)->order("catSort asc")->select();
        } else {
            $where = array();
            $where['isShow'] = 1;
            $where['isShowIndex'] = 1;
            $where['catFlag'] = 1;
            $where['parentId'] = 0;
            $mod = M("goods_cats")->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();
        }

        $res = returnData((array)$mod);
        return $res;
    }

    //获取商城 二级分类列表 循环内不能使用 ->cache  会覆盖数据
    public function getShopTypeTwolistData()
    {
        $where['isShow'] = 1;
        $where['catFlag'] = 1;

        $mod = M("goods_cats");
        $getTypeOne = $this->getShopTypeOnelistData();

        for ($i = 0; $i <= count($getTypeOne['apiData']) - 1; $i++) {
            $where['parentId'] = $getTypeOne['apiData'][$i]['catId'];
            $modData[$i] = $mod->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();
        }

        if (empty($modData)) {
//            $res['apiCode'] = -1;
//            $res['apiInfo'] = 'error';
//            $res['apiData'] =$modData;
            $res = returnData($modData, -1, 'error', '有误');
            return $res;
        }

//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =array_values(array_filter($modData));
        $res = returnData(array_values(array_filter($modData)));
        return $res;
    }

    //获取商城 三级分类列表
    public function getShopTypeThreelistData()
    {
        $where['isShow'] = 1;
        $where['catFlag'] = 1;

        $mod = M("goods_cats");
        $getTypeTwo = $this->getShopTypeTwolistData();

        $hynum = -1;
        for ($i = 0; $i <= count($getTypeTwo['apiData']) - 1; $i++) {
            for ($j = 0; $j <= count($getTypeTwo['apiData'][$i]) - 1; $j++) {
                $hynum++;
                $where['parentId'] = $getTypeTwo['apiData'][$i][$j]['catId'];
                $modData[$hynum] = $mod->where($where)->field(array("isShow", "priceSection", "catFlag", "isFloor"), true)->order("catSort asc")->select();
            }
        }

        if (empty($modData)) {
//            $res['apiCode'] = -1;
//            $res['apiInfo'] = 'error';
//            $res['apiData'] =$modData;
            $res = returnData($modData, -1, 'error', '有误');
            return $res;
        }

//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$modData;
        $res = returnData($modData);
        return $res;
    }

    //获取商城 二三级分类列表
    public function getShopTypeTwoAndThreelistData()
    {
        $twoList = $this->getShopTypeTwolistData();
        $threeList = $this->getShopTypeThreelistData();

        $data['twoList'] = $twoList['apiData'];
        $data['threeList'] = $threeList['apiData'];

//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$data;
        $res = returnData($data);
        return $res;
    }

    //获取商城 一二三级分类列表
    public function getShopTypeOneAndTwoAndThreelistData()
    {
        $oneList = $this->getShopTypeOnelistData();
        $twoList = $this->getShopTypeTwolistData();
        $threeList = $this->getShopTypeThreelistData();

        $data['oneList'] = $oneList['apiData'];
        $data['twoList'] = $twoList['apiData'];
        $data['threeList'] = $threeList['apiData'];

//        $res['apiCode'] = 0;
//        $res['apiInfo'] = 'success';
//        $res['apiData'] =$data;
        $res = returnData($data);

        return $res;
    }

    //根据商品id获取商品轮播图
    public function goodsBanners($goodsId, $shopId)
    {
        $map['goodsId'] = $goodsId;
        $map['shopId'] = $shopId;
        //$mod = M('goods_gallerys')->where($map)->cache("goodsBanners_{$goodId}",3600)->select();
        $mod = M('goods_gallerys')->where($map)->order('id asc')->select();
        if (!$mod) {
            $mod = [];
        }
        return $mod;
    }

    //根据第三级城市获取品牌1
    public function AdcodeBrands($areaId3, $page = 1)
    {
        $data = S("NIAO_CACHE_AdcodeBrands_{$areaId3}_{$page}");
        $pageDataNum = 10;
        if (empty($data)) {
            $Model = M();
            $limits = ($page - 1) * $pageDataNum;
            $sql = "SELECT bs.brandId,bs.brandName,bs.brandIco,bs.brandDesc FROM __PREFIX__brands bs,__PREFIX__shops sp,__PREFIX__goods g,__PREFIX__goods_cat_brands gcb WHERE bs.brandId=g.brandId AND g.shopId=sp.shopId AND gcb.brandId=bs.brandId AND bs.brandFlag = 1 AND sp.areaId3 = $areaId3 group by bs.brandId limit $limits,$pageDataNum";
            $data = $Model->query($sql);
            S("NIAO_CACHE_AdcodeBrands_{$areaId3}_{$page}", $data, C("allApiCacheTime"));
        }
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='品牌列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取热销商品列表
//    public function getHotGoodsList($areaId3, $userId = 0)
//    {
//        $data = S("NIAO_CACHE_getHotGoodsList_{$areaId3}");
//        if (empty($data)) {
//            $newTime = date('H') . '.' . date('i');//获取当前时间
//            $where["wst_shops.shopStatus"] = 1;
//            $where["wst_shops.shopFlag"] = 1;
//            //$where["wst_shops.areaId3"] = $areaId3;//不该限制店铺地区
//            $where["wst_shops.shopAtive"] = 1;
//            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
//            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
//
//            //商品条件
//            $where["wst_goods.isSale"] = 1;
//            $where["wst_goods.goodsStatus"] = 1;
//            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isShopPreSale"] = 0;//非预售
////            $where["wst_goods.isFlashSale"] = 0;//是否限时
////            $where["wst_goods.isLimitBuy"] = 0;//是否限量购
//
//            //区域条件
//            $where["wst_shops_communitys.areaId3"] = $areaId3;
//
//            $Model = M('goods');
//
//            $data = $Model->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
//                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
//                ->where($where)
//                ->order('saleCount desc')
//                // ->field('goodsId,goodsImg,marketPrice,shopPrice,goodsUnit,goodsSpec,saleCount,goodsName,markIcon,wst_goods.shopId,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.goodsCompany,wst_goods.isNewPeople')
//                ->limit(20)
//                ->group('wst_goods.goodsId')
//                ->select();
//            $data = handleNewPeople($data, $userId);
//            $data = rankGoodsPrice($data); //商品等级价格处理
//            S("NIAO_CACHE_getHotGoodsList_{$areaId3}", $data, C("allApiCacheTime"));
//        }
//
////        $apiRet['apiCode']=0;
////        $apiRet['apiInfo']='热销商品列表获取成功';
////        $apiRet['apiState']='success';
////        $apiRet['apiData']=$data;
//        $apiRet = returnData($data);
//        return $apiRet;
//    }


    /**
     * 获取商城热销商品
     * @param int $userId
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return array
     * */
    public function getHotGoodsList($userId = 0, $lat, $lng)
    {
        //复制上面原本的方法,然后新加过滤店铺的配送范围条件
        $data = S("NIAO_CACHE_getHotGoodsList_{$lat}-{$lng}");
        if (empty($data)) {
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return returnData();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["shops.shopId"] = array("in", $shopIdArr);
            $where["shops.shopStatus"] = 1;
            $where["shops.shopFlag"] = 1;
            $where["shops.shopAtive"] = 1;
//            $newTime = date('H') . '.' . date('i');//获取当前时间
//             $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
//             $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
            //商品条件
            $where["goods.isSale"] = 1;
            $where["goods.goodsStatus"] = 1;
            $where["goods.goodsFlag"] = 1;
            $where["goods.isShopPreSale"] = 0;
            $goodsModel = new \App\Models\GoodsModel();
            $resData = $goodsModel
                ->alias("goods")
                ->join('wst_shops shops on goods.shopId = shops.shopId')
                ->where($where)
                ->order('goods.saleCount desc')
                ->limit(20)
                ->group('goods.goodsId')
                ->select();
            $resData = handleNewPeople($resData, $userId);
            $resData = rankGoodsPrice($resData);
            S("NIAO_CACHE_getHotGoodsList_{$lat}-{$lng}", $resData, C("allApiCacheTime"));
        }
        return returnData((array)$resData);
    }

    /**
     * 获取新品商品列表
     * */
    public function getNewGoods($lat, $lng, $page = 1, $pageSize, $userId = 0)
    {

        $data = S("NIAO_CACHE_app_recFQ_{$lat}-{$lng}_{$page}");
        if (empty($data)) {
            $pageDataNum = $pageSize;
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return returnData();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["wst_shops.shopId"] = array("in", $shopIdArr);

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购


            $where['wst_goods.isNew'] = 1;//新品

            //配送区域条件
//            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                //->field('wst_goods.goodsId,wst_goods.goodsStock,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.isNewPeople,wst_goods.IntelligentRemark')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data);
            S("NIAO_CACHE_app_recFQ_{$lat}-{$lng}_{$page}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商城新品商品列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData((array)$data);

        return $apiRet;
    }

    /**
     * 获取新品商品列表
     * */
    public function getNewGoodsShop($shopId, $page = 1, $pageSize, $userId = 0, $adcode, $lng, $lat)
    {
        $pageDataNum = $pageSize;
        $newTime = date('H') . '.' . date('i');//获取当前时间

        $data = array();
        if (!empty($shopId)) {
            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
            $where["wst_goods.shopId"] = $shopId;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购


            $where['wst_goods.isNew'] = 1;//新品
            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                // ->field('wst_goods.goodsId,wst_goods.goodsStock,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.IntelligentRemark,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
        } else if (!empty($lng) && !empty($lat)) {
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return returnData();
            }
            $shopIdArrStr = implode(',', array_column($canUseShopList, 'shopId'));
            // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $newTime . "' and s.serviceEndTime >= '" . $newTime . "' and g.isSale = 1 and g.goodsStatus = 1 and g.goodsFlag = 1 and g.isNew = 1 and sc.areaId3 = " . $adcode;
            $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and g.isSale = 1 and g.goodsStatus = 1 and g.goodsFlag = 1 and g.isNew = 1";
            $where .= " and s.shopId IN($shopIdArrStr) ";

            // $sql = "select DISTINCT g.goodsId,g.IntelligentRemark,g.goodsStock,g.goodsName,g.goodsImg,g.goodsThums,g.shopId,g.marketPrice,g.shopPrice,g.saleCount,g.goodsUnit,g.goodsSpec,s.latitude,s.longitude,g.isShopSecKill,g.ShopGoodSecKillStartTime,g.ShopGoodSecKillEndTime,g.isNewPeople,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s left join wst_shops_communitys sc on s.shopId=sc.shopId left join __PREFIX__goods g on g.shopId=s.shopId " . $where . " group by g.goodsId order by distance limit " ;//. ($page - 1) * $pageDataNum . "," . $pageDataNum
            $sql = "select DISTINCT g.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s  left join __PREFIX__goods g on g.shopId=s.shopId " . $where . " group by g.goodsId order by distance limit " . ($page - 1) * $pageDataNum . "," . $pageDataNum;

            $data = $this->query($sql);
        }
        $data = handleNewPeople($data, $userId);//剔除新人专享商品
        $data = (array)rankGoodsPrice($data);
        $apiRet = returnData($data);
        return $apiRet;
    }

    //根据 店铺id获取所有评价列表
    public function getShopEvaluateList($shopId, $page = 1)
    {

        $data = S("NIAO_CACHE_getShopEvaluateList_{$shopId}_{$page}");
        if (empty($data)) {
            $parameter = I();
            $pageDataNum = 10;//每页一条数据
            $where['shopId'] = $shopId;
            $where['isShow'] = 1;
            isset($parameter['compScore']) ? $where['compScore'] = $parameter['compScore'] : false;
            $mod_oods_appraises = M('goods_appraises');
            $data = $mod_oods_appraises->where($where)->limit(($page - 1) * $pageDataNum, $pageDataNum)->order('createTime desc')->select();

            for ($i = 0; $i <= count($data) - 1; $i++) {
                $scro = (int)$data[$i]['goodsScore'] + (int)$data[$i]['serviceScore'] + (int)$data[$i]['timeScore'];
                switch ($scro) {
                    case $scro <= 5 :
                        $data[$i]['status'] = 0;

                        break;
                    case $scro > 5 and $scro <= 10:
                        $data[$i]['status'] = 1;

                        break;
                    case $scro > 10 and $scro <= 15:
                        $data[$i]['status'] = 2;

                        break;
                    default:
//                        $apiRet['apiCode']=-1;
//                        $apiRet['apiInfo']='数据异常 有鬼...';
//                        $apiRet['apiState']='error';
//                        $apiRet['apiData']=null;
                        $apiRet = returnData(null, -1, 'error', '数据异常 有鬼...');
                }
            }

            $modUsers = M('users');
            $where = array();
            $where['userFlag'] = 1;
            for ($i = 0; $i <= count($data) - 1; $i++) {
                $where['userId'] = $data[$i]['userId'];
                $uerData = $modUsers->where($where)->field('userName,userPhoto')->find();
                if (!empty($uerData['userPhoto'])) {
                    $data[$i]['userPhoto'] = $uerData['userPhoto'];
                } else {
                    $data[$i]['userPhoto'] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
                }
                if (!empty($uerData['userName'])) {
                    $data[$i]['userName'] = $uerData['userName'];
                } else {
                    $data[$i]['userName'] = "未设置";
                }
            }

            S("NIAO_CACHE_getShopEvaluateList_{$shopId}_{$page}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='评价列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取指定店铺类型评价 好中差 默认好评
    public function getShopEvaluateListDes($shopId, $page, $compScore)
    {

        $data = S("NIAO_CACHE_getShopEvaluateList_{$shopId}_{$page}_{$compScore}");
        if (empty($data)) {
            $pageDataNum = 10;//每页一条数据
            $where['shopId'] = $shopId;
            $where['isShow'] = 1;
            $where['compScore'] = $compScore;
            $mod_oods_appraises = M('goods_appraises');
            $data = $mod_oods_appraises->where($where)->limit(($page - 1) * $pageDataNum, $pageDataNum)->order('createTime desc')->select();

            $modUsers = M('users');
            $where = array();
            $where['userFlag'] = 1;
            for ($i = 0; $i <= count($data) - 1; $i++) {
                $where['userId'] = $data[$i]['userId'];
                $uerData = $modUsers->where($where)->field('userName,userPhoto')->find();
                if (!empty($uerData['userPhoto'])) {
                    $data[$i]['userPhoto'] = $uerData['userPhoto'];
                } else {
                    $data[$i]['userPhoto'] = $GLOBALS["CONFIG"]["goodsImg"];//未设置头像 获取默认头像
                }
                if (!empty($uerData['userName'])) {
                    $data[$i]['userName'] = $uerData['userName'];
                } else {
                    $data[$i]['userName'] = "未设置";
                }
            }

            S("NIAO_CACHE_getShopEvaluateList_{$shopId}_{$page}_{$compScore}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='评价列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //根据店铺id 获取店铺热销商品
    public function getShopHotGoodsList($shopId, $page, $userId = 0)
    {
        $data = S("NIAO_CACHE_getShopHotGoodsList_{$shopId}_{$page['page']}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['isMembershipExclusive'] = 0;
                }
            }

            $where['isSale'] = 1;
            $where['isHot'] = 1;
            $where['goodsStatus'] = 1;
            $where['goodsFlag'] = 1;
            $where['shopId'] = $shopId;

            $modGoods = M('goods');

            $data = $modGoods->where($where)
                // ->field('goodsId,goodsName,goodsImg,goodsThums,shopId,marketPrice,shopPrice,goodsStock,saleCount,goodsUnit,goodsSpec,markIcon,isDistribution,firstDistribution,SecondaryDistribution,isMembershipExclusive,memberPrice,integralReward,isNewPeople,IntelligentRemark')
                ->limit((intval($page['page']) - 1) * $page['pageSize'], $page['pageSize'])
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data);
            if (empty($data)) {
                $data = [];
            }
            S("NIAO_CACHE_getShopHotGoodsList_{$shopId}_{$page['page']}", $data, C("allApiCacheTime"));
        }

//		$apiRet['apiCode']=0;
//		$apiRet['apiInfo']='店铺热销商品获取成功';
//		$apiRet['apiState']='success';
//		$apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取商城第三级分类下的商品 先缓存数据 带分页数据缓存注意 在排序数据
    public function getShopTypeidGoodsList($userId, $adcode, $lat, $lng, $typeThreeId, $page = 1, $sort = [])
    {

        //$data = S("NIAO_CACHE_getShopTypeidGoodsList_{$adcode}_{$page}_{$typeThreeId}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $pageDataNum = 10;//每页一条数据

            $newTime = date('H') . '.' . date('i');//获取当前时间

            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.areaId3"] = $adcode;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
            $where["wst_goods.goodsCatId3"] = $typeThreeId;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            //排序条件
            $sortWhere = '';
            if (isset($sort['priceSort']) && !empty($sort['priceSort'])) {
                if ($sort['priceSort'] == 'ASC') {
                    $sortWhere = "wst_goods.shopPrice ASC";
                } elseif ($sort['priceSort'] == 'DESC') {
                    $sortWhere = "wst_goods.shopPrice DESC";
                }
            }
            if (isset($sort['saleCount']) && !empty($sort['saleCount'])) {
                if ($sort['saleCount'] == 'ASC') {
                    $sortWhere = "wst_goods.saleCount ASC";
                } elseif ($sort['saleCount'] == 'DESC') {
                    $sortWhere = "wst_goods.saleCount DESC";
                }
            }
            if (isset($sort['brandId']) && !empty($sort['brandId'])) {
                $where["wst_goods.brandId"] = $sort['brandId'];
            }
            $whereStr = ' 1=1 ';
            $withJoin = '';
            if (isset($sort['goodsAttrId']) && !empty($sort['goodsAttrId'])) {
                $goodsAttrId = $sort['goodsAttrId'];
                $whereStr .= " AND wst_goods_attributes.id IN($goodsAttrId)";
                $withJoin = "LEFT JOIN wst_goods_attributes ON wst_goods_attributes.goodsId = wst_goods.goodsId";
            }
            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join($withJoin)
                ->where($where)
                ->where($whereStr)
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_goods.goodsStock,wst_shops.latitude,wst_shops.longitude,wst_goods.markIcon,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.memberPrice,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->order($sortWhere)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            //S("NIAO_CACHE_getShopTypeidGoodsList_{$adcode}_{$page}_{$typeThreeId}",$data,C("allApiCacheTime"));
        }

        if (empty($sort)) {
            //计算距离
            foreach ($data as $key => &$val) {
                $val['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($val['latitude'], $val['longitude'], $lat, $lng)['kilometers']);
            }
            unset($val);
            /*for($i=0;$i<=count($data)-1;$i++){
                $data[$i]['kilometers'] = sprintf("%.2f",getDistanceBetweenPointsNew($data[$i]['latitude'],$data[$i]['longitude'],$lat,$lng)['kilometers']);
            }*/

            $shopsDataSort = array();
            foreach ($data as $user) {
                $shopsDataSort[] = $user['kilometers'];
            }
            array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序
        }
        return $data;
    }

    //获取商城第一级分类下的商品 先缓存数据 带分页数据缓存注意 在排序数据
    public function getShopTypeidIsOneGoodsList($userId, $typeThreeId, $page = 1, $shopId)
    {

        $data = S("NIAO_CACHE_getShopTypeidIsOneGoodsList_{$shopId}_{$page}_{$typeThreeId}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $pageDataNum = 10;//每页10条数据
            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
            $where["wst_goods.goodsCatId1"] = $typeThreeId;
            $where["wst_goods.shopId"] = $shopId;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->where($where)
                ->field('goodsId,goodsName,goodsImg,goodsThums,shopId,marketPrice,shopPrice,saleCount,goodsUnit,goodsSpec,goodsCatId1,goodsStock,isDistribution,firstDistribution,SecondaryDistribution,markIcon,isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();

            /* foreach ($data as $k=>$user) {
		  $data[$k]['typeimg'] = M('goods_cats')->where("catId = '{$user['goodsCatId1']}'")->find()['typeimg'];;
		} */
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_getShopTypeidIsOneGoodsList_{$shopId}_{$page}_{$typeThreeId}", $data, C("allApiCacheTime"));
        }

        $resdata['goods'] = $data;
        $resdata['typeimg'] = M('goods_cats')->where("catId = '{$typeThreeId}'")->find()['typeimg'];

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$resdata;
        $apiRet = returnData($resdata);
        return $apiRet;
    }

    //商品的综合评价 缓存加载控制器上
    public function goodDetailAppraises($goodsId)
    {
        $parameter = I();
        $where['goodsId'] = $goodsId;
        $where['isShow'] = 1;
        if (isset($parameter['compScore'])) {
            $where['compScore'] = $parameter['compScore'];
        }
        $mod_oods_appraises = M('goods_appraises');
        $data_goodsScore = $mod_oods_appraises->where($where)->avg('goodsScore');
        $data_serviceScore = $mod_oods_appraises->where($where)->avg('serviceScore');
        $data_timeScore = $mod_oods_appraises->where($where)->avg('timeScore');

        $data['goodsScore'] = (float)$data_goodsScore;//商品评分
        $data['serviceScore'] = (float)$data_serviceScore;//服务评分
        $data['timeScore'] = (float)$data_timeScore;//时效评分
        //$data['zfScore'] = ($data['goodsScore']+$data['serviceScore']+$data['timeScore'])/3;//总分
        $sql = "SELECT count(id) as num from __PREFIX__goods_appraises where goodsId=$goodsId and isShow=1 and goodsScore+serviceScore+timeScore > 5 and goodsScore+serviceScore+timeScore<=15";
        if (isset($parameter['compScore'])) {
            $sql .= " and compScore={$parameter['compScore']}";
        }
        $appraises_num = $this->query($sql);

        $appraises_num2 = $mod_oods_appraises->where($where)->count();

        $appraises_zf = (int)$appraises_num[0]['num'] / (int)$appraises_num2 / 0.01;//求出百分比
        $data['zfScore'] = round($appraises_zf, 1);

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商品综合评价获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //店铺的综合评价
    public function shopDetailAppraisess($shopId)
    {
        $parameter = I();
        $where['shopId'] = $shopId;
        $where['isShow'] = 1;
        if (isset($parameter['compScore'])) {
            $where['compScore'] = $parameter['compScore'];
        }
        $mod_oods_appraises = M('goods_appraises');
        $data_goodsScore = $mod_oods_appraises->where($where)->avg('goodsScore');
        $data_serviceScore = $mod_oods_appraises->where($where)->avg('serviceScore');
        $data_timeScore = $mod_oods_appraises->where($where)->avg('timeScore');

        $data['goodsScore'] = (float)$data_goodsScore;//商品评分
        $data['serviceScore'] = (float)$data_serviceScore;//服务评分
        $data['timeScore'] = (float)$data_timeScore;//时效评分
        //$data['zfScore'] = ($data['goodsScore']+$data['serviceScore']+$data['timeScore'])/3;//总分
        $sql = "SELECT count(id) as num from __PREFIX__goods_appraises where shopId=$shopId and isShow=1 and goodsScore+serviceScore+timeScore > 5 and goodsScore+serviceScore+timeScore<=15";
        if (isset($parameter['compScore'])) {
            $sql .= " and compScore={$parameter['compScore']}";
        }
        $appraises_num = $this->query($sql);

        $appraises_num2 = $mod_oods_appraises->where($where)->count();

        $appraises_zf = (int)$appraises_num[0]['num'] / (int)$appraises_num2 / 0.01;//求出百分比
        $data['zfScore'] = round($appraises_zf, 1);

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='店铺综合评价获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //商城秒杀商品 三条数据-广告性质 目前采取 销量最高三条 应采取后台设置
    public function indexSecKillList($userId, $adcode, $lat, $lng)
    {
        $data = S("NIAO_CACHE_app_indexSecKillList_{$adcode}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $newTime = date('H') . '.' . date('i');//获取当前时间

            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.AdminShopGoodSecKillStartTime'] = array('ELT', date("Y-m-d H:i:s"));
            $where['wst_goods.AdminShopGoodSecKillEndTime'] = array('EGT', date("Y-m-d H:i:s"));
            $where['wst_goods.isAdminShopSecKill'] = 1;

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")//拿出前三销量最高的商品
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.markIcon,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.goodsCompany,wst_goods.isNewPeople')
                ->limit(3)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_app_indexSecKillList_{$adcode}", $data, C("allApiCacheTime"));
        }

        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

        return $data;
    }

    //商城秒杀商品列表 分页
    public function indexSecKillAllLists($userId, $adcode, $lat, $lng, $page = 1)
    {
        $data = S("NIAO_CACHE_app_indexSecKillAllLists_{$adcode}_{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $pageDataNum = 10;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.AdminShopGoodSecKillStartTime'] = array('ELT', date("Y-m-d H:i:s"));
            $where['wst_goods.AdminShopGoodSecKillEndTime'] = array('EGT', date("Y-m-d H:i:s"));

            $where['wst_goods.isAdminShopSecKill'] = 1;

            $where['wst_goods.isShopSecKill'] = 1;//店铺必须为秒杀 否则商城秒杀没有任何意义

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.markIcon,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_app_indexSecKillAllLists_{$adcode}_{$page}", $data, C("allApiCacheTime"));
        }

        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

        return $data;
    }

    //店铺秒杀商品列表
    public function ShopSecKillAllLists($userId, $shopId)
    {
        $data = S("NIAO_CACHE_app_ShopSecKillAllLists_{$shopId}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopId"] = $shopId;
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.ShopGoodSecKillStartTime'] = array('ELT', date("Y-m-d H:i:s"));
            $where['wst_goods.ShopGoodSecKillEndTime'] = array('EGT', date("Y-m-d H:i:s"));

            $where['wst_goods.isShopSecKill'] = 1;//店铺必须为秒杀 否则商城秒杀没有任何意义

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.markIcon,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.isNewPeople')
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_app_ShopSecKillAllLists_{$shopId}", $data, C("allApiCacheTime"));
        }

        return $data;
    }

    //获取订单详情
    public function getOrderDetail($orderId, $userId)
    {
//        $config = $GLOBALS['CONFIG'];
        $where['orderId'] = $orderId;
        $where['userId'] = $userId;

        $data = M('orders')->where($where)->field('isPay,requireTime,orderId,orderNo,shopId,userId,userAddress,userPhone,userName,deliverMoney,orderScore,realTotalMoney,scoreMoney,distance,dmName,dmMobile,payFrom,dmId,orderRemarks,isSelf,createTime,totalMoney,couponId,defPreSale,setDeliveryMoney,deliverType,driverId')->find();
        if ($data['requireTime'] == '0000-00-00 00:00:00') {
            $data['requireTime'] = '';
        }
        if ($data) {
            $data['couponMoney'] = 0;
            $couponInfo = M('coupons')->where(['couponId' => $data['couponId']])->find();
            if ($couponInfo) {
                $data['couponMoney'] = number_format($couponInfo['couponMoney'], 2, ".", "");
            }
            $data['assembleState'] = 3;//拼团状态，正常订单
            $user_activity_relation_info = M('user_activity_relation')->where(array('orderId' => $orderId, 'uid' => $userId))->find();
            if (!empty($user_activity_relation_info)) $data['assembleState'] = $user_activity_relation_info['state'];//拼团状态，拼团订单
        }
        if (!empty($data['driverId'])) {
            $driverDetail = (new DriverModule())->getDriverDetailById($data['driverId'], 'driverName,driverPhone', 0);
            if (!empty($driverDetail)) {
                $data['dmName'] = $driverDetail['driverName'];
                $data['dmMobile'] = $driverDetail['driverPhone'];
            }
        }
        $shopInfo = M("shops")->where("shopId='" . $data['shopId'] . "'")->find();
        $data['shopAddress'] = $shopInfo['shopAddress'];
        $data['shopName'] = $shopInfo['shopName'];
        $data['deliveryFreeMoney'] = $shopInfo['deliveryFreeMoney'];
        $data['shopTel'] = $shopInfo['shopTel'];
        $config = $GLOBALS['CONFIG'];
        if ($config['setDeliveryMoney'] == 2) {//废弃
            if ($data['isPay'] == 1) {
                //$data['realTotalMoney'] = $data['realTotalMoney'] + $data['deliverMoney'];
                $data['realTotalMoney'] = $data['realTotalMoney'];
            } else {
                if ($data['realTotalMoney'] < $config['deliveryFreeMoney']) {
                    $data['realTotalMoney'] = $data['realTotalMoney'] + $data['setDeliveryMoney'];
                    $data['deliverMoney'] = $data['setDeliveryMoney'];
                }
            }
        } else {
            if ($data['isPay'] != 1 && $data['setDeliveryMoney'] > 0) {
                $data['realTotalMoney'] = $data['realTotalMoney'] + $data['setDeliveryMoney'];
                $data['deliverMoney'] = $data['setDeliveryMoney'];
            }
        }
        $orderGoods = M('order_goods og')
            ->join('left join wst_goods g on g.goodsId=og.goodsId')
            ->where("og.orderId = " . $orderId)
            ->field('og.*,g.minBuyNum')
            ->select();
        $systemTab = M('sku_goods_system');
        $data['buyAgain'] = 1;//再次购买(-1:不可以|1:可以)
        $goodsModel = M('goods');
        $goodsModule = new GoodsModule();
        foreach ($orderGoods as $key => $val) {
            $orderGoods[$key]['unit'] = $goodsModule->getGoodsUnitByParams($val['goodsId'], $val['skuId']);
            $val['goodsNums'] = (float)$val['goodsNums'];
            $orderGoods[$key]['goodsNums'] = $val['goodsNums'];
            $orderGoods[$key]['totalGoodsPrice'] = sprintfNumber((float)$val['goodsPrice'] * $val['goodsNums']);
            $goodsDetail = $goodsModel->where(array('goodsId' => $val['goodsId']))->find();
            $orderGoods[$key]['SuppPriceDiff'] = $goodsDetail['SuppPriceDiff'];
            if (!empty($val['skuId'])) {
                $systemInfo = $systemTab->where(['skuId' => $val['skuId'], 'dataFlag' => 1])->find();
                if ($systemInfo) {
                    $orderGoods[$key]['minBuyNum'] = $systemInfo['minBuyNum'];
                }
            } else {
                //主要为了处理商品之前没有sku后来添加sku,导致加入购物车数量不对(最小起购量)
                $systemCount = $systemTab->where(['goodsId' => $val['goodsId'], 'dataFlag' => 1])->count();
                if ($systemCount > 0) {
                    $data['buyAgain'] = -1;
                }
            }
        }
        $data['goodsList'] = $orderGoods;
        return $data;
    }

    //商品是否被关注
    public function goodIsFollow($targetId, $userId)
    {

        $where['userId'] = $userId;
        $where['targetId'] = $targetId;
        $where['favoriteType'] = 0;

        $data = M('favorites')->where($where)->find();
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    //店铺是否被关注
    public function shopIsFollow($targetId, $userId)
    {
        $where['userId'] = $userId;
        $where['targetId'] = $targetId;
        $where['favoriteType'] = 1;

        $data = M('favorites')->where($where)->find();
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    //商城快讯 、通知
    public function adminShopNotice()
    {
        $where['catId'] = 1;
        $where['isShow'] = 1;
        $data = M('articles')->where($where)->order('createTime desc')->select();
        return $data;
    }

    //根据订单id 获取订单日志
    public function userOrderLog($orderId, $userId)
    {
        $orderis = M('orders')->where("orderId='{$orderId}'")->find()['userId'];
        if ($orderis !== $userId) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='数据对比失败';
//            $apiRet['apiState']='error';
            //$apiRet['apiData']=$data;
            $apiRet = returnData(null, -1, 'error', '数据对比失败');
            return $apiRet;
        }
        $data = M('log_orders')->where("orderId = '{$orderId}'")->order('logTime desc')->select();
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取订单日志成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    /**
     * 获取今日已抽多少次
     * @param $userId
     */
    public function getUserPrizeCount($userId)
    {
        $where['userId'] = $userId;
        $where['createTime'] = array('like', date('Y-m-d') . '%');
        return M('user_prize')->where($where)->count();
    }

    // app - 抽奖随机
    public function rndIntegral($userId)
    {

        //用户今天是否已经抽奖
        $where['userId'] = $userId;
        $where['createTime'] = array('gt', date('Y-m-d'));
        $static = M('user_prize')->where($where)->find();
        if (!empty($static)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='今日已抽奖';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '今日已抽奖');
            return $apiRet;
        }

        $num = explode("-", $GLOBALS["CONFIG"]['IntegralRange']);
        $Integral = rand($num[0], $num[1]);

        //积分累计到用户积分
        //M('users')->where("userId={$userId}")->setInc('userScore', $Integral);
        //积分处理-start
        $users_service_module = new UsersServiceModule();
        $score = (int)$Integral;
        $users_id = $userId;
        $result = $users_service_module->return_users_score($users_id, $score);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(null, -1, 'error', $result['msg']);
        }
        //积分处理-end

        //添加积分记录
        $data['userId'] = $userId;
        $data['score'] = $Integral;
        $data['dataSrc'] = 7;
        $data['dataRemarks'] = "app抽奖获得";
        $data['scoreType'] = 1;
        $data['createTime'] = date('Y-m-d H:i:s');
        M('user_score')->add($data);

        //添加抽奖记录
        unset($data);
        $data['userId'] = $userId;
        $data['prize'] = 1;
        $data['prizeValue'] = $Integral;
        $data['createTime'] = date('Y-m-d H:i:s');
        M('user_prize')->add($data);

        unset($data);
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='抽奖结果';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=array('Integral'=>$Integral,'info'=>'恭喜获得积分');
        $apiRet = returnData(array('Integral' => $Integral, 'info' => '恭喜获得积分'));
        return $apiRet;
    }

    // 小程序 - 抽奖随机
    public function xcxRndIntegral($userId)
    {

        //用户今天是否已经抽奖
        $where['userId'] = $userId;
        $where['createTime'] = array('gt', date('Y-m-d'));
        $static = M('user_prize')->where($where)->find();
        if (!empty($static)) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='今日已抽奖';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '今日已抽奖');
            return $apiRet;
        }

        $num = explode("-", $GLOBALS["CONFIG"]['IntegralRange']);
        $Integral = rand($num[0], $num[1]);

        //积分累计到用户积分
        //M('users')->where("userId={$userId}")->setInc('userScore', $Integral);
        //积分处理-start
        $users_service_module = new UsersServiceModule();
        $score = (int)$Integral;
        $users_id = $userId;
        $result = $users_service_module->return_users_score($users_id, $score);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(null, -1, 'error', $result['msg']);
        }
        //积分处理-end

        //添加积分记录
        $data['userId'] = $userId;
        $data['score'] = $Integral;
        $data['dataSrc'] = 7;
        $data['dataRemarks'] = "小程序抽奖获得";
        $data['scoreType'] = 1;
        $data['createTime'] = date('Y-m-d H:i:s');
        M('user_score')->add($data);

        //添加抽奖记录

        unset($data);
        $data['userId'] = $userId;
        $data['prize'] = 1;
        $data['prizeValue'] = $Integral;
        $data['createTime'] = date('Y-m-d H:i:s');
        M('user_prize')->add($data);

        unset($data);
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='抽奖结果';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=array('Integral'=>$Integral,'info'=>'恭喜获得积分');
        $apiRet = returnData(array('Integral' => $Integral, 'info' => '恭喜获得积分'));
        return $apiRet;
    }

    //抽奖记录列表 分页
    public function ranListIntegralPage($userId, $page)
    {

        $pageDataNum = 10;//每页10条数据
        $where['userId'] = $userId;
        $data = M('user_prize')->
        where($where)->
        order('createTime desc')->
        limit(($page - 1) * $pageDataNum, $pageDataNum)->
        select();

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //积分记录列表 分页
    public function scoreHisList($userId, $page)
    {
        $pageDataNum = 10;//每页10条数据
        $where['userId'] = $userId;
        $data = M('user_score')->
        where($where)->
        order('createTime desc')->
        limit(($page - 1) * $pageDataNum, $pageDataNum)->
        select();

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);

        return $apiRet;
    }

    //用户等级列表
    public function userListranks()
    {
        $data = M('user_ranks')->order('endScore desc')->field(array('createTime'), true)->select();
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='用户等级列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //用户等级信息(针对总后台等级)
    public function userRankInfo($userId)
    {
        $userRank = M('rank_user')->where("userId='" . $userId . "'")->find();
        $rankInfo = [];
        if ($userRank) {
            $rankInfo = M('user_ranks')->where("rankId='" . $userRank['rankId'] . "'")->find();
        }
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '用户等级信息获取成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $rankInfo;
        $rankInfo = empty($rankInfo) ? [] : $rankInfo;
        $apiRet = returnData($rankInfo);
        return $apiRet;
    }

    //用户等级信息(针对商家等级)
    public function userRankInfoShop($userId)
    {
        $userRank = M('rank_user')->where("userId='" . $userId . "'")->find();
        $rankInfo = [];
        if ($userRank) {
            $rankInfo = M('rank')->where("rankId='" . $userRank['rankId'] . "'")->find();
        }
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '用户等级信息获取成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $rankInfo;
        $apiRet = returnData($rankInfo);

        return $apiRet;
    }

    //获取全部订单-分页
    public function getAllOrdersList($userId, $page, $pageSize)
    {
        $pageDataNum = $pageSize;//每页10条数据
        $where['wst_orders.userId'] = $userId;
        $where['wst_orders.orderFlag'] = 1;
        $where['wst_orders.userDelete'] = 1;
        $where['wst_orders.orderStatus'] = array('NEQ', 15);

        $shopId = I('shopId', 0, 'intval');
        if (!empty($shopId)) $where['wst_orders.shopId'] = $shopId;

        $fieldArr = array(
            'orderId',
            'orderNo',
            'shopId',
            'orderStatus',
            'totalMoney',
            'payType',
            'isSelf',
            'isPay',
            'deliverType',
            'createTime',
            'needPay',
            'defPreSale',
            'PreSalePay',
            'PreSalePayPercen',
            'deliverMoney',
            'realTotalMoney',
            'isAppraises',
            'setDeliveryMoney'
        );

        $data = M('orders')
            ->where($where)
            ->order('createTime desc')
            ->field($fieldArr)
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();

        $order_goods_mod = M('order_goods');
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['goodsData'] = $order_goods_mod->where("orderId = '{$data[$i]['orderId']}'")->select();
            $data[$i]['assembleState'] = 3;//拼团状态，正常订单
            $user_activity_relation_info = M('user_activity_relation')->where(array('orderId' => $data[$i]['orderId'], 'uid' => $userId))->find();
            if (!empty($user_activity_relation_info)) $data[$i]['assembleState'] = $user_activity_relation_info['state'];//拼团状态，拼团订单
        }

        //获取商品描述等
        $goods_mod = M('goods');
        $shops_mod = M('shops');
        $config = $GLOBALS['CONFIG'];
        $goodsModule = new GoodsModule();
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $shops_mod_data = $shops_mod->where("shopId = '{$data[$i]['shopId']}'")->find();
            $data[$i]['buyAgain'] = 1;//是否可以再次购买(-1:不可以|1:可以)
            if ($config['setDeliveryMoney'] == 2) {//废弃
                if ($data[$i]['isPay'] == 1) {
                    //$data[$i]['realTotalMoney'] = $data[$i]['realTotalMoney'] + $data[$i]['deliverMoney'];
                    $data[$i]['realTotalMoney'] = $data[$i]['realTotalMoney'];
                } else {
                    if ($data[$i]['realTotalMoney'] < $config['deliveryFreeMoney']) {
                        $data[$i]['realTotalMoney'] = $data[$i]['realTotalMoney'] + $data[$i]['setDeliveryMoney'];
                    }
                }
            } else {
                if ($data[$i]['isPay'] != 1 && $data[$i]['setDeliveryMoney'] > 0) {
                    $data[$i]['realTotalMoney'] = $data[$i]['realTotalMoney'] + $data[$i]['setDeliveryMoney'];
                    $data[$i]['deliverMoney'] = $data[$i]['setDeliveryMoney'];
                }
            }
            $data[$i]['shopName'] = $shops_mod_data['shopName'];
            $data[$i]['shopImg'] = $shops_mod_data['shopImg'];

            $systemTab = M('sku_goods_system');
            for ($j = 0; $j <= count($data[$i]['goodsData']) - 1; $j++) {
                $goods_mod_data = $goods_mod->field(array('goodsUnit,recommDesc,goodsSpec,goodsDesc,saleCount,minBuyNum,SuppPriceDiff'))->where("goodsId = '{$data[$i]['goodsData'][$j]['goodsId']}'")->find();

                $data[$i]['goodsData'][$j]['unit'] = $goodsModule->getGoodsUnitByParams($data[$i]['goodsData'][$j]['goodsId'], $data[$i]['goodsData'][$j]['skuId']);
                $data[$i]['goodsData'][$j]['goodsNums'] = (float)$data[$i]['goodsData'][$j]['goodsNums'];
                $data[$i]['goodsData'][$j]['totalGoodsPrice'] = sprintfNumber((float)$data[$i]['goodsData'][$j]['goodsPrice'] * $data[$i]['goodsData'][$j]['goodsNums']);
                $data[$i]['goodsData'][$j]['SuppPriceDiff'] = $goods_mod_data['SuppPriceDiff'];
                $data[$i]['goodsData'][$j]['recommDesc'] = $goods_mod_data['recommDesc'];
                $data[$i]['goodsData'][$j]['goodsUnit'] = $goods_mod_data['goodsUnit'];
                $data[$i]['goodsData'][$j]['goodsSpec'] = $goods_mod_data['goodsSpec'];
                $data[$i]['goodsData'][$j]['goodsDesc'] = $goods_mod_data['goodsDesc'];
                $data[$i]['goodsData'][$j]['saleCount'] = $goods_mod_data['saleCount'];
                $data[$i]['goodsData'][$j]['minBuyNum'] = $goods_mod_data['minBuyNum'];
                if (!empty($data[$i]['goodsData'][$j]['skuId'])) {
                    $systemInfo = $systemTab->where(['skuId' => $data[$i]['goodsData'][$j]['skuId'], 'dataFlag' => 1])->find();
                    if ($systemInfo) {
                        $data[$i]['goodsData'][$j]['minBuyNum'] = $systemInfo['minBuyNum'];
                    }
                } else {
                    $systemCount = $systemTab->where(['goodsId' => $data[$i]['goodsData'][$j]['goodsId'], 'dataFlag' => 1])->count();
                    if ($systemCount > 0) {
                        $data[$i]['buyAgain'] = -1;
                    }
                }
            }
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='全部订单列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    //商城精品
    public function mallFQ($userId, $adcode, $lat, $lng, $page = 1)
    {

        $data = S("NIAO_CACHE_app_mallFQ_{$adcode}_{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $pageDataNum = 10;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购


            $where['wst_goods.isAdminBest'] = 1;//精品

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.markIcon,wst_shops.shopId,wst_shops.shopName,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data);
            S("NIAO_CACHE_app_mallFQ_{$adcode}_{$page}", $data, C("allApiCacheTime"));

        }


        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

        return $data;
    }

    //商城推荐
    public function recFQ($userId, $adcode, $lat, $lng, $page = 1, $pageSize)
    {

        $data = S("NIAO_CACHE_app_recFQ_{$adcode}_{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            $pageDataNum = $pageSize;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return array();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["wst_shops.shopId"] = array("in", $shopIdArr);

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.isAdminRecom'] = 1;//精品

            //配送区域条件
//            if (!empty($adcode)) {
//                $where["wst_shops_communitys.areaId3"] = $adcode;
//            }
            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                //->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("wst_goods.isAdminBest desc,wst_goods.isAdminRecom desc,wst_goods.saleCount desc")
                ->field('wst_goods.goodsId,wst_goods.goodsStock,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople,wst_goods.memberPrice,wst_goods.IntelligentRemark')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();

            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data);
            S("NIAO_CACHE_app_recFQ_{$adcode}_{$page}", $data, C("allApiCacheTime"));
        }

        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

        return $data;
    }

    //店铺精品
    public function shopFQ($userId, $shopId)
    {
        //暂不验证店铺是否处于运营状态
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
            $expireTime = $user_info['expireTime'];//会员过期时间
            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                $where['isMembershipExclusive'] = 0;
            }
        }

        $where['shopId'] = $shopId;
        $where['isSale'] = 1;
        $where['isBest'] = 1;
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['shopId'] = $shopId;
//        $where['isFlashSale'] = 0;//是否限时
//        $where['isLimitBuy'] = 0;//是否限量购

        $array_field = array(
            'goodsId',
            'goodsName',
            'goodsImg',
            'goodsThums',
            'shopId',
            'marketPrice',
            'shopPrice',
            'goodsStock',
            'saleCount',
            'goodsUnit',
            'goodsSpec',
            'recommDesc',
            'goodsDesc',
            'isNewPeople'
        );
        $data = M('goods')->where($where)->field($array_field)->order('saleCount asc')->select();
        $data = handleNewPeople($data, $userId);//剔除新人专享商品
        $data = rankGoodsPrice($data);

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //店铺推荐商品
    public function shopRecFQ($userId, $shopId, $page = 1, $pageSize)
    {
        //暂不验证店铺是否处于运营状态
        $pageDataNum = $pageSize;//每页10条数据
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
            $expireTime = $user_info['expireTime'];//会员过期时间
            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                $where['isMembershipExclusive'] = 0;
            }
        }

        $where['shopId'] = $shopId;
        $where['isSale'] = 1;
        $where['isRecomm'] = 1;//是否店铺推荐
        $where['goodsStatus'] = 1;
        $where['goodsFlag'] = 1;
        $where['shopId'] = $shopId;
//        $where['isFlashSale'] = 0;//是否限时
//        $where['isLimitBuy'] = 0;//是否限量购

        $array_field = array(
            'goodsId',
            'goodsName',
            'goodsImg',
            'goodsThums',
            'shopId',
            'marketPrice',
            'shopPrice',
            'goodsStock',
            'saleCount',
            'goodsUnit',
            'goodsSpec',
            'recommDesc',
            'goodsDesc',
            'markIcon',
            'isDistribution',
            'firstDistribution',
            'SecondaryDistribution',
            'isMembershipExclusive',
            'memberPrice',
            'integralReward',
            'IntelligentRemark',
            'isFlashSale',
            'isShopPreSale',
            'saleTime',
            'isNewPeople',
            'minBuyNum',
            'isLimitBuy',
            'limitCount',
            'isFlashSale',
            'limitCountActivityPrice',
            'unit'
        );
        $data = M('goods')
            ->where($where)
            ->field($array_field)
            ->order('saleCount asc')
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        $data = handleNewPeople($data, $userId);//剔除新人专享商品
        $data = rankGoodsPrice($data);
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;

        return $data;
    }

    //文章分类列表
    public function articleCats()
    {
        $array_field = array(
            'catId',
            'parentId',
            'catName'
        );

        $where['isShow'] = 1;
        $where['catFlag'] = 1;
        $data = M('article_cats')->where($where)->field($array_field)->order('catSort asc')->select();

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //文章列表 不传catId 默认返回所有文章
    public function areasList($catId = null)
    {
        $array_field = array(
            'a.articleId',
            'a.catId',
            'a.articleTitle',
            'a.articleImg',
            'a.articleKey',
            'a.createTime'
        );

        if ($catId != null) {
            $where['catId'] = $catId;
        }

//        $where['c.isShow'] = 1;
//        $where['c.catFlag'] = 1;
//        $where['a.isShow'] = 1;
//        $data = M('articles a')
//            ->join('left join wst_article_cats c on c.catId=a.catId')
//            ->where($where)
//            ->field($array_field)
//            ->order('a.createTime desc')
//            ->select();
        $where['isShow'] = 1;
        $data = M('articles')
            ->where($where)
            ->order('createTime desc')
            ->select();

        if (empty($data)) {
            $data = [];
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);

        return $apiRet;
    }

    //文章详情
    public function areasDetail($articleId)
    {
        $array_field = array(
            'articleId',
            'catId',
            'articleTitle',
            'articleImg',
            'articleContent',
            'articleKey',
            'createTime'
        );

        $where['articleId'] = $articleId;
        $where['isShow'] = 1;
        $data = M('articles')->where($where)->field($array_field)->find();

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='文章详情获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //对商品进行评价 暂不支持一个商品多个评论 - app
    public function goodAppraises($funData)
    {
        $mod_orders = M('orders');
        $mod_goods_appraises = M('goods_appraises');
        $mod_order_goods = M('order_goods');
        //判断订单是否是当前用户的
        $mod_orders_Res = $mod_orders->where("orderId = '{$funData['orderId']}' and orderStatus=4")->find();
        if ($mod_orders_Res['userId'] !== $funData['userId']) {
//            $apiRet['apiCode']='000075';
//            $apiRet['apiInfo']='订单与用户信息不符';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '订单与用户信息不符');
            return $apiRet;
        }

        //判断订单是否已经评价 如果已经评价 判断当前商品是否评价过
        if ($mod_orders_Res['isAppraises'] == 1) {
            $where['orderId'] = $funData['orderId'];
            $where['goodsId'] = $funData['goodsId'];
            if ($mod_goods_appraises->where($where)->count()) {
//                $apiRet['apiCode']='000076';
//                $apiRet['apiInfo']='该商品已评价过';
//                $apiRet['apiState']='error';
//                $apiRet['apiData']=null;
                $apiRet = returnData(null, -1, 'error', '该商品已评价过');

                return $apiRet;
            }
        }

        //判断订单下 是否有这个评价的商品
        unset($where);
        $where['orderId'] = $funData['orderId'];
        $where['goodsId'] = $funData['goodsId'];
        $mod_order_goodsRes = $mod_order_goods->where($where)->count();
        if (!$mod_order_goodsRes) {
//            $apiRet['apiCode']='000077';
//            $apiRet['apiInfo']='该订单下没有该商品';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '该订单下没有该商品');
            return $apiRet;
        }

        //进行评价 且更新订单评价状态

        //添加评价记录
        $add_data['shopId'] = $mod_orders_Res['shopId'];
        $add_data['orderId'] = $funData['orderId'];
        $add_data['goodsId'] = $funData['goodsId'];

        $add_data['userId'] = $funData['userId'];
        $add_data['goodsScore'] = $funData['goodsScore'];
        $add_data['serviceScore'] = $funData['serviceScore'];
        $add_data['timeScore'] = $funData['timeScore'];
        $add_data['content'] = $funData['content'];
        $add_data['createTime'] = date("Y-m-d H:i:s");
        $add_data['compScore'] = $funData['compScore'];

        if (empty($add_data['shopId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='shopId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'shopId字段为空');
            return $apiRet;
        }
        if (empty($add_data['orderId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='orderId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'orderId字段为空');
            return $apiRet;
        }
        if (empty($add_data['goodsId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='goodsId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'goodsId字段为空');

            return $apiRet;
        }
        if (empty($add_data['userId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='userId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'userId字段为空');

            return $apiRet;
        }
        if (empty($add_data['goodsScore'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='goodsScore字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'goodsScore字段为空');

            return $apiRet;
        }
        if (empty($add_data['serviceScore'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='serviceScore字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'serviceScore字段为空');

            return $apiRet;
        }
        if (empty($add_data['timeScore'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='timeScore字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'timeScore字段为空');

            return $apiRet;
        }
        if (empty($add_data['content'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='content字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'content字段为空');

            return $apiRet;
        }

        //如果有图片
        if (!empty($funData['appraisesAnnex'])) {
            $add_data['appraisesAnnex'] = $funData['appraisesAnnex'];
        }

        if ($mod_goods_appraises->add($add_data)) {

            //评价成功 更新订单状态
            $save_data['isAppraises'] = 1;
            $mod_orders->where("orderId = '{$funData['orderId']}'")->save($save_data);

            //评价成功后 判断是否是第一次评论是的话 就随机奖励积分 并在系统开启评价送积分的情况下
            if ($mod_orders_Res['isAppraises'] == 0 and $GLOBALS["CONFIG"]['isAppraisesScore'] == 1) {

                $num = explode("-", $GLOBALS["CONFIG"]['evaluateRange']);
                $Integral = rand($num[0], $num[1]);

                //积分累计到用户积分
//                M('users')->where("userId='{$funData['userId']}'")->setInc('userScore', $Integral);

                //积分处理-start
                $users_service_module = new UsersServiceModule();
                $score = (int)$Integral;
                $users_id = $funData['userId'];
                $result = $users_service_module->return_users_score($users_id, $score);
                if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return returnData(null, -1, 'error', $result['msg']);
                }
                //积分处理-end

                //添加积分记录
                $data['userId'] = $funData['userId'];
                $data['score'] = $Integral;
                $data['dataSrc'] = 2;
                $data['dataRemarks'] = "app评价商品获得";
                $data['scoreType'] = 1;
                $data['createTime'] = date('Y-m-d H:i:s');
                M('user_score')->add($data);
                $apiRet['apiData'] = array('Integral' => $Integral);
            }

//            $apiRet['apiCode']='000078';
//            $apiRet['apiInfo']='评价成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData();
            return $apiRet;
        } else {
//            $apiRet['apiCode']='000079';
//            $apiRet['apiInfo']='评价失败';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '评价失败');
            return $apiRet;
        }
    }

    //对商品进行评价 暂不支持一个商品多个评论 - 小程序
    public function xcxGoodAppraises($funData)
    {
        $mod_orders = M('orders');
        $mod_goods_appraises = M('goods_appraises');
        $mod_order_goods = M('order_goods');
        //判断订单是否是当前用户的
        $mod_orders_Res = $mod_orders->where("orderId = '{$funData['orderId']}' and orderStatus=4")->find();
        if ($mod_orders_Res['userId'] !== $funData['userId']) {
//            $apiRet['apiCode']='000075';
//            $apiRet['apiInfo']='订单与用户信息不符';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '订单与用户信息不符');
            return $apiRet;
        }

        //判断订单是否已经评价 如果已经评价 判断当前商品是否评价过
        if ($mod_orders_Res['isAppraises'] == 1) {
            $where['orderId'] = $funData['orderId'];
            $where['goodsId'] = $funData['goodsId'];
            if ($mod_goods_appraises->where($where)->count()) {
//                $apiRet['apiCode']='000076';
//                $apiRet['apiInfo']='该商品已评价过';
//                $apiRet['apiState']='error';
//                $apiRet['apiData']=null;
                $apiRet = returnData(null, -1, 'error', '该商品已评价过');
                return $apiRet;
            }
        }

        //判断订单下 是否有这个评价的商品
        unset($where);
        $where['orderId'] = $funData['orderId'];
        $where['goodsId'] = $funData['goodsId'];
        $mod_order_goodsRes = $mod_order_goods->where($where)->count();
        if (!$mod_order_goodsRes) {
//            $apiRet['apiCode']='000077';
//            $apiRet['apiInfo']='该订单下没有该商品';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '该订单下没有该商品');
            return $apiRet;
        }

        //进行评价 且更新订单评价状态

        //添加评价记录
        $add_data['shopId'] = $mod_orders_Res['shopId'];
        $add_data['orderId'] = $funData['orderId'];
        $add_data['goodsId'] = $funData['goodsId'];

        $add_data['userId'] = $funData['userId'];
        $add_data['goodsScore'] = $funData['goodsScore'];
        $add_data['serviceScore'] = $funData['serviceScore'];
        $add_data['timeScore'] = $funData['timeScore'];
        $add_data['content'] = $funData['content'];
        $add_data['createTime'] = date("Y-m-d H:i:s");
        $add_data['compScore'] = $funData['compScore'];

        if (empty($add_data['shopId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='shopId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'shopId字段为空');
            return $apiRet;
        }
        if (empty($add_data['orderId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='orderId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'orderId字段为空');

            return $apiRet;
        }
        if (empty($add_data['goodsId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='goodsId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'goodsId字段为空');

            return $apiRet;
        }
        if (empty($add_data['userId'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='userId字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'userId字段为空');

            return $apiRet;
        }
        if (empty($add_data['goodsScore'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = 'goodsScore字段为空';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        if (empty($add_data['serviceScore'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='serviceScore字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'serviceScore字段为空');

            return $apiRet;
        }
        if (empty($add_data['timeScore'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='timeScore字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'timeScore字段为空');

            return $apiRet;
        }
        if (empty($add_data['content'])) {
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='content字段为空';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', 'content字段为空');

            return $apiRet;
        }

        //如果有图片
        if (!empty($funData['appraisesAnnex'])) {
            $add_data['appraisesAnnex'] = $funData['appraisesAnnex'];
        }

        if ($mod_goods_appraises->add($add_data)) {

            //评价成功 更新订单状态
            $save_data['isAppraises'] = 1;
            $mod_orders->where("orderId = '{$funData['orderId']}'")->save($save_data);

            //评价成功后 判断是否是第一次评论是的话 就随机奖励积分 并在系统开启评价送积分的情况下
            if ($mod_orders_Res['isAppraises'] == 0 and $GLOBALS["CONFIG"]['isAppraisesScore'] == 1) {

                $num = explode("-", $GLOBALS["CONFIG"]['evaluateRange']);
                $Integral = rand($num[0], $num[1]);

                //积分累计到用户积分
                //M('users')->where("userId='{$funData['userId']}'")->setInc('userScore', $Integral);
                //积分处理-start
                $users_service_module = new UsersServiceModule();
                $score = (int)$Integral;
                $users_id = $funData['userId'];
                $result = $users_service_module->return_users_score($users_id, $score);
                if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return returnData(null, -1, 'error', $result['msg']);
                }
                //积分处理-end

                //添加积分记录
                $data['userId'] = $funData['userId'];
                $data['score'] = $Integral;
                $data['dataSrc'] = 2;
                $data['dataRemarks'] = "小程序评价商品获得";
                $data['scoreType'] = 1;
                $data['createTime'] = date('Y-m-d H:i:s');
                M('user_score')->add($data);
                $apiRet['apiData'] = array('Integral' => $Integral);
            }
//            $apiRet['apiCode']='000078';
//            $apiRet['apiInfo']='评价成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData($apiRet);
            return $apiRet;
        } else {
//            $apiRet['apiCode']='000079';
//            $apiRet['apiInfo']='评价失败';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=null;
            $apiRet = returnData(null, -1, 'error', '评价失败');
            return $apiRet;
        }
    }

    //订单-待评价列表-获取列表时自动判断用户超时评价列表 进行自动评价 但是不长积分
    public function timeEvaluate($userId)
    {
        $config = $GLOBALS['CONFIG'];
        $mod_orders = M('orders');
        $mod_order_goods = M('order_goods');
        $mod_goods = M('goods');
        $mod_shops = M('shops');
        $mod_goods_appraises = M('goods_appraises');
        //未在评价期限内给评价 系统自动评价
        $where['userId'] = $userId;
        $where['isAppraises'] = 0;
        $where['orderStatus'] = 4;
        $where['orderFlag'] = 1;
        //$where['receiveTime'] = array('exp',"DATE_ADD(receiveTime,INTERVAL 7 DAY) < NOW()");//thinkphp3.2的where条件bug 此条错误
        $where['receiveTime'] = array('exp', " and DATE_ADD(receiveTime,INTERVAL {$GLOBALS['CONFIG']['autoAppraiseDays']} DAY) < NOW()");//thinkphp3.2的where条件bug 此条正确 需要某些加入条件 或者不加条件 用and 等等都行
        $mod_orders_list = $mod_orders->where($where)->select();//获取未期限内评价的所有订单

        //echo M('orders')->_sql();
        $goodsModule = new GoodsModule();
        for ($i = 0; $i < count($mod_orders_list); $i++) {
//            if ($config['setDeliveryMoney'] == 2) {
//                if ($mod_orders_list[$i]['realTotalMoney'] < $config['deliveryFreeMoney']) {
//                    $mod_orders_list[$i]['realTotalMoney'] += $mod_orders_list[$i]['setDeliveryMoney'];
//                }
//            }

            $res_order_goods = $mod_order_goods->where("orderId = '{$mod_orders_list[$i]['orderId']}'")->select();
            //评价商品
            for ($j = 0; $j < count($res_order_goods); $j++) {

                $add_goods_appraises['shopId'] = $mod_orders_list[$i]['shopId'];
                $add_goods_appraises['orderId'] = $mod_orders_list[$i]['orderId'];
                $add_goods_appraises['goodsId'] = $res_order_goods[$j]['goodsId'];
                $add_goods_appraises['userId'] = $userId;
                $add_goods_appraises['goodsScore'] = 5;
                $add_goods_appraises['serviceScore'] = 5;
                $add_goods_appraises['timeScore'] = 5;
                $add_goods_appraises['content'] = '系统默认好评！';
                $add_goods_appraises['createTime'] = date("Y-m-d H:i:s");
                $mod_goods_appraises->add($add_goods_appraises);
            }

            //更新订单评价状态

            $save_mod_orders['isAppraises'] = 1;
            $mod_orders->where("orderId = '{$mod_orders_list[$i]['orderId']}'")->save($save_mod_orders);
        }

        //返回待评价列表
        unset($where);
        $field_name = array(
            'orderId',
            'orderNo',
            'shopId',
            'orderStatus',
            'totalMoney',
            'deliverMoney',
            'payType',
            'isSelf',
            'isPay',
            'deliverType',
            'userId',
            'createTime',
            'orderSrc',
            'payFrom',
            'receiveTime',
            'realTotalMoney',
            'isAppraises',
            'setDeliveryMoney'
        );
        $where['isAppraises'] = 0;
        $where['orderStatus'] = 4;
        $where['userId'] = $userId;
        $where['orderFlag'] = 1;
        $res_orders_list = $mod_orders->where($where)->field($field_name)->order('receiveTime desc')->select();//获取未期限内评价的所有订单
        //获取订单下所有的商品e

        unset($field_name);
        $field_name = array(
            'goodsImg',
            'shopId',
            'goodsStock',
            'saleCount',
            'goodsUnit',
            'goodsSpec',
            'isDistribution',
            'firstDistribution',
            'SecondaryDistribution',
            'SuppPriceDiff',

        );

        for ($i = 0; $i < count($res_orders_list); $i++) {
//            if ($config['setDeliveryMoney'] == 2) {
//                if ($res_orders_list[$i]['realTotalMoney'] < $config['deliveryFreeMoney']) {
//                    $res_orders_list[$i]['realTotalMoney'] += $res_orders_list[$i]['setDeliveryMoney'];
//                }
//            }
            $res_orders_list[$i]['goodsData'] = $mod_order_goods->where("orderId = '{$res_orders_list[$i]['orderId']}'")->select();

            //获取店铺头像 和 店铺姓名
            $mod_shop_resdata = $mod_shops->where("shopId = '{$res_orders_list[$i]['shopId']}'")->field(array('shopImg', 'shopName'))->find();
            $res_orders_list[$i]['shopImg'] = $mod_shop_resdata['shopImg'];
            $res_orders_list[$i]['shopName'] = $mod_shop_resdata['shopName'];

            for ($j = 0; $j < count($res_orders_list[$i]['goodsData']); $j++) {
                $oneGood = $mod_goods->where("goodsId = '{$res_orders_list[$i]['goodsData'][$j]['goodsId']}'")->field($field_name)->find();
                $res_orders_list[$i]['goodsData'][$j]['unit'] = $goodsModule->getGoodsUnitByParams($res_orders_list[$i]['goodsData'][$j]['goodsId'], $res_orders_list[$i]['goodsData'][$j]['skuId']);
                $res_orders_list[$i]['goodsData'][$j]['goodsNums'] = (float)$res_orders_list[$i]['goodsData'][$j]['goodsNums'];
                $res_orders_list[$i]['goodsData'][$j]['totalGoodsPrice'] = sprintfNumber((float)$res_orders_list[$i]['goodsData'][$j]['goodsPrice'] * $res_orders_list[$i]['goodsData'][$j]['goodsNums']);
                $res_orders_list[$i]['goodsData'][$j]['SuppPriceDiff'] = $oneGood['SuppPriceDiff'];
                $res_orders_list[$i]['goodsData'][$j]['goodsImg'] = $oneGood['goodsImg'];
                $res_orders_list[$i]['goodsData'][$j]['shopId'] = $oneGood['shopId'];
                $res_orders_list[$i]['goodsData'][$j]['goodsStock'] = $oneGood['goodsStock'];
                $res_orders_list[$i]['goodsData'][$j]['saleCount'] = $oneGood['saleCount'];
                $res_orders_list[$i]['goodsData'][$j]['saleCount'] = $oneGood['saleCount'];
                $res_orders_list[$i]['goodsData'][$j]['goodsSpec'] = $oneGood['goodsSpec'];
            }
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='待评价列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$res_orders_list;
        $apiRet = returnData($res_orders_list);
        return $apiRet;
    }

    //邀请的历史积分 以及 人数 和列表
    public function getInvitationInfo($userId)
    {
        $mod_users = M('users');
        $mod_user_Invitation = M('user_invitation');

        $res_user_data = $mod_users->where("userId = '{$userId}'")->field(array('HistoIncoInte', 'HistoIncoNum'))->find();
        $res_user_data_list = $mod_user_Invitation->where("userId = '{$userId}'")->order('createTime desc')->limit(10)->select();

        for ($i = 0; $i < count($res_user_data_list); $i++) {
            $tmpData = $mod_users->where("userId = '{$res_user_data_list[$i]['UserToId']}' and userFlag=1")->field(array('loginName', 'userPhoto', 'userName'))->find();
            if (empty($tmpData)) {
                unset($res_user_data_list[$i]);
                continue;
            }
            $res_user_data_list[$i]['loginName'] = $tmpData['loginName'];
            $res_user_data_list[$i]['userPhoto'] = $tmpData['userPhoto'];
            $res_user_data_list[$i]['userName'] = $tmpData['userName'];
        }

        $res_user_data['userList'] = empty($res_user_data_list) ? array() : $res_user_data_list;

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='邀请记录获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$res_user_data;
        $apiRet = returnData($res_user_data);
        return $apiRet;
    }

    //邀请的历史积分 以及 人数 和列表
    public function getCacheInvitationInfo($userId)
    {
        $mod_users = M('users');
        $mod_invite_cache_record = M('invite_cache_record');
        $mod_orders = M('orders');

        $regetInvitationInfos_user_data = $mod_users->where("userId = '{$userId}'")->field(array('HistoIncoInte'))->find();
        $HistoIncoNum = $mod_invite_cache_record->where(array('inviterId' => $userId))->count();
        $res_user_data['HistoIncoNum'] = ($HistoIncoNum > 0) ? $HistoIncoNum : 0;
        $res_user_data_list = $mod_invite_cache_record->where("inviterId = '{$userId}'")->order('createTime desc')->limit(10)->select();

        for ($i = 0; $i < count($res_user_data_list); $i++) {
            $tmpData = $mod_users->where("userPhone = '{$res_user_data_list[$i]['inviteePhone']}'")->field(array('loginName', 'userPhoto'))->find();
            $res_user_data_list[$i]['loginName'] = $tmpData['loginName'];
            $res_user_data_list[$i]['userPhoto'] = $tmpData['userPhoto'];
            $res_user_data_list[$i]['state'] = empty($tmpData) ? 1 : 2;// 状态，1：已邀请 2：已注册 3：已下单
            $res_user_data_list[$i]['stateContent'] = empty($tmpData) ? '已邀请' : '已注册';// 状态，1：已邀请 2：已注册 3：已下单

            if (!empty($tmpData)) {
                $order_info = $mod_orders->where(array('userId' => $tmpData['userId'], 'orderStatus' => 4, 'orderFlag' => 1))->find();
                if (!empty($order_info)) {
                    $res_user_data_list[$i]['state'] = 3;// 状态，1：已邀请 2：已注册 3：已下单
                    $res_user_data_list[$i]['stateContent'] = '已下单';// 状态，1：已邀请 2：已注册 3：已下单
                }
            }
        }

        $res_user_data['userList'] = $res_user_data_list;
//
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='邀请记录获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$res_user_data;
        $apiRet = returnData($res_user_data);
        return $apiRet;
    }

    //获取商城预售列表 分页  目前预售的展示 只在店铺营业期间 后期可能会修改为预售的商品 不依赖店铺营业时间
    public function PreSaleList($adcode, $lat, $lng, $page)
    {

        $data = S("NIAO_CACHE_app_PreSaleList_{$adcode}_{$page}");
        if (empty($data)) {
            $pageDataNum = 10;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购


//            $where['wst_goods.AdminShopGoodPreSaleStartTime'] = array('ELT',date("Y-m-d H:i:s"));
//            $where['wst_goods.AdminShopGoodPreSaleEndTime'] = array('EGT',date("Y-m-d H:i:s"));
//            $where['wst_goods.isAdminShopPreSale'] = 1;//商城预售

            $where['wst_goods.isShopPreSale'] = 1;//店铺必须为预售 否则商城预售没有任何意义

            $where['wst_goods.isShopSecKill'] = 0;//店铺秒杀状态必须为0 未开启秒杀的商品
            $where['wst_goods.isAdminShopSecKill'] = 0;//商城秒杀状态必须为0 未开启秒杀的商品

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("bookQuantity desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isAdminShopPreSale,wst_goods.AdminShopGoodPreSaleStartTime,wst_goods.AdminShopGoodPreSaleEndTime,wst_goods.bookQuantity,wst_goods.goodsStock,wst_goods.markIcon,wst_goods.goodsCompany,wst_shops.shopName')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();

            $data = rankGoodsPrice($data);
            S("NIAO_CACHE_app_PreSaleList_{$adcode}_{$page}", $data, C("allApiCacheTime"));

        }


        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序


//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商城预售列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;

    }

    //店铺预售商品列表
    public function ShopPreSaleAllLists($shopId, $userId = 0)
    {
        $data = S("NIAO_CACHE_app_ShopPreSaleAllLists_{$shopId}");
        if (empty($data)) {

            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopId"] = $shopId;
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            //$where["wst_shops.serviceStartTime"] =  array('ELT',(float)$newTime);//店铺营业时间;  预售 不受店铺营业时间影响
            //$where["wst_shops.serviceEndTime"] = array('EGT',(float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.ShopGoodPreSaleStartTime'] = array('ELT', date("Y-m-d H:i:s"));
            $where['wst_goods.ShopGoodPreSaleEndTime'] = array('EGT', date("Y-m-d H:i:s"));

            $where['wst_goods.isShopPreSale'] = 1;//店铺商品必须为预售

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("bookQuantity desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopPreSale,wst_goods.ShopGoodPreSaleStartTime,wst_goods.ShopGoodPreSaleEndTime,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.markIcon,wst_goods.IntelligentRemark,wst_goods.isNewPeople')
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_app_ShopPreSaleAllLists_{$shopId}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商城预售列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    //店铺商品搜索 店铺参数需要关联查询 重构要注意----------
    public function mallSearch($shopId, $wd, $page, $salesSort, $Price, $userId = 0)
    {
        //商品条件
        $where["isSale"] = 1;
        $where["goodsStatus"] = 1;
        $where["goodsFlag"] = 1;
        $where["shopId"] = $shopId;
//        $where["isFlashSale"] = 0;//是否限时
//        $where["isLimitBuy"] = 0;//是否限量购
        $where['goodsName'] = array('like', "%{$wd}%");
        $order = array('saleCount' => 'desc');
        if (!empty($salesSort)) {
            $order['saleCount'] = $salesSort;
        }
        if (!empty($Price)) {
            if (empty($salesSort)) {
                unset($order['saleCount']);
            }
            $order['shopPrice'] = $Price;
        }

        $Model = M('goods');
        $data = $Model
            ->where($where)
            ->order($order)
            ->field('goodsStock,goodsId,goodsName,goodsImg,goodsThums,marketPrice,shopPrice,saleCount,goodsUnit,goodsSpec,isAdminShopPreSale,AdminShopGoodPreSaleStartTime,AdminShopGoodPreSaleEndTime,bookQuantity,wst_goods.shopId,isDistribution,firstDistribution,SecondaryDistribution,isMembershipExclusive,memberPrice,integralReward,IntelligentRemark,isFlashSale,saleTime,isNewPeople,minBuyNum,unit,isLimitBuy,limitCount,limitCountActivityPrice,goodsStock,selling_stock')
            ->limit(($page['page'] - 1) * $page['pageSize'], $page['pageSize'])
            ->select();
        $data = handleNewPeople($data, $userId);//剔除新人专享商品
        $data = rankGoodsPrice($data); //商品等级价格处理
        //增加店铺参数
        if ($data) {
            $shops = M('shops')->where('shopId=' . $shopId)->field('shopId,avgeCostMoney,deliveryStartMoney')->find();
            foreach ($data as $key => &$value) {
                $value['avgeCostMoney'] = $shops['avgeCostMoney'];
                $value['deliveryStartMoney'] = $shops['deliveryStartMoney'];
            }
        }
        //END

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='搜索结果获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        return (array)$data;
    }

    /**
     * 商城-商城搜索
     * @param int shopId 门店id,传shopId就是前置仓模式,否则就是多商户模式
     * @param varchar wd 关键字
     * @param int adcode 区域id 废弃
     * @param float lat 纬度
     * @param float lng 经度
     * @param int dataFormat 返回数据格式【1：以商品列表形式展示|2：店铺信息中包含商品列表】
     * @param int page 页码
     * @param int pageSize 分页条数
     * */
    public function adminMallSearch($shopId, $userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat)
    {
        if ($shopId > 0) {
            //前置仓
            $data = $this->shopGoodsSearch($shopId, $userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat);
        } else {
            //多商户
            $data = $this->mallGoodsSearch($userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat);
        }
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    /**
     * 获取店铺评分
     * @param int $shopId
     */
    public function getShopScores(int $shopId)
    {
        $data = [];
        $data['goodsScore'] = 0;//商品评分
        $data['serviceScore'] = 0;//服务评分
        $data['timeScore'] = 0;//时效评分
        $data['zfScore'] = 0;//综合评分
        $sql = "SELECT totalScore,totalScore ,goodsScore,goodsUsers,serviceScore,serviceUsers,timeScore,timeUsers
				FROM __PREFIX__shop_scores WHERE shopId = $shopId";
        $scores = $this->queryRow($sql);
        if (empty($scores)) {
            return returnData($data);
        }
        $goodsScore = $scores["goodsUsers"] ? sprintf('%.1f', $scores["goodsScore"] / $scores["goodsUsers"]) : 0;
        $timeScore = $scores["timeUsers"] ? sprintf('%.1f', $scores["timeScore"] / $scores["timeUsers"]) : 0;
        $serviceScore = $scores["serviceUsers"] ? sprintf('%.1f', $scores["serviceScore"] / $scores["serviceUsers"]) : 0;
        $data["goodsScore"] = (float)$goodsScore;
        $data["timeScore"] = (float)$timeScore;
        $data["serviceScore"] = (float)$serviceScore;
        $data["zfScore"] = (float)(sprintf('%.1f', ($goodsScore + $timeScore + $serviceScore) / 3));
        return returnData($data);
    }

    /**
     * 店铺商品搜索
     * @param int shopId 门店id,传shopId就是前置仓模式,否则就是多商户模式
     * @param varchar wd 关键字
     * @param int adcode 区域id
     * @param float lat 纬度
     * @param float lng 经度
     * @param int dataFormat 返回数据格式【1：以商品列表形式展示|2：店铺信息中包含商品列表】
     * @param int page 页码
     * @param int pageSize 分页条数
     * **/
    public function shopGoodsSearch($shopId, $userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat)
    {
        $data = S("NIAO_CACHE_app_mallSearch_{$lat}-{$lng}_{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }
//            $newTime = date('H').'.'.date('i');//获取当前时间
            //店铺条件
            $where["wst_shops.shopId"] = $shopId;
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
//            $where["wst_shops.shopAtive"] = 1;
//            $where["wst_shops.serviceStartTime"] =  array('ELT',(float)$newTime);//店铺营业时间;
//            $where["wst_shops.serviceEndTime"] = array('EGT',(float)$newTime);//店铺休息时间;
            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购
            $where['goodsName'] = array('like', "%{$wd}%");
            //配送区域条件
//            $where["wst_shops_communitys.areaId3"] = $adcode;
            $order = array('wst_goods.saleCount' => 'desc');
            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                //->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order($order)
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isAdminShopPreSale,wst_goods.AdminShopGoodPreSaleStartTime,wst_goods.AdminShopGoodPreSaleEndTime,wst_goods.bookQuantity,wst_goods.goodsStock,wst_shops.deliveryStartMoney,wst_shops.avgeCostMoney,wst_shops.shopName,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.isNewPeople,wst_goods.IntelligentRemark')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();
            $data = rankGoodsPrice($data);
            $data = getGoodsSku($data);//获取商品的sku
            $data = goodsRules($data, $userId, "*");//验证商品的有效性
            S("NIAO_CACHE_app_mallSearch_{$lat}-{$lng}_{$page}", $data, C("allApiCacheTime"));
        }
        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }
        $shopsDataSort = array();
        foreach ($data as $dataVal) {
            $shopsDataSort[] = $dataVal['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序
        if ($dataFormat == 2) {
            //店铺信息包含商品列表
            $shopTab = M('shops');
            $where = [];
            $where['shopId'] = $shopId;
            $where['shopFlag'] = 1;
            $field = 'shopId,shopImg,isSelf,shopName,shopCompany,shopTel,shopAddress,avgeCostMoney,deliveryStartMoney,deliveryMoney,deliveryFreeMoney,deliveryCostTime,shopAtive,latitude,longitude';
            $shopInfo = $shopTab->where($where)->field($field)->find();
            $shopInfo['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($shopInfo['latitude'], $shopInfo['longitude'], $lat, $lng)['kilometers']);
            $returnData = [];
            $returnData['shops'] = !empty($shopInfo) ? $shopInfo : [];
            $returnData['shops']['goods'] = !empty($data) ? $data : [];
            return $returnData;
        }
        return $data;
    }

    /**
     * 商城商品搜索
     * @param varchar wd 关键字
     * @param int adcode 区域id 废弃
     * @param float lat 纬度
     * @param float lng 经度
     * @param int dataFormat 返回数据格式【1：以商品列表形式展示|2：店铺信息中包含商品列表】
     * @param int page 页码
     * @param int pageSize 分页条数
     * **/
    public function mallGoodsSearch($userId, $lat, $lng, $page, $pageSize, $wd, $dataFormat)
    {
//        $data = S("NIAO_CACHE_app_mallSearch_{$adcode}_{$page}");
        if (empty($data)) {
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }
//            $newTime = date('H').'.'.date('i');//获取当前时间
            //店铺条件
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return array();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["wst_shops.shopId"] = array("in", $shopIdArr);

            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
//            $where["wst_shops.shopAtive"] = 1;
//            $where["wst_shops.serviceStartTime"] =  array('ELT',(float)$newTime);//店铺营业时间;
//            $where["wst_shops.serviceEndTime"] = array('EGT',(float)$newTime);//店铺休息时间;
            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购
            $where['goodsName'] = array('like', "%{$wd}%");
            //配送区域条件
//            $where["wst_shops_communitys.areaId3"] = $adcode;
            $order = array('wst_goods.saleCount' => 'desc');
            //直接复制之前的,表别名,字段别名就不重新定义了
            if ($dataFormat == 1) {
                //适用于以商品列表的形式展示数据
                $goodsTab = M('goods');
                //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
                $data = $goodsTab
                    ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
//                    ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                    ->group('wst_goods.goodsId')
                    ->where($where)
                    ->order($order)
                    ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isAdminShopPreSale,wst_goods.AdminShopGoodPreSaleStartTime,wst_goods.AdminShopGoodPreSaleEndTime,wst_goods.bookQuantity,wst_goods.goodsStock,wst_shops.deliveryStartMoney,wst_shops.avgeCostMoney,wst_shops.shopName,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.isNewPeople,wst_goods.IntelligentRemark')
                    ->limit(($page - 1) * $pageSize, $pageSize)
                    ->select();
                $data = rankGoodsPrice($data);
                $validate = '*';
                $data = goodsRules($data, $userId, $validate);//剔除无效商品
                if (empty($data)) {
                    return [];
                }
                //计算距离 不参与缓存
                for ($i = 0; $i <= count($data) - 1; $i++) {
                    $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);//店铺距离
                }
                $shopsDataSort = array();
                foreach ($data as $user) {
                    $shopsDataSort[] = $user['kilometers'];
                }
                array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序
            }
            if ($dataFormat == 2) {
                //适用于以店铺列表下包含商品的形式展示
                $shopTab = M('shops');
                $goodsTab = M('goods');
                $field = 'wst_shops.shopId,wst_shops.shopImg,wst_shops.isSelf,wst_shops.shopName,wst_shops.shopCompany,wst_shops.shopTel,wst_shops.shopAddress,wst_shops.avgeCostMoney,wst_shops.deliveryStartMoney,wst_shops.deliveryMoney,wst_shops.deliveryFreeMoney,wst_shops.deliveryCostTime,wst_shops.shopAtive,wst_shops.latitude,wst_shops.longitude,wst_shops.deliveryTime,wst_shops.deliveryType,wst_shops.serviceStartTime,wst_shops.serviceEndTime';
                $data = $shopTab
//                    ->join('left join wst_shops_communitys on wst_shops_communitys.shopId=wst_shops.shopId')
                    ->join('left join wst_goods on wst_goods.shopId=wst_shops.shopId')
                    ->where($where)
                    ->field($field)
                    ->group("wst_shops.shopId")
                    ->limit(($page - 1) * $pageSize, $pageSize)
                    ->select();
                if (empty($data)) {
                    return [];
                }
                $shopIdArr = [];
                foreach ($data as $key => $val) {
                    $shopId = $val['shopId'];
                    //计算店铺好评
                    $shopScores = $this->getShopScores($val['shopId'])['data'];
                    $data[$key]['goodsScore'] = $shopScores['goodsScore'];//商品评分(5分制)
                    $data[$key]['serviceScore'] = $shopScores['serviceScore'];//服务评分(5分制)
                    $data[$key]['timeScore'] = $shopScores['timeScore'];//时效评分(5分制)
                    $data[$key]['zfScore'] = $shopScores['zfScore'];//综合评分(5分制)
                    //获取店铺的月销量
                    $data[$key]['monthSale'] = $this->getShopMonthSale($shopId);
                    //店铺优惠券
                    $data[$key]['coupons'] = $this->getShopCoupons($shopId, $userId)['data'];
                    //计算距离 不参与缓存
                    $data[$key]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($val['latitude'], $val['longitude'], $lat, $lng)['kilometers']);//店铺距离
                    $data[$key]['goods'] = [];
                    $shopIdArr[] = $val['shopId'];
                }
                $shopsDataSort = array();
                foreach ($data as $dataVal) {
                    $shopsDataSort[] = $dataVal['kilometers'];
                }
                array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序
                $field = "wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_goods.isAdminShopPreSale,wst_goods.AdminShopGoodPreSaleStartTime,wst_goods.AdminShopGoodPreSaleEndTime,wst_goods.bookQuantity,wst_goods.goodsStock,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.isNewPeople,wst_goods.IntelligentRemark,wst_goods.goodsSpec,wst_goods.isBest,wst_goods.isHot,wst_goods.isRecomm,wst_goods.isNew,wst_goods.isAdminBest,wst_goods.isAdminRecom,wst_goods.isShopRecomm,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.isAdminShopSecKill,wst_goods.AdminShopGoodSecKillStartTime,wst_goods.AdminShopGoodSecKillEndTime,wst_goods.isShopPreSale,wst_goods.ShopGoodPreSaleStartTime,wst_goods.ShopGoodPreSaleEndTime,wst_goods.isAdminShopPreSale,wst_goods.AdminShopGoodPreSaleStartTime,wst_goods.AdminShopGoodPreSaleEndTime";
                $goods = $goodsTab
                    ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
//                    ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                    ->group('wst_goods.goodsId')
                    ->where($where)
                    ->order($order)
                    ->field($field)
                    ->limit(20)
                    ->select();
                $goods = rankGoodsPrice($goods);
                $validate = '*';
                $goods = goodsRules($goods, $userId, $validate);//剔除无效商品
                if (!empty($goods)) {
                    $orderGoodsTab = M('order_goods');
                    $thisMonth = date('m');
                    $thisYear = date('Y');
                    $startMonth = $thisYear . '-' . $thisMonth . '-1' . ' 00:00:00';
                    $endMonth = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($startMonth)) . ' 23:59:59';
                    foreach ($data as $key => $val) {
                        foreach ($goods as $goodsVal) {
                            if ($goodsVal['shopId'] == $val['shopId']) {
                                $where = [];
                                $where['wst_order_goods.goodsId'] = $goodsVal['goodsId'];
                                $where['wst_orders.orderFlag'] = 1;
                                $where['wst_orders.isPay'] = 1;
                                $where['wst_orders.createTime'] = ['between', [$startMonth, $endMonth]];
                                //月销量
                                $goodsVal['monthSale'] = (int)$orderGoodsTab
                                    ->join('left join wst_orders on wst_orders.orderId=wst_order_goods.orderId')
                                    ->where($where)
                                    ->sum('goodsNums');
                                $goodsVal['goodsAppraises'] = (float)$this->goodDetailAppraises($goodsVal['goodsId'])['data']['zfScore'];//调用已有的方法获取商品好评率
                                $data[$key]['goods'][] = $goodsVal;
                            }
                        }
                    }
                }
                foreach ($data as $key => $val) {
                    if (count($val['goods']) <= 0) {
                        unset($data[$key]);
                    }
                }
                $data = array_values($data);
            }
//            S("NIAO_CACHE_app_mallSearch_{$adcode}_{$page}", $data, C("allApiCacheTime"));
        }
        return $data;
    }

    /**
     * 获取店铺的月销量
     * @param int shopId 店铺id
     * */
    public function getShopMonthSale(int $shopId)
    {
        if (empty($shopId)) {
            return 0;
        }
        $where = [];
        $where['o.shopId'] = $shopId;
        $where['o.orderFlag'] = 1;
        $where['o.isPay'] = 1;
        $res = M('order_goods og')
            ->join('left join wst_goods g on g.goodsId=og.goodsId')
            ->join('left join wst_orders o on o.orderId=og.orderId')
            ->group('o.orderId')
            ->where($where)
            ->sum('og.goodsNums');
        return (int)$res;
    }

    /**
     * 根据店铺id获取店铺优惠券(包含已领取和未领取),用于京东到家模式,店铺搜索
     * @param int $shopId 店铺id
     * @param int $userId 用户id
     * */
    public function getShopCoupons($shopId, $userId)
    {
        $where = [];
        $where['shopId'] = $shopId;
        $where['couponType'] = 1;
        $where['dataFlag'] = 1;//有效
        $where['type'] = 2;//店铺
        $where["validStartTime"] = array('ELT', date('Y-m-d'));
        $where["validEndTime"] = array('EGT', date('Y-m-d'));
        $couponList = M('coupons')->where($where)->select();
        if (empty($couponList)) {
            return returnData([]);
        }
        $couponIdArr = [];
        foreach ($couponList as $key => $value) {
            $couponList[$key]['isReceive'] = 0;//是否已领取【0:未领取|1:已领取】
            $couponList[$key]['shortDesc'] = '领' . $value['couponName'];
            if (!empty($userId)) {
                $couponIdArr[] = $value['couponId'];
            }
        }
        $userCouponTab = M('coupons_users');
        $userCouponWhere = [];
        $userCouponWhere['userId'] = $userId;
        $userCouponWhere['couponId'] = ['IN', $couponIdArr];
        $userCouponList = $userCouponTab->where($userCouponWhere)->select();
        if (empty($userCouponList)) {
            return returnData($couponList);
        }
        foreach ($userCouponList as $ukey => $uval) {
            foreach ($couponList as $ckey => $cval) {
                if ($uval['couponId'] == $cval['couponId']) {
                    $couponList[$key]['isReceive'] = 1;
                    $couponList[$key]['shortDesc'] = '已领' . $value['couponName'];
                }
            }
        }
        return returnData($couponList);
    }

    //提交订单-预售 实在蛋疼 其他提交订单都未加入事务操作 很多地方需要添加 后续添加吧
    //app
//    public function PreSaleSub($userId, $addressId, $goodsId, $goodsNum = 1, $lng, $lat)
//    {
//        $mod_goods = M('goods');
//        $mod_user_address = M('user_address');
//        $orderids = M('orderids');
//        $order_communitys_mod = M('communitys');
//        $mod_shops = M('shops');
//        $mod_orders = M('orders');
//        $mod_order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $sm = D('Home/Shops');
//
//        $where['goodsId'] = $goodsId;
//        $mod_goods_find_data = $mod_goods->where($where)->find();//获取商品信息
//        $mod_goods_find_data = rankGoodsPrice($mod_goods_find_data); //商品等级价格
//        $mod_user_address_data = $mod_user_address->where("addressFlag = 1 and addressId = '{$addressId}'")->find();//获取地址信息
//        $mod_shops_data = $mod_shops->where("shopId = '{$mod_goods_find_data['shopId']}'")->find();//获取店铺信息
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);
//
//        if (empty($mod_goods_find_data)) {
////            $apiRet['apiCode']='000084';
////            $apiRet['apiInfo']='不存在的商品';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '不存在的商品');
//            return $apiRet;
//        }
//
//        //判断商品是否在售
//        if ($mod_goods_find_data['isSale'] == 0) {
////            $apiRet['apiCode']='000085';
////            $apiRet['apiInfo']='商品未上架';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品未上架');
//
//            return $apiRet;
//        }
//
//        $checkGoodsFlashSale = checkGoodsFlashSale($goodsId); //检查商品限时状况
//        if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//            return $checkGoodsFlashSale;
//        }
//
//        //判断是否为限制下单次数的商品 start
//        $checkRes = checkGoodsOrderNum($goodsId, $userId);
//        if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//            return $checkRes;
//        }
//
//        if ($mod_goods_find_data['goodsStatus'] == 0) {
////            $apiRet['apiCode']='000086';
////            $apiRet['apiInfo']='商品禁售或未审核';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品禁售或未审核');
//            return $apiRet;
//        }
//        if ($mod_goods_find_data['goodsFlag'] == 0) {
////            $apiRet['apiCode']='000087';
////            $apiRet['apiInfo']='商品已删除';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品已删除');
//
//            return $apiRet;
//        }
//
//        //商品是否是店铺预售商品
//        if ($mod_goods_find_data['isShopPreSale'] == 0) {
////            $apiRet['apiCode']='000088';
////            $apiRet['apiInfo']='该商品非预售状态';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品非预售状态');
//
//            return $apiRet;
//        }
//
//        //预售是否结束
//        if (strtotime($mod_goods_find_data['ShopGoodPreSaleEndTime']) < time()) {
////            $apiRet['apiCode']='000089';
////            $apiRet['apiInfo']='该商品已结束预售';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品已结束预售');
//
//            return $apiRet;
//        }
//
//        //地址是否存在
//        if (empty($mod_user_address_data)) {
////            $apiRet['apiCode']='000090';
////            $apiRet['apiInfo']='请添加收货地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//
//            return $apiRet;
//        }
//
//        //地址是否属于本人
//        if ($mod_user_address_data['userId'] !== $userId) {
////            $apiRet['apiCode']='000091';
////            $apiRet['apiInfo']='请使用本人的地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请使用本人的地址');
//
//            return $apiRet;
//        }
//
//        //商品库存是否充足
//
//        if ($mod_goods_find_data['goodsStock'] < $goodsNum) {
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'], -1, 'error', "库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}");
//
//            return $apiRet;
//        }
//
//        $shopConfig = M('shop_configs')->where(['shopId' => $mod_goods_find_data['shopId']])->find();
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $mod_user_address_data['areaId3']);
//            if (!empty($isDistriScope)) {
//                return $isDistriScope;
//            }
//        }
//
//        //检测配送区域
//        /*if(!$lng || !$lat){
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="，参数缺失：经纬度";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'],-1,'error','参数缺失：经纬度');
//            return $apiRet;
//        }*/
//
//        if ($shopConfig['deliveryLatLngLimit'] == 1) {
//            $dcheck = checkShopDistribution($mod_goods_find_data['shopId'], $mod_user_address_data['lng'], $mod_user_address_data['lat']);
//            if (!$dcheck) {
//                unset($apiRet);
////                $apiRet['apiCode']='000093';
////                $apiRet['apiInfo']='配送范围超出';
//                $apiRet['goodsId'] = $mod_goods_find_data['goodsId'];
//                $apiRet['goodsName'] = $mod_goods_find_data['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//                return $apiRet;
//            }
//            //END
//        }
//
//        //生成订单数据
//        //订单公用参数
//        $data["areaId1"] = $mod_user_address_data["areaId1"];
//        $data["areaId2"] = $mod_user_address_data["areaId2"];
//        $data["areaId3"] = $mod_user_address_data["areaId3"];
//        $data["payType"] = "1";//支付方式
//        $data["isSelf"] = "0";//是否自提
//        $data["isPay"] = "0";//是否支付
//        $data["defPreSale"] = "1";//是否是预售订单 1是
//
//        $data["userId"] = $userId;//用户Id
//        $data["userName"] = $mod_user_address_data["userName"];//收货人名称
//        $data["communityId"] = $mod_user_address_data["communityId"];//收货地址所属社区id
//
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $mod_user_address_data['setaddress'] .
//            $mod_user_address_data["address"];//收件人地址
//
//        $data["userPhone"] = $mod_user_address_data["userPhone"];//收件人手机
//        $data["userPostCode"] = $mod_user_address_data["postCode"];//收件人邮编
//        $data["isInvoice"] = "0";//是否需要发票
//        $data["orderRemarks"] = "APP下单";//订单备注
//        $data["requireTime"] = date("Y-m-d H:i:s", time() + 3600);//要求送达时间
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "3";//订单来源
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = "2";//支付来源
//
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        $data["useScore"] = "0";//本次交易使用的积分数
//        $data["scoreMoney"] = "0";//积分兑换的钱 完成交易 确认收货在给积分
//
//        //生成订单号
//        $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//        $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//        $data["orderNo"] = $orderNo;//订单号
//        $data["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//
//        $data["orderStatus"] = "12";//订单状态 预售订单（未支付）
//        $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//
//        //减去对应商品总库存
//        $where = null;
//        $where["goodsId"] = $goodsId;
//        $goods_num = gChangeKg($goodsId, $goodsNum, 1);
//        $mod_goods->where($where)->setDec('goodsStock', $goods_num);
//        //增加预定量
//        $mod_goods->where($where)->setInc('bookQuantity', $goods_num);
//
//        //更新进销存系统商品的库存
//        //updateJXCGoodsStock($goodsId, $goodsNum, 1);
//
//        //获取当前订单商品总价
//        $mod_users = M('users');
//        $goods_integral = 0;
//        $totalMoney = (float)$mod_goods_find_data["shopPrice"] * (int)$goodsNum;
//        $goodsPrice = $mod_goods_find_data["shopPrice"];
//        $users = $mod_users->where(array('userId' => $userId))->find();
//        if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//            //如果设置了会员价，则使用会员价，否则使用会员折扣
//            if ($mod_goods_find_data['memberPrice'] > 0) {
//                $totalMoney = (float)$mod_goods_find_data['memberPrice'] * (int)$goodsNum;
//                $goods_integral = (int)$goodsNum * $mod_goods_find_data['integralReward'];
//                $goodsPrice = $mod_goods_find_data['memberPrice'];
//            } else {
//                if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                    $totalMoney = $totalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                    $goodsPrice = $mod_goods_find_data["shopPrice"] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                }
//            }
//        }
//        $data["totalMoney"] = (float)$totalMoney;//商品总金额
//        //$data["orderScore"] = floor($totalMoney+$goods_integral)*$rewardScoreMultiple;//所得积分
//        $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney) + $goods_integral;//所得积分
//        $data["deliverMoney"] = $mod_shops_data["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//        $data["deliverType"] = "1";//配送方式 默认门店配送
//
////        $mod_goods_find_data['PreSalePayPercen'] = 100; //应前端要求,预付改为全付
////        $PreSalePay = $mod_goods_find_data['PreSalePayPercen']/100*$totalMoney;//获取首付款价格
//        $PreSalePay = $totalMoney;//获取首付款价格
//
//        if (0 >= $PreSalePay) {
////            $apiRet['apiCode']='000097';
////            $apiRet['apiInfo']='订单金额异常！';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '订单金额异常！');
//            return $apiRet;
//        }
//
//        $PreSalePayPercen = $mod_goods_find_data['PreSalePayPercen'];//预付款百分比
//        $data["PreSalePayPercen"] = $PreSalePayPercen;
//        $data["PreSalePay"] = (float)$PreSalePay + (float)$data["deliverMoney"];//预付的金额+运费
//
//        //设置订单配送方式
//        if ($mod_shops_data["deliveryType"] == 4) {
//            $data["deliverType"] = "4";//配送方式 快跑者配送
//        }
//
//        //当前店铺是否是达达配送
//        if ($mod_shops_data["deliveryType"] == 2) {
//
//            $funData['shopId'] = $mod_goods_find_data['shopId'];
//            $funData['areaId2'] = $data["areaId2"];
//            $funData['orderNo'] = $data["orderNo"];
//            $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//            $funData['userName'] = $data["userName"];//收货人姓名
//            $funData['userAddress'] = $data["userAddress"];//收货人地址
//            $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//            $dadaresFun = self::dadaDeliver($funData);
//
//            if ($dadaresFun['status'] == -6) {
//                //获取城市出错
////                $apiRet['apiCode']='000093';
////                $apiRet['apiInfo']='提交失败，获取城市出错';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == -7) {
//                //不在达达覆盖城市内
////                $apiRet['apiCode']='000094';
////                $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == 0) {
//                //获取成功
//                $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//                $data["deliverMoney"] = $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//
//            }
//            $data["deliverType"] = "2";//配送方式 达达配送
//        }
//
//        $data["needPay"] = $totalMoney + $data["deliverMoney"];//需缴费用 加运费-----
//        $data["realTotalMoney"] = (float)$totalMoney + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//
//        $shop_info = $sm->getShopInfo($data["shopId"]);
//        $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//        $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//        //写入订单
//        $orderId = $mod_orders->add($data);
//
//        //建立订单记录
//        $data_order["orderId"] = $orderId;
//        $data_order["logContent"] = "APP预付下单成功，等待支付";
//        $data_order["logUserId"] = $data['userId'];
//        $data_order["logType"] = 0;
//        $data_order["logTime"] = date('Y-m-d H:i:s');
//        M('log_orders')->add($data_order);
//
//        //将订单商品写入order_goods
//
//        $data_order_goods["orderId"] = $orderId;
//        $data_order_goods["goodsId"] = $goodsId;
//        $data_order_goods["goodsNums"] = $goodsNum;
//        $data_order_goods["goodsPrice"] = $goodsPrice;
//        $data_order_goods["goodsAttrId"] = "0";
//        $data_order_goods["goodsName"] = $mod_goods_find_data["goodsName"];
//        $data_order_goods["goodsThums"] = $mod_goods_find_data["goodsThums"];
//        $data_order_goods["remarks"] = I('remarks', '', 'trim');
//        M('order_goods')->add($data_order_goods);
//
//        //限制商品下单次数
//        limitGoodsOrderNum($goodsId, $userId);
//
//        //建立订单提醒
//        $data_order_reminds["orderId"] = $orderId;
//        $data_order_reminds["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//        $data_order_reminds["userId"] = $data['userId'];
//        $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//        $order_reminds_statusCode = $mod_order_reminds->add($data_order_reminds);
//        // 获取生成的所有订单号 返回给APP支付
//        $wxorderNo = $orderNo;
//
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode($data["PreSalePay"]);//预定首款金额 单位元  加运费
//            $statusCode["orderNo"] = strencode($wxorderNo);//订单号  多个订单号用 A隔开
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5($wxorderNo);
//            $wst_order_merge_data['value'] = $wxorderNo;
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//            unset($apiRet);
////            $apiRet['apiCode']='000095';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//        } else {
//            unset($apiRet);
////            $apiRet['apiCode']='000096';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//    }

    //提交订单-预售
//    public function PreSaleSubSku($userId, $addressId, $goodsId, $goodsNum = 1, $lng, $lat, $skuId)
//    {
//        $mod_goods = M('goods');
//        $mod_user_address = M('user_address');
//        $orderids = M('orderids');
//        $order_communitys_mod = M('communitys');
//        $mod_shops = M('shops');
//        $mod_orders = M('orders');
//        $mod_order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $sm = D('Home/Shops');
//
//        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//
//        $where['goodsId'] = $goodsId;
//        $mod_goods_find_data = $mod_goods->where($where)->find();//获取商品信息
//        $mod_goods_find_data = rankGoodsPrice($mod_goods_find_data); //商品等级价格
//        $mod_user_address_data = $mod_user_address->where("addressFlag = 1 and addressId = '{$addressId}'")->find();//获取地址信息
//        $mod_shops_data = $mod_shops->where("shopId = '{$mod_goods_find_data['shopId']}'")->find();//获取店铺信息
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);
//
//        if (empty($mod_goods_find_data)) {
////            $apiRet['apiCode']='000084';
////            $apiRet['apiInfo']='不存在的商品';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '不存在的商品');
//            return $apiRet;
//        }
//
//        //判断商品是否在售
//        if ($mod_goods_find_data['isSale'] == 0) {
////            $apiRet['apiCode']='000085';
////            $apiRet['apiInfo']='商品未上架';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品未上架');
//
//            return $apiRet;
//        }
//
//
//        $checkGoodsFlashSale = checkGoodsFlashSale($goodsId); //检查商品限时状况
//        if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//            return $checkGoodsFlashSale;
//        }
//
//        //判断是否为限制下单次数的商品 start
//        $checkRes = checkGoodsOrderNum($goodsId, $userId);
//        if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//            return $checkRes;
//        }
//
//        if ($mod_goods_find_data['goodsStatus'] == 0) {
////            $apiRet['apiCode']='000086';
////            $apiRet['apiInfo']='商品禁售或未审核';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品禁售或未审核');
//
//            return $apiRet;
//        }
//        if ($mod_goods_find_data['goodsFlag'] == 0) {
////            $apiRet['apiCode']='000087';
////            $apiRet['apiInfo']='商品已删除';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品已删除');
//
//            return $apiRet;
//        }
//
//
//        //商品是否是店铺预售商品
//        if ($mod_goods_find_data['isShopPreSale'] == 0) {
////            $apiRet['apiCode']='000088';
////            $apiRet['apiInfo']='该商品非预售状态';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品非预售状态');
//
//            return $apiRet;
//        }
//
//
//        //预售是否结束
//        if (strtotime($mod_goods_find_data['ShopGoodPreSaleEndTime']) < time()) {
////            $apiRet['apiCode']='000089';
////            $apiRet['apiInfo']='该商品已结束预售';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品已结束预售');
//
//            return $apiRet;
//        }
//
//
//        if ($skuId > 0) {
//            if ($mod_goods_find_data['hasGoodsSku'] == 1) {
//                $goodsSkuList = $mod_goods_find_data['goodsSku']['skuList'];
//                $skuSpecAttr = '';
//                foreach ($goodsSkuList as $sku) {
//                    if ($sku['skuId'] == $skuId) {
//                        foreach ($sku['selfSpec'] as $sv) {
//                            $skuSpecAttr .= $sv['attrName'] . '，';
//                        }
//                    }
//                }
//                $skuSpecAttr = trim($skuSpecAttr, '，');
//            }
//        }
//        //地址是否存在
//        if (empty($mod_user_address_data)) {
////            $apiRet['apiCode']='000090';
////            $apiRet['apiInfo']='请添加收货地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//
//            return $apiRet;
//        }
//
//        //地址是否属于本人
//        if ($mod_user_address_data['userId'] != $userId) {
////            $apiRet['apiCode']='000091';
////            $apiRet['apiInfo']='请使用本人的地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请使用本人的地址');
//
//            return $apiRet;
//        }
//
//        //新增sku
//        $systemSkuSpec = M('sku_goods_system')->where(['goodsId' => $goodsId, 'skuId' => $skuId])->find();
//        if ($systemSkuSpec) {
//            foreach ($replaceSkuField as $rk => $rv) {
//                if ((int)$systemSkuSpec[$rk] == -1) {//如果sku属性值为-1,则调用商品原本的值(详情查看config)
//                    continue;
//                }
//                if (in_array($rk, ['dataFlag', 'addTime'])) {
//                    continue;
//                }
//                if (isset($mod_goods_find_data[$rv])) {
//                    $mod_goods_find_data[$rv] = $systemSkuSpec[$rk];
//                }
//            }
//        }
//        //商品库存是否充足
//
//        if ($mod_goods_find_data['goodsStock'] < $goodsNum) {
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'], -1, 'error', "库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}");
//
//            return $apiRet;
//        }
//
//        $shopConfig = M('shop_configs')->where(['shopId' => $mod_goods_find_data['shopId']])->find();
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $mod_user_address_data['areaId3']);
//            if (!empty($isDistriScope)) {
//                return $isDistriScope;
//            }
//        }
//
//        //检测配送区域
//        /*if(!$lng || !$lat){
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="，参数缺失：经纬度";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'],-1,'error','参数缺失：经纬度');
//            return $apiRet;
//        }*/
//
//        if ($shopConfig['deliveryLatLngLimit'] == 1) {
//            $dcheck = checkShopDistribution($mod_goods_find_data['shopId'], $mod_user_address_data['lng'], $mod_user_address_data['lat']);
//            if (!$dcheck) {
//                unset($apiRet);
////                $apiRet['apiCode']='000093';
////                $apiRet['apiInfo']='配送范围超出';
//                $apiRet['goodsId'] = $mod_goods_find_data['goodsId'];
//                $apiRet['goodsName'] = $mod_goods_find_data['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//                return $apiRet;
//            }
//            //END
//        }
//
//        //生成订单数据
//        //订单公用参数
//        $data["areaId1"] = $mod_user_address_data["areaId1"];
//        $data["areaId2"] = $mod_user_address_data["areaId2"];
//        $data["areaId3"] = $mod_user_address_data["areaId3"];
//        $data["payType"] = "1";//支付方式
//        $data["isSelf"] = "0";//是否自提
//
//
//        $data["isPay"] = "0";//是否支付
//
//        $data["defPreSale"] = "1";//是否是预售订单 1是
//
//        $data["userId"] = $userId;//用户Id
//        $data["userName"] = $mod_user_address_data["userName"];//收货人名称
//        $data["communityId"] = $mod_user_address_data["communityId"];//收货地址所属社区id
//
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $mod_user_address_data['setaddress'] .
//            $mod_user_address_data["address"];//收件人地址
//
//        $data["userPhone"] = $mod_user_address_data["userPhone"];//收件人手机
//        $data["userPostCode"] = $mod_user_address_data["postCode"];//收件人邮编
//        $data["isInvoice"] = "0";//是否需要发票
//        $data["orderRemarks"] = "";//订单备注
//        $data["requireTime"] = date("Y-m-d H:i:s", time() + 3600);//要求送达时间
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "4";//订单来源
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = "2";//支付来源
//        //$data["settlementId"] = "0";//结算记录ID 用户确认收货补上id 创建 wst_order_settlements 结算表 再更改结算记录Id
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        $data["useScore"] = "0";//本次交易使用的积分数
//        $data["scoreMoney"] = "0";//积分兑换的钱 完成交易 确认收货在给积分
//
//        //生成订单号
//        $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//        $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//        $data["orderNo"] = $orderNo;//订单号
//        $data["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//
//        $data["orderStatus"] = "12";//订单状态 预售订单（未支付）
//        $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//
//
//        //减去对应商品总库存
//        $where = null;
//        $where["goodsId"] = $goodsId;
//        $goods_num = gChangeKg($goodsId, $goodsNum, 1);
//        $mod_goods->where($where)->setDec('goodsStock', $goods_num);
//        if ($skuId > 0) {
//            M('sku_goods_system')->where(['skuId' => $skuId])->setDec('skuGoodsStock', $goods_num);
//        }
//        //增加预定量
//        $mod_goods->where($where)->setInc('bookQuantity', $goods_num);
//
//        //更新进销存系统商品的库存
//        //updateJXCGoodsStock($goodsId, $goodsNum, 1);
//
//        //获取当前订单商品总价
//        $mod_users = M('users');
//        $goods_integral = 0;
//        $totalMoney = (float)$mod_goods_find_data["shopPrice"] * (int)$goodsNum;
//        $goodsPrice = $mod_goods_find_data["shopPrice"];
//        $users = $mod_users->where(array('userId' => $userId))->find();
//        if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//            //如果设置了会员价，则使用会员价，否则使用会员折扣
//            if ($mod_goods_find_data['memberPrice'] > 0) {
//                $totalMoney = (float)$mod_goods_find_data['memberPrice'] * (int)$goodsNum;
//                $goods_integral = (int)$goodsNum * $mod_goods_find_data['integralReward'];
//                $goodsPrice = $mod_goods_find_data['memberPrice'];
//            } else {
//                if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                    $totalMoney = $totalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                    $goodsPrice = $mod_goods_find_data["shopPrice"] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                }
//            }
//        }
//        $data["totalMoney"] = (float)$totalMoney;//商品总金额
//        //$data["orderScore"] = floor($totalMoney+$goods_integral)*$rewardScoreMultiple;//所得积分
//        $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney) + $goods_integral;//所得积分
//        $data["deliverMoney"] = $mod_shops_data["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//        $data["deliverType"] = "1";//配送方式 默认门店配送
//
//
////        $mod_goods_find_data['PreSalePayPercen'] = 100; //应前端要求,预付改为全付
////        $PreSalePay = $mod_goods_find_data['PreSalePayPercen']/100*$totalMoney;//获取首付款价格
//        $PreSalePay = $totalMoney;//获取首付款价格
//
//
//        if (0 >= $PreSalePay) {
//            //不在达达覆盖城市内
////            $apiRet['apiCode']='000097';
////            $apiRet['apiInfo']='订单金额异常！';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '订单金额异常！');
//            return $apiRet;
//        }
//
//        $PreSalePayPercen = $mod_goods_find_data['PreSalePayPercen'];//预付款百分比
//        $data["PreSalePayPercen"] = $PreSalePayPercen;
//        $data["PreSalePay"] = (float)$PreSalePay + (float)$data["deliverMoney"];//预付的金额+运费
//
//        //设置订单配送方式
//        if ($mod_shops_data["deliveryType"] == 4) {
//            $data["deliverType"] = "4";//配送方式 快跑者配送
//        }
//
//        //当前店铺是否是达达配送
//        if ($mod_shops_data["deliveryType"] == 2) {
//
//
//            $funData['shopId'] = $mod_goods_find_data['shopId'];
//            $funData['areaId2'] = $data["areaId2"];
//            $funData['orderNo'] = $data["orderNo"];
//            $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//            $funData['userName'] = $data["userName"];//收货人姓名
//            $funData['userAddress'] = $data["userAddress"];//收货人地址
//            $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//            $dadaresFun = self::dadaDeliver($funData);
//
//            if ($dadaresFun['status'] == -6) {
//                //获取城市出错
////                $apiRet['apiCode']='000093';
////                $apiRet['apiInfo']='提交失败，获取城市出错';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == -7) {
//                //不在达达覆盖城市内
////                $apiRet['apiCode']='000094';
////                $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == 0) {
//                //获取成功
//                $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//                $data["deliverMoney"] = $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//
//            }
//            $data["deliverType"] = "2";//配送方式 达达配送
//        }
//
//
//        $data["needPay"] = $totalMoney + $data["deliverMoney"];//需缴费用 加运费-----
//        $data["realTotalMoney"] = (float)$totalMoney + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//        $shop_info = $sm->getShopInfo($data["shopId"]);
//        $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//        $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//
//        //写入订单
//        $orderId = $mod_orders->add($data);
//
//        //建立订单记录
//        $data_order["orderId"] = $orderId;
//        $data_order["logContent"] = "小程序预付下单成功，等待支付";
//        $data_order["logUserId"] = $data['userId'];
//        $data_order["logType"] = 0;
//        $data_order["logTime"] = date('Y-m-d H:i:s');
//
//
//        M('log_orders')->add($data_order);
//
//        //将订单商品写入order_goods
//
//        $data_order_goods["orderId"] = $orderId;
//        $data_order_goods["goodsId"] = $goodsId;
//        $data_order_goods["goodsNums"] = $goodsNum;
//        $data_order_goods["goodsPrice"] = $goodsPrice;
//        $data_order_goods["goodsAttrId"] = "0";
//        $data_order_goods["skuId"] = $skuId;
//        if ($skuId > 0) {
//            $data_order_goods["skuSpecAttr"] = $skuSpecAttr;
//        }
//        $data_order_goods["goodsName"] = $mod_goods_find_data["goodsName"];
//        $data_order_goods["goodsThums"] = $mod_goods_find_data["goodsThums"];
//        M('order_goods')->add($data_order_goods);
//        //限制商品下单次数
//        limitGoodsOrderNum($goodsId, $userId);
//
//        //建立订单提醒
//        $data_order_reminds["orderId"] = $orderId;
//        $data_order_reminds["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//        $data_order_reminds["userId"] = $data['userId'];
//        $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//        $order_reminds_statusCode = $mod_order_reminds->add($data_order_reminds);
//        // 获取生成的所有订单号 返回给APP支付
//        $wxorderNo = $orderNo;
//
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode($data["PreSalePay"]);//预定首款金额 单位元  加运费
//            $statusCode["orderNo"] = strencode($wxorderNo);//订单号  多个订单号用 A隔开
//
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5($wxorderNo);
//            $wst_order_merge_data['value'] = $wxorderNo;
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//
//            unset($apiRet);
////            $apiRet['apiCode']='000095';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//
//        } else {
//
//            unset($apiRet);
////            $apiRet['apiCode']='000096';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//
//
//    }

    //提交订单-预售 实在蛋疼 其他提交订单都未加入事务操作 很多地方需要添加 后续添加吧
    //小程序
//    public function xcxPreSaleSub($userId, $addressId, $goodsId, $goodsNum = 1, $lng, $lat)
//    {
//        $mod_goods = M('goods');
//        $mod_user_address = M('user_address');
//        $orderids = M('orderids');
//        $order_communitys_mod = M('communitys');
//        $mod_shops = M('shops');
//        $mod_orders = M('orders');
//        $mod_order_reminds = M('order_reminds');
//        $order_areas_mod = M('areas');
//        $sm = D('Home/Shops');
//
//        $where['goodsId'] = $goodsId;
//        $mod_goods_find_data = $mod_goods->where($where)->find();//获取商品信息
//        $mod_goods_find_data = rankGoodsPrice($mod_goods_find_data); //商品等级价格
//        $mod_user_address_data = $mod_user_address->where("addressFlag = 1 and addressId = '{$addressId}'")->find();//获取地址信息
//        $mod_shops_data = $mod_shops->where("shopId = '{$mod_goods_find_data['shopId']}'")->find();//获取店铺信息
//
//        //获取会员奖励积分倍数
//        //$rewardScoreMultiple = WSTRewardScoreMultiple($userId);
//
//        if (empty($mod_goods_find_data)) {
////            $apiRet['apiCode']='000084';
////            $apiRet['apiInfo']='不存在的商品';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '不存在的商品');
//            return $apiRet;
//        }
//
//        //判断商品是否在售
//        if ($mod_goods_find_data['isSale'] == 0) {
////            $apiRet['apiCode']='000085';
////            $apiRet['apiInfo']='商品未上架';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品未上架');
//
//            return $apiRet;
//        }
//        if ($mod_goods_find_data['goodsStatus'] == 0) {
////            $apiRet['apiCode']='000086';
////            $apiRet['apiInfo']='商品禁售或未审核';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品禁售或未审核');
//
//            return $apiRet;
//        }
//        if ($mod_goods_find_data['goodsFlag'] == 0) {
////            $apiRet['apiCode']='000087';
////            $apiRet['apiInfo']='商品已删除';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '商品已删除');
//
//            return $apiRet;
//        }
//
//        //商品是否是店铺预售商品
//        if ($mod_goods_find_data['isShopPreSale'] == 0) {
////            $apiRet['apiCode']='000088';
////            $apiRet['apiInfo']='该商品非预售状态';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品非预售状态');
//
//            return $apiRet;
//        }
//
//        //预售是否结束
//        if (strtotime($mod_goods_find_data['ShopGoodPreSaleEndTime']) < time()) {
////            $apiRet['apiCode']='000089';
////            $apiRet['apiInfo']='该商品已结束预售';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '该商品已结束预售');
//
//            return $apiRet;
//        }
//
//        $checkGoodsFlashSale = checkGoodsFlashSale($goodsId); //检查商品限时状况
//        if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
//            return $checkGoodsFlashSale;
//        }
//
//        //判断是否为限制下单次数的商品 start
//        $checkRes = checkGoodsOrderNum($goodsId, $userId);
//        if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
//            return $checkRes;
//        }
//
//        //地址是否存在
//        if (empty($mod_user_address_data)) {
////            $apiRet['apiCode']='000090';
////            $apiRet['apiInfo']='请添加收货地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请添加收货地址');
//            return $apiRet;
//        }
//
//        //地址是否属于本人
//        if ($mod_user_address_data['userId'] !== $userId) {
////            $apiRet['apiCode']='000091';
////            $apiRet['apiInfo']='请使用本人的地址';
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=null;
//            $apiRet = returnData(null, -1, 'error', '请使用本人的地址');
//
//            return $apiRet;
//        }
//
//        //商品库存是否充足
//
//        if ($mod_goods_find_data['goodsStock'] < $goodsNum) {
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'], -1, 'error', "库存不足，剩余数量：{$mod_goods_find_data['goodsStock']}");
//            return $apiRet;
//        }
//
//        $shopConfig = M('shop_configs')->where(['shopId' => $mod_goods_find_data['shopId']])->find();
//        if ($shopConfig['relateAreaIdLimit'] == 1) {
//            //判断商品是否在配送范围内 在确认订单页面 或者购物车就自动验证 提高用户体验度
//            $isDistriScope = isDistriScope($goodsId, $mod_user_address_data['areaId3']);
//            if (!empty($isDistriScope)) {
//                M()->rollback();
//                return $isDistriScope;
//            }
//        }
//
//        //检测配送区域
//        /*if(!$lng || !$lat){
////            $apiRet['apiCode']='000092';
////            $apiRet['apiInfo']="，参数缺失：经纬度";
////            $apiRet['apiState']='error';
////            $apiRet['apiData']=$mod_goods_find_data['goodsStock'];
//            $apiRet = returnData($mod_goods_find_data['goodsStock'],-1,'error','参数缺失：经纬度');
//            return $apiRet;
//        }*/
//
//        if ($shopConfig['deliveryLatLngLimit'] == 1) {
//            $dcheck = checkShopDistribution($mod_goods_find_data['shopId'], $mod_user_address_data['lng'], $mod_user_address_data['lat']);
//            if (!$dcheck) {
//                M()->rollback();
//                unset($apiRet);
////                $apiRet['apiCode']='000074';
////                $apiRet['apiInfo']='配送范围超出';
//                $apiRet['goodsId'] = $mod_goods_find_data['goodsId'];
//                $apiRet['goodsName'] = $mod_goods_find_data['goodsName'];
////                $apiRet['apiState']='error';
//                $apiRet = returnData($apiRet, -1, 'error', '配送范围超出');
//                return $apiRet;
//            }
//            //END
//        }
//
//        //生成订单数据
//        //订单公用参数
//        $data["areaId1"] = $mod_user_address_data["areaId1"];
//        $data["areaId2"] = $mod_user_address_data["areaId2"];
//        $data["areaId3"] = $mod_user_address_data["areaId3"];
//        $data["payType"] = "1";//支付方式
//        $data["isSelf"] = "0";//是否自提
//
//        $data["isPay"] = "0";//是否支付
//
//        $data["defPreSale"] = "1";//是否是预售订单 1是
//
//        $data["userId"] = $userId;//用户Id
//        $data["userName"] = $mod_user_address_data["userName"];//收货人名称
//        $data["communityId"] = $mod_user_address_data["communityId"];//收货地址所属社区id
//
//        $data["userAddress"] =
//            $order_areas_mod->where("areaId = '{$data["areaId1"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId2"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_areas_mod->where("areaId = '{$data["areaId3"]}'")->field('areaName')->find()['areaName'] . ' ' .
//            $order_communitys_mod->where("communityId = '{$data["communityId"]}'")->field('communityName')->find()['communityName'] . ' ' .
//            $mod_user_address_data['setaddress'] .
//            $mod_user_address_data["address"];//收件人地址
//
//        $data["userPhone"] = $mod_user_address_data["userPhone"];//收件人手机
//        $data["userPostCode"] = $mod_user_address_data["postCode"];//收件人邮编
//        $data["isInvoice"] = "0";//是否需要发票
//        $data["orderRemarks"] = "";//订单备注
//        $data["requireTime"] = date("Y-m-d H:i:s", time() + 3600);//要求送达时间
//        $data["isAppraises"] = "0";//是否点评
//        $data["createTime"] = date("Y-m-d H:i:s", time());//下单时间
//        $data["orderSrc"] = "4";//订单来源
//        $data["orderFlag"] = "1";//订单有效标志
//        $data["payFrom"] = "2";//支付来源
//        //$data["settlementId"] = "0";//结算记录ID 用户确认收货补上id 创建 wst_order_settlements 结算表 再更改结算记录Id
////        $data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//
//        $data["useScore"] = "0";//本次交易使用的积分数
//        $data["scoreMoney"] = "0";//积分兑换的钱 完成交易 确认收货在给积分
//
//        //生成订单号
//        $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
//        $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
//        $data["orderNo"] = $orderNo;//订单号
//        $data["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//
//        $data["orderStatus"] = "12";//订单状态 预售订单（未支付）
//        $data["orderunique"] = uniqid() . mt_rand(2000, 9999);//订单唯一流水号
//
//        //减去对应商品总库存
//        $where = null;
//        $where["goodsId"] = $goodsId;
//        $goods_num = gChangeKg($goodsId, $goodsNum, 1);
//        $mod_goods->where($where)->setDec('goodsStock', $goods_num);
//        //增加预定量
//        $mod_goods->where($where)->setInc('bookQuantity', $goods_num);
//
//        //获取当前订单商品总价
//        $mod_users = M('users');
//        $goods_integral = 0;
//        $totalMoney = (float)$mod_goods_find_data["shopPrice"] * (int)$goodsNum;
//        $goodsPrice = $mod_goods_find_data["shopPrice"];
//        $users = $mod_users->where(array('userId' => $userId))->find();
//        if ($users['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//            //如果设置了会员价，则使用会员价，否则使用会员折扣
//            if ($mod_goods_find_data['memberPrice'] > 0) {
//                $totalMoney = (float)$mod_goods_find_data['memberPrice'] * (int)$goodsNum;
//                $goods_integral = (int)$goodsNum * $mod_goods_find_data['integralReward'];
//                $goodsPrice = $mod_goods_find_data['memberPrice'];
//            } else {
//                if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                    $totalMoney = $totalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                    $goodsPrice = $mod_goods_find_data["shopPrice"] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                }
//            }
//        }
//        $data["totalMoney"] = (float)$totalMoney;//商品总金额
//        //$data["orderScore"] = floor($totalMoney+$goods_integral)*$rewardScoreMultiple;//所得积分
//        $data["orderScore"] = getOrderScoreByOrderScoreRate($totalMoney) + $goods_integral;//所得积分
//        $data["deliverMoney"] = $mod_shops_data["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//        $data["deliverType"] = "1";//配送方式 默认门店配送
//
//        $mod_goods_find_data['PreSalePayPercen'] = 100; //应前端要求,预付改为全付
//        $PreSalePay = $mod_goods_find_data['PreSalePayPercen'] / 100 * $totalMoney;//获取首付款价格
//
//        if (0 >= $PreSalePay) {
//            //不在达达覆盖城市内
////            $apiRet['apiCode']='000097';
////            $apiRet['apiInfo']='订单金额异常！';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '订单金额异常！');
//            return $apiRet;
//        }
//
//        $PreSalePayPercen = $mod_goods_find_data['PreSalePayPercen'];//预付款百分比
//        $data["PreSalePayPercen"] = $PreSalePayPercen;
//        $data["PreSalePay"] = (float)$PreSalePay + (float)$data["deliverMoney"];//预付的金额+运费
//
//        //设置订单配送方式
//        if ($mod_shops_data["deliveryType"] == 4) {
//            $data["deliverType"] = "4";//配送方式 快跑者配送
//        }
//
//        //当前店铺是否是达达配送
//        if ($mod_shops_data["deliveryType"] == 2) {
//
//            $funData['shopId'] = $mod_goods_find_data['shopId'];
//            $funData['areaId2'] = $data["areaId2"];
//            $funData['orderNo'] = $data["orderNo"];
//            $funData['totalMoney'] = $data["totalMoney"];//订单金额 不加运费
//            $funData['userName'] = $data["userName"];//收货人姓名
//            $funData['userAddress'] = $data["userAddress"];//收货人地址
//            $funData['userPhone'] = $data["userPhone"];//收货人手机
//
//            $dadaresFun = self::dadaDeliver($funData);
//
//            if ($dadaresFun['status'] == -6) {
//                //获取城市出错
////                $apiRet['apiCode']='000093';
////                $apiRet['apiInfo']='提交失败，获取城市出错';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，获取城市出错');
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == -7) {
//                //不在达达覆盖城市内
////                $apiRet['apiCode']='000094';
////                $apiRet['apiInfo']='提交失败，不在达达覆盖城市内';
////                $apiRet['apiData'] = null;
////                $apiRet['apiState']='error';
//                $apiRet = returnData(null, -1, 'error', '提交失败，不在达达覆盖城市内');
//
//                return $apiRet;
//            }
//            if ($dadaresFun['status'] == 0) {
//                //获取成功
//                $data['distance'] = $dadaresFun['data']['distance'];//配送距离(单位：米)
//                $data['deliveryNo'] = $dadaresFun['data']['deliveryNo'];//来自达达返回的平台订单号
//                $data["deliverMoney"] = $dadaresFun['data']['deliverMoney'];//	实际运费(单位：元)，运费减去优惠券费用
//            }
//            $data["deliverType"] = "2";//配送方式 达达配送
//        }
//
//        $data["needPay"] = $totalMoney + $data["deliverMoney"];//需缴费用 加运费-----
//        $data["realTotalMoney"] = (float)$totalMoney + (float)$data["deliverMoney"];//实际订单总金额 加运费-----
//
//        $shop_info = $sm->getShopInfo($data["shopId"]);
//        $data["poundageRate"] = (!empty($shop_info) && $shop_info['commissionRate'] > 0) ? (float)$shop_info['commissionRate'] : (float)$GLOBALS['CONFIG']['poundageRate'];//佣金比例
//        $data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100, 0, 2);//佣金
//
//        //写入订单
//        $orderId = $mod_orders->add($data);
//
//        //建立订单记录
//        $data_order["orderId"] = $orderId;
//        $data_order["logContent"] = "小程序预付下单成功，等待支付";
//        $data_order["logUserId"] = $data['userId'];
//        $data_order["logType"] = 0;
//        $data_order["logTime"] = date('Y-m-d H:i:s');
//
//        M('log_orders')->add($data_order);
//
//        //将订单商品写入order_goods
//
//        $data_order_goods["orderId"] = $orderId;
//        $data_order_goods["goodsId"] = $goodsId;
//        $data_order_goods["goodsNums"] = $goodsNum;
//        $data_order_goods["goodsPrice"] = $goodsPrice;
//        $data_order_goods["goodsAttrId"] = "0";
//        $data_order_goods["goodsName"] = $mod_goods_find_data["goodsName"];
//        $data_order_goods["goodsThums"] = $mod_goods_find_data["goodsThums"];
//        M('order_goods')->add($data_order_goods);
//
//        //建立订单提醒
//        $data_order_reminds["orderId"] = $orderId;
//        $data_order_reminds["shopId"] = $mod_goods_find_data['shopId'];//店铺id
//        $data_order_reminds["userId"] = $data['userId'];
//        $data_order_reminds["createTime"] = date("Y-m-d H:i:s");
//        $order_reminds_statusCode = $mod_order_reminds->add($data_order_reminds);
//        // 获取生成的所有订单号 返回给APP支付
//        $wxorderNo = $orderNo;
//
//        if ($order_reminds_statusCode) {
//            unset($statusCode);
//            $statusCode["appRealTotalMoney"] = strencode($data["PreSalePay"]);//预定首款金额 单位元  加运费
//            $statusCode["orderNo"] = strencode($wxorderNo);//订单号  多个订单号用 A隔开
//
//            //写入订单合并表
//            $wst_order_merge_data['orderToken'] = md5($wxorderNo);
//            $wst_order_merge_data['value'] = $wxorderNo;
//            $wst_order_merge_data['createTime'] = time();
//            M('order_merge')->add($wst_order_merge_data);
//
//            unset($apiRet);
////            $apiRet['apiCode']='000095';
////            $apiRet['apiInfo']='提交成功';
////            $apiRet['apiData'] = $statusCode;
////            $apiRet['apiState']='success';
//            $apiRet = returnData($statusCode);
//            return $apiRet;
//        } else {
//
//            unset($apiRet);
////            $apiRet['apiCode']='000096';
////            $apiRet['apiInfo']='提交失败';
////            $apiRet['apiData'] = null;
////            $apiRet['apiState']='error';
//            $apiRet = returnData(null, -1, 'error', '提交失败');
//            return $apiRet;
//        }
//    }

    //品牌 商品列表-分页
    public function indexBrandLists($adcode, $lat, $lng, $page = 1, $brandId, $userId = 0)
    {
        $data = S("NIAO_CACHE_app_indexBrandLists_{$adcode}_{$page}_{$brandId}");
        if (empty($data)) {
            $pageDataNum = 10;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺基本条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品基本条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            //查找特殊条件
            $where['wst_goods.brandId'] = $brandId;//商品品牌ID

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.isShopPreSale,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级加处理
            S("NIAO_CACHE_app_indexBrandLists_{$adcode}_{$page}_{$brandId}", $data, C("allApiCacheTime"));
        }

        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取品牌商品列表成功';
//        $apiRet['apiData'] = $data;
//        $apiRet['apiState']='success';
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取订单ID
    public function getOrderID($orderNo)
    {
        $where['orderNo'] = $orderNo;

        $data = M('orders')->where($where)->field('orderId')->find();

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取订单id成功';
//        $apiRet['apiData'] = $data;
//        $apiRet['apiState']='success';
        $apiRet = returnData($data);
        return $apiRet;
    }

    //获取优惠券列表 处于发放期间
    public function getCoupons($parameter = array())
    {
        $where['dataFlag'] = 1;
        !empty($parameter['couponId']) ? $where['couponId'] = $parameter['couponId'] : false;
//        !empty($parameter['couponType']) ? $where['couponType'] = $parameter['couponType'] : false;
        !empty($parameter['type']) ? $where['type'] = $parameter['type'] : false;

        if (empty($parameter['shopId'])) {
            $parameter['shopId'] = 0;
        }
        $where['couponType'] = 1;

        //$where['shopId'] = array('in', "{$parameter['shopId']}");//2019.5.19   //2020.5.17 todo:考虑这句话的改动调整 关系着多商户多门店的共存问题 暂时应急 先注释


        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();
        foreach ($data as $key => &$val) {
            $val['isShow'] = 1; //未领取过,不直接去重是因为这样更灵活
        }
        unset($val);
        $datetime = date("Y-m-d H:i:s");
        if ($parameter['userId'] > 0) {
            if (count($data) > 0) {
                foreach ($data as $key => $val) {
                    if ($val["sendNum"] > 0) {//如果有发放数量限制
                        if ($val["receiveNum"] >= $val["sendNum"]) {
                            unset($data[$key]);
                            continue;
                        }
                    }
                    if ($val["validEndTime"] . " 23:59:59" <= $datetime) {//活动已结束
                        unset($data[$key]);
                        continue;
                    }
                    $couponId[] = $val['couponId'];
                }
                unset($where);
                $where['couponId'] = ["IN", $couponId];
                $where['userId'] = $parameter['userId'];
                $where['dataFlag'] = 1;
                $couponsUsers = M("coupons_users")->where($where)->select();
                foreach ($data as $key => &$val) {
                    if (count($couponsUsers) > 0) {
                        foreach ($couponsUsers as $v) {
                            if ($v['couponId'] == $val['couponId']) {
                                $val['isShow'] = -1; //已领取过
                                unset($data[$key]);
                            }
                        }
                    }
                }
                unset($val);
                $data = array_values($data);
            }
        }

        //获取优惠券权限信息
        // get_couponsList_auth($data);
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取优惠券列表成功';
//        $apiRet['apiData'] = $data;
//        $apiRet['apiState']='success';
        $apiRet = returnData((array)$data);
        return $apiRet;

    }

    //领取优惠券 $dataFlag：领取删除的优惠券可用于邀请下单成功返优惠券
    public function okCoupons($userId, $couponId, $couponType = 1, $dataFlag = 1, $userToId = 0)
    {
        $mod_coupons = M('coupons');
        $mod_coupons_users = M('coupons_users');
        //优惠券是否存在
        $where['couponId'] = $couponId;
        $where['dataFlag'] = 1;
        $where['couponType'] = $couponType;//默认领取满减 1

        $coupondata = $mod_coupons->where($where)->find();
        //兼容邀请好友优惠券可多次领取 只判断需要判断的优惠券

        if (!$coupondata) {
//            $apiRet['apiCode']='000098';
//            $apiRet['apiInfo']='不存在这个优惠券';
//            $apiRet['apiData'] = null;
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '不存在这个优惠券');
            return $apiRet;
        }


        //优惠券是否已经领取
        $where = array();
        $where['couponId'] = $couponId;
        $where['userId'] = $userId;
        //兼容邀请好友优惠券可多次领取 只判断需要判断的优惠券 //3=>邀请好友 6=>邀请开通会员 8=>运费券
        if (!in_array($coupondata['couponType'], array(3, 6, 8))) {
            if ($mod_coupons_users->where($where)->find()) {
//                $apiRet['apiCode']='000099';
//                $apiRet['apiInfo']='优惠券已经领取了';
//                $apiRet['apiData'] = null;
//                $apiRet['apiState']='error';
                $apiRet = returnData(null, -1, 'error', '优惠券已经领取了');

                return $apiRet;
            }
        }
        if ($coupondata["validEndTime"] . " 23:59:59" <= date("Y-m-d H:i:s")) {
            return returnData(null, -1, 'error', '优惠券活动时间已结束');
        }
        //优惠券是否处于发放期间
        $where = array();
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $where['couponId'] = $couponId;
        if (!$mod_coupons->where($where)->find()) {
//            $apiRet['apiCode']='000100';
//            $apiRet['apiInfo']='优惠券已发放结束';
//            $apiRet['apiData'] = null;
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '优惠券已发放结束');

            return $apiRet;
        }

        //发放数量 是否充足
        unset($where);
        // $where['sendNum'] = array('EQ',0);
        $where['couponId'] = $couponId;

        if ($mod_coupons->where($where)->find()['sendNum'] >= 0) {//是否是不限数量 大于0为限制数量 小于0为不限数量
            unset($where);

            //sql有问题---------------------------
            // $where['sendNum'] = array('exp', 'receiveNum+1 >= sendNum');// receiveNum+1 因为目前一次只能领取一张
            $where['sendNum'] = array('exp', ' < `receiveNum`+1');// receiveNum+1 因为目前一次只能领取一张  这个是个坑 不能把senNum放进去 会导致sql拼接出问题 tp未校验sql语法

            $where['couponId'] = $couponId;
            $ismod = $mod_coupons->where($where)->find();


            if ($ismod) {
//                $apiRet['apiCode']='000101';
//                $apiRet['apiInfo']='优惠券已发放完';
//                $apiRet['apiData'] = null;
//                $apiRet['apiState']='error';
                $apiRet = returnData(null, -1, 'error', '优惠券已发放完');

                return $apiRet;
            }
        }


        $addData['couponId'] = $couponId;
        $addData['userId'] = $userId;
        $addData['userToId'] = $userToId;
        $addData['receiveTime'] = date('Y-m-d H:i:s');
        $addData['dataFlag'] = $dataFlag;
        $addData['couponExpireTime'] = calculationTime(date('Y-m-d H:i:s'), $coupondata['expireDays']);
        if ($addData['couponExpireTime'] > $coupondata["validEndTime"]) {//已领券的过期时间不能大于活动结束时间
            $addData["couponExpireTime"] = $coupondata["validEndTime"] . " 23:59:59";
        }
//        $addData['couponExpireTime'] = $coupondata['validEndTime'] . ' 23:59:59';
        if ($mod_coupons_users->add($addData)) {

            $saveData['receiveNum'] = array('exp', 'receiveNum+1');
            $mod_coupons->where('couponId =' . $couponId)->save($saveData);

//            $apiRet['apiCode']='000102';
//            $apiRet['apiInfo']='优惠券领取成功';
//            $apiRet['apiData'] = null;
//            $apiRet['apiState']='success';
            $apiRet = returnData();

            return $apiRet;
        } else {
//            $apiRet['apiCode']='000103';
//            $apiRet['apiInfo']='优惠券领取失败';
//            $apiRet['apiData'] = null;
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '优惠券领取失败');

            return $apiRet;

        }

    }

    //获取会员未使用的优惠券 且未过期
    public function getUserCoupons($userId)
    {

        // $where["wst_coupons.validStartTime"] =  array('ELT',date('Y-m-d'));
//			$where["wst_coupons.validEndTime"] = array('EGT',date('Y-m-d'));
        $where['wst_coupons_users.userId'] = $userId;

        $where['wst_coupons_users.dataFlag'] = 1;
        $where['wst_coupons_users.couponStatus'] = 1;
        $where['wst_coupons_users.couponExpireTime'] = array('GT', date('Y-m-d H:i:s'));
        $where['wst_coupons.dataFlag'] = 1;
        $mod = M("coupons_users")
            ->where($where)
            ->join('wst_coupons ON wst_coupons_users.couponId = wst_coupons.couponId')
            ->group('wst_coupons_users.id')
            ->order('receiveTime desc')
            ->select();

        //获取优惠券权限信息
        get_couponsList_auth($mod);
        return (array)$mod;
    }

    //获取会员已使用的优惠券
    public function getUserCouponsYes($userId)
    {

        $mod_coupons_users = M('coupons_users');
        $mod_coupons = M('coupons');
        $where['userId'] = $userId;
        $where['dataFlag'] = 1;
        $where['couponStatus'] = array('in', '0');
        $data = $mod_coupons_users->where($where)->order('receiveTime desc')->select();
        //获取优惠券权限信息
        // get_couponsList_auth($data);
        $resData = array();
        for ($i = 0; $i < count($data); $i++) {
            $data2[$i] = $mod_coupons->where("couponId = " . $data[$i]['couponId'])->find();
            $data2[$i]['receiveTime'] = $data[$i]['receiveTime'];
            $data2[$i]['couponStatus'] = $data[$i]['couponStatus'];
            $data2[$i]['couponExpireTime'] = $data[$i]['couponExpireTime'];
        }
        return (array)$data2;
    }

    //获取会员的已过期优惠券列表
    public function getUserCouponsNo($userId)
    {

//			$where['wst_coupons.validEndTime'] = array('LT',date('Y-m-d'));
        $where['wst_coupons_users.userId'] = $userId;
        $where['wst_coupons_users.couponExpireTime'] = array('ELT', date('Y-m-d H:i:s'));
        $mod = M("coupons_users")
            ->where($where)
            ->join('wst_coupons ON wst_coupons_users.couponId = wst_coupons.couponId')
            ->group('wst_coupons_users.id')
            ->order('receiveTime desc')
            ->select();
        //获取优惠券权限信息
        get_couponsList_auth($mod);
        return (array)$mod;
    }

    //获取自提码
    public function getUserCouponsNum($userId, $orderId)
    {
        $where['userId'] = $userId;
        $where['orderId'] = $orderId;
        $where['onStart'] = 0;
        $mod = M('user_self_goods')->where($where)->find();
        return (array)$mod;
    }

    //获取店铺配置
    public function getShopConfig($shopId)
    {
        $data = M('shop_configs')->where('shopId = ' . $shopId)->find();
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='店铺配置获取成功';
//        $apiRet['apiData'] = $data;
//        $apiRet['apiState']='success';
        $apiRet = returnData($data);
        return $apiRet;
    }


    /**
     * 申请售后-提交售后申请
     * @param array $params <p>
     * int userId 用户id
     * int id 订单商品唯一标识id
     * float returnNum 退货数量/重量
     * int complainType 投诉类型[ 1：商品质量有问题 2：没收到此商品 3：仓库发错货 4：实物与描述不符 5：其他]
     * string complainContent 投诉内容
     * string complainAnnex 图片附件
     * </p>
     * */
    public function Mobcomplains(array $params)
    {
        $orderModule = new OrdersModule();
        $id = (int)$params['id'];
        $returnNum = (float)$params['returnNum'];
        $complainType = (int)$params['complainType'];
        $complainContent = (string)$params['complainContent'];//投诉内容
        $complainAnnex = (string)$params['complainAnnex'];//图片附件
        $orderGoodsField = 'id,orderId,goodsId,goodsName,skuId,goodsNums,goodsPrice,skuSpecAttr,goodsThums,is_weight,weight';
        $orderGoodsDetail = $orderModule->getOrderGoodsInfoById($id, $orderGoodsField, 2);
        $deliverRespondTime = date("Y-m-d H:i:s");
        if (empty($orderGoodsDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单商品信息有误');
        }
        $orderGoodsDetail['receivingGoodsNums'] = (float)$orderGoodsDetail['goodsNums'];
        $orderGoodsDetail['weight'] = (float)$orderGoodsDetail['weight'];
        if ($orderGoodsDetail['weight'] > 0) {
            $orderGoodsDetail['receivingGoodsNums'] = $orderGoodsDetail['weight'];
        }
        $orderId = $orderGoodsDetail['orderId'];
        $goodsId = $orderGoodsDetail['goodsId'];
        $skuId = $orderGoodsDetail['skuId'];
        $sortingModule = new SortingModule();
        $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
        if (!empty($sortingOrderGoodsDetail)) {
            $personId = $sortingOrderGoodsDetail['personId'];
            $sortingId = $sortingOrderGoodsDetail['id'];
            $sortingGoodsInfo = $sortingModule->getSortingGoodsDetailByParams($personId, $sortingId, $goodsId, $skuId);
            if (!empty($sortingGoodsInfo)) {
                $orderGoodsDetail['receivingGoodsNums'] = $sortingGoodsInfo['sorting_ok_weight'];
            }
        }
        $numOrWeightTag = '数量';
        $goodsModule = new GoodsModule();
        $unit = $goodsModule->getGoodsUnitByParams($orderGoodsDetail['goodsId'], $orderGoodsDetail['skuId']);
        $exReturnNum = explode('.', $returnNum);
        if ($orderGoodsDetail['is_weight'] != 1) {//标品
            if (count($exReturnNum) > 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '标品退货数量只支持整数');
            }
        } else {//非标品
            $numOrWeightTag = '重量';
            if ($exReturnNum > 1) {
                $pointNum = $exReturnNum[1];
                if (mb_strlen($pointNum) > 3) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非标品退货重量最多支持3位小数位');
                }
            }
        }
        if ($returnNum > $orderGoodsDetail['receivingGoodsNums']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "退货{$numOrWeightTag}不能大于实收{$numOrWeightTag}{$orderGoodsDetail['receivingGoodsNums']}{$unit}");
        }
        $orderField = 'orderId,orderNo,userId,shopId,orderStatus,isRefund,receiveTime';
        $orderDetail = $orderModule->getOrderInfoById($orderId, $orderField, 2);
        if ($orderDetail['orderStatus'] != 4) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '未确认收货订单不能申请售后');
        }
        if ($orderDetail['isRefund'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已退款订单不能申请售后');
        }
        $afterSaleTime = !empty($GLOBALS['CONFIG']['AfterSaleTime']) ? $GLOBALS['CONFIG']['AfterSaleTime'] : 15;
        $overTime = strtotime($orderDetail['receiveTime']) + $afterSaleTime * 24 * 3600;
        if ($overTime < time()) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '售后期限已过，请联系商家');
        }
        $complainLog = $orderModule->getOrderGoodsComplainsDetailByParams($orderId, $goodsId, $skuId);
        if (!empty($complainLog)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请勿重复申请');
        }
        $userId = $orderDetail['userId'];
        $userDetail = (new UsersModule())->getUsersDetailById($userId, 'userId,userName', 2);
        $complainsData = array();
        $complainsData['orderId'] = $orderId;
        $complainsData['complainType'] = $complainType;
        $complainsData['deliverRespondTime'] = $deliverRespondTime;
        $complainsData['complainContent'] = $complainContent;
        $complainsData['complainAnnex'] = $complainAnnex;
        $complainsData['goodsId'] = $goodsId;
        $complainsData['skuId'] = $skuId;
        $complainsData['returnNum'] = $returnNum;
        $complainsData['complainTargetId'] = $orderDetail['userId'];
        $complainsData['respondTargetId'] = $orderDetail['shopId'];
        $complainsData['needRespond'] = 1;//系统自动递交给店家
        $complainsData['complainStatus'] = 0;//待处理
        $trans = new Model();
        $trans->startTrans();
        $complainId = $orderModule->saveOrderGoodsComplains($complainsData, $trans);
        if (empty($complainId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '售后申请提交失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_order_complains',
            'dataId' => $complainId,
            'actionUserId' => $orderDetail['userId'],
            'actionUserName' => $userDetail['userName'],
            'fieldName' => 'complainStatus',
            'fieldValue' => 0,
            'remark' => '申请退货',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '售后申请提交失败', '日志记录失败');
        }
        //商城信息通知
        $messsageData = [
            'msgType' => 0,
            'sendUserId' => 0,
            'receiveUserId' => $orderDetail['shopId'],
            'msgContent' => "您有新的被投诉订单【" . $orderDetail['orderNo'] . "】，请及时回应以免影响您的店铺评分。",
            'msgStatus' => 0,
            'msgFlag' => 1,
        ];
        $messageId = (new MessageModule())->saveMessages($messsageData, $trans);
        if (empty($messageId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '售后申请提交失败', '未能成功发送消息到商家');
        }
        $canDistributionRes = (new DistributionModule())->cancelOrderGoodsDistribution($orderId, $goodsId, $trans);//取消订单商品的分销金
        if ($canDistributionRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '售后申请提交失败', $canDistributionRes['msg']);
        }
        $trans->commit();
        return returnData(true);
    }

    //售后列表
//    public function MobcomplainsList($userId)
//    {
//        $mod_orders = M('orders');
//        $mod_order_goods = M('order_goods');
//        $mod_goods = M('goods');
//        $mod_shops = M('shops');
//
//        unset($where);
//        $field_name = array(
//            'orderId',
//            'orderNo',
//            'shopId',
//            'orderStatus',
//            'totalMoney',
//            'deliverMoney',
//            'payType',
//            'isSelf',
//            'isPay',
//            'deliverType',
//            'userId',
//            'createTime',
//            'orderSrc',
//            'payFrom',
//            'receiveTime'
//        );
//
//
//        $where['receiveTime'] = array('exp', " and DATE_ADD(receiveTime,INTERVAL {$GLOBALS['CONFIG']['AfterSaleTime']} DAY) > NOW()");
//        $where['orderStatus'] = 4;
//        $where['userId'] = $userId;
//        $res_orders_list = $mod_orders->where($where)->field($field_name)->order('receiveTime desc')->select();
//        //获取订单下所有的商品
//
//        unset($field_name);
//        $field_name = array(
//            'goodsImg',
//            'shopId',
//            'goodsStock',
//            'saleCount',
//            'goodsUnit',
//            'goodsSpec'
//        );
//
//
//        for ($i = 0; $i < count($res_orders_list); $i++) {
//            $res_orders_list[$i]['goodsList'] = $mod_order_goods->where("orderId = '{$res_orders_list[$i]['orderId']}'")->select();
//
//
//            //获取店铺头像 和 店铺姓名
//            $mod_shop_resdata = $mod_shops->where("shopId = '{$res_orders_list[$i]['shopId']}'")->field(array('shopImg', 'shopName'))->find();
//            $res_orders_list[$i]['shopImg'] = $mod_shop_resdata['shopImg'];
//            $res_orders_list[$i]['shopName'] = $mod_shop_resdata['shopName'];
//
//            for ($j = 0; $j < count($res_orders_list[$i]['goodsList']); $j++) {
//                $oneGood = $mod_goods->where("goodsId = '{$res_orders_list[$i]['goodsList'][$j]['goodsId']}'")->field($field_name)->find();
//                $res_orders_list[$i]['goodsList'][$j]['goodsImg'] = $oneGood['goodsImg'];
//                $res_orders_list[$i]['goodsList'][$j]['shopId'] = $oneGood['shopId'];
//                $res_orders_list[$i]['goodsList'][$j]['goodsStock'] = $oneGood['goodsStock'];
//                $res_orders_list[$i]['goodsList'][$j]['saleCount'] = $oneGood['saleCount'];
//                $res_orders_list[$i]['goodsList'][$j]['saleCount'] = $oneGood['saleCount'];
//                $res_orders_list[$i]['goodsList'][$j]['goodsSpec'] = $oneGood['goodsSpec'];
//
//
//            }
//        }
//        //======update start======
//        if (count($res_orders_list) > 0) {
//            foreach ($res_orders_list as $val) {
//                $orderId[] = $val['orderId'];
//            }
//            $orderIdStr = implode(',', $orderId);
//            if (empty($orderId)) {
//                $orderIdStr = 0;
//            }
//            $complainsTab = M('order_complains');
//            $complainsList = $complainsTab->where("orderId IN($orderIdStr)")->select();
//            if (empty($complainsList)) { //后加
//                foreach ($res_orders_list as $key => $val) {
//                    foreach ($val['goodsList'] as $gk => $gv) {
//                        $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;
//                        $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'];
//                    }
//                }
//            }
//            foreach ($res_orders_list as $key => $val) {
//                foreach ($complainsList as $k => $v) {
//                    //整单退款
//                    if ($v['orderId'] == $val['orderId'] && empty($v['goodsId'])) {
//                        unset($res_orders_list[$key]);
//                    }
//                    //单商品退款
//                    if (count($val['goodsList']) > 1) {
//                        //一个订单多种商品
//                        foreach ($val['goodsList'] as $gk => $gv) {
//                            //统计同一个订单商品售后了几次
//                            $complainCount = $complainsTab->where("orderId='" . $gv['orderId'] . "' AND goodsId='" . $gv['goodsId'] . "' and skuId='" . $gv['skuId'] . "'")->count();
//                            if ($gv['goodsId'] == $v['goodsId'] && $gv['skuId'] == $v['skuId']) {
//                                if ($gv['goodsNums'] == $complainCount) {
//                                    //订单所包含的该商品已全部售后
//                                    //unset($res_orders_list[$key]);
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 0;
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = 0;
//                                } else {
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'] - $complainCount;//该商品还能继续售后的次数
//                                }
//                            } else {
//                                $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
//                                $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'] - $complainCount;//该商品还能继续售后的次数
//                            }
//                        }
//                    } else {
//                        //一个订单单种商品
//                        foreach ($val['goodsList'] as $gk => $gv) {
//                            //统计同一个订单商品售后了几次
//                            $complainCount = $complainsTab->where("orderId='" . $gv['orderId'] . "' AND goodsId='" . $gv['goodsId'] . "' and skuId='" . $gv['skuId'] . "'")->count();
//                            //后加
//                            if ($complainCount) {
//                                unset($res_orders_list[$key]);
//                            } else {
//                                if ($gv['goodsId'] == $v['goodsId']) {
//                                    if ($gv['goodsNums'] == $complainCount) {
//                                        //订单所包含的该商品已全部售后
//                                        unset($res_orders_list[$key]);
//                                    } else {
//                                        $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
//                                        $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'] - $complainCount;//该商品还能继续售后的次数
//                                    }
//                                } else {
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
//                                    $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'] - $complainCount;//该商品还能继续售后的次数
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//            //去除已售后所有商品的订单
//            foreach ($res_orders_list as $key => $val) {
//                $existCount = count($val['goodsList']);
//                $complainState = 0;
//                foreach ($val['goodsList'] as $gk => $gv) {
//                    if ($gv['complainNum'] <= 0) {
//                        $gv['complainState'] = 0;
//                        $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 0;
//                    }
//                    if (!isset($gv['complainState'])) {
//                        $res_orders_list[$key]['goodsList'][$gk]['complainState'] = 1;
//                        $res_orders_list[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'];
//                    }
//                    if (isset($gv['complainState']) && $gv['complainState'] == 0) {
//                        $complainState += 1;
//                    }
//                }
//                if ($existCount == $complainState) {
//                    unset($res_orders_list[$key]);
//                }
//                if (empty($res_orders_list[$key]['goodsList'][$gk]['id'])) {
//                    unset($res_orders_list[$key]);
//                }
//            }
//        }
//        //======update end======
////        $apiRet['apiCode']=0;
////        $apiRet['apiInfo']='售后列表获取成功';
////        $apiRet['apiState']='success';
////        $apiRet['apiData']=array_values($res_orders_list);
//        $res_orders_list = array_values($res_orders_list);
//        $res_orders_list = empty($res_orders_list) ? array() : $res_orders_list;
//        return $res_orders_list;
//
//    }

    /**
     * PS:该业务逻辑完全copy以上注释的方法
     * 售后列表
     * @param int $userId 用户id
     * */
    public function MobcomplainsList(int $userId)
    {
        $orderTab = M('orders');
        $orderGoodsTab = M('order_goods');
        $goodsTab = M('goods');
        $shopTab = M('shops');
        $fields = array(
            'orderId',
            'orderNo',
            'shopId',
            'orderStatus',
            'totalMoney',
            'realTotalMoney',
            'deliverMoney',
            'payType',
            'isSelf',
            'isPay',
            'deliverType',
            'userId',
            'createTime',
            'orderSrc',
            'payFrom',
            'receiveTime'
        );
        $where = [];
        //$where['userDelete'] = 1;
        $where['receiveTime'] = array('exp', " and DATE_ADD(receiveTime,INTERVAL {$GLOBALS['CONFIG']['AfterSaleTime']} DAY) > NOW()");
        $where['orderStatus'] = 4;
        $where['userId'] = $userId;
        $orderList = $orderTab
            ->where($where)
            ->field($fields)
            ->order('receiveTime desc')
            ->select();
        //获取订单下所有的商品
        $fields = array(
            'goodsImg',
            'shopId',
            'goodsStock',
            'saleCount',
            'goodsUnit',
            'goodsSpec'
        );
        $sortingModule = new SortingModule();
        foreach ($orderList as &$item) {
            $item['goodsList'] = [];
            //获取店铺头像 和 店铺姓名
            $where = [];
            $where['shopId'] = $item['shopId'];
            $shopInfo = $shopTab
                ->where($where)
                ->field(['shopImg', 'shopName'])
                ->find();
            $item['shopImg'] = $shopInfo['shopImg'];
            $item['shopName'] = $shopInfo['shopName'];
            $where = [];
            $where['orderId'] = $item['orderId'];
            $orderGoodsList = (array)$orderGoodsTab
                ->where($where)
                ->select();
            if (!empty($orderGoodsList)) {
                foreach ($orderGoodsList as &$gval) {
                    if ($gval['is_weight'] == 1) {
                        $gval['SuppPriceDiff'] = 1;
                    } else {
                        $gval['SuppPriceDiff'] = -1;
                    }
                    $gval['goodsNums'] = (float)$gval['goodsNums'];//购买数量/重量
                    $gval['receivingGoodsNums'] = $gval['goodsNums'];//实收数量/重量
                    $gval['weight'] = (float)$gval['weight'];
                    if ($gval['weight'] > 0) {
                        $gval['receivingGoodsNums'] = $gval['weight'];
                    }
                    $orderId = $gval['orderId'];
                    $goodsId = $gval['goodsId'];
                    $skuId = $gval['skuId'];
                    $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                    if (!empty($sortingOrderGoodsDetail)) {
                        $personId = $sortingOrderGoodsDetail['personId'];
                        $sortingId = $sortingOrderGoodsDetail['id'];
                        $sortingGoodsInfo = $sortingModule->getSortingGoodsDetailByParams($personId, $sortingId, $goodsId, $skuId);
                        if (!empty($sortingGoodsInfo)) {
                            $gval['receivingGoodsNums'] = $sortingGoodsInfo['sorting_ok_weight'];
                        }
                    }
                    $where = [];
                    $where['goodsId'] = $gval['goodsId'];
                    $goodsInfo = $goodsTab
                        ->where($where)
                        ->field($fields)
                        ->find();
                    $gval['goodsImg'] = $goodsInfo['goodsImg'];
                    $gval['shopId'] = $goodsInfo['shopId'];
                    $gval['goodsStock'] = $goodsInfo['goodsStock'];
                    $gval['saleCount'] = $goodsInfo['saleCount'];
                    $gval['saleCount'] = $goodsInfo['saleCount'];
                    $gval['goodsSpec'] = $goodsInfo['goodsSpec'];
                }
                unset($gval);
                $item['goodsList'] = $orderGoodsList;
            }
        }
        unset($item);
        //======update start======
        if (count($orderList) > 0) {
            $orderId = array_unique(array_column($orderList, 'orderId'));
            $orderIdStr = implode(',', $orderId);
            if (empty($orderId)) {
                $orderIdStr = 0;
            }
            $complainsTab = M('order_complains');
            $complainsList = $complainsTab->where("orderId IN({$orderIdStr})")->select();
            if (empty($complainsList)) { //后加
                foreach ($orderList as $key => $val) {
                    foreach ($val['goodsList'] as $gk => $gv) {
                        $orderList[$key]['goodsList'][$gk]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
                        //$orderList[$key]['goodsList'][$gk]['complainNum'] = $gv['goodsNums'];//该商品还能继续售后的次数
                        $orderList[$key]['goodsList'][$gk]['complainNum'] = 1;
                    }
                }
            } else {
                foreach ($orderList as $key => $val) {
                    foreach ($val['goodsList'] as $v_key => $v_goods) {
                        $orderList[$key]['goodsList'][$v_key]['complainState'] = 1;//是否还可以申请售后(0=>否,1=>是)
                        $orderList[$key]['goodsList'][$v_key]['complainNum'] = 1;
                        foreach ($complainsList as $v) {
                            //整单退款
                            if ($v['orderId'] == $val['orderId'] && empty($v['goodsId'])) {
                                unset($orderList[$key]);
                            }
                            //单商品退款
                            if ($val['orderId'] == $v['orderId'] && $v_goods['goodsId'] == $v['goodsId'] && $v_goods['skuId'] == $v['skuId']) {
                                $orderList[$key]['goodsList'][$v_key]['complainState'] = 0;//是否还可以申请售后(0=>否,1=>是)
                                $orderList[$key]['goodsList'][$v_key]['complainNum'] = 0;
                            }
                        }
                    }
                }
            }
            //去除已售后所有商品的订单
            foreach ($orderList as $key => $val) {
                $existCount = count($val['goodsList']);
                $complainState = 0;
                foreach ($val['goodsList'] as $gk => $gv) {
                    if ($gv['complainNum'] <= 0) {
                        $gv['complainState'] = 0;
                        $orderList[$key]['goodsList'][$gk]['complainState'] = 0;
                    }
                    if ($gv['complainState'] == 0) {
                        $complainState += 1;
                    }
                    if ($gv['complainState'] == 0) {
                        unset($orderList[$key]['goodsList'][$gk]);
                    }
                }
                if ((int)$existCount == (int)$complainState) {
                    unset($orderList[$key]);
                }
//                if (empty($orderList[$key]['goodsList'][$gk]['id'])) {
//                    unset($orderList[$key]);
//                }
                if (!empty($orderList[$key]['goodsList'])) {
                    $orderList[$key]['goodsList'] = array_values($orderList[$key]['goodsList']);
                }
            }
        }
        $orderList = (array)array_values($orderList);
        return $orderList;
    }

    /**
     * 申请售后-订单商品详情
     * @param int $id 订单商品唯一标识id
     * @param float $returnNum 退货数量/重量
     * @return array
     * */
    public function getMobcomplainsGoodsDetail(int $id, float $returnNum)
    {
        $orderModule = new OrdersModule();
        $orderGoodsField = 'id,orderId,goodsId,goodsName,skuId,goodsNums,goodsPrice,skuSpecAttr,goodsThums,is_weight,weight';
        $orderGoodsDetail = $orderModule->getOrderGoodsInfoById($id, $orderGoodsField, 2);
        if (empty($orderGoodsDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单商品信息有误');
        }
        $orderGoodsDetail['receivingGoodsNums'] = (float)$orderGoodsDetail['goodsNums'];
        $orderGoodsDetail['weight'] = (float)$orderGoodsDetail['weight'];
        if ($orderGoodsDetail['weight'] > 0) {
            $orderGoodsDetail['receivingGoodsNums'] = $orderGoodsDetail['weight'];
        }
        $orderId = (int)$orderGoodsDetail['orderId'];
        $goodsId = (int)$orderGoodsDetail['goodsId'];
        $skuId = (int)$orderGoodsDetail['skuId'];
        $sortingModule = new SortingModule();
        $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
        if (!empty($sortingOrderGoodsDetail)) {
            $personId = $sortingOrderGoodsDetail['personId'];
            $sortingId = $sortingOrderGoodsDetail['id'];
            $sortingGoodsInfo = $sortingModule->getSortingGoodsDetailByParams($personId, $sortingId, $goodsId, $skuId);
            if (!empty($sortingGoodsInfo)) {
                $orderGoodsDetail['receivingGoodsNums'] = $sortingGoodsInfo['sorting_ok_weight'];
            }
        }
        $exReturnNum = explode('.', $returnNum);
        $orderGoodsDetail['SuppPriceDiff'] = -1;
        $numOrWeightTag = '数量';
        if ($orderGoodsDetail['is_weight'] != 1) {//标品
            if (count($exReturnNum) > 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '标品退货数量只支持整数');
            }
        } else {//非标品
            $numOrWeightTag = '重量';
            $orderGoodsDetail['SuppPriceDiff'] = 1;
            if ($exReturnNum > 1) {
                $pointNum = $exReturnNum[1];
                if (mb_strlen($pointNum) > 3) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非标品退货重量最多支持3位小数位');
                }
            }
        }
        $orderGoodsDetail['goodsNums'] = (float)$orderGoodsDetail['goodsNums'];
        $goodsPrice = (float)$orderGoodsDetail['goodsPrice'];
//        $goodsNums = $orderGoodsDetail['goodsNums'];
        $receivingGoodsNums = $orderGoodsDetail['receivingGoodsNums'];
        $goodsModule = new GoodsModule();
        $unit = $goodsModule->getGoodsUnitByParams($goodsId, $skuId);
        if ($returnNum > $receivingGoodsNums) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "申请商品退货的{$numOrWeightTag}不能大于实收{$numOrWeightTag}{$receivingGoodsNums}{$unit}");
        }
        $complainsLog = $orderModule->getOrderGoodsComplainsDetailByParams($orderId, $goodsId, $skuId);
        if (!empty($complainsLog)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '该商品已经申请过了，请勿重复申请');
        }
        $orderGoodsDetail['goodsPriceTotal'] = bc_math($orderGoodsDetail['goodsNums'], $goodsPrice, 'bcmul', 2);
        $orderGoodsDetail['goodsPriceTotal'] = sprintfNumber($orderGoodsDetail['goodsPriceTotal']);
        $orderGoodsDetail['realPayAmount'] = $orderGoodsDetail['goodsPriceTotal'];
        $orderGoodsDetail['returnAmount'] = bc_math($goodsPrice, $returnNum, 'bcmul', 2);
        $orderGoodsDetail['returnAmount'] = sprintfNumber($orderGoodsDetail['returnAmount']);
        return returnData($orderGoodsDetail);
    }

    //用户售后 申请 列表 分页
//    public function userComplainsList($page, $userId)
//    {
//        $pageDataNum = I('pageSize', 10, 'intval');//每页10条数据
//
//        $where["complainTargetId"] = $userId;
//
//        $Model = M('order_complains');
//        $data = $Model
//            ->where($where)
//            ->order("complainTime desc")
//            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
//            ->select();
//
//        $order_goods_mod = M('order_goods');
//        $orders_mod = M('orders');
//        $mod_order_complainsrecord = M('order_complainsrecord');
//
//
//        for ($i = 0; $i < count($data); $i++) {
//
//            $where = array();
//            $where['orderId'] = $data[$i]['orderId'];
//            $where['goodsId'] = $data[$i]['goodsId'];
//            $where['userId'] = $userId;
//            $data[$i]['complainsrecord'] = $mod_order_complainsrecord->where($where)->find();
//
//            $data[$i]['goodDetail'] = $order_goods_mod->where("goodsId = {$data[$i]['goodsId']}")->field('goodsName,goodsThums,goodsNums,goodsId,skuId,skuSpecAttr')->find();
//            $data[$i]['orderNo'] = $orders_mod->where("orderId = {$data[$i]['orderId']}")->field('orderNo')->find()['orderNo'];
//
//        }
//
////        $apiRet['apiCode']=0;
////        $apiRet['apiInfo']='用户售后列表获取成功';
////        $apiRet['apiState']='success';
////        $apiRet['apiData']=$data;
//        $data = empty($data) ? array() : $data;
//        $apiRet = returnData($data);
//        return $apiRet;
//
//    }

    /**
     * 售后记录-用户售后申请列表
     * @param int $userId 用户id
     * @param int $page 页码
     * @param int $pageSize 分页条数
     * @return array
     * */
    public function userComplainsList(int $userId, int $page, int $pageSize)
    {
        $where = [];
        $where["complainTargetId"] = $userId;
        $orderComplainsTab = M('order_complains');
        $data = $orderComplainsTab
            ->where($where)
            ->order("createTime desc")
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        $orderGoodsTab = M('order_goods');
        $orderTab = M('orders');
        $orderComplainsrecordTab = M('order_complainsrecord');
//        $orderModule = new OrdersModule();
        foreach ($data as $key => &$item) {
            $item['returnNum'] = (float)$item['returnNum'];
//            if ($item['complainStatus'] == 0) {
//                //该方法中的状态值兼容前端,临时修改,因为这个地方后台已被产品重构,但是前端没有重构页面
//                $item['complainStatus'] = 1;
//            } else {
//                unset($item['complainStatus']);
//            }
            if ($item['complainStatus'] == -1) {
                $item['returnAmountStatus'] = -1;
            }
            $item['returnAmountTime'] = '';//退款时间
            if (strtotime($item['respondTime']) == false) {
                $item['respondTime'] = '';
            }
            $item['returnAmountRemark'] = '';
            if ($item['returnAmountStatus'] == 1) {
                $where = [];
                $where['orderId'] = $item['orderId'];
                $where['goodsId'] = $item['goodsId'];
                $where['skuId'] = $item['skuId'];
                $where['userId'] = $userId;
                $orderComplainsrecordInfo = $orderComplainsrecordTab->where($where)->find();
                if (!empty($orderComplainsrecordInfo)) {
                    $item['returnAmountTime'] = $orderComplainsrecordInfo['addTime'];
                }
                $item['complainsrecord'] = true;
            }
            if ($item['returnAmountStatus'] == -1) {
                $logParams = array(
                    'tableName' => "wst_order_complains",
                    'dataId' => $item['complainId'],
                    'fieldName' => 'returnAmountStatus',
                    'fieldValue' => -1,
                );
                $complainLogDetal = (new TableActionLogModule())->getLogDetailByParams($logParams);
                if (!empty($complainLogDetal)) {
                    $item['returnAmountRemark'] = $complainLogDetal['remark'];
                }
            }
            if ($item['complainStatus'] == -1) {
                $logParams = array(
                    'tableName' => "wst_order_complains",
                    'dataId' => $item['complainId'],
                    'fieldName' => 'complainStatus',
                    'fieldValue' => -1,
                );
                $complainLogDetal = (new TableActionLogModule())->getLogDetailByParams($logParams);
                if (!empty($complainLogDetal)) {
                    $item['returnAmountRemark'] = $complainLogDetal['remark'];
                }
            }
            $where = [];
            $where['goodsId'] = $item['goodsId'];
            $where['skuId'] = $item['skuId'];
            $where['orderId'] = $item['orderId'];
            $goodsDetail = $orderGoodsTab
                ->where($where)
                ->field('orderId,goodsName,goodsThums,goodsNums,goodsId,skuId,skuSpecAttr')
                ->find();
            $goodsDetail['goodsNums'] = $item['returnNum'];
            $goodsDetail['returnNum'] = $item['returnNum'];
            $item['goodDetail'] = $goodsDetail;
            $where = [];
            $where['orderId'] = $item['orderId'];
            $item['orderNo'] = $orderTab
                ->where($where)
                ->getField('orderNo');
            if (empty($item['orderNo'])) {
                unset($data[$key]);
            }
        }
        unset($item);
        return returnData((array)array_values($data));

    }

    //用户售后详情 退款详情
    public function userComplainsDetail($complainId, $userId)
    {
        $orderComplainsTab = M('order_complains');
        $ordersTab = M('orders');
        $orderGoodsTab = M('order_goods');
        $orderComplainsrecordTab = M('order_complainsrecord');
        $where = [];
        $where['complainId'] = $complainId;
        $where['complainTargetId'] = $userId;
        $data = $orderComplainsTab->where($where)->find();
        if (empty($data)) {
            return [];
        }
        $data['respondTime'] = strtotime($data['respondTime']) == false ? '' : $data['respondTime'];
        $where = [];
        $where['orderId'] = $data['orderId'];
        $data['orderNo'] = $ordersTab->where($where)->getField('orderNo');
        $where = [];
        $where['orderId'] = $data['orderId'];
        $where['goodsId'] = $data['goodsId'];
        $where['skuId'] = $data['skuId'];
        $orderGoodsInfo = $orderGoodsTab->where($where)->find();
        $data['returnNum'] = (float)$data['returnNum'];
        $orderGoodsInfo['returnNum'] = $data['returnNum'];
        $orderGoodsInfo['goodsNums'] = $data['returnNum'];
        if ($data['returnAmountStatus'] != 1) {
            $data['returnAmount'] = sprintfNumber($data['returnNum'] * $orderGoodsInfo['goodsPrice']);
        }
        $orderGoodsInfo['returnAmount'] = $data['returnAmount'];
        $data['orderGoodsInfo'] = $orderGoodsInfo;
        $data['returnAmountTime'] = '';//退款时间
        $data['returnAmountRemark'] = '';//退款备注
        if ($data['returnAmountStatus'] == 1) {
            $where = [];
            $where['orderId'] = $data['orderId'];
            $where['goodsId'] = $data['goodsId'];
            $where['skuId'] = $data['skuId'];
            $where['userId'] = $userId;
            $recordInfo = (array)$orderComplainsrecordTab->where($where)->find();
            if (!empty($recordInfo)) {
                $data['returnAmountTime'] = $recordInfo['addTime'];
                $data['finalResultTime'] = $recordInfo['addTime'];//兼容前端之前的代码
            }
            $data['complainsrecord'] = true;
            $logParams = array(
                'tableName' => "wst_order_complains",
                'dataId' => $complainId,
                'fieldName' => 'returnAmountStatus',
                'fieldValue' => 1,
            );
            $complainLogDetal = (new TableActionLogModule())->getLogDetailByParams($logParams);
            if (!empty($complainLogDetal)) {
                $data['returnAmountRemark'] = $complainLogDetal['remark'];
            }
        }
        if ($data['returnAmountStatus'] == -1) {
            $logParams = array(
                'tableName' => "wst_order_complains",
                'dataId' => $complainId,
                'fieldName' => 'returnAmountStatus',
                'fieldValue' => -1,
            );
            $complainLogDetal = (new TableActionLogModule())->getLogDetailByParams($logParams);
            if (!empty($complainLogDetal)) {
                $data['returnAmountRemark'] = $complainLogDetal['remark'];
            }
        }
        if ($data['complainStatus'] == -1) {
            $data['returnAmountStatus'] = -1;
            $logParams = array(
                'tableName' => "wst_order_complains",
                'dataId' => $complainId,
                'fieldName' => 'complainStatus',
                'fieldValue' => -1,
            );
            $complainLogDetal = (new TableActionLogModule())->getLogDetailByParams($logParams);
            if (!empty($complainLogDetal)) {
                $data['returnAmountRemark'] = $complainLogDetal['remark'];
            }
        }
//        if ($data['complainStatus'] == 0) {
//            //该方法中的状态值兼容前端,临时修改,因为这个地方后台已被产品重构,但是前端没有重构页面
//            $data['complainStatus'] = 1;
//        } else {
//            unset($data['complainStatus']);
//        }
        return $data;
    }

    /**
     * 获取订单商品的退款金额
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param int $skuId 商品skuId
     * @return array
     * */
    public function getComplainsMoney(int $orderId, int $goodsId, int $skuId)
    {
        $orderModule = new OrdersModule();
        $money = 0;
        $orderGoodsParams = array(
            'orderId' => $orderId,
            'goodsId' => $goodsId,
            'skuId' => $skuId,
        );
        $orderGoodsDetail = $orderModule->getOrderGoodsInfoByParams($orderGoodsParams, 'goodsNums,goodsPrice', 2);
        if (empty($orderGoodsDetail)) {
            return returnData($money);
        }
        $money = $orderGoodsDetail['goodsNums'] * $orderGoodsDetail['goodsPrice'];
        $money = sprintfNumber($money);
        $where = array(
            'orderId' => $orderId,
            'goodsId' => $goodsId,
            'skuId' => $skuId,
        );
        $orderComplainsDetail = M('order_complains')->where($where)->find();
        if (!empty($orderComplainsDetail)) {
            if ($orderComplainsDetail['returnAmountStatus'] == 1) {
                $money = $orderComplainsDetail['returnAmount'];
            }
        }
        return returnData($money);
    }

    //获取商城第一级分类下的商品 先缓存数据 带分页数据缓存注意 在排序数据1
    public function getAdminTypeidIsOneGoodsList($adcode, $lat, $lng, $typeThreeId, $page = 1, $userId = 0, $pageSize = 20)
    {

        $data = S("NIAO_CACHE_getShopTypeidIsOneGoodsList_{$lat}-{$lng}_{$page}_{$typeThreeId}");
        if (empty($data)) {
            $pageDataNum = $pageSize;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            //$where["wst_shops.areaId3"] = $adcode;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return returnData();
            }
            $shopIdArr = array_column($canUseShopList, 'shopId');
            $where["wst_shops.shopId"] = array("in", $shopIdArr);
            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
            $where["wst_goods.goodsCatId1"] = $typeThreeId;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购
            if ($userId) {
                $user_info = M('users')->where(array('userId' => $userId))->find();
                $expireTime = $user_info['expireTime'];//会员过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['wst_goods.isMembershipExclusive'] = 0;
                }
            }

            //配送区域条件
            //$where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
                //->join('LEFT JOIN wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order('wst_goods.shopCatId2 desc,wst_goods.shopGoodsSort desc,wst_goods.isAdminBest desc,wst_goods.isAdminRecom desc')
                ->field('wst_goods.goodsId,wst_goods.markIcon,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.goodsCatId1,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople,wst_goods.memberPrice,wst_goods.IntelligentRemark')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = filterRecycleGoods($data);//过滤回收站的商品
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            /* foreach ($data as $k=>$user) {
    		  $data[$k]['typeimg'] = M('goods_cats')->where("catId = '{$user['goodsCatId1']}'")->find()['typeimg'];;
    		} */

            S("NIAO_CACHE_getShopTypeidIsOneGoodsList_{$lat}-{$lng}_{$page}_{$typeThreeId}", $data, C("allApiCacheTime"));
        }

        //计算距离 不参与缓存
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['kilometers'] = sprintf("%.2f", getDistanceBetweenPointsNew($data[$i]['latitude'], $data[$i]['longitude'], $lat, $lng)['kilometers']);
        }

        $shopsDataSort = array();
        foreach ($data as $user) {
            $shopsDataSort[] = $user['kilometers'];
        }
        array_multisort($shopsDataSort, SORT_ASC, SORT_NUMERIC, $data);//从低到高排序

        if (empty($data)) {
            $data = [];
        }
        $resdata['goods'] = $data;
        $resdata['typeimg'] = M('goods_cats')->where("catId = '{$typeThreeId}'")->find()['typeimg'];

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$resdata;
        $apiRet = returnData($resdata);
        return $apiRet;
    }

    //

    /***********条件筛选附近的商家************/
    public function WhereGetNearby()
    {
        /* 	$data = S("WhereGetDistricts_".$areaId3);
		if(empty($data)){
			$mod = M("shops")->where("areaId3 = '{$areaId3}' and shopFlag = '1' and shopStatus = '1'")->select();
			S("getDistricts_".$areaId3,$mod,C("app_shops_cache_time"));
			return $mod;
		}
		return $data; */
    }

    public function getUsersDynamiccode($parameter = array())
    {
        if (!$parameter['userId']) {
//            $apiRet['apiCode']='000101';
//            $apiRet['apiInfo']='会员数据丢失';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '会员数据丢失');
            return $apiRet;
        }
        $parameter['pastTime'] = 60;//过期时间
        $userCode = retuenUsersDynamiccode($parameter);
        if (!$userCode) {
//            $apiRet['apiCode']='000103';
//            $apiRet['apiInfo']='动态获取失败';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '动态获取失败');

            return $apiRet;
        }
        $saveData = array(
            'userId' => $parameter['userId'],
            'state' => 2,
            'addtime' => date('Y-m-d H:i:s'),
            'code' => $userCode
        );
        $m = M('users_dynamiccode');
        $res = $m->add($saveData);
        if (!$res) {
//            $apiRet['apiCode']='000102';
//            $apiRet['apiInfo']='动态获取失败';
//            $apiRet['apiState']='error';
            $apiRet = returnData(null, -1, 'error', '动态获取失败');

            return $apiRet;
        }
        //成功返回
        $resdata['code'] = $userCode;
//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$resdata;
        $apiRet = returnData($resdata);

        return $apiRet;
    }

    //区分可用优惠券列表和不可用列表 检测权限
    public function getCouponAuthList($goodsId, $userId)
    {
        //获取用户未使用的优惠券
        $couponList = $this->getUserCoupons($userId);

        $goodsId = explode(",", $goodsId);//分割出商品id

        $yes = [];
        $no = [];

        foreach ($couponList as $couponA) {
            //检测商品是否满足优惠券权限
            $msg = '';
            $checkArr = array(
                'couponId' => $couponA['couponId'],
                'goods_id_arr' => $goodsId,
            );
            $checkRes = check_coupons_auth($checkArr, $msg);
            $couponA['info'] = $msg;
            if (!$checkRes) {
                array_push($no, $couponA);
            } else {
                array_push($yes, $couponA);
            }
        }

        /**
         * 只能使用购买商品对应店铺的优惠券
         * */
        if (!empty($yes)) {
            $shopWhere['goodsId'] = ['IN', $goodsId];
            $shops = M('goods')->where($shopWhere)->field('shopId')->select();
            foreach ($shops as $val) {
                $shopIdArr[] = $val['shopId'];
            }
            foreach ($yes as $key => $val) {
                if (is_null($val['auth_goods'])) {
                    $yes[$key]['auth_goods'] = [];
                }
                if (is_null($val['auth_cats'])) {
                    $yes[$key]['auth_cats'] = [];
                }
                if (is_null($val['auth_shops'])) {
                    $yes[$key]['auth_shops'] = [];
                }
                if ($val['shopId'] > 0 && !in_array($val['shopId'], $shopIdArr)) {
                    array_push($no, $yes[$key]);
                    unset($yes[$key]);
                }
            }
            $yes = array_values($yes);
        }

        return array('yes' => $yes, 'no' => $no);
    }

    /**
     * @param $userId
     * @return mixed
     * 根据订单id生成发票
     */
    public function addInvoiceReceipt($userId)
    {
        $ordersId = I('ordersId', '', 'trim');
        $invoiceClient = (int)I('invoiceClient');
        if (empty($ordersId) || empty($invoiceClient)) {
            return returnData(null, -1, 'error', '参数不全');
        }
        $where['orderId'] = array('in', $ordersId);
        $where['isPay'] = 1;//是否支付[0:未支付 1:已支付]
        $where['orderStatus'] = 4;//用户确认收货
        $where['isRefund'] = 0;//是否退款[0:否 1：是]
        $where['orderFlag'] = 1;//订单有效标志[-1：删除 1:有效]
        $where['userDelete'] = 1;//用户删除状态(-1:已删除|1:未删除)
        $order = M('orders');
        $data = $order->where($where)->select();
        $shopInfo = $order->where($where)->group('shopId')->field('shopId')->select();
        $receipt = [];
        $orders = [];
        foreach ($shopInfo as $value) {
            $ordersMoney = 0.00;
            $str = '';
            foreach ($data as $v) {
                if ($value['shopId'] == $v['shopId']) {
                    if ($v['userId'] != $userId) {
                        $apiRet = returnData(null, -1, 'error', '订单id有误');
                        return $apiRet;
                    }
                    if ($v['invoiceClient'] == 0) {
                        $invoiceWhere['id'] = $invoiceClient;
                        $invoiceWhere['userId'] = $userId;
                        $invoiceInfo = M('invoice')->where($invoiceWhere)->find();
                        if (empty($invoiceInfo)) {
                            return returnData(null, -1, 'error', '发票信息不存在');
                        }
                        $str .= "," . $v['orderId'];
                        $ordersMoney = $ordersMoney + $v['realTotalMoney'];
                    } else {
                        if (!empty($v['receiptId'])) {
                            return returnData(null, -1, 'error', '发票已申请');
                        }
                    }
                }
            }
            $str = ltrim($str, ",");
            $orders['orderId'] = $str;
            $orders['shopId'] = $value['shopId'];
            $receipt['headertype'] = $invoiceInfo['headertype'];
            $receipt['headerName'] = $invoiceInfo['headerName'];
            $receipt['taxNo'] = $invoiceInfo['taxNo'];
            $receipt['address'] = $invoiceInfo['address'];
            $receipt['number'] = $invoiceInfo['number'];
            $receipt['depositaryBank'] = $invoiceInfo['depositaryBank'];
            $receipt['account'] = $invoiceInfo['account'];
            $receipt['userId'] = $userId;
            $receipt['shopId'] = $value['shopId'];
            $receipt['realTotalMoney'] = $ordersMoney;
            $receipt['addtime'] = date("Y-m-d H:i:s");
            $receiptInfo[] = $receipt;
            $orderInfo[] = $orders;
        }
        M()->startTrans();
        $res = M('invoice_receipt')->addAll($receiptInfo);
        $receiptId = $res + count($receiptInfo) - 1;
        $receiptsIdWhere['receiptId'] = array('between', "$res,$receiptId");
        $receiptData = M('invoice_receipt')->where($receiptsIdWhere)->select();
        if (empty($receiptData)) {
            return returnData(null, -1, 'error', '数据获取失败');
        }
        foreach ($receiptData as $v) {
            foreach ($orderInfo as $val) {
                if ($v['shopId'] == $val['shopId']) {
                    $orderIdWhere['orderId'] = array('IN', $val['orderId']);
                    $id['receiptId'] = $v['receiptId'];
                    $id['invoiceClient'] = $invoiceClient;
                    $data = $order->where($orderIdWhere)->save($id);
                    if (!$data) {
                        M()->rollback();
                    }
                }
            }
        }
        M()->commit();
        return returnData(null, 0, 'success', '数据添加成功');
    }

    /**
     * @param $userId
     * @return array
     * 获取未开发票的订单
     */
    public function getPermitInvoiceList($userId)
    {
        //是否支付[0:未支付 1:已支付]--用户确认收货--是否退款[0:否 1：是]--订单有效标志[-1：删除 1:有效]--用户删除状态(-1:已删除|1:未删除)--用户ID--发票id--开据发票id
        $where = "`isPay` = 1 AND `orderStatus` = 4 AND `isRefund` = 0 AND `orderFlag` = 1 AND `userDelete` = 1 AND `userId` = $userId AND `invoiceClient` = 0 AND `receiptId` = 0";
        $fieldArr = array(
            'orderId',
            'orderNo',
            'shopId',
            'orderStatus',
            'totalMoney',
            'payType',
            'isSelf',
            'isPay',
            'deliverType',
            'createTime',
            'needPay',
            'defPreSale',
            'PreSalePay',
            'PreSalePayPercen',
            'deliverMoney',
            'realTotalMoney',
            'isAppraises',
            'setDeliveryMoney'
        );
        $order = M('orders');
        $data = $order->where($where)->field($fieldArr)->select();
        $order_goods_mod = M('order_goods');
        for ($i = 0; $i <= count($data) - 1; $i++) {
            $data[$i]['goodsData'] = $order_goods_mod->where("orderId = '{$data[$i]['orderId']}'")->select();
        }
        return empty($data) ? array() : $data;
    }

    /**
     * @param $userId
     * @return array
     * 获取发票历史
     */
    public function getInvoiceHistoryList($userId)
    {
        $where = [];
        $where['userId'] = $userId;
        $data = M('invoice_receipt')->where($where)->select();
//        foreach ($data as $k=>$v){
//            $order = M('orders')->where('receiptId = '.$v['receiptId'])->field('sum(realTotalMoney) as realTotalMoney')->select();
//            $data[$k]['realTotalMoney'] = $order[0]['realTotalMoney'];
//        }
        return empty($data) ? array() : $data;
    }

    /**
     * @param $userId
     * @return array|mixed
     * 获取发票详情
     */
    public function getInvoiceHistoryInfo($userId)
    {
        $receiptId = I('receiptId');
        if (empty($receiptId)) {
            return returnData(null, -1, 'error', '参数有误');
        }
        $where = [];
        $where['userId'] = $userId;
        $where['receiptId'] = $receiptId;
        $data = M('invoice_receipt')->where($where)->find();
        return empty($data) ? array() : $data;
    }

    /**
     * @param $userId
     * @return array
     * 抬头列表
     */
    public function invoiceList($userId)
    {
        $where["userId"] = $userId;
        $Model = M('invoice');
        $data = $Model
            ->where($where)
            ->order("addtime desc")
            ->select();
//        if($data){
//            $apiRet['apiCode']=0;
//            $apiRet['apiInfo']='抬头列表获取成功';
//            $apiRet['apiState']='success';
//            $apiRet['apiData']=$data;
//            $apiRet = returnData($data);
//        }else{
//            $apiRet['apiCode']=-1;
//            $apiRet['apiInfo']='抬头列表获取失败';
//            $apiRet['apiState']='error';
//            $apiRet['apiData']=$data;
//            $apiRet = returnData($data,-1,'error','抬头列表获取失败');
//        }
        return empty($data) ? array() : $data;
    }

    /**
     * 抬头添加
     * @param array $data
     * */
    public function invoiceInsert($data)
    {
        $m = M('invoice');
        $res = $m->add($data);
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '数据添加成功';
//        $apiRet['apiState'] = 'success';
        $apiRet = returnData(null, 0, 'success', '数据添加成功');

        if (!$res) {
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '数据添加失败';
//            $apiRet['apiState'] = 'error';
            $apiRet = returnData(null, -1, 'error', '数据添加失败');

        }
        return $apiRet;
    }

    /**
     * 抬头编辑
     * @param int $invoiceId
     * @param array $data
     * */
    public function invoiceSave($invoiceId, $data)
    {
        $m = M('invoice');
        $where['id'] = $invoiceId;
        $res = $m->where($where)->save($data);
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo']='数据更新失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '数据更新失败');

        if ($res !== false && $invoiceId > 0) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo']='数据更新成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData();
        }
        return $apiRet;
    }

    /**
     * 抬头删除
     * @param int $userId
     * */
    public function invoiceDel($userId)
    {
        $id = (int)I('voince_id', 0);
        $where['id'] = $id;
        $where['userId'] = $userId;
        $res = M('invoice')->where($where)->delete();
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo']='数据删除失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(array(), -1, 'error', '数据删除失败');
        if ($res != false) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo']='数据删除成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData(array());
        }
        return $apiRet;
    }

    /**
     * 获取商品属性
     * @param Array $data
     * */
    public function getGoodsAttr($data)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '商品属性获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '商品属性获取失败');
        $m = M('goods_attributes ga');
        $goodsAttr = $m
            ->join("LEFT JOIN wst_attributes a ON ga.attrId=a.attrId")
            ->where("ga.goodsId='" . $data['goodsId'] . "' AND a.attrFlag=1")
            ->field('ga.*,a.attrName,a.isPriceAttr')
            ->select();
        if ($goodsAttr) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiInfo'] = '商品属性获取成功';
//            $apiRet['apiData'] = $goodsAttr;
            $apiRet = returnData($goodsAttr);

        }
        return $apiRet;
    }

    /**
     * 获取邀请人列表 PS:此邀请人列表是直属下线和二级下线混合在一个列表
     * @param Array $data
     * */
    public function invitation($data)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '邀请人列表失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '邀请人列表失败');
        $m = M('user_invitation i');
        $userId = $data['userId'];
        $firstInvitation = $m
            ->join("LEFT JOIN wst_users u ON i.userId=u.userId")
            ->where("i.userId='" . $userId . "'")
            ->field('i.*,u.userName')
            ->select();
        if ($firstInvitation) {
            $firstInvitationId = [];
            foreach ($firstInvitation as $key => $value) {
                $firstInvitation[$key]['distributionLevel'] = 1;
                $firstInvitationId[] = $value['UserToId'];
            }
            sort($firstInvitationId);
            $firstInvitationId = array_unique($firstInvitationId);
            $firstInvitationIdStr = implode(',', $firstInvitationId);

            $secondInvitation = $m
                ->join("LEFT JOIN wst_users u ON i.userId=u.userId")
                ->where("i.userId IN($firstInvitationIdStr)")
                ->field('i.*,u.userName')
                ->select();
            $list = $firstInvitation;
            if ($secondInvitation) {
                foreach ($secondInvitation as $key => $value) {
                    $secondInvitation[$key]['distributionLevel'] = 2;
                }
                $list = array_merge($firstInvitation, $secondInvitation);
            }
            $addTime = [];
            foreach ($list as $val) {
                $addTime[] = $val['createTime'];
            }
            array_multisort($addTime, SORT_DESC, $list);
        }
        $userTab = M('users');
        foreach ($list as $key => $value) {
            if ($value['distributionLevel'] == 1) {
                $list[$key]['distributionLevelName'] = '直属下线';
            } else {
                $list[$key]['distributionLevelName'] = '二级下线';
            }
            $list[$key]['buyer'] = $userTab->where("userId='" . $val['UserToId'] . "'")->field(['userId', 'loginName', 'userName', 'userPhone', 'userPhoto'])->find();
        }
        if ($list) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiInfo'] = '邀请人列表获取成功';
//            $apiRet['apiData'] = $list;
            $apiRet = returnData($list);
        }
        return $apiRet;
    }

    /**
     * 获取邀请人列表 PS:直属下线
     * @param Array $data
     * */
    public function myInvitationFirst($data)
    {
        $m = M('distribution_relation i');
        $pageSize = $data['pageSize'];
        $page = $data['page'];
        $where = "i.pid='" . $data['userId'] . "' AND distributionLevel=1 and u.userFlag=1";
        $wcount = $m->where($where)->count();
        $userInfo = M('users')->where("userId='" . $data['userId'] . "'")->find();
        $list = (array)$m
            ->join("LEFT JOIN wst_users u ON i.userId=u.userId")
            ->where($where)
            ->field('i.*,u.userPhoto as UserToIdPhoto,u.userName as UserToIdName ')
            ->order('i.id DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        foreach ($list as $key => &$value) {
            $value['userName'] = $userInfo['userName'];
            $value['userPhoto'] = $userInfo['userPhoto'];
        }
        unset($value);
        $return = [];
        $return['list'] = $list;
        $return['totalPage'] = ceil($wcount / $pageSize);//总页数
        $return['currentPage'] = $page;//当前页码
        $return['count'] = (int)$wcount;//总数量
        $return['pageSize'] = $pageSize;//页码条数
        if (is_null($return['list'])) {
            $return['list'] = [];
        }
        return returnData($return);
    }

    /**
     * 获取邀请人列表 PS:二级下线
     * @param Array $data
     * */
    public function myInvitationSecond($data)
    {
        $m = M('distribution_relation i');
        $pageSize = $data['pageSize'];
        $page = $data['page'];
        $where = "i.pid='" . $data['userId'] . "' AND distributionLevel=2 and u.userFlag=1";
        $wcount = $m->where($where)->count();
        $userInfo = M('users')->where("userId='" . $data['userId'] . "'")->find();
        $list = (array)$m
            ->join("LEFT JOIN wst_users u ON i.userId=u.userId")
            ->where($where)
            ->field('i.*,u.userPhoto as UserToIdPhoto,u.userName as UserToIdName ')
            ->order('i.id DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        foreach ($list as $key => &$value) {
            $value['userName'] = $userInfo['userName'];
            $value['userPhoto'] = $userInfo['userPhoto'];
        }
        unset($value);
        $return = [];
        $return['list'] = $list;
        $return['totalPage'] = ceil($wcount / $pageSize);//总页数
        $return['currentPage'] = $page;//当前页码
        $return['count'] = (int)$wcount;//总数量
        $return['pageSize'] = $pageSize;//页码条数
        return returnData($return);
    }

    /**
     * 获取分销列表
     * @param Array $data
     * */
    public function distribution($data)
    {
        $m = M('user_distribution');
        $userTab = M('users');
        $pageSize = $data['pageSize'];
        $page = $data['page'];
        $where = "d.userId='" . $data['userId'] . "' and u.userFlag=1";
        $list = (array)$m
            ->alias('d')
            ->join("left join wst_users u on u.userId=d.UserToId")
            ->where($where)
            ->field('d.*')
            ->order('d.id DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        $orderGoodsTab = M('order_goods');
        $orderTab = M('orders');
        foreach ($list as $key => $val) {
            if ($val['distributionLevel'] == 1) {
                $list[$key]['distributionLevelName'] = '直属下线';
            } else {
                $list[$key]['distributionLevelName'] = '二级下线';
            }
            $list[$key]['describe'] = '用户下单，分销奖励';
            if ($val['state'] == 0) {
                $list[$key]['stateName'] = '待结算';
            } elseif ($val['state'] == 1) {
                $list[$key]['stateName'] = '已结算';
            } else {
                $list[$key]['describe'] = '用户退货，取消分销奖励';
                $list[$key]['stateName'] = '已取消';
            }
            $list[$key]['buyer'] = $userTab->where("userId='" . $val['buyerId'] . "'")->field(['userId', 'loginName', 'userName', 'userPhone', 'userPhoto'])->find();
            $list[$key]['goodsData'] = $orderGoodsTab->where("orderId = '{$val['orderId']}'")->select();
            $orderInfo = $orderTab->where("orderId='" . $val['orderId'] . "'")->find();
            $list[$key]['shopId'] = $orderInfo['shopId'];
        }
        //获取商品描述等
        $goods_mod = M('goods');
        $shops_mod = M('shops');
        for ($i = 0; $i <= count($list) - 1; $i++) {
            $shops_mod_data = $shops_mod->where("shopId = '{$list[$i]['shopId']}'")->find();
            $list[$i]['shopName'] = $shops_mod_data['shopName'];
            $list[$i]['shopImg'] = $shops_mod_data['shopImg'];
            for ($j = 0; $j <= count($list[$i]['goodsData']) - 1; $j++) {
                $goods_mod_data = $goods_mod->field(array('goodsUnit,recommDesc,goodsSpec,goodsDesc,saleCount'))->where("goodsId = '{$list[$i]['goodsData'][$j]['goodsId']}'")->find();
                $list[$i]['goodsData'][$j]['recommDesc'] = $goods_mod_data['recommDesc'];
                $list[$i]['goodsData'][$j]['goodsUnit'] = $goods_mod_data['goodsUnit'];
                $list[$i]['goodsData'][$j]['goodsSpec'] = $goods_mod_data['goodsSpec'];
                $list[$i]['goodsData'][$j]['goodsDesc'] = $goods_mod_data['goodsDesc'];
                $list[$i]['goodsData'][$j]['saleCount'] = $goods_mod_data['saleCount'];
            }
        }
        return returnData($list);
    }

    /**
     * 用户申请提现
     * @param array $params <p>
     * int userId 用户id
     * int dataType 提现类型【1：分销提现|2：用户余额提现】 不传默认为分销提现
     * string money 提现金额
     * int payType 提现方式【1：银行卡|2：微信|3：支付宝】
     * string withdrawalAccount 提款账号
     * string actualName 持卡人姓名|真实姓名
     * int backId 银行卡ID
     * </p>
     * */
    public function distributionWithdraw($params)
    {
        $userId = (int)$params['userId'];
        $dataType = (int)$params['dataType'];
        $money = (float)$params['money'];
        $payType = (int)$params['payType'];
        $withdrawalAccount = (string)$params['withdrawalAccount'];
        $actualName = (string)$params['actualName'];
        $bankId = (int)$params['bankId'];
        $tab = M('distribution_withdraw');
        if (!in_array($dataType, [1, 2])) {
            return returnData(false, -1, 'error', '提现类型不合法');
        }
        if (!in_array($payType, [1, 2, 3])) {
            return returnData(false, -1, 'error', '提现方式不合法');
        }
        if ($money < $GLOBALS['CONFIG']['minWithdrawalMoney']) {
            return returnData(false, -1, 'error', '最小提现金额为' . $GLOBALS['CONFIG']['minWithdrawalMoney'] . '元');
        }
        $insert = [];
        if ($payType == 1) {
            if ($bankId <= 0) {
                return returnData(false, -1, 'error', '银行卡提现请携带银行卡id');
            }
            $where = [];
            $where['bankId'] = $bankId;
            $where['bankFlag'] = 1;
            $bankInfo = M('banks')
                ->where($where)
                ->field('bankId,bankName')
                ->find();
            if (empty($bankInfo)) {
                return returnData(false, -1, 'error', '请携带正确的银行卡id');
            }
            $insert['bankName'] = $bankInfo['bankName'];
        }
        $userTab = M('users');
        $where = [];
        $where['userId'] = $userId;
        $userInfo = $userTab->where($where)->field('balance,distributionMoney')->find();
        if ($money <= 0) {
            return returnData(false, -1, 'error', '请输入正确的提现金额');
        }
        $needMoney = 0;//可提现的金额
        if ($dataType == 1) {
            //分销提现
            $needMoney = $userInfo['distributionMoney'];
        }
        if ($dataType == 2) {
            //余额提现
            $needMoney = $userInfo['balance'];
        }
        if ($needMoney < $money) {
            return returnData(false, -1, 'error', '可提现金额不足');
        }
        //生成订单号
        $orderids = M('orderids');
        $orderSrcNo = $orderids->add(array('rnd' => microtime(true)));
        $orderNo = $orderSrcNo . "" . (fmod($orderSrcNo, 7));//订单号
        M()->startTrans();
        $insert['orderNo'] = $orderNo;
        $insert['userId'] = $userId;
        $insert['money'] = $money;
        $insert['state'] = 1;
        $insert['addTime'] = date('Y-m-d H:i:s', time());
        $insert['updateTime'] = date('Y-m-d H:i:s', time());
        //公共数据 - 提款账号
        $insert['withdrawalAccount'] = $withdrawalAccount;
        $insert['withdrawalMethod'] = $payType;
        $insert['actualName'] = $actualName; //收款人真实姓名
        //提现手续费 - %
        $insert['transferCharge'] = floatval($GLOBALS['CONFIG']['transferCharge']);
        //提现手续费
        $insert['transferChargeMoney'] = floatval($insert['money']) * floatval($GLOBALS['CONFIG']['transferCharge']) / 100;
        //应收打款
        $insert['receivables'] = floatval($money) - $insert['transferChargeMoney'];
        $balanceLog = [];//余额流水记录
        if ($dataType == 1) {
            //分销
            $field = 'distributionMoney';
            $insert['dataFrom'] = 5;
            $nowMoney = $userInfo['distributionMoney'] - $insert['money'];
        } elseif ($dataType == 2) {
            //余额
            $field = 'balance';
            $insert['dataFrom'] = 1;
            $nowMoney = $userInfo['balance'] - $insert['money'];
            $balanceLog['userId'] = $userId;
            $balanceLog['balance'] = $insert['money'];
            $balanceLog['dataSrc'] = 1;
            $balanceLog['orderNo'] = $orderNo;
            $balanceLog['dataRemarks'] = '余额提现';
            $balanceLog['balanceType'] = 2;
            $balanceLog['createTime'] = date('Y-m-d H:i:s', time());
            $balanceLog['shopId'] = 0;
        }
        $nowMoney = $nowMoney < 0 ? 0 : $nowMoney;
        $insertRes = $tab->add($insert);
        if (!$insertRes) {
            M()->rollback();
            return returnData(false, -1, 'error', '提现失败');
        }
        $sql = "update __PREFIX__users set $field={$nowMoney} where userId={$userId}";
        $data = $this->execute($sql);
        if (!$data) {
            M()->rollback();
            return returnData(false, -1, 'error', '提现失败');
        }
        if (!empty($balanceLog)) {
            $userBalanceTab = M('user_balance');
            $logRes = $userBalanceTab->add($balanceLog);
            if (!$logRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '提现失败');
            }
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 申请提现银行
     * @param array $params <p>
     *int userId 用户id
     *int payType 提现方式【1：银行卡|2：微信|3：支付宝】
     *int dataType 提现类型【1：分销提现|2：用户余额提现】
     * </p>
     */
    public function getDwHandleInfo($params)
    {
        //PS:该放法由分销提现改写,分销提现已经对接并正常运行,所以不要纠结方法和字段的命名
        $userId = $params['userId'];
        $payType = $params['payType'];
        $dataType = $params['dataType'];
        $where = [];
        $where['userId'] = $userId;
        $userTab = M('users');
        $userInfo = $userTab->where($where)->field('distributionMoney,balance')->find();
        $data = [];
        $transferCharge = $GLOBALS["CONFIG"]['transferCharge'];//提现手续费
        if (empty($transferCharge)) {
            $transferCharge = 0;
        }
        $data['withdrawalFee'] = $transferCharge . '%';
        $data['distributionMoney'] = 0;//可提现金额
        if ($dataType == 1) {
            //分销提现
            $data['distributionMoney'] = $userInfo['distributionMoney'];
        }
        if ($dataType == 2) {
            //余额提现
            $data['distributionMoney'] = $userInfo['balance'];
        }
        if ($payType == 1) {
            //如果是银行卡提现的话就返回银行卡信息
            $where = [];
            $where['bankFlag'] = 1;
            $banksList = M('banks')
                ->where($where)
                ->field('bankId,bankName')
                ->order('bankId desc')
                ->select();
            if (empty($banksList)) {
                $banksList = [];
            }
            $data['bankList'] = $banksList;
        }
        return returnData($data);
    }

    /**
     * 提现列表
     * @param array $params <p>
     * int userId 用户id
     * int dataFrom 提现来源 【1用户余额提现，2供应商提现，3商户提现，4司机提现，5.分销提现 6扣除合伙人收益，7扣除团长佣金，8扣除红包余额】
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * */
    public function withdrawList(array $params)
    {
        $userId = (int)$params['userId'];
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $dataFrom = (int)$params['dataFrom'];
        $tab = M('distribution_withdraw dw');
        $where = array();
        $where['dw.userId'] = $userId;
        $where['dw.dataFrom'] = $dataFrom;
        $data = $tab
            ->join("LEFT JOIN wst_users u ON dw.userId=u.userId")
            ->where($where)
            ->field("dw.*,u.userName,u.userPhoto,u.userPhone")
            ->order('dw.id DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        return (array)$data;
    }

    /**
     * 依据第三级城市名 获取三级数据
     * @param Array $data
     * */
    public function analysiscityList($str)
    {
        $mod_areas = M('areas');
        //需要考虑 省市 区县 乡镇的剔除搜索
        // str_replace(array('省','市'),'',$order_areas_mod->where("areaId = '{$funData['areaId2']}'")->field('areaName')->find()['areaName']);
        $area3 = $mod_areas->where("areaName = '{$str}'")->find();
        $area2 = $mod_areas->where("areaId = {$area3['parentId']}")->find();
        $area1 = $mod_areas->where("areaId = {$area2['parentId']}")->find();

        $apiRet['apiCode'] = 0;
        $apiRet['apiState'] = 'success';
        $apiRet['apiInfo'] = '获取成功';
        $apiRet['apiData'] = array(
            'a1' => $area1,
            'a2' => $area2,
            'a3' => $area3,
        );
        $apiRet = returnData(array(
            'a1' => $area1,
            'a2' => $area2,
            'a3' => $area3,
        ));
        return $apiRet;
    }

    /**
     * 获取三级分类下面的品牌 PS: 其实是一级分类下的
     * @param $request ['adcode'] PS:一级分类id
     * */
    public function threeTypeBrand($request)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '品牌列表获取失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = false;
        $apiRet = returnData(false, -1, 'error', '品牌列表获取失败');
        if ($request) {
            $brandLlist = M('goods_cat_brands c')
                ->join("LEFT JOIN wst_brands b ON c.brandId=b.brandId")
                ->field('b.*')
                ->where("c.catId='" . $request['adcode'] . "' AND b.brandFlag=1")
                ->select();
            if ($brandLlist) {
//                $apiRet['apiCode'] = 1;
//                $apiRet['apiInfo'] = '品牌列表获取成功';
//                $apiRet['apiState'] = 'success';
//                $apiRet['apiData'] = $brandLlist;
                $apiRet = returnData($brandLlist);
            }
        }
        return $apiRet;
    }

    /**
     * 获取二级分类下面的属性
     * */
    public function twoTypeAttr($request)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '属性获取失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = false;
        $apiRet = returnData(false, -1, 'error', '属性获取失败');
        if ($request) {
            $goodsAttrTab = M('goods_attributes a');
            $firstAttrList = $goodsAttrTab
                ->join("LEFT JOIN wst_goods g ON g.goodsId=a.goodsId")
                ->join("LEFT JOIN wst_attributes wa ON a.attrId=wa.attrId")
                ->where("g.goodsCatId2='" . $request['twoTypeId'] . "' AND g.goodsFlag = 1 AND wa.isMandatory=1")
                ->field('a.attrId,wa.attrName,wa.isCheckbox')
                ->select();
            $firstAttrList = arrayUnset($firstAttrList, 'attrName');
            if ($firstAttrList) {
                foreach ($firstAttrList as $key => $value) {
                    $children = $goodsAttrTab->where("attrId='" . $value['attrId'] . "'")->select();
                    $children = arrayUnset($children, 'attrVal');
                    $firstAttrList[$key]['children'] = $children;
                }
//                $apiRet['apiCode'] = 1;
//                $apiRet['apiInfo'] = '属性列表获取成功';
//                $apiRet['apiState'] = 'success';
//                $apiRet['apiData'] = $firstAttrList;
                $apiRet = returnData($firstAttrList);

            }
        }
        return $apiRet;
    }

    /**
     * 删除订单
     * @param $request ['orderId']
     * @param $request ['userId']
     * */
    public function deleteOrder($request)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '删除订单失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '删除订单失败');
        if ($request) {
            $order = M('orders');
            $data['userDelete'] = '-1';
            $deleteOrder = $order->where("orderId='" . $request['orderId'] . "' AND userId='" . $request['userId'] . "'")->save($data);
            if ($deleteOrder) {
//                $apiRet['apiCode'] = 1;
//                $apiRet['apiInfo'] = '订单删除成功';
//                $apiRet['apiState'] = 'success';
                $apiRet = returnData();

            }
        }
        return $apiRet;
    }

    /**
     * 分销记录(用于邀请)
     * @param array $params <p>
     * int userId 邀请人id
     * varchar userPhone 被邀请人手机号
     * varchar code 验证码
     * int dataType 数据类型【1：分销邀请记录|2：地推邀请记录】
     * </p>
     * */
    public function distributionLog($params)
    {
        $userId = $params['userId'];
        $userPhone = $params['userPhone'];
        $code = $params['code'];
        $dataType = $params['dataType'];
        $verificationSmsCodeRes = $this->verificationSmsCode($userPhone, $code);
        if ($verificationSmsCodeRes['code'] == -1) {
            return $verificationSmsCodeRes;
        }
        $userTab = M('users');
        $where = [];
        $where['userId'] = $userId;
        $where['userFlag'] = 1;
        $userInfo = $userTab->where($where)->find();
        if ($userInfo && $userInfo['userPhone'] == $userPhone) {
            return returnData(false, -1, 'error', '邀请人的手机号不能和被邀请人的手机号一致');
        }
        $where = [];
        $where['userPhone'] = $userPhone;
        $where['userFlag'] = 1;
        $registerUser = $userTab->where($where)->find();
        if ($registerUser) {
            return returnData(false, -1, 'error', '该手机号已经注册过了');
        }
        $invitationTab = M('distribution_invitation');
        $saveData = [];
        $saveData['userId'] = $userId;
        $saveData['userPhone'] = $userPhone;
        $saveData['dataType'] = $dataType;
        $saveData['addTime'] = date('Y-m-d H:i:s', time());
        $inserRes = $invitationTab->add($saveData);//只要该用户未成功注册就可以反复邀请
        if (!$inserRes) {
            return returnData(false, -1, 'error', '邀请失败');
        }
        //销毁验证码
        $where = [];
        $where['smsPhoneNumber'] = $userPhone;
        $where['smsCode'] = $code;
        M('log_sms')->where($where)->save(['dataFlag' => -1]);
        return returnData(true);
    }

    /**
     * 验证手机验证码的有效性
     * @param string $userPhone 手机号
     * @param string $code 验证码
     * @return array $data
     * */
    public function verificationSmsCode($userPhone, $code)
    {
        $smsTab = M('log_sms');
        $where = [];
        $where['smsPhoneNumber'] = $userPhone;
        $where['smsCode'] = $code;
        $where['dataFlag'] = 1;
        $smsInfo = $smsTab
            ->where($where)
            ->find();
        if (empty($smsInfo)) {
            $apiRet = returnData(false, -1, 'error', '验证码不正确');
            return $apiRet;
        }
        if ((time() - strtotime($smsInfo['createTime'])) > 1800) {
            $apiRet = returnData(false, -1, 'error', '验证码已经失效');
            return $apiRet;
        }
        return returnData();
    }

    /**
     * 获取所有非自营商家的商品
     * @param int page
     * */
    public function normalShopGoods($response)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '获取数据失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '获取数据失败');
        $page = $response['page'];
        $pageDataNum = 20;
        $newTime = date('H') . '.' . date('i');//获取当前时间
        //店铺条件
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        $where["wst_shops.isSelf"] = 0;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
//        $where["wst_goods.isFlashSale"] = 0;//是否限时
//        $where["wst_goods.isLimitBuy"] = 0;//是否限量购
        $Model = M('goods');
        $data = $Model
            ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
            ->group('wst_goods.goodsId')
            ->where($where)
            ->order("wst_goods.shopGoodsSort DESC")
            ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.markIcon,wst_goods.isNewPeople')
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        $data = handleNewPeople($data, $response['userId']);//剔除新人专享商品
        $data = rankGoodsPrice($data);
        if ($data) {
//            $apiRet['apiCode'] = 1;
//            $apiRet['apiInfo'] = '获取数据成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $data;
            $apiRet = returnData($data);
        }
        return $apiRet;
    }

    /**
     * 菜谱收藏列表
     * @param $request ['userId']
     * */
    public function menusCollectionList($response)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '获取数据失败';
//        $apiRet['apiState'] = 'error';
        $page = $response['page'];
        $pageDataNum = $response['pageSize'];
        $collection = M('menus_collection c');
        $data = $collection
            ->join('LEFT JOIN wst_menus m ON m.id=c.menuId')
            ->where("c.userId='" . $response['userId'] . "' AND c.state=0 AND m.state=0")
            ->order("m.id DESC")
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->select();
        if (!$data) {
            $data = [];
        }
        $apiRet = returnData($data);
        return $apiRet;
    }

    /**
     * 菜谱收藏添加
     * @param array $data
     * */
    public function menusCollection($data)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo']='菜谱收藏失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '菜谱收藏失败');
        $collection = M('menus_collection');
        $where['userId'] = $data['userId'];
        $where['menuId'] = $data['menuId'];
        $where['state'] = 0;
        $conllectionInfo = $collection->where($where)->find();
        if ($conllectionInfo) {
//            $apiRet['apiInfo'] = "您已经收藏过这个菜单了";
            $apiRet = returnData(null, -1, 'error', '您已经收藏过这个菜单了');
            return $apiRet;
        }
        $res = $collection->add($data);
        if ($res) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo']='菜谱收藏成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData();

        }
        return $apiRet;
    }

    /**
     * 菜谱收藏删除
     * @param array $data
     * */
    public function menusCollectionDelete($data)
    {
        $collection = M('menus_collection');
        $edit['state'] = -1;
        //临时添加
        $existMenuInfo = $collection->where("userId='" . $data['userId'] . "' AND menuId='" . $data['menuId'] . "'")->find();
        if (!$existMenuInfo) {
            $apiRet = returnData(null, -1, 'error', '菜谱收藏删除失败');
            return $apiRet;
        }
        $res = $collection->where("userId='" . $data['userId'] . "' AND menuId='" . $data['menuId'] . "'")->save($edit);
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo']='菜谱收藏删除失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '菜谱收藏删除失败');
        if ($res !== false) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo']='菜谱收藏删除成功';
//            $apiRet['apiState']='success';
            $apiRet = returnData();

        }
        return $apiRet;
    }

    /**
     * 今日推荐 PS(取数据库最新的一条)
     * */
    public function recommendList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $recommendTab = M('menus_recommend');
        $menuTab = M('menus');
        $recommendInfo = $recommendTab->where("state=0")->order('id DESC')->find();
        $menuIds = trim($recommendInfo['menuId'], ',');
        if (!empty($menuIds)) {
            $res = $menuTab->where("id IN($menuIds) AND state=0")->select();
            if ($res != false) {
                $recommendInfo['menus'] = $res;
//                $apiRet['apiCode'] = 0;
//                $apiRet['apiInfo'] = '数据获取成功';
//                $apiRet['apiState'] = 'success';
//                $apiRet['apiData'] = $recommendInfo;
                $apiRet = returnData($recommendInfo);

            }
        }
        return $apiRet;
    }

    /**
     * 今日菜单
     * @param int page
     * @param int catId
     * @param int pageSize
     * */
    public function menusList($catId, $page = 1, $pageSize)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        // $apiRet = returnData(null,-1,'error','数据获取失败');
        $apiRet = returnData([]);
        $pageDataNum = $pageSize;
        $menuTab = M('menus');
        $where = " state=0 and isShow = 1 ";
        if (!empty($catId)) {
            $where .= "AND catId='" . $catId . "'";
        }
        $list = $menuTab
            ->where($where)
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->order("id DESC")
            ->select();
        if ($list) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;
            $apiRet = returnData($list);

        }
        return $apiRet;
    }

    /**
     * 菜谱分类
     * */
    public function menusCatList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $menuCatTab = M('menus_cat');
        $where = " state=0 ";
        $list = $menuCatTab
            ->where($where)
            ->field('id,catname,pic')
            ->order("id DESC")
            ->select();
//        if ($list) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;
//            $apiRet = returnData($list);
//        }
        return returnData((array)$list);
    }

    /**
     * 菜谱搜索
     * @param string $keyword PS:菜谱标题或食材
     * */
    public function searchMenu($keyword, $page = 1, $pageSize)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData([]);
        if (empty($keyword)) {
            return $apiRet;
        }

        $pageDataNum = $pageSize;
        $where = " m.state=0 AND (m.title like '%$keyword%' OR c.catname like '%$keyword%') ";
        $list = M("menus m")
            ->join("LEFT JOIN wst_menus_ingredientcat_relation r ON r.menuId=m.id")
            ->join("LEFT JOIN wst_menus_ingredientcat c ON r.ingredientCatId=c.id")
            ->where($where)
            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
            ->field('m.*')
            ->group('m.id')
            ->order("m.id DESC")
            ->select();
        if ($list) {
            foreach ($list as $key => &$val) {
                $catList = M('menus_ingredientcat_relation r')
                    ->join("LEFT JOIN wst_menus m ON r.menuId=m.id")
                    ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=r.ingredientCatId")
                    ->field('c.catname')
                    ->where("menuId='" . $val['id'] . "'")
                    ->select();
                $catname = '';
                $arr = [];
                foreach ($catList as $v) {
                    $arr[] = $v['catname'];
                }
                if (!empty($arr)) {
                    $catname = implode('、', $arr);
                }
                $val['catname'] = $catname;
            }
            unset($val);
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;

            $apiRet = returnData($list);
            if (empty($list)) {
                $apiRet = returnData([]);
            }
        }
        return $apiRet;
    }

    /**
     * 菜谱详情
     * @param int $menuId
     * */
    public function menuInfo($menuId, $userId)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $menuTab = M('menus');
        $menuStepTab = M('menus_step');
        $goodsTab = M('goods');
        $ingredientTab = M('menus_ingredient');
        $info = $menuTab
            ->where("id='" . $menuId . "'")
            ->find();
        $menuTab->where("id='" . $menuId . "'")->setInc('click', 1); //浏览量
        $catList = M('menus_ingredientcat_relation r')
            ->join("LEFT JOIN wst_menus m ON r.menuId=m.id")
            ->join("LEFT JOIN wst_menus_ingredientcat c ON c.id=r.ingredientCatId")
            ->field('c.catname,c.id')
            ->where("menuId='" . $menuId . "' AND r.state=0")
            ->select();
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
        }

        /*foreach ($catList as $key=>&$val){
            $ingredientInfo = $ingredientTab->where("ingredientCatId='".$val['id']."' AND state=0")->find();
            $goodsIds = trim($ingredientInfo['goodsId'],',');
            if(empty($goodsIds)){
                $goodsIds = '0';
            }
            $goods = $goodsTab->where("goodsId IN($goodsIds)")->select();
            $val['goodsList'] = rankGoodsPrice($goods);
        }*/
        foreach ($catList as $key => &$val) {
            $ingredientInfo = $ingredientTab->where("ingredientCatId='" . $val['id'] . "' AND state=0")->find();
            $goodsIds = trim($ingredientInfo['goodsId'], ',');
            if (empty($goodsIds)) {
                $goodsIds = '0';
            }
            $where = array();
            $where['goodsId'] = array('in', $goodsIds);
            $where['goodsStatus'] = 1;
            $where['isSale'] = 1;
            $where['goodsFlag'] = 1;
            if (!empty($user_info)) {
                $expireTime = $user_info['expireTime'];//会员的过期时间
                if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                    $where['isMembershipExclusive'] = 0;
                }
            }
            $goods = $goodsTab->where($where)->select();
            $goods = empty($goods) ? array() : $goods;
            $goods = handleNewPeople($goods, $userId);
            $goods = rankGoodsPrice($goods);
            $val['goodsList'] = $goods;
        }
        unset($val);
        $stepList = $menuStepTab->where("menuId='" . $menuId . "' AND state=0")->order('sort ASC')->select();
        $info['stepList'] = $stepList;
        $info['catList'] = $catList;
        $menusCollection = M('menus_collection')->where("userId='" . $userId . "' AND menuId='" . $menuId . "' AND state=0")->find();
        if ($menusCollection) {
            $info['isCollection'] = 1;
        } else {
            $info['isCollection'] = 0;
        }
        if ($info) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $info;
            $apiRet = returnData($info);
        }
        return $apiRet;
    }


    /**
     * 菜谱详情
     * @param int $menuId
     * @param int $userId
     * @param string lat
     * @param string lng
     * @param int adcode //店铺社区对应的区县ID
     * @param int shopId //店铺id shopId>0则为前置仓,否则为多商户
     * */
    public function menuInfoNew($menuId, $userId, $lat, $lng, $adcode, $shopId)
    {
        $menuTab = M('menus');
        $menuStepTab = M('menus_step');
        $goodsTab = M('goods');
        $info = $menuTab
            ->where("id='" . $menuId . "'")
            ->find();
        if (!$info) {
            return returnData([]);
        }
        $menuTab->where("id='" . $menuId . "'")->setInc('click', 1); //浏览量
        $stepList = $menuStepTab->where("menuId='" . $menuId . "' AND state=0")->order('sort ASC')->select();
        $stepStr = '';
        if ($stepList) {
            foreach ($stepList as $val) {
                $stepStr .= " " . $val['content'];
            }
        }
        $info['stepList'] = $stepList;
        $info['catList'] = [];
        $menusCollection = M('menus_collection')->where("userId='" . $userId . "' AND menuId='" . $menuId . "' AND state=0")->find();//是否收藏
        //分词
        //获取分词 start
        $menusTree = new MenusTreeModel();
        $keyword = $menusTree->menuTreeHandle($info['title'] . $stepStr);
        //获取分词 end
        if (!empty($keyword)) {
            //获取在地图配送范围内的商家
            if (!empty($shopId)) {
                //后加,前置仓可不传lat,lng,adcode
                if (empty($lat) || empty($lng) || empty($adcode)) {
                    $shopInfo = M('shops')->where(['shopId' => $shopId])->find();
                    $lat = $shopInfo['latitude'];
                    $lng = $shopInfo['longitude'];
                    $adcode = $shopInfo['areaId3'];
                }
            }
            $shopData = $this->getDistrictsMap($adcode, $lat, $lng);
            $type = 1;
            if (empty($shopId)) {
                $type = 2;
            }
            $shopData = handleMenuShop($shopData, $keyword, $type);//后加,做下兼容,过滤掉不符合条件的店铺,前端如果没有传shopId,这边也处理下,避免无数据的尴尬
            if ($type == 1) {
                $shopIdStr = $shopId;
            } else {
                $shopIdArr = [];
                foreach ($shopData as $val) {
                    $shopIdArr[] = $val;
                }
                $shopIdStr = implode(',', $shopIdArr);
            }
            if (empty($shopIdStr)) {
                $shopIdStr = 0;
            }
            $keywordStr = "";
            foreach ($keyword as $val) {
                $keywordStr .= " (goodsStatus=1 and goodsFlag=1 and isSale=1 and goodsName like '%" . $val . "%' and shopId IN($shopIdStr)) " . " or";
            }
            $keywordStr = rtrim($keywordStr, 'or');
            if ($shopData) {
                $gWhere = "goodsStatus=1 and goodsFlag=1 and isSale=1 and shopId IN($shopIdStr) and $keywordStr";
                $goods = $goodsTab->where($gWhere)->select();
                $goods = rankGoodsPrice($goods);
                //后加过滤失效商品 start PS:先这样,后期再封装
                for ($i = 0; $i < count($goods); $i++) {
                    $goodsInfo = $goods[$i];
                    //2020-04-05 又是后加过滤规则 start
                    $checkGoodsRes = [
                        'status' => true,//商品有效状态(true:有效|false:失效)
                        'errorMsg' => '',//失效商品原因
                    ];
                    if ($goodsInfo['goodsFlag'] != 1) {//商品已被删除
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '已被删除';
                    }
                    if ($goodsInfo['isSale'] != 1) {//商品已被下架
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '已被下架';
                    }
                    if ($goodsInfo['isShopPreSale'] == 1) {//预售商品
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '预售商品不能购买';
                    }
                    if ($goodsInfo['skuId'] > 0) {
                        //有sku
                        $skuInfo = M('sku_goods_system')->where(['skuId' => $goodsInfo['skuId']])->find();
                        if ($skuInfo['dataFlag'] == -1) {
                            $checkGoodsRes['status'] = false;
                            $checkGoodsRes['errorMsg'] = 'sku不存在或下架';
                        }
                        if ($skuInfo['skuGoodsStock'] != -1 && $skuInfo['skuGoodsStock'] <= 0) {
                            $checkGoodsRes['status'] = false;
                            $checkGoodsRes['errorMsg'] = 'sku库存不足';
                        }
                    } else {
                        //无sku
                        if ($goodsInfo['goodsStock'] <= 1) {//商品库存不足
                            $checkGoodsRes['status'] = false;
                            $checkGoodsRes['errorMsg'] = '库存不足';
                        }
                    }
                    $checkGoodsFlashSale = checkGoodsFlashSale($goodsInfo['goodsId']); //检查商品限时状况
                    if (isset($checkGoodsFlashSale['apiCode']) && $checkGoodsFlashSale['apiCode'] == '000822') {
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = $checkGoodsFlashSale['apiInfo'];
                    }
                    $checkRes = checkGoodsOrderNum($goodsInfo['goodsId'], $userId);//限制下单次数
                    if (isset($checkRes['apiCode']) && $checkRes['apiCode'] == '000821') {
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = $checkRes['apiInfo'];
                    }
                    if ($goodsInfo['buyNum'] > 0 && $goodsInfo['goodsCnt'] > $goodsInfo['buyNum']) {//单笔购买商品数量限制
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '单笔订单最多购买' . $goodsInfo['buyNum'] . '件';
                    }
                    if ($goodsInfo['buyNumLimit'] != -1 && $goodsInfo['goodsCnt'] > $goodsInfo['buyNumLimit']) {
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '单笔订单最多购买' . $goodsInfo['buyNumLimit'] . '件';
                    }

                    $isBuyNewPeopleGoods = isBuyNewPeopleGoods($goodsInfo['goodsId'], $userId);//新人专享
                    if (!$isBuyNewPeopleGoods) {
                        $checkGoodsRes['status'] = false;
                        $checkGoodsRes['errorMsg'] = '新人专享商品，您不能购买!';
                    }

                    //秒杀商品限量控制
                    if ($goodsInfo['isShopSecKill'] == 1) {
                        if ((int)$goodsInfo['shopSecKillNUM'] < (int)$goodsInfo['goodsCnt']) {
                            $checkGoodsRes['status'] = false;
                            $checkGoodsRes['errorMsg'] = '秒杀库存不足';
                        }
                        //已经秒杀完成的记录
                        $killTab = M('goods_secondskilllimit');
                        $existWhere['userId'] = $userId;
                        $existWhere['goodsId'] = $goodsInfo['goodsId'];
                        $existOrderW['o.orderStatus'] = ['IN', [0, 1, 2, 3, 4]];
                        //$existWhere['endTime'] = $res_goodsId['ShopGoodSecKillEndTime'];
                        $existWhere['state'] = 1;
                        $existKillLog = $killTab->where($existWhere)->group('orderId')->select();
                        $existOrderField = [
                            'o.orderId'
                        ];
                        $existOrderId = [];
                        foreach ($existKillLog as $val) {
                            $existOrderId[] = $val['orderId'];
                        }
                        $existOrderW['o.orderFlag'] = 1;
                        $existOrderW['o.orderId'] = ['IN', $existOrderId];
                        $existOrder = M('orders o')
                            ->join("LEFT JOIN wst_order_goods og ON og.orderId=o.orderId")
                            ->where($existOrderW)
                            ->field($existOrderField)
                            ->count();
                        if ($existOrder >= $goodsInfo['userSecKillNUM']) {
                            $num = $goodsInfo['userSecKillNUM'] - $existOrder; //剩余可购买次数
                            if ($num < 0) {
                                $num = 0;
                            }
                            $checkGoodsRes['status'] = false;
                            $checkGoodsRes['errorMsg'] = '每个用户最多购买' . $goodsInfo['userSecKillNUM'] . '次该商品,还能秒杀' . $num . '次';
                        }
                    }

                    if ($checkGoodsRes['status'] == false) {
                        M('cart')->where(['cartId' => $goodsInfo['cartId']])->save(['isCheck' => 0]);
                        $cartGoodsList[$i]['errorMsg'] = $checkGoodsRes['errorMsg'];
                        $cartGoodsList[$i]['isCheck'] = 0;
                        $overTimeGoods[] = $cartGoodsList[$i];
                        unset($goods[$i]);
                    }
                }
                $goods = array_values($goods);
                //后加过滤失效商品 end
                if ($goods) {
                    //给商品分个类
                    $shopCatId2 = [];
                    foreach ($goods as $val) {
                        $shopCatId2[] = $val['shopCatId2'];
                    }
                    $shopCatId2 = array_unique($shopCatId2);
                    //获取分类
                    $catWhere['isShow'] = 1;
                    $catWhere['catFlag'] = 1;
                    $catWhere['catId'] = ["IN", $shopCatId2];
                    $shopCatId2Arr = M('shops_cats')->where($catWhere)->select();
                    foreach ($shopCatId2Arr as $key => &$val) {
                        $val['catname'] = $val['catName'];//兼容之前的代码
                        $val['goods'] = [];
                        foreach ($goods as $gval) {
                            if ($gval['shopCatId2'] == $val['catId']) {
                                //每个分类给20条数据差不多了
                                if (count($shopCatId2Arr[$key]['goods']) < 21) {
                                    $shopCatId2Arr[$key]['goods'][] = $gval;
                                }
                            }
                        }
                    }
                    unset($val);
                    $info['catList'] = $shopCatId2Arr;
                }
            }
        }
        if ($menusCollection) {
            $info['isCollection'] = 1;
        } else {
            $info['isCollection'] = 0;
        }
        $info = (array)$info;
        return returnData($info);
    }


    /**
     *推荐商品菜单
     * @param array $request
     * */
    public function recommendMenus($request)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData([]);
        $page = $request['page'];
        $pageDataNum = $request['pageSize'];
        $tab = M('goods');
        $where['goodsId'] = $request['goodsId'];
        $goodsInfo = $tab->where($where)->field('goodsId,goodsName')->find();
        $list = [];
        if (!empty($goodsInfo['goodsName'])) {
            //获取分词 start
            $menusTree = new MenusTreeModel();
            $keyword = $menusTree->menuTreeHandle($goodsInfo['goodsName']);
            //获取分词 end
            if (!empty($keyword)) {
                $where = ' 1=1 and m.state = 0 AND ms.state = 0 AND ';
                $stepWhere = "";
                foreach ($keyword as $value) {
                    $where .= " m.title LIKE '%" . $value . "%' OR";
                    $stepWhere .= " ms.content LIKE '%" . $value . "%' OR";
                }
                $where = rtrim($where, 'OR');
                $stepWhere = rtrim($stepWhere, 'OR');
                $where .= "OR " . $stepWhere;
                $menuTab = M('menus m');
                $list = $menuTab
                    ->join("inner join wst_menus_step ms on ms.menuId=m.id")
                    ->where($where)
                    ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                    ->group("m.id")
                    ->order("m.id DESC")
                    ->select();
            }
        }
        if ($list) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;
            $apiRet = returnData($list);
        }
        return $apiRet;
    }

    /**
     *手机验证码登陆
     * */
//    public function account($resquest)
//    {
////        $apiRes['apiCode'] = -1;
////        $apiRes['apiInfo'] = '操作失败';
////        $apiRes['apiState'] = 'error';
//        $apiRes = returnData(null, -1, 'error', '操作失败');
//        $smsTab = M('log_sms');
//        $smsInfo = $smsTab->where("smsPhoneNumber='" . $resquest['userPhone'] . "' and smsCode = '" . $resquest['smsCode'] . "' and dataFlag = 1")->find();
//        //if (!$smsInfo || strtotime($smsInfo['createTime']) + 1800 < time()) {
//        if (!$smsInfo || ((time() - strtotime($smsInfo['createTime'])) > 1800)) {
////            $apiRes['apiInfo'] = '验证码不正确或已超时';
//            $apiRes = returnData(null, -1, 'error', '验证码不正确或已超时');
//
//            return $apiRes;
//        }
//        $userTab = M('users');
//        $userInfo = $userTab->where("userPhone='" . $resquest['userPhone'] . "'")->find();
//        if (!$userInfo) {
//            //新增
//            unset($data);
//            $data['loginName'] = $resquest['userPhone'];
//            $data['loginSecret'] = rand(1000, 9999);
//            $data['userName'] = $resquest['userName'];
//            $data['userPhoto'] = $resquest['userPhoto'];
//            $data['userPhone'] = $resquest['userPhone'];
//            $data['createTime'] = date('Y-m-d H:i:s');
//            $data['userFrom'] = 3;
//            $data['firstOrder'] = 1;
//            $insertUserId = $userTab->add($data);
//
//            //注册成功发送推送信息
//            $push = D('Adminapi/Push');
//            $push->postMessage(4, $insertUserId);
//
//            //判断是否是被邀请
//            $invitationId = !empty($resquest['InvitationID']) ? $resquest['InvitationID'] : 0;
//            //后加,修复邀请无效的bug start
//            if (empty($invitationId)) {
//                $cacheRecordTable = M('invite_cache_record');
//                $recordWhere = [];
//                $recordWhere['inviteePhone'] = $resquest['userPhone'];
//                $recordWhere['icrFlag'] = 1;
//                $recordInfo = $cacheRecordTable->where($recordWhere)->order('id desc')->find();
//                if ($recordInfo) {
//                    $invitationId = $recordInfo['inviterId'];
//                }
//            }
//            //后加 end
//            if (!empty($invitationId)) {
//                self::InvitationFriend($invitationId, $insertUserId);
//            }
//
//            //新人专享大礼
//            $isNewPeopleGift = self::FunNewPeopleGift($insertUserId);
//            self::distributionRelation($data['userPhone'], $insertUserId);//写入用户分销关系表
//            $userInfo = $userTab->where("userId='" . $insertUserId . "'")->find();
//        }
//        //登陆生成token
//        if ($userInfo['userFlag'] == -1) {
////            $apiRet['apiCode'] = -1;
////            $apiRet['apiInfo'] = '用户被禁用，或者不存在';
////            $apiRet['apiState'] = 'error';
//            $apiRet = returnData(null, -1, 'error', '用户被禁用，或者不存在');
//
//            return $apiRet;
//        }
//        if (isset($isNewPeopleGift)) {
//            $userInfo['isNewPeopleGift'] = $isNewPeopleGift;
//        }
//        //记录登录日志
//        $userLoginLog = M("log_user_logins");
//        $data = array();
//        $data["userId"] = $userInfo['userId'];
//        $data["loginTime"] = date('Y-m-d H:i:s');
//        $data["loginIp"] = get_client_ip();
//        $data["loginSrc"] = 3;
//        $userLoginLog->add($data);
//
//        $logdata['lastIP'] = get_client_ip();
//        $logdata['lastTime'] = date('Y-m-d H:i:s');
//        $userTab->where("userId='" . $userInfo['userId'] . "'")->save($logdata);
//        //生成用唯一token
//        $memberToken = md5(uniqid('', true) . $userInfo['userId'] . $userInfo['loginName'] . (string)microtime());
//        if (!userTokenAdd($memberToken, $userInfo)) {
//
////            $apiRes['apiCode'] = -1;
////            $apiRes['apiInfo'] = '登陆失败';
////            $apiRes['apiState'] = 'error';
////            $apiRes['apiData'] = null;
//
//
//            $apiRes = returnData(null, -1, 'error', '登陆失败');
//
//            return $apiRes;
//        }
//
//        //短信验证码使用过后，直接销毁
//        $smsTab->where(array('smsId' => $smsInfo['smsId']))->setField('dataFlag', -1);
//
//        $userInfo['memberToken'] = $memberToken;
////        $apiRes['apiCode'] = '0';
////        $apiRes['apiInfo'] = '登陆成功';
////        $apiRes['apiState'] = 'success';
////        $apiRes['apiData'] = $userInfo;
//        $apiRes = returnData($userInfo);
//        return $apiRes;
//    }

    /**
     * 手机验证登陆
     * @param array $params <p>
     * string userPhone 手机号
     * string smsCode 验证码
     * string userName 用户名
     * string userPhoto 用户头像
     * </p>
     * */
    public function account(array $params)
    {
        $response = LogicResponse::getInstance();
        $log_service_module = new LogServiceModule();
        $sms_where = array(
            'smsPhoneNumber' => $params['userPhone'],
            'smsCode' => $params['smsCode'],
        );
        $sms_info = $log_service_module->getLogSmsInfo($sms_where);
        if ($sms_info['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确')->toArray();
        }
        $sms_info_data = $sms_info['data'];
        if (((time() - strtotime($sms_info_data['createTime'])) > SmsEnum::OUTTIME)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确或已超时')->toArray();
        }
        $users_service_module = new UsersServiceModule();
        $users_result = $users_service_module->getUsersDetailByWhere(array(
            'loginName' => $params['userPhone'],
        ));
        $user_model = new \V3\Model\UserModel();
        if ($users_result['code'] != ExceptionCodeEnum::SUCCESS) {
            //注册用户
            $reg_data = array();
            $reg_data['loginName'] = (string)$params['userPhone'];
            $reg_data['userName'] = (string)$params['userName'];
            $reg_data['userPhoto'] = (string)$params['userPhoto'];
            $reg_data['openId'] = '';
            $reg_data['WxUnionid'] = '';
            $reg_data['userPhone'] = (string)$params['userPhone'];
            $userId = (int)$user_model->reg($reg_data);
            if (empty($userId)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('注册用户失败')->toArray();
            }
            //注册成功后
            $user_model->UserPushInfo($userId, 4);//注册成功推送
            $user_model->UserLogInfo($userId);//记录登陆日志
            $user_model->InvitationFriend($userId);//邀请好友奖励
            $user_model->FunNewPeopleGift($userId);//新人专享大礼
            $user_model->InvitationFriendSetmeal($userId); //邀请好友开通会员送券
            $user_model->distributionRelation($userId);//写入分销与地推关系
        } else {
            $userId = $users_result['data']['userId'];
        }
        $user_model->UserLogInfo($userId);//记录登陆日志
        $data = $user_model->login($userId);
        //销毁短信验证码
        $log_service_module->destructionSms($sms_info_data['smsId']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    static function InvitationFriend($Invitation, $userId)
    {
        //送优惠券
        $mod_users = M('users');
        $mod_user_Invitation = M('user_invitation');
        //更新历史邀请人数 +1
        $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum', 1);//方便其他地方引用为了不影响过多逻辑暂不判断是否成功

        //自动领取优惠券
        $where['dataFlag'] = 1;
        $where['couponType'] = 3;
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();
        $m = new ApiModel();
        for ($i = 0; $i < count($data); $i++) {
            $m->okCoupons($Invitation, $data[$i]['couponId'], 3, -1, $userId);
        }
        //写入邀请关系
        $add_data['userId'] = (int)$Invitation;
        $add_data['source'] = 2;//app邀请好友获得
        $add_data['UserToId'] = (int)$userId;
        $add_data['reward'] = count($data);//优惠券数量
        //获取邀请者给被邀请者的奖励次数
        $inviteRewardNum = (int)$GLOBALS["CONFIG"]['inviteNumReward'];
        $inviteNumRules = $GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
        $add_data['inviteRewardNum'] = $inviteRewardNum;
        //1.优惠券||2.返现||3.积分---------由于之前已经有一次邀请好友就赠送优惠券，所以要将获取的配置次数减一
        if ($inviteNumRules == 1 && $inviteRewardNum > 0) {
            $add_data['inviteRewardNum'] = intval($inviteRewardNum - 1);
        }
        $add_data['createTime'] = date("Y-m-d H:i:s");
        $mod_user_Invitation->add($add_data);


    }


    /*******
     *新人专享大礼
     ******/
    static function FunNewPeopleGift($userId)
    {
        //新人奖励运费券
        $freightCouponsNum = $GLOBALS["CONFIG"]['freightCoupons'];
        if (!empty($freightCouponsNum)) {
            $m = D("V3/Api");
            $freightCouponsNum = (int)$freightCouponsNum;
            $where = [];
            $where['dataFlag'] = 1;
            $where['couponType'] = 8;
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->find();
            if (!empty($data)) {
                for ($i = 0; $i < $freightCouponsNum; $i++) {
                    $m->okCoupons($userId, $data['couponId'], 8);  //运费券8
                }
            }
        }
        //奖励优惠券

        //获取新人优惠券
        $where = [];
        $where['dataFlag'] = 1;
        $where['couponType'] = 2;
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();
        // get_couponsList_auth($data);

        $m = new ApiModel();

        for ($i = 0; $i < count($data); $i++) {
            $m->okCoupons($userId, $data[$i]['couponId'], 2);//新人专享2
        }

        return $data;
        /* 	$mod_users = M('users');
            $mod_user_score = M('user_score');

            //随机奖励积分
            $num = explode("-",$GLOBALS["CONFIG"]['newPeopleGift']);
            $Integral = rand($num[0],$num[1]);
            $mod_users->where("userId='{$userId}'")->setInc('userScore',$Integral);



            //写入用户积分流水
            unset($add_data);
            $add_data['userId'] = $userId;
            $add_data['score'] = $Integral;
            $add_data['dataSrc'] = 10;//小程序新人专享大礼
            $add_data['dataId'] = 0;
            $add_data['dataRemarks'] = "小程序新人专享大礼获得";
            $add_data['scoreType'] = 1;
            $add_data['createTime'] = date("Y-m-d H:i:s");
            $mod_user_score->add($add_data);

            return $Integral; */
    }

    /**
     * 记录用户分销关系表
     * @param varchar $userPhone PS:注册人手机号
     * @param int $add_is_ok_id PS:注册人id
     * */
    static function distributionRelation($userPhone, $add_is_ok_id)
    {
        //写入用户邀请表
        $invitationLogTab = M('distribution_invitation invitation');
        $where = [];
        $where['invitation.userPhone'] = $userPhone;
        $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
        $field .= ',users.balance';
        $invitationInfo = $invitationLogTab
            ->join("left join wst_users users on users.userId=invitation.userId")
            ->field($field)
            ->where($where)
            ->find();
        if (empty($invitationInfo)) {
            return false;
        }
        if ($invitationInfo['dataType'] == 1) {
            //分销
            //写入用户分销关系表 PS:后加
            $distributionRelation = M('distribution_relation');
            //一级邀请人
            $relation['userId'] = $add_is_ok_id;
            $relation['distributionLevel'] = 1;
            $relation['pid'] = $invitationInfo['userId'];
            $relation['addTime'] = date('Y-m-d H:i:s', time());
            $distributionRelation->add($relation);
            //二级
            $preInvitation = $distributionRelation->where("distributionLevel=1 AND userId='" . $invitationInfo['userId'] . "'")->find();
            if ($preInvitation) {
                //如果邀请人有上级邀请人,处理该邀请人的上级邀请人为此注册人的二级邀请人
                unset($relation);
                $relation['userId'] = $add_is_ok_id;
                $relation['distributionLevel'] = 2;
                $relation['pid'] = $preInvitation['pid'];
                $relation['addTime'] = date('Y-m-d H:i:s', time());
                $distributionRelation->add($relation);
            }
        }
        if ($invitationInfo['dataType'] == 2) {
            $usersTab = M('users');
            $where = [];
            $where['userFlag'] = 1;
            $where['userId'] = $invitationInfo['userId'];
            $invitationUser = $usersTab->where($where)->field('pullNewPermissions,pullNewRegister,pullNewOrder')->find();
            //地推
            M()->startTrans();
            $pullNewTab = M('pull_new_log');
            $pullData = [];
            $pullData['userId'] = $add_is_ok_id;
            $pullData['inviterId'] = $invitationInfo['userId'];
            $pullData['createTime'] = date('Y-m-d H:i:s', time());
            $pullNewRes = $pullNewTab->add($pullData);
            if (!$pullNewRes) {
                M()->rollback();
                return false;
            }
            //用户注册成功后,发放给邀请人成功注册相关的奖励
            if ($invitationUser['pullNewPermissions'] == 1) {
                $configs = $GLOBALS['CONFIG'];
                $pullNewRegister = $configs['pullNewRegister'];//奖励规则-用户成功注册
                //如果用户开启了拉新权限,但是没有配置奖励,而平台商城信息却配置了,则采用商品信息中的奖励规则,否则采用用户中的配置规则
                if ($invitationUser['pullNewRegister'] > 0) {
                    $pullNewRegister = $invitationUser['pullNewRegister'];
                }
                if ($pullNewRegister > 0) {
                    $date = date('Y-m-d H:i:s', time());
                    $amountLog = [];
                    $amountLog['userId'] = $add_is_ok_id;
                    $amountLog['inviterId'] = $invitationInfo['userId'];
                    $amountLog['dataType'] = 1;
                    $amountLog['amount'] = $pullNewRegister;
                    $amountLog['status'] = 1;
                    $amountLog['createTime'] = $date;
                    $amountLog['updateTime'] = $date;
                    $insertAmountLogRes = M('pull_new_amount_log')->add($amountLog);
                    if (!$insertAmountLogRes) {
                        M()->rollback();
                        return false;
                    }
                    //拉新奖励记录成功后更新用户余额并记录余额变动日志
                    //余额记录
                    $balanceLog = M('user_balance')->add(array(
                        'userId' => $invitationInfo['userId'],
                        'balance' => $pullNewRegister,
                        'dataSrc' => 1,
                        'orderNo' => '',
                        'dataRemarks' => "拉新奖励-用户成功注册",
                        'balanceType' => 1,
                        'createTime' => $date,
                        'shopId' => 0
                    ));
                    if (!$balanceLog) {
                        M()->rollback();
                        return false;
                    }
                    $userSave = [];
                    $userSave['balance'] = $invitationInfo['balance'] + $pullNewRegister;
                    $updateUser = $usersTab->where(['userId' => $invitationInfo['userId']])->save($userSave);
                    if (!$updateUser) {
                        M()->rollback();
                        return false;
                    }
                }
            }
            M()->commit();
        }
        return true;
    }

    /**
     * 设置默认地址
     * @param array $request
     * */
    static function setDefaultAddress($request)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if (!empty($request['userId']) && !empty($request['addressId'])) {
            $addressTab = M('user_address');
            $address = $addressTab->where("userId='" . $request['userId'] . "' AND addressFlag=1")->select();
            $addressId = [];
            foreach ($address as $val) {
                $addressId[] = $val['addressId'];
            }
            //临时添加
            $addressInfo = $addressTab->where(['addressId' => $request['addressId'], 'userId' => $request['userId']])->find();
            if (!$addressInfo) {
                $apiRet = returnData(null, -1, 'error', '操作失败');
                return $apiRet;
            }
            if (count($addressId) > 0) {
                $addressIdStr = implode(',', $addressId);
                $where = " addressId IN($addressIdStr) ";
                $addressTab->where($where)->save(['isDefault' => 0]);
                $saveRes = $addressTab->where("addressId='" . $request['addressId'] . "'")->save(['isDefault' => 1]);
                if ($saveRes !== false) {
                    $apiRet['apiCode'] = 0;
                    $apiRet['apiInfo'] = '操作成功';
                    $apiRet['apiState'] = 'success';
                }
            }
        }
        $apiRet = returnData(null);
        return $apiRet;
        //return $apiRet;
    }

    /**
     * 申请团长
     * @param array $request
     * */
    static function submitGroup($request)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '申请失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '申请失败');
        $groupTab = M('group');
        if (!empty($request['mobile'])) {
            $groupInfo = $groupTab->where("mobile='" . $request['mobile'] . "'")->find();
            if ($groupInfo) {
//                $apiRet['apiInfo'] = '该手机号已经申请过了';
                $apiRet = returnData(null, -1, 'error', '该手机号已经申请过了');

                return $apiRet;
            }
        }
        $insertGroup = $groupTab->add($request);
        if ($insertGroup) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '申请成功';
//            $apiRet['apiState'] = 'success';
            $apiRet = returnData();
        }
        return $apiRet;
    }

    // =========================================================
    // ========== 类似美团一样的购买会员机制  - start ==========
    // =========================================================

    /**
     * 获得会员套餐列表
     */
    public function getSetmealList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $list = M('set_meal')->where(array('isEnable' => 1, 'smFlag' => 1))->order('smId asc')->select();

        if (!empty($list)) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;
            $apiRet = returnData($list);
        }

        return $apiRet;
    }

    /**
     * @return mixed
     * 获得会员套餐列表[包含会员专享优惠卷]
     */
    public function getSetmealCouponsList()
    {
//        $apiRet = returnData([], -1, 'error', '数据获取失败');
//        $where = " c.shopId = 0 and c.couponType = 5 and  c.dataFlag = 1 and c.type = 1 and sm.isEnable = 1 and sm.smFlag = 1 and c.couponId IN (sm.couponId)";
        $day = date('Y-m-d');
        $where = " shopId = 0 and couponType = 5 and  dataFlag = 1 and type = 1 and validStartTime <= '$day' and validEndTime >= '$day' and sendStartTime <= '$day' and sendEndTime >= '$day'";
        $list = M('set_meal')->where(array('isEnable' => 1, 'smFlag' => 1))->order('smId asc')->select();
//        $sql = "select * from __PREFIX__set_meal sm left join __PREFIX__coupons c on {$where} ";
//        $listInfo = $this->query($sql);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $couponId = trim($v['couponId'], ',');
                $sql = "select * from __PREFIX__coupons where {$where} and couponId IN ({$couponId}) ";
                $coupons = $this->query($sql);
                $list[$k]['couponsInfo'] = $coupons;
            }
        }
        $apiRet = returnData((array)$list);
        return $apiRet;
    }

    /**
     * 会员专享券列表
     * @return mixed
     */
    public function getUserCouponList()
    {
        //商城/通用优惠券
        return M('coupons')->where(array('shopId' => 0, 'couponType' => 5, 'validStartTime' => array('ELT', date('Y-m-d')), 'validEndTime' => array('EGT', date('Y-m-d')), 'dataFlag' => 1, 'type' => 1))->select();
    }

    /**
     * 购买会员套餐
     */
    public function buySetmeal($user, $smId)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '购买失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '购买失败');
        $setmealInfo = M('set_meal')->where(array('smId' => $smId, 'isEnable' => 1, 'smFlag' => 1))->find();
        if (empty($setmealInfo)) {
//            $apiRet['apiInfo'] = '套餐不存在';
            $apiRet = returnData(null, -1, 'error', '套餐不存在');

            return $apiRet;
        }

        /*if ($user['balance'] < $setmealInfo['money']) {
            $apiRet['apiInfo'] = '余额不足';
            return $apiRet;
        }*/

        $curTime = date("Y-m-d H:i:s");
        $result = M('buy_user_record')->add(array(
            'userId' => $user['userId'],
            'smId' => $smId,
            'buyTime' => $curTime,
            'burFlag' => 1
        ));
        if ($result) {

            //扣款并记录流水
            //$this->userPay($user['userId'],$setmealInfo['money'],1,2,'购买会员');

            //购买成功后，发放商城/通用优惠券
            $couponList = M('coupons')->where(array('shopId' => 0, 'couponType' => 5, 'validStartTime' => array('ELT', date('Y-m-d')), 'validEndTime' => array('EGT', date('Y-m-d')), 'dataFlag' => 1, 'type' => 1))->select();
            if (!empty($couponList)) {
                $data = array();
                foreach ($couponList as $v) {
                    $data[] = array(
                        'couponId' => $v['couponId'],
                        'userId' => $user['userId'],
                        'receiveTime' => $curTime,
                        'couponStatus' => 1,
                        'dataFlag' => 1,
                        'orderNo' => '',
                        'ucouponId' => 0,
                        'couponExpireTime' => calculationTime($curTime, $v['expireDays'])
                    );
                }
                M('coupons_users')->addAll($data);
            }

            $user_t = M('users')->where(array('userId' => $user['userId'], 'userFlag' => 1))->find();
            $expireTime = ($user_t['expireTime'] > $curTime) ? date("Y-m-d H:i:s", strtotime("+" . $setmealInfo['dayNum'] . " days", strtotime($user_t['expireTime']))) : date('Y-m-d H:i:s', strtotime("+" . $setmealInfo['dayNum'] . " days"));
            M('users')->where(array('userId' => $user['userId']))->save(array('expireTime' => $expireTime));
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '购买成功';
//            $apiRet['apiState'] = 'success';
            $apiRet = returnData();
        }

        return $apiRet;
    }

    /**
     * 购买会员套餐（微信回调）
     */
    public function notifyBuySetmeal($userId, $smId)
    {
        $user = M('users')->where(array('userId' => $userId))->find();
        if (empty($user)) {
            return false;
        }
        $setmealInfo = M('set_meal')->where(array('smId' => $smId, 'isEnable' => 1, 'smFlag' => 1))->find();
        if (empty($setmealInfo)) {
            $msg = '套餐不存在';
            return false;
        }
        $curTime = date("Y-m-d H:i:s");
        $result = M('buy_user_record')->add(array(
            'userId' => $user['userId'],
            'smId' => $smId,
            'buyTime' => $curTime,
            'burFlag' => 1
        ));
        if ($result) {
            //扣款并记录流水
            //$this->userPay($user['userId'],$setmealInfo['money'],1,2,'购买会员');
            //购买成功后，发放商城/通用优惠券
            $where = array(
                'shopId' => 0,
                'couponId' => array('IN', $setmealInfo['couponId']),
                'couponType' => 5,
                'validStartTime' => array('ELT', date('Y-m-d')),
                'validEndTime' => array('EGT', date('Y-m-d')),
                'dataFlag' => 1,
                'type' => 1
            );
            $couponList = M('coupons')->where($where)->select();
            if (!empty($couponList)) {
                $data = array();
                foreach ($couponList as $v) {
                    $couponExpireTime = calculationTime($curTime, $v['expireDays']);
                    $data[] = array(
                        'couponId' => $v['couponId'],
                        'userId' => $user['userId'],
                        'receiveTime' => $curTime,
                        'couponStatus' => 1,
                        'dataFlag' => 1,
                        'orderNo' => '',
                        'ucouponId' => 0,
                        'couponExpireTime' => $couponExpireTime
                    );
                }
                M('coupons_users')->addAll($data);
            }
            $expireTime = ($user['expireTime'] > $curTime) ? date("Y-m-d H:i:s", strtotime("+" . $setmealInfo['dayNum'] . " days", strtotime($user['expireTime']))) : date('Y-m-d H:i:s', strtotime("+" . $setmealInfo['dayNum'] . " days"));
            M('users')->where(array('userId' => $user['userId']))->save(array('expireTime' => $expireTime));
            //如果用户是第一次购买会员,返给邀请人优惠券
            invitationFriendSetmeal($user['userId']);
            return true;
        }
        return false;
    }

    /**
     * 获取抢购加量包列表
     */
    public function getCouponsetList()
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(array(), -1, 'error', '数据获取失败');
        $list = M('coupon_set')->where(array('csFlag' => 1))->order('csId asc')->select();

//        if (!empty($list)) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $list;
        $apiRet = returnData((array)$list);
//        }

        return $apiRet;
    }

    /**
     * 购买抢购加量包
     */
    public function buyCouponset($user, $csId)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '购买失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '购买失败');
        $userInfo = M('users')->where(array('userId' => $user['userId'], 'userFlag' => 1))->find();
        if (empty($userInfo)) {
//            $apiRet['apiInfo'] = '当前会员不存在';
            $apiRet = returnData(null, -1, 'error', '当前会员不存在');

            return $apiRet;
        }
        //判断会员是否已过期
        if ($userInfo['expireTime'] < date('Y-m-d H:i:s')) {
//            $apiRet['apiInfo'] = '当前会员已过期';
            $apiRet = returnData(null, -1, 'error', '当前会员已过期');

            return $apiRet;
        }

        $couponsetInfo = M('coupon_set as cs')->join("wst_coupons as c on cs.couponId = c.couponId")->where(array('cs.csId' => $csId, 'cs.csFlag' => 1, 'c.couponType' => 5, 'c.dataFlag' => 1, 'c.type' => 1))->field('cs.*')->find();
        if (empty($couponsetInfo)) {
//            $apiRet['apiInfo'] = '加量包或优惠券不存在';
            $apiRet = returnData(null, -1, 'error', '加量包或优惠券不存在');

            return $apiRet;
        }

        //判断余额是否足够
        /*if ($user['balance'] < $couponsetInfo['nprice']) {
            $apiRet['apiInfo'] = '余额不足';
            return $apiRet;
        }*/

        //扣款并记录流水
        //$this->userPay($user['userId'],$couponsetInfo['nprice'],1,2,'购买抢购加量包');

        if ($couponsetInfo['num'] > 0) {
            $data = array();
            for ($i = 0; $i < $couponsetInfo['num']; $i++) {
                $data[] = array(
                    'couponId' => $couponsetInfo['couponId'],
                    'userId' => $user['userId'],
                    'receiveTime' => date('Y-m-d H:i:s'),
                    'couponStatus' => 1,
                    'dataFlag' => 1,
                    'orderNo' => '',
                    'ucouponId' => 0,
                    'couponExpireTime' => calculationTime(date('Y-m-d H:i:s'), $couponsetInfo['expireDays'])
                );
            }
            $result = M('coupons_users')->addAll($data);
            if ($result) {
//                $apiRet['apiCode'] = 0;
//                $apiRet['apiInfo'] = '购买成功';
//                $apiRet['apiState'] = 'success';
//                $apiRet['apiData'] = null;
                $apiRet = returnData(null);
            }
        }

        return $apiRet;
    }

    /**
     * 购买抢购加量包（微信回调）
     */
    public function notifyBuyCouponset($userId, $csId)
    {

        $user = M('users')->where(array('userId' => $userId))->find();
        if (empty($user)) {

            $myfile = fopen("notifyBuySetmeal.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "用户购买会员套餐：用户不存在 \r\n");
            fclose($myfile);

            $msg = '用户不存在';
            return false;
        }

        //判断会员是否已过期
        if ($user['expireTime'] < date('Y-m-d H:i:s')) {

            $myfile = fopen("notifyBuyCouponset.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "用户购买抢购加量包：当前会员已过期 \r\n");
            fclose($myfile);

            $msg = '当前会员已过期';
            return false;
        }

        $couponsetInfo = M('coupon_set as cs')->join("wst_coupons as c on cs.couponId = c.couponId")->where(array('cs.csId' => $csId, 'cs.csFlag' => 1, 'c.couponType' => 5, 'c.dataFlag' => 1, 'c.type' => 1))->field('cs.*,c.expireDays')->find();
        if (empty($couponsetInfo)) {

            $myfile = fopen("notifyBuyCouponset.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "用户购买抢购加量包：加量包或优惠券不存在 \r\n");
            fclose($myfile);

            $msg = '加量包或优惠券不存在';
            return false;
        }

        //扣款并记录流水
        //$this->userPay($user['userId'],$couponsetInfo['nprice'],1,2,'购买抢购加量包');

        if ($couponsetInfo['num'] > 0) {
            $data = array();
            for ($i = 0; $i < $couponsetInfo['num']; $i++) {
                $data[] = array(
                    'couponId' => $couponsetInfo['couponId'],
                    'userId' => $user['userId'],
                    'receiveTime' => date('Y-m-d H:i:s'),
                    'couponStatus' => 1,
                    'dataFlag' => 1,
                    'orderNo' => '',
                    'ucouponId' => 0,
                    'couponExpireTime' => calculationTime(date('Y-m-d H:i:s'), $couponsetInfo['expireDays'])
                );
            }
            $result = M('coupons_users')->addAll($data);

            if (!$result) return false;
            else return true;
        }

        return false;
    }

    /**
     * 获得店铺优惠券
     * @param $shopId
     */
    public function getShopCouponList($shopId)
    {
        $couponList = M('coupons')->where(array('shopId' => $shopId, 'couponType' => 4, 'dataFlag' => 1, 'type' => 2))->select();

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '获取数据成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $couponList;
        $apiRet = returnData($couponList);
        return $apiRet;
    }

    /**
     * 获取门店券信息
     */
    public function getShopCouponInfo($couponId)
    {
        return M('coupons')->where(array('couponId' => $couponId, 'couponType' => 4, 'dataFlag' => 1, 'type' => 2))->find();
    }

    /**
     * 随机获取一张未使用的通用券
     * @param $userId
     */
    public function getCommonCouponInfo($param)
    {
        $where = array('cu.couponStatus' => 1, 'cu.dataFlag' => 1, 'cu.ucouponId' => 0, 'c.couponType' => 5, 'cu.couponExpireTime' => array('GT', date('Y-m-d H:i:s')));
        if (!empty($param['userId'])) $where['cu.userId'] = $param['userId'];
        if (!empty($param['couponMoney'])) $where['c.couponMoney'] = $param['couponMoney'];
        if (!empty($param['id'])) $where['cu.id'] = $param['id'];
        $list = M('coupons_users as cu')->field("cu.*,c.couponName,c.couponMoney,c.spendMoney,c.expireDays")->join("wst_coupons as c on cu.couponId = c.couponId")->where($where)->order('cu.id desc')->find();
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $apiRet = returnData($list);
        return $apiRet;
    }

    /**
     * 兑换门店优惠券
     * @param $shopCouponId   店铺优惠券id
     * @param $id   优惠券用户关联表id
     * @return mixed
     */
    public function exchangeShopCoupon($shopCouponId, $id)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = null;
        $apiRet = returnData(null, -1, 'error', '操作失败');
        $shopCouponInfo = $this->getShopCouponInfo($shopCouponId);
        if (empty($shopCouponInfo)) {
//            $apiRet['apiInfo'] = '店铺优惠券不存在';
            $apiRet = returnData(null, -1, 'error', '店铺优惠券不存在');

            return $apiRet;
        }

        $commonCouponInfo = $this->getCommonCouponInfo(array('id' => $id));
        if (empty($commonCouponInfo)) {
//            $apiRet['apiInfo'] = '商城优惠券不存在';
            $apiRet = returnData(null, -1, 'error', '商城优惠券不存在');

            return $apiRet;
        }

        $result = M('coupons_users')->where(array('id' => $id))->save(array('ucouponId' => $shopCouponId, 'couponExpireTime' => calculationTime($commonCouponInfo['receiveTime'], $shopCouponInfo['expireDays'])));
        if ($result) {
            M('coupon_upgrade')->add(array(
                'coupons_users_id' => $id,
                'createTime' => date('Y-m-d H:i:s')
            ));
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '操作成功';
//            $apiRet['apiState'] = 'success';
            $apiRet = returnData();
        }

        return $apiRet;
    }

    /**
     * 购买会员记录
     * @param $userId
     */
    public function getBuyUserRecord($userId, $page = 1, $pageSize = 10)
    {
        $list = M('buy_user_record as bur')->field("bur.*,sm.name,sm.money,sm.dayNum")->join('wst_set_meal as sm on bur.smId = sm.smId')->where(array('bur.userId' => $userId, 'bur.burFlag' => 1))->order('bur.buyTime desc')->limit(($page - 1) * $pageSize, $pageSize)->select();

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $apiRet = returnData($list);
        return $apiRet;
    }

    /**
     * 优惠券使用记录
     * @param $userId
     * @param int $page
     * @param int $pageSize
     */
    public function getUseCouponRecord($userId, $page = 1, $pageSize = 10)
    {
        $list = M('coupons_users as cu')->field('cu.*,c.couponName,c.couponMoney,c.spendMoney')->join("wst_coupons as c on cu.couponId = c.couponId")->where(array('cu.userId' => $userId, 'cu.couponStatus' => 0, 'cu.dataFlag' => 1))->order('cu.id desc')->limit(($page - 1) * $pageSize, $pageSize)->select();

//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
        $apiRet = returnData($list);

        return $apiRet;
    }

    /**
     * 用户支付金额 (扣款并记录流水)
     */
    public function userPay($userId, $balance, $dataSrc = 1, $balanceType = 2, $dataRemarks = "购买套餐")
    {
        //扣款
        $result = M('users')->where(array('userId' => $userId))->setDec('balance', $balance);
        //记录余额流水
        M('user_balance')->add(array(
            'userId' => $userId,
            'balance' => $balance,
            'dataSrc' => $dataSrc,
            'orderNo' => '',
            'dataRemarks' => $dataRemarks,
            'balanceType' => $balanceType,
            'createTime' => date('Y-m-d H:i:s'),
            'shopId' => 0
        ));
        return $result;
    }

    /**
     * 附近商家的券
     */
    public function getNearShopCouponList($userId, $areaId3, $user_lat, $user_lng, $page = 1, $pageSize = 10)
    {
        $condition = array();
        $condition["cu.couponStatus"] = 1;
        $condition["cu.dataFlag"] = 1;
        $condition["cu.ucouponId"] = 0;
        $condition["cu.userId"] = $userId;
        $condition["c.couponType"] = 5;
        $condition["c.validStartTime"] = array('ELT', date('Y-m-d'));
        $condition["c.validEndTime"] = array('EGT', date('Y-m-d'));
        $condition["c.dataFlag"] = 1;
        $condition["c.type"] = 1;

        $userCouponList = M('coupons_users as cu')->join('wst_coupons as c on cu.couponId = c.couponId')->where($condition)->field('cu.*,c.couponMoney')->select();
        if (empty($userCouponList)) return false;

        $commonCouponMoney_arr = array();
        foreach ($userCouponList as $v) {
            $commonCouponMoney_arr[] = $v['couponMoney'];
        }
        $commonCouponMoney_arr = array_unique($commonCouponMoney_arr);

        $newTime = date('H') . '.' . date('i');//获取当前时间
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;

        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

        $where["wst_coupons.couponType"] = 4;
        $where["wst_coupons.validStartTime"] = array('ELT', date('Y-m-d'));
        $where["wst_coupons.validEndTime"] = array('EGT', date('Y-m-d'));
        $where["wst_coupons.dataFlag"] = 1;
        $where["wst_coupons.type"] = 2;

        //配送区域条件
        $where["wst_shops_communitys.areaId3"] = $areaId3;

        $mod = M("shops")->where($where)
            ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
            ->join("LEFT JOIN wst_users  ON wst_users.userId=wst_shops.userId")
            ->join("wst_coupons ON wst_coupons.shopId=wst_shops.shopId")
            ->field("wst_shops.*,wst_users.userName,wst_coupons.couponId,wst_coupons.couponName,wst_coupons.couponMoney,wst_coupons.spendMoney,wst_coupons.validStartTime,wst_coupons.validEndTime,wst_coupons.commonCouponMoney")
            ->group('wst_shops.shopId')
            ->order('wst_coupons.couponMoney desc')
            ->select();
        if (empty($mod)) return false;

        $shopId_arr = array();
        foreach ($mod as $v) {
            if (in_array($v['commonCouponMoney'], $commonCouponMoney_arr) && checkShopDistribution($v['shopId'], $user_lng, $user_lat)) $shopId_arr[] = $v['shopId'];
        }
        if (empty($shopId_arr)) return false;

        $where_new["wst_shops.shopStatus"] = 1;
        $where_new["wst_shops.shopFlag"] = 1;

        $where_new["wst_shops.shopAtive"] = 1;
        // $where_new["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where_new["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
        $where_new["wst_shops.shopId"] = array('in', array_unique($shopId_arr));

        $where_new["wst_coupons.couponType"] = 4;
        $where_new["wst_coupons.validStartTime"] = array('ELT', date('Y-m-d'));
        $where_new["wst_coupons.validEndTime"] = array('EGT', date('Y-m-d'));
        $where_new["wst_coupons.dataFlag"] = 1;
        $where_new["wst_coupons.type"] = 2;
        $mod = M("shops")->where($where_new)
            ->join("wst_coupons ON wst_coupons.shopId=wst_shops.shopId")
            ->field("wst_shops.*,wst_coupons.couponId,wst_coupons.couponName,wst_coupons.couponMoney,wst_coupons.spendMoney,wst_coupons.validStartTime,wst_coupons.validEndTime,wst_coupons.commonCouponMoney")
            ->group('wst_shops.shopId')
            ->order('wst_coupons.couponMoney desc')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        if (empty($mod)) return false;

        foreach ($mod as $k => $v) {
            foreach ($userCouponList as $ucl) {
                if ($v['commonCouponMoney'] == $ucl['couponMoney']) {
                    $mod[$k]['commonCouponInfo'] = $ucl;
                    break 1;
                }
            }
        }

        return $mod;
    }

    // =========================================================
    // ========== 类似美团一样的购买会员机制  - end ==========
    // =========================================================

    /**
     * 获取充值金额配置列表
     */
    public function getRechargesetList()
    {
        return M('recharge_set')->where(array('rsFlag' => 1))->order('sortorder desc')->select();
    }

    /**
     * 接受邀请 - 动作
     * @param $inviterId
     * @param $phone
     * @return mixed
     */
    public function doAcceptInvite($inviterId, $phone)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = '';
        $apiRet = returnData(null, -1, 'error', '操作失败');

        $userInfo = $this->getUserDetail(array('userId' => $inviterId, 'userFlag' => 1));
        if (empty($userInfo)) {
//            $apiRet['apiInfo'] = '邀请人不存在';
            $apiRet = returnData(null, -1, 'error', '邀请人不存在');

            return $apiRet;
        }

        $userInfo_t = $this->getUserDetail(array('userPhone' => $phone, 'userFlag' => 1));
        if (!empty($userInfo_t)) {
//            $apiRet['apiInfo'] = $phone . ' 已被注册';
            $apiRet = returnData(null, -1, 'error', $phone . '已被注册');

            return $apiRet;
        }

        $icrm = M('invite_cache_record');
        $data = array(
            'inviterId' => $inviterId,
            'inviterPhone' => $userInfo['userPhone'],
            'inviteePhone' => $phone,
            'icrFlag' => 1
        );
        $inviteInfo = $icrm->where($data)->find();
        if (!empty($inviteInfo)) {
//            $apiRet['apiInfo'] = $phone . ' 已被邀请了';
            $apiRet = returnData(null, -1, 'error', $phone . '已被邀请了');

            return $apiRet;
        }

        $data['createTime'] = date('Y-m-d H:i:s');
        $id = $icrm->add($data);

        if ($id > 0) {
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '操作成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = '';
            $apiRet = returnData();

        }

        return $apiRet;
    }

    /**
     * 获取用户详情
     * @param $where
     * @return mixed
     */
    public function getUserDetail($where)
    {
        return M('users')->where($where)->find();
    }

    /**
     * 获取限时
     * */
    public function getFlashSale()
    {
        $shopId = (int)I('shopId', 0);
        $where = [];
        $where['state'] = 1;
        $where['isDelete'] = 0;
        $where['shopId'] = $shopId;
        $list = (array)M('flash_sale')->where($where)->select();
        return returnData($list);
    }

    /**
     * 获取店铺限时商品 - 弃用 可删除
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * */
//    public function getShopFlashSaleGoodsBackup($userId, $shopId, $flashSaleId, $page = 1, $pageDataNum)
//    {
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '数据获取失败';
////        $apiRet['apiState'] = 'error';
//        //$apiRet = returnData(null,-1,'error','数据获取失败');
//
//        if ($userId) {
//            $user_info = M('users')->where(array('userId' => $userId))->find();
//            $expireTime = $user_info['expireTime'];//会员过期时间
//            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
//                $where['wst_goods.isMembershipExclusive'] = 0;
//            }
//        }
//
//        $newTime = date('H') . '.' . date('i');//获取当前时间
//        //店铺条件
//        $where["wst_shops.shopId"] = $shopId;
//        $where["wst_shops.shopStatus"] = 1;
//        $where["wst_shops.shopFlag"] = 1;
//        $where["wst_shops.shopAtive"] = 1;
//        $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
//        $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
//        //商品条件
//        $where["wst_goods.isSale"] = 1;
//        $where["wst_goods.goodsStatus"] = 1;
//        $where["wst_goods.goodsFlag"] = 1;
//        $where['wst_goods.isFlashSale'] = 1;
//        //限时商品条件
//        $where['wst_flash_sale_goods.isDelete'] = 0;
//        if (!empty($flashSaleId)) {
//            $where['wst_flash_sale_goods.flashSaleId'] = $flashSaleId;
//        }
//        //限时条件
//        $where['wst_flash_sale.isDelete'] = 0;//未删除
//        $where['wst_flash_sale.state'] = 1;//显示
//
//        $Model = M('flash_sale_goods');
//        $data = $Model
//            ->join('LEFT JOIN wst_goods ON wst_goods.goodsId = wst_flash_sale_goods.goodsId')
//            ->join('LEFT JOIN wst_flash_sale ON wst_flash_sale.id = wst_flash_sale_goods.flashSaleId')
//            ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
//            ->group('wst_goods.goodsId')
//            ->where($where)
//            ->order("saleCount desc")
//            ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.markIcon,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.goodsCompany,wst_goods.isFlashSale,wst_goods.isLimitBuy,wst_flash_sale.startTime,wst_flash_sale.endTime,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.IntelligentRemark,wst_goods.isNewPeople,wst_goods.minBuyNum')
//            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
//            ->select();
//        if ($data) {
//            $data = handleNewPeople($data, $userId);//剔除新人专享商品
//            $data = rankGoodsPrice($data); //商品等级价格处理
//            return $data;
//        }
//        return [];
//    }

    /**
     * 获取限时商品
     * 多商户-传递adcode
     * 前置仓-shopId
     * */
    public function getShopFlashSaleGoods($shopId, $flashSaleId, $areaId3)
    {
        $page = I('page', 1);
        $pageSize = I('pageSize', 10000);
        //商品条件
        $where["ws.shopStatus"] = 1;
        $where["ws.shopFlag"] = 1;
        $where["ws.shopAtive"] = 1;

        $where["wg.isSale"] = 1;
        $where["wg.goodsStatus"] = 1;
        $where["wg.goodsFlag"] = 1;
        $where["wg.isFlashSale"] = 1;//是否限时
        $where["wg.isLimitBuy"] = 0;//是否限量购

        $where['gts.dataFlag'] = 1;
        //区域条件
        if (!empty($areaId3)) {
            $where["sc.areaId3"] = $areaId3;
        }
        if (!empty($shopId)) {
            $where["gts.shopId"] = $shopId;
        }
        //时间段ID
        $where['gts.flashSaleId'] = $flashSaleId;

        $model = M('goods_time_snapped gts');
        $field = '';
        //商品名称 - 商品图片 - 商品缩略图 - 限时商品ID - 限量商品商户ID [修改]
        $field .= 'wg.goodsName,wg.goodsImg,wg.goodsThums,gts.tsId,gts.shopId,wg.IntelligentRemark,wg.memberPrice,wg.unit,wg.isFlashSale,';
        //商品ID - 市场结构 - 活动价格 - 活动库存 - 起订量 - 销售量
        $field .= 'gts.goodsId,gts.marketPrice,gts.activityPrice as shopPrice,gts.activeInventory as goodsStock,gts.minBuyNum,gts.salesInventory';

        $data = $model
            ->join('wst_shops ws ON gts.shopId = ws.shopId')
            ->join('left join wst_shops_communitys sc ON gts.shopId = sc.shopId')
            ->join('wst_goods wg ON wg.goodsId = gts.goodsId')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->where($where)
            ->order('salesInventory desc')//根据已售库存由高到低进行排序
            ->field($field)
            ->group('gts.tsId,sc.shopId')
            ->select();
        $where = array(
            'id' => $flashSaleId
        );
        $toDay = date("Y-m-d ", time());
        $toDayTime = date("Y-m-d H:i", time());
        $flashSaleInfo = M('flash_sale')->where($where)->find();
        $checkNum = 0;
        if ($flashSaleInfo['endTime'] == '00:00' && $flashSaleInfo['startTime'] == '00:00') {
            $checkNum += 1;
        }
        if ($flashSaleInfo['endTime'] == '00:00') {
            $checkNum += 1;
        }
        $startTime = $toDay . $flashSaleInfo['startTime'];
        $endTime = $toDay . $flashSaleInfo['endTime'];
        if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
            $checkNum += 1;
        }
        if (empty($data)) {
            $data = [];
        }
        foreach ($data as $k => $v) {
            $data[$k]['flashSaleStatus'] = false;//前端需要
            if ($checkNum > 0) {
                $data[$k]['flashSaleStatus'] = true;
            }
            $data[$k]['hasGoodsSku'] = 0;
            $sku['skuSpec'] = [];
            $sku['skuList'] = [];
            $data[$k]['goodsSku'] = $sku;
        }
        $data = rankGoodsPrice($data);
        return returnData($data);
    }

    /**
     * 获取商城限时商品
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * */
    public function getMallFlashSaleGoods($userId, $adcode, $lat, $lng, $flashSaleId, $page, $pageDataNum)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        //$apiRet = returnData(null,-1,'error','数据获取失败');
        $where_t = '';
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
            $expireTime = $user_info['expireTime'];//会员过期时间
            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                $where_t = " and g.isMembershipExclusive = 0 ";
            }
        }

        $newTime = date('H') . '.' . date('i');//获取当前时间
        // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $newTime . "' and s.serviceEndTime >= '" . $newTime . "' and g.isSale = 1 and g.goodsStatus = 1 and g.goodsFlag = 1 and g.isFlashSale = 1 and g.isLimitBuy = 0 and fsg.isDelete = 0 and fsg.flashSaleId = " . $flashSaleId . $where_t;
        $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1  and g.isSale = 1 and g.goodsStatus = 1 and g.goodsFlag = 1 and g.isFlashSale = 1 and g.isLimitBuy = 0 and fsg.isDelete = 0 and fsg.flashSaleId = " . $flashSaleId . $where_t;

        $sql = "select DISTINCT g.goodsId,g.goodsName,g.goodsImg,g.goodsThums,g.shopId,g.marketPrice,g.shopPrice,g.saleCount,g.goodsUnit,g.goodsSpec,s.latitude,s.longitude,g.isShopSecKill,g.ShopGoodSecKillStartTime,g.ShopGoodSecKillEndTime,g.goodsStock,g.markIcon,g.isDistribution,g.firstDistribution,g.SecondaryDistribution,g.goodsCompany,g.isFlashSale,g.isLimitBuy,fs.startTime,fs.endTime,g.isMembershipExclusive,g.memberPrice,g.integralReward,g.IntelligentRemark,g.isNewPeople,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__flash_sale_goods as fsg left join  __PREFIX__goods g on g.goodsId = fsg.goodsId left join __PREFIX__flash_sale fs on fs.id = fsg.flashSaleId left join __PREFIX__shops s on g.shopId = s.shopId " . $where . " group by g.goodsId order by distance asc,g.saleCount desc limit " . ($page - 1) * $pageDataNum . "," . $pageDataNum;
        $data = $this->query($sql);
        if ($data) {
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
            return $data;
        }
        return [];
    }

    /**
     * 获取店铺限时商品数量统计
     * @param int shopId
     * @param int flashSaleId wst_flash_sale表id
     * */
    public function getShopFlashSaleGoodsCount($shopId, $flashSaleId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '数据获取失败';
        $apiRet['apiState'] = 'error';
        $newTime = date('H') . '.' . date('i');//获取当前时间
        //店铺条件
        $where["wst_shops.shopId"] = $shopId;
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
        $where['wst_goods.isFlashSale'] = 1;
        $where['wst_goods.isLimitBuy'] = 0;//是否限量购
        //限时商品条件
        $where['wst_flash_sale_goods.isDelete'] = 0;
        if (!empty($flashSaleId)) {
            $where['wst_flash_sale_goods.flashSaleId'] = $flashSaleId;
        }
        //限时条件
        $where['wst_flash_sale.isDelete'] = 0;//未删除
        $where['wst_flash_sale.state'] = 1;//显示
        $Model = M('flash_sale_goods');
        $data = $Model
            ->join('LEFT JOIN wst_goods ON wst_goods.goodsId = wst_flash_sale_goods.goodsId')
            ->join('LEFT JOIN wst_flash_sale ON wst_flash_sale.id = wst_flash_sale_goods.flashSaleId')
            ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->group('wst_goods.goodsId')
            ->where($where)
            ->count("wst_goods.goodsId");
        if (is_null($data)) {
            $data = 0;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '数据获取成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = ['count' => $data];
        return $apiRet;
    }


    /**
     * 获取商城限时商品
     * */
    public function getFlashSaleGoods_backup($userId, $areaId3, $flashSaleId, $limit)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
            $expireTime = $user_info['expireTime'];//会员过期时间
            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                $where['wst_goods.isMembershipExclusive'] = 0;
            }
        }

        $newTime = date('H') . '.' . date('i');//获取当前时间

        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
        $where["wst_goods.isFlashSale"] = 1;//限时
        $where["wst_goods.isLimitBuy"] = 0;//是否限量购

        //区域条件
        $where["wst_shops_communitys.areaId3"] = $areaId3;
        //wst_flash_sale_goods
        $where["wst_flash_sale_goods.isDelete"] = 0;
        if (!empty($flashSaleId)) {
            $where["wst_flash_sale_goods.flashSaleId"] = $flashSaleId;
        }

        $Model = M('goods');

        $data = $Model->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
            ->join('wst_flash_sale_goods ON wst_flash_sale_goods.goodsId = wst_goods.goodsId')
            ->where($where)
            ->order('saleCount desc')//从高到低
            ->field('wst_goods.goodsId,goodsImg,marketPrice,shopPrice,goodsUnit,goodsSpec,saleCount,goodsName,markIcon,wst_goods.shopId,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople,wst_goods.IntelligentRemark')
//            ->limit($limit)
            ->group('wst_goods.goodsId')
            ->select();

        if ($data) {
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $data;

        }
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    /**
     * 获取商城限时商品-弃用可删除
     * */
    public function getFlashSaleGoodsBackup($userId, $shopId, $flashSaleId, $limit)
    {
        if ($userId) {
            $user_info = M('users')->where(array('userId' => $userId))->find();
            $expireTime = $user_info['expireTime'];//会员过期时间
            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
                $where['wst_goods.isMembershipExclusive'] = 0;
            }
        }

        $newTime = date('H') . '.' . date('i');//获取当前时间

        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
        $where["wst_goods.isFlashSale"] = 1;//限时

        //区域条件
        $where["wst_shops_communitys.areaId3"] = $areaId3;
        //wst_flash_sale_goods
        $where["wst_flash_sale_goods.isDelete"] = 0;
        if (!empty($flashSaleId)) {
            $where["wst_flash_sale_goods.flashSaleId"] = $flashSaleId;
        }

        $Model = M('goods');

        $data = $Model->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
            ->join('wst_flash_sale_goods ON wst_flash_sale_goods.goodsId = wst_goods.goodsId')
            ->where($where)
            ->order('saleCount desc')//从高到低
            ->field('wst_goods.goodsId,goodsImg,marketPrice,shopPrice,goodsUnit,goodsSpec,saleCount,goodsName,markIcon,wst_goods.shopId,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople,wst_goods.IntelligentRemark')
            ->limit($limit)
            ->group('wst_goods.goodsId')
            ->select();
        if ($data) {
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data); //商品等级价格处理
        }
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    /**
     * 获取商城限时商品1
     * */
    public function getFlashSaleGoods($areaId3, $flashSaleId)
    {
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        //商品条件
        $where["ws.shopStatus"] = 1;
        $where["ws.shopFlag"] = 1;
        $where["ws.shopAtive"] = 1;

        $where["wg.isSale"] = 1;
        $where["wg.goodsStatus"] = 1;
        $where["wg.goodsFlag"] = 1;
        $where["wg.isFlashSale"] = 1;//是否限时
        $where["wg.isLimitBuy"] = 0;//是否限量购

        $where['gts.dataFlag'] = 1;
        //区域条件
        $where["sc.areaId3"] = $areaId3;
        $where['gts.flashSaleId'] = $flashSaleId;

        $model = M('goods_time_snapped gts');
        $field = '';
        //商品名称 - 商品图片 - 商品缩略图 - 限时商品ID - 限量商品商户ID [修改]
        $field .= 'wg.goodsName,wg.goodsImg,wg.goodsThums,gts.tsId,gts.shopId,wg.IntelligentRemark,wg.memberPrice,';
        //商品ID - 市场结构 - 活动价格 - 活动库存 - 起订量 - 销售量
        $field .= 'gts.goodsId,gts.marketPrice,gts.activityPrice as shopPrice,gts.activeInventory as goodsStock,gts.minBuyNum,gts.salesInventory';

        $data = $model
            ->join('wst_shops ws ON gts.shopId = ws.shopId')
            ->join('wst_shops_communitys sc ON gts.shopId = sc.shopId')
            ->join('wst_goods wg ON wg.goodsId = gts.goodsId')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->where($where)
            ->order('salesInventory desc')//根据已售库存由高到低进行排序
            ->field($field)
            ->group('gts.tsId')
            ->select();

        $where = array(
            'id' => $flashSaleId
        );
        $toDay = date("Y-m-d ", time());
        $toDayTime = date("Y-m-d H:i", time());
        $flashSaleInfo = M('flash_sale')->where($where)->find();
        $checkNum = 0;
        if ($flashSaleInfo['endTime'] == '00:00' && $flashSaleInfo['startTime'] == '00:00') {
            $checkNum += 1;
        }
        if ($flashSaleInfo['endTime'] == '00:00') {
            $checkNum += 1;
        }
        $startTime = $toDay . $flashSaleInfo['startTime'];
        $endTime = $toDay . $flashSaleInfo['endTime'];
        if (($startTime <= $toDayTime) && ($endTime >= $toDayTime)) {
            $checkNum += 1;
        }

        foreach ($data as $k => $v) {
            $data[$k]['flashSaleStatus'] = false;//前端需要
            if ($checkNum > 0) {
                $data[$k]['flashSaleStatus'] = true;
            }
            $data[$k]['hasGoodsSku'] = 0;
            $sku['skuSpec'] = [];
            $sku['skuList'] = [];
            $data[$k]['goodsSku'] = $sku;
        }
//        $sql = $model->getLastSql();
//        $page = I('page', 1);
//        $pageSize = I('pageSize', 15);
//
//        $data = $this->pageQuery($sql, $page, $pageSize);
//        if(empty($data['root'])){
//            $data['root'] = [];
//        }
        if (empty($data)) {
            $data = [];
        }
        return returnData($data);
    }

    /**
     * 获取店鋪限购商品
     * @param int shopId
     * */
//    public function getShopLimitBuyGoods($userId, $shopId, $page = 1, $pageDataNum, $catId)
//    {
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '数据获取失败';
////        $apiRet['apiState'] = 'error';
//        if ($userId) {
//            $user_info = M('users')->where(array('userId' => $userId))->find();
//            $expireTime = $user_info['expireTime'];//会员过期时间
//            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
//                $where['wst_goods.isMembershipExclusive'] = 0;
//            }
//        }
//
//        $newTime = date('H') . '.' . date('i');//获取当前时间
//        //店铺条件
//        $where["wst_shops.shopId"] = $shopId;
//        $where["wst_shops.shopStatus"] = 1;
//        $where["wst_shops.shopFlag"] = 1;
//        $where["wst_shops.shopAtive"] = 1;
//        $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
//        $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
//        //商品条件
//        $where["wst_goods.isSale"] = 1;
//        $where["wst_goods.goodsStatus"] = 1;
//        $where["wst_goods.goodsFlag"] = 1;
//        $where['wst_goods.isLimitBuy'] = 1;
//        if ($catId > 0) {
//            $where['wst_goods.shopCatId1'] = $catId;
//        }
//        $Model = M('goods');
//        $data = $Model
//            ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
//            ->group('wst_goods.goodsId')
//            ->where($where)
//            ->order("saleCount desc")
//            ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.goodsStock,wst_goods.markIcon,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.goodsCompany,wst_goods.isFlashSale,wst_goods.isLimitBuy,wst_goods.IntelligentRemark,wst_goods.memberPrice,wst_goods.isNewPeople')
//            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
//            ->select();
//        if (empty($data)) {
//            return returnData([]);
//        }
//        foreach ($data as $key => &$val) {
//            if ($val['isFlashSale'] == 1) {
//                unset($info);
//                $info = M('flash_sale_goods')
//                    ->join("LEFT JOIN wst_flash_sale ON wst_flash_sale_goods.flashSaleId=wst_flash_sale.id")
//                    ->where("wst_flash_sale_goods.isDelete=0 AND wst_flash_sale_goods.goodsId='" . $val['goodsId'] . "'")
//                    ->find();
//                $val['startTime'] = $info['startTime'];
//                $val['endTime'] = $info['endTime'];
//            } else {
//                $val['startTime'] = '';
//                $val['endTime'] = '';
//            }
//        }
//        unset($val);
//        $data = handleNewPeople($data, $userId);//剔除新人专享商品
//        $data = rankGoodsPrice($data); //商品等级价格处理
//        return returnData($data);
//    }
    /**
     * @return mixed
     * 获取限量购商品列表
     * 前置仓/多商户
     * shopID/adcode
     * 商户id/区县id
     */
    public function getShopLimitBuyGoods($shopId, $areaId3)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        //商品id-商品名称-市场价-限量活动价-最小起订量-销售量-限量库存-总库存
        $field = "wg.goodsId,wg.goodsName,wg.goodsImg,wg.goodsThums,wg.marketPrice,wg.limitCountActivityPrice as shopPrice,wg.minBuyNum,wg.saleCount,wg.limitCount as goodsStock,wg.shopId,wg.memberPrice,wg.unit,wg.isLimitBuy";
        $where = "ws.shopStatus = 1 and ws.shopFlag = 1 and ws.shopAtive = 1 ";
        //商品条件 是否限量-是否上架(0:不上架 1:上架)-商品状态(-1:禁售 0:未审核 1:已审核)-删除标志(-1:删除 1:有效)
        $where .= "and wg.isLimitBuy = 1 and wg.isFlashSale = 0 and wg.isSale = 1 and wg.goodsStatus = 1 and wg.goodsFlag = 1 ";
        if (!empty($shopId)) {
            $where .= "and wg.shopId = " . $shopId;
        }
        if (!empty($areaId3)) {
            $where .= "and wsc.areaId3 = '$areaId3'";
        }
        $sql = "select {$field} from wst_shops ws inner JOIN wst_goods wg ON wg.shopId = ws.shopId ";
        if (!empty($areaId3)) {
            $sql .= " inner JOIN wst_shops_communitys wsc ON wsc.shopId = wg.shopId ";
        }
        $sql .= " where " . $where;
//        if(!empty($areaId3)){
//            $sql .= " group by wsc.shopId";
//        }
        $sql .= ' order by wg.goodsId desc ';
        $data = $this->pageQuery($sql, $page, $pageSize);
        foreach ($data['root'] as $k => $v) {
            //前端需要
            $data['root'][$k]['flashSaleStatus'] = (bool)true;
            $data['root'][$k]['hasGoodsSku'] = 0;
            $sku['skuSpec'] = [];
            $sku['skuList'] = [];
            $data['root'][$k]['goodsSku'] = $sku;
        }
        return returnData($data['root']);
    }

    /**
     * 获取店鋪限购商品分类
     * @param int shopId
     * */
    public function getShopLimitBuyGoodsCat($shopId)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $newTime = date('H') . '.' . date('i');//获取当前时间
        //店铺条件
        $where["wst_shops.shopId"] = $shopId;
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
        $where['wst_goods.isLimitBuy'] = 1;
        $where['wst_goods.isFlashSale'] = 0;
        $Model = M('goods');
        $data = $Model
            ->join('LEFT JOIN wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->group('wst_goods.goodsId')
            ->where($where)
            ->order("saleCount desc")
            ->field('wst_goods.shopCatId1')
            ->select();
        if ($data) {
            foreach ($data as $val) {
                $catId[] = $val['shopCatId1'];
            }
            $catWhere['catId'] = ["IN", $catId];
            $catWhere['isShow'] = 1;
            $catWhere['catFlag'] = 1;
            $catList = M('shops_cats')->where($catWhere)->select();
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $catList;
            $apiRet = returnData($catList);
        }
        return $apiRet;
    }

    /**
     * 获取商城限购商品
     * @param int shopId
     * */
//    public function getLimitBuyGoods($userId, $areaId3, $page, $pageDataNum, $catId)
//    {
////        $apiRet['apiCode'] = -1;
////        $apiRet['apiInfo'] = '数据获取失败';
////        $apiRet['apiState'] = 'error';
//        $apiRet = returnData(null, -1, 'error', '数据获取失败');
//
//        if ($userId) {
//            $user_info = M('users')->where(array('userId' => $userId))->find();
//            $expireTime = $user_info['expireTime'];//会员过期时间
//            if (empty($expireTime) || $expireTime < date('Y-m-d H:i:s')) {
//                $where['wst_goods.isMembershipExclusive'] = 0;
//            }
//        }
//
//        $newTime = date('H') . '.' . date('i');//获取当前时间
//        $where["wst_shops.shopStatus"] = 1;
//        $where["wst_shops.shopFlag"] = 1;
//        $where["wst_shops.shopAtive"] = 1;
//        $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
//        $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;
//
//        //商品条件
//        $where["wst_goods.isSale"] = 1;
//        $where["wst_goods.goodsStatus"] = 1;
//        $where["wst_goods.goodsFlag"] = 1;
//        $where["wst_goods.isLimitBuy"] = 1;
//        if ($catId > 0) {
//            $where['wst_goods.goodsCatId1'] = $catId;
//        }
//
//        //区域条件
//        $where["wst_shops_communitys.areaId3"] = $areaId3;
//
//        $Model = M('goods');
//
//        $data = $Model->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
//            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
//            ->where($where)
//            ->order('saleCount desc')//从高到低
//            ->field('wst_goods.goodsId,goodsImg,marketPrice,shopPrice,goodsUnit,goodsSpec,saleCount,goodsName,markIcon,wst_goods.shopId,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isNewPeople')
//            ->limit(($page - 1) * $pageDataNum, $pageDataNum)
//            ->group('wst_goods.goodsId')
//            ->select();
//        if ($data) {
//            $data = handleNewPeople($data, $userId);//剔除新人专享商品
//            $data = rankGoodsPrice($data); //商品等级价格处理
////            $apiRet['apiCode'] = 0;
////            $apiRet['apiInfo'] = '数据获取成功';
////            $apiRet['apiState'] = 'success';
////            $apiRet['apiData'] = $data;
//            $apiRet = returnData($data);
//        }
//        return $apiRet;
//    }
    /**
     * @param $areaId3
     * @return mixed
     * 多商户获取限量购商品列表
     */
    public function getLimitBuyGoods($areaId3)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        //商品id-商品名称-市场价-限量活动价-最小起订量-销售量-限量库存-总库存
        $field = "wg.goodsId,wg.goodsName,wg.goodsImg,wg.goodsThums,wg.marketPrice,wg.limitCountActivityPrice as shopPrice,wg.minBuyNum,wg.saleCount,wg.limitCount as goodsStock,wg.memberPrice";
        //店铺条件 店铺状态(-2:已停止 -1:拒绝 0：未审核 1:已审核)-删除标志(-1:删除 1:有效)-店铺营业状态(1:营业中 0：休息中)
        $where = "ws.shopStatus = 1 and ws.shopFlag = 1 and ws.shopAtive = 1";
        //商品条件 是否限量-是否上架(0:不上架 1:上架)-商品状态(-1:禁售 0:未审核 1:已审核)-删除标志(-1:删除 1:有效)
        $where .= "and wg.isLimitBuy = 1 and wg.isSale = 1 and wg.goodsStatus = 1 and wg.goodsFlag = 1 and wg.isFlashSale = 0";
        //区域条件 310115
        $where .= "and wsc.areaId3 = '$areaId3'";
        $sql = "select {$field} from  wst_goods wg inner JOIN wst_shops ws ON ws.shopId = wg.shopId inner JOIN wst_shops_communitys wsc ON wsc.shopId = wg.shopId where " . $where;
        $sql .= "group by wg.goodsId";
        $data = $this->pageQuery($sql, $page, $pageSize);
        foreach ($data['root'] as $k => $v) {
            //前端需要
            $data['root'][$k]['flashSaleStatus'] = (bool)true;
            $data['root'][$k]['hasGoodsSku'] = 0;
            $sku['skuSpec'] = [];
            $sku['skuList'] = [];
            $data['root'][$k]['goodsSku'] = $sku;
        }
        return returnData($data['root']);
    }


    /**
     * 获取商城限购商品
     * @param int shopId
     * */
    public function getLimitBuyGoodsCat($areaId3)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '数据获取失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '数据获取失败');
        $newTime = date('H') . '.' . date('i');//获取当前时间
        $where["wst_shops.shopStatus"] = 1;
        $where["wst_shops.shopFlag"] = 1;
        $where["wst_shops.shopAtive"] = 1;
        // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
        // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

        //商品条件
        $where["wst_goods.isSale"] = 1;
        $where["wst_goods.goodsStatus"] = 1;
        $where["wst_goods.goodsFlag"] = 1;
        $where["wst_goods.isLimitBuy"] = 1;
        $where["wst_goods.isFlashSale"] = 0;//是否限时

        //区域条件
        $where["wst_shops_communitys.areaId3"] = $areaId3;

        $Model = M('goods');

        $data = $Model->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
            ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
            ->where($where)
            ->order('saleCount desc')//从高到低
            ->field('wst_goods.goodsCatId1')
            ->group('wst_goods.goodsId')
            ->select();
        if ($data) {
            //获取分类
            foreach ($data as $val) {
                $catId[] = $val['goodsCatId1'];
            }
            $catWhere['catId'] = ["IN", $catId];
            $catWhere['isShow'] = 1;
            $catWhere['catFlag'] = 1;
            $catList = M("goods_cats")->where($catWhere)->select();
//            $apiRet['apiCode'] = 0;
//            $apiRet['apiInfo'] = '数据获取成功';
//            $apiRet['apiState'] = 'success';
//            $apiRet['apiData'] = $catList;
            $apiRet = returnData($catList);
        }
        return $apiRet;
    }

    /**
     * 订单状态下的数量
     * */
    public function getOrderStateNum($userId)
    {
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
        $apiRet = returnData();
        $orderTab = M('orders');
        $data['all'] = 0; //全部
        $data['noPay'] = 0; //待付款
        $data['noSend'] = 0; //待发货
        $data['noReceive'] = 0; //待收货
        $data['noAppraises'] = 0; //待评价
        $data['refund'] = 0; //售后/退款
        $where['wst_orders.userId'] = $userId;
        $where['wst_orders.orderFlag'] = 1;
        $where['wst_orders.userDelete'] = 1;
        $all = $orderTab->where($where)->count('orderId');
        if (!empty($all)) {
            $data['all'] = (int)$all;
        }
//        $noPayWhere['payType'] = 1;
        $noPayWhere['isPay'] = 0;
        $noPayWhere['userId'] = $userId;
        $noPayWhere['orderFlag'] = 1;
        $noPayWhere['userDelete'] = 1;
        $noPayWhere['orderStatus'] = -2;
        $noPay = $orderTab->where($noPayWhere)->count('orderId');
        if (!empty($noPay)) {
            $data['noPay'] = (int)$noPay;
        }

        $noSendWhere['userId'] = $userId;
        $noSendWhere['orderFlag'] = 1;
        $noSendWhere['userDelete'] = 1;
        $noSendWhere['orderStatus'] = array('in', '0,1,2');
        $noSend = $orderTab->where($noSendWhere)->count('orderId');
        if (!empty($noSend)) {
            $data['noSend'] = (int)$noSend;
        }

        $noReceiveWhere['userId'] = $userId;
        $noReceiveWhere['orderFlag'] = 1;
        $noReceiveWhere['userDelete'] = 1;
        $noReceiveWhere['orderStatus'] = array('in', '3,7,8,9,10,11,16,17');
        $noReceive = $orderTab->where($noReceiveWhere)->count('orderId');
        if (!empty($noReceive)) {
            $data['noReceive'] = (int)$noReceive;
        }

        $noAppraisesWhere['userId'] = $userId;
        $noAppraisesWhere['isAppraises'] = 0;
        $noAppraisesWhere['orderStatus'] = 4;
        $noAppraisesWhere['orderFlag'] = 1;
        $noAppraisesWhere['userDelete'] = 1;
        //$where['receiveTime'] = array('exp', " and DATE_ADD(receiveTime,INTERVAL {$GLOBALS['CONFIG']['autoAppraiseDays']} DAY) < NOW()");
        $noAppraises = $orderTab->where($noAppraisesWhere)->count('orderId');//获取未期限内评价的所有订单
        if (!empty($noAppraises)) {
            $data['noAppraises'] = (int)$noAppraises;
        }


        //售后/退款的订单数量
        $refundWhere['userId'] = $userId;
        $refundWhere['orderFlag'] = 1;
        $refundWhere['userDelete'] = 1;
        $refundWhere['isRefund'] = 1;
        $refundWhere['receiveTime'] = array('exp', " and DATE_ADD(receiveTime,INTERVAL {$GLOBALS['CONFIG']['AfterSaleTime']} DAY) > NOW()");
        $refund = $orderTab->where($refundWhere)->count('orderId');
        if (!empty($refund)) {
            $data['refund'] = (int)$refund;
        }
        $apiRet['apiData'] = $data;
        return $apiRet;
    }

    /**
     * 根据 社区id 来获取附近商家
     * @param $param
     * @return mixed
     */
    public function nearShopByCommunityId($param)
    {
        $data = S("nearShopByCommunityId_" . $param['communityId'] . '_' . $param['page'] . '_' . $param['pageSize']);
        if (empty($data)) {

            $newTime = date('H') . '.' . date('i');//获取当前时间
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;

            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //配送区域条件
            $where["wst_shops_communitys.communityId"] = $param['communityId'];

            $mod = M("shops")->where($where)
                ->join('wst_shops_communitys ON wst_shops.shopId = wst_shops_communitys.shopId')
                ->join("LEFT JOIN wst_users  ON wst_users.userId=wst_shops.userId")
                ->field("wst_shops.*,wst_users.userName")
                ->group('wst_shops.shopId')
                ->limit(($param['page'] - 1) * $param['pageSize'], $param['pageSize'])
                ->select();
            S("nearShopByCommunityId_" . $param['communityId'] . '_' . $param['page'] . '_' . $param['pageSize'], $mod, C("app_shops_cache_time"));
            return $mod;
        }
        return $data;

    }

    /**
     * 会员专享商品列表
     * @param $where
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getMembershipExclusiveGoodsList($where, $page = 1, $pageSize = 10, $shopId, $adcode, $lng, $lat)
    {
        $goods_list = array();
        if (!empty($shopId)) {
            $where['shopId'] = $shopId;
            $goods_list = M('goods')->where($where)->order('createTime desc')->limit(($page - 1) * $pageSize, $pageSize)->select();
        } else if (!empty($adcode) && !empty($lat) && !empty($lng)) {
            $newTime = date('H') . '.' . date('i');//获取当前时间
            // $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and s.serviceStartTime <= '" . $newTime . "' and s.serviceEndTime >= '" . $newTime . "' and g.isMembershipExclusive = 1 and g.isSale = 1 and g.goodsFlag = 1 ";
            $where = " where s.shopStatus=1 and s.shopFlag=1 and s.shopAtive=1 and g.isMembershipExclusive = 1 and g.isSale = 1 and g.goodsFlag = 1 ";
            $shopModule = new ShopsModule();
            $canUseShopList = $shopModule->getCanUseShopByLatLng($lat, $lng);
            if (empty($canUseShopList)) {
                return array();
            }
            $shopIdArrStr = implode(",", array_column($canUseShopList, 'shopId'));
            $where .= " and s.shopId IN($shopIdArrStr) ";
            $sql = "select DISTINCT g.*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-s.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(s.latitude*PI()/180)*POW(SIN(($lng*PI()/180-s.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance from __PREFIX__shops s left join wst_shops_communitys sc on s.shopId=sc.shopId left join __PREFIX__goods g on g.shopId=s.shopId " . $where . " order by distance limit " . ($page - 1) * $pageSize . "," . $pageSize;
            $goods_list = $this->query($sql);
        }
        return $goods_list;
    }

    /**
     * (分销)已接受邀请记录
     * @param int $userId 用户id
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     */
    public function getDistributionInvitationAccepted($userId, $page, $pageSize)
    {
        $invitationTab = M('distribution_invitation');
        $where = [];
        $where['userId'] = $userId;
        $userPhoneArr = $invitationTab->where($where)->distinct(true)->where($where)->getField('userPhone', true);
        if (empty($userPhoneArr)) {
            return returnData();
        }
        $where = [];
        $where['userPhone'] = ['IN', $userPhoneArr];
        $phoneArr = M('users')->distinct(true)->where($where)->getField('userPhone', true);
        if (empty($phoneArr)) {
            return returnData();
        }
        $where = [];
        $where['di.userId'] = $userId;
        $where['di.userPhone'] = ['IN', $phoneArr];
        $list = M('distribution_invitation as di')
            ->field("di.*,u.userId as inviteeId,u.userName as inviteeName,u.loginName as inviteeLoginName")
            ->where($where)
            ->join("left join wst_users as u on di.userPhone = u.userPhone")
            ->order('di.addTime desc')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        $apiRet = returnData((array)$list);
        return $apiRet;
    }

    //店铺新人专享商品列表
    public function ShopNewPeopleAllList($adcode, $lat, $lng, $societyId, $page = 1, $pageSize = 10)
    {
        $data = S("NIAO_CACHE_app_ShopNewPeopleAllList_{$adcode}_{$page}");
        if (empty($data)) {
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            //筛选符合社区条件的店铺 start
            $shopIdArr = getSocietyShop($lat, $lng, $societyId)['shopIdArr'];
            $where["wst_shops.shopId"] = ['IN', $shopIdArr];
            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;  预售 不受店铺营业时间影响
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
            $where["wst_goods.isFlashSale"] = 0;//是否限时
            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.isNewPeople'] = 1;//店铺商品必须为新人专享

            //配送区域条件
//            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("wst_goods.createTime desc")
                ->field('wst_goods.goodsId,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopPreSale,wst_goods.ShopGoodPreSaleStartTime,wst_goods.ShopGoodPreSaleEndTime,wst_goods.goodsStock,wst_goods.isDistribution,wst_goods.firstDistribution,wst_goods.SecondaryDistribution,wst_goods.isMembershipExclusive,wst_goods.memberPrice,wst_goods.integralReward,wst_goods.markIcon,wst_goods.isFlashSale,wst_goods.saleTime')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();
            $data = rankGoodsPrice($data); //商品等级价格处理
            S("NIAO_CACHE_app_ShopNewPeopleAllList_{$adcode}_{$page}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商城新人专享商品列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    /**
     * 获取新品商品列表
     * */
    public function getAttrGoodsList($adcode, $lat, $lng, $page = 1, $pageSize, $userId = 0)
    {

        $data = S("NIAO_CACHE_app_recFQ_{$adcode}_{$page}");
        if (empty($data)) {
            $pageDataNum = $pageSize;//每页10条数据
            $newTime = date('H') . '.' . date('i');//获取当前时间

            //店铺条件
            $where["wst_shops.shopStatus"] = 1;
            $where["wst_shops.shopFlag"] = 1;
            $where["wst_shops.shopAtive"] = 1;
            // $where["wst_shops.serviceStartTime"] = array('ELT', (float)$newTime);//店铺营业时间;
            // $where["wst_shops.serviceEndTime"] = array('EGT', (float)$newTime);//店铺休息时间;

            //商品条件
            $where["wst_goods.isSale"] = 1;
            $where["wst_goods.goodsStatus"] = 1;
            $where["wst_goods.goodsFlag"] = 1;
//            $where["wst_goods.isFlashSale"] = 0;//是否限时
//            $where["wst_goods.isLimitBuy"] = 0;//是否限量购

            $where['wst_goods.isNew'] = 1;//新品

            //配送区域条件
            $where["wst_shops_communitys.areaId3"] = $adcode;

            $Model = M('goods');
            //field 下可以使用as 我这边没必要用 例如 wst_goods.shopId as shopIds
            $data = $Model
                ->join('wst_shops ON wst_goods.shopId = wst_shops.shopId')
                ->join('wst_shops_communitys ON wst_goods.shopId = wst_shops_communitys.shopId')
                ->group('wst_goods.goodsId')
                ->where($where)
                ->order("saleCount desc")
                // ->field('wst_goods.goodsId,wst_goods.goodsStock,wst_goods.goodsName,wst_goods.goodsImg,wst_goods.goodsThums,wst_goods.shopId,wst_goods.marketPrice,wst_goods.shopPrice,wst_goods.saleCount,wst_goods.goodsUnit,wst_goods.goodsSpec,wst_shops.latitude,wst_shops.longitude,wst_goods.isShopSecKill,wst_goods.ShopGoodSecKillStartTime,wst_goods.ShopGoodSecKillEndTime,wst_goods.isNewPeople')
                ->limit(($page - 1) * $pageDataNum, $pageDataNum)
                ->select();
            $data = handleNewPeople($data, $userId);//剔除新人专享商品
            $data = rankGoodsPrice($data);
            S("NIAO_CACHE_app_recFQ_{$adcode}_{$page}", $data, C("allApiCacheTime"));
        }

//        $apiRet['apiCode']=0;
//        $apiRet['apiInfo']='商城商品列表获取成功';
//        $apiRet['apiState']='success';
//        $apiRet['apiData']=$data;
        $apiRet = returnData($data);
        return $apiRet;
    }

    /**
     * 获取商品的sku列表
     * */
    public function getGoodsSkuList($param)
    {
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '获取数据失败';
//        $apiRet['apiState'] = 'error';
        $apiRet = returnData(null, -1, 'error', '获取数据失败');
        $goodsId = $param['goodsId'];
        if (!empty($goodsId)) {
            $response = [];
            $systemTab = M('sku_goods_system');
            $goodsId = $param['goodsId'];
            $sysWhere = [];
            $sysWhere ['dataFlag'] = 1;
            $sysWhere ['goodsId'] = $goodsId;
            $systemSpec = $systemTab->where($sysWhere)->order('skuId asc')->select();
            if ($systemSpec) {
                $response = [];
                foreach ($systemSpec as $value) {
                    $spec = [];
                    $spec['skuId'] = $value['skuId'];
                    $spec['systemSpec']['skuShopPrice'] = $value['skuShopPrice'];
                    $spec['systemSpec']['skuMemberPrice'] = $value['skuMemberPrice'];
                    $spec['systemSpec']['skuGoodsStock'] = $value['skuGoodsStock'];
                    $spec['systemSpec']['skuGoodsImg'] = $value['skuGoodsImg'];
                    $spec['systemSpec']['skuBarcode'] = $value['skuBarcode'];
                    $spec['systemSpec']['skuMarketPrice'] = $value['skuMarketPrice'];

                    $spec['selfSpec'] = M("sku_goods_self se")
                        ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                        ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                        ->where(['se.skuId' => $value['skuId'], 'se.dataFlag' => 1, 'sp.dataFlag' => 1, 'sr.dataFlag' => 1])
                        ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName')
                        ->order('sp.sort asc')
                        ->select();;
                    $response[] = $spec;
                }
//                $apiRet['apiCode'] = 0;
//                $apiRet['apiInfo'] = '获取数据成功';
//                $apiRet['apiState'] = 'success';

            }
        }
//        $apiRet['apiData'] = $response;
        $apiRet = returnData($response);
        return $apiRet;
    }

    /**
     * 获取店铺配置
     */
    public function getShopCfg($shopId)
    {
        $mc = M('shop_configs');
        $rs = $mc->where("shopId=" . $shopId)->find();
        $shopAds = array();
        if ($rs["shopAds"] != '') {
            $shopAdsImg = explode('#@#', $rs["shopAds"]);
            $shopAdsUrl = explode('#@#', $rs["shopAdsUrl"]);
            for ($i = 0; $i < count($shopAdsImg); $i++) {
                $adsImg = $shopAdsImg[$i];
                $shopAds[$i]["adImg"] = $adsImg;

                //判断是商品:1 还是活动页:2  0不跳转
                $shopAdsUrl2 = explode('$@$', $shopAdsUrl[$i]);


                if ($shopAdsUrl2[1] == 1) {
                    $shopAds[$i]["type"] = 1;
                }

                if ($shopAdsUrl2[1] == 2) {
                    $shopAds[$i]["type"] = 2;
                }

                if (empty($shopAds[$i]["type"])) {
                    $shopAds[$i]["type"] = 0;
                }

                $imgpaths = explode('.', $adsImg);
                $shopAds[$i]["adImg_thumb"] = $imgpaths[0] . "_thumb." . $imgpaths[1];
                $shopAds[$i]["adUrl"] = explode('$@$', $shopAdsUrl[$i])[0];
            }
        }
        $rs['shopAds'] = $shopAds;
        return $rs;
    }

    /**
     * 改变购物中商品的状态[前置仓]
     */
    public function changeCartGoodsStatus($param)
    {
        $userId = $param['userId'];
        $cartId = trim($param['cartId'], ',');
        $isChecked = $param['isChecked'];
        $tab = M('cart');
        $cartList = null;
        if (empty($param['cartId'])) {
            $where = [];
            $where['userId'] = $userId;
            $cartList = $tab->where($where)->select();
            foreach ($cartList as $val) {
                $cartId .= $val['cartId'] . ",";
            }
            $cartId = trim($cartId, ',');
        }
        if (!empty($cartId)) {
            $cartIdArr = explode(',', $cartId);
            $where = [];
            $where['userId'] = $userId;
            $where['cartId'] = ['IN', $cartIdArr];
            $cartList = $tab->where($where)->select();
            if (!$cartList) {
                $apiRet = returnData(null, -1, 'error', '购物车中暂无相关数据');
                return $apiRet;
            }
            foreach ($cartList as $key => $val) {
                $status = 1;
                if ($val['isCheck'] == 1) {
                    $status = 0;
                }
                if (empty($param['cartId']) && $isChecked == 'true') {
                    $status = 1;
                } elseif (empty($param['cartId']) && $isChecked == 'false') {
                    $status = 0;
                }
                //后加清除失效商品的已选中状态(true|false)
                if ($param['clear'] == 'true') {
                    $status = 0;
                } elseif ($param['clear'] == 'false') {
                    $status = 1;
                }
                $save['isCheck'] = $status;
                $checkRes = $tab->where(['cartId' => $val['cartId']])->save($save);
                if ($checkRes) {
                    $cartList[$key]['isCheck'] = $status;
                }
                $cartList[$key]['isCheck'] = (int)$status;
            }
        }
        $apiRet = returnData($cartList);
        return $apiRet;
    }

    /**
     * 改变购物中商品的状态[多商户]
     */
    public function changeCartGoodsStatusDSH($param)
    {
        $userId = $param['userId'];
        $cartId = trim($param['cartId'], ',');
        $shopId = $param['shopId'];
        $checkAll = $param['checkAll'];
        $clear = $param['clear'];
        $cartTab = M('cart');
        if (!empty($cartId) && empty($shopId) && empty($checkAll)) {
            $where = [];
            $where['userId'] = $userId;
            $where['cartId'] = ['IN', $cartId];
            $cartList = $cartTab->where($where)->select();
            if (empty($cartList)) {
                $apiRet = returnData(null, -1, 'error', '购物车中暂无相关数据');
                return $apiRet;
            }
            foreach ($cartList as $key => $val) {
                $isCheck = 1;//0:未选中|1:已选中
                //根据传过来的cartId自行判断是否选中
                if ($val['isCheck'] == 1) {
                    $isCheck = 0;
                }
                //清除失效商品
                if ($clear == 'true') {
                    $isCheck = 0;
                } elseif ($clear == 'false') {
                    $isCheck = 1;
                }
                $where = [];
                $where['cartId'] = $val['cartId'];
                $saveData = [];
                $saveData['isCheck'] = $isCheck;
                $changeRes = $cartTab->where($where)->save($saveData);
                $cartList[$key]['isCheck'] = (int)$val['isCheck'];
                if ($changeRes) {
                    $cartList[$key]['isCheck'] = (int)$isCheck;
                }
            }
            return returnData($cartList);
        }
        //店铺全选或者反选 全选/反选(1:店铺全选|2:店铺反选|3:所有全选|4:所有反选)
        if ($checkAll > 0) {
            $submitOrderMod = D('V3/SubmitOrder');
            if (in_array($checkAll, [1, 3])) {
                $goodsChecked = 0;
                $isCheck = 1;
            } elseif (in_array($checkAll, [2, 4])) {
                $goodsChecked = 1;
                $isCheck = 0;
            }
            $cartGoodsInfo = $submitOrderMod->getCartGoodsChecked($userId, $shopId, $goodsChecked);
            $cartGoods = (array)$cartGoodsInfo['goodsSku'];
            if (empty($cartGoods)) {
                //应前端要求,把该验证去掉
                /*$apiRet = returnData(null,-1,'error','购物车中暂无相关数据');
                return $apiRet;*/
                return returnData();
            }
            if ($shopId > 0) {
                foreach ($cartGoods as $key => $val) {
                    if ($val['shopId'] != $shopId) {
                        unset($cartGoods[$key]);
                    }
                }
                $cartGoods = array_values($cartGoods);
                if (!$cartGoods) {
                    //应前端要求,把该验证去掉
                    /*$apiRet = returnData(null,-1,'error','购物车中暂无符合条件的数据');
                    return $apiRet;*/
                    return returnData();
                }
            }
            foreach ($cartGoods as $key => $val) {
                $where = [];
                $where['cartId'] = $val['cartId'];
                $saveData = [];
                $saveData['isCheck'] = (int)$isCheck;
                $changeRes = $cartTab->where($where)->save($saveData);
                if ($changeRes) {
                    $cartGoods[$key]['isCheck'] = (int)$isCheck;
                }
            }
        }
        return returnData($cartGoods);
    }

    /**
     * 废弃
     * 获取购物车商品合计价格
     */
    public function sumCartGoodsAmount($param)
    {
        $userId = $param['userId'];
        $cartId = trim($param['cartId'], ',');
        $tab = M('cart');
        $amount = 0;
        if (empty($param['cartId'])) {
            $where = [];
            $where['userId'] = $userId;
            $cartList = $tab->where($where)->select();
            foreach ($cartList as $val) {
                $cartId .= $val['cartId'] . ",";
            }
            $cartId = trim($cartId, ',');
        }
        if (!empty($cartId)) {
            $userInfo = M('users')->where(['userId' => $userId])->field('userId,loginName,expireTime')->find();
            $cartIdArr = explode(',', $cartId);
            $where = [];
            $where['userId'] = $userId;
            $where['cartId'] = ['IN', $cartIdArr];
            $cartList = $tab->where($where)->select();
            if (!$cartList) {
                $apiRet = returnData(null, -1, 'error', '购物车中暂无相关数据');
                return $apiRet;
            }
            if ($cartList) {
                $goodsTab = M('goods');
                $time = time();
                foreach ($cartList as $value) {
                    $goodsInfo = $goodsTab->where(['goodsId' => $value['goodsId']])->find();
                    $goodsPrice = $goodsInfo['shopPrice'];
                    if (strtotime($userInfo['expireTime']) > $time && $goodsInfo['memberPrice'] > 0) {
                        $goodsPrice = $goodsInfo['memberPrice'];
                    }
                    $totalPrice = $goodsPrice * $value['goodsCnt'];
                    $amount += $totalPrice;
                }
            }
        }
        $data = [];
        $data['amount'] = number_format($amount, 2, ".", "");
        $apiRet = returnData($data);
        return $apiRet;
    }

    /**
     * 废弃:现在从预提交订单中获取可用优惠券列表
     * 提交订单-可用优惠券列表
     */
    public function effectiveCoupons($param)
    {
        $userId = $param['userId'];
        $goodsTab = M('goods');
        $tab = M('cart');
        $where = [];
        $where['userId'] = $userId;
        $where['isCheck'] = 1;
        $cartList = $tab->where($where)->select();
        $data = null;
        $amount = 0;
        if ($cartList) {
            $time = time();
            $userInfo = M('users')->where(['userId' => $userId])->field('userId,loginName,expireTime')->find();
            foreach ($cartList as $value) {
                $goodsInfo = $goodsTab->where(['goodsId' => $value['goodsId']])->find();
                $goodsPrice = $goodsInfo['shopPrice'];
                if (strtotime($userInfo['expireTime']) > $time && $goodsInfo['memberPrice'] > 0) {
                    $goodsPrice = $goodsInfo['memberPrice'];
                }
                $totalPrice = $goodsPrice * $value['goodsCnt'];
                $amount += $totalPrice;
            }
        }

        $couponsTab = M('coupons');
        $couponsUsersTab = M('coupons_users');
        $couponWhere = array();
        $couponWhere['userId'] = $userInfo['userId'];
        $couponWhere['dataFlag'] = 1;
        $couponWhere['couponStatus'] = 1;
        $couponWhere['couponExpireTime'] = ['EGT', date('Y-m-d H:i:s')];
        $userCouponList = $couponsUsersTab->where($couponWhere)->select();
        if ($userCouponList) {
            $coupons = [];
            foreach ($userCouponList as $key => $value) {
                $where = [];
                $where['couponId'] = $value['couponId'];
                $where['dataFlag'] = 1;
                $couponInfo = $couponsTab->where($where)->find();
                if ($couponInfo && $couponInfo['spendMoney'] <= $amount) {
                    $newData = $couponInfo;
                    $newData['couponExpireTime'] = $value['couponExpireTime'];
                    $newData['userToId'] = $value['userToId'];
                    $newData['userId'] = $value['userId'];
                    $newData['receiveTime'] = $value['receiveTime'];
                    $newData['couponStatus'] = $value['couponStatus'];
                    $newData['userCouponId'] = $value['id'];
                    $coupons[] = $newData;
                }
            }
            $sort = [];
            foreach ($coupons as $dataval) {
                $sort[] = $dataval['couponMoney'];
            }
            array_multisort($sort, SORT_DESC, SORT_NUMERIC, $coupons);
            $data = $coupons;
        }
        //判断是否允许使用优惠券-start
        $goods_module = new GoodsModule();
        $split_order_res = $goods_module->getGoodsSplitOrder($cartList);
        $can_use_coupon = $split_order_res['can_use_coupon'];//是否允许使用优惠券(0:不允许 1:允许)
        if ($can_use_coupon != 1) {
            $data = array();
        }
        //判断是否允许使用优惠券-end
        $apiRet = returnData((array)$data);
        return $apiRet;
    }

    /**
     * 订单-预提交 PS:只适用于前置仓
     * */
    public function preSubmit($param)
    {
        $shop_module = new ShopsModule();
        $goods_module = new GoodsModule();
        $users_module = new UsersModule();
        $order_module = new OrdersModule();
        $cart_module = new CartModule();
        $coupon_module = new CouponsModule();
        $configs = $GLOBALS['CONFIG'];
        $wu_coupon_id = $param['wuCouponId'];//用户领取的运费券的记录id
        $address_id = $param['addressId'];
        $users_id = (int)$param['userId'];
        $shop_id = (int)$param['shopId'];
        $cuid = $param['cuid'];//用户领取优惠券的记录id
        $is_self = $param['isSelf'];
        $use_score = $param['useScore'];//是否使用积分(1:使用 非1为不使用)
        $invoice_client = $param['invoiceClient'];
        $useCouponType = $configs['useCouponType'];//优惠券使用【1：用户手动选择|2：系统默认使用最大面值优惠券】
        $buyNowGoodsId = (int)$param['buyNowGoodsId'];//立即购买-商品id 注：仅用于立即购买
        $buyNowSkuId = (int)$param['buyNowSkuId'];//立即购买-skuId 注：仅用于立即购买
        $buyNowGoodsCnt = (float)$param['buyNowGoodsCnt']; //立即购买-数量 注：仅用于立即购买
        if (!empty($buyNowGoodsId)) {//立即购买
            //为了兼容线上正在运行的程序，这里不强制校验
            if ($buyNowGoodsCnt <= 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入立即购买数量！');
            }
            $cart_goods = $cart_module->getBuyNowGoodsList($users_id, $buyNowGoodsId, $buyNowSkuId, $buyNowGoodsCnt);
            if (empty($cart_goods['goods_list'])) {
                if (!empty($cart_goods['error'])) {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', $cart_goods['error']);
                } else {
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品库存不足或已被下架！');
                }
            }
        } else {
            $cart_goods = $cart_module->getCartGoodsChecked($users_id, array($shop_id));
        }
        $goods_list = $cart_goods['goods_list'];
        //数据验证
        $validator = array();
        //验证数据是否为同一家店铺数据
        $goods_shops = $order_module->getAllOrderShopList($goods_list);
        if (count($goods_shops) > 1) {
            $validator[] = ['shop' => 'error', 'msg' => '数据有误,不能同时提交多个店铺的数据'];
        }
        $shop_detail = $goods_shops[0];
        $shop_id = $shop_detail['shopId'];
        if (empty($shop_id)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要支付的商品');
        }
        $all_goods_list_map = [];
        $all_goods_id_arr = array_column($goods_list, 'goodsId');
        $all_goods_id_arr = array_unique($all_goods_id_arr);
        if (count($all_goods_id_arr) > 0) {
            $all_goods_list_data = $goods_module->getGoodsListById($all_goods_id_arr);
            $all_goods_list = $all_goods_list_data['data'];
            foreach ($all_goods_list as $all_goods_list_row) {
                $all_goods_list_map[$all_goods_list_row['goodsId']] = $all_goods_list_row;
            }
        }
        $systemSpecListMap = [];
        $skuIdArr = array_column($goods_list, 'skuId');
        $skuIdArr = array_unique($skuIdArr);
        if (count($skuIdArr) > 0) {
            $systemTab = M('sku_goods_system');
            $sysWhere = [];
            $sysWhere['skuId'] = array('in', $skuIdArr);
            $systemSpecList = $systemTab->where($sysWhere)->select();
            foreach ($systemSpecList as $systemSpecListRow) {
                if ($systemSpecListRow['skuId'] <= 0) {
                    continue;
                }
                $systemSpecListMap[$systemSpecListRow['skuId']] = $systemSpecListRow;
            }
        }
        //获取用户信息
        $users_detail = $users_module->getUsersDetailById($users_id, '*', 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        //验证收货地址,addressId为0则选择默认地址
        $address_detail = $users_module->getUserAddressDetail($users_id, $address_id);
        $delivery_latlng_error = '';//用于存放门店配送范围的错误信息
        if ($is_self != 1) {//如果是自提订单就不验证收货地址了
            if (empty($address_detail) && empty($address_id)) {
                $address_error = ['address' => 'error', 'msg' => '请添加收货地址'];
            }
            if (empty($address_detail) && !empty($address_id)) {
                $address_error = ['address' => 'error', 'msg' => '请添加正确的收货地址'];
            }
            //验证用户收货地址否在配送范围内
            if ($address_detail) {
//                $address_id = (int)$address_detail['addressId'];
                $verrification_distribution = $order_module->verificationShopDistributionNew($users_detail, $address_detail, $shop_detail);
                if (!$verrification_distribution) {
                    $delivery_latlng_error = ['deliveryLatLng' => 'error', 'msg' => '不在配送范围内'];
                }
            }
        }

        //商城配置中设置为统一配置
        if ($configs['setDeliveryMoney'] == 2) {
            $shop_detail["deliveryMoney"] = $configs['deliveryMoney'];
            $shop_detail["deliveryFreeMoney"] = $configs['deliveryFreeMoney'];
        }
        //处理购物车商品sku数据
        $result = $cart_module->handleCartGoodsSku($goods_list);
//        $result[0] = rankGoodsPrice($result[0]);//商品等级价格处理
        //校验购物车商品是否符合店铺设置的配送起步价
//        $verification_shop = $cart_module->verificationDeliveryStartMoney($users_id, $shop_id);
        $verification_shop = $cart_module->verificationDeliveryStartMoneyNew($users_detail, $shop_id, $result);
        $delivery_start_money = '';//用于存放店铺起步价的错误信息
        if ($verification_shop['code'] != ExceptionCodeEnum::SUCCESS) {
            $delivery_start_money = ['deliveryLatLng' => 'error', 'msg' => '未达到店铺订单配送起步价'];
        }
        //$totalGoodsAmount = 0;//商品总金额
        $coupon_amount = 0;//优惠券抵扣金额
        $score_amount = 0;//积分可抵扣金额
        $total_member_price = array();//商品会员价总计
        $use_scoreb = 0;//使用积分
        //后加本单可使用积分-start
        $can_use_score = 0;//本单可使用积分
        $can_use_score_amount = 0;//本单可使用积分抵扣金额
        //后加本单可使用积分-end
        //未失效优惠券
        $coupons = array();//可使用的优惠券
        $coupons_delivery = array();//可使用的运费券
//        $effective_coupons = $order_module->get_order_can_use_coupons($users_id, $shop_id);//获取可用的优惠券
        $effective_coupons = $order_module->get_order_can_use_coupons_new($users_id, $shop_id, $goods_list, $result);//获取可用的优惠券
        if ($effective_coupons['code'] == 0 && !empty($effective_coupons['data']) && $useCouponType == 2) {
            $cuid = $effective_coupons['data'][0]['userCouponId'];
        }
        //--------------配送劵--------------------
//        $delivery_coupons = $order_module->get_order_can_use_coupons($users_id, $shop_id, 2);//获取可用的运费券
        $delivery_coupons = $order_module->get_order_can_use_coupons_new($users_id, $shop_id, $goods_list, $result, 2);//获取可用的运费券
        //$psType = 0;//用于判断配送劵是否展示【0:展示|1:不展示】
        $is_show_delivery_coupon = 1;//用于判断配送劵是否展示【0:不展示|1:展示】
        //----------------------------------------

        $count_goods = 0;//购物车商品种类
        $count_goods_cnt = 0;//购物车商品数量
        $delayed_arr = array();//本单将延时多少分钟
        $total_money = array();//商品总金额,纯粹的商品金额
        $current_shop_goods = $result[0];//当前门店商品数据
        $score_cash_ratio = explode(':', $configs['scoreCashRatio']);//积分金额比例
        $score_cash_ratio0 = 0;
        $score_cash_ratio1 = 0;
        if (is_array($score_cash_ratio) && count($score_cash_ratio) == 2) {
            $score_cash_ratio0 = $score_cash_ratio[0];
            $score_cash_ratio1 = $score_cash_ratio[1];
        }


        $open_score_pay = $configs['isOpenScorePay'];//是否开启了积分支付(1:开启)
        //PS:整个方法就不重构了,只修改这个循环体
        $shop_price_total = array();//店铺价总计
        foreach ($current_shop_goods as $current_key => $current_val) {
            $goods_id = (int)$current_val['goodsId'];
            $sku_id = (int)$current_val['skuId'];
//            $goods_attr_id = (int)$current_val['goodsAttrId'];
            $goods_cnt = $current_val['goodsCnt'];
            $goods_detail = $current_val;
            $goods_delayed = (int)$current_val['delayed'];
            if ($goods_delayed > 0) {
                $delayed_arr[] = $goods_delayed;
            }
            $shop_price_total[] = bc_math($current_val['shopPrice'], $goods_cnt, 'bcmul', 2);
            $count_goods += 1;
            $count_goods_cnt += $current_val['goodsCnt'];
            $init_price = $goods_module->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_cnt, $current_val);//替换商品价格
            $current_shop_goods[$current_key]['shopPrice'] = formatAmount($init_price);
            $current_shop_goods[$current_key]['goodsPrice'] = formatAmount($init_price);
            $goods_price_total = bc_math($init_price, $goods_cnt, 'bcmul', 2);//该商品金额小计
            if ($open_score_pay == 1) {
                //用户使用积分
                $discount_price = $goods_price_total;
                //获取比例
                $goods_score = (float)$discount_price / (float)$score_cash_ratio1 * (float)$score_cash_ratio0;
                $integral_rate_score = (int)($goods_score * ($goods_detail['integralRate'] / 100));//该商品允许抵扣的积分
                if ($use_score == 1) {
                    $use_scoreb += (int)$integral_rate_score;
                }
                $can_use_score += $integral_rate_score;//用户最多可以抵扣的积分,后面还要计算
            }
            $total_money[$current_key] = $goods_price_total;
//            $member_price = $goods_module->getGoodsMemberPrice($goods_id, $sku_id);
            $member_price = $goods_module->getGoodsMemberPriceNew($all_goods_list_map[$goods_id], $systemSpecListMap[$sku_id]);
            $member_price_total = (float)bc_math($member_price, $goods_cnt, 'bcmul', 2);
            $total_member_price[$current_key] = $member_price_total;
        }
//        根据商品列表信息返回拆单信息-start
//        拆单需求:
//        1.常规商品和非常规商品
//        2.限时活动商品或限量活动商品仅自提
        $split_order_res = $goods_module->getGoodsSplitOrder($goods_list);
        $split_num = $split_order_res['num'];
        $must_self = $split_order_res['must_self'];//是否必须自提(0:非必须 1:必须)
        $can_use_coupon = $split_order_res['can_use_coupon'];//是否可以使用优惠券(0:不允许 1:允许)
        if ($can_use_coupon != 1) {
            //不允许使用优惠券就不要返回数据给前端了
            $effective_coupons['data'] = array();
            $delivery_coupons['data'] = array();
            $cuid = 0;
            $wu_coupon_id = 0;
        }
//        根据商品列表信息返回拆单信息-end
        $total_money = array_sum($total_money);//后面就不要再操作该金额了
        $total_member_price = array_sum($total_member_price);
        $math_total_amount = $total_money;//用于后面的金额计算
        $coupon_detail = array();//优惠券信息
        $use_coupon_max_money = 0;//优惠券最多可抵扣金额
        if (!empty($cuid) && !empty($users_detail)) {
            $user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
            $coupon_detail = $coupon_module->getCouponDetailById($user_coupon_detail['couponId']);
            if (empty($coupon_detail) || $user_coupon_detail['couponStatus'] != 1) {
                $coupon_detail = array();
                $coupon_error = ['coupon' => 'error', 'msg' => '请选择可用的优惠券'];
            } else {
                $coupon_detail['userCouponId'] = $user_coupon_detail['id'];
                $verification_coupon = $goods_module->getGoodsSplitOrder($goods_list, $cuid);//校验当前优惠券是否可用
                if ($verification_coupon['can_use_coupon'] == 1) {
                    if ($coupon_detail['couponType'] != 8) {
                        $coupon_amount = (float)$coupon_detail['couponMoney'];//优惠券面额
                        $use_coupon_max_money = $verification_coupon['use_coupon_detail']['coupon_use_max_money'];//优惠券最多抵扣金额
                        $coupon_detail['use_coupon_max_money'] = formatAmount($use_coupon_max_money);
//                        $math_total_amount = bc_math($math_total_amount, $coupon_amount, 'bcsub', 2);
                        $math_total_amount = bc_math($math_total_amount, $use_coupon_max_money, 'bcsub', 2);
                    }
                } else {
                    $coupon_error = ['coupon' => 'error', 'msg' => '当前优惠券不可用'];
                }
            }
        }
        if ($can_use_score > 0) {
            //积分可以抵扣的金额
            $can_use_score_amount = (int)$can_use_score / (float)$score_cash_ratio0 * (float)$score_cash_ratio1;
        }
        //是否使用发票
        if ($invoice_client > 0) {
            if (isset($shop_detail['isInvoice']) && $shop_detail['isInvoice'] == 1) {
                $invoiceAmount = $math_total_amount * ((int)$shop_detail['isInvoicePoint'] / 100);
                $math_total_amount = bc_math($math_total_amount, $invoiceAmount, 'bcadd', 2);
            }
        }
        $order_score = getOrderScoreByOrderScoreRate($total_money);//本次订单可以获得的积分
        $deliver_money = $shop_detail['deliveryMoney'];//运费
        if ((float)$total_money >= (float)$shop_detail["deliveryFreeMoney"]) {
            $deliver_money = 0;
        }
        if ($is_self == 1) {
            //自提免运费
            $deliver_money = 0;
        }
        $delivery_coupon_detail = array();//运费券信息
        $use_delivery_coupon_max_money = 0;//运费券最多可抵扣的金额
        if (!empty($wu_coupon_id)) {
            $user_coupon_detail = $coupon_module->getUserCouponDetail($wu_coupon_id);
            $delivery_coupon_detail = $coupon_module->getCouponDetailById($user_coupon_detail['couponId']);
            if (empty($delivery_coupon_detail) || $user_coupon_detail['couponStatus'] != 1) {
                $delivery_coupon_detail = array();
                $delivery_coupon_error = ['delivery_coupon' => 'error', 'msg' => '请选择可用的运费券'];
            } else {
                $delivery_coupon_detail['userCouponId'] = $user_coupon_detail['id'];
                $verification_coupon = $goods_module->getGoodsSplitOrder($goods_list, $wu_coupon_id);//校验当前优惠券是否可用
                if ($verification_coupon['can_use_coupon'] == 1) {
                    if ($delivery_coupon_detail['couponType'] == 8) {
                        $delivery_coupon_money = $delivery_coupon_detail['couponMoney'];//运费券面额
                        $use_delivery_coupon_max_money = $verification_coupon['use_coupon_detail']['coupon_use_max_money'];//运费券最多抵扣金额
                        $last_delivery_money = $deliver_money;//勿动
                        $delivery_coupon_detail['use_delivery_coupon_max_money'] = formatAmount($last_delivery_money);
                        $deliver_money = (float)bc_math($deliver_money, $use_delivery_coupon_max_money, 'bcsub', 2);
//                        $deliver_money = (float)bc_math($deliver_money, $delivery_coupon_money, 'bcsub', 2);
                        if ($deliver_money <= 0) {
                            $use_delivery_coupon_max_money = $last_delivery_money;
                            $deliver_money = 0;
                        }
                        if ($total_money <= $use_delivery_coupon_max_money) {
                            $deliver_money = 0;
                        }
                    }
                } else {
                    $coupon_error = ['coupon' => 'error', 'msg' => '当前运费券不可用'];
                }
            }
        }
        $invoice_detail = array();
        if (!empty($invoice_client)) {
            $invoice_detail = $users_module->getUserInvoiceDetail($users_id, $invoice_client);//用户发票详情
        }
        //运费券详情------------start---------2020-8-20 14:30:34----------
        //--------------------end----------------------------------------
        //验证信息结果start
        //地址验证
        if (!empty($address_error)) {
            $validator[] = $address_error;
        }
        //配送范围验证
        if (!empty($delivery_latlng_error)) {
            $validator[] = $delivery_latlng_error;
        }
        //配送起步价
        if (!empty($delivery_start_money)) {
            $validator[] = $delivery_start_money;
        }
        //优惠券
        if (!empty($coupon_error)) {
            $validator[] = $coupon_error;
        }
        //运费券
        if (!empty($delivery_coupon_error)) {
            $validator[] = $delivery_coupon_error;
        }
        //已欠款额度
        if ((float)$users_detail['quota_arrears'] > 0) {
            $validator[] = array('quota_arrears' => 'error', 'msg' => "账户存在欠款，请先处理已欠款额度");
        }
        if ($must_self == 1) {
            //必须自提的订单归为自提订单,不收取配送费
            $deliver_money = 0;
        }
        //-----------------------start------2020-8-19 16:11:48修改，添加配送费为0时不会返回数据
        if ($deliver_money == 0 && empty($wu_coupon_id)) {
            $is_show_delivery_coupon = 0;//用于判断配送劵是否展示【0:不展示|1:展示】
        }
        if ($delivery_coupons['code'] == 0 && $is_show_delivery_coupon == 1) {
            $coupons_delivery = $delivery_coupons['data'];
            foreach ($coupons_delivery as $key => $val) {
                $coupons_delivery[$key]['useStatus'] = 1;
            }
            $coupons_delivery = array_values($coupons_delivery);
        }
        if ($effective_coupons['code'] == 0) {
            $coupons = $effective_coupons['data'];
            foreach ($coupons as $key => $val) {
                $coupons[$key]['useStatus'] = 1;//该字段是以前的,大概是使用状态吧,先保留着吧
            }
            $coupons = array_values($coupons);
        }
        //-----------------------end-----------------------------------------------------------
        //分单--订单数量
        if ($split_num > 1) {
            $response['isConventionInfo'] = "您的订单将进行分单,本次将分成{$split_num}笔订单";//分单订单信息
        } else {
            $response['isConventionInfo'] = "";
            if ($must_self == 1) {
                $response['isConventionInfo'] = "当前订单仅限自提";
            }
        }
        if ($use_scoreb > 0) {
            $score_amount = (float)((int)$use_scoreb / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        if ($use_scoreb > $users_detail['userScore']) {
            $use_scoreb = (int)$users_detail['userScore'];
            $score_amount = (float)((int)$use_scoreb / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        if ($can_use_score > $users_detail['userScore']) {
            $can_use_score = (int)$users_detail['userScore'];
            $can_use_score_amount = (float)((int)$can_use_score / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        $real_total_amount = (float)bc_math($math_total_amount, $deliver_money, 'bcadd', 2);
        if ((float)$score_amount > 0) {
            $real_total_amount = (float)bc_math($real_total_amount, $score_amount, 'bcsub', 2);
        }
        if ($real_total_amount < 0) {
            $real_total_amount = 0;
        }
        $need_pay = $real_total_amount;
        //验证信息结果end
        $response['must_self'] = $must_self;//是否必须自提(0:不限制 1:限制)
        $response['goods'] = $current_shop_goods;//商品信息
        $response['couponList'] = $coupons;//可用优惠券信息
        $response['couponDeliveryList'] = $coupons_delivery;//可用运费券信息
        $response['couponInfo'] = $coupon_detail;//优惠券详情,前端传cuid时,返回优惠券详情
        $response['wuCouponInfo'] = $delivery_coupon_detail;//运费券详情,前端传wuCouponId时,返回运费券详情
        $user_invoice_list = $users_module->getUserInvoiceList($users_id);
        if ($shop_detail['isInvoice'] != 1) {
            //店铺不支持开发票就不返回用户发票信息了
            $user_invoice_list = array();
        }
        $response['userInvoices'] = $user_invoice_list;//用户发票信息列表 PS:仅门店支持开具发票时才会返回信息
        $response['invoiceInfo'] = $invoice_detail;//返回用户当前选择的发票信息
        $response['address'] = $address_detail;//地址信息
        $response['needPay'] = formatAmount($need_pay);//需付款金额
        $response['orderScore'] = $order_score;
        $response['totalGoodsAmount'] = formatAmount($total_money);//商品总金额
        $response['realTotalAmount'] = formatAmount($real_total_amount);//实付金额
        $response['couponAmount'] = formatAmount($coupon_amount);//优惠券金额
        $response['use_coupon_max_money'] = formatAmount($use_coupon_max_money);//优惠券最多抵扣金额
        $response['delivery_coupon_money'] = formatAmount($delivery_coupon_money);//运费券面额
        $response['use_delivery_coupon_max_money'] = formatAmount($use_delivery_coupon_max_money);//运费券最多抵扣金额
        $response['deliveryAmount'] = formatAmount($deliver_money);//配送费
        $response['balance'] = formatAmount($users_detail['balance']);//用户余额
        $response['userScore'] = (int)$users_detail['userScore'];//用户积分
        $response['useScore'] = (int)$use_scoreb;//抵扣积分
        $response['can_use_score'] = $can_use_score;
        $response['can_use_score_amount'] = formatAmount($can_use_score_amount);
        $response['scoreAmount'] = formatAmount($score_amount);//积分抵扣金额
        //包邮起步价
        $response['deliveryFreeMoney'] = $shop_detail['deliveryFreeMoney'];
        $response['shopAddress'] = $shop_detail['shopAddress'];
        $response['detailAddress'] = (string)$address_detail['detail_address'];
        $response['totalMemberPrice'] = formatAmount($total_member_price);
        //店铺营业时间
        $response['serviceStartTime'] = $shop_detail['serviceStartTime'];
        $response['serviceEndTime'] = $shop_detail['serviceEndTime'];
        //店铺配置
        $shopConfig = $shop_module->getShopConfig($shop_id, '*', 2);
        $response['cashOnDelivery'] = $shopConfig['cashOnDelivery'];//开启货到付款【-1：未开启|1：已开启】
        $response['cashOnDeliveryCoupon'] = $shopConfig['cashOnDeliveryCoupon'];//货到付款支持优惠券【-1：不支持|1：支持】
        $response['cashOnDeliveryScore'] = $shopConfig['cashOnDeliveryScore'];//货到付款支持积分【-1：不支持|1：支持】
        $response['cashOnDeliveryMemberCoupon'] = $shopConfig['cashOnDeliveryMemberCoupon'];//货到付款会员券【-1：不支持|1：支持】
        $response['isInvoice'] = $shop_detail['isInvoice'];//能否开发票(1:能 0:不能)
        $response['invoiceRemarks'] = $shop_detail['invoiceRemarks'];//发票说明
        $response['deliveryCostTime'] = $shop_detail['deliveryCostTime'];
        $response['economyAmount'] = $cart_module->getMemberEconomyAmount($users_id, $shop_id);//开通会员节省了多少钱(会员)
        $shop_price_total = array_sum($shop_price_total);
        $amount_saved = (float)bc_math($shop_price_total, $response['realTotalAmount'], 'bcsub', 2);
        if ($amount_saved < 0) {
            $amount_saved = 0;
        }
        $response['amount_saved'] = formatAmount($amount_saved);//本单节省的金额 PS:店铺价与实收金额的差
        $response['validator'] = $validator;
        $response['countGoods'] = $count_goods;//购物车商品种类
        $response['countGoodsCnt'] = $count_goods_cnt;//购物车商品数量
        //商品延时
        $response['delayed_msg'] = "";
        if (count($delayed_arr) > 0) {
            array_multisort($delayed_arr, SORT_DESC);
            $response['delayed_msg'] = "本单将延时{$delayed_arr[0]}分钟";
        }
        return returnData($response);
    }

    //旧的
//    public function preSubmit($param)
//    {
//        $wuCouponId = $param['wuCouponId'];//运费劵ID
//        $addressId = $param['addressId'];
//        $userId = $param['userId'];
//        $cuid = $param['cuid'];
//        $isSelf = $param['isSelf'];
//        $useScore = $param['useScore'];
//        $invoiceClient = $param['invoiceClient'];
//        $submitMod = new SubmitOrderModel();
//        $cartGoods = $submitMod->ge
//tCartGoodsChecked($param['userId'], $param['shopId']);
//        $goodsSku = $cartGoods['goodsSku'];
//        //数据验证
//        $validator = [];
//        //验证数据是否为同一家店铺数据
//        $goodsShops = $submitMod->getGoodsShop($goodsSku);
//        if (count($goodsShops) > 1) {
//            $validator[] = ['shop' => 'error', 'msg' => '数据有误,不能同时提交多个店铺的数据'];
//        }
//        $config = $GLOBALS['CONFIG'];
//        $shopInfo = $goodsShops[0];
//        $shopId = $goodsShops[0]['shopId'];
//        //验证收货地址,addressId为0则选择默认地址
//        $addressInfo = $submitMod->getAddress($userId, $addressId);
//        $lat = $addressInfo['lat'];
//        $lng = $addressInfo['lng'];
//        if ($isSelf != 1) {//如果是自提订单就不验证收货地址了
//            if (empty($addressInfo) && empty($addressId)) {
//                $addressMsg = ['address' => 'error', 'msg' => '请添加收货地址'];
//            }
//            if (empty($addressInfo) && !empty($addressId)) {
//                $addressMsg = ['address' => 'error', 'msg' => '请添加正确的收货地址'];
//            }
//            //验证是否在配送范围内
//            if ($addressInfo) {
//                $dcheck = checkShopDistribution($shopId, $lng, $lat);
//                if (!$dcheck) {
//                    $deliveryLatLngMsg = ['deliveryLatLng' => 'error', 'msg' => '配送范围超出'];
//                }
//            }
//        }
//        //商城配置中设置为统一配置
//        if ($config['setDeliveryMoney'] == 2) {
//            $shopInfo["deliveryMoney"] = $GLOBALS['CONFIG']['deliveryMoney'];
//            $shopInfo["deliveryFreeMoney"] = $GLOBALS['CONFIG']['deliveryFreeMoney'];
//        }
//        $goodsTab = M("goods");
//        $systemTab = M('sku_goods_system');
////        $cartModel = D('V3/Cart');
//        $cartModel = new CartModel();
//        //获取用户Id 根据登陆名
//        $userInfo = $this->getUserInfoById($userId);
//        //处理商品sku数据
//        $result = $submitMod->handleGoodsSku($goodsSku);
//        $result[0] = rankGoodsPrice($result[0]);//商品等级价格处理
//        //检测是否符合店铺设置的配送起步价
//        $checkInfo = checkdeliveryStartMoney($result, $userInfo);
//        if ($checkInfo['state'] == false) {
//            $deliveryStartMoney = ['deliveryLatLng' => 'error', 'msg' => '未达到店铺订单配送起步价'];
//        }
//        $totalGoodsAmount = 0;//商品总金额
//        $couponAmount = 0;//优惠券抵扣金额
//        $scoreAmount = 0;//积分可抵扣金额
//        $realTotalAmount = 0;//实际支付金额
//        $totalMemberPrice = 0;
//        $useScoreb = 0;//使用积分
//        $isConvention = 0;//非常规订单【0:不存在|1:存在】
//        $convention = 0;//常规订单【0:不存在|1:存在】
//        $isConventionCount = 0;//分单订单数量
//        //后加本单可使用积分-start
//        $can_use_score = 0;//本单可使用积分
//        $can_use_score_amount = 0;//本单可使用积分抵扣金额
//        //后加本单可使用积分-end
//        //未失效优惠券
//        $coupons = [];
//        //运费劵
//        $couponsDelivery = [];
//        $effectiveCoupons = $submitMod->effectiveCoupons(['userId' => $userId, 'orderData' => $result]);
//        //--------------配送劵--------------------
//        $deliveryCoupons = $submitMod->effectiveCoupons(['userId' => $userId, 'orderData' => $result, 'tpType' => 'eq']);
//        $psType = 0;//用于判断配送劵是否展示【0:展示|1:不展示】
//        //----------------------------------------
//        $countGoods = 0;//购物车商品种类
//        $countGoodsCnt = 0;//购物车商品数量
//        $delayed_arr = array();//本单将延时多少分钟
//        foreach ($result as $key => $value) {
//            $singleData = $result[$key];
//            foreach ($singleData as $singleKey => $singleVal) {
//                $goods_delayed = (int)$singleVal['delayed'];
//                if ($goods_delayed > 0) {
//                    $delayed_arr[] = $goods_delayed;
//                }
//                $countGoods += 1;
//                $countGoodsCnt += $singleVal['goodsCnt'];
//                $totalMoney[$key][$singleKey] = (float)$singleVal["shopPrice"] * (int)$singleVal["goodsCnt"];
//                $goodsTotalMoney = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                //后加sku
//                if ($singleVal["skuId"] > 0) {
//                    $goodsTotalMoney = getGoodsSkuPrice($userId, $singleVal["skuId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                }
//                //限时---限量---数据替换
//                if ($singleVal['isLimitBuy'] == 1 || $singleVal['isFlashSale'] == 1) {
//                    $handleLimitGoods = $cartModel->activePriceReplaceShopPrice($singleVal);
//                    $goodsTotalMoney = $handleLimitGoods['shopPrice'] * (int)$singleVal["goodsCnt"];
//                }
//                $totalMoney[$key][$singleKey] = $goodsTotalMoney;
//                //非常规订单
//                if ($singleVal['isConvention'] == 1) {
//                    $isConvention = 1;//非常规订单【0:不存在|1:存在】
//                }
//                //常规订单
//                if ($singleVal['isConvention'] == 0) {
//                    $convention = 1;//常规订单【0:不存在|1:存在】
//                }
//            }
//            unset($singleKey);
//            unset($singleVal);
//            $totalMoney[$key] = array_sum($totalMoney[$key]);//计算总金额
//            $totalGoodsMoney[$key] = $totalMoney[$key];//纯粹的商品总金额
//            // ---------- 优惠券的使用(修改后的) --- @author liusijia --- 2019-08-15 18:46 --- start --
//            if (!empty($cuid) && !empty($userInfo)) {
//                $checkCoupon = $submitMod->checkCoupon($userInfo, $totalMoney[$key], $cuid);
//                if ($checkCoupon['code'] == 0) {
//                    if ($checkCoupon['data']['couponType'] != 8) {
//                        $totalMoney[$key] = $totalMoney[$key] - (int)$checkCoupon['data']['couponMoney'];
//                    }
//                    $couponAmount = $checkCoupon['data']['couponMoney'];//优惠券抵扣金额
//                } else {
//                    $couponMsg = ['coupon' => 'error', 'msg' => $checkCoupon['msg']];
//                }
//            }
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- start ---
//            $scoreScoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
//            foreach ($singleData as $singleKey => $singleVal) {
//                $totalMoney_s[$key][$singleKey] = (float)$singleVal["shopPrice"] * (int)$singleVal["goodsCnt"];
//                $goodsTotalMoney = getGoodsAttrPrice($userId, $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                if ($singleVal["skuId"] > 0) {
//                    $goodsTotalMoney = getGoodsSkuPrice($userId, $singleVal["skuId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"])['totalMoney'];
//                }
//                //TODO
//                $totalMoney_s[$key][$singleKey] = $goodsTotalMoney;
//                $goodsInfo = $goodsTab->where(['goodsId' => $singleVal["goodsId"]])->find();
//                $systemSkuSpec = $systemTab->where(['skuId' => $singleVal["skuId"]])->find();
//                if ($userInfo['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMoney_s[$key][$singleKey] = (int)$singleVal["goodsCnt"] * $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMoney_s[$key][$singleKey] = $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                    //限时---限量---数据替换
//                    if ($singleVal['isLimitBuy'] == 1 || $singleVal['isFlashSale'] == 1) {
//                        $handleLimitGoods = $cartModel->activePriceReplaceShopPrice($singleVal);
//                        $totalMoney_s[$key][$singleKey] = $handleLimitGoods['shopPrice'] * (int)$singleVal["goodsCnt"];
//                    }
//                } else {
//                    //后加
//                    if ($systemSkuSpec && $systemSkuSpec['skuMemberPrice'] > 0) {
//                        $totalMemberPrice += $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $totalMemberPrice += (int)$singleVal["goodsCnt"] * $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $totalMemberPrice += $goodsTotalMoney * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                    //限时---限量---数据替换
//                    if ($singleVal['isLimitBuy'] == 1 || $singleVal['isFlashSale'] == 1) {
//                        $handleLimitGoods = $cartModel->activePriceReplaceShopPrice($singleVal);
//                        $totalMoney_s[$key][$singleKey] = $handleLimitGoods['shopPrice'] * (int)$singleVal["goodsCnt"];
//                    }
//                }
//                if ($GLOBALS['CONFIG']['isOpenScorePay'] == 1) {
//                    if ($useScore == 1) {
//                        //用户使用积分
//                        $discountPrice = $totalMoney_s[$key][$singleKey];
//                        //获取比例
//                        $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//                        $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
//                        $useScoreb += $integralRateScore;
//                    }
//                    //用户使用积分
//                    $discountPrice = $totalMoney_s[$key][$singleKey];
//                    //获取比例
//                    $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
//                    $integralRateScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
//                    $can_use_score += $integralRateScore;
//                }
//            }
//            unset($singleKey);
//            unset($singleVal);
//            if ($can_use_score > 0) {
//                $can_use_score_amount = (int)$can_use_score / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//            }
//            //计算总金额
//            if (is_array($totalMoney_s[$key])) {
//                $totalMoney[$key] = array_sum($totalMoney_s[$key]);
//            } else {
//                $totalMoney[$key] = $totalMoney_s[$key];
//            }
//            //是否使用发票
//            if ($invoiceClient > 0) {
//                if (isset($shopInfo['isInvoice']) && $shopInfo['isInvoice'] == 1) {
//                    $invoiceAmount = $totalMoney[$key] * ((int)$shopInfo['isInvoicePoint'] / 100);
//                    $totalMoney[$key] = $totalMoney[$key] + $invoiceAmount;
//                }
//            }
//            $totalGoodsMoney[$key] = $totalMoney[$key];//纯粹的商品总金额
//            $data["totalMoney"] = (float)$totalGoodsMoney[$key];//商品总金额
//            $data["orderScore"] = getOrderScoreByOrderScoreRate($data['totalMoney']);//所得积分
//            // --- 处理会员折扣和会员专享商品的价格和积分奖励 - 二次验证，主要针对优惠券 --- @author liusijia --- end ---
//            $data["deliverMoney"] = $shopInfo["deliveryMoney"];//默认店铺运费 如果是其他配送方式下方覆盖即可
//            $data["deliverType"] = "1";//配送方式 门店配送
//            //设置订单配送方式
//            if ($shopInfo["deliveryType"] == 4 and $isSelf !== 1) {
//                $data["deliverType"] = "4";//配送方式 快跑者配送
//            }
//            //当前店铺是否是达达配送 且当前订单 不为自提
//            if ($shopInfo["deliveryType"] == 2 and $isSelf !== 1) {
//                $data["deliverType"] = "2";//配送方式 达达配送
//            }
//            //--------------------金额满减运费 start---------------------------
//            if ((float)$data["totalMoney"] >= (float)$shopInfo["deliveryFreeMoney"]) {
//                $data["deliverMoney"] = 0;
//            }
//            //-----------------------金额满减运费end----------------------
//            //在使用运费之前处理好运费一些相关问题
//            if ($isSelf == 1) {//如果为自提订单 将运费重置为0
//                $data["deliverMoney"] = 0;
//            }
////            if(!empty($checkCoupon) && $checkCoupon['data']['couponType']==8 && $data['deliverMoney']>0){
////                $data["deliverMoney"] = 0;
////            }
//            //单加配送劵抵扣--------22020-8-12 21:44:20-----改2020-8-19 16:44:50判断配送费为0时不会展示配送劵---------
//            if ($data['deliverMoney'] == 0) {
//                $psType = 1;//用于判断配送劵是否展示【0:展示|1:不展示】
//            }
//            $couponTypeOn = $submitMod->checkCoupon($userInfo, $totalMoney[$key], $wuCouponId);
//            if (!empty($wuCouponId) && !empty($couponTypeOn) && $couponTypeOn['data']['couponType'] == 8) {
//                $data["deliverMoney"] = 0;
//            }
//            //-----------------单加配送劵抵扣--------2020-8-12 21:44:29-------------------------------------
//            $data["needPay"] = $totalMoney[$key] + $data["deliverMoney"];//需缴费用 加运费-----
//            $data["realTotalMoney"] = (float)$totalMoney[$key] + (float)$data["deliverMoney"] - (float)$checkCoupon['data']['couponMoney'];//实际订单总金额 加运费-----
//            $realTotalAmount += $data['realTotalMoney'];
//            $scoreAmount += $data["scoreMoney"];
//            $useScoreb += $data["useScore"];
//            $totalGoodsAmount += $data["totalMoney"];
//            //将订单商品写入order_goods
//            foreach ($singleData as $singleKey => $singleVal) {
//                //$result[$key] = $cartModel->handleGoodsMemberPrice($result[$key], $userId);//商品金额处理完后最终在shopPrice字段上呈现
//                //限时购----限量购商品需要----不是则返回原来数据
//                $result[$key] = $cartModel->activePriceReplaceShopPrice($result[$key]);//商品金额处理完后最终在shopPrice字段上呈现
//                //2019-6-14 start
//                $getGoodsAttrPrice = getGoodsAttrPrice($userInfo['userId'], $singleVal["goodsAttrId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                if ($singleVal["skuId"] > 0) {
//                    $getGoodsAttrPrice = getGoodsSkuPrice($userInfo['userId'], $singleVal["skuId"], $singleVal["goodsId"], $result[$key][$singleKey]["shopPrice"], $singleVal["goodsCnt"], $singleVal["shopId"]);
//                }
//                $goodsPrice = $getGoodsAttrPrice["goodsPrice"];
//                //todo
//                if ($userInfo['expireTime'] > date('Y-m-d H:i:s')) {//是会员
//                    $goodsInfo = $singleVal;
//                    $systemSkuSpec = $systemTab->where(['skuId' => $singleVal["skuId"]])->find();
//                    if ($systemSkuSpec && (int)$systemSkuSpec['skuMemberPrice'] > 0) {
//                        $goodsInfo['memberPrice'] = $systemSkuSpec['skuMemberPrice'];
//                    }
//                    //如果设置了会员价，则使用会员价，否则使用会员折扣
//                    if ($goodsInfo['memberPrice'] > 0) {
//                        $goodsPrice = $goodsInfo['memberPrice'];
//                    } else {
//                        if ($GLOBALS["CONFIG"]["userDiscount"] > 0) {//会员折扣
//                            $goodsPrice = $goodsInfo['shopPrice'] * ($GLOBALS["CONFIG"]["userDiscount"] / 100);
//                        }
//                    }
//                    //限量购---限时购---数据替换
//                    if ($goodsInfo['isLimitBuy'] == 1 || $goodsInfo['isFlashSale'] == 1) {
//                        $goodsPrice = $getGoodsAttrPrice["goodsPrice"];
//                    }
//                }
//                $result[$key][$singleKey]["goodsPrice"] = formatAmount($goodsPrice);
//            }
//            $orderData[] = $data;
//        }
//        if ($effectiveCoupons['code'] == 0) {
//            $coupons = $effectiveCoupons['data'];
//            foreach ($coupons as $key => $val) {
//                $coupons[$key]['useStatus'] = 1;
//                if ($totalGoodsAmount < $val['spendMoney']) {
//                    unset($coupons[$key]);//如果没有达到可使用优惠券的消费金额就不返回给前端了
//                }
//            }
//            $coupons = array_values($coupons);
//        }
//        //-----------------------start------2020-8-19 16:11:48修改，添加配送费为0时不会返回数据
//        if ($deliveryCoupons['code'] == 0 && $psType == 0) {
//            $couponsDelivery = $deliveryCoupons['data'];
//            foreach ($couponsDelivery as $key => $val) {
//                $couponsDelivery[$key]['useStatus'] = 1;
//            }
//            $couponsDelivery = array_values($couponsDelivery);
//        }
//        //-----------------------end-----------------------------------------------------------
//        //优惠券详情
//        $couponInfo = [];
//        if ($cuid > 0) {
//            $couponWhere = [];
//            $couponWhere['userId'] = $userId;
//            $couponWhere['couponId'] = $cuid;
//            $couponInfo = $submitMod->getCouponInfo($couponWhere);
//        }
//        $invoiceInfo = $submitMod->getUserInvoiceInfo($userId, $invoiceClient);//发票详情,返回用户当前选择的发票信息
//        //运费券详情------------start---------2020-8-20 14:30:34----------
//        $wuCouponInfo = [];
//        if (!empty($wuCouponId)) {
//            $couponWhere = [];
//            $couponWhere['userId'] = $userId;
//            $couponWhere['couponId'] = $wuCouponId;
//            $wuCouponInfo = $submitMod->getCouponInfo($couponWhere);
//        }
//        //--------------------end----------------------------------------
//        //验证信息结果start
//        //地址验证
//        if (!empty($addressMsg)) {
//            $validator[] = $addressMsg;
//        }
//        //配送范围验证
//        if (!empty($deliveryLatLngMsg)) {
//            $validator[] = $deliveryLatLngMsg;
//        }
//        //配送起步价
//        if (!empty($deliveryStartMoney)) {
//            $validator[] = $deliveryStartMoney;
//        }
//        //优惠券
//        if (!empty($couponMsg)) {
//            $validator[] = $couponMsg;
//        }
//        //配送劵---限制配送费--最终决定
//        if (!empty($wuCouponId)) {
//            $data['deliverMoney'] = 0;
//        }
//        //分单--订单数量
//        if ($convention == 1) {
//            $isConventionCount += 1;
//        }
//        if ($isConvention == 1) {
//            $isConventionCount += 1;
//        }
//        if ($isConvention == 1 && $isConventionCount > 1) {
//            $response['isConventionInfo'] = "您的订单将进行分单,本次将分成:" . $isConventionCount . "笔订单";//分单订单信息
//        } else {
//            $response['isConventionInfo'] = "";
//        }
//        if ($useScoreb > 0) {
//            $scoreAmount = (int)$useScoreb / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
//        }
//        //验证信息结果end
//        $response['goods'] = $result[0];//商品信息
//        $response['couponList'] = $coupons;//可用优惠券信息
//        $response['couponDeliveryList'] = $couponsDelivery;//可用运费券信息
//        $response['couponInfo'] = $couponInfo;//优惠券详情,前端传cuid时,返回优惠券详情
//        $response['wuCouponInfo'] = $wuCouponInfo;//运费券详情,前端传wuCouponId时,返回运费券详情
//        $response['userInvoices'] = $submitMod->getUserInvoices($userId, $shopId);//用户发票信息列表 PS:仅门店支持开具发票时才会返回信息
//        $response['invoiceInfo'] = $invoiceInfo;//返回用户当前选择的发票信息
//        $response['address'] = $addressInfo;//地址信息
//        $response['totalGoodsAmount'] = formatAmount($totalGoodsAmount);//商品总金额
//        $response['realTotalAmount'] = formatAmount($realTotalAmount);//实付金额
//        $response['couponAmount'] = formatAmount($couponAmount);//优惠券抵扣金额
//        $response['deliveryAmount'] = formatAmount($data['deliverMoney']);//配送费
//        $response['balance'] = formatAmount($userInfo['balance']);//用户余额
//        $response['userScore'] = (int)$userInfo['userScore'];//用户积分
//        $response['useScore'] = (int)$useScoreb;//抵扣积分
//        $response['can_use_score'] = $can_use_score;
//        $response['can_use_score_amount'] = formatAmount($can_use_score_amount);
//        $response['scoreAmount'] = formatAmount($scoreAmount);//积分抵扣金额
//        if ($useScoreb > $userInfo['userScore']) {
//            $response['useScore'] = (int)$userInfo['userScore'];
//            $response['scoreAmount'] = $userInfo['userScore'] / ($scoreScoreCashRatio[0] * $scoreScoreCashRatio[1]);
//            $response['scoreAmount'] = formatAmount($response['scoreAmount']);
//        }
//        if ($can_use_score > $userInfo['userScore']) {
//            $response['can_use_score'] = (int)$userInfo['userScore'];
//            $response['can_use_score_amount'] = $userInfo['userScore'] / ($scoreScoreCashRatio[0] * $scoreScoreCashRatio[1]);
//            $response['can_use_score_amount'] = formatAmount($response['can_use_score_amount']);
//        }
//        //非自提加上配送费
//        /*if($isSelf != 1){
//            $response['realTotalAmount'] += $data['deliverMoney'];
//        }*/
//        $response['realTotalAmount'] -= $response['scoreAmount'];
//        $response['realTotalAmount'] = formatAmount($response['realTotalAmount']);//实付金额
////        if ($response['realTotalAmount'] <= 0) {
////            //为了避免前端调用支付失败
////            $response['realTotalAmount'] = 0.01;
////        }
//        //包邮起步价
//        $response['deliveryFreeMoney'] = $shopInfo['deliveryFreeMoney'];
//        $response['shopAddress'] = $shopInfo['shopAddress'];
//        $response['detailAddress'] = '';
//        if (!empty($addressInfo)) {
//            $addressInfo['areaId1Name'] = $this->getAreaName($addressInfo['areaId1']);
//            $addressInfo['areaId2Name'] = $this->getAreaName($addressInfo['areaId2']);
//            $addressInfo['areaId3Name'] = $this->getAreaName($addressInfo['areaId3']);
//            if (handleCity($addressInfo['areaId1Name'])) {
//                $response['detailAddress'] .= $addressInfo['areaId1Name'];
//            }
//            $response['detailAddress'] .= $addressInfo['areaId2Name'] . $addressInfo['areaId3Name'] . $addressInfo['address'];
//        }
//        $response['totalMemberPrice'] = $totalMemberPrice;
//        //店铺营业时间
//        $response['serviceStartTime'] = $shopInfo['serviceStartTime'];
//        $response['serviceEndTime'] = $shopInfo['serviceEndTime'];
//        //店铺配置
//        $shopConfig = M('shop_configs')->where(['shopId' => $shopId])->find();
//        $response['cashOnDelivery'] = $shopConfig['cashOnDelivery'];//开启货到付款【-1：未开启|1：已开启】
//        $response['cashOnDeliveryCoupon'] = $shopConfig['cashOnDeliveryCoupon'];//货到付款支持优惠券【-1：不支持|1：支持】
//        $response['cashOnDeliveryScore'] = $shopConfig['cashOnDeliveryScore'];//货到付款支持积分【-1：不支持|1：支持】
//        $response['cashOnDeliveryMemberCoupon'] = $shopConfig['cashOnDeliveryMemberCoupon'];//货到付款会员券【-1：不支持|1：支持】
//        $response['isInvoice'] = $shopInfo['isInvoice'];//能否开发票(1:能 0:不能)
//        $response['invoiceRemarks'] = $shopInfo['invoiceRemarks'];//发票说明
//        $response['deliveryCostTime'] = $shopInfo['deliveryCostTime'];
//        //新增用户省了多少钱(会员)
//        $response['economyAmount'] = $submitMod->getMemberEconomyAmount($userId);
//        $response['validator'] = $validator;
//        $response['countGoods'] = $countGoods;//购物车商品种类
//        $response['countGoodsCnt'] = $countGoodsCnt;//购物车商品数量
//        //商品延时
//        $response['delayed_msg'] = "";
//        if (count($delayed_arr) > 0) {
//            array_multisort($delayed_arr, SORT_DESC);
//            $response['delayed_msg'] = "本单将延时{$delayed_arr[0]}分钟";
//        }
//        return returnData($response);
//    }

    /**
     * 订单-预提交 PS:适用于多商户
     * 文档链接:https://www.yuque.com/youzhibu/qdmx37/rq7yvm
     * */
    public function preSubmitDSH($param)
    {
        //原有方法重构,变量名称不更改,保持和以前的结构一样,避免影响前端,所以不要用现在的业务纠结之前的传参方式与变量命名
        //PS:现在不再支持多家店铺结算
        $shop_module = new ShopsModule();
        $goods_module = new GoodsModule();
        $users_module = new UsersModule();
        $order_module = new OrdersModule();
        $cart_module = new CartModule();
        $coupon_module = new CouponsModule();
        $configs = $GLOBALS['CONFIG'];
        $shop_param = json_decode($param['shopParam'], true);
        $wu_coupon_id = (int)$param['wuCouponId'];//用户领取的运费券记录id
        $address_id = (int)$param['addressId'];//收货地址id
        $users_id = (int)$param['userId'];
        $is_self = (int)$param['isSelf'];//是否自提(0:非自提 1:自提)
        $use_score = (int)$param['useScore'];//是否使用积分(0:不使用 1:使用)
        $cart_goods = $cart_module->getCartGoodsChecked($users_id);
        $goods_list = $cart_goods['goods_list'];
        $goods_shops = $order_module->getAllOrderShopList($goods_list);
        $validator = array();
        if (count($goods_shops) <= 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择要支付的商品');
        }
        if (count($goods_shops) > 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择一家店铺的商品进行结算');
        }
        $cuid = (int)$shop_param[0]['cuid'];//用户领取的优惠券记录id
        $invoice_client = (int)$shop_param[0]['invoiceClient'];
        $shop_detail = $goods_shops[0];//现在只支持一家店铺结算
        $shop_id = $shop_detail['shopId'];
        //验证收货地址,addressId为0则选择默认地址
        $address_detail = $users_module->getUserAddressDetail($users_id, $address_id);
        $delivery_latlng_error = '';//用于存放门店配送范围的错误信息
        if ($is_self != 1) {//如果是自提订单就不验证收货地址了
            if (empty($address_detail) && empty($address_id)) {
                $address_error = ['address' => 'error', 'msg' => '请添加收货地址'];
            }
            if (empty($address_detail) && !empty($address_id)) {
                $address_error = ['address' => 'error', 'msg' => '请添加正确的收货地址'];
            }
            //验证用户收货地址否在配送范围内
            if ($address_detail) {
                $address_id = (int)$address_detail['addressId'];
                $verrification_distribution = $order_module->verificationShopDistribution($users_id, $address_id, $shop_id);
                if (!$verrification_distribution) {
                    $delivery_latlng_error = ['deliveryLatLng' => 'error', 'msg' => '不在配送范围内'];
                }
            }
        }
        //商城配置中设置为统一配置
        if ($configs['setDeliveryMoney'] == 2) {//现在只支持一家店铺结算,所以统一运费也废弃了
            $shop_detail["deliveryMoney"] = $configs['deliveryMoney'];
            $shop_detail["deliveryFreeMoney"] = $configs['deliveryFreeMoney'];
        }
        //获取用户信息
        $users_detail = $users_module->getUsersDetailById($users_id, '*', 2);
        if (empty($users_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        //处理购物车商品sku数据
        $result = $cart_module->handleCartGoodsSku($goods_list);
//        $result[0] = rankGoodsPrice($result[0]);//商品等级价格处理
        //校验购物车商品是否符合店铺设置的配送起步价
        $verification_shop = $cart_module->verificationDeliveryStartMoney($users_id, $shop_id);
        $delivery_start_money = '';//用于存放店铺起步价的错误信息
        if ($verification_shop['code'] != ExceptionCodeEnum::SUCCESS) {
            $delivery_start_money = ['deliveryLatLng' => 'error', 'msg' => '未达到店铺订单配送起步价'];
        }
        //$totalGoodsAmount = 0;//商品总金额
        $coupon_amount = 0;//优惠券抵扣金额
        $score_amount = 0;//积分可抵扣金额
        $total_member_price = array();//商品会员价总计
        $use_scoreb = 0;//使用积分
        //后加本单可使用积分-start
        $can_use_score = 0;//本单可使用积分
        $can_use_score_amount = 0;//本单可使用积分抵扣金额
        //后加本单可使用积分-end
        //未失效优惠券
        $coupons = array();//可使用的优惠券
        $coupons_delivery = array();//可使用的运费券
        $effective_coupons = $order_module->get_order_can_use_coupons($users_id, $shop_id);//获取可用的优惠券
        //--------------配送劵--------------------
        $delivery_coupons = $order_module->get_order_can_use_coupons($users_id, $shop_id, 2);//获取可用的运费券
        $is_show_delivery_coupon = 1;//用于判断配送劵是否展示【0:不展示|1:展示】
        //----------------------------------------
        $count_goods = 0;//购物车商品种类
        $count_goods_cnt = 0;//购物车商品数量
        $delayed_arr = array();//本单将延时多少分钟
        $total_money = array();//商品总金额,纯粹的商品金额
        $current_shop_goods = $result[0];//当前门店商品数据
        $score_cash_ratio = explode(':', $configs['scoreCashRatio']);//积分金额比例
        $score_cash_ratio0 = 0;
        $score_cash_ratio1 = 0;
        if (is_array($score_cash_ratio) && count($score_cash_ratio) == 2) {
            $score_cash_ratio0 = $score_cash_ratio[0];
            $score_cash_ratio1 = $score_cash_ratio[1];
        }
        $open_score_pay = $configs['isOpenScorePay'];//是否开启了积分支付(1:开启)
        //PS:整个方法就不重构了,只修改这个循环体
        $shop_price_total = array();//店铺价总计
        foreach ($current_shop_goods as $current_key => $current_val) {
            $goods_id = (int)$current_val['goodsId'];
            $sku_id = (int)$current_val['skuId'];
            $goods_attr_id = (int)$current_val['goodsAttrId'];
            $goods_cnt = $current_val['goodsCnt'];
            $goods_detail = $current_val;
            $goods_delayed = (int)$current_val['delayed'];
            if ($goods_delayed > 0) {
                $delayed_arr[] = $goods_delayed;
            }
            $shop_price_total[] = bc_math($current_val['shopPrice'], $goods_cnt, 'bcmul', 2);
            $count_goods += 1;
            $count_goods_cnt += $current_val['goodsCnt'];
            $init_price = $goods_module->replaceGoodsPrice($users_id, $goods_id, $sku_id, $goods_cnt, $current_val);//替换商品价格
            $current_shop_goods[$current_key]['shopPrice'] = formatAmount($init_price);
            $current_shop_goods[$current_key]['goodsPrice'] = formatAmount($init_price);
            $goods_price_total = bc_math($init_price, $goods_cnt, 'bcmul', 2);//该商品金额小计
            if ($open_score_pay == 1) {
                //用户使用积分
                $discount_price = $goods_price_total;
                //获取比例
                $goods_score = (float)$discount_price / (float)$score_cash_ratio1 * (float)$score_cash_ratio0;
                $integral_rate_score = (int)($goods_score * ($goods_detail['integralRate'] / 100));//该商品允许抵扣的积分
                if ($use_score == 1) {
                    $use_scoreb += (int)$integral_rate_score;
                }
                $can_use_score += $integral_rate_score;//用户最多可以抵扣的积分,后面还要计算
            }
            $total_money[$current_key] = $goods_price_total;
            $member_price = $goods_module->getGoodsMemberPrice($goods_id, $sku_id);
            $member_price_total = (float)bc_math($member_price, $goods_cnt, 'bcmul', 2);
            $total_member_price[$current_key] = $member_price_total;
        }
        //根据商品列表信息返回拆单信息-start
        //拆单需求:
        //1.常规商品和非常规商品
        //2.限时活动商品或限量活动商品仅自提
        $split_order_res = $goods_module->getGoodsSplitOrder($goods_list);
        $split_num = $split_order_res['num'];
        $must_self = $split_order_res['must_self'];//是否必须自提(0:非必须 1:必须)
        $can_use_coupon = $split_order_res['can_use_coupon'];//是否可以使用优惠券(0:不允许 1:允许)
        if ($can_use_coupon != 1) {
            //不允许使用优惠券就不要返回数据给前端了
            $effective_coupons['data'] = array();
            $delivery_coupons['data'] = array();
            $cuid = 0;
            $wu_coupon_id = 0;
        }
        //根据商品列表信息返回拆单信息-end
        $total_money = array_sum($total_money);//后面就不要再操作该金额了
        $total_member_price = array_sum($total_member_price);
        $math_total_amount = $total_money;//用于后面的金额计算
        $coupon_detail = array();//优惠券信息
        $use_coupon_max_money = 0;//优惠券最多可抵扣金额
        if (!empty($cuid) && !empty($users_detail)) {
            $user_coupon_detail = $coupon_module->getUserCouponDetail($cuid);
            $coupon_detail = $coupon_module->getCouponDetailById($user_coupon_detail['couponId']);
            if (empty($coupon_detail) || $user_coupon_detail['couponStatus'] != 1) {
                $coupon_detail = array();
                $coupon_error = ['coupon' => 'error', 'msg' => '请选择可用的优惠券'];
            } else {
                $coupon_detail['userCouponId'] = $user_coupon_detail['id'];
                $verification_coupon = $goods_module->getGoodsSplitOrder($goods_list, $cuid);//校验当前优惠券是否可用
                if ($verification_coupon['can_use_coupon'] == 1) {
                    if ($coupon_detail['couponType'] != 8) {
                        $coupon_amount = (float)$coupon_detail['couponMoney'];//优惠券面额
                        $use_coupon_max_money = $verification_coupon['use_coupon_detail']['coupon_use_max_money'];//优惠券最多抵扣金额
                        $coupon_detail['use_coupon_max_money'] = formatAmount($use_coupon_max_money);
//                        $math_total_amount = bc_math($math_total_amount, $coupon_amount, 'bcsub', 2);
                        $math_total_amount = bc_math($math_total_amount, $use_coupon_max_money, 'bcsub', 2);
                    }
                } else {
                    $coupon_error = ['coupon' => 'error', 'msg' => '当前优惠券不可用'];
                }
            }
        }
        if ($can_use_score > 0) {
            //积分可以抵扣的金额
            $can_use_score_amount = (int)$can_use_score / (float)$score_cash_ratio0 * (float)$score_cash_ratio1;
        }
        //是否使用发票
        if ($invoice_client > 0) {
            if (isset($shop_detail['isInvoice']) && $shop_detail['isInvoice'] == 1) {
                $invoiceAmount = $math_total_amount * ((int)$shop_detail['isInvoicePoint'] / 100);
                $math_total_amount = bc_math($math_total_amount, $invoiceAmount, 'bcadd', 2);
            }
        }
        $order_score = getOrderScoreByOrderScoreRate($total_money);//本次订单可以获得的积分
        $deliver_money = $shop_detail['deliveryMoney'];//运费
        if ((float)$total_money >= (float)$shop_detail["deliveryFreeMoney"]) {
            $deliver_money = 0;
        }
        if ($is_self == 1) {
            //自提免运费
            $deliver_money = 0;
        }
        $delivery_coupon_detail = array();//运费券信息
        $use_delivery_coupon_max_money = 0;//运费券最多可抵扣的金额
        if (!empty($wu_coupon_id)) {
            $user_coupon_detail = $coupon_module->getUserCouponDetail($wu_coupon_id);
            $delivery_coupon_detail = $coupon_module->getCouponDetailById($user_coupon_detail['couponId']);
            if (empty($delivery_coupon_detail) || $user_coupon_detail['couponStatus'] != 1) {
                $delivery_coupon_detail = array();
                $delivery_coupon_error = ['delivery_coupon' => 'error', 'msg' => '请选择可用的运费券'];
            } else {
                $delivery_coupon_detail['userCouponId'] = $user_coupon_detail['id'];
                $verification_coupon = $goods_module->getGoodsSplitOrder($goods_list, $wu_coupon_id);//校验当前优惠券是否可用
                if ($verification_coupon['can_use_coupon'] == 1) {
                    if ($delivery_coupon_detail['couponType'] == 8) {
                        $delivery_coupon_money = $delivery_coupon_detail['couponMoney'];//运费券面额
                        $use_delivery_coupon_max_money = $verification_coupon['use_coupon_detail']['coupon_use_max_money'];//运费券最多抵扣金额
                        $last_delivery_money = $deliver_money;//勿动
                        $delivery_coupon_detail['use_delivery_coupon_max_money'] = formatAmount($last_delivery_money);
                        $deliver_money = (float)bc_math($deliver_money, $use_delivery_coupon_max_money, 'bcsub', 2);
//                        $deliver_money = (float)bc_math($deliver_money, $delivery_coupon_money, 'bcsub', 2);
                        if ($deliver_money <= 0) {
                            $use_delivery_coupon_max_money = $last_delivery_money;
                            $deliver_money = 0;
                        }
                    }
                } else {
                    $coupon_error = ['coupon' => 'error', 'msg' => '当前运费券不可用'];
                }
            }
        }
        $invoice_detail = $users_module->getUserInvoiceDetail($users_id, $invoice_client);//用户发票详情
        //运费券详情------------start---------2020-8-20 14:30:34----------
        //--------------------end----------------------------------------
        //验证信息结果start
        //地址验证
        if (!empty($address_error)) {
            $validator[] = $address_error;
        }
        //配送范围验证
        if (!empty($delivery_latlng_error)) {
            $validator[] = $delivery_latlng_error;
        }
        //配送起步价
        if (!empty($delivery_start_money)) {
            $validator[] = $delivery_start_money;
        }
        //优惠券
        if (!empty($coupon_error)) {
            $validator[] = $coupon_error;
        }
        //运费券
        if (!empty($delivery_coupon_error)) {
            $validator[] = $delivery_coupon_error;
        }
        //已欠款额度
        if ((float)$users_detail['quota_arrears'] > 0) {
            $validator[] = array('quota_arrears' => 'error', 'msg' => "账户存在欠款，请先处理已欠款额度");
        }
        if ($must_self == 1) {
            //必须自提的订单归为自提订单,不收取配送费
            $deliver_money = 0;
        }
//-----------------------start------2020-8-19 16:11:48修改，添加配送费为0时不会返回数据
        if ($deliver_money == 0 && empty($wu_coupon_id)) {
            $is_show_delivery_coupon = 0;//用于判断配送劵是否展示【0:不展示|1:展示】
        }
        if ($delivery_coupons['code'] == 0 && $is_show_delivery_coupon == 1) {
            $coupons_delivery = $delivery_coupons['data'];
            foreach ($coupons_delivery as $key => $val) {
                $coupons_delivery[$key]['useStatus'] = 1;
            }
            $coupons_delivery = array_values($coupons_delivery);
        }
        if ($effective_coupons['code'] == 0) {
            $coupons = $effective_coupons['data'];
            foreach ($coupons as $key => $val) {
                $coupons[$key]['useStatus'] = 1;//该字段是以前的,大概是使用状态吧,先保留着吧
            }
            $coupons = array_values($coupons);
        }
//-----------------------end-----------------------------------------------------------
        //分单--订单数量
        if ($split_num > 1) {
            $response['isConventionInfo'] = "您的订单将进行分单,本次将分成{$split_num}笔订单";//分单订单信息
        } else {
            $response['isConventionInfo'] = "";
            if ($must_self == 1) {
                $response['isConventionInfo'] = "当前订单仅限自提";
            }
        }
        if ($use_scoreb > 0) {
            $score_amount = (float)((int)$use_scoreb / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        if ($use_scoreb > $users_detail['userScore']) {
            $use_scoreb = (int)$users_detail['userScore'];
            $score_amount = (float)((int)$use_scoreb / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        if ($can_use_score > $users_detail['userScore']) {
            $can_use_score = (int)$users_detail['userScore'];
            $can_use_score_amount = (float)((int)$can_use_score / (float)$score_cash_ratio0 * (float)$score_cash_ratio1);
        }
        $real_total_amount = (float)bc_math($math_total_amount, $deliver_money, 'bcadd', 2);
        if ((float)$score_amount > 0) {
            $real_total_amount = (float)bc_math($real_total_amount, $score_amount, 'bcsub', 2);
        }
        if ($real_total_amount < 0) {
            $real_total_amount = 0;
        }
        $need_pay = $real_total_amount;
        //验证信息结果end
        $response['must_self'] = $must_self;//是否必须自提(0:不限制 1:限制)
        $response['deliveryTime'] = $shop_detail['deliveryTime'];
        $response['serviceStartTime'] = $shop_detail['serviceStartTime'];
        $response['serviceEndTime'] = $shop_detail['serviceEndTime'];
        //$response['goods'] = $current_shop_goods;//商品信息
        //$response['couponList'] = $coupons;//可用优惠券信息
//        $response['couponDeliveryList'] = $coupons_delivery;//可用运费券信息
        $response['couponsDelivery'] = $coupons_delivery;//可用运费券信息
        //$response['couponInfo'] = $coupon_detail;//优惠券详情,前端传cuid时,返回优惠券详情
        $response['wuCouponInfo'] = $delivery_coupon_detail;//运费券详情,前端传wuCouponId时,返回运费券详情
        $user_invoice_list = $users_module->getUserInvoiceList($users_id);
        if ($shop_detail['isInvoice'] != 1) {
            //店铺不支持开发票就不返回用户发票信息了
            $user_invoice_list = array();
        }
        //$response['userInvoices'] = $user_invoice_list;//用户发票信息列表 PS:仅门店支持开具发票时才会返回信息
        //$response['invoiceInfo'] = $invoice_detail;//返回用户当前选择的发票信息
        $response['address'] = $address_detail;//地址信息
        $response['needPay'] = formatAmount($need_pay);//需付款金额
        $response['orderScore'] = $order_score;
        $response['totalGoodsAmount'] = formatAmount($total_money);//商品总金额
        $response['realTotalAmount'] = formatAmount($real_total_amount);//实付金额
        $response['couponAmount'] = formatAmount($coupon_amount);//优惠券金额
        $response['use_coupon_max_money'] = formatAmount($use_coupon_max_money);//优惠券最多抵扣金额
        $response['couponAmount'] = formatAmount($use_coupon_max_money);//优惠券抵扣金额(最多抵扣可抵扣金额)
        $response['delivery_coupon_money'] = formatAmount($delivery_coupon_money);//运费券面额
        $response['use_delivery_coupon_max_money'] = formatAmount($use_delivery_coupon_max_money);//运费券最多抵扣金额
        $response['deliveryAmount'] = formatAmount($deliver_money);//配送费
        $response['balance'] = formatAmount($users_detail['balance']);//用户余额
        $response['userScore'] = (int)$users_detail['userScore'];//用户积分
        $response['useScore'] = (int)$use_scoreb;//抵扣积分
        $response['can_use_score'] = $can_use_score;
        $response['can_use_score_amount'] = formatAmount($can_use_score_amount);
        $response['scoreAmount'] = formatAmount($score_amount);//积分抵扣金额
        //包邮起步价
        $response['deliveryFreeMoney'] = $shop_detail['deliveryFreeMoney'];
        $response['shopAddress'] = $shop_detail['shopAddress'];
        $response['detailAddress'] = $address_detail['detailAddress'];
        $response['totalMemberPrice'] = formatAmount($total_member_price);
        //店铺营业时间
        $response['serviceStartTime'] = $shop_detail['serviceStartTime'];
        $response['serviceEndTime'] = $shop_detail['serviceEndTime'];
        //店铺配置
        $shopConfig = $shop_module->getShopConfig($shop_id, '*', 2);
        $response['cashOnDelivery'] = $shopConfig['cashOnDelivery'];//开启货到付款【-1：未开启|1：已开启】
        $response['cashOnDeliveryCoupon'] = $shopConfig['cashOnDeliveryCoupon'];//货到付款支持优惠券【-1：不支持|1：支持】
        $response['cashOnDeliveryScore'] = $shopConfig['cashOnDeliveryScore'];//货到付款支持积分【-1：不支持|1：支持】
        $response['cashOnDeliveryMemberCoupon'] = $shopConfig['cashOnDeliveryMemberCoupon'];//货到付款会员券【-1：不支持|1：支持】
        $response['isInvoice'] = $shop_detail['isInvoice'];//能否开发票(1:能 0:不能)
        $response['invoiceRemarks'] = $shop_detail['invoiceRemarks'];//发票说明
        $response['deliveryCostTime'] = $shop_detail['deliveryCostTime'];
        //新增用户省了多少钱(会员)
        $response['economyAmount'] = $cart_module->getMemberEconomyAmount($users_id);
        $response['validator'] = $validator;
        $response['countGoods'] = $count_goods;//购物车商品种类
        $response['countGoodsCnt'] = $count_goods_cnt;//购物车商品数量
        //商品延时
        $response['delayed_msg'] = "";
        if (count($delayed_arr) > 0) {
            array_multisort($delayed_arr, SORT_DESC);
            $response['delayed_msg'] = "本单将延时{$delayed_arr[0]}分钟";
        }
        $return_data = array();//兼容之前的返回数据结构
        $shop_detail['cashOnDelivery'] = $shopConfig['cashOnDelivery'];//开启货到付款【-1：未开启|1：已开启】
        $shop_detail['cashOnDeliveryCoupon'] = $shopConfig['cashOnDeliveryCoupon'];//货到付款支持优惠券【-1：不支持|1：支持】
        $shop_detail['cashOnDeliveryScore'] = $shopConfig['cashOnDeliveryScore'];//货到付款支持积分【-1：不支持|1：支持】
        $shop_detail['cashOnDeliveryMemberCoupon'] = $shopConfig['cashOnDeliveryMemberCoupon'];//货到付款会员券【-1：不支持|1：支持】
        $shop_detail['userInvoices'] = $user_invoice_list;//用户发票列表
        $shop_detail['invoiceInfo'] = $invoice_detail;//用户发票详情
        $shop_detail['coupon'] = $coupons;//可用优惠券列表
        $shop_detail['couponInfo'] = $coupon_detail;//优惠券详情
        $shop_detail['goodsList'] = $current_shop_goods;
        $return_data['shopInfo'] = $shop_detail;
        $return_data['coupon'] = $coupons;
        $shop_price_total = array_sum($shop_price_total);
        $amount_saved = (float)bc_math($shop_price_total, $response['realTotalAmount'], 'bcsub', 2);
        if ($amount_saved < 0) {
            $amount_saved = 0;
        }
        $response['amount_saved'] = formatAmount($amount_saved);//本单节省了多少钱 PS:店铺价总计与实付金额的差
        $response['returnData'][] = $return_data;
        return returnData($response);

    }

    /**
     * 金额格式化
     * @param float $amount PS:金额
     * @param int $num PS:保留位数
     * */
    public function formatAmount($amount, $num = 2)
    {
        return number_format($amount, $num, ".", "");
    }


    /**
     * 统计商品评论数量
     * */
    public function countGoodsAppraises($goodsId)
    {
        $goodsTab = M('goods');
        $goodsAppraises = M('goods_appraises');
        $goodsInfo = $goodsTab->where(['goodsId' => $goodsId, 'goodsFlag' => 1])->find();
        if (!$goodsInfo) {
            return returnData(null, -1, 'error', '非法数据请求');
        }
        $count = 0;
        $countGoodsAppraises = $goodsAppraises->where(['goodsId' => $goodsId])->count();
        if (!is_null($countGoodsAppraises)) {
            $count = $countGoodsAppraises;
        }
        $data = [];
        $data['count'] = (int)$count;
        return returnData($data);
    }

    /**
     * 意见反馈
     * */
    public function submitFeedback($param)
    {
        $tab = M('feedback');
        $save['userId'] = $param['userId'];
        $save['content'] = $param['content'];
        $save['imgs'] = $param['imgs'];
        $save['loginIP'] = get_client_ip();
        $save['addTime'] = date('Y-m-d H:i:s', time());
        $insert = $tab->add($save);
        if (!$insert) {
            return returnData(null, -1, 'error', '提交失败');
        }
        return returnData();
    }

    /**
     * 统一下单
     * */
    public function jsapi($param)
    {
        $param['openId'] = I('openId');
        $param['amount'] = 1;
        $param['notifyUrl'] = WSTDomain() . '/index.php/Home/WxPay/notify';
        $param['orderNo'] = 'niaocms' . time();
        $param['attach'] = '123123';
        $param['payType'] = I('payType');
        $param['dataFrom'] = I('dataFrom');
        $pay = unifiedOrder($param);
        if ($pay['result_code'] !== 'SUCCESS') {
            return returnData(null, -1, 'error', $pay['err_code_des']);
        }
        $pay['orderNo'] = (string)$param['orderNo'];
        return returnData($pay);
    }

    /**
     * 获取购物车商品数量
     * */
    public function getCartGoodsNum($param)
    {
        $userId = (int)$param['userId'];
        $where = [];
        $where['cart.userId'] = $userId;
        $where['goods.goodsFlag'] = 1;
        $where['shops.shopFlag'] = 1;
        if (!empty($param['shopId'])) {
            $where['shops.shopId'] = $param['shopId'];
        }
        $count = M('cart cart')
            ->join('left join wst_goods goods on goods.goodsId=cart.goodsId')
            ->join('left join wst_shops shops on shops.shopId=goods.shopId')
            ->where($where)
            ->sum('cart.goodsCnt');
        $data['count'] = (int)$count;
        return returnData($data);
    }

    /**
     * 获得用户余额流水
     * @param $userId
     */
    public function getUserBalanceList($userId, $page = 1, $pageSize = 10)
    {
        return M('user_balance')->where(['userId' => $userId])->order('createTime desc')->limit(($page - 1) * $pageSize, $pageSize)->select();
    }

    /**
     * 轮询订单状态
     * @param string memberToken
     * @param string orderToken
     * */
    public function pollingOrderStatus($param)
    {
        $tab = M('order_merge');
        $mergeInfo = $tab->where(['orderToken' => $param['orderToken']])->find();
        if (!$mergeInfo) {
            return returnData(null, -1, 'error', '非法数据请求');
        }
        $orderNoStr = $mergeInfo['value'];
        $ordernoArr = explode('A', $orderNoStr);
        $where = [];
        $where['orderNo'] = ['IN', $ordernoArr];
        $orderList = M('orders')->where($where)->select();
        $num = 0;
        $orderScore = 0;//订单积分
        $userScore = M('users')->where(['userId' => $param['userId']])->getField('userScore');//用户现有积分
        foreach ($orderList as $key => $val) {
            $orderScore += $val['orderScore'];
            if ($val['isPay'] == 1) {
                $num += 1;
            }
        }
        $data = [];
        $data['total'] = count($orderList);//订单总数
        $data['paynums'] = $num;//已支付
        $data['userScore'] = (int)$userScore;//现有积分
        $data['orderScore'] = (int)$orderScore;//订单积分
        $data['requireTime'] = $orderList[0]['requireTime'];//要求送达时间
        if ($data['requireTime'] == '0000-00-00 00:00:00') {
            $data['requireTime'] = '';
        }
        return returnData($data);
    }

    /**
     * 根据userId获取用户信息
     * */
    public function getUserInfoById($userId)
    {
        $userId = (int)$userId;
        $userInfo = M('users')->where(['userId' => $userId])->find();
        return (array)$userInfo;
    }

    /**
     * 发票详情
     * @param $userId
     * @return mixed
     */
    public function invoiceDetail($userId)
    {
        $id = (int)I('id', 0);
        $where['id'] = $id;
        $where['userId'] = $userId;
        $res = M('invoice')->where($where)->find();

        return returnData((array)$res);
    }

    /**
     * 领取店铺优惠券[返回店铺可用优惠券列表]
     */
    public function receiveShopCoupon($param)
    {
        $userId = (int)$param['userId'];
        $shopId = (int)$param['shopId'];
        $where = [];
        $where['shopId'] = $shopId;
        $where['couponType'] = 1;
        $where['dataFlag'] = 1;//有效
        $where['type'] = 2;//店铺
        $where["validStartTime"] = array('ELT', date('Y-m-d'));
        $where["validEndTime"] = array('EGT', date('Y-m-d'));
        $couponList = (array)M('coupons')->where($where)->select();
        if (!empty($couponList)) {
            foreach ($couponList as $key => $val) {
                $couponIdArr[] = $val['couponId'];
            }
            $userCouponTab = M('coupons_users');
            $userCouponWhere = [];
            $userCouponWhere['userId'] = $userId;
            $userCouponWhere['couponId'] = ['IN', $couponIdArr];
            $userCouponList = $userCouponTab->where($userCouponWhere)->select();
            if ($userCouponList) {
                foreach ($userCouponList as $key => $val) {
                    foreach ($couponList as $ckey => $cval) {
                        if ($val['couponId'] == $cval['couponId']) {
                            unset($couponList[$ckey]);
                        }
                    }
                }
                $couponList = array_values($couponList);
            }
        }
        $apiRet = returnData((array)$couponList);
        return $apiRet;
    }

    /**
     * 获取openId
     * @param int type 支付场景类型(1:微信小程序|2:公众号)
     * @param string code 前端传过来的code
     * @param string userId 用户id
     * */
    public function getOpenId(int $type, string $code, $userId)
    {
        $appid = '';
        $secret = '';
        $weiResData = S('APP_CACHE_OPENID_' . $type . "_" . $userId);
        if (!empty($weiResData)) {
            //后加,openId设置缓存,针对微信返回openId慢的问题 start
            $weiResData = json_decode($weiResData, true);
            //后加,end
        }
        if (empty($weiResData)) {
            $config = $GLOBALS['CONFIG'];
            $weiResData = [];
            switch ($type) {
                case 1:
                    $appid = $config["xiaoAppid"];
                    $secret = $config["xiaoSecret"];
                    $weiResData = curlRequest("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code", '', false, 1);
                    break;
                case 2:
                    $appid = $config["wxAppId"];
                    $secret = $config["wxAppKey"];
                    $weiResData = curlRequest("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code", '', false, 1);
                    break;
                default:
                    $appid = '';
                    $secret = '';
                    break;
            }
            if ($weiResData) {
                S('APP_CACHE_OPENID_' . $type . "_" . $userId, $weiResData, 3600 * 24 * 30);
                $weiResData = json_decode($weiResData, true);
            }
        }
        return returnData($weiResData);
    }

    /**
     * 获取微信公众号授权地址
     * @param string $redirectUrl 跳转路径
     * @param type PS:后期可以根据业务场景进行扩展
     * */
    public function getWxCode($redirectUrl = '')
    {
        $config = $GLOBALS['CONFIG'];
        $appid = $config["wxAppId"];
        $appsecret = $config["wxAppKey"];
        //url填写可当前访问url
        $redirectUrl = urlencode($redirectUrl);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirectUrl . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        $data['url'] = $url;
        return returnData($data);
    }

    /**
     * 添加用户登录日志
     * loginSrc ,0:商城 1:webapp 2:App 3：小程序
     */
    public function addUsersLoginLog($userId, $loginSrc)
    {
        $m = M('log_user_logins');
        $login_count = $m->where(array('userId' => $userId, 'loginTime' => array('like', date('Y-m-d') . '%')))->count();
        if ($login_count > 0) {
            return returnData();
        }

        $data = array();
        $data["userId"] = $userId;
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $data["loginSrc"] = $loginSrc;
        $data['loginRemark'] = I('loginRemark');
        $m->add($data);

        return returnData();
    }

    /**
     * 减购物车商品数量(依赖商品id和skuId)
     * @param int goodsId 商品id
     * @param int skuId skuId
     * @param int goodsCnt 数量
     * */
    public function subtractToCart($userId, $goodsId, $goodsCnt, $goodsAttrId, $skuId)
    {
        $cartTab = M("cart");
        $where = [];
        $where['userId'] = $userId;
        $where['goodsId'] = $goodsId;
        if (!empty($goodsAttrId)) {
            $where['goodsAttrId'] = $goodsAttrId;
        }
        if (!empty($skuId)) {
            $where['skuId'] = $skuId;
        }
        $cartInfo = $cartTab->where($where)->find();
        if (!$cartInfo) {
            return returnData(null, -1, 'error', '暂无相关数据');
        }
        $updateGoodsCnt = $cartInfo['goodsCnt'] - $goodsCnt;
        if ($updateGoodsCnt <= 0) {
            $cartTab->where(['cartId' => $cartInfo['cartId']])->delete();
            return returnData();
        }
        $wehre = [];
        $wehre["cartId"] = $cartInfo['cartId'];
        $saveData = [];
        $saveData['goodsCnt'] = $updateGoodsCnt;
        $rs = $cartTab->where($wehre)->save($saveData);
        if ($rs !== false) {
            return returnData();
        } else {
            return returnData(null, -1, 'error', '减去失败');
        }
    }

    /**
     * 获取活动页详情
     */
    public function getBannerActivityDetail($activityId)
    {
//         $Model = M('');

//         $sql = <<<Eof
//         SELECT DISTINCT
//             ap.id,
//             ap.shopId,
//             ap.img,
//             ap.activityId,
//             ap.state,
//             ap.title
//         FROM
//             wst_activity_page AS ap
//         INNER JOIN wst_activity_type AS `at` ON ap.id = `at`.activityPageId
//         WHERE
//             `at`.state = 1
//         AND `ap`.activityId = '{$activityId}'
//         and `ap`.state = 1
//         GROUP BY
//             ap.id
// Eof;
//         $data = $Model->query($sql);

        $where['state'] = 1;
        $where['activityId'] = $activityId;
        $data_activity_page = M('activity_page')->where($where)->find();
        if (!$data_activity_page) {
            return [];
        }
        unset($where);
        $where['state'] = 1;
        $where['activityPageId'] = $data_activity_page['id'];
        $data = M('activity_type')->where($where)->select();

        if (empty($data)) {
            $data = [];
        }

        $mod_goods = M('goods');
        foreach ($data as $k => &$v) {
            unset($where);

            //TODO:未处理更多的条件 待处理

            $where['goodsId'] = array('in', $v['goods']);
            $where['goodsStatus'] = 1;
            $where['isSale'] = 1;
            $where['goodsFlag'] = 1;
            $where['goodsStock'] = array('GT', 0);
            $v['goodsList'] = rankGoodsPrice($mod_goods->where($where)->select());
        }
        unset($v);
        $retdata['info'] = $data_activity_page;
        $retdata['list'] = $data;


        return $retdata;
    }

    /**
     * 获取支付方式列表
     */
    public function getPayList()
    {
        $where['enabled'] = 1;
        $data = M('payments')->where($where)->field('payCode,payName,payOrder')->order('payOrder asc')->select();
        return $data;
    }

    /**
     * @param $adCode
     * @return mixed
     * 获取广告列表[标识码]
     */
    public function getAdList($adCode, $shopId)
    {
        $whereInfo = "adlocationCode = '$adCode' ";

        $res = M('mobile_ad_location')->where($whereInfo)->find();
        if (empty($res)) {
            return $res;
        }
        $where = "ma.shopId = $shopId and ma.dataFlag = 1 and mal.adLocationId = " . $res['adLocationId'];
        $data = M('mobile_ad ma')
            ->where($where)
            ->join(" inner join wst_mobile_ad_location mal on mal.adLocationId = ma.adLocationId ")
            ->join(" inner join wst_mobile_ad_type mat on mat.adTypeId = ma.adTypeId ")
            ->field('ma.*,mal.adlocationCode,mal.locationDescribe,mat.adTypeCode,mat.typeDescribe')
            ->select();
        return (array)$data;
    }

    /**
     * 获取用户的拉新记录
     * @param int $userId
     * @param int $page 页码
     * @param int $pageSize 分页条数
     * @return array $data
     * */
    public function getPullNewLogList(int $userId, int $page, int $pageSize)
    {
        $pullNewLogTab = M('pull_new_log log');
        $where = [];
        $where['log.inviterId'] = $userId;
        $where['users.userFlag'] = 1;
        $where['usersToId.userFlag'] = 1;
        $field = 'log.id,users.createTime,log.inviterId as userId,users.userName,users.userPhoto,users.userPhone';
        $field .= ',log.userId as usersToIdUserId,usersToId.userName as usersToIdUserName,usersToId.userPhoto as usersToIdUserPhoto,usersToId.userPhone as usersToIdUserUserPhone';
        $field .= ',invitation.addTime as invitationTime';
        $list = $pullNewLogTab
            ->join("left join wst_users users on users.userId=log.inviterId")
            ->join("left join wst_users usersToId on usersToId.userId=log.userId")
            ->join("left join wst_distribution_invitation invitation on invitation.userId=log.inviterId and usersToId.userPhone=invitation.userPhone")
            ->where($where)
            ->field($field)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->group('log.userId')
            ->order('log.id desc')
            ->select();
        if (empty($list)) {
            $list = [];
        }
        $cwhere = array(
            'log.inviterId' => $userId,
            'usersToId.userFlag' => 1
        );
        $count = $pullNewLogTab
            ->join("left join wst_users usersToId on usersToId.userId=log.userId")
            ->where($cwhere)
            ->count();
        $data['list'] = $list;
        $data['count'] = (int)$count;
        return returnData((array)$data);
    }

    /**
     * 地推收益明细
     * @param int $userId
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     * */
    public function getPullNewAmountLogList(int $userId, int $page, int $pageSize)
    {
        $pullNewAmountTab = M('pull_new_amount_log as log');
        $where = [];
        $where['log.inviterId'] = $userId;
        $field = 'log.id,log.inviterId as userId,users.userName,users.userPhoto,users.userPhone,log.dataType,log.status,log.amount,log.createTime';
        $field .= ',log.userId as usersToIdUserId,usersToId.userName as usersToIdUserName,usersToId.userPhoto as usersToIdUserPhoto,usersToId.userPhone as usersToIdUserUserPhone';
        $data = $pullNewAmountTab
            ->join("left join wst_users users on users.userId=log.inviterId")
            ->join("left join wst_users usersToId on usersToId.userId=log.userId")
            ->where($where)
            ->field($field)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->order('log.id desc')
            ->select();
        return returnData((array)$data);
    }

    /**
     * 用户提交商户申请
     */

    public function submitSettlement(array $params)
    {
        $mod = M("user_settlement");
        //查询是否已经存在？
        if (!empty($params["userId"])) {
            $where['userId'] = $params["userId"];
            if ($mod->where($where)->find()) {
                return returnData(null, -1, 'error', '已经提交过了');
            }
        }


        if (empty($params['shopTel']) || empty($params['name'])) {
            return returnData(null, -1, 'error', '联系电话与姓名不能为空');
        }

        $addData['areaId1Name'] = $params['areaId1Name'];
        $addData['areaId2Name'] = $params['areaId2Name'];
        $addData['areaId3Name'] = $params['areaId3Name'];
        $addData['goodsCatIdName'] = $params['goodsCatIdName'];
        $addData['shopName'] = $params['shopName'];
        $addData['shopAddress'] = $params['shopAddress'];
        $addData['shopTel'] = $params['shopTel'];
        $addData['name'] = $params['name'];
        $addData['BusinessLicenseImg'] = $params['BusinessLicenseImg'];
        $addData['userId'] = $params['userId'];
        $addData['addTime'] = date("Y-m-d H:i:s");

        if ($mod->add($addData)) {
            return returnData(true);
        } else {
            return returnData(null, -1, 'error', '提交失败');
        }


    }

    //判断用户是否已经申请店铺了 true已注册 false未注册
    public function isRegSubmitSettlement($userId)
    {
        $mod = M("user_settlement");
        //查询是否已经存在？
        $where['userId'] = $userId;
        if ($mod->where($where)->find()) {
            return returnData(true);
        } else {
            return returnData(false);
        }
    }


    /**
     * 获取公告列表
     * @param int $shopId
     * @param int $page 页码
     * @param int $pageSize 分页条数,默认15条
     * */
    public function getAnnouncementList(int $shopId, int $page, int $pageSize)
    {
        $where = " dataFlag = 1 ";
        if (!empty($shopId)) {
            $where .= " and shopId={$shopId} ";
        }
        $field = 'id,title,content,createTime ';
        $model = M('announcement');
        $data = $model
            ->where($where)
            ->field($field)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->order('id desc')
            ->select();
        if (empty($data)) {
            $data = [];
        }
        return returnData((array)$data);
    }

    /**
     * 获取公告详情
     * @param int $id 公告id
     * */
    public function getAnnouncementDetail(int $id)
    {
        $where = " dataFlag = 1 and id={$id} ";
        $field = 'id,title,content,createTime ';
        $model = M('announcement');
        $data = $model
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            $data = [];
        }
        return returnData((array)$data);
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取时间分类与时间点
     */
    public function getdeliveryTime($shopId)
    {
        $where = array();
        if (!empty($shopId)) {
            $shops_module = new ShopsModule();
            $where['shopId'] = $shopId;
            $field = 'deliveryCostTime,serviceStartTime,serviceEndTime';
            $shop_detail = $shops_module->getShopsInfoById($shopId, $field, 2);
            $deliveryCostTime = (int)$shop_detail['deliveryCostTime'];
            $serviceStartTime = strtotime(str_replace('.', ':', $shop_detail['serviceStartTime']));
            $serviceEndTime = strtotime(str_replace('.', ':', $shop_detail['serviceEndTime']));
        } else {
            $deliveryCostTime = 60;
        }
        if ($deliveryCostTime <= 0) {
            $deliveryCostTime = 60;
        }
        $delivery_time_type_data = M("delivery_time_type")->where($where)->order("sort asc")->select();
        $delivery_time_mod_data = M("delivery_time")->where($where)->order("sort asc")->select();
        foreach ($delivery_time_type_data as $k => $v) {
            $timeLists = [];
            foreach ($delivery_time_mod_data as $key => $val) {
                if ($v["id"] == $val["deliveryTimeTypeId"]) {
                    $hour = date('H:i', strtotime("+{$deliveryCostTime} min"));
                    if ($v['number'] == 0 && $val['timeEnd'] < $hour) {//去除今天已过的时间区间
                        continue;
                    }
                    $val['timeInterval'] = $val['timeStart'] . '-' . $val['timeEnd'];
                    $val['requireTime'] = date('Y-m-d', strtotime("+{$v['number']} day")) . " {$val['timeEnd']}:00";//期望送达最晚时间
                    $timeLists[] = $val;
                }
            }
            $delivery_time_type_data[$k]['dateTime'] = date('m月d日', strtotime("+{$v['number']} day"));
            $timeStart = date('H:i');
            if ($v['number'] == 0 && strtotime($timeStart) >= $serviceStartTime && strtotime($timeStart) <= $serviceEndTime) {
                //如果配送分类是当天则在该时间分类下push一个当前时间+店铺平均配送时间的数组放在最前面
                $timeEnd = date('H:i', strtotime($timeStart) + ($deliveryCostTime * 60));
                $dateTime = date('Y-m-d H:i:s');
                if (strtotime($timeEnd) < $serviceEndTime) {//如果不在营业时间或者超出营业时间就不再拼接时间了
                    $current_time_detail = array(//格式和其他时间段保持一致
                        'id' => "-1",
                        'timeStart' => "{$timeStart}",
                        'timeEnd' => "{$timeEnd}",
                        'shopId' => "{$shopId}",
                        'sort' => "0",
                        'deliveryTimeTypeId' => "{$v['id']}",
                        'addTime' => "{$dateTime}",
                        'timeInterval' => "{$timeStart}-{$timeEnd}",
                        'requireTime' => date('Y-m-d', strtotime("+{$v['number']} day")) . " {$timeEnd}:00"//期望送达最晚时间
                    );
                    if (!empty($timeLists)) {
                        //后台没有可用的时间段就没必要加这个了,会影响打印订单,
                        array_unshift($timeLists, $current_time_detail);
                    }
                }
            }
            $delivery_time_type_data[$k]['timeLists'] = $timeLists;
            if (empty($timeLists)) {//去除没有设置时间段的时间分类
                unset($delivery_time_type_data[$k]);
            }
        }
        $data = (array)array_values($delivery_time_type_data);
        return returnData($data);
    }

    /**
     * 获取返现规则
     */
    public function getRechargeConfig()
    {
        $where = " dataFlag = 1 ";
        $field = 'id,minAmount,maxAmount,returnAmount ';
        $model = M('recharge_config');
        $data = $model
            ->where($where)
            ->field($field)
            ->order('id desc')
            ->select();
        if (empty($data)) {
            $data = [];
        }
        return returnData((array)$data);
    }

    /**
     * 编辑购物车商品数量
     * @param int $userId 用户id
     * @param int $cartId 购物车cartId
     * @param float $goodsCnt 变更数量
     * @return array
     * */
    public function inputCartGoodsNum(int $userId, int $cartId, float $goodsCnt)
    {
        if ($goodsCnt < 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '购买数量或重量必须大于0');
        }
        $cart_module = new CartModule();
        $cartInfo = $cart_module->getCartDetailById($cartId);
        if (empty($cartInfo)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无相关数据');
        }
        $goodsId = $cartInfo['goodsId'];
        $skuId = $cartInfo['skuId'];
        $verificationCartGoods = $cart_module->verificationCartGoodsStatus($userId, $goodsId, $goodsCnt, $skuId, 3);//验证购物车商品的有效性
        if ($verificationCartGoods['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationCartGoods;
        }
        $cartInfo = $verificationCartGoods['data']['cartInfo'];
        if (empty($cartInfo['cartId'])) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败，购物车不存在当前商品');
        }
        $goodsCount = $cartInfo['goodsCount'];
        $verificationStock = $cart_module->verificationCartGoodsStock($goodsId, $goodsCount, $skuId);
        if ($verificationStock['code'] != ExceptionCodeEnum::SUCCESS) {
            return $verificationStock;
        }
        $cart_data = array(
            'cartId' => $cartInfo['cartId'],
            'goodsCnt' => $cartInfo['goodsCount']
        );
        $cart_res = $cart_module->saveCart($cart_data);
        if ($cart_res) {
            $res = array();
            $res['count'] = $cart_module->getCartGoodsCnt($userId);//统计购物车现在的数量
            return returnData($res);
        } else {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '增加失败');
        }
    }

    /**
     * 获取直播/短视频列表
     * @param array $params <p>
     * int shopId 店铺id,不传为多商户否则为前置仓
     * array type 直播类型【1:小程序直播|2:系统原生直播|3:第三方推流直播|4:短视频】
     * string keywords 产品/店铺/标题
     * int goodsCatId3 商城三级分类id
     * int live_status 直播状态【1:即将开始|2:正在直播|3:已结束】
     * int liveSort 排序【1：最新|2：最早】
     * </p>
     * */
    public function getLiveplayList(array $params)
    {
        $shopId = $params['shopId'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = ' liveplay.dataFlag=1 and liveplay.liveSale=1 ';
        $where .= ' and shop.shopFlag=1 and shop.shopStatus=1 and shop.shopAtive=1 ';
        if (empty($params['live_status'])) {
            $where .= " and liveplay.live_status IN(1,2,3) ";
        }
        if (!empty($params['type'])) {
            $typeStr = implode(',', $params['type']);
            $where .= " and liveplay.type IN({$typeStr}) ";
        }
        if (!empty($params['keywords'])) {
            $where .= " and ((liveplay.name like '%{$params['keywords']}%') or (shop.shopName like '%{$params['keywords']}%') or (goods.goodsName like '%{$params['keywords']}%')) ";
        }
        $whereFind = [];
        $whereFind['liveplay.shopId'] = function () use ($params) {
            if (empty($params['shopId'])) {
                return null;
            }
            return ['=', "{$params['shopId']}", 'and'];
        };
        $whereFind['liveplay.goodsCatId3'] = function () use ($params) {
            if (empty($params['goodsCatId3'])) {
                return null;
            }
            return ['=', "{$params['goodsCatId3']}", 'and'];
        };
        $whereFind['liveplay.live_status'] = function () use ($params) {
            if (empty($params['live_status'])) {
                return null;
            }
            return ['=', "{$params['live_status']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        if ($shopId > 0) {
            //前置仓
            $sort = ' liveplay.shopSort desc ';
        } else {
            //多商户
            $sort = ' liveplay.storeSort desc ';
        }
        if ($params['liveSort'] == 1) {
            $sort = ' liveplay.createTime desc ';
        } elseif ($params['liveSort'] == 2) {
            $sort = ' liveplay.createTime asc ';
        }
        $data = M('liveplay liveplay')
            ->join('left join wst_shops shop on shop.shopId=liveplay.shopId')
            ->join('left join wst_liveplay_goods_relation lp_relation on lp_relation.liveplayId=liveplay.liveplayId')
            ->join('left join wst_goods goods on goods.goodsId=lp_relation.goodsId')
            ->where($whereInfo)
            ->field('liveplay.*')
            ->order($sort)
            ->group('liveplay.liveplayId')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        return returnData((array)$data);
    }

    /**
     * 获取直播/短视频详情
     * @param int $liveplayId 直播/短视频id
     * */
    public function getLiveplayDetail(int $liveplayId)
    {
        $where = " liveplayId={$liveplayId} ";
        $field = 'liveplayId,roomId,name,type,goodsCatId1,goodsCatId2,goodsCatId3,liveImgUrl,coverImgUrl,shareImgUrl,videoUrl,pageView,likenumInt,commentNumber,startDate,endDate,anchorName,anchorWechat,closeComment,live_status,liveSale,shopSort,createTime';
        $data = M('liveplay')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            return returnData([]);
        }
        $where = " lp_relation.liveplayId={$liveplayId} and lp_relation.dataFlag=1 ";
        $field = " lp_goods.status,lp_goods.createTime";
        $field .= ",goods.goodsId,goods.goodsName,goods.goodsImg,goods.goodsThums,goods.shopId,goods.marketPrice,goods.shopPrice,goods.saleCount,goods.goodsUnit,goods.goodsSpec,goods.goodsStock,goods.markIcon,goods.isDistribution,goods.firstDistribution,goods.SecondaryDistribution,goods.memberPrice,goods.isNewPeople";
        $field .= ',lp_relation.goodsSort';
        $goods = M('liveplay_goods_relation lp_relation')
            ->join("left join wst_liveplay_goods lp_goods on lp_goods.goodsId=lp_relation.goodsId")
            ->join("left join wst_goods goods on goods.goodsId=lp_goods.goodsId")
            ->where($where)
            ->field($field)
            ->group("goods.goodsId")
            ->order('lp_relation.goodsSort desc')
            ->select();
        $data['goods'] = (array)rankGoodsPrice($goods);
        M('liveplay')->where(['liveplayId' => $liveplayId])->setInc('pageView');
        return returnData($data);
    }

    /**
     * 获取商城三级分类列表
     * */
    public function getPlatformGoodsCatList()
    {
        $goodsCatTab = M('goods_cats');
        $where = [];
        $where['catFlag'] = 1;
        $where['isShow'] = 1;
        $data = $goodsCatTab->where($where)->order('catSort asc')->select();
        $goodsCat1 = [];
        foreach ($data as &$value) {
            if ($value['parentId'] == 0) {
                $value['child'] = [];
                $goodsCat1[] = $value;
            }
        }
        unset($value);
        foreach ($goodsCat1 as &$value) {
            foreach ($data as $allval) {
                if ($allval['parentId'] == $value['catId']) {
                    $value['child'][] = $allval;
                }
            }
        }
        unset($value);
        foreach ($goodsCat1 as &$value) {
            foreach ($value['child'] as $childval) {
                $childval['child'] = [];
                foreach ($data as $allval) {
                    if ($allval['parentId'] == $childval['catId']) {
                        $childval['child'][] = $allval;
                    }
                }
                $value['child'] = $childval;
            }
        }
        unset($value);
        return returnData($goodsCat1);
    }

    /**
     * 售后日志
     * @param int $complainId 申诉单id
     * @return array $data
     * */
    public function getOrderComplainsLog(int $complainId)
    {
        $table_action_log_tab = M('table_action_log');
        $where = [];
        $where['tableName'] = 'wst_order_complains';
        $where['dataId'] = $complainId;
        $data = $table_action_log_tab
            ->where($where)
            ->order('logId desc')
            ->select();
        foreach ($data as &$item) {
            $item['statusName'] = '';
            if ($item['fieldName'] == 'complainStatus' && $item['fieldValue'] == -1) {
                $item['statusName'] = '商家已拒绝';
            }
            if ($item['fieldName'] == 'complainStatus' && $item['fieldValue'] == 0) {
                $item['statusName'] = '待退货';
            }
            if ($item['fieldName'] == 'complainStatus' && $item['fieldValue'] == 1) {
                $item['statusName'] = '退货中';
            }
            if ($item['fieldName'] == 'complainStatus' && $item['fieldValue'] == 2) {
                $item['statusName'] = '退货完成';
            }
            if ($item['fieldName'] == 'returnAmountStatus' && $item['fieldValue'] == -1) {
                $item['statusName'] = '商家拒绝退款';
            }
            if ($item['fieldName'] == 'returnAmountStatus' && $item['fieldValue'] == 0) {
                $item['statusName'] = '待退款';
            }
            if ($item['fieldName'] == 'returnAmountStatus' && $item['fieldValue'] == 1) {
                $item['statusName'] = '退款完成';
            }
        }
        unset($item);
        return (array)$data;
    }

    /**
     * 验证登陆名是否已经存在
     * @param string $loginName 登陆账号
     * @return array
     * */
    public function verificationLoginName(string $loginName)
    {
        $response = LogicResponse::getInstance();
        $users_service_module = new UsersServiceModule();
        $where = array(
            'loginName' => $loginName
        );
        $result = $users_service_module->getUsersDetailByWhere($where);
        $data = array(
            'is_exist' => false//is_exist true:已存在 false:不存在
        );
        if ($result['code'] == ExceptionCodeEnum::SUCCESS) {
            $data['is_exist'] = true;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 用户账号密码登陆
     * @param array $params <p>
     * string mobileNumber 手机号
     * string loginPwd 密码
     * string unionid 微信用户唯一id
     * string headimgurl 用户微信头像
     * string openid 用户openid
     * string nickname 昵称
     * string smsCode 验证码
     * </p>
     * @return array
     * */
    public function userLogin(array $params)
    {
        //接收的参数都是之前已有的,就不修改了
        $response = LogicResponse::getInstance();
        $mobile_number = (string)$params['mobileNumber'];
        $get_unionid = (string)$params['unionid'];
        $get_headimgurl = (string)$params['headimgurl'];
        $get_openid = (string)$params['openid'];
        $get_nickname = (string)$params['nickname'];
        $get_smsCode = (string)$params['smsCode'];
        $user_model = new \V3\Model\UserModel();
        if (!empty($get_unionid)) {
            //已绑定过微信直接登陆
            $userData = $user_model->isUnionid($get_unionid);
            if (!empty($userData['userId'])) {
                $user_model->UserLogInfo($userData['userId']);//记录登陆日志
                $login_info = $user_model->login($userData['userId']);//完成登陆
                if (empty($login_info)) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('登陆失败')->toArray();
                }
                return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($login_info)->setMsg('登陆成功')->toArray();
            }
        }
        if (empty($mobile_number) || empty($get_smsCode)) {
            return $response->setCode(UsersEnum::BIND_MOBILE)->setMsg('请绑定手机号')->toArray();
        }
        $sms_module = new LogSmsModule();
        $sms_info = $sms_module->getLogSmsInfoByWhere(
            array(
                'smsPhoneNumber' => $mobile_number,
                'smsCode' => $get_smsCode,
            )
        );
        if ($sms_info['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确')->toArray();
        }
        $sms_info_data = $sms_info['data'];
        if (((time() - strtotime($sms_info_data['createTime'])) > SmsEnum::OUTTIME)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确或已超时')->toArray();
        }
        $users_info = $user_model->isUserLoginName($mobile_number);
        if (empty($users_info)) {
            //注册用户
            $reg_data = array();
            $reg_data['loginName'] = $mobile_number;
            $reg_data['userName'] = $get_nickname;
            $reg_data['userPhoto'] = $get_headimgurl;
            $reg_data['openId'] = $get_openid;
            $reg_data['WxUnionid'] = $get_unionid;
            $reg_data['userPhone'] = $mobile_number;
            $userId = (int)$user_model->reg($reg_data);
            if (empty($userId)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('绑定手机号失败')->toArray();
            }
            //注册成功后
            $user_model->UserPushInfo($userId, 4);//注册成功推送
            $user_model->UserLogInfo($userId);//记录登陆日志
            $user_model->InvitationFriend($userId);//邀请好友奖励
            $user_model->FunNewPeopleGift($userId);//新人专享大礼
            $user_model->InvitationFriendSetmeal($userId); //邀请好友开通会员送券
            $user_model->distributionRelation($userId);//写入分销与地推关系
        } else {
            //更新用户信息
            $userId = $users_info['userId'];
            $users_service_module = new UsersServiceModule();
            $save = array(
                'userName' => $get_nickname,
                'userPhoto' => $get_headimgurl,
                'openId' => $get_openid,
                'userFrom' => 3,
                'WxUnionid' => $get_unionid,
                'userPhone' => $mobile_number,
            );
            $update_res = $users_service_module->updateUsersInfo($userId, $save);
            if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('绑定手机号失败')->toArray();
            }
        }
        $user_model->UserLogInfo($userId);//记录登陆日志
        $data = $user_model->login($userId);
        $sms_module->destructionSms($sms_info_data['smsId']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 登陆-账号密码登陆
     * @param string $account 账号
     * @param string $password 密码
     * @return array
     * */
    public function passwordLogin(string $account, string $password)
    {
        $users_module = new UsersModule();
        $account_where = array(
            'loginName' => $account
        );
        $account_detail = $users_module->getUsersDetailByWhere($account_where, 'userId,loginName,loginSecret,loginPwd', 2);
        if (empty($account_detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '账号或密码错误');
        }
        $handle_password = md5($password . $account_detail['loginSecret']);
        if ($handle_password != $account_detail['loginPwd']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '账号密码不匹配');
        }

        $data = (new \V3\Model\UserModel())->login($account_detail['userId']);
        return returnData($data);
    }

    /**
     * 貌似是微信登陆 PS:仅为userLogin方法服务
     * @param array $params <p>
     * string mobileNumber 手机号
     * string loginPwd 密码
     * string unionid 微信用户唯一id
     * string headimgurl 用户微信头像
     * string openid 用户openid
     * string nickname 昵称
     * string smsCode 验证码
     * int InvitationID 邀请人id
     * </p>
     * @return array
     * */
    static function wxLogin(array $params)
    {
        //PS:该逻辑原有,目前未使用,就不改了
        $response = LogicResponse::getInstance();
        $log_service_module = new LogServiceModule();
        $wei_res_data['unionid'] = (string)$params['unionid'];
        $wei_res_data['openid'] = (string)$params['openid'];
        $user_name = $params['nickname'];
        $user_photo = $params['headimgurl'];
        $user_phone = $params['mobileNumber'];//用户手机号
        $user_login_pwd = $params['loginPwd'];//用户密码 未加密
        $user_sms_code = $params['smsCode'];//短信验证码
        if (empty($wei_res_data['unionid'])) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('unionid为空')->toArray();
        }
        $users_service_module = new UsersServiceModule();
        //检查该微信是否已绑定过账号
        $users_result = $users_service_module->getUsersDetailByWhere(
            array(
                'WxUnionid' => $wei_res_data['unionid'],
                'loginName' => array('NEQ', ''),
            )
        );
        $users_model = new UsersModel();
        if ($users_result['code'] != ExceptionCodeEnum::SUCCESS) {
            //如果为空即为注册
            if (empty($user_phone) || empty($user_login_pwd) || empty($user_sms_code)) {
                $users_enum = new UsersEnum();
                $users_state_arr = $users_enum->getUsersState();
                return $response->setCode($users_enum::BIND_MOBILE)->setMsg($users_state_arr[$users_enum::BIND_MOBILE])->toArray();
            }
            //校验短信验证码
            $sms_result = $log_service_module->getLogSmsInfo(
                array(
                    'smsPhoneNumber' => $user_phone,
                    'smsCode' => $user_sms_code,
                )
            );
            if ($sms_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码错误')->toArray();
            }
            $sms_data = $sms_result['data'];
            if (((time() - strtotime($sms_data['createTime'])) > 1800)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('验证码不正确或已超时')->toArray();
            }
            //判断当前手机号是否已被注册 如果已被注册 则进行直接绑定微信
            $users_result = $users_service_module->getUsersDetailByWhere(
                array(
                    'loginName' => $user_phone
                )
            );
            if ($users_result['code'] == ExceptionCodeEnum::SUCCESS) {
                //更新用户信息
                $save = array();
                // $save['userName'] = $user_name;
                $save['userPhoto'] = $user_photo;
                $save['openId'] = $wei_res_data['openid'];
                $save['userFrom'] = 3;
                $save['WxUnionid'] = $wei_res_data['unionid'];
                $save['userPhone'] = $user_phone;
                $where = array();
                $where['userId'] = $users_result['data']['userId'];
                $update_res = $users_model->where($where)->save($save);
                if ($update_res === false) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('绑定微信失败')->toArray();
                }
            } else {
                //新增用户
                $save = array();
                $save['loginName'] = $user_phone;
                $save['loginSecret'] = rand(1000, 9999);
                $save['loginPwd'] = md5($user_login_pwd . $save['loginSecret']);
                $save['userName'] = $user_name;
                $save['userPhoto'] = $user_phone;
                $save['createTime'] = date('Y-m-d H:i:s');
                $save['openId'] = $wei_res_data['openid'];
                $save['userFrom'] = 3;
                $save['WxUnionid'] = $wei_res_data['unionid'];
                $save['userPhone'] = $user_phone;
                $insert_user_id = $users_model->add($save);
                //判断是否是被邀请
                $Invitation = (int)$params['InvitationID'];//原始邀请人的userId
                if (!empty($Invitation)) {
                    self::InvitationFriend($Invitation, $insert_user_id);
                } else {
                    $where = array();
                    $where['inviteePhone'] = $user_phone;
                    $record_result = $users_service_module->getRecordInfoByWhere($where);
                    if ($record_result['code'] != ExceptionCodeEnum::SUCCESS) {
                        self::InvitationFriend($record_result['data']['inviterId'], $insert_user_id);
                    }
                }
                //新人专享大礼
                $isNewPeopleGift = self::FunNewPeopleGift($insert_user_id);
            }
        }
        //登陆生成token
        $where = array();
        $where['WxUnionid'] = $wei_res_data['unionid'];
        $users_result = $users_service_module->getUsersDetailByWhere($where);
        if ($users_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户被禁用，或者不存在')->toArray();
        }
        $users_data = $users_result['data'];
        //判断新人专享获得的礼包是否为空
        if (!empty($isNewPeopleGift)) {
            $users_data['isNewPeopleGift'] = $isNewPeopleGift;
        }
        //记录登录日志
        $log_service_module->addLogUserLogins(
            array(
                'userId' => $users_data['userId'],
                'loginSrc' => 3,
            )
        );
        $save = array();
        $save['lastIP'] = get_client_ip();
        $save['lastTime'] = date('Y-m-d H:i:s');
        $where = array();
        $where['userId'] = $users_data['userId'];
        $users_model->where($where)->save($save);
        $memberToken = md5(uniqid('', true) . $users_data['userId'] . $users_data['loginName'] . (string)microtime());
        if (!userTokenAdd($memberToken, $users_data)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('登陆失败')->toArray();
        }
        $log_service_module->destructionSms((int)$sms_data['smsId']);//销毁短信验证码
        $users_data['memberToken'] = $memberToken;
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($users_data)->toArray();
    }

    /**
     * 用户还款-发起还款
     * @param array $params
     * @return array
     * */
    public function userRepayment($params)
    {
        $userId = (int)$params['userId'];
        $dataValue = json_decode($params['dataValue'], true);//业务参数
        $money = (float)$dataValue['money'];//还款金额
        $payType = (int)$params['payType'];//支付方式(1:支付宝|2:微信|3:余额(注:目前只支持用户还款))
        $dataFrom = (int)$params['dataFrom'];//来源(3:app 4:小程序 )
        $openId = (string)$params['openId'];//用户openId,小程序微信支付必填
        if ($money <= 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款金额必须大于0');
        }
        $userModule = new UsersModule();
        $userDetail = $userModule->getUsersDetailById($userId, 'userId,userName,quota_arrears', 2);
        if (empty($userDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
        }
        $quotaArrears = (float)$userDetail['quota_arrears'];
        if ($quotaArrears <= 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无欠款金额');
        }
        if ($money > $quotaArrears) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "还款金额不得大于欠款金额{$quotaArrears}元");
        }
        if (!in_array($payType, array(1, 2, 3))) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的还款方式');
        }
        if (!in_array($dataFrom, array(3, 4))) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择正确的支付来源');
        }
        if ($payType == 2 && empty($openId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少必填参数-openId');
        }
        //生成统一下单记录
        $orderModule = new OrdersModule();
        $autoId = $orderModule->getOrderAutoId();
        $billNo = $autoId . "" . (fmod($autoId, 7));//以前就是这样的方式
        $mergeData = array(
            'value' => $billNo,
            'realTotalMoney' => $money,
        );
        $mergeRes = $orderModule->addOrderMerge($mergeData);
        if (!$mergeRes) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '还款失败-订单标识创建失败');
        }
        $orderToken = md5($billNo);
        $attach = array();//附加参数,用于回调
        $attach['userId'] = $userId;
        $attach['orderToken'] = $orderToken;
        $attach['money'] = $money;
        $attach['amount'] = $money;
        $attach['payType'] = $payType;
        $attach['dataFrom'] = $dataFrom;
        $attach['openId'] = $openId;
        $params['requestJson'] = json_encode($attach);
        $params['dataType'] = 6;
        $sign = $orderModule->createNotifyLog($params);
        if (empty($sign)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '还款失败-支付请求记录创建失败');
        }
        $payModule = new PayModule();
        if ($payType == 1) {//支付宝支付
            $payRes = $payModule->aliPay($sign);
        }
        if ($payType == 2) {//微信
            $payRes = $payModule->wxPay($sign);
        }
        if ($payType == 3) {//余额
            $repaymentModule = new RepaymentModule();
            $payRes = $repaymentModule->userRepaymentBalance($sign);
        }
        if ($payRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '支付失败！', $payRes['info']);
        }
        $resData = $payRes['data'];
        $resData['money'] = formatAmount($money);
        $resData['notifySign'] = $sign;
        $resData['timestamp'] = time();//时间戳
//        if ($payType == 1) {//支付宝
//
//        } elseif ($payType == 2) {//微信
//            $result = $repaymentModule->userRepaymentWxpay($sign);
//        } else {//余额
//            $result = $repaymentModule->userRepaymentBalance($sign);
//        }
//        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $result['msg']);
//        }

        return returnData($resData, 0, '还款成功');
    }

    /**
     * 用户还款-发起还款
     * @param array $params
     * -int userId 用户id
     * -float money 还款金额
     * -payType 还款方式(2:微信 3:余额)
     * -payFrom 来源(1:小程序 2:APP)
     * -openId 用户openId,小程序微信支付必填
     * @return array
     * */
//    public function userRepayment(array $params)
//    {
//        $userId = $params['userId'];
//        $money = $params['money'];//还款金额
//        $payType = $params['payType'];//还款方式(2:微信 3:余额)
//        $payFrom = $params['payFrom'];//来源(1:小程序 2:APP)
//        $openId = $params['openId'];//用户openId,小程序微信支付必填
//        $userModule = new UsersModule();
//        $userDetail = $userModule->getUsersDetailById($userId, 'userId,userName,quota_arrears', 2);
//        if (empty($userDetail)) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户信息有误');
//        }
//        $quotaArrears = (float)$userDetail['quota_arrears'];
//        if ($quotaArrears <= 0) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无欠款金额');
//        }
//        if ($money > $quotaArrears) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "还款金额不得大于欠款金额{$quotaArrears}元");
//        }
//        $orderModule = new OrdersModule();
//        $autoId = $orderModule->getOrderAutoId();
//        $billNo = $autoId . "" . (fmod($autoId, 7));//以前就是这样的方式
//        $mergeData = array(
//            'value' => $billNo,
//            'realTotalMoney' => $money,
//        );
//        $mergeRes = $orderModule->addOrderMerge($mergeData);
//        if (!$mergeRes) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '还款失败-订单标识创建失败');
//        }
//        $orderToken = md5($billNo);
//        $attach = array();//附加参数,用于回调
//        $attach['userId'] = $userId;
//        $attach['orderToken'] = $orderToken;
//        $attach['money'] = $money;
//        $attach['pay_type'] = $payType;
//        $attach['pay_from'] = $payFrom;
//        $attach['openId'] = $openId;
//        $params = array();
//        $params['dataType'] = 6;
//        $params['requestJson'] = json_encode($attach);
//        $params['orderToken'] = $orderToken;
//        $sign = $orderModule->createNotifyLog($params);
//        if (empty($sign)) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '还款失败', '还款失败-支付请求记录创建失败');
//        }
//        $repaymentModule = new RepaymentModule();
//        if ($payType == 1) {//支付宝
//
//        } elseif ($payType == 2) {//微信
//            $result = $repaymentModule->userRepaymentWxpay($sign);
//        } else {//余额
//            $result = $repaymentModule->userRepaymentBalance($sign);
//        }
//        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
//            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $result['msg']);
//        }
//        return $result;
//    }

    /**
     * 用户还款-查询还款状态
     * @param string $notifyKey 还款标识
     * @return array
     * */
    public function queryUserRepayment(string $notifyKey)
    {
        $orderModule = new OrdersModule();
        $data = $orderModule->getNotifyLogDetail($notifyKey);
        if (empty($data)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无相关还款记录');
        }
        $status = 0;//还款状态(0:还款中 1:还款成功)
        if ($data['notifyStatus'] == 1) {
            $status = 1;
        }
        $returnData = array(
            'status' => $status
        );
        return returnData($returnData);
    }

    /**
     *  地图-逆地址解析-经纬度转地址
     * @params float $lat 纬度
     * @params float $lng 经度度
     * @return array
     * */
    public function mapLatLngToAddress(float $lat, float $lng)
    {
        $mapModule = new MapModule();
        $result = $mapModule->latlngToAddress($lat, $lng, 1);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '地址解析失败！');
        }
        return returnData($result);
    }

    /**
     *  地图-地址-关键字搜索
     * @params string $keywords 地址检索关键字
     * @params string $cityName 城市名称 例子:上海市
     * @params float $lat 纬度
     * @params float $lng 经度
     * @return array
     * */
    public function mapPlaceByKeywords(string $keywords, string $cityName, $lat, $lng)
    {
        $mapModule = new MapModule();
        $result = $mapModule->mapPlaceByKeywords($keywords, $cityName, $lat, $lng);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '地址解析失败！');
        }
        return returnData($result);
    }
}

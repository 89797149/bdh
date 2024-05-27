<?php
/**
 * 文件描述
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-12
 * Time: 11:05
 */

namespace App\Modules\Map;


use App\Enum\ExceptionCodeEnum;
use App\Models\AreasModel;
use App\Modules\Areas\AreasModule;

class MapModule
{
    /**
     * 地址-逆地址解析-经纬度转地址
     * @param float $lat 纬度
     * @param float $lng 经度
     * @param int $isDeCodeAreaCode 是否解析出本地对应的省市区(0:不需要 1:需要)
     * @return array
     * */
    public function latlngToAddress(float $lat, float $lng, $isDeCodeAreaCode = 0)
    {
        //不太清楚的请看高德地图文档:https://lbs.amap.com/api/webservice/guide/api/georegeo/
        $config = $GLOBALS['CONFIG'];
        $key = $config['gaode_map_key'];
        $url = "https://restapi.amap.com/v3/geocode/regeo?location={$lng},{$lat}&key={$key}&extensions=all";
        $result = curlRequest($url, false, 1, 1);
        $result = json_decode($result, true);
        if ($result['status'] != 1 || empty($result['regeocode']['addressComponent']['adcode'])) {
            return array('code' => ExceptionCodeEnum::FAIL, 'msg' => '地址解析失败');
        }
        if ($isDeCodeAreaCode == 1) {
            $adcode = $result['regeocode']['addressComponent']['adcode'];
            $result['province_id'] = 0;//省id
            $result['province_name'] = '';
            $result['city_id'] = 0;//市id
            $result['city_name'] = '';
            $result['region_id'] = 0;//区id
            $result['region_name'] = 0;
            $area_detail = $this->getAreaDetailByAreaId($adcode);
            if (empty($area_detail)) {
                $result['code'] = ExceptionCodeEnum::FAIL;
                return $result;
            }
            $result['province_id'] = $area_detail['province_id'];
            $result['province_name'] = $area_detail['province_name'];
            $result['city_id'] = $area_detail['city_id'];
            $result['city_name'] = $area_detail['city_name'];
            $result['region_id'] = $area_detail['region_id'];
            $result['region_name'] = $area_detail['region_name'];
            $pois = $result['regeocode']['pois'];
            foreach ($pois as &$row) {
                $row['province_id'] = $area_detail['province_id'];
                $row['province_name'] = $area_detail['province_name'];
                $row['city_id'] = $area_detail['city_id'];
                $row['city_name'] = $area_detail['city_name'];
                $row['region_id'] = $area_detail['region_id'];
                $row['region_name'] = $area_detail['region_name'];
                if (!empty($row["location"])) {
                    $locationArr = explode(",", $row["location"]);
                    $row["lat"] = $locationArr[1];
                    $row["lng"] = $locationArr[0];
                }
                if (!empty($lat) and !empty($lng)) {
                    $row['Kilometer'] = getDistanceBetweenPointsNew($row['lat'], $row['lng'], $lat, $lng);
                }
                $result['regeocode']['pois'] = $pois;
            }
            unset($row);
        }
        $result['code'] = ExceptionCodeEnum::SUCCESS;
        return $result;
    }

    /**
     * 地区-地区详情-根据area_id获取
     * @param int $areaId 地区id
     * @return array
     * */
    public function getAreaDetailByAreaId(int $areaId)
    {
        $model = new AreasModel();
        $where = array(
            'areaId' => $areaId,
            'areaFlag' => 1,
        );
        $areaDetial = $model->where($where)->find();
        if (empty($areaDetial)) {
            return array();
        }
        $areaDetial['area_level'] = 1;//等级(1:省 2:市 3:区)
        $areaDetial['province_id'] = $areaId;//省id
        $areaDetial['province_name'] = $areaDetial['areaName'];
        $areaDetial['city_id'] = 0;//市id
        $areaDetial['city_name'] = '';
        $areaDetial['region_id'] = 0;//区id
        $areaDetial['region_name'] = '';
        if ($areaDetial['parentId'] > 0) {
            $where['areaId'] = $areaDetial['parentId'];
            $area2Detial = $model->where($where)->find();
            if (empty($area2Detial)) {
                return $areaDetial;
            }

            $areaDetial['area_level'] = 2;
            $areaDetial['province_id'] = $area2Detial['areaId'];
            $areaDetial['province_name'] = $area2Detial['areaName'];
            $areaDetial['city_id'] = $areaDetial['areaId'];
            $areaDetial['city_name'] = $areaDetial['areaName'];
            if ($area2Detial['parentId'] > 0) {
                $where['areaId'] = $area2Detial['parentId'];
                $area3Detial = $model->where($where)->find();
                if (empty($area3Detial)) {
                    return $areaDetial;
                }
                $areaDetial['area_level'] = 3;
                $areaDetial['province_id'] = $area3Detial['areaId'];
                $areaDetial['province_name'] = $area3Detial['areaName'];
                $areaDetial['city_id'] = $area2Detial['areaId'];
                $areaDetial['city_name'] = $area2Detial['areaName'];
                $areaDetial['region_id'] = $areaDetial['areaId'];
                $areaDetial['region_name'] = $areaDetial['areaName'];
            }
        }
        return $areaDetial;
    }

    /**
     *  地图-地址-关键字搜索
     * @params string $keywords 地址检索关键字
     * @params string $cityName 城市名称 例子:上海市
     * @params float $lat 纬度
     * @params float $lng 经度
     * @return array
     * */
    public function mapPlaceByKeywords(string $keywords, $cityName = "", $lat = 0, $lng = 0)
    {
        //不太清楚的请看高德地图文档:https://lbs.amap.com/api/webservice/guide/api/search
        $config = $GLOBALS['CONFIG'];
        $key = $config['gaode_map_key'];
        $url = "https://restapi.amap.com/v3/place/text?keywords={$keywords}&city={$cityName}&offset=20&page=1&key={$key}&extensions=all";
        $params = array(
            "key" => $key,
            "keywords" => $keywords,
        );
        if (!empty($cityName)) {
            $params["city"] = $cityName;
        }
        $result = curlRequest($url, $params, 0, 1);
        $result = json_decode($result, true);
        if ($result["status"] != 1) {
            return array('code' => ExceptionCodeEnum::FAIL, "msg" => "请求失败");
        }
        if ($result["info"] != "OK") {
            return array('code' => ExceptionCodeEnum::FAIL, "msg" => $result["info"]);
        }
        $pois = $result["pois"];
        foreach ($pois as &$row) {
            $row["lat"] = "";
            $row["lng"] = "";
            if (!empty($row["location"])) {
                $locationArr = explode(",", $row["location"]);
                $row["lat"] = $locationArr[1];
                $row["lng"] = $locationArr[0];
            }
            if (!empty($lat) and !empty($lng)) {
                $row['Kilometer'] = getDistanceBetweenPointsNew($row['lat'], $row['lng'], $lat, $lng);
            }
        }
        $result["pois"] = $pois;
        unset($row);
        $result["code"] = ExceptionCodeEnum::SUCCESS;
        return $result;
    }
}
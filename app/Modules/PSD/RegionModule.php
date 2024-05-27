<?php
/**
 * 配送端-区域
 */

namespace App\Modules\PSD;


use App\Models\BaseModel;
use App\Models\MemberRegionRelationModel;
use App\Models\RegionModel;

class RegionModule extends BaseModel
{
    /**
     * 会员区域关联-是否存在关联
     * @param int $userId 会员id
     * @param int $shopId 门店id
     * @return bool
     * */
    public function isExistMemberRegion(int $userId, int $shopId)
    {
        $model = new MemberRegionRelationModel();
        $where = array(
            'userId' => $userId,
            'shopId' => $shopId,
            'isDelete' => 0,
        );
        $count = $model->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 会员区域关联-获取会员关联的区域id
     * @param int $userId 会员id
     * @param int $shopId 门店id
     * @return int
     * */
    public function getUserRegionId(int $userId, int $shopId)
    {

        $model = new MemberRegionRelationModel();
        $prefix = $model->tablePrefix;
        $where = array(
            'relation.userId' => $userId,
            'relation.shopId' => $shopId,
            'relation.isDelete' => 0,
        );
        $result = $model
            ->alias('relation')
            ->join("left join {$prefix}psd_region_range region on region.regionId=relation.regionId")
            ->where($where)
            ->field('region.regionId')
            ->find();
        $regionId = 0;
        if (!empty($result)) {
            $regionId = $result['regionId'];
        }
        return (int)$regionId;
    }

    /**
     * 区域-区域列表
     * @param array $params
     * -int shopId 门店id
     * -string regionName 线路名称
     * -int usePage 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 分页条数
     * @return array $result
     * */
    public function getRegionList(array $paramsInput)
    {
        $searchParams = array(
            'shopId' => 0,
            'regionName' => '',
            'usePage' => 0,
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($searchParams, $paramsInput);
        $model = new RegionModel();
        $shopId = $searchParams['shopId'];
        $where = array(
            'shopId' => $shopId,
            'dataFlag' => 1,
        );
        if (!empty($params['regionName'])) {
            $where['regionName'] = array('like', "%{$searchParams['regionName']}%");
        }
        $field = 'regionId,regionName,createTime';
        if ($params['usePage'] == 1) {//有分页
            $page = $params['page'];
            $pageSize = $params['pageSize'];
            $sql = $model
                ->where($where)
                ->field($field)
                ->buildSql();
            $result = $this->pageQuery($sql, $page, $pageSize);
        } else {//无分页
            $result = $model
                ->where($where)
                ->field($field)
                ->select();
        }
        return $result;
    }

    /**
     * 区域-区域详情
     * @param int $regionId 区域id
     * @return array
     * */
    public function getRegionDetailById(int $regionId)
    {
        $model = new RegionModel();
        $where = array(
            'regionId' => $regionId,
            'dataFlag' => 1
        );
        $field = 'regionId,regionName,deliveryLatLng';
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }
}
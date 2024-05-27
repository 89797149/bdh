<?php

namespace Merchantapi\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告类
 */
class MobileAdModel extends BaseModel
{
    /**
     * @param $shopId
     * @return array
     * 新增广告
     */
    public function insertAdInfo($shopId,$params)
    {
        $data = array();
        $data["adTitle"] = $params['adTitle'];
        $data["adImage"] = $params['adImage'];
        $data["adTypeId"] = (int)$params['adTypeId'];
        $data["adLocationId"] = (int)$params['adLocationId'];
        $data["adDescribe"] = (string)$params['adDescribe'];
        $data["adSort"] = (int)$params['adSort'];
        $data["createTime"] = date('Y-m-d H:i:s');
        $data["shopId"] = $shopId;
        $data["adFieldValue"] = I("adFieldValue");
        $sql = "select * from __PREFIX__mobile_ad_type where dataFlag = 1 and adTypeId = " . I('adTypeId');
        $adTypeCount = $this->queryRow($sql);
        if (empty($adTypeCount)) {
//            $rd['msg'] = '广告分类不存在';
//            return $rd;
            return returnData(false, -1, 'error', '广告分类不存在');
        }
        $sqlAdl = "select * from __PREFIX__mobile_ad_location where  adLocationId = " . I('adLocationId');
        $adLocation = $this->queryRow($sqlAdl);
        if (empty($adLocation)) {
//            $rd['msg'] = '广告位置不存在';
//            return $rd;
            return returnData(false, -1, 'error', '广告位置不存在');
        }
        $sqLi = "select count(adLocationId) as adCount from __PREFIX__mobile_ad where shopId = $shopId and dataFlag = 1 and adLocationId = " . I('adLocationId');
        $adCount = $this->queryRow($sqLi);
        if ($adCount['adCount'] == $adLocation['restrictCount']) {
//            $rd['msg'] = '超出广告条数限制';
//            return $rd;
            return returnData(false, -1, 'error', '超出广告条数限制');
        }
        $rs = $this->add($data);
        if (false === $rs) {
//            $rd['code'] = 0;
//            $rd['msg'] = '操作成功';
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * @param $shopId
     * @return array
     * 编辑广告信息
     */
    public function editAdInfo($shopId,$params)
    {
        $data["adId"] = (int)$params['adId'];
        $data["adTitle"] = $params['adTitle'];
        $data["adImage"] = $params['adImage'];
        $data["adTypeId"] = (int)$params['adTypeId'];
        $data["adLocationId"] = (int)$params['adLocationId'];
        $data["adDescribe"] = (string)$params['adDescribe'];
        $data["adFieldValue"] = (string)$params['adFieldValue'];
        $data["adSort"] = (int)$params['adSort'];
        $sql = "select * from __PREFIX__mobile_ad_type where dataFlag = 1 and adTypeId = " . I('adTypeId');
        $adType = $this->queryRow($sql);
        if (empty($adType)) {
            return returnData(false, -1, 'error', '广告分类不存在');
//            $rd['msg'] = '广告分类不存在';
//            return $rd;
        }
        $sqlAdl = "select * from __PREFIX__mobile_ad_location where adLocationId = " . I('adLocationId');
        $adLocation = $this->queryRow($sqlAdl);
        if (empty($adLocation)) {
//            $rd['msg'] = '广告位置不存在';
//            return $rd;
            return returnData(false, -1, 'error', '广告位置不存在');
        }
        $sqLi = "select count(adLocationId) as adCount from __PREFIX__mobile_ad where shopId = $shopId and dataFlag = 1 and adLocationId = " . $params['adLocationId'];
        $adCount = $this->queryRow($sqLi);
        $adInfo = $this->where(" shopId = $shopId and dataFlag = 1 and adLocationId=" . I('adLocationId'))->field('adId')->select();
        $adId = [];
        foreach ($adInfo as $v) {
            $adId[] = $v['adId'];
        }
        if (!in_array($data['adId'], $adId)) {
            if ($adCount['adCount'] == $adLocation['restrictCount']) {
//                $rd['msg'] = '超出广告条数限制';
//                return $rd;
                return returnData(false, -1, 'error', '超出广告条数限制');
            }
        }
        $rs = $this->where("adId=" . $data["adId"])->save($data);
        if (false === $rs) {
//            $rd['code'] = 0;
//            $rd['msg'] = '操作成功';
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * @param $shopId
     * @return array
     * 删除广告信息
     */
    public function delAdInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data['dataFlag'] = -1;
        $rs = $this->where("shopId = $shopId and adId = " . (int)I('adId'))->save($data);
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @param $shopId
     * @return array
     * 广告列表分页
     */
    public function getAdList($page = 1, $pageSize = 15, $shopId)
    {
        $adTitle = WSTAddslashes(I("adTitle"));
        $startTime = WSTAddslashes(I("startTime"));
        $endTime = WSTAddslashes(I("endTime"));
        $adTypeId = (int)I("adTypeId");
        $adLocationId = (int)I("adLocationId");
        $sql = "select ma.*,mat.typeDescribe,mal.locationDescribe from __PREFIX__mobile_ad ma left join __PREFIX__mobile_ad_type mat on mat.adTypeId = ma.adTypeId left join __PREFIX__mobile_ad_location mal on mal.adLocationId = ma.adLocationId ";
        $sql .= " where ma.dataFlag = 1 and ma.shopId = $shopId";
        if ($adTypeId > 0) {
            $sql .= " and mat.dataFlag = 1 and mat.adTypeId = $adTypeId";
        }
        if ($adLocationId > 0) {
            $sql .= " and mal.adLocationId = $adLocationId";
        }
        if ($adTitle != "") {
            $sql .= " and ma.adTitle like '%" . $adTitle . "%'";
        }
        if ($startTime != "") {
            $sql .= " and ma.createTime >= '$startTime'";
        }
        if ($endTime != "") {
            $sql .= " and ma.createTime <= '$endTime'";
        }
        $sql .= " order by ma.adSort desc ";
        return $this->pageQuery($sql, $page, $pageSize);
    }

    /**
     * @param $adId
     * @param $shopId
     * @return mixed
     * 获取广告详情
     */
    public function getAdDetail($adId, $shopId)
    {
        return $this->where("shopId = $shopId and dataFlag = 1 and adId='" . $adId . "'")->find();
    }
}
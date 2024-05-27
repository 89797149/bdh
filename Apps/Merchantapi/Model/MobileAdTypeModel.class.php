<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 广告类型类
 */
class MobileAdTypeModel extends BaseModel
{
    /**
     * @param $shopId
     * @return array
     * 新增广告分类
     */
    public function insertAdTypeInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data = array();
        $data["adTypeCode"] = I("adTypeCode");
        $data["typeDescribe"] = I("typeDescribe");
        $data["adTypeSort"] = I("adTypeSort", 0);
        $data["shopId"] = $shopId;
        $data["createTime"] = date('Y-m-d H:i:s');
        $adCode = $this->where("dataFlag = 1 and adTypeCode='" . $data['adTypeCode'] . "'")->find();
        if (!empty($adCode)) {
            $rd['msg'] = '该标识码已经存在';
            return $rd;
        }
        $adTypeInfo = $this->where("typeDescribe='" . $data['typeDescribe'] . "'")->find();
        if ($adTypeInfo) {
            $rd['msg'] = '该标题已经存在';
            return $rd;
        }
        if ($this->checkEmpty($data, true)) {
            $rs = $this->add($data);
            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * @param $shopId
     * @return array
     * 编辑广告分类信息
     */
    public function editAdTypeInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data["adTypeId"] = (int)I("adTypeId", 0);
        if ($this->checkEmpty($data, true)) {
            $data["adTypeCode"] = I("adTypeCode");
            $data["typeDescribe"] = I("typeDescribe");
            $data["adTypeSort"] = I("adTypeSort", 0);
            $adCode = $this->where("dataFlag = 1 and adTypeCode='" . $data['adTypeCode'] . "'")->find();
            if (!empty($adCode)) {
                if ($adCode['adTypeId'] != $data['adTypeId']) {
                    $rd['msg'] = '该标识码已经存在';
                    return $rd;
                }
            }
            $adTypeInfo = $this->where("and dataFlag = 1 and typeDescribe='" . $data['typeDescribe'] . "'")->find();
            if (!empty($adTypeInfo)) {
                if ($adTypeInfo['adTypeId'] != $data['adTypeId']) {
                    $rd['msg'] = '该标题已经存在';
                    return $rd;
                }
            }
            $rs = $this->where("adTypeId=" . $data["adTypeId"])->save($data);
            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * @param $shopId
     * @return array
     * 删除广告分类信息
     */
    public function delAdTypeInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data['dataFlag'] = -1;
        $rs = $this->where("adTypeId = " . (int)I('adTypeId'))->save($data);
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
     * 获取列表分页
     */
    public function getAdTypeList($page = 1, $pageSize = 15, $shopId)
    {
        $typeDescribe = WSTAddslashes(I("typeDescribe"));
        $sql = "select mat.* from __PREFIX__mobile_ad_type mat where mat.dataFlag = 1";
        if ($typeDescribe != "") {
            $sql .= " and typeDescribe like '%" . $typeDescribe . "%'";
        }
        $sql .= " order by mat.adTypeSort desc ";
        return $this->pageQuery($sql, $page, $pageSize);
    }

    /**
     * @param $adTypeId
     * @param $shopId
     * @return mixed
     * 获取广告分类详情
     */
    public function getAdTypeDetail($adTypeId, $shopId)
    {
        return $this->where("dataFlag = 1 and adTypeId='" . $adTypeId . "'")->find();
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取列表无分页
     */
    public function getAdType($shopId)
    {
        $where['dataFlag'] = 1;
        // $where['shopId'] = $shopId;
        $res = $this->where($where)->select();
        return $res;
    }

    /**
     * @param $shopId
     * @return array
     * 新增广告位置信息
     */
    public function addAdLocationInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $data = array();
        $data["adlocationCode"] = I("adlocationCode");
        $data["locationDescribe"] = I("locationDescribe");
        $data["adTypeSort"] = I("adTypeSort", 0);
        $data["createTime"] = date('Y-m-d H:i:s');
        $data["shopId"] = $shopId;
        $model = M('mobile_ad_location');
        $adCode = $model->where("adlocationCode ='" . $data['adlocationCode'] . "'")->find();
        if (!empty($adCode)) {
            $rd['msg'] = '该标识码已经存在';
            return $rd;
        }
        if ($this->checkEmpty($data, true)) {
            $data["restrictCount"] = (int)I("restrictCount", -1);
            $rs = $model->add($data);
            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * @param $shopId
     * @return array
     * 编辑广告位置信息
     */
    public function editAdLocationInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $model = M('mobile_ad_location');
        $data["adLocationId"] = (int)I("adLocationId", 0);
        if ($this->checkEmpty($data, true)) {
            $data["adlocationCode"] = I("adlocationCode");
            $data["locationDescribe"] = I("locationDescribe");
            $data["restrictCount"] = (int)I("restrictCount", -1);
            $data["adTypeSort"] = I("adTypeSort", 0);
            $adCode = $model->where("adlocationCode='" . $data['adlocationCode'] . "'")->find();
            if (!empty($adCode)) {
                if ($adCode['adLocationId'] != $data['adLocationId']) {
                    $rd['msg'] = '该标识码已经存在';
                    return $rd;
                }
            }
            $rs = $model->where("adLocationId=" . $data["adLocationId"])->save($data);
            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * @param $adLocationId
     * @param $shopId
     * @return mixed
     * 获取广告分类详情
     */
    public function getAdLocationDetail($adLocationId, $shopId)
    {
        return M('mobile_ad_location')->where("adLocationId='" . $adLocationId . "'")->find();
    }

    /**
     * @param $shopId
     * @return array
     * 删除广告位置信息
     */
    public function delAdLocationInfo($shopId)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $rs = M('mobile_ad_location')->where("adLocationId = " . (int)I('adLocationId'))->delete();
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
     * @return mixed
     * 获取广告位置列表分页
     */
    public function getAdLocationList($page = 1, $pageSize = 15, $shopId)
    {
        $locationDescribe = WSTAddslashes(I("locationDescribe"));
        $sql = "select mal.* from __PREFIX__mobile_ad_location mal where ";
        if ($locationDescribe != "") {
            $sql .= "locationDescribe like '%" . $locationDescribe . "%'";
        }
        $sql .= " order by mal.adTypeSort desc ";
        return M('mobile_ad_location')->pageQuery($sql, $page, $pageSize);
    }

    /**
     * @param $shopId
     * @return mixed
     * 获取广告位置列表无分页
     */
    public function getAdLocation($shopId)
    {
        $res = M('mobile_ad_location')->select();
        return $res;
    }
}
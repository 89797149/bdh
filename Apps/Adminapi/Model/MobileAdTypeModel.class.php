<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 广告类型类
 */
class MobileAdTypeModel extends BaseModel {
    /**
     * @param $userData
     * @return array|mixed
     * 新增广告分类
     */
    public function insert($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $data = array();
        $data["adTypeCode"] = I("adTypeCode");
        $data["typeDescribe"] = I("typeDescribe");
        $data["adTypeSort"] = I("adTypeSort",0);
        $data["createTime"] = date('Y-m-d H:i:s');
        $adCode = $this->where("shopId = 0 and dataFlag = 1 and adTypeCode='".$data['adTypeCode']."'")->find();
        if(!empty($adCode)){
            return returnData(false, -1, 'error', '该标识码已经存在');
        }
        $adTypeInfo = $this->where("shopId = 0 and typeDescribe='".$data['typeDescribe']."'")->find();
        if($adTypeInfo){
            return returnData(false, -1, 'error', '该标题已经存在');
        }
        if($this->checkEmpty($data,true)){
            $rs = $this->add($data);
            if(false !== $rs){
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]新增了广告分类信息:[{$data["typeDescribe"]}]";
                addOperationLog($userData['loginName'],$userData['staffId'],$describe,1);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return array|mixed
     * 编辑广告分类信息
     */
    public function edit($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $data["adTypeId"] = (int)I("adTypeId",0);
        if($this->checkEmpty($data,true)){
            $data["adTypeCode"] = I("adTypeCode");
            $data["typeDescribe"] = I("typeDescribe");
            $data["adTypeSort"] = I("adTypeSort",0);
            $adCode = $this->where("shopId = 0 and dataFlag = 1 and adTypeCode='".$data['adTypeCode']."'")->find();
            if(!empty($adCode)){
                if($adCode['adTypeId'] != $data['adTypeId']){
                    return returnData(false, -1, 'error', '该标识码已经存在');
                }
            }
            $adTypeInfo = $this->where("shopId = 0 and dataFlag = 1 and typeDescribe='".$data['typeDescribe']."'")->find();
            if(!empty($adTypeInfo)){
                if($adTypeInfo['adTypeId'] != $data['adTypeId']){
                    return returnData(false, -1, 'error', '该标题已经存在');
                }
            }
            $rs = $this->where("adTypeId=".$data["adTypeId"])->save($data);
            if(false !== $rs){
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]编辑了广告分类信息:[{$data["typeDescribe"]}]";
                addOperationLog($userData['loginName'],$userData['staffId'],$describe,3);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return array|mixed
     * 删除广告分类信息
     */
    public function del($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $adTypeId = (int)I('adTypeId',0);
        $rest = M('mobile_ad_type')->where(['adTypeId'=>$adTypeId])->find();
        if(empty($rest)){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $data['dataFlag'] = -1;
        $rs = $this->where(['adTypeId'=>$adTypeId])->save($data);
        if(false !== $rs){
            $rd = returnData(true);
            $describe = "[{$userData['loginName']}]删除了广告分类信息:[{$rest["typeDescribe"]}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,2);
        }
        return $rd;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * 获取列表分页
     */
    public function getAdTypeList($page=1,$pageSize=15){
        $typeDescribe = WSTAddslashes(I("typeDescribe"));
        $sql = "select mat.* from __PREFIX__mobile_ad_type mat where mat.dataFlag = 1 and mat.shopId = 0 ";
        if($typeDescribe!=""){
            $sql .= " and typeDescribe like '%".$typeDescribe."%'";
        }
        $sql .= " order by mat.adTypeSort desc ";
        return $this->pageQuery($sql,$page,$pageSize);
    }

    /**
     * 获取广告分类详情
     */
    public function getAdTypeDetail($adTypeId){
        return $this->where("dataFlag = 1 and adTypeId='".$adTypeId."'")->find();
    }

    /**
     * @return mixed
     * 获取列表无分页
     */
    public function getAdType(){
        $where['dataFlag'] = 1;
        $where['shopId'] = 0;
        $res = $this->where($where)->select();
        return $res;
    }

    /**
     * @param $userData
     * @return array
     * 新增广告位置信息
     */
    public function addAdLocationInfo($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $data = array();
        $data["adlocationCode"] = I("adlocationCode");
        $data["locationDescribe"] = I("locationDescribe");
        $data["adTypeSort"] = I("adTypeSort",0);
        $data["createTime"] = date('Y-m-d H:i:s');
        $model = M('mobile_ad_location');
        $adCode = $model->where("shopId = 0 and adlocationCode='".$data['adlocationCode']."'")->find();
        if(!empty($adCode)){
            return returnData(false, -1, 'error', '该标识码已经存在');
        }
        if($this->checkEmpty($data,true)){
            $data["restrictCount"] = (int)I("restrictCount",-1);
            $rs = $model->add($data);
            if(false !== $rs){
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]新增了广告位置信息:[{$data["locationDescribe"]}]";
                addOperationLog($userData['loginName'],$userData['staffId'],$describe,1);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return mixed
     * 编辑广告位置信息
     */
    public function editAdLocationInfo($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $model = M('mobile_ad_location');
        $data["adLocationId"] = (int)I("adLocationId",0);
        if($this->checkEmpty($data,true)){
            $data["adlocationCode"] = I("adlocationCode");
            $data["locationDescribe"] = I("locationDescribe");
            $data["restrictCount"] = (int)I("restrictCount",-1);
            $data["adTypeSort"] = I("adTypeSort",0);
            $adCode = $model->where("shopId = 0 and adlocationCode='".$data['adlocationCode']."'")->find();
            if(!empty($adCode)){
                if($adCode['adLocationId'] != $data['adLocationId']){
                    return returnData(false, -1, 'error', '该标识码已经存在');
                }
            }
            $rs = $model->where("adLocationId=".$data["adLocationId"])->save($data);
            if(false !== $rs){
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]编辑了广告位置信息:[{$data["locationDescribe"]}]";
                addOperationLog($userData['loginName'],$userData['staffId'],$describe,3);
            }
        }
        return $rd;
    }

    /**
     * 获取广告分类详情
     */
    public function getAdLocationDetail($adLocationId){
        return M('mobile_ad_location')->where("shopId = 0 and adLocationId='".$adLocationId."'")->find();
    }

    /**
     * @param $userData
     * @return array|mixed
     * 删除广告位置信息
     */
    public function delAdLocationInfo($userData){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $res = M('mobile_ad_location')->where('adLocationId = '.(int)I('adLocationId'))->find();
        if(empty($res)){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $rs = M('mobile_ad_location')->where('adLocationId = '.(int)I('adLocationId'))->delete();
        if(false !== $rs){
            $rd = returnData(true);
            $describe = "[{$userData['loginName']}]删除了广告位置信息:[{$res["locationDescribe"]}]";
            addOperationLog($userData['loginName'],$userData['staffId'],$describe,2);
        }
        return $rd;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * 获取广告位置列表分页
     */
    public function getAdLocationList($page=1,$pageSize=15){
        $locationDescribe = WSTAddslashes(I("locationDescribe"));
        $sql = "select mal.* from __PREFIX__mobile_ad_location mal where mal.shopId = 0 ";
        if($locationDescribe!=""){
            $sql .= "and locationDescribe like '%".$locationDescribe."%'";
        }
        $sql .= " order by mal.adTypeSort desc ";
        return M('mobile_ad_location')->pageQuery($sql,$page,$pageSize);
    }

    /**
     * @return mixed
     * 获取广告位置列表无分页
     */
    public function getAdLocation(){
        $res = M('mobile_ad_location')->where('shopId = 0')->select();
        return $res;
    }
}
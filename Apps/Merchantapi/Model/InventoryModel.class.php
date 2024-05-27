<?php

namespace Merchantapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\InventoryBillModel;
use App\Models\InventoryBillRelationModel;
use App\Models\InventoryLossModel;
use App\Models\SkuGoodsSelfModel;
use App\Models\SkuGoodsSystemModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Inventory\InventoryLossModule;
use App\Modules\Inventory\InventoryModule;
use App\Modules\Inventory\LocationModule;
use App\Modules\Inventory\ReportLossReasonModule;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 盘点功能类
 */
class InventoryModel extends BaseModel
{

    /**
     * 获得货位列表 - 带分页的
     * 可正常使用的
     * @param $param
     */
    /*public function getLocationList($param){
        $where = " shopId = " . $param['shopId'] . " and lFlag = 1 ";
        $sql = "select * from __PREFIX__location where " . $where . " order by sort desc ";
        return $this->pageQuery($sql, $param['page'], $param['pageSize']);
    }*/

    /**
     * 获得货位列表 - 不带分页的
     * @param $param
     */
    public function getLocationList($param)
    {
        $m = M('location');
        $list = $m->where(array('shopId' => $param['shopId'], 'lFlag' => 1, 'parentId' => 0))->order('sort desc')->select();
        if (!empty($list)) {
            $twoList = $m->where(array('shopId' => $param['shopId'], 'lFlag' => 1, 'parentId' => array('GT', 0)))->order('sort desc')->select();
            $twoList_arr = array();
            if (!empty($twoList)) {
                foreach ($twoList as $k => $v) {
                    $twoList_arr[$v['parentId']][] = $v;
                }
            }
            if (!empty($twoList_arr)) {
                foreach ($list as $k => $v) {
                    $list[$k]['twoList'] = $twoList_arr[$v['lid']];
                }
            }
        }
        return $list;
    }

    /**
     * 编辑货位
     * @param $where
     * @param $data
     */
    public function editLocation($where, $data)
    {
        return M('location')->where($where)->save($data);
    }

    /**
     * 新增货位
     * @param $data
     */
//    public function addLocation($data)
//    {
//        return M('location')->add($data);
//    }

    /**
     *新增货位
     * @param array $params <p>
     *int shopId
     *int parentId 父id
     *string name 货位名称
     *int sort 排序 （值越大排序越靠前）
     * </p>
     * @return bool $data
     * */
    public function addLocation(array $params)
    {
        $save = [];
        $save['shopId'] = 0;
        $save['parentId'] = 0;
        $save['name'] = '';
        $save['sort'] = null;
        parm_filter($save, $params);
        $where = [];
        $where['shopId'] = $save['shopId'];
        $where['name'] = $save['name'];
        $where['parentId'] = $save['parentId'];
        $info = $this->getLocationDetail($where);
        if (!empty($info)) {
            return returnData(false, -1, 'error', "货位【{$save['name']}】已存在");
        }
        $tab = M('location');
        $data = $tab->add($save);
        if (!$data) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     *修改货位
     * @param array $params <p>
     *int id 货位id
     *int shopId
     *int parentId 父id
     *string name 货位名称
     *int sort 排序 （值越大排序越靠前）
     * </p>
     * @return bool $data
     * */
    public function updateLocation(array $params)
    {
        $where = [];
        $where['lid'] = $params['lid'];
        $save = [];
        $save['shopId'] = 0;
        $save['parentId'] = 0;
        $save['name'] = '';
        $save['sort'] = null;
        parm_filter($save, $params);
        $verificationWhere = [];
        $verificationWhere['name'] = $save['name'];
        $verificationWhere['parentId'] = $save['parentId'];
        $verificationWhere['shopId'] = $save['shopId'];
        $info = $this->getLocationDetail($verificationWhere);
        if (!empty($info) && $info['lid'] != $params['lid']) {
            return returnData(false, -1, 'error', "货位【{$save['name']}】已存在");
        }
        $tab = M('location');
        $data = $tab->where($where)->save($save);
        if ($data === false) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * 删除货位
     * @param $where
     * @param $data
     */
    public function deleteLocation($where, $data)
    {
        $lm = M('location');
        $lgm = M('location_goods');
        $locationInfo = $lm->where($where)->find();
        if (empty($locationInfo)) return false;
        $result = $lm->where($where)->save($data);
        if (empty($locationInfo['parentId'])) {//一级货位
            //删除二级货位
            $where_new = array('shopId' => $where['shopId'], 'parentId' => $locationInfo['lid']);
            $lm->where($where_new)->save($data);
            //删除货位商品
            $lgm->where(array('shopId' => $where['shopId'], 'lparentId' => $locationInfo['lid']))->save(array('lgFlag' => -1));
        } else {//二级货位
            //删除货位商品
            $lgm->where(array('shopId' => $where['shopId'], 'lid' => $locationInfo['lid']))->save(array('lgFlag' => -1));
        }
        return $result;
    }

    /**
     * 获取一级/二级货位列表
     * @param $where
     * @return mixed
     */
    public function getLocationRankList($where)
    {
        return M('location')->where($where)->order('sort desc')->select();
    }

    /**
     * 获取一级货位列表
     * @param array $params <p>
     * int shopId
     * </p>
     * @return mixed
     */
//    public function getLocationRankList($params)
//    {
//        $page = $params['page'];
//        $pageSize = $params['pageSize'];
//        $where = [];
//        $where['lFlag'] = 1;
//        $where['shopId'] = 0;
//        $where['parentId'] = 0;
//        parm_filter($where, $params);
//        where($where);
//        $sql = "select * from __PREFIX__location where {$where} order by sort desc ";
//        $data = $this->pageQuery($sql, $page, $pageSize);
//        return $data;
//        //return M('location')->where($where)->order('sort desc')->select();
//    }

    /**
     * 获取二级货位列表
     * @param array $params <p>
     * int shopId
     * </p>
     * @return mixed
     */
    public function twoLocationRankList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = "lFlag=1 and shopId={$params['shopId']} and parentId > 0 ";
        $sql = "select * from __PREFIX__location where {$where} order by sort desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        return $data;
        //return M('location')->where($where)->order('sort desc')->select();
    }

    /**
     * 获得货位商品列表
     * @param $param
     */
    public function getLocationGoodsList($param)
    {
        $where = " lg.shopId = " . $param['shopId'] . " and lg.lgFlag = 1 ";
        if (!empty($param['lparentId'])) $where .= " and lg.lparentId = " . $param['lparentId'] . " ";
        if (!empty($param['lid'])) $where .= " and lg.lid = " . $param['lid'] . " ";
        $sql = "select lg.*,l.name,g.goodsName,g.goodsImg,g.goodsThums from __PREFIX__location_goods as lg inner join __PREFIX__location as l on lg.lid = l.lid inner join __PREFIX__goods as g on lg.goodsId = g.goodsId where " . $where . " order by lg.createTime desc ";
        $result = $this->pageQuery($sql, $param['page'], $param['pageSize']);
        if (!empty($result['root'])) {
            $locationRank_arr = array();
            $getLocationRankList = $this->getLocationRankList(array('shopId' => $param['shopId'], 'parentId' => 0));
            if (!empty($getLocationRankList)) {
                foreach ($getLocationRankList as $v) {
                    $locationRank_arr[$v['lid']] = $v['name'];
                }
            }
            foreach ($result['root'] as $k => $vl) {
                $result['root'][$k]['lparentName'] = $locationRank_arr[$vl['lparentId']];
            }
        }
        return $result;
    }

    /**
     * 删除货位商品
     * @param $where
     * @param $data
     * @return bool
     */
    public function deleteLocationGoods($where, $data)
    {
        $lgm = M('location_goods');
        $locationGoodsInfo = $lgm->where($where)->find();
        if (empty($locationGoodsInfo)) return false;
        return $lgm->where($where)->save($data);
    }

    /**
     * @param $goodsId
     * @param $shopId
     * @param $goodsLocation
     * @return mixed
     * 根据商品id添加货位
     */
    public function addGoodsLocation($goodsId, $shopId, $goodsLocation)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();
        $m = M('goods');
        $goods = $m->where('goodsId = ' . $goodsId . " and shopId =" . $shopId)->find();
        if (empty($goods)) {
            $apiRet['apiInfo'] = '商品不存在';
            return $apiRet;
        }
        $lm = M('location');
        $lgm = M('location_goods');
        $lgm->where(array('shopId' => $shopId, 'goodsId' => $goods['goodsId'], 'lgFlag' => 1))->save(['lgFlag' => -1]);
//        $lid = [];
//        foreach ($locationGoodsInfo as $v) {
//            $lid[] = $v['lid'];
//        }
        $goodsLocation_arr = explode(',', $goodsLocation);
//        $goodsLocation_arr = [];
//        foreach ($goodsLocation as $k => $v) {
//            if (in_array($v['lid'], $lid)) {
//                $apiRet['apiInfo'] = '请检查货位是否已添加过';
//                return $apiRet;
//            }
//            $goodsLocation_arr[] = $v['lid'];
//        }
        $data_t = array();
        foreach ($goodsLocation_arr as $v) {
            $locationInfo = $lm->where(array('shopId' => $shopId, 'lid' => $v, 'lFlag' => 1))->find();
//            if (empty($locationInfo)) continue;
            if ($locationInfo['parentId'] == 0 && !empty($locationInfo)) {
                $apiRet['apiInfo'] = '货位参数有误';
                return $apiRet;
            }
            $data_t[] = array(
                'shopId' => $shopId,
                'lparentId' => $locationInfo['parentId'],
                'lid' => $v,
                'goodsId' => $goods['goodsId'],
                'goodsSn' => $goods['goodsSn'],
                'createTime' => date('Y-m-d H:i:s'),
                'lgFlag' => 1
            );
        }
        if (!empty($data_t)) {
            $lgm->addAll($data_t);
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * @param $goodsId
     * @param $shopId
     * @return mixed
     * 根据商品id获取货位信息
     */
    public function getGoodsLocationInfo($goodsId, $shopId)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();
        $m = M('goods');
        $goods = $m->where('goodsId = ' . $goodsId . " and shopId =" . $shopId)->find();
        if (empty($goods)) {
            $apiRet['apiInfo'] = '商品不存在';
            return $apiRet;
        }
        $lgm = M('location_goods lg');
        $locationGoodsInfo = $lgm
            ->join('left join wst_location wl on wl.lid = lg.lid')
            ->where(array('lg.shopId' => $shopId, 'lg.goodsId' => $goods['goodsId'], 'lg.lgFlag' => 1))
            ->field('lg.*,wl.name')
            ->select();
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $locationGoodsInfo;
        return $apiRet;
    }

    /**
     * 货位商品转移 - 动作
     * @param $shopId
     * @param $lgid
     * @param $lparentId
     * @param $lid
     * @param $targetLid
     * @return array
     */
    public function doLocationGoodsTransfer($shopId, $lgid, $lparentId, $lid, $targetLid)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $where = array('shopId' => $shopId, 'lgid' => array('in', $lgid), 'lgFlag' => 1);
        $where_o = $where;
        if (!empty($lparentId)) $where_t['lparentId'] = $lparentId;
        if (!empty($lid)) $where_t['lid'] = $lid;
        $lgm = M('location_goods');
        $lm = M('location');
        $location_goods_list = $lgm->where($where_o)->select();
        if (empty($location_goods_list)) {
            $apiRet['apiInfo'] = '请选择商品';
            return $apiRet;
        }
        $location_info = $lm->where(array('lid' => $targetLid, 'lFlag' => 1))->find();
        if (empty($location_info)) {
            $apiRet['apiInfo'] = '目标货位不存在';
            return $apiRet;
        }

        $lgid_arr = array();
        foreach ($location_goods_list as $k => $v) {
            $where_n['shopId'] = $shopId;
            $where_n['lid'] = $targetLid;
            $where_n['goodsId'] = $v['goodsId'];
            $location_goods_info = $lgm->where($where_n)->find();
            if (!empty($location_goods_info)) {
//                $lgm->where(array('lgid'=>$v['lgid']))->save(array('lgFlag'=>-1));
//                $lgm->where(array('lgid'=>$v['lgid']))->delete();
                continue;
            }
            $lgid_arr[] = $v['lgid'];
        }
        if (empty($lgid_arr)) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $lgm->where(array('lgid' => array('in', $lgid_arr), 'lgFlag' => 1))->save(array('lid' => $targetLid, 'lparentId' => $location_info['parentId']));
        if ($result) {
            $gm = M('goods');
            $goodsId_arr = $lgm->where(array('lgid' => array('in', $lgid_arr), 'lgFlag' => 1))->getField('goodsId', true);
            $lg_list = $lgm->where(array('goodsId' => array('in', $goodsId_arr), 'lgFlag' => 1))->field('goodsId,lid')->select();
            $goodsId_lid_arr = array();
            if (!empty($lg_list)) {
                foreach ($lg_list as $v) {
                    $goodsId_lid_arr[$v['goodsId']] .= $v['lid'] . ",";
                }
            }
            if (!empty($goodsId_lid_arr)) {
                foreach ($goodsId_lid_arr as $k => $v) {
                    $goodsLocation = rtrim($v, ',');
                    $gm->where(array('goodsId' => $k))->save(array('goodsLocation' => $goodsLocation));
                }
            }

            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 盘点任务列表
     * @param $param
     */
    public function getInventoryList($param)
    {
        $where = " shopId = " . $param['shopId'] . " and iFlag = 1 and state = " . $param['state'];
        if (!empty($param['name'])) $where .= " and name like '%" . $param['name'] . "%' ";
        if (!empty($param['start_time'])) $where .= " and createTime >= '" . $param['start_time'] . "' ";
        if (!empty($param['end_time'])) $where .= " and createTime <= '" . $param['end_time'] . "' ";
        $sql = "select * from __PREFIX__inventory where " . $where . " order by createTime desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        return $list;
    }

    /**
     * 子盘点任务列表
     * @param $param
     */
    public function getChildInventoryList($param)
    {
        $where = " ci.shopId = " . $param['shopId'] . " and ci.iid = " . $param['iid'] . " and ci.ciFlag = 1 ";
        $sql = "select ci.*,i.name from __PREFIX__child_inventory as ci left join __PREFIX__inventory as i on ci.iid = i.iid where " . $where . " order by ci.ciid asc ";

        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        if (!empty($list['root'])) {
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            foreach ($list['root'] as $k => $v) {
                $list['root'][$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
                $list['root'][$k]['lName'] = $locationKeyValueArr[$v['lid']];
            }
        }

        return $list;
    }

    /**
     * 编辑盘点任务
     * @param $where
     * @param $data
     */
    public function editInventory($where, $data)
    {
        $idata = array(
            'shopId' => $data['shopId'],
            'name' => $data['name'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime']
        );
        $result = M('inventory')->where($where)->save($idata);
        if ($result) {
            $iid = $where['iid'];
            $lids = $data['lids'];
            $uids = $data['uids'];
            $isUpdateStocks = $data['isUpdateStocks'];
            if (!empty($lids)) {
                $lids_arr = explode(',', $lids);
                $uids_arr = empty($uids) ? array() : explode(',', $uids);
                $isUpdateStocks_arr = empty($isUpdateStocks) ? array() : explode(',', $isUpdateStocks);

                $cim = M('child_inventory');
                $ciwhere = array(
                    'shopId' => $data['shopId'],
                    'iid' => $iid,
                    'lid' => array('not in', $lids_arr)
                );
                $cim->where($ciwhere)->save(array('ciFlag' => -1));

                $cidata = array();
                $ciwhere_t = array('shopId' => $data['shopId'], 'iid' => $iid, 'ciFlag' => 1);
                foreach ($lids_arr as $k => $v) {
                    $ciwhere_t['lid'] = $v;
                    if (empty($uids_arr[$k])) {
                        $cim->where($ciwhere_t)->save(array('ciFlag' => -1));
                        continue;
                    }
                    $childInventoryInfo = $cim->where($ciwhere_t)->find();

                    $userInfo = $this->getShopUserInfoById(array('id' => $uids_arr[$k]));
                    //如果子盘点任务存在，则编辑子盘点任务，否则，则添加子盘点任务
                    if (!empty($childInventoryInfo)) {
                        $editData = array(
                            'uid' => $uids_arr[$k],
                            'username' => empty($userInfo) ? '' : $userInfo['username'],
                            'isUpdateStock' => $isUpdateStocks_arr[$k]
                        );
                        $cim->where($ciwhere_t)->save($editData);
                    } else {
                        $locationInfo = $this->getLocationInfoByLid(array('lid' => $v));
                        if (empty($locationInfo)) continue;
                        $cidata[] = array(
                            'shopId' => $data['shopId'],
                            'iid' => $iid,
                            'lparentId' => $locationInfo['parentId'],
                            'lid' => $v,
                            'uid' => $uids_arr[$k],
                            'username' => empty($userInfo) ? '' : $userInfo['username'],
                            'state' => 0,
                            'isUpdateStock' => (int)$isUpdateStocks_arr[$k],
                            'ciFlag' => 1
                        );
                    }
                }
                if (!empty($cidata)) $cim->addAll($cidata);
            }
        }
        return $result;
    }

    /**
     * 新增盘点任务
     * @param $data
     */
    public function addInventory($data)
    {
        $idata = array(
            'shopId' => $data['shopId'],
            'name' => $data['name'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime'],
            'createTime' => date('Y-m-d H:i:s'),
            'state' => 0,
            'iFlag' => 1
        );
        $iid = M('inventory')->add($idata);
        if ($iid > 0) {
            $lids = $data['lids'];
            $uids = $data['uids'];
            $isUpdateStocks = $data['isUpdateStocks'];
            if (!empty($lids)) {
                $lids_arr = explode(',', $lids);
                $uids_arr = empty($uids) ? array() : explode(',', $uids);
                $isUpdateStocks_arr = empty($isUpdateStocks) ? array() : explode(',', $isUpdateStocks);
                $cidata = array();
                foreach ($lids_arr as $k => $v) {
                    if (empty($uids_arr[$k])) continue;
                    $locationInfo = $this->getLocationInfoByLid(array('lid' => $v));
                    if (empty($locationInfo)) continue;
                    $userInfo = $this->getShopUserInfoById(array('id' => $uids_arr[$k]));
                    $cidata[] = array(
                        'shopId' => $data['shopId'],
                        'iid' => $iid,
                        'lparentId' => $locationInfo['parentId'],
                        'lid' => $v,
                        'uid' => $uids_arr[$k],
                        'username' => empty($userInfo) ? '' : $userInfo['username'],
                        'state' => 0,
                        'isUpdateStock' => (int)$isUpdateStocks_arr[$k],
                        'ciFlag' => 1
                    );
                }
                if (!empty($cidata)) M('child_inventory')->addAll($cidata);
            }
        }
        return $iid;
    }

    /**
     * 作废盘点任务
     * @param $where
     * @param $data
     * @return mixed
     */
    public function cancelInventory($where, $data)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $im = M('inventory');
        $cim = M('child_inventory');
        $inventoryInfo = $im->where($where)->find();
        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '盘点任务不存在';
            return $apiRet;
        }

        if (in_array($inventoryInfo['state'], array(3))) {
            $apiRet['apiInfo'] = '盘点任务已作废';
            return $apiRet;
        }

        $result = $im->where($where)->save($data);
        $cim->where($where)->save($data);
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * 盘点详情
     * @param $where
     * @return array
     */
    public function getInventoryDetail($where)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $im = M('inventory');
        $cim = M('child_inventory');
        $lm = M('location');
        $where_s = $where;
        $where_s['iFlag'] = 1;
        $inventoryDetail = $im->where($where_s)->find();
        if (empty($inventoryDetail)) {
            $apiRet['apiInfo'] = '盘点任务不存在';
            return $apiRet;
        }

        $locationKeyValueArr = $this->getLocationKeyValueArr();
        $where['ciFlag'] = 1;
        $childInventory = $cim->distinct(true)->where($where)->field('lparentId')->select();
        if (!empty($childInventory)) {
            foreach ($childInventory as $k => $v) {
                $childInventory[$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
                $where['lparentId'] = $v['lparentId'];
                $childLocationList = $cim->where($where)->order('ciid asc')->select();
                if (!empty($childLocationList)) {
                    foreach ($childLocationList as $kc => $vc) {
                        $childLocationList[$kc]['lName'] = $locationKeyValueArr[$vc['lid']];
                    }
                }
                $childInventory[$k]['childInventory'] = $childLocationList;
            }
        }
        $inventoryDetail['childInventory'] = $childInventory;

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $inventoryDetail;

        return $apiRet;
    }

    /**
     * 删除盘点任务
     * @param $where
     * @param $data
     */
    public function deleteInventory($where, $data)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $im = M('inventory');
        $cim = M('child_inventory');
        $irm = M('inventory_record');
        $iwhere = $where;
        $iwhere['iFlag'] = 1;
        $inventoryInfo = $im->where($iwhere)->find();
        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '盘点任务 不存在 或 已被删除';
            return $apiRet;
        }
        if (in_array($inventoryInfo['state'], array(1))) {
            $apiRet['apiInfo'] = '任务正在盘点中，不可删除';
            return $apiRet;
        }

        $result = $im->where($iwhere)->save($data);
        $ciwhere = $where;
        $ciwhere['ciFlag'] = 1;
        $result1 = $cim->where($ciwhere)->save(array('ciFlag' => -1));
        $result2 = $irm->where($where)->save(array('irFlag' => -1));
        if ($result && $result1) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 获取子盘点任务列表
     * @param $where
     * @return mixed
     */
    public function getInventoryListByCondition($where)
    {
        return M('child_inventory')->where($where)->select();
    }

    /**
     * 获取门店职员列表
     * @param $where
     * @return mixed
     */
    public function getShopUserList($where)
    {
        return M('user')->where($where)->select();
    }

    /**
     * 根据 职员id 来获取 门店职员信息
     * @param $where
     * @return mixed
     */
    public function getShopUserInfoById($where)
    {
        return M('user')->where($where)->find();
    }

    /**
     * 获得入库任务列表
     * @param $param
     */
    public function getInWarehouseList($param)
    {
        $where = " shopId = " . $param['shopId'] . " and iwFlag = 1 ";
        if ($param['state'] > -1) $where .= " and state = " . $param['state'];
        if (!empty($param['name'])) $where .= " and name like '%" . $param['name'] . "%' ";
        if (!empty($param['start_time'])) $where .= " and createTime >= '" . $param['start_time'] . "' ";
        if (!empty($param['end_time'])) $where .= " and createTime <= '" . $param['end_time'] . "' ";
        if (!empty($param['username'])) {
            $userWhere = [];
            $userWhere['username'] = $param['username'];
            $userWhere['status'] = ['IN', '0,-2'];
            $userInfo = M('user')->where($userWhere)->find();
        }
        $sql = "select * from __PREFIX__in_warehouse where " . $where . " order by createTime desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        if (!empty($param['username'])) {
            $ids = '0';
            if (count($list['root']) > 0) {
                foreach ($list['root'] as $value) {
                    $uidArr = explode(',', $value['uids']);
                    if (in_array($userInfo['id'], $uidArr)) {
                        $ids .= "," . $value['iwid'];
                    }
                }
            }
            $where .= " and iwid IN ($ids) ";
            $sql = "select * from __PREFIX__in_warehouse where " . $where . " order by createTime desc ";
            $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);
        }

        if (!empty($list['root'])) {
            $userKeyValueArr = $this->getUserKeyValueArr();
            foreach ($list['root'] as $k => $v) {
                if (empty($v['uids'])) continue;
                $uids = explode(',', $v['uids']);
                $username_arr = array();
                foreach ($uids as $ku => $vu) {
                    $username_arr[] = $userKeyValueArr[$vu];
                }
                $list['root'][$k]['username'] = implode(',', $username_arr);
            }
        }

        return $list;
    }

    /**
     * 编辑入库任务
     * @param $where
     * @param $data
     */
    public function editInWarehouse($where, $data)
    {
        $warehouseTab = M('in_warehouse');//入库任务表
        $warehouseInfo = M('in_warehouse_info');//入库任务明细表
        $warehouseDetail = $warehouseTab->where($where)->find();
        if (empty($warehouseDetail)) {
            return returnData(null, -1, 'error', '入库任务不存在');
        }
        if ($warehouseDetail['state'] > 0) {
            return returnData(null, -1, 'error', '入库中或已完成的入库任务不能修改');
        }
        $warseData = [];
        $warseData['name'] = $data['name'];
        $warseData['startTime'] = $data['startTime'];
        $warseData['endTime'] = $data['endTime'];
        $warseData['dataType'] = $data['dataType'];
        $warseData['uids'] = rtrim($data['uids'], ',');
        $iwid = $warehouseTab->where(['iwid' => $warehouseDetail['iwid']])->save($warseData);//入库任务主表
        if ($iwid === false) {
            return returnData(null, -1, 'error', '修改失败');
        }
        $warehouseInfo->where(['iwid' => $warehouseDetail['iwid']])->delete();
        $dataIdArr = explode(',', rtrim($data['purchaseOrderIds'], ','));
        $insertInfo = [];
        foreach ($dataIdArr as $key => $value) {
            $info = [];
            $info['iwid'] = $iwid;
            $info['dataId'] = $value;
            $insertInfo[] = $info;
        }
        $res = $warehouseInfo->addAll($insertInfo);
        return (bool)$res;
    }

    /**
     * 新增入库任务
     * @param $data
     */
    public function addInWarehouse($data)
    {
        $warseData = [];
        $warseData['shopId'] = $data['shopId'];
        $warseData['name'] = $data['name'];
        $warseData['startTime'] = $data['startTime'];
        $warseData['endTime'] = $data['endTime'];
        $warseData['createTime'] = $data['createTime'];
        $warseData['state'] = $data['state'];
        $warseData['dataType'] = $data['dataType'];
        $warseData['uids'] = rtrim($data['uids'], ',');
        $iwid = M('in_warehouse')->add($warseData);//入库任务主表
        if ($iwid <= 0) {
            return returnData(false, -1, 'error', '添加入库任务失败');
        }
        $dataIdArr = explode(',', rtrim($data['purchaseOrderIds'], ','));
        $warehouseInfo = M('in_warehouse_info');//入库任务明细表
        $insertInfo = [];
        foreach ($dataIdArr as $key => $value) {
            $info = [];
            $info['iwid'] = $iwid;
            $info['dataId'] = $value;
            $insertInfo[] = $info;
        }
        $res = $warehouseInfo->addAll($insertInfo);
        return returnData((bool)$res);
    }

    /**
     * 入库详情
     * @param $data
     */
    public function getInWarehouseDetail($where)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $inWarehouseDetail = M('in_warehouse')->where($where)->find();
        if (empty($inWarehouseDetail)) {
            $apiRet['apiInfo'] = '入库任务不存在';
            return $apiRet;
        }
        $wareInfo = M('in_warehouse_info')->where(['iwid' => $inWarehouseDetail['iwid']])->select();
        $purchaseOrderIds = [];
        foreach ($wareInfo as $value) {
            $purchaseOrderIds[] = $value['dataId'];
        }
        $inWarehouseDetail['purchaseOrderIds'] = implode(',', $purchaseOrderIds);
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $inWarehouseDetail;

        return $apiRet;
    }

    /**
     * 删除入库任务
     * @param $where
     * @param $data
     */
    public function deleteInWarehouse($where, $data)
    {
        $warehouseTab = M('in_warehouse');
        $recordTab = M('in_warehouse_record');
        $inWarehouseInfo = $warehouseTab->where($where)->find();
        if (empty($inWarehouseInfo)) {
            return returnData(false, -1, 'error', '删除失败，入库任务不存在');
        }
        $result = $warehouseTab->where($where)->save($data);
        if (!$result) {
            return returnData(false, -1, 'error', '删除失败');
        }
        $recordTab->where($where)->save(array('iwrFlag' => -1));
        return returnData($result);
    }

    /**
     * 根据商品编号或名称来搜索商品
     * @param $shopId
     * @param $keywords
     * @return mixed
     */
    public function getGoodsList($shopId, $keywords)
    {
        if (substr($keywords, 0, 3) == 'CZ-') {
            $barcodeModel = M('barcode');
            $where = [];
            $where['barcode'] = $keywords;
            $where['bFlag'] = 1;
            $barcodeInfo = $barcodeModel->where($where)->find();
            if ($barcodeInfo) {
                $where = [];
                $where['goodsId'] = ['IN', $barcodeInfo['goodsId']];
                $where['goodsFlag'] = 1;
                $where['isSale'] = 1;
                $data = M('goods')->where($where)->select();
            }
        } else {
            $sql = "select * from __PREFIX__goods where shopId = " . $shopId . " and (goodsSn like '%" . $keywords . "%' or goodsName like '%" . $keywords . "%') and isSale = 1 and goodsFlag = 1";
            $data = $this->query($sql);
        }
        return (array)$data;
    }

    /**
     * 根据商品编号或名称来搜索商品
     * 只查询出一条数据
     * @param $shopId
     * @param $keywords
     * @return mixed
     */
    public function searchGoodsByGoodsIdAndGoodsName($shopId, $keywords)
    {
        if (substr($keywords, 0, 3) == 'CZ-') {
            $barcodeModel = M('barcode');
            $where = [];
            $where['barcode'] = $keywords;
            $where['bFlag'] = 1;
            $barcodeInfo = $barcodeModel->where($where)->find();
            if ($barcodeInfo) {
                $where = [];
                $where['goodsId'] = ['IN', $barcodeInfo['goodsId']];
                $where['goodsFlag'] = 1;
                $where['isSale'] = 1;
                $data = M('goods')->where($where)->find();
            }
        } else {
            $sql = "select * from __PREFIX__goods where shopId = " . $shopId . " and (goodsSn like '%" . $keywords . "%' or goodsName like '%" . $keywords . "%') and isSale = 1 and goodsFlag = 1";
            $data = $this->queryRow($sql);
        }
        if (empty($data)) {
            $data = [];
        }
        return $data;
    }

    /**
     * 根据二级货位名称来搜索货位
     * @param $shopId
     * @param $keywords
     * @return mixed
     */
    public function getLocationListByTwoLocationName($shopId, $keywords)
    {
        return M('location')->where(array('shopId' => $shopId, 'parentId' => array('GT', 0), 'name' => array('like', '%' . $keywords . '%'), 'lFlag' => 1))->order('sort desc')->select();
    }

    /**
     * 根据名称来搜索入库任务
     * @param $shopId
     * @param $keywords
     * @return mixed
     */
    public function getInWarehouseListByName($shopId, $keywords)
    {
        return M('in_warehouse')->where(array('shopId' => $shopId, 'name' => array('like', '%' . $keywords . '%'), 'iwFlag' => 1))->order('createTime desc')->select();
    }

    /**
     * 根据 货位id 来获取货位信息
     * @param $where
     * @return mixed
     */
    public function getLocationInfoByLid($where)
    {
        return M('location')->where($where)->find();
    }

    /**
     * 根据 子盘点id 来获取子盘点任务信息
     * @param $where
     * @return mixed
     */
    public function getChildInventoryInfoByCiid($where)
    {
        return M('child_inventory')->where($where)->find();
    }

    /**
     * 根据 商品ID 来获取商品信息
     * @param $where
     * @return mixed
     */
    public function getGoodsInfoByGoodsId($where)
    {
        return M('goods')->where($where)->find();
    }

    /**
     * 获取商品报损列表
     * @param $param
     */
    public function getGoodsReportLossList($param)
    {
        $where = " grl.shopId = " . $param['shopId'] . " and grl.grlFlag = 1 ";
        $sql = "select grl.*,l.name,g.goodsName from __PREFIX__goods_report_loss as grl inner join __PREFIX__location as l on grl.lid = l.lid inner join __PREFIX__goods as g on grl.goodsId = g.goodsId where " . $where . " order by grl.createTime desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        if (!empty($list['root'])) {
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            foreach ($list['root'] as $k => $v) {
                $list['root'][$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
            }
        }

        return $list;
    }

    /**
     * 编辑商品报损
     * @param $where
     * @param $data
     */
    public function editGoodsReportLoss($where, $data)
    {
        return M('goods_report_loss')->where($where)->save($data);
    }

    /**
     * 新增商品报损
     * @param $data
     */
    public function addGoodsReportLoss($data)
    {
        return M('goods_report_loss')->add($data);
    }

    /**
     * 删除商品报损
     * @param $where
     * @param $data
     */
    public function deleteGoodsReportLoss($where, $data)
    {
        $grlm = M('goods_report_loss');

        $goodsReportLossInfo = $grlm->where($where)->find();
        if (empty($goodsReportLossInfo)) return false;

        return $grlm->where($where)->save($data);
    }

    /**
     * 盘点商品
     * @param $data
     * @return mixed
     */
    public function addInventoryGoods($data)
    {
        return M('inventory_record')->add($data);
    }

    /**
     * 入库商品
     * @param $data
     * @return mixed
     */
    public function addInWarehouseGoods($data)
    {
        return M('in_warehouse_record')->add($data);
    }

    /**
     * 完成子盘点任务
     * @param $user
     * @param $ilid
     * @return bool
     */
    public function completeChildInventory($user, $ciid)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $cim = M('child_inventory');
        $where = array('ciid' => $ciid, 'shopId' => $user['shopId'], 'uid' => $user['id'], 'ciFlag' => 1);
        $inventoryInfo = $cim->where($where)->find();
        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '子盘点任务不存在';
            return $apiRet;
        }
        if (in_array($inventoryInfo['state'], array(0))) {
            $apiRet['apiInfo'] = '子盘点任务未开始';
            return $apiRet;
        }
        if (in_array($inventoryInfo['state'], array(2))) {
            $apiRet['apiInfo'] = '子盘点任务已完成';
            return $apiRet;
        }
        $result = $cim->where($where)->save(array('state' => 2));
        //处理漏盘
        if ($result) {
            $irm = M('inventory_record');
            $irwhere = array('shopId' => $user['shopId'], 'ciid' => $ciid, 'iid' => $inventoryInfo['iid'], 'lid' => $inventoryInfo['lid'], 'uid' => $inventoryInfo['uid'], 'irFlag' => 1);
            $locationInfo = $this->getLocationInfoByLid(array('lid' => $inventoryInfo['lid'], 'lFlag' => 1));
            $irwhere['lparentId'] = empty($locationInfo) ? 0 : $locationInfo['parentId'];
            $inventoryRecordList = $irm->where($irwhere)->field('goodsId')->select();
            $goodsId_arr = array();
            if (!empty($inventoryRecordList)) {
                foreach ($inventoryRecordList as $v) {
                    $goodsId_arr[] = $v['goodsId'];
                }
            }
            if (!empty($goodsId_arr)) {
                $lgwhere = array('goodsId' => array('not in', $goodsId_arr), 'lgFlag' => 1, 'lid' => $inventoryInfo['lid'], 'lparentId' => empty($locationInfo) ? 0 : $locationInfo['parentId']);
                $locationGoodsList = M('location_goods')->where($lgwhere)->select();
                //如果有数据，则说明有漏盘，将漏盘商品写入盘点记录表
                if (!empty($locationGoodsList)) {
                    $irdata = array();
                    foreach ($locationGoodsList as $v) {
                        $irdata[] = array(
                            'shopId' => $user['shopId'],
                            'iid' => $inventoryInfo['iid'],
                            'ciid' => $ciid,
                            'uid' => $user['id'],
                            'username' => $user['username'],
                            'lparentId' => empty($locationInfo) ? 0 : $locationInfo['parentId'],
                            'lid' => $inventoryInfo['lid'],
                            'goodsId' => $v['goodsId'],
                            'goodsSn' => $v['goodsSn'],
                            'num' => 0,
                            'createTime' => date('Y-m-d H:i:s'),
                            'state' => 1,
                            'isCheck' => 0,
                            'irFlag' => 1
                        );
                    }
                    if (!empty($irdata)) $irm->addAll($irdata);
                }
            }

            //如果子盘点任务全部完成，拥有更新库存权限的职员盘点的商品，会自动更新库存
            $this->autoUpdateGoodsNumber($user, $ciid);
        }
        $this->updateInventoryState($inventoryInfo['iid']);//子盘点任务全部完成更改盘点任务的状态
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 检测盘点任务是否完成,如果全部完成则更新盘点任务的状态
     * @param int $iid 盘点任务id
     * */
    public function updateInventoryState($iid)
    {
        $inventoryTab = M('inventory');
        $childTab = M('child_inventory');
        $where = [];
        $where['iid'] = $iid;
        $where['ciFlag'] = 1;
        $childNum = $childTab->where($where)->count();
        $where = [];
        $where['iid'] = $iid;
        $where['ciFlag'] = 1;
        $where['state'] = 2;
        $childNumYes = $childTab->where($where)->count();
        if ($childNumYes == $childNum) {
            $inventoryTab->where(['iid' => $iid])->save(['state' => 2]);
        }
    }

    /**
     * 如果子盘点任务全部完成，拥有更新库存权限的职员盘点的商品，会自动更新库存
     * 原来的，可用的
     */
    /*    public function autoUpdateGoodsNumber($user,$ciid){
            $cim = M('child_inventory');
            $inventoryInfo = $cim->where(array('ciid'=>$ciid,'shopId'=>$user['shopId'],'ciFlag'=>1))->find();
            if (empty($inventoryInfo)) exit();
            $where = array('shopId'=>$user['shopId'],'iid'=>$inventoryInfo['iid'],'ciFlag'=>1);
            //统计总任务数
            $taskCount = $this->countChildInventoryList($where);
            $where['state'] = 2;
            //统计完成任务数
            $completeTaskCount = $this->countChildInventoryList($where);

            if ($taskCount !== $completeTaskCount) exit();

            //如果子盘点任务全部完成，拥有更新库存权限的职员盘点的商品，会自动更新库存
            $irm = M('inventory_record');
            $gm = M('goods');
            $where['isUpdateStock'] = 1;
            //获取拥有更新商品库存权限的子盘点任务数据
            $haveUpdateGoodsNumberInventoryList = $this->getInventoryListByCondition($where);
            if (empty($haveUpdateGoodsNumberInventoryList)) exit();

            $data = array();
            $data_gsl = array();
            $where_t = array('shopId'=>$user['shopId'],'irFlag'=>1,'state'=>0,'isCheck'=>0);
            foreach ($haveUpdateGoodsNumberInventoryList as $v) {
                $where_t['ciid'] = $v['ciid'];
                $inventoryRecord = $irm->where($where_t)->select();
                if (empty($inventoryRecord)) continue;
                foreach ($inventoryRecord as $vir) {
                    $where_s = array('shopId'=>$user['shopId'],'irFlag'=>1,'state'=>0,'isCheck'=>0,'goodsId'=>$vir['goodsId'],'iid'=>$vir['iid']);
                    $goodsInfo = $irm->where($where_s)->find();
                    if (empty($goodsInfo)) continue;
                    //盘点前库存
                    $oNumber = $gm->where(array('goodsId'=>$vir['goodsId']))->getField('goodsStock');

                    //盘点后库存
                    $where_n = array('shopId'=>$vir['shopId'],'irFlag'=>1,'state'=>0,'isCheck'=>0,'goodsId'=>$vir['goodsId'],'iid'=>$vir['iid']);
                    $nNumber = $irm->where($where_n)->sum('num');

                    $num = $nNumber-$oNumber+$oNumber;
                    $result = $gm->where(array('goodsId'=>$vir['goodsId']))->save(array('goodsStock'=>$num));

                    if ($result) {
                        //更改盘点商品状态为已核对
                        $irm->where($where_n)->save(array('isCheck'=>1));
                        $data[] = array(
                            'shopId'    =>  $vir['shopId'],
                            'iid'       =>  $inventoryInfo['iid'],
                            'goodsId'   =>  $vir['goodsId'],
                            'goodsSn'   =>  $vir['goodsSn'],
                            'oNumber'   =>  $oNumber,
                            'nNumber'   =>  $nNumber,
                            'createTime'=>  date('Y-m-d H:i:s'),
                            'irFlag'    =>  1
                        );
                        $data_gsl[] = array(
                            'shopId'    =>  $vir['shopId'],
                            'goodsId'   =>  $vir['goodsId'],
                            'num'       =>  $nNumber-$oNumber,
                            'type'      =>  1,
                            'createTime'    =>  date('Y-m-d H:i:s'),
                            'gslFlag'   =>  1
                        );
                    }
                }
            }
            if (!empty($data)) M('inventory_report')->addAll($data);
            if (!empty($data_gsl)) M('goods_stock_log')->addAll($data_gsl);
        }*/

    /**
     * 如果子盘点任务全部完成，拥有更新库存权限的职员盘点的商品，会自动更新库存
     */
    public function autoUpdateGoodsNumber($user, $ciid)
    {
        $cim = M('child_inventory');
        $inventoryInfo = $cim->where(array('ciid' => $ciid, 'shopId' => $user['shopId'], 'ciFlag' => 1))->find();
        if (!empty($inventoryInfo)) {
            $where = array('shopId' => $user['shopId'], 'iid' => $inventoryInfo['iid'], 'ciFlag' => 1);
            //统计总任务数
            $taskCount = $this->countChildInventoryList($where);
            $where['state'] = 2;
            //统计完成任务数
            $completeTaskCount = $this->countChildInventoryList($where);

            if ($taskCount === $completeTaskCount) {

                //如果子盘点任务全部完成，拥有更新库存权限的职员盘点的商品，会自动更新库存
                $irm = M('inventory_record');
                $gm = M('goods');
                $where['isUpdateStock'] = 1;
                //获取拥有更新商品库存权限的子盘点任务数据
                $haveUpdateGoodsNumberInventoryList = $this->getInventoryListByCondition($where);
                if (!empty($haveUpdateGoodsNumberInventoryList)) {

                    $data = array();
                    $data_gsl = array();
                    $where_t = array('shopId' => $user['shopId'], 'irFlag' => 1, 'state' => 0, 'isCheck' => 0);
                    foreach ($haveUpdateGoodsNumberInventoryList as $v) {
                        $where_t['ciid'] = $v['ciid'];
                        $inventoryRecord = $irm->where($where_t)->select();
                        if (empty($inventoryRecord)) continue;
                        foreach ($inventoryRecord as $vir) {
                            $where_s = array('shopId' => $user['shopId'], 'irFlag' => 1, 'state' => 0, 'isCheck' => 0, 'goodsId' => $vir['goodsId'], 'iid' => $vir['iid']);
                            $goodsInfo = $irm->where($where_s)->find();
                            if (empty($goodsInfo)) continue;
                            //盘点前库存
                            $oNumber = $gm->where(array('goodsId' => $vir['goodsId']))->getField('goodsStock');
                            //后加兼容商品sku start
                            if (!empty($vir['skuId'])) {
                                $where = [];
                                $where['goodsId'] = $goodsInfo['goodsId'];
                                $where['skuId'] = $vir['skuId'];
                                $skuInfo = M('sku_goods_system')->where($where)->find();
                                if (!empty($skuInfo)) {
                                    if ($skuInfo['skuGoodsStock'] != -1) {
                                        $oNumber = $skuInfo['skuGoodsStock'];
                                    }
                                }
                            }
                            //后加兼容商品sku end


                            //盘点后库存
                            /*$where_n = array('shopId' => $vir['shopId'], 'irFlag' => 1, 'state' => 0, 'isCheck' => 0, 'goodsId' => $vir['goodsId'], 'iid' => $vir['iid']);
                            $nNumber = $irm->where($where_n)->sum('num');*/

                            //后加,兼容商品sku start
                            $where_n = [];
                            $where_n['shopId'] = $vir['shopId'];
                            $where_n['irFlag'] = 1;
                            $where_n['state'] = 0;
                            $where_n['isCheck'] = 0;
                            $where_n['goodsId'] = $vir['goodsId'];
                            $where_n['skuId'] = $vir['skuId'];
                            $where_n['iid'] = $vir['iid'];
                            $nNumber = $irm->where($where_n)->sum('num');
                            //后加,兼容商品sku end

                            $num = $nNumber - $oNumber + $oNumber;
                            $result = $gm->where(array('goodsId' => $vir['goodsId']))->save(array('goodsStock' => $num));

                            if ($result) {
                                //更改盘点商品状态为已核对
                                $irm->where($where_n)->save(array('isCheck' => 1));
                                $data[] = array(
                                    'shopId' => (int)$vir['shopId'],
                                    'iid' => (int)$inventoryInfo['iid'],
                                    'goodsId' => (int)$vir['goodsId'],
                                    'goodsSn' => $vir['goodsSn'],
                                    'skuId' => (int)$vir['skuId'],
                                    'oNumber' => (int)$oNumber,
                                    'nNumber' => (int)$nNumber,
                                    'createTime' => date('Y-m-d H:i:s'),
                                    'irFlag' => 1
                                );
                                $data_gsl[] = array(
                                    'shopId' => (int)$vir['shopId'],
                                    'goodsId' => (int)$vir['goodsId'],
                                    'skuId' => (int)$vir['skuId'],
                                    'num' => $nNumber - $oNumber,
                                    'type' => 1,
                                    'createTime' => date('Y-m-d H:i:s'),
                                    'gslFlag' => 1
                                );
                            }
                        }
                    }
                    if (!empty($data)) M('inventory_report')->addAll($data);
                    if (!empty($data_gsl)) M('goods_stock_log')->addAll($data_gsl);
                }
            }
        }
    }

    /**
     * 获取所有商品
     * @return mixed 键值对，格式：array('goodsId'=>goodsStock)
     */
    public function getGoodsKeyValueArr()
    {
        return M('goods')->getField('goodsId,goodsStock');
    }

    /**
     * 统计盘点任务数
     * @param $where
     * @return mixed
     */
    public function countChildInventoryList($where)
    {
        return M('child_inventory')->where($where)->count();
    }

    /**
     * 根据条件获取货位商品
     * @param $where
     */
    public function getLocationGoodsInfo($where)
    {
        return M('location_goods')->where($where)->find();
    }

    /**
     * 新增货位商品
     * @param $where
     */
    public function addLocationGoods($data)
    {
        return M('location_goods')->add($data);
    }

    /**
     * 根据条件获取入库商品记录信息
     * @param $where
     * @return mixed
     */
    public function getInWarehouseRecordInfo($where)
    {
        return M('in_warehouse_record')->where($where)->find();
    }

    /**
     * 商品盘点报表
     * @param $param
     * @return array
     */
    public function getInventoryReport($param)
    {
        $where = " ir.shopId = " . $param['shopId'] . " and ir.irFlag = 1 ";
        $sql = "select ir.*,g.goodsName,i.name from __PREFIX__inventory_report as ir inner join __PREFIX__inventory as i on ir.iid = i.iid inner join __PREFIX__goods as g on ir.goodsId = g.goodsId where " . $where . " order by ir.createTime desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);
        if (!empty($list['root'])) {
            $list['root'] = getCartGoodsSku($list['root']);
        }
        return $list;
    }


    /**
     * 完成盘点任务
     * @param $shopId
     * @param $ilid
     * @return bool
     */
    public function completeInventory($shopId, $iid)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $im = M('inventory');
        $where = array('iid' => $iid, 'shopId' => $shopId, 'iFlag' => 1);
        $inventoryInfo = $im->where($where)->find();
        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '盘点任务不存在';
            return $apiRet;
        }

        if (in_array($inventoryInfo['state'], array(0, 2))) {
            $apiRet['apiInfo'] = '盘点任务 未开始 或 已完成';
            return $apiRet;
        }
        if (in_array($inventoryInfo['state'], array(3))) {
            $apiRet['apiInfo'] = '盘点任务已作废';
            return $apiRet;
        }

        //判断是否所有子盘点任务都已完成
        $where = array('shopId' => $shopId, 'iid' => $iid, 'ciFlag' => 1);
        //统计总任务数
        $taskCount = $this->countChildInventoryList($where);
        $where['state'] = 2;
        //统计完成任务数
        $completeTaskCount = $this->countChildInventoryList($where);

        //如果 总盘点任务数 != 已完成子盘点任务数,则说明子盘点任务还没有全部完成，此时不能完成总盘点任务
        if ($taskCount !== $completeTaskCount) {
            $apiRet['apiInfo'] = '子盘点任务没有全部完成';
            return $apiRet;
        }

        // --- 处理盘点商品库存 --- start ---
        $irm = M('inventory_record');
        $irwhere = array('shopId' => $shopId, 'state' => 0, 'isCheck' => 0, 'irFlag' => 1, 'iid' => $iid);
        $inventoryRecordList = $irm->where($irwhere)->field('goodsId,skuId,sum(num) as total_num')->group('goodsId')->select();
        if (!empty($inventoryRecordList)) {
            $data = array();
            $data_gsl = array();
            $gm = M('goods');
            foreach ($inventoryRecordList as $v) {

                $gwhere = array('goodsId' => $v['goodsId'], 'isSale' => 1, 'goodsFlag' => 1);
                $goodsInfo = $gm->where($gwhere)->find();
                if (empty($goodsInfo)) continue;

                //盘点前库存
                $oNumber = $gm->where(array('goodsId' => $v['goodsId']))->getField('goodsStock');
                //后加,兼容sku start
                if (!empty($v['skuId'])) {
                    $skuInfo = M('sku_goods_system')->where(['skuId' => $v['skuId']])->find();
                    if (!empty($skuInfo)) {
                        if ($skuInfo['skuGoodsStock'] != -1) {
                            $oNumber = $skuInfo['skuGoodsStock'];
                        }
                    }
                }
                //后加,兼容sku end
                //盘点后库存
                $nNumber = $v['total_num'];

                $num = $nNumber - $oNumber + $oNumber;
                $result = $gm->where($gwhere)->save(array('goodsStock' => $num));

                //后加,兼容sku start
                if (!empty($v['skuId'])) {
                    M('sku_goods_system')->where(['skuId' => $v['skuId']])->save(['skuGoodsStock' => $num]);
                }
                //后加,兼容sku end
                if ($result) {
                    //更改盘点商品状态为已核对
                    $irm->where(array('shopId' => $shopId, 'goodsId' => $v['goodsId'], 'state' => 0, 'isCheck' => 0, 'irFlag' => 1, 'iid' => $iid))->save(array('isCheck' => 1));
                    $data[] = array(
                        'shopId' => (int)$shopId,
                        'iid' => (int)$iid,
                        'goodsId' => (int)$goodsInfo['goodsId'],
                        'skuId' => (int)$v['skuId'],
                        'goodsSn' => $goodsInfo['goodsSn'],
                        'oNumber' => (int)$oNumber,
                        'nNumber' => (int)$nNumber,
                        'createTime' => date('Y-m-d H:i:s'),
                        'irFlag' => 1
                    );
                    $data_gsl[] = array(
                        'shopId' => $shopId,
                        'goodsId' => $goodsInfo['goodsId'],
                        'skuId' => $v['skuId'],
                        'num' => $nNumber - $oNumber,
                        'type' => 1,
                        'createTime' => date('Y-m-d H:i:s'),
                        'gslFlag' => 1
                    );
                }
            }
            if (!empty($data)) M('inventory_report')->addAll($data);
            if (!empty($data_gsl)) M('goods_stock_log')->addAll($data_gsl);
        }
        // --- 处理盘点商品库存 --- end ---

        $result = $im->where(array('shopId' => $shopId, 'iid' => $iid))->save(array('state' => 2));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 子盘点任务下的盘点商品列表
     * @param $param
     * @return array
     */
    public function getChildInventoryUnderInventoryGoodsList($param)
    {
        $inventoryInfo = M('child_inventory')->where(array('ciid' => $param['ciid'], 'ciFlag' => 1))->find();
        if (empty($inventoryInfo)) return false;
        $where = " ir.shopId = " . $param['shopId'] . " and ir.ciid = " . $param['ciid'] . " and ir.irFlag = 1 ";
        $sql = "select ir.*,g.goodsName,i.name from __PREFIX__inventory_record as ir inner join __PREFIX__inventory as i on ir.iid = i.iid inner join __PREFIX__goods as g on ir.goodsId = g.goodsId where " . $where . " order by ir.createTime desc ";
        $dataList = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        if (!empty($dataList['root'])) {
            $dataList['root'] = getCartGoodsSku($dataList['root']);
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            foreach ($dataList['root'] as $k => $v) {
                $dataList['root'][$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
                $dataList['root'][$k]['lName'] = $locationKeyValueArr[$v['lid']];
            }
        }

        return $dataList;
    }

    /**
     * 获取所有货位
     * @return mixed 键值对，格式：array('lid'=>name)
     */
    public function getLocationKeyValueArr()
    {
        return M('location')->getField('lid,name');
    }

    /**
     * 门店(待核对)盘点商品列表(主要用于核对商品库存)
     * @param $param
     * @return array
     */
    public function getShopInventoryGoodsList($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $im = M('inventory');
        $iwhere = array('shopId' => $param['shopId'], 'iid' => $param['iid'], 'iFlag' => 1);
        $inventoryInfo = $im->where($iwhere)->find();

        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '盘点任务不存在';
            return $apiRet;
        }

        $ciwhere = array('shopId' => $param['shopId'], 'iid' => $param['iid'], 'ciFlag' => 1);
        //总任务
        $task_count = $this->countChildInventoryList($ciwhere);
        $ciwhere['state'] = 2;
        //已完成任务数
        $complete_task_count = $this->countChildInventoryList($ciwhere);
        if ($task_count !== $complete_task_count) {
            $apiRet['apiInfo'] = '子盘点任务还没有全部完成';
            return $apiRet;
        }

        $where = " ir.shopId = " . $param['shopId'] . " and ir.iid = " . $param['iid'] . "  and ir.state = 0 and ir.isCheck = 0 and ir.irFlag = 1 ";
        $sql = "select ir.shopId,ir.goodsId,ir.skuId,ir.goodsSn,sum(ir.num) as total_num,g.goodsName from __PREFIX__inventory_record as ir inner join __PREFIX__goods as g on ir.goodsId = g.goodsId where " . $where . " group by ir.goodsId order by ir.goodsId desc ";
        $dataList = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        if (!empty($dataList['root'])) {
            $dataList['root'] = getCartGoodsSku($dataList['root']);
            $goodsKeyValueArr = $this->getGoodsKeyValueArr();
            foreach ($dataList['root'] as $k => $v) {
                $dataList['root'][$k]['goodsStock'] = $goodsKeyValueArr[$v['goodsId']];
            }
        }

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $dataList;

        return $apiRet;
    }

    /**
     * 盘点完成后，一键核对商品库存，并更改商品库存
     * 多个以逗号连接，但 goodsId 和 skuId 和 goodsNum 的数量应保持一致
     * @param $shopId
     * @param $goodsId
     * @param $skuId
     * @param $goodsNum
     * @return bool
     */
    public function checkGoodsNumber($shopId, $goodsId, $skuId, $goodsNum, $iid)
    {
        $goodsId_arr = explode(',', $goodsId);
        $skuId_arr = explode(',', $skuId);
        $goodsNum_arr = explode(',', $goodsNum);
//        if (empty($goodsId_arr) || empty($goodsNum_arr) || empty($skuId_arr)) return false;
        if (empty($goodsId_arr) || empty($goodsNum_arr)) return false;

        $gm = M('goods');
        $irm = M('inventory_record');
        $data = array();
        $data_gsl = array();
        foreach ($goodsId_arr as $k => $v) {
            $skuId = $skuId_arr[$k];
            $gwhere = array('goodsId' => $v, 'isSale' => 1, 'goodsFlag' => 1);
            $goodsInfo = $gm->where($gwhere)->find();
            if (empty($goodsInfo)) continue;

            //盘点前库存
            $oNumber = $gm->where($gwhere)->getField('goodsStock');
            if (!empty($skuId)) {
                $skuInfo = M('sku_goods_system')->where(['skuId' => $skuId])->find();
                if (!empty($skuInfo)) {
                    if ($skuInfo['skuGoodsStock'] != -1) {
                        $oNumber = $skuInfo['skuGoodsStock'];
                    }
                }
            }
            //盘点后库存
            $nNumber = $goodsNum_arr[$k];
            $nNumber = empty($nNumber) ? 0 : $nNumber;

            $num = $nNumber - $oNumber + $oNumber;
            $result = $gm->where($gwhere)->save(array('goodsStock' => $num));
            if ($goodsInfo['goodsStock'] == $num) {
                $result = 1;
            }
            if (!empty($skuInfo)) {
                if ($skuInfo['skuGoodsStock'] != -1) {
                    M('sku_goods_system')->where(['skuId' => $skuInfo['skuId']])->save(['skuGoodsStock' => $num]);
                }
            }
            if ($result) {
                //更改盘点商品状态为已核对
                $irm->where(array('shopId' => $shopId, 'goodsId' => $v, 'state' => 0, 'isCheck' => 0, 'irFlag' => 1, 'iid' => $iid))->save(array('isCheck' => 1));
                $data[] = array(
                    'shopId' => (int)$shopId,
                    'iid' => (int)$iid,
                    'goodsId' => (int)$goodsInfo['goodsId'],
                    'skuId' => (int)$skuId,
                    'goodsSn' => $goodsInfo['goodsSn'],
                    'oNumber' => (int)$oNumber,
                    'nNumber' => (int)$nNumber,
                    'createTime' => date('Y-m-d H:i:s'),
                    'irFlag' => 1
                );
                $data_gsl[] = array(
                    'shopId' => $shopId,
                    'goodsId' => $goodsInfo['goodsId'],
                    'skuId' => $skuId,
                    'num' => $nNumber - $oNumber,
                    'type' => 1,
                    'createTime' => date('Y-m-d H:i:s'),
                    'gslFlag' => 1
                );
            }
        }
        if (!empty($data)) M('inventory_report')->addAll($data);
        if (!empty($data_gsl)) M('goods_stock_log')->addAll($data_gsl);

        return true;
    }

    /**
     * 获取商品报损原因详情
     * @param $where
     * @return mixed
     */
    public function getGoodsReportLossDetail($where)
    {
        return M('report_loss_reason')->where($where)->find();
    }

    /**
     * 编辑商品报损原因
     * @param $where
     * @param $data
     * @return mixed
     */
    public function editGoodsReportLossReason($where, $data)
    {
        $rlrm = M('report_loss_reason');
        $rlrInfo = $rlrm->where($where)->find();
        if (empty($rlrInfo)) return false;
        $saveData = [];
        $saveData['shopId'] = null;
        $saveData['name'] = null;
        $saveData['rlrFlag'] = null;
        parm_filter($saveData, $data);
        $res = $rlrm->where($where)->save($saveData);
        if ($res !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加商品报损原因
     * @param $where
     * @param $data
     * @return mixed
     */
    public function addGoodsReportLossReason($data)
    {
        $saveData = [];
        $saveData['shopId'] = null;
        $saveData['name'] = null;
        $saveData['createTime'] = date('Y-m-d H:i:s', time());
        parm_filter($saveData, $data);
        return M('report_loss_reason')->add($saveData);
    }

    /**
     * 获取商品报损原因列表 - 带分页
     * @param $param
     */
    public function getGoodsReportLossReasonListWithPage($param)
    {
        $where = " shopId = " . $param['shopId'] . " and rlrFlag = 1 ";
        $sql = "select * from __PREFIX__report_loss_reason where " . $where . " order by createTime desc ";
        $list = $this->pageQuery($sql, $param['page'], $param['pageSize']);

        return $list;
    }

    /**
     * 获取商品报损原因列表 - 不带分页
     * @param $where
     * @return mixed
     */
    public function getGoodsReportLossReasonList($where)
    {
        return M('report_loss_reason')->where($where)->select();
    }

    /**
     * 校对(同意)报损商品
     * 不支持批量同意
     * @param $shopId
     * @param $grlid
     * @return bool
     */
    public function doCheckReportLossGoods($shopId, $grlid)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $grlm = M('goods_report_loss');
        $gm = M('goods');
        $gslm = M('goods_stock_log');
        $where_grl = array('shopId' => $shopId, 'grlid' => $grlid, 'grlFlag' => 1);
        $grlInfo = $grlm->where($where_grl)->find();
        if (empty($grlInfo)) {
            $apiRet['apiInfo'] = '报损商品不存在';
            return $apiRet;
        }
        $where_g = array('shopId' => $shopId, 'goodsId' => $grlInfo['goodsId'], 'goodsFlag' => 1);
        $gInfo = $gm->where($where_g)->find();
        if (empty($gInfo)) {
            $apiRet['apiInfo'] = '商品不存在';
            return $apiRet;
        }

        M()->startTrans();
        $result = $grlm->where($where_grl)->save(array('isCheck' => 1));
        $result1 = $gm->where($where_g)->setDec('goodsStock', $grlInfo['num']);
        $result2 = $gslm->add(array(
            'shopId' => $shopId,
            'goodsId' => $grlInfo['goodsId'],
            'num' => '-' . $grlInfo['num'],
            'type' => 2,
            'createTime' => date('Y-m-d H:i:s'),
            'gslFlag' => 1
        ));
        if ($result && $result1 && $result2) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            M()->commit();
        } else {
            M()->rollback();
        }

        return $apiRet;
    }

    /**
     * 获取职员键值对
     */
    public function getUserKeyValueArr()
    {
        return M('user')->getField('id,username');
    }

    /**
     * 盘点任务列表
     * state ,0：待盘点 1：盘点中 2：已完成 ,默认为0
     * @param $param
     * @return array
     */
    public function getUserInventoryList($param)
    {

        $where = array();
        $where['ci.uid'] = $param['userId'];
        $where['ci.state'] = $param['state'];
        $where['ci.ciFlag'] = 1;
        $list = M('child_inventory as ci')
            ->join('left join wst_inventory as i on ci.iid = i.iid')
            ->field('ci.*,i.name')
            ->where($where)
            ->order('ci.ciid asc')
            ->limit(($param['page'] - 1) * $param['pageSize'], $param['pageSize'])
            ->select();
        if (!empty($list)) {
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            foreach ($list as $k => $v) {
                $list[$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
                $list[$k]['lName'] = $locationKeyValueArr[$v['lid']];
            }
        }

        return $list;
    }

    /**
     * 修改子盘点任务状态为盘点中
     * @param $userId
     * @param $ciid
     * @return mixed
     */
    public function changeChildInventoryState($userId, $ciid)
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $cim = M('child_inventory');
        $where = array('ciid' => $ciid, 'uid' => $userId, 'ciFlag' => 1);
        $inventoryInfo = $cim->where($where)->find();
        if (empty($inventoryInfo)) {
            $apiRet['apiInfo'] = '子盘点任务不存在';
            return $apiRet;
        }
        if (in_array($inventoryInfo['state'], array(1))) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $cim->where($where)->save(array('state' => 1));
        if ($result) {
            $where = [];
            $where['iid'] = $inventoryInfo['iid'];
            $inventoryTab = M('inventory');
            $inventoryInfo = $inventoryTab->where($where)->find();
            if ($inventoryInfo['state'] == 0) {
                $inventoryTab->where($where)->save(['state' => 1]);
            }
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * 盘点记录详情
     * @param $param
     */
    public function getInventoryRecordDetail($param)
    {
        $where = array();
        $where['ir.uid'] = $param['userId'];
        $where['ir.ciid'] = $param['ciid'];
        $where['ir.irFlag'] = 1;
        $list = M('inventory_record as ir')
            ->field('ir.*,ci.state as inventoryState,g.goodsName,g.goodsImg,g.goodsThums')
            ->join('wst_child_inventory as ci on ir.ciid = ci.ciid')
            ->join('wst_goods as g on ir.goodsId = g.goodsId')
            ->where($where)
            ->order('ir.createTime desc')
            ->limit(($param['page'] - 1) * $param['pageSize'], $param['pageSize'])
            ->select();
        $list = getCartGoodsSku($list);
        if (!empty($list)) {
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            foreach ($list as $k => $v) {
                $list[$k]['lparentName'] = $locationKeyValueArr[$v['lparentId']];
                $list[$k]['lName'] = $locationKeyValueArr[$v['lid']];
            }
        }

        return $list;
    }

    /**
     * 修改盘点商品
     * @param $where
     * @param $data
     */
    public function updateInventoryGoods($where, $data)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $m = M('inventory_record');
        $inventory_record_info = $m->where($where)->find();
        if (empty($inventory_record_info)) {
            $apiRet['apiInfo'] = '商品不存在';
            return $apiRet;
        }
        if ($inventory_record_info['num'] == $data['num']) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $m->where($where)->save($data);
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 删除盘点商品
     * @param $where
     * @param $data
     */
    public function deleteInventoryGoods($where, $data)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $m = M('inventory_record');
        $inventory_record_info = $m->where($where)->find();
        if (empty($inventory_record_info)) {
            $apiRet['apiInfo'] = '商品不存在';
            return $apiRet;
        }
        if ($inventory_record_info['irFlag'] == -1) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            return $apiRet;
        }
        $result = $m->where($where)->save($data);
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        return $apiRet;
    }

    /**
     * 报损历史
     */
    public function getGoodsReportLossHistory($param)
    {
        $where = array();
        $where['grl.uid'] = $param['user']['id'];
        $where['grl.shopId'] = $param['user']['shopId'];
        $where['grl.grlFlag'] = 1;
        $list = M('goods_report_loss as grl')
            ->field('grl.*,g.goodsName,g.goodsImg,g.goodsThums')
            ->join('left join wst_goods as g on grl.goodsId = g.goodsId')
            ->where($where)
            ->order('createTime desc')
            ->limit(($param['page'] - 1) * $param['pageSize'], $param['pageSize'])
            ->select();
        $list = getCartGoodsSku($list);
        return $list;
    }

    /**
     * 报损详情
     * 盘点端
     */
    public function goodsReportLossDetail($param)
    {
        $where = array();
        $where['grl.uid'] = $param['user']['id'];
        $where['grl.shopId'] = $param['user']['shopId'];
        $where['grl.grlid'] = $param['grlid'];
        $where['grl.grlFlag'] = 1;
        $detail = M('goods_report_loss as grl')
            ->field('grl.*,g.goodsName,g.goodsImg,g.goodsThums')
            ->join('left join wst_goods as g on grl.goodsId = g.goodsId')
            ->where($where)
            ->find();

        if (!empty($detail)) {
            $detail = getCartGoodsSku($detail);
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            $detail['lparentName'] = $locationKeyValueArr[$detail['lparentId']];
            $detail['lName'] = $locationKeyValueArr[$detail['lid']];

            $detail['imgs_list'] = explode(';', $detail['imgs']);
        }

        return $detail;
    }

    /**
     * 报损详情
     * 管理端
     */
    public function goodsReportLossDetailForShop($param)
    {
        $where = array();
        $where['grl.shopId'] = $param['shopId'];
        $where['grl.grlid'] = $param['grlid'];
        $where['grl.grlFlag'] = 1;
        $detail = M('goods_report_loss as grl')
            ->field('grl.*,g.goodsName,g.goodsImg,g.goodsThums')
            ->join('left join wst_goods as g on grl.goodsId = g.goodsId')
            ->where($where)
            ->find();

        if (!empty($detail)) {
            $locationKeyValueArr = $this->getLocationKeyValueArr();
            $detail['lparentName'] = $locationKeyValueArr[$detail['lparentId']];
            $detail['lName'] = $locationKeyValueArr[$detail['lid']];

            $detail['imgs_list'] = explode(';', $detail['imgs']);
        }

        return $detail;
    }

    /**
     * 商品报损原因列表
     * @param $shopId
     */
    public function getReportLossReasonList($shopId)
    {
        $where = array('shopId' => $shopId, 'rlrFlag' => 1);
        return M('report_loss_reason')->where($where)->select();

    }

    /**
     * 货位详情
     * @param array $params
     * <p>
     * int lid 货位id
     * string name 货位名称
     * int parentId 父id
     * int shopId
     * </p>
     * @return array $data
     */
    public function getLocationDetail(array $params)
    {
        $tab = M('location');
        $where = [];
        $where['shopId'] = null;
        $where['lid'] = null;
        $where['lFlag'] = 1;
        $where['name'] = null;
        $where['parentId'] = null;
        parm_filter($where, $params);
        $data = $tab->where($where)->find();
        return (array)$data;
    }

    /**
     * 盘点库存-获取门店商品
     * @param array $params <p>
     * int shop_id 门店id
     * string keyword 商品关键字 PS:当前需求不支持SKU相关搜索
     * int shopCatId1 门店一级分类id
     * int screening_criteria 筛选(1:根据商品名排序 2:只看有库存商品)
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * @return array
     * */
    public function getShopGoods(array $params, $login_info)
    {
        //PS:该方法忽略分页条数的正确性，保证数据的可用性即可
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $screening_criteria = (int)$params['screening_criteria'];
        $where = " goods.shopId={$shop_id} and goods.goodsFlag=1 and goods.isBecyclebin=0";
        $where_find = array();
        $where_find['goods.shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return array('=', "{$params['shopCatId1']}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where} and {$where_find} ";
        }
        if (!empty($params['keyword'])) {
            $where_info .= " and (goods.goodsName like '%{$params['keyword']}%' or goods.goodsSn like '%{$params['keyword']}%' or system.skuBarcode like '%{$params['keyword']}%' ) ";
        }
        $sort_field = 'goods.shopGoodsSort';
        $sort_value = 'desc';
        if ($screening_criteria == 1) {
            //按商品名排序
            $sort_field = 'CONVERT(goods.goodsName USING gbk)';
            $sort_value = "";
        } elseif ($screening_criteria == 2) {
            //只看有库存商品
            $where_info .= " and (goods.goodsStock > 0 or system.skuGoodsStock > 0 )";
        }
        $field = "goods.goodsId,goods.shopId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.goodsStock,goods.SuppPriceDiff,goods.weightG,goods.shopCatId1,goods.shopCatId2,goods.unit";
        $goods_model = new GoodsModel();
        $goods_list = $goods_model
            ->alias('goods')
            ->join('left join wst_sku_goods_system system on system.goodsId=goods.goodsId ')
            ->where($where_info)
            ->field($field)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->group('goods.goodsId')
            ->order("{$sort_field} {$sort_value}")
            ->select();
        if (!empty($goods_list)) {
            $result = array();
            $key = 0;
            foreach ($goods_list as $item) {
                $goods_info = getGoodsSku($item);
                if ($goods_info['hasGoodsSku'] < 1) {
                    $item['hasGoodsSku'] = 0;
                    $item['skuId'] = 0;
                    $item['sku_spec_attr'] = '';
                    $item['goodsStock'] = (float)$item['goodsStock'];
                    //无sku
                    $result[$key] = $item;
                    $key++;
                } else {
                    //有sku
                    $sku_list = $goods_info['goodsSku']['skuList'];
                    foreach ($sku_list as $sku_val) {
                        $item['goodsSn'] = $sku_val['systemSpec']['skuBarcode'];
                        $item['goodsStock'] = $sku_val['systemSpec']['skuGoodsStock'];
                        $item['goodsImg'] = $sku_val['systemSpec']['skuGoodsImg'];
                        $item['hasGoodsSku'] = 1;
                        $item['skuId'] = (int)$sku_val['skuId'];
                        $item['sku_spec_attr'] = $sku_val['selfSpecStr'];
                        $item['goodsStock'] = (float)$item['goodsStock'];
                        $item['unit'] = $sku_val['systemSpec']['unit'];
                        if ($item['goodsStock'] <= 0 && $screening_criteria == 2) {
                            continue;
                        }
                        $result[$key] = $item;
                        $key++;
                        if (!empty($params['keyword']) && $params['keyword'] == $item['goodsSn']) {//如果输入的关键字与SKU商品的编码一样直接返回一条即可 注：临时性修复手段
                            $result = array(
                                $item
                            );
                            break;
                        }
                    }
                }
            }
        }

        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        foreach ($result as &$item) {
            $goods_id = (int)$item['goodsId'];
            $sku_id = (int)$item['skuId'];
            $item['old_stock'] = (float)$item['goodsStock'];//原库存
            $item['current_stock'] = (float)$item['goodsStock'];//现库存
            $item['checked'] = 0;//选中状态(0:未选中 1:已选中)
            $item['profit_loss'] = 0;//盈亏数
            $where = array(
                'inventory_user_type' => $inventory_user_type,
                'inventory_user_id' => $inventory_user_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id
            );
            $cache_detail = $inventory_module->getCacheDetailByParams($where);
            if (!empty($cache_detail)) {
                $item['current_stock'] = (float)$cache_detail['current_stock'];//现库存
                $item['checked'] = 1;//选中状态(0:未选中 1:已选中)
                $item['profit_loss'] = (float)bc_math($cache_detail['current_stock'], $item['old_stock'], 'bcsub', 3);
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 盘点库存-扫码搜索商品
     * @param int $shop_id 门店id
     * @param string $code 商品编码
     * @return array
     * */
    public function searchGoodsInfoByCode(int $shop_id, string $code, $login_info)
    {
        $response = LogicResponse::getInstance();
        $goods_model = new GoodsModel();
        $system_model = new SkuGoodsSystemModel();
        //处理称重条码-start
        $code_arr = explode('CZ-', $code);
        if (count($code_arr) == 2) {
            //称重条码
            $goods_module = new GoodsModule();
            $barcode_result = $goods_module->getBarcodeInfoByCode($shop_id, $code);
            if ($barcode_result['code'] == ExceptionCodeEnum::SUCCESS) {
                $barcode_info = $barcode_result['data'];
                if ($barcode_info['skuId'] > 0) {
                    $sku_system_result = $goods_module->getSkuSystemInfoById($barcode_info['skuId']);
                    $code = $sku_system_result['data']['skuBarcode'];
                } else {
                    $goods_result = $goods_module->getGoodsInfoById($barcode_info['goodsId']);
                    $code = $goods_result['data']['goodsSn'];
                }
            }
        }
        //处理称重条码-end
        $where = array();
        $where['goods.isBecyclebin'] = 0;
        $where['goods.goodsFlag'] = 1;
        $where['goods.shopId'] = $shop_id;
        $where['system.dataFlag'] = 1;
        $where['system.skuBarcode'] = $code;
        $field = 'goods.goodsId,goods.goodsSn,goods.goodsName,goods.shopCatId1,goods.shopCatId2,goods.goodsStock,goods.SuppPriceDiff,goods.weightG,goods.unit';
        $field .= ',system.skuId,system.skuBarcode,system.skuGoodsImg,system.skuBarcode,system.skuGoodsStock,system.unit';
        $system_info = $system_model
            ->alias('system')
            ->join('left join wst_goods goods on goods.goodsId=system.goodsId')
            ->where($where)
            ->field($field)
            ->find();
        $data = array();
        if (!empty($system_info)) {
            $system_info['sku_spec_attr'] = '';
            $sku_self_model = new SkuGoodsSelfModel();
            $sku_id = $system_info['skuId'];
            $where = array(
                'se.skuId' => $sku_id,
                'se.dataFlag' => 1,
                'sp.dataFlag' => 1,
                'sr.dataFlag' => 1
            );
            $self_list = $sku_self_model->alias('se')->where($where)
                ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                ->where($where)
                ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                ->order('sp.sort asc')
                ->select();
            if (!empty($self_list)) {
                foreach ($self_list as $val) {
                    $system_info['sku_spec_attr'] .= $val['attrName'] . "，";
                }
            }
            $system_info['sku_spec_attr'] = rtrim($system_info['sku_spec_attr'], '，');
            $data = $system_info;
            $data['hasGoodsSku'] = 1;
            $data['skuId'] = (int)$system_info['skuId'];
            $data['goodsSn'] = $system_info['skuBarcode'];
            $data['goodsStock'] = (float)$system_info['skuGoodsStock'];
            $data['goodsImg'] = (string)$system_info['skuGoodsImg'];
            unset($data['skuBarcode']);
            unset($data['skuGoodsStock']);
            unset($data['skuGoodsImg']);
        }
        if (empty($system_info)) {
            $field = 'goods.goodsId,goods.goodsSn,goods.goodsName,goods.goodsImg,goods.shopCatId1,goods.shopCatId2,goods.goodsStock,goods.SuppPriceDiff,goods.weightG';
            $where = array();
            $where['goods.isBecyclebin'] = 0;
            $where['goods.shopId'] = $shop_id;
            $where['goodsSn'] = $code;
            $where['goodsFlag'] = 1;
            $goods_info = $goods_model->alias('goods')->where($where)->field($field)->find();
            if (empty($goods_info)) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未找到该编码商品')->toArray();
            }
            $goods_info['goodsStock'] = (float)$goods_info['goodsStock'];
            $goods_info['hasGoodsSku'] = 0;
            $goods_info['skuId'] = 0;
            $goods_info['sku_spec_attr'] = '';
            $data = $goods_info;
        }
        if (empty($data)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未找到该编码商品')->toArray();
        }
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $goods_id = (int)$data['goodsId'];
        $sku_id = (int)$data['skuId'];
        $data['old_stock'] = (float)$data['goodsStock'];//原库存
        $data['current_stock'] = (float)$data['goodsStock'];//现库存
        $data['checked'] = 0;//选中状态(0:未选中 1:已选中)
        $data['profit_loss'] = 0;//盈亏数
        $where = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id
        );
        $cache_detail = $inventory_module->getCacheDetailByParams($where);
        if (!empty($cache_detail)) {
            $data['current_stock'] = (float)$cache_detail['current_stock'];//现库存
            $data['checked'] = 1;//选中状态(0:未选中 1:已选中)
            $data['profit_loss'] = (float)bc_math($cache_detail['current_stock'], $data['old_stock'], 'bcsub', 3);
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('成功')->toArray();
    }

    /**
     * 盘点库存-创建盘点单
     * @param array $params 业务参数
     * @param array $login_info
     * @return array
     * */
    public function createInventory(array $params, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $inventory_service_module = new InventoryModule();
        $shop_id = $login_info['shopId'];
        $goods_params = (array)$params['goods'];
        $goods_params = $inventory_service_module->getCacheListByInventoryUser($login_info['user_id'], $login_info['user_type']);//改动,由后端获取
        if (empty($goods_params)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请先去盘点")->toArray();
        }
        //校验商品数据-start
        $goods_module = new GoodsModule();
        foreach ($goods_params as &$item) {
            if (empty($item['goods_id'])) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误")->toArray();
            }
            $goods_result = $goods_module->getGoodsInfoById($item['goods_id'], 'goodsId,goodsName,goodsStock');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请检查商品信息是否有误")->toArray();
            }
            $goods_info = $goods_result['data'];
            $sku_id = (int)$item['sku_id'];
            $sku_info = array();
            if ($sku_id > 0) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                if ($sku_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}传递的sku信息有误")->toArray();
                }
                $sku_info = $sku_result['data'];
                if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_info['goodsName']}的sku信息不匹配")->toArray();
                }
                $sku_id_arr[] = $sku_id;
            }
            if ($sku_id > 0) {
                $item['goodsStock'] = (float)$sku_info['skuGoodsStock'];
                $item['sku_spec_attr'] = $sku_info['skuSpecAttr'];
            } else {
                $item['goodsStock'] = (float)$goods_info['goodsStock'];
                $item['sku_spec_attr'] = '';
            }
        }
        unset($item);
        $goods_id_arr = array_column($goods_params, 'goods_id');
        $unique_goods_id = array_unique($goods_id_arr);
        $field = 'goodsId,goodsName';
        $goods_list_result = $goods_module->getGoodsListById($unique_goods_id, $field);
        if ($goods_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("请选择商品")->toArray();
        }
        $goods_list = $goods_list_result['data'];
        if (count($sku_id_arr) < 1) {
            $diff_goods_id = array_diff_assoc($goods_id_arr, $unique_goods_id);
            if (count($diff_goods_id) > 0) {
                foreach ($diff_goods_id as $val) {
                    foreach ($goods_list as $goods_val) {
                        if ($goods_val['goodsId'] == $val) {
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$goods_val['goodsName']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        } else {
            $unique_sku_id = array_unique($sku_id_arr);
            $sku_list_result = $goods_module->getSkuSystemListById($unique_sku_id);
            if ($sku_list_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("提交的商品sku信息有误")->toArray();
            }
            $sku_list = $sku_list_result['data'];
            $diff_sku_id = array_diff_assoc($sku_id_arr, $unique_sku_id);
            if (count($diff_sku_id) > 0) {
                foreach ($diff_sku_id as $val) {
                    foreach ($sku_list as $sku_val) {
                        if ($sku_val['skuId'] == $val) {
                            $current_goods_info = array();
                            foreach ($goods_list as $goods_val) {
                                if ($sku_val['goodsId'] == $goods_val['goodsId']) {
                                    $current_goods_info = $goods_val;
                                    break;
                                }
                            }
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品{$current_goods_info['goodsName']}提交的sku属性{$sku_val['skuSpecAttr']}存在重复数据，请去除重复数据")->toArray();
                        }
                    }
                }
            }
        }
        //校验商品数据-end
        $total_goods_num = (int)count($goods_params);
        $old_stock_total = array_sum(array_column($goods_params, 'goodsStock'));
        $current_stock_total = array_sum(array_column($goods_params, 'current_stock'));
        $total_profit_loss = bc_math($current_stock_total, $old_stock_total, 'bcsub', 2);//盘盈盘亏
        $bill_data = array(
            'shop_id' => $shop_id,
            'total_goods_num' => $total_goods_num,
            //'total_profit_loss' => $total_profit_loss,
            'total_profit_loss' => $total_profit_loss,
            'inventory_user_type' => $login_info['user_type'],
            'inventory_user_id' => $login_info['user_id'],
            'inventory_user_name' => $login_info['user_username'],
            'inventory_time' => $params['inventory_time'],
            'remark' => (string)$params['remark'],
        );
        $m = new Model();
        $inventory_result = $inventory_service_module->addInventoryBill($bill_data, $m);
        if ($inventory_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("创建盘点单失败")->toArray();
        }
        $bill_id = $inventory_result['data']['bill_id'];
        $relation = array();
        foreach ($goods_params as $val) {
            //清空缓存数据
            $cache_detail = $inventory_service_module->getCacheDetailByParams(array(
                'inventory_user_type' => $login_info['user_type'],
                'inventory_user_id' => $login_info['user_id'],
                'goods_id' => $val['goods_id'],
                'sku_id' => $val['sku_id'],
            ));
            if (empty($cache_detail)) {
                $m->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("创建盘点单失败")->toArray();
            }
            $save_cache = array(
                'cache_id' => $cache_detail['cache_id'],
                'inventory_user_type' => $login_info['user_type'],
                'inventory_user_id' => $login_info['user_id'],
                'goods_id' => $val['goods_id'],
                'sku_id' => $val['sku_id'],
                'is_delete' => 1,
            );
            $inventory_service_module->saveInventoryCache($save_cache);
            $info = array();
            $info['bill_id'] = $bill_id;
            $info['goods_id'] = $val['goods_id'];
            $info['sku_id'] = $val['sku_id'];
            $info['sku_spec_attr'] = $val['sku_spec_attr'];
            $info['old_stock'] = $val['goodsStock'];
            $info['current_stock'] = $val['current_stock'];
            $info['profit_loss'] = bc_math($info['current_stock'], $info['old_stock'], 'bcsub', 2);
            $info['create_time'] = time();
            $relation[] = $info;
        }
        $relation_model = new InventoryBillRelationModel();
        $insert_res = $relation_model->addAll($relation);
        if (!$insert_res) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("盘点失败")->toArray();
        }
        $m->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
    }

    /**
     * 库存盘点-获取盘点记录列表
     * @param array $params <p>
     * int shop_id 门店id
     * int inventory_user_id 盘点人id
     * string bill_no 单号
     * date start_date 开始日期
     * date end_date 结束日期
     * int page 页码
     * int pageSize 分页条数
     * </p>
     * */
    public function getInventoryBillList(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $inventory_user_id = $params['inventory_user_id'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " shop_id={$shop_id} and inventory_user_id={$inventory_user_id}";
        $where_find = array();
        $where_find['bill.inventory_time'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            $params['start_date'] = $params['start_date'] . ' 00:00:00';
            $params['end_date'] = $params['end_date'] . ' 23:59:59';
            return array('between', "{$params['start_date']} ' and '{$params['end_date']}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = "{$where} and $where_find ";
        }
        if (!empty($params['keyword'])) {
            $where_info .= " and (goods.goodsName like '%{$params['keyword']}%' or bill.bill_no like '%{$params['keyword']}%') ";
        }
        $inventory_model = new InventoryBillModel();
        $field = 'bill.bill_id,bill.bill_no,bill.total_goods_num,bill.total_profit_loss,bill.inventory_user_name,bill.inventory_time,bill.remark,bill.confirm_status,bill.confirm_time,bill.confirm_user_name,bill.create_time';
        $list = $inventory_model
            ->alias('bill')
            ->where($where_info)
            ->join('left join wst_inventory_bill_relation relation on relation.bill_id=bill.bill_id')
            ->join('left join wst_goods goods on goods.goodsId=relation.goods_id')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->group('bill.bill_id')
            ->order('inventory_time desc')
            ->field($field)
            ->select();
        foreach ($list as &$item) {
            $item['total_goods_num'] = $item['total_goods_num'];
            $item['total_profit_loss'] = (float)$item['total_profit_loss'];
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if ($item['confirm_status'] != 1) {
                $item['confirm_time'] = '';
            } else {
                $item['confirm_time'] = date('Y-m-d H:i:s', $item['confirm_time']);
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($list)->setMsg('成功')->toArray();
    }

    /**
     * 报损-新增报损
     * @param array $params
     * @param array $login_info
     * @return array
     * */
    public function createLoss(array $params, array $login_info)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $login_info['shopId'];
        $goods_id = (int)$params['goods_id'];
        $sku_id = (int)$params['sku_id'];
        $two_lid = (int)$params['two_lid'];
        $loss_num = (float)$params['loss_num'];
        $reason_id = (int)$params['reason_id'];
        $remark = (string)$params['remark'];
        $loss_pic = (string)rtrim($params['loss_pic'], ',');
        $goods_module = new GoodsModule();
        $goods_result = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsName,goodsSn');
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('提交的报损商品信息有误')->toArray();
        }
        $goods_info = $goods_result['data'];
        $sku_spec_attr = '';
        $loss_reason = '';
        $code = $goods_info['goodsSn'];
        if ($sku_id > 0) {
            $sku_system_result = $goods_module->getSkuSystemInfoById($sku_id);
            if ($sku_system_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('提交的商品SKU信息有误')->toArray();
            }
            $sku_info = $sku_system_result['data'];
            if ($sku_info['goodsId'] != $goods_info['goodsId']) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('提交的商品信息和SKU信息不匹配')->toArray();
            }
            $sku_spec_attr = $sku_info['skuSpecAttr'];
            $code = $sku_info['skuBarcode'];
        }
        if ($reason_id > 0) {
            $reason_module = new ReportLossReasonModule();
            $reason_result = $reason_module->getLossReasonInfoById($reason_id);
            if ($reason_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('请提交正确的报损原因')->toArray();
            }
            $loss_reason = $reason_result['data']['name'];
        }
        if (!empty($loss_pic)) {
            $loss_pic_arr = explode(',', $loss_pic);
            if (count($loss_pic_arr) > 5) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('最多上传5张凭证照片')->toArray();
            }
        }
        $one_lid = 0;
        if ($two_lid > 0) {
            $location_module = new LocationModule();
            $location_result = $location_module->getLocationInfoById($two_lid);
            if ($location_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('货位信息不正确')->toArray();
            }
            $location_info = $location_result['data'];
            $one_lid = $location_info['parentId'];
        }
        $save = array(
            'shop_id' => $shop_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id,
            'code' => $code,
            'one_lid' => $one_lid,
            'two_lid' => $two_lid,
            'sku_spec_attr' => $sku_spec_attr,
            'loss_num' => $loss_num,
            'loss_reason' => $loss_reason,
            'remark' => $remark,
            'loss_pic' => $loss_pic,
            'inventory_user_type' => $login_info['user_type'],
            'inventory_user_id' => $login_info['user_id'],
            'inventory_user_name' => $login_info['user_username'],
        );
        $inventory_module = new InventoryLossModule();
        $m = new Model();
        $insert_res = $inventory_module->addLoss($save, $m);
        if ($insert_res['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('新增报损失败')->toArray();
        }
        $result = array(
            'loss_id' => $insert_res['data']['loss_id']
        );
        $m->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 报损-报损记录列表
     * @param array $params <p>
     * int inventory_user_id 报损人员id
     * string keyword 关键字(商品名,编码)
     * date start_date 开始日期
     * date end_date 结束日期
     * int page
     * int pageSize
     * </p>
     * @return array
     * */
    public function getLossList(array $params)
    {
        $response = LogicResponse::getInstance();
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $inventory_user_id = $params['inventory_user_id'];
        $where = " loss.inventory_user_id={$inventory_user_id} and loss.is_delete=0";
        $where_find = array();
        $where_find['loss.create_time'] = function () use ($params) {
            if (empty($params['start_date']) || empty($params['end_date'])) {
                return null;
            }
            $start_date = strtotime($params['start_date'] . ' 00:00:00');
            $end_date = strtotime($params['end_date'] . ' 23:59:59');
            return array('between', "{$start_date}' and '{$end_date}", 'and');
        };
        where($where_find);
        if (empty($where_find) || $where_find == ' ') {
            $where_info = $where;
        } else {
            $where_find = rtrim($where_find, ' and');
            $where_info = " {$where} and {$where_find} ";
        }
        if (!empty($params['keyword'])) {
            $where_info .= " and (goods.goodsName like '%{$params['keyword']}%' or loss.code like '%{$params['keyword']}%')";
        }
        $model = new InventoryLossModel();
        $field = 'loss.loss_id,loss.code,loss.sku_spec_attr,loss.loss_num,loss.loss_reason,loss.remark,loss.confirm_status,loss.confirm_user_name,loss.confirm_time,loss.create_time,loss.sku_id,loss.goods_id,loss.inventory_user_name';
        $field .= ',goods.goodsName,goods.goodsImg,goods.unit';
        $result = $model
            ->alias('loss')
            ->join('left join wst_goods goods on goods.goodsId=loss.goods_id')
            ->field($field)
            ->where($where_info)
            ->order('loss.loss_id desc')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();
        $goods_module = new GoodsModule();
        foreach ($result as &$item) {
            $item['loss_num'] = (float)$item['loss_num'];
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if ($item['confirm_status'] != 1) {
                $item['confirm_time'] = '';
            } else {
                $item['confirm_time'] = date('Y-m-d H:i:s', $item['confirm_time']);
            }
            if ($item['sku_id'] > 0) {
                $sku_system_result = $goods_module->getSkuSystemInfoById($item['sku_id']);
                if ($sku_system_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    continue;
                }
                $sku_info = $sku_system_result['data'];
                $item['unit'] = $sku_info['unit'];
                if (!empty($sku_info['skuGoodsImg']) && $sku_info['skuGoodsImg'] != -1) {
                    $item['goodsImg'] = $sku_info['skuGoodsImg'];
                }
            }
        }
        unset($item);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
    }

    /**
     * 盘点库存缓存-递增现库存数量
     * @param array $login_info 登陆者信息
     * @param int $goods_id 商品id
     * @param int $sku_id 规格id
     * @param float $stock 递增的库存数量
     * @return array
     * */
    public function incInventoryCache(array $login_info, int $goods_id, int $sku_id, $stock)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $where = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id
        );
        $cache_detail = $inventory_module->getCacheDetailByParams($where, 'cache_id,current_stock');
        $goods_module = new GoodsModule();
        $goods_result = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsStock');
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goods_detail = $goods_result['data'];
        $old_stock = (float)$goods_detail['goodsStock'];
        if (!empty($sku_id)) {
            $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
            $sku_detail = $sku_result['data'];
            $old_stock = (float)$sku_detail['skuGoodsStock'];
        }
        $save = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id,
            'current_stock' => (float)bc_math($stock, $old_stock, 'bcadd', 3),
        );
        $m = M();
        $m->startTrans();
        if (empty($cache_detail)) {
            //新增
            $save_res = $inventory_module->saveInventoryCache($save, $m);
            $cache_id = (int)$save_res;
        } else {
            //递增盘点现库存数量
            $cache_id = $cache_detail['cache_id'];
            $save_res = $inventory_module->incCacheCurrentStock($cache_id, $stock, $m);
        }
        if (!$save_res) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '增加失败');
        }
        //返回当前商品的原库存,现库存,盈亏数-start
        $current_goods_cache_total = $inventory_module->getCurrentGoodsCacheTotal($cache_id);
        //返回当前商品的原库存,现库存,盈亏数-end
        //返回该盘点人员当前盘点商品总数,总盈亏数-start
        $inventory_cache_total = $inventory_module->getInventoryUserCacheTotal($inventory_user_id, $inventory_user_type);
        //返回该盘点人员当前盘点商品总数,总盈亏数-end
        $result = array(
            'current_goods' => array(//当前商品
                'old_stock' => $current_goods_cache_total['old_stock'],//原库存
                'current_stock' => $current_goods_cache_total['current_stock'],//现库存
                'profit_loss' => $current_goods_cache_total['profit_loss'],//盈亏数
            ),
            'total_goods_num' => $inventory_cache_total['total_goods_num'],//缓存中商品总数
            'total_profit_loss' => $inventory_cache_total['total_profit_loss'],//缓存中总盈亏数
        );
        $m->commit();
        return returnData($result);
    }

    /**
     * 盘点库存缓存-递减现库存数量
     * @param array $login_info 登陆者信息
     * @param int $goods_id 商品id
     * @param int $sku_id 规格id
     * @param float $stock 递增的库存数量
     * @return array
     * */
    public function decInventoryCache(array $login_info, int $goods_id, int $sku_id, $stock)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $where = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id
        );
        $cache_detail = $inventory_module->getCacheDetailByParams($where, 'cache_id,current_stock');
        $goods_module = new GoodsModule();
        $goods_result = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsStock');
        if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goods_detail = $goods_result['data'];
        $old_stock = (float)$goods_detail['goodsStock'];
        if (!empty($sku_id)) {
            $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
            $sku_detail = $sku_result['data'];
            $old_stock = (float)$sku_detail['skuGoodsStock'];
        }
        $save = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id,
            'current_stock' => (float)bc_math($old_stock, $stock, 'bcsub', 3),
        );
        $m = M();
        $m->startTrans();
        if (empty($cache_detail)) {
            if ($stock > $old_stock) {
                $max_stock = (float)bc_math($old_stock, $cache_detail['current_stock'], 'bcsub', 3);
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "本次最多盘点数量{$max_stock}");
            }
            $save['current_stock'] = (float)bc_math($old_stock, $stock, 'bcsub', 3);
            //新增
            $save_res = $inventory_module->saveInventoryCache($save, $m);
            $cache_id = (int)$save_res;
        } else {
            $cache_detail['current_stock'] = (float)$cache_detail['current_stock'];
            //递减盘点现库存数量
            $cache_id = $cache_detail['cache_id'];
            $handle_stock = (float)bc_math($cache_detail['current_stock'], $stock, 'bcsub', 2);
            if ($handle_stock <= 0) {
                M()->rollback();
                $handle_stock = (float)bc_math($cache_detail['current_stock'], $stock, 'bcsub', 3);
                if ($handle_stock <= 0) {
                    //$max_stock = (float)bc_math($handle_stock, $stock, 'bcadd', 3);
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "盘点数量必须大于0");
                }
            }
            $save_res = $inventory_module->decCacheCurrentStock($cache_id, $stock, $m);
        }
        if (!$save_res) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '增加失败');
        }
        //返回当前商品的原库存,现库存,盈亏数-start
        $current_goods_cache_total = $inventory_module->getCurrentGoodsCacheTotal($cache_id);
        //返回当前商品的原库存,现库存,盈亏数-end
        //返回该盘点人员当前盘点商品总数,总盈亏数-start
        $inventory_cache_total = $inventory_module->getInventoryUserCacheTotal($inventory_user_id, $inventory_user_type);
        //返回该盘点人员当前盘点商品总数,总盈亏数-end
        $result = array(
            'current_goods' => array(//当前商品
                'old_stock' => $current_goods_cache_total['old_stock'],//原库存
                'current_stock' => $current_goods_cache_total['current_stock'],//现库存
                'profit_loss' => $current_goods_cache_total['profit_loss'],//盈亏数
            ),
            'total_goods_num' => $inventory_cache_total['total_goods_num'],//缓存中商品总数
            'total_profit_loss' => $inventory_cache_total['total_profit_loss'],//缓存中总盈亏数
        );
        $m->commit();
        return returnData($result);
    }

    /**
     * 盘点库存缓存-输入框方式加减盘点缓存的现库存数量
     * @param array $login_info 登陆者信息
     * @param int $goods_id 商品id
     * @param int $sku_id 规格id
     * @param float $stock 递增的库存数量
     * @return array
     * */
    public function inputInventoryCache(array $login_info, int $goods_id, int $sku_id, $stock)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $where = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id
        );
        $cache_detail = $inventory_module->getCacheDetailByParams($where, 'cache_id,current_stock');
        $save = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id,
            'current_stock' => (float)$stock,
        );
        $m = M();
        $m->startTrans();
        if (!empty($cache_detail)) {
            $save['cache_id'] = $cache_detail['cache_id'];
        }
        $save_res = $inventory_module->saveInventoryCache($save, $m);
        if (!$save_res) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '增加失败');
        }
        $cache_id = $save_res;
        //返回当前商品的原库存,现库存,盈亏数-start
        $current_goods_cache_total = $inventory_module->getCurrentGoodsCacheTotal($cache_id);
        //返回当前商品的原库存,现库存,盈亏数-end
        //返回该盘点人员当前盘点商品总数,总盈亏数-start
        $inventory_cache_total = $inventory_module->getInventoryUserCacheTotal($inventory_user_id, $inventory_user_type);
        //返回该盘点人员当前盘点商品总数,总盈亏数-end
        $result = array(
            'current_goods' => array(//当前商品
                'old_stock' => $current_goods_cache_total['old_stock'],//原库存
                'current_stock' => $current_goods_cache_total['current_stock'],//现库存
                'profit_loss' => $current_goods_cache_total['profit_loss'],//盈亏数
            ),
            'total_goods_num' => $inventory_cache_total['total_goods_num'],//缓存中商品总数
            'total_profit_loss' => $inventory_cache_total['total_profit_loss'],//缓存中总盈亏数
        );
        $m->commit();
        return returnData($result);
    }

    /**
     * 盘点库存缓存-取消状态/选中状态
     * @param array $login_info 登陆者信息
     * @param int $goods_id 商品id
     * @param int $sku_id 规格id
     * @param status 状态(0:取消 1:选中)
     * @return array
     * */
    public function checkedInventoryGoods(array $login_info, int $goods_id, int $sku_id, int $status)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $where = array(
            'inventory_user_type' => $inventory_user_type,
            'inventory_user_id' => $inventory_user_id,
            'goods_id' => $goods_id,
            'sku_id' => $sku_id
        );
        $cache_detail = $inventory_module->getCacheDetailByParams($where, 'cache_id,current_stock');
        if (empty($cache_detail) && $status == 0) {
            //未存在,取消
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '取消失败，未匹配到相关数据');
        }
        $goods_module = new GoodsModule();
        if (empty($cache_detail) && $status == 1) {
            //未存在,选中
            $goods_result = $goods_module->getGoodsInfoById($goods_id, 'goodsId,goodsStock');
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            $goods_detail = $goods_result['data'];
            $old_stock = (float)$goods_detail['goodsStock'];
            if (!empty($sku_id)) {
                $sku_result = $goods_module->getSkuSystemInfoById($sku_id);
                $sku_detail = $sku_result['data'];
                $old_stock = (float)$sku_detail['skuGoodsStock'];
            }
            $save = array(
                'inventory_user_type' => $inventory_user_type,
                'inventory_user_id' => $inventory_user_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'current_stock' => (float)$old_stock,
            );
        }
        if (!empty($cache_detail) && $status == 0) {
            //已存在,取消
            $save = array(
                'cache_id' => $cache_detail['cache_id'],
                'is_delete' => 1,
            );
        }
        if (!empty($cache_detail) && $status == 1) {
            //已存在,选中
            $save = array(
                'cache_id' => $cache_detail['cache_id'],
                'is_delete' => 0,
            );
        }
        $m = M();
        $m->startTrans();
        if (!empty($cache_detail)) {
            $save['cache_id'] = $cache_detail['cache_id'];
        }
        $save_res = $inventory_module->saveInventoryCache($save, $m);
        if (!$save_res) {
            $m->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '失败');
        }
        $cache_id = (int)$save_res;
        //返回当前商品的原库存,现库存,盈亏数-start

        if ($status != 0) {
            $current_goods_cache_total = $inventory_module->getCurrentGoodsCacheTotal($cache_id);
        } else {
            //取消
            $goods_result = $goods_module->getGoodsInfoById($goods_id);
            $goods_detail = $goods_result['data'];
            $old_stock = (float)$goods_detail['goodsStock'];
            if (!empty($sku_id)) {
                //有规格
                $sku_system_result = $goods_module->getSkuSystemInfoById($sku_id);
                $sku_system_detail = $sku_system_result['data'];
                $old_stock = (float)$sku_system_detail['skuGoodsStock'];
            }
            $current_stock = (float)$old_stock;
            $profit_loss = (float)bc_math($current_stock, $old_stock, 'bcsub', 3);
            $current_goods_cache_total = array(
                'old_stock' => $old_stock,
                'current_stock' => $current_stock,
                'profit_loss' => $profit_loss,
            );
        }
        //返回当前商品的原库存,现库存,盈亏数-end
        //返回该盘点人员当前盘点商品总数,总盈亏数-start
        $inventory_cache_total = $inventory_module->getInventoryUserCacheTotal($inventory_user_id, $inventory_user_type);
        //返回该盘点人员当前盘点商品总数,总盈亏数-end
        $result = array(
            'current_goods' => array(//当前商品
                'old_stock' => $current_goods_cache_total['old_stock'],//原库存
                'current_stock' => $current_goods_cache_total['current_stock'],//现库存
                'profit_loss' => $current_goods_cache_total['profit_loss'],//盈亏数
            ),
            'total_goods_num' => $inventory_cache_total['total_goods_num'],//缓存中商品总数
            'total_profit_loss' => $inventory_cache_total['total_profit_loss'],//缓存中总盈亏数
        );
        $m->commit();
        return returnData($result);
    }


    /**
     * 盘点库存缓存-清除所有的盘点商品缓存
     * @param array $login_info 登陆者信息
     * @return array
     * */
    public function clearInventoryGoods(array $login_info)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $cache_list = $inventory_module->getCacheListByInventoryUser($inventory_user_id, $inventory_user_type, 'cache_id');
        if (count($cache_list) <= 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无盘点商品数据，请先去盘点商品');
        }
        $cache_res = $inventory_module->clearInventoryGoods($inventory_user_id, $inventory_user_type);
        return returnData($cache_res);
    }

    /**
     * 盘点库存缓存-获取商品总数,总盈亏数
     * @param array $login_info 登陆者信息
     * @return array
     * */
    public function getInventoryUserCacheTotal(array $login_info)
    {
        $inventory_user_type = $login_info['user_type'];
        $inventory_user_id = $login_info['user_id'];
        $inventory_module = new InventoryModule();
        $res = $inventory_module->getInventoryUserCacheTotal($inventory_user_id, $inventory_user_type);
        return returnData($res);
    }

}
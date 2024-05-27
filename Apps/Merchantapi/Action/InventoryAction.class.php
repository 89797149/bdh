<?php

namespace Merchantapi\Action;

use App\Modules\Inventory\InventoryLossModule;
use App\Modules\Inventory\LocationModule;
use App\Modules\Inventory\InventoryServiceModule;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Inventory\ReportLossReasonModule;
use App\Modules\Shops\ShopsServiceModule;
use function App\Util\response;
use function App\Util\responseError;
use function App\Util\responseSuccess;
use Home\Model\UserModel;
use Merchantapi\Model\InventoryMerModel;
use Merchantapi\Model\InventoryModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 盘点功能类
 */
class InventoryAction extends BaseAction
{

    /**
     * 货位列表 - 带分页的
     * 原来的，可正常使用的
     */
    /*public function locationList(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId'    =>  $shopId,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getLocationList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }*/

    /**
     * 货位列表 - 不带分页的
     */
    public function locationList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getLocationList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 新增/编辑 货位
     */
    public function editLocation()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/Inventory');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editLocation(array('lid' => $id, 'shopId' => $shopId), $data);
        } else {//新增
            $data['lFlag'] = 1;
            $result = $m->addLocation($data);
        }

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }


    /**
     * 新增货位
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/sg90qy
     * */
    public function addLocation()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $requestParams['shopId'] = $shopId;
        $requestParams['parentId'] = (int)I('parentId');
        if (empty($requestParams['name'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '货位名称必填'));
        }
        $m = D('Merchantapi/Inventory');
        $data = $m->addLocation($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 修改货位
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ognx3m
     * */
    public function updateLocation()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $requestParams['shopId'] = $shopId;
        $requestParams['parentId'] = (int)I('parentId');
        if (empty($requestParams['lid']) || empty($requestParams['name'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Inventory');
        $data = $m->updateLocation($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 一级货位列表
     * 管理端
     */
    public function oneLocationRankList()
    {
        $shop_id = $this->MemberVeri()['shopId'];
        $params = [
            'shop_id' => $shop_id,
            'parent_id' => 0,
            'page' => (int)I('page', 1),
            'pageSize' => (int)I('pageSize', 15),
        ];
        $m = new InventoryMerModel();
        $result = $m->getLocationListById($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 一级货位列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vn0c5s
     */
//    public function oneLocationRankList()
//    {
//        $shopId = $this->MemberVeri()['shopId'];
//        $params = [
//            'shopId' => $shopId,
//            'page' => I('page', 1),
//            'pageSize' => I('pageSize', 15),
//        ];
//        $m = D('Merchantapi/Inventory');
//        $data = $m->getLocationRankList($params);
//        $this->ajaxReturn(returnData($data));
//    }

    /**
     * 二级货位列表
     */
//    public function twoLocationRankList(){
//
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
//
//        $shopId = $this->MemberVeri()['shopId'];
//        if (!$shopId) {
//            $user = session('WST_USER');
//            $shopId = $user['shopId'];
//        }
////        $shopId = I('shopId', 0, 'intval');
//
//        if (empty($shopId)) {
//            $apiRet['apiInfo'] = "参数不全";
//            $this->ajaxReturn($apiRet);
//        }
//
//        $param = array(
//            'shopId'    =>  $shopId,
//            'parentId'  =>  array('GT',0),
//            'lFlag'     =>  1
//        );
//
//        $m = D('Merchantapi/Inventory');
//        $list = $m->getLocationRankList($param);
//
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
//
//        $this->ajaxReturn($apiRet);
//    }
    /**
     * 二级货位列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pca6wx
     * */
    public function twoLocationRankList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $params = [
            'shopId' => $shopId,
            'page' => I('page', 1),
            'pageSize' => I('pageSize', 9999),
        ];
        $m = D('Merchantapi/Inventory');
        $data = $m->twoLocationRankList($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 删除货位
     */
    public function deleteLocation()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteLocation(array('lid' => $id, 'shopId' => $shopId), array('lFlag' => -1));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 货位详情
     */
    public function locationDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $detail = $m->getLocationInfoByLid(array('lid' => $id, 'shopId' => $shopId));

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $detail;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 货位商品列表
     */
    public function locationGoodsList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $lparentId = I('lparentId', 0, 'intval');
        $lid = I('lid', 0, 'intval');

        $param = array(
            'shopId' => $shopId,
            'lparentId' => $lparentId,
            'lid' => $lid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getLocationGoodsList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据商品id添加货位
     */
    public function addGoodsLocation()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();
        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
        $lid = I('lid', '', 'trim');//逗号隔开
        $goodsId = I('goodsId', 0, 'intval');
//        if (empty($shopId) || empty($lid) || empty($goodsId)) {
        if (empty($shopId) || empty($goodsId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
//        $lid = json_decode($lid,true);
        $m = D('Merchantapi/Inventory');
        $result = $m->addGoodsLocation($goodsId, $shopId, $lid);
        $this->ajaxReturn($result);
    }

    /**
     * 根据商品id获取货位信息
     */
    public function getGoodsLocationInfo()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();
        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
        $goodsId = I('goodsId', 0, 'intval');
        if (empty($shopId) || empty($goodsId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Inventory');
        $result = $m->getGoodsLocationInfo($goodsId, $shopId);
        $this->ajaxReturn($result);
    }

    /**
     * 删除货位商品
     */
    public function deleteLocationGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteLocationGoods(array('lgid' => $id, 'shopId' => $shopId), array('lgFlag' => -1));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 货位商品转移 - 动作
     */
    public function locationGoodsTransfer()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $lgid = I('lgid', 0, 'trim');//以逗号连接
        $lparentId = I('lparentId', 0, 'intval');
        $lid = I('lid', 0, 'intval');
        $targetLid = I('targetLid', 0, 'intval');

        if (empty($shopId) || empty($lparentId) || empty($targetLid) || empty($lgid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->doLocationGoodsTransfer($shopId, $lgid, $lparentId, $lid, $targetLid);

        $this->ajaxReturn($result);
    }

    /**
     * 盘点任务列表
     * state 0：待盘点 1：盘点中 2：已完成 3:已作废
     */
    public function inventoryList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $state = I('state', 0, 'intval');//0：待盘点 1：盘点中 2：已完成 3:已作废
        $name = I('name', '', 'trim');
        $start_time = I('start_time', '', 'trim');
        $end_time = I('end_time', '', 'trim');

        $param = array(
            'shopId' => $shopId,
            'parentId' => 0,
            'state' => $state,
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getInventoryList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 新增/编辑 盘点任务
     */
    public function editInventory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/Inventory');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editInventory(array('iid' => $id, 'shopId' => $shopId), $data);
        } else {//新增
            $result = $m->addInventory($data);
        }

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 删除盘点任务
     */
    public function deleteInventory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteInventory(array('iid' => $id, 'shopId' => $shopId), array('iFlag' => -1));

        $this->ajaxReturn($result);
    }

    /**
     * 作废盘点任务
     */
    public function cancelInventory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->cancelInventory(array('iid' => $id, 'shopId' => $shopId), array('state' => 3));

        $this->ajaxReturn($result);
    }

    /**
     * 盘点详情
     */
    public function inventoryDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->getInventoryDetail(array('iid' => $id, 'shopId' => $shopId));

        $this->ajaxReturn($result);
    }

    /**
     * (废弃)
     * 根据一级货位id来获取二级货位列表
     * 管理端和盘点端
     */
//    public function getLocationRankListByParentId()
//    {
//
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
//
//        $shopId = $this->MemberVeri()['shopId'];
//        if (!$shopId) {
//            $user = session('WST_USER');
//            $shopId = $user['shopId'];
//        }
////        $shopId = I('shopId', 0, 'intval');
//        $lid = I('id', 0, 'intval');
//
//        if (empty($shopId) || empty($lid)) {
//            $apiRet['apiInfo'] = "参数不全";
//            $this->ajaxReturn($apiRet);
//        }
//
//        $param = array(
//            'shopId' => $shopId,
//            'parentId' => $lid,
//            'lFlag' => 1
//        );
//
//        $m = D('Merchantapi/Inventory');
//        $list = $m->getLocationRankList($param);
//
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
//
//        $this->ajaxReturn($apiRet);
//    }

    /**
     * (废弃)
     * 根据一级货位id来获取二级货位列表
     * 管理端和盘点端
     */
    public function getLocationRankListByParentId()
    {
        $shop_id = $this->MemberVeri()['shopId'];
        $parent_id = I('id', 0, 'intval');
        $params = array(
            'shop_id' => $shop_id,
            'parent_id' => $parent_id,
            'page' => (int)I('page', 1),
            'pageSize' => (int)I('pageSize', 15),
        );
        $m = new InventoryMerModel();
        $result = $m->getLocationListById($params);
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 子盘点任务列表
     */
    public function childInventoryList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $iid = I('id', 0, 'intval');

        if (empty($shopId) || empty($iid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'iid' => $iid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getChildInventoryList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 获取门店职员列表
     */
    public function shopUserList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'status' => 0
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getShopUserList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 入库任务列表
     * state（0：待入库 1：入库中 2：已完成）
     */
    public function inWarehouseList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
        $state = I('state', -1, 'intval');//状态 （0：待入库 1：入库中 2：已完成）
        $name = I('name', '', 'trim');
        $start_time = I('start_time', '', 'trim');
        $end_time = I('end_time', '', 'trim');
        $username = I('username');

        $param = array(
            'shopId' => $shopId,
            'state' => $state,
            'username' => $username,
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getInWarehouseList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * updateLog start
     * 2020-05-13
     * 以前就存在的接口,直接修改就不重写了
     * updateLog end
     * 新增/编辑 入库任务
     */
    public function editInWarehouse()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();
        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
        $id = I('id', 0, 'intval');
        $data = I('param.');
        if (empty($shopId) || empty($data['uids']) || empty($data['purchaseOrderIds']) || empty($data['startTime']) || empty($data['endTime']) || empty($data['name'])) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
        unset($data['token']);
        $data['shopId'] = $shopId;
        $data['dataType'] = I('dataType', 1);
        $m = D('Merchantapi/Inventory');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editInWarehouse(array('iwid' => $id, 'shopId' => $shopId), $data);
        } else {//新增
            $data['createTime'] = date('Y-m-d H:i:s');
            $data['state'] = 0;
            $result = $m->addInWarehouse($data);
        }
        if ($result['code'] != -1) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        } else {
            $apiRet['apiInfo'] = !empty($result['msg']) ? $result['msg'] : '操作失败';
        }
        $this->ajaxReturn($apiRet);
    }

    /**
     * updateLog start
     * 2020-05-13
     * 以前的接口就不重写了,加个验证和错误提示
     * updateLog end
     * 删除入库任务
     */
    public function deleteInWarehouse()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteInWarehouse(array('iwid' => $id, 'shopId' => $shopId), array('iwFlag' => -1));
        if ($result['code'] != -1) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        } else {
            $apiRet['apiInfo'] = !empty($result['msg']) ? $result['msg'] : "操作失败";
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 入库详情
     */
    public function inWarehouseDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->getInWarehouseDetail(array('iwid' => $id, 'shopId' => $shopId, 'iwFlag' => 1));

        $this->ajaxReturn($result);
    }

    /**
     * 商品报损列表
     * 管理端
     */
    public function goodsReportLossList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getGoodsReportLossList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 新增/编辑 商品报损
     * 盘点端
     */
    public function editGoodsReportLoss()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }
//        $userId = $user['id'];
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');

        if (empty($user)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');
        $num = I('num', 0);//报损数量
        if ($num <= 0) {
            $apiRet['apiInfo'] = '报损数量必须大于0';
            return $apiRet;
        }

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $user['shopId'];
        $data['uid'] = $user['id'];
        $data['username'] = $user['username'];
//        unset($data['idss']);

        $m = D('Merchantapi/Inventory');
        if ($data['lid'] > 0) {
            $locationInfo = $m->getLocationInfoByLid(array('lid' => $data['lid'], 'lFlag' => 1));
            if (!empty($locationInfo)) $data['lparentId'] = $locationInfo['parentId'];
        }

        if ($data['goodsId'] > 0) {
            $goodsInfo = $m->getGoodsInfoByGoodsId(array('goodsId' => $data['goodsId'], 'isSale' => 1, 'goodsFlag' => 1));
            if (!empty($goodsInfo)) $data['goodsSn'] = $goodsInfo['goodsSn'];
            //后加,兼容商品sku start
            if (!empty($data['skuId'])) {
                $where = [];
                $where['goodsId'] = $goodsInfo['goodsId'];
                $where['skuId'] = $data['skuId'];
                $skuInfo = M('sku_goods_system')->where($where)->find();
                if (!empty($skuInfo)) {
                    if ($skuInfo['skuBarcode'] != -1) {
                        $data['goodsSn'] = $skuInfo['skuBarcode'];
                    }
                }
            }
            //后加,兼容商品sku end
        }

        if ($data['rlrid'] > 0) {
            $goodsReportLossInfo = $m->getGoodsReportLossDetail(array('rlrid' => $data['rlrid'], 'rlrFlag' => 1));
            if (!empty($goodsReportLossInfo)) $data['rlrname'] = $goodsReportLossInfo['name'];
        }
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editGoodsReportLoss(array('grlid' => $id, 'shopId' => $user['shopId']), $data);
        } else {//新增
            $data['createTime'] = date('Y-m-d H:i:s');
            $data['grlFlag'] = 1;
            $result = $m->addGoodsReportLoss($data);
        }

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 删除商品报损
     * 管理端
     */
    public function deleteGoodsReportLoss()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteGoodsReportLoss(array('grlid' => $id, 'shopId' => $shopId), array('grlFlag' => -1));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 校对(同意)报损商品
     * 不支持批量同意
     */
    public function checkReportLossGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $grlid = I('id', 0, 'intval');

        if (empty($shopId) || empty($grlid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->doCheckReportLossGoods($shopId, $grlid);

        $this->ajaxReturn($result);
    }

    /**
     * 商品报损原因列表 - 带分页
     * 管理端
     */
    public function goodsReportLossReasonListWithPage()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getGoodsReportLossReasonListWithPage($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 商品报损原因列表 - 不带分页
     * 管理端
     */
    public function goodsReportLossReasonList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->getGoodsReportLossReasonList(array('shopId' => $shopId));

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 新增/编辑 商品报损原因
     * 管理端
     */
    public function editGoodsReportLossReason()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $shopId;

        $m = D('Merchantapi/Inventory');
        if ($id > 0) {//编辑
            unset($data['id']);
            $result = $m->editGoodsReportLossReason(array('rlrid' => $id, 'shopId' => $shopId, 'rlrFlag' => 1), $data);
        } else {//新增
            unset($data['id']);
            $data['createTime'] = date('Y-m-d H:i:s');
            $data['rlrFlag'] = 1;
            $result = $m->addGoodsReportLossReason($data);
        }

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 删除商品报损原因
     * 管理端
     */
    public function deleteGoodsReportLossReason()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->editGoodsReportLossReason(array('rlrid' => $id, 'shopId' => $shopId, 'rlrFlag' => 1), array('rlrFlag' => -1));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 商品报损原因详情
     * 管理端
     */
    public function goodsReportLossReasonDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id', 0, 'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->getGoodsReportLossDetail(array('rlrid' => $id, 'shopId' => $shopId, 'rlrFlag' => 1));

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $result;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据商品编号或名称来搜索商品
     * 盘点端
     */
    public function searchGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $keywords = I('keywords', '', 'trim');

        if (empty($shopId) || empty($keywords)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->getGoodsList($shopId, $keywords);
        $list = getGoodsSku($list);
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据编号或名称来搜索一条商品数据
     * 只查询出一条数据
     * 盘点端
     */
    public function searchGoodsByGoodsIdAndGoodsName()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $keywords = I('keywords', '', 'trim');

        if (empty($shopId) || empty($keywords)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->searchGoodsByGoodsIdAndGoodsName($shopId, $keywords);
        $list = getGoodsSku($list);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据二级货位名称来搜索货位
     * 盘点端
     */
    public function searchLocation()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $keywords = I('keywords', '', 'trim');

        if (empty($shopId) || empty($keywords)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->getLocationListByTwoLocationName($shopId, $keywords);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据名称来搜索入库任务
     * 盘点端
     */
    public function searchInWarehouse()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 0, 'intval');
        $keywords = I('keywords', '', 'trim');

        if (empty($shopId) || empty($keywords)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->getInWarehouseListByName($shopId, $keywords);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 废弃
     * 盘点商品 - 动作
     * 盘点端
     */
    public function doInventoryGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }
//        $userId = $user['id'];
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');

        if (empty($user)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $user['shopId'];
        $data['uid'] = $user['id'];
        $data['username'] = $user['username'];
//        unset($data['idss']);

        $m = D('Merchantapi/Inventory');
        if ($data['ciid'] > 0) {
            $inventoryInfo = $m->getChildInventoryInfoByCiid(array('ciid' => $data['ciid'], 'ciFlag' => 1));
            if (!empty($inventoryInfo)) $data['iid'] = $inventoryInfo['iid'];
        }

        if ($data['lid'] > 0) {
            $locationInfo = $m->getLocationInfoByLid(array('lid' => $data['lid'], 'lFlag' => 1));
            if (!empty($locationInfo)) $data['lparentId'] = $locationInfo['parentId'];
        }

        if ($data['goodsId'] > 0) {
            $goodsInfo = $m->getGoodsInfoByGoodsId(array('goodsId' => $data['goodsId'], 'isSale' => 1, 'goodsFlag' => 1));
            if (!empty($goodsInfo)) $data['goodsSn'] = $goodsInfo['goodsSn'];
            //后加,兼容商品sku start
            if (!empty($data['skuId'])) {
                $where = [];
                $where['goodsId'] = $goodsInfo['goodsId'];
                $where['skuId'] = $data['skuId'];
                $skuInfo = M('sku_goods_system')->where($where)->find();
                if (!empty($skuInfo)) {
                    if ($skuInfo['skuBarcode'] != -1) {
                        $data['goodsSn'] = $skuInfo['skuBarcode'];
                    }
                }
            }
            //后加,兼容商品sku end
        }

        $data['createTime'] = date('Y-m-d H:i:s');
        $data['state'] = 0;
        $data['isCheck'] = 0;
        $data['irFlag'] = 1;
        $result = $m->addInventoryGoods($data);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 入库商品 - 动作
     * 弃用
     * 盘点端
     */
    public function doInWarehouseGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }
//        $userId = $user['id'];
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');

        if (empty($user)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $data = I('param.');
        unset($data['token']);
        $data['shopId'] = $user['shopId'];
        $data['uid'] = $user['id'];
        $data['username'] = $user['username'];
//        unset($data['idss']);

        $data_new['shopId'] = $data['shopId'];
        $data_new['lid'] = $data['lid'];

        $m = D('Merchantapi/Inventory');
        if ($data['lid'] > 0) {
            $locationInfo = $m->getLocationInfoByLid(array('lid' => $data['lid'], 'lFlag' => 1));
            if (!empty($locationInfo)) {
                $data['lparentId'] = $locationInfo['parentId'];
                $data_new['lparentId'] = $locationInfo['parentId'];
            }
        }

        $data_new['goodsId'] = $data['goodsId'];
        if ($data['goodsId'] > 0) {
            $goodsInfo = $m->getGoodsInfoByGoodsId(array('goodsId' => $data['goodsId'], 'isSale' => 1, 'goodsFlag' => 1));
            if (!empty($goodsInfo)) {
                $data['goodsSn'] = $goodsInfo['goodsSn'];
                $data_new['goodsSn'] = $goodsInfo['goodsSn'];
            }
        }
        $data['iwrFlag'] = 1;
        $inWarehouseRecord = $m->getInWarehouseRecordInfo($data);
        //判断商品是否已入库，如果已入库，则提示
        if (!empty($inWarehouseRecord)) {
            $apiRet['apiInfo'] = "商品已入库";
            $this->ajaxReturn($apiRet);
        }

        $data_new['lgFlag'] = 1;
        $locationGoodsInfo = $m->getLocationGoodsInfo($data_new);
        //如果货位商品没有，则添加
        if (empty($locationGoodsInfo)) {
            $data_new['createTime'] = date('Y-m-d H:i:s');
            $m->addLocationGoods($data_new);
        }

        $data['createTime'] = date('Y-m-d H:i:s');

        $result = $m->addInWarehouseGoods($data);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 废弃
     * 完成子盘点任务
     * 盘点端
     */
    public function completeChildInventory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }

//        $userId = $user['id'];
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $ciid = I('id', 0, 'intval');

        if (empty($user) || empty($ciid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->completeChildInventory($user, $ciid);

        $this->ajaxReturn($result);
    }

    /**
     * 完成盘点任务
     * 管理端
     */
    public function completeInventory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        $iid = I('id', 0, 'intval');

        if (empty($shopId) || empty($iid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->completeInventory($shopId, $iid);

        $this->ajaxReturn($result);
    }

    /**
     * 完成入库任务
     * 管理端
     */
    public function completeInWarehouse()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 1;
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        $iwid = I('id', 0, 'intval');

        if (empty($shopId) || empty($iwid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Jxc');
        $result = $m->completeInWarehouse($shopId, $iwid);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 商品盘点报表
     * 管理端
     */
    public function inventoryReport()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getInventoryReport($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 子盘点任务下的盘点商品列表
     */
    public function childInventoryUnderInventoryGoodsList()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        $ciid = I('id', 0, 'intval');

        if (empty($shopId) || empty($ciid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'ciid' => $ciid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getChildInventoryUnderInventoryGoodsList($param);
        if (!$list) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '数据为空';
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            $this->ajaxReturn($apiRet);
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;
        $this->ajaxReturn($apiRet);
    }

    /**
     * 门店(待核对)盘点商品列表(主要用于核对商品库存)
     */
    public function shopInventoryGoodsList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        $iid = I('id', 1, 'intval');

        if (empty($shopId) || empty($iid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'iid' => $iid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $result = $m->getShopInventoryGoodsList($param);

        $this->ajaxReturn($result);
    }

    /**
     * 盘点完成后，一键核对商品库存，并更改商品库存
     * 多个以逗号连接，但 goodsId 和 和 skuId 和 goodsNum 的数量应保持一致
     * 管理端
     */
    public function checkGoodsNumber()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (!$shopId) {
            $user = session('WST_USER');
            $shopId = $user['shopId'];
        }
//        $shopId = I('shopId', 1, 'intval');
        /*$iid = I('id',0,'intval');
        $goodsId = I('goodsId', array());
        $goodsNum = I('goodsNum', array());*/
        $requestParam = I();
        $params['id'] = 0;
        $params['goodsId'] = '';
        $params['skuId'] = '';
        $params['goodsNum'] = '';
        parm_filter($params, $requestParam);
//        if (empty($shopId) || empty($params['goodsId']) || empty($params['skuId']) || empty($params['goodsNum']) || empty($params['id'])) {
        if (empty($shopId) || empty($params['goodsId']) || empty($params['goodsNum']) || empty($params['id'])) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Inventory');
        $result = $m->checkGoodsNumber($shopId, $params['goodsId'], $params['skuId'], $params['goodsNum'], $params['id']);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 废弃
     * 盘点任务列表
     * state ,0：待盘点 1：盘点中 2：已完成 ,默认为0
     * 盘点端
     */
    public function userInventoryList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }

//        $userId = I('id', 0, 'intval');
//        $iid = I('id', 0, 'intval');
        $state = I('state', 0, 'intval');//state ,0：待盘点 1：盘点中 2：已完成 ,默认为0

        if (empty($userId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'userId' => $userId,
            'state' => $state,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getUserInventoryList($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 废弃
     * 修改子盘点任务状态为盘点中
     * 盘点端
     */
    public function changeChildInventoryState()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }
//        $userId = I('userId',0,'intval');
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $ciid = I('id', 0, 'intval');

        if (empty($userId) || empty($ciid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->changeChildInventoryState($userId, $ciid);

        $this->ajaxReturn($result);
    }

    /**
     * 废弃
     * 盘点记录详情
     * 盘点端
     */
    public function inventoryRecordDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }

//        $userId = I('userId', 0, 'intval');
//        $iid = I('id', 0, 'intval');
        $ciid = I('ciid', 0, 'intval');//子盘点任务id

        if (empty($userId) || empty($ciid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'userId' => $userId,
            'ciid' => $ciid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getInventoryRecordDetail($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 废弃
     * 修改盘点商品
     * 盘点端
     */
    public function updateInventoryGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }

//        $userId = I('userId',0,'intval');
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $irid = I('irid', 0, 'intval');
        $num = I('num', 0, 'intval');

        if (empty($userId) || empty($irid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->updateInventoryGoods(array('uid' => $userId, 'irid' => $irid), array('num' => $num));

        $this->ajaxReturn($result);
    }

    /**
     * 废弃
     * 删除盘点商品
     * 盘点端
     */
    public function deleteInventoryGoods()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }

//        $userId = I('userId',0,'intval');
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $irid = I('irid', 0, 'intval');

        if (empty($userId) || empty($irid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $result = $m->deleteInventoryGoods(array('uid' => $userId, 'irid' => $irid), array('irFlag' => -1));

        $this->ajaxReturn($result);
    }

    /**
     * 报损历史
     * 盘点端
     */
    public function goodsReportLossHistory()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }

//        $userId = $user['id'];
//        $user = array('id'=>I('id',0,'intval'),'shopId'=>I('shopId',0,'intval'));
//        $iid = I('id', 0, 'intval');

        if (empty($user)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'user' => $user,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Inventory');
        $list = $m->getGoodsReportLossHistory($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 报损详情
     * 盘点端
     */
    public function goodsReportLossDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }

//        $userId = $user['id'];
//        $user = array('id'=>I('id',0,'intval'),'shopId'=>I('shopId',0,'intval'));
        $grlid = I('grlid', 0, 'intval');

        if (empty($user) || empty($grlid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'user' => $user,
            'grlid' => $grlid
        );

        $m = D('Merchantapi/Inventory');
        $detail = $m->goodsReportLossDetail($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $detail;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 报损详情
     * 管理端
     */
    public function goodsReportLossDetailForShop()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }
        $shopId = $user['shopId'];

//        $userId = $user['id'];
//        $user = array('id'=>I('id',0,'intval'),'shopId'=>I('shopId',0,'intval'));
        $grlid = I('grlid', 0, 'intval');

        if (empty($shopId) || empty($grlid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId' => $shopId,
            'grlid' => $grlid
        );

        $m = D('Merchantapi/Inventory');
        $detail = $m->goodsReportLossDetailForShop($param);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $detail;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 职员登录验证
     * 盘点端
     */
    public function checkLogin()
    {
        $rs = array('status' => -2);
        $rs["status"] = 1;
        /*if(!$this->checkVerify("4") && ($GLOBALS['CONFIG']["captcha_model"]["valueRange"]!="" && strpos($GLOBALS['CONFIG']["captcha_model"]["valueRange"],"3")>=0)){
            $rs["status"]= -2;
            $rs["msg"]= "验证码错误";//验证码错误
            $this->ajaxReturn($rs);
        }
        */
        $type = I('type', 2);
        $m = new UserModel();
        //$m = new UserModel();
        $rs = $m->login();

        if ($rs['status'] != 1) {
            $rs["msg"] = "登录失败";
            $this->ajaxReturn($rs);
        }

        $rs['msg'] = '登录成功';
        $shop = $rs['shop'];
        //生成用唯一token
        $code = 'shops';
        $memberToken = md5(uniqid('', true) . $code . $shop['shopId'] . $shop['loginName'] . (string)microtime());
        $shop['login_type'] = $type;
        if (!userTokenAdd($memberToken, $shop)) {
            $rs["status"] = -3;
            $rs["msg"] = "登录失败";

            $this->ajaxReturn($rs);
        }
        session('WST_USER', $rs['shop']);
        $rs["data"] = array(
            'token' => $memberToken,
            'userInfo' => $shop,
        );
        //END

        unset($rs['shopInfo'], $rs['staffNid']);
        unset($rs['shop']);
        $this->ajaxReturn($rs);
    }

    /**
     * 退出
     * 盘点端
     */
    public function logout()
    {
        session('WST_USER', null);
        echo "1";
    }

    /**
     * 获取职员信息
     * 盘点端
     */
    public function getUserInfor()
    {
        $this->ajaxReturn(session('WST_USER'));
    }

    /**
     * 采购单列表
     * 新增/编辑入库任务时会用到
     * 管理端
     */
    public function purchaseOrderList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shop = $this->MemberVeri();
        if (!$shop) {
            $shop = session('WST_USER');
        }

//        $shop = array('shopId'=>I('shopId',0,'intval'));

        if (empty($shop)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Jxc');
        $list = $m->getPurchaseOrderList($shop);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 用户入库任务列表
     * 盘点端
     */
//    public function userInWarehouseList(){
//
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '操作失败';
//        $apiRet['apiState'] = 'error';
//        $apiRet['apiData'] = array();
//
//        $userId = $this->MemberVeri()['id'];
//        if (!$userId) {
//            $user = session('WST_USER');
//            $userId = $user['id'];
//        }
//
////        $userId = I('userId', 0, 'intval');
////        $iid = I('id', 0, 'intval');
//        $state = I('state',0,'intval');//state ,0：待入库 1：入库中 2：已入库 ,默认为0
//
//        if (empty($userId)) {
//            $apiRet['apiInfo'] = "参数不全";
//            $this->ajaxReturn($apiRet);
//        }
//
//        $param = array(
//            'userId'    =>  $userId,
//            'state'     =>  $state,
//            'page'      =>  I('page', 1, 'intval'),
//            'pageSize'  =>  I('pageSize', 10, 'intval')
//        );
//
//        $m = D('Merchantapi/Jxc');
//        $list = $m->getUserInWarehouseList($param);
//
//        $apiRet['apiCode'] = 0;
//        $apiRet['apiInfo'] = '操作成功';
//        $apiRet['apiState'] = 'success';
//        $apiRet['apiData'] = $list;
//
//        $this->ajaxReturn($apiRet);
//    }

    /**
     * 需求更改,已废弃
     * PS:原来的方法在上面注释的地方
     * 盘点端->用户入库任务列表
     * @param varchar memberToken
     * @param int state 入库状态【0：待入库 1：入库中 2：已入库】
     * @param int page 页码
     * @param int pageSize 分页条数
     * */
    public function userInWarehouseList()
    {
        $userId = $this->MemberVeri()['id'];
        $state = (int)I('state');//state ,0：待入库 1：入库中 2：已入库
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D('Merchantapi/Jxc');
        $res = $m->getUserInWarehouseList($userId, $state, $page, $pageSize);
        $this->ajaxReturn(returnData($res));
    }

    /**
     * 修改入库任务状态为入库中
     * 盘点端
     */
    public function changeInWarehouseState()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $userId = $this->MemberVeri()['id'];
        //$userId = 10;
        if (!$userId) {
            $user = session('WST_USER');
            $userId = $user['id'];
        }

//        $userId = I('userId',0,'intval');
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $iwid = I('iwid', 0, 'intval');

        if (empty($userId) || empty($iwid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Jxc');
        $result = $m->changeInWarehouseState($iwid);

        $this->ajaxReturn($result);
    }

    /**
     * 入库详情
     * 盘点端
     */
    public function userInWarehouseDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        //$user = ['id'=>10,'shopId'=>1];
        if (!$user) {
            $user = session('WST_USER');
        }
        $userId = $user['id'];
        $shopId = $user['shopId'];

//        $userId = I('userId', 0, 'intval');
//        $shopId = I('shopId', 0, 'intval');
        $storage = I('storage', 0, 'intval');//storage ,0:未入库|1:部分入库|2:已入库 ,默认为0
        $iwid = I('iwid', 0, 'intval');//入库任务id

        if (empty($shopId) || empty($iwid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'userId' => $userId,
            'shopId' => $shopId,
            'storage' => $storage,
            'iwid' => $iwid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 10, 'intval')
        );

        $m = D('Merchantapi/Jxc');
        $result = $m->getUserInWarehouseDetail($param);

        $this->ajaxReturn($result);
    }

    /**
     * 修改采购单商品的状态
     * 盘点端
     */
    public function changeInWarehouseGoodsState()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        //$user = ["userId"=>10,"shopId"=>1];
        if (!$user) {
            $user = session('WST_USER');
        }

//        $userId = I('userId',0,'intval');
//        $user = array('shopId'=>I('shopId',0,'intval'),'id'=>I('idss',0,'intval'),'username'=>I('username','','trim'));
//        $shopId = I('shopId', 1, 'intval');
        $id = I('id', 0, 'intval');
        $storage = I('storage', 0, 'intval');//storage ,0:未入库|1:部分入库|2:已入库 ,默认为0
        $iwid = (int)I("iwid");//入库任务id

        if (empty($user) || empty($id) || empty($storage) || empty($iwid)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Jxc');
        $result = $m->changeInWarehouseGoodsState($user, $id, $storage, $iwid);

        $this->ajaxReturn($result);
    }

    /**
     * 商品报损原因列表
     * 盘点端
     */
    public function reportLossReasonList()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
        if (!$user) {
            $user = session('WST_USER');
        }
        $shopId = $user['shopId'];
//        $userId = I('userId',0,'intval');
//        $shopId = I('shopId',0,'intval');

        if (empty($user) || empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Inventory');
        $list = $m->getReportLossReasonList($shopId);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    ###################################不晓得为什么移动端接口和后台接口要写在同一个文件里###############################
    #########################################盘点移动端-start########################################################

    /**
     * 盘点库存-扫码搜索商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kngtc1
     * @param string token
     * @param string code 编码
     * @return json
     * */
    public function searchGoodsInfoByCode()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $code = I('code');
        if (empty($code)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请扫码'));
        }
        $mod = new InventoryModel();
        $result = $mod->searchGoodsInfoByCode($shop_id, $code, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }


    /**
     * 盘点库存-获取门店一级分类
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fwt17m
     * @param string token
     * */
    public function getShopFirstClass()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $mod = new ShopsServiceModule();
        $field = 'catId,catName,icon,typeImg';
        $result = $mod->getShopFirstClass($shop_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点库存-获取门店商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dmthky
     * @param string token
     * @param string keyword 商品关键字
     * @param int shopCatId1 门店一级分类
     * @param int screening_criteria 筛选(1:根据商品名排序 2:只看有库存商品)
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function getShopGoods()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $params['shop_id'] = $shop_id;
        $mod = new InventoryModel();
        $result = $mod->getShopGoods($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点库存缓存-递增现库存数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/romdkl
     * @param string token
     * @param int goods_id
     * @param int sku_id 规格id
     * @param float stock 递增的库存数量
     * @return json
     * */
    public function incInventoryCache()
    {
        $login_info = $this->MemberVeri();
        $goods_id = (int)I('goods_id');
        $sku_id = (int)I('sku_id');
        $stock = (float)I('stock');
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        if ($stock <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '库存数量传参必须大于0'));
        }
        $mod = new InventoryModel();
        $result = $mod->incInventoryCache($login_info, $goods_id, $sku_id, $stock);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存缓存-递减现库存数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kb8qui
     * @param string token
     * @param int goods_id
     * @param int sku_id 规格id
     * @param float stock 递减的库存数量
     * @return json
     * */
    public function decInventoryCache()
    {
        $login_info = $this->MemberVeri();
        $goods_id = (int)I('goods_id');
        $sku_id = (int)I('sku_id');
        $stock = (float)I('stock');
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        if ($stock <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '库存数量传参必须大于0'));
        }
        $mod = new InventoryModel();
        $result = $mod->decInventoryCache($login_info, $goods_id, $sku_id, $stock);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存缓存-输入框方式加减盘点缓存的现库存数量
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bm6hq9
     * @param string token
     * @param int goods_id
     * @param int sku_id 规格id
     * @param float stock 现库存数量
     * @return json
     * */
    public function inputInventoryCache()
    {
        $login_info = $this->MemberVeri();
        $goods_id = (int)I('goods_id');
        $sku_id = (int)I('sku_id');
        $stock = (float)I('stock');
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        if ($stock <= 0) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '库存数量传参必须大于0'));
        }
        $mod = new InventoryModel();
        $result = $mod->inputInventoryCache($login_info, $goods_id, $sku_id, $stock);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存商品-取消状态/选中状态
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xaxoex
     * @param string token
     * @param int goods_id
     * @param int sku_id 规格id
     * @param int status 状态(0:取消 1:选中)
     * @return json
     * */
    public function checkedInventoryGoods()
    {
        $login_info = $this->MemberVeri();
        $goods_id = (int)I('goods_id');
        $sku_id = (int)I('sku_id');
        $status = (int)I('status');
        if (empty($goods_id)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误'));
        }
        if (!in_array($status, array(0, 1))) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '选中状态传参有误'));
        }
        $mod = new InventoryModel();
        $result = $mod->checkedInventoryGoods($login_info, $goods_id, $sku_id, $status);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存商品-清除所有的盘点商品缓存
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gyp9c1
     * @param string token
     * @return json
     * */
    public function clearInventoryGoods()
    {
        $login_info = $this->MemberVeri();
        $mod = new InventoryModel();
        $result = $mod->clearInventoryGoods($login_info);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存商品-获取商品总数,总盈亏数
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ni3x44
     * @param string token
     * @return json
     * */
    public function getInventoryUserCacheTotal()
    {
        $login_info = $this->MemberVeri();
        $mod = new InventoryModel();
        $result = $mod->getInventoryUserCacheTotal($login_info);
        $this->ajaxReturn($result);
    }

    /**
     * 盘点库存-创建盘点单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/nyxh3r
     * @param string token
     * @param json params
     * @return  json
     * */
    public function createInventory()
    {
        $login_info = $this->MemberVeri();
        $params = json_decode(htmlspecialchars_decode(I('params')), true);
        if (empty($params)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        if (empty($params['inventory_time'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请输入盘点日期'));
        }
        $inventory_time = $params['inventory_time'];
        if (!$inventory_time) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请输入正确的盘点日期'));
        }
//        if (empty($params['goods'])) {
//            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请先盘点商品库存'));
//        }
        $mod = new InventoryModel();
        $result = $mod->createInventory($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点库存-获取盘点记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/hhggkb
     * @param string token
     * @param string keyword 关键字
     * @param date start_date 开始日期
     * @param date end_date 结束日期
     * @param int page 页码
     * @param int pageSize 分页条数
     * @return json
     * */
    public function getInventoryBillList()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $params = I();
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $params['shop_id'] = $shop_id;
        $params['inventory_user_id'] = $login_info['user_id'];
        $mod = new InventoryModel();
        $result = $mod->getInventoryBillList($params);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
//        $this->ajaxReturn(responseSuccess($result['data']));
        $this->ajaxReturn(returnData((array)$result['data']));
    }

    /**
     * 盘点库存-获取盘点记录详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/otwft6
     * @param string token
     * @param int bill_id 盘点单id
     * @return json
     * */
    public function getInventoryBillDetail()
    {
        $this->MemberVeri();
        $bill_id = (int)I('bill_id');
        if (empty($bill_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryServiceModule();
        $field = 'bill_id,bill_no,total_goods_num,total_profit_loss,inventory_user_name,inventory_time,remark,confirm_status,confirm_user_name,confirm_time,create_time';
        $result = $mod->getInventoryBillDetail($bill_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点-根据父级id获取货位列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/lg7g2g
     * @param string token
     * @param int parent_id 父级id,默认为0:一级货位
     * @return json
     * */
    public function getLocationListById()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $parent_id = (int)I('parent_id');
        $mod = new LocationModule();
        $result = $mod->getLocationListById($shop_id, $parent_id, 'lid,name,parentId');
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 盘点-获取报损原因列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/kp3dnx
     * @param string token
     * @return json
     * */
    public function getLossReasonList()
    {
        $login_info = $this->MemberVeri();
        $shop_id = $login_info['shopId'];
        $mod = new ReportLossReasonModule();
        $result = $mod->getLossReasonList($shop_id, 'rlrid,name');
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        //$this->ajaxReturn(responseSuccess($result['data']));
        $this->ajaxReturn(returnData((array)$result['data']));
    }

    /**
     * 报损-新增报损
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gk7utx
     * @param string token
     * @param json params
     * @return json
     * */
    public function createLoss()
    {
        $login_info = $this->MemberVeri();
        $params = json_decode(htmlspecialchars_decode(I('params')), true);
        if (empty($params['goods_id'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请检查报损商品信息是否正确'));
        }
        if ($params['loss_num'] <= 0) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请输入正确的报损数量'));
        }
        if ((int)$params['reason_id'] <= 0) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请选择报损原因'));
        }
        if (empty($params['loss_pic'])) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '请上传凭证照片'));
        }
        $mod = new InventoryModel();
        $result = $mod->createLoss($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, $result['msg']));
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    /**
     * 报损-报损记录列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/agcked
     * @param string token
     * @param string keyword 关键字(商品名称,编码)
     * @param date start_date 开始日期
     * @param date end_date 结束日期
     * @param int page
     * @param int pageSize
     * @return json
     * */
    public function getLossList()
    {
        $login_info = $this->MemberVeri();
        $params = I();
        $params['inventory_user_id'] = $login_info['user_id'];
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $mod = new InventoryModel();
        $result = $mod->getLossList($params, $login_info);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(returnData((array)$result['data']));
    }

    /**
     * 报损-报损记录详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/obo3s8
     * @param int loss_id 报损id
     * @return json
     * */
    public function getLossInfo()
    {
        $this->MemberVeri();
        $loss_id = (int)I('loss_id');
        if (empty($loss_id)) {
            $this->ajaxReturn(responseError(ExceptionCodeEnum::FAIL, '参数有误'));
        }
        $mod = new InventoryLossModule();
        $field = 'loss_id,goods_id,sku_id,sku_spec_attr,code,one_lid,two_lid,loss_num,loss_reason,remark,loss_pic,inventory_user_name,confirm_status,confirm_user_name,confirm_time,create_time';
        $result = $mod->getLossInfoById($loss_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(responseSuccess($result['data']));
    }

    #########################################盘点移动端-end########################################################

}
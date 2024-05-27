<?php

namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 投筐功能类
 */
class BasketAction extends BaseAction
{

    /**
     * 分区列表 - 不带分页
     * 商家端
     */
    public function partitionList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = D('Merchantapi/Basket');
        $data = $m->getPartitionList($shopId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 新增 / 编辑 分区
     * 商家端
     */
    public function editPartition()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $name = I('name', '', 'trim');
        $pid = I('pid', 0, 'intval');

        if (empty($shopId) || empty($name)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');
        $data = array(
            'shopId' => $shopId,
            'name' => $name,
            'pid' => $pid
        );
        $m = D('Merchantapi/Basket');
        if ($id > 0) {//编辑
            $result = $m->editPartition(array('id' => $id, 'shopId' => $shopId), $data);
        } else {//添加
            $data['pFlag'] = 1;
            $result = $m->addPartition($data);
        }

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 删除分区
     * 商家端
     */
    public function deletePartition()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $id = I('id', '', 'trim');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $result = $m->deletePartition(array('id' => $id, 'shopId' => $shopId));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 分区详情
     */
    public function partitionDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $id = I('id', '', 'trim');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $partitionInfo = $m->getPartitionDetail(array('id' => $id, 'shopId' => $shopId));

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $partitionInfo;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 投筐列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/fufnx5
     */
    public function basketList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $params = [
            'shopId' => $shopId,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 15, 'intval')
        ];
        $m = D('Merchantapi/Basket');
        $data = $m->getBasketList($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 获取框位下订单【打包中的】
     *https://www.yuque.com/anthony-6br1r/oq7p0p/yhfahx
     */
    public function getBasketOrderList(){
        $shopId = $this->MemberVeri()['shopId'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $bid = (int)I('bid');
        if(empty($bid)){
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }
        $params = [
            'shopId' => $shopId,
            'bid' => $bid,
            'page' => I('page', 1, 'intval'),
            'pageSize' => I('pageSize', 15, 'intval')
        ];
        $m = D('Merchantapi/Basket');
        $data = $m->getBasketOrderList($params);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 新增 / 编辑 投筐
     * 商家端
     */
    public function editBasket()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $name = I('name', '', 'trim');
        $partitionId = I('partitionId', 0, 'intval');
        $orderNum = I('orderNum', -1, 'intval');//-1:不限量
        $basketSn = I('basketSn');//框位编号

        if (empty($shopId) || empty($name) || empty($partitionId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $id = I('id', 0, 'intval');
        $data = array(
            'shopId' => $shopId,
            'name' => $name,
            'partitionId' => $partitionId,
            'orderNum' => $orderNum,
            'basketSn' => $basketSn,
        );

        $m = D('Merchantapi/Basket');
        if ($partitionId > 0) {
            $partitionDetail = $m->getPartitionDetail(array('id' => $partitionId));
            $data['pid'] = $partitionDetail['pid'];
        }
        if ($id > 0) {//编辑
            $result = $m->editBasket(array('bid' => $id, 'shopId' => $shopId), $data);
        } else {//添加
            if (empty($basketSn)) {
                $apiRet['apiInfo'] = "请填写框位编号";
                $this->ajaxReturn($apiRet);
            }
            $data['bFlag'] = 1;
            $result = $m->addBasket($data);
        }
        if($result['apiCode'] == -1){
            $this->ajaxReturn($result);
        }
        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 删除投筐
     * 商家端
     */
    public function deleteBasket()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $id = I('id', '', 'trim');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $result = $m->deleteBasket(array('bid' => $id, 'shopId' => $shopId, 'bFlag' => 1), array('bFlag' => -1));

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 获得二级分区列表
     */
    public function getTwoPartitionList()
    {

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

        $where = array(
            'shopId' => $shopId,
            'pid' => array('GT', 0),
            'pFlag' => 1
        );

        $m = D('Merchantapi/Basket');
        $list = $m->getPartitionListByCondition($where);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 根据一级框位分类id获得二级框位分类
     */
    public function getTwoFrameList()
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
        if (empty($shopId) || empty(I('id'))) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $list = $m->getTwoFrameList($shopId);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 投筐详情
     */
    public function basketDetail()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $id = I('id', '', 'trim');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $partitionInfo = $m->getBasketDetail(array('bid' => $id, 'shopId' => $shopId));

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $partitionInfo;

        $this->ajaxReturn($apiRet);
    }

    /**
     * (订单受理时)分配筐位
     * (这个接口不一定用的到)
     * 商家端
     */
    public function distributionBasket()
    {

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $orderId = I('orderId', '', 'trim');

        if (empty($shopId) || empty($orderId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Basket');
        $result = $m->distributionBasket($shopId, $orderId);

        $this->ajaxReturn($result);
    }

}
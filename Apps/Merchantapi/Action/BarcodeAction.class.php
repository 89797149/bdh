<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 条码功能类
 */
class BarcodeAction extends BaseAction {

    /**
     * 生成(秤重商品)条码
     * 分拣端
     */
    public function createBarcode(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $user = $this->MemberVeri();
//        $user = array('id'=>I('id',0,'intval'), 'userName'=>I('userName','','trim'), 'mobile'=>I('mobile', '', 'trim'), 'shopId'=>I('shopId',0,'intval'));
        $goodsId = I('goodsId', 0, 'intval');//商品id
        $weight = I('weight', 0,'trim');//重量
        $price = I('price', 0, 'trim');//价格
        $orderNo = I('orderNo', '', 'trim');//订单编号

        if (empty($user) || empty($goodsId) || empty($weight) || empty($price)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $basketId = 0;
        $orderInfo = D('Home/Orders')->getOrderDetail(array('orderNo'=>$orderNo));
        if (!empty($orderInfo)) $basketId = $orderInfo['basketId'];

        $data = array(
            'shopId'    =>  $user['shopId'],
            'barcode'   =>   '',
            'goodsId'   =>  $goodsId,
            'weight'    =>  $weight,
            'price'     =>  $price,
            'orderNo'   =>  $orderNo,
            'basketId'  =>  $basketId,
            'sid'       =>  $user['id'],
            'suserName'=>   $user['userName'],
            'smobile'   =>  $user['mobile'],
            'createTime'    =>  date('Y-m-d H:i:s'),
            'bFlag'     =>  1
        );

        //后加skuId
        $skuId = (int)I('skuId');
        if($skuId > 0 ){
            $data['skuId'] = $skuId;
        }
        $m = D('Merchantapi/Barcode');
        $result = $m->createBarcode($data);

        $this->ajaxReturn($result);
    }

    /**
     * 条码管理
     * 商家端
     */
    public function barcodeManage(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 0, 'intval');
        $startTime = I('startTime','','trim');
        $endTime = I('endTime','','trim');
        $barcode = I('barcode','','trim');

        if (empty($shopId)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId'    =>  $shopId,
            'barcode'   =>  $barcode,
            'startTime' =>  $startTime,
            'endTime'   =>  $endTime,
            'page'      =>  I('page', 1, 'intval'),
            'pageSize'  =>  I('pageSize', 10, 'intval')
        );

        //后加sku处理
        $m = D('Merchantapi/Barcode');
        $list = $m->getBarcodeList($param);
        $list['root'] = handlePosSkuData($list['root']);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 条码详情
     * 商家端
     */
    public function barcodeManageDetail(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId', 0, 'intval');
        $id = I('id',0,'intval');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $param = array(
            'shopId'    =>  $shopId,
            'id'   =>  $id
        );

        $m = D('Merchantapi/Barcode');
        $list = $m->getBarcodeManageDetail($param);
        $list = handlePosSkuData($list);//后加sku
        $list = getCartGoodsSku($list);

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $list;

        $this->ajaxReturn($apiRet);
    }

    /**
     * 销毁条码
     * 支持批量销毁
     * 商家端
     */
    public function destroyBarcode(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $id = I('id','','trim');

        if (empty($shopId) || empty($id)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Barcode');
        $result = $m->destroyBarcode($shopId,$id);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

    /**
     * 出库(销毁条码并修改库存)
     * 移动端
     */
    public function outStock(){

        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId','','intval');
        $barcode = I('barcode','','trim');
        $orderNo = I('orderNo', '', 'trim');//订单编号
        //后加skuId
        $skuId = (int)I('skuId');

        if (empty($shopId) || empty($barcode)) {
            $apiRet['apiInfo'] = "参数不全";
            $this->ajaxReturn($apiRet);
        }

        $m = D('Merchantapi/Barcode');
        $result = $m->outStock($shopId,$barcode,$orderNo,$skuId);

        if ($result) {
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }

        $this->ajaxReturn($apiRet);
    }

}
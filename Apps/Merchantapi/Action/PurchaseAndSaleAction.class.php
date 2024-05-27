<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 用来对接进销存,单独拿到此文件,避免和他人冲突
 */
class PurchaseAndSaleAction extends BaseAction {
    /**
     *商家注册云仓账号
     * @param string token
     * @param string username 用户名称
     * @param string userpwd 用户密码
     * @param string name 真实姓名
     * @param string mobile 手机号
     */
    public function registerUser(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $request['username'] = I('username');
        $request['userpwd'] = I('userpwd');
        $request['name'] = I('name');
        $request['mobile'] = I('mobile');
        if(empty($request['username']) || empty($request['userpwd']) || empty($request['name']) || empty($request['mobile'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数有误';
            $apiRet['apiState'] = 'error';
        }
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->registerUser($shopId,$request);
        $this->ajaxReturn($res);
    }

    /**
     *获取云仓账号
     * @param string token
     */
    public function getAdminUser(){
        //$shopId = $this->MemberVeri()['shopId'];
        $shopId = 1;
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->getAdminUser($shopId);
        $this->ajaxReturn($res);
    }

    /**
     *更新云仓账号
     * @param string token
     * @param string username 用户名称 (用户名称不能更改)
     * @param string userpwd 用户密码
     * @param string name 真实姓名
     * @param string mobile 手机号
     */
    public function updateAdminUser(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 1;
        //$request['username'] = I('username');
        $request['userpwd'] = I('userpwd');
        $request['name'] = I('name');
        $request['mobile'] = I('mobile');
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->updateAdminUser($shopId,$request);
        $this->ajaxReturn($res);
    }

    /**
     *商家添加仓库(一个商家对应一个仓库)
     * @param string token
     * @param string name 仓库名称
     * @param string locationNo 仓库编号
     */
    public function insertStorage(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $request['name'] = I('name');
        $request['locationNo'] = I('locationNo');
        if(empty($request['name']) || empty($request['locationNo'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数有误';
            $apiRet['apiState'] = 'error';
        }
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->insertStorage($shopId,$request);
        $this->ajaxReturn($res);
    }

    /**
     *获取仓库
     * @param string token
     */
    public function getStorage(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->getStorage($shopId);
        $this->ajaxReturn($res);
    }

    /**
     *获取仓库
     * @param string token
     */
    public function updateStorage(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $request = I();
        if(empty($request['name'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数有误';
            $apiRet['apiState'] = 'error';
        }
        $res = $m->updateStorage($shopId,$request);
        $this->ajaxReturn($res);
    }


    /**
     *验证商品
     * @param jsonString goodsInfo PS:[{"goodsId": 84,"nums": 2,"outLocationId": 4}] PS:outLocationId为0的时候为总仓
     */
    public function checkGoodsState(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId  =1;
        $goodsInfo = I('goodsInfo');
        if(empty($goodsInfo)){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数有误';
            $apiRet['apiState'] = 'error';
        }
        //$shopId = 1;
        $m = D('Merchantapi/PurchaseAndSale');
        $data = [];
        !empty($_POST['goodsInfo'])?$data['goodsInfo']=$_POST['goodsInfo']:false;
        $rs = $m->checkGoodsState($shopId,$data);
        $this->ajaxReturn($rs);
    }

    /**
     *商家生成调拨单
     * @param string description PS:备注信息
     * @param jsonString goodsId PS:[{"goodsId": 84,"nums": 2,"outLocationId": 4,"description": "我的备注"},{"goodsId": 85,"nums": 1,"outLocationId": 4,"description": "我的备注2"}]
     */
    public function transferSlip(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $data = [];
        !empty($_POST['goodsId'])?$data['goodsId']=$_POST['goodsId']:false;
        !empty($_POST['description'])?$data['description']=$_POST['description']:false;
        $rs = $m->transferSlip($shopId,$data);
        $this->ajaxReturn($rs);
    }

    /*
     * 商家获取调拨单
     * @param string token
     * @param int page
     * */
    public function getTransferSlip(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $page = I('page',1);
        $rs = $m->getTransferSlip($shopId,$page);
        $this->ajaxReturn($rs);
    }

    /**
     *更新商品库存 单个商品
     * @param string token
     * @param int goodsId
     * @param string propertys 例子:[{"locationId":5,"quantity":"1000.0","unitCost":1000,"amount":1000000,"batch":"","prodDate":"","safeDays":"","validDate":"","id":"40"},{"locationId":5,"quantity":"11111.0","unitCost":1111,"amount":12344321,"batch":"","prodDate":"","safeDays":"","validDate":"","id":"41"}]
     */
    public function updateGoodsPropertys(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 1;
        $goodsId = I('goodsId');
        if(empty($goodsId)){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数有误';
            $apiRet['apiState'] = 'error';
        }
        $m = D('Home/Goods');
        $rs = $m->updateGoodsPropertys($goodsId,$shopId);
        $this->ajaxReturn($rs);
    }

    /**
     *同步云端库存
     */
    public function synchronizationGoodsStock(){
        //$shopId = $this->MemberVeri()['shopId'];
        $shopId = 1;
        $m = D('Merchantapi/PurchaseAndSale');
        $rs = $m->synchronizationGoodsStock();
        $this->ajaxReturn($rs);
    }


    /**
     *获取仓库列表
     * @param string token
     * @param int page
     */
    public function getAllStorage(){
        $shopId = $this->MemberVeri()['shopId'];
        $page = I('page',1);
        //$shopId = 1;
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->getAllStorage($shopId,$page);
        $this->ajaxReturn($res);
    }

    /**
     *获取供应商
     * @param string token
     * @param int page
     */
    public function getAllContact(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 1;
        $page = I('page',1);
        $m = D('Merchantapi/PurchaseAndSale');
        $res = $m->getAllContact($shopId,$page);
        $this->ajaxReturn($res);
    }

    /**
     *商家生成采购单
     * @param string contactId PS:供应商
     * @param string description PS:备注信息
     * @param jsonString goods PS:[{"goodsId": 84,"nums": 2,"outLocationId":28,"mainUnit":"斤","buId":3,"description":"我的备注"},{"goodsId": 84,"nums": 1,"mainUnit":"斤","outLocationId":28,"buId":3,"description": "我的备注2"}]
     */
    public function purchasingOrder(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $data = [];
        !empty($_POST['contactId'])?$data['contactId']=$_POST['contactId']:false;
        !empty($_POST['contactName'])?$data['contactName']=$_POST['contactName']:false;
        !empty($_POST['description'])?$data['description']=$_POST['description']:false;
        !empty($_POST['goods'])?$data['goods']=$_POST['goods']:false;
        $rs = $m->purchasingOrder($shopId,$data);
        $this->ajaxReturn($rs);
    }

    /*
     * 商家获取购货单
     * @param string token
     * @param int page
     * */
    public function getPurchasingOrder(){
        $shopId = $this->MemberVeri()['shopId'];
        //$shopId = 2;
        $m = D('Merchantapi/PurchaseAndSale');
        $page = I('page',1);
        $rs = $m->getPurchasingOrder($shopId,$page);
        $this->ajaxReturn($rs);
    }
}

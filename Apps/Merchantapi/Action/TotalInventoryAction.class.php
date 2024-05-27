<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 总销存
 */
class TotalInventoryAction extends BaseAction {
    /*
     * 本地同步云仓商品的商品相册
     * @param string token
     * @param int type 类型(1=>选择性同步,2=>全部更新(一键同步))
     * @param string goodsId 商品id(type为2时可以为空) 例子:["1","2","3"]
     * */
    public function synchroGoodsGallery(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId' => 2,'shopSn'=>'002'];
        $parameter['shopId'] = $shopInfo['shopId'];
        $parameter['type'] = I('type');
        $parameter['goodsId'] = $_POST['goodsId'];
        $parameter['shopSn'] = $shopInfo['shopSn'];
        if(empty($parameter['type']) || $parameter['shopSn']){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->synchroGoodsGallery($parameter);
        $this->ajaxReturn($res);
    }

    /*
     * 获取云仓库的商品列表
     * @param int warehouseId PS:仓库id,如果选择总仓请传 0
     * */
    public function getWareHouseGoods(){
        //$shopInfo = $this->MemberVeri();
        $warehouseId = I('warehouseId',0);
        $m = D('Merchantapi/Jxc');
        $res = $m->getWareHouseGoods($warehouseId);
        $this->ajaxReturn($res);
    }

    /*
     * 同步商品(PS:除了可以同步自己云仓的商品还可以同步总仓的商品)
     * @param string token
     * @param int warehouseType PS:(选择仓库:0=>总仓,1=>云仓(默认))
     * @param int type PS:类型(1=>选择更新,2=>一键同步)
     * @param jsonString goodsId PS:商品id(type为2时可以为空) 例子:["1","2","3"]
     * */
    public function synchroGoods(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId' => 2,'shopSn'=>'002'];
        $parameter['shopId'] = $shopInfo['shopId'];
        $parameter['type'] = I('type');
        $parameter['goodsId'] = $_POST['goodsId'];
        $parameter['warehouseType'] = I('warehouseType',1);
        $parameter['shopSn'] = $shopInfo['shopSn'];
        if(empty($parameter['type']) || $parameter['shopSn']){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->synchroGoods($parameter);
        $this->ajaxReturn($res);
    }

    /*
     * 获取店铺对应的仓库的信息
     * @param string token
     * */
    public function getShopWareHouse(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $shopSn = $shopInfo['shopSn'];
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Jxc');
        $res = $m->getShopWareHouse($shopId,$shopSn);
        $this->ajaxReturn($res);
    }

    /*
     * 获取所有的仓库列表
     * @param string token
     * */
    public function getAllWareHouse(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $shopSn = $shopInfo['shopSn'];
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Jxc');
        $res = $m->getAllWareHouse($shopId,$shopSn);
        $this->ajaxReturn($res);
    }

    /*
     * 商品列表(采购专用) PS:本地和云仓共有的
     * @param string token
     * */
    public function getPurchaseGoods(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Jxc');
        $paramete = I();
        $paramete['shopId'] = $shopInfo['shopId'];
        $paramete['shopSn'] = $shopInfo['shopSn'];
        $page = $m->getPurchaseGoods($paramete);
        $this->ajaxReturn($page);
    }

    /*
     * 商户端生成采购单
     * @param string token
     * @param jsonString goods 例子: [{"goods":81,"nums": 1,"data": "备注信息"},{"goods": 81,"nums": 1,"data":"备注信息"}
     * @param string remark PS:备注信息 例子:备注信息
]
     * */
    public function createPurchase(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['goods'] = $_POST['goods'];
        $param['remark'] = I('remark');
        if(empty($param['goods'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->createPurchase($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关采购单
     * @param string token
     * @param int storage PS:入库状态[0:未入库|1:部分入库|2:已入库|3:未审核|4:已审核|20:全部]
     * @param string startDate PS:开始时间 例子:2019-10-06 12:00:00
     * @param string endDate PS:结束时间 例子:2019-10-06 20:00:00
     * @param string number PS:单号
     * */
    public function getShopPurchase(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['storage'] = $_POST['storage'];
        $param['startDate'] = I('startDate');
        $param['endDate'] = I('endDate');
        $param['number'] = I('number');
        $m = D('Merchantapi/Jxc');
        $res = $m->getShopPurchase($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关采购单
     * @param string token
     * @storage int purchaseId PS:采购单id
     * */
    public function getShopPurchaseInfo(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['purchaseId'] = I('purchaseId',0);
        $m = D('Merchantapi/Jxc');
        if(empty($param['purchaseId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getShopPurchaseInfo($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取云仓储的商品 PS:(总仓和申请的分仓和本地仓都有的商品才会展示出来)
     * 用于申请调拨单
     * @param string token
     * @param int warehouseId 仓库id
     * 搜索
     * @param string goodsName 商品名称
     * @param string goodsSn 商品编号
     * @param string code 条形码
     * */
    public function getRoomGoods(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1,'shopSn'=>'00181107'];
        $param = I();
        $m = D('Merchantapi/Jxc');
        if(is_null(I('warehouseId')) || I("warehouseId") === ""){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请先选择要申请调拨的仓库';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $param['shopId'] = $shopInfo['shopId'];
        $param['warehouseId'] = I("warehouseId");
        $res = $m->getRoomGoods($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取调拨仓库
     * @param string token
     * @param string name PS:仓库名称
     * */
    public function getAllRoomHouse(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $shopSn = $shopInfo['shopSn'];
        $shopId = $shopInfo['shopId'];
        $m = D('Merchantapi/Jxc');
        $param['name'] = I("name");
        $res = $m->getAllRoomHouse($shopId,$param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端生成调拨单
     * @param string token
     * @param jsonString goods 例子:
     * [
         {"goods":81,"nums": 1,"data":"备注信息", "towarehouseId":1,"roomId":1},
         {"goods": 81,"nums": 1,"data":"备注信息", "towarehouseId":1,"roomId":1}
        ]
     * @param string remark PS:备注信息 例子:备注信息
     * */
    public function createOtpurchase(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['goods'] = $_POST['goods'];
        $param['remark'] = I('remark');
        if(empty($param['goods'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->createOtpurchase($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关调拨单
     * @param string token
     * @param int type PS:审核状态[0:等待确认|1:已确认|2:已拒绝|3:未审核|4:已审核|5:完成|20:全部]
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string number PS:单号
     * */
    public function getShopOtpurchase(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['type'] = $_POST['type'];
        $param['startDate'] = I('startDate');
        $param['endDate'] = I('endDate');
        $param['number'] = I('number');
        $m = D('Merchantapi/Jxc');
        $res = $m->getShopOtpurchase($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关调拨单(被申请调拨)
     * @param string token
      * @param int type PS:审核状态[0:等待确认|1:已确认|2:已拒绝|3:未审核|4:已审核|5:完成|20:全部]
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string number PS:单号
     * */
    public function getShopOtpurchaseTo(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['type'] = $_POST['type'];
        $param['startDate'] = I('startDate');
        $param['endDate'] = I('endDate');
        $param['number'] = I('number');
        $m = D('Merchantapi/Jxc');
        $res = $m->getShopOtpurchaseTo($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关调拨单详情
     * @param string token
     * @storage int allocationclassId PS:调拨单id
     * */
    public function getShopOtpurchaseInfo(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['allocationclassId'] = I('allocationclassId',0);
        $m = D('Merchantapi/Jxc');
        if(empty($param['allocationclassId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getShopOtpurchaseInfo($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端获取相关调拨单详情(被申请调拨)
     * @param string token
     * @storage int allocationclassId PS:调拨单id
     * */
    public function getShopOtpurchaseInfoTo(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['allocationclassId'] = I('allocationclassId',0);
        $m = D('Merchantapi/Jxc');
        if(empty($param['allocationclassId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getShopOtpurchaseInfoTo($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端更改调拨单的状态 PS:(被申请调拨)
     * @param string token
     * @storage int allocationclassId PS:调拨单id
     * @storage int type 状态 PS:[1:已确认|2:已拒绝]
     * @storage string desc 拒绝原因
     * */
    public function updateShopOtpurTypeTo(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['allocationclassId'] = I('allocationclassId',0);
        $param['type'] = I('type',1);
        $param['desc'] = I('desc');
        $m = D('Merchantapi/Jxc');
        if(empty($param['allocationclassId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->updateShopOtpurTypeTo($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取采购单的操作日志
     * @param string token
     * @param int opurchaseclassId PS:采购单id
     * */
    public function getPurchaseActionLog(){
        $shopInfo = $this->MemberVeri();
        $param['shopId'] = $shopInfo['shopId'];
        $param['opurchaseclassId'] = I('opurchaseclassId');
        if(empty($param['opurchaseclassId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->getPurchaseActionLog($param);
        $this->ajaxReturn($res);
    }

    /*
     * 商户端更改调拨单的状态 PS:(申请调拨)
     * @param string token
     * @storage int allocationclassId PS:调拨单id
     * @storage int type 状态(可扩展) PS:[5:已完成]
     * */
    public function updateShopOtpurType(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数错误';
        $apiRet['apiState'] = 'error';
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['allocationclassId'] = I('allocationclassId',0);
        $param['type'] = I('type',5);
        if(!in_array($param['type'],[5])){
            $apiRet['apiInfo'] = "type值不正确";
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        if(empty($param['allocationclassId'])){
            $this->ajaxReturn($apiRet);
        }
        $res = $m->updateShopOtpurType($param);
        $this->ajaxReturn($res);
    }

    /*
     * 获取调拨单的操作日志
     * @param string token
     * @param int allocationclassId PS:调拨单id
     * */
    public function getAllocationclassLog(){
        $shopInfo = $this->MemberVeri();
        $param['shopId'] = $shopInfo['shopId'];
        $param['allocationclassId'] = I('allocationclassId');
        if(empty($param['allocationclassId'])){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数错误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D('Merchantapi/Jxc');
        $res = $m->getAllocationclassLog($param);
        $this->ajaxReturn($res);
    }

    /*
     * 统计调拨单各个状态下的数量
     * @param string token
     * @param string status 状态:[0:等待确认|1:已确认|2:已拒绝|3:未审核|4:已审核|5:完成],多个用英文逗号分隔,例如: 0,1,2,3
     * */
    public function getAllocationclassCount(){
        $shopInfo = $this->MemberVeri();
        //$shopInfo = ['shopId'=>1,'shopSn'=>'00181107'];
        $param['shopId'] = $shopInfo['shopId'];
        $param['status'] = I('status');
        $m = D('Merchantapi/Jxc');
        $res = $m->getAllocationclassCount($param);
        $this->ajaxReturn($res);
    }

}
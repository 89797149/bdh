<?php
namespace Made\Action;
use Think\Cache\Driver\Redis;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
首页控制器 app
 */
class MerchantapiAction extends BaseAction {

    /**
     * 用户(会员)登陆,并获取会员信息
     * 使用账号和密码登录
     */
//    public function userLogin(){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '登陆失败';
//        $apiRes['apiState'] = 'error';
//        $apiRes['apiData'] = null;
//
//        $mobileNumber = I("loginName");
//
//        if (empty($mobileNumber)) {
//            $apiRes['apiInfo'] = '参数不全';
//            $this->ajaxReturn($apiRes);
//        }
//
//        $users = M("users");
//        $mod = $users->where("loginName = '{$mobileNumber}' or userPhone = '{$mobileNumber}'")->find();//获取安全码
//        if (empty($mod)) {
//            $apiRes["apiInfo"] = "账号不存在";
//            $this->ajaxReturn($apiRes);
//        }
//
//        $mod['memberToken'] = getUserTokenByUserId($mod['userId']);
//        $mod['historyConsumeIntegral'] = historyConsumeIntegral($mod['userId']);
//
//        $apiRes['apiCode'] = 0;
//        $apiRes['apiInfo'] = '登陆成功';
//        $apiRes['apiState'] = 'success';
//        $apiRes['apiData'] = $mod;
//        $this->ajaxReturn($apiRes);
//    }
//
//    /**
//     * 商户后台->新增商品
//     */
//    public function Merchantapi_Goods_insert(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $m = D('Made/Merchantapi');
//        $rs = array();
//        $rs = $m->Merchantapi_Goods_insert($shopInfo);
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 商户后台->编辑商品
//     */
//    public function Merchantapi_Goods_edit(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $m = D('Made/Merchantapi');
//        if(!(int)I('id')){
//            $this->returnResponse(-1,'操作失败',array());
//        }
//        $rs = array();
//        $rs = $m->Merchantapi_Goods_edit($shopInfo);
//        $this->ajaxReturn($rs);
//    }
//
//    /*
//     *获取ERP商品分类
//     *@param string $level PS:等级id
//     *@param string $parid PS:父id
//     *@param int $page PS:页码
//     *@param int $pageSize PS:分页条数,默认15条
//     * */
//    public function getERPGoodsCat(){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $level = (int)I('level',0);
//        $parid = I('parid');
//        $page = (int)I('page',1);
//        $pageSize = (int)I('pageSize',15);
//        $mod = D('Made/Merchantapi');
//        $res = $mod->getERPGoodsCat($level,$parid,$page,$pageSize);
//        $this->ajaxReturn($res);
//    }
//
//    /*
//     *获取ERP商品列表,拉取ERP商品到本地可以用到
//     *@param string typeId1 PS:一级分类(PS:如果三级分类id都为空,返回全部数据)
//     *@param string typeId2 PS:二级分类
//     *@param string typeId3 PS:三级分类
//     *@param int $page PS:页码
//     *@param int $pageSize PS:分页条数,默认15条
//     * */
//    public function getERPGoods(){
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(!isset($request['typeId1']) || !isset($request['typeId2']) || !isset($request['typeId3'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $page = (int)I('page',1);
//        $pageSize = (int)I('pageSize',15);
//        $request['page'] = $page;
//        $request['pageSize'] = $pageSize;
//        $mod = D('Made/Merchantapi');
//        $res = $mod->getERPGoods($request);
//        $this->ajaxReturn($res);
//    }
//
//
//    /*
//     *拉取ERP商品到本地
//     *@param string goodsId PS:多个商品用逗号分隔
//     * */
//    public function syncERPGoods(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo['shopId'] = 1 ;
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(empty($request['goodsId'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $request['goodsId'] = $request['goodsId'];
//        $request['shopId'] = $shopInfo['shopId'];
//        $mod = D('Made/Merchantapi');
//        $res = $mod->syncERPGoods($request);
//        $this->ajaxReturn($res);
//    }
//
//
//    /*
//     *本地订单同步到ERP PS:放到合适的位置,因为是单向同步,只同步一次
//     *@param int orderId PS:订单id,多个用逗号分隔
//     * */
//    public function syncERPOrder(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(empty($request['orderId'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $request['shopId'] = $shopInfo['shopId'];
//        $request['orderId'] = $request['orderId'];
//        $mod = D('Made/Merchantapi');
//        $res = $mod->syncERPOrder($request);
//        $this->ajaxReturn($res);
//    }
//
//    /**
//     * 职员管理->添加职员
//     */
//    public function Merchantapi_User_addUser(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $parameter = I();
//        $parameter['shopId'] = $shopInfo['shopId'];
//        $m = D('Made/Merchantapi');
//        $msg = '';
//        $res = $m->Merchantapi_User_addUser($parameter,$msg);
//        if(!$res){
//            $this->returnResponse(-1,$msg?$msg:'添加失败');
//        }
//        $this->returnResponse(1,'添加成功');
//    }
//
//    /**
//     * 职员管理->编辑职员
//     */
//    public function Merchantapi_User_edit(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $parameter = I();
//        $parameter['shopId'] = $shopInfo['shopId'];
//        $m = D('Made/Merchantapi');
//        $msg = '';
//        $res = $m->Merchantapi_User_edit($parameter,$msg);
//        if($res === false){
//            $this->returnResponse(-1,$msg?$msg:'编辑失败');
//        }
//        $this->returnResponse(1,'编辑成功');
//    }
//
//    /**
//     * 职员管理->删除职员
//     */
//    public function Merchantapi_User_del(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>1];
//        $parameter = I();
//        $parameter['shopId'] = $shopInfo['shopId'];
//        $m = D('Made/Merchantapi');
//        $msg = '';
//        $res = $m->Merchantapi_User_del($parameter,$msg);
//        if(!$res){
//            $this->returnResponse(-1,$msg?$msg:'删除失败');
//        }
//        $this->returnResponse(1,'删除成功');
//    }
//
//    /*
//     *本地调拨单同步到ERP
//     *@param int otpurchaseId PS:调拨单id,多个用英文逗号分隔
//     * */
//    public function syncOutBill(){
//        $shopInfo = $this->shopMemberVeri();
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(empty($request['otpurchaseId'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $request['shopId'] = $shopInfo['shopId'];
//        $mod = D('Made/Merchantapi');
//        $res = $mod->syncOutBill($request);
//        $this->ajaxReturn($res);
//    }
//
//    /*
//     * 商户端生成调拨单
//     * @param string token
//     * @param jsonString goods 例子:
//     * [
//         {"goods":81,"nums": 1,"data":"备注信息", "towarehouseId":1,"roomId":1},
//         {"goods": 81,"nums": 1,"data":"备注信息", "towarehouseId":1,"roomId":1}
//        ]
//     * @param string remark PS:备注信息 例子:备注信息
//     * */
//    public function Merchantapi_TotalInventory_createOtpurchase(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
//        $param['shopId'] = $shopInfo['shopId'];
//        $param['goods'] = $_POST['goods'];
//        $param['remark'] = I('remark');
//        if(empty($param['goods'])){
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数错误';
//            $apiRet['apiState'] = 'error';
//            $this->ajaxReturn($apiRet);
//        }
//        $m = D('Made/Merchantapi');
//        $res = $m->Merchantapi_TotalInventory_createOtpurchase($param);
//        $this->ajaxReturn($res);
//    }
//
//
//    /*
//     *本地商品批量同步到ERP
//     *@param string goodsIds PS:本地商品id,多个用英文逗号分隔
//     * */
//    public function syncGoodsToERP(){
//        $shopInfo = $this->shopMemberVeri();
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(empty($request['goodsIds'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $param['shopId'] = $shopInfo['shopId'];
//        $param['goodsIds'] = trim($request['goodsIds'],',');
//        $mod = D('Made/Merchantapi');
//        $res = $mod->syncGoodsToERP($param);
//        $this->ajaxReturn($res);
//    }
//
//    /**
//     * 商家发货配送订单
//     */
//    public function Merchantapi_Orders_shopOrderDelivery(){
//        $shopInfo = $this->shopMemberVeri();
//        $USER = session('WST_USER');
//        $morders = D('Made/Merchantapi');
//        $obj["userId"] = (int)$shopInfo['userId'];
//        $obj["shopId"] = (int)$shopInfo['shopId'];
//        $obj["orderId"] = (int)I("orderId");
//        $obj["weightGJson"]=I('weightGJson');
//        //自定义数据结构解析 $str = 'goodsId=4@goodWeight=30#goodsId=5@goodWeight=50';
//        if(!empty($obj["weightGJson"])){
//            $result = explode('#', $obj["weightGJson"]);
//            for($i=0;$i<count($result);$i++){
//                $result[$i] = explode('@', $result[$i]);
//                for($j=0;$j<count($result[$i]);$j++){
//                    $result[$i][$j] = explode('=', $result[$i][$j]);
//                }
//            }
//            $goodsWeight = array();
//            foreach($result as $index => $a){
//                array_push($goodsWeight,array($a[0][0]=>$a[0][1],$a[1][0]=>$a[1][1]));
//            }
//            $obj["weightGJson"]=$goodsWeight;
//
//        }else{
//            $obj["weightGJson"]=array();
//        }
//        if(!is_array($obj["weightGJson"])){
//            $rsdata["status"] = -1;
//            $rsdata['data'] = $obj["weightGJson"];
//            $rsdata['msg'] = 'weightGJson 接受异常';
//            $this->ajaxReturn($rsdata);
//        }
//        $obj["isShopGo"] = (int)I("isShopGo",0);//如果值为1 则指定为商家自定配送
//        //$obj["deliverType"] = (int)I("deliverType");
//        $rs = $morders->Merchantapi_Orders_shopOrderDelivery($obj);
//        if($rs['status'] = 1){
//            $this->returnResponse(1,'操作成功',$rs);
//        }else{
//            $this->returnResponse(-1,'操作失败',[]);
//        }
//        $this->ajaxReturn($rs);
//    }
//
//
//    /**
//     * 商家发货配送订单
//     */
//    public function Merchantapi_Orders_batchShopOrderDelivery(){
//        $shopInfo = $this->shopMemberVeri();
//        $morders = D('Made/Merchantapi');
//        $rs = $morders->Merchantapi_Orders_batchShopOrderDelivery($shopInfo);
//        if($rs['status'] = 1){
//            $this->returnResponse(1,'操作成功',[]);
//        }else{
//            $this->returnResponse(-1,'操作失败',[]);
//        }
////		$this->ajaxReturn($rs);
//    }
//
//
//    /*
//     * 采购单同步到ERP
//     * */
//    public function syncInputBill(){
//        $shopInfo = $this->shopMemberVeri();
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数不全';
//        $apiRes['apiState'] = 'error';
//        $request = I();
//        if(empty($request['opurchaseclassId'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $request['shopId'] = $shopInfo['shopId'];
//        $mod = D('Made/Merchantapi');
//        $res = $mod->syncInputBill($request);
//        $this->ajaxReturn($res);
//    }
//
//    /*
//     * 商户端生成采购单
//     * @param string token
//     * @param jsonString goods 例子: [{"goods":81,"nums": 1,"data": "备注信息"},{"goods": 81,"nums": 1,"data":"备注信息"}
//     * @param string remark PS:备注信息 例子:备注信息
//]
//     * */
//    public function Merchantapi_TotalInventory_createPurchase(){
//        $shopInfo = $this->shopMemberVeri();
//        //$shopInfo = ['shopId'=>2,'shopSn'=>'002'];
//        $param['shopId'] = $shopInfo['shopId'];
//        $param['goods'] = $_POST['goods'];
//        $param['remark'] = I('remark');
//        if(empty($param['goods'])){
//            $apiRet['apiCode'] = -1;
//            $apiRet['apiInfo'] = '参数错误';
//            $apiRet['apiState'] = 'error';
//            $this->ajaxReturn($apiRet);
//        }
//        $m = D('Made/Merchantapi');
//        $res = $m->Merchantapi_TotalInventory_createPurchase($param);
//        $this->ajaxReturn($res);
//    }
//
//
//    /**
//     * 获取数据库表变化
//     */
//    public function handleTableData(){
//        $str1 = htmlspecialchars_decode(I('str'));
//        $str1Arr = json_decode($str1,true);
//        $str2 = htmlspecialchars_decode(I('str2'));
//        $str2Arr = json_decode($str2,true);
//        $type = I('type',1);//库的选择
//        //查出变动的表
//        if(!empty($str2Arr) && !empty($str1Arr)){
//            $diff = [];
//            foreach ($str1Arr as $key1=>$value1){
//                foreach ($str2Arr as $key2=>$value2){
//                    if($value2['tableName'] == $value1['tableName'] && $value2['rows'] > $value1['rows']){
//                        $value2['diffnums'] = $value2['rows'] - $value1['rows'];
//                        $diff[] = $value2;
//                    }
//                }
//            }
//            $this->ajaxReturn(['code'=>0,'msg'=>'数据表波动','data'=>$diff]);
//        }
//
//        if($type == 1){
//            $sql = "select a.name as tableName,max(b.rows) as rows from sysobjects as a, sysindexes as b
//where a.id=b.id and a.xtype ='u'
//group by a.name
//order by max(b.rows) desc";
//            $db= sqlServerDB();
//            $conn = $db->prepare($sql);
//            $conn->execute();
//            $result = hanldeSqlServerData($conn);
//        }
//        $myfile = fopen("Apps/Made/debug.txt", "a+") or die("Unable to open file!");
//        $text = json_encode($result);
//        fwrite($myfile, "全表简略信息:$text\r\n");
//        fclose($myfile);
//
//        $this->ajaxReturn($result);
//    }

    //备注:该文件里面的方法都是从原有对应模块中直接复制过来的,作为订制方法
    /**
     * 更新店铺出售中商品的库存
     * @param string token
     * */
    public function updateShopErpGoodsStock(){
        $shopInfo = $this->shopMemberVeri();
        $m = D('Made/Merchantapi');
        $shopId = $shopInfo['shopId'];
        $res = $m->updateShopErpGoodsStock($shopId);
        $this->ajaxReturn($res);
    }


    /**
     * 商家批量受理订单
     */
    public function Merchantapi_Orders_batchShopOrderAccept(){
        $shopInfo = $this->shopMemberVeri();
        $morders = D('Made/Merchantapi');
        $rs = $morders->Merchantapi_Orders_batchShopOrderAccept($shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 保存分类
     */
    public function Merchantapi_ShopsCats_addCats(){
        $parameter = I();
        $shopInfo = $this->shopMemberVeri();
        $parameter['shopId'] = $shopInfo['shopId'];
        if(empty($parameter['number'])){
            $this->returnResponse(-1,'分类编码不能为空',[]);
        }
        $m = D('Made/Merchantapi');
        $rs = $m->Merchantapi_ShopsCats_addCats($parameter);
        if($rs['code'] == 0){
            $this->returnResponse(1,'操作成功',[]);
        }else{
            $msg = !empty($rs['msg'])?$rs['msg']:'操作失败';
            $this->returnResponse(-1,$msg,[]);
        }
    }

    /**
     * 修改分类信息
     */
    public function Merchantapi_ShopsCats_editName(){
        $shopInfo = $this->shopMemberVeri();
        $m = D('Made/Merchantapi');
        $rs = array();
        $requestParams = I();
        $params = [];
        $params['id'] = null;
        $params['catName'] = null;
        $params['catSort'] = null;
        $params['number'] = null;
        parm_filter($params,$requestParams);
        if(empty($params['id'])){
            $error = array('status'=>-1,'msg'=>'参数错误');
            $this->ajaxReturn($error);
        }
        $rs = $m->Merchantapi_ShopsCats_editName($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 同步erp职员信息
     * @param token
     */
    public function syncEmployee(){
        $shopInfo = $this->shopMemberVeri();
        $m = D('Made/Merchantapi');
        $rs = $m->syncEmployee($shopInfo);
        $this->ajaxReturn($rs);
    }

    /*
     * 测试用
     * */
    public function testApi(){
        $mod = D('Made/Merchantapi');
        $res = $mod->testApi();
        $this->ajaxReturn($res);
    }

}

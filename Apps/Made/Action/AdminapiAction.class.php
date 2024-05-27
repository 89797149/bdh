<?php
namespace Made\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 *订制模块 后台(Admin)
 * PS:方法名定义: 模块名_控制器_方法名,例如后台修改店铺在此处定义为Admin_Shops_edit
 *
 */
class AdminapiAction extends BaseAction {

//    /**
//     * 总后台->店铺->新增|修改操作
//     */
//    public function Adminapi_Shops_edit(){
//        $this->isAdminapiLogin();
//        $m = D('Made/Adminapi');
//        if(I('id',0)>0){
//            $this->AdminapicheckPrivelege('dplb_02');
//            if(I('shopStatus',0)<=-1){
//                $rs = $m->Admin_Shops_reject();
//            }else{
//                $rs = $m->Admin_Shops_edit();
//            }
//        }else{
//            $this->checkPrivelege('dplb_01');
//            $rs = $m->Admin_Shops_insert();
//        }
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 总后台->店铺->删除店铺
//     */
//    public function Admin_Shops_del(){
//        $this->isAdminapiLogin();
//        $this->AdminapicheckPrivelege('dplb_03');
//        $m = D('Made/Adminapi');
//        $rs = $m->Admin_Shops_del();
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 总后台->店铺->批量同步店铺信息到ERP
//     * @param string $shopId PS:店铺id,多个店铺用英文逗号隔开
//     */
//    public function batchShopToErp(){
//        $this->isAdminLogin();
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数有误';
//        $apiRes['apiState'] = 'error';
//        $m = D('Made/Adminapi');
//        $request = I();
//        if(empty($request['shopId'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $param = [];
//        $param['shopId'] = trim($request['shopId'],',');
//        $rs = $m->batchShopToErp($param);
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 总后台->店铺->获取ERP分支机构数据(只做一级)
//     *@param string keyword PS:分支机构名称
//     *@param int page PS:页码
//     *@param int pageSize PS:分页条数,默认15条
//     */
//    public function getSTypeList(){
//        $this->isAdminLogin();
//        $m = D('Made/Adminapi');
//        $keyword = I('keyword');
//        $page = (int)I('page',1);
//        $pageSize = (int)I('pageSize',15);
//        $param = [];
//
//        $param['leveal'] = 1;//固定值为1
//        $param['keyword'] = $keyword;
//        $param['page'] = $page;
//        $param['pageSize'] = $pageSize;
//        $rs = $m->getSTypeList($param);
//        $this->ajaxReturn($rs);
//    }
//
//    /**
//     * 总后台->店铺->分支机构同步到本地店铺
//     * @param string Sid PS:分支机构中的Sid字段,多个用英文逗号分隔
//     */
//    public function batchSTypeToShops(){
//        $this->isAdminLogin();
//        $apiRes['apiCode'] = -1;
//        $apiRes['apiInfo'] = '参数有误';
//        $apiRes['apiState'] = 'error';
//        $m = D('Made/Adminapi');
//        $request = I();
//        if(empty($request['Sid'])){
//            $this->ajaxReturn($apiRes);
//        }
//        $param = [];
//        $param['Sid'] = trim($request['Sid'],',');
//        $rs = $m->batchSTypeToShops($param);
//        $this->ajaxReturn($rs);
//    }

    //分界线,需求更改
    //PS:变量名称别讲究规范,管家婆的数据字段就是这样的
    /**
     * 获取管家婆仓库列表
     * @param string Name 仓库简名
     * @param string FullName 仓库名称
     * @param int page
     * @param int pageSize
     */
    public function getErpStockList(){
        $this->isAdminLogin();
        $params = [];
        $params['Name'] = I('Name');
        $params['FullName'] = I('FullName');
        $params['page'] = (int)I('page',1);
        $params['pageSize'] = (int)I('pageSize',15);
        $m = D('Made/Adminapi');
        $rs = $m->getErpStockList($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取管家婆商品分类(商品分类制作两级,原因:小鸟店铺分类只有两级)
     * @param string leveal PS:等级(1:一级分类|2:二级分类)
     * @param string ParId PS:父id,获取一级分类时传00000,二级分类时取值分类列表中的typeId字段
     * */
    public function getErpGoodsCatList(){
        $this->isAdminLogin();
        $params = [];
        $params['leveal'] = (int)I('leveal');
        $params['ParId'] = I('ParId');
        $mod = D('Made/Adminapi');
        $rs = $mod->getErpGoodsCatList($params);
        $this->ajaxReturn($rs);
    }

    /**
     *获取管家婆商品品牌
     * */
    public function getErpBrandarList(){
        $this->isAdminLogin();
        $mod = D('Made/Adminapi');
        $rs = $mod->getErpBrandarList();
        $this->ajaxReturn($rs);
    }

    /**
     * 获取管家婆商品列表
     * @param string FullName 商品名称
     * @param string BrandarTypeID 品牌id
     * @param string typeId1 商品一级分类id
     * @param string typeId2 商品二级分类id
     * @param int page 分页
     * @param int pageSize 分页条数
     */
    public function getErpGoodsList(){
        $this->isAdminLogin();
        $params = [];
        $params['FullName'] = I('FullName');
        $params['BrandarTypeID'] = I('BrandarTypeID');
        $params['typeId1'] = I('typeId1');
        $params['typeId2'] = I('typeId2');
        $params['page'] = I('page',1);
        $params['pageSize'] = I('pageSize',15);
        if(empty($params['typeId1']) && empty($params['typeId2'])){
            $rs = returnData(null,-1,'error','参数有误，至少要选择一个商品分类');
            $this->ajaxReturn($rs);
        }
        $mod = D('Made/Adminapi');
        $rs = $mod->getErpGoodsList($params);
        $this->ajaxReturn($rs);
    }

    /**
     *获取管家婆地区价格列表
     * */
    public function getErpPriceNameList(){
        $this->isAdminLogin();
        $mod = D('Made/Adminapi');
        $rs = $mod->getErpPriceNameList();
        $this->ajaxReturn($rs);
    }

    /**
     *同步管家婆数据
     * @param string goodsData 是否导入商品数据(true:是|false:否)
     * @param int allGoods 导入类型(1:全部商品|2:部分商品),PS:当选则点击部分商品的时候出现选择商品的操作,点击弹出商品列表给用户选择商品
     * @param string goodsTypeIds 多个商品用英文逗号分隔,取值商品列表的typeId字段
     * @param string brandData 是否导入品牌数据(true:是|false:否)
     * @param string goodsCatData 是否导入分类数据(true:是|false:否)
     * @param string stockTypeId 选择的同步仓库的typeId
     * @param string priceNameId 选择的地区售价Id
     * @param string goodsStock 同步库存(true:是|false:否)
     * @param string updateGoods 是否更新商品信息(true:是|false:否),PS:是,除商品编码、商品所属分类、商品关联品牌外的字段做二次更新;
    否,除商品计量单位、商品预设售价外的字段不做二次更新
     * */
    public function syncErpGoods(){
        $this->isAdminLogin();
        $params = [];
        $params['goodsData'] = I('goodsData',"true");
        $params['allGoods'] = (int)I('allGoods',1);
        $params['goodsTypeIds'] = trim(I('goodsTypeIds'),',');
        $params['brandData'] = I('brandData',"true");
        $params['goodsCatData'] = I('goodsCatData',"true");
        $params['stockTypeId'] = I('stockTypeId');
        $params['priceNameId'] = I('priceNameId');
        $params['goodsStock'] = I('goodsStock',"true");
        $params['updateGoods'] = I('updateGoods',"true");
        if($params['allGoods'] == 2 && empty($params['goodsTypeIds'])){
            $rs = returnData(null,-1,'error','参数有误，导入部分商品的时候请选择需要导入的商品');
            $this->ajaxReturn($rs);
        }
        if(empty($params['stockTypeId'])){
            $rs = returnData(null,-1,'error','参数有误，请选择库存同步仓库');
            $this->ajaxReturn($rs);
        }
        if(empty($params['priceNameId'])){
            $rs = returnData(null,-1,'error','参数有误，请选择地区价格');
            $this->ajaxReturn($rs);
        }
        $mod = D('Made/Adminapi');
        $rs = $mod->syncErpGoods($params);
        $this->ajaxReturn($rs);
    }

    ///////同步店铺商品信息
    /**
     * 获取门店列表
     * @param string shopName 门店名称
     * @param string shopSn 门店编号
     * @param int page
     * @param int pageSize
     * */
    public function getShopList()
    {
        $this->isAdminLogin();
        $params['shopName'] = I('shopName');
        $params['shopSn'] = I('shopSn');
        $params['page'] = (int)I('page',1);
        $params['pageSize'] = (int)I('pageSize',15);
        $mod = D('Made/Adminapi');
        $rs = $mod->getShopList($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取门店分类
     * @param int shopId 门店id
     * @param int parentId 父分类id
     * */
    public function getShopCatList()
    {
        $this->isAdminLogin();
        $shopId = (int)I('shopId',0);
        $parentId = (int)I('parentId');
        $mod = D('Made/Adminapi');
        if(empty($shopId)){
            $rs = returnData(null,-1,'error','参数有误，请选择库存同步门店');
            $this->ajaxReturn($rs);
        }
        $rs = $mod->getShopCatList($shopId,$parentId);
        $this->ajaxReturn($rs);
    }

    /*
     * 获取门店商品
     * @param int shopId 店铺id
     * @param int goodsName 商品名称
     * @param int goodsSn 商品编号
     * @param int goodsCatId1 店铺一级分类
     * @param int goodsCatId2 店铺二级分类
     * @param int page 分页
     * @param int pageSize 分页条数
     * */
    public function getShopGoodsList(){
        $this->isAdminLogin();
        $params = [];
        $params['shopId'] = (int)I('shopId');
        $params['goodsName'] = I('goodsName');
        $params['goodsSn'] = I('goodsSn');
        $params['goodsCatId1'] = (int)I('goodsCatId1');
        $params['goodsCatId2'] = (int)I('goodsCatId2');
        $params['page'] = (int)I('page',1);
        $params['pageSize'] = (int)I('pageSize',15);
        if(empty($params['shopId'])){
            $rs = returnData(null,-1,'error','参数有误，shopId不能为空');
            $this->ajaxReturn($rs);
        }
        $mod = D('Made/Adminapi');
        $rs = $mod->getShopGoodsList($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 同步门店商品信息,使用该功能需要先同步管家婆商品数据
     * @param int shopId 选择同步店铺id
     * @param string toShopId 选择要同步的店铺id,多个用英文逗号分隔
     * @param int allGoods 类型(1:全部商品|2:部分商品),PS:当选则点击部分商品的时候出现选择商品的操作,点击弹出商品列表给用户选择商品
     * @param string goodsIds 多个商品用英文逗号分隔
     * * @param string updateGoods 是否更新商品信息(true:是|false:否),PS:是,除商品编码、商品所属店铺分类、商品价格、商品库存外的字段做二次更新
    否,除商品图片、商品相册外的字段不做二次更新
     * */
    public function syncShopGoods(){
        $this->isAdminLogin();
        $params = [];
        $params['shopId'] = (int)I('shopId');
        $params['toShopId'] = trim(I('toShopId'),',');
        $params['allGoods'] = (int)I('allGoods');
        $params['goodsIds'] = trim(I('goodsIds'),',');
        $params['updateGoods'] = I('updateGoods');
        if(empty($params['shopId'])){
            $rs = returnData(null,-1,'error','参数有误，请选择门店');
            $this->ajaxReturn($rs);
        }
        if(empty($params['toShopId'])){
            $rs = returnData(null,-1,'error','参数有误，请选择同步门店');
            $this->ajaxReturn($rs);
        }
        $mod = D('Made/Adminapi');
        $rs = $mod->syncShopGoods($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 自动重置门店流水号(定时任务,每天执行一次)
     */
    public function Adminapi_CronJobs_autoResetShopSerialNumber(){
        $m = D('Made/Adminapi');
        $m->autoResetShopSerialNumber();
        echo "done";
    }
}

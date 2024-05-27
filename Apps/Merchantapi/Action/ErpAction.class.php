<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\ErpModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * ERP相关
 */
class ErpAction extends BaseAction
{
    /**
     * 根据erp商品分类父id获取商城分类
     * @param int parentId 商城分类父id
     */
    public function getErpGoodsCatByParentId()
    {
        $this->MemberVeri();
        $m = new ErpModel();
        $parentId = (int)I('parentId', 0);
        $rs = $m->getErpGoodsCatByParentId($parentId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 已废弃
     * 获取总仓商品列表(用于采购和向总仓调拨商品)
     * @param int goodsCat1 商城一级分类
     * @param int goodsCat2 商城二级分类
     * @param int goodsCat3 商城三级分类
     * @param string goodsName 商品名称
     * @param string goodsSn 商品编码/条码
     * @param int page 分页,默认为1
     * @param int pageSize 分页条数,默认15条
     */
//    public function getErpGoodsList()
//    {
//        $this->MemberVeri();
//        $params = [];
//        $params['goodsCat1'] = (int)I('goodsCat1');
//        $params['goodsCat2'] = (int)I('goodsCat2');
//        $params['goodsCat3'] = (int)I('goodsCat3');
//        $params['goodsName'] = I('goodsName');
//        $params['goodsSn'] = I('goodsSn');
//        $params['page'] = (int)I('page',1);
//        $params['pageSize'] = (int)I('pageSize',15);
//        $m = new ErpModel();
//        $rs = $m->getErpGoodsList($params);
//        $this->ajaxReturn(returnData($rs));
//    }

    /**
     * 获取门仓商品列表(用于向门店调拨)
     * @param int shopId 门仓id
     * @param int goodsCat1 商城一级分类
     * @param int goodsCat2 商城二级分类
     * @param int goodsCat3 商城三级分类
     * @param string goodsName 商品名称
     * @param string goodsSn 商品编码/条码
     * @param int page 分页,默认为1
     * @param int pageSize 分页条数,默认15条
     */
    public function getShopGoodsList()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['shopId'] = (int)I('shopId');
        $params['goodsCat1'] = (int)I('goodsCat1');
        $params['goodsCat2'] = (int)I('goodsCat2');
        $params['goodsCat3'] = (int)I('goodsCat3');
        $params['goodsName'] = I('goodsName');
        $params['goodsSn'] = I('goodsSn');
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $m = new ErpModel();
        if (empty($params['shopId'])) {
            $apiRet = returnData(null, -1, 'error', '字段有误，shopId不能为空');
            $this->ajaxReturn($apiRet);
        }
        if ($shopInfo['shopId'] == $params['shopId']) {
            $apiRet = returnData(null, -1, 'error', '请选择其他门店');
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->getShopGoodsList($params);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取商品详情(PS:主义门店商品和总仓商品详情,字段是不太一样的)
     * @param int shopId 门仓id PS:传0或不传则为总仓,默认为总仓0
     * @param int goodsId 商品id
     */
    public function getGoodsDetail()
    {
        $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $shopId = (int)I('shopId');
        $m = new ErpModel();
        if ($goodsId == 0) {
            $apiRet = returnData(null, -1, 'error', '参数有误，goodsId:' . $goodsId);
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->getGoodsDetail($goodsId, $shopId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取门仓列表 PS:用于向某个门仓申请调拨单
     * @param string shopName 门仓名称
     * @param string shopSn 门仓编号
     * @param int page 分页,默认为1
     * @param int pageSize 分页条数,默认15条
     */
    public function getShopList()
    {
        $this->MemberVeri();
        $params = [];
        $params['shopName'] = I('shopName');
        $params['shopSn'] = I('shopSn');
        $params['page'] = (int)I('page', 1);
        $params['pageSize'] = (int)I('pageSize', 15);
        $m = new ErpModel();
        $rs = $m->getShopList($params);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 添加采购单
     * 文档链接地址:
     * @throws SuccessMessage
     */
    public function addPurchaseOrder()
    {
        $shopInfo = $this->MemberVeri();
        $params = array();
        $params['remark'] = I('remark');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        $params['purchaseGoodsWhere'] = json_decode(htmlspecialchars_decode(I('purchaseGoodsWhere')), true);//一键采购页的搜索条件,仅仅针对一键采购页的商品
        if (empty($params['purchaseGoodsWhere'])) {
            $params['purchaseGoodsWhere'] = array();
        }
        $model = new ErpModel();
        if (empty($params['detail'])) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误，detail不能为空'));
        }
        $rs = $model->addPurchaseOrder($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 更新采购单
     * @param int otpId 采购单id
     * @param string remark 采购单备注
     * @param array detail 采购商品明细
     * @throws SuccessMessage
     */
    public function updatePurchaseOrderDetail()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['otpId'] = (int)I('otpId');
        $params['remark'] = I('remark');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        $m = new ErpModel();
        if (empty($params['otpId'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，otpId不能为空');
            $this->ajaxReturn($apiRet);
        }
        if (empty($params['detail'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，detail不能为空');
            $this->ajaxReturn($apiRet);
        }
        $this->ajaxReturn(returnData(null, -1, 'error', '采购单创建完成不能修改'));//逻辑变动,采购单创建完成不给修改
        $rs = $m->updatePurchaseOrderDetail($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取采购单详情
     * @param int otpId 采购单id
     */
    public function getPurchaseOrderDetail()
    {
        $this->MemberVeri();
        $otpId = (int)I('otpId');
        $m = new ErpModel();
        if (empty($otpId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误，otpId不能为空');
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->getPurchaseOrderDetail($otpId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取采购单列表
     * @param string number 单号
     * @param string actionUserName 制单人
     * @param int status 审核状态(-1:平台已拒绝|0:平台待审核|1:平台已审核)
     * @param int receivingStatus 收货状态(0:待收货|1:已收货)
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     * @throws SuccessMessage
     */
    public function getPurchaseOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $requestParam = I();
        $params = [];
        $params['number'] = null;
        $params['actionUserName'] = null;
        $params['status'] = null;
        $params['receivingStatus'] = null;
        $params['startDate'] = null;
        $params['endDate'] = null;
        $params['page'] = 1;
        $params['pageSize'] = 15;
        $params['shopId'] = $shopInfo['shopId'];
        $params['export'] = 0;
        parm_filter($params, $requestParam);
        $m = new ErpModel();
        $rs = $m->getPurchaseOrderList($params);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 删除采购单
     * @param int otpId 采购单id
     * @throws SuccessMessage
     */
    public function delPurchaseOrder()
    {
        $this->MemberVeri();
        $otpId = (int)I('otpId');
        if (empty($otpId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误，otpId不能为空');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->delPurchaseOrder($otpId);
        $this->ajaxReturn($rs);
    }

    /**
     * 添加调拨单
     * @param int toShopId 门仓id(不传或传0为总仓,有值为被申请调拨的门店id)
     * @param string remark 调拨单备注
     * @param array detail 调拨单商品明细
     * @throws SuccessMessage
     */
    public function addAllocationOrder()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['toShopId'] = (int)I('toShopId');
        $params['remark'] = I('remark');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        $m = new ErpModel();
        if (empty($params['detail'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，detail不能为空');
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->addAllocationOrder($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 更新调拨单
     * @param int allId 调拨单id
     * @param int toShopId 门仓id(不传或传0为总仓,有值为被申请调拨的门店id)
     * @param string remark 调拨单备注
     * @param array detail 调拨单商品明细
     * @throws SuccessMessage
     */
    public function updateAllocationOrderDetail()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['allId'] = (int)I('allId');
        $params['toShopId'] = (int)I('toShopId');
        $params['remark'] = I('remark');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        $m = new ErpModel();
        if (empty($params['allId'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，allId不能为空');
            $this->ajaxReturn($apiRet);
        }
        if (empty($params['detail'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，detail不能为空');
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->updateAllocationOrderDetail($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取调拨单详情
     * @param int allId 调拨单id
     */
    public function getAllocationOrderDetail()
    {
        $shopInfo = $this->MemberVeri();
        $allId = (int)I('allId');
        if (empty($allId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误，allId不能为空');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->getAllocationOrderDetail($allId, $shopInfo);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取申请调拨单列表
     * @param string number 单号
     * @param int examineStatus 状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成|200:全部)
     * @param int warehouse 入库状态(0:未入库|1:部分入库|2:已入库|200:全部)
     * @param string startTime 开始时间
     * @param string endTime 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function getAllocationOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $requestParam = I();
        $params['number'] = null;
        $params['warehouse'] = null;
        $params['examineStatus'] = null;
        $params['startDate'] = null;
        $params['endDate'] = null;
        $params['page'] = 1;
        $params['pageSize'] = 15;
        $m = new ErpModel();
        parm_filter($params, $requestParam, false);
        $rs = $m->getAllocationOrderList($params, $shopInfo);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取被申请调拨单列表(其他门店向当前门店发起的调拨请求)
     * @param string number 单号
     * @param int examineStatus 状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成|'':全部)
     * @param int warehouse 入库状态(0:未入库|1:部分入库|2:已入库|'':全部)
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function getToAllocationOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $requestParam = I();
        $params['number'] = null;
        $params['warehouse'] = null;
        $params['examineStatus'] = null;
        $params['startDate'] = null;
        $params['endDate'] = null;
        $params['page'] = 1;
        $params['pageSize'] = 15;
        $m = new ErpModel();
        parm_filter($params, $requestParam, false);
        $rs = $m->getToAllocationOrderList($params, $shopInfo);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 删除调拨单
     * @param int allId 调拨单id
     * @throws SuccessMessage
     */
    public function delAllocationOrder()
    {
        $shopInfo = $this->MemberVeri();
        $allId = (int)I('allId');
        if (empty($allId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误，allId不能为空');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->delAllocationOrder($allId, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 更改调拨单的状态
     * @param string allIds 调拨单id,多个用英文逗号分隔
     * @param int examineStatus 状态(-1:已拒绝|0:待审核|1:待调拨|2:已调拨|3:已完成)
     * @param string refusalRemark 拒绝原因
     */
    public function examineAllocationOrder()
    {
        $shopInfo = $this->MemberVeri();
        $requestParam = I();
        $params['allId'] = null;
        $params['examineStatus'] = null;
        $params['refusalRemark'] = '';
        parm_filter($params, $requestParam, false);
        if (empty($params['allId']) || is_null($params['examineStatus'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $params['allId'] = explode(',', trim($params['allId'], ','));
        $rs = $m->examineAllocationOrder($params['allId'], $params['examineStatus'], $params['refusalRemark'], $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 入库任务->获取采购单列表
     * @param string token
     */
    public function getWarehousingPurchaseOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['shopId'] = $shopInfo['shopId'];
        $params['examineStatus'] = 1;
        $m = new ErpModel();
        $data = $m->getWarehousingPurchaseOrderList($params, $shopInfo);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 入库任务->获取调拨单列表
     * @param string token
     */
    public function getWarehousingAllocationOrderList()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['shopId'] = $shopInfo['shopId'];
        $params['examineStatus'] = 1;
        $m = new ErpModel();
        $data = $m->getWarehousingAllocationOrderList($params, $shopInfo);
        $this->ajaxReturn(returnData($data));
    }

    //产品经理调整后 start

    /**
     * 获取总仓商品分类列表
     * @param varchar token
     * @param int parentId 分类父id
     */
    public function getErpGoodsCats()
    {
        $this->MemberVeri();
        $m = new ErpModel();
        $parentId = (int)I('parentId', 0);
        $dataType = (int)I('dataType', 1);//返回数据类型[1:根据父级id返回数据|2:返回所有数据]
        $rs = $m->getErpGoodsCats($parentId, $dataType);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取总仓商品列表
     * @param int goodsCat1 一级分类id
     * @param int goodsCat2 二级分类id
     * @param int goodsCat3 三级分类id
     * @param string keywords 关键字(商品名称/商品编码)
     * @param varchar goodsName 商品名称 废弃
     * @param varchar goodsSn 商品编码/条码 废弃
     * @param int page 分页,默认为1
     * @param int pageSize 分页条数,默认15条
     */
    public function getErpGoodsList()
    {
        $this->MemberVeri();
        $requestParams = I();
        $params = [];
        $params['goodsCat1'] = null;
        $params['goodsCat2'] = null;
        $params['goodsCat3'] = null;
        $params['goodsName'] = null;
        $params['goodsSn'] = null;
        $params['keywords'] = null;
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params, $requestParams);
        $m = new ErpModel();
        $rs = $m->getErpGoodsList($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 根据总仓商品id获取商品规格列表
     * @param int goodsId 商品id
     */
    public function getErpGoodsSkuByGoodsId()
    {
        $this->MemberVeri();
        $goodsId = I('goodsId');
        if (empty($goodsId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->getErpGoodsSkuByGoodsId($goodsId);
        $this->ajaxReturn($rs);
    }

    /**
     * 根据调拨条件获取门仓商品列表
     * @param varchar token
     * @param int goodsId 总仓商品id
     * @param int skuId 总仓商品规格id
     * @param float num 调拨数量
     */
    public function getAllocationShopGoods()
    {
        $shopInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $skuId = (int)I('skuId');
        $num = (float)I('num', 0);
        if (empty($goodsId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if (empty($num)) {
            $apiRet = returnData(null, -1, 'error', '请输入调拨数量');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->getAllocationShopGoods($shopInfo, $goodsId, $skuId, $num);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 创建调拨单
     * @param varchar token
     * @param int goodsId 门仓商品id
     * @param int skuId 规格id
     * @param float num 调拨数量
     */
    public function addAllocationBill()
    {
        $login_info = $this->MemberVeri();
        $goods_id = (int)I('goodsId');
        $sku_id = (int)I('skuId');
        $num = (float)I('num');
        if (empty($goods_id)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if ($num <= 0) {
            $apiRet = returnData(null, -1, 'error', '调拨数量必须大于0');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->addAllocationBill($goods_id, $sku_id, $num, $login_info);
        $this->ajaxReturn($rs);
    }

    /**
     * 调拨单->新建入库单 PS:针对手动入库的场景,单据生成则入库完成
     * @param varchar token
     * @param int goodsId 门仓商品id
     * @param int skuId 规格id
     * @param float num 调拨数量
     * @param int warehouseUserId 入库人员id
     */
    public function addAllocationBillComplete()
    {
        $shopInfo = $this->MemberVeri();
        $goodsId = (int)I('goodsId');
        $skuId = (int)I('skuId');
        $num = (float)I('num');
        $warehouseUserId = (int)I('warehouseUserId');
        if (empty($goodsId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if (empty($num)) {
            $apiRet = returnData(null, -1, 'error', '调拨数量必须大于0');
            $this->ajaxReturn($apiRet);
        }
        if (empty($warehouseUserId)) {
            $apiRet = returnData(null, -1, 'error', '请分配入库人员');
            $this->ajaxReturn($apiRet);
        }
        $m = new ErpModel();
        $rs = $m->addAllocationBillComplete($goodsId, $skuId, $num, $warehouseUserId, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 我的调入/我的调出
     * @param varchar token
     * @param int dataType 数据类型【1：我的调入|2：我的调出】
     * @param varchar number 单号
     * @param int status 调拨状态【-1:平台已拒绝|0:平台待审核|1:调出方待确认|2:等待调入方收货|3:调出方已交货】
     * @param dateTime startDate 开始时间
     * @param dateTime endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function allocationBillListIO()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $dataType = (int)I('dataType');
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $requestParams = I();
        $search = [];
        $search['startDate'] = null;
        $search['endDate'] = null;
        $search['status'] = null;
        $search['number'] = null;
        parm_filter($search, $requestParams);
        $m = new ErpModel();;
        $rs = $m->allocationBillListIO($shopId, $dataType, $page, $pageSize, $search);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 已完成的调拨
     * @param varchar token
     * @param varchar number 单号
     * @param dateTime startDate 开始时间
     * @param dateTime endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function allocationBillListComplete()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $requestParams = I();
        $search = [];
        $search['startDate'] = null;
        $search['endDate'] = null;
        $search['number'] = null;
        parm_filter($search, $requestParams);
        $m = new ErpModel();
        $rs = $m->allocationBillListComplete($shopId, $page, $pageSize, $search);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 调拨入库单
     * @param varchar token
     * @param varchar number 单号
     * @param int warehouseStatus 入库状态(0:待入库|1:部分入库|2:已入库)
     * @param dateTime startDate 开始时间
     * @param dateTime endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function allocationBillListWarehouse()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $requestParams = I();
        $search = [];
        $search['startDate'] = null;
        $search['endDate'] = null;
        $search['number'] = null;
        $search['warehouseStatus'] = null;
        parm_filter($search, $requestParams);
        $m = new ErpModel();
        $rs = $m->allocationBillListWarehouse($shopId, $page, $pageSize, $search);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取调拨单详情
     * @param varchar token
     * @param int allId 调拨单id
     */
    public function getAllocationBillDetail()
    {
        $this->MemberVeri()['shopId'];
        $allId = (int)I('allId');
        $m = new ErpModel();
        $rs = $m->getAllocationBillDetail($allId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 更改调拨单的状态
     * @param varchar token
     * @param int allId 调拨单id
     * @param int dataType 场景【1：场景1->调出方确认调拨|2：场景2->调入方再次发起调拨|3：场景3->调入方收货】
     * @param varchar billPic 单据照片【场景3】
     * @param varchar consigneeName 接货人姓名【场景3】
     */
    public function updateAllocationBillStatus()
    {
        $shopInfo = $this->MemberVeri();
        $allId = (int)I('allId');
        $dataType = (int)I('dataType');
        $billPic = I('billPic');
        $consigneeName = I('consigneeName');
        if (empty($allId) || empty($dataType)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if ($dataType == 3) {
            if (empty($billPic) || empty($consigneeName)) {
                $apiRet = returnData(null, -1, 'error', '请补全单据照片和接货人信息');
                $this->ajaxReturn($apiRet);
            }
        }
        //$m = new ErpModel();
        $m = new ErpModel();
        $rs = $m->updateAllocationBillStatus($shopInfo, $allId, $dataType, $billPic, $consigneeName);
        $this->ajaxReturn($rs);
    }

    /**
     * 统计调拨单各个状态的总数
     * @param varchar token
     */
    public function allocationBillCount()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $m = new ErpModel();
        $rs = $m->allocationBillCount($shopId);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 更改调拨单入库单的入库数量
     * @param varchar token
     * @param int allId 调拨单id
     * @param float warehouseCompleteNum 入库数量
     */
    public function updateWarehouseCompleteNum()
    {
        $shopInfo = $this->MemberVeri();
        $allId = (int)I('allId');
        $warehouseCompleteNum = (float)I('warehouseCompleteNum');
        if (empty($allId) || empty($warehouseCompleteNum)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        //$m = new ErpModel();
        $m = new ErpModel();
        $rs = $m->updateWarehouseCompleteNum($shopInfo, $allId, $warehouseCompleteNum);
        $this->ajaxReturn($rs);
    }

    /**
     * 采购入库单列表
     * @param string number 单号
     * @param string actionUserName 制单人
     * @param int warehouseStatus 入库状态(0:待入库|1:部分入库|2:入库完成)
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param int export 导出(0:否|1:是)
     * @param int page 页码
     * @param int pageSize 分页条数
     * @throws SuccessMessage
     */
    public function getWarehouseList()
    {
        $shopInfo = $this->MemberVeri();
        $requestParam = I();
        $params = [];
        $params['number'] = null;
        $params['actionUserName'] = null;
        $params['warehouseStatus'] = null;
        $params['startDate'] = null;
        $params['endDate'] = null;
        $params['page'] = 1;
        $params['pageSize'] = 15;
        $params['shopId'] = $shopInfo['shopId'];
        $params['export'] = 0;
        parm_filter($params, $requestParam);
//        $m = new ErpModel();
        $m = new ErpModel();
        $rs = $m->getWarehouseList($params);
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 更改采购单的状态
     * @param varchar token
     * @param int otpId 采购单id
     * @param int dataType 场景【1：场景1->采购方确认收货】
     * @param varchar billPic 单据照片【场景1】
     * @param varchar consigneeName 接货人姓名【场景1】
     */
    public function updatePurchaseStatus()
    {
        $shopInfo = $this->MemberVeri();
        $otpId = (int)I('otpId');
        $rpId = (int)I('rpId');//分单id PS:后加兼容分单
        $dataType = (int)I('dataType', 1);
        $billPic = I('billPic');
        $consigneeName = I('consigneeName');
        if (empty($otpId) && empty($rpId)) {
            $apiRet = returnData(null, -1, 'error', '参数有误');
            $this->ajaxReturn($apiRet);
        }
        if ($dataType == 1) {
            if (empty($billPic) || empty($consigneeName)) {
                $apiRet = returnData(null, -1, 'error', '请补全单据照片和接货人信息');
                $this->ajaxReturn($apiRet);
            }
        }
        $m = new ErpModel();
        $rs = $m->updatePurchaseStatus($shopInfo, $otpId, $rpId, $dataType, $billPic, $consigneeName);
        $this->ajaxReturn($rs);
    }

    /**
     * 修改采购入库单的入库数量
     * @param varchar token
     * @param int otpId 采购单id
     * @param varchar detail 入库商品明细
     */
    public function updatePurchaseWarehouseNum()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['otpId'] = (int)I('otpId');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        //$m = new ErpModel();
        $m = new ErpModel();
        if (empty($params['otpId'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，otpId不能为空');
            $this->ajaxReturn($apiRet);
        }
        if (empty($params['detail'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，detail不能为空');
            $this->ajaxReturn($apiRet);
        }
        $rs = $m->updatePurchaseWarehouseNum($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 手动创建采购入库单，注意该单据中的商品明细调用的是当前门店的商品信息
     * @param string remark 采购单备注
     * @param array detail 采购商品明细
     * @throws SuccessMessage
     */
    public function addPurchaseOrderWarehouse()
    {
        $shopInfo = $this->MemberVeri();
        $params = [];
        $params['remark'] = I('remark');
        $params['detail'] = json_decode(htmlspecialchars_decode(I('detail')), true);
        $params['shopId'] = $shopInfo['shopId'];
        $m = new ErpModel();
        if (empty($params['detail'])) {
            $apiRet = returnData(null, -1, 'error', '参数有误，detail不能为空');
            $this->ajaxReturn($apiRet);
        }
        $params['billType'] = 2;//单据类型(1:采购单转为入库单|2:手动创建入库单)
        $rs = $m->addPurchaseOrderWarehouse($params, $shopInfo);
        $this->ajaxReturn($rs);
    }

    //产品经理调整后 end

    /**
     * 导入erp商品 PS:总仓商品和分仓商品关联的依据是商品编码,如果门店商品编码已存在则更新商品数据
     * @param string goodsIds 总仓商品id,多个用英文逗号分隔,-1为导入全部
     * */
    public function importErpGoods()
    {
        $shopInfo = $this->MemberVeri();
        $shopId = $shopInfo['shopId'];
        $goodsIds = I('goodsIds', '');
        //$m = new ErpModel();
        $m = new ErpModel();
        if (empty($goodsIds)) {
            $apiRet = returnData(null, -1, 'error', '参数有误，商品id不能为空');
            $this->ajaxReturn($apiRet);
        }
        $data = $m->importErpGoods($shopId, $goodsIds);
        $this->ajaxReturn($data);
    }

    /**
     * 采购入库单 进行入库操作
     */
    public function updateEntryWarehouse()
    {
        $shopInfo = $this->MemberVeri();
        $otpId = I('otpId');
        $goodsArr = I('goodsArr');
        $goodsArr = json_decode(htmlspecialchars_decode($goodsArr), true);
        if (empty($otpId)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '参数有误，otpId不能为空'));
        }
        if (empty($goodsArr)) {
            $this->ajaxReturn(returnData(null, -1, 'error', '参数有误，goodsArr不能为空'));
        }
        $data = (new \App\Modules\Erp\purchaseWarehousingServiceModule())->purchaseWarehousing($otpId, $goodsArr, $shopInfo);
        $this->ajaxReturn($data);
    }
}

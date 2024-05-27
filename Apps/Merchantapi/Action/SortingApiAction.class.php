<?php

namespace Merchantapi\Action;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Shops\ShopsServiceModule;
use Merchantapi\Model\SortingApiModel;
use Symfony\Component\DependencyInjection\Tests\Compiler\I;
use function App\Util\responseSuccess;
use function App\Util\responseError;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 分拣APP
 */
class SortingApiAction extends BaseAction
{
    /*
     * 验证token,正确则返回分拣员数据
     * */
    public function sortingMemberVeri()
    {
        $memberToken = I("memberToken");
        if (empty($memberToken)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token
        if (empty($sessionData)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        return $sessionData;
    }

    /*
     * 登陆
     * @param string account
     * @param string password
     * */
    public function sortingLogin()
    {
        $account = I('account');
        $password = I('password');
        if (empty($account) || empty($password)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $request['account'] = $account;
        $request['password'] = md5($password);
        $m = D("Merchantapi/SortingApi");
        $res = $m->sortingLogin($request);
        $this->ajaxReturn($res);
    }

    /*
     * 获取分拣员信息
     * @param string memberToken
     * */
    public function getSortingInfo()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingInfo($sortId);
        $this->ajaxReturn($res);
    }

    /*
     * 分拣员信息更新
     * @param string memberToken
     * @param string userName PS:分拣员信姓名(非必填)
     * @param string mobile PS:手机号(非必填)
     * @param int state PS:状态(1=>在线,-1=>不在线 非必填)
     * */
    public function editSortingInfo()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $request = I();
        if (empty($request)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        !empty($request['userName']) ? $data['userName'] = $request['userName'] : false;
        !empty($request['mobile']) ? $data['mobile'] = $request['mobile'] : false;
        isset($request['state']) ? $data['state'] = $request['state'] : false;
        !empty($request['password']) ? $data['password'] = md5($request['password']) : false;
        $m = D("Merchantapi/SortingApi");
        $res = $m->editSortingInfo($sortId, $data);
        $this->ajaxReturn($res);
    }

    /*
     * 获取分拣任务
     * @param string memberToken
     * @param int page
     * @param int pageDataNum PS :条数,默认10条
     * @param int status PS:分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成,10=>全部)
     * */
    public function getSortingList()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $page = I('page', 1);
        $status = I('status', 10);
        $pageDataNum = I('pageDataNum', 10);
        $res = $m->getSortingList($sortId, $status, $page, $pageDataNum);
        $this->ajaxReturn($res);
    }


    /*
     * 获取分拣任务下所有的商品数据
     * @param string memberToken
     * @param int page
     * @param int pageDataNum PS :条数,默认10条
     * @param int isConformity PS :是否聚合,默认0：不聚合|1：聚合
     * @param int status PS:分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成,10=>全部)
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/kgvtg1
     * */
    public function getSortingGoodsList()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $params = [];
        $params['sortId'] = $sortId;
        $params['page'] = I('page', 1);//页码
        $params['pageSize'] = I('pageSize', 10);//条数,默认10条
        $params['status'] = I('status', 10);
        $params['isConformity'] = (int)I('isConformity', 0);//是否聚合,默认0：不聚合|1：聚合
        $params['basketId'] = (int)I('basketId', 0);//框位ID
        $params['shopCatId1'] = (int)I('shopCatId1', 0);//店铺商品分类第一级
        $params['shopCatId2'] = (int)I('shopCatId2', 0);//店铺商品分类第二级
        $params['orderId'] = (int)I('orderId', 0);//订单ID
        $res = $m->getSortingGoodsList($params);
        $this->ajaxReturn($res);
    }

    /**
     * 一键开始分拣
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/fl71t4
     */
    public function updateGoodsStatus()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $params = [];
        $params['sortId'] = $sortId;
        $params['basketId'] = (int)I('basketId', 0);//框位ID
        $params['shopCatId1'] = (int)I('shopCatId1', 0);//店铺商品分类第一级
        $params['shopCatId2'] = (int)I('shopCatId2', 0);//店铺商品分类第二级
        $params['orderId'] = (int)I('orderId', 0);//订单ID
        $res = $m->updateGoodsStatus($params);
        $this->ajaxReturn($res);
    }

    /**
     * 待分拣--扫码开始分拣
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/addgxg
     */
    public function editSortOrderStatus()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $orderNo = I('orderNo');
        if (empty($orderNo)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请扫码分拣订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D("Merchantapi/SortingApi");
        $params = [];
        $params['sortId'] = $sortId;
        $params['orderNo'] = $orderNo;
        $res = $m->editSortOrderStatus($params);
        $this->ajaxReturn($res);
    }

    /**
     * 获取框位|店铺分类列表
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/fsypuk
     */
    public function getShopCatList()
    {
        $sortInfo = $this->sortingMemberVeri();
        $m = D("Merchantapi/SortingApi");
        $status = I('status');
        //分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成(待入框),3=>已入框(已完成))
        if (!in_array($status, [0, 1, 2])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择正确的分拣状态';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['shopId'] = $sortInfo['shopid'];
        $params['status'] = $status;
        $params['sortId'] = $sortInfo['id'];
        $res = $m->getShopCatList($params);
        $this->ajaxReturn($res);
    }

    /**
     * 分拣中---获取商品详情
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/eva7c9
     */
    public function getSortGoodsInfo()
    {
        $this->sortingMemberVeri();
        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = I('sortingGoodsId');
        if (empty($sortingGoodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择分拣的商品';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['sortingGoodsId'] = $sortingGoodsId;
        $res = $m->getSortGoodsInfo($params);
        $this->ajaxReturn($res);
    }

    /**
     * 分拣中---获取聚合商品详情
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/bgx5z0
     */
    public function getConformitySortGoodsInfo()
    {
        $this->sortingMemberVeri();
        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = I('sortingGoodsId');
        if (empty($sortingGoodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择分拣的商品';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['sortingGoodsId'] = $sortingGoodsId;
        $res = $m->getConformitySortGoodsInfo($params);
        $this->ajaxReturn($res);
    }

    /**
     * 分拣中---框位详情--【弃用】
     */
    public function getBasketSortInfo()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $basketId = (int)I('basketId', 0);
        if (empty($basketId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择框位';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getBasketSortInfo($sortId, $basketId);
        $this->ajaxReturn($res);
    }

    /*
     * 更改分拣任务的状态---【弃用】
     * @param string memberToken
     * @param int sortingId PS:分拣任务的id
     * @param int status PS:分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成)
     * PS:分拣任务状态更改为分拣中时,其任务下的商品状态会更改为分拣中
     * */
    public function editSoritingStatus()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $status = (int)I('status');
        $sortindId = (int)I('sortingId');
        if (empty($status) || empty($sortindId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->editSoritingStatus($sortId, $sortindId, $status);
        $this->ajaxReturn($res);
    }

    /**
     * 更改分拣商品的状态---单商品
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/zo1igm
     */
    public function editSortGoodsStatus()
    {
        $this->sortingMemberVeri();
        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = (int)I('sortingGoodsId');
        if (empty($sortingGoodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择商品进行分拣';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->editSortGoodsStatus($sortingGoodsId);
        $this->ajaxReturn($res);
    }

    /**
     * 更改分拣商品的状态---聚合商品
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/yepsds
     */
    public function editConformityGoodsStatus()
    {
        $this->sortingMemberVeri();
        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = I('sortingGoodsId');
        if (empty($sortingGoodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择商品进行分拣';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->editConformityGoodsStatus($sortingGoodsId);
        $this->ajaxReturn($res);
    }

    /*
     * 获取分拣任务的详情-----【弃用】
     * @param string memberToken
     * @param int sortingWorkId PS:分拣任务的id
     * */
    public function getSortingWorkInfo()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        //$sortId = 15;
        $m = D("Merchantapi/SortingApi");
        $sortingWorkId = (int)I('sortingWorkId');
        if (empty($sortingWorkId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getSortingWorkInfo($sortId, $sortingWorkId);
        $this->ajaxReturn($res);
    }

    /*
     * 旧接口
     * 更改分拣商品已完成分拣的数量(如果数量达到该分拣需求的数量,系统会自动更改分拣商品的状态为分拣完成)
     * @param string memberToken
     * @param int sortingWorkId PS:分拣任务的id
     * @param int goodsId PS:商品id
     * @param int barcode PS:条码 后加
     * @param int goodsNum PS:数量默认为1
     * */
//    public function editSortingGoodsNum()
//    {
//        $sortInfo = $this->sortingMemberVeri();
//        $sortId = $sortInfo['id'];
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '字段有误';
//        $apiRet['apiState'] = 'error';
//
//        $m = D("Merchantapi/SortingApi");
//        $sortingWorkId = (int)I('sortingWorkId');
//        $goodsId = (int)I('goodsId');
//        $goodsNum = I('goodsNum');//用于存在分拣数量|分拣商品重量
//        $barcode = I('barcode');
//        $basketSn = I('basketSn');
//        $skuId = (int)I('skuId', 0);
//        $isNativeGoods = (int)I('isNativeGoods', 0);//用于判断是否是本地预打包商品【0:不是|1:是】
//        //为啥要分开提示错误，前端需要
//        if (empty($sortingWorkId)) {
//            $apiRet['apiInfo'] = '请选择分拣任务';
//            $this->ajaxReturn($apiRet);
//        }
//
//        if (empty($goodsId) || empty($barcode)) {
//            $apiRet['apiInfo'] = '请扫码分拣商品条码';
//            $this->ajaxReturn($apiRet);
//        }
//
//        if (empty($basketSn)) {
//            $apiRet['apiInfo'] = '请扫描框位条码';
//            $this->ajaxReturn($apiRet);
//        }
//        if ($goodsNum <= 0) {//后加
//            $apiRet['apiInfo'] = '请输入正确的分拣数量';
//            $this->ajaxReturn($apiRet);
//        }
//        $request['sortingWorkId'] = $sortingWorkId;
//        $request['goodsId'] = $goodsId;
//        $request['goodsNum'] = $goodsNum;
//        $request['barcode'] = $barcode;
//        $request['basketSn'] = $basketSn;
//        $request['shopId'] = $sortInfo['shopid'];
//        $request['skuId'] = $skuId;
//        $request['isNativeGoods'] = $isNativeGoods;
//        $res = $m->editSortingGoodsNum($sortId, $request);
//        $this->ajaxReturn($res);
//    }

    /**
     * 新接口
     * 扫码直接分拣商品
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/bekp27
     */
    public function editSortingGoodsNum()
    {
        $sortInfo = $this->sortingMemberVeri();
        $sortId = $sortInfo['id'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';

        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = (int)I('sortingGoodsId');
        $barcode = I('barcode');//扫码获取的编码
        //分开提示错误，前端需要
        if (empty($sortingGoodsId)) {
            $apiRet['apiInfo'] = '请选择分拣任务商品';
            $this->ajaxReturn($apiRet);
        }

        if (empty($barcode)) {
            $apiRet['apiInfo'] = '请扫码分拣商品条码';
            $this->ajaxReturn($apiRet);
        }

        $request = [];
        $request['sortingGoodsId'] = $sortingGoodsId;
        $request['barcode'] = $barcode;
        $request['shopId'] = $sortInfo['shopid'];
        $res = $m->editSortingGoods($sortId, $request);
        $this->ajaxReturn($res);
    }

    /**
     * 确定完成分拣时-----获取未分拣商品数量
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/lr0ca2
     */
    public function getSortGoodsNum()
    {
        $sortInfo = $this->sortingMemberVeri();
        $sortId = $sortInfo['id'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';

        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = (int)I('sortingGoodsId');
        $serverTime = (int)I('server_time');//时间戳
        $confirm = (int)I('isConfirm');//是否确认分拣 0:否|1:是
        if (empty($sortingGoodsId)) {
            $apiRet['apiInfo'] = '请选择分拣任务商品';
            $this->ajaxReturn($apiRet);
        }

        $request = [];
        $request['sortingGoodsId'] = $sortingGoodsId;
        $request['shopId'] = $sortInfo['shopid'];
        $request['server_time'] = $serverTime;
        $request['isConfirm'] = $confirm;
        $res = $m->getSortGoodsNum($sortId, $request);
        $this->ajaxReturn($res);
    }

    /**
     * 入框时----商品扫码验证
     */
    public function getBasketGoodsVerify()
    {
        $sortInfo = $this->sortingMemberVeri();
        $sortId = $sortInfo['id'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';

        $m = D("Merchantapi/SortingApi");
        $sortingGoodsId = (int)I('sortingGoodsId');
        $barcode = I('barcode');//扫码获取的商品编码
        //分开提示错误，前端需要
        if (empty($sortingGoodsId)) {
            $apiRet['apiInfo'] = '请选择分拣任务商品';
            $this->ajaxReturn($apiRet);
        }

        if (empty($barcode)) {
            $apiRet['apiInfo'] = '请扫描分拣商品条码';
            $this->ajaxReturn($apiRet);
        }

        $request = [];
        $request['sortingGoodsId'] = $sortingGoodsId;
        $request['barcode'] = $barcode;
        $request['shopId'] = $sortInfo['shopid'];
        $res = $m->getBasketGoodsVerify($sortId, $request);
        $this->ajaxReturn($res);
    }

    /**
     * 入框时----商品扫码入框
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/wqkibf
     */
    public function editSortGoodsBasket()
    {
        $sortInfo = $this->sortingMemberVeri();
        $sortId = $sortInfo['id'];
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';

//        $m = D("Merchantapi/SortingApi");
        $m = new SortingApiModel();
        $sortingGoodsId = (int)I('sortingGoodsId');
        $barcode = I('barcode');//扫码获取的商品编码
        $basketSn = I('basketSn');//扫码获取的框位编码
        //分开提示错误，前端需要
        if (empty($sortingGoodsId)) {
            $apiRet['apiInfo'] = '请选择分拣任务商品';
            $this->ajaxReturn($apiRet);
        }

        if (empty($barcode)) {
            $apiRet['apiInfo'] = '请扫描分拣商品条码';
            $this->ajaxReturn($apiRet);
        }

        $shop_id = $sortInfo['shopid'];
        $service_module = new ShopsServiceModule();
        $shop_result = $service_module->getShopConfig($shop_id);
        $shop_data = $shop_result['data'];
        if (empty($basketSn)) {
            $msg = '请扫描框位条码';
            if ($shop_data['open_suspension_chain'] == 1) {
                $msg = '请扫描钩子条码';
            }
            $apiRet['apiInfo'] = $msg;
            $this->ajaxReturn($apiRet);
        }
        $request = [];
        $request['sortingGoodsId'] = $sortingGoodsId;
        $request['barcode'] = $barcode;
        $request['basketSn'] = $basketSn;
        $request['shopId'] = $sortInfo['shopid'];
        $res = $m->editSortGoodsBasket($sortId, $request);
        $this->ajaxReturn($res);
    }

    /*
     * 获取商品详情
     * @param string memberToken
     * @param string barcode PS:条码
     * */
    public function getGoodsInfo()
    {
        $sortInfo = $this->sortingMemberVeri();
        $barcode = I('barcode');
        if (empty($barcode)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请扫码商品条码';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $request['barcode'] = $barcode;
        $request['shopId'] = $sortInfo['shopid'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getGoodsInfo($request);
        $this->ajaxReturn($res);
    }

    /**
     * 根据商品名称获取商品详情
     * @param string memberToken
     * @param string $keyword PS:商品名称|商品编号
     * */
    public function getGoodsInfoByName()
    {
        $sortInfo = $this->sortingMemberVeri();
        $keyword = I('keyword');
        if (empty($keyword)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误，请输入商品名称或商品编号';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $request['keyword'] = $keyword;
        $request['sortId'] = $sortInfo['id'];
        $request['shopid'] = $sortInfo['shopid'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getGoodsInfoByName($request);
        $this->ajaxReturn($res);
    }


    /*
     * 申请结算
     * @param int sortingWorkId PS:任务id
     * */
    public function subSettlement()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $sortingWorkId = (int)I('sortingWorkId');
        if (empty($sortingWorkId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $request['sortingWorkId'] = $sortingWorkId;
        $request['sortId'] = $sortId;
        $m = D("Merchantapi/SortingApi");
        $res = $m->subSettlement($request);
        $this->ajaxReturn($res);
    }

    /*
     * 获取结算列表
     * @param string memberToken
     * @param int page
     * @param int pageDataNum PS :条数,默认10条
     * @param int status PS:状态(1=>待结算,2=>已结算,10=>全部)
     * */
    public function getSettlementList()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $page = I('page', 1);
        $settlement = I('status');
        $pageDataNum = I('pageDataNum', 10);
        if (empty($settlement)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $res = $m->getSettlementList($sortId, $settlement, $page, $pageDataNum);
        $this->ajaxReturn($res);
    }


    /*
     * 一键结算 PS(自动将分拣完成状态的任务结算)
     * @param string memberToken
     * */
    public function autoSortingSettlement()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->autoSortingSettlement($sortId);
        $this->ajaxReturn($res);
    }

    /*
     * 获取分拣任务的操作日志
     * @param string memberToken
     * @param int sortingId PS:分拣任务id
     * */
    public function getSortingActLog()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $sortingId = (int)I('sortingId');
        if (empty($sortingId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingActLog($sortingId);
        $this->ajaxReturn($res);
    }

    /*
     * 获取分拣任务各个状态下的数量
     * @param string memberToken
     * */
    public function getSortingCount()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingCount($sortId);
        $this->ajaxReturn($res);
    }

    /**
     * 获取打包订单列表
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/oqvdwx
     */
    public function getSortingPackList()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $param = [];
        $param['page'] = I('page', 1);
        $param['pageSize'] = I('pageSize', 15);
        $res = $m->getSortingPackList($param, $sortId);
        $this->ajaxReturn($res);
    }

    /**
     * 变更打包订单
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/tvi2nx
     */
    public function updateSortingPack()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $orderId = I('orderId', 0);
        $orderNo = I('orderNo', 0);
        if (empty($orderId) && empty($orderNo)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['orderId'] = $orderId;
        $params['orderNo'] = $orderNo;
        $m = D("Merchantapi/SortingApi");
        $res = $m->updateSortingPack($sortId, $params);
        $this->ajaxReturn($res);
    }

    /**
     * 获取打包订单详情
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/fe1ynz
     */
    public function getSortingPackInfo()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $orderNo = I('orderNo', 0);
        if (empty($sortId) || empty($orderNo)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingPackInfo($sortId, $orderNo);
        $this->ajaxReturn($res);
    }

    /**
     * 商品打包---已弃用
     */
    public function addSortingPackGoods()
    {
        $this->sortingMemberVeri()['id'];
        $orderNo = I('orderNo');//订单条码
        $barcode = I('barcode');//商品条码
        $skuId = (int)I('skuId', 0);//商品skuId
        $goodsId = (int)I('goodsId', 0);//商品id
        if (empty($orderNo)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择打包订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        if (empty($barcode) || empty($goodsId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择打包商品';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['barcode'] = $barcode;
        $params['orderNo'] = $orderNo;
        $params['skuId'] = $skuId;
        $params['goodsId'] = $goodsId;
        $m = D("Merchantapi/SortingApi");
        $res = $m->addSortingPackGoods($params);
        $this->ajaxReturn($res);
    }

    /**
     * 商品扫码打包
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/xm6kn5
     */
    public function editSortingPackGoods()
    {
        $sortInfo = $this->sortingMemberVeri();
        $orderNo = I('orderNo');//订单条码
        $barcode = I('barcode');//商品条码
        if (empty($orderNo)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择打包订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        if (empty($barcode)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请扫描打包商品';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['barcode'] = $barcode;
        $params['orderNo'] = $orderNo;
        $params['shopId'] = $sortInfo['shopid'];
        $params['sortUserId'] = $sortInfo['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->editSortingPackGoods($params);
        $this->ajaxReturn($res);
    }

    /**
     * 确认完成打包-----获取未打包商品数量
     * 手册https://www.yuque.com/anthony-6br1r/oq7p0p/yqobsn
     */
    public function getPackGoodsNum()
    {
        $sortInfo = $this->sortingMemberVeri();
        $orderId = (int)I('orderId');
        if (empty($orderId)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '请选择打包订单';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $params = [];
        $params['orderId'] = $orderId;
        $params['shopId'] = $sortInfo['shopid'];
        $params['sortUserId'] = $sortInfo['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getPackGoodsNum($params);
        $this->ajaxReturn($res);
    }

    /**
     * 获取门店配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ge36ib
     * */
    public function getShopConfig()
    {
        $shop_info = $this->sortingMemberVeri();
        $shop_id = $shop_info['shopid'];
        $service_module = new ShopsServiceModule();
        $field = 'configId,open_suspension_chain';
        $result = $service_module->getShopConfig($shop_id, $field);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $result['data'] = array();
        }
        $this->ajaxReturn(returnData($result['data']));
    }
}
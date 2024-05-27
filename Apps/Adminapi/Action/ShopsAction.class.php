<?php

namespace Adminapi\Action;

use Adminapi\Model\ShopsModel;
use Home\Model\SystemModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺控制器
 */
class ShopsAction extends BaseAction
{

    /**
     * 查询店铺名称是否存在
     */
    public function checkShopName()
    {
        $m = D('Adminapi/Shops');
        $rs = $m->checkShopName(I('shopName'), (int)I('id'));
        echo json_encode($rs);
    }

    /**
     *
     */
    public function tomap()
    {
        $this->view->display('/shops/map');
    }

    /**
     *
     */
    public function tochooseMsgmap()
    {
        $this->view->display('/shops/chooseMsgmap');
    }


    /**
     * 新增/修改操作
     * https://www.yuque.com/youzhibu/ruah6u/ba9uak  新增
     * https://www.yuque.com/youzhibu/ruah6u/vkl86b  修改
     */
    public function edit()
    {
        //新增或者修改店铺的时候 注册达达店铺 或者更新达达店铺
        $userInfo = $this->isLogin();
        $m = new ShopsModel();
        if (I('shopId', 0) > 0) {
            $this->checkPrivelege('dplb_02');
            $rs = $m->edit($userInfo);//修改店铺信息
//            if (I('shopStatus', 0) <= -1) {
//                $rs = $m->reject();//停止或者拒绝店铺【弃用,单独接口】
//            } else {
//                $rs = $m->edit();//修改店铺信息
//            }
        } else {
            $this->checkPrivelege('dplb_01');
            $rs = $m->insert($userInfo);//新增店铺
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除店铺及相关信息
     * https://www.yuque.com/youzhibu/ruah6u/xbgpw1
     */
    public function del()
    {
        $userInfo = $this->isLogin();
        $this->checkPrivelege('dplb_03');
        $shopId = (int)I('shopId', 0);//店铺id
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
        $m = new ShopsModel();
        $rs = $m->del($shopId, $userInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取店铺详情
     */
    public function detail()
    {
        $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $shopId = (int)I('shopId', 0);//店铺id
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
        $m = new ShopsModel();
        $detail = $m->get($shopId);
        $this->ajaxReturn($detail);
    }

    /**
     * 获取店铺列表
     * https://www.yuque.com/youzhibu/ruah6u/slvlnw
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new ShopsModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 获取店铺配送范围详情
     * https://www.yuque.com/youzhibu/ruah6u/fyb55h
     */
    public function getShopDeliveryArea()
    {
        $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $shopId = I('shopId', 0);//店铺id
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
        $m = new ShopsModel();
        $list = $m->getShopDeliveryArea($shopId);
        $this->ajaxReturn($list);
    }

    /**
     * 编辑店铺配送范围
     * https://www.yuque.com/youzhibu/ruah6u/pzngyn
     */
    public function editShopDeliveryArea()
    {
        $userInfo = $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $shopId = I('shopId', 0);//店铺id
        $deliveryLatLng = I('deliveryLatLng', 0);//配送区域(不规则局域 存储json字符串)  按范围配送
        $deliveryLatLngName = I('deliveryLatLngName');//配送区域名称
        $relateAreaId = I('relateAreaId', 0);//配送区域ID   按区域配送
        $relateCommunityId = I('relateCommunityId', 0);//社区ID（多个以逗号连接）【保留暂未使用】
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
        if (!empty($deliveryLatLng) && !empty($relateAreaId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择按范围配送或按区域配送'));
        }
        if (empty($deliveryLatLng) && empty($relateAreaId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择按范围配送或按区域配送'));
        }
        $param = [];
        $param['shopId'] = $shopId;
        $param['deliveryLatLng'] = $deliveryLatLng;
        $param['deliveryLatLngName'] = $deliveryLatLngName;
        $param['relateAreaId'] = $relateAreaId;
        $param['relateCommunityId'] = $relateCommunityId;
        $m = new ShopsModel();
        $list = $m->editShopDeliveryArea($param, $userInfo);
        $this->ajaxReturn($list);
    }

    /**
     * 重置店铺密码
     * https://www.yuque.com/youzhibu/ruah6u/obmuze
     */
    public function updateShopPwd()
    {
        $userInfo = $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $shopId = I('shopId', 0);
        $loginPwd = I('loginPwd', 0);
//        $confirmPwd = I('confirmPwd', 0);
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
//        if (empty($loginPwd) || empty($confirmPwd)) {
//            $this->ajaxReturn(returnData(false,-1,'error','请输入重置密码或确认密码'));
//        }
//        if ($loginPwd != $confirmPwd) {
//            $this->ajaxReturn(returnData(false,-1,'error','请确认密码是否一致'));
//        }
        $m = new ShopsModel();
        $list = $m->updateShopPwd($shopId, $loginPwd, $userInfo);
        $this->ajaxReturn($list);
    }

    /**
     * 总后台快捷登录商户后台
     * https://www.yuque.com/youzhibu/ruah6u/yhc49p
     */
    public function shopTokenLogin()
    {
        $this->isLogin();
        $this->checkPrivelege('dplb_00');
        $shopId = (int)I('shopId', 0);
        $systemModel = new SystemModel();
        $config = $systemModel->getSystemConfig();
        $shopUrl = $config['shopUrl'];
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择店铺'));
        }
        if (empty($shopUrl)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写门店接口域名'));
        }
        $m = new ShopsModel();
        $list = $m->shopTokenLogin($shopId, $shopUrl);
        $this->ajaxReturn($list);
    }

    /**
     * 获取待审核店铺列表
     */
    public function queryPeddingByPage()
    {
        $this->isLogin();
        $this->checkPrivelege('dpsh_00');
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new ShopsModel();
        $list = $m->queryPeddingByPage($page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = D('Adminapi/Shops');
        $list = $m->queryByPage();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 获取待审核的店铺数量
     */
    public function queryPenddingGoodsNum()
    {
        $this->isLogin();
        $m = new ShopsModel();
        $rs = $m->queryPenddingShopsNum();
        $this->ajaxReturn($rs);
    }

    /**
     * 获取所有店铺列表,不带分页
     */
    public function getShopList()
    {
        $this->isLogin();
        $m = new ShopsModel();
        $list = $m->getShopList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 获取所有已审核店铺列表,不带分页
     */
    public function getAllShopList()
    {
        $this->isLogin();
        $m = new ShopsModel();
        $list = $m->getAllShopList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 获取店铺分类
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/mkbv6g
     */
    public function getShopCatList()
    {
        $this->isLogin();
        $shopId = I('shopId');
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = new ShopsModel();
        $list = $m->getShopCatList($shopId);
        $this->ajaxReturn($list);
    }

    /**
     * 获取店铺列表【用于搜索下拉列表】
     * https://www.yuque.com/youzhibu/ruah6u/eob90a
     */
    public function getSearchShopsList()
    {
        $this->isLogin();
        $shopWords = I('shopWords', 0);
        $m = new ShopsModel();
        $list = $m->getSearchShopsList($shopWords);
        $this->ajaxReturn($list);
    }

    /**
     * 复用店铺信息
     * https://www.yuque.com/youzhibu/ruah6u/adfmyi
     */
    public function addCopyShopInfo()
    {
        $userData = $this->isLogin();
        $shopId = (int)I('shopId', 0);
        $loginName = I('loginName', 0);//名称
        $loginPwd = I('loginPwd', 0);//密码
        $userName = I('userName');//用户名称
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择复用店铺'));
        }
        if (empty($loginName)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写登录账号'));
        }
        if (empty($loginPwd)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写登录密码'));
        }
        if (empty($userName)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写店主名称'));
        }
        $params = [];
        $params['shopId'] = $shopId;
        $params['loginName'] = $loginName;
        $params['loginPwd'] = $loginPwd;
        $params['userName'] = $userName;
        $m = new ShopsModel();
        $list = $m->addCopyShopInfo($params, $userData);
        $this->ajaxReturn($list);
    }

    /**
     * 店铺状态变更
     * -2:已停止 1:已审核
     * https://www.yuque.com/youzhibu/ruah6u/ikzgq5
     */
    public function editShopAuditStatus()
    {
        $userData = $this->isLogin();
        $shopId = (int)I('shopId', 0);
        $shopStatus = I('shopStatus', 0);//店铺状态 -2:已停止 1:已审核
        $statusRemarks = I('statusRemarks');//备注
        if (empty($shopId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择复用店铺'));
        }
        if (empty($shopStatus) || !in_array($shopStatus, [-2, 1])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择变更状态'));
        }
        $params = [];
        $params['shopId'] = $shopId;
        $params['shopStatus'] = $shopStatus;
        $params['statusRemarks'] = $statusRemarks;
        $m = new ShopsModel();
        $list = $m->editShopAuditStatus($params, $userData);
        $this->ajaxReturn($list);
    }
}
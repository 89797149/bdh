<?php

namespace Adminapi\Action;

use Adminapi\Model\GoodsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商品控制器
 */
class GoodsAction extends BaseAction
{

    /**
     * 查看商品详情
     * https://www.yuque.com/youzhibu/ruah6u/ppybw3
     */
    public function detail()
    {
        $this->isLogin();
        $this->checkPrivelege('splb_00');

        $id = I('id', 0, 'intval');
        if ($id <= 0) {
            $rs = returnData(false, -1, 'error', "请选择查看商品");
            $this->ajaxReturn($rs);
        }
        $m = new GoodsModel();
        $detail = $m->get();

        $this->ajaxReturn($detail);
    }

    /**
     * 查看详情
     */
    public function penddingDetail()
    {
        $this->isLogin();
        $this->checkPrivelege('spsh_00');
        $id = I('id', 0, 'intval');
        if ($id <= 0) {
            $this->ajaxReturn(returnData(false, -1, 'error', "请选择查看商品"));
        }
        $m = new GoodsModel();
        $detail = $m->get();

        $this->ajaxReturn($detail);
    }

    /**
     * 获取商品列表
     * https://www.yuque.com/youzhibu/ruah6u/oc5mak
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('splb_00');

        $params = I('');
        $params['page'] = I('page', 1, 'intval');
        $params['pageSize'] = I('pageSize', 15, 'intval');

        $m = new GoodsModel();
        $list = $m->queryByPage($params);

        $this->ajaxReturn(returnData($list));
    }


    /**
     * 分页查询
     */
    /*public function SecKill(){
        $this->isLogin();
        $this->checkPrivelege('splb_00');
        //获取地区信息
        $m = D('Adminapi/Areas');
        $this->assign('areaList',$m->queryShowByList(0));
        //获取商品分类信息
        $m = D('Adminapi/GoodsCats');
        $this->assign('goodsCatsList',$m->queryByList());
        $m = D('Adminapi/Goods');
        $page = $m->queryByPage();
        $pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
        $page['pager'] = $pager->show();
        $this->assign('Page',$page);
        $this->assign('shopName',I('shopName'));
        $this->assign('goodsName',I('goodsName'));
        $this->assign('areaId1',I('areaId1',0));
        $this->assign('areaId2',I('areaId2',0));
        $this->assign('goodsCatId1',I('goodsCatId1',0));
        $this->assign('goodsCatId2',I('goodsCatId2',0));
        $this->assign('goodsCatId3',I('goodsCatId3',0));
        $this->assign('isAdminBest',I('isAdminBest',-1));
        $this->assign('isAdminRecom',I('isAdminRecom',-1));
        $this->assign('isAdminShopSecKill',I('isAdminShopSecKill',-1));
        $this->assign('isAdminShopPreSale',I('isAdminShopPreSale',-1));
        $this->display("/seckill/list");
    }*/

    /**
     * 分页查询
     */
    public function SecKill()
    {
        $this->isLogin();
        $this->checkPrivelege('splb_00');

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = new GoodsModel();
        $list = $m->queryByPage($page, $pageSize);

        $this->ajaxReturn(returnData($list));
    }

    /**
     * 分页查询
     */
    public function queryPenddingByPage()
    {
        $this->isLogin();
        $this->checkPrivelege('spsh_00');

        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');

        $m = new GoodsModel();
        $list = $m->queryPendDingByPage($page, $pageSize);

        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询
     */
    public function queryByList()
    {
        $this->isLogin();
        $m = new GoodsModel();
        $list = $m->queryByPage();
//        $rs = array();
//        $rs['status'] = 1;
//        $rs['list'] = $list;
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 列表查询[获取启用的区域信息]
     */
    public function queryShowByList()
    {
        $this->isLogin();
        $m = D('Adminapi/Goods');
        $list = $m->queryShowByList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 修改待审核商品状态
     */
    public function changePenddingGoodsStatus()
    {
        $loginUserInfo = $this->isLogin();
        $this->checkPrivelege('spsh_04');
        $m = new GoodsModel();
//		$rs = $m->changeGoodsStatus($loginUserInfo);//旧接口
        $rs = $m->changeGoodsStatusNew($loginUserInfo);//新接口
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商品状态
     */
    public function changeGoodsStatus()
    {
        $userData = $this->isLogin();
        $this->checkPrivelege('splb_04');
        $m = new GoodsModel();
//		$rs = $m->changeGoodsStatus($loginUserInfo);//旧接口
        $rs = $m->changeGoodsStatusNew($userData);//新接口
        $this->ajaxReturn($rs);
    }

    /**
     * 获取待审核的商品数量
     */
    public function queryPenddingGoodsNum()
    {
        $this->isLogin();
        $m = new GoodsModel();
        $rs = $m->queryPenddingGoodsNum();
        $this->ajaxReturn($rs);
    }

    /**
     * 批量设置精品
     */
    public function changeBestStatus()
    {
        $loginUserInfo = $this->isLogin();
        $this->checkPrivelege('splb_04');
        $m = new GoodsModel();
        $rs = $m->changeBestStatus($loginUserInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 批量设置推荐
     */
    public function changeRecomStatus()
    {
        $loginUserInfo = $this->isLogin();
        $this->checkPrivelege('splb_04');
        $m = new GoodsModel();
        $rs = $m->changeRecomStatus($loginUserInfo);
        $this->ajaxReturn($rs);
    }

    /**
     * 跳去限时秒杀弹框
     */
    public function toSettlement()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_00');//跳转权限控制
        /*$m = D('Adminapi/OrderSettlements');
        if((int)I('id')>0){
            $object = $m->get();
            $this->assign('object',$object);
        } */
        $this->display("/goods/settlement");
    }


    /**
     * 跳预售弹框页面
     */
    public function ysSettlement()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_00');//跳转权限控制
        /*$m = D('Adminapi/OrderSettlements');
        if((int)I('id')>0){
            $object = $m->get();
            $this->assign('object',$object);
        } */
        $this->display("/goods/ysSettlement");
    }


    /**
     * 跳去限时秒杀编辑弹框
     */
    public function toEdittlement()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_00');//跳转权限控制

        $m = D('Adminapi/Goods');
        $id = (int)I('id', 0);
        $object = $m->toEdittlement($id);

        $this->assign('object', $object);

        //echo json_encode($object);
        $this->display("/goods/editSettlement");
    }


    /**
     * 跳去预售编辑弹框
     */
    public function toEdiysttlement()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_00');//跳转权限控制

        $m = D('Adminapi/Goods');
        $id = (int)I('id', 0);
        $object = $m->toEditystlement($id);

        $this->assign('object', $object);

        //echo json_encode($object);
        $this->display("/goods/editysSettlement");
    }

    /**
     * 批量设置秒杀商品
     */
    public function setGoodsSecKillStatus()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_01');
        $m = new GoodsModel();
        $rs = $m->setGoodsSecKillStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 批量设置预售商品
     */
    public function setGoodsPreSaleStatus()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_01');
        $m = new GoodsModel();
        $rs = $m->setGoodsPreSaleStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 批量取消商品秒杀状态 支持单个
     */
    public function CGoodsSecKillStatus()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_02');
        $m = new GoodsModel();
        $rs = $m->CGoodsSecKillStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 批量取消商品预售状态 支持单个
     */
    public function CGoodsPreSaleStatus()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_02');
        $m = new GoodsModel();
        $rs = $m->CGoodsPreSaleStatus();
        $this->ajaxReturn($rs);
    }

    /**
     * 修改商品秒杀信息
     */
    public function editGoodsSecKill()
    {
        $this->isLogin();
        $this->checkPrivelege('mshys_03');
        $m = new GoodsModel();
        $rs = $m->editGoodsSecKill();
        $this->ajaxReturn($rs);
    }

    /**
     *商品列表 分页
     */
    public function ajaxGoods()
    {
        //$this->isLogin();
        $m = new GoodsModel();
        $page = (int)I('page', 1);
        $object = $m->getList($page);
        $this->ajaxReturn($object);
    }

    /**
     *商品列表 不带分页
     */
    public function ajaxGoodsList()
    {
        //$this->isLogin();
        $m = new GoodsModel();
        $object = $m->ajaxGoodsList();
        $this->ajaxReturn($object);
    }
}
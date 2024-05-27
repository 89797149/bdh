<?php

namespace Merchantapi\Action;

use Home\Model\LivePlayModel;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商户端直播
 */
class LiveplayAction extends BaseAction
{
    private $shopInfo = [];

    public function __construct()
    {
        parent::__construct();
        $shopInfo = $this->MemberVeri();
        $openLivePlay = M('shops')->where(['shopId' => $shopInfo['shopId']])->getField('openLivePlay');
        if ($openLivePlay != 1) {
            $this->ajaxReturn(returnData(false, -1, 'error', '门店暂未开通直播权限'));
        }
        $shopInfo['openLivePlay'] = $openLivePlay;
        $this->shopInfo = $shopInfo;
    }

    /**
     * 上传微信素材库
     * 文档地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dz8vpp
     * @param file file 文件信息
     * */
    public function uploadMedia()
    {
        $livePlayModel = D('Home/LivePlay');
        $type = I('type', 'image');
        if (!in_array($type, ['image', 'voice', 'video', 'thumb'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请上传正确的文件类型'));
        }
        $file = $_FILES;
        $data = $livePlayModel->uploadMedia($type, $file);
        $this->returnResponse($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 添加直播
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xd1flw
     * */
    public function addLiveplay()
    {
        $shopInfo = $this->shopInfo;
        $m = D('Merchantapi/Liveplay');
        $requestParams = I();
        $requestParams['shopId'] = $shopInfo['shopId'];
        $data = $m->addLivePlay($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 删除直播/短视频
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/xd1flw
     * @param array liveplayId 直播id
     * */
    public function delLiveplay()
    {
        $shopInfo = $this->shopInfo;
        $liveplayId = I('liveplayId');
        $liveplayId = !empty($liveplayId) ? explode(',', $liveplayId) : [];
        $shopId = $shopInfo['shopId'];
        if (empty($liveplayId) || !is_array($liveplayId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->delLiveplay($shopId, $liveplayId);
        $this->ajaxReturn($data);
    }

    /**
     * 更新直播/短视频
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ylk4ag
     * */
    public function updateLiveplay()
    {
        $shopInfo = $this->shopInfo;
        $requestParams = I();
        $requestParams['shopId'] = $shopInfo['shopId'];
        $liveplayGoodsId = htmlspecialchars_decode($requestParams['liveplayGoodsId']);
        $liveplayGoodsId = !empty($liveplayGoodsId) ? json_decode($liveplayGoodsId, true) : [];
        $requestParams['liveplayGoodsId'] = $liveplayGoodsId;
        if (empty($requestParams['liveplayId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->updateLiveplay($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 直播/短视频-批量上下架
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gv9ikk
     * */
    public function bathActionSale()
    {
        $shopInfo = $this->shopInfo;
        $liveplayId = I('liveplayId', '');
        $liveplayId = explode(',', $liveplayId);
        $shopId = $shopInfo['shopId'];
        $liveSale = (int)I('liveSale', -1);
        if (!is_array($liveplayId) || (!in_array($liveSale, [0, 1]))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->bathActionSale($shopId, $liveplayId, $liveSale);
        $this->ajaxReturn($data);
    }

    /**
     * 获取直播列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/puw2m4
     * */
    public function getLiveplayList()
    {
        $shopInfo = $this->shopInfo;
        $requestParams = I();
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $params = [];
        $params['name'] = '';
        $params['type'] = '';
        $params['goodsCatId1'] = '';
        $params['goodsCatId2'] = '';
        $params['goodsCatId3'] = '';
        $params['anchorName'] = '';
        $params['anchorWechat'] = '';
        $params['live_status'] = '';
        $params['liveSale'] = '';
        $params['startDate'] = '';
        $params['endDate'] = '';
        parm_filter($params, $requestParams);
        $m = D('Merchantapi/Liveplay');
        $params['shopId'] = $shopInfo['shopId'];
        $data = $m->getLiveplayList($params, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     * 获取直播/短视频详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gtxcp8
     * */
    public function getLiveplayDetail()
    {
        $shopId = $this->shopInfo['shopId'];
        $liveplayId = I('liveplayId', 0);
        if (empty($liveplayId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->getLiveplayDetail($shopId, $liveplayId);
        $this->ajaxReturn($data);
    }

    /**
     * 删除直播/短视频商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bzflg9
     * */
    public function delLiveplayGoodsRelation()
    {
        $liveplayId = I('liveplayId', 0);
        $goodsId = I('goodsId');
        $goodsId = !empty($goodsId) ? explode(',', $goodsId) : [];
        if (empty($liveplayId) || empty($goodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->delLiveplayGoodsRelation($liveplayId, $goodsId);
        $this->ajaxReturn($data);
    }

    /**
     * 商品库->上传商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ba2med
     * */
    public function addLiveplayGoods()
    {
        $shopId = $this->shopInfo['shopId'];
        $goodsParams = htmlspecialchars_decode(I('goodsParams'));
        $goodsParams = json_decode($goodsParams, true);
        if (empty($goodsParams)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
//        $m = D('Merchantapi/Liveplay');
        $m = new \Merchantapi\Model\LiveplayModel();
        $data = $m->addLiveplayGoods($shopId, $goodsParams);
        $this->ajaxReturn($data);
    }

    /**
     * 商品库-商品详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/ut5gdb
     * */
    public function getLiveplayGoodsDetail()
    {
        $liveplayGoodsId = I('liveplayGoodsId');
        if (empty($liveplayGoodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->getLiveplayGoodsDetail('', $liveplayGoodsId);
        $this->ajaxReturn($data);
    }

    /**
     * 商品库->删除商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vyovd4
     * */
    public function delLiveplayGoods()
    {
        $shopId = $this->shopInfo['shopId'];
        $liveplayGoodsId = I('liveplayGoodsId', '');
        $liveplayGoodsId = explode(',', $liveplayGoodsId);
        if (empty($liveplayGoodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->delLiveplayGoods($shopId, $liveplayGoodsId);
        $this->ajaxReturn($data);
    }

    /**
     * 商品库->更新商品
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qohtpo
     * */
    public function updateLiveplayGoods()
    {
        $goodsParams = htmlspecialchars_decode(I('goodsParams'));
        $goodsParams = json_decode($goodsParams, true);
        if (empty($goodsParams)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Liveplay');
        $data = $m->updateLiveplayGoods($goodsParams);
        $this->ajaxReturn($data);
    }

    /**
     * 商品库->商品列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/bpkef1
     * */
    public function getLiveplayGoodsList()
    {
        $requestParams = I();
        $m = D('Merchantapi/Liveplay');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $data = $m->getLiveplayGoodsList($requestParams, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     * 获取小程序直播码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/reinuh
     * */
    public function getShareLiveplayCode()
    {
        $roomId = (int)I('roomId');
        if (empty($roomId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Home/LivePlay');
        $data = $m->getShareLiveplayCode($roomId);
        $this->ajaxReturn($data);
    }

    /**
     * 测试
     * */
    public function testSync()
    {
        $requestParams = I();
        $LiveplayModel = D('Home/LivePlay');
        $data = $LiveplayModel->getWxGoodsStatus();
        dd($data);
    }

}

?>
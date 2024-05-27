<?php

namespace Adminapi\Action;

use Home\Model\LivePlayModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 直播控制器
 */
class LivePlayAction extends BaseAction
{
    /**
     * test1 待处理,用到时再处理
     * */
    public function callback()
    {
        $params = [];
        $params['timestamp'] = '';
        $params['nonce'] = '';
        $params['msg_signature'] = '';
        parm_filter($params, $_GET);
        $data = D('Home/LivePlay')->getTicket($params);
        $this->returnResponse($data['code'], $data['msg']);
    }

    /**
     * 获取微信推送的ticket(component_verify_ticket)
     * */
    public function getTicket()
    {
        $params = [];
        $params['timestamp'] = '';
        $params['nonce'] = '';
        $params['msg_signature'] = '';
        parm_filter($params, $_GET);
        $data = D('Home/LivePlay')->getTicket($params);
        //$data = ['code'=>0,'msg'=>'','data'=>[]];
        $this->returnResponse($data['code'], $data['msg']);
    }

    /**
     * 微信授权后回调URI，获取授权码和过期时间
     */
    public function redirect_url()
    {
        $authCode = !empty($_GET['auth_code']) ? $_GET['auth_code'] : '';//授权码
        $expiresIn = !empty($_GET['expires_in']) ? $_GET['expires_in'] : '';//失效日期
        $m = new LivePlayModel();
        $data = $m->redirect_url($authCode, $expiresIn);
        $this->returnResponse($data['code'], $data['msg']);
    }

    /**
     * 授权页
     * */
    public function auth()
    {
        $domain = WSTDomain();
//        $livePlayModel = D('Home/LivePlay');
        $livePlayModel = new LivePlayModel();
        $authInfo = $livePlayModel->getAuth();
        $authUrl = $authInfo['data']['authUrl'];
        $page = <<<Eof
        <!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<meta name="keywords" content="" />
<meta name="description" content="" />
<title></title>
<script src="{$domain}/Public/js/jquery-3.1.1.min.js"></script>
<script src="{$domain}/Public/js/popwin.js"></script>
<script>
$(document).ready(function() {
    popWin.showWin("1000","800","公众平台帐号授权","$authUrl");
	$('#popWinClose').on('click',function() {
	    window.history.go(-1);
	    //window.history.back();
	});
});
</script>
</head>
<body>
</body>
</html>
Eof;
        echo $page;
        exit;
    }

    /**
     * 上传微信素材库
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/gc4px5
     * @param string type 媒体文件类型【图片（image）| 语音（voice）| 视频（video）| 缩略图（thumb）】
     * @param file file 文件信息
     * */
    public function uploadMedia()
    {
        $this->isLogin();
        $livePlayModel = D('Home/LivePlay');
        $type = I('type', 'image');
        if (!in_array($type, ['image', 'voice', 'video', 'thumb'])) {
            $this->returnResponse(-1, '请上传正确的文件类型', false);
        }
        $file = $_FILES;
        $data = $livePlayModel->uploadMedia($type, $file);
        $this->returnResponse($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 添加直播/短视频
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/im106n
     * */
    public function addLiveplay()
    {
        $this->isLogin();
        $m = D('Adminapi/LivePlay');
        $requestParams = I();
        if (empty($requestParams['shopId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '门店id不能为空'));
        }
        $data = $m->addLivePlay($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 更新直播/短视频
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/pzw2xg
     * */
    public function updateLiveplay()
    {
        $this->isLogin();
        $requestParams = I();
        $m = D('Adminapi/LivePlay');
        $liveplayGoodsId = htmlspecialchars_decode($requestParams['liveplayGoodsId']);
        $liveplayGoodsId = !empty($liveplayGoodsId) ? json_decode($liveplayGoodsId, true) : [];
        $requestParams['liveplayGoodsId'] = $liveplayGoodsId;
        if (empty($requestParams['liveplayId'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $data = $m->updateLiveplay($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 删除直播/短视频
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/gh4u42
     * @param array liveplayId 直播id
     * */
    public function delLiveplay()
    {
        $this->isLogin();
        $liveplayId = I('liveplayId', []);
        $liveplayId = !empty($liveplayId) ? json_decode(htmlspecialchars_decode($liveplayId), true) : [];
        if (empty($liveplayId) || !is_array($liveplayId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Adminapi/LivePlay');
        $data = $m->delLiveplay($liveplayId);
        $this->ajaxReturn($data);
    }

    /**
     * 直播/短视频-批量上下架/批量禁用
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ucolgw
     * */
    public function bathActionSale()
    {
        $this->isLogin();
        $liveplayId = I('liveplayId', []);
        $liveplayId = !empty($liveplayId) ? json_decode(htmlspecialchars_decode($liveplayId), true) : [];
        $liveSale = (int)I('status', -2);
        if (!is_array($liveplayId) || (!in_array($liveSale, [-1, 0, 1]))) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Adminapi/LivePlay');
        $data = $m->bathActionSale($liveplayId, $liveSale);
        $this->ajaxReturn($data);
    }

    /**
     * 获取直播/短视频列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/agrfie
     * */
    public function getLiveplayList()
    {
        $this->isLogin();
        $requestParams = I();
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $params = [];
        $params['shopName'] = '';
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
        $m = D('Adminapi/LivePlay');
        $data = $m->getLiveplayList($params, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     * 获取直播/短视频详情
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/nopr2i
     * */
    public function getLiveplayDetail()
    {
        $this->isLogin();
        $liveplayId = I('liveplayId', 0);
        if (empty($liveplayId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Adminapi/LivePlay');
        $data = $m->getLiveplayDetail($liveplayId);
        $this->ajaxReturn($data);
    }

    /**
     * 获取门店商品库-商品列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ipbx5k
     * */
    public function getLiveplayGoodsList()
    {
        $this->isLogin();
        $requestParams = I();
        $m = D('Adminapi/LivePlay');
        $page = I('page', 1);
        $pageSize = I('pageSize', 15);
        $data = $m->getLiveplayGoodsList($requestParams, $page, $pageSize);
        $this->ajaxReturn($data);
    }

    /**
     * 更新商品库商品状态 PS:小程序直播不可用
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ppovq2
     * */
    public function updateLiveplayGoodsStatus()
    {
        $this->isLogin();
        $liveplayGoodsId = I('liveplayGoodsId', 0);
        $status = (int)I('status');
        if (empty($liveplayGoodsId) || !in_array($status, [0, 1, 2, 3])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Adminapi/LivePlay');
        $data = $m->updateLiveplayGoodsStatus($liveplayGoodsId, $status);
        $this->ajaxReturn($data);
    }

    /**
     * 删除直播/短视频商品
     * 文档链接地址:
     * */
    public function delLiveplayGoodsRelation()
    {
        $this->isLogin();
        $liveplayId = I('liveplayId', 0);
        $goodsId = I('goodsId');
        $goodsId = !empty($goodsId) ? json_decode(htmlspecialchars_decode($goodsId), true) : [];
        if (empty($liveplayId) || empty($goodsId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Adminapi/LivePlay');
        $data = $m->delLiveplayGoodsRelation($liveplayId, $goodsId);
        $this->ajaxReturn($data);
    }

    /**
     * 获取直播码
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/vpopym
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
     * 测试,勿删
     * */
    public function testApi()
    {
        $livePlayModel = D('Home/LivePlay');
        //$data = $livePlayModel->getAccessToken();
        $data = $livePlayModel->getShareLiveplayCode(7);
//        $this->returnResponse($data);
        dd($data);
    }

}
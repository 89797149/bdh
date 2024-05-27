<?php

namespace Adminapi\Action;
use Adminapi\Model\PushModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 消息推送控制器
 */
class PushAction extends BaseAction
{

    /**
     * 消息推送
     */
    /*public function newsPush(){
        $this->isLogin();
        $this->checkPrivelege('tslb_00');
        $this->view->display('/push/newsPush');
    }*/

    /**
     * 消息推送 - 动作
     */
    public function doNewsPush()
    {
        $param = I('param.');
        $m = D('Adminapi/Push');
        $result = $m->doNewsPush($param);

        $this->ajaxReturn($result);
    }

    /**
     * 消息推送记录
     */
    public function pushRecord()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/Push');
        $list = $m->queryByPage($page, $pageSize);
        $this->returnResponse(0, '操作成功', $list);
    }

    /**
     * 获取小程序模板
     */
    public function wxTemplateList()
    {
        $m = D('Adminapi/Template');
        $list = $m->getTemplateList(array('wntFlag' => 1));
        $this->ajaxReturn(array('data' => $list));
    }

    /**
     * 获取小程序模板详情
     */
    public function wxTemplateDetail()
    {
        $id = I('id', 0, 'intval');
        if (empty($id)) $this->ajaxReturn(array('code' => 1, 'msg' => '参数不全'));
        $m = D('Adminapi/Template');
        $detail = $m->wxTemplateDetail(array('wntFlag' => 1, 'id' => $id));
        if (!empty($detail)) {
            $detail['names'] = explode(',', $detail['name']);
        }
        $this->ajaxReturn(array('code' => 0, 'data' => $detail));
    }

    /**
     * 精准营销
     */
    public function precisionMarketing()
    {
        $userData = $this->isLogin();
        $m = new PushModel();
        $result = $m->precisionMarketing($userData);
        $this->ajaxReturn($result);
    }

    /**
     * 推送信息
     */
    public function postMessage()
    {
        $id = I('id');
        $userId = I('userId');
        $m = D('Adminapi/Push');
        $result = $m->postMessage($id, $userId);
        $this->ajaxReturn($result);
    }
}
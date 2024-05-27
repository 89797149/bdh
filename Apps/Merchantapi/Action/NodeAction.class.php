<?php

namespace Merchantapi\Action;
;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商城消息控制器
 */
class NodeAction extends BaseAction
{
    /**
     * 获取
     */
    public function getlist()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->getlist($parameter, $msg);
        $this->returnResponse(1, '获取成功', array('list' => $res));
    }

    /*
     * 获取权限列表
     * */
    public function getlistNew()
    {
        $shopInfo = $this->MemberVeri();
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->getlistNew($parameter, $msg);
        $this->returnResponse(1, '获取成功', array('list' => $res));
    }

    /**
     * 获取权限列表【树形】
     */
    public function getAuthRuleList()
    {
        $shopInfo = $this->MemberVeri();
        $param = [];
        $param['shopId'] = $shopInfo['shopId'];
        $param['rid'] = I('rid',0);
        $param['userId'] = $shopInfo['userId'];
        $nodeModel = D('Merchantapi/Node');
        $res = $nodeModel->getAuthRuleList($param);
        $this->returnResponse(1, '获取成功', array('list' => $res));
    }

}
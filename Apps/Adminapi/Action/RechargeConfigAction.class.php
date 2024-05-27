<?php

namespace Adminapi\Action;

use Adminapi\Model\RechargeConfigModel;
use Adminapi\Model\RechargesetModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 充值返现规则表
 */
class RechargeConfigAction extends BaseAction
{
    /**
     * 获取充值规则详情
     * @param int $id
     */
    public function getRechargeConfigDetail()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/RechargeConfig');
        $rs = $m->getRechargeConfigDetail($id);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取充值规则列表
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function getRechargeConfigList()
    {
        $this->isLogin();
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $m = D('Adminapi/RechargeConfig');
        $rs = $m->getRechargeConfigList($page, $pageSize);
        $this->ajaxReturn($rs);
    }

    /**
     * 新增充值返现规则
     * @param decimal minAmount 最小充值金额
     * @param decimal maxAmount 最大充值金额
     * @param decimal returnAmount 充值返现金额
     */
    public function addRechargeConfig()
    {
        $userData = $this->isLogin();
        $requestParams = I();
        $params = [];
        $params['minAmount'] = 0;
        $params['maxAmount'] = 0;
        $params['returnAmount'] = 0;
        parm_filter($params, $requestParams);
        if ($params['minAmount'] < 0) {
            $this->returnResponse(-1, '最小充值金额不正确');
        }
        if ($params['maxAmount'] <= 0) {
            $this->returnResponse(-1, '最大充值金额必须大于0');
        }
        if ($params['returnAmount'] < 0) {
            $this->returnResponse(-1, '正输入正确的充值返现金额');
        }
        if ($params['minAmount'] > $params['maxAmount']) {
            $this->returnResponse(-1, '请输入正确的充值金额区间');
        }
        $params['returnAmount'] = (float)$params['returnAmount'];
        $m = new RechargeConfigModel();
        $rs = $m->addRechargeConfig($params, $userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 更新充值返现规则
     * @param int id 充值返现规则id
     * @param decimal minAmount 最小充值金额
     * @param decimal maxAmount 最大充值金额
     * @param decimal returnAmount 充值返现金额
     */
    public function updateRechargeConfig()
    {
        $userData = $this->isLogin();
        $requestParams = I();
        $params = [];
        $params['id'] = 0;
        $params['minAmount'] = 0;
        $params['maxAmount'] = 0;
        $params['returnAmount'] = 0;
        parm_filter($params, $requestParams);
        if ($params['id'] <= 0) {
            $this->returnResponse(-1, '参数错误');
        }
        if ($params['minAmount'] < 0) {
            $this->returnResponse(-1, '最小充值金额不正确');
        }
        if ($params['maxAmount'] <= 0) {
            $this->returnResponse(-1, '最大充值金额必须大于0');
        }
        if ($params['returnAmount'] < 0) {
            $this->returnResponse(-1, '正输入正确的充值返现金额');
        }
        if ($params['minAmount'] > $params['maxAmount']) {
            $this->returnResponse(-1, '请输入正确的充值金额区间');
        }
        $params['returnAmount'] = (float)$params['returnAmount'];
        $m = new RechargeConfigModel();
        $rs = $m->updateRechargeConfig($params, $userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function delRechargeConfig()
    {
        $userData = $this->isLogin();
        $id = (int)I('id');
        if ($id <= 0) {
            $this->returnResponse(-1, '参数不正确');
        }
        $m = new RechargeConfigModel();
        $rs = $m->delRechargeConfig($id, $userData);
        $this->ajaxReturn($rs);
    }

    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = D('Adminapi/RechargeConfig');
        $list = $m->queryByPage($page, $pageSize);
        $this->returnResponse(0, '操作成功', $list);
    }
}

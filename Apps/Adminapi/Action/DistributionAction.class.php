<?php

namespace Adminapi\Action;

use Adminapi\Model\DistributionModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 分销控制器
 */
class DistributionAction extends BaseAction
{
    /**
     * 分页查询
     */
    public function index()
    {
        $this->isLogin();
        $page = I('page', 1, 'intval');
        $pageSize = I('pageSize', 15, 'intval');
        $m = new DistributionModel();
        $list = $m->queryByPage($page, $pageSize);
        $this->ajaxReturn(returnData($list));
    }

    /**
     * 分销信息编辑
     */
    public function editStatus()
    {
        $this->isLogin();
        $m = D('Adminapi/Distribution');
        $request = I();
        $data = [];
        !empty($request['id']) ? $data['id'] = $request['id'] : $data['id'] = 0;
        $data['updateTime'] = date('Y-m-d H:i:s', time());
        $data['state'] = 1;
        $response = $m->editStatus($data);
        $this->ajaxReturn($response);
    }

    /**
     * 修改审核的状态
     */
    public function distributionWithdrawAudit()
    {
        $userData = $this->isLogin();
        $reviewerId = $this->isLogin()['staffId'];
        $m = new DistributionModel();

        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }

        $response = $m->distributionWithdrawAudit($reviewerId, $userData);
        $this->ajaxReturn($response);
    }

    /**
     * 提现列表
     */
    public function distributionWithdraw()
    {
        $this->isLogin();
        $m = D('Adminapi/Distribution');

        $data['userPhone'] = WSTAddslashes(I('userPhone'));
        $data['orderNo'] = WSTAddslashes(I('orderNo'));

        $data['page'] = I('page', 1, 'intval');
        $data['pageSize'] = I('pageSize', 15, 'intval');
        $data['state'] = I('state', '');

        $list = $m->distributionWithdraw($data);
        $this->returnResponse(0, '操作成功', $list);
    }

    /**
     *  提现详情
     */
    public function withdrawInfo()
    {
        $this->isLogin();
        $id = I('id', 0, 'intval');
        if (empty($id)) {
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/Distribution');
        $detail = $m->getInfo($id);
        $this->returnResponse(0, '操作成功', $detail);
    }

    /**
     * 分销信息编辑
     */
    public function editWithdrawStatus()
    {
        $this->isLogin();
//        $this->checkPrivelege('fxyjtx_01');
        $m = D('Adminapi/Distribution');
        $request = I();
        $data = [];
        !empty($request['id']) ? $data['id'] = $request['id'] : $data['id'] = 0;
        $data['updateTime'] = date('Y-m-d H:i:s', time());
        $data['state'] = 1;
        $response = $m->editWithdrawStatus($data);
        $this->ajaxReturn($response);
    }

    /**
     * 获取分销记录 (后加接口)
     * @param int state 状态 (0:待结算 | 1:已结算 | 20:全部)
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param string userName 会员名称
     * @param string userPhone 会员手机号
     * @param string maxMoeny 最大金额 PS:用于金额区间查询
     * @param string minMoeny 最小金额 PS:用于金额区间查询
     * @param string orderNo 订单号
     * @param int goodsCatId1 商城一级分类 Home/GoodsCats/queryByList
     * @param int goodsCatId2 商城二级分类
     * @param int goodsCatId3 商城三级分类
     * @param string p 页码
     */
    public function getDistributionList()
    {
        $this->isLogin();
//        $this->checkPrivelege('fxsjgl_00');
        $m = D('Adminapi/Distribution');
        $param = I();
        $param['page'] = I('page', 1, 'intval');
        $param['pageSize'] = I('pageSize', 15, 'intval');
        $res = $m->getDistributionList($param);
        $this->ajaxReturn($res);
    }

    /**
     * 分销记录金额统计 (后加接口,配合分销记录使用)
     * @param int state 状态 (0:待结算 | 1:已结算 | 20:全部)
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param string userName 会员名称
     * @param string userPhone 会员手机号
     * @param string maxMoeny 最大金额
     * @param string minMoeny 最小金额
     * @param string orderNo 订单号
     * @param int goodsCatId1 商城一级分类 Home/GoodsCats/queryByList
     * @param int goodsCatId2 商城二级分类
     * @param int goodsCatId3 商城三级分类
     */
    public function getDistributionListCountMoney()
    {
        $this->isLogin();
        //$this->checkPrivelege('fxsjgl_00');
        $m = D('Adminapi/Distribution');
        $param = I();
        $res = $m->getDistributionListCountMoney($param);
        $this->ajaxReturn($res);
    }

    /**
     * 会员分销关系查询 (后加接口)
     * @param string userPhone 会员手机号
     * @param string loginName 登陆账号
     */
    public function getDistributionRelation()
    {
        $this->isLogin();
        $apiRes['code'] = -1;
        $apiRes['msg'] = '参数错误';
        $apiRes['data'] = array();
        $m = D('Adminapi/Distribution');
        $param = I();
        if (empty($param['userPhone']) && empty($param['loginName'])) {
            $this->ajaxReturn($apiRes);
        }
        $res = $m->getDistributionRelation($param);
        $this->ajaxReturn($res);
    }

    /**
     * 分销提现接口 (后加接口)
     * @param string userPhone PS:会员手机号
     * @param string loginName PS:登陆账号
     * @param string orderNo PS:提现订单号
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param string state PS:状态(0:审核中,1提现成功,20=>全部)
     */
    public function getDistributionWithdraw()
    {
        $this->isLogin();
        /*$this->isLogin();
        $this->checkPrivelege('fxyjtx_00');*/
        /*$apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '参数错误';
        $apiRes['apiState'] = 'error';*/

        $m = D('Adminapi/Distribution');
        $data['userPhone'] = WSTAddslashes(I('userPhone'));
        $data['loginName'] = WSTAddslashes(I('loginName'));
        $data['orderNo'] = WSTAddslashes(I('orderNo'));
        $data['state'] = I('state');
        $data['startDate'] = I('startDate');
        $data['endDate'] = I('endDate');
        $data['page'] = I('page', 1, 'intval');
        $data['pageSize'] = I('pageSize', 15, 'intval');
        $res = $m->getDistributionWithdraw($data);
        $this->ajaxReturn($res);
    }

    /**
     * 分销提现接口金额统计 (后加接口,配合分销提现列表接口使用)
     * @param string userPhone PS:会员手机号
     * @param string loginName PS:登陆账号
     * @param string orderNo PS:提现订单号
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param string state PS:状态(0:审核中,1提现成功,20=>全部)
     */
    public function getDistributionWithdrawCountMoney()
    {
        $this->isLogin();
        /*$this->isLogin();
        $this->checkPrivelege('fxyjtx_00');*/
        /*$apiRes['apiCode'] = -1;
        $apiRes['apiInfo'] = '参数错误';
        $apiRes['apiState'] = 'error';*/

        $m = D('Adminapi/Distribution');
        $data['userPhone'] = WSTAddslashes(I('userPhone'));
        $data['loginName'] = WSTAddslashes(I('loginName'));
        $data['orderNo'] = WSTAddslashes(I('orderNo'));
        $data['state'] = I('state');
        $data['startDate'] = I('startDate');
        $data['endDate'] = I('endDate');
        $res = $m->getDistributionWithdrawCountMoney($data);
        $this->ajaxReturn($res);
    }

    /**
     * 分销-用户分销记录列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ob6gsb
     * */
    public function getDistributionLogList()
    {
        $this->isLogin();
        $reqParams = I();
        $params = array(
            'orderNo' => '',//订单号
            'goodsName' => '',//商品名
            'paymentUserName' => '',//下单人用户名
            'paymentUserPhone' => '',//下单人手机号
            'addtimeStart' => '',//时间-开始时间
            'addtimeEnd' => '',//时间-结束时间
            'invitationName' => '',//邀请人用户名
            'inviteeName' => '',//受邀人用户名
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($params, $reqParams);
        $model = new DistributionModel();
        $res = $model->getDistributionLogList($params);
        $this->ajaxReturn(returnData($res));
    }

}

?>
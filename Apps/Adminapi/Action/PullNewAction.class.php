<?php
namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 对推管理
 */
class PullNewAction extends BaseAction
{
    /**
     * 获取地推列表
     * @param string token
     * @param string userName 邀请人姓名
     * @param string userPhone 邀请人手机号
     * @param string usersToIdUserName 受邀人名称
     * @param string usersToIdUserUserPhone 受邀人手机号
     * @param string startDate 拉新时间-开始时间
     * @param string endDate 拉新时间-结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getPullNewList()
    {
        $this->isLogin();
        $requestParams = I();
        $params = [];
        $params['userName'] = '';
        $params['userPhone'] = '';
        $params['usersToIdUserName'] = '';
        $params['usersToIdUserUserPhone'] = '';
        $params['startDate'] = '';
        $params['endDate'] = '';
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params,$requestParams);
        $m = D('Adminapi/PullNew');
        $data = $m->getPullNewList($params);
        $this->ajaxReturn($data);
    }
}
<?php
namespace Adminapi\Action;
use Adminapi\Model\UserDetailModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员详情控制器
 */
class UserDetailAction extends BaseAction
{
    /**
     * 会员详情
     */
    public function getUserDetail()
    {
        $this->isLogin();
        $this->checkPrivelege('hylb_02');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $type = I('type', 1, 'intval');
        $typeInfo = [1,2,3,4,5,6,7,8,9,10,11,12];
        if (!in_array($type, $typeInfo)) {
            $rs['msg'] = '参数有误';
            $this->ajaxReturn($rs);
        }
        $m = new UserDetailModel();
        $detail = $m->getUserDetail();
        $this->ajaxReturn($detail);
    }

    /**
     * 平台修改会员密码
     */
    public function editUserPwd()
    {
        $this->isLogin();
        $this->checkPrivelege('hylb_02');
        $rs = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $userId = I('userId', 0, 'intval');
        $loginPwd = I('loginPwd');
        if (empty($userId) || empty($loginPwd)) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $m = D('Adminapi/UserDetail');
        $data = $m->editUserPwd();
        $this->ajaxReturn($data);
    }
}
<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员等级控制器(使用总后台等级时使用)
 */
class UserRankAction extends BaseAction {

    /**
     * 等级列表
     */
    public function rankList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/UserRank');
        $parameter = [];
        $res = $m->rankList($parameter);
        $this->ajaxReturn($res);
    }
}
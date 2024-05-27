<?php
namespace Merchantapi\Action;;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 配置
 */
class SystemAction extends BaseAction
{
    /**
     * 获取商城配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/afg6hv
     */
    public function getMallConfig()
    {
        $this->MemberVeri();
        $systemModel = D('Home/System');
        $config = $systemModel->getSystemConfig();
        $this->ajaxReturn(returnData($config));
    }
}

?>

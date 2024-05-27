<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器
 */
class  DistributionAction extends BaseAction {
	/**
     * 分销页面
	 */
    public function index(){
        //分销封面图可以在商城设置->运营设置中自定义
        $distributionImg = M('sys_configs')->where(['fieldCode'=>'distributionImg'])->getField('fieldValue');
        $info['distributionImg'] = $distributionImg;
        $this->assign('info',$info);
        $this->display("default/distribution/index");
    }

    /**
     * 下载页
     */
    public function download(){
        $this->display("default/distribution/download");
    }
}
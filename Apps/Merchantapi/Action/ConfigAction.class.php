<?php
/**
 * 获取站点配置
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-04-21
 * Time: 13:58
 */

namespace Merchantapi\Action;


class ConfigAction extends BaseAction
{
    /*
     * 获取站点配置
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/tn7eud
     * */
    public function getConfig()
    {
        $this->MemberVeri();
        $config = $GLOBALS['CONFIG'];
        $data = array(
            'qiniuDomain' => $config['qiniuDomain'],
            'qiniuUploadUrl' => $config['qiniuUploadUrl'],
            'siteLogo' => $config['shopLogo'],
        );
        $this->ajaxReturn(returnData($data));
    }
}
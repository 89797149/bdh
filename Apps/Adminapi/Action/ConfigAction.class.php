<?php
/**
 * 获取站点配置
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-04-21
 * Time: 13:58
 */

namespace Adminapi\Action;


class ConfigAction extends BaseAction
{
    /*
     * 获取站点配置
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/lrct2r
     * */
    public function getConfig()
    {
        $this->isLogin();
        $config = $GLOBALS['CONFIG'];
        $data = array(
            'qiniuDomain' => $config['qiniuDomain'],
            'qiniuUploadUrl' => $config['qiniuUploadUrl'],
            'siteLogo' => $config['shopLogo'],
        );
        $this->ajaxReturn(returnData($data));
    }
}
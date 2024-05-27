<?php

namespace Adminapi\Action;

use Adminapi\Model\AreasModel;
use Adminapi\Model\ConfigsModel;


/**
 * Class ConfigsAction
 * @package Adminapi\Action
 * 配置控制器
 */
class ConfigsAction extends BaseAction
{

    /**
     * 获取配置分类列表
     * https://www.yuque.com/youzhibu/ruah6u/dwsndg
     */
    public function getConfigClassList()
    {
        $this->isLogin();
        $m = new ConfigsModel();
        $rs = $m->getConfigClassList();
        $this->ajaxReturn(returnData($rs));
    }

    /**
     * 获取配置分类详情
     * https://www.yuque.com/youzhibu/ruah6u/hmyh9z
     */
    public function getConfigClassInfo()
    {
        $this->isLogin();
        $m = new ConfigsModel();
        $id = (int)I('id',0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要编辑的配置分类'));
        }

        $rs = $m->getConfigClassInfo($id);
        $this->ajaxReturn($rs);
    }

    /**
     * 编辑配置分类信息
     * https://www.yuque.com/youzhibu/ruah6u/uexnga
     */
    public function editConfigClassInfo()
    {
        $this->isLogin();
        $m = new ConfigsModel();
        $id = (int)I('id',0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要编辑的配置分类'));
        }

        $params = I();
        $param = [];
        $param['className'] = null;
        $param['sort'] = null;
        parm_filter($param,$params);
        $param['id'] = $id;
        $rs = $m->editConfigClassInfo($param);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取配置列表
     * https://www.yuque.com/youzhibu/ruah6u/mb7cqx
     */
    public function getConfigsList()
    {
        $this->isLogin();
        $m = new ConfigsModel();
        $id = (int)I('id',0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要编辑的配置分类'));
        }

        $rs = $m->getConfigsList($id);
        $this->ajaxReturn($rs);
    }

    /**
     * 变更配置信息分类
     * https://www.yuque.com/youzhibu/ruah6u/nau0u9
     */
    public function editConfigsInfo()
    {
        $this->isLogin();
        $m = new ConfigsModel();
        $id = (int)I('id',0);
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要编辑的配置分类'));
        }
        $params = [];
        $params['id'] = $id;
        $params['configId'] = I('configId');
        $rs = $m->editConfigsInfo($params);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取分类广告列表
     * https://www.yuque.com/youzhibu/ruah6u/nmdvfa
     */
    public function getAdClassConfigList(){
        $this->isLogin();
        $m = new ConfigsModel();
        $parentId = 18;//分类广告【不可更改】
        $rs = $m->getConfigParentIdByList($parentId);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取官网配置列表
     * https://www.yuque.com/youzhibu/ruah6u/ckw30m
     */
    public function getWebsiteConfigList(){
        $this->isLogin();
        $m = new ConfigsModel();
        $parentId = 19;//官网配置【不可更改】
        $rs = $m->getConfigParentIdByList($parentId);
        $areaId2 = intval($GLOBALS['CONFIG']['defaultCity']) > 0 ? $GLOBALS['CONFIG']['defaultCity'] : (int)C('DEFAULT_CITY');
        $areasModel = new AreasModel();
        $area = $areasModel->get($areaId2);
        $list = array(
            'configs' => $rs['data'],
            'areaList' => (array)$areasModel->getAreasList(),
        );
        $list['areaId1'] = (string)$area['parentId'];
        $rs['data'] = $list;
        $this->ajaxReturn($rs);
    }
}
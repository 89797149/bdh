<?php

namespace Adminapi\Model;

use App\Modules\Config\SysConfigClassServiceModule;
use App\Modules\Config\SysConfigsServiceModule;


/**
 * Class ConfigsModel
 * @package Adminapi\Model
 * 配置服务类
 */
class ConfigsModel extends BaseModel
{
    /**
     * @return mixed
     * 获取配置分类列表
     */
    public function getConfigClassList()
    {
        $SysConfigClassServiceModule = new SysConfigClassServiceModule();
        $getConfigClassList = $SysConfigClassServiceModule->getConfigClassList();
        return $getConfigClassList['data'];
    }

    /**
     * @param $id
     * @return mixed
     * 获取配置分类详情
     */
    public function getConfigClassInfo($id)
    {
        $SysConfigClassServiceModule = new SysConfigClassServiceModule();
        $getConfigClassInfo = $SysConfigClassServiceModule->getConfigClassInfo($id);
        if(empty($getConfigClassInfo['data'])){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        return returnData($getConfigClassInfo['data']);
    }

    /**
     * @param $param
     * @return mixed
     * 编辑配置分类信息
     */
    public function editConfigClassInfo($param)
    {
        $SysConfigClassServiceModule = new SysConfigClassServiceModule();
        $getConfigClassInfo = $SysConfigClassServiceModule->getConfigClassInfo($param['id']);
        if(empty($getConfigClassInfo['data'])){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $SysConfigClassServiceModule->editConfigClassInfo($param);
        return returnData(true);
    }

    /**
     * @param $id
     * @return mixed
     * 获取配置列表
     */
    public function getConfigsList($id)
    {
        $SysConfigClassServiceModule = new SysConfigClassServiceModule();
        $getConfigClassInfo = $SysConfigClassServiceModule->getConfigClassInfo($id);
        if(empty($getConfigClassInfo['data'])){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $sysConfigsServiceModule = new SysConfigsServiceModule();
        $getConfigsListByParentId = $sysConfigsServiceModule->getConfigsList($id);
        $configIds = array_get_column($getConfigsListByParentId['data'],'configId');
        $getConfigsList = $sysConfigsServiceModule->getConfigsList();
        $getConfigsLists = $getConfigsList['data'];
        $getConfigClassList = $SysConfigClassServiceModule->getConfigClassList();
        $getConfigClassLists = $getConfigClassList['data'];
        foreach ($getConfigClassLists as $key=>$val){
            $configList = [];
            foreach ($getConfigsLists as $k=>$v){
                $getConfigsLists[$k]['isChecked'] = false;//是否选中【false:未选中|true:已选中】
                if(in_array($v['configId'],$configIds)){
                    $getConfigsLists[$k]['isChecked'] = true;
                }
                if((int)$val['id'] == (int)$v['parentId']){
                    $configList[] = $getConfigsLists[$k];
                }
            }
            $getConfigClassLists[$key]['configList'] = (array)$configList;
        }
        return returnData($getConfigClassLists);
    }

    /**
     * @param $param
     * @return mixed
     * 变更配置信息分类
     */
    public function editConfigsInfo($param)
    {
        $SysConfigClassServiceModule = new SysConfigClassServiceModule();
        $getConfigClassInfo = $SysConfigClassServiceModule->getConfigClassInfo($param['id']);
        if(empty($getConfigClassInfo['data'])){
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $sysConfigsServiceModule = new SysConfigsServiceModule();
        $editConfigsInfo = $sysConfigsServiceModule->editConfigsInfo($param);
//        if(empty($editConfigsInfo['data'])){
//            return returnData(false, -1, 'error', '操作失败');
//        }
        return returnData(true);
    }

    /**
     * @param $parentId
     * @return mixed
     * 所属类别ID获取配置列表
     */
    public function getConfigParentIdByList($parentId)
    {
        $field = "*";
        $sysConfigsServiceModule = new SysConfigsServiceModule();
        $getAdClassList = $sysConfigsServiceModule->getConfigsList($parentId,$field);
        return returnData($getAdClassList['data']);
    }
}
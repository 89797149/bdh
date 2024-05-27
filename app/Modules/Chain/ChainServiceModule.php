<?php

namespace App\Modules\Chain;

use App\Models\BaseModel;
use App\Modules\Chain\HookModule;

/**
 * 统一提供给内部其他模块使用的悬挂链类
 * Class ChainServiceModule
 * @package App\Modules\User
 */
class ChainServiceModule extends BaseModel
{
    /**
     * 钩子-添加钩子
     * @param array $field 表字段
     * @return array
     * */
    public function addHook(array $params)
    {
        $module = new HookModule();
        $result = $module->addHook($params);
        return $result;
    }

    /**
     * 钩子-修改钩子
     * @param array $params
     * @return array
     * */
    public function updateHook(array $params)
    {
        $module = new HookModule();
        $result = $module->updateHook($params);
        return $result;
    }

    /**
     * 钩子-根据条件获取钩子详情
     * @param array $params
     * @param string $field 表字段
     * @return array
     * */
    public function getHookInfoByParams(array $params, $field = '*')
    {
        $module = new HookModule();
        $result = $module->getHookInfoByParams($params, $field);
        return $result;
    }

    /**
     * 钩子-根据钩子id获取钩子详情
     * @param int $hook_id 钩子id
     * @param string $field 表字段
     * @return array
     * */
    public function getHookInfoById(int $hook_id, $field = '*')
    {
        $module = new HookModule();
        $result = $module->getHookInfoById($hook_id, $field);
        return $result;
    }

    /**
     * 钩子-删除钩子
     * @param stirng $hook_id 钩子id,多个钩子用英文逗号分隔
     * @return array
     * */
    public function delHook(string $hook_id)
    {
        $module = new HookModule();
        $result = $module->delHook($hook_id);
        return $result;
    }

    /**
     * 下链口-添加下链口
     * @param array $field 表字段
     * @return array
     * */
    public function addChain(array $params)
    {
        $module = new ChainModule();
        $result = $module->addChain($params);
        return $result;
    }

    /**
     * 下链口-修改下链口
     * @param array $params
     * @return array
     * */
    public function updateChain(array $params)
    {
        $module = new ChainModule();
        $result = $module->updateChain($params);
        return $result;
    }

    /**
     * 下链口-根据条件获取下链口详情
     * @param array $params
     * @param string $field 表字段
     * @return array
     * */
    public function getChainInfoByParams(array $params, $field = '*')
    {
        $module = new ChainModule();
        $result = $module->getChainInfoByParams($params, $field);
        return $result;
    }

    /**
     * 下链口-根据下链口id获取下链口详情
     * @param int $chain_id 下链口id
     * @param string $field 表字段
     * @return array
     * */
    public function getChainInfoById(int $chain_id, $field = '*')
    {
        $module = new ChainModule();
        $result = $module->getChainInfoById($chain_id, $field);
        return $result;
    }

    /**
     * 下链口-删除下链口
     * @param stirng $chain_id 下链口id,多个下链口用英文逗号分隔
     * @return array
     * */
    public function delChain(string $chain_id)
    {
        $module = new ChainModule();
        $result = $module->delChain($chain_id);
        return $result;
    }

    /**
     * 下链口-根据条件获取某个门店的下链口列表
     * @param array $params <p>
     * int shop_id 门店id
     * int status 状态(-1:禁用 1:启用)
     * </p>
     * @param array $sort <p>
     * string field 排序字段名称
     * string value 排序字段值
     * </p>
     * @return array
     * */
    public function getChainListByParams(array $params, array $sort)
    {
        $module = new ChainModule();
        $result = $module->getChainListByParams($params, $sort);
        return $result;
    }

    /**
     * 下链口-增加当前订单量
     * @param int $chain_id 下链口id
     * @param int $num 数量
     * @param object $trans
     * @return array
     * */
    public function setIncChainOrderNum(int $chain_id, int $num, $trans = null)
    {
        $module = new ChainModule();
        $result = $module->setIncChainOrderNum($chain_id, $num, $trans);
        return $result;
    }

    /**
     * 下链口-减少当前订单量
     * @param int $chain_id 下链口id
     * @param int $num 数量
     * @param object $trans
     * @return array
     * */
    public function setDecChainOrderNum(int $chain_id, int $num, $trans = null)
    {
        $module = new ChainModule();
        $result = $module->setDecChainOrderNum($chain_id, $num, $trans);
        return $result;
    }
}
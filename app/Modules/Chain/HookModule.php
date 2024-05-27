<?php

namespace App\Modules\Chain;


use App\Enum\ExceptionCodeEnum;
use App\Models\HookModel;
use App\Models\BaseModel;
use CjsProtocol\LogicResponse;

/**
 * 钩子类
 * Class HookModule
 * @package App\Modules\Chain
 */
class HookModule extends BaseModel
{
    /**
     * 钩子-添加钩子
     * @param array $params 表字段
     * @return array
     * */
    public function addHook($params = array())
    {
        $response = LogicResponse::getInstance();
        $model = new HookModel();
        $save = array(
            'shop_id' => null,
            'hook_code' => null,
            'create_time' => time()
        );
        parm_filter($save, $params);
        $hook_id = $model->add($save);
        if (!$hook_id) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
        }
        $data = array(
            'hook_id' => $hook_id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('添加成功')->toArray();
    }

    /**
     * 钩子-根据条件获取钩子详情
     * @param array $params <p>
     * int hook_id
     * string hook_code
     * </p>
     * @param string $field 表字段
     * @return array
     * */
    public function getHookInfoByParams(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new HookModel();
        $where = array(
            'shop_id' => null,
            'hook_id' => null,
            'hook_code' => null,
            'is_delete' => 0,
        );
        parm_filter($where, $params);
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('钩子不存在')->toArray();
        }
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 钩子-根据钩子id获取钩子详情
     * @param int $hook_id 钩子id
     * @param string $field 表字段
     * @return array
     * */
    public function getHookInfoById(int $hook_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new HookModel();
        $where = array(
            'hook_id' => $hook_id,
            'is_delete' => 0,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('钩子不存在')->toArray();
        }
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 钩子-修改钩子
     * @param array $params 表字段
     * @return array
     * */
    public function updateHook($params = array())
    {
        $response = LogicResponse::getInstance();
        $model = new HookModel();
        $save = array(
            'hook_id' => null,
            'hook_code' => null,
            'update_time' => time()
        );
        parm_filter($save, $params);
        $result = $model->save($save);
        if ($result === false) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改失败')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('修改成功')->toArray();
    }

    /**
     * 钩子-删除钩子
     * @param string $hook_id 多个钩子用英文逗号分隔
     * @return array
     * */
    public function delHook(string $hook_id)
    {
        $response = LogicResponse::getInstance();
        if (empty($hook_id)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('删除失败')->toArray();
        }
        $response = LogicResponse::getInstance();
        $model = new HookModel();
        $where = array(
            'hook_id' => array('IN', $hook_id)
        );
        $save = array(
            'is_delete' => 1,
            'delete_time' => time(),
        );
        $result = $model->where($where)->save($save);
        if ($result === false) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('删除失败')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('删除成功')->toArray();
    }
}
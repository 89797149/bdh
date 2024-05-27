<?php

namespace App\Modules\Chain;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\ChainModel;
use CjsProtocol\LogicResponse;
use Think\Model;

/**
 * 下链口类
 * Class ChainModule
 * @package App\Modules\Chain
 */
class ChainModule extends BaseModel
{
    /**
     * 下链口-添加下链口
     * @param array $params 表字段
     * @return array
     * */
    public function addChain($params = array())
    {
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $save = array(
            'shop_id' => null,
            'chain_code' => null,
            'status' => 1,
            'create_time' => time()
        );
        parm_filter($save, $params);
        $id = $model->add($save);
        if (!$id) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('添加失败')->toArray();
        }
        $data = array(
            'chain_id' => $id
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->setMsg('添加成功')->toArray();
    }

    /**
     * 钩子-根据条件获取钩子详情
     * @param array $params <p>
     * int chain_id 下链口id
     * string chain_code 下链口编码
     * </p>
     * @param string $field 表字段
     * @return array
     * */
    public function getChainInfoByParams(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $where = array(
            'shop_id' => null,
            'chain_id' => null,
            'chain_code' => null,
            'is_delete' => 0,
        );
        parm_filter($where, $params);
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('下链口不存在')->toArray();
        }
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 下链口-根据下链口id获取下链口详情
     * @param int $chain_id 下链口id
     * @param string $field 表字段
     * @return array
     * */
    public function getChainInfoById(int $chain_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $where = array(
            'chain_id' => $chain_id,
            'is_delete' => 0,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('下链口不存在')->toArray();
        }
        $result['create_time'] = date('Y-m-d H:i:s', $result['create_time']);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 下链口-修改下链口
     * @param array $params 表字段
     * @return array
     * */
    public function updateChain($params = array())
    {
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $save = array(
            'chain_id' => null,
            'chain_code' => null,
            'status' => null,
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
     * 下链口-删除下链口
     * @param string $chain_id 多个下链口用英文逗号分隔
     * @return array
     * */
    public function delChain(string $chain_id)
    {
        $response = LogicResponse::getInstance();
        if (empty($chain_id)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('删除失败')->toArray();
        }
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $where = array(
            'chain_id' => array('IN', $chain_id)
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

    /**
     * 获取某个门店的下链口
     * @param array $params <p>
     * int shop_id
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
        $response = LogicResponse::getInstance();
        $model = new ChainModel();
        $where = array(
            'shop_id' => null,
            'status' => null,
            'is_delete' => 0,
        );
        parm_filter($where, $params);
        $order_by = 'chain_id desc';
        if (!empty($sort['field']) && !empty($sort['value'])) {
            $order_by = "{$sort['field']} {$sort['value']}";
        }
        $result = $model->where($where)->order($order_by)->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无可用的下链口')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->setMsg('成功')->toArray();
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
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $chain_model = new ChainModel();
        $where = array(
            'chain_id' => $chain_id
        );
        $result = $chain_model->where($where)->setInc('current_order_num', $num);
        if (!$result) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('当前订单量更新失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('当前订单量更新成功')->toArray();
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
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        $chain_model = new ChainModel();
        $where = array(
            'chain_id' => $chain_id
        );
        $chain_info_result = $this->getChainInfoById($chain_id);
        if ($chain_info_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('请检查下链口是否正常')->toArray();
        }
        $chain_info_data = $chain_info_result['data'];
        if ($chain_info_data['current_order_num'] > 0) {
            $result = $chain_model->where($where)->setDec('current_order_num', $num);
            if (!$result) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('当前订单量更新失败')->toArray();
            }
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('当前订单量更新成功')->toArray();
    }
}
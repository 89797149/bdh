<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 充值金额规则类
 */
class RechargeConfigModel extends BaseModel
{
    /**
     * @param array $params
     * @param $userData
     * @return mixed
     * 新增充值返现规则
     * @param decimal minAmount 最小充值金额
     * @param decimal maxAmount 最大充值金额
     * @param decimal returnAmount 充值返现金额
     */
    public function addRechargeConfig(array $params, $userData)
    {
        $model = M('recharge_config');
        $configlist = $model->where(['dataFlag' => 1])->select();
        foreach ($configlist as $value) {
            $returnRes = checkSection($params['minAmount'], $params['maxAmount'], $value['minAmount'], $value['maxAmount']);
            if ($returnRes) {
                //$msg = '校验失败，新增规则和已有规则id:' . $value['id'] . '的边界发生交集冲突';
                $msg = '校验失败，新增规则和已有规则发生冲突';
                return returnData(null, -1, 'error', $msg);
            }
        }
        $data = [];
        $data['minAmount'] = $params['minAmount'];
        $data['maxAmount'] = $params['maxAmount'];
        $data['returnAmount'] = $params['returnAmount'];
        $res = $this->add($data);
        if (!$res) {
            return returnData(null, -1, 'error', '添加失败');
        }
        $describe = "[{$userData['loginName']}]新增充值返现规则:[最低充值金额{$data['minAmount']},最大充值金额{$data['maxAmount']},充值返现金额{$data['returnAmount']}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
        return returnData(true);
    }

    /**
     * @param array $params
     * @param $userData
     * @return mixed
     * 更新充值返现规则
     * @param int minAmount 充值返现规则id
     * @param decimal minAmount 最小充值金额
     * @param decimal maxAmount 最大充值金额
     * @param decimal returnAmount 充值返现金额
     */
    public function updateRechargeConfig(array $params, $userData)
    {
        $model = M('recharge_config');
        $configlist = $model->where(['dataFlag' => 1])->select();
        foreach ($configlist as $value) {
            $returnRes = checkSection($params['minAmount'], $params['maxAmount'], $value['minAmount'], $value['maxAmount']);
            if ($returnRes) {
                if ($params['id'] == $value['id']) {
                    continue;
                }
                //$msg = '校验失败，新增规则和已有规则id:' . $value['id'] . '的边界发生交集冲突';
                $msg = '校验失败，新增规则和已有规则发生冲突';
                return returnData(null, -1, 'error', $msg);
            }
        }
        $data = [];
        $data['minAmount'] = $params['minAmount'];
        $data['maxAmount'] = $params['maxAmount'];
        $data['returnAmount'] = $params['returnAmount'];
        $res = $this->where(['id' => $params['id']])->save($data);
        if ($res === false) {
            return returnData(null, -1, 'error', '更新失败');
        }
        $describe = "[{$userData['loginName']}]编辑了充值返现规则:[最低充值金额{$data['minAmount']},最大充值金额{$data['maxAmount']},充值返现金额{$data['returnAmount']}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
        $res = (bool)$res;
        return returnData($res);
    }

    /**
     * 获取充值规则详情
     * @param int $id
     */
    public function getRechargeConfigDetail(int $id)
    {
        $where = [];
        $where['id'] = $id;
        $where['dataFlag'] = 1;
        $res = M('recharge_config')->where($where)->find();
        if (empty($res)) {
            $res = [];
        }
        return returnData($res);
    }

    /**
     * 获取充值规则列表
     * @param int $page
     * @param int $pageSize
     */
    public function getRechargeConfigList($page = 1, $pageSize = 15)
    {
        $sql = "SELECT id,minAmount,maxAmount,returnAmount FROM __PREFIX__recharge_config WHERE dataFlag=1 ";
        $sql .= "  order by id desc ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return returnData($rs);
    }

    /**
     * @param int $id
     * @param $userData
     * @return mixed
     * 删除充值规则
     */
    public function delRechargeConfig(int $id, $userData)
    {
        $m = M('recharge_config');
        $where = [];
        $where['id'] = $id;
        $res = $m->where($where)->find();
        if (empty($res)) {
            return returnData(null, -1, 'error', '暂无相关信息');
        }
        $data = [];
        $data['dataFlag'] = -1;
        $rs = $m->where($where)->save($data);
        if (!$rs) {
            return returnData(null, -1, 'error', '删除失败');
        }
        $rs = (bool)$rs;
        $describe = "[{$userData['loginName']}]删除了充值规则:[最低充值金额{$res['minAmount']},最大充值金额{$res['maxAmount']},充值返现金额{$res['returnAmount']}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
        return returnData($rs);
    }
}
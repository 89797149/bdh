<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券服务类
 */
class RechargesetModel extends BaseModel
{
    /**
     * @param $userData
     * @return mixed
     * 新增充值金额配置
     */
    public function insert($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');

        //创建数据
        $data = array();
        if ($this->checkEmpty($data, true)) {
            $data["money"] = I("money");
            $data["sortorder"] = I("sortorder");
            $data['priceFullDiscount'] = I('priceFullDiscount');
            $data["rsFlag"] = 1;
            $rs = M('recharge_set')->add($data);

            if (false !== $rs) {
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]新增了充值金额配置:[{$data['priceFullDiscount']}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return mixed
     * 修改充值金额配置
     */
    public function edit($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id', 0);
        //修改数据
        $data = array();
        if ($this->checkEmpty($data, true)) {
            $data["money"] = I("money");
            $data["sortorder"] = I("sortorder");
            $data['priceFullDiscount'] = I('priceFullDiscount');
            $rs = M('recharge_set')->where("id=" . $id)->save($data);

            if (false !== $rs) {
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]编辑了充值金额配置:[{$data['priceFullDiscount']}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
            }
        }
        return $rd;
    }

    /**
     * 获取配置信息
     */
    public function getRechargeset()
    {
        return M('recharge_set')->where("id=" . (int)I('id'))->find();
    }

    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $sql = "SELECT * FROM __PREFIX__recharge_set WHERE rsFlag=1 ";
//         if(I('money')!='')$sql.=" and money LIKE '%".WSTAddslashes(I('money'))."%'";
        $sql .= "  ORDER BY sortorder desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * @param $userData
     * @return mixed
     * 删除充值金额配置
     */
    public function del($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id');
        $m = M('recharge_set');
        $rechargeSetInfo = $m->where(['id' => $id])->find();
        if (empty($rechargeSetInfo)) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $rs = $m->where("id=" . $id)->save(array('rsFlag' => -1));
        if (false !== $rs) {
            $rd = returnData(true);
            $describe = "[{$userData['loginName']}]删除了充值金额配置:[{$rechargeSetInfo['priceFullDiscount']}]";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
        }
        return $rd;
    }
}
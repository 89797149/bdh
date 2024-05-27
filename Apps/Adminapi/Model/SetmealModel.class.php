<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 套餐服务类
 */
class SetmealModel extends BaseModel
{
    /**
     * @param $userData
     * @return mixed
     * 新增套餐
     */
    public function insert($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');

        //创建数据
        $data = array();
        if ($this->checkEmpty($data, true)) {
            $data["name"] = I("name");
            $data["omoney"] = I("omoney");
            $data["money"] = I("money");
            $data["dayNum"] = I("dayNum", 0);
            $data["isEnable"] = I("isEnable");
            $data["smFlag"] = 1;
            $data['couponId'] = implode(',', json_decode(htmlspecialchars_decode(I("couponId")), true));
            $rs = M('set_meal')->add($data);

            if (false !== $rs) {
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]新增了套餐:[{$data['name']}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return mixed
     * 修改套餐
     */
    public function edit($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id', 0);
        $rest = M('set_meal')->where(['smId' => $id])->find();
        if (empty($rest)) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        //修改数据
        $data = array();
        if ($this->checkEmpty($data, true)) {
            $data["name"] = I("name");
            $data["omoney"] = I("omoney");
            $data["money"] = I("money");
            $data["dayNum"] = I("dayNum");
            $data["isEnable"] = I("isEnable");
            $data['couponId'] = implode(',', json_decode(htmlspecialchars_decode(I("couponId")), true));
            $rs = M('set_meal')->where("smId=" . $id)->save($data);

            if (false !== $rs) {
                $rd = returnData(true);
                $describe = "[{$userData['loginName']}]编辑了套餐:[{$data['name']}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
            }
        }
        return $rd;
    }

    /**
     * 获取套餐信息
     */
    public function getSetmeal()
    {
        $day = date('Y-m-d');
        $where = " shopId = 0 and couponType = 5 and  dataFlag = 1 and type = 1 and validStartTime <= '$day' and validEndTime >= '$day' and sendStartTime <= '$day' and sendEndTime >= '$day'";
        $date = M('set_meal')->where("smId=" . (int)I('id'))->find();
        $sql = "select * from __PREFIX__coupons where {$where} and couponId IN ({$date['couponId']}) ";
        $coupons = $this->query($sql);
        $date['couponsInfo'] = $coupons;
        return $date;
    }

    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $sql = "SELECT * FROM __PREFIX__set_meal  WHERE smFlag=1 ";
        if (I('name') != '') $sql .= " and name LIKE '%" . WSTAddslashes(I('name')) . "%'";
        $sql .= "  ORDER BY smId desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($rs['root'])) {
            foreach ($rs['root'] as $k => $v) {
                $where = [];
                $where['couponId'] = ['IN', $v['couponId']];
                $where['dataFlag'] = 1;
                $couponsList = M('coupons')->where($where)->select();
                $couponsName = array_get_column($couponsList, 'couponName');
                $rs['root'][$k]['couponName'] = implode(',', $couponsName);
            }
        }
        return $rs;
    }

    /**
     * @param $userData
     * @return mixed
     * 删除套餐
     */
    public function del($userData)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id', 0);
        $rest = M('set_meal')->where(['smId' => $id])->find();
        if (empty($rest)) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $id = (int)I('id');
        $m = M('set_meal');
        $rs = $m->where("smId=" . $id)->save(array('smFlag' => -1));
        if (false !== $rs) {
            $rd = returnData(true);
            $describe = "[{$userData['loginName']}]删除了套餐:[{$rest['name']}]";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
        }
        return $rd;
    }
}
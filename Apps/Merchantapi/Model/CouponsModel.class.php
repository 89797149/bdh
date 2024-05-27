<?php

namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券服务类
 */
class CouponsModel extends BaseModel
{

    /**
     * 获取优惠券列表
     */
    public function getlist($parameter = array())
    {
//        $where['dataFlag'] = 1;
//        $where['type'] = 2;
//        $where['shopId'] = $parameter['shopId'];
//        $mod = M('coupons');
//        $data = $mod->where($where)->order('createTime desc')->select();
        $sql = "SELECT * FROM __PREFIX__coupons 
				WHERE dataFlag = 1 AND type=2 AND shopId={$parameter['shopId']} order by createTime desc";
        $data = $this->pageQuery($sql);
        if ($data['root']) {
            $times = date('Y-m-d');
            foreach ($data['root'] as $key => &$value) {
                if ($times >= $value['sendStartTime'] and $times <= $value['sendEndTime']) {
                    $value['issueStatus'] = '发放中';
                } elseif ($times < $value['sendStartTime']) {
                    $value['issueStatus'] = '未开始';
                } elseif ($times > $value['sendEndTime']) {
                    $value['issueStatus'] = '已经结束';
                } else {
                    $value['issueStatus'] = '';
                }
            }

            //获取权限信息
            get_couponsList_auth($data['root']);
        }
        return $data;
    }


    /**
     * 删除优惠券
     */
    public function deleteYhq($parameter = array())
    {
        $id = $parameter['couponId'] ? $parameter['couponId'] : 0;
        $where['couponId'] = $id;
        $where['type'] = 2;
        $where['shopId'] = $parameter['shopId'];
        $data['dataFlag'] = -1;
        $res = M('coupons')->where($where)->save($data);
        if (!$res) {
            return returnData(false, -1, 'error', '删除失败');
        }
        return returnData(true);
    }


    /**
     * 添加优惠券
     */
    public function addYhq($funData)
    {
        $rd['status'] = -1;
        $funData['type'] = 2;
        $mod = M('coupons');
        $authjson = $funData['authjson'];
        unset($funData['authjson']);
        M()->startTrans();//开启事物
        $res = $mod->add($funData);
        if (!$res) {
            $rd['status'] = -1;
            return $rd;
        }
        $authRes = true;
        if ($res && $authjson) {
            $authRes = $this->saveauth($res, $authjson);
        }
        if (!$authRes) {
            $rd['status'] = -2;
            $rd['msg'] = '权限保存失败';
            M()->rollback();
            return $rd;
        }

        $rd['status'] = 1;
        M()->commit();
        return $rd;
    }

    //权限保存
    public function saveauth($coupon_id, $authjson = '')
    {
        $authjson = str_replace("&quot;", '"', $authjson);
        $auth_arr = json_decode($authjson, 1);
        if (!$auth_arr || !$coupon_id) {
            return false;
        }
        $am = M('coupons_auth');
        //清除垃圾数据
        $am->where('couponId=' . $coupon_id)->save(array('state' => -1));
        $res = false;
        foreach ($auth_arr as $value) {
            $toidarr = explode(',', $value['toid']);
            if (!$toidarr || !in_array($value['type'], array(1, 2))) {
                continue;
            }
            foreach ($toidarr as $toid) {
                $saveData = array(
                    'couponId' => $coupon_id,
                    'type' => $value['type'],
                    'toid' => $toid,
                    'state' => 1,
                );
                $res = $am->add($saveData);
            }

        }

        return $res;

    }


}

;
?>
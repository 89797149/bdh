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
class CouponsModel extends BaseModel
{

    /**
     * 获取优惠券列表
     */
    public function get()
    {
        $where['dataFlag'] = 1;
        $where['type'] = 1;
        $couponType = I('couponType'); //优惠券类型[1：满减 2：新人专享（仅限总后台使用） 3：邀请好友（仅限总后台使用）,4:店铺兑换券 5:会员专享,6:邀请开通会员 ,7:赠送劵(仅限用户唤醒使用),8:运费券]
        $sendNum = I('sendNum', -1); //发放数量
        $receiveNum = I('receiveNum', -1); //领取数量
        $issueType = I('issueType'); //发放状态[1:发放中 2:已结束]
        if (!empty($couponType)) {
            $where['couponType'] = $couponType;
        }
        if ($sendNum != -1) {
            $where['sendNum'] = $sendNum;
        }
        if ($receiveNum != -1) {
            $where['receiveNum'] = $receiveNum;
        }
        $mod = M('coupons');
        $data = $mod->where($where)->order('createTime desc')->select();
        $res = [];
        foreach ($data as $v) {
            $dateTime = strtotime(date("Y-m-d"));
            if ($dateTime < strtotime($v['sendStartTime']) || $dateTime > strtotime($v['sendEndTime'])) {
                $v['issueType'] = 2;
            } else {
                $v['issueType'] = 1;
            }
            if (!empty($issueType)) {
                if ($v['issueType'] == $issueType) {
                    $res[] = $v;
                }
            } else {
                $res[] = $v;
            }
        }
        //获取权限信息
        get_couponsList_auth($res);
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $res = arrayPage($res, $page, $pageSize);
        return (array)$res;
    }

    /**
     * @param $id
     * @return mixed
     * 删除优惠券
     */
    public function deleteYhq($id)
    {
        $rd = returnData(false, -1, 'error', '操作失败');
        $where['couponId'] = $id;
        $data['dataFlag'] = -1;
        $res = M('coupons')->where($where)->save($data);
        if ($res) {
            $rd = returnData(true, 0, 'success', '操作成功');
        }
        return $rd;
    }

    /**
     * @param $funData
     * @param $userData
     * @return mixed
     * 添加优惠券
     */
    public function addYhq($funData, $userData)
    {
        $mod = M('coupons');
        $authjson = $funData['authjson'];
        unset($funData['authjson']);
        M()->startTrans(); //开启事物
        $res = $mod->add($funData);
        if (!$res) {
            M()->rollback();
            return returnData(false, -1, 'error', '添加失败');
        }
        $authRes = true;
        if ($res && $authjson) {
            $authRes = $this->saveauth($res, $authjson);
        }
        if (!$authRes) {
            M()->rollback();
            return returnData(false, -1, 'error', '权限保存失败');
        }

        M()->commit();
        $describe = "[{$userData['loginName']}]新增了优惠券:[{$funData['couponName']}]";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
        return returnData(true, 0, 'success', '操作成功');
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
            if (!$toidarr || !in_array($value['type'], array(1, 2, 3))) {
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

    /**
     * 获得通用优惠券
     */
    public function getCouponList()
    {
        $where = array(
            'shopId' => 0,
            'couponType' => 5,
            'validStartTime' => array('ELT', date('Y-m-d')),
            'validEndTime' => array('EGT', date('Y-m-d')),
            'dataFlag' => 1,
            'type' => 1
        );
        return (array)M('coupons')->where($where)->order('createTime desc')->Field('couponId,couponName')->select();
    }

    /**
     * 获得商城满减优惠券列表
     */
    public function getMallCouponList()
    {
        $where = array(
            'shopId' => 0,
            'couponType' => 1,
            'sendStartTime' => array('ELT', date('Y-m-d')),
            'sendEndTime' => array('EGT', date('Y-m-d')),
            'dataFlag' => 1,
            'type' => 1
        );
        return (array)M('coupons')->where($where)->order('createTime desc')->field('couponId,couponName')->select();
    }

    /**
     * 唤醒赠送劵
     */
    public function getPresentedList()
    {
        $where = array(
            'shopId' => 0,
            'couponType' => 7,
            'sendStartTime' => array('ELT', date('Y-m-d')),
            'sendEndTime' => array('EGT', date('Y-m-d')),
            'dataFlag' => 1,
            'type' => 1
        );
        return (array)M('coupons')->where($where)->order('createTime desc')->field('couponId,couponName')->select();
    }

    /**
     * 优惠券编辑
     * @param array $params <p>
     * int couponId 优惠券id
     * date sendEndTime 发放结束时间
     * date validEndTime 活动结束时间
     * </p>
     * @return json
     */
    public function updateCoupon(array $params)
    {
        $coupon_model = new CouponsModel();
        $coupon_id = (int)$params['couponId'];
        $where = array(
            'couponId' => $coupon_id,
            'dataFlag' => 1
        );
        $coupon_info = $coupon_model->where($where)->find();
        if (empty($coupon_info)) {
            return returnData(false, -1, 'error', '修改失败，未匹配到相关的优惠券');
        }
        if (strtotime($params['sendStartTime']) > strtotime($params['sendEndTime'])) {
            return returnData(false, -1, 'error', '发放结束时间不能小于发放开始时间');
        }
        if (strtotime($params['validStartTime']) > strtotime($params['validEndTime'])) {
            return returnData(false, -1, 'error', '活动结束时间不能小于活动开始时间');
        }
        $save = array(
            'couponId' => null,
            'sendEndTime' => null,
            'validEndTime' => null
        );
        parm_filter($save, $params);
        $res = $coupon_model->save($save);
        if ($res === false) {
            return returnData(false, -1, 'error', '修改失败');
        }
        //修改领取后的优惠券过期时间-start
        //sb
        if (!empty($params['received_delay'])) {
            $coupons_users_model = M('coupons_users');
            $delay_where = array(
                'couponId' => $coupon_id
            );
            $delay_save = array(
                'couponExpireTime' => $params['received_delay'] . ' 23:59:59'
            );
            $coupons_users_model->where($delay_where)->save($delay_save);
        }
        //修改领取后的优惠券过期时间-end
        return returnData(true);
    }

}

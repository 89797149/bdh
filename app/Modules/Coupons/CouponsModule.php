<?php

namespace App\Modules\Coupons;

use App\Enum\ExceptionCodeEnum;
use App\Models\CouponsModel;
use App\Models\BaseModel;
use App\Models\CouponsUsersModel;
use Think\Model;

/**
 * 优惠券类
 * Class CouponsModule
 * @package App\Modules\Coupons
 */
class CouponsModule extends BaseModel
{
    /**
     * 根据优惠券id获取优惠券详情
     * @param int $coupon_id 优惠券id
     * @return array
     * */
    public function getCouponDetailById(int $coupon_id, $field = '*')
    {
        $model = new CouponsModel();
        $detail = $model->where(array(
            'couponId' => $coupon_id,
            'dataFlag' => 1
        ))->field($field)->find();
        return (array)$detail;
    }

    /**
     * 保存用户优惠券信息
     * @param array $params <p>
     * int id 用户领取的优惠券记录id
     * int couponId 优惠券id
     * int userId 用户id
     * datetime receiveTime 领取时间
     * int couponStatus 使用状态1:未用，0：已用 -1:删除
     * int dataFlag 有效状态(1:有效 -1:删除（冻结）  （暂时依赖关联查询判断是否有效）)
     * string orderNo 订单号
     * int ucouponId 优惠券id（升级后的店铺优惠券ID）
     * datetime couponExpireTime 优惠券过期时间
     * int userToId 被邀请人id
     * </p>
     * @param object $trans
     * @return int $id
     * */
    public function saveUsersCoupon(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'couponId' => null,
            'userId' => null,
            'receiveTime' => null,
            'couponStatus' => null,
            'dataFlag' => null,
            'orderNo' => null,
            'ucouponId' => null,
            'couponExpireTime' => null,
            'userToId' => null,
        );
        parm_filter($save, $params);
        $model = new CouponsUsersModel();
        if (empty($params['id'])) {
            $res = $model->add($save);
            $id = $res;
        } else {
            $res = $model->where(array(
                'id' => $params['id']
            ))->save($save);
            $id = $params['id'];
        }
        if ($res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$id;
    }

    /**
     * 用户优惠券列表-已领取(未使用未过期)
     * @param int $users_id 用户id
     * @return array
     * */
    public function getUserNotExpiredCouponList(int $users_id)
    {
        if (empty($users_id)) {
            return array();
        }
        $coupon_model = new CouponsModel();
        $coupon_user_model = new CouponsUsersModel();
        $cu_where = array(
            'userId' => $users_id,
            'dataFlag' => 1,
            'couponStatus' => 1,
            'couponExpireTime' => array('EGT', date('Y-m-d H:i:s'))
        );
        $coupon_user_list = $coupon_user_model->where($cu_where)->select();
        if (empty($coupon_user_list)) {
            return array();
        }
        $coupon_id_arr = array_unique(array_column($coupon_user_list, 'couponId'));
        $c_where = array(
            'couponId' => array('IN', $coupon_id_arr),
            'dataFlag' => 1
        );
        $coupon_list = $coupon_model->where($c_where)->select();
        $can_use_coupons = array();
        $datetime = date('Y-m-d H:i:s');
        foreach ($coupon_user_list as $item) {
            foreach ($coupon_list as $val) {
                if ($val['couponId'] == $item['couponId'] && $datetime >= $val['validStartTime']) {
                    $can_use_info = $item;
                    $can_use_info['shopId'] = $val['couponId'];
                    $can_use_info['couponName'] = $val['couponName'];
                    $can_use_info['couponType'] = $val['couponType'];
                    $can_use_info['couponMoney'] = $val['couponMoney'];
                    $can_use_info['spendMoney'] = $val['spendMoney'];
                    $can_use_info['couponDes'] = $val['couponDes'];
                    $can_use_info['sendStartTime'] = $val['sendStartTime'];
                    $can_use_info['sendEndTime'] = $val['sendEndTime'];
                    $can_use_info['validStartTime'] = $val['validStartTime'];
//                    $can_use_info['validEndTime'] = $val['validEndTime'];
                    $can_use_info["validEndTime"] = $item["couponExpireTime"]; //这里后端直接临时改成用户券的过期时间，就不让前端改了
                    $can_use_info['type'] = $val['type'];
                    $can_use_info['commonCouponMoney'] = $val['commonCouponMoney'];
                    $can_use_info['expireDays'] = $val['expireDays'];
                    $can_use_coupons[] = $can_use_info;
                }
            }
        }
        return (array)$can_use_coupons;
    }

    /**
     * 获取用户优惠券详情
     * @param user_coupon_id 用户领取的优惠券记录id
     * @return array
     * */
    public function getUserCouponDetail(int $user_coupon_id)
    {
        $coupon_user_model = new CouponsUsersModel();
        $detail = $coupon_user_model->where(array(
            'id' => $user_coupon_id
        ))->find();
        if (empty($detail)) {
            return array();
        }
        $coupon_id = $detail['couponId'];
        $coupon_detail = $this->getCouponDetailById($coupon_id);
        if (empty($coupon_detail)) {
            return array();
        }
        $detail['coupon_detail'] = $coupon_detail;
        return (array)$detail;
    }

    /**
     * 优惠券用户关联-详情
     * @param int $couponId 优惠券id
     * @param int $userId 用户id
     * @return array
     * */
    public function getCouponsUsersDetailByParams(int $couponId, int $userId)
    {
        $model = new CouponsUsersModel();
        $detail = $model->where(array(
            'couponId' => $couponId,
            'userId' => $userId
        ))->find();
        if (empty($detail)) {
            return array();
        }
        $coupon_id = $detail['couponId'];
        $coupon_detail = $this->getCouponDetailById($coupon_id);
        if (empty($coupon_detail)) {
            return array();
        }
        $detail['coupon_detail'] = $coupon_detail;
        return (array)$detail;
    }

    /**
     * 校验优惠券的可用性
     * @param int $user_coupon_id 用户领取的优惠券记录id
     * @return bool
     * */
    public function verificationUserCoupon(int $user_coupon_id)
    {
        if (empty($user_coupon_id)) {
            return false;
        }
        $coupon_user_detail = $this->getUserCouponDetail($user_coupon_id);
        if (empty($coupon_user_detail)) {
            return false;
        }
        $users_id = $coupon_user_detail['userId'];
        $cau_use_coupons = $this->getUserNotExpiredCouponList($users_id);
        $all_user_coupon_Id = array_column($cau_use_coupons, 'id');
        if (!in_array($user_coupon_id, $all_user_coupon_Id)) {
            return false;
        }
        return true;
    }

    /**
     * 优惠券列表-多条件查找
     * @param array $params
     * -int couponType 优惠券类型[1：满减 2：新人专享（仅限总后台使用） 3：邀请好友（仅限总后台使用）,4:店铺兑换券 5:会员专享,6:邀请开通会员 ,7:赠送劵(仅限用户唤醒使用),8:运费券]
     * -datetime sendStartTime 发放开始时间
     * -datetime sendEndTime 发放结束时间
     * @param string $field 表字段
     * @return array
     * */
    public function getCouponListByParams(array $params, $field = '*')
    {
        $where = array(
            'dataFlag' => 1,
            'couponType' => null,
            'sendStartTime' => null,
            'sendEndTime' => null,
        );
        parm_filter($where, $params);
        $model = new CouponsModel();
        $result = $model->where($where)->field($field)->select();
        return (array)$result;
    }

    /**
     * PS:该逻辑复制V3/ApiModel中的okCoupons方法,不清楚或看不懂的自己先去看原有方法
     * 优惠券-领取优惠券
     * @param int $userId 用户id
     * @param int $couponId 优惠券id
     * @param int $couponType 优惠券类型[1：满减 2：新人专享（仅限总后台使用） 3：邀请好友（仅限总后台使用）,4:店铺兑换券 5:会员专享,6:邀请开通会员 ,7:赠送劵(仅限用户唤醒使用),8:运费券]
     * @param int $userToId 受邀人id
     * @return array
     * */
    public function okCoupons($userId, $couponId, $couponType = 1, $userToId = 0)
    {
        //该逻辑里面不要添加事务
        $couponDetail = $this->getCouponDetailById($couponId);
        if (empty($couponDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券不存在');
        }
        if ($couponDetail['couponType'] != $couponType) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券类型不匹配');
        }
        if (!in_array($couponDetail['couponType'], array(3, 6, 8))) {
            $where = array();
            $where['couponId'] = $couponId;
            $where['userId'] = $userId;
            $couponsUsersDetail = $this->getCouponsUsersDetailByParams($couponId, $userId);
            if (empty($couponsUsersDetail)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券已经领取了');
            }
        }
        $date = date('Y-m-d');
        if (!($couponDetail['sendStartTime'] <= $date && $couponDetail['sendEndTime'] >= $date)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券已发放结束');
        }
        if ($couponDetail["validEndTime"] . " 23:59:59" <= $date) {
            return returnData(null, ExceptionCodeEnum::FAIL, 'error', '优惠券活动时间已结束');
        }
        if ($couponDetail['sendNum'] > 0) {//是否是不限数量 大于0为限制数量 小于0为不限数量
            $receiveNum = $couponDetail['receiveNum'];//
            if (($receiveNum + 1) > $couponDetail['sendNum']) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券数量不足');
            }
        }
        $ouponsUsersModel = new CouponsUsersModel();
        $saveData = array();
        $saveData['couponId'] = $couponId;
        $saveData['userId'] = $userId;
        $saveData['userToId'] = $userToId;
        $saveData['receiveTime'] = date('Y-m-d H:i:s');
        $saveData['couponExpireTime'] = calculationTime(date('Y-m-d H:i:s'), $couponDetail['expireDays']);
        if ($saveData['couponExpireTime'] > $couponDetail["validEndTime"]) {//已领券的过期时间不能大于活动结束时间
            $saveData["couponExpireTime"] = $couponDetail["validEndTime"] . " 23:59:59";
        }
        if (!$ouponsUsersModel->add($saveData)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '优惠券领取失败');
        }
        M('coupons')->where('couponId =' . $couponId)->setInc('receiveNum', 1);
        return returnData(true);
    }
}
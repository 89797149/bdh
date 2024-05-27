<?php

namespace App\Modules\Users;


use App\Enum\ExceptionCodeEnum;
use App\Models\InviteCacheRecordModel;
use App\Models\InvoiceModel;
use App\Models\PullNewAmountLogModel;
use App\Models\UserAddressModel;
use App\Models\UserBalanceModel;
use App\Models\UserScoreModel;
use App\Models\UsersModel;
use App\Models\BaseModel;
use App\Modules\Areas\AreasModule;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;

/**
 * 移动终端用户类,该类只为UserServiceModule类服务
 * Class UsersModule
 * @package App\Modules\Users
 */
class UsersModule extends BaseModel
{
    /**
     * 根据用户id获取用户详情
     * @param int $userId 用户id
     * @param string $field 表字段
     * @param int $data_type (1:返回data格式 2:直接返回结果集) 主要是兼容之前的程序
     * @return array
     * */
    public function getUsersDetailById(int $userId, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        $where = array(
            'userId' => $userId,
            'userFlag' => 1
        );
        $result = $users_model->where($where)->field($field)->find();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户不存在')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
        } else {
            return (array)$result;
        }

    }

    /**
     * 根据参数获取用户详情
     * @param array $params <p>
     * int userId 用户id
     * string loginName 用户登陆名
     * string loginPwd 登陆密码
     * string WxUnionid 微信unionid
     * </p>
     * @return array
     * */
    public function getUsersDetailByWhere(array $params, $field = '*', $data_type = 1)
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        $where = array(
            'userId' => null,
            'loginName' => null,
            'loginPwd' => null,
            'WxUnionid' => null,
            'userFlag' => 1
        );
        parm_filter($where, $params);
        $result = (array)$users_model->where($where)->field($field)->find();
        if (empty($result)) {
            if ($data_type == 1) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户不存在')->toArray();
            } else {
                return array();
            }
        }
        if ($data_type == 1) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
        } else {
            return $result;
        }
    }

    /**
     * 获取受邀详情
     * @param array $params <p>
     * int id 邀请记录id
     * int inviterId 邀请人id
     * string inviterPhone 邀请人手机号
     * string inviteePhone 受邀人手机号
     * </p>
     * */
    public function getRecordInfoByWhere(array $params, $field = '')
    {
        $response = LogicResponse::getInstance();
        $invite_cache_record_model = new InviteCacheRecordModel();
        $where = array(
            'id' => null,
            'inviterId' => null,
            'inviterPhone' => null,
            'inviteePhone' => null,
            'icrFlag' => 1,
        );
        parm_filter($where, $params);
        $result = (array)$invite_cache_record_model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 修改用户信息 PS:该方法谁用谁扩展字段
     * @param int $userId
     * @param array $save 表字段
     * @param object $trans 事务M()
     * @return array
     * */
    public function updateUsersInfo(int $userId, $save = array(), $trans = null)
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        if (empty($save)) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('参数错误')->toArray();
        }
        $where = array(
            'userId' => $userId
        );
        $result = $users_model->where($where)->save($save);
        if ((bool)$result === false) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 根据memberToken获取用户信息
     * */
    public function getUsersInfoByMemberToken()
    {
        $response = LogicResponse::getInstance();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (empty($memberToken)) {
            $memberToken = $headers['Membertoken'];
        }
        if (empty($memberToken)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('MemberToken失效')->toArray();
        }
        $users_info = userTokenFind($memberToken, 86400 * 30);//查询token
        if (empty($users_info['userId'])) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($users_info)->toArray();
    }

    /**
     * 返还用户积分
     * @param int $users_id 用户id
     * @param int $score 返还的积分
     * @return array
     * */
    public function return_users_score(int $users_id, int $score, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $response = LogicResponse::getInstance();
        if ($score <= 0) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('传入的积分有误')->toArray();
        }
        $reset_result = $this->reset_user_score($users_id, $m);
        if ($reset_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户积分重置失败')->toArray();
        }
        $users_model = new UsersModel();
        $where = array(
            'userId' => $users_id,
        );
        $update_res = $users_model->where($where)->setInc('userScore', $score);
        if (!$update_res) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('积分返还失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }


    /**
     * 返还用户积分
     * @param int $users_id 用户id
     * @param int $score 返还的积分
     * @return bool
     * */
    public function incUserScore(int $users_id, int $score, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if ($score <= 0) {
            $dbTrans->rollback();
            return false;
        }
        $model = new UsersModel();
        $where = array(
            'userId' => $users_id,
        );
        $update_res = $model->where($where)->setInc('userScore', $score);
        if (!$update_res) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }


    /**
     * @param $startTime
     * @param $endTime
     * @return array
     * 根据时间区间获取新增用户数量
     */
    public function getNewUsersCount($startTime = '', $endTime = '')
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        $where = [];
        $where['userFlag'] = 1;
        if (!empty($startTime) and !empty($endTime)) {
            $where['createTime'] = ['between', [$startTime, $endTime]];
        }
        $usersCount = $users_model->where($where)->count();

        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((int)$usersCount)->toArray();
    }

    /**
     * 扣除用户积分
     * @param int $users_id 用户id
     * @param int $score 扣除的积分
     * @param object $trans
     * @return array
     * */
    public function deduction_users_score(int $users_id, int $score, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $reset_result = $this->reset_user_score($users_id, $m);
        if ($reset_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户积分重置失败')->toArray();
        }
        $users_model = new UsersModel();
        $where = array(
            'userId' => $users_id,
        );
        if ($score <= 0) {
            $score = 0;
        }
        $update_res = $users_model->where($where)->setDec('userScore', $score);
        if ($update_res === false) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('积分扣除失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }

    /**
     * 重置用户积分-用户积分为负数时重置为0
     * @param int $users_id 用户id
     * @return array
     * */
    public function reset_user_score(int $users_id, $trans = null)
    {
        $response = LogicResponse::getInstance();
        $user_result = $this->getUsersDetailById($users_id);
        if ($user_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户信息有误')->toArray();
        }
        $user_info = $user_result['data'];
        if ($user_info['userScore'] >= 0) {
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
        }
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $users_model = new UsersModel();
        $where = array(
            'userId' => $users_id,
        );
        $save = array(
            'userScore' => 0
        );
        $update_res = $users_model->where($where)->save($save);
        if (!$update_res) {
            $m->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户积分重置失败')->toArray();
        }
        if (empty($trans)) {
            $m->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->toArray();
    }


    /**
     * 检测用户是否是正常未过期的付费会员
     * return true可用|false不可用
     */
    public function isPayingMembers(int $users_id = null)
    {
        if (empty($users_id)) {
            return false;
        }

        $where['userId'] = $users_id;
        $where['userStatus'] = 1;//账号状态1:启用
        $where['userFlag'] = 1;//删除标志1:有效
        $where['expireTime'] = array("EGT", date("Y-m-d H:i:s"));//大于等于当前时间
        $userData = (new UsersModel())->where($where)->find();
        if (!empty($userData)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断用户余额是否充足
     * @param int $users_id
     * @param float $amount 需要扣除的金额
     * @return bool
     * */
    public function isAdequateBalance(int $users_id, $amount)
    {
        $field = 'userId,balance';
        $users_detail = $this->getUsersDetailById($users_id, $field, 2);
        $balance = (int)(bc_math($users_detail['balance'], 100, 'bcmul', 2));
        if ($balance <= 0) {
            return false;
        }
        $amount = (int)(bc_math($amount, 100, 'bcmul', 2));
        if ($balance < $amount) {
            return false;
        }
        return true;
    }

    /**
     * 扣除用户余额
     * @param int $users_id 用户id
     * @param float $amount 需要扣除的金额
     * @param object $trans
     * @return bool
     * */
    public function deductionUsersBalance(int $users_id, $amount, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $field = 'userId,balance';
        $users_detail = $this->getUsersDetailById($users_id, $field, 2);
        $balance = (int)(bc_math($users_detail['balance'], 100, 'bcmul', 2));
        if ($balance <= 0) {
            $m->rollback();
            return false;
        }
        $tempAmount = (int)(bc_math($amount, 100, 'bcmul', 2));
        if ($balance < $tempAmount) {
            $m->rollback();
            return false;
        }
        $users_module = new UsersModel();
        $save_res = $users_module->where(array(
            'userId' => $users_id
        ))->setDec('balance', $amount);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 增加用户余额
     * @param int $users_id 用户id
     * @param float $amount 需要扣除的金额
     * @param object $trans
     * @return bool
     * */
    public function incUsersBalance(int $users_id, $amount, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $users_module = new UsersModel();
        $save_res = $users_module->where(array(
            'userId' => $users_id
        ))->setInc('balance', $amount);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 添加用户余额流水
     * @param array $params <p>
     * int userId 用户id
     * float balance 金额
     * int dataSrc 来源(1：线上 2：线下)
     * string orderNo 订单号
     * string dataRemarks 描述/备注
     * int balanceType 余额标识(1:收入 2：支出)
     * int shopId 门店id
     * int actionUserId 操作者id
     * </p>
     * @param object $trans
     * @return bool
     * */
    public function addUserBalance(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'userId' => null,
            'balance' => null,
            'dataSrc' => null,
            'orderNo' => '',
            'dataRemarks' => null,
            'balanceType' => null,
            'shopId' => 0,
            'actionUserId' => null,
            'createTime' => date('Y-m-d H:i:s')
        );
        parm_filter($save, $params);
        $model = new UserBalanceModel();
        $save_res = $model->add($save);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 用户余额流水详情-根据id查找
     * @param int $balanceId 余额流水id
     * @param string $field 表字段
     * @return array
     * */
    public function getUserBalanceDetailById(int $balanceId, $field = '*')
    {
        $user_balance_model = new UserBalanceModel();
        $detail = $user_balance_model->where(array(
            'balanceId' => $balanceId,
        ))->field($field)->find();
        return (array)$detail;
    }

    /**
     * 添加用户积分流水
     * @param array $params <p>
     * int userId 用户ID
     * int score 积分数
     * int dataSrc 来源(1：订单 2:评价 3：订单取消返还 4：拒收返还 5.app签到 6.小程序签到获取  7：抽奖获得 8：小程序邀请好友获得 9：app邀请好友获得 10:小程序新人专享大礼 11：app新人专享大礼 12：线下门店消费 13:反馈 14:门店赠送【如:用户唤醒】)
     * int dataId 来源记录ID
     * string dataRemarks 描述
     * int scoreType 积分标识(1:收入 2：支出)
     * </p>
     * @param object $trans
     * @return bool
     * */
    public function addUserScore(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'userId' => null,
            'score' => null,
            'dataSrc' => null,
            'dataId' => null,
            'dataRemarks' => null,
            'scoreType' => null,
            'createTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($save, $params);
        $mode = new UserScoreModel();
        $save_res = $mode->add($save);
        if (!$save_res) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 用户收货地址-详情
     * @param int $users_id 用户id
     * @param int $address_id PS:地址id为0取默认地址
     * @return array
     * */
    public function getUserAddressDetail(int $users_id, $address_id = 0)
    {
        $where = array(
            'userId' => $users_id,
            'addressFlag' => 1,
        );
        if ($address_id <= 0) {
            $where['isDefault'] = 1;
        } else {
            $where['addressId'] = $address_id;
        }
        $model = new UserAddressModel();
        $detail = $model->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        $detail['detailAddress'] = '';
        $area_module = new AreasModule();
        $areaIdArr = [];
        if ($detail['areaId1'] > 0) {
            $areaIdArr[] = $detail['areaId1'];
        }
        if ($detail['areaId2'] > 0) {
            $areaIdArr[] = $detail['areaId2'];
        }
        if ($detail['areaId3'] > 0) {
            $areaIdArr[] = $detail['areaId3'];
        }
        $area_list_map = [];
        if (!empty($areaIdArr)) {
            $area_list = $area_module->getAreaListByIdArr($areaIdArr);
            foreach ($area_list as $area_list_row) {
                $area_list_map[$area_list_row['areaId']] = $area_list_row;
            }
        }
//        $detail['areaId1Name'] = (string)$area_module->getAreaDetailById($detail['areaId1'])['areaName'];
//        $detail['areaId2Name'] = (string)$area_module->getAreaDetailById($detail['areaId2'])['areaName'];
//        $detail['areaId3Name'] = (string)$area_module->getAreaDetailById($detail['areaId3'])['areaName'];
        $detail['areaId1Name'] = (string)$area_list_map[$detail['areaId1']]['areaName'];
        $detail['areaId2Name'] = (string)$area_list_map[$detail['areaId2']]['areaName'];
        $detail['areaId3Name'] = (string)$area_list_map[$detail['areaId3']]['areaName'];
        if (handleCity($detail['areaId1Name'])) {
            $detail['detailAddress'] .= $detail['areaId1Name'];
        }
        $detail['detailAddress'] .= $detail['areaId2Name'] . $detail['areaId3Name'];
        $del_str = $detail['detailAddress'];
        if (!empty($detail['setaddress'])) {
            $detail['detailAddress'] .= $detail['setaddress'];
        }
        $detail['detailAddress'] .= $detail['address'];
        $detailAddressArr = explode($del_str, $detail['detailAddress']);
        if (count($detailAddressArr) > 2) {
            $detail['detailAddress'] = $del_str . $detailAddressArr[2];
        }
        if (!empty($detail['setaddress'])) {
            $detail['detailAddress'] = $detail['setaddress'] . $detail['address'];
        }
        return (array)$detail;
    }

    /**
     * 用户收货地址-列表
     * @param int $userId 用户id
     * @return array
     * */
    public function getUserAddressList(int $userId)
    {
        $model = new UserAddressModel();
        $where = array(
            'userId' => $userId,
            'addressFlag' => 1,
        );
        $addressList = $model->where($where)->field('addressId')->select();
        $result = array();
        foreach ($addressList as $item) {
            $addressDetail = $this->getUserAddressDetail($userId, $item['addressId']);
            $result[] = $addressDetail;
        }
        return $result;
    }

    /**
     * 用户收货地址-保存
     * @param array $params
     * -wst_user_address表字段
     * @return int
     * */
    public function saveUserAddress(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new UserAddressModel();
        $saveParams = array(
            'userId' => null,
            'userName' => null,
            'userPhone' => null,
            'userTel' => null,
            'areaId1' => null,
            'areaId2' => null,
            'areaId3' => null,
            'communityId' => null,
            'address' => null,
            'postCode' => null,
            'isDefault' => null,
            'addressFlag' => null,
            'lat' => null,
            'lng' => null,
            'setaddress' => null,
        );
        parm_filter($saveParams, $params);
        if (empty($params['addressId'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $addressId = $model->add($saveParams);
            if (empty($addressId)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $addressId = $params['addressId'];
            $saveRes = $model->where(array('addressId' => $addressId))->save($saveParams);
            if ($saveRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 获取用户发票详情
     * @param int $users_id 用户id
     * @param int $invoice_id 发票id
     * @param string $field 表字段
     * @return array
     * */
    public function getUserInvoiceDetail(int $users_id, int $invoice_id, $field)
    {
        $invoice_model = new InvoiceModel();
        $detail = $invoice_model->where(array(
            'id' => $invoice_id,
            'userId' => $users_id
        ))->field($field)->find();
        return (array)$detail;
    }

    /**
     * 获取用户发票信息列表
     * @param int $users_id 用户id
     * @return array
     * */
    public function getUserInvoiceList(int $users_id)
    {
        $invoice_tab = new InvoiceModel();
        $list = $invoice_tab->where(array(
            'userId' => $users_id
        ))->order('id desc')->select();
        return (array)$list;
    }

    /**
     * 保存地推收益明细记录
     * @param array $params <p>
     * int id 记录id
     * int userId 用户id
     * int inviterId 邀请人id
     * int dataType 数据类型【1：邀请成功注册|2：成功下单】
     * string orderToken 合并订单标识
     * float amount 奖励金额
     * int status 结算状态【0：待结算|1：已结算|2：已取消】
     * </p>
     * @param object $trans
     * @return int
     * */
    public function savePullNewAmountLog(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $date = date('Y-m-d H:i:s', time());
        $save_data = array(
            'userId' => null,
            'inviterId' => null,
            'dataType' => null,
            'orderToken' => null,
            'amount' => null,
            'amount' => null,
            'status' => null,
            'updateTime' => $date,
        );
        parm_filter($save_data, $params);
        $model = new PullNewAmountLogModel();
        if (empty($params['id'])) {
            $save_data['createTime'] = $date;
            $save_res = $model->add($save_data);
            $id = $save_res;
        } else {
            $save_res = $model->where(array(
                'id' => $params['id']
            ))->save($save_data);
            $id = $params['id'];
        }
        if ($save_res === false) {
            $m->rollback();
            return 0;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return (int)$id;
    }

    /**
     * 用户已欠款额度-递增
     * @param int $userId 用户id
     * @param float $money 金额
     * @param object $trans
     * @return bool
     * */
    public function incQuotaArrears(int $userId, float $money, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if ($money < 0) {
            $dbTrans->rollback();
            return false;
        }
        $model = new UsersModel();
        $res = $model->where(array('userId' => $userId))->setInc('quota_arrears', $money);
        if ($res === false) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 用户已欠款额度-递减
     * @param int $userId 用户id
     * @param float $money 金额
     * @param object $trans
     * @return bool
     * */
    public function decQuotaArrears(int $userId, float $money, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if ($money < 0) {
            $dbTrans->rollback();
            return false;
        }
        $model = new UsersModel();
        $res = $model->where(array('userId' => $userId))->setDec('quota_arrears', $money);
        if ($res === false) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 用户分销金额-递增
     * @param int $userId 用户id
     * @param float $money 金额
     * @param object $trans
     * @return bool
     * */
    public function incDistributionMoney(int $userId, float $money, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if ($money < 0) {
            $dbTrans->rollback();
            return false;
        }
        $model = new UsersModel();
        $res = $model->where(array('userId' => $userId))->setInc('distributionMoney', $money);
        if ($res === false) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 用户分销金额-递减
     * @param int $userId 用户id
     * @param float $money 金额
     * @param object $trans
     * @return bool
     * */
    public function decDistributionMoney(int $userId, float $money, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if ($money < 0) {
            $dbTrans->rollback();
            return false;
        }
        $model = new UsersModel();
        $res = $model->where(array('userId' => $userId))->setDec('distributionMoney', $money);
        if ($res === false) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 获取用户的业务人员信息
     * @param string $userPhone 用户手机号
     * @return array
     * */
    public function getBusinessPersonnelDetail(string $userPhone)
    {
        $inviteInfo = M('invite_cache_record record')
            ->join('left join wst_users users ON users.userId = record.inviterId')
            ->where("record.inviteePhone = {$userPhone}")
            ->field('users.userId,users.userName,users.userPhone')
            ->find();
        if (empty($inviteInfo)) {
            $inviteInfo = M('distribution_invitation icr')
                ->join('left join wst_users users ON users.userId = icr.userId')
                ->where("icr.userPhone = {$userPhone}")
                ->field('users.userId,users.userName,users.userPhone')
                ->find();
        }
        if (empty($inviteInfo)) {
            return array();
        }
        $returnData = array();
        $returnData['inviteUserId'] = $inviteInfo['userId'];
        $returnData['inviteUserName'] = $inviteInfo['userName'];
        $returnData['inviteUserPhone'] = $inviteInfo['userPhone'];
        return $returnData;
    }
}
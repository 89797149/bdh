<?php

namespace V3\Model;

/**
 *用户模块相关
 * 重构时注意 每个函数功能相对要独立 用最少的参数 最少的上下文耦合 完成自己原子性的功能
 * 例如这里的邀请人函数 只要一个用户id就可以了 而不是需要在其他逻辑调用前 还自己调用判断 传参  这个统一交给原子功能函数就可以了 其他函数只要知道调用它能干嘛 就行了 其他的不用考虑
 **/
class UserModel extends BaseModel
{

    //从微信获取基本信息 例如unionID $session_key
    /**
     *   $code 前端传过来的code
     */
    public function getWechatUserInfo($code)
    {
        $appid = $GLOBALS["CONFIG"]["xiaoAppid"];
        $secret = $GLOBALS["CONFIG"]["xiaoSecret"];

        $weiResData = curlRequest("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code", '', false, 1);
        return json_decode($weiResData, true);

    }


    /**
     *   解密微信数据
     **/
    public function WXBizDataCrypt($encryptedData, $iv, $session_key)
    {
        import('Vendor.WXBizDataCrypt.WXBizDataCrypt');
        $appid = $GLOBALS["CONFIG"]["xiaoAppid"];
        $pc = new \WXBizDataCrypt($appid, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $redata);
        $weiResData = json_decode($redata, true);

        if ($errCode == 0) {
            //解密成功
            return $weiResData;
        } else {
            return [];//解密失败
        }


    }


    //【用户是否存在】根据Unionid获取 userId
    public function isUnionid($Unionid)
    {
        $mod_Users = M('users');
        $where['WxUnionid'] = $Unionid;
        $data = $mod_Users->where($where)->find();//再次获取用户所有字段
        if (!empty($data)) {
            return $data;
        } else {
            return [];
        }
    }


    //【用户是否存在】用户手机号 获取 这个登陆名 存在就返回 包含userId
    public function isUserLoginName($loginName)
    {
        $mod_Users = M('users');
        $where['loginName'] = $loginName;
        $data = $mod_Users->where($where)->find();//获取用户所有字段
        if (!empty($data)) {
            return $data;
        } else {
            return [];
        }
    }

    //【通过userId】获取用户信息|获取用户是否存在
    public function getUserData($userId)
    {
        $mod_Users = M('users');
        $where['userId'] = $userId;
        return $mod_Users->where($where)->find();//获取用户所有字段
    }

    //根据userId进行登陆 返回 token以及用户基本信息
    public function login($userId)
    {
        $mod_Users = M('users');
        $where['userId'] = $userId;
        $userData = $mod_Users->where($where)->find();//再次获取用户所有字段
        //生成token
        $memberToken = md5(uniqid('', true) . $code . $userData['userId'] . $userData['loginName'] . (string)microtime());

        if (userTokenAdd($memberToken, $userData)) {
            $retdata['memberToken'] = $memberToken;
            $retdata['userData'] = $userData;
//            $this->UserLogInfo($userId);//记录登陆日志 --前端调用的是 addUsersLoginLog ，当前这个去掉，重复了
            return $retdata;
        } else {
            return [];//登陆失败
        }


    }

    //用户登陆日志
    public function UserLogInfo($userId)
    {
        if (empty($userId)) {//验证有效性 也可以验证用户是否真实存在 可不处理 内部方法调用不用管
            return false;
        }
        $mod_Users = M("users");
        $mod_log_user_logins = M('log_user_logins');

        //获取上一次登陆信息 进行更新
        $userLogin = $mod_log_user_logins->where("userId={$userId}")->order("loginTime desc")->find();
        if (!empty($userLogin)) {
            $logdata['lastIP'] = $userLogin['loginIp'];
            $logdata['lastTime'] = $userLogin['loginTime'];
            $mod_Users->where("userId = '{$userId}'")->save($logdata);
        }

        //新增当前登陆信息
        $data = array();
        $data["userId"] = $userId;
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $data["loginSrc"] = 2;//来源目前统一为移动端 暂时
        return $mod_log_user_logins->add($data);
    }

    //用户注册 成功返回id 失败返回0
    public function reg($userInfo)
    {
        $mod_Users = M('users');

        //备参
        $data['loginName'] = $userInfo['loginName'];

        $data['userName'] = $userInfo['userName'];
        $data['userPhoto'] = $userInfo['userPhoto'];
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['openId'] = $userInfo['openId'];
        $data['userFrom'] = 3;
        $data['userType'] = 0;//默认普通会员 后续可以支持用户手动选择注册成商户类型还是个人 只是商户资料要有不同的要求 商户可以切换个人 但是个人必须在商户资料齐全情况下才能切回去 订单可能要冗余用户类型 避免切换导致订单这块后续统计或者其他业务上出现问题

        $data['WxUnionid'] = $userInfo['WxUnionid'];
        $data['userPhone'] = $userInfo['userPhone'];
        $data['firstOrder'] = 1;
        $add_is_ok_id = $mod_Users->add($data);

        if (empty($add_is_ok_id)) {
            return 0;
        }
        return $add_is_ok_id;
    }

    /**
     *推送消息给用户 该推送不限于任何推送方式 推送方式由push接管处理 这里只负责触发不同的类型推送
     *这里还有待改造 不应该直接传输类型 因为不同类型还要不同的参数要求 后续等待重构 将类型变为函数 然后不同推送业务类型就不同函数 不同参数 更加明了
     *推送渠道不用管 交给用户自己开启和关闭即可 模板这块还需要完善 不然不够灵活
     *注册成功发送推送信息 type=4
     */
    public function UserPushInfo($userId, $type = null)
    {
        //未指定推送类型 不进行推送
        if ($type == null) {
            return false;
        }
        //注册成功发送推送信息
        $push = D('Adminapi/Push');
        return $push->postMessage((int)$type, $userId);
    }


    /*******
     *邀请好友 好友必须下单才有优惠券 先领取优惠券 状态dataFlag -1 在用户确认收货的时候进行处理 还有自动收货的时候
     ******/
    public function InvitationFriend($userId)
    {

        //是否有邀请人？TODO: 后续需要增加 配置期限 例如 一周内没注册 该邀请人就算失效 别人可以继续邀请ta
        $userData = $this->getUserData($userId);
        if (empty($userData)) {
            return false;//用户不存在
        }

        $inviteInfo = M('invite_cache_record')->where(array('inviteePhone' => $userData['userPhone'], 'icrFlag' => 1))->find();
        $Invitation = $inviteInfo['inviterId'];
        if (empty($Invitation)) {
            return false;//不存在邀请人
        }

        //送优惠券
        $mod_users = M('users');
        $mod_user_Invitation = M('user_invitation');
        //更新历史邀请人数 +1
        $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum', 1);
        //自动领取优惠券
//        $where['dataFlag'] = 1;
//        $where['couponType'] = 3;
//        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
//        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
//        $data = M('coupons')->where($where)->order('createTime desc')->select();

        $m = D("V3/Api");

//        for ($i = 0; $i < count($data); $i++) {
//            $m->okCoupons($Invitation, $data[$i]['couponId'], 3, -1, $userId);
//        }

        //写入邀请关系
        $add_data['userId'] = (int)$Invitation;
        $add_data['source'] = 2;//app邀请好友获得
        $add_data['UserToId'] = (int)$userId;
//        $add_data['reward'] = count($data);//优惠券数量
        $add_data['createTime'] = date("Y-m-d H:i:s");
        //获取邀请者给被邀请者的奖励次数
        $inviteRewardNum = (int)$GLOBALS["CONFIG"]['inviteNumReward'];
        $inviteNumRules = $GLOBALS["CONFIG"]['inviteNumRules'];  //1.优惠券||2.返现||3.积分
        $add_data['inviteRewardNum'] = $inviteRewardNum;
        //1.优惠券||2.返现||3.积分---------由于之前已经有一次邀请好友就赠送优惠券，所以要将获取的配置次数减一
//        if ($inviteNumRules == 1 && $inviteRewardNum > 0) {
//            $add_data['inviteRewardNum'] = intval($inviteRewardNum - 1);
//        }
        return $mod_user_Invitation->add($add_data);

    }

    /*******
     *邀请好友开通会员 好友必须开通会员才有优惠券 先领取优惠券 状态dataFlag -1 在用户开通会员成功的时候进行处理
     * @param int userId PS：注册用户id
     * TODO：前端可能暂未有此功能
     ******/
    public function InvitationFriendSetmeal($userId)
    {
        $cacheRecordTable = M('invite_cache_record');
        $recordWhere = [];
        $recordWhere['inviteePhone'] = $user_Phone;
        $recordWhere['icrFlag'] = 1;
        $recordInfo = $cacheRecordTable->where($recordWhere)->order('id desc')->find();
        $Invitation = $recordInfo['inviterId'];


        $userInfo = M('users')->where(['userId' => $userId])->field('userId,userPhone')->find();
        if ($Invitation > 0) {
            //后加
            //通过分享链接
            //自动领取优惠券
            $where['dataFlag'] = 1;
            $where['couponType'] = 6; //邀请开通会员
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->select();
            $m = D("Weimendian/Api");
            for ($i = 0; $i < count($data); $i++) {
                $m->okCoupons($Invitation, $data[$i]['couponId'], 6, -1, $userId);
            }

            $tab = M('setmeal_invitation');
            $insert['userId'] = $Invitation;
            $insert['userPhone'] = $userInfo['userPhone'];
            $insert['addTime'] = date('Y-m-d H:i:s', time());
            if ($tab->add($insert)) {
                return true;
            } else {
                return false;
            }
        } else {
            //相当于面对面分享 TODO:不知道是否有实际用处了 可能只是为了兼容的代码
            $setmealInvitation = M('setmeal_invitation')->where(['userPhone' => $userInfo['userPhone']])->find();
            if ($setmealInvitation) {
                $Invitation = $setmealInvitation['userId'];
                //送优惠券
                $mod_users = M('users');
                //更新历史邀请人数 +1
                $mod_users->where("userId = '{$Invitation}'")->setInc('HistoIncoNum', 1);//方便其他地方引用为了不影响过多逻辑暂不判断是否成功
                //自动领取优惠券
                $where['dataFlag'] = 1;
                $where['couponType'] = 6; //邀请开通会员
                $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
                $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
                $data = M('coupons')->where($where)->order('createTime desc')->select();
                $m = D("Weimendian/Api");
                for ($i = 0; $i < count($data); $i++) {
                    $m->okCoupons($Invitation, $data[$i]['couponId'], 6, -1, $userId);
                }
                return true;
            }
        }
    }


    /*******
     *新人专享大礼
     ******/
    public function FunNewPeopleGift($userId)
    {
        $m = D("V3/Api");
        //新人奖励运费券 
        $freightCouponsNum = $GLOBALS["CONFIG"]['freightCoupons'];
        if (!empty($freightCouponsNum)) {
            $freightCouponsNum = (int)$freightCouponsNum;
            $where['dataFlag'] = 1;
            $where['couponType'] = 8;
            $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
            $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
            $data = M('coupons')->where($where)->order('createTime desc')->find();
            if (!empty($data)) {
                for ($i = 0; $i < $freightCouponsNum; $i++) {
                    $c = $m->okCoupons($userId, $data['couponId'], 8);  //运费券8
                }
            }
        }
        //获取新人优惠券
        $where = array();
        $where['dataFlag'] = 1;
        $where['couponType'] = 2;
        $where['sendStartTime'] = array('ELT', date('Y-m-d'));//发放开始时间
        $where['sendEndTime'] = array('EGT', date('Y-m-d'));//发放结束时间
        $data = M('coupons')->where($where)->order('createTime desc')->select();


        for ($i = 0; $i < count($data); $i++) {
            $m->okCoupons($userId, $data[$i]['couponId'], 2);//新人专享2
        }
        return $data;
    }

    /**
     * 记录用户分销关系表
     * @param varchar $userPhone PS:注册人手机号
     * @param int $add_is_ok_id PS:注册人id
     * */
    public function distributionRelation($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $add_is_ok_id = $userId;
        $userPhone = $this->getUserData($userId)['userPhone'];


        //写入用户邀请表
        $invitationLogTab = M('distribution_invitation invitation');
        $where = [];
        $where['invitation.userPhone'] = $userPhone;
        $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
        $field .= ',users.balance';
        $invitationInfo = $invitationLogTab
            ->join("left join wst_users users on users.userId=invitation.userId")
            ->field($field)
            ->where($where)
            ->find();
        if (empty($invitationInfo)) {
            return false;
        }
        if ($invitationInfo['dataType'] == 1) {
            //分销
            //写入用户分销关系表 PS:后加
            $distributionRelation = M('distribution_relation');
            //一级邀请人
            $relation['userId'] = $add_is_ok_id;
            $relation['distributionLevel'] = 1;
            $relation['pid'] = $invitationInfo['userId'];
            $relation['addTime'] = date('Y-m-d H:i:s', time());
            $distributionRelation->add($relation);
            //二级
            $preInvitation = $distributionRelation->where("distributionLevel=1 AND userId='" . $invitationInfo['userId'] . "'")->find();
            if ($preInvitation) {
                //如果邀请人有上级邀请人,处理该邀请人的上级邀请人为此注册人的二级邀请人
                unset($relation);
                $relation['userId'] = $add_is_ok_id;
                $relation['distributionLevel'] = 2;
                $relation['pid'] = $preInvitation['pid'];
                $relation['addTime'] = date('Y-m-d H:i:s', time());
                $distributionRelation->add($relation);
            }
        }
        if ($invitationInfo['dataType'] == 2) {
            $usersTab = M('users');
            $where = [];
            $where['userFlag'] = 1;
            $where['userId'] = $invitationInfo['userId'];
            $invitationUser = $usersTab->where($where)->field('pullNewPermissions,pullNewRegister,pullNewOrder')->find();
            //地推
            M()->startTrans();
            $pullNewTab = M('pull_new_log');
            $pullData = [];
            $pullData['userId'] = $add_is_ok_id;
            $pullData['inviterId'] = $invitationInfo['userId'];
            $pullData['createTime'] = date('Y-m-d H:i:s', time());
            $pullNewRes = $pullNewTab->add($pullData);
            if (!$pullNewRes) {
                M()->rollback();
                return false;
            }
            //用户注册成功后,发放给邀请人成功注册相关的奖励
            if ($invitationUser['pullNewPermissions'] == 1) {
                $configs = $GLOBALS['CONFIG'];
                $pullNewRegister = $configs['pullNewRegister'];//奖励规则-用户成功注册
                //如果用户开启了拉新权限,但是没有配置奖励,而平台商城信息却配置了,则采用商品信息中的奖励规则,否则采用用户中的配置规则
                if ($invitationUser['pullNewRegister'] > 0) {
                    $pullNewRegister = $invitationUser['pullNewRegister'];
                }
                if ($pullNewRegister > 0) {
                    $date = date('Y-m-d H:i:s', time());
                    $amountLog = [];
                    $amountLog['userId'] = $add_is_ok_id;
                    $amountLog['inviterId'] = $invitationInfo['userId'];
                    $amountLog['dataType'] = 1;
                    $amountLog['amount'] = $pullNewRegister;
                    $amountLog['status'] = 1;
                    $amountLog['createTime'] = $date;
                    $amountLog['updateTime'] = $date;
                    $insertAmountLogRes = M('pull_new_amount_log')->add($amountLog);
                    if (!$insertAmountLogRes) {
                        M()->rollback();
                        return false;
                    }
                    //拉新奖励记录成功后更新用户余额并记录余额变动日志
                    //余额记录
                    $balanceLog = M('user_balance')->add(array(
                        'userId' => $invitationInfo['userId'],
                        'balance' => $pullNewRegister,
                        'dataSrc' => 1,
                        'orderNo' => '',
                        'dataRemarks' => "拉新奖励-用户成功注册",
                        'balanceType' => 1,
                        'createTime' => $date,
                        'shopId' => 0
                    ));
                    if (!$balanceLog) {
                        M()->rollback();
                        return false;
                    }
                    $userSave = [];
                    $userSave['balance'] = $invitationInfo['balance'] + $pullNewRegister;
                    $updateUser = $usersTab->where(['userId' => $invitationInfo['userId']])->save($userSave);
                    if (!$updateUser) {
                        M()->rollback();
                        return false;
                    }
                }
            }
            M()->commit();
        }
        return true;
    }


    //TODO 用户微信相关函数 例如解密 获取等 应该放到用户微信类中 可采用 php: traits


}
<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 消息推送类
 */
class PushModel extends BaseModel
{

    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        $userName = I('userName', '', 'trim');
        $openId = I('openId', '', 'trim');
        $registration_id = I('registration_id', '', 'trim');

        $where = " where 1=1 ";
        $sql = "select * from __PREFIX__push_record " . $where;
        if (!empty($userName)) $sql .= " and userName = '" . $userName . "' ";
        if (!empty($openId)) $sql .= " and openId = '" . $openId . "' ";
        if (!empty($registration_id)) $sql .= " and registration_id = '" . $registration_id . "' ";
        $sql .= " order by id desc";
        return $this->pageQuery($sql, $page, $pageSize);

    }

    /**
     * 消息推送 - 动作
     * @param array $data
     */
    public function doNewsPush($param = array())
    {
        $where = array('u.userFlag' => 1);
        if (!empty($param['loginStartTime'])) $where['u.lastTime'][] = array('EGT', $param['loginStartTime']);
        if (!empty($param['loginEndTime'])) $where['u.lastTime'][] = array('ELT', $param['loginEndTime']);
        if (!empty($param['orderStartTime'])) $where['o.createTime'][] = array('EGT', $param['orderStartTime']);
        if (!empty($param['orderEndTime'])) $where['o.createTime'][] = array('ELT', $param['orderEndTime']);

        $list = M('users as u')->field('u.*')->join('left join wst_orders as o on u.userId = o.userId')->where($where)->group('u.userId')->select();

        if (empty($list)) return array('status' => -1);

        $templateId = intval($param['templateId']);
        $first = trim($param['first']);
        $remark = trim($param['remark']);
        $newsParam = empty($param['newsParam']) ? array() : $param['newsParam'];

        $len = 0;

        if ($templateId > 0) {
            $templateDetail = D('Admin/Template')->wxTemplateDetail(array('wntFlag' => 1, 'id' => $templateId));
            if (!empty($templateDetail)) $len = count(explode(',', $templateDetail['name']));
        }

        $data = array();
        if (empty($param['pushType'])) {//小程序消息模板
            $datas = array();
            $datas['first'] = array('value' => $first, 'color' => '');
            if ($len > 0) {
                for ($i = 0; $i < $len; $i++) {
                    $datas['keyword' . ($i + 1)] = array('value' => $newsParam[$i], 'color' => '');
                }
            }
            $datas['remark'] = array('value' => $remark, 'color' => '');

            foreach ($list as $v) {
                if (empty($v['openId'])) continue;

                $result = sendMessage($v['openId'], $templateDetail['template_id'], '', 'keyword1', $datas);
//                $result = array('errcode'=>0);
                //$result['errcode'] = 0 成功
                $data[] = array(
                    'userId' => $v['userId'],
                    'userName' => $v['userName'],
                    'openId' => $v['openId'],
                    'registration_id' => $v['registration_id'],
                    'pushType' => 0,//类型（0：小程序模板 1:极光推送）
                    'title' => $param['titles'],
                    'newsImg' => $param['newsImg'],
                    'content' => json_encode($datas),
                    'createTime' => date('Y-m-d H:i:s'),
                    'templateId' => $templateId,
                    'templateTitle' => $templateDetail['title'],
                    'state' => ($result['errcode'] == 0) ? 1 : 0,
                    'prFlag' => 1
                );
            }
        } else {//极光推送模板
            foreach ($list as $v) {
                if (empty($v['registration_id'])) continue;
                $result = pushMessageByRegistrationId($param['titles'], $param['content'], $v['registration_id']);
//                $result = array('http_code'=>200);
                //$result['http_code'] = 200 表示成功
                $data[] = array(
                    'userId' => $v['userId'],
                    'userName' => $v['userName'],
                    'openId' => $v['openId'],
                    'registration_id' => $v['registration_id'],
                    'pushType' => 1,//类型（0：小程序模板 1:极光推送）
                    'title' => $param['titles'],
                    'newsImg' => $param['newsImg'],
                    'content' => $param['content'],
                    'createTime' => date('Y-m-d H:i:s'),
                    'templateId' => 0,
                    'templateTitle' => '',
                    'state' => ($result['http_code'] == 200) ? 1 : 0,
                    'prFlag' => 1
                );
            }
        }
        if (!empty($data)) M('push_record')->addAll($data);

        return array('status' => 1);

    }

    /**
     * @param $userData
     * @return array
     * 精准营销
     */
    public function precisionMarketing($userData)
    {
        $result = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $bigType = I('bigType', 0, 'intval');//大分类，1：用户唤醒功能
        $noticeMode = I('noticeMode');//通知方式，1：短信通知、2：极光推送、3：公众号
//        $startCreateTime = I('startCreateTime');//开始创建时间
        $endCreateTime = I('endCreateTime');//结束创建时间
//        $startUserExpireTime = I('startUserExpireTime');//开始过期时间
        $endUserExpireTime = I('endUserExpireTime');//结束过期时间
        $startBalance = I('startBalance', 0, 'intval');//开始余额
        $endBalance = I('endBalance', 0, 'intval');//结束余额
        $startIntegral = I('startIntegral', 0, 'intval');//开始积分
        $endIntegral = I('endIntegral', 0, 'intval');//结束积分
        $isSignIn = I('isSignIn', 0, 'intval');//今日是否已签到 ，0:否，1：是
//        $startLastTime = I('startLastTime');//开始登录时间
        $endLastTime = I('endLastTime');//结束登录时间
        $startConsumeNum = I('startConsumeNum', 0, 'intval');//开始消费次数
        $endConsumeNum = I('endConsumeNum', 0, 'intval');//结束消费次数
        $implementType = I('implementType', 0, 'intval');//执行方式，1：积分+文案、2：优惠券+文案、3：余额+文案、4：会员体验+文案、5：纯文案通知
        $integral = I('integral', 0, 'intval');//积分
        $couponId = I('couponId', '', 'trim');//优惠券ID,多个以逗号连接
        $balance = I('balance', 0, 'intval');//余额
        $day = I('day', 0, 'intval');//天数，主要用于会员体验
        $copywriting = I('copywriting', '', 'trim');//文案内容
        $sendPeopleNum = I('sendPeopleNum', 0, 'intval');//发送人数
        $userPhone = I('userPhone');//手机号可以为多个
        $title = I('title', 0);//标题
        if (empty($bigType) || empty($noticeMode) || empty($implementType) || empty($sendPeopleNum) || empty($copywriting)) {
            $result['msg'] = '参数不全';
            return $result;
        }

        $noticeMode_arr = explode(',', $noticeMode);
        if (in_array(1, $noticeMode_arr)) {//短信通知
            if (empty($GLOBALS['CONFIG']['smsKey']) || empty($GLOBALS['CONFIG']['smsPass'])) {
                $result['msg'] = '短信配置信息不全';
                return $result;
            }
        }
        if (in_array(2, $noticeMode_arr)) {//极光推送
            if (empty($GLOBALS["CONFIG"]["jAppkey"]) || empty($GLOBALS["CONFIG"]["jMastersecret"])) {
                $result['msg'] = '极光配置信息不全';
                return $result;
            }
        }
        if (in_array(3, $noticeMode_arr)) {//公众号
            if (empty($GLOBALS["CONFIG"]["xiaoTemplateid"])) {
                $result['msg'] = '公众号配置信息不全';
                return $result;
            }
        }
        if (in_array(4, $noticeMode_arr)) {//邮箱
            if (empty($GLOBALS['CONFIG']['mailSmtp']) || empty($GLOBALS['CONFIG']['mailAuth']) || empty($GLOBALS['CONFIG']['mailUserName']) || empty($GLOBALS['CONFIG']['mailPassword']) || empty($GLOBALS['CONFIG']['mailAddress']) || empty($GLOBALS['CONFIG']['mailSendTitle'])) {
                $result['msg'] = '邮箱配置信息不全';
                return $result;
            }
        }
        if ($bigType == 1) {//用户唤醒功能
            $testFlag = I('testFlag', 0, 'intval');//测试标记，如果为1，走测试功能，否则正常发送
            if (!empty($testFlag)) {//测试功能
                $targetObject = I('targetObject', '', 'trim');//目标对象（登录名或手机号）
                if (empty($targetObject)) {
                    $result['msg'] = '参数不全';
                    return $result;
                }
                $where = "(loginName = '$targetObject' or userPhone = '$targetObject') and userStatus = 1 and userFlag = 1";
                $user_info = M('users')->where($where)->find();
                if (empty($user_info)) {
                    $result['msg'] = '目标对象不存在';
                    return $result;
                }
                $noticeMode_arr = explode(',', $noticeMode);
                if (in_array(1, $noticeMode_arr)) {//短信通知
                    $rest = WSTSendSMS($user_info['userPhone'], $copywriting);
                }
                if (in_array(2, $noticeMode_arr)) {//极光推送
                    $rest = pushMessageByRegistrationId($title, $copywriting, $user_info['userId']);
                }
                if (in_array(3, $noticeMode_arr)) {//公众号
                    if (empty($user_info['openId'])) {
                        $result['msg'] = '参数不全';
                        return $result;
                    }
                    $rest = sendMessage($user_info['openId'], 3, '', 'keyword1', $copywriting);
                }
                if (in_array(4, $noticeMode_arr)) {//邮箱
                    if (empty($title) || empty($user_info['userEmail'])) {
                        $result['msg'] = '参数不全';
                        return $result;
                    }
                    $rest = WSTSendMail($user_info['userEmail'], $title, $copywriting);
                }
                if ($rest == 1) {
                    $result['code'] = 0;
                    $result['msg'] = '发送成功';
                    return $result;
                }
            } else {//正常推送
                $where = " userStatus = 1 ";
                //手机号[批量]
                if (!empty($userPhone)) {
                    $userPhone = implode(',', preg_split("[\n]", $userPhone));
//                    $where .= " and userPhone IN ($userPhone)";
                    $where .= " and loginName IN ($userPhone)";
                }
                //积分
                if (!empty($startIntegral) && !empty($endIntegral)) {
                    $where .= " and userScore >= '$startIntegral' and userScore <= '$endIntegral'";
                }
                // 余额
                if (!empty($startBalance) && !empty($endBalance)) {
                    $where .= " and  balance >= '$startBalance' and balance <= '$endBalance'";
                }
                if (!empty($endCreateTime)) {
                    //用户创建时间
                    $startCreateTime = date('Y-m-d', strtotime("$endCreateTime +1 day"));
                    $where .= " and createTime >= '$endCreateTime' and createTime <= '$startCreateTime'";
                }
                if (!empty($endUserExpireTime)) {
                    //会员过期时间
                    $startCreateTime = date('Y-m-d', strtotime("$endUserExpireTime +1 day"));
                    $where .= " and expireTime >= '$endUserExpireTime' and expireTime <= '$startCreateTime'";
                }
                if (!empty($endLastTime)) {
                    //最后登录时间
                    $startCreateTime = date('Y-m-d', strtotime("$endLastTime +1 day"));
                    $where .= " and lastTime >= '$endLastTime' and lastTime <= '$startCreateTime'";
                }
                if ($isSignIn == 1) {
                    $lastSignInTime = date('Y-m-d');//今日已签到
                    $startCreateTime = date('Y-m-d', strtotime("$endLastTime +1 day"));
                    $where .= " and lastSignInTime >= '$lastSignInTime' and lastSignInTime <= '$startCreateTime'";
                }
                $list = M('users')->where($where)->limit($sendPeopleNum)->select();
                if (empty($list)) {
                    $result['msg'] = '目标对象不存在';
                    return $result;
                }
                $bet = [];
                $couponsUsers = [];
                $integralLists = [];
                $balanceLists = [];
                foreach ($list as $v) {
                    $noticeMode_arr = explode(',', $noticeMode);
                    if (in_array(1, $noticeMode_arr)) {//短信通知
                        $res = WSTSendSMS($v['userPhone'], $copywriting);
                    }
                    if (in_array(2, $noticeMode_arr)) {//极光推送
                        $res = pushMessageByRegistrationId($title, $copywriting, $v['userId']);
                    }
                    if (in_array(3, $noticeMode_arr)) {//公众号
                        if (empty($v['openId'])) {
                            continue;
                        }
                        $res = sendMessage($v['openId'], 3, '', 'keyword1', $copywriting);
                    }
                    if (in_array(4, $noticeMode_arr)) {//邮箱  要发送的邮箱地址--邮件标题--邮件内容
                        if (empty($title)) {
                            $result['msg'] = '参数不全';
                            return $result;
                        }
                        $res = WSTSendMail($v['userEmail'], $title, $copywriting);
                    }
                    //发送记录
                    $betLog = [];
                    $betLog['userId'] = (int)$v['userId'];
                    $betLog['userName'] = (string)$v['userName'];
                    $betLog['openId'] = (string)$v['openId'];
                    $betLog['registration_id'] = (int)$v['registration_id'];
                    $betLog['pushType'] = (int)$noticeMode;//类型（0：小程序模板 1:极光推送）
                    $betLog['title'] = (string)$title;
                    $betLog['newsImg'] = '';
                    $betLog['content'] = (string)$copywriting;
                    $betLog['createTime'] = date('Y-m-d H:i:s');
                    $betLog['templateId'] = 0;
                    $betLog['templateTitle'] = '';
                    $betLog['state'] = (bool)$res;
                    $betLog['prFlag'] = 1;
                    $bet[] = $betLog;
                    //积分添加
                    if (!empty($integral)) {
                        if ($integral <= 0) {
                            $integral = 0;
                        }
                        $sql = "UPDATE wst_users SET userScore = userScore + $integral WHERE userId = " . $v['userId'];
                        $this->execute($sql);
                        //添加积分记录
                        $integralList = array();
                        $integralList["userId"] = $v['userId'];
                        $integralList["score"] = (int)$integral;
                        $integralList["dataSrc"] = 14;//14:门店赠送【如:用户唤醒】
                        $integralList["dataId"] = 2;
                        $integralList["dataRemarks"] = "门店赠送--用户唤醒";
                        $integralList["scoreType"] = 1;
                        $integralList["createTime"] = date('Y-m-d H:i:s');
                        $integralLists[] = $integralList;
                    }
                    //优惠券添加
                    if (!empty($couponId)) {
                        $couponIds = explode(',', $couponId);
                        foreach ($couponIds as $val) {
                            $couponsInfo = M('coupons')->where("couponId = $val")->find();
                            $couponsUsers[] = [
                                'couponId' => $val,
                                'userId' => $v['userId'],
                                'receiveTime' => date('Y-m-d H:i:s'),
                                'couponExpireTime' => $couponsInfo['validEndTime'] . ' 23:59:59',
                            ];
                        }
                    }
                    //余额添加
                    if (!empty($balance)) {
                        $sql = "UPDATE wst_users SET balance = balance + $balance WHERE userId = " . $v['userId'];
                        $this->execute($sql);
                        $balanceList = [];
                        $balanceList['userId'] = $v['userId'];
                        $balanceList['balance'] = $balance;
                        $balanceList['dataSrc'] = 1;
                        $balanceList['orderNo'] = 1;
                        $balanceList['dataRemarks'] = "门店赠送--用户唤醒";
                        $balanceList['balanceType'] = 1;
                        $balanceList['createTime'] = date('Y-m-d H:i:s');
                        $balanceList['shopId'] = 0;
                        $balanceLists[] = $balanceList;
                    }
                    //会员添加
                    if (!empty($day)) {
                        $newTime = strtotime(date('Y-m-d H:i:s'));
                        $expireTime = strtotime($v['expireTime']);
                        $newExpireTime = strtotime("+$day day", $expireTime);
                        if ($expireTime <= $newTime) {
                            $lastTime = date("Y-m-d H:i:s", $newExpireTime);
                        } else {
                            $lastTime = date("Y-m-d H:i:s", $newExpireTime);
                        }
                        $sql = "UPDATE wst_users SET expireTime = '$lastTime' WHERE userId = " . $v['userId'];
                        $this->execute($sql);
                    }
                }
                M('push_record')->addAll($bet);//唤醒记录
                M('coupons_users')->addAll($couponsUsers);//优惠券添加
                if (!empty($integralLists)) {//积分记录
                    M('user_score')->addAll($integralLists);
                }
                if (!empty($balanceLists)) {//余额记录
                    M('user_balance')->addAll($balanceLists);
                }
                $data = array(
                    'bigType' => $bigType,
                    'noticeMode' => $noticeMode,
                    'startCreateTime' => $startCreateTime,
                    'endCreateTime' => $endCreateTime,
//                    'startUserExpireTime' => $startUserExpireTime,
                    'endUserExpireTime' => $endUserExpireTime,
                    'startBalance' => $startBalance,
                    'endBalance' => $endBalance,
                    'startIntegral' => $startIntegral,
                    'endIntegral' => $endIntegral,
                    'isSignIn' => $isSignIn,
//                    'startLastTime' => $startLastTime,
                    'endLastTime' => $endLastTime,
                    'startConsumeNum' => $startConsumeNum,
                    'endConsumeNum' => $endConsumeNum,
                    'implementType' => $implementType,
                    'integral' => $integral,
                    'couponId' => $couponId,
                    'balance' => $balance,
                    'day' => $day,
                    'sendPeopleNum' => $sendPeopleNum,
                    'copywriting' => $copywriting,
                    'title' => $title,
                    'createTime' => date('Y-m-d H:i:s'),
                    'state' => ($result['errcode'] == 0) ? 1 : 0,
                );
                M('precision_marketing')->add($data);
            }
        } else {//失败
            return $result;
        }
        $describe = "[{$userData['loginName']}]使用了用户唤醒功能";
        addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
        $result['code'] = 0;
        $result['msg'] = '发送成功';
        return $result;
    }

    /**
     * @param $id  通知id
     * @param $userId 用户id
     * @return mixed
     */
    public function postMessage($id, $userId, $orderNo = '', $shopId = '')
    {
        $where = "userId = $userId and userStatus = 1 and userFlag = 1";
        $user_info = M('users')->where($where)->find();
        if (empty($user_info)) {
            $result['msg'] = '目标对象不存在';
            return $result;
        }
        $detail = M('notice')->where(array('id' => $id))->find();

        $shopInfo = M('shops')->where(array('shopId' => $shopId))->find();

        if (!empty($detail)) {
            $boolean_value = array('0' => false, '1' => true);
            $detail['isEmail'] = $boolean_value[$detail['isEmail']];
            $detail['isShortMessage'] = $boolean_value[$detail['isShortMessage']];
            $detail['isWxService'] = $boolean_value[$detail['isWxService']];
            $detail['isJgPush'] = $boolean_value[$detail['isJgPush']];

            //变量替换
            $field = array("{{Name}}", "{{Password}}", "{{Email}}", "{{ShopName}}", "{{OrderNo}}");
            $fieldName = array($user_info['userName'], $user_info['loginPwd'], $user_info['userEmail'], $shopInfo['shopName'], $orderNo);

            $shortMessageTemplate = strip_tags(str_replace($field, $fieldName, $detail['shortMessageTemplate']));
            $emailTemplate = strip_tags(str_replace($field, $fieldName, $detail['emailTemplate']));
            $wxServiceTemplate = strip_tags(str_replace($field, $fieldName, $detail['wxServiceTemplate']));
            $jgPushTemplate = strip_tags(str_replace($field, $fieldName, $detail['jgPushTemplate']));

            $detail['emailTemplate'] = json_decode($emailTemplate, true);
            $detail['shortMessageTemplate'] = json_decode($shortMessageTemplate, true);
            $detail['wxServiceTemplate'] = json_decode($wxServiceTemplate, true);
            $detail['jgPushTemplate'] = json_decode($jgPushTemplate, true);
        }
        if ($detail['isShortMessage'] == 1) {//短信通知
            WSTSendSMS($user_info['userPhone'], $detail['shortMessageTemplate']['templateCode']);
        }
        if ($detail['isJgPush'] == 1) {//极光推送
            pushMessageByRegistrationId($detail['jgPushTemplate']['jgTitle'], $detail['jgPushTemplate']['jgContent'], $user_info['userId']);
        }
        if ($detail['isWxService'] == 1) {//公众号
            sendMessage($user_info['openId'], 3, '', 'keyword1', $detail['wxServiceTemplate']['wxContent']);
        }
        if ($detail['isEmail'] == 1) {//邮箱
            WSTSendMail($user_info['userEmail'], $detail['emailTemplate']['mailTitle'], $detail['emailTemplate']['mailContent']);
        }
        $result['code'] = 0;
        $result['msg'] = '发送成功';
        return $result;
    }
}
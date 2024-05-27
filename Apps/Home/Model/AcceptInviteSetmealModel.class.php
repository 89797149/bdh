<?php
namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 邀请好友开通会员
 */
class AcceptInviteSetmealModel extends BaseModel {
    /*
     * 邀请好友开通会员记录
     * @param int $request['userId']
     * @param varchar $request['userPhone']
     * */
    public function insertLog($request){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '添加邀请记录失败';
        $apiRet['apiState'] = 'error';
        if($request){
            $smsTab = M('log_sms');
            $smsInfo = $smsTab->where("smsPhoneNumber='".$request['userPhone']."' AND smsCode='".$request['code']."'")->order("smsId DESC")->find();
            if(!$smsInfo){
                $apiRet['apiInfo'] = '验证码不正确';
                return $apiRet;
            }
            if($smsInfo && (strtotime($smsInfo['createTime']) + 1800) < time()){
                $apiRet['apiInfo'] = '验证码已经失效';
                return $apiRet;
            }
            $userInfo = M('users')->where("userId='".$request['userId']."'")->find();
            if($userInfo && $userInfo['userPhone'] == $request['userPhone']){
                $apiRet['apiInfo'] = '邀请人的手机号不能和被邀请人的手机号一致';
                return $apiRet;
            }
            $userInfo = M('users')->where("userPhone='".$request['userPhone']."' AND userFlag=1")->find();
            if($userInfo){
                $apiRet['apiInfo'] = '该手机号已经注册过了';
                return $apiRet;
            }
            $tab = M('setmeal_invitation');
            $insert['userId'] = $request['userId'];
            $insert['userPhone'] = $request['userPhone'];
            $insert['addTime'] = date('Y-m-d H:i:s',time());
            $inserRes = $tab->add($insert);
            if($inserRes){
                $apiRet['apiCode'] = 1;
                $apiRet['apiInfo'] = '添加邀请记录成功';
                $apiRet['apiState'] = 'success';
            }
        }
        return $apiRet;
    }
}
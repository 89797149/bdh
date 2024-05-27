<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器
 */
class AcceptInviteSetmealAction extends BaseAction {
	/**
     * 邀请好友开通会员
	 */
    public function index(){
        $this->display("default/acceptInviteSetmeal/index");
    }

    /**
     * 下载页
     */
    public function download(){
        $this->display("default/acceptInviteSetmeal/download");
    }

    /**
     * 获取手机验证码
     * @param varchar $phone
     */
    public function acceptInviteSetmealPhoneVerify(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '短信发送失败';
        $apiRet['apiState'] = 'error';
        $phone = WSTAddslashes(I("userPhone"));
        if(empty($phone)){
            $apiRet['apiInfo']='字段有误';
            $this->ajaxReturn($apiRet);
        }
        if(!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#",$phone)){
            $apiRet['apiInfo']='手机号格式不正确';
            $this->ajaxReturn($apiRet);
        }
        $phoneVerify = mt_rand(100000,999999);
        $msg = "您正在获取邀请验证码，验证码为:".$phoneVerify."，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0,$phone,$msg,'distributionPhoneVerify',$phoneVerify);
        $rv['time']=30*60;
        $this->ajaxReturn($rv);
    }

    /*
     * 邀请记录
     * @param int userId
     * @param varchar userPhone
     * @param varchar code
     * */
    public function insertLog(){
        $request = I();
        if(empty($request['userId']) || empty($request['userPhone'])|| empty($request['code'])){
            $apiRet['apiCode']=-1;
            $apiRet['apiInfo']='字段有误';
            $apiRet['apiState']='error';
            $this->ajaxReturn($apiRet);
        }
        $data['userId'] = isset($request['userId'])?$data['userId']=$request['userId']:$data['userId']=0;
        $data['userPhone'] = isset($request['userPhone'])?$data['userPhone']=$request['userPhone']:$data['userPhone']=0;
        $data['code'] = isset($request['code'])?$data['code']=$request['code']:$data['code']=0;
        $m = D("Home/AcceptInviteSetmeal");
        $mod = $m->insertLog($data);
        $this->ajaxReturn($mod);
    }
}
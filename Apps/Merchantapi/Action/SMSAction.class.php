<?php
namespace Merchantapi\Action;;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 短信
 */
class SMSAction extends BaseAction
{
    /**
     * 获取手机验证码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/er3i3y
     * @param string userPhone 手机号
     */
    public function getPhoneInviteVerify()
    {
        $phone = WSTAddslashes(I("userPhone"));
        if (empty($phone)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (!preg_match("#^13[\d]{9}$|^14[\d]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[\d]{9}$|^17[\d]{1}\d{8}$|^18[\d]{9}$|^19[\d]{9}$#", $phone)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '手机号格式不正确'));
        }
        $phoneVerify = mt_rand(100000, 999999);
        $msg = "您正在获取验证码，验证码为:" . $phoneVerify . "，请在30分钟内输入。";
        $rv = D('Home/LogSms')->sendSMS(0, $phone, $msg, 'getPhoneInviteVerify', $phoneVerify);
        $rv['time'] = 30 * 60;
        if ($rv['status'] == 1) {
            $rv = returnData(true, 0, 'success', '短信发送成功', '');
        } else {
            $rv = returnData(false, -1, 'error', $rv['msg']);
        }
        $this->ajaxReturn($rv);
    }
}

?>

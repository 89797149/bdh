<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 通知方式服务类
 */
class NoticeModel extends BaseModel {

    /**
     * 根据通知方式发送消息
     * @param $noticeId 通知ID
     * @param $userId 用户ID
     * @param $orderNo 订单号
     * @param string $password 密码
     * @param int $money 金额
     * @param string $remark 备注
     */
    public function sendNoticeMessage($noticeId = 0,$userId = 0,$orderNo = '',$password = '',$money = 0,$remark = ''){
        $notice_info = M('notice')->where(array('id'=>$noticeId))->find();
        $user_info = M('users')->where(array('userId'=>$userId))->find();
        if(!empty($notice_info)){
            $notice_config = M('notice_config')->where(array('noticeFlag'=>1))->select();
            if (!empty($notice_info['isEmail'])){//邮件发送
                if (!empty($notice_config)){
                    foreach ($notice_config as $v){
                        if ($v['noticeCode'] == 'email'){
                            $email_config = json_decode($v['noticeValue'],true);
                            break;
                        }
                    }
                }
                if (!empty($email_config)){
                    $emailTemplate = json_decode($notice_info['emailTemplate'],true);
                    $emailTemplateContent = str_replace('$Username$',$user_info['userName'],$emailTemplate['mailContent']);
                    $emailTemplateContent = str_replace('$Password$',$password,$emailTemplateContent);
                    WSTSendMailForNotice($email_config,$emailTemplate['receipt'],$emailTemplateContent);
                } else {
                    return false;
                }
            }
            if (!empty($notice_info['isShortMessage'])){//短信发送
                if (!empty($notice_config)){
                    foreach ($notice_config as $v){
                        if ($v['noticeCode'] == 'shortMessage'){
                            $short_message_config = json_decode($v['noticeValue'],true);
                            break;
                        }
                    }
                }
                if (!empty($short_message_config)){
                    $shortMessageTemplate = json_decode($notice_info['shortMessageTemplate'],true);
                    $shortMessageTemplateContent = $shortMessageTemplate['templateCode'];
                    WSTSendSMS3ForNotice($short_message_config,$user_info['userPhone'],$shortMessageTemplateContent);
                } else {
                    return false;
                }
            }
            if (!empty($notice_info['isWxService'])){//微信服务号发送

            }
            if (!empty($notice_info['isJgPush'])){//极光推送

            }
        }
    }

};
?>
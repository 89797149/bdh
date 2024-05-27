<?php
 namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 通知方式控制器
 */
class NoticeAction extends BaseAction{

	/**
	 * 通知方式详情
	 */
	public function noticeConfigDetail(){
		$this->isLogin();
        $noticeCode = I('noticeCode','','trim');
        if (empty($noticeCode)) {
            $this->returnResponse(-1,'参数不全');
        }
	    $m = D('Adminapi/Notice');
        $detail = $m->noticeConfigDetail($noticeCode);
        if (!empty($detail)){
            $detail['noticeValue'] = json_decode($detail['noticeValue'],true);
        }
        $this->returnResponse(0,'操作成功',$detail);
	}

	/**
	 * 修改通知方式
	 */
	public function noticeConfigEdit(){
		$this->isLogin();
		$id = I('id',0,'intval');
        $noticeCode = I('noticeCode','','trim');
		if (empty($id) || empty($noticeCode)){
            $this->returnResponse(-1,'参数不全');
        }
		$m = D('Adminapi/Notice');
        if($noticeCode == 'email') {//邮件配置
            //公共函数：WSTSendMail
            $data_param = array(
                'mailSmtp'  =>  I('mailSmtp','','trim'),//SMTP服务器
                'mailAuth'  =>  I('mailAuth',0,'intval'),//是否验证SMTP,0:否，1：是
                'mailUserName'  =>  I('mailUserName','','trim'),//SMTP登录账号
                'mailPassword'  =>  I('mailPassword','','trim'),//SMTP登录密码
                'mailAddress'   =>  I('mailAddress','','trim'),//SMTP发件人邮箱
                'mailSendTitle' =>  I('mailSendTitle','','trim')//发件人名称
            );
        } else if($noticeCode == 'shortMessage'){//短信
            //公共函数：WSTSendSMS3
            $data_param = array(
                'dhAccount' =>  I('dhAccount','','trim'),//用户账号
                'dhPassword'    =>  I('dhPassword','','trim'),//密码
                'dhSign'    =>  I('dhSign','','trim'),//短信签名，该签名需要提前报备，生效后方可使用，不可修改，必填，示例如：【大汉三通】
                'dhSendUrl' =>  I('dhSendUrl','','trim')//接口地址
            );
        } else if ($noticeCode == 'wxService'){//微信服务号
            $data_param = array();
        } else if($noticeCode == 'jgPush'){//极光推送
            $data_param = array();
        }
		$data = array(
//		    'noticeCode'    =>  $noticeCode,
            'noticeName'    =>  I('noticeName','','trim'),
            'noticeValue'   =>  json_encode($data_param)
        );
        $rs = $m->noticeConfigEdit($id,$data);
        $this->returnResponse(0,'操作成功');
	}

	/**
	 * 通知设置列表
	 */
    public function noticeList(){
    	$this->isLogin();
		$m = D('Adminapi/Notice');
		$list = $m->noticeList();
        $this->returnResponse(0,'操作成功',$list);
	}

    /**
     * 修改通知发送状态
     */
	public function updateNoticeSendState(){
//        $this->isLogin();
        $m = D('Adminapi/Notice');
        $id = I('id',0,'intval');
        $noticeCode = I('noticeCode','','trim');//email、shortMessage、wxService、jgPush

        $boolean_value = array('false'=>0,'true'=>1);

        $isEmail = I('isEmail',-1,'trim');
        if($isEmail != -1) $isEmail = $boolean_value[$isEmail];

        $isShortMessage = I('isShortMessage',-1,'trim');
        if($isShortMessage != -1) $isShortMessage = $boolean_value[$isShortMessage];

        $isWxService = I('isWxService',-1,'trim');
        if($isWxService != -1) $isWxService = $boolean_value[$isWxService];

        $isJgPush = I('isJgPush',-1,'trim');
        if($isJgPush != -1) $isJgPush = $boolean_value[$isJgPush];
        if (empty($id) || empty($noticeCode)) {
            $this->returnResponse(-1,'参数不全');
        }
        $verifyInfo = $m->noticeDetail($id);
        if ($noticeCode == 'email') {//邮件
            if ($isEmail < 0) {//是否选择邮件发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }
            $data = array('isEmail'=>$isEmail);
            if($isEmail == 1) {
                if (empty($GLOBALS['CONFIG']['mailSmtp']) || empty($GLOBALS['CONFIG']['mailAuth']) || empty($GLOBALS['CONFIG']['mailUserName']) || empty($GLOBALS['CONFIG']['mailPassword']) || empty($GLOBALS['CONFIG']['mailAddress']) || empty($GLOBALS['CONFIG']['mailSendTitle'])) {
                    $this->returnResponse(-1, '配置信息不全');
                }
                if (empty($verifyInfo['emailTemplate']['mailTitle']) || empty($verifyInfo['emailTemplate']['mailContent'])) {
                    $this->returnResponse(-1, '完善模板后添加');
                }
            }
        } else if ($noticeCode == 'shortMessage') {//短信
            if ($isShortMessage < 0) {//是否选择短信发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }
            $data = array('isShortMessage'=>$isShortMessage);
            if($isShortMessage == 1) {
                if (empty($GLOBALS['CONFIG']['smsKey']) || empty($GLOBALS['CONFIG']['smsPass'])) {
                    $this->returnResponse(-1, '配置信息不全');
                }
                if (empty($verifyInfo['shortMessageTemplate']['templateCode'])) {
                    $this->returnResponse(-1, '完善模板后添加');
                }
            }
        } else if ($noticeCode == 'wxService') {//微信服务号
            if ($isWxService < 0) {//是否选择微信服务号发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }
            $data = array('isWxService'=>$isWxService);
            if($isWxService == 1) {
                if (empty($GLOBALS["CONFIG"]["xiaoTemplateid"])) {
                    $this->returnResponse(-1, '配置信息不全');
                }
                if (empty($verifyInfo['wxServiceTemplate']['wxContent'])) {
                    $this->returnResponse(-1, '完善模板后添加');
                }
            }
        } else if ($noticeCode == 'jgPush') {//极光推送
            if ($isJgPush < 0) {//是否选择极光发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }
            $data = array('isJgPush'=>$isJgPush);
            if($isJgPush == 1) {
                if (empty($GLOBALS["CONFIG"]["jAppkey"]) || empty($GLOBALS["CONFIG"]["jMastersecret"])) {
                    $this->returnResponse(-1, '配置信息不全');
                }
                if (empty($verifyInfo['jgPushTemplate']['jgTitle']) || empty($verifyInfo['jgPushTemplate']['jgContent'])) {
                    $this->returnResponse(-1, '完善模板后添加');
                }
            }
        }
        if ($isEmail == -1 && $isShortMessage == -1 && $isWxService == -1 && $isJgPush == -1){
            $this->returnResponse(-1, '参数不全');
        }
        $result = $m->updateNotice($id,$data);
        $this->returnResponse(0,'操作成功');
    }

    /**
     * 修改通知发送模板
     */
    public function updateNoticeTemplate(){
        $this->isLogin();
        $id = I('id',0,'intval');
        $noticeCode = I('noticeCode','','trim');//email、shortMessage、wxService、jgPush
//        $emailTemplate = I('emailTemplate');
//        $shortMessageTemplate = I('shortMessageTemplate');
//        $wxServiceTemplate = I('wxServiceTemplate');
//        $jgPushTemplate = I('jgPushTemplate');
        if (empty($id) || empty($noticeCode)) {
            $this->returnResponse(-1,'参数不全');
        }
        if ($noticeCode == 'email') {//邮件
            /*if (empty($emailTemplate)) {//邮件模板
                $this->returnResponse(-1, '参数不全');
            }*/
            $emailTemplate = array(
                'receipt'   =>  I('receipt','','trim'),
                'mailTitle' =>  I('mailTitle','','trim'),
                'mailContent'   =>  I('mailContent','','trim')
            );
            $data = array('emailTemplate'=>json_encode($emailTemplate));
        } else if ($noticeCode == 'shortMessage') {//短信
            /*if (empty($shortMessageTemplate)) {//是否选择短信发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }*/
            $shortMessageTemplate = array(
                'messageTemplate'  =>  I('messageTemplate','','trim'),
                'templateCode'  =>  I('templateCode','','trim'),
                'signName'      =>  I('signName','','trim')
            );
            $data = array('shortMessageTemplate'=>json_encode($shortMessageTemplate));
        } else if ($noticeCode == 'wxService') {//微信服务号
            /*if (empty($wxServiceTemplate)) {//是否选择微信服务号发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }*/
            $wxServiceTemplate = array(
                'wxTemplate'  =>  I('wxTemplate','','trim'),
                'wxContent'    =>  I('wxContent','','trim')
            );
            $data = array('wxServiceTemplate'=>json_encode($wxServiceTemplate));
        } else if ($noticeCode == 'jgPush') {//极光推送
            /*if (empty($jgPushTemplate)) {//是否选择极光发送（0：否，1：是）
                $this->returnResponse(-1, '参数不全');
            }*/
            $jgPushTemplate = array(
                'jgTemplate'  =>  I('jgTemplate','','trim'),
                'jgTitle'    =>  I('jgTitle','','trim'),
                'jgContent'    =>  I('jgContent','','trim')
            );
            $data = array('jgPushTemplate'=>json_encode($jgPushTemplate));
        }
        /*if (empty($emailTemplate) && empty($shortMessageTemplate) && empty($wxServiceTemplate) && empty($jgPushTemplate)){
            $this->returnResponse(-1, '参数不全');
        }*/

        $m = D('Adminapi/Notice');
        $result = $m->updateNotice($id,$data);
        $this->returnResponse(0,'操作成功');
    }

    /**
     * 通知详情
     */
    public function noticeDetail(){
        $this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)){
            $this->returnResponse(-1, '参数不全');
        }
        $m = D('Adminapi/Notice');
        $detail = $m->noticeDetail($id);
        $this->returnResponse(0,'操作成功',$detail);
    }
};
?>
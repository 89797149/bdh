<?php


namespace App\Modules\Notify;

use App\Models\BaseModel;
use App\Models\NoticeModel;
use App\Modules\Shops\ShopsModule;
use App\Modules\Users\UsersModule;
use Think\Model;

//通知类
class NotifyModule extends BaseModel
{
    /**
     * 消息通知 PS:该方法copy原有方法Apps\Adminapi\Model\PushModel.class.php\postMessage
     * @param int 通知模板id
     * @param int $user_id 用户id
     * @param string $bill_no 单号
     * @param int $shop_id 门店id
     * @return bool
     * */
    public function postMessage($id, $user_id, $bill_no = '', $shop_id = '')
    {
        if (empty($id)) {
            return false;
        }
        $users_module = new UsersModule();
        $users_detail = $users_module->getUsersDetailById($user_id, '*', 2);
        if (empty($users_detail)) {
            return false;
        }
        $notice_model = new NoticeModel();
        $notice_detail = $notice_model->where(array(
            'id' => $id
        ))->find();
        if (empty($notice_detail)) {
            return false;
        }
        $shop_detail = array();
        if (!empty($shop_id)) {
            $shop_module = new ShopsModule();
            $shop_detail = $shop_module->getShopsInfoById($shop_id, '*', 2);
        }
        $boolean_value = array('0' => false, '1' => true);
        $notice_detail['isEmail'] = $boolean_value[$notice_detail['isEmail']];
        $notice_detail['isShortMessage'] = $boolean_value[$notice_detail['isShortMessage']];
        $notice_detail['isWxService'] = $boolean_value[$notice_detail['isWxService']];
        $notice_detail['isJgPush'] = $boolean_value[$notice_detail['isJgPush']];
        //变量替换
        $field = array("{{Name}}", "{{Password}}", "{{Email}}", "{{ShopName}}", "{{OrderNo}}");
        $field_name = array($users_detail['userName'], $users_detail['loginPwd'], $users_detail['userEmail'], $shop_detail['shopName'], $bill_no);

        $short_msessage_template = strip_tags(str_replace($field, $field_name, $notice_detail['shortMessageTemplate']));
        $email_template = strip_tags(str_replace($field, $field_name, $notice_detail['emailTemplate']));
        $wx_service_template = strip_tags(str_replace($field, $field_name, $notice_detail['wxServiceTemplate']));
        $jg_push_template = strip_tags(str_replace($field, $field_name, $notice_detail['jgPushTemplate']));
        $notice_detail['emailTemplate'] = json_decode($email_template, true);
        $notice_detail['shortMessageTemplate'] = json_decode($short_msessage_template, true);
        $notice_detail['wxServiceTemplate'] = json_decode($wx_service_template, true);
        $notice_detail['jgPushTemplate'] = json_decode($jg_push_template, true);
        if ($notice_detail['isShortMessage'] == 1) {//短信通知
            WSTSendSMS($users_detail['userPhone'], $notice_detail['shortMessageTemplate']['templateCode']);
        }
        if ($notice_detail['isJgPush'] == 1) {//极光推送
            pushMessageByRegistrationId($notice_detail['jgPushTemplate']['jgTitle'], $notice_detail['jgPushTemplate']['jgContent'], $users_detail['userId']);
        }
        if ($notice_detail['isWxService'] == 1) {//公众号
            sendMessage($notice_detail['openId'], 3, '', 'keyword1', $notice_detail['wxServiceTemplate']['wxContent']);
        }
        if ($notice_detail['isEmail'] == 1) {//邮箱
            WSTSendMail($users_detail['userEmail'], $notice_detail['emailTemplate']['mailTitle'], $notice_detail['emailTemplate']['mailContent']);
        }
        return true;
    }
}
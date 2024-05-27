<?php
 namespace Adminapi\Model;
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
     * 通知方式详情
     * @param $noticeCode
     */
	 public function noticeConfigDetail($noticeCode){
        return M('notice_config')->where(array('noticeCode'=>$noticeCode,'noticeFlag'=>1))->find();
     }

    /**
     * 修改通知方式
     * @param $id
     * @param $data
     * @return mixed
     */
     public function noticeConfigEdit($id,$data){
        return M('notice_config')->where(array('id'=>$id))->save($data);
     }

    /**
     * 通知设置列表
     */
     public function noticeList(){
         $list = M('notice')->order('id desc')->select();
         if (!empty($list)){
             $boolean_value = array('0'=>false,'1'=>true);
             foreach($list as $k=>$v){
                 $list[$k]['isEmail'] = $boolean_value[$v['isEmail']];
                 $list[$k]['isShortMessage'] = $boolean_value[$v['isShortMessage']];
                 $list[$k]['isWxService'] = $boolean_value[$v['isWxService']];
                 $list[$k]['isJgPush'] = $boolean_value[$v['isJgPush']];

                 if (!empty($v['emailTemplate'])){
                     $list[$k]['emailTemplate'] = json_decode($v['emailTemplate'],true);
                 }
                 if (!empty($v['shortMessageTemplate'])){
                     $list[$k]['shortMessageTemplate'] = json_decode($v['shortMessageTemplate'],true);
                 }
                 if (!empty($v['wxServiceTemplate'])){
                     $list[$k]['wxServiceTemplate'] = json_decode($v['wxServiceTemplate'],true);
                 }
                 if (!empty($v['jgPushTemplate'])){
                     $list[$k]['jgPushTemplate'] = json_decode($v['jgPushTemplate'],true);
                 }
             }
         }
         return $list;
     }

    /**
     * 修改通知
     * @param $id
     * @param $data
     */
     public function updateNotice($id,$data){
         return M('notice')->where(array('id'=>$id))->save($data);
     }

    /**
     * 通知详情
     * @param $id
     * @return mixed
     */
     public function noticeDetail($id){
         $detail = M('notice')->where(array('id'=>$id))->find();
         if (!empty($detail)){
             $boolean_value = array('0'=>false,'1'=>true);
             $detail['isEmail'] = $boolean_value[$detail['isEmail']];
             $detail['isShortMessage'] = $boolean_value[$detail['isShortMessage']];
             $detail['isWxService'] = $boolean_value[$detail['isWxService']];
             $detail['isJgPush'] = $boolean_value[$detail['isJgPush']];

             $detail['emailTemplate'] = json_decode($detail['emailTemplate'],true);
             $detail['shortMessageTemplate'] = json_decode($detail['shortMessageTemplate'],true);
             $detail['wxServiceTemplate'] = json_decode($detail['wxServiceTemplate'],true);
             $detail['jgPushTemplate'] = json_decode($detail['jgPushTemplate'],true);
         }
         return $detail;
     }
}
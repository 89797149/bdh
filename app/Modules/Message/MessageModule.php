<?php
/**
 * 信息
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-24
 * Time: 13:26
 */

namespace App\Modules\Message;


use App\Models\MessageModel;
use Think\Model;

class MessageModule
{
    /**
     * 商城信息-保存
     * @param array $params
     * -int id 消息id
     * -int msgType 消息类型[0:系统消息]
     * -int sendUserId 发送者ID
     * -int receiveUserId 接受者ID
     * -string msgContent 消息内容
     * -int msgStatus 阅读状态[0:未读 1:已读]
     * @param object $trans
     * @return int
     * */
    public function saveMessages(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = new $trans;
        }
        $saveParams = array(
            'msgType' => null,
            'sendUserId' => null,
            'receiveUserId' => null,
            'msgContent' => null,
            'msgStatus' => null,
        );
        parm_filter($saveParams, $params);
        $model = new MessageModel();
        if (empty($params['id'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $id = $model->add($saveParams);
            if (!$id) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $saveRes = $model->where($where)->save($saveParams);
            if ($saveRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }
}
<?php
/**
 * 分销/地推
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-12
 * Time: 17:45
 */

namespace App\Modules\Disribution;


use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Models\DistributionInvitationModel;
use App\Models\DistributionRelationModel;
use App\Models\PullNewAmountLogModel;
use App\Models\UserDistributionModel;
use App\Modules\Orders\OrdersModule;
use App\Modules\Users\UsersModule;
use Think\Model;

class DistributionModule extends BaseModel
{
    /**
     * 分销-用户分销记录列表
     * @param array $params
     * -string orderNo 订单号
     * -string goodsName 商品名
     * -string paymentUserPhone 下单人手机号
     * -datetime addtimeStart 时间-开始时间
     * -datetime addtimeEnd 时间-结束时间
     * -string invitationUserId 邀请人id
     * -string invitationName 邀请人名称
     * -string inviteeName 受邀人名称
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     */
    public function getDistributionLogList(array $params)
    {
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $where = "orders.orderFlag=1 ";
        $where .= " and invitationUser.userFlag=1 ";
        $where .= " and inviteeUser.userFlag=1 ";
        $where .= " and paymentUser.userFlag=1 ";
        if (!empty($params['orderNo'])) {
            $where .= "and orders.orderNo like '%{$params['orderNo']}%' ";
        }
        if (!empty($params['goodsName'])) {
            $where .= "and goods.goodsName like '%{$params['goodsName']}%' ";
        }
        if (!empty($params['paymentUserPhone'])) {
            $where .= "and paymentUser.userPhone like '%{$params['paymentUserPhone']}%' ";
        }
        if (!empty($params['paymentUserName'])) {
            $where .= "and paymentUser.userName like '%{$params['paymentUserName']}%' ";
        }
        if (!empty($params['addtimeStart'])) {
            $where .= "and dis.addtime >= '{$params['addtimeStart']}' ";
        }
        if (!empty($params['addtimeEnd'])) {
            $where .= "and dis.addtime <= '{$params['addtimeEnd']}' ";
        }
        if (!empty($params['invitationName'])) {
            $where .= "and invitationUser.userName like '%{$params['invitationName']}%' ";
        }
        if (!empty($params['invitationUserId'])) {
            $where .= "and invitationUser.userId = '{$params['invitationUserId']}' ";
        }
        if (!empty($params['inviteeName'])) {
            $where .= "and inviteeUser.userName like '%{$params['inviteeName']}%' ";
        }
        $field = 'dis.id,dis.goodsId,dis.userId as invitationUserId,dis.UserToId as inviteeUserId,dis.orderId,dis.distributionLevel,dis.distributionMoney,dis.buyerId as paymentUserId,dis.addtime,dis.updateTime';
        $field .= ',goodsName,goodsImg';
        $field .= ',orders.orderNo';
        $field .= ',paymentUser.userName as paymentUserName,paymentUser.userPhone as paymentUserPhone,paymentUser.userPhoto as paymentUserPhoto ';
        $field .= ',invitationUser.userName as invitationUserName,invitationUser.userPhone as invitationUserPhone,invitationUser.userPhoto as invitationUserPhoto ';
        $field .= ',inviteeUser.userName as inviteeUserName,inviteeUser.userPhone as inviteeUserPhone,inviteeUser.userPhoto as inviteeUserPhoto ';
        $disModel = new UserDistributionModel();
        $prefix = $disModel->tablePrefix;
        $sql = $disModel
            ->alias('dis')
            ->join("left join {$prefix}goods goods on goods.goodsId=dis.goodsId")
            ->join("left join {$prefix}orders orders on orders.orderId=dis.orderId")
            ->join("left join {$prefix}users paymentUser on paymentUser.userId=dis.buyerId")
            ->join("left join {$prefix}users invitationUser on invitationUser.userId=dis.userId")
            ->join("left join {$prefix}users inviteeUser on inviteeUser.userId=dis.UserToId")
            ->where($where)
            ->field($field)
            ->buildSql();
        $result = $this->pageQuery($sql, $page, $pageSize);

        $sql = $disModel
            ->alias('dis')
            ->join("left join {$prefix}goods goods on goods.goodsId=dis.goodsId")
            ->join("left join {$prefix}orders orders on orders.orderId=dis.orderId")
            ->join("left join {$prefix}users paymentUser on paymentUser.userId=dis.buyerId")
            ->join("left join {$prefix}users invitationUser on invitationUser.userId=dis.userId")
            ->join("left join {$prefix}users inviteeUser on inviteeUser.userId=dis.UserToId")
            ->where($where)
            ->field($field)
            ->buildSql();
        $result2 = $this->query($sql);
        $totalDistributionMoney = 0;
        if (!empty($result2)) {
            $totalDistributionMoneyArr = array_column($result2, 'distributionMoney');
            $totalDistributionMoney = array_sum($totalDistributionMoneyArr);
        }
        $result['count'] = array(
            'totalDistributionMoney' => $totalDistributionMoney,
        );
        return $result;
    }

    /**
     * 分销/地推-邀请记录-手机号查找
     * @param string $phone 手机号
     * @param int $dataType 数据类型【1：分销邀请记录|2：地推邀请记录】
     * @return array
     * */
    public function getDistributionInvitationDetailByPhone(string $phone, $dataType = 1)
    {
        $invitationLogModel = new DistributionInvitationModel();
        $prefix = $invitationLogModel->tablePrefix;
        $field = 'invitation.id,invitation.userId,invitation.userPhone,invitation.dataType';
        $field .= ',users.balance';
        $where = array();
        $where['invitation.userPhone'] = $phone;
        $where['invitation.dataType'] = $dataType;
        $detail = $invitationLogModel
            ->alias('invitation')
            ->join("left join {$prefix}users users on users.userId=invitation.userId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($detail)) {
            return array();
        }
        return $detail;
    }

    /**
     * 地推-地推收益明细-详情-多条件查找
     * @param int $userId 用户id
     * @param string $orderToken 订单标识id
     * @param int $dataType 数据类型【1：邀请成功注册|2：成功下单】
     * @param int $status 结算状态【0：待结算|1：已结算|2：已取消】
     * @return array
     * */
    public function getPullNewAmountLogByParams(int $userId, string $orderToken, int $dataType, int $status)
    {
        $amountLogModel = new PullNewAmountLogModel();
        $where = array();
        $where['userId'] = $userId;
        $where['orderToken'] = $orderToken;
        $where['dataType'] = $dataType;
        $where['status'] = $status;
        $detail = $amountLogModel->where($where)->find();
        if (empty($detail)) {
            return array();
        }
        return $detail;
    }

    /**
     * 地推-地推收益明细-保存
     * @param array $params
     * -wst_pull_new_amount_log表字段
     * @param object $trans
     * @return int
     * */
    public function savePullNewAmountLog(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $saveParams = array(
            'userId' => null,
            'inviterId' => null,
            'dataType' => null,
            'orderToken' => null,
            'amount' => null,
            'status' => null,
            'updateTime' => null,
        );
        parm_filter($saveParams, $params);
        $model = new PullNewAmountLogModel();
        if (empty($params['id'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $id = $model->add($saveParams);
            if (empty($id)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }

    /**
     * 分销-获取用户的分销关系(一级二级分销关系)
     * @param int $userId 用户id
     * @return array
     * */
    public function getUserDistributionRelationList(int $userId)
    {
        $model = new DistributionRelationModel();
        $where = array(
            'userId' => $userId
        );
        $result = $model->where($where)->select();
        return $result;
    }

    /**
     * 分销-分销金记录-保存
     * @param array $params
     * -wst_user_distribution表字段
     * @param object $trans
     * */
    public function saveUserDistribution(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $saveParams = array(
            'goodsId' => null,
            'userId' => null,
            'UserToId' => null,
            'orderId' => null,
            'distributionLevel' => null,
            'distributionMoney' => null,
            'state' => null,
            'buyerId' => null,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($saveParams, $params);
        $model = new UserDistributionModel();
        if (empty($params['id'])) {
            $saveParams['addtime'] = date('Y-m-d H:i:s');
            $id = $model->add($saveParams);
            if (empty($id)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $params['id'];
            $where = array(
                'id' => $id
            );
            $saveRes = $model->where($where)->save($saveParams);
            if ($saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }

    /**
     * 分销记录-详情-多条件查找
     * @param array $params
     * -int goodsId 商品id
     * -int orderId 订单id
     * -int userId 邀请人id
     * -int UserToId 受邀请人id
     * @return array
     * */
    public function getUserDistributionDetailParams(array $params)
    {
        $where = array(
            'goodsId' => null,
            'orderId' => null,
            'userId' => null,
            'UserToId' => null,
        );
        parm_filter($where, $params);
        $model = new UserDistributionModel();
        $detail = $model->where($where)->find();
        return (array)$detail;
    }

    /**
     * 商品分销金额-取消
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param object $trans
     * @return array
     * */
    public function cancelOrderGoodsDistribution(int $orderId, int $goodsId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $orderModule = new OrdersModule();
        $orderField = 'orderId,userId';
        $orderDetail = $orderModule->getOrderInfoById($orderId, $orderField, 2);
        if (empty($orderDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $userId = $orderDetail['userId'];
        $distributionList = $this->getUserDistributionRelationList($userId);
        if (empty($distributionList)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true, ExceptionCodeEnum::SUCCESS, 'success', '无分销记录，无需处理');
        }
        $userModule = new UsersModule();
        foreach ($distributionList as $detail) {
            $invitationUserId = $detail['pid'];
            $where = array(
                'goodsId' => $goodsId,
                'orderId' => $orderId,
                'userId' => $invitationUserId,
                'UserToId' => $userId,
            );
            $userDistributionDetail = $this->getUserDistributionDetailParams($where);
            if (!empty($userDistributionDetail)) {
                $distributionParams = array(
                    'goodsId' => $goodsId,
                    'userId' => $invitationUserId,
                    'UserToId' => $userId,
                    'orderId' => $orderId,
                    'distributionLevel' => $userDistributionDetail['userDistributionDetail'],
                    'distributionMoney' => -$userDistributionDetail['distributionMoney'],
                    'state' => 2,
                    'buyerId' => $userId,
                );
                $res = $this->saveUserDistribution($distributionParams, $dbTrans);
                if (empty($res)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '分销金额退还失败');
                }
                $decRes = $userModule->decDistributionMoney($invitationUserId, $userDistributionDetail['distributionMoney'], $dbTrans);
                if (!$decRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '用户分销金额更新失败');
                }
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }
}
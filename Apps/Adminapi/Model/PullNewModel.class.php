<?php

namespace Adminapi\Model;

use http\Params;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 地推管理
 */
class PullNewModel extends BaseModel
{
    /**
     * 获取地推列表
     * @param array $params <p>
     * string userName 邀请人姓名
     * string userPhone 邀请人手机号
     * string usersToIdUserName 受邀人名称
     * string usersToIdUserUserPhone 受邀人手机号
     * string startDate 拉新时间-开始时间
     * string endDate 拉新时间-结束时间
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     */
//    public function getPullNewList(array $params)
//    {
//        $page = $params['page'];
//        $pageSize = $params['pageSize'];
//        $whereFind = [];
//        $whereFind['dataType'] = 2;
//        $whereFind['users.userName'] = function ()use($params){
//            if(empty($params['userName'])){
//                return null;
//            }
//            return ['like',"%{$params['userName']}%",'and'];
//        };
//        $whereFind['users.userPhone'] = function ()use($params){
//            if(empty($params['userPhone'])){
//                return null;
//            }
//            return ['like',"%{$params['userPhone']}%",'and'];
//        };
//        $whereFind['distribution.userPhone'] = function ()use($params){
//            if(empty($params['usersToIdUserUserPhone'])){
//                return null;
//            }
//            return ['like',"%{$params['usersToIdUserUserPhone']}%",'and'];
//        };
//        $whereFind['usersToId.userName'] = function ()use($params){
//            if(empty($params['usersToIdUserName'])){
//                return null;
//            }
//            return ['like',"%{$params['usersToIdUserName']}%",'and'];
//        };
//        $whereFind['distribution.addTime'] = function ()use($params){
//            if(empty($params['startDate']) || empty($params['endDate'])){
//                return null;
//            }
//            return ['between',"{$params['startDate']}'and'{$params['endDate']}",'and'];
//        };
//        where($whereFind);
//        $whereFind = rtrim($whereFind,' and ');
//        $distributionInvitationTab = M('distribution_invitation distribution');
//        $field = 'distribution.id,distribution.addTime as invitationTime,distribution.userPhone as usersToIdUserUserPhone';
//        $field .= ',users.userId,users.userName,users.userPhone,users.userPhoto';
//        $field .= ',usersToId.userId as usersToId,usersToId.userName as usersToIdUserName,usersToId.userPhoto as usersToIdUserUserPhoto ';
//        $list = $distributionInvitationTab
//            ->join('left join wst_pull_new_log log on log.inviterId=distribution.userId')
//            ->join('left join wst_users users on users.userId=distribution.userId')
//            ->join('left join wst_users usersToId on usersToId.userId=log.userId')
//            ->where($whereFind)
//            ->field($field)
//            ->limit(($page - 1) * $pageSize, $pageSize)
//            ->group('distribution.userPhone')
//            ->order('distribution.id desc')
//            ->select();
//        $usersTab = M('users');
//        $ordersTab = M('orders');
//        if(!empty($list)){
//            foreach ($list as $key=>&$value){
//                //dataType 拉新状态【0：未注册|1：已注册|2：已下单】
//                $value['dataType'] = 0;
//                $where = [];
//                $where['userPhone'] = $value['usersToIdUserUserPhone'];
//                $where['userFlag'] = 1;
//                $userInfo = $usersTab->where($where)->field('userId,userName,userPhone')->find();
//                $value['usersToId'] = (int)$value['usersToId'];
//                $value['usersToIdUserName'] = (string)$value['usersToIdUserName'];
//                $value['usersToIdUserUserPhoto'] = (string)$value['usersToIdUserUserPhoto'];
//                if(!empty($userInfo)){
//                    $value['dataType'] = 1;
//                    $where = [];
//                    $where['userId'] = $userInfo['userId'];
//                    $where['orderFlag'] = 1;
//                    $where['isPay'] = 1;
//                    $orderCount = $ordersTab->where($where)->count();
//                    if($orderCount > 0 ){
//                        $value['dataType'] = 2;
//                    }
//                }
//            }
//            unset($value);
//        }
//        $count = $distributionInvitationTab
//            ->join('left join wst_pull_new_log log on log.inviterId=distribution.userId')
//            ->join('left join wst_users users on users.userId=distribution.userId')
//            ->join('left join wst_users usersToId on usersToId.userId=log.userId')
//            ->where($whereFind)
//            ->count();
//        if(empty($list)){
//            $list = [];
//        }
//        $data = [];
//        $data['totalPage'] = ceil($count / $pageSize);//总页数
//        $data['currentPage'] = $page;//当前页码
//        $data['total'] = (int)$count;//总数量
//        $data['pageSize'] = $pageSize;//页码条数
//        $data['root'] = (array)$list;
//        $rs['code'] = 0;
//        $rs['msg'] = '操作成功';
//        $rs['data'] = $data;
//        return $rs;
//    }

    /**
     * 获取地推列表  注：上面注释的是原来的，因为数据量太大，联表查询需要变更为单表查询
     * @param array $params <p>
     * string userName 邀请人姓名
     * string userPhone 邀请人手机号
     * string usersToIdUserName 受邀人名称
     * string usersToIdUserUserPhone 受邀人手机号
     * string startDate 拉新时间-开始时间
     * string endDate 拉新时间-结束时间
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     */
    public function getPullNewList(array $params)
    {
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $whereParams = array();
        $whereParams['dataType'] = 2;
        $usersTab = M('users');
        // $usersTab = $usersTab->where("pullNewPermissions",1);
        if (!empty($params['userName'])) {
            $usersWhere = array(
                'userName' => array('like', "%{$params['userName']}%"),
                'pullNewPermissions'=>1
            );
            $usersList = $usersTab->field('userId')->where($usersWhere)->select();
            if (!empty($usersList)) {
                $usersIdArr = array_column($usersList, 'userId');
                $whereParams["userId"] = array('in', $usersIdArr);
            }
        }
        if (!empty($params['userPhone'])) {
            $usersWhere = array(
                'userPhone' => array('like', "%{$params['userPhone']}%"),
                'pullNewPermissions'=>1
            );
            $usersList = $usersTab->field('userId')->where($usersWhere)->select();
            if (!empty($usersList)) {
                $usersIdArr = array_column($usersList, 'userId');
                $whereParams["userId"] = array('in', $usersIdArr);

            }
        }
        if (!empty($params['usersToIdUserName'])) {
            $usersWhere = array(
                'userName' => array('like', "%{$params['usersToIdUserName']}%"),
                'pullNewPermissions'=>1
            );
            $usersList = $usersTab->field('userPhone')->where($usersWhere)->select();
            if (!empty($usersList)) {
                $usersPhoneArr = array_column($usersList, 'userPhone');
                $whereParams["userPhone"] = array('in', $usersPhoneArr);
            }
        }
        if (!empty($params['usersToIdUserUserPhone'])) {
            $whereParams['userPhone'] = array('like', "%{$params['usersToIdUserUserPhone']}%");
        }
        if (!empty($params['startDate']) || !empty($params['endDate'])) {
            //前端传的日期格式有误,这里直接处理下
            $params['startDate'] = explode(" ", $params['startDate'])[0] . ' 00:00:00';
            $params['endDate'] = explode(" ", $params['endDate'])[0] . ' 23:59:59';
            $whereParams['addTime'] = array('between', array("{$params['startDate']}", "{$params['endDate']}"));
        }
        $distributionInvitationTab = M('distribution_invitation');
        $field = 'id,addTime as invitationTime,userPhone as usersToIdUserUserPhone,userId';
        $list = $distributionInvitationTab
            ->where($whereParams)
            ->field($field)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->group('userPhone')
            ->order('id desc')
            ->select();
        $count = $distributionInvitationTab
            ->where($whereParams)
            ->count('distinct(userPhone)');
        $ordersTab = M('orders');
        foreach ($list as &$value) {
            //邀请人
            $usersRow = $usersTab->where(array('userId' => $value['userId']))->find();
            $value['userName'] = (string)$usersRow['userName'];
            $value['userPhone'] = (string)$usersRow['userPhone'];
            $value['userPhoto'] = (string)$usersRow['userPhoto'];
            //受邀人
            $usersToIdRow = $usersTab->where(array('loginName' => $value['usersToIdUserUserPhone']))->find();
            $value['usersToId'] = (string)$usersToIdRow['userId'];
            $value['usersToIdUserName'] = (string)$usersToIdRow['userName'];
            $value['usersToIdUserUserPhoto'] = (string)$usersToIdRow['userPhoto'];

            $value['dataType'] = 0;//dataType 拉新状态【0：未注册|1：已注册|2：已下单】
            if (!empty($usersToIdRow)) {
                $value['dataType'] = 1;
                $ordersWhere = [];
                $ordersWhere['userId'] = $usersToIdRow['userId'];
                $ordersWhere['orderFlag'] = 1;
                $ordersWhere['isPay'] = 1;
                $orderCount = $ordersTab->where($ordersWhere)->count();
                if ($orderCount > 0) {
                    $value['dataType'] = 2;
                }
            }
        }
        unset($value);
        $data = [];
        $data['totalPage'] = ceil($count / $pageSize);//总页数
        $data['currentPage'] = $page;//当前页码
        $data['total'] = (int)$count;//总数量
        $data['pageSize'] = $pageSize;//页码条数
        $data['root'] = (array)$list;
        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $data;
        return $rs;
    }
}
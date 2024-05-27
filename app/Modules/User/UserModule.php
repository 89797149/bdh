<?php

namespace App\Modules\User;


use App\Enum\ExceptionCodeEnum;
use App\Models\UserModel;
use App\Models\BaseModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;

/**
 * 职员类,该类只为UserServiceModule类服务
 * Class UsersModule
 * @package App\Modules\User
 */
class UserModule extends BaseModel
{
    /**
     * 根据职员id获取用户详情
     * @param int $id 职员id
     * @param string $field 表字段
     * @return array
     * */
    public function getUserDetailById(int $id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $user_model = new UserModel();
        $where = array(
            'id' => $id,
        );
        $result = $user_model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('职员不存在')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 根据参数获取用户详情
     * @param array $params <p>
     * int userId 用户id
     * string loginName 用户登陆名
     * string loginPwd 登陆密码
     * string WxUnionid 微信unionid
     * </p>
     * @return array
     * */
    public function getUsersDetailByWhere(array $params, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        $where = array(
            'userId' => null,
            'loginName' => null,
            'loginPwd' => null,
            'WxUnionid' => null,
            'userFlag' => 1
        );
        parm_filter($where, $params);
        $result = (array)$users_model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户不存在')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 获取受邀详情
     * @param array $params <p>
     * int id 邀请记录id
     * int inviterId 邀请人id
     * string inviterPhone 邀请人手机号
     * string inviteePhone 受邀人手机号
     * </p>
     * */
    public function getRecordInfoByWhere(array $params, $field = '')
    {
        $response = LogicResponse::getInstance();
        $invite_cache_record_model = new InviteCacheRecordModel();
        $where = array(
            'id' => null,
            'inviterId' => null,
            'inviterPhone' => null,
            'inviteePhone' => null,
            'icrFlag' => 1,
        );
        parm_filter($where, $params);
        $result = (array)$invite_cache_record_model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 修改用户信息 PS:该方法谁用谁扩展字段
     * @param int $userId
     * @param array $save 表字段
     * @param object $trans 事务M()
     * @return array
     * */
    public function updateUsersInfo(int $userId, $save = array(), $trans = null)
    {
        $response = LogicResponse::getInstance();
        $users_model = new UsersModel();
        if (empty($trans)) {
            $model = new Model();
            $model->startTrans();
        } else {
            $model = $trans;
        }
        if (empty($save)) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('参数错误')->toArray();
        }
        $where = array(
            'userId' => $userId
        );
        $result = $users_model->where($where)->save($save);
        if ($result === false) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('修改失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 根据memberToken获取用户信息
     * */
    public function getUsersInfoByMemberToken()
    {
        $response = LogicResponse::getInstance();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (empty($memberToken)) {
            $memberToken = $headers['Membertoken'];
        }
        if (empty($memberToken)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('MemberToken失效')->toArray();
        }
        $users_info = userTokenFind($memberToken, 86400 * 30);//查询token
        if (empty($users_info['userId'])) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无相关数据')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($users_info)->toArray();
    }

//    /**
//     * 获取用户所属路线
//     */
//    public function getPsdLineByUserId(int $userId):object
//    {
//
//        return (object)[];
//    }
//
//    /***
//     * 新增或更新所属路线
//     * 存在更新 否则新增关系绑定
//     */
//    public function addUpdatePasLineByUserId(int $userId,int $lineId):bool
//    {
//        return true;
//    }
//
//    /***
//     * 获取指定用户的所属区域
//     */
//    public function getPsdReginRange(int $userId):object
//    {
//        return (object)[];
//    }
//
//    /***
//     * 新增或更新所属区域
//     * 存在更新 否则新增关系绑定
//     */
//    public function addUpdatePsdReginRangeByUserId():bool
//    {
//        return true;
//    }


}
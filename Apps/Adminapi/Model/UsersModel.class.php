<?php

namespace Adminapi\Model;

use App\Enum\ExceptionCodeEnum;
use App\Modules\Map\MapModule;
use App\Modules\Rank\RankModule;
use App\Modules\Shops\ShopsServiceModule;
use App\Modules\Users\UsersModule;
use App\Modules\Users\UsersServiceModule;
use App\Models\UsersModel as UsersTabModel;
use App\Models\ShopsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员服务类
 */
class UsersModel extends BaseModel
{
    /**
     * @param $userData
     * @return array
     * 新增会员信息
     */
    public function insert($userData)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        //检测账号
        $hasLoginName = self::checkLoginKey(I("loginName"));
        if ($hasLoginName['status'] <= 0) {
            $rd['msg'] = '账号已存在';
            return $rd;
        }
        if (I("userPhone") != '') {
            $hasUserPhone = self::checkLoginKey(I("userPhone"));
            if ($hasUserPhone['status'] <= 0) {
                $rd['msg'] = '账号已存在';
                return $rd;
            }
        }
        if (I("userEmail") != '') {
            $hasUserEmail = self::checkLoginKey(I("userEmail"));
            if ($hasUserEmail['status'] <= 0) {
                $rd['msg'] = '账号已存在';
                return $rd;
            }
        }
        //创建数据
        $id = I("id", 0);
        $data = array();
        $data["loginName"] = I("loginName");
        $data["loginSecret"] = rand(1000, 9999);
        $data["loginPwd"] = md5(I('loginPwd') . $data['loginSecret']);
        if ($this->checkEmpty($data)) {
            $data["userPhoto"] = I("userPhoto");
            $data["userName"] = I("userName");
            $data["userStatus"] = I("userStatus", 0);
            $data["userType"] = (int)I("userType", 0);
            $data["userSex"] = (int)I("userSex", 0);
            $data["userEmail"] = I("userEmail");
            $data["userPhone"] = I("userPhone");
            $data["userQQ"] = I("userQQ");
            $data["userScore"] = I("userScore", 0);
            $data["userTotalScore"] = I("userTotalScore", 0);
            $data["userFlag"] = 1;
            $data["balance"] = I("balance", 0);
            $data["expireTime"] = I("expireTime");
            $data['firstOrder'] = 1;//新添加用户为首单状态
            $data["pullNewPermissions"] = I("pullNewPermissions", -1);//拉新权限【-1：关闭|1：开启】
            $data["pullNewRegister"] = I("pullNewRegister", 0);//拉新奖励规则-邀请成功注册
            $data["pullNewOrder"] = I("pullNewOrder", 0);//拉新奖励规则-用户成功下单
            if ($data['pullNewPermissions'] == -1) {
                $data["pullNewRegister"] = 0;
                $data["pullNewOrder"] = 0;
            }
            if ($data['pullNewRegister'] <= 0) {
                $data['pullNewRegister'] = 0;
            }
            if ($data['pullNewOrder'] <= 0) {
                $data['pullNewOrder'] = 0;
            }
            $data["createTime"] = date('Y-m-d H:i:s');
            $rs = $this->add($data);

            //后加等级
//            !empty(I('rankId'))?$rankId=I('rankId'):$rankId=0;
//            $rankId = I('rankId', 0);
//            //新加等级
//            $rankData['rankId'] = $rankId;
//            $rankData['userId'] = $this->getLastInsID();
//            $rankData['state'] = 1;
//            M('rank_user')->add($rankData);

            if (false !== $rs) {
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
                $describe = "[{$userData['loginName']}]新增会员:[{$data["userName"]}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 1);
            }
        }
        return $rd;
    }

    /**
     * @param $userData
     * @return array
     * 修改会员信息
     */
    public function edit($userData)
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        $id = (int)I('id', 0);
        $users_service_module = new UsersServiceModule();
        $users_result = $users_service_module->getUsersDetailById($id);
        $users_data = $users_result['data'];
        //检测账号
//        if (I("userPhone") != '') {
//            $hasUserPhone = self::checkLoginKey(I("userPhone"), $id);
//            if ($hasUserPhone['status'] <= 0) {
//                $rd['msg'] = '账号不存在';
//                return $rd;
//            }
//        }
//        if (I("userEmail") != '') {
//            $hasUserEmail = self::checkLoginKey(I("userEmail"), $id);
//            if ($hasUserEmail['status'] <= 0) {
//                $rd['msg'] = '账号不存在';
//                return $rd;
//            }
//
//        }
        //修改数据
        $data = array();
        $data["userScore"] = (int)I("userScore", 0);
//        $data["userTotalScore"] = (int)I("userTotalScore", 0);
        if ($this->checkEmpty($data, true)) {
//            $data["userName"] = I("userName");不做修改
            $data["userPhoto"] = I("userPhoto");
            $data["userSex"] = (int)I("userSex", 0);
            $data["userQQ"] = I("userQQ");
            $data["userName"] = I("userName");
            $data["userPhone"] = I("userPhone");
            $data["userEmail"] = I("userEmail");
            $data["expireTime"] = I("expireTime");
            $data["balance"] = I("balance", 0);
            $data["pullNewPermissions"] = I("pullNewPermissions", -1);//拉新权限【-1：关闭|1：开启】
            $data["pullNewRegister"] = I("pullNewRegister", 0);//拉新奖励规则-邀请成功注册
            $data["pullNewOrder"] = I("pullNewOrder", 0);//拉新奖励规则-用户成功下单
            if ($data['pullNewPermissions'] == -1) {
                $data["pullNewRegister"] = 0;
                $data["pullNewOrder"] = 0;
            }
            if ($data['pullNewRegister'] <= 0) {
                $data['pullNewRegister'] = 0;
            }
            if ($data['pullNewOrder'] <= 0) {
                $data['pullNewOrder'] = 0;
            }
            $data['distributionAuthority'] = I('distributionAuthority');//分销权限(-1:关闭 1:开启)
            $rs = $this->where("userId=" . $id)->save($data);

            //后加等级
//            !empty(I('rankId'))?$rankId=I('rankId'):$rankId=0;
//            $rankId = I('rankId', 0);
//            $rankUser = M('rank_user')->where("userId='" . $id . "' AND state=1")->find();
//            if ($rankUser) {
//                //更新等级
//                $rankData['rankId'] = $rankId;
//                M('rank_user')->where("id='" . $rankUser['id'] . "'")->save($rankData);
//            } else {
//                //新加等级
//                $rankData['rankId'] = $rankId;
//                $rankData['userId'] = $id;
//                $rankData['state'] = 1;
//                M('rank_user')->add($rankData);
//            }
            if (false !== $rs) {
                //如果更新了手机号也要把关联的店铺手机号更新下
                if (!empty($data['userPhone'])) {
                    if ($users_data['userPhone'] != $data['userPhone']) {
                        //更新相关店铺的手机号
                        $users_model = new UsersModel();
                        $where = array(
                            'userPhone' => $users_data['userPhone'],
                            'userFlag' => 1,
                        );
                        $users_list = $users_model->where($where)->field('userId')->select();
                        $users_id = array_column($users_list, 'userId');
                        $shop_model = new ShopsModel();
                        $where = array(
                            'userId' => array('IN', $users_id),
                            'shopFlag' => 1
                        );
                        $shop_list = $shop_model->where($where)->field('userId,shopId')->select();
                        if (!empty($shop_list)) {
                            $save = array(
                                'userPhone' => $data['userPhone']
                            );
                            foreach ($shop_list as $value) {
                                $users_service_module->updateUsersInfo($value['userId'], $save);
                            }
                        }
                    }
                }
                $rd['code'] = 0;
                $rd['msg'] = '操作成功';
                $describe = "[{$userData['loginName']}]编辑了会员信息:[{$users_data['userName']}]";
                addOperationLog($userData['loginName'], $userData['staffId'], $describe, 3);
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function get()
    {
        return $this->where("userId=" . (int)I('id'))->find();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $userInfo = M('users')->where("userId=" . (int)I('id'))->find();
        if (!empty($userInfo)) {
            if (empty($userInfo['expireTime']) || $userInfo['expireTime'] == "0000-00-00 00:00:00") {
                $userInfo['expireTime'] = "";
            }
            $userInfo['has_rank'] = 0;//是否存在身份(0:不存在 1:存在)
            $userInfo['rankDetail'] = (object)array();
            $rankModule = new RankModule();
            $userRankDetail = $rankModule->getUserRankDetialByUserId($userInfo['userId']);
            if (!empty($userRankDetail)) {
                $userInfo['has_rank'] = 1;
                $userInfo['rankDetail'] = array(
                    'rankId' => $userRankDetail['rankId'],
                    'rankName' => $userRankDetail['rankName'],
                );
            }
        }

        // $usermod = new UsersTabModel();
        // $usermod->


        return (array)$userInfo;
    }

    /**
     * 分页列表
     */
    public function queryByPage($page = 1, $pageSize = 15)
    {
        ini_set("memory_limit", -1);
        set_time_limit(0);//0表示没有限制
        $map = array();

        /*$sql = "select * from __PREFIX__users where userFlag=1 ";
	 	if(I('loginName')!='')$sql.=" and loginName LIKE '%".WSTAddslashes(I('loginName'))."%'";
	 	if(I('userPhone')!='')$sql.=" and userPhone LIKE '%".WSTAddslashes(I('userPhone'))."%'";
	 	if(I('userEmail')!='')$sql.=" and userEmail LIKE '%".WSTAddslashes(I('userEmail'))."%'";
	 	if(I('userType',-1)!=-1)$sql.=" and userType=".I('userType',-1);
	 	$sql.="  order by userId desc";
		$rs = $this->pageQuery($sql);
		//计算等级
		if(count($rs)>0){
			$m = M('user_ranks');
			$urs = $m->select();
			foreach ($rs['root'] as $key=>$v){
				foreach ($urs as $rkey=>$rv){
					if($v['userTotalScore']>=$rv['startScore'] && $v['userTotalScore']<$rv['endScore']){
					   $rs['root'][$key]['userRank'] = $rv['rankName'];
					}
				}
			}
		}*/
        $withSql = '';
        /*对已删除的数据进行过滤*/
//        $sql = "SELECT u.*,user_ranks.rankName,user_ranks.rankId FROM wst_users u LEFT JOIN wst_rank_user rank_user ON rank_user.userId=u.userId LEFT JOIN wst_user_ranks user_ranks ON user_ranks.rankId=rank_user.rankId  ".$withSql." WHERE u.userFlag=1";
        /*删除的数据不进行过滤*/
        $sql = "SELECT u.*,user_ranks.rankName FROM wst_users u left JOIN wst_rank_user rank_user ON rank_user.userId=u.userId left JOIN wst_user_ranks user_ranks ON user_ranks.rankId=rank_user.rankId  where 1=1 " . $withSql . " ";

//         if(I('userType',-1) == 2){
//             $sql.=" AND u.expireTime	> '".date('Y-m-d H:i:s',time())."'";
//         }
//         if(I('loginName')!='')$sql.=" and u.loginName LIKE '%".WSTAddslashes(I('loginName'))."%'";
//         if(I('userPhone')!='')$sql.=" and u.userPhone LIKE '%".WSTAddslashes(I('userPhone'))."%'";
//         if(I('userEmail')!='')$sql.=" and u.userEmail LIKE '%".WSTAddslashes(I('userEmail'))."%'";
//         if(I('userType',-1)!=-1 && I('userType',-1)!=2)$sql.=" and u.userType=".I('userType',-1);
        //余额区间条件
        $startBalance = I('startBalance');
        $endBalance = I('endBalance');
        if (!empty($startBalance) and !empty($endBalance)) {
            $sql .= " and $startBalance <= u.balance and u.balance <= $endBalance ";
        }
        //余额区间条件
        $startUserScore = I('startUserScore');
        $endUserScore = I('endUserScore');
        if (!empty($startUserScore) and !empty($endUserScore)) {
            $sql .= " and $startUserScore <= u.userScore and u.userScore <= $endUserScore ";
        }
        //用户账号
        $loginName = I('loginName');
        if (!empty($loginName)) {
            $sql .= " and u.loginName LIKE '%" . WSTAddslashes(I('loginName')) . "%'";
        }
        //用户名称/昵称
        $userName = I('userName');
        if (!empty($userName)) {
            $sql .= " and u.userName LIKE '%" . WSTAddslashes(I('userName')) . "%'";
        }
        //用户手机
        $userPhone = I('userPhone');
        if (!empty($userPhone)) {
            $sql .= " and u.userPhone LIKE '%" . WSTAddslashes(I('userPhone')) . "%'";
        }
        //用户来源  应该是 userFrom  前端传的是 userForm
        $userFrom = (int)I('userForm');
        if (!empty($userFrom)) {
            $sql .= " and u.userFrom = $userFrom";
        }
        //注册时间区间
        $startDate = I('startDate');
        $endDate = I('endDate');
        if (!empty($startDate) and !empty($endDate)) {
            $sql .= " and u.createTime between '{$startDate}' and '{$endDate}' ";
        }
        //用户邮箱
        $userEmail = I('userEmail');
        if (!empty($userEmail)) {
            $sql .= " and u.userEmail LIKE '%" . WSTAddslashes(I('userEmail')) . "%'";
        }
        //账号类型
        $userType = I('userType', -1);
        if (is_numeric($userType)) {
            //此处代码逻辑是根据老代码进行的复写 2020-5.4
            if ($userType == 1) {
                $sql .= " AND u.expireTime	> '" . date('Y-m-d H:i:s', time()) . "'";
            }
            if ($userType == 0) {
                $sql .= " AND u.expireTime	< '" . date('Y-m-d H:i:s', time()) . "' or u.expireTime is null";
            }
//            if ($userType != -1 && $userType != 2) {
//                $sql .= " and u.userType=" . $userType;
//            }
        }

        $sql .= "  ORDER BY u.userId desc";

        $export = (int)I('export');//1:导出
        $rankModule = new RankModule();
        if (empty($export)) {
            $rs = $this->pageQuery($sql, $page, $pageSize);
            if (!empty($rs['root'])) {
                foreach ($rs['root'] as $k => $v) {
                    $userId = $v['userId'];
                    $expireTimeState = -1;//会员过期状态【-1：失效|1：有效】【弃用】
                    $userType = 0;//会员类型临时判断【0:普通会员|1:付费会员】
                    $memberSurplusTime = 0;//会员剩余天数
                    if (!empty($v['expireTime']) && $v['expireTime'] > date('Y-m-d H:i:s', time())) {
                        $time = strtotime($v['expireTime']) - time();
                        $memberSurplusTime = ceil($time / (3600 * 24));
                        $expireTimeState = 1;
                        $userType = 1;
                    }
                    $rs['root'][$k]['userType'] = $userType;
                    $rs['root'][$k]['expireTimeState'] = $expireTimeState;
                    $rs['root'][$k]['memberSurplusTime'] = "还剩{$memberSurplusTime}天";
                    $orderPrice = M('orders')
                        ->where(['orderFlag' => 1, 'userId' => $v['userId'], 'isPay' => 1, 'isRefund' => 0, 'orderStatus' => 4])
                        ->field("sum(realTotalMoney) as orderPrice,count(orderId) as orderCount")
                        ->select();//消费金额 订单数量
                    $rs['root'][$k]['orderPrice'] = (string)number_format($orderPrice[0]['orderPrice'], '2');//消费金额
                    $rs['root'][$k]['orderCount'] = (int)$orderPrice[0]['orderCount'];//订单数量
                    $rs['root'][$k]['has_rank'] = 0;//是否存在身份(0:不存在 1:已存在)
                    $rs['root'][$k]['rankDetail'] = (object)array();
                    $userRankDetial = $rankModule->getUserRankDetialByUserId($userId);
                    if (!empty($userRankDetial)) {
                        $rs['root'][$k]['has_rank'] = 1;
                        $rs['root'][$k]['rankDetail'] = array(
                            'rankId' => $userRankDetial['rankId'],
                            'rankName' => $userRankDetial['rankName'],
                        );
                    }
                }
            }
            return $rs;
        } else {
            $result = array();
            $rs = $this->pageQuery($sql, 1, 1);
            $total = $rs['total'];//总条数
            $pageSize = 5000;
            $pageNum = (int)ceil($total / $pageSize);
            for ($i = 1; $i <= $pageNum; $i++) {
                $currResult = $this->pageQuery($sql, $i, $pageSize);
                if (!empty($currResult['root'])) {
                    $result = array_merge($result, $currResult['root']);
                }
            }
            //$data = $this->query($sql);
            $this->exportUserList($result);
        }
    }

//    /**
//     * 导出会员列表
//     * @param array $usersList 需要导出的会员数据
//     * @param array $params 前端传过来的参数
//     * */
//    public function exportUserList(array $usersList, array $params)
//    {
//        //拼接表格信息
//        $date = '';
//        $startDate = '';
//        $endDate = '';
//        if (!empty($params['startDate']) && !empty($params['endDate'])) {
//            $startDate = $params['startDate'];
//            $endDate = $params['endDate'];
//            $date = $startDate . ' - ' . $endDate;
//        }
//        //794px
//        $body = "<style type=\"text/css\">
//    table  {border-collapse:collapse;border-spacing:0;}
//    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
//    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
//</style>";
//        $body .= "
//            <tr>
//                <th style='width:40px;'>序号</th>
//                <th style='width:100px;'>用户名</th>
//                <th style='width:200px;'>手机号</th>
//                <th style='width:100px;'>邮箱</th>
//                <th style='width:50px;'>积分</th>
//                <th style='width:80px;'>用户历史消费积分</th>
//                <th style='width:150px;'>余额</th>
//                <th style='width:200px;'>会员过期时间</th>
//                <th style='width:200px;'>注册时间</th>
//                <th style='width:200px;'>最后登录时间</th>
//            </tr>";
//        $num = 0;
//        $rowspan = 1;
//        foreach ($usersList as $okey => $ovalue) {
//            $key = $okey + 1;
//            $body .=
//                "<tr align='center'>" .
//                "<td style='width:40px;' rowspan='{$rowspan}'>" . $key . "</td>" .//序号
//                "<td style='width:100px;' rowspan='{$rowspan}'>" . $ovalue['userName'] . "</td>" .//用户名
//                "<td style='width:200px;' rowspan='{$rowspan}'>" . $ovalue['userPhone'] . "</td>" .//手机号
//                "<td style='width:200px;' rowspan='{$rowspan}'>" . $ovalue['userEmail'] . "</td>" .//邮箱
//                "<td style='width:80px;' rowspan='{$rowspan}'>" . $ovalue['userScore'] . "</td>" .//积分
//                "<td style='width:50px;' >" . $ovalue['userTotalScore'] . "</td>" .//用户历史消费积分
//                "<td style='width:100px;' >" . $ovalue['balance'] . "</td>" .//余额
//                "<td style='width:200px;' >" . $ovalue['expireTime'] . "</td>" .//会员过期时间
//                "<td style='width:200px;'>" . $ovalue['createTime'] . "</td>" .//注册时间
//                "<td style='width:200px;'>" . $ovalue['lastTime'] . "</td>" .//最后登录时间
//                "</tr>";
//        }
//        $headTitle = "会员数据";
//        $filename = $headTitle . ".xls";
//        usePublicExport($body, $headTitle, $filename, $date);
//    }

    /**
     * 导出会员列表 上面注释的是原来的
     * @param array $usersList 需要导出的会员数据
     * @param array $params 前端传过来的参数
     * */
    public function exportUserList(array $usersList, array $params)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/user_list.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getComment('A2')->getText()->createTextRun('Total amount on the current invoice, including VAT.');
        $keyTag = 3;
        $serialNumber = 0;//序号
        foreach ($usersList as $key => $row) {
            $userType = "普通会员";
            if ($row['userType'] == 1) {
                $userType = "商户";
            }
            $userFrom = 'APP';//来源
            if ($row['userFrom'] == 3) {
                $userFrom = '小程序';
            }
            $expireTime = (string)$row['expireTime'];// 会员有效期
            if (empty($expireTime) || $expireTime == '0000-00-00 00:00:00') {
                $expireTime = '';
            }
            $distributionAuthority = "关闭";//分销权限(-1:关闭 1:开启)
            if ($row['distributionAuthority'] == 1) {
                $distributionAuthority = '开启';
            }
            $pullNewPermissions = "关闭";//拉新权限【-1：关闭|1：开启】
            if ($row['pullNewPermissions'] == 1) {
                $pullNewPermissions = "开启";
            }
            $userStatus = "禁用";
            if ($row['userStatus'] == 1) {
                $userStatus = "启用";
            }
            $serialNumber++;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $serialNumber);//序列号
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $row['userId']);//用户id
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $row['loginName']);//账号
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $row['userName']);//用户名称
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $userType);//用户类型
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $userFrom);//来源
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, (string)$row['rankName']);//身份
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, (float)$row['balance']);//余额
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, (float)$row['userScore']);//可用积分
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $expireTime);//会员有效期
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $distributionAuthority);//分销权限(-1:关闭 1:开启)
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $pullNewPermissions);//拉新权限(-1:关闭 1:开启)
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $userStatus);//账号状态[0:禁用|1:启用]
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $row['createTime']);//注册时间
            $keyTag++;
        }
        $savefileName = '会员列表' . date('Y-m-d H:i:s');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 获取列表
     */
    public function queryByList()
    {
        $sql = "select * from __PREFIX__users order by userId desc";
        $rs = $this->find($sql);
        return $rs;
    }

    /**
     * @param $userData
     * @return array
     * 删除会员
     */
    public function del($userData)
    {
        $userId = (int)I('userId', 0);
        if (empty($userId)) {
            return returnData(false, -1, 'error', '请选择操作的会员');
        }
        //获取用户类型
        $usersServiceModule = new UsersServiceModule();
        $userInfo = $usersServiceModule->getUsersDetailById($userId);
        if (empty($userInfo['data'])) {
            return returnData(false, -1, 'error', '用户不存在');
        }
        $userType = $userInfo['data']['userType'];
        $shopInfo = M('shops')->where(['userId' => $userId, 'shopFlag' => 1])->find();
        if (!empty($shopInfo)) {
            return returnData(false, -1, 'error', '当前会员是店铺管理员,请删除对应店铺后再做操作');
        }
        $rs = M('users')->where(" userId=" . $userId)->delete();
        if (false !== $rs) {
            M('distribution_invitation')->where(array(
                'userPhone' => $userInfo['data']['loginName']
            ))->delete();
            M('invite_cache_record')->where("inviterPhone = {$userInfo['data']['loginName']} or inviteePhone = {$userInfo['data']['loginName']}")->delete();
            //如果是商家还要下架他的商品
            if ($userType == 1) {
                $save = [];
                $save['shopFlag'] = -1;
                $save['shopStatus'] = -2;
                M('shops')->where(" userId=" . $userId)->save();
                $shopId = (int)$shopInfo['shopId'];
                $sql = "update __PREFIX__goods set isSale=0,goodsStatus=-1 where shopId=" . $shopId;
                $this->execute($sql);
            }
            $describe = "[{$userData['loginName']}]删除了会员:[{$userInfo['data']['userName']}]";
            addOperationLog($userData['loginName'], $userData['staffId'], $describe, 2);
        }
        return returnData(true);
    }

    /**
     * @param $val
     * @param int $id
     * @return array
     * 查询登录关键字
     */
    public function checkLoginKey($val, $id = 0)
    {
        $rd = array('status' => -1);
        if ($val == '') return $rd;
        $sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') and userFlag=1";
        $keyArr = array($val, $val, $val);
        if ($id > 0) {
            $sql .= " and userId!=" . $id;
        }
        $rs = $this->where($sql, $keyArr)->count();
        if ($rs == 0) $rd['status'] = 1;
        return $rd;
    }


    /**********************************************************************************************
     *                                             账号管理                                                                                                                              *
     **********************************************************************************************/
    /**
     * 获取账号分页列表
     */
    public function queryAccountByPage($page = 1, $pageSize = 15)
    {
        $sql = "select * from __PREFIX__users where userFlag=1 ";
        if (I('loginName') != '') $sql .= " and loginName LIKE '%" . WSTAddslashes(I('loginName')) . "%'";
        if (I('userStatus', -1) != -1) $sql .= " and userStatus=" . (int)I('userStatus', -1);
        if (I('userType', -1) != -1) $sql .= " and userType=" . (int)I('userType', -1);
        $sql .= "  order by userId desc";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        //计算等级
        if (count($rs) > 0) {
            $m = M('user_ranks');
            $urs = $m->select();
            foreach ($rs['root'] as $key => $v) {
                foreach ($urs as $rkey => $rv) {
                    if ($v['userTotalScore'] >= $rv['startScore'] && $v['userTotalScore'] < $rv['endScore']) {
                        $rs['root'][$key]['userRank'] = $rv['rankName'];
                    }
                }
            }
        }
        return $rs;
    }

    /**
     * 编辑账号状态
     */
    public function editUserStatus()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (I('id', 0) == 0) return $rd;
        $this->userStatus = (I('userStatus') == 1) ? 1 : 0;
        $rs = $this->where("userId=" . (int)I('id', 0))->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * 获取账号信息
     */
    public function getAccountById()
    {
        $rs = $this->where('userId=' . (int)I('id', 0))->getField('userId,loginName,userStatus,userType', 1);
        return current($rs);
    }

    /**
     * 修改账号信息
     */
    public function editAccount()
    {
        $rd = array('code' => -1, 'msg' => '操作失败', 'data' => array());
        if (I('id') == '') return $rd;
        $loginSecret = $this->where("userId=" . (int)I('id'))->getField('loginSecret');
        if (I('loginPwd') != '') $this->loginPwd = md5(I('loginPwd') . $loginSecret);
        $this->userStatus = (int)I('userStatus', 0);
        $rs = $this->where('userId=' . (int)I('id'))->save();
        if (false !== $rs) {
            $rd['code'] = 0;
            $rd['msg'] = '操作成功';
        }
        return $rd;
    }

    /**
     * 分页列表
     */
    public function queryByPageRecord($page = 1, $pageSize = 15)
    {
        $where = " WHERE 1=1 ";
        //$where .= " AND bur.burFlag=1 AND u.userFlag=1 AND sm.smFlag=1 ";
        $where .= " AND bur.burFlag=1 AND sm.smFlag=1";
        if (!empty(I('startDate'))) {
            if (count(explode('-', I('startDate'))) > 1) {
                $startDate = I('startDate');
            } else {
                $startDate = date('Y-m-d H:i:s', I('startDate'));
            }
            $where .= " and bur.buyTime > '" . $startDate . "'";
        }
        if (!empty(I('endDate'))) {
            if (count(explode('-', I('endDate'))) > 1) {
                $endDate = I('endDate');
            } else {
                $endDate = date('Y-m-d H:i:s', I('endDate'));
            }
            $where .= " and bur.buyTime <= '" . $endDate . "'";
        }
        if (!empty(I('userPhone'))) {
            $where .= " AND u.userPhone='" . trim(I('userPhone'), '+') . "'";
        }
        if (!empty(I('loginName'))) {
            $where .= " AND u.loginName='" . trim(I('loginName'), '+') . "'";
        }
        $sql = "SELECT bur.smId,bur.buyTime,u.userName,u.userPhone,sm.name,sm.money FROM " . __PREFIX__buy_user_record . " bur LEFT JOIN " . __PREFIX__users . " u ON u.userId=bur.userId LEFT JOIN " . __PREFIX__set_meal . " sm ON sm.smId=bur.smId " . $where;
        $sql .= " ORDER BY bur.buyTime DESC ";
        $rs = $this->pageQuery($sql, $page, $pageSize);
        $sql = "SELECT bur.smId,bur.buyTime,u.userName,u.userPhone,sm.name,sm.money FROM " . __PREFIX__buy_user_record . " bur LEFT JOIN " . __PREFIX__users . " u ON u.userId=bur.userId LEFT JOIN " . __PREFIX__set_meal . " sm ON sm.smId=bur.smId " . $where;
        $allList = $this->query($sql);
        $totalMoeny = 0;
        foreach ($allList as $val) {
            $totalMoeny += $val['money'];
        }
        $rs['totalMoney'] = number_format($totalMoeny, 2, ".", "");
        return $rs;
    }

    /**
     * 用户收货地址-删除
     * @param int $addressId 地址id
     * @return array
     * */
    public function delUserAddress(int $addressId)
    {
        $module = new UsersModule();
        $saveParams = array(
            'addressId' => $addressId,
            'addressFlag' => -1,
        );
        $saveId = $module->saveUserAddress($saveParams);
        if (empty($saveId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        return returnData(true);
    }

    /**
     * 用户收货地址-修改
     * @param array $reqParams
     * @return array
     * */
    public function updateUserAddress(array $reqParams)
    {
        $module = new UsersModule();
        if (!empty($reqParams['lat'] || !empty($reqParams['lng']))) {
            $reqParams['areaId1'] = 0;
            $reqParams['areaId2'] = 0;
            $reqParams['areaId3'] = 0;
            $reqParams['communityId'] = 0;
            $reqParams['setaddress'] = '';
            $latlngDetail = (new MapModule())->latlngToAddress($reqParams['lat'], $reqParams['lng'], 1);
            if ($latlngDetail['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '经纬度逆地址解析失败');
            }
            $reqParams['address'] = $latlngDetail['regeocode']['formatted_address'];
            $reqParams['areaId1'] = $latlngDetail['province_id'];
            $reqParams['areaId2'] = $latlngDetail['city_id'];
            $reqParams['areaId3'] = $latlngDetail['region_id'];
        }
        $saveId = $module->saveUserAddress($reqParams);
        if (empty($saveId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '修改失败');
        }
        return returnData(true);
    }

    /**
     * 用户收货地址-添加
     * @param array $reqParams
     * @return array
     * */
    public function addUserAddress(array $reqParams)
    {
        $module = new UsersModule();
        $reqParams['areaId1'] = 0;
        $reqParams['areaId2'] = 0;
        $reqParams['areaId3'] = 0;
        $reqParams['communityId'] = 0;
        $reqParams['setaddress'] = '';
        $latlngDetail = (new MapModule())->latlngToAddress($reqParams['lat'], $reqParams['lng'], 1);
        if ($latlngDetail['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '经纬度逆地址解析失败');
        }
        $reqParams['address'] = $latlngDetail['regeocode']['formatted_address'];
        $reqParams['areaId1'] = $latlngDetail['province_id'];
        $reqParams['areaId2'] = $latlngDetail['city_id'];
        $reqParams['areaId3'] = $latlngDetail['region_id'];
        $saveId = $module->saveUserAddress($reqParams);
        if (empty($saveId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '添加失败');
        }
        return returnData(true);
    }
}
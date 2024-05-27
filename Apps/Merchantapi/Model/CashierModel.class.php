<?php
namespace Merchantapi\Model;

use AlipayTradePayContentBuilder;
use AlipayTradeQueryContentBuilder;
use AlipayTradeService;
use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsCatModel;
use App\Models\GoodsModel;
use App\Models\PosExchangeGoodsLogModel;
use App\Models\PosGivebackOrdersGoodsModel;
use App\Models\PosGivebackOrdersModel;
use App\Models\PosOrdersGoodsModel;
use App\Models\PosOrdersModel;
use App\Models\PosReportModel;
use App\Models\PosReportRelationModel;
use App\Models\ShopsModel;
use App\Models\SkuGoodsSystemModel;
use App\Models\UserScoreModel;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Goods\GoodsServiceModule;
use App\Modules\Log\LogServiceModule;
use App\Modules\Pos\PosReportModule;
use App\Modules\Pos\PosServiceModule;
use App\Modules\Pos\PromotionModule;
use App\Modules\Report\ReportModule;
use App\Modules\Shops\ShopCatsModule;
use App\Modules\Users\UsersModule;
use App\Modules\Users\UsersServiceModule;
use CjsProtocol\LogicResponse;
use CLogFileHandler;
use GoodsDetail;
use Log;
use MicroPay;
use PayNotifyCallBack;
use Think\Model;
use WxPayMicroPay;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 收银端
 */
class CashierModel extends BaseModel
{
    /**
     * 新会员注册
     * @param array $requestParams <p>
     * string userPhone 手机号
     * string cardNum 卡号
     * string userName 用户名称
     * </p>
     */
    public function userRegister(array $requestParams)
    {
        $params = [];
        $params['loginName'] = '';
        $params['loginPwd'] = '';
        $params['userPhone'] = null;
        $params['cardNum'] = null;
        $params['userName'] = null;
        parm_filter($params, $requestParams);
        $params["loginSecret"] = rand(1000, 9999);
        $params['loginPwd'] = '';
        $params['userType'] = 0;
        $params['userQQ'] = I('userQQ', '');
        $params['userScore'] = I('userScore', 0);
        $params['userEmail'] = I("userEmail", '');
        $params['createTime'] = date('Y-m-d H:i:s');
        $params['userFlag'] = 1;
        $params['cardState'] = 1;
        $usersModel = D('Home/Users');
        $usersTab = M('users');
        //检测账号是否存在
        if (!empty($params['loginName'])) {
            $verification = [];
            $verification['loginName'] = $params['loginName'];
            $verificationRes = $usersModel->verificationLoginKey($verification, 'loginName');
            if ($verificationRes['code'] != 0) {
                return $verificationRes;
            }
        } else {
            $params['loginName'] = $params['userPhone'];
        }
        //检测手机号是否存在
        if (!empty($params['userPhone'])) {
            $verification = [];
            $verification['userPhone'] = $params['userPhone'];
            $verificationRes = $usersModel->verificationLoginKey($verification, 'userPhone');
            if ($verificationRes['code'] != 0) {
                return $verificationRes;
            }
        }
        //检测用户名是否存在
        if (!empty($params['userName'])) {
            $verification = [];
            $verification['userName'] = $params['userName'];
            $verificationRes = $usersModel->verificationLoginKey($verification, 'userName');
            if ($verificationRes['code'] != 0) {
                return $verificationRes;
            }
        }
        //判断卡是否已被使用
        if (!empty($params['cardNum'])) {
            if (strlen($params['cardNum']) != 10) {
                return returnData(false, -1, 'error', '请录入正确的10位卡号');
            }
            $where = array();
            $where['cardNum'] = $params['cardNum'];
            $where['userFlag'] = 1;
            $verificationCardRes = $usersModel->getUserInfoRow($where);
            if (!empty($verificationCardRes)) {
                return returnData(false, -1, 'error', '卡已被使用');
            }
        }
        $userId = $usersTab->add($params);
        if (!$userId) {
            return returnData(false, -1, 'error', '注册用户失败');
        }
        $data = [];
        $data['lastTime'] = date('Y-m-d H:i:s');
        $data['lastIP'] = get_client_ip();
        $usersTab->where("userId={$userId}")->save($data);
        //记录登录日志
        $data = array();
        $data["userId"] = $userId;
        $data["loginTime"] = date('Y-m-d H:i:s');
        $data["loginIp"] = get_client_ip();
        $m = M('log_user_logins');
        $m->add($data);

        $userInfo = $usersModel->getUserInfoRow(['userId' => $userId]);
        return returnData($userInfo);
    }

    /**
     * 会员短信验证码登录，并返回会员信息
     * @param string $phone 手机号
     * @param string $code 验证码
     * @return array $data
     */
    public function userLoginByCode(string $phone, string $code)
    {
        $logSms = D('Home/LogSms');
        $verificationSmsRes = $logSms->verificationSmsCode($phone, $code);
        if ($verificationSmsRes['code'] != 0) {
            return $verificationSmsRes;
        }
        $where = [];
        $where['userFlag'] = 1;
        $where['userPhone'] = $phone;
        $data = D('Home/Users')->getUserInfoRow($where);
        if (!empty($data)) {
            $data['memberToken'] = getUserTokenByUserId($data['userId']);
            $data['historyConsumeIntegral'] = (int)historyConsumeIntegral($data['userId']);
            $logSms->destructionSmsCode($code);
        }
        return returnData((array)$data);
    }

    /**
     *更改会员密码
     * @param int $userId
     * @param string $password
     * */
    public function updateUserPassword(int $userId, string $password)
    {
        $usersModel = D('Home/Users');
        $where = [];
        $where['userId'] = $userId;
        $where['userFlag'] = 1;
        $userInfo = $usersModel->getUserInfoRow($where);
        if (empty($userInfo)) {
            return returnData(false, -1, 'error', '会员信息有误');
        }
        $password_new = md5($password . $userInfo['loginSecret']);
        if ($userInfo['loginPwd'] == $password_new) {
            return returnData($userInfo);
        }
        $saveData = [];
        $saveData['loginPwd'] = $password_new;
        $data = $usersModel->updateUser($where, $saveData);
        if (!$data) {
            return returnData(false, -1, 'error', '修改密码失败');
        }
        return returnData($userInfo);
    }

    /**
     * 会员-编辑会员信息
     * @param array $requestParams <p>
     * int userId
     * string password 密码
     * string userName 会员姓名
     * </p>
     * */
    public function updateUsers($requestParams)
    {
        $usersModel = D('Home/Users');
        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $requestParams['userId'];
        $userInfo = $usersModel->getUserInfoRow($where);
        $params = [];
        $params['userName'] = null;
        parm_filter($params, $requestParams);
        //检测用户名是否存在
        if (!empty($params['userName'])) {
            $verification = [];
            $verification['userName'] = $params['userName'];
            $verificationRes = $usersModel->verificationLoginKey($verification, 'userName', $requestParams['userId']);
            if ($verificationRes['code'] != 0) {
                return $verificationRes;
            }
        }
        if (!empty($requestParams['password'])) {
            $params['loginPwd'] = md5($params['password'] . $userInfo['loginSecret']);
        }
        $data = D('Home/Users')->updateUser($where, $params);
        if ($data !== false) {
            $userInfo = $usersModel->getUserInfoRow($where);
            return returnData($userInfo);
        } else {
            return returnData(false, -1, 'error', '修改失败');
        }
    }

    /**
     * 卡-挂失或解除挂失
     * @param int $userId
     * @param int $cardState 状态 【-1：挂失|1：正常】
     * */
    public function changeCardState(int $userId, int $cardState)
    {
        $usersTab = M("users");
        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $userId;
        $saveData = [];
        $saveData['cardState'] = $cardState;
        $data = $usersTab->where($where)->save($saveData);
        if ($data !== false) {
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '操作失败');
        }
    }

    /**
     * 卡-会员绑卡或换卡
     * @param int $userId
     * @param string $cardNum 卡号
     * */
    public function bindOrReplaceCard(int $userId, string $cardNum)
    {
        $usersModel = D('Home/Users');
        $where = [];
        $where['cardNum'] = $cardNum;
        $where['userFlag'] = 1;
        $userInfo = $usersModel->getUserInfoRow($where);
        if (!empty($userInfo)) {
            return returnData(false, -1, 'error', '卡已被使用，请换一张');
        }
        $where = [];
        $where['userId'] = $userId;
        $userInfo = $usersModel->getUserInfoRow($where);
        if (empty($userInfo)) {
            return returnData(false, -1, 'error', '会员信息有误');
        }
        $saveData = [];
        $saveData['cardNum'] = $cardNum;
        $data = $usersModel->updateUser($where, $saveData);
        if ($data) {
            return returnData(true);
        } else {
            return returnData(false, -1, 'error', '操作失败');
        }
    }

    /**
     * 会员-使用账号和密码登录
     * @param string $loginName 账号
     * @param string $loginPwd 密码
     * */
    public function userLogin(string $loginName, string $loginPwd)
    {
        $usersTab = M("users");
        $where = [];
        $where['userFlag'] = 1;
        $where['loginName'] = $loginName;
        $loginSecret = $usersTab->where($where)->field(array("loginSecret"))->getField('loginSecret');//获取安全码
        $where = [];
        $where['loginName'] = $loginName;
        $where['userFlag'] = 1;
        $where['loginPwd'] = md5($loginPwd . $loginSecret);
        $userInfo = D('Home/Users')->getUserInfoRow($where);
        if (empty($userInfo)) {
            return returnData(false, -1, 'error', '账号或密码错误');
        }
        $userInfo['memberToken'] = getUserTokenByUserId($userInfo['userId']);
        $userInfo['historyConsumeIntegral'] = historyConsumeIntegral($userInfo['userId']);
        return returnData($userInfo);
    }

    /**
     * 获取充值列表
     * */
    public function getRechargesetList()
    {
        $tab = M('recharge_set');
        $where = [];
        $where['rsFlag'] = 1;
        $data = $tab
            ->where($where)
            ->order('sortorder desc')
            ->select();
        return returnData((array)$data);
    }

    /**
     * 获取充值返现规则列表
     * */
    public function getRechargeConfigList()
    {
        $tab = M('recharge_config');
        $where = [];
        $where['dataFlag'] = 1;
        $data = $tab
            ->where($where)
            ->order('id asc')
            ->select();
        return returnData((array)$data);
    }

    /**
     * 会员-会员充值
     * @param int $userId
     * @param int $type 类型（1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付）
     * @param string $auth_code 付款码
     * @param float $money 金額
     * @param string $orderNo 订单号
     * @param int $shopId 当前登陆者信息
     * */

    public function userRecharge($userId, $type, $auth_code, $money, $orderNo, $loginUserInfo)
    {
        $shopId = $loginUserInfo['shopId'];
        if (empty($loginUserInfo['id'])) {
            $actionUserId = $loginUserInfo['userId'];
        } else {
            $actionUserId = $loginUserInfo['id'];
        }
        //如果是现金付款，则需要判断一下商户预存款是否足够
        if ($type == 1 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {
            //获取商户预存款
            $predeposit = M('shops')->where(array('shopId' => $shopId))->getField('predeposit');
            if ($predeposit < $money) {
                return returnData(false, -1, 'error', '商户预存款不足');
            }
        }
        if ($type == 4) {//微信
            $result = $this->doWxPay($auth_code, $money, $orderNo);
        } elseif ($type == 5) {//支付宝
            $result = $this->doAliPay($userId, $auth_code, $money, $orderNo);
        }
        if ($result['code'] != 0) {
            return $result;
        }
        M()->startTrans();
        $res = M('users')->where(array('userId' => $userId))->setInc('balance', $money);
        if (!$res) {
            M()->rollback();
            return returnData(false, -1, 'error', '修改用户余额失败');
        }
        //充值成功，写入余额流水

        $user_balance_data = array(
            'userId' => $userId,
            'balance' => $money,
            'dataSrc' => 2,
            'orderNo' => $orderNo,
            'dataRemarks' => '会员充值',
            'balanceType' => 1,
            'createTime' => date('Y-m-d H:i:s'),
            'shopId' => $shopId,
            'actionUserId' => $actionUserId
        );
        $balanceId = M('user_balance')->add($user_balance_data);
        if (empty($balanceId)) {
            M()->rollback();
            return returnData(false, -1, 'error', '余额流水记录失败');
        }
        if ($type == 1 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {//现金
            //如果是现金，则需要从预存款中扣除对应金额
            M('shops')->where(array('shopId' => $shopId))->setDec('predeposit', $money);
        }
        //充值成功,处理返现逻辑 后加 start
        $amount = $money;
        $rechargeConfigList = M('recharge_config')->where(['dataFlag' => 1])->select();
        $returnAmount = 0;
        foreach ($rechargeConfigList as $value) {
            if ($amount >= $value['minAmount'] && $amount <= $value['maxAmount']) {
                $returnAmount = $value['returnAmount'];
            }

        }
        if ($returnAmount > 0) {
            $usersModel = D('Home/Users');
            $usersWhere = [];
            $usersWhere['userId'] = $userId;
            $userInfo = $usersModel->getUserInfoRow($usersWhere);
            $updateBalance = [];
            $updateBalance['balance'] = $userInfo['balance'] + $returnAmount;
            $updateBalanceRes = $usersModel->updateUser($usersWhere, $updateBalance);
            if ($updateBalanceRes) {
                //写入余额流水
                $add_user_balance['userId'] = $userId;
                $add_user_balance['balance'] = $returnAmount;
                $add_user_balance['dataSrc'] = 1;
                $add_user_balance['orderNo'] = $orderNo;
                $add_user_balance['dataRemarks'] = '余额充值返现';
                $add_user_balance['balanceType'] = 1;
                $add_user_balance['createTime'] = date('Y-m-d H:i:s');
                $add_user_balance['shopId'] = $shopId;
                M('user_balance')->add($add_user_balance);
            }

        }
        //充值成功,处理返现逻辑 后加 end
        //写入平台流水表
        $recharge_data = array(
            'targetType' => 0,
            'targetId' => $userId,
            'dataSrc' => 3,
            'dataId' => (int)$balanceId,
            'moneyRemark' => '会员充值',
            'moneyType' => 1,
            'money' => $money,
            'createTime' => date('Y-m-d H:i:s'),
            'dataFlag' => 1,
            'state' => empty($result['apiCode']) ? 1 : 0,
            'payType' => $type
        );
        $logId = M('log_sys_moneys')->add($recharge_data);
        if (!$logId) {
            M()->rollback();
            return returnData(false, -1, 'error', '平台流水记录失败');
        }
        M()->commit();
        $returnData = [];
        $returnData['money'] = formatAmount($money);
        $returnData['returnMoney'] = formatAmount($returnAmount);
        return returnData($returnData);
    }

    /**
     * 微信支付 - 动作
     * @param $userId
     * @param $auth_code
     * @param $money
     * @param $orderNo
     * @return mixed
     */
    public function doWxPay($auth_code, $money, $orderNo)
    {
        Vendor('WxPay.lib.WxPayApi');
        Vendor('WxPay.MicroPay');
        Vendor('WxPay.log');
        if ((isset($auth_code) && !preg_match("/^[0-9]{6,64}$/i", $auth_code, $matches))) {
//            header('HTTP/1.1 404 Not Found');
//            exit();
            return returnData(false, -1, 'error', '请使用正确的微信付款码');
        }
        //初始化日志
        $logHandler = new CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
        $log = Log::Init($logHandler, 15);
        if (isset($auth_code) && $auth_code != '') {
            try {
                $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
                if (empty($wx_payments['payConfig'])) {
                    return returnData(false, -1, 'error', '微信支付配置不全');
                }
                $wx_config = json_decode($wx_payments['payConfig'], true);
                $wx_config['appId'] = $GLOBALS['CONFIG']['xiaoAppid'];
                $input = new WxPayMicroPay();
                $input->SetAuth_code($auth_code);
                $input->SetBody("POS-支付");
                $input->SetTotal_fee($money * 100);
                $input->SetOut_trade_no($orderNo);
                $microPay = new MicroPay();
                $result = printf_info($microPay->pay($wx_config, $input));
//                    echo "<pre>";var_dump($result);exit();
                //支付成功后,执行下面的方法
                $debug = [];
                $debug['text'] = json_encode($result);
                $debug['createTime'] = date('Y-m-d H:i:s', time());
                M('debug')->add($debug);
                if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
                    Vendor('WxPay.notify');
                    //查询订单
                    Log::DEBUG("begin notify");
                    $PayNotifyCallBack = new PayNotifyCallBack();
                    $res = $PayNotifyCallBack->Queryorder($wx_config, $result['transaction_id']);
                    if ($res) {//支付成功
                        //回调方法
                        //$this->scanPayCallback($orderNo);
                        //订单校验
                        //$this->checkOrder($orderNo);
                        return returnData($result);
                    } else {
                        return returnData(false, -1, 'error', '收款失败');
                    }
                } else {
                    return returnData(false, -1, 'error', '收款失败');
                }
            } catch (Exception $e) {
                Log::ERROR(json_encode($e));
            }
        }
    }

    /**
     * 支付宝支付 - 动作
     */
    public function doAliPay($userId, $auth_code, $money, $orderNo)
    {
        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $order_info = $pom->where(array('orderNO' => $orderNo))->find();
        $order_goods = $pogm->where(array('orderid' => $order_info['id']))->select();
        header("Content-type: text/html; charset=utf-8");
        Vendor('Alipay.dangmianfu.f2fpay.model.builder.AlipayTradePayContentBuilder');
        Vendor('Alipay.dangmianfu.f2fpay.service.AlipayTradeService');
        $wx_payments = M('payments')->where(array('payCode' => 'alipay'))->find();
        if (empty($wx_payments['payConfig'])) {
            return returnData(false, -1, 'error', '支付宝支付配置不全');
        }
        $config = json_decode($wx_payments['payConfig'], true);
        // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
        // 需保证商户系统端不能重复，建议通过数据库sequence生成，
        //$outTradeNo = "barpay" . date('Ymdhis') . mt_rand(100, 1000);
//            $outTradeNo = $_POST['out_trade_no'];
        $outTradeNo = $orderNo;

        // (必填) 订单标题，粗略描述用户的支付目的。如“XX品牌XXX门店消费”
//            $subject = $_POST['subject'];
        $subject = "收银员收银";

        // (必填) 订单总金额，单位为元，不能超过1亿元
        // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $money;

        // (必填) 付款条码，用户支付宝钱包手机app点击“付款”产生的付款条码
        $authCode = $auth_code; //28开头18位数字

        // (可选,根据需要使用) 订单可打折金额，可以配合商家平台配置折扣活动，如果订单部分商品参与打折，可以将部分商品总价填写至此字段，默认全部商品可打折
        // 如果该值未传入,但传入了【订单总金额】,【不可打折金额】 则该值默认为【订单总金额】- 【不可打折金额】
        //String discountableAmount = "1.00"; //

        // (可选) 订单不可打折金额，可以配合商家平台配置折扣活动，如果酒水不参与打折，则将对应金额填写至此字段
        // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
//            $undiscountableAmount = "0.01";

        // 卖家支付宝账号ID，用于支持一个签约账号下支持打款到不同的收款账号，(打款到sellerId对应的支付宝账号)
        // 如果该字段为空，则默认为与支付宝签约的商户的PID，也就是appid对应的PID
        $sellerId = "";

        // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
//            $body = "购买商品2件共15.00元";
        $body = "";
        $goods_num = 0;
        if (!empty($order_goods)) {
            foreach ($order_goods as $v) {
                $goods_num += $v['number'] + $v['weight'];
            }
        }
        if ($goods_num > 0) {
            $body = "购买商品 " . $goods_num . " 件共 " . $order_info['realpayment'] . " 元";
        }

        //商户操作员编号，添加此参数可以为商户操作员做销售统计
//            $operatorId = "test_operator_id";
        $operatorId = $userId;//根据检查,该字段是错误的,不过不影响业务逻辑

        // (可选) 商户门店编号，通过门店号和商家后台可以配置精准到门店的折扣信息，详询支付宝技术支持
//            $storeId = "test_store_id";
        $storeId = $order_info['shopId'];

        // 支付宝的店铺编号
//            $alipayStoreId = "test_alipay_store_id";

        // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
//            $providerId = ""; //系统商pid,作为系统商返佣数据提取的依据
//            $extendParams = new \ExtendParams();
//            $extendParams->setSysServiceProviderId($providerId);
//            $extendParamsArr = $extendParams->getExtendParams();

        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";

        // 商品明细列表，需填写购买商品详细信息，
        $goodsDetailList = array();

        // 创建一个商品信息，参数含义分别为商品id（使用国标）、名称、单价（单位为分）、数量，如果需要添加商品类别，详见GoodsDetail
        if (!empty($order_goods)) {
            $goods = new GoodsDetail();
            foreach ($order_goods as $k => $v) {
                $goods->setGoodsId($v['goodsId']);
                $goods->setGoodsName($v['goodsName']);
                $goods->setPrice($v['presentPrice']);
                $num = ($v['number'] > 0) ? $v['number'] : $v['weight'];
                $goods->setQuantity($num);
                $goodsDetailList[] = $goods->getGoodsDetail();
            }
        }

        //第三方应用授权令牌,商户授权系统商开发模式下使用
        $appAuthToken = "";//根据真实值填写

        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new AlipayTradePayContentBuilder();
        $barPayRequestBuilder->setOutTradeNo($outTradeNo);
        $barPayRequestBuilder->setTotalAmount($totalAmount);
        $barPayRequestBuilder->setAuthCode($authCode);
        $barPayRequestBuilder->setTimeExpress($timeExpress);
        $barPayRequestBuilder->setSubject($subject);
        $barPayRequestBuilder->setBody($body);
//            $barPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
//            $barPayRequestBuilder->setExtendParams($extendParamsArr);
        $barPayRequestBuilder->setGoodsDetailList($goodsDetailList);
        $barPayRequestBuilder->setStoreId($storeId);
        $barPayRequestBuilder->setOperatorId($operatorId);
//            $barPayRequestBuilder->setAlipayStoreId($alipayStoreId);

        $barPayRequestBuilder->setAppAuthToken($appAuthToken);

        // 调用barPay方法获取当面付应答
        $barPay = new AlipayTradeService($config);
        $barPayResult = $barPay->barPay($barPayRequestBuilder);
        $result = $barPayResult->getTradeStatus();
        $debug = [];
        $debug['text'] = serialize($barPayResult->getResponse());
        $debug['createTime'] = date('Y-m-d H:i:s', time());
        M('debug')->add($debug);
        if ($result == "SUCCESS") {//支付宝支付成功
//                print_r($barPayResult->getResponse());
//                $alipayResult = $barPayResult->getResponse();
//                echo "<pre>";var_dump($alipayResult);
//                exit();
            ////获取商户订单号
//                $out_trade_no = trim($_POST['out_trade_no']);
            $out_trade_no = trim($outTradeNo);

            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写

            //构造查询业务请求参数对象
            $queryContentBuilder = new AlipayTradeQueryContentBuilder();
            $queryContentBuilder->setOutTradeNo($out_trade_no);

            $queryContentBuilder->setAppAuthToken($appAuthToken);


            //初始化类对象，调用queryTradeResult方法获取查询应答
            $queryResponse = new AlipayTradeService($config);
            $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);

            $debug = [];
            $debug['text'] = $queryResult;
            $debug['createTime'] = date('Y-m-d H:i:s', time());
            M('debug')->add($debug);

            //根据查询返回结果状态进行业务处理
            $resultState = $queryResult->getTradeStatus();
            if ($resultState == "SUCCESS") {//支付宝查询交易成功
                //处理业务逻辑
                //回调方法
                //$this->scanPayCallback($orderNo);
                //订单校验
                //$this->checkOrder($orderNo);
                $returnData = json_decode($queryResult, true);
                $returnData['transaction_id'] = $returnData['trade_no'];
                return returnData($returnData);
            } else if ($resultState == "FAILED") {//支付宝查询交易失败或者交易已关闭
                return returnData(false, -1, 'error', '支付宝查询交易失败或者交易已关闭');
            } else if ($resultState == "UNKNOWN") {//系统异常，订单状态未知
                return returnData(false, -1, 'error', '系统异常，订单状态未知');
            } else {//不支持的查询状态，交易返回异常
                return returnData(false, -1, 'error', '不支持的查询状态，交易返回异常');
            }
        } else if ($result == "FAILED") {//支付宝支付失败
            return returnData(false, -1, 'error', '支付宝支付失败');
        } else if ($result == "UNKNOWN") {//系统异常，订单状态未知
            return returnData(false, -1, 'error', '系统异常，订单状态未知');
        } else {//不支持的交易状态，交易返回异常
            return returnData(false, -1, 'error', '不支持的交易状态，交易返回异常');
        }
    }

    /**
     *总管理员-申请提现
     * @param int $shopId
     * @param float $money
     * */
    public function applyWithdraw($shopId, $money)
    {
        $where = [];
        $where['shopFlag'] = 1;
        $where['shopId'] = $shopId;
        $shopInfo = M('shops')->where($where)->find();
        if (empty($shopInfo)) {
            return returnData(false, -1, 'error', '商户不存在');
        }
        if ($money > $shopInfo['predeposit']) {
            return returnData(false, -1, 'error', '提现金额不能大于预存款');
        }
        $data = array(
            'shopId' => $shopId,
            'money' => $money,
            'state' => 0,
            'addTime' => date('Y-m-d H:i:s'),
            'updateTime' => ''
        );
        $insertId = M('withdraw')->add($data);
        if (empty($insertId)) {
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true);
    }

    /**
     * 会员-购买会员套餐
     * @param int $shopId
     * @param int $userId
     * @param int $smId 套餐ID
     * @param int $type 类型（1:现金 4：微信 5：支付宝）
     * */
    public function buySetmeal(int $shopId, int $userId, int $smId, int $type)
    {
        $shopTab = M('shops');
        $money = M('set_meal')->where(array('smId' => $smId))->getField('money');
        //如果是现金支付，则需要验证商户预存款是否足够
        if ($type == 1 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {
            $predeposit = $shopTab->where(array('shopId' => $shopId))->getField('predeposit');
            if ($predeposit < $money) {
                return returnData(false, -1, 'error', '商户预存款不足');
            }
        }
        $usersModel = D('Home/Users');
        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $userId;
        $userInfo = $usersModel->getUserInfoRow($where);
        $m = D('V3/Api');
        $result = $m->buySetmeal($userInfo, $smId);
        //支付成功
        if ($result['code'] == 0) {
            //如果是现金支付，则需要从商户预存款中扣除相应金额
            if ($type == 1 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {//现金
                $shopTab->where(array('shopId' => $shopId))->setDec('predeposit', $money);
            }
        }
        return $result;
    }

    /**
     *会员套餐列表
     * */
    public function getSetmealList()
    {
        $where = [];
        $where['smFlag'] = 1;
        $where['isEnable'] = 1;
        $data = M('set_meal')->where($where)->order('smId asc')->select();
        return returnData((array)$data);
    }

    /**
     * 商户预存款流水
     * @param int $shopId
     * @param int $page
     * @param int $pageSize
     */
    public function getRechargeRecord(int $shopId, int $page, int $pageSize)
    {
        $sql = "select lsm.*,s.shopSn,s.shopName,s.shopCompany from __PREFIX__log_sys_moneys as lsm left join __PREFIX__shops as s on lsm.targetId = s.shopId where lsm.dataSrc = 3 and lsm.dataFlag = 1 and lsm.targetType = 1 and lsm.targetId = " . $shopId;
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     *商家预存款（充值）
     * @param int $shopId
     * @param int $type
     * @param int $auth_code
     * @param float $money
     * @param string $orderNo
     * */
    public function recharge($shopId, $type, $auth_code, $money, $orderNo)
    {
        $shopInfo = $this->getShopInfo($shopId);
        if (empty($shopInfo)) {
            return returnData(false, -1, 'error', '店铺信息有误');
        }
        $userId = $shopInfo['userId'];
        //是否开启商户预存款 0:否  1：是
        if (empty($GLOBALS["CONFIG"]["openPresaleCash"])) {//没有开启
            return returnData(false, -1, 'error', '没有开启商户预存款');
        } else {//开启预存款
            if ($money <= 0) {
                return returnData(false, -1, 'error', '请输入大于0的金额');
            }
            if ($type == 4) {//微信
                $result = $this->doWxPay($auth_code, $money, $orderNo);
            } else if ($type == 5) {//支付宝
                $result = $this->doAliPay($userId, $auth_code, $money, $orderNo);
            }
            if ($result['code'] != 0) {
                return $result;
            }
            //写入充值记录
            $recharge_data = array(
                'targetType' => 1,
                'targetId' => $shopId,
                'dataSrc' => 3,
                'dataId' => 0,
                'moneyRemark' => '充值',
                'moneyType' => 1,
                'money' => $money,
                'createTime' => date('Y-m-d H:i:s'),
                'dataFlag' => 1,
                'state' => empty($result['code']) ? 1 : 0,
                'payType' => $type
            );
            M('log_sys_moneys')->add($recharge_data);
            //充值成功，将金额充到商户账户
            M('shops')->where(array('shopId' => $shopId))->setInc('predeposit', $money);
            return returnData(true);
        }
    }

    /**
     * 店铺信息
     * */
    public function getShopInfo(int $shopId)
    {
        $where = [];
        $where['shopFlag'] = 1;
        $where['shopId'] = $shopId;
        $shopInfo = M('shops')->where($where)->find();
        return $shopInfo;
    }

    /**
     * Pos订单结算申请
     * @param array <p>
     * int shopId
     * string ids pos订单id,多个用英文逗号分隔
     * </p>
     */
    public function posOrderSettlement($param)
    {
        $ids = $param['ids'];
        $shopId = $param['shopId'];
        $tab = M('pos_orders');
        $where = [];
        $where['id'] = ["IN", $ids];
        $where['state'] = 1;
        $list = $tab->where($where)->select();
        if (empty($list)) {
            return returnData(false, -1, 'error', '操作失败');
        }
        $poundageRate = M('sys_configs')->where(['fieldCode' => 'poundageRate'])->getField('fieldValue');//佣金比例
        $settlementStartMoney = M('sys_configs')->where(['fieldCode' => 'settlementStartMoney'])->getField('fieldValue');//结算金额,低于该值不给结算
        $openPresaleCash = M('sys_configs')->where(['fieldCode' => 'openPresaleCash'])->getField('fieldValue');//预存款,开启则不需要减去现金
        $realpayment = 0;
        $settlementMoney = 0;
        $poundageMoney = 0;
        foreach ($list as $key => $val) {
            $realpayment += $val['realpayment'];
            $settlementMoney += $val['settlementMoney'];
            if ($poundageRate > 0) {
                $poundageMoney += WSTBCMoney($val["realpayment"] * $poundageRate / 100, 0, 2);
            }
            if ($openPresaleCash != 1) {
                $settlementMoney += $val['realpayment'] - $val['cash'];
            } else {
                $settlementMoney += $val['realpayment'];
            }
        }
        if ($settlementMoney < $settlementStartMoney) {
            $msg = '操作失败,结算金额必须大于' . $settlementStartMoney;
            return returnData(false, -1, 'error', $msg);
        }
        $bankInfo = M('shops s')
            ->join("left join wst_banks b on b.bankId=s.bankId")
            ->where("s.shopId='" . $val['shopId'] . "' and s.bankId=b.bankId")
            ->field("b.bankName,s.bankUserName,s.bankNo")
            ->find();
        //生成结算单
        $data = array();
        $data['settlementType'] = 1;
        $data['shopId'] = $shopId;
        $data['accName'] = $bankInfo['bankName'];
        $data['accNo'] = $bankInfo['bankNo'];
        $data['accUser'] = $bankInfo['bankUserName'];
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['realpayment'] = $realpayment;
        $data['settlementMoney'] = $settlementMoney - $poundageMoney;
        $data['poundageMoney'] = $poundageMoney;
        $data['poundageRate'] = $poundageRate;
        $data['isFinish'] = 1;
        $editData['state'] = 3;
        $settlementId = M('pos_order_settlements')->add($data);
        if ($settlementId) {
            $sql = "update __PREFIX__pos_order_settlements set settlementNo='" . date('y') . sprintf("%08d", $settlementId) . "' where settlementId=" . $settlementId;
            $this->execute($sql);
            $where = [];
            $where['id'] = ["IN", $ids];
            M("pos_orders")->where($where)->save(['settlementId' => $settlementId, 'state' => 3]);
        }
        return returnData(true);
    }

    /**
     * 获取Pos订单列表
     * @param array $params
     * @param string settlementNo 结算单号
     * @param string startDate 开始时间
     * @param string endDate 结束时间
     * @param string maxMoney 最大金额(金额区间)
     * @param string minMoney 最小金额(金额区间)
     * @param int state 状态(1:未结算 | 2：已结算)
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getPosOrderSettlementList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $shopId = $params['shopId'];
        $where = " where os.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['os.settlementMoney'] = function () use ($params) {
            if (empty($params['maxMoney']) || empty($params['minMoney'])) {
                return null;
            }
            return ['between', "{$params['minMoney']}' and '{$params['maxMoney']}", 'and'];
        };
        $whereFind['os.settlementNo'] = function () use ($params) {
            if (empty($params['settlementNo'])) {
                return null;
            }
            return ['like', "%{$params['settlementNo']}%", 'and'];
        };
        $whereFind['os.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['os.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['os.isFinish'] = function () use ($params) {
            if (!in_array($params['isFinish'], [1, 2])) {
                return null;
            }
            return ['=', "{$params['isFinish']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $sql = "select os.* from __PREFIX__pos_order_settlements os ";
        $sql .= $whereInfo;
        $sql .= " order by os.settlementId desc ";
        $res = $this->pageQuery($sql, $page, $pageSize);
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                $val['finishTime'] = (string)$val['finishTime'];
                $val['remarks'] = (string)$val['finishTime'];
            }
            unset($val);
        }
        return returnData($res);
    }

    /**
     * 获取Pos订单详情
     * @param int $shopId
     * @param int $posId Pos订单id
     */
    public function getPosOrderDetail(int $shopId, int $posId)
    {
        $field = "po.*,u.name as user_name,u.username as user_username,u.phone as user_phone,users.userName,users.userPhone ";
        $where = " where po.id={$posId} and po.shopId={$shopId} ";
        $sql = "select {$field} from __PREFIX__pos_orders po left join __PREFIX__user u on po.userId=u.id left join __PREFIX__users users on users.userId=po.memberId ";
        $sql .= $where;
        $res = (array)$this->queryRow($sql);
        if (empty($res)) {
            return array();
        }
        $res['goods_amount_total'] = 0;
        if ($res) {
            $res['discount'] = ((float)$res['discount'] * 10) . '%';
            $res['userName'] = (string)$res['userName'];
            $res['userPhone'] = (string)$res['userPhone'];
            $res['user_name'] = (string)$res['user_name'];
            $res['user_username'] = (string)$res['user_username'];
            $res['user_phone'] = (string)$res['user_phone'];
            $res['pay_time'] = (string)$res['pay_time'];
            $res['is_return_state'] = 1;//本单可退货状态(-1:不能退货 1:可以退货)
            $res['is_exchange_state'] = 1;//本单可换货状态(-1:不能换货 1:可以换货)
            //商品信息
            $orderGoodsTab = M('pos_orders_goods pg');
            $goodsList = (array)$orderGoodsTab
                ->join("left join wst_goods g on g.goodsId=pg.goodsId")
                ->where(['orderid' => $res['id']])
                ->field("pg.*,g.goodsThums,g.goodsImg")
                ->select();
            $goods_num = 0;//所有商品数量
            $return_num = 0;//已退货数量
            $exchange_num = 0;//已换货数量
            $discount_price = 0;
            foreach ($goodsList as &$item) {
                $discount_price += ($item['originalPrice'] * $item['discount']);
                $goods_num += $item['number'];
                $return_num += $item['refundNum'];
                $exchange_num += $item['exchangeNum'];
                $item['discount'] = ((float)$item['discount'] * 100) . '%';
                $res['goods_amount_total'] += $item['subtotal'];
                $res['original_price_total'] += $item['originalPrice'];
            }
            unset($item);
            if ($return_num != 0 || $exchange_num != 0) {
                if ($return_num >= $goods_num || $exchange_num >= $goods_num) {
                    $res['is_return_state'] = -1;
                    $res['is_exchange_state'] = -1;
                }
            }
            $res['goodslist'] = $goodsList;
            $res['goods_amount_total'] = formatAmount($res['goods_amount_total']);//商品信息-合计
            //退货商品信息
            $res['return_goods'] = array();
            $pos_service_module = new PosServiceModule();
            $giveback_order_result = $pos_service_module->getGivebackOrdersInfoByOrderId($posId);
            $res['return_goods_amount_total'] = 0;//退货商品信息金额合计
            if ($giveback_order_result['code'] == ExceptionCodeEnum::SUCCESS) {
                $giveback_order_data = $giveback_order_result['data'];
                $backId = $giveback_order_data['backId'];
                $return_goods_result = $pos_service_module->getGivebackGoodsListById($backId);
                if ($return_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $return_goods_data = $return_goods_result['data'];
                    foreach ($return_goods_data as &$item) {
                        $item['skuSpecAttr'] = '';
                        foreach ($goodsList as $val) {
                            if ($val['goodsId'] == $item['goodsId'] && $val['skuId'] == $item['skuId']) {
                                $item['skuSpecAttr'] = $val['skuSpecAttr'];
                            }
                        }
                        $item['presentPrice_total'] = $item['subtotal'];
                    }
                    unset($item);
                    $res['return_goods'] = $return_goods_data;
                    $res['return_goods_amount_total'] = array_sum(array_column($return_goods_data, 'subtotal'));
                }
            }
            //换货商品信息-退回
            $res['exchange_goods_return'] = array();
            $res['exchange_return_amount_total'] = 0;//换货-退回商品-金额合计
            $exchange_goods_return = $pos_service_module->getExchangeRelationListByOrderId($posId, 1);
            $goods_service_module = new GoodsServiceModule();
            $before_amount = 0;
            if ($exchange_goods_return['code'] == ExceptionCodeEnum::SUCCESS) {
                $exchange_return_goods = $exchange_goods_return['data'];
                foreach ($exchange_return_goods as $item) {
                    $order_goods_result = $pos_service_module->getPosOrdersGoodsInfo($item['relation_id']);
                    if ($order_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $order_goods_data = $order_goods_result['data'];
                        $goods_info_result = $goods_service_module->getGoodsInfoById($order_goods_data['goodsId']);
                        $goods_info_data = $goods_info_result['data'];
                        $goods_info = array();
                        $goods_info['goodsId'] = $order_goods_data['goodsId'];
                        $goods_info['goodsName'] = $order_goods_data['goodsName'];
                        $goods_info['goodsImg'] = (string)$goods_info_data['goodsImg'];
                        $goods_info['goodsThums'] = (string)$goods_info_data['goodsThums'];
                        $goods_info['goodsSn'] = $order_goods_data['goodsSn'];
                        $goods_info['skuSpecAttr'] = $order_goods_data['skuSpecAttr'];
                        $goods_info['return_num_total'] = $item['return_num_total'];
                        $goods_info['return_present_price'] = $item['return_present_price'];
                        $goods_info['return_present_total'] = $item['return_present_total'];
                        $goods_info['return_present_total'] = $item['return_present_total'];
                        $goods_info['return_subtotal'] = $item['return_present_total'];
                        $res['exchange_goods_return'][] = $goods_info;
                        $res['exchange_return_amount_total'] += $goods_info['return_subtotal'];
                        $before_amount += $item['return_present_total'];
                    }
                }
                unset($item);
            }
            $res['exchange_amount_total'] = formatAmount($res['exchange_amount_total']);
            //换货商品信息-换出
            $res['exchange_goods_exchange'] = array();
            $exchange_goods = array();
            $exchange_goods_exchange = $pos_service_module->getExchangeRelationListByOrderId($posId, 2);
            $after_amount = 0;
            if ($exchange_goods_exchange['code'] == ExceptionCodeEnum::SUCCESS) {
                $exchange_goods_data = $exchange_goods_exchange['data'];
                foreach ($exchange_goods_data as &$item) {
                    $goods_info_result = $goods_service_module->getGoodsInfoById($order_goods_data['goodsId']);
                    $goods_info_data = $goods_info_result['data'];
                    $goods_info = array();
                    $goods_info['goodsId'] = $item['goodsId'];
                    $goods_info['goodsSn'] = $item['goodsSn'];
                    $goods_info['goodsName'] = $goods_info_data['goodsName'];
                    $goods_info['skuSpecAttr'] = $item['skuSpecAttr'];
                    $goods_info['goodsImg'] = (string)$goods_info_data['goodsImg'];
                    $goods_info['goodsThums'] = (string)$goods_info_data['goodsThums'];
                    $goods_info['exchange_num'] = $item['exchange_num'];
                    $goods_info['exchange_subtotal'] = $item['exchange_subtotal'];
                    $goods_info['present_price'] = $item['present_price'];
                    $exchange_goods[] = $goods_info;
                    $after_amount += $item['exchange_subtotal'];
                }
                unset($item);
                $res['exchange_goods_exchange'] = $exchange_goods;
            }
            //换货前小计 PS:退回商品金额统计
            $res['before_amount'] = formatAmount($before_amount);
            //换货后小计 PS:换出商品金额统计
            $res['after_amount'] = formatAmount($after_amount);
            //顾客补差价
            $res['diff_money'] = $res['after_amount'] - $res['before_amount'];
            $oprea = '';
            if ($res['diff_money'] < 0) {
                $oprea = '-';
            }
            $res['diff_money'] = $oprea . formatAmount(abs($res['diff_money']));
            //订单日志
            $res['logs'] = $this->getPosOrdersLog($res['id']);
            //费用信息-商品合计
            $res['original_price_total'] = formatAmount($res['original_price_total']);
            //费用信息-折扣金额
            $res['discount_amount'] = bc_math($res['original_price_total'], $discount_price, 'bcsub', 2);
            $res['order_amount_total'] = $res['goods_amount_total'];
        }
        return $res;
    }

    /**
     * 获取Pos订单列表
     * where
     * @param string orderNo PS:订单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大金额(金额区间)
     * @param string minMoney PS:最小金额(金额区间)
     * @param int state PS:状态 (1:待结算 | 2：已取消 | 3：已结算)
     * @param int pay PS:支付方式 (1:现金支付 | 2：余额支付 | 3：银联支付 | 4：微信支付 | 5：支付宝支付 | 6：组合支付)
     * @param string name PS:收银员账号
     * @param string username PS:收银员姓名
     * @param string phone PS:收银员手机号
     * @param string identity PS:身份 1:会员 2：游客
     * @param string userName 用户名
     * @param string userPhone 用户手机号
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param int pageSupport 支持分页【-1：无分页|1：有分页】
     * 默认为1
     */
    public function getPosOrderList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $shopId = $params['shopId'];
        $field = "po.*,u.username as user_username,u.phone as user_phone,us.userName,us.userPhone,og.promotion_type ";
        $field1 = " sum(po.realpayment) as total_order_money ";//主要用来统计相关条件下的总订单金额
        $where = " where po.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['og.promotion_type'] = function () use ($params) {
            if (empty($params['promotion_type'])) {
                return null;
            }
            return ['=', "{$params['promotion_type']}", 'and'];
        };
        $whereFind['po.orderNO'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['po.addtime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            $params['startDate'] = $params['startDate'] . ' 00:00:00';
            $params['endDate'] = $params['endDate'] . ' 23:59:59';
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['po.realpayment'] = function () use ($params) {
            if (!is_numeric($params['maxMoney']) || !is_numeric($params['minMoney'])) {
                return null;
            }
            if ((float)$params['maxMoney'] <= 0) {
                return null;
            }
            return ['between', "{$params['minMoney']}' and '{$params['maxMoney']}", 'and'];
        };
        $whereFind['po.state'] = function () use ($params) {
            if (empty($params['state']) || !in_array($params['state'], [1, 2, 3])) {
                return null;
            }
            return ['=', "{$params['state']}", 'and'];
        };
        $whereFind['po.pay'] = function () use ($params) {
            if (empty($params['pay']) || !in_array($params['pay'], [1, 2, 3, 4, 5, 6])) {
                return null;
            }
            return ['=', "{$params['pay']}", 'and'];
        };
        $whereFind['u.name'] = function () use ($params) {
            if (empty($params['user_name'])) {
                return null;
            }
            return ['like', "%{$params['user_name']}%", 'and'];
        };
        $whereFind['u.username'] = function () use ($params) {
            if (empty($params['user_username'])) {
                return null;
            }
            return ['like', "%{$params['user_username']}%", 'and'];
        };
        $whereFind['u.phone'] = function () use ($params) {
            if (empty($params['user_phone'])) {
                return null;
            }
            return ['like', "%{$params['user_phone']}%", 'and'];
        };
        $whereFind['us.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['us.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        where($whereFind);
        if ($params['identity'] == 1) {//会员
            $where .= " and po.memberId > 0 ";
        } else if ($params['identity'] == 2) {//游客
            $where .= " and po.memberId = 0 ";
        }
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        $sql = "select $field from __PREFIX__pos_orders po
        left join __PREFIX__pos_orders_goods og on og.orderid=po.id
        left join __PREFIX__user u on po.userId=u.id
         left join __PREFIX__users us on po.memberId = us.userId ";
        $sql .= $whereInfo;
//        $sql = "select $field from __PREFIX__pos_orders po
//        left join __PREFIX__user u on po.userId=u.id
//         left join __PREFIX__users us on po.memberId = us.userId ";
//        $sql .= $whereInfo;
        $sql .= " group by po.id ";
        $sql .= " order by po.id desc ";
        if ($params['pageSupport'] == 1) {
            $res = $this->pageQuery($sql, $page, $pageSize);
        } else {
            $res = [];
            $res['root'] = $this->query($sql);
            $res['total'] = count($res['root']);
        }
        //主要用来统计相关条件下的总订单金额
        $sql1 = "select $field1 from __PREFIX__pos_orders po
        left join __PREFIX__pos_orders_goods og on og.orderid=po.id
        left join __PREFIX__user u on po.userId=u.id
         left join __PREFIX__users us on po.memberId = us.userId ";
        $sql1 .= $whereInfo;
        $sql1 .= "group by po.id ";
        $total_order_money = $this->query($sql1);
        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
        $res['total_order_money'] = $total_order_money[0]['total_order_money'];
        $openPresaleCash = M("sys_configs")->where("fieldCode='openPresaleCash'")->getField('fieldValue'); //是否开启预存款
        $pos_orders_goods_tab = M('pos_orders_goods');
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                $val['pay_time'] = (string)$val['pay_time'];
                //$val['user_name'] = (string)$val['user_name'];
                $val['user_username'] = (string)$val['user_username'];
                $val['user_phone'] = (string)$val['user_phone'];
                $val['memberId'] = (int)$val['memberId'];
                $val['userName'] = (string)$val['userName'];
                $val['userPhone'] = (string)$val['userPhone'];
                $val['pay'] = (int)$val['pay'];
                $val['integral'] = (int)$val['integral'];
                $val['outTradeNo'] = (string)$val['outTradeNo'];
                $val['discount'] = ((float)$val['discount'] * 10) . '%';
                if ($openPresaleCash == 1) {
                    $val['trueRealpayment'] = $val['realpayment'];
                }
                $val['total_favorablePrice'] = (float)$pos_orders_goods_tab->where(array(
                    'orderid' => $val['id'],
                    'state' => 1
                ))->sum('favorablePrice');
                $val['total_favorablePrice'] = formatAmountNum($val['total_favorablePrice']);
            }
            unset($val);
        }
        return returnData($res);
    }

    /**
     * 退货、退款
     * 文档链接地址：https://www.yuque.com/anthony-6br1r/oq7p0p/bqycp4
     */
    public function returnGoods($shopInfo, $pack)
    {
        $posOrderGoodsTab = M('pos_orders_goods');
        $givebackOrdersTab = M('pos_giveback_orders');
        $givebackOrdersGoodsTab = M('pos_giveback_orders_goods');
        $goodsTab = M('goods');
        $usersTab = M('users');
        $shopId = $shopInfo['shopId'];
        $orderId = (int)$pack['orderId'];
        $wrapRealpayment = $pack['realpayment'];//实退金额
        if (empty($pack['goods'])) {
            return returnData(false, -1, 'error', '请输入退款商品数据');
        }
        if (!empty($shopInfo['id'])) {
            //职员登陆
            $actionUserId = $shopInfo['id'];
        } else {
            //管理员登陆
            $actionUserId = $shopInfo['userId'];
        }
        $where = [];
        $where['id'] = $orderId;
        $where['shopId'] = $shopId;
        $orderInfo = $this->getPosOrderInfo($where);
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '订单不存在');
        }
        if ($orderInfo['state'] != 3) {
            return returnData(false, -1, 'error', '未结算订单不能退货');
        }
        $returnGoods = $pack['goods'];
        //校验退款商品数据-start
        $total_money = 0;//退款总金额
        foreach ($returnGoods as &$item) {
            $item['goodsId'] = (int)$item['goodsId'];
            $item['skuId'] = (int)$item['skuId'];
            $item['goodsName'] = (string)$item['goodsName'];
            $item['number'] = (int)$item['number'];
            $item['weight'] = (float)$item['weight'];
            $item['originalPrice'] = (float)$item['originalPrice'];
            $item['favorablePrice'] = (float)$item['favorablePrice'];
            $item['presentPrice'] = (float)$item['presentPrice'];
            $item['discount'] = (float)$item['discount'];
            $item['subtotal'] = (float)$item['subtotal'];
            $item['integral'] = (int)$item['integral'];
            $goodsId = $item['goodsId'];
            $skuId = $item['skuId'];
            $goodsName = $item['goodsName'];
            $number = $item['number'];
            $weight = $item['weight'];
            if (empty($goodsName)) {
                return returnData(false, -1, 'error', "参数不全");
            }
            if (empty($goodsId)) {
                return returnData(false, -1, 'error', "商品【{$goodsName}】商品id错误");
            }
            if (empty($number)) {
                return returnData(false, -1, 'error', "商品【{$goodsName}】退货数量错误");
            }
            $where = [];
            $where['pog.goodsId'] = $goodsId;
            $where['pog.orderid'] = $orderId;
            $where['pog.skuId'] = $skuId;
            $pos_order_goods_info = M('pos_orders_goods as pog')
                ->join('left join wst_goods as g on g.goodsId = pog.goodsId')
                ->field('pog.*,g.SuppPriceDiff')
                ->where($where)
                ->find();
            if (empty($pos_order_goods_info)) {
                return returnData(false, -1, 'error', "商品【{$goodsName}】和订单不匹配");
            }
            //判断商品是否已退过
            if ($pos_order_goods_info['isRefund'] == 3) {
                return returnData(false, -1, 'error', "商品【{$goodsName}】退货完成，不能重复退货");
            }
            if ($pos_order_goods_info['SuppPriceDiff'] < 0) {//标品
                if ($pos_order_goods_info['number'] < $number) {
                    return returnData(false, -1, 'error', "商品【{$goodsName}】退货数量不能大于购买时的数量");
                }
                $money_t = bc_math($pos_order_goods_info['presentPrice'], $number, 'bcmul', 2);
            } else if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                if (empty($weight)) {
                    return returnData(false, -1, 'error', "商品【{$goodsName}】退货的重量不能为空");
                }
                if ($pos_order_goods_info['weight'] < $weight) {
                    return returnData(false, -1, 'error', "商品【{$goodsName}】退货的重量不能大于购买时的重量");
                }
                if ($pos_order_goods_info['weight'] == $weight) {
                    $money_t = bc_math($pos_order_goods_info['presentPrice'], $number, 'bcmul', 2);
                } else {
                    $signleGoodsPrice = mathWeightPrice($pos_order_goods_info['goodsId'], $weight, $pos_order_goods_info['presentPrice']);
                    $money_t = bc_math($signleGoodsPrice, $number, 'bcmul', 2);
                }
            }
            $total_money += $money_t;
        }
        unset($item);
        $total_money = formatAmount($total_money, 2);
        //检验前端计算的退款金额和后端计算的是否一样
        if ($total_money != $wrapRealpayment) {
            return returnData(false, -1, 'error', "退款金额不正确，应当退款{$total_money}元");
        }
        //校验退款商品数据-start
        M()->startTrans();
        $where = [];
        $where['dataFlag'] = 1;
        $where['orderId'] = $orderInfo['id'];
        $givebackOrderInfo = $givebackOrdersTab->where($where)->find();
        if (empty($givebackOrderInfo)) {
            //创建退货单
            $mod_pos_order_id = M('pos_order_id')->add(array('id' => ''));//获取增量id
            $time = date('Ymd');
            // 拼接订单号
            $billNo = $time . $mod_pos_order_id;
            $giveData = [];
            $giveData['orderId'] = $orderInfo['id'];
            $giveData['billNo'] = $billNo;
            $giveData['state'] = 1;//退款状态(1:待退款 2：部分退款 3：已退款)
            $giveData['pay'] = 1;//退款方式(1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付)
            $giveData['actionUserId'] = $actionUserId;
            $giveData['outTradeNo'] = '';
            $giveData['realpayment'] = $total_money;
            $time = date('Y-m-d H:i:s', time());
            $giveData['createTime'] = $time;
            $giveData['updateTime'] = $time;
            $backId = $givebackOrdersTab->add($giveData);
        } else {
            $backId = $givebackOrderInfo['backId'];
        }
        if (empty($backId)) {
            M()->rollback();
            return returnData(false, -1, 'error', '退货单创建失败或不存在');
        }
        $givebackGoods = [];
        $total_goods_integral = 0;//本次需要退的积分
        foreach ($returnGoods as $item) {
            $goods_integral = 0;//退还积分
            $goodsId = $item['goodsId'];
            $skuId = $item['skuId'];
            $where = [];
            $where['pog.goodsId'] = $goodsId;
            $where['pog.skuId'] = $skuId;
            $where['pog.orderid'] = $orderId;
            $field = 'pog.*,g.SuppPriceDiff';
            $pos_order_goods_info = M('pos_orders_goods as pog')//购买的商品信息
            ->join('left join wst_goods as g on g.goodsId = pog.goodsId')
                ->where($where)
                ->field($field)
                ->find();
            $where = [];
            $where['dataFlag'] = 1;
            $where['backId'] = $backId;
            $where['goodsId'] = $goodsId;
            $where['skuId'] = $skuId;
            $giviback_goods_info = $givebackOrdersGoodsTab->where($where)->find();//退货记录
            //默认是标品
            $subtotal = bc_math($pos_order_goods_info['presentPrice'], $item['number'], 'bcmul', 2);
            if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                $signleGoodsPrice = mathWeightPrice($pos_order_goods_info['goodsId'], $pos_order_goods_info['weight'], $pos_order_goods_info['presentPrice']);
                $subtotal = bc_math($signleGoodsPrice, $item['number'], 'bcmul', 2);
            }
            if (!empty($giviback_goods_info)) {
                $givebackGoodsInfo = [];
                //已经退过至少一件的商品
                if ($giviback_goods_info['state'] == 3) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "商品【{$pos_order_goods_info['goodsName']}】退货完成，不能重复退货");
                }
                $nowNumber = $item['number'] + $giviback_goods_info['number'] + $pos_order_goods_info['exchangeNum'];
                if ($nowNumber > $pos_order_goods_info['number']) {
                    $returnNumber = $pos_order_goods_info['number'] - $giviback_goods_info['number'] - $pos_order_goods_info['exchangeNum'];
                    $msg = "商品【{$pos_order_goods_info['goodsName']}】本次最多退货数量为{$returnNumber}";
                    $msg .= "，包含已退货商品数量：{$pos_order_goods_info['refundNum']}";
                    if ($pos_order_goods_info['exchangeNum'] > 0) {
                        $msg .= "，已换货商品数量：{$pos_order_goods_info['exchangeNum']}";
                    }
                    M()->rollback();
                    return returnData(false, -1, 'error', $msg);
                }
                $goods_integral = bc_math($pos_order_goods_info['integral'], $pos_order_goods_info['number'], 'bcdiv', 0);
                if (($pos_order_goods_info['number'] - $giviback_goods_info['number']) == $item['number']) {
                    //该商品最后一次退货,该商品剩下的积分都给你吧
                    $goods_integral = $pos_order_goods_info['integral'] - ($giviback_goods_info['number'] * $goods_integral);
                    $givebackGoodsInfo['state'] = 3;
                }
                $givebackGoodsInfo['number'] = $giviback_goods_info['number'] + $item['number'];
                $givebackGoodsInfo['subtotal'] = $giviback_goods_info['subtotal'] + $subtotal;
                $givebackGoodsInfo['integral'] = $giviback_goods_info['integral'] + $goods_integral;
                $givebackGoodsInfo['weight'] = $item['weight'];
                $givebackGoodsInfo['updateTime'] = date('Y-m-d H:i:s', time());
                $where = [];
                $where['id'] = $giviback_goods_info['id'];
                $updateGivebackGooodsRes = $givebackOrdersGoodsTab->where($where)->save($givebackGoodsInfo);
                if (!$updateGivebackGooodsRes) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "退货失败，商品【{$pos_order_goods_info['goodsName']}】退货失败");
                }
                $givebackGoodsInfo['number'] = $item['number'];
            } else {
                $nowNumber = $item['number'] + $pos_order_goods_info['exchangeNum'];
                if ($nowNumber > $pos_order_goods_info['number']) {
                    $returnNumber = $pos_order_goods_info['number'] - $giviback_goods_info['number'] - $pos_order_goods_info['exchangeNum'];
                    $msg = "商品【{$pos_order_goods_info['goodsName']}】本次最多退货数量为{$returnNumber}";

                    if ($pos_order_goods_info['exchangeNum'] > 0) {
                        $msg .= "，包含已换货商品数量：{$pos_order_goods_info['exchangeNum']}";
                    }
                    M()->rollback();
                    return returnData(false, -1, 'error', $msg);
                }
                //从未退过的商品
                $givebackGoodsInfo = [];
                if ($pos_order_goods_info['integral'] > 0) {
                    if ($pos_order_goods_info['number'] == $item['number']) {
                        $goods_integral = $pos_order_goods_info['integral'];
                    } else {
                        $goods_integral = bc_math($pos_order_goods_info['integral'], $pos_order_goods_info['number'], 'bcdiv', 0);
                    }
                }
                $givebackGoodsInfo['backId'] = $backId;
                $givebackGoodsInfo['goodsId'] = $goodsId;
                $givebackGoodsInfo['skuId'] = $skuId;
                $givebackGoodsInfo['presentPrice'] = $pos_order_goods_info['presentPrice'];
                $givebackGoodsInfo['number'] = $item['number'];
                $givebackGoodsInfo['subtotal'] = $subtotal;
                $givebackGoodsInfo['state'] = $item['number'] == $pos_order_goods_info['number'] ? 3 : 2;
                $givebackGoodsInfo['weight'] = $item['weight'];
                $givebackGoodsInfo['integral'] = $goods_integral;
                $givebackGoodsInfo['createTime'] = date('Y-m-d H:i:s', time());
                $givebackGoodsInfo['updateTime'] = date('Y-m-d H:i:s', time());
                $givebackGoods[] = $givebackGoodsInfo;
            }
            //将原订单商品标记为已退货
            $isRefund = $givebackGoodsInfo['state'] == 3 ? 3 : 2;
            $where = [];
            $where['goodsId'] = $goodsId;
            $where['orderid'] = $orderId;
            $where['state'] = 1;
            $saveData = [];
            $saveData['isRefund'] = $isRefund;
            $saveData['refundNum'] = $pos_order_goods_info['refundNum'] + $item['number'];
            $updateRefundRes = $posOrderGoodsTab->where($where)->save($saveData);
            if ($updateRefundRes === false) {
                M()->rollback();
                return returnData(false, -1, 'error', '退货失败，退货状态修改失败');
            }
            //改库存
            if ($pos_order_goods_info['weight'] > 0) {
                //非标品
                $num = $givebackGoodsInfo['weight'] * $givebackGoodsInfo['number'];
            } else {
                //标品
                $num = $givebackGoodsInfo['number'];
            }
            $updateStockRes = $goodsTab->where(['goodsId' => $goodsId])->setInc('goodsStock', $num);
            if ($skuId > 0) {
                //返还sku属性库存
                $updateStockRes = M('sku_goods_system')->where(['skuId' => $skuId])->setInc('skuGoodsStock', $num);
            }
            if (!$updateStockRes) {
                M()->rollback();
                return returnData(false, -1, 'error', "退货失败，【{$pos_order_goods_info['goodsName']}】库存修改失败");
            }
            $total_goods_integral += $goods_integral;
        }
        if (!empty($givebackGoods)) {
            $insertRes = $givebackOrdersGoodsTab->addAll($givebackGoods);
            if (!$insertRes) {
                M()->rollback();
                return returnData(false, -1, 'error', "退货失败");
            }
        }
        //退积分
        if ($total_goods_integral > 0) {
            if ($orderInfo['memberId'] > 0) {
//                $returnScoreRes = $usersTab->where(array('userId' => $orderInfo['memberId']))->setInc('userScore', $total_goods_integral);
//                if (!$returnScoreRes) {
//                    M()->rollback();
//                    return returnData(false, -1, 'error', '积分返回失败');
//                }
                //积分处理-start
                $users_service_module = new UsersServiceModule();
                $score = (int)$total_goods_integral;
                $users_id = $orderInfo['memberId'];
                $result = $users_service_module->return_users_score($users_id, $score, M());
                if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                    M()->rollback();
                    return returnData(null, -1, 'error', $result['msg']);
                }
//积分处理-end
            }
        }
        //更新退货单信息
        $where = [];
        $where['dataFlag'] = 1;
        $where['backId'] = $backId;
        //$where['state'] = 3;
        $givebackOrderGoods = $givebackOrdersGoodsTab->where($where)->select();
        $givebackOrderParams = [];
        $givebackOrderParams['realpayment'] = 0;//实退金额
        $givebackOrderParams['setintegral'] = 0;//实退积分
        $givebackOrderParams['updateTime'] = date('Y-m-d H:i:s', time());
        $doneReturnGoodsNum = 0;//已退货数量
        foreach ($givebackOrderGoods as $gitem) {
            $givebackOrderParams['realpayment'] += $gitem['subtotal'];
            $givebackOrderParams['setintegral'] += $gitem['integral'];
            $doneReturnGoodsNum += $gitem['number'];
        }
        $orderGoodsWhere = [];
        $orderGoodsWhere['orderid'] = $orderInfo['id'];
        $orderGoodsWhere['state'] = 1;
        $orderGoodsNum = $posOrderGoodsTab->where($orderGoodsWhere)->sum('number');
        if (bccomp($doneReturnGoodsNum, 0) == 1) {
            $givebackOrderParams['state'] = 2;
        }
        if (bccomp($doneReturnGoodsNum, $orderGoodsNum) >= 0) {
            $givebackOrderParams['state'] = 3;
        }
        $where = [];
        $where['backId'] = $backId;
        $updateGivebackOrderRes = $givebackOrdersTab->where($where)->save($givebackOrderParams);
        if (!$updateGivebackOrderRes) {
            M()->rollback();
            return returnData(false, -1, 'error', '退货失败，退货单修改失败');
        }
        M()->commit();
        return returnData(true);
    }

    /**
     * 退货/换货-订单商品搜索(条码|编码)
     * @param int $shopId
     * @param int $orderId 订单id
     * @param string $barcode (编码|条码)
     * */

    public function searchOrderGoodsInfo(int $shopId, int $orderId, string $barcode)
    {
        $barcodeArr = explode('-', $barcode);
        if ($barcodeArr[0] == 'CZ') {
            //条码查找
            $barcodeWhere = [];
            $barcodeWhere['barcode'] = $barcode;
            $barcodeWhere['shopId'] = $shopId;
            $barcodeInfo = M('barcode')->where($barcodeWhere)->field('goodsId,skuId')->find();
            if (empty($barcodeInfo)) {
                return returnData(false, -1, 'error', '条码查询失败');
            }
        } else {
            //编码查找
            $skuWhere = [];
            $skuWhere['system.skuBarcode'] = $barcode;
            $skuWhere['system.dataFlag'] = 1;
            $skuWhere['goods.goodsId'] = ['GT', 0];
            $skuWhere['goods.shopId'] = $shopId;
            $barcodeInfo = M('sku_goods_system system')
                ->join('left join wst_goods goods on goods.goodsId = system.goodsId')
                ->where($skuWhere)
                ->field('system.goodsId,system.skuId')
                ->find();
            if (empty($barcodeInfo)) {
                //标品编码
                $where = [];
                $where['goodsFlag'] = 1;
                $where['shopId'] = $shopId;
                $where['goodsSn'] = $barcode;
                $barcodeInfo = M('goods')->where($where)->field('goodsId')->find();
                $barcodeInfo['skuId'] = 0;
            }
            if (empty($barcodeInfo['goodsId'])) {
                return returnData(false, -1, 'error', '编码查询失败');
            }
            $where = [];
            $where['order_goods.orderid'] = $orderId;
            $where['order_goods.goodsId'] = $barcodeInfo['goodsId'];
            $where['order_goods.skuId'] = $barcodeInfo['skuId'];
            $where['order_goods.state'] = 1;
            $orderGoodsInfo = M('pos_orders_goods order_goods')->where($where)->find();
            if (empty($orderGoodsInfo)) {
                return returnData(false, -1, 'error', '订单商品查询失败');
            }
            $orderGoodsInfo['discount'] = ($orderGoodsInfo['discount'] * 100) . '%';
            $orderGoodsInfo['SuppPriceDiff'] = M('goods')->where(['goodsId' => $orderGoodsInfo['goodsId']])->getField('SuppPriceDiff');
            return returnData($orderGoodsInfo);
        }
    }

    /**
     * @param int $shopId
     * @param array $params <p>
     * string billNo 退货单号
     * string orderNO 订单号
     * string userName 用户名
     * string userPhone 手机号
     * string user_name 收营员-账号
     * string user_username 收营员-名称
     * string user_phone 收营员-电话
     * int pay 退款方式(1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付)
     * int state 退货状态(1:待退货 2：部分退货 3：全退)
     * datetime startDate 单据时间区间-开始时间
     * datetime endDate 单据时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     * */

    public function getGivebackOrderList(int $shopId, array $params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " givebackOrders.dataFlag=1 and orders.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['givebackOrders.billNo'] = function () use ($params) {
            if (empty($params['billNo'])) {
                return null;
            }
            return ['like', "%{$params['billNo']}%", 'and'];
        };
        $whereFind['user.name'] = function () use ($params) {
            if (empty($params['user_name'])) {
                return null;
            }
            return ['like', "%{$params['user_name']}%", 'and'];
        };
        $whereFind['user.username'] = function () use ($params) {
            if (empty($params['user_username'])) {
                return null;
            }
            return ['like', "%{$params['user_username']}%", 'and'];
        };
        $whereFind['user.phone'] = function () use ($params) {
            if (empty($params['user_phone'])) {
                return null;
            }
            return ['like', "%{$params['user_phone']}%", 'and'];
        };
        $whereFind['orders.orderNO'] = function () use ($params) {
            if (empty($params['orderNO'])) {
                return null;
            }
            return ['like', "%{$params['orderNO']}%", 'and'];
        };
        $whereFind['users.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['users.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        $whereFind['givebackOrders.pay'] = function () use ($params) {
            if (empty($params['pay'])) {
                return null;
            }
            return ['=', "{$params['pay']}", 'and'];
        };
        $whereFind['givebackOrders.state'] = function () use ($params) {
            if (empty($params['state'])) {
                return null;
            }
            return ['=', "{$params['state']}", 'and'];
        };
        $whereFind['givebackOrders.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        $field = 'orders.orderNO,orders.realpayment as orderRealpayment,orders.setintegral as orderSetintegral';
        $field .= ',users.userName,users.userPhone';
        $field .= ',givebackOrders.*';
        $field .= ',user.name as user_name,user.username as user_username,user.phone as user_phone';
        $sql = "select {$field} from __PREFIX__pos_giveback_orders givebackOrders 
                left join __PREFIX__pos_orders orders on orders.id=givebackOrders.orderId
                left join __PREFIX__users users on users.userId=orders.memberId
                left join __PREFIX__user `user` on orders.userId=`user`.id
                where {$whereInfo} order by givebackOrders.backId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$item) {
                $item['userName'] = (string)$item['userName'];
                $item['userPhone'] = (string)$item['userPhone'];
                $item['user_name'] = (string)$item['user_name'];
                $item['user_username'] = (string)$item['user_username'];
                $item['user_phone'] = (string)$item['user_phone'];
            }
            unset($item);
            $data['root'] = $root;
        }
        return returnData($data);
    }

    /**
     * 退货单详情
     * @param int $backId 退货单id
     * */

    public function getGivebackOrderDetail(int $backId)
    {
        $where = [];
        $where['giveback.backId'] = $backId;
        $where['giveback.dataFlag'] = 1;
        $field = 'giveback.*';
        $field .= ',orders.orderNO,orders.realpayment as orderRealpayment,orders.setintegral as orderSetintegral';
        $field .= ',users.userName,users.userPhone';
        $field .= ',u.name as user_name,u.username as user_username,u.phone as user_phone';
        $data = M('pos_giveback_orders giveback')
            ->join('left join wst_pos_orders orders on orders.id=giveback.orderId')
            ->join('left join wst_users users on users.userId=orders.memberId')
            ->join('left join wst_user u on u.id=orders.userId')
            ->where($where)
            ->field($field)
            ->find();

        if (empty($data)) {
            return [];
        }
        $data['userName'] = (string)$data['userName'];
        $data['userPhone'] = (string)$data['userPhone'];
        $data['user_name'] = (string)$data['user_name'];
        $data['user_username'] = (string)$data['user_username'];
        $data['user_phone'] = (string)$data['user_phone'];
        //goods
        $field = 'back_goods.*';
        $field .= ',pos_goods.goodsName,pos_goods.skuSpecAttr,pos_goods.number as orderGoodsNumber';
        $where = [];
        $where['backId'] = $backId;
        $where['dataFlag'] = 1;
        $returnGoods = M('pos_giveback_orders_goods back_goods')
            ->join('left join wst_pos_orders_goods pos_goods on pos_goods.goodsId=back_goods.goodsId')
            ->where($where)
            ->field($field)
            ->group('back_goods.id')
            ->select();
        $data['goods'] = $returnGoods;
        return $data;
    }

    /**
     * 获得POS订单详情
     * @param $where
     * @return array
     */
    public function getPosOrderInfo($where)
    {
        $data = M('pos_orders')->where($where)->find();
        if (empty($data)) {
            return [];
        }
        $data['discount'] = ($data['discount'] * 10) . '%';
        $data['shopName'] = M('shops')->where(array('shopId' => $data['shopId']))->field('shopName')->getField('shopName');
        $cashier_user_info = M('user')->where(array(
                'id' => $data['userId'],
                'status' => 0
            )
        )->find();
        if (empty($cashier_user_info)) {
            $cashier_user_info = M('users')->where(array(
                    'userId' => $data['userId'],
                    'userFlag' => 1
                )
            )->field('userName as username')->find();
        }
        $data['cashier_username'] = $cashier_user_info['username'];
        return $data;
    }

    /**
     * 获得POS订单商品列表
     * @param $where
     */
    public function getPosOrderGoodsList($where)
    {
        return M('pos_orders_goods')->where($where)->select();
    }

    /**
     * 根据订单编号获取订单详情和订单商品
     * @param $shopId
     * @param $orderNO
     */
    public function getOrderDetailAndOrderGoodsByOrderNo($shopId, $orderNO)
    {
        $pos_order_info = $this->getPosOrderInfo(array('orderNO' => $orderNO, 'shopId' => $shopId));
        if (empty($pos_order_info)) {
            return returnData(false, -1, 'error', ' 订单不存在');
        }
        $pos_order_info['realpayment'] = $pos_order_info['real_accept_amount'];//前端用错字段,这里临时修改下
        $pos_order_info['discount'] = ((float)$pos_order_info['discount'] * 10) . '%';
        $pos_order_goods_list = $this->getPosOrderGoodsList(array('orderid' => $pos_order_info['id']));
        $gm = M('goods');
        $goods_key_value_arr = $gm->getField('goodsId,SuppPriceDiff');
        foreach ($pos_order_goods_list as $k => $v) {
            $pos_order_goods_list[$k]['SuppPriceDiff'] = $goods_key_value_arr[$v['goodsId']];
            $pos_order_goods_list[$k]['discount'] = ($v['discount'] * 100) . '%';
        }
        $data = array(
            'pos_order_info' => (array)$pos_order_info,
            'pos_order_goods_list' => (array)$pos_order_goods_list
        );
        return returnData((array)$data);
    }

    /**
     * 扫码支付
     * @param int $shopId
     * @param int $type 类型，（4：微信 5：支付宝）
     * @param string $auth_code 付款码
     * @param float $money 金额（元）
     * @param string $orderNo 订单号
     * */
    public function scanPay($shopId, $type, $auth_code, $money, $orderNo)
    {
        $shopInfo = $this->getShopInfo($shopId);
        if (empty($shopInfo)) {
            return returnData(false, -1, 'error', '店铺信息有误');
        }
        $userId = $shopInfo['userId'];
        if ($money <= 0) {
            return returnData(false, -1, 'error', '请输入大于0的金额');
        }
        if ($type == 4) {//微信
            $result = $this->doWxPay($auth_code, $money, $orderNo);
        } else if ($type == 5) {//支付宝
            $result = $this->doAliPay($userId, $auth_code, $money, $orderNo);
        }
        if ($result['code'] != 0) {
            return returnData(false, -1, 'error', '支付失败');
        }
        if (!empty($result['transaction_id'])) {
            M('pos_orders')->where(array('orderNO' => $orderNo))->save(array('outTradeNo' => $result['transaction_id']));
        }
        return returnData(true);
    }

    /**
     * 获取商品列表
     */
    public function getShopGoodsList($paramete = array())
    {
        $shopId = $paramete['shopId'];
        $page = $paramete['page'];
        $pageSize = $paramete['pageSize'];
        $shopCatId1 = (int)$paramete['shopCatId1'];
        $shopCatId2 = (int)$paramete['shopCatId2'];
        $goodCatId1 = (int)$paramete['goodCatId1'];
        $keywords = $paramete['keywords'];
        if (!empty($paramete['isSale'])) {
            $paramete['isSale'] = $paramete['isSale'] == 2 ? 0 : $paramete['isSale'];
        }
        if (!empty($paramete['goodsStatus'])) {
            $paramete['goodsStatus'] = $paramete['goodsStatus'] == 2 ? 0 : $paramete['goodsStatus'];
        }
        $goodsName = WSTAddslashes(I('goodsName'));
        $goodsSn = WSTAddslashes(I('goodsSn'));
        $minMoney = $paramete['minMoney'];
        $maxMoney = $paramete['maxMoney'];
        $sql = "select g.* from __PREFIX__goods g where g.goodsFlag=1 and g.shopId={$shopId}";
        if ($shopCatId1 > 0) $sql .= " and g.shopCatId1=" . $shopCatId1;
        if ($shopCatId2 > 0) $sql .= " and g.shopCatId2=" . $shopCatId2;
        if ($goodCatId1 > 0) $sql .= " and g.goodsCatId1=" . $goodCatId1;
        if ($minMoney > 0) $sql .= " and g.shopPrice >= {$minMoney} ";
        if ($maxMoney > 0) $sql .= " and g.shopPrice <= {$maxMoney} ";
        //二开
        if (is_numeric($paramete['goodsStatus'])) $sql .= " and g.goodsStatus=" . $paramete['goodsStatus'];
        if (is_numeric($paramete['isSale']) && in_array($paramete['isSale'], [0, 1])) $sql .= " and g.isSale=" . $paramete['isSale'];
        if (isset($paramete['SuppPriceDiff'])) $sql .= " and g.SuppPriceDiff=" . $paramete['SuppPriceDiff'];
        //END
        if (!empty($goodsName)) $sql .= " and (g.goodsName like '%" . $goodsName . "%') ";
        if (!empty($goodsSn)) $sql .= " and (g.goodsSn like '%" . $goodsSn . "%') ";
        if (!empty($keywords)) $sql .= " and ((g.goodsSn like '%" . $keywords . "%') or (g.goodsName like '%" . $keywords . "%') or (g.plu_code like '%{$keywords}%')) ";
        if (!empty($paramete['goodsAttr']) && in_array($paramete['goodsAttr'], array('isAdminRecom', 'isAdminBest', 'isNew', 'isHot', 'isMembershipExclusive', 'isShopSecKill', 'isAdminShopSecKill', 'isAdminShopPreSale', 'isShopPreSale'))) $sql .= " and g." . $paramete['goodsAttr'] . " = 1 ";
        $sql .= " order by g.shopGoodsSort desc";
        $list = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($list['root'])) {
            $goods = $list['root'];
            $shopCatIdArr = [];//店铺分类id
            $goodsCatIdArr = [];//商城分类id
            foreach ($goods as $value) {
                $shopCatIdArr[] = $value['shopCatId1'];
                $shopCatIdArr[] = $value['shopCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId1'];
                $goodsCatIdArr[] = $value['goodsCatId2'];
                $goodsCatIdArr[] = $value['goodsCatId3'];
            }
            $shopCatIdArr = array_unique($shopCatIdArr);
            $shopCatIdStr = implode(',', $shopCatIdArr);
            $shopCatList = M('shops_cats')->where(['catId' => ['IN', $shopCatIdStr]])->select();
            $goodsCatIdArr = array_unique($goodsCatIdArr);
            $goodsCatIdStr = implode(',', $goodsCatIdArr);
            $goodsCatIdList = M('goods_cats')->where(['catId' => ['IN', $goodsCatIdStr]])->select();
            foreach ($goods as $key => $value) {
                foreach ($shopCatList as $shopCat) {
                    if ($value['shopCatId1'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId1Name'] = $shopCat['catName'];
                    }
                    if ($value['shopCatId2'] == $shopCat['catId']) {
                        $goods[$key]['shopCatId2Name'] = $shopCat['catName'];
                    }
                }
                foreach ($goodsCatIdList as $goodsCat) {
                    if ($value['goodsCatId1'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId1Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId2'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId2Name'] = $goodsCat['catName'];
                    }
                    if ($value['goodsCatId3'] == $goodsCat['catId']) {
                        $goods[$key]['goodsCatId3Name'] = $goodsCat['catName'];
                    }
                }
            }
            $list['root'] = $goods;
        }
        $list['root'] = getGoodsSku($list['root']);
        return $list;
    }

    /**
     * 获取店铺分类列表
     * @param int $shopId
     * @param int $parentId 父级分类id
     */
    public function getShopCatsList(int $shopId, int $parentId, $page, $pageSize)
    {
        $sql = "SELECT * FROM __PREFIX__shops_cats WHERE  shopId={$shopId} and catFlag=1 and parentId={$parentId} order by catSort asc";
        return $this->pageQuery($sql, $page, $pageSize);
    }

    /**
     * 生成条码
     * @param array $loginUser 当前登陆者
     * @param int $goodsId
     * @param number $weight
     * */
    public function getPosBarcode(array $loginUser, $goodsId, $weight)
    {
        $shopId = $loginUser['shopId'];
        if (!empty($loginUser['id'])) {
            $user = [
                'sid' => $loginUser['id'],
                'suserName' => $loginUser['userName'],
                'smobile' => $loginUser['mobile'],
            ];
        } else {
            $user = [
                'sid' => $loginUser['userId'],
                'suserName' => $loginUser['userName'],
                'smobile' => $loginUser['userPhone'],
            ];
        }
        $where = [];
        $where['goodsId'] = $goodsId;
        $where['shopId'] = $shopId;
        $goodsInfo = M('goods')->where($where)->field('goodsId,goodsSn,goodsName,shopPrice,weightG,SuppPriceDiff')->find();
        if (empty($goodsInfo)) {
            return returnData(false, -1, 'error', '商品信息有误');
        }

        if ($goodsInfo['SuppPriceDiff'] != 1) {
            return returnData(false, -1, 'error', '非称重商品禁止调用该接口');
        }
        $saveData = [
            'shopId' => $shopId,
            'barcode' => '',
            'goodsId' => $goodsId,
            'weight' => $weight,
            'price' => mathWeightPrice($goodsId, $weight),
            'orderNo' => '',
            'basketId' => 0,
            'sid' => $user['sid'],
            'suserName' => $user['suserName'],
            'smobile' => $user['smobile'],
            'createTime' => date('Y-m-d H:i:s'),
            'bFlag' => 1
        ];
        $barcodeTab = M('barcode');
        $id = $barcodeTab->add($saveData);
        if (!id) {
            return returnData(false, -1, 'error', '条码生成失败');
        }
        $code = joinString($id, 0, 18);
        $barcode = 'CZ-' . $code;
        $where['id'] = $id;
        $saveData = [];
        $saveData['barcode'] = $barcode;
        $result = $barcodeTab->where($where)->save($saveData);
        if (!$result) {
            return returnData(false, -1, 'error', '条码保存失败');
        }
        $data = array(
            'id' => $id,
            'barcode' => $barcode
        );
        return returnData($data);
    }

    /**
     *会员-会员码登录/实体卡登录
     * @param string $userCode 会员码/实体卡号
     * */
    public function getUserInfoByUserCode($userCode)
    {
        $cardNum = $userCode;
        $where = [];
        $where['userFlag'] = 1;
        $where['cardNum'] = $cardNum;
        $userInfo = D('Home/Users')->getUserInfoRow($where);//实体卡号登陆
        if (empty($userInfo)) {
            $userInfo = $this->userInfoByUserCode($userCode)['data'];
        }
        if (empty($userInfo)) {
            return returnData(false, -1, 'error', '获取用户信息失败');
        }
        $userInfo['memberToken'] = getUserTokenByUserId($userInfo['userId']);
        $userInfo['historyConsumeIntegral'] = (int)historyConsumeIntegral($userInfo['userId']);
        return returnData($userInfo);
    }

    /**
     *获取用户信息-通过会员动态码
     * @param $userCode 会员码
     */
    public function userInfoByUserCode($userCode)
    {
        $mod_users_dynamiccode = M('users_dynamiccode');
        $mod_users = M('users');
        $where['state'] = 2;
        $where['code'] = $userCode;
        $data = $mod_users_dynamiccode->where($where)->find();
        if (!$data) {
            return returnData(false, -1, 'error', '会员码不存在或已使用');
        }
        $outTime = strtotime($data['addtime']) + 60;
        if (time() > $outTime) {
            return returnData(false, -1, 'error', '会员码已过期');
        }
        $userInfo = $mod_users->where('userId=' . $data['userId'])->find();
        if (!empty($userInfo['userId'])) {
            $expireTimeState = -1;//会员过期状态【-1：失效|1：有效】
            if (!empty($userInfo['expireTime']) && $userInfo['expireTime'] > date('Y-m-d H:i:s', time())) {
                $expireTimeState = 1;
            }
            $userInfo['expireTimeState'] = $expireTimeState;
        }
        $mod_users_dynamiccode->where(['id' => $data['id']])->save(['state' => 1]);
        return returnData($userInfo);
    }

    /**
     *会员-搜索会员列表
     * @param string $keywords
     * @param int $page 页码
     * @param int $pageSize 分页条数
     * */
    public function searchUser(string $keywords, $page, $pageSize)
    {
        $keywords = trim($keywords);
        $field = 'users.userId,users.userName,users.userPhone,users.userPhoto,users.cardNum,users.balance';
        //or (code.code like '%{$keywords}%'))
        //模糊搜索三张表 后面数据量越来越大 会导致很大的性能问题 分段查询即可 对于会员码和实体卡必须要准确查询 不能模糊 而且会员码是否过期未看到处理

        //TODO：等待拆分重写 逻辑   参照Apps\Merchantapi\Model\PosModel.class.php 下的 public function userInfo
        //1.如果会员码有数据就不要查询其他数据 （移动端的普及一般此种用的多 排列第一）
        //code...
        $field_code = $field . ',code.addtime';
        $where = " users.userFlag=1 and code.state=2 and code.code={$keywords}";
        $sql = "select {$field_code} from __PREFIX__users users left join __PREFIX__users_dynamiccode code on code.userId=users.userId where {$where} group by users.userId";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as $key => &$item) {
                $outtime = strtotime($item['addtime']) + 60;//会员码六十秒过期
                if ($outtime < time()) {
                    unset($root[$key]);
                }
            }
            unset($item);
            $data['root'] = array_values($root);
        }
        //2.如果会员实体卡有数据就不要查询其他数据 （会员卡略少）
        //code...
        if (empty($data['root'])) {
            $where = " users.userFlag=1 and users.cardState=1 and users.cardNum='{$keywords}'";
            $sql = "select {$field} from __PREFIX__users users where {$where} group by users.userId";
            $data = $this->pageQuery($sql, $page, $pageSize);
        }
        //3.都没有数据只能模糊查询 （收银台直接搜索会员可能性很低 排列第三）
        //code...
        if (empty($data['root'])) {
            $where = " users.userFlag=1 and (users.userName like '%{$keywords}%') or (users.userPhone like '%{$keywords}%') ";
            $field = 'users.userId,users.userName,users.userPhone,users.userPhoto,users.cardNum,users.balance';
            $sql = "select {$field} from __PREFIX__users users where {$where} group by users.userId";
            $data = $this->pageQuery($sql, $page, $pageSize);
        }
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as $key => &$item) {
                $item['cardNum'] = (string)$item['cardNum'];
                if (!empty($item['cardNum'])) {
                    $total_num = strlen($item['cardNum']);
                    $center_num = $total_num - 4 - 3;
                    $split = "";
                    $split = str_pad($split, $center_num, "*", STR_PAD_LEFT);
                    $item['cardNum'] = substr_replace($item['cardNum'], '**', 2, 3);
                    $item['cardNum'] = substr_replace($item['cardNum'], $split, 4, -3);
                }
            }
            unset($item);
            $data['root'] = array_values($root);
        }
        //下方等待重构 只有模糊查询才需要分页  且不是为了获取某一个会员分页 可以保留分页 会员码和实体卡就不用分页 且 保留该接口array的数据风格即可 减少前端改动
//        $where = " users.userFlag=1 and users.cardState=1 ";
//        $where .= " and ((users.userName like '%{$keywords}%') or (users.userPhone like '%{$keywords}%') or (users.cardNum like '%{$keywords}%') or (code.code like '%{$keywords}%') and code.state=2) ";
//        $field = 'users.userId,users.userName,users.userPhone,users.userPhoto,users.cardNum,users.balance';
//        $sql = "select {$field} from __PREFIX__users users left join __PREFIX__users_dynamiccode code on code.userId=users.userId where {$where} group by users.userId";
//        $data = $this->pageQuery($sql, $page, $pageSize);
//        if (!empty($data['root'])) {
//            $root = $data['root'];
//            foreach ($root as &$item) {
//                $item['cardNum'] = (string)$item['cardNum'];
//                if (!empty($item['cardNum'])) {
//                    $total_num = strlen($item['cardNum']);
//                    $center_num = $total_num - 4 - 3;
//                    $split = "";
//                    $split = str_pad($split, $center_num, "*", STR_PAD_LEFT);
//                    $item['cardNum'] = substr_replace($item['cardNum'], '**', 2, 3);
//                    $item['cardNum'] = substr_replace($item['cardNum'], $split, 4, -3);
//                }
//            }
//            unset($item);
//            $data['root'] = $root;
//        }


        return returnData((array)$data);
    }

    /**
     * 会员-会员详情
     * @param int $userId
     * */
    public function getUserDetail(int $userId)
    {
        $usersModel = D('Home/Users');
        $where = [];
        $where['userFlag'] = 1;
        $where['userId'] = $userId;
        $userInfo = $usersModel->getUserInfoRow($where);
        if (empty($userInfo)) {
            return returnData([]);
        }
        $userInfo['historyConsumeIntegral'] = (int)historyConsumeIntegral($userInfo['userId']);//历史消费积分
        $userInfo['historyConsumption'] = $this->historyConsumption($userInfo['userId']);//历史消费金额
        return returnData($userInfo);
    }

    /**
     *历史消费金额
     * @param int $userId
     * */
    public function historyConsumption(int $userId)
    {
        $data = M('pos_orders')->where(array('memberId' => $userId))->sum('realpayment');
        return (float)$data;
    }

    /**
     * POS提交订单
     * @param int $shopId
     * @param array $pack
     * */
//    public function submit($shopId, $pack)
//    {
//        $shopInfo = $this->getShopInfo($shopId);
//        if (empty($shopInfo)) {
//            return returnData(false, -1, 'error', '门店信息有误');
//        }
//        $goodsTab = M('goods');
//        $posOrdersTab = M('pos_orders');
//        $orderGoodsTab = M('pos_orders_goods');
//        $dynaiccodeTab = M('users_dynamiccode');
//        $usersTab = M('users');
//        $userScoreTab = M('user_score');
//        $shopsTab = M('shops');
//        $systemSkuTab = M('sku_goods_system');
//        $shopId = $shopInfo['shopId'];
//        $userId = $pack['userId'];
//        $wrapGoods = $pack['goods'];
//        $wrapOrderNO = $pack['orderNO'];
//        $orderWhere = [];
//        $orderWhere['orderNO'] = $wrapOrderNO;
//        $orderInfo = $posOrdersTab->where($orderWhere)->find();
//        if ($orderInfo['shopId'] != $shopId) {
//            return returnData(false, -1, 'error', '订单非本门店订单或者订单不存在');
//        }
//        $wrapAlipay = $pack['alipay'];
//        $wrapWechat = $pack['wechat'];
//        $wrapAuthCode = $pack['auth_code'];
//        $wrapCash = $pack['cash'];
//        $wrapChange = $pack['change'];
//        $wrapBalance = $pack['balance'];
//        $wrapSetintegral = (int)$pack['setintegral'];
//        $wrapPay = $pack['pay'];
//        $wrapDiscount = $pack['discount'];
//        $wrapDiscountPrice = $pack['discountPrice'];
//        $wrapUnionpay = $pack['unionpay'];
//        $wrapIsCombinePay = $pack['isCombinePay'];
//        $goodsIdArr = [];
//        $goodsCountNum = 0;
//        foreach ($wrapGoods as $data) {
//            $goodsId = (int)$data['goodsId'];
//            $skuId = (int)$data['skuId'];
//            $where = [];
//            $where['goods.goodsId'] = $data['goodsId'];
//            $where['goods.shopId'] = $shopId;
//            $where['goods.goodsFlag'] = 1;
//            if ($skuId > 0) {
//                $where['system.skuId'] = $skuId;
//                $goodsCount = M('sku_goods_system system')
//                    ->join("left join wst_goods goods on g.goodsId=system.goodsId")
//                    ->where($where)
//                    ->count('goods.goodsId');
//            } else {
//                $goodsCount = M('goods goods')->where($where)->count('goods.goodsId');
//            }
//            if (is_null($goodsCount)) {
//                $goodsCount = 0;
//            }
//            $goodsCountNum += $goodsCount;
//            //新的 结束
//            array_push($goodsIdArr, $goodsId);
//        }
//        if ($goodsCountNum != count($wrapGoods)) {
//            return returnData(false, -1, 'error', '商品异常');
//        }
//        //计算商品总金额和总积分，并和传进来的总金额和总积分进行比对 -- start ---
//        $total_money = 0;
//        $total_score = 0;
//        $goodsId_arr = array();
//        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
//        foreach ($wrapGoods as $v) {
//            $goodsId = (int)$v['goodsId'];
//            $skuId = (int)$v['skuId'];
//            $SuppPriceDiff = $v['SuppPriceDiff'];
//            $shopPrice = (float)$v['shopPrice'];
//            $number = (float)$v['number'];
//            $weight = (float)$v['weight'];
//            $goods_info = $goodsTab->where(array('goodsId' => $goodsId))->find();
//            $discount = empty($v['discount']) ? 1 : $v['discount'] * 10 / 100;
//            if ($skuId > 0) {
//                //sku详情
//                $systemSkuInfo = $systemSkuTab->where(['skuId' => $skuId])->find();
//                foreach ($replaceSkuField as $rk => $rv) {
//                    if (isset($v[$rv])) {
//                        $v[$rv] = $systemSkuInfo[$rk];
//                    }
//                }
//            }
//            if ($SuppPriceDiff < 0) {//标品
//                $money_t = $shopPrice * $number * $discount;
//            } elseif ($SuppPriceDiff > 0) {//秤重商品
//                $money_t = ($shopPrice / $goods_info['weightG']) * $weight * $discount;
//            }
//            $total_money += $money_t;
//            $total_score += moneyToIntegral($money_t * $goods_info['integralRate']);
//            $goodsId_arr[] = $v['goodsId'];
//        }
//        $total_money = number_format($total_money, 2);
//        //获取用户信息 并改code为已使用
//        if (!empty($userId)) {
//            $where = [];
//            $where['userFlag'] = 1;
//            $where['userId'] = $userId;
//            $userinfo = M('users')->where($where)->find();
//            if (empty($userinfo)) {
//                return returnData(false, -1, 'error', '会员信息有误');
//            }
//        }
//        $wrapRealpayment = $total_money;
//        $setintegral = 0;
//        if ($wrapWechat > 0) {//微信支付
//            $result = $this->doWxPay($wrapAuthCode, $wrapWechat, $wrapOrderNO);
//        }
//        if ($wrapAlipay > 0) {//支付宝支付
//            $result = $this->doAliPay($shopInfo['userId'], $wrapAuthCode, $wrapAlipay, $wrapOrderNO);
//        }
//        if ($result['code'] == -1) {//支付失败
//            return $result;
//        }
//        $posOrdersTab->where(array('orderNO' => $wrapOrderNO))->save(array('outTradeNo' => $result['transaction_id']));
//        $cash_new = $wrapCash - $wrapChange;
//        //使用到了现金，要扣除商家预存款
//        if ($cash_new > 0) {
//            $shopsTab->where(array('shopId' => $shopId))->setDec('predeposit', $cash_new);
//            //写入流水
//            $log_sys_moneys_data = array(
//                'targetType' => 1,
//                'targetId' => $shopId,
//                'dataSrc' => 3,
//                'dataId' => $orderInfo['id'],
//                'moneyRemark' => '消费',
//                'moneyType' => 0,
//                'money' => $cash_new,
//                'createTime' => date('Y-m-d H:i:s'),
//                'dataFlag' => 1,
//                'state' => 1,
//                'payType' => 1
//            );
//            M('log_sys_moneys')->add($log_sys_moneys_data);
//        }
//        //判断是否使用余额 并用户余额是否够用 扣除余额 写入余额流水
//        if (!empty($wrapBalance) && !empty($userId) && !empty($userinfo)) {
//            if ((float)$wrapBalance > (float)$userinfo['balance']) {
//                return returnData(false, -1, 'error', '你怎么能大于用户现有余额？');
//            }
//            //更改用户余额
//            $usersTab->where('userId=' . $userinfo['userId'])->setDec('balance', (float)$wrapBalance);
//            //写入余额流水
//            $mod_user_balance = M('user_balance');
//            $add_user_balance['userId'] = $userinfo['userId'];
//            $add_user_balance['balance'] = $wrapBalance;
//            $add_user_balance['dataSrc'] = 2;
//            $add_user_balance['orderNo'] = $wrapOrderNO;
//            $add_user_balance['dataRemarks'] = '线下消费';
//            $add_user_balance['balanceType'] = 2;
//            $add_user_balance['createTime'] = date('Y-m-d H:i:s');
//            $add_user_balance['shopId'] = $shopId;
//            $mod_user_balance->add($add_user_balance);
//        }
//        //没有开启积分支付
//        if (empty($GLOBALS['CONFIG']['isOpenScorePay']) && $wrapSetintegral > 0) {
//            return returnData(false, -1, 'error', '没有开启积分支付功能，该笔订单不能使用积分抵扣');
//        }
//        //判断是否使用积分抵现且是否code 并依赖比例消费 且用户积分是否充足 写入积分流水
//        if (!empty($wrapSetintegral) and !empty($userId) && $GLOBALS['CONFIG']['isOpenScorePay'] == 1 && !empty($userinfo)) {
//            if ($wrapSetintegral > (int)$total_score) {
//                $msg = "当前订单最多可抵扣 " . (int)$total_score . " 积分";
//                return returnData(false, -1, 'error', $msg);
//            }
//            if ($wrapSetintegral > (int)$userinfo['userScore']) {
//                $msg = "你怎么能大于用户现有积分？";
//                return returnData(false, -1, 'error', $msg);
//            }
//            //要扣除的积分
//            $setintegral = ($wrapSetintegral > (int)$total_score) ? $total_score : $wrapSetintegral;
//            //更改用户积分
//            $usersTab->where('userId=' . $userinfo['userId'])->setDec('userScore', $setintegral);
//            //写入积分流水
//            $mod_user_score_data['userId'] = $userinfo['userId'];
//            $mod_user_score_data['score'] = $setintegral;
//            $mod_user_score_data['dataSrc'] = 12;
//            $mod_user_score_data['dataRemarks'] = "积分消费";
//            $mod_user_score_data['dataId'] = $orderInfo['id'];
//            $mod_user_score_data['scoreType'] = 2;
//            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
//            $userScoreTab->add($mod_user_score_data);
//        }
//        //更改订单数据
//        $data_where['orderNO'] = $wrapOrderNO;
//        $data_where['state'] = 1;//订单未结算
//        $data_where['shopId'] = $shopId;
//        $data_save['pay'] = $wrapPay;
//        $data_save['discount'] = $wrapDiscount;
//        $data_save['discountPrice'] = $wrapDiscountPrice;
//        $data_save['integral'] = 0;
//        $data_save['shopId'] = $shopId;
//        $data_save['state'] = 3;//更改订单 已结算
//        //如果开启获取积分 对用户增加积分
//        if ($GLOBALS['CONFIG']['isOrderScore'] == 1 && !empty($userinfo)) {
//            $reward_score = getOrderScoreByOrderScoreRate($wrapRealpayment);
//            $usersTab->where("userId = " . $userinfo['userId'])->setInc('userScore', $reward_score);
//            $mod_user_score_data = array();
//            $mod_user_score_data['userId'] = $userinfo['userId'];
//            $mod_user_score_data['score'] = $reward_score;
//            $mod_user_score_data['dataSrc'] = 12;
//            $mod_user_score_data['dataRemarks'] = "线下购物奖励积分";
//            $mod_user_score_data['dataId'] = $orderInfo['id'];
//            $mod_user_score_data['scoreType'] = 1;
//            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
//            $userScoreTab->add($mod_user_score_data);
//            $data_save['integral'] = $reward_score;
//        }
//        $data_save['cash'] = $wrapCash;
//        $data_save['balance'] = $wrapBalance;
//        $data_save['unionpay'] = $wrapUnionpay;
//        $data_save['wechat'] = $wrapWechat;
//        $data_save['alipay'] = $wrapAlipay;
//        $data_save['change'] = $wrapChange;
//        $data_save['realpayment'] = $wrapRealpayment;
//        $data_save['setintegral'] = $setintegral;
//        $data_save['isCombinePay'] = $wrapIsCombinePay;
//        $data_save['memberId'] = $userinfo['userId'];
//        //添加收银员ID
//        if (empty($shopInfo['id'])) { //总管理员
//            $data_save['userId'] = $shopId;
//        } else {//其他管理员
//            $data_save['userId'] = $shopInfo['id'];
//        }
//        if (!$posOrdersTab->where($data_where)->save($data_save)) {
//            return returnData(false, -1, 'error', '订单数据异常');
//        }
//        //1:按商品积分抵扣比例计算 2：平摊分配
//        $integral_flag = ((int)$wrapSetintegral < (int)$total_score) ? 2 : 1;
//        //计算总积分抵扣比例
//        $sum_integralRate = $goodsTab->where(array('goodsId' => array('in', $goodsId_arr)))->sum('integralRate');
//        $len = count($pack['goods']);
//        //写入商品 更改库存
//        for ($i = 0; $i < $len; $i++) {
//            //写入商品啊
//            $adddata = null;
//            $adddata['goodsId'] = $pack['goods'][$i]['goodsId'];
//            $adddata['skuId'] = $pack['goods'][$i]['skuId'];//后加skuId
//            $adddata['skuSpecAttr'] = '';
//            if ($pack['goods'][$i]['skuId'] > 0) {
//                //sku属性值
//                $systemSkuInfo = $systemSkuTab->where(['skuId' => $pack['goods'][$i]['skuId']])->find();
//                $systemSkuInfo['selfSpec'] = M("sku_goods_self se")
//                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
//                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
//                    ->where(['se.skuId' => $systemSkuInfo['skuId']])
//                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
//                    ->order('sp.sort asc')
//                    ->select();
//                if (!empty($systemSkuInfo['selfSpec'])) {
//                    foreach ($systemSkuInfo['selfSpec'] as $sv) {
//                        $systemSkuInfo['skuSpecStr'] .= $sv['attrName'] . "，";
//                    }
//                }
//                $adddata['skuSpecAttr'] = trim($systemSkuInfo['skuSpecStr'], '，');
//            }
//            $adddata['goodsName'] = $pack['goods'][$i]['goodsName'];
//            $adddata['goodsSn'] = $pack['goods'][$i]['goodsSn'];
//            $adddata['originalPrice'] = $pack['goods'][$i]['unitprice'];
//            $adddata['favorablePrice'] = $pack['goods'][$i]['favorablePrice'];
//            $adddata['presentPrice'] = $pack['goods'][$i]['shopPrice'];
//            $adddata['number'] = $pack['goods'][$i]['number'];
//            $adddata['subtotal'] = $pack['goods'][$i]['price'];
//            $adddata['discount'] = $pack['goods'][$i]['discount'];
//            $adddata['orderid'] = $orderInfo['id'];
//            $adddata['weight'] = $pack['goods'][$i]['weight'];
//            $adddata['state'] = 1;
//            $adddata['isRefund'] = 0;
//            $goods_info = $goodsTab->where(array('goodsId' => $pack['goods'][$i]['goodsId']))->find();
//            //1:按商品积分抵扣比例计算 2：平摊分配
//            if ($integral_flag == 1) {//按商品积分抵扣比例计算
//                $discount = empty($pack['goods'][$i]['discount']) ? 1 : $pack['goods'][$i]['discount'] * 10 / 100;
//                if ($pack['goods'][$i]['SuppPriceDiff'] < 0) {//标品
//                    $money_t = $pack['goods'][$i]['shopPrice'] * $pack['goods'][$i]['number'] * $discount;
//                } else if ($pack['goods'][$i]['SuppPriceDiff'] > 0) {//秤重商品
//                    $money_t = $pack['goods'][$i]['shopPrice'] * $pack['goods'][$i]['weight'] * $discount;
//                }
//                $integral = moneyToIntegral($money_t * $goods_info['integralRate']);
//            } else if ($integral_flag == 2) {//平摊分配
//                $integral = integralAssignment($wrapSetintegral, $goods_info['integralRate'], $sum_integralRate);
//            }
//            $adddata['integral'] = $integral;
//            $sdata[] = $adddata;
//            $num = ($adddata['weight'] > 0) ? $adddata['weight'] : $adddata['number'];
//            //改库存
//            $goodsTab->where('goodsId = ' . $pack['goods'][$i]['goodsId'])->setDec('goodsStock', $num);
//            if ($pack['goods'][$i]['skuId'] > 0) {
//                //更改sku库存
//                $systemSkuTab->where(['skuId' => $pack['goods'][$i]['skuId']])->setDec('skuGoodsStock', $num);
//            }
//        }
//        if ($orderGoodsTab->addAll($sdata)) {//全部添加
//            return returnData(true);
//        } else {
//            return returnData(false, -1, 'error', '提交失败');
//        }
//    }

    /**
     * POS提交订单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dzbn9r
     * */
    public function posSubmit(array $loginInfo, $pack)
    {
        $response = LogicResponse::getInstance();
        $promotion_module = new PromotionModule();
        if (empty($loginInfo)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('门店信息有误')->toArray();
        }
        $config = $GLOBALS['CONFIG'];
//        $goodsTab = M('goods');
        $goodsTab = new GoodsModel();
//        $posOrdersTab = M('pos_orders');
        $posOrdersTab = new PosOrdersModel();
//        $orderGoodsTab = M('pos_orders_goods');
        $orderGoodsTab = new PosOrdersGoodsModel();
        //$usersTab = M('users');
        $usersTab = new \App\Models\UsersModel();
//        $userScoreTab = M('user_score');
        $userScoreTab = new UserScoreModel();
//        $shopsTab = M('shops');
        $shopsTab = new ShopsModel();
        //$systemSkuTab = M('sku_goods_system');
        $systemSkuTab = new SkuGoodsSystemModel();
        $shopId = $loginInfo['shopId'];
        $userId = (int)$pack['userId'];//会员id
        if (!empty($userId) || !empty($pack['balance'])) {
            $user_service_module = new UsersServiceModule();
            $users_result = $user_service_module->getUsersDetailById($userId);
            if ($users_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return returnData(false, -1, 'error', '会员信息有误');
            }
            $userInfo = $users_result['data'];//兼容之前的变量
        }
        $wrapGoods = (array)$pack['goods'];//购买商品信息
        $wrapOrderNO = trim((string)$pack['orderNO']);//订单号
        $orderWhere = [];
        $orderWhere['orderNO'] = $wrapOrderNO;
        $orderInfo = $posOrdersTab->where($orderWhere)->find();
        if ($orderInfo['shopId'] != $shopId) {
            return returnData(false, -1, 'error', '订单非本门店订单或者订单不存在');
        }
        if ($orderInfo['state'] != 1) {
            return returnData(false, -1, 'error', '处理中，请稍后');//这个用于友好提示,主要是为了防止用户端多次提交订单
        }
        $wrapAlipay = (float)$pack['alipay'];//支付宝(元)
        $wrapWechat = (float)$pack['wechat'];//微信(元)
        $wrapAuthCode = (string)$pack['auth_code'];//支付授权码
        $wrapCash = (float)$pack['cash'];//现金(元)
        $wrapChange = (float)$pack['change'];//找零
        $wrapBalance = (float)$pack['balance'];//余额(元)
        $wrapSetintegral = (int)$pack['setintegral'];//可使用积分
        $wrapPay = (int)$pack['pay'];//支付方式
        $wrapDiscount = (string)$pack['discount'];//整单折扣【1-100】
        $wrapDiscountPrice = (float)$pack['discountPrice'];//整单折扣价格(元)
        $wrapUnionpay = (float)$pack['unionpay'];//银联(元)
        $wrapIsCombinePay = (int)$pack['isCombinePay'];//是否组合支付
        $wrapOrderAmount = (float)$pack['orderAmount'];//订单金额(应付金额)
        $wrapRealpayment = (float)$pack['realpayment'];//实付金额 PS:本单实际应收多少钱
        $wrapRealAcceptAmount = (float)$pack['real_accept_amount'];//实收金额 PS:收了用户多少钱
        $wrapAllowanceAmount = (float)$pack['allowanceAmount'];//折让金额
        if (empty($wrapOrderAmount) || empty($wrapRealpayment)) {
            return returnData(false, -1, 'error', '请输入正确的应付金额和实付金额');
        }
        if (!empty($wrapDiscount)) {
            if ($wrapDiscount < 1 || $wrapDiscount > 100) {
                return returnData(false, -1, 'error', '整单折扣值必须在1至100之间');
            }
        }
        $goodsCountNum = 0;//订单-商品数量
        $totalMoney = 0;//订单-商品金额
        $totalScore = 0;//订单-商品可用积分
        $goodsIdArr = [];//商品id
        $single_score_total = 0;//单商特价积分总计
        $single_goods_money = 0;//单商品特价金额
        foreach ($wrapGoods as &$data) {
            $single_goods_money += (float)$data['presentPrice'] * (int)$data['number'];
            $promotion_id = (int)$data['promotion_id'];
            $goods_id = (int)$data['goodsId'];
            $sku_id = (int)$data['skuId'];
            $promotion_info_result = $promotion_module->getPromotionInfoById($promotion_id);
            if ($promotion_info_result['code'] != ExceptionCodeEnum::FAIL) {
                $promotion_info = $promotion_info_result['data'];
                if ($promotion_info['data_type'] == 2) {
                    $single_where = array(
                        'promotion_id' => $promotion_id,
                        'goods_id' => $goods_id,
                        'sku_id' => $sku_id,
                    );
                    $single_result = $promotion_module->getSpecialSingleInfoByParams($single_where);
                    if ($single_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $single_info = $single_result['data'];
                        $single_score = (int)$single_info['score'];
                        if ($single_score > 0) {
                            $single_score_total += $single_score;
                        }
                        $single_goods_money += '';
                    }
                }
            }

            $data['goodsId'] = (int)$data['goodsId'];
            $data['skuId'] = (int)$data['skuId'];
            $data['goodsName'] = (string)$data['goodsName'];
            $data['number'] = (int)$data['number'];
            $data['weight'] = (float)$data['weight'];
            $data['originalPrice'] = (float)$data['originalPrice'];
            $data['favorablePrice'] = (float)$data['favorablePrice'];
            $data['presentPrice'] = (float)$data['presentPrice'];
            $data['discount'] = (float)$data['discount'];
            $data['subtotal'] = (float)$data['subtotal'];
            $data['integral'] = (int)$data['integral'];
            if ($data['presentPrice'] <= 0) {
                $msg = "商品【{$data['goodsName']}】现价校验不通过，现价必须大于0";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            if ($data['number'] <= 0) {
                $msg = "商品【{$data['goodsName']}】购买数量必须大于0";
//                return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            if (!empty($data['discount'])) {
                $data['discount'] = (float)$data['discount'];
                if ($data['discount'] < 1 || $data['discount'] > 99) {
                    $msg = "商品【{$data['goodsName']}】折扣值错误，取值范围必须控制在1之至99";
//                    return returnData(false, -1, 'error', $msg);
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
                }
            }
            $goodsId = $data['goodsId'];
            $skuId = $data['skuId'];
            $where = [];
            $where['goods.goodsId'] = $goodsId;
            $where['goods.shopId'] = $shopId;
            $where['goods.goodsFlag'] = 1;
            if ($skuId > 0) {
                $where['system.skuId'] = $skuId;
                $goodsCount = M('sku_goods_system system')
                    ->join("left join wst_goods goods on goods.goodsId=system.goodsId")
                    ->where($where)
                    ->count('goods.goodsId');
            } else {
                $goodsCount = M('goods goods')->where($where)->count('goods.goodsId');
            }
            if (empty($goodsCount)) {
                $goodsCount = 0;
            }
            $goodsCountNum += $goodsCount;
            array_push($goodsIdArr, $goodsId);
            $goodsId = $data['goodsId'];
            $presentPrice = $data['presentPrice'];//商品现价
            $number = $data['number'];//购买数量
            $weight = $data['weight'];//实际称重重量
            $goodsInfo = $this->getShopGoodsInfo($shopId, $goodsId)['data'];
            if ($goodsInfo['SuppPriceDiff'] < 0) {//标品
                $money_t = bc_math($presentPrice, $number, 'bcmul', 2);//商品小计
            } else {//称重商品
                $singleGoodsPirce = mathWeightPrice($goodsId, $weight);
                $money_t = bc_math($singleGoodsPirce, $number, 'bcmul', 2);
            }
            $totalMoney += $money_t;
            if ($config['isOpenScorePay'] == 1) { //计算商品可用积分抵扣
                $discountPrice = $money_t;
                $scoreScoreCashRatio = explode(':', $config['scoreCashRatio']);
                $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
                $canUseScore = $goodsScore * ((float)$goodsInfo['integralRate'] / 100);//可以抵扣的积分
                $totalScore += (int)$canUseScore;
                //$scoreAmount = (int)$integralRateScore / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
            }
            $goodsIdArr[] = $data['goodsId'];
        }
        unset($data);
        if ($goodsCountNum != count($wrapGoods)) {
            $msg = '提交的的商品信息有误';
//            return returnData(false, -1, 'error', '提交的的商品信息有误');
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
        }
        $promotion_id_arr = array_column($wrapGoods, 'promotion_id');
        $promotion_result = $promotion_module->getPromotionListById($promotion_id_arr);
        #促销-单商品特价送特价积分
        $exist_single_promotion = 0;//是否存在特价促销(0:不存在 1:存在),PS:存在则需要处理积分相关
        if ($promotion_result['code'] == ExceptionCodeEnum::SUCCESS) {
            $promotion_list = $promotion_result['data'];
            $data_type = array_column($promotion_list, 'data_type');
            if (in_array(2, $data_type)) {
                $exist_single_promotion = 1;
            }
        }
        //收银的订单金额相关这里就不计算了,没有意义,收银端是可以随便改金额的
        M()->startTrans();
        //判断是否使用余额 并用户余额是否够用 扣除余额 写入余额流水
        if (!empty($wrapBalance) && !empty($userId) && !empty($userInfo)) {
            if ((float)$wrapBalance > (float)$userInfo['balance']) {
                M()->rollback();
//                return returnData(false, -1, 'error', '用户余额不足');
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户余额不足')->toArray();
            }
            //更改用户余额
            $updateBalanceRes = $usersTab->where('userId=' . $userInfo['userId'])->setDec('balance', (float)$wrapBalance);
            if (!$updateBalanceRes) {
                M()->rollback();
//                return returnData(false, -1, 'error', '用户余额扣款失败');
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户余额扣款失败')->toArray();
            }
            //写入余额流水
            $mod_user_balance = M('user_balance');
            $add_user_balance['userId'] = $userInfo['userId'];
            $add_user_balance['balance'] = $wrapBalance;
            $add_user_balance['dataSrc'] = 2;
            $add_user_balance['orderNo'] = $wrapOrderNO;
            $add_user_balance['dataRemarks'] = '线下消费';
            $add_user_balance['balanceType'] = 2;
            $add_user_balance['createTime'] = date('Y-m-d H:i:s');
            $add_user_balance['shopId'] = $shopId;
            $balanceLogRes = $mod_user_balance->add($add_user_balance);
            if (!$balanceLogRes) {
                M()->rollback();
//                return returnData(false, -1, 'error', '用户余额流水记录失败');
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('用户余额流水记录失败')->toArray();
            }
        }
        //没有开启积分支付
        if (empty($config['isOpenScorePay']) && $wrapSetintegral > 0) {
            M()->rollback();
//            return returnData(false, -1, 'error', '没有开启积分支付功能，该笔订单不能使用积分抵扣');
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('没有开启积分支付功能，该笔订单不能使用积分抵扣')->toArray();
        }
        $setintegral = 0;//要扣除的积分
        //判断是否使用积分抵现且是否code 并依赖比例消费 且用户积分是否充足 写入积分流水
        $users_id = $userInfo['userId'];
        $users_service_module = new UsersServiceModule();
        if (!empty($wrapSetintegral) and !empty($userId) && $config['isOpenScorePay'] == 1 && !empty($userInfo)) {
            if ($wrapSetintegral != $totalScore) {//统一前后端计算可使用积分的结果
                M()->rollback();
                $msg = "积分校验不通过，当前订单可以使用{$totalScore}积分";
//                return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            if ($wrapSetintegral > (int)$userInfo['userScore']) {
                M()->rollback();
                $msg = "用户积分不足，本单最多可以使用{$userInfo['userScore']}积分";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            //要扣除的积分
            $setintegral = $wrapSetintegral;
            //更改用户积分
            //$usersTab->where('userId=' . $userInfo['userId'])->setDec('userScore', $setintegral);
            //积分处理-start
            $score = (int)$setintegral;
            $result = $users_service_module->deduction_users_score($users_id, $score, M());
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(null, -1, 'error', $result['msg']);
            }
//积分处理-end
            //写入积分流水
            $mod_user_score_data['userId'] = $userInfo['userId'];
            $mod_user_score_data['score'] = $setintegral;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "积分消费";
            $mod_user_score_data['dataId'] = $orderInfo['id'];
            $mod_user_score_data['scoreType'] = 2;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $scoreLogRes = $userScoreTab->add($mod_user_score_data);
            if (!$scoreLogRes) {
                M()->rollback();
                $msg = "积分流水记录失败";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        $cash_new = $wrapCash - $wrapChange;
        //使用到了现金，要扣除商家预存款
        if ($cash_new > 0 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {
            $where = [];
            $where['shopId'] = $shopId;
            $shopInfo = M('shops')->lock(true)->where($where)->find();
            if ($cash_new > $shopInfo['predeposit']) {
                M()->rollback();
                $msg = "商家预存款不足";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            $shopsTab->where(array('shopId' => $shopId))->setDec('predeposit', $cash_new);
            //写入流水
            $log_sys_moneys_data = array(
                'targetType' => 1,
                'targetId' => $shopId,
                'dataSrc' => 3,
                'dataId' => $orderInfo['id'],
                'moneyRemark' => '消费',
                'moneyType' => 0,
                'money' => $cash_new,
                'createTime' => date('Y-m-d H:i:s'),
                'dataFlag' => 1,
                'state' => 1,
                'payType' => 1
            );
            $logRes = M('log_sys_moneys')->add($log_sys_moneys_data);
            if (!$logRes) {
                M()->rollback();
                $msg = "消费流水记录失败";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        //更改订单数据
        $data_where = [];
        $data_where['orderNO'] = $wrapOrderNO;
        $data_where['state'] = 1;//订单未结算
        $data_where['shopId'] = $shopId;
        $data_save = [];
        $data_save['pay'] = $wrapPay;
        $data_save['discount'] = $wrapDiscount;
        $data_save['discountPrice'] = $wrapDiscountPrice;
        $data_save['integral'] = 0;
        $data_save['shopId'] = $shopId;
        $data_save['state'] = 3;//更改订单 已结算
        if ($data_save['state'] == 3) {
            $data_save['pay_time'] = date('Y-m-d H:i:s');
        }
        //订单日志-start
        $logParams = [
            'tableName' => 'wst_pos_orders',
            'dataId' => $orderInfo['id'],
            'actionUserId' => (int)$data_save['memberId'],
            'actionUserName' => '用户',
            'fieldName' => 'state',
            'fieldValue' => 3,
            'remark' => '用户成功下单',
            'createTime' => date('Y-m-d H:i:s'),
        ];
        M('table_action_log')->add($logParams);
        //订单日志-end
        //如果开启获取积分 对用户增加积分
        if ($config['isOrderScore'] == 1 && !empty($userInfo)) {
            if ($exist_single_promotion != 1) {
                $reward_score = getOrderScoreByOrderScoreRate($wrapRealpayment);
            } else {
                $reward_score = getOrderScoreByOrderScoreRate($wrapRealpayment);
                $single_goods_score = getOrderScoreByOrderScoreRate($single_goods_money);
                $surplus_score = (int)bc_math($reward_score, $single_goods_score, 'bcsub', 0);
                if ($surplus_score < 0) {
                    $surplus_score = 0;
                }
                $reward_score = (int)bc_math($surplus_score, $single_goods_score, 'bcadd', 0);
                //$reward_score = $single_score_total;
            }
            //$usersTab->where("userId = " . $userInfo['userId'])->setInc('userScore', $reward_score);
            //积分处理-start
            $score = (int)$reward_score;
            $users_id = $userInfo['userId'];
            $result = $users_service_module->return_users_score($users_id, $score, M());
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(null, -1, 'error', $result['msg']);
            }
//积分处理-end
            $mod_user_score_data = array();
            $mod_user_score_data['userId'] = $userInfo['userId'];
            $mod_user_score_data['score'] = $reward_score;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "线下购物奖励积分";
            $mod_user_score_data['dataId'] = $orderInfo['id'];
            $mod_user_score_data['scoreType'] = 1;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $logRes = $userScoreTab->add($mod_user_score_data);
            if (!$logRes) {
                M()->rollback();
                $msg = "积分记录失败";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            $data_save['integral'] = $reward_score;
        }
        $data_save['cash'] = $wrapCash;
        $data_save['balance'] = $wrapBalance;
        $data_save['unionpay'] = $wrapUnionpay;
        $data_save['wechat'] = $wrapWechat;
        $data_save['alipay'] = $wrapAlipay;
        $data_save['change'] = $wrapChange;
        $data_save['allowanceAmount'] = $wrapAllowanceAmount;
        $data_save['orderAmount'] = $wrapOrderAmount;
        $data_save['realpayment'] = $wrapRealpayment;
        $data_save['real_accept_amount'] = $wrapRealAcceptAmount;
        $data_save['setintegral'] = $setintegral;
        $data_save['isCombinePay'] = $wrapIsCombinePay;
        $data_save['memberId'] = (int)$userInfo['userId'];
        if (!empty($data_save['setintegral'])) {
            //积分抵扣金额
            $data_save['setintegral_amount'] = (float)((int)$setintegral / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1]);
        }
        //添加收银员ID
        if (empty($loginInfo['id'])) { //总管理员
            $data_save['userId'] = $loginInfo['userId'];
        } else {//其他管理员
            $data_save['userId'] = $loginInfo['id'];
        }
        if (!$posOrdersTab->where($data_where)->save($data_save)) {
            M()->rollback();
            //return returnData(false, -1, 'error', '订单数据异常');
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('订单数据异常')->toArray();
        }
        $len = count($pack['goods']);
        $goods_module = new GoodsModule();
        $warehouseGoodsData = array();
        //写入商品 更改库存
        for ($i = 0; $i < $len; $i++) {
            $goodsValue = $wrapGoods[$i];
            $warehouseGoodsData[] = array(
                'goods_id' => $goodsValue['goodsId'],
                'sku_id' => $goodsValue['skuId'],
                'sku_id' => $goodsValue['skuId'],
                'nums' => $goodsValue['number'],
                'actual_delivery_quantity' => $goodsValue['number'],
            );
            $goodsSn = (string)$goodsValue['goodsSn'];
            //写入商品啊
            $adddata = [];
            $adddata['goodsId'] = $goodsValue['goodsId'];
            $adddata['skuId'] = $goodsValue['skuId'];//后加skuId
            $adddata['skuSpecAttr'] = '';
            $goodsInfo = $goodsTab->where(array('goodsId' => $goodsValue['goodsId']))->find();
            $adddata['goodsSn'] = $goodsInfo['goodsSn'];
            if ($goodsValue['skuId'] > 0) {
                //sku属性值
                $systemSkuInfo = $systemSkuTab->where(['skuId' => $goodsValue['skuId']])->find();
                $systemSkuInfo['selfSpec'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $systemSkuInfo['skuId']])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->order('sp.sort asc')
                    ->select();
                if (!empty($systemSkuInfo['selfSpec'])) {
                    foreach ($systemSkuInfo['selfSpec'] as $sv) {
                        $systemSkuInfo['skuSpecStr'] .= $sv['attrName'] . ",";
                    }
                }
                $adddata['skuSpecAttr'] = trim($systemSkuInfo['skuSpecStr'], ',');
                $adddata['goodsSn'] = $systemSkuInfo['skuBarcode'];
            }
            $adddata['promotion_id'] = (int)$goodsValue['promotion_id'];
            $adddata['promotion_price'] = (float)$goodsValue['promotion_price'];
            $adddata['goodsName'] = $goodsValue['goodsName'];
            //$adddata['goodsSn'] = $goodsValue['goodsSn'];
            $adddata['originalPrice'] = $goodsValue['originalPrice'];
            $adddata['favorablePrice'] = $goodsValue['favorablePrice'];
            $adddata['presentPrice'] = $goodsValue['presentPrice'];
            $adddata['number'] = $goodsValue['number'];
            $adddata['subtotal'] = $goodsValue['subtotal'];
            $adddata['discount'] = empty($goodsValue['discount']) ? 1 : $goodsValue['discount'] / 100;
            $adddata['orderid'] = $orderInfo['id'];
            $adddata['weight'] = $goodsValue['weight'];
            $adddata['state'] = 1;
            $adddata['isRefund'] = 0;
            $integral = $goodsValue['integral'];
            $adddata['integral'] = 0;
            $adddata['SuppPriceDiff'] = (int)$goodsInfo['SuppPriceDiff'];//是否称重商品
            $adddata['weight'] = 0;//实际称重,暂时没有
            $adddata['packing_factor'] = 0;//包装系数
            $plu_code = (int)$goodsInfo['plu_code'];
            if ($adddata['SuppPriceDiff'] == 1) {
                //称重商品
                if (strlen($goodsSn) == 18 && $plu_code > 0) {//计重商品
                    //$codeE = (int)substr($goodsSn, 7, 5) / 100;//金额单位/元
                    $codeN = (int)substr($goodsSn, 12, 5);//解析出来的重量,单位/g
                    $adddata['packing_factor'] = $codeN;
                    $adddata['weight'] = $adddata['packing_factor'] * $adddata['number'];
                } else {//非标品
                    $adddata['packing_factor'] = $goodsInfo['weightG'];
                    $adddata['weight'] = $goodsInfo['weightG'] * $adddata['number'];
                }
            }
            if (!empty($integral) && !empty($wrapSetintegral)) {
                if ($goodsInfo['SuppPriceDiff'] < 0) {//标品
                    $money_t = bc_math($adddata['presentPrice'], $adddata['number'], 'bcmul', 2);//商品小计
                } else {//称重商品
                    if (strlen($goodsSn) == 18 && $plu_code > 0) {//计重商品
                        $codeE = (int)substr($goodsSn, 7, 5) / 100;//金额单位/元
                        $codeN = (int)substr($goodsSn, 12, 5);//解析出来的重量,单位/g
                        $unitPrice = bc_math($codeE, $codeN, 'bcdiv', 4);
                        $money_t = bc_math($unitPrice, $adddata['weight'], 'bcmul', 2);
                    } else {//非标品
                        $unitPrice = bc_math($adddata['presentPrice'], $goodsInfo['weightG'], 'bcdiv', 4);
                        $money_t = bc_math($unitPrice, $adddata['weight'], 'bcmul', 2);
                    }
                }
                //获取比例
                $discountPrice = $money_t;
                $scoreScoreCashRatio = explode(':', $config['scoreCashRatio']);
                $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
                $canUseScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
                if ($canUseScore != $integral) {
                    M()->rollback();
                    //return returnData(false, -1, 'error', "商品【{$adddata['goodsName']}】使用积分校验不通过，可以使用{$canUseScore}积分");
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$adddata['goodsName']}】使用积分校验不通过，可以使用{$canUseScore}积分")->toArray();
                }
                $adddata['integral'] = $canUseScore;
            }
            //促销-更改全场限购数量-start
            $promotion_id = (int)$goodsValue['promotion_id'];
            $goods_id = (int)$goodsValue['goodsId'];
            $sku_id = (int)$goodsValue['skuId'];
            $promotion_result = $promotion_module->getPromotionInfoById($promotion_id);
            if ($promotion_result['code'] == ExceptionCodeEnum::SUCCESS) {
                $promotion_info = $promotion_result['data'];
                $data_type = $promotion_info['data_type'];
                $adddata['promotion_type'] = $data_type;
                if ($data_type == 1) {
                    //DM商品特价
                    $dm_where = array(
                        'promotion_id' => $promotion_id,
                        'goods_id' => $goods_id,
                        'sku_id' => $sku_id,
                    );
                    $dm_result = $promotion_module->getSpecialDMInfoByParams($dm_where);
                    if ($dm_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $dm_info = $dm_result['data'];
                        $limited_purchase_used = bc_math($dm_info['limited_purchase_used'], $goodsValue['number'], 'bcadd', 0);
                        $dm_save = array(
                            'dm_id' => $dm_info['dm_id'],
                            'limited_purchase_used' => $limited_purchase_used
                        );
                        $update_res = $promotion_module->updateDMGoods($dm_save, M());
                        if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                            M()->rollback();
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('DM促销信息更新失败')->toArray();
                        }
                        $adddata['promotion_price'] = $dm_info['sale_price'];
                    }
                } elseif ($data_type == 2) {
                    //单商品特价
                    $single_where = array(
                        'promotion_id' => $promotion_id,
                        'goods_id' => $goods_id,
                        'sku_id' => $sku_id,
                    );
                    $single_result = $promotion_module->getSpecialSingleInfoByParams($single_where);
                    if ($single_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $single_info = $single_result['data'];
                        $single_save = array(
                            'single_id' => $single_info['single_id'],
                            'limited_purchase_used' => bc_math($single_info['limited_purchase_used'], $goodsValue['number'], 'bcadd', 0),
                        );
                        $update_res = $promotion_module->updateSingleGoods($single_save, M());
                        if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
                            M()->rollback();
                            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('DM促销信息更新失败')->toArray();
                        }
                        $adddata['promotion_price'] = $single_info['special_price'];
                    }
                } elseif ($data_type == 3) {
                    //买满数量后特价
                    $full_where = array(
                        'promotion_id' => $promotion_id,
                        'goods_id' => $goods_id,
                        'sku_id' => $sku_id,
                    );
                    $full_result = $promotion_module->getSpecialFullInfoByParams($full_where);
                    if ($full_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $full_info = $full_result['data'];
                        $adddata['promotion_price'] = $full_info['special_price'];
                    }
                } elseif ($data_type == 4) {
                    //按分类折扣特价
                    $discount_where = array(
                        'promotion_id' => $promotion_id,
                        'shop_class_id2' => $goodsInfo['shopCatId2'],
                    );
                    $discount_result = $promotion_module->getSpecialDiscountInfoByParams($discount_where);
                    if ($discount_result['code'] == ExceptionCodeEnum::SUCCESS) {
                        $discount_info = $discount_result['data'];
                        $adddata['promotion_price'] = bc_math($discount_info['discount'], $goodsInfo['shopPrice'], 'bcmul', 2);
                    }
                }
            }
            //促销-更改全场限购数量-end
            $sdata[] = $adddata;
            if ($adddata['SuppPriceDiff'] == 1) {
                //非标品
//                $num = $adddata['weight'] / 1000;
                $num = (float)$adddata['weight'];
            } else {
                //标品
                $num = $adddata['number'];
            }

            //改库存
            if ($goodsValue['skuId'] > 0) {
                if ($num > $systemSkuInfo['selling_stock']) {
                    M()->rollback();
//                    return returnData(false, -1, 'error', "商品【{$adddata['goodsName']}】库存不足");
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$adddata['goodsName']}】库存不足")->toArray();
                }
                //更改sku库存
                $editStockRes = $systemSkuTab->where(['skuId' => $goodsValue['skuId']])->setDec('selling_stock', $num);
            } else {
                if ($num > $goodsInfo['selling_stock']) {
                    M()->rollback();
                    //return returnData(false, -1, 'error', "商品【{$adddata['goodsName']}】库存不足");
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$adddata['goodsName']}】库存不足")->toArray();
                }
                $editStockRes = $goodsTab->where('goodsId = ' . $goodsValue['goodsId'])->setDec('selling_stock', $num);
            }
            $sale_res = $goods_module->IncGoodsSale($goodsValue['goodsId'], 0, $goodsValue['number'], 1, M());
            if (!$sale_res) {
                M()->rollback();
                $msg = "商品【{$adddata['goodsName']}】销量增加失败";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
            if (!$editStockRes) {
                M()->rollback();
                $msg = "商品【{$adddata['goodsName']}】库存修改失败";
                //return returnData(false, -1, 'error', $msg);
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($msg)->toArray();
            }
        }
        $billData = array(
            'pagetype' => 1,
            'shopId' => $shopId,
            'user_id' => $loginInfo['user_id'],
            'user_name' => $loginInfo['user_username'],
            'remark' => '',
            'relation_order_number' => $wrapOrderNO,
            'relation_order_id' => $orderInfo['id'],
            'goods_data' => $warehouseGoodsData,
        );
        $addBillRes = (new ExWarehouseOrderModule())->addExWarehouseOrder($billData, M());
        if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
            M()->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("支付失败，出库单创建失败")->toArray();
        }
        if ($orderGoodsTab->addAll($sdata)) {//全部添加
            if ($wrapWechat > 0) {//微信支付
                if (empty($wrapAuthCode)) {
                    M()->rollback();
                    //return returnData(false, -1, 'error', '使用微信支付必须传微信付款码');
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("使用微信支付必须传微信付款码")->toArray();
                }
                $result = $this->doWxPay($wrapAuthCode, $wrapWechat, $wrapOrderNO);
            }
            if ($wrapAlipay > 0) {//支付宝支付
                if (empty($wrapAuthCode)) {
                    M()->rollback();
//                    return returnData(false, -1, 'error', '使用支付宝支付必须传支付宝付款码');
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("使用支付宝支付必须传支付宝付款码")->toArray();
                }
                $result = $this->doAliPay($shopInfo['userId'], $wrapAuthCode, $wrapAlipay, $wrapOrderNO);
            }
            if ($result['code'] == -1) {//支付失败
                M()->rollback();
                return $result;
            }
            if (!empty($result['transaction_id'])) {
                $posOrdersTab->where(array('orderNO' => $wrapOrderNO))->save(array('outTradeNo' => $result['transaction_id']));
            }
            //上报订单报表数据-start
            $pos_report_module = new PosReportModule();
            $report_res = $pos_report_module->reportPosReport($orderInfo['id'], $pack, 1, M());//上报订单报表
            if (!$report_res) {
                M()->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("上报订单报表失败")->toArray();
            }
            //上报订单报表数据-end
            M()->commit();
            //return returnData(true);
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->setMsg('成功')->toArray();
        } else {
            M()->rollback();
            //return returnData(false, -1, 'error', '提交失败');
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('提交失败')->toArray();
        }
    }

    /**
     * 搜索商品(商品名/商品编码&条码)
     * @param array $shopInfo
     * @param string $goodsName 商品名称
     * @param string $barcode 商品编码/条码
     */
    public function search($shopInfo, $goodsName, $barcode)
    {
        $shopId = $shopInfo['shopId'];
        $goodsTab = M('goods');
        if (!empty($barcode)) {
            $barcodeArr = explode('-', $barcode);
            if ($barcodeArr[0] == 'CZ') {
                //条码查找
                $barcodeWhere = [];
                $barcodeWhere['barcode'] = $barcode;
                $barcodeWhere['shopId'] = $shopId;
                $barcodeWhere['bFlag'] = 1;
                $barcodeInfo = M('barcode')->where($barcodeWhere)->find();
                if (empty($barcodeInfo)) {
                    $barCodeGoodsInfo = [];
                } else {
                    if ($barcodeInfo['skuId'] > 0) {
                        //编码查找
                        $skuWhere = [];
                        $skuWhere['system.skuId'] = $barcodeInfo['skuId'];
                        $skuWhere['system.dataFlag'] = 1;
                        $skuWhere['goods.goodsFlag'] = 1;
                        $skuWhere['goods.goodsId'] = ['GT', 0];
                        $skuWhere['goods.shopId'] = $shopId;
                        $skuWhere['goods.isBecyclebin'] = 0;//不在商品回收站

                        $barCodeGoodsInfo = M('sku_goods_system system')
                            ->join('left join wst_goods goods on goods.goodsId = system.goodsId')
                            ->where($skuWhere)
                            ->field('goods.*,system.skuBarcode,system.skuId')
                            ->limit(1)
                            ->select();
                        $barcode = $barCodeGoodsInfo[0]['skuBarcode'];
                    } else {
                        $where = [];
                        $where['goodsId'] = $barcodeInfo['goodsId'];
                        $where['goodsFlag'] = 1;
                        $where['isBecyclebin'] = 0;//不在商品回收站
                        $barCodeGoodsInfo = $goodsTab->where($where)->select();
                    }

                    if (!empty($barCodeGoodsInfo)) {
                        $barCodeGoodsInfo[0]['barcodeInfo'] = [];
                        $barCodeGoodsInfo[0]['barcodeInfo']['barcode'] = $barcodeInfo['barcode'];
                        $barCodeGoodsInfo[0]['barcodeInfo']['weight'] = $barcodeInfo['weight'];
                        $barCodeGoodsInfo[0]['barcodeInfo']['price'] = $barcodeInfo['price'];

                        //替换店铺价格
                        setObj($barCodeGoodsInfo, "barcodeInfo", null, function ($k, $v) use (&$barCodeGoodsInfo) {
                            setObj($barCodeGoodsInfo, "shopPrice", (float)$v['price'], null);
                            return $v;
                        });
                        //替换条码
                        setObj($barCodeGoodsInfo, "barcodeInfo", null, function ($k, $v) use (&$barCodeGoodsInfo) {
                            setObj($barCodeGoodsInfo, "goodsSn", $v['barcode'], null);
                            setObj($barCodeGoodsInfo, "barcode", $v['barcode'], null);//新增字段
                            return $v;
                        });


                    }
                }


            } else {

                //编码查找
                $skuWhere = [];
                $skuWhere['system.skuBarcode'] = $barcode;
                $skuWhere['system.dataFlag'] = 1;
                $skuWhere['goods.goodsFlag'] = 1;
                $skuWhere['goods.goodsId'] = ['GT', 0];
                $skuWhere['goods.shopId'] = $shopId;
                $skuWhere['goods.isBecyclebin'] = 0;//不在商品回收站
                $barCodeGoodsInfo = M('sku_goods_system system')
                    ->join('left join wst_goods goods on goods.goodsId = system.goodsId')
                    ->where($skuWhere)
                    //辉辉 修复sku商品价格错误 2021511
                    ->field('goods.*,system.skuId,system.skuShopPrice as shopPrice,system.skuMemberPrice as memberPrice,system.skuGoodsStock as goodsStock,system.skuMemberPrice as memberPrice,system.skuGoodsImg as goodsImg,system.skuBarcode as goodsSn,system.skuMarketPrice as marketPrice,system.skuGoodsImg as goodsThums,system.skuGoodsImg as goodsThums,system.minBuyNum as minBuyNum,system.weigetG as weigetG,system.UnitPrice as UnitPrice,system.unit as unit')
                    ->limit(1)
                    ->select();

                if (empty($barCodeGoodsInfo)) {
                    //标品编码
                    $where = [];
                    $where['goodsFlag'] = 1;
                    $where['shopId'] = $shopId;
                    //$where['goodsSn'] = array('like', "%{$barcode}%");
                    $where['goodsSn'] = $barcode;
                    $where['isBecyclebin'] = 0;//不在商品回收站
                    $barCodeGoodsInfo = $goodsTab->where($where)->limit(1)->select();//不分页 最多一百条 商品名称搜索
                }

                if (!empty($barCodeGoodsInfo)) {
                    foreach ($barCodeGoodsInfo as &$item) {
                        $item['barcodeInfo'] = [];
                    }
                    unset($item);
                }

                if (!empty($barCodeGoodsInfo)) {//如果没查到数据不返回 20200902-huihui
                    $data = getGoodsSku((array)$barCodeGoodsInfo, $barcode);
                    foreach ($data as &$item) {

                        if (!empty($barcode) && $item['hasGoodsSku'] == 1) {
                            $item['barcodeInfo']['barcode'] = $item['goodsSku']['skuList'][0]['systemSpec']['skuBarcode'];
                            $item['barcodeInfo']['weight'] = 0;
                            $item['barcodeInfo']['price'] = $item['goodsSku']['skuList'][0]['systemSpec']['skuShopPrice'];
                        }
                    }
                    unset($item);
                    return returnData($data);
                }

            }
        }


        //不知道具体作用 if (!empty($barcode))改为了if (!empty($barCodeGoodsInfo)) 判断条码会导致返回为空数组
        if (!empty($barCodeGoodsInfo)) {
            return returnData(getGoodsSku((array)$barCodeGoodsInfo, $barcode));
        }


        //判断是否是本地预打包商品 该种商品条码并不会存储在系统上面 存粹靠提取条码信息
        //注意该逻辑请位于最下方 在所有条件都找不到商品的时候 在通过该方式判断是否是本地预打包商品 与系统预打包逻辑一样 都必须放在所有条件最后
        /**
         * FFWWWWWEEEEENNNNNC
         *  F店名 2位
         *  W编码 5位
         * E金额 5位
         * N重量 5位
         * C校验 1位
         * 一共18位
         * 例如
         */
        //为了防止自身不是最后的逻辑 将走到该逻辑大部分行为都会被返回空数组 尽可能避免该逻辑位置放置不正确
        $GetCZBarcodeFunc = function ($str, $shopId) {
            $codeF = (int)substr($str, 2);
            $codeW = (int)substr($str, 2, 5);//商品库编码
            $codeE = (int)substr($str, 7, 5);//金额单位 为 分
            $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
            $codeC = (int)substr($str, 17);


            if (!empty($codeW)) {
                $where = [];
                $where['goodsFlag'] = 1;
                $where['goodsSn'] = $codeW;
                $where['shopId'] = $shopId;
                $where['isBecyclebin'] = 0;//不在商品回收站
                $retdata = M('goods')->where($where)->find();


                if (!empty($retdata)) {
                    return getGoodsSku([$retdata]);
                } else {
                    return [];
                }

            }
        };
        //huihui20200902 处理本地预打包条码
        if (strlen($barcode) == 18) {//是否为18位条码
            $codeF = (int)substr($barcode, 2);
            $codeW = (int)substr($barcode, 2, 5);//商品库编码
            $codeE = (int)substr($barcode, 7, 5);//金额单位 为 分
            $codeN = (int)substr($barcode, 12, 5);//重量为G 应该 待测试------
            $codeC = (int)substr($barcode, 17);


            $codeData = $GetCZBarcodeFunc($barcode, $shopId);
            setObj($codeData, "shopPrice", $codeE / 100, null);//替换店铺价格

            //替换条码
            setObj($codeData, "goodsSn", $barcode, null);
            setObj($codeData, "barcode", $barcode, null);//新增字段


            //goodsSn 标品时就是标品条码 称重商品就替换成称重条码  为了兼容之前的错误结构现在还要再次新增结构体
            //新增二维结构 前端暂时未用 固定个字段即可
            setObj($codeData, "barcodeInfo", [], null);


            return returnData($codeData);
        }


        //TODO:系统预打包和本地预打包的价格需要直接替换 避免前端更换字段计算处理 过于麻烦 ok
        //增加一个商品类型标识 系统预打包和本地预打包后期可能会有特殊作用 SuppPriceDiff  如果为预打包商品改为 -1
        //TODO:如果收银提交结算商品记录 那么需要考虑同一个商品id但是存在多个的情况 因为一个产品如果称重会有多个条码 而且条码与商品id都可能重复的情况 需要注意结算接口
        //TODO:前端根据goodsSn做唯一性所以预打包的条码也要替换掉才行 ok


        //如果按商品名搜索

        if (!empty($goodsName)) {
            $where = [];
            $where['goodsFlag'] = 1;
            $where['shopId'] = $shopId;
            $where['isBecyclebin'] = 0;//不在商品回收站
            if (!empty($goodsName)) {//商品名称查找
                $where['goodsName'] = array('like', "%{$goodsName}%");
            }
            $data = $goodsTab->where($where)->limit(100)->select();//不分页 最多一百条 商品名称搜索

            if (!empty($data)) {
                foreach ($data as &$item) {
                    $item['barcodeInfo'] = [];
                }
                unset($item);
            }
            return returnData(getGoodsSku((array)$data));
        }
        return returnData([]);
    }

    /**
     * POS开单(其实就是获取一个订单号)
     */
    public function orderCreate($shopInfo)
    {
        //年月日时分秒+收银员id+门店id+增量订单id
        $mod_pos_orders = M('pos_orders');
        $mod_pos_order_id = M('pos_order_id')->add(array('id' => ''));//获取增量id
        $date = date('Ymd');
        // 拼接订单号
        $orderNO = $date . str_pad($mod_pos_order_id, 10, "0", STR_PAD_LEFT);
        // 创建订单
        $addata['orderNO'] = $orderNO;
        $addata['addtime'] = date('Y-m-d H:i:s');
        $addata['createTime'] = date('Y-m-d H:i:s');
        $addata['shopId'] = $shopInfo['shopId'];
        if ($mod_pos_orders->add($addata)) {
            $data = [];
            $data['orderNO'] = $orderNO;
            return returnData($data);
        } else {
            return returnData(false, -1, 'error', '提交失败');
        }
    }

    /**
     * 获取订单号
     * */
    public function createOrderNo()
    {
        $posOrderId = M('pos_order_id')->add(array('id' => ''));
        $date = date('Ymd');
        //年月日+自增id
        $orderNO = $date . $posOrderId;
        return $orderNO;
    }

    /**
     * 获取商品信息
     * @param int $shopId
     * @param int $goodsId
     * @param string $barcode 编码|条码
     */
    public function getShopGoodsInfo(int $shopId, int $goodsId, string $barcode)
    {
        $goodsTab = M('goods');
        if (!empty($barcode)) {
            $barcodeArr = explode('-', $barcode);
            if ($barcodeArr[0] == 'CZ') {
                //条码查找
                $barcodeWhere = [];
                $barcodeWhere['barcode'] = $barcode;
                $barcodeWhere['shopId'] = $shopId;
                $barcodeWhere['bFlag'] = 1;
                $barcodeInfo = M('barcode')->where($barcodeWhere)->find();
                if (empty($barcodeInfo)) {
                    $barCodeGoodsInfo = [];
                } else {
                    if ($barcodeInfo['skuId'] > 0) {
                        //编码查找
                        $skuWhere = [];
                        $skuWhere['system.skuId'] = $barcodeInfo['skuId'];
                        $skuWhere['system.dataFlag'] = 1;
                        $skuWhere['goods.goodsId'] = ['GT', 0];
                        $barCodeGoodsInfo = M('sku_goods_system system')
                            ->join('left join wst_goods goods on goods.goodsId = system.goodsId')
                            ->where($skuWhere)
                            ->field('goods.*,system.skuBarcode')
                            ->find();
                        $barcode = $barCodeGoodsInfo['skuBarcode'];
                    } else {
                        $where = [];
                        $where['goodsId'] = $barcodeInfo['goodsId'];
                        $where['goodsFlag'] = 1;
                        $barCodeGoodsInfo = $goodsTab->where($where)->find();
                    }
                    if (!empty($barCodeGoodsInfo)) {
                        $barCodeGoodsInfo['barcodeInfo'] = [];
                        $barCodeGoodsInfo['barcodeInfo']['barcode'] = $barcodeInfo['barcode'];
                        $barCodeGoodsInfo['barcodeInfo']['weight'] = $barcodeInfo['weight'];
                        $barCodeGoodsInfo['barcodeInfo']['price'] = $barcodeInfo['price'];
                        $barCodeGoodsInfoBat = $barCodeGoodsInfo;
                        unset($barCodeGoodsInfo);
                        $barCodeGoodsInfo[] = $barCodeGoodsInfoBat;//PS:因为setObj中的一维或多维数组判断不适用该地方,所以把这里改为多维数组
                        //替换店铺价格
                        setObj($barCodeGoodsInfo, "barcodeInfo", null, function ($k, $v) use (&$barCodeGoodsInfo) {
                            setObj($barCodeGoodsInfo, "shopPrice", (float)$v['price'], null);
                            return $v;
                        });
                        //替换条码
                        setObj($barCodeGoodsInfo, "barcodeInfo", null, function ($k, $v) use (&$barCodeGoodsInfo) {
                            setObj($barCodeGoodsInfo, "goodsSn", $v['barcode'], null);
                            return $v;
                        });
                    }
                }
            } else {
                //编码查找
                $skuWhere = [];
                $skuWhere['system.skuBarcode'] = $barcode;
                $skuWhere['system.dataFlag'] = 1;
                $skuWhere['goods.goodsId'] = ['GT', 0];
                $barCodeGoodsInfo = M('sku_goods_system system')
                    ->join('left join wst_goods goods on goods.goodsId = system.goodsId')
                    ->where($skuWhere)
                    ->field('goods.*')
                    ->find();
                if (empty($barCodeGoodsInfo)) {
                    //标品编码
                    $where = [];
                    $where['goodsFlag'] = 1;
                    $where['shopId'] = $shopId;
                    $where['goodsSn'] = $barcode;
                    $barCodeGoodsInfo = $goodsTab->where($where)->find();
                    return returnData(getGoodsSku((array)$barCodeGoodsInfo));
                }
            }
        }
        if (!empty($barCodeGoodsInfo)) {
            $data = getGoodsSku((array)$barCodeGoodsInfo, $barcode);
            return returnData($data[0]);
        }

        //判断是否是本地预打包商品 该种商品条码并不会存储在系统上面 存粹靠提取条码信息
        //注意该逻辑请位于最下方 在所有条件都找不到商品的时候 在通过该方式判断是否是本地预打包商品 与系统预打包逻辑一样 都必须放在所有条件最后
        /**
         * FFWWWWWEEEEENNNNNC
         *  F店名 2位
         *  W编码 5位
         * E金额 5位
         * N重量 5位
         * C校验 1位
         * 一共18位
         * 例如
         */
        //为了防止自身不是最后的逻辑 将走到该逻辑大部分行为都会被返回空数组 尽可能避免该逻辑位置放置不正确
        $GetCZBarcodeFunc = function ($str) {
            $codeF = (int)substr($str, 2);
            $codeW = (int)substr($str, 2, 5);//商品库编码
            $codeE = (int)substr($str, 7, 5);//金额单位 为 分
            $codeN = (int)substr($str, 12, 5);//重量为G 应该 待测试------
            $codeC = (int)substr($str, 17);


            if (!empty($codeW)) {
                $where = [];
                $where['goodsFlag'] = 1;
                $where['goodsSn'] = $codeW;
                $retdata = M('goods')->where($where)->find();


                if (!empty($retdata)) {
                    return getGoodsSku([$retdata]);
                } else {
                    return [];
                }

            }
        };
        //huihui20200902 处理本地预打包条码
        if (strlen($barcode) == 18) {//是否为18位条码
            $codeF = (int)substr($barcode, 2);
            $codeW = (int)substr($barcode, 2, 5);//商品库编码
            $codeE = (int)substr($barcode, 7, 5);//金额单位 为 分
            $codeN = (int)substr($barcode, 12, 5);//重量为G 应该 待测试------
            $codeC = (int)substr($barcode, 17);


            $codeData = $GetCZBarcodeFunc($barcode);
            setObj($codeData, "shopPrice", $codeE / 100, null);//替换店铺价格

            //替换条码
            setObj($codeData, "goodsSn", $barcode, null);


            return returnData($codeData);
        }


        //TODO:系统预打包和本地预打包的价格需要直接替换 避免前端更换字段计算处理 过于麻烦 ok
        //增加一个商品类型标识 系统预打包和本地预打包后期可能会有特殊作用 SuppPriceDiff  如果为预打包商品改为 -1
        //TODO:如果收银提交结算商品记录 那么需要考虑同一个商品id但是存在多个的情况 因为一个产品如果称重会有多个条码 而且条码与商品id都可能重复的情况 需要注意结算接口
        //TODO:前端根据goodsSn做唯一性所以预打包的条码也要替换掉才行 ok

        $where = [];
        $where['goodsFlag'] = 1;
        $where['shopId'] = $shopId;
        $where['goodsId'] = (int)$goodsId;
        $data = (array)$goodsTab->where($where)->find();
        $data = getGoodsSku($data);
        $data['barcodeInfo'] = [];
        return returnData(getGoodsSku($data));
    }

    /**
     *POS职员登陆
     * @param string $loginName 登陆账号
     * @param string $loginPwd 登陆密码
     * @param int $type 账号类型【1:总管理员|2:门店职员】
     * */
    public function login(string $loginName, string $loginPwd, int $type)
    {
        if ($type == 1) {//管理员登录
            $m = D('Home/Shops');
            $rs = $m->login();
            $userName = $rs['shop']['shopName'];
            $account = $rs['shop']['loginName'];
        } else {//门店职员
            $m = D('Home/User');
            $rs = $m->login();
            $userName = $rs['shop']['username'];
            $account = $rs['shop']['phone'];
        }
        if ($rs['status'] != 1) {
            return returnData(false, -1, 'error', '账号或密码错误');
        }
        $shop = $rs['shop'];
        //生成用唯一token
        $code = 'shops';
        $memberToken = md5(uniqid('', true) . $code . $shop['shopId'] . $shop['loginName'] . (string)microtime());
        $shop['login_type'] = $type;
        if (!userTokenAdd($memberToken, $shop)) {
            return returnData(false, -1, 'error', '登陆失败');
        }
        //END
        unset($rs['shop']);
        $data = [];
        $data['token'] = $memberToken;
        $data['userName'] = $userName;
        $data['account'] = $account;
        return returnData($data);
    }

    /**
     * 换货-动作
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/vpd42w
     * @param array $loginInfo
     * @param array $params <p>
     * array $exchangeData
     * </p>
     * */
    public function exchangeGoods(array $loginInfo, array $params)
    {
        $config = $GLOBALS['CONFIG'];
        $shopId = $loginInfo['shopId'];
        $usersTab = M('users');
        $userScoreTab = M('user_score');
        $shopsTab = M('shops');
        $exchangeOrderTab = M('pos_exchange_orders');
        $exchangeOrderGoodsTab = M('pos_exchange_orders_goods');
        $posOrderGoodsTab = M('pos_orders_goods');
        //接收参数-start
        $exchangeData = $params['exchangeData'];
        $userId = (int)$exchangeData['userId'];
        $orderId = (int)$exchangeData['orderId'];
        $wrapAuthCode = (string)$exchangeData['auth_code'];
        $wrapPay = (int)$exchangeData['pay'];
        $wrapDiscount = (float)$exchangeData['discount'];//整单折扣必须1-10之间
        $wrapDiscountPrice = (float)$exchangeData['discountPrice'];//整单折扣金额
        $wrapCash = (float)$exchangeData['cash'];
        $wrapChange = (float)$exchangeData['change'];
        $wrapBalance = (float)$exchangeData['balance'];
        $wrapUnionpay = (float)$exchangeData['unionpay'];
        $wrapWechat = (float)$exchangeData['wechat'];
        $wrapAlipay = (float)$exchangeData['alipay'];
        $wrapRealpayment = (float)$exchangeData['realpayment'];
        $wrapSetintegral = (float)$exchangeData['setintegral'];
        $wrapAllowanceAmount = (float)$exchangeData['allowanceAmount'];
        if (!empty($wrapPay)) {
            if (!in_array($wrapPay, [1, 2, 3, 4, 5, 6]) || (empty($wrapPay) && !empty($wrapRealpayment))) {
                $msg = "请输入正确的付款方式";
                return returnData(false, -1, 'error', $msg);
            }
        }
        if (!empty($wrapDiscount)) {
            if ($wrapDiscount < 1 || $wrapDiscount > 10) {
                $msg = "整单折扣必须在1至10之间";
                return returnData(false, -1, 'error', $msg);
            }
        }
        $goodsData = $exchangeData['goods'];//换货商品数据
        //接收参数-end
        $orderWhere = [];
        $orderWhere['id'] = $orderId;
        $orderInfo = $this->getPosOrderInfo($orderWhere);
        if (empty($orderInfo)) {
            return returnData(false, -1, 'error', '订单不存在');
        }
        $userInfo = [];
        if (!empty($userId)) {
            $where = [];
            $where['userFlag'] = 1;
            $where['userId'] = $userId;
            $userInfo = D('Home/Users')->getUserInfoRow($where);
            if (empty($userInfo) || $userInfo['userId'] != $orderInfo['memberId']) {
                return returnData(false, -1, 'error', '当前用户和当前订单不匹配');
            }
        }
        $orderGoodsTab = M('pos_orders_goods');
        $goodsTab = M('goods');
        $systemSkuTab = M('sku_goods_system');
        $totalMoney = 0;
        $totalScore = 0;
        foreach ($goodsData as &$item) {
            $item['orderGoodsRelationId'] = (int)$item['orderGoodsRelationId'];
            $item['orderGoodsExchangeNumber'] = (int)$item['orderGoodsExchangeNumber'];
            $item['orderGoodsExchangeWeight'] = (float)$item['orderGoodsExchangeWeight'];
            $item['exchangeGoodsId'] = (int)$item['exchangeGoodsId'];
            $item['exchangeGoodsSkuId'] = (int)$item['exchangeGoodsSkuId'];
            $item['exchangeGoodsWeight'] = (float)$item['exchangeGoodsWeight'];
            $item['exchangeGoodsName'] = (string)$item['exchangeGoodsName'];
            $item['exchangeGoodsNumber'] = (int)$item['exchangeGoodsNumber'];
            $item['exchangeGoodsOriginalPrice'] = (float)$item['exchangeGoodsOriginalPrice'];
            $item['exchangeGoodsFavorablePrice'] = (float)$item['exchangeGoodsFavorablePrice'];
            $item['exchangeGoodsPresentPrice'] = (float)$item['exchangeGoodsPresentPrice'];
            $item['exchangeGoodsDiscount'] = (float)$item['exchangeGoodsDiscount'];
            $item['exchangeGoodsSubtotal'] = (float)$item['exchangeGoodsSubtotal'];
            $item['exchangeGoodsIntegral'] = (float)$item['exchangeGoodsIntegral'];
            $where = [];
            $where['id'] = $item['orderGoodsRelationId'];
            $where['state'] = 1;
            $orderGoodsInfo = $orderGoodsTab
                ->where($where)
                ->find();
            if (empty($orderGoodsInfo)) {
                $msg = "订单商品关联id错误";
                return returnData(false, -1, 'error', $msg);
            }
            if ($orderGoodsInfo['orderid'] != $orderId) {
                $msg = "订单商品【{$orderGoodsInfo['goodsName']}】和当前订单不匹配";
                return returnData(false, -1, 'error', $msg);
            }
            $goodsInfo = $goodsTab->where(['goodsId' => $orderGoodsInfo['goodsId']])->find();
            $item['orderGoodsName'] = $orderGoodsInfo['goodsName'];
            $item['orderGoodsExchangeNumber'] = abs($item['orderGoodsExchangeNumber']);

            if (empty($item['orderGoodsExchangeNumber'])) {
                $msg = "商品【{$item['orderGoodsName']}】退回的数量必须大于0";
                return returnData(false, -1, 'error', $msg);
            }
            if (($item['orderGoodsExchangeNumber'] + $orderGoodsInfo['exchangeNum'] + $orderGoodsInfo['refundNum']) > $orderGoodsInfo['number']) {
                $surplusNumber = $orderGoodsInfo['number'] - $orderGoodsInfo['exchangeNum'] - $orderGoodsInfo['refundNum'];
                $msg = "商品【{$item['orderGoodsName']}】本次换货数量最多为{$surplusNumber}";
                $msg .= "，包含已换货数量：{$orderGoodsInfo['exchangeNum']}";
                if ($orderGoodsInfo['refundNum'] > 0) {
                    $msg .= "，已退货数量：{$orderGoodsInfo['refundNum']}";
                }
                return returnData(false, -1, 'error', $msg);
            }
            if (empty($item['exchangeGoodsNumber'])) {
                $msg = "商品【{$item['orderGoodsName']}】换货的数量必须大于0";
                return returnData(false, -1, 'error', $msg);
            }
            if ($goodsInfo['SuppPriceDiff'] == 1) {
                //如果原订单商品是称重商品
                //如果前端现在不做称重的话,那就直接把购买时的商品重量传过来
                if (empty($item['orderGoodsExchangeWeight'])) {
                    $msg = "原订单商品【{$orderGoodsInfo['goodsName']}】缺少重量";
                    return returnData(false, -1, 'error', $msg);
                }
                if ($item['orderGoodsExchangeWeight'] <= 0 || $item['orderGoodsExchangeWeight'] > $orderGoodsInfo['weight']) {
                    $msg = "请输入原订单商品【{$orderGoodsInfo['goodsName']}】正确的换货重量";
                    return returnData(false, -1, 'error', $msg);
                }
            }
            if (!empty($item['exchangeGoodsDiscount'])) {
                if ($item['exchangeGoodsDiscount'] < 1 || $item['exchangeGoodsDiscount'] > 99) {
                    $msg = "单商品折扣必须在1-99之间";
                    return returnData(false, -1, 'error', $msg);
                }
            }
            $where = [];
            $where['goodsFlag'] = 1;
            $where['goodsId'] = $item['exchangeGoodsId'];
            $exchangeGoodsInfo = $goodsTab->where($where)->find();//换货商品信息
            if ($exchangeGoodsInfo['SuppPriceDiff'] == 1) {
                //没有称重功能就传商品信息中的重量
                if ($item['exchangeGoodsWeight'] <= 0) {
                    $msg = "换货商品【{$item['exchangeGoodsName']}】属于称重商品，请传入重量";
                    return returnData(false, -1, 'error', $msg);
                }
            }
            if ($item['exchangeGoodsSkuId'] > 0) {
                //sku属性值
                $where = [];
                $where['skuId'] = $item['exchangeGoodsSkuId'];
                $systemSkuInfo = $systemSkuTab->where($where)->find();
                if (empty($systemSkuInfo)) {
                    $msg = "换货商品【{$item['exchangeGoodsName']}】sku信息有误";
                    return returnData(false, -1, 'error', $msg);
                }
            }
            if (empty($exchangeGoodsInfo['goodsId'])) {
                $msg = "换货商品【{$item['exchangeGoodsName']}】不存在";
                return returnData(false, -1, 'error', $msg);
            }
            $item['orderGoodsExchangeSubtotal'] = $orderGoodsInfo['presentPrice'] * $item['orderGoodsExchangeNumber'];
            $goodsId = $item['exchangeGoodsId'];
            $presentPrice = $item['exchangeGoodsPresentPrice'];//商品现价
            $number = $item['exchangeGoodsNumber'];//购买数量
            $weight = $item['exchangeGoodsWeight'];//实际称重重量
            $goodsInfo = $this->getShopGoodsInfo($shopId, $goodsId)['data'];
            if ($goodsInfo['SuppPriceDiff'] < 0) {//标品
                $money_t = bc_math($presentPrice, $number, 'bcmul', 2);//商品小计
            } else {//称重商品
                $singleGoodsPirce = mathWeightPrice($goodsId, $weight, $presentPrice);
                $money_t = bc_math($singleGoodsPirce, $number, 'bcmul', 2);
            }
            $totalMoney += $money_t;
            if ($config['isOpenScorePay'] == 1) {
                //获取比例
                $discountPrice = $money_t;
                $scoreScoreCashRatio = explode(':', $config['scoreCashRatio']);
                $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
                $canUseScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
                $totalScore += $canUseScore;
                //$scoreAmount = (int)$integralRateScore / (int)$scoreScoreCashRatio[0] * (int)$scoreScoreCashRatio[1];
            }
        }
        unset($item);
        //收银的订单金额相关这里就不计算了,没有意义,收银端是可以随便改金额的
        //$total_money = formatAmount($total_money, 2);
        //$wrapRealpayment = $total_money;
        M()->startTrans();
        $exchangeType = 1;
        if ($wrapRealpayment > 0) {
            $exchangeType = 2;
        } elseif ($wrapRealpayment < 0) {
            $exchangeType = 3;
        }
        $wrapOrderNO = $this->createOrderNo();
        $exchangeId = $exchangeOrderTab->add(
            [
                'orderId' => $orderId,
                'billNo' => $wrapOrderNO,
                'exchangeType' => $exchangeType,
                'state' => 1,
                'createTime' => date('Y-m-d H:i:s', time()),
                'updateTime' => date('Y-m-d H:i:s', time()),
            ]
        );
        //判断是否使用余额 并用户余额是否够用 扣除余额 写入余额流水
        if (!empty($wrapBalance) && !empty($userId) && !empty($userInfo)) {
            if ((float)$wrapBalance > (float)$userInfo['balance']) {
                M()->rollback();
                return returnData(false, -1, 'error', '用户余额不足');
            }
            //更改用户余额
            $updateBalanceRes = $usersTab->where('userId=' . $userInfo['userId'])->setDec('balance', (float)$wrapBalance);
            if (!$updateBalanceRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '用户余额扣款失败');
            }
            //写入余额流水
            $mod_user_balance = M('user_balance');
            $add_user_balance['userId'] = $userInfo['userId'];
            $add_user_balance['balance'] = $wrapBalance;
            $add_user_balance['dataSrc'] = 2;
            $add_user_balance['orderNo'] = $wrapOrderNO;
            $add_user_balance['dataRemarks'] = '线下消费';
            $add_user_balance['balanceType'] = 2;
            $add_user_balance['createTime'] = date('Y-m-d H:i:s');
            $add_user_balance['shopId'] = $shopId;
            $balanceLogRes = $mod_user_balance->add($add_user_balance);
            if (!$balanceLogRes) {
                M()->rollback();
                return returnData(false, -1, 'error', '用户余额流水记录失败');
            }
        }
        //没有开启积分支付
        if (empty($config['isOpenScorePay']) && $wrapSetintegral > 0) {
            M()->rollback();
            return returnData(false, -1, 'error', '没有开启积分支付功能，该笔订单不能使用积分抵扣');
        }
        $setintegral = 0;//要扣除的积分
        //判断是否使用积分抵现且是否code 并依赖比例消费 且用户积分是否充足 写入积分流水
        if (!empty($wrapSetintegral) and !empty($userId) && $config['isOpenScorePay'] == 1 && !empty($userInfo)) {
            if ($wrapSetintegral != $totalScore) {//统一前后端计算可使用积分的结果
                M()->rollback();
                $msg = "积分校验不通过，当前订单可以使用{$totalScore}积分";
                return returnData(false, -1, 'error', $msg);
            }
            if ($wrapSetintegral > (int)$userInfo['userScore']) {
                M()->rollback();
                $msg = "用户积分不足，本单最多可以使用{$userInfo['userScore']}积分";
                return returnData(false, -1, 'error', $msg);
            }
            //要扣除的积分
            $setintegral = $wrapSetintegral;
            //更改用户积分
            //$usersTab->where('userId=' . $userInfo['userId'])->setDec('userScore', $setintegral);
            //积分处理-start
            $users_service_module = new UsersServiceModule();
            $score = (int)$setintegral;
            $users_id = $userInfo['userId'];
            $result = $users_service_module->deduction_users_score($users_id, $score, M());
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(null, -1, 'error', $result['msg']);
            }
//积分处理-end
            //写入积分流水
            $mod_user_score_data['userId'] = $userInfo['userId'];
            $mod_user_score_data['score'] = $setintegral;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "积分消费";
            $mod_user_score_data['dataId'] = $orderInfo['id'];
            $mod_user_score_data['scoreType'] = 2;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $scoreLogRes = $userScoreTab->add($mod_user_score_data);
            if (!$scoreLogRes) {
                M()->rollback();
                $msg = "积分流水记录失败";
                return returnData(false, -1, 'error', $msg);
            }
        }
        $cash_new = $wrapCash - $wrapChange;
        //使用到了现金，要扣除商家预存款
        if ($cash_new > 0 and $GLOBALS['CONFIG']['openPresaleCash'] == 1) {
            $where = [];
            $where['shopId'] = $shopId;
            $shopInfo = M('shops')->lock(true)->where($where)->find();
            if ($cash_new > $shopInfo['predeposit']) {
                M()->rollback();
                $msg = "商家预存款不足";
                return returnData(false, -1, 'error', $msg);
            }
            $shopsTab->where(array('shopId' => $shopId))->setDec('predeposit', $cash_new);
            //写入流水
            $log_sys_moneys_data = [
                'targetType' => 1,
                'targetId' => $shopId,
                'dataSrc' => 3,
                'dataId' => $orderInfo['id'],
                'moneyRemark' => '消费',
                'moneyType' => 0,
                'money' => $cash_new,
                'createTime' => date('Y-m-d H:i:s'),
                'dataFlag' => 1,
                'state' => 1,
                'payType' => 1
            ];
            $logRes = M('log_sys_moneys')->add($log_sys_moneys_data);
            if (!$logRes) {
                M()->rollback();
                $msg = "消费流水记录失败";
                return returnData(false, -1, 'error', $msg);
            }
        }
        //更改订单数据
        $dataWhere = [];
        $dataWhere['billNo'] = $wrapOrderNO;
        $dataWhere['state'] = 1;
        $dataWhere['orderId'] = $orderId;
        $dataSave = [];
        $dataSave['pay'] = $wrapPay;
        $dataSave['discount'] = $wrapDiscount;
        $dataSave['discountPrice'] = $wrapDiscountPrice;
        $dataSave['integral'] = 0;
        //如果开启获取积分 对用户增加积分
        if ($config['isOrderScore'] == 1 && !empty($userInfo)) {
            $reward_score = getOrderScoreByOrderScoreRate($wrapRealpayment);
            //$usersTab->where("userId = " . $userInfo['userId'])->setInc('userScore', $reward_score);
            //积分处理-start
            //$users_service_module = new UsersServiceModule();
            $score = (int)$reward_score;
            $users_id = $userInfo['userId'];
            $result = $users_service_module->deduction_users_score($users_id, $score, M());
            if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
                M()->rollback();
                return returnData(null, -1, 'error', $result['msg']);
            }
//积分处理-end
            $mod_user_score_data = [];
            $mod_user_score_data['userId'] = $userInfo['userId'];
            $mod_user_score_data['score'] = $reward_score;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "线下购物奖励积分";
            $mod_user_score_data['dataId'] = $orderInfo['id'];
            $mod_user_score_data['scoreType'] = 1;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $logRes = $userScoreTab->add($mod_user_score_data);
            if (!$logRes) {
                M()->rollback();
                $msg = "积分记录失败";
                return returnData(false, -1, 'error', $msg);
            }
            $dataSave['integral'] = $reward_score;
        }
        $dataSave['cash'] = $wrapCash;
        $dataSave['balance'] = $wrapBalance;
        $dataSave['unionpay'] = $wrapUnionpay;
        $dataSave['wechat'] = $wrapWechat;
        $dataSave['alipay'] = $wrapAlipay;
        $dataSave['change'] = $wrapChange;
        $dataSave['allowanceAmount'] = $wrapAllowanceAmount;
        $dataSave['realpayment'] = $wrapRealpayment;
        $dataSave['state'] = 2;
        $dataSave['setintegral'] = $setintegral;
        $dataSave['updateTime'] = date('Y-m-d H:i:s', time());
        //添加收银员ID
        if (empty($loginInfo['id'])) { //总管理员
            $dataSave['actionUserId'] = $loginInfo['userId'];
        } else {//其他管理员
            $dataSave['actionUserId'] = $loginInfo['id'];
        }
        if (!$exchangeOrderTab->where($dataWhere)->save($dataSave)) {
            M()->rollback();
            return returnData(false, -1, 'error', '订单数据异常');
        }
        $len = count($goodsData);
        //写入商品 更改库存
        for ($i = 0; $i < $len; $i++) {
            $goodsValue = $goodsData[$i];
            $adddata = [];
            $adddata['exchangeId'] = $exchangeId;
            $adddata['orderGoodsRelationId'] = $goodsValue['orderGoodsRelationId'];
            $adddata['orderGoodsExchangeNumber'] = $goodsValue['orderGoodsExchangeNumber'];
            $adddata['orderGoodsExchangeWeight'] = $goodsValue['orderGoodsExchangeWeight'];
            $adddata['orderGoodsExchangeSubtotal'] = $goodsValue['orderGoodsExchangeSubtotal'];
            $adddata['exchangeGoodsId'] = $goodsValue['exchangeGoodsId'];
            $adddata['exchangeGoodsSkuId'] = $goodsValue['exchangeGoodsSkuId'];
            $adddata['exchangeGoodsSpecAttr'] = '';
            if ($goodsValue['exchangeGoodsSkuId'] > 0) {
                //sku属性值
                $where = [];
                $where['skuId'] = $goodsValue['skuId'];
                $systemSkuInfo = $systemSkuTab->where($where)->find();
                $systemSkuInfo['selfSpec'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $systemSkuInfo['skuId']])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->order('sp.sort asc')
                    ->select();
                if (!empty($systemSkuInfo['selfSpec'])) {
                    foreach ($systemSkuInfo['selfSpec'] as $sv) {
                        $systemSkuInfo['skuSpecStr'] .= $sv['attrName'] . ",";
                    }
                }
                $adddata['exchangeGoodsSpecAttr'] = trim($systemSkuInfo['skuSpecStr'], ',');
            }
            $adddata['exchangeGoodsWeight'] = $goodsValue['exchangeGoodsWeight'];
            $adddata['exchangeGoodsName'] = $goodsValue['exchangeGoodsName'];
            $adddata['exchangeGoodsNumber'] = $goodsValue['exchangeGoodsNumber'];
            $adddata['exchangeGoodsOriginalPrice'] = $goodsValue['exchangeGoodsOriginalPrice'];
            $adddata['exchangeGoodsFavorablePrice'] = $goodsValue['exchangeGoodsFavorablePrice'];
            $adddata['exchangeGoodsPresentPrice'] = $goodsValue['exchangeGoodsPresentPrice'];
            if (!empty($goodsValue['exchangeGoodsDiscount'])) {
                $adddata['exchangeGoodsDiscount'] = $goodsValue['exchangeGoodsDiscount'] / 100;
            }
            $adddata['exchangeGoodsSubtotal'] = $goodsValue['exchangeGoodsSubtotal'];
            $adddata['createTime'] = date('Y-m-d H:i:s', time());
            $adddata['updateTime'] = date('Y-m-d H:i:s', time());
            $integral = $goodsValue['exchangeGoodsIntegral'];
            $adddata['exchangeGoodsIntegral'] = 0;
            if (!empty($integral) && !empty($wrapSetintegral)) {
                $goodsInfo = $this->getShopGoodsInfo($shopId, $adddata['exchangeGoodsId'])['data'];
                if ($goodsInfo['SuppPriceDiff'] < 0) {//标品
                    $money_t = bc_math($adddata['exchangeGoodsPresentPrice'], $adddata['exchangeGoodsNumber'], 'bcmul', 2);//商品小计
                } else {//称重商品
                    $singleGoodsPirce = mathWeightPrice($adddata['exchangeGoodsId'], $adddata['exchangeGoodsWeight'], $adddata['exchangeGoodsPresentPrice']);
                    $money_t = bc_math($singleGoodsPirce, $adddata['exchangeGoodsNumber'], 'bcmul', 2);
                }
                //获取比例
                $discountPrice = $money_t;
                $scoreScoreCashRatio = explode(':', $config['scoreCashRatio']);
                $goodsScore = (int)$discountPrice / (int)$scoreScoreCashRatio[1] * (int)$scoreScoreCashRatio[0];
                $canUseScore = $goodsScore * ($goodsInfo['integralRate'] / 100);//可以抵扣的积分
                if ($canUseScore != $integral) {
                    M()->rollback();
                    return returnData(false, -1, 'error', "商品【{$adddata['exchangeGoodsName']}】使用积分校验不通过，可以使用{$canUseScore}积分");
                }
                $adddata['exchangeGoodsIntegral'] = $canUseScore;
            }
            $sdata[] = $adddata;
            //处理换货商品的库存-start
            if ($adddata['exchangeGoodsWeight'] > 0) {
                //非标品
                $num = $adddata['exchangeGoodsWeight'] * $adddata['exchangeGoodsNumber'];
            } else {
                //标品
                $num = $adddata['exchangeGoodsNumber'];
            }
            if ($goodsData[$i]['exchangeGoodsSkuId'] > 0) {
                $editStockRes = $systemSkuTab->where(['skuId' => $goodsData[$i]['exchangeGoodsSkuId']])->setDec('skuGoodsStock', $num);
            } else {
                $editStockRes = $goodsTab->where('goodsId = ' . $goodsData[$i]['exchangeGoodsId'])->setDec('goodsStock', $num);
            }
            if (!$editStockRes) {
                M()->rollback();
                $msg = "商品【{$adddata['exchangeGoodsName']}】库存修改失败";
                return returnData(false, -1, 'error', $msg);
            }
            //处理换货商品的库存-end
            //处理返还商品的库存-start
            $where = [];
            $where['id'] = $goodsData[$i]['orderGoodsRelationId'];
            $where['state'] = 1;
            $orderGoodsInfo = $orderGoodsTab->where($where)->find();
            $orderGoodsInfo['SuppPriceDiff'] = $goodsTab->where(['goodsId' => $orderGoodsInfo['goodsId']])->getField('SuppPriceDiff');
            if ($orderGoodsInfo['SuppPriceDiff'] > 0) {
                //非标品
                $num = $adddata['orderGoodsExchangeWeight'] * $adddata['orderGoodsExchangeNumber'];
            } else {
                //标品
                $num = $adddata['orderGoodsExchangeNumber'];
            }
            $editStockRes = $goodsTab->where('goodsId = ' . $orderGoodsInfo['goodsId'])->setInc('goodsStock', $num);
            if ($orderGoodsInfo['skuId'] > 0) {
                //更改sku库存
                $editStockRes = $systemSkuTab->where(['skuId' => $orderGoodsInfo['skuId']])->setInc('skuGoodsStock', $num);
            }
            if (!$editStockRes) {
                M()->rollback();
                $msg = "返还商品【{$orderGoodsInfo['goodsId']}】库存修改失败";
                return returnData(false, -1, 'error', $msg);
            }
            $exchangeNumber = $orderGoodsInfo['number'] - $orderGoodsInfo['refundNum'] - $orderGoodsInfo['exchangeNum'];
            $exchangeStatus = 2;//部分换货

            if ($exchangeNumber == $goodsValue['orderGoodsExchangeNumber']) {
                $exchangeStatus = 3;//换货完成
            }
            //处理返还商品的库存-end
            //修改订单商品换货信息
            $where = [];
            $where['id'] = $orderGoodsInfo['id'];
            $saveData = [];
            $saveData['exchangeStatus'] = $exchangeStatus;
            $saveData['exchangeNum'] = $orderGoodsInfo['exchangeNum'] + $goodsValue['orderGoodsExchangeNumber'];
            $updateRes = $posOrderGoodsTab->where($where)->save($saveData);
            if ($updateRes === false) {
                M()->rollback();
                $msg = "商品【{$orderGoodsInfo['goodsName']}】库存换货失败";
                return returnData(false, -1, 'error', $msg);
            }
        }
        if ($exchangeOrderGoodsTab->addAll($sdata)) {//全部添加
            if ($wrapWechat > 0) {//微信支付
                $result = $this->doWxPay($wrapAuthCode, $wrapWechat, $wrapOrderNO);
            }
            if ($wrapAlipay > 0) {//支付宝支付
                $result = $this->doAliPay($shopInfo['userId'], $wrapAuthCode, $wrapAlipay, $wrapOrderNO);
            }
            if ($result['code'] == -1) {//支付失败
                M()->rollback();
                return $result;
            }
            if (!empty($result['transaction_id'])) {
                $saveData = [];
                $saveData['outTradeNo'] = $result['transaction_id'];
                $where = [];
                $where['billNo'] = [];
                $exchangeOrderTab->where(['billNo' => $where])->save($saveData);
            }
            M()->commit();
            return returnData(true);
        } else {
            M()->rollback();
            return returnData(false, -1, 'error', '提交失败');
        }
    }

    /**
     * 获取换货单列表
     * @param int $shopId
     * @param array $params <p>
     * string billNo 换货单号
     * string orderNO 订单号
     * string userName 用户名
     * string userPhone 手机号
     * string user_name 收营员-账号
     * string user_username 收营员-名称
     * string user_phone 收营员-电话
     * int pay 付款方式(1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付)
     * int state 换货状态【1：待换货|3:换货完成】
     * datetime startDate 单据时间区间-开始时间
     * datetime endDate 单据时间区间-结束时间
     * int page 页码
     * int pageSize 分页条数,默认15条
     * </p>
     * */
    public function getExchangeOrderList(int $shopId, array $params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = " exchangeOrders.dataFlag=1 and orders.shopId={$shopId} ";
        $whereFind = [];
        $whereFind['exchangeOrders.billNo'] = function () use ($params) {
            if (empty($params['billNo'])) {
                return null;
            }
            return ['like', "%{$params['billNo']}%", 'and'];
        };
        $whereFind['user.name'] = function () use ($params) {
            if (empty($params['user_name'])) {
                return null;
            }
            return ['like', "%{$params['user_name']}%", 'and'];
        };
        $whereFind['user.username'] = function () use ($params) {
            if (empty($params['user_username'])) {
                return null;
            }
            return ['like', "%{$params['user_username']}%", 'and'];
        };
        $whereFind['user.phone'] = function () use ($params) {
            if (empty($params['user_phone'])) {
                return null;
            }
            return ['like', "%{$params['user_phone']}%", 'and'];
        };
        $whereFind['orders.orderNO'] = function () use ($params) {
            if (empty($params['orderNO'])) {
                return null;
            }
            return ['like', "%{$params['orderNO']}%", 'and'];
        };
        $whereFind['users.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['users.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        $whereFind['exchangeOrders.pay'] = function () use ($params) {
            if (empty($params['pay'])) {
                return null;
            }
            return ['=', "{$params['pay']}", 'and'];
        };
        $whereFind['exchangeOrders.state'] = function () use ($params) {
            if (empty($params['state'])) {
                return null;
            }
            return ['=', "{$params['state']}", 'and'];
        };
        $whereFind['exchangeOrders.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        $field = 'orders.orderNO,orders.realpayment as orderRealpayment,orders.setintegral as orderSetintegral';
        $field .= ',users.userName,users.userPhone';
        $field .= ',exchangeOrders.*';
        $field .= ',user.name as user_name,user.username as user_username,user.phone as user_phone';
        $sql = "select {$field} from __PREFIX__pos_exchange_orders exchangeOrders 
                left join __PREFIX__pos_orders orders on orders.id=exchangeOrders.orderId
                left join __PREFIX__users users on users.userId=orders.memberId
                left join __PREFIX__user `user` on orders.userId=`user`.id
                where {$whereInfo} order by exchangeOrders.exchangeId desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            $root = $data['root'];
            foreach ($root as &$item) {
                $item['discount'] = ($item['discount'] / 10) . '%';
                $item['userName'] = (string)$item['userName'];
                $item['userPhone'] = (string)$item['userPhone'];
                $item['user_name'] = (string)$item['user_name'];
                $item['user_username'] = (string)$item['user_username'];
                $item['user_phone'] = (string)$item['user_phone'];
            }
            unset($item);
            $data['root'] = $root;
        }
        return returnData($data);
    }

    /**
     * 换货单单详情
     * @param int $exchangeId 退货单id
     * */
    public function getExchangeOrderDetail(int $exchangeId)
    {
        $where = [];
        $where['exchange.exchangeId'] = $exchangeId;
        $where['exchange.dataFlag'] = 1;
        $field = 'exchange.*';
        $field .= ',orders.orderNO,orders.realpayment as orderRealpayment,orders.setintegral as orderSetintegral';
        $field .= ',users.userName,users.userPhone';
        $field .= ',u.name as user_name,u.username as user_username,u.phone as user_phone';
        $data = M('pos_exchange_orders exchange')
            ->join('left join wst_pos_orders orders on orders.id=exchange.orderId')
            ->join('left join wst_users users on users.userId=orders.memberId')
            ->join('left join wst_user u on u.id=orders.userId')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($data)) {
            return [];
        }
        $data['discount'] = ($data['discount'] * 10) . '%';
        $data['userName'] = (string)$data['userName'];
        $data['userPhone'] = (string)$data['userPhone'];
        $data['user_name'] = (string)$data['user_name'];
        $data['user_username'] = (string)$data['user_username'];
        $data['user_phone'] = (string)$data['user_phone'];
        //goods
        $field = 'exchange_goods.*';
        $field .= ',pos_goods.goodsName,pos_goods.skuSpecAttr,pos_goods.number as orderGoodsNumber,pos_goods.weight';
        $where = [];
        $where['exchangeId'] = $exchangeId;
        $where['dataFlag'] = 1;
        $returnGoods = M('pos_exchange_orders_goods exchange_goods')
            ->join('left join wst_pos_orders_goods pos_goods on pos_goods.goodsId=exchange_goods.exchangeGoodsId')
            ->where($where)
            ->field($field)
            ->group('exchange_goods.id')
            ->select();
        $returnGoods = empty($returnGoods) ? [] : $returnGoods;
        foreach ($returnGoods as &$item) {
            $item['exchangeGoodsDiscount'] = ($item['exchangeGoodsDiscount'] / 100) . '%';
        }
        unset($item);
        $data['goods'] = $returnGoods;
        return $data;
    }

    /**
     * 日结单
     * @param array $loginUser
     * @param datetime $datetime
     * */
    public function dailyStatement(array $loginUser, string $datetime)
    {
        $startDate = $datetime . ' 00:00:00';
        $endDate = $datetime . ' 23:59:59';
        if (empty($loginUser['id'])) {
            $actionUserInfo = [
                'user_id' => $loginUser['userId'],
                'user_username' => $loginUser['userName'],
                'user_phone' => $loginUser['userPhone'],
            ];
        } else {
            $actionUserInfo = [
                'user_id' => $loginUser['id'],
                'user_username' => $loginUser['username'],
                'user_phone' => $loginUser['phone'],
            ];
        }
        //table-start
        $posOrderTab = M('pos_orders');
        $givebackOrderTab = M('pos_giveback_orders');
        $exchangeOrderTab = M('pos_exchange_orders');
        $userBalanceTab = M('user_balance');
        $logSysMoneysTab = M('log_sys_moneys');
        //table-end
        //收银订单-start
        $where = [];
        $where['userId'] = $actionUserInfo['user_id'];
        $where['state'] = 3;
        $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        $posOrderTotal = $posOrderTab->where($where)->count();//收银订单统计
        $real_accept_amount = (float)$posOrderTab->where($where)->sum('real_accept_amount');//收银订单实收金额统计
        $posOrderTotalAmount = (float)$posOrderTab->where($where)->sum('realpayment');//收银订单金额统计
        $posOrderTotalCash = (float)$posOrderTab->where($where)->sum('cash');//收银订单现金统计
        //收银订单-end
        //退货单-start
        $where = [];
        $where['actionUserId'] = $actionUserInfo['user_id'];
        $where['state'] = ['GT', 1];
        $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        $givebackOrderTotal = $givebackOrderTab->where($where)->count();//退货订单统计
        $givebackOrderTotalAmount = (float)$givebackOrderTab->where($where)->sum('realpayment');//退货订单金额统计
        $where['pay'] = 1;
        $givebackOrderTotalCash = (float)$givebackOrderTab->where($where)->sum('realpayment');//退货订单现金统计
        //退货单-end
        //换货单-start
        $where = [];
        $where['actionUserId'] = $actionUserInfo['user_id'];
        $where['state'] = ['GT', 1];
        $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        $exchangeOrderTotal = $exchangeOrderTab->where($where)->count();//退货订单统计
        $where['exchangeType'] = 2;
        $exchangeAmountV = (float)$exchangeOrderTab->where($where)->sum('realpayment');//退货订单金额统计(用户补差价)
        $exchangeCashV = (float)$exchangeOrderTab->where($where)->sum('cash');//退货订单现金统计(用户补差价)
        $where['exchangeType'] = 3;
        $exchangeAmountX = (float)$exchangeOrderTab->where($where)->sum('realpayment');//退货订单金额统计(商家补差价)
        $exchangeCashX = (float)$exchangeOrderTab->where($where)->sum('cash');//退货订单现金统计(商家补差价)
        $diffAmount = $exchangeAmountV - $exchangeAmountX;
        $exchangeOrderTotalAmount = $diffAmount;
        $exchangeOrderTotalCash = $exchangeCashV - $exchangeCashX;
        //换货单-end
        //会员充值-start
        $where = [];
        $where['actionUserId'] = $actionUserInfo['user_id'];
        $where['dataRemarks'] = '会员充值';
        $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
        $rechargeTotal = $userBalanceTab->where($where)->count();//会员充值统计
        $rechargeList = $userBalanceTab->where($where)->select();
        $rechargeTotalAmount = 0;//会员充值金额统计
        $balanceIdArr = [];
        foreach ($rechargeList as $item) {
            $rechargeTotalAmount += $item['balance'];
            $balanceIdArr[] = $item['balanceId'];
        }
        $rechargeTotalCash = 0;//会员充值现金统计
        if (!empty($rechargeList)) {
            $where = [];
            $where['dataId'] = ['IN', $balanceIdArr];
            $where['moneyRemark'] = '会员充值';
            $where['createTime'] = ['between', ["{$startDate}", "{$endDate}"]];
            $rechargeTotalCash = (float)$logSysMoneysTab->where($where)->sum('money');
        }
        //会员充值-end
        $data = [];
        $data['datetime'] = $datetime;
        $data['total_num'] = (int)($posOrderTotal + $givebackOrderTotal + $exchangeOrderTotal + $rechargeTotal);//总笔数 pos订单+退+换+用户充值
        $data['total_amount'] = formatAmount(($real_accept_amount + $exchangeOrderTotalAmount + $rechargeTotalAmount) - $givebackOrderTotalAmount);
        $data['total_sale'] = (int)$posOrderTotal;//笔数-销
        $data['total_return'] = (int)$givebackOrderTotal;//笔数-退
        //$data['total_gift'] = 0;//笔数-赠 PS:暂时没有
        $data['total_exchange'] = (int)$exchangeOrderTotal;//笔数-换
        $data['sale_amount'] = formatAmount($posOrderTotalAmount);//金额-销
        $data['return_amount'] = formatAmount($givebackOrderTotalAmount);//金额-退
        //$data['exchange_gift'] = 0;//金额-赠 PS:暂时没有
        $data['exchange_amount'] = formatAmount($diffAmount);//金额-换
        $data['vip_recharge_num'] = (int)$rechargeTotal;//会员充值-笔数
        $data['vip_recharge_amount'] = formatAmount($rechargeTotalAmount);//会员充值-金额
        //dd($posOrderTotalCash.'+'.$exchangeOrderTotalCash.'+'.$rechargeTotalCash.'+'.$givebackOrderTotalCash);
        $data['rmb_amount'] = formatAmount(($posOrderTotalCash + $exchangeOrderTotalCash + $rechargeTotalCash) - $givebackOrderTotalCash);//人民币-现金 (pos订单+换+用户充值)-退
        return $data;
    }

    /**
     * 获取收营员销售明细
     * @param array $loginUser
     * @param string $startDate
     * @param string $endDate
     * @return array
     * */
    public function getCashierSalesDetails(array $loginUser, string $startDate, string $endDate)
    {
        $todayDate = date('Y-m-d');
        if (empty($startDate) || empty($endDate)) {
            $startDate = $todayDate . ' 00:00:00';
            $endDate = $todayDate . ' 23:59:59';
        } else {
            $startDate .= ' 00:00:00';
            $endDate .= ' 23:59:59';
        }
        $startDateInfo = explode(' ', $startDate);
        $endDateInfo = explode(' ', $endDate);
        $pos_orders_tab = M('pos_orders');
        $where = array(
            'userId' => $loginUser['user_id'],
            'state' => 3,
            'addtime' => array('between', array("{$startDate}", "{$endDate}"))
        );
        $order_list = (array)$pos_orders_tab->where($where)->field('id,orderNO,addtime,userId')->select();
        $total_num = 0;//合计数量
        $total_amount = 0;//金额
        if (empty($order_list)) {
            return array(
                'user_info' => array(
                    'user_id' => $loginUser['user_id'],
                    'user_username' => $loginUser['user_username'],
                ),
                'goods_list' => array(),
                'total_num' => 0,
                'total_amount' => '0.00',
                'startDate' => $startDateInfo[0],
                'endDate' => $endDateInfo[0],
            );
        }
        $order_id_arr = array_unique(array_column($order_list, 'id'));
        $pos_orders_goods_tab = M('pos_orders_goods');
        $where = array(
            'orderid' => array('IN', $order_id_arr),
            'state' => 1,
        );
        $field = 'goodsId,goodsName,goodsSn,originalPrice,favorablePrice,presentPrice,number,subtotal,discount,weight,skuSpecAttr';
        $goods_list = $pos_orders_goods_tab->where($where)->field($field)->select();
        foreach ($goods_list as $item) {
            $total_num += $item['number'];
            $total_amount += $item['subtotal'];
        }
        return array(
            'user_info' => array(
                'user_id' => $loginUser['user_id'],
                'user_username' => $loginUser['user_username'],
                'user_phone' => $loginUser['user_phone'],
            ),
            'goods_list' => $goods_list,
            'total_num' => $total_num,
            'total_amount' => formatAmount($total_amount),
            'startDate' => $startDateInfo[0],
            'endDate' => $endDateInfo[0],
        );
    }

    /**
     * 收银订单统计
     * @param array $params
     * int $shopId 门店id
     * string orderNo PS:订单号
     * string startDate PS:开始时间
     * string endDate PS:结束时间
     * string maxMoney PS:最大金额(金额区间)
     * string minMoney PS:最小金额(金额区间)
     * int state PS:状态 (1:待结算 | 2：已取消 | 3：已结算)
     * int pay PS:支付方式 (1:现金支付 | 2：余额支付 | 3：银联支付 | 4：微信支付 | 5：支付宝支付 | 6：组合支付)
     * string name PS:收银员账号
     * string username PS:收银员姓名
     * string phone PS:收银员手机号
     * string identity PS:身份 1:会员 2：游客
     * string userName 用户名
     * string userPhone 用户手机号
     * @return array
     * */
    public function countPosOrders(array $params)
    {
        $shopId = $params['shopId'];
        $where = " where po.shopId={$shopId} ";
        $whereFind = array();
        $whereFind['po.orderNO'] = function () use ($params) {
            if (empty($params['orderNo'])) {
                return null;
            }
            return ['like', "%{$params['orderNo']}%", 'and'];
        };
        $whereFind['po.addtime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            $params['startDate'] = $params['startDate'];
            $params['endDate'] = $params['endDate'];
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['po.realpayment'] = function () use ($params) {
            if (!is_numeric($params['maxMoney']) || !is_numeric($params['minMoney'])) {
                return null;
            }
            if ((float)$params['maxMoney'] <= 0) {
                return null;
            }
            return ['between', "{$params['minMoney']}' and '{$params['maxMoney']}", 'and'];
        };
        $whereFind['po.state'] = function () use ($params) {
            if (empty($params['state']) || !in_array($params['state'], [1, 2, 3])) {
                return null;
            }
            return ['=', "{$params['state']}", 'and'];
        };
        $whereFind['po.pay'] = function () use ($params) {
            if (empty($params['pay']) || !in_array($params['pay'], [1, 2, 3, 4, 5, 6])) {
                return null;
            }
            return ['=', "{$params['pay']}", 'and'];
        };
        $whereFind['u.name'] = function () use ($params) {
            if (empty($params['user_name'])) {
                return null;
            }
            return ['like', "%{$params['user_name']}%", 'and'];
        };
        $whereFind['u.username'] = function () use ($params) {
            if (empty($params['user_username'])) {
                return null;
            }
            return ['like', "%{$params['user_username']}%", 'and'];
        };
        $whereFind['u.phone'] = function () use ($params) {
            if (empty($params['user_phone'])) {
                return null;
            }
            return ['like', "%{$params['user_phone']}%", 'and'];
        };
        $whereFind['us.userName'] = function () use ($params) {
            if (empty($params['userName'])) {
                return null;
            }
            return ['like', "%{$params['userName']}%", 'and'];
        };
        $whereFind['us.userPhone'] = function () use ($params) {
            if (empty($params['userPhone'])) {
                return null;
            }
            return ['like', "%{$params['userPhone']}%", 'and'];
        };
        where($whereFind);
        if ($params['identity'] == 1) {//会员
            $where .= " and po.memberId > 0 ";
        } else if ($params['identity'] == 2) {//游客
            $where .= " and po.memberId = 0 ";
        }
        $whereFind = rtrim($whereFind, 'and ');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = $where;
        } else {
            $whereInfo = $where . ' and ' . $whereFind;
        }
        $field = "po.*,u.username as user_username,u.phone as user_phone,us.userName,us.userPhone,og.promotion_type ";
        $sql = "select {$field} from __PREFIX__pos_orders po
        left join __PREFIX__pos_orders_goods og on og.orderid=po.id
        left join __PREFIX__user u on po.userId=u.id
         left join __PREFIX__users us on po.memberId = us.userId ";
        $sql .= $whereInfo;
        $sql .= " group by po.id ";
        $sql .= " order by po.id desc ";
        $result = $this->query($sql);
        $order_amount_total = array_sum(array_column($result, 'realpayment'));
        $order_cash_amount = array_sum(array_column($result, 'cash'));
        $order_cash_wechat = array_sum(array_column($result, 'wechat'));
        $order_cash_alipay = array_sum(array_column($result, 'alipay'));
        $order_cash_unionpay = array_sum(array_column($result, 'unionpay'));
        $data = array(
            'order_count_total' => (int)count($result),
            'order_amount_total' => formatAmount($order_amount_total),
            'order_cash_amount' => formatAmount($order_cash_amount),
            'order_cash_wechat' => formatAmount($order_cash_wechat),
            'order_cash_alipay' => formatAmount($order_cash_alipay),
            'order_cash_unionpay' => formatAmount($order_cash_unionpay),
        );
        return $data;
    }

    /**
     * 收银订单日志
     * @param int $pos_order_id 收银订单id
     * @return array
     * */
    public function getPosOrdersLog(int $pos_order_id)
    {
        $table_action_log_tab = M('table_action_log');
        $where = array(
            'tableName' => 'wst_pos_orders',
            'dataId' => $pos_order_id,
        );
        $field = 'logId,actionUserName,fieldName,fieldValue,remark,createTime';
        $data = (array)$table_action_log_tab->where($where)->field($field)->order('logId asc')->select();
        foreach ($data as &$item) {
            $item['pay_status_name'] = '未支付';
            if ($item['fieldName'] == 'state' && $item['fieldValue'] == 3) {
                $item['pay_status_name'] = '已支付';
            }
            if ($item['fieldName'] == 'isRefund' && $item['fieldValue'] > 0) {
                $item['pay_status_name'] = '已支付';
            }
            if ($item['fieldName'] == 'exchangeStatus' && $item['fieldValue'] > 0) {
                $item['pay_status_name'] = '已支付';
            }
            unset($item['fieldName']);
            unset($item['fieldValue']);
        }
        unset($item);
        return $data;
    }

    /**
     * 获取退换商品明细-用于退货/换货
     * @param pos_order_id 收银订单id
     * @return array
     * */
    public function getReturnAndExchangeGoods(int $pos_order_id)
    {
        $pos_orders_goods_tab = M('pos_orders_goods');
        $where = array(
            'orderid' => $pos_order_id,
            'state' => 1
        );
        $field = 'id,goodsName,goodsSn,number,originalPrice,favorablePrice,presentPrice,refundNum,exchangeNum,isRefund,exchangeStatus,SuppPriceDiff,packing_factor';
        $data = $pos_orders_goods_tab->where($where)->field($field)->select();
        foreach ($data as &$item) {
            $item['isRefund'] = (int)$item['isRefund'];
            $item['exchangeStatus'] = (int)$item['exchangeStatus'];
            $item['exchangeStatus'] = (int)$item['exchangeStatus'];
            $item['number'] = (int)$item['number'];
            $item['refundNum'] = (int)$item['refundNum'];
            $item['exchangeNum'] = (int)$item['exchangeNum'];
            $item['can_refund_num'] = ($item['number'] - $item['refundNum'] - $item['exchangeNum']);//可换货数量
            $item['can_exchange_num'] = ($item['number'] - $item['refundNum'] - $item['exchangeNum']);
            //可退货数量
        }
        unset($item);
        return $data;
    }

    /**
     * 提交退货
     * @param array login_user_info 登陆者信息
     * @param array return_params<p>
     * int id 收银订单商品关联id
     * int current_return_num 本次退货数量
     * </p>
     * @return array
     * */
    public function returnGoodsAction(array $login_user_info, array $return_params)
    {
        $response = LogicResponse::getInstance();
        $pos_service_module = new PosServiceModule();
        $goods_service_module = new GoodsServiceModule();
        $log_service_module = new LogServiceModule();
        $pos_orders_goods_id = (int)$return_params[0]['id'];
        $goods_info_result = $pos_service_module->getPosOrdersGoodsInfo($pos_orders_goods_id);
        if ($goods_info_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('订单不存在')->toArray();
        }
        $action_user_id = $login_user_info['user_id'];
        $action_username = $login_user_info['user_username'];
        $order_id = $goods_info_result['data']['orderid'];
        $pos_order_result = $pos_service_module->getPosOrderInfoById($order_id);
        if ($pos_order_result['code'] != ExceptionCodeEnum::SUCCESS) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($pos_order_result['msg'])->toArray();
        }
        $pos_order_data = $pos_order_result['data'];
        if ($pos_order_data['state'] != 3) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('未结算订单不能退货')->toArray();
        }
        $total_money = 0;//退款金额
        $total_integral = 0;//退积分
        $return_goods = array();
        //换货单信息 PS:用于后面验证是否换过货
        $exchange_order_result = $pos_service_module->getExchangeOrdersInfoByOrderId($order_id);
        //退货单信息
        $giveback_result = $pos_service_module->getGivebackOrdersInfoByOrderId($order_id);
        foreach ($return_params as $item) {
            $orders_goods_result = $pos_service_module->getPosOrdersGoodsInfo($item['id']);
            if ($orders_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("参数有误，未匹配到id为{$item['id']}的信息")->toArray();
            }
            $orders_goods_data = $orders_goods_result['data'];
            $goods_name = $orders_goods_data['goodsName'];
            $number = (int)$orders_goods_data['number'];
            $current_return_num = (int)$item['current_return_num'];
            if ($orders_goods_data['isRefund'] == 3) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$goods_name}】退货完成，不能重复退货")->toArray();
            }
            if ($current_return_num > $number) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$goods_name}】退货数量不能大于购买时的数量")->toArray();
            }
            $already_return_num = 0;//已退货数量
            if ($giveback_result['code'] == ExceptionCodeEnum::SUCCESS) {
                //已有退货单记录
                $giveback_data = $giveback_result['data'];
                $back_id = $giveback_data['backId'];
                $back_goods_result = $pos_service_module->getGivebackGoodsInfo($back_id, $orders_goods_data['goodsId'], $orders_goods_data['skuId']);
                if ($giveback_result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $back_goods_data = $back_goods_result['data'];
                    $already_return_num = $back_goods_data['number'];
                    $total_return_num = bc_math($already_return_num, $current_return_num, 'bcadd', 0);
                    if ($total_return_num > $orders_goods_data['number']) {
                        $surplus_return_num = bc_math($orders_goods_data['number'], $already_return_num, 'bcsub', 0);//剩余可退数量
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$goods_name}】剩余可退数量为{$surplus_return_num}")->toArray();
                    }
                }
            }
            if ($exchange_order_result['code'] == ExceptionCodeEnum::SUCCESS) {
                //已有换货单记录
                $exchange_order_data = $exchange_order_result['data'];
                $exchange_id = $exchange_order_data['exchangeId'];
                $goods_relation_id = $orders_goods_data['id'];//订单商品表关联id
                $exchange_goods_result = $pos_service_module->getExchangeGoodsInfo($exchange_id, $goods_relation_id);
                if ($exchange_goods_result['code'] == ExceptionCodeEnum::SUCCESS) {
                    $exchange_goods_data = $exchange_goods_result['data'];
                    $already_exchange_num = $exchange_goods_data['orderGoodsExchangeNumber'];
                    $total_exchange_num = bc_math($already_exchange_num, $current_return_num, 'bcadd', 0);
                    $total_exchange_num += (float)$already_return_num;
                    if ($total_exchange_num > $orders_goods_data['number']) {
                        $surplus_return_num = bc_math($orders_goods_data['number'], $already_return_num, 'bcsub', 0);//剩余可退数量
                        $surplus_return_num = bc_math($surplus_return_num, $already_exchange_num, 'bcsub', 0);
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("商品【{$goods_name}】已换货数量为{$already_exchange_num}，剩余可退数量为{$surplus_return_num}")->toArray();
                    }
                }
            }

            $total_money += bc_math($orders_goods_data['presentPrice'], $current_return_num, 'bcmul', 2);
            if ($orders_goods_data['number'] <= 1) {
                $total_integral += $orders_goods_data['integral'];
                $integral = $orders_goods_data['integral'];
            } else {
                $single_integral = bc_math($orders_goods_data['integral'], $orders_goods_data['number'], 'bcdiv', 2);
                $integral = bc_math($single_integral, $item['current_return_num'], 'bcmul', 2);
                $total_integral += $integral;
            }
            $goodsInfo = $orders_goods_data;
            $goodsInfo['current_return_num'] = $item['current_return_num'];
            $goodsInfo['integral'] = $integral;
            $goodsInfo['weight'] = 0;
            if ($orders_goods_data['SuppPriceDiff'] == 1) {
                $goodsInfo['weight'] = bc_math($orders_goods_data['packing_factor'], $current_return_num, 'bcmul', 2);
            }
            $return_goods[] = $goodsInfo;
        }
        $model = M();
        $model->startTrans();
        if ($giveback_result['code'] != ExceptionCodeEnum::SUCCESS) {
            //创建单号
            $bill_result = $pos_service_module->getPosBillNo($model);
            if ($bill_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($bill_result['msg'])->toArray();
            }
            $bill_no = $bill_result['data']['bill_no'];
            //新增退货单
            $give_data = array();
            $give_data['orderId'] = $order_id;
            $give_data['billNo'] = $bill_no;
            $give_data['state'] = 1;//退款状态(1:待退款 2：部分退款 3：已退款)
            $give_data['pay'] = 1;//退款方式(1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付)
            $give_data['actionUserId'] = $action_user_id;
            $give_data['realpayment'] = $total_money;
            $insert_result = $pos_service_module->addGivebackOrders($give_data, $model);
            if ($insert_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('退货单创建失败')->toArray();
            }
            $backId = $insert_result['data']['backId'];
        } else {
            //编辑退货单
            $backId = $giveback_result['data']['backId'];
        }
        //退货商品明细
        $return_score = 0;//返还用户积分
        foreach ($return_goods as $item) {
            $item['integral'] = (int)$item['integral'];
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $goods_name = $item['goodsName'];
            $giveback_goods_result = $pos_service_module->getGivebackGoodsInfo($backId, $goods_id, $sku_id);
            if ($giveback_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $field_value = 1;
                //新增
                $save = array(
                    'backId' => $backId,
                    'orderId' => $order_id,
                    'goodsId' => $goods_id,
                    'goodsName' => $goods_name,
                    'goodsSn' => $item['goodsSn'],
                    'skuId' => $sku_id,
                    'presentPrice' => $item['presentPrice'],
                    'number' => $item['current_return_num'],
                    'subtotal' => bc_math($item['presentPrice'], $item['current_return_num'], 'bcmul', 2),
                    'state' => 1,
                    'weight' => $item['weight'],//收银暂无称重,先以包装系数为准
                    'integral' => $item['integral'],
                );
                $save_result = $pos_service_module->addGivebackOrdersGoods($save, $model);
            } else {
                //编辑
                $giveback_goods_data = $giveback_goods_result['data'];
                $save = array(
                    'number' => bc_math($giveback_goods_data['number'], $item['current_return_num'], 'bcadd', 0),
                );
                $single_integral = bc_math($item['integral'], $item['number'], 'bcdiv', 2);
                $save_integral = bc_math($single_integral, $item['number'], 'bcmul', 2);
                if ($save['number'] >= $item['number']) {
                    $save_integral = $item['integral'];
                }
                $save['integral'] = $save_integral;
                $save_result = $pos_service_module->updateGivebackGoodsInfo($giveback_goods_data['id'], $save, $model);
            }
            if ($save_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('退货单明细更新失败')->toArray();
            }
            //后加退货记录-start 兼容改版之后的页面
            $goods_log = array(
                'orderId' => $order_id,
                'relation_id' => $item['id'],
                'number' => $item['current_return_num'],
                'presentPriceTotal' => bc_math($item['current_return_num'], $item['presentPrice'], 'bcmul', 2),
                'subtotal' => bc_math($item['current_return_num'], $item['presentPrice'], 'bcmul', 2),
                'weight' => $item['weight'],
                'integral' => $item['integral'],
                'action_user_id' => $login_user_info['user_id'],
                'action_user_name' => $login_user_info['user_username'],
            );
            $back_log_result = $pos_service_module->addGivebackGoodsLog($goods_log, $model);
            if ($back_log_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('退货记录失败')->toArray();
            }
            //后加退货记录-end
            //返还商品库存
            if ($item['SuppPriceDiff'] == 1) {
                $stock = (float)bc_math($item['packing_factor'], $item['current_return_num'], 'bcmul', 2);
                $stock = bc_math($stock, 1000, 'bcdiv', 2);
            } else {
                $stock = $item['current_return_num'];
            }
            $return_stock_result = $goods_service_module->returnGoodsStock($goods_id, $sku_id, $stock, 1, 2, $model);
            if ($return_stock_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($return_stock_result['msg'])->toArray();
            }
            //添加操作日志
            $logParams = [
                'tableName' => 'wst_pos_orders',
                'dataId' => $order_id,
                'actionUserId' => $action_user_id,
                'actionUserName' => $action_username,
                'fieldName' => 'isRefund',
                'fieldValue' => $field_value,
                'remark' => "商品({$goods_name})成功退货{$current_return_num}份",
            ];
            $log_service_module->addTableActionLog($logParams, $model);
            $return_score += $save['integral'];
        }
        //返积分
        if ($return_score > 0 && $pos_order_data['memberId'] > 0) {
            $users_service_module = new UsersServiceModule();
            $return_score_result = $users_service_module->return_users_score($pos_order_data['memberId'], $return_score, $model);
            if ($return_score_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('积分返还失败')->toArray();
            }
        }
        //更新退货单状态
        $update_res = $pos_service_module->updateGivebackOrderState($backId, $model);
        if ($update_res['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('退货单更新失败')->toArray();
        }
        $pos_report_module = new PosReportModule();
        //上报报表数据-start
        $report_res = $pos_report_module->reportPosReport($backId, $return_params, 4, $model);//上报退货单报表
        if (!$report_res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('上传退货报表数据失败')->toArray();
        }
        //上报报表数据-end
        $model->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->toArray();
    }

    /**
     * 退货记录统计
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function countReturnGoodsLog(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $where = " o.shopId={$shop_id} and o.state=3 ";
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO,og.goodsName,og.goodsSn,og.presentPrice";
        $sql = "select {$field} from __PREFIX__pos_giveback_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                left join __PREFIX__pos_orders_goods og on og.id=log.relation_id
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $result = $this->query($sql);
        $return_order_num = count(array_unique(array_column($result, 'orderId')));//退货单数
        $return_order_amount = array_sum(array_column($result, 'subtotal'));//退货金额
        $return_goods_num = count($result);//退货商品数量
        $result = array(
            'return_order_num' => (int)$return_order_num,
            'return_order_amount' => $return_order_amount,
            'return_goods_num' => (int)$return_goods_num,
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 获取退货记录列表
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function getReturnGoodsLogList(array $params)
    {
        $response = LogicResponse::getInstance();
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $shop_id = $params['shop_id'];
        $where = " o.shopId={$shop_id} and o.state=3 ";
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO,og.goodsName,og.goodsSn,og.presentPrice";
        $sql = "select {$field} from __PREFIX__pos_giveback_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                left join __PREFIX__pos_orders_goods og on og.id=log.relation_id
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                $item['presentPrice_total'] = bc_math($item['presentPrice'], $item['number'], 'bcmul', 2);
            }
            unset($item);
            $data['root'] = $list;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 换货记录统计
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function countExchangeGoodsLog(array $params)
    {
        $response = LogicResponse::getInstance();
        $shop_id = $params['shop_id'];
        $where = " o.shopId={$shop_id} and o.state=3 ";
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO";
        $sql = "select {$field} from __PREFIX__pos_exchange_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $exchange_log_list = $this->query($sql);
        $exchange_order_num = count(array_unique(array_column($exchange_log_list, 'orderId')));
        $return_goods_num = array_sum(array_column($exchange_log_list, 'return_num'));
        $exchange_goods_num = array_sum(array_column($exchange_log_list, 'exchange_num'));
        $due_amount = 0;//补商家的
        $negative_amount = 0;//补用户的
        foreach ($exchange_log_list as $item) {
            if ($item['diff_type'] == 1) {
                $negative_amount += $item['diff_amount'];
            } elseif ($item['diff_type'] == 2) {
                $due_amount += $item['diff_amount'];
            }
        }
        $diff_amount = $due_amount - $negative_amount;
        if ($diff_amount < 0) {
            $diff_amount_symbol = '-';
        } else {
            $diff_amount_symbol = '+';
        }
        $result = array(
            'exchange_order_num' => (int)$exchange_order_num,//换货单数
            'return_goods_num' => (float)$return_goods_num,//退回商品数
            'exchange_goods_num' => (int)$exchange_goods_num,//换出商品数
            'diff_amount_symbol' => $diff_amount_symbol,//顾客补差价金额正负标志
            'diff_amount' => formatAmount(abs($diff_amount)),//顾客补差价金额
        );
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($result)->toArray();
    }

    /**
     * 获取换货记录列表
     * @param array $params <p>
     * int shop_id
     * string action_user_name 操作人名称
     * string bill_no 订单号
     * datetime startDate 时间区间-开始时间
     * datetime endDate 时间区间-结束时间
     * </p>
     * @return array
     * */
    public function getExchangeGoodsLogList(array $params)
    {
        $response = LogicResponse::getInstance();
        $page = (int)$params['page'];
        $pageSize = (int)$params['pageSize'];
        $shop_id = $params['shop_id'];
        $where = " o.shopId={$shop_id} and o.state=3 ";
        $whereFind = array();
        $whereFind['log.createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        $whereFind['o.orderNO'] = function () use ($params) {
            if (empty($params['bill_no'])) {
                return null;
            }
            return ['like', "%{$params['bill_no']}%", 'and'];
        };
        $whereFind['log.action_user_name'] = function () use ($params) {
            if (empty($params['action_user_name'])) {
                return null;
            }
            return ['like', "%{$params['action_user_name']}%", 'and'];
        };
        where($whereFind);
        $whereFind = rtrim($whereFind, ' and');
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = "log.*,o.orderNO";
        $sql = "select {$field} from __PREFIX__pos_exchange_goods_log log
                left join __PREFIX__pos_orders o on o.id=log.orderId
                ";
        $sql .= " where {$whereInfo} order by log.log_id desc ";
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data)) {
            $list = $data['root'];
            foreach ($list as &$item) {
                if ($item['diff_amount'] == 0) {
                    $item['diff_amount'] = '￥0.00';
                } elseif ($item['diff_amount'] > 0) {
                    if ($item['diff_type'] == 1) {
                        $item['diff_amount'] = '-￥' . $item['diff_amount'];
                    } elseif ($item['diff_type'] == 2) {
                        $item['diff_amount'] = '￥' . $item['diff_amount'];
                    }
                }
            }
            unset($item);
            $data['root'] = $list;
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
    }

    /**
     * 提交换货
     * @param array $login_user_info
     * @param array $exchange_params
     * */
    public function exchangeGoodsAction(array $login_user_info, array $exchange_params)
    {
        /*$exchange_params = array(
                    'primary_params' => array(//退还商品信息
                        array(
                            'id' => 1,
                            'primary_num' => 2
                        )
                    ),
                    'present_params' => array(//换货商品信息
                        array(
                            'goodsId' => 309,
                            'present_num' => 2
                        )
                    )
                );*/
        $response = LogicResponse::getInstance();
        $primary_params = $exchange_params['primary_params'];//退回商品数据
        $present_params = $exchange_params['present_params'];//换出商品数据
        $pos_service_module = new PosServiceModule();
        $goods_service_module = new GoodsServiceModule();
        $log_service_module = new LogServiceModule();
        $primary_goods = array();//退回商品数据
        $order_id = 0;
        $return_num = 0;//退回数量
        $return_amount = 0;//退回金额
        foreach ($primary_params as &$item) {
            $orders_goods_result = $pos_service_module->getPosOrdersGoodsInfo($item['id']);
            if ($orders_goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('请检查退回商品数据是否正确')->toArray();
            }
            $orders_goods_data = $orders_goods_result['data'];
            $order_id = $orders_goods_data['orderid'];
            $goods_name = $orders_goods_data['goodsName'];
            if ($orders_goods_data['isRefund'] == 3) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_name . '已退货完成的商品不能换货')->toArray();
            }
            if ($orders_goods_data['exchangeStatus'] == 3) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_name . '已换货完成的商品不能换货')->toArray();
            }
            $primary_num = $item['primary_num'];//当前退回数量
            $can_primary_num = (int)$orders_goods_data['number'];//目前可以退回的数量
            if ($orders_goods_data['refundNum'] > 0) {
                $can_primary_num -= (int)$orders_goods_data['refundNum'];
            }
            if ($orders_goods_data['exchangeNum'] > 0) {
                $can_primary_num -= (int)$orders_goods_data['exchangeNum'];
            }
            if ($primary_num > $can_primary_num) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_name . "本次最多可退回{$can_primary_num}份")->toArray();
            }
            $orders_goods_data['primary_num'] = $item['primary_num'];
            $primary_goods[] = $orders_goods_data;
            $return_num += $orders_goods_data['primary_num'];
            $return_amount += (float)bc_math($orders_goods_data['presentPrice'], $orders_goods_data['primary_num'], 'bcmul', 2);
        }
        unset($item);
        $present_goods = array();//换出商品数据
        $exchange_num = 0;//换出数量
        $exchange_amount = 0;//换出金额
        foreach ($present_params as &$item) {
            $goods_result = $goods_service_module->getGoodsInfoById($item['goodsId']);
            if ($goods_result['code'] != ExceptionCodeEnum::SUCCESS) {
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('换货商品信息有误')->toArray();
            }
            $goods_data = $goods_result['data'];
            $goods_data['skuId'] = 0;
            $goods_data['skuSpecAttr'] = '';
            $weightG = $goods_data['weightG'];
            if ((int)$item['skuId'] > 0) {
                $goods_data['skuId'] = $item['skuId'];
                $system_result = $goods_service_module->getSkuSystemInfoById($item['skuId']);
                if ($system_result['code'] != ExceptionCodeEnum::SUCCESS) {
                    return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("skuId:{$item['skuId']}暂无相关数据")->toArray();
                }
                $system_data = $system_result['data'];
                $goods_data['shopPrice'] = $system_data['skuShopPrice'];
                if ($goods_data['SuppPriceDiff'] == 1) {
                    //称重
                    $exchange_stock = bc_math($weightG, $item['present_num'], 'bcmul', 2) / 1000;
                    if ($exchange_stock > $system_data['skuGoodsStock']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("库存不足")->toArray();
                    }
                } else {
                    //非称重
                    if ($item['present_num'] > $system_data['skuGoodsStock']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("库存不足")->toArray();
                    }
                }
            } else {
                if ($goods_data['SuppPriceDiff'] == 1) {
                    //称重
                    $exchange_stock = bc_math($weightG, $item['present_num'], 'bcmul', 2) / 1000;
                    if ($exchange_stock > $goods_data['goodsStock']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("库存不足")->toArray();
                    }
                } else {
                    //非称重
                    if ($item['present_num'] > $goods_data['goodsStock']) {
                        return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg("库存不足")->toArray();
                    }
                }
            }
            //getGoodsSku($goods_data,)
            $shop_price = $goods_data['shopPrice'];
            $goods_data['present_num'] = $item['present_num'];
            $present_goods[] = $goods_data;
            $exchange_amount += bc_math($shop_price, $item['present_num'], 'bcmul', 2);
            $exchange_num += $item['present_num'];
        }
        unset($item);
        $model = M();
        $model->startTrans();
        if ($return_amount > $exchange_amount) {
            //补用户
            $diff_type = 1;
            $diff_amount = bc_math($return_amount, $exchange_amount, 'bcsub', 2);
        } else {
            //补商家
            $diff_type = 2;
            $diff_amount = bc_math($exchange_amount, $return_amount, 'bcsub', 2);
        }
        $log_params = array(
            'orderId' => $order_id,
            'exchange_num' => $exchange_num,
            'return_num' => $return_num,
            'diff_type' => $diff_type,
            'diff_amount' => abs($diff_amount),
            'action_user_id' => $login_user_info['user_id'],
            'action_user_name' => $login_user_info['user_username'],
        );
        $log_result = $pos_service_module->addExchangeGoodsLog($log_params, $model);
        if ($log_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('换货商品信息有误')->toArray();
        }
        $exchange_orders_id = $log_result['data']['log_id'];
        //添加退回记录
        foreach ($primary_goods as $item) {
            $goods_name = $item['goodsName'];
            $relation_id = $item['id'];
            $relation_result = $pos_service_module->getLogRelationInfoByRelationId($relation_id);
            if ($relation_result['code'] != ExceptionCodeEnum::SUCCESS) {
                //添加
                $save = array(
                    'orderId' => $order_id,
                    'type' => 1,
                    'relation_id' => $relation_id,
                    'skuSpecAttr' => $item['skuSpecAttr'],
                    'return_num_total' => $item['primary_num'],
                    'return_present_price' => $item['presentPrice'],
                    'return_present_total' => bc_math($item['primary_num'], $item['presentPrice'], 'bcmul', '2'),
                    'return_subtotal' => bc_math($item['primary_num'], $item['presentPrice'], 'bcmul', '2'),
                );
                $goods_relation_result = $pos_service_module->addExchangeGoodsRelationLog($save, $model);
            } else {
                //修改
                $relation_data = $relation_result['data'];
                $id = $relation_data['id'];
                $amount = bc_math($item['primary_num'], $item['presentPrice'], 'bcmul', '2');
                $subtotal = bc_math($item['primary_num'], $item['presentPrice'], 'bcmul', '2');
                $save = array(
                    'return_num_total' => bc_math($relation_data['return_num_total'], $item['primary_num'], 'bcadd', 0),
                    'return_present_total' => bc_math($relation_data['return_present_total'], $amount, 'bcadd', 2),
                    'return_subtotal' => bc_math($subtotal, $relation_data['return_subtotal'], 'bcadd', 2),
                );
                $goods_relation_result = $pos_service_module->updateExchangeGoodsRelationLog($id, $save, $model);
            }
            if ($goods_relation_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_relation_result['msg'])->toArray();
            }
            if ($item['SuppPriceDiff'] == 1) {
                //称重商品
                $stock = bc_math($item['packing_factor'], $item['primary_num'], 'bcmul', 2);
                $stock = $stock / 1000;
            } else {
                //非称重
                $stock = $item['primary_num'];
            }
            //返还商品库存
            $return_stock_result = $goods_service_module->returnGoodsStock($item['goodsId'], $item['skuId'], $stock, 1, 2, $model);
            if ($return_stock_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_name . $goods_relation_result['msg'])->toArray();
            }
            //添加操作日志
            $logParams = [
                'tableName' => 'wst_pos_orders',
                'dataId' => $order_id,
                'actionUserId' => $login_user_info['user_id'],
                'actionUserName' => $login_user_info['user_username'],
                'fieldName' => 'exchangeStatus',
                'fieldValue' => 3,
                'remark' => "商品({$goods_name})成功退回{$item['primary_num']}份",
            ];
            $log_service_module->addTableActionLog($logParams, $model);
        }
        //添加换出记录
        foreach ($present_goods as $item) {
            $goods_name = $item['goodsName'];
            $where = array(
                'orderId' => $order_id,
                'goodsId' => $item['goodsId'],
                'skuId' => $item['skuId'],
            );
            $relation_result = $pos_service_module->getLogRelationInfoByParams($where);
            if ($relation_result['code'] != ExceptionCodeEnum::SUCCESS) {
                //添加换出记录
                $save = array(
                    'orderId' => $order_id,
                    'type' => 2,
                    'goodsId' => $item['goodsId'],
                    'skuId' => $item['skuId'],
                    'skuSpecAttr' => $item['skuSpecAttr'],
                    'goodsSn' => $item['goodsSn'],
                    'present_price' => $item['shopPrice'],
                    'exchange_num' => $item['present_num'],
                    'exchange_subtotal' => bc_math($item['shopPrice'], $item['present_num'], 'bcmul', 2),
                );
                $relation_save_result = $pos_service_module->addExchangeGoodsRelationLog($save, $model);
            } else {
                //修改换出记录
                $relation_data = $relation_result['data'];
                $id = $relation_data['id'];
                $exchange_subtotal = bc_math($item['shopPrice'], $item['present_num'], 'bcmul', 2);
                $save = array(
                    'exchange_num' => bc_math($relation_data['exchange_num'], $item['present_num'], 'bcadd', 0),
                    'exchange_subtotal' => bc_math($relation_data['exchange_subtotal'], $exchange_subtotal, 'bcadd', 0),
                );
                $relation_save_result = $pos_service_module->updateExchangeGoodsRelationLog($id, $save, $model);
            }
            if ($relation_save_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($relation_result['msg'])->toArray();
            }
            //扣除商品库存
            if ($item['SuppPriceDiff'] == 1) {
                //称重商品
                $stock = bc_math($item['weightG'], $item['present_num'], 'bcmul', 2);
                $stock = $stock / 1000;
            } else {
                //非称重
                $stock = $item['present_num'];
            }
            $deduction_stock_result = $goods_service_module->deductionGoodsStock($item['goodsId'], $item['skuId'], $stock, 1, 2, $model);
            if ($deduction_stock_result['code'] != ExceptionCodeEnum::SUCCESS) {
                $model->rollback();
                return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg($goods_name . $relation_result['msg'])->toArray();
            }
            //添加操作日志
            $logParams = [
                'tableName' => 'wst_pos_orders',
                'dataId' => $order_id,
                'actionUserId' => $login_user_info['user_id'],
                'actionUserName' => $login_user_info['user_username'],
                'fieldName' => 'exchangeStatus',
                'fieldValue' => 3,
                'remark' => "商品({$goods_name})成功换出{$item['present_num']}份",
            ];
            $log_service_module->addTableActionLog($logParams, $model);
        }
        //更新订单商品相关的换货状态
        $exchange_goods_state_result = $pos_service_module->updateExchangeOrdersGoodsState($order_id, $model);
        if ($exchange_goods_state_result['code'] != ExceptionCodeEnum::SUCCESS) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('换货失败')->toArray();
        }
        //上报换货报表数据-start
        $pos_report_module = new PosReportModule();
        $report_res = $pos_report_module->reportPosReport($exchange_orders_id, $exchange_params, 5, $model);//上报换货报表数据
        if (!$report_res) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('换货失败')->toArray();
        }
        //上报换货报表数据-end
        $model->commit();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->toArray();
    }

    #############################收银报表-start###################################

    /**
     * 报表-营业数据统计
     * @param int $shop_id 门店id
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export (0:不导出 1:导出)
     * @return array
     * */
    public function businessStatisticsReport(int $shop_id, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $pos_report_model = new PosReportModel();
        $field = 'report_id,report_date,sales_order_num,return_order_num,exchange_order_num,recharge_num,buy_setmeal_num,sales_order_money,return_order_money,exchange_order_money,recharge_wxpay_money,recharge_alipay_money,recharge_cash_money,buy_setmeal_wxpay_money,buy_setmeal_alipay_money,buy_setmeal_cash_money';
        $where = array(
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'shop_id' => $shop_id,
            'is_delete' => 0
        );
        $where['_string'] = ' (sales_order_num > 0 or return_order_num > 0 or exchange_order_num > 0) ';//只统计订单,退货单,换货单
        $sql = $pos_report_model
            ->where($where)
            ->field($field)
            ->order('report_date asc')
            ->buildSql();//报表日期数据
        if ($export != 1) {
            $report_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $report_data = array();
            $report_data['root'] = $this->query($sql);
        }
        $report_list = $report_data['root'];
        $sales_order_num = 0;//销售订单总数
        $return_order_num = 0;//退货单总数
        $exchange_order_num = 0;//换货单总数
        //$recharge_num = 0;//充值总数
        //$buy_setmeal_num = 0;//购买套餐总数
        $sales_order_money = 0;//销售订单总金额
        $return_order_money = 0;//退货单总金额
        $exchange_order_money = 0;//换货单总金额
        //$recharge_money = 0;//充值总金额 微信充值金额+支付宝充值金额+现金充值金额
        //$buy_setmeal_money = 0;//购买套餐总金额 微信购买套餐金额+支付宝购买套餐金额+现金购买套餐金额
        foreach ($report_list as &$item) {
            $sales_order_num += (int)$item['sales_order_num'];
            $return_order_num += (int)$item['return_order_num'];
            $exchange_order_num += (int)$item['exchange_order_num'];
            //$recharge_num += (int)$item['recharge_num'];
            //$buy_setmeal_num += (int)$item['buy_setmeal_num'];
            $sales_order_money += (float)$item['sales_order_money'];
            $return_order_money += (float)$item['return_order_money'];
            $exchange_order_money += (float)$item['exchange_order_money'];
            //$current_recharge_money = (float)((float)$item['buy_setmeal_wxpay_money'] + (float)$item['buy_setmeal_alipay_money'] + (float)$item['buy_setmeal_cash_money']);//当期充值总金额
            //$buy_setmeal_money += $current_recharge_money;
            //$current_buy_setmeal_money = (float)((float)$item['recharge_wxpay_money'] + (float)$item['recharge_alipay_money'] + (float)$item['recharge_cash_money']);//档期购买套餐总金额
            //$recharge_money += $current_buy_setmeal_money;
            //$item['recharge_money'] = formatAmount($current_recharge_money);
            //$item['buy_setmeal_money'] = formatAmount($current_buy_setmeal_money);
            $current_actual_money = ((float)$item['sales_order_money'] + (float)$item['exchange_order_money']) - (float)$item['return_order_money'];//当前实际金额=(销售订单金额+换货补差价金额)-退货金额
            $item['actual_money'] = formatAmount($current_actual_money);
        }
        unset($item);
        //PS:换货金额有正负之分,正数代表用户补给商家的款,负数代表商家补给用户的款
        //实际金额=(销售订单金额+换货补差价金额+充值金额+购买套餐金额)-退货金额
        $actual_money = ($sales_order_money + $exchange_order_money) - $return_order_money;//实际金额=(销售订单金额+换货补差价金额)-退货金额
        $report_data['root'] = $report_list;
        $report_data['sum'] = array(//统计字段
            'sales_order_num' => formatAmount($sales_order_num, 0),//销售订单总数
            'return_order_num' => formatAmount($return_order_num, 0),//退货单总数
            'exchange_order_num' => formatAmount($exchange_order_num, 0),//换货单总数
            //'recharge_num' => formatAmount($recharge_num, 0),//充值总数
            //'buy_setmeal_num' => formatAmount($buy_setmeal_num, 0),//购买套餐总数
            'sales_order_money' => formatAmount($sales_order_money),//销售订单总金额
            'return_order_money' => formatAmount($return_order_money),//退货单总金额
            'exchange_order_money' => formatAmount($exchange_order_money),//换货单总金额
            //'recharge_money' => formatAmount($recharge_money),//充值总金额 微信充值金额+支付宝充值金额+现金充值金额
            //'buy_setmeal_money' => formatAmount($buy_setmeal_money),//购买套餐总金额 微信购买套餐金额+支付宝购买套餐金额+现金购买套餐金额
            'actual_money' => formatAmount($actual_money),//实际金额
        );
        if ($export == 1) {//导出营业数据
            $this->exportBusinessStatistics($report_data);
        }
        return $report_data;
    }

    /**
     * 营业数据-导出
     * @param array $report_data 营业报表数据
     * */
    public function exportBusinessStatistics(array $report_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '营业数据报表';
        $excel_filename = '营业数据报表' . date('YmdHis');
        $sheet_title = array('日期', '订单总量', '退货单总量', '换货单总量', '订单总金额', '退货单总金额', '换货单补差价金额', '实际金额');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $list = $report_data['root'];
        if (!empty($list)) {
            $sum = $report_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'report_date' => '合计',
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'return_order_num' => $sum['return_order_num'],//退货单总数
                'exchange_order_num' => $sum['exchange_order_num'],//换货单总数
                'sales_order_money' => $sum['sales_order_money'],//订单总金额
                'return_order_money' => $sum['return_order_money'],//退货单总金额
                'exchange_order_money' => $sum['exchange_order_money'],//换货单总金额
                'actual_money' => $sum['actual_money'],//实际金额
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总数
                        break;
                    case 2:
                        $cellvalue = $detail['return_order_num'];//退货单总数
                        break;
                    case 3:
                        $cellvalue = $detail['exchange_order_num'];//换货单总数
                        break;
                    case 4:
                        $cellvalue = $detail['sales_order_money'];//订单总金额
                        break;
                    case 5:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 6:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价总金额
                        break;
                    case 7:
                        $cellvalue = $detail['actual_money'];//换货补差价总金额
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 商品销量统计报表
     * @param array $params <p>
     * int shop_id 门店id
     * date start_date 开始日期
     * date end_date 结束日期
     * int page 页码
     * int page_size 分页条数
     * int data_type 统计类型(1:按商品统计 2:按分类统计)
     * int model_type 模式(1:列表模式 2:图标模式)
     * int goods_cat_id1 商品商城一级分类id
     * int goods_cat_id2 商品商城二级分类id
     * int goods_cat_id3 商品商城三级分类id
     * string goods_keywords 商品名称或商品编码 PS:仅仅按商品统计的场景需要
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function goodsSaleReport(array $params)
    {
        if ($params['data_type'] == 1) {//按商品统计
            $response_data = $this->saleReportToGoods($params);
        } elseif ($params['data_type'] == 2) {//按分类统计
            $response_data = $this->saleReportToCat($params);
        }
        if ($params['export'] == 1) {//导出
            $this->exportgoodsSale($response_data, $params);
        }
        return $response_data;
    }

    /**
     * 导出商品销量统计报表
     * @param array $response_data 业务数据
     * @param array $attach 附加参数
     * */
    public function exportgoodsSale(array $response_data, $attach = array())
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $list = $response_data['root'];
        $data_type = $attach['data_type'];//统计类型(1:按商品统计 2:按分类统计)
        $title = '商品销量统计报表';
        $excel_filename = '商品销量统计报表' . date('YmdHis');
        if ($data_type == 1) {//按商品统计
//            $sheet_title = array('商品名称', '商品编码', '商品分类', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '换货-退回数量', '换货-换出数量', '换货-补差价金额', '实际金额');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
            $sheet_title = array('商品名称', '商品编码', '商品分类', '订单数量', '销售数量', '销售金额');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F');
        } else {//按分类统计
//            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '换货-退回数量', '换货-换出数量', '换货-补差价金额', '实际金额');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额');
            $letter = array('A', 'B', 'C', 'D');
        }
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            if ($data_type == 1) {//按商品统计
                $list[] = array(
                    'goodsName' => '合计',
                    'order_num' => $sum['sales_order_num'],//订单总量
                    'sale_num' => $sum['goods_sale_num'],//销售总量
                    'sale_money' => $sum['goods_sale_money'],//销售总金额
//                    'return_goods_num' => $sum['goods_return_num'],//退货总量
//                    'return_goods_money' => $sum['goods_return_money'],//退货总金额
//                    'exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回总量
//                    'exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出总量
//                    'exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货-补差价金额
//                    'actual_money' => $sum['actual_money'],//实际金额
                );
            } else {//按分类统计
                $list[] = array(
                    'goodsCatName3' => '合计',
                    'order_num' => $sum['sales_order_num'],//订单总量
                    'sale_num' => $sum['goods_sale_num'],//销售总量
                    'sale_money' => $sum['goods_sale_money'],//销售总金额
//                    'return_goods_num' => $sum['goods_return_num'],//退货总量
//                    'return_goods_money' => $sum['goods_return_money'],//退货总金额
//                    'exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回总量
//                    'exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出总量
//                    'exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货-补差价金额
//                    'actual_money' => $sum['actual_money'],//实际金额
                );
            }
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                if ($data_type == 1) {//按商品统计
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsName'];//商品名称
                            break;
                        case 1:
                            $cellvalue = $detail['goodsSn'];//商品编码
                            break;
                        case 2:
                            $cellvalue = $detail['goodsCatName3'];//商品分类
                            break;
                        case 3:
                            $cellvalue = $detail['order_num'];//订单数量
                            break;
                        case 4:
                            $cellvalue = $detail['sale_num'];//销售数量
                            break;
                        case 5:
                            $cellvalue = $detail['sale_money'];//销售金额
                            break;
//                        case 6:
//                            $cellvalue = $detail['return_goods_num'];//退货数量
//                            break;
//                        case 7:
//                            $cellvalue = $detail['return_goods_money'];//退货金额
//                            break;
//                        case 8:
//                            $cellvalue = $detail['exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['exchange_diff_money'];//换货-补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
                    }
                } else {//按分类统计
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsCatName3'];//分类名称
                            break;
                        case 1:
                            $cellvalue = $detail['order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['sale_money'];//销售金额
                            break;
//                        case 4:
//                            $cellvalue = $detail['return_goods_num'];//退货数量
//                            break;
//                        case 5:
//                            $cellvalue = $detail['return_goods_money'];//退货金额
//                            break;
//                        case 6:
//                            $cellvalue = $detail['exchange_input_num'];//换货-退回数量
//                            break;
//                        case 7:
//                            $cellvalue = $detail['exchange_out_num'];//换货-换出数量
//                            break;
//                        case 8:
//                            $cellvalue = $detail['exchange_diff_money'];//换货-补差价金额
//                            break;
//                        case 9:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 商品销量统计报表-按商品统计
     * @param array $params <p>
     *  int goods_cat_id1 商品商城一级分类id
     *  int goods_cat_id2 商品商城二级分类id
     *  int goods_cat_id3 商品商城三级分类id
     *  int model_type 模式(1:列表模式 2:图标模式)
     * </p>
     * @param array $report_list 报表日期列表
     * @return array
     * */
    public function saleReportToGoods(array $params)
    {
        $shop_id = $params['shop_id'];
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $goods_keywords = $params['goods_keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $model_type = $params['model_type'];
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $relation_where = " relation.shop_id={$shop_id} and relation.is_delete = 0 ";
        $relation_where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        //$relation_where .= " and relation.data_type IN(1,4,5) ";//商品销量,目前处理了订单,退货单,换货单
        $relation_where .= " and relation.data_type=1 ";//商品销量,目前处理了订单
        if (!empty($goods_cat_id1)) {
            $relation_where .= " and goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $relation_where .= " and goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $relation_where .= " and goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $relation_where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        $field = 'relation.id,relation.report_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_id,relation.goods_paid_price,goods_paid_price_total,relation.refund_money,relation.report_date,relation.is_return_goods,relation.return_goods_num,relation.goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price';
        $field .= ',goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $relation_list = (array)$report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods as goods on goods.goodsId=relation.goods_id")
            ->where($relation_where)
            ->field($field)
            ->select();
        if ($model_type == 1) {//列表模式
            $response_data = $this->saleReportToGoodsToList($relation_list, $params);
        } else {//图表模式
            $response_data = $this->saleReportToGoodsToChart($relation_list);
        }
        return $response_data;
    }

    /**
     * 商品销量统计报表-按商品统计-列表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToGoodsToList(array $relation_list, $attach = array())
    {
        $page = $attach['page'];
        $page_size = $attach['page_size'];
        $relation_id_arr = array_unique(array_column($relation_list, 'id'));
        $relation_id_str = implode(',', $relation_id_arr);
        $sales_order_id_arr = array_unique(array_column($relation_list, 'order_id'));
        $sales_order_num = count($sales_order_id_arr);//订单总量
        $goods_sale_num = 0;//商品销售数量
        $goods_sale_money = 0;//商品销售金额
        $goods_return_money = 0;//商品退货金额
        $goods_exchange_input_num = 0;//商品换货退回数量
        $goods_exchange_out_num = 0;//商品换货换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $actual_money = 0;//实际金额
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $goods_cat_module = new GoodsCatModule();
        $where = array(
            'id' => array('IN', $relation_id_str),
        );
        $field = 'goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $sql = $report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->group('goods_id')
            ->buildSql();
        if ($attach['export'] != 1) {
            $goods_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_data = array();
            $goods_data['root'] = $this->query($sql);
        }
        $goods_list = (array)$goods_data['root'];
        $goods_cat_id_arr = array();//商品商城分类id
        foreach ($goods_list as &$goods_detail) {
            $goods_cat_id_arr[] = $goods_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId3'];
            $curr_order_id_arr = array();//当前商品关联的订单id
            $curr_sale_num = 0;//当前商品销售数量
            $curr_sale_money = 0;//当前商品销售金额
            $curr_refund_money = 0;//当前商品退货金额
            $curr_return_goods_num = 0;//当前商品退货数量
            $curr_exchange_input_num = 0;//换货-当前商品退回商品数量
            $curr_exchange_out_num = 0;//换货-当前商品换出商品数量
            $curr_exchange_diff_money = 0;//换货-当前商品换货补差价金额 PS:存在正负值,正数用户补给商家,负数为商家补给用户
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goods_id'] != $goods_detail['goodsId']) {
                    continue;
                }
                $curr_order_id_arr[] = $relation_detail['order_id'];
                $curr_sale_num += (float)$relation_detail['goods_num'];
                $curr_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_refund_money += (float)$relation_detail['refund_money'];
                $curr_return_goods_num += (float)$relation_detail['return_goods_num'];
                $curr_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
            }
            $curr_order_num = (int)count(array_unique($curr_order_id_arr));//当前商品订单数量
            $curr_actual_money = (float)(((float)$curr_sale_money - (float)$curr_refund_money) - (float)$curr_exchange_diff_money);//当前商品实际金额 = 销售金额-退回金额+补差价金额
            $goods_detail['order_num'] = formatAmount($curr_order_num, 0);
            $goods_detail['sale_num'] = formatAmount($curr_sale_num, 0);
            $goods_detail['sale_money'] = formatAmount($curr_sale_money);
            $goods_detail['return_goods_num'] = formatAmount($curr_return_goods_num, 0);
            $goods_detail['return_goods_money'] = formatAmount($curr_refund_money);
            $goods_detail['exchange_input_num'] = formatAmount($curr_exchange_input_num, 0);
            $goods_detail['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);
            $goods_detail['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);
            $goods_detail['actual_money'] = formatAmount($curr_actual_money);

            $goods_sale_num += $curr_sale_num;
            $goods_sale_money += $curr_sale_money;
            $goods_return_money += $curr_refund_money;
            $goods_exchange_input_num += $curr_exchange_input_num;
            $goods_exchange_out_num += $curr_exchange_out_num;
            $goods_exchange_diff_money += $curr_exchange_diff_money;
            $actual_money += $curr_actual_money;
        }
        unset($goods_detail);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_list as &$goods_detail) {//获取商品分类信息
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                    $goods_detail['goodsCatName1'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                    $goods_detail['goodsCatName2'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                    $goods_detail['goodsCatName3'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($goods_detail);
        $goods_data['root'] = $goods_list;
        $goods_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//订单总量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//商品销售数量
            'goods_sale_money' => formatAmount($goods_sale_money),//商品销售金额
            'goods_return_num' => formatAmount($goods_return_money, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//换货-商品退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//换货-商品换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-补差价金额
            'actual_money' => formatAmount($actual_money)//实际金额
        );
        return $goods_data;
    }

    /**
     * 商品销量统计报表-按商品统计-图标模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToGoodsToChart(array $relation_list)
    {
        $sale_goods_list = array();//订货商品列表
        $return_goods_list = array();//退货商品列表
        $exchange_goods_list = array();//换货商品数据
        $return_goods_money = 0;//商品退货总金额
        $sale_goods_money = 0;//商品订货总金额
        $sale_goods_num = 0;//订货商品总数量
        $return_goods_num = 0;//退货商品总数量
        $goods_exchange_out_num = 0;//换货-换出商品总数量
        $goods_exchange_input_num = 0;//换货-退回商品总数量
        $goods_exchange_diff_money = 0;//换货-换货补差价金额
        foreach ($relation_list as $relation_detail) {//拼接商品列表数据
            $goods_id = $relation_detail['goodsId'];
            $goods_paid_price = (float)$relation_detail['goods_paid_price'];//当前单商品实付金额
            $current_order_goods_num = (int)$relation_detail['goods_num'];//当前订单购买该商品的数量
            $current_order_return_goods_num = (int)$relation_detail['return_goods_num'];//当前订单已退货该商品的数量
            $curr_goods_exchange_out_num = (int)$relation_detail['exchange_goods_out_num'];//当前订单该商品换出数量
            $curr_goods_exchange_input_num = (int)$relation_detail['exchange_goods_input_num'];//当前订单该商品换出数量
            $curr_goods_exchange_diff_price = (float)$relation_detail['exchange_diff_price'];//当前订单该商品补差价金额
            $surplus_order_goods_num = (int)bc_math($current_order_goods_num, $current_order_return_goods_num, 'bcsub', 0);//当前订单该商品剩余订货数量
            $current_order_return_goods_money = (float)bc_math($goods_paid_price, $current_order_return_goods_num, 'bcmul', 2);//当前订单已退货商品的金额
            $surplus_order_goods_money = (float)bc_math($goods_paid_price, $surplus_order_goods_num, 'bcmul', 2);//当前订单该商品剩余订货金额
            $sale_goods_money += $surplus_order_goods_money;
            $return_goods_money += $current_order_return_goods_money;
            $sale_goods_num += $surplus_order_goods_num;
            $return_goods_num += $current_order_return_goods_num;
            $goods_exchange_out_num += $curr_goods_exchange_out_num;
            $goods_exchange_input_num += $curr_goods_exchange_input_num;
            $goods_exchange_diff_money += $curr_goods_exchange_diff_price;
            //订货商品
            if ($relation_detail['is_return_goods'] != 2) {//只处理未全部退货的商品
                if (!isset($sale_goods_list[$goods_id])) {
                    //新增
                    $sale_goods_list[$goods_id] = array(
                        'goodsId' => formatAmount($goods_id, 0),
                        'goodsName' => $relation_detail['goodsName'],
                        'sale_money' => formatAmount($surplus_order_goods_money),//当前商品订货总金额
                        'sale_num' => formatAmount($surplus_order_goods_num, 0),//当前商品订货总数量
                        'percent' => '',//比例 = 当前商品订货总金额/订货总金额*100
                    );
                } else {
                    //已存在直接更新商品的总订货价
                    $current_sale_money = bc_math($sale_goods_list[$goods_id]['sale_money'], $surplus_order_goods_money, 'bcadd', 2);
                    $sale_goods_list[$goods_id]['sale_money'] = formatAmount($current_sale_money);//当前商品订货总金额
                    $current_sale_num = bc_math($sale_goods_list[$goods_id]['sale_num'], $surplus_order_goods_num, 'bcadd', 2);
                    $sale_goods_list[$goods_id]['sale_num'] = formatAmount($current_sale_num, 0);//当前商品订货总数量
                }
            }
            //退货商品
            if ($relation_detail['is_return_goods'] != 0) {
                if (!isset($return_goods_list[$goods_id])) {
                    //新增
                    $return_goods_list[$goods_id] = array(
                        'goodsId' => $goods_id,
                        'goodsName' => $relation_detail['goodsName'],
                        'return_money' => formatAmount($current_order_return_goods_money),//当前商品退货总金额
                        'return_sum' => formatAmount($current_order_return_goods_num, 0),//当前商品退货总数量
                        'percent' => '',//比例 = 当前商品退货总金额/退货总金额*100
                    );
                } else {
                    //已存在直接更新商品的退货总金额
                    $current_return_money = bc_math($return_goods_list[$goods_id]['return_money'], $current_order_return_goods_money, 'bcadd', 2);
                    $return_goods_list[$goods_id]['return_money'] = formatAmount($current_return_money);//当前商品退货总金额
                    $current_return_sum = bc_math($return_goods_list[$goods_id]['return_num'], $current_order_return_goods_num, 'bcadd', 2);
                    $return_goods_list[$goods_id]['return_num'] = formatAmount($current_return_sum, 0);//当前商品订货总数量
                }
            }
            //换货-商品换出列表
            if ($relation_detail['is_exchange_goods'] != 0) {
                if (!isset($exchange_goods_list[$goods_id])) {
                    //新增
                    $exchange_goods_list[$goods_id] = array(
                        'goodsId' => $goods_id,
                        'goodsName' => $relation_detail['goodsName'],
                        'exchange_diff_money' => formatAmount($curr_goods_exchange_diff_price),//当前商品换货补差价金额
                        'exchange_out_num' => formatAmount($curr_goods_exchange_out_num, 0),//当前商品换出总数量
                        'percent' => '',//比例 = 当前商品换货补差价金额/换货补差价总金额*100
                    );
                } else {
                    //已存在直接更新商品的总订货价
                    $curr_exchange_diff_money = bc_math($exchange_goods_list[$goods_id]['exchange_diff_money'], $curr_goods_exchange_diff_price, 'bcadd', 2);
                    $exchange_goods_list[$goods_id]['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);//当前商品换货补差价金额
                    $curr_exchange_out_num = bc_math($exchange_goods_list[$goods_id]['exchange_out_num'], $curr_goods_exchange_out_num, 'bcadd', 2);
                    $exchange_goods_list[$goods_id]['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);//当前商品换货补价差数量
                }
            }
        }
        foreach ($sale_goods_list as &$goods_detail) {//处理销售商品金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['sale_money'], $sale_goods_money, 'bcdiv', 4) * 100) . '%';
        }
        unset($goods_detail);
        foreach ($return_goods_list as &$goods_detail) {//处理退货商品退货金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['return_money'], $return_goods_money, 'bcdiv', 4) * 100) . '%';;
        }
        unset($goods_detail);
        foreach ($exchange_goods_list as &$goods_detail) {//处理换货商品补差价金额比例
            $goods_detail['percent'] = ((float)bc_math($goods_detail['exchange_diff_money'], $goods_exchange_diff_money, 'bcdiv', 4) * 100) . '%';;
        }
        unset($goods_detail);
        $response_data = array(
            'sale_goods_list' => array_values($sale_goods_list),//订货商品列表
            'return_goods_list' => array_values($return_goods_list),//退货商品列表
            'exchange_goods_list' => array_values($exchange_goods_list),//退货商品列表
            'sale_goods_money' => formatAmount($sale_goods_money),//订货商品总金额
            'sale_goods_num' => formatAmount($sale_goods_num),//订货商品总数量
            'return_goods_num' => formatAmount($return_goods_num),//退货商品总数量
            'return_goods_money' => formatAmount($return_goods_money),//退货商品总金额
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num),//换货-商品换出总数量
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num),//换货-商品退回总数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-换货补差价金额
        );
        return $response_data;
    }

    /**
     * 商品销量统计报表-按分类统计
     * @param array $params <p>
     *  int goods_cat_id1 商品商城一级分类id
     *  int goods_cat_id2 商品商城二级分类id
     *  int goods_cat_id3 商品商城三级分类id
     *  int model_type 模式(1:列表模式 2:图标模式)
     * </p>
     * @param array $report_list 报表日期列表
     * @return array
     * */
    public function saleReportToCat(array $params)
    {
        $shop_id = $params['shop_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $model_type = $params['model_type'];
        $report_relation_model = new PosReportRelationModel();
        $table_prefix = $report_relation_model->tablePrefix;
        $relation_where = array();
        $relation_where['relation.shop_id'] = $shop_id;
        $relation_where['relation.report_date'] = array('between', array("{$start_date}", "{$end_date}"));
//        $relation_where['relation.data_type'] = array('IN', '1,4,5');//商品销量,目前仅仅处理和销售订单相关的数据
        $relation_where['relation.data_type'] = 1;//商品销量,目前仅仅处理和销售订单相关的数据
        $relation_where['relation.is_delete'] = 0;
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId1'] = $goods_cat_id1;
        }
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId2'] = $goods_cat_id2;
        }
        if (!empty($goods_cat_id1)) {
            $relation_where['relation.goodsCatId3'] = $goods_cat_id3;
        }
        $field = 'relation.id,relation.report_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_id,relation.goods_paid_price,goods_paid_price_total,relation.refund_money,relation.report_date,relation.is_return_goods,relation.return_goods_num,relation.goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.goods_cat_id1 as goodsCatId1,relation.goods_cat_id2 as goodsCatId2,relation.goods_cat_id3 as goodsCatId3';
        $relation_list = (array)$report_relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods_cats as cats on cats.catId=relation.goods_cat_id3")
            ->where($relation_where)
            ->field($field)
            ->select();
        if ($model_type == 1) {//列表模式
            $response_data = $this->saleReportToCatToList($relation_list, $params);
        } else {//图表模式
            $response_data = $this->saleReportToCatToChart($relation_list);
        }
        return $response_data;
    }

    /**
     * 商品销量统计报表-按分类统计-列表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $params
     * @return array
     * */
    public function saleReportToCatToList(array $relation_list, $params)
    {
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_id_arr = array_unique(array_column($relation_list, 'id'));
        $relation_id_str = implode(',', $relation_id_arr);
        $order_id_arr = array_unique(array_column($relation_list, 'order_id'));
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.id' => array('IN', $relation_id_str),
        );
        $field = 'relation.goods_cat_id1 as goodsCatId1,relation.goods_cat_id2 as goodsCatId2,relation.goods_cat_id3 as goodsCatId3';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods_cats cats on cats.catId=relation.goods_cat_id3")
            ->where($where)
            ->field($field)
            ->group('relation.goods_cat_id3')
            ->buildSql();
        if ($params['export'] != 1) {
            $goods_cat_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_cat_data = array();
            $goods_cat_data['root'] = $this->query($sql);
        }
        $goods_cat_list = (array)$goods_cat_data['root'];
        $goods_cat_id_arr = array();//商品商城分类id
        $sales_order_num = count($order_id_arr);//订单总量
        $goods_sale_num = 0;//商品销售总数量
        $goods_sale_money = 0;//商品销售总金额
        $goods_return_num = 0;//商品退货总数量
        $goods_return_money = 0;//商品退货总金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货-补差价金额
        //$actual_money = 0;//实际金额 = 销售金额-退货金额+换货补差价金额
        foreach ($goods_cat_list as &$cat_detail) {
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId1'];
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId2'];
            $goods_cat_id_arr[] = (int)$cat_detail['goodsCatId3'];
            $curr_order_id_arr = array();//当前分类订单id
            $curr_sale_num = 0;//当前分类商品销售数量
            $curr_sale_money = 0;//当前分类商品销售金额
            $curr_return_num = 0;//当前分类商品退货数量
            $curr_return_money = 0;//当前分类商品退货金额
            $curr_exchange_input_num = 0;//当前分类商品换货-退回数量
            $curr_exchange_out_num = 0;//当前分类商品换货-换出数量
            $curr_exchange_diff_money = 0;//当前分类商品换货-补差价金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goodsCatId3'] != $cat_detail['goodsCatId3']) {
                    continue;
                }
                $curr_order_id_arr[] = $relation_detail['order_id'];
                $curr_sale_num += (float)$relation_detail['goods_num'];
                $curr_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_return_num += (float)$relation_detail['return_goods_num'];
                $curr_return_money += (float)$relation_detail['refund_money'];
                $curr_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
            }
            $curr_order_num = count(array_unique($curr_order_id_arr));
            $curr_actual_money = (float)(((float)$curr_sale_money - (float)$curr_return_money) + (float)$curr_exchange_diff_money);//当前分类商品实际总金额 = 销售金额-退回金额+换货补差价金额
            $cat_detail['order_num'] = formatAmount($curr_order_num, 0);
            $cat_detail['sale_num'] = formatAmount($curr_sale_num, 0);
            $cat_detail['sale_money'] = formatAmount($curr_sale_money);
            $cat_detail['return_goods_num'] = formatAmount($curr_return_num, 0);
            $cat_detail['return_goods_money'] = formatAmount($curr_return_money);
            $cat_detail['exchange_input_num'] = formatAmount($curr_exchange_input_num, 0);
            $cat_detail['exchange_out_num'] = formatAmount($curr_exchange_out_num, 0);
            $cat_detail['exchange_diff_money'] = formatAmount($curr_exchange_diff_money);
            $cat_detail['actual_money'] = formatAmount($curr_actual_money);
            $goods_sale_num += $curr_sale_num;
            $goods_sale_money += $curr_sale_money;
            $goods_return_num += $curr_return_num;
            $goods_return_money += $curr_return_money;
            $goods_exchange_input_num += $curr_exchange_input_num;
            $goods_exchange_out_num += $curr_exchange_out_num;
            $goods_exchange_diff_money += $curr_exchange_diff_money;
        }
        unset($cat_detail);
        $actual_money = (float)(($goods_sale_money - $goods_return_money) - $goods_exchange_diff_money);//实际金额 = 销售金额-退货金额+换货补差价金额
        $goods_cat_module = new GoodsCatModule();
        $cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as &$cat_detail) {
            foreach ($cat_list as $cat_info) {
                if ($cat_detail['goodsCatId1'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName1'] = $cat_info['catName'];
                }
                if ($cat_detail['goodsCatId2'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName2'] = $cat_info['catName'];
                }
                if ($cat_detail['goodsCatId3'] == $cat_info['catId']) {
                    $cat_detail['goodsCatName3'] = $cat_info['catName'];
                }
            }
        }
        unset($cat_detail);
        $goods_cat_data['root'] = $goods_cat_list;
        $goods_cat_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//订单总量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//商品销售总量
            'goods_sale_money' => formatAmount($goods_sale_money),//商品销售总金额
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货总量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货总金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//换货-商品退回总数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//换货-商品换出总数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-补差价金额
            'actual_money' => formatAmount($actual_money),//实际金额
        );
        return $goods_cat_data;
    }

    /**
     * 商品销量统计报表-按商品统计-图表模式
     * @param array $relation_list 日期报表关联数据列表
     * @param array $attach 附加参数
     * @return array
     * */
    public function saleReportToCatToChart(array $relation_list)
    {
        $goods_cat_module = new GoodsCatModule();
        $goods_cat_id_arr = array();//商品商城分类id
        $sale_goods_money = 0;//订货商品金额
        $return_goods_money = 0;//退货商品金额
        $sale_goods_num = 0;//订货商品数量
        $return_goods_num = 0;//退货商品数量
        $goods_exchange_input_num = 0;//换货-商品退回数量
        $goods_exchange_out_num = 0;//换货-商品换出数量
        $goods_exchange_diff_money = 0;//换货-商品补差价金额
        foreach ($relation_list as $relation_detail) {//拼接商品列表数据
            $goods_cat_id_arr[] = $relation_detail['goodsCatId3'];
        }
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');//三级分类列表
        $cat_sale_list = array();//销货数据
        $cat_return_list = array();//退货数据
        $cat_exchange_list = array();//换货数据
        foreach ($relation_list as &$relation_detail) {
            $relation_detail['catName'] = '';
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $relation_detail['goodsCatId3']) {
                    $relation_detail['catName'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($relation_detail);
        foreach ($relation_list as $relation_detail) {
            $goods_cat_id3 = $relation_detail['goodsCatId3'];
            $is_return_goods = $relation_detail['is_return_goods'];
            $is_exchange_goods = $relation_detail['is_exchange_goods'];
            $public_cat_detail = array(
                'catId' => $goods_cat_id3,
                'catName' => $relation_detail['catName'],
            );
            $current_goods_paid_price = (float)$relation_detail['goods_paid_price'];//单商品实付金额
            $current_goods_num = (int)$relation_detail['goods_num'];//当前商品购买数量
            $current_return_goods_num = (int)$relation_detail['return_goods_num'];//退货数量
            $current_return_goods_money = (float)bc_math($current_return_goods_num, $current_goods_paid_price, 'bcmul', 2);//当前商品退货金额
            $current_exchange_input_num = (int)$relation_detail['exchange_goods_input_num'];//换货-当前商品退回数量
            $current_exchange_out_num = (int)$relation_detail['exchange_goods_out_num'];//换货-当前商品换出数量
            $current_exchange_diff_money = (float)$relation_detail['exchange_diff_price'];//换货-当前商品换货补差价金额
            $surplus_goods_num = (int)bc_math($current_goods_num, $current_return_goods_num, 'bcsub', 0);//当前商品有效订货数量
            $surplus_goods_money = (float)bc_math($surplus_goods_num, $current_goods_paid_price, 'bcmul', 2);//当前商品有效订货金额
            if ($is_return_goods != 2) {//只处理未全部退货的数据
                if (!isset($cat_sale_list[$goods_cat_id3])) {
                    //新增
                    $cat_sale_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_sale_list[$goods_cat_id3]['sale_num'] = formatAmount($surplus_goods_num, 0);//订货商品数量
                    $cat_sale_list[$goods_cat_id3]['sale_money'] = formatAmount($surplus_goods_money);//订货商品金额
                } else {
                    //更新
                    $sale_num = (int)bc_math($cat_sale_list[$goods_cat_id3]['sale_num'], $surplus_goods_num, 'bcadd', 0);
                    $sale_money = (float)bc_math($cat_sale_list[$goods_cat_id3]['sale_money'], $surplus_goods_money, 'bcadd', 2);
                    $cat_sale_list[$goods_cat_id3]['sale_num'] = formatAmount($sale_num, 0);//订货商品数量
                    $cat_sale_list[$goods_cat_id3]['sale_money'] = formatAmount($sale_money);//订货商品金额
                }
            }
            if ($is_return_goods != 0) {//退货数据
                if (!isset($cat_return_list[$goods_cat_id3])) {
                    //新增
                    $cat_return_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_return_list[$goods_cat_id3]['return_num'] = formatAmount($current_return_goods_num, 0);//退货商品数量
                    $cat_return_list[$goods_cat_id3]['return_money'] = formatAmount($current_return_goods_money);//退货商品金额
                } else {
                    //更新
                    $return_num = (int)bc_math($cat_return_list[$goods_cat_id3]['return_num'], $current_return_goods_num, 'bcadd', 0);
                    $return_money = (float)bc_math($cat_return_list[$goods_cat_id3]['return_money'], $current_return_goods_money, 'bcadd', 2);
                    $cat_return_list[$goods_cat_id3]['return_num'] = formatAmount($return_num, 0);//退货商品数量
                    $cat_return_list[$goods_cat_id3]['return_money'] = formatAmount($return_money);//退货商品金额
                }
            }
            if ($is_exchange_goods != 0) {//换货数据
                if (!isset($cat_exchange_list[$goods_cat_id3])) {
                    //新增
                    $cat_exchange_list[$goods_cat_id3] = $public_cat_detail;
                    $cat_exchange_list[$goods_cat_id3]['exchange_input_num'] = formatAmount($current_exchange_input_num, 0);//换货-退回商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_out_num'] = formatAmount($current_exchange_out_num, 0);//换货-换出商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_diff_money'] = formatAmount($current_exchange_diff_money);//换货-换货补差价金额
                } else {
                    //更新
                    $current_exchange_out_num = (int)bc_math([$goods_cat_id3]['exchange_input_num'], $current_exchange_input_num, 'bcadd', 0);
                    $current_exchange_out_num = (int)bc_math([$goods_cat_id3]['exchange_out_num'], $current_exchange_out_num, 'bcadd', 0);
                    $return_money = (float)bc_math($cat_exchange_list[$goods_cat_id3]['exchange_diff_money'], $current_exchange_out_num, 'bcadd', 2);
                    $cat_exchange_list[$goods_cat_id3]['exchange_out_num'] = formatAmount($return_num, 0);//换货-换出商品数量
                    $cat_exchange_list[$goods_cat_id3]['exchange_diff_money'] = formatAmount($return_money);//换货-换货补差价金额
                }
            }
            $sale_goods_money += $surplus_goods_money;
            $return_goods_money += $current_return_goods_money;
            $sale_goods_num += $surplus_goods_num;
            $goods_exchange_input_num += $current_exchange_input_num;
            $goods_exchange_out_num += $current_exchange_out_num;
            $goods_exchange_diff_money += $current_exchange_diff_money;
            $return_goods_num += $current_return_goods_num;
        }
        foreach ($cat_sale_list as &$sale_detail) {//处理销售商品金额比例
            $percent = (float)bc_math($sale_detail['sale_money'], $sale_goods_money, 'bcdiv', 4);
            $sale_detail['percent'] = ($percent * 100) . '%';
        }
        unset($sale_detail);
        foreach ($cat_return_list as &$return_detail) {//处理退货商品金额比例
            $percent = (float)bc_math($return_detail['return_money'], $return_goods_money, 'bcdiv', 4);
            $return_detail['percent'] = ($percent * 100) . '%';
        }
        unset($return_detail);
        foreach ($cat_exchange_list as &$exchange_detail) {//处理换货商品金额比例
            $percent = (float)bc_math($exchange_detail['exchange_diff_money'], $goods_exchange_diff_money, 'bcdiv', 4);
            $exchange_detail['percent'] = ($percent * 100) . '%';
        }
        unset($exchange_detail);
        $response_data = array();
        $response_data['cat_sale_list'] = array_values($cat_sale_list);
        $response_data['cat_return_list'] = array_values($cat_return_list);
        $response_data['cat_exchange_list'] = array_values($cat_exchange_list);
        $response_data['sum'] = array(
            'sale_goods_num' => formatAmount($sale_goods_num, 0),//订货商品数量
            'sale_goods_money' => formatAmount($sale_goods_money),//订货商品金额
            'return_goods_num' => formatAmount($return_goods_num, 0),//商品退货数量
            'return_goods_money' => formatAmount($return_goods_money),//商品退货总金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num),//换货-商品退回总数
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num),//换货-商品退回总数
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//换货-商品换货补差价金额
        );
        return $response_data;
    }

    /**
     * 商品销量统计报表-客户详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/qmclsk
     * @param array $params <p>
     * int shop_id 门店id
     * int goods_id 商品id
     * int cat_id 分类id
     * date start_date 开始日期
     * date end_date 结束日期
     * int data_type 统计类型(1:按商品统计 2:按类型统计)
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function goodsSaleReportCustomerDetail(array $params)
    {
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $data_type = (int)$params['data_type'];
        if ($data_type == 1) {//按商品统计-客户详情
            $response_data = $this->saleReportCustomerDetailToGoods($params);
        } else {//按分类统计-客户详情
            $response_data = $this->saleReportCustomerDetailToCat($params);
        }
        $response_data['start_date'] = $start_date;
        $response_data['end_date'] = $end_date;
        return $response_data;
    }

    /**
     * 商品销量统计报表-按商品统计-客户详情
     * @param array $params <p>
     * int goods_id 商品id
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function saleReportCustomerDetailToGoods(array $params)
    {
        $goods_module = new GoodsModule();
        $goods_cat_module = new GoodsCatModule();
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $shop_id = (int)$params['shop_id'];
        $goods_id = (int)$params['goods_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $field = 'goodsId,goodsName,goodsSn,goodsCatId1,goodsCatId2,goodsCatId3,goodsSpec';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        $goods_cat_id = array($goods_detail['goodsCatId1'], $goods_detail['goodsCatId2'], $goods_detail['goodsCatId3']);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id, 'catId,catName');
        foreach ($goods_cat_list as $cat_detail) {
            if ($goods_detail['goodsCatId1'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName1'] = $cat_detail['catName'];
            }
            if ($goods_detail['goodsCatId2'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName2'] = $cat_detail['catName'];
            }
            if ($goods_detail['goodsCatId3'] == $cat_detail['catId']) {
                $goods_detail['goodsCatName3'] = $cat_detail['catName'];
            }
        }
        $where = array(
            'relation.shop_id' => $shop_id,
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.goods_id' => $goods_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.is_delete' => 0,
        );
        $field = 'relation.id,users.userId,users.userName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        $customer_data = $this->pageQuery($sql, $page, $page_size);//获取客户列表信息
        $customer_list = (array)$customer_data['root'];
        $relation_where = array(
            'shop_id' => $shop_id,
//            'data_type' => array('IN', array(1, 4, 5)),
            'data_type' => 1,
            'goods_id' => $goods_id,
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'is_delete' => 0,
        );
        $field = 'id,report_date,user_id,order_id,goods_num,goods_paid_price,goods_paid_price_total,is_return_goods,return_goods_num,goods_paid_price_total,exchange_goods_input_num,exchange_goods_out_num,exchange_diff_price';
        $relation_list = (array)$relation_model
            ->where($relation_where)
            ->field($field)
            ->select();//获取相关报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $customer_detail['goods_sale_num'] += (int)$relation_detail['goods_num'];
                $customer_detail['goods_sale_money'] += (float)$relation_detail['goods_paid_price_total'];
                $customer_detail['goods_return_num'] += (int)$relation_detail['return_goods_num'];
                $customer_detail['goods_return_money'] += (float)$relation_detail['refund_money'];
                $customer_detail['goods_exchange_input_num'] += (int)$relation_detail['exchange_goods_input_num'];
                $customer_detail['goods_exchange_out_num'] += (int)$relation_detail['exchange_goods_out_num'];
                $customer_detail['goods_exchange_diff_money'] += (float)$relation_detail['exchange_diff_price'];
                $actual_money = ((float)$relation_detail['goods_paid_price_total'] - (float)$relation_detail['refund_money'] + (float)$relation_detail['exchange_diff_price']);
                $customer_detail['actual_money'] += $actual_money;

            }
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            //处理返回的格式,避免出现格式不统一的情况
            $customer_detail['goods_sale_num'] = formatAmount($customer_detail['goods_sale_num'], 0);
            $customer_detail['goods_sale_money'] = formatAmount($customer_detail['goods_sale_money']);
            $customer_detail['goods_return_num'] = formatAmount($customer_detail['goods_return_num'], 0);
            $customer_detail['goods_return_money'] = formatAmount($customer_detail['goods_return_money']);
            $customer_detail['goods_exchange_input_num'] = formatAmount($customer_detail['goods_exchange_input_num'], 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($customer_detail['goods_exchange_out_num'], 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($customer_detail['goods_exchange_diff_money']);
            $customer_detail['actual_money'] = formatAmount($customer_detail['actual_money']);
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['goods_detail'] = $goods_detail;
        return $customer_data;
    }

    /**
     * 商品销量统计报表-按分类统计-客户详情
     * @param array $params <p>
     * string report_id_str 报表日期id,多个用英文逗号分隔
     * int cat_id 商品第三级分类id
     * int page 页码
     * int page_size 分页条数
     * </p>
     * @return array
     * */
    public function saleReportCustomerDetailToCat(array $params)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $goods_cat_module = new GoodsCatModule();
        $shop_id = (int)$params['shop_id'];
        $cat_id = (int)$params['cat_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $field = 'catId,catName,parentId';
        $cat_detail = $goods_cat_module->getGoodsCatDetailById($cat_id, $field);
        $cat_detail['goodsCatId1'] = '';
        $cat_detail['goodsCatName1'] = '';
        $cat_detail['goodsCatId2'] = '';
        $cat_detail['goodsCatName2'] = '';
        $cat_detail['goodsCatId3'] = $cat_detail['catId'];
        $cat_detail['goodsCatName3'] = $cat_detail['catName'];
        if (!empty($cat_detail['parentId'])) {
            $cat2_detail = $goods_cat_module->getGoodsCatDetailById($cat_detail['parentId'], $field);
            $cat_detail['goodsCatId2'] = $cat2_detail['catId'];
            $cat_detail['goodsCatName2'] = $cat2_detail['catName'];
            if (!empty($cat2_detail['parentId'])) {
                $cat1_detail = $goods_cat_module->getGoodsCatDetailById($cat2_detail['parentId'], $field);
                $cat_detail['goodsCatId1'] = $cat1_detail['catId'];
                $cat_detail['goodsCatName1'] = $cat1_detail['catName'];
            }
        }
        $where = array(
            'relation.shop_id' => $shop_id,
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.goods_cat_id3' => $cat_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.is_delete' => 0,
        );
        $field = 'relation.id,users.userId,users.userName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        $customer_data = $this->pageQuery($sql, $page, $page_size);//获取客户信息列表
        $customer_list = (array)$customer_data['root'];
        $relation_where = array(
            'shop_id' => $shop_id,
            'data_type' => array('IN', array(1, 4, 5)),
            'goods_cat_id3' => $cat_id,
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'is_delete' => 0,
        );
        $field = 'id,report_date,user_id,order_id,goods_num,goods_paid_price,goods_paid_price_total,is_return_goods,return_goods_num,goods_paid_price_total,exchange_goods_input_num,exchange_goods_out_num,exchange_diff_price';
        $relation_list = (array)$relation_model
            ->where($relation_where)
            ->field($field)
            ->select();//获取相关报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $customer_detail['goods_sale_num'] += (int)$relation_detail['goods_num'];
                $customer_detail['goods_sale_money'] += (float)$relation_detail['goods_paid_price_total'];
                $customer_detail['goods_return_num'] += (int)$relation_detail['return_goods_num'];
                $customer_detail['goods_return_money'] += (float)$relation_detail['refund_money'];
                $customer_detail['goods_exchange_input_num'] += (int)$relation_detail['exchange_goods_input_num'];
                $customer_detail['goods_exchange_out_num'] += (int)$relation_detail['exchange_goods_out_num'];
                $customer_detail['goods_exchange_diff_money'] += (float)$relation_detail['exchange_diff_price'];
                $actual_money = ((float)$relation_detail['goods_paid_price_total'] - (float)$relation_detail['refund_money'] + (float)$relation_detail['exchange_diff_price']);
                $customer_detail['actual_money'] += $actual_money;

            }
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            //处理返回的格式,避免出现格式不统一的情况
            $customer_detail['goods_sale_num'] = formatAmount($customer_detail['goods_sale_num'], 0);
            $customer_detail['goods_sale_money'] = formatAmount($customer_detail['goods_sale_money']);
            $customer_detail['goods_return_num'] = formatAmount($customer_detail['goods_return_num'], 0);
            $customer_detail['goods_return_money'] = formatAmount($customer_detail['goods_return_money']);
            $customer_detail['goods_exchange_input_num'] = formatAmount($customer_detail['goods_exchange_input_num'], 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($customer_detail['goods_exchange_out_num'], 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($customer_detail['goods_exchange_diff_money']);
            $customer_detail['actual_money'] = formatAmount($customer_detail['actual_money']);
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['cat_detail'] = $cat_detail;
        return $customer_data;
    }

    /**
     * 客户统计
     * @param int $shop_id 门店id
     * @param string $user_name 客户名称
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function customerReport(int $shop_id, $user_name, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.shop_id' => $shop_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.is_delete' => 0,
        );
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where['relation.user_id'] = 0;
            } else {
                $where['users.userName'] = array('like', "%{$user_name}%");
            }
        }
        $field = 'relation.id,users.userId,users.userName';
        //获取客户信息列表
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('users.userId')
            ->buildSql();
        if ($export != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.id,relation.report_date,relation.user_id,relation.order_id,relation.data_type,relation.goods_num,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_return_goods,relation.return_goods_num,relation.goods_paid_price_total,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.return_orders_id,relation.exchange_orders_id';
        //获取相关报表明细
        $relation_list = (array)$relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->select();
        $sales_order_num = 0;//销售订单总数
        $return_order_num = 0;//退货单总数
        $exchange_order_num = 0;//换货单总数
//        $recharge_num = 0;//充值总数
//        $buy_setmeal_num = 0;//购买套餐总数
        $sales_order_money = 0;//销售订单总金额
        $return_order_money = 0;//退货单总金额
        $exchange_order_money = 0;//换货单补差价总金额
//        $recharge_money = 0;//充值总金额
//        $buy_setmeal_money = 0;//购买套餐总金额
        $actual_money = 0;//实际金额
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $current_sales_order_num = array();//当前客户销售订单总数
            $current_return_order_num = array();//当前客户退货单总数
            $current_exchange_order_num = array();//当前客户换货单总数
//            $current_recharge_num = 0;//当前客户充值总数
//            $current_buy_setmeal_num = 0;//当前客户购买套餐总数
            $current_sales_order_money = 0;//当前客户销售订单总金额
            $current_return_order_money = 0;//当前客户退货单总金额
            $current_exchange_order_money = 0;//当前客户换货单补差价总金额
//            $current_recharge_money = 0;//当前客户充值总金额
//            $current_buy_setmeal_money = 0;//当前客户购买套餐总金额
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $data_type = (int)$relation_detail['data_type'];//业务类型(1:订单 2:会员充值 3:购买套餐 4:退货单 5:换货单) PS:目前已知业务类型
                if ($data_type == 1) {//销售订单
                    $current_sales_order_num[] = $relation_detail['order_id'];
                    $current_sales_order_money += (float)$relation_detail['goods_paid_price_total'];
                } elseif ($data_type == 2) {//会员充值
//                    $current_recharge_num += 1;
//                    $current_recharge_money += (float)$relation_detail['recharge_money'];
                } elseif ($data_type == 3) {//购买套餐
//                    $current_buy_setmeal_num += 1;
//                    $current_buy_setmeal_money += (float)$relation_detail['buy_setmeal_money'];
                } elseif ($data_type == 4) {//退货单
                    $current_return_order_num[] = $relation_detail['return_orders_id'];
                    $current_return_order_money += (float)$relation_detail['refund_money'];
                } elseif ($data_type == 5) {//换货单
                    $current_exchange_order_num[] = $relation_detail['exchange_orders_id'];
                    $current_exchange_order_money += (float)$relation_detail['exchange_diff_price'];
                }
            }
            $current_actual_money = bc_math($current_sales_order_money, $current_return_order_money, 'bcsub', 2);//当前客户实际金额=销售商品金额-退货金额+换货补差价金额
            $current_actual_money = bc_math($current_actual_money, $current_exchange_order_money, 'bcadd', 2);
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['userName'] = (string)$customer_detail['userName'];
            if (empty($customer_detail['userId'])) {
                $customer_detail['userName'] = '游客';
            }
            $customer_detail['sales_order_num'] = formatAmount(count(array_unique($current_sales_order_num)), 0);//当前客户销售单总数量
            $customer_detail['return_order_num'] = formatAmount(count(array_unique($current_return_order_num)), 0);//当前客户退货单总数量
            $customer_detail['exchange_order_num'] = formatAmount(count(array_unique($current_exchange_order_num)), 0);//当前客户换货单总数量
//            $customer_detail['recharge_num'] = formatAmount($current_recharge_num, 0);//当前客户充值总数
//            $customer_detail['buy_setmeal_num'] = formatAmount($current_buy_setmeal_num, 0);//当前客户购买套餐总数
            $customer_detail['sales_order_money'] = formatAmount($current_sales_order_money);//当前客户购买销售订单
            $customer_detail['return_order_money'] = formatAmount($current_return_order_money);//当前客户退货单总金额
            $customer_detail['exchange_order_money'] = formatAmount($current_exchange_order_money);//当前客户换货补差价总金额
//            $customer_detail['recharge_money'] = formatAmount($current_recharge_money);//当前客户充值总金额
//            $customer_detail['buy_setmeal_money'] = formatAmount($current_buy_setmeal_money);//当前客户充值总金额
            $customer_detail['actual_money'] = formatAmount($current_actual_money);//当前客户实际金额
            $sales_order_num += $customer_detail['sales_order_num'];
            $return_order_num += $customer_detail['return_order_num'];
            $exchange_order_num += $customer_detail['exchange_order_num'];
//            $recharge_num += $customer_detail['recharge_num'];
//            $buy_setmeal_num += $customer_detail['buy_setmeal_num'];
            $sales_order_money += $customer_detail['sales_order_money'];
            $return_order_money += $customer_detail['return_order_money'];
            $exchange_order_money += $customer_detail['exchange_order_money'];
//            $recharge_money += $customer_detail['recharge_money'];
//            $buy_setmeal_money += $customer_detail['buy_setmeal_money'];
            $actual_money += $customer_detail['actual_money'];
        }
        unset($customer_detail);
        $customer_data['root'] = $customer_list;
        $customer_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),
            'return_order_num' => formatAmount($return_order_num, 0),
            'exchange_order_num' => formatAmount($exchange_order_num, 0),
//            'recharge_num' => formatAmount($recharge_num, 0),
//            'buy_setmeal_num' => formatAmount($buy_setmeal_num, 0),
            'sales_order_money' => formatAmount($sales_order_money),
            'return_order_money' => formatAmount($return_order_money),
            'exchange_order_money' => formatAmount($exchange_order_money),
//            'recharge_money' => formatAmount($recharge_money),
//            'buy_setmeal_money' => formatAmount($buy_setmeal_money),
            'actual_money' => formatAmount($actual_money),
        );
        if ($export == 1) {
            $this->exportCustomer($customer_data);
        }
        return $customer_data;
    }

    /**
     * 客户毛利-导出
     * @param array $response_data 业务数据
     * */
    public function exportCustomer(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户统计报表';
        $excel_filename = '客户统计报表' . date('YmdHis');
        $sheet_title = array('客户名称', '订单总量', '退货单总量', '换货单总量', '订单总金额', '退货单总金额', '换货单补差价金额', '实际金额');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'userName' => '合计',
                'sales_order_num' => $sum['sales_order_num'],
                'return_order_num' => $sum['return_order_num'],
                'exchange_order_num' => $sum['exchange_order_num'],
                'sales_order_money' => $sum['sales_order_money'],
                'return_order_money' => $sum['return_order_money'],
                'exchange_order_money' => $sum['exchange_order_money'],
                'actual_money' => $sum['actual_money'],
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总量
                        break;
                    case 2:
                        $cellvalue = $detail['return_order_num'];//退货单总量
                        break;
                    case 3:
                        $cellvalue = $detail['exchange_order_num'];//换货单总量
                        break;
                    case 4:
                        $cellvalue = $detail['sales_order_money'];//订单总金额
                        break;
                    case 5:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 6:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价金额
                        break;
                    case 7:
                        $cellvalue = $detail['actual_money'];//换货单补差价金额
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 订单统计报表
     * @param int $shop_id 门店id
     * @param date $start_date 开始日期
     * @param date $end_date 结束日期
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function ordersReport(int $shop_id, $start_date, $end_date, int $page, int $page_size, int $export)
    {
        $report_model = new PosReportModel();
        $where = array(
            'shop_id' => $shop_id,
            'report_date' => array('between', array("{$start_date}", "{$end_date}")),//只统计订单,退货单,换货单
            'is_delete' => 0
        );
        $where['_string'] = "(sales_order_num > 0 or return_order_num > 0 or exchange_order_num > 0)";
        $field = 'report_id,shop_id,report_date,return_order_num,return_order_money,exchange_order_num,exchange_order_money,exchange_cash_money,exchange_wxpay_money,exchange_alipay_money,sales_order_num,sales_order_money,sales_order_practical_money,sales_order_need_money,sales_order_cash_money,sales_order_balance_money,sales_order_wxpay_money,sales_order_alipay_money,sales_order_goods_money,sales_order_score_money,sales_order_use_score,recharge_num,recharge_wxpay_money,recharge_alipay_money,recharge_cash_money,buy_setmeal_num,buy_setmeal_wxpay_money,buy_setmeal_alipay_money,buy_setmeal_cash_money,is_delete,return_cash_money,return_wxpay_money,return_alipay_money';
        $sql = $report_model
            ->where($where)
            ->field($field)
            ->order('report_date asc')
            ->buildSql();//报表日期数据
        if ($export != 1) {
            $report_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $report_data = array();
            $report_data['root'] = $this->query($sql);
        }
        $report_list = (array)$report_data['root'];
        $sales_order_num = 0;//销售订单总数
        $sales_order_use_score = 0;//销售订单积分抵扣
        $sales_order_goods_money = 0;//销售订单商品总金额
        $sales_order_need_money = 0;//销售订单应收总金额
        $sales_order_score_money = 0;//销售订单积分抵扣总金额
        $sales_order_practical_money = 0;//销售订单实收总金额
        $return_cash_money = 0;//现金退款金额
//        $recharge_wxpay_money = 0;//余额微信充值总金额
//        $recharge_alipay_money = 0;//余额支付宝充值总金额
//        $recharge_cash_money = 0;//余额现金充值总金额
//        $buy_setmeal_wxpay_money = 0;//购买套餐总金额(微信)
//        $buy_setmeal_alipay_money = 0;//购买套餐总金额(支付宝)
//        $buy_setmeal_cash_money = 0;//购买套餐总金额(现金)
        $cash_pay_money = 0;//现金支付总金额
        $balance_pay_money = 0;//余额支付总金额
        $wxpay_money = 0;//微信支付总金额
        $alipay_money = 0;//支付宝支付总金
        $return_order_num = 0;//退货单总数量
        $return_order_money = 0;//退货单总金额
        $exchange_order_num = 0;//换货单笔数
        $exchange_order_money = 0;//换货补差价金额总计
        $cash_arrival_money = 0;//现金实际到账金额
        $wxpay_arrival_money = 0;//微信实际到账金额
        $alipay_arrival_money = 0;//支付宝实际到账金额
        $income_money = 0;//实际收入
        foreach ($report_list as &$report_detail) {
            $sales_order_num += (float)$report_detail['sales_order_num'];
            $sales_order_goods_money += (float)$report_detail['sales_order_goods_money'];
            $sales_order_need_money += (float)$report_detail['sales_order_need_money'];
            $sales_order_use_score += (float)$report_detail['sales_order_use_score'];
            $sales_order_score_money += (float)$report_detail['sales_order_score_money'];
            $sales_order_practical_money += (float)$report_detail['sales_order_practical_money'];
            $return_cash_money += (float)$report_detail['return_cash_money'];
            $wxpay_money += (float)$report_detail['sales_order_wxpay_money'];
            $alipay_money += (float)$report_detail['sales_order_alipay_money'];
            $return_order_num += (float)$report_detail['return_order_num'];
            $return_order_money += (float)$report_detail['return_order_money'];
            $exchange_order_num += (float)$report_detail['exchange_order_num'];
            $exchange_order_money += (float)$report_detail['exchange_order_money'];
            $cash_pay_money += (float)(((float)$report_detail['sales_order_cash_money'] + (float)$report_detail['exchange_cash_money']) - (float)$report_detail['return_cash_money']);
            $balance_pay_money += (float)((float)$report_detail['sales_order_balance_money']);
            $current_cash_arrival_money = ((float)$report_detail['sales_order_cash_money'] + (float)$report_detail['exchange_cash_money']) - ((float)$report_detail['return_cash_money']);//当前日期-现金实际到账金额 = (销售订单金额(现金) + 换货补差价(现金)) - (退货单金额(现金))
            $current_wxpay_arrival_money = ((float)$report_detail['sales_order_wxpay_money'] + (float)$report_detail['exchange_cash_money']) - ((float)$report_detail['return_wxpay_money']);//当前日期-微信实际到账金额 = (销售订单金额(微信) + 换货补差价(微信)) - (退货单金额(微信))
            $current_alipay_arrival_money = ((float)$report_detail['sales_order_alipay_money'] + (float)$report_detail['exchange_alipay_money']) - ((float)$report_detail['return_alipay_money']);//当前日期-支付宝实际到账金额 = (销售订单金额(支付宝) + 换货补差价(支付宝)) - (退货单金额(支付宝))
            $current_income_money = $current_cash_arrival_money + $current_wxpay_arrival_money + $current_alipay_arrival_money;//当前日期-实际收入 = 当期实际到账金额(现金 + 微信 + 支付宝)
            $report_detail['cash_arrival_money'] = formatAmount($current_cash_arrival_money);
            $report_detail['wxpay_arrival_money'] = formatAmount($current_wxpay_arrival_money);
            $report_detail['alipay_arrival_money'] = formatAmount($current_alipay_arrival_money);
            $report_detail['income_money'] = formatAmount($current_income_money);
            $cash_arrival_money += $current_cash_arrival_money;
            $wxpay_arrival_money += $current_wxpay_arrival_money;
            $alipay_arrival_money += $current_alipay_arrival_money;
            $income_money += $current_income_money;
        }
        unset($report_detail);
        $report_data['root'] = $report_list;
        $report_data['sum'] = array(
            'sales_order_num' => formatAmount($sales_order_num, 0),//销售订单总数
            'sales_order_goods_money' => formatAmount($sales_order_goods_money),//销售订单商品总金额
            'sales_order_need_money' => formatAmount($sales_order_need_money),//销售订单应收总金额
            'sales_order_use_score' => formatAmount($sales_order_use_score),//销售订单积分抵扣
            'sales_order_score_money' => formatAmount($sales_order_score_money),//销售订单积分抵扣总金额
            'sales_order_practical_money' => formatAmount($sales_order_practical_money),//销售订单实收总金额
            'return_cash_money' => formatAmount($return_cash_money),//现金退款金额
//            'recharge_wxpay_money' => formatAmount($recharge_wxpay_money),//余额微信充值总金额
//            'recharge_alipay_money' => formatAmount($recharge_alipay_money),//余额支付宝充值总金额
//            'recharge_cash_money' => formatAmount($recharge_cash_money),//余额现金充值总金额
//            'buy_setmeal_wxpay_money' => formatAmount($buy_setmeal_wxpay_money),//购买套餐总金额(微信)
//            'buy_setmeal_alipay_money' => formatAmount($buy_setmeal_alipay_money),//购买套餐总金额(支付宝)
//            'buy_setmeal_cash_money' => formatAmount($buy_setmeal_cash_money),//购买套餐总金额(现金)
            'cash_pay_money' => formatAmount($cash_pay_money),//现金支付总金额
            'balance_pay_money' => formatAmount($balance_pay_money),//余额支付总金额
            'wxpay_money' => formatAmount($wxpay_money),//微信支付总金额
            'alipay_money' => formatAmount($alipay_money),//支付宝支付总金
            'return_order_num' => formatAmount($return_order_num, 0),//退货单总数量
            'return_order_money' => formatAmount($return_order_money),//退货单总金额额
            'exchange_order_num' => formatAmount($exchange_order_num, 0),//换货单总数
            'exchange_order_money' => formatAmount($exchange_order_money),//换货补差价金额总计
            'cash_arrival_money' => formatAmount($cash_arrival_money),//现金实际到账金额
            'wxpay_arrival_money' => formatAmount($wxpay_arrival_money),//微信实际到账金额
            'alipay_arrival_money' => formatAmount($alipay_arrival_money),//支付宝实际到账金额
            'income_money' => formatAmount($income_money),//实际收入
        );
        if ($export == 1) {//导出
            $this->exportOrdersReport($report_data);
        }
        return $report_data;
    }

    /**
     * 订单统计报表-导出
     * @param array $response_data 业务数据
     * */
    public function exportOrdersReport(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '订单统计报表';
        $excel_filename = '订单统计报表' . date('YmdHis');
        $sheet_title = array('日期', '订单总数', '订单商品总金额', '订单应收总金额', '订单实收总金额', '订单现金支付总金额', '订单余额支付总金额', '订单微信支付总金额', '订单支付宝支付总金额', '订单积分抵扣', '单积分抵扣金额', '现金实际到账金额', '微信实际到账金额', '支付宝实际到账金额', '退货单总数', '退货单总金额', '换货单总数', '换货单补差价金额', '实际收入');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S');
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $list[] = array(
                'report_date' => '合计',
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'sales_order_goods_money' => $sum['sales_order_goods_money'],//商品总金额
                'sales_order_need_money' => $sum['sales_order_need_money'],//订单应收总金额
                'sales_order_score_money' => $sum['sales_order_score_money'],//订单积分抵扣总金额
                'sales_order_use_score' => $sum['sales_order_use_score'],//订单积分抵扣总金额
                'sales_order_practical_money' => $sum['sales_order_practical_money'],//订单实收总金额
                'sales_order_cash_money' => $sum['cash_pay_money'],//订单现金支付总金额
                'sales_order_balance_money' => $sum['balance_pay_money'],//订单余额支付总金额
                'return_cash_money' => $sum['return_cash_money'],//现金退款金额
                'cash_arrival_money' => $sum['cash_pay_money'],//现金支付总金额
                'balance_pay_money' => $sum['balance_pay_money'],//余额支付总金额
                'sales_order_wxpay_money' => $sum['wxpay_money'],//微信支付总金额
                'sales_order_alipay_money' => $sum['alipay_money'],//支付宝支付总金
                'return_order_num' => $sum['return_order_num'],//退货单总数量
                'return_order_money' => $sum['return_order_money'],//退货单总金额
                'exchange_order_num' => $sum['exchange_order_num'],//换货单总数
                'exchange_order_money' => $sum['exchange_order_money'],//换货补差价金额
                'cash_arrival_money' => $sum['cash_arrival_money'],//现金实际到账金额
                'wxpay_arrival_money' => $sum['wxpay_arrival_money'],//微信实际到账金额
                'alipay_arrival_money' => $sum['alipay_arrival_money'],//支付宝实际到账金额
                'income_money' => $sum['income_money'],//实际收入
            );
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单总数
                        break;
                    case 2:
                        $cellvalue = $detail['sales_order_goods_money'];//订单商品总金额
                        break;
                    case 3:
                        $cellvalue = $detail['sales_order_need_money'];//订单应收总金额
                        break;
                    case 4:
                        $cellvalue = $detail['sales_order_practical_money'];//订单实收总金额
                        break;
                    case 5:
                        $cellvalue = $detail['sales_order_cash_money'];//订单现金支付总金额
                        break;
                    case 6:
                        $cellvalue = $detail['sales_order_balance_money'];//订单余额支付总金额
                        break;
                    case 7:
                        $cellvalue = $detail['sales_order_wxpay_money'];//订单微信支付总金额
                        break;
                    case 8:
                        $cellvalue = $detail['sales_order_alipay_money'];//订单支付宝支付总金额
                        break;
                    case 9:
                        $cellvalue = $detail['sales_order_use_score'];//订单积分抵扣
                        break;
                    case 10:
                        $cellvalue = $detail['sales_order_score_money'];//订单积分抵扣金额
                        break;
                    case 11:
                        $cellvalue = $detail['cash_arrival_money'];//现金实际到账金额
                        break;
                    case 12:
                        $cellvalue = $detail['wxpay_arrival_money'];//微信实际到账金额
                        break;
                    case 13:
                        $cellvalue = $detail['alipay_arrival_money'];//支付宝实际到账金额
                        break;
                    case 14:
                        $cellvalue = $detail['return_order_num'];//退货单总数
                        break;
                    case 15:
                        $cellvalue = $detail['return_order_money'];//退货单总金额
                        break;
                    case 16:
                        $cellvalue = $detail['exchange_order_num'];//换货单总数
                        break;
                    case 17:
                        $cellvalue = $detail['exchange_order_money'];//换货单补差价金额
                        break;
                    case 18:
                        $cellvalue = $detail['income_money'];//实际收入
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 销售毛利统计
     * @param array $params <p>
     * date start_date 开始日期
     * date end_date 结束日期
     * int date_type 统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * string user_name 客户名称 PS:仅按客户统计时需要
     * int page 页码
     * int page_size 分页条数
     * int export 导出 (0:导出 1:不导出)
     * </p>
     * @return array
     * */
    public function saleGrossProfit(array $params)
    {
        $data_type = (int)$params['data_type'];
        if ($data_type == 1) {//按商品统计
            $response_data = $this->saleGrossProfitToGoods($params);
        } elseif ($data_type == 2) {//按分类统计
            $response_data = $this->saleGrossProfitToCat($params);
        } else {//按客户统计
            $response_data = $this->saleGrossProfitToCustomer($params);
        }
        if ($params['export'] == 1) {//导出
            $this->exportSaleGrossProfit($response_data, $params);
        }
        return $response_data;
    }

    /**
     * 销售毛利-导出
     * @param array $response_data
     * @param array $attach 附加参数
     * */
    public function exportSaleGrossProfit(array $response_data, array $attach)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '销售毛利';
        $excel_filename = '销售毛利' . date('YmdHis');
        $data_type = $attach['data_type'];//统计类型(1:按商品统计 2:按分类统计 3:按客户统计)
        if ($data_type == 1) {//按商品统计
//            $sheet_title = array('商品名称', '商品编码', '所属分类', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
            $sheet_title = array('商品名称', '商品编码', '所属分类', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        } elseif ($data_type == 2) {//按分类统计
//            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
            $sheet_title = array('分类名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        } elseif ($data_type == 3) {//按客户统计
//            $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本', '换货-退回数量', '换货-换出数量', '换货补差价金额', '换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
            $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        }
        $list = $response_data['root'];
        if (!empty($list)) {
            $sum = $response_data['sum'];
            //增加尾部合计数据
            $end_list_data = array(
                'sales_order_num' => $sum['sales_order_num'],//订单总数
                'goods_sale_num' => $sum['goods_sale_num'],//销售数量
                'goods_sale_money' => $sum['goods_sale_money'],//销售金额
                'goods_cost_money' => $sum['goods_cost_money'],//成本金额
//                'goods_return_num' => $sum['goods_return_num'],//退货数量
//                'goods_return_money' => $sum['goods_return_money'],//退货金额
//                'goods_return_cost_money' => $sum['goods_return_cost_money'],//退货成本
//                'goods_exchange_input_num' => $sum['goods_exchange_input_num'],//换货-退回数量
//                'goods_exchange_out_num' => $sum['goods_exchange_out_num'],//换货-换出数量
//                'goods_exchange_diff_money' => $sum['goods_exchange_diff_money'],//换货补差价金额
//                'goods_exchange_cost_money' => $sum['goods_exchange_cost_money'],//换货补差价成本金额
//                'actual_money' => $sum['actual_money'],//实际金额
//                'actual_cost_money' => $sum['actual_cost_money'],//实际成本金额
                'gross_profit' => $sum['gross_profit'],//毛利
            );
            if ($data_type == 1) {//按商品统计
                $end_list_data['goodsName'] = '合计';
            } elseif ($data_type == 2) {//按分类统计
                $end_list_data['catName'] = '合计';
            } elseif ($data_type == 3) {//按客户名统计
                $end_list_data['userName'] = '合计';
            }
            $list[] = $end_list_data;
        }
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                //这里每种类型分开写,避免后面字段变动
                $cellvalue = '';
                if ($data_type == 1) {//按商品统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['goodsName'];//商品名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['goodsSn'];//商品编码
//                            break;
//                        case 2:
//                            if (!empty($detail['goodsCatName1'])) {
//                                $cellvalue = $detail['goodsCatName1'] . '/' . $detail['goodsCatName2'] . '/' . $detail['goodsCatName3'];//所属分类
//                            }
//                            break;
//                        case 3:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 12:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 15:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 16:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 17:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
//                    }
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['goodsName'];//商品名称
                            break;
                        case 1:
                            $cellvalue = $detail['goodsSn'];//商品编码
                            break;
                        case 2:
                            if (!empty($detail['goodsCatName1'])) {
                                $cellvalue = $detail['goodsCatName1'] . '/' . $detail['goodsCatName2'] . '/' . $detail['goodsCatName3'];//所属分类
                            }
                            break;
                        case 3:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 4:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 5:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 6:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 7:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 8:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                } elseif ($data_type == 2) {//按分类统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['catName'];//分类名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 2:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 3:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 12:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 15:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
//                    }

                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['catName'];//分类名称
                            break;
                        case 1:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 4:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 5:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 6:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                } elseif ($data_type == 3) {//按客户统计
//                    switch ($j) {
//                        case 0:
//                            $cellvalue = $detail['userName'];//客户名称
//                            break;
//                        case 1:
//                            $cellvalue = $detail['sales_order_num'];//订单数量
//                            break;
//                        case 2:
//                            $cellvalue = $detail['goods_sale_num'];//销售数量
//                            break;
//                        case 3:
//                            $cellvalue = $detail['goods_sale_money'];//销售金额
//                            break;
//                        case 4:
//                            $cellvalue = $detail['goods_cost_money'];//成本金额
//                            break;
//                        case 5:
//                            $cellvalue = $detail['goods_return_num'];//退货数量
//                            break;
//                        case 6:
//                            $cellvalue = $detail['goods_return_money'];//退货金额
//                            break;
//                        case 7:
//                            $cellvalue = $detail['goods_return_cost_money'];//退货成本
//                            break;
//                        case 8:
//                            $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                            break;
//                        case 9:
//                            $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                            break;
//                        case 10:
//                            $cellvalue = $detail['goods_exchange_diff_money'];//换货补差价金额
//                            break;
//                        case 11:
//                            $cellvalue = $detail['goods_exchange_cost_money'];//换货补差价成本金额
//                            break;
//                        case 12:
//                            $cellvalue = $detail['actual_money'];//实际金额
//                            break;
//                        case 13:
//                            $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                            break;
//                        case 14:
//                            $cellvalue = $detail['gross_profit'];//毛利
//                            break;
//                        case 15:
//                            $cellvalue = $detail['gross_profit_rate'];//毛利率
//                            break;
                    switch ($j) {
                        case 0:
                            $cellvalue = $detail['userName'];//客户名称
                            break;
                        case 1:
                            $cellvalue = $detail['sales_order_num'];//订单数量
                            break;
                        case 2:
                            $cellvalue = $detail['goods_sale_num'];//销售数量
                            break;
                        case 3:
                            $cellvalue = $detail['goods_sale_money'];//销售金额
                            break;
                        case 4:
                            $cellvalue = $detail['goods_cost_money'];//成本金额
                            break;
                        case 5:
                            $cellvalue = $detail['gross_profit'];//毛利
                            break;
                        case 6:
                            $cellvalue = $detail['gross_profit_rate'];//毛利率
                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 销售毛利统计-按商品统计
     * @param array $params <p>
     * date start_date 开始日期
     * date end_date 结束日期
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * string goods_keywords 商品名称或商品编码 PS:仅按商品统计时需要
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToGoods(array $params)
    {
        $shop_id = $params['shop_id'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $goods_keywords = $params['goods_keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5)";
        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($goods_cat_id1)) {
            $where .= " goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $where .= " goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $where .= " goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        $field = 'relation.id,goods.goodsId,goods.goodsName,goods.goodsSn,goods.goodsCatId1,goods.goodsCatId2,goods.goodsCatId3';
        $sql = $relation_model
            ->alias('relation')
            ->join(" left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_id')
            ->buildSql();//商品列表信息
        if ($params['export'] != 1) {
            $goods_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_data = array();
            $goods_data['root'] = $this->query($sql);
        }
        $goods_list = $goods_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->join(" left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        $goods_cat_id_arr = array();
        foreach ($goods_list as &$goods_detail) {
            $goods_id = $goods_detail['goodsId'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $goods_detail['goodsCatId3'];
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_goods_id = $relation_detail['goods_id'];
                if ($goods_id != $current_goods_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (int)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_diff_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
                $sales_order_num[] = $relation_detail['order_id'];
            }
            $goods_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $goods_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $goods_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $goods_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $goods_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $goods_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $goods_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $goods_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $goods_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $goods_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货-换货补差价金额
            $goods_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $goods_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $goods_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $goods_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $goods_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $goods_sale_num += (int)$goods_detail['goods_sale_num'];
            $goods_sale_money += (float)$goods_detail['goods_sale_money'];
            $goods_cost_money += (float)$goods_detail['goods_cost_money'];
            $goods_return_num += (int)$goods_detail['goods_return_num'];
            $goods_return_money += (float)$goods_detail['goods_return_money'];
            $goods_return_cost_money += (float)$goods_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$goods_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$goods_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$goods_detail['goods_exchange_cost_money'];
            $actual_money += (float)$goods_detail['actual_money'];
            $actual_cost_money += (float)$goods_detail['actual_cost_money'];
            $gross_profit += (float)$goods_detail['gross_profit'];
        }
        unset($goods_detail);
        $goods_cat_module = new GoodsCatModule();
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_list as &$goods_detail) {
            $goods_detail['goodsCatName1'] = '';
            $goods_detail['goodsCatName2'] = '';
            $goods_detail['goodsCatName3'] = '';
            foreach ($goods_cat_list as $cat_detail) {
                if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                    $goods_detail['goodsCatName1'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                    $goods_detail['goodsCatName2'] = (string)$cat_detail['catName'];
                }
                if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                    $goods_detail['goodsCatName3'] = (string)$cat_detail['catName'];
                }
            }
        }
        unset($goods_detail);
        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退货数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $goods_data['root'] = $goods_list;
            $goods_data['sum'] = $sum;
            $response_data = $goods_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利统计-按分类统计
     * @param array $params <p>
     * date start_date 开始日期
     * date end_date 结束日期
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int goods_cat_id1 商品商城一级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id2 商品商城二级分类id PS:仅按商品统计和按分类统计时需要
     * int goods_cat_id3 商品商城三级分类id PS:仅按商品统计和按分类统计时需要
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToCat(array $params)
    {
        $shop_id = $params['shop_id'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $goods_cat_id1 = $params['goods_cat_id1'];
        $goods_cat_id2 = $params['goods_cat_id2'];
        $goods_cat_id3 = $params['goods_cat_id3'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5)";
        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($goods_cat_id1)) {
            $where .= " goods.goodsCatId1={$goods_cat_id1} ";
        }
        if (!empty($goods_cat_id2)) {
            $where .= " goods.goodsCatId2={$goods_cat_id2} ";
        }
        if (!empty($goods_cat_id3)) {
            $where .= " goods.goodsCatId3={$goods_cat_id3} ";
        }
        if (!empty($goods_keywords)) {
            $where .= " and (goods.goodsName like '%{$goods_keywords}%' or goods.goodsSn like '%{$goods_keywords}%') ";
        }
        $field = 'goods_cat_id1 as goodsCatId1,goods_cat_id2 as goodsCatId2,goods_cat_id3 as goodsCatId3 ';
        $sql = $relation_model
            ->alias('relation')
            ->where($where)
            ->field($field)
            ->group('relation.goods_cat_id3')
            ->buildSql();//第三级分类列表数据
        if ($params['export'] != 1) {
            $goods_cat_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_cat_data = array();
            $goods_cat_data['root'] = $this->query($sql);
        }
        $goods_cat_list = (array)$goods_cat_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        $goods_cat_id_arr = array();
        foreach ($goods_cat_list as &$cat_detail) {
            $goods_cat_id_arr[] = $cat_detail['goodsCatId1'];
            $goods_cat_id_arr[] = $cat_detail['goodsCatId2'];
            $goods_cat_id_arr[] = $cat_detail['goodsCatId3'];
            $cat_detail['catId'] = $cat_detail['goodsCatId3'];
            $cat_detail['goodsCatName1'] = '';
            $cat_detail['goodsCatName2'] = '';
            $cat_detail['goodsCatName3'] = '';
            $cat_detail['catName'] = '';
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_cat_id = $relation_detail['goods_cat_id3'];
                if ($cat_detail['goodsCatId3'] != $current_cat_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (int)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (int)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (int)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_diff_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
                $sales_order_num[] = $relation_detail['order_id'];
            }
            $cat_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $cat_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $cat_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $cat_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $cat_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $cat_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $cat_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $cat_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $cat_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $cat_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货金额
            $cat_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $cat_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $cat_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $cat_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $cat_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $goods_sale_num += (int)$cat_detail['goods_sale_num'];
            $goods_sale_money += (float)$cat_detail['goods_sale_money'];
            $goods_cost_money += (float)$cat_detail['goods_cost_money'];
            $goods_return_num += (int)$cat_detail['goods_return_num'];
            $goods_return_money += (float)$cat_detail['goods_return_money'];
            $goods_return_cost_money += (float)$cat_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$cat_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$cat_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$cat_detail['goods_exchange_diff_money'];
            $goods_exchange_cost_money += (float)$cat_detail['goods_exchange_cost_money'];
            $actual_money += (float)$cat_detail['actual_money'];
            $actual_cost_money += (float)$cat_detail['actual_cost_money'];
            $gross_profit += (float)$cat_detail['gross_profit'];
        }
        unset($cat_detail);
        $goods_cat_module = new GoodsCatModule();
        $cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as &$cat_detail) {
            foreach ($cat_list as $detail) {
                if ($cat_detail['goodsCatId1'] == $detail['catId']) {
                    $cat_detail['goodsCatName1'] = (string)$detail['catName'];
                }
                if ($cat_detail['goodsCatId2'] == $detail['catId']) {
                    $cat_detail['goodsCatName2'] = (string)$detail['catName'];
                }
                if ($cat_detail['catId'] == $detail['catId']) {
                    $cat_detail['goodsCatName3'] = (string)$detail['catName'];
                }
                $cat_detail['catName'] = $cat_detail['goodsCatName1'] . '/' . $cat_detail['goodsCatName2'] . '/' . $cat_detail['goodsCatName3'];
            }
        }
        unset($cat_detail);
        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money, 0),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $goods_cat_data['root'] = $goods_cat_list;
            $goods_cat_data['sum'] = $sum;
            $response_data = $goods_cat_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利统计-按客户统计
     * @param array $params <p>
     * date start_date 开始日期
     * date end_date 结束日期
     * string userName 客户名称
     * int model_type 统计模式(1:列表模式 2:图表模式)
     * int page 页码
     * int page_size 分页条数
     * </p>
     * */
    public function saleGrossProfitToCustomer(array $params)
    {
        $shop_id = $params['shop_id'];
        $model_type = $params['model_type'];//统计模式(1:列表模式 2:图表模式)
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $user_name = $params['user_name'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $relation_model = new PosReportRelationModel();
        $m = new Model();
        $table_prefix = $m->tablePrefix;
//        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type IN(1,4,5) ";
        $where = " relation.shop_id={$shop_id} and relation.is_delete=0 and relation.data_type=1 ";
        $where .= " and relation.report_date between '{$start_date}' and '{$end_date}' ";
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where .= " and relation.user_id=0 ";
            } else {
                $where .= " and users.userName like '%{$user_name}%' ";
            }
        }
        $field = ' users.userId,users.userName ';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('relation.user_id')
            ->buildSql();//第三级分类列表数据
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $relation_list = $relation_model
            ->alias('relation')
            ->where($where)
            ->select();//报表明细信息
        $sales_order_num = array();//销售订单数量 PS:改为用于存在订单的
        $goods_sale_num = 0;//销售商品数量
        $goods_sale_money = 0;//销售商品金额
        $goods_cost_money = 0;//销售成本
        $goods_return_num = 0;//商品退货数量
        $goods_return_money = 0;//商品退货金额
        $goods_return_cost_money = 0;//商品退货成本金额
        $goods_exchange_input_num = 0;//商品换货-退回数量
        $goods_exchange_out_num = 0;//商品换货-换出数量
        $goods_exchange_diff_money = 0;//商品换货补差价金额
        $goods_exchange_cost_money = 0;//商品补差价成本金额
        $actual_money = 0;//实际金额
        $actual_cost_money = 0;//实际成本
        $gross_profit = 0;//毛利
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $user_name = (string)$customer_detail['userName'];
            if (empty($curr_user_id)) {
                $user_name = '游客';
            }
            $customer_detail['userId'] = $user_id;
            $customer_detail['userName'] = $user_name;
            $curr_sales_order_num = array();//当前商品订单数量
            $curr_goods_sale_num = 0;//当前商品销售数量
            $curr_goods_sale_money = 0;//当前商品销售金额
            $curr_goods_cost_money = 0;//当前商品销售成本
            $curr_goods_return_num = 0;//当前商品退货数量
            $curr_goods_return_money = 0;//当前商品退货金额
            $curr_goods_return_cost_money = 0;//当前商品退货成本金额
            $curr_goods_exchange_input_num = 0;//当前商品换货-退回数量
            $curr_goods_exchange_out_num = 0;//当前商品换货-换出数量
            $curr_goods_exchange_diff_money = 0;//当前商品换货-换货补差价金额
            $curr_goods_exchange_money = 0;//当前商品换货补差价金额
            $curr_goods_exchange_cost_money = 0;//当前商品换货补差价成本金额
            $curr_actual_money = 0;//当前商品实际金额
            $curr_actual_cost_money = 0;//当前商品实际成本金额
            $curr_gross_profit = 0;//当前商品毛利
            $curr_gross_profit_rate = 0;//当前商品毛利率
            foreach ($relation_list as $relation_detail) {
                $current_user_id = (int)$relation_detail['user_id'];
                if ($user_id != $current_user_id) {
                    continue;
                }
                $curr_sales_order_num[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (int)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (int)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (int)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (int)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $curr_actual_money = ((float)$curr_goods_sale_money - (float)$curr_goods_return_money + $curr_goods_exchange_money);//实际金额 = 销售金额 - 退货金额 + 换货补差价金额
                $curr_actual_cost_money = ((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money + $curr_goods_exchange_cost_money);//实际成本 = 销售商品成本金额 - 退货成本金额 + 换货补差价成本金额;
                $curr_gross_profit = $curr_actual_money - $curr_goods_cost_money;//毛利 = 实际金额 - 实际成本
                $curr_gross_profit_rate = (bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
                $sales_order_num[] = $relation_detail['order_id'];

            }
            $customer_detail['sales_order_num'] = formatAmount(count(array_unique($curr_sales_order_num)), 0);//当前商品订单数量
            $customer_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);//当前商品销售数量
            $customer_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);//当前商品销售金额
            $customer_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);//当前商品成本金额
            $customer_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);//当前商品退货数量
            $customer_detail['goods_return_money'] = formatAmount($curr_goods_return_money);//当前商品退货金额
            $customer_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);//当前商品退货成本金额
            $customer_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);//当前商品换货-退回数量
            $customer_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);//当前商品换货-换出数量
            $customer_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);//当前商品换货金额
            $customer_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);//当前商品换货补差价成本金额
            $customer_detail['actual_money'] = formatAmount($curr_actual_money);//当前商品实际金额
            $customer_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);//当前商品实际成本金额
            $customer_detail['gross_profit'] = formatAmount($curr_gross_profit);//当前商品毛利
            $customer_detail['gross_profit_rate'] = $curr_gross_profit_rate;//当前商品毛利率
            $goods_sale_num += (int)$customer_detail['goods_sale_num'];
            $goods_sale_money += (float)$customer_detail['goods_sale_money'];
            $goods_cost_money += (float)$customer_detail['goods_cost_money'];
            $goods_return_num += (int)$customer_detail['goods_return_num'];
            $goods_return_money += (float)$customer_detail['goods_return_money'];
            $goods_return_cost_money += (float)$customer_detail['goods_return_cost_money'];
            $goods_exchange_input_num += (int)$customer_detail['goods_exchange_input_num'];
            $goods_exchange_out_num += (int)$customer_detail['goods_exchange_out_num'];
            $goods_exchange_diff_money += (float)$customer_detail['goods_exchange_diff_money'];
            $goods_exchange_cost_money += (float)$customer_detail['goods_exchange_cost_money'];
            $actual_money += (float)$customer_detail['actual_money'];
            $actual_cost_money += (float)$customer_detail['actual_cost_money'];
            $gross_profit += (float)$customer_detail['gross_profit'];
        }
        unset($customer_detail);

        $sum = array(
            'sales_order_num' => formatAmount(count(array_unique($sales_order_num)), 0),//销售订单数量
            'goods_sale_num' => formatAmount($goods_sale_num, 0),//销售商品数量
            'goods_sale_money' => formatAmount($goods_sale_money),//销售商品金额
            'goods_cost_money' => formatAmount($goods_cost_money),//销售成本
            'goods_return_num' => formatAmount($goods_return_num, 0),//商品退货数量
            'goods_return_money' => formatAmount($goods_return_money),//商品退货金额
            'goods_return_cost_money' => formatAmount($goods_return_cost_money),//商品退货成本金额
            'goods_exchange_input_num' => formatAmount($goods_exchange_input_num, 0),//商品换货-退回数量
            'goods_exchange_out_num' => formatAmount($goods_exchange_out_num, 0),//商品换货-换出数量
            'goods_exchange_diff_money' => formatAmount($goods_exchange_diff_money, 0),//商品换货补差价金额
            'goods_exchange_cost_money' => formatAmount($goods_exchange_cost_money),//商品换货补差价成本金额
            'actual_money' => formatAmount($actual_money),//实际金额
            'actual_cost_money' => formatAmount($actual_cost_money),//实际成本
            'gross_profit' => formatAmount($gross_profit)//毛利
        );
        if ($model_type == 1) {//列表模式
            $customer_data['root'] = $customer_list;
            $customer_data['sum'] = $sum;
            $response_data = $customer_data;
        } else {//图表模式
            $response_data = $sum;
        }
        return $response_data;
    }

    /**
     * 销售毛利-客户详情 PS:仅支持按商品统计
     * @param array $params <p>
     * int shop_id 门店id
     * date start_date 开始日期
     * date end_date 结束
     * int goods_id 商品id
     * int page 页码
     * int page_size 分页条数
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function saleGrossProfitCustomerDetail(array $params)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $shop_id = (int)$params['shop_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $goods_id = (int)$params['goods_id'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $where = array(
            'relation.shop_id' => $shop_id,
            'relation.report_date' => array('between', array("{$start_date}", "{$end_date}")),
            'relation.goods_id' => $goods_id,
//            'relation.data_type' => array('IN', array(1, 4, 5)),
            'relation.data_type' => 1,
            'relation.is_delete' => 0,
        );
        $field = 'users.userId,users.userName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('relation.user_id')
            ->buildSql();
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $field .= ',users.userId,users.userName';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($customer_list as &$customer_detail) {
            $user_id = (int)$customer_detail['userId'];
            $user_name = (string)$customer_detail['userName'];
            if (empty($user_id)) {
                $user_name = '游客';
            }
            $customer_detail['userId'] = $user_id;
            $customer_detail['userName'] = $user_name;
            $curr_goods_sale_num = 0;//销售商品数量
            $curr_goods_sale_money = 0;//销售商品金额
            $curr_goods_cost_money = 0;//销售商品成本金额
            $curr_goods_return_num = 0;//退货数量
            $curr_goods_return_money = 0;//退货金额
            $curr_goods_return_cost_money = 0;//退货成本金额
            $curr_goods_exchange_input_num = 0;//换货-退回数量
            $curr_goods_exchange_out_num = 0;//换货-换出数量
            $curr_goods_exchange_diff_money = 0;//换货补差价金额
            $curr_goods_exchange_cost_money = 0;//换货补差价成本金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['user_id'] != $user_id) {
                    continue;
                }
                $sales_order_id_arr[] = $relation_detail['order_id'];
                $curr_goods_sale_num += (float)$relation_detail['goods_num'];
                $curr_goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $curr_goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $curr_goods_return_num += (float)$relation_detail['return_goods_num'];
                $curr_goods_return_money += (float)$relation_detail['refund_money'];
                $curr_goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $curr_goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $curr_goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $curr_goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $curr_goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
            }
            $curr_sales_order_num = count(array_unique($sales_order_id_arr));//销售订单数量
            $curr_actual_money = (float)(((float)$curr_goods_sale_money - (float)$curr_goods_return_money) + (float)$curr_goods_exchange_diff_money);//实际金额 = 商品销售金额 - 商品退货金额 + 商品换货补差价金额
            $curr_actual_cost_money = (float)(((float)$curr_goods_cost_money - (float)$curr_goods_return_cost_money) + (float)$curr_goods_exchange_cost_money);//实际成本金额 = 商品成本金额 - 退货成本金额 + 换货补差价成本金额
            $curr_gross_profit = (float)bc_math($curr_actual_money, $curr_actual_cost_money, 'bcsub', 4);//毛利 = 实际金额 - 实际成本
            $curr_gross_profit_rate = ((float)bc_math($curr_gross_profit, $curr_actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $customer_detail['userId'] = formatAmount($user_id, 0);
            $customer_detail['sales_order_num'] = formatAmount($curr_sales_order_num, 0);
            $customer_detail['goods_sale_num'] = formatAmount($curr_goods_sale_num, 0);
            $customer_detail['goods_sale_money'] = formatAmount($curr_goods_sale_money);
            $customer_detail['goods_cost_money'] = formatAmount($curr_goods_cost_money);
            $customer_detail['goods_return_num'] = formatAmount($curr_goods_return_num, 0);
            $customer_detail['goods_return_money'] = formatAmount($curr_goods_return_money);
            $customer_detail['goods_return_cost_money'] = formatAmount($curr_goods_return_cost_money);
            $customer_detail['goods_exchange_input_num'] = formatAmount($curr_goods_exchange_input_num, 0);
            $customer_detail['goods_exchange_out_num'] = formatAmount($curr_goods_exchange_out_num, 0);
            $customer_detail['goods_exchange_diff_money'] = formatAmount($curr_goods_exchange_diff_money);
            $customer_detail['goods_exchange_cost_money'] = formatAmount($curr_goods_exchange_cost_money);
            $customer_detail['actual_money'] = formatAmount($curr_actual_money);
            $customer_detail['actual_cost_money'] = formatAmount($curr_actual_cost_money);
            $customer_detail['gross_profit'] = formatAmount($curr_gross_profit);
            $customer_detail['gross_profit_rate'] = $curr_gross_profit_rate;

        }
        unset($customer_detail);
        $goods_module = new GoodsModule();
        $goods_cat_module = new GoodsCatModule();
        $field = 'goodsId,goodsName,goodsSn,goodsCatId1,goodsCatId2,goodsCatId3';
        $goods_detail = $goods_module->getGoodsInfoById($goods_id, $field, 2);
        $goods_detail['goodsCatName1'] = '';
        $goods_detail['goodsCatName2'] = '';
        $goods_detail['goodsCatName3'] = '';
        $goods_cat_id_arr = array($goods_detail['goodsCatId1'], $goods_detail['goodsCatId2'], $goods_detail['goodsCatId3']);
        $goods_cat_list = $goods_cat_module->getGoodsCatListById($goods_cat_id_arr, 'catId,catName');
        foreach ($goods_cat_list as $cat_detail) {
            if ($cat_detail['catId'] == $goods_detail['goodsCatId1']) {
                $goods_detail['goodsCatName1'] = $cat_detail['catName'];
            }
            if ($cat_detail['catId'] == $goods_detail['goodsCatId2']) {
                $goods_detail['goodsCatName2'] = $cat_detail['catName'];
            }
            if ($cat_detail['catId'] == $goods_detail['goodsCatId3']) {
                $goods_detail['goodsCatName3'] = $cat_detail['catName'];
            }
        }
        $customer_data['root'] = $customer_list;
        $customer_data['goods_detail'] = $goods_detail;
        if ($params['export'] == 1) {//导出
            $this->exportSaleGrossProfitCustomerDetail($customer_data);
        }
        return (array)$customer_data;
    }

    /**
     * 销售毛利-客户详情-导出
     * @param array $response_data 业务数据
     * */
    public function exportSaleGrossProfitCustomerDetail(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '销售毛利-客户详情';
        $excel_filename = '销售毛利-客户详情' . date('YmdHis');
//        $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
//        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P');
        $sheet_title = array('客户名称', '订单数量', '销售数量', '销售金额', '成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
//                switch ($j) {
//                    case 0:
//                        $cellvalue = $detail['userName'];//客户名称
//                        break;
//                    case 1:
//                        $cellvalue = $detail['sales_order_num'];//订单数量
//                        break;
//                    case 2:
//                        $cellvalue = $detail['goods_sale_num'];//销售数量
//                        break;
//                    case 3:
//                        $cellvalue = $detail['goods_sale_money'];//销售金额
//                        break;
//                    case 4:
//                        $cellvalue = $detail['goods_cost_money'];//成本金额
//                        break;
//                    case 5:
//                        $cellvalue = $detail['goods_return_num'];//退货数量
//                        break;
//                    case 6:
//                        $cellvalue = $detail['goods_return_money'];//退货金额
//                        break;
//                    case 7:
//                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
//                        break;
//                    case 8:
//                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
//                        break;
//                    case 9:
//                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
//                        break;
//                    case 10:
//                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
//                        break;
//                    case 11:
//                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
//                        break;
//                    case 12:
//                        $cellvalue = $detail['actual_money'];//实际金额
//                        break;
//                    case 13:
//                        $cellvalue = $detail['actual_cost_money'];//实际成本金额
//                        break;
//                    case 14:
//                        $cellvalue = $detail['gross_profit'];//毛利
//                        break;
//                    case 15:
//                        $cellvalue = $detail['gross_profit_rate'];//毛利率
//                        break;
//                }

                switch ($j) {
                    case 0:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 1:
                        $cellvalue = $detail['sales_order_num'];//订单数量
                        break;
                    case 2:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 3:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 4:
                        $cellvalue = $detail['goods_cost_money'];//成本金额
                        break;
                    case 5:
                        $cellvalue = $detail['gross_profit'];//毛利
                        break;
                    case 6:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 客户毛利 PS:本质上其实是按照每笔订单来统计的
     * @param array $params <p>
     * int shop_id 门店id
     * date start_date 开始日期
     * date end_date 结束日期
     * string user_name 客户名称
     * string bill_no 单号
     * int page 页码
     * int page_size 分页条数
     * int export 导出(0:不导出 1:导出)
     * </p>
     * @return array
     * */
    public function customerGrossProfit(array $params)
    {
        $shop_id = (int)$params['shop_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $user_name = (string)$params['user_name'];
        $bill_no = (string)$params['bill_no'];
        $page = (int)$params['page'];
        $page_size = (int)$params['page_size'];
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array();
        $where['relation.shop_id'] = $shop_id;
        $where['relation.data_type'] = array('IN', array(1, 4, 5));
        $where['relation.report_date'] = array('between', array($start_date, $end_date));
        $where['relation.is_delete'] = 0;
        if (!empty($user_name)) {
            if ($user_name == '游客') {
                $where['relation.user_id'] = 0;
            } else {
                $where['users.userName'] = array('like', "%{$user_name}%");
            }
        }
        if (!empty($bill_no)) {
            $where['orders.orderNO'] = array('like', "%{$bill_no}%");
        }
        $field = 'relation.report_date';
        $field .= ',users.userId,users.userName';
        $field .= ',orders.id as order_id,orders.orderNO';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}pos_orders orders on orders.id=relation.order_id")
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->group('relation.order_id')
            ->buildSql();
        if ($params['export'] != 1) {
            $customer_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $customer_data = array();
            $customer_data['root'] = $this->query($sql);
        }
        $customer_list = (array)$customer_data['root'];
        $field = 'relation.order_id,relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $field .= ',users.userId,users.userName';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}pos_orders orders on orders.id=relation.order_id")
            ->join("left join {$table_prefix}users users on users.userId=relation.user_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($customer_list as &$customer_detal) {
            $user_id = (int)$customer_detal['userId'];
            $user_name = (string)$customer_detal['userName'];
            if (empty($user_id)) {
                $user_name = '游客';
            }
            $customer_detal['userId'] = formatAmount($user_id, 0);
            $customer_detal['userName'] = $user_name;
            $order_id = $customer_detal['order_id'];
            $goods_sale_num = 0;//商品销售数量
            $goods_sale_money = 0;//商品销售金额
            $goods_cost_money = 0;//销售成本金额
            $goods_return_num = 0;//退货数量
            $goods_return_money = 0;//退回金额
            $goods_return_cost_money = 0;//退回成本金额
            $goods_exchange_input_num = 0;//换货-退回数量
            $goods_exchange_out_num = 0;//换货-换出数量
            $goods_exchange_diff_money = 0;//换货-换货补差价金额
            $goods_exchange_cost_money = 0;//换货-换货补差价成本金额
            $actual_money = 0;//实际金额 = (销售金额-退货金额) + 补差价金额
            $actual_cost_money = 0;//实际成本金额 (实际成本-退货成本) + 换货补差价成本
//            $gross_profit = '';//毛利 = 实际金额 - 实际成本
//            $gross_profit_rate = '';//毛利率=毛利/营业收入×100%
            foreach ($relation_list as $relation_detail) {
                $current_order_id = $relation_detail['order_id'];
                if ($order_id != $current_order_id) {
                    continue;
                }
                $goods_sale_num += (float)$relation_detail['goods_num'];
                $goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $goods_return_num += (float)$relation_detail['return_goods_num'];
                $goods_return_money += (float)$relation_detail['refund_money'];
                $goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
                $actual_money += (float)(((float)$goods_sale_money - (float)$goods_return_money) + (float)$goods_exchange_diff_money);
                $actual_cost_money += (float)(((float)$goods_cost_money - (float)$goods_return_cost_money) + (float)$goods_exchange_cost_money);
            }
            $gross_profit = (float)$actual_money - (float)$actual_cost_money;//毛利 = 实际金额 - 实际成本
            $gross_profit_rate = ((float)bc_math($gross_profit, $actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $customer_detal['goods_sale_num'] = formatAmount($goods_sale_num, 0);
            $customer_detal['goods_sale_money'] = formatAmount($goods_sale_money);
            $customer_detal['goods_cost_money'] = formatAmount($goods_cost_money);
            $customer_detal['goods_return_num'] = formatAmount($goods_return_num, 0);
            $customer_detal['goods_return_money'] = formatAmount($goods_return_money);
            $customer_detal['goods_return_cost_money'] = formatAmount($goods_return_cost_money);
            $customer_detal['goods_exchange_input_num'] = formatAmount($goods_exchange_input_num, 0);
            $customer_detal['goods_exchange_out_num'] = formatAmount($goods_exchange_out_num, 0);
            $customer_detal['goods_exchange_diff_money'] = formatAmount($goods_exchange_diff_money);
            $customer_detal['goods_exchange_cost_money'] = formatAmount($goods_exchange_cost_money);
            $customer_detal['actual_money'] = formatAmount($actual_money);
            $customer_detal['actual_cost_money'] = formatAmount($actual_cost_money);
            $customer_detal['gross_profit'] = formatAmount($gross_profit);
            $customer_detal['gross_profit_rate'] = $gross_profit_rate;
        }
        unset($customer_detal);
        $customer_data['root'] = $customer_list;
        if ($params['export'] == 1) {//导出
            $this->exportCustomerGrossProfit($customer_data);
        }
        return $customer_data;
    }

    /**
     * 客户毛利-导出
     * @param array $response_data 业务参数
     * */
    public function exportCustomerGrossProfit(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户毛利';
        $excel_filename = '客户毛利' . date('YmdHis');
        $sheet_title = array('日期', '客户名称', '订单号', '销售数量', '销售金额', '销售成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['report_date'];//日期
                        break;
                    case 1:
                        $cellvalue = $detail['userName'];//客户名称
                        break;
                    case 2:
                        $cellvalue = $detail['orderNO'];//订单号
                        break;
                    case 3:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 4:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 5:
                        $cellvalue = $detail['goods_cost_money'];//销售成本金额
                        break;
                    case 6:
                        $cellvalue = $detail['goods_return_num'];//退货数量
                        break;
                    case 7:
                        $cellvalue = $detail['goods_return_money'];//退货金额
                        break;
                    case 8:
                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
                        break;
                    case 9:
                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
                        break;
                    case 10:
                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
                        break;
                    case 11:
                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
                        break;
                    case 12:
                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
                        break;
                    case 13:
                        $cellvalue = $detail['actual_money'];//实际金额
                        break;
                    case 14:
                        $cellvalue = $detail['actual_money'];//实际成本金额
                        break;
                    case 15:
                        $cellvalue = $detail['actual_money'];//毛利
                        break;
                    case 16:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    /**
     * 客户毛利-详情
     * @param int $order_id 订单id
     * @param int $page 页码
     * @param int $page_size 分页条数
     * @param int $export 导出(0:导出 1:不导出)
     * @return array
     * */
    public function customerGrossProfitToDetail(int $order_id, int $page, int $page_size, int $export)
    {
        $relation_model = new PosReportRelationModel();
        $table_prefix = $relation_model->tablePrefix;
        $where = array(
            'relation.order_id' => $order_id
        );
        $field = 'goods.goodsId,goods.goodsName';
        $sql = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->group('relation.goods_id')
            ->buildSql();
        if ($export != 1) {
            $goods_data = $this->pageQuery($sql, $page, $page_size);
        } else {
            $goods_data = $this->query($sql);
        }
        $goods_list = (array)$goods_data['root'];
        $field = 'relation.order_id,relation.report_date,relation.user_id,relation.goods_id,relation.order_id,relation.goods_num,relation.goods_cost_price,relation.goods_cost_price_total,relation.goods_paid_price,relation.goods_paid_price_total,relation.is_refund,relation.refund_money,relation.refund_cost_money,relation.is_return_goods,relation.return_goods_num,relation.is_exchange_goods,relation.exchange_goods_input_num,relation.exchange_goods_out_num,relation.exchange_diff_price,relation.exchange_diff_cost_price';
        $relation_list = $relation_model
            ->alias('relation')
            ->join("left join {$table_prefix}goods goods on goods.goodsId=relation.goods_id")
            ->where($where)
            ->field($field)
            ->select();//报表明细
        foreach ($goods_list as &$goods_detail) {
            $goods_sale_num = 0;//销售数量
            $goods_sale_money = 0;//销售金额
            $goods_cost_money = 0;//成本金额
            $goods_return_num = 0;//退货数量
            $goods_return_money = 0;//退货金额
            $goods_return_cost_money = 0;//退货成本金额
            $goods_exchange_input_num = 0;//换货-退回数量
            $goods_exchange_out_num = 0;//换货-换出数量
            $goods_exchange_diff_money = 0;//换货-换货补差价金额
            $goods_exchange_cost_money = 0;//换货-换货补差价成本金额
            foreach ($relation_list as $relation_detail) {
                if ($relation_detail['goods_id'] != $goods_detail['goodsId']) {
                    continue;
                }
                $goods_sale_num += (float)$relation_detail['goods_num'];
                $goods_sale_money += (float)$relation_detail['goods_paid_price_total'];
                $goods_cost_money += (float)$relation_detail['goods_cost_price_total'];
                $goods_return_num += (float)$relation_detail['return_goods_num'];
                $goods_return_money += (float)$relation_detail['refund_money'];
                $goods_return_cost_money += (float)$relation_detail['refund_cost_money'];
                $goods_exchange_input_num += (float)$relation_detail['exchange_goods_input_num'];
                $goods_exchange_out_num += (float)$relation_detail['exchange_goods_out_num'];
                $goods_exchange_diff_money += (float)$relation_detail['exchange_diff_price'];
                $goods_exchange_cost_money += (float)$relation_detail['exchange_diff_cost_price'];
            }
            $actual_money = (float)(((float)$goods_sale_money - (float)$goods_return_money) + (float)$goods_exchange_diff_money);//实际金额 = (销售金额 - 退货金额) + 换货补差价金额
            $actual_cost_money = (float)(((float)$goods_cost_money - (float)$goods_return_cost_money) + (float)$goods_exchange_cost_money);//实际成本金额 = (销售成本金额 - 退货成本金额) + 换货补差价成本金额
            $gross_profit = (float)$actual_money - (float)$actual_cost_money;//毛利 = 实际金额 - 实际成本
            $gross_profit_rate = ((float)bc_math($gross_profit, $actual_money, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/营业收入×100%
            $goods_detail['goods_sale_num'] = formatAmount($goods_sale_num, 0);
            $goods_detail['goods_sale_money'] = formatAmount($goods_sale_money);
            $goods_detail['goods_cost_money'] = formatAmount($goods_cost_money);
            $goods_detail['goods_return_num'] = formatAmount($goods_return_num, 0);
            $goods_detail['goods_return_money'] = formatAmount($goods_return_money);
            $goods_detail['goods_return_cost_money'] = formatAmount($goods_return_cost_money);
            $goods_detail['goods_exchange_input_num'] = formatAmount($goods_exchange_input_num, 0);
            $goods_detail['goods_exchange_out_num'] = formatAmount($goods_exchange_out_num, 0);
            $goods_detail['goods_exchange_diff_money'] = formatAmount($goods_exchange_diff_money);
            $goods_detail['goods_exchange_cost_money'] = formatAmount($goods_exchange_cost_money);
            $goods_detail['actual_money'] = formatAmount($actual_money);
            $goods_detail['actual_cost_money'] = formatAmount($actual_cost_money);
            $goods_detail['gross_profit'] = formatAmount($gross_profit);
            $goods_detail['gross_profit_rate'] = $gross_profit_rate;
        }
        unset($goods_detail);
        $goods_data['root'] = $goods_list;
        if ($export == 1) {
            $this->exportCustomerGrossProfitToDetail($goods_data);
        }
        return $goods_data;
    }

    /**
     * 客户毛利-详情-导出
     * @param array $response_data 业务数据
     * */
    public function exportCustomerGrossProfitToDetail(array $response_data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户毛利';
        $excel_filename = '客户毛利-详情' . date('YmdHis');
        $sheet_title = array('商品名称', '销售数量', '销售金额', '销售成本金额', '退货数量', '退货金额', '退货成本金额', '换货-退回数量', '换货-换出数量', '换货-换货补差价金额', '换货-换货补差价成本金额', '实际金额', '实际成本金额', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O');
        $list = $response_data['root'];
        for ($i = 0; $i < count($list) + 1; $i++) {
            $row = $i + 3;//前两行为标题和表头,第三行开始为数据
            $detail = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                $cellvalue = '';
                switch ($j) {
                    case 0:
                        $cellvalue = $detail['goodsName'];//商品名称
                        break;
                    case 1:
                        $cellvalue = $detail['goods_sale_num'];//销售数量
                        break;
                    case 2:
                        $cellvalue = $detail['goods_sale_money'];//销售金额
                        break;
                    case 3:
                        $cellvalue = $detail['goods_cost_money'];//销售成本金额
                        break;
                    case 4:
                        $cellvalue = $detail['goods_return_num'];//退货数量
                        break;
                    case 5:
                        $cellvalue = $detail['goods_return_money'];//退货金额
                        break;
                    case 6:
                        $cellvalue = $detail['goods_return_cost_money'];//退货成本金额
                        break;
                    case 7:
                        $cellvalue = $detail['goods_exchange_input_num'];//换货-退回数量
                        break;
                    case 8:
                        $cellvalue = $detail['goods_exchange_out_num'];//换货-换出数量
                        break;
                    case 9:
                        $cellvalue = $detail['goods_exchange_diff_money'];//换货-换货补差价金额
                        break;
                    case 10:
                        $cellvalue = $detail['goods_exchange_cost_money'];//换货-换货补差价成本金额
                        break;
                    case 11:
                        $cellvalue = $detail['actual_money'];//实际金额
                        break;
                    case 12:
                        $cellvalue = $detail['actual_cost_money'];//实际成本金额
                        break;
                    case 13:
                        $cellvalue = $detail['gross_profit'];//毛利
                        break;
                    case 14:
                        $cellvalue = $detail['gross_profit_rate'];//毛利率
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportExcelPublic($title, $excel_filename, $sheet_title, $letter, $objPHPExcel);
    }

    #############################收银报表-end###################################

    /**
     * 获取PLU商品列表
     * @param array $params <p>
     * int shop_id 门店id
     * string keywords 关键字(商品名称/编码/PLU编码)
     * int shopCatId1 门店一级分类id
     * int shopCatId2 门店二级分类id
     * </p>
     * @return array
     * */
    public function getPluGoodsList($params = array(), int $page, int $page_size)
    {
        $shop_id = $params['shop_id'];
        $where = " shopId={$shop_id} and goodsFlag=1 and isBecyclebin=0 and plu_code != '' ";
        $where_find['shopCatId1'] = function () use ($params) {
            if (empty($params['shopCatId1'])) {
                return null;
            }
            return array('=', "{$params['shopCatId1']}", 'and');
        };
        $where_find['shopCatId2'] = function () use ($params) {
            if (empty($params['shopCatId2'])) {
                return null;
            }
            return array('=', "{$params['shopCatId2']}", 'and');
        };
        $where_find['SuppPriceDiff'] = function () use ($params) {
            if (empty($params['SuppPriceDiff'])) {
                return null;
            }
            return array('=', "{$params['SuppPriceDiff']}", 'and');
        };
        where($where_find);
        $where_find = rtrim($where_find, ' and');
        if (empty($where_find) || $where_find == ' ') {
            $where_find = $where;
        } else {
            $where_find = "{$where} and {$where_find}";
        }
        if (!empty($params['keywords'])) {
            $keywords = $params['keywords'];
            $where_find .= " and ((goodsSn like '%{$keywords}%') or (goodsName like '%{$keywords}%') or (plu_code like '%{$keywords}%')) ";
        }
        $goods_model = new GoodsModel();
        // $goods_list = $goods_model
        //     ->where($where_find)
        //     ->order('shopGoodsSort desc')
        //     ->select();
        $goods_list_sql = $goods_model
            ->where($where_find)
            ->order('shopGoodsSort desc')
            ->buildSql();

        $goods_list_obj = $this->pageQuery($goods_list_sql, $page, $page_size);
        $goods_list = (array)$goods_list_obj['root'];

        if (!empty($goods_list)) {
            $shop_cat_module = new ShopCatsModule();
            $shop_cat_id_arr = array();//门店分类id
            foreach ($goods_list as $goods_detail) {
                $shop_cat_id_arr[] = $goods_detail['shopCatId1'];
                $shop_cat_id_arr[] = $goods_detail['shopCatId2'];
            }
            $shop_cat_id_arr = array_unique($shop_cat_id_arr);
            $shop_cat_id_str = implode(',', $shop_cat_id_arr);
            $shop_cat_list = $shop_cat_module->getShopCatListById($shop_cat_id_str, 'catId,catName', 2);
            foreach ($goods_list as &$goods_detail) {
                $goods_detail['shopCatId1Name'] = '';
                $goods_detail['shopCatId2Name'] = '';
                foreach ($shop_cat_list as $cat_detail) {
                    if ($goods_detail['shopCatId1'] == $cat_detail['catId']) {
                        $goods_detail['shopCatId1Name'] = $cat_detail['catName'];
                    }
                    if ($goods_detail['shopCatId2'] == $cat_detail['catId']) {
                        $goods_detail['shopCatId2Name'] = $cat_detail['catName'];
                    }
                }
                //为了PLU同步工具正确性 这里不再处理转换了 PLU文件可能会出错 如果出错请另外单独处理不要影响该接口 不再推荐使用PLU文件形式
                // $shop_price = (float)$goods_detail['shopPrice'];//单品价格
                // if ($goods_detail['SuppPriceDiff'] == 1) {//称重商品
                //     //根据包装系数计算500g的单价
                //     $weight_g = (float)$goods_detail['weightG'];//包装系数
                //     $g_price = (float)$shop_price / (float)$weight_g;
                //     $piece_price = $g_price * 500;
                //     $piece_price = sprintf("%.2f", $piece_price);
                // } else {//标品
                //     $piece_price = $goods_detail['shopPrice'];
                // }
                // $goods_detail['piece_price'] = $piece_price;//称重商品(500g/元) 或 标品(件/元)
            }
            unset($goods_detail);
        }
        return (array)$goods_list_obj;
    }
}

?>

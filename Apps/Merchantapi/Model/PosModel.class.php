<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 收银服务类 主要移植到了 Apps\Merchantapi\Model\CashierModel.class.php
 */
class PosModel extends BaseModel
{
    /**
     * 开单
     */
    public function orderCreate($shopInfo)
    {
        //年月日时分秒+收银员id+门店id+增量订单id

        $mod_pos_orders = M('pos_orders');

        $mod_pos_order_id = M('pos_order_id')->add(array('id' => ''));//获取增量id
        $time = date('YmdHis');
        $userid = $shopInfo['userId'];
        $shopid = $shopInfo['shopId'];

        // 拼接订单号
        $orderNO = $time . $userid . $shopid . $mod_pos_order_id;
        // 创建订单
        $addata['orderNO'] = $orderNO;
        $addata['addtime'] = date('Y-m-d H:i:s');
        $addata['shopId'] = $shopid;


        if ($mod_pos_orders->add($addata)) {
            return $orderNO;
        } else {
            return false;
        }
    }

    /**
     * 搜索商品
     */
    public function search($shopInfo, $str)
    {
        //goodsType商品类型目前未在数据库中体现 现在临时增加处理 【1：预打包商品】 后期促销商品可能也会用到该字段或类似字段
        //现在就是两个值 是否是预打包商品 1是 -1不是


        //1.本地预打包和系统预打包 只要保持一致的数据即可 前端不需要额外兼容再次改动========
        //2.称重重量结果将kg转换为g方便前端临时计算 因为前端目前临时称重会转换为g来使用 无论是本地预打包还是系统预打包 为保证前端逻辑一致

        //前端只需要处理 标品 和 非标品【临时称重】 转标品【本地预打包、系统预打包】三种类型即可

        $mod_goods = M('goods');
        //构建查询条件
        $where['goodsName|goodsSn'] = array('like', "%{$str}%");
        //$where['goodsSn']=$str;
        $where['shopId'] = $shopInfo['shopId'];
        // $where['isSale']=1;//不用管是否已上架
        // $where['goodsStatus']=1;//线下不用管是否已审核
        $where['goodsFlag'] = 1;
//    $where['SuppPriceDiff']=-1;//标品
        $data = $mod_goods->where($where)->limit(100)->select();//不分页 最多一百条
        if (!empty($data)) {
            $data = rankGoodsPrice($data);
            $data = getGoodsSku($data);
            setObj($data, "goodsType", -1, null);//设置不是预打包商品
            return $data;
        }

        //通过指定sku编码获取商品信息
        $skuBarcode['sgs.skuBarcode'] = $str;
        $skuBarcode['sgs.dataFlag'] = 1;
        $skuGoodsInfo = M('sku_goods_system sgs')->join('left join wst_goods g on g.goodsId = sgs.goodsId')->where($skuBarcode)->limit(1)->field('g.*')->select();
        if (!empty($skuGoodsInfo)) {
            $skuGoodsInfo = rankGoodsPrice($skuGoodsInfo);
            $skuGoodsInfo = getGoodsSku($skuGoodsInfo, $str);
            $skuGoodsInfo[0]['hasGoodsSku'] = 0;
            $systemSpec = $skuGoodsInfo[0]['goodsSku']['skuList'][0]['systemSpec'];
            $skuId = $skuGoodsInfo[0]['goodsSku']['skuList'][0]['skuId'];
            $skuGoodsInfo[0]['goodsSku']['systemSpec'] = $systemSpec;
            $skuGoodsInfo[0]['skuId'] = $skuId;

            return $skuGoodsInfo;
        }

        //判断是否是线下称重商品 如果是从条码称重表取数据 包含CZ-即为查询称重商品
        if (strpos($str, 'CZ-') !== false) {//是称重商品
//    $mod_pos_barcode = M('barcode');
//    $retdata = $mod_pos_barcode->where("barcode = '".$str."'")->find();
            $retdata = M('barcode as b')->join('left join wst_goods as g on b.goodsId = g.goodsId')->where("b.barcode = '" . $str . "'")->find();
            $retdata = getGoodsSku([$retdata],$str);//后加sku处理
            //查询库存
//    $retdata['goodsStock'] = $mod_goods->where("goodsId = ".$retdata['goodsId'])->find()['goodsStock'];
//      $goods_info = $mod_goods->where("goodsId = ".$retdata['goodsId'])->find();
            //   $retdata['goodsStock'] = $goods_info['goodsStock'];
            //   $retdata['SuppPriceDiff'] = $goods_info['SuppPriceDiff'];
//      $goods_info['barcode'] = $retdata['barcode'];
//      $goods_info['weight'] = $retdata['weight'];
            setObj($retdata, "goodsType", 1, null);//设置是预打包商品
            setObj($retdata, "SuppPriceDiff", -1, null);//如果为预打包商品改为 -1
            

            //替换店铺价格
            setObj($retdata, "price", null, function ($k, $v)use(&$retdata) {
                setObj($retdata, "shopPrice", (float)$v, null);
                return (float)$v;
            });
            //替换goodsSn
            setObj($retdata, "barcode", null, function ($k, $v)use(&$retdata) {
                setObj($retdata, "goodsSn", $v, null);
                return $v;
            });

            //处理重量kg转换为g
            setObj($retdata, "weight", null, function ($k, $v) {
                return (float)$v * 1000;

            });
            return $retdata;
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
        if (strlen($str) != 18) {
            return [];
        }

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

                //   setObj($retdata,"goodsSku",['skuList'=>[],'skuSpec'=>[]] ,null);

                $retdata = getGoodsSku([$retdata]);//后加sku处理
                //增加字段 保证与系统预打包数据结构一致
                

                // 单位为G
                setObj($retdata, "weight", $codeN, null);

                setObj($retdata, "SuppPriceDiff", -1, null);//如果为预打包商品改为 -1

                setObj($retdata, "goodsType", 1, null);//设置是预打包商品

                //称重结果金额分转为元
                setObj($retdata, "price", $codeE / 100);

                setObj($retdata, "shopPrice", $codeE/100, null);//替换店铺价格

                //替换goodsSn
                setObj($retdata, "goodsSn",$str, null);

                return $retdata;
            }


            return [];


            //该逻辑貌似没有用处的样子？huihui-20200829
            $str_7 = intval(substr($str, 0, -6));//商品条码
            $where = array();
            $where['goodsSn'] = $str_7;
            $where['shopId'] = $shopInfo['shopId'];
            $where['goodsFlag'] = 1;
            $retdata = $mod_goods->where($where)->find();
            if (!empty($retdata)) {
                $money = intval(substr($str, -6, -1)) / 100;
                $weight = number_format($money / ($retdata['shopPrice'] / $retdata['weightG']), 3);
                $retdata['weight'] = $weight;
                $retdata['price'] = $money;
                $retdata['barcode'] = $str_7;
                $retdata['skuId'] = 0;
                $retdata['goodsSku'] = array(
                    'skuList' => array(),
                    'skuSpecStr' => array(),
                    'systemSpec' => array()
                );
                $retdata = getGoodsSku([$retdata]);//后加sku处理
                // setObj($retdata,"goodsType",-1,null);//设置不是预打包商品
            }


            return $retdata;
        }
    }

    /**
     * 提交订单
     */
    public function submit($shopInfo, $pack)
    {

        $mod_goods = M('goods');
        $mod_pos_orders = M('pos_orders');

        $mod_pos_orders_goods = M('pos_orders_goods');
        $mod_users_dynamiccode = M('users_dynamiccode');
        $mod_users = M('users');

        $mod_user_score = M('user_score');

        //exit(json_encode(explode(':',$GLOBALS['CONFIG']['scoreCashRatio'])));//获取商城信息配置

        $arrs = [];
        $goodsCountNum = 0;
        foreach ($pack['goods'] as $data) {
            //新的 开始
            $where = [];
            $where['g.goodsId'] = $data['goodsId'];
            $where['g.shopId'] = $shopInfo['shopId'];
            $where['g.goodsFlag'] = 1;
            if ($data['skuId'] > 0) {
                $where['sg.skuId'] = $data['skuId'];
                $goodsCount = M('sku_goods_system sg')
                    ->join("left join wst_goods g on g.goodsId=sg.goodsId")
                    ->where($where)
                    ->count('g.goodsId');
            } else {
                $goodsCount = M('goods g')->where($where)->count('goodsId');
            }
            if (is_null($goodsCount)) {
                $goodsCount = 0;
            }
            $goodsCountNum += $goodsCount;
            //新的 结束


            array_push($arrs, (int)$data['goodsId']);
        }

        if ($goodsCountNum != count($pack['goods'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '商品异常';
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            return $apiRet;
        }
        $koId = implode(",", $arrs);//可直接传入数组 这里直接处理成字符串 反正tp框架最后还是要分割
        //判断商品是否是本店的
        /*$where['goodsId'] = array('in',$koId); 原来的
        $where['shopId'] = $shopInfo['shopId'];
        $where['goodsFlag'] = 1;



        if($mod_goods->where($where)->count() != count($pack['goods'])){//节省查询次数
          $rs['status'] = -1;
          $rs['msg'] = '商品异常';
          $rs['data'] = null;
          return $rs;
        }*/

        //判断订单是否 存在 本门店的订单
        if ($mod_pos_orders->where('orderNO=' . $pack['orderNO'])->find()['shopId'] != $shopInfo['shopId']) {

            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '订单非本门店订单或者订单不存在';
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            return $apiRet;
        }

        $systemSkuTab = M('sku_goods_system');
        //计算商品总金额和总积分，并和传进来的总金额和总积分进行比对 -- start ---
        $total_money = 0;
        $total_score = 0;
        $goodsId_arr = array();
        $replaceSkuField = C('replaceSkuField');//需要被sku属性替换的字段
        foreach ($pack['goods'] as $v) {
            $goods_info = $mod_goods->where(array('goodsId' => $v['goodsId']))->find();
            $discount = empty($v['discount']) ? 1 : $v['discount'] * 10 / 100;
            if ($v['skuId'] > 0) {
                //sku详情
                $systemSkuInfo = $systemSkuTab->where(['skuId' => $v['skuId']])->find();
                foreach ($replaceSkuField as $rk => $rv) {
                    if (isset($v[$rv])) {
                        $v[$rv] = $systemSkuInfo[$rk];
                    }
                }
            }
            if ($v['SuppPriceDiff'] < 0) {//标品
                $money_t = $v['shopPrice'] * $v['number'] * $discount;
            } else if ($v['SuppPriceDiff'] > 0) {//秤重商品
                $money_t = ($v['shopPrice'] / $goods_info['weightG']) * $v['weight'] * $discount;
            }
            $total_money += $money_t;
            $total_score += moneyToIntegral($money_t * $goods_info['integralRate']);
            $goodsId_arr[] = $v['goodsId'];
        }
        $total_money = number_format($total_money, 2);

        //判断商品金额是否一致
        /*    if ($total_money != $pack['realpayment']){
                $rs['status'] = -1;
                $rs['msg'] = '订单总金额不一致';
                $rs['data'] = null;
                return $rs;
            }*/
        /*
            //如果开启积分支付
            if (!empty($GLOBALS['CONFIG']['isOpenScorePay'])) {
                //积分与金钱兑换比例
                if (!empty($GLOBALS['CONFIG']['scoreCashRatio'])) {
                    $scoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
                    $reward_score = ceil($total_money * $scoreCashRatio[0]);
                }
            }*/
        //判断积分是否一致
        /*if ($pack['setintegral'] > $total_score) {
            $rs['status'] = -1;
            $rs['msg'] = '使用积分不能大于商品总积分';
            $rs['data'] = null;
            return $rs;
        }*/
        //计算商品总金额和总积分，并和传进来的总金额和总积分进行比对 -- end ---

        //获取用户信息 并改code为已使用
        /*  if(!empty($pack['userCode'])){
              $userCode = $pack['userCode'];
              $userCode_arr = explode('K',$userCode);
              $userId = intval($userCode_arr[1]);
              if (!empty($userId)) {//实体卡
                  $userinfo = D('Home/Users')->getUserInfoRow(array('userId'=>$userId,'cardNum'=>$userCode_arr[0]));
              } else {//会员个人中心二维码
                  $data = $this->userInfo($userCode);
                  $userinfo = $data['data'];
                  $users_dynamiccode_save['state'] = 1;
                    $mod_users_dynamiccode->where("userId = '{$userinfo['userId']}' and code = '{$pack['userCode']}'")->save($users_dynamiccode_save);
              }
        //    $userinfo = $this->userInfo($shopInfo,$pack['userCode'])['data'];//获取用户信息 已自动判断过期或已使用
        //    $users_dynamiccode_save['state'] = 1;
        //    $mod_users_dynamiccode->where("userId = '{$userinfo['userId']}' and code = '{$pack['userCode']}'")->save($users_dynamiccode_save);
          }*/

        //获取用户信息 并改code为已使用
        if (!empty($pack['memberToken'])) {
            $userinfo = userTokenFind($pack['memberToken'], 86400 * 30);//查询token
        }

        $pack['realpayment'] = $total_money;
        $cash = $pack['cash'];
        $balance = $pack['balance'];
        $unionpay = $pack['unionpay'];
//    $wechat = $pack['wechat'];
//    $alipay = $pack['alipay'];
        $change = $pack['change'];
//    $setintegral = $pack['setintegral'];

        $setintegral = 0;
        $score_money = 0;
        //如果开启积分支付
        /*if (!empty($GLOBALS['CONFIG']['isOpenScorePay'])) {
            //积分与金钱兑换比例
            if (!empty($GLOBALS['CONFIG']['scoreCashRatio'])) {
                $scoreCashRatio = explode(':', $GLOBALS['CONFIG']['scoreCashRatio']);
                $score_money = number_format($total_score / $scoreCashRatio[0],2);
            }
        }*/

        $order_info = $mod_pos_orders->where(array('orderNO' => $pack['orderNO']))->find();
//        $out_trade_no = uniqid().date("YmdHis").mt_rand(100, 1000);
        $out_trade_no = date("YmdHis") . mt_rand(100, 1000);
//        $out_trade_no = joinString($order_info['id'],0,18);
//    $app_money = $total_money-$cash-$balance-$unionpay+$change-$score_money;
        /*if ($pack['pay'] == 4) {//微信支付
            $pack['wechat'] = $app_money;
            $result = $this->doWxPay(1,$pack['auth_code'],$app_money,$pack['orderNO'],$out_trade_no);
        } else if($pack['pay'] == 5) {//支付宝支付
            $pack['alipay'] = $app_money;
            $result = $this->doAliPay(1,$pack['auth_code'],$app_money,$pack['orderNO'],$out_trade_no);
        }*/

        if ($pack['wechat'] > 0) {//微信支付
            $result = $this->doWxPay(1, $pack['auth_code'], $pack['wechat'], $pack['orderNO'], $out_trade_no);
        }
        if ($pack['alipay'] > 0) {//支付宝支付
            $result = $this->doAliPay(1, $pack['auth_code'], $pack['alipay'], $pack['orderNO'], $out_trade_no);
        }

        if ($result['apiCode'] == -1) {//支付失败
            return $result;
        }
        $mod_pos_orders->where(array('orderNO' => $pack['orderNO']))->save(array('outTradeNo' => $out_trade_no));

        $cash_new = $pack['cash'] - $pack['change'];
        //使用到了现金，要扣除商家预存款
        if ($cash_new > 0) {
            M('shops')->where(array('shopId' => $shopInfo['shopId']))->setDec('predeposit', $cash_new);
            //写入流水
            $log_sys_moneys_data = array(
                'targetType' => 1,
                'targetId' => $shopInfo['shopId'],
                'dataSrc' => 3,
                'dataId' => $order_info['id'],
                'moneyRemark' => '消费',
                'moneyType' => 0,
                'money' => $cash_new,
                'createTime' => date('Y-m-d H:i:s'),
                'dataFlag' => 1,
                'state' => 1,
                'payType' => 1
            );
            $this->addRechargeLog($log_sys_moneys_data);
        }

        //判断是否使用余额 并用户余额是否够用 扣除余额 写入余额流水
        if (!empty($pack['balance']) && !empty($pack['memberToken']) && !empty($userinfo)) {

            if ((float)$pack['balance'] > (float)$userinfo['balance']) {
                $apiRet['apiCode'] = -1;
                $apiRet['apiInfo'] = '你怎么能大于用户现有余额？';
                $apiRet['apiState'] = 'error';
                $apiRet['apiData'] = [];
                return $apiRet;

//                $rs['status'] = -1;
//                $rs['msg'] = '你怎么能大于用户现有余额？';
//                $rs['data'] = null;
//                return $rs;
            }

            //更改用户余额
            $mod_users->where('userId=' . $userinfo['userId'])->setDec('balance', (float)$pack['balance']);
            //写入余额流水
            $mod_user_balance = M('user_balance');
            $add_user_balance['userId'] = $userinfo['userId'];
            $add_user_balance['balance'] = $pack['balance'];
            $add_user_balance['dataSrc'] = 2;
            $add_user_balance['orderNo'] = $pack['orderNO'];
            $add_user_balance['dataRemarks'] = '线下消费';
            $add_user_balance['balanceType'] = 2;
            $add_user_balance['createTime'] = date('Y-m-d H:i:s');
            $add_user_balance['shopId'] = $shopInfo['shopId'];
            $mod_user_balance->add($add_user_balance);

        }

        //没有开启积分支付
        if (empty($GLOBALS['CONFIG']['isOpenScorePay']) && $pack['setintegral'] > 0) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '没有开启积分支付功能，该笔订单不能使用积分抵扣';
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            return $apiRet;
//            $rs['status'] = -1;
//            $rs['msg'] = '没有开启积分支付功能，该笔订单不能使用积分抵扣';
//            $rs['data'] = null;
//            return $rs;
        }
        //判断是否使用积分抵现且是否code 并依赖比例消费 且用户积分是否充足 写入积分流水
        if (!empty($pack['setintegral']) and !empty($pack['memberToken']) && $GLOBALS['CONFIG']['isOpenScorePay'] == 1 && !empty($userinfo)) {

            if ((int)$pack['setintegral'] > (int)$total_score) {
                $apiRet['apiCode'] = -1;
                $apiRet['apiInfo'] = "当前订单最多可抵扣 " . (int)$total_score . " 积分";
                $apiRet['apiState'] = 'error';
                $apiRet['apiData'] = [];
                return $apiRet;
//                $rs['status'] = -1;
//                $rs['msg'] = "当前订单最多可抵扣 " . (int)$total_score . " 积分";
//                $rs['data'] = null;
//                return $rs;
            }

            if ((int)$pack['setintegral'] > (int)$userinfo['userScore']) {
                $apiRet['apiCode'] = -1;
                $apiRet['apiInfo'] = "你怎么能大于用户现有积分？";
                $apiRet['apiState'] = 'error';
                $apiRet['apiData'] = [];
                return $apiRet;
//                $rs['status'] = -1;
//                $rs['msg'] = '你怎么能大于用户现有积分？';
//                $rs['data'] = null;
//                return $rs;
            }
            //要扣除的积分
            $setintegral = ((int)$pack['setintegral'] > (int)$total_score) ? $total_score : $pack['setintegral'];
            //更改用户积分
            $mod_users->where('userId=' . $userinfo['userId'])->setDec('userScore', $setintegral);
            //写入积分流水
            $mod_user_score_data['userId'] = $userinfo['userId'];
            $mod_user_score_data['score'] = $setintegral;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "积分消费";
            $mod_user_score_data['dataId'] = $order_info['id'];
            $mod_user_score_data['scoreType'] = 2;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $mod_user_score->add($mod_user_score_data);
        }


        //更改订单数据
        $data_where['orderNO'] = $pack['orderNO'];
        $data_where['state'] = 1;//订单未结算
        $data_where['shopId'] = $shopInfo['shopId'];


        $data_save['pay'] = $pack['pay'];
        $data_save['discount'] = $pack['discount'];
        $data_save['discountPrice'] = $pack['discountPrice'];
        $data_save['integral'] = 0;
        $data_save['shopId'] = $shopInfo['shopId'];
        $data_save['state'] = 3;//更改订单 已结算

        //如果开启获取积分 对用户增加积分
        if ($GLOBALS['CONFIG']['isOrderScore'] == 1 && !empty($userinfo)) {
            $reward_score = getOrderScoreByOrderScoreRate($pack['realpayment']);
            M('users')->where("userId = " . $userinfo['userId'])->setInc('userScore', $reward_score);
            $mod_user_score_data = array();
            $mod_user_score_data['userId'] = $userinfo['userId'];
            $mod_user_score_data['score'] = $reward_score;
            $mod_user_score_data['dataSrc'] = 12;
            $mod_user_score_data['dataRemarks'] = "线下购物奖励积分";
            $mod_user_score_data['dataId'] = $order_info['id'];
            $mod_user_score_data['scoreType'] = 1;
            $mod_user_score_data['createTime'] = date('Y-m-d H:i:s');
            $mod_user_score->add($mod_user_score_data);

            $data_save['integral'] = $reward_score;
        }


        $data_save['cash'] = $pack['cash'];
        $data_save['balance'] = $pack['balance'];
        $data_save['unionpay'] = $pack['unionpay'];
        $data_save['wechat'] = $pack['wechat'];
        $data_save['alipay'] = $pack['alipay'];
        $data_save['change'] = $pack['change'];
        $data_save['realpayment'] = $pack['realpayment'];
        $data_save['setintegral'] = $setintegral;
        $data_save['isCombinePay'] = $pack['isCombinePay'];
        $data_save['memberId'] = $userinfo['userId'];

        //添加收银员ID
        if (empty($shopInfo['id'])) { //总管理员
            $data_save['userId'] = $shopInfo['shopId'];
        } else {//其他管理员
            $data_save['userId'] = $shopInfo['id'];
        }


        if (!$mod_pos_orders->where($data_where)->save($data_save)) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = "订单数据异常";
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            return $apiRet;
//            $rs['status'] = -1;
//            $rs['msg'] = '订单数据异常';
//            $rs['data'] = null;

            //return $rs;
        }

        //1:按商品积分抵扣比例计算 2：平摊分配
        $integral_flag = ((int)$pack['setintegral'] < (int)$total_score) ? 2 : 1;

        //计算总积分抵扣比例
        $sum_integralRate = $mod_goods->where(array('goodsId' => array('in', $goodsId_arr)))->sum('integralRate');

        $len = count($pack['goods']);
        //写入商品 更改库存
        for ($i = 0; $i < $len; $i++) {

            //写入商品啊
            $adddata = null;
            $adddata['goodsId'] = $pack['goods'][$i]['goodsId'];
            $adddata['skuId'] = $pack['goods'][$i]['skuId'];//后加skuId
            $adddata['skuSpecAttr'] = '';
            if ($pack['goods'][$i]['skuId'] > 0) {
                //sku属性值
                $systemSkuInfo = $systemSkuTab->where(['skuId' => $pack['goods'][$i]['skuId']])->find();
                $systemSkuInfo['selfSpec'] = M("sku_goods_self se")
                    ->join("left join wst_sku_spec sp on se.specId=sp.specId")
                    ->join("left join wst_sku_spec_attr sr on sr.attrId=se.attrId")
                    ->where(['se.skuId' => $systemSkuInfo['skuId']])
                    ->field('se.id,se.skuId,se.specId,se.attrId,sp.specName,sr.attrName,sp.sort as specSort,sr.sort as attrSort')
                    ->order('sp.sort asc')
                    ->select();
                if (!empty($systemSkuInfo['selfSpec'])) {
                    foreach ($systemSkuInfo['selfSpec'] as $sv) {
                        $systemSkuInfo['skuSpecStr'] .= $sv['attrName'] . "，";
                    }
                }
                $adddata['skuSpecAttr'] = trim($systemSkuInfo['skuSpecStr'], '，');;
            }
            $adddata['goodsName'] = $pack['goods'][$i]['goodsName'];
            $adddata['goodsSn'] = $pack['goods'][$i]['goodsSn'];
            $adddata['originalPrice'] = $pack['goods'][$i]['unitprice'];
            $adddata['favorablePrice'] = $pack['goods'][$i]['favorablePrice'];
            $adddata['presentPrice'] = $pack['goods'][$i]['shopPrice'];
            $adddata['number'] = $pack['goods'][$i]['number'];
            $adddata['subtotal'] = $pack['goods'][$i]['price'];
            $adddata['discount'] = $pack['goods'][$i]['discount'];
            $adddata['orderid'] = $order_info['id'];
            $adddata['weight'] = $pack['goods'][$i]['weight'];
            $adddata['state'] = 1;
            $adddata['isRefund'] = 0;

            $goods_info = $mod_goods->where(array('goodsId' => $pack['goods'][$i]['goodsId']))->find();
            //1:按商品积分抵扣比例计算 2：平摊分配
            if ($integral_flag == 1) {//按商品积分抵扣比例计算
                $discount = empty($pack['goods'][$i]['discount']) ? 1 : $pack['goods'][$i]['discount'] * 10 / 100;
                if ($pack['goods'][$i]['SuppPriceDiff'] < 0) {//标品
                    $money_t = $pack['goods'][$i]['shopPrice'] * $pack['goods'][$i]['number'] * $discount;
                } else if ($pack['goods'][$i]['SuppPriceDiff'] > 0) {//秤重商品
                    $money_t = $pack['goods'][$i]['shopPrice'] * $pack['goods'][$i]['weight'] * $discount;
                }
                $integral = moneyToIntegral($money_t * $goods_info['integralRate']);
            } else if ($integral_flag == 2) {//平摊分配
                $integral = integralAssignment($pack['setintegral'], $goods_info['integralRate'], $sum_integralRate);
            }
            $adddata['integral'] = $integral;


            $sdata[] = $adddata;

            $num = ($adddata['weight'] > 0) ? $adddata['weight'] : $adddata['number'];
            //改库存
            $mod_goods->where('goodsId = ' . $pack['goods'][$i]['goodsId'])->setDec('goodsStock', $num);
            if ($pack['goods'][$i]['skuId'] > 0) {
                //更改sku库存
                $systemSkuTab->where(['skuId' => $pack['goods'][$i]['skuId']])->setDec('skuGoodsStock', $num);
            }

        }

        if ($mod_pos_orders_goods->addAll($sdata)) {//全部添加
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = "提交成功";
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = [];
            return $apiRet;
//            $rs['status'] = 1;
//            $rs['msg'] = '提交成功';
//            $rs['data'] = null;
//
//            return $rs;
        } else {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = "提交失败";
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = [];
            return $apiRet;
//            $rs['status'] = -1;
//            $rs['msg'] = '提交失败';
//            $rs['data'] = null;
//
//            return $rs;
        }


    }


    /*
    *获取pos订单列表-分页  可携带参数搜索 搜索暂未完成------------------
    */
    public function orders($shopInfo, $orderNO, $count, $pageCount, $startTime, $endTime, $userId, $state)
    {
        // $mod_goods = M('goods');
        $mod_pos_orders = M('pos_orders');

        $mod_pos_orders_goods = M('pos_orders_goods');

        if (!empty($orderNO)) {
            $where['orderNO'] = $orderNO;
        }

        //如果有时间段限制
        if (!empty($startTime) and !empty($endTime)) {
            $where['addtime'] = array('BETWEEN', array($startTime, $endTime));
        }
        if (!empty($userId)) $where['userId'] = $userId;
        $where['shopId'] = $shopInfo['shopId'];
        $where['state'] = $state;

        $wcount = $mod_pos_orders->where($where)->count();// 查询满足要求的总记录数
        $list = $mod_pos_orders->where($where)->order('addtime desc')
            ->limit(($pageCount - 1) * $count, $count)
            ->select();

        // 获取订单下的商品 暂未使用联查
        for ($i = 0; $i < count($list); $i++) {
            unset($where);
            $where['orderid'] = $list[$i]['id'];
            $list[$i]['goods'] = $mod_pos_orders_goods->where($where)->select();
//        $isRefund = $mod_pos_orders->where(array('refundOrderId'=>$list[$i]['id']))->find();
//        $list[$i]['isRefund'] = empty($isRefund) ? 0 : 1;//是否已退货 0：否 1：是
        }

        $ret['list'] = $list;//数据列表
        $ret['pages'] = ceil($wcount / $count);//总页数
        $ret['pageCount'] = $pageCount;//当前页码
        $ret['count'] = $wcount;//总数量

        return $ret;
    }


    /*
    *通过会员动态码 获取用户信息
    */
    public function userInfo($userCode)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '登录失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $mod_users_dynamiccode = M('users_dynamiccode');
        $mod_users = M('users');
        //条件
        $where['state'] = 2;
        $where['code'] = $userCode;
        $data = $mod_users_dynamiccode->where($where)->find();
        if (!$data) {
            $apiRet['apiInfo'] = '会员码不存在或已使用';
            return $apiRet;
        }
        $outTime = strtotime($data['addtime']) + 60;
        if(time() > $outTime){
            $apiRet['apiInfo'] = '会员码已过期';
            return $apiRet;
        }
        $mod_users_dynamiccode->where(['id'=>$data['id']])->save(['state'=>1]);
        /*
            $field = array(
              'userId',
              'loginName',
              'userSex',
              'userName',
              'userPhone',
              'userEmail',
              'userScore',
              'userPhoto',
              'balance'
            );*/

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $mod_users->where('userId=' . $data['userId'])->find();
        return $apiRet;


    }


    /*
  *生成条码
  */
    public function getPosBarcode($parameter = array(), &$msg = '')
    {
        if (!$parameter['shopId'] || !$parameter['goodsId'] || !$parameter['weight']) {
            return false;
        }

        //由于是线下门店 不进行判断商品当前的任何状态 只要存在即可
        $where = array(
            'goodsId' => $parameter['goodsId'],
            'shopId' => $parameter['shopId'] //判断商品是否是本门店
        );


        $goodsInfo = M('goods')->where($where)->field('goodsId,goodsSn,goodsName,shopPrice,weightG')->find();
        if (!$goodsInfo) {
            return false;
        }

        #过称后的金额获取
        //$saveData['barcode'] = 'CZ-'.time();
        $saveData['barcode'] = '';
        $saveData['static'] = 1;
        $saveData['addtime'] = date('Y-m-d H:i:s');
        $saveData['goodsId'] = $goodsInfo['goodsId'];
        $saveData['goodsSn'] = $goodsInfo['goodsSn'];
        $saveData['goodsName'] = $goodsInfo['goodsName'];
        $saveData['shopId'] = $parameter['shopId'];
        $saveData['weight'] = $parameter['weight'];
        $saveData['price'] = $goodsInfo['shopPrice'];
        $saveData['weightprice'] = (float)$goodsInfo['shopPrice'] / (float)$goodsInfo['weightG'] * (float)$parameter['weight'];
        $res = M('pos_barcode')->add($saveData);
        if (!$res) {
            return false;
        }
        $code = joinString($res, 0, 18);
        $barcode = 'CZ-' . $code;
        $result = M('pos_barcode')->where(array('id' => $res))->save(array('barcode' => $barcode));
        if (!$result) return false;
        $data = array(
            'id' => $res,
            'barcode' => $barcode
        );
        return $data;


    }

    /**
     * 获取分类列表
     */
    public function queryByList($paramete)
    {
        $parentId = $paramete['id'] ? $paramete['id'] : 0;
        $sql = 'SELECT * FROM __PREFIX__shops_cats WHERE  shopId=' . $paramete['shopId'] . ' and catFlag=1 and parentId=' . $parentId . ' order by catSort asc';
        return $this->pageQuery($sql);
    }

    /**
     * 获得POS订单详情
     * @param $where
     */
    public function getPosOrderInfo($where)
    {
        return M('pos_orders')->where($where)->find();
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
     * 微信支付 - 动作
     * @param $userId
     * @param $auth_code
     * @param $money
     * @param $orderNo
     * @param $out_trade_no
     * @return mixed
     */
    public function doWxPay($userId, $auth_code, $money, $orderNo, $out_trade_no)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $order_info = $pom->where(array('orderNO' => $orderNo))->find();
        $order_goods = $pogm->where(array('orderid' => $order_info['id']))->select();

        Vendor('WxPay.lib.WxPayApi');
        Vendor('WxPay.MicroPay');
        Vendor('WxPay.log');
        if ((isset($auth_code) && !preg_match("/^[0-9]{6,64}$/i", $auth_code, $matches))) {
            header('HTTP/1.1 404 Not Found');
            exit();
        }
        //初始化日志
        $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler, 15);
        if (isset($auth_code) && $auth_code != '') {
            try {
                $wx_payments = M('payments')->where(array('payCode' => 'weixin', 'enabled' => 1))->find();
                if (empty($wx_payments['payConfig'])) {
                    $apiRet['apiCode'] = -1;
                    $apiRet['apiInfo'] = '参数不全';
                    $apiRet['apiState'] = 'error';
                    return $apiRet;
                }
                $wx_config = json_decode($wx_payments['payConfig'], true);
                $wx_config['appId'] = $GLOBALS['CONFIG']['xiaoAppid'];
//                    $auth_code = $_REQUEST["auth_code"];
                $input = new \WxPayMicroPay();
                $input->SetAuth_code($auth_code);
                $input->SetBody("POS-支付");
                $input->SetTotal_fee($money * 100);
                $input->SetOut_trade_no($out_trade_no);

                $microPay = new \MicroPay();
                $result = printf_info($microPay->pay($wx_config, $input));
//                    echo "<pre>";var_dump($result);exit();
                //支付成功后,执行下面的方法
                if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
                    Vendor('WxPay.notify');
                    //查询订单
                    \Log::DEBUG("begin notify");
                    $PayNotifyCallBack = new \PayNotifyCallBack();
                    $res = $PayNotifyCallBack->Queryorder($wx_config, $result['transaction_id']);
                    if ($res) {//支付成功
                        //回调方法
//                        $this->scanPayCallback($orderNo);
                        //订单校验
//                        $this->checkOrder($orderNo);

                        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 微信付款码： 收款成功 \r\n");
                        fclose($myfile);

                        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                        fclose($myfile);

                        $apiRet['apiCode'] = 0;
                        $apiRet['apiInfo'] = '收款成功';
                        $apiRet['apiState'] = 'success';
//                        $apiRet['apiData'] = $result;
                        return $apiRet;
                    } else {

                        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 微信付款码： 收款失败 \r\n");
                        fclose($myfile);

                        $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                        fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                        fclose($myfile);

                        $apiRet['apiInfo'] = '收款失败';
                        return $apiRet;
                    }
                } else {

                    $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                    fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 微信付款码： 收款失败 \r\n");
                    fclose($myfile);

                    $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                    fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                    fclose($myfile);

                    $apiRet['apiInfo'] = '收款失败';
                    return $apiRet;
                }
            } catch (Exception $e) {
                Log::ERROR(json_encode($e));
            }
        }
    }

    /**
     * 支付宝支付 - 动作
     */
    public function doAliPay($userId, $auth_code, $money, $orderNo, $out_trade_no)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = array();

        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $order_info = $pom->where(array('orderNO' => $orderNo))->find();
        $order_goods = $pogm->where(array('orderid' => $order_info['id']))->select();

        header("Content-type: text/html; charset=utf-8");
        Vendor('Alipay.dangmianfu.f2fpay.model.builder.AlipayTradePayContentBuilder');
        Vendor('Alipay.dangmianfu.f2fpay.service.AlipayTradeService');
//        $config = C('alipayConfig');

        $wx_payments = M('payments')->where(array('payCode' => 'alipay'))->find();
        if (empty($wx_payments['payConfig'])) {
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '参数不全';
            $apiRet['apiState'] = 'error';
            return $apiRet;
        }
        $config = json_decode($wx_payments['payConfig'], true);

        // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
        // 需保证商户系统端不能重复，建议通过数据库sequence生成，
        //$outTradeNo = "barpay" . date('Ymdhis') . mt_rand(100, 1000);
//            $outTradeNo = $_POST['out_trade_no'];
        $outTradeNo = $out_trade_no;

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
        $operatorId = $userId;

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
            $goods = new \GoodsDetail();
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
        $barPayRequestBuilder = new \AlipayTradePayContentBuilder();
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
        $barPay = new \AlipayTradeService($config);
        $barPayResult = $barPay->barPay($barPayRequestBuilder);

        $result = $barPayResult->getTradeStatus();
        if ($result == "SUCCESS") {//支付宝支付成功
//                print_r($barPayResult->getResponse());
//                $alipayResult = $barPayResult->getResponse();
//                echo "<pre>";var_dump($alipayResult);
//                exit();
            ////获取商户订单号
//                $out_trade_no = trim($_POST['out_trade_no']);
            $out_trade_no = trim($out_trade_no);

            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写

            //构造查询业务请求参数对象
            $queryContentBuilder = new \AlipayTradeQueryContentBuilder();
            $queryContentBuilder->setOutTradeNo($out_trade_no);

            $queryContentBuilder->setAppAuthToken($appAuthToken);


            //初始化类对象，调用queryTradeResult方法获取查询应答
            $queryResponse = new \AlipayTradeService($config);
            $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);

            //根据查询返回结果状态进行业务处理
            $resultState = $queryResult->getTradeStatus();
            if ($resultState == "SUCCESS") {//支付宝查询交易成功
                //处理业务逻辑
                //回调方法
//                $this->scanPayCallback($orderNo);
                //订单校验
//                $this->checkOrder($orderNo);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款成功 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '收款成功';
                $apiRet['apiState'] = 'success';
                return $apiRet;
            } else if ($resultState == "FAILED") {//支付宝查询交易失败或者交易已关闭
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 支付宝查询交易失败或者交易已关闭 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '支付宝查询交易失败或者交易已关闭';
                return $apiRet;
            } else if ($resultState == "UNKNOWN") {//系统异常，订单状态未知
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 系统异常，订单状态未知 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '系统异常，订单状态未知';
                return $apiRet;
            } else {//不支持的查询状态，交易返回异常
                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 不支持的查询状态，交易返回异常 \r\n");
                fclose($myfile);

                $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
                fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
                fclose($myfile);

                $apiRet['apiInfo'] = '不支持的查询状态，交易返回异常';
                return $apiRet;
            }

        } else if ($result == "FAILED") {//支付宝支付失败
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 支付宝支付失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '支付宝支付失败';
            return $apiRet;
        } else if ($result == "UNKNOWN") {//系统异常，订单状态未知
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 系统异常，订单状态未知 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '系统异常，订单状态未知';
            return $apiRet;
        } else {//不支持的交易状态，交易返回异常
            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款码： 收款失败 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")方式 - 支付宝付款失败原因： 不支持的交易状态，交易返回异常 \r\n");
            fclose($myfile);

            $myfile = fopen("scanPay.txt", "a+") or die("Unable to open file!");
            fwrite($myfile, "扫码收款(" . date('Y-m-d H:i:s') . ")： 结束 \r\n");
            fclose($myfile);

            $apiRet['apiInfo'] = '不支持的交易状态，交易返回异常';
            return $apiRet;
        }
    }

    /**
     * 根据订单编号获取订单详情和订单商品
     * @param $shopId
     * @param $orderNO
     */
    public function getOrderDetailAndOrderGoodsByOrderNo($shopId, $orderNO)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $pos_order_info = $this->getPosOrderInfo(array('orderNO' => $orderNO, 'shopId' => $shopId));
        if (empty($pos_order_info)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }

        $pos_order_goods_list = $this->getPosOrderGoodsList(array('orderid' => $pos_order_info['id']));
        if (empty($pos_order_goods_list)) {
            $apiRet['apiInfo'] = '该订单没有商品';
            return $apiRet;
        }

        $gm = M('goods');
        $goods_key_value_arr = $gm->getField('goodsId,SuppPriceDiff');
        foreach ($pos_order_goods_list as $k => $v) {
            $pos_order_goods_list[$k]['SuppPriceDiff'] = $goods_key_value_arr[$v['goodsId']];
        }

        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '操作成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = array(
            'pos_order_info' => $pos_order_info,
            'pos_order_goods_list' => $pos_order_goods_list
        );

        return $apiRet;
    }

    /**
     * 退货、退款
     */
    /*    public function returnGoods($shopId,$orderid,$orderMoney,$goods_info,$userId){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '操作失败';
            $apiRet['apiState'] = 'error';
            $apiRet['apiData'] = '';

            $pom = M('pos_orders');
            $pogm = M('pos_orders_goods');
            $prom = M('pos_return_orders');
            $progm = M('pos_return_orders_goods');
            $gm = M('goods');
            $um = M('users');

    //        $pos_order_info = $pom->where(array('id'=>$orderid,'shopId'=>$shopId))->find();
            $pos_order_info = $this->getPosOrderInfo(array('id'=>$orderid,'shopId'=>$shopId));
            if (empty($pos_order_info)) {
                $apiRet['apiInfo'] = '订单不存在';
                return $apiRet;
            }

             //判断商品是否 已退过 不得再次退款操作 --------------------------
            $total_money = 0;//退款总金额
            foreach ($goods_info as $v) {
                $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where(array('pog.goodsId'=>$v['goodsId'],'pog.orderid'=>$orderid))->find();
                if (empty($pos_order_goods_info)) {
                    $apiRet['apiInfo'] = $v['goodsName'].' 不存在';
                    return $apiRet;
                }
                //判断商品是否已退过
                if ($pos_order_goods_info['isRefund'] == 1) {
                    $apiRet['apiInfo'] = $v['goodsName'].' 已退过,不可再退';
                    return $apiRet;
                }
                $order_goods_discount = empty($pos_order_goods_info['discount']) ? 1 : $pos_order_goods_info['discount']*10/100;
                if ($pos_order_goods_info['SuppPriceDiff'] < 0) {//标品
                    if ($pos_order_goods_info['number'] < $v['number']) {
                        $apiRet['apiInfo'] = $v['goodsName'].' 的数量不能大于购买时的数量';
                        return $apiRet;
                    }
                    $total_money += $pos_order_goods_info['presentPrice']*$v['number']*$order_goods_discount;
                } else if($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                    if ($pos_order_goods_info['weight'] < $v['weight']) {
                        $apiRet['apiInfo'] = $v['goodsName'].' 的重量不能大于购买时的重量';
                        return $apiRet;
                    }
                    $total_money += $pos_order_goods_info['presentPrice']*$v['weight']*$order_goods_discount;
                }
            }
            $total_money = number_format($total_money,2);

            //比较 传过来的订单总金额 和 计算后的订单总金额 是否一致
            if ($total_money != $orderMoney) {
                $apiRet['apiInfo'] = '退款金额不正确';
                return $apiRet;
            }

            $cash = 0;//现金
            $balance = 0;//余额
            $unionpay = 0;//银联
            $wechat = 0;//微信
            $alipay = 0;//支付宝
            $setintegral = 0;//积分
            $pay = 0;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            //退现金
            if ($pos_order_info['cash'] > 0) {
    //            $cash = $total_money;
                $cash = $pos_order_info['cash'] - $pos_order_info['change'];
                $pay = 1;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            }

            //退余额
            if ($pos_order_info['balance'] > 0) {
    //            $balance = $total_money;
                $balance = $pos_order_info['balance'];
                $pay = 2;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付

                //退余额
                if ($userId > 0) {
                    $um->where(array('userId'=>$userId))->setInc('balance',$pos_order_info['balance']);
                }
            }

            //退银联
            if ($pos_order_info['unionpay'] > 0) {
    //            $unionpay = $total_money;
                $unionpay = $pos_order_info['unionpay'];
                $pay = 3;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付

                //业务逻辑 - 待添加

            }

            //退微信
            if ($pos_order_info['wechat'] > 0) {
    //            $wechat = $total_money;
                $wechat = $pos_order_info['wechat'];
                $result = wxRefundForDangMianFu($pos_order_info['id'],$wechat);
                if ($result['apiCode'] !== 0) {
                    return $result;
                }
                $pay = 4;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            }

            //退支付宝
            if ($pos_order_info['alipay'] > 0) {
    //            $alipay = $total_money;
                $alipay = $pos_order_info['alipay'];
                $result = alipayRefund($pos_order_info['id'],$alipay,'',1);
                if ($result['apiCode'] !== 0) {
                    return $result;
                }
                $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            }

            //退积分
            if ($pos_order_info['setintegral'] > 0) {
    //            $setintegral = $total_money;
                $setintegral = $pos_order_info['setintegral'];
                $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付

                //退积分
                if ($userId > 0) {
                    $um->where(array('userId'=>$userId))->setInc('userScore',$pos_order_info['setintegral']);
                }
            }

            $odata = array(
                'orderNO'=>'',
                'state' =>  4,//状态 1:待结算 2：已取消 3：已结算 4:退款
                'addtime'   =>  date('Y-m-d H:i:s'),
                'pay'       =>  $pay,//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
                'discount'  =>  $pos_order_info['discount'],//整单打折%
                'discountPrice' =>  $pos_order_info['discountPrice'],//整单折扣价（元）
                'shopId'    =>  $pos_order_info['shopId'],//门店id,
                'integral'  =>  0,//应得积分
                'cash'  =>  $cash,//现金（元）
                'balance'   =>  $balance,//余额（元）
                'unionpay'  =>  $unionpay,//银联（元）
                'wechat'    =>  $wechat,//微信（元）
                'alipay'    =>  $alipay,//支付宝（元）
                'change'    =>  0,//找零（元）
                'realpayment'   =>  '-'.$total_money,
                'userId'    =>  '',//收银员ID
                'setintegral'   =>  0,//使用积分
                'outTradeNo'    =>  $pos_order_info['outTradeNo'],//商户订单号
                'orderId' => $orderid,
                'memberId'  =>  $pos_order_info['memberId'],
                'isCombinePay'  =>  $pos_order_info['isCombinePay']
            );
            $insert_order_id = $prom->add($odata);
            if ($insert_order_id > 0) {
                $pogdata = array();
                foreach ($goods_info as $v) {
                    $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where(array('pog.goodsId'=>$v['goodsId'],'pog.orderid'=>$orderid))->find();
                    if (!empty($pos_order_goods_info)) {

                        //默认是标品
                        $subtotal = $pos_order_goods_info['presentPrice']*$v['number']*$pos_order_goods_info['discount']/100;
                        if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                            $subtotal = $pos_order_goods_info['presentPrice']*$v['weight']*$pos_order_goods_info['discount']/100;
                        }
                        $pogdata[] = array(
                            'goodsId'   =>  $v['goodsId'],
                            'goodsName' =>  $pos_order_goods_info['goodsName'],
                            'goodsSn'   =>  $pos_order_goods_info['goodsSn'],
                            'originalPrice' =>  $pos_order_goods_info['originalPrice'],
                            'favorablePrice'    =>  $pos_order_goods_info['favorablePrice'],
                            'presentPrice'  =>  $pos_order_goods_info['presentPrice'],
                            'number'    =>  ($pos_order_goods_info['SuppPriceDiff'] < 0)?$v['number']:1,
                            'subtotal'  =>  $subtotal,
                            'discount'  =>  $pos_order_goods_info['discount'],
                            'orderid'   =>  $insert_order_id,
                            'weight'    =>  ($pos_order_goods_info['SuppPriceDiff'] > 0)?$v['weight']:0,
                            'state' =>  1,
                            'isRefund'  =>  1,
                            'integral'  =>  $pos_order_goods_info['integral']
                        );

                        //将原订单商品标记为已退货
                        $pogm->where(array('goodsId'=>$v['goodsId'],'orderid'=>$orderid))->save(array('isRefund'=>1));

                        //改库存
                        if ($pos_order_goods_info['SuppPriceDiff'] < 0) {//标品
                            $gm->where('goodsId = ' . $v['goodsId'])->setInc('goodsStock', $v['number']);
                        } else if ($pos_order_goods_info['SuppPriceDiff'] > 0){//秤重商品
                            $goods_weight = gChangeKg($v["goodsId"],$v['weight'],0);
                            $gm->where('goodsId = ' . $v['goodsId'])->setInc('goodsStock', $goods_weight);
                        }
                    }
                }
                if (!empty($pogdata)) $progm->addAll($pogdata);
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '退款成功';
                $apiRet['apiState'] = 'success';
            }
            return $apiRet;
        }*/

    /**
     * 退货、退款
     */
    /*public function returnGoods($shopInfo,$pack){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $prom = M('pos_return_orders');
        $progm = M('pos_return_orders_goods');
        $gm = M('goods');
        $um = M('users');

//        $pos_order_info = $pom->where(array('id'=>$orderid,'shopId'=>$shopId))->find();
        $pos_order_info = $this->getPosOrderInfo(array('id'=>$pack['orderId'],'shopId'=>$shopInfo['shopId']));
        if (empty($pos_order_info)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }

        //判断商品是否 已退过 不得再次退款操作 --------------------------
        $total_money = 0;//退款总金额
        $total_score = 0;//退款总积分
        foreach ($pack['goods'] as $v) {
            $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where(array('pog.goodsId'=>$v['goodsId'],'pog.orderid'=>$pack['orderId']))->find();
            if (empty($pos_order_goods_info)) {
                $apiRet['apiInfo'] = $v['goodsName'].' 不存在';
                return $apiRet;
            }
            //判断商品是否已退过
            if ($pos_order_goods_info['isRefund'] == 1) {
                $apiRet['apiInfo'] = $v['goodsName'].' 已退过,不可再退';
                return $apiRet;
            }
            $order_goods_discount = empty($pos_order_goods_info['discount']) ? 1 : $pos_order_goods_info['discount']*10/100;
            if ($pos_order_goods_info['SuppPriceDiff'] < 0) {//标品
                if ($pos_order_goods_info['number'] < $v['number']) {
                    $apiRet['apiInfo'] = $v['goodsName'].' 的数量不能大于购买时的数量';
                    return $apiRet;
                }
                $money_t = $pos_order_goods_info['presentPrice']*$v['number']*$order_goods_discount;
            } else if($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                if ($pos_order_goods_info['weight'] < $v['weight']) {
                    $apiRet['apiInfo'] = $v['goodsName'].' 的重量不能大于购买时的重量';
                    return $apiRet;
                }
                $money_t = $pos_order_goods_info['presentPrice']*$v['weight']*$order_goods_discount;
            }
            $total_money += $money_t;
            $integral_t = moneyToIntegral($money_t);
            $goods_integral = ($integral_t > $pos_order_goods_info['integral']) ? $pos_order_goods_info['integral'] : $integral_t;
            $total_score += $goods_integral;
        }
        $total_money = number_format($total_money,2);

        //比较 传过来的订单总金额 和 计算后的订单总金额 是否一致
        if ($total_money != $pack['realpayment']) {
            $apiRet['apiInfo'] = '退款金额不正确';
            return $apiRet;
        }

        $cash = 0;//现金
        $balance = 0;//余额
        $unionpay = 0;//银联
        $wechat = 0;//微信
        $alipay = 0;//支付宝
        $setintegral = 0;//积分
        $pay = 0;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
        //退现金
        if ($pack['cash'] > 0) {
            $cash = $pack['cash'] - $pack['change'];
            $pay = 1;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
        }

        //退余额
        if ($pack['balance'] > 0) {
            $balance = $pack['balance'];
            $pay = 2;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付

            //退余额
            if ($pos_order_info['memberId'] > 0) {
                $um->where(array('userId'=>$pos_order_info['memberId']))->setInc('balance',$pack['balance']);
            }
        }

        //退银联
        if ($pack['unionpay'] > 0) {
            $unionpay = $pack['unionpay'];
            $pay = 3;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付

            //业务逻辑 - 待添加

        }

        //退微信
        if ($pack['wechat'] > 0) {
            $wechat = $pack['wechat'];
            $result = wxRefundForDangMianFu($pos_order_info['id'],$wechat);
            if ($result['apiCode'] !== 0) {
                return $result;
            }
            $pay = 4;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
        }

        //退支付宝
        if ($pack['alipay'] > 0) {
            $alipay = $pack['alipay'];
            $result = alipayRefund($pos_order_info['id'],$alipay,'',1);
            if ($result['apiCode'] !== 0) {
                return $result;
            }
            $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
        }

        //退积分
        if ($total_score > 0) {
            $setintegral = $total_score;
//            $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付

            //退积分
            if ($pos_order_info['memberId'] > 0) {
                $um->where(array('userId'=>$pos_order_info['memberId']))->setInc('userScore',$setintegral);
            }
        }

        $mod_pos_order_id = M('pos_order_id')->add(array('id'=>''));//获取增量id
        $time = date('YmdHis');
        $shopid = $shopInfo['shopId'];

        // 拼接订单号
        $orderNO = $time.$shopid.$mod_pos_order_id;

        $odata = array(
            'orderNO'=>$orderNO,
            'state' =>  4,//状态 1:待结算 2：已取消 3：已结算 4:退款
            'addtime'   =>  date('Y-m-d H:i:s'),
            'pay'       =>  $pay,//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            'discount'  =>  $pack['discount'],//整单打折%
            'discountPrice' =>  $pack['discountPrice'],//整单折扣价（元）
            'shopId'    =>  $pos_order_info['shopId'],//门店id,
            'integral'  =>  0,//应得积分
            'cash'  =>  $cash,//现金（元）
            'balance'   =>  $balance,//余额（元）
            'unionpay'  =>  $unionpay,//银联（元）
            'wechat'    =>  $wechat,//微信（元）
            'alipay'    =>  $alipay,//支付宝（元）
            'change'    =>  $pack['change'],//找零（元）
            'realpayment'   =>  '-'.$total_money,
            'userId'    =>  ($shopInfo['id'] > 0) ? $shopInfo['id'] : $shopInfo['shopId'],//收银员ID
            'setintegral'   =>  $setintegral,//使用积分
            'outTradeNo'    =>  $pos_order_info['outTradeNo'],//商户订单号
            'orderId' => $pack['orderId'],
            'settlementId'=>0,
            'memberId'  =>  $pos_order_info['memberId'],
            'isCombinePay'  =>  empty($pos_order_info['isCombinePay'])?0:1
        );
        $insert_order_id = $prom->add($odata);
        if ($insert_order_id > 0) {
            $pogdata = array();
            foreach ($pack['goods'] as $v) {
                $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where(array('pog.goodsId'=>$v['goodsId'],'pog.orderid'=>$pack['orderId']))->find();
                if (!empty($pos_order_goods_info)) {

                    //默认是标品
                    $subtotal = $pos_order_goods_info['presentPrice']*$v['number']*$pos_order_goods_info['discount']/100;
                    if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                        $subtotal = $pos_order_goods_info['presentPrice']*$v['weight']*$pos_order_goods_info['discount']/100;
                    }
                    $integral_t = moneyToIntegral($subtotal);
                    $goods_integral = ($integral_t > $pos_order_goods_info['integral']) ? $pos_order_goods_info['integral'] : $integral_t;
                    $subtotal = number_format($subtotal,2);
                    $pogdata[] = array(
                        'goodsId'   =>  $v['goodsId'],
                        'goodsName' =>  $pos_order_goods_info['goodsName'],
                        'goodsSn'   =>  $pos_order_goods_info['goodsSn'],
                        'originalPrice' =>  $pos_order_goods_info['originalPrice'],
                        'favorablePrice'    =>  $pos_order_goods_info['favorablePrice'],
                        'presentPrice'  =>  $pos_order_goods_info['presentPrice'],
                        'number'    =>  ($pos_order_goods_info['SuppPriceDiff'] < 0)?$v['number']:1,
                        'subtotal'  =>  $subtotal,
                        'discount'  =>  $pos_order_goods_info['discount'],
                        'orderid'   =>  $insert_order_id,
                        'weight'    =>  ($pos_order_goods_info['SuppPriceDiff'] > 0)?$v['weight']:0,
                        'state' =>  1,
                        'isRefund'  =>  1,
                        'integral'  =>  $goods_integral
                    );

                    //将原订单商品标记为已退货
                    $pogm->where(array('goodsId'=>$v['goodsId'],'orderid'=>$pack['orderId']))->save(array('isRefund'=>1));

                    //改库存
                    $num = ($pos_order_goods_info['SuppPriceDiff'] < 0) ? $v['number'] : $v['weight'];
					$goods_num = gChangeKg($v["goodsId"],$num,0);
                    $gm->where('goodsId = ' . $v['goodsId'])->setInc('goodsStock', $goods_num);
                }
            }
            if (!empty($pogdata)) $progm->addAll($pogdata);
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '退款成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }*/

    /**
     * 退货、退款
     */
    public function returnGoods($shopInfo, $pack)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $apiRet['apiData'] = '';

        $pom = M('pos_orders');
        $pogm = M('pos_orders_goods');
        $prom = M('pos_return_orders');
        $progm = M('pos_return_orders_goods');
        $gm = M('goods');
        $um = M('users');

//        $pos_order_info = $pom->where(array('id'=>$orderid,'shopId'=>$shopId))->find();
        $pos_order_info = $this->getPosOrderInfo(array('id' => $pack['orderId'], 'shopId' => $shopInfo['shopId']));
        if (empty($pos_order_info)) {
            $apiRet['apiInfo'] = '订单不存在';
            return $apiRet;
        }

        //判断商品是否 已退过 不得再次退款操作 --------------------------
        $total_money = 0;//退款总金额
        $total_score = 0;//退款总积分
        foreach ($pack['goods'] as $v) {
            $where = [];
            $where['pog.goodsId'] = $v['goodsId'];
            $where['pog.orderid'] = $pack['orderId'];
            $where['pog.skuId'] = $v['skuId'];
            $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where($where)->find();
            if (empty($pos_order_goods_info)) {
                $apiRet['apiInfo'] = $v['goodsName'] . ' 不存在';
                return $apiRet;
            }
            //判断商品是否已退过
            if ($pos_order_goods_info['isRefund'] == 1) {
                $apiRet['apiInfo'] = $v['goodsName'] . ' 已退过,不可再退';
                return $apiRet;
            }
            $order_goods_discount = empty($pos_order_goods_info['discount']) ? 1 : $pos_order_goods_info['discount'] * 10 / 100;
            if ($pos_order_goods_info['SuppPriceDiff'] < 0) {//标品
                if ($pos_order_goods_info['number'] < $v['number']) {
                    $apiRet['apiInfo'] = $v['goodsName'] . ' 的数量不能大于购买时的数量';
                    return $apiRet;
                }
                $money_t = $pos_order_goods_info['presentPrice'] * $v['number'] * $order_goods_discount;
            } else if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                if ($pos_order_goods_info['weight'] < $v['weight']) {
                    $apiRet['apiInfo'] = $v['goodsName'] . ' 的重量不能大于购买时的重量';
                    return $apiRet;
                }
                $money_t = $pos_order_goods_info['presentPrice'] * $v['weight'] * $order_goods_discount;
            }
            $total_money += $money_t;
//            $integral_t = moneyToIntegral($money_t);
//            $goods_integral = ($integral_t > $pos_order_goods_info['integral']) ? $pos_order_goods_info['integral'] : $integral_t;
//            $total_score += $goods_integral;
        }
        $total_money = number_format($total_money, 2);

        //比较 传过来的订单总金额 和 计算后的订单总金额 是否一致
        if ($total_money != $pack['realpayment']) {
            $apiRet['apiInfo'] = '退款金额不正确';
            return $apiRet;
        }

        $cash = $total_money;//现金
        $balance = 0;//余额
        $unionpay = 0;//银联
        $wechat = 0;//微信
        $alipay = 0;//支付宝
        $setintegral = 0;//积分
        $pay = 1;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
        //退现金
//        if ($pack['cash'] > 0) {
//            $cash = $pack['cash'] - $pack['change'];
//            $pay = 1;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
//        }

        //退余额
//        if ($pack['balance'] > 0) {
//            $balance = $pack['balance'];
//            $pay = 2;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
//
//            退余额
//            if ($pos_order_info['memberId'] > 0) {
//                $um->where(array('userId'=>$pos_order_info['memberId']))->setInc('balance',$pack['balance']);
//            }
//        }

        //退银联
//        if ($pack['unionpay'] > 0) {
//            $unionpay = $pack['unionpay'];
//            $pay = 3;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付

        //业务逻辑 - 待添加

//        }

        //退微信
//        if ($pack['wechat'] > 0) {
//            $wechat = $pack['wechat'];
//            $result = wxRefundForDangMianFu($pos_order_info['id'],$wechat);
//            if ($result['apiCode'] !== 0) {
//                return $result;
//            }
//            $pay = 4;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
//        }

        //退支付宝
//        if ($pack['alipay'] > 0) {
//            $alipay = $pack['alipay'];
//            $result = alipayRefund($pos_order_info['id'],$alipay,'',1);
//            if ($result['apiCode'] !== 0) {
//                return $result;
//            }
//            $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
//        }

        //退积分
        if ($pack['setintegral'] > 0) {
            $total_score = moneyToIntegral($total_money);
            $setintegral = $pack['setintegral'] - $total_score;
//            $pay = 5;//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付

            //退积分
            if ($pos_order_info['memberId'] > 0) {
                $um->where(array('userId' => $pos_order_info['memberId']))->setInc('userScore', $setintegral);
            }
        }

        $mod_pos_order_id = M('pos_order_id')->add(array('id' => ''));//获取增量id
        $time = date('YmdHis');
        $shopid = $shopInfo['shopId'];

        // 拼接订单号
        $orderNO = $time . $shopid . $mod_pos_order_id;

        $odata = array(
            'orderNO' => $orderNO,
            'state' => 4,//状态 1:待结算 2：已取消 3：已结算 4:退款
            'addtime' => date('Y-m-d H:i:s'),
            'pay' => $pay,//支付方式 1:现金支付 2：余额支付 3：银联支付 4：微信支付 5：支付宝支付 6：组合支付
            'discount' => $pack['discount'],//整单打折%
            'discountPrice' => $pack['discountPrice'],//整单折扣价（元）
            'shopId' => $pos_order_info['shopId'],//门店id,
            'integral' => 0,//应得积分
            'cash' => $cash,//现金（元）
            'balance' => $balance,//余额（元）
            'unionpay' => $unionpay,//银联（元）
            'wechat' => $wechat,//微信（元）
            'alipay' => $alipay,//支付宝（元）
            'change' => $pack['change'],//找零（元）
            'realpayment' => '-' . $total_money,
            'userId' => ($shopInfo['id'] > 0) ? $shopInfo['id'] : $shopInfo['shopId'],//收银员ID
            'setintegral' => $pack['setintegral'],//使用积分
            'outTradeNo' => $pos_order_info['outTradeNo'],//商户订单号
            'orderId' => $pack['orderId'],
            'settlementId' => 0,
            'memberId' => $pos_order_info['memberId'],
            'isCombinePay' => empty($pos_order_info['isCombinePay']) ? 0 : 1
        );
        $insert_order_id = $prom->add($odata);
        if ($insert_order_id > 0) {
            $pogdata = array();
            foreach ($pack['goods'] as $v) {
                $pos_order_goods_info = M('pos_orders_goods as pog')->field('pog.*,g.SuppPriceDiff')->join('left join wst_goods as g on g.goodsId = pog.goodsId')->where(array('pog.goodsId' => $v['goodsId'], 'pog.orderid' => $pack['orderId']))->find();
                if (!empty($pos_order_goods_info)) {

                    //默认是标品
                    $subtotal = $pos_order_goods_info['presentPrice'] * $v['number'] * $pos_order_goods_info['discount'] / 100;
                    if ($pos_order_goods_info['SuppPriceDiff'] > 0) {//秤重商品
                        $subtotal = $pos_order_goods_info['presentPrice'] * $v['weight'] * $pos_order_goods_info['discount'] / 100;
                    }
                    $integral_t = moneyToIntegral($subtotal);
                    $goods_integral = ($integral_t > $pos_order_goods_info['integral']) ? $pos_order_goods_info['integral'] : $integral_t;
                    $subtotal = number_format($subtotal, 2);
                    $pogdata[] = array(
                        'goodsId' => $v['goodsId'],
                        'skuId' => $pos_order_goods_info['skuId'],
                        'skuSpecAttr' => $pos_order_goods_info['skuSpecAttr'],
                        'goodsName' => $pos_order_goods_info['goodsName'],
                        'goodsSn' => $pos_order_goods_info['goodsSn'],
                        'originalPrice' => $pos_order_goods_info['originalPrice'],
                        'favorablePrice' => $pos_order_goods_info['favorablePrice'],
                        'presentPrice' => $pos_order_goods_info['presentPrice'],
                        'number' => ($pos_order_goods_info['SuppPriceDiff'] < 0) ? $v['number'] : 1,
                        'subtotal' => $subtotal,
                        'discount' => $pos_order_goods_info['discount'],
                        'orderid' => $insert_order_id,
                        'weight' => ($pos_order_goods_info['SuppPriceDiff'] > 0) ? $v['weight'] : 0,
                        'state' => 1,
                        'isRefund' => 1,
                        'integral' => $goods_integral
                    );

                    //将原订单商品标记为已退货
                    $pogm->where(array('goodsId' => $v['goodsId'], 'orderid' => $pack['orderId']))->save(array('isRefund' => 1));

                    //改库存
                    $num = ($pos_order_goods_info['SuppPriceDiff'] < 0) ? $v['number'] : $v['weight'];
                    $gm->where('goodsId = ' . $v['goodsId'])->setInc('goodsStock', $num);
                    if ($v['skuId'] > 0) {
                        //返还sku属性库存
                        M('sku_goods_system')->where(['skuId' => $v['skuId']])->setInc('skuGoodsStock', $num);
                    }
                }
            }
            if (!empty($pogdata)) $progm->addAll($pogdata);
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '退款成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * 获取Pos订单列表
     * where
     * @param string orderNo PS:订单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大金额(金额区间)
     * @param string minMoney PS:最小金额(金额区间)
     * @param int state PS:状态 (1:待结算 | 2：已取消 | 3：已结算 | 20:全部)
     * @param int pay PS:支付方式 (1:现金支付 | 2：余额支付 | 3：银联支付 | 4：微信支付 | 5：支付宝支付 | 6：组合支付 | 20:全部)
     * @param string name PS:收银员账号
     * @param string username PS:收银员姓名
     * @param string phone PS:收银员手机号
     * @param string identity PS:身份 1:会员 2：游客
     * @param string membername PS:会员名
     * @param string p PS:页码
     */
    public function getPosOrderList($shopId, $param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $field = "po.*,u.name as user_name,u.username as user_useranme,u.phone as user_phone,us.userName as membername ";
        $field1 = " sum(po.realpayment) as total_order_money ";//主要用来统计相关条件下的总订单金额
        $where = " where po.shopId='" . $shopId . "' and u.status != -1 and u.shopId='" . $shopId . "' ";
        if (!empty($param['orderNo'])) {
            $where .= " and po.orderNO='" . $param['orderNo'] . "' ";
        }
        if (!empty($param['startDate']) && !empty($param['endDate'])) {
            $where .= " and po.addtime between '" . $param['startDate'] . "' and '" . $param['endDate'] . "' ";
        }
        if (!empty($param['maxMoney']) && !empty($param['minMoney'])) {
            $where .= " and po.realpayment between '" . $param['minMoney'] . "' and '" . $param['maxMoney'] . "' ";
        }
        if ($param['state'] != 20) {
            $where .= " and po.state='" . $param['state'] . "' ";
        }
        if ($param['pay'] != 20) {
            $where .= " and po.pay='" . $param['pay'] . "' ";
        }
        if (!empty($param['name'])) {
            $where .= " and u.name like '%" . $param['name'] . "%' ";
        }
        if (!empty($param['username'])) {
            $where .= " and u.username like '%" . $param['username'] . "%' ";
        }
        if (!empty($param['phone'])) {
            $where .= " and u.phone like '%" . $param['phone'] . "%' ";
        }
        if ($param['identity'] == 1) {//会员
            $where .= " and po.memberId > 0 ";
        } else if ($param['identity'] == 2) {//游客
            $where .= " and po.memberId = 0 ";
        }
        if (!empty($param['membername'])) {//会员名
            $where .= " and us.userName like '%" . $param['membername'] . "%' ";
        }

        $sql = "select $field from __PREFIX__pos_orders po 
        left join __PREFIX__user u on po.userId=u.id
         left join __PREFIX__users us on po.memberId = us.userId ";
        $sql .= $where;
        $sql .= " order by po.id desc ";
        $res = $this->pageQuery($sql);

        //主要用来统计相关条件下的总订单金额
        $sql1 = "select $field1 from __PREFIX__pos_orders po
        left join __PREFIX__user u on po.userId=u.id
         left join __PREFIX__users us on po.memberId = us.userId ";
        $sql1 .= $where;
        $total_order_money = $this->query($sql1);
        if (is_null($total_order_money[0]['total_order_money'])) $total_order_money[0]['total_order_money'] = 0;//订单总金额
        $res['total_order_money'] = $total_order_money[0]['total_order_money'];

        $openPresaleCash = M("sys_configs")->where("fieldCode='openPresaleCash'")->getField('fieldValue'); //是否开启预存款
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                if (is_null($val['pay'])) {
                    $val['pay'] = '';
                }
                if (is_null($val['integral'])) {
                    $val['integral'] = '';
                }
                if (is_null($val['outTradeNo'])) {
                    $val['outTradeNo'] = '';
                }
                if (is_null($val['discount'])) {
                    $val['discount'] = '';
                }
                if ($openPresaleCash == 1) {
                    $val['trueRealpayment'] = $val['realpayment'];
                }
            }
            unset($val);
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $res;
        return $apiRet;
    }

    /**
     * 获取Pos订单详情
     */
    public function getPosOrderDetail($shopId, $param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $field = "po.*,u.name as user_name,u.username as user_useranme,u.phone as user_phone ";
        $where = " where po.id='" . $param['posId'] . "' and po.shopId='" . $shopId . "'";
        $sql = "select $field from __PREFIX__pos_orders po 
        left join __PREFIX__user u on po.userId=u.id ";
        $sql .= $where;
        $res = $this->queryRow($sql);
        if ($res) {
            //获取涉及的订单及商品
            $orderGoodsTab = M('pos_orders_goods pg');
            $res['goodslist'] = $orderGoodsTab
                ->join("left join wst_goods g on g.goodsId=pg.goodsId")
                ->where(['orderid' => $res['id']])
                ->field("pg.*,g.goodsThums")
                ->select();
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res;
        }
        return $apiRet;
    }

    /**
     * 获取Pos订单列表
     * where
     * @param string settlementNo PS:结算单号
     * @param string startDate PS:开始时间
     * @param string endDate PS:结束时间
     * @param string maxMoney PS:最大金额(金额区间)
     * @param string minMoney PS:最小金额(金额区间)
     * @param int state PS:状态 (1:未结算 | 2：已结算 | 20:全部)
     * @param int p PS:页码
     */
    public function getPosOrderSettlementList($shopId, $param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        if (empty($param['maxMoney'])) {
            $param['maxMoney'] = 0;
        }
        if (empty($param['minMoney'])) {
            $param['minMoney'] = 0;
        }

        $where = " where os.shopId='" . $shopId . "' ";
        if (!empty($param['settlementNo'])) {
            $where .= " and os.settlementNo='" . $param['settlementNo'] . "' ";
        }
        if (!empty($param['startDate']) && !empty($param['endDate'])) {
            $where .= " and os.createTime between '" . $param['startDate'] . "' and '" . $param['endDate'] . "' ";
        }
        if (!empty($param['maxMoney']) && !empty($param['minMoney'])) {
            $where .= " and os.settlementMoney between '" . $param['minMoney'] . "' and '" . $param['maxMoney'] . "' ";
        }
        if ($param['isFinish'] != 20) {
            $where .= " and os.isFinish='" . $param['isFinish'] . "' ";
        }
        $sql = "select os.* from __PREFIX__pos_order_settlements os ";
        $sql .= $where;
        $sql .= " order by os.settlementId desc ";
        $res = $this->pageQuery($sql);
        if ($res['root']) {
            foreach ($res['root'] as $key => &$val) {
                if (is_null($val['finishTime'])) {
                    $val['finishTime'] = '';
                }
                if (is_null($val['remarks'])) {
                    $val['remarks'] = '';
                }
            }
            unset($val);
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res;
        }
        return $apiRet;
    }

    /**
     * Pos订单结算申请
     */
    public function posOrderSettlement($param)
    {
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $ids = $param['ids'];
        $tab = M('pos_orders');
        $where = [];
        $where['id'] = ["IN", $ids];
        $where['state'] = 1;

        $list = $tab->where($where)->select();
        if ($list) {
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
                $apiRet['apiInfo'] = '操作失败,结算金额必须大于' . $settlementStartMoney;
                return $apiRet;
            }
            $bankInfo = M('shops s')
                ->join("left join wst_banks b on b.bankId=s.bankId")
                ->where("s.shopId='" . $val['shopId'] . "' and s.bankId=b.bankId")
                ->field("b.bankName,s.bankUserName,s.bankNo")
                ->find();
            //生成结算单
            $data = array();
            $data['settlementType'] = 1;
            $data['shopId'] = $param['shopId'];
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
                $apiRet['apiCode'] = 0;
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }
        }
        return $apiRet;
    }

    /**
     * 记录充值 记录
     */
    public function addRechargeLog($data)
    {
        return M('log_sys_moneys')->add($data);
    }

    /**
     * 获取收银员列表
     * @param $shopId
     * @return mixed
     */
    public function getCashierList($shopId)
    {
        return M('pos_orders as po')->distinct(true)->join('left join wst_user as u on po.userId = u.id')->where(array('po.shopId' => $shopId, 'u.status' => 0))->field('u.id,u.username')->select();
    }

    /**
     * 商户预存款流水
     * @param $shopId
     * @param $page
     * @param $pageSize
     */
    public function getRechargeRecord($shopId, $page, $pageSize)
    {
        $sql = "select lsm.*,s.shopSn,s.shopName,s.shopCompany from __PREFIX__log_sys_moneys as lsm left join __PREFIX__shops as s on lsm.targetId = s.shopId where lsm.dataSrc = 3 and lsm.dataFlag = 1 and lsm.targetType = 1 and lsm.targetId = " . $shopId;
        $rs = $this->pageQuery($sql, $page, $pageSize);
        return $rs;
    }

    /**
     * 申请提现
     * @param $data
     * @return mixed
     */
    public function addWithdraw($data)
    {
        return M('withdraw')->add($data);
    }
}

?>

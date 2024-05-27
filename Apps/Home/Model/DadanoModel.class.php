<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 达达回调服务类
 */
use Think\Model;
class DadanoModel extends BaseModel {

	//达达订单回调地址
	public function dadaOrderCall($data){
		$data = json_decode($data,true);
		
		$dataSign['order_id'] = $data['order_id'];
		$dataSign['update_time'] = $data['update_time'];
		$dataSign['client_id'] = $data['client_id'];
		
        asort($dataSign);
        $args = "";
        foreach ($dataSign as $key => $value) {
            $args.=$value;
        }
        $args = $args;
        $sign = md5($args);
	  
		//TODO:签名算法有变 不能正常使用 先不校验 知道算法后再处理
		// if($sign !== $data['signature']){
			
		// 	$apiRet['apiCode']=-1;
		// 	$apiRet['apiInfo']='有毒...';
		// 	$apiRet['apiState']='error';
		// 	return $apiRet;
		// }
		
		//-------------订单处理流程开始------------------
		
		$mod_orders = M('orders');
		$mod_log_orders = M('log_orders');
		$mod_users = M('users');
		$mod_user_score = M('user_score');
		/*********

		{
"cancel_from": 0,
"cancel_reason": "",
"signature": "272a6bd36eb5822b6a0d7df21f86e1e3",
"dm_name": "达达骑手",
"order_status": 2,
"dm_id": 666,
"order_id": "1000009511",
"update_time": 1531206763,
"dm_mobile": "13546670420",
"client_id": "277060575687522"
}

		********/
	
		switch ($data['order_status'])
			{
			case 1://待接单
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = 7;
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
//					$add_data['orderId'] = $resData['orderId'];
//					$add_data['logContent'] = '达达-等待骑手接单';
//					$add_data['logUserId'] = 0;
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = '达达-等待骑手接单';
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 7,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
				}
			  break;
			case 2://待取货
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = 8;
					$save_data['dmName'] = $data['dm_name'];
					$save_data['dmMobile'] = $data['dm_mobile'];
					$save_data['dmId'] =$data['dm_id'];
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
//					$add_data['orderId'] = $resData['orderId'];
//					$add_data['logContent'] = '达达-等待骑手取货';
//					$add_data['logUserId'] = 0;
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = '达达-等待骑手取货';
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 8,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
				}
			  break;
			case 3://配送中
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = 3;
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);

                    //去除筐位，防止占位
                    removeBasket($data['order_id']);

					//写入订单日志
					
//					$add_data['orderId'] = $resData['orderId'];
//					$add_data['logContent'] = '达达-配送中';
//					$add_data['logUserId'] = 0;
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = '达达-配送中';
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 3,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
				}
			  break;
			case 4://已完成
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['receiveTime'] = date('Y-m-d H:i:s');
					$save_data['orderStatus'] = 4;
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);

                    //去除筐位，防止占位
                    removeBasket($data['order_id']);

					//写入订单日志
					
//					$add_data['orderId'] = $resData['orderId'];
//					$add_data['logContent'] = '达达-订单已送达';
//					$add_data['logUserId'] = 0;
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);

                    $content = '达达-订单已送达';
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 4,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);

                    $param = [];
                    $param['orderId'] = $resData['orderId'];
                    $param['userId'] = $resData['userId'];
                    $param['rsv'] = $resData;
                    //积分相关操作|商品销量
                    editOrderInfo($param);

                    //判断商品是否属于分销商品
                    checkGoodsDistribution($resData['orderId']);

                    //发放地推邀请奖励
                    grantPullNewAmount($resData['orderId']);

                    //判断当前订单是否有差价(列表)需要退
                    editPriceDiffer($param);

                    //邀请有礼
                    editUserInfo($param);

                    $content = "用户已收货";
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => $resData['userId'],
                        'logUserName' => '系统',
                        'orderStatus' => 4,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);

                    //订单送达时通知
                    $push = D('Adminapi/Push');
                    $push->postMessage(10, $resData['userId'], $resData['orderNo'], $resData['shopId']);
//					if($GLOBALS['CONFIG']['isOrderScore']==1 && $resData["orderScore"]>0){
//						//增加用户积分
//						$mod_users->where("userId='{$resData['userId']}'")->setInc('userScore',$resData['orderScore']);
//
//						//写入积分日志
//						$add_user_score_data['userId'] = $resData['userId'];
//						$add_user_score_data['score'] = $resData['orderScore'];
//						$add_user_score_data['dataSrc'] = 1;
//						$add_user_score_data['dataId'] = $data['order_id'];
//						$add_user_score_data['dataRemarks'] = '订单完成';
//						$add_user_score_data['scoreType'] = 1;
//						$add_user_score_data['createTime'] = date("Y-m-d H:i:s");
//						$mod_user_score->add($add_user_score_data);
//					}
				}
			  break;
			case 5://已取消
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = 9;
					
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
					$add_data['orderId'] = $resData['orderId'];

					$logType = 2;
					if($data['cancel_from'] == 1){
						$add_data['logContent'] = '达达配送员取消';
					}
					if($data['cancel_from'] == 2){
                        $logType = 1;
						$add_data['logContent'] = '商家主动取消';
					}
					if($data['cancel_from'] == 3){
						$add_data['logContent'] = '系统或客服取消';
					}
					
//					$add_data['logUserId'] = $resData['userId'];
//					$add_data['logType'] = $logType;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = $add_data['logContent'];
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 9,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
					
				
				}
			  break;
			case 7://已过期
				//状态改为10 已过期 要在物流列表显示 并作为异常订单
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = 10;
					
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
					$add_data['orderId'] = $resData['orderId'];
					
				
					$add_data['logContent'] = '达达-订单已过期';
					
					
//					$add_data['logUserId'] = $resData['userId'];
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = $add_data['logContent'];
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => 10,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
					
				
				}
			  break;
			case 8://指派单
			 
			  break;
			case 9://妥投异常之物品返回中
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$save_data['orderStatus'] = -3;
					
					
					
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
					$add_data['orderId'] = $resData['orderId'];

					$add_data['logContent'] = '达达-妥投异常之物品返回中(配送员在收货地，无法正常送到用户手中（包括用户电话打不通、客户暂时不方便收件、客户拒收、货物有问题等等)';

//					$add_data['logUserId'] = $resData['userId'];
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = $add_data['logContent'];
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => -3,
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
					
				
				}
			  break;
			case 10://妥投异常之物品返回完成
				$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
					$add_data['orderId'] = $resData['orderId'];

					$add_data['logContent'] = '达达-妥投异常之物品返回完成';

//					$add_data['logUserId'] = $resData['userId'];
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = $add_data['logContent'];
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => $resData['orderStatus'],
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
					
				
				}
			  break;
			case 1000://创建达达运单失败
			  	$resData = $mod_orders->where("orderNo = '{$data['order_id']}'")->find();
				$updateTime=$resData['updateTime'];
				if(empty($updateTime) or $data['update_time']>$updateTime){
					//更新订单状态
					$save_data['updateTime'] = $data['update_time'];
					$mod_orders->where("orderNo = '{$data['order_id']}'")->save($save_data);
					//写入订单日志
					
					$add_data['orderId'] = $resData['orderId'];

					$add_data['logContent'] = '达达-创建达达运单失败';

//					$add_data['logUserId'] = 0;
//					$add_data['logType'] = 2;
//					$add_data['logTime'] = date("Y-m-d H:i:s");
//					$mod_log_orders->add($add_data);
                    $content = $add_data['logContent'];
                    $logParams = [
                        'orderId' => $resData['orderId'],
                        'logContent' => $content,
                        'logUserId' => 0,
                        'logUserName' => '系统',
                        'orderStatus' => $resData['orderStatus'],
                        'payStatus' => 1,
                        'logType' => 2,
                        'logTime' => date('Y-m-d H:i:s'),
                    ];
                    M('log_orders')->add($logParams);
					
				
				}
			  break;
			default:
			  echo "异常通知";
			}
		
		exit('ok');
		
	  
    }
	
};
?>
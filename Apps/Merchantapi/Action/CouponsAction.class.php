<?php
namespace Merchantapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券控制器
 */
class CouponsAction extends BaseAction{

    /**
     * 查看优惠券列表
     */
    public function getlist(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Coupons');
        $data =  $object = $m->getlist($shopInfo);
        $this->returnResponse(1,'获取成功',$data);
    }

    /**
     * 删除优惠券
     */
    public function deleteYhq(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Coupons');
        $parameter = I();
        $parameter['shopId'] = $shopInfo['shopId'];
        $object = $m->deleteYhq($parameter);
        $this->ajaxReturn($object);
    }



    /**
     * 添加优惠券
     */
    public function addYhq(){
        $shopInfo = $this->MemberVeri();

        $rd = array('status'=>-1);

        $couponName = I('couponName');
        $data['couponName'] = $couponName;
        if(empty($couponName)){
            $rd['error'] = 'couponName字段为空';
            $this->ajaxReturn($rd);
        }

        $couponType = abs((int)I('couponType'));
        $data['couponType'] = $couponType;
        if(empty($couponType)){
            $rd['error'] = 'couponType字段为空';
            $this->ajaxReturn($rd);
        }
        if(!in_array($couponType,array(1))){
            $rd['error'] = '优惠券类型字段超出可选范围';
            $this->ajaxReturn($rd);
        }

        $couponMoney = abs((int)I('couponMoney',0));
        $data['couponMoney'] = $couponMoney;
//		if(empty($couponMoney)){
//			$rd['error'] = 'couponMoney字段为空';
//			$this->ajaxReturn($rd);
//		}

        $spendMoney = abs((int)I('spendMoney',0));
        $data['spendMoney'] = $spendMoney;
//		if(empty($spendMoney)){
//			$rd['error'] = 'spendMoney字段为空';
//			$this->ajaxReturn($rd);
//		}

        $couponDes = I('couponDes');
        $data['couponDes'] = $couponDes;
//		if(empty($couponDes)){
//			$rd['error'] = 'couponDes字段为空';
//			$this->ajaxReturn($rd);
//		}

        $sendNum = (int)I('sendNum',-1);
        $data['sendNum'] = $sendNum;
        /* 		$data['sendNum'] = $sendNum;
                if(empty($sendNum)){
                    $rd['error'] = 'sendNum字段为空';
                    $this->ajaxReturn($rd);
                } */

        /* 		$receiveNum = abs((int)I('receiveNum'));
                $data['receiveNum'] = $receiveNum;
                if(empty($receiveNum)){
                    $rd['error'] = 'receiveNum字段为空';
                    $this->ajaxReturn($rd);
                } */

        $sendStartTime = I('sendStartTime');
        $data['sendStartTime'] = $sendStartTime;
        if(empty($sendStartTime)){
            $rd['error'] = 'sendStartTime字段为空';
            $this->ajaxReturn($rd);
        }

        $sendEndTime = I('sendEndTime');
        $data['sendEndTime'] = $sendEndTime;
        if(empty($sendEndTime)){
            $rd['error'] = 'sendEndTime字段为空';
            $this->ajaxReturn($rd);
        }

        $validStartTime = I('validStartTime');
        $data['validStartTime'] = $validStartTime;
        if(empty($validStartTime)){
            $rd['error'] = 'validStartTime字段为空';
            $this->ajaxReturn($rd);
        }

        $validEndTime = I('validEndTime');
        $data['validEndTime'] = $validEndTime;
        if(empty($validEndTime)){
            $rd['error'] = 'validEndTime字段为空';
            $this->ajaxReturn($rd);
        }

        //二开-test
        $authjson = I('authjson');
        $data['authjson'] = $authjson;
//        if(empty($authjson)){
//            $rd['error'] = 'authjson字段为空';
//            $this->ajaxReturn($rd);
//        }
        $data['expireDays'] = I('expireDays',0,'intval');
        $createTime = date('Y-m-d H:i:s');
        $data['createTime'] = $createTime;


        $m = D('Merchantapi/Coupons');
        $data['shopId'] = $shopInfo['shopId'];
        $object = $m->addYhq($data);
        $this->ajaxReturn($object);

    }



};
?>
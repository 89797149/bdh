<?php

namespace Adminapi\Action;

use Adminapi\Model\CouponsModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 优惠券控制器
 */
class CouponsAction extends BaseAction
{

    /**
     * 查看优惠券列表
     */
    public function index()
    {
        $this->isLogin();
        $this->checkPrivelege('yhqgl_00');
//		$m = D('Adminapi/Coupons');
        $m = new CouponsModel();
        $list = $m->get();
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
    }

    /**
     * 删除优惠券
     */
    public function deleteYhq()
    {
        $this->isLogin();
        $this->checkPrivelege('yhq_02');
//        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());

        $CouponsID = (int)I('id', 0);
        if ($CouponsID <= 0) {
//            $rs['msg'] = '参数不全';
//            $this->ajaxReturn($rs);
            $this->ajaxReturn(returnData(false, -1, 'error', '参数不全'));
        }
        $m = D('Adminapi/Coupons');
        $object = $m->deleteYhq($CouponsID);
        $this->ajaxReturn($object);
    }

    /**
     * 添加优惠券
     */
    public function addYhq()
    {
        $userData = $this->isLogin();
        $this->checkPrivelege('yhq_01');//添加优惠券权限控制


        $couponName = I('couponName');
        $data['couponName'] = $couponName;
        if (empty($couponName)) {
            $this->ajaxReturn(returnData(false, -1, 'error', 'couponName字段为空'));
        }

        $couponType = abs((int)I('couponType'));
        $data['couponType'] = $couponType;
        if (empty($couponType)) {
            $this->ajaxReturn(returnData(false, -1, 'error', 'couponType字段为空'));
        }

        $couponMoney = abs((int)I('couponMoney', 0));
        $data['couponMoney'] = $couponMoney;

        $spendMoney = abs((int)I('spendMoney', 0));
        $data['spendMoney'] = $spendMoney;

        $couponDes = I('couponDes');
        $data['couponDes'] = $couponDes;

        $sendNum = (int)I('sendNum', -1);//-1:不限量
        $data['sendNum'] = $sendNum;

        $sendStartTime = I('sendStartTime');
        if (empty($sendStartTime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择发放开始时间'));
        }
        $data['sendStartTime'] = $sendStartTime;


        $sendEndTime = I('sendEndTime');
        if (empty($sendEndTime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择发放结束时间'));
        }
        $data['sendEndTime'] = $sendEndTime;


        $validStartTime = I('validStartTime');
        $data['validStartTime'] = $validStartTime;
        if (empty($validStartTime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择活动开始时间'));
        }


        $validEndTime = I('validEndTime');
        $data['validEndTime'] = $validEndTime;
        if (empty($validEndTime)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择活动结束时间'));
        }


        $authjson = I('authjson');
        $data['authjson'] = $authjson;
        $data['expireDays'] = I('expireDays', 0, 'intval');
        $createTime = date('Y-m-d H:i:s');
        $data['createTime'] = $createTime;


        $m = new CouponsModel();

        $object = $m->addYhq($data,$userData);
        $this->ajaxReturn($object);

    }

    /**
     * 通用优惠券列表
     */
    public function getCouponList()
    {
        $list = D('Adminapi/Coupons')->getCouponList();
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
    }

    /**
     * 商城满减优惠券列表
     */
    public function getMallCouponList()
    {
        $this->isLogin();
        $list = D('Adminapi/Coupons')->getMallCouponList();
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
    }

    /**
     * 唤醒赠送劵
     */
    public function getPresentedList()
    {
        $this->isLogin();
        $list = D('Adminapi/Coupons')->getPresentedList();
//        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $rs = returnData($list);
        $this->ajaxReturn($rs);
    }

    /**
     * 优惠券编辑
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/tsfqgn
     * @param int couponId 优惠券id
     * @param date sendEndTime 发放结束时间
     * @param date validEndTime 活动结束时间
     * @return json
     */
    public function updateCoupon()
    {
        $this->isLogin();
        $mode = new CouponsModel();
        $save = I();
        $couponId = (int)$save['couponId'];
        if (empty($couponId)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        if (empty($save['sendEndTime'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择发放结束时间'));
        }
        if (empty($save['validEndTime'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择活动结束时间'));
        }
        $result = $mode->updateCoupon($save);
        $this->ajaxReturn($result);
    }
}
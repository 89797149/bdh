<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 拉新服务类
 */
class NewpeopleModel extends BaseModel {

    /**
     * 获取拉新列表
     */
    /*public function get($page){
       $mod = M('user_invitation');
       $mod_users = M('users');
       $mod_orders = M('orders');
        $pageDataNum = 50;//每页50条数据


       $count = $mod->count();
       //return $count;
       $dataok = $mod
       ->order("createTime desc")
       ->limit(($page-1)*$pageDataNum,$pageDataNum)
       ->select();

       $ret['code'] = 1;
       $ret['msg'] = '拉新列表';
        //return $dataok;
       $pageCount = (int)ceil($count/$pageDataNum);
       $where['orderStatus'] = 4;
       $where['isPay'] = 1;


       for($i=0;$i<count($dataok);$i++){

           $where['userId'] = $dataok[$i]['userId'];
           $dataok[$i]['isok'] = $mod_orders->where($where)->count() >=1 ? 1:-1;//是否是成功邀请
           $dataok[$i]['userData'] = $mod_users->where("userId = '{$where['userId']}'")->field('userName,userPhoto')->find();//是否是成功邀请



       }
       $ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);


       return $ret;


    }*/

    /**
     * 获取拉新列表
     */
    public function get($page=1,$pageSize=50){
        $mod = M('user_invitation ui');
        $mod_users = M('users');
        $mod_orders = M('orders');
//        $pageDataNum = 1;//每页50条数据
        $where = " ui.userId > 0 ";

        //用户名
        $userName = I('userName');
        if(!empty($userName)){
            $where .= " AND u.userName like '%".$userName."%' ";
        }

        //被拉用户名
        $userPullName = I('userPullName');
        if(!empty($userPullName)){
            $where .= " AND up.userName like '%".$userPullName."%' ";
        }

        //用户手机号
        $userPhone = I('userPhone');
        if(!empty($userPhone)){
            $where .= " AND u.userPhone like '".$userPhone."%' ";
        }

        //被拉手机号
        $userPullPhone = I('userPullPhone');
        if(!empty($userPullPhone)){
            $where .= " AND up.userPhone like '".$userPullPhone."%' ";
        }

        //邀请状态
        $invitationStatus = I('invitationStatus');
        if(!empty($invitationStatus)){
            $where .= " AND ui.invitationStatus = '".$invitationStatus."' ";
        }

        //邀请时间段 - 开始
        $startTime = I('startTime');
        if(!empty($startTime)){
            $where .= " AND ui.createTime >= '".$startTime."' ";
        }
        //邀请时间段 - 结束
        $endTime = I('endTime');
        if(!empty($endTime)){
            $where .= " AND ui.createTime <= '".$endTime."' ";
        }
        $where .= " and up.userFlag=1 ";

        $count = $mod
            ->where($where)
            ->join("LEFT JOIN wst_users u ON u.userId=ui.userId ")
            ->join("LEFT JOIN wst_users up ON up.userId=ui.UserToId ")
            ->count();
        $dataok = $mod
            ->join("LEFT JOIN wst_users u ON u.userId=ui.userId ")
            ->join("LEFT JOIN wst_users up ON up.userId=ui.UserToId ")
            ->where($where)
            ->field("ui.*,u.userPhone,u.userPhoto,u.userName,up.userPhone as userPullPhone,up.userName as userPullName")
            ->order("ui.createTime DESC")
            ->limit(($page-1)*$pageSize,$pageSize)
            ->select();

        $ret['code'] = 0;
        $ret['msg'] = '操作成功';
        //return $dataok;
        $pageCount = (int)ceil($count/$pageSize);
        for($i=0;$i<count($dataok);$i++){
            $where['userId'] = $dataok[$i]['UserToId'];
            $dataok[$i]['userData'] = (array)$mod_users->where("userId = '{$dataok[$i]['UserToId']}'")->field('userName,userPhone,userPhoto')->find();
        }
//        $ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount,'dataNum'=>!empty($count)?$count:0);
        $ret['data'] = array('total'=>!empty($count)?$count:0,'pageSize'=>$pageSize,'start'=>($page-1)*$pageSize,'root'=>(array)$dataok,'totalPage'=>$pageCount == 0 ? 1 : $pageCount,'currPage'=>$page);

        return (array)$ret;


    }

    //通过手机号搜索用户
    public function getuser($phone){
        $mod_users = M('users');

        $where['userPhone'] = $phone;
        $data = $mod_users->where($where)->find();
        if(!empty($data)){
            $ret['code'] = 0;
            $ret['msg'] = '用户信息';
            $ret['data'] = $data;
            return $ret;
        }

        $ret['code'] = -1;
        $ret['msg'] = '未找到用户';
        $ret['data'] = null;
        return $ret;

    }

    //获取优惠券数量
    public function getCoupons($userId){
        $mod_coupons_users = M('coupons_users');

        $where['couponStatus'] = 1;
        $where['userId'] = $userId;
        $where['dataFlag'] = 1;


        $ret['code'] = 0;
        $ret['msg'] = '优惠券数量';
        $ret['data'] =  $mod_coupons_users->where($where)->count();
        return $ret;
    }

    //结算
    public function Settlement($userId){

        $mod_coupons_users = M('coupons_users');

        $where['couponStatus'] = 1;
        $where['userId'] = $userId;
        $where['dataFlag'] = 1;

        $save['dataFlag'] = -1;
        $save['couponStatus'] = -1;
        $count = $mod_coupons_users->where($where)->save($save);
        if($count){
            $ret['code'] = 0;
            $ret['msg'] = '已结算的优惠券';
            $ret['data'] =  $count;
            return $ret;
        }else{
            $ret['code'] = -1;
            $ret['msg'] = '结算失败';
            $ret['data'] =  $count;
            return $ret;
        }
    }




};
?>
<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员相关类
 */
class UsersModel extends BaseModel {
    /**
     * 搜索会员
     */
    public function usersRow($data){
        $ret = array(
            'status' => -1,
            'msg' => '数据获取失败',
        );
        if(!isset($data['userPhone']) || empty($data['userPhone'])){
            $ret['msg'] = '手机号不能为空';
            return $ret;
        }
        $m = M('users');
        $where = "userPhone = '".$data['userPhone']."'";
        if ($data['balance_start'] > 0) $where .= " and balance >= ".$data['balance_start'];
        if ($data['balance_end'] > 0) $where .= " and balance <= ".$data['balance_end'];
        $list = $m->where($where)->select();
        $ret['list'] = $list;//数据列表
        if($ret['list']){
            $ret['status'] = 1;
            $ret['msg'] = '数据获取成功';
        }
        return $ret;
    }
};
?>
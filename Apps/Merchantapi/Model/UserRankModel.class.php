<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 会员等级类(使用总后台等级时使用)
 */
class UserRankModel extends BaseModel {
    /**
     * 等级列表
     */
    public function rankList($data){
        $mod = M('user_ranks');
        $res = $mod
            ->field('rankId,rankName')
            ->order('rankId DESC')
            ->select();
        $returnData = array(
            'status' => -1,
            'msg' => '数据获取失败',
        );
        //次步骤是为了和商家等级的数据字段一致
        foreach ($res as $Key=>&$val){
            $val['shopId'] = 0;
            $val['state'] = 1;
        }
        if($res){
            $returnData['status'] = 1;
            $returnData['list'] = $res;
            $returnData['msg'] = '数据获取成功';
        }
        return $returnData;
    }

    /**
     * 添加等级
     */
    public function rankAdd($data){
        $mod = M('user_ranks');
        $res = $mod->add($data);
        $returnData = array(
            'status' => -1,
            'msg' => '添加失败',
        );
        if($res){
            $returnData['status'] = 1;
            $returnData['msg'] = '添加成功';
        }
        return $returnData;
    }

    /**
     * 编辑等级
     */
    public function rankEdit($data){
        $mod = M('user_ranks');
        $res = $mod->where("rankId='".$data['rankId']."'")->save($data);
        $returnData = array(
            'status' => -1,
            'msg' => '编辑失败',
        );
        if($res !== false){
            $returnData['status'] = 1;
            $returnData['msg'] = '编辑成功';
        }
        return $returnData;
    }

    /**
     * 删除等级
     */
    /*public function rankDel($data){
        $ids = trim($data['id'],',');
        if(empty($ids)){
            $ids = 0;
        }
        $shopId = $data['shopId'];
        $mod = M('rank');
        $res = $mod->where("shopId='".$shopId."' AND rankId IN($ids)")->save(['state' => -1]);
        $returnData = array(
            'status' => -1,
            'msg' => '删除失败',
        );
        if($res){
            $returnData['status'] = 1;
            $returnData['msg'] = '删除成功';
        }
        return $returnData;
    }*/
};
?>
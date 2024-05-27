<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 官网设置
 */
class SettingModel extends BaseModel {

    /**
     * 分页列表
     */
    public function getIndexList($page=1,$pageSize=15){
        $sql = "SELECT id,pic,url,sort,addTime FROM __PREFIX__setting_index_banner ORDER BY id DESC";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return $rs;
    }

    /**
     * 新增banner
     */
    public function indexBannerAddDo($response){
        $rd = ['code' => -1,'msg' => '添加失败','data'=>[]];
        if(!empty($response)){
            $rs = M('setting_index_banner')->add($response);
            if($rs){
                $rd['code'] = 0;
                $rd['msg'] = '添加成功';
            }
        }
        return $rd;
    }

    /**
     * 编辑banner
     */
    public function indexBannerEditDo($response){
        $rd = ['code' => -1,'msg' => '编辑失败','data'=>[]];
        if(!empty($response)){
            $rs = M('setting_index_banner')->where(['id'=>$response['id']])->save($response);
            if($rs !== false){
                $rd['code'] = 0;
                $rd['msg'] = '编辑成功';
            }
        }
        return $rd;
    }

    /**
     * 删除
     */
    public function indexBannerDel($response){
        $rd = ['code' => -1,'msg'=>'操作失败','data'=>[]];
        if(!empty($response)){
            $rs = M('setting_index_banner')->where("id='".$response['id']."'")->delete();
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
        return $rd;
    }

    /**
     * 其他图片设置操作
     */
    public function serverBannerEdit($response){
        $rd = ['code' => -1,'msg' => '编辑失败','data'=>[]];
        if(!empty($response)){
            $tab = M('setting');
            foreach ($response as $key=>$value){
                $info = $tab->where(['name'=>$key])->find();
                if($info){
                    $res = $tab->where(['name'=>$key])->save(['value'=>$value]);
                }else{
                    $insert = [];
                    $insert['name'] = $key;
                    $insert['value'] = $value;
                    $res = $tab->add($insert);
                }
            }
            if($res !== false){
                $rd['code'] = 0;
                $rd['msg'] = '编辑成功';
            }
        }
        return $rd;
    }

}
?>
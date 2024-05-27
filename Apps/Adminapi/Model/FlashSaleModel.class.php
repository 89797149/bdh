<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 限时购
 */
class FlashSaleModel extends BaseModel {
    /**
     * 分页列表
     */
    public function queryByPage($page=1,$pageSize=15){
        $where = " WHERE isDelete=0 ";
        $sql = "SELECT id,startTime,endTime,state FROM __PREFIX__flash_sale $where ORDER BY id DESC";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return $rs;
    }

    /**
	  * 新增
	  */
	 public function insert($request){
	 	$rd = ['code' => -1,'msg' => '操作失败','data'=>[]];
		if(!empty($request)){
			$rs = $this->add($request);
			if($rs){
				$rd['code'] = 0;
				$rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit($request){
         $rd = ['code' => -1,'msg' => '操作失败','data'=>[]];
		if(!empty($request)){
			$rs = $this->where("id='".$request['id']."'")->save($request);
			if($rs !== false){
				$rd['code'] = 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 }

	 /**
	  * 获取指定对象
	  */
     public function getInfo($id){
		return $this->where("id='".$id."'")->find();
	 }

    /**
     * 获取指定对象
     */
    public function getList($where){
        return $this->where($where)->select();
    }
	  
	 /**
	  * 删除
	  */
	 public function del($request){
	 	$rd = ['code' => -1,'msg'=>'操作失败'];
	 	if(!empty($request)){
	 	    $edit['isDelete'] = 1;
            $rs = $this->where("id='".$request['id']."'")->save($edit);
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
		return $rd;
	 }
}
?>
<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 新闻动态
 */
class NewsModel extends BaseModel {
    /**
     * 分页列表
     */
    public function queryByPage($page=1,$pageSize=15){
        $param = I();
        $where = " WHERE isDelete=0 ";
        if(!empty($param['title'])){
            $where .= " AND title like '%".I('title')."%'";
        }
        $sql = "SELECT id,title,content,addTime FROM __PREFIX__news $where ORDER BY id DESC";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        return $rs;
    }

    /**
	  * 新增
	  */
	 public function insert($response){
	 	$rd = ['code' => -1,'msg' => '添加失败','data'=>[]];
	 	$newInfo = $this->where("title='".$response['title']."'")->find();
	 	if($newInfo){
            $rd['msg'] = '该新闻标题已经存在';
            return $rd;
        }
		if(!empty($response)){
			$rs = $this->add($response);
			if($rs){
				$rd['code'] = 0;
				$rd['msg'] = '添加成功';
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit($response){
         $rd = ['code' => -1,'msg' => '修改失败','data'=>[]];
		if(!empty($response)){
			$rs = $this->where("id='".$response['id']."'")->save($response);
			if($rs !== false){
				$rd['code'] = 0;
                $rd['msg'] = '修改成功';
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
	 public function del($response){
	 	$rd = ['code' => -1,'msg'=>'操作失败','data'=>[]];
	 	if(!empty($response)){
	 	    $edit['isDelete'] = 1;
            $rs = $this->where("id='".$response['id']."'")->save($edit);
            if($rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';
            }
        }
		return $rd;
	 }
}
?>
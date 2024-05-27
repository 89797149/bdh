<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 文章服务类
 */
class ArticlesModel extends BaseModel {
    /**
     * @param $staffId
     * @return mixed
     * 新增文章
     */
	 public function insert($staffId){
        $rd = returnData(false, -1, 'error', '操作失败');
		$data = [];
	    if($this->checkEmpty($data,true)){
            $data["catId"] = (int)I("catId");
            $data["articleTitle"] = I("articleTitle");
            $data["isShow"] = (int)I("isShow",0);
            $data["articleContent"] = I("articleContent");
            $data["articleKey"] = I("articleKey");
            $data["staffId"] = $staffId;
            $data["createTime"] = date('Y-m-d H:i:s');
			$rs = $this->add($data);
		    if(false !== $rs){
                $rd = returnData(true,0,'success','操作成功');
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit($staffId){
         $rd = returnData(false, -1, 'error', '操作失败');
         $data = [];
	    if($this->checkEmpty($data,true)){
            $data["catId"] = (int)I("catId");
            $data["articleTitle"] = I("articleTitle");
            $data["isShow"] = (int)I("isShow",0);
            $data["articleContent"] = I("articleContent");
            $data["articleKey"] = I("articleKey");
            $data["staffId"] = $staffId;
		    $rs = $this->where("articleId=".(int)I('id',0))->save($data);
			if(false !== $rs){
                $rd = returnData(true,0,'success','操作成功');
				
			}
		}
		return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get(){
		$data = $this->where("articleId=".(int)I('id'))->find();
		if(!empty($data)){
            $data['articleContent'] = htmlspecialchars_decode($data['articleContent']);
        }
		return (array)$data;
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
         $field = "a.articleId,a.articleTitle,a.isShow,a.createTime,s.staffName";
         $sql = "select {$field} from __PREFIX__articles a LEFT join  __PREFIX__staffs s ON s.staffId = a.staffId ";
         if(!empty(I('articleTitle'))){
             $sql .=" where a.articleTitle like '%".WSTAddslashes(I('articleTitle'))."%' ";
         }
         $sql .=' group by a.articleId order by a.articleId desc ';
         return $this->pageQuery($sql,$page,$pageSize);
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
	     $sql = "select * from __PREFIX__articles where isShow =1 order by articleId desc";
		 $rs = $this->query($sql);
		 return (array)$rs;
	  }
	  
	 /**
	  * 删除
	  */
	 public function del(){
         $rd = returnData(false, -1, 'error', '操作失败');
	    $rs = $this->delete((int)I('id'));
		if(false !== $rs){
            $rd = returnData(true,0,'success','操作成功');
		}
		return $rd;
	 }
	 /**
	  * 显示分类是否显示/隐藏
	  */
	 public function editiIsShow(){
         $rd = returnData(false, -1, 'error', '操作失败');
	 	if(I('id',0)==0)return $rd;
	 	$this->isShow = ((int)I('isShow')==1)?1:0;
	 	$rs = $this->where("articleId=".(int)I('id',0))->save();
	    if(false !== $rs){
            $rd = returnData(true,0,'success','操作成功');
		}
	 	return $rd;
	 }
};
?>
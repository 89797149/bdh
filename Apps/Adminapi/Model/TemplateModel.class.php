<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 模板服务类
 */
class TemplateModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
         $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $data = I('param.');
         unset($data['token']);
         $data['wntFlag'] = 1;
         $result = M('wx_news_template')->add($data);
         if ($result) {
             $rd['code'] = 0;
             $rd['msg'] = '操作成功';
         }
         return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit(){
         $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());

         $id = I('id',0,'intval');
         if (empty($id)) {
             $rd['msg'] = '参数不全';
             return $rd;
         }

         $data = I('param.');
         unset($data['id']);
         unset($data['token']);

         $result = M('wx_news_template')->where(array('id'=>$id))->save($data);
         if ($result) {
             $rd['code'] = 0;
             $rd['msg'] = '操作成功';
         }
         return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get($where){
         return M('wx_news_template')->where($where)->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
         $template_id = I('template_id','','trim');
         $title = I('title','','trim');
         $sql = "select * from __PREFIX__wx_news_template where wntFlag = 1 ";
         if (!empty($template_id)) $sql .= " and template_id = '" . $template_id . "' ";
         if (!empty($title)) $sql .= " and title = '" . $title . "' ";
         $sql .= " order by id desc";
         return $this->pageQuery($sql,$page,$pageSize);
	 }

    /**
     * 获取所有模板
     * @param $where
     * @return mixed
     */
    public function getTemplateList($where){
        return M('wx_news_template')->where($where)->order('id desc')->select();
    }

    /**
     * 获取所有模板
     * @param $where
     * @return mixed
     */
    public function wxTemplateDetail($where){
        return M('wx_news_template')->where($where)->order('id desc')->find();
    }
	  
	 /**
	  * 删除
	  */
	 public function del(){
         $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());

         $id = I('id',0,'intval');
         if(empty($id)) {
             $rd['msg'] = '参数不全';
             return $rd;
         }
         $result = M('wx_news_template')->where("id=".$id)->save(array('wntFlag'=>-1));
         if ($result) {
             $rd['code'] = 0;
             $rd['msg'] = '操作成功';
         }

         return $rd;
	 }
};
?>
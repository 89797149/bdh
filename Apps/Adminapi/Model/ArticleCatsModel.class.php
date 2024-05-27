<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 文章分类服务类
 */
class ArticleCatsModel extends BaseModel {
    /**
      * 新增
      */
     public function insert(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I("id",0);
        $data = array();
        $data["parentId"] = (int)I("parentId");
        $data["catType"] = (int)I("catType",0);
        $data["isShow"] = (int)I("isShow",1);
        $data["catName"] = I("catName");
        $data["catSort"] = (int)I("catSort",0);
        $data["catFlag"] = 1;
        if($this->checkEmpty($data,true)){
            $rs = $this->add($data);
            if(false !== $rs){
//                $rd['code']= 0;
//                $rd['msg'] = '操作成功';
                $rd = returnData(true,0,'success','操作成功');
            }
        }
        return $rd;
     }
     /**
      * 修改
      */
     public function edit(){
        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $id = (int)I("id",0);
        $data = array();
        $data["isShow"] = (int)I("isShow");
        $data["catName"] = I("catName");
        $data["catSort"] = (int)I("catSort");
        if($this->checkEmpty($data)){
            $rs = $this->where("catId=".(int)I('id',0))->save($data);
            if(false !== $rs){
                $rd['code']= 0;
                $rd['msg'] = '操作成功';

            }
        }
        return $rd;
     }
     /**
      * 修改名称
      */
     public function editName(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I("id",0);
        $data = array();
        $data["catName"] = I("catName");
        if($this->checkEmpty($data)){
            $rs = $this->where("catFlag=1 and catId=".(int)I('id'))->save($data);
            if(false !== $rs){
//                $rd['code']= 0;
//                $rd['msg'] = '操作成功';
                $rd = returnData(true,0,'success','操作成功');
            }
        }
        return $rd;
     }
     /**
      * 获取指定对象
      */
     public function get(){
        return (array)$this->where("catId=".(int)I('id'))->find();
     }
     /**
      * 分页列表
      */
     public function queryByPage($pid,$page=1,$pageSize=15){
        $sql = "select * from __PREFIX__article_cats where catFlag=1 and parentId=$pid order by catSort asc,catId asc";
        $result = $this->pageQuery($sql,$page,$pageSize);
         $m = M('article_cats');
         if (!empty($result['root'])) {
             foreach ($result['root'] as $k=>$v) {
                 $child_2 = $m->where("catFlag=1 and parentId=".$v['catId'])->order('catSort asc,catId asc')->select();
                 $result['root'][$k]['child'] = (array)$child_2;
                 if (!empty($child_2)) {
                     foreach ($child_2 as $kk=>$vv){
                         $child_3 = $m->where("catFlag=1 and parentId=".$vv['catId'])->order('catSort asc,catId asc')->select();
                         $result['root'][$k]['child'][$kk]['child'] = (array)$child_3;
                     }
                 }
             }
         }
         return $result;
     }
     /**
      * 获取列表
      */
      public function queryByList($pid){
         return (array)$this->where('catFlag=1 and parentId='.$pid)->order('catSort asc,catId asc')->select();
      }

    /**
     * 获得文章分类列表
     */
    public function getArticleCatList($pid){
        return (array)$this->where('isShow = 1 and catFlag=1 and parentId='.$pid)->order('catSort asc,catId asc')->select();
    }
     /**
      * 迭代获取下级
      */
     public function getChild($ids = array(),$pids = array()){
        $sql = "select catId from __PREFIX__article_cats where catFlag=1 and parentId in(".implode(',',$pids).")";
        $rs = $this->query($sql);
        if(count($rs)>0){
            $cids = array();
            foreach ($rs as $key =>$v){
                $cids[] = $v['catId'];
            }
            $ids = array_merge($ids,$cids);
            return $this->getChild($ids,$cids);

        }else{
            return $ids;
        }
     }

     /**
      * 删除
      */
     public function del(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
        //获取子集
        $ids = array();
        $ids[] = (int)I('id');
        if(empty($ids)){
            return $rd;
        }
        $ids = $this->getChild($ids,$ids);
        $this->catFlag = -1;
        $rs = $this->where(" catId in(".implode(',',$ids).")")->save();
        if(false !== $rs){
//           $rd['code']= 0;
//            $rd['msg'] = '操作成功';
            $rd = returnData(true,0,'success','操作成功');
        }
        return $rd;
     }
     /**
      * 显示文章分类是否显示/隐藏
      */
     public function editiIsShow(){
//        $rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
        $id = (int)I('id');
        if($id==0)return $rd;
        //获取子集
        $ids = array();
        $ids[] = $id;
        $ids = $this->getChild($ids,$ids);

        $this->isShow = (I('isShow')==1)?1:0;
        $rs = $this->where("catId in(".implode(',',$ids).")")->save();
        if(false !== $rs){
//            $rd['code']= 0;
//            $rd['msg'] = '操作成功';
            $rd = returnData(true,0,'success','操作成功');
        }
        return $rd;
     }

     /**
      * 获取所有的类别，并且添加层级
      */
     public function getCatLists(){
        $sql = "select * from __PREFIX__article_cats where catFlag = 1 order by catSort asc";
        $catList = $this->query($sql);
        if ($catList !== false) {
            $catList = self::unlimitedForLevel($catList);
        }
        return $catList;
     }

     Static Public function unlimitedForLevel($cate,$html='&nbsp;&nbsp;',$parentId=0,$level=0){
        $arr = array();
        foreach ($cate as $v) {
            if ($v['parentId'] == $parentId) {
                $v['level'] = $level + 1;
                $html2 = $level==0 ? '' : '|--';//生成目录|--
                $v['html'] = str_repeat($html,$level).$html2;
                $v['catName'] = $v['html'].$v['catName'];
                $arr[]=$v;
                $arr = array_merge($arr,self::unlimitedForLevel($cate,$html,$v['catId'],$level + 1));
            }
        }
        return $arr;
    }
};
?>
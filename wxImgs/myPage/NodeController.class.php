<?php
namespace Admin\Controller;

//节点信息管理控制器
class NodeController extends CommonController{
    
    /*
    //浏览节点信息  
	public function index(){
	   //获取当前页号
	   $p = !empty($_REQUEST['pageNum'])?$_REQUEST['pageNum']:1;
	   $_GET['p']=$p; //给page对象使用
	   //封装多少条数据一页
	   $pageSize = !empty($_REQUEST['numPerPage'])?$_REQUEST['numPerPage']:5;
	   //封装排序
	   $order = !empty($_REQUEST['_order'])?$_REQUEST['_order']:"id";
	   $sort = !empty($_REQUEST['_sort'])?$_REQUEST['_sort']:"asc";
	   
	
       $mod = M("Node"); //实例化Model类
	   $total = $mod->count(); //获取总数据条数
	   $page = new \Think\Page($total,$pageSize); //创建分页对象
	   $list = $mod->limit($page->firstRow,$page->listRows)->order($order." ".$sort)->select(); //获取所有信息
	   $this->assign("list",$list); //放置到模板中
	   
	   $this->assign("totalCount",$total); //封装总数据条数
	   $this->assign("numPerPage",$pageSize); //页大小
	   $this->assign("currentPage",$p); //当前页
	   
	   $this->display("index"); //加载模板输出
    }
*/
}
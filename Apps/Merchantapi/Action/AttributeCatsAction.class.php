<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 属性类型控制器
 */
class AttributeCatsAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$shopInfo = $this->MemberVeri();
	    $m = D('Home/AttributeCats');
    	$object = array();
    	if((int)I('id',0)>0){
    		$object = $m->get();
    	}else{
    		$object = $m->getModel();
    	}
    	$this->assign('object',$object);
    	$this->assign('umark',"AttributeCats");
		$this->view->display('default/shops/attributecats/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/AttributeCats');
    	$rs = array();
    	if(!I('id')){
            $this->returnResponse(1,'操作是啊比',array());
        }
        $rs = $m->edit($shopInfo);
    	$this->ajaxReturn($rs);
	}

    /**
     * 新增/修改操作
     */
    public function insert(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/AttributeCats');
        $rs = array();
        $rs = $m->insert($shopInfo);
        $this->ajaxReturn($rs);
    }
	/**
	 * 删除操作
	 */
	public function del(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/AttributeCats');
    	$rs = $m->del($shopInfo);
    	$this->ajaxReturn($rs);
	}
//	/**
//	 * 分页查询
//	 */
//	public function index(){
//		$shopInfo = $this->MemberVeri();
//		$m = D('Home/AttributeCats');
//		$list = $m->queryByList($shopInfo);
//        $this->returnResponse(1,'获取成功',$list);
//	}
    /**
     * 分页查询
     */
    public function getlist(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/AttributeCats');
        $list = $m->queryByList($shopInfo);
        $this->returnResponse(1,'获取成功',$list);
    }
	/**
	 * 获取属性分类列表
	 */
	public function queryByList(){
		//获取商品属性分类信息
		$m = D('Home/AttributeCats');
		$list = $m->queryByList();
		$rs = array('status'=>1,'list'=>$list);
    	$this->ajaxReturn($rs);
	}
};
?>
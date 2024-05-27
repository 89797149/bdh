<?php
 namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 属性控制器
 */
class AttributesAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$shopInfo = $this->MemberVeri();
		
	    $m = D('Home/Attributes');
    	$object = array();
    	if((int)I('id',0)>0){
    		$object = $m->get();
    	}else{
    		$object = $m->getModel();
    		$object['catId'] = (int)I('catId');
    	}
    	$m = D('Home/AttributeCats');
		$this->assign('cat',$m->get((int)$object['catId']));
    	$this->assign('object',$object);
    	$this->assign('umark',"AttributeCats");
		$this->view->display('default/shops/attributes/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/Attributes');
    	$rs = array();
    	$rs = $m->edit();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/Attributes');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/AttributeCats');
		$this->assign('cat',$m->get((int)I('catId')));
		$this->assign('catList',$m->queryByList());
		$m = D('Home/Attributes');
    	$list = $m->queryByPage();
    	$this->assign('List',$list);
    	$this->assign('umark',"AttributeCats");
        $this->display("default/shops/attributes/list");
	}
	
	/**
	 * 获取列表
	 */
	public function getAttributes(){
		$m = D('Home/Attributes');
    	$list = $m->queryByListForGoods();
    	$rs = array('status'=>1,'list'=>$list);
    	$this->ajaxReturn($rs);
	}

	//以上代码是之前的,不做处理
    /**
     *属性列表
     */
    public function getAttributesList(){
        $m = D('Home/Attributes');
        $res = $m->getAttributesList();
        $this->ajaxReturn($res);
    }

    /**
     * 添加或编辑商品屬性
     */
    public function attributesSave(){
        $parameter = I();
        $m = D('Home/Attributes');
        $data = array();
        !empty($parameter['attrId'])?$data['attrId']=$parameter['attrId']:false;
        !empty($parameter['attrName'])?$data['attrName']=$parameter['attrName']:false;
        !empty($parameter['isPriceAttr'])?$data['isPriceAttr']=$parameter['isPriceAttr']:$data['isPriceAttr']=0;
        !empty($parameter['isMandatory'])?$data['isMandatory']=$parameter['isMandatory']:$data['isMandatory']=0;
        !empty($parameter['isCheckbox'])?$data['isCheckbox']=$parameter['isCheckbox']:$data['isCheckbox']=0;
        if(isset($data['attrId']) && !empty($data['attrId'])){
            //编辑
            $res = $m->attributesEdit($data);
        }else{
            //添加
            $data['createTime'] = date('Y-m-d H:i:s',time());
            $res = $m->attributesAdd($data);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 删除屬性
     */
    public function attributesDel(){
        $parameter = I();
        $data = array();
        !empty($parameter['attrId'])?$data['attrId']=$parameter['attrId']:false;
        $m = D('Home/Attributes');
        $res = $m->attributesDel($data);
        $this->ajaxReturn($res);
    }

    /**
     *获取复用商品的属性列表
     */
    public function getAttributesListCopy(){
        $m = D('Home/Attributes');
        $res = $m->getAttributesListCopy();
        $this->ajaxReturn($res);
    }

};
?>
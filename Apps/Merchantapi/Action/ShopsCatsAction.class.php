<?php
 namespace Merchantapi\Action;

 use App\Enum\ExceptionCodeEnum;
 use Home\Model\ShopsCatsModel;

 ;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺分类控制器
 */
class ShopsCatsAction extends BaseAction{
	/**
	 * 修改名称
	 */
    public function editName(){
    	$shopInfo = $this->MemberVeri();
		$m = D('Home/ShopsCats');
    	$rs = array();
    	if((int)I('id',0)>0){
    		$rs = $m->editName();
    	}
    	$this->ajaxReturn($rs);
	}
    /**
	 * 修改排序
	 */
    public function editSort(){
    	$shopInfo = $this->MemberVeri();
		$m = D('Home/ShopsCats');
    	$rs = array();
    	if((int)I('id',0)>0){
    		$rs = $m->editSort();
    	}
    	$this->ajaxReturn($rs);
	}

    /**
     * 修改typeImg
     */
    public function editTypeImg(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/ShopsCats');
        $rs = array();
        if((int)I('id',0)>0){
            $rs = $m->editTypeImg();
        }
        $this->ajaxReturn($rs);
    }


	    /**
	 * 修改icon
	 */
    public function editIcon(){
    	$shopInfo = $this->MemberVeri();
		$m = D('Home/ShopsCats');
    	$rs = array();
    	if((int)I('id',0)>0){
    		$rs = $m->editIcon();
    	}
    	$this->ajaxReturn($rs);
	}

	/**
	 * 批量保存商品分类
	 */
	public function batchSaveShopCats(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/ShopsCats');
		$rs = $m->batchSaveShopCats($shopInfo);
    	$this->ajaxReturn($rs);
	}
    /**
     * 保存分类
     */
    public function addCats(){
        $parameter = I();
        $shopInfo = $this->MemberVeri();
        $parameter['shopId'] = $shopInfo['shopId'];
        $m = new ShopsCatsModel();
        $rs = $m->addCats($parameter);
        if($rs){
            $this->returnResponse(1,'操作成功',[]);
        }else{
            $this->returnResponse(-1,'操作失败',[]);
        }
    }
	/**
	 * 删除操作
	 */
	public function del(){
		$shopInfo = $this->MemberVeri();
		$m = D('Home/ShopsCats');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 列表
	 */
	public function getlist(){
		$shopInfo = $this->MemberVeri();
		// $USER = session('WST_USER');
		$m = D('Home/ShopsCats');
      	$List = $m->getCatAndChild($shopInfo['shopId'],(int)I('parentId',0));
        $this->returnResponse(1,'获取成功',(array)$List);
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/ShopsCats');
		// $USER = session('WST_USER');
		$list = $m->queryByList($shopInfo['shopId'],(int)I('id',0));
        $this->returnResponse(1,'获取成功',(array)$list);
	}

	public function changeCatStatus(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/ShopsCats');
		$rs = $m->changeCatStatus($shopInfo);
		$this->ajaxReturn($rs);
	}

    public function changeCatShowStatus(){
        $shopInfo = $this->MemberVeri();
        $m = D('Home/ShopsCats');
        $rs = $m->changeCatShowStatus($shopInfo);
        $this->ajaxReturn($rs);
    }

    //上面的编辑分类让人受不了,下面重写一个
    /**
     * 店铺分类-修改店铺分类
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/mp7ucz
     * */
    public function updateShopCat(){
        $this->MemberVeri();
        $params = I();
        if(empty($params['catId'])){
            $this->ajaxReturn(returnData(false,ExceptionCodeEnum::FAIL,'error',"缺少必填参数catId"));
        }
        if(isset($params['distributionLevel1Amount'])){
            if((float)$params['distributionLevel1Amount'] < 0){
                $this->ajaxReturn(returnData(false,ExceptionCodeEnum::FAIL,'error',"请输入正确的一级分销金额百分比"));
            }
        }
        if(isset($params['distributionLevel2Amount'])){
            if((float)$params['distributionLevel2Amount'] < 0){
                $this->ajaxReturn(returnData(false,ExceptionCodeEnum::FAIL,'error',"请输入正确的二级分销金额百分比"));
            }
        }
        $mod = new ShopsCatsModel();
        $result = $mod->updateShopCat($params);
        $this->ajaxReturn($result);
    }

}
?>
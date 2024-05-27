<?php
 namespace Home\Model;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Shops\ShopCatsModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 店铺分类服务类
 */
class ShopsCatsModel extends BaseModel {
	/**
	 * 批量保存商品分类
	 */
	public function batchSaveShopCats($parameter=array()){
		$rd = array('status'=>-1);
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
        $shopId = $shopId?$shopId:$parameter['shopId'];
		$m = M('shops_cats');
		//先保存了已经有父级的分类
		$otherNo = (int)I('otherNo');
		for($i=0;$i<$otherNo;$i++){
			$data = array();
			$data['catName'] = I('catName_o_'.$i);
			if($data['catName']=='')continue;
			$data['shopId'] = $shopId;
			$data['parentId'] = (int)I('catId_o_'.$i);
			$data['catSort'] = (int)I('catSort_o_'.$i);
			$data['isShow'] = (int)I('catShow_o_'.$i);
			$sql = "select catId from __PREFIX__shops_cats where catFlag=1 and shopId=".$shopId." and catId=".$data['parentId'];
			$rs = $this->query($sql);
			if(empty($rs))continue;
			$m->add($data);
		}
		//保存没有父级分类的
		$fristNo = (int)I('fristNo');
	    for($i=0;$i<$fristNo;$i++){
			$data = array();
			$data['catName'] = I('catName_'.$i);
			if($data['catName']=='')continue;
			$data['parentId'] = 0;

			$data['shopId'] = $shopId;
			$data['catSort'] = (int)I('catSort_'.$i);
			$data['isShow'] = (int)I('catShow_'.$i);
			$parentId = $m->add($data);
			if(false !== $parentId){
				//新增子类
				$catSecondNo = (int)I('catSecondNo_'.$i);
		        for($j=0;$j<$catSecondNo;$j++){
					$data = array();
					$data['catName'] = I('catName_'.$i."_".$j);
					if($data['catName']=='')continue;
					$data['shopId'] = $shopId;
					$data['parentId'] = $parentId;
					$data['catSort'] = (int)I('catSort_'.$i."_".$j);
					$data['isShow'] = (int)I('catShow_'.$i."_".$j);

					$m->add($data);
			    }
			}
		}
		$rd['status'] = 1;
		return $rd;
	}

    //保存分类
    public function addCats($parameter=array()){
        $m = M('shops_cats');
        $data = array();
        $data['shopId'] = $parameter['shopId'];
        $data['parentId'] = $parameter['parentId']?$parameter['parentId']:0;
        $data['isShow'] = isset($parameter['isShow'])?$parameter['isShow']:1;
        $data['isShowIndex'] = isset($parameter['isShowIndex'])?$parameter['isShowIndex']:1;
        $data['catName'] = $parameter['catName'];
        $data['catSort'] = $parameter['catSort'];
        $data['catFlag'] = 1;
        $data['icon'] = $parameter['icon'];
        if(isset($parameter['distributionLevel1Amount'])){
            $data['distributionLevel1Amount'] = (float)$parameter['distributionLevel1Amount']/100;
        }
        if(isset($parameter['distributionLevel2Amount'])){
            $data['distributionLevel2Amount'] = (float)$parameter['distributionLevel2Amount']/100;
        }
        if(isset($data['describe'])){
            $data['describe'] = $parameter['describe'];
        }
        $res = $m->add($data);
        return $res;
    }

	 /**
	  * 修改名称
	  */
	 public function editName(){
	 	$rd = array('status'=>-1);
	 	$id = (int)I("id",0);
		$data = array();
		$data["catName"] = I("catName");
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		if($this->checkEmpty($data,true)){
			$m = M('shops_cats');
			$rs = $m->where("catId=".$id." and shopId=".$shopId)->save($data);
			if(false !== $rs){
				$rd['status']= 1;
				S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
			}
		}
		return $rd;
	 }
	/**
	 * 修改排序号
	 */
	public function editSort(){
		$rd = array('status'=>-1);
		$id = (int)I("id",0);
		$data = array();
		$data["catSort"] = (int)I("catSort");
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
			$shopId = $this->MemberVeri()['shopId'];
		}
		$m = M('shops_cats');
		$rs = $m->where("catId=".$id." and shopId=".$shopId)->save($data);
		if(false !== $rs){
			$rd['status']= 1;
			S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
		}else{
			$rd['status'] = -1;
		}
		return $rd;
	}

    /**
     * 修改图标
     */
    public function editTypeImg(){
        $rd = array('status'=>-1);
        $id = (int)I("id",0);
        $data = array();
        $data['typeImg'] = I('typeImg');
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $m = M('shops_cats');
        $rs = $m->where("catId=".$id." and shopId=".$shopId)->save($data);
        if(false !== $rs){
            $rd['status']= 1;
            S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
        }
        return $rd;
    }

	 	 /**
	  * 修改图标
	  */
	 public function editIcon(){
	 	$rd = array('status'=>-1);
	 	$id = (int)I("id",0);
		$data = array();
		$data['icon'] = I('icon');
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		$m = M('shops_cats');
		$rs = $m->where("catId=".$id." and shopId=".$shopId)->save($data);
		if(false !== $rs){
			$rd['status']= 1;
			S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
		}
		return $rd;
	 }



	 /**
	  * 获取指定对象
	  */
     public function get($id){
	 	$m = M('shops_cats');
		return $m->where("catId=".(int)$id)->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($shopId){
        $m = M('shops_cats');
	 	$sql = "select * from __PREFIX__shops_cats where shopId=".$shopId." and catFlag=1 order by catSort asc";
		return $m->pageQuery($sql);
	 }
	 /**
	  * 获取树形分类
	  */
	 public function getCatAndChild($shopId){
	 	 //获取第一级分类
	 	 $m = M('shops_cats');
	 	 $rs1 = $m->where('shopId='.$shopId.' and catFlag=1 and parentId=0')->order('catSort asc')->select();
	 	 if(count($rs1)>0){
	 	 	$ids = array();
	 	 	foreach ($rs1 as $key => $v){
	 	 		$ids[] = $v['catId'];
	 	 	}
	 	 	$rs2 = $m->where('shopId='.$shopId.' and catFlag=1 and parentId in('.implode(',',$ids).')')->order('catSort asc,catId asc')->select();
	 	 	if(count($rs2)>0){
	 	 		$tmpArr = array();
	 	 		foreach ($rs2 as $key => $v){
	 	 			$tmpArr[$v['parentId']][] = $v;
	 	 		}
	 	 		foreach ($rs1 as $key => $v){
	 	 			$rs1[$key]['child'] = $tmpArr[$v['catId']];
	 	 			$rs1[$key]['childNum'] = count($tmpArr[$v['catId']]);
	 	 		}
	 	 	}
	 	 }
	 	 return $rs1;
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList($shopId,$parentId){
	     $m = M('shops_cats');
//		 return $m->where('shopId='.$shopId.' and catFlag=1 and parentId='.$parentId." and shopId=".$shopId)->order('catSort asc')->select();
          $result = $m->where('shopId='.$shopId.' and catFlag=1 and parentId='.$parentId." and shopId=".$shopId)->order('catSort asc')->select();
          foreach ($result as &$item){
              $item['distributionLevel1Amount'] = ((float)$item['distributionLevel1Amount']*100);
              $item['distributionLevel2Amount'] = ((float)$item['distributionLevel2Amount']*100);
          }
          unset($item);
          return $result;
	  }

	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('status'=>-1);
	 	$m = M('shops_cats');
	 	$id = (int)I('id');
	 	if($id==0)return $rd;
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		//把相关的商品下架了
		$sql = "update __PREFIX__goods set isSale=0 where shopId=".$shopId." and shopCatId1 = ".$id;
		$m->execute($sql);
		$sql = "update __PREFIX__goods set isSale=0 where shopId=".$shopId." and shopCatId2 = ".$id;
		$m->execute($sql);
		//删除商品分类
		$data = array();
		$data["catFlag"] = -1;
//	 	$rs = $m->where("(catId=".$id." or parentId=".$id.") and shopId=".$shopId)->save($data);
	 	$rs = $m->where("(catId=".$id." or parentId=".$id.")")->save($data);//临时修复,去除$shopId,因为上面是通过session获取的,有时候会出问题
	    if(false !== $rs){
			$rd['status']= 1;
			S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
		}
		return $rd;
	 }


	/**
	  * 获取店铺商品分类列表
	*/
    public function getShopCateList($shopId = 0){
		$shopId = ($shopId>0)?$shopId:(int)I("shopId");
		$data = S("WST_CACHE_SHOP_CAT_".$shopId);
		if(!$data){
			$m = M('shops_cats');
			$sql = "select catId,parentId,catName,shopId from __PREFIX__shops_cats where shopId=".$shopId." and parentId =0 and isShow=1 and catFlag=1 order by catSort asc";
			$data = $this->query($sql);
			if(count($data)>0){
				$ids = array();
				foreach ($data as $v){
					$ids[] = $v['catId'];
				}
				$sql = "select catId,parentId,catName,shopId from __PREFIX__shops_cats where shopId=".$shopId." and parentId in(".implode(',',$ids).") and isShow=1 and catFlag=1 order by catSort asc";
				$crs = $this->query($sql);
				$ids = array();
			    foreach ($crs as $v){
					$ids[$v['parentId']][] = $v;
				}
				foreach ($data as $key =>$v){
					if($ids[$v['catId']])$data[$key]['children'] = $ids[$v['catId']];
				}
			}
			S("WST_CACHE_SHOP_CAT_".$shopId,$data,86400);
	    }
		return $data;
	}

	/**
	 * 显示状态
	 */
	public function changeCatStatus($parameter=array()){
		$rd = array('status'=>-1);
		$id = (int)I("id",0);
		$isShow = (int)I("isShow",0);
		$parentId = (int)I("pid",0);
		$data = array();
		$data["isShow"] = (int)I("isShow");
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
        $shopId = $shopId?$shopId:$parameter['shopId'];
		if($this->checkEmpty($data,true)){
			$m = M('shops_cats');
			$m->where("catId=".$id." and shopId=".$shopId)->save($data);
			$m->where("parentId=".$id." and shopId=".$shopId)->save($data);
			if($parentId>0 && $isShow==1){
				$m->where("catId=".$parentId." and shopId=".$shopId)->save($data);
			}
			S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
            $rd = array('status'=>1);
        }
		return $rd;
	}

	/**
	 * 显示状态
	 */
	public function changeCatShowStatus($parameter=array()){
		$rd = array('status'=>-1);
		$id = (int)I("id",0);
		$isShowIndex = (int)I("isShowIndex",-1);
		$parentId = (int)I("pid",0);
		$data = array();
		$data["isShowIndex"] = (int)I("isShowIndex");
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
			$shopId = $this->MemberVeri()['shopId'];
		}
		$shopId = $shopId?$shopId:$parameter['shopId'];
		if($this->checkEmpty($data,true)){
			$m = M('shops_cats');
			$m->where("catId=".$id." and shopId=".$shopId)->save($data);
			$m->where("parentId=".$id." and shopId=".$shopId)->save($data);
			if($parentId>0 && $isShowIndex==1){
				$m->where("catId=".$parentId." and shopId=".$shopId)->save($data);
			}
			S("WST_CACHE_SHOP_CAT_".session('WST_USER.shopId'),null);
			$rd = array('status'=>1);
		}
		return $rd;
	}

	/**
	 * 店铺分类-修改店铺分类
     * @param array $params
     * -wst_shops_cats表字段
	 * */
    public function updateShopCat(array $params){
        $shop_cat_module = new ShopCatsModule();
        if(isset($params['distributionLevel1Amount'])){
            $params['distributionLevel1Amount'] = (float)$params['distributionLevel1Amount']/100;
        }
        if(isset($params['distributionLevel2Amount'])){
            $params['distributionLevel2Amount'] = (float)$params['distributionLevel2Amount']/100;
        }
        $result = $shop_cat_module->saveShopCat($params);
        if(empty($result)){
            return returnData(false,ExceptionCodeEnum::FAIL,'error',"修改失败");
        }
        return returnData(true);
    }
}
?>

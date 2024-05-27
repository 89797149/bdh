<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品属性类
 */
class AttributesModel extends BaseModel { 
    
     /**
      * 保存属性记录
      */
	 function edit(){
	 	$m = M('attributes'); 
	 	$rd = array('status'=>-1);
	 	$no = (int)I('no');
	 	$catId = (int)I('catId');
	 	$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
	 	//获取该类型下的价格属性
	 	$sql = "select attrId from __PREFIX__attributes where catId=".$catId." and isPriceAttr = 1 and attrFlag=1 and shopId=".$shopId;
	 	$priceRs = $this->query($sql);
	 	$priceAttrId = (int)$priceRs[0]['attrId'];
	 	$newPriceAttrId = 0;
	 	for($i=0;$i<=$no;$i++){
	 		$attrName = trim(I('attrName_'.$i));
	 		if($attrName=='')continue;
	 		$data = array();
	 		$data['shopId'] = $shopId;
	 		$data['catId'] = $catId;
	 		$id = (int)I('id_'.$i);
	 		if($id>0){
	 			$data['attrName'] = $attrName;
	 			$data['isPriceAttr'] = (int)I('isPriceAttr_'.$i);
	 			$data['attrType'] = (int)I('attrType_'.$i);
	 			if($data['attrType']==1 || $data['attrType']==2)$data['attrContent'] = str_replace('，',',',I('attrContent_'.$i));
	 			$data['attrSort'] = (int)I('attrSort_'.$i);
	 			$this->where('shopId='.$shopId.' and catId='.$catId.' and attrId='.$id)->save($data);
	 			if($data['isPriceAttr']==1)$newPriceAttrId = $id;
	 		}else{
	 			$data['attrName'] = $attrName;
	 			$data['isPriceAttr'] = (int)I('isPriceAttr_'.$i);
	 			$data['attrType'] = (int)I('attrType_'.$i);
	 			if($data['attrType']==1 || $data['attrType']==2)$data['attrContent'] = str_replace('，',',',I('attrContent_'.$i));
	 			$data['attrSort'] = (int)I('attrSort_'.$i);
	 			$data['attrFlag'] = 1;
			    $data['createTime'] = date('Y-m-d H:i:s');
	 			$rs = $this->add($data);
	 			if($data['isPriceAttr']==1)$newPriceAttrId = $id;
	 		}
	 	}
	 	//做价格属性的删除工作
	 	if(($priceAttrId>0 && $newPriceAttrId==0) || ($priceAttrId>0 && $newPriceAttrId>0 && $priceAttrId !=$newPriceAttrId )){
	 		//修改前一條记录状态
			$this->execute("update __PREFIX__attributes set isPriceAttr=0 where attrId=".$priceAttrId);
			$m = M('goods_attributes');
			//删除相关商品的属性
			$m->where("shopId=".$shopId." and attrId = ".$priceAttrId)->delete();
	 	}
	 	$rd['status']= 1;
	 	return $rd;
	 }
	 /**
	  * 获取指定对象
	  */
     public function get(){
     	$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		return $this->where("shopId=".$shopId." and attrId=".(int)I('id'))->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage(){
     	 $catId = (int)I('catId');
     	 $shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		 return $this->where('shopId='.$shopId.' and attrFlag=1 and catId='.$catId)->order('attrSort asc,attrId asc')->select();
	 }
	 
	 /**
	  * 下拉列表
	  */
     public function queryByList(){
     	 $catId = (int)I('catId');
     	 $shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		 return $this->where('shopId='.$shopId.' and attrFlag=1 and catId='.$catId)->order('attrSort asc,attrId asc')->select();
	 }
	 
     /**
	  * 下拉列表2
	  */
     public function queryByListForGoods(){
     	 $catId = (int)I('catId');
     	 $shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
		 $rs = $this->where('shopId='.$shopId.' and attrFlag=1 and catId='.$catId)->order('attrSort asc,attrId asc')->select();
         foreach ($rs as $key => $v){
		     //分解下拉和多选的选项
		     if($rs[$key]['attrType']==1 || $rs[$key]['attrType']==2){
				$rs[$key]['opts']['txt'] = explode(',',$rs[$key]['attrContent']);
		     }
		     
		}
		return $rs;
	 }
	 
	 /**
	  * 删除
	  */
	 public function del(){
	    $rd = array('status'=>-1);
	    $id = (int)I('id');
	    if($id==0)return $rd;
	    $shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
	    $m = M('goods_attributes');
		//删除相关商品的属性
		$m->where("shopId=".$shopId." and attrId=$id")->delete();
	    //删除属性
	    $rs = $this->execute("update __PREFIX__attributes set attrFlag=-1 where shopId=".$shopId." and attrId=".$id);
		if(false !== $rs){
		   $rd['status']= 1;
		}
		return $rd;
	 }

	 //以上是之前的老代码,不做处理
    /**
     * 属性列表
     */
    public function getAttributesList(){
        $shopId = (int)session('WST_USER.shopId');

        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $where = "";
        $goodsId = I('goodsId',0);
        if($goodsId > 0){
        	$where .= " AND goodsId='".$goodsId."'";
		}
        $m = M('attributes');
        $ret = array(
            'status' => -1,
            'msg' => '数据获取失败',
        );
        $list = $m->where("shopId='".$shopId."' AND attrFlag=1")->select();
        $ret['list'] = $list;
        if($ret['list']){
        	$parentId = [];
        	foreach ($list as $key=>&$val){
				$parentId[] = $val['attrId'];
				$val['children'] = [];
			}
			unset($val);
        	sort($parentId);
			$parentIdStr = 0;
			if(count($parentId) > 0){
                $parentIdStr = implode(',',$parentId);
			}
            $childrenList = M('goods_attributes')->where("attrId IN($parentIdStr)".$where)->select();
			if(count($childrenList) > 0){
                foreach ($list as $key=>$val){
                    foreach ($childrenList as $k=>$v){
                    	if($v['attrId'] == $val['attrId']){
                            $list[$key]['children'][] = $v;
						}
					}
                }

            }
            $ret['list'] = $list;
            $ret['status'] = 1;
            $ret['msg'] = '获取数据成功';
        }
        return $ret;
    }

    /**
     * 添加属性
     */
    public function attributesAdd($data){
        $mod = M('attributes');
        $returnData = array(
            'status' => -1,
            'msg' => '添加失败',
        );
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $data['shopId'] = $shopId;
        $info = $mod->where("shopId='".$shopId."' AND attrName='".$data['attrName']."' AND attrFlag=1")->find();
        if($info){
            $returnData['msg'] = "该商品属性已经存在,不能重复添加";
            return $returnData;
        }
        $res = $mod->add($data);
        if($res){
            $returnData['status'] = 1;
            $returnData['msg'] = '添加成功';
        }
        return $returnData;
    }


    /**
     * 编辑商品属性
     */
    public function attributesEdit($data){
        $mod = M('attributes');
        $res = $mod->where("attrId='".$data['attrId']."'")->save($data);
        $returnData = array(
            'status' => -1,
            'msg' => '编辑失败',
        );
        if($res !== false){
            $returnData['status'] = 1;
            $returnData['msg'] = '编辑成功';
        }
        return $returnData;
    }

    /**
     * 删除商品属性
     */
    public function attributesDel($data){
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $ids = trim($data['attrId'],',');
        if(empty($ids)){
            $ids = 0;
        }
        $mod = M('attributes');
        $update['attrFlag'] = -1;
        $res = $mod->where("attrId IN($ids) AND shopId='".$shopId."'")->save($update);
        $returnData = array(
            'status' => -1,
            'msg' => '删除失败',
        );
        if($res){
            $returnData['status'] = 1;
            $returnData['msg'] = '删除成功';
        }
        return $returnData;
    }

    /**
     * 获取复用商品的属性列表
     */
    public function getAttributesListCopy(){
        $where = "";
        $goodsId = I('goodsId',0);
        if($goodsId > 0){
            $where .= " AND goodsId='".$goodsId."'";
        }
        $m = M('attributes');
        $ret = array(
            'status' => -1,
            'msg' => '数据获取失败',
        );
        $shopId = M('goods')->where(['goodsId'=>$goodsId])->getField('shopId');
        $list = $m->where("shopId='".$shopId."' AND attrFlag=1")->select();
        $ret['list'] = $list;
        if($ret['list']){
            $parentId = [];
            foreach ($list as $key=>&$val){
                $parentId[] = $val['attrId'];
                $val['children'] = [];
            }
            unset($val);
            sort($parentId);
            $parentIdStr = 0;
            if(count($parentId) > 0){
                $parentIdStr = implode(',',$parentId);
            }
            $childrenList = M('goods_attributes')->where("attrId IN($parentIdStr)".$where)->select();
            if(count($childrenList) > 0){
                foreach ($list as $key=>$val){
                    foreach ($childrenList as $k=>$v){
                        if($v['attrId'] == $val['attrId']){
                            $list[$key]['children'][] = $v;
                        }
                    }
                }

            }
            $ret['list'] = $list;
            $ret['status'] = 1;
            $ret['msg'] = '获取数据成功';
        }
        return $ret;
    }
};
?>

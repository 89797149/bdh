<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品评价服务类
 */
class GoodsAppraisesModel extends BaseModel {
	 /**
	  * 商品评价分页列表
	  */
    public function queryByPage($shopId){
        $param = I();
        $m = M('goods_appraises');
        $shopCatId1 = (int)I('shopCatId1',0);
        $shopCatId2 = (int)I('shopCatId2',0);
        $params = [];
        $params['goodsInfo'] = null;
        $params['userInfo'] = null;
        $params['orderInfo'] = null;
        $params['compScore'] = null;
        $params['goodsScore'] = null;
        $params['serviceScore'] = null;
        $params['timeScore'] = null;
//        $goodsInfo = WSTAddslashes(I('goodsInfo'));//商品名称、商品ID
//        $userInfo = WSTAddslashes(I('userInfo'));//用户账号、用户名称、手机
//        $orderInfo = WSTAddslashes(I('orderInfo'));//订单号、订单ID
//        $compScore = I('compScore','compScore');//综合评分等级[好评：2  （ > 10  <= 15）  中评：1 （> 5  <= 10）  差评：0 （<=5）]
//        $goodsScore = I('goodsScore');//商品评分
//        $serviceScore = I('serviceScore');//服务评分
//        $timeScore = I('timeScore');//时效评分
        $pcurr = (int)I("pcurr",0);
        $page = (int)I("page",0);
        $pageSize = (int)I("pageSize",0);
        parm_filter($params,$param,false);
        $sql = "select gp.*,g.goodsName,g.goodsThums,u.loginName,u.userName,u.userPhoto,u.userPhone,o.orderNo from __PREFIX__goods_appraises gp left join ".__PREFIX__goods." g on g.goodsId=gp.goodsId left join ".__PREFIX__orders." o on gp.orderId=o.orderId left join ".__PREFIX__users." u on u.userId=gp.userId where gp.shopId='".$shopId."'";
        if(!empty($params['goodsInfo'])){
            $goodsInfo = $params['goodsInfo'];
            $sql .= " and (g.goodsName like '%".$goodsInfo."%' or g.goodsId = '$goodsInfo')";
        }
        if(!empty($params['userInfo'])){
            $userInfo = $params['userInfo'];
            $sql .= " and (u.loginName like '%".$userInfo."%' or u.userName like '%".$userInfo."%' or u.userPhone = '$userInfo')";
        }
        if(!empty($params['orderInfo'])){
            $orderInfo = $params['orderInfo'];
            $sql .= " and (o.orderNo like '%".$orderInfo."%' or o.orderId = '$orderInfo')";
        }
        //if($params['compScore'] != 'compScore'){//sbsbsbsbsbsbsbsbsbsbsbsbsbsbsbsbsb
        if(is_numeric($params['compScore'])){
            $compScore = $params['compScore'];
            $sql .= " and  gp.compScore = {$compScore}";
        }
        if(!empty($params['goodsScore'])){
            $goodsScore = $params['goodsScore'];
            $sql .= " and  gp.goodsScore = $goodsScore";
        }
        if(!empty($params['serviceScore'])){
            $serviceScore = $params['serviceScore'];
            $sql .= " and  gp.serviceScore = $serviceScore";
        }
        if(!empty($params['timeScore'])){
            $timeScore = $params['timeScore'];
            $sql .= " and  gp.timeScore = $timeScore";
        }
        $sql.=" order by id desc";
        $pages = $this->pageQuery($sql,$page,$pageSize);
        if(!empty($pages['root'])){
            foreach ($pages['root'] as $k=>$v){
                if(!empty($v['appraisesAnnex'])){
                    $pages['root'][$k]['appraisesAnnex'] = explode(',',$v['appraisesAnnex']);
                }else{
                    $pages['root'][$k]['appraisesAnnex'] = [];
                }
            }
        }
        return $pages;
    }

    /**
     * @param $shopId
     * @return array
     * 回复商品评价
     */
    public function updateAppraises($shopId){
        $rd = array('status'=>-1);
        $revert = WSTAddslashes(I('revert'));
        $id = (int)I("id");
        $where = "id = $id and shopId = $shopId";
        $res = M('goods_appraises')->where($where)->find();
        if(!empty($res['revert'])){
            $rd['msg'] = '该商品已评价';
            return $rd;
        }
        $data = M('goods_appraises')->where($where)->save(['revert' => $revert]);
        return $data;
    }

    /**
     * @param $shopId
     * @return mixed
     * 添加虚拟评论
     */
    public function addAppraises($shopId){
        $params = [];
        $params['shopId'] = $shopId;
        $params['goodsId'] = I('goodsId');
        $params['content'] = I('content');
        $params['goodsScore'] = I('goodsScore');
        $params['serviceScore'] = I('serviceScore');
        $params['timeScore'] = I('timeScore');
        $scro = (int)$params['goodsScore']+(int)$params['serviceScore']+(int)$params['timeScore'];
        switch ($scro)
        {
            case $scro <=5 :
                $params['compScore'] = 0;
                break;
            case $scro > 5 and  $scro <= 10:
                $params['compScore'] = 1;
                break;
            case $scro > 10 and  $scro <= 15:
                $params['compScore'] = 2;
                break;
            default:
                $params['compScore'] = null;
        }
        $data = M('goods_appraises')->add($params);
        return $data;
    }
	 
	/**
	 * 查询商品评价
	 */
	public function getGoodsAppraises(){		
		$goodsId = (int)I("goodsId");
		$sql = "SELECT ga.*, u.userName,u.loginName, od.createTime as ocreateTIme 
				FROM __PREFIX__goods_appraises ga , __PREFIX__orders od , __PREFIX__users u 
				WHERE ga.userId = u.userId AND ga.orderId = od.orderId AND ga.goodsId = $goodsId AND ga.isShow =1 order by id desc ";		
		$data = $this->pageQuery($sql);	
		return $data;
	}
	 
 	/**
	  * 获取指定商品评价
	  */
     public function getAppraise(){
	 	$m = M('goods_appraises');
	 	$id = (int)I('id');
	 	$sql = "select gp.*,g.goodsName,g.goodsThums from __PREFIX__goods_appraises gp ,__PREFIX__goods g where gp.goodsId=g.goodsId and gp.id=".$id;
		return $this->queryRow($sql);
	 }
	
	  
	 /**
	  * 删除商品评价
	  */
	 public function delAppraise(){
	 	$rd = array('status'=>-1);
	 	$m = M('goods_appraises');
		$rs = $m->delete((int)I('id'));
		if($rs){
		   $rd['status']= 1;
		   $rd['msg']= "删除成功";
		}
		return $rd;
	 }
	 /**
	  * 增加商品评论
	  */
	public function addGoodsAppraises($obj){
		$rd = array('status'=>-1);	
		$m = M('goods_appraises');
		$userId = $obj["userId"];
		$orderId = $obj["orderId"];
		$goodsId = $obj["goodsId"];
		$goodsAttrId = $obj["goodsAttrId"];
		
		$goodsScore = (int)I("goodsScore");
		$goodsScore = $goodsScore>5?5:$goodsScore;
		$goodsScore = $goodsScore<1?1:$goodsScore;
		$timeScore = (int)I("timeScore");
		$timeScore = $timeScore>5?5:$timeScore;
		$timeScore = $timeScore<1?1:$timeScore;
		$serviceScore = (int)I("serviceScore");
		$serviceScore = $serviceScore>5?5:$serviceScore;
		$serviceScore = $serviceScore<1?1:$serviceScore;
		//检查订单是否有效
		$sql="select isAppraises,orderFlag,shopId from __PREFIX__orders o where o.orderStatus = 4 and o.orderId=".$orderId." and o.userId=".$userId;
		$rs = $this->query($sql);
		if(empty($rs)){
			$rd['msg'] = '无效的订单!';
			return $rd;
		}
		if($rs[0]['isAppraises']==1 || $rs[0]['orderFlag']==-1){
			$rd['msg'] = '订单状态已改变，请刷新后再尝试!';
			return $rd;
		}
		$shopId = $rs[0]['shopId'];
		//检测商品是否已评价
		$sql = 'select * from __PREFIX__goods_appraises where goodsId='.$goodsId.' and goodsAttrId='.$goodsAttrId.' and orderId='.$orderId.' and shopId='.$shopId.' and userId='.$userId;
		$rs = $m->query($sql);
		if(!empty($rs)){
			$rd['msg'] = '该商品已评价!';
			return $rd;
		}
		
		//新增评价记录
		$data = array();
		
		$data["goodsId"] = $goodsId;
		$data["shopId"] = $shopId;
		$data["userId"] = $userId;
		$data["goodsScore"] = $goodsScore;
		$data["timeScore"] = $timeScore;
		$data["serviceScore"] = $serviceScore;
		$data["content"] = I("content");
		$data['goodsAttrId'] = $goodsAttrId;
		$data["isShow"] = 1;
		$data["createTime"] = date('Y-m-d H:i:s');
		$data["orderId"] = (int)I("orderId");
		$rs = $m->add($data);
		if(false !== $rs){
			$data["totalScore"] = $data["goodsScore"]+$data["timeScore"]+$data["serviceScore"];
			
			$sql ="SELECT * FROM __PREFIX__goods_scores WHERE goodsId=$goodsId";
			$goodsScores = $this->queryRow($sql);
			
			if($goodsScores["goodsId"]>0){
				$sql = "UPDATE __PREFIX__goods_scores set 
						totalUsers = totalUsers +1 , totalScore = totalScore +".$data["totalScore"]."
						,goodsUsers = goodsUsers +1 , goodsScore = goodsScore +".$data["goodsScore"]."
						,timeUsers = timeUsers +1 , timeScore = timeScore +".$data["timeScore"]."
						,serviceUsers = serviceUsers +1 , serviceScore = serviceScore +".$data["serviceScore"]."
						WHERE goodsId = ".$goodsId;		
				$this->execute($sql);		
			}else{
				$data = array();
				$gm = M('goods_scores');
	
				$data["goodsId"] = (int)I("goodsId");
				$data["shopId"] = $shopId;
				
				$data["goodsScore"] = $goodsScore;
				$data["goodsUsers"] = 1;
				
				$data["timeScore"] = $timeScore;
				$data["timeUsers"] = 1;
				
				$data["serviceScore"] = $serviceScore;
				$data["serviceUsers"] = 1;
				
				$data["totalScore"] = (int)$data["goodsScore"]+$data["timeScore"]+$data["serviceScore"];
				$data["totalUsers"] = 1;
				
				$rs = $gm->add($data);
			}
			//添加商城评分
			$sql = "UPDATE __PREFIX__shop_scores set 
						totalUsers = totalUsers +1 , totalScore = totalScore +".$data["totalScore"]."
						,goodsUsers = goodsUsers +1 , goodsScore = goodsScore +".$data["goodsScore"]."
						,timeUsers = timeUsers +1 , timeScore = timeScore +".$data["timeScore"]."
						,serviceUsers = serviceUsers +1 , serviceScore = serviceScore +".$data["serviceScore"]."
						WHERE shopId = ".$shopId;		
			$this->execute($sql);
			
			//检查下是不是订单的所有商品都评论完了
			$sql = "SELECT og.goodsId,ga.id as gaId
					FROM __PREFIX__order_goods og left join __PREFIX__goods_appraises ga on og.goodsAttrId=ga.goodsAttrId
					AND og.goodsId = ga.goodsId and ga.orderId=$orderId
					WHERE og.orderId = $orderId ";
			$goodslist = $this->query($sql);
			$gmark = 1;
			for($i=0;$i<count($goodslist);$i++){
				$goods = $goodslist[$i];
				if(!$goods["gaId"]) $gmark =0;
			}
			if($gmark==1){
				$sql="update __PREFIX__orders set isAppraises=1 where orderId=".$orderId;
				$this->execute($sql);
				//修改积分
				$appraisesScore = (int)$GLOBALS['CONFIG']['appraisesScore'];
				if((int)$GLOBALS['CONFIG']['isAppraisesScore']==1 && $appraisesScore>0){
	
					$sql = "UPDATE __PREFIX__users set userScore=userScore+".$appraisesScore.",userTotalScore=userTotalScore+".$appraisesScore." WHERE userId=".$userId;
					$rs = $this->execute($sql);
					
					$data = array();
					$m = M('user_score');
					$data["userId"] = $userId;
					$data["score"] = $appraisesScore;
					$data["dataSrc"] = 2;
					$data["dataId"] = $orderId;
					$data["dataRemarks"] = "订单评价获得";
					$data["scoreType"] = 1;
					$data["createTime"] = date('Y-m-d H:i:s');
					$m->add($data);
				}
			}
		}
		$rd['status'] = 1;
		return $rd;
	}
	/**
	 * 获取待评价订单
	 */
	public function getOrderAppraises($obj){
		$userId = $obj["userId"];
		$orderId = $obj["orderId"];
		$data = array();
		
		$sql = "SELECT o.*,sp.shopId,sp.shopName FROM __PREFIX__orders o,__PREFIX__shops sp WHERE o.orderStatus in (4,5) and o.shopId=sp.shopId AND o.orderId = $orderId ";		
		$order = $this->queryRow($sql);
		$data["order"] = $order;
		
		$sql = "SELECT g.*,og.goodsNums as ogoodsNums,og.goodsPrice as ogoodsPrice,og.goodsAttrName ,ga.id as gaId,og.goodsAttrId
				FROM __PREFIX__order_goods og 
				LEFT JOIN __PREFIX__goods_appraises ga ON og.goodsId = ga.goodsId AND og.goodsAttrId=ga.goodsAttrId and ga.orderId = $orderId,
				__PREFIX__goods g WHERE og.orderId = $orderId AND og.goodsId = g.goodsId";
		$goods = $this->query($sql);
		$data["goodsList"] = $goods;
		$sql = "SELECT * FROM __PREFIX__shop_appraises WHERE orderId = $orderId ";	
		$appraises = $this->query($sql);
		$data["shopAppraises"] = $appraises;
		
		return $data;
	}
	/**
	 * 获取商品评价列表
	 */
	public function getAppraisesList($obj){
		$userId = $obj["userId"];
		$pcurr = (int)I("pcurr",0);
		$data = array();
		
		$sql = "SELECT ga.*,o.orderNo,g.goodsName,g.goodsThums
				FROM __PREFIX__goods_appraises ga, __PREFIX__goods g, __PREFIX__orders o 
				WHERE ga.userId=$userId AND ga.goodsId = g.goodsId AND ga.orderId = o.orderId
				ORDER BY ga.createTime DESC";
		$pages = $this->pageQuery($sql,$pcurr);	
		return $pages;
	}
}
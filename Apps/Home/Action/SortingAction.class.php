<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 分拣控制器
 */
class SortingAction extends BaseAction {
	/**
	 * 分拣控制器
	 */
    public function index(){
		$this->isShopLogin();
		$this->assign('umark','sorting');
   		$this->display('default/shops/sorting_ElectronicScaleList');
    }

	//获取拣货员列表
	 public function getlist(){
		 $this->isShopLogin();
		
		
		$where['isdel'] = 1;
		
		$mod_data = M('sortingpersonnel')->where($where)->select();
		
		$this->ajaxReturn($mod_data);
    }
	
	//添加拣货员
	 public function add(){
		$this->isShopLogin();
		
		$userName = I('userName');
		$mobile = I('mobile');
		$shopid = session('WST_USER')['shopId'];
		
		
		if(empty($userName) or empty($mobile) or empty($shopid)){
			$ret['code'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		$data['userName'] = $userName;
		$data['mobile'] = $mobile;
		$data['shopid'] = $shopid;
		
		if(M('sortingpersonnel')->add($data)){
			$ret['code'] = 1;
			$ret['msg'] = '添加成功';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}else{
			$ret['code'] = -1;
			$ret['msg'] = '添加失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
		
    }
	
	//更新拣货员
	 public function update(){
		 $this->isShopLogin();
		
		$userName = I('userName');
		$mobile = I('mobile');
		$id = (int)I('id');
		if(empty($id)){
			$ret['code'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		
		$shopid = session('WST_USER')['shopId'];
		
		$where['shopid'] = $shopid;
		$where['id'] = $id;
		
		$save['userName'] = $userName;
		$save['mobile'] = $mobile;
		
		$mod_data = M('sortingpersonnel')->where($where)->save($save);
		if($mod_data){
			$ret['code'] = 1;
			$ret['msg'] = '更新成功';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}else{
			$ret['code'] = -1;
			$ret['msg'] = '更新失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
		
    }
	
	//删除拣货员
	 public function del(){
		 $this->isShopLogin();
		
		$id = (int)I('id');
		if(empty($id)){
			$ret['code'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		
		$shopid = session('WST_USER')['shopId'];
		
		$where['shopid'] = $shopid;
		$where['id'] = $id;
		
		$save['isdel'] = -1;
		$mod_data = M('sortingpersonnel')->where($where)->save($save);
		
		if($mod_data){
			$ret['code'] = 1;
			$ret['msg'] = '删除成功';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}else{
			$ret['code'] = -1;
			$ret['msg'] = '删除失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
    }
	
	//获取并分配分拣员  可用于查询当前订单的分拣员 每日重置
	 public function getSorting(){
		 $this->isShopLogin();
		
		$orderId = (int)I('orderId');
		if(empty($orderId)){
			$ret['code'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		//校验当前订单是否是当前门店 订单是否存在
		$mod_orders = M('orders');
		
		$shopid = session('WST_USER')['shopId'];
		
		if($mod_orders->where("orderId = '{$orderId}' and orderFlag = 1 and shopId = '{$shopid}' ")->count() <= 0){
			$ret['code'] = -1;
			$ret['msg'] = '订单不存在';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
		
		
		
		
		
		//查看当前订单是否已分配分拣员 如果已经分配直接返回分拣员数据
		$mod_sorting = M('sorting');
		$mod_sortingpersonnel = M('sortingpersonnel');
		
		$where['orderId'] = $orderId;

		
		$retdata = $mod_sorting->where($where)->find();
		if($retdata){
			$ret['code'] = 1;
			$ret['msg'] = '已分配';
			$ret['data'] = $mod_sortingpersonnel->where("id = '{$retdata['uid']}'")->find();
			$this->ajaxReturn($ret);
		}
		
		//分配分拣员  后期增加表 将分拣员每天分拣数量 单独存储 不用每次count计算 后期待优化
		
		

		unset($where);
		
		//获取分拣员
		$where['shopid'] = $shopid;
		$where['state'] = 1;
		$where['isdel'] = 1;
		
		$users = $mod_sortingpersonnel->where($where)->select();
		
		if(count($users)<=0){
			$ret['code'] = -1;
			$ret['msg'] = '没有拣货员 请先添加拣货员';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}

		//听说多表联合查询不一定比循环查询效率好 孰真孰假 1.让缓存效率更高 2.减少锁竞争3.易拆分 高性能 可扩展4.减少冗余查询
		for($i=0;$i<count($users);$i++){
			//获取分拣员今日的接单数
			unset($where);
			
			$where['uid'] = $users[$i]['id'];
			$where['addtime'] =  array('EGT',date('Y-m-d 00:00:00'));
			$users[$i]['count'] = $mod_sorting->where($where)->count();
		}
		
		
		
		$shopsDataSort = array();
		foreach ($users as $user) {
		  $shopsDataSort[] = $user['count'];
		}
		array_multisort($shopsDataSort,SORT_ASC,SORT_NUMERIC,$users);//从低到高排序
		
		
		//系统自动分配人员 写入数据库
		
		
		$add_data['uid'] = $users[0]['id'];
		$add_data['orderId'] = $orderId;
		$add_data['addtime'] = date('Y-m-d H:i:s');
		$add_data['shopid'] =  session('WST_USER')['shopId'];
		if($mod_sorting->add($add_data)){
			$ret['code'] = 1;
			$ret['msg'] = '分配成功';
			$ret['data'] = $users[0];
			$this->ajaxReturn($ret);
		}else{
			$ret['code'] = -1;
			$ret['msg'] = '分配失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		
		
		
		
    }
	
	//只获取分拣员
	 public function getOnlySorting(){
		 $this->isShopLogin();
		
		$orderId = (int)I('orderId');
		if(empty($orderId)){
			$ret['code'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			
		}
		
		//校验当前订单是否是当前门店订单是否存在
		$mod_orders = M('orders');
		
		if($mod_orders->where("orderId = '{$orderId}' and orderFlag = 1")->count() <= 0){
			$ret['code'] = -1;
			$ret['msg'] = '订单不存在';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
		
		
		
		
		
		//查看当前订单是否已分配分拣员 如果已经分配直接返回分拣员数据
		$mod_sorting = M('sorting');
		$mod_sortingpersonnel = M('sortingpersonnel');
		
		$where['orderId'] = $orderId;

		
		$retdata = $mod_sorting->where($where)->find();
		if($retdata){
			$ret['code'] = 1;
			$ret['msg'] = '已分配';
			$ret['data'] = $mod_sortingpersonnel->where("id = '{$retdata['uid']}'")->find();
			$this->ajaxReturn($ret);
		}
		
		$ret['code'] = -1;
		$ret['msg'] = '未分配';
		$ret['data'] = null;
		$this->ajaxReturn($ret);
		
		
		
		
    }
	
	//去结算页
	public function toOrders(){
		$this->isShopLogin();
		$this->assign('umark','toOrders');
		$this->display('default/shops/sorting_orders_ElectronicScaleList');

	
	}
	
	//获取未结算的分拣单 分页 参数 页码：page
	public function getNOorders(){
		
		 $pageDataNum = 50;//每页50条数据
		 
		
		
		 $this->isShopLogin();
		  $shopid = session('WST_USER')['shopId'];
		 $where['shopid'] = $shopid;
		 $where['settlement'] = -1;
		  $mod_sorting = M('sorting');
		 
		$count = $mod_sorting->where($where)->count();
		
		$where1['isdel'] = 1;
		$mod_sortingpersonnel_data = M('sortingpersonnel')->where($where1)->select();//获取未删除的分拣员
		$inStr = array();
		foreach($mod_sortingpersonnel_data as $data){
			array_push($inStr,$data['id']);
		}
		
		$where['uid']  = array('in',implode(",",$inStr));
		 
		
		$page = (int)I('page',1);
		
		
		$dataok =$mod_sorting
		->order("addtime desc")
		->limit(($page-1)*$pageDataNum,$pageDataNum)
		->where($where)
		->select();
		
		
		
		$ret['code'] = 1;
		$ret['msg'] = '未结算的分拣单';
		
		$pageCount = (int)ceil($count/$pageDataNum);
		$ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);
		$this->ajaxReturn($ret);
		 
		
		
	}
	
	
	
	//获取已结算的分拣单 分页
	public function getYesorders(){
		 $this->isShopLogin();
		 $pageDataNum = 50;//每页50条数据
		 $mod_sorting = M('sorting');
		
		 $shopid = session('WST_USER')['shopId'];
		 $where['shopid'] = $shopid;
		 $where['settlement'] = 1;
		
		$page = (int)I('page',1);
		$count = $mod_sorting->where($where)->count();
		$dataok = $mod_sorting
		->order("addtime desc")
		->limit(($page-1)*$pageDataNum,$pageDataNum)
		->where($where)
		->select();
		$ret['code'] = 1;
		$ret['msg'] = '已结算的分拣单';
		
		$pageCount = (int)ceil($count/$pageDataNum);

		$ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);
		
		$this->ajaxReturn($ret);
		
	}
	
	
	
	//根据分拣员id获取未结算订单
	public function sortingPgetnoOrder(){
		 $this->isShopLogin();
		 $shopid = session('WST_USER')['shopId'];
		 $userid = (int)I('userid');
		 if(empty($userid)){
			 $ret['code'] = -1;
			$ret['msg'] = '参数为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			 
		 }
		 
		 $where['uid'] = $userid;
		 $where['settlement'] = -1;
		 $where['shopid'] = $shopid;
		 

		 
		$ret['code'] = 1;
		$ret['msg'] = '未结算的分拣单';
		$ret['data'] = M('sorting')->where($where)->select();
		$this->ajaxReturn($ret);
		 
		 
	}
	
	//结算分拣 改变结算状态
	public function setorders(){
		$this->isShopLogin();
		$shopid = session('WST_USER')['shopId'];
		$userid = (int)I('userid');
		 if(empty($userid)){
			 $ret['code'] = -1;
			$ret['msg'] = '参数为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
			 
		 }
		 $where['uid'] = $userid;
		 $where['settlement'] = -1;
		 $where['shopid'] = $shopid;
		 
		 $save['settlement'] = 1;
		 if(M('sorting')->where($where)->save($save)){
			  $ret['code'] = 1;
			$ret['msg'] = '结算成功';
			$ret['data'] =null;
			$this->ajaxReturn($ret);
		 }
		 
			$ret['code'] = -1;
			$ret['msg'] = '结算失败';
			$ret['data'] =null;
			$this->ajaxReturn($ret);
		 
		
		 
		 
		
	}
	
	
}
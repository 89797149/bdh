<?php
namespace Merchantapi\Action;
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
		$shopInfo = $this->MemberVeri();
		$this->assign('umark','sorting');
   		$this->display('default/shops/sorting_ElectronicScaleList');
    }

	/*
	 * 获取分拣员列表
	 * 文档链接地址;https://www.yuque.com/anthony-6br1r/oq7p0p/glk5wu
	 * */
    public function getlist(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request['shopId'] = $shopInfo['shopId'];
        $request['page'] = I('page',1);
        $request['pageSize'] = I('pageSize',15);
        $res = $m->getlist($request);
        $this->ajaxReturn(returnData($res));
    }

	/*
	 * 添加拣货员
	 * @param string userName 名称
	 * @param string mobile 手机号
	 * @param string account 账号
	 * @param string passwor 密码
	 * @param int status 状态(1=>在线 ,-1=>不在线)
	 * @param jsonString locations 货位信息 例子:[{"firstLocations":"1","secondLocations":["2","4","5"]},{"firstLocations":"3","secondLocations":["8","9","10"]}] 原来的作废
	 * @param jsonString locations 货位信息 例子:["2","4","5"]
	 * */
	 public function add(){
		$shopInfo = $this->MemberVeri();
		$userName = I('userName');
		$mobile = I('mobile');
		$shopid = $shopInfo['shopId'];

		if(empty($userName) or empty($mobile) or empty($shopid)){
			$ret['status'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);

		}
		$data['userName'] = $userName;
		$data['mobile'] = $mobile;
		$data['shopid'] = $shopid;
		//原有基础后加
		$data['account'] = I('account');
		$data['password'] = md5(I('password'));
		$data['state'] = I('state',1);
		$existAccountWhere['isdel'] = 1;
		$existAccountWhere['account'] = I('account');
		$existAccountWhere['shopid'] = $shopid;
		$existAccount = M('sortingpersonnel')->where($existAccountWhere)->find();
		if($existAccount){
            $ret['status'] = -1;
            $ret['msg'] = '该账号已经存在,不能重复添加';
            $ret['data'] = null;
            $this->ajaxReturn($ret);
        }
        $personId = M('sortingpersonnel')->add($data);
		if($personId){
		    //添加对应的货位
            $m = D('Merchantapi/Sorting');
            $dataParam['shopId'] = $shopid;
            $dataParam['personId'] = $personId;
            $dataParam['locations'] = $_POST['locations'];
            $m->addSortLoacation($dataParam);

			$ret['status'] = 1;
			$ret['msg'] = '添加成功';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}else{
			$ret['status'] = -1;
			$ret['msg'] = '添加失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);
		}
    }

    /**
     * 更新拣货员
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/gesss6
     * */
    public function update(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        if(empty($requestParams['id'])){
            $this->ajaxReturn(returnData(false,-1,'error','参数有误'));
        }
        $requestParams['shopId'] = $shopInfo['shopId'];
        $m = D('Merchantapi/Sorting');
        $data = $m->update($requestParams);
        $this->ajaxReturn($data);
    }

    /**
     * 修改拣货员密码
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/utt80v
     * */
    public function updatePassword(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        if(empty($requestParams['id'])){
            $this->ajaxReturn(returnData(false,-1,'error','参数有误'));
        }
        $requestParams['shopId'] = $shopId;
        $m = D('Merchantapi/Sorting');
        $data = $m->updatePassword($requestParams);
        $this->ajaxReturn($data);
    }

    /*
     * 获取分拣员详情
     * */
    public function getInfo(){
        $shopInfo = $this->MemberVeri();
        $id = (int)I('id');
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '参数错误';
        $apiRet['apiState'] = 'error';
        if(empty($id)){
            $this->ajaxReturn($apiRet);
        }
        $where['id'] = $id;
        $info = M('sortingpersonnel')->where($where)->find();
        if($info){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $info;
        }else{
            $apiRet['apiInfo'] = '操作失败';
        }
        $this->ajaxReturn($apiRet);
    }

    /*
     * 获取分拣员对应的货位
     * @param int personId PS:分拣员id
     * */
    public function getPersonLocation(){
        $shopInfo = $this->MemberVeri();
        $id = (int)I('personId');
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '参数错误';
//        $apiRet['apiState'] = 'error';
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Sorting');
        $request['id'] = $id;
//        $res = $m->getPersonLocation($request);
        $data = $m->getPersonLocation($request);
        $this->ajaxReturn(returnData($data));
    }


	//删除拣货员
    public function del(){
        $shopInfo = $this->MemberVeri();
        $id = (int)I('id');
        if(empty($id)){
            $ret['status'] = -1;
            $ret['msg'] = '有参数不能为空';
            $ret['data'] = null;
            $this->ajaxReturn($ret);
        }
        $shopid = $shopInfo['shopId'];
        $where['shopid'] = $shopid;
        $where['id'] = $id;
        $save['isdel'] = -1;
        $mod_data = M('sortingpersonnel')->where($where)->save($save);
        if($mod_data){
            //删除对应的货位
            M('sorting_location_relation')->where(['personId'=>$id,'isDelete'=>0])->save(['isDelete'=>1]);
            $ret['status'] = 1;
            $ret['msg'] = '删除成功';
            $ret['data'] = null;
            $this->ajaxReturn($ret);
        }else{
            $ret['status'] = -1;
            $ret['msg'] = '删除失败';
            $ret['data'] = null;
            $this->ajaxReturn($ret);
        }
    }

	//获取并分配分拣员  可用于查询当前订单的分拣员 每日重置
	 public function getSorting(){
		 $shopInfo = $this->MemberVeri();
         $mc = M('shop_configs');
         //加载商店信息
         $shopcg = $mc->where('shopId='.$shopInfo['shopId'])->find();
         if($shopcg['isSorting'] != 1){
             $ret['status'] = -1;
             $ret['msg'] = '未启动订单分拣功能';
             $ret['data'] = null;
             $this->ajaxReturn($ret);
         }
		$orderId = (int)I('orderId');
		if(empty($orderId)){
			$ret['status'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);

		}

		//校验当前订单是否是当前门店 订单是否存在
		$mod_orders = M('orders');
		$shopid = $shopInfo['shopId'];

		if($mod_orders->where("orderId = '{$orderId}' and orderFlag = 1 and shopId = '{$shopid}' ")->count() <= 0){
			$ret['status'] = -1;
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
			$ret['status'] = 1;
			$ret['msg'] = '已分配';
			$ret['data'] = $mod_sortingpersonnel->where("id = '{$retdata['uid']}'")->find();
			$this->ajaxReturn($ret);
		}

		//分配分拣员  后期增加表 将分拣员每天分拣数量 单独存储 不用每次count计算 后期待优化-------------


/*
		unset($where);

		//获取分拣员
		$where['shopid'] = $shopid;
		$where['state'] = 1;
		$where['isdel'] = 1;

		$users = $mod_sortingpersonnel->where($where)->select();

		if(count($users)<=0){
			$ret['status'] = -1;
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
*/

		//系统自动分配人员 写入数据库
/*
        $goodsIds = M('order_goods')->where(array('orderId'=>$orderId))->getField('goodsId',true);
         if (empty($goodsIds)) {
             $ret['status'] = -1;
             $ret['msg'] = '订单没有商品';
             $ret['data'] = null;
             $this->ajaxReturn($ret);
         }
*/
//         $orderInfo = $mod_orders->where(array('orderId'=>$orderId,'shopId'=>$shopid))->find();
         /*if (empty($orderInfo['basketId'])) {
             $ret['status'] = -1;
             $ret['msg'] = '没有筐位，请先对订单进行受理';
             $ret['data'] = null;
             $this->ajaxReturn($ret);
         }*/
         //分拣方式,按整笔订单分
         /*$sortingData = getOrderSorting($shopid);
         if ($sortingData['status'] !== 1) {
             return $sortingData;
         }
         $add_data = array();
         foreach ($goodsIds as $v){
             //分拣方式,按商品分
             if ($shopcg['sortingType'] == 1) {
                 $len = count($sortingData['data'])-1;
                 $num = mt_rand(0,$len);
             } else {//按整笔订单分
                 $num = 0;
             }

             $add_data[] = array(
                 'uid'  =>  $sortingData['data'][$num]['id'],
                 'orderId'  =>  $orderId,
                 'goodsId'  =>  $v,
                 'addtime'  =>  date('Y-m-d H:i:s'),
                 'shopid'   =>  $shopInfo['shopId'],
                 'basketId' =>  $orderInfo['basketId']
             );
         }*/
         $result = autoDistributionSorting($shopid,$orderId);
		if($result['status'] == 1){
			$ret['status'] = 1;
			$ret['msg'] = '分配成功';
			$ret['data'] = $result['data'];
			$this->ajaxReturn($ret);
		}else{
			$ret['status'] = -1;
			$ret['msg'] = '分配失败';
			$ret['data'] = null;
			$this->ajaxReturn($ret);

		}





    }

	//只获取分拣员
	 public function getOnlySorting(){
		 $shopInfo = $this->MemberVeri();

		$orderId = (int)I('orderId');
		if(empty($orderId)){
			$ret['status'] = -1;
			$ret['msg'] = '有参数不能为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);

		}

		//校验当前订单是否是当前门店订单是否存在
		$mod_orders = M('orders');

		if($mod_orders->where("orderId = '{$orderId}' and orderFlag = 1")->count() <= 0){
			$ret['status'] = -1;
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
			$ret['status'] = 1;
			$ret['msg'] = '已分配';
			$ret['data'] = $mod_sortingpersonnel->where("id = '{$retdata['uid']}'")->find();
			$this->ajaxReturn($ret);
		}

		$ret['status'] = -1;
		$ret['msg'] = '未分配';
		$ret['data'] = null;
		$this->ajaxReturn($ret);




    }

	//去结算页
	public function toOrders(){
		$shopInfo = $this->MemberVeri();
		$this->assign('umark','toOrders');
		$this->display('default/shops/sorting_orders_ElectronicScaleList');


	}

	//获取未结算的分拣单 分页 参数 页码：page
    public function getNOorders(){

        $pageDataNum = 50;//每页50条数据



        $shopInfo = $this->MemberVeri();
        $shopid = $shopInfo['shopId'];
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



        $ret['status'] = 1;
        $ret['msg'] = '未结算的分拣单';

        $pageCount = (int)ceil($count/$pageDataNum);
        $ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);
        $this->ajaxReturn($ret);



    }



	//获取已结算的分拣单 分页
	public function getYesorders(){
		 $shopInfo = $this->MemberVeri();
		 $pageDataNum = 50;//每页50条数据
		 $mod_sorting = M('sorting');

		 $shopid = $shopInfo['shopId'];
		 $where['shopid'] = $shopid;
		 $where['settlement'] = 1;

		$page = (int)I('page',1);
		$count = $mod_sorting->where($where)->count();
		$dataok = $mod_sorting
		->order("addtime desc")
		->limit(($page-1)*$pageDataNum,$pageDataNum)
		->where($where)
		->select();
		$ret['status'] = 1;
		$ret['msg'] = '已结算的分拣单';

		$pageCount = (int)ceil($count/$pageDataNum);

		$ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);

		$this->ajaxReturn($ret);

	}



//	//根据分拣员id获取未结算订单 不用了
//	public function sortingPgetnoOrder(){
//		 $shopInfo = $this->MemberVeri();
//		 $shopid = $shopInfo['shopId'];
//		 $userid = (int)I('userid');
//		 if(empty($userid)){
//			 $ret['status'] = -1;
//			$ret['msg'] = '参数为空';
//			$ret['data'] = null;
//			$this->ajaxReturn($ret);
//
//		 }
//
//		 $where['uid'] = $userid;
//		 $where['settlement'] = -1;
//		 $where['shopid'] = $shopid;
//
//
//
//		$ret['status'] = 1;
//		$ret['msg'] = '未结算的分拣单';
//		$ret['data'] = M('sorting')->where($where)->select();
//		$this->ajaxReturn($ret);
//
//
//	}



	//结算分拣 改变结算状态
	public function setorders(){
		$shopInfo = $this->MemberVeri();
		$shopid = $shopInfo['shopId'];
		$userid = (int)I('userid');
		 if(empty($userid)){
			 $ret['status'] = -1;
			$ret['msg'] = '参数为空';
			$ret['data'] = null;
			$this->ajaxReturn($ret);

		 }
		 $where['uid'] = $userid;
		 $where['settlement'] = -1;
		 $where['shopid'] = $shopid;

		 $save['settlement'] = 1;
     $ok = M('sorting')->where($where)->save($save);
		 if($ok){
			  $ret['status'] = 1;
			$ret['msg'] = '结算成功';
			$ret['data'] =(int)$ok;
			$this->ajaxReturn($ret);
		 }

			$ret['status'] = -1;
			$ret['msg'] = '结算失败';
			$ret['data'] =null;
			$this->ajaxReturn($ret);





	}

    public function getOrdersList(){
        $pageDataNum = 50;//每页50条数据
        $shopInfo = $this->MemberVeri();
        $shopid = $shopInfo['shopId'];
        $where['shopid'] = $shopid;
        if((int)I('settlement')){
            $where['settlement'] = (int)I('settlement');
        }
        if((int)I('userid')){
            $where['uid'] = (int)I('userid');
        }
        $mod_sorting = M('sorting');

        $count = $mod_sorting->where($where)->count();
        //未结算订单处理
        if((int)I('settlement') == -1){
            $where1['isdel'] = 1;
            $mod_sortingpersonnel_data = M('sortingpersonnel')->where($where1)->select();//获取未删除的分拣员
            $inStr = array();
            foreach($mod_sortingpersonnel_data as $data){
                array_push($inStr,$data['id']);
            }

            $where['uid']  = array('in',implode(",",$inStr));
        }
        //END

        $p = (int)I('p',1);
        $page= $p-1;
        $page = $page>=0 ? $page : 0;
        $dataok =$mod_sorting
            ->order("addtime desc")
            ->limit($page*$pageDataNum,$pageDataNum)
            ->where($where)
            ->select();
        $ret['status'] = 1;
        $ret['msg'] = '获取成功';

        $pageCount = (int)ceil($count/$pageDataNum);
        $ret['data'] = array('list'=>$dataok,'pageCount'=>$pageCount == 0 ? 1 : $pageCount);
        $this->ajaxReturn($ret);
    }

    /*
	 * 获取结算列表
	 * @param string settlementNo PS:单号
	 * @param string orderNo PS:订单号
	 * @param string userName PS:买家姓名
	 * @param string userPhone PS:买家手机号
	 * @param string personName PS:分拣员姓名
	 * @param string personMobile PS:分拣员手机号
	 * @param string startDate PS:开始时间 例子: 2019-04-12 12:00:00
	 * @param string endDate PS:结束时间 例子: 2019-04-13 12:00:00
	 * @param int settlement PS:结算状态(1=>待结算,2=>已结算,10=>全部)
	 * */
    public function getSettlementList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request = I();
        $request['shopId'] = $shopInfo['shopId'];
        $request['settlement'] = (int)I('settlement',10);
        $res = $m->getSettlementList($request);
        $this->ajaxReturn($res);
    }

    /*
	 * 获取任务列表
	 * @param string settlementNo PS:单号
	 * @param string orderNo PS:订单号
	 * @param string userName PS:买家姓名
	 * @param string userPhone PS:买家手机号
	 * @param string personName PS:分拣员姓名
	 * @param string personMobile PS:分拣员手机号
	 * @param string startDate PS:开始时间 例子: 2019-04-12 12:00:00
	 * @param string endDate PS:结束时间 例子: 2019-04-13 12:00:00
	 * @param int status PS:分拣状态(0=>待分拣,1=>分拣中,2=>分拣完成,10=>全部)
	 * */
    public function getSortingList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request = I();
        $request['shopId'] = $shopInfo['shopId'];
        $request['status'] = I('status');
        $request['page'] = I('page',1);
        $request['pageSize'] = I('pageSize',15);
        $res = $m->getSortingList($request);
        $this->ajaxReturn($res);
    }

    /*
	 * 获取打包列表
	 * @param string settlementNo PS:单号
	 * @param string orderNo PS:订单号
	 * @param string userName PS:买家姓名
	 * @param string userPhone PS:买家手机号
	 * @param string personName PS:分拣员姓名
	 * @param string personMobile PS:分拣员手机号
	 * @param string startDate PS:开始时间 例子: 2019-04-12 12:00:00
	 * @param string endDate PS:结束时间 例子: 2019-04-13 12:00:00
	 * @param int packType PS:打包状态(0=>待打包,1=>打包中,2=>已打包,10=>全部)
	 * */
    public function getSortPackList(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request = I();
        $request['shopId'] = $shopInfo['shopId'];
        $request['packType'] = I('packType');
        $request['page'] = I('page',1);
        $request['pageSize'] = I('pageSize',15);
        $res = $m->getSortPackList($request);
        $this->ajaxReturn($res);
    }

    /*
	 * 商家更改结算单的状态
	 * @param int status PS:状态(1=>待结算,2=>已结算)
	 * @param int sortingWorkId PS:结算单id
	 * */
    public function editSettlementStatus(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request['shopId'] = $shopInfo['shopId'];
        $request['id'] = I('sortingWorkId');
        $request['settlement'] = (int)I('status',10);
        if(empty($request['id']) || empty($request['settlement'])){
            $ret['status'] = -1;
            $ret['msg'] = '参数为空';
            $this->ajaxReturn($ret);
        }
        $res = $m->editSettlementStatus($request);
        $this->ajaxReturn($res);
    }

    /*
	 * 获取结算单详情
	 * @param int sortingWorkId PS:结算单id
	 * */
    public function getSettlementDetail(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request['shopId'] = $shopInfo['shopId'];
        $request['sortingWorkId'] = I('sortingWorkId');
        if(empty($request['sortingWorkId'])){
            $ret['status'] = -1;
            $ret['msg'] = '参数为空';
            $this->ajaxReturn($ret);
        }
        $res = $m->getSettlementDetail($request);
        $this->ajaxReturn($res);
    }

    /*
	 * 统计任务列表各个状态下的数量
	 * @param int status PS:状态(0:待分拣|1:分拣中|2:已分拣|20:全部)
	 * @param string settlementNo PS:单号
	 * @param string orderNo PS:订单号
	 * @param string userName PS:买家姓名
	 * @param string userPhone PS:买家手机号
	 * @param string personName PS:分拣员姓名
	 * @param string personMobile PS:分拣员手机号
	 * @param string startDate PS:开始时间 例子: 2019-04-12 12:00:00
	 * @param string endDate PS:结束时间 例子: 2019-04-13 12:00:00
	 * */
    public function getSortingListCount(){
        $shopInfo = $this->MemberVeri();
        $m = D('Merchantapi/Sorting');
        $request = I();
        $request['shopId'] = $shopInfo['shopId'];
        $res = $m->getSortingListCount($request);
        $this->ajaxReturn($res);
    }


    /*
     * 获取分拣任务的操作日志
     * @param string token
     * @param int sortingId PS:分拣任务id
     * */
    public function getSortingActLog(){
        $this->MemberVeri();
        $sortingId = (int)I('sortingId');
        if(empty($sortingId)){
            $apiRet['apiCode'] = -1;
            $apiRet['apiInfo'] = '字段有误';
            $apiRet['apiState'] = 'error';
            $this->ajaxReturn($apiRet);
        }
        $m = D("Merchantapi/Sorting");
        $res = $m->getSortingActLog($sortingId);
        $this->ajaxReturn($res);
    }

    /*
	 * 拣货员详情
	 * 文档链接地址;https://www.yuque.com/anthony-6br1r/oq7p0p/samq5z
	 * */
    public function getSortingpersonnelInfo(){
        $shopId = $this->MemberVeri()['shopId'];
        $id = (int)I('id');
        if(empty($id)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D("Merchantapi/Sorting");
        $res = $m->getSortingpersonnelInfo($shopId,$id);
        $this->ajaxReturn(returnData($res));
    }
}

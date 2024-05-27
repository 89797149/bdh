<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 分拣员
 */

//PS:特别备注,该文件的逻辑大部分保留了原有的逻辑,追溯请看控制器Action
class SortingModel extends BaseModel {
    /**
     *添加分拣员对应的货位
     */
    public function addSortLoacation($data){
        $locations = json_decode(htmlspecialchars_decode($data['locations']),true);
        $tab = M('sorting_location_relation');
        if(!empty($locations)){
            $locationTab = M('location');
            $secondParam['lFlag'] = 1;
            $secondParam['lid'] = ["IN",$locations];
            $secondLocationList = $locationTab->where($secondParam)->order('sort desc')->select();
//            $firstId = []; //一级货位id
//            foreach ($secondLocationList as $val){
//                $firstId[] = $val['parentId'];
//            }
            $firstId = array_column($secondLocationList,'parentId');
            $firstId = array_unique($firstId);
            $firstParam['lFlag'] = 1;
            $firstParam['lid'] = ["IN",$firstId];
            $firstLocationList = $locationTab->where($firstParam)->order('sort desc')->select();
            foreach ($firstLocationList as $key=>$val){
                $firstLocationList[$key]['secondLocations'] = [];
                foreach ($secondLocationList as $v){
                    if($v['parentId'] == $val['lid']){
                        $firstLocationList[$key]['secondLocations'][] = $v;
                    }
                }
            }
            $locations = $firstLocationList;
            $firstLocation = [];
            $secondLocation = [];
            foreach ($locations as $key=>$value){
                $firstParam = [];
                //一级
                $firstParam['shopId'] = $data['shopId'];
                $firstParam['personId'] = $data['personId'];
                $firstParam['locationId'] = $value['lid'];
                $firstParam['addTime'] = date('Y-m-d H:i:s',time());
                $firstLocation[] = $firstParam;
                //二级
                $secondParam = [];
                foreach ($value['secondLocations'] as $val){
                    $secondParam['shopId'] = $data['shopId'];
                    $secondParam['personId'] = $data['personId'];
                    $secondParam['locationId'] = $val['lid'];
                    $secondParam['addTime'] = date('Y-m-d H:i:s',time());
                    $secondLocation[] = $secondParam;
                }
            }
            if(!empty($firstLocation) && !empty($secondLocation)){
                $tab->where(['shopId'=>$data['shopId'],'personId'=>$data['personId']])->delete();
                $mergeArr = array_merge($firstLocation,$secondLocation);
                $tab->addAll($mergeArr);
            }
        }else{
            $tab->where(['shopId'=>$data['shopId'],'personId'=>$data['personId']])->save(['isDelete'=>1]);
        }
    }

    /**
     * 获取分拣员对应的货位
     * @param $param
     */
    public function getPersonLocation($param){
//        $apiRet['apiCode'] = -1;
//        $apiRet['apiInfo'] = '获取失败';
//        $apiRet['apiState'] = 'error';
        //获取所有相关货位数据
        $relationTab = M('sorting_location_relation');
        $relationWhere['personId'] = $param['id'];
        $relationWhere['isDelete'] = 0;
        $relationList = $relationTab->where($relationWhere)->group('locationId')->order('id asc')->select();
        if(empty($relationList)){
            return [];
        }
        foreach ($relationList as $key=>$value){
            $relationId[] = $value['locationId'];
        }
        sort($relationId);
        //获取一级
        $locationTab = M('location');
        $firstWhere['lFlag'] = 1;
        $firstWhere['parentId'] = 0;
        $firstWhere['lid'] = array('IN',$relationId);
        $firstLocation = $locationTab->where($firstWhere)->select();
        foreach ($relationList as $key=>$value){
            foreach ($firstLocation as $k=>$val){
                //$firstLocation[$k]['secondLocation'] = [];
                if($value['locationId'] == $val['lid']){
                    unset($relationList[$key]);
                }
            }
        }
        $relationList = array_values($relationList);
        foreach ($relationList as $key=>$val){
            $locationInfo = $locationTab->where(['lid'=>$val['locationId'],'lFlag'=>1])->find();
            $relationList[$key]['parentId'] = $locationInfo['parentId'];
            $relationList[$key]['name'] = $locationInfo['name'];
            $relationList[$key]['sort'] = $locationInfo['sort'];
        }

        foreach ($firstLocation as $key=>$value){
            foreach ($relationList as $val){
                if($value['lid'] == $val['parentId']){
                    $firstLocation[$key]['secondLocation'][] = $val;
                }
            }
        }
        return (array)$firstLocation;
    }

    /**
     * 获取分拣员列表
     * @param array $request
     */
    public function getlist($request){
        $shopId = $request['shopId'];
        $page = $request['page'];
        $pageSize = $request['pageSize'];
        $where = " where shopid={$shopId} and isdel=1 ";
        $sql = "select id,userName,shopid,mobile,account,password,state,isdel from ".__PREFIX__.sortingpersonnel.$where." order by id desc ";
        $data  = $this->pageQuery($sql,$page,$pageSize);
        $root = $data['root'];
//        if(empty($root)){
//            return [];
//        }
        $relationTab = M('sorting_location_relation');
        foreach ($root as $key=>$value){
            $root[$key]['locations'] = '';
            $where = [];
            $where['personId'] = $value['id'];
            $where['isDelete'] = 0;
            $personSortingLocation = $relationTab->where($where)->select();
            if($personSortingLocation){
                $locationIdArr = array_column($personSortingLocation,'locationId');
                $locationIdArr = array_unique($locationIdArr);
                $root[$key]['locations'] = implode(',',$locationIdArr);
            }
        }
        $data['root'] = $root;
        return $data;
    }

    /*
	 * 获取结算单
	 * @param string settlementNo PS:单号
	 * @param string orderNo PS:订单号
	 * @param string userName PS:买家姓名
	 * @param string userPhone PS:买家手机号
	 * @param string personName PS:分拣员姓名
	 * @param string personMobile PS:分拣员手机号
	 * @param string startDate PS:开始时间 例子: 2019-04-12 12:00:00
	 * @param string endDate PS:结束时间 例子: 2019-04-13 12:00:00
	 * @param int settlement PS:状态(1=>待结算,2=>已结算,10=>全部) //分拣状态非2,不会出现在此列表
	 * */
    public function getSettlementList($request){
        //where
        $field = " s.*,sp.userName as personName,sp.mobile as personMobile,o.orderNo,o.orderStatus,o.totalMoney,o.payType,o.isSelf,o.isPay,o.deliverType,o.createTime,o.needPay,o.defPreSale,o.PreSalePay,o.PreSalePayPercen,o.deliverMoney,o.isAppraises,u.userName as buyer_userName,u.userPhone as buyer_userPhone,u.userPhoto as buyer_userPhoto ";
        $where = " where s.shopid='".$request['shopId']."' and s.sortingFlag=1 and sp.isdel=1 and s.status=2 ";
        if(!empty($request['settlementNo'])){
            $where .= " and s.settlementNo='".$request['settlementNo']."' ";
        }
        if(!empty($request['orderNo'])){
            $where .= " and o.orderNo='".$request['orderNo']."' ";
        }
        if(!empty($request['userName'])){
            $where .= " and u.userName like'%".$request['userName']."%' ";
        }
        if(!empty($request['userPhone'])){
            $where .= " and u.userPhone like'%".$request['userPhone']."%' ";
        }
        if(!empty($request['personName'])){
            $where .= " and sp.userName like'%".$request['personName']."%' ";
        }
        if(!empty($request['personMobile'])){
            $where .= " and sp.mobile like'%".$request['personMobile']."%' ";
        }
        if(!empty($request['startDate']) && !empty($request['endDate'])){
            $where .= " and s.addtime between '".$request['startDate']."' and '".$request['endDate']."' ";
        }
        $settlement = "1,2";
        if($request['status'] != 10){
            $settlement = $request['status'];
        }
        $where .= " and s.settlement IN ($settlement) ";

        $sql = "select ".$field." from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." order by s.addtime desc ";
        $data = $this->pageQuery($sql);
        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        $list = $data['root'];
        foreach ($list as $key=>$val){
            //shop
            $shopInfo = $shopTab->where(['shopId'=>$val['shopId']])->find();
            $list[$key]['shopName'] = $shopInfo['shopName'];
            $list[$key]['shopImg'] = $shopInfo['shopImg'];
            //goods
            $swhere['dataFlag'] = 1;
            $swhere['sortingId'] = $val['id'];
            $list[$key]['goods'] = $sortingGoodsTab->where($swhere)->select();
            //框位
            $list[$key]['basketInfo'] = $this->getBasketInfo($val['basketId']); //获取框位信息
        }
        $goodsTab = M('goods');
        foreach ($list as $key=>$val){
            $list[$key]['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量的总和
            $list[$key]['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量总和
            foreach ($val['goods'] as $k=>$v){
                $list[$key]['totalSoringGoods'] += $v['goodsNum'];
                $list[$key]['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
                $goodsInfo = $goodsTab->where(['goodsId'=>$v['goodsId']])->field("recommDesc,goodsUnit,goodsSpec,goodsDesc,saleCount,goodsImg,goodsThums")->find();
                $list[$key]['goods'][$k]['recommDesc'] = $goodsInfo['recommDesc'];
                $list[$key]['goods'][$k]['goodsUnit'] = $goodsInfo['goodsUnit'];
                $list[$key]['goods'][$k]['goodsSpec'] = $goodsInfo['goodsSpec'];
                $list[$key]['goods'][$k]['goodsDesc'] = $goodsInfo['goodsDesc'];
                $list[$key]['goods'][$k]['saleCount'] = $goodsInfo['saleCount'];
                $list[$key]['goods'][$k]['goodsImg'] = $goodsInfo['goodsImg'];
                $list[$key]['goods'][$k]['goodsThums'] = $goodsInfo['goodsThums'];
                $orderGoodsInfo = M('order_goods')->where(['orderId'=>$val['orderId'],'goodsId'=>$v['goodsId']])->find();
                $list[$key]['goods'][$k]['goodsPrice'] = $orderGoodsInfo['goodsPrice'];
                //$list[$key]['goods'][$k]['location'] = []; //商品对应的货位信息
                //location 商品对应的货位信息,目前只做两级
                $list[$key]['goods'][$k]['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
            }
        }
        $data['root'] = $list;
        if($data){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $data;
        }
        return $apiRet;
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
	 * @param int status PS:分拣状态(0=>待分拣,1=>分拣中,2=>待入框,3=>分拣完成,10=>全部) //结算状态大于0不会出现在此列表
	 * */
    public function getSortingList($request){
        //where
        $field = " s.*,sp.userName as personName,sp.mobile as personMobile,o.orderNo,o.orderStatus,o.totalMoney,o.payType,o.isSelf,o.isPay,o.deliverType,o.createTime,o.needPay,o.defPreSale,o.PreSalePay,o.PreSalePayPercen,o.deliverMoney,o.isAppraises,u.userName as buyer_userName,u.userPhone as buyer_userPhone,u.userPhoto as buyer_userPhoto ";
        $where = " where s.shopid='".$request['shopId']."' and s.sortingFlag=1 and sp.isdel=1 and s.settlement<1 and o.orderFlag=1 ";
        if(!empty($request['settlementNo'])){
            $where .= " and s.settlementNo='".$request['settlementNo']."' ";
        }
        if(!empty($request['orderNo'])){
            $where .= " and o.orderNo='".$request['orderNo']."' ";
        }
        if(!empty($request['userName'])){
            $where .= " and u.userName like'%".$request['userName']."%' ";
        }
        if(!empty($request['userPhone'])){
            $where .= " and u.userPhone like'%".$request['userPhone']."%' ";
        }
        if(!empty($request['personName'])){
            $where .= " and sp.userName like'%".$request['personName']."%' ";
        }
        if(!empty($request['personMobile'])){
            $where .= " and sp.mobile like'%".$request['personMobile']."%' ";
        }
        if(!empty($request['startDate']) && !empty($request['endDate'])){
            $where .= " and s.addtime between '".$request['startDate']."' and '".$request['endDate']."' ";
        }
        if($request['status'] != 10){
            $where .= " and s.status='".$request['status']."' ";
        }

        $sql = "select ".$field." from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." order by s.addtime desc ";
        $data = $this->pageQuery($sql,$request['page'],$request['pageSize']);
        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        $list = $data['root'];
        foreach ($list as $key=>$val){
            //shop
            $shopInfo = $shopTab->where(['shopId'=>$val['shopId']])->find();
            $list[$key]['shopName'] = $shopInfo['shopName'];
            $list[$key]['shopImg'] = $shopInfo['shopImg'];
            //goods
            $swhere['dataFlag'] = 1;
            $swhere['sortingId'] = $val['id'];
            $list[$key]['goods'] = $sortingGoodsTab->where($swhere)->select();
            //框位
            $list[$key]['basketInfo'] = $this->getBasketInfo($val['basketId']); //获取框位信息
        }
        $goodsTab = M('goods');
        foreach ($list as $key=>$val){
            $list[$key]['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量的总和
            $list[$key]['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量总和
            foreach ($val['goods'] as $k=>$v){
                $list[$key]['totalSoringGoods'] += $v['goodsNum'];
                $list[$key]['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
                $goodsInfo = $goodsTab->where(['goodsId'=>$v['goodsId']])->field("recommDesc,goodsUnit,goodsSpec,goodsDesc,saleCount,goodsImg,goodsThums")->find();
                $list[$key]['goods'][$k]['recommDesc'] = $goodsInfo['recommDesc'];
                $list[$key]['goods'][$k]['goodsUnit'] = $goodsInfo['goodsUnit'];
                $list[$key]['goods'][$k]['goodsSpec'] = $goodsInfo['goodsSpec'];
                $list[$key]['goods'][$k]['goodsDesc'] = $goodsInfo['goodsDesc'];
                $list[$key]['goods'][$k]['saleCount'] = $goodsInfo['saleCount'];
                $list[$key]['goods'][$k]['goodsImg'] = $goodsInfo['goodsImg'];
                $list[$key]['goods'][$k]['goodsThums'] = $goodsInfo['goodsThums'];
                $orderGoodsInfo = M('order_goods')->where(['orderId'=>$val['orderId'],'goodsId'=>$v['goodsId']])->find();
                $list[$key]['goods'][$k]['goodsPrice'] = $orderGoodsInfo['goodsPrice'];
                //$list[$key]['goods'][$k]['location'] = []; //商品对应的货位信息
                //location 商品对应的货位信息,目前只做两级
                $list[$key]['goods'][$k]['location'] = getGoodsLocation($v['goodsId']); //商品对应的货位信息
            }
        }
        $data['root'] = $list;
        if($data){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $data;
        }
        return $apiRet;
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
    public function getSortPackList($request){
        $page = $request['page'];
        $pageSize = $request['pageSize'];
        //where
        $field = " sp.userName as personName,sp.mobile as personMobile, ";//分拣员信息
        $field .= " o.orderId,o.orderNo,o.orderStatus,o.totalMoney,o.payType,o.isSelf,o.isPay,o.deliverType,o.createTime,o.needPay,o.defPreSale,o.PreSalePay,o.PreSalePayPercen,o.deliverMoney,o.isAppraises, ";//订单信息
        $field .= " u.userName as buyer_userName,u.userPhone as buyer_userPhone,u.userPhoto as buyer_userPhoto, ";//客户信息
        $field .= " wsp.packType,wsp.createTime,wsp.updateTime ";//打包订单信息
        $where = [];
        $where['s.shopid'] = $request['shopId'];
        $where['s.sortingFlag'] = 1;
        $where['s.status'] = 3;

        $personId = "s.personId";//待领取的分拣员和领取后的分拣员是不一样的
        if($request['packType'] == 0){//0=>待打包
            $where['s.isPack'] = -1;//是否打包[-1:未进入|1:已进入]
        } elseif($request['packType'] != 10 && !empty($request['packType'])){
            $where['wsp.packType'] = (int)$request['packType'];//[1:待打包|2:已打包]
            $personId = "wsp.personId";//待领取的分拣员和领取后的分拣员是不一样的
        }

        if(!empty($request['orderNo'])){
            $where['o.orderNo'] = $request['orderNo'];
        }
        if(!empty($request['userName'])){
            $where['u.userName'] = ['like',"%".$request['userName']."%"];
        }
        if(!empty($request['userPhone'])){
            $where['u.userPhone'] = ['like',"%".$request['userPhone']."%"];
        }
        if(!empty($request['personName'])){
            $where['sp.userName'] = ['like',"%".$request['personName']."%"];
        }
        if(!empty($request['personMobile'])){
            $where['sp.mobile'] = ['like',"%".$request['personMobile']."%"];
        }
        if(!empty($request['startDate']) && !empty($request['endDate'])){
            $where['wsp.createTime'] = ['between',[$request['startDate'],$request['endDate']]];
        }

        $sortModel = M('sorting s');
        $data = $sortModel
            ->join('left join wst_sorting_packaging wsp on wsp.orderId = s.orderId')
            ->join('left join wst_orders o on o.orderId = s.orderId')
            ->join('left join wst_users u on o.userId = u.userId ')
            ->join("left join wst_sortingpersonnel sp on sp.id = {$personId} and sp.isdel = 1")
            ->where($where)
            ->field($field)
            ->order('wsp.updateTime desc, s.addtime desc')
            ->group('o.orderId')
            ->select();

        $rest = [];
        if (!empty($data)) {
            $sortingApiModel = D('Merchantapi/SortingApi');
            foreach ($data as $k => $v) {
                $sortOrderVerify = $sortingApiModel->sortOrderVerify($v['orderId']);
                if($sortOrderVerify['apiCode'] != 0){//如果订单下存在未完成的分拣任务,就跳出当前
                    continue;
                }
                $goodsCount = $sortingApiModel->getOrderBasketGoods($v['orderId'],0);//获取入框商品数量
                if ($goodsCount == 0) {//如果入框商品数量为0,则跳出当前订单
                    continue;
                }
                $packGoodsCount = M('sorting_pack_goods')->where(['orderId' => $v['orderId']])->count();
                $v['goodsCount'] = (int)$goodsCount;//打包商品数量
                $v['packGoodsCount'] = (int)$packGoodsCount;//已打包商品数量
                $v['goodsList'] = $sortingApiModel->getGoodsList($v['orderId']); //获取商品信息
                foreach ($v['goodsList'] as $key=>$value){
                    $v['goodsList'][$key]['orderNo'] = $v['orderNo'];
                }
                $v['basketInfo'] = $sortingApiModel->getOrderBasket($v['orderId']); //获取框位信息
                $v['packType'] = (int)$data[$k]['packType'];
                $rest[] = $v;
            }
        }

        $count = count($rest);
        $pageData = array_slice($rest, ($page - 1) * $pageSize, $pageSize);
        $res = [];
        $res['root'] = $pageData;
        $res['totalPage'] = ceil($count / $pageSize);//总页数
        $res['currentPage'] = $page;//当前页码
        $res['total'] = (int)$count;//总数量
        $res['pageSize'] = $pageSize;//页码条数

        if($res){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res;
        }
        return $apiRet;
    }
    /*
     *获取框位
     * @param int $basketId PS:框位id
     * */
    public function getBasketInfo($basketId){
        $basketInfo = [];
        if($basketId > 0){
            $basketInfo = M('basket')->where(['bid'=>$basketId])->find();
            $partitionTab = M('partition');
            $basketInfo['firstPartitionName'] = $partitionTab->where(['id'=>$basketInfo['pid']])->getField('name');
            $basketInfo['secondPartitionName'] = $partitionTab->where(['id'=>$basketInfo['partitionId']])->getField('name');
        }
        return $basketInfo;
    }

    /**
     *更改结算单的状态
     */
    public function editSettlementStatus($request){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $tab = M('sorting');
        //where
        $where['id'] = $request['id'];
        $where['shopid'] = $request['shopId'];
        //edit
        $request['settlement'] = 2;
        $edit['settlement'] = $request['settlement'];
        $edit['updatetime'] = date('Y-m-d H-i-s',time());
        $rs = $tab->where($where)->save($edit);
        if($rs !== false){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }
        return $apiRet;
    }

    /**
     * 更新拣货员信息
     * @param array $requestParams<p>
     * int shopId
     * int id 拣货员id
     * string userName 拣货员姓名
     * string mobile 拣货员手机号
     * string account 拣货员登陆账号
     * string password 拣货员登陆密码
     * string locations 拣货员货位
     * string state 在线状态(1：在线 -1：不在线)
     * </p>
     * */
    public function update(array $requestParams){
        $tab = M('sortingpersonnel');
        $shopId = $requestParams['shopId'];
        $where['shopid'] = $shopId;
        $where['id'] = $requestParams['id'];
        $where['isdel'] = 1;
        $info = $tab->where($where)->find();
        if(empty($info)){
            return returnData(false,-1,'error','分拣员信息有误');
        }
        $save = [];
        $save['userName'] = null;
        $save['mobile'] = null;
        $save['account'] = null;
        $save['state'] = null;
        parm_filter($save,$requestParams);
        if(!empty($requestParams['password'])){
            $save['password'] = md5($requestParams['password']);
        }
        if(!empty($save['account'])){
            $where = [];
            $where['isdel'] = 1;
            $where['account'] = $save['account'];
            $where['shopid'] = $shopId;
            $existAccount = $tab->where($where)->find();
            if($existAccount && $existAccount['id'] != $requestParams['id']){
                return returnData(false,-1,'error','该账号已存在');
            }
        }
        $res = $tab->where(array('id'=>$info['id']))->save($save);
        if($res !== false){
            //更新对应的货位
            $m = D('Merchantapi/Sorting');
            $dataParam['shopId'] = $shopId;
            $dataParam['personId'] = $requestParams['id'];
            $dataParam['locations'] = $requestParams['locations'];
            $m->addSortLoacation($dataParam);
            return returnData(true);
        }else{
            return returnData(false,-1,'error','修改失败');
        }
    }

    /**
     * 修改分拣员密码
     * @param array $params<p>
     * int $shopId
     * int $id 分拣员id
     * int $id 分拣员id
     * </p>
     * */
    public function updatePassword(array $params){
        $tab = M('sortingpersonnel');
        $save = [];
        if(!empty($params['password'])){
            $save['password'] = md5($params['password']);
        }
        $where['isdel'] = 1;
        $where['shopid'] = $params['shopId'];
        $where['id'] = $params['id'];
        $info = $tab->where($where)->find();
        if(empty($info)){
            return returnData(false,-1,'error','分拣员信息有误');
        }
        if(empty($save)){
            return returnData(true);
        }
        $res = $tab->where($where)->save($save);
        if($res === false){
            return returnData(false,-1,'error','修改失败');
        }
        return returnData(true);
    }

    /**
     *获取结算单的详情
     */
    public function getSettlementDetail($request){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';
        $field = [
            's.*',
            'ws.userName as personName',
            'ws.mobile as personMobile',
            'o.orderNo',
            'o.basketId',
            'o.shopId',
            'o.orderStatus',
            'o.totalMoney',
            'o.payType',
            'o.isSelf',
            'o.isPay',
            'o.deliverType',
            'o.createTime',
            'o.needPay',
            'o.defPreSale',
            'o.PreSalePay',
            'o.PreSalePayPercen',
            'o.deliverMoney',
            'o.isAppraises',
            'u.userName as buyer_userName',
            'u.userPhone as buyer_userPhone',
            'u.userPhoto as buyer_userPhoto',
        ];
        $where['s.id'] = $request['sortingWorkId'];
        $where['s.sortingFlag'] = 1;
        $info = M('sorting s')
            ->join("left join wst_orders o on o.orderId=s.orderId")
            ->join("left join wst_users u on o.userId=u.userId")
            ->join("left join wst_sortingpersonnel ws on ws.id=s.personId")
            ->where($where)
            ->field($field)
            ->find();
        $sortingGoodsTab = M('sorting_goods_relation');
        $shopTab = M('shops');
        //shop
        $shopInfo = $shopTab->where(['shopId'=>$info['shopId']])->find();
        $info['shopName'] = $shopInfo['shopName'];
        $info['shopImg'] = $shopInfo['shopImg'];
        //goods
        $swhere['dataFlag'] = 1;
        $swhere['sortingId'] = $info['id'];
        $info['goods'] = $sortingGoodsTab->where($swhere)->select();
        $goodsTab = M('goods');
        $info['totalSoringGoods'] = 0;//该分拣任务下的所有商品数量的总和
        $info['totalSortingGoodsNum'] = 0;//该分拣任务下已完成分拣的商品数量总和
        foreach ($info['goods'] as $k=>&$v){
            $orderGoodsInfo = M('order_goods og')
                ->join("left join wst_orders o on o.orderId=og.orderId")
                ->where("o.orderId='".$info['orderId']."'")
                ->field('o.orderId,o.orderNo')
                ->find();
            $v['orderNo'] = $orderGoodsInfo['orderNo'];
            $info['totalSoringGoods'] += $v['goodsNum'];
            $info['totalSortingGoodsNum'] += $v['sortingGoodsNum'];
            $goodsInfo = $goodsTab->where(['goodsId'=>$v['goodsId']])->find();
            $v['goodsCatId1Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId1']);
            $v['goodsCatId2Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId2']);
            $v['goodsCatId3Name'] = $this->getGoodsCatName($goodsInfo['goodsCatId3']);
            $v['shopCatId1Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId1']);
            $v['shopCatId2Name'] = $this->getGoodsShopCatName($goodsInfo['shopCatId2']);
            $v['goodsName'] = $goodsInfo['goodsName'];
            $v['recommDesc'] = $goodsInfo['recommDesc'];
            $v['goodsUnit'] = $goodsInfo['goodsUnit'];
            $v['goodsSpec'] = $goodsInfo['goodsSpec'];
            $v['goodsDesc'] = $goodsInfo['goodsDesc'];
            $v['saleCount'] = $goodsInfo['saleCount'];
            $v['goodsImg'] = $goodsInfo['goodsImg'];
            $v['goodsThums'] = $goodsInfo['goodsThums'];
            $v['goodsStock'] = $goodsInfo['goodsStock'];
            $v['goodsSpec'] = $goodsInfo['goodsSpec'];
            $v['SuppPriceDiff'] = $goodsInfo['SuppPriceDiff'];
            //$v['weightG'] = $goodsInfo['weightG'];
            $v['weightG'] = 0;//条码称重
            $barcodeInfo = M('barcode')->where(['goodsId'=>$goodsInfo['goodsId']])->find();
            if($barcodeInfo){
                $v['weightG'] = $barcodeInfo['weight'];
            }
            $orderGoodsInfo = M('order_goods')->where(['orderId'=>$info['orderId'],'goodsId'=>$v['goodsId']])->find();
            $v['goodsPrice'] = $orderGoodsInfo['goodsPrice'];

            if(is_null($v['startDate']) || is_null($v['endDate'])){
                $v['sortStartDate'] = 0;
                $v['sortEndDate'] = 0;
                $v['sortOverTime'] = 0;
            }else{
                $endDateInt = strtotime($v['endDate']);
                $startDateInt = strtotime($v['startDate']);
                $diffTime = timeDiff($endDateInt,$startDateInt);//耗时
                $v['sortOverTime'] =  $diffTime['day'] * 24 * 60 + $diffTime['hour'] * 60 + $diffTime['min'];
            }
            $location = getGoodsLocation($v['goodsId']); //商品对应的货位信息
            $v['locationName'] = $this->getLocationList($location);//数据处理
        }
        unset($v);
        //框位
        $info['basketInfo'] = $this->getBasketInfo($info['basketId']); //获取框位信息
        if($info){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '获取数据成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $info;
        }
        return $apiRet;
    }

    /**
     * @param $location
     * @return string
     * 获取货位名称
     */
    public function getLocationList($location){
        if(empty($location)){
            return "";
        }
        $allName = "";
        $name = "";
        foreach ($location as $k=>$v){
            foreach ($v['secondLocation'] as $key => $val){
                $allName .= '/'.$val['name'];
            }
            $name .= $v['name'].$allName.'/';
        }
        //删除字符串最后一位字符
        $nameNew = substr($name, 0, -1);
        return (string)$nameNew;
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
	 * */
    public function getSortingListCount($request){
        $where = " where s.shopid='".$request['shopId']."' and s.sortingFlag=1 and sp.isdel=1 and s.settlement<1 and o.orderFlag=1 ";
        if(!empty($request['settlementNo'])){
            $where .= " and s.settlementNo='".$request['settlementNo']."' ";
        }
        if(!empty($request['orderNo'])){
            $where .= " and o.orderNo='".$request['orderNo']."' ";
        }
        if(!empty($request['userName'])){
            $where .= " and u.userName like'%".$request['userName']."%' ";
        }
        if(!empty($request['userPhone'])){
            $where .= " and u.userPhone like'%".$request['userPhone']."%' ";
        }
        if(!empty($request['personName'])){
            $where .= " and sp.userName like'%".$request['personName']."%' ";
        }
        if(!empty($request['personMobile'])){
            $where .= " and sp.mobile like'%".$request['personMobile']."%' ";
        }
        if(!empty($request['startDate']) && !empty($request['endDate'])){
            $where .= " and s.addtime between '".$request['startDate']."' and '".$request['endDate']."' ";
        }
        if($request['status'] != 20){
            $where .= " and s.status='".$request['status']."' ";
        }
        $info['all'] = 0; //;所有
        $info['statusNo'] = 0; //待分拣
        $info['statusWaiting'] = 0; //分拣中
        $info['statusBasket'] = 0; //待入框
        $info['statusYes'] = 0; //已分拣
        $sql = "select count(s.id) from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where;
        $all = $this->queryRow($sql)['count(s.id)'];
        if(!is_null($all)){
            $info['all'] = $all;
        }

        $sql = "select count(s.id) from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." and s.status=0 ";
        $statusNo = $this->queryRow($sql)['count(s.id)'];
        if(!is_null($all)){
            $info['statusNo'] = $statusNo;
        }

        $sql = "select count(s.id) from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." and s.status=1 ";
        $statusWaiting = $this->queryRow($sql)['count(s.id)'];
        if(!is_null($statusWaiting)){
            $info['statusWaiting'] = $statusWaiting;
        }

        $sql = "select count(s.id) from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." and s.status=2 ";
        $statusBasket = $this->queryRow($sql)['count(s.id)'];
        if(!is_null($statusBasket)){
            $info['statusBasket'] = $statusBasket;
        }

        $sql = "select count(s.id) from __PREFIX__sorting s left join __PREFIX__orders o on o.orderId=s.orderId left join __PREFIX__users u on o.userId=u.userId left join __PREFIX__sortingpersonnel sp on sp.id=s.personId ".$where." and s.status=3 ";
        $statusYes = $this->queryRow($sql)['count(s.id)'];
        if(!is_null($statusYes)){
            $info['statusYes'] = $statusYes;
        }
        if($request['status'] == 0){//后加
            $info = [];
            $info['statusNo'] = $statusNo;
        }
        if($request['status'] == 1){//后加
            $info = [];
            $info['statusWaiting'] = $statusWaiting;
        }
        if($request['status'] == 2){//后加
            $info = [];
            $info['statusBasket'] = $statusBasket;
        }
        if($request['status'] == 3){//后加
            $info = [];
            $info['statusYes'] = $statusYes;
        }
        $apiRet['apiCode'] = 0;
        $apiRet['apiInfo'] = '获取数据成功';
        $apiRet['apiState'] = 'success';
        $apiRet['apiData'] = $info;
        return $apiRet;
    }

    /*
     *获取商品商城分类
     * */
    public function getGoodsCatName($catId){
        $catname = '';
        if($catId > 0){
            $catname = M('goods_cats')->where(['catId'=>$catId])->getField('catName');
        }
        return $catname;
    }

    /*
     *获取商品店铺分类
     * */
    public function getGoodsShopCatName($catId){
        $catname = '';
        if($catId > 0){
            $catname = M('shops_cats')->where(['catId'=>$catId])->getField('catName');
        }
        return $catname;
    }

    /*
     *获取分拣任务的操作日志
     * */
    public function getSortingActLog($sortingId){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '获取数据失败';
        $apiRet['apiState'] = 'error';

        $tab = M('sorting_action_log');
        //where
        $where['sortingId'] = $sortingId;

        $list = $tab->where($where)->order('id asc')->select();
        if($list){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $list;
        }
        return $apiRet;
    }

    /**
     * 获取分拣员详情
     * @param int $shopId
     * @param int $id 分拣员id
     * @return array data
     */
    public function getSortingpersonnelInfo(int $shopId,int $id){
        $tab = M('sortingpersonnel');
        $where['shopid'] = $shopId;
        $where['id'] = $id;
        $where['isdel'] = 1;
        $field = 'id,userName,mobile,state,account';//不知道为什么返回的字段和表字段不一样,神经病
        $data = $tab->where($where)->field($field)->find();
        if(empty($data)){
            return [];
        }
        $relationTab = M('sorting_location_relation');
        $data['locations'] = '';
        $where = [];
        $where['personId'] = $data['id'];
        $where['isDelete'] = 0;
        $personSortingLocation = $relationTab->where($where)->select();
        if($personSortingLocation){
            $locationIdArr = array_column($personSortingLocation,'locationId');
            $locationIdArr = array_unique($locationIdArr);
            $data['locations'] = implode(',',$locationIdArr);
        }
        return $data;
    }
}
?>
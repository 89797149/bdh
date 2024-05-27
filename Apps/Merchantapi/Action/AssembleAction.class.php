<?php
 namespace Merchantapi\Action;;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 拼团活动控制器
 */
class AssembleAction extends BaseAction{

    /**
     * 拼团活动列表 (废弃)
     */
	public function assembleList(){
        $shopId = $this->MemberVeri()['shopId'];
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId' =>  $shopId,
            'title' =>  I('title','','trim'),
            'state' =>  I('state',0,'intval'),//0：全部 1：未开始 2：进行中 3：已结束
            'goodsName' =>  I('goodsName','','trim'),//商品名称
            'start_time'    =>  I('start_time','','trim'),//开始时间
            'end_time'      =>  I('end_time','','trim'),//结束时间
            'page'  =>  I('page',1,'intval'),
            'pageSize'  => I('pageSize',10,'intval')//默认每页显示条数
        );

        $m = D('Merchantapi/Assemble');
        $list = $m->getAssembleList($param);

        $this->ajaxReturn(array('code' => 0, 'list' => $list));
    }

    /**
     * 获取拼团活动列表
     * @param varchar title 活动标题
     * @param varchar goodsName 商品名称
     * @param dateTime startDate 创建时间->开始时间
     * @param dateTime endDate 创建时间->结束时间
     * @param int state 状态【1：未开始|2：进行中|3：已结束】
     * @param int activityStatus 活动状态【-1：关闭|1：开启】
     * @param int page 页码
     * @param int pageSize 分页条数
     */
    public function getAssembleActivityList(){
        $shopId = $this->MemberVeri()['shopId'];
        $requestParams = I();
        $params = [];
        $params['title'] = '';
        $params['goodsName'] = '';
        $params['startDate'] = '';
        $params['endDate'] = '';
        $params['state'] = '';
        $params['activityStatus'] = '';
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params,$requestParams);
        $params['shopId'] = $shopId;
        $m = D('Merchantapi/Assemble');
        $res = $m->getAssembleActivityList($params);
        $this->ajaxReturn($res);
    }

    /**
     * 添加拼团活动
     * @param varchar title 活动标题
     * @param int groupPeopleNum 成团人数
     * @param int limitNum 限购数量
     * @param int goodsId 商品id
     * @param float tprice 拼团价格
     * @param dateTime startTime 活动开始时间
     * @param dateTime endTime 活动结束时间
     * @param varchar describle 活动说明
     * @param int limitHour 成团限期 PS:单位(小时)
     */
    public function addAssembleActivity(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $params = [];
        $params['title'] = '';
        $params['groupPeopleNum'] = 0;
        $params['limitNum'] = 0;
        $params['goodsId'] = 0;
        $params['tprice'] = 0;
        $params['startTime'] = '';
        $params['endTime'] = '';
        $params['describle'] = '';
        $params['limitHour'] = 0;
        parm_filter($params,$requestParams);
        if(empty($params['title']) || empty($params['goodsId']) || empty($params['startTime']) || empty($params['endTime']) || empty($params['describle'])){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数错误'));
        }
        if($params['groupPeopleNum'] <= 0){
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写正确的成团人数'));
        }
//        if($params['tprice'] <= 0){
//            $this->ajaxReturn(returnData(false, -1, 'error', '请填写正确的拼团价格'));
//        }
        if((float)$params['tprice'] < 0){
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写正确的拼团价格'));
        }
        if($params['limitHour'] <= 0){
            $this->ajaxReturn(returnData(false, -1, 'error', '请填写正确的成团期限'));
        }
        $m = D('Merchantapi/Assemble');
        $res = $m->addAssembleActivity($params,$shopInfo);
        return $this->ajaxReturn($res);
    }

    /**
     * 添加和编辑拼团活动 (废弃)
     */
    public function editAssemble(){

        $shopId = $this->MemberVeri()['shopId'];
//        $shopId = I('shopId',0,'intval');
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $aid = I('aid',0,'intval');
        $data = I('param.');

        unset($data['token']);
        $data['shopId'] = $shopId;
        $m = D('Merchantapi/Assemble');
        if ($aid > 0) {//编辑
            unset($data['aid']);
            $result = $m->updateAssemble($aid, $data);
        } else {//添加
            $data['createTime'] = date('Y-m-d H:i:s');
            $result = $m->insertAssemble($data);
        }


        $this->ajaxReturn(array('code' => $result ? 0 : 1));
    }

    /**
     * 编辑拼团活动
     * @param int aid 活动id
     * @param varchar title 活动标题
     * @param int groupPeopleNum 成团人数
     * @param int limitNum 限购数量
     * @param float tprice 拼团价格
     * @param dateTime startTime 活动开始时间
     * @param dateTime endTime 活动结束时间
     * @param varchar describle 活动说明
     * @param int limitHour 成团限期 PS:单位(小时)
     * @param int activityStatus 活动状态【-1:关闭|1:开启】
     * @param jsonString activityGoodsSku 拼团商品信息
     */
    public function updateAssembleActivity(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $params = [];
        $params['aid'] = null;
        $params['title'] = null;
        $params['groupPeopleNum'] = null;
        $params['limitNum'] = null;
        $params['goodsId'] = null;
        $params['tprice'] = null;
        $params['startTime'] = null;
        $params['endTime'] = null;
        $params['describle'] = null;
        $params['limitHour'] = null;
        $params['activityStatus'] = null;
        $params['activityGoodsSku'] = null;
        parm_filter($params,$requestParams);
        if(empty($params['aid'])){
            $returnData = returnData(false, -1, 'error', '参数错误');
            $this->ajaxReturn($returnData);
        }
        if(is_numeric($params['tprice']) && $params['tprice'] < 0){
            $returnData = returnData(false, -1, 'error', '请填写正确的拼团价格');
            $this->ajaxReturn($returnData);
        }
        if(is_numeric($params['groupPeopleNum']) && $params['groupPeopleNum'] <=0){
            $returnData = returnData(false, -1, 'error', '请填写正确的成团人数');
            $this->ajaxReturn($returnData);
        }
//        if(is_numeric($params['limitNum']) && $params['limitNum'] <=0){
//            $returnData = returnData(false, -1, 'error', '请填写正确的限购数量');
//            $this->ajaxReturn($returnData);
//        }
        if(is_numeric($params['limitHour']) && $params['limitHour'] <=0){
            $returnData = returnData(false, -1, 'error', '请填写正确的拼团期限');
            $this->ajaxReturn($returnData);
        }
        $m = D('Merchantapi/Assemble');
        $res = $m->updateAssembleActivity($params,$shopInfo);
        return $this->ajaxReturn($res);
    }

    /**
     * 拼团活动详情(废弃)
     */
    public function assembleDetail(){
        $aid = I('aid',0,'intval');
        if (empty($aid)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $m = D('Merchantapi/Assemble');
        $this->ajaxReturn(array(
            'code'  =>  0,
            'info'  =>  $m->assembleDetail($aid),
            'assembleUser'  =>  $m->getAssembleUser($aid)
        ));
    }

    /**
     * 拼团活动详情
     * @param int aid 拼团活动id
     */
    public function getAssembleActivityDetail(){
        $this->MemberVeri();
        $aid = I('aid',0,'intval');
        if(empty($aid)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Assemble');
        $res = $m->getAssembleActivityDetail($aid);
        return $this->ajaxReturn($res);
    }

    /**
     * 删除拼团活动 (废弃)
     */
//    public function deleteAssemble(){
//        $shopId = $this->MemberVeri()['shopId'];
//        $aid = I('aid',0,'intval');
//        if (empty($aid) || empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));
//
//        $result = D('Merchantapi/Assemble')->deleteAssemble($aid, $shopId);
//        $this->ajaxReturn(array('code'  =>  $result ? 0 : 1));
//    }

    /**
     * 删除拼团活动
     * @param int aid 拼团活动id
     */
    public function delAssembleActivity(){
        $shopInfo = $this->MemberVeri();
        $aid = I('aid',0,'intval');
        if(empty($aid)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Assemble');
        $res = $m->delAssembleActivity($aid,$shopInfo);
        return $this->ajaxReturn($res);
    }

    /**
     * 拼团订单（正在进行中和拼团失败） (废弃)
     */
    public function assembleOrderList(){
        $shopId = $this->MemberVeri()['shopId'];
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId' =>  $shopId,
            'curTime'   =>  date('Y-m-d H:i:s'),//当前时间
            'orderNo'   =>  I('orderNo','','trim'),//订单号
            'userName'  =>  I('userName','','trim'),//收货人
            'userPhone' =>  I('userPhone','','trim'),//收件人手机
            'page'  =>  I('page',1,'intval'),
            'pageSize'  =>  I('pageSize',10,'intval')//默认每页显示条数
        );

        $m = D('Merchantapi/Assemble');
        $list = $m->getAssembleOrderList($param);

        $this->ajaxReturn(array('code' => 0, 'list' => $list));
    }

    /**
     * 店铺商品搜索
     */
//    public function searchShopGoods(){
//        $keywords = I('keywords','','trim');
//        if (empty($keywords)) exit();
//
//        $shopId = $this->MemberVeri()['shopId'];
//        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));
//
//        $param = array(
//            'keywords'  =>  $keywords,
//            'shopId'    =>  $shopId
//        );
//
//        $m = D('Merchantapi/Assemble');
//        $list = $m->getShopGoodsListByKeywords($param);
//
//        $this->ajaxReturn(array('code'=>0, 'list'=>$list));
//    }

     /**
      * 店铺商品搜索
      * @param varchar keywords 商品关键字
      * */
     public function searchShopGoods(){
         $shopInfo = $this->MemberVeri();
         $keywords = I('keywords','','trim');
         if(empty($keywords)){
             $this->ajaxReturn(returnData(false, -1, 'error', '请输入商品关键字'));
         }
         $m = D('Merchantapi/Assemble');
         $res = $m->searchShopGoods($keywords,$shopInfo);
         $this->ajaxReturn($res);
     }

    /**
     * 拼团列表 (废弃)
     */
    public function assembleListData(){
        $shopId = $this->MemberVeri()['shopId'];
        if (empty($shopId)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数不全'));

        $param = array(
            'shopId' =>  $shopId,
            'title' =>  I('title','','trim'),
            'state' =>  I('state',-2,'intval'),//-2:全部 -1：拼团失败 0：进行中 1：拼团成功
            'goodsName' =>  I('goodsName','','trim'),//商品名称
            'start_time'    =>  I('start_time','','trim'),//开始时间
            'end_time'      =>  I('end_time','','trim'),//结束时间
            'page'  =>  I('page',1,'intval'),
            'pageSize'  => I('pageSize',10,'intval')//默认每页显示条数
        );

        $m = D('Merchantapi/Assemble');
        $list = $m->getAssembleListData($param);

        $this->ajaxReturn(array('code' => 0, 'list' => $list));
    }

    /**
     * 获取拼团列表
     * @param varchar title 活动标题
     * @param varchar goodsName 商品名称
     * @param int state 状态【-1:拼团失败,0:拼团中,1:拼团成功】
     * @param varchar userName 团长名称
     * @param dateTime startDate 开始时间
     * @param dateTime endDate 结束时间
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getAssembleList(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $params = [];
        $params['title'] = '';
        $params['userName'] = '';
        $params['goodsName'] = '';
        $params['startDate'] = '';
        $params['endDate'] = '';
        $params['state'] = '';
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params,$requestParams);
        $m = D('Merchantapi/Assemble');
        $res = $m->getAssembleList($params,$shopInfo);
        $this->ajaxReturn($res);
    }

    /**
     * 拼团详情
     * @param int pid 拼团id
     */
    public function getAssembleDetail(){
        $this->MemberVeri();
        $pid = I('pid',0,'intval');
        if(empty($pid)){
            $this->ajaxReturn(returnData(false, -1, 'error', '参数有误'));
        }
        $m = D('Merchantapi/Assemble');
        $res = $m->getAssembleDetail($pid);
        return $this->ajaxReturn($res);
    }

    /**
     * 拼团订单
     * @param varchar orderNo 订单号
     * @param varchar userName 收货人姓名
     * @param varchar userPhone 收货人手机号
     * @param varchar assembleUserName 团长名称
     * @param varchar assembleUserPhone 团长手机号
     * @param int page 页码
     * @param int pageSize 分页条数,默认15条
     */
    public function getAssembleOrderList(){
        $shopInfo = $this->MemberVeri();
        $requestParams = I();
        $params  = [];
        $params['orderNo'] = '';
        $params['userName'] = '';
        $params['userPhone'] = '';
        $params['assembleUserName'] = '';
        $params['assembleUserPhone'] = '';
        $params['page'] = 1;
        $params['pageSize'] = 15;
        parm_filter($params,$requestParams);
        $m = D('Merchantapi/Assemble');
        $res = $m->getAssembleOrderList($params,$shopInfo);
        $this->ajaxReturn($res);
    }


};
?>
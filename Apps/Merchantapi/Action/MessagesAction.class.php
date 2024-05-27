<?php
 namespace Merchantapi\Action;;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商城消息控制器
 */
class MessagesAction extends BaseAction{
//    /**
//	 * 分页查询
//	 */
//	public function queryByPage(){
//		$shopInfo = $this->MemberVeri();
//		$USER = session('WST_USER');
//		$m = D('Home/Messages');
//    	$page = $m->queryByPage();
//    	$pager = new \Think\Page($page['total'],$page['pageSize']);// 实例化分页类 传入总记录数和每页显示的记录数
//    	$page['pager'] = $pager->show();
//    	$this->assign('Page',$page);
//    	$this->assign("umark","queryMessageByPage");
//    	if($USER['loginTarget']=='User'){
//            $this->display("default/users/messages/list");
//    	}else{
//    		$this->display("default/shops/messages/list");
//    	}
//	}


     /**
      * 分页查询
      */
     public function getlist(){
         $params = $this->MemberVeri();
         $USER = session('WST_USER');
         $m = D('Home/Messages');
         $params['page'] = (int)I('page',1);
         $params['pageSize'] = (int)I('pageSize',15);
         $page = $m->queryByPage($params);
         $pager = new \Think\Page($page['total'],$page['pageSize']);// 实例化分页类 传入总记录数和每页显示的记录数
         $page['pager'] = $pager->show();
         $this->returnResponse(1,'获取成功',$page);
     }

    /**
     * 显示详情页面
     */
    public function showMessage(){
        $shopInfo = $this->MemberVeri();
        $info = D('Home/Messages')->get($shopInfo);
        $this->returnResponse(1,'获取成功',$info);
    }

    public function batchDel(){
        $shopInfo = $this->MemberVeri();
        $re = D('Home/Messages')->batchDel($shopInfo);
        $this->ajaxReturn($re);
    }

     /**
      * 商城信息数量统计
      * #param string token
      * #param int status PS:状态(0:未读 | 1:已读 | 20:全部)
      */
     public function getlistCount(){
         $shopInfo = $this->MemberVeri();
         $m = D('Home/Messages');
         $param = I();
         $res = $m->getlistCount($shopInfo,$param);
         $this->ajaxReturn($res);
     }
};
?>
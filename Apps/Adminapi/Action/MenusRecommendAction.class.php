<?php
namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 每日推荐
 */
class MenusRecommendAction extends BaseAction{
    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $m = D('Adminapi/MenusRecommend');
        $list = $m->queryByPage($page,$pageSize);
        $this->returnResponse(0,'操作成功',$list);
    }

    /**
     * 查看详情
     */
    public function detail(){
        $this->isLogin();
        $id = I('id',0,'intval');
        if (empty($id)) {
            $this->returnResponse(-1,'参数不全');
        }
        $m = D('Adminapi/MenusRecommend');
        $detail = $m->getInfo($id);
        $this->returnResponse(0,'操作成功',$detail);
    }
    /**
     * 新增/修改操作
     */
    public function edit(){
        $this->isLogin();
        $m = D('Adminapi/MenusRecommend');
        $param = I();
        isset($param['id'])?$response['id']=$param['id']:false;
        isset($param['title'])?$response['title']=$param['title']:false;
//        isset($_POST['menuId'])?$response['menuId']=implode(',',json_decode($_POST['menuId'])):false;
        isset($param['menuId'])?$response['menuId']=$param['menuId']:false;
        if($response['id'] > 0){
            $rs = $m->edit($response);
        }else{
            $response['addTime'] = date('Y-m-d H:i:s',time());
            $rs = $m->insert($response);
        }
        $this->ajaxReturn($rs);
    }
    /**
     * 删除操作
     */
    public function del(){
        $this->isLogin();
//        $this->checkPrivelege('mrtj_03');
        $m = D('Adminapi/MenusRecommend');
        $param = I();
        isset($param['id'])?$response['id']=$param['id']:false;
        $rs = $m->del($response);
        $this->ajaxReturn($rs);
    }
};
?>
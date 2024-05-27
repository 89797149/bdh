<?php
namespace Adminapi\Action;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 团长
 */
class GroupAction extends BaseAction{
    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('tzsq_00');
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        $m = D('Adminapi/Group');
        $list = $m->queryByPage($page,$pageSize);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
    }


    /**
     * 查看详情
     */
    public function detail(){
        $this->isLogin();
        $this->checkPrivelege('tzsq_01');
        $rs = array('code'=>-1,'msg'=>'操作失败','data'=>array());
        $request = I();
        $m = D('Adminapi/Group');
        $data['id'] = $request['id'];
        if (empty($data['id'])) {
            $rs['msg'] = '参数不全';
            $this->ajaxReturn($rs);
        }
        $detail = $m->getInfo($data);

        $rs['code'] = 0;
        $rs['msg'] = '操作成功';
        $rs['data'] = $detail;
        $this->ajaxReturn($rs);
    }

    /**
     * 新增/修改操作
     */
    public function edit(){
        $this->isLogin();
        $this->checkPrivelege('tzsq_01');
        $m = D('Adminapi/Group');
        $request = I();
        $data = [];
        isset($request['id'])?$data['id']=$request['id']:false;
        isset($request['status'])?$data['status']=$request['status']:false;
        $rs = $m->edit($data);
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del(){
        $this->isLogin();
        $this->checkPrivelege('tzsq_02');
        $m = D('Adminapi/Group');
        $param = I();
        isset($param['id'])?$response['id']=$param['id']:false;
        $rs = $m->del($response);
        $this->ajaxReturn($rs);
    }
}
?>
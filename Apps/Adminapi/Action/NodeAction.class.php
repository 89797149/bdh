<?php
namespace Adminapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 商家节点控制器
 */
class NodeAction extends BaseAction {

    public function __construct()
    {
        parent::__construct();
        $this->isLogin();
//        $this->checkPrivelege('dttj_00');
    }

    /**
     * 分页查询
     */
    public function index(){
        $this->isLogin();
        $this->checkPrivelege('ddlb_00');
        $parameter = I();
        $parameter['page'] = I('page',1,'intval');
        $parameter['pageSize'] = I('pageSize',15,'intval');
        $m = D('Adminapi/Node');
        $msg = '';
        $list = $m->getlist($parameter,$msg);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取节点分类(无分页)
     */
    public function getCatListAll(){
        $this->isLogin();
        $m = D('Adminapi/Node');
        $list = $m->getCatListAll();

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$list);
        $this->ajaxReturn($rs);
    }

    /**
     * 获取
     * 无用
     */
    public function getlist(){

        $parameter = I();
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->getlist($parameter,$msg);

        $rs = array('code'=>0,'msg'=>'操作成功','data'=>$res);
        $this->ajaxReturn($rs);
    }

    /**
     * 添加节点
     */
    public function add(){
        $parameter = I();
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->addnode($parameter,$msg);
        if(!$res){
            $this->returnResponse(-1,$msg?$msg:'操作失败');
        }
        $this->returnResponse(0,'操作成功');
    }

    /**
     * 查看详情
     */
    public function detail(){
        $parameter = I();
        $m = D('Adminapi/Node');
        $msg = '';
        $detail = $m->getInfo($parameter,$msg);
        $this->returnResponse(0,'操作成功',$detail);
    }

    /**
     * 编辑账号
     */
    public function edit(){
        $parameter = I();
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->edit($parameter,$msg);
        if(!$res){
            $this->returnResponse(-1,$msg?$msg:'操作失败');
        }
        $this->returnResponse(0,'操作成功');
    }

    /**
     * 删除账号
     */
    public function del(){
        $parameter = I();
        $m = D('Adminapi/Node');
        $msg = '';
        $res = $m->del($parameter,$msg);
        if(!$res){
            $this->returnResponse(-1,$msg?$msg:'删除失败');
        }
        $this->returnResponse(0,'删除成功');
    }

    //分类 后加
    /**
     * 节点分类
     * @param string catname PS:分类名称
     */
    public function catIndex(){
        $this->isLogin();
//        $this->checkPrivelege('jdfllb_00');
        $param = I();
        $param['page'] = I('page',1,'intval');
        $param['pageSize'] = I('pageSize',15,'intval');
        $m = D('Adminapi/Node');
        $list = $m->getCatIndex($param);

        $this->returnResponse(0,'操作成功',$list);
    }

    /**
     * 节点分类详情
     */
    public function catDetail(){
        $this->isLogin();
//        $this->checkPrivelege('jdfllb_02');
        $parameter = I();
        $m = D('Adminapi/Node');
        $catDetail = $m->getCatInfo($parameter);

        $this->returnResponse(0,'操作成功',$catDetail);
    }

    /**
     * 编辑或添加节点分类
     */
    public function catEdit(){
        $this->isLogin();
        $parameter = I();
        $m = D('Adminapi/Node');
        if($parameter['id'] > 0 ){
//            $this->checkPrivelege('jdfllb_02');
            $res = $m->catEdit($parameter);
        }else{
//            $this->checkPrivelege('jdfllb_01');
            $res = $m->catAdd($parameter);
        }
        if($res['errorCode'] == -1){
            $this->returnResponse(-1,$res['errorMsg']);
        }
        $this->returnResponse(0,$res['errorMsg']);
    }

    /**
     * 删除节点分类
     * @param string id PS:分类id
     */
    public function catDel(){
        $parameter = I();
        $m = D('Adminapi/Node');
        $res = $m->catDel($parameter);
        if($res['errorCode'] == -1){
            $this->returnResponse(-1,$res['errorMsg']);
        }
        $this->returnResponse(0,$res['errorMsg']);
    }

}
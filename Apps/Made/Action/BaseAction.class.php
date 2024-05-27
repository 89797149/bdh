<?php
namespace Made\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 基础控制器
 */
use Think\Controller;
$A = DIRECTORY_SEPARATOR;
$root = WSTRootPath().$A.'Apps'.$A.'Made'.$A.'Common'.$A.'function.php';
include_once $root;
class BaseAction extends Controller {

    public function __construct(){
        parent::__construct();
        //跨域支持
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Credentials: true');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        /* $token = I("token");
        if(Niao_token($token) !== true){
            if(I("apiAll") == 1){return "error";}else{$this->ajaxReturn("error");}//返回方式处理
        } */
        //初始化系统信息
        $m = D('Made/System');
        $GLOBALS['CONFIG'] = $m->loadConfigs();
    }

    //要先实现系统内的聚合 必须得改动所有控制器和base控制 通过参数控制返回的方式 是通过return还是ajax---------------------

	//---------------比如通过参数控制  先测试node是否好使 可以的话就用这个方式 这个方式真正做到了聚合接口
	/**
	 * if($reret == 1){
	 * $this->
	 * }else{
	 * 	return 
	 * }
	 * 
	 */
    public function allurl(){
        header('Content-Type:application/json; charset=utf-8');

        $_POST['apiAll'] = 1;//表示本次调用为聚合
        $_GET['apiAll'] = 1;

        //覆盖系统returnajax方法

        // $this->ajaxReturn = function($e){
        // 	echo '23';
        // };

        $ret['code'] = -1;
        $ret['msg'] = '失败';
        $ret['data'] = [];

        $pack = I('pack');
        if(empty($pack)){
            $ret['msg'] = 'pack参数不能为空';
            exit(json_encode($ret));
        }
        $pack = htmlspecialchars_decode($pack);
        $pack = json_decode($pack,true);

        foreach($pack['urls'] as $k =>$v){
            $data = explode('/',$v['url']);
            if(count($data)<3){
                $ret['msg'] = '地址不对';
                exit(json_encode($ret));
            }
            $action = A(ucfirst($data[0]) .'/'.ucfirst($data[1]));

            //解析并 更改相关参数
            //装载公用参数
            foreach($pack['commomParam'] as $k1 =>$v1){
                $_POST[$k1]= $v1;
                $_GET[$k1] = $v1;

            }
            //装载私有参数
            foreach($pack['privateParam'][$v['name']] as $k2 =>$v2){
                $_POST[$k2]= $v2;
                $_GET[$k2] = $v2;
            }

            $t1=microtime(true); //获取程序1，开始的时间
            $ret['data'][$v['name']]['body'] = call_user_func_array(array($action,$data[2]),array());//赋值给自定义的name
            $t2=microtime(true); //获取程序1，结束的时间

            //记录每个接口调用时间
            $time1=$t2-$t1;
            $ret['data'][$v['name']]['taketime'] = $time1;

        }



        $ret['code'] = 0;
        $ret['msg'] = '成功';
        exit(json_encode($ret));





        // echo call_user_func(array($action,"getOrderID"));
        // echo call_user_func_array('test1',[]);
        // echo call_user_func_array(array($this, "test1"));

        // $this->getOrderID();
    }
    /**
     * 空操作处理
     */
    public function _empty($name){
        $status['apiCode'] = "-1";
        $status['apiInfo'] = "你的思想太飘忽，系统完全跟不上....";
        $status['apiState'] = "error";
        if(I("apiAll") == 1){return $status;}else{$this->ajaxReturn($status);}//返回方式处理
    }

    //会员身份验证 成功即返回用户信息
    public function MemberVeri(){
        $memberToken = I("memberToken");

        if(empty($memberToken)){
            $status['apiCode'] = -1;
            $status['apiInfo'] = "token字段有误";
            $status['apiState'] = "error";
            if(I("apiAll") == 1){return $status;}else{$this->ajaxReturn($status);}//返回方式处理
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken,86400*30);//查询token

        if(empty($sessionData)){
            $status['apiCode'] = "000005";
            $status['apiInfo'] = "认证失效，请重新登陆";
            $status['apiState'] = "error";
            if(I("apiAll") == 1){return $status;}else{$this->ajaxReturn($status);}//返回方式处理
        }
        return $sessionData;
    }

    //商家身份验证 成功即返回商户信息
    public function shopMemberVeri(){
        $memberToken = I("token");


        if(empty($memberToken)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken,86400*30);//查询token

        if(empty($sessionData)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }

        //普通管理员权限检测
        if($sessionData['login_type'] == 2){
            if(!$this->checkUserJurisdiction($sessionData)){
                $this->returnResponse(-3,'无权访问');
            }
        }
        //END

        return $sessionData;
    }

    //访问权限检测
    public function checkUserJurisdiction ($parameter=array(),&$msg=''){


        $urlPath_arr = explode('/',$_SERVER['PATH_INFO']);

        //未加入到权限数据库里的路由不做任何处理 辉辉 2019.6.22
        $nm = M('node');
        $IsnodeList = $nm->where("status=1 and mname = '{$urlPath_arr[0]}' and aname = '{$urlPath_arr[1]}'  ")->count();
        if($IsnodeList <= 0){//如果不存在路由
            return true;
        }

        if(!$parameter['id'] || !$parameter['shopId'] || !$urlPath_arr[0] || !$urlPath_arr[1]){
            return false;
        }

        $nodeList = $this->getUserNodes($parameter,$msg);
        if(!$nodeList){
            return false;
        }
        foreach ($nodeList as $key => $value) {
            if($value['mname'] == $urlPath_arr[0] && $value['aname'] == $urlPath_arr[1]){
                return true;
            }
        }

        return false;
    }

    public function getUserNodes ($parameter=array(),&$msg=''){
        if(!$parameter['id'] || !$parameter['shopId']){
            return false;
        }
        //缓存
        $cache_arr =S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}");
        if($cache_arr && is_array($cache_arr)){
            return $cache_arr;
        }

        //数据库
        $m = M('user_role');
        //获取角色id
        $ruList = $m->where('uid='.(int)$parameter['id'].' and shopId='.$parameter['shopId'])->select();
        if(!$ruList){
            return false;
        }
        $rid_arr = array_get_column($ruList,'rid');
        $rids = implode(',',array_unique($rid_arr));
        if(!$rids){
            return false;
        }
        //角色检测
        $rm =  M('role');
        $roleList = $rm->where('status=1 and id in('.$rids.') and shopId='.$parameter['shopId'])->select();
        $check_ridArr = array_get_column($roleList,'id');
        $check_rids = implode(',',array_unique($check_ridArr));
        if(!$check_rids){
            return false;
        }
        //END

        //获取节点
        $rnm = M('role_node');
        $nrList = $rnm->where('rid in('.$check_rids.') and shopId='.$parameter['shopId'])->select();
        $nid_arr = array_get_column($nrList,'nid');
        $nids = implode(',',array_unique($nid_arr));//用户所有权限id
        if(!$nids){
            return false;
        }

        $nm = M('node');
        $nodeList = $nm->where("status=1 and id in({$nids})")->select();
        //存缓存
        if($nodeList && is_array($nodeList)){
            S("merchatapi.shopid_{$parameter['shopId']}.userid_{$parameter['id']}",$nodeList,300);
        }
        return $nodeList;
    }

    /**
     * ajax程序验证,只要不是会员都返回-999
     */
    public function isUserLogin() {
        $USER = session('WST_USER');
        if (empty($USER) || ($USER['userId']=='')){
            if(IS_AJAX){
                if(I("apiAll") == 1){return array('status'=>-999,'url'=>'Users/login');}else{$this->ajaxReturn(array('status'=>-999,'url'=>'Users/login'));}//返回方式处理
            }else{
                $this->redirect("Users/login");
            }
        }
    }

    /**
     * 商家ajax登录验证
     */
    public function isShopLogin(){
        $USER = session('WST_USER');
        if (empty($USER) || $USER['userType']==0){
            if(IS_AJAX){
                if(I("apiAll") == 1){return array('status'=>-999,'url'=>'Shops/login');}else{$this->ajaxReturn(array('status'=>-999,'url'=>'Shops/login'));}//返回方式处理
            }else{
                $this->redirect("Shops/login");
            }
        }
    }

    /**
     * 用户登录验证-主要用来判断会员和商家共同功能的部分
     */
    public function isLogin($userType = 'Users'){
        $USER = session('WST_USER');
        if (empty($USER)){
            if(IS_AJAX){
                if(I("apiAll") == 1){return array('status'=>-999,'url'=>$userType.'/login');}else{$this->ajaxReturn(array('status'=>-999,'url'=>$userType.'/login'));}//返回方式处理
            }else{
                $this->redirect($userType."/login");
            }
        }
    }

    /**
     * 检查登录状态
     */
    public function checkLoginStatus(){
        $USER = session('WST_USER');
        if (empty($USER)){
            die("{status:-999}");
        }else{
            die("{status:1}");
        }
    }


    /**
     * 验证模块的码校验
     */
    public function checkVerify($type){
        if(stripos($GLOBALS['CONFIG']['captcha_model'],$type) !==false) {
            $verify = new \Think\Verify();
            return $verify->check(I('verify'));
        }else{
            return true;
        }
        return false;
    }

    /**
     * 核对单独的验证码
     * $re = false 的时候不是ajax返回
     * @param  boolean $re [description]
     * @return [type]      [description]
     */
    public function checkCodeVerify($re = true){
        $code = I('code');
        $verify = new \Think\Verify(array('reset'=>false));
        $rs =  $verify->check($code);
        if ($re == false) return $rs;
        else if(I("apiAll") == 1){return array('status'=>(int)$rs);}else{$this->ajaxReturn(array('status'=>(int)$rs));}//返回方式处理
    }

    /**
     * 单个上传图片
     */
    public function uploadPic(){
        $config = array(
            'maxSize'       =>  0, //上传的文件大小限制 (0-不做限制)
            'exts'          =>  array('jpg','png','gif','jpeg'), //允许上传的文件后缀
            'rootPath'      =>  './Upload/', //保存根路径
            'driver'        =>  'LOCAL', // 文件上传驱动
            'subName'       =>  array('date', 'Y-m'),
            'savePath'      =>  I('dir','uploads')."/"
        );
        $dirs = explode(",",C("WST_UPLOAD_DIR"));
        if(!in_array(I('dir','uploads'), $dirs)){
            echo '非法文件目录！';
            return false;
        }

        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $Filedata = key($_FILES);
        if(!$rs){
            $this->error($upload->getError());
        }else{
            $images = new \Think\Image();
            $images->open('./Upload/'.$rs[$Filedata]['savepath'].$rs[$Filedata]['savename']);
            $newsavename = str_replace('.','_thumb.',$rs[$Filedata]['savename']);
            $vv = $images->thumb(I('width',300), I('height',300))->save('./Upload/'.$rs[$Filedata]['savepath'].$newsavename);
            if(C('WST_M_IMG_SUFFIX')!=''){
                $msuffix = C('WST_M_IMG_SUFFIX');
                $mnewsavename = str_replace('.',$msuffix.'.',$rs[$Filedata]['savename']);
                $mnewsavename_thmb = str_replace('.',"_thumb".$msuffix.'.',$rs[$Filedata]['savename']);
                $images->open('./Upload/'.$rs[$Filedata]['savepath'].$rs[$Filedata]['savename']);
                $images->thumb(I('width',700), I('height',700))->save('./Upload/'.$rs[$Filedata]['savepath'].$mnewsavename);
                $images->thumb(I('width',250), I('height',250))->save('./Upload/'.$rs[$Filedata]['savepath'].$mnewsavename_thmb);
            }
            $rs[$Filedata]['savepath'] = "Upload/".$rs[$Filedata]['savepath'];
            $rs[$Filedata]['savethumbname'] = $newsavename;
            $rs['status'] = 1;

            echo json_encode($rs);

        }
    }

    /**
     * 产生验证码图片
     *
     */
    public function getVerify(){
        // 导入Image类库
        $Verify = new \Think\Verify();
        $Verify->length   = 4;
        $Verify->entry();
    }

    /**
     * 页尾参数初始化
     */
    public function footer(){
        $m = D('Home/Friendlinks');
        $friendLikds = $m->getFriendLinks();
        $this->assign('friendLikds',$friendLikds);
        $m = D('Home/Articles');
        $helps = $m->getHelps();
        $this->view->assign("helps",$helps);
    }

    /**
     * 设置所在城市
     */
    public function setDefaultCity($cityId){
        setcookie("areaId2", $cityId, time()+3600*24*90);
    }

    /**
     * 定位所在城市
     */
    public function getDefaultCity(){
        $areas= D('Home/Areas');
        return $areas->getDefaultCity();
    }


    /**
     * 返回所有参数
     */
    function WSTAssigns(){
        $params = I();
        $this->assign("params",$params);
    }

    /**
     * Admin
     * 登录操作验证
     */
    public function isAdminLogin(){
        $memberToken = I("token");


        if(empty($memberToken)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken,86400*30);//查询token

        if(empty($sessionData)){
            $status['status'] = -2;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }

        //普通管理员权限检测
        if($sessionData['login_type'] == 2){
            if(!$this->checkUserJurisdiction($sessionData)){
                $this->returnResponse(-3,'无权访问');
            }
        }
        //END

        return $sessionData;
    }

    /**
     * 跳转权限操作
     */
    public function checkPrivelege($grant){
        $s = session('WST_STAFF.grant');
        if(IS_AJAX){
            if(empty($s) || !in_array($grant,$s))die("{status:-998}");
        }else{
            if(empty($s) || !in_array($grant,$s)){
                $this->display("/noprivelege");exit();
            }
        }
    }

    //二开-公用方法
    public function returnResponse ($code,$msg = '',$data=array()){
        $returnData = array(
            'status' =>$code,
            'msg' => $msg,
            'data' => $data,
        );
        if(isset($data['list'])){
            $returnData['list'] =  $data['list'];
            unset($returnData['data']);
        }
        $this->ajaxReturn($returnData);
    }

    /**
     * 格式化查询语句中传入的in 参与，防止sql注入
     * @param unknown $split
     * @param unknown $str
     */
    public function formatIn($split,$str){
        if(is_array($str)){
            $strdatas = $str;
        }else{
            $strdatas = explode($split,$str);
        }

        $data = array();
        for($i=0;$i<count($strdatas);$i++){
            $data[] = (int)$strdatas[$i];
        }
        $data = array_unique($data);
        return implode($split,$data);
    }

    /**
     * 新后台登录操作验证
     */
    public function isAdminapiLogin(){
        return $this->AdminapiMemberVeri();
    }

    /*
     * 新后台
     * 会员身份验证 成功即返回用户信息
     * */
    public function AdminapiMemberVeri(){
        $memberToken = I("token");

        if(empty($memberToken)){
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        //$sessionData = session($memberToken);
        //$sessionData = S($memberToken);

        $sessionData = userTokenFind($memberToken,86400*30);//查询token

        if(empty($sessionData)){
            $status['status'] = -2;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }

        //普通管理员权限检测
        if($sessionData['login_type'] == 2){
            if(!$this->checkUserJurisdiction($sessionData)){
                $this->returnResponse(-3,'无权访问');
            }
        }
        //END
        return $sessionData;
    }

    /**
     * 跳转权限操作
     */
    public function AdminapicheckPrivelege($grant){
        return '';
        /*$s = session('WST_STAFF.grant');
        if(IS_AJAX){
            if(empty($s) || !in_array($grant,$s))die("{status:-998}");
        }else{
            if(empty($s) || !in_array($grant,$s)){
                $this->display("/noprivelege");exit();
            }
        }*/
    }
}
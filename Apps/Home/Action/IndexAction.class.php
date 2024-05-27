<?php
namespace Home\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器
 */
class IndexAction extends BaseAction {
    /**
     * 获取首页信息
     *
     */
    public function index(){
        $where['isDelete'] = 0;
        $news = M('news')->where($where)->order('id desc')->limit(5)->select();
        $bannerList = M('setting_index_banner')->order('sort asc')->select();
        $object['indexBottomBanner'] = M('setting')->where(['name'=>'indexBottomBanner'])->getField('value');
        $this->assign('news',$news);
        $config = $GLOBALS['CONFIG'];
        $this->assign('bannerList',$bannerList);
        $this->assign('config',$config);
        $this->assign('object',$object);
        $this->display("pc/index");
    }

    public function aboutus(){
        $object = M('setting')->where(['name'=>'about'])->find();
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $this->assign('object',$object);
        $this->display("pc/aboutus");
    }


    public function ourwork(){
        $object = M('setting')->where(['name'=>'work'])->find();
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $this->assign('object',$object);
        $this->display("pc/ourwork");

    }

    public function talent(){
        $object = M('setting')->where(['name'=>'join'])->find();
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $this->assign('object',$object);
        $this->display("pc/talent");

    }


    public function download(){
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        //后加start
        $setting = M('setting')->select();
        $this->assign('setting',$setting);
        //后加end
        $where['configId'] = ['IN',['55','56']];
        $downLoadUrl = M('sys_configs')->where($where)->select();
        $object = M('setting')->where(['name'=>'down'])->find();
        $img1 = $downLoadUrl[0]['fieldValue'];
        $img2 = $downLoadUrl[1]['fieldValue'];
        $this->assign('object',$object);
        $this->assign('img1',$img1);
        $this->assign('img2',$img2);
        $this->display("pc/download");

    }

    public function media(){
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $object = M('setting')->where(['name'=>'data'])->find();
        $this->assign('object',$object);
        $this->display("pc/media");

    }


    public function contactus(){
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $object = M('setting')->where(['name'=>'contact'])->find();
        $this->assign('object',$object);
        $this->display("pc/contactus");

    }

    public function news(){
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $mod = M('news'); // 实例化User对象
        $count = $mod->where('isDelete=0')->count();
        $Page = new \Think\Page($count,10);
        $show = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $news = $mod->where('isDelete=0')->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('news',$news);
        $this->display("pc/news");

    }

    public function newsInfo(){
        $config = $GLOBALS['CONFIG'];
        $this->assign('config',$config);
        $object = M('news')->where(['id'=>I('id')])->find();
        $this->assign('object',$object);
        $this->display("pc/news_info");

    }
    /**
     * 广告记数
     */
    public function access(){
        $ads = D('Home/Ads');
        $ads->statistics((int)I('id'));
    }
    /**
     * 切换城市
     */
    public function changeCity(){
        $m = D('Home/Areas');
        $areaId2 = $this->getDefaultCity();
        $provinceList = $m->getProvinceList();
        $cityList = $m->getCityGroupByKey();
        $area = $m->getArea($areaId2);
        $this->assign('provinceList',$provinceList);
        $this->assign('cityList',$cityList);
        $this->assign('area',$area);
        $this->assign('areaId2',$areaId2);
        //echo json_encode($provinceList);
        //echo json_encode($cityList);
        //echo json_encode($area);
        //exit();
        $this->display("default/change_city");
    }
    /**
     * 跳到用户注册协议
     */
    public function toUserProtocol(){
        $this->display("default/user_protocol");
    }

    /**
     * 修改切换城市ID
     */
    public function reChangeCity(){
        $this->getDefaultCity();
    }

    /*
     * 提交入驻申请
     * */
    public function submitSettled(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '字段有误';
        $apiRet['apiState'] = 'error';
        $request = I();
        if(empty($request['companyName']) || empty($request['linkName']) || empty($request['mobile'])){
            $this->ajaxReturn($apiRet);
        }
        $insert['type'] = I('type');
        $insert['companyName'] = I('companyName');
        $insert['address'] = I('address');
        $insert['lat'] = I('lat');
        $insert['lng'] = I('lng');
        $insert['desc'] = I('desc');
        $insert['linkName'] = I('linkName');
        $insert['mobile'] = I('mobile');
        $insert['remark'] = I('remark');
        $insert['business_pic'] = I('business_pic');
        $insert['authorization_pic'] = I('authorization_pic');
        $insert['addTime'] = date('Y-m-d H:i:s',time());
        $insertRes = M('settled_in')->add($insert);
        if($insertRes){
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '提交成功';
            $apiRet['apiState'] = 'success';
        }else{
            $apiRet['apiInfo'] = '操作失敗';
        }
        $this->ajaxReturn($apiRet);
    }

    /*
     *生成订单号
     * @param string param
     * */
    public function createStr(){
        $apiRet['apiCode'] = -1;
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $str = I('str');
        $add['value'] = $str;
        $add['addTime'] = date("Y-m-d H:i:s",time());
        $res = M('auto_create_orderid')->add($add);
        if($res){
            $token = str_pad($res,32,"0",STR_PAD_LEFT);
            M('auto_create_orderid')->where(["id"=>$res])->save(['key'=>$token]);
            $apiRet['apiCode'] = 0;
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
            $apiRet['orderToken'] = $token;
        }
        $this->ajaxReturn($apiRet);
    }

    /**
     * 发送打印内容
     */
    public function printcenter(){
        header("Content-Type: text/html;charset=utf-8");
        // $DEVICE_NO = I('DEVICE_NO');
        // $key = I('key');
        // $hsize = I('hsize');
        // $vsize = I('vsize');
        // $gap = I('gap',1);
        // $content = I('content');
        // $times = I('times',1);
        // $url = I('url');
        // $selfMessage = array(
        //     'deviceNo'=>$DEVICE_NO,
        //     'printContent'=>$content,
        //     'key'=>$key,
        //     'times'=>$times,
        //     'hsize'=>$hsize,
        //     'vsize'=>$vsize,
        //     'gap'=>$gap
        // );

        $str = I('str');

        $str = json_decode(base64_decode($str),true);
        $url = I('url');




        // $this->ajaxReturn($str);


        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => http_build_query($str),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $this->ajaxReturn(json_decode($result,true));
    }

}
<?php
namespace Home\Action;

/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 七牛云上传控制器
 */
class QiniuUploadAction extends BaseAction {

    /**
     * 单个上传图片
     */
/*    public function uploadPic(){
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
        $setting['saveName'] = array('getQiniuImgName',I('str'));
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];
        $upload = new \Think\Upload($setting);
        $rs = $upload->upload($_FILES);
        if(empty($rs)){
            $this->error($upload->getError());
        }

        $rs['file']['url_new'] = Qiniu_Sign($rs['file']['url']);
        $rs['status'] = 1;

        //本接口需要做兼容（移动端、pc端）
        //兼容接收参数
        //兼容返回结果结构 （可选择返回结构）
//        $flag = I('flag',0,'intval');//1:PC端 2：移动端
        $flag = $_REQUEST['flag'];//1:PC端 2：移动端
        if ($flag == 1) {//PC端
            $rs = array_values($rs);
            $result = array('code'=>0,'url'=>array($rs[0]['url']));
            echo json_encode($result);
            exit();
        } else if ($flag == 2) {//移动端
            $rs = array_values($rs);
            $result = array('code'=>0,'url'=>array($rs[0]['url']));
            echo json_encode($result);
            exit();
        }

        echo json_encode($rs);
//        echo json_encode(array('code'=>0,'url'=>array($rs['file']['url'])));
    }*/

    /**
     * 上传图片
     * 支持单个图片上传、多个图片上传
     * 使用的是 tp 自带的七牛云驱动上传，只是上传速度实在是太慢了
     * 正常的，可用的
     */
    public function uploadPic(){
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
        $setting['saveName'] = array('getQiniuImgName',I('str'));
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];
        $upload = new \Think\Upload($setting);
        $rs = $upload->upload($_FILES);
        if(empty($rs)){
            $this->error($upload->getError());
        }

//        $rs['file']['url_new'] = Qiniu_Sign($rs['file']['url']);
//        $rs['status'] = 1;

        //本接口需要做兼容（移动端、pc端）
        //兼容接收参数
        //兼容返回结果结构 （可选择返回结构）
//        $flag = I('flag',0,'intval');//1:PC端 2：移动端
        $flag = $_REQUEST['flag'];//1:PC端 2：移动端
        if ($flag == 1) {//PC端
            foreach ($rs as $v) {
                $pArray[] = $setting['driverConfig']['domain']."/".strtr($v['name'], '/', '_');
            }
            echo json_encode(array('code'=>0,'url'=>$pArray));
            exit();
        } else if ($flag == 2) {//移动端
            foreach ($rs as $v) {
                $pArray[] = $setting['driverConfig']['domain']."/".strtr($v['name'], '/', '_');
            }
            echo json_encode(array('code'=>0,'url'=>$pArray));
            exit();
        }

        echo json_encode($rs);
//        echo json_encode(array('code'=>0,'url'=>array($rs['file']['url'])));
    }

    /**
     * 上传图片
     * 支持单个图片上传、多个图片上传
     * 可以单个图片上传成功，目前暂时不能批量上传
     */
    public function uploadPicss(){
        vendor('Qiniu.autoload');
        vendor('Qiniu.src.Qiniu.Auth');
        vendor('Qiniu.src.Qiniu.Storage.BucketManager');
        vendor('Qiniu.src.Qiniu.Storage.UploadManager');

        $str = I('str');
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
        $setting['saveName'] = array('getQiniuImgName',I('str'));
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];

        $accessKey = $setting['driverConfig']['accessKey'];
        $secretKey = $setting['driverConfig']['secrectKey'];
        $bucket = $setting['driverConfig']['bucket'];

        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);

        // 生成上传 Token
        $token = $auth->uploadToken($bucket);

        // 要上传文件的本地路径
//        $filePath = './php-logo.png';
        $filePath = $_FILES['file']['tmp_name'];
        $ext = explode('.',$_FILES['file']['name'])[1];  //后缀

        // 上传到七牛后保存的文件名
//        $key = 'my-php-logo.png';
        $key = getQiniuImgName($str);

        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new \Qiniu\Storage\UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
//        echo "\n====> putFile result: \n";
        if ($err !== null) {
//            var_dump($err);
            echo json_encode($err);
            exit();
        } else {

            if ($ext == 'gif') {
                $info = [
                    'data'=>$setting['driverConfig']['domain'].'/'.$ret['key'].'?imageView2/0/h/300/q/60',
                    'status'=>'1'
                ];
            }else{
                $info = [
                    'data'=>$setting['driverConfig']['domain'].'/'.$ret['key'].'?imageView2/0/h/800/format/jpg/q/75',
                    'status'=>'1'
                ];
            }
//            echo "<pre>";var_dump($info);exit();
            /*if (!empty($ret)) {
                foreach ($ret as $v) {
                    $info[] = $setting['driverConfig']['domain'].'/'.$v['key'].'?imageView2/0/h/800/format/jpg/q/75';
                }
            }
            $data = array('code'=>0,'url'=>$info);
            echo json_encode($data);exit();*/
        }

//        $rs['file']['url_new'] = Qiniu_Sign($rs['file']['url']);
//        $rs['status'] = 1;

        //本接口需要做兼容（移动端、pc端）
        //兼容接收参数
        //兼容返回结果结构 （可选择返回结构）
//        $flag = I('flag',0,'intval');//1:PC端 2：移动端
        $flag = $_REQUEST['flag'];//1:PC端 2：移动端
        if ($flag == 1) {//PC端
            foreach ($ret as $v) {
                $pArray[] = $setting['driverConfig']['domain']."/".strtr($v['name'], '/', '_');
            }
            echo json_encode(array('code'=>0,'url'=>$pArray));
            exit();
        } else if ($flag == 2) {//移动端
            foreach ($ret as $v) {
                $pArray[] = $setting['driverConfig']['domain']."/".strtr($v['name'], '/', '_');
            }
            echo json_encode(array('code'=>0,'url'=>$pArray));
            exit();
        }
/*
        foreach ($ret as $v) {
            $pArray[] = $setting['driverConfig']['domain']."/".strtr($v['name'], '/', '_');
        }
        echo json_encode(array('code'=>0,'url'=>$pArray));
        exit();*/
        echo "<pre>";var_dump($ret);exit();

//        echo json_encode($ret);
//        echo json_encode(array('code'=>0,'url'=>array($rs['file']['url'])));
    }

    public function uploadPics(){

        vendor('Qiniu.autoload');
        vendor('Qiniu.src.Qiniu.Auth');
        vendor('Qiniu.src.Qiniu.Storage.BucketManager');
        vendor('Qiniu.src.Qiniu.Storage.UploadManager');

        $str = I('str');
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
//        $setting['saveName'] = getQiniuImgName(I('str'));
        $setting['saveName'] = array('getQiniuImgName',I('str'));
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $setting['driverConfig']['qiniuUploadUrl'] = $GLOBALS['CONFIG']['qiniuUploadUrl'];

        $accessKey = $setting['driverConfig']['accessKey'];
        $secretKey = $setting['driverConfig']['secrectKey'];
        $bucket = $setting['driverConfig']['bucket'];
        $image_arr = array();
        if (!empty($_FILES)) {
            foreach ($_FILES as $v) {
                // 构建鉴权对象
                $auth = new \Qiniu\Auth($accessKey, $secretKey);

                // 生成上传 Token
                $token = $auth->uploadToken($bucket);

                // 要上传文件的本地路径
//        $filePath = './php-logo.png';
                $filePath = $v['tmp_name'];
                $ext = explode('.', $v['name'])[1];  //后缀

                // 上传到七牛后保存的文件名
//        $key = 'my-php-logo.png';
                $key = getQiniuImgName($str);

                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new \Qiniu\Storage\UploadManager();

                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
//        echo "\n====> putFile result: \n";
                if ($err !== null) {
//            var_dump($err);
                    echo json_encode($err);
                    exit();
                } else {

                    if ($ext == 'gif') {
                        $info = [
                            'data' => $setting['driverConfig']['domain'] . '/' . $ret['key'] . '?imageView2/0/h/300/q/60',
                            'status' => '1'
                        ];
                    } else {
                        $info = [
                            'data' => $setting['driverConfig']['domain'] . '/' . $ret['key'] . '?imageView2/0/h/800/format/jpg/q/75',
                            'status' => '1'
                        ];
                    }
                    $image_arr[] = $info['data'];
                }
            }
        }
//        echo "<pre>";var_dump($image_arr);exit();
        $result_data = array('code'=>0,'url'=>$image_arr);
        echo json_encode($result_data);
        exit();
    }

    /**
     * 获取上传token
     */
    public function getUploadToken(){
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $qiniuUploadUrl = $GLOBALS['CONFIG']['qiniuUploadUrl'];
        $qiniu = new \Think\Upload\Driver\Qiniu\QiniuStorage($setting['driverConfig']);
        $qiniu_token = $qiniu->UploadToken($setting['driverConfig']['secrectKey'],$setting['driverConfig']['accessKey']);
        echo json_encode(array('qiniu_token'=>$qiniu_token,'qiniuUploadUrl'=>$qiniuUploadUrl));
        exit();
    }

    /**
     * 获取上传token
     */
    public function getToken(){
        vendor('Qiniu.autoload');
//        use Qiniu\Auth as Auth;
//        use Qiniu\Storage\BucketManager;
//        use Qiniu\Storage\UploadManager;
        $setting=C('UPLOAD_SITEIMG_QINIU');
        $setting['rootPath'] = $GLOBALS['CONFIG']['qiniuRootPath'];
        $setting['driver'] = $GLOBALS['CONFIG']['qiniuDriver'];
        $setting['driverConfig']['accessKey'] = $GLOBALS['CONFIG']['qiniuAccessKey'];
        $setting['driverConfig']['secrectKey'] = $GLOBALS['CONFIG']['qiniuSecrectKey'];
        $setting['driverConfig']['domain'] = $GLOBALS['CONFIG']['qiniuDomain'];
        $setting['driverConfig']['bucket'] = $GLOBALS['CONFIG']['qiniuBucket'];
        $qiniuUploadUrl = $GLOBALS['CONFIG']['qiniuUploadUrl'];
        $auth = new \Qiniu\Auth($setting['driverConfig']['accessKey'], $setting['driverConfig']['secrectKey']);
        $token = $auth->uploadToken($setting['driverConfig']['bucket']);
        echo json_encode(array('qiniu_token'=>$token,'qiniuUploadUrl'=>$qiniuUploadUrl));
        exit();
    }

    /**
     * 获取七牛云上传图片的命名
     */
    public function getQiniuImgName(){
        $str = I('str','');
        echo json_encode(array('img_name'=>getQiniuImgName($str)));
        exit();
    }
    
}
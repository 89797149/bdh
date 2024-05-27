<?php

namespace Home\Model;

use http\Encoding\Stream;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 直播相关 PS:此类供于运营后台和商户后台等,所以返回格式不要随意变更
 */
class LivePlayModel extends BaseModel
{
    private $xiaoAppid = '';
    private $componentAppid = '';
    private $componentAppsecret = '';
    private $componentVerifyTicket = '';
    private $componentAccessToken = '';
    private $authorizationCode = '';
    private $authorizerRefreshToken = '';
    private $accessToken = '';

    public function __construct()
    {
        $systemModel = D('Home/System');
        $config = $systemModel->getSystemConfig();
        $this->xiaoAppid = $config['xiaoAppid'];
        $this->componentAppid = $config['component_appid'];
        $this->componentAppsecret = $config['component_appsecret'];
        $this->componentVerifyTicket = $config['component_verify_ticket'];
        $componentAccessTokenData = json_decode($config['component_access_token'], 'true');
        $this->componentAccessToken = $componentAccessTokenData;
        $authorizationCodeData = json_decode($config['authorization_code'], true);
        if (empty($authorizationCodeData)) {
            $authorizationCodeData = json_decode(json_decode(htmlspecialchars_decode($config['authorization_code']), true));
        }
        $this->authorizationCode = $authorizationCodeData;
        $authorizerRefreshToken = json_decode($config['authorizer_refresh_token'], 'true');
        $this->authorizerRefreshToken = $authorizerRefreshToken;
        $accessToken = json_decode($config['access_token'], 'true');
        $this->accessToken = $accessToken;


    }

    /*
     *
     * */
    public function callback()
    {
        //具体逻辑用到的时候再处理
    }

    /**
     * 获取component_access_token令牌,两小时过期,所以需要定时获取更新
     * */
    public function getComponentAccessToken()
    {
        $componentAccessToken = $this->componentAccessToken;
        $systemTab = M('sys_configs');
        if (empty($componentAccessToken['expiresDate']) || $componentAccessToken['expiresDate'] < date('Y-m-d H:i:s', time()) || empty($componentAccessToken['component_access_token'])) {
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $params = [];
            $params['component_appid'] = $this->componentAppid;
            $params['component_appsecret'] = $this->componentAppsecret;
            $params['component_verify_ticket'] = $this->componentVerifyTicket;
            $data = curlRequest($url, json_encode($params), 1, 1);
            $data = json_decode($data, true);
            $where = [];
            $where['fieldCode'] = 'component_access_token';
            $saveData = [];
            $saveData['component_access_token'] = $data['component_access_token'];
            $saveData['expiresDate'] = date('Y-m-d H:i:s', (3600 + time()));//保险点,设置一个小时过期
            $systemTab->where($where)->save(['fieldValue' => json_encode($saveData)]);
        }
        $data = empty($data) ? $componentAccessToken : $data;
        return $data;
    }

    /**
     * 获取刷新令牌authorizer_refresh_token，获取授权信息时得到 PS:两小时失效,所以需要定时获取更新
     * */
    public function getAuthorizerRefreshToken()
    {
        $authorizerRefreshToken = $this->authorizerRefreshToken;
        if (empty($authorizerRefreshToken['expiresDate']) || $authorizerRefreshToken['expiresDate'] < date('Y-m-d H:i:s', time())) {
            $componentAccessTokenData = $this->componentAccessToken;
            $componentAppid = $this->componentAppid;
            $authorizationCode = $this->authorizationCode['auth_code'];
            $componentAccessToken = $componentAccessTokenData['component_access_token'];
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token={$componentAccessToken}";
            $params = [];
            $params['component_appid'] = $componentAppid;
            $params['authorization_code'] = $authorizationCode;
            $data = curlRequest($url, json_encode($params), 1, 1);
            $data = json_decode($data, true);
            if (!empty($data['authorization_info']['authorizer_refresh_token'])) {
                //$expiresIn = $data['authorization_info']['expires_in'];
                $saveData = [];
                $saveData['authorizer_refresh_token'] = $data['authorization_info']['authorizer_refresh_token'];
                $saveData['authorizer_appid'] = $data['authorization_info']['authorizer_appid'];
                $saveData['expiresDate'] = date('Y-m-d H:i:s', (3600 + time()));//保险点,设置一个小时过期
                $where = [];
                $where['fieldCode'] = 'authorizer_refresh_token';
                M('sys_configs')->where($where)->save(['fieldValue' => json_encode($saveData)]);
            }
        }
        $data = empty($data) ? $authorizerRefreshToken : $data;
        return (array)$data;
    }

    /**
     * 获取预授权码pre_auth_code PS:有效期10分钟
     * */
    public function getPreAuthCode()
    {
        $systemModel = D('Home/System');
        $config = $systemModel->getSystemConfig();
        $componentAccessTokenData = $this->componentAccessToken;
        $componentAccessToken = $componentAccessTokenData['component_access_token'];
        $componentAppid = $config['component_appid'];
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token={$componentAccessToken}";
        $params = [];
        $params['component_appid'] = $componentAppid;
        $data = curlRequest($url, json_encode($params), 1, 1);
        $data = json_decode($data, true);
        return (array)$data;
    }

    /**
     * 获取授权码和授权链接
     * */
    public function getAuth()
    {
        $systemModel = D('Home/System');
        $config = $systemModel->getSystemConfig();
        $componentAppid = $this->componentAppid;
        $preAuthCode = $this->getPreAuthCode()['pre_auth_code'];
        $apiDomain = WSTDomain();
        if (!empty($config['apiDomain'])) {
            $apiDomain = $config['apiDomain'];
        }
        $redirectUrl = $apiDomain . '/Adminapi/LivePlay/redirect_url';//回调地址返回
        $url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid={$componentAppid}&pre_auth_code={$preAuthCode}&redirect_uri={$redirectUrl}&auth_type=3";
        $returnData = [
            'authUrl' => $url,
        ];
        $data = ['code' => 0, 'msg' => '成功', 'data' => $returnData];
        return $data;
    }

    /**
     * 获取授权码和过期时间
     * @param string $authCode 授权码
     * @param string $expiresIn 过期时间
     * @return mixed
     */
    public function redirect_url($authCode, $expiresIn)
    {
        //$data = ['code'=>0,'msg'=>'成功','data'=>[]];
        if (!empty($authCode)) {
            $configTab = M('sys_configs');
            $redirectUrlData = [];
            $redirectUrlData['auth_code'] = $authCode;
            $redirectUrlData['expires_in'] = $expiresIn;
            $redirectUrlData['expiresDate'] = date('Y-m-d H:i:s', ($expiresIn + time()));//失效日期
            $where = [];
            $where['fieldCode'] = 'authorization_code';
            $saveData = [];
            $saveData['fieldValue'] = json_encode($redirectUrlData);
            $saveRes = $configTab->where($where)->save($saveData);
        }
        echo "<script>window.history.go(-2)</script>>";
        exit;
//        if(!$saveRes){
//            $data['code'] = -1;
//            $data['msg'] = '微信授权码保存失败';
//            return $data;
//        }
//        return $data;
    }

    /**
     * 获取小程序接口令牌
     * */
    public function getAccessToken()
    {
        $componentAccessTokenData = $this->componentAccessToken;
        $componentAppid = $this->componentAppid;
        $authorizer_appid = $this->authorizerRefreshToken['authorizer_appid'];
        $componentAccessToken = $componentAccessTokenData['component_access_token'];
        $authorizerRefreshToken = $this->authorizerRefreshToken['authorizer_refresh_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token={$componentAccessToken}";
        $params = [];
        $params['component_appid'] = $componentAppid;
        $params['authorizer_appid'] = $authorizer_appid;
        $params['authorizer_refresh_token'] = $authorizerRefreshToken;
        $data = curlRequest($url, json_encode($params), 1, 1);
        $data = json_decode($data, true);
        if (!empty($data['authorizer_access_token'])) {
            $expiresIn = $data['expires_in'];
            $saveData = [];
            $saveData['authorizer_access_token'] = $data['authorizer_access_token'];
            $saveData['expiresDate'] = date('Y-m-d H:i:s', ($expiresIn + time()));//失效日期
            $where = [];
            $where['fieldCode'] = 'access_token';
            M('sys_configs')->where($where)->save(['fieldValue' => json_encode($saveData)]);
        }
        return (array)$data;
    }

    /**
     * 获取微信推送的ticket(component_verify_ticket)
     * @param array $params <p>
     * string timestamp
     * string nonce
     * string msg_signature
     * </p>
     * */
    public function getTicket(array $params)
    {
        $data = ['code' => 0, 'msg' => '成功', 'data' => []];
        $systemModel = D('Home/System');
        $config = $systemModel->getSystemConfig();//该处取即时配置信息,不要缓存中的信息
        include_once "wxBizMsgCrypt.php";
        vendor('WxBizMsgCrypt.wxBizMsgCrypt');
        // 第三方发送消息给公众平台
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['encodingToken'];
        $appId = $config['component_appid'];
        $timeStamp = $params['timestamp'];
        $nonce = $params['nonce'];
        $msg_sign = $params['msg_signature'];
        $pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);
        $encryptMsg = file_get_contents('php://input');
        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $array_s = $xml_tree->getElementsByTagName('MsgSignature');
        $encrypt = $array_e->item(0)->nodeValue;
        //$msg_sign = $array_s->item(0)->nodeValue;
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode != 0) {
            $data['code'] = -1;
            $data['msg'] = '校验失败';
            return $data;
        }
        $configTab = M('sys_configs');
        $notifyData = xmlToArray($msg);
        if (empty($notifyData['ComponentVerifyTicket'])) {
            $data['code'] = -1;
            $data['msg'] = 'ticket获取失败';
            return $data;
        }
        //保存component_verify_ticket
        $where = [];
        $where['fieldCode'] = 'component_verify_ticket';
        $saveData = [];
        $saveData['fieldValue'] = $notifyData['ComponentVerifyTicket'];
        $saveRes = $configTab->where($where)->save($saveData);
        if (!$saveRes) {
            $data['code'] = -1;
            $data['msg'] = 'ticket保存失败';
            return $data;
        }
        //保存component_access_token
        $data = $this->getComponentAccessToken();
        if (empty($data['component_access_token'])) {
            $data['code'] = -1;
            $data['msg'] = 'component_access_token获取失败';
            return $data;
        }
        $componentAccessTokenData = [];
        $componentAccessTokenData['component_access_token'] = $data['component_access_token'];
        $componentAccessTokenData['expiresDate'] = date('Y-m-d H:i:s', ($data['expires_in'] + time()));//失效日期
        $where = [];
        $where['fieldCode'] = 'component_access_token';
        $saveData = [];
        $saveData['fieldValue'] = json_encode($componentAccessTokenData);
        $saveRes = $configTab->where($where)->save($saveData);
        if (!$saveRes) {
            $data['code'] = -1;
            $data['msg'] = 'component_access_token保存失败';
            return $data;
        }
        echo "success";
        exit;
        return $data;
    }

    /**
     * 上传微信媒体库
     * 文档地址:https://www.yuque.com/anthony-6br1r/oq7p0p/dz8vpp
     * @param string type 媒体文件类型【图片（image）| 语音（voice）| 视频（video）| 缩略图（thumb）】
     * @param file file 文件信息
     * */
    public function uploadMedia($type, $file)
    {
        if (empty($file['file']['tmp_name'])) {
            return ['code' => -1, 'msg' => '上传文件失败'];
        }
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 5 * 1024 * 1024;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        // 设置附件上传根目录，如果服务器中没有这个目录，必须新建文件夹设置，否则无法上传文件
        $upload->rootPath = WSTRootPath() . '/Apps/Runtime/TempMedia';//上传完成会删除本地文件,所以直接放到Runtime文件里就可以了
        $upload->autoSub = false;
        $upload->savePath = '/'; //设置附件上传（子）目录
        if (!is_dir($upload->rootPath)) {
            @mkdir($upload->rootPath, 0777);
        }
        $info = $upload->upload();
        if (!$info) {
            return ['code' => -1, 'msg' => $upload->getError()];
        }
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$accessToken}&type={$type}";
        $params = [];
        $realPath = $_SERVER['DOCUMENT_ROOT'] . "/Apps/Runtime/TempMedia/{$info['file']['savename']}";
        $params['media'] = new \CURLFile($realPath);
        $curlData = curlRequest($url, $params, 1, 1);
        $curlData = json_decode($curlData, true);
        if (empty($curlData) || !empty($curlData['errmsg'])) {
            return ['code' => -1, 'msg' => !empty($curlData['errmsg']) ? $curlData['errmsg'] : '上传媒体失败'];
        }
        $result_new = uploadQiniuPic($realPath, $info['file']['savename']);
        $curlData['qiniuImageUrl'] = '';
        if ($result_new['code'] == 0) {
            $image = 'qiniu://' . $result_new['data']['key'];
            $curlData['qiniuImageUrl'] = $image;
            $mediaParams = [];
            $mediaParams['mediaID'] = $curlData['media_id'];
            $mediaParams['qiniuImageUrl'] = $image;
            $mediaParams['createTime'] = date('Y-m-d H:i:s', time());
            M('liveplay_media')->add($mediaParams);
        }
        unlink($realPath);
        $data = [
            'code' => 0,
            'msg' => '成功',
            'data' => $curlData,
        ];
        return $data;
    }

    /**
     * 添加微信小程序直播
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/studio-api.html#1
     * */
    public function addWxLiveplay($requestParams)
    {
        $params = [];
        $params['name'] = '';
        $params['coverImg'] = '';
        $params['startTime'] = '';
        $params['endTime'] = '';
        $params['anchorName'] = '';
        $params['anchorWechat'] = '';
        //$params['anchorImg'] = '';
        $params['shareImg'] = '';
        $params['feedsImg'] = '';
        $params['type'] = 0;
        $params['screenType'] = 0;
        $params['closeLike'] = 0;
        $params['closeGoods'] = 0;
        $params['closeComment'] = 0;
        parm_filter($params, $requestParams);
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxaapi/broadcast/room/create?access_token={$accessToken}";
        $data = curlRequest($url, json_encode($params), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        return $data;
    }

    /**
     * 直播间导入商品
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/studio-api.html#4
     * */
    public function addWxLiveplayGoods($requestParams)
    {
        $params = [];
        $params['ids'] = '';
        $params['roomId'] = '';
        parm_filter($params, $requestParams);
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxaapi/broadcast/room/addgoods?access_token={$accessToken}";
        $data = curlRequest($url, json_encode($params), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        return $data;
    }

    /**
     * 商品库-添加商品
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/commodity-api.html#1
     * */
    public function addWxGoods($requestParams)
    {
        $params = [];
        $params['coverImgUrl'] = '';
        $params['name'] = '';
        $params['priceType'] = '';
        $params['price'] = '';
        $params['price2'] = null;
        $params['url'] = '';
        parm_filter($params, $requestParams);
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxaapi/broadcast/goods/add?access_token={$accessToken}";
        $data = curlRequest($url, json_encode(['goodsInfo' => $params]), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        return $data;
    }

    /**
     * 修改图片像素并上传微信素材库
     * @param $filename 文件名(所在路径)
     * @param $newx 修改后最大宽度
     * @param $newy 修改后最大高度
     * @param $save_to 保存路径
     * @return array $data
     */
    public function resizeImage($fileUrl, $newx, $newy, $save_to = '')
    {
        $systemModel = D('Home/System');
        $configs = $systemModel->getSystemConfig();
        $file_url = str_replace('qiniu://', $configs['qiniuDomain'], $fileUrl);
        $imageInfo = getimagesize($file_url);
        $extension = explode('/', $imageInfo['mime'])[1];
        $filename = pathinfo($file_url)['filename'] . '.' . $extension;
        if (empty($save_to)) {
            $save_to = $_SERVER['DOCUMENT_ROOT'] . "/Apps/Runtime/TempMedia";
            if (!file_exists($save_to)) {
                @mkdir($save_to, 0777);
            }
            $save_to .= "/{$filename}";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
        if ($imageInfo[0] > 300 || $imageInfo[1] > 300) {//微信图片规则：图片尺寸最大300像素*300像素；
            //根据后缀，由文件或 URL 创建一个新图象(内置函数)
            if (in_array($extension, ['jpg', 'jpeg'])) {
                //jpg
                $im = imagecreatefromjpeg($save_to);
            } elseif (in_array($extension, ['png'])) {
                //png
                $im = imagecreatefrompng($save_to);
            } elseif (in_array($extension, ['gif'])) {
                //gif
                $im = imagecreatefromgif($save_to);
            } else {
                //bmp
                $im = imagecreatefrombmp($save_to);
            }
            //获取当前待修改图片像素（内置函数）
            $x = imagesx($im);
            $y = imagesy($im);
            //新建一个真彩色图像(内置函数)
            $im2 = imagecreatetruecolor($newx, $newy);
            //重采样拷贝部分图像并调整大小(内置函数)
            imagecopyresampled($im2, $im, 0, 0, 0, 0, floor($newx), floor($newy), $x, $y);
            if (in_array($extension, ['jpg', 'jpeg'])) {
                //jpg
                imagejpeg($im2, $save_to);
            } elseif (in_array($extension, ['png'])) {
                //png
                imagepng($im2, $save_to);
            } elseif (in_array($extension, ['gif'])) {
                //gif
                imagegif($im2, $save_to);
            } else {
                //bmp
                imagebmp($im2, $save_to);
            }
            imagedestroy($im2);
        }
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$accessToken}&type=image";
        $params = [];
        $realPath = $save_to;
        $params['media'] = new \CURLFile($realPath);
        $curlData = curlRequest($url, $params, 1, 1);
        $curlData = json_decode($curlData, true);
        if (empty($curlData) || !empty($curlData['errmsg'])) {
            return ['code' => -1, 'msg' => !empty($curlData['errmsg']) ? $curlData['errmsg'] : '上传媒体失败'];
        }
        $curlData['qiniuImageUrl'] = $fileUrl;
        $mediaParams = [];
        $mediaParams['mediaID'] = $curlData['media_id'];
        $mediaParams['qiniuImageUrl'] = $fileUrl;
        $mediaParams['createTime'] = date('Y-m-d H:i:s', time());
        M('liveplay_media')->add($mediaParams);
        unlink($realPath);
        $data = [
            'code' => 0,
            'msg' => '成功',
            'data' => $curlData,
        ];
        return $data;
    }

    /**
     * 商品库-删除商品
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/commodity-api.html#1
     * */
    public function delWxGoods($goodsId)
    {
        $params = [];
        $params['goodsId'] = $goodsId;
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxaapi/broadcast/goods/delete?access_token={$accessToken}";
        $data = curlRequest($url, json_encode($params), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        return $data;
    }

    /**
     * 商品库-更新商品
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/commodity-api.html#1
     * */
    public function updateWxGoods($requestParams)
    {
        $params = [];
        $params['goodsId'] = '';
        $params['coverImgUrl'] = null;
        $params['name'] = null;
        $params['priceType'] = null;
        $params['price'] = null;
        $params['price2'] = null;
        $params['url'] = '';
        parm_filter($params, $requestParams);
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxaapi/broadcast/goods/update?access_token={$accessToken}";
        $data = curlRequest($url, json_encode(['goodsInfo' => $params]), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        return $data;
    }

    /**
     * 商品库-获取商品状态并同步到本地
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/commodity-api.html#6
     * */
    public function getWxGoodsStatus()
    {
        $liveplayGoodsTab = M('liveplay_goods');
        $where = [];
        $where['dataFlag'] = 1;
        $where['examinePlatform'] = 1;
        $where['status'] = ['IN', [0, 1]];
        $field = 'wxGoodsId';
        $liveplayGoodsList = $liveplayGoodsTab
            ->where($where)
            ->field($field)
            ->select();
        if (empty($liveplayGoodsList)) {
            return ['code' => -1, 'msg' => '暂无相关数据', 'data' => []];
        }
        $wxGoodsId = array_column($liveplayGoodsList, 'wxGoodsId');
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxa/business/getgoodswarehouse?access_token={$accessToken}";
        $params = [];
        $params['goods_ids'] = $wxGoodsId;
        $data = curlRequest($url, json_encode($params), 1, 1, 1);
        $data = $this->getWxLiveplayErrmsg(json_decode($data, true));
        if ($data['errcode'] != 0) {
            return ['code' => -1, 'msg' => $data['errmsg'], 'data' => []];
        }
        $wxGoodsList = $data['goods'];
        foreach ($wxGoodsList as $value) {
            $where = [];
            $where['wxGoodsId'] = $value['goods_id'];
            $saveData = [];
            $saveData['status'] = $value['audit_status'];//该字段和微信文档字段保持一致
            $liveplayGoodsTab->where($where)->save($saveData);
        }
        return ['code' => 0, 'msg' => '成功', 'data' => []];
    }

    /**
     * 获取小程序直播间列表并更新本地数据状态
     * 文档地址:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/live_player/studio-api.html#2
     * */
    public function getWxLiveplayStatus()
    {
        //微信暂未提供直播间详情接口或者获取状态接口,先用定时任务更新状态,后期微信接口更新后再优化
        $liveplayTab = M('liveplay');
        $where = [];
        $where['type'] = 1;
        $where['dataFlag'] = 1;
        $where['live_status'] = ['IN', [0, 1, 2]];
        $field = 'liveplayId,name,roomId';
        $liveplayList = $liveplayTab
            ->where($where)
            ->field($field)
            ->select();
        if (empty($liveplayList)) {
            return ['code' => -1, 'msg' => '暂无相关数据', 'data' => []];
        }
        //目前只更新最新的2000条数据,微信接口暂不完善,没招了,算下来一天最多调用28800次,而接口限制为100000次/一天,目前完全足够调用
        $accessToken = $this->accessToken['authorizer_access_token'];
        for ($i = 0; $i < 20; $i++) {
            $pageSize = 100;
            $start = $i * $pageSize;
            $url = "https://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$accessToken}";
            $params = [];
            $params['start'] = $start;
            $params['limit'] = $pageSize;
            $data = curlRequest($url, json_encode($params), 1, 1, 1);
            $data = json_decode($data, true);
            if ($data['errcode'] != 0) {
                break;
            }
            $roomlist = $data['room_info'];
            foreach ($roomlist as $item) {
                $live_status = '';
                if (in_array($item['live_status'], [101])) {
                    $live_status = 2;
                } elseif (in_array($item['live_status'], [102])) {
                    $live_status = 1;
                } elseif (in_array($item['live_status'], [103, 107])) {
                    $live_status = 3;
                } elseif (in_array($item['live_status'], [104])) {
                    $live_status = -1;
                }
                if (!empty($live_status)) {
                    $saveData = array();
                    $saveData['live_status'] = $live_status;
                    $where = array();
                    $where['roomId'] = $item['roomid'];
                    $liveplayTab->where($where)->save($saveData);
                }
            }
        }
        return ['code' => 0, 'msg' => '成功', 'data' => []];
    }

    /**
     * 获取微信返回码-中文提示
     * array $data 微信返回数据
     * */
    public function getWxLiveplayErrmsg(array $data)
    {
        if (empty($data)) {
            return $data;
        }
        switch ($data['errcode']) {
            case -1:
                $data['errmsg'] = '主播微信号未实名认证';
                break;
            case 1:
                $data['errmsg'] = '未创建直播间';
                break;
            case 1003:
                $data['errmsg'] = '未创建直播间';
                break;
            case 47001:
                $data['errmsg'] = '未创建直播间';
                break;
            case 47001:
                $data['errmsg'] = '入参格式不符合规范';
                break;
            case 200002:
                $data['errmsg'] = '入参错误';
                break;
            case 300001:
                $data['errmsg'] = '禁止创建/更新商品 或 禁止编辑&更新房间';
                break;
            case 300002:
                $data['errmsg'] = '名称长度不符合规则';
                break;
            case 300006:
                $data['errmsg'] = '图片上传失败（如：mediaID过期）';
                break;
            case 300022:
                $data['errmsg'] = '此房间号不存在';
                break;
            case 300023:
                $data['errmsg'] = '房间状态 拦截（当前房间状态不允许此操作）';
                break;
            case 300024:
                $data['errmsg'] = '商品不存在';
                break;
            case 300025:
                $data['errmsg'] = '商品审核未通过';
                break;
            case 300026:
                $data['errmsg'] = '房间商品数量已经满额';
                break;
            case 300027:
                $data['errmsg'] = '导入商品失败';
                break;
            case 300028:
                $data['errmsg'] = '房间名称违规';
                break;
            case 300029:
                $data['errmsg'] = '主播昵称违规';
                break;
            case 300030:
                $data['errmsg'] = '主播微信号不合法';
                break;
            case 300031:
                $data['errmsg'] = '直播间封面图不合规';
                break;
            case 300032:
                $data['errmsg'] = '直播间分享图违规';
                break;
            case 300033:
                $data['errmsg'] = '添加商品超过直播间上限';
                break;
            case 300034:
                $data['errmsg'] = '主播微信昵称长度不符合要求';
                break;
            case 300035:
                $data['errmsg'] = '主播微信号不存在';
                break;
            case 300036:
                $data['errmsg'] = '主播微信号未实名认证';
                break;
        }
        return $data;
    }

    /**
     * 获取直播码
     * @param int $roomId 房间id
     * */
    public function getShareLiveplayCode($roomId)
    {
        $accessToken = $this->accessToken['authorizer_access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$accessToken}";
        $params = [];
        $params['width'] = 300;
        $params['path'] = "plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id={$roomId}&type=9";
        $data = curlRequest($url, json_encode($params), 1, 1, 1);
        if (empty($data)) {
            $returnData = returnData(false, -1, 'error', '获取失败');
            return $returnData;
        }
        $file_content = $data;
        $save_to = $_SERVER['DOCUMENT_ROOT'] . "/Apps/Runtime/TempMedia";
        if (!file_exists($save_to)) {
            @mkdir($save_to, 0777);
        }
        $filename = md5(uniqid() . time() . mt_rand(10, 99)) . ".png";
        $save_to .= "/{$filename}";
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
        $result_new = uploadQiniuPic($save_to, $filename);
        $image = '';
        if ($result_new['code'] == 0) {
            $image = 'qiniu://' . $result_new['data']['key'];
        }
        unlink($save_to);
        return returnData(['shareImgUrl' => $image]);
    }
}
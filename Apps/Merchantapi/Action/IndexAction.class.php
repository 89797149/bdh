<?php

namespace Merchantapi\Action;
/**
 * ============================================================================
 *NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 首页控制器
 */
class IndexAction extends BaseAction
{
    /**
     * 获取首页信息
     *
     */
    public function index()
    {
        //dump($GLOBALS);exit;
        //dump(session("areaId2"));
        $ads = D('Home/Ads');
        $areaId2 = $this->getDefaultCity();
        //获取分类
        $gcm = D('Home/GoodsCats');
        $catList = $gcm->getGoodsCatsAndGoodsForIndex($areaId2);
        $this->assign('catList', $catList);
        //分类广告
        $catAds = $ads->getAdsByCat($areaId2);
        $this->assign('catAds', $catAds);
        $this->assign('ishome', 1);
        if (I("changeCity")) {
            echo $_SERVER['HTTP_REFERER'];
        } else {
            $this->display("default/index");
        }

    }

    /**
     * 广告记数
     */
    public function access()
    {
        $ads = D('Home/Ads');
        $ads->statistics((int)I('id'));
    }

    /**
     * 切换城市
     */
    public function changeCity()
    {
        $m = D('Home/Areas');
        $areaId2 = $this->getDefaultCity();
        $provinceList = $m->getProvinceList();
        $cityList = $m->getCityGroupByKey();
        $area = $m->getArea($areaId2);
        $this->assign('provinceList', $provinceList);
        $this->assign('cityList', $cityList);
        $this->assign('area', $area);
        $this->assign('areaId2', $areaId2);
        //echo json_encode($provinceList);
        //echo json_encode($cityList);
        //echo json_encode($area);
        //exit();
        $this->display("default/change_city");
    }

    /**
     * 跳到用户注册协议
     */
    public function toUserProtocol()
    {
        $this->display("default/user_protocol");
    }

    /**
     * 修改切换城市ID
     */
    public function reChangeCity()
    {
        $this->getDefaultCity();
    }

    /**
     * 发送请求
     * @param string url
     * @param string param
     * @param int ispost PS:(0=>'get',1=>'post')
     * @param string printContent PS:打印内容
     */
    public function curlRequest()
    {
        $url = I('url');
        $ispost = I('ispost', 0);
        $urlArr = explode(':', $url);
        if ($urlArr[0] == 'https') {
            $https = 1;
        } else {
            $https = 0;
        }

        if ($ispost == 1) {
            $xml_parser = xml_parser_create();
            if (xml_parse($xml_parser, $_POST['param'], true)) {
                //$param = xmlToArray($_POST['param']);
                $param = $_POST['param'];
            } else {
                $param = json_decode($_POST['param'], true);
            }
        } else {
            $param = false;
        }
        if ($param['orderNo'] != '*') {
            $logInfo = M("print_log")->where(["orderNo" => $param['orderNo']])->find();
            if ($logInfo) {
                $res = [
                    "responseCode" => -1,
                    "msg" => "订单不能重复打印",
                ];
                //如果订单已打印但未受理,则把状态改为已受理
                $orderWhere = [];
                $orderWhere['orderNo'] = $param['orderNo'];
                $orderWhere['orderFlag'] = 1;
                $orderInfo = M('orders')->where($orderWhere)->find();
                if ($orderInfo["orderStatus"] == 0 || $orderInfo["orderStatus"] == 13 || $orderInfo["orderStatus"] == 14) {
                    $sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderId = '" . $orderInfo['orderId'] . "' ";
                    $this->execute($sql);
                }
                $this->ajaxReturn($res);
            }
        }
        if (xml_parse($xml_parser, $_POST['param'], true)) {
            $res = xmlToArray(curlRequest($url, $param, $ispost, $https));
        } else {
            $param['printContent'] = htmlspecialchars_decode(I('printContent'));
            $orderNo = $param['orderNo'];
            unset($param['orderNo']);
            //$res = json_decode(curlRequest($url,$param,$ispost,$https));
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded ",
                    'method' => 'POST',
                    'content' => http_build_query($param),
                ),
            );
            $context = stream_context_create($options);
            $res = json_decode(file_get_contents($url, false, $context), true);
            if ($res['responseCode'] == 0 && $orderNo != '*') {
                $insertLog['orderNo'] = $orderNo;
                $insertLog['addTime'] = date("Y-m-d H:i:s", time());
                M("print_log")->add($insertLog);
            }
        }
        $this->ajaxReturn($res);
    }

    /**
     * 发送请求
     * @param string url
     * @param string param
     * @param int ispost PS:(0=>'get',1=>'post')
     * @param string printContent PS:打印内容
     */
    public function curlRequestTwo()
    {
        $url = I('url');
        $ispost = I('ispost', 0);
        $urlArr = explode(':', $url);
        if ($urlArr[0] == 'https') {
            $https = 1;
        } else {
            $https = 0;
        }

        if ($ispost == 1) {
            $xml_parser = xml_parser_create();
            if (xml_parse($xml_parser, $_POST['param'], true)) {
                //$param = xmlToArray($_POST['param']);
                $param = $_POST['param'];
            } else {
                $param = json_decode($_POST['param'], true);
            }
        } else {
            $param = false;
        }
        if ($param['orderNo'] != '*') {
            $logInfo = M("print_log")->where(["orderNo" => $param['orderNo']])->find();
            if ($logInfo) {
//                $res = [
//                    "responseCode" => -1,
//                    "msg" => "订单不能重复打印",
//                ];
//                $this->ajaxReturn($res);
                $this->ajaxReturn(returnData(false, -1, 'error', '订单不能重复打印'));
                //去掉提示
//                $this->ajaxReturn(returnData(array(
//                    'orderindex' => ''
//                )));
            }
        }
        if (xml_parse($xml_parser, $_POST['param'], true)) {
            $res = xmlToArray(curlRequest($url, $param, $ispost, $https));
        } else {
            $param['printContent'] = htmlspecialchars_decode(I('printContent'));
            $orderNo = $param['orderNo'];
            unset($param['orderNo']);
            //$res = json_decode(curlRequest($url,$param,$ispost,$https));
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded ",
                    'method' => 'POST',
                    'content' => http_build_query($param),
                ),
            );
            $context = stream_context_create($options);
            $res = json_decode(file_get_contents($url, false, $context), true);
            if ($res['responseCode'] == 0 && $orderNo != '*') {
                $insertLog['orderNo'] = $orderNo;
                $insertLog['addTime'] = date("Y-m-d H:i:s", time());
                M("print_log")->add($insertLog);
            }
        }
        $data = [
            'orderindex' => $res['orderindex']
        ];
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 发送打印内容
     */
    public function printcenter()
    {
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

        $str = json_decode(base64_decode($str), true);
        $url = I('url');


        // $this->ajaxReturn($str);


        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded",
                'method' => 'POST',
                'content' => http_build_query($str),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $this->ajaxReturn(json_decode($result, true));
    }

}
<?php
header("Content-type: text/html; charset=utf-8");
//echo "<meta http-equiv='refresh' content='10'>";//
//include "./TopSdk.php";
//include "./top/TopClient.php";
//include "./top/request/AlibabaAliqinFcSmsNumSendRequest.php";
include "./Autoloader.php";

//将下载SDK解压后top里的TopClient.php第8行$gatewayUrl的值改为沙箱地址:http://gw.api.tbsandbox.com/router/rest,

//正式环境时需要将该地址设置为:http://gw.api.taobao.com/router/rest

$c = new TopClient;
$c->appkey = '23290340';//appkey
$c->secretKey = 'aa36aacc5bcb54d87bbad8c915081733';//secretKey
$req = new AlibabaAliqinFcSmsNumSendRequest;
$req->setExtend("1111");//消息返回”中会透传回该参
$req->setSmsType("normal");//短信类型，传入值请填写normal
$req->setSmsFreeSignName("注册验证");//短信签名，传入的短信签名必须是在阿里大鱼“管理中心-短信签名管理”中的可用签名
$req->setSmsParam("{'code':'7758258','product':'辉哥奴隶'}");//短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。示例：针对模板“验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”，传参时需传入{"code":"1234","product":"alidayu"}
$req->setRecNum("18017156561");//接受人手机号
$req->setSmsTemplateCode("SMS_3925176");//短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。示例：SMS_585014
$resp = $c->execute($req);
//var_dump($resp);
echo "result:";
print_r($resp);
?>
<?php
/**
 * 获取指定位置的导航菜单
 * @param int $type 导航位置
 */
function WSTNavigation($type=0){
	$URL_HTML_SUFFIX = C('URL_HTML_SUFFIX');
	$cururl =  U(MODULE_NAME."/".CONTROLLER_NAME."/".ACTION_NAME);
	$cururl = str_ireplace(".".$URL_HTML_SUFFIX,'',$cururl);
	$areaId2 = (int)session('areaId2');
	$rs = F('navigation/'.$areaId2);
	if(!$rs){
		$m = M();
		//获取所在省份
	    $sql = "select parentId from __PREFIX__areas where areaId=".$areaId2;
		$areaId1Rs = $m->query($sql);
		$areaId1 = (int)$areaId1Rs[0]['parentId'];
		$sql = "select navType,navTitle,navUrl,isShow,isOpen 
		  from __PREFIX__navs where isShow=1 and (areaId1=0 or areaId1=".$areaId1.") and (areaId2=0 or areaId2=".$areaId2.") 
		  order by navType asc,navSort asc";
	    $rs = $m->query($sql);
	    F('navigation/'.$areaId2,$rs);
	}
	foreach ($rs as $key =>$v){
		$rs[$key]['url'] = $cururl;
		if(stripos($v['navUrl'],'https://')===false &&  stripos($v['navUrl'],'http://')===false){
			$rs[$key]['navUrl'] = WSTDomain()."/".$rs[$key]['navUrl'];
		}
		$rs[$key]['active'] = (stripos($rs[$key]['navUrl'],$cururl)!==false)?1:0;
		$rs[$key]['end'] = ($key==count($rs)-1)?1:0;
	}
	//分组
	$data = array();
	foreach ($rs as $key =>$v){
		$data[$v['navType']][] = $v;
	}
	return $data[$type];
}

/**
 * 货币枨式化
 * @param unknown $number
 */
function WSTMoney($number,$lc="en_US"){
	setlocale(LC_MONETARY, $lc);
	return money_format("%=*(#10.2n", $number);
}

/**
 * 获取首页商品分类列表
 */
function WSTGoodsCats(){
    $cats = S("WST_CACHE_GOODS_CAT_WEB");
	if(!$cats){
		$m = M();
		$sql = "select catId,catName from __PREFIX__goods_cats WHERE parentId = 0 AND isShow =1 AND catFlag = 1 order by catSort asc";
		$rs1 = $m->query($sql);
		$cats = array();
		for ($i = 0; $i < count($rs1); $i++) {
			$cat1Id = $rs1[$i]["catId"];
			$sql = "select catId,catName from __PREFIX__goods_cats WHERE parentId = $cat1Id AND isShow =1 AND catFlag = 1 order by catSort asc";
			$rs2 = $m->query($sql);
			$cats2 = array();
			for ($j = 0; $j < count($rs2); $j++) {
				$cat2Id = $rs2[$j]["catId"];
				$sql = "select catId,catName from __PREFIX__goods_cats WHERE parentId = $cat2Id AND isShow =1 AND catFlag = 1 order by catSort asc";
				$rs3 = $m->query($sql);
				$cats3 = array();
				for ($k = 0; $k < count($rs3); $k++) {
					$cats3[] = $rs3[$k];
				}
				$rs2[$j]["catChildren"] = $cats3;
				$cats2[] = $rs2[$j];
			}
			$rs1[$i]["catChildren"] = $cats2;
			$cats[] = $rs1[$i];
		}
		S("WST_CACHE_GOODS_CAT_WEB",$cats,31536000);
	}
	return $cats;
}

/**
 * 获取购物车数量
 */
function WSTCartNum(){
	$m = M();
	$userId = session('WST_USER.userId');
	$sql = "select count(*) cnt from __PREFIX__cart where userId=$userId";
	$rows = $m->query($sql);
	$count = $rows[0]["cnt"]?$rows[0]["cnt"]:0;
	return $count;
}

/**
 *API 获取广告列表
 */
function WSTAds($areaId2,$type){
	$ads = D('Mendianapi/Api');
	return $ads->getAds($areaId2,$type);
}


function WSTOrderScore(){
	$scoreCashRatio = $GLOBALS['CONFIG']["scoreCashRatio"];
	$scoreCash = explode(":",$scoreCashRatio);
	return (int)$scoreCash[0];
}

function WSTScoreMoney(){
	$scoreCashRatio = $GLOBALS['CONFIG']["scoreCashRatio"];
	$scoreCash = explode(":",$scoreCashRatio);
	return (int)$scoreCash[1];
}


/**
 * 获取当前用户对像
 */
function WSTTarget(){
	$target = array();
	$targetId = (int)session('WST_USER.userId');
	$targetType = $targetId>0?0:-1;
	if(!$targetId){
		$targetId = (int)session('WST_USER.shopId');
		$targetType = $targetId>0?1:-1;
	}
	$target["targetId"] = $targetId;
	$target["targetType"] = $targetType;
	return $target;
}


//API接口token生成
function Niao_token($token){
	$md5 = C("api_md5");
	$url = NiaoRootDomain();
	$str1 = $md5.$url;
	$str2 = $url.$md5;
	$niao_token = md5($str1.$str2);
	if($token == $niao_token){
		return true;
	}else{
		return false;
	}

}

//数组去重复
function FetchRepeatMemberInArray($array) { 
    // 获取去掉重复数据的数组 
    $unique_arr = array_unique ($array); 
    // 获取重复数据的数组
    $repeat_arr = array_diff_assoc ($array, $unique_arr); 
    return $repeat_arr; 
}

//微信xml解析
function WxXmlToArray($xml){
 //禁止引用外部xml实体 
libxml_disable_entity_loader(true); 
 
$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA); 
 
$val = json_decode(json_encode($xmlstring),true); 
 
return $val; 
}

//数据加密
function strencode($string) {
    return $string;
    /*$string = base64_encode ($string);
    $key = md5 (C("data_key"));   
    $len = strlen ( $key );
    $code = '';   
    for($i = 0; $i < strlen ( $string ); $i ++) {       
 $k = $i % $len;       
 $code .= $string [$i] ^ $key [$k];   
    }   
    return base64_encode ( $code );*/
}

//删除二维数组中相同元素及数组重复值
function assoc_title($arr, $key)
{
  $tmp_arr = array();
  foreach ($arr as $k => $v) {
    if (in_array($v[$key], $tmp_arr)) {
      unset($arr[$k]);
    } else {
      $tmp_arr[] = $v[$key];
    }
  }
  return $arr;
}



<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 基础服务类
 */
use Think\Model;
class BaseModel extends Model {
    /**
     * 用来处理内容中为空的判断
     */
    public function checkEmpty($data,$isDie = false){
        foreach ($data as $key=>$v){
            if(trim($v)===''){
//				if($isDie)die("{'status':-1,'key':'$key'}");
                $msg = $this->statusName($key).' 不能为空';
                // if($isDie)die("{'code':-1,'msg':".$msg."}");
                if($isDie){
                    //$arr = array('code'=>-1,'msg'=>$msg);
                    $arr = returnData(false, -1, 'error', $msg);
                    echo json_encode($arr);
                    die();
                }
                return false;
            }
        }
        return true;
    }

    public function statusName($key){
        $statusName = '';
        if(!empty($key)){
            $statusName = [];
            $statusName['loginPwd'] = '登录密码';
            $statusName['loginName'] = '登录账号';
            $statusName['shopSn'] = '门店编号';
            $statusName['areaId1'] = '省';
            $statusName['areaId2'] = '市';
            $statusName['areaId3'] = '区';
            $statusName['goodsCatId1'] = '所属行业';
            $statusName['shopName'] = '店铺名称';
            $statusName['shopCompany'] = '公司名称';
            $statusName['shopImg'] = '门店图标';
            $statusName['shopAddress'] = '门店地址';
            $statusName['bankId'] = '银行';
            $statusName['bankNo'] = '银行卡号';
            $statusName['bankUserName'] = '银行卡所有人';
            $statusName['serviceStartTime'] = '开始营业时间';
            $statusName['serviceEndTime'] = '结束营业时间';
            $statusName['shopTel'] = '店铺电话';
            $statusName['commissionRate'] = '订单佣金比例';
            $statusName['longitude'] = '店铺经纬度';
            $statusName['latitude'] = '店铺经纬度';
            $statusName['statusRemarks'] = '拒绝原因';
            $statusName = $statusName[$key];
        }
        return (string)$statusName;
    }

	/**
	 * 输入sql调试信息
	 */
	public function logSql($m){
		echo $m->getLastSql();
	}
    /**
	 * 获取一行记录
	 */
	public function queryRow($sql){
		$plist = $this->query($sql);
		return empty($plist)?array():$plist[0];
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
};
?>
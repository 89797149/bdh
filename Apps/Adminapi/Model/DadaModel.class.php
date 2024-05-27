<?php
namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 物流服务类
 */
class DadaModel extends BaseModel {

	private $DadaOpenapiUrl;
	private $dadaAppKey;
	private $dadaAppSecret;
	
 	public function __construct(){
		$this->DadaOpenapiUrl = $GLOBALS['CONFIG']['dada_url'];
		$this->dadaAppKey = $GLOBALS['CONFIG']['dadaAppKey'];
		$this->dadaAppSecret = $GLOBALS['CONFIG']['dadaAppSecret'];
	}
	
		/**
	 * 数组 转 对象
	 *
	 * @param array $arr 数组
	 * @return object
	 */
	public function array_to_object($arr) {
		if (gettype($arr) != 'array') {
			return;
		}
		foreach ($arr as $k => $v) {
			if (gettype($v) == 'array' || getType($v) == 'object') {
				$arr[$k] = (object)array_to_object($v);
			}
		}
	 
		return (object)$arr;
	}
	 
	/**
	 * 对象 转 数组
	 *
	 * @param object $obj 对象
	 * @return array
	 */
	public function object_to_array($array) {
		if(is_object($array)) {    
			$array = (array)$array;    
		 } if(is_array($array)) {    
			 foreach($array as $key=>$value) {    
				 $array[$key] = $this->object_to_array($value);    
				 }    
		 }    
		 return $array;    
}

    /**
     * 注册商户
     */
	public function merchantAdd($data){
		$config = array();
		$config['app_key'] = $this->dadaAppKey;
		$config['app_secret'] = $this->dadaAppSecret;
		$config['url'] = $this->DadaOpenapiUrl."merchantApi/merchant/add";
		$config['source_id'] = '';
		$DadaOpenapi = new \Org\Util\DadaOpenapi($config);
		
		$reqStatus = $DadaOpenapi->makeRequest($data);
		
		if (!$reqStatus) {
			
			//接口请求正常，判断接口返回的结果，自定义业务操作
			if ($DadaOpenapi->getCode() == 0) {
				
				return $DadaOpenapi->getResult();
				//echo "成功";
				//返回成功 ....
			}else{
				//exit(sprintf('code:%s，msg:%s',$DadaOpenapi->getCode(), $DadaOpenapi->getMsg()));

				$data['niaocmsstatic']=1;//错误
				$data['info'] = $DadaOpenapi->getMsg();
				return $data;

			}
			
		}else{
			//请求异常或者失败
			$resdata['static'] = 'except';
			return $resdata;
		}
		
		
	}
	
	 /**
     * 新增门店
     */
	public function apiShopAdd($data,$source_id){
		$config = array();
		$config['app_key'] = $this->dadaAppKey;
		$config['app_secret'] = $this->dadaAppSecret;
		$config['url'] = $this->DadaOpenapiUrl."/api/shop/add";
		$config['source_id'] = $source_id;
		$DadaOpenapi = new \Org\Util\DadaOpenapi($config);
		
		$reqStatus = $DadaOpenapi->makeRequest($data);
		
		if (!$reqStatus) {
			
			//接口请求正常，判断接口返回的结果，自定义业务操作
			if ($DadaOpenapi->getCode() == 0) {

				return $DadaOpenapi->getResult();

			}else{
				
				
				$data['niaocmsstatic']=1;//错误
				$data['info'] = $DadaOpenapi->getMsg();
				return $data;

			}
			
		}else{
			//请求异常或者失败
			return 'except';
		}
		
		
	}

    /**
     * 更新门店
     */
    public function apiShopUpdate($data,$source_id){
        $config = array();
        $config['app_key'] = $this->dadaAppKey;
        $config['app_secret'] = $this->dadaAppSecret;
        $config['url'] = $this->DadaOpenapiUrl."/api/shop/update";
        $config['source_id'] = $source_id;
        $DadaOpenapi = new \Org\Util\DadaOpenapi($config);

        $reqStatus = $DadaOpenapi->makeRequest($data);

        if (!$reqStatus) {

            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($DadaOpenapi->getCode() == 0) {

                return $DadaOpenapi->getResult();

            }else{

                //echo sprintf('code:%s，msg:%s',$DadaOpenapi->getCode(), $DadaOpenapi->getMsg()); //调试异常的时候 可以开启
                $data['niaocmsstatic']=1;//错误
                $data['info'] = $DadaOpenapi->getMsg();
                return $data;
            }

        }else{
            //请求异常或者失败
            return 'except';
        }


    }
	
	/**
     * 获取城市信息
     */
	public function cityCodeList($data,$source_id){
		$config = array();
		$config['app_key'] = $this->dadaAppKey;
		$config['app_secret'] = $this->dadaAppSecret;
		$config['url'] = $this->DadaOpenapiUrl."/api/cityCode/list";
		$config['source_id'] = $source_id;
		$DadaOpenapi = new \Org\Util\DadaOpenapi($config);
		
		$reqStatus = $DadaOpenapi->makeRequest($data);
		
		if (!$reqStatus) {
			
			//接口请求正常，判断接口返回的结果，自定义业务操作
			if ($DadaOpenapi->getCode() == 0) {
				
				return $DadaOpenapi->getResult();

			}else{
				
				//echo sprintf('code:%s，msg:%s',$DadaOpenapi->getCode(), $DadaOpenapi->getMsg()); //调试异常的时候 可以开启
				$data['niaocmsstatic']=1;//错误
				$data['info'] = $DadaOpenapi->getMsg();
				return $data;
			}
			
		}else{
			//请求异常或者失败
			return 'except';
		}
		
		
	}
	
	/**
     * 订单重发
     */
	public function reAddOrder($data,$source_id){
		$config = array();
		$config['app_key'] = $this->dadaAppKey;
		$config['app_secret'] = $this->dadaAppSecret;
		$config['url'] = $this->DadaOpenapiUrl."/api/order/reAddOrder";
		$config['source_id'] = $source_id;
		$DadaOpenapi = new \Org\Util\DadaOpenapi($config);
		
		$reqStatus = $DadaOpenapi->makeRequest($data);
		
		if (!$reqStatus) {
			
			//接口请求正常，判断接口返回的结果，自定义业务操作
			if ($DadaOpenapi->getCode() == 0) {
				
				return $DadaOpenapi->getResult();

			}else{
				
				//echo sprintf('code:%s，msg:%s',$DadaOpenapi->getCode(), $DadaOpenapi->getMsg()); //调试异常的时候 可以开启
				$data['niaocmsstatic']=1;//错误
				$data['info'] = $DadaOpenapi->getMsg();
				return $data;
			}
			
		}else{
			//请求异常或者失败
			return 'except';
		}
		
		
	}
	
	
	/**
     * 查询订单运费
     */
	public function queryDeliverFee($data,$source_id){
		$config = array();
		$config['app_key'] = $this->dadaAppKey;
		$config['app_secret'] = $this->dadaAppSecret;
		$config['url'] = $this->DadaOpenapiUrl."/api/order/queryDeliverFee";
		$config['source_id'] = $source_id;
		$DadaOpenapi = new \Org\Util\DadaOpenapi($config);
		
		$reqStatus = $DadaOpenapi->makeRequest($data);
		
		if (!$reqStatus) {
			
			//接口请求正常，判断接口返回的结果，自定义业务操作
			if ($DadaOpenapi->getCode() == 0) {

				return $DadaOpenapi->getResult();

			}else{
				
				
				$data['niaocmsstatic']=1;//错误
				$data['info'] = $DadaOpenapi->getMsg();
				return $data;

			}
			
		}else{
			//请求异常或者失败
			return 'except';
		}
		
		
	}
	
	
}
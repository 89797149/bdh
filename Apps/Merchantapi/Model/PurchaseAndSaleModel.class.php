<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 *
 */
class PurchaseAndSaleModel extends BaseModel {
    /**
     *商家注册云仓账号
     * @param string token
     * request:
     * @param string username 用户名称
     * @param string userpwd 用户密码
     * @param string name 真实姓名
     * @param string mobile 手机号
     */
    public function registerUser($shopId,$request){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($request)){
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/registerUser";
            $res = curlRequest($openApiUrl,$request,true);
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                //注册成功后更新shop_config
                $shopWhere['shopId'] = $shopId;
                $shopParams['cloudAccount'] = $request['username'];
                $shopParams['cloudPwd'] = $request['userpwd'];
                M('shop_configs')->where($shopWhere)->save($shopParams);
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }
        }
        return $apiRet;
    }

    /**
     *获取云仓账号
     */
    public function getAdminUser($shopId){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        if(!$shopConfig){
            $apiRet['apiInfo'] = '您还未设置云仓账号';
            return $apiRet;
        }
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getAdminUser";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     *更新云仓账号
     * @param string token
     * @param string username 用户名称 (用户名称不能更改)
     * @param string userpwd 用户密码
     * @param string name 真实姓名
     * @param string mobile 手机号
     */
    public function updateAdminUser($shopId,$request){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($request)){
            $shopConfig = checkCloudAccount($shopId);
            if(!$shopConfig){
                $apiRet['apiInfo'] = '您还未设置云仓账号';
                return $apiRet;
            }
            $request['username'] = $shopConfig['cloudAccount'];
            $request['userpwd'] = $shopConfig['cloudPwd'];
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/updateAdminUser";
            $res = curlRequest($openApiUrl,$request,true);
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }
        }
        return $apiRet;
    }

    /**
     *商家添加仓库(一个商家对应一个仓库)
     */
    public function insertStorage($shopId,$request){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($request)){
            $shopConfig = checkCloudAccount($shopId);
            if(!$shopConfig){
                $apiRet['apiInfo'] = '您还未设置云仓账号';
                return $apiRet;
            }
            $request['username'] = $shopConfig['cloudAccount'];
            $request['userpwd'] = $shopConfig['cloudPwd'];
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/insertStorage";
            $res = curlRequest($openApiUrl,$request,true);
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }else{
                $apiRet['apiInfo'] = $res['apiInfo'];
            }
        }
        return $apiRet;
    }

    /**
     *编辑仓库
     */
    public function updateStorage($shopId,$request){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($request)){
            $shopConfig = checkCloudAccount($shopId);
            if(!$shopConfig){
                $apiRet['apiInfo'] = '您还未设置云仓账号';
                return $apiRet;
            }
            $request['username'] = $shopConfig['cloudAccount'];
            $request['userpwd'] = $shopConfig['cloudPwd'];
            $storageInfo = $this->getStorage($shopId)['apiData'][0];
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/updateStorage";
            $request['id'] = $storageInfo['id'];
            $res = curlRequest($openApiUrl,$request,true);
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }else{
                $apiRet['apiInfo'] = $res['apiInfo'];
            }
        }
        return $apiRet;
    }


    /**
     *获取仓库
     */
    public function getStorage($shopId){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        if(!$shopConfig){
            $apiRet['apiInfo'] = '您还未设置云仓账号';
            return $apiRet;
        }
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getStorage";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     *获取仓库列表
     */
    public function getAllStorage($shopId,$page){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['page'] = $page;
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getAllStorage";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     * 商家生成调拨单
     */
    public function transferSlip($shopId,$data){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($data)){
            $shopConfig = checkCloudAccount($shopId);
            $request['username'] = $shopConfig['cloudAccount'];
            $request['userpwd'] = $shopConfig['cloudPwd'];
            $goods = json_decode($data['goodsId'],true);
            if(empty($goods)){
                $apiRet['apiInfo'] = '商品数据不正确';
                return $apiRet;
            }
            $nums = 0; //商品总数
            foreach ($goods as $val){
                $goodsId[] = $val['goodsId'];
                $nums += $val['nums'];
            }
            $goodsWhere['goodsId'] = ['IN',$goodsId];
            $field = [
                'goodsId',
                'goodsSn',
                'goodsName',
            ];
            $goodsList = M('goods')->where($goodsWhere)->field($field)->select();
            foreach ($goods as $key=>$val){
                foreach ($goodsList as $gv){
                    if($gv['goodsId'] == $val['goodsId']){
                        $goods[$key]['goodsSn'] = $gv['goodsSn'];
                        $goods[$key]['goodsName'] = $gv['goodsName'];
                        $goods[$key]['qty'] = $val['nums'];
                    }
                }
            }
            $request['goodsList'] = json_encode($goods);
            $request['description'] = $data['description'];
            $request['goodsTotalNums'] = $nums;
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/transferSlip";
            $res = curlRequest($openApiUrl,$request,true);//生成调拨单
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = '操作成功';
                $apiRet['apiState'] = 'success';
            }else{
                $apiRet['apiInfo'] = $res['apiInfo'];
            }
        }
        return $apiRet;
    }

    /**
     *获取商家调拨单
     */
    public function getTransferSlip($shopId,$page){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['page'] = $page;
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getTransferSlip";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     * 验证商品
     */
    public function checkGoodsState($shopId,$data){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        if(!empty($data)){
            $shopConfig = checkCloudAccount($shopId);
            $request['username'] = $shopConfig['cloudAccount'];
            $request['userpwd'] = $shopConfig['cloudPwd'];
            $goods = json_decode($data['goodsInfo'],true);
            if(empty($goods)){
                $apiRet['apiInfo'] = '商品数据不正确';
                return $apiRet;
            }
            foreach ($goods as $val){
                $goodsId[] = $val['goodsId'];
            }
            $goodsWhere['goodsId'] = ['IN',$goodsId];
            $goodsList = M('goods')->where($goodsWhere)->field('goodsId,goodsSn,goodsName')->select();
            foreach ($goods as $key=>$val){
                foreach ($goodsList as $v){
                    if($v['goodsId'] == $val['goodsId']){
                        $goods[$key]['goodsSn'] = $v['goodsSn'];
                    }
                }
            }
            $request['goodsList'] = json_encode($goods);
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/checkGoodsState";
            $res = curlRequest($openApiUrl,$request,true);//校验商品
            $res = json_decode($res,true);
            if($res['apiCode'] == 0){
                $apiRet['apiCode'] = '0';
                $apiRet['apiInfo'] = $res['apiInfo'];
                $apiRet['apiState'] = 'success';
            }else{
                $apiRet['apiInfo'] = $res['apiInfo'];
            }
        }
        return $apiRet;
    }

    /**
     *获取供应商
     */
    public function getAllContact($shopId,$page){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['page'] = $page;
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getAllContact";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     *商家生成采购单
     */
    public function purchasingOrder($shopId,$data){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['description'] = $data['description'];
        $request['buId'] = $data['contactId'];
        $request['contactName'] = $data['contactName'];
        $goods = json_decode($data['goods'],true);
        if(empty($goods)){
            $apiRet['apiInfo'] = '商品信息不正确';
            return $apiRet;
        }
        $totalQty = 0;
        $totalAmount = 0;
        foreach ($goods as $key=>$val){
            $goodsId[] = $val['goodsId'];
            $totalQty += $val['nums'];
        }
        $goodsWhere['goodsId'] = ["IN",$goodsId];
        $goodsList = M('goods')->where($goodsWhere)->field('goodsId,goodsSn,goodsName,shopPrice,goodsUnit')->select();
        foreach ($goods as $key=>$val){
            foreach ($goodsList as $gv){
                if($gv['goodsId'] == $val['goodsId']){
                    $goods[$key]['goodsName'] = $gv['goodsName'];
                    $goods[$key]['goodsSn'] = $gv['goodsSn'];
                    $goods[$key]['shopPrice'] = $gv['shopPrice'];
                    $goods[$key]['unitName'] = $gv['unitName'];
                    $totalAmount += $goods[$key]['nums'] * $goods[$key]['shopPrice'];
                }
            }
        }
        $request['totalQty'] = $totalQty;
        $request['totalAmount'] = $totalAmount;
        $request['goodsList'] = json_encode($goods);
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/purchasingOrder";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '操作成功';
            $apiRet['apiState'] = 'success';
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     *获取商家购货单
     */
    public function getPurchasingOrder($shopId,$page){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '获取失败';
        $apiRet['apiState'] = 'error';
        $shopConfig = checkCloudAccount($shopId);
        $request['username'] = $shopConfig['cloudAccount'];
        $request['userpwd'] = $shopConfig['cloudPwd'];
        $request['page'] = $page;
        $openApiUrl = C('OPEN_API')."/index.php/OpenApi/getPurchasingOrder";
        $res = curlRequest($openApiUrl,$request,true);
        $res = json_decode($res,true);
        if($res['apiCode'] == 0){
            $apiRet['apiCode'] = '0';
            $apiRet['apiInfo'] = '获取成功';
            $apiRet['apiState'] = 'success';
            $apiRet['apiData'] = $res['apiData'];
        }else{
            $apiRet['apiInfo'] = $res['apiInfo'];
        }
        return $apiRet;
    }

    /**
     *同步云端库存
     * @param string propertys 例子:[{"locationId":5,"quantity":"1000.0","unitCost":1000,"amount":1000000,"batch":"","prodDate":"","safeDays":"","validDate":"","id":"40"},{"locationId":5,"quantity":"11111.0","unitCost":1111,"amount":12344321,"batch":"","prodDate":"","safeDays":"","validDate":"","id":"41"}]
     */
    public function synchronizationGoodsStock(){
        $apiRet['apiCode'] = '-1';
        $apiRet['apiInfo'] = '操作失败';
        $apiRet['apiState'] = 'error';
        $shopWhere['shopStatus'] = 1;
        $shopWhere['shopFlag'] = 1;
        $shopField = [
            'shopId',
            'shopName',
        ];
        $shops = M('shops')->where($shopWhere)->field($shopField)->select();
        if(count($shops) > 0 ){
            foreach ($shops as $val){
                $shopId[] = $val['shopId'];
            }
            $goodsWhere['shopId'] = ["IN",$shopId];
            $goodsWhere['goodsFlag'] = 1;
            $goodsField = [
                'goodsId',
                'shopId',
                'goodsSn',
                'goodsName',
                'goodsStock',
            ];
            $goodsList = M('goods')->where($goodsWhere)->field($goodsField)->select();
            foreach ($shops as $key=>$val){
                $shops[$key]['goods'] = [];
                foreach ($goodsList as $gk=>$gv){
                    if($gv['shopId'] == $val['shopId']){
                        $shops[$key]['goods'][] = $gv;
                    }
                }
            }

            $shopConfigTab = M('shop_configs');
            foreach ($shops as $key=>$val){
                $shopConfigInfo = $shopConfigTab->where(['shopId'=>$val['shopId']])->field('cloudAccount,cloudPwd')->find();
                if(count($val['goods']) > 0 && (!empty($shopConfigInfo['cloudAccount']) && !empty($shopConfigInfo['cloudPwd']))){
                    $shops[$key]['cloudAccount'] = $shopConfigInfo['cloudAccount'];
                    $shops[$key]['cloudPwd'] = $shopConfigInfo['cloudPwd'];
                }else{
                    unset($shops[$key]);
                }
            }
            $shops = array_values($shops);
            $request['shops'] = json_encode($shops);
            $openApiUrl = C('OPEN_API')."/index.php/OpenApi/synchronizationGoodsStock";
            $res = curlRequest($openApiUrl,$request,true);
            var_dump($res);exit;
        }
    }

}
?>
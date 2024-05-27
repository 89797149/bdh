<?php
namespace App\Modules\Pos;

use App\Models\BaseModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;

/**
 * DM促销方案服务类
 *
 * 这里只做逻辑 通过调用各module里的各个原子性函数实现完整的逻辑
 */
class PromotionDMServiceModule extends BaseModel
{

    // 检测所满足的方案并选择一个最优方案返回【获取最优优惠方案】 这个应该写到控制器里去调用编写逻辑
    // public function PreferentialScheme(){
        
    // }

    
    /**
     * 创建促销单
     */
    // public function createPromoteSalesScheme(){
        
    // }


    /**
      * 获取促销单列表|导出促销单
    */
    

    /**
      * 创建DM档期计划 
    */
    public function createSchedulePlan(){


    }

    /**
     * 删除DM档期计划
     */

     /**
     * 修改DM档期计划
     */

       /**
     * 查询DM档期计划
     */

     


    

    //TODO:考虑是否要支持提交pos单中记录本次优惠方案 对本次优惠方案进行冗余？后面记录上查账上 报表都可能需要体现的
    
    //TODO: 收银预提交订单接口 用于计算返回给前端购物车 需要新增接口 逻辑写在拆散在 PosOrdersModule 完整逻辑放到控制器
    //TODO: 检测商品状态 是否正常 例如 是否删除了 被删除的商品要给出报错 这个放到商品领域里 给其他地方调用即可 收银上可以不用管是否被下架 不过商品领域可以增加检测商品是否被下架前提是没被删除


}
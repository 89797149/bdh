<?php
namespace App\Modules\Pos;

use App\Enum\ExceptionCodeEnum;
use App\Models\BaseModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Shops\ShopCatsModule;
use Think\Model;


// 365wifi云打印58mm
class wifi365printModule  extends BaseModel{

    private $Appkey;
    
    private $Secret;

/**
 * 初始化打印机配置、这里不做任何事务处理 事务统一由外部处理
 *
 * @param [string] $Appkey
 * @param [string] $Secret
 */
public function __construct($Appkey,$Secret){
    if (empty($Appkey) || empty($Secret)) {
        throw new \Exception("Appkey 和 Secret 参数不能为空");
    }
    $this->Appkey=$Appkey;
    $this->Secret=$Secret;
}

/**
 * 发送模板打印
 *
 * @return void
 */
public function printTemplate(){
    return null;
}

/**
 * 获取打印机状态
 *
 * @return void
 */
public function getPrintStatus(){
    return null;
}

/**
 * 执行打印任务
 *
 * @return void
 */
public function runPrint(){
    return null;
}

/**
 * 获取打印结果/进程
 *
 * @return void
 */
public function getPrintStackStatus(){
    return null;
}

/**
 * 获取打印结果状态
 *
 * @return void
 */
public function getPrintOrderStatus(){
    return null;
}



}



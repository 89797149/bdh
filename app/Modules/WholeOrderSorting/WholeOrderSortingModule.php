<?php

namespace App\Modules\WholeOrderSorting;

use App\Models\OrdersModel;
use App\Models\whole_order_sorting_task;
use App\Models\OrderGoodsModel;
use App\Models\whole_order_sorting_goods;
use App\Modules\Goods\GoodsModule;
use App\Modules\ShopsModel;


class WholeOrderSortingModule
{

    //    获取可领取的订单列表
    public function GetOrderAvailableList(int $shop_id)
    {

//       TODO：摘果法和播种法 需要在开启分拣时设置分拣方式，可能部分业务在分配的时候会用到，比如开启摘果法将不会自动调度分拣员等逻辑

//        TODO：必须开启分拣才能获取到领取订单列表 且必须为 摘果法分拣

        $OrdersModel = new OrdersModel();
        $where = [];
        $where["whole_order_sorting_status"] = 1; //未领取分拣
        $where["orderStatus"] = array("in","1,2");//已受理
//        $where["orderStatus"] = 1;//已受理
        $where["shopId"] = $shop_id;
//        TODO：按临近期望送达时间进行排序
        return $OrdersModel->where($where)->select();
    }



//    领取分拣任务 领取成功后需要更新订单为打包中以及未领取分拣改为已领取分拣
//        TODO：必须开启分拣才能领取任务  且必须为 摘果法分拣
    public function ReceiveOrderTask(int $order_id,int $user_id,int $shop_id,string $order_no){

        if (empty($order_id)){
            if(empty($order_no)){
                $ok = false;
                $error = "订单id或订单号其一必填";
                return array($ok,$error);
            }
        }
        if (empty($user_id)){
            $ok = false;
            $error = "分拣员不存在";
            return array($ok,$error);
        }
        if (empty($shop_id)){
            $ok = false;
            $error = "店铺必填";
            return array($ok,$error);
        }

        $OrdersModel = new OrdersModel();
        if (empty($order_id)){
            if(!empty($order_no)){
//               通过订单号获取订单id
                $where["orderNo"] = $order_no;
                $order_info = $OrdersModel->where($where)->find();
                if (empty($order_info)){
                    $ok = false;
                    $error = "不存在的订单";
                    return array($ok,$error);
            }else{
                    $order_id = $order_info["orderId"];
                }
            }
        }


//        验证订单是否存在并获取订单信息

        $where["orderId"] = $order_id;
        $order_info = $OrdersModel->where($where)->find();
        if (empty($order_info)){
            $ok = false;
            $error = "不存在的订单";
            return array($ok,$error);
        }

//        校验订单是否已被领取
        if ($order_info['whole_order_sorting_status'] == 2){
            $ok = false;
            $error = "订单已被领取";
            return array($ok,$error);
        }

//        校验订单是否能被领取
        $whole_order_sorting_task =  new whole_order_sorting_task();
//        如果已存在任务中且task_status为正常的不允许再次领取

        $whole_order_sorting_task_info = $whole_order_sorting_task->where(array("order_id"=>$order_id,"task_status"=>1))->find();
       if(!empty($whole_order_sorting_task_info)){
           $ok = false;
           $error = "任务已被领取";
           return array($ok,$error);
       }

        $M = M();
        $M->startTrans();

//        领取订单-写入任务表
        $addData["order_id"] = $order_id;
        $addData["sorting_status"] = 1;
        $addData["order_time"] = $order_info["requireTime"]; //期望送达时间，用于排序，将送达时间距离当前时间最近的排列到最前
        $addData["task_status"] = 1;
        $addData["user_id"] = $user_id;
        $addData["addtime"] = date("Y-m-d H:i:s");
        $addData["is_delete"] = 1;
        $addData["order_no"] = $order_info["orderNo"];
        $addData["shop_id"] = $shop_id;
        $addDataId = $whole_order_sorting_task->add($addData);
        if (!$addDataId){
            $M->rollback();
            $ok = false;
            $error = "任务写入失败";
            return array($ok,$error);
        }

//        领取订单-写入任务商品表
//        查询订单商品
        $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
        $order_goods = (new OrderGoodsModel())->where(array("orderId"=>$order_id))->select();
        foreach($order_goods as $v){
//          新增任务商品
            $order_goods_add["whole_order_sorting_id"] = $addDataId;
            $order_goods_add["order_id"] = $v["orderId"];
            $order_goods_add["sku_id"] = $v["skuId"];
            $order_goods_add["goods_id"] = $v["goodsId"];
            $order_goods_add["piece_quantity"] = 0;
            $order_goods_add["goods_code"] = $v["goodsCode"];
            $whole_order_sorting_goods_mod->add($order_goods_add);
        }
//        更改订单为已领取
        $OrdersModel->where(array("orderId" => $order_id))->save(array("whole_order_sorting_status" => 2));

//        订单状态改为打包中
        $OrdersModel->where(array("orderId" => $order_id))->save(array("orderStatus" => 2));
        $M->commit();

        $ok = true;
        $error = null;
        return array($ok,$error);
    }



//    获取任务详情【商品信息】 对于需求数和已分拣数已返回，但是不建议前端使用，前端自行计算最好
public function GetTaskDetails(int $order_id,int $user_id){

    //        验证订单是否存在并获取订单信息
    $OrdersModel = new OrdersModel();
    $where["orderId"] = $order_id;
    $order_info = $OrdersModel->where($where)->find();
    if (empty($order_info)){
        $ok = false;
        $error = "不存在的订单";
        return array($ok,$error);
    }

//        获取任务信息 检测是否在任务中
    $whole_order_sorting_task =  new whole_order_sorting_task();
    $whole_order_sorting_taskInfo = $whole_order_sorting_task->where(array('order_id' => $order_id))->find();
    if (empty($whole_order_sorting_taskInfo)){
        $ok = false;
        $error = "不存在的任务";
        return array($ok,$error);
    }

//    检测是不是自己的任务
    if ((int)$whole_order_sorting_taskInfo["user_id"] != (int)$user_id){
        $ok = false;
        $error = "这可能不是你的任务";
        return array($ok,$error);
    }

    $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
    $whole_order_sorting_goods = $whole_order_sorting_goods_mod->order("piece_quantity","desc")->where(array("order_id"=>$order_id))->select();

    if (empty($whole_order_sorting_goods)){
        $ok = false;
        $error = "不存在的任务详情数据";
        return array($ok,$error);
    }

    $order_goods = (new OrderGoodsModel())->where(array("orderId"=>$order_id))->select();

//    合并商品对象 根据skuid goodsid
    $goods_array = array();
    foreach($whole_order_sorting_goods as $v){

        foreach($order_goods as $v2){
            if ((int)$v["goods_id"] == (int)$v2["goodsId"] and (int)$v["sku_id"] == (int)$v2["skuId"]){
                array_push($goods_array,array_merge($v2,$v));
            }
        }
    }

//    去重复
    $goods_array = arrayUnset($goods_array, "id");


//    对sku进行处理 拼接规格字符串【貌似已存在不用管】
//    foreach($goods_array as &$v){
//        $goods_module = new GoodsModule();
//        if ($v[""])
//        $sku_detail = $goods_module->getSkuSystemInfoById($sku_id, 2);
//    }


//   获取单位中文名 貌似自带中文名单位 不用单独处理

//    统计商品需求数
    $total_goods_num = 0;
    foreach($order_goods as $v){
        $total_goods_num+=(int)$v["goodsNums"];
    }
//    统计已分拣数
    $total_sortings_num = 0;
    foreach($whole_order_sorting_goods as $v){
        $total_sortings_num+=(int)$v["piece_quantity"];
    }

    $response = array();
    $response["需求数"] = $total_goods_num;
    $response["已分拣数"] = $total_sortings_num;
    $response["goods_list"] = (array)$goods_array;
    $response["order_info"] = (array)$order_info;


    $ok = $response;
    $error = null;
    return array($ok,$error);
}



//获取已领取的任务列表【分拣状态-待分拣、任务状态-正常】
public function MyTask(int $user_id){
    $whole_order_sorting_task =  new whole_order_sorting_task();
    $whole_order_sorting_task_list = $whole_order_sorting_task->where(array('user_id' => $user_id,'sorting_status'=>1,'task_status'=>1))->select();
    if (empty($whole_order_sorting_task_list)){
        return [];
    }
    return $whole_order_sorting_task_list;

}


// 更新分拣任务下某个商品已分拣数量。
// 用于实时变更某个任务下商品拣货数量 支持传入18位条码或商品编码/国条实现更新分拣数量
// $goods_code为18位称重条码或国条或普通编码
// $data_type【累加：+x、累减：-x、直接修改：x】比如扫码属于累加、手动输入直接修改、加减号属于累加或累减【注意：如果当前输入框数量大于1那么加减数值为1，如果小于1那么加减数值为0.1，由前端自行做好处理，避免产生负数】
// $goods_code为18位条码时 将忽略$data_type、$num字段、如果是普通条码如果未携带num将默认为1
    public function SaveSortingTasksGoods(int $order_id,string $goods_code,int $user_id,string $data_type,float $num)
{
    if ($num < 0){
        $ok = false;
        $error = "不允许小于0";
        return array($ok,$error);
    }

    if(empty($goods_code)){
        $ok = false;
        $error = "goods_code编码条码必填";
        return array($ok,$error);
    }

//    校验订单是否存在并获取订单详情
        $OrdersModel = new OrdersModel();
        $where["orderId"] = $order_id;
        $order_info = $OrdersModel->where($where)->find();
        if (empty($order_info)){
            $ok = false;
            $error = "不存在的订单";
            return array($ok,$error);
        }

//    校验订单任务是否存在于分拣任务中 并获取分拣任务详情
        $whole_order_sorting_task =  new whole_order_sorting_task();
        $whole_order_sorting_taskInfo = $whole_order_sorting_task->where(array('order_id' => $order_id))->find();
        if (empty($whole_order_sorting_taskInfo)){
            $ok = false;
            $error = "不存在的任务";
            return array($ok,$error);
        }

//    校验是否是当前用户的任务
        if ((int)$whole_order_sorting_taskInfo["user_id"] != (int)$user_id){
            $ok = false;
            $error = "这可能不是你的任务";
            return array($ok,$error);
        }

//    校验data type是否在允许范围
    if(!in_array($data_type,["+x","-x","x"])){
        $ok = false;
        $error = "data_type仅允许【累加：+x、累减：-x、直接修改：x】";
        return array($ok,$error);
    }


//    首先处理18位条码
    if(strlen($goods_code) == 18){
        $codeF = (int)substr($goods_code, 2);
        $codeW = (int)substr($goods_code, 2, 5);//商品库编码
        $codeE = (int)substr($goods_code, 7, 5);//金额单位 为 分
        $codeN = (int)substr($goods_code, 12, 5);//重量为G 应该是的【计件的话就是数量了】
        $codeC = (int)substr($goods_code, 17);//校验位



//    校验18位编码中的商品是否存在于分拣任务商品下
        $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
        $whole_order_sorting_goods_mod_info = $whole_order_sorting_goods_mod->where(array("goods_code"=>$codeW,"order_id"=>$order_id))->find();
        if(empty($whole_order_sorting_goods_mod_info)){
            $ok = false;
            $error = "该称重条码中的商品不存在";
            return array($ok,$error);
        }

//    此称重条码模式下仅会自动累加 不会受data_type字段影响 TODO:目前不支持称重补差价，仅支持数量补差价 比如按份的话就是标品 按计件方式
        $whole_order_sorting_goods_mod_save = $whole_order_sorting_goods_mod->where(array("goods_code"=>$codeW,"order_id"=>$order_id))->setInc("piece_quantity",(float)$codeN);
        if (!$whole_order_sorting_goods_mod_save){
            $ok = false;
            $error = "更新任务商品失败";
            return array($ok,$error);
        }else{
            $ok = true;
            $error = null;
            return array($ok,$error);
        }


    }



//    处理普通条码商品
//        校验普通商品编码是否存在于分拣任务商品下
        $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
        $whole_order_sorting_goods_mod_info = $whole_order_sorting_goods_mod->where(array("goods_code"=>$goods_code,"order_id"=>$order_id))->find();
        if(empty($whole_order_sorting_goods_mod_info)){
            $ok = false;
            $error = "商品不在当前任务下";
            return array($ok,$error);
        }
//        如果未传默认为1
//        if ($num==0){
//            $num = 1;
//        }

//        累加
        if($data_type == "+x"){
            $whole_order_sorting_goods_mod_save = $whole_order_sorting_goods_mod->where(array("goods_code"=>$goods_code,"order_id"=>$order_id))->setInc("piece_quantity",(float)$num);
            if (!$whole_order_sorting_goods_mod_save){
                $ok = false;
                $error = "累加任务商品分拣数量失败";
                return array($ok,$error);
            }else{
                $ok = true;
                $error = null;
                return array($ok,$error);
            }
        }
//        累减
        if($data_type == "-x"){
            $whole_order_sorting_goods_mod_save = $whole_order_sorting_goods_mod->where(array("goods_code"=>$goods_code,"order_id"=>$order_id))->setDec("piece_quantity",(float)$num);
            if (!$whole_order_sorting_goods_mod_save){
                $ok = false;
                $error = "累减任务商品分拣数量失败";
                return array($ok,$error);
            }else{
                $ok = true;
                $error = null;
                return array($ok,$error);
            }
        }

//        直接修改
        if($data_type == "x"){
            $whole_order_sorting_goods_mod_save = $whole_order_sorting_goods_mod->where(array("goods_code"=>$goods_code,"order_id"=>$order_id))->save(array("piece_quantity"=>(float)$num));
            if (!$whole_order_sorting_goods_mod_save){
                $ok = false;
                $error = "直接修改任务商品分拣数量失败";
                return array($ok,$error);
            }else{
                $ok = true;
                $error = null;
                return array($ok,$error);
            }
        }

//    TODO：通过计算允许超出的数量 是否允许完成分拣 【暂时不做】

}


//    完成分拣任务
//    目前完成分拣后不允许撤销
//    这里不支持重新修改所有分拣数量
// 创建出库单？按理说是发货再创建出库单,比如订单状态是已领取分拣 那么就获取分拣任务是不是正常完成分拣 如果是就直接使用分拣结果作为出库单库存 否则 默认使用购买数量
public function FinishTask(int $order_id,int $user_id){

    //        验证订单是否存在并获取订单信息
    $OrdersModel = new OrdersModel();
    $where["orderId"] = $order_id;
    $order_info = $OrdersModel->where($where)->find();
    if (empty($order_info)){
        $ok = false;
        $error = "不存在的订单";
        return array($ok,$error);
    }

//      获取分拣任务详情 以及 校验是否存在
    $whole_order_sorting_task =  new whole_order_sorting_task();
    $whole_order_sorting_taskInfo = $whole_order_sorting_task->where(array('order_id' => $order_id))->find();
    if (empty($whole_order_sorting_taskInfo)){
        $ok = false;
        $error = "不存在的任务";
        return array($ok,$error);
    }

//    校验是否是当前用户的任务
    if ((int)$whole_order_sorting_taskInfo["user_id"] != (int)$user_id){
        $ok = false;
        $error = "这可能不是你的任务";
        return array($ok,$error);
    }


//    该任务是否已完成，否则不允许重复完成
    if((int)$whole_order_sorting_taskInfo["sorting_status"] == 2){
        $ok = false;
        $error = "已完成分拣，不允许重复完成分拣！";
        return array($ok,$error);
    }

//    检查任务状态是否正常
    if((int)$whole_order_sorting_taskInfo["task_status"] == 2){
        $ok = false;
        $error = "该任务已关闭，无法完成分拣！";
        return array($ok,$error);
    }

// 开启事务
    $M = M();
    $M->startTrans();

    //   修改状态为已分拣 订单和分拣状态都往下走
//    修改订单状态为？ 这里分拣完成直接自动发货配送 改为 配送中 3
    $OrdersModel = new OrdersModel();
    $OrdersModel->where(array("orderId" => $order_id))->save(array("whole_order_sorting_status" => 3));
//    修改任务状态为 ？ sorting_status 已分拣
    $whole_order_sorting_task->where(array("order_id" => $order_id))->save(array("sorting_status"=>2));

//    更新订单商品中已分拣数量 sortingNum
//    获取分拣任务下的商品列表
    $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
    $OrderGoodsModel =  new OrderGoodsModel();
    $whole_order_sorting_goods_mod_arr = $whole_order_sorting_goods_mod->where(array("order_id" => $order_id))->select();
    foreach ($whole_order_sorting_goods_mod_arr as $v){
//        更新订单商品里的已分拣数量
        $OrderGoodsModelStatus = $OrderGoodsModel->where(array("orderId" => $order_id,"goodsCode"=>$v["goods_code"]))->save(array("sortingNum"=>$v["piece_quantity"]));
//        if (!$OrderGoodsModelStatus){
//            $M->rollback();
//            $ok = false;
//            $error = "更新分拣数量失败！";
//            return array($ok,$error);
//        }
    }

//  TODO:  对于差价退款必须依赖 订单商品实付金额 平摊每个数量单位是多少钱  然后差多少再计算出来实际需要补款多少

// TODO: 如果所有商品都是分拣0个 直接生成补差价 并等待手动补款。这里会自动完成订单。不会自动退差价。因为自动完成了 所以差价不好自动补了
// TODO:如果先生成差价单 然后调用系统自动确认收货的话 那最好了 可能会自动退款 退优惠 收回奖励等等



//  TODO:  对于分拣数量不够的进行补差价 比如0分件数量 或 未达到用户购买数量 生成补差价记录相关等 需要计算差价



// 呼叫骑手 ？根据配送方式？
    if (in_array($order_info["deliverType"], [2, 4]) && $order_info["isSelf"] == 0) {

//        呼叫达达
        if ($order_info["deliverType"] == 2){
//            $where['shopId'] = $order_info['shopId'];
////            获取店铺信息
//            $shopInfo = (new ShopsModel())->where($where)->find();
            $morders = D('Home/Orders');
//            $editCallAgainRider = $morders->editCallAgainRider(null, (int)$order_info['shopId']);
            $editCallAgainRider = $morders->editCallAgainRider(null, (int)$order_id);
            if((int)$editCallAgainRider["code"] != 0){
                $M->rollback();
                $ok = false;
                $error = $editCallAgainRider["msg"];
                return array($ok,$error);
            }
        }

//        呼叫快跑者
        if ($order_info["deliverType"] == 4){
            $morders = D('Home/Orders');
            $requestParams['orderId'] = (int)$order_info['shopId'];
//            $KuaiqueryDeliverFee = $morders->KuaiqueryDeliverFee(null, (int)$order_info['shopId']);
            $KuaiqueryDeliverFee = $morders->KuaiqueryDeliverFee(null, (int)$order_id);
            if((int)$KuaiqueryDeliverFee["code"] != 0){
                $M->rollback();
                $ok = false;
                $error = $KuaiqueryDeliverFee["msg"];
                return array($ok,$error);
            }
        }

//        添加订单日志 TODO：暂时不加
    }


    $M->commit();

    $ok = true;
    $error = null;
    return array($ok,$error);
}

//解析商品编码或18条码获取商品信息
public function GetGoodsDetailByBarcode(string $goods_code,int $order_id){
    $whole_order_sorting_goods_mod = new whole_order_sorting_goods();
    $OrderGoodsModel =  new OrderGoodsModel();



//    解析18位条码 普通编码无需解析
    if(strlen($goods_code) == 18){
        $codeF = (int)substr($goods_code, 2);
        $codeW = (int)substr($goods_code, 2, 5);//商品库编码
        $codeE = (int)substr($goods_code, 7, 5);//金额单位 为 分
        $codeN = (int)substr($goods_code, 12, 5);//重量为G 应该是的【计件的话就是数量了】
        $codeC = (int)substr($goods_code, 17);//校验位
//        如果是18位条码进行替换为解析后的编码
        $goods_code = $codeW;
    }

    $where = [];
    $where["orderId"] = $order_id;
    $where["goodsCode"] = $goods_code;
    //        获取订单商品中的信息

    $order_goods_info = $OrderGoodsModel->where($where)->find();
    if (empty($order_goods_info)){
        $ok = new \stdClass();
        $error = "未查询到该商品信息！";
        return array($ok,$error);
    }
    //    获取分拣商品的信息
    $where = [];
    $where["order_id"] = $order_id;
    $where["goods_code"] = $goods_code;
    $whole_order_sorting_goods_info = $whole_order_sorting_goods_mod->where($where)->find();
    if (empty($whole_order_sorting_goods_info)){
        $ok = new \stdClass();
        $error = "未查询到该商品信息！";
        return array($ok,$error);
    }

    //    合并信息并返回
    $ok = array_merge($order_goods_info,$whole_order_sorting_goods_info);
    $error = null;
    return array($ok,$error);
}

//    分拣任务历史-已完成分拣
public function GetSortingTaskList(int $user_id,int $current_page,int $page_size){
    $whole_order_sorting_task =  new whole_order_sorting_task();
    $OrdersModel = new OrdersModel();

    $limit = ($current_page-1)*$page_size.",".$page_size;

    $whole_order_sorting_task_list = $whole_order_sorting_task->where(array('user_id' => $user_id,'sorting_status'=>2))->limit($limit)->select();
    $order_list = [];
    foreach($whole_order_sorting_task_list as $v){
//        获取订单详情 然后和 分拣任务进行合并
        $order_info = $OrdersModel->where(array("orderId" => $v["order_id"]))->find();
        array_push($order_list,array_merge($order_info,$v));
    }

    $response = [];
    $response["list"] = $order_list;
    $response["current_page"] = $current_page;
    $response["page_size"] = $page_size;
    $ok = $response;
    $error = null;
    return array($ok,$error);

}



//TODO:暂时可以不做

//    释放撤销关闭分拣任务【发货前释放可以被其他人领取】 订单会更改为 已受理、未领取分拣。该操作只能在打包中操作。只能在打包中进行回滚操作





}
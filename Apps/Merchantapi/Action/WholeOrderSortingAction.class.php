<?php
namespace Merchantapi\Action;

use App\Modules\WholeOrderSorting\WholeOrderSortingModule;
use function GuzzleHttp\Psr7\str;

//摘果法分拣 APP接口
class WholeOrderSortingAction extends BaseAction {

    /*
 * 验证token,正确则返回分拣员数据
 * */
    public function sortingMemberVeri()
    {
        $memberToken = I("memberToken");
        if (empty($memberToken)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        $sessionData = userTokenFind($memberToken, 86400 * 30);//查询token
        if (empty($sessionData)) {
            $status['status'] = 401;
            $status['code'] = 401;
            $status['msg'] = "token字段有误";
            $this->ajaxReturn($status);
        }
        return $sessionData;
    }

//    获取待领取的订单任务列表
    public function GetOrderAvailableList()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingInfo($sortId);

        $shop_id = (int)$res["apiData"]['shopid'];
        $WholeOrderSorting = new WholeOrderSortingModule();
        $order_list = $WholeOrderSorting->GetOrderAvailableList($shop_id);

        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = (array)$order_list;
        $this->ajaxReturn($status);
    }

//    领取指定订单任务
//检测用户所属店铺和订单是否为同一个店铺
    public function ReceiveOrderTask()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $m = D("Merchantapi/SortingApi");
        $res = $m->getSortingInfo($sortId);


        $shop_id = (int)$res["apiData"]['shopid'];

        $order_id = I("order_id");
        $order_no = I("order_no");

        $user_id = $sortId;

        $WholeOrderSorting = new WholeOrderSortingModule();
        $order_list = $WholeOrderSorting->ReceiveOrderTask((int)$order_id,(int)$user_id,(int)$shop_id,(string)$order_no);
        list($ok,$error) = $order_list;
        if ($error!=null){
            $status['status'] = -1;
            $status['code'] = -1;
            $status['msg'] = $error;
            $status['data'] = $ok;
            $this->ajaxReturn($status);
        }

        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = $ok;
        $this->ajaxReturn($status);
    }

//    获取任务详情
public function GetTaskDetails()
{
    $sortId = $this->sortingMemberVeri()['id'];
    $order_id = I("order_id");
    $WholeOrderSorting = new WholeOrderSortingModule();
    $order_detail = $WholeOrderSorting->GetTaskDetails((int)$order_id,(int)$sortId);
    list($ok,$error) = $order_detail;
    if ($error!=null){
        $status['status'] = -1;
        $status['code'] = -1;
        $status['msg'] = $error;
        $status['data'] = $ok;
        $this->ajaxReturn($status);
    }
    $status['status'] = 0;
    $status['code'] = 0;
    $status['msg'] = "成功";
    $status['data'] = $ok;
    $this->ajaxReturn($status);


}

//获取我的任务
    public function MyTask()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $WholeOrderSorting = new WholeOrderSortingModule();
        $order_detail = $WholeOrderSorting->MyTask((int)$sortId);
        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = $order_detail;
        $this->ajaxReturn($status);
    }

// 更新分拣任务下某个商品已分拣数量。
// 用于实时变更某个任务下商品拣货数量 支持传入18位条码或商品编码/国条实现更新分拣数量
// $goods_code为18位称重条码或国条或普通编码
// $data_type【累加：+x、累减：-x、直接修改：x】比如扫码属于累加、手动输入直接修改、加减号属于累加或累减【注意：如果当前输入框数量大于1那么加减数值为1，如果小于1那么加减数值为0.1，由前端自行做好处理，避免产生负数】
// $goods_code为18位条码时 将忽略$data_type、$num字段、如果是普通条码如果未携带num将默认为1
    public function SaveSortingTasksGoods()
    {
        $sortId = $this->sortingMemberVeri()['id'];
        $WholeOrderSorting = new WholeOrderSortingModule();

        $order_id = I("order_id",0);//订单id
        $goods_code = I("goods_code",0);//商品编码或称重条码
        $data_type = I("data_type");//【累加：+x、累减：-x、直接修改：x】
        $num = I("num",0);//分拣数量【用于累增、累减、直接修改】
        $user_id = $sortId;

        $SaveSortingTasksGoodsResponse = $WholeOrderSorting->SaveSortingTasksGoods((int)$order_id,(string)$goods_code,(int)$user_id,(string)$data_type,(float)$num);

        list($ok,$error) = $SaveSortingTasksGoodsResponse;
        if ($error!=null){
            $status['status'] = -1;
            $status['code'] = -1;
            $status['msg'] = $error;
            $status['data'] = $ok;
            $this->ajaxReturn($status);
        }
        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = $ok;
        $this->ajaxReturn($status);
    }


//    完成分拣任务
public function FinishTask(){
    $sortId = $this->sortingMemberVeri()['id'];
    $WholeOrderSorting = new WholeOrderSortingModule();

    $order_id = I("order_id",0);//订单id
    $FinishTaskResponse = $WholeOrderSorting->FinishTask((int)$order_id,(int)$sortId);

    list($ok,$error) = $FinishTaskResponse;
    if ($error!=null){
        $status['status'] = -1;
        $status['code'] = -1;
        $status['msg'] = $error;
        $status['data'] = $ok;
        $this->ajaxReturn($status);
    }
    $status['status'] = 0;
    $status['code'] = 0;
    $status['msg'] = "成功";
    $status['data'] = $ok;
    $this->ajaxReturn($status);

}


//    解析商品编码或18条码获取商品信息
    public function GetGoodsDetailByBarcode(){
        $sortId = $this->sortingMemberVeri()['id'];
        $WholeOrderSorting = new WholeOrderSortingModule();

        $order_id = I("order_id",0);//订单id
        $goods_code = I("goods_code");//商品编码或称重18位条码
        $GetGoodsDetailByBarcodeResponse = $WholeOrderSorting->GetGoodsDetailByBarcode((string)$goods_code,(int)$order_id);

        list($ok,$error) = $GetGoodsDetailByBarcodeResponse;
        if ($error!=null){
            $status['status'] = -1;
            $status['code'] = -1;
            $status['msg'] = $error;
            $status['data'] = $ok;
            $this->ajaxReturn($status);
        }
        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = $ok;
        $this->ajaxReturn($status);

    }

//    分拣任务历史-已完成分拣
    public function GetSortingTaskList(){
        $sortId = $this->sortingMemberVeri()['id'];
        $WholeOrderSorting = new WholeOrderSortingModule();

        $current_page = I("current_page",0);//当前页
        $page_size = I("page_size",10);//每页数量
        $GetSortingTaskListResponse = $WholeOrderSorting->GetSortingTaskList((int)$sortId,(int)$current_page,(int)$page_size);

        list($ok,$error) = $GetSortingTaskListResponse;
        if ($error!=null){
            $status['status'] = -1;
            $status['code'] = -1;
            $status['msg'] = $error;
            $status['data'] = $ok;
            $this->ajaxReturn($status);
        }
        $status['status'] = 0;
        $status['code'] = 0;
        $status['msg'] = "成功";
        $status['data'] = $ok;
        $this->ajaxReturn($status);

    }


}
<?php
namespace App\Modules\Pos;

use App\Models\BaseModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;
use Think\Model;
use App\Enum\ExceptionCodeEnum;


use App\Models\PromotionScheduleModel;//DM档期计划


//收银DM促销
class PosPromotionDMModule extends BaseModel{

    // 创建档期计划  成功返回id
    public function addPromotionSchedule(array $dataP){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();
        //可校验数据合法 这一层应保证数据正确 与控制器和service不同 目的为了减少调用者犯的错以及简易使用
        //调用时需传递的
        $data['shop_id'] = null;
        $data['title'] = null;
        $data['sale_start_date'] = null;
        $data['sale_end_date'] = null;
        $data['remark'] = null;
        $data['purchase_start_date'] = null;
        $data['purchase_end_date'] = null;
        parm_filter($data, $dataP);

        //自动默认填充数据
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['sale_day'] = diffBetweenTwoDays( $data['sale_start_date'], $data['sale_end_date']);
        $data['purchase_day'] = diffBetweenTwoDays( $data['purchase_start_date'], $data['purchase_end_date']);

        $data_id = $mod->add($data);
        if($data_id){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data_id)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('创建档期计划失败')->toArray();
        }

    }

    //获取指定的id的档期计划
    public function getPromotionSchedule(int $id){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();
        $where['schedule_id'] = $id;
        $data = $mod->where($where)->find();
        if(!empty($data)){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($data)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无数据')->toArray();
        }

        
    }

    //根据店铺id 获取某个店铺的档期计划列表 分页
    public function getPromotionScheduleList(int $shopId,$page=1,$pageSize=15){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();

        $where['shop_id'] = $shopId;
        $where['is_delete'] = 0;
        $data = $mod->where($where)->page($page,$pageSize)->order("create_time desc")->select();
        if(!empty($data)){
            $page['total'] = $mod->where($where)->count();//一共条数
            $page['pageSize'] = $pageSize;//每页数量
            $page['start'] = ($page - 1) * $pageSize;
            $page['root'] = $data;
            $page['totalPage'] = ceil((int)$page['total'] / $pageSize);//总计页数
            $page['currPage'] = $page;//当前页
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($page)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无数据')->toArray();
        }

        

    }

    //模糊查询标题 某个店铺的档期计划列表 分页
    public function getTitleLikePromotionScheduleList(int $shopId,string $word='',int $page=1,int $pageSize=15){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();

        $where['shop_id'] = $shopId;
        $where['is_delete'] = 0;
        $where['title'] = array('like'=>'%'.$word.'%');
        $data = $mod->where($where)->page($page,$pageSize)->order("create_time desc")->select();
        if(!empty($data)){
            $page['total'] = $mod->where($where)->count();//一共条数
            $page['pageSize'] = $pageSize;//每页数量
            $page['start'] = ($page - 1) * $pageSize;
            $page['root'] = $data;
            $page['totalPage'] = ceil((int)$page['total'] / $pageSize);//总计页数
            $page['currPage'] = $page;//当前页
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($page)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无数据')->toArray();
        }

        

    }


    //删除档期计划
    public function delPromotionSchedule(int $id){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();

        $where['schedule_id'] = $id;
        $save['is_delete'] = 1;
        $data = $mod->where($where)->save($save);
        if($data){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(null)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('删除失败')->toArray();
        }

        return true;
    }

    //修改档期计划
    public function editPromotionSchedule(int $id,array $save){
        $response = LogicResponse::getInstance();
        $mod = new PromotionScheduleModel();
        $where['schedule_id'] = $id;
        $where['is_delete'] = 0;

        //调用时需传递的
        $data['title'] = null;
        $data['sale_start_date'] = null;
        $data['sale_end_date'] = null;
        $data['remark'] = null;
        $data['purchase_start_date'] = null;
        $data['purchase_end_date'] = null;
        $data['sale_day'] = null;
        $data['purchase_day'] = null;
        parm_filter($data, $save);

        //自动默认填充数据
        $data['update_time'] = time();
       
        $data = $mod->where($where)->save($data);
        if($data){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData(true)->toArray();
        }else{
            return $response->setCode(ExceptionCodeEnum::FAIL)->setData(false)->setMsg('修改失败')->toArray();
        }
    }

}
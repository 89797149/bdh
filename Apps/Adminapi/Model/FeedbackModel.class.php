<?php
 namespace Adminapi\Model;
 use function Qiniu\base64_urlSafeDecode;

 /**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 意见反馈
 */
class FeedbackModel extends BaseModel {
    /**
     * 分页列表
     */
    public function queryByPage($page=1,$pageSize=15,$startDate,$endDate){
        $where = " WHERE 1=1 ";
        $where .= " and f.dataFlag=1 ";
        if(!empty($startDate)){
            if(count(explode('-',$startDate)) > 1){
                $startTime = $startDate;
            }else{
                $startTime = date('Y-m-d H:i:s',$startDate);
            }
            $where .= " and f.addTime > '".$startTime."'";
        }
        if(!empty($endDate)){
            if(count(explode('-',$endDate)) > 1){
                $endTime = $endDate;
            }else{
                $endTime = date('Y-m-d H:i:s',$endDate);
            }
            $where .= " and f.addTime <= '".$endTime."'";
        }
        if(!empty(I('status'))){
            $where .= " AND f.status='".trim(I('status'),'+')."'";
        }
        $sql = "select f.* from __PREFIX__feedback f $where order by f.id desc ";
        $rs = $this->pageQuery($sql,$page,$pageSize);
        $list = $rs['root'];
        $userTab = M('users');
        foreach ($list as $key=>$value){
            $list[$key]['userName'] = '游客';
            if(!empty($value['userId'])){
                $userInfo = $userTab->where(['userId'=>$value['userId']])->field('userName,userPhone')->find();
                $list[$key]['userName'] = $userInfo['userName'];
            }
            $list[$key]['statusName'] = '未处理';
            if($value['status'] == 1){
                $list[$key]['statusName'] = '已处理';
            }
        }
        $rs['root'] = $list;
        return $rs;
    }

     /**
	  * 修改
	  */
	 public function edit($response){
         $rd = ['status' => -1,'msg' => '修改失败'];
		if(!empty($response)){
			$rs = $this->where("id='".$response['id']."'")->save($response);
			if($rs !== false){
				$rd['status'] = 1;
                $rd['msg'] = '修改成功';
			}
		}
		return $rd;
	 }

	 /**
	  * 获取指定对象
	  */
     public function getInfo($id){
        $info = $this->where("id='".$id."'")->find();
        $info['userName'] = '游客';
        if(!empty($info['userId'])){
            $userInfo = M('users')->where(['userId'=>$info['userId']])->find();
            $info['userName'] = $userInfo['userName'];
        }
        $imgs = [];
        if(!empty($info['imgs'])){
            $imgs = explode(',',$info['imgs']);
            foreach ($imgs as $key =>$val){
                $imgs[$key] = getimgsrc($val);
            }
        }
        $info['imgs'] = $imgs;
		return $info;
	 }


	 /**
	  * 删除
	  */
	 public function del($response){
	 	$rd = ['status' => -1];
	 	if(!empty($response)){
	 	    $edit['dataFlag'] = -1;
            $rs = $this->where("id='".$response['id']."'")->save($edit);
            if($rs){
                $rd['status']= 1;
            }
        }
		return $rd;
	 }

}
?>
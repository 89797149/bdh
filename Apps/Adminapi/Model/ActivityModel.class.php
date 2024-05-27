<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 活动页类
 */
class ActivityModel extends BaseModel
{


    /**
     * 获取活动列表
     */
    public function getList()
    {
        $where['state'] = 1;
        return (array)M('terrace_activity_page')->where($where)->select();
    }


    /**
     * 删除活动
     *
     * @param int $id
     * @return void
     */
    public function delData($post)
    {

        $id = $post['id'];
        //查询待删除的产品

        $mod_activity_page = M('terrace_activity_page');
        $mod_activity_page->startTrans();//开启事物
        $where['id'] = $id;
        $data = $mod_activity_page->where($where)->find();
        if (empty($mod_activity_page)) {
            return false;
        }

        $save['state'] = -1;
        $state1 = $mod_activity_page->where($where)->save($save);

        $state2 = M('terrace_activity_type')->where('activityPageId = ' . $data['id'])->delete();
        if ($state1) {
            $mod_activity_page->commit();  // 提交事物
            return true;
        }
        $mod_activity_page->rollback();  // 回滚
        return false;

    }

    /**
     * 新增活动
     *
     * @param [type] $parm
     * @return void
     */
    public function addData($parm)
    {
        $data['img'] = $parm['img'];
        $data['title'] = $parm['title'];
        $data['activityId'] = $parm['activityId'];
        return M('terrace_activity_page')->add($data);
    }

    /**
     * 修改活动
     */
    public function edit($parm)
    {
        //备参与过滤
        $save['img'] = $parm['img'];
        $save['activityId'] = $parm['activityId'];
        $save['title'] = $parm['title'];

        // parm_filter($save,$parm);


        $where['id'] = $parm['id'];

        return M('terrace_activity_page')->where($where)->save($save);
    }

    /**
     * 获取活动-详情
     */
    public function getActivityDetail($parm)
    {

        $where['id'] = $parm['id'];
        $where['state'] = 1;
        return (array)M('terrace_activity_page')->where($where)->find();
    }






    //-------------------------------------------------------------------

    /**
     * 活动页内容-修改
     */
    public function editActivityPageType($parm)
    {


        //预处理数据
        $parm['goods'] = implode(',', json_decode(htmlspecialchars_decode($parm['goods']), true));//特殊存储


        // $save['img'] = $parm['img'];
        // $save['goods'] = $parm['goods'];
        // $save['sort'] = $parm['sort'];
        // $save['direction'] = $parm['direction'];

        // var_dump($parm);
        $save['img'] = null;
        $save['goods'] = null;
        $save['sort'] = null;
        $save['direction'] = null;


        //去除null和''  貌似 parm_filter 函数 有问题
        forEach ($save as $k => $v) {
            if (!array_key_exists($k, $post_arr)) {
                $arr[$k] = $parm[$k];
            }
        }
        $save = array_filter($arr);


        $where['id'] = $parm['id'];

        return M('terrace_activity_type')->where($where)->save($save);
    }

    /**
     * 活动页内容-删除
     */
    public function deleteActivityPageType($parm)
    {

        $where['id'] = $parm['id'];
        // $where['activityPageId'] = $parm['activityPageId'];//没有就不安全

        $save['state'] = -1;
        return M('terrace_activity_type')->where($where)->save($save);
    }

    /**
     * 活动页内容-列表
     */
    public function getActivityPageType($parm)
    {
        $where['activityPageId'] = $parm['activityPageId'];
        $where['state'] = 1;
        return (array)M('terrace_activity_type')->where($where)->order('sort asc')->select();
    }

    /**
     * 活动页内容-详情 包含商品
     */
    public function getActivityPageTypeDetail($parm)
    {
        $where['state'] = 1;
        $where['id'] = $parm['id'];
        $data = M('terrace_activity_type')->where($where)->find();
        unset($where);
        $where['goodsId'] = array('in', $data['goods']);
        $data['goods'] = M('goods')->where($where)->select();
        return $data;
    }

    /**
     * 活动页内容-新增
     */
    public function addActivityPage($parm)
    {
        $data['activityPageId'] = $parm['activityPageId'];
        $data['img'] = $parm['img'];


        $data['goods'] = implode(',', json_decode(htmlspecialchars_decode($parm['goods']), true));
        $data['sort'] = (int)$parm['sort'];
        $data['direction'] = $parm['direction'];
        return M('terrace_activity_type')->add($data);
    }
}
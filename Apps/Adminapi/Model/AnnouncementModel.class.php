<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 公告相关
 */
class AnnouncementModel extends BaseModel
{
    /**
     * 添加商城公告
     * @param string $title 公告标题
     * @param string $content 公告内容
     * @return bool $data
     * */
    public function addAnnouncement(string $title, string $content)
    {
        $where = [];
        $where['title'] = $title;
        $info = $this->getAnnouncementDetail($where);
        if (!empty($info)) {
            //return ['code' => -1, 'msg' => "公告标题【{$title}】已存在，请更换公告标题", 'data' => false];
            return returnData(false, -1, 'error', "公告标题【{$title}】已存在，请更换公告标题");
        }
        $date = date('Y-m-d H:i:s', time());
        $saveData = [];
        $saveData['title'] = $title;
        $saveData['content'] = $content;
        $saveData['createTime'] = $date;
        $saveData['updateTime'] = $date;
        $model = M('announcement');
        $data = $model->add($saveData);
        if (!$data) {
            //return ['code' => -1, 'msg' => "添加失败", 'data' => false];
            return returnData(false, -1, 'error', "添加失败");
        }
        //return ['code' => 0, 'msg' => "添加成功", 'data' => true];;
        return returnData(true, 0, 'success', '添加成功');
    }

    /**
     * 编辑商城公告
     * @param array $params <p>
     * int id 公告id
     * string title 公告标题
     * string content 公告内容
     * </p>
     * @return bool $data
     * */
    public function updateAnnouncement(array $params)
    {
        $where = [];
        $where['id'] = $params['id'];
        $info = $this->getAnnouncementDetail($where);
        if (empty($info)) {
//            return ['code' => -1, 'msg' => "修改失败，请输入正确的公告id", 'data' => false];
            return returnData(false, -1, 'error', "修改失败，请输入正确的公告id");
        }
        if (!empty($params['title'])) {
            $where = [];
            $where['title'] = $params['title'];
            $infoByTitle = $this->getAnnouncementDetail($where);
            if ($infoByTitle && $infoByTitle['id'] != $params['id']) {
//                return ['code' => -1, 'msg' => "公告标题【{$params['title']}】已存在，请更换公告标题", 'data' => false];
                return returnData(false, -1, 'error', "公告标题【{$params['title']}】已存在，请更换公告标题");
            }
        }
        $date = date('Y-m-d H:i:s', time());
        $saveData = [];
        $saveData['id'] = $params['id'];
        $saveData['title'] = null;
        $saveData['content'] = null;
        $saveData['updateTime'] = $date;
        parm_filter($saveData, $params);
        $model = M('announcement');
        $data = $model->save($saveData);
        if (!$data) {
//            return ['code' => -1, 'msg' => "修改失败", 'data' => false];
            return returnData(false, -1, 'error', "修改失败");
        }
//        return ['code' => 0, 'msg' => "修改成功", 'data' => true];;
        return returnData(true, 0, 'success', '修改成功');
    }

    /**
     * 获取商城公告列表
     * @param array $params <p>
     * string title 公告标题
     * datetime startDate 添加时间区间-开始时间
     * datetime endDate 添加时间区间-结束时间
     * </p>
     * @return bool $data
     * */
    public function getAnnouncementList(array $params)
    {
        $where = " dataFlag = 1 and shopId=0 ";
        $whereFind = [];
        $whereFind['title'] = function () use ($params) {
            if (empty($params['title'])) {
                return null;
            }
            return ['like', "%{$params['title']}%", 'and'];
        };
        $whereFind['createTime'] = function () use ($params) {
            if (empty($params['startDate']) || empty($params['endDate'])) {
                return null;
            }
            return ['between', "{$params['startDate']}' and '{$params['endDate']}", 'and'];
        };
        where($whereFind, $params);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $field = 'id,title,content,createTime ';
        $sql = "select {$field} from __PREFIX__announcement where $whereInfo order by id desc ";
        $data = $this->pageQuery($sql, $params['page'], $params['pageSize']);
        if (empty($data['root'])) {
            $data['root'] = [];
        }
        return $data;
//        return ['code' => 0, 'msg' => "成功", 'data' => $data];;
    }

    /**
     * 获取公告详情
     * @param array $params <p>
     * int id 公告id
     * string title 公告标题
     * </p>
     * @return array $data
     * */
    public function getAnnouncementDetail(array $params)
    {
        $where = " dataFlag = 1 and shopId=0 ";
        $whereFind = [];
        $whereFind['id'] = function () use ($params) {
            if (empty($params['id'])) {
                return null;
            }
            return ['=', "{$params['id']}", 'and'];
        };
        $whereFind['title'] = function () use ($params) {
            if (empty($params['title'])) {
                return null;
            }
            return ['=', "{$params['title']}", 'and'];
        };
        where($whereFind, $params);
        if (empty($whereFind) || $whereFind == ' ') {
            $whereInfo = "{$where}";
        } else {
            $whereFind = rtrim($whereFind, ' and');
            $whereInfo = "{$where} and {$whereFind}";
        }
        $model = M('announcement');
        $field = 'id,title,content,createTime';
        $data = $model->where($whereInfo)->field($field)->find();
        if (empty($data)) {
            $data = [];
        }
        return (array)$data;
    }

    /**
     * 删除公告
     * @param ids 公告id
     * @return bool $data
     * */
    public function delAnnouncement($ids)
    {
        $saveDate = [];
        $saveDate['dataFlag'] = -1;
        $where = [];
        $where['id'] = ['IN', $ids];
        $model = M('announcement');
        $data = $model->where($where)->save($saveDate);
        if (!$data) {
//            return ['code' => -1, 'msg' => "删除失败", 'data' => false];
            return returnData(false, -1, 'error', '操作失败');
        }
        return returnData(true, 0, 'success', '操作成功');
    }
}

?>
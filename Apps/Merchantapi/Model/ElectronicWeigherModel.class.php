<?php
namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 电子秤
 */
class ElectronicWeigherModel extends BaseModel {

    /**
     * 新增电子秤配置
     * @param array $requestParams<p>
     * int shopId 门店id
     * string ip ip
     * string port 端口
     * int isDefault 是否默认【0:否|1:默认】
     * string remark 备注
     * </p>
     * @return bool $data
     */
    public function addElectronicWeigher(array $requestParams){
        $tab = M('electronic_weigher');
        $params = [];
        $params['shopId'] = null;
        $params['ip'] = null;
        $params['port'] = null;
        $params['isDefault'] = 0;
        $params['remark'] = null;
        parm_filter($params,$requestParams);
        if(empty($params['ip'])){
            return returnData(false, -1, 'error', 'ip不能为空');
        }
        if(empty($params['port'])){
            return returnData(false, -1, 'error', '端口不能为空');
        }
        $where = [];
        $where['shopId'] = $params['shopId'];
        $where['ip'] = $params['ip'];
        $info = $this->getElectronicWeigherInfo($where);
        if(!empty($info)){
            return returnData(false, -1, 'error', 'ip重复');
        }
        $params['createTime'] = date('Y-m-d H:i:s');
        $params['updateTime'] = date('Y-m-d H:i:s');
        if($params['isDefault'] == 1){
            //只允许存在一个默认小票机
            $where = [];
            $where['shopId'] = $params['shopId'];
            $where['dataFlag'] = 1;
            $save = [];
            $save['isDefault'] = 0;
            $tab->where($where)->save($save);
        }
        $data = $tab->add($params);
        if(!$data){
            return returnData(false, -1, 'error', '添加失败');
        }
        return returnData(true);
    }

    /**
     * 修改电子秤配置
     * @param array $requestParams<p>
     * int shopId 门店id
     * int id id
     * string ip ip
     * string port 端口
     * int isDefault 是否默认【0:否|1:默认】
     * string remark 备注
     * </p>
     * @return bool $data
     */
    public function updateElectronicWeigher(array $requestParams){
        $tab = M('electronic_weigher');
        $params = [];
        $params['shopId'] = null;
        $params['id'] = null;
        $params['ip'] = null;
        $params['port'] = null;
        $params['isDefault'] = 0;
        $params['remark'] = null;
        parm_filter($params,$requestParams);
        if(empty($params['ip'])){
            return returnData(false, -1, 'error', 'ip不能为空');
        }
        if(empty($params['port'])){
            return returnData(false, -1, 'error', '端口不能为空');
        }
        $where = [];
        $where['shopId'] = $params['shopId'];
        $where['ip'] = $params['ip'];
        $info = $this->getElectronicWeigherInfo($where);
        if(!empty($info) && $info['id'] != $params['id']){
            return returnData(false, -1, 'error', 'ip重复');
        }
        if($params['isDefault'] == 1){
            //只允许存在一个默认
            $where = [];
            $where['shopId'] = $params['shopId'];
            $where['dataFlag'] = 1;
            $save = [];
            $save['isDefault'] = 0;
            $tab->where($where)->save($save);
        }
        $params['createTime'] = date('Y-m-d H:i:s');
        $params['updateTime'] = date('Y-m-d H:i:s');
        $data = $tab->save($params);
        if(!$data){
            return returnData(false, -1, 'error', '修改失败');
        }
        return returnData(true);
    }

    /**
     * 获取电子秤配置详情
     * @param array $params<p>
     * int id id
     * string ip ip
     * </p>
     * @return array $data
     * */
    public function getElectronicWeigherInfo(array $params){
        $where = [];
        $where['dataFlag'] = 1;
        $where['id'] = null;
        $where['ip'] = null;
        parm_filter($where,$params);
        $tab = M('electronic_weigher');
        $data = $tab->where($where)->find();
        return (array)$data;
    }

    /**
     * 获取电子秤配置列表
     * @param int $shopId
     * @param string $ip ip
     * @param string $port
     */
    public function getElectronicWeigherList(int $shopId,string $ip,$port,$page,$pageSize){
        $where = "shopId={$shopId} and dataFlag=1 ";
        if(!empty($ip)){
            $where .= " and ip like '%{$ip}%' ";
        }
        if(!empty($port)){
            $where .= " and port like '%{$port}%' ";
        }
        $sql = "select * from __PREFIX__electronic_weigher where {$where} order by id desc ";
        $data = $this->pageQuery($sql,$page,$pageSize);
        return $data;
    }

    /**
     * 删除电子秤配置
     * @param int $shopId
     * @param array $id 电子秤配置id
     */
    public function delElectronicWeigher(int $shopId,array $id){
        $where['shopId'] = $shopId;
        $where['dataFlag'] = 1;
        $where['id'] = ['IN',$id];
        $save = [];
        $save['dataFlag'] = -1;
        $save['updateTime'] = date('Y-m-d H:i:s');
        $data = M('electronic_weigher')
            ->where($where)
            ->save($save);
        if(!$data){
            return returnData(false, -1, 'error', '删除失败');
        }
        return returnData(true);
    }

    /**
     *获取默认电子秤配置
     * @param int $shopId
     * @return array $data
     * */
    public function getElectronicWeigherDefault(int $shopId){
        $tab = M('electronic_weigher');
        $where = [];
        $where['shopId'] = $shopId;
        $where['isDefault'] = 1;
        $where['dataFlag'] = 1;
        $data = $tab->where($where)->find();
        return (array)$data;
    }
}
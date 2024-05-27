<?php
 namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品属性值类
 */
class GoodsAttributesModel extends BaseModel {
    /**
     * 属性值列表
     */
    public function getGoodsAttributesList($data){
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $m = M('goods_attributes');
        $ret = array(
            'status' => -1,
            'msg' => '数据获取失败',
        );
        $list = $m->where("shopId='".$shopId."' AND goodsId='".$data['goodsId']."'")->field('attrId')->select();
        $ret['list'] = $list;
        if($ret['list']){
            $parentId = [];
            foreach ($list as $val){
                $parentId[] = $val['attrId'];
            }
            $parentIdStr = 0;
            $parentId = array_unique($parentId);
            if(count($parentId) > 0){
                $parentIdStr = implode(',',$parentId);
            }
            $parentList = M('attributes')->where("attrId IN($parentIdStr)")->select();
            foreach ($parentList as $key=>&$val){
                $val['children'] = M('goods_attributes')->where("attrId='".$val['attrId']."' AND goodsId='".$data['goodsId']."'")->select();
            }
            unset($val);
            $ret['list'] = $parentList;
            $ret['status'] = 1;
            $ret['msg'] = '获取数据成功';
        }
        return $ret;
    }

    /**
     * 添加属性值
     */
    public function goodsAttributesAdd($data){
        $mod = M('goods_attributes');
        $returnData = array(
            'status' => -1,
            'msg' => '添加失败',
        );
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $data['shopId'] = $shopId;
        $info = $mod->where("shopId='".$shopId."' AND attrVal='".$data['attrVal']."' AND goodsID='".$data['goodsId']."' AND attrId='".$data['attrId']."'")->find();
        if($info){
            $returnData['msg'] = "该商品属性值已经存在,不能重复添加";
            return $returnData;
        }
        //限制普通价格属性大类只能存在一个 后加
        $parent = $mod = M('goods_attributes ga')
            ->join("LEFT JOIN wst_attributes a ON ga.attrId=a.attrId")
            ->where("a.isPriceAttr = 1 AND ga.goodsId='".$data['goodsId']."' AND a.attrFlag=1")
            ->field('a.*')
            ->select();
        $parent = arrayUnset($parent,'attrId');
        if(in_array(count($parent),[1])){
            $exitAattr = M('goods_attributes')->where("attrId='".$data['attrId']."' AND goodsId='".$data['goodsId']."' AND attrVal='".$data['attrVal']."'")->find();
            if($exitAattr){
                $attrName = $parent[0]['attrName'];
                $returnData['msg'] = "已存在普通价格属性($attrName),每个商品只能存在一个普通价格属性";
                return $returnData;
            }
            $parentAttr = M('attributes')->where("attrId='".$data['attrId']."'")->find();
            if(count($parent) == 1 && $parentAttr['attrId'] != $parent[0]['attrId'] && $parentAttr['isPriceAttr'] == 1){
                $attrName = $parent[0]['attrName'];
                $returnData['msg'] = "已存在普通价格属性($attrName),每个商品只能存在一个普通价格属性";
                return $returnData;
            }

        }
        $res = M('goods_attributes')->add($data);
        if($res){
            //更新商品的总库存
            $goodsAttrList = M('goods_attributes')->where("goodsID='".$data['goodsId']."'")->select();
            $stock = 0;
            foreach ($goodsAttrList as $val){
                $stock += $val['attrStock'];
            }
            M('goods')->where("goodsId='".$data['goodsId']."'")->save(['goodsStock'=>$stock]);
            //云仓参数 start
            $goodsInfo = M('goods')->where("goodsId='".$data['goodsId']."'")->find();
            $cloudInvoice['goodsSn'] = $goodsInfo['goodsSn'];
            //云仓参数 end
            //更新云仓数据 start
            updateCloudStorage($cloudInvoice);
            //更新云仓数据 end
            $returnData['status'] = 1;
            $returnData['msg'] = '添加成功';
        }
        return $returnData;
    }


    /**
     * 编辑商品属性值
     */
    public function goodsAttributesEdit($data){
        $mod = M('goods_attributes');
        $res = $mod->where("id='".$data['id']."'")->save($data);
        $returnData = array(
            'status' => -1,
            'msg' => '编辑失败',
        );
        if($res !== false){
            //更新商品的总库存
            $goodsAttrList = M('goods_attributes')->where("goodsID='".$data['goodsId']."'")->select();
            $stock = 0;
            foreach ($goodsAttrList as $val){
                $stock += $val['attrStock'];
            }
            M('goods')->where("goodsId='".$data['goodsId']."'")->save(['goodsStock'=>$stock]);
            //云仓参数 start
            $goodsInfo = M('goods')->where("goodsId='".$data['goodsId']."'")->find();
            $cloudInvoice['goodsSn'] = $goodsInfo['goodsSn'];
            //云仓参数 end
            //更新云仓数据 start
            updateCloudStorage($cloudInvoice);
            //更新云仓数据 end
            $returnData['status'] = 1;
            $returnData['msg'] = '编辑成功';
        }
        return $returnData;
    }

    /**
     * 删除商品属性值
     */
    public function goodsAttributesDel($data){
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $ids = trim($data['id'],',');
        if(empty($ids)){
            $ids = 0;
        }
        $mod = M('goods_attributes');
        $countStock = 0;
        $attrList = $mod->where("id IN($ids) AND shopId='".$shopId."'")->select();
        foreach ($attrList as $val){
            $goodsId = $val['goodsId'];
            $countStock += $val['attrStock'];
        }
        $sql = "UPDATE  ".__PREFIX__goods." SET goodsStock=goodsStock-$countStock WHERE goodsId='".$goodsId."'";
        $this->execute($sql);
        $res = $mod->where("id IN($ids) AND shopId='".$shopId."'")->delete();

        //云仓参数 start
        $goodsInfo = M('goods')->where("goodsId='".$goodsId."'")->find();
        $cloudInvoice['goodsSn'] = $goodsInfo['goodsSn'];
        //云仓参数 end
        //更新云仓数据 start
        updateCloudStorage($cloudInvoice);
        //更新云仓数据 end

        $returnData = array(
            'status' => -1,
            'msg' => '删除失败',
        );
        if($res){
            $returnData['status'] = 1;
            $returnData['msg'] = '删除成功';
        }
        return $returnData;
    }

};
?>

<?php
namespace app\admin\model;
use think\Db;
use app\common\model\ErrorCode;

class GoodsModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function getGoodsName($goods)
    {
        if(empty($goods))return false;
        $sql = "SELECT 
                  goods_name
                FROM
                  ".tablename(GOODS)."
                WHERE id IN ({$goods});";
        $res = Db::query($sql);
        if(!empty($res))
        {
            foreach($res as $key => $val)
            {
                $arr[] = $val['goods_name'];
            }
            return $arr;
        }
        return false;
    }

    public static function goodsList($data)
    {
        $where = " is_del = 0 ";
        if($data['keywords']){
            $where .= " AND goods_name LIKE '%{$data['keywords']}%'";
        }
        $total = Db::name(GOODS)->where($where)->count('id');
        if($total <= 0)
        {
            return [ErrorCode::SUCCESS,'还没有添加商品'];
        }

        $sql = "SELECT 
                  * 
                FROM
                  ".tablename(GOODS)."
                WHERE {$where} 
                ORDER BY id DESC 
                LIMIT ".(($data['page']-1) * $data['size']).','.$data['size'];
        $list = Db::query($sql);
        if(!empty($list))
        {
            foreach($list as $key => $val)
            {
//                $list[$key]['goods_img'] = tomedia($val['goods_img']);
//                $list[$key]['goods_img'] = 'https://'.$_SERVER['HTTP_HOST'].'/attachment/'.$val['goods_img'];
            }
        }
        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }

    public static function GoodsAdd($data)
    {
        if($data['goods_name']==null){
            return [ErrorCode::FAILED,'请输入商品名称'];
        }
//        elseif($data['class_id']==null){
//        return [ErrorCode::FAILED,'请选择商品分类'];
//        }
        elseif($data['goods_img']==null){
            return [ErrorCode::FAILED,'请上传商品图片'];
        }elseif($data['market_price'] <= 0){
            return [ErrorCode::FAILED,'请输入商品市场价'];
        }elseif($data['goods_price'] <= 0){
            return [ErrorCode::FAILED,'请输入商品本店价'];
        }
        else
        {
            $where = [];
            if($data['id'] > 0)
            {
                $isRecord = pdo_getcolumn(GOODS, ['id' => $data['id']], 'id');
                if (empty($isRecord)) {
                    return [ErrorCode::FAILED, '该记录不存在,请确认'];
                }
                $where['id'] = $data['id'];
            }
            else
            {
                $data['createtime'] = TIMESTAMP;
            }
            $result = self::addEditData(GOODS,$data,$where);
        }
        return $result;
    }



    public static function GoodsInfo($data)
    {
        if($data['id'] == 0){
            return [ErrorCode::FAILED,'未查询到该条记录'];
        }
        else
        {
            $info = pdo_get(GOODS,['id'=>$data['id']] );
            if($info==null){
                return [ErrorCode::FAILED,'未查询到该条记录'];
            }
            else
            {
                if($info['goods_img'])
                {
                    $info['goods_img'] = tomedia($info['goods_img']);
                }
            }
        }
        return [ErrorCode::SUCCESS,'',$info];
    }

    public static function GoodsDel($data)
    {
        if($data['id'] == 0){
            return [ErrorCode::FAILED,'未获取到商品id'];
        }
        else
        {
            return self::delData(GOODS,['is_del'=>1,'id'=>$data['id']]);
        }
    }


}
<?php
namespace app\admin\model;
use think\Db;
use app\common\model\ErrorCode;

class CouponModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function getCouponInfo($coupon_id)
    {
        if(empty($coupon_id))return false;
        $sql = "SELECT 
                  *
                FROM
                  ".tablename(USER_COUPON)." as a
                  LEFT JOIN ".tablename(COUPON)." AS b 
                    ON a.coupon_id = b.id 
                WHERE a.id = {$coupon_id};";
        $res = Db::query($sql);
        if(!empty($res[0]))
        {
            return $res[0];
        }
        return false;
    }

    public static function CouponList($data)
    {
        $where = " WHERE is_del = 0 ";
        if($data['keywords']){
            $where .= " AND title LIKE '%{$data['keywords']}%'";
        }
        $total = pdo_fetchcolumn(" select count(id) from " . tablename(COUPON) .$where);
        if($total <= 0)
        {
            return [ErrorCode::SUCCESS,'暂无数据'];
        }
        $sql = "SELECT 
                  * 
                FROM
                  ".tablename(COUPON)."
                {$where} 
                ORDER BY id DESC 
                LIMIT ".(($data['page']-1) * $data['size']).','.$data['size'];
        $list = pdo_fetchall($sql);
        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }

    public static function CouponAdd($data)
    {
        if($data['title']==null){
            return [ErrorCode::FAILED,'请输入标题'];
        }elseif($data['start_time']==null){
            return [ErrorCode::FAILED,'请输入开始时间'];
        }elseif($data['end_time']==null){
            return [ErrorCode::FAILED,'请输入结束时间'];
        }elseif($data['money']==null||$data['money']<0.01){
            return [ErrorCode::FAILED,'请填写金额'];
        }
        else
        {
            if($data['id'] > 0)
            {
                $where['id'] = $data['id'];
            }
            else
            {
                $data['createtime'] = TIMESTAMP;
            }
            $result = self::addEditData(COUPON,$data,$where);
        }
        return $result;
    }

    public static function CouponInfo($data)
    {
        if($data['id'] == 0){
            return [ErrorCode::FAILED,'未获取到优惠券id'];
        }
        else
        {
            $result = pdo_get(COUPON,$data);
            return [ErrorCode::SUCCESS,'',$result];
        }
        return [ErrorCode::FAILED,'获取信息失败'];
    }


    public static function CouponDel($data)
    {
        if($data['id'] == 0){
            return [ErrorCode::FAILED,'未获取到id'];
        }
        else
        {
            if($data['type'] == 'couponType')
            {
                $table = COUPON;
            }
            else if($data['type'] == 'coupon')
            {
                $table = USER_COUPON;
            }
            else
            {
                return [ErrorCode::FAILED,'未获取到要操作的优惠券类型'];
            }
            return self::delData($table,['is_del'=>1,'id'=>$data['id']]);
        }
    }

    public static function CouponBatchCreate($data)
    {
        if($data['coupon_id'] == 0){
            return [ErrorCode::FAILED,'请选择优惠券类型'];
        }
        elseif($data['num'] == 0)
        {
            return [ErrorCode::FAILED,'请输入要生成的优惠卷数量'];
        }
        elseif($data['num'] < 1 || $data['num'] > 999 )
        {
            return [ErrorCode::FAILED,'要生成的优惠卷数量不能小于0或大于999张'];
        }

        $coupon = pdo_get(COUPON,['id'=>$data['coupon_id']]);
        if($coupon == null)
        {
            return [ErrorCode::FAILED,'该优惠券不存在'];
        }
        else
        {
            /* 生成优惠劵序列号 start */
            $arr_keys = ['coupon_id','coupon_sn','start_time','end_time','send_type','createtime'];
            $sql = 'INSERT INTO '.tablename(USER_COUPON).' (' . implode(',' ,$arr_keys) . ') values';

            $num = pdo_fetchcolumn("SELECT MAX(coupon_sn) FROM ".tablename(USER_COUPON));
            $num = $num ? floor($num / 10000) : 100000;

            for($i=0;$i<$data['num'];$i++)
            {
                $coupon_sn = ($num + $i) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $arr_values = [$coupon['id'],$coupon_sn,$coupon['start_time'],$coupon['end_time'],$coupon['send_type'],TIMESTAMP];
                $sql .= " ('" . implode("','" ,$arr_values) . "'),";
            }
            $sql = substr($sql ,0 ,-1);
            $res = pdo_query($sql);
            if($res){
                return [ErrorCode::SUCCESS,'操作成功'];
            }else{
                return [ErrorCode::FAILED,'操作失败'];
            }
            /* 生成优惠劵序列号 end */
        }

    }



}
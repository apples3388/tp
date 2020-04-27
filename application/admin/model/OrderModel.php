<?php
namespace app\admin\model;
use app\admin\model\GoodsModel;
use app\admin\model\CouponModel;
use think\Config;
use think\Db;
use app\common\model\ErrorCode;

class OrderModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function orderList($params)
    {
        $where = " o.is_del = 0 AND o.order_type = 1";
        if($params['keywords'])
        {
            $where .= " AND order_sn LIKE  '%{$params['keywords']}%'";
        }
        if($params['staff_id'] > 0)
        {
            $where .= " AND o.staff_id = {$params['staff_id']} ";
        }
        if($params['phone'])
        {
            $where .= " AND o.phone LIKE  '%{$params['phone']}%'";
        }
        if($params['start_time'])
        {
            $where .= " AND o.createtime >= ".strtotime($params['start_time']);
        }
        if($params['end_time'])
        {
            $where .= " AND o.createtime <= ".strtotime($params['end_time']);
        }

        $total = Db::name(ORDER)->alias('o')->where($where)->count('o.id');
        if($total <= 0)
        {
            return [ErrorCode::SUCCESS,'暂无订单'];
        }
        $sql = "SELECT 
                  o.id,
                  o.section_number,
                  c.carnum,
                  o.username,
                  o.phone,
                  o.car_id,
                  o.price,
                  o.nowprice,
                  o.goods,
                  o.order_status,
                  o.coupon_id,
                  o.washtime,
                  o.createtime,
                  o.paytime,
                  s.`name` AS staff_name 
                FROM
                  ".tablename(ORDER)." AS o 
                  LEFT JOIN ".tablename(STAFF)." AS s 
                    ON o.staff_id = s.id 
                  LEFT JOIN ".tablename(USER_CAR)." AS c
                    ON o.car_id = c.id 
                WHERE {$where} 
                ORDER BY id DESC 
                LIMIT ".(($params['page']-1) * $params['size']). ",". $params['size'];
        $list = Db::query($sql);

        if(!empty($list))
        {
            $order_type = Config::get('config.orderType');
            foreach($list as $key => $val)
            {
                $list[$key]['goods_names'] = GoodsModel::getGoodsName($val['goods']);
                if($val['coupon_id'] > 0)
                {
                    $list[$key]['coupon_info'] = CouponModel::getCouponInfo($val['coupon_id']);
                }
                $list[$key]['status'] = $order_type[$val['order_status']];
            }
        }

        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }

    public static function orderInfo($id)
    {
        if($id==0)
        {
            return [ErrorCode::SUCCESS,'未获取到订单id'];
        }
        $order_info = Db::name(ORDER)->where(" id = {$id} ")->find();
        return [ErrorCode::SUCCESS ,'操作成功',$order_info];
    }

    public static function OrderOperation($params)
    {
        $order_id = $params['order_id'];
        $type = $params['type'];

        if($order_id == 0)
        {
            return [ErrorCode::SUCCESS,'未获取到订单id'];
        }
        $order = pdo_get(ORDER,['id'=>$order_id]);

        if(empty($type))
        {
            return [ErrorCode::SUCCESS,'未获取到订单类型'];
        }

        if($order['order_status'] == -2)
        {
            return [ErrorCode::SUCCESS,'当前订单状态已超时'];
        }
        elseif($order['order_status'] == -1)
        {
            return [ErrorCode::SUCCESS,'当前订单状态已取消'];
        }
        elseif ($order['order_status'] == 1)
        {
            return [ErrorCode::SUCCESS,'当前订单状态已支付'];
        }
        elseif ($order['order_status'] == 3)
        {
            return [ErrorCode::SUCCESS,'当前订单状态已完成'];
        }

        if($type == 'cancel')
        {
            $where = ['id'=>$order_id, 'order_status'=>0];
            $data = ['order_status'=>-1];
            //退回优惠券
            if($order['coupon_id'] > 0)
            {
                pdo_update(USER_COUPON,
                    ['order_id'=>0,'is_use'=>0,'use_time'=>0],
                    ['id'=>$order['coupon_id']]
                );
            }
        }
        else if($type == 'done')
        {
            $where = ['id'=>$order_id, 'order_status'=>2];
            $data = ['order_status'=>3];
        }
//        if($type == 'send')
//        {
//            if($staff_id == 0)
//            {
//                return [ErrorCode::FAILED,'请选择指派洗车人员'];
//            }
//            $where = ['id'=>$order_id, 'order_status'=>1,'order_type'=>1,'staff_id'=>0];
//            $data = ['order_status'=>2,'staff_id'=>$staff_id];
//        }
        $res = pdo_update(ORDER,$data,$where);
        if($res)
        {
            return [ErrorCode::SUCCESS ,'操作成功'];
        }
        else
        {
            return [ErrorCode::FAILED,'操作失败'];
        }
    }

    public static function TransferOrder($params)
    {
        if($params['order_id'] <= 0)
        {
            return [ErrorCode::FAILED,'未获取到订单id'];
        }
        if($params['new_staff_id'] <= 0)
        {
            return [ErrorCode::FAILED,'未获取到新的洗车员id'];
        }
        $order_id = $params['order_id'];

        $sql = "SELECT 
                  order.id,
                  order.user_id,
                  order.staff_id,
                  order.phone ,
                  order.order_status,
                  staff.`name`,
                  staff.`phone` as staff_phone,
                  uc.carnum,
                  uc.carcolor,
                  uc.carbrand,
                  uc.cartype
                FROM
                  ".tablename(ORDER)." AS `order` 
                  LEFT JOIN ".tablename(STAFF)." AS `staff` 
                    ON order.staff_id = staff.id
                  LEFT JOIN ".tablename(USER_CAR)." AS `uc` 
                    ON order.car_id = uc.id 
                WHERE order.is_del = 0 
                AND order.id = {$params['order_id']};";
        $order = pdo_fetch($sql);
        if($order['order_status'] != 2)
        {
            return [ErrorCode::FAILED,'当前订单状态不正确！'];
        }
        //转单后原洗车员需要短信通知吗 模板id未注册
//        $res = SmsModel::Send([
//            'send_phone'=>$order['staff_phone'],
//            'name' => cutstr($order['name'], 1),
//            'msg'=> "尊敬的".cutstr($order['name'], 1)."师傅，您的订单{$order['order_sn']}已经指派给其他师傅。",
//        ]);

        pdo_begin();

        $new_staff_id = $params['new_staff_id'];
        $new_staff = pdo_get(STAFF,['id'=>$new_staff_id]);

        $res1 = pdo_update(ORDER,['staff_id'=>$new_staff_id]);
        //生成订单操作步骤
        $res2 = pdo_insert(ORDER_ACTION,[
            'order_id'=>$order_id,
            'order_status'=> 2,
            'action_note'=> '订单'.$order['order_sn'].'由洗车员'.$order['name'].'转单，洗车员'.$new_staff['name'].'已接单 ',
            'createtime' => TIMESTAMP
        ]);

        if($res1 && $res2)
        {
            pdo_commit();
            //如果该笔订单存在代理商数据则更改
            $id = pdo_getcolumn(PROXY_FINANCE_RECORD,['order_id'=>$order],'id');
            if($id > 0)
            {
                pdo_update(PROXY_FINANCE_RECORD,['staff_id'=>$new_staff_id],['order_id'=>$order_id,'staff_id'=>$order['staff_id']]);
            }

            //发送短信
//            $res = SmsModel::Send([
//                'send_phone'=>$new_staff['phone'],
//                'name' => cutstr($new_staff['name'], 1),
//                'phone'=>$order['phone'],
//                'address'=>$order['address'],
//                'card_number'=> self::$cartype[$order['cartype']].'-'.$order['carcolor'].'-'.$order['carbrand'].'-'.$order['carnum'],
//            ]);
            //载入日志函数
            load()->func('logging');
            //记录支付日志
//            logging_run('<<'.json_encode($res));

            pdo_rollback();
            return [ErrorCode::SUCCESS,'操作成功！'];
        }

        return [ErrorCode::SUCCESS,'操作失败！'];
    }

}
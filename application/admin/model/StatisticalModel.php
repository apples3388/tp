<?php
namespace app\admin\model;
use app\common\model\ErrorCode;
use think\Db;

class StatisticalModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function IndexData()
    {
        $starTime = strtotime(date("Y-m-d",time()));   //昨天开始时间
        $endTime = $starTime + 86399;  //昨天结束时间

        $where = " AND ( createtime between '{$starTime}' and '{$endTime}' )";

        //洗车订单总金额
        $all_money = Db::name(ORDER)->where('order_type = 1 AND order_status > 0' )->sum('nowprice');
        $data['all_money'] = !empty($all_money) ? $all_money : 0;

        //洗车订单今日收入
        $today_all_money = Db::name(ORDER)->where("order_type = 1 AND order_status > 0 {$where}" )->sum('nowprice');
        $data['today_all_money'] = $today_all_money ? $today_all_money : 0;

        //包月购买
        $month_card_money = Db::name(ORDER)->where("order_type = 2 AND order_status > 0 " )->sum('nowprice');
        $data['month_card_money'] = $month_card_money ? $month_card_money : 0;

        //会员购买
        $data['vip_buy_num'] = 0;

        //洗车类型
        $car_type = \think\Config::get('config.carType');
        foreach ($car_type as $k => $v)
        {
            $sql = "SELECT 
                      COUNT(cartype) as count
                    FROM
                      (SELECT 
                        a.id,
                        b.cartype 
                      FROM
                        ".tablename(ORDER)." AS a 
                      LEFT JOIN ".tablename(USER_CAR)." AS b 
                          ON a.car_id = b.id
                      WHERE order_type = 1 
                        AND order_status > 0) AS tmp 
                    WHERE cartype = {$k};";
            $car_type_num = Db::query($sql)[0]['count'];
            $new_arr[] = $car_type_num ? $car_type_num : 0;
        }
        $data['car_order_type'] = $new_arr;
        unset($new_arr);

        //订单数量
        $done_order = Db::name(ORDER)->where("order_type = 1 AND order_status > 2 " )->count('id');
        $washing_order = Db::name(ORDER)->where("order_type = 1 AND order_status = 2 " )->count('id');
        $cancel_order = Db::name(ORDER)->where("order_type = 1 AND order_status = -1 " )->count('id');
        $data['order_type']['done_order'] = $done_order ? $done_order : 0;
        $data['order_type']['washing_order'] = $washing_order ? $washing_order : 0;
        $data['order_type']['cancel_order'] = $cancel_order ? $cancel_order : 0;

        //总会员数
        $all_user = Db::name(USER)->where('')->count('id');
        $data['all_user'] = $all_user ? $all_user : 0;

        //今日注册会员
        $today_reg_user = Db::name(USER)->where("1 {$where}")->count('id');
        $data['today_reg_user'] = $today_reg_user ? $today_reg_user : 0;

        //今日消费客户数
        $today_user_pay_num = Db::name(ORDER)->where(" order_status > 0 {$where}")->count('user_id');
        $data['today_user_pay_num'] = $today_user_pay_num ? $today_user_pay_num : 0;

        //保留字段
        $data['field'] = 0;
        return [ErrorCode::SUCCESS ,'获取数据成功',$data];
    }





}
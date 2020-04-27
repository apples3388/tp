<?php
namespace app\admin\controller;
use app\admin\model\OrderModel;

class Order extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function orderList()
    {
        $result = OrderModel::orderList([
            'keywords'=>input('keywords',''),
            'staff_id'=>input('staff_id',0),
            'phone'=>input('phone',''),
            'start_time'=>input('start_time',''),
            'end_time'=>input('end_time',''),
            'page'=>input('page',1),
            'size'=>input('size',5),
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }


}

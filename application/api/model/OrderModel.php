<?php
namespace app\api\model;
use think\Db;

class OrderModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function getOrderInfo($where=array(),$fields=array())
    {
        return pdo_get(ORDER,$where,$fields);
    }

    private function autoCancelOrder($user_id)
    {
        $sql = "SELECT 
                  * 
                FROM
                  ".tablename(ORDER)."
                WHERE user_id = {$user_id} 
                  AND order_status = 0 
                  AND (createtime + 600) - UNIX_TIMESTAMP() <= 0 ;";
        $list = pdo_fetchall($sql);
        foreach($list as $key => $val)
        {
            if($val['coupon_id'] > 0)
            {
                pdo_update(USER_COUPON,
                    ['order_id'=>0,'user_id'=>$val['user_id'],'is_use'=>0,'use_time'=>0],
                    ['id'=>$val['coupon_id']]
                );
            }
            pdo_update(ORDER,
                ['order_status'=>-2],
                ['id'=>$val['id']]
            );
        }
    }

    public function orderList($data)
    {
        $this->autoCancelOrder($data['user_id']);
        $where = " WHERE o.order_type = 1 AND o.user_id = {$data['user_id']} ";

        switch ($data['status'])
        {
            case 1;
                $status = "0,1,2";
                break;
            case 4;
                $status = "-1,-2,-3";
                break;
            default;
                $status = ($data['status']+1);
                break;
        }
        $where .= " AND order_status IN ($status)";

        $sql = "
            SELECT 
              o.id,
              o.order_sn,
              o.section_number,
              s.section_name,
              s.address as section_address,
              s.section_img,
              o.address,
              o.order_status,
              o.nowprice,
              o.createtime,
              o.order_type,
              uc.carbrand,
              uc.carnum,
              uc.carcolor,
              uc.cartype,
              goods.goods_name
            FROM
              ".tablename(ORDER)." as o 
              LEFT JOIN ".tablename(SECTION)." AS s 
                ON o.section_number = s.section_number 
              LEFT JOIN ".tablename(USER_CAR)." AS uc 
                ON o.car_id = uc.id
              LEFT JOIN ".tablename(GOODS)." AS goods 
                ON goods.id = o.goods 
            {$where} 
            ORDER BY createtime DESC
            LIMIT ".(($data['page']-1) * $data['size']). ",". $data['size'];
        $result = pdo_fetchall($sql);
        foreach ($result as $key => $val)
        {
            $result[$key]['createtime'] = date("Y-m-d H:i:s",$val['createtime']);
        }
        return [ErrorCode::SUCCESS, '获取成功',$result];
    }

    public function orderInfo($data)
    {
        $order = self::getOrderInfo($data);
        if($order['section_number'] > 0)
        {
            $sectionModel = new SectionModel();
            $section = $sectionModel->sectionInfo($order['section_number']);
            if(empty($section))
            {
                return ['code' => 1, 'msg' => '该小区不存在'];
            }
            $order['section_info'] = $section;
        }
        else
        {
            $order['section_info'] = null;
        }

        if ($order['car_id'] > 0)
        {
            $carModel = new CarModel();
            $info = $carModel->getInfo(
                ['id' => $order['car_id']],
                ['id', 'carbrand', 'carcolor','carnum','cartype']
            );
            $order['car_info'] = $info;
        }
        else
        {
            $order['car_info'] = null;
        }

        //获取服务类型
        if(!empty($order['goods']))
        {
            $goodsModel = new GoodsModel();
            $goods = $goodsModel->getGoodsList($order['goods']);
            foreach ($goods as $key => $val)
            {
                $goods_price = intval($val['goods_price']);
                $new_goods[$key]['id'] = $val['id'];
                $new_goods[$key]['goods_name'] = $val['goods_name'];
                $new_goods[$key]['goods_price'] = $goods_price;
            }
            $order['goods'] = $new_goods;
            /** 水利厅默认便宜5元 start */
            $section_number = $order['section_number'];
            $cartype = $order['car_info']['cartype'];

            if($section_number == '100009' && ($cartype==1 || $cartype == 2)){
                $order['goods'][0]['goods_price'] -= 500;
            }
            /** 水利厅默认便宜5元 end */
        }
        else
        {
            $order['goods'] = null;
        }

        //选择优惠券
        if ($order['coupon_id'] > 0)
        {
            $couponModel = new CouponModel();
            $coupon = $couponModel->getCoupon($order['coupon_id']);
            $order['coupon'] = $coupon;
        }
        else
        {
            $order['coupon'] = null;
        }

        //订单过期时间
        if((($order['createtime']+600) - TIMESTAMP) > 0)
        {
            $order['expirationtime'] = (($order['createtime']+600) - TIMESTAMP);
        }
        else
        {
            $order['expirationtime'] = 0;
        }

        if(intval($order['staff_id']) > 0)
        {
            $staff_info = pdo_get(STAFF,['is_del'=>0,'id'=>$order['staff_id']],['name','phone']);
            $order['staff_info'] = $staff_info;
        }
        else
        {
            $order['staff_info'] = null;
        }
        return ['code' => 0, 'msg' => '', 'data' => $order];
    }

    public function getGoodsByCarId($id)
    {
        if ($id <= 0) return false;
        $sql = "SELECT 
                  id,
                  goods_name,
                  market_price,
                  goods_price 
                FROM
                  " . tablename(GOODS) . "
                WHERE is_del = 0 
                  AND goods_type = 1 
                  AND type_id = 
                  (SELECT 
                    cartype 
                  FROM
                    " . tablename(USER_CAR) . "
                  WHERE id = {$id}) ;";
        return pdo_fetchall($sql);
    }


    //预约洗车
    public function flowOrder($data)
    {
        if($data['section_number'] > 0)
        {
            $section = SectionModel::getSectionInfo($data['section_number']);
            if($section['code'] == 1)
            {
                return $section;
            }
            $shop['section_info'] = $section['data'];
        }
        else
        {
            $shop['section_info'] = null;
            if(!empty($data['latitude']) && !empty($data['longitude']))
            {
                $lat = $data['latitude'];
                $lng = $data['longitude'];
                $sectionList = SectionModel::sectionList([
                    'lat' => $lat,
                    'lng' => $lng,
                    'distance' => 50000, //默认50公里内
                    'page' => 1,
                    'size' => 10
                ]);
                if(!empty($sectionList[2][0]))
                {
                    $shop['section_info'] = $sectionList[2][0];
                }
            }
        }

        //获取默认地址
//        $default_address_id = pdo_getcolumn(USER_ADDRESS,
//            ['user_id'=>$data['user_id'],'default'=>1],
//            'id'
//        );
//        $address_id = $data['address_id'] > 0 ? $data['address_id'] : $default_address_id;
//        if($address_id > 0)
//        {
//            $addressModel = new address();
//            $info = $addressModel->getInfo(['id'=>$address_id]);
//            $shop['address_info'] = $info;
//        }
//        else
//        {
//            $shop['address_info'] = null;
//        }

        $all_price = 0;
        //获取默认车辆
        $default_car_id = pdo_getcolumn(USER_CAR,
            ['user_id' => $data['user_id'], 'default' => 1,'is_del'=>0],
            'id'
        );

        $car_id = $data['car_id'] > 0 ? $data['car_id'] : $default_car_id;
        if ($car_id > 0)
        {
            $carModel = new CarModel();
            $info = $carModel->getInfo(
                ['id' => $car_id , 'is_del' => 0],
                ['id', 'carbrand', 'carcolor','carnum','cartype','username','phone']
            );
            $shop['car_info'] = $info;

            $goods = $this->getGoodsByCarId($car_id);
            if (empty($goods))
            {
                return ['code' => 1, 'msg' => '请在后台添加洗车服务'];
            }
            else
            {
                foreach($goods as $val){$str_goods[] = $val['id'];}
                $data['goods'] = implode(",",$str_goods);
            }
        }
        else
        {
            $shop['car_info'] = null;
        }

        $shop['username'] = $info['username'] ? $info['username'] : null;
        $shop['phone'] = $info['phone'] ? $info['phone'] : null;
        $shop['address'] = $data['address'] ? $data['address'] : null;
        $shop['washtime'] = $data['washtime'] ? $data['washtime'] : TIMESTAMP;

        //获取服务类型
        if(!empty($data['goods']))
        {
            $goodsModel = new GoodsModel();
            $goods = $goodsModel->getGoodsList($data['goods']);
            foreach ($goods as $key => $val)
            {
                $goods_price = intval($val['goods_price']);
                $new_goods[$key]['id'] = $val['id'];
                $new_goods[$key]['goods_name'] = $val['goods_name'];
                $new_goods[$key]['goods_price'] = $goods_price;
                $all_price += $goods_price;
            }

            /** 水利厅默认便宜5元 start */
            $section_number = $shop['section_info']['section_number'];
            $cartype = $shop['car_info']['cartype'];
            if(!empty($section_number) && !empty($cartype))
            {
                if($section_number == '100009' && ($cartype==1 || $cartype == 2)){
                    $all_price -= 500;
                    $new_goods[0]['goods_price'] -= 500;
                }
            }
            /** 水利厅默认便宜5元 end */

            $shop['goods'] = $new_goods;
        }
        else
        {
            $shop['goods'] = null;
        }

        //商家优惠
        $shop['section_activity'] = ['name' => '暂无活动'];

        //选择优惠券
        if ($data['coupon_id'] > 0)
        {
            $couponModel = new CouponModel();
            $coupon = $couponModel->getCoupon($data['coupon_id']);
            if (empty($coupon)) {
                return_msg($coupon['code'], $coupon['msg'], $coupon['data']);
            }
            $shop['coupon'] = $coupon;
            $all_price -= $shop['coupon']['money'];
        }

        /**检测是否已购买月卡 start*/
        if ($car_id > 0) {
            $card = CardModel::userIsBuyCard([
                'user_id' => $data['user_id'],
                'car_id' => $car_id,
            ]);
            if (intval($card) > 0) {
                $all_price = 0;
            }
        }
        /**检测是否已购买月卡 end*/

        if($all_price <= 0)$all_price = 0;
        $shop['all_price'] = $all_price;
        return ['code' => 0, 'msg' => '', 'data' => $shop];
    }

    public function buyMonthCard($params)
    {
        $carModel = new CarModel();
        $info = $carModel->detail($params['car_id'],$params['user_id']);
        if(isset($info[0]))
        {
            return $info;
        }

        //根据不同的车辆类型获取月卡价格
        $sql = " SELECT 
                  id as goods,
                  type_id,
                  goods_img,
                  goods_name,
                  market_price,
                  goods_price,
                  goods_type as order_type,
                  card_type,
                  goods_desc
                FROM
                  ".tablename(GOODS)." 
                WHERE is_del = 0 
                  AND goods_type = 2 
                  AND card_type = 1 
                  AND type_id = {$info['cartype']} LIMIT 1;";
        $result = pdo_fetch($sql);
        $result['car_id'] = self::price_format($info['id']);
        $result['goods'] = self::price_format($result['goods']);
        $result['type_id'] = self::price_format($result['type_id']);
        $result['market_price'] = self::price_format($result['market_price']);
        $result['goods_price'] = self::price_format($result['goods_price']);
        $result['order_type'] = self::price_format($result['order_type']);
        $result['card_type'] = self::price_format($result['card_type']);
        $result['goods_img'] = tomedia($result['goods_img']);
        return [ErrorCode::SUCCESS,'获取成功',$result];
    }

    //添加订单
    public function addOrder($order)
    {
//        $addressModel = new AddressModel();
//        $address = $addressModel->getInfo(['id'=>$order['address_id']],['consignee','phone']);

        if($order['order_type'] == 1)
        {
            if ($order['section_number'] == 0) {
                return_msg(1, '未获取到小区编号');
            } else if (empty($order['address'])) {
                return_msg(1, '请输入您车辆的详细地址');
            } else if (empty($order['washtime'])) {
                return_msg(1, '请选择预约洗车时间');
            } else if ($order['car_id'] === null) {
                return_msg(1, '请添加车辆');
            } else if ($order['goods'] == null) {
                return_msg(1, '请选择服务产品');
            }

            $carModel = new CarModel();
            $car_info = $carModel->getInfo(['id' => $order['car_id']]);
            if ($car_info['username'] == null) {
                return_msg(1, '请在车辆信息中填写您的姓名');
            }
            else if ($car_info['phone'] == null) {
                return_msg(1, '请在车辆信息中填写您的手机号');
            }
            $order['username'] = $car_info['username'] ? $car_info['username'] : null;
            $order['phone'] = $car_info['phone'] ? $car_info['phone'] : null;
            $order['cartype'] = $car_info['cartype'];
        }
        else if($order['order_type'] == 2)
        {
            if (empty($order['goods'])) {
                return_msg(1, '请选择要开通的会员卡');
            }

            if($order['card_type'] == 1 && $order['car_id'] > 0)
            {
                //检测是否已购买月卡
                $card = CardModel::userIsBuyCard([
                    'user_id'=>$order['user_id'],
                    'car_id'=>$order['car_id'],
                ]);
                if(intval($card) > 0)
                {
                    return ['code' => ErrorCode::FAILED, 'msg' => '该车辆已绑定月卡请勿重复购买'];
                }
            }
        }

        $goodsModel = new GoodsModel();
        $goods = $goodsModel->getGoodsList($order['goods']);
        foreach ($goods as $key => $val)
        {
            $goods_price = intval($val['goods_price']);
            $order['price'] += $goods_price;
        }

        //计算价格
        $fee = $this->calc_price($order);
        $order['nowprice'] = $fee;
        unset($order['cartype']);
        $order['createtime'] = TIMESTAMP;
        $order['order_sn'] = $this->get_order_sn();


        /**检测是否已购买月卡 start*/
        if($order['order_type'] == 1 && $order['car_id'] > 0)
        {
            $card = CardModel::userIsBuyCard([
                'user_id' => $order['user_id'],
                'car_id' => $order['car_id'],
            ]);
            if (intval($card) > 0) {
                $order['nowprice'] = 0;
                $order['pay_type'] = 1;
            }
        }
        /**检测是否已购买月卡 end*/
        $result = pdo_insert(ORDER, $order);

        if ($result) {
            $order_id = pdo_insertid();
            $sql = "";
            foreach ($goods as $val)
            {
                /* 插入订单商品 */
                $sql .= " INSERT INTO " . tablename(ORDER_GOODS) . "
                (order_id,goods_id,goods_name,goods_number,goods_price,createtime) VALUES
                (" . $order_id . ", " . $val['id'] . ",'" . $val['goods_name'] . "',1," . $val['goods_price'] . "," . TIMESTAMP . ");";
            }
            pdo_run($sql);
            //更新优惠券
            if($order['coupon_id'] > 0)
            {
                pdo_update(USER_COUPON,
                    ['order_id'=>$order_id,'user_id'=>$order['user_id'],'is_use'=>1,'use_time'=>TIMESTAMP],
                    ['id'=>$order['coupon_id']]
                );
            }

            //插入支付记录
            pdo_insert(PAY_LOG,[
                'user_id'=>$order['user_id'],
                'order_id'=>$order_id,
                'price'=>$order['nowprice'],
                'is_pay'=>0,
                'createtime'=>TIMESTAMP
            ]);

            if ($order['order_type'] == 1)
            {
                //生成订单操作步骤
                pdo_insert(ORDER_ACTION,[
                    'order_id'=>$order_id,
                    'order_status'=> 0,
                    'action_note'=> '订单'.$order['order_sn'].'已创建，等待用户支付',
                    'createtime' => TIMESTAMP
                ]);

                //如果支付金额为0元则执行支付回调
                if($order['nowprice'] == 0)
                {
                    self::payResult(['tid'=>$order_id]);
                }
            }
            //添加会员包月卡记录
            else if($order['order_type'] == 2 && $order['card_type'] == 1)
            {
                pdo_insert(USER_CARD,[
                    'user_id'=>$order['user_id'],
                    'order_id'=>$order_id,
                    'car_id'=>$order['car_id'],
                    'is_pay'=>0,
                    'createtime'=>TIMESTAMP
                ]);
            }

            return ['code' => 0, 'msg' => '下单成功','data'=>['order_id'=>$order_id]];
        }
        return ['code' => 1, 'msg' => '下单失败'];
    }

    /**
     * 得到新订单号
     */
    private function get_order_sn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 计算订单价格
     */
    private function calc_price($order)
    {
        $price = $order['price'];
        if ($order['order_type'] == 1 &&  $order['coupon_id'] > 0) {
            $couponModel = new CouponModel();
            $coupon = $couponModel->getCoupon($order['coupon_id']);
            if ($coupon['code'] == 1) {
                return_msg($coupon['code'], $coupon['msg'], $coupon['data']);
            }
            $price -= $coupon['money'];
        }

        /** 水利厅默认便宜5元 start */
        if($order['section_number'] == '100009' && ($order['cartype']==1 || $order['cartype'] == 2)){
            $price -= 500;
        }
        /** 水利厅默认便宜5元 end */

        if ($price <= 0) {
            $price = 0;
        }
        return $price;
    }

    public static function getLastCarWash($user_id)
    {
        $sql = "SELECT 
              (UNIX_TIMESTAMP(NOW())-createtime) as time
            FROM
              " . tablename(ORDER) . "
            WHERE order_status > 0
              AND order_type = 1 
              AND user_id = {$user_id} 
            ORDER BY createtime DESC
            LIMIT 1 ;";
        $res = Db::query($sql);
        $time = $res[0]['time'];
        if($time && $time < 86400)
        {
            $result = 0;
        }
        elseif($time && $time >= 86400)
        {
            $result = round ($time/86400);
        }
        else
        {
            $result = null;
        }
        return $result;
    }

    public static function payOrder($data)
    {
        if($data['order_id'] == 0)
        {
            return ['code'=>1,'msg'=>'未获取到orderId'];
        }
        else
        {
            // 判断权限
            $order = self::getOrderInfo([
                'id'=>$data['order_id'],
                'user_id'=>$data['user_id'],
            ]);
            if (!$order){
                return ['code'=>1,'msg'=>'非用户订单'];
            }
            else if($order['order_status'] != 0)
            {
                return ['code'=>1,'msg'=>'该订单已支付或已超时'];
            }
            else if(time() > ($order['createtime'] + 600))
            {
                //更新订单状态为已关闭
                pdo_update(ORDER,['order_status'=>-2],['id'=>$data['order_id']]);
                return ['code'=>1,'msg'=>'订单超时已关闭,请重新下单'];
            }

//            $fee = ($order['user_id'] == 2) ? floatval(0.01) : floatval($order['nowprice']/100);

            $data = [
                'tid' => $order['id'], //订单号
//                'fee' => $fee,
                'fee' => floatval($order['nowprice']/100), //支付参数
//                'fee' => floatval(0.01), //支付参数
                'title' => '白龙马洗车服务', //标题
                'openid'=>$data['openid'],
            ];
            return ['code'=>0,'msg'=>'','data'=>$data];
        }
    }


    public static function RefundOrder($params)
    {
        // 判断权限
        $order = self::getOrderInfo([
            'id'=>$params['order_id'],
            'user_id'=>$params['user_id'],
        ]);
        if (!$order){
            return [ErrorCode::FAILED,'非用户订单'];
        }
//        else if($order['order_status'] < 2 )
//        {
//            return [ErrorCode::FAILED,'该订单已完成或已超时'];
//        }

        //首先load模块函数
        load()->model('refund');
        //创建退款订单
        //$tid  模块内订单id
        //$module 需要退款的模块
        //$fee 退款金额
        //$reason 退款原因
        //成功返回退款单id，失败返回error结构错误
        $refund_id = refund_create_order($order['id'], 'carwash');
        if (is_error($refund_id)) {
            return [$refund_id['errno'],$refund_id['message']];
        }
        //发起退款
        $refund_result = refund($refund_id);
        if (is_error($refund_result)) {
            return [$refund_result['errno'],$refund_result['message']];
        } else {
            pdo_update('core_refundlog', array('status' => 1), array('id' => $refund_id));
            //更改用户订单状态为已退款
            pdo_update(ORDER, ['order_status'=>-3], ['id'=>$order['id']]);
            return [ErrorCode::SUCCESS,'退款成功'];
        }
    }


    public static function AutoOrder($order_id)
    {
        $order = pdo_get(ORDER,['id'=>$order_id],['id','order_sn','order_status','section_number']);

        if (empty($order))
        {
            return [ErrorCode::FAILED,'订单不存在'];
        }
        else
        {
            if($order['order_status'] != 1)
            {
                return [ErrorCode::FAILED,'当前订单状态不正确'];
            }

//            $sql = "SELECT
//                      staff.id
//                    FROM
//                      ".tablename(STAFF)." AS staff
//                      LEFT JOIN ".tablename(ORDER)." AS `order`
//                        ON staff.id = order.staff_id
//                        AND order.order_status = 2
//                    WHERE staff.is_del = 0
//                      AND staff.section_number = '{$order['section_number']}'
//                    GROUP BY staff.id
//                    ORDER BY COUNT(order.id) ASC;";
//            $staff_id = pdo_fetchcolumn($sql);

            $StaffQueueModel = new StaffQueueModel($order['section_number']);
            $staff_id = $StaffQueueModel->update();

            if (intval($staff_id) == 0)
            {
                return [ErrorCode::FAILED,'该地区暂时没有洗车员'];
            }
            else
            {
                $where = ['id' => $order['id'], 'order_status' => 1, 'order_type' => 1, 'staff_id' => 0];
                $data = ['staff_id' => $staff_id];
                $res = pdo_update(ORDER, $data, $where);
                if ($res)
                {
                    $staff = pdo_get(STAFF,['id'=>$staff_id]);

                    //生成订单操作步骤
                    pdo_insert(ORDER_ACTION,[
                        'order_id'=>$order['id'],
                        'order_status'=> 1,
                        'action_note'=> '订单'.$order['order_sn'].'已派单，等待洗车员'.$staff['name'].'接单',
                        'createtime' => TIMESTAMP
                    ]);

                    //短信推送至洗车员
                    SmsTalk::sendSmsByStaff($order_id);
                    //微信小程序模板推送至洗车员
                    WechatTalk::sendMsgBystaff($order_id);
                    //钉钉消息推送至运营群
                    DingTalk::push($order_id);

                    return [ErrorCode::SUCCESS,'操作成功'];
                }
                else
                {
                    return [ErrorCode::FAILED,'操作失败'];
                }
            }
        }
    }

    /**
     * 洗车员洗车完毕提交订单
     */
    public static function OrderOperation($params)
    {
        $order_id = $params['order_id'];
        $type = $params['type'];

        if(empty($type))
        {
            return [ErrorCode::FAILED,'未获取到订单类型'];
        }
        if($order_id == 0)
        {
            return [ErrorCode::FAILED,'未获取到订单id'];
        }
        $order = pdo_get(ORDER,['id'=>$order_id]);

        if($order['order_start'] == -1)
        {
            return [ErrorCode::FAILED,'订单已完成或已取消'];
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

    public static function payResult($params)
    {
        $order_id = $params['tid'];
        if (empty($order_id)) {
            return_msg(1, '未获取到订单ID');
        }
        //订单id
        $order = pdo_get(ORDER,
            ['id' => $order_id, 'order_status' => 0,]
        );
        if (empty($order))
        {
            return_msg(1, '订单不存在或已支付');
        }
        else
        {
            /** 如果订单类型为购买包月次卡则生成优惠券 start */
            if ($order['order_type'] == 2)
            {
                if($order['card_type'] == 0)
                {
                    CouponModel::sendConpon([
                        'goods_id' => $order['goods'],
                        'order_type' => $order['order_type'],
                        'user_id'=>$order['user_id'],
                    ]);
                }
                elseif($order['card_type'] == 1)
                {
                    $start_time = TIMESTAMP;
                    $end_time = (strtotime("+1 month",$start_time)-86400);
                    pdo_update(USER_CARD,[
                        'is_pay'=>1,
                        'start_time'=>$start_time,
                        'end_time'=>$end_time,
                    ],[
                        'user_id'=>$order['user_id'],
                        'order_id'=>$order['id'],
                    ]);
                }
            }
            /** 如果订单类型为购买包月次卡则生成优惠券 end */

            /** 更新订单操作 start */
            $result = pdo_update(ORDER,['order_status' => 1,'paytime' => TIMESTAMP,],['id' => $order['id']]);
            //更新支付记录
            pdo_update(PAY_LOG, ['is_pay'=>1],['order_id'=>$order_id]);

            /** 更新订单操作 end */

            /** 自动派单 start */
            if ($order['order_type'] == 1) {

                //生成订单操作步骤
                pdo_insert(ORDER_ACTION,[
                    'order_id'=>$order_id,
                    'order_status'=> 1,
                    'action_note'=> '订单'.$order['order_sn'].'已支付，等待系统派单',
                    'createtime' => TIMESTAMP
                ]);

                $autoOrderRes = self::AutoOrder($order_id);
                if($autoOrderRes[0] > 0)
                {
                    return $autoOrderRes;
                }
                /** 代理商结算 start */
                self::InsertProxyFinanceRecord($order['id']);
                /** 代理商结算 end */
            }
            /** 自动派单 end */
            return ['code'=> $result ? 0 : 1,'msg'=>$result ? '支付成功' : '支付失败'];
        }
    }

    public static function InsertProxyFinanceRecord($order_id)
    {
        $order = pdo_get(ORDER,['id'=>$order_id,'order_type'=>1]);
        if(empty($order))
        {
            return ['code'=> 1,'msg'=>'订单不存在'];
        }
        pdo_begin();
        $sql = "
            SELECT 
              (SELECT 
                proxy_id 
              FROM
                ".tablename(SECTION)." AS s 
              WHERE s.section_number = o.section_number) AS proxy_id,
              (SELECT 
                cartype 
              FROM
                ".tablename(USER_CAR)." AS uc 
              WHERE uc.id = o.car_id) AS cartype 
            FROM
              ".tablename(ORDER)." AS o 
            WHERE o.order_type = 1 AND o.id = {$order['id']};";
        $other = pdo_fetch($sql);
        $sql = "SELECT commission_car{$other['cartype']} as money FROM ".tablename(PROXY)." WHERE id = {$other['proxy_id']};";
        $money = pdo_fetch($sql);
        $order = array_merge($order,$other,$money);
        $result = pdo_query("UPDATE ".tablename(PROXY_FINANCE)." SET money_total = (money_total+{$order['money']}) WHERE proxy_id = {$order['proxy_id']}");
        $result2 = pdo_insert(PROXY_FINANCE_RECORD,[
            'proxy_id' => $order['proxy_id'],
            'order_id' => $order['id'],
            'section_number' => $order['section_number'],
            'staff_id' => $order['staff_id'],
            'user_id' => $order['user_id'],
            'car_id' => $order['car_id'],
            'pay_type' => $order['pay_type'],
            'coupon_id' => $order['coupon_id'],
            'order_money' => $order['nowprice'],
            'money' => $order['money'],
        ]);
        if($result && $result2)
        {
            pdo_commit();
            return ['code'=>ErrorCode::SUCCESS, 'msg' => '操作成功'];
        }
        pdo_rollback();
        return ['code'=>ErrorCode::FAILED, 'msg' => '操作失败'];
    }


    public static function OrderCommentBefore($order_id,$user_id)
    {
        $sql = "SELECT 
                  orders.id,
                  orders.order_sn,
                  orders.donetime,
                  section.section_name,
                  staff.name,
                  staff.photo 
                FROM
                  ".tablename(ORDER)." AS `orders` 
                  LEFT JOIN ".tablename(STAFF)." AS `staff` 
                    ON orders.staff_id = staff.id 
                  LEFT JOIN ".tablename(SECTION)." AS `section` 
                    ON orders.section_number = section.section_number 
                WHERE orders.id = {$order_id} 
                  AND orders.user_id = {$user_id};";
        $result = pdo_fetch($sql);
        if($result)
        {
            $result['donetime'] = date("Y-m-d H:i:s",$result['donetime']);
//            self::price_format($result['goods']);
            return [ErrorCode::SUCCESS,'操作成功',$result];
        }
        return [ErrorCode::FAILED,'获取订单失败，请检查数据'];
    }

    public static function OrderComment($params)
    {
        $order = pdo_get(ORDER,[
            'user_id'=> $params['user_id'],
            'id'=>$params['order_id'],
            'order_type'=>1
        ]);
        if(empty($order))
        {
            return [ErrorCode::FAILED,'订单不存在'];
        }

        if($order['order_status'] == 3)
        {
            if(intval($params['comment_a']) <= 0)
            {
                return [ErrorCode::FAILED,'请为洗车员的服务打分'];
            }
            else if(intval($params['comment_b']) <= 0)
            {
                return [ErrorCode::FAILED,'请为车体清洁打分'];
            }
            else if(intval($params['comment_c']) <= 0)
            {
                return [ErrorCode::FAILED,'请为车窗清洁打分'];
            }
            else if(intval($params['comment_d']) <= 0)
            {
                return [ErrorCode::FAILED,'请为内饰清洁打分'];
            }
            else if(intval($params['comment_e']) <= 0)
            {
                return [ErrorCode::FAILED,'请为轮毂清洁打分'];
            }

            //获取代理商id
            $proxy_id = pdo_getcolumn(SECTION,['section_number'=>$order['section_number']],'proxy_id');
            $proxy_id = !empty($proxy_id) ? $proxy_id : 0;

            $data = [
                'proxy_id' => $proxy_id,
                'section_number'=>$order['section_number'],
                'order_id' => $order['id'],
                'staff_id' => $order['staff_id'],
                'user_id'=>$order['user_id'],
                'comment_a'=> $params['comment_a'],
                'comment_b'=> $params['comment_b'],
                'comment_c'=> $params['comment_c'],
                'comment_d'=> $params['comment_d'],
                'comment_e'=> $params['comment_e'],
                'content'=> $params['content'],
            ];
            $result = pdo_insert(COMMENT,$data);
            if($result)
            {
                pdo_update(ORDER,['order_status'=>4],['id'=>$order['id']]);
                return [ErrorCode::SUCCESS,'操作成功'];
            }
        }
        else
        {
            return [ErrorCode::FAILED,'订单状态不正确'];
        }
        return [ErrorCode::FAILED,'操作失败'];
    }


}
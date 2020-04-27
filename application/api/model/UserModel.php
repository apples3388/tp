<?php
namespace app\api\model;
use think\Db;

class UserModel extends BaseModel
{
    //自定义初始化
    public function __construct()
    {
        parent::__construct();
    }

    public static function checkLogin($session_id)
    {
        if(empty($session_id))
        {
            return [ErrorCode::FAILED,'登录失效,请重新授权'];
        }
        else
        {
            $user = Db::name('user')
                ->where(['is_del'=>0,'session_id'=>$session_id])
                ->field('id,openid,phone,createtime,updatetime,last_login')
                ->find();
            if(empty($user['id']))
            {
                return [ErrorCode::FAILED,'登录失效,请重新授权'];
            }
            else
            {
                return [ErrorCode::SUCCESS,'操作成功',$user];
            }
        }
    }

//    public function validate($data=array(),$fields=array())
//    {
//        if (empty($data))
//        {
//            return_msg(0, '缺少参数');
//        }
//        $info = $this->getInfo($data,$fields);
//        if(!$info)
//        {
//            return_msg(0, '该用户尚未注册');
//        }
//        else
//        {
//            return $info;
//        }
//    }


    /**
     * 检测登陆
     */
    public function authLogin($code='')
    {
        if(!empty($code))
        {
            $post_data['appid'] = $this->appid;
            $post_data['secret'] = $this->secret;
            $post_data['js_code'] = $code;
            $post_data['grant_type'] = 'authorization_code';
            $res = https_request($this->url,$post_data);

            if(isset($res->session_key) && isset($res->openid))
            {
                $openid = trim($res->openid);
                $session_key = trim($res->session_key);
                $this->addEditUser($openid,$session_key);
            }
            else
            {
                return return_msg(1, '',$res);
            }
        }
    }

    public function addEditUser($openid,$session_key)
    {
        $user = pdo_get(USER, ['openid'=>$openid], ['id','phone']);

        $session_id = md5(mt_rand() . $openid);
        if (0 >= intval($user['id']))
        {
            $data['openid'] = $openid;
            $data['session_key'] = $session_key;
            $data['createtime'] = TIMESTAMP;
            $data['session_id'] = $session_id;
            $res = pdo_insert(USER, $data);
            if ($res) {
//                $user_id = pdo_insertid();
                //新用户注册送10元洗车优惠券(有效期1周)
//                CouponModel::sysSendCoupon([
//                    'coupon_id'=> 1,
//                    'user_id'=>$user_id,
//                ]);
                return return_msg(ErrorCode::SUCCESS, '添加用户成功', ['session_id' => $session_id]);
            } else {
                return return_msg(ErrorCode::FAILED, '添加用户失败');
            }
        }
        else if (0 < intval($user['id']))
        {
            $data['session_key'] = $session_key;
            $data['updatetime'] = TIMESTAMP;
            $data['session_id'] = $session_id;
            $res = pdo_update(USER, $data, ['openid' => $openid]);
            if ($res) {
                return return_msg(ErrorCode::SUCCESS, '更新用户成功', ['session_id' => $session_id,'phone'=>$user['phone']]);
            } else {
                return return_msg(ErrorCode::FAILED, '更新用户失败');
            }
        }
        else
        {
            return return_msg(1, '未知错误,请联系管理员');
        }



    }

    public function getPhoneNumber($wx_data)
    {
        $user = $this->validate(['session_id'=>$wx_data['session_id']]);
        $pc = new \WXBizDataCrypt($this->appid, $user['session_key']);
        $errCode = $pc->decryptData($wx_data['encryptedData'], $wx_data['iv'], $data );
        if ($errCode == 0)
        {
            $json = json_decode($data);
            $phone = $json->phoneNumber;
            //判断多个微信号绑定一个手机号
            $is_phone = $this->getField(['phone'=>$phone],'id');
            if($is_phone > 0)
            {
                return ['code'=>1, 'msg'=>'该账号已绑定手机号'.$phone];
            }
            else
            {
                $res = $this->addEditData(
                    ['phone'=>$phone],
                    ['session_id'=>$user['session_id']
                    ]);
                if($res)
                {
                    return ['code'=>0, 'msg'=>'', 'data'=> ['phone'=>$phone]];
                }
                else
                {
                    return ['code'=>1, 'msg'=>'获取失败,请联系管理员'];
                }
            }
        }
        else
        {
            return ['code'=>1,'msg'=>'解码错误'];
        }
    }

    public static function userCenter($data)
    {
        $user_id = $data['user_id'];
        $sql = "SELECT 
              *,
              (SELECT 
                COUNT(id) 
              FROM
                ".tablename(USER_COUPON) ." AS uc 
              WHERE c.id = uc.coupon_id 
                AND uc.user_id = ".$data['user_id'].") AS use_coupon_num 
            FROM
              ".tablename(COUPON) ." AS c 
            WHERE send_type = 0 
            AND c.start_time <= ".TIMESTAMP." 
            AND c.end_time >= ".TIMESTAMP." 
            HAVING use_coupon_num = 0;";
        $coupon_list = pdo_fetchall($sql);
        $coupon_num = count($coupon_list);
        $car_num = pdo_fetchcolumn("SELECT COUNT(id) FROM ".tablename(USER_CAR) ." WHERE is_del = 0 AND user_id = {$user_id} ");
        $data = [
            'field1' => "购买会员更优惠哦",
            'field2' => '查看最新活动',
            'field3' => ($coupon_num+0),
            'field4' => ($car_num+0),
            'field5' => '有问题请反馈给我们',
            'field6' => '9:00-18:00人工客服在线',
            'field7' => [
                'msg'=>'请您认真阅读',
                'url'=> 'staticPage/index.html',
                'is_use' => true,
            ],
        ];
        return [0,'获取成功',$data];
    }

    public static function updateUserInfo($data,$where)
    {
        $result = Db::name(USER)->where($where)->update($data);
        return $result;
    }

    public static function CheckUserInfo($user_id)
    {
        //检查用户是否添加车辆
        $res1 = pdo_getcolumn(USER_CAR,['user_id'=>$user_id,'is_del'=>0],'id');
        $is_car = empty($res1) ? 1 : 0;

        //检车用户是否有未评论订单
        $sql = "SELECT 
                  order.id
                FROM
                  ".tablename(ORDER)." AS `order` 
                  LEFT JOIN ".tablename(COMMENT)." AS `comment` 
                    ON order.id = comment.order_id 
                WHERE order.is_del = 0 
                  AND order.order_status = 3
                  AND ISNULL(comment.id)
                  AND order.user_id = {$user_id} 
                ORDER BY order.id DESC 
                LIMIT 1;";
        $res2 = pdo_fetchcolumn($sql);
        $is_commnet = empty($res2) ? 1 : 0;
        $result['is_car'] = $is_car;
        $result['is_commnet'] = $is_commnet;
        return [ErrorCode::SUCCESS,'操作成功',$result];
    }

}
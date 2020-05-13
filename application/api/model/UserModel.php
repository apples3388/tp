<?php
namespace app\api\model;
use app\common\model\ErrorCode;
use think\Db;
use http\Request;
use wechat\WXBizDataCrypt;

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


    /**
     * 检测登陆
     */
    public static function authLogin($code='')
    {
        if(!empty($code))
        {
            $wxConfig = self::getWxConfig();
            $post_data['appid'] = $wxConfig['userAppid'];
            $post_data['secret'] = $wxConfig['userSecret'];
            $post_data['js_code'] = $code;
            $post_data['grant_type'] = 'authorization_code';
            $result = Request::send($wxConfig['getOpenIdUrl'],$post_data);
            $res = json_decode($result);
            if(isset($res->session_key) && isset($res->openid))
            {
                $openid = trim($res->openid);
                $session_key = trim($res->session_key);
                return self::addEditUser($openid,$session_key);
            }
            else
            {
                return [ErrorCode::FAILED,'操作失败',$res];
            }
        }
        return [ErrorCode::FAILED,'未获取到小程序code'];
    }

    public static function addEditUser($openid,$session_key)
    {
        $user = Db::name(USER)->where(" openid = '{$openid}' ")->field('id,phone')->find();
        $session_id = md5(mt_rand() . $openid);
        if (0 >= intval($user['id']))
        {
            $data['openid'] = $openid;
            $data['session_key'] = $session_key;
            $data['createtime'] = TIMESTAMP;
            $data['session_id'] = $session_id;
            $res = Db::name(USER)->insert($data);
            if ($res) {
//                $user_id = pdo_insertid();
                //新用户注册送10元洗车优惠券(有效期1周)
//                CouponModel::sysSendCoupon([
//                    'coupon_id'=> 1,
//                    'user_id'=>$user_id,
//                ]);
                return [ErrorCode::SUCCESS, '添加用户成功', ['session_id' => $session_id]];
            } else {
                return [ErrorCode::FAILED, '添加用户失败'];
            }
        }
        else if (0 < intval($user['id']))
        {
            $data['session_key'] = $session_key;
            $data['updatetime'] = TIMESTAMP;
            $data['session_id'] = $session_id;
            $res = Db::name(USER)->where(" openid  = '{$openid}' ")->update($data);
            if ($res) {
                return [ErrorCode::SUCCESS, '更新用户成功', ['session_id' => $session_id,'phone'=>$user['phone']]];
            } else {
                return [ErrorCode::FAILED, '更新用户失败'];
            }
        }
        else
        {
            return [ErrorCode::FAILED, '未知错误,请联系管理员'];
        }
    }

    public static function getPhoneNumber($params)
    {
        if (empty($params['iv']) || empty($params['encryptedData'])) {
            return [ErrorCode::FAILED, '缺少参数'];
        }
        $user = Db::name(USER)->where(" id = '{$params['user_id']}' ")->field('id,session_id,session_key')->find();
        if(empty($user))
        {
            return [ErrorCode::FAILED, '该用户尚未注册'];
        }
        $wxConfig = self::getWxConfig();
        $pc = new WXBizDataCrypt($wxConfig['userAppid'], $user['session_key']);
        $errCode = $pc->decryptData($params['encryptedData'], $params['iv'], $data );
        if ($errCode == 0)
        {
            $json = json_decode($data);
            $phone = $json->phoneNumber;
            //判断多个微信号绑定一个手机号
            $is_phone = Db::name(USER)->where(" phone = '{$phone}' ")->column('id');
            if($is_phone > 0)
            {
                return [ErrorCode::FAILED,'该账号已绑定手机号'.$phone];
            }
            else
            {
                $res = Db::name(USER)->where(" session_id  = '{$user['session_id']}' ")->update(['phone'=>$phone]);
                if($res)
                {
                    return [ErrorCode::SUCCESS,'操作成功',['phone'=>$phone]];
                }
                else
                {
                    return [ErrorCode::FAILED,'获取失败,请联系管理员'];
                }
            }
        }
        else
        {
            return [ErrorCode::FAILED,'解码错误'];
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
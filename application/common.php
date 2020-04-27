<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
!defined('USER') && define('USER', 'user');
!defined('USER_CAR') && define('USER_CAR', 'user_car');
!defined('CAR_TYPE') && define('CAR_TYPE', 'car_type');
!defined('SYSTEM') && define('SYSTEM', 'system');
!defined('GOODS') && define('GOODS','goods');
!defined('GOODS_CLASS') && define('GOODS_CLASS','goods_class');
!defined('ORDER') && define('ORDER','order');
!defined('ORDER_GOODS') && define('ORDER_GOODS','order_goods');
!defined('SECTION') && define('SECTION','section');
!defined('COUPON') && define('COUPON','coupon');
!defined('USER_COUPON') && define('USER_COUPON','user_coupon');
!defined('STAFF') && define('STAFF','staff');
!defined('STAFF_FINANCE') && define('STAFF_FINANCE','staff_finance');
!defined('STAFF_WITHDRAW_RECORD') && define('STAFF_WITHDRAW_RECORD','staff_withdraw_record');

!defined('USER_ADDRESS') && define('USER_ADDRESS','user_address');
!defined('ACTIVITY') && define('ACTIVITY','activity');
!defined('PAY_LOG') && define('PAY_LOG','pay_log');
!defined('ADMIN') && define('ADMIN','admin');
!defined('ORDER_ACTION') && define('ORDER_ACTION','order_action');
!defined('USER_CARD') && define('USER_CARD','user_card');
!defined('PROXY') && define('PROXY','proxy');
!defined('PROXY_FINANCE') && define('PROXY_FINANCE','proxy_finance');
!defined('PROXY_FINANCE_RECORD') && define('PROXY_FINANCE_RECORD','proxy_finance_record');
!defined('PROXY_WITHDRAW_RECORD') && define('PROXY_WITHDRAW_RECORD','proxy_withdraw_record');
!defined('CONTACT') && define('CONTACT','contact');
!defined('COMMENT') && define('COMMENT','comment');
!defined('ARTICLE') && define('ARTICLE','article');
!defined('ARTICLE_CAT') && define('ARTICLE_CAT','article_cat');


function printr($data,$isExit=true)
{
    if(empty($data))exit('打印数据为空');
    echo "<pre>";print_r($data);echo "</pre>";
    if($isExit == true)exit();
}


/**
 * 返回信息
 */
function return_msg($code=1,$msg='success',$data=array())
{
    $res['code'] = $code;
    $res['msg'] = $msg;
    if (!empty($data)){$res['data'] = $data;}
    exit(json_encode($res));
}

function tablename($table) {

    $prefix = \think\Config::get('database.prefix');
	if(empty($prefix)) {
        return "`{$table}`";
    }
	return "`{$prefix}{$table}`";
}

function tomedia($image){
    return $image;
}

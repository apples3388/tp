<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [

    //和风天气数据
    'weatherUrl' => 'https://free-api.heweather.net/s6/weather/now?',
    'weatherUser' => 'HE1910291549361002',
    'weatherKey' => '93ea434a80004c5e81a105b01207074a',

    'carType' => ['1'=>'普通小车','2'=> 'SUV','3'=>'商务车'],
    'orderType' => [-3=>'已退款',-2=>'已超时',-1=>'已取消',0=>'待支付',1=>'已支付,派单中',2=>'已接单,清洗中',3=>'已完成',4=>'已评价'],

    //微信配置信息
    'wxConfig'=>[
        /* 用户端数据 start */
        'userAppid' => 'wx44b575394600c582',
        'userSecret' => '888aa6ff731513af36a81b740c2fce0e',
        'getOpenIdUrl'=>'https://api.weixin.qq.com/sns/jscode2session',
        /* 用户端数据 end */
    ]


];

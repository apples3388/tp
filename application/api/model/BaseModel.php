<?php

namespace app\api\model;
use think\Model;


class BaseModel extends Model
{

    protected static function getWxConfig()
    {
        return \think\Config::get('config.wxConfig');
    }

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
    }
}
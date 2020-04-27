<?php
namespace app\api\controller;
use app\api\model\ApiModel;
use app\api\model\OrderModel;
use app\api\model\UserModel;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 显示数据
     * 接口一个名为"index"的接口
     * 响应json串
     */
    public function index()
    {
        $data = [];
        $location = input('location',0);
        if (!empty($location)) {
            $weather = ApiModel::getWeather(['location' => $location]);
        }
        $lastCarWash = OrderModel::getLastCarWash($this->user_id);
        UserModel::updateUserInfo(['last_login'=>TIMESTAMP],['id'=>$this->user_id]);
        $url['url'] = 'attachment/staticPage/employment.html';
        $url['is_use'] = true;
        $data['weather'] = $weather;
        $data['last_car_wash'] = $lastCarWash;
        $data['url'] = $url;
        return_msg(0, '操作成功', $data);
    }








}

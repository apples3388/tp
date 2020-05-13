<?php
namespace app\api\controller;
use app\common\model\ErrorCode;
use app\api\model\ApiModel;
use app\api\model\OrderModel;
use app\api\model\UserModel;
use think\Db;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function authLogin()
    {
        $code = input('code', '');
        $result = UserModel::authLogin($code);
        return_msg($result[0], $result[1], empty($result[2])?'':$result[2]);
    }

    public function GetPhoneNumber()
    {
        $result = UserModel::getPhoneNumber([
            'iv' => input('iv', ''),
            'encryptedData' => input('encryptedData', ''),
            'user_id' => $this->user_id
        ]);
        return_msg($result[0], $result[1], empty($result[2])?'':$result[2]);
    }


    /**
     * 显示数据
     * 接口一个名为"index"的接口
     * 响应json串
     */
    public function index()
    {
        $location = input('location',0);
        if (!empty($location)) {
            $data['weather'] = ApiModel::getWeather(['location' => $location]);
        }
        $data['last_car_wash'] = OrderModel::getLastCarWash($this->user_id);
        UserModel::updateUserInfo(['last_login'=>TIMESTAMP],['id'=>$this->user_id]);
        return_msg(ErrorCode::SUCCESS, '操作成功', $data);
    }








}

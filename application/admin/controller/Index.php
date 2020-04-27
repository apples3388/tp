<?php
namespace app\admin\controller;
use app\admin\model\StatisticalModel;
use app\admin\model\AdminModel;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 管理员登陆
     */
    public function login()
    {
        $username = input('username','');
        $password = input('password','');
        $result = AdminModel::login([
            'username' => $username,
            'password' => $password,
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 首页数据统计
     */
    public function index()
    {
        $result = StatisticalModel::IndexData();
        return_msg($result[0],$result[1],$result[2]);
    }

}

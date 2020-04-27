<?php
namespace app\admin\controller;
use app\admin\model\UserModel;

class User extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 会员列表
     */
    public function userList()
    {
        $result = UserModel::userList([
            'keywords'=>input('keywords',''),
            'page'=>input('page',1),
            'size'=>input('size',10),
        ]);
        return_msg($result[0],$result[1],$result[2]);
    }


}

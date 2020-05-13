<?php
namespace app\api\controller;
use app\api\model\UserModel;
use think\Request;

class Base
{
    public $user;
    public $user_id;
    public $session_id;

    private static $not_check_actions = [
        'authlogin','adposition','contactadd',
    ];

    public function __construct()
    {
        //获取当前请求的name变量
        $request = Request::instance();
        $this->session_id = input('session_id','');
//        $module = $request->module();
//        $controller = $request->controller();
        $action = $request->action();
        if (!empty($action) && !in_array(strtolower($action),self::$not_check_actions))
        {
            $res = UserModel::checkLogin($this->session_id);
            if ($res[0] == 0)
            {
                $this->user = $res[2];
                $this->user_id = $res[2]['id'];
            }
            else
            {
                return_msg($res[0],$res[1]);
            }
        }
    }



}

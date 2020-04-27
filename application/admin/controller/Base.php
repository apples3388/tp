<?php
namespace app\admin\controller;
use app\admin\model\AdminModel;
use app\common\model\ErrorCode;
use think\Request;

class Base
{
    public $user;
    public $user_id;

    private static $not_check_actions = [
        'login','authlogin','adposition','contactadd',
    ];

    public function __construct()
    {
        //获取当前请求的name变量
        $request = Request::instance();
//        $module = $request->module();
//        $controller = $request->controller();
        $action = $request->action();
        if (!empty($action) && !in_array(strtolower($action),self::$not_check_actions))
        {
            $utoken = $request->header('utoken');
            if(empty($utoken))
            {
                return_msg(ErrorCode::FAILED,'未获取到utoken');
            }
            $result = AdminModel::checkLogin($utoken);
            if ($result[0] == 0)
            {
                $this->user = $result[2];
                $this->user_id = $result[2]['id'];
            }
            else
            {
                return_msg($result[0],$result[1]);
            }
        }
    }

}

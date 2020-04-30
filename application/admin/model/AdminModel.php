<?php
namespace app\admin\model;
use app\common\model\ErrorCode;
use think\Db;

class AdminModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function checklogin($token)
    {
        $user = self::getAdmin(['token'=>$token]);
        if(empty($user['id']))
        {
            return [ErrorCode::FAILED,'token失效,请重新登陆授权'];
        }
        else
        {
            return [ErrorCode::SUCCESS,'获取成功',$user];
        }
    }

    public static function login($params)
    {
        $username = trim($params['username']);
        $password = trim($params['password']);
        $info = self::getAdmin(['username'=>$username],'id,token,nickname,password,is_del');
        if(empty($info))
        {
            return [ErrorCode::LOGIN_ACCOUNT_NOT_EXISTS,'帐号不存在'];
        }
        else
        {
            if($info['is_del'] == 1)
            {
                return [ErrorCode::LOGIN_ACCOUNT_DISABLE,'帐号被禁用，请联系管理员'];
            }
            if($password != $info['password'])
            {
                return [ErrorCode::LOGIN_ACCOUNT_PASSWORD_INCORRECT,'帐号或密码错误'];
            }
        }
        $return_data['token'] = $info['token'];
        $return_data['nickname'] = $info['nickname'];
        return [ErrorCode::SUCCESS,'登录成功',$return_data];
    }

    public static function getAdmin($params,$field='id,nickname')
    {
        $info = Db::name(ADMIN)->where($params)->field($field)->find();
//        echo Db::name(ADMIN)->getLastSql();die;
        return $info;
    }


}
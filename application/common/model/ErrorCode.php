<?php

namespace app\common\model;

class ErrorCode
{
    const SUCCESS = 0; //成功
    const FAILED = 1;//失败
//    const FAILED = -1;//失败
    const ERROR = -2;//错误

    const INVALID_PARAMETER = 40001;//非法参数，无效参数
    const INVALID_ACCESS = 40002;//非法访问

    //认证
    const AUTHORIZE_TOKEN_NOT_EXISTS = 10001; //认证令牌不存在
    const AUTHORIZE_TOKEN_VALIDATION_FAILED = 10002; //认证令牌验证失败
    const AUTHORIZE_TOKEN_PARSER_FAILED = 10003; //认证令牌解析失败
    const AUTHORIZE_FAILED = 10004; //认证出现错误
    const AUTHORIZE_IP_ILLEGAL = 10005; //认证IP不合法
    const AUTHORIZE_FREQUENCY_LIMIT = 10006; //超出最大次数调用限制

    //登录
    const LOGIN_ACCOUNT_NOT_EXISTS = 11000; //帐号不存在
    const LOGIN_ACCOUNT_DISABLE = 11001; //帐号禁用
    const LOGIN_ACCOUNT_PASSWORD_INCORRECT = 11002; //帐号或密码错误
    const LOGIN_VERIFY_CODE_EXPIRE = 11010; //验证码失效
    const LOGIN_VERIFY_CODE_INCORRECT = 11011; //验证码错误
    const LOGIN_AGAIN = 11020; //请重新登录

    //消息服务
    const CLIENT_DISCONNECT = 12001; //客户端断开连接
    const VISITOR_ROLE_INCORRECT = 12002; //访问者角色受限

    //订单
    const ORDER_INDUSTRY_ERROR = 20001; //订单类型错误
    const ORDER_PARAMS_DEFECT = 20002; //订单参数丢失
    const ORDER_GENERATE_FAILED = 20003; //订单创建失败
    const ORDER_ABNORMAL = 20004; //订单异常
    const ORDER_BOUNDED = 20005; //订单受限


}

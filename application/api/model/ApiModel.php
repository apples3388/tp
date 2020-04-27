<?php
namespace app\api\model;
use http\Request;

class ApiModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function getWeather($params){
        //准备请求参数（需要替换）
        $data['location'] = $params['location'];
        $config = \think\Config::get('config');
        $data['username'] = $config['weatherUser'];
        $data['t'] = TIMESTAMP;
        $sign = self::getSignature($data, $config['weatherKey']);
        $data['sign'] = $sign;
        $result = Request::send($config['weatherUrl'],$data);
        $res_data = json_decode($result,true);
        $weather = $res_data['HeWeather6'][0];
        if($weather)
        {
            return $weather;
        }
        else
        {
            return [ErrorCode::FAILED,'获取失败'];
        }
    }

    /**
     * 和风天气签名生成算法-PHP版本
     * @param array $params API调用的请求参数集合的关联数组（全部需要传递的参数组成的数组），不包含sign参数
     * @param $secret 用户的认证 key
     * @return string 返回参数签名值
     */
    public static function getSignature($params, $secret)
    {
        $str = '';  //待签名字符串
        //先将参数以其参数名的字典序升序进行排序
        array_filter($params);
        unset($params['sign']);
        unset($params['key']);
        ksort($params);
        //遍历排序后的参数数组中的每一个key/value对
        foreach($params as $k => $v){
            $str .= $k . '=' . $v . '&';
        }
        $str = substr($str,0,strlen($str)-1);
        //将签名密钥拼接到签名字符串最后面
        $str .= $secret;
        //通过md5算法为签名字符串生成一个md5签名，该签名就是我们要追加的sign参数值
        return md5($str);
    }


}
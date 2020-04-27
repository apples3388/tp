<?php
namespace app\admin\model;
use app\common\model\ErrorCode;
use think\Db;

class UserModel extends BaseModel
{
    //自定义初始化
    public function __construct()
    {
        parent::__construct();
    }

    public static function userList($params)
    {
        $where = "  a.is_del = 0 ";
        if($params['keywords']){
            $where .= " AND a.phone LIKE '%{$params['keywords']}%'";
        }

        $total = Db::name(USER)->alias('a')->where($where)->count('id');
        if($total <= 0)
        {
            return [ErrorCode::SUCCESS,'未获取到列表'];
        }

        $sql = "SELECT 
                  id,
                  phone,
                  createtime,
                  last_login,
                  (SELECT 
                    createtime 
                  FROM
                    ".tablename(ORDER)."
                  WHERE user_id = a.id AND order_status >= 3
                  LIMIT 1) AS last_carwash_time,
                  (SELECT 
                    SUM(nowprice) 
                  FROM
                    ".tablename(ORDER)."
                  WHERE user_id = a.id AND order_status >= 3
                  LIMIT 1) AS all_money
                FROM
                    ".tablename(USER)." AS a
                WHERE {$where}
                ORDER BY a.id DESC 
                LIMIT ".(($params['page']-1) * $params['size']).','.$params['size'];
        $list = Db::query($sql);
        foreach($list as $key => $val)
        {
            $section = self::getUserLastCarwashInfo($val['id']);
            $list[$key]['last_carwash_address'] = !empty($section) ? $section['section_name'] : '';
            $list[$key]['last_login'] = ($val['last_login']>0) ? date("Y-m-d H:i:s",$val['last_login']) : '暂无记录';
            $list[$key]['createtime'] = date("Y-m-d H:i:s",$val['createtime']);
            $list[$key]['all_money'] =  intval($val['all_money']);
            $list[$key]['last_carwash_time'] =  !empty($val['last_carwash_time']) ? date("Y-m-d H:i:s",$val['last_carwash_time']) : '暂无记录';
        }
        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }

    public static function getUserLastCarwashInfo($user_id)
    {
        $sql = "SELECT 
                  * 
                FROM
                  ".tablename(SECTION)."
                WHERE section_number = 
                  (SELECT 
                    section_number 
                  FROM
                    ".tablename(ORDER)."
                  WHERE user_id = {$user_id}
                  ORDER BY createtime DESC 
                  LIMIT 1)";
        $result = Db::query($sql);
        if(!empty($result))
        {
            return $result[0];
        }
        return false;
    }

}
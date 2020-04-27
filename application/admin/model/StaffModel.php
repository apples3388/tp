<?php
namespace app\admin\model;
use think\Config;
use think\Db;
use app\common\model\ErrorCode;
use app\common\model\StaffQueueModel;

class StaffModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function staffList($params)
    {
        $where = " a.is_del = 0 ";

        if($params['keywords']){
            $where .= " AND a.name LIKE '%{$params['keywords']}%'";
        }
        $total = Db::name(STAFF)->alias('a')->where($where)->count('id');
        if($total <= 0)
        {
            return [ErrorCode::SUCCESS,'还没有添加洗车员'];
        }

        $sql = "SELECT 
                  a.id,
                  b.section_name,
                  a.name,
                  a.phone,
                  a.photo,
                  a.createtime,
                  (SELECT 
                    COUNT(id) 
                  FROM
                    ".tablename(ORDER)."
                  WHERE staff_id = a.id) AS wash_num,
                  (SELECT 
                    COUNT(id) 
                  FROM
                    ".tablename(ORDER)."
                  WHERE staff_id = a.id 
                    AND order_status = 2) AS is_work 
                FROM
                  ".tablename(STAFF)." AS a 
                  LEFT JOIN ".tablename(SECTION)." AS b 
                    ON a.section_number = b.section_number 
                WHERE {$where} 
                ORDER BY a.id DESC
                LIMIT ".(($params['page']-1) * $params['size']).','.$params['size'];

        $list = Db::query($sql);
        if(!empty($list))
        {
            foreach($list as $key => $val)
            {
//            $photo = !empty($val['photo']) ? $val['photo'] : 'images/global/avatars/avatar_0.jpg';
//            $list[$key]['photo'] = $photo;
                $list[$key]['status'] = ($val['is_work']>0) ? 2 : 0;
            }
        }
        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }


    public static function addEditStaff($params)
    {
        if($params['section_number']==null){
            return [ErrorCode::FAILED,'请选择所属小区'];
        }elseif($params['name']==null){
            return [ErrorCode::FAILED,'请输入名称'];
        }elseif($params['phone']==null){
            return [ErrorCode::FAILED,'请输入手机'];
        }
        else
        {
            if(is_array($params['photo']) && count($params['photo'])){
                $params['photo'] = $params['photo'][0];
            }

            if($params['id'] > 0)
            {
                $where['id'] = $params['id'];
                pdo_update(STAFF, $params, $where);
                return [ErrorCode::SUCCESS,'编辑成功'];
            }
            else
            {
                $params['passwd'] = '123456';
                $params['createtime'] = TIMESTAMP;
                pdo_begin();//开启事务
                $result = pdo_insert(STAFF, $params);
                $staff_id = pdo_insertid();
                $result2 = pdo_insert(STAFF_FINANCE,['staff_id'=>$staff_id]);
                if($result && $result2)
                {
                    pdo_commit();//提交事务

                    $StaffQueueModel = new StaffQueueModel($params['section_number']);
                    $StaffQueueModel->Write($staff_id);
                    return [ErrorCode::SUCCESS,'添加成功'];
                }
                else
                {
                    pdo_rollback();//回滚事务
                }
            }
        }
        return [ErrorCode::FAILED,'操作失败'];
    }

    public static function staffInfo($params)
    {
        if($params['id'] == 0){
            return [ErrorCode::FAILED,'未查询到该条记录'];
        }
        else
        {
            $info = Db::name(STAFF)->where(" id = {$params['id']}")->field('id,section_number,name,phone,photo,status,createtime')->find();
            if(empty($info))
            {
                return [ErrorCode::FAILED,'未查询到该条记录'];
            }
            else
            {
                if($info['photo'])
                {
                    $info['photo'] = tomedia($info['photo']);
                }
            }
        }
        return [ErrorCode::SUCCESS,'操作成功',$info];
    }

    public static function staffDel($staff_id)
    {
        if($staff_id == 0){
            return [ErrorCode::FAILED,'未获取到洗车员id'];
        }
        else
        {
            $res = pdo_update(STAFF, ['is_del'=>1],['id'=>$staff_id]);
            if ($res)
            {
                $section_number = pdo_getcolumn(STAFF,['id'=>$staff_id],'section_number');
                $StaffQueueModel = new StaffQueueModel($section_number);
                $StaffQueueModel->delete($staff_id);
                return [ErrorCode::SUCCESS,'操作成功'];
            }
        }
        return [ErrorCode::FAILED,'操作失败'];
    }

    public static function getStaffsBySection($staff_id)
    {
        if($staff_id<=0)
        {
            return [ErrorCode::FAILED,'未获取到洗车员id'];
        }
        $sql = " 
             SELECT 
              id,`name`,phone
             FROM
              ".tablename(STAFF)."
             WHERE is_del = 0
              AND id <> {$staff_id} 
              AND section_number = (SELECT section_number FROM ".tablename(STAFF)." WHERE id = {$staff_id}); ";
        $res = Db::query($sql);
        return [ErrorCode::SUCCESS,'操作成功',$res];
    }


}
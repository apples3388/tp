<?php
namespace app\admin\controller;
use app\admin\model\StaffModel;

class Staff extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 洗车员列表
     */
    public function staffList()
    {
        $result = StaffModel::staffList([
            'keywords'=>input('keywords',''),
            'page'=>input('page',1),
            'size'=>input('size',10),
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 添加/编辑洗车员
     */
    public function addEditStaff()
    {
        $result = StaffModel::addEditStaff([
            'id'=>input('id',0),
            'section_number'=> input('section_number',0),
            'name' => input('name',''),
            'phone' => input('phone',''),
            'passwd' => input('passwd',''),
            'photo' => input('photo',''),
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 洗车员详情
     */
    public function staffInfo()
    {
        $result = StaffModel::staffInfo([
            'id'=>input('id',0),
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 删除洗车员
     */
    public function staffDel()
    {
        $id = input('id',0);
        $result = StaffModel::staffDel($id);
        return_msg($result[0],$result[1],$result[2]);
    }


}
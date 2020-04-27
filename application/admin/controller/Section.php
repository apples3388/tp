<?php
namespace app\admin\controller;
use app\admin\model\SectionModel;

class Section extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 小区列表
     */
    public function sectionList()
    {
        $result = SectionModel::sectionList([
            'keywords'=>input('keywords',''),
            'page'=>input('page',1),
            'size'=>input('size',10),
        ]);
        return_msg($result[0],$result[1],$result[2]);
    }

    /**
     * 添加/编辑小区
     */
    public function addEditSection()
    {
        $result = SectionModel::addEditSection([
            'id'=>input('id',0),
            'section_img' => input('section_img',''),
            'section_type' => input('section_type',0),
            'section_name' => input('section_name',''),
            'nickname' => input('nickname',''),
            'phone' => input('phone',''),
            'address' => input('address',''),
            'start_time' => input('start_time',''),
            'end_time' => input('end_time',''),
            'lat' => input('lat',''),
            'lng' => input('lng',''),
            'sort' => input('sort',100),
            'province' => input('province',''),
            'city' => input('city',''),
            'district' => input('district',''),
            'tag' => input('tag',''),
        ]);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 小区详情
     */
    public function sectionInfo()
    {
        $id = input('id',0);
        $result = SectionModel::sectionInfo($id);
        return_msg($result[0],$result[1],empty($result[2])?'':$result[2]);
    }

    /**
     * 删除小区
     */
    public function sectionDel()
    {
        $id = input('id',0);
        $result = SectionModel::sectionDel($id);
        return_msg($result[0],$result[1]);
    }


}

<?php

namespace app\admin\model;
use app\common\model\ErrorCode;
use think\Model;
use think\Db;

class BaseModel extends Model
{

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
    }

    /**
     * 添加/编辑数据
     */
    public static function addEditData($table , $params = array(), $where = array())
    {
        if (is_array($where) && count($where)) {
            $res =  Db::name($table)->where($where)->update($params);
            if ($res) {
                return [ErrorCode::SUCCESS,'修改成功'];
            } else {
                return [ErrorCode::FAILED,'修改失败'];
            }
        } else {
            $res = Db::name($table)->insert($params);
            if ($res) {
                return [ErrorCode::SUCCESS,'添加成功'];
            } else {
                return [ErrorCode::FAILED,'添加失败'];
            }
        }
        return [ErrorCode::FAILED,'操作失败'];
    }

}
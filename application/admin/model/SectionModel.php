<?php
namespace app\admin\model;
use app\common\model\ErrorCode;
use think\Db;

class SectionModel extends BaseModel
{
    public function __construct()
    {
    }

    public static function sectionList($params)
    {
        $where = " is_del = 0 ";
        if(!empty($params['keywords'])){
            $where .= " AND section_name LIKE '%{$params['keywords']}%'";
        }
        $total = Db::name(SECTION)->where($where)->count('id');
        if($total <= 0)
        {
            return [ErrorCode::FAILED,'还没有添加小区'];
        }
        $sql = "SELECT 
                  * 
                FROM
                  ".tablename(SECTION)."
                WHERE {$where} 
                ORDER BY sort ASC , id DESC 
                LIMIT ".(($params['page']-1) * $params['size']).','.$params['size'];
        $list = Db::query($sql);
        foreach($list as $key => $val)
        {
            if(!empty($val['section_img']))
            {
                if(strpos($val['section_img'],',')){
                    $section_img = explode(',',$val['section_img']);
                }else{
                    $section_img = [0 => $val['section_img']];
                }
                unset($list[$key]['section_img']);
                foreach($section_img as $k => $v)
                {
                    $list[$key]['section_img'][] = tomedia($v);
                }
            }
            else
            {
                $list[$key]['section_img'] = [];
            }
            $list[$key]['tag'] = !empty($val['tag']) ? explode(",",$val['tag']):false;
        }
        return [ErrorCode::SUCCESS ,'获取列表成功',['total'=>$total,'list'=>$list]];
    }

    public static function addEditSection($params)
    {
        if($params['section_img'])
        {
            if(count($params['section_img'])<4)
            {
                $params['section_img'] = implode(",",$params['section_img']);
            }
            else
            {
                return [ErrorCode::FAILED,'图片最多只能上传三张'];
            }
        }
        else
        {
            $params['section_img']='';
        }

        if($params['section_name'] ==null){
            return [ErrorCode::FAILED,'请输入小区名称'];
        }elseif($params['start_time']==null) {
            return [ErrorCode::FAILED,'请输入营业开始时间'];
        }elseif($params['end_time']==null) {
            return [ErrorCode::FAILED,'请输入营业结束时间'];
        }elseif($params['lat']==null){
            return [ErrorCode::FAILED,'请填写地址并进行定位'];
        }
        else
        {
            if($params['id'] > 0)
            {
                $where['id'] = $params['id'];
            }
            else
            {
                $params['createtime'] = TIMESTAMP;
                $params['section_number'] = self::get_section_number();
                $where = [];
            }
            $result = self::addEditData(SECTION,$params,$where);
        }
        return $result;
    }

    /**
     * 得到新小区编号
     */
    private static function get_section_number()
    {
        $max_number = Db::name(SECTION)->where('')->max('section_number');
        $max_number = ($max_number==0) ? 100001 : $max_number+1;
        return $max_number;
    }

    public static function sectionInfo($id)
    {
        if($id == 0){
            return [ErrorCode::FAILED,'未获取到小区id'];
        }
        else
        {
            $result = Db::name(SECTION)->where(" id = {$id} ")->find();
            if($result['section_img']){
                if(strpos($result['section_img'],',')){
                    $section_img = explode(',',$result['section_img']);
                }else{
                    $section_img = [0 => $result['section_img']];
                }
                foreach($section_img as $key => $val)
                {
                    $section_img[$key] = tomedia($val);
                }
                $result['section_img'] = $section_img;
            }
            if(strpos($result['tag'],','))
            {
                $result['tag'] = explode(",",$result['tag']);
            }
            else
            {
                $result['tag'] = [];
            }
            return [ErrorCode::SUCCESS,'',$result];
        }
        return [ErrorCode::FAILED,'获取信息失败'];
    }

    public static function sectionDel($id)
    {
        if($id == 0){
            return [ErrorCode::FAILED,'未获取到小区id'];
        }
        else
        {
            Db::name(SECTION)->where(" id = {$id} ")->setField('is_del', 1);
            return [ErrorCode::SUCCESS,'操作成功'];;
        }
    }

    public static function selSetction($params=array())
    {
        $where = " WHERE is_del = 0 ";
        $sql = "SELECT 
                  section_number,section_name
                FROM
                  ".tablename(SECTION)."
                {$where} 
                ORDER BY sort ASC , id ASC ";
        $list = Db::query($sql);
        if(empty($list))
        {
            return [ErrorCode::SUCCESS,'暂未添加小区列表'];
        }
        return [ErrorCode::SUCCESS,'操作成功',$list];
    }





}
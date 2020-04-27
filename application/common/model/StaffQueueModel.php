<?php
namespace app\common\model;

class StaffQueueModel {

    public $section_number=0;
    public $path;
    public function __construct($section_number)
    {
        if(empty($section_number))return false;
        $this->section_number = $section_number;
        $this->path = CONF_PATH . $section_number;
    }

    /**
     * 获取小区下的洗车人员id
     */
    public function getStaffBySection()
    {
        $list = pdo_getall(STAFF,['section_number'=>$this->section_number,'is_del'=>0],'id','id','id asc');
        $res = implode(",",array_unique(array_keys($list)));
        return $res;
    }

    public function Write($new_staff=0)
    {
        if(is_file($this->path))
        {
            $list = $this->Read();
            $arr = explode(",",$list);
            $key = array_search(max($arr),$arr);
            array_splice($arr,$key+1,0,$new_staff);
            $list = implode(",",array_unique($arr));
        }
        else
        {
            $list = self::getStaffBySection();
        }
        file_put_contents($this->path,$list);
    }

    public function delete($staff_id)
    {
        $list = explode(",",$this->Read());
        $res = array_values(array_diff($list,[$staff_id]));
        $res2 = implode(",",$res);
        file_put_contents($this->path,$res2);
    }

    public function Read()
    {
        if(!is_file($this->path))return false;
        $res = file_get_contents($this->path);
        return $res;
    }

    /**
     * 检测当前洗车员是否还存在未完成订单
     */
    private function checkStaffOrder($id)
    {
        $sql = "SELECT
                  COUNT(id) AS orders
                FROM
                  ".tablename(ORDER)."
                WHERE is_del = 0
                  AND order_type = 1
                  AND staff_id = {$id}
                  AND order_status = 2;";
        $count = pdo_fetchcolumn($sql);
        return $count ? $count : 0;
    }

    public function update()
    {
        $list = $this->Read();
        $arr = explode(",",$list);
        foreach($arr as $k => $v)
        {
            array_shift($arr);
            array_push($arr,$v);
            $res = $this->checkStaffOrder($v);
            if($res == 0)
            {
                $staff_id = $v;
                break;
            }
        }
        if(empty($staff_id)) return 0;
        file_put_contents($this->path,implode(",",$arr));
        return $staff_id;
    }











}
<?php
namespace app\admin\controller;
use app\admin\model\GoodsModel;

class Goods extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 商品列表
     */
    public function goodsList()
    {
        $result = GoodsModel::GoodsList([
            'keywords'=>input('keywords',''),
            'page'=>input('page',1),
            'size'=>input('size',10),
        ]);
        return_msg($result[0],$result[1],$result[2]);
    }


}

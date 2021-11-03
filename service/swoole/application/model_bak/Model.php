<?php
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Date: 2019-11-28 11:22
// +----------------------------------------------------------------------

namespace app\model_bak;


/**
 * Class Im
 * @package app\model
 */
class Model extends \think\Model
{
    protected bool $autoWriteTimestamp = true;
    protected bool $datetime_format    = false;

    protected array $connection = [
        // 数据集返回类型 array 数组 collection Collection对象
        'resultset_type'  => 'collection',
        // 是否自动写入时间戳字段
        'auto_timestamp'  => true,
        // 时间字段输出的时候会自动进行格式转换
        'datetime_format' => false,
    ];
}


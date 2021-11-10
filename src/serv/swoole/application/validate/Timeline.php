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
// | Date: 2021/4/22 11:03
// +----------------------------------------------------------------------
namespace app\validate;

/**
 * Class System
 * @package app\validate
 */
class Timeline extends Validate
{

    protected array $rule = [
        'offset|起始位置' => 'require|number',
        'length|查询数量' => 'require|number|between:1,15',
    ];

    protected array $scene = [
        'sync' => ['offset', 'length'],
    ];

}

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
// | Version: 2.0 2019-11-27 22:18
// +----------------------------------------------------------------------
namespace app\validate;

/**
 * Class System
 * @package app\validate
 */
class System extends Validate
{

    protected array $rule = [
        'offset|起始位置' => 'require|number',
        'length|查询数量' => 'require|number|between:0,100',
    ];

    protected array $scene = [
        'index' => ['offset', 'length'],
    ];

}

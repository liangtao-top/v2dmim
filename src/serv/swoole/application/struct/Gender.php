<?php
declare(strict_types=1);
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2021/5/26 14:17
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

/**
 * 性别
 * @package app\struct
 */
class Gender extends Enum
{
    // 未知
    const GENDER_UNKNOWN = 0;
    // 男
    const GENDER_MALE = 1;
    // 女
    const GENDER_FEMALE = 2;
}

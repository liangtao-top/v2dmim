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
// | Version: 2.0 2021/4/23 10:45
// +----------------------------------------------------------------------

namespace app\struct;

use com\enum\Enum;

/**
 * 会话类型
 * @package app\struct
 */
class ConvType extends Enum
{
    // 会话类型：C2C(Client to Client, 端到端) 会话
    const CONV_C2C = 1;
    // 会话类型：GROUP(群组) 会话
    const CONV_GROUP = 2;
    // 会话类型：SYSTEM(系统) 会话
    const CONV_SYSTEM = 3;
}

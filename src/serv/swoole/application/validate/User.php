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

use app\model_bak\ImFriendsApply;
use app\model_bak\ImMember;

/**
 * Class User
 * @package app\validate
 */
class User extends Validate
{

    protected array $rule = [
        'sign' => 'require',
        'uuid' => 'require|length:36',
    ];

    protected array $scene = [
        'login' => ['uuid', 'sign'],
    ];

}

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
 * Class Friend
 * @package app\validate
 */
class Friend extends Validate
{

    protected array $rule = [
        'rfr|拒绝理由'         => 'max:255',
        'rfa|申请理由'         => 'max:255',
        'status|状态'        => 'require|in:1,2,3',
        'to_id|ToId'       => 'require|checkToId',
        'apply_id|申请记录ID'  => 'require|checkApplyId',
        'name|搜索关键字'       => 'require',
        'member_id|好友UUID' => 'require|checkToId',
        'nickname|好友昵称'    => 'require|length:1,25',
        'offset|起始位置'      => 'require|number',
        'length|查询数量'      => 'require|number|between:0,100',
    ];

    protected array $scene = [
        'add'         => ['to_id', 'rfa'],
        'delete'      => ['to_id'],
        'applyHandle' => ['apply_id', 'status', 'rfr'],
        'search'      => ['name'],
        'nickname'    => ['member_id', 'nickname'],
        'star'        => ['to_id'],
        'disturb'     => ['to_id'],
        'index'       => ['offset', 'length'],
        'apply'       => ['offset', 'length'],
    ];

    // 自定义验证规则
    protected function checkToId($value)
    {
        $model = new ImMember;
        $count = $model->where('uuid', $value)->count();
        if ($count > 0) {
            return true;
        }
        return 'ToId错误,用户不存在';
    }

    // 自定义验证规则
    protected function checkApplyId($value)
    {
        $model = new ImFriendsApply;
        $count = $model->where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return 'ApplyId错误,申请记录不存在';
    }
}

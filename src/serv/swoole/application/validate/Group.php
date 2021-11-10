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

use app\model_bak\ImGroupChat;
use app\model_bak\ImGroupChatApply;
use app\model_bak\ImGroupChatMember;
use app\model_bak\ImMember;

/**
 * Class Group
 * @package app\validate
 */
class Group extends Validate
{

    protected array $rule = [
        'msg_id|消息ID'            => 'require',
        'group_id|群ID'           => 'require|number|checkGroupId',
        'member_id|群成员ID'        => 'require|number|checkGroupMemberId',
        'nickname|群昵称'           => 'require|length:1,25',
        'status|状态'              => 'require|in:0,1,2,3',
        'to_id|ToId'             => 'require|checkToId',
        'name|搜索关键字'             => 'require',
        'rfr|拒绝理由'               => 'max:255',
        'rfa|申请理由'               => 'max:255',
        'apply_id|申请记录ID'        => 'require|checkApplyId',
        'is_invite|邀请成员'         => 'require|in:0,1',
        'is_mute|禁言权限'           => 'require|in:0,1',
        'is_remove|移除成员'         => 'require|in:0,1',
        'vr_total_people|群虚拟总人数' => 'require|number',
        'vr_online|群虚拟在线人数'      => 'require|number',
        'is_search|是否搜索'         => 'require|in:0,1',
        'disturb|消息免打扰'          => 'require|in:0,1',
        'is_mute_all|全员禁言'   => 'require|in:0,1',
        'shield|屏蔽'              => 'require|in:0,1',
        'group_name|群名称'         => 'require|length:1,120',
        'member_ids|成员UUID'      => 'require|array',
        'offset|起始位置'            => 'require|number',
        'length|查询数量'            => 'require|number|between:0,100',
    ];

    protected array $scene = [
        'applyList'         => ['offset', 'length'],
        'info'              => ['group_id'],
        'recipient'         => ['msg_id', 'offset', 'length'],
        'index'              => ['offset', 'length'],
        'setGroupName'      => ['group_id', 'group_name'],
        'shield'            => ['msg_id', 'shield'],
        'allForbiddenWords' => ['group_id', 'is_mute_all'],
        'disturb'           => ['group_id', 'disturb'],
        'applyMarkAsRead'   => ['apply_id'],
        'nickname'          => ['member_id', 'nickname'],
        'read'              => ['group_id'],
        'singleMember'      => ['group_id', 'to_id'],
        'member'            => ['offset', 'length', 'group_id'],
        'admin'             => ['member_id', 'status'],
        'mute'              => ['member_id', 'status'],
        'delete'            => ['member_id'],
        'out'               => ['group_id'],
        'add'               => ['group_id', 'member_ids'],
        'search'            => ['name'],
        'apply'             => ['group_id', 'rfa'],
        'applyHandle'       => ['apply_id', 'status', 'rfr'],
        'auth'              => ['group_id', 'to_id', 'is_invite', 'is_mute', 'is_remove'],
        'vrTotalPeople'     => ['group_id', 'vr_total_people'],
        'vrOnline'          => ['group_id', 'vr_online'],
        'isSearch'          => ['group_id', 'is_search'],
        'star'              => ['group_id'],
        'create'            => ['member_ids'],
    ];

    // 自定义验证规则
    protected function checkApplyId($value)
    {
        $model = new ImGroupChatApply();
        $count = $model->where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return 'ApplyId错误,申请记录不存在';
    }

    // 自定义UUID验证规则
    protected function checkToId($value)
    {
        $model = new ImMember;
        $count = $model->where('uuid', $value)->count();
        if ($count > 0) {
            return true;
        }
        return 'ToId错误,用户不存在';
    }

    // 自定义GroupId验证规则
    protected function checkGroupId($value, $rule, $data)
    {
        $model = new ImGroupChat();
        $count = $model->where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return '群ID错误,群不存在';
    }

    protected function checkGroupMemberId($value, $rule, $data)
    {

        $model = new ImGroupChatMember();
        $count = $model->where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return '群成员ID错误,成员不存在';
    }

}

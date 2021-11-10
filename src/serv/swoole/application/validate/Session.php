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
use app\model_bak\ImMember;
use app\model_bak\ImSession;
use app\model_bak\ImSystemChat;

/**
 * Class Session
 * @package app\validate
 */
class Session extends Validate
{

    protected array $rule = [
        'session_id|会话ID' => 'require|checkSessionId',
        'cate_id|会话类型'    => 'require|in:1,2,3',
        'to_id|消息接收者ID'   => 'require|checkToId',
        'offset|起始位置'     => 'require|number',
        'length|查询数量'     => 'require|number|between:0,100',
    ];

    protected array $scene = [
        'index'  => ['offset', 'length'],
        'info'   => ['session_id'],
        'create' => ['to_id', 'cate_id'],
        'delete' => ['session_id'],
    ];

    // 自定义会话ID验证规则
    protected function checkSessionId($value)
    {
        $count = ImSession::where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return '会话ID错误,会话不存在';
    }

    // 自定义UUID验证规则
    protected function checkToId($value, $rule, $data)
    {
        unset($rule);
        $cate_id = (int)$data['cate_id'];
        switch ($cate_id) {
            case 1:
                $model = new ImMember;
                $count = $model->where('uuid', $value)->count();
                if ($count > 0) {
                    return true;
                }
                return 'to_id错误,用户不存在';
            case 2:
                $model = new ImGroupChat();
                $count = $model->where('id', $value)->count();
                if ($count > 0) {
                    return true;
                }
                return 'to_id错误,群不存在';
            case 3:
                $model = new ImSystemChat();
                $count = $model->where('id', $value)->count();
                if ($count > 0) {
                    return true;
                }
                return 'to_id错误,系统不存在';
            default:
                return 'cate_id错误,会话类型不存在';
        }
    }

}

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

use app\model_bak\ImSession;

/**
 * Class Message
 * @package app\validate
 */
class Message extends Validate
{

    protected array $rule = [
        'cate_id|会话类型'    => 'require|in:1,2,3',
        'to_id|消息接收者ID'   => 'require',
        'random|消息随机数'    => 'max:25',
        'type_id|消息类型ID'  => 'require|in:1,2,3,4,5',
        'content|消息内容'    => 'require',
        'list_rows|每页条数'  => 'number',
        'page|页码'         => 'number',
        'msg_id|消息ID'     => 'require|number',
        'offset|起始位置'     => 'require|number',
        'length|查询数量'     => 'require|number|between:1,20',
        'session_id|会话ID' => 'require|number|checkSessionId',
    ];

    protected array $scene = [
        'index'    => ['session_id', 'offset', 'length'],
        'send'     => ['cate_id', 'to_id', 'random', 'type_id', 'content'],
        'read'     => ['to_id', 'cate_id'],
        'withdraw' => ['msg_id', 'cate_id'],
        'delete'   => ['msg_id', 'cate_id'],
    ];

    /**
     * 自定义会话ID验证规则
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @throws \think\Exception
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/28 10:19
     */
    protected function checkSessionId($value, $rule, $data): bool|string
    {
        $count = (new ImSession)->where('id', $value)->count();
        if ($count > 0) {
            return true;
        }
        return 'SessionID 值无效，找不到会话';
    }

}

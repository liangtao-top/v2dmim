<?php /** @noinspection PhpDynamicAsStaticMethodCallInspection DuplicatedCode PhpFullyQualifiedNameUsageInspection */
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

use think\exception\DbException;

/**
 * Class ImSession
 * @package app\model
 */
class ImSession extends Model
{

    /**
     * 最后一条消息
     * @param $value
     * @param $data
     * @return ImGroupChatMessageState|ImSingleChatMessageState|ImSystemChatMessageState|null
     * @throws DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/13 12:18
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function getLastMessageAttr($value, $data): ImSystemChatMessageState|ImGroupChatMessageState|ImSingleChatMessageState|null
    {
        $result = null;
        if ($value > 0) {
            $cate_id = (int)$data['cate_id']; // 会话类型ID
            $result  = match ($cate_id) {
                1 => ImSingleChatMessageState::get($value, ['message']),
                2 => ImGroupChatMessageState::get($value, ['message', 'identifier']),
                3 => ImSystemChatMessageState::get($value, ['message']),
            };
        }
        return $result;
    }

}


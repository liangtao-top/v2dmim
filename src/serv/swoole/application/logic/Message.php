<?php /** @noinspection PhpUnusedParameterInspection PhpParameterByRefIsNotUsedAsReferenceInspection PhpFullyQualifiedNameUsageInspection PhpUndefinedMethodInspection DuplicatedCode PhpDynamicAsStaticMethodCallInspection */
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/9/14 14:49
// +----------------------------------------------------------------------

namespace app\logic;


use app\model_bak\ImGroupChatMessage;
use app\model_bak\ImSingleChatMessage;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * 文件业务类
 * Class File
 * @package app\logic
 */
class Message extends Logic
{

    //会话类型：C2C(Client to Client, 端到端) 会话
    const CONV_C2C    = 1;
    const CONV_GROUP  = 2;
    const CONV_SYSTEM = 3;

    //消息类型
    const MSGTYPE_TEXT         = 1; //文本消息
    const MSGTYPE_IMAGE        = 2; //图片消息
    const MSGTYPE_AUDIO        = 3; //音频消息
    const MSGTYPE_FILE         = 4; //文件消息
    const MSGTYPE_VIDEO        = 5; //视频消息
    const MSGTYPE_LOCATION     = 6; //位置消息
    const MSGTYPE_GROUP_NOTICE = 7; //群提示消息
    const MSGTYPE_GROUP_SYS    = 8; //群系统通知消息
    const MSGTYPE_CUSTOM       = 9; //自定义消息
    const MSGTYPE_MERGE        = 10; //合并消息
    const MSGTYPE_EMOJ         = 11; //表情消息
    const MSGTYPE_FORWARD      = 12; //转发消息


    /**
     * 下载合并消息
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/27 16:16
     * @param array $param
     * @param Ws $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downloadMergerMessage(array $param, Ws &$ws, Frame &$frame): bool
    {
        $result          = [];
        $single_chat_ids = $group_chat_ids = [];
        $array           = [];
        foreach ($param as $item) {
            $array[$item['conversationType'] . '_' . $item['ID']] = $item;

            if ($item['conversationType'] == self::CONV_C2C) { //单聊
                $single_chat_ids[] = $item['ID'];

            }
            if ($item['conversationType'] == self::CONV_GROUP) { //群聊
                $group_chat_ids[] = $item['ID'];

            }
        }

        //查询单聊
        $singList = [];
        if (!empty($single_chat_ids)) {
            $model = new ImSingleChatMessage();
            $res   = $model->with('user')->field('id,uuid,cate_id,content')->select($single_chat_ids);
            foreach ($res as $re) {
                $singList[self::CONV_C2C . '_' . $re['id']] = [
                    'nickname' => $re->user->nickname ?? '未知',
                    'avatar'   => $re->user->avatar ?? 0,
                    'uuid'     => $re['uuid'],
                    'cate_id'  => $re['cate_id'],
                    'content'  => json_decode($re['content'], TRUE)
                ];
            }
        }

        //查询群聊
        $groupList = [];
        if (!empty($group_chat_ids)) {
            $model = new ImGroupChatMessage();
            $res   = $model->with('user')->field('id,uuid,cate_id,content')->select($group_chat_ids);
            foreach ($res as $re) {
                $groupList[self::CONV_GROUP . '_' . $re['id']] = [
                    'nickname' => $re->user->nickname ?? '未知',
                    'avatar'   => $re->user->avatar ?? 0,
                    'uuid'     => $re['uuid'],
                    'cate_id'  => $re['cate_id'],
                    'content'  => json_decode($re['content'], TRUE)
                ];
            }
        }

        foreach ($array as $key => $arr) {
            if (isset($singList[$key])) {
                $result[] = $singList[$key];
            }
            if (isset($groupList[$key])) {
                $result[] = $groupList[$key];
            }
        }
        $this->result = $result;
        return true;
    }

}

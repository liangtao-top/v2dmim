<?php /** @noinspection DuplicatedCode PhpUndefinedFieldInspection PhpDynamicAsStaticMethodCallInspection */
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/9/11 17:16
// +----------------------------------------------------------------------

namespace app\logic;

use app\logic\Session as Logic;
use app\model_bak\ImSystemChat;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * Class SystemChat
 * @package app\logic
 */
class SystemChat extends Logic
{

    /**
     * send
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/11 18:01
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function send(array $param, Ws &$ws, Frame &$frame): bool
    {
        // TODO::等待业务需要时开放
        return true;
    }


    /**
     * 检测用户是否存在,防止产生垃圾数据
     * @param $system_id
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 12:05
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function checkSystemId($system_id)
    {
        if (ImSystemChat::where('id', $system_id)->count()) {
            return true;
        }
        $this->error = 'to_id错误,系统不存在';
        return false;
    }
}

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
// | Version: 2.0 2020/9/11 11:40
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Base;
use app\logic\GroupChat;
use app\logic\GroupMessage;
use app\logic\SingleChat;
use app\logic\SingleMessage;
use app\logic\SystemMessage;
use app\model_bak\ImSession;
use app\validate\Message as Validate;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use think\Exception;

/**
 * Class Session
 * @package app\controller
 */
class Message extends Base
{

    /**
     * 我的会话的聊天记录
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/12 21:10
     * @noinspection DuplicatedCode
     */
    public function index(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $session = ImSession::get($data['session_id']);
        $cate_id = (int)$session['cate_id'];
        switch ($cate_id) {
            case 1:
                $logic  = new SingleMessage();
                $result = $logic->sync($param, $session);
                break;
            case 2:
                $logic  = new GroupMessage();
                $result = $logic->sync($param, $session);
                break;
            case 3:
                $logic  = new SystemMessage();
                $result = $logic->sync($param, $session);
                break;
            default:
                throw new Exception('会话类型异常: ' . $cate_id);
        }
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }

    /**
     * 发送消息
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 13:55
     */
    public function send(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $cate_id = (int)$data['cate_id'];
        switch ($cate_id) {
            case 1:
                $logic  = new SingleChat;
                $result = $logic->send($param, $ws, $frame);
                break;
            case 2:
                $logic  = new GroupChat;
                $result = $logic->send($param, $ws, $frame);
                break;
            default:
                throw new Exception('会话类型异常: ' . $cate_id);
        }
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }

    /**
     * 更新消息未已读
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 17:17
     */
    public function read(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid = $param['uuid'];
        $data = $param['data'] ?? [];
        if (!isset($data['session_id']) || empty($data['session_id'])) {
            $this->error = '会话ID不能为空';
            return false;
        }
        $session = ImSession::get($data['session_id']);
        if (empty($session)) {
            $this->error = '会话ID参数错误，会话不存在';
            return false;
        }
        switch ((int)$session['cate_id']) {
            case 1: // 单聊
                $logic  = new SingleMessage;
                $result = $logic->read($uuid, $session, $ws, $frame);
                break;
            case 2: // 群聊
                $logic  = new GroupMessage;
                $result = $logic->read($uuid, $session, $ws, $frame);
                break;
            case 3: // 系统
                $logic  = new SystemMessage;
                $result = $logic->read($uuid, $session, $ws, $frame);
                break;
            default:
                throw new Exception('会话类型异常: ' . $session['cate_id']);
        }
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }

    /**
     * 撤回消息
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/14 20:34
     */
    public function withdraw(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data = $param['data'] ?? [];
        if (!isset($data['msg_id']) || empty($data['msg_id'])) {
            $this->error = 'msg_id参数不能为空';
            return false;
        }
        $cate_id = (int)$data['cate_id']; // 会话类型ID
        switch ($cate_id) {
            case 1: // 单聊
                $logic  = new SingleMessage;
                $result = $logic->withdraw($param, $ws, $frame);
                break;
            case 2: // 群聊
                $logic  = new GroupMessage;
                $result = $logic->withdraw($param, $ws, $frame);
                break;
            case 3: // 系统
                $logic  = new SystemMessage;
                $result = $logic->withdraw($param, $ws, $frame);
                break;
            default:
                throw new Exception('会话类型异常: ' . $cate_id);
        }
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }

    /**
     * 删除消息
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/16 9:11
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data    = $param['data'] ?? [];
        $cate_id = (int)$data['cate_id']; // 会话类型ID
        if (!isset($data['msg_state_id']) || empty($data['msg_state_id'])) {
            $this->error = 'msg_state_id参数不能为空';
            return false;
        }
        switch ($cate_id) {
            case 1: // 单聊
                $logic  = new SingleMessage;
                $result = $logic->delete($param, $ws, $frame);
                break;
            case 2: // 群聊
                $logic  = new GroupMessage;
                $result = $logic->delete($param, $ws, $frame);
                break;
            case 3: // 系统
                $logic  = new SystemMessage;
                $result = $logic->delete($param, $ws, $frame);
                break;
            default:
                throw new Exception('会话类型异常: ' . $cate_id);
        }
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        return true;
    }

    /**
     * 下载合并消息
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/27 8:56
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downloadMergerMessage(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data = $param['data'] ?? [];
        if (empty($data)) {
            $this->error = '参数不能为空';
            return false;
        }
        $logic  = new \app\logic\Message();
        $result = $logic->downloadMergerMessage($data, $ws, $frame);
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }
}

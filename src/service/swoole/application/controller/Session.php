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
use app\logic\Online;
use app\logic\Session as Logic;
use app\model_bak\ImSession;
use app\validate\Session as Validate;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * Class Session
 * @package app\controller
 */
class Session extends Base
{

    /**
     * 我的会话列表
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/12 12:10
     * @noinspection DuplicatedCode
     */
    public function index(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $offset       = $data['offset'] ?? 0;
        $length       = $data['length'] ?? 50;
        $model        = new ImSession;
        $count        = $model->where('uuid', $uuid)->count();
        $list         = $model->where('uuid', $uuid)->order('update_time DESC')
                              ->limit($offset, $length)
                              ->select()
                              ->toArray();
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

    /**
     * 创建会话
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/17 14:02
     * @noinspection PhpUnusedParameterInspection
     */
    public function create(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $to_id   = $data['to_id'];
        $cate_id = $data['cate_id'];
        $logic   = new Logic;
        $result  = $logic->create($cate_id, $uuid, $to_id, true);
        if (!$result) {
            $this->error = $logic->getError();
            return false;
        }
        $this->result = $logic->getResult();
        return true;
    }

    /**
     * 读取单条会话信息
     * @param array                    $param
     * @param \Swoole\WebSocket\Server $ws
     * @param \Swoole\WebSocket\Frame  $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/4/14 13:41
     */
    public function info(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $this->result = (new ImSession)->where('id', $data['session_id'])->find()->toArray();
        return true;
    }

    /**
     * 删除会话
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/15 9:13
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $model = ImSession::get($data['session_id']);
        if ($param['uuid'] !== $model['uuid']) {
            $this->error = '只能删除自己的会话';
            return false;
        }
        $this->result = $model->id;
        $model->delete();
        return true;
    }
}

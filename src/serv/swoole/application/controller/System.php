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
// | Version: 2.0 2020/9/16 16:55
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Base;
use app\model_bak\ImSystemChat;
use app\validate\System as Validate;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * Class System
 * @package app\controller
 */
class System extends Base
{
    /**
     * 系统消息类型列表
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
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $offset       = $data['offset'] ?? 0;
        $length       = $data['length'] ?? 50;
        $model        = new ImSystemChat;
        $count        = $model->count();
        $list         = $model->field('id,name,avatar')->order('id ASC')->limit($offset, $length)->select()->toArray();
        $nextSeq      = $offset + $length;
        $this->result = [
            'list'       => $list,
            'nextSeq'    => $nextSeq,
            'isFinished' => $nextSeq >= $count,
        ];
        return true;
    }

}

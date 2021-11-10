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
use app\validate\Timeline as Validate;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use app\logic\Timeline as TimelineLogic;


/**
 * Class Timeline
 * @package app\controller
 */
class Timeline extends Base
{
    /**
     * 同步
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/12 12:10
     * @noinspection DuplicatedCode
     */
    public function sync(array $param, Ws &$ws, Frame &$frame): bool
    {
        $uuid     = $param['uuid'];
        $data     = $param['data'] ?? [];
        $validate = new Validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $offset = $data['offset'] ?? 0;
        $length = $data['length'] ?? 15;
        // 获取数据
        $start        = $offset;
        $end          = $offset + $length;
        $this->result = TimelineLogic::all($uuid, $start, $end);
        return true;
    }

    /**
     * lastSeq
     * @param array                    $param
     * @param \Swoole\WebSocket\Server $ws
     * @param \Swoole\WebSocket\Frame  $frame
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/14 11:01
     */
    public function lastSeq(array $param, Ws &$ws, Frame &$frame): bool
    {
        $this->result = TimelineLogic::lastSeq( $param['uuid']);
        return true;
    }
}

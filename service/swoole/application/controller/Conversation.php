<?php
declare(strict_types=1);
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2021/5/25 15:34
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Base;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

class Conversation extends Base
{

    public function index(array $param, Ws &$ws, Frame &$frame): bool
    {

        // TODO::待开发
        return true;
    }

    public function create(array $param, Ws &$ws, Frame &$frame): bool
    {

        // TODO::待开发
        return true;
    }

    public function update(array $param, Ws &$ws, Frame &$frame): bool
    {
        // TODO::待开发
        return true;
    }

    public function delete(array $param, Ws &$ws, Frame &$frame): bool
    {
        // TODO::待开发
        return true;
    }

}

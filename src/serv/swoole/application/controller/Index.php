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
// | Version: 2.0 2020-02-26 16:39
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Http;

/**
 * Class Index
 * @package app\controller
 */
class Index extends Http
{
    public function index(): string
    {
        return '<meta http-equiv="content-type" content="text/html; charset=utf-8"><style>*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei",serif; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> DM 聊天系统 <br/><span style="font-size:30px">WebSocket服务 TinkPHP' . THINK_VERSION . ' + Swoole4.6.4</span></p><span style="font-size:22px;">[ 倾情奉献 - 异步 协程 高性能 网络通信引擎 ]</span></div><think id="ad_bd568ce7058a1091" parse="1" style="display: block; overflow: hidden;"><div class="think_default_text"></div></think><script>document.title=\'DM 聊天系统\';</script>';
    }
}

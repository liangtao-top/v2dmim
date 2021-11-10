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
// | Version: 2.0 2020/3/12 18:05
// +----------------------------------------------------------------------

namespace app\controller;

use think\Request;

class Error
{
    public function index(Request $request): string
    {
        return 'Non-existent: \app\controller\\' . $request->controller() . '::' . $request->action();
    }
}

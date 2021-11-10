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
// | Version: 2.0 2021/5/27 13:31
// +----------------------------------------------------------------------

namespace app\common;

abstract class Service
{
    private mixed $error = '';

    private mixed $result = '';

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function getError(): mixed
    {
        return $this->error;
    }

    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}

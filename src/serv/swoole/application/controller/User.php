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
// | Version: 2.0 2020/3/13 10:35
// +----------------------------------------------------------------------

namespace app\controller;

use app\common\Base;
use app\logic\Online;
use app\model_bak\ImMember as Model;
use app\validate\Friend as Validate;
use com\sign\UserSig;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;

/**
 * Class User
 * @package app\controller
 */
class User extends Base
{

    /**
     * login
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/10 17:25
     */
    public function login(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new \app\validate\User();
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $init_time   = 0;
        $expire_time = 0;
        $error_msg   = '';
        $res         = (new UserSig)->verifySig($data['sign'], $data['uuid'], $init_time, $expire_time, $error_msg);
        if (!$res) {
            $this->error = $error_msg;
            return false;
        }
        $find = Model::get($data['uuid'], ['profile']);
        if (is_null($find)) {
            $this->error = 'uuid 参数错误';
            return false;
        }
        if (!$find->status) {
            $this->error = '用户已被禁用';
            return false;
        }
        if (isset($data['device_token']) && !empty($data['device_token'])) {
            $find->device_token = $data['device_token'];
        }
        $find->last_login_time = time();
        $find->save();
        Online::push($ws, $frame, $data['uuid']);
        $this->result = $find->hidden(['password'])->toArray();
        return true;
    }

    /**
     * 退出登录
     * @param array                    $param
     * @param \Swoole\WebSocket\Server $ws
     * @param \Swoole\WebSocket\Frame  $frame
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/4/20 9:01
     * @noinspection PhpUnusedParameterInspection
     */
    public function logout(array $param, Ws &$ws, Frame &$frame): bool
    {
        Online::remove($frame->fd);
        return true;
    }

    /**
     * 查询用户资料
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2021/1/16 13:58
     */
    public function info(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data = $param['data'] ?? [];
        if (!isset($data['uuid'])) {
            $this->error = '预查询uuid必填';
            return false;
        }
        $uuid = $data['uuid'];
        $user = Model::where('uuid', $uuid)->field('account,uuid,surname,name,nickname,sex,avatar')->find();
        if (empty($user)) {
            $this->error = '用户不存在';
            return false;
        }
        $this->result = $user->toArray();
        return true;
    }

    /**
     * 搜索
     * @param array $param
     * @param Ws    $ws
     * @param Frame $frame
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/21 10:17
     */
    public function search(array $param, Ws &$ws, Frame &$frame): bool
    {
        $data     = $param['data'] ?? [];
        $validate = new validate;
        $result   = $validate->scene(__FUNCTION__)->check($data);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        $name         = $data['name'];
        $this->result = [];
        //修改晒选条件，模糊搜索。
        $where['account'] = $name;
        $where['status']  = 1;
        $count            = (new Model)->where($where)->count();
        if ($count > 0) {
            $this->result = (new Model)->where($where)->append(['online'])->field('account,uuid,surname,name,nickname,sex,status,avatar')->limit(0, 1000)->select()->toArray();
        }
        return true;
    }
}

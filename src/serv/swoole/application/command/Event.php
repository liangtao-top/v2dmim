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
// | Version: 2.0 2020-01-19 13:36
// +----------------------------------------------------------------------

namespace app\command;

use app\common\R;
use app\logic\Online;
use app\validate\Server as Validate;
use com\console\Color;
use com\redis\OnlinePool;
use com\sign\TokenSig;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as Ws;
use Throwable;

class Event
{

    /**
     * AccessToken鉴权
     * @param Request  $request
     * @param Response $response
     * @return bool
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/10 13:48
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public static function auth(Request &$request, Response &$response): bool
    {
        if (!isset($request->get['access_token']) || empty($request->get['access_token'])) {
            Color::error("fd：{$request->fd} 缺少access_token参数");
            $response->end();
            return false;
        }
        $token = $request->get['access_token'];
        if (!TokenSig::verify($token)) {
            Color::error("fd：$request->fd AccessToken 鉴权失败");
            return false;
        }
        $device = TokenSig::getDevice($token);
        $redis  = OnlinePool::instance()->get();
        $redis->set('device' . $request->fd, $device);
        OnlinePool::instance()->put($redis);
        return true;
    }

    /**
     * request
     * @param Request  $request
     * @param Response $response
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2020/10/31 13:36
     */
    public static function request(Request &$request, Response &$response)
    {
        // URL 路由
        $uri        = explode('/', trim($request->server['request_uri'], '/'));
        $controller = $uri[0] ?? null;
        $controller = $controller ?: 'Index';
        $controller = '\app\controller\\' . parse_name($controller, 1);
        $action     = parse_name(($uri[1] ?? 'index') ?: 'index', 1, false);
        // 根据 $controller, $action 映射到不同的控制器类和方法
        if (!class_exists($controller)) {
            $response->end(n('Non-existent: ' . $controller . '::class'));
            return;
        }
        $class = new $controller;
        if (!method_exists($class, $action)) {
            $response->end(n('Non-existent: ' . $controller . '::' . $action));
            return;
        }
        try {
            $result = $class->$action($request, $response);
            $response->end($result);
        } catch (Throwable $e) {
            Color::error($e->__toString());
            $response->status(500);
            $response->end(n('Server exception!'));
        }
    }

    /**
     * message
     * @param Ws    $ws
     * @param Frame $frame
     * @return mixed
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/9/14 16:33
     */
    public static function message(Ws &$ws, Frame &$frame): mixed
    {
        if (empty($frame->data) || !is_json($frame->data)) {
            Color::error('数据格式异常');
            return $ws->push($frame->fd, R::e(\app\struct\Event::EXCEPTION_WRONG_FORMAT, $frame->data));
        }
        $params   = json_decode($frame->data, true);
        $validate = new Validate;
        if (!$validate->check($params)) {
            Color::error((string)$validate->getError());
            return $ws->push($frame->fd, R::n($params['route'], $validate->getError(), '', ['state' => $params['state']]));
        }
        Color::log($params['route'] . (isset($params['data']) ? ' ' . json_encode($params['data'], JSON_UNESCAPED_UNICODE) : ''));
        $tmp_arr    = explode('.', $params['route']);
        $action     = isset($tmp_arr[1]) ? parse_name($tmp_arr[1], 1, false) : 'index';
        $controller = '\app\controller\\' . parse_name($tmp_arr[0], 1, true);
        unset($tmp_arr);
        // 根据 $controller, $action 映射到不同的控制器类和方法
        if (!class_exists($controller)) {
            $msg = 'Non-existent: ' . $controller . '::class';
            Color::error($msg);
            return $ws->push($frame->fd, R::n($params['route'], $msg, '', ['state' => $params['state']]));
        }
        $class = new $controller;
        if (!method_exists($class, $action)) {
            $msg = 'Non-existent: ' . $controller . '::' . $action;
            Color::error($msg);
            return $ws->push($frame->fd, R::n($params['route'], $msg, '', ['state' => $params['state']]));
        }
        try {
            $online = Online::get($frame->fd);
            if ($online) {
                $params['uuid'] = $online['uuid'];
            }
            $result = $class->$action($params, $ws, $frame);
            if (!$result) {
                Color::error((string)$class->getError());
                $data = R::n($params['route'], $class->getError(), '', ['state' => $params['state']]);
            } else {
                $data = R::y($params['route'], $class->getResult(), 'success', ['state' => $params['state']]);
            }
        } catch (Throwable $e) {
            $data = R::n($params['route'], $e->getMessage(), '', ['state' => $params['state']]);
            Color::error($e->__toString());
        }
        return $ws->push($frame->fd, $data);
    }

    /**
     * task
     * @param Ws $ws
     * @param    $task_id
     * @param    $from_id
     * @param    $data
     * @return mixed
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/28 11:53
     * @noinspection DuplicatedCode
     */
    public static function task(Ws &$ws, $task_id, $from_id, $data): bool
    {
        if (empty($data) || !is_array($data) || !isset($data['class']) || !isset($data['method'])) {
            Color::error('数据异常');
            return false;
        }
        $class  = $data['class'];
        $method = $data['method'] . 'Before';
        if (!class_exists($class)) {
            Color::warning('Non-existent: ' . $class . '::class');
            return false;
        }
        $controller = new $class;
        if (!method_exists($controller, $method)) {
            Color::warning('Non-existent: ' . $class . '::' . $method);
            return false;
        }
        try {
            if (isset($data['arg'])) {
                $arg = $data['arg'];
                if (!is_array($arg)) {
                    return $controller->$method($ws, $arg);
                }
                array_unshift($arg, $ws);
                return call_user_func_array([$controller, $method], $arg);
            }
            return $controller->$method($ws);
        } catch (Throwable $e) {
            Color::error($e->__toString());
            return false;
        }
    }

    /**
     * finish
     * @param Ws $ws
     * @param    $task_id
     * @param    $data
     * @return mixed
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2020/10/28 17:23
     * @noinspection DuplicatedCode
     */
    public static function finish(Ws &$ws, $task_id, $data): mixed
    {
        if (empty($data) || !is_array($data) || !isset($data['class']) || !isset($data['method'])) {
            Color::error('数据异常');
            return false;
        }
        $class  = $data['class'];
        $method = $data['method'] . 'After';
        if (!class_exists($class)) {
            Color::warning('Non-existent: ' . $class . '::class');
            return false;
        }
        $controller = new $class;
        if (!method_exists($controller, $method)) {
            Color::warning('Non-existent: ' . $class . '::' . $method);
            return false;
        }
        try {
            if (isset($data['arg'])) {
                $arg = $data['arg'];
                if (!is_array($arg)) {
                    return $controller->$method($ws, $arg);
                }
                array_unshift($arg, $ws);
                return call_user_func_array([$controller, $method], $arg);
            }
            return $controller->$method($ws);
        } catch (Throwable $e) {
            Color::error($e->__toString());
            return false;
        }
    }

    /**
     * WebSocket连接关闭事件
     * @param Ws  $ws
     * @param int $fd
     * @author       TaoGe <liangtao.gz@foxmail.com>
     * @date         2019-11-29 20:36
     * @noinspection PhpUnusedParameterInspection
     */
    public static function close(Ws &$ws, int $fd)
    {
        Online::remove($fd);
        $redis = OnlinePool::instance()->get();
        $redis->del('device' . $fd);
        OnlinePool::instance()->put($redis);
        Color::log("fd：{$fd} 连接关闭");
    }
}

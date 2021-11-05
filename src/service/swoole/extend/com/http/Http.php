<?php
/**
 * 描述
 * Created by: PhpStorm
 * User: zmq <zmq3821@163.com>
 * Date: 2021/5/21
 */

namespace com\http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\Config;

class Http
{
    protected mixed $error = '';

    protected mixed $result = '';

    /**
     * @var \GuzzleHttp\Client
     */
    private \GuzzleHttp\Client $client;

    /**
     * Http constructor.
     */
    public function __construct(array $config = [])
    {
        $httpConfig = config('http');
        $this->client = new Client(array_merge($httpConfig, $config));
    }

    public function get(string $uri, array $params, array $header = []): bool
    {
        try {
            $options = ['query' => $params];
            if (!empty($header)) {
                $options['headers'] = $header;
            }
            $response = $this->client->get($uri, $options);
        } catch (GuzzleException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $this->parser($response);
    }

    /**
     * post
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/21 13:55
     * @param string $uri
     * @param array $params
     * @param array $header
     * @return bool
     */
    public function post(string $uri, array $params, array $header = []): bool
    {
        try {
            $options['form_params'] = $params;
            if (!empty($header)) {
                $options['headers'] = $header;
            }
            $response = $this->client->post($uri, $options);
        } catch (GuzzleException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $this->parser($response);
    }

    public function delete(string $uri, array $params, array $header = []): bool
    {
        trace("DELETE {$this->config->uc_api}{$uri}", 'debug');
        try {
            $options = ['json' => $params];
            if (!empty($header)) {
                $options['headers'] = $header;
            }
            $response = $this->client->delete($uri, $options);
        } catch (GuzzleException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $this->parser($response);
    }

    public function put(string $uri, array $params, array $header = []): bool
    {
        trace("PUT {$this->config->uc_api}{$uri}", 'debug');
        try {
            $options = ['json' => $params];
            if (!empty($header)) {
                $options['headers'] = $header;
            }
            $response = $this->client->put($uri, $options);
        } catch (GuzzleException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $this->parser($response);
    }

    private function parser(&$response): bool
    {
        if ($response->getStatusCode() !== 200) {
            $this->error = 'UCenter服务器响应异常，StatusCode：' . $response->getStatusCode() . '！';
            return false;
        }
        $json_str = $response->getBody()->getContents(); //获取响应体
        if (!is_array($json_str)) {
            $body = json_decode($json_str, true); // 字符串转json
        } else {
            $body = $json_str;
        }
        if ($body === false) {
            $this->error = 'UCenter服务器响应异常，Content-Type：' . implode(';', $response->getHeader('Content-Type')) . '！';
            return false;
        }
        $this->result = $body;
        return true;
    }

    public function getError(): mixed
    {
        return $this->error;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

}
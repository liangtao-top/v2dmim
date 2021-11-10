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
// | Version: 2.0 2021/5/10 9:38
// +----------------------------------------------------------------------

namespace app\common;

use ArrayAccess;
use JsonSerializable;
use ReflectionClass;

abstract class Struct implements JsonSerializable, ArrayAccess
{

    /**
     * Struct constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $ref = new ReflectionClass($this);
        foreach ($data as $key => $value) {
            if ($ref->hasProperty($key)) {
                $property = $ref->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($this, $value);
            }
        }
    }

    /**
     * 转换当前对象为Array数组
     * @return array
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 11:26
     */
    public function toArray(): array
    {
        $ref   = new ReflectionClass($this);
        $array = [];
        foreach ($ref->getProperties() as $value) {
            $value->setAccessible(true);
            $array[$value->getName()] = $value->getValue($this);
        }
        return $array;
    }

    /**
     * 转换当前对象为JSON字符串
     * @param int $options
     * @return string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 11:26
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * 返回能被 json_encode() 序列化的数据， 这个值可以是除了 resource 外的任意类型。
     * @return array
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 11:30
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 检查一个偏移位置是否存在
     * @param mixed $offset
     * @return bool
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 9:36
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * 获取一个偏移位置的值
     * @param mixed $offset
     * @return mixed
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 9:37
     */
    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    /**
     *  设置一个偏移位置的值
     * @param mixed $offset
     * @param mixed $value
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 9:37
     */
    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    /**
     * 复位一个偏移位置的值
     * @param mixed $offset
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 9:37
     */
    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }

    /**
     * __toString
     * @return string
     * @author TaoGe <liangtao.gz@foxmail.com>
     * @date   2021/5/10 11:27
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

}

V2DMIM
===============

> 运行环境要求Docker Version 20.10.5+，docker-compose version 1.29.1+。

V2DMIM V1.0版本由[艾邦智汇](https://www.cdabon.com/)独家发布。

## 主要新特性

* 采用`PHP8`强类型（严格模式）
* 支持更多的`PSR`规范
* 原生多应用支持
* 更强大和易用的查询
* 全新的事件系统
* 模型事件和数据库事件统一纳入事件系统
* 模板引擎分离出核心
* 内部功能中间件化
* 对Swoole以及协程支持改进
* 对IDE更加友好
* 统一和精简大量用法

## 安装

~~~
docker-compose up --build
~~~
清空日志
~~~
truncate -s 0 /var/lib/docker/containers/*/*-json.log
~~~

## 文档

[完全开发手册](https://www.kancloud.cn)

## 参与开发

请参阅 [V2DMIM 核心框架包](https://github.com)。

## 版权信息

V2DMIM遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2020-2021 by V2DMIM (http://www.cdabon.com)

All rights reserved。

V2DMIM® 商标和著作权所有者为艾邦智汇（成都）科技有限公司。

更多细节参阅 [LICENSE.txt](LICENSE.txt)

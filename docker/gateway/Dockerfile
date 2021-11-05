# 基础镜像
FROM phpswoole/swoole:latest-alpine

# 作者信息
MAINTAINER TaoGe <liangtao.gz@foxmail.com>

# 更新下 apk 的源
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories
RUN apk update && apk upgrade
RUN apk add m4 make autoconf gcc g++ linux-headers pcre
RUN apk add --no-cache bash tzdata \
    && ln -snf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone \
    && pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis
#开启扩展
RUN docker-php-ext-install pdo_mysql
#查看扩展
RUN php -m && php -v && composer --version && php --ri swoole

# 环境变量
ENV TZ Asia/Shanghai
ENV LANG en_US.UTF-8

# 切换工作目录
WORKDIR /v2dmin

CMD [ "php", "/v2dmin/src/gateway/service" ]

# 使用 Dockerfile 创建镜像 im-std-swoole 版本号为 1.0.0
# docker build -t im-std-swoole:1.0.0 .

# 查看镜像
# docker images

# 挂载目录
# -v /www/im-std.docker.abontest.com:/apps/site

# 链接容器
# --link im-std-mysql-runtime_1.0.0:mysql

# 创建容器
# docker run -it --rm --name im-std-swoole_1.0.0 im-std-swoole:1.0.0 sh

# 创建运行时容器
# docker run --link im-std-mysql-runtime_1.0.0:mysql -v /www/im-std.docker.abontest.com:/apps/site -it --rm --name im-std-swoole-runtime_1.0.0 -p 9502:9502 im-std-swoole:1.0.0

# 查看活动的容器
# docker ps

# 停止容器
# docker stop im-std-swoole-runtime_1.0.0

# 进入容器
# docker exec -it -u root im-std-swoole-runtime_1.0.0 sh

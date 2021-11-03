# 基础镜像
FROM php:8.0.3-fpm-alpine

# 作者信息
MAINTAINER TaoGe <liangtao.gz@foxmail.com>

# 更新下 apk 的源
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories
RUN apk update && apk upgrade
RUN apk add m4 make autoconf gcc g++ linux-headers pcre
RUN apk add --no-cache bash tzdata \
    && ln -snf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone
RUN docker-php-ext-install pdo_mysql
RUN chmod -R 777 /var/www/html

# 添加gd扩展
RUN apk add libpng-dev freetype-dev libjpeg-turbo-dev \
   && docker-php-ext-configure gd --with-freetype --with-jpeg \
   && docker-php-ext-install -j$(nproc) gd

# 环境变量
ENV TZ Asia/Shanghai

# 拷贝配置文件
COPY certs /usr/local/etc/php/certs
COPY conf.d/* /usr/local/etc/php/conf.d/

# 切换工作目录
WORKDIR /var/www/html

# 使用 Dockerfile 创建镜像 im-std-php8 版本号为 1.0.0
# docker build -t im-std-php8:1.0.0 .

# 查看镜像
# docker images

# 挂载目录
# -v /www/im-std.docker.abontest.com:/var/www/html
# -v /www/im-std.docker.abontest.com/docker/php8/php.ini:/usr/local/etc/php/php.ini:ro

# 链接容器
# --link im-std-mysql-runtime_1.0.0:mysql

# 创建容器
# docker run --link im-std-mysql-runtime_1.0.0:mysql -v /www/im-std.docker.abontest.com:/var/www/html -it --rm --name im-std-php8_1.0.0 im-std-php8:1.0.0

# 拷贝php官方镜像的php.ini-production到本地
# docker cp im-std-php8_1.0.0:/usr/local/etc/php/php.ini-production /www/im-std.docker.abontest.com/docker/php8/php.ini

# 查看活动的容器
# docker ps

# 进入容器
# docker exec -it -u root im-std-php8_1.0.0 sh

# 停止容器
# docker stop im-std-php8_1.0.0

# 查看日志
# docker logs im-std-php8_1.0.0

# 访问 php 应用
# curl -X GET http://localhost:8081/index.php

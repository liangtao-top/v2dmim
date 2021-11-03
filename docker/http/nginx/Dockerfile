# 基础镜像
FROM nginx:alpine

# 作者信息
MAINTAINER TaoGe <liangtao.gz@foxmail.com>

# 更新下 apk 的源
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories
RUN apk update && apk upgrade
RUN apk add --no-cache bash tzdata \
    && ln -snf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone
# 环境变量
ENV TZ Asia/Shanghai

# 复制文件
#COPY /www/im-std.docker.abontest.com /apps/site
#COPY /www/im-std.docker.abontest.com/docker/nginx/conf/nginx.conf /etc/nginx/nginx.conf

# 切换工作目录
WORKDIR /v2dmim

# 设置容器启动时执行的命令
#CMD [""]

# 使用 Dockerfile 创建镜像 im-std-nginx 版本号为 1.0.0
# docker build -t im-std-nginx:1.0.0 .

# 查看镜像
# docker images

# 挂载目录
# -v /www/im-std.docker.abontest.com:/apps/site
# -v /www/im-std.docker.abontest.com/docker/nginx/logs:/var/log/nginx
# -v /www/im-std.docker.abontest.com/docker/nginx/conf.d:/etc/nginx/conf.d:ro
# -v /www/im-std.docker.abontest.com/docker/nginx/php.conf:/etc/nginx/php.conf:ro
# -v /www/im-std.docker.abontest.com/docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
# -v /www/im-std.docker.abontest.com/docker/nginx/rewrite.conf:/etc/nginx/rewrite.conf:ro

# 链接容器
# --link im-std-php8_1.0.0:php

# 创建运行时容器
# docker run --link im-std-php8_1.0.0:php -v /www/im-std.docker.abontest.com/docker/nginx/rewrite.conf:/etc/nginx/rewrite.conf:ro -v /www/im-std.docker.abontest.com:/apps/site -v /www/im-std.docker.abontest.com/docker/nginx/logs:/var/log/nginx -v /www/im-std.docker.abontest.com/docker/nginx/conf.d:/etc/nginx/conf.d:ro -v /www/im-std.docker.abontest.com/docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro -v /www/im-std.docker.abontest.com/docker/nginx/php.conf:/etc/nginx/php.conf:ro -it --rm --name im-std-nginx-runtime_1.0.0 -p 80:80 im-std-nginx:1.0.0

# 拷贝nginx官方镜像的nginx.conf到本地
# docker cp im-std-nginx-runtime_1.0.0:/etc/nginx/conf.d /www/im-std.docker.abontest.com/docker/nginx/conf.d
# docker cp im-std-nginx-runtime_1.0.0:/etc/nginx/nginx.conf /www/im-std.docker.abontest.com/docker/nginx/nginx.conf

# 查看活动的容器
# docker ps

# 重启容器
# docker restart  im-std-nginx-runtime_1.0.0

# 停止容器
# docker stop im-std-nginx-runtime_1.0.0

# 查看日志
# docker logs im-std-nginx-runtime_1.0.0

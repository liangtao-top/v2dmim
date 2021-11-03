# 基础镜像
FROM redis:6.2.1-alpine3.13

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
ENV TIME_ZONE Asia/Shanghai

# 使用 Dockerfile 创建镜像 im-std-redis 版本号为 1.0.0
# docker build -t im-std-redis:1.0.0 .

# 查看镜像
# docker images

# 挂载目录
# -v /www/im-std.docker.abontest.com/docker/redis/data:/data
# -v /www/im-std.docker.abontest.com/docker/redis/conf:/etc/redis/redis.conf:ro

# 创建容器
# docker run -v /www/im-std.docker.abontest.com/docker/redis/conf:/etc/redis/redis.conf:ro -v /www/im-std.docker.abontest.com/docker/redis/data:/data -it --rm --name im-std-redis_1.0.0 -p 6379:6379 --sysctl net.core.somaxconn=1024 im-std-redis:1.0.0 /etc/redis/redis.conf

# 拷贝redis官方镜像的redis.conf到本地
# docker cp im-std-redis_1.0.0:/usr/local/etc/redis/redis.conf /www/im-std.docker.abontest.com/docker/redis/redis.conf

# 查看活动的容器
# docker ps

# 进入容器
# docker exec -it -u root im-std-redis_1.0.0 sh

# 停止容器
# docker stop im-std-redis_1.0.0

# 查看日志
# docker logs im-std-redis_1.0.0

# 基础镜像
FROM mysql:5.7.33

# 作者信息
MAINTAINER TaoGe <liangtao.gz@foxmail.com>

# 环境变量
ENV MYSQL_ROOT_PASSWORD pEPCYAl99IE7iLve
ENV MYSQL_DATABASE im-std-db
ENV TZ Asia/Shanghai
ENV LANG en_US.UTF-8

# 拷贝配置文件
COPY conf.d/* /etc/mysql/conf.d/
RUN chmod a-w /etc/mysql/conf.d/*

# 设置容器启动时执行的命令
CMD ["--innodb-use-native-aio=0"]

# 使用 Dockerfile 创建镜像 im-std-mysql 版本号为 1.0.0
# docker build -t im-std-mysql:1.0.0 .

# 查看镜像
# docker images

# 挂载目录
# -v /www/im-std.docker.abontest.com/docker/mysql/data:/var/lib/mysql

# 创建运行时容器
# docker run -v /www/im-std.docker.abontest.com/docker/mysql/data:/var/lib/mysql -it --rm --name im-std-mysql-runtime_1.0.0 -p 3306:3306 im-std-mysql:1.0.0

# 查看活动的容器
# docker ps

# 进入容器
# docker exec -it -u root im-std-mysql-runtime_1.0.0 sh

# 拷贝mysql官方镜像的my.cnf到本地
# docker cp im-std-mysql-runtime_1.0.0:/etc/mysql/conf.d /c/www/im-std.docker.abontest.com/docker/mysql/conf.d

# 停止容器
# docker stop im-std-mysql-runtime_1.0.0

# 查看日志
# docker logs im-std-mysql-runtime_1.0.0

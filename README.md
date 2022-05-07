V2DMIM
===============
> 运行环境要求Docker Version 20.10.5+，docker-compose version 1.29.1+。

V2DMIM V1.0版本由[艾邦智汇](https://www.cdabon.com/)独家发布。

## 运行

### step 1: 拉取代码

~~~
git clone git@github.com:liangtao-top/v2dmim.git --recursive
~~~

### step 2: 编译启动

~~~
cd v2dmim-server
docker-compose up --build --remove-orphans
~~~

## CentOS8 yum 源
>CentOS Linux 8在2022年12月31日来到生命周期终点（End of Life，EoL）。即CentOS Linux 8操作系统版本结束了生命周期（EOL），Linux社区已不再维护该操作系统版本。所以原来的CentOS
Linux 8的yum源也都失效了！阿里云服务器不需要更换，本地服务器需要设置：
~~~
sudo rm -rf /etc/yum.repos.d/*
sudo wget -O /etc/yum.repos.d/CentOS-Base.repo https://mirrors.aliyun.com/repo/Centos-vault-8.5.2111.repo
sudo yum makecache
~~~

## 安装／升级Docker客户端

### step 1: 安装必要的一些系统工具

~~~
sudo yum install -y yum-utils device-mapper-persistent-data lvm2
~~~

### Step 2: 添加软件源信息

~~~
sudo yum-config-manager --add-repo http://mirrors.aliyun.com/docker-ce/linux/centos/docker-ce.repo
~~~

### Step 3: 更新并安装 Docker-CE

~~~
sudo yum makecache
sudo yum -y install --allowerasing docker-ce
~~~

### Step 4: 安装 docker-compose

~~~
sudo curl -L "https://get.daocloud.io/docker/compose/releases/download/v2.1.1/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
~~~

### Step 5: 开启Docker服务，并设置开机自启

~~~
sudo service docker start
sudo systemctl enable docker
~~~

## 配置Docker镜像加速器

您可以通过修改daemon配置文件/etc/docker/daemon.json来使用加速器
https://tvtv.fun/mirrors-list.html

~~~
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json <<-'EOF'
{"registry-mirrors": ["https://hub-mirror.c.163.com"]}
EOF
sudo systemctl daemon-reload
sudo systemctl restart docker
docker info
~~~

如果加速后依然很慢修改DNS配置为 8.8.8.8 https://blog.csdn.net/yinfang_11/article/details/123408991

## 需要先改的配置

~~~
sudo echo "vm.max_map_count=262144">>/etc/sysctl.conf 
sudo cat /etc/sysctl.conf 
sudo /sbin/sysctl -p
~~~

## 服务／端口

| Service       | Port      |
|---------------|-----------|
| Elasticsearch | 9200      |
| Redis         | 6379      |
| Nacos         | 6379      |
| Click-house   | 8123      |
| Seata         | 8091      |
| Mysql         | 3306      |
| Sentinel      | 8079      |
| Job           | 8999      |
| xxx-job       | 9998/7070 |
| Gateway       | 8443      |

## Docker 常见指令

清空所有日志

~~~
truncate -s 0 /var/lib/docker/containers/*/*-json.log
~~~

杀死运行的容器

~~~
docker kill $(docker ps -a -q)
~~~

删除所有容器

~~~
docker rm $(docker ps -a -q)
~~~

强制删除所有镜像

~~~
docker rmi -f $(docker images -q)
~~~

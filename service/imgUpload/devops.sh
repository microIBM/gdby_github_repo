#!/bin/bash

sudo apt-get install -y lua5.1 lua5.1-dev
cd /tmp/ && wget http://luarocks.org/releases/luarocks-2.2.1.tar.gz
tar zxpf luarocks-2.2.1.tar.gz
cd luarocks-2.2.1
./configure; sudo make bootstrap
sudo apt-get install -y libmysqld-dev
sudo luarocks install luasql-mysql MYSQL_INCDIR=/usr/include/mysql
cd /tmp
wget http://www.imagemagick.org/download/ImageMagick.tar.gz
tar zxvf ImageMagick.tar.gz
cd ImageMagick
./configure && make && sudo make install
sudo apt-get install libmagickcore-dev libmagickwand-dev
sudo mkdir -p /data/images && sudo chown www-data:www-data /data/images
cd /data/service/dachuwang/service/imgUpload
sudo ln -s /data/images files
sudo ln -s lua/watermask.png /data/images/watermask.png
sudo cp tools/tool_create_directories.sh files
sudo cp nginx_conf/img.dachuwang.com.conf nginx_conf/upload.dachuwang.com.conf /home/work/local/ymtwebserver/conf/sites-available
cd /home/work/local/ymtwebserver/conf/sites-enabled/
sudo ln -s ../sites-available/img.dachuwang.com.conf img.dachuwang.com.conf
cd /home/work/local/ymtwebserver
# sudo vim conf/nginx.conf # add `lua_package_path` conf to your lua scripts 
sudo sbin/nginx -t
sudo sbin/nginx -s reload

#!/usr/bin/env ruby

# ARGV[0] git repo dachuwang 的目录 
`sudo apt-get install -y uwsgi`
`sudo chown www-data:www-data -R #{ARGV[0]}/web/applications/cms`
`ln -s #{ARGV[0]}/web/applications/cms/uwsgi_admin.ini /etc/uwsgi/apps-enabled/uwsgi_admin.ini`
`sudo service uwsgi restart`
`ln -s #{ARGV[0]}/web/appliactions/cms/service.dachuwang.com.conf /etc/nginx/sites-enabled/service.dachuwang.com.conf`
`sudo nginx -s reload`

#!/usr/bin/env python
# -*- coding: utf-8

import subprocess
import gzip
import json
import re
import StringIO
import shutil
import flask
import GeoIP
import urllib2


__author__ = 'zhanghaili@dachuwang.com'

app = flask.Flask(__name__)
# geoip数据库存储路径
dat_file_path = '/usr/local/share/GeoIP/GeoLiteCity.dat'
gi = GeoIP.open(dat_file_path, GeoIP.GEOIP_STANDARD)
# geoip数据库更新链接
geo_city_download_url = ('http://geolite.maxmind.com/download/geoip/databa'
                         'se/GeoLiteCity.dat.gz')
ip_pattern = re.compile("^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$")
"""
TRADE: 更精确的正则，但可能带来性能下降
ip_pattern = re.compile("(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]"
                        "[0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|"
                        "[01]?[0-9][0-9]?)")
"""

@app.route("/get_geo/ip/<ip_addr>")
def get_ip_geo(ip_addr):
    """ 获取IP地址的GEO信息
    
    @param::ip_addr: 指定的ip地址，程序会检查其是否是ip地址，否则返回错误信息
    @return::type:json: {"status": "OK", "geo": "{...}", "error_msg": null}
    """
    global gi
    result = {}
    status = 'OK'
    error_msg = None
    geo = None
    status_code = 200

    match = ip_pattern.search(ip_addr)
    if not match:
        status = 'Failed'
        error_msg = u'无效的ip地址'
        status_code = 400
    else:
        gi_rcd = gi.record_by_addr(ip_addr)
        geo = str(gi_rcd)

    result['status'] = status
    result['error_msg'] = error_msg
    result['geo'] = geo
    result_json = json.dumps(result)
    return result_json, status_code


@app.route("/update")
def update():
    """ 更新地址数据库的借口

    程序去maxmind的数据下载页面下载地址数据库，然后重新初始化
    全局变量gi，以载入新的地址数据。
    """
    global gi
    result = {}
    status = 'OK'
    error_msg = None
    status_code = 200

    try:
        res = urllib2.urlopen(geo_city_download_url)
        f_compressed = StringIO.StringIO(res.read())
        f_decompressed = gzip.GzipFile(fileobj=f_compressed)
        with open(dat_file_path, 'w') as f_dat:
            f_dat.write(f_decompressed.read())
    except Exception, e:
        status = 'Failed'
        error_msg = str(e)
        status_code = 500
    else:
        gi = GeoIP.open(dat_file_path, GeoIP.GEOIP_STANDARD)
    
    result['status'] = status
    result['error_msg'] = error_msg
    result_json = json.dumps(result)
    return result_json, status_code


if __name__ == '__main__':
    """ 直接运行此脚本可进行测试

    """
    import sys
    test_port = int(sys.argv[1])
    app.run(debug=True, host='0.0.0.0', port=test_port)

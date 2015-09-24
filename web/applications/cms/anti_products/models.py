# -*- coding: utf-8 -*-

from __future__ import unicode_literals

import MySQLdb
import os

from django.db import models
import exec_env_conf

exec_env = os.environ['EXEC_ENV']
db_host = exec_env_conf.conf[exec_env]['db_host']
conn = MySQLdb.connect(host=db_host, user='ecun', passwd='ecun001', db='d_dachuwang')
cur = conn.cursor()
cur.execute("set names utf8;")
cur.execute("select site_id, name from t_anti_site;")
rows = cur.fetchall()
utf8_rows = [(row[0], row[1].decode('utf-8')) for row in rows]
format_rows = [(item[0], u'%s %s' % item) for item in utf8_rows]
SITE_ID_CHOICES = tuple(format_rows)
cur.close()
conn.close()

status_c = ((1, '有效'), (0, '无效') )

class TAntiProducts(models.Model):
    id = models.AutoField(primary_key=True)
    name = models.CharField('竟品名称', max_length=512)
    site_id = models.IntegerField('友商网站', choices=SITE_ID_CHOICES)
    prod_id = models.IntegerField('友商站内id',)
    images = models.CharField('图片', max_length=1024)
    cate = models.CharField('分类', max_length=512)
    prod_desc = models.CharField('描述', max_length=2048)
    feature = models.CharField('特征', max_length=512)
    origin = models.CharField('产地', max_length=512)
    price = models.IntegerField('单价', )
    price_unit = models.CharField('单价单位', max_length=256)
    prop = models.CharField('规格', max_length=512)
    level = models.CharField('级别', max_length=256)
    size = models.CharField('尺寸', max_length=512)
    total_price = models.IntegerField('总价',)
    sold_count = models.IntegerField('已售出')
    status = models.IntegerField('状态', choices=status_c)
    created_time = models.DateTimeField('创建时间', )
    updated_time = models.DateTimeField('更新时间', )
    url = models.CharField('竟品链接', max_length=1024)

    def image_img(self):
        if self.images == '-':
            return u'<div><span>%s</span></div>' % self.images
        elif self.images:
            image = self.images.split(';')[0]
            return u'<img src="%s" width="150" />' % image
    image_img.short_description = u'缩略图'
    image_img.allow_tags = True
    
    def desc(self):
        return self.prod_desc
    desc.short_description = '商品描述'
    desc.allow_tags = True

    def url_url(self):
        return '<a href="%s" target="_blank" >%s</a>' % (self.url, self.url)
    url_url.short_description = '竟品链接'
    url_url.allow_tags = True
   
    def price_(self):
        return float(self.price) / 100
    price_.short_description = '单价'

    def total_price_(self):
        return float(self.total_price) / 100
    total_price_.short_description = '总价'
    
    class Meta:
        managed = False
        db_table = 't_anti_products'


class TAntiSite(models.Model):
    id = models.IntegerField(primary_key=True)  # AutoField?
    site_id = models.IntegerField('友商id')
    name = models.CharField('友商名称', max_length=256)
    url = models.CharField('网站首页', max_length=1024)
    status = models.IntegerField('状态')
    created_time = models.DateTimeField('创建时间')
    updated_time = models.DateTimeField('更新时间')

    class Meta:
        managed = False
        db_table = 't_anti_site'

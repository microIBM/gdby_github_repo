# -*- coding: utf-8 -*-
from __future__ import unicode_literals

from django.db import models, migrations


class Migration(migrations.Migration):

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='TAntiProducts',
            fields=[
                ('id', models.AutoField(serialize=False, primary_key=True)),
                ('name', models.CharField(max_length=512, verbose_name='\u7adf\u54c1\u540d\u79f0')),
                ('site_id', models.IntegerField(verbose_name='\u5bf9\u624b\u7f51\u7ad9', choices=[(22, '\u679c\u4e50\u4e50')])),
                ('prod_id', models.IntegerField(verbose_name='\u5bf9\u624b\u7ad9\u5185id')),
                ('images', models.CharField(max_length=1024, verbose_name='\u56fe\u7247')),
                ('cate', models.CharField(max_length=512, verbose_name='\u5206\u7c7b')),
                ('prod_desc', models.CharField(max_length=2048, verbose_name='\u63cf\u8ff0')),
                ('feature', models.CharField(max_length=512, verbose_name='\u7279\u5f81')),
                ('origin', models.CharField(max_length=512, verbose_name='\u4ea7\u5730')),
                ('price', models.IntegerField(verbose_name='\u5355\u4ef7*100')),
                ('prop', models.CharField(max_length=512, verbose_name='\u89c4\u683c')),
                ('size', models.CharField(max_length=512, verbose_name='\u5c3a\u5bf8')),
                ('total_price', models.IntegerField(verbose_name='\u603b\u4ef7*100')),
                ('status', models.IntegerField(verbose_name='\u72b6\u6001')),
                ('created_time', models.DateTimeField(verbose_name='\u521b\u5efa\u65f6\u95f4')),
                ('updated_time', models.DateTimeField(verbose_name='\u66f4\u65b0\u65f6\u95f4')),
                ('url', models.CharField(max_length=1024, verbose_name='\u7adf\u54c1\u94fe\u63a5')),
            ],
            options={
                'db_table': 't_anti_products',
                'managed': False,
            },
            bases=(models.Model,),
        ),
    ]

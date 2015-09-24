# -*- coding: utf-8 -*-
from __future__ import unicode_literals

from django.db import models, migrations


class Migration(migrations.Migration):

    dependencies = [
        ('anti_products', '0002_auto_20150326_1745'),
    ]

    operations = [
        migrations.AlterModelOptions(
            name='tantiproducts',
            options={'managed': False, 'permissions': ('operate_anti_products', 'can operate anti products')},
        ),
    ]

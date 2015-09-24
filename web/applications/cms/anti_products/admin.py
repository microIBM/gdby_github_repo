from django.contrib import admin

# Register your models here.
from anti_products.models import *


class TAntiProductsAdmin(admin.ModelAdmin):
    list_display = ('name', 'site_id', 'prod_id', 'url_url', 'image_img', 'cate',
                    'feature', 'origin', 'price_', 'price_unit', 'total_price_', 
                    'sold_count',  'prop', 'size', 'level', 'status', 
                    'created_time', 'updated_time')
    fields = ('name', 'cate', 'feature', 'origin', 'price', 'price_unit', 
              'total_price', 'prop', 'size', 'level', 'prod_desc')
    readonly_fields = ('name', 'site_id', 'prod_id', 'url', 'images', 'cate',
                    'feature', 'origin', 'level', 'price', 'total_price', 'prop',
                    'price_unit', 'size', 'prod_desc', 'status', 'created_time', 
                    'updated_time')
    ordering = ('site_id', 'prod_id')
    search_fields = ('name', 'site_id', 'prod_id', 'url', 'cate', 'feature', 
                     'origin', 'prop', 'size', 'level', 'prod_desc')
 
    def has_add_permission(self, request):
        return True

    def has_delete_permission(self, request, obj=None):
        return False

    def has_change_permission(self, request, obj=None):
        return True


class TAntiSiteAdmin(admin.ModelAdmin):
    list_display = ('site_id', 'name', 'url', 'status', 'created_time', 'updated_time')
    fileds = ('site_id', 'name', 'url', 'status')
    oridering = ('site_id')
    search_fields = ('site_id', 'name', 'url')

    def has_delete_permission(self, request, obj=None):
        return False


admin.site.register(TAntiProducts, TAntiProductsAdmin)
admin.site.register(TAntiSite, TAntiSiteAdmin)

# 设置北京地区
update t_user set `max_customer`=40,`max_potential_customer`=50 where site_id=1 and province_id=804;
update t_user set `max_customer`=30,`max_potential_customer`=50 where site_id=2 and province_id=804;

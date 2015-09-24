# 按照商圈分配现有下单客户所属AM
update t_customer set am_id = 22, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(175,174,173,172,137,176));
update t_customer set am_id = 9, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(33,13,139,140,138,124,168));
update t_customer set am_id = 58, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(69,70,72,71,74,12,73,34));
update t_customer set am_id = 32, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(32,16,30,27,14,31,15,28,11,26));
update t_customer set am_id = 10, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(121,122,125,123,29,62,18,19,22,23,119,25));
update t_customer set am_id = 120, status = 12 where id in (select distinct(user_id) from t_order where status in (1,5,6) and line_id in(17,126,161,177,178,193,41,63,75,76,77,78,20,24,21,79,80,118,120));

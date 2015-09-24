--  修复活动id为1的最晚配送时间
update t_promo_event set latest_deliver_time= 1430755200 where id = 1;

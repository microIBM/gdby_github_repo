# 更新上海的潜在客户的invite_id为0，所有BD可以共享看到这些潜在客户，谁开通就算是谁的
update t_potential_customer set invite_id = 0 where province_id = 993;

ALTER TABLE `t_potential_customer` add column `invite_bd` int(11) not null default 0 comment '客户注册BD';
# 更新每个客户的邀请BD
UPDATE `t_potential_customer` SET invite_bd = invite_id;

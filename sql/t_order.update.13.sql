ALTER TABLE `t_order` ADD COLUMN `promotion_id` int unsigned NOT NULL DEFAULT 0 COMMENT '新的活动的id，和以前的promo_event_id对应的表不一样';

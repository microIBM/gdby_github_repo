ALTER TABLE `t_order_detail` ADD COLUMN `sale_id` int unsigned NOT NULL DEFAULT 0;
ALTER TABLE `t_order_detail` ADD COLUMN `sale_role` int unsigned NOT NULL DEFAULT 0;
update t_order_detail set sale_id = (select sale_id from t_suborder where t_suborder.id = t_order_detail.suborder_id);
update t_order_detail set sale_role = (select sale_role from t_suborder where t_suborder.id = t_order_detail.suborder_id);

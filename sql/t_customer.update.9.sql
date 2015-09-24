ALTER TABLE `t_customer` DROP INDEX `mobile`;
ALTER TABLE `t_customer` ADD INDEX `mobile`(`mobile`);

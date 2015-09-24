alter table t_sms_log add used_whose varchar(32) NOT NULL DEFAULT '' COMMENT '用的谁的平台';
alter table t_sms_log add templateId int(11)  NOT NULL DEFAULT 0 COMMENT '短信模板id';
alter table t_sms_log add time_limit int(11)  NOT NULL DEFAULT 0 COMMENT '时间限制 分钟制度';

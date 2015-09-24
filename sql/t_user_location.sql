create table `t_user_location` (
    `id` int primary key auto_increment,
    `lat` char(20) not null,
    `lng` char(20) not null,
    `user_id` int not null comment '记录的bd的id',
    `time` int not null comment '进行定位的时间',
    `created_time` int not null,
    `updated_time` int not null,
    `type` tinyint not null comment '1表示GPS定位，2表示LBS定位',
    index `u_idx` (`user_id`)
) engine=InnoDB default charset=utf8;

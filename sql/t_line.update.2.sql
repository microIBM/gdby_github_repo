alter table t_line add column site_src tinyint(3) not null default 0;
update t_line set site_src = 2 where name like '大果%';
update t_line set site_src = 1 where name like '大厨%';

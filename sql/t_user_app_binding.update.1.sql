ALTER TABLE t_user_app_binding ADD COLUMN user_encrypt char(32) NOT NULL DEFAULT '';
ALTER TABLE t_user_app_binding ADD COLUMN user_encrypt_expire INT UNSIGNED NOT NULL DEFAULT 0;

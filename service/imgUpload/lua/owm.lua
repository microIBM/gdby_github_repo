--origin watermask

local common = require("common")

local bucket, path,ext = ngx.var.bucket, ngx.var.path, ngx.var.ext

local pic_dir = common.get_pic_path(common.config_table.path_level, bucket, path)
local origin_fname = common.config_table.img_dir..pic_dir .. '/' .. path .. ".".. ext
local dest_fname = common.config_table.img_dir .. pic_dir .. '/' .. path .. "-owm.".. ext

common.EXIST_OR_NOT_FOUND(origin_fname)
if common.WATERMASK_BUCKETS[bucket] ~= nil then
    local blob_data = common.add_watermask(origin_fname,dest_fname, "rb")
    ngx.say(blob_data)
    ngx.exit(0)
else
    common.ALREADY_EXIST_AND_READ(origin_fname)
end

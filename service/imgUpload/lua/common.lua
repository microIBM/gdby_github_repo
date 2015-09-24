local M = {}

local config_table ={
    max_width = 2000,
    max_height =2000,
    min_width =49,
    min_height=49,
    max_precent = 100,
    min_precent = 20,
    watermask_w = 120,
    watermask_h = 55,
    watermask_mask_type = "PinLightCompositeOp",
    path_level = 2,
    img_dir = "/data/images/"
}

local WATERMASK_BUCKETS = { }

--[[
driver = require "luasql.mysql"
env = assert(driver.mysql())
con = assert(env:connect('d_dachuwang', 'ecun', 'ecun001', '10.10.6.183'))
sql = "select bucket from img_upload where watermask=1"

function rows(connection, sql)
    local cursor = assert(connection:execute(sql))
    return function ()
        return cursor:fetch()
    end
end

for bucket in rows(con, sql) do
    WATERMASK_BUCKETS[bucket] = 1
end
]]--

local magick = require("magick")

local function get_pic_path(depth,bucket, path)
    if depth < 1 or depth >= 16 then
        return bucket
    end

    pic_path = bucket
    for i=1,tonumber(depth)do
        pic_path = pic_path .. '/' .. string.sub(path,2*i-1,2*i)
    end
    
    return  pic_path
end

--[[
-- return  deal type  offset x, offset y 
-- 1 mean not need 
--]]

local function get_watermask_path(w, h, pos)
    local w = tonumber(w)
    local h = tonumber(h)
    if(w >= 200 and h >= 200) then
        --[[
        lt, ct, rt
        lm, cm, rm
        lb, cb, rb (default)
        ]]--
        local offset_w
        local offset_h
        if pos == "lt" then
            offset_w = 10
            offset_h = 10
        elseif pos == "ct" then
            offset_w = (w-config_table.watermask_w)/2
            offset_h = 10
        elseif pos == "rt" then
            offset_w = w - config_table.watermask_w - 10
            offset_h = 10
        elseif pos == "lm" then
            offset_w = 10
            offset_h = (h-config_table.watermask_h)/2
        elseif pos == "cm" then
            offset_w = (w-config_table.watermask_w)/2
            offset_h = (h-config_table.watermask_h)/2
        elseif pos == "rm" then
            offset_w = w - config_table.watermask_w - 10
            offset_h = (h-config_table.watermask_h)/2
        elseif pos == "lb" then
            offset_w = 10
            offset_h = h - config_table.watermask_h - 10
        elseif pos == "cb" then
            offset_w = (w-config_table.watermask_w)/2
            offset_h = h - config_table.watermask_h - 10
        else -- rb (default)
            offset_w = w - config_table.watermask_w - 10
            offset_h = h - config_table.watermask_h - 10
        end
        return 2, offset_w, offset_h
    end
    return 1 , 1 , 1
end

function add_watermask(src_fname,dest_fname, pos)
    local img_blob =  assert(magick.load_image(src_fname))
    local watermask_type , offset_x, offset_y = get_watermask_path(img_blob:get_width(),img_blob:get_height(), pos)
    if 2 ==  watermask_type then
        local watermask =  assert(magick.load_image(config_table.img_dir .."watermask.png"))
        img_blob:composite(watermask, math.floor(offset_x), math.floor(offset_y), config_table.watermask_mask_type)
        img_blob:write(dest_fname)
        watermask:destroy()
    end

    local blob_data = img_blob:get_blob()
    img_blob:destroy()
    return blob_data
end

local function NOT_FOUND(msg)
  ngx.status = ngx.HTTP_NOT_FOUND
  ngx.header["Content-type"] = "text/html"
  ngx.say(msg or "not found")
  ngx.exit(0)
end

local function FORBIDDEN(msg)
    ngx.status = 403
    ngx.header["Content-type"] = "text/html"
    ngx.say(msg or "forbidden")
    ngx.exit(0)
end

local function EXIST_OR_NOT_FOUND(fname)
    local file = io.open(fname)
    if not file then
        NOT_FOUND()
    end
    file:close()
end

local function ALREADY_EXIST_AND_READ(dest_fname)
    local file = io.open(dest_fname)
    if file then
        ngx.say(file:read("*all"))

        file:close()
        ngx.exit(0)
    end
end

M.config_table = config_table
M.WATERMASK_BUCKETS = WATERMASK_BUCKETS
M.get_watermask_path = get_watermask_path
M.get_pic_path = get_pic_path
M.add_watermask = add_watermask
M.NOT_FOUND = NOT_FOUND
M.FORBIDDEN = FORBIDDEN
M.EXIST_OR_NOT_FOUND = EXIST_OR_NOT_FOUND
M.ALREADY_EXIST_AND_READ = ALREADY_EXIST_AND_READ

return M 

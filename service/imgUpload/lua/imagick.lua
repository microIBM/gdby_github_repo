--TODO
local common = require("common")
local magick = require("magick")
local get_thumb_size_by_type = {
    [1] = function (w, h)  return w .. 'x' .. h end,
    [2] = function (w, h)  return w .. 'x' .. h .. '!' end,
    [3] = function (w)  return w .. 'x' end,
    [4] = function (h)  return 'x' ..  h end,
    [5] = function (w, h)  return w .. '%x' .. h .. '%' end,
}

-- check weather need save to disk
-- 1  mean save ,  2 mean not save , 3 mean  403
-- TODO need  by size
local function save_or_not(w, h, formattype)
    w = tonumber(w)
    h = tonumber(h)

    if w == nil then
        w = 0
    end

    if h == nil then
        h = 0
    end

    if 1 == formattype then
        if (w > common.config_table.max_width or h >common.config_table.max_height)then
             return 3 
        elseif (w <= common.config_table.min_width or h <= common.config_table.min_height ) then
             return  2
        end
        return 1
    elseif 2 == formattype then
        if (w> common.config_table.max_precent or h>common.config_table.max_precent) then
            return 3
        elseif (w<common.config_table.min_precent or h< common.config_table.min_precent)then
            return 2
        end
        return 1
    end
end


--parse analysis the formatstr,possible format are as follows:
--[[
    500-300
    500-300wrb
    500-300!
    500-
    -300
    50L-20L
]]--
local function parse(formatstr)
    local i = string.find(formatstr, "-")
    if i == nil then
        FORBIDDEN()
    end

    -- get watermask position
    --[[
        "w" for watermask position flag
        lt, ct, rt
        lm, cm, rm
        lb, cb, rb (default)
    ]]--
    local pi = string.find(formatstr, 'w')
    local watermask_pos = "rb"
    if pi ~= nil then
        watermask_pos = string.sub(formatstr, pi + 1)
        formatstr = string.sub(formatstr, 1, pi - 1)
    end

    local w = string.sub(formatstr,1,i-1)
    local h = string.sub(formatstr,i+1)
    local Lpos = string.find(formatstr, "L")
    local formattype = 2
    if Lpos == nil then
        formattype = 1
    end

    if w ~= nil then
        w = string.gsub(w, "!", "")
        w = string.gsub(w, "L", "")
    end

    if h ~= nil then
        h = string.gsub(h, "!", "")
        h = string.gsub(h, "L", "")
    end

    formatstr = string.gsub(formatstr, "-", "x")
    formatstr = string.gsub(formatstr, "L", "%%")

    return w,h,formatstr,formattype, watermask_pos
end

local bucket, path,ext,formatstr = ngx.var.bucket, ngx.var.path, ngx.var.ext,ngx.var.formatstr
local img_dir = common.config_table.img_dir -- where images come from
local pic_dir = common.get_pic_path(common.config_table.path_level, bucket, path)
local dest_fname = img_dir .. pic_dir .. '/' .. path .. "_" .. string.gsub(formatstr, "-", "_") .. ".".. ext
local origin_fname = img_dir..pic_dir .. '/' .. path .. "-owm.".. ext


-- 1.first check the dest_fname
common.ALREADY_EXIST_AND_READ(dest_fname)

-- 2.fall through
-- first check the origin_fname exists
common.EXIST_OR_NOT_FOUND(origin_fname)

local magick = require("magick")
local w, h,deal_formatstr,formattype, watermask_pos = parse(formatstr)
local deal_type = save_or_not(w, h, formattype)

if deal_type == 1 then
    -- save
    magick.thumb(origin_fname, deal_formatstr, dest_fname)
    if common.WATERMASK_BUCKETS[bucket] ~= nil then
        common.add_watermask(dest_fname,dest_fname, watermask_pos)
    end
    ngx.exec(ngx.var.request_uri)
    ngx.exit(0)
elseif deal_type == 2 then
    -- not save
    local image = magick.thumb(origin_fname, deal_formatstr)
    ngx.header.content_type = "image/jpg"
    ngx.say(image)
    ngx.exit(0)
else
    common.NOT_FOUND()
end

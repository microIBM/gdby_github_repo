
local p = "/soa/infrastructure/imgUpload"  
local m_package_path = package.path  
package.path = string.format("%s;%s?.lua;%s?/init.lua",  
    m_package_path, p, p)  

local format_url = string.gsub(ngx.var.request_uri, "-", "_")
ngx.log(0,formal_url)
ngx.exec(format_url)
ngx.exit(0)

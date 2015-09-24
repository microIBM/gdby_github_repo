$(document).ready(function () {
    var edit_white_user = function (obj) {
        var user_id = $(obj).attr('content');
        $.ajax({
            'url': base_url + '/white_user/get_white_user_info?user_id=' + user_id,
            'type': "get",
            'dataType': 'json',
            'success': function (data) {
                if (data.status == 1) {
                    $("input[name=edit_user_mobile]").val(data.user_info.mobile);
                    $("input[name=edit_user_name]").val(data.user_info.name);
                    $("input[name=edit_user_id]").val(data.user_info.user_id);
                    $("#edit-module > :checkbox").each(function () {
                        for (var key in data.user_info.module_ids) {
                            if ($(this).val() == data.user_info.module_ids[key]) {
                                $(this).prop('checked', true);
                            }
                        }
                    });
                    if (data.user_role) {
                        $(".J－edit-user-role").each(function() {
                            if ($(this).val() == data.user_role) {
                                $(this).prop('checked', true);
                            }
                        });
                    } else {
                       $(".J-admin-radio").addClass("hidden"); 
                    }
                } else {
                    alert('NOT FOUND');
                }
            }
        });
    };
    
    var jqchk = function(){ //jquery获取复选框值 
        var chk_value = []; 
        $('input[name="select_module[]"]:checked').each(function(){ 
            chk_value.push($(this).val()); 
        });
        return chk_value;
    };
    var add_white_user = function() {
        $.ajax({
            'url' : base_url+'/white_user/create',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'user_name' : $("input[name=user_name]").val(),
                'user_mobile' : $("input[name=user_mobile]").val(),
                'select_module' : jqchk(),
                'city_id' : php_city_id,
                'menue_id' : php_menue_id                
            },
            'success' : function(data){
                if (data.status == 0) {
                    window.location.href = base_url+"/white_user?city_id="+php_city_id+"&menue_id="+php_menue_id;
                }
                $(".J-user-tip").html(data.msg);
            }
        });
    };
    
    var add_white_module = function() {
        $.ajax({
            'url' : base_url+'/white_module/create',
            'type' : "post",
            'dataType' : 'json',
            'data' : {
                'module' : $("input[name=module]").val(),
                'controller' : $("input[name=controller]").val(),
                'action' : $("input[name=action]").val(),
                'city_id' : php_city_id,
                'menue_id' : php_menue_id                
            },
            'success' : function(data){
                if (data.status == 0) {
                    window.location.href = base_url+"/white_user?city_id="+php_city_id+"&menue_id="+php_menue_id;
                }
                $(".J-module-tip").html(data.msg);
            }
        });
    };
    //监听编辑点击事件
    $(".J-edit-white-user").on("click", function (e) {
        $("#edit-module > :checkbox").each(function () {
            $(this).prop("checked", false);
        });
        edit_white_user(e.target);
    });
    
    //监听白名单添加点击事件
    $(".J-user-submit").on("click", function(e) {
        add_white_user();
    });
    
    //监听白名单模块添加点击事件
    $(".J-module-submit").on("click", function(e) {
        add_white_module();
    });
});



/**
 * 
 * @author wangzejun@dachuwang.com
 */
$(document).ready(function(){
    var customer_valid_url = $(".a-list > a:eq(5)").attr('href');
    var customer_loss_url  = $(".a-list > a:eq(6)").attr('href');
    var customer_type = 0;
    var url_change = function(){
        customer_valid_url = customer_valid_url.replace(/customer_type=\d+/ig, 'customer_type='+customer_type);
        customer_loss_url  = customer_loss_url.replace(/customer_type=\d+/ig, 'customer_type='+customer_type);
        
        $(".a-list > a:eq(5)").attr('href', customer_valid_url);
        $(".a-list > a:eq(6)").attr('href', customer_loss_url);
        $("input[name=customer_type]").val(customer_type);
    };
    
    //监听客户切换
    $('.J-customer').on('change', function(){
        customer_type = $('.J-customer').val();
        url_change();
    });
});


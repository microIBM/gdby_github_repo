$(document).ready(function(){
    //按钮随点击变化内容
    //	$(".dachu").on('click', function(){
    //		if($(".daguo").hasClass("active")){
    //			$(".daguo").removeClass("active");
    //			$(this).addClass("active");
    //			$(".page-header").text("大厨网统计");
    //		}
    //	});
    //	
    //	$(".daguo").on('click', function(){
    //		if($(".dachu").hasClass("active")){
    //			$(".dachu").removeClass("active");
    //			$(this).addClass("active");
    //			$(".page-header").text("大果网统计");
    //		}
    //	});
    //	
    //	$(".week").on('click', function(){
    //		if($(".month").hasClass("active")){
    //			$(".month").removeClass("active");
    //			$(this).addClass("active");
    //			$(".totalData").text("一周总计");
    //		}
    //	});
    //	
    //	$(".month").on('click', function(){
    //		if($(".week").hasClass("active")){
    //			$(".week").removeClass("active");
    //			$(this).addClass("active");
    //			$(".totalData").text("一月总计");
    //		}
    //	});
    
    //初始化popover控件
    $(function(){
        $("[data-toggle='popover']").popover();
    });
    
    //监听鼠标滑动popover事件
    $(".pop").on('mouseenter', function(){
        $(this).popover('show');
    });
    
    $(".pop").on('mouseleave', function(){
        $(this).popover('hide');
    });
    
    //文档load完毕监听表头并固定
    fixTable();
    
    //当用户resize窗口，重新初始化监听
    $(window).on("resize", function(){
        fixTable();
    });
    
});

//监听表头到top并固定表头
//function fixTable(){
//		//测量表头宽度并复制给隐藏表头
//		for(var i=1;i<=17;i++){
//			var t = $(".t-"+i).outerWidth();
//			$('.t-fix-'+i).attr("width",t);
//		}
//		var theadHeight = $('.nav-table').offset().top;
//		
//		$(window).scroll(function(){
//			var scroHeight = $(this).scrollTop();
//			
//			if((scroHeight+50)>=theadHeight){
//				$(".table-hide").css({"display":"table","position":"fixed","top":40,"z-index":1000});
//			}else{
//				$(".table-hide").css({"display":"none"});
//			}
//		});
//}

//监听表头到top并固定表头
function fixTable(){
    //测量表头宽度并复制给隐藏表头
    $(".table-show thead>tr>th").each(function(index, element){
        var width = $(element).outerWidth();
        $(".table-hide thead>tr>th").eq(index).attr("width", width);
    });
    var theadHeight = $('.nav-table').offset().top;
	$(window).scroll(function(){
        var scroHeight = $(this).scrollTop();
        if ((scroHeight + 50) >= theadHeight) {
            $(".table-hide").css({
                "display": "table",
                "position": "fixed",
                "top": 40,
                "z-index": 1000
            });
        }
        else {
            $(".table-hide").css({
                "display": "none"
            });
        }
    });
}

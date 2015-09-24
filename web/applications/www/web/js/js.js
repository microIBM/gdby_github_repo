$(function(){
	$('#toggle-btn').click(function (){
		$('#mobile-nav').toggleClass('mobile');
	})
	$('#mobile-nav li').click(function(){
		$('.mobile').removeClass('mobile')
	})
	$(window).bind('scroll', function(event){
		$('.prolist li').each(function(){
			
			if($(window).height() + $(window).scrollTop() >= $(this).offset().top + $(window).height()/2){
				$(this).addClass('pulse animated')
			}else{
				$(this).removeClass('pulse animated')
			}
		})
	})
})
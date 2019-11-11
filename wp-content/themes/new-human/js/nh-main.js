;(function($){

	$(function(){

		if ( $('.sv-testimonial').length ) {
			$('.sv-testimonial').matchHeight();

			$('.accordion-toggle').on('click', function(){
			    
			  $('.accordion-toggle').not($(this)).removeClass('open').text('READ MORE');
			  
				if ( ! $(this).hasClass('open') ) {
			      
				  $(this).addClass('open').text('SHOW LESS');
			      $('.sv-plug-and-play').find('.accordion-content').slideUp();
			      $(this).parents('.fl-module-content').find('.accordion-content').slideDown(function(){
			      	$(this).animate({'opacity': 1});
			      });
			    } else {
			      $(this).removeClass('open').text('READ MORE');
			      $(this).parents('.fl-module-content').find('.accordion-content').animate({'opacity': 0}, function(){
			      	$(this).slideUp();
			      });

			    }

			});
		}

		$(document).ajaxStop(function(response){
			var infscrLoading = $('#infscr-loading');
			if ( infscrLoading.length ) {
				infscrLoading.remove();
			}
		});


	});

})(jQuery);
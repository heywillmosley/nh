(function($) {
	
	var FLAccordion = {
		
		init: function()
		{
			$( '.fl-accordion-button' ).click( FLAccordion._buttonClick );
		},
		
		_buttonClick: function()
		{
			var button      = $(this),
				accordion   = button.closest('.fl-accordion'),
				allContent  = $('.fl-accordion-content'),
				allIcons    = $('.fl-accordion-button i.fl-accordion-button-icon'),
				content     = button.siblings('.fl-accordion-content'),
				icon        = button.find('i.fl-accordion-button-icon');
		
			if(accordion.hasClass('fl-accordion-collapse')) {
				allContent.slideUp('normal');   
				allIcons.removeClass('fa-minus');
				allIcons.addClass('fa-plus');
			}
			
			if(content.is(':hidden')) {
				content.slideDown('normal', FLAccordion._slideDownComplete);
				icon.addClass('fa-minus');
				icon.removeClass('fa-plus');
			}
			else {
				content.slideUp('normal');
				icon.addClass('fa-plus');
				icon.removeClass('fa-minus');
			}
		},
		
		_slideDownComplete: function()
		{
			var item = $( this ).parent(),
				win  = $( window );
			
			if ( item.offset().top < win.scrollTop() + 100 ) {
				$( 'html, body' ).animate({ 
					scrollTop: item.offset().top - 100 
				}, 500, 'swing');
			}
		}
	};
	
	$( FLAccordion.init );
	
})(jQuery);
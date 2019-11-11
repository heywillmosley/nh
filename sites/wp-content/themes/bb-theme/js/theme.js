(function($){
	
	var FLTheme = {
		
		init: function()
		{
			this._bind();
			this._initRetinaImages();
		},
		
		_bind: function()
		{
			// Fixed header
			if($('.fl-page-header-fixed').length != 0) {
				$(window).on('resize', $.throttle(500, this._enableFixedHeader));
				this._enableFixedHeader();
			} 
			
			// Top Nav Drop Downs
			if($('.fl-page-bar-nav ul.sub-menu').length != 0) {
				this._setupDropDowns();
				this._enableTopNavDropDowns();
			} 
			
			// Page Nav Drop downs
			if($('.fl-page-nav ul.sub-menu').length != 0) {
				$(window).on('resize', $.throttle(500, this._enablePageNavDropDowns));
				this._setupDropDowns();
				this._enablePageNavDropDowns();
			} 
			
			// Nav Search
			if($('.fl-page-nav-search').length != 0) {
				$('.fl-page-nav-search a.fa-search').on('click', this._toggleNavSearch);
			} 
			
			// Lightbox
			if(typeof $('body').magnificPopup != 'undefined') {
				this._enableLightbox();
			}
		},
		
		_enableFixedHeader: function()
		{
			var win = $(window);
			
			if(win.width() < 992) {
				win.off('scroll.fl-theme');
				$('.fl-page-header-fixed').hide();
			}
			else {
				win.on('scroll.fl-theme', FLTheme._toggleFixedHeader);
			}
		},
		
		_toggleFixedHeader: function()
		{
			var win             = $(window),
				header          = $('.fl-page-header').not('.fl-page-header-fixed'),
				headerHidden    = win.scrollTop() > header.height() + header.offset().top,
				fixed           = $('.fl-page-header-fixed'),
				fixedVisible    = fixed.is(':visible');
			
			if(headerHidden && !fixedVisible) {
				fixed.stop().fadeIn(200);
			}
			else if(!headerHidden && fixedVisible) {
				fixed.stop().hide();
			}
		},
		
		_setupDropDowns: function()
		{
			$('ul.sub-menu').each(function(){
				$(this).closest('li').attr('aria-haspopup', 'true');
			});
		},
		
		_enableTopNavDropDowns: function()
		{
			var nav      = $('.fl-page-bar-nav'),
				navItems = nav.find(' > li');
			
			navItems.hover(FLTheme._navItemMouseover, FLTheme._navItemMouseout);
		},
		
		_enablePageNavDropDowns: function()
		{
			var win      = $(window),
				nav      = $('.fl-page-nav .fl-page-nav-collapse'),
				navItems = nav.find('ul li'),
				subMenus = navItems.find('ul.sub-menu');
			
			if(win.width() < 768) {
				navItems.off('mouseenter mouseleave');
				nav.find('> ul > li').has('ul.sub-menu').find('> a').on('click', FLTheme._navItemClickMobile);
			}
			else {
				nav.find('a').off('click', FLTheme._navItemClickMobile);
				nav.removeClass('in').addClass('collapse');
				navItems.removeClass('fl-mobile-sub-menu-open');
				navItems.find('a').width(0).width('auto');
				navItems.hover(FLTheme._navItemMouseover, FLTheme._navItemMouseout);
			}
		},
		
		_navItemClickMobile: function(e)
		{
			var parent = $(this).parent();

			if(!parent.hasClass('fl-mobile-sub-menu-open')) {
				e.preventDefault(); 
				parent.addClass('fl-mobile-sub-menu-open');
			}
		},
		
		_navItemMouseover: function()
		{
			if($(this).find('ul.sub-menu').length === 0) {
				return;
			} 
			
			var li              = $(this),
				parent          = li.parent(),
				subMenu         = li.find('ul.sub-menu'),
				subMenuWidth    = subMenu.width(),
				subMenuPos      = 0,
				winWidth        = $(window).width();
			
			if(li.closest('.fl-sub-menu-right').length !== 0) {
				li.addClass('fl-sub-menu-right');
			}
			else if($('body').hasClass('rtl')) {
				
				subMenuPos = parent.is('ul.sub-menu') ?
							 parent.offset().left - subMenuWidth: 
							 li.offset().left - subMenuWidth;
				
				if(subMenuPos <= 0) {
					li.addClass('fl-sub-menu-right');
				}
			}
			else {
				
				subMenuPos = parent.is('ul.sub-menu') ?
							 parent.offset().left + (subMenuWidth * 2) : 
							 li.offset().left + subMenuWidth;
				
				if(subMenuPos > winWidth) {
					li.addClass('fl-sub-menu-right');
				}
			}
			
			li.addClass('fl-sub-menu-open');
			subMenu.hide();
			subMenu.stop().fadeIn(200);
			FLTheme._hideNavSearch();
		},
		
		_navItemMouseout: function()
		{
			var li      = $(this),
				subMenu = li.find('ul.sub-menu');
			
			subMenu.stop().fadeOut({
				duration: 200, 
				done: FLTheme._navItemMouseoutComplete
			});
		},
		
		_navItemMouseoutComplete: function()
		{
			var li = $(this).parent();
			
			li.removeClass('fl-sub-menu-open');
			li.removeClass('fl-sub-menu-right');
			
			$(this).show();
		},
		
		_toggleNavSearch: function()
		{
			var form = $('.fl-page-nav-search form');
			
			if(form.is(':visible')) {
				form.stop().fadeOut(200);
			}
			else {
				form.stop().fadeIn(200);
				$('body').on('click.fl-theme', FLTheme._hideNavSearch);
			}
		},
		
		_hideNavSearch: function(e)
		{
			var form = $('.fl-page-nav-search form');
			
			if(e !== undefined) {
				if($(e.target).closest('.fl-page-nav-search').length > 0) {
					return;
				}
			}
			
			form.stop().fadeOut(200);
			
			$('body').off('click.fl-theme');
		},
		
		_enableLightbox: function()
		{
			var body = $('body');
			
			if(!body.hasClass('fl-builder') && !body.hasClass('woocommerce')) {
				
				$('.fl-content').find('a[href*=".jpg"], a[href*=".jpeg"], a[href*=".png"], a[href*=".gif"]').magnificPopup({
					closeBtnInside: false,
					type: 'image',
					gallery: {
						enabled: true
					}
				});
			}
		},
		
		_initRetinaImages: function()
		{
			var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
		
			if ( pixelRatio > 1 ) {
				$( 'img[data-retina]' ).each( FLTheme._convertImageToRetina );
			}
		},
		
		_convertImageToRetina: function()
		{
			var image       = $( this ),
				tmpImage    = new Image(),
				src         = image.attr( 'src' ),
				retinaSrc   = image.data( 'retina' );
				
			if ( '' != retinaSrc ) {
			
				tmpImage.onload = function() {
					image.width( tmpImage.width );
					image.attr( 'src', retinaSrc );
				};
				
				tmpImage.src = src; 
			}
		}
	};
	
	$(function(){
		FLTheme.init();
	});
	
})(jQuery);
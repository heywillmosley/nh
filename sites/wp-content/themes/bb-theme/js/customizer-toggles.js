var FLCustomizerToggles;

( function( $ ) {
	
	/* Internal shorthand */
	var api = wp.customize;
	
	/**
	 * @class FLCustomizerToggles
	 */
	FLCustomizerToggles = {
		
		'fl-layout-width': [{
			controls: [ 'fl-layout-spacing', 'fl-layout-shadow-size', 'fl-layout-shadow-color' ],
			callback: function( val ) { return 'boxed' == val; }
		}],
		
		'fl-link-color-type': [{
			controls: [ 'fl-link-color', 'fl-link-hover-color' ],
			callback: function( val ) { return 'custom' == val; }
		}],
		
		'fl-topbar-bg-type': [{
			controls: [ 'fl-topbar-bg-color' ],
			callback: function( val ) { return 'custom' == val; }
		},{
			controls: [ 'fl-topbar-bg-gradient' ],
			callback: function( val ) { return 'content' == val || 'custom' == val; }
		}],
		
		'fl-header-bg-type': [{
			controls: [ 'fl-header-bg-color' ],
			callback: function( val ) { return 'custom' == val; }
		},{
			controls: [ 'fl-header-bg-gradient' ],
			callback: function( val ) { return 'content' == val || 'custom' == val; }
		}],
		
		'fl-nav-bg-type': [{
			controls: [ 'fl-nav-bg-color' ],
			callback: function( val ) { return 'custom' == val; }
		},{
			controls: [ 'fl-nav-bg-gradient' ],
			callback: function( val ) { return 'content' == val || 'custom' == val; }
		}],
		
		'fl-nav-text-type': [{
			controls: [ 'fl-nav-font-family', 'fl-nav-font-weight', 'fl-nav-font-format', 'fl-nav-font-size' ],
			callback: function( val ) { return 'custom' == val; }
		}],
		
		'fl-footer-widgets-bg-type': [{
			controls: [ 'fl-footer-widgets-bg-color' ],
			callback: function( val ) { return 'custom' == val; }
		}],
		
		'fl-footer-bg-type': [{
			controls: [ 'fl-footer-bg-color' ],
			callback: function( val ) { return 'custom' == val; }
		}],
		
		'fl-topbar-layout': [{
			controls: [ 'fl-topbar-line1', 'fl-topbar-col1-layout' ],
			callback: function( val ) { 
				
				var col1Layout = api( 'fl-topbar-col1-layout' ).get(),
					col1Text   = api.control( 'fl-topbar-col1-text' ).container,
					col2Layout = api( 'fl-topbar-col2-layout' ).get(),
					col2Text   = api.control( 'fl-topbar-col2-text' ).container;
				
				col1Text.toggle( 'none' != val && 'text' == col1Layout );
				col2Text.toggle( '2-cols' == val && 'text' == col2Layout );
				
				return '1-col' == val || '2-cols' == val; 
			}
		},{
			controls: [ 'fl-topbar-line2', 'fl-topbar-col2-layout' ],
			callback: function( val ) { return '2-cols' == val;  }
		}],
		
		'fl-topbar-col1-layout': [{
			controls: [ 'fl-topbar-col1-text' ],
			callback: function( val ) { return 'none' != api( 'fl-topbar-layout' ).get() && ('text' == val || 'text-social' == val); }
		}],
		
		'fl-topbar-col2-layout': [{
			controls: [ 'fl-topbar-col2-text' ],
			callback: function( val ) { return '2-cols' == api( 'fl-topbar-layout' ).get() && ('text' == val || 'text-social' == val); }
		}],
		
		'fl-logo-type': [{
			controls: [ 'fl-logo-text', 'fl-logo-font-family', 'fl-logo-font-weight', 'fl-logo-font-size' ],
			callback: function( val ) { return 'text' == val; }
		},{
			controls: [ 'fl-logo-image', 'fl-logo-image-retina' ],
			callback: function( val ) { return 'image' == val; }
		}],
		
		'fl-header-layout': [{
			controls: [ 'fl-header-nav-search' ],
			callback: function( val ) { return 'none' != val; }
		},{
			controls: [ 'fl-header-line1', 'fl-header-content-layout' ],
			callback: function( val ) {
				
				var layout = api( 'fl-header-content-layout' ).get(),
					text   = api.control( 'fl-header-content-text' ).container;
				
				text.toggle( 'bottom' == val && ('text' == layout || 'social-text' == layout) );
				
				return 'bottom' == val; 
			}
		}],
		
		'fl-header-content-layout': [{
			controls: [ 'fl-header-content-text' ],
			callback: function( val ) { 
				return 'bottom' == api( 'fl-header-layout' ).get() && ('text' == val || 'social-text' == val); 
			}
		}],
		
		'fl-blog-layout': [{
			controls: [ 'fl-blog-sidebar-size', 'fl-blog-sidebar-display' ],
			callback: function( val ) { return 'no-sidebar' != val; }
		}],
		
		'fl-archive-show-full': [{
			controls: [ 'fl-archive-readmore-text' ],
			callback: function( val ) { return '0' == val; }
		}],
		
		'fl-woo-layout': [{
			controls: [ 'fl-woo-sidebar-size', 'fl-woo-sidebar-display' ],
			callback: function( val ) { return 'no-sidebar' != val; }
		}],
		
		'fl-footer-layout': [{
			controls: [ 'fl-footer-line1', 'fl-footer-col1-layout' ],
			callback: function( val ) { 
				
				var col1Layout = api( 'fl-footer-col1-layout' ).get(),
					col1Text   = api.control( 'fl-footer-col1-text' ).container,
					col2Layout = api( 'fl-footer-col2-layout' ).get(),
					col2Text   = api.control( 'fl-footer-col2-text' ).container;
				
				col1Text.toggle( 'none' != val && ('text' == col1Layout || 'social-text' == col1Layout) );
				col2Text.toggle( '2-cols' == val && ('text' == col2Layout || 'social-text' == col2Layout) );
				
				return '1-col' == val || '2-cols' == val; 
			}
		},{
			controls: [ 'fl-footer-line2', 'fl-footer-col2-layout' ],
			callback: function( val ) { return '2-cols' == val;  }
		}],
		
		'fl-footer-col1-layout': [{
			controls: [ 'fl-footer-col1-text' ],
			callback: function( val ) { 
				return 'none' != api( 'fl-footer-layout' ).get() && ('text' == val || 'social-text' == val); 
			}
		}],
		
		'fl-footer-col2-layout': [{
			controls: [ 'fl-footer-col2-text' ],
			callback: function( val ) { 
				return '2-cols' == api( 'fl-footer-layout' ).get() && ('text' == val || 'social-text' == val); 
			}
		}]
	};
	
})( jQuery );
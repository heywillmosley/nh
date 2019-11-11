var FLCustomizerPreview;

( function( $ ){
	
	/* Internal shorthand */
	var api = wp.customize;

	/**
	 * @class FLCustomizerPreview
	 */
	FLCustomizerPreview = {
		
		/**
		 * @param _styleSheet
		 * @private
		 */  
		_styleSheet: null,
		
		/**
		 * @method init
		 */
		init: function()
		{
			// Remove the loading graphic.
			window.parent.jQuery( '#fl-customizer-loading' ).remove();
			
			// Create the stylesheet.
			this._styleSheet = new FLStyleSheet();
			
			// Bind CSS callbacks.
			this._css( 'fl-body-bg-image', 'body', 'background-image', 'url({val})', 'none' );
			this._css( 'fl-body-bg-repeat', 'body', 'background-repeat' );
			this._css( 'fl-body-bg-position', 'body', 'background-position' );
			this._css( 'fl-body-bg-attachment', 'body', 'background-attachment' );
			this._css( 'fl-body-bg-size', 'body', 'background-size' );
			this._css( 'fl-heading-text-color', 'h1, h2, h3, h4, h5, h6', 'color' );
			this._css( 'fl-heading-text-color', 'h1 a, h2 a, h3 a, h4 a, h5 a, h6 a', 'color' );
			this._css( 'fl-heading-font-format', 'h1, h2, h3, h4, h5, h6', 'text-transform' );
			this._css( 'fl-h1-font-size', 'h1', 'font-size', '{val}px', '36px', 'int' );
			this._css( 'fl-h2-font-size', 'h2', 'font-size', '{val}px', '30px', 'int' );
			this._css( 'fl-h3-font-size', 'h3', 'font-size', '{val}px', '24px', 'int' );
			this._css( 'fl-h4-font-size', 'h4', 'font-size', '{val}px', '18px', 'int' );
			this._css( 'fl-h5-font-size', 'h5', 'font-size', '{val}px', '14px', 'int' );
			this._css( 'fl-h6-font-size', 'h6', 'font-size', '{val}px', '12px', 'int' );
			this._css( 'fl-logo-font-size', '.fl-logo-text', 'font-size', '{val}px', '40px', 'int' );
			this._css( 'fl-nav-font-size', '.fl-page-nav .navbar-nav', 'font-size', '{val}px', '16px', 'int' );
			this._css( 'fl-nav-font-size', '.fl-page-nav .navbar-nav a', 'font-size', '{val}px', '16px', 'int' );
			this._css( 'fl-nav-font-format', '.fl-page-nav .navbar-nav', 'text-transform' );
			this._css( 'fl-nav-font-format', '.fl-page-nav .navbar-nav a', 'text-transform' );
			
			// Bind HTML callbacks.
			this._html( 'fl-topbar-col1-text', '.fl-page-bar-text-1' );
			this._html( 'fl-topbar-col2-text', '.fl-page-bar-text-2' );
			this._html( 'fl-logo-text', '.fl-logo-text' );
			this._html( 'fl-footer-col1-text', '.fl-page-footer-text-1' );
			this._html( 'fl-footer-col2-text', '.fl-page-footer-text-2' );
		},
		
		/**
		 * @method _bind
		 * @private
		 */
		_bind: function( key, callback )
		{
			api( key, function( val ) {
				val.bind( function( newVal ) {
					callback.call( FLCustomizerPreview, newVal )
				});
			});
		},
		
		/**
		 * @method _css
		 * @private
		 */
		_css: function( key, selector, property, format, fallback, sanitizeCallback )
		{
			api( key, function( val ) {
				
				val.bind( function( newVal ) {
					
					switch ( sanitizeCallback ) {
						case 'int':
						newVal = FLCustomizerPreview._sanitizeInt( newVal );
						break;
					}
					
					if ( 'undefined' != typeof fallback && null != fallback && '' == newVal ) {
						newVal = fallback;
					}
					else if ( 'undefined' != typeof format && null != format ) {
						newVal = format.replace( '{val}', newVal );
					}
					
					FLCustomizerPreview._styleSheet.updateRule( selector, property, newVal );
				});
			});
		},
		
		/**
		 * @method _html
		 * @private
		 */
		_html: function( key, selector )
		{
			api( key, function( val ) {
				val.bind( function( newVal ) {
					$( selector ).html( newVal );
				});
			});
		},
		
		/**
		 * @method _sanitizeInt
		 * @private
		 */
		_sanitizeInt: function( val )
		{
			var number = parseInt( val );
			
			return isNaN( number ) ? 0 : number;
		}
	};
	
	$( function() { FLCustomizerPreview.init(); } );
	
})( jQuery );
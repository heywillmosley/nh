var FLCustomizer;

( function( $ ) {
	
	/* Internal shorthand */
	var api = wp.customize;

	/**
	 * @class FLCustomizer
	 */
	FLCustomizer = {
	
		/**
		 * @method init
		 */
		init: function()
		{
			FLCustomizer._initLoading();
			FLCustomizer._initToggles();
			FLCustomizer._initFonts();
			FLCustomizer._initPresets();
		},
	
		/**
		 * @method _initLoading
		 * @private
		 */
		_initLoading: function()
		{
			$.each( api.settings.settings, function( key, data ) {

				if ( 'refresh' == data.transport ) {
					
					api( key, function( setting ) {
					   
						setting.bind( function() {
							$( '#fl-customizer-loading' ).remove();
							$( '#customize-preview' ).append( '<div id="fl-customizer-loading"></div>' ); 
						});
					});
				}
			});
		},
	
		/**
		 * @method _initToggles
		 * @private
		 */
		_initToggles: function()
		{
			// Loop through each setting.
			$.each(FLCustomizerToggles, function( settingId, toggles ) {
			
				// Get the setting object. 
				api( settingId, function( setting ) {
					
					// Loop though the toggles for the setting.
					$.each( toggles, function( i, toggle ) {
						
						// Loop through the controls for the toggle.
						$.each( toggle.controls, function( k, controlId ) {
							
							// Get the control object.
							api.control( controlId, function( control ) {
								
								// Define the visibility callback.
								var visibility = function( to ) {
									control.container.toggle( toggle.callback( to ) );
								};
			
								// Init visibility.
								visibility( setting.get() );
								
								// Bind the visibility callback to the setting.
								setting.bind( visibility );
							});
						});
					});
				});
			});
		},
	
		/**
		 * @method _initFonts
		 * @private
		 */
		_initFonts: function()
		{
			$( '.customize-control-font select' ).each( FLCustomizer._initFont );
		},
	
		/**
		 * @method _initFont
		 * @private
		 */
		_initFont: function()
		{
			var select  = $( this ),
				link    = select.data( 'customize-setting-link' ),
				weight  = select.data( 'connected-control' );
				
			if ( 'undefined' != typeof weight ) {
				api( link ).bind( FLCustomizer._fontSelectChange );
				FLCustomizer._setFontWeightOptions.apply( api( link ), [ true ] );
			}
		},
	
		/**
		 * @method _fontSelectChange
		 * @private
		 */
		_fontSelectChange: function()
		{
			FLCustomizer._setFontWeightOptions.apply( this, [ false ] );
		},
	
		/**
		 * @method _setFontWeightOptions
		 * @private
		 */
		_setFontWeightOptions: function( init )
		{
			var i               = 0,
				fontSelect      = api.control( this.id ).container.find( 'select' ),
				fontValue       = this(),
				weightKey       = fontSelect.data( 'connected-control' ),
				weightSelect    = api.control( weightKey ).container.find( 'select' ),
				weightValue     = init ? weightSelect.val() : '400',
				weightObject    = null,
				weightOptions   = '',
				weightMap       = {
					'100': 'Thin 100',
					'200': 'Extra-Light 200',
					'300': 'Light 300',
					'400': 'Normal 400',
					'500': 'Medium 500',
					'600': 'Semi-Bold 600',
					'700': 'Bold 700',
					'800': 'Extra-Bold 800',
					'900': 'Ultra-Bold 900'
				};
				
			if ( 'undefined' != typeof FLFontFamilies.system[ fontValue ] ) {
				weightObject = FLFontFamilies.system[ fontValue ].weights;
			}
			else if ( 'undefined' != typeof FLFontFamilies.google[ fontValue ] ) {
				weightObject = FLFontFamilies.google[ fontValue ];
			}
			
			for ( ; i < weightObject.length; i++ ) {
				selected = weightObject[ i ] == weightValue ? ' selected="selected"' : '';
				weightOptions += '<option value="' + weightObject[ i ] + '"' + selected + '>' + weightMap[ weightObject[ i ] ] + '</option>';
			}
			
			weightSelect.html( weightOptions );
			
			if ( ! init ) {
				api( weightKey ).set( '' );
				api( weightKey ).set( weightValue );
			}
		},
	
		/**
		 * @method _initPresets
		 * @private
		 */
		_initPresets: function()
		{
			api.control( 'fl-preset' ).container.find( 'select' ).on( 'change', FLCustomizer._presetChange );
		},
	
		/**
		 * @method _presetChange
		 * @private
		 */
		_presetChange: function()
		{
			var val         = $( this ).val(),
				settings    = $.extend( {}, FLCustomizerPresetDefaults, FLCustomizerPresets[ val ].settings ),
				control     = null,
				picker      = null;
			
			// Loop the settings.
			for ( key in settings ) {
				
				// Get the control instance.
				control = api.control.instance( key );
				
				// Set the preset setting.
				control.setting.set( settings[ key ] );
				
				// Update the color picker if a color control.
				picker = control.container.find( '.color-picker-hex' );
				
				if ( picker.length > 0 ) {
					picker.wpColorPicker( 'color', settings[ key ] );
				}
			}
		}
	};
	
	$( function() { FLCustomizer.init(); } );
	
})( jQuery );
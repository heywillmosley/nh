//	Responsive Tabs v1.3, Copyright 2014, Joe Mottershaw, https://github.com/joemottershaw/
//	=======================================================================================

	// Tabs function
		function responsiveTabs() {
			
			
			
			// Tabs functionality
				jQuery('.responsive-tabs').each(function(e) {
					// Hide all tab panels except for the first
						jQuery('.responsive-tabs-panel').not(':first').hide();

					// Add active statuses to first tab and show display
						jQuery('li', this).removeClass('active');
						jQuery('li:first-child', this).addClass('active');
						jQuery('.responsive-tabs-panel:first-child').show();

					// Tab clicked
						jQuery('li', this).click(function(e) {
							// Prevent the anchor's default click action
								e.preventDefault();
							
							// Corresponding tabs panel
								var panel = jQuery('a', this).attr('href');

							// Remove active statuses to other tabs
								jQuery(this).siblings().removeClass('active');

							// Add active status to this tab
								jQuery(this).addClass('active');

							// Hide other tab panels
								jQuery(panel).siblings().hide();
								
								var that = jQuery(this);
								var flag = true;
							
							// Showing the clicked tabs' panel
								jQuery(panel).fadeIn(400,'linear',function(){
									
									if(flag) {									
										var tab_id = that.find('a').attr('data-tab');
										if(tab_id == "#tab-2") jQuery(tab_id).find('a.box_tab_report:first').click();
										flag = false;
									}
									
								});
								
						});

					// Responsive
						if (jQuery(window).width() < 319)
							jQuery('.responsive-tabs-panel').show();
				});

			// Panel link functionality
				jQuery('.responsive-tabs-content .responsive-tabs-panel .responsive-tabs-panel-link').on('click', function(e) {
					// Corresponding tabs panel
						var panel = $(this).attr('href');

					// Remove active statuses to other tabs
						$(this).parents('.responsive-tabs-content').siblings('.responsive-tabs').find('a[href=' + panel + ']').parent().siblings().removeClass('active');

					// Add active status to this tab
						$(this).parents('.responsive-tabs-content').siblings('.responsive-tabs').find('a[href=' + panel + ']').parent().addClass('active');

					// Hide other tab panels
						$(panel).siblings().hide();

					// Showing the clicked tabs' panel
						$(panel).fadeIn(400);

					// Prevent the anchor's default click action
						e.preventDefault();
						
						var tab_id = $(this).attr('data-tab');
								alert(tab_id)
				});
		}

	jQuery(document).ready(function() {
		responsiveTabs();

		jQuery('.responsive-tabs li a').each(function() {
			var	tabID = jQuery(this).attr('href');
			var	tabTitle = jQuery(this).html();

			jQuery(tabID + ' .responsive-tab-title').prepend('<p><strong>' + tabTitle + '</strong></p>');
		});
	});

	jQuery(window).resize(function() {
		responsiveTabs();
	});
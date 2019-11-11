function load_map(data){
	 var $ = jQuery;
	 var gdpData = { "AF": 16.63,"AL": 11.58, "DZ": 158.97,"IN":80};
	 
	 var data_total = data['total'];
	 var data_count = data['count'];
	 
	 $('#map1').vectorMap({
		map: 'world_mill_en',
		panOnDrag: true,
		focusOn: {
		  x: 0.5,
		  y: 0.5,
		  scale: 1,
		  animate: true
		},
		series: {
		  regions: [{
			scale: ['#C8EEFF', '#0071A4'],
			normalizeFunction: 'polynomial',
			values: data_total
		  }]
		},
		onRegionTipShow: function(e, el, code){
			if (!data_total[code]) {
				e.preventDefault();
		 	}else{
				el.html("Country: " +el.html()+' <br />Order Total: '+ic_ajax_object.currency_symbol+' '+data_total[code]+'<br /> Order Count: ' + data_count[code]);
			}
		}
	  });
}
jQuery(document).ready(function($) {
	jQuery.noConflict();
	jQuery(function(){
		var $ = jQuery;
		var columns = {};
		columns['action'] 			= ic_ajax_object.ic_ajax_action;
		columns['json_encode'] 		= 1;
		columns['do_action_type'] 	= 'map_details';
		columns['admin_page'] 		= ic_ajax_object.admin_page;
		columns['page'] 			= ic_ajax_object.admin_page;
		
		$.ajax({
			url: ic_ajax_object.ajaxurl,
			data:columns,
			dataType: "json",
			success:function(data){
				$('#map1 p.please_wait').hide();
				load_map(data);
			},
			error: function(jqxhr, textStatus, error ){												
				//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText)
				 console.log(textStatus, jqxhr.responseText);
			}
		});
	});
});
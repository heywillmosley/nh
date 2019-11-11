jQuery(document).ready(function($) {	
	
	//$('.ic_dashboard_summary_form').hide();
	
	jQuery( "#default_date_rage_start_date" ).datepicker({
		dateFormat : 'yy-mm-dd',		
		changeMonth: true,
		changeYear: true,
		maxDate:0,
		onClose: function( selectedDate ) {
			$( "#default_date_rage_end_date" ).datepicker( "option", "minDate", selectedDate );
		}
	});							
	
	jQuery( "#default_date_rage_end_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		changeMonth: true,
		changeYear: true,
		///maxDate: 0,
		onClose: function( selectedDate ) {
			$( "#default_date_rage_start_date" ).datepicker( "option", "maxDate", selectedDate );
		}
	}); 
	
	/*
	$('.ic_dashboard_summary_title span').click(function(){
		
	})
	*/
	
	var ic_dashboard_summary_form = false;
	
	$('.ic_dashboard_summary_change span').click(function(){
		if(!ic_dashboard_summary_form){
			$('.ic_dashboard_summary_title').hide();
			$('.ic_dashboard_summary_form').show();
			ic_dashboard_summary_form = true;
		}else{
			$('.ic_dashboard_summary_form').hide();
			$('.ic_dashboard_summary_title').show();			
			ic_dashboard_summary_form = false;
		}
	});
	
	$(".quick_date_change").change(function(){
		alert($(this).val());
	});
	
	jQuery( "#start_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		changeMonth: true,
		changeYear: true,
		maxDate:ic_commerce_vars['max_date_start_date'],
		onClose: function( selectedDate ) {
			$( "#end_date" ).datepicker( "option", "minDate", selectedDate );
		},beforeShow: function() {
			setTimeout(function(){
				$('.ui-datepicker').css('z-index', 99999999999999);
			}, 0);
		}
	});							
	
	jQuery( "#end_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		changeMonth: true,
		changeYear: true,
		onClose: function( selectedDate ) {
			$( "#start_date" ).datepicker( "option", "maxDate", selectedDate );
		},beforeShow: function() {
			setTimeout(function(){
				$('.ui-datepicker').css('z-index', 99999999999999);
			}, 0);
		}
	}); 
});

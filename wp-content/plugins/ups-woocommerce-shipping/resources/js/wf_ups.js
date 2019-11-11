jQuery(document).ready(function(){
	// Toggle pickup options
	wf_ups_load_pickup_options();
	jQuery('#woocommerce_wf_shipping_ups_pickup_enabled').click(function(){
		wf_ups_load_pickup_options();
	});
	
	wf_ups_load_declaration_statement();
	jQuery('#woocommerce_wf_shipping_ups_commercial_invoice').click(function(){
		wf_ups_load_declaration_statement();
	});

	jQuery('#woocommerce_wf_shipping_ups_pickup_date').change(function(){
		wf_ups_load_working_days();
	});
});

function wf_ups_load_pickup_options(){
	var checked	=	jQuery('#woocommerce_wf_shipping_ups_pickup_enabled').is(":checked");
	if(checked){
		jQuery('.wf_ups_pickup_grp').closest('tr').show();
	}else{
		jQuery('.wf_ups_pickup_grp').closest('tr').hide();
	}
	wf_ups_load_working_days();
}

function wf_ups_load_declaration_statement(){
	var checked	=	jQuery('#woocommerce_wf_shipping_ups_commercial_invoice').is(":checked");
	if(checked){
		jQuery('#woocommerce_wf_shipping_ups_declaration_statement').closest('tr').show();
	}else{
		jQuery('#woocommerce_wf_shipping_ups_declaration_statement').closest('tr').hide();
	}
}

function wf_ups_load_working_days(){
	var pickup_date = jQuery('#woocommerce_wf_shipping_ups_pickup_date').val();
	if( pickup_date != 'specific' ){
		jQuery('.pickup_working_days').closest('tr').hide();
	}else{
		jQuery('.pickup_working_days').closest('tr').show();
	}
}
var submitClicked = false;
var button_customize_columns_label = "Customize Column"

if (!Array.prototype.indexOf){
  Array.prototype.indexOf = function(searchElement /*, fromIndex */)
  {
	"use strict";

	if (this === void 0 || this === null)
	  throw new TypeError();

	var t = Object(this);
	var len = t.length >>> 0;
	if (len === 0)
	  return -1;

	var n = 0;
	if (arguments.length > 0)
	{
	  n = Number(arguments[1]);
	  if (n !== n) // shortcut for verifying if it's NaN
		n = 0;
	  else if (n !== 0 && n !== (1 / 0) && n !== -(1 / 0))
		n = (n > 0 || -1) * Math.floor(Math.abs(n));
	}

	if (n >= len)
	  return -1;

	var k = n >= 0
		  ? n
		  : Math.max(len - Math.abs(n), 0);

	for (; k < len; k++)
	{
	  if (k in t && t[k] === searchElement)
		return k;
	}
	return -1;
  };
}


function create_dropdown(data,all_list,select_id,parent_id,default_value,value_type){
	$ = jQuery;
	i = 1;
	var option_data = Array();
	
	var options = '<option value="-1">Select All</option>';
	if((parent_id.indexOf("-1") == 0 || parent_id.indexOf("-2") == 0) && parent_id.length == 1){
		jQuery.each(all_list, function(key,val){
			if(!option_data[val.id]){
				options += '<option value="' + val.id + '">' + val.label + '</option>';
				option_data[val.id] = val.label;
			}
			i++;
		});
	}else{
		if(value_type == "string"){
			jQuery.each(data, function(key,val){	
				//alert(default_value + "=="+val.parent_id + "(" + val.label);													
				if(parent_id == val.parent_id){
					if(!option_data[val.id]){
						options += '<option value="' + val.id + '">' + val.label + '</option>';
						option_data[val.id] = val.label;
					}
					i++;
				}
			});														
		}else{
			jQuery.each(data, function(key,val){														
				if(parent_id.indexOf(val.parent_id) >= 0){
					if(!option_data[val.id]){
						options += '<option value="' + val.id + '">' + val.label + '</option>';
						option_data[val.id] = val.label;
					}
					i++;
				}
			});
		}
		
	}
	
	if(default_value != "-2"){
		jQuery("select#"+select_id).html(options).val(default_value);
	}else{
		jQuery("select#"+select_id).html(options);
	}
}

function dump(arr, level) {
    var dumped_text = "";
    if (!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for (var j = 0; j < level + 1; j++) level_padding += "    ";

    if (typeof (arr) == 'object') { //Array/Hashes/Objects 
        for (var item in arr) {
            var value = arr[item];

            if (typeof (value) == 'object') { //If it is an array,
                dumped_text += level_padding + "'" + item + "' ...\n";
                dumped_text += dump(value, level + 1);
            } else {
                dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
            }
        }
    } else { //Stings/Chars/Numbers etc.
        dumped_text = "===>" + arr + "<===(" + typeof (arr) + ")";
    }
    return dumped_text;
}

function back_to_previous() {
	if(submitClicked) return false;
	window.history.back();
}

function detailViewFields(){
	if(jQuery("#detail_view").is(':checked')){		
		jQuery("#product_id, .detail_fields").attr('disabled',false).removeClass('disabled').attr('size',product_dropdown_size);
		jQuery("#category_id").attr('disabled',false).removeClass('disabled').attr('size',category_dropdown_size);
		jQuery("input[name='detail_view']").val('yes');
		jQuery(".detail_view_seciton_note").hide();
		jQuery(".normal_view_seciton_note").show();
		jQuery(".normal_view_only").attr('disabled',true);
		jQuery(".details_view_only").attr('disabled',false);
	}else{
		//alert(4)
		jQuery("#product_id, .detail_fields").addClass('disabled').attr('size',2).attr('disabled',true);
		jQuery("#category_id").addClass('disabled').attr('size',2).attr('disabled',true);
		jQuery("input[name='detail_view']").val('no');
		jQuery(".detail_view_seciton_note").show();
		jQuery(".normal_view_seciton_note").hide();
		jQuery(".normal_view_only").attr('disabled',false);
		jQuery(".details_view_only").attr('disabled',true);
	}
	
	var v = jQuery('#button_customize_columns').val();
	if(v == "Hide column view"){		
		if(jQuery("#detail_view").is(':checked')){
			jQuery(".search_by_details_fields").show();
			jQuery(".search_by_normal_fields").hide();
		}else{
			jQuery(".search_by_details_fields").hide();
			jQuery(".search_by_normal_fields").show();
		}
	}
}

function pull_product_dropdonw(){
	var $ = jQuery;
	var category_id = $('#category_id').val();
	var purchased_product_id = $('#purchased_product_id').val();
	var submitButton = $('form#search_order_report').find('input[type="submit"]');											
	submitButton.attr('disabled',true).addClass('disabled');
	$('.ajax_progress').html("Please wait").fadeIn();
	
	$(".form_process").fadeIn();
	$(".onformprocess").attr('disabled',true).addClass('disabled');
	
	submitClicked = true;
	
	var action_data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "product",
			'category_id'		: category_id
        }
		
		$(".update-nag.ic_ajax_error").hide();
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  action_data,
			dataType: "json",
			success:function(data) {								
					//alert(JSON.stringify(data))
					//alert(JSON.stringify(data.success_output))
					submitClicked = false;
					if(data.error == "true"){
						//errorString =  errorString + "<li>"+data.error_output+"</li>";
						//$('.woocommerce-ajax-error').addClass('woocommerce-error').removeClass('woocommerce-info').html(errorString).fadeIn();
					}else if(data.success == "true"){
						
						var options = $('#product_id');
						options.empty(); // empty the dropdown (if necessarry)
						if(data.success_output.length > 1)	options.append('<option value="-1">Select Product</select>');
						$.each(data.success_output, function(i,v) {
							options.append($("<option />").val(v.id).html(v.label));
						});
						
					}
					submitButton.attr('disabled',false).removeClass('disabled');
					$('.ajax_progress').html("Please wait").fadeOut();
					$(".form_process").hide();
					$(".onformprocess").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				submitClicked = false;								
				//window.location = window.location;
				//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText)
				submitButton.attr('disabled',false).removeClass('disabled');
				$(".form_process").hide();
				$(".onformprocess").attr('disabled',false).removeClass('disabled');
				display_ajax_error(jqxhr);
				
				
				
			}
		});
}

function checkbox_check(do_action_type){
	var checked_count = jQuery('.'+do_action_type+' input:checked').size();
		
	if(checked_count <= 1) {
		jQuery('.'+do_action_type+' input:checked').attr('disabled','disabled').addClass('disabled').parent().addClass('disabled');
	}else{
		jQuery('.'+do_action_type+' input:checked').removeAttr('disabled').removeClass('disabled').parent().removeClass('disabled');
	}
}

function save_columns(columns, do_action_type){
	
	//alert(JSON.stringify(columns, null, "\t"));
	$(".update-nag.ic_ajax_error").hide();
	jQuery.ajax({
		type: "POST",
		url: ic_ajax_object.ajaxurl,
		data:  columns,
		dataType: "json",
		success:function(data) {			
			//jQuery(".form_process").hide();
			//alert(JSON.stringify(data, null, "\t"));
			jQuery(".onformprocess").attr('disabled',false).removeClass('disabled');
		},
		error: function(jqxhr, textStatus, error ){
			//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText)
			jQuery(".form_process").hide();
			jQuery(".onformprocess").attr('disabled',false).removeClass('disabled');
			display_ajax_error(jqxhr);
		}
	});
}

var popup_open = false;
var popup_id = null;
function center() {
	if (popup_open == true) {
		if(popup_id == null) return;
		
		obj = popup_id;
		var $ = jQuery;
		var windowWidth = document.documentElement.clientWidth;
		var windowHeight = document.documentElement.clientHeight;
		var popupHeight = $(obj).height();
		var popupWidth = $(obj).width();
		$(obj).css({
			"position": "fixed",
			"top": windowHeight / 2 - popupHeight / 2,
			"left": windowWidth / 2 - popupWidth / 2
		}).fadeIn();
	}
}

function hidePopup() {
	var $ = jQuery;
    if (popup_open == true) {
        $(".popup_mask").fadeOut("slow");
        $(".popup_box").fadeOut("slow");
        popup_open = false;
		popup_id = null;
    }
}

var print_page_active = false;
function back_to_detail(){
	if(!print_page_active) return false;
	print_page_active = false;
	$ = jQuery;
	$('.hide_for_print').show();
	$('.hide_for_print').show();
	$('.hide_for_print').show();
	$('.search_for_print_block').hide();
	return true;
}

function print_report(){
	window.print();
}

function enable_variation_dropdown(){
	
}

var selected_products 		= Array();
var selected_variations  	= Array();
var selected_simple_products  	= Array();
  
function enable_disable_product_variation_fields(){
	
		var options = '';
	
		var show_variation_value = jQuery("#show_variation").val();
		
		if(show_variation_value == 1 || show_variation_value == 'variable'){
			
			selected_products = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_products[i] = jQuery( selected ).val();
			});
			
			selected_simple_products = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_simple_products[i] = jQuery( selected ).val();
			});
			
			//selected_variations = selected_variations.concat(selected_products);
			
			if(variation_products.length > 0){											
				options = '<option value="-1">Select All</option>';
				jQuery.each(variation_products, function(key,val){
					options += '<option value="' + val.id + '">' + val.label + '</option>';
				});
				jQuery("select#product_id2").html(options);
				jQuery("select#product_id2").val(selected_variations).attr('size',5);
			}
			
			//alert(show_variation_value);
			
			
			
		}else if(show_variation_value == 'simple'){
			selected_products = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_products[i] = jQuery( selected ).val();
			});
			
			selected_variations = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_variations[i] = jQuery( selected ).val();
			});
			
			//selected_variations = selected_variations.concat(selected_products);
			
			
			
			if(simple_products.length > 0){											
				options = '<option value="-1">Select All</option>';
				jQuery.each(simple_products, function(key,val){
					options += '<option value="' + val.id + '">' + val.label + '</option>';
				});
				jQuery("select#product_id2").html(options);
				jQuery("select#product_id2").val(selected_simple_products).attr('size',5);
			}
		}else{
			
			selected_simple_products = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_simple_products[i] = jQuery( selected ).val();
			});
			
			selected_variations = Array();
			jQuery('select#product_id2 option:selected').each( function( i, selected ) {
				selected_variations[i] = jQuery( selected ).val();
			});
			
			if(products.length > 0){
				//alert(products.length)
				options = '<option value="-1">Select All</option>';
				jQuery.each(products, function(key,val){
					options += '<option value="' + val.id + '">' + val.label + '</option>';
				});
				jQuery("select#product_id2").html(options);					
				jQuery("select#product_id2").val(selected_products).attr('size',5)
			}
			
		}
		
		variationPageOrderDropdown(show_variation_value);
		
		if(show_variation_value == 1 || show_variation_value == 'variable'){
			jQuery("#variations").attr('disabled',false).removeClass('disabled');
			jQuery("#variation_column").attr('disabled',false).removeClass('disabled');
			jQuery("#variation_group_by").attr('disabled',false).removeClass('disabled');
			jQuery("#variation_sku").attr('disabled',false).removeClass('disabled');
			jQuery(".variation_dropdowns").attr('disabled',false).removeClass('disabled');
			jQuery(".detail_variation_seciton_note").hide();
			
			jQuery("#variations").trigger('change');
			
		}else{
			jQuery("#variations").attr('disabled',true).addClass('disabled');
			jQuery("#variation_column").attr('disabled',true).addClass('disabled');
			jQuery("#variation_group_by").attr('disabled',true).addClass('disabled');
			jQuery("#variation_sku").attr('disabled',true).addClass('disabled');
			jQuery(".variation_dropdowns").attr('disabled',true).addClass('disabled');
			jQuery(".detail_variation_seciton_note").show();
		}
		
		
}

jQuery(document).ready(function($) {		
	if(jQuery("#show_variation").length > 0){
		//alert(jQuery("#show_variation").length)
		enable_disable_product_variation_fields();
		$('#show_variation').change(function(){
			enable_disable_product_variation_fields();
		});
	}
});

//var variationPageOrderData = {"product_name":"Product Name","ProductID":"Product ID","variation_id":"Variation ID","sku":"SKU","amount":"Amount"};
var variationPageOrderData = {"product_name":"Product Name","ProductID":"Product ID","variation_id":"Variation ID","amount":"Amount"};

function variationPageOrderDropdown(show_variation_value){
	var options = "";
	if(show_variation_value == 1 || show_variation_value == 'variable'){
		jQuery.each(variationPageOrderData, function(key,val){
			options += '<option value="' + key + '">' + val + '</option>';
		});
	}else{
		jQuery.each(variationPageOrderData, function(key,val){
			if(key != 'variation_id')
				options += '<option value="' + key + '">' + val + '</option>';
		});
	}
	
	jQuery("select#sort_by").html(options);
}


function set_all_detail_refund_detail_tab(){
	
	var $ = jQuery;
	var report_name 			= $("#report_name").val();
	if(report_name == "manual_refund_detail_page"){
		var refund_status_type 	= $("#refund_status_type").val();
		var group_by 			= $("#group_by").val();
		
		if(refund_status_type == "part_refunded"){
			if(group_by == "order_id"){
				 //$("#group_by").val('refund_id');
				 $("#show_refund_note").attr('disabled',true);
			}else{
				if(group_by == "refund_id"){
					$("#show_refund_note").attr('disabled',false);
				}else if(group_by == "order_id"){
					$("#show_refund_note").attr('disabled',true);
				}else{
					$("#show_refund_note").attr('disabled',true);
				}
			}
			
			
			
		}else if(refund_status_type == "status_refunded"){
			if(group_by == "refund_id"){
				$("#show_refund_note").attr('disabled',false);
			}else{
				$("#show_refund_note").attr('disabled',true);
			}
			
		}
		
	}
}

var product_dropdown_size = 0;
var category_dropdown_size = 0;
var status_dropdown_size = 0;

jQuery(document).ready(function($) {
	
	$(document).on('click',"input.stock_email_alert", function(){
		
		//var that = this;
		
		if(submitClicked) return false;
		
		//$('.ajax_progress').html("Please wait").fadeIn();
		//$(".form_process").fadeIn();
		$(".onformprocess").attr('disabled',true).addClass('disabled');
		var product_id 			= $(this).attr("data-product_id");
		submitClicked 			= true;
		var data 				= {};
		data['action'] 			= ic_ajax_object.ic_ajax_action;
		data['product_id'] 		= product_id;
		data['do_action_type'] 	= "save_stock_email_alert";
		
		if($(this).is(':checked')){
			data['checked'] 	= 1;
		}else{
			data['checked'] 	= 0;
		}
		
		$(".update-nag.ic_ajax_error").hide();
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  data,
			//dataType: "json",
			success:function(data) {
				//alert(data)
				//alert(JSON.stringify(data))
				submitClicked = false;				
				$('.ajax_progress').html("Please wait").fadeOut();
				$(".form_process").hide();
				$(".onformprocess").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				submitClicked = false;				
				$('.ajax_progress').html("Please wait").fadeOut();
				$(".form_process").hide();
				$(".onformprocess").attr('disabled',false).removeClass('disabled');
				display_ajax_error(jqxhr);
			}
		});
		
		return true;
				
	});
	

	var l = 0;
	//l  = jQuery("#order_status_id").find('option').size();	
	status_dropdown_size = jQuery("#order_status_id").attr('data-size');
	//if(status_dropdown_size) status_dropdown_size = l	
	product_dropdown_size = jQuery("#product_id").attr('data-size');
	category_dropdown_size = jQuery("#category_id").attr('data-size');
	
	
	detailViewFields();
	
	
	$(".form_process").css({"opacity":0.5});
	$(".form_process").hide();
	
	$('#detail_view').change(function(){
		detailViewFields();
		jQuery('#p').val(1);						
		$('form#search_order_report').submit();
	});
	
	$('#category_id').change(function(){
		//alert('fdasfdasf');
		//pull_product_dropdonw();
	});
	
	set_all_detail_refund_detail_tab();	
	$('#refund_status_type, #group_by').change(function(){
		set_all_detail_refund_detail_tab();
	});
	
	total_shop_day = "-"+ic_ajax_object.total_shop_day+"D";
	
	
	jQuery( "#start_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		//defaultDate: "+1w",/*
		changeMonth: true,
		changeYear: true,
		//numberOfMonths: 3,
		//maxDate: 0,
		//minDate: total_shop_day,
		maxDate:ic_commerce_vars['max_date_start_date'],
		onClose: function( selectedDate ) {
			$( "#end_date" ).datepicker( "option", "minDate", selectedDate );
		}
	});							
	
	//alert(ic_commerce_vars['max_date_end_date'])
	jQuery( "#end_date" ).datepicker({
		//dateFormat : 'mm-dd-yy',
		dateFormat : 'yy-mm-dd',
		//defaultDate: "+1w",/*
		changeMonth: true,
		changeYear: true,
		maxDate: 0,
		//minDate: 0,
		//maxDate:"+396D",
		//numberOfMonths: 3,
		onClose: function( selectedDate ) {
			$( "#start_date" ).datepicker( "option", "maxDate", selectedDate );
		}
	}); 
	
	
	$("#cross_tab_start_date").datepicker({
        //minDate: "dateToday",
        changeMonth: true,
		//changeYear: true,
        dateFormat : 'yy-mm-dd',
        onClose: function (selectedDate, instance) {
            if (selectedDate != '') {
                $("#cross_tab_end_date").datepicker("option", "minDate", selectedDate);
                var date = $.datepicker.parseDate(instance.settings.dateFormat, selectedDate, instance.settings);
				date.setMonth(date.getMonth() + 12);				
				var newEndDate = new Date(date.getFullYear(), date.getMonth(), date.getDate()-1);
				
                $("#cross_tab_end_date").datepicker("option", "minDate", selectedDate);
                $("#cross_tab_end_date").datepicker("option", "maxDate", newEndDate);
            }
        }
    });
    $("#cross_tab_end_date").datepicker({
        //minDate: "dateToday",
        changeMonth: true,
		//changeYear: true,
        dateFormat : 'yy-mm-dd',
        onClose: function (selectedDate) {
            $("#cross_tab_start_date").datepicker("option", "maxDate", selectedDate);
        }
    });
	
	
/*	$("#ga_ao_start_date").datepicker({
        //minDate: "dateToday",
        changeMonth: true,
        dateFormat : 'yy-mm-dd',
        onClose: function (selectedDate, instance) {
            if (selectedDate != '') {
                $("#ga_ao_end_date").datepicker("option", "minDate", selectedDate);
                var date = $.datepicker.parseDate(instance.settings.dateFormat, selectedDate, instance.settings);
				date.setMonth(date.getMonth() + 3);				
				var newEndDate = new Date(date.getFullYear(), date.getMonth(), date.getDate()-1);
				
                $("#ga_ao_end_date").datepicker("option", "minDate", selectedDate);
                $("#ga_ao_end_date").datepicker("option", "maxDate", newEndDate);
            }
        }
    });
    $("#ga_ao_end_date").datepicker({
        //minDate: "dateToday",
        changeMonth: true,
        dateFormat : 'yy-mm-dd',
        onClose: function (selectedDate) {
            $("#ga_ao_start_date").datepicker("option", "maxDate", selectedDate);
        }
    });
	*/
	
	jQuery( "#ga_ao_start_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		changeMonth: true,
		maxDate:0,
		onClose: function( selectedDate ) {
			$( "#ga_ao_end_date" ).datepicker( "option", "minDate", selectedDate );
		}
	});							
	
	jQuery( "#ga_ao_end_date" ).datepicker({
		dateFormat : 'yy-mm-dd',
		changeMonth: true,
		maxDate: 0,
		onClose: function( selectedDate ) {
			$( "#ga_ao_start_date" ).datepicker( "option", "maxDate", selectedDate );
		}
	}); 
	
	
	$.fn.slideFadeToggle = function(speed, easing, callback) {
		return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
	};
	
	if(ic_ajax_object.defaultOpen == "undefined"){
		defaultOpen = '';
	}else{
		defaultOpen = ic_ajax_object.defaultOpen;
	}
	
	$('.collapsible').collapsible({
		defaultOpen: defaultOpen, //'section1',
		//cookieName: 'nav',
		speed: 'slow'
	});	
	
	
	$(document).on('click','.pagination a',  function(){
		var p = $(this).attr('data-p');
		$('form#search_order_pagination').find('input[name=p]').val(p);			
		$('form#search_order_pagination').submit();
		return false;
	});
	
	$('input#SearchOrder').click(function(){
		$('#p').val(1);
	});

	$(document).on('submit','form#search_order_pagination',  function(){
		
		if(submitClicked) return false;
		
		$('.ajax_progress').html("Please wait").fadeIn();
		$(".form_process").fadeIn();
		$(".onformprocess").attr('disabled',true).addClass('disabled');
		submitClicked = true;
		
		$(".update-nag.ic_ajax_error").hide();
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  $( "form#search_order_pagination" ).serialize(),
			//dataType: "json",
			success:function(data) {
				//alert(JSON.stringify(data))
				submitClicked = false;
				$('div.search_report_content').html(data);								
				$('.ajax_progress').html("Please wait").fadeOut();
				$(".form_process").hide();
				$(".onformprocess").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				submitClicked = false;								
				//window.location = window.location;
				$(".form_process").hide();
				$(".onformprocess").attr('disabled',false).removeClass('disabled');
				display_ajax_error(jqxhr);
			}
		});
		return false;
	});
	
	
	
	$('form#search_order_report').submit(function(){
			if(submitClicked) return false;
			
			var errorString = "";					
			var submitButton = $(this).find('input[type="submit"]');											
			submitButton.attr('disabled',true).addClass('disabled');
			
			if(errorString.length > 1 ){			
				//$('.ajax_progress').addClass('woocommerce-error').removeClass('woocommerce-info').html(errorString).fadeIn();
				submitClicked = false;
				submitButton.attr('disabled',false).removeClass('disabled');
				return false;
			}
			$(this).find('input[type="submit"]').attr('disabled',true);
			$('.ajax_progress').html("Please wait").fadeIn();
			$(".form_process").fadeIn();
			$(".onformprocess").attr('disabled',true).addClass('disabled');
			
			$('input[type="text"]').each(function(index, element) {
				var v = $.trim($(element).val());
				$(element).val(v);
			});
			
			submitClicked = true;
			
			//alert(ic_ajax_object.ajaxurl)
			
			$(".update-nag.ic_ajax_error").hide();			
			$.ajax({
				type: "POST",
				url: ic_ajax_object.ajaxurl,
				data:  $( "form#search_order_report" ).serialize(),
				//dataType: "json",
				success:function(data) {
					//alert(JSON.stringify(data))
					submitClicked = false;
					//alert(data)
					$('div.search_report_content').html(data);								
					submitButton.attr('disabled',false).removeClass('disabled');
					$('.ajax_progress').html("Please wait").fadeOut();
					$(".form_process").hide();
					$(".onformprocess").attr('disabled',false).removeClass('disabled');
				},
				error: function(jqxhr, textStatus, error ){
					submitClicked = false;	
					//alert(JSON.stringify(jqxhr))							
					//window.location = window.location;
					submitButton.attr('disabled',false).removeClass('disabled');
					$(".form_process").hide();
					$(".onformprocess").attr('disabled',false).removeClass('disabled');					
					display_ajax_error(jqxhr);
				}
			});
			return false;
	});
	
	var search_content = $.trim($('div.search_report_content').html());
	if(search_content.length == 0){
		$('div.search_report_content').html("Please wait!");
		$('#SearchOrder').trigger('click');	
	}
	
	//$('.search_for_print').click(function(){			
	$(document).on('click','input.search_for_print',  function(){
		
			
			
			if(submitClicked) return false;
			submitClicked = true;
			
			
			
			$('.hide_for_print').hide();
			$('.hide_for_print').hide();
			$('.hide_for_print').hide();
			$('.search_for_print_block').show().html("Please wait");
			var do_action_type = $(this).attr('data-do_action_type');
			var form = $(this).attr('data-form');
			
			var data = {};		
			
			if(form == "popup"){
				$(this).parent().parent().parent().parent().parent().find('input[type="hidden"]').each(function(index, element) {
					var _name = $(element).attr('name');
					if(_name != "export_file_format");
					data[_name] = $(element).val();
					
				});
				
				$(this).parent().parent().parent().parent().parent().find('input[type="text"]').each(function(index, element) {
					var _name = $(element).attr('name');
					data[_name] = $(element).val();
					
				});
				
				$(this).parent().parent().parent().parent().parent().find('input[type="checkbox"]').each(function(index, element) {
					var _name = $(element).attr('name');
					if(jQuery(element).is(':checked')){
						data[_name] = $(element).val();
					}
					
				});
			}else{
				$(this).parent().find('input[type="hidden"]').each(function(index, element) {
					var _name = $(element).attr('name');
					if(_name != "export_file_format");
					data[_name] = $(element).val();
					
				});
			};
			
			
			
			
			//alert($(this).parent().parent().parent().parent().parent().html())
			
			//alert(JSON.stringify(data))
			
			data['action'] = ic_ajax_object.ic_ajax_action;
			data['do_action_type'] = do_action_type;
			
			print_page_active = true;
			
			$(".update-nag.ic_ajax_error").hide();
			$.ajax({
				type: "POST",
				url: ic_ajax_object.ajaxurl,
				data: data,
				success:function(data) {
					if(print_page_active)
						$('div.search_for_print_block').html(data);
					submitClicked = false;
				},
				error: function(jqxhr, textStatus, error ){					
					//window.location = window.location;
					submitClicked = false;
					display_ajax_error(jqxhr);
				}
			});
			return false;
	});
	
	//search_for_print
	

	$("label.label_order_id_save_detail_column").addClass('order_checkbox');
	$("label.label_billing_name_save_detail_column").addClass('order_checkbox');
	$("label.label_billing_email_save_detail_column").addClass('order_checkbox');
	$("label.label_order_date_save_detail_column").addClass('order_checkbox');
	$("label.label_status_save_detail_column").addClass('order_checkbox');	
	
	$("label.label_category_name_save_detail_column").addClass('product_checkbox');
	$("label.label_product_name_save_detail_column").addClass('product_checkbox');
	$("label.label_product_quantity_save_detail_column").addClass('product_checkbox');
	$("label.label_product_rate_save_detail_column").addClass('product_checkbox');
	$("label.label_total_price_save_detail_column").addClass('product_checkbox');
	$("label.label_product_variation_save_detail_column").addClass('product_checkbox');
	
	
	checkbox_check('order_checkbox');
	checkbox_check('product_checkbox');
	
	$('.save_detail_column input').click(function(){
		var columns = {};
		
		$(".onformprocess").attr('disabled',true).addClass('disabled');
		//$(".form_process").show();
		
		checkbox_check('order_checkbox');
		checkbox_check('product_checkbox');
		
		$('.save_detail_column input').each(function(index, element) {
			var checkbox_name = $(element).attr('data-name');
			columns[checkbox_name] = 0;
			if ($(element).is(':checked')) {
				columns[checkbox_name] = 1;
			}
        });
		
		columns['action'] 			= ic_ajax_object.ic_ajax_action;
		columns['do_action_type'] 	= 'save_detail_column'
		columns['ic_admin_page']	= $('#ic_admin_page').val();
		
		save_columns(columns);
		
		var ccheckbox_name = $(this).attr('data-name');
		
		if ($(this).is(':checked')) {
			$("table.widefat_detial_table ."+ccheckbox_name).show();
		}else{
			$("table.widefat_detial_table ."+ccheckbox_name).hide();
		}
		return true;
		
		if(	
				$("#category_name_save_detail_column").is(':checked') 
			||	$("#product_name_save_detail_column").is(':checked')
			||	$("#product_quantity_save_detail_column").is(':checked')
			||	$("#total_price_save_detail_column").is(':checked')
		){
			$("table.widefat_detial_table .product_row").show();
		}else{
			$("table.widefat_detial_table .product_row").hide();
		}
		
	});
	
	
	checkbox_check('_order_checkbox');
	
	$('.save_normal_column input').click(function(){
		var columns = {};
		
		$(".onformprocess").attr('disabled',true).addClass('disabled');
		//$(".form_process").show();
		checkbox_check('_order_checkbox');
		
		$('.save_normal_column input').each(function(index, element) {
			var checkbox_name = $(element).attr('data-name');
			columns[checkbox_name] = 0;
			if ($(element).is(':checked')) {
				columns[checkbox_name] = 1;
			}
        });
		
		columns['action'] 			= ic_ajax_object.ic_ajax_action;
		columns['do_action_type'] 	= 'save_normal_column';
		columns['ic_admin_page']	= $('#ic_admin_page').val();
		
		
		
		save_columns(columns);
		
		var ccheckbox_name = $(this).attr('data-name');
		
		if ($(this).is(':checked')) {
			$("table.widefat_normal_table ."+ccheckbox_name).show();
			$(this).parent().parent().find('span.select_all_checkbox').removeClass('pendingforcheck');
		}else{
			$("table.widefat_normal_table ."+ccheckbox_name).hide();
			$(this).parent().parent().find('span.select_all_checkbox').addClass('pendingforcheck');
		}
		
	});
	
	$('.select_all_checkbox').click(function(){
		
		//if(!$(this).hasClass('pendingforcheck')) return false;
		
		var type = $(this).attr('data-type');
		var table = $(this).attr('data-table');
		
		$(".onformprocess").attr('disabled',true).addClass('disabled');
		
		var columns = {};
		$('.'+type+' input').each(function(index, element) {
			$(element).attr('checked',true);
			var checkbox_name = $(element).attr('data-name');
			columns[checkbox_name] = 1;
			$("table."+table+" ."+checkbox_name).show();
        });
		
		columns['action'] 			= ic_ajax_object.ic_ajax_action;
		columns['do_action_type'] 	= type;
		columns['ic_admin_page']	= $('#ic_admin_page').val();
		
		save_columns(columns)
		checkbox_check(type);
		
		$(this).removeClass('pendingforcheck');
		
	}).addClass('pendingforcheck');
	
	$('#button_customize_columns').click(function(){
		
		var v = jQuery(this).val();
		if(v == button_customize_columns_label){
			jQuery(this).val("Hide column view");
			if(jQuery("#detail_view").is(':checked')){
				jQuery(".search_by_details_fields").show();
				jQuery(".search_by_normal_fields").hide();
			}else{
				jQuery(".search_by_details_fields").hide();
				jQuery(".search_by_normal_fields").show();
			}
		}else{
			jQuery(".search_by_normal_fields").hide();
			jQuery(".search_by_details_fields").hide();
			$(this).val(button_customize_columns_label);
		}
	}).val(button_customize_columns_label);
	
	jQuery(".search_by_details_fields").hide();
	jQuery(".search_by_normal_fields").hide();
	
	
	$('#ResetForm').click(function(){
		if(jQuery("#detail_view").is(':checked')){
			jQuery("input[name='detail_view']").attr('checked',true);
		}else{
			jQuery("input[name='detail_view']").attr('checked',false);
		}
		
		detailViewFields();
	});
	
	/*jQuery('label.save_normal_column.disabled').click(function() {
		alert('Atleast one column should be selected.');
	});*/
	
	$('.popup_close, .popup_mask, .button_popup_close').click(function(){
		hidePopup();
	});
	
	$(document).keyup(function(e) {
		if(popup_id){
			//if (e.keyCode == 13) {$(popup_id).find("input[type='submit']").click();  }     // enter
			if (e.keyCode == 27) {hidePopup();}   // esc
		}
		if (e.keyCode == 27){
			if(print_page_active) back_to_detail();
		}
	});
	
	//popup
	$(document).on('click','input.open_popup',  function(){	
	
		if(submitClicked) return false;
			
		var obj 		= this;	
		var popupid 	= $(obj).attr("data-popupid");
		var hiddenbox 	= $(obj).attr("data-hiddenbox");
		var format 		= $(obj).attr("data-format");
		var popupbutton	= $(obj).attr("data-popupbutton");
		var title 		= $(obj).attr("data-title");
		popup_open 		= true;
		popup_id 		= "#" + popupid;
		
		$('.'+hiddenbox).html('');
		$(obj).parent().find('input[type="hidden"]').each(function(index, element) {
			$(element).clone().appendTo('.'+hiddenbox);
			//alert(hiddenbox)
        });
		
		var page_name = $(obj).parent().find('input[name="page_name"]').val();

		if(page_name == "all_detail" || page_name == "cross_tab_detail"){
			
			if($(obj).parent().find('input[name="formatted_start_date"]').length > 0){
				var from_date 	= $(obj).parent().find('input[name="formatted_start_date"]').val();				
			}else{
				var from_date 	= $(obj).parent().find('input[name="start_date"]').val();
			}			
			
			if($(obj).parent().find('input[name="formatted_end_date"]').length > 0){
				var to_date 	= $(obj).parent().find('input[name="formatted_end_date"]').val();				
			}else{
				var to_date 	= $(obj).parent().find('input[name="end_date"]').val();
			}
			
			var page_title 	= $(obj).parent().find('input[name="page_title"]').val();
			
			//var report_title = page_title+ " From "+ from_date + " To " + to_date;
			
			var report_title = page_title;
			
			if(from_date){
				report_title = report_title+ " From "+ from_date;
			}
			
			if(to_date){
				report_title = report_title+ " To "+ to_date;
			}
			
			$(popup_id).find("input[name='report_title']").val(report_title);
			var set_report_title = $.trim($(popup_id).find("input[name='report_title']").attr("data-report_title")); ///alert(set_report_title)			
			if(set_report_title.length>0){
				$(popup_id).find("input[name='report_title']").val(set_report_title + " ("+report_title+")");	
			}else{
				$(popup_id).find("input[name='report_title']").val(report_title);	
			}
		}
		
		if(page_name == "coupon_page"){
			var page_title 	= $(obj).parent().find('input[name="page_title"]').val();
			var report_title = page_title;
			$(popup_id).find("input[name='report_title']").val(report_title);	
		}
		
		$(popup_id).find("input[name='export_file_format']").val(format);
		$(popup_id).find("input[type='submit']").val(popupbutton);
		$(popup_id).find("h4").html(title);
		
		$(".popup_mask").fadeIn();
		$(popup_id).fadeIn("slow");
		center();
		
		return false;
	});
	
	//popup resize
	$( window ).resize(function() {
		center();
	});
	
	$("#variations").change(function(){
		var l = jQuery("#variations :checked").length;
		
			
		if(l > 0){
			jQuery(".variation_dropdowns").attr('disabled',true).addClass('disabled');
			
			jQuery("#variations option:selected" ).each(function() {
				var variation_dropdowns = $( this ).val();
				if(variation_dropdowns == "-1"){
					jQuery(".variation_dropdowns").attr('disabled',false).removeClass('disabled');
				}else{
					variation_dropdowns = variation_dropdowns.replace('-', '_');
					jQuery("#new_variations_value_"+variation_dropdowns).attr('disabled',false).removeClass('disabled');
				}
			});	
		}else{
			jQuery("#variations option" ).each(function() {
				var variation_dropdowns = $( this ).val();
				variation_dropdowns = variation_dropdowns.replace('-', '_');
				jQuery("#new_variations_value_"+variation_dropdowns).attr('disabled',false).removeClass('disabled');
			});
		}
	});
});

function display_ajax_error(jqxhr){
	//$(".update-nag.ic_ajax_error").hide();
	//display_ajax_error(jqxhr);
	var $ = jQuery;
	if($(".update-nag.ic_ajax_error").length <=0){
		$( "h2.hide_for_print" ).before( "<div class='update-nag ic_ajax_error' style=\"padding:15px; margin-bottom:10px;\"></div>" );
	}
	
	var ajax_error = "<strong>Ajax error</strong>";
	ajax_error = ajax_error + "<br />responseText:-" + jqxhr.responseText+ "<br />statusText:-" + jqxhr.statusText;
	
	$(".update-nag.ic_ajax_error").show().html("<p>"+ajax_error+"</p>");
}
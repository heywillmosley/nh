jQuery(document).ready(function($) {	
	var custom_uploader;
	var upload_this = null;
	
	$('a.ic_upload_button').click(function(e) {
		upload_this = $(this);
		e.preventDefault();
		//If the uploader object has already been created, reopen the dialog
		if (custom_uploader) {
			custom_uploader.open();
			return;
		}
		//Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose File',
			//frame: 'post',
			button: {
				text: 'Choose File'
			},
			multiple: false
		});
		//When a file is selected, grab the URL and set it as the text field's value
		custom_uploader.on('select', function() {
			attachment = custom_uploader.state().get('selection').first().toJSON();
			upload_this.parent().find('input[type=text].upload_field').val(attachment.url);
		});
		//Open the uploader dialog
		custom_uploader.open();
	});
	
	$('.clear_textbox').click(function(){
		$(this).parent().find('input[type=text]').val('');
	});
	
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
	
	var projected_amount_list = Array();
	var last_projected_amount_list = Array();
	var last_projected_sales_year = $('#projected_sales_year').val();
	
	$('#projected_sales_year').change(function(){
		var projected_sales_year = $(this).val();
		projected_sales_year = parseInt(projected_sales_year);
		
		if(projected_sales_year <= 0) {
			$('.projected_sales_month_textbox').each(function(index, element) {
				$(element).val(0);
			});
			return false;
		}
	
		$("#ic_please_wait").css({ "opacity":"0.8"});
		
		if(!projected_amount_list[projected_sales_year]){
			var action_data = {
				"action"				: ic_ajax_object.ic_ajax_action,
				"do_action_type"		: "projected_sales_year",
				'projected_sales_year'	: projected_sales_year
				
			}
			
			$('.ic_close_button').hide();
			$(".ic_please_wait_msg").html("Please Wait");			
			$("#ic_please_wait").fadeIn();
			$("#ic_please_wait").addClass('ic_please_wait');
			$(".ic_save_setting").attr('disabled',true).addClass('disabled');
			
			$('.projected_sales_month_textbox').each(function(index, element) {
				$(element).val(0);
			});
			
			$.ajax({
				type: "POST",
				url: ic_ajax_object.ajaxurl,
				data:  action_data,
				dataType: "json",
				success:function(data) {								
					//alert(JSON.stringify(data))
					if(data.success == "true"){
						var projected_amounts = projected_amount_list[projected_sales_year] = data.projected_amounts;
						var x = 0;
						$.each(projected_amounts, function(i,v) {
							//last_projected_amount_list[i] = $("#projected_sales_month_"+i).val();							
							//$("#projected_sales_month_"+i).val(projected_sales_year - 1500 + x);x++;
							$("#projected_sales_month_"+i).val(v);
						});						
						//projected_amount_list[last_projected_sales_year] = last_projected_amount_list;
						//last_projected_sales_year = projected_sales_year;
					}
					$("#ic_please_wait").hide();
					$("#ic_please_wait").removeClass('ic_please_wait');
					$(".ic_save_setting").attr('disabled',false).removeClass('disabled');
				},
				error: function(jqxhr, textStatus, error ){
					alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText);
					$("#ic_please_wait").hide();
					$("#ic_please_wait").removeClass('ic_please_wait');
					$(".ic_save_setting").attr('disabled',false).removeClass('disabled');
				}
			});
		}else{
			var projected_amounts = projected_amount_list[projected_sales_year];
			$.each(projected_amounts, function(i,v) {
				$("#projected_sales_month_"+i).val(v);
			});
		}
	});
	
	$(".ic_close_button").click(function(){
		$('.ic_close_button').hide();
		$("#ic_please_wait").hide();
		$("#ic_please_wait").removeClass('ic_please_wait');
		
		ajax_on_processing = false;
		
	});
	
	var ajax_on_processing = false;
	
	/*
	$('#email_report_actions_order_status_mail').click(function(){
		
		if(ajax_on_processing) return false;
		
		var action_data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "order_status_email"
        }
		
		$('.ic_close_button').hide();
		$(".ic_please_wait_msg").html("Please Wait");
		$("#ic_please_wait").fadeIn();
		$("#ic_please_wait").addClass('ic_please_wait');
		
		ajax_on_processing = true;
		
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  action_data,
			success:function(data) {								
				$('.ic_close_button').fadeIn();
				$(".ic_please_wait_msg").html(data);				
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText);
				$("#ic_please_wait").hide();
				$("#ic_please_wait").removeClass('ic_please_wait');
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
				
				ajax_on_processing = false;
			}
		});
		
		return '';
	});
	
	$('#email_report_actions_order_dashboard_email').click(function(){
		
		if(ajax_on_processing) return false;
		
		var action_data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "order_dashboard_email"
        }
		
		$('.ic_close_button').hide();
		$(".ic_please_wait_msg").html("Please Wait");
		$("#ic_please_wait").fadeIn();
		$("#ic_please_wait").addClass('ic_please_wait');
		
		ajax_on_processing = true;
		
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  action_data,
			success:function(data) {								
				$('.ic_close_button').fadeIn();
				$(".ic_please_wait_msg").html(data);				
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText);
				$("#ic_please_wait").hide();
				$("#ic_please_wait").removeClass('ic_please_wait');
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
				
				ajax_on_processing = false;
			}
		});
		
		return '';
	});
	*/
	
	$('.test_email_schedule').click(function(){
		
		if(ajax_on_processing) return false;
		
		var action_data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: $(this).attr("data-sub_action")
        }
		
		//alert($(this).attr("data-sub_action"))
		
		$('.ic_close_button').hide();
		$(".ic_please_wait_msg").html("Please Wait");
		$("#ic_please_wait").fadeIn();
		$("#ic_please_wait").addClass('ic_please_wait');
		
		ajax_on_processing = true;
		
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  action_data,
			success:function(data) {								
				$('.ic_close_button').fadeIn();
				$(".ic_please_wait_msg").html(data);				
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
			},
			error: function(jqxhr, textStatus, error ){
				//alert(jqxhr + "  - " + textStatus + "  - " + error + " --- " + jqxhr.responseText);
				$("#ic_please_wait").hide();
				$("#ic_please_wait").removeClass('ic_please_wait');
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
				
				ajax_on_processing = false;
			}
		});
		
		return '';
	});
	
	$('.cogs_metakey_textbox').attr('readonly',true).addClass('readonly').dblclick(function(){
		if(ajax_on_processing) return false;
		$(this).attr('readonly',false).removeClass('readonly');
	});
	
	$('.cogs_metakey_textbox').blur(function(){
		
		var this_object = this;
		
		if(ajax_on_processing) return false;
		
		if($(this_object).hasClass('readonly')){
			return true;
		}
				
		var cogs_metakey = $.trim($(this_object).val());
		
		cogs_metakey = cogs_metakey.replace(/\s+/g, '_').toLowerCase();
		
		$(this_object).val(cogs_metakey);
		
		var default_metakey = $(this_object).attr('data-default_value');
		
		if(cogs_metakey == default_metakey) return true;		
		
		if(ajax_on_processing) return false;
		
		var action_data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "check_cogs_exits",
			"cogs_metakey"		: cogs_metakey,
			"default_metakey"	: default_metakey
        }
		
		$('.ic_close_button').hide();
		$(".ic_please_wait_msg").html("Please Wait, checking "+cogs_metakey + " is exits.");
		$("#ic_please_wait").fadeIn();
		$("#ic_please_wait").addClass('ic_please_wait');
		
		ajax_on_processing = true;
		
		$(this_object).attr('readonly',true);
		
		$.ajax({
			type: "POST",
			url: ic_ajax_object.ajaxurl,
			data:  action_data,
			success:function(data) {
				var count = parseInt(data);
				$('.ic_close_button').show();
				if(count > 0){
					$(".ic_please_wait_msg").html("we found your new entred cost meta key.");
				}else{					
					$(".ic_please_wait_msg").html("We are not found your enter cost meta key.");
				}				
				ajax_on_processing = false;
				$(this_object).attr('readonly',false);
			},
			error: function(jqxhr, textStatus, error ){				
				ajax_on_processing = false;
				$("#ic_please_wait").hide();
				$("#ic_please_wait").removeClass('ic_please_wait');
				$(".email_report_actions_order_status_mail").attr('disabled',false).removeClass('disabled');
				$(this_object).attr('readonly',false);
			}
		});
	});
	
	
	
	
	$("form.form_ic_commerce_settings.force_submit").submit(function(){
		if(ajax_on_processing) return false;
	});
	
	$("#graph_setting_action_reset").click(function(event) {
		var r = confirm("Do you want to reset Graph Settings? \nPress \"OK\" for reset. \nUpon reset please click \"Save Changes\" button.");
		if (r == true) {
			$("#graph_height").val(300);
			$("#tick_angle").val(0);
			$("#tick_font_size").val(9);
			$("#tick_char_length").val(15);
			$("#tick_char_suffix").val("...");
			$("#graph_setting_action_reset").hide();
		}
	});
});
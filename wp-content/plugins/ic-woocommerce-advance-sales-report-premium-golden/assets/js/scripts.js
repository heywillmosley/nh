var ic_commerce_vars 					= Array();
var total_shop_day 						= "-365D";

ic_commerce_vars['country_dropdown']	=	5;
ic_commerce_vars['max_date_start_date']	=	0;
ic_commerce_vars['max_date_end_date']	=	0;

jQuery(document).ready(function($) {
	
	$("input.numberonly").each(function(index, element) {
		var that 		= this;		
        var maxlength 	= parseInt($(that).attr("maxlength"));
		var str = "";
		for(i = 0; i < maxlength; i++){
			str = str + "9";
		}
		$(that).attr("data-max",str);
    });
	
	$("input.numberonly").keydown(function(event) {
		//return ;
		
		//$(".wrap").prepend(event.keyCode + ", <br>");
		
		// Allow: backspace, delete, tab, escape, enter and .
		if ( $.inArray(event.keyCode,[46,8,9,27,13,190]) !== -1 ||
			// Allow: Ctrl+A
			(event.keyCode == 65 && event.ctrlKey === true) || 
			
			// Allow: Ctrl+C
			(event.keyCode == 67 && event.ctrlKey === true) || 
			
			// Allow: Ctrl+V
			(event.keyCode == 86 && event.ctrlKey === true) || 
			
			 // Allow: home, end, left, right
			(event.keyCode >= 35 && event.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		else {
			// Ensure that it is a number and stop the keypress
			if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
				event.preventDefault(); 
			}   
		}
	});
	
	$("input.numberonly").keydown(function(event) {
		if (event.keyCode == 40 || event.keyCode == 38){
			var that 		= this;
			var new_value  	= 0;
			var val 		= $(that).val();
			if(val <= 0) val = 0;		
			val = parseInt(val);
			
			var min_number = $(that).attr("data-min");		
			var max_number = $(that).attr("data-max");
			
			if(min_number == "undefined"){
				max_number	= 0;			
				$(that).attr("data-min", max_number);
			}
			
			if(max_number == "undefined"){
				var maxlength 	= parseInt($(that).attr("maxlength"));			
				var max_number 	= "";			
				for(i = 0; i < max_number; i++){				
					str = str + "9";				
				}			
				$(that).attr("data-min", max_number);
			}
				
			if(min_number <= 0) min_number = 0;		
				min_number = parseInt(min_number);
			
			if(max_number <= 0) max_number = 0;		
				max_number = parseInt(max_number);
			
			if (event.keyCode == 40){
				if(val == 0){
					$(that).val('');
				}else{
					new_value = val - 1;				
					if(new_value<min_number){
						$(that).val(min_number);
					}else{
						$(that).val(new_value);
					}
				}				
				return;
			}
			
			if (event.keyCode == 38){			
				var max_number = parseInt($(that).attr("data-max"));			
				new_value = val + 1;
				if(new_value <= max_number){
					$(that).val(new_value);
				}			
				return;
			}
		}
		return;
	});
	
	
	
	$("input.numberonly").bind("keyup change", function(event) {
		var that 		= this;
		var val 		= $(that).val();
		//if(val <= 0) val = 0;		
		val = parseInt(val);
		if(!$.isNumeric(val)){
			$(that).val('')
		}
	});
	
	$( "input.numberonly").bind('mousewheel', function(e){
        
		var that 		= this;
		
		var new_value  	= 0;
		
		var val 		= $(that).val();
		
		if(val <= 0) val = 0;
		
		val = parseInt(val);
		
		var min_number = $(that).attr("data-min");		
		var max_number = $(that).attr("data-max");
		
		if(min_number == "undefined"){
			max_number	= 0;			
			$(that).attr("data-min", max_number);
		}
		
		if(max_number == "undefined"){
			var maxlength 	= parseInt($(that).attr("maxlength"));			
			var max_number 	= "";			
			for(i = 0; i < max_number; i++){				
				str = str + "9";				
			}			
			$(that).attr("data-min", max_number);
		}
			
		if(min_number <= 0) min_number = 0;		
			min_number = parseInt(min_number);
		
		if(max_number <= 0) max_number = 0;		
			max_number = parseInt(max_number);
		
		//$(that).off('mousewheel.disableScroll')
		
		
		if(e.originalEvent.wheelDelta /120 > 0) {
			//$(that).off('mousewheel.disableScroll')
            new_value = val + 1;
			if(new_value <= max_number){
				$(that).val(new_value);
			}			
			return;
        }else{
			//$(that).on('mousewheel.disableScroll')
			if(val == 0){
				$(that).val('');	
			}else{
				new_value = val - 1;
				if(new_value < min_number){
					$(that).val(min_number);
				}else{
					$(that).val(new_value);
				}
			}            
			return;
        }
    });
});
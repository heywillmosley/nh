/*function dump(arr, level) {
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
}*/

/*
function ConvertJasonToBarFormat2(JSONFormat) {
    var data = [
        []
    ];
    $.each(JSONFormat, function (k, v) {

        data[0].push([v.Month, parseFloat(v.TotalAmount)]);
    });
    return data;
}

function bar_chart(response, do_inside_id) {
    var $ = jQuery;
	try {

        var data = [
            []
        ];
		
        $("#"+do_inside_id).removeClass('legend_event');
		
		if(do_inside_id == "top_tab_graphs_chart" || do_inside_id == "top_tab_graphs2_chart"){
			$.each(response, function (k, v) {
				var l = v.Label;
				data[0].push([l, parseFloat(v.Value)]);
			});
			var angle 		= dlt_tick_angle;
			var font_size 	= dlt_tick_font_size;
		}else{
			$.each(response, function (k, v) {
				var l = v.Label;
				if(l.length > tick_char_length){
					l = l.substring(0,trim_char_length);
					l = l+tick_char_suffix;
				}
				data[0].push([l, parseFloat(v.Value)]);
			});
			var angle 		= tick_angle;
			var font_size 	= tick_font_size;
		}

        $('#' + do_inside_id).jqplot(data, {
            //title:'Default Bar Chart',
            animate: !$.jqplot.use_excanvas,
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                pointLabels: {
                    show: true
                },
                rendererOptions: {
                    varyBarColor: true
                }
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    pad: 20,
                    labelOptions: {
                        fontFamily: 'Arial, Georgia, Serif',
                        fontSize: '12pt'
                    }
					,tickRenderer: $.jqplot.CanvasAxisTickRenderer
					,tickOptions: {
					  angle: angle,
					  fontSize: font_size+'pt',
					  labelPosition: 'start'
					}
                },
                yaxis: {
                    pad: 2,
                    min: 0,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        fontFamily: 'Arial, Georgia, Serif',
                        fontSize: '12pt'
                    },					
                    tickOptions: {
                        formatString: formatString2
                    }
                }
            }
        });

    } catch (e) {
        alert(e);
    }
}

function line_chart(response, do_inside_id, formatString_y) {
    var $ = jQuery;
	try {
        var data = [
            []
        ];
        $.each(response, function (k, v) {
			var l = v.Label;
            data[0].push([l, parseFloat(v.Value)]);
        });
		
		$("#"+do_inside_id).removeClass('legend_event');
		
        var plot1 = jQuery.jqplot(do_inside_id, data, {
            //title: 'Last 30 days Sales Amount',
            animate: !$.jqplot.use_excanvas,
            seriesDefaults: {
                showMarker: true,
                pointLabels: {
                    show: true,
                    ypadding: 5
                }
            },
            axes: {
                xaxis: {
                    renderer: jQuery.jqplot.DateAxisRenderer,
                    tickOptions: {
                        formatString: '%b&nbsp;%#d'
                    }
                },
                yaxis: {
                    min: 0,
                    tickOptions: {
                       // formatString: '$%.2f'
					    formatString: formatString_y
                    }
                }
            },
            highlighter: {
                show: false
            },
            cursor: {
                show: true,
                tooltipLocation: 'sw'
            }
        });

    } catch (e) {
        alert(e.message);
    }
}

var pie_chart_var = null;
function pie_chart(response, do_inside_id) {
	var $ = jQuery;
    try {
        var data = [
            []
        ];
        
		if(do_inside_id == "top_tab_graphs_chart" || do_inside_id == "top_tab_graphs2_chart"){
			$.each(response, function (k, v) {
				var l = v.Label;
				data[0].push([l, parseFloat(v.Value)]);
			});
			var show_legend = true;
			$("#"+do_inside_id).removeClass('legend_event');
		}else{
			$.each(response, function (k, v) {
				var l = v.Label;
				if(l.length > tick_char_length){
					l = l.substring(0,trim_char_length);
					l = l+tick_char_suffix;
				}
				data[0].push([l, parseFloat(v.Value)]);
			});
			var show_legend = true;
			$("#"+do_inside_id).addClass('legend_event');
		}

        var plot1 = jQuery.jqplot(do_inside_id, data, {
            seriesDefaults: {
                // Make this a pie chart.
                renderer: jQuery.jqplot.PieRenderer,
                rendererOptions: {
                    // Put data labels on the pie slices.
                    // By default, labels show the percentage of the slice.
                    showDataLabels: true
                }
            },
            legend: {
                //show: true,
                //location: 'e'
				//location: 'nw'
				//location: 'w'
				//location: 'sw'
				//location: 'ne'
				//location: 'se'
				//location: 'n'
				//location: 's'
				//location: 'f'
				//,rendererOptions:{numberRows: 3, placement: "outside"}
				
				//renderer: $.jqplot.EnhancedLegendRenderer,
                show:show_legend,
                location: 'f', 
                //showSwatches: true,
                //placement: 'outsideGrid',
				//seriesToggle : false,
				//disableIEFading: true
				
            }
        });
		
		if(do_inside_id != "top_tab_graphs_chart"){
			if(hide_legand[do_inside_id]){
				if(hide_legand[do_inside_id] == 1){
					$("#"+do_inside_id).find('.jqplot-table-legend').hide();
				}
			}else{
				$("#"+do_inside_id).find('.jqplot-table-legend').show();
			}		
		}
		
    } catch (e) {
        alert(e.message);
    }

}

function create_chart(parent, do_inside_id, do_content, do_action, response){
	var $ = jQuery;
	parent.find('span.progress_status').html(" ").fadeOut();
	
	if(do_inside_id == "top_tab_graphs" || do_inside_id == "top_tab_graphs2"){
		var chart_height = dlt_graph_height;
	}else{
		var chart_height = graph_height;
	}

	var chart_id = $("#" + do_inside_id).find('.chart').attr('id');
	$("#" + do_inside_id).find('.chart_parent').fadeIn();
	$("#" + chart_id).fadeIn();
	$("#" + chart_id).html(' ').height(chart_height)
	$("#" + do_inside_id).find('.grid').hide();
	
	if(do_action == "sales_by_week"){
		//alert(response.length * 100)
		//$("#" + chart_id).width(response.length * 100);
		var w = 800;
		if(response.length > 5) w = 100 * response.length;
		
		//alert($("#" + chart_id).parent().width());
		
		//var window_width = $(window).width();
		var window_width = $("#" + chart_id).parent().width();
		
		//alert(window_width)
		
		if(window_width <= (w+100))
			$("#" + chart_id).width('100%');
		else
			$("#" + chart_id).width(w);
			
			//.css({"float":"right"})
		
	}else if(do_action == "top_product"){		
		$("#" + chart_id).width('100%');
	}else
		$("#" + chart_id).width('100%');
		
	if (do_content == "barchart") {
		bar_chart(response, chart_id);
	}
	if (do_content == "piechart") {
		pie_chart(response, chart_id);
	}
	if (do_content == "linechart") {
		if(do_action == "thirty_days_visit")
			line_chart(response, chart_id, '%d');
		else
			line_chart(response, chart_id, formatString1);
	}
}

var graph_data = new Array();

var num_decimals		= 0;
var currency_symbol		= "&";
var currency_pos		= "left";
var decimal_sep			= ".";
var thousand_sep		= ",";
var formatString1		= '$%.2f';
var formatString2		= "$%'d";
var currency_space		= "";



var tick_angle			= 0;
var tick_font_size		= 9;
var graph_height		= 300;
var tick_char_length	= 15;
var tick_char_suffix	= "...";
var trim_char_length	= 15;

var dlt_tick_angle		= 0;
var dlt_tick_font_size	= 9;
var dlt_graph_height	= 300;

function create_formatString(){
	currency_symbol			=	ic_ajax_object.currency_symbol;
	num_decimals			=	ic_ajax_object.num_decimals;
	currency_pos			=	ic_ajax_object.currency_pos;
	decimal_sep				=	ic_ajax_object.decimal_sep;
	thousand_sep			=	ic_ajax_object.thousand_sep;
	
	graph_height			=	ic_ajax_object.graph_height;
	tick_angle				=	ic_ajax_object.tick_angle;
	tick_font_size			=	ic_ajax_object.tick_font_size;
	tick_char_length		=	ic_ajax_object.tick_char_length;
	tick_char_suffix		=	jQuery.trim(ic_ajax_object.tick_char_suffix);	
	trim_char_length		= 	ic_ajax_object.tick_char_length;
	
	
	if(tick_char_suffix.length > 0)
	trim_char_length		= 	parseInt(tick_char_length) - tick_char_suffix.length;
	
	if(thousand_sep.length == 0){
		thousand_sep 		= "";
	}else if(thousand_sep != "'"){
		thousand_sep 		= "'";
	}
	
	if(currency_pos == "left_space" || currency_pos == "right_space"){
		currency_space			= " ";
	}
	
	if(currency_pos == "left" || currency_pos == "left_space"){
		if(num_decimals == 0)
			formatString1		=	currency_symbol+currency_space+"%"+thousand_sep+"d";
		else
			formatString1		=	currency_symbol+currency_space+"%"+thousand_sep+decimal_sep+num_decimals+"f";
		
		if(num_decimals == 0)
			formatString2		=	currency_symbol+currency_space+"%"+thousand_sep+"d";
		else
			formatString2		=	currency_symbol+currency_space+"%"+thousand_sep+decimal_sep+num_decimals+"f";
			
		//formatString2			=	currency_symbol+currency_space+"%"+thousand_sep+"d";
	}else{
		if(num_decimals == 0)
			formatString1		=	"%"+thousand_sep+"d"+currency_space+currency_symbol;
		else
			formatString1		=	"%"+thousand_sep+decimal_sep+num_decimals+"f"+currency_space+currency_symbol;
			
		
		if(num_decimals == 0)
			formatString2		=	"%"+thousand_sep+"d"+currency_space+currency_symbol;
		else
			formatString2		=	"%"+thousand_sep+decimal_sep+num_decimals+"f"+currency_space+currency_symbol;
			
		//formatString2			=	"%"+thousand_sep+"d"+currency_space+currency_symbol;
	}
	
	temp_formatString1 = formatString1;
	temp_formatString2 = formatString2;
}

var temp_formatString1 = null;
var temp_formatString2 = null;

var hide_legand = new Array();

jQuery(document).ready(function ($) {
	
	create_formatString();
	
	$('.chart').addClass('.legend_event')
	
	$('.chart').bind('jqplotClick', function(ev, seriesIndex, pointIndex, data) {
		
		var this_object = this;
		
		if(!$(this_object).hasClass('legend_event')){
			return false;
		}else{
			var chart_id = $(this_object).attr('id');
		
			if($(this_object).find('.jqplot-table-legend').is(':visible')) {
				$(this_object).find('.jqplot-table-legend').hide();
				hide_legand[chart_id] = 1;
			}
			else {
				$(this_object).find('.jqplot-table-legend').show();
				hide_legand[chart_id] = 2;
			}
		}
	});
	
	$('.box_tab_report').click(function () {
        if($(this).hasClass('active')) return false;
        var that = this;
        var title = $(that).text();
        var parent = $(that).parent().parent().parent().parent();
        var do_action = $(that).attr('data-doreport');
        var do_content = $(that).attr('data-content');
        var do_inside_id = $(that).attr('data-inside_id');

        //alert(do_content);
        $(that).parent().find('a').removeClass('active');
        $(that).addClass('active');

        if (do_content == "table") {
            $("#" + do_inside_id).find('.chart_parent').hide();
			$("#" + do_inside_id).find('.chart').hide();
            $("#" + do_inside_id).find('.grid').fadeIn();
            parent.find('span.title.chart').html(title);
            parent.find('span.progress_status').html("").fadeOut();
            return false;
        }else{
			//var graph_data_name = do_action+do_content;
			var graph_data_name = do_action;
			if(graph_data[graph_data_name]){
				/////				
				response = graph_data[graph_data_name];
				create_chart(parent, do_inside_id, do_content, do_action, response);
				parent.find('span.title.chart').html(title);
				/////
				return false;
			}
		}
		
		
        var data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "graph",
			"do_action"			: do_action,
            "do_content"		: do_content,
            "do_inside_id"		: do_inside_id,
			"admin_page"		: ic_ajax_object.admin_page,
			"page"				: ic_ajax_object.admin_page
        }
		
		//alert(JSON.stringify(data));
		parent.find('span.progress_status').html("Please wait").fadeOut();

        $.ajax({
            type: "POST",
            data: data,
            async: false,
            url: ic_ajax_object.ajaxurl,
            dataType: "json",
            success: function (response) {
				//alert(JSON.stringify(response));
                //alert(response.length)							
                parent.find('span.title.chart').html(title);
				//alert(response.error);
                
					
					
					if(do_action == "thirty_days_visit"){
						var chart_id = $("#" + do_inside_id).find('.chart').attr('id');
						
						//alert(chart_id)
						if(response.error == true){
							$("#"+chart_id).html(response.notice);
							$(".stats-overview-list").html(response.notice);
							//graph_data[graph_data_name] = response.notice;
						}else if(response.success == true){
							//alert(JSON.stringify(response));
							//alert(JSON.stringify(response.visit_data));
							//alert(response.visit_data.length);
							graph_data[graph_data_name] = response.visit_data;
							create_chart(parent, do_inside_id, do_content, do_action, response.visit_data);
							
							if(response.ga_summary)
								$(".stats-overview-list").html(response.ga_summary);
						}
					}else{
						if("top_product"){
							if (response.length > 0) {
								graph_data[graph_data_name] = response;
								create_chart(parent, do_inside_id, do_content, do_action, response);
							}else{
								var chart_id = $("#" + do_inside_id).find('.chart').attr('id');
								$("#" + do_inside_id).find('.chart_parent').fadeIn();
								$("#" + chart_id).fadeIn();
								$("#" + chart_id).html(' ').height(graph_height)
								$("#" + do_inside_id).find('.grid').hide();
								parent.find('span.progress_status').html("No product found").fadeIn();
							}
						}else{
							if (response.length > 0) {
								graph_data[graph_data_name] = response;
								create_chart(parent, do_inside_id, do_content, do_action, response);
							} else {
								parent.find('span.progress_status').html(": Orders not found").fadeIn();
							}
						}
						
					}
					
                    
                
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert("responseText" + jqXHR.responseText);
                //alert(textStatus);
                //alert(errorThrown);
            }
        });
		
		if(do_action == "thirty_days_visit"){
			//get_stats();
		}

    }).removeAttr('href');
    $('.box_tab_report.activethis').trigger('click').removeClass('activethis');
	
	$( window ).resize(function() {
		$('.box_tab_report.active').each(function(index, element) {
            $(element).removeClass('active');
			$(element).click();
        });
	});
	
	
	jQuery('#tablink2').click(function(){
		//var tab_id = $(this).find('a').attr('data-tab');
		//alert($(this).attr('data-tab'))
		//$(tab_id).find('a.box_tab_report:first').click();	
		
		
	});
});


function get_stats(){
	
	var data = {
		"action"			: ic_ajax_object.ic_ajax_action,
		"do_action_type"	: "graph",
		"do_action"			: "ga_summary",
		"admin_page"		: ic_ajax_object.admin_page,
		"page"				: ic_ajax_object.admin_page
	}
	
	$ = jQuery;
	jQuery.ajax({
		type: "POST",
		data: data,
		async: false,
		url: ic_ajax_object.ajaxurl,
		success: function (response) {
			//alert(response);				
			//alert(JSON.stringify(response));				
			jQuery(".stats-overview-list").html(response);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			//alert("responseText" + jqXHR.responseText);

			//alert(textStatus);
			//alert(errorThrown);
		}
	});
}
*/
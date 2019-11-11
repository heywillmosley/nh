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


function get_export_field_data(chart_type,response, do_inside_id){
		var column_names = {
			"Label": "Months",
			"Count": "Order Count",
			"Value": "Sales Amount"
		};
		
		var export_fields 	= new Array('Label','Count','Value');
		
		var file_name 		= "sales_summary";
		
		if(do_inside_id == "sales_order_status_chart"){
			column_names = {
				"Label": "Order Status",
				"Count": "Order Count",
				"Value": "Sales Amount"
			}
			file_name 		= "top_order_status_summary";
		}
		
		if(do_inside_id == "top_product_status_chart"){
			
			column_names = {
				"Label": "Product",
				"Count": "Order Count",
				"Value": "Sales Amount"
			}
			file_name 		= "top_product_summary";
		}
		
		if(do_inside_id == "top_category_status_chart"){
			//alert(do_inside_id)
			//alert(JSON.stringify(response));
			column_names = {
				"Label": "Category",
				"Count": "Order Count",
				"Value": "Sales Amount"
			}
			
			file_name 		= "top_category_summary";
		}
		
		if(do_inside_id == "top_billing_country_chart"){
			column_names = {
				"Label": "Billing Country",
				"item_count": "Order Count",
				"Value": "Sales Amount"
			}
			
			file_name 		= "top_billing_country_summary";
			
			export_fields 	= new Array('Label','item_count','Value');
		}
		
		if(do_inside_id == "top_billing_state_chart"){
			column_names = {
				"Label": "Billing State",
				"item_count": "Order Count",
				"Value": "Sales Amount"
			}
			
			file_name 	= "top_billing_status_summary";
			
			export_fields 	= new Array('Label','item_count','Value');
		}
		
		if(do_inside_id == "top_customer_list_chart"){
			//alert(do_inside_id)
			//alert(JSON.stringify(response));			
			column_names = {
				"Label": "Billing Name",
				"_billing_email": "Billing Email",
				"OrderCount": "Order Count",
				"Value": "Sales Amount"
				
			}
			
			file_name 	= "top_customer_summary";
			
			export_fields 	= new Array('Label','_billing_email','OrderCount','Value');
		}
		
		if(do_inside_id == "top_coupon_list_chart"){
			column_names = {
				"Label": "Coupon",
				"Count": "Order Count",
				"Value": "Sales Amount"
			}
			
			file_name 	= "top_coupon_summary";
		}
		
		if(do_inside_id == "top_payment_gateway_chart"){
			column_names = {
				"Label": "Gateway",
				"Count": "Order Count",
				"Value": "Sales Amount"
			}
			
			file_name 	= "top_payment_gateway_summary";
		}
		
		var export_settings = {
			"enabled"		: true,
			"fileName"		: file_name,
			"exportTitles"	: true,
			"columnNames"	: column_names,
			"exportFields" 	: export_fields
		};
		
		return export_settings;
}

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

        $("#"+do_inside_id).removeClass('legend_event');
		
		$.each(response, function (k, v) {
			var v = parseFloat(v.Value);
			//var count = (v.Count != "undefined") ? v.Count : 0;
			response[k]['Value'] 	= v;
			response[k]['Line'] 	= v;
		});
		
		//alert(JSON.stringify(response));
		
		if(do_inside_id == "top_tab_graphs_chart" || do_inside_id == "top_tab_graphs2_chart"){
			var angle 		= dlt_tick_angle;
			var font_size 	= dlt_tick_font_size;
		}else{
			$.each(response, function (k, v) {
				var l = v.Label;
				if(l.length > tick_char_length){
					l = l.substring(0,trim_char_length);
					l = l+tick_char_suffix;
				}
				response[k]['Label'] = l;
				
			});
			var angle 		= tick_angle;
			var font_size 	= tick_font_size;
		}
		
		var graph_title = "";
		switch(do_inside_id){
			case "graph_title":
				graph_title = "";
				break;
			default:
				graph_title = "Month";
				break;	
		}
		
		var chartData = response;
		var chart = new AmCharts.AmSerialChart();

		chart.dataProvider = chartData;
		chart.categoryField = "Label";
		chart.categoryTitle = "Label-----";
		chart.startDuration = 1;
		chart.fontSize = tick_font_size;
		
		chart.numberFormatter = {
		  precision				:num_decimals
		  ,decimalSeparator		:decimal_sep
		  ,thousandsSeparator	:","//thousand_sep
		};
		
		
		// AXES
		// category
		var categoryAxis = chart.categoryAxis;
		categoryAxis.gridPosition = "start";
		categoryAxis.labelRotation = dlt_tick_angle;
		
		
		
		// value
		var valueAxis = new AmCharts.ValueAxis();
		valueAxis.axisAlpha = 0;
		chart.addValueAxis(valueAxis);
		
		// GRAPHS
		// column graph
		var graph1 = new AmCharts.AmGraph();
		graph1.type = "column";
		graph1.title = "Month";
		graph1.lineColor = "#00B0FF";
		graph1.valueField = "Value";
		graph1.lineAlpha = 1;
		graph1.fillAlphas = 1;
		graph1.dashLengthField = 2;
		graph1.alphaField = "alpha";
		
		//graph1.balloonText = "<span style='font-size:13px;'>[[title]] in [[category]]:<b>"+currency_symbol+"[[value]]</b> [[additional]]</span>";
		graph1.balloonText = "<span style='font-size:13px;'><b>"+currency_symbol+"[[value]]</b> [[additional]]</span>";
		chart.addGraph(graph1);

		// line
		var graph2 = new AmCharts.AmGraph();
		graph2.type = "line";
		graph2.title = "Sales Amount";
		graph2.lineColor = "#76addb";
		graph2.valueField = "Line";
		graph2.lineThickness = 3;
		graph2.bullet = "round";
		graph2.bulletBorderThickness = 3;
		graph2.bulletBorderColor = "#76addb";
		graph2.bulletBorderAlpha = 1;
		graph2.bulletColor = "#ffffff";
		graph2.dashLengthField = "dashLengthLine";
		//graph2.balloonText = "<span style='font-size:13px;'>[[title]] in [[category]]:<b>"+currency_symbol+"[[value]]</b> [[additional]]</span>";
		graph1.balloonText = "<span style='font-size:13px;'><b>"+currency_symbol+"[[value]]</b> [[additional]]</span>";
		chart.addGraph(graph2);
				
		chart.export = get_export_field_data('bar_chart',response, do_inside_id);

		// WRITE
		chart.write(do_inside_id);
		
		
		
		
		//Updating the graph to show the new data
		chart.validateData();
		
		//jQuery(".amcharts-chart-div").find('a').remove('a');

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
       /* $.each(response, function (k, v) {
			var l = v.Label;
            data[0].push([l, parseFloat(v.Value)]);
        });*/
		
		//alert(JSON.stringify(response));
		
		$("#"+do_inside_id).removeClass('legend_event');
		
		var chartData = response;
		// SERIAL CHART
		var chart = new AmCharts.AmSerialChart();

		chart.dataProvider = chartData;
		chart.dataDateFormat = "YYYY-MM-DD";
		chart.categoryField = "Label";
		chart.fontSize = tick_font_size;

		// AXES
		// category
		var categoryAxis = chart.categoryAxis;
		categoryAxis.parseDates = true; // as our data is date-based, we set parseDates to true
		categoryAxis.minPeriod = "DD"; // our data is daily, so we set minPeriod to DD
		categoryAxis.gridAlpha = 0.1;
		categoryAxis.minorGridAlpha = 0.1;
		categoryAxis.axisAlpha = 0;
		categoryAxis.minorGridEnabled = true;
		categoryAxis.inside = true;
		categoryAxis.labelRotation = 45;

		// value
		var valueAxis = new AmCharts.ValueAxis();
		valueAxis.tickLength = 0;
		valueAxis.axisAlpha = 0;
		valueAxis.showFirstLabel = false;
		valueAxis.showLastLabel = false;
		chart.addValueAxis(valueAxis);

		// GRAPH
		var graph = new AmCharts.AmGraph();
		graph.dashLength = 3;
		graph.title = "Sales Amount";
		graph.lineColor = "#00CC00";
		graph.valueField = "Value";
		graph.dashLength = 3;
		graph.bullet = "round";
		graph.balloonText = "[[category]]<br><b><span style='font-size:14px;'>"+currency_symbol+"[[value]]</span></b>";
		chart.addGraph(graph);

		// CURSOR
		var chartCursor = new AmCharts.ChartCursor();
		chartCursor.valueLineEnabled = true;
		chartCursor.valueLineBalloonEnabled = true;
		chart.addChartCursor(chartCursor);
		
		chart.numberFormatter = {
		  precision				:num_decimals
		  ,decimalSeparator		:decimal_sep
		  ,thousandsSeparator	:","//thousand_sep
		};

		// SCROLLBAR
		var chartScrollbar = new AmCharts.ChartScrollbar();
		chart.addChartScrollbar(chartScrollbar);

		// HORIZONTAL GREEN RANGE
		var guide = new AmCharts.Guide();
		guide.value = 10;
		guide.toValue = 20;
		guide.fillColor = "#00CC00";
		guide.inside = false;
		guide.fillAlpha = 0.2;
		guide.lineAlpha = 0;
		valueAxis.addGuide(guide);
		
	 	chart.export = get_export_field_data('line_chart',response, do_inside_id);
		
		// WRITE
		chart.write(do_inside_id);
		
		//Updating the graph to show the new data
		chart.validateData();

    } catch (e) {
        alert(e.message);
    }
}

var pie_chart_var = null;
function pie_chart(response, do_inside_id) {
	var $ = jQuery;
    try {
			
			if(do_inside_id == "top_tab_graphs_chart" || do_inside_id == "top_tab_graphs2_chart"){
				var show_legend = true;
			}else{
				$.each(response, function (k, v) {
					var l = v.Label;
					if(l.length > tick_char_length){
						l = l.substring(0,trim_char_length);
						l = l+tick_char_suffix;
					}
					response[k]['Label'] = l;
				});
				var show_legend = true;				
			}
			
			//alert(JSON.stringify(response));
			var chartData = response;
			// AmCharts.ready(function () {
			// PIE CHART
			var chart = new AmCharts.AmPieChart();
			chart.dataProvider = chartData;
			chart.titleField = "Label";
			chart.valueField = "Value";
			chart.outlineColor = "#FFFFFF";
			chart.outlineAlpha = 0.8;
			chart.outlineThickness = 2;
			chart.fontSize = tick_font_size;
			chart.sequencedAnimation = true;
			chart.startEffect = "elastic";
			chart.innerRadius = "30%";
			chart.startDuration = 2;
			chart.labelRadius = 15;			
			chart.balloonText = "[[title]]<br><span style='font-size:14px'><b>"+currency_symbol+"[[value]]</b> ([[percents]]%)</span>";
			
			// the following two lines makes the chart 3D
			chart.depth3D = 10;
			chart.angle = 15;
			
			chart.numberFormatter = {
			  precision				:num_decimals
			  ,decimalSeparator		:decimal_sep
			  ,thousandsSeparator	:","//thousand_sep
			};
			
			chart.export = get_export_field_data('pie_chart',response, do_inside_id);

			// WRITE
			chart.write(do_inside_id);
			
			//Updating the graph to show the new data
			chart.validateData();
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
	
	if(thousand_sep == ""){
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
		
		var start_date = $("#start_date").val();
		var end_date = $("#end_date").val();
		
        var data = {
            "action"			: ic_ajax_object.ic_ajax_action,
            "do_action_type"	: "graph",
			"do_action"			: do_action,
            "do_content"		: do_content,
            "do_inside_id"		: do_inside_id,
			"start_date"		: start_date,
			"end_date"			: end_date,
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
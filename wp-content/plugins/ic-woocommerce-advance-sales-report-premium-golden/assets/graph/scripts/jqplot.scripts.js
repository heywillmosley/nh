function ConvertJasonToBarFormat2(JSONFormat) {
    var data = [
        []
    ];
    $.each(JSONFormat, function (k, v) {

        data[0].push([v.Month, parseInt(v.TotalAmount)]);
    });
    return data;
}

function bar_chart(response, do_inside_id) {
    var $ = jQuery;
	try {

        var data = [
            []
        ];
        $.each(response, function (k, v) {
            data[0].push([v.Label, parseInt(v.Value)]);
        });

        //alert(JSON.stringify(data));

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
                        fontFamily: 'Georgia, Serif',
                        fontSize: '12pt'
                    }
                },
                yaxis: {
                    pad: 2,
                    min: 0,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        fontFamily: 'Georgia, Serif',
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

            data[0].push([v.Label, parseInt(v.Value)]);
        });

        //alert(JSON.stringify(data))
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

function pie_chart(response, do_inside_id) {
	var $ = jQuery;
    try {
        var data = [
            []
        ];
        //alert(JSON.stringify(response))
        jQuery.each(response, function (k, v) {
            data[0].push([v.Label, parseInt(v.Value)]);
        });

        //alert(JSON.stringify(data))

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
                show: true,
                location: 'e'
            }
        });
    } catch (e) {
        alert(e.message);
    }

}

function create_chart(parent, do_inside_id, do_content, do_action, response){
	var $ = jQuery;
	parent.find('span.progress_status').html(" ");

	var chart_id = $("#" + do_inside_id).find('.chart').attr('id');
	 $("#" + do_inside_id).find('.chart_parent').fadeIn();
	$("#" + chart_id).fadeIn();
	
	
	
	
	
	$("#" + chart_id).html(' ').height(300)
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

function create_formatString(){
	currency_symbol			=	ic_ajax_object.currency_symbol;
	num_decimals			=	ic_ajax_object.num_decimals;
	currency_pos			=	ic_ajax_object.currency_pos;
	decimal_sep				=	ic_ajax_object.decimal_sep;
	thousand_sep			=	ic_ajax_object.thousand_sep;
	
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
			
		formatString2			=	currency_symbol+currency_space+"%"+thousand_sep+"d";
	}else{
		if(num_decimals == 0)
			formatString1		=	"%"+thousand_sep+"d"+currency_space+currency_symbol;
		else
			formatString1		=	"%"+thousand_sep+decimal_sep+num_decimals+"f"+currency_space+currency_symbol;
			
		formatString2			=	"%"+thousand_sep+"d"+currency_space+currency_symbol;
	}
}

jQuery(document).ready(function ($) {
	
	create_formatString();
		
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
            parent.find('span.progress_status').html("").fadeIn();
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
            "do_inside_id"		: do_inside_id
        }
		
		//alert(JSON.stringify(data));
		parent.find('span.progress_status').html(": Please wait").fadeIn();

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
						if (response.length > 0) {
							graph_data[graph_data_name] = response;
							create_chart(parent, do_inside_id, do_content, do_action, response);
						} else {
							parent.find('span.progress_status').html(": Orders not found").fadeIn();
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
// JavaScript Document

$(function() {
  // Handler for .ready() called.
  BarChart1();
});

function BarChart1()
{
	//alert("Anzar Ahmed");	
	try
	{
		var line1 = [['Nissan', 4],['Porche', 6],['Acura', 2],['Aston Martin', 5],['Rolls Royce', 6]];
						//var line1 =[{'dsa':1},{'sa',2},{'hegdfllo':3},{'hellohgfs':4},{'hellohfg':6}];
		$('#chart1').jqplot([line1], {
			title:'Default Bar Chart',
			animate: !$.jqplot.use_excanvas,
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				pointLabels: { show: true },
				rendererOptions: {
				// Set the varyBarColor option to true to use different colors for each bar.
				// The default series colors are used.
					varyBarColor: true
				}
			},
			axes:{
				xaxis:{
					renderer: $.jqplot.CategoryAxisRenderer,
					 label: "X Axis",
					  pad: 0
				},
				yaxis: {
				  label: "Y Axis"
				}
			}
		});	
		//alert("Anzar Ahmed 2 ");	
	}
	catch(e)
	{alert(e.Message);}
}

function BarChart(data2)
{
	//alert(JSON.stringify(data2));
	
	var data = [[]];
	$.each(data2, function(k, v) {
    		//alert(v.Total);
			//alert(v.Billing_Email);
			
			data[0].push([ v.Billing_Email,parseInt(v.Total)]);
	});
	
	//alert("Anzar Ahmed");	
		//alert(JSON.stringify(data));
	try
	{
		//[[["47@gmail.com","45994"],["55@gmail.com","29692"],["51@gmail.com","21086"],["41@gmail.com","20722"],["32@gmail.com","19448"]]]
		var line1 = data;
		//var line1 =[{'dsa':1},{'sa',2},{'hegdfllo':3},{'hellohgfs':4},{'hellohfg':6}];
		//var line1	= [["47@gmail.com","95296217"],["55@gmail.com","55256217"],["51@gmail.com","95296217"],["41@gmail.com","45296217"],["32@gmail.com","55296217"]];
	
	//	[['Nissan', 4],['Porche', 6],['Acura', 2],['Aston Martin', 5],['Rolls Royce', 6]];
		$('#chart2').jqplot(line1, {
			title:'Default Bar Chart',
			animate: !$.jqplot.use_excanvas,
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				pointLabels: { show: true },
				rendererOptions: {
				// Set the varyBarColor option to true to use different colors for each bar.
				// The default series colors are used.
					varyBarColor: true
				}
			},
			axes:{
				xaxis:{
					renderer: $.jqplot.CategoryAxisRenderer,
					 //label: "X Axis",
					  pad: 20,
					  labelOptions: {
						fontFamily: 'Georgia, Serif',
						fontSize: '12pt'
					  }
				},
				yaxis: {
				 // label: "Y Axis",
				  pad: 20,
				  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				   labelOptions: {
					fontFamily: 'Georgia, Serif',
					fontSize: '12pt'
				  }
				}
			}
		});	
		//alert("Anzar Ahmed 2 ");	
	}
	catch(e)
	{alert(e.Message);}
}

function ConvertJasonToBarFormat(JSONFormat)
{
	var data = [[]];
		$.each(JSONFormat, function(k, v) {
		
			data[0].push([ v.Month,parseInt(v.TotalAmount)]);
		});
	return  data;
}
function bar_chart_sales_by_days(JSONFormat)
{
	try{
		var data = [[]];
		$.each(JSONFormat, function(k, v) {
		
			data[0].push([ v.Date,parseInt(v.TotalAmount)]);
		});
		//var	data = ConvertJasonToBarFormat(response);
		
		$('#plot_graph').jqplot(data, {
			title: 'Daily sales graph',
			animate: !$.jqplot.use_excanvas,
		    seriesDefaults: { 
				showMarker:false,
				pointLabels: { show:true } 
			},
			axes:{
				xaxis:{
					renderer: $.jqplot.CategoryAxisRenderer,
					 //label: "X Axis",
					 
					  pad: 20,
					  
					  labelOptions: {
						fontFamily: 'Georgia, Serif',
						fontSize: '12pt'
					  }
				},
				yaxis: {
				 // label: "Y Axis",
				  pad: 20,
				  min:0,
				  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				   labelOptions: {
					fontFamily: 'Georgia, Serif',
					fontSize: '12pt'
				  },
				  tickOptions: {
                       //   formatString: "$%'d"
                 	}
				}
			}
		});	
	
	}catch(e){
		alert(e);
	}
}

function bar_chart_sales_by_week(JSONFormat)
{
	try{
		var data = [[]];
		$.each(JSONFormat, function(k, v) {
		
			data[0].push([ v.Date,parseInt(v.TotalAmount)]);
		});
		//var	data = ConvertJasonToBarFormat(response);
		
		$('#plot_graph').jqplot(data, {
			title: 'Concern vs. Occurrance',
			animate: !$.jqplot.use_excanvas,
		    seriesDefaults: { 
				showMarker:false,
				pointLabels: { show:true } 
			},
			axes:{
				xaxis:{
					renderer: $.jqplot.CategoryAxisRenderer,
					 //label: "X Axis",
					 
					  pad: 20,
					  
					  labelOptions: {
						fontFamily: 'Georgia, Serif',
						fontSize: '12pt'
					  }
				},
				yaxis: {
				 // label: "Y Axis",
				  pad: 20,
				  min:0,
				  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				   labelOptions: {
					fontFamily: 'Georgia, Serif',
					fontSize: '12pt'
				  },
				  tickOptions: {
                       //   formatString: "$%'d"
                 	}
				}
			}
		});	
	
	}catch(e){
		alert(e);
	}
}

function bar_chart_sales_by_months(response){
	try{
		
		var	data = ConvertJasonToBarFormat(response);
		
		$('#plot_graph').jqplot(data, {
			title:'Default Bar Chart',
			animate: !$.jqplot.use_excanvas,
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				pointLabels: { show: true },
				rendererOptions: {
				// Set the varyBarColor option to true to use different colors for each bar.
				// The default series colors are used.
					varyBarColor: true
				}
			},
			axes:{
				xaxis:{
					renderer: $.jqplot.CategoryAxisRenderer,
					 //label: "X Axis",
					  pad: 20,
					  
					  labelOptions: {
						fontFamily: 'Georgia, Serif',
						fontSize: '12pt'
					  }
				},
				yaxis: {
				 // label: "Y Axis",
				  pad: 20,
				  min:0,
				  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				   labelOptions: {
					fontFamily: 'Georgia, Serif',
					fontSize: '12pt'
				  },
				  tickOptions: {
                          formatString: "$%'d"
                 	}
				}
			}
		});	
	
	}catch(e){
		alert(e);
	}
}
function pi_test()
{
  		var data = [
			['Heavy Industry', 12],['Retail', 9], ['Light Industry', 14], 
			['Out of home', 16],['Commuting', 7], ['Orientation', 9]
		  ];
		  var plot1 = jQuery.jqplot ('plot_graph', [data], 
			{ 
			  seriesDefaults: {
				// Make this a pie chart.
				renderer: jQuery.jqplot.PieRenderer, 
				rendererOptions: {
				  // Put data labels on the pie slices.
				  // By default, labels show the percentage of the slice.
				  showDataLabels: true
				}
			  }, 
			  legend: { show:true, location: 'e' }
			}
		  );
		
}
function pi_chart_top_product(JSONFormat){
	
	try
	{
		alert(JSON.stringify(JSONFormat));
			
	/*  var data = [
		['Heavy Industry', 12],['Retail', 9], ['Light Industry', 14], 
		['Out of home', 16],['Commuting', 7], ['Orientation', 9]
	  ];*/
	  
	  	var data = [[]];
		$.each(JSONFormat, function(k, v) {
		
			data[0].push([ v.ItemName,parseInt(v.Total)]);
		});
	  	
	  alert(JSON.stringify(data));
	  
		  var plot1 = jQuery.jqplot ('plot_graph', data, 
			{ 
			  seriesDefaults: {
				// Make this a pie chart.
				renderer: jQuery.jqplot.PieRenderer, 
				rendererOptions: {
				  // Put data labels on the pie slices.
				  // By default, labels show the percentage of the slice.
				  showDataLabels: true
				}
			  }, 
			  legend: { show:true, location: 'e' }
			}
		  );
	}
	catch(e)
	{	alert(e.message);	}
	
}

jQuery(document).ready(function($){
	var data = {"action":"woo_cr_action_comman","xyz":"1"}
	$.ajax({
		type: "POST",	   
     	data: data,
	  	async: false,
      	url: ajax_object.ajaxurl,
      	dataType:"json",
      	success: function(data) {
       // ret = data;
		BarChart(data);
      }
    });
	
	$('.tab_report').click(function(){
		var do_action = $(this).attr('data-doreport');
		//var type_report = $(this).attr('data-type_report');
		//alert(do_action);
		var data = {"action":"woo_cr_action_comman","do_action":do_action}
		
		$.ajax({
			type: "POST",	   
			data: data,
			async: false,
			url: ajax_object.ajaxurl,
			dataType:"json",
			success: function(response) {
		   // ret = data;
		   		if (do_action=="sales_by_months"){bar_chart_sales_by_months(response);}
				if (do_action=="sales_by_days"){bar_chart_sales_by_days(response);}
				if (do_action=="sales_by_week"){bar_chart_sales_by_week(response);}
				if (do_action=="top_product"){
					//pi_chart_top_product(response);
					pi_test();
					}
				
		  },
		  error: function(jqXHR, textStatus, errorThrown) {
  			alert(jqXHR.responseText);
			alert(textStatus);
			alert(errorThrown);
		 }
		});
	});
	
});
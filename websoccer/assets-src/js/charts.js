$(function() {
	
	/**
	 * Grades line chart
	 */
	$('#grades').each(function(index, component) {
		$.plot("#grades", [$(component).data("series")], {
			   xaxis: {
				   tickSize: 1, 
				   tickDecimals: 0
			   },
			yaxis: {
				   min: 0,
				   max: 6
			   },
			   lines: { show: true },
			   points: { show: true },
			   grid: {
					hoverable: true
				},
		});
	});
	
	var previousPoint = null;
	$("#grades").bind("plothover", function (event, pos, item) {

		if (item) {
			if (previousPoint != item.dataIndex) {

				previousPoint = item.dataIndex;

				$("#graphtooltip").remove();
				y = item.datapoint[1].toFixed(2);

				showGrpahTooltip(item.pageX, item.pageY, y);
			}
		} else {
			$("#graphtooltip").remove();
			previousPoint = null;
		}
	});
	
	var showGrpahTooltip = function(x, y, contents) {
		$("<div id='graphtooltip'>" + contents + "</div>").css({
			position: "absolute",
			display: "none",
			top: y - 30,
			left: x + 5,
			border: "1px solid #fdd",
			padding: "2px",
			"background-color": "#fee",
			opacity: 0.80
		}).appendTo("body").fadeIn(200);
	};
	
	/**
	 * Initialize Pie Charts
	 */
	$(document).ajaxComplete(function() {
		$('.pieChart').each(function(index, component) {
			initPieChart(component);
		});
	});
	
	var initPieChart = function(component) {
		$.plot($(component), $(component).data('series'), {
		    series: {
		        pie: {
		            show: true,
		            radius: 1,
		            label: {
		                show: true,
		                radius: 0.8,
		                formatter: function(label, series){
	                        return '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">'+ Math.round(series.percent)+'%</div>';
	                    },
		                background: {
		                    opacity: 0.5
		                }
		            }
		        }
		    },
		    legend: {
		        show: true,
		        position: 'se',
		        container: $(component).parent().find(".pieChartLabel")
		    }
		});
	};
	
	/**
	 * League history
	 */
	$('#leaguehistorychart').each(function(index, component) {
		$.plot("#leaguehistorychart", [$(component).data("series")], {
			   xaxis: {
				   tickSize: 1, 
				   tickDecimals: 0
			   },
			yaxis: {
				   min: 1,
				   tickDecimals: 0,
				   max: $(component).data("maxpos"),
				   transform: function(v) {
				        return -v;
				    },
				    inverseTransform: function(v) {
				        return -v;
				    }
			   },
			   lines: { show: true },
			   points: { show: true },
			   grid: {
					hoverable: true
				},
		});
	});
	$("#leaguehistorychart").bind("plothover", function (event, pos, item) {

		if (item) {
			if (previousPoint != item.dataIndex) {

				previousPoint = item.dataIndex;

				$("#graphtooltip").remove();
				y = item.datapoint[1];

				showGrpahTooltip(item.pageX, item.pageY, y);
			}
		} else {
			$("#graphtooltip").remove();
			previousPoint = null;
		}
	});
	

});

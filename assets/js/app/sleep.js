$(function () {
	Number.prototype.round = function(places) {
		return +(Math.round(this + "e+" + places)  + "e-" + places);
	};

	var graphHandlers = {};

	graphHandlers['sleep/overview.php'] = function(data) {
		$('.sleep-overview').highcharts({
			chart: {
				type: 'scatter',
				zoomType: 'xy'
			},

			title: {
				text: 'Sleep Overview',
			},

			tooltip: {
				enabled: false,
			},

			xAxis: {
				title: {
					text: 'Date'
				},
				type: 'datetime',
			},

			yAxis: [{
				title: {
					text: 'Clock time',
				},
				type: 'datetime',
				min: 0,
				max: 24*60*60*1000,
				dateTimeLabelFormats : {
					day: '%H:%M'
				},
				tickInterval: 60*60*1000,
			}],

			series: [
				{
					name: 'Off/Sleep',
					data: _.map(data, function(item) {
						return [item.LocalDate, item.StartLocalTime];
					}),
					turboThreshold: 0,
				},
				{
					name: 'On/Wake up',
					data: _.map(data, function(item) {
						return [item.LocalDate, item.EndLocalTime];
					}),
					turboThreshold: 0,
				}
			]
		});
	};

	var refreshGraphs = function() {
		$('[data-url]').each(function() {
			var $this = $(this),
				url = $this.data('url');

			$.ajax({
				url: url,
				data: {
					from: $('#from').val(),
					to: $('#to').val(),
				}
			}).done(function(data) {
				if (typeof graphHandlers[url] !== 'undefined') {
					graphHandlers[url](data);
				}
			});
		});
	};

	refreshGraphs();

	$('#from, #to').change(function() {
		refreshGraphs();
	});
});

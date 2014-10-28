/**
 *  Axis for WordPress — Frontend Code
 *  This renders the data-uri-based images from the backend as interactive charts.
 */
/*global c3*/
(function($){
	function makeId(){
		var text = '';
		var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		for( var i=0; i < 5; i++ ) {
			text += possible.charAt(Math.floor(Math.random() * possible.length));
		}

		return text;
	}

	$('.axisChart').each(function(){
		var config = $.parseJSON(window.atob($(this).data('axisjs')));
		config.axis.x.tick.format = function (b){return'series'===config.chartGlobalType&&'category'!==config.axis.x.type?config.axis.x.prefix+b.toFixed(config.axis.x.accuracy).toString()+config.axis.x.suffix:b;};
		config.axis.y.tick.format = function (b){return'series'===config.chartGlobalType&&'category'!==config.axis.y.type?config.axis.y.prefix+b.toFixed(config.axis.y.accuracy).toString()+config.axis.y.suffix:b;};
		config.axis.y2.tick.format = function (b){return'series'===config.chartGlobalType&&'category'!==config.axis.y2.type?config.axis.y2.prefix+b.toFixed(config.axis.y2.accuracy).toString()+config.axis.y2.suffix:b;};
		config.donut.label.format = function (b,c){return(100*c).toFixed(config.chartAccuracy)+'%';};
		config.pie.label.format = function (b,c){return(100*c).toFixed(config.chartAccuracy)+'%';};
		config.gauge.label.format = function (b,c){return(100*c).toFixed(config.chartAccuracy)+'%';};
		var chartId = 'axisJS' + makeId();
		$(this).replaceWith($('<div>').attr('id', chartId));
		config.bindto = '#' + chartId;

		window.chart = c3.generate(config);
	});

})(jQuery);

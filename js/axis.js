/*global c3*/
/**
 *  Axis for WordPress — Frontend Code
 *  This renders the data-uri-based images from the backend as interactive charts.
 */

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
		var chartId = 'axisJS' + makeId();
		$(this).replaceWith($('<div>').attr('id', chartId));
		config.bindto = '#' + chartId;
		c3.generate(config);
	});

})(jQuery);

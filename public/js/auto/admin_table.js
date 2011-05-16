jQuery(function($) {
	$(".iconAddItem").fancybox({
		'scrolling'		: 'no',
		'titleShow'		: false,
		'onClosed'		: function() {
				$("#login_error").hide();
		}
	});
});

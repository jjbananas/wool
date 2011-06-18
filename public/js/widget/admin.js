jQuery(function($) {
	var widgetApi = window.parent.WOOL.widgetApi;
	
	$(".closePanel").click(function(e) {
		e.preventDefault();
		widgetApi.closePanel();
	});

	var form = $("form.widgetConfig");
	form.submit(function(e) {
		e.preventDefault();
		widgetApi.update(form.serializeArray());
		widgetApi.closePanel();
	});
});

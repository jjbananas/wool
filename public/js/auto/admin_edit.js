jQuery(function() {
	var form = $("form.tableEdit");
	form.validate({
		wrapper: 'div class="msgBox msgError msgFieldError"',
		rules: validators
	});
});

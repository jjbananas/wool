jQuery(function($) {	var myNicEditor = new nicEditor({		iconsPath: '/shaded/components/nicEdit/mooIconsLarge.png'	});	myNicEditor.setPanel($("#editHeader .editContainer").get(0));		var editables = $(".editable");	editables.each(function() {		myNicEditor.addInstance(this);	});		window.saveContent = function() {		var content = {};				editables.each(function() {			content[$(this).parent().attr("id").substr(7)] = {				type: "content",				content: $(this).html()			};		});				jQuery.ajax({			url: "/shaded/admin/layout/setContent",			type: "post",			data: {				page: 1,				widgets: content			},			success: function() {				console.log("done");			}		});	};});
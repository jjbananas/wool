jQuery(function() {
	$(".preview a.msg").live("click", function(e) {
		e.preventDefault();
		
		var link = $(e.target);
		var message = link.closest("li");
		var root = link.closest("li.root");
		
		var full = message.children(".full");
		
		// Switch if the full text is available, otherwise get the thread by ajax.
		if (full.length == 0) {
			jQuery.ajax({
				url: this.href,
				success: function(data) {
					root.replaceWith(data);
				}
			});
		} else {
			$(".active", root).removeClass("active");
			message.addClass("active");
		}
		
		return false;
	});
	
	// Reply box
	var replyBox = $("#replyBox");
	
	replyBox.submit(function(e) {
		e.preventDefault();
		
		var link = $(e.target);
		var root = link.closest("li.root");
		
		var form = $("form", replyBox);
		
		jQuery.ajax({
			url: form.attr("action"),
			data: form.serialize(),
			type: "post",
			success: function(data) {
				root.replaceWith(data);
			}
		});
		
		return false;
	});
	
	$(".iconReply").live("click", function(e) {
		e.preventDefault();
		
		var link = $(this);
		replyBox = replyBox.detach();
		link.parent().parent().append(replyBox);
		$("form", replyBox).attr("action", this.href);
		replyBox.show();
		
		return false;
	});
	
	// Keyboard controls
	var keyMap = {
		'J': function() {
			console.log('move down');
		},
		
		'K': function() {
			console.log('move down');
		}
	};
	
	$(document).keyup(function(e) {
		var ch = String.fromCharCode(e.which);
		if (keyMap[ch]) {
			keyMap[ch]();
		}
	});
});

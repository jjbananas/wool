/*

*/

(function($) {

	var specialActions = {
		ol: function(args) {
			document.execCommand("insertorderedlist",false,args);
		},
		ul: function(args) {
			document.execCommand("insertunorderedlist",false,args);
		},
		image: function(args) {
			var urlPrompt = prompt("Enter Image URL:", "http://");
			document.execCommand("insertimage",false,urlPrompt);
		}
	};
	
	function command(cmd, args) {
		if (specialActions[cmd]) {
			specialActions[cmd](args);
		} else {
			document.execCommand(cmd,false,args);
		}
	}

	$.fn.simpledit = function(options) {
		var opts = $.extend({}, $.fn.simpledit.defaults, options);
		
		this.each(function() {
			var area = $(this);
			var buttonPanel = $(opts.buttonPanel).eq(0);

			buttonPanel.parent().parent().css("height", buttonPanel.outerHeight(true));
			
			// Create button panel.
			if (!buttonPanel.length) {
				buttonPanel = $("<div>");
				area.before(buttonPanel);
			}
			
			buttonPanel.addClass("simpledit-buttonPanel");
			buttonPanel.attr("unselectable", "on");
			
			// Create buttons.
			if (opts.buttons) {
				jQuery.each(opts.buttons, function(index, value) {
					var btn = $("<button>").attr({
						"href": "#",
						"data-action": value,
						"unselectable": "on"
					})
					btn.addClass("simpledit-button").addClass(value).html(value);
					buttonPanel.append(btn);
				});
			}
			
			var buttons = $(".simpledit-button", buttonPanel);
			
			buttons.click(function(e) {
				e.preventDefault();
				
				var btn = $(this);
				var action = btn.attr("data-action");
				
				btn.toggleClass("active");
				
				//area.focus();
				command(action, null);
				return false;
			});
			
			buttons.bind("selectstart", function() {
				return false;
			});
			
			area.bind("keyup mouseup", function() {
				buttons.each(function() {
					var btn = $(this);
					var action = btn.attr("data-action");
					
					if (document.queryCommandState) {
						btn.toggleClass("active", document.queryCommandState(action));
					}
				});
			});
			
			area.attr("contenteditable", "true");
		});
	   
		return this;
	};
	
	// Allow access to defaults.
	$.fn.simpledit.defaults = {
		buttonPanel: null,
		buttons: null
	};

})(jQuery);

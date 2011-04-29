/*
	jQuery Pull-down Menu Plugin.
   
	A simple reusable pull-down (or flyout) for use in a menu structure.
   
	Usage:
		Call the pulldown function on the UL element that you want to use as
		the menu root.
	   
		$("#myPulldownMenu").pulldown();
	   
		The menu structure is assumed to be a nested set of elements with one
		"hider" as the descendent of each "handle". A simple nested list is the
		easiest example and will work with no further configuration.
	   
		<ul id="myPulldownMenu">
			<li>
				<a>Child 1</a>
			   
				<ul>
					<li><a>Sub Category</a></li>
				</ul>
			</li>
		</ul>
		
		In this example the ULs (except the outermost) would be the hiders and
		the LIs are the handles.
		
	Options:
		closeDelay
			Time taken for menu to close after the mouse leaves (ms).
			
		clickAway
			Enables the ability to click outside of the menu to close it
			instantly.
			
		handleSelector
			The handles which when hovered over will open up an "hider".
			
		hiderSelector
			The elements that get hidden and shown as the mouse moves over the
			"handles".
		
		activeClass
			CSS class to give to active elements. Normally just "active".
			
		siblingDist
			Similar to using z-index in CSS, in order to determine the depth
			of each handle there must be some point in the DOM where there are
			sibling elements with just one handle in each.
			
			Usually this is the handles themselves. If not, siblingDist is the
			height to assend the tree to find such elements.
*/

(function($) {

	var allowedActions = {
		bold: "bold"
	};
	
	function command(cmd, args) {
		document.execCommand(cmd,false,args);
	}

	$.fn.simpledit = function(options) {
		var opts = $.extend({}, $.fn.simpledit.defaults, options);
		
		this.each(function() {
			var area = $(this);
			var buttonPanel = $(opts.buttonPanel).eq(0);
			
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
					btn.addClass("simpledit-button").html(value);
					buttonPanel.append(btn);
				});
			}
			
			var buttons = $(".simpledit-button", buttonPanel);
			
			buttons.click(function(e) {
				e.preventDefault();
				
				var btn = $(this);
				var action = btn.attr("data-action");
				
				btn.toggleClass("active");
				
				area.focus();
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
					
					btn.toggleClass("active", document.queryCommandState(action));
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

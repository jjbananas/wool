/*
	Slider Position Match Plugin.
	
	Force an element to track another elements position and size.
	
	Usage:
		Call the positionMatch function on the element that you want to control.
		You will need to supply, at a minimum, the matchedElement to follow:
		
		$(".positionMatch").positionMatch({matchedElement: ".followMe"});
		
		The main reason for creating this plugin is to allow a form to appear as if
		it was positioned inside where another form exists.
	
	Options:
		matchedElement
			Element or selector of the element to track.
	
	Events:
		matchPositions
			Trigger this event on an element to force an updated of the tracked
			position. Useful if you have moved or resized matchedElement.
*/

(function($) {

	$.fn.positionMatch = function(options) {
		var opts = $.extend({}, $.fn.positionMatch.defaults, options);
		
		// Get the number of elements we are replacing.
		var numReplaced = this.length;
		
		this.each(function() {
			var el = $(this);
			var matched = $(opts.matchedElement);
			
			if (!matched.length) {
				return;
			}
			
			el.css({
				position: "absolute"
			});
			
			function reposition() {
				el.width(matched.width());
				matched.height(el.height());
				var pos = matched.position();
				el.css({
					top: pos.top,
					left: pos.left
				});
			}
			
			$(window).resize(reposition);
			el.bind("matchPositions", reposition);
			
			if (opts.startPositioned) {
				reposition();
			}
		});
		
		return this;
	};
	
	// Allow access to defaults.
	$.fn.positionMatch.defaults = {
		matchedElement: null,
		startPositioned: true
	};

})(jQuery);

var WOOL = WOOL || {};

jQuery(function($) {
	var list = $("#ajaxThrobber .content");
	if (!list.length) {
		return;
	}
	
	// Message content and timestamp information.
	var nextId = 0;
	var msgs = {};
	
	function createTimer(uid, el, ms) {
		if (!ms) {
			return null;
		}
		
		return setTimeout(function() {
			WOOL.msgBox.remove(uid);
		}, ms);
	}
	
	WOOL.msgBox = {
		add: function(msg, ms) {
			var id = nextId++;
			
			var el = $("<li>");
			el.html(msg);
			el.appendTo(list);
			
			var timer = createTimer(id, el, ms);
			
			msgs[id] = {
				el: el,
				id: id,
				timer: timer
			};
			
			list.show();
			
			return id;
		},
		
		update: function(uid, msg, ms) {
			if (!msgs[uid]) {
				return false;
			}
			
			msgs[uid].el.html(msg);
			clearTimeout(msgs[uid].timer);
			msgs[uid].timer = createTimer(uid, msgs[uid].el, ms);
			
			return true;
		},
		
		remove: function(uid) {
			if (!msgs[uid]) {
				return;
			}
			msgs[uid].el.remove();
			clearTimeout(msgs[uid].timer);
			msgs[uid] = null;
			
			if (!msgs.length) {
				list.hide();
			}
		}
	};
});

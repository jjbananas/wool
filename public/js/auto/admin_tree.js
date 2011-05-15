function fillForm(form, data) {
	var inputs = $(":input", form);
	
	_.each(data, function(props, obj) {
		_.each(props, function(value, prop) {
			inputs.filter('[name="' + obj + '[' + prop + ']"]').val(value);
		});
	});
}

jQuery(function($) {
	var tree = $("#jstree")
	var form = $("form");
	var inputs = $(":input", form);
	var changed = null;
	
	var itemCache = {};
	
	tree.jstree({
		"plugins" : ["themes", "html_data", "dnd", "ui", "crrm"]
	}).bind("select_node.jstree", function(e, treeEvent) {
		if (!treeEvent.args[0]) {
			return false;
		}
		
		var link = $(treeEvent.args[0]);
		var node = link.attr("data-node");
		
		if (itemCache[node]) {
			fillForm(form, itemCache[node]);
			return;
		}
		
		var alert = WOOL.msgBox.add("Fetching data...");

		jQuery.ajax({
			url: window.location,
			type: "post",
			data: {id: node},
			dataType: "json",
			success: function(res) {
				if (res.success == true) {
					WOOL.msgBox.remove(alert);
					form.parent().show();
					fillForm(form, res);
					itemCache[node] = res;
				} else {
					WOOL.msgBox.update(alert, res.msg, 5000);
				}
			},
			error: function(res, status) {
				WOOL.msgBox.update(alert, status, 5000);
			}
		});
	}).bind("before.jstree", function(e, treeEvent) {
		if (treeEvent.func == "select_node" && checkChanges()) {
			return false;
		}
	});
	
	
	inputs.change(function(e) {
		changed = changed || true;
	});
	
	form.submit(function(e) {
		e.preventDefault();
		changed = null;
	});
	
	function checkChanges() {
		if (!changed) {
			return false;
		}
		
		if (confirm("You have unsaved changes.\n\nDiscard changes?")) {
			changed = null;
			if (changed !== false) {
				tree.jstree("remove");
			}
			return false;
		}
		
		return true;
	}
	
	$(".actionRoot").click(function(e) {
		e.preventDefault();
		
		if (changed) {
			return;
		}
		
		var params = {data: {title: "*new item*", attr: {"data-node": 0}}};
		var inserted = tree.jstree("create", -1, "last", params, null, true);
		tree.jstree("deselect_all");
		tree.jstree("select_node", inserted);
		changed = inserted;
	});
	
	$(".actionInsert").click(function(e) {
		e.preventDefault();
		
		if (changed) {
			return;
		}
		
		var params = {data: {title: "*new item*", attr: {"data-node": 0}}};
		var inserted = tree.jstree("create", null, "last", params, null, true);
		tree.jstree("deselect_all");
		tree.jstree("select_node", inserted);
		changed = inserted;
	});
	
	$(".actionRemove").click(function(e) {
		e.preventDefault();
		
		if (confirm("Delete branch (including contents)?")) {
			tree.jstree("remove");
		}
	});
});

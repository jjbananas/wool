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
	var changes = false;
	
	var itemCache = {};
	
	tree.jstree({
		"plugins" : ["themes", "html_data", "dnd", "ui", "crrm"]
	}).bind("select_node.jstree", function(e, treeEvent) {
		if (checkChanges() || !treeEvent.args[0]) {
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
	}).bind("before.jstree", function() {
		console.log(arguments);
	});
	
	
	inputs.change(function(e) {
		changes = true;
	});
	
	form.submit(function(e) {
		e.preventDefault();
		changes = false;
	});
	
	function checkChanges() {
		if (!changes) {
			return false;
		}
		
		if (confirm("You have unsaved changes.\n\nDiscard changes?")) {
			return false;
		}
		
		return true;
	}
	
	$(".actionRoot").click(function(e) {
		e.preventDefault();
		
		tree.jstree("create", -1, "last", {data: {title: "*new item*", attr: {"data-node": 0}}}, null, true);
	});
	
	$(".actionInsert").click(function(e) {
		e.preventDefault();
		
		var inserted = tree.jstree("create", null, "last", {data: {title: "*new item*", attr: {"data-node": 0}}}, null, true);
		tree.jstree("deselect_all");
		tree.jstree("select_node", inserted);
	});
	
	$(".actionRemove").click(function(e) {
		e.preventDefault();
		
		if (confirm("Delete branch (including contents)?")) {
			tree.jstree("remove");
		}
	});
});

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
	var form = $("form.tableEdit");
	var inputs = $(":input", form);
	var changed = null;
	
	var itemCache = {};

	var parentId = $("<input>").attr({
		type: "hidden",
		name: "parentId"
	});

	form.append(parentId);
	
	tree.jstree({
		plugins : ["themes", "html_data", "dnd", "ui", "crrm"],
		crrm: {
			move: {
				check_move: function(data) {
					if (changed) {
						return false;
					}

					return true;
				}
			}
		}
	}).bind("select_node.jstree", function(e, treeEvent) {
		if (!treeEvent.args[0]) {
			return false;
		}
		
		var link = $(treeEvent.args[0]);
		var node = link.attr("data-node");

		if (node) {
			parentId.val(node);
		}
		
		if (itemCache[node]) {
			fillForm(form, itemCache[node]);
			return;
		}
		
		var alert = WOOL.msgBox.add("Fetching data...");

		jQuery.ajax({
			url: window.location,
			type: "get",
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
	}).bind("move_node.jstree", function(e, treeEvent) {
		var alert = WOOL.msgBox.add("Moving node...");

		var position = treeEvent.rslt.p;
		var node = treeEvent.rslt.o.find("a").attr("data-node");
		var relatedId = treeEvent.rslt.r.find("a").attr("data-node");

		jQuery.ajax({
			url: tree.attr("data-move"),
			type: "post",
			data: {
				id: node,
				position: position,
				relatedId: relatedId
			},
			dataType: "json",
			success: function(res) {
				if (res.success == true) {
					WOOL.msgBox.remove(alert);
				} else {
					WOOL.msgBox.update(alert, res.msg, 5000);
				}
			},
			error: function(res, status) {
				WOOL.msgBox.update(alert, status, 5000);
			}
		});
	}).bind("remove.jstree", function(e, treeEvent) {
		var node = treeEvent.rslt.obj.find("a").attr("data-node");

		jQuery.ajax({
			url: tree.attr("data-delete"),
			type: "post",
			data: {
				id: node,
			},
			dataType: "json",
			success: function(res) {
				if (res.success == true) {
					WOOL.msgBox.remove(alert);
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

		var alert = WOOL.msgBox.add("Saving...");

		jQuery.ajax({
			url: form.attr("action") || window.location,
			type: form.attr("method") || "post",
			data: form.serialize(),
			dataType: "json",
			success: function(res) {
				WOOL.msgBox.remove(alert);
				tree.jstree("rename_node", null, res.item.title);
				changed.find("a").attr("data-node", res.item.pageDirectoryId);
				changed = null;
			},
			error: function(res, status) {
				WOOL.msgBox.update(alert, status, 5000);
			}
		});
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

		if (changed) {
			return;
		}
		
		if (confirm("Delete branch (including contents)?")) {
			tree.jstree("remove");
		}
	});
});

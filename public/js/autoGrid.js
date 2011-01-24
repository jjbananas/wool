jQuery(function($) {
	function columnElements(header, context, incHead) {
		var incHead = incHead || false;
		var header = $(header);
		
		var siblingsInc = header.parent().children();
		var rowIndex = siblingsInc.index(header);
		var columnItems = $("tr td:nth-child(" + (rowIndex+1) + ")", context);
		
		if (incHead) {
			return header.add(columnItems);
		}
		
		return columnItems;
	}
	
	function relatedHeader(td, context) {
		var siblingsInc = td.parent().children();
		var rowIndex = siblingsInc.index(td);
		return $("thead th:nth-child(" + (rowIndex+1) + ")", context);
	}
	
	// Create dragable absolute table.
	var mover = $("<table>").addClass("dataGrid dataGridMover");
	var moverHead = $("<thead>");
	var moverBody = $("<tbody>");
	
	$(document.body).append(mover);
	
	mover.append(moverHead).append(moverBody);
	
	function clearMover() {
		moverHead.html("");
		moverBody.html("");
		mover.hide();
	}
	
	// Set up grid functions.
	var grids = $(".dataGrid").not(".dataGridMover");
	
	grids.each(function() {
		var grid = $(this);
		
		// Dragables
		var headers = $(".dragable", grid);
		var dragRows = grid.attr("data-dragRows");
		dragRows = (dragRows && dragRows == "true");
		
		grid.mousedown(function(e) {
			var deadZone = 10;
			var srcItem = $(e.target).closest("th, tr");
			var startPos = {x: e.pageX, y: e.pageY};
			
			if (srcItem.length != 1) {
				return;
			}
			
			var horiz = srcItem.is("th");
			var widthFn = horiz ? "outerWidth" : "outerHeight";
			var dirProp = horiz ? "left" : "top";
			var pageProp = horiz ? "pageX" : "pageY";
			var moverContainer = horiz ? moverHead : moverBody;
			var srcItems = horiz ? headers : $("tbody tr", grid);
			
			if ((!horiz && !dragRows) || srcItem.closest("tfoot").length) {
				return;
			}
			
			e.preventDefault();
			
			// Start mover
			moverContainer.append(srcItem.clone());
			
			$(document).mousemove(function(e) {
				if (Math.abs(e.pageY - startPos.y) < deadZone && Math.abs(e.pageX - startPos.x) < deadZone) {
					return;
				}
				
				// Nudge mover away from cursor to prevent rapid cursor changing during movement.
				mover.css({top: e.pageY+5, left: e.pageX+5});
				mover.show();
			});
			
			$(document).one("mouseup", function(e) {
				$(document).unbind("mousemove");
				clearMover();
				
				if (Math.abs(e.pageY - startPos.y) < deadZone && Math.abs(e.pageX - startPos.x) < deadZone) {
					return;
				}
				
				e.preventDefault();
				
				var swapItem = null;
				var insertFn = "insertBefore";
				
				srcItems.each(function(pos) {
					var h = $(this);
					var halfSize = (h[widthFn]() / 2);
					var center = h.offset()[dirProp] + halfSize;
					var diff = e[pageProp] - center;
					
					if (Math.abs(diff) < halfSize) {
						swapItem = this;
						insertFn = (diff < 0 ? "insertBefore" : "insertAfter");
						return false;
					}
				});
				
				
				if (horiz) {
					var columnItems = columnElements(srcItem, grid, true);
					var swapColumnItems = columnElements(swapItem, grid, true);
					
					_.each(_.zip(columnItems, swapColumnItems), function(val) {
						if (val[0] && val[1] && val[0] != val[1]) {
							$(val[0])[insertFn](val[1]);
						}
					});
					
					var alert = WOOL.msgBox.add("Saving columns...");
					
					jQuery.ajax({
						url: "auto/headerUpdate",
						type: "post",
						data: {
							table: grid.attr("data-gridTable"),
							cols: $("th.dragable", grid).map(function() {
								return $(this).attr('data-column');
							})
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
				} else {
					if (srcItem == swapItem) {
						return;
					}
					
					$(srcItem)[insertFn](swapItem);
					
					var alert = WOOL.msgBox.add("Moving row...");
					
					jQuery.ajax({
						url: "auto/rowOrder",
						type: "post",
						data: {
							table: grid.attr("data-gridTable"),
							src: srcItem.attr("data-unique"),
							dst: $(swapItem).attr("data-unique"),
							before: insertFn
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
				}
			});
		});
		
		// Hover rows and columns
		grid.mouseover(function(e) {
			var el = $(e.target);
			if (!el.is("td")) {
				return;
			}
			
			el.parent().addClass("hover");
			columnElements(el, grid, true).addClass("hover");
		});
		
		grid.mouseout(function(e) {
			var el = $(e.target);
			if (!el.is("td")) {
				return;
			}
			
			el.parent().removeClass("hover");
			columnElements(el, grid, true).removeClass("hover");
		});
		
		// Activate row by clicking
		grid.click(function(e) {
			var el = $(e.target).closest("td");
			if (!el.is("td")) {
				return;
			}
			
			var row = el.parent();
			var checkbox = row.find("td.rowSelect input");
			
			if (e.target.tagName != "INPUT") {
				checkbox.attr("checked", !checkbox.attr("checked")).change();
			}
			row.toggleClass("selected", checkbox.is(":checked"));
		});
		
		// Double-click to edit items
		var editBox = $("#colunmEdit");
		(function() {
			editBox.css({
				position: "absolute",
				width: 400
			});
			editBox.find("input").select();
			
			// el is used to hold the currently dbl-clicked item.
			var el = null;
			var form = editBox.find("form");
			
			form.submit(function(e) {
				e.preventDefault();
				var alert = WOOL.msgBox.add("Saving...");
				
				jQuery.ajax({
					url: form.attr("action"),
					type: "post",
					data: form.serialize(),
					dataType: "json",
					success: function(res) {
						editBox.hide();
						
						if (res.success == true) {
							el.html(res.value);
							WOOL.msgBox.update(alert, "Success", 1000);
						} else {
							WOOL.msgBox.update(alert, res.msg, 5000);
						}
					},
					error: function(res, status) {
						editBox.hide();
						WOOL.msgBox.update(alert, status, 5000);
					}
				});
			});
			
			var table = $("<input>").attr("type", "hidden").attr("name", "table").appendTo(form);
			var column = $("<input>").attr("type", "hidden").attr("name", "column").appendTo(form);
			var unique = $("<input>").attr("type", "hidden").attr("name", "unique").appendTo(form);
			
			editBox.find("a").click(function(e) {
				e.preventDefault();
				editBox.hide();
			});
			
			grid.dblclick(function(e) {
				el = $(e.target);
				if (!el.is("td")) {
					return;
				}
				
				var header = relatedHeader(el, grid);
				
				var x = e.pageX+5;
				if (x + editBox.outerWidth(true) > $(document).width()) {
					x -= editBox.outerWidth(true) / 2;
				}
				
				editBox.css({
					top: e.pageY+5,
					left: x
				});
				
				editBox.show();
				editBox.find("input:first").val(el.text()).select();
				editBox.find("label:first").html(header.text());
				
				table.val(grid.attr("data-gridTable"));
				column.val(header.attr("data-column"));
				unique.val(el.closest("tr").attr("data-unique"));
			});
		})();
	});
	
	
	// Edit panels
	$(".foreign").each(function() {
		var el = $(this);
		el.click(function(e) {
			e.preventDefault();
			var grid = $(".selectionGrid");
			grid.show();
			grid.find("input").select();
		});
	});
});

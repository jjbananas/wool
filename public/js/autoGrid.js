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
		var body = grid.find("tbody");
		
		// Dragables
		var headers = $(".dragable", grid);
		var dragRows = grid.attr("data-dragRows");
		var headerUpdate = grid.attr("data-headerUpdate");
		
		var lastSelectedRow = null;
		
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
						url: headerUpdate,
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
						url: dragRows,
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
		body.mouseover(function(e) {
			var el = $(e.target).closest("td");
			el.parent().addClass("hover");
			columnElements(el, body, true).addClass("hover");
		});
		
		body.mouseout(function(e) {
			var el = $(e.target).closest("td");
			el.parent().removeClass("hover");
			columnElements(el, body, true).removeClass("hover");
		});
		
		// Sort columns by clicking header
		headers.click(function(e) {
			e.preventDefault();
		});
		
		// Activate row by clicking
		body.click(function(e) {
			var el = $(e.target).closest("td");
			
			var row = el.parent();
			var checkbox = row.find("td.rowSelect input");
			
			if (e.target.tagName != "INPUT") {
				checkbox.attr("checked", !checkbox.attr("checked")).change();
			}
			row.toggleClass("selected", checkbox.is(":checked"));
			
			// Group select
			if (e.shiftKey) {
				var group = [];
				var found = false;
				var rowEl = row.get(0);
				
				body.find("tr").each(function() {
					if (this == rowEl || this == lastSelectedRow) {
						if (found) {
							group.push(this);
							return false;
						}
						found = true;
					}
					
					if (found) {
						group.push(this);
					}
				});
				
				var checked = checkbox.is(":checked");
				
				jQuery.each(group, function(i, el) {
					var cb = $(el).find("td.rowSelect input");
					if (checked) {
						cb.attr("checked", "checked");
					} else {
						cb.removeAttr("checked");
					}
					cb.change();
					$(el).toggleClass("selected", checked);
				});
			}
			
			lastSelectedRow = row.get(0);
		});
		
		// Dynamic insert of new rows.
		var rowForm = $(".rowForm");
		var newRow = $(".newRow", grid);
		newRow.click(function(e) {
			if (!rowForm.length) {
				if (!$(e.target).is("a")) {
				 window.location = newRow.find("a:first").attr("href");
				}
				return true;
			}
			
			rowForm.trigger("matchPositions");
			
			e.preventDefault();
			
			newRow.css("visibility", "hidden");
			rowForm.show().find("input:not([type=hidden]):first").select();
		});
		
		rowForm.live("submit", function(e) {
			e.preventDefault();
			var form = $(e.target);

			var insRow = $('<tr><td colspan="0">Saving...</td></tr>');
			newRow.closest("tfoot").prepend(insRow);
			rowForm.trigger("matchPositions");
			
			jQuery.ajax({
				url: form.attr("action"),
				type: form.attr("method") || "post",
				data: form.serialize(),
				dataType: "json",
				success: function(res) {
					if (res.success == true) {
						insRow.remove();
						grid.find("tbody").append(res.html);
					} else {
						insRow.find("td").html(res.html).find("form").show();
						WOOL.msgBox.add(res.msg, 5000);
					}
					
					rowForm.trigger("matchPositions");
				},
				error: function(res, status) {
					WOOL.msgBox.add(status, 5000);
				}
			});
			
			form.get(0).reset();
			rowForm.show().find("input:not([type=hidden]):first").focus().select();
		});
		
		rowForm.positionMatch({matchedElement: newRow, startPositioned: false});
		
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
			
			body.dblclick(function(e) {
				el = $(e.target);
				if (!el.is("td") || el.closest("tfoot").length) {
					return;
				}
				
				var header = relatedHeader(el, grid);
				
				if (!header.hasClass("editable")) {
					return;
				}
				
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
		
		// Column tools.
		var columnSelect = $(".columnTools .button");
		var buttonOverlays = $(".podOverlay", columnSelect);
		columnSelect.click(function(e) {
			var columnOverlay = $(".podOverlay", this);
			
			if (!columnOverlay.length) {
				return;
			}
			
			if (columnOverlay.is(":visible")) {
				if ($(e.target).closest(".head").length) {
					e.preventDefault();
					buttonOverlays.hide();
					columnOverlay.hide();
				}
			} else {
				e.preventDefault();
				buttonOverlays.hide();
				columnOverlay.show();
			}
		});
		
		// Column sorting box.
		var columnSort = $(".columnSort");
		var rowDup = $(".duplicate", columnSort);
		var rowCount = $("tr", columnSort).length - 1;
		rowDup.hide();
		
		$(".addRow", columnSort).click(function(e) {
			e.preventDefault();
			
			var newRow = rowDup.clone();
			newRow.find(":input").removeAttr("disabled").each(function() {
				$(this).attr("name", $(this).attr("name").supplant({num: rowCount}));
			});
			rowCount++;
			newRow.show();
			$(this).closest("tr").before(newRow);
		});
		
		$(".delRow", columnSort).live("click", function(e) {
			e.preventDefault();
			$(this).closest("tr").remove();
		});
	});
	
	
	// Open/close toggle-able pods
	$(".podToggleOpen").each(function() {
		var pod = $(this);
		
		pod.find(".foot").click(function() {
			pod.toggleClass("open");
		});
	});
	
	
	
	// Date inputs
	//$("input[type=date]").daterangepicker();
	
	
	$(".comboBox").comboBox({buttonLabelEl: ".btn .label"});
});

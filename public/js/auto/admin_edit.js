jQuery(function() {
	(function() {
		// Edit panels - what follows is a massive fudge just because we can't nest
		// forms.
		var grid = $(".selectionGrid");
		var spacer = $(".selectionGridSpacer");
		var derived = $(".derivedPanel");
		var foreigners = $(".foreign");
		var selected = null;
		var blankHtml = grid.find(".results").html();
		
		grid.submit(function(e) {
			e.preventDefault();
			
			jQuery.post(grid.attr("action"), grid.serialize(), function(data) {
				grid.find(".results").html(data);
				positionSelectionGrid();
			});
		});
		
		grid.click(function(e) {
			var a = $(e.target).closest("a");
			if (!a.length) {
				return;
			}
			
			if (a.attr("href") == "#close") {
				e.preventDefault();
				closeSelectionGrid();
				return;
			}
			
			if (selected && a.attr("href") == "#use") {
				selected.find("a").html(a.closest("tr").find("span").text());
				selected.find("span").html(a.attr("data-id"));
				selected.find("input").val(a.attr("data-id"));
				closeSelectionGrid();
				return;
			}
		});
		
		function positionSelectionGrid() {
			grid.width(spacer.width());
			spacer.height(grid.height());
			var pos = spacer.position();
			grid.css({
				top: pos.top,
				left: pos.left
			});
		}
		
		function closeSelectionGrid() {
			selected = null;
			foreigners.removeClass("active");
			spacer.hide();
			grid.hide();
			derived.show();
		}
		
		$(window).resize(positionSelectionGrid);
		
		foreigners.each(function() {
			var el = $(this);
			el.click(function(e) {
				e.preventDefault();
				
				if (el.hasClass("active")) {
					closeSelectionGrid();
					return;
				}
				
				selected = el;
				
				grid.get(0).reset();
				grid.find(".results").html(blankHtml);
				
				foreigners.removeClass("active");
				el.addClass("active");
				
				grid.find(".searchTarget").html(el.find("label").text());
				grid.find(".searchTable").val(el.attr("data-references"));
				
				derived.hide();
				grid.show();
				spacer.show();
				positionSelectionGrid();
				grid.find("input").select();
			});
		});
	})();
	
	(function() {
		var form = $("form.tableEdit");
		form.validate({
			wrapper: 'div class="msgBox msgError msgFieldError"',
			rules: {
				"item[title]": {
					required: true,
					email: true,
					minlength: 20
				}
			}
		});
	})();
});

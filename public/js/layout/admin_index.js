jQuery(function($) {
	var container = $(".layoutPanel");
	
	var structure = $(".layoutStructure", container);
	var crumbList = $(".layoutBreadcrumbs ol", container);
	var areas = $(".area", structure);
	
	var def = layoutJson;
	var unsavedChanges = false;
	
	// This will need to match the generation code on PHP side.
	function generateHtmlBranch(targetEl, jsonBranch) {
		if (!def[jsonBranch]) {
			return;
		}
		
		var targetEl = $(targetEl);
		var branch = def[jsonBranch];
		var hor = (branch.direction == "horizontal");
		
		targetEl.empty();
		targetEl.append($('<span>').addClass("label").html(jsonBranch));
		
		if (!branch.children || !branch.children.length) {
			return;
		}
		
		_.each(branch.children, function(name) {
			var child = $("<div>").addClass("area").addClass(targetEl.hasClass("even") ? "odd" : "even");
			
			if (currentWidgets[name]) {
				if (currentWidgets[name].type == "content") {
					child.addClass("content");
				} else {
					child.addClass("widget");
				}
			}
			
			child.attr("id", "layout-" + name);
			
			if (hor) {
				var sizer = $("<div>").css({
					"float": "left",
					"width": (100 / branch.children.length) + "%"
				});
				sizer.append(child);
				targetEl.append(sizer);
			} else {
				targetEl.append(child);
			}
			generateHtmlBranch(child, name);
		});
	}
	
	function generateHtml(targetEl, jsonBranch) {
		generateHtmlBranch(targetEl, jsonBranch);
		areas = $(".area", structure);
	}
	
	var unassignedCanvas = $(".layoutUnassigned .layoutCanvas");
	
	unassignedCanvas.click(function(e) {
		var widget = $(e.target).closest(".widget");
		
		if (!widget) {
			return;
		}
		
		if ($(e.target).is("span")) {
			if (confirm("Are you sure you want to permanently delete this widget?")) {
				delete currentWidgets[widget.text()];
				unusedWidgets();
			}
			
			return;
		}
		
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		if (activeEl.length != 1) {
			alert("Please select an area above.");
			return;
		}
		
		if (currentWidgets[activeLabel]) {
			if (!confirm("Are you sure you want to replace the widget in this position?")) {
				return;
			}
		}
		
		currentWidgets[activeLabel] = currentWidgets[widget.text()];
		delete currentWidgets[widget.text()];
		
		var parentEl = activeEl.parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();

		generateHtml(parentEl, parentLabel);
		unusedWidgets();
	});
	
	function unusedWidgets() {
		unassignedCanvas.empty();
		var empty = true;
		
		_.each(currentWidgets, function(val, name) {
			if (def[name]) {
				return;
			}
			
			empty = false;
			unassignedCanvas.append('<div class="widget"><div class="inner"><span></span>' + name + '</div></div>');
		});
		
		if (empty) {
			unassignedCanvas.append('<div class="widget"><div class="inner">None</div></div>');
		}
	}
	
	function selectArea(el) {
		areas.removeClass("active");
		$(el).addClass("active");
		
		var crumbs = $(el).parents(".area");
		crumbList.empty();
		$(crumbs.get().reverse()).each(function() {
			addCrumbItem(this, true);
		});
		addCrumbItem(el, false);
		
		// Update form
		var label = $(el).find(".label").html();
		$("#area").val(label);
		$("#widget").val((currentWidgets[label] && currentWidgets[label].type) || "layout").change();
		

		$("#direction").val(def[label].direction || "vertical");
		
		var parentEl = $(el).parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();
		
		if (parentLabel && def[parentLabel].direction == "horizontal") {
			$(".sizeInput").show();
		} else {
			$(".sizeInput").hide();
		}

		if (def[label].sizeType == "grid") {
			$("#gridSelect").val(def[label].size);
		} else {
			$("#widthSelect").change();
		}
		$("#size").val(def[label].sizeType || "width").change();
	}
	
	function addCrumbItem(el, link) {
			var item = $("<li>");
			var label = $(el).find(".label").html();
			
			if (link) {
				var link = $("<a>").attr("href", "#").html(label).click(function(e) {
					e.preventDefault();
					selectArea(el);
				});
				
				item.append(link);
				item.append("\n");
			} else {
				item.html(label);
			}
			crumbList.append(item);	
	}
	
	function createWidgetConfigInput(label, input, target) {
		var newInput = $("<div>").addClass("input");
		newInput.append($("<label>").html(label));
		newInput.append(input);
		
		target.append(newInput);
	}
	
	var widgetCustom = $(".widgetCustom");
	
	function createWidgetConfigPanel(area) {
		var widget = currentWidgets[area];
		widgetCustom.empty();
		
		$("#widgetView").val(widget.view);
		
		if (widget.type == "content") {
			var input = $("<textarea>").val(widget.content).attr({name: "content"});
			createWidgetConfigInput("Content", input, widgetCustom);
		} else {
			_.each(widget.params, function(param, paramName) {
				var input = $("<input>").attr({type: "text", name: paramName});
				input.val(param);
				createWidgetConfigInput(widgetTypes[widget.type]["params"][paramName]["name"], input, widgetCustom);
			});
		}
	}
	
	function newName() {
		var i = 1;
		while (true) {
			if (!def["area" + i]) {
				return "area" + i;
			}
			i++;
		}
	}
	
	areas.live("click", function(e) {
		e.stopPropagation();
		selectArea(this);
	});
	
	function insertSibling(before) {
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var parentEl = activeEl.parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();
		
		if (!def[parentLabel]) {
			return;
		}
		
		var parentDef = def[parentLabel];
		var name = newName();
		def[name] = {};
		
		var offset = (before ? 0 : 1);
		parentDef.children.splice(_.indexOf(def[parentLabel].children, activeLabel)+offset, 0, name);
		
		generateHtml(parentEl, parentLabel);
		selectArea($("#layout-" + name));
	}
	
	$(".before", container).click(function(e) {
		e.preventDefault();
		insertSibling(true);
	});
	
	$(".after", container).click(function(e) {
		e.preventDefault();
		insertSibling(false);
	});
	
	$(".outside", container).click(function(e) {
		e.preventDefault();
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var parentEl = activeEl.parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();

		var name = newName();
		def[name] = {
			children: [activeLabel]
		};
		
		def[parentLabel].children[_.indexOf(def[parentLabel].children, activeLabel)] = name;
		
		generateHtml(parentEl, parentLabel);
		selectArea($("#layout-" + name));
	});
	
	$(".inside", container).click(function(e) {
		e.preventDefault();
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var name = newName();
		def[name] = {
			children: def[activeLabel].children,
			direction: def[activeLabel].direction
		};
		
		def[activeLabel].children = [name];
		
		generateHtml(activeEl, activeLabel);
		selectArea($("#layout-" + name));
	});
	
	$(".lastChild", container).click(function(e) {
		e.preventDefault();
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var name = newName();
		def[name] = {};
		
		var activeDef = def[activeLabel];
		activeDef.children = activeDef.children || [];
		activeDef.children.push(name);
		
		generateHtml(activeEl, activeLabel);
		selectArea($("#layout-" + name));
	});
	
	$(".remove", container).click(function(e) {
		e.preventDefault();
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var parentEl = activeEl.parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();
		
		def[activeLabel] = null;
		def[parentLabel].children = _.without(def[parentLabel].children, activeLabel);
		
		generateHtml(parentEl, parentLabel);
		selectArea($("#layout-" + parentLabel));
		unusedWidgets();
	});
	
	
	$("#area").change(function(e) {
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var parentEl = activeEl.parent().closest(".area");
		var parentLabel = parentEl.find(".label:first").text();
		
		console.log($(this).val());
		console.log(activeLabel);
		
		def[$(this).val()] = def[activeLabel];
		delete def[activeLabel];
		
		for (var ch = 0; ch < def[parentLabel].children.length; ch++) {
			if (def[parentLabel].children[ch] == activeLabel) {
				def[parentLabel].children[ch] = $(this).val();
				break;
			}
		}
		
		generateHtml(activeEl, $(this).val());
		areas = $(".area", structure);
	});
	
	
	var sizeSelectors = $(".sizeSelect");
	
	$("#size").change(function(e) {
		sizeSelectors.hide();
		sizeSelectors.filter("#" + $(this).val() + "Select").show().change();
	});
	
	sizeSelectors.change(function(e) {
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		def[activeLabel].sizeType = $(this).attr("id").slice(0,-6);
		def[activeLabel].size = $(this).val();
	});
	
	$("#direction").change(function(e) {
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		def[activeLabel][$(this).attr("name")] = $(this).val();
		
		generateHtml(activeEl, activeLabel);
	});
	
	$("#widget").change(function(e) {
		var area = $("#area").val();
		var type = $(this).val();
		
		if (type == "layout") {
			$("#widget-config").hide();
			return;
		}
		
		$("#widget-config").show();
		
		if (!currentWidgets[area] || type != currentWidgets[area].type) {
			if (type == "content") {
				currentWidgets[area] = {
					type: type,
					content: ""
				};
			} else {
				var params = {};
				_.each(widgetTypes[type].params, function(value, key) {
					params[key] = value.default;
				});
				
				currentWidgets[area] = {
					type: type,
					view: widgetTypes[type].views[0],
					params: params
				};
			}
		}
		
		createWidgetConfigPanel(area);
	});
	
	
	$("#widget-config :input").live("change", function() {
		var area = $("#area").val();
		var input = $(this);
		
		if (currentWidgets[area].type == "content") {
			currentWidgets[area].content = input.val();
		} else {
			currentWidgets[area].params[input.attr("name")] = input.val();
		}
	});
	
	
	$(".iconAddItem").click(function(e) {
		e.preventDefault();
		var alert = WOOL.msgBox.add("Saving...");
		
		jQuery.ajax({
			url: "/shaded/admin/layout/setLayout",
			type: "post",
			data: {
				page: 1,
				layout: jQuery.toJSON(def),
				widgets: jQuery.toJSON(currentWidgets)
			},
			success: function() {
				WOOL.msgBox.update(alert, "Changes saved!", 3000);
				unsavedChanges = false;
			}
		});
	});
	
	$(window).bind("beforeunload", function(e) {
		if (unsavedChanges) {
			return "You have unsaved changes. Do you want to leave?";
		}
	});
	
	generateHtml($(".layoutCanvas .area:first", structure), "body");
	unusedWidgets();
	areas = $(".area", structure);
});

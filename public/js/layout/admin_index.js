jQuery(function($) {
	var container = $(".layoutPanel");
	
	var structure = $(".layoutStructure", container);
	var crumbList = $(".layoutBreadcrumbs ol", container);
	var areas = $(".area", structure);
	
	var def = layoutJson;
	
	// This will need to match the generation code on PHP side.
	function generateHtml(targetEl, jsonBranch) {
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
			generateHtml(child, name);
		});
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
		$("#direction").val(def[label].direction || "vertical");
		$("#widget").val((currentWidgets[label] && currentWidgets[label].type) || "layout").change();
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
		
		$("#widgetArea").val(area);
		$("#widgetView").val(widget.view);
		
		if (widget.type == "content") {
			createWidgetConfigInput("Content", $("<textarea>"), widgetCustom);
		} else {
			_.each(widget.params, function(param, paramName) {
				var input = $("<input>").attr("type", "text");
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
		areas = $(".area", structure);
		
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
		areas = $(".area", structure);
		
		selectArea($("#layout-" + name));
	});
	
	$(".inside", container).click(function(e) {
		e.preventDefault();
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		var name = newName();
		def[name] = {};
		
		var activeDef = def[activeLabel];
		activeDef.children = activeDef.children || [];
		activeDef.children.push(name);
		
		generateHtml(activeEl, activeLabel);
		areas = $(".area", structure);
		
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
		areas = $(".area", structure);
		
		selectArea($("#layout-" + parentLabel));
	});
	
	
	$("#direction").change(function(e) {
		var activeEl = $(".active", structure);
		var activeLabel = activeEl.find(".label:first").text();
		
		def[activeLabel][$(this).attr("name")] = $(this).val();
		
		generateHtml(activeEl, activeLabel);
		areas = $(".area", structure);
	});
	
	$("#widget").change(function(e) {
		var area = $("#area").val();
		var type = $(this).val();
		
		if (type == "layout") {
			$("#widget-config").hide();
			return;
		}
		
		$("#widget-config").show();
		
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
		
		createWidgetConfigPanel(area);
	});
	
	
	$(".iconAddItem").click(function(e) {
		e.preventDefault();
		
		jQuery.ajax({
			url: "/shaded/admin/layout/setLayout",
			type: "post",
			data: {
				page: 1,
				layout: jQuery.toJSON(def),
				widgets: jQuery.toJSON(currentWidgets)
			},
			success: function() {
				console.log("done");
			}
		});
	});
	
	generateHtml($(".layoutCanvas", structure), "layout");
	areas = $(".area", structure);
});

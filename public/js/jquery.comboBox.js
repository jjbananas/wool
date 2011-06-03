/*
	Slider ComboBox Plugin.
	
	Takes a select box and turns it into a slider using progressive enhancement.
	
	Usage:
		Call the comboBox function on the element that you want to control.
		
		$(".comboBox").comboBox();
		
		This should be called on HTML something like the following:
		
		<div class="comboBox">
			<span class="btn btnLink btnLinkThin">Save Search: <span class="label"></span></span>
			
			<div class="combo">
				<input type="text" name="save_name" data-action="" autocomplete="off" />
				
				<ul class="options">
					<li>Tax free</li>
					<li>Some other</li>
					<li>And one more</li>
				</ul>
				
				<ul class="fixed">
					<li><a href="">Manage Saved Searches</a></li>
				</ul>
			</div>
		</div>
		
		Options are given by list items. Those within the ".options" area will be
		dynamically hidden and shown as the user searches. The ".fixed" options
		will remain visible.
		
		Options with a link inside them will not function as a normal option.
		Instead, selecting a link option will follow the link immediately.
	
	Options:
		buttonLabelEl
			Selector or element to use as the button label. This will be updated with
			the current value so that it is visible when the combo box is closed.
			
		clickAwayClose
			True to close the combo box when the user clicks outside on the
			surrounding document.
			
		clickAwayCancel
			True if clicking away (see clickAwayClose) will be regarded as a "cancel"
			operation. Cancel will return the selection to its previous state rather
			than selecting a new value.
			
		submitOnSelect
			True if selecting an option should submit the form. Submission will use
			the containing FORM element by default, but this can be overridden by
			setting a "data-action" attribute on the INPUT element. (See example HTML
			above.) Using "data-action" will also work if the combo box is stand-alone
			outside of any form.
			
		closeOnSelect
			True to close the combo box when an option is selected.
			
		selectActiveOnClose
			True will select the active option when the combo box is closed. False
			will leave the input text untouched. (Nb. Cancel operations will always
			revert the value, so this option is ignored.)
*/

(function($) {

	$.fn.comboBox = function(options) {
		var opts = $.extend({}, $.fn.comboBox.defaults, options);
		
		// Get the number of elements we are replacing.
		var numReplaced = this.length;
		
		this.each(function() {
			var box = $(this);
			var button = box.find(".btn");
			var dropdown = box.find(".combo");
			var input = box.find("input");
			var newItem = $("<li>");
			
			var buttonLabelEl = $(opts.buttonLabelEl, box);
			
			box.find("ul.options").append(newItem);
			newItem.hide();
			
			var items = box.find("ul.options li");
			var allItems = box.find("li");
			
			var lastSearch = "";
			var currentVal = input.val();
			
			function searchItems(search) {
				var exactMatch = false;
				
				if (lastSearch == search) {
					return;
				}
				lastSearch = search;

				items.each(function() {
					var val = $(this).attr("data-value") || $(this).text();
					$(this).attr("data-value", val);
					
					var match = val.toLowerCase().indexOf(search.toLowerCase());
					if (match != -1) {
						$(this).show();
						$(this).html(
							val.slice(0, match)
							+ "<b>"
							+ val.slice(match, match+search.length)
							+ "</b>"
							+ val.slice(match+search.length)
						);
						if (match == 0 && search.length == val.length) {
							exactMatch = true;
						}
					} else {
						$(this).hide();
					}
				});
				
				// For none-exact matches add a "new" option.
				if (!search || exactMatch) {
					newItem.hide();
				} else {
					newItem.html('"' + search + '" (New)');
					newItem.show();
				}
				
				// Activate first item in list if the previous selection has disappeared.
				if (!allItems.filter(".active").is(":visible")) {
					allItems.removeClass("active");
					nextActive();
				}
			}
			
			input.keydown(function(e) {
				if (e.keyCode == 13) { // Enter
					selectItem(allItems.filter(".active"));
					
					if (!opts.submitOnSelect) {
						e.preventDefault();
						return;
					}
					
					if (submitForm(false)) {
						e.preventDefault();
					}
				}
				else if (e.keyCode == 38) { // Up
					prevActive();
				}
				else if (e.keyCode == 40) { // Down
					nextActive();
				}
				else if (e.keyCode == 27) { // Escape
					close(true);
				}
			});
			
			input.bind("keyup input", function(e) {
				var val = input.val();
				searchItems(val);
			});
			
			function toggle() {
				if (dropdown.is(":visible")) {
					close(false);
				} else {
					open();
				}
			}
			
			function open() {
				dropdown.show();
				input.focus();
			}
			
			function close(cancel) {
				if (!dropdown.is(":visible")) {
					return;
				}
				
				dropdown.hide();
				
				if (cancel) {
					input.val(currentVal);
					searchItems(currentVal);
					if (opts.buttonLabelEl) {
						buttonLabelEl.html(currentVal);
					}
				} else {
					if (opts.selectActiveOnClose) {
						selectItem(allItems.filter(".active"), true);
					} else {
						selectItem(newItem, true);
					}
				}
			}
			
			function nextActive() {
				var visible = allItems.filter(":visible");
				var index = visible.index(visible.filter(".active")) + 1;
				
				if (index >= visible.length) {
					index = 0;
				}

				allItems.removeClass("active");
				visible.eq(index).addClass("active");
			}
			
			function prevActive() {
				var visible = allItems.filter(":visible");
				var index = visible.index(visible.filter(".active")) - 1;
				
				if (index < 0) {
					index = visible.length-1;
				}

				allItems.removeClass("active");
				visible.eq(index).addClass("active");
			}
			
			function selectItem(item, closing) {
				if(!item.filter(newItem).length) {
					var val = item.text();
					input.val(val);
					searchItems(val);
				}
				
				if (!closing && opts.closeOnSelect) {
					close(false);
				}
				
				if (opts.buttonLabelEl) {
					buttonLabelEl.html(input.val());
				}
				
				allItems.removeClass("active");
				item.addClass("active");
				
				currentVal = input.val();
			}
			
			function submitForm(forceSubmit) {
				var form = input.closest("form");
				
				if (input.attr("data-action")) {
					var data = {};
					
					if (form.length) {
						data = form.serialize();
					} else {
						data[input.attr("name")] = input.val();
					}
					
					var alert = WOOL.msgBox.add("Waiting...");
					
					jQuery.ajax({
						url: input.attr("data-action"),
						type: "post",
						data: data,
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

					return true;
				}
				
				if (forceSubmit) {
					form.submit();
					return true;
				}
				
				return false;
			}
			
			button.click(toggle);
			
			dropdown.click(function(e) {
				var item = $(e.target).closest("li");
				if (!item.length) {
					return;
				}
				
				// Let the browser deal normally with link items.
				if (item.find("a").length) {
					return;
				}
				
				selectItem(item);
				
				if (opts.submitOnSelect) {
					submitForm(true);
				}
			});
			
			// Close if clicking elsewhere on the document.
			if (opts.clickAwayClose) {
				$(document).click(function(e) {
					if (!$(e.target).parents().filter(box).length) {
						close(opts.clickAwayCancel);
					}
				});
			}
		});
		
		return this;
	};
	
	// Allow access to defaults.
	$.fn.comboBox.defaults = {
		buttonLabelEl: null,
		clickAwayClose: true,
		clickAwayCancel: false,
		submitOnSelect: false,
		closeOnSelect: true,
		selectActiveOnClose: true
	};

})(jQuery);

<?php
	$self->js("/js/jquery.json-2.2.js");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("controller"=>"auto", "action"=>"index")) ?></li>
		<li>Layout</li>
	</ol>
</div>

<div class="pad">
	<div class="pod">
		<div class="head">
			<h2 class="icon iconEdit">Layout</h2>
		</div>
		
		<div class="body splitBody">
			<div class="s1of2">
				<div class="editPanel">
					<?php echo renderNotices() ?>
					
					<div class="pod podNested">
						<div class="head">
							<h3>Area Configuration</h3>
						</div>
						
						<div class="editPanel areaConfig">
							<div class="input">
								<label for="area">Area</label>
								<input type="text" name="area" id="area" value="" />
							</div>
							
							<div class="input">
								<label for="direction">Direction</label>
								<select type="text" name="direction" id="direction">
									<option>horizontal</option>
									<option>vertical</option>
								</select>
							</div>
							
							<div class="input sizeInput">
								<label for="size">Size</label>
								<select type="text" name="size" id="size">
									<option>width</option>
									<option>grid</option>
								</select>
								
								<select id="widthSelect" class="sizeSelect">
									<option value="">1/1</option>
									<option value="s1of2">1/2</option>
									<option value="s1of3">1/3</option>
									<option value="s2of3">2/3</option>
									<option value="s1of4">1/4</option>
									<option value="s3of4">3/4</option>
								</select>
								
								<select id="gridSelect" class="sizeSelect hide">
									<?php foreach (range(1,14) as $width) { ?>
									<option value="<?php echo $width ?>"><?php echo $width ?></option>
									<?php } ?>
								</select>
							</div>
							
							<div class="input">
								<label for="widget">Widget</label>
								<select type="text" name="widget" id="widget">
									<option value="layout">Layout</option>
									<option value="content">Content</option>
									<option value="productcollection">Product Collection</option>
									<option value="banner">Banner</option>
								</select>
							</div>
						</div>
					</div>
					
					<div id="widget-config" class="pod podNested marTop hide">
						<div class="head">
							<h3>Widget Configuration</h3>
						</div>
						
						<div class="editPanel">
							<div class="input">
								<label for="widgetView">Widget View</label>
								<select type="text" name="widgetView" id="widgetView">
									<option>default</option>
								</select>
							</div>
							
							<div class="widgetCustom">
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="s1of2">
				<div class="padh">
					<div class="pod podToolbar podEditTools">
						<div class="pad">
							<?php echo linkTo('Delete', array("action"=>"delete"), 'class="btnLink btnLinkLight icon iconDelete"') ?>
							<?php echo linkTo('Edit in Page', array("action"=>"delete"), 'class="btnLink btnLinkLight icon iconEdit" target="_blank"') ?>
						</div>
					</div>
				</div>
				
				<div class="pad layoutPanel">
					<div class="layoutToolbar">
						<a href="#" class="inside">Inside</a>
						<a href="#" class="outside">Outside</a>
						<a href="#" class="before">Before</a>
						<a href="#" class="after">After</a>
						<a href="#" class="remove">Remove</a>
					</div>
					
					<div class="layoutBreadcrumbs">
						<ol>
							<li><a href="#">body</a></li>
							<li><a href="#">mainContent</a></li>
							<li>banner</li>
						</ol>
					</div>
					
					<div class="layoutStructure">
						<div class="layoutCanvas">
							<div class="area even">
								<span class="label">body</span>
							</div>
						</div>
					</div>
					
					<div class="layoutUnassigned">
						Non-positioned Widgets
						
						<div class="layoutCanvas">
							<div class="widget"><div class="inner">None</div></div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="clear"></div>
		</div>
		
		<div class="foot">
			<?php echo linkTo("Save", array("action"=>"edit"), 'class="btnLink icon iconAddItem"') ?>
		</div>
	</div>
</div>

<script>
var layoutJson = <?php echo $page->row->layout ?>;

var widgetTypes = <?php echo Widget::typeDefJson() ?>;

var currentWidgets = <?php echo $page->widgetJson() ?>;
</script>
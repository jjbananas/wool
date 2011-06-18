<?php

class BannerWidget extends Widget {
	public static function define() {
		return array(
			"id" => "banner",
			"controller" => "banner",
			"name" => "Banner",
			"views" => array("default"),
			"params" => array(
				"collection" => array(
					"name" => "Image Collection",
					"default" => "1"
				)
			)
		);
	}
	
	public function action() {
		$this->collection = WoolTable::fetch("image_collection", $this->param('collection', 0));
		$this->images = new RowSet(<<<SQL
select i.*
from image i
join image_in_collection iic on iic.imageId = i.imageId
where iic.imageCollectionId = ?
SQL
		, $this->param('collection', 0));
	}
}

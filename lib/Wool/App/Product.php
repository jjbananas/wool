<?php

class Product extends WoolTable {
	public static function keySearch($search) {
		if (is_numeric($search)) {
			return new RowSet(<<<SQL
select productId id, price, title
from product
where productId = ?
SQL
			, $search);
		}
		
		return new RowSet(<<<SQL
select productId id, price, title
from product
where title like ?
limit 10
SQL
		, "{$search}%");
	}
}

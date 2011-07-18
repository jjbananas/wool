<?php

class GridData extends WoolTable {
	public static function saveData($uerId, $reference, $data) {
		$data = serialize($data);
		WoolDb::upsert(array(
			"userId" => $userId,
			"reference" => $reference,
			"data" => $data
		));
	}

	public static function byReference($userId, $reference) {
		$data = WoolDb::fetchOne("select gridData from grid_data where reference = ?", $reference);
		return $data ? unserialize($data) : array();
	}
}

WoolTable::registerTable("GridData", "grid_data");

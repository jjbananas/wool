<?php

class GridData extends WoolTable {
	public static function saveData($userId, $reference, $data) {
		$data = serialize($data);
		WoolDb::upsert("grid_data", array(
			"userId" => $userId,
			"reference" => $reference,
			"gridData" => $data
		));
	}

	public static function byReference($userId, $reference) {
		$data = WoolDb::fetchOne("select gridData from grid_data where reference = ?", $reference);
		return $data ? unserialize($data) : array();
	}
}

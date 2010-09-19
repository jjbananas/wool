<?php

class TransactionRaii {
	private $success = false;
	private $strict = false;
	private static $depth = 0;
	
	public function __construct($strict=false) {
		self::$depth++;
		$this->strict = $strict;
		
		if (self::$depth == 1 || $strict) {
			WoolDb::beginTransaction();
		}
	}
	public function __destruct() {
		self::$depth--;
		
		if (self::$depth != 0 && !$this->strict) {
			return;
		}
		
		if ($this->success) {
			WoolDb::commit();
		} else {
			WoolDb::rollback();
		}
	}
	public function success() {
		$this->success = true;
	}
}

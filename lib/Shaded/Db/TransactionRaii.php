<?php

class TransactionRaii {
	private $success = false;
	private $strict = false;
	private static $depth = 0;
	
	public function __construct($strict=false) {
		self::$depth++;
		$this->strict = $strict;
		
		if (self::$depth == 1 || $strict) {
			EvanceDb::beginTransaction();
		}
	}
	public function __destruct() {
		self::$depth--;
		
		if (self::$depth != 0 && !$this->strict) {
			return;
		}
		
		if ($this->success) {
			EvanceDb::commit();
		} else {
			EvanceDb::rollback();
		}
	}
	public function success() {
		$this->success = true;
	}
}

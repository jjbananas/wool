<?php

class WoolPager {
	private $name;
	private $count;
	private $perPage;
	
	public function __construct($name, $itemCount, $perPage=50) {
		$this->name = $name;
		$this->count = $itemCount;
		$this->perPage = $perPage;
	}
	
	// Get current page.
	public function page() {
		return id_param($this->name . '_page', 1);
	}
	
	public function isFirst() {
		return $this->page() == 1;
	}
	public function isLast() {
		return $this->page() == $this->totalPages();
	}
	
	public function totalPages() {
		$perPage = $this->getPerPage();
		if (!$perPage) { return 1; }
		
		$lastPageRows = $this->count % $perPage;
		$total = (int)($this->count / $perPage);
		$total += $lastPageRows ? 1 : 0;
		return $total;
	}
	
	public function setPerPage($num) {
		$this->perPage = $num;
	}
	public function getPerPage() {
		return id_param("{$this->name}_perPage", $this->perPage);
	}
}

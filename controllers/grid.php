<?php

class GridController extends AppController {
	function adminSavedSearches() {
		$this->table = param('id');

		if (!$this->table) {
			$this->redirectTo(array("controller"=>"auto"));
		}

		if (Session::loggedIn()) {
			$_SESSION['grids'][$this->table] = GridData::byReference($this->table);
		}

		if (!isset($_SESSION['grids'][$this->table]['filters'])) {
			$_SESSION['grids'][$this->table]['filters'] = array();
		}
	}
}
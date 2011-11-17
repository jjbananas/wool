<?php

class TestEvent extends WoolEvent {
	public static function onProductDispatch() {
		return true;
	}
}

<?php
function tag_build($element, $attrs, $close=true) {
	$attrHTML = array();

	foreach ($attrs as $name=>$values) {
		if (!$values) {
			continue;
		}

		if (is_array($values)) {
			$values = join(' ', $values);
		}
		$attrHTML[] = "{$name}=\"{$values}\"";
	}

	$attrHTML = join(' ', $attrHTML);
	$attrHTML = $attrHTML ? ' ' . $attrHTML : '';
	$slash = $close ? ' /' : '';
	return "<{$element}{$attrHTML}{$slash}>";
}

function element_tag_build($element, $attrs, $content) {
	$tag = tag_build($element, $attrs, false);
	return "{$tag}{$content}</{$element}>";
}

function add_error_classes($obj, $field) {
	$classes = array();

	// Handle WoolTable
	if ($obj instanceof StdClass) {
		$validators = WoolTable::validators($obj, $field);
		if (isset($validators['required'])) {
			$classes[] = 'required';
		}
		return $classes;
	}

	// Handle Doctrine
	// Invalid after validation attempt.
	if ($obj->getErrorStack()->contains($field)) {
		$classes[] = 'invalid';
	}

	// Required fields.
	$validators = $obj->getTable()->getFieldValidators($field);
	if (isset($validators['notblank'])) {
		$classes[] = 'required';
	}

	return $classes;
}

function class_array($classes, $preSpace=false) {
	if (!$classes) {
		return '';
	}

	$cls = join(' ', $classes);
	$cls = "class=\"{$cls}\"";
	if ($preSpace) {
		return ' ' . $cls;
	}

	return $cls;
}

function label($objName, $field, $attrs = array()) {
	return element_tag_build('label', array_merge(array(
			'for' => "{$objName}_{$field}"
		), $attrs), WoolTable::nameFor($objName, $field)
	);
}

function text_field($obj, $objName, $field, $attrs = array()) {
	return tag_build('input', array_merge(array(
			'type' => 'text',
			'id' => "{$objName}_{$field}",
			'name' => "{$objName}[{$field}]",
			'value' => "{$obj->$field}",
			'class' => add_error_classes($obj, $field)
		), $attrs)
	);
}

function text_field_tag($name, $value = null, $attrs = array()) {
	return tag_build('input', array_merge(array(
			'type' => 'text',
			'id' => $name,
			'name' => $name,
			'value' => html(param($name, $value))
		), $attrs)
	);
}

function file_field($obj, $objName, $field, $attrs = array()) {
	return tag_build('input', array_merge(array(
			'type' => 'file',
			'id' => "{$objName}_{$field}",
			'name' => "{$objName}[{$field}]",
			'value' => "{$obj->$field}",
			'class' => add_error_classes($obj, $field)
		), $attrs)
	);
}

function file_field_tag($name, $value = null, $attrs = array()) {
	$attrs['type'] = 'file';
	return text_field_tag($name, $value, $attrs);
}

function password_field($obj, $objName, $field, $attrs = array()) {
	$attrs['type'] = 'password';
	return text_field($obj, $objName, $field, $attrs);
}

function password_field_tag($name, $value = null, $attrs = array()) {
	$attrs['type'] = 'password';
	return text_field_tag($name, $value, $attrs);
}

function date_field($obj, $objName, $field, $attrs = array()) {
	$attrs['type'] = 'date';
	return text_field($obj, $objName, $field, $attrs);
}

function date_field_tag($name, $value = null, $attrs = array()) {
	$attrs['type'] = 'date';
	return text_field_tag($name, $value, $attrs);
}

function radio_tag($name, $value = null, $checkedValue = null, $attrs = array()) {
	return tag_build('input', array_merge($attrs, array(
			'type' => 'radio',
			'id' => $name . '_' . $value,
			'name' => $name,
			'value' => $value,
			'checked' => param($name, $checkedValue) == $value ? 'checked' : null
		)
	));
}

function text_area($obj, $objName, $field, $attrs = array()) {
	return tag_build('textarea', array_merge($attrs, array(
			'id' => $field,
			'name' => "{$objName}[{$field}]",
			'class' => add_error_classes($obj, $field)
		)
	), false) . "{$obj->$field}</textarea>";
}

function text_area_tag($name, $value, $attrs = array()) {
	$value = param($name, $value);
	return tag_build('textarea', array_merge($attrs, array(
			'id' => $name,
			'name' => $name,
		)
	), false) . "{$value}</textarea>";
}

function check_box($obj, $objName, $field, $attrs = array()) {
	$attrs = array_merge($attrs, array(
			'type' => 'checkbox',
			'id' => $field,
			'name' => "{$objName}[{$field}]",
			'value' => 'Y',
			'class' => add_error_classes($obj, $field)));

	if($obj->$field == 'Y') {
		$attrs = array_merge($attrs, array('checked' => 'checked'));
	}

	return tag_build('input', $attrs);
}

function check_box_tag($name, $value = null, $checkedValue = null, $attrs = array()) {
	$param = isset($_REQUEST[$name][$value]) ? $_REQUEST[$name][$value] : $checkedValue;
	return tag_build('input', array_merge($attrs, array(
			'type' => 'checkbox',
			'id' => $name . '_' . $value,
			'name' => $name . "[" . $value . "]",
			'value' => $value,
			'checked' => $param == $value ? 'checked' : null
		)
	));
}

function array_to_options($arr, $names, $values=null) {
	$results = array();
	foreach ($arr as $a) {
		if ($values) {
			$results[$a[$names]] = $a[$values];
		} else {
			$results[$a[$names]] = $a[$names];
		}
	}
	return $results;
}

function object_to_options($objs, $names, $values=null, $default=null) {
	$results = $default ? array($default) : array();
	foreach ($objs as $a) {
		if ($values) {
			$results[$a->$values] = $a->$names;
		} else {
			$results[$a->$names] = $a->$names;
		}
	}
	return $results;
}

function prepend_option($name, $value, $options) {
	if (isset($options[0]) && isset($options[0][1])) {
		return array_merge(array(array($name, $value)), $options);
	}
	return array_merge(array(array($name)), $options);
}

function select_box($obj, $objName, $field, $options, $ignoreValues = false, $class='') {
	$optionHTML = '';
	foreach ($options as $val=>$text) {
		$value = '';
		$selected = '';
		
		if (!$ignoreValues) {
			$value = " value=\"{$val}\"";
			$selected = $val == $obj->$field ? ' selected="selected"' : '';
		} else {
			$selected = $text == $obj->$field ? ' selected="selected"' : '';
		}
		$optionHTML .= "<option{$value}{$selected}>{$text}</option>\n";
	}

	$cls = class_array(array_merge(add_error_classes($obj, $field), array($class)));

	return <<<HTML
<select id="{$field}" name="{$objName}[{$field}]" {$cls}>
{$optionHTML}
</select>
HTML;
}

function select_box_tag($name, $options, $selected=null, $ignoreValues = false) {
	$optionHTML = '';
	$selected = param($name, $selected);

	foreach ($options as $val=>$text) {
		$value = '';
		$selAttr = '';

		if (!$ignoreValues) {
			$value = " value=\"{$val}\"";
			$selAttr = $val == $selected ? ' selected="selected"' : '';
		} else {
			$selAttr = $text == $selected ? ' selected="selected"' : '';
		}
		$optionHTML .= "<option{$value}{$selAttr}>{$text}</option>\n";
	}
	return element_tag_build('select', array('id'=>$name, 'name'=>$name), $optionHTML);
}

function msg_for_validator($obj, $field, $error) {
	$errorStrs = array(
		'type' => '%s must be a valid %s.',
		'length' => '%s can have at most %d characters.',
		'notblank' => '%s must not be empty.',
		'values' => '%s must be a valid option.'
	);

	$errorConvert = array(
		'enum'=>'values'
	);

	if (isset($errorConvert[$error])) {
		$error = $errorConvert[$error];
	}

	if (method_exists($obj, 'errorMessage')) {
		$msg = $obj->errorMessage($field, $error);
		if ($msg) {
			return $msg;
		}
	}

	// Get the value specified for the validator.
	$columns = $obj->getTable()->getColumns();
	$validatorValue = $columns[strtolower($field)][$error];

	$doctrineMan = Doctrine_Manager::getInstance();

	$validators = $doctrineMan->getValidators();

	if(in_array($error, $validators)) {
		$validator = Doctrine_Validator::getValidator($error);

		if(method_exists($validator, 'errorMessage')) {
			return $validator->errorMessage($field, $validatorValue, $obj->get($field));
		}
	}

	if (isset($errorStrs[$error])) {
		return sprintf($errorStrs[$error], $field, $validatorValue);
	} else {
		return "{$field}: {$error}";
	}
}

function error_list($obj, $options=array()) {
	$options = array_merge(array(
		'text' => 'Some values may have been entered incorrectly. Please correct the following:'
	), $options);

	$errors = $obj->getErrorStack();
	if (!$errors->toArray()) {
		return;
	}

	$listHTML = array();
	foreach ($errors as $field=>$fieldErrors) {
		foreach ($fieldErrors as $fieldError) {
			$errStr = msg_for_validator($obj, $field, $fieldError);
			$listHTML[] = "<li>{$errStr}</li>";
		}
	}

	$listHTML = join("\n", $listHTML);

	return <<<HTML
<div class="error-summary">
	<p class="intro">{$options['text']}</p>
	<ol>
		{$listHTML}
	</ol>
</div>
HTML;
}

function error_list_for($obj, $field) {
	$errors = $obj->getErrorStack();
	if (!$errors->contains($field)) {
		return;
	}

	$listHTML = array();
	foreach ($errors->get($field) as $fieldError) {
			$errStr = msg_for_validator($obj, $field, $fieldError);
			$listHTML[] = "<li>{$errStr}</li>";
	}

	$listHTML = join("\n", $listHTML);

	return <<<HTML
<div class="error-field">
	<ol>
		{$listHTML}
	</ol>
</div>
HTML;
}


function formatErrors($obj=null, $options=array()) {
	$options = array_merge(array(
		'text' => 'Some values may have been entered incorrectly. Please correct the following:'
	), $options);
	
	$errors = WoolErrors::get($obj);
	if (!$errors) {
		return;
	}
	
	if ($obj) {
		$errors = array($errors);
	}

	$listHTML = array();
	foreach ($errors as $objectErrors) {
		foreach ($objectErrors as $field=>$fieldErrors) {
			foreach ($fieldErrors as $fieldError) {
				$listHTML[] = "<li>{$fieldError}</li>";
			}
		}
	}

	$listHTML = join("\n", $listHTML);

	return <<<HTML
<div class="msgBox msgError">
	<p class="intro">{$options['text']}</p>
	<ol>
		{$listHTML}
	</ol>
</div>
HTML;
}

function formatError($obj, $field) {
	$errors = WoolErrors::get($obj, $field);
	if (!$errors) {
		return;
	}

	$listHTML = array();
	foreach ($errors as $fieldError) {
		$listHTML[] = "<li>{$fieldError}</li>";
	}

	$listHTML = join("\n", $listHTML);

	return <<<HTML
<div class="error-field">
	<ol>
		{$listHTML}
	</ol>
</div>
HTML;
}


// Flash system.
function flash($flash, $type='info', $redirect=null) {
	$_SESSION['flash'] = $flash;
	$_SESSION['flash-type'] = $type;
	if ($redirect) {
		redirectTo($redirect);
	}
}

function renderFlash() {
	// Get flash message.
	if (!isset($_SESSION['flash'])) {
		return '';
	}

	$msg = $_SESSION['flash'];
	$type = isset($_SESSION['flash-type']) ? $_SESSION['flash-type'] : '';
	unset($_SESSION['flash']);
	unset($_SESSION['flash-type']);

	return <<<HTML
<div class="flash {$type}">
	{$msg}
</div>
HTML;
}

function renderNotices() {
	return renderFlash() . "\n" . formatErrors();
}


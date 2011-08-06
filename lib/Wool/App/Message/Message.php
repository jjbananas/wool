<?php

class WoolMessage {
	public static function existingLayouts() {
		return Query("select messageLayoutId, name from message_layout");
	}

	public static function importedTypes() {
		return Query("select * from message_type where customCampaign = false");
	}

	public static function templatesFor($typeId) {
		$typeId = is_array($typeId) ? $typeId : array($typeId);
		return Query("select * from message_template where messageTypeId in :tids", array("tids"=>$typeId));
	}

	public static function layoutsFor($layoutIds) {
		$layoutIds = is_array($layoutIds) ? $layoutIds : array($layoutIds);

		return Query("select * from message_layout where messageLayoutId in :ids", array("ids"=>$layoutIds));
	}

	public static function recipientsFor($templateIds) {
		$templateIds = is_array($templateIds) ? $templateIds : array($templateIds);

		return Query(<<<SQL
select mtu.*, u.email
from message_template_user mtu
join users u on u.userId = mtu.userId
where mtu.messageTemplateId in :tids
SQL
		, array("tids"=>$templateIds));
	}

	public static function scanMessageFiles() {
		self::importLayouts(privatePath("/messages/layouts"));

		$existingTypes = self::importedTypes()->rowSet();
		$existingTemplates = self::templatesFor(pluck($existingTypes, "messageTypeId"))->rowSet();
		$layouts = self::existingLayouts()->rowSet();

		foreach (glob(privatePath("/messages/*")) as $dir) {
			$reference = basename($dir);

			if ($reference == "layouts") {
				continue;
			}

			if (!file_exists($dir . "/def.yml")) {
				continue;
			}

			$reference = basename($dir);
			$def = Spyc::YAMLLoad($dir . "/def.yml");
			self::importFromDef($dir, $reference, $def, $existingTypes, $existingTemplates, $layouts);
		}

		debug(WoolErrors::get());
	}

	private static function importLayouts($dir) {
		$templates = array();
		$existing = self::existingLayouts()->rowSet();

		foreach (glob($dir . "/*") as $file) {
			if (fileExtension($file) != "tpl") {
				continue;
			}

			$name = basename($file, ".tpl");
			$type = "content";

			if (substr($name, -6) == "_plain") {
				$name = substr($name, 0, -6);
				$type = "contentPlain";
			}

			if ($existing->by("name", $name)) {
				continue;
			}

			$content = file_get_contents($file);
			$templates[$name][$type] = $content;
		}

		foreach ($templates as $name=>$template) {
			$layout = WoolTable::blank("message_layout");
			$layout->name = $name;
			$layout->content = isset($template["content"]) ? $template["content"] : "";
			$layout->contentPlain = isset($template["contentPlain"]) ? $template["contentPlain"] : "";

			WoolTable::save($layout);
		}
	}

	private static function importFromDef($dir, $ref, $def, $types, $templates, $layouts) {
		if (!$def["name"] || !$def["templates"]) {
			return;
		}

		$type = $types->by("reference", $ref);
		if (!$type) {
			$type = WoolTable::blank("message_type");
			$type->name = $def["name"];
			$type->reference = $ref;
			$type->customCampaign = 0;

			WoolTable::save($type);
		}

		foreach ($def["templates"] as $template) {
			$layout = $layouts->by("name", isset($template["layout"]) ? $template["layout"] : "blank");

			if (!$layout) {
				continue;
			}

			if ($templates->by("name", $template["name"])) {
				continue;
			}

			$newTemplate = WoolTable::blank("message_template");
			$newTemplate->messageTypeId = $type->messageTypeId;
			$newTemplate->messageLayoutId = $layout->messageLayoutId;
			$newTemplate->name = $template["name"];
			$newTemplate->sendTarget = isset($template["sendTarget"]) ? $template["sendTarget"] : "email";
			$newTemplate->personalised = isset($template["personalised"]) ? $template["personalised"] : 0;

			if (file_exists($dir . "/" . $template["reference"] . ".tpl")) {
				$newTemplate->content = file_get_contents($dir . "/" . $template["reference"] . ".tpl");
			}
			if (file_exists($dir . "/" . $template["reference"] . "_plain.tpl")) {
				$newTemplate->contentPlain = file_get_contents($dir . "/" . $template["reference"] . "_plain.tpl");
			}

			WoolTable::save($newTemplate);
		}
	}

	public static function sendMessage($ref, $params=array(), $scheduledOn=null, $uri=null) {
		$messageType = WoolDb::fetchRow("select * from message_type where reference = ?", $ref);

		if (!$messageType->messageTypeId) {
			return;
		}

		self::$params = $params;

		$templates = self::templatesFor($messageType->messageTypeId)->rowSet();
		$layouts = self::layoutsFor(pluck($templates, "messageLayoutId"))->rowSet();
		$recipients = self::recipientsFor(pluck($templates, "messageTemplateId"))->rowSet();

		foreach ($templates as $template) {
			$layout = $layouts->by("messageLayoutId", $template->messageLayoutId);
			$tempRecip = $recipients->byGroup("messageTemplateId", $template->messageTemplateId);

			if (!$layout) {
				trigger_error("Missing layout for message template: '{$messageType->name}/{$template->name}'", E_USER_WARNING);
			}

			if (!count($tempRecip)) {
				continue;
			}

			if ($template->personalised) {
				foreach ($tempRecip as $recip) {
					list($content, $contentPlain) = self::renderMessage($layout, $template);

					$message = self::saveMessage($template, $content, $contentPlain, $uri);
					$user = self::saveMessageUser($message, $recip, $scheduledOn);
				}
			} else {
				list($content, $contentPlain) = self::renderMessage($layout, $template);

				$message = self::saveMessage($template, $content, $contentPlain, $uri);

				foreach ($tempRecip as $recip) {
					$user = self::saveMessageUser($message, $recip, $scheduledOn);
				}
			}
		}
	}

	private static $params = array();

	private static function saveMessage($template, $content, $contentPlain, $uri) {
		$message = WoolTable::blank("message");
		$message->messageTemplateId = $template->messageTemplateId;
		$message->sendTarget = $template->sendTarget;
		$message->content = $content;
		$message->contentPlain = $contentPlain;
		$message->uri = $uri;

		if (!WoolTable::save($message)) {
			trigger_error("Couldn't save message", E_USER_ERROR);
		}

		return $message;
	}

	private static function saveMessageUser($message, $recipient, $scheduledOn=null) {
		$user = WoolTable::blank("message_user");
		$user->messageId = $message->messageId;
		$user->address = $recipient->email;
		$user->userId = $recipient->userId;
		$user->scheduledOn = $scheduledOn ? $scheduledOn : now();
		$user->failed = false;

		if (!WoolTable::save($user)) {
			trigger_error("Couldn't save message user", E_USER_ERROR);
		}

		return $user;
	}

	private static function renderMessage($layout, $template) {
		$regex = "/{([\w\.]+)}/";
		
		self::$params["body"] = preg_replace_callback($regex, array("self", "messageReplaceCallback"), $template->content);
		$content = preg_replace_callback($regex, array("self", "messageReplaceCallback"), $layout->content);

		self::$params["body"] = preg_replace_callback($regex, array("self", "messageReplaceCallback"), $template->contentPlain);
		$contentPlain = preg_replace_callback($regex, array("self", "messageReplaceCallback"), $layout->contentPlain);

		return array($content, $contentPlain);
	}

	private static function messageReplaceCallback($matches) {
		$name = $matches[1];
		$names = explode(".", $name);

		$lookup = self::$params;

		foreach ($names as $name) {
			if (isset($lookup[$name])) {
				$lookup = $lookup[$name];
				continue;
			} else if (isset($lookup->$name)) {
				$lookup = $lookup->$name;
				continue;
			}

			trigger_error("Missing message parameter '{$name}'.", E_USER_WARNING);
		}

		return $lookup;
	}
}

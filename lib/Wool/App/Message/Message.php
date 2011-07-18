<?php

class WoolMessage {
	public static function importedTypes() {
		return Query("select * from message_type where customCampaign = false");
	}

	public static function templatesFor($typeId) {
		$typeId = is_array($typeId) ? $typeId : array($typeId);
		return Query("select * from message_template where messageTypeId in :tids", array("tids"=>$typeId));
	}

	public static function scanMessageFiles() {
		$existingTypes = self::importedTypes()->rowSet();
		$existingTemplates = self::templatesFor(pluck($existingTypes, "messageTypeId"))->rowSet();

		foreach (glob(privatePath("/messages/*")) as $dir) {
			if (!file_exists($dir . "/def.yml")) {
				continue;
			}

			$reference = basename($dir);
			$def = Spyc::YAMLLoad($dir . "/def.yml");
			self::importFromDef($dir, $reference, $def, $existingTypes, $existingTemplates);
		}
	}

	private static function importFromDef($dir, $ref, $def, $types, $templates) {
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
			if ($templates->by("name", $template["name"])) {
				continue;
			}

			$newTemplate = WoolTable::blank("message_template");
			$newTemplate->messageTypeId = $type->messageTypeId;
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

	public static function sendMessage($ref) {
		$row = WoolDb::fetchRow("select * from message_type where reference = ?", $ref);

		if (!$row->messageTypeId) {
			return;
		}

		$templates = self::templatesFor($row->messageTypeId)->rowSet();
	}
}
